<?php

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('display_errors', '0');

require __DIR__.'/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$path = $argv[1] ?? null;

if (!$path || !is_file($path)) {
    fwrite(STDERR, "Missing XLSX path.\n");
    exit(1);
}

$spreadsheet = IOFactory::load($path);
$values = [];

foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
    foreach ($sheet->toArray(null, true, true, true) as $row) {
        foreach ($row as $value) {
            if ($value !== null && $value !== '') {
                $values[] = (string) $value;
            }
        }
    }
}

echo json_encode($values, JSON_THROW_ON_ERROR);
