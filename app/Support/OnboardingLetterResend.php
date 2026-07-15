<?php

namespace App\Support;

use App\Models\EmployeesNewJoiner;
use Illuminate\Support\Facades\Log;

class OnboardingLetterResend
{
    protected static function blocked(EmployeesNewJoiner $employee): bool
    {
        return OnboardingStepGate::isHrMutationsBlocked($employee);
    }

    /** First-time offer preparation, or resubmit after SR-HR / candidate rejection. */
    public static function canReviseOfferForSrHr(EmployeesNewJoiner $employee): bool
    {
        if (self::blocked($employee) || OnboardingStepGate::isOfferStageLocked($employee)) {
            return false;
        }

        if ($employee->emp_offer_letter_status === 'accept') {
            return false;
        }

        if ($employee->onboardingStep() === 'offer_sr_rejected') {
            return true;
        }

        if ($employee->onboardingStep() === 'offer_rejected') {
            return true;
        }

        if ($employee->emp_offer_sent_at || in_array($employee->onboardingStep(), [
            'offer_pending_sr_hr',
            'offer_sent',
        ], true)) {
            return false;
        }

        return $employee->canProceedToOfferLetter();
    }

    public static function canResendOfferSrApproval(EmployeesNewJoiner $employee): bool
    {
        if (self::blocked($employee) || OnboardingStepGate::isOfferStageLocked($employee)) {
            return false;
        }

        return SrHrLetterApproval::isPending($employee, SrHrLetterApproval::TYPE_OFFER);
    }

    public static function canResendOfferToCandidate(EmployeesNewJoiner $employee): bool
    {
        if (self::blocked($employee) || OnboardingStepGate::isOfferStageLocked($employee)) {
            return false;
        }

        return in_array($employee->onboardingStep(), ['offer_sent', 'offer_rejected'], true)
            && SrHrLetterApproval::isApproved($employee, SrHrLetterApproval::TYPE_OFFER)
            && $employee->emp_offer_letter_status !== 'accept';
    }

    /** Appointment letter form — first send (policy signed) or re-send after candidate decline. */
    public static function canReviseAppointmentForCandidate(EmployeesNewJoiner $employee): bool
    {
        if (self::blocked($employee)) {
            return false;
        }

        if ($employee->onboardingStep() === 'policy_signed' && !$employee->emp_appointment_sent_at) {
            return true;
        }

        return $employee->onboardingStep() === 'appointment_rejected'
            && $employee->emp_appointment_letter_status === 'reject';
    }

    public static function canResendAppointmentToCandidate(EmployeesNewJoiner $employee): bool
    {
        if (self::blocked($employee)) {
            return false;
        }

        return in_array($employee->onboardingStep(), ['appointment_sent', 'appointment_rejected'], true)
            && $employee->emp_appointment_letter_status !== 'accept';
    }

    public static function prepareAppointmentAfterCandidateReject(EmployeesNewJoiner $employee): void
    {
        $other = $employee->emp_other ?? [];
        unset($other['appointment_signature']);
        $employee->emp_other = $other;
        $employee->emp_appointment_letter_status = 'pending';
        $employee->emp_appointment_reject_reason = null;
        OnboardingLetterDeadline::assignAppointmentDueDate($employee);
        $employee->save();
        $employee->setOnboardingStep('appointment_sent');
    }

    public static function canResendAppointmentSrApproval(EmployeesNewJoiner $employee): bool
    {
        if (self::blocked($employee)) {
            return false;
        }

        return SrHrLetterApproval::isPending($employee, SrHrLetterApproval::TYPE_APPOINTMENT);
    }

    public static function canResendRegistrationRequest(EmployeesNewJoiner $employee): bool
    {
        if (self::blocked($employee) || OnboardingStepGate::isRegistrationVerified($employee)) {
            return false;
        }

        return in_array($employee->onboardingStep(), [
            'registration_sent',
            'registration_submitted',
        ], true);
    }

    /** @deprecated Use OnboardingStepGate::canResendPortalLink() */
    public static function canAllowDocumentReupload(EmployeesNewJoiner $employee): bool
    {
        return OnboardingStepGate::canResendPortalLink($employee);
    }

    public static function canResendPortalLink(EmployeesNewJoiner $employee): bool
    {
        return OnboardingStepGate::canResendPortalLink($employee);
    }

    public static function prepareOfferRevision(EmployeesNewJoiner $employee): void
    {
        $employee->emp_signature = null;
        $employee->emp_offer_letter_status = 'pending';
        $employee->emp_offer_reject_reason = null;
        $employee->save();
    }

    public static function resendOfferToCandidate(EmployeesNewJoiner $employee): void
    {
        if (!self::canResendOfferToCandidate($employee)) {
            throw new \RuntimeException('Offer cannot be resent to the candidate at this stage.');
        }

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
                ->subject('Offer Letter (Reminder) – ' . config('app.name', 'Ami Polymer'));
        });

        OnboardingLetterDeadline::assignOfferDueDate($employee);
        $employee->save();

        try {
            OnboardingMail::candidateActivity($employee, 'Offer letter resent', 'HR resent the offer letter email.');
        } catch (\Throwable $e) {
            Log::warning('Offer resent but activity log failed: ' . $e->getMessage());
        }
    }

    public static function resendAppointmentToCandidate(EmployeesNewJoiner $employee): void
    {
        if (!self::canResendAppointmentToCandidate($employee)) {
            throw new \RuntimeException('Appointment letter cannot be resent at this stage.');
        }

        if ($employee->onboardingStep() === 'appointment_rejected') {
            self::prepareAppointmentAfterCandidateReject($employee);
        } else {
            OnboardingLetterDeadline::assignAppointmentDueDate($employee);
            $employee->save();
        }

        OnboardingMail::hrStepAdvanced($employee, 'appointment_sent');
        OnboardingMail::candidateActivity($employee, 'Appointment letter resent', 'HR resent the appointment letter notification.');
    }

    public static function resendRegistrationRequest(EmployeesNewJoiner $employee): void
    {
        if (!self::canResendRegistrationRequest($employee)) {
            throw new \RuntimeException('Registration request cannot be resent at this stage.');
        }

        OnboardingLetterDeadline::assignRegistrationDueDate($employee);
        $employee->save();

        OnboardingMail::hrStepAdvanced($employee, 'send_registration');
    }

    public static function resendPortalLink(EmployeesNewJoiner $employee): void
    {
        if (!self::canResendPortalLink($employee)) {
            throw new \RuntimeException('Portal link cannot be resent after the candidate has submitted their profile.');
        }

        $uploadLink = route('onboarding.portal', $employee->emp_url);
        $documentNamesList = \App\Models\DocumentNamesList::all();

        OnboardingMail::deliver('emails.new_employee_upload', [
            'employee' => $employee,
            'uploadLink' => $uploadLink,
            'documentNamesList' => $documentNamesList,
            'dueDate' => \Carbon\Carbon::parse($employee->emp_document_due_date)->format('d M Y'),
        ], function ($message) use ($employee) {
            $message->to($employee->emp_email)
                ->subject('Your Onboarding Portal Link – ' . config('app.name', 'Ami Polymer'));
        });
    }
}
