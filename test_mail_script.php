<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/app/core/MailServiceForPHPMailer.php';
$m = new MailService();
$assignedBy = [
    'full_name' => 'System Tester',
    'designation_name' => 'Dev',
    'department_name' => 'IT',
    'role_name' => 'Admin'
];
$r = $m->sendMail('farzana.tphl13@gmail.com', 'Local ERP Test Email', 'http://localhost/TPHL-ERP', $assignedBy);
if ($r) {
    echo "SUCCESS";
} else {
    echo "FAILED: " . $m->lastError;
}
