<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\OAuth;
use League\OAuth2\Client\Provider\Google;

class MailService
{
    private $smtpHost = 'smtp.gmail.com';
    private $smtpPort = 587; // TLS
    private $senderEmail = 'no-reply@dpremiumhomes.com';
    private $senderName = 'TPHL ERP Notification';
    private $clientId = '684667519692-hcdp7rhi9r64d87g93eq1cagpgqurk35.apps.googleusercontent.com';
    private $clientSecret = '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';
    private $refreshToken = '1//041-wDvZzKIoVCgYIARAAGAQSNwF-L9IrP3uKc9BZMrTRHt9EHVR-PDRZdgog0Hk8uAOapqaf45h4Xkp8rkwzPTIY2Vhxt1DgIx4'; // Must be permanent, Playground token won't work

    private function getMailer()
    {
        $mail = new PHPMailer(true);
        // SMTP setup
        $mail->isSMTP();
        $mail->Host = $this->smtpHost;
        $mail->Port = $this->smtpPort;
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        // XOAUTH2 setup
        $provider = new Google([
            'clientId'     => $this->clientId,
            'clientSecret' => $this->clientSecret,
        ]);

        $mail->AuthType = 'XOAUTH2';
        $mail->setOAuth(new OAuth([
            'provider'     => $provider,
            'clientId'     => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'refreshToken' => $this->refreshToken,
            'userName'     => $this->senderEmail,
        ]));

        // Sender
        $mail->setFrom($this->senderEmail, $this->senderName);
        return $mail;
    }

public function sendGenericMail($toEmail, $subject, $body)
{
    try {
        $mail = $this->getMailer();
        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body);
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Exception: " . $e->getMessage());
        return false;
    }
}

public function sendMail($toEmail, $subject, $link, $assignedBy)
{
    try {
        $mail = $this->getMailer();
        $mail->addAddress($toEmail);

        // Professional HTML Template
        $mail->isHTML(true);
        $mail->Subject = $subject;
        
        $mail->Body = "
        <div style='font-family: \"Segoe UI\", Tahoma, Geneva, Verdana, sans-serif; background-color: #f9f9f9; padding: 40px 10px;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border: 1px solid #e1e1e1;'>
                
                <div style='background-color: #28a745; height: 6px;'></div>

                <div style='padding: 30px;'>
                    <h2 style='color: #333; margin-top: 0;'>Task Notification</h2>
                    <p style='color: #555; font-size: 16px; line-height: 1.6;'>Hello,</p>
                    <p style='color: #555; font-size: 15px; line-height: 1.6;'>A new task has been assigned to you. Here are the details:</p>
                    
                    <div style='background-color: #f8f9fa; border-radius: 6px; padding: 20px; margin: 25px 0; border: 1px dashed #ccc;'>
                        <p style='margin: 0 0 10px 0; font-size: 14px;'><strong>Task Subject:</strong> {$subject}</p>
                        <p style='margin: 0 0 10px 0; font-size: 14px;'><strong>Assigned By:</strong> {$assignedBy['full_name']}</p>
                        <p style='margin: 0 0 10px 0; font-size: 14px;'><strong>Designation:</strong> {$assignedBy['designation_name']}</p>
                        <p style='margin: 0 0 10px 0; font-size: 14px;'><strong>Department:</strong> {$assignedBy['department_name']}</p>
                        <p style='margin: 0; font-size: 14px;'><strong>Role:</strong> {$assignedBy['role_name']}</p>
                    </div>

                    <div style='text-align: center; margin-top: 30px;'>
                        <a href='{$link}' style='background-color: #28a745; color: #ffffff; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 15px; display: inline-block;'>View Task Details</a>
                    </div>
                </div>

                <div style='background-color: #f1f1f1; padding: 15px; text-align: center;'>
                    <p style='margin: 0; font-size: 12px; color: #888;'>This is an automated message, please do not reply to this email.</p>
                </div>
            </div>
        </div>";

        // Plain text version
        $mail->AltBody = "New Task: {$subject}\nAssigned By: {$assignedBy['full_name']}\nLink: {$link}";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Mailer Exception: " . $e->getMessage());
        return false;
    }
}
}
