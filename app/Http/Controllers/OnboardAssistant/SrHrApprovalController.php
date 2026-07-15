<?php

namespace App\Http\Controllers\OnboardAssistant;

use App\Http\Controllers\Controller;
use App\Models\EmployeesNewJoiner;
use App\Models\NewEmployeesDocument;
use App\Support\OnboardingLetterData;
use App\Support\OnboardingLetterDeadline;
use App\Support\OnboardingLetterDocument;
use App\Support\OnboardingMail;
use App\Support\OnboardingSrHrDocumentList;
use App\Support\SrHrLetterApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class SrHrApprovalController extends Controller
{
    public function show(string $token)
    {
        $employee = SrHrLetterApproval::findEmployeeByToken($token);
        if (!$employee) {
            return view('pages.sr-hr.approval', ['invalid' => true]);
        }

        $type = SrHrLetterApproval::typeForToken($employee, $token);
        if (!$type) {
            return view('pages.sr-hr.approval', ['invalid' => true]);
        }

        $state = SrHrLetterApproval::state($employee, $type);
        $hrPreparerName = OnboardingMail::hrUser($employee)?->name;

        if (($state['status'] ?? '') !== 'pending') {
            return view('pages.sr-hr.approval', [
                'employee' => $employee,
                'type' => $type,
                'token' => $token,
                'state' => $state,
                'alreadyDecided' => true,
                'hrPreparerName' => $hrPreparerName,
                'documentItems' => OnboardingSrHrDocumentList::items($employee, $token),
            ]);
        }

        $letter = $type === SrHrLetterApproval::TYPE_OFFER
            ? (($employee->emp_other ?? [])['offer_letter'] ?? [])
            : (($employee->emp_other ?? [])['appointment_letter'] ?? []);

        $approverOptions = SrHrLetterApproval::approverPoolFor($employee, $type);

        return view('pages.sr-hr.approval', [
            'employee' => $employee,
            'type' => $type,
            'token' => $token,
            'letter' => $letter,
            'approverOptions' => $approverOptions,
            'hrPreparerName' => $hrPreparerName,
            'previewUrl' => $type === SrHrLetterApproval::TYPE_OFFER
                ? route('offer.preview.pdf', ['id' => $employee->id, 'sr_hr_token' => $token])
                : route('appointment.preview.pdf', ['id' => $employee->id, 'sr_hr_token' => $token]),
            'documentItems' => OnboardingSrHrDocumentList::items($employee, $token),
        ]);
    }

    public function viewDocument(string $token, int $document)
    {
        $employee = SrHrLetterApproval::findEmployeeByToken($token);
        if (!$employee || !SrHrLetterApproval::typeForToken($employee, $token)) {
            abort(404);
        }

        $doc = NewEmployeesDocument::query()
            ->where('emp_id', $employee->id)
            ->where('id', $document)
            ->where('emp_document_status', '!=', 'upload')
            ->firstOrFail();

        if ($doc->emp_select_document === 'emergency_contact' || empty($doc->emp_document_file_path) || $doc->emp_document_file_path === '-') {
            abort(404);
        }

        $path = OnboardingLetterDocument::absolutePath($doc);
        if (!$path) {
            abort(404, 'File not found.');
        }

        $mime = mime_content_type($path) ?: 'application/octet-stream';
        $filename = (string) ($doc->emp_document_file ?: basename($path));

        return response()->file($path, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . addslashes($filename) . '"',
        ]);
    }

    public function decide(Request $request, string $token)
    {
        $employee = SrHrLetterApproval::findEmployeeByToken($token);
        if (!$employee) {
            return back()->with('error', 'Invalid or expired approval link.');
        }

        $employee->refresh();
        $type = SrHrLetterApproval::typeForToken($employee, $token);
        if (!$type) {
            return back()->with('error', 'Invalid approval link for this candidate.');
        }

        $state = SrHrLetterApproval::state($employee, $type);

        if (($state['status'] ?? '') !== 'pending') {
            return back()->with('error', 'This letter was already reviewed.');
        }

        $request->validate([
            'decision' => 'required|in:approve,reject',
            'approver_email' => 'required|email',
            'reject_reason' => [
                'nullable',
                Rule::requiredIf(fn () => $request->input('decision') === 'reject'),
                'string',
                'min:10',
                'max:2000',
            ],
        ]);

        $approverEmail = strtolower(trim($request->input('approver_email')));
        if (!SrHrLetterApproval::isAllowedApproverEmail($employee, $type, $approverEmail)) {
            return back()
                ->withInput()
                ->with('error', 'Your email is not on the approver list for this letter. Use the work email that received the approval request.');
        }

        if ($request->input('decision') === 'reject') {
            SrHrLetterApproval::reject($employee, $type, $request->input('reject_reason'), $approverEmail);
            try {
                OnboardingMail::candidateActivity(
                    $employee,
                    ($type === SrHrLetterApproval::TYPE_OFFER ? 'Offer' : 'Appointment') . ' SR-HR rejected',
                    'SR-HR (' . $approverEmail . '): ' . $request->input('reject_reason')
                );
            } catch (\Throwable $e) {
                report($e);
            }

            return redirect()->route('sr-hr.approval.show', $token)
                ->with('success', 'Rejection recorded. HR will revise and resubmit.');
        }

        try {
            SrHrLetterApproval::approve($employee, $type, $approverEmail);
            $employee->refresh();

            $mailError = null;
            try {
                if ($type === SrHrLetterApproval::TYPE_OFFER) {
                    $this->sendOfferToCandidate($employee);
                } else {
                    $this->finalizeAppointmentAfterSrHrApproval($employee);
                }
            } catch (\Throwable $e) {
                report($e);
                $mailError = $e->getMessage();
            }

            $msg = $type === SrHrLetterApproval::TYPE_OFFER
                ? ($mailError
                    ? 'Approved. HR can resend to the candidate if the notification email failed.'
                    : 'Approved. Candidate has been notified.')
                : ($mailError
                    ? 'Approved. PDF saved but candidate email may have failed — HR can follow up from the candidate record.'
                    : 'Approved. Signed appointment letter saved and candidate has been notified that onboarding is complete.');

            return redirect()->route('sr-hr.approval.show', $token)->with('success', $msg);
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Could not save approval: ' . $e->getMessage());
        }
    }

    protected function sendOfferToCandidate(EmployeesNewJoiner $employee): void
    {
        $employee->emp_offer_sent_at = now();
        OnboardingLetterDeadline::assignOfferDueDate($employee);
        $employee->emp_offer_letter_status = 'pending';
        $employee->emp_offer_reject_reason = null;
        $employee->emp_signature = null;
        $employee->save();
        $employee->setOnboardingStep('offer_sent');

        $offer = ($employee->emp_other ?? [])['offer_letter'] ?? [];
        $portalLink = route('onboarding.portal', $employee->emp_url) . '?tab=letter';
        $offerLink = route('EmployeeJoiner.documents.viewOffer', $employee->id);

        OnboardingMail::deliver('emails.offer_letter_sent', [
            'employee' => $employee,
            'portalLink' => $portalLink,
            'offerLink' => $offerLink,
            'offer' => $offer,
        ], function ($message) use ($employee) {
            $message->to($employee->emp_email)
                ->subject('Offer Letter – ' . config('app.name', 'Ami Polymer'));
        });

        try {
            OnboardingMail::candidateActivity($employee, 'Offer letter sent', 'SR-HR approved; offer emailed to candidate.');
        } catch (\Throwable $e) {
            Log::warning('Offer sent mail ok but activity log failed: ' . $e->getMessage());
        }
    }

    /** Candidate already accepted; SR-HR approval archives final signed PDF. */
    protected function finalizeAppointmentAfterSrHrApproval(EmployeesNewJoiner $employee): void
    {
        $employee->refresh();

        $pdfDocument = OnboardingLetterDocument::archiveFromView(
            $employee,
            OnboardingLetterDocument::APPOINTMENT_SR_APPROVED,
            'pdf.appointment_letter_pdf',
            [
                'employee' => $employee,
                'appointment' => OnboardingLetterData::appointment($employee),
            ]
        );

        if ($employee->onboardingStep() !== 'appointment_accepted') {
            $employee->setOnboardingStep('appointment_accepted');
        }

        OnboardingMail::appointmentSrHrApproved($employee, $pdfDocument);
    }
}
