<?php
class MailService
{
    private $serviceId = "service_hw116xq"; 
    private $templateId = "template_i9wjt57"; 
    private $publicKey = "8zuNoxwRwE5Z01vQ6"; 
    private $accessToken = "YrIy1pa2qgo5OfWL_gCrr" ;
 

    public function sendMail($email, $title, $link, $assignedBy)
    {
        $url = "https://api.emailjs.com/api/v1.0/email/send";

        $data = [
            "service_id" => $this->serviceId,
            "template_id" => $this->templateId,
            "user_id" => $this->publicKey,
            "accessToken" => $this->accessToken,
            "template_params" => [
                "to_email"    => $email,
                "title"       => $title,
                "link"        => $link,
                "from_name"   => $assignedBy['full_name'] ?? 'N/A',
                "designation" => $assignedBy['designation_name'] ?? 'N/A',
                "department"  => $assignedBy['department_name'] ?? 'N/A',
                "role"        => $assignedBy['role_name'] ?? 'N/A'
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            error_log("CURL Error: " . curl_error($ch));
            return false;
        }
        
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("EmailJS Error Response: " . $response);
            return false;
        }

        return true;
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
