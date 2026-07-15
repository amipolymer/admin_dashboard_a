<?php

namespace App\Http\Controllers\OnboardAssistant;

use App\Data\DocumentNamesList;
use App\Data\LevelWiseGrading;
use App\Http\Controllers\Controller;
use App\Models\EmployeesNewJoiner;
use App\Models\NewEmployeesDocument;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Support\OnboardingArchive;
use App\Support\OnboardingLetterDocument;
use App\Support\OnboardingFileRules;
use App\Support\OnboardingMail;
use App\Support\OfferLetterCompensation;
use App\Support\OnboardingLetterResend;
use App\Support\OnboardingDocumentReedit;
use App\Support\OnboardingEarlyJoin;
use App\Support\OnboardingHrCombinedDocument;
use App\Support\OnboardingHrDocumentReupload;
use App\Support\OnboardingProfileReedit;
use App\Support\OnGrid;
use App\Support\OnboardingStepGate;
use App\Support\OnboardingSignature;
use App\Support\SrHrLetterApproval;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use ZipArchive;

class EmployeeNewJoinerController extends Controller
{
    public $Url = 'Mph6MSf9L4NU4NWRRuxTYAvZM';

    public function index()
    {
        $userRole = Auth::user()->role;
        if ($userRole == 'hr') {
            $employeesJoinerList = EmployeesNewJoiner::where('emp_hr_id', Auth::user()->id)->latest()->get();
        } else {
            $employeesJoinerList = EmployeesNewJoiner::latest()->get();
        }

        return view(
            "pages.new-join-employee.index",
            compact("employeesJoinerList")
        );
    }

    public function create()
    {
        $roleList = [];
        return view("pages.new-join-employee.create", compact("roleList"));
    }

    public function store(Request $request)
    {

        $request->validate([
            "emp_hr_id" => "required",
            "emp_name" => "required|string",
            "emp_location" => "required|string",
            "emp_phone" => "required|unique:employees_new_joiners,emp_phone",
            "emp_email" => "required|email|unique:employees_new_joiners,emp_email",
            "emp_role" => "required|string|max:255",
            "emp_level_grading" => "required|string",
            "emp_grade" => "required|string|max:32",
            "emp_category" => "required|string|max:64",
            "emp_date" => "required|date",
            "emp_department" => "required|string",
            "emp_dob" => "required|date",
            "emp_mrf_no" => "required|string",
            "emp_document_due_date" => "required|date",

            // New file validations
            "emp_cv_file" => "required|file|mimes:pdf",
            "emp_interview_evaluation_file" => "required|file|mimes:pdf",
            "emp_mrf_file" => "required|file|mimes:pdf",
            "employment_type" => "required|in:fresher,experienced",
        ]);

        $this->assertValidLevelGradingSelection($request);
        $emp_url = Str::random(80);
        $employee = EmployeesNewJoiner::create([
            "emp_hr_id" => $request->emp_hr_id,
            "emp_name" => $request->emp_name,
            "emp_phone" => $request->emp_phone,
            "emp_location" => $request->emp_location,
            "emp_role" => $request->emp_role,
            "emp_grade" => $request->emp_grade,
            "emp_category" => $request->emp_category,
            "emp_date" => $request->emp_date,
            "emp_document_due_date" => $request->emp_document_due_date,
            "emp_email" => $request->emp_email,
            "emp_url" => $emp_url,
            "emp_department" => $request->emp_department,
            "emp_dob" => $request->emp_dob,
            "emp_mrf_no" => $request->emp_mrf_no,
            "emp_status" => "active",
            "emp_onboarding_step" => "start",
            "emp_onboarding_status" => "active",
        ]);
        $uploadLink = route('onboarding.portal', $emp_url);
        $documentNamesList = DocumentNamesList::all();
        OnboardingMail::deliver(
            "emails.new_employee_upload",
            [
                "employee" => $employee,
                "uploadLink" => $uploadLink,
                "documentNamesList" => $documentNamesList,
                "dueDate" => Carbon::parse(
                    $employee->emp_document_due_date
                )->format("d M Y"),
            ],
            function ($message) use ($employee) {
                $message
                    ->to($employee->emp_email)
                    ->subject("Upload Your Joining Documents");
            }
        );

        // Fetch employee using emp_url
        $employee_1 = EmployeesNewJoiner::findOrFail($employee->id);

        $folder = "temp/employees/EMP_{$employee_1->id}";

        // Define all files with their document types
        $documents = [
            "cv" => $request->file("emp_cv_file"),
            "interview_evaluation" => $request->file("emp_interview_evaluation_file"),
            "mrf_file" => $request->file("emp_mrf_file"),
        ];

        foreach ($documents as $type => $file) {
            if ($file) {
                $extension = $file->getClientOriginalExtension();

                $fileName = "EMP_" . $employee_1->id . "_" . $type . "." . $extension;

                $filePath = Storage::disk("public")->putFileAs(
                    $folder,
                    $file,
                    $fileName
                );

                NewEmployeesDocument::updateOrCreate(
                    [
                        "emp_id" => $employee_1->id,
                        "emp_select_document" => $type,
                    ],
                    [
                        "emp_document_file" => $fileName,
                        "emp_document_file_path" => "storage/" . $filePath,
                        "emp_document_status" => "approved",
                        "emp_doc_date" => Carbon::now(),
                        "emp_hr_id" => $employee->emp_hr_id,
                        "approval_date" => null,
                        "rejection_reason" => null,
                    ]
                );
            }
        }
        $employee_1->emp_folder = "EMP_" . $employee_1->id;
        $employee_1->emp_folder_path = 'temp/employees/';
        \App\Support\CandidateEmploymentType::persist($employee_1, $request->input('employment_type', 'experienced'));
        $employee_1->save();

        return redirect()
            ->route("EmployeeJoiner.Create")
            ->with([
                "success" => "Employee joined successfully",
                "bg-color" => "success",
            ]);
    }

    public function edit($id)
    {
        $main_url = config('app.main_url');
        $employee = EmployeesNewJoiner::findOrFail($id);
        $documentNamesList = DocumentNamesList::all();
        $document_list = NewEmployeesDocument::where("emp_id", $employee->id)->get();
        return view("pages.new-join-employee.edit", compact("employee", "document_list", "main_url", "documentNamesList"));
    }

    public function show($id)
    {
        $main_url = config('app.main_url');
        $employee = EmployeesNewJoiner::findOrFail($id);
        $documentNamesList = DocumentNamesList::all();
        $document_list = NewEmployeesDocument::where("emp_id", $employee->id)->get();
        $documentNames = collect($documentNamesList)->collapse()->toArray();
        $assetPrefix = config('app.asset_prefix');

        return view("pages.new-join-employee.show", compact(
            "employee",
            "document_list",
            "main_url",
            "documentNamesList",
            "documentNames",
            "assetPrefix"
        ));
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());
        $employee = EmployeesNewJoiner::findOrFail($id);
        if (!empty($request->has('emergency_contact'))) {
        } else {
            $employee->emergency_contact = $request->emergency_contact;
        }
        $employee->emp_date = $request->emp_date;
        $employee->emp_document_due_date = $request->emp_document_due_date;
        $employee->emp_location = $request->emp_location;
        $employee->emp_phone = $request->emp_phone;
        if ($request->filled('emp_level_grading')) {
            $request->validate([
                'emp_level_grading' => 'required|string',
                'emp_role' => 'required|string|max:255',
                'emp_grade' => 'required|string|max:32',
                'emp_category' => 'required|string|max:64',
            ]);
            $this->assertValidLevelGradingSelection($request);
            $employee->emp_role = $request->emp_role;
            $employee->emp_grade = $request->emp_grade;
            $employee->emp_category = $request->emp_category;
        } elseif ($request->filled('emp_role')) {
            $employee->emp_role = $request->emp_role;
        }
        $employee->emp_email = $request->emp_email;
        $employee->emp_name = $request->emp_name;
        $employee->emp_status = $request->emp_status;
        if ($request->filled('employment_type')) {
            $request->validate(['employment_type' => 'in:fresher,experienced']);
            \App\Support\CandidateEmploymentType::persist($employee, $request->employment_type);
        }
        if ($request->emp_status == 'active') {
            $employee->emp_document_status = 'process';
        }
        $employee->save();
        return redirect()->back()->with([
            'success'  => 'Employee updated successfully',
            'bg-color' => 'success',
        ]);
    }

    public function hrReuploadDocument(Request $request, $id)
    {
        $employee = EmployeesNewJoiner::findOrFail($id);

        if (!OnboardingHrDocumentReupload::hrCanReupload($employee)) {
            return back()->with('error', 'Cannot re-upload documents after onboarding is finalized.');
        }

        $request->validate([
            'document_id' => 'required|integer',
            'document_file' => 'required|file',
            'reupload_reason' => 'required|string|max:1000',
        ], [
            'document_file.required' => 'Please select a file to upload.',
            'reupload_reason.required' => 'Please enter a reason for this update.',
        ]);

        $document = NewEmployeesDocument::query()
            ->where('emp_id', $employee->id)
            ->findOrFail($request->input('document_id'));

        if (!OnboardingHrDocumentReupload::canReuploadDocument($document)) {
            $message = OnboardingHrDocumentReupload::reuploadBlockedMessage($document)
                ?? 'This document cannot be re-uploaded.';

            return back()->with('error', $message)->withInput();
        }

        $fileRule = OnboardingHrDocumentReupload::fileValidationRule($document, $employee);
        $request->validate([
            'document_file' => $fileRule,
        ], [
            'document_file.mimes' => OnboardingFileRules::mimeErrorMessage($document->emp_select_document),
        ]);

        try {
            OnboardingHrDocumentReupload::store(
                $employee,
                $document,
                $request->file('document_file'),
                trim($request->input('reupload_reason'))
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        $label = OnboardingHrDocumentReupload::documentLabel($document->emp_select_document);

        return back()->with([
            'success' => "{$label} updated successfully.",
            'bg-color' => 'success',
        ]);
    }

    public function updateDocument(Request $request, $id)
    {
        $employee = EmployeesNewJoiner::findOrFail($id);

        $uploadLink = route('onboarding.portal', $employee->emp_url);

        $data = $request->only([
            'emp_name',
            'emp_email',
            'emp_phone',
            'emp_role',
            'emp_location',
            'emp_date',
            'emp_document_due_date',
        ]);

        $rejectedFiles = [];
        $hasRejection  = false;
        $allRemarks    = [];


        //   Process document statuses

        if ($request->filled('doc_status')) {
            foreach ($request->input('doc_status') as $docId => $status) {

                $document = NewEmployeesDocument::find($docId);
                if (!$document) {
                    continue;
                }

                $document->emp_document_status = $status;

                if ($status === 'rejected') {

                    $remark = trim($request->input("doc_remark.$docId", ''));

                    if (empty($remark)) {
                        return back()
                            ->withErrors(["doc_remark.$docId" => 'Rejection reason is required'])
                            ->withInput();
                    }

                    $document->rejection_reason = $remark;

                    $rejectedFiles[] = $document->emp_document_file . ' : ' . $remark;

                    $hasRejection = true;
                    $allRemarks[] = $remark;
                } else {
                    $document->rejection_reason = null;
                }

                $document->save();
            }
        }


        //   Final statuses — completed when every reviewable doc (not upload) is approved
        $finalDocStatus = 'process';
        $finalEmpStatus = $employee->emp_status;

        $hasReviewableDocs = NewEmployeesDocument::where('emp_id', $employee->id)
            ->where('emp_document_status', '!=', 'upload')
            ->exists();

        $allReviewableApproved = $hasReviewableDocs && !NewEmployeesDocument::where('emp_id', $employee->id)
            ->where('emp_document_status', '!=', 'upload')
            ->where('emp_document_status', '!=', 'approved')
            ->exists();

        if ($hasRejection) {

            $finalDocStatus = 'rejected';
            $finalEmpStatus = 'active';
            $employee->setOnboardingStep('documents_rejected');
        } elseif ($allReviewableApproved) {

            $finalDocStatus = 'completed';
            $finalEmpStatus = 'active';
            if (OnboardingDocumentReedit::shouldEnableBgvAfterDocumentApproval($employee)) {
                OnboardingDocumentReedit::markReadyForBgvAfterApproval($employee);
            } elseif (!OnboardingDocumentReedit::isPostOfferStage($employee)
                && $employee->onboardingStep() !== 'documents_approved') {
                $employee->setOnboardingStep('documents_approved');
            }
        } elseif ($request->filled('doc_status')) {
            $finalDocStatus = 'process';
        }

        $data['emp_document_status'] = $finalDocStatus;
        $data['emp_status']          = $finalEmpStatus;

        //  | Store rejection remarks
        if (!empty($allRemarks)) {

            $other      = $employee->emp_other ?? [];
            $rejections = $other['rejections'] ?? [];

            $rejections[] = [
                'sr'          => count($rejections) + 1,
                'date'        => now()->toDateString(),
                'remark'      => implode(", \n", $allRemarks),
                'document_id' => $request->input('document_id', []),
            ];

            $data['emp_other'] = array_merge($other, [
                'rejections' => $rejections
            ]);
        }


        //   Send rejection email

        if ($hasRejection && !empty($rejectedFiles)) {

            OnboardingMail::deliver(
                'emails.new_employee_re_upload',
                [
                    'employee'   => $employee,
                    'uploadLink' => $uploadLink,
                    'file_name'  => $rejectedFiles,
                    'remark'     => 'Please check individual remarks on documents',
                    'dueDate'    => Carbon::parse(
                        $employee->emp_document_due_date ?? now()->addDays(7)
                    )->format('d M Y'),
                ],
                function ($message) use ($employee) {
                    $message->to($employee->emp_email)
                        ->subject('Re-Upload Joining Documents');
                }
            );
        }

        //  | Update remaining employee fields
        $employee->update($data);

        $message = $finalDocStatus === 'completed'
            ? (OnboardingDocumentReedit::canStartBgv($employee->fresh())
                ? 'All documents approved. Start OnGrid BGV from Onboarding Actions below.'
                : 'All documents approved. Document status set to completed.')
            : ($finalDocStatus === 'rejected'
                ? 'Documents updated. Re-upload request sent to candidate.'
                : 'Employee documents updated successfully');

        return redirect()->back()->with([
            'success'  => $message,
            'bg-color' => 'success',
        ]);
    }

    // public function delete($id)
    // {
    //     // EmployeesNewJoiner::findOrFail($id)->delete();
    //     return back()->with("success", "Employee deleted");
    // }

    public function delete($id)
    {
        DB::transaction(function () use ($id) {

            $employee = EmployeesNewJoiner::withTrashed()->findOrFail($id);

            // Permanently delete related documents
            NewEmployeesDocument::where('emp_id', $employee->id)
                ->forceDelete();

            // Paths relative to storage/app/public
            $folder1 = 'uploads/employees/' . $employee->emp_folder;
            $folder2 = 'team/employees/' . $employee->emp_folder;

            // Delete folders
            if (Storage::disk('public')->exists($folder1)) {
                Storage::disk('public')->deleteDirectory($folder1);
            }

            if (Storage::disk('public')->exists($folder2)) {
                Storage::disk('public')->deleteDirectory($folder2);
            }

            // Permanently delete employee
            $employee->forceDelete();
        });

        return back()->with([
            'success'  => 'Employee deleted successfully!',
            'bg-color' => 'success',
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $employee = EmployeesNewJoiner::findOrFail($id);
        $employee->update([
            "emp_document_status" => $request->emp_document_status,
            "emp_last_updated_at" => now(),
        ]);

        return back()->with("success", "Status updated");
    }

    public function DocVerify($id)
    {
        $document_list = NewEmployeesDocument::findOrFail($id);
        if ($document_list->emp_document_status == 'process') {
            $document_list->emp_document_status = 'approved';
        } else {
            $document_list->emp_document_status = 'process';
        }
        $document_list->save();
        return redirect()->back()->with('success', 'Document approved successfully!');
    }

    public function reSendEmail($id, $type, $remarkId)
    {
        $employee = EmployeesNewJoiner::findOrFail($id);

        if ($type !== 'reupload' && !OnboardingStepGate::canResendPortalLink($employee)) {
            return redirect()->back()->with('error', 'Portal link can only be resent before the candidate submits their profile.');
        }

        $documentNamesList = DocumentNamesList::all();
        $uploadLink = route('onboarding.portal', $employee->emp_url);

        if ($type == 'reupload') {
            $file_name = [];
            $remarkList = $employee->emp_other['rejections'];
            $document_ids = $remarkList[$remarkId]['document_id'];
            foreach ($document_ids as $document) {
                $document_list = NewEmployeesDocument::findOrFail($document);
                if ($document_list->emp_document_status == 'rejected') {
                    $file_name[] = $document_list->emp_document_file;
                }
            }
            $remark = $remarkList[$remarkId]['remark'];
            OnboardingMail::deliver(
                "emails.new_employee_re_upload",
                [
                    "employee" => $employee,
                    "uploadLink" => $uploadLink,
                    "file_name" => $file_name,
                    "remark" => $remark,
                    "dueDate" => Carbon::parse(
                        $employee->emp_document_due_date
                    )->format("d M Y"),
                ],
                function ($message) use ($employee) {
                    $message
                        ->to($employee->emp_email)
                        ->subject("Re-Upload Your Joining Documents Resend");
                }
            );
        } else {
            OnboardingMail::deliver(
                "emails.new_employee_upload",
                [
                    "employee" => $employee,
                    "uploadLink" => $uploadLink,
                    "documentNamesList" => $documentNamesList,
                    "dueDate" => Carbon::parse(
                        $employee->emp_document_due_date
                    )->format("d M Y"),
                ],
                function ($message) use ($employee) {
                    $message
                        ->to($employee->emp_email)
                        ->subject("Upload Your Joining Documents-resend");
                }
            );
        }
        return redirect()->back()
            ->with([
                "success" => "Email resent successfully!",
                "bg-color" => "success",
            ]);
    }


    public function downloadAll($id)
    {
        $employee = EmployeesNewJoiner::findOrFail($id);
        $documents = NewEmployeesDocument::where('emp_id', $employee->id)->get();

        $zipFileName = $employee->emp_folder . '_Documents.zip';
        $zipFilePath = storage_path('app/public/' . $zipFileName);

        $zip = new \ZipArchive();
        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            foreach ($documents as $doc) {
                $filePath = storage_path('app/public/uploads/employees/' . $employee->emp_folder . '/' . $doc->emp_document_file);
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, $doc->emp_document_file);
                }
            }
            $zip->close();

            return response()->download($zipFilePath)->deleteFileAfterSend(true);
        } else {
            return redirect()->back()->with('error', 'Could not create zip file.');
        }
    }

    public function viewOffer(Request $request, $id)
    {
        $employee = EmployeesNewJoiner::findOrFail($id);

        if (\App\Support\OnboardingLetterDeadline::isOfferExpired($employee)) {
            return response()->view('shared.letter-preview-blocked', [
                'message' => \App\Support\OnboardingLetterDeadline::expiredMessage(\App\Support\OnboardingLetterDeadline::TYPE_OFFER),
            ], 403);
        }

        return redirect()->route('onboarding.portal', ['token' => $employee->emp_url, 'tab' => 'letter']);
    }

    public function previewOffer(Request $request, $id)
    {
        $employee = EmployeesNewJoiner::findOrFail($id);

        if ($request->filled('token')) {
            if ($request->input('token') !== $employee->emp_url) {
                abort(403, 'Invalid link.');
            }
            if (!\App\Support\OnboardingLetterDeadline::canCandidateViewOffer($employee)) {
                return response()->view('shared.letter-preview-blocked', [
                    'message' => \App\Support\OnboardingLetterDeadline::expiredMessage(\App\Support\OnboardingLetterDeadline::TYPE_OFFER),
                ], 403);
            }
        } else {
            $this->assertLetterPreviewAccess($request, $employee, SrHrLetterApproval::TYPE_OFFER);
        }

        $extra = [];
        if ($request->boolean('simulate_accepted') && $this->letterPreviewAllowsInspect()) {
            $extra['testSimulateAccepted'] = true;
            $extra['testPlaceholderSignature'] = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAAFUlEQVR42mNk+M9Qz0AEYBxVSF+FABJADveWkH6oAAAAAElFTkSuQmCC';
        }

        return $this->renderOfferLetterHtmlPreview($employee, $extra);
    }

    protected function getOfferLetterData(EmployeesNewJoiner $employee): array
    {
        return \App\Support\OnboardingLetterData::offer($employee);
    }
    
    public function storeSignature(Request $request)
    {
        $request->validate([
            'emp_id' => 'required|integer',
            'signature_file' => OnboardingFileRules::signatureFileRule(),
            'signature' => 'nullable|string',
            'return_url' => 'nullable|string|max:500',
        ], [
            'signature_file.mimes' => 'Signature must be JPG or PNG image.',
        ]);

        $employee = EmployeesNewJoiner::findOrFail($request->emp_id);

        if (!\App\Support\OnboardingLetterDeadline::canCandidateActOnOffer($employee)) {
            $message = \App\Support\OnboardingLetterDeadline::isOfferExpired($employee)
                ? \App\Support\OnboardingLetterDeadline::expiredMessage(\App\Support\OnboardingLetterDeadline::TYPE_OFFER) . ' Please contact HR.'
                : 'This offer cannot be accepted at this stage.';

            return $this->offerActionResponse($request, 'error', $message);
        }

        $signatureData = OnboardingSignature::fromRequest($request);

        if (!$signatureData) {
            return $this->offerActionResponse($request, 'error', 'Please draw or upload your signature.');
        }

        $employee->emp_signature = $signatureData;
        $employee->emp_offer_letter_status = 'accept';
        $employee->emp_offer_reject_reason = null;
        $employee->save();

        if ($employee->onboardingStep() === 'offer_sent') {
            $employee->setOnboardingStep('offer_accepted');
        }

        $employee->refresh();
        try {
            OnboardingLetterDocument::archiveFromView(
                $employee,
                OnboardingLetterDocument::OFFER,
                'pdf.offer_letter_pdf',
                [
                    'employee' => $employee,
                    'offer' => $this->getOfferLetterData($employee),
                ]
            );
        } catch (\Throwable $e) {
            report($e);
            return $this->offerActionResponse($request, 'error', 'Offer accepted but PDF could not be saved. Please contact HR.');
        }

        OnboardingMail::offerAccepted($employee);

        return $this->offerActionResponse($request, 'success', 'Offer accepted. Signed offer letter saved as PDF in HR documents. Confirmation emails sent.');
    }

    public function rejectOffer(Request $request)
    {
        $request->validate([
            'emp_id' => 'required|integer',
            'reason' => \App\Support\OnboardingLetterRejectReason::rules(),
            'return_url' => 'nullable|string|max:500',
        ], \App\Support\OnboardingLetterRejectReason::messages());

        $employee = EmployeesNewJoiner::findOrFail($request->emp_id);

        if ($employee->emp_offer_letter_status === 'accept') {
            return $this->offerActionResponse($request, 'error', 'Offer was already accepted.');
        }

        if (!\App\Support\OnboardingLetterDeadline::canCandidateActOnOffer($employee)) {
            $message = \App\Support\OnboardingLetterDeadline::isOfferExpired($employee)
                ? \App\Support\OnboardingLetterDeadline::expiredMessage(\App\Support\OnboardingLetterDeadline::TYPE_OFFER) . ' Please contact HR.'
                : 'This offer cannot be declined at this stage.';

            return $this->offerActionResponse($request, 'error', $message);
        }

        $employee->emp_offer_letter_status = 'reject';
        $employee->emp_offer_reject_reason = $request->reason;
        $employee->save();

        if ($employee->onboardingStep() === 'offer_sent') {
            $employee->setOnboardingStep('offer_rejected');
        }

        OnboardingMail::offerRejected($employee, $request->reason);

        return $this->offerActionResponse($request, 'success', 'Offer declined. Your reason has been sent to HR.');
    }

    protected function employeeCanRespondToOffer(EmployeesNewJoiner $employee): bool
    {
        return \App\Support\OnboardingLetterDeadline::canCandidateActOnOffer($employee);
    }

    public function viewAppointment($id)
    {
        $employee = EmployeesNewJoiner::findOrFail($id);

        if (OnboardingLetterDocument::latestSrHrApprovedAppointment($employee)) {
            return redirect()->route('appointment.signed.pdf', $employee->id);
        }

        if (\App\Support\OnboardingLetterDeadline::isAppointmentExpired($employee)) {
            return response()->view('shared.letter-preview-blocked', [
                'message' => \App\Support\OnboardingLetterDeadline::expiredMessage(\App\Support\OnboardingLetterDeadline::TYPE_APPOINTMENT),
            ], 403);
        }

        return view('pdf.appointment_letter_view', compact('employee'));
    }

    /** SR-HR approved signed appointment letter (PDF file, not HTML). */
    public function downloadSignedAppointmentPdf($id)
    {
        $employee = EmployeesNewJoiner::findOrFail($id);
        $document = OnboardingLetterDocument::latestSrHrApprovedAppointment($employee);

        if (!$document) {
            abort(404, 'Signed appointment letter PDF is not available yet.');
        }

        $path = OnboardingLetterDocument::absolutePath($document);
        if (!$path) {
            abort(404, 'Appointment letter PDF file not found on server.');
        }

        $filename = 'Appointment_Letter_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $employee->emp_name ?: 'candidate') . '.pdf';

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    public function previewAppointment(Request $request, $id)
    {
        $employee = EmployeesNewJoiner::findOrFail($id);

        if ($request->filled('token')) {
            if ($request->input('token') !== $employee->emp_url) {
                abort(403, 'Invalid link.');
            }
            if (!\App\Support\OnboardingLetterDeadline::canCandidateViewAppointment($employee)) {
                return response()->view('shared.letter-preview-blocked', [
                    'message' => \App\Support\OnboardingLetterDeadline::expiredMessage(\App\Support\OnboardingLetterDeadline::TYPE_APPOINTMENT),
                ], 403);
            }
        } else {
            $this->assertLetterPreviewAccess($request, $employee, SrHrLetterApproval::TYPE_APPOINTMENT);
        }

        $extra = [];
        if ($request->boolean('simulate_accepted') && $this->letterPreviewAllowsInspect()) {
            $extra['testSimulateAccepted'] = true;
            $extra['testPlaceholderSignature'] = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAAFUlEQVR42mNk+M9Qz0AEYBxVSF+FABJADveWkH6oAAAAAElFTkSuQmCC';
        }

        return $this->renderAppointmentLetterHtmlPreview($employee, $extra);
    }

    /**
     * Unified HTML preview for offer letter (HR, candidate, superadmin).
     *
     * @param  array<string, mixed>  $extra
     */
    protected function renderOfferLetterHtmlPreview(EmployeesNewJoiner $employee, array $extra = [])
    {
        return view('pdf.offer_letter_pdf', array_merge([
            'employee' => $employee,
            'offer' => $this->getOfferLetterData($employee),
            'signatureAsUrl' => true,
            'letterRenderMode' => 'html',
            'letterViewOnly' => true,
            'letterAllowInspect' => $this->letterPreviewAllowsInspect(),
        ], $extra));
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    protected function renderAppointmentLetterHtmlPreview(EmployeesNewJoiner $employee, array $extra = [])
    {
        return view('pdf.appointment_letter_pdf', array_merge([
            'employee' => $employee,
            'appointment' => $this->getAppointmentLetterData($employee),
            'signatureAsUrl' => true,
            'letterRenderMode' => 'html',
            'letterViewOnly' => true,
            'letterAllowInspect' => $this->letterPreviewAllowsInspect(),
        ], $extra));
    }

    protected function letterPreviewAllowsInspect(): bool
    {
        $user = Auth::user();

        return $user && $user->role === 'superadmin';
    }

    /**
     * Superadmin only — preview HTML or download PDF for offer / appointment letter testing.
     */
    public function letterPdfTest(Request $request, $id, string $type)
    {
        if (!Auth::check() || Auth::user()->role !== 'superadmin') {
            abort(403, 'Superadmin access required.');
        }

        if (!in_array($type, ['offer', 'appointment'], true)) {
            abort(404);
        }

        $employee = EmployeesNewJoiner::findOrFail($id);
        $format = $request->input('format', 'pdf');
        $testSimulateAccepted = $request->boolean('simulate_accepted');
        $testPlaceholderSignature = $testSimulateAccepted
            ? 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAAFUlEQVR42mNk+M9Qz0AEYBxVSF+FABJADveWkH6oAAAAAElFTkSuQmCC'
            : null;
        $signatureAsUrl = $format === 'html';

        if ($type === 'offer') {
            $offer = $this->getOfferLetterData($employee);
            $view = 'pdf.offer_letter_pdf';
            $viewData = compact('employee', 'offer', 'signatureAsUrl', 'testSimulateAccepted', 'testPlaceholderSignature');
            $filename = 'Offer_Letter_Test_' . $employee->id . '.pdf';
        } else {
            $appointment = $this->getAppointmentLetterData($employee);
            $view = 'pdf.appointment_letter_pdf';
            $viewData = compact('employee', 'appointment', 'signatureAsUrl', 'testSimulateAccepted', 'testPlaceholderSignature');
            $filename = 'Appointment_Letter_Test_' . $employee->id . '.pdf';
        }

        if ($format === 'html') {
            if ($type === 'offer') {
                return $this->renderOfferLetterHtmlPreview($employee, compact('testSimulateAccepted', 'testPlaceholderSignature'));
            }

            return $this->renderAppointmentLetterHtmlPreview($employee, compact('testSimulateAccepted', 'testPlaceholderSignature'));
        }

        $html = view($view, array_merge($viewData, [
            'signatureAsUrl' => false,
            'letterRenderMode' => 'pdf',
        ]))->render();
        $pdf = \App\Support\OnboardingLetterPdf::fromHtml($html);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    protected function getAppointmentLetterData(EmployeesNewJoiner $employee): array
    {
        return \App\Support\OnboardingLetterData::appointment($employee);
    }

    public function storeAppointmentSignature(Request $request)
    {
        $request->validate([
            'emp_id' => 'required|integer',
            'signature_file' => OnboardingFileRules::signatureFileRule(),
            'signature' => 'nullable|string',
            'return_url' => 'nullable|string|max:500',
        ], [
            'signature_file.mimes' => 'Signature must be JPG or PNG image.',
        ]);

        $employee = EmployeesNewJoiner::findOrFail($request->emp_id);

        if (!\App\Support\OnboardingLetterDeadline::canCandidateActOnAppointment($employee)) {
            $message = \App\Support\OnboardingLetterDeadline::isAppointmentExpired($employee)
                ? \App\Support\OnboardingLetterDeadline::expiredMessage(\App\Support\OnboardingLetterDeadline::TYPE_APPOINTMENT) . ' Please contact HR.'
                : 'Appointment cannot be accepted at this stage.';

            return $this->offerActionResponse($request, 'error', $message);
        }

        $signatureData = OnboardingSignature::fromRequest($request);
        if (!$signatureData) {
            return $this->offerActionResponse($request, 'error', 'Please draw or upload your signature.');
        }

        $other = $employee->emp_other ?? [];
        $other['appointment_signature'] = $signatureData;
        $employee->emp_other = $other;
        $employee->emp_appointment_letter_status = 'accept';
        $employee->save();
        $employee->setOnboardingStep('appointment_accepted');
        $employee->refresh();

        OnboardingMail::appointmentAcceptedByCandidate($employee);

        return $this->offerActionResponse(
            $request,
            'success',
            'Appointment accepted. HR will submit to SR-HR for final approval; you will be notified when onboarding is complete.'
        );
    }

    public function rejectAppointment(Request $request)
    {
        $request->validate([
            'emp_id' => 'required|integer',
            'reason' => \App\Support\OnboardingLetterRejectReason::rules(),
            'return_url' => 'nullable|string|max:500',
        ], \App\Support\OnboardingLetterRejectReason::messages());

        $employee = EmployeesNewJoiner::findOrFail($request->emp_id);

        if (!\App\Support\OnboardingLetterDeadline::canCandidateActOnAppointment($employee)) {
            $message = \App\Support\OnboardingLetterDeadline::isAppointmentExpired($employee)
                ? \App\Support\OnboardingLetterDeadline::expiredMessage(\App\Support\OnboardingLetterDeadline::TYPE_APPOINTMENT) . ' Please contact HR.'
                : 'Appointment cannot be declined at this stage.';

            return $this->offerActionResponse($request, 'error', $message);
        }

        $employee->emp_appointment_letter_status = 'reject';
        $employee->emp_appointment_reject_reason = $request->reason;
        $employee->save();
        $employee->setOnboardingStep('appointment_rejected');

        OnboardingMail::candidateActivity(
            $employee,
            'Appointment declined',
            'Reason: ' . $request->reason,
            'letter'
        );

        return $this->offerActionResponse($request, 'success', 'Appointment declined. HR has been notified.');
    }

    protected function employeeCanRespondToAppointment(EmployeesNewJoiner $employee): bool
    {
        return \App\Support\OnboardingLetterDeadline::canCandidateActOnAppointment($employee);
    }

    protected function offerActionResponse(Request $request, string $type, string $message)
    {
        $returnUrl = $this->safeOfferReturnUrl($request->input('return_url'));

        if ($returnUrl) {
            return redirect($returnUrl)->with($type, $message);
        }

        return back()->with($type, $message);
    }

    protected function safeOfferReturnUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        $parsed = parse_url($url);
        $appHost = parse_url(url('/'), PHP_URL_HOST);

        if (!empty($parsed['host']) && $parsed['host'] !== $appHost) {
            return null;
        }

        if (!str_starts_with($url, url('/')) && !str_starts_with($url, '/')) {
            return null;
        }

        return $url;
    }

    /** Cancel / block HR-granted document re-upload permission. */
    public function revokeDocumentReedit($id)
    {
        $employee = EmployeesNewJoiner::findOrFail($id);

        if (!OnboardingDocumentReedit::hrCanRevoke($employee)) {
            return back()->with('error', 'There is no active document re-upload permission to cancel.');
        }

        if (!OnboardingDocumentReedit::revoke($employee)) {
            return back()->with('error', 'Could not block document re-upload. Please try again or contact support.');
        }

        return redirect()
            ->route('EmployeeJoiner.Show', $employee->id)
            ->withFragment('candidateDocumentReeditPanel')
            ->with('success', 'Document re-upload blocked. The candidate can no longer upload or replace documents on the portal.');
    }

    /** HR onboarding step actions (one form, step field) */
    public function onboardingStep(Request $request, $id)
    {
        $employee = EmployeesNewJoiner::findOrFail($id);
        $step = $request->input('step');

        if ($step === 'allow_profile_reedit') {
            if (!OnboardingProfileReedit::hrCanGrant($employee)) {
                return back()->with('error', 'Cannot allow profile re-update for this candidate.');
            }

            $request->validate([
                'profile_reedit_reason' => 'required|string|in:' . implode(',', array_keys(OnboardingProfileReedit::reasonOptions())),
                'profile_reedit_detail' => 'nullable|string|max:500',
            ], [
                'profile_reedit_reason.required' => 'Please select a reason before allowing re-update.',
                'profile_reedit_reason.in' => 'Please select a valid reason.',
            ]);

            try {
                OnboardingProfileReedit::grant(
                    $employee,
                    $request->input('profile_reedit_reason'),
                    $request->input('profile_reedit_detail')
                );
            } catch (\InvalidArgumentException $e) {
                return back()->with('error', $e->getMessage())->withInput();
            }

            return back()->with('success', 'Candidate can update their information. Portal email sent with your reason.');
        }

        if ($step === 'revoke_profile_reedit') {
            if (!OnboardingProfileReedit::hrCanRevoke($employee)) {
                return back()->with('error', 'There is no active profile re-update permission to cancel.');
            }
            OnboardingProfileReedit::revoke($employee);

            return back()->with('success', 'Profile re-update permission cancelled.');
        }

        if ($step === 'allow_document_reedit') {
            if (!OnboardingDocumentReedit::hrCanGrant($employee)) {
                return back()->with('error', 'Cannot allow document re-upload for this candidate at this stage.');
            }

            $reasonKey = (string) $request->input('document_reedit_reason');
            $presetKeys = OnboardingDocumentReedit::reasonDocumentMap()[$reasonKey] ?? [];
            $rules = [
                'document_reedit_reason' => 'required|string|in:' . implode(',', array_keys(OnboardingDocumentReedit::reasonOptions())),
                'document_reedit_detail' => 'nullable|string|max:500',
                'document_reedit_keys' => 'nullable|array',
                'document_reedit_keys.*' => 'string|in:' . implode(',', array_keys(OnboardingDocumentReedit::documentOptions($employee))),
            ];
            if ($presetKeys === []) {
                $rules['document_reedit_keys'] = 'required|array|min:1';
            }
            $request->validate($rules, [
                'document_reedit_reason.required' => 'Please select a reason before allowing re-upload.',
                'document_reedit_keys.min' => 'Select at least one document type.',
            ]);

            try {
                OnboardingDocumentReedit::grant(
                    $employee,
                    $request->input('document_reedit_reason'),
                    $request->input('document_reedit_keys', []),
                    $request->input('document_reedit_detail')
                );
            } catch (\InvalidArgumentException $e) {
                return back()->with('error', $e->getMessage())->withInput();
            }

            return back()->with('success', 'Candidate can re-upload documents. Portal email sent with your reason.');
        }

        if ($step === 'revoke_document_reedit') {
            return $this->revokeDocumentReedit($employee);
        }


        if ($step === 'allow_early_join') {
            if (!OnboardingEarlyJoin::hrCanGrant($employee)) {
                return back()->with('error', 'Cannot allow early join for this candidate at this stage.');
            }

            $request->validate([
                'early_join_reason' => 'required|string|in:' . implode(',', array_keys(OnboardingEarlyJoin::reasonOptions())),
                'early_join_detail' => 'nullable|string|max:500',
            ], [
                'early_join_reason.required' => 'Please select a reason before allowing early join.',
            ]);

            try {
                OnboardingEarlyJoin::grant(
                    $employee,
                    $request->input('early_join_reason'),
                    $request->input('early_join_detail')
                );
            } catch (\InvalidArgumentException $e) {
                return back()->with('error', $e->getMessage())->withInput();
            }

            return back()->with('success', 'Early join allowed. You can now start the join process before BGV completes.');
        }

        if ($step === 'revoke_early_join') {
            if (!OnboardingEarlyJoin::hrCanRevoke($employee)) {
                return back()->with('error', 'There is no active early join permission to cancel.');
            }
            OnboardingEarlyJoin::revoke($employee);

            return back()->with('success', 'Early join permission cancelled.');
        }

        if ($step === 'upload_hr_combined_pdf') {
            if (!OnboardingHrCombinedDocument::hrCanUpload($employee)) {
                return back()->with('error', 'Cannot upload combined document for this candidate at this stage.');
            }

            $request->validate([
                'hr_combined_file' => 'required|file|mimes:pdf|max:20480',
                'hr_combined_note' => 'nullable|string|max:500',
            ], [
                'hr_combined_file.required' => 'Please select a PDF file to upload.',
                'hr_combined_file.mimes' => 'Combined document must be a PDF file.',
            ]);

            OnboardingHrCombinedDocument::store(
                $employee,
                $request->file('hr_combined_file'),
                $request->input('hr_combined_note')
            );

            return back()->with('success', 'Combined legacy / missing documents PDF uploaded.');
        }

        if (OnboardingStepGate::isHrMutationsBlocked($employee)) {
            return back()->with('error', 'This onboarding stage is complete. Use Finalize & Save to Folder to finish, or contact admin if a correction is required.');
        }

        switch ($step) {
            case 'submit_offer_sr_approval':
                $isOfferResubmit = in_array($employee->onboardingStep(), ['offer_sr_rejected', 'offer_rejected'], true);

                if (!$isOfferResubmit && !$employee->canProceedToOfferLetter()) {
                    return back()->with('error', $employee->offerLetterBlockedReason() ?? 'Cannot prepare offer letter yet.');
                }

                if (!$isOfferResubmit && ($employee->emp_offer_sent_at || in_array($employee->onboardingStep(), [
                    'offer_pending_sr_hr', 'offer_sent',
                ], true))) {
                    return back()->with('error', 'Offer letter cannot be changed after it has been sent. Use email reminders if needed.');
                }

                if ($employee->emp_offer_letter_status === 'accept') {
                    return back()->with('error', 'Offer already accepted by the candidate.');
                }

                $request->validate([
                    'offer_candidate_name' => 'required|string|max:255',
                    'offer_role' => 'required|string|max:255',
                    'offer_designation' => 'required|string|max:255',
                    'offer_grade' => 'nullable|string|max:100',
                    'offer_ctc' => 'required|numeric|min:1',
                    'offer_retention_bonus' => 'nullable|numeric|min:0',
                    'offer_variable_component' => 'nullable|numeric|min:0',
                    'offer_location' => 'required|string|max:255',
                    'offer_joining_date' => 'nullable|date',
                    'sr_hr_email' => 'required|email',
                    'sr_hr_confirm' => 'accepted',
                ], [
                    'sr_hr_confirm.accepted' => 'Please confirm SR-HR approval is required before the candidate receives the offer.',
                    'sr_hr_email.required' => 'Select an SR-HR approver.',
                ]);
                if ($compError = OfferLetterCompensation::validateOfferAmounts(
                    (float) $request->offer_ctc,
                    $request->offer_retention_bonus,
                    $request->offer_variable_component
                )) {
                    return back()->with('error', $compError)->withInput();
                }
                $other = $employee->emp_other ?? [];
                $other['offer_letter'] = [
                    'candidate_name' => $request->offer_candidate_name,
                    'role' => $request->offer_role,
                    'designation' => $request->offer_designation,
                    'grade' => $request->offer_grade ?: $employee->emp_grade,
                    'category' => $employee->emp_category,
                    'ctc' => $request->offer_ctc,
                    'retention_bonus' => \App\Support\OnboardingLetterData::positiveAmount($request->offer_retention_bonus),
                    'variable_component' => \App\Support\OnboardingLetterData::positiveAmount($request->offer_variable_component),
                    'location' => $request->offer_location,
                    'joining_date' => $request->offer_joining_date,
                    'offer_date' => now()->toDateString(),
                ];
                $employee->emp_other = $other;
                $employee->emp_offer_reject_reason = null;
                OnboardingLetterResend::prepareOfferRevision($employee);
                $employee->save();

                $notifyEmail = $this->resolveSrHrEmailFromRequest($request);
                if (!$notifyEmail) {
                    return back()->with('error', 'Select a valid SR-HR approver from the list.')->withInput();
                }

                SrHrLetterApproval::requestApproval($employee, SrHrLetterApproval::TYPE_OFFER, [$notifyEmail]);

                return back()->with('success', 'Offer sent to SR-HR for approval. Candidate will be emailed after SR-HR approves.');
            case 'reassign_offer_sr_approval':
                $request->validate(['sr_hr_email' => 'required|email']);
                $notifyEmail = $this->resolveSrHrEmailFromRequest($request);
                if (!$notifyEmail) {
                    return back()->with('error', 'Select a valid SR-HR approver from the list.')->withInput();
                }
                try {
                    SrHrLetterApproval::reassignPendingApprover($employee, SrHrLetterApproval::TYPE_OFFER, $notifyEmail);
                } catch (\Throwable $e) {
                    return back()->with('error', $e->getMessage())->withInput();
                }

                return back()->with('success', 'Offer approval sent to ' . \App\Data\SrHrTeam::displayNameForEmail($notifyEmail) . '.');
            case 'resend_offer_sr_approval':
                try {
                    SrHrLetterApproval::resendApprovalRequest($employee, SrHrLetterApproval::TYPE_OFFER);
                } catch (\Throwable $e) {
                    return back()->with('error', $e->getMessage());
                }

                return back()->with('success', 'SR-HR approval email resent.');
            case 'resend_offer_to_candidate':
                try {
                    OnboardingLetterResend::resendOfferToCandidate($employee);
                } catch (\Throwable $e) {
                    return back()->with('error', $e->getMessage());
                }

                return back()->with('success', 'Offer letter email resent to candidate.');
            case 'resend_appointment_sr_approval':
                try {
                    SrHrLetterApproval::resendApprovalRequest($employee, SrHrLetterApproval::TYPE_APPOINTMENT);
                } catch (\Throwable $e) {
                    return back()->with('error', $e->getMessage());
                }

                return back()->with('success', 'SR-HR appointment approval email resent.');
            case 'resend_appointment_to_candidate':
                try {
                    OnboardingLetterResend::resendAppointmentToCandidate($employee);
                } catch (\Throwable $e) {
                    return back()->with('error', $e->getMessage());
                }

                return back()->with('success', 'Appointment letter notification resent to candidate.');
            case 'resend_registration_request':
                try {
                    OnboardingLetterResend::resendRegistrationRequest($employee);
                } catch (\Throwable $e) {
                    return back()->with('error', $e->getMessage());
                }

                return back()->with('success', 'Resignation acceptance letter request resent to candidate.');
            case 'resend_portal_upload_link':
                try {
                    OnboardingLetterResend::resendPortalLink($employee);
                } catch (\Throwable $e) {
                    return back()->with('error', $e->getMessage());
                }

                return back()->with('success', 'Portal link emailed to candidate.');
            case 'update_document_deadline':
                $request->validate([
                    'emp_document_due_date' => 'required|date|after_or_equal:today',
                ], [
                    'emp_document_due_date.after_or_equal' => 'Document deadline must be today or a future date.',
                ]);

                $employee->emp_document_due_date = $request->emp_document_due_date;
                $employee->save();

                return back()->with('success', 'Document submission deadline updated. The candidate can access the portal again.');
            case 'update_letter_deadlines':
                $request->validate([
                    'emp_offer_due_date' => 'nullable|date|after_or_equal:today',
                    'emp_registration_due_date' => 'nullable|date|after_or_equal:today',
                    'emp_appointment_due_date' => 'nullable|date|after_or_equal:today',
                ], [
                    'emp_offer_due_date.after_or_equal' => 'Offer deadline must be today or a future date.',
                    'emp_registration_due_date.after_or_equal' => 'Resignation letter deadline must be today or a future date.',
                    'emp_appointment_due_date.after_or_equal' => 'Appointment deadline must be today or a future date.',
                ]);

                $updated = false;
                if ($request->filled('emp_offer_due_date') && $employee->onboardingStep() === 'offer_sent') {
                    $employee->emp_offer_due_date = $request->emp_offer_due_date;
                    $updated = true;
                }
                if ($request->filled('emp_registration_due_date') && $employee->onboardingStep() === 'registration_sent') {
                    $employee->emp_registration_due_date = $request->emp_registration_due_date;
                    $updated = true;
                }
                if ($request->filled('emp_appointment_due_date') && in_array($employee->onboardingStep(), ['appointment_sent', 'appointment_rejected'], true)) {
                    $employee->emp_appointment_due_date = $request->emp_appointment_due_date;
                    $updated = true;
                }

                if (!$updated) {
                    return back()->with('error', 'No deadline was updated. Open the correct onboarding stage and choose a new date.');
                }

                $employee->save();

                return back()->with('success', 'Letter deadline updated. The candidate can continue once the new date is valid.');
            case 'save_offer_draft':
                $isOfferResubmit = in_array($employee->onboardingStep(), ['offer_sr_rejected', 'offer_rejected'], true);
                if (!$isOfferResubmit && !$employee->canProceedToOfferLetter()) {
                    return back()->with('error', $employee->offerLetterBlockedReason() ?? 'Cannot prepare offer letter yet.');
                }
                if (!$isOfferResubmit && ($employee->emp_offer_sent_at || in_array($employee->onboardingStep(), [
                    'offer_pending_sr_hr', 'offer_sent',
                ], true))) {
                    return back()->with('error', 'Offer letter cannot be changed after it has been sent.');
                }
                if ($employee->emp_offer_letter_status === 'accept') {
                    return back()->with('error', 'Offer already accepted by the candidate.');
                }
                $request->validate([
                    'offer_candidate_name' => 'required|string|max:255',
                    'offer_role' => 'required|string|max:255',
                    'offer_designation' => 'required|string|max:255',
                    'offer_grade' => 'nullable|string|max:100',
                    'offer_ctc' => 'required|numeric|min:1',
                    'offer_retention_bonus' => 'nullable|numeric|min:0',
                    'offer_variable_component' => 'nullable|numeric|min:0',
                    'offer_location' => 'required|string|max:255',
                    'offer_joining_date' => 'nullable|date',
                ]);
                if ($compError = OfferLetterCompensation::validateOfferAmounts(
                    (float) $request->offer_ctc,
                    $request->offer_retention_bonus,
                    $request->offer_variable_component
                )) {
                    return back()->with('error', $compError)->withInput();
                }
                $other = $employee->emp_other ?? [];
                $other['offer_letter'] = [
                    'candidate_name' => $request->offer_candidate_name,
                    'role' => $request->offer_role,
                    'designation' => $request->offer_designation,
                    'grade' => $request->offer_grade ?: $employee->emp_grade,
                    'category' => $employee->emp_category,
                    'ctc' => $request->offer_ctc,
                    'retention_bonus' => \App\Support\OnboardingLetterData::positiveAmount($request->offer_retention_bonus),
                    'variable_component' => \App\Support\OnboardingLetterData::positiveAmount($request->offer_variable_component),
                    'location' => $request->offer_location,
                    'joining_date' => $request->offer_joining_date,
                    'offer_date' => now()->toDateString(),
                ];
                $employee->emp_other = $other;
                $employee->save();

                return back()->with('success', 'Offer details saved. Use Preview Offer PDF to review before sending.');
            case 'send_registration':
                $employee->emp_registration_sent_at = now();
                \App\Support\OnboardingLetterDeadline::assignRegistrationDueDate($employee);
                $employee->save();
                $employee->setOnboardingStep('registration_sent');
                OnboardingMail::hrStepAdvanced($employee, 'send_registration');

                return back()->with('success', 'Candidate can now upload resignation acceptance letter. Email sent to candidate.');
            case 'verify_registration':
                $regDoc = $employee->resignationLetterDocument();
                if (!$regDoc) {
                    return back()->with('error', 'No resignation acceptance letter in documents. Ask candidate to upload on Letter tab.');
                }
                $regDoc->emp_document_status = 'approved';
                $regDoc->rejection_reason = null;
                $regDoc->save();
                $employee->setOnboardingStep('registration_verified');
                OnboardingMail::hrStepAdvanced($employee, 'verify_registration');

                return back()->with('success', 'Resignation letter approved in documents. Candidate notified by email.');
            case 'bgv_started':
                $employee->setOnboardingStep('bgv_started');
                break;
            case 'bgv_completed':
                $employee->setOnboardingStep('bgv_completed');
                break;
            case 'start_join':
                if (!OnboardingEarlyJoin::hrCanStartJoin($employee)) {
                    return back()->with('error', 'Cannot start join process at this stage. Complete BGV or allow early join first.');
                }
                $request->validate(['emp_joining_date' => 'required|date']);
                if (!OnboardingEarlyJoin::isJoinDateAllowedForStartProcess($request->emp_joining_date)) {
                    return back()
                        ->with('error', OnboardingEarlyJoin::joinDateWindowMessage())
                        ->withInput();
                }
                $employee->emp_joining_date = $request->emp_joining_date;
                $employee->setOnboardingStep('join_forms_sent');
                OnboardingMail::hrStepAdvanced($employee, 'join_forms_sent');
                break;
            case 'send_appointment_to_candidate':
            case 'save_appointment_draft':
                $isApptResubmit = $employee->onboardingStep() === 'appointment_rejected';
                if (!$isApptResubmit && $employee->onboardingStep() !== 'policy_signed') {
                    return back()->with('error', 'Appointment letter can only be prepared when the candidate has signed policy and before it is sent.');
                }
                if ($isApptResubmit && $employee->emp_appointment_letter_status !== 'reject') {
                    return back()->with('error', 'Appointment can only be re-sent after the candidate declines.');
                }
                if (!$isApptResubmit) {
                    $joinToday = $employee->joinDateIsToday();
                    if (!$joinToday && !$request->boolean('confirm_joined_today')) {
                        return back()
                            ->with('error', 'Scheduled join date is not today (' . optional($employee->scheduledJoinDate())->format('d-m-Y') . '). Check "Confirm joined today" to proceed manually.')
                            ->withInput();
                    }
                }
                $request->validate([
                    'appt_candidate_name' => 'required|string|max:255',
                    'appt_role' => 'required|string|max:255',
                    'appt_designation' => 'required|string|max:255',
                    'appt_location' => 'required|string|max:255',
                    'appt_joining_date' => 'required|date',
                    'appt_ctc_annual' => 'required|numeric|min:1',
                    'appt_ctc_basic' => 'nullable|numeric|min:0',
                    'appt_ctc_hra' => 'nullable|numeric|min:0',
                    'appt_ctc_special' => 'nullable|numeric|min:0',
                    'appt_ctc_pf' => 'nullable|numeric|min:0',
                ]);
                $other = $employee->emp_other ?? [];
                $other['appointment_letter'] = [
                    'candidate_name' => $request->appt_candidate_name,
                    'role' => $request->appt_role,
                    'designation' => $request->appt_designation,
                    'location' => $request->appt_location,
                    'joining_date' => $request->appt_joining_date,
                    'ctc_annual' => $request->appt_ctc_annual,
                    'ctc_breakdown' => [
                        'basic' => $request->appt_ctc_basic,
                        'hra' => $request->appt_ctc_hra,
                        'special' => $request->appt_ctc_special,
                        'pf' => $request->appt_ctc_pf,
                    ],
                    'letter_date' => now()->toDateString(),
                    'manual_join_confirm' => $request->boolean('confirm_joined_today'),
                ];
                $employee->emp_other = $other;
                $employee->save();
                if ($step === 'save_appointment_draft') {
                    return back()->with('success', 'Appointment letter saved. Preview PDF, then send to the candidate.');
                }

                if ($employee->emp_appointment_letter_status === 'accept') {
                    return back()->with('error', 'Candidate already accepted. Submit to SR-HR for approval instead.');
                }

                unset($other['appointment_signature']);
                $employee->emp_other = $other;
                if (!$employee->emp_appointment_sent_at) {
                    $employee->emp_appointment_sent_at = now();
                }
                \App\Support\OnboardingLetterResend::prepareAppointmentAfterCandidateReject($employee);
                OnboardingMail::hrStepAdvanced($employee, 'appointment_sent');

                return back()->with('success', $isApptResubmit
                    ? 'Appointment letter revised and re-sent to the candidate after decline.'
                    : 'Appointment letter sent to candidate. After they sign, submit to SR-HR for approval.');
            case 'submit_appointment_sr_approval':
                if (!in_array($employee->onboardingStep(), ['appointment_accepted', 'appointment_sr_rejected'], true)) {
                    return back()->with('error', 'Candidate must accept the appointment letter before SR-HR approval.');
                }
                if ($employee->emp_appointment_letter_status !== 'accept') {
                    return back()->with('error', 'Candidate has not accepted the appointment letter yet.');
                }
                $request->validate([
                    'sr_hr_email' => 'required|email',
                    'sr_hr_confirm' => 'accepted',
                ], [
                    'sr_hr_confirm.accepted' => 'Please confirm SR-HR approval is required after candidate acceptance.',
                    'sr_hr_email.required' => 'Select an SR-HR approver.',
                ]);
                $notifyEmail = $this->resolveSrHrEmailFromRequest($request);
                if (!$notifyEmail) {
                    return back()->with('error', 'Select a valid SR-HR approver from the list.')->withInput();
                }

                SrHrLetterApproval::requestApproval($employee, SrHrLetterApproval::TYPE_APPOINTMENT, [$notifyEmail]);

                return back()->with('success', 'Submitted to SR-HR. Final PDF will be saved after SR-HR approves.');
            case 'reassign_appointment_sr_approval':
                $request->validate(['sr_hr_email' => 'required|email']);
                $notifyEmail = $this->resolveSrHrEmailFromRequest($request);
                if (!$notifyEmail) {
                    return back()->with('error', 'Select a valid SR-HR approver from the list.')->withInput();
                }
                try {
                    SrHrLetterApproval::reassignPendingApprover($employee, SrHrLetterApproval::TYPE_APPOINTMENT, $notifyEmail);
                } catch (\Throwable $e) {
                    return back()->with('error', $e->getMessage())->withInput();
                }

                return back()->with('success', 'Appointment approval sent to ' . \App\Data\SrHrTeam::displayNameForEmail($notifyEmail) . '.');
            default:
                return back()->with('error', 'Unknown step.');
        }

        return back()->with('success', 'Updated successfully.');
    }

    public function finalizeOnboarding(Request $request, $id)
    {
        $request->validate([
            'manual_emp_id' => 'required|string|max:50',
        ]);

        $employee = EmployeesNewJoiner::findOrFail($id);

        if (!OnboardingArchive::canFinalize($employee)) {
            return back()->with('error', 'Onboarding cannot be finalized yet. Candidate must accept the appointment and SR-HR must approve it.');
        }

        try {
            OnboardingArchive::finalize($employee, $request->manual_emp_id);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with([
            'success' => 'All documents saved to folder EMP_' . trim($request->manual_emp_id) . '. Onboarding complete.',
            'bg-color' => 'success',
        ]);
    }

    protected function resolveSrHrEmailFromRequest(Request $request): ?string
    {
        $email = strtolower(trim((string) $request->input('sr_hr_email', '')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return SrHrLetterApproval::isConfiguredApproverEmail($email) ? $email : null;
    }

    protected function assertValidLevelGradingSelection(Request $request): void
    {
        $entry = LevelWiseGrading::findByValue($request->input('emp_level_grading'));
        if (!$entry) {
            throw ValidationException::withMessages([
                'emp_level_grading' => 'Please select a valid level wise grading.',
            ]);
        }

        if (!LevelWiseGrading::isValidDesignation($entry['grade'], $entry['category'], $request->input('emp_role'))) {
            throw ValidationException::withMessages([
                'emp_role' => 'Please select a designation that belongs to the chosen grading level.',
            ]);
        }
    }

    protected function assertHrLetterPreviewAccess(): void
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['hr', 'admin', 'superadmin'], true)) {
            abort(403, 'HR/Admin access required.');
        }
    }

    protected function assertLetterPreviewAccess(Request $request, EmployeesNewJoiner $employee, string $letterType): void
    {
        if ($request->filled('sr_hr_token')) {
            $token = trim((string) $request->input('sr_hr_token'));
            $found = SrHrLetterApproval::findEmployeeByToken($token);
            if ($found && (int) $found->id === (int) $employee->id
                && SrHrLetterApproval::typeForToken($employee, $token) === $letterType) {
                return;
            }

            abort(403, 'Invalid or expired approval link.');
        }

        $this->assertHrLetterPreviewAccess();
    }
}
