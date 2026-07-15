<?php

namespace App\Data;

use App\Support\PublicAssetUrl;

class SrHrTeam
{
    protected static ?array $configCache = null;

    /**
     * @return array{members: list<array>, emails: list<string>}
     */
    protected static function config(): array
    {
        if (self::$configCache !== null) {
            return self::$configCache;
        }

        $path = base_path('form_join/sr-hr-team.json');
        if (!is_file($path)) {
            self::$configCache = ['members' => [], 'emails' => []];

            return self::$configCache;
        }

        $data = json_decode(file_get_contents($path), true) ?: [];
        $members = [];
        foreach ($data['members'] ?? [] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $email = strtolower(trim((string) ($row['email'] ?? '')));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $members[] = [
                'id' => trim((string) ($row['id'] ?? '')),
                'name' => trim((string) ($row['name'] ?? '')),
                'email' => $email,
                'role' => trim((string) ($row['role'] ?? '')),
                'signature_file' => trim((string) ($row['signature_file'] ?? '')),
            ];
        }

        $emails = [];
        foreach (array_merge($data['emails'] ?? [], array_column($members, 'email')) as $email) {
            $email = strtolower(trim((string) $email));
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $email;
            }
        }

        self::$configCache = [
            'members' => $members,
            'emails' => array_values(array_unique($emails)),
        ];

        return self::$configCache;
    }

    /**
     * @return list<array{id: string, name: string, email: string, signature_file: string}>
     */
    public static function members(): array
    {
        return self::config()['members'];
    }

    /**
     * @return list<string>
     */
    public static function emails(): array
    {
        return self::config()['emails'];
    }

    public static function findMemberByEmail(string $email): ?array
    {
        $email = strtolower(trim($email));
        foreach (self::members() as $member) {
            if ($member['email'] === $email) {
                return $member;
            }
        }

        return null;
    }

    public static function displayNameForEmail(?string $email): string
    {
        $email = strtolower(trim((string) $email));
        if ($email === '') {
            return '—';
        }
        $member = self::findMemberByEmail($email);

        return $member['name'] ?? $member['id'] ?? $email;
    }

    public static function roleForEmail(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));
        if ($email === '') {
            return null;
        }

        $role = trim((string) (self::findMemberByEmail($email)['role'] ?? ''));

        return $role !== '' ? $role : null;
    }

    /**
     * @return list<array{id: string, name: string, email: string, signature_file: string}>
     */
    public static function memberOptions(): array
    {
        return self::members();
    }

    /**
     * @return list<string>
     */
    public static function signaturesDirectories(): array
    {
        $dirs = config('onboarding.hr_signatures_dirs', []);
        if ($dirs === [] || $dirs === null) {
            $legacy = config('onboarding.hr_signatures_dir');
            $dirs = $legacy ? [$legacy] : [public_path('hr-signatures')];
        }

        $resolved = [];
        foreach ((array) $dirs as $dir) {
            $dir = rtrim((string) $dir, DIRECTORY_SEPARATOR . '/\\');
            if ($dir !== '' && is_dir($dir)) {
                $resolved[] = $dir;
            }
        }

        return array_values(array_unique($resolved));
    }

    /**
     * Absolute path to signature image file for a member / email.
     */
    public static function resolveSignaturePath(?array $member, string $email = ''): ?string
    {
        foreach (self::signaturePathCandidates($member, $email) as $path) {
            if (is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Public URL for web previews (HR panel, SR-HR approval page).
     */
    public static function signaturePublicUrl(?array $member, string $email = ''): ?string
    {
        $path = self::resolveSignaturePath($member, $email);
        if (!$path) {
            return null;
        }

        $publicRoot = rtrim(str_replace('\\', '/', public_path()), '/');
        $normalized = str_replace('\\', '/', $path);

        if (str_starts_with($normalized, $publicRoot . '/')) {
            return PublicAssetUrl::url(ltrim(substr($normalized, strlen($publicRoot)), '/'));
        }

        $storagePublic = rtrim(str_replace('\\', '/', storage_path('app/public')), '/');
        if (str_starts_with($normalized, $storagePublic . '/')) {
            return PublicAssetUrl::url('storage/' . ltrim(substr($normalized, strlen($storagePublic)), '/'));
        }

        return null;
    }

    public static function signatureDataUriForEmail(string $email): ?string
    {
        $member = self::findMemberByEmail($email);
        $path = self::resolveSignaturePath($member, $email);
        if (!$path) {
            return null;
        }

        return self::dataUriFromPath($path);
    }

    public static function dataUriFromPath(string $path): ?string
    {
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';
        if (!in_array($mime, ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp'], true)) {
            $mime = 'image/png';
        }

        return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($path));
    }

    /**
     * Load signature from stored relative ref (e.g. hr-signatures/hr-2.png).
     */
    public static function dataUriFromSignatureRef(string $ref): ?string
    {
        $path = self::absolutePathFromSignatureRef($ref);

        return $path ? self::dataUriFromPath($path) : null;
    }

    /**
     * Store-friendly relative path for DB (under public/ or storage/app/public/).
     */
    public static function relativeSignatureRef(string $absolutePath): ?string
    {
        $normalized = str_replace('\\', '/', $absolutePath);

        $publicRoot = rtrim(str_replace('\\', '/', public_path()), '/');
        if (str_starts_with($normalized, $publicRoot . '/')) {
            return ltrim(substr($normalized, strlen($publicRoot)), '/');
        }

        $storagePublic = rtrim(str_replace('\\', '/', storage_path('app/public')), '/');
        if (str_starts_with($normalized, $storagePublic . '/')) {
            return 'storage/' . ltrim(substr($normalized, strlen($storagePublic)), '/');
        }

        return basename($normalized);
    }

    /**
     * @return list<string>
     */
    protected static function signaturePathCandidates(?array $member, string $email): array
    {
        $candidates = [];

        if ($member) {
            if (!empty($member['signature_file'])) {
                $fromRef = self::absolutePathFromSignatureRef($member['signature_file']);
                if ($fromRef) {
                    $candidates[] = $fromRef;
                }
            }
            if (!empty($member['id'])) {
                foreach (self::signaturesDirectories() as $dir) {
                    foreach (['png', 'jpg', 'jpeg'] as $ext) {
                        $candidates[] = $dir . DIRECTORY_SEPARATOR . $member['id'] . '.' . $ext;
                    }
                }
            }
        }

        $slug = preg_replace('/[^a-z0-9._-]+/i', '_', str_replace('@', '_at_', strtolower(trim($email))));
        if ($slug !== '') {
            foreach (self::signaturesDirectories() as $dir) {
                $candidates[] = $dir . DIRECTORY_SEPARATOR . $slug . '.png';
                $candidates[] = $dir . DIRECTORY_SEPARATOR . $slug . '.jpg';
            }
        }

        return array_values(array_unique($candidates));
    }

    /**
     * Public URL from stored ref (hr-signatures/hr-2.png, /hr-signatures/hr-2.png, storage/...).
     */
    public static function publicUrlFromSignatureRef(string $ref): ?string
    {
        $path = self::absolutePathFromSignatureRef($ref);
        if (!$path) {
            return null;
        }

        $publicRoot = rtrim(str_replace('\\', '/', public_path()), '/');
        $normalized = str_replace('\\', '/', $path);

        if (str_starts_with($normalized, $publicRoot . '/')) {
            return PublicAssetUrl::url(ltrim(substr($normalized, strlen($publicRoot)), '/'));
        }

        $storagePublic = rtrim(str_replace('\\', '/', storage_path('app/public')), '/');
        if (str_starts_with($normalized, $storagePublic . '/')) {
            return PublicAssetUrl::url('storage/' . ltrim(substr($normalized, strlen($storagePublic)), '/'));
        }

        return null;
    }

    protected static function absolutePathFromSignatureRef(string $ref): ?string
    {
        $ref = trim(str_replace('\\', '/', $ref));
        if ($ref === '') {
            return null;
        }

        if (is_file($ref)) {
            return $ref;
        }

        $basename = basename($ref);
        $candidates = [];

        if (str_starts_with($ref, '/')) {
            $candidates[] = public_path(ltrim($ref, '/'));
        }

        if (str_starts_with($ref, 'storage/')) {
            $candidates[] = public_path($ref);
            $candidates[] = storage_path('app/public/' . substr($ref, strlen('storage/')));
        }

        $candidates[] = storage_path('app/public/' . ltrim($ref, '/'));

        foreach (self::signaturesDirectories() as $dir) {
            $candidates[] = $dir . DIRECTORY_SEPARATOR . $basename;
            $candidates[] = $dir . DIRECTORY_SEPARATOR . ltrim($ref, '/');
        }

        foreach (array_unique($candidates) as $path) {
            if (is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        return null;
    }
}
