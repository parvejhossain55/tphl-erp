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

    // =========================================================================
    // VENDOR PAYMENT REQUEST NOTIFICATIONS
    // =========================================================================

    /**
     * Notification: Vendor Payment Request Submitted
     * Sent to Management (1, 3) and Accounts (6)
     */
    public function sendVendorPaymentOnSubmission($requestData)
    {
        try {
            $mail = $this->getMailer();
            $mail->Subject = 'New Vendor Payment Request Submitted';

            $invoiceAmount = number_format((float)($requestData['invoice_amount'] ?? 0), 2);
            $netPayable    = number_format((float)($requestData['net_payable'] ?? 0), 2);
            $paymentType   = htmlspecialchars($requestData['payment_type'] ?? 'N/A');
            $vendorName    = htmlspecialchars($requestData['vendor_name'] ?? 'N/A');

            $requester     = $this->getUserById($requestData['created_by'] ?? 0);
            $requesterName = $requester ? htmlspecialchars($requester['full_name']) : 'Unknown User';

            $projectName = '';
            if (!empty($requestData['project_name'])) {
                $projectName = htmlspecialchars($requestData['project_name']);
            } elseif (!empty($requestData['project_id'])) {
                $projectName = htmlspecialchars($this->getProjectNames($requestData['project_id']) ?? '');
            }

            $extraDetails = "";
            if (!empty($projectName)) {
                $extraDetails .= "<strong style='color: #000000;'>Project:</strong> {$projectName}<br>";
            }

            $headerTitle = "New Vendor Payment Request";
            $greeting    = "Hello,";
            $message     = "A new vendor payment request has been submitted and is pending your review.";
            $buttonText  = "View Requests";
            $buttonUrl   = "https://thepremiumhomesltd.com/TPHL-ERP/con-vendor-payment-requests";

            $detailsHtml = "
                <h4 style='margin-top: 0; margin-bottom: 15px; color: #000000; font-size: 16px;'>Request Details:</h4>
                <div style='color: #333333; font-size: 15px; line-height: 1.6;'>
                    <strong style='color: #000000;'>Requested By:</strong> {$requesterName}<br>
                    <strong style='color: #000000;'>Vendor:</strong> {$vendorName}<br>
                    {$extraDetails}
                    <strong style='color: #000000;'>Payment Type:</strong> {$paymentType}<br>
                    <strong style='color: #000000;'>Invoice Amount:</strong> BDT {$invoiceAmount}<br>
                    <strong style='color: #000000;'>Net Payable:</strong> BDT {$netPayable}
                </div>
            ";

            $mail->Body    = $this->getHtmlTemplate($headerTitle, $greeting, $message, $buttonText, $buttonUrl, $detailsHtml);
            $mail->AltBody = "New Vendor Payment Request by {$requesterName}. Vendor: {$vendorName}. Invoice: BDT {$invoiceAmount}. Net Payable: BDT {$netPayable}.";

            $recipients = $this->getUsersByRoles([1, 3, 6]);
            if (empty($recipients)) return false;

            foreach ($recipients as $recipient) {
                if (filter_var($recipient['email'], FILTER_VALIDATE_EMAIL)) {
                    $mail->addAddress($recipient['email'], $recipient['full_name']);
                }
            }

            return $mail->send();
        } catch (Exception $e) {
            error_log("Mail Service Error (Vendor Payment Submission): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notification: Vendor Payment Request Approved
     * Sent to Management (1, 3), Accounts (6), and original Requester
     */
    public function sendVendorPaymentOnApproval($requestData, $status, $approverId)
    {
        try {
            $mail = $this->getMailer();

            $isPartial  = ($status === 'partial' || $status === 'partially_paid');
            $statusText = $isPartial ? 'Partially Approved' : 'Approved';

            $mail->Subject = "Vendor Payment Request {$statusText}";

            $approvedAmount = number_format((float)($requestData['approved_amount'] ?? $requestData['net_payable'] ?? 0), 2);
            $vendorName     = htmlspecialchars($requestData['vendor_name'] ?? 'N/A');

            $requester     = $this->getUserById($requestData['created_by'] ?? 0);
            $requesterName = $requester ? htmlspecialchars($requester['full_name']) : 'Unknown User';

            $approver     = $this->getUserById($approverId);
            $approverName = $approver ? htmlspecialchars($approver['full_name']) : 'System/Admin';

            $projectName = '';
            if (!empty($requestData['project_name'])) {
                $projectName = htmlspecialchars($requestData['project_name']);
            } elseif (!empty($requestData['project_id'])) {
                $projectName = htmlspecialchars($this->getProjectNames($requestData['project_id']) ?? '');
            }

            $extraDetails = "";
            if (!empty($projectName)) {
                $extraDetails .= "<strong style='color: #000000;'>Project:</strong> {$projectName}<br>";
            }

            $amountLabel = $isPartial ? 'Partially Approved Amount' : 'Approved Amount';
            $requestId   = (int)($requestData['id'] ?? 0);

            $headerTitle = "Vendor Payment Request {$statusText}";
            $greeting    = "Hello,";
            $message     = "A vendor payment request has been processed and updated to {$statusText}.";
            $buttonText  = "View Requests";
            $buttonUrl   = "https://thepremiumhomesltd.com/TPHL-ERP/con-vendor-payment-requests";

            $detailsHtml = "
                <h4 style='margin-top: 0; margin-bottom: 15px; color: #000000; font-size: 16px;'>Approval Details:</h4>
                <div style='color: #333333; font-size: 15px; line-height: 1.6;'>
                    <strong style='color: #000000;'>Requested By:</strong> {$requesterName}<br>
                    <strong style='color: #000000;'>Approved By:</strong> {$approverName}<br>
                    <strong style='color: #000000;'>Vendor:</strong> {$vendorName}<br>
                    {$extraDetails}
                    <strong style='color: #000000;'>{$amountLabel}:</strong> BDT {$approvedAmount}
                </div>
            ";

            $mail->Body    = $this->getHtmlTemplate($headerTitle, $greeting, $message, $buttonText, $buttonUrl, $detailsHtml);
            $mail->AltBody = "Vendor Payment Request {$statusText}. Vendor: {$vendorName}. {$amountLabel}: BDT {$approvedAmount}. Approved By: {$approverName}.";

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
            error_log("Mail Service Error (Vendor Payment Approval): " . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // CONTRACTOR BILL REQUEST NOTIFICATIONS
    // =========================================================================

    /**
     * Notification: Contractor Bill Submitted
     * Sent to Management (1, 3) and Accounts (6)
     */
    public function sendContractorBillOnSubmission($billData)
    {
        try {
            $mail = $this->getMailer();
            $mail->Subject = 'New Contractor Bill Submitted';

            $billAmount      = number_format((float)($billData['bill_amount'] ?? 0), 2);
            $contractorName  = htmlspecialchars($billData['contractor_name'] ?? 'N/A');
            $workDescription = nl2br(htmlspecialchars($billData['work_description'] ?? ''));

            $requester     = $this->getUserById($billData['created_by'] ?? 0);
            $requesterName = $requester ? htmlspecialchars($requester['full_name']) : 'Unknown User';

            $projectName = '';
            if (!empty($billData['project_name'])) {
                $projectName = htmlspecialchars($billData['project_name']);
            } elseif (!empty($billData['project_id'])) {
                $projectName = htmlspecialchars($this->getProjectNames($billData['project_id']) ?? '');
            }

            $extraDetails = "";
            if (!empty($projectName)) {
                $extraDetails .= "<strong style='color: #000000;'>Project:</strong> {$projectName}<br>";
            }

            $headerTitle = "New Contractor Bill";
            $greeting    = "Hello,";
            $message     = "A new contractor bill has been submitted and is pending your review.";
            $buttonText  = "View Bills";
            $buttonUrl   = "https://thepremiumhomesltd.com/TPHL-ERP/con-contractor-bills";

            $detailsHtml = "
                <h4 style='margin-top: 0; margin-bottom: 15px; color: #000000; font-size: 16px;'>Bill Details:</h4>
                <div style='color: #333333; font-size: 15px; line-height: 1.6;'>
                    <strong style='color: #000000;'>Submitted By:</strong> {$requesterName}<br>
                    <strong style='color: #000000;'>Contractor:</strong> {$contractorName}<br>
                    {$extraDetails}
                    <strong style='color: #000000;'>Bill Amount:</strong> BDT {$billAmount}<br>
                    <strong style='color: #000000;'>Work Description:</strong> {$workDescription}
                </div>
            ";

            $mail->Body    = $this->getHtmlTemplate($headerTitle, $greeting, $message, $buttonText, $buttonUrl, $detailsHtml);
            $mail->AltBody = "New Contractor Bill by {$requesterName}. Contractor: {$contractorName}. Amount: BDT {$billAmount}.";

            $recipients = $this->getUsersByRoles([1, 3, 6]);
            
            // Add Project Manager (PM) to recipients
            if (!empty($billData['project_id'])) {
                $projSql = "SELECT pm_id FROM projects WHERE id = " . (int)$billData['project_id'];
                $projRes = $this->db->query($projSql);
                if ($projRes && $projRow = $projRes->fetch_assoc()) {
                    if ((int)$projRow['pm_id'] > 0) {
                        $pmUser = $this->getUserById((int)$projRow['pm_id']);
                        if ($pmUser && filter_var($pmUser['email'], FILTER_VALIDATE_EMAIL)) {
                            $recipients[] = $pmUser;
                        }
                    }
                }
            }

            if (empty($recipients)) return false;

            // Use a track array to avoid duplicates
            $addedEmails = [];
            foreach ($recipients as $recipient) {
                if (filter_var($recipient['email'], FILTER_VALIDATE_EMAIL) && !in_array($recipient['email'], $addedEmails)) {
                    $mail->addAddress($recipient['email'], $recipient['full_name']);
                    $addedEmails[] = $recipient['email'];
                }
            }

            return $mail->send();
        } catch (Exception $e) {
            error_log("Mail Service Error (Contractor Bill Submission): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notification: Contractor Bill Approved
     * Sent to Management (1, 3), Accounts (6), and original Requester
     */
    public function sendContractorBillOnApproval($billData, $status, $approverId)
    {
        try {
            $mail = $this->getMailer();

            $isPartial  = ($status === 'partial' || $status === 'partially_paid');
            $statusText = $isPartial ? 'Partially Approved' : 'Approved';

            $mail->Subject = "Contractor Bill {$statusText}";

            $approvedAmount = number_format((float)($billData['approved_amount'] ?? $billData['bill_amount'] ?? 0), 2);
            $contractorName = htmlspecialchars($billData['contractor_name'] ?? 'N/A');

            $requester     = $this->getUserById($billData['created_by'] ?? 0);
            $requesterName = $requester ? htmlspecialchars($requester['full_name']) : 'Unknown User';

            $approver     = $this->getUserById($approverId);
            $approverName = $approver ? htmlspecialchars($approver['full_name']) : 'System/Admin';

            $projectName = '';
            if (!empty($billData['project_name'])) {
                $projectName = htmlspecialchars($billData['project_name']);
            } elseif (!empty($billData['project_id'])) {
                $projectName = htmlspecialchars($this->getProjectNames($billData['project_id']) ?? '');
            }

            $extraDetails = "";
            if (!empty($projectName)) {
                $extraDetails .= "<strong style='color: #000000;'>Project:</strong> {$projectName}<br>";
            }

            $amountLabel = $isPartial ? 'Partially Approved Amount' : 'Approved Amount';
            $billId      = (int)($billData['id'] ?? 0);

            $headerTitle = "Contractor Bill {$statusText}";
            $greeting    = "Hello,";
            $message     = "A contractor bill has been processed and updated to {$statusText}.";
            $buttonText  = "View Bills";
            $buttonUrl   = "https://thepremiumhomesltd.com/TPHL-ERP/con-contractor-bills";

            $detailsHtml = "
                <h4 style='margin-top: 0; margin-bottom: 15px; color: #000000; font-size: 16px;'>Approval Details:</h4>
                <div style='color: #333333; font-size: 15px; line-height: 1.6;'>
                    <strong style='color: #000000;'>Submitted By:</strong> {$requesterName}<br>
                    <strong style='color: #000000;'>Approved By:</strong> {$approverName}<br>
                    <strong style='color: #000000;'>Contractor:</strong> {$contractorName}<br>
                    {$extraDetails}
                    <strong style='color: #000000;'>{$amountLabel}:</strong> BDT {$approvedAmount}
                </div>
            ";

            $mail->Body    = $this->getHtmlTemplate($headerTitle, $greeting, $message, $buttonText, $buttonUrl, $detailsHtml);
            $mail->AltBody = "Contractor Bill {$statusText}. Contractor: {$contractorName}. {$amountLabel}: BDT {$approvedAmount}. Approved By: {$approverName}.";

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
            error_log("Mail Service Error (Contractor Bill Approval): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notification: Contractor Bill Rejected
     */
    public function sendContractorBillOnRejection($billData, $rejecterId)
    {
        try {
            $mail = $this->getMailer();
            $mail->Subject = 'Contractor Bill Rejected';

            $billAmount      = number_format((float)($billData['bill_amount'] ?? 0), 2);
            $contractorName  = htmlspecialchars($billData['contractor_name'] ?? 'N/A');

            $requester     = $this->getUserById($billData['created_by'] ?? 0);
            $requesterName = $requester ? htmlspecialchars($requester['full_name']) : 'Unknown User';

            $rejecter     = $this->getUserById($rejecterId);
            $rejecterName = $rejecter ? htmlspecialchars($rejecter['full_name']) : 'System/Admin';

            $projectName = '';
            if (!empty($billData['project_name'])) {
                $projectName = htmlspecialchars($billData['project_name']);
            } elseif (!empty($billData['project_id'])) {
                $projectName = htmlspecialchars($this->getProjectNames($billData['project_id']) ?? '');
            }

            $extraDetails = "";
            if (!empty($projectName)) {
                $extraDetails .= "<strong style='color: #000000;'>Project:</strong> {$projectName}<br>";
            }

            $headerTitle = "Contractor Bill Rejected";
            $greeting    = "Hello,";
            $message     = "A contractor bill has been rejected by Management/PM.";
            $buttonText  = "View Bill";
            $buttonUrl   = "https://thepremiumhomesltd.com/TPHL-ERP/con-contractor-bills";

            $detailsHtml = "
                <h4 style='margin-top: 0; margin-bottom: 15px; color: #000000; font-size: 16px;'>Rejection Details:</h4>
                <div style='color: #333333; font-size: 15px; line-height: 1.6;'>
                    <strong style='color: #000000;'>Submitted By:</strong> {$requesterName}<br>
                    <strong style='color: #000000;'>Rejected By:</strong> {$rejecterName}<br>
                    <strong style='color: #000000;'>Contractor:</strong> {$contractorName}<br>
                    {$extraDetails}
                    <strong style='color: #000000;'>Bill Amount:</strong> BDT {$billAmount}
                </div>
            ";

            $mail->Body    = $this->getHtmlTemplate($headerTitle, $greeting, $message, $buttonText, $buttonUrl, $detailsHtml);
            $mail->AltBody = "Contractor Bill Rejected. Contractor: {$contractorName}. Amount: BDT {$billAmount}. Rejected By: {$rejecterName}.";

            $recipients = $this->getUsersByRoles([1, 3, 6]);

            // Add PM
            if (!empty($billData['project_id'])) {
                $projSql = "SELECT pm_id FROM projects WHERE id = " . (int)$billData['project_id'];
                $projRes = $this->db->query($projSql);
                if ($projRes && $projRow = $projRes->fetch_assoc()) {
                    if ((int)$projRow['pm_id'] > 0) {
                        $pmUser = $this->getUserById((int)$projRow['pm_id']);
                        if ($pmUser && filter_var($pmUser['email'], FILTER_VALIDATE_EMAIL)) {
                            $recipients[] = $pmUser;
                        }
                    }
                }
            }

            $addedEmails = [];
            foreach ($recipients as $recipient) {
                if (filter_var($recipient['email'], FILTER_VALIDATE_EMAIL) && !in_array($recipient['email'], $addedEmails)) {
                    $mail->addAddress($recipient['email'], $recipient['full_name']);
                    $addedEmails[] = $recipient['email'];
                    if ($requester && $recipient['email'] === $requester['email']) {
                        $requesterEmailAdded = true;
                    }
                }
            }

            if (!isset($requesterEmailAdded) || !$requesterEmailAdded) {
                if ($requester && filter_var($requester['email'], FILTER_VALIDATE_EMAIL)) {
                    $mail->addAddress($requester['email'], $requester['full_name']);
                }
            }

            return $mail->send();
        } catch (Exception $e) {
            error_log("Mail Service Error (Contractor Bill Rejection): " . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // CLIENT REFUND NOTIFICATIONS
    // =========================================================================

    /**
     * Notification: Client Refund Request Submitted
     * Sent to Management (1, 3) and Accounts (6)
     */
    public function sendRefundOnSubmission($refundData)
    {
        try {
            $mail = $this->getMailer();
            $mail->Subject = 'New Client Refund Request';

            $clientName      = htmlspecialchars($refundData['client_name'] ?? 'N/A');
            $flatName        = htmlspecialchars($refundData['flat_name'] ?? 'N/A');
            $projectName     = htmlspecialchars($refundData['project_name'] ?? 'N/A');
            $totalPaid       = number_format((float)($refundData['total_paid'] ?? 0), 2);
            $retentionAmount = number_format((float)($refundData['retention_amount'] ?? 0), 2);
            $refundAmount    = number_format((float)($refundData['refund_amount'] ?? 0), 2);
            $reason          = nl2br(htmlspecialchars($refundData['reason'] ?? ''));

            $requester     = $this->getUserById($refundData['requested_by'] ?? 0);
            $requesterName = $requester ? htmlspecialchars($requester['full_name']) : 'Unknown User';

            $headerTitle = "New Client Refund Request";
            $greeting    = "Hello,";
            $message     = "A client refund request has been submitted and is pending your approval.";
            $buttonText  = "View Refunds";
            $buttonUrl   = "https://thepremiumhomesltd.com/TPHL-ERP/client-refunds";

            $detailsHtml = "
                <h4 style='margin-top: 0; margin-bottom: 15px; color: #000000; font-size: 16px;'>Refund Details:</h4>
                <div style='color: #333333; font-size: 15px; line-height: 1.6;'>
                    <strong style='color: #000000;'>Requested By:</strong> {$requesterName}<br>
                    <strong style='color: #000000;'>Client:</strong> {$clientName}<br>
                    <strong style='color: #000000;'>Project:</strong> {$projectName}<br>
                    <strong style='color: #000000;'>Flat:</strong> {$flatName}<br>
                    <strong style='color: #000000;'>Total Paid:</strong> BDT {$totalPaid}<br>
                    <strong style='color: #000000;'>Retention:</strong> BDT {$retentionAmount}<br>
                    <strong style='color: #000000;'>Refund Amount:</strong> BDT {$refundAmount}<br>
                    <strong style='color: #000000;'>Reason:</strong> {$reason}
                </div>
            ";

            $mail->Body    = $this->getHtmlTemplate($headerTitle, $greeting, $message, $buttonText, $buttonUrl, $detailsHtml);
            $mail->AltBody = "Client Refund Request by {$requesterName}. Client: {$clientName}. Refund: BDT {$refundAmount}.";

            $recipients = $this->getUsersByRoles([1, 3, 6]);
            if (empty($recipients)) return false;

            foreach ($recipients as $recipient) {
                if (filter_var($recipient['email'], FILTER_VALIDATE_EMAIL)) {
                    $mail->addAddress($recipient['email'], $recipient['full_name']);
                }
            }

            return $mail->send();
        } catch (Exception $e) {
            error_log("Mail Service Error (Refund Submission): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notification: Client Refund Approved
     * Sent to Management (1, 3), Accounts (6), and original Requester
     */
    public function sendRefundOnApproval($refundData, $approverId)
    {
        try {
            $mail = $this->getMailer();
            $mail->Subject = 'Client Refund Approved';

            $clientName   = htmlspecialchars($refundData['client_name'] ?? 'N/A');
            $flatName     = htmlspecialchars($refundData['flat_name'] ?? 'N/A');
            $projectName  = htmlspecialchars($refundData['project_name'] ?? 'N/A');
            $refundAmount = number_format((float)($refundData['refund_amount'] ?? 0), 2);
            $retention    = number_format((float)($refundData['retention_amount'] ?? 0), 2);

            $requester     = $this->getUserById($refundData['requested_by'] ?? 0);
            $requesterName = $requester ? htmlspecialchars($requester['full_name']) : 'Unknown User';

            $approver     = $this->getUserById($approverId);
            $approverName = $approver ? htmlspecialchars($approver['full_name']) : 'System/Admin';

            $headerTitle = "Client Refund Approved";
            $greeting    = "Hello,";
            $message     = "A client refund has been approved. The booking has been cancelled and the flat released. A cashout request has been auto-created for disbursement.";
            $buttonText  = "View Refunds";
            $buttonUrl   = "https://thepremiumhomesltd.com/TPHL-ERP/client-refunds";

            $detailsHtml = "
                <h4 style='margin-top: 0; margin-bottom: 15px; color: #000000; font-size: 16px;'>Approval Details:</h4>
                <div style='color: #333333; font-size: 15px; line-height: 1.6;'>
                    <strong style='color: #000000;'>Approved By:</strong> {$approverName}<br>
                    <strong style='color: #000000;'>Client:</strong> {$clientName}<br>
                    <strong style='color: #000000;'>Project:</strong> {$projectName}<br>
                    <strong style='color: #000000;'>Flat:</strong> {$flatName}<br>
                    <strong style='color: #000000;'>Retention:</strong> BDT {$retention}<br>
                    <strong style='color: #000000;'>Refund Amount:</strong> BDT {$refundAmount}<br>
                    <strong style='color: #000000;'>Requested By:</strong> {$requesterName}
                </div>
            ";

            $mail->Body    = $this->getHtmlTemplate($headerTitle, $greeting, $message, $buttonText, $buttonUrl, $detailsHtml);
            $mail->AltBody = "Client Refund Approved. Client: {$clientName}. Refund: BDT {$refundAmount}. Approved By: {$approverName}.";

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

            if (!$requesterEmailAdded && $requester && filter_var($requester['email'], FILTER_VALIDATE_EMAIL)) {
                $mail->addAddress($requester['email'], $requester['full_name']);
            }

            return $mail->send();
        } catch (Exception $e) {
            error_log("Mail Service Error (Refund Approval): " . $e->getMessage());
            return false;
        }
    }
}

