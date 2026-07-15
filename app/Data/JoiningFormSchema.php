<?php

namespace App\Data;

class JoiningFormSchema
{
    protected static ?array $cache = null;

    public static function get(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $path = base_path('form_join/joining-form-schema.json');

        if (!is_file($path)) {
            return self::$cache = ['sections' => []];
        }

        return self::$cache = json_decode(file_get_contents($path), true) ?: ['sections' => []];
    }

    public static function section(string $key): ?array
    {
        foreach (self::get()['sections'] ?? [] as $section) {
            if (($section['key'] ?? '') === $key) {
                return $section;
            }
        }

        return null;
    }

    /** @return list<string> */
    public static function requiredFieldNames(?array $section): array
    {
        if (!$section) {
            return [];
        }

        $names = [];
        foreach (self::fieldsFromSection($section) as $field) {
            if (!empty($field['required']) && !empty($field['name'])) {
                $names[] = (string) $field['name'];
            }
        }

        return $names;
    }

    /** @return array<string, string> */
    public static function fieldLabels(?array $section): array
    {
        $labels = [];
        foreach (self::fieldsFromSection($section ?? []) as $field) {
            if (!empty($field['name'])) {
                $labels[(string) $field['name']] = (string) ($field['label'] ?? $field['name']);
            }
        }

        return $labels;
    }

    /** @return list<array<string, mixed>> */
    public static function fieldsFromSection(array $section): array
    {
        $fields = [];

        foreach ($section['fields'] ?? [] as $field) {
            if (is_array($field)) {
                $fields[] = $field;
            }
        }

        foreach ($section['groups'] ?? [] as $group) {
            if (!is_array($group)) {
                continue;
            }
            foreach ($group['fields'] ?? [] as $field) {
                if (is_array($field)) {
                    $fields[] = $field;
                }
            }
            foreach ($group['rows'] ?? [] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                foreach ($row['fields'] ?? [] as $field) {
                    if (is_array($field)) {
                        $fields[] = $field;
                    }
                }
            }
        }

        foreach ($section['employee_fields'] ?? [] as $field) {
            if (is_array($field)) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    public static function sectionCount(): int
    {
        return count(self::get()['sections'] ?? []);
    }
}
