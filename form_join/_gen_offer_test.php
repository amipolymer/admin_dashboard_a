<?php

/**
 * Regenerate form_join/Offer_Letter_Test_43.pdf for layout checks.
 * Usage: php form_join/_gen_offer_test.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$e = \App\Models\EmployeesNewJoiner::find(43);
if (!$e) {
    fwrite(STDERR, "Employee 43 not found\n");
    exit(1);
}

$html = view('pdf.offer_letter_pdf', [
    'employee' => $e,
    'offer' => \App\Support\OnboardingLetterData::offer($e),
    'signatureAsUrl' => false,
    'testSimulateAccepted' => true,
    'testPlaceholderSignature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAAFUlEQVR42mNk+M9Qz0AEYBxVSF+FABJADveWkH6oAAAAAElFTkSuQmCC',
])->render();

$pdf = \App\Support\OnboardingLetterPdf::fromHtml($html);
$out = __DIR__ . '/Offer_Letter_Test_43.pdf';
file_put_contents($out, $pdf);

preg_match_all('/\/Type\s*\/Page[^s]/', $pdf, $m);
echo "Written: {$out}\n";
echo 'Pages: ' . count($m[0]) . "\n";
