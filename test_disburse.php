<?php
$url = 'http://localhost/TPHL-ERP/disburse-batch-payslips';
$data = ['payslip_ids' => [3, 4], 'bank_account_id' => 1];

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\nCookie: PHPSESSID=test\r\n",
        'method'  => 'POST',
        'content' => json_encode($data)
    ]
];
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);
echo "Result:\n" . $result;
