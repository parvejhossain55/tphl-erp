<?php
// app/core/TrackerMailService.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\OAuth;
use League\OAuth2\Client\Provider\Google;

class TrackerMailService
{
    private $db;
    
    private $senderEmail = 'no-reply@dpremiumhomes.com';
    private $senderName = 'TPHL ERP Notification';

    public function __construct($db)
    {
        $this->db = $db;
    }

    private function getMailer()
    {
        $config = require __DIR__ . '/../config/config.php';
        $smtpHost = $config['smtp_host'] ?? 'smtp.gmail.com';
        $smtpPort = $config['smtp_port'] ?? 465;
        $senderEmail = $config['smtp_user'] ?? 'no-reply@dpremiumhomes.com';
        $smtpPass = $config['smtp_pass'] ?? '';
        $smtpSecure = $config['smtp_secure'] ?? 'ssl';

        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->Port = $smtpPort;
        $mail->SMTPAuth = true;
        $mail->Username = $senderEmail;
        $mail->Password = $smtpPass;
        $mail->SMTPSecure = (strtolower($smtpSecure) === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;

        $mail->setFrom($senderEmail, $this->senderName);
        $mail->isHTML(true);

        return $mail;
    }

    public function notifyChampionsOfNewSession($topic, $meeting_date)
    {
        // Fetch only designated champions who have an email address
        $sql = "SELECT u.id, u.full_name, u.email 
                FROM erp_tracker_champions c
                JOIN users u ON c.user_id = u.id
                WHERE u.email IS NOT NULL AND u.email != ''";
        $result = $this->db->query($sql);
        if (!$result) return false;

        $subject = "ERP Tracker: New Weekly Session Created";
        $body = "
            <h3>New ERP Weekly Tracker Session</h3>
            <p>A new weekly tracking session has been created.</p>
            <p><strong>Topic:</strong> $topic</p>
            <p><strong>Date:</strong> $meeting_date</p>
            <p>Please log in to the ERP to fill out your department's action items and progress.</p>
        ";

        try {
            $mail = $this->getMailer();
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            // Add a generic main address to satisfy SMTP rules
            $mail->addAddress($this->senderEmail, $this->senderName);

            // BCC everyone else
            $count = 0;
            while ($user = $result->fetch_assoc()) {
                if (filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
                    $mail->addBCC($user['email'], $user['full_name']);
                    $count++;
                }
            }
            
            if ($count > 0) {
                $mail->send();
            }
        } catch (Exception $e) {
            error_log("TrackerMailService Error (Session Notify) - " . $mail->ErrorInfo);
        }
    }

    public function notifyTaskOwner($owner, $champion_name, $department_name, $topic, $action_name, $priority, $deadline)
    {
        if (empty($owner['email'])) return false;

        $subject = "Action Required: New Task Assigned for $topic";
        $body = "
            <div style='font-family: Arial, sans-serif;'>
                <p>Dear {$owner['full_name']},</p>
                
                <p>You have been assigned a new task by $champion_name for the $department_name department.</p>
                
                <h4>Task Details:</h4>
                <ul>
                    <li><strong>Action Name:</strong> $action_name</li>
                    <li><strong>Priority:</strong> $priority</li>
                    <li><strong>Deadline:</strong> $deadline</li>
                </ul>
                
                <p>Please ensure this task is completed by the deadline. Your progress is being tracked by Management in the ERP Tracker Dashboard. If this task is not completed by the deadline, it will be automatically flagged as <strong style='color:red;'>OVERDUE</strong>.</p>
                
                <p>Best regards,<br>IT Team & Management</p>
            </div>
        ";

        try {
            $mail = $this->getMailer();
            $mail->addAddress($owner['email'], $owner['full_name']);
            $mail->Subject = $subject;
            $mail->Body = $body;
            return $mail->send();
        } catch (Exception $e) {
            error_log("TrackerMailService Error (Task Owner) - " . $mail->ErrorInfo);
            return false;
        }
    }
}
