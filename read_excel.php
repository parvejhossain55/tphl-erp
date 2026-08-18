<?php
require_vendor();
function require_vendor()
{
    $vendorPath = __DIR__ . '/vendor/autoload.php';
    if (file_exists($vendorPath)) {
        require_once $vendorPath;
    }
}
use PhpOffice\PhpSpreadsheet\IOFactory;

$spreadsheet = IOFactory::load(__DIR__ . '/payroll-sample.xlsx');
$sheetNames = $spreadsheet->getSheetNames();
echo "Tabs in Excel File:\n";
print_r($sheetNames);

if (in_array('Attendance', $sheetNames) || in_array('attendance', $sheetNames) || stripos(implode(',', $sheetNames), 'attend') !== false) {
    foreach ($sheetNames as $name) {
        if (stripos($name, 'attend') !== false) {
            echo "\nData from sheet: $name\n";
            $sheet = $spreadsheet->getSheetByName($name);
            $data = $sheet->toArray();
            for ($i = 0; $i < min(3, count($data)); $i++) {
                print_r($data[$i]);
            }
        }
    }
}
?>