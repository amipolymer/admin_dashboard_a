<?php

namespace App\Support;

use App\Models\EmployeesNewJoiner;
use App\Models\NewEmployeesDocument;
use Illuminate\Support\Facades\Storage;

class OnboardingArchive
{
    public static function canFinalize(EmployeesNewJoiner $employee): bool
    {
        if (self::isFinalized($employee)) {
            return false;
        }

        if ($employee->emp_appointment_letter_status !== 'accept') {
            return false;
        }

        return SrHrLetterApproval::isApproved($employee, SrHrLetterApproval::TYPE_APPOINTMENT);
    }

    public static function isFinalized(EmployeesNewJoiner $employee): bool
    {
        if ($employee->emp_archived_at) {
            return true;
        }

        if (($employee->emp_onboarding_status ?? '') === 'completed') {
            return true;
        }

        return $employee->onboardingStep() === 'end';
    }

    /**
     * Move all files to uploads/employees/EMP_{id}/ and mark onboarding complete.
     *
     * @throws \InvalidArgumentException
     */
    public static function finalize(EmployeesNewJoiner $employee, string $manualEmpId): EmployeesNewJoiner
    {
        $manualEmpId = trim($manualEmpId);

        if ($manualEmpId === '') {
            throw new \InvalidArgumentException('Employee ID is required.');
        }

        if (!self::canFinalize($employee)) {
            throw new \InvalidArgumentException(
                'Onboarding cannot be finalized yet. Candidate must accept the appointment letter and SR-HR must approve it.'
            );
        }

        $newFolderName = str_starts_with($manualEmpId, 'EMP_')
            ? $manualEmpId
            : 'EMP_' . $manualEmpId;
        $destPath = 'uploads/employees/';
        $destFolder = $destPath . $newFolderName;

        if (!Storage::disk('public')->exists($destFolder)) {
            Storage::disk('public')->makeDirectory($destFolder);
        }

        $documents = NewEmployeesDocument::where('emp_id', $employee->id)->get();

        foreach ($documents as $doc) {
            self::moveDocumentRecord($doc, $employee, $destFolder, $newFolderName);
        }

        foreach (self::sourceFolders($employee) as $sourceFolder) {
            self::moveRemainingFiles($sourceFolder, $destFolder, $newFolderName, $employee->id);
        }

        if (!empty($employee->emp_profile_data)) {
            $profileFile = $newFolderName . '_profile_data.json';
            Storage::disk('public')->put(
                $destFolder . '/' . $profileFile,
                json_encode($employee->emp_profile_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
        }

        $employee->emp_employee_id = $manualEmpId;
        $employee->emp_folder = $newFolderName;
        $employee->emp_folder_path = $destPath;
        $employee->emp_onboarding_status = 'completed';
        $employee->emp_status = 'completed';
        $employee->emp_archived_at = now();
        $employee->setOnboardingStep('end');

        return $employee->fresh();
    }

    /**
     * @return list<string>
     */
    protected static function sourceFolders(EmployeesNewJoiner $employee): array
    {
        $folders = [
            'temp/employees/EMP_' . $employee->id,
        ];

        if ($employee->emp_folder && $employee->emp_folder_path) {
            $current = rtrim($employee->emp_folder_path, '/') . '/' . $employee->emp_folder;
            if (!in_array($current, $folders, true)) {
                $folders[] = $current;
            }
        }

        return array_values(array_unique($folders));
    }

    protected static function moveDocumentRecord(
        NewEmployeesDocument $doc,
        EmployeesNewJoiner $employee,
        string $destFolder,
        string $newFolderName
    ): void {
        if ($doc->emp_select_document === 'emergency_contact' || !$doc->emp_document_file) {
            return;
        }

        $sourcePath = self::resolveStoragePath($doc->emp_document_file_path, $employee, $doc->emp_document_file);

        if (!$sourcePath || !Storage::disk('public')->exists($sourcePath)) {
            return;
        }

        $newFileName = self::buildDestFileName($doc->emp_document_file, $newFolderName, $employee->id);
        $destFile = $destFolder . '/' . $newFileName;

        if ($sourcePath !== $destFile) {
            if (Storage::disk('public')->exists($destFile)) {
                Storage::disk('public')->delete($destFile);
            }
            Storage::disk('public')->move($sourcePath, $destFile);
        }

        $doc->emp_document_file = $newFileName;
        $doc->emp_document_file_path = 'storage/' . $destFile;
        $doc->save();
    }

    protected static function moveRemainingFiles(
        string $sourceFolder,
        string $destFolder,
        string $newFolderName,
        int $employeeId
    ): void {
        if (!Storage::disk('public')->exists($sourceFolder)) {
            return;
        }

        foreach (Storage::disk('public')->files($sourceFolder) as $file) {
            $basename = basename($file);
            $destFile = $destFolder . '/' . self::buildDestFileName($basename, $newFolderName, $employeeId);
            if (!Storage::disk('public')->exists($destFile)) {
                Storage::disk('public')->move($file, $destFile);
            }
        }

        if (empty(Storage::disk('public')->files($sourceFolder))) {
            Storage::disk('public')->deleteDirectory($sourceFolder);
        }
    }

    protected static function resolveStoragePath(?string $dbPath, EmployeesNewJoiner $employee, string $fileName): ?string
    {
        if ($dbPath && $dbPath !== '-') {
            $relative = preg_replace('#^storage/#', '', $dbPath);
            if ($relative && Storage::disk('public')->exists($relative)) {
                return $relative;
            }
        }

        foreach (self::sourceFolders($employee) as $folder) {
            $candidate = $folder . '/' . $fileName;
            if (Storage::disk('public')->exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    protected static function buildDestFileName(string $original, string $newFolderName, int $employeeId): string
    {
        $updated = preg_replace('/EMP_' . $employeeId . '(?=_|\.)/', $newFolderName, $original);

        if ($updated === null || $updated === $original) {
            $updated = preg_replace('/EMP_\d+(?=_|\.)/', $newFolderName, $original) ?? $original;
        }

        return $updated;
    }
}
