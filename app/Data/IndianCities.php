<?php

namespace App\Data;

class IndianCities
{
    protected static ?array $entriesCache = null;

    /**
     * @return list<array{city: string, state: string}>
     */
    public static function entries(): array
    {
        if (self::$entriesCache !== null) {
            return self::$entriesCache;
        }

        $entries = [];
        foreach ([
            base_path('form_join/cities.json'),
            base_path('form_join/cities-custom.json'),
        ] as $path) {
            if (!is_file($path)) {
                continue;
            }
            $rows = json_decode((string) file_get_contents($path), true) ?: [];
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $city = trim((string) ($row['city'] ?? ''));
                $state = trim((string) ($row['state'] ?? ''));
                if ($city === '' || $state === '') {
                    continue;
                }
                $entries[] = ['city' => $city, 'state' => $state];
            }
        }

        usort($entries, static function (array $a, array $b): int {
            $cmp = strcasecmp($a['state'], $b['state']);

            return $cmp !== 0 ? $cmp : strcasecmp($a['city'], $b['city']);
        });

        self::$entriesCache = $entries;

        return self::$entriesCache;
    }

    /**
     * @return list<string>
     */
    public static function states(): array
    {
        $states = array_values(array_unique(array_map(
            static fn (array $row) => $row['state'],
            self::entries()
        )));
        sort($states, SORT_NATURAL | SORT_FLAG_CASE);

        return $states;
    }

    /**
     * @return array<string, list<string>>
     */
    public static function citiesByState(): array
    {
        $map = [];
        foreach (self::entries() as $row) {
            $map[$row['state']] ??= [];
            if (!in_array($row['city'], $map[$row['state']], true)) {
                $map[$row['state']][] = $row['city'];
            }
        }

        foreach ($map as $state => $cities) {
            sort($cities, SORT_NATURAL | SORT_FLAG_CASE);
            $map[$state] = $cities;
        }

        return $map;
    }

    /**
     * @return array{states: list<string>, cities: array<string, list<string>>}
     */
    public static function forFrontend(): array
    {
        return [
            'states' => self::states(),
            'cities' => self::citiesByState(),
        ];
    }

    public static function addCustom(string $state, string $city): bool
    {
        $state = trim($state);
        $city = trim($city);
        if ($state === '' || $city === '') {
            return false;
        }

        foreach (self::entries() as $row) {
            if (strcasecmp($row['state'], $state) === 0 && strcasecmp($row['city'], $city) === 0) {
                return true;
            }
        }

        $path = base_path('form_join/cities-custom.json');
        $custom = [];
        if (is_file($path)) {
            $custom = json_decode((string) file_get_contents($path), true) ?: [];
        }
        $custom[] = ['city' => $city, 'state' => $state];

        file_put_contents(
            $path,
            json_encode($custom, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n"
        );

        self::$entriesCache = null;

        return true;
    }
}
