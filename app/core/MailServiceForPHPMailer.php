<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;



class MailService
{
    private $db = null;
    private $host = 'mail.dpremiumhomes.com';
    private $username = 'no-reply@dpremiumhomes.com';
    private $password = 'djcdawaxdbmdotlj';
    private $port = 465;
    private $secure = 'ssl';
    public $lastError = '';

    public function __construct($db = null)
    {
        $this->db = $db;
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

    public function sendTaskMailWithCC($toEmail, $title, $link, $assignedBy, $assignedTo, $ccUsers = [], $mentionUsers = [], $taskDetails = [])
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->CharSet = 'UTF-8';
            $mail->SMTPDebug = 0;
            $mail->Timeout = 10;

            $mail->Host       = $this->host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->username;
            $mail->Password   = $this->password;
            $mail->SMTPSecure = isset($this->secure) ? $this->secure : 'ssl';
            $mail->Port       = $this->port;

            $mail->setFrom('no-reply@dpremiumhomes.com', 'TPHL ERP');
            if (!empty($toEmail)) {
                $mail->addAddress($toEmail, $assignedTo['full_name'] ?? '');
            }

            // Add CC recipients
            foreach ($ccUsers as $cc) {
                if (!empty($cc['email']) && filter_var($cc['email'], FILTER_VALIDATE_EMAIL)) {
                    $mail->addCC($cc['email'], $cc['full_name'] ?? '');
                }
            }

            $mail->isHTML(true);
            $mail->Subject = 'New Task Assigned: ' . $title;

            $ccNames = !empty($ccUsers) ? implode(', ', array_map(fn($u) => $u['full_name'] ?? $u['email'], $ccUsers)) : '';
            $mentionNames = !empty($mentionUsers) ? implode(', ', array_map(fn($u) => $u['full_name'] ?? $u['email'], $mentionUsers)) : '';

            $priorityBadge = ucfirst($taskDetails['priority'] ?? 'Medium');
            $startFormatted = !empty($taskDetails['start_date']) ? date('d M Y', strtotime($taskDetails['start_date'])) : 'N/A';
            $deadlineFormatted = !empty($taskDetails['deadline']) ? date('d M Y', strtotime($taskDetails['deadline'])) : 'N/A';

            $body = "
            <div style='font-family: \"Segoe UI\", Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; padding: 35px 15px;'>
                <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;'>
                    <div style='background: linear-gradient(135deg, #16a34a, #15803d); padding: 22px 28px;'>
                        <div style='font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; color: rgba(255,255,255,0.8); margin-bottom: 4px;'>TPHL ERP &middot; Task Management</div>
                        <h2 style='margin: 0; color: #ffffff; font-size: 20px; font-weight: 700;'>New Task Assignment</h2>
                    </div>
                    <div style='padding: 28px;'>
                        <p style='color: #334155; font-size: 15px; margin: 0 0 14px;'>Hello <strong>" . htmlspecialchars($assignedTo['full_name'] ?? 'Team Member') . "</strong>,</p>
                        <p style='color: #475569; font-size: 14px; line-height: 1.6; margin: 0 0 20px;'>A new task has been assigned to you in TPHL ERP. Details are given below:</p>
                        
                        <div style='background-color: #f1f5f9; border-left: 4px solid #16a34a; border-radius: 6px; padding: 18px 20px; margin-bottom: 22px;'>
                            <div style='font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 8px;'>" . htmlspecialchars($title) . "</div>";
                            if (!empty($taskDetails['description'])) {
                                $body .= "<div style='font-size: 13.5px; color: #475569; margin-bottom: 12px; line-height: 1.5;'>" . nl2br(strip_tags($taskDetails['description'])) . "</div>";
                            }
                            $body .= "
                            <div style='font-size: 12.5px; color: #64748b; line-height: 1.8;'>
                                <div>📅 <strong>Start:</strong> {$startFormatted} &nbsp;|&nbsp; ⏰ <strong>Deadline:</strong> {$deadlineFormatted}</div>
                                <div> <strong>Priority:</strong> <span style='font-weight: 600; color: #16a34a;'>{$priorityBadge}</span></div>";
                                if (!empty($mentionNames)) {
                                    $body .= "<div>📢 <strong>Mentioned:</strong> <span style='color: #2563eb;'>{$mentionNames}</span></div>";
                                }
                                if (!empty($ccNames)) {
                                    $body .= "<div>✉️ <strong>CC:</strong> <span style='color: #0891b2;'>{$ccNames}</span></div>";
                                }
                            $body .= "
                            </div>
                        </div>

                        <div style='background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 14px 18px; margin-bottom: 22px; font-size: 13px; color: #475569;'>
                            <strong>Assigned By:</strong> " . htmlspecialchars($assignedBy['full_name'] ?? 'Management') . "
                            " . (!empty($assignedBy['designation_name']) ? "<br><span style='color:#64748b;'>{$assignedBy['designation_name']} &middot; {$assignedBy['department_name']}</span>" : "") . "
                        </div>
                        
                        <div style='text-align: center; margin: 25px 0 10px;'>
                            <a href='{$link}' style='background-color: #16a34a; color: #ffffff; padding: 12px 28px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px; display: inline-block;'>View Task Details &rarr;</a>
                        </div>
                    </div>
                    <div style='background-color: #f8fafc; padding: 14px 28px; text-align: center; border-top: 1px solid #f1f5f9;'>
                        <p style='margin: 0; font-size: 11.5px; color: #94a3b8;'>This is an automated notification from TPHL ERP. Please do not reply directly.</p>
                    </div>
                </div>
            </div>";

            $mail->Body = $body;
            $mail->AltBody = "New Task: {$title}\nAssigned To: {$assignedTo['full_name']}\nAssigned By: {$assignedBy['full_name']}\nLink: {$link}";

            $mail->send();
            return true;
        } catch (Exception $e) {
            $this->lastError = $mail->ErrorInfo;
            error_log("[MailService] Task Mail Error: " . $mail->ErrorInfo);
            return false;
        }
    }

    public function sendMentionMail($toEmail, $mentionName, $title, $link, $assignedBy, $assignedTo, $allMentions = [], $allCcs = [], $taskDetails = [])
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->CharSet = 'UTF-8';
            $mail->SMTPDebug = 0;
            $mail->Timeout = 10;

            $mail->Host       = $this->host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->username;
            $mail->Password   = $this->password;
            $mail->SMTPSecure = isset($this->secure) ? $this->secure : 'ssl';
            $mail->Port       = $this->port;

            $mail->setFrom('no-reply@dpremiumhomes.com', 'TPHL ERP');
            $mail->addAddress($toEmail, $mentionName);

            $mail->isHTML(true);
            $mail->Subject = '[Mentioned] You were mentioned in task: ' . $title;

            $mentionNames = !empty($allMentions) ? implode(', ', array_map(fn($u) => $u['full_name'] ?? $u['email'], $allMentions)) : $mentionName;
            $ccNames = !empty($allCcs) ? implode(', ', array_map(fn($u) => $u['full_name'] ?? $u['email'], $allCcs)) : '';

            $priorityBadge = ucfirst($taskDetails['priority'] ?? 'Medium');
            $startFormatted = !empty($taskDetails['start_date']) ? date('d M Y', strtotime($taskDetails['start_date'])) : 'N/A';
            $deadlineFormatted = !empty($taskDetails['deadline']) ? date('d M Y', strtotime($taskDetails['deadline'])) : 'N/A';

            $body = "
            <div style='font-family: \"Segoe UI\", Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; padding: 35px 15px;'>
                <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;'>
                    <div style='background: linear-gradient(135deg, #2563eb, #1d4ed8); padding: 22px 28px;'>
                        <div style='font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; color: rgba(255,255,255,0.8); margin-bottom: 4px;'>TPHL ERP &middot; Task Mention Alert</div>
                        <h2 style='margin: 0; color: #ffffff; font-size: 20px; font-weight: 700;'>You were Mentioned in a Task</h2>
                    </div>
                    <div style='padding: 28px;'>
                        <div style='background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; font-size: 14px; color: #1e40af;'>
                            📢 <strong>Notice:</strong> <strong>" . htmlspecialchars($assignedBy['full_name'] ?? 'Someone') . "</strong> mentioned you in a task assigned to <strong>" . htmlspecialchars($assignedTo['full_name'] ?? 'an employee') . "</strong>.
                        </div>

                        <p style='color: #334155; font-size: 15px; margin: 0 0 14px;'>Hello <strong>" . htmlspecialchars($mentionName) . "</strong>,</p>
                        <p style='color: #475569; font-size: 14px; line-height: 1.6; margin: 0 0 20px;'>Here are the complete details of the task you were mentioned in:</p>
                        
                        <div style='background-color: #f1f5f9; border-left: 4px solid #2563eb; border-radius: 6px; padding: 18px 20px; margin-bottom: 22px;'>
                            <div style='font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 8px;'>" . htmlspecialchars($title) . "</div>";
                            if (!empty($taskDetails['description'])) {
                                $body .= "<div style='font-size: 13.5px; color: #475569; margin-bottom: 12px; line-height: 1.5;'>" . nl2br(strip_tags($taskDetails['description'])) . "</div>";
                            }
                            $body .= "
                            <div style='font-size: 12.5px; color: #64748b; line-height: 1.8;'>
                                <div>👤 <strong>Assigned To:</strong> <span style='font-weight: 600; color: #0f172a;'>" . htmlspecialchars($assignedTo['full_name'] ?? 'N/A') . "</span></div>
                                <div>📢 <strong>Mentioned Users:</strong> <span style='color: #2563eb;'>{$mentionNames}</span></div>";
                                if (!empty($ccNames)) {
                                    $body .= "<div>✉️ <strong>CC Users:</strong> <span style='color: #0891b2;'>{$ccNames}</span></div>";
                                }
                            $body .= "
                                <div>📅 <strong>Start Date:</strong> {$startFormatted} &nbsp;|&nbsp; ⏰ <strong>Deadline:</strong> {$deadlineFormatted}</div>
                                <div>🚩 <strong>Priority:</strong> <span style='font-weight: 600; color: #2563eb;'>{$priorityBadge}</span></div>
                            </div>
                        </div>

                        <div style='background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 14px 18px; margin-bottom: 22px; font-size: 13px; color: #475569;'>
                            <strong>Assigned By:</strong> " . htmlspecialchars($assignedBy['full_name'] ?? 'Management') . "
                            " . (!empty($assignedBy['designation_name']) ? "<br><span style='color:#64748b;'>{$assignedBy['designation_name']} &middot; {$assignedBy['department_name']}</span>" : "") . "
                        </div>
                        
                        <div style='text-align: center; margin: 25px 0 10px;'>
                            <a href='{$link}' style='background-color: #2563eb; color: #ffffff; padding: 12px 28px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px; display: inline-block;'>View Task in Dashboard &rarr;</a>
                        </div>
                    </div>
                    <div style='background-color: #f8fafc; padding: 14px 28px; text-align: center; border-top: 1px solid #f1f5f9;'>
                        <p style='margin: 0; font-size: 11.5px; color: #94a3b8;'>This is an automated notification from TPHL ERP. Please do not reply directly.</p>
                    </div>
                </div>
            </div>";

            $mail->Body = $body;
            $mail->AltBody = "You were mentioned in task: {$title}\nAssigned To: {$assignedTo['full_name']}\nAssigned By: {$assignedBy['full_name']}\nLink: {$link}";

            $mail->send();
            return true;
        } catch (Exception $e) {
            $this->lastError = $mail->ErrorInfo;
            error_log("[MailService] Mention Mail Error: " . $mail->ErrorInfo);
            return false;
        }
    }

    public function sendMail($email, $title, $link, $assignedBy)
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

            $mail->setFrom('no-reply@dpremiumhomes.com', 'TPHL ERP');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = $title;

            $mail->Body = $this->buildTemplate($title, $link, $assignedBy);

            $mail->send();
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


    public function getDb()
    {
        if ($this->db instanceof \mysqli) {
            return $this->db;
        }
        if (isset($GLOBALS['db']) && $GLOBALS['db'] instanceof \mysqli) {
            return $GLOBALS['db'];
        }
        try {
            $config = require __DIR__ . '/../config/config.php';
            $db = new \mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
            if (!$db->connect_error) {
                $db->set_charset('utf8mb4');
                $this->db = $db;
                return $this->db;
            }
        } catch (\Throwable $t) {}
        return null;
    }

    public function getBaseUrl(): string
    {
        $base = rtrim($GLOBALS['base_url'] ?? '/TPHL-ERP', '/');
        if (str_starts_with($base, 'http')) {
            return $base;
        }
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
        return $scheme . '://' . $host . $base;
    }

    public function triggerQueueProcessorAsync(): void
    {
        $script = realpath(__DIR__ . '/../../public/process_email_queue.php');

        // 1. In CLI mode (Linux/Windows cron), run process_email_queue.php locally instead of cURLing localhost:8080
        if (PHP_SAPI === 'cli' && $script && file_exists($script)) {
            try {
                if (DIRECTORY_SEPARATOR === '\\') {
                    pclose(popen("start /B php " . escapeshellarg($script), "r"));
                    return;
                } elseif (function_exists('exec')) {
                    $phpBin = (defined('PHP_BINARY') && PHP_BINARY) ? PHP_BINARY : 'php';
                    exec(escapeshellcmd($phpBin) . ' ' . escapeshellarg($script) . ' > /dev/null 2>&1 &');
                    return;
                }
            } catch (\Throwable $e) {}
        }

        // 2. Non-blocking HTTP cURL call with 150ms timeout (web requests / fallback)
        try {
            $url = $this->getBaseUrl() . '/process_email_queue.php';
            $ch = curl_init($url);
            if ($ch) {
                curl_setopt($ch, CURLOPT_TIMEOUT_MS, 150);
                curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_exec($ch);
                curl_close($ch);
            }
        } catch (\Throwable $e) {}

        // 3. On Windows web requests, also trigger background detached process
        if (DIRECTORY_SEPARATOR === '\\' && $script && file_exists($script)) {
            try {
                pclose(popen("start /B php " . escapeshellarg($script), "r"));
            } catch (\Throwable $e) {}
        }
    }

    public function buildRequisitionEmailBody($toName, $actionType, $requisitionType, $requisitionDetails, $link): string
    {
        $amtFormatted = number_format((float)($requisitionDetails['amount'] ?? 0), 2);
        $title = htmlspecialchars($requisitionDetails['title'] ?? 'N/A');
        $requesterName = htmlspecialchars($requisitionDetails['requester_name'] ?? 'N/A');
        $noteHtml = '';
        if (!empty($requisitionDetails['note'])) {
            $noteText = htmlspecialchars($requisitionDetails['note']);
            $noteHtml = "<tr>
                <td style='padding: 8px; border: 1px solid #ddd; background: #f9f9f9; font-weight: bold;'>Approval Note</td>
                <td style='padding: 8px; border: 1px solid #ddd;'>{$noteText}</td>
            </tr>";
        }

        return "
        <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; border-radius: 8px;'>
            <h2 style='color: #10a34d; border-bottom: 2px solid #0e6119; padding-bottom: 10px;'>{$requisitionType} Update</h2>
            <p>Dear {$toName},</p>
            <p>A <strong>{$requisitionType}</strong> requires your attention. Current Status: <strong>{$actionType}</strong></p>
            
            <table style='width: 100%; border-collapse: collapse; margin-top: 15px; margin-bottom: 20px;'>
                <tr>
                    <td style='padding: 8px; border: 1px solid #ddd; background: #f9f9f9; font-weight: bold; width: 30%;'>Title/Purpose</td>
                    <td style='padding: 8px; border: 1px solid #ddd;'>{$title}</td>
                </tr>
                <tr>
                    <td style='padding: 8px; border: 1px solid #ddd; background: #f9f9f9; font-weight: bold;'>Requested By</td>
                    <td style='padding: 8px; border: 1px solid #ddd;'>{$requesterName}</td>
                </tr>
                <tr>
                    <td style='padding: 8px; border: 1px solid #ddd; background: #f9f9f9; font-weight: bold;'>Amount</td>
                    <td style='padding: 8px; border: 1px solid #ddd;'>৳ {$amtFormatted}</td>
                </tr>
                {$noteHtml}
            </table>
            
            <div style='text-align: center; margin-top: 30px;'>
                <a href='{$link}' style='background-color: #03791d; color: #ffffff; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>View Requisition in ERP</a>
            </div>
            
            <p style='margin-top: 30px; font-size: 12px; color: #807d7d; text-align: center;'>
                This is an automated notification from TPHL ERP. Please do not reply to this email.
            </p>
        </div>";
    }

    public function queueRequisitionNotification($toEmail, $toName, $actionType, $requisitionType, $requisitionDetails, $link, $triggerNow = true): bool
    {
        if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $subject = "Action Required: {$requisitionType} - {$actionType}";
        $body = $this->buildRequisitionEmailBody($toName, $actionType, $requisitionType, $requisitionDetails, $link);

        $db = $this->getDb();
        if ($db) {
            $stmt = $db->prepare("INSERT INTO email_queue (to_email, subject, body, status) VALUES (?, ?, ?, 'pending')");
            if ($stmt) {
                $stmt->bind_param("sss", $toEmail, $subject, $body);
                $res = $stmt->execute();
                $stmt->close();

                if ($res && $triggerNow) {
                    $this->triggerQueueProcessorAsync();
                }
                return (bool)$res;
            }
        }

        return $this->sendGenericMail($toEmail, $subject, $body);
    }

    public function queueRequisitionNotificationBatch($recipients, $actionType, $requisitionType, $requisitionDetails, $link): bool
    {
        if (empty($recipients)) {
            return false;
        }

        $db = $this->getDb();
        $subject = "Action Required: {$requisitionType} - {$actionType}";
        $queuedCount = 0;

        if ($db) {
            $stmt = $db->prepare("INSERT INTO email_queue (to_email, subject, body, status) VALUES (?, ?, ?, 'pending')");
            if ($stmt) {
                foreach ($recipients as $item) {
                    $email = trim(is_array($item) ? ($item['email'] ?? '') : $item);
                    $name = is_array($item) ? ($item['full_name'] ?? 'User') : 'User';
                    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $body = $this->buildRequisitionEmailBody($name, $actionType, $requisitionType, $requisitionDetails, $link);
                        $stmt->bind_param("sss", $email, $subject, $body);
                        $stmt->execute();
                        $queuedCount++;
                    }
                }
                $stmt->close();

                if ($queuedCount > 0) {
                    $this->triggerQueueProcessorAsync();
                    return true;
                }
            }
        }

        return false;
    }

    public function sendRequisitionNotification($toEmail, $toName, $actionType, $requisitionType, $requisitionDetails, $link)
    {
        return $this->queueRequisitionNotification($toEmail, $toName, $actionType, $requisitionType, $requisitionDetails, $link, true);
    }

    public function sendRequisitionNotificationBatch($recipients, $actionType, $requisitionType, $requisitionDetails, $link)
    {
        return $this->queueRequisitionNotificationBatch($recipients, $actionType, $requisitionType, $requisitionDetails, $link);
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
