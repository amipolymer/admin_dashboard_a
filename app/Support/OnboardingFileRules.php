<?php

namespace App\Support;

use App\Data\DocumentNamesList;
use App\Models\EmployeesNewJoiner;

class OnboardingFileRules
{
    public const IMAGE_TYPES = ['photo'];

    public const PDF_DEFAULT_MAX_KB = 3072;

    public const BANK_STATEMENT_MAX_KB = 5120;

    public const PHOTO_MAX_KB = 1024;

    public static function isImageDocument(string $documentType): bool
    {
        return in_array($documentType, self::IMAGE_TYPES, true);
    }

    public static function maxSizeKb(string $documentType): ?int
    {
        if ($documentType === 'emergency_contact') {
            return null;
        }

        if (self::isImageDocument($documentType)) {
            return self::PHOTO_MAX_KB;
        }

        if ($documentType === 'bank_statement') {
            return self::BANK_STATEMENT_MAX_KB;
        }

        return self::PDF_DEFAULT_MAX_KB;
    }

    /** Laravel validation rule for portal document upload by type */
    public static function documentFileRule(string $documentType): string
    {
        if ($documentType === 'emergency_contact') {
            return 'string|max:255';
        }

        if (self::isImageDocument($documentType)) {
            return 'file|mimes:jpg,jpeg,png|max:' . self::PHOTO_MAX_KB;
        }

        return 'file|mimes:pdf|max:' . self::maxSizeKb($documentType);
    }

    public static function signatureFileRule(): string
    {
        return 'nullable|file|mimes:jpg,jpeg,png|max:2048';
    }

    public static function resignationLetterRule(): string
    {
        return 'required|file|mimes:pdf|max:5120';
    }

    /** HTML accept attribute for file input */
    public static function acceptAttribute(string $context): string
    {
        return match ($context) {
            'photo' => '.jpg,.jpeg,.png,image/jpeg,image/png',
            'signature' => '.jpg,.jpeg,.png,image/jpeg,image/png',
            default => '.pdf,application/pdf',
        };
    }

    public static function documentRulesMap(): array
    {
        $rules = ['emergency_contact' => 'string|max:255'];

        foreach (array_keys(collect(DocumentNamesList::all())->collapse()->toArray()) as $type) {
            if ($type === 'emergency_contact') {
                continue;
            }
            $rules[$type] = self::documentFileRule($type);
        }

        return $rules;
    }

    public static function resolveDocumentFileRule(EmployeesNewJoiner $employee, string $documentType): ?string
    {
        $map = self::documentRulesMap();
        if (isset($map[$documentType])) {
            return $map[$documentType];
        }

        if (OnboardingMediclaimDocuments::isAllowedKey($employee, $documentType)) {
            return self::documentFileRule($documentType);
        }

        return null;
    }

    /** Stored filename on disk — mediclaim family Aadhaar uses EMP_{id}_F_{type}.ext */
    public static function storedFileName(int $employeeId, string $documentType, string $extension): string
    {
        if (OnboardingMediclaimDocuments::isMediclaimAadhaarKey($documentType)) {
            return "EMP_{$employeeId}_F_{$documentType}.{$extension}";
        }

        return "EMP_{$employeeId}_{$documentType}.{$extension}";
    }

    public static function maxSizeLabel(string $documentType): string
    {
        $kb = self::maxSizeKb($documentType);
        if ($kb === null) {
            return '';
        }

        if (self::isImageDocument($documentType)) {
            return '1 MB (JPG/PNG only)';
        }

        if ($documentType === 'bank_statement') {
            return '5 MB (PDF only)';
        }

        return '3 MB (PDF only)';
    }

    public static function mimeErrorMessage(string $documentType): string
    {
        if (self::isImageDocument($documentType)) {
            return 'Passport size photo must be JPG or PNG.';
        }

        return 'This document must be a PDF file.';
    }
}
