<?php
ignore_user_abort(true);
set_time_limit(0);

// Suppress output so background execution doesn't leak to caller
error_reporting(0);

$config = require __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$database = new Database($config);
$db = $database->conn;

// Lock up to 50 pending emails for processing
$db->query("UPDATE email_queue SET status = 'processing' WHERE status = 'pending' LIMIT 50");
$result = $db->query("SELECT * FROM email_queue WHERE status = 'processing'");

if ($result && $result->num_rows > 0) {
    $smtpHost    = $config['smtp_host'] ?? 'smtp.gmail.com';
    $smtpPort    = (int)($config['smtp_port'] ?? 465);
    $senderEmail = $config['smtp_user'] ?? 'no-reply@dpremiumhomes.com';
    $smtpPass    = $config['smtp_pass'] ?? 'djcdawaxdbmdotlj';
    $smtpSecure  = $config['smtp_secure'] ?? 'ssl';
    $senderName  = 'TPHL ERP System';

    $mail = new PHPMailer(true);
    try {
        $mail->CharSet   = 'UTF-8';
        $mail->isSMTP();
        $mail->Host      = $smtpHost;
        $mail->Port      = $smtpPort;
        $mail->SMTPAuth  = true;
        $mail->Username  = $senderEmail;
        $mail->Password  = $smtpPass;
        $mail->SMTPSecure = (strtolower($smtpSecure) === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->SMTPKeepAlive = true; // Keep connection alive for fast multi-send
        $mail->Timeout   = 5;
        $mail->setFrom($senderEmail, $senderName);

        while ($row = $result->fetch_assoc()) {
            $id = (int)$row['id'];
            $toEmail = trim($row['to_email']);
            $subject = $row['subject'];
            $htmlBody = $row['body'];

            if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
                $db->query("UPDATE email_queue SET status = 'failed' WHERE id = {$id}");
                continue;
            }

            try {
                $mail->clearAddresses();
                $mail->clearCCs();
                $mail->clearBCCs();
                $mail->clearAttachments();

                $mail->addAddress($toEmail);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $htmlBody;
                $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '</p>'], ["\n", "\n", "\n\n"], $htmlBody));

                $mail->send();
                $db->query("UPDATE email_queue SET status = 'sent' WHERE id = {$id}");
            } catch (Exception $e) {
                error_log("[EmailQueue] Failed to send ID {$id} to {$toEmail}: " . $e->getMessage());
                $db->query("UPDATE email_queue SET status = 'failed' WHERE id = {$id}");
            }
        }

        $mail->smtpClose();
    } catch (\Throwable $e) {
        error_log("[EmailQueue] SMTP Connection error: " . $e->getMessage());
        $db->query("UPDATE email_queue SET status = 'failed' WHERE status = 'processing'");
    }
}
