<?php

namespace App\Support;

class LetterheadAssets
{
    public static function imageSrc(string $filename, bool $forBrowser = false): string
    {
        $relative = 'assets/letterhead/' . ltrim($filename, '/');

        if ($forBrowser) {
            return PublicAssetUrl::url($relative);
        }

        $absolute = public_path($relative);
        if (!is_file($absolute)) {
            $fallback = base_path('user-form/Offer-letter/assest/' . basename($filename));
            if (is_file($fallback)) {
                $absolute = $fallback;
            }
        }

        if (!is_file($absolute)) {
            return $relative;
        }

        $mime = mime_content_type($absolute) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($absolute));
    }
}
