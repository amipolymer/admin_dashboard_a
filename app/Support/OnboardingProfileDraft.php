<?php

namespace App\Support;

use App\Data\ProfileFormSchema;
use App\Models\EmployeesNewJoiner;

class OnboardingProfileDraft
{
    public static function isSubmitted(EmployeesNewJoiner $employee): bool
    {
        $data = $employee->emp_profile_data ?? [];

        if (!empty($data['submitted'])) {
            return true;
        }

        $info = $data['information'] ?? [];
        if (is_array($info) && !empty($info['basic_information']['name'])) {
            return in_array($employee->onboardingStep(), [
                'profile_completed',
                'hr_review',
                'documents_submitted',
                'documents_approved',
                'documents_rejected',
                'offer_pending_sr_hr',
                'offer_sr_rejected',
                'offer_sent',
                'offer_accepted',
                'offer_rejected',
                'registration_sent',
                'registration_submitted',
                'registration_verified',
                'bgv_started',
                'bgv_completed',
                'join_started',
                'join_forms_sent',
                'join_forms_submitted',
                'policy_signed',
                'appointment_pending_sr_hr',
                'appointment_sr_rejected',
                'appointment_sent',
                'appointment_accepted',
                'appointment_rejected',
                'end',
            ], true);
        }

        return false;
    }

    public static function hasDraft(EmployeesNewJoiner $employee): bool
    {
        if (self::isSubmitted($employee)) {
            return false;
        }

        $data = $employee->emp_profile_data ?? [];

        return !empty($data['information']) && is_array($data['information']);
    }

    /**
     * @return array<string, mixed>
     */
    public static function formValues(EmployeesNewJoiner $employee): array
    {
        return ($employee->emp_profile_data ?? [])['information'] ?? [];
    }

    public static function currentStep(EmployeesNewJoiner $employee): int
    {
        return max(0, (int) (($employee->emp_profile_data ?? [])['draft_step'] ?? 0));
    }

    public static function savedAt(EmployeesNewJoiner $employee): ?string
    {
        return ($employee->emp_profile_data ?? [])['draft_saved_at']
            ?? ($employee->emp_profile_data ?? [])['saved_at']
            ?? null;
    }

    public static function progressLabel(EmployeesNewJoiner $employee): ?string
    {
        if (!self::hasDraft($employee)) {
            return null;
        }

        $total = max(1, ProfileFormSchema::sectionCount());
        $step = min(self::currentStep($employee), $total - 1);

        return 'Draft in progress — Step ' . ($step + 1) . ' of ' . $total;
    }

    /**
     * @param  array<string, mixed>  $information
     */
    public static function saveDraft(EmployeesNewJoiner $employee, array $information, int $step): void
    {
        $data = $employee->emp_profile_data ?? [];
        $data['information'] = $information;
        $data['draft_step'] = max(0, $step);
        $data['draft_saved_at'] = now()->toIso8601String();
        $data['submitted'] = false;
        unset($data['saved_at']);
        $employee->emp_profile_data = $data;
        $employee->save();
    }

    /**
     * @param  array<string, mixed>  $information
     */
    public static function markSubmitted(EmployeesNewJoiner $employee, array $information): void
    {
        $data = $employee->emp_profile_data ?? [];
        $data['information'] = $information;
        $data['submitted'] = true;
        $data['saved_at'] = now()->toIso8601String();
        unset($data['draft_step'], $data['draft_saved_at']);
        $employee->emp_profile_data = $data;
    }
}
