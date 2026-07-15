<?php

namespace App\Support;

use App\Models\EmployeesNewJoiner;
use App\Models\NewEmployeesDocument;

/** Dynamic Aadhaar upload types for mediclaim dependents (from joining form). */
class OnboardingMediclaimDocuments
{
    public const KEY_PREFIX = 'aadhaar_card_';

    public const GROUP = 'Mediclaim';

    /** Mediclaim Aadhaar uploads are only available after the joining form is submitted. */
    public static function isPostJoiningSubmit(EmployeesNewJoiner $employee): bool
    {
        return in_array($employee->onboardingStep(), [
            'join_forms_submitted',
            'policy_signed',
            'appointment_sent',
            'appointment_accepted',
            'appointment_pending_sr_hr',
            'appointment_sr_rejected',
            'appointment_rejected',
            'end',
        ], true);
    }

    /** @return list<array{name: string, dob: string, relationship: string, slug: string}> */
    public static function dependents(EmployeesNewJoiner $employee): array
    {
        $rows = ($employee->emp_joining_requirements ?? [])['mediclaim_dependents'] ?? [];

        if (!is_array($rows)) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $slug = trim((string) ($row['slug'] ?? ''));
            if ($slug === '') {
                $slug = self::slugFromName($name);
            }
            $out[] = [
                'name' => $name,
                'dob' => trim((string) ($row['dob'] ?? '')),
                'relationship' => trim((string) ($row['relationship'] ?? '')),
                'slug' => $slug,
            ];
        }

        return $out;
    }

    public static function slugFromName(string $name, array $usedSlugs = []): string
    {
        $base = strtolower(trim($name));
        $base = preg_replace('/[^a-z0-9]+/', '_', $base) ?? '';
        $base = trim($base, '_');
        if ($base === '') {
            $base = 'member';
        }

        $slug = $base;
        $n = 2;
        while (in_array($slug, $usedSlugs, true)) {
            $slug = $base . '_' . $n;
            $n++;
        }

        return $slug;
    }

    public static function documentKey(array $dependent): string
    {
        $slug = trim((string) ($dependent['slug'] ?? ''));

        return self::KEY_PREFIX . ($slug !== '' ? $slug : self::slugFromName((string) ($dependent['name'] ?? 'member')));
    }

    public static function documentLabel(array $dependent): string
    {
        $name = trim((string) ($dependent['name'] ?? ''));

        return 'Aadhaar Card — ' . ($name !== '' ? $name : 'Dependent');
    }

    public static function isMediclaimAadhaarKey(string $key): bool
    {
        return str_starts_with($key, self::KEY_PREFIX);
    }

    /** @return list<string> */
    public static function documentKeys(EmployeesNewJoiner $employee): array
    {
        if (!self::isPostJoiningSubmit($employee)) {
            return [];
        }

        $keys = [];
        foreach (self::dependents($employee) as $dependent) {
            $keys[] = self::documentKey($dependent);
        }

        return $keys;
    }

    /** @return array<string, string> */
    public static function documentLabels(EmployeesNewJoiner $employee): array
    {
        if (!self::isPostJoiningSubmit($employee)) {
            return [];
        }

        $labels = [];
        foreach (self::dependents($employee) as $dependent) {
            $labels[self::documentKey($dependent)] = self::documentLabel($dependent);
        }

        return $labels;
    }

    public static function isAllowedKey(EmployeesNewJoiner $employee, string $key): bool
    {
        return in_array($key, self::documentKeys($employee), true);
    }

    public static function resolveLabel(EmployeesNewJoiner $employee, string $key): ?string
    {
        return self::documentLabels($employee)[$key] ?? null;
    }

    /** @param  array<string, array<string, string>>  $baseList */
    public static function mergeIntoCandidateUpload(array $baseList, EmployeesNewJoiner $employee): array
    {
        $labels = self::documentLabels($employee);
        if ($labels === []) {
            return $baseList;
        }

        $baseList[self::GROUP] = $labels;

        return $baseList;
    }

    /** @param  array<string, string>  $baseLabels */
    public static function mergeIntoLabels(array $baseLabels, EmployeesNewJoiner $employee): array
    {
        return array_merge($baseLabels, self::documentLabels($employee));
    }

    /**
     * @param  list<array<string, mixed>>|null  $existing
     * @return list<array{name: string, dob: string, relationship: string, slug: string}>
     */
    public static function normalizeDependents(mixed $rows, ?array $existing = null): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $existing = is_array($existing) ? $existing : [];
        $existingByName = [];
        foreach ($existing as $ex) {
            if (!is_array($ex)) {
                continue;
            }
            $name = strtolower(trim((string) ($ex['name'] ?? '')));
            if ($name === '') {
                continue;
            }
            $slug = trim((string) ($ex['slug'] ?? ''));
            $existingByName[$name] = $slug !== '' ? $slug : self::slugFromName((string) ($ex['name'] ?? ''));
        }

        $usedSlugs = [];
        $normalized = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            $dob = trim((string) ($row['dob'] ?? ''));
            $rel = trim((string) ($row['relationship'] ?? ''));
            if ($name === '' && $dob === '' && $rel === '') {
                continue;
            }

            $nameKey = strtolower($name);
            if (isset($existingByName[$nameKey]) && !in_array($existingByName[$nameKey], $usedSlugs, true)) {
                $slug = $existingByName[$nameKey];
            } else {
                $slug = self::slugFromName($name, $usedSlugs);
            }
            $usedSlugs[] = $slug;

            $normalized[] = [
                'name' => $name,
                'dob' => $dob,
                'relationship' => $rel,
                'slug' => $slug,
            ];
        }

        return $normalized;
    }

    /** Remove Aadhaar uploads for mediclaim members no longer listed in joining data. */
    public static function pruneOrphanDocuments(EmployeesNewJoiner $employee, array $normalizedDependents): void
    {
        $validKeys = array_flip(array_map(fn (array $row) => self::documentKey($row), $normalizedDependents));

        NewEmployeesDocument::query()
            ->where('emp_id', $employee->id)
            ->where('emp_select_document', 'like', self::KEY_PREFIX . '%')
            ->get()
            ->each(function (NewEmployeesDocument $doc) use ($validKeys) {
                if (!isset($validKeys[$doc->emp_select_document])) {
                    $doc->delete();
                }
            });
    }
}
