<?php

namespace App\Support;

use App\Models\RouteURLList;
use Illuminate\Support\Facades\Route;

class RouteRegistrySync
{
    /**
     * Import Laravel routes into route_u_r_l_lists.
     * New paths are inserted; existing rows are left unchanged (keeps manual title edits).
     *
     * @return array{created: int, skipped: int, total_in_db: int}
     */
    public static function syncFromApplication(): array
    {
        $created = 0;
        $skipped = 0;

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();

            if (str_starts_with($uri, '_') || str_contains($uri, 'debugbar')) {
                continue;
            }

            $title = self::titleFromRoute($route->getName(), $uri);

            $record = RouteURLList::firstOrCreate(
                ['url_name' => $uri],
                ['title' => $title]
            );

            if ($record->wasRecentlyCreated) {
                $created++;
            } else {
                $skipped++;
            }
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'total_in_db' => RouteURLList::count(),
        ];
    }

    protected static function titleFromRoute(?string $routeName, string $uri): string
    {
        if ($routeName) {
            $title = implode('-', explode('.', $routeName));
        } else {
            $segments = collect(explode('/', $uri))
                ->filter(fn ($segment) => !in_array($segment, ['dashboard', 'admin'], true))
                ->values();
            $title = $segments->implode('-');
        }

        $titleParts = array_unique(explode('-', $title));

        return implode('-', $titleParts) ?: $uri;
    }
}
