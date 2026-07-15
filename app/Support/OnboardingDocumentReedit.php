<?php

namespace App\Support;

use App\Data\DocumentNamesList;
use App\Models\EmployeesNewJoiner;
use App\Models\NewEmployeesDocument;

/** HR grants temporary permission for candidate to upload or replace documents. */
class OnboardingDocumentReedit
{
    /** @return array<string, string> */
    public static function documentOptions(?EmployeesNewJoiner $employee = null): array
    {
        $options = collect(DocumentNamesList::forCandidateUpload())->collapse()->toArray();
        if ($employee) {
            $options = array_merge($options, OnboardingMediclaimDocuments::documentLabels($employee));
        }

        return $options;
    }

    /** @return array<string, string> */
    public static function reasonOptions(): array
    {
        return [
            'hr_review_missing' => 'HR review — document missing or not uploaded',
            'hr_review_photo' => 'HR review — passport photo required or unclear',
            'hr_review_id_proof' => 'HR review — ID proof unclear or missing',
            'empv_salary_slip' => 'BGV — salary slip required (EMPV)',
            'empv_appointment_letter' => 'BGV — previous appointment letter required (EMPV)',
            'empv_employment_docs' => 'BGV — employment documents incomplete (EMPV)',
            'eduv_certificate' => 'BGV — education certificate required (EDUV)',
            'documents_mismatch' => 'Uploaded document unclear or does not match profile',
            'other' => 'Other (explain below)',
        ];
    }

    /** @return array<string, list<string>> */
    public static function reasonDocumentMap(): array
    {
        return [
            'hr_review_missing' => [],
            'hr_review_photo' => ['photo'],
            'hr_review_id_proof' => ['aadhaar_card', 'pan_card', 'passport', 'address_proof'],
            'empv_salary_slip' => ['salary_slips'],
            'empv_appointment_letter' => ['appointment_letter'],
            'empv_employment_docs' => ['salary_slips', 'appointment_letter', 'increment_letter'],
            'eduv_certificate' => ['highest_certificate', 'additional_certification'],
            'documents_mismatch' => [],
            'other' => [],
        ];
    }

    public static function isAllowed(EmployeesNewJoiner $employee): bool
    {
        return !empty(($employee->emp_other ?? [])['document_reedit']['allowed']);
    }

    public static function meta(EmployeesNewJoiner $employee): ?array
    {
        $row = ($employee->emp_other ?? [])['document_reedit'] ?? null;

        return is_array($row) && !empty($row['allowed']) ? $row : null;
    }

    /** @return list<string> */
    public static function allowedDocumentKeys(EmployeesNewJoiner $employee): array
    {
        $keys = self::meta($employee)['document_keys'] ?? [];

        return array_values(array_filter($keys, fn ($k) => isset(self::documentOptions($employee)[$k])));
    }

    public static function canUploadType(EmployeesNewJoiner $employee, string $documentType): bool
    {
        if (!self::isAllowed($employee)) {
            return false;
        }

        return in_array($documentType, self::allowedDocumentKeys($employee), true);
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

    /** @return list<string> */
    public static function bgvGaps(EmployeesNewJoiner $employee): array
    {
        $gaps = [];

        if ($employee->isFresher()) {
            return $gaps;
        }

        $employment = ($employee->emp_profile_data ?? [])['information']['previous_employment'] ?? [];
        $records = OnGrid::employmentRecords(is_array($employment) ? $employment : []);
        $record = OnGrid::empvEmploymentRecord(is_array($employment) ? $employment : []);
        if ($records === []) {
            $gaps[] = 'Previous employer details missing in profile (Info tab)';
        } elseif (!$record || empty(trim((string) ($record['employer_name'] ?? '')))) {
            $gaps[] = 'Previous employer name missing in profile';
        } elseif (!OnGrid::empvRecordHasRequiredDates($record)) {
            $gaps[] = 'HR must add last working date for previous employer before BGV (EMPV)';
        }

        $hasEmpPdf = self::hasUploadedPdf($employee->id, 'salary_slips')
            || self::hasUploadedPdf($employee->id, 'appointment_letter');
        if (!$hasEmpPdf) {
            $gaps[] = 'Salary slip or previous appointment letter PDF required for EMPV';
        }

        return $gaps;
    }

    /** Always show the HR panel until onboarding is finalized. */
    public static function hrShouldShowPanel(EmployeesNewJoiner $employee): bool
    {
        return !OnboardingArchive::isFinalized($employee);
    }

    public static function hrCanGrant(EmployeesNewJoiner $employee): bool
    {
        if (OnboardingArchive::isFinalized($employee) || self::isAllowed($employee)) {
            return false;
        }

        return $employee->isProfileComplete()
            || $employee->hasCandidateSubmittedDocuments()
            || NewEmployeesDocument::query()
                ->where('emp_id', $employee->id)
                ->whereNotIn('emp_select_document', DocumentNamesList::hrManagedKeys())
                ->exists();
    }

    public static function isPostOfferStage(EmployeesNewJoiner $employee): bool
    {
        if ($employee->emp_offer_letter_status === 'accept') {
            return true;
        }

        return in_array($employee->onboardingStep(), [
            'offer_accepted',
            'registration_sent',
            'registration_submitted',
            'registration_verified',
            'bgv_started',
            'bgv_completed',
            'join_forms_sent',
            'join_forms_submitted',
            'policy_signed',
        ], true);
    }

    /** Onboarding steps where BGV should no longer be offered. */
    public static function bgvFinishedSteps(): array
    {
        return [
            'bgv_completed',
            'join_forms_sent',
            'join_forms_submitted',
            'policy_signed',
            'appointment_sent',
            'appointment_accepted',
            'appointment_pending_sr_hr',
            'appointment_sr_rejected',
            'appointment_rejected',
            'end',
        ];
    }

    /** After HR approves all documents (post-offer), enable BGV again. */
    public static function shouldEnableBgvAfterDocumentApproval(EmployeesNewJoiner $employee): bool
    {
        if (!self::isPostOfferStage($employee)) {
            return false;
        }

        return !in_array($employee->onboardingStep(), self::bgvFinishedSteps(), true);
    }

    /** @return list<string> */
    public static function missingDocumentKeys(EmployeesNewJoiner $employee): array
    {
        return OnboardingDocumentRequirements::notUploadedKeys($employee);
    }

    public static function hrCanRevoke(EmployeesNewJoiner $employee): bool
    {
        return self::isAllowed($employee) && !OnboardingArchive::isFinalized($employee);
    }

    public static function isOpenForCandidate(EmployeesNewJoiner $employee): bool
    {
        return self::isAllowed($employee);
    }

    /**
     * @param  list<string>  $documentKeys
     */
    public static function grant(
        EmployeesNewJoiner $employee,
        string $reasonKey,
        array $documentKeys,
        ?string $reasonDetail = null
    ): void {
        $options = self::reasonOptions();
        if (!isset($options[$reasonKey])) {
            throw new \InvalidArgumentException('Invalid reason selected.');
        }

        $documentKeys = array_values(array_unique(array_filter($documentKeys)));
        $preset = self::reasonDocumentMap()[$reasonKey] ?? [];
        if ($preset !== []) {
            $documentKeys = $preset;
        }

        if ($documentKeys === [] && $reasonKey === 'hr_review_missing') {
            $documentKeys = self::missingDocumentKeys($employee);
        }

        if ($documentKeys === []) {
            throw new \InvalidArgumentException('Select at least one document the candidate may upload.');
        }

        foreach ($documentKeys as $key) {
            if (!isset(self::documentOptions($employee)[$key])) {
                throw new \InvalidArgumentException('Invalid document type selected.');
            }
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
        $other['document_reedit'] = [
            'allowed' => true,
            'allowed_at' => now()->toIso8601String(),
            'allowed_by' => auth()->user()?->name ?? auth()->user()?->email ?? 'HR',
            'reason_key' => $reasonKey,
            'reason_detail' => $detail ?: null,
            'reason' => $reasonText,
            'document_keys' => $documentKeys,
        ];
        $employee->emp_other = $other;
        $employee->emp_document_status = 'process';
        $employee->save();
        $employee->refresh();

        OnboardingMail::documentReeditGranted($employee, $reasonText, $documentKeys);
    }

    public static function revoke(EmployeesNewJoiner $employee): bool
    {
        if (!self::isAllowed($employee)) {
            return false;
        }

        $fresh = EmployeesNewJoiner::query()->find($employee->id);
        if (!$fresh) {
            return false;
        }

        $other = is_array($fresh->emp_other) ? $fresh->emp_other : [];
        unset($other['document_reedit']);

        $fresh->emp_other = $other;

        if ($fresh->emp_document_status === 'process' && $fresh->hasCandidateSubmittedDocuments()) {
            $fresh->emp_document_status = 'completed';
        }

        $saved = $fresh->save();
        $fresh->refresh();

        if ($employee->id === $fresh->id) {
            $employee->setRawAttributes($fresh->getAttributes());
            $employee->syncOriginal();
        }

        return $saved && !self::isAllowed($fresh);
    }

    protected static function hasUploadedPdf(int $empId, string $selectKey): bool
    {
        return NewEmployeesDocument::query()
            ->where('emp_id', $empId)
            ->where('emp_select_document', $selectKey)
            ->whereNotNull('emp_document_file_path')
            ->where('emp_document_file_path', '!=', '-')
            ->exists();
    }

    /** Candidate finished a HR-requested document re-upload — notify HR without undoing later onboarding steps. */
    public static function completeCandidateResubmit(EmployeesNewJoiner $employee): void
    {
        $other = is_array($employee->emp_other) ? $employee->emp_other : [];
        unset($other['document_reedit']);
        $other['document_reedit_resubmitted_at'] = now()->toIso8601String();
        $employee->emp_other = $other;
        $employee->save();

        $step = $employee->onboardingStep();
        $preOfferSteps = [
            'start',
            'profile_completed',
            'hr_review',
            'documents_submitted',
            'documents_approved',
            'documents_rejected',
        ];

        if (in_array($step, $preOfferSteps, true)) {
            $employee->setOnboardingStep('hr_review');
        } elseif (in_array($step, ['registration_sent', 'registration_submitted'], true)) {
            $employee->setOnboardingStep('registration_submitted');
        }

        $employee->refresh();
    }

    public static function wasRecentlyResubmitted(EmployeesNewJoiner $employee): bool
    {
        $at = ($employee->emp_other ?? [])['document_reedit_resubmitted_at'] ?? null;

        return $at && \Carbon\Carbon::parse($at)->gte(now()->subDays(30));
    }

    /** HR approved re-uploaded documents — candidate is ready for BGV again. */
    public static function markReadyForBgvAfterApproval(EmployeesNewJoiner $employee): void
    {
        $other = is_array($employee->emp_other) ? $employee->emp_other : [];
        $other['document_reedit_ready_for_bgv'] = true;
        $employee->emp_other = $other;
        $employee->save();
        $employee->setOnboardingStep('registration_verified');
        $employee->refresh();
    }

    public static function isReadyForBgv(EmployeesNewJoiner $employee): bool
    {
        return !empty(($employee->emp_other ?? [])['document_reedit_ready_for_bgv']);
    }

    public static function clearBgvReadyFlag(EmployeesNewJoiner $employee): void
    {
        $other = is_array($employee->emp_other) ? $employee->emp_other : [];
        unset($other['document_reedit_ready_for_bgv'], $other['document_reedit_resubmitted_at']);
        $employee->emp_other = $other;
        $employee->save();
    }

    /** HR may start (or retry) OnGrid BGV — post-offer, all documents approved, BGV not yet completed. */
    public static function canStartBgv(EmployeesNewJoiner $employee): bool
    {
        if (!self::isPostOfferStage($employee)) {
            return false;
        }

        if (($employee->emp_document_status ?? '') !== 'completed') {
            return false;
        }

        return !in_array($employee->onboardingStep(), self::bgvFinishedSteps(), true);
    }
}
