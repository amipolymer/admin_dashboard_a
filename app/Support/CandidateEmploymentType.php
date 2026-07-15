<?php

namespace App\Support;

use App\Models\EmployeesNewJoiner;

class CandidateEmploymentType
{
    public const FRESHER = 'fresher';

    public const EXPERIENCED = 'experienced';

    public static function isFresher(EmployeesNewJoiner $employee): bool
    {
        return self::resolve($employee) === self::FRESHER;
    }

    public static function resolve(EmployeesNewJoiner $employee): string
    {
        $type = ($employee->emp_other ?? [])['candidate_profile']['employment_type'] ?? null;

        if (in_array($type, [self::FRESHER, self::EXPERIENCED], true)) {
            return $type;
        }

        $saved = ($employee->emp_profile_data ?? [])['information']['previous_employment']['employment_type'] ?? null;
        if ($type === null && in_array($saved, [self::FRESHER, self::EXPERIENCED], true)) {
            return $saved;
        }

        return self::EXPERIENCED;
    }

    public static function persist(EmployeesNewJoiner $employee, string $type): void
    {
        if (!in_array($type, [self::FRESHER, self::EXPERIENCED], true)) {
            return;
        }

        $other = $employee->emp_other ?? [];
        $other['candidate_profile'] = array_merge($other['candidate_profile'] ?? [], [
            'employment_type' => $type,
        ]);
        $employee->emp_other = $other;
    }

    /**
     * Normalize previous_employment block before save (fresher vs experienced).
     *
     * @param  array<string, mixed>  $employment
     * @return array<string, mixed>
     */
    public static function normalizeEmploymentPayload(EmployeesNewJoiner $employee, array $employment): array
    {
        if (self::isFresher($employee)) {
            $normalized = [
                'employment_type' => self::FRESHER,
                'records' => [],
                'current_ctc' => null,
                'expected_ctc' => trim((string) ($employment['expected_ctc'] ?? '')),
                'hr_name' => null,
                'hr_email' => null,
                'hr_phone' => null,
                'hr_phone_country_code' => null,
            ];

            return self::mergeIndustryRelatives($normalized, $employment);
        }

        $employment['employment_type'] = self::EXPERIENCED;

        return self::normalizeIndustryRelatives($employment);
    }

    /**
     * @param  array<string, mixed>  $employment
     * @return array<string, mixed>
     */
    public static function normalizeIndustryRelatives(array $employment): array
    {
        $has = strtolower(trim((string) ($employment['has_industry_relatives'] ?? '')));
        $employment['has_industry_relatives'] = in_array($has, ['yes', 'no'], true) ? $has : '';

        if ($has !== 'yes') {
            $employment['industry_relatives'] = [];

            return $employment;
        }

        $rows = is_array($employment['industry_relatives'] ?? null) ? $employment['industry_relatives'] : [];
        $filled = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $hasData = false;
            foreach ($row as $value) {
                if (trim((string) $value) !== '') {
                    $hasData = true;
                    break;
                }
            }
            if ($hasData) {
                $filled[] = $row;
            }
        }
        $employment['industry_relatives'] = $filled;

        return $employment;
    }

    /**
     * @param  array<string, mixed>  $target
     * @param  array<string, mixed>  $source
     * @return array<string, mixed>
     */
    protected static function mergeIndustryRelatives(array $target, array $source): array
    {
        return self::normalizeIndustryRelatives(array_merge($target, [
            'has_industry_relatives' => $source['has_industry_relatives'] ?? '',
            'industry_relatives' => $source['industry_relatives'] ?? [],
        ]));
    }

    public static function label(string $type): string
    {
        return $type === self::FRESHER ? 'Fresher' : 'Experienced';
    }
}
