<?php

namespace App\Support;

use App\Models\EmployeesNewJoiner;
use App\Models\NewEmployeesDocument;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** HR-uploaded combined PDF for legacy or missing candidate documents. */
class OnboardingHrCombinedDocument
{
    public const DOCUMENT_KEY = 'hr_combined_legacy_documents';

    public static function label(): string
    {
        return 'Combined Legacy / Missing Documents (HR)';
    }

    public static function hrCanUpload(EmployeesNewJoiner $employee): bool
    {
        if (OnboardingArchive::isFinalized($employee)) {
            return false;
        }

        return in_array($employee->onboardingStep(), [
            'documents_approved',
            'offer_sent',
            'offer_accepted',
            'registration_sent',
            'registration_submitted',
            'registration_verified',
            'bgv_started',
            'bgv_completed',
            'join_forms_sent',
            'join_forms_submitted',
            'policy_signed',
            'appointment_sent',
            'appointment_accepted',
            'appointment_pending_sr_hr',
            'appointment_sr_rejected',
            'end',
        ], true) || $employee->emp_document_status === 'completed';
    }

    public static function existing(EmployeesNewJoiner $employee): ?NewEmployeesDocument
    {
        return NewEmployeesDocument::query()
            ->where('emp_id', $employee->id)
            ->where('emp_select_document', self::DOCUMENT_KEY)
            ->first();
    }

    public static function store(EmployeesNewJoiner $employee, UploadedFile $file, ?string $note = null): NewEmployeesDocument
    {
        $folder = 'temp/employees/EMP_' . $employee->id;
        $extension = $file->getClientOriginalExtension() ?: 'pdf';
        $fileName = 'EMP_' . $employee->id . '_' . self::DOCUMENT_KEY . '.' . $extension;
        $filePath = Storage::disk('public')->putFileAs($folder, $file, $fileName);

        $doc = NewEmployeesDocument::updateOrCreate(
            [
                'emp_id' => $employee->id,
                'emp_select_document' => self::DOCUMENT_KEY,
            ],
            [
                'emp_doc_date' => Carbon::now(),
                'emp_document_file' => $fileName,
                'emp_document_file_path' => 'storage/' . $filePath,
                'emp_document_status' => 'approved',
                'emp_hr_id' => $employee->emp_hr_id,
                'approval_date' => Carbon::now(),
                'rejection_reason' => null,
            ]
        );

        if ($note) {
            $other = $employee->emp_other ?? [];
            $other['hr_combined_document_note'] = $note;
            $employee->emp_other = $other;
            $employee->save();
        }

        if (!$employee->emp_folder) {
            $employee->emp_folder = 'EMP_' . $employee->id;
            $employee->emp_folder_path = 'temp/employees/';
            $employee->save();
        }

        return $doc;
    }
}
