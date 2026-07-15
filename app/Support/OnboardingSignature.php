<?php

namespace App\Support;

use Illuminate\Http\Request;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class OnboardingSignature
{
    public static function fromRequest(Request $request): ?string
    {
        if (!empty($request->signature)) {
            return $request->signature;
        }

        if ($request->hasFile('signature_file')) {
            $request->validate(['signature_file' => OnboardingFileRules::signatureFileRule()]);
            $file = $request->file('signature_file');
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file->getPathname());

            try {
                $image = $image->trim();
            } catch (\Exception $e) {
            }

            $image = $image->toPng();

            return 'data:image/png;base64,' . base64_encode($image->toString());
        }

        return null;
    }
}
