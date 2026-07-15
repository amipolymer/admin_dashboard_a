<?php

namespace App\Support;

use App\Models\EmployeesNewJoiner;

class OnboardingPortalTabs
{
    /** @return array<string, string> */
    public static function tabsFor(EmployeesNewJoiner $employee): array
    {
        $step = $employee->onboardingStep();
        $tabs = ['info' => 'Info'];

        if ($employee->isProfileComplete()
            || $employee->hasCandidateSubmittedDocuments()
            || in_array($step, [
                'hr_review', 'documents_submitted', 'documents_approved', 'documents_rejected',
                'offer_pending_sr_hr', 'offer_sr_rejected', 'offer_sent', 'offer_accepted', 'offer_rejected',
                'registration_sent', 'registration_submitted', 'registration_verified',
                'bgv_started', 'bgv_completed',
                'join_forms_sent', 'join_forms_submitted', 'policy_signed',
                'appointment_pending_sr_hr', 'appointment_sr_rejected', 'appointment_sent',
                'appointment_accepted', 'appointment_rejected', 'end',
            ], true)) {
            $tabs['document'] = 'Document';
        }

        if ($employee->emp_offer_sent_at
            || in_array($step, [
                'offer_sent', 'offer_accepted', 'offer_rejected',
                'registration_sent', 'registration_submitted', 'registration_verified',
                'bgv_started', 'bgv_completed',
                'join_forms_sent', 'join_forms_submitted', 'policy_signed',
                'appointment_pending_sr_hr', 'appointment_sr_rejected', 'appointment_sent',
                'appointment_accepted', 'appointment_rejected', 'end',
            ], true)) {
            $tabs['letter'] = 'Letter';
        }

        if (OnboardingJoiningAccess::shouldShowJoiningTab($employee)) {
            $tabs['joining'] = 'Joining';
        }

        if (OnboardingJoiningAccess::shouldShowPolicyTab($employee)) {
            $tabs['policy'] = 'Policy';
        }

        return $tabs;
    }

    public static function isVisible(EmployeesNewJoiner $employee, string $tab): bool
    {
        return isset(self::tabsFor($employee)[$tab]);
    }
}
