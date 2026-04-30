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
    $smtpHost     = 'smtp.gmail.com';
    $smtpPort     = 587;
    $senderEmail  = 'info@dpremiumhomes.com';
    $senderName   = 'TPHL ERP System';
    $clientId     = '893215055562-pbt3j7lg7ufqju370ltss46kh7pati3n.apps.googleusercontent.com';
    $clientSecret = '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';
    $refreshToken = '1//04tQCZJUfNcm7CgYIARAAGAQSNwF-L9IrFjuXh9BJ80IcIEKsg8h5m5TFRRQS6BQTt-wwIvtJKnUHd4sAtITIw8QJ0pgbijIOF0w';

    $provider = new Google([
        'clientId'     => $clientId,
        'clientSecret' => $clientSecret,
    ]);

    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $toEmail = $row['to_email'];
        $subject = $row['subject'];
        $htmlBody = $row['body'];

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->Port       = $smtpPort;
            $mail->SMTPAuth   = true;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            
            $mail->AuthType = 'XOAUTH2';
            $mail->setOAuth(new OAuth([
                'provider'     => $provider,
                'clientId'     => $clientId,
                'clientSecret' => $clientSecret,
                'refreshToken' => $refreshToken,
                'userName'     => $senderEmail,
            ]));

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
