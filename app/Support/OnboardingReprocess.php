<?php

namespace App\Support;

use App\Models\EmployeesNewJoiner;
use App\Models\NewEmployeesDocument;

class OnboardingReprocess
{
    public static function canReprocessOffer(EmployeesNewJoiner $employee): bool
    {
        if (OnboardingStepGate::isHrMutationsBlocked($employee)
            || OnboardingStepGate::isOfferStageLocked($employee)) {
            return false;
        }

        return in_array($employee->onboardingStep(), [
            'offer_sent',
            'offer_rejected',
            'offer_accepted',
        ], true);
    }

    public static function canReprocessJoin(EmployeesNewJoiner $employee): bool
    {
        if (OnboardingStepGate::isHrMutationsBlocked($employee)) {
            return false;
        }

        return in_array($employee->onboardingStep(), [
            'join_forms_submitted',
            'policy_signed',
            'appointment_sent',
        ], true);
    }

    public static function canReprocessAppointment(EmployeesNewJoiner $employee): bool
    {
        if (OnboardingStepGate::isHrMutationsBlocked($employee)) {
            return false;
        }

        return $employee->onboardingStep() === 'appointment_sent'
            && $employee->emp_appointment_letter_status !== 'accept';
    }

    public static function isAppointmentStage(EmployeesNewJoiner $employee): bool
    {
        return in_array($employee->onboardingStep(), [
            'appointment_sent',
            'appointment_accepted',
            'appointment_rejected',
            'end',
        ], true);
    }

    public static function applyOffer(EmployeesNewJoiner $employee, string $mode): void
    {
        if (!self::canReprocessOffer($employee)) {
            throw new \RuntimeException('Offer re-process is not allowed at this stage.');
        }

        if ($mode === 'fresh') {
            throw new \RuntimeException('Full restart is not available. Use one step back only.');
        }

        $employee->emp_offer_letter_status = 'pending';
        $employee->emp_signature = null;
        $employee->emp_offer_reject_reason = null;
        OnboardingLetterDeadline::assignOfferDueDate($employee);
        $employee->save();
        $employee->setOnboardingStep('offer_sent');
    }

    public static function applyJoin(EmployeesNewJoiner $employee, string $mode): void
    {
        if (!self::canReprocessJoin($employee)) {
            throw new \RuntimeException('Join re-process is not allowed at this stage.');
        }

        if ($mode === 'fresh') {
            throw new \RuntimeException('Full restart is not available. Use one step back only.');
        }

        $step = $employee->onboardingStep();
        if ($step === 'policy_signed') {
            $employee->emp_policy_accepted_at = null;
            $employee->emp_policy_signature = null;
            $employee->save();
            $employee->setOnboardingStep('join_forms_submitted');
        } elseif (in_array($step, ['join_forms_submitted', 'appointment_sent'], true)) {
            self::clearJoinPolicyAppointment($employee, false);
            $employee->setOnboardingStep('join_forms_sent');
        }
    }

    public static function applyAppointment(EmployeesNewJoiner $employee, string $mode): void
    {
        if (!self::canReprocessAppointment($employee)) {
            throw new \RuntimeException('Appointment re-process is not allowed at this stage.');
        }

        if ($mode === 'fresh') {
            throw new \RuntimeException('Full restart is not available. Use one step back only.');
        }

        $other = $employee->emp_other ?? [];
        unset($other['appointment_signature']);
        $employee->emp_other = $other;
        $employee->emp_appointment_letter_status = 'pending';
        $employee->emp_appointment_sent_at = null;
        $employee->emp_appointment_reject_reason = null;
        OnboardingLetterDeadline::assignAppointmentDueDate($employee);
        $employee->save();
        self::removeLetterDocument($employee, OnboardingLetterDocument::APPOINTMENT_SR_APPROVED);
        self::removeLetterDocument($employee, OnboardingLetterDocument::APPOINTMENT);
        $employee->setOnboardingStep('policy_signed');
    }

    /**
     * @deprecated Admin-only; not exposed in HR UI.
     */
    public static function resetCandidateFromBeginning(EmployeesNewJoiner $employee): void
    {
        self::clearAfterOffer($employee);

        $other = $employee->emp_other ?? [];
        unset(
            $other['offer_letter'],
            $other['appointment_letter'],
            $other['appointment_signature']
        );
        $employee->emp_other = $other;

        $employee->emp_profile_data = null;
        $employee->emp_signature = null;
        $employee->emp_policy_signature = null;
        $employee->emp_offer_sent_at = null;
        $employee->emp_offer_due_date = null;
        $employee->emp_offer_letter_status = '0';
        $employee->emp_offer_reject_reason = null;
        $employee->emp_joining_date = null;
        $employee->emp_document_status = 'process';
        $employee->emp_onboarding_status = 'active';
        $employee->emp_archived_at = null;
        $employee->save();

        $hrPreserved = ['cv', 'interview_evaluation', 'mrf_file'];
        NewEmployeesDocument::where('emp_id', $employee->id)
            ->whereNotIn('emp_select_document', $hrPreserved)
            ->delete();

        $employee->setOnboardingStep('start');
    }

    protected static function clearAfterOffer(EmployeesNewJoiner $employee): void
    {
        $employee->emp_registration_sent_at = null;
        $employee->emp_registration_due_date = null;
        self::clearJoinPolicyAppointment($employee);
        self::removeLetterDocument($employee, OnboardingLetterDocument::RESIGNATION_UPLOAD);
        self::removeLetterDocument($employee, OnboardingLetterDocument::REGISTRATION);
        SrHrLetterApproval::clear($employee, SrHrLetterApproval::TYPE_OFFER);
        self::removeLetterDocument($employee, OnboardingLetterDocument::OFFER);
        self::removeLetterDocument($employee, OnboardingLetterDocument::APPOINTMENT_SR_APPROVED);
        self::removeLetterDocument($employee, OnboardingLetterDocument::APPOINTMENT);
    }

    protected static function clearJoinPolicyAppointment(EmployeesNewJoiner $employee, bool $clearAppointmentLetter = true): void
    {
        $employee->emp_joining_requirements = null;
        $employee->emp_policy_accepted_at = null;
        $employee->emp_policy_signature = null;
        $employee->emp_appointment_letter_status = '0';
        $employee->emp_appointment_sent_at = null;
        $employee->emp_appointment_due_date = null;
        $employee->emp_appointment_reject_reason = null;
        $employee->emp_onboarding_status = 'active';
        $employee->emp_archived_at = null;

        $other = $employee->emp_other ?? [];
        unset($other['appointment_signature']);
        if ($clearAppointmentLetter) {
            unset($other['appointment_letter']);
        }
        $employee->emp_other = $other;
        $employee->save();

        self::removeLetterDocument($employee, OnboardingLetterDocument::APPOINTMENT_SR_APPROVED);
        self::removeLetterDocument($employee, OnboardingLetterDocument::APPOINTMENT);
    }

    protected static function removeLetterDocument(EmployeesNewJoiner $employee, string $key): void
    {
        NewEmployeesDocument::where('emp_id', $employee->id)
            ->where('emp_select_document', $key)
            ->delete();
    }
}
