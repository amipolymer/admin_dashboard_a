<?php

namespace App\Support;

use App\Models\EmployeesNewJoiner;
use App\Models\NewEmployeesDocument;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * OnGrid BGV: API calls, profile mapping, initiate payload, PDF attachments.
 * Postman: "Onboarding and Initiate Verification" → POST .../individuals/initiate
 */
class OnGrid
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected string $communityId;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.ongrid.base_url'), '/');
        $this->username = (string) config('services.ongrid.username');
        $this->password = (string) config('services.ongrid.password');
        $this->communityId = (string) config('services.ongrid.community_id');
    }

    public static function make(): self
    {
        return new self();
    }

    /** True when all main credentials are set in .env (via config/services.php). */
    public static function isConfigured(): bool
    {
        foreach (['base_url', 'username', 'password', 'community_id'] as $key) {
            if (trim((string) config("services.ongrid.{$key}")) === '') {
                return false;
            }
        }

        return true;
    }

    /** Exact consentText required by OnGrid community config (character-for-character). */
    public static function configuredConsentText(): string
    {
        $text = trim((string) config('services.ongrid.consent_text', ''));
        if ($text === '') {
            throw new \RuntimeException(
                'OnGrid consent text is not configured. Set ONGRID_CONSENT_TEXT in .env to the exact text from your OnGrid community settings.'
            );
        }

        return $text;
    }

    /** @return list<string> Missing .env keys (human-readable). */
    public static function missingConfigKeys(): array
    {
        $map = [
            'base_url' => 'ONGRID_BASE_URL',
            'community_id' => 'ONGRID_COMMUNITY_ID',
            'username' => 'ONGRID_USERNAME (API client ID)',
            'password' => 'ONGRID_PASSWORD (API secret key)',
            'consent_text' => 'ONGRID_CONSENT_TEXT (must match OnGrid dashboard)',
        ];
        $missing = [];
        foreach ($map as $configKey => $envName) {
            if (trim((string) config("services.ongrid.{$configKey}")) === '') {
                $missing[] = $envName;
            }
        }

        return $missing;
    }

    protected function assertConfigured(): void
    {
        $missing = self::missingConfigKeys();
        if ($missing !== []) {
            throw new \RuntimeException(
                'OnGrid is not configured. Add to .env then run php artisan config:clear: ' . implode(', ', $missing)
            );
        }
    }

    // --- Offering list (HR modal) ---

    /** @return array<string, array<string, string>> */
    public static function offeringGroups(): array
    {
        return [
            'Address' => ['LAV' => 'Local Address Physical Verification', 'PAV' => 'Permanent Address Physical Verification'],
            'Identity' => ['PANV' => 'PAN Verification', 'VIDV' => 'Voter ID Verification', 'DLV' => 'Driver License Verification'],
            'Criminal' => ['CCRV' => 'Criminal Court Record Verification', 'PVLF' => 'Police Verification Via Law Firm', 'PCC' => 'Police Clearance Certificate', 'GDC' => 'Global Database Check'],
            'Education' => ['EDUV' => 'Education Verification'],
            'Employment' => ['EMPV' => 'Employment Verification', 'EREF' => 'eLockr Reference Check'],
            'Credit' => ['CC' => 'Credit Check'],
            'Other Check' => ['CVV' => 'Curriculum Vitae Validation'],
        ];
    }

    /** @return list<string> */
    public static function allOfferingCodes(): array
    {
        $codes = [];
        foreach (self::offeringGroups() as $items) {
            foreach ($items as $code => $label) {
                $codes[] = $code;
            }
        }

        return $codes;
    }

    public static function offeringLabel(string $code): string
    {
        foreach (self::offeringGroups() as $items) {
            if (isset($items[$code])) {
                return $items[$code];
            }
        }

        return $code;
    }

    /** @return array<string, mixed> Summary for HR BGV status panel (from DB cache + live verificationstatus). */
    public static function hrBgvSnapshot(EmployeesNewJoiner $employee): array
    {
        $response = is_array($employee->ongrid_response) ? $employee->ongrid_response : [];
        $verifications = [];
        foreach ($response['verifications'] ?? [] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $code = (string) ($row['code'] ?? '');
            if ($code === '') {
                continue;
            }
            $verifications[] = self::formatVerificationRow([
                'code' => $code,
                'label' => self::offeringLabel($code),
                'key' => $row['key'] ?? null,
                'request_id' => $row['requestId'] ?? null,
                'status' => $row['status'] ?? null,
                'state' => $row['state'] ?? null,
                'reason' => $row['reason'] ?? null,
                'result' => $row['result'] ?? null,
            ]);
        }

        if ($employee->ongrid_id && self::isConfigured()) {
            try {
                $live = self::make()->getVerificationStatus($employee->ongrid_id);
                $verifications = self::mergeLiveStatusIntoVerifications(
                    $verifications,
                    self::parseVerificationStatusPayload($live)
                );
            } catch (\Throwable $e) {
                Log::debug('OnGrid hrBgvSnapshot live status skipped', [
                    'employee_id' => $employee->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return [
            'individual_id' => $employee->ongrid_id,
            'initiated_at' => $response['initiated_at'] ?? null,
            'verification_codes' => $response['verification_codes'] ?? [],
            'skipped_offerings' => $response['skipped_offerings'] ?? [],
            'verifications' => $verifications,
        ];
    }

    /** @return list<array<string, mixed>> Rows from GET /individual/{id}/verificationstatus (*States arrays). */
    public static function parseVerificationStatusPayload(mixed $payload): array
    {
        if (!is_array($payload)) {
            return [];
        }

        $rows = [];
        foreach ($payload as $key => $value) {
            if (!is_string($key) || !str_ends_with($key, 'States') || !is_array($value)) {
                continue;
            }

            $code = strtoupper(substr($key, 0, -6));
            if ($code === '') {
                continue;
            }

            foreach ($value as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $status = isset($item['status']) ? (string) $item['status'] : null;
                $state = isset($item['state']) ? (string) $item['state'] : null;
                $result = array_key_exists('result', $item) && $item['result'] !== null
                    ? (string) $item['result']
                    : null;
                $reason = array_key_exists('reason', $item) && $item['reason'] !== null
                    ? (string) $item['reason']
                    : null;

                $rows[] = self::formatVerificationRow([
                    'code' => $code,
                    'label' => self::offeringLabel($code),
                    'request_id' => $item['requestId'] ?? null,
                    'status' => $status !== '' ? $status : null,
                    'state' => $state !== '' ? $state : null,
                    'result' => $result !== '' ? $result : null,
                    'reason' => $reason !== '' ? $reason : null,
                ]);
            }
        }

        return $rows;
    }

    /** @return list<array<string, mixed>> */
    public static function normalizeVerificationStatusRows(mixed $payload): array
    {
        $fromStates = self::parseVerificationStatusPayload($payload);
        if ($fromStates !== []) {
            return $fromStates;
        }

        if (!is_array($payload)) {
            return [];
        }

        $list = $payload['verifications'] ?? $payload['verificationStatuses'] ?? $payload;
        if (!is_array($list)) {
            return [];
        }

        $rows = [];
        foreach ($list as $row) {
            if (!is_array($row)) {
                continue;
            }
            $code = (string) ($row['code'] ?? $row['offeringCode'] ?? $row['verificationCode'] ?? '');
            if ($code === '') {
                continue;
            }

            $rows[] = self::formatVerificationRow([
                'code' => $code,
                'label' => self::offeringLabel($code),
                'request_id' => $row['requestId'] ?? $row['id'] ?? null,
                'key' => $row['key'] ?? null,
                'status' => isset($row['status']) ? (string) $row['status'] : null,
                'state' => isset($row['state']) ? (string) $row['state'] : null,
                'reason' => isset($row['reason']) ? (string) $row['reason'] : null,
                'result' => isset($row['result']) ? (string) $row['result'] : null,
            ]);
        }

        return $rows;
    }

    public static function isVerificationStatusSuccess(?string $status, ?string $state, ?string $result, ?string $reason): bool
    {
        $statusNorm = strtolower(trim((string) ($status ?: $state ?: '')));
        $resultNorm = strtolower(trim((string) ($result ?? '')));

        if (in_array($statusNorm, ['failed', 'failure', 'rejected', 'insufficient'], true)) {
            return false;
        }
        if (in_array($resultNorm, ['failed', 'failure', 'rejected'], true)) {
            return false;
        }
        if ($reason !== null && trim($reason) !== '' && in_array($statusNorm, ['failed', 'completed'], true)) {
            return false;
        }

        return in_array($statusNorm, ['closed', 'completed', 'success', 'verified', 'clear'], true)
            || in_array($resultNorm, ['success', 'verified', 'clear'], true);
    }

    /** True when a check is already running or finished — re-request would create a duplicate. */
    public static function isVerificationBlockingRerequest(?string $status, ?string $state, ?string $result, ?string $reason): bool
    {
        $statusNorm = strtolower(trim((string) ($status ?: $state ?: '')));
        $resultNorm = strtolower(trim((string) ($result ?? '')));

        if (in_array($statusNorm, ['failed', 'failure', 'rejected', 'insufficient', 'cancelled', 'canceled'], true)) {
            return false;
        }
        if (in_array($resultNorm, ['failed', 'failure', 'rejected'], true)) {
            return false;
        }

        // Success, in-progress, empty, or unknown — do not create another request.
        return true;
    }

    /**
     * Codes that must not be re-requested (already OK or in progress).
     * Maps OnGrid AV/UIDV aliases so Aadhaar is not restarted via `uid`.
     *
     * @param  list<array<string, mixed>>  $liveRows
     * @return array<string, true>
     */
    public static function blockingVerificationCodes(array $liveRows): array
    {
        $blocking = [];
        foreach ($liveRows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $code = strtoupper((string) ($row['code'] ?? ''));
            if ($code === '') {
                continue;
            }
            if (!self::isVerificationBlockingRerequest(
                $row['status'] ?? null,
                $row['state'] ?? null,
                $row['result'] ?? null,
                $row['reason'] ?? null
            )) {
                continue;
            }
            $blocking[$code] = true;
            if (in_array($code, ['AV', 'UIDV', 'UID'], true)) {
                $blocking['AV'] = true;
                $blocking['UIDV'] = true;
                $blocking['UID'] = true;
            }
        }

        return $blocking;
    }

    /** @return array{blocking: array<string, true>, live_rows: list<array<string, mixed>>} */
    public static function liveBlockingContext(EmployeesNewJoiner $employee): array
    {
        $rows = [];
        if ($employee->ongrid_id && self::isConfigured()) {
            try {
                $live = self::make()->getVerificationStatus($employee->ongrid_id);
                $rows = self::parseVerificationStatusPayload($live);
            } catch (\Throwable $e) {
                Log::debug('OnGrid liveBlockingContext skipped', [
                    'employee_id' => $employee->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        if ($rows === []) {
            foreach ((is_array($employee->ongrid_response) ? ($employee->ongrid_response['verifications'] ?? []) : []) as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $code = (string) ($row['code'] ?? '');
                if ($code === '') {
                    continue;
                }
                $rows[] = self::formatVerificationRow([
                    'code' => $code,
                    'request_id' => $row['requestId'] ?? null,
                    'status' => $row['status'] ?? null,
                    'state' => $row['state'] ?? null,
                    'result' => $row['result'] ?? null,
                    'reason' => $row['reason'] ?? null,
                ]);
            }
        }

        return [
            'blocking' => self::blockingVerificationCodes($rows),
            'live_rows' => $rows,
        ];
    }

    /** @return list<string> Offering codes already active/OK on OnGrid (for BGV modal). */
    public static function blockingOfferingCodesForEmployee(EmployeesNewJoiner $employee): array
    {
        if (!$employee->ongrid_id) {
            return [];
        }

        $blocking = self::liveBlockingContext($employee)['blocking'];
        $offerings = array_flip(self::allOfferingCodes());

        return array_values(array_filter(array_keys($blocking), fn (string $c) => isset($offerings[$c])));
    }

    /** @return array<string, int> */
    public static function storedDocumentIds(EmployeesNewJoiner $employee): array
    {
        $ids = is_array($employee->ongrid_response)
            ? ($employee->ongrid_response['document_ids'] ?? [])
            : [];
        if (!is_array($ids)) {
            return [];
        }

        $out = [];
        foreach ($ids as $type => $id) {
            $id = (int) $id;
            if ($id > 0 && is_string($type) && $type !== '') {
                $out[$type] = $id;
            }
        }

        return $out;
    }

    /** @param  array<string, mixed>  $individualPayload */
    public static function documentIdsFromIndividual(array $individualPayload): array
    {
        $documents = $individualPayload['individual']['documents']
            ?? $individualPayload['documents']
            ?? [];
        if (!is_array($documents)) {
            return [];
        }

        $out = [];
        foreach ($documents as $doc) {
            if (!is_array($doc)) {
                continue;
            }
            $type = (string) ($doc['documentType'] ?? '');
            $id = (int) ($doc['id'] ?? 0);
            if ($type !== '' && $id > 0 && !isset($out[$type])) {
                $out[$type] = $id;
            }
        }

        return $out;
    }

    /** @param  array<string, true>  $blockingCodes */
    public static function individualHasAadhaarVerification(EmployeesNewJoiner $employee, array $blockingCodes = []): bool
    {
        if (isset($blockingCodes['AV']) || isset($blockingCodes['UIDV']) || isset($blockingCodes['UID'])) {
            return true;
        }

        $response = is_array($employee->ongrid_response) ? $employee->ongrid_response : [];
        if (!empty($response['individual']['uidvRequestId'])) {
            return true;
        }

        return false;
    }

    public static function verificationStatusDisplay(?string $status, ?string $state, ?string $result, ?string $reason): string
    {
        if (self::isVerificationStatusSuccess($status, $state, $result, $reason)) {
            return 'OK';
        }

        $label = trim((string) ($status ?: $state ?: ''));

        return $label !== '' ? $label : '—';
    }

    /** @param  array<string, mixed>  $row */
    protected static function formatVerificationRow(array $row): array
    {
        $status = $row['status'] ?? null;
        $state = $row['state'] ?? null;
        $result = $row['result'] ?? null;
        $reason = $row['reason'] ?? null;

        return [
            'code' => $row['code'],
            'label' => $row['label'] ?? self::offeringLabel((string) $row['code']),
            'key' => $row['key'] ?? null,
            'request_id' => $row['request_id'] ?? null,
            'status' => $status,
            'state' => $state,
            'result' => $result,
            'reason' => $reason,
            'is_success' => self::isVerificationStatusSuccess($status, $state, $result, $reason),
            'display' => self::verificationStatusDisplay($status, $state, $result, $reason),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $verifications
     * @param  list<array<string, mixed>>  $liveRows
     * @return list<array<string, mixed>>
     */
    protected static function mergeLiveStatusIntoVerifications(array $verifications, array $liveRows): array
    {
        if ($liveRows === []) {
            return $verifications;
        }

        $byRequestId = [];
        foreach ($liveRows as $row) {
            if (($row['request_id'] ?? null) !== null) {
                $byRequestId[(string) $row['request_id']] = $row;
            }
        }

        $merged = [];
        $usedRequestIds = [];

        foreach ($verifications as $verification) {
            $requestId = $verification['request_id'] ?? null;
            if ($requestId !== null && isset($byRequestId[(string) $requestId])) {
                $live = $byRequestId[(string) $requestId];
                $usedRequestIds[(string) $requestId] = true;
                $merged[] = self::formatVerificationRow(array_merge($verification, [
                    'status' => $live['status'] ?? $verification['status'] ?? null,
                    'state' => $live['state'] ?? $verification['state'] ?? null,
                    'result' => $live['result'] ?? $verification['result'] ?? null,
                    'reason' => $live['reason'] ?? $verification['reason'] ?? null,
                ]));
            } else {
                $merged[] = $verification;
            }
        }

        foreach ($liveRows as $row) {
            $requestId = $row['request_id'] ?? null;
            if ($requestId !== null && isset($usedRequestIds[(string) $requestId])) {
                continue;
            }
            $merged[] = $row;
        }

        return $merged;
    }

    /** Checks HR can reasonably start without missing portal documents. */
    public static function defaultOfferingCodes(): array
    {
        return ['CCRV', 'LAV', 'PAV', 'GDC', 'EMPV', 'EDUV'];
    }

    /**
     * @param  list<string>  $requestedCodes
     * @return array{codes: list<string>, skipped: array<string, string>}
     */
    public static function filterOfferingCodes(EmployeesNewJoiner $employee, array $requestedCodes): array
    {
        $codes = array_values(array_unique(array_filter($requestedCodes)));
        if ($codes === []) {
            $codes = self::defaultOfferingCodes();
        }

        $basic = ($employee->emp_profile_data ?? [])['information']['basic_information'] ?? [];
        $included = [];
        $skipped = [];

        foreach ($codes as $code) {
            $reason = self::offeringSkipReason($employee, $basic, $code);
            if ($reason !== null) {
                $skipped[$code] = $reason;
                continue;
            }
            $included[] = $code;
        }

        return ['codes' => $included, 'skipped' => $skipped];
    }

    protected static function offeringSkipReason(EmployeesNewJoiner $employee, array $basic, string $code): ?string
    {
        return self::offeringSelectionError($employee, $code);
    }

    /**
     * User-facing reason a selected offering cannot start (missing file or profile data).
     */
    public static function offeringSelectionError(EmployeesNewJoiner $employee, string $code, ?string $empvStartDate = null): ?string
    {
        return match ($code) {
            'CVV' => self::cvvOfferingError($employee),
            'PANV' => self::panvOfferingError($employee),
            'DLV', 'VIDV' => self::offeringLabel($code) . ': not collected in candidate portal yet — uncheck this option or add the document.',
            'CC', 'EREF' => self::offeringLabel($code) . ': not wired to portal data yet — uncheck this option.',
            'PCC' => self::pccOfferingError($employee),
            'EMPV' => self::empvOfferingError($employee, $empvStartDate),
            'EDUV' => self::eduvOfferingError($employee),
            default => null,
        };
    }

    /**
     * @param  list<string>  $requestedCodes
     * @return list<string>
     */
    public static function validateSelectedOfferings(EmployeesNewJoiner $employee, array $requestedCodes, ?string $empvStartDate = null): array
    {
        $codes = array_values(array_unique(array_filter($requestedCodes)));
        if ($codes === []) {
            $codes = self::defaultOfferingCodes();
        }

        $errors = [];
        foreach ($codes as $code) {
            if ($code === 'EMPV' && self::isEmpvScheduledForFuture($employee, $empvStartDate)) {
                continue;
            }
            $message = self::offeringSelectionError($employee, $code, $empvStartDate);
            if ($message !== null) {
                $errors[] = $message;
            }
        }

        return $errors;
    }

    /** @param  list<string>  $requestedCodes */
    public static function assertSelectedOfferingsReady(EmployeesNewJoiner $employee, array $requestedCodes, ?string $empvStartDate = null): void
    {
        $errors = self::validateSelectedOfferings($employee, $requestedCodes, $empvStartDate);
        if ($errors === []) {
            return;
        }

        throw new \RuntimeException(
            'Cannot start BGV — fix the following for your selected checks: ' . implode(' ', $errors)
        );
    }

    protected static function cvvOfferingError(EmployeesNewJoiner $employee): ?string
    {
        if (self::cvPdfRow($employee) !== null) {
            return null;
        }

        return self::offeringLabel('CVV') . ': CV file not uploaded — please upload file.';
    }

    protected static function panvOfferingError(EmployeesNewJoiner $employee): ?string
    {
        $label = self::offeringLabel('PANV');
        $basic = ($employee->emp_profile_data ?? [])['information']['basic_information'] ?? [];
        $pan = self::panNumberFromBasic($basic);

        if ($pan === '') {
            return "{$label}: PAN number not filled in candidate Info tab.";
        }
        if (!preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $pan)) {
            return "{$label}: PAN number in Info tab is invalid.";
        }
        if (self::findUploadedPdf($employee->id, 'pan_card') === null) {
            return "{$label}: PAN Card file not uploaded — please upload file in Documents tab.";
        }
        if (self::panIdentityDocument($employee, $basic, self::deduplicationId($employee)) === null) {
            return "{$label}: PAN Card file is missing or unreadable — please re-upload file.";
        }

        return null;
    }

    protected static function pccOfferingError(EmployeesNewJoiner $employee): ?string
    {
        if (self::findUploadedPdf($employee->id, 'photo') !== null) {
            return null;
        }

        return self::offeringLabel('PCC') . ': passport size photo not uploaded — please upload file in Documents tab.';
    }

    protected static function empvOfferingError(EmployeesNewJoiner $employee, ?string $empvStartDate = null): ?string
    {
        $label = self::offeringLabel('EMPV');
        if ($employee->isFresher()) {
            return "{$label}: not applicable for freshers — uncheck this option.";
        }

        $employment = ($employee->emp_profile_data ?? [])['information']['previous_employment'] ?? [];
        $record = self::empvEmploymentRecord(is_array($employment) ? $employment : []);
        if (!$record || trim((string) ($record['employer_name'] ?? '')) === '') {
            return "{$label}: previous employer details required in candidate profile.";
        }

        $dates = self::empvResolvableDates($employee, $record, $empvStartDate);
        if ($dates['joining'] === null) {
            return "{$label}: candidate joining date at previous employer required in profile (Info tab).";
        }
        if ($dates['last_working'] === null) {
            return "{$label}: select Employment verification start date in BGV modal (BGV runs from this date at the prior employer).";
        }

        foreach (['salary_slips', 'appointment_letter', 'increment_letter'] as $key) {
            if (self::findUploadedPdf($employee->id, $key) !== null) {
                return null;
            }
        }

        return "{$label}: salary slip, appointment letter, or increment letter file not uploaded — please upload file in Documents tab.";
    }

    protected static function eduvOfferingError(EmployeesNewJoiner $employee): ?string
    {
        $label = self::offeringLabel('EDUV');
        $educationRows = ($employee->emp_profile_data ?? [])['information']['education_qualification'] ?? [];
        $rows = is_array($educationRows) && isset($educationRows[0]) ? $educationRows : [$educationRows];
        $completeRows = 0;
        $missingCertificate = false;

        $i = 0;
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $institute = trim((string) ($row['name_of_institute'] ?? $row['institute'] ?? ''));
            $degree = trim((string) ($row['degree'] ?? ''));
            $year = trim((string) ($row['year_of_passing'] ?? ''));
            if ($institute === '' || $degree === '' || $year === '') {
                continue;
            }
            $completeRows++;
            $i++;
            $docKey = $i === 1 ? 'highest_certificate' : 'additional_certification';
            if (self::findUploadedPdf($employee->id, $docKey) === null
                && self::findUploadedPdf($employee->id, 'highest_certificate') === null) {
                $missingCertificate = true;
            }
        }

        if ($completeRows === 0) {
            return "{$label}: education details (institute, degree, year) missing in candidate profile.";
        }
        if ($missingCertificate) {
            return "{$label}: education certificate file not uploaded — please upload Highest Qualification in Documents tab.";
        }

        return null;
    }

    protected static function canBuildEmpv(EmployeesNewJoiner $employee): bool
    {
        if ($employee->isFresher()) {
            return false;
        }
        $employment = ($employee->emp_profile_data ?? [])['information']['previous_employment'] ?? [];
        $record = self::empvEmploymentRecord($employment);
        if (!$record || !self::empvRecordHasRequiredDates($record)) {
            return false;
        }
        foreach (['salary_slips', 'appointment_letter', 'increment_letter'] as $key) {
            if (self::findUploadedPdf($employee->id, $key) !== null) {
                return true;
            }
        }

        return false;
    }

    protected static function canBuildEduv(EmployeesNewJoiner $employee): bool
    {
        $educationRows = ($employee->emp_profile_data ?? [])['information']['education_qualification'] ?? [];
        $rows = is_array($educationRows) && isset($educationRows[0]) ? $educationRows : [$educationRows];
        $i = 0;
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $institute = trim((string) ($row['name_of_institute'] ?? $row['institute'] ?? ''));
            $degree = trim((string) ($row['degree'] ?? ''));
            $year = trim((string) ($row['year_of_passing'] ?? ''));
            if ($institute === '' || $degree === '' || $year === '') {
                continue;
            }
            $i++;
            $docKey = $i === 1 ? 'highest_certificate' : 'additional_certification';
            if (self::findUploadedPdf($employee->id, $docKey) === null
                && self::findUploadedPdf($employee->id, 'highest_certificate') === null) {
                return false;
            }
        }

        return $i > 0;
    }

    // --- API ---

    /**
     * Onboard on OnGrid, start verifications, and optionally run CVV (upload CV then POST /cvv).
     * When ongrid_id already exists: update documents, skip checks already running/completed.
     *
     * @param  list<string>  $requestedCodes
     * @return array<string, mixed>
     */
    public function initiateBgv(EmployeesNewJoiner $employee, array $requestedCodes, ?string $empvStartDate = null): array
    {
        $empvDeferred = false;
        $empvSkipReason = null;
        $empvScheduledDate = null;

        if (in_array('EMPV', $requestedCodes, true)) {
            $resolvedEmpvDate = self::resolveEmpvStartDate($employee, $empvStartDate);
            if ($resolvedEmpvDate === null) {
                throw new \RuntimeException(
                    'Employment Verification requires the employment verification start date in the BGV modal.'
                );
            }

            $prepared = self::prepareEmpvForBgv($employee, $resolvedEmpvDate);
            $employee->refresh();
            $empvStartDate = $prepared['formatted'];

            if ($prepared['defer']) {
                $empvDeferred = true;
                $empvScheduledDate = $prepared['formatted'];
                $empvSkipReason = $prepared['skip_reason'];
                $requestedCodes = array_values(array_filter(
                    $requestedCodes,
                    fn (string $c) => $c !== 'EMPV'
                ));
            }
        }

        $filtered = self::filterOfferingCodes($employee, $requestedCodes);
        if ($empvDeferred && $empvSkipReason !== null) {
            $filtered['skipped']['EMPV'] = $empvSkipReason;
        }
        $codes = $filtered['codes'];

        $individualId = (int) ($employee->ongrid_id ?: 0);
        $isUpdate = $individualId > 0;
        $documentIds = self::storedDocumentIds($employee);
        $blocking = [];
        $existingIndividual = null;

        if ($isUpdate) {
            try {
                $existingIndividual = $this->getIndividual($individualId);
                $documentIds = array_merge($documentIds, self::documentIdsFromIndividual($existingIndividual));
            } catch (\Throwable $e) {
                Log::warning('OnGrid get individual before re-initiate failed', [
                    'employee_id' => $employee->id,
                    'individual_id' => $individualId,
                    'message' => $e->getMessage(),
                ]);
            }

            $blocking = self::liveBlockingContext($employee)['blocking'];
            foreach ($codes as $code) {
                if (!isset($blocking[$code])) {
                    continue;
                }
                $filtered['skipped'][$code] = 'Already started/completed on OnGrid — not re-requested (existing check kept).';
            }
            $codes = array_values(array_filter($codes, fn (string $c) => !isset($blocking[$c])));
        }

        $wantsCvv = in_array('CVV', $codes, true);
        $initiateCodes = array_values(array_filter($codes, fn (string $c) => $c !== 'CVV'));

        $omitUid = $isUpdate && self::individualHasAadhaarVerification($employee, $blocking);
        $payloadOptions = [
            'omit_uid' => $omitUid,
            'omit_aadhaar_document' => $isUpdate,
            'omit_pan_nested_file' => $isUpdate,
            'omit_photo_document' => $isUpdate && isset($documentIds['ProfileImage']),
        ];

        $needsDocumentUpdate = $isUpdate && self::findUploadedPdf($employee->id, 'pan_card') !== null;
        $canProfileOnlyUpdate = $isUpdate && ($needsDocumentUpdate || $wantsCvv || $initiateCodes !== []);

        if ($initiateCodes === [] && !$wantsCvv && !$canProfileOnlyUpdate) {
            if ($empvDeferred && $empvScheduledDate !== null) {
                throw new \RuntimeException(
                    'Employment Verification is scheduled for '
                    . Carbon::parse($empvScheduledDate)->format('d M Y')
                    . ' and cannot start before that date. Select at least one other check to start BGV now.'
                );
            }

            if ($isUpdate && $filtered['skipped'] !== []) {
                throw new \RuntimeException(
                    'No new OnGrid checks to start — selected checks are already running or completed. '
                    . self::formatSkippedOfferings($filtered['skipped'])
                );
            }

            throw new \RuntimeException(
                'No OnGrid verifications can be started. '
                . self::formatSkippedOfferings($filtered['skipped'])
            );
        }

        $payload = self::buildInitiatePayload($employee, $initiateCodes, $payloadOptions);
        self::sanitizeProfilePayload($payload, $employee, $payloadOptions);

        $response = $this->initiateVerification($payload);
        $individualId = (int) ($response['individual']['id'] ?? $individualId);

        // PAN: always multipart Add/Update — initiate Base64 does not attach files (and must not re-Add on update).
        if ($individualId > 0 && self::findUploadedPdf($employee->id, 'pan_card') !== null) {
            $existingPanId = $documentIds['PANCard']
                ?? self::findDocumentIdByType($response, 'PANCard')
                ?? ($existingIndividual ? self::findDocumentIdByType($existingIndividual, 'PANCard') : null);
            $documentIds['PANCard'] = $this->uploadPanDocument($individualId, $employee, $existingPanId);
            try {
                $refreshed = $this->getIndividual($individualId);
                if (isset($refreshed['individual']) && is_array($refreshed['individual'])) {
                    $response['individual'] = $refreshed['individual'];
                } elseif (isset($refreshed['id'])) {
                    $response['individual'] = $refreshed;
                }
                $documentIds = array_merge($documentIds, self::documentIdsFromIndividual($response));
            } catch (\Throwable $e) {
                Log::warning('OnGrid refresh after PAN upload failed', [
                    'employee_id' => $employee->id,
                    'individual_id' => $individualId,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        if ($wantsCvv) {
            if ($individualId <= 0) {
                throw new \RuntimeException('OnGrid did not return an individual ID; cannot start CVV.');
            }
            $deduplicationId = self::deduplicationId($employee);
            $existingCvId = $documentIds['CurriculumVitae']
                ?? self::findDocumentIdByType($response, 'CurriculumVitae')
                ?? ($existingIndividual ? self::findDocumentIdByType($existingIndividual, 'CurriculumVitae') : null);
            $cvDocumentId = $this->uploadCvDocument($individualId, $employee, $existingCvId);
            $documentIds['CurriculumVitae'] = $cvDocumentId;
            $cvvResult = $this->requestCvvVerification(
                $individualId,
                $deduplicationId,
                $cvDocumentId,
                self::verificationDocumentUid('CVV', $employee)
            );
            $response['verifications'] = array_values(array_merge(
                $response['verifications'] ?? [],
                [['code' => 'CVV', 'key' => $deduplicationId, 'requestId' => $cvvResult['requestId'] ?? null, 'state' => $cvvResult['state'] ?? null]]
            ));
        }

        if ($filtered['skipped'] !== []) {
            $response['skipped_offerings'] = $filtered['skipped'];
        }
        if ($empvDeferred && $empvScheduledDate !== null) {
            $response['empv_scheduled_start'] = $empvScheduledDate;
        }
        $response['document_ids'] = $documentIds;

        return $response;
    }

    public function uploadCvDocument(int $individualId, EmployeesNewJoiner $employee, ?int $existingDocumentId = null): int
    {
        $row = self::cvPdfRow($employee);
        if (!$row) {
            throw new \RuntimeException('CV PDF not found for this joiner.');
        }
        $full = self::resolveDocumentFullPath($row, $employee);
        if ($full === null || !self::isPdfFile($full)) {
            throw new \RuntimeException('CV PDF file is missing on disk or is not a PDF.');
        }

        $binary = file_get_contents($full);
        if ($binary === false || $binary === '') {
            throw new \RuntimeException('Could not read CV PDF from storage.');
        }

        $fileName = $row->emp_document_file ?: basename($full);
        $basic = ($employee->emp_profile_data ?? [])['information']['basic_information'] ?? [];
        $fields = ['nameAsPerDocument' => $basic['name'] ?? $employee->emp_name];

        if ($existingDocumentId && $existingDocumentId > 0) {
            $updateUrl = "{$this->baseUrl}/individual/{$individualId}/doc/cv/{$existingDocumentId}";
            $response = Http::withBasicAuth($this->username, $this->password)
                ->attach('file', $binary, $fileName)
                ->post($updateUrl, $fields);
            if ($response->successful()) {
                $cvDocumentId = (int) ($response->json('id') ?? $existingDocumentId);

                return $cvDocumentId > 0 ? $cvDocumentId : $existingDocumentId;
            }
            // Only Add when Update is clearly missing (404). Other errors must not create a second CV.
            if ($response->status() !== 404) {
                Log::warning('OnGrid CV update failed; keeping existing document id (no Add)', [
                    'individual_id' => $individualId,
                    'document_id' => $existingDocumentId,
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                ]);

                return $existingDocumentId;
            }
            Log::warning('OnGrid CV update returned 404; falling back to Add', [
                'individual_id' => $individualId,
                'document_id' => $existingDocumentId,
            ]);
        }

        $url = "{$this->baseUrl}/individual/{$individualId}/doc/cv";
        $response = Http::withBasicAuth($this->username, $this->password)
            ->attach('file', $binary, $fileName)
            ->post($url, $fields);
        $this->failIfError($response, 'OnGrid CV upload failed');

        $cvDocumentId = (int) ($response->json('id') ?? 0);
        if ($cvDocumentId <= 0) {
            throw new \RuntimeException('OnGrid CV upload did not return a document id.');
        }

        return $cvDocumentId;
    }

    /**
     * Multipart PAN upload — initiate Base64 documents[] does not attach PAN files (files stay null).
     * Add: POST /individual/{id}/doc/pan — Update: POST /individual/{id}/doc/pan/{documentId}
     */
    public function uploadPanDocument(int $individualId, EmployeesNewJoiner $employee, ?int $existingDocumentId = null): int
    {
        $row = self::findUploadedPdf($employee->id, 'pan_card');
        if (!$row) {
            throw new \RuntimeException('PAN Card PDF not found for this joiner.');
        }
        $full = self::resolveDocumentFullPath($row, $employee);
        if ($full === null || !self::isPdfFile($full)) {
            throw new \RuntimeException('PAN Card PDF file is missing on disk or is not a PDF.');
        }

        $binary = file_get_contents($full);
        if ($binary === false || $binary === '') {
            throw new \RuntimeException('Could not read PAN Card PDF from storage.');
        }

        $basic = ($employee->emp_profile_data ?? [])['information']['basic_information'] ?? [];
        $pan = self::panNumberFromBasic($basic);
        if ($pan === '') {
            throw new \RuntimeException('PAN number is required in the candidate Info tab before uploading PAN to OnGrid.');
        }

        $fileName = $row->emp_document_file ?: basename($full);
        $fields = array_filter([
            'documentUID' => $pan,
            'nameAsPerDocument' => $basic['name'] ?? $employee->emp_name,
            'legalGuardianName' => $basic['fathers_name'] ?? '',
            'dob' => !empty($basic['dob']) ? Carbon::parse($basic['dob'])->format('Y-m-d') : '',
        ], fn ($v) => $v !== null && $v !== '');

        $url = $existingDocumentId && $existingDocumentId > 0
            ? "{$this->baseUrl}/individual/{$individualId}/doc/pan/{$existingDocumentId}"
            : "{$this->baseUrl}/individual/{$individualId}/doc/pan";

        $response = Http::withBasicAuth($this->username, $this->password)
            ->attach('file', $binary, $fileName)
            ->post($url, $fields);
        $this->failIfError($response, 'OnGrid PAN document upload failed');

        $panDocumentId = (int) ($response->json('id') ?? $existingDocumentId ?? 0);
        if ($panDocumentId <= 0) {
            throw new \RuntimeException('OnGrid PAN upload did not return a document id.');
        }

        return $panDocumentId;
    }

    /** @param  array<string, mixed>  $response */
    protected static function findDocumentIdByType(array $response, string $documentType): ?int
    {
        $documents = $response['individual']['documents']
            ?? $response['documents']
            ?? [];
        if (!is_array($documents)) {
            return null;
        }

        foreach ($documents as $doc) {
            if (!is_array($doc)) {
                continue;
            }
            if (($doc['documentType'] ?? '') !== $documentType) {
                continue;
            }
            $id = (int) ($doc['id'] ?? 0);

            return $id > 0 ? $id : null;
        }

        return null;
    }

    public function requestCvvVerification(int $individualId, string $key, int $cvDocumentId, ?string $documentUid = null): array
    {
        $url = "{$this->baseUrl}/individual/{$individualId}/cvv";
        $body = array_filter([
            'key' => $key,
            'cvDocumentId' => $cvDocumentId,
            'documentUID' => $documentUid,
        ], fn ($v) => $v !== null && $v !== '');
        $response = $this->http()->post($url, $body);
        $this->failIfError($response, 'OnGrid CVV request failed');

        return $response->json() ?? [];
    }

    public function initiateVerification(array $payload): array
    {
        $this->assertConfigured();
        $url = "{$this->baseUrl}/community/{$this->communityId}/individuals/initiate";
        $response = $this->http()->post($url, $payload);
        $this->failIfError($response, 'OnGrid initiate verification failed');

        return $response->json() ?? [];
    }

    public function getIndividual(int|string $individualId): array
    {
        $this->assertConfigured();
        $url = "{$this->baseUrl}/individual/{$individualId}";
        $response = $this->http()->get($url);
        $this->failIfError($response, 'OnGrid get individual failed');

        return $response->json() ?? [];
    }

    public function getVerificationStatus(int|string $individualId, bool $includeReport = false): array
    {
        $this->assertConfigured();
        $url = "{$this->baseUrl}/individual/{$individualId}/verificationstatus";
        $response = $this->http()->get($url, ['includeReport' => $includeReport ? 'true' : 'false']);
        $this->failIfError($response, 'OnGrid verification status failed');

        return $response->json() ?? [];
    }

    public function listIndividuals(): array
    {
        $this->assertConfigured();
        $url = "{$this->baseUrl}/community/{$this->communityId}/individuals";
        $response = $this->http()->get($url);
        $this->failIfError($response, 'OnGrid list individuals failed');

        return $response->json() ?? [];
    }

    /** @param  list<string>  $verificationCodes  CVV is excluded (handled after initiate via /doc/cv + /cvv).
     * @param  array{
     *   omit_uid?: bool,
     *   omit_aadhaar_document?: bool,
     *   omit_pan_nested_file?: bool,
     *   omit_photo_document?: bool
     * }  $options
     */
    public static function buildInitiatePayload(EmployeesNewJoiner $employee, array $verificationCodes, array $options = []): array
    {
        $codes = array_values(array_unique(array_filter(
            $verificationCodes,
            fn (string $c) => $c !== 'CVV'
        )));

        $deduplicationId = self::deduplicationId($employee);
        $basic = ($employee->emp_profile_data ?? [])['information']['basic_information'] ?? [];
        $payload = self::profileFields($employee, $deduplicationId, $codes, $options);
        $payload['verifications'] = self::verificationList($employee, $codes, $deduplicationId);
        self::assertInitiatePayload($payload, $employee, $codes, $basic);

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array{omit_uid?: bool}  $options
     */
    protected static function sanitizeProfilePayload(array &$payload, EmployeesNewJoiner $employee, array $options = []): void
    {
        $phone = self::digitsOnly((string) ($payload['phone'] ?? $employee->emp_phone ?? ''));
        if (strlen($phone) !== 10) {
            throw new \RuntimeException(
                'OnGrid requires a 10-digit mobile number. Update the candidate phone in the Info tab (current: "'
                . ($payload['phone'] ?? '') . '").'
            );
        }
        $payload['phone'] = $phone;

        unset($payload['phoneCountryCode'], $payload['alternatePhoneCountryCode']);

        $alt = self::digitsOnly((string) ($payload['alternatePhone'] ?? ''));
        if (strlen($alt) === 10) {
            $payload['alternatePhone'] = $alt;
        } else {
            unset($payload['alternatePhone']);
        }

        // uid triggers a new AV/UIDV every time it is sent — omit on re-initiate when AV already exists.
        if (!empty($options['omit_uid'])) {
            unset($payload['uid']);
        } else {
            $uid = self::digitsOnly((string) ($payload['uid'] ?? ''));
            if (strlen($uid) === 12 && self::findUploadedPdf($employee->id, 'aadhaar_card')) {
                $payload['uid'] = $uid;
            } else {
                unset($payload['uid']);
            }
        }
    }

    /** @param  array<string, string>  $skipped */
    protected static function formatSkippedOfferings(array $skipped): string
    {
        if ($skipped === []) {
            return '';
        }
        $parts = [];
        foreach ($skipped as $code => $reason) {
            $parts[] = "{$code}: {$reason}";
        }

        return 'Skipped: ' . implode('; ', $parts) . '.';
    }

    /** @return array{professionId: string, otherProfession: string} */
    protected static function resolveProfessionFields(EmployeesNewJoiner $employee, array $basic): array
    {
        // OnGrid professionId must be numeric (EMP_51 → 51). Job title goes in otherProfession.
        $professionId = (string) $employee->id;

        $titleParts = array_filter([
            trim((string) ($basic['other_profession'] ?? '')),
            trim((string) ($employee->emp_role ?? '')),
        ]);

        return [
            'professionId' => $professionId,
            'otherProfession' => $titleParts !== [] ? implode(' — ', array_unique($titleParts)) : '',
        ];
    }

    protected static function mapGender(string $gender): string
    {
        $g = strtolower(trim($gender));

        return match ($g) {
            'male', 'm' => 'M',
            'female', 'f' => 'F',
            'other', 'o' => 'O',
            default => strlen($g) === 1 ? strtoupper($g) : '',
        };
    }

    protected static function digitsOnly(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    protected static function annualCompensationValue(array $employment): string
    {
        $raw = (string) ($employment['current_ctc'] ?? '');
        $digits = self::digitsOnly($raw);
        if ($digits === '') {
            throw new \RuntimeException(
                'EMPV annualCompensation must be a number. Current CTC in profile is not numeric: "' . $raw . '".'
            );
        }

        return $digits;
    }

    /** @param  array<string, mixed>  $payload
     * @param  list<string>  $codes
     * @param  array<string, mixed>  $basic
     */
    protected static function assertInitiatePayload(
        array $payload,
        EmployeesNewJoiner $employee,
        array $codes,
        array $basic
    ): void {
        $professionId = (string) ($payload['professionId'] ?? '');
        if ($professionId === '' || !preg_match('/^\d+$/', $professionId)) {
            throw new \RuntimeException(
                'OnGrid professionId must be numeric. Got "' . $professionId . '". '
                . 'Update candidate Info tab: Profession field = OnGrid ID; job title = Other Profession.'
            );
        }

        $pincode = (string) (($payload['permanentAddress'] ?? [])['pincode'] ?? '');
        if ($pincode !== '' && !preg_match('/^\d{6}$/', $pincode)) {
            throw new \RuntimeException(
                'Permanent address pincode must be 6 digits for OnGrid. Got "' . $pincode . '".'
            );
        }

        if (in_array('PANV', $codes, true) && self::panIdentityDocument($employee, $basic, self::deduplicationId($employee)) === null) {
            throw new \RuntimeException(
                'PANV requires PAN number in the candidate profile (Info tab) and PAN Card PDF in Documents tab.'
            );
        }

        $deduplicationId = self::deduplicationId($employee);
        $dedupKeys = $payload['deduplicationKeys'] ?? [];
        if (!is_array($dedupKeys) || !in_array($deduplicationId, $dedupKeys, true)) {
            throw new \RuntimeException('OnGrid payload missing deduplicationKeys entry: ' . $deduplicationId);
        }

        foreach ($payload['verifications'] ?? [] as $verification) {
            if (!is_array($verification)) {
                continue;
            }
            $key = $verification['key'] ?? null;
            if ($key !== $deduplicationId) {
                $code = (string) ($verification['code'] ?? '?');
                throw new \RuntimeException(
                    "OnGrid verification {$code} is missing matching key (expected {$deduplicationId})."
                );
            }
        }
    }

    /** Map portal degree text to OnGrid education level enum. */
    protected static function mapEducationLevel(array $row): string
    {
        $degree = strtolower(trim((string) ($row['degree'] ?? '')));
        $rawLevel = strtoupper(trim((string) ($row['level'] ?? '')));
        $ongridLevels = [
            'NO_EDUCATION', 'LESS_THEN_FIFTH_STD', 'FIFTH_STD', 'EIGHT_STD', 'TENTH_STD', 'TWELFTH_STD',
            'DIPLOMA', 'GRADUATE', 'PROFESSIONAL_COURSE', 'MASTERS', 'PHD', 'POST_GRADUATE',
        ];
        if (in_array($rawLevel, $ongridLevels, true)) {
            return $rawLevel;
        }

        $map = [
            'ssc' => 'TENTH_STD', '10th' => 'TENTH_STD', 'tenth' => 'TENTH_STD', 'matric' => 'TENTH_STD',
            'hsc' => 'TWELFTH_STD', '12th' => 'TWELFTH_STD', 'twelfth' => 'TWELFTH_STD', 'intermediate' => 'TWELFTH_STD',
            'diploma' => 'DIPLOMA',
            'bachelor' => 'GRADUATE', 'b.tech' => 'GRADUATE', 'btech' => 'GRADUATE', 'graduate' => 'GRADUATE', 'graduation' => 'GRADUATE',
            'master' => 'MASTERS', 'masters' => 'MASTERS', 'mba' => 'MASTERS', 'm.tech' => 'MASTERS',
            'phd' => 'PHD', 'doctorate' => 'PHD',
            'post graduate' => 'POST_GRADUATE', 'postgraduate' => 'POST_GRADUATE',
        ];
        foreach ($map as $needle => $level) {
            if ($degree === $needle || str_contains($degree, $needle)) {
                return $level;
            }
        }

        return 'GRADUATE';
    }

    protected static function normalizePanNumber(?string $value): string
    {
        return strtoupper(preg_replace('/\s+/', '', (string) $value) ?? '');
    }

    /** @param  array<string, mixed>  $basic */
    protected static function panNumberFromBasic(array $basic): string
    {
        foreach (['pan', 'pan_number', 'pan_no'] as $key) {
            $pan = self::normalizePanNumber($basic[$key] ?? '');
            if ($pan !== '') {
                return $pan;
            }
        }

        return '';
    }

    // --- Profile helpers (used in views + payload) ---

    /** @return list<array<string, mixed>> */
    public static function employmentRecords(array $employment): array
    {
        if (!empty($employment['records']) && is_array($employment['records'])) {
            return array_values(array_filter($employment['records'], 'is_array'));
        }
        if (!empty($employment['last_company']) && is_array($employment['last_company'])) {
            return [$employment['last_company']];
        }
        if (isset($employment[0]) && is_array($employment[0])) {
            return $employment;
        }

        return [];
    }

    /**
     * Prior employer used for OnGrid EMPV (most recent by last working date).
     * Verification is scoped only to joiningDate / lastWorkingDate on this record.
     */
    public static function empvEmploymentRecord(array $employment): ?array
    {
        $records = self::employmentRecords($employment);
        if ($records === []) {
            return null;
        }

        $best = null;
        $bestTs = null;

        foreach ($records as $record) {
            if (trim((string) ($record['employer_name'] ?? '')) === '') {
                continue;
            }

            $lastWorking = self::formatEmploymentDate($record['last_working_date'] ?? null);
            if ($lastWorking === null) {
                $best ??= $record;

                continue;
            }

            $ts = strtotime($lastWorking);
            if ($ts !== false && ($bestTs === null || $ts > $bestTs)) {
                $bestTs = $ts;
                $best = $record;
            }
        }

        return $best ?? $records[array_key_last($records)];
    }

    public static function lastEmploymentRecord(array $employment): ?array
    {
        return self::empvEmploymentRecord($employment);
    }

    public static function empvRecordHasRequiredDates(array $record): bool
    {
        return self::formatEmploymentDate($record['joining_date'] ?? null) !== null
            && self::formatEmploymentDate($record['last_working_date'] ?? null) !== null;
    }

    /**
     * Joining date from candidate profile; last working from profile or HR employment verification start date.
     *
     * @param  array<string, mixed>  $record
     * @return array{joining: ?string, last_working: ?string}
     */
    public static function empvResolvableDates(EmployeesNewJoiner $employee, array $record, ?string $empvStartDate = null): array
    {
        $joining = self::formatEmploymentDate($record['joining_date'] ?? null);
        $lastWorking = self::formatEmploymentDate($record['last_working_date'] ?? null);

        if ($lastWorking === null) {
            $lastWorking = self::formatEmploymentDate($empvStartDate);
        }
        if ($lastWorking === null) {
            $other = is_array($employee->emp_other) ? $employee->emp_other : [];
            $lastWorking = self::formatEmploymentDate($other['empv_start_date'] ?? null);
        }

        return ['joining' => $joining, 'last_working' => $lastWorking];
    }

    public static function formatEmploymentDate(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Allowed window for HR employment verification start date (relative to today).
     *
     * @return array{min: string, max: string, min_display: string, max_display: string, back_days: int, forward_months: int}
     */
    public static function empvStartDateAllowedRange(): array
    {
        $backDays = 45;
        $forwardMonths = 3;
        $today = Carbon::today();
        $min = $today->copy()->subDays($backDays);
        $max = $today->copy()->addMonths($forwardMonths);

        return [
            'min' => $min->format('Y-m-d'),
            'max' => $max->format('Y-m-d'),
            'min_display' => $min->format('d M Y'),
            'max_display' => $max->format('d M Y'),
            'back_days' => $backDays,
            'forward_months' => $forwardMonths,
        ];
    }

    /**
     * Effective min/max for EMPV start date (allowed window + candidate joining date at prior employer).
     *
     * @return array{min: string, max: string, range: array<string, mixed>}
     */
    public static function empvStartDateEffectiveBounds(?string $joiningDateMin = null): array
    {
        $range = self::empvStartDateAllowedRange();
        $min = $range['min'];
        $joining = self::formatEmploymentDate($joiningDateMin);
        if ($joining !== null && $joining > $min) {
            $min = $joining;
        }

        return [
            'min' => $min,
            'max' => $range['max'],
            'range' => $range,
        ];
    }

    /** @return list<string> Laravel validation rules for empv_start_date */
    public static function empvStartDateValidationRules(?string $joiningDateMin = null): array
    {
        $bounds = self::empvStartDateEffectiveBounds($joiningDateMin);

        return [
            'required',
            'date',
            'after_or_equal:' . $bounds['min'],
            'before_or_equal:' . $bounds['max'],
        ];
    }

    public static function assertEmpvStartDateAllowed(string $date, ?string $joiningDateMin = null): string
    {
        $formatted = self::formatEmploymentDate($date);
        if ($formatted === null) {
            throw new \InvalidArgumentException('Enter a valid employment verification start date.');
        }

        $bounds = self::empvStartDateEffectiveBounds($joiningDateMin);
        $range = $bounds['range'];

        if ($formatted < $bounds['min']) {
            $joining = self::formatEmploymentDate($joiningDateMin);
            if ($joining !== null && $formatted < $joining) {
                throw new \InvalidArgumentException(
                    'Employment verification start date cannot be before the candidate\'s joining date at that employer.'
                );
            }

            throw new \InvalidArgumentException(
                'Employment verification start date cannot be more than '
                . $range['back_days'] . ' days in the past (earliest: ' . $range['min_display'] . ').'
            );
        }

        if ($formatted > $bounds['max']) {
            throw new \InvalidArgumentException(
                'Employment verification start date cannot be more than '
                . $range['forward_months'] . ' months ahead (latest: ' . $range['max_display'] . ').'
            );
        }

        return $formatted;
    }

    /**
     * Persist HR employment verification start date (survives BGV errors / page reload).
     */
    public static function saveEmpvStartDatePreference(EmployeesNewJoiner $employee, string $date): string
    {
        if ($employee->isFresher()) {
            throw new \RuntimeException('EMPV is not applicable for fresher candidates.');
        }

        $employment = ($employee->emp_profile_data ?? [])['information']['previous_employment'] ?? [];
        $record = self::empvEmploymentRecord(is_array($employment) ? $employment : []);
        $joining = self::formatEmploymentDate($record['joining_date'] ?? null);
        $formatted = self::assertEmpvStartDateAllowed($date, $joining);

        self::setEmpvLastWorkingDate($employee, $formatted);

        $other = is_array($employee->emp_other) ? $employee->emp_other : [];
        $other['empv_start_date'] = $formatted;
        if (Carbon::parse($formatted)->startOfDay()->isAfter(Carbon::today()->startOfDay())) {
            $other['empv_scheduled_start'] = $formatted;
        } else {
            unset($other['empv_scheduled_start']);
        }
        $employee->emp_other = $other;
        $employee->save();

        return $formatted;
    }

    /**
     * Apply HR-selected EMPV start date (last working date at prior employer).
     * If the date is in the future, EMPV is deferred until on/after that day.
     *
     * @return array{formatted: string, defer: bool, display: string, skip_reason: string}
     */
    public static function prepareEmpvForBgv(EmployeesNewJoiner $employee, string $date): array
    {
        if ($employee->isFresher()) {
            throw new \RuntimeException('EMPV is not applicable for fresher candidates.');
        }

        $formatted = self::saveEmpvStartDatePreference($employee, $date);

        $display = Carbon::parse($formatted)->format('d M Y');
        $defer = Carbon::parse($formatted)->startOfDay()->isAfter(Carbon::today()->startOfDay());

        return [
            'formatted' => $formatted,
            'defer' => $defer,
            'display' => $display,
            'skip_reason' => 'Scheduled for ' . $display
                . ' — Employment Verification will not start until this date; all other selected checks start now.',
        ];
    }

    /** Saved or submitted HR employment verification start date (YYYY-MM-DD). */
    public static function resolveEmpvStartDate(EmployeesNewJoiner $employee, ?string $override = null): ?string
    {
        $fromOverride = self::formatEmploymentDate($override);
        if ($fromOverride !== null) {
            return $fromOverride;
        }

        $other = is_array($employee->emp_other) ? $employee->emp_other : [];
        $fromSaved = self::formatEmploymentDate($other['empv_start_date'] ?? null);
        if ($fromSaved !== null) {
            return $fromSaved;
        }

        if (is_array($employee->ongrid_response)) {
            return self::formatEmploymentDate($employee->ongrid_response['empv_start_date'] ?? null);
        }

        return null;
    }

    /** True when EMPV start date is after today (verification must wait). */
    public static function isEmpvScheduledForFuture(EmployeesNewJoiner $employee, ?string $empvStartDate = null): bool
    {
        if ($employee->isFresher()) {
            return false;
        }

        $date = self::resolveEmpvStartDate($employee, $empvStartDate);
        if ($date === null) {
            return false;
        }

        return Carbon::parse($date)->startOfDay()->isAfter(Carbon::today()->startOfDay());
    }

    /** Context for the BGV modal EMPV date field. */
    public static function empvStartDateFormContext(EmployeesNewJoiner $employee): ?array
    {
        if ($employee->isFresher()) {
            return null;
        }

        $employment = ($employee->emp_profile_data ?? [])['information']['previous_employment'] ?? [];
        $record = self::empvEmploymentRecord(is_array($employment) ? $employment : []);
        if (!$record || trim((string) ($record['employer_name'] ?? '')) === '') {
            return null;
        }

        $joining = self::formatEmploymentDate($record['joining_date'] ?? null);
        $lastWorking = self::formatEmploymentDate($record['last_working_date'] ?? null);
        $other = is_array($employee->emp_other) ? $employee->emp_other : [];
        $preferred = self::formatEmploymentDate($other['empv_start_date'] ?? null);
        $scheduled = is_string($other['empv_scheduled_start'] ?? null)
            ? self::formatEmploymentDate($other['empv_scheduled_start'])
            : null;
        $fromBgv = is_array($employee->ongrid_response)
            ? self::formatEmploymentDate($employee->ongrid_response['empv_start_date'] ?? null)
            : null;

        $savedDate = $preferred ?: $scheduled ?: $lastWorking ?: $fromBgv ?: '';
        $defaultDate = $savedDate !== '' ? $savedDate : ($joining ?: '');
        $bounds = self::empvStartDateEffectiveBounds($joining);
        $range = $bounds['range'];
        $isFuture = $defaultDate !== ''
            && Carbon::parse($defaultDate)->startOfDay()->isAfter(Carbon::today()->startOfDay());

        return [
            'employer_name' => trim((string) ($record['employer_name'] ?? '')),
            'joining_date' => $joining,
            'default_date' => $defaultDate,
            'saved_date' => $savedDate !== '' ? $savedDate : $defaultDate,
            'saved_display' => $defaultDate !== '' ? Carbon::parse($defaultDate)->format('d M Y') : '',
            'has_saved_date' => $savedDate !== '',
            'is_future_scheduled' => $isFuture,
            'allowed_min' => $bounds['min'],
            'allowed_max' => $bounds['max'],
            'back_days' => $range['back_days'],
            'forward_months' => $range['forward_months'],
            'min_display' => Carbon::parse($bounds['min'])->format('d M Y'),
            'max_display' => Carbon::parse($bounds['max'])->format('d M Y'),
        ];
    }

    /** HR sets last working date on the prior employer used for OnGrid EMPV. */
    public static function setEmpvLastWorkingDate(EmployeesNewJoiner $employee, string $date): void
    {
        $formatted = self::formatEmploymentDate($date);
        if ($formatted === null) {
            throw new \InvalidArgumentException('Enter a valid last working date.');
        }

        $profile = $employee->emp_profile_data ?? [];
        $employment = $profile['information']['previous_employment'] ?? [];
        $records = self::employmentRecords($employment);
        if ($records === []) {
            throw new \RuntimeException('Candidate has not submitted previous employment details yet.');
        }

        $target = self::empvEmploymentRecord($employment);
        if (!$target) {
            throw new \RuntimeException('Could not find the previous employer record to update.');
        }

        $targetName = trim((string) ($target['employer_name'] ?? ''));
        $joiningDate = self::formatEmploymentDate($target['joining_date'] ?? null);
        if ($joiningDate !== null && $formatted < $joiningDate) {
            throw new \InvalidArgumentException('Last working date cannot be before the candidate\'s joining date at that company.');
        }

        $updated = false;
        foreach ($records as $i => $record) {
            if (trim((string) ($record['employer_name'] ?? '')) !== $targetName) {
                continue;
            }
            $records[$i]['last_working_date'] = $formatted;
            $updated = true;
            break;
        }

        if (!$updated) {
            throw new \RuntimeException('Could not update last working date on the employment record.');
        }

        if (!empty($employment['records']) && is_array($employment['records'])) {
            $employment['records'] = $records;
        } elseif (!empty($employment['last_company']) && is_array($employment['last_company'])) {
            $employment['last_company']['last_working_date'] = $formatted;
        } else {
            $employment = ['records' => $records] + (is_array($employment) ? $employment : []);
        }

        $profile['information'] = ($profile['information'] ?? []) + ['previous_employment' => $employment];
        $employee->emp_profile_data = $profile;
    }

    /**
     * OnGrid profile employeeId (e.g. EMP_1). professionId uses the numeric id separately.
     */
    public static function ongridEmployeeId(EmployeesNewJoiner $employee): string
    {
        return 'EMP_' . $employee->id;
    }

    /**
     * Deduplication id for deduplicationKeys and verification key (e.g. EMP_GID_1).
     */
    public static function deduplicationId(EmployeesNewJoiner $employee): string
    {
        return 'EMP_GID_' . $employee->id;
    }

    /** Verification / section reference when no document number exists: e.g. CCRV_EMP_1. */
    protected static function verificationDocumentUid(string $code, EmployeesNewJoiner $employee, int $index = 1): string
    {
        $empRef = self::ongridEmployeeId($employee);
        if ($index > 1) {
            return $code . '_' . $empRef . '_' . $index;
        }

        return $code . '_' . $empRef;
    }

    protected static function aadhaarNumberFromBasic(array $basic): string
    {
        $uid = self::digitsOnly($basic['uid'] ?? '');

        return strlen($uid) === 12 ? $uid : '';
    }

    /** @param  list<string>  $verificationCodes
     * @return array<string, mixed>
     */
    /**
     * @param  list<string>  $verificationCodes
     * @param  array{
     *   omit_uid?: bool,
     *   omit_aadhaar_document?: bool,
     *   omit_pan_nested_file?: bool,
     *   omit_photo_document?: bool
     * }  $options
     */
    protected static function profileFields(
        EmployeesNewJoiner $employee,
        string $deduplicationId,
        array $verificationCodes = [],
        array $options = []
    ): array {
        $info = ($employee->emp_profile_data ?? [])['information'] ?? [];
        $basic = $info['basic_information'] ?? [];
        $address = $info['address_details'] ?? [];
        $declaration = $info['declaration'] ?? [];

        $profession = self::resolveProfessionFields($employee, $basic);
        $pincode = self::digitsOnly($address['permanent_pincode'] ?? '');

        $permanent = array_filter([
            'co' => $address['permanent_co'] ?? '',
            'line1' => $address['permanent_line1'] ?? '',
            'locality' => $address['permanent_locality'] ?? '',
            'landmark' => $address['permanent_landmark'] ?? '',
            'district' => $address['permanent_city'] ?? '',
            'state' => $address['permanent_state'] ?? '',
            'pincode' => $pincode,
            'fullAddress' => $address['permanent_full_address'] ?? '',
        ], fn ($v) => $v !== null && $v !== '');

        $payload = [
            'name' => $basic['name'] ?? $employee->emp_name,
            'professionId' => $profession['professionId'],
            'otherProfession' => $profession['otherProfession'],
            'gender' => self::mapGender((string) ($basic['gender'] ?? '')),
            'city' => $basic['city'] ?? '',
            'phoneCountryCode' => $basic['phone_country_code'] ?? '+91',
            'phone' => $basic['phone'] ?? $employee->emp_phone,
            'uid' => !empty($options['omit_uid']) ? '' : self::aadhaarNumberFromBasic($basic),
            'email' => $basic['email'] ?? $employee->emp_email,
            'dob' => !empty($basic['dob']) ? Carbon::parse($basic['dob'])->format('Y-m-d') : '',
            'hasConsent' => 'true',
            'consentText' => self::configuredConsentText(),
            'permanentAddress' => $permanent,
            'currentAddress' => $address['current_full_address'] ?? '',
            'employeeId' => self::ongridEmployeeId($employee),
            'fathersName' => $basic['fathers_name'] ?? '',
            'alternatePhoneCountryCode' => $basic['alternate_phone_country_code'] ?? '',
            'alternatePhone' => $basic['alternate_phone'] ?? '',
            'joiningDate' => self::joiningDate($employee, $basic),
            'deduplicationKeys' => [$deduplicationId],
        ];

        if (!empty($basic['uan'])) {
            $payload['uans'] = array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', (string) $basic['uan']))));
        }

        $pan = self::panNumberFromBasic($basic);
        if ($pan !== '') {
            $payload['otherIdentifiers'] = ['pan' => $pan];
        }

        $identityDocuments = self::identityDocumentsForPayload(
            $employee,
            $basic,
            $verificationCodes,
            $deduplicationId,
            $options
        );
        if ($identityDocuments !== []) {
            $payload['documents'] = $identityDocuments;
        }

        return $payload;
    }

    /**
     * @param  list<string>  $verificationCodes
     * @param  array{
     *   omit_aadhaar_document?: bool,
     *   omit_pan_nested_file?: bool,
     *   omit_photo_document?: bool
     * }  $options
     * @return list<array<string, mixed>>
     */
    protected static function identityDocumentsForPayload(
        EmployeesNewJoiner $employee,
        array $basic,
        array $verificationCodes,
        string $deduplicationId,
        array $options = []
    ): array {
        $documents = [];

        // On update, skip PAN Base64 in documents[] — multipart /doc/pan Update attaches the file.
        if (empty($options['omit_pan_nested_file'])
            && in_array('PANV', $verificationCodes, true)
            && ($panDoc = self::panIdentityDocument($employee, $basic, $deduplicationId)) !== null) {
            $documents[] = $panDoc;
        }

        // On update, do not re-attach Aadhaar PDF (uid alone starts AV on first create).
        if (empty($options['omit_aadhaar_document'])
            && ($aadhaarDoc = self::aadhaarIdentityDocument($employee, $basic, $deduplicationId))) {
            $documents[] = $aadhaarDoc;
        }

        if (empty($options['omit_photo_document'])
            && in_array('PCC', $verificationCodes, true)
            && ($photoDoc = self::photoIdentityDocument($employee, $basic, $deduplicationId)) !== null) {
            $documents[] = $photoDoc;
        }

        return $documents;
    }

    /** @param  array<string, mixed>  $basic
     * @return array<string, mixed>|null
     */
    protected static function panIdentityDocument(EmployeesNewJoiner $employee, array $basic, string $deduplicationId): ?array
    {
        $pan = self::panNumberFromBasic($basic);
        if ($pan === '' || !preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $pan)) {
            return null;
        }

        $row = self::findUploadedPdf($employee->id, 'pan_card');
        $documentUid = self::panNumberFromBasic($basic);
        if ($documentUid === '') {
            return null;
        }
        if (!$row || ($attachment = self::fileAsBase64Attachment($row, 'PANCard', $employee, $deduplicationId, $documentUid)) === null) {
            return null;
        }

        $dob = !empty($basic['dob']) ? Carbon::parse($basic['dob'])->format('Y-m-d') : '';

        return array_filter([
            'documentType' => 'PANCard',
            'documentUID' => $documentUid,
            'key' => $deduplicationId,
            'nameAsPerDocument' => $basic['name'] ?? $employee->emp_name,
            'legalGuardianName' => $basic['fathers_name'] ?? '',
            'dob' => $dob,
            'documents' => [$attachment],
        ], fn ($v) => $v !== null && $v !== '' && $v !== []);
    }

    /** @param  array<string, mixed>  $basic
     * @return array<string, mixed>|null
     */
    protected static function aadhaarIdentityDocument(EmployeesNewJoiner $employee, array $basic, string $deduplicationId): ?array
    {
        $uid = self::aadhaarNumberFromBasic($basic);
        if ($uid === '') {
            return null;
        }

        $row = self::findUploadedPdf($employee->id, 'aadhaar_card');
        if (!$row || ($attachment = self::fileAsBase64Attachment($row, 'Aadhaar', $employee, $deduplicationId, $uid)) === null) {
            return null;
        }

        $dob = !empty($basic['dob']) ? Carbon::parse($basic['dob'])->format('Y-m-d') : '';

        return array_filter([
            'documentType' => 'Aadhaar',
            'documentUID' => $uid,
            'key' => $deduplicationId,
            'nameAsPerDocument' => $basic['name'] ?? $employee->emp_name,
            'legalGuardianName' => $basic['fathers_name'] ?? '',
            'dob' => $dob,
            'documents' => [$attachment],
        ], fn ($v) => $v !== null && $v !== '' && $v !== []);
    }

    /** @param  array<string, mixed>  $basic
     * @return array<string, mixed>|null
     */
    protected static function photoIdentityDocument(EmployeesNewJoiner $employee, array $basic, string $deduplicationId): ?array
    {
        $row = self::findUploadedDocument($employee->id, 'photo');
        $documentUid = self::verificationDocumentUid('PCC', $employee);
        if (!$row || ($attachment = self::fileAsBase64Attachment($row, 'ProfileImage', $employee, $deduplicationId, $documentUid, true)) === null) {
            return null;
        }

        return array_filter([
            'documentType' => 'ProfileImage',
            'documentUID' => $documentUid,
            'key' => $deduplicationId,
            'nameAsPerDocument' => $basic['name'] ?? $employee->emp_name,
            'documents' => [$attachment],
        ], fn ($v) => $v !== null && $v !== '' && $v !== []);
    }

    protected static function cvPdfRow(EmployeesNewJoiner $employee): ?NewEmployeesDocument
    {
        return self::findUploadedDocument($employee->id, 'cv');
    }

    /** @param  list<string>  $codes
     * @return list<array<string, mixed>>
     */
    protected static function verificationList(EmployeesNewJoiner $employee, array $codes, string $deduplicationId): array
    {
        $blocks = [];
        $educationRows = ($employee->emp_profile_data ?? [])['information']['education_qualification'] ?? [];
        $wantEduv = false;

        foreach ($codes as $code) {
            if ($code === 'EDUV') {
                $wantEduv = true;
                continue;
            }
            if ($code === 'EMPV') {
                $empv = self::empvVerification($employee, $deduplicationId);
                if ($empv !== null) {
                    $blocks[] = $empv;
                }
                continue;
            }
            if ($code === 'PANV') {
                $panv = self::panvVerification($employee, $deduplicationId);
                if ($panv !== null) {
                    $blocks[] = $panv;
                }
                continue;
            }
            $blocks[] = [
                'code' => $code,
                'key' => $deduplicationId,
                'data' => ['documentUID' => self::verificationDocumentUid($code, $employee)],
            ];
        }

        if ($wantEduv) {
            foreach (self::eduvVerifications($employee, $educationRows, $deduplicationId) as $eduv) {
                $blocks[] = $eduv;
            }
        }

        return $blocks;
    }

    protected static function panvVerification(EmployeesNewJoiner $employee, string $deduplicationId): ?array
    {
        $basic = ($employee->emp_profile_data ?? [])['information']['basic_information'] ?? [];
        $pan = self::panNumberFromBasic($basic);
        if ($pan === '' || self::panIdentityDocument($employee, $basic, $deduplicationId) === null) {
            return null;
        }

        return [
            'code' => 'PANV',
            'key' => $deduplicationId,
            'data' => ['documentUID' => self::panNumberFromBasic($basic)],
        ];
    }

    protected static function empvVerification(EmployeesNewJoiner $employee, string $deduplicationId): ?array
    {
        if ($employee->isFresher()) {
            return null;
        }

        $employment = ($employee->emp_profile_data ?? [])['information']['previous_employment'] ?? [];
        $record = self::empvEmploymentRecord($employment);
        if (!$record) {
            return null;
        }

        $dates = self::empvResolvableDates($employee, $record);
        $joiningDate = $dates['joining'];
        $lastWorkingDate = $dates['last_working'];
        if ($joiningDate === null) {
            throw new \RuntimeException(
                'EMPV requires the candidate joining date at the previous employer in the profile (Info tab).'
            );
        }
        if ($lastWorkingDate === null) {
            throw new \RuntimeException(
                'EMPV requires the Employment verification start date in the BGV modal (used as last working date at the prior employer).'
            );
        }

        $docs = [];
        $empvDocIndex = 0;
        foreach (['salary_slips' => 'SalarySlip', 'appointment_letter' => 'AppointmentLetter', 'increment_letter' => 'ExperienceLetter'] as $key => $type) {
            $row = self::findUploadedPdf($employee->id, $key);
            if (!$row) {
                continue;
            }
            $empvDocIndex++;
            $documentUid = self::verificationDocumentUid('EMPV', $employee, $empvDocIndex);
            if ($att = self::fileAsBase64Attachment($row, $type, $employee, $deduplicationId, $documentUid)) {
                $docs[] = $att;
            }
        }

        if (empty($record['employer_name']) || $docs === []) {
            throw new \RuntimeException('EMPV requires previous employer details and salary slip or appointment letter PDF.');
        }

        return [
            'code' => 'EMPV',
            'key' => $deduplicationId,
            'data' => array_filter([
                'documentUID' => self::verificationDocumentUid('EMPV', $employee),
                'employmentRecord' => array_filter([
                    'nameAsPerEmployerRecords' => $record['name_as_per_employer_records'] ?? $employee->emp_name,
                    'employeeId' => self::ongridEmployeeId($employee),
                    'employerName' => $record['employer_name'],
                    'lastDesignation' => $record['last_designation'] ?? '',
                    'jobDescription' => $record['job_description'] ?? '',
                    'lastWorkingCity' => $record['last_working_city'] ?? '',
                    'joiningDate' => $joiningDate,
                    'lastWorkingDate' => $lastWorkingDate,
                    'annualCompensation' => self::annualCompensationValue($employment),
                    'hrName' => $employment['hr_name'] ?? '',
                    'hrEmail' => $employment['hr_email'] ?? '',
                    'hrPhone' => $employment['hr_phone'] ?? '',
                    'documents' => $docs,
                ], fn ($v) => $v !== null && $v !== '' && $v !== []),
            ], fn ($v) => $v !== null && $v !== '' && $v !== []),
        ];
    }

    /** @return list<array<string, mixed>> */
    protected static function eduvVerifications(EmployeesNewJoiner $employee, array $educationRows, string $deduplicationId): array
    {
        $rows = is_array($educationRows) && isset($educationRows[0]) ? $educationRows : [$educationRows];
        $blocks = [];
        $i = 0;

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $institute = trim((string) ($row['name_of_institute'] ?? $row['institute'] ?? ''));
            $degree = trim((string) ($row['degree'] ?? ''));
            $year = trim((string) ($row['year_of_passing'] ?? ''));
            if ($institute === '' || $degree === '' || $year === '') {
                continue;
            }

            $i++;
            $docKey = $i === 1 ? 'highest_certificate' : 'additional_certification';
            $docRow = self::findUploadedPdf($employee->id, $docKey) ?? self::findUploadedPdf($employee->id, 'highest_certificate');
            $documentUid = self::verificationDocumentUid('EDUV', $employee, $i);
            $documents = $docRow && ($att = self::fileAsBase64Attachment($docRow, 'EducationalCertificates', $employee, $deduplicationId, $documentUid)) ? [$att] : [];

            if ($documents === []) {
                throw new \RuntimeException("EDUV row {$i} requires education certificate PDF on file.");
            }

            $yearDigits = self::digitsOnly($year);
            if ($yearDigits === '' || strlen($yearDigits) !== 4) {
                throw new \RuntimeException("EDUV row {$i}: year of passing must be a 4-digit year. Got \"{$year}\".");
            }

            $issueDate = !empty($row['issue_date'])
                ? Carbon::parse($row['issue_date'])->format('Y-m-d')
                : '';

            $blocks[] = [
                'code' => 'EDUV',
                'key' => $deduplicationId,
                'data' => [
                    'documentUID' => self::verificationDocumentUid('EDUV', $employee, $i),
                    'educationDocument' => array_filter([
                    'nameAsPerDocument' => $employee->emp_name,
                    'level' => self::mapEducationLevel($row),
                    'nameOfInstitute' => $institute,
                    'yearOfPassing' => $yearDigits,
                    'degree' => $degree,
                    'registrationNumber' => trim((string) ($row['registration_number'] ?? '')),
                    'issueDate' => $issueDate,
                    'fieldOfStudy' => trim((string) ($row['field_of_study'] ?? '')),
                    'nameOfBoardUniversity' => trim((string) ($row['name_of_board_university'] ?? '')),
                    'grade' => trim((string) ($row['grade'] ?? '')),
                    'documents' => $documents,
                ], fn ($v) => $v !== null && $v !== '' && $v !== []),
                ],
            ];
        }

        if ($blocks === []) {
            throw new \RuntimeException('EDUV requires at least one education record in profile.');
        }

        return $blocks;
    }

    protected static function findUploadedDocument(int $empId, string $selectKey): ?NewEmployeesDocument
    {
        return NewEmployeesDocument::query()
            ->where('emp_id', $empId)
            ->where('emp_select_document', $selectKey)
            ->whereNotNull('emp_document_file_path')
            ->where('emp_document_file_path', '!=', '-')
            ->orderByDesc('id')
            ->first();
    }

    protected static function findUploadedPdf(int $empId, string $selectKey): ?NewEmployeesDocument
    {
        return self::findUploadedDocument($empId, $selectKey);
    }

    protected static function resolveDocumentFullPath(NewEmployeesDocument $document, EmployeesNewJoiner $employee): ?string
    {
        $dbPath = (string) ($document->emp_document_file_path ?? '');
        if ($dbPath !== '' && $dbPath !== '-') {
            $relative = ltrim(preg_replace('#^storage/#', '', $dbPath), '/');
            if ($relative !== '' && Storage::disk('public')->exists($relative)) {
                return storage_path('app/public/' . $relative);
            }
        }

        $fileName = (string) ($document->emp_document_file ?? '');
        if ($fileName === '') {
            return null;
        }

        $folders = ['temp/employees/EMP_' . $employee->id];
        if ($employee->emp_folder && $employee->emp_folder_path) {
            $folders[] = rtrim((string) $employee->emp_folder_path, '/') . '/' . $employee->emp_folder;
        }
        if ($employee->emp_folder) {
            $folders[] = 'uploads/employees/' . $employee->emp_folder;
        }

        foreach (array_unique($folders) as $folder) {
            $relative = $folder . '/' . $fileName;
            if (Storage::disk('public')->exists($relative)) {
                return storage_path('app/public/' . $relative);
            }
        }

        return null;
    }

    /**
     * OnGrid enum: Url | Binary | Base64 (case-sensitive).
     *
     * @return array{documentType: string, fileDataType: string, fileName: string, fileContent: string, key?: string}|null
     */
    protected static function fileAsBase64Attachment(
        NewEmployeesDocument $document,
        string $ongridType,
        EmployeesNewJoiner $employee,
        ?string $clientKey = null,
        ?string $documentUid = null,
        bool $allowImages = false
    ): ?array {
        $full = self::resolveDocumentFullPath($document, $employee);
        if ($full === null || !self::isAllowedUploadFile($full, $allowImages)) {
            return null;
        }

        $binary = file_get_contents($full);
        if ($binary === false || $binary === '' || strlen($binary) > 5 * 1024 * 1024) {
            return null;
        }

        $fileName = $document->emp_document_file ?: basename($full);
        if (self::isPdfFile($full) && !str_ends_with(strtolower($fileName), '.pdf')) {
            $fileName .= '.pdf';
        }

        return array_filter([
            'documentType' => $ongridType,
            'fileDataType' => 'Base64',
            'fileName' => $fileName,
            'fileContent' => base64_encode($binary),
            'key' => $clientKey,
            'documentUID' => $documentUid,
        ], fn ($v) => $v !== null && $v !== '' && $v !== []);
    }

    protected static function isAllowedUploadFile(string $fullPath, bool $allowImages): bool
    {
        if (self::isPdfFile($fullPath)) {
            return true;
        }
        if (!$allowImages) {
            return false;
        }

        $mime = mime_content_type($fullPath) ?: '';

        return str_contains($mime, 'jpeg')
            || str_contains($mime, 'png')
            || (bool) preg_match('/\.(jpe?g|png)$/i', $fullPath);
    }

    protected static function isPdfFile(string $fullPath): bool
    {
        if (str_ends_with(strtolower($fullPath), '.pdf')) {
            return true;
        }

        $mime = mime_content_type($fullPath) ?: '';

        return str_contains($mime, 'pdf');
    }

    /** @param  array<string, mixed>  $basic */
    protected static function joiningDate(EmployeesNewJoiner $employee, array $basic): string
    {
        unset($basic);

        if ($employee->emp_joining_date) {
            return $employee->emp_joining_date->format('Y-m-d');
        }

        return $employee->emp_date ? $employee->emp_date->format('Y-m-d') : '';
    }

    protected function http()
    {
        return Http::withBasicAuth($this->username, $this->password)
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('onboarding.ongrid_timeout', 120));
    }

    protected function failIfError(Response $response, string $message): void
    {
        if ($response->successful()) {
            return;
        }
        $body = $response->json() ?? $response->body();
        Log::warning($message, ['status' => $response->status(), 'body' => $body]);
        $detail = is_array($body) ? ($body['message'] ?? json_encode($body)) : (string) $body;
        if (stripos((string) $detail, 'consent text') !== false) {
            $sent = self::configuredConsentText();
            $detail .= ' — App sent consentText (' . strlen($sent) . ' chars): "'
                . Str::limit($sent, 120, '…')
                . '". Copy the exact consent wording from OnGrid community settings into ONGRID_CONSENT_TEXT, then run php artisan config:clear.';
        }
        if (stripos((string) $detail, 'no pan found') !== false) {
            $detail .= ' — Add PAN number in candidate Info tab and upload PAN Card PDF in Documents tab, then retry BGV.';
        }
        if (stripos((string) $detail, 'file data type') !== false) {
            $detail .= ' — A document attachment is missing fileDataType (must be Base64). Re-upload the PDF and retry BGV.';
        }
        if (stripos((string) $detail, 'cv document') !== false) {
            $detail .= ' — CV is uploaded via OnGrid /doc/cv after onboarding; retry BGV or contact support if this persists.';
        }
        if (stripos((string) $detail, 'required parameters not found') !== false) {
            $detail .= ' — Often EDUV (education level/fields), invalid phone, or a check selected without portal data. Use default BGV checks or fix profile/documents.';
        }
        throw new \RuntimeException(trim($message . ': ' . $detail));
    }
}
