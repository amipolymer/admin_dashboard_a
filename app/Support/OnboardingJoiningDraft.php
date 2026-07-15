<?php

namespace App\Support;

use App\Data\JoiningFormSchema;
use App\Models\EmployeesNewJoiner;

class OnboardingJoiningDraft
{
    public static function hasDraft(EmployeesNewJoiner $employee): bool
    {
        if ($employee->onboardingStep() !== 'join_forms_sent') {
            return false;
        }

        $draft = ($employee->emp_other ?? [])['joining_draft'] ?? null;

        return is_array($draft) && !empty($draft['information']);
    }

    /**
     * @return array<string, mixed>
     */
    public static function formValues(EmployeesNewJoiner $employee): array
    {
        $draft = ($employee->emp_other ?? [])['joining_draft']['information'] ?? null;

        return is_array($draft) ? $draft : [];
    }

    public static function currentStep(EmployeesNewJoiner $employee): int
    {
        return max(0, (int) (($employee->emp_other ?? [])['joining_draft']['draft_step'] ?? 0));
    }

    public static function savedAt(EmployeesNewJoiner $employee): ?string
    {
        return ($employee->emp_other ?? [])['joining_draft']['draft_saved_at'] ?? null;
    }

    /**
     * @param  array<string, mixed>  $information
     */
    public static function saveDraft(EmployeesNewJoiner $employee, array $information, int $step): void
    {
        $other = is_array($employee->emp_other) ? $employee->emp_other : [];
        $other['joining_draft'] = [
            'information' => $information,
            'draft_step' => max(0, $step),
            'draft_saved_at' => now()->toIso8601String(),
        ];
        $employee->emp_other = $other;
        $employee->save();
    }

    public static function clear(EmployeesNewJoiner $employee): void
    {
        $other = is_array($employee->emp_other) ? $employee->emp_other : [];
        unset($other['joining_draft']);
        $employee->emp_other = $other;
    }

    public static function progressLabel(EmployeesNewJoiner $employee): ?string
    {
        if (!self::hasDraft($employee)) {
            return null;
        }

        $total = max(1, JoiningFormSchema::sectionCount());
        $step = min(self::currentStep($employee), $total - 1);

        return 'Joining draft — Step ' . ($step + 1) . ' of ' . $total;
    }
}
