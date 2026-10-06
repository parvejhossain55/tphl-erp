<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class TodoMailService
{
    private $smtpHost = 'smtp.gmail.com';
    private $smtpPort = 587;
    private $senderEmail = 'no-reply@dpremiumhomes.com'; // Changed to no-reply
    private $senderName = 'TPHL ERP Todo Alerts';
    private $username = 'tech@dpremiumhomes.com'; // Use their actual SMTP user
    private $password = 'YourSMTPPasswordHere'; // Note: User needs to update this or use OAuth if they use OAuth.

    // If they use OAuth like in ProjectWorkflowMailService, we should use that setup. 
    // Let's assume standard SMTP or the user will copy their credentials over.
    
    public function send($toEmail, $subject, $body)
    {
        // For demonstration, using simple mail() if PHPMailer isn't fully configured, 
        // but we will write PHPMailer logic assuming they have it in vendor.
        require_once __DIR__ . '/../../vendor/autoload.php';

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            // The user will need to put their actual SMTP credentials here since we don't have the exact ones for no-reply
            $mail->Username   = 'tech@dpremiumhomes.com'; 
            $mail->Password   = 'YourAppPassword'; // User must replace this
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom($this->senderEmail, $this->senderName);
            $mail->addAddress($toEmail);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
