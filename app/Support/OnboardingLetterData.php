<?php

namespace App\Support;

use App\Models\EmployeesNewJoiner;
use Carbon\Carbon;

class OnboardingLetterData
{
    public static function offer(EmployeesNewJoiner $employee): array
    {
        $stored = ($employee->emp_other ?? [])['offer_letter'] ?? [];
        $basic = OnboardingHrDisplay::profileInformation($employee)['basic_information'] ?? [];
        $address = OnboardingHrDisplay::profileInformation($employee)['address_details'] ?? [];

        return [
            'candidate_name' => $stored['candidate_name'] ?? $employee->emp_name,
            'salutation' => $stored['salutation'] ?? self::salutationFromProfile($basic),
            'candidate_address' => $stored['candidate_address'] ?? self::formatAddress($address),
            'role' => $stored['role'] ?? $employee->emp_department,
            'designation' => $stored['designation'] ?? $employee->emp_role,
            'department' => $stored['department'] ?? $employee->emp_department,
            'grade' => $stored['grade'] ?? $employee->emp_grade ?? null,
            'category' => $stored['category'] ?? $employee->emp_category ?? null,
            'ctc' => $stored['ctc'] ?? null,
            'retention_bonus' => self::positiveAmount($stored['retention_bonus'] ?? null),
            'variable_component' => self::positiveAmount($stored['variable_component'] ?? null),
            'location' => $stored['location'] ?? $employee->emp_location,
            'joining_date' => $stored['joining_date'] ?? optional($employee->emp_date)->format('Y-m-d'),
            'offer_date' => $stored['offer_date'] ?? now()->format('Y-m-d'),
        ];
    }

    public static function appointment(EmployeesNewJoiner $employee): array
    {
        $stored = ($employee->emp_other ?? [])['appointment_letter'] ?? [];
        $offer = ($employee->emp_other ?? [])['offer_letter'] ?? [];

        return [
            'candidate_name' => $stored['candidate_name'] ?? $offer['candidate_name'] ?? $employee->emp_name,
            'salutation' => $stored['salutation'] ?? self::salutationFromProfile(
                OnboardingHrDisplay::profileInformation($employee)['basic_information'] ?? []
            ),
            'role' => $stored['role'] ?? $offer['role'] ?? $employee->emp_department,
            'designation' => $stored['designation'] ?? $offer['designation'] ?? $employee->emp_role,
            'grade' => $stored['grade'] ?? $offer['grade'] ?? $employee->emp_grade ?? null,
            'category' => $stored['category'] ?? $offer['category'] ?? $employee->emp_category ?? null,
            'location' => $stored['location'] ?? $offer['location'] ?? $employee->emp_location,
            'joining_date' => $stored['joining_date'] ?? optional($employee->emp_joining_date)->format('Y-m-d'),
            'ctc_annual' => $stored['ctc_annual'] ?? $offer['ctc'] ?? null,
            'retention_bonus' => self::positiveAmount($stored['retention_bonus'] ?? $offer['retention_bonus'] ?? null),
            'variable_component' => self::positiveAmount($stored['variable_component'] ?? $offer['variable_component'] ?? null),
            'ctc_breakdown' => $stored['ctc_breakdown'] ?? [],
            'letter_date' => $stored['letter_date'] ?? now()->toDateString(),
        ];
    }

    /** Treat blank or zero as not set — optional compensation lines in letters. */
    public static function positiveAmount(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $amount = (float) $value;

        return $amount > 0 ? $amount : null;
    }

    public static function ordinalSuffix(int $day): string
    {
        return match (true) {
            $day % 100 >= 11 && $day % 100 <= 13 => 'th',
            $day % 10 === 1 => 'st',
            $day % 10 === 2 => 'nd',
            $day % 10 === 3 => 'rd',
            default => 'th',
        };
    }

    /** Plain text: 22nd June 2026 */
    public static function ordinalDate(?string $date): string
    {
        if (!$date) {
            return '—';
        }

        $carbon = Carbon::parse($date);
        $day = (int) $carbon->format('j');

        return $day . self::ordinalSuffix($day) . ' ' . $carbon->format('F Y');
    }

    /** HTML with superscript ordinal: 22<sup>nd</sup> June 2026 */
    public static function ordinalDateHtml(?string $date): string
    {
        if (!$date) {
            return '—';
        }

        $carbon = Carbon::parse($date);
        $day = (int) $carbon->format('j');
        $suffix = self::ordinalSuffix($day);

        return $day . '<span class="letter-ordinal">' . $suffix . '</span> ' . $carbon->format('F Y');
    }

    /**
     * @param  array<string, mixed>  $basic
     */
    public static function salutationFromProfile(array $basic): string
    {
        return self::salutation(
            $basic['gender'] ?? null,
            $basic['marital_status'] ?? null
        );
    }

    public static function salutation(?string $gender, ?string $maritalStatus): string
    {
        $g = strtolower(trim((string) $gender));
        $m = strtolower(trim((string) $maritalStatus));

        if (in_array($g, ['male', 'm'], true)) {
            return 'Mr.';
        }

        if (in_array($g, ['female', 'f'], true)) {
            if (in_array($m, ['married', 'm'], true)) {
                return 'Mrs.';
            }

            return 'Ms.';
        }

        return '';
    }

    /** @deprecated Use salutation() or salutationFromProfile() */
    public static function salutationFromGender(?string $gender): string
    {
        return self::salutation($gender, null);
    }

    /**
     * @param  array<string, mixed>  $address
     */
    public static function formatAddress(array $address): string
    {
        $full = trim((string) ($address['current_full_address'] ?? ''));
        if ($full !== '') {
            return $full;
        }

        $parts = array_filter([
            $address['current_co'] ?? null,
            $address['current_line1'] ?? null,
            $address['current_line2'] ?? null,
            $address['current_locality'] ?? null,
            $address['current_landmark'] ?? null,
            trim(implode(', ', array_filter([
                $address['current_city'] ?? null,
                $address['current_state'] ?? null,
                $address['current_pincode'] ?? null,
            ]))),
        ], fn ($v) => trim((string) $v) !== '');

        return $parts ? implode(', ', $parts) : '—';
    }
}
