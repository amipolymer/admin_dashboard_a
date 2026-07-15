<?php

namespace App\Support;

use App\Data\DocumentNamesList;
use App\Models\EmployeesNewJoiner;
use App\Models\NewEmployeesDocument;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/** HR replaces a stored candidate document with audit reason. */
class OnboardingHrDocumentReupload
{
    public const INTAKE_KEYS = ['cv', 'interview_evaluation', 'mrf_file'];

    public static function hrCanReupload(EmployeesNewJoiner $employee): bool
    {
        return !OnboardingArchive::isFinalized($employee);
    }

    public static function canReuploadDocument(NewEmployeesDocument $document): bool
    {
        if (($document->emp_select_document ?? '') === 'emergency_contact') {
            return false;
        }

        return $document->emp_document_status !== 'approved';
    }

    public static function reuploadBlockedMessage(NewEmployeesDocument $document): ?string
    {
        if (($document->emp_select_document ?? '') === 'emergency_contact') {
            return 'This document cannot be re-uploaded.';
        }

        if ($document->emp_document_status === 'approved') {
            return 'Reject this document first, then re-upload the new file.';
        }

        return null;
    }

    public static function documentLabel(string $key): string
    {
        $labels = DocumentNamesList::collapsedLabels();

        return $labels[$key] ?? str_replace('_', ' ', $key);
    }

    public static function fileValidationRule(NewEmployeesDocument $document, ?EmployeesNewJoiner $employee = null): string
    {
        $key = $document->emp_select_document ?? '';

        if (in_array($key, self::INTAKE_KEYS, true)) {
            return 'required|file|mimes:pdf|max:3072';
        }

        $employee = $employee ?? EmployeesNewJoiner::find($document->emp_id);
        $rule = $employee
            ? OnboardingFileRules::resolveDocumentFileRule($employee, $key)
            : null;

        return $rule ? 'required|' . $rule : 'required|file|mimes:pdf|max:3072';
    }

    public static function store(
        EmployeesNewJoiner $employee,
        NewEmployeesDocument $document,
        UploadedFile $file,
        string $reason
    ): NewEmployeesDocument {
        if (!self::hrCanReupload($employee)) {
            throw new \InvalidArgumentException('Cannot re-upload documents after onboarding is finalized.');
        }

        if (!self::canReuploadDocument($document)) {
            throw new \InvalidArgumentException(
                self::reuploadBlockedMessage($document) ?? 'This document cannot be re-uploaded.'
            );
        }

        $documentKey = $document->emp_select_document;
        $folder = 'temp/employees/EMP_' . $employee->id;
        $extension = $file->getClientOriginalExtension() ?: 'pdf';
        $fileName = OnboardingFileRules::storedFileName($employee->id, $documentKey, $extension);

        if ($document->emp_document_file_path && $document->emp_document_file_path !== '-') {
            $oldRelative = str_replace('storage/', '', $document->emp_document_file_path);
            if (Storage::disk('public')->exists($oldRelative)) {
                Storage::disk('public')->delete($oldRelative);
            }
        }

        $filePath = Storage::disk('public')->putFileAs($folder, $file, $fileName);
        $isIntake = in_array($documentKey, self::INTAKE_KEYS, true);
        $previousStatus = $document->emp_document_status;

        $document->emp_doc_date = Carbon::now();
        $document->emp_document_file = $fileName;
        $document->emp_document_file_path = 'storage/' . $filePath;
        $document->rejection_reason = null;

        if ($isIntake) {
            $document->emp_document_status = 'approved';
            $document->approval_date = Carbon::now();
        } elseif (in_array($previousStatus, ['approved', 'rejected', 'process'], true)) {
            $document->emp_document_status = 'process';
            $document->approval_date = null;
        }

        $document->save();

        $other = $employee->emp_other ?? [];
        $history = $other['hr_document_reuploads'] ?? [];
        $history[] = [
            'document_id' => $document->id,
            'document_key' => $documentKey,
            'document_label' => self::documentLabel($documentKey),
            'file_name' => $fileName,
            'reason' => $reason,
            'previous_status' => $previousStatus,
            'new_status' => $document->emp_document_status,
            'by_user_id' => Auth::id(),
            'by_name' => Auth::user()->name ?? 'HR',
            'at' => now()->toDateTimeString(),
        ];
        $other['hr_document_reuploads'] = $history;
        $employee->emp_other = $other;

        if (!$isIntake && $employee->emp_document_status === 'completed') {
            $employee->emp_document_status = 'process';
        }

        $employee->save();

        if (!$employee->emp_folder) {
            $employee->emp_folder = 'EMP_' . $employee->id;
            $employee->emp_folder_path = 'temp/employees/';
            $employee->save();
        }

        return $document;
    }
}
