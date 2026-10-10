<?php

require_once __DIR__ . '/MailService.php';

class TodoMailService
{
    private $db;
    private $mailService;

    public function __construct($db)
    {
        $this->db = $db;
        $this->mailService = new MailService();
    }

    public function send($toEmail, $subject, $body)
    {
        try {
            // Send directly via PHPMailer / SMTP
            $sent = $this->mailService->sendGenericMail($toEmail, $subject, $body);

            // Also record in email_queue for logging/audit trail
            $status = $sent ? 'sent' : 'failed';
            $stmt = $this->db->prepare("INSERT INTO email_queue (to_email, subject, body, status) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("ssss", $toEmail, $subject, $body, $status);
                $stmt->execute();
                $stmt->close();
            }

            return $sent;
        } catch (Exception $e) {
            error_log("[TodoMailService] Failed to send email to {$toEmail}: " . $e->getMessage());
            return false;
        }
    }
}
