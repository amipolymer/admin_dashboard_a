<?php

/** One-off: dump plain text from user-form/Joining_Forms_word-PDF for gap analysis. */
$dir = dirname(__DIR__) . '/user-form/Joining_Forms_word-PDF';
$out = dirname(__DIR__) . '/storage/app/joining-forms-extract.txt';

$files = glob($dir . '/*') ?: [];
sort($files);
$buf = '';

foreach ($files as $path) {
    $name = basename($path);
    $buf .= str_repeat('=', 72) . "\nFILE: {$name}\n" . str_repeat('=', 72) . "\n";

    if (str_ends_with(strtolower($name), '.docx')) {
        $zip = new ZipArchive();
        if ($zip->open($path) === true) {
            $xml = $zip->getFromName('word/document.xml') ?: '';
            $zip->close();
            $text = preg_replace('/<w:tab[^\/]*\/>/', "\t", $xml);
            $text = preg_replace('/<\/w:p>/', "\n", $text);
            $text = strip_tags($text);
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $buf .= preg_replace("/\n{3,}/", "\n\n", $text) . "\n";
        } else {
            $buf .= "Could not open docx\n";
        }
        continue;
    }

    if (str_ends_with(strtolower($name), '.pdf')) {
        $buf .= "(PDF — install pdftotext or pypdf for full extract; filename only)\n";
        continue;
    }
}

file_put_contents($out, $buf);
echo "Wrote " . strlen($buf) . " bytes to {$out}\n";
