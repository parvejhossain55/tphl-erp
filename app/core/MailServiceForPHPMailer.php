<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;



class MailService
{
    private $host = 'mail.dpremiumhomes.com';
    private $username = 'no-reply@dpremiumhomes.com';
    private $password = 'djcdawaxdbmdotlj';
    private $port = 465;
    private $secure = 'ssl';
    public $lastError = '';

    public function __construct()
    {
        // Load configuration to handle local vs live server dynamically
        $config = require __DIR__ . '/../config/config.php';
        
        // If config has SMTP settings (e.g., from local $current_server == 1), override the defaults
        if (!empty($config['smtp_host'])) {
            $this->host = $config['smtp_host'];
            $this->username = $config['smtp_user'];
            $this->password = $config['smtp_pass'];
            $this->port = $config['smtp_port'];
            if (!empty($config['smtp_secure'])) {
                $this->secure = $config['smtp_secure'];
            }
        }
    }

    public function sendMail($email, $title, $link, $assignedBy)
    {
        $mail = new PHPMailer(true);

        try {
            // SMTP config

            $mail->isSMTP();
            $mail->CharSet = 'UTF-8';
            $mail->SMTPDebug = 0; // set to 0 to prevent raw debug output from breaking HTTP redirects
            $mail->Timeout = 5;

            $mail->Host       = $this->host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->username;
            $mail->Password   = $this->password;
            $mail->SMTPSecure = 'ssl';
            $mail->Port       = $this->port;

            // Sender & receiver
            $mail->setFrom('no-reply@dpremiumhomes.com', 'TPHL ERP');
            $mail->addAddress($email);

            // Email content
            $mail->isHTML(true);
            $mail->Subject = $title;

            $mail->Body = $this->buildTemplate($title, $link, $assignedBy);

            $mail->send();

            // echo "Email sent successfully to $email";
            return true;
        } catch (Exception $e) {
            $this->lastError = $mail->ErrorInfo;
            error_log("Mail Error: " . $mail->ErrorInfo);
            return false;
        }
    }

    private function buildTemplate($title, $link, $assignedBy)
    {
        return "
        <div style='font-family:Arial;background:#f4f6f8;padding:20px;'>
            <div style='max-width:600px;margin:auto;background:#fff;border-radius:10px;overflow:hidden;'>

                <div style='background:#28a745;color:#fff;padding:15px;text-align:center;'>
                    <h2>$title</h2>
                </div>

                <div style='padding:20px;'>
                    <p>Hello,</p>

                    <p>You have a new task assigned.</p>

                    <a href='$link' style='display:inline-block;padding:10px 20px;background:#28a745;color:#fff;border-radius:5px;text-decoration:none;'>
                        View Task
                    </a>

                    <hr>

                    <h4>Assigned By:</h4>
                    <p>
                        <b>Name:</b> {$assignedBy['full_name']} <br>
                        <b>Designation:</b> {$assignedBy['designation_name']} <br>
                        <b>Department:</b> {$assignedBy['department_name']} <br>
                        <b>Role:</b> {$assignedBy['role_name']}
                    </p>

                    <p style='font-size:12px;color:#999;margin-top:20px;'>
                        If button not working:<br>
                        $link
                    </p>
                </div>

                <div style='background:#f1f1f1;text-align:center;padding:10px;font-size:12px;'>
                    © 2026 TPHL ERP
                </div>
            </div>
        </div>
        ";
    }

    public function sendGenericMail($email, $subject, $body)
    {
        // --- LOCAL TESTING OVERRIDES (Uncomment tomorrow to test locally) ---
        // $originalEmail = $email;
        // $email = 'farzana.tphl13@gmail.com';
        // $subject = "[TEST for {$originalEmail}] " . $subject;
        // --------------------------------------------------------------------

        if (empty($email)) {
            return false;
        }

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->CharSet = 'UTF-8';
            $mail->SMTPDebug = 0; // set to 0 to avoid breaking JSON responses with debug output
            $mail->Timeout = 5;
            $mail->Host       = $this->host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->username;
            $mail->Password   = $this->password;
            $mail->SMTPSecure = isset($this->secure) ? $this->secure : 'ssl';
            $mail->Port       = $this->port;

            $mail->setFrom('no-reply@dpremiumhomes.com', 'TPHL ERP');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\r\n", "\r\n\r\n"], $body));

            $mail->send();
            return true;
        } catch (Exception $e) {
            $this->lastError = $mail->ErrorInfo;
            error_log("Mail Error: " . $mail->ErrorInfo);
            return false;
        }
    }

    public function sendRequisitionNotification($toEmail, $toName, $actionType, $requisitionType, $requisitionDetails, $link)
    {
        $subject = "Action Required: {$requisitionType} - {$actionType}";
        
        $body = "
        <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; border-radius: 8px;'>
            <h2 style='color: #10a34dff; border-bottom: 2px solid #0e6119ff; padding-bottom: 10px;'>{$requisitionType} Update</h2>
            <p>Dear {$toName},</p>
            <p>A <strong>{$requisitionType}</strong> requires your attention. Current Status: <strong>{$actionType}</strong></p>
            
            <table style='width: 100%; border-collapse: collapse; margin-top: 15px; margin-bottom: 20px;'>
                <tr>
                    <td style='padding: 8px; border: 1px solid #ddd; background: #f9f9f9; font-weight: bold; width: 30%;'>Title/Purpose</td>
                    <td style='padding: 8px; border: 1px solid #ddd;'>{$requisitionDetails['title']}</td>
                </tr>
                <tr>
                    <td style='padding: 8px; border: 1px solid #ddd; background: #f9f9f9; font-weight: bold;'>Requested By</td>
                    <td style='padding: 8px; border: 1px solid #ddd;'>{$requisitionDetails['requester_name']}</td>
                </tr>
                <tr>
                    <td style='padding: 8px; border: 1px solid #ddd; background: #f9f9f9; font-weight: bold;'>Amount</td>
                    <td style='padding: 8px; border: 1px solid #ddd;'>৳" . number_format((float)$requisitionDetails['amount'], 2) . "</td>
                </tr>
            </table>
            
            <div style='text-align: center; margin-top: 30px;'>
                <a href='{$link}' style='background-color: #03791dff; color: #ffffff; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>View Requisition in ERP</a>
            </div>
            
            <p style='margin-top: 30px; font-size: 12px; color: #807d7dff; text-align: center;'>
                This is an automated notification from TPHL ERP. Please do not reply to this email.
            </p>
        </div>
        ";

        return $this->sendGenericMail($toEmail, $subject, $body);
    }

    /**
     * Send notification to multiple recipients in a SINGLE SMTP connection to eliminate slow page loads
     */
    public function sendRequisitionNotificationBatch($recipients, $actionType, $requisitionType, $requisitionDetails, $link)
    {
        if (empty($recipients)) {
            return false;
        }

        $subject = "Action Required: {$requisitionType} - {$actionType}";
        
        $body = "
        <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; border-radius: 8px;'>
            <h2 style='color: #10a34dff; border-bottom: 2px solid #0e6119ff; padding-bottom: 10px;'>{$requisitionType} Update</h2>
            <p>Dear Management,</p>
            <p>A <strong>{$requisitionType}</strong> requires your attention. Current Status: <strong>{$actionType}</strong></p>
            
            <table style='width: 100%; border-collapse: collapse; margin-top: 15px; margin-bottom: 20px;'>
                <tr>
                    <td style='padding: 8px; border: 1px solid #ddd; background: #f9f9f9; font-weight: bold; width: 30%;'>Title/Purpose</td>
                    <td style='padding: 8px; border: 1px solid #ddd;'>{$requisitionDetails['title']}</td>
                </tr>
                <tr>
                    <td style='padding: 8px; border: 1px solid #ddd; background: #f9f9f9; font-weight: bold;'>Requested By</td>
                    <td style='padding: 8px; border: 1px solid #ddd;'>{$requisitionDetails['requester_name']}</td>
                </tr>
                <tr>
                    <td style='padding: 8px; border: 1px solid #ddd; background: #f9f9f9; font-weight: bold;'>Amount</td>
                    <td style='padding: 8px; border: 1px solid #ddd;'>৳" . number_format((float)$requisitionDetails['amount'], 2) . "</td>
                </tr>
            </table>
            
            <div style='text-align: center; margin-top: 30px;'>
                <a href='{$link}' style='background-color: #03791dff; color: #ffffff; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>View Requisition in ERP</a>
            </div>
            
            <p style='margin-top: 30px; font-size: 12px; color: #807d7dff; text-align: center;'>
                This is an automated notification from TPHL ERP. Please do not reply to this email.
            </p>
        </div>
        ";

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->CharSet = 'UTF-8';
            $mail->SMTPDebug = 0;
            $mail->Timeout = 5;
            $mail->Host       = $this->host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->username;
            $mail->Password   = $this->password;
            $mail->SMTPSecure = isset($this->secure) ? $this->secure : 'ssl';
            $mail->Port       = $this->port;

            $mail->setFrom('no-reply@dpremiumhomes.com', 'TPHL ERP');
            
            $primaryAdded = false;
            foreach ($recipients as $item) {
                $email = is_array($item) ? ($item['email'] ?? '') : $item;
                $name = is_array($item) ? ($item['full_name'] ?? '') : '';
                if (empty($email)) continue;

                if (!$primaryAdded) {
                    $mail->addAddress($email, $name);
                    $primaryAdded = true;
                } else {
                    $mail->addBCC($email, $name);
                }
            }

            if (!$primaryAdded) {
                return false;
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\r\n", "\r\n\r\n"], $body));

            $mail->send();
            return true;
        } catch (Exception $e) {
            $this->lastError = $mail->ErrorInfo;
            error_log("Mail Batch Error: " . $mail->ErrorInfo);
            return false;
        }
    }
    public function sendPaymentSuccessMail($email, $data)
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->CharSet = 'UTF-8';
            $mail->SMTPDebug = 0;
            $mail->Timeout = 5;
            $mail->Host       = $this->host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->username;
            $mail->Password   = $this->password;
            $mail->SMTPSecure = isset($this->secure) ? $this->secure : 'ssl';
            $mail->Port       = $this->port;

            $mail->setFrom('no-reply@dpremiumhomes.com', 'The Premium Homes Ltd');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Payment Confirmation - The Premium Homes Ltd';

            $clientName = $data['client_name'] ?? 'Valued Client';
            $amount = $data['amount'] ?? '0.00';
            $projectName = $data['project_name'] ?? 'N/A';
            $flatName = $data['flat_name'] ?? 'N/A';
            $paymentDate = $data['payment_date'] ?? date('Y-m-d');

            $mail->Body = "
            <div style='font-family:Arial,sans-serif;background:#f4f6f8;padding:20px;'>
                <div style='max-width:600px;margin:auto;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.05);'>
                    <div style='background:#28a745;color:#fff;padding:20px;text-align:center;'>
                        <h2 style='margin:0;'>Payment Received Successfully!</h2>
                    </div>
                    <div style='padding:30px;color:#333;line-height:1.6;'>
                        <p style='font-size:16px;'>Dear <strong>$clientName</strong>,</p>
                        <p style='font-size:15px;'>We are delighted to inform you that we have successfully received your recent payment. Thank you for your continued trust in <strong>The Premium Homes Ltd</strong>.</p>
                        
                        <div style='background:#f8f9fa;border-left:4px solid #28a745;padding:15px;margin:25px 0;'>
                            <p style='margin:5px 0;'><strong>Amount Paid:</strong> BDT " . number_format((float)$amount, 2) . "</p>
                            <p style='margin:5px 0;'><strong>Project:</strong> $projectName</p>
                            <p style='margin:5px 0;'><strong>Flat/Unit:</strong> $flatName</p>
                            <p style='margin:5px 0;'><strong>Date of Payment:</strong> " . date('d M Y', strtotime($paymentDate)) . "</p>
                        </div>
                        
                        <p style='font-size:14px;color:#555;'>If you have any questions regarding this payment or your property, please feel free to contact us.</p>
                        <p style='font-size:14px;color:#555;margin-top:30px;'>Best Regards,<br><strong>The Premium Homes Ltd</strong></p>
                    </div>
                    <div style='background:#f1f1f1;padding:15px;text-align:center;font-size:12px;color:#777;'>
                        © " . date('Y') . " The Premium Homes Ltd. All rights reserved.
                    </div>
                </div>
            </div>
            ";

            $mail->send();
            return true;
        } catch (Exception $e) {
            $this->lastError = $mail->ErrorInfo;
            error_log("Payment Mail Error: " . $mail->ErrorInfo);
            return false;
        }
    }
}

// class MailService
// {
//     private $apiKey = "re_FybkVhb1_KTeF2JDqXDEadrgzXkCXP7dq";
//     private $url = "https://api.resend.com/emails";

//     public function sendMail($email, $title, $link, $assignedBy)
//     {
//         $html = $this->buildTemplate($title, $link, $assignedBy);

//         $data = [
//             "from" => "onboarding@resend.dev",
//             "to" => [$email],
//             "subject" => $title,
//             "html" => $html
//         ];

//         $ch = curl_init($this->url);

//         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//         curl_setopt($ch, CURLOPT_POST, true);
//         curl_setopt($ch, CURLOPT_HTTPHEADER, [
//             "Authorization: Bearer {$this->apiKey}",
//             "Content-Type: application/json"
//         ]);
//         curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

//         $response = curl_exec($ch);

//         if (curl_errno($ch)) {
//             throw new Exception(curl_error($ch));
//         }

//         curl_close($ch);

//         return json_decode($response, true);
//     }

//     private function buildTemplate($title, $link, $assignedBy)
//     {
//         return "
//         <div style='font-family:Arial,sans-serif;background:#f4f6f8;padding:20px;'>
            
//             <div style='max-width:600px;margin:auto;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.05);'>
                
//                 <div style='background:#28a745;color:#fff;padding:15px;text-align:center;'>
//                     <h2 style='margin:0;'>$title</h2>
//                 </div>

//                 <div style='padding:20px;color:#333;'>
//                     <p>Hello,</p>

//                     <p>You have a new task assigned to you.</p>

//                     <p style='margin:20px 0;'>
//                         <a href='$link' style='background:#28a745;color:#fff;padding:10px 20px;text-decoration:none;border-radius:5px;'>
//                             View Task
//                         </a>
//                     </p>

//                     <hr>

//                     <h4>Assigned By:</h4>
//                     <p>
//                         <strong>Name:</strong> {$assignedBy['full_name']}<br>
//                         <strong>Designation:</strong> {$assignedBy['designation_name']}<br>
//                         <strong>Department:</strong> {$assignedBy['department_name']}<br>
//                         <strong>Role:</strong> {$assignedBy['role_name']}
//                     </p>

//                     <p style='margin-top:30px;font-size:12px;color:#888;'>
//                         If button doesn't work:<br>
//                         $link
//                     </p>
//                 </div>

//                 <div style='background:#f1f1f1;padding:10px;text-align:center;font-size:12px;color:#666;'>
//                     © 2026 TPHL ERP
//                 </div>

//             </div>

//         </div>
//         ";
//     }
// } -->
