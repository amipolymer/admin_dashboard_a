<?php

namespace App\Support;

use App\Models\EmployeesNewJoiner;

class OnboardingStepGate
{
    /** Onboarding complete or SR-HR approved signed appointment — no further HR/candidate edits. */
    public static function isOnboardingLocked(EmployeesNewJoiner $employee): bool
    {
        if (OnboardingArchive::isFinalized($employee)) {
            return true;
        }

        return $employee->emp_appointment_letter_status === 'accept'
            && SrHrLetterApproval::isApproved($employee, SrHrLetterApproval::TYPE_APPOINTMENT);
    }

    public static function isHrMutationsBlocked(EmployeesNewJoiner $employee): bool
    {
        return self::isOnboardingLocked($employee);
    }

    /** Initial portal invitation — only before candidate saves profile. */
    public static function canResendPortalLink(EmployeesNewJoiner $employee): bool
    {
        if (self::isHrMutationsBlocked($employee)) {
            return false;
        }

        if ($employee->isProfileComplete()) {
            return false;
        }

        return $employee->onboardingStep() === 'start';
    }

    /** Offer letter edit / SR-HR resend blocked once candidate accepts appointment. */
    public static function isOfferStageLocked(EmployeesNewJoiner $employee): bool
    {
        if ($employee->emp_appointment_letter_status === 'accept') {
            return true;
        }

        return in_array($employee->onboardingStep(), [
            'appointment_accepted',
            'appointment_pending_sr_hr',
            'appointment_sr_rejected',
            'end',
        ], true);
    }

    /** Registration letter — no candidate re-upload after HR verification. */
    public static function isRegistrationVerified(EmployeesNewJoiner $employee): bool
    {
        return in_array($employee->onboardingStep(), [
            'registration_verified',
            'bgv_started',
            'bgv_completed',
            'join_forms_sent',
            'join_forms_submitted',
            'policy_signed',
            'appointment_sent',
            'appointment_accepted',
            'appointment_pending_sr_hr',
            'appointment_sr_rejected',
            'appointment_rejected',
            'end',
        ], true);
    }

    public static function canCandidateUploadRegistration(EmployeesNewJoiner $employee): bool
    {
        if (self::isRegistrationVerified($employee)) {
            return false;
        }

        return $employee->onboardingStep() === 'registration_sent';
    }

    public static function humanStepLabel(?string $step): string
    {
        $step = $step ?: 'start';

        return match ($step) {
            'start' => 'Invitation sent',
            'profile_completed' => 'Profile submitted',
            'hr_review' => 'Documents under HR review',
            'documents_submitted' => 'Documents submitted',
            'documents_approved' => 'Documents approved',
            'documents_rejected' => 'Documents rejected',
            'offer_pending_sr_hr' => 'Offer awaiting SR-HR',
            'offer_sr_rejected' => 'Offer rejected by SR-HR',
            'offer_sent' => 'Offer sent to candidate',
            'offer_accepted' => 'Offer accepted',
            'offer_rejected' => 'Offer declined',
            'registration_sent' => 'Registration letter requested',
            'registration_submitted' => 'Registration letter uploaded',
            'registration_verified' => 'Registration verified',
            'bgv_started' => 'Background verification in progress',
            'bgv_completed' => 'Background verification complete',
            'join_forms_sent' => 'Joining forms open',
            'join_forms_submitted' => 'Joining forms submitted',
            'policy_signed' => 'Policy accepted',
            'appointment_sent' => 'Appointment letter sent',
            'appointment_accepted' => 'Appointment accepted',
            'appointment_rejected' => 'Appointment declined',
            'appointment_pending_sr_hr' => 'Appointment awaiting SR-HR',
            'appointment_sr_rejected' => 'Appointment rejected by SR-HR',
            'end' => 'Onboarding complete',
            default => ucfirst(str_replace('_', ' ', $step)),
        };
    }

    /** Short stage label for HR employee list grid. */
    public static function listOnboardingStage(?string $step): string
    {
        $step = $step ?: 'start';

        return match ($step) {
            'start', 'profile_completed' => 'Portal',
            'hr_review', 'documents_submitted', 'documents_approved', 'documents_rejected' => 'Documents',
            'offer_pending_sr_hr', 'offer_sr_rejected', 'offer_sent', 'offer_accepted', 'offer_rejected' => 'Offer',
            'registration_sent', 'registration_submitted', 'registration_verified' => 'Registration',
            'bgv_started', 'bgv_completed' => 'BGV',
            'join_forms_sent', 'join_forms_submitted' => 'Join',
            'policy_signed' => 'Policy',
            'appointment_sent', 'appointment_accepted', 'appointment_rejected',
            'appointment_pending_sr_hr', 'appointment_sr_rejected' => 'Appointment',
            'end' => 'Complete',
            default => ucfirst(str_replace('_', ' ', $step)),
        };
    }

    public static function documentStatusLabel(?string $status): string
    {
        return match ($status) {
            'completed' => 'Approved',
            'process' => 'In review',
            'rejected' => 'Rejected',
            default => $status !== null && $status !== '' ? ucfirst($status) : '—',
        };
    }
}
