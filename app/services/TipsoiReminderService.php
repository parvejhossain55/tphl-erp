<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/ExternalApiService.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class TipsoiReminderService
{
    private $db;
    private $config;
    private $api;
    private $token;
    private $baseUrl;

    public function __construct($db = null)
    {
        if ($db) {
            $this->db = $db;
        } else {
            $config = require __DIR__ . '/../config/config.php';
            $this->db = new \mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
        }
        $this->config = require __DIR__ . '/../config/config.php';
        $this->api = new ExternalApiService($this->db);
        $this->token = $this->api->getBearerToken();
        $this->baseUrl = rtrim($this->config['tipsoi_base_url'] ?? 'https://app.tipsoi.ai/inovace-client/api/v1/', '/') . '/';

        $this->ensureTable();
    }

    /**
     * Ensure database table exists
     */
    public function ensureTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `tipsoi_email_logs` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `recipient_type` ENUM('approver', 'requester') NOT NULL DEFAULT 'approver',
          `approver_name` VARCHAR(150) DEFAULT NULL,
          `approver_email` VARCHAR(150) DEFAULT NULL,
          `recipient_name` VARCHAR(150) NOT NULL,
          `recipient_email` VARCHAR(150) NOT NULL,
          `pending_count` INT NOT NULL DEFAULT 0,
          `pending_level` VARCHAR(100) DEFAULT NULL,
          `request_details` TEXT DEFAULT NULL,
          `trigger_source` VARCHAR(50) NOT NULL DEFAULT 'login',
          `status` ENUM('Success', 'Failed') NOT NULL DEFAULT 'Success',
          `error_message` TEXT DEFAULT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `sent_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          INDEX `idx_email_sent` (`recipient_email`, `sent_at`),
          INDEX `idx_sent_at` (`sent_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $this->db->query($sql);
    }

    /**
     * Check if email should be sent based on 7-day rule and 23rd of month rule
     */
    public function shouldSendReminder(string $email, bool $force = false): array
    {
        $cleanEmail = strtolower(trim($email));
        if (empty($cleanEmail) || !filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
            return ['should_send' => false, 'reason' => 'Invalid email address'];
        }

        if ($force) {
            return ['should_send' => true, 'reason' => 'Forced execution'];
        }

        $stmt = $this->db->prepare("
            SELECT sent_at 
            FROM tipsoi_email_logs 
            WHERE (recipient_email = ? OR approver_email = ?) 
              AND status = 'Success' 
            ORDER BY sent_at DESC 
            LIMIT 1
        ");
        $stmt->bind_param("ss", $cleanEmail, $cleanEmail);
        $stmt->execute();
        $res = $stmt->get_result();
        $lastRow = $res->fetch_assoc();
        $stmt->close();

        $todayDay = date('d');
        $todayDate = date('Y-m-d');

        if ($lastRow) {
            $lastSentAt = $lastRow['sent_at'];
            $lastSentDate = date('Y-m-d', strtotime($lastSentAt));
            $daysSince = (strtotime($todayDate) - strtotime($lastSentDate)) / (60 * 60 * 24);

            // Never send more than once on the same calendar day
            if ($lastSentDate === $todayDate) {
                return [
                    'should_send' => false,
                    'reason' => "Already sent today ($lastSentAt)",
                    'last_sent' => $lastSentAt,
                    'days_since' => $daysSince
                ];
            }

            // Attendance cut-off date: 23rd of each month
            if ($todayDay === '23') {
                return [
                    'should_send' => true,
                    'reason' => "Today is 23rd (Cut-off date), not sent today (last sent: $lastSentAt)",
                    'last_sent' => $lastSentAt,
                    'days_since' => $daysSince
                ];
            }

            // 7-day (1 week) rule
            if ($daysSince >= 7) {
                return [
                    'should_send' => true,
                    'reason' => "7 or more days passed ($daysSince days) since last reminder ($lastSentAt)",
                    'last_sent' => $lastSentAt,
                    'days_since' => $daysSince
                ];
            }

            return [
                'should_send' => false,
                'reason' => "Cooldown active: sent $daysSince days ago ($lastSentAt), next due in " . (7 - $daysSince) . " days",
                'last_sent' => $lastSentAt,
                'days_since' => $daysSince
            ];
        }

        // Never received a reminder before
        return [
            'should_send' => true,
            'reason' => 'No previous reminder record found',
            'last_sent' => null,
            'days_since' => null
        ];
    }

    /**
     * Fetch all Tipsoi employees map
     */
    public function getEmployeesMap(): array
    {
        $ch = curl_init($this->baseUrl . "employee?order=asc&pageNumber=0&perPage=2000&status=1");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $this->token, 'Accept: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $res = curl_exec($ch);
        curl_close($ch);

        $d = json_decode($res, true);
        $employees = $d['data'] ?? $d['employees'] ?? [];

        $mapById = [];
        $mapByEmail = [];
        $mapByOfficeId = [];

        foreach ($employees as $e) {
            $tid = $e['id'] ?? $e['employeeId'] ?? null;
            $email = strtolower(trim($e['email'] ?? ''));
            $officeId = trim((string)($e['employeeOfficeId'] ?? $e['officeId'] ?? ''));

            if ($tid) $mapById[$tid] = $e;
            if ($email) $mapByEmail[$email] = $e;
            if ($officeId) $mapByOfficeId[$officeId] = $e;
        }

        return [
            'by_id' => $mapById,
            'by_email' => $mapByEmail,
            'by_office_id' => $mapByOfficeId,
            'all' => $employees
        ];
    }

    /**
     * Fetch all pending requests across the company from Tipsoi
     */
    public function fetchAllPendingRequests(): array
    {
        $empMapData = $this->getEmployeesMap();
        $empById = $empMapData['by_id'];

        $pendingByApprover = [];
        $pendingByRequester = [];
        $allRequests = [];

        // Helper to resolve email of an employee/approver
        $resolveEmail = function($tid) use ($empById) {
            if ($tid && isset($empById[$tid])) {
                $em = strtolower(trim($empById[$tid]['email'] ?? ''));
                if ($em) return $em;
            }
            return '';
        };

        // 1. Fetch Global Leaves
        $ch = curl_init($this->baseUrl . "leave-management/leave-history?page=0&count=1000&status=0");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $this->token, 'Accept: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $res = curl_exec($ch);
        curl_close($ch);
        $d = json_decode($res, true);
        $leaves = $d['leaveLogList'] ?? [];

        foreach ($leaves as $l) {
            $appTid = $l['appliedBy'] ?? $l['employeeId'] ?? null;
            $appEmail = $resolveEmail($appTid);
            $appName = $l['employeeName'] ?? ($empById[$appTid]['name'] ?? 'Unknown');
            $sDate = isset($l['startDate']) ? (is_numeric($l['startDate']) ? date('Y-m-d', (int)($l['startDate'] / 1000)) : $l['startDate']) : '—';
            $eDate = isset($l['endDate']) ? (is_numeric($l['endDate']) ? date('Y-m-d', (int)($l['endDate'] / 1000)) : $l['endDate']) : $sDate;
            $dateStr = ($sDate === $eDate) ? $sDate : "$sDate to $eDate";

            $firstAppId = $l['firstApproverId'] ?? null;
            $firstAppName = $l['firstApproverName'] ?? ($empById[$firstAppId]['name'] ?? '1st Approver');
            $firstAppStatus = (int)($l['firstApprovalStatus'] ?? 0);
            $firstAppEmail = $resolveEmail($firstAppId);

            $secondAppId = $l['secondApproverId'] ?? null;
            $secondAppName = $l['secondApproverName'] ?? ($empById[$secondAppId]['name'] ?? '2nd Approver');
            $secondAppStatus = (int)($l['secondApprovalStatus'] ?? 0);
            $secondAppEmail = $resolveEmail($secondAppId);

            $currentPendingLevel = ($firstAppStatus === 0) ? '1st Approval' : '2nd Approval';
            $targetApproverName = ($firstAppStatus === 0) ? $firstAppName : $secondAppName;
            $targetApproverEmail = ($firstAppStatus === 0) ? $firstAppEmail : $secondAppEmail;

            $item = [
                'id' => $l['id'] ?? null,
                'type' => 'Leave Request (' . ($l['categoryName'] ?? 'General') . ')',
                'applicant_id' => $appTid,
                'applicant_name' => $appName,
                'applicant_email' => $appEmail,
                'date' => $dateStr,
                'reason' => $l['reason'] ?? '—',
                'pending_level' => $currentPendingLevel,
                'first_approver_name' => $firstAppName,
                'first_approval_status' => $firstAppStatus,
                'second_approver_name' => $secondAppName,
                'second_approval_status' => $secondAppStatus,
                'target_approver_name' => $targetApproverName,
                'target_approver_email' => $targetApproverEmail
            ];
            $allRequests[] = $item;

            if ($targetApproverEmail) {
                if (!isset($pendingByApprover[$targetApproverEmail])) {
                    $pendingByApprover[$targetApproverEmail] = [
                        'name' => $targetApproverName,
                        'email' => $targetApproverEmail,
                        'requests' => []
                    ];
                }
                $pendingByApprover[$targetApproverEmail]['requests'][] = $item;
            }

            if ($appEmail) {
                if (!isset($pendingByRequester[$appEmail])) {
                    $pendingByRequester[$appEmail] = [
                        'name' => $appName,
                        'email' => $appEmail,
                        'requests' => []
                    ];
                }
                $pendingByRequester[$appEmail]['requests'][] = $item;
            }
        }

        // 2. Fetch Global Manual Entries (1: Manual, 2: Missing Punch, 3: Field Visit)
        $officeId = 4699;
        $allManual = [];
        foreach ([1, 2, 3] as $type) {
            $url = $this->baseUrl . "manual-entry?officeId=$officeId&order=asc&pageNumber=0&perPage=500&sortKey=reportPosition&manualEntryType=$type&applicationStatus=0";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $this->token, 'Accept: application/json']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $res = curl_exec($ch);
            curl_close($ch);
            $d = json_decode($res, true);
            $mList = $d['manualEntries'] ?? [];
            if (!empty($mList)) {
                $allManual = array_merge($allManual, $mList);
            }
        }

        foreach ($allManual as $m) {
            $appTid = $m['employeeId'] ?? null;
            $appEmail = $resolveEmail($appTid);
            $appName = $m['employeeName'] ?? ($empById[$appTid]['name'] ?? 'Unknown');
            $typeVal = (int)($m['manualEntryType'] ?? ($m['type'] ?? 1));
            $reqType = ($typeVal === 1) ? 'Manual Entry' : (($typeVal === 2) ? 'Missing Punch' : 'Field Visit');

            $sDateVal = $m['startDate'] ?? $m['entryTime'] ?? null;
            $dateStr = '—';
            if ($sDateVal) {
                $dateStr = is_numeric($sDateVal) ? (($sDateVal > 10000000000) ? date('Y-m-d', (int)($sDateVal / 1000)) : date('Y-m-d', (int)$sDateVal)) : date('Y-m-d', strtotime($sDateVal));
            }

            $firstAppId = $m['firstManualEntryApproverId'] ?? null;
            $firstAppName = $m['firstManualApproverName'] ?? ($empById[$firstAppId]['name'] ?? '1st Approver');
            $firstAppStatus = (int)($m['firstManualEntryApprovalStatus'] ?? 0);
            $firstAppEmail = $resolveEmail($firstAppId);

            $secondAppId = $m['secondManualEntryApproverId'] ?? null;
            $secondAppName = $m['secondManualApproverName'] ?? ($empById[$secondAppId]['name'] ?? '2nd Approver');
            $secondAppStatus = (int)($m['secondManualEntryApprovalStatus'] ?? 0);
            $secondAppEmail = $resolveEmail($secondAppId);

            $currentPendingLevel = ($firstAppStatus === 0) ? '1st Approval' : '2nd Approval';
            $targetApproverName = ($firstAppStatus === 0) ? $firstAppName : $secondAppName;
            $targetApproverEmail = ($firstAppStatus === 0) ? $firstAppEmail : $secondAppEmail;

            $item = [
                'id' => $m['id'] ?? null,
                'type' => $reqType,
                'applicant_id' => $appTid,
                'applicant_name' => $appName,
                'applicant_email' => $appEmail,
                'date' => $dateStr,
                'reason' => $m['reason'] ?? '—',
                'pending_level' => $currentPendingLevel,
                'first_approver_name' => $firstAppName,
                'first_approval_status' => $firstAppStatus,
                'second_approver_name' => $secondAppName,
                'second_approval_status' => $secondAppStatus,
                'target_approver_name' => $targetApproverName,
                'target_approver_email' => $targetApproverEmail
            ];
            $allRequests[] = $item;

            if ($targetApproverEmail) {
                if (!isset($pendingByApprover[$targetApproverEmail])) {
                    $pendingByApprover[$targetApproverEmail] = [
                        'name' => $targetApproverName,
                        'email' => $targetApproverEmail,
                        'requests' => []
                    ];
                }
                $pendingByApprover[$targetApproverEmail]['requests'][] = $item;
            }

            if ($appEmail) {
                if (!isset($pendingByRequester[$appEmail])) {
                    $pendingByRequester[$appEmail] = [
                        'name' => $appName,
                        'email' => $appEmail,
                        'requests' => []
                    ];
                }
                $pendingByRequester[$appEmail]['requests'][] = $item;
            }
        }

        // 3. Fetch Late Requests using parallel curl_multi in batches
        $extEmps = $empMapData['all'];
        $chunks = array_chunk($extEmps, 50);
        foreach ($chunks as $chunk) {
            $mh = curl_multi_init();
            $handles = [];
            foreach ($chunk as $e) {
                $tid = $e['id'] ?? $e['employeeId'] ?? null;
                if (!$tid) continue;
                $url = $this->baseUrl . "late-approval-management/late-approvals/$tid";
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $this->token, 'Accept: application/json']);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 6);
                curl_multi_add_handle($mh, $ch);
                $handles[$tid] = $ch;
            }

            $running = null;
            do {
                curl_multi_exec($mh, $running);
                curl_multi_select($mh);
            } while ($running > 0);

            foreach ($handles as $tid => $ch) {
                $content = curl_multi_getcontent($ch);
                $data = json_decode($content, true);
                $logs = $data['lateLogList'] ?? [];
                foreach ($logs as $l) {
                    if (($l['status'] ?? -1) === 0) {
                        $appTid = $tid;
                        $appEmail = $resolveEmail($appTid);
                        $appName = $empById[$appTid]['name'] ?? ($l['employeeName'] ?? 'Unknown');
                        $rawDate = $l['attendanceDate'] ?? $l['date'] ?? null;
                        $dateStr = '—';
                        if ($rawDate) {
                            $dateStr = is_numeric($rawDate) ? (($rawDate > 10000000000) ? date('Y-m-d', (int)($rawDate / 1000)) : date('Y-m-d', (int)$rawDate)) : date('Y-m-d', strtotime($rawDate));
                        }

                        $firstAppId = $l['firstApproverId'] ?? null;
                        $firstAppName = $l['firstApproverName'] ?? ($empById[$firstAppId]['name'] ?? '1st Approver');
                        $firstAppStatus = (int)($l['firstApprovalStatus'] ?? 0);
                        $firstAppEmail = $resolveEmail($firstAppId);

                        $secondAppId = $l['secondApproverId'] ?? null;
                        $secondAppName = $l['secondApproverName'] ?? ($empById[$secondAppId]['name'] ?? '2nd Approver');
                        $secondAppStatus = (int)($l['secondApprovalStatus'] ?? 0);
                        $secondAppEmail = $resolveEmail($secondAppId);

                        $currentPendingLevel = ($firstAppStatus === 0) ? '1st Approval' : '2nd Approval';
                        $targetApproverName = ($firstAppStatus === 0) ? $firstAppName : $secondAppName;
                        $targetApproverEmail = ($firstAppStatus === 0) ? $firstAppEmail : $secondAppEmail;

                        $lateReason = trim(($l['lateBy'] ? '[' . $l['lateBy'] . '] ' : '') . ($l['reason'] ?? ''));

                        $item = [
                            'id' => $l['id'] ?? null,
                            'type' => 'Late Excuse',
                            'applicant_id' => $appTid,
                            'applicant_name' => $appName,
                            'applicant_email' => $appEmail,
                            'date' => $dateStr,
                            'reason' => $lateReason ?: '—',
                            'pending_level' => $currentPendingLevel,
                            'first_approver_name' => $firstAppName,
                            'first_approval_status' => $firstAppStatus,
                            'second_approver_name' => $secondAppName,
                            'second_approval_status' => $secondAppStatus,
                            'target_approver_name' => $targetApproverName,
                            'target_approver_email' => $targetApproverEmail
                        ];
                        $allRequests[] = $item;

                        if ($targetApproverEmail) {
                            if (!isset($pendingByApprover[$targetApproverEmail])) {
                                $pendingByApprover[$targetApproverEmail] = [
                                    'name' => $targetApproverName,
                                    'email' => $targetApproverEmail,
                                    'requests' => []
                                ];
                            }
                            $pendingByApprover[$targetApproverEmail]['requests'][] = $item;
                        }

                        if ($appEmail) {
                            if (!isset($pendingByRequester[$appEmail])) {
                                $pendingByRequester[$appEmail] = [
                                    'name' => $appName,
                                    'email' => $appEmail,
                                    'requests' => []
                                ];
                            }
                            $pendingByRequester[$appEmail]['requests'][] = $item;
                        }
                    }
                }
                curl_multi_remove_handle($mh, $ch);
                curl_close($ch);
            }
            curl_multi_close($mh);
        }

        return [
            'total_pending' => count($allRequests),
            'all_requests' => $allRequests,
            'by_approver' => $pendingByApprover,
            'by_requester' => $pendingByRequester
        ];
    }

    /**
     * Handle single user login trigger:
     * When user logs in, check if user has pending approvals or pending requests,
     * check cooldown (7 days or 23rd cutoff), and send reminders.
     */
    public function handleUserLogin(string $userEmail): array
    {
        $cleanEmail = strtolower(trim($userEmail));
        if (empty($cleanEmail)) {
            return ['status' => 'Ignored', 'reason' => 'No email provided'];
        }

        // 1. Check if user is eligible to receive an email today
        $userEligibility = $this->shouldSendReminder($cleanEmail, false);

        // Fetch pending requests
        $pendingData = $this->fetchAllPendingRequests();
        $results = [];

        $hasApproverRole = isset($pendingData['by_approver'][$cleanEmail]);
        $hasRequesterRole = isset($pendingData['by_requester'][$cleanEmail]);

        // If user has nothing pending at all
        if (!$hasApproverRole && !$hasRequesterRole) {
            return [
                'status' => 'NoAction',
                'message' => 'User has no pending requests either as approver or requester'
            ];
        }

        // Case A: User has pending requests awaiting their approval
        if ($hasApproverRole && $userEligibility['should_send']) {
            $approverData = $pendingData['by_approver'][$cleanEmail];
            $results['approver_email'] = $this->sendApproverEmail(
                $approverData['name'],
                $cleanEmail,
                $approverData['requests'],
                'login'
            );
        }

        // Case B: User has requests submitted that are awaiting approval
        if ($hasRequesterRole) {
            $requesterData = $pendingData['by_requester'][$cleanEmail];

            // Send requester status reminder if eligible
            if ($userEligibility['should_send']) {
                $results['requester_email'] = $this->sendRequesterEmail(
                    $requesterData['name'],
                    $cleanEmail,
                    $requesterData['requests'],
                    'login'
                );
            }

            // Also check pending approvers for this user's requests:
            // "and jader kache approval pending ache tader kache email jabe"
            foreach ($requesterData['requests'] as $req) {
                $targetApproverEmail = $req['target_approver_email'] ?? '';
                if ($targetApproverEmail && $targetApproverEmail !== $cleanEmail) {
                    $approverEligibility = $this->shouldSendReminder($targetApproverEmail, false);
                    if ($approverEligibility['should_send'] && isset($pendingData['by_approver'][$targetApproverEmail])) {
                        $apprObj = $pendingData['by_approver'][$targetApproverEmail];
                        $results['nudge_approver_' . $targetApproverEmail] = $this->sendApproverEmail(
                            $apprObj['name'],
                            $targetApproverEmail,
                            $apprObj['requests'],
                            'login_requester_trigger'
                        );
                    }
                }
            }
        }

        return [
            'status' => 'Processed',
            'user' => $cleanEmail,
            'eligibility' => $userEligibility,
            'results' => $results
        ];
    }

    /**
     * Run company-wide sync (for Cron or Admin Manual Trigger)
     */
    public function runCompanyWideSync(bool $force = false, string $source = 'cron'): array
    {
        $pendingData = $this->fetchAllPendingRequests();
        $dispatched = [];
        $skipped = [];

        // 1. Process Approvers
        foreach ($pendingData['by_approver'] as $email => $data) {
            $eligibility = $this->shouldSendReminder($email, $force);
            if ($eligibility['should_send']) {
                $sendRes = $this->sendApproverEmail($data['name'], $email, $data['requests'], $source);
                $dispatched[] = $sendRes;
            } else {
                $skipped[] = [
                    'type' => 'approver',
                    'email' => $email,
                    'reason' => $eligibility['reason']
                ];
            }
        }

        // 2. Process Requesters
        foreach ($pendingData['by_requester'] as $email => $data) {
            $eligibility = $this->shouldSendReminder($email, $force);
            if ($eligibility['should_send']) {
                $sendRes = $this->sendRequesterEmail($data['name'], $email, $data['requests'], $source);
                $dispatched[] = $sendRes;
            } else {
                $skipped[] = [
                    'type' => 'requester',
                    'email' => $email,
                    'reason' => $eligibility['reason']
                ];
            }
        }

        return [
            'total_pending' => $pendingData['total_pending'],
            'total_approvers_pending' => count($pendingData['by_approver']),
            'total_requesters_pending' => count($pendingData['by_requester']),
            'dispatched_count' => count($dispatched),
            'skipped_count' => count($skipped),
            'dispatched' => $dispatched,
            'skipped' => $skipped
        ];
    }

    /**
     * Send email to an Approver
     */
    public function sendApproverEmail(string $name, string $email, array $requests, string $source = 'login'): array
    {
        $count = count($requests);
        if ($count === 0) {
            return ['status' => 'Skipped', 'message' => 'No pending requests'];
        }

        $appUrl = rtrim($this->config['app_url'] ?? 'https://thepremiumhomesltd.com/TPHL-ERP', '/');
        $actionUrl = $appUrl . '/my-tipsoi-info';

        $subject = "Action Required: {$count} Pending Tipsoi Approval Request" . ($count > 1 ? 's' : '') . " — TPHL ERP";

        $rowsHtml = "";
        $detailsLog = [];
        foreach ($requests as $r) {
            $badgeColor = ($r['pending_level'] === '1st Approval') ? '#0d6efd' : '#fd7e14';
            $rowsHtml .= "
            <tr style='border-bottom: 1px solid #e2e8f0;'>
                <td style='padding: 10px 12px; font-weight: 600; color: #1e293b;'>{$r['applicant_name']}</td>
                <td style='padding: 10px 12px; color: #334155;'>{$r['type']}</td>
                <td style='padding: 10px 12px; color: #475569;'>{$r['date']}</td>
                <td style='padding: 10px 12px; color: #64748b; font-size: 13px;'>{$r['reason']}</td>
                <td style='padding: 10px 12px; text-align: center;'>
                    <span style='background-color: {$badgeColor}; color: #ffffff; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;'>{$r['pending_level']}</span>
                </td>
            </tr>";
            $detailsLog[] = "{$r['type']} for {$r['applicant_name']} on {$r['date']} ({$r['pending_level']})";
        }

        $html = "
        <div style='font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; padding: 30px 15px;'>
            <div style='max-width: 650px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #e2e8f0;'>
                <div style='background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); padding: 24px 30px; color: #ffffff;'>
                    <h2 style='margin: 0; font-size: 20px; font-weight: 700; letter-spacing: -0.5px;'>TPHL ERP — Tipsoi Notification</h2>
                    <p style='margin: 6px 0 0 0; font-size: 13px; opacity: 0.9;'>Pending Approvals Reminder</p>
                </div>
                
                <div style='padding: 30px;'>
                    <p style='font-size: 16px; color: #1e293b; margin-top: 0;'>Dear <strong>{$name}</strong>,</p>
                    <p style='color: #475569; font-size: 14px; line-height: 1.6;'>
                        You have <strong style='color: #dc2626;'>{$count} pending request(s)</strong> waiting for your approval in the Tipsoi Smart Attendance System. Please review them at your earliest convenience to ensure accurate attendance and payroll processing.
                    </p>

                    <div style='margin: 20px 0; overflow-x: auto;'>
                        <table style='width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;'>
                            <thead>
                                <tr style='background-color: #f1f5f9; color: #475569; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;'>
                                    <th style='padding: 10px 12px;'>Employee</th>
                                    <th style='padding: 10px 12px;'>Type</th>
                                    <th style='padding: 10px 12px;'>Date</th>
                                    <th style='padding: 10px 12px;'>Reason</th>
                                    <th style='padding: 10px 12px; text-align: center;'>Stage</th>
                                </tr>
                            </thead>
                            <tbody>
                                {$rowsHtml}
                            </tbody>
                        </table>
                    </div>

                    <div style='text-align: center; margin: 30px 0 15px 0;'>
                        <a href='{$actionUrl}' style='display: inline-block; background: #2563eb; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 8px; font-weight: 600; font-size: 14px; box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);'>
                            Review & Approve in ERP
                        </a>
                    </div>
                </div>

                <div style='background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 16px 30px; text-align: center;'>
                    <p style='margin: 0; font-size: 12px; color: #94a3b8;'>
                        This is an automated notification from TPHL ERP Attendance & Tipsoi Synchronization. Please do not reply directly to this email.
                    </p>
                </div>
            </div>
        </div>";

        return $this->sendAndLog(
            'approver',
            $name,
            $email,
            $count,
            $requests[0]['pending_level'] ?? '1st Approval',
            implode("\n", $detailsLog),
            $subject,
            $html,
            $source
        );
    }

    /**
     * Send email to a Requester
     */
    public function sendRequesterEmail(string $name, string $email, array $requests, string $source = 'login'): array
    {
        $count = count($requests);
        if ($count === 0) {
            return ['status' => 'Skipped', 'message' => 'No pending requests'];
        }

        $appUrl = rtrim($this->config['app_url'] ?? 'https://thepremiumhomesltd.com/TPHL-ERP', '/');
        $actionUrl = $appUrl . '/my-tipsoi-info';

        $subject = "Status Update: Your {$count} Pending Tipsoi Application" . ($count > 1 ? 's' : '') . " — TPHL ERP";

        $rowsHtml = "";
        $detailsLog = [];
        foreach ($requests as $r) {
            $rowsHtml .= "
            <tr style='border-bottom: 1px solid #e2e8f0;'>
                <td style='padding: 10px 12px; font-weight: 600; color: #1e293b;'>{$r['type']}</td>
                <td style='padding: 10px 12px; color: #475569;'>{$r['date']}</td>
                <td style='padding: 10px 12px; color: #64748b; font-size: 13px;'>{$r['reason']}</td>
                <td style='padding: 10px 12px; color: #2563eb; font-weight: 500;'>
                    {$r['target_approver_name']} <span style='font-size: 11px; color: #64748b;'>({$r['pending_level']})</span>
                </td>
            </tr>";
            $detailsLog[] = "{$r['type']} on {$r['date']} (Pending at {$r['target_approver_name']})";
        }

        $html = "
        <div style='font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; padding: 30px 15px;'>
            <div style='max-width: 650px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #e2e8f0;'>
                <div style='background: linear-gradient(135deg, #059669 0%, #10b981 100%); padding: 24px 30px; color: #ffffff;'>
                    <h2 style='margin: 0; font-size: 20px; font-weight: 700; letter-spacing: -0.5px;'>TPHL ERP — Tipsoi Status Update</h2>
                    <p style='margin: 6px 0 0 0; font-size: 13px; opacity: 0.9;'>Pending Applications Notice</p>
                </div>
                
                <div style='padding: 30px;'>
                    <p style='font-size: 16px; color: #1e293b; margin-top: 0;'>Dear <strong>{$name}</strong>,</p>
                    <p style='color: #475569; font-size: 14px; line-height: 1.6;'>
                        This is a friendly reminder that you have <strong>{$count} application(s)</strong> currently pending approval in Tipsoi. If these are urgent, you may follow up with your line manager.
                    </p>

                    <div style='margin: 20px 0; overflow-x: auto;'>
                        <table style='width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;'>
                            <thead>
                                <tr style='background-color: #f1f5f9; color: #475569; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;'>
                                    <th style='padding: 10px 12px;'>Request Type</th>
                                    <th style='padding: 10px 12px;'>Date</th>
                                    <th style='padding: 10px 12px;'>Reason</th>
                                    <th style='padding: 10px 12px;'>Awaiting Approval From</th>
                                </tr>
                            </thead>
                            <tbody>
                                {$rowsHtml}
                            </tbody>
                        </table>
                    </div>

                    <div style='text-align: center; margin: 30px 0 15px 0;'>
                        <a href='{$actionUrl}' style='display: inline-block; background: #059669; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 8px; font-weight: 600; font-size: 14px; box-shadow: 0 2px 4px rgba(5, 150, 105, 0.2);'>
                            View My Applications in ERP
                        </a>
                    </div>
                </div>

                <div style='background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 16px 30px; text-align: center;'>
                    <p style='margin: 0; font-size: 12px; color: #94a3b8;'>
                        This is an automated notification from TPHL ERP Attendance System. Please do not reply directly to this email.
                    </p>
                </div>
            </div>
        </div>";

        return $this->sendAndLog(
            'requester',
            $name,
            $email,
            $count,
            'Pending Approval',
            implode("\n", $detailsLog),
            $subject,
            $html,
            $source
        );
    }

    /**
     * Send email via PHPMailer and record in database log
     */
    private function sendAndLog($recipientType, $name, $email, $count, $level, $details, $subject, $html, $source): array
    {
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $status = 'Failed';
        $errorMsg = '';

        try {
            $mail->isSMTP();
            $mail->Host       = $this->config['smtp_host'] ?? 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->config['smtp_user'] ?? '';
            $mail->Password   = $this->config['smtp_pass'] ?? '';
            $mail->SMTPSecure = (strtolower($this->config['smtp_secure'] ?? 'ssl') === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $this->config['smtp_port'] ?? 465;
            $mail->Timeout    = 15;

            $mail->setFrom($this->config['smtp_user'], 'TPHL ERP Notification');
            $mail->addAddress($email, $name);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $html;
            $mail->AltBody = strip_tags($html);

            $mail->send();
            $status = 'Success';
        } catch (Exception $e) {
            $status = 'Failed';
            $errorMsg = $mail->ErrorInfo ?: $e->getMessage();
        }

        // Insert into database
        $stmt = $this->db->prepare("
            INSERT INTO tipsoi_email_logs (
                recipient_type, approver_name, approver_email, 
                recipient_name, recipient_email, pending_count, 
                pending_level, request_details, trigger_source, 
                status, error_message, sent_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $approverName = ($recipientType === 'approver') ? $name : null;
        $approverEmail = ($recipientType === 'approver') ? $email : null;

        $stmt->bind_param(
            "sssssisssss",
            $recipientType,
            $approverName,
            $approverEmail,
            $name,
            $email,
            $count,
            $level,
            $details,
            $source,
            $status,
            $errorMsg
        );
        $stmt->execute();
        $stmt->close();

        return [
            'recipient_type' => $recipientType,
            'name' => $name,
            'email' => $email,
            'pending_count' => $count,
            'status' => $status,
            'error' => $errorMsg
        ];
    }
}
