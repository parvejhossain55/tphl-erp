<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/app/config/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendTestMail($to, $subject, $body, $config) {
    try {
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8'; // This fixes the dash issue
        
        $mail->isSMTP();
        $mail->Host       = $config['smtp_host'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['smtp_user'] ?? 'no-reply@dpremiumhomes.com';
        $mail->Password   = $config['smtp_pass'] ?? '';
        $mail->SMTPSecure = (strtolower($config['smtp_secure'] ?? 'ssl') === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $config['smtp_port'] ?? 465;

        $mail->setFrom($config['smtp_user'], 'TPHL ERP System');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        echo "Successfully sent to {$to}: {$subject}<br>\n";
    } catch (Exception $e) {
        echo "Failed to send to {$to}: {$mail->ErrorInfo}<br>\n";
    }
}

$to = 'farzana.tphl13@gmail.com';

// Test 1: Purchase Request Approval
$subject1 = "Action Required: Purchase Request Approval — The Premium Breeze Residence";
$body1 = "<h2>This is a test email to verify that the dash (—) character is not broken in the subject line.</h2><p>Please check the subject line of this email.</p>";
sendTestMail($to, $subject1, $body1, $config);

// Test 2: Fund Requisition
$subject2 = "Action Required: Fund Requisition — Pending Management Approval";
$body2 = "<h2>This is a test email to verify that the dash (—) character is not broken in the subject line.</h2><p>Please check the subject line of this email.</p>";
sendTestMail($to, $subject2, $body2, $config);

echo "Done.";
?>
