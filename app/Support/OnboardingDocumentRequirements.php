<?php

namespace App\Support;

use App\Data\DocumentNamesList;
use App\Models\EmployeesNewJoiner;
use App\Models\NewEmployeesDocument;

/** Candidate document upload rules (env: all required vs optional partial submit). */
class OnboardingDocumentRequirements
{
    public static function allRequired(): bool
    {
        return filter_var(config('onboarding.all_documents_required', true), FILTER_VALIDATE_BOOLEAN);
    }

    /** @return list<string> */
    public static function employmentDocumentKeys(): array
    {
        return self::fresherExcludedKeys();
    }

    public static function isRequiredFor(EmployeesNewJoiner $employee, string $documentKey): bool
    {
        if (!self::allRequired()) {
            return false;
        }

        return in_array($documentKey, self::requiredKeys($employee), true);
    }

    /** @return list<string> */
    public static function fresherExcludedKeys(): array
    {
        return [
            'appointment_letter',
            'increment_letter',
            'salary_slips',
            'bank_statement',
        ];
    }

    /** @return list<string> */
    public static function requiredKeys(EmployeesNewJoiner $employee): array
    {
        $keys = DocumentNamesList::candidateUploadKeys();

        if ($employee->isFresher()) {
            $keys = array_values(array_diff($keys, self::fresherExcludedKeys()));
        }

        return $keys;
    }

    /** @return list<string> */
    public static function uploadedKeys(EmployeesNewJoiner $employee): array
    {
        $keys = NewEmployeesDocument::query()
            ->where('emp_id', $employee->id)
            ->whereNotIn('emp_select_document', DocumentNamesList::hrManagedKeys())
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNotNull('emp_document_file_path')
                        ->where('emp_document_file_path', '!=', '-');
                })->orWhere('emp_select_document', 'emergency_contact');
            })
            ->pluck('emp_select_document')
            ->all();

        if ($employee->emergency_contact && !in_array('emergency_contact', $keys, true)) {
            $keys[] = 'emergency_contact';
        }

        return array_values(array_unique($keys));
    }

    /** @return list<string> */
    public static function missingKeys(EmployeesNewJoiner $employee): array
    {
        if (!self::allRequired()) {
            return [];
        }

        return self::notUploadedKeys($employee);
    }

    /** Document types the candidate has not uploaded yet (for HR re-upload picker). */
    public static function notUploadedKeys(EmployeesNewJoiner $employee): array
    {
        return array_values(array_diff(self::requiredKeys($employee), self::uploadedKeys($employee)));
    }

    public static function canConfirmSubmit(EmployeesNewJoiner $employee): bool
    {
        if (OnboardingDocumentReedit::isAllowed($employee)) {
            $allowed = OnboardingDocumentReedit::allowedDocumentKeys($employee);
            $uploaded = self::uploadedKeys($employee);

            return count(array_intersect($allowed, $uploaded)) > 0;
        }

        if (self::allRequired()) {
            return self::missingKeys($employee) === [];
        }

        return count(self::uploadedKeys($employee)) > 0;
    }

    /** @return list<string> */
    public static function missingLabels(EmployeesNewJoiner $employee): array
    {
        $labels = OnboardingMediclaimDocuments::mergeIntoLabels(DocumentNamesList::collapsedLabels(), $employee);

        return array_values(array_map(
            fn (string $key) => $labels[$key] ?? $key,
            self::missingKeys($employee)
        ));
    }
}
