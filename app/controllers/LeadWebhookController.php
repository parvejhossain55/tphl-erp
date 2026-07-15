<?php

/**
 * LeadWebhookController
 *
 * Receives real-time leads from Freshsales Webhooks, verifies them,
 * and saves them to the freshsales_leads_cache table. Also handles
 * exporting selected leads to CSV for the digital marketing team.
 */
class LeadWebhookController extends Controller
{
    protected $db;
    protected FreshsalesService $freshsalesService;

    public function __construct($db)
    {
        $this->db = $db;
        $config = require dirname(__DIR__) . '/config/config.php';
        $this->freshsalesService = new FreshsalesService($config);
    }

    /**
     * Webhook receiver for lead.created / contact.created event
     */
    public function receive(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(405, 'Method Not Allowed');
            return;
        }

        $rawPayload = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_X_FRESHSALES_SIGNATURE'] ?? $_SERVER['HTTP_X_FS_SIGNATURE'] ?? '';

        if (!$this->freshsalesService->verifySignature($rawPayload, $signature)) {
            $this->saveLog('unknown', null, null, null, 'failed', 'Invalid signature');
            $this->jsonResponse(401, 'Unauthorized');
            return;
        }

        $payload = json_decode($rawPayload, true);
        if (empty($payload)) {
            $this->saveLog('unknown', null, null, null, 'failed', 'Invalid/Empty JSON payload');
            $this->jsonResponse(400, 'Invalid JSON');
            return;
        }

        $lead = $payload['lead'] ?? $payload['data'] ?? $payload;
        
        $firstName = trim($lead['contact_first_name'] ?? $lead['first_name'] ?? '');
        $lastName = trim($lead['contact_last_name'] ?? $lead['last_name'] ?? '');
        $fullName = trim("$firstName $lastName") ?: ($lead['contact_name'] ?? $lead['name'] ?? 'Unknown');

        $phone = $lead['contact_mobile_number'] ?? $lead['mobile_number'] ?? $lead['contact_phone'] ?? $lead['phone'] ?? null;
        $freshsalesId = $lead['contact_id'] ?? $lead['id'] ?? null;

        // If phone is missing from standard fields, search payload recursively (e.g. chat messages)
        $extracted = false;
        if (empty($phone)) {
            $phone = $this->extractPhoneNumber($payload);
            if (!empty($phone)) {
                $extracted = true;
                // If we have a Freshsales contact ID, push the phone number back to the CRM profile
                if (!empty($freshsalesId)) {
                    $this->freshsalesService->updateContact($freshsalesId, ['mobile_number' => $phone]);
                }
            }
        }

        if (empty($phone)) {
            $this->saveLog('lead.created', $freshsalesId, null, null, 'skipped', 'No phone number in payload');
            $this->jsonResponse(200, 'Skipped: No Phone Number');
            return;
        }

        $source = $lead['contact_source_name'] ?? $lead['lead_source']['name'] ?? $lead['source'] ?? null;
        $platform = $this->freshsalesService->mapPlatform($source);
        
        $campaign = $lead['contact_campaign_name'] ?? $lead['campaign_name'] ?? $lead['utm_campaign'] ?? $lead['campaign'] ?? $lead['custom_field']['campaign_name'] ?? null;
        $city = $lead['contact_city'] ?? $lead['city'] ?? null;
        $country = $lead['contact_country'] ?? $lead['country'] ?? 'Bangladesh';
        $jobTitle = $lead['contact_job_title'] ?? $lead['job_title'] ?? null;
        $companyName = $lead['contact_company_name'] ?? $lead['contact_sales_account_name'] ?? $lead['company_name'] ?? null;
        $email = $lead['contact_email'] ?? $lead['email'] ?? null;

        // Custom questionnaire fields parsing (with contact_ prefix fallback)
        $custom = $lead['custom_field'] ?? $lead;
        $aptSize = $custom['contact_which_apartment_size_are_you_interested_in'] ?? $custom['which_apartment_size_are_you_interested_in'] ?? null;
        $shopFloor = $custom['contact_which_floor_are_you_for_shop'] ?? $custom['which_floor_are_you_for_shop'] ?? null;
        $whenBuy = $custom['contact_when_are_you_planning_to_buy'] ?? $custom['when_are_you_planning_to_buy'] ?? null;
        $siteVisit = $custom['contact_would_you_like_to_schedule_a_site_visit'] ?? $custom['would_you_like_to_schedule_a_site_visit'] ?? null;
        $bestTime = $custom['contact_when_is_the_best_time_to_contact_you'] ?? $custom['when_is_the_best_time_to_contact_you'] ?? null;

        // Insert or update on duplicate key (matching phone or freshsales_id)
        $stmt = $this->db->prepare("
            INSERT INTO freshsales_leads_cache 
                (freshsales_id, campaign_name, platform, 
                 which_apartment_size_are_you_interested_in, which_floor_are_you_for_shop, 
                 when_are_you_planning_to_buy, would_you_like_to_schedule_a_site_visit, 
                 when_is_the_best_time_to_contact_you, full_name, job_title, company_name, 
                 phone_number, email, city, country, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                full_name = VALUES(full_name),
                campaign_name = VALUES(campaign_name),
                phone_number = VALUES(phone_number),
                email = VALUES(email),
                which_apartment_size_are_you_interested_in = VALUES(which_apartment_size_are_you_interested_in),
                which_floor_are_you_for_shop = VALUES(which_floor_are_you_for_shop),
                when_are_you_planning_to_buy = VALUES(when_are_you_planning_to_buy),
                would_you_like_to_schedule_a_site_visit = VALUES(would_you_like_to_schedule_a_site_visit),
                when_is_the_best_time_to_contact_you = VALUES(when_is_the_best_time_to_contact_you)
        ");

        if (!$stmt) {
            $this->saveLog('lead.created', $freshsalesId, $platform, $phone, 'failed', 'Prepare failed: ' . $this->db->error);
            $this->jsonResponse(500, 'Database error');
            return;
        }

        $stmt->bind_param('sssssssssssssss', 
            $freshsalesId, $campaign, $platform, 
            $aptSize, $shopFloor, $whenBuy, $siteVisit, $bestTime, 
            $fullName, $jobTitle, $companyName, $phone, $email, $city, $country
        );

        $logEvent = $extracted ? 'lead.extracted' : 'lead.created';
        if ($stmt->execute()) {
            $this->saveLog($logEvent, $freshsalesId, $platform, $phone, 'success', null);
            $this->jsonResponse(200, 'Lead Cached Successfully');
        } else {
            $this->saveLog($logEvent, $freshsalesId, $platform, $phone, 'failed', $stmt->error);
            $this->jsonResponse(500, 'Database Insert Failed');
        }
        $stmt->close();
    }

    /**
     * CSV Export Action
     */
    public function exportCsv(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $ids = $_POST['ids'] ?? [];
        if (empty($ids)) {
            $_SESSION['error'] = "No leads selected for export!";
            header("Location: " . $GLOBALS['base_url'] . "/digital-marketing/freshsales-leads");
            exit;
        }

        // CSV Headers matching existing leads upload format
        $headers = [
            'campaign_name',
            'platform',
            'which_apartment_size_are_you_interested_in',
            'which_floor_are_you_for_shop',
            'when_are_you_planning_to_buy',
            'would_you_like_to_schedule_a_site_visit',
            'when_is_the_best_time_to_contact_you',
            'full_name',
            'job_title',
            'company_name',
            'phone_number',
            'city',
            'country'
        ];

        // Set response headers to force download CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="freshsales_leads_' . date('Y-m-d_H-i-s') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);

        $idPlaceholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("
            SELECT id, campaign_name, platform, 
                   which_apartment_size_are_you_interested_in, which_floor_are_you_for_shop, 
                   when_are_you_planning_to_buy, would_you_like_to_schedule_a_site_visit, 
                   when_is_the_best_time_to_contact_you, full_name, job_title, company_name, 
                   phone_number, city, country 
            FROM freshsales_leads_cache
            WHERE id IN ($idPlaceholders)
        ");

        $types = str_repeat('i', count($ids));
        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
        $result = $stmt->get_result();

        $exportedIds = [];
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['campaign_name'],
                $row['platform'],
                $row['which_apartment_size_are_you_interested_in'],
                $row['which_floor_are_you_for_shop'],
                $row['when_are_you_planning_to_buy'],
                $row['would_you_like_to_schedule_a_site_visit'],
                $row['when_is_the_best_time_to_contact_you'],
                $row['full_name'],
                $row['job_title'],
                $row['company_name'],
                $row['phone_number'],
                $row['city'],
                $row['country']
            ]);
            $exportedIds[] = $row['id'];
        }
        fclose($output);
        $stmt->close();

        // Mark as exported
        if (!empty($exportedIds)) {
            $updatePlaceholders = implode(',', array_fill(0, count($exportedIds), '?'));
            $updateStmt = $this->db->prepare("
                UPDATE freshsales_leads_cache 
                SET is_exported = 1, exported_at = NOW() 
                WHERE id IN ($updatePlaceholders)
            ");
            $updateStmt->bind_param(str_repeat('i', count($exportedIds)), ...$exportedIds);
            $updateStmt->execute();
            $updateStmt->close();
        }
        exit;
    }

    private function saveLog(string $event, ?string $fsId, ?string $platform, ?string $phone, string $status, ?string $error): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO freshsales_webhook_logs (event_type, freshsales_id, platform, phone_number, status, error_message)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        if ($stmt) {
            $stmt->bind_param('ssssss', $event, $fsId, $platform, $phone, $status, $error);
            $stmt->execute();
            $stmt->close();
        }
    }

    private function jsonResponse(int $code, string $message): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['status' => $code, 'message' => $message]);
        exit;
    }

    /**
     * Recursively search an array for any string matching a Bangladeshi phone number.
     */
    private function extractPhoneNumber(array $array): ?string
    {
        // Matches: 01711223344, +8801711223344, 01711-223344, +880 1711-223344, etc.
        $pattern = '/(?:\+?88)?01[3-9][\s-]?\d{2}[\s-]?\d{2}[\s-]?\d{4}/';
        
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $found = $this->extractPhoneNumber($value);
                if ($found) {
                    return $found;
                }
            } elseif (is_string($value)) {
                if (preg_match($pattern, $value, $matches)) {
                    // Clean up: remove spaces, hyphens, keep leading + if present
                    $clean = preg_replace('/[\s-]/', '', $matches[0]);
                    return $clean;
                }
            }
        }
        return null;
    }
}
