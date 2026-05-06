<?php
$headers = [
    'Payment Type', 'Project Name', 'Requested By', 'Amount', 'Date', 'Status',
    'Purpose / Description', 'Vendor Name', 'PO Number', 'Invoice Number',
    'Operator Name', 'File Attachment', 'Payment Method', 'Paid Amount'
];

$sampleData = [
    ['General', 'The Premium Signature Heights', '1', '5000', date('Y-m-d'), 'Fully Paid', 'Office Supplies', '', '', '', '', '', 'BankTransfer', '5000'],
    ['Vendor', 'The Premium Signature Heights', '1', '15000', date('Y-m-d'), 'Paid', 'Hardware supplies', 'Global Tech Supplies', '123', 'INV-001', '', '', 'Cheque', '15000'],
    ['Contractor', 'The Premium Signature Heights', '1', '20000', date('Y-m-d'), 'Paid', 'Foundation Work', '', '', '', 'Alpha Builders', '', 'Cash', '20000'],
];

$fp = fopen('dummy_cashouts_test.csv', 'w');
fputcsv($fp, $headers);
foreach ($sampleData as $row) {
    fputcsv($fp, $row);
}
fclose($fp);
echo "CSV created successfully.\n";
?>
