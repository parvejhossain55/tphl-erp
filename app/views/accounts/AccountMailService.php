<?php
// app/views/accounts/AccountMailService.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\OAuth;
use League\OAuth2\Client\Provider\Google;

class AccountMailService
{
    private $db;
    
    // OAuth and SMTP configuration placeholders
    private $smtpHost = 'smtp.gmail.com';
    private $smtpPort = 587; // Or 465 for SSL
    private $senderEmail = 'info@dpremiumhomes.com';
    private $senderName = 'TPHL ERP Notification';
    private $clientId = '893215055562-pbt3j7lg7ufqju370ltss46kh7pati3n.apps.googleusercontent.com';
    private $clientSecret = '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';
    private $refreshToken = '1//04tQCZJUfNcm7CgYIARAAGAQSNwF-L9IrFjuXh9BJ80IcIEKsg8h5m5TFRRQS6BQTt-wwIvtJKnUHd4sAtITIw8QJ0pgbijIOF0w';

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Fetch users by their role IDs
     * Roles: 1, 3 (Management), 6 (Accounts)
     */
    private function getUsersByRoles($roles = [])
    {
        if (empty($roles)) {
            return [];
        }

        $rolesStr = implode(',', array_map('intval', $roles));
        $sql = "SELECT id, full_name, email FROM users WHERE role_id IN ($rolesStr) AND email IS NOT NULL AND email != ''";
        
        $result = $this->db->query($sql);
        $users = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        return $users;
    }

    /**
     * Get a single user's detail by ID
     */
    public function getUserById($userId)
    {
        $sql = "SELECT id, full_name, email FROM users WHERE id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return null;

        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        $stmt->close();

        return $user;
    }

    /**
     * Get Project names by comma-separated IDs
     */
    private function getProjectNames($projectIds)
    {
        if (empty($projectIds)) return null;
        
        $pieces = explode(',', $projectIds);
        $safeIds = implode(',', array_map('intval', $pieces));
        if (empty($safeIds)) return null;

        $sql = "SELECT GROUP_CONCAT(name SEPARATOR ', ') as project_name FROM projects WHERE id IN ($safeIds)";
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['project_name'] ?? null;
        }
        
        return null;
    }

    /**
     * Configure and return an initialized PHPMailer instance using Google OAuth
     */
    private function getMailer()
    {
        $mail = new PHPMailer(true);

        // Inform PHPMailer to use SMTP
        $mail->isSMTP();
        $mail->Host = $this->smtpHost;
        $mail->Port = $this->smtpPort;
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        
        // Use XOAUTH2 logic
        $mail->AuthType = 'XOAUTH2';

        $provider = new Google([
            'clientId'     => $this->clientId,
            'clientSecret' => $this->clientSecret,
        ]);

        $mail->setOAuth(new OAuth([
            'provider'     => $provider,
            'clientId'     => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'refreshToken' => $this->refreshToken,
            'userName'     => $this->senderEmail,
        ]));

        $mail->setFrom($this->senderEmail, $this->senderName);
        $mail->isHTML(true);

        return $mail;
    }

    /**
     * Generates a reusable dark-mode HTML template similar to the application's UI
     */
    private function getHtmlTemplate($headerTitle, $greeting, $message, $buttonText, $buttonUrl, $detailsHtml)
    {
        $year = date('Y');
        // using precise colors from the UI mock
        return <<<HTML
        <div style="padding: 30px 10px; font-family: Arial, sans-serif; color: #333333;">
            <div style="max-width: 600px; margin: 0 auto; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border: 1px solid #eeeeee;">
                <!-- Header -->
                <div style="background-color: #28a745; padding: 25px 20px; text-align: center;">
                    <h2 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: bold;">{$headerTitle}</h2>
                </div>
                <!-- Body -->
                <div style="padding: 30px;">
                    <p style="margin-top: 0; font-size: 16px; color: #333333;">{$greeting}</p>
                    <p style="font-size: 16px; color: #555555; margin-bottom: 25px;">{$message}</p>
                    
                    <a href="{$buttonUrl}" style="display: inline-block; background-color: #28a745; color: #ffffff; text-decoration: none; padding: 12px 25px; border-radius: 4px; font-size: 16px; margin-bottom: 30px;">{$buttonText}</a>
                    
                    <hr style="border: none; border-top: 1px solid #eeeeee; margin-bottom: 25px;">
                    
                    {$detailsHtml}
                    
                    <p style="color: #777777; font-size: 13px; margin-bottom: 5px; margin-top: 40px;">Visit:</p>
                    <a href="{$buttonUrl}" style="color: #5c9ce6; font-size: 13px; text-decoration: none; word-break: break-all;">{$buttonUrl}</a>
                </div>
                <!-- Bottom Footer -->
                <div style="background-color: #f8f9fa; padding: 15px; text-align: center; color: #777777; font-size: 14px; border-top: 1px solid #eeeeee;">
                    &copy; {$year} TPHL ERP
                </div>
            </div>
        </div>
HTML;
    }

    /**
     * Notification 1 (On Submission)
     * Sent to Management (1, 3) and Accounts (6)
     */
    public function sendOnSubmission($requestData)
    {
        try {
            $mail = $this->getMailer();
            $mail->Subject = 'New Cashout Request Submitted';
            
            $amount = number_format($requestData['amount'], 2);
            $purpose = htmlspecialchars($requestData['purpose_paid_to'] ?? 'N/A');
            $description = nl2br(htmlspecialchars($requestData['description'] ?? ''));

            $requester = $this->getUserById($requestData['requested_by']);
            $requesterName = $requester ? htmlspecialchars($requester['full_name']) : 'Unknown User';

            $projectNames = isset($requestData['project_name']) && !empty($requestData['project_name']) 
                            ? $requestData['project_name'] 
                            : $this->getProjectNames($requestData['project_id'] ?? '');

            $office = !empty($requestData['office']) ? htmlspecialchars($requestData['office']) : null;
            
            $extraDetails = "";
            if (!empty($projectNames)) {
                $safeProjectNames = htmlspecialchars($projectNames);
                $extraDetails .= "<strong style='color: #000000;'>Project(s):</strong> {$safeProjectNames}<br>";
            }
            if (!empty($office)) {
                $extraDetails .= "<strong style='color: #000000;'>Office:</strong> {$office}<br>";
            }

            $headerTitle = "New Cashout Request";
            $greeting = "Hello,";
            $message = "A new cashout request has been submitted and is pending your review.";
            $buttonText = "View Request";
            $buttonUrl = "https://thepremiumhomesltd.com/TPHL-ERP/admin-cashout-requests";
            
            $detailsHtml = "
                <h4 style='margin-top: 0; margin-bottom: 15px; color: #000000; font-size: 16px;'>Request Details:</h4>
                <div style='color: #333333; font-size: 15px; line-height: 1.6;'>
                    <strong style='color: #000000;'>Requester:</strong> {$requesterName}<br>
                    {$extraDetails}
                    <strong style='color: #000000;'>Amount:</strong> BDT {$amount}<br>
                    <strong style='color: #000000;'>Purpose / Paid To:</strong> {$purpose}<br>
                    <strong style='color: #000000;'>Description:</strong> {$description}
                </div>
            ";

            $mail->Body = $this->getHtmlTemplate($headerTitle, $greeting, $message, $buttonText, $buttonUrl, $detailsHtml);

            $mail->AltBody = "New Cashout Request by {$requesterName}. Amount: BDT {$amount}. Purpose: {$purpose}. Description: {$requestData['description']}";

            $recipients = $this->getUsersByRoles([1, 3, 6]);
            
            if (empty($recipients)) {
                return false;
            }

            foreach ($recipients as $recipient) {
                if (filter_var($recipient['email'], FILTER_VALIDATE_EMAIL)) {
                    $mail->addAddress($recipient['email'], $recipient['full_name']);
                }
            }

            return $mail->send();
        } catch (Exception $e) {
            error_log("Mail Service Error (Submission): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notification 2 (On Approval/Partial Approval)
     * Sent to Management (1, 3), Accounts (6), and original Requester
     */
    public function sendOnApproval($requestData, $status, $approverId)
    {
        try {
            $mail = $this->getMailer();
            
            $isPartial = ($status === 'partial' || $status === 'partially_paid');
            $statusText = $isPartial ? 'Partially Approved' : 'Approved';
            
            $mail->Subject = "Cashout Request {$statusText}";
            
            $approvedAmountRaw = $requestData['approved_amount'] ?? $requestData['amount'];
            $approvedAmount = number_format($approvedAmountRaw, 2);
            $purpose = htmlspecialchars($requestData['purpose_paid_to'] ?? 'N/A');

            $requester = $this->getUserById($requestData['requested_by']);
            $requesterName = $requester ? htmlspecialchars($requester['full_name']) : 'Unknown User';
            
            $approver = $this->getUserById($approverId);
            $approverName = $approver ? htmlspecialchars($approver['full_name']) : 'System/Admin';

            $projectNames = isset($requestData['project_name']) && !empty($requestData['project_name']) 
                            ? $requestData['project_name'] 
                            : $this->getProjectNames($requestData['project_id'] ?? '');

            $office = !empty($requestData['office']) ? htmlspecialchars($requestData['office']) : null;
            
            $extraDetails = "";
            if (!empty($projectNames)) {
                $safeProjectNames = htmlspecialchars($projectNames);
                $extraDetails .= "<strong style='color: #000000;'>Project(s):</strong> {$safeProjectNames}<br>";
            }
            if (!empty($office)) {
                $extraDetails .= "<strong style='color: #000000;'>Office:</strong> {$office}<br>";
            }

            $amountLabel = $isPartial ? 'Partially Approved Amount' : 'Approved Amount';

            $headerTitle = "Cashout Request {$statusText}";
            $greeting = "Hello,";
            $message = "The cashout request has been processed and updated to {$statusText}.";
            $buttonText = "View Request";
            $buttonUrl = "https://thepremiumhomesltd.com/TPHL-ERP/admin-login";

            $detailsHtml = "
                <h4 style='margin-top: 0; margin-bottom: 15px; color: #000000; font-size: 16px;'>Approval Details:</h4>
                <div style='color: #333333; font-size: 15px; line-height: 1.6;'>
                    <strong style='color: #000000;'>Requester:</strong> {$requesterName}<br>
                    <strong style='color: #000000;'>Approved By:</strong> {$approverName}<br>
                    {$extraDetails}
                    <strong style='color: #000000;'>Purpose / Paid To:</strong> {$purpose}<br>
                    <strong style='color: #000000;'>{$amountLabel}:</strong> BDT {$approvedAmount}
                </div>
            ";

            $mail->Body = $this->getHtmlTemplate($headerTitle, $greeting, $message, $buttonText, $buttonUrl, $detailsHtml);

            $mail->AltBody = "Cashout Request {$statusText}. Requester: {$requesterName}. Approved By: {$approverName}. {$amountLabel}: BDT {$approvedAmount}.";

            $recipients = $this->getUsersByRoles([1, 3, 6]);
            
            $requesterEmailAdded = false;
            foreach ($recipients as $recipient) {
                if (filter_var($recipient['email'], FILTER_VALIDATE_EMAIL)) {
                    $mail->addAddress($recipient['email'], $recipient['full_name']);
                    if ($requester && $recipient['email'] === $requester['email']) {
                        $requesterEmailAdded = true;
                    }
                }
            }

            // Ensure Requester gets the email
            if (!$requesterEmailAdded && $requester && filter_var($requester['email'], FILTER_VALIDATE_EMAIL)) {
                $mail->addAddress($requester['email'], $requester['full_name']);
            }

            return $mail->send();
        } catch (Exception $e) {
            error_log("Mail Service Error (Approval): " . $e->getMessage());
            return false;
        }
    }
}
