<?php

namespace App\Support;

use App\Models\EmployeesNewJoiner;
use Illuminate\Support\Str;

class OnboardingHrDisplay
{
    /**
     * Normalized profile sections from emp_profile_data (supports legacy step1/step2).
     *
     * @return array<string, mixed>
     */
    public static function profileInformation(EmployeesNewJoiner $employee): array
    {
        $raw = $employee->emp_profile_data ?? [];

        if (!empty($raw['information']) && is_array($raw['information'])) {
            return $raw['information'];
        }

        return array_filter([
            'basic_information' => $raw['step1'] ?? [],
            'address_details' => $raw['step2'] ?? [],
            'education_qualification' => $raw['education_list'] ?? [],
            'previous_employment' => $raw['employment_list'] ?? [],
            'declaration' => $raw['declaration'] ?? [],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public static function basicRows(EmployeesNewJoiner $employee, array $basic): array
    {
        $companyName = config('letterhead.company_name', config('app.name'));
        $rows = [
            'Name' => $basic['name'] ?? $employee->emp_name,
            'Designation (HR)' => $employee->emp_role,
            'Level Wise Grading' => $employee->emp_grade,
            'Category' => $employee->emp_category,
            'Source' => $basic['application_source'] ?? $employee->emp_application_source ?? null,
            'Job title (profile)' => $basic['other_profession'] ?? null,
            "Father's name" => $basic['fathers_name'] ?? null,
            'Gender' => $basic['gender'] ?? null,
            'Religion' => $basic['religion'] ?? null,
            'Date of birth' => $basic['dob'] ?? optional($employee->emp_dob)->format('Y-m-d'),
            'City' => $basic['city'] ?? null,
            'Aadhaar (uid)' => $basic['uid'] ?? null,
            'PAN (PANV)' => $basic['pan'] ?? null,
            'Email' => $basic['email'] ?? $employee->emp_email,
            'Mobile' => self::formatPhone($basic['phone_country_code'] ?? null, $basic['phone'] ?? $employee->emp_phone),
            'Alternate mobile' => self::formatPhone($basic['alternate_phone_country_code'] ?? null, $basic['alternate_phone'] ?? null),
            'Expected joining (HR)' => optional($employee->emp_joining_date)->format('Y-m-d') ?? optional($employee->emp_date)->format('Y-m-d'),
            'UAN / PF' => $basic['uan'] ?? null,
            'Blood group' => $basic['blood_group'] ?? null,
            'Marital status' => $basic['marital_status'] ?? null,
            'Spouse name' => $basic['spouse_name'] ?? null,
            "Voter's ID" => $basic['voters_id_number'] ?? null,
            'Driving licence' => $basic['driving_license_number'] ?? null,
            'DL valid till' => $basic['driving_license_valid_till'] ?? null,
            'Passport number' => $basic['passport_number'] ?? null,
            'Passport validity' => $basic['passport_validity'] ?? null,
            'Relative/contact at ' . $companyName => $basic['has_company_contacts'] ?? null,
            'Contact name' => ($basic['has_company_contacts'] ?? '') === 'Yes' ? ($basic['company_contact_name'] ?? null) : null,
            'Contact relationship' => ($basic['has_company_contacts'] ?? '') === 'Yes' ? ($basic['company_contact_relationship'] ?? null) : null,
            'Contact department' => ($basic['has_company_contacts'] ?? '') === 'Yes' ? ($basic['company_contact_department'] ?? null) : null,
            'Location (HR record)' => $employee->emp_location,
            'Emergency contact' => $employee->emergency_contact,
        ];

        return self::filterEmpty($rows);
    }

    /**
     * @return array<string, string>
     */
    public static function addressBlock(array $address, string $prefix, string $title = ''): array
    {
        unset($title);
        $fields = [
            'C/O' => "{$prefix}_co",
            'Line 1' => "{$prefix}_line1",
            'Line 2' => "{$prefix}_line2",
            'Locality' => "{$prefix}_locality",
            'Landmark' => "{$prefix}_landmark",
            'City' => "{$prefix}_city",
            'State' => "{$prefix}_state",
            'Country' => "{$prefix}_country",
            'Pin code' => "{$prefix}_pincode",
            'Full address' => "{$prefix}_full_address",
        ];
        $out = [];
        foreach ($fields as $label => $field) {
            $val = $address[$field] ?? null;
            if ($val !== null && $val !== '') {
                $out[$label] = $val;
            }
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<string>
     */
    public static function tableColumns(array $rows): array
    {
        $cols = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            foreach (array_keys($row) as $key) {
                if (!in_array($key, $cols, true)) {
                    $cols[] = $key;
                }
            }
        }

        return $cols;
    }

    /**
     * @return array<string, string>
     */
    public static function familyHeaderRows(array $family): array
    {
        return self::filterEmpty([
            "Father's name" => $family['father_name'] ?? null,
            'Spouse name' => $family['spouse_name'] ?? null,
            'Include spouse in list' => self::yesNo($family['include_spouse'] ?? null),
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    public static function familyMemberRows(array $family): array
    {
        $members = $family['members'] ?? [];
        if (!is_array($members)) {
            return [];
        }

        return array_values(array_filter($members, fn ($row) => is_array($row) && self::rowHasData($row)));
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    public static function industryRelativeRows(array $employment): array
    {
        $rows = $employment['industry_relatives'] ?? [];
        if (!is_array($rows)) {
            return [];
        }

        return array_values(array_filter($rows, fn ($row) => is_array($row) && self::rowHasData($row)));
    }

    /**
     * @return array<string, string>
     */
    public static function employmentFooterRows(array $employment): array
    {
        $hasRel = $employment['has_industry_relatives'] ?? null;

        return self::filterEmpty([
            'Current CTC' => $employment['current_ctc'] ?? null,
            'Expected CTC' => $employment['expected_ctc'] ?? null,
            'HR name (last company)' => $employment['hr_name'] ?? null,
            'HR email' => $employment['hr_email'] ?? null,
            'HR phone' => self::formatPhone($employment['hr_phone_country_code'] ?? null, $employment['hr_phone'] ?? null),
            'Relative in industry' => $hasRel === 'yes' ? 'Yes' : ($hasRel === 'no' ? 'No' : null),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public static function declarationRows(array $declaration): array
    {
        return self::filterEmpty([
            'Declaration accepted' => self::yesNo($declaration['consent_correct'] ?? null),
            'Declaration date' => $declaration['declaration_date'] ?? null,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public static function letterRows(?array $letter): array
    {
        if (!$letter) {
            return [];
        }

        $rows = [
            'Candidate name' => $letter['candidate_name'] ?? null,
            'Role' => $letter['role'] ?? null,
            'Designation' => $letter['designation'] ?? null,
            'Location' => $letter['location'] ?? null,
            'CTC (annual)' => isset($letter['ctc']) ? (string) $letter['ctc'] : ($letter['ctc_annual'] ?? null),
            'Joining date' => $letter['joining_date'] ?? null,
            'Offer / letter date' => $letter['offer_date'] ?? $letter['letter_date'] ?? null,
        ];
        if (!empty($letter['ctc_breakdown']) && is_array($letter['ctc_breakdown'])) {
            foreach ($letter['ctc_breakdown'] as $part => $amount) {
                if ($amount !== null && $amount !== '') {
                    $rows['CTC ' . self::label((string) $part)] = (string) $amount;
                }
            }
        }

        return self::filterEmpty($rows);
    }

    /**
     * @return array<string, string>
     */
    public static function joiningFieldRows(?array $section, array $data): array
    {
        if (!$section) {
            return [];
        }

        $rows = [];
        foreach (\App\Data\JoiningFormSchema::fieldsFromSection($section) as $field) {
            $name = (string) ($field['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $label = (string) ($field['label'] ?? self::label($name));
            $val = $data[$name] ?? null;
            if ($val === null || $val === '') {
                continue;
            }
            if (($field['type'] ?? '') === 'select' && in_array($val, ['yes', 'no'], true)) {
                $val = ucfirst((string) $val);
            }
            $rows[$label] = (string) $val;
        }

        return self::filterEmpty($rows);
    }

    /**
     * @return array<string, string>
     */
    public static function joiningMedicalFitnessRows(array $medical, bool $hasUploadedFile): array
    {
        $available = $medical['medical_fitness_available'] ?? '';
        $rows = [];
        if ($available !== '') {
            $rows['Certificate available'] = ucfirst((string) $available);
        }
        if ($available === 'no') {
            $reason = trim((string) ($medical['medical_fitness_unavailable_reason'] ?? ''));
            if ($reason !== '') {
                $rows['Reason'] = $reason;
            }
        }
        if ($available === 'yes' && ($hasUploadedFile || !empty($medical['uploaded']))) {
            $rows['Certificate'] = 'Uploaded';
        }

        return self::filterEmpty($rows);
    }

    public static function formatPhone(?string $code, ?string $number): ?string
    {
        $n = trim((string) $number);
        if ($n === '') {
            return null;
        }

        return trim((string) $code . ' ' . $n);
    }

    public static function label(string $key): string
    {
        return Str::title(str_replace('_', ' ', $key));
    }

    protected static function yesNo(mixed $v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }

        return ($v === '1' || $v === 1 || $v === true) ? 'Yes' : 'No';
    }

    /**
     * @param  array<string, mixed>  $rows
     * @return array<string, string>
     */
    protected static function filterEmpty(array $rows): array
    {
        return array_filter($rows, fn ($v) => $v !== null && $v !== '' && $v !== '—');
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected static function rowHasData(array $row): bool
    {
        foreach ($row as $v) {
            if (trim((string) $v) !== '') {
                return true;
            }
        }

        return false;
    }
}
