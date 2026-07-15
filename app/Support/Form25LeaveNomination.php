<?php

namespace App\Support;

use App\Data\JoiningFormSchema;
use App\Models\EmployeesNewJoiner;

class Form25LeaveNomination
{
    public const SECTION_KEY = 'form_25_leave_nomination';

    public static function section(): ?array
    {
        return JoiningFormSchema::section(self::SECTION_KEY);
    }

    /** @return list<string> */
    public static function requiredTextFields(): array
    {
        return JoiningFormSchema::requiredFieldNames(self::section());
    }

    /** @return array<string, string> */
    public static function fieldLabels(): array
    {
        return JoiningFormSchema::fieldLabels(self::section());
    }

    /** @return array<string, string> */
    public static function defaults(EmployeesNewJoiner $employee, array $saved = [], array $joiningDetails = []): array
    {
        $info = OnboardingHrDisplay::profileInformation($employee);
        $basic = $info['basic_information'] ?? [];

        $nominationDate = trim((string) (
            $saved['nomination_date']
            ?? ($joiningDetails['confirmed_join_date'] ?? '')
            ?? optional($employee->emp_joining_date)->format('Y-m-d')
            ?? now()->toDateString()
        ));

        $defaults = [];
        foreach (JoiningFormSchema::fieldsFromSection(self::section() ?? []) as $field) {
            $name = (string) ($field['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $defaults[$name] = '';
        }

        $defaults['nomination_date'] = $nominationDate;
        $defaults['nomination_place'] = trim((string) (
            $basic['city'] ?? $employee->emp_location ?? ''
        ));

        foreach ($defaults as $key => $value) {
            if (array_key_exists($key, $saved) && (string) $saved[$key] !== '') {
                $defaults[$key] = (string) $saved[$key];
            }
        }

        return $defaults;
    }

    /** @param  array<string, mixed>  $data */
    public static function validate(array $data): ?string
    {
        $labels = self::fieldLabels();

        foreach (self::requiredTextFields() as $field) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                $label = $labels[$field] ?? str_replace('_', ' ', $field);

                return 'Form No. 25: please complete ' . $label . '.';
            }
        }

        return null;
    }
}
