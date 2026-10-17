<?php
/**
 * TPHL ERP - Scheduled Lead Automation Sync Cron
 * -----------------------------------------------------------------------------
 * Automatically syncs Google Sheets / Meta Leads at 2:00 AM every night.
 * 
 * Execution Methods:
 * 1. Linux Crontab (Daily 2:00 AM):
 *    0 2 * * * /usr/bin/php /var/www/your_erp_path/public/cron_lead_sync.php >> /var/www/your_erp_path/logs/cron_leads.log 2>&1
 * 
 * 2. cURL / URL Trigger:
 *    0 2 * * * curl -s "https://your-erp-domain.com/cron_lead_sync.php?token=ERP_LEAD_SYNC_SECURE_TOKEN_2026" > /dev/null 2>&1
 * -----------------------------------------------------------------------------
 */

set_time_limit(300);
ini_set('memory_limit', '256M');

require_once __DIR__ . '/../app/services/LeadAutomation/LeadSynchronizer.php';
$config = require __DIR__ . '/../app/config/lead_automation.php';

// Security token check (CLI runs without token; web requests require secret_token)
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
    $token = $_GET['token'] ?? '';
    $expectedToken = $config['secret_token'] ?? 'ERP_LEAD_SYNC_SECURE_TOKEN_2026';

    if (empty($token) || !hash_equals($expectedToken, $token)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Access Denied: Invalid Security Token.']);
        exit;
    }
}

try {
    $customUrl = $_GET['sheet_url'] ?? null;
    $synchronizer = new LeadSynchronizer($config);
    $result = $synchronizer->runSync($customUrl);

    $response = [
        'status'    => 'success',
        'timestamp' => date('Y-m-d H:i:s'),
        'data'      => $result,
        'message'   => sprintf(
            "Cron sync completed: %d leads inserted, %d duplicates skipped in %s seconds.",
            $result['inserted'] ?? 0,
            $result['skipped_duplicate'] ?? 0,
            $result['execution_time_seconds'] ?? 0
        )
    ];

    if (php_sapi_name() === 'cli') {
        echo "[CRON SUCCESS " . date('Y-m-d H:i:s') . "] " . $response['message'] . PHP_EOL;
    } else {
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    $errorMsg = "[CRON ERROR " . date('Y-m-d H:i:s') . "] " . $e->getMessage();
    error_log($errorMsg);

    if (php_sapi_name() === 'cli') {
        echo $errorMsg . PHP_EOL;
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
