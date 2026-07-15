<?php

namespace App\Support;

use App\Models\EmployeesNewJoiner;
use App\Models\NewEmployeesDocument;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class OnboardingLetterDocument
{
    public const OFFER = 'signed_offer_letter';

    /** PDF archived when SR-HR approves appointment (includes SR-HR + candidate signatures). */
    public const APPOINTMENT_SR_APPROVED = 'sr_hr_approved_appointment_letter';

    /** @deprecated Legacy key; kept for existing rows before sr_hr_approved_appointment_letter. */
    public const APPOINTMENT = 'signed_appointment_letter';

    /** Candidate upload — HR approves in documents table (not on joiner row). */
    public const RESIGNATION_UPLOAD = 'resignation_acceptance_letter';

    /** Legacy archived key after verify (optional duplicate). */
    public const REGISTRATION = 'signed_registration_letter';

    /** Candidate policy acceptance record (signature + accepted policies) for compliance. */
    public const POLICY_ACCEPTANCE = 'signed_company_policy';

    /**
     * Render letter view → PDF → storage + new_employees_documents (no HTML saved).
     */
    public static function archiveFromView(EmployeesNewJoiner $employee, string $documentKey, string $view, array $data): NewEmployeesDocument
    {
        $html = View::make($view, $data)->render();
        $pdfBinary = OnboardingLetterPdf::fromHtml($html);

        $folder = self::employeeFolder($employee);
        $fileName = 'EMP_' . $employee->id . '_' . $documentKey . '_' . now()->format('Ymd_His') . '.pdf';
        $relativePath = $folder . '/' . $fileName;

        self::deletePreviousFile($employee->id, $documentKey);

        Storage::disk('public')->put($relativePath, $pdfBinary);

        return self::upsertDocument($employee, $documentKey, $fileName, $relativePath);
    }

    /** Register resignation acceptance letter file in the documents table. */
    public static function archiveUploadedFile(
        EmployeesNewJoiner $employee,
        string $documentKey,
        string $storagePath,
        string $fileName
    ): NewEmployeesDocument {
        return self::upsertDocument($employee, $documentKey, $fileName, $storagePath);
    }

    /** Candidate resignation letter upload (status upload → process → approved). */
    public static function storeCandidateUpload(
        EmployeesNewJoiner $employee,
        string $documentKey,
        string $storagePath,
        string $fileName,
        string $status = 'upload'
    ): NewEmployeesDocument {
        $pathForDb = str_starts_with($storagePath, 'storage/')
            ? $storagePath
            : 'storage/' . ltrim($storagePath, '/');

        return NewEmployeesDocument::updateOrCreate(
            [
                'emp_id' => $employee->id,
                'emp_select_document' => $documentKey,
            ],
            [
                'emp_document_file' => $fileName,
                'emp_document_file_path' => $pathForDb,
                'emp_document_status' => $status,
                'emp_doc_date' => Carbon::now(),
                'emp_hr_id' => $employee->emp_hr_id,
                'rejection_reason' => null,
            ]
        );
    }

    protected static function deletePreviousFile(int $empId, string $documentKey): void
    {
        $existing = NewEmployeesDocument::query()
            ->where('emp_id', $empId)
            ->where('emp_select_document', $documentKey)
            ->first();

        if (!$existing || empty($existing->emp_document_file_path)) {
            return;
        }

        $path = preg_replace('#^storage/#', '', (string) $existing->emp_document_file_path);
        try {
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        } catch (\Throwable $e) {
            Log::warning('Could not delete previous letter file: ' . $e->getMessage());
        }
    }

    protected static function upsertDocument(
        EmployeesNewJoiner $employee,
        string $documentKey,
        string $fileName,
        string $relativePath
    ): NewEmployeesDocument {
        $pathForDb = str_starts_with($relativePath, 'storage/')
            ? $relativePath
            : 'storage/' . ltrim($relativePath, '/');

        return NewEmployeesDocument::updateOrCreate(
            [
                'emp_id' => $employee->id,
                'emp_select_document' => $documentKey,
            ],
            [
                'emp_document_file' => $fileName,
                'emp_document_file_path' => $pathForDb,
                'emp_document_status' => 'approved',
                'emp_doc_date' => Carbon::now(),
                'emp_hr_id' => $employee->emp_hr_id,
                'rejection_reason' => null,
            ]
        );
    }

    protected static function employeeFolder(EmployeesNewJoiner $employee): string
    {
        $base = rtrim($employee->emp_folder_path ?? 'temp/employees/', '/');
        $folder = $employee->emp_folder ?? 'EMP_' . $employee->id;

        return $base . '/' . $folder;
    }

    public static function latestForEmployee(EmployeesNewJoiner $employee, string $documentKey): ?NewEmployeesDocument
    {
        return NewEmployeesDocument::query()
            ->where('emp_id', $employee->id)
            ->where('emp_select_document', $documentKey)
            ->orderByDesc('id')
            ->first();
    }

    /** Latest SR-HR approved appointment letter PDF (current or legacy document key). */
    public static function latestSrHrApprovedAppointment(EmployeesNewJoiner $employee): ?NewEmployeesDocument
    {
        foreach ([self::APPOINTMENT_SR_APPROVED, self::APPOINTMENT] as $documentKey) {
            $document = self::latestForEmployee($employee, $documentKey);
            if ($document) {
                return $document;
            }
        }

        return null;
    }

    /** Full path on disk for an archived letter PDF, or null if missing. */
    public static function absolutePath(NewEmployeesDocument $document): ?string
    {
        if (empty($document->emp_document_file_path)) {
            return null;
        }

        $relative = preg_replace('#^storage/#', '', (string) $document->emp_document_file_path);
        $path = storage_path('app/public/' . ltrim($relative, '/'));

        return is_file($path) ? $path : null;
    }
}
