<?php
ignore_user_abort(true);
set_time_limit(0);

// Set error reporting to avoid revealing issues in AJAX
error_reporting(0);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';

// We need PHPMailer
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\OAuth;
use League\OAuth2\Client\Provider\Google;

$config = require __DIR__ . "/../app/config/config.php";
$database = new Database($config);
$db = $database->conn;

// Lock 5 pending emails
$db->query("UPDATE email_queue SET status = 'processing' WHERE status = 'pending' LIMIT 5");
$result = $db->query("SELECT * FROM email_queue WHERE status = 'processing'");

if ($result && $result->num_rows > 0) {
    // Mail configuration
    $smtpHost     = $config['smtp_host'] ?? 'smtp.gmail.com';
    $smtpPort     = $config['smtp_port'] ?? 465;
    $senderEmail  = $config['smtp_user'] ?? 'no-reply@dpremiumhomes.com';
    $smtpPass     = $config['smtp_pass'] ?? '';
    $smtpSecure   = $config['smtp_secure'] ?? 'ssl';
    $senderName   = 'TPHL ERP System';

    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $toEmail = $row['to_email'];
        $subject = $row['subject'];
        $htmlBody = $row['body'];

        try {
            $mail = new PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->Port       = $smtpPort;
            $mail->SMTPAuth   = true;
            $mail->Username   = $senderEmail;
            $mail->Password   = $smtpPass;
            $mail->SMTPSecure = (strtolower($smtpSecure) === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;

            $mail->setFrom($senderEmail, $senderName);
            $mail->addAddress($toEmail);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>'], "\n", $htmlBody));

            $mail->send();
            
            // Mark as sent
            $db->query("UPDATE email_queue SET status = 'sent' WHERE id = $id");
        } catch (Exception $e) {
            error_log("[ProjectWorkflowMailService] Failed to send to {$toEmail}: " . $e->getMessage());
            // Mark as failed
            $db->query("UPDATE email_queue SET status = 'failed' WHERE id = $id");
        }
    }
}
