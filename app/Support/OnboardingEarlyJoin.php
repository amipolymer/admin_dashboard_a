<?php

namespace App\Support;

use App\Models\EmployeesNewJoiner;
use Carbon\Carbon;

/** HR may allow joining forms while BGV is still in progress (with documented reason). */
class OnboardingEarlyJoin
{
    /** @return array<string, string> */
    public static function reasonOptions(): array
    {
        return [
            'bgv_in_progress' => 'BGV in progress — candidate must join on schedule',
            'urgent_joining' => 'Urgent joining date — proceed before BGV completes',
            'partial_bgv_ok' => 'Partial BGV clearance — management approved',
            'other' => 'Other (explain below)',
        ];
    }

    public static function isAllowed(EmployeesNewJoiner $employee): bool
    {
        return !empty(($employee->emp_other ?? [])['early_join']['allowed']);
    }

    public static function meta(EmployeesNewJoiner $employee): ?array
    {
        $row = ($employee->emp_other ?? [])['early_join'] ?? null;

        return is_array($row) && !empty($row['allowed']) ? $row : null;
    }

    public static function reasonLabel(?array $meta): string
    {
        if (!$meta) {
            return '';
        }

        $key = $meta['reason_key'] ?? '';
        $options = self::reasonOptions();
        $label = $options[$key] ?? '';

        if ($key === 'other' && !empty($meta['reason_detail'])) {
            return $meta['reason_detail'];
        }

        if ($label !== '' && !empty($meta['reason_detail'])) {
            return $label . ' — ' . $meta['reason_detail'];
        }

        return $label ?: (string) ($meta['reason'] ?? '');
    }

    public static function hrCanGrant(EmployeesNewJoiner $employee): bool
    {
        if (OnboardingArchive::isFinalized($employee)) {
            return false;
        }

        if (self::isAllowed($employee)) {
            return false;
        }

        return in_array($employee->onboardingStep(), [
            'registration_verified',
            'bgv_started',
        ], true);
    }

    public static function hrCanRevoke(EmployeesNewJoiner $employee): bool
    {
        return self::isAllowed($employee)
            && !in_array($employee->onboardingStep(), ['join_forms_sent', 'join_forms_submitted', 'policy_signed', 'end'], true)
            && !OnboardingArchive::isFinalized($employee);
    }

    public static function hrCanStartJoin(EmployeesNewJoiner $employee): bool
    {
        if (OnboardingArchive::isFinalized($employee)) {
            return false;
        }

        $allowedSteps = ['registration_verified', 'bgv_started', 'bgv_completed'];
        if (!in_array($employee->onboardingStep(), $allowedSteps, true)) {
            return false;
        }

        return $employee->onboardingStep() === 'bgv_completed' || self::isAllowed($employee);
    }

    public static function joinProcessPastDays(): int
    {
        return max(0, (int) config('onboarding.join_process_past_days', 5));
    }

    public static function joinProcessFutureDays(): int
    {
        return max(0, (int) config('onboarding.join_process_future_days', 2));
    }

    public static function joinDateWindowStart(): Carbon
    {
        return today()->subDays(self::joinProcessPastDays());
    }

    public static function joinDateWindowEnd(): Carbon
    {
        return today()->addDays(self::joinProcessFutureDays());
    }

    public static function isJoinDateAllowedForStartProcess(mixed $date): bool
    {
        if ($date === null || $date === '') {
            return false;
        }

        $selected = $date instanceof Carbon
            ? $date->copy()->startOfDay()
            : Carbon::parse((string) $date)->startOfDay();

        return $selected->gte(self::joinDateWindowStart()->startOfDay())
            && $selected->lte(self::joinDateWindowEnd()->startOfDay());
    }

    public static function joinDateWindowMessage(): string
    {
        return sprintf(
            'Join date must be between %s and %s (within %d days before today or up to %d days after today).',
            self::joinDateWindowStart()->format('d-m-Y'),
            self::joinDateWindowEnd()->format('d-m-Y'),
            self::joinProcessPastDays(),
            self::joinProcessFutureDays()
        );
    }

    public static function grant(EmployeesNewJoiner $employee, string $reasonKey, ?string $reasonDetail = null): void
    {
        $options = self::reasonOptions();
        if (!isset($options[$reasonKey])) {
            throw new \InvalidArgumentException('Invalid reason selected.');
        }

        $detail = trim((string) $reasonDetail);
        if ($reasonKey === 'other' && strlen($detail) < 10) {
            throw new \InvalidArgumentException('Please describe the reason (at least 10 characters).');
        }

        $other = $employee->emp_other ?? [];
        $other['early_join'] = [
            'allowed' => true,
            'reason_key' => $reasonKey,
            'reason_detail' => $detail,
            'granted_at' => now()->toIso8601String(),
            'granted_by' => auth()->id(),
        ];
        $employee->emp_other = $other;
        $employee->save();
    }

    public static function revoke(EmployeesNewJoiner $employee): void
    {
        $other = $employee->emp_other ?? [];
        unset($other['early_join']);
        $employee->emp_other = $other;
        $employee->save();
    }
}
