<?php

namespace App\Support;

use App\Models\EmployeesNewJoiner;

class CandidateSignature
{
    public static function offer(EmployeesNewJoiner $employee, bool $asUrl = false): ?string
    {
        return self::preview($employee->emp_signature, $asUrl, $employee->id);
    }

    public static function appointment(EmployeesNewJoiner $employee, bool $asUrl = false): ?string
    {
        $raw = ($employee->emp_other ?? [])['appointment_signature'] ?? $employee->emp_signature ?? null;

        return self::preview($raw, $asUrl, $employee->id);
    }

    public static function policy(EmployeesNewJoiner $employee, bool $asUrl = false): ?string
    {
        return self::preview($employee->emp_policy_signature, $asUrl, $employee->id);
    }

    public static function preview(?string $stored, bool $asUrl = false, ?int $employeeId = null): ?string
    {
        if ($stored === null || ($stored = trim($stored)) === '') {
            return null;
        }

        if (str_starts_with($stored, 'data:')) {
            return $asUrl && str_starts_with($stored, 'data:image/')
                ? (self::urlFromDataUri($stored, $employeeId) ?? $stored)
                : $stored;
        }

        if (preg_match('#^https?://#i', $stored)) {
            return $stored;
        }

        $relative = ltrim(str_replace('\\', '/', $stored), '/');

        if ($asUrl) {
            return PublicAssetUrl::url($relative);
        }

        foreach ([public_path($relative), base_path($relative)] as $file) {
            if (is_readable($file)) {
                return 'data:' . (mime_content_type($file) ?: 'image/png') . ';base64,' . base64_encode((string) file_get_contents($file));
            }
        }

        return PublicAssetUrl::url($relative);
    }

    protected static function urlFromDataUri(string $dataUri, ?int $employeeId = null): ?string
    {
        if (!preg_match('#^data:image/(png|jpe?g|gif|webp);base64,(.+)$#i', $dataUri, $matches)) {
            return null;
        }

        $ext = strtolower($matches[1]) === 'jpeg' ? 'jpg' : strtolower($matches[1]);
        $binary = base64_decode($matches[2], true);
        if ($binary === false || $binary === '') {
            return null;
        }

        $name = ($employeeId ? 'emp_' . $employeeId . '_' : '') . substr(sha1($binary), 0, 16) . '.' . $ext;
        $relative = 'candidate-signatures/' . $name;
        $absolute = storage_path('app/public/' . $relative);

        if (!is_dir(dirname($absolute))) {
            mkdir(dirname($absolute), 0755, true);
        }

        if (!is_file($absolute)) {
            file_put_contents($absolute, $binary);
        }

        return PublicAssetUrl::url('storage/' . $relative);
    }
}
