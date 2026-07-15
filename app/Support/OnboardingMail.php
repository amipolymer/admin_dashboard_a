<?php

namespace App\Support;

use App\Models\EmployeesNewJoiner;
use App\Models\NewEmployeesDocument;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OnboardingMail
{
    /** @return list<string> */
    public static function ccAddresses(): array
    {
        $configured = config('onboarding.mail_cc', []);
        if (!is_array($configured)) {
            $configured = explode(',', (string) $configured);
        }

        return array_values(array_filter(array_map(
            static fn ($email) => trim((string) $email),
            $configured
        ), static fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL)));
    }

    public static function applyCc($message): void
    {
        $cc = self::ccAddresses();
        if ($cc !== []) {
            $message->cc($cc);
        }
    }

    /** Send any onboarding mailable; applies global CC when configured. */
    public static function deliver(string $view, array $data, callable $configure): void
    {
        try {
            Mail::send($view, $data, function ($message) use ($configure) {
                $configure($message);
                self::applyCc($message);
            });
        } catch (\Throwable $e) {
            Log::warning('Onboarding email failed: ' . $e->getMessage(), [
                'view' => $view,
            ]);
        }
    }

    public static function hrUser(EmployeesNewJoiner $employee): ?User
    {
        if (!$employee->emp_hr_id) {
            return null;
        }

        return User::find($employee->emp_hr_id);
    }

    public static function hrShowUrl(EmployeesNewJoiner $employee): string
    {
        return route('EmployeeJoiner.Show', $employee->id);
    }

    public static function portalUrl(EmployeesNewJoiner $employee, ?string $tab = null): string
    {
        $url = route('onboarding.portal', $employee->emp_url);

        return $tab ? $url . '?tab=' . $tab : $url;
    }

    /** Candidate accepted offer — thank you + HR confirmation. */
    public static function offerAccepted(EmployeesNewJoiner $employee): void
    {
        $offer = ($employee->emp_other ?? [])['offer_letter'] ?? [];
        $portalLink = self::portalUrl($employee, 'letter');

        self::sendCandidate(
            $employee,
            'Thank you — Offer accepted | ' . config('app.name', 'Ami Polymer'),
            'emails.onboarding.offer_accepted_candidate',
            compact('employee', 'offer', 'portalLink')
        );

        self::notifyHr(
            $employee,
            'Offer accepted: ' . $employee->emp_name,
            'Candidate has signed and accepted the offer letter. Please proceed with the next onboarding step.',
            'Offer accepted',
            ['portal_tab' => 'letter']
        );
    }

    public static function offerRejected(EmployeesNewJoiner $employee, string $reason): void
    {
        self::notifyHr(
            $employee,
            'Offer declined: ' . $employee->emp_name,
            'Reason: ' . $reason,
            'Offer declined'
        );
    }

    /** HR advanced onboarding — notify candidate what to do next. */
    public static function hrStepAdvanced(EmployeesNewJoiner $employee, string $step): void
    {
        $messages = self::candidateStepMessages($step);
        if (!$messages) {
            return;
        }

        self::sendCandidate(
            $employee,
            $messages['subject'],
            'emails.onboarding.candidate_step_update',
            [
                'employee' => $employee,
                'headline' => $messages['headline'],
                'body' => $messages['body'],
                'portalLink' => self::portalUrl($employee, $messages['tab'] ?? null),
                'actionLabel' => $messages['action'] ?? 'Open onboarding portal',
            ]
        );
    }

    /** Candidate completed an action — notify assigned HR. */
    public static function candidateActivity(EmployeesNewJoiner $employee, string $title, string $detail, ?string $tab = null): void
    {
        self::notifyHr($employee, $title . ' — ' . $employee->emp_name, $detail, $title, ['portal_tab' => $tab]);
    }

    public static function onboardingComplete(EmployeesNewJoiner $employee): void
    {
        self::sendCandidate(
            $employee,
            'Onboarding complete — Welcome | ' . config('app.name', 'Ami Polymer'),
            'emails.onboarding.onboarding_complete_candidate',
            ['employee' => $employee, 'portalLink' => self::portalUrl($employee)]
        );

        self::notifyHr(
            $employee,
            'Onboarding finished: ' . $employee->emp_name,
            'SR-HR approved the signed appointment letter. Onboarding is complete from the candidate side.',
            'Onboarding complete'
        );
    }

    public static function appointmentAcceptedByCandidate(EmployeesNewJoiner $employee): void
    {
        self::sendCandidate(
            $employee,
            'Appointment accepted — Thank you | ' . config('app.name', 'Ami Polymer'),
            'emails.onboarding.appointment_accepted_candidate',
            ['employee' => $employee, 'portalLink' => self::portalUrl($employee, 'letter')]
        );

        self::notifyHr(
            $employee,
            'Appointment accepted: ' . $employee->emp_name,
            'The candidate signed the appointment letter. Please submit it to SR-HR for approval in the HR panel.',
            'Appointment accepted — SR-HR pending',
            ['portal_tab' => 'letter']
        );
    }

    /** SR-HR final approval after candidate signed appointment — notify candidate and HR. */
    public static function appointmentSrHrApproved(EmployeesNewJoiner $employee, ?NewEmployeesDocument $pdfDocument = null): void
    {
        $appointment = ($employee->emp_other ?? [])['appointment_letter'] ?? [];
        $portalLink = self::portalUrl($employee, 'letter');

        $pdfDocument = $pdfDocument ?? OnboardingLetterDocument::latestSrHrApprovedAppointment($employee);
        $pdfPath = $pdfDocument ? OnboardingLetterDocument::absolutePath($pdfDocument) : null;
        $pdfLink = $pdfDocument ? route('appointment.signed.pdf', $employee->id) : null;
        $pdfFilename = 'Appointment_Letter_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $employee->emp_name ?: 'candidate') . '.pdf';

        self::sendCandidate(
            $employee,
            'Appointment letter approved — Welcome | ' . config('app.name', 'Ami Polymer'),
            'emails.onboarding.appointment_sr_hr_approved_candidate',
            compact('employee', 'appointment', 'portalLink', 'pdfLink', 'pdfFilename'),
            $pdfPath,
            $pdfFilename
        );

        self::notifyHr(
            $employee,
            'SR-HR approved appointment: ' . $employee->emp_name,
            'Signed appointment letter PDF is saved. Completion email sent to candidate. You may finalize onboarding and assign Employee ID.',
            'Appointment SR-HR approved',
            ['portal_tab' => 'letter']
        );

        try {
            self::candidateActivity(
                $employee,
                'Appointment final approval',
                'SR-HR approved; onboarding complete email sent to candidate.',
                'letter'
            );
        } catch (\Throwable $e) {
            Log::warning('Appointment SR-HR approved mail ok but activity log failed: ' . $e->getMessage());
        }
    }

    /** HR allowed candidate to upload or replace documents (e.g. BGV / EMPV). */
    public static function documentReeditGranted(EmployeesNewJoiner $employee, string $reason, array $documentKeys): void
    {
        $labels = OnboardingDocumentReedit::documentOptions($employee);
        $docList = array_values(array_filter(array_map(fn ($k) => $labels[$k] ?? null, $documentKeys)));
        $body = 'HR has asked you to upload or replace the following document(s) in the onboarding portal: '
            . implode(', ', $docList) . '.';
        if ($reason !== '') {
            $body .= ' Reason: ' . $reason;
        }

        self::sendCandidate(
            $employee,
            'Action required — Upload documents | ' . config('app.name', 'Ami Polymer'),
            'emails.onboarding.candidate_step_update',
            [
                'employee' => $employee,
                'headline' => 'Please upload the requested documents',
                'body' => $body,
                'portalLink' => self::portalUrl($employee, 'document'),
                'actionLabel' => 'Open documents tab',
            ]
        );
    }

    public static function profileReeditGranted(EmployeesNewJoiner $employee, string $reason): void
    {
        $body = 'Please open the onboarding portal, go to the Info tab, correct your information, and submit again.';
        if ($reason !== '') {
            $body = 'Reason from HR: ' . $reason . '. ' . $body;
        }

        self::sendCandidate(
            $employee,
            'Update your information | ' . config('app.name', 'Ami Polymer'),
            'emails.onboarding.candidate_step_update',
            [
                'employee' => $employee,
                'headline' => 'HR has allowed you to update your details',
                'body' => $body,
                'portalLink' => self::portalUrl($employee, 'info'),
                'actionLabel' => 'Update information',
            ]
        );
    }

    public static function documentsSubmittedThanks(EmployeesNewJoiner $employee): void
    {
        self::sendCandidate(
            $employee,
            'Documents submitted — Thank you | ' . config('app.name', 'Ami Polymer'),
            'emails.onboarding.documents_submitted_candidate',
            ['employee' => $employee, 'portalLink' => self::portalUrl($employee, 'document')]
        );
    }

    /**
     * @return array{subject: string, headline: string, body: string, tab?: string, action?: string}|null
     */
    protected static function candidateStepMessages(string $step): ?array
    {
        return match ($step) {
            'send_registration' => [
                'subject' => 'Next step: Resignation acceptance letter | ' . config('app.name', 'Ami Polymer'),
                'headline' => 'HR has opened the next step',
                'body' => 'Please upload your resignation acceptance letter from your previous employer in the onboarding portal (Letter tab).',
                'tab' => 'letter',
                'action' => 'Upload resignation letter',
            ],
            'verify_registration' => [
                'subject' => 'Resignation letter verified | ' . config('app.name', 'Ami Polymer'),
                'headline' => 'Your document was verified',
                'body' => 'HR has verified your resignation acceptance letter. You will be notified when the next step is available.',
                'tab' => 'document',
            ],
            'start_join',             'join_forms_sent' => [
                'subject' => 'Joining form — Please complete | ' . config('app.name', 'Ami Polymer'),
                'headline' => 'Joining details required',
                'body' => 'Please complete the joining form and company policy acceptance in your onboarding portal.',
                'tab' => 'joining',
                'action' => 'Complete joining form',
            ],
            'send_appointment_to_candidate', 'send_appointment', 'appointment_sent' => [
                'subject' => 'Appointment letter — Please sign | ' . config('app.name', 'Ami Polymer'),
                'headline' => 'Appointment letter ready',
                'body' => 'Your appointment letter is ready. Please review and sign it in the onboarding portal.',
                'tab' => 'letter',
                'action' => 'Sign appointment letter',
            ],
            default => null,
        };
    }

    protected static function notifyHr(
        EmployeesNewJoiner $employee,
        string $subject,
        string $detail,
        string $activityTitle,
        array $extra = []
    ): void {
        $hr = self::hrUser($employee);
        if (!$hr || empty($hr->email)) {
            return;
        }

        self::send(
            $hr->email,
            $subject,
            'emails.onboarding.hr_notification',
            array_merge([
                'employee' => $employee,
                'hr' => $hr,
                'detail' => $detail,
                'activityTitle' => $activityTitle,
                'adminLink' => self::hrShowUrl($employee),
                'step' => str_replace('_', ' ', $employee->onboardingStep()),
            ], $extra)
        );
    }

    protected static function sendCandidate(
        EmployeesNewJoiner $employee,
        string $subject,
        string $view,
        array $data,
        ?string $pdfPath = null,
        ?string $pdfFilename = null
    ): void {
        if (empty($employee->emp_email)) {
            return;
        }

        self::send($employee->emp_email, $subject, $view, $data, $pdfPath, $pdfFilename);
    }

    protected static function send(
        string $to,
        string $subject,
        string $view,
        array $data,
        ?string $pdfPath = null,
        ?string $pdfFilename = null
    ): void {
        try {
            Mail::send($view, $data, function ($message) use ($to, $subject, $pdfPath, $pdfFilename) {
                $message->to($to)->subject($subject);
                if ($pdfPath && is_file($pdfPath)) {
                    $message->attach($pdfPath, [
                        'as' => $pdfFilename ?? 'Appointment_Letter.pdf',
                        'mime' => 'application/pdf',
                    ]);
                }
                self::applyCc($message);
            });
        } catch (\Throwable $e) {
            Log::warning('Onboarding email failed: ' . $e->getMessage(), [
                'to' => $to,
                'subject' => $subject,
                'view' => $view,
            ]);
        }
    }
}
