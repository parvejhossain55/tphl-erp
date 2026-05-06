<?php
// Simulate exactly what the import does
$conn = new mysqli('localhost', 'root', '', 'tphl_db');

$csvFile = __DIR__ . '/../dummy_cashouts_test.csv';

// Simulate parseAndValidateCSV header mapping
$handle = fopen($csvFile, 'r');
$headers = fgetcsv($handle);
$headerMap = [];
foreach ($headers as $i => $h) {
    $headerMap[trim($h)] = $i;
}

echo "Headers mapped: " . json_encode($headerMap) . "\n\n";

$rowNum = 1;
while (($row = fgetcsv($handle)) !== false) {
    $rowNum++;
    if (empty(array_filter($row))) continue;
    
    $paymentTypeRaw = trim($row[$headerMap['Payment Type']] ?? '');
    $paymentType = strtolower($paymentTypeRaw);
    
    echo "Row $rowNum: payment_type_raw='$paymentTypeRaw', payment_type='$paymentType'\n";
    
    if ($paymentType === 'vendor') {
        $vendorName = trim($row[$headerMap['Vendor Name']] ?? '');
        $poNumber = trim($row[$headerMap['PO Number']] ?? '');
        echo "  -> VENDOR detected: vendor='$vendorName', po='$poNumber'\n";
        
        // Check vendor exists
        $stmt = $conn->prepare("SELECT id FROM vendors WHERE name = ? LIMIT 1");
        $stmt->bind_param("s", $vendorName);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($v = $res->fetch_assoc()) {
            echo "  -> Vendor found: ID=" . $v['id'] . "\n";
        } else {
            echo "  -> *** VENDOR NOT FOUND ***\n";
        }
        $stmt->close();
    } elseif ($paymentType === 'contractor') {
        $operatorName = trim($row[$headerMap['Operator Name']] ?? '');
        echo "  -> CONTRACTOR detected: operator='$operatorName'\n";
        
        $stmt = $conn->prepare("SELECT id FROM contractors WHERE name = ? LIMIT 1");
        $stmt->bind_param("s", $operatorName);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($o = $res->fetch_assoc()) {
            echo "  -> Contractor found: ID=" . $o['id'] . "\n";
        } else {
            echo "  -> *** CONTRACTOR NOT FOUND ***\n";
        }
        $stmt->close();
    } else {
        echo "  -> GENERAL detected\n";
    }
}
fclose($handle);

// Now check what's actually in the tables after last import
echo "\n=== LAST 5 cashout_requests ===\n";
$res = $conn->query('SELECT id, purpose_paid_to, amount, status FROM cashout_requests ORDER BY id DESC LIMIT 5');
while($r = $res->fetch_assoc()) echo json_encode($r)."\n";

echo "\n=== LAST 5 vendor_payment_requests ===\n";
$res = $conn->query('SELECT id, vendor_id, invoice_amount, status FROM vendor_payment_requests ORDER BY id DESC LIMIT 5');
while($r = $res->fetch_assoc()) echo json_encode($r)."\n";

echo "\n=== LAST 5 contractor_bill_requests ===\n";
$res = $conn->query('SELECT id, contractor_id, bill_amount, status FROM contractor_bill_requests ORDER BY id DESC LIMIT 5');
while($r = $res->fetch_assoc()) echo json_encode($r)."\n";

$conn->close();
?>
