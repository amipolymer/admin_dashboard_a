<?php

namespace App\Support;

class PublicAssetUrl
{
    public static function url(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        $prefix = rtrim((string) config('app.asset_prefix', ''), '/');

        return url($prefix !== '' ? $prefix . '/' . $path : $path);
    }
}
