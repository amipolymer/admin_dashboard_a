<?php

namespace App\Data;

class LevelWiseGrading
{
    protected static ?array $entriesCache = null;

    /**
     * @return list<array{grade: string, category: string, designations: list<string>}>
     */
    public static function entries(): array
    {
        if (self::$entriesCache !== null) {
            return self::$entriesCache;
        }

        $path = base_path('form_join/Level-wise-grading.json');
        if (!is_file($path)) {
            self::$entriesCache = [];

            return self::$entriesCache;
        }

        $rows = json_decode((string) file_get_contents($path), true) ?: [];
        $entries = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $grade = trim((string) ($row['grade'] ?? ''));
            $category = trim((string) ($row['category'] ?? ''));
            if ($grade === '' || $category === '') {
                continue;
            }
            $designations = array_values(array_filter(array_map(
                fn ($d) => trim((string) $d),
                $row['designations'] ?? []
            )));
            $entries[] = [
                'grade' => $grade,
                'category' => $category,
                'designations' => $designations,
            ];
        }

        self::$entriesCache = $entries;

        return self::$entriesCache;
    }

    public static function optionValue(string $grade, string $category): string
    {
        return $grade . '|' . $category;
    }

    /**
     * @return list<array{value: string, label: string, grade: string, category: string, designations: list<string>}>
     */
    public static function selectOptions(): array
    {
        $options = [];
        foreach (self::entries() as $entry) {
            $value = self::optionValue($entry['grade'], $entry['category']);
            $options[] = [
                'value' => $value,
                'label' => $entry['grade'] . ' — ' . $entry['category'],
                'grade' => $entry['grade'],
                'category' => $entry['category'],
                'designations' => $entry['designations'],
            ];
        }

        return $options;
    }

    /**
     * @return array{grade: string, category: string, designations: list<string>}|null
     */
    public static function findByValue(?string $value): ?array
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (self::entries() as $entry) {
            if (self::optionValue($entry['grade'], $entry['category']) === $value) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public static function designationsFor(?string $grade, ?string $category): array
    {
        $grade = trim((string) $grade);
        $category = trim((string) $category);
        if ($grade === '' || $category === '') {
            return [];
        }

        foreach (self::entries() as $entry) {
            if ($entry['grade'] === $grade && $entry['category'] === $category) {
                return $entry['designations'];
            }
        }

        return [];
    }

    public static function isValidDesignation(?string $grade, ?string $category, ?string $designation): bool
    {
        $designation = trim((string) $designation);
        if ($designation === '') {
            return false;
        }

        return in_array($designation, self::designationsFor($grade, $category), true);
    }

    /**
     * @return array{grade: string, category: string, designations: list<string>}|null
     */
    public static function findByGradeAndCategory(?string $grade, ?string $category): ?array
    {
        return self::findByValue(self::optionValue((string) $grade, (string) $category));
    }
}
