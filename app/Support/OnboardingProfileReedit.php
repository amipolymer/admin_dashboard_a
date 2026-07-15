<?php

namespace App\Support;

use App\Models\EmployeesNewJoiner;

/** HR grants temporary permission for candidate to update submitted profile (Info tab). */
class OnboardingProfileReedit
{
    /** @return array<string, string> */
    public static function reasonOptions(): array
    {
        return [
            'address' => 'Address details are incorrect',
            'education' => 'Education details need correction',
            'employment' => 'Employment / CTC details need correction',
            'contact' => 'Contact or personal details are wrong',
            'documents_mismatch' => 'Details do not match uploaded documents',
            'other' => 'Other (explain below)',
        ];
    }

    public static function isAllowed(EmployeesNewJoiner $employee): bool
    {
        return !empty(($employee->emp_other ?? [])['profile_reedit']['allowed']);
    }

    public static function meta(EmployeesNewJoiner $employee): ?array
    {
        $row = ($employee->emp_other ?? [])['profile_reedit'] ?? null;

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

        return $label ?: (string) ($meta['reason'] ?? $meta['note'] ?? '');
    }

    public static function hasPortalProfileData(EmployeesNewJoiner $employee): bool
    {
        if ($employee->isProfileComplete()) {
            return true;
        }

        $info = OnboardingHrDisplay::profileInformation($employee);
        if (!empty($info['basic_information']) || !empty($info['address_details'])) {
            return true;
        }

        return !in_array($employee->onboardingStep(), ['start', ''], true);
    }

    public static function hrCanGrant(EmployeesNewJoiner $employee): bool
    {
        if (OnboardingArchive::isFinalized($employee)) {
            return false;
        }

        if (!self::hasPortalProfileData($employee)) {
            return false;
        }

        return !self::isAllowed($employee);
    }

    public static function hrCanRevoke(EmployeesNewJoiner $employee): bool
    {
        return self::isAllowed($employee) && !OnboardingArchive::isFinalized($employee);
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

        $reasonText = self::reasonLabel([
            'reason_key' => $reasonKey,
            'reason_detail' => $detail,
        ]);

        $other = is_array($employee->emp_other) ? $employee->emp_other : [];
        $other['profile_reedit'] = [
            'allowed' => true,
            'allowed_at' => now()->toIso8601String(),
            'allowed_by' => auth()->user()?->name ?? auth()->user()?->email ?? 'HR',
            'reason_key' => $reasonKey,
            'reason_detail' => $detail ?: null,
            'reason' => $reasonText,
        ];
        $employee->emp_other = $other;
        $employee->save();
        $employee->refresh();

        OnboardingMail::profileReeditGranted($employee, $reasonText);
    }

    public static function revoke(EmployeesNewJoiner $employee): void
    {
        $other = is_array($employee->emp_other) ? $employee->emp_other : [];
        unset($other['profile_reedit']);
        $employee->emp_other = $other;
        $employee->save();
    }
}
