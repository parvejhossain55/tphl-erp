<?php
require 'vendor/autoload.php';

$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
$reader->setReadDataOnly(true);
$reader->setReadFilter(new class implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter {
    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        if ($row <= 3) {
            return true;
        }
        return false;
    }
});

try {
    $spreadsheet = $reader->load('payroll-sample.xlsx');
    $sheet = $spreadsheet->getActiveSheet();
    $data = $sheet->toArray(null, true, true, true);
    file_put_contents('excel_headers.json', json_encode(array_values($data), JSON_PRETTY_PRINT));
    echo "done";
} catch (Exception $e) {
    echo "Error loading file: " . $e->getMessage();
}
