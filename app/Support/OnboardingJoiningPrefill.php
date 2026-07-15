<?php

namespace App\Support;

use App\Models\EmployeesNewJoiner;

class OnboardingJoiningPrefill
{
    /**
     * @param  array<string, mixed>  $savedBankPf
     * @return array<string, mixed>
     */
    public static function mergeBankPf(EmployeesNewJoiner $employee, array $savedBankPf = []): array
    {
        $basic = ($employee->emp_profile_data ?? [])['information']['basic_information'] ?? [];
        $uan = trim((string) ($basic['uan'] ?? ''));
        if ($uan !== '' && trim((string) ($savedBankPf['pf_uan_details'] ?? '')) === '') {
            $savedBankPf['pf_uan_details'] = $uan;
        }

        return $savedBankPf;
    }

    /**
     * @param  list<array<string, mixed>>|null  $existing
     * @return list<array{name: string, dob: string, relationship: string}>
     */
    public static function mediclaimDependentsFromProfile(EmployeesNewJoiner $employee, ?array $existing = null): array
    {
        if (is_array($existing) && $existing !== []) {
            return $existing;
        }

        $profile = ($employee->emp_profile_data ?? [])['information'] ?? [];
        $basic = is_array($profile['basic_information'] ?? null) ? $profile['basic_information'] : [];
        $family = is_array($profile['family_details'] ?? null) ? $profile['family_details'] : [];
        $members = $family['members'] ?? [];
        if (!is_array($members)) {
            $members = [];
        }

        $rows = [];
        $seen = [];

        $marital = strtolower(trim((string) ($basic['marital_status'] ?? '')));
        $spouseName = trim((string) ($family['spouse_name'] ?? $basic['spouse_name'] ?? ''));
        $includeSpouse = !empty($family['include_spouse']) || $marital === 'married';
        if ($includeSpouse && $spouseName !== '') {
            $rows[] = self::mediclaimRow($spouseName, '', 'Spouse');
            $seen[strtolower($spouseName)] = true;
        }

        foreach ($members as $member) {
            if (!is_array($member)) {
                continue;
            }
            $name = trim((string) ($member['name'] ?? ''));
            if ($name === '' || isset($seen[strtolower($name)])) {
                continue;
            }
            $relationship = self::mapMediclaimRelationship((string) ($member['relation'] ?? $member['relationship'] ?? ''));
            if ($relationship === null) {
                continue;
            }
            $dob = trim((string) ($member['dob'] ?? ''));
            if ($dob === '' && trim((string) ($member['age'] ?? '')) !== '') {
                $dob = '';
            }
            $rows[] = self::mediclaimRow($name, $dob, $relationship);
            $seen[strtolower($name)] = true;
        }

        return $rows;
    }

    /**
     * @return array{name: string, dob: string, relationship: string}
     */
    private static function mediclaimRow(string $name, string $dob, string $relationship): array
    {
        return [
            'name' => $name,
            'dob' => $dob,
            'relationship' => $relationship,
        ];
    }

    public static function mapMediclaimRelationship(string $relation): ?string
    {
        $r = strtolower(trim($relation));
        if ($r === '') {
            return null;
        }

        if (preg_match('/\b(father|mother|parent|parents|in[-\s]?law|brother|sister|sibling)\b/', $r)) {
            return null;
        }

        if (preg_match('/\b(wife|husband|spouse)\b/', $r)) {
            return 'Spouse';
        }
        if (preg_match('/\b(son|boy)\b/', $r) || ($r === 's/o')) {
            return 'Son';
        }
        if (preg_match('/\b(daughter|girl|d\/o)\b/', $r)) {
            return 'Daughter';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $saved
     * @return array<string, mixed>
     */
    public static function mergeSavedJoiningData(EmployeesNewJoiner $employee, array $saved = []): array
    {
        $saved['bank_pf'] = self::mergeBankPf($employee, is_array($saved['bank_pf'] ?? null) ? $saved['bank_pf'] : []);
        $existingMediclaim = is_array($saved['mediclaim_dependents'] ?? null) ? $saved['mediclaim_dependents'] : null;
        $saved['mediclaim_dependents'] = self::mediclaimDependentsFromProfile($employee, $existingMediclaim);

        return $saved;
    }
}
