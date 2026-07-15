<?php

namespace App\Data;

class CompanyPolicyDocuments
{
    public static function list(): array
    {
        $path = base_path('form_join/company-policies.json');

        if (!is_file($path)) {
            return [];
        }

        $data = json_decode(file_get_contents($path), true) ?: [];

        return $data['documents'] ?? [];
    }

    public static function streamUrl(\App\Models\EmployeesNewJoiner $employee, array $doc): string
    {
        if (!empty($doc['key'])) {
            return route('onboarding.policy.file', [
                'token' => $employee->emp_url,
                'key' => $doc['key'],
            ]);
        }

        if (!empty($doc['url'])) {
            $url = (string) $doc['url'];

            return str_starts_with($url, 'http') ? $url : url($url);
        }

        return '#';
    }

    /** @deprecated Use streamUrl() with candidate token — direct links allow download. */
    public static function viewUrl(array $doc): string
    {
        if (!empty($doc['key'])) {
            return route('onboarding.policy.legacy', ['key' => $doc['key']]);
        }

        if (!empty($doc['url'])) {
            $url = (string) $doc['url'];

            return str_starts_with($url, 'http') ? $url : url($url);
        }

        return '#';
    }

    public static function filePath(array $doc): ?string
    {
        if (empty($doc['file'])) {
            return null;
        }

        $path = base_path((string) $doc['file']);

        return is_file($path) ? $path : null;
    }

    public static function candidateCanView(\App\Models\EmployeesNewJoiner $employee): bool
    {
        return in_array($employee->onboardingStep(), [
            'join_forms_sent',
            'join_forms_submitted',
            'policy_signed',
            'appointment_sent',
            'appointment_accepted',
            'appointment_rejected',
            'end',
        ], true);
    }
}
