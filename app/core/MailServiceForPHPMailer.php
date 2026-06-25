<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;



class MailService
{
    private $host = 'mail.dpremiumhomes.com'; // or smtp.gmail.com
    private $username = 'tech@dpremiumhomes.com';
    private $password = 'Tech@12345'; // 
    private $port = 465;

    public function sendMail($email, $title, $link, $assignedBy)
    {
        $mail = new PHPMailer(true);

        try {
            // SMTP config

            // SMTP config
            $mail->isSMTP();
            $mail->SMTPDebug = 2; // for debugging, set to 0 in production

            $mail->Host       = $this->host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->username;
            $mail->Password   = $this->password;
            $mail->SMTPSecure = 'ssl';
            $mail->Port       = $this->port;

            // Sender & receiver
            $mail->setFrom($this->username, 'TPHL ERP');
            $mail->addAddress($email);

            // Email content
            $mail->isHTML(true);
            $mail->Subject = $title;

            $mail->Body = $this->buildTemplate($title, $link, $assignedBy);

            $mail->send();

            // echo "Email sent successfully to $email";
            return true;
        } catch (Exception $e) {
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
