<?php

class TodoMailService
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    private function getBaseUrl(): string
    {
        $base = rtrim($GLOBALS['base_url'] ?? '/TPHL-ERP', '/');
        if (str_starts_with($base, 'http')) {
            return $base;
        }
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
        return $scheme . '://' . $host . $base;
    }

    public function send($toEmail, $subject, $body)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO email_queue (to_email, subject, body, status) VALUES (?, ?, ?, 'pending')");
            if (!$stmt) {
                error_log("[TodoMailService] email_queue table missing or prepare failed: " . $this->db->error);
                return false;
            }
            $stmt->bind_param("sss", $toEmail, $subject, $body);
            $stmt->execute();
            $stmt->close();
            
            // Trigger the async processor immediately via cURL
            $url = $this->getBaseUrl() . '/process_email_queue.php';
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 200); // 200ms timeout
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            curl_close($ch);
            
            return true;
        } catch (Exception $e) {
            error_log("[TodoMailService] Failed to queue email to {$toEmail}: " . $e->getMessage());
            return false;
        }
    }
}
