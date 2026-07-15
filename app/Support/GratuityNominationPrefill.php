<?php

namespace App\Support;

use App\Data\JoiningFormSchema;
use App\Models\EmployeesNewJoiner;

class GratuityNominationPrefill
{
    public const SECTION_KEY = 'gratuity_nomination';

    public static function section(): ?array
    {
        return JoiningFormSchema::section(self::SECTION_KEY);
    }

    /** @return list<array{key: string, label: string, type?: string, required?: bool}> */
    public static function nomineeColumns(): array
    {
        $columns = self::section()['nominee_group']['columns'] ?? [];

        return is_array($columns) ? $columns : [];
    }

    /** @return list<string> */
    public static function requiredNomineeFields(): array
    {
        return array_column(self::nomineeColumns(), 'key');
    }

    /** @return array<string, mixed> */
    public static function employeeDefaults(EmployeesNewJoiner $employee, array $saved = [], array $joiningDetails = []): array
    {
        $info = OnboardingHrDisplay::profileInformation($employee);
        $basic = $info['basic_information'] ?? [];
        $address = $info['address_details'] ?? [];

        $permanent = trim((string) ($address['permanent_full_address'] ?? ''));
        if ($permanent === '') {
            $parts = OnboardingHrDisplay::addressBlock($address, 'permanent');
            $permanent = implode(', ', array_filter(array_values($parts)));
        }

        $joinDate = trim((string) (
            $joiningDetails['confirmed_join_date']
            ?? optional($employee->emp_joining_date)->format('Y-m-d')
            ?? ($basic['joining_date'] ?? '')
        ));

        $empId = trim((string) (
            $employee->emp_employee_id
            ?? ($joiningDetails['emp_id'] ?? '')
        ));

        $defaults = [
            'employee_full_name' => trim((string) ($basic['name'] ?? $employee->emp_name)),
            'gender' => trim((string) ($basic['gender'] ?? '')),
            'religion' => trim((string) ($basic['religion'] ?? '')),
            'marital_status' => trim((string) ($basic['marital_status'] ?? '')),
            'department' => trim((string) ($employee->emp_department ?? '')),
            'employee_id' => $empId,
            'date_of_joining' => $joinDate,
            'permanent_address' => $permanent,
            'gratuity_declaration_confirm' => '',
            'nomination_submitted' => '',
        ];

        foreach ($defaults as $key => $value) {
            if (isset($saved[$key]) && (string) $saved[$key] !== '') {
                $defaults[$key] = (string) $saved[$key];
            }
        }

        return $defaults;
    }

    /** @return array<string, mixed> */
    public static function defaults(EmployeesNewJoiner $employee, array $saved = [], array $joiningDetails = []): array
    {
        return array_merge(
            self::employeeDefaults($employee, $saved, $joiningDetails),
            ['nominees' => self::normalizeNominees($saved)]
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array<string, string>>
     */
    public static function normalizeNominees(array $data): array
    {
        if (!empty($data['nominees']) && is_array($data['nominees'])) {
            $rows = [];
            foreach ($data['nominees'] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $normalized = self::normalizeNomineeRow($row);
                if (self::nomineeRowHasData($normalized)) {
                    $rows[] = $normalized;
                }
            }

            return $rows !== [] ? $rows : [self::emptyNomineeRow()];
        }

        $legacy = self::normalizeNomineeRow([
            'nominee_full_name' => $data['nominee_full_name'] ?? '',
            'nominee_relationship' => $data['nominee_relationship'] ?? '',
            'nominee_age' => $data['nominee_age'] ?? '',
            'nominee_address' => $data['nominee_address'] ?? '',
            'gratuity_share_percentage' => $data['gratuity_share_percentage'] ?? '',
        ]);

        return self::nomineeRowHasData($legacy) ? [$legacy] : [self::emptyNomineeRow()];
    }

    /** @param  list<array<string, string>>  $nominees */
    public static function validateNominees(array $nominees): ?string
    {
        $active = array_values(array_filter($nominees, fn (array $row) => self::nomineeRowHasData($row)));

        if ($active === []) {
            return 'Gratuity nomination: add at least one nominee.';
        }

        $totalShare = 0.0;

        foreach ($active as $index => $row) {
            foreach (self::requiredNomineeFields() as $field) {
                if (trim((string) ($row[$field] ?? '')) === '') {
                    return 'Gratuity nomination: please complete all fields for nominee #' . ($index + 1) . '.';
                }
            }

            $share = (float) ($row['gratuity_share_percentage'] ?? 0);
            if ($share <= 0 || $share > 100) {
                return 'Gratuity share for nominee #' . ($index + 1) . ' must be between 1 and 100.';
            }

            $totalShare += $share;
        }

        if (abs($totalShare - 100) > 0.01) {
            return 'Total gratuity share across all nominees must equal 100%.';
        }

        return null;
    }

    /** @param  array<string, mixed>  $row */
    protected static function normalizeNomineeRow(array $row): array
    {
        $out = self::emptyNomineeRow();
        foreach (self::requiredNomineeFields() as $key) {
            $out[$key] = trim((string) ($row[$key] ?? ''));
        }

        return $out;
    }

    /** @return array<string, string> */
    protected static function emptyNomineeRow(): array
    {
        return [
            'nominee_full_name' => '',
            'nominee_relationship' => '',
            'nominee_age' => '',
            'nominee_address' => '',
            'gratuity_share_percentage' => '',
        ];
    }

    /** @param  array<string, string>  $row */
    protected static function nomineeRowHasData(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }
}
