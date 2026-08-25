<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\workspace.php';
$content = file_get_contents($file);

// Replace the return condition that skips unchecked rows entirely and map it cleanly to `is_excluded` variable
$pattern = '/if \(\!tr\.find\(\'\.emp-checkbox\'\)\.is\(\':checked\'\)\) return;/';
$replacement = 'var isExcluded = tr.find(\'.emp-checkbox\').is(\':checked\') ? 0 : 1;';
$content = preg_replace($pattern, $replacement, $content);

// Ensure is_excluded gets pushed inside rowData
$patternPush = '/attendance_remarks:\s*attRemarks,\r?\n\s*details:\s*dynamicDetails/';
$replacementPush = "attendance_remarks: attRemarks,\r\n                        is_excluded: isExcluded,\r\n                        details: dynamicDetails";
$content = preg_replace($patternPush, $replacementPush, $content);

file_put_contents($file, $content);
echo "Frontend mapping array updated with is_excluded tracking.";
