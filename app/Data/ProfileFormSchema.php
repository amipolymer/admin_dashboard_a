<?php

namespace App\Data;

class ProfileFormSchema
{
    public static function get(): array
    {
        $path = base_path('form_join/profile-form-schema.json');

        if (!is_file($path)) {
            return ['sections' => []];
        }

        $schema = json_decode(file_get_contents($path), true) ?: ['sections' => []];
        $consentText = trim((string) config('services.ongrid.consent_text', ''));
        if ($consentText !== '') {
            foreach ($schema['sections'] ?? [] as $i => $section) {
                if (($section['key'] ?? '') !== 'declaration') {
                    continue;
                }
                if (isset($schema['sections'][$i]['fields']['consent_text'])) {
                    $schema['sections'][$i]['fields']['consent_text']['default'] = $consentText;
                }
            }
        }

        return $schema;
    }

    public static function sectionCount(): int
    {
        return count(self::get()['sections'] ?? []);
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

    public static function fieldLabel(string $sectionKey, string $fieldName): string
    {
        $section = self::section($sectionKey);
        if (!$section) {
            return ucwords(str_replace('_', ' ', $fieldName));
        }

        $fields = $section['fields'] ?? [];
        if (isset($fields[$fieldName]['label'])) {
            return (string) $fields[$fieldName]['label'];
        }

        foreach ($fields as $field) {
            if (is_array($field) && ($field['name'] ?? '') === $fieldName) {
                return (string) ($field['label'] ?? $fieldName);
            }
        }

        foreach ($section['groups'] ?? [] as $group) {
            foreach ($group['fields'] ?? [] as $field) {
                if (is_array($field) && ($field['name'] ?? '') === $fieldName) {
                    return (string) ($field['label'] ?? $fieldName);
                }
            }
        }

        return ucwords(str_replace('_', ' ', $fieldName));
    }
}
