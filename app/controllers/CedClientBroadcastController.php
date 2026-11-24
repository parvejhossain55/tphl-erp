<?php
require_once __DIR__ . '/../core/Controller.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailerException;

/**
 * CED Client SMS & Email Broadcast Controller
 *
 * Allows Head of Client Experience (Role 39) & Admin/Management to filter active
 * project clients by Project, CED Agent, Due Type/Amount, Upcoming EMI Window,
 * Follow-up Status, Booking Status, and Contact Availability, then dispatch
 * personalized SMS (via SmsService / Automas) and Email (via PHPMailer SMTP)
 * in non-blocking AJAX batches with full audit logs and failed-item retry.
 */
class CedClientBroadcastController extends Controller
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Authorization check: CED Head (Role 39), Super Admin (1), Admin (3),
     * Chief Officer (33), Board of Director (38).
     */
    protected function canAccessBroadcast(): bool
    {
        $roleId    = (int)($_SESSION['user']['role_id'] ?? 0);
        $secRoleId = (int)($_SESSION['user']['secondary_role_id'] ?? 0);
        $priRoleId = (int)($_SESSION['user']['primary_role_id'] ?? 0);
        $roleName  = strtolower(trim((string)($_SESSION['user']['role_name'] ?? '')));

        return in_array($roleId, [1, 3, 33, 38, 39], true)
            || in_array($secRoleId, [1, 3, 38, 39], true)
            || in_array($priRoleId, [1, 3, 38, 39], true)
            || strpos($roleName, 'head of client experience') !== false
            || (function_exists('isHeadOfClientExperience') && isHeadOfClientExperience());
    }

    protected function requireAuth(bool $isAjax = false): void
    {
        if (!$this->canAccessBroadcast()) {
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Unauthorized. Only Head of CED and Admin can access Client SMS & Email Broadcast.']);
                exit;
            }
            if (function_exists('setError')) {
                setError('You do not have permission to access CED Client SMS & Email Broadcast.');
            }
            header("Location: {$GLOBALS['base_url']}/ced-dashboard");
            exit;
        }
    }

    /**
     * Main Page: CED Client SMS & Email Broadcast
     */
    public function index()
    {
        $this->requireAuth(false);

        $projectsList = $this->fetchAllActiveProjects();
        $agentsList   = $this->fetchActiveCedAgents();

        $projectAgentMap = [];
        $resMap = $this->db->query("
            SELECT project_id, user_id
            FROM ced_project_assignments
            WHERE status = 'active'
            ORDER BY id ASC
        ");
        if ($resMap) {
            while ($mr = $resMap->fetch_assoc()) {
                $pId = (int)$mr['project_id'];
                $uId = (int)$mr['user_id'];
                if ($pId > 0 && $uId > 0 && !isset($projectAgentMap[$pId])) {
                    $projectAgentMap[$pId] = $uId;
                }
            }
        }

        $config = require dirname(__DIR__) . '/config/config.php';
        $globalSmsTestingMode = !empty($config['sms_testing_mode']);

        $preselectedProjectId = isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int)$_GET['project_id'] : 0;
        $preselectedAgentId   = ($preselectedProjectId > 0 && isset($projectAgentMap[$preselectedProjectId]))
            ? (int)$projectAgentMap[$preselectedProjectId]
            : 0;

        $this->view('ced/ced-client-broadcast', [
            'current_route'          => 'ced-client-broadcast',
            'page_title'             => 'CED Client SMS & Email Broadcast',
            'projects_list'          => $projectsList,
            'agents_list'            => $agentsList,
            'project_agent_map'      => $projectAgentMap,
            'global_sms_testing'     => $globalSmsTestingMode,
            'preselected_project_id' => $preselectedProjectId,
            'preselected_agent_id'   => $preselectedAgentId,
        ]);
    }

    /**
     * AJAX: Fetch filtered active clients with financial due & upcoming EMI breakdown.
     */
    public function fetchClientsAjax()
    {
        $this->requireAuth(true);
        header('Content-Type: application/json; charset=utf-8');

        $projectIds = [];
        if (isset($_GET['project_ids']) && is_array($_GET['project_ids'])) {
            $projectIds = array_values(array_unique(array_filter(array_map('intval', $_GET['project_ids']))));
        } elseif (!empty($_GET['project_ids']) && is_string($_GET['project_ids'])) {
            $projectIds = array_values(array_unique(array_filter(array_map('intval', explode(',', $_GET['project_ids'])))));
        } elseif (!empty($_GET['project_id'])) {
            $pId = (int)$_GET['project_id'];
            if ($pId > 0) {
                $projectIds = [$pId];
            }
        }

        $filters = [
            'project_ids'             => $projectIds,
            'agent_id'                => (int)($_GET['agent_id'] ?? 0),
            'due_filter'              => trim((string)($_GET['due_filter'] ?? '')),
            'min_due'                 => isset($_GET['min_due']) && $_GET['min_due'] !== '' ? (float)$_GET['min_due'] : null,
            'upcoming_emi_days'       => trim((string)($_GET['upcoming_emi_days'] ?? '')),
            'exclude_advance_covered' => !isset($_GET['exclude_advance_covered']) || $_GET['exclude_advance_covered'] === '1',
            'followup_filter'         => trim((string)($_GET['followup_filter'] ?? '')),
            'booking_status'          => trim((string)($_GET['booking_status'] ?? '')),
            'contact_filter'          => trim((string)($_GET['contact_filter'] ?? '')),
            'search'                  => trim((string)($_GET['search'] ?? '')),
        ];

        $clients = $this->queryFilteredClients($filters);

        $summary = [
            'total'          => count($clients),
            'valid_phone'    => 0,
            'valid_email'    => 0,
            'total_due_sum'  => 0.0,
            'emi_due_sum'    => 0.0,
            'upcoming_count' => 0,
        ];

        foreach ($clients as $c) {
            if ($c['phone_valid']) {
                $summary['valid_phone']++;
            }
            if ($c['email_valid']) {
                $summary['valid_email']++;
            }
            $summary['total_due_sum'] += $c['total_due'];
            $summary['emi_due_sum']   += $c['regular_emi_due'];
            if (!empty($c['next_emi_date'])) {
                $summary['upcoming_count']++;
            }
        }
        $summary['total_due_sum'] = round($summary['total_due_sum'], 2);
        $summary['emi_due_sum']   = round($summary['emi_due_sum'], 2);

        echo json_encode([
            'success' => true,
            'clients' => $clients,
            'summary' => $summary,
        ]);
        exit;
    }

    /**
     * AJAX: Create a broadcast campaign and queue per-recipient jobs in ced_broadcast_logs.
     */
    public function createCampaignAjax()
    {
        $this->requireAuth(true);
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
            exit;
        }

        $channel = trim((string)($_POST['channel'] ?? 'sms'));
        if (!in_array($channel, ['sms', 'email', 'both'], true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid communication channel.']);
            exit;
        }

        $rawBookingIds = $_POST['booking_ids'] ?? [];
        if (is_string($rawBookingIds)) {
            $rawBookingIds = explode(',', $rawBookingIds);
        }
        $bookingIds = array_values(array_unique(array_filter(array_map('intval', (array)$rawBookingIds))));

        if (empty($bookingIds)) {
            echo json_encode(['success' => false, 'message' => 'Please select at least one client.']);
            exit;
        }

        $campaignTitle = trim((string)($_POST['campaign_title'] ?? ''));
        $smsMessage    = trim((string)($_POST['sms_message'] ?? ''));
        $emailSubject  = trim((string)($_POST['email_subject'] ?? ''));
        $emailBody     = trim((string)($_POST['email_body'] ?? ''));
        $includeSec    = !empty($_POST['include_secondary_contacts']) ? 1 : 0;
        $isTesting     = !empty($_POST['is_testing']) ? 1 : 0;
        $filterSummary = trim((string)($_POST['filter_summary'] ?? ''));

        $needSms   = in_array($channel, ['sms', 'both'], true);
        $needEmail = in_array($channel, ['email', 'both'], true);

        if ($needSms && $smsMessage === '') {
            echo json_encode(['success' => false, 'message' => 'SMS message body is required.']);
            exit;
        }
        if ($needEmail && ($emailSubject === '' || strip_tags($emailBody) === '')) {
            echo json_encode(['success' => false, 'message' => 'Email subject and message body are required.']);
            exit;
        }

        // Re-fetch authoritative client records from DB by selected booking IDs
        $clients = $this->queryFilteredClients(['booking_ids' => $bookingIds]);
        if (empty($clients)) {
            echo json_encode(['success' => false, 'message' => 'No valid active bookings found for the selected clients.']);
            exit;
        }

        require_once dirname(__DIR__) . '/services/SmsService.php';
        $smsService = new SmsService($this->db);

        // Determine if messages contain booking-specific placeholders
        $unitPlaceholders = ['{flat_name}', '{total_due}', '{land_share_due}', '{dc_due}', '{emi_due}', '{next_emi_date}', '{next_emi_amount}'];
        $smsIsUnitSpecific   = $this->containsAnyPlaceholder($smsMessage, $unitPlaceholders);
        $emailIsUnitSpecific = $this->containsAnyPlaceholder($emailSubject . ' ' . $emailBody, $unitPlaceholders);

        $jobs            = [];
        $skippedJobs     = [];
        $seenSmsContacts = [];
        $seenEmails      = [];
        $skippedInvalid  = 0;
        $deduplicated    = 0;

        foreach ($clients as $c) {
            // SMS jobs
            if ($needSms) {
                $phones = [];
                if (!empty($c['phone'])) {
                    $phones[] = $c['phone'];
                }
                if ($includeSec && !empty($c['second_owner_phone'])) {
                    $phones[] = $c['second_owner_phone'];
                }

                $validPhonesForBooking = [];
                foreach ($phones as $rawPhone) {
                    $v = $smsService->validatePhoneNumber($rawPhone);
                    if ($v['valid']) {
                        $validPhonesForBooking[] = $v['normalized'];
                    }
                }
                $validPhonesForBooking = array_values(array_unique($validPhonesForBooking));

                $renderedSms = $this->renderPlaceholders($smsMessage, $c);
                if (empty($validPhonesForBooking)) {
                    $skippedInvalid++;
                    $rawContact = trim((string)($c['phone'] ?? ''));
                    $reason = $rawContact !== ''
                        ? 'Invalid / Foreign BD mobile — Manual SMS/WhatsApp needed'
                        : 'No phone number — Manual follow-up needed';
                    $skippedJobs[] = [
                        'lead_id'           => $c['lead_id'],
                        'booking_id'        => $c['booking_id'],
                        'project_id'        => $c['project_id'],
                        'client_name'       => $c['client_name'],
                        'flat_name'         => $c['flat_name'],
                        'channel'           => 'sms',
                        'recipient_contact' => $rawContact !== '' ? $rawContact : 'Missing',
                        'subject'           => null,
                        'rendered_message'  => $renderedSms,
                        'gateway_response'  => $reason,
                    ];
                } else {
                    foreach ($validPhonesForBooking as $normPhone) {
                        if (!$smsIsUnitSpecific && isset($seenSmsContacts[$normPhone])) {
                            $deduplicated++;
                            continue;
                        }
                        $seenSmsContacts[$normPhone] = true;
                        $jobs[] = [
                            'lead_id'           => $c['lead_id'],
                            'booking_id'        => $c['booking_id'],
                            'project_id'        => $c['project_id'],
                            'client_name'       => $c['client_name'],
                            'flat_name'         => $c['flat_name'],
                            'channel'           => 'sms',
                            'recipient_contact' => $normPhone,
                            'subject'           => null,
                            'rendered_message'  => $renderedSms,
                        ];
                    }
                }
            }

            // Email jobs
            if ($needEmail) {
                $emails = [];
                if (!empty($c['email'])) {
                    $emails[] = trim($c['email']);
                }
                if ($includeSec) {
                    if (!empty($c['alt_email'])) {
                        $emails[] = trim($c['alt_email']);
                    }
                    if (!empty($c['second_owner_email'])) {
                        $emails[] = trim($c['second_owner_email']);
                    }
                }

                $validEmailsForBooking = [];
                foreach ($emails as $rawEmail) {
                    $em = strtolower(trim($rawEmail));
                    if ($em !== '' && filter_var($em, FILTER_VALIDATE_EMAIL)) {
                        $validEmailsForBooking[] = $em;
                    }
                }
                $validEmailsForBooking = array_values(array_unique($validEmailsForBooking));

                $renderedSubj = $this->renderPlaceholders($emailSubject, $c);
                $renderedBody = $this->renderPlaceholders($emailBody, $c);
                if (empty($validEmailsForBooking)) {
                    $skippedInvalid++;
                    $rawEmailContact = trim((string)($c['email'] ?? ''));
                    $reason = $rawEmailContact !== ''
                        ? 'Invalid email format — Manual follow-up needed'
                        : 'No email address — Manual follow-up needed';
                    $skippedJobs[] = [
                        'lead_id'           => $c['lead_id'],
                        'booking_id'        => $c['booking_id'],
                        'project_id'        => $c['project_id'],
                        'client_name'       => $c['client_name'],
                        'flat_name'         => $c['flat_name'],
                        'channel'           => 'email',
                        'recipient_contact' => $rawEmailContact !== '' ? $rawEmailContact : 'Missing',
                        'subject'           => $renderedSubj,
                        'rendered_message'  => $renderedBody,
                        'gateway_response'  => $reason,
                    ];
                } else {
                    foreach ($validEmailsForBooking as $em) {
                        if (!$emailIsUnitSpecific && isset($seenEmails[$em])) {
                            $deduplicated++;
                            continue;
                        }
                        $seenEmails[$em] = true;
                        $jobs[] = [
                            'lead_id'           => $c['lead_id'],
                            'booking_id'        => $c['booking_id'],
                            'project_id'        => $c['project_id'],
                            'client_name'       => $c['client_name'],
                            'flat_name'         => $c['flat_name'],
                            'channel'           => 'email',
                            'recipient_contact' => $em,
                            'subject'           => $renderedSubj,
                            'rendered_message'  => $renderedBody,
                        ];
                    }
                }
            }
        }

        if (empty($jobs) && empty($skippedJobs)) {
            echo json_encode([
                'success' => false,
                'message' => 'No recipients found for the selected clients.',
            ]);
            exit;
        }

        if ($campaignTitle === '') {
            $campaignTitle = strtoupper($channel) . ' Broadcast — ' . date('d M Y, h:i A');
        }

        $createdBy    = (int)($_SESSION['user']['id'] ?? 0);
        $totalClients = count($clients);
        $totalJobs    = count($jobs);
        $initialStatus = $totalJobs > 0 ? 'processing' : 'completed';

        $stmtCamp = $this->db->prepare("
            INSERT INTO ced_broadcast_campaigns
                (campaign_title, channel, filter_summary, sms_message, email_subject, email_body,
                 include_secondary_contacts, is_testing, total_clients, total_jobs, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$stmtCamp) {
            echo json_encode(['success' => false, 'message' => 'Database error creating campaign.']);
            exit;
        }

        $smsMsgDb  = $needSms ? $smsMessage : null;
        $emSubjDb  = $needEmail ? $emailSubject : null;
        $emBodyDb  = $needEmail ? $emailBody : null;
        $filterDb  = $filterSummary !== '' ? $filterSummary : null;

        $stmtCamp->bind_param(
            'ssssssiiiisi',
            $campaignTitle,
            $channel,
            $filterDb,
            $smsMsgDb,
            $emSubjDb,
            $emBodyDb,
            $includeSec,
            $isTesting,
            $totalClients,
            $totalJobs,
            $initialStatus,
            $createdBy
        );
        if (!$stmtCamp->execute()) {
            $stmtCamp->close();
            echo json_encode(['success' => false, 'message' => 'Failed to save campaign record.']);
            exit;
        }
        $campaignId = (int)$stmtCamp->insert_id;
        $stmtCamp->close();

        // Insert pending dispatchable jobs
        if (!empty($jobs)) {
            $stmtLog = $this->db->prepare("
                INSERT INTO ced_broadcast_logs
                    (campaign_id, lead_id, booking_id, project_id, client_name, flat_name,
                     channel, recipient_contact, subject, rendered_message, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            if ($stmtLog) {
                foreach ($jobs as $j) {
                    $stmtLog->bind_param(
                        'iiiissssss',
                        $campaignId,
                        $j['lead_id'],
                        $j['booking_id'],
                        $j['project_id'],
                        $j['client_name'],
                        $j['flat_name'],
                        $j['channel'],
                        $j['recipient_contact'],
                        $j['subject'],
                        $j['rendered_message']
                    );
                    $stmtLog->execute();
                }
                $stmtLog->close();
            }
        }

        // Insert skipped_invalid rows for manual follow-up tracking
        if (!empty($skippedJobs)) {
            $stmtSkip = $this->db->prepare("
                INSERT INTO ced_broadcast_logs
                    (campaign_id, lead_id, booking_id, project_id, client_name, flat_name,
                     channel, recipient_contact, subject, rendered_message, status, gateway_response)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'skipped_invalid', ?)
            ");
            if ($stmtSkip) {
                foreach ($skippedJobs as $sj) {
                    $stmtSkip->bind_param(
                        'iiiisssssss',
                        $campaignId,
                        $sj['lead_id'],
                        $sj['booking_id'],
                        $sj['project_id'],
                        $sj['client_name'],
                        $sj['flat_name'],
                        $sj['channel'],
                        $sj['recipient_contact'],
                        $sj['subject'],
                        $sj['rendered_message'],
                        $sj['gateway_response']
                    );
                    $stmtSkip->execute();
                }
                $stmtSkip->close();
            }
        }

        if ($totalJobs === 0) {
            $this->db->query("UPDATE ced_broadcast_campaigns SET completed_at = NOW() WHERE id = {$campaignId}");
        }

        echo json_encode([
            'success'         => true,
            'campaign_id'     => $campaignId,
            'total_clients'   => $totalClients,
            'total_jobs'      => $totalJobs,
            'skipped_invalid' => $skippedInvalid,
            'deduplicated'    => $deduplicated,
            'is_testing'      => (bool)$isTesting,
        ]);
        exit;
    }

    /**
     * AJAX: Process next chunk of pending jobs for a campaign (prevents PHP timeout).
     */
    public function processBatchAjax()
    {
        $this->requireAuth(true);
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
            exit;
        }

        $campaignId = (int)($_POST['campaign_id'] ?? 0);
        $batchSize  = max(1, min(15, (int)($_POST['batch_size'] ?? 8)));

        if ($campaignId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid campaign ID.']);
            exit;
        }

        $qCamp = $this->db->query("SELECT id, is_testing, total_jobs FROM ced_broadcast_campaigns WHERE id = {$campaignId} LIMIT 1");
        $campaign = $qCamp ? $qCamp->fetch_assoc() : null;
        if (!$campaign) {
            echo json_encode(['success' => false, 'message' => 'Campaign not found.']);
            exit;
        }

        $campaignTesting = ((int)$campaign['is_testing'] === 1);
        $config = require dirname(__DIR__) . '/config/config.php';
        $globalSmsTesting = !empty($config['sms_testing_mode']);

        // Select next pending batch
        $pendingRows = [];
        $qPending = $this->db->query("
            SELECT id, lead_id, booking_id, project_id, client_name, flat_name,
                   channel, recipient_contact, subject, rendered_message
            FROM ced_broadcast_logs
            WHERE campaign_id = {$campaignId}
              AND status = 'pending'
            ORDER BY id ASC
            LIMIT {$batchSize}
        ");
        if ($qPending) {
            while ($r = $qPending->fetch_assoc()) {
                $pendingRows[] = $r;
            }
        }

        if (!empty($pendingRows)) {
            // Mark selected rows as 'processing' to avoid concurrent duplicate dispatch
            $lockIds = implode(',', array_map(fn($r) => (int)$r['id'], $pendingRows));
            $this->db->query("UPDATE ced_broadcast_logs SET status = 'processing' WHERE id IN ({$lockIds})");

            $smsRows   = array_values(array_filter($pendingRows, fn($r) => $r['channel'] === 'sms'));
            $emailRows = array_values(array_filter($pendingRows, fn($r) => $r['channel'] === 'email'));

            $updStmt = $this->db->prepare("
                UPDATE ced_broadcast_logs
                SET status = ?, gateway_response = ?, sent_at = NOW()
                WHERE id = ?
            ");

            // 1. Dispatch SMS jobs
            if (!empty($smsRows)) {
                require_once dirname(__DIR__) . '/services/SmsService.php';
                $smsService = new SmsService($this->db);

                foreach ($smsRows as $row) {
                    $logId = (int)$row['id'];
                    if ($campaignTesting || $globalSmsTesting) {
                        // Safe dry-run: log in sms_logs via SmsService testing mode without calling Automas API
                        $res = $smsService->sendSms($row['recipient_contact'], $row['rendered_message'], [
                            'client_id'  => $row['lead_id'],
                            'booking_id' => $row['booking_id'],
                            'sms_type'   => 'ced_broadcast',
                        ], true);
                        $st  = 'skipped_testing';
                        $msg = $res['message'] ?? 'Skipped SMS dispatch (Testing Mode ON)';
                    } else {
                        $res = $smsService->sendSms($row['recipient_contact'], $row['rendered_message'], [
                            'client_id'  => $row['lead_id'],
                            'booking_id' => $row['booking_id'],
                            'sms_type'   => 'ced_broadcast',
                        ], false);
                        $st  = !empty($res['success']) ? 'sent' : 'failed';
                        $msg = (string)($res['response'] ?? ($res['message'] ?? ''));
                    }

                    if ($updStmt) {
                        $updStmt->bind_param('ssi', $st, $msg, $logId);
                        $updStmt->execute();
                    }
                }
            }

            // 2. Dispatch Email jobs (single SMTPKeepAlive connection per batch)
            if (!empty($emailRows)) {
                if ($campaignTesting) {
                    foreach ($emailRows as $row) {
                        $logId = (int)$row['id'];
                        $st    = 'skipped_testing';
                        $msg   = 'Skipped Email dispatch (Testing Mode ON)';
                        if ($updStmt) {
                            $updStmt->bind_param('ssi', $st, $msg, $logId);
                            $updStmt->execute();
                        }
                    }
                } else {
                    $mailer = $this->createBatchMailer($config);
                    foreach ($emailRows as $row) {
                        $logId = (int)$row['id'];
                        if (!$mailer) {
                            $st  = 'failed';
                            $msg = 'SMTP mailer initialization failed.';
                        } else {
                            try {
                                $mailer->clearAddresses();
                                $mailer->clearCCs();
                                $mailer->clearBCCs();
                                $mailer->addAddress($row['recipient_contact'], $row['client_name'] ?? '');
                                $mailer->isHTML(true);
                                $mailer->Subject = $row['subject'] ?: 'Notice from The Premium Homes Ltd';
                                $wrappedHtml     = $this->wrapEmailHtml($mailer->Subject, $row['rendered_message']);
                                $mailer->Body    = $wrappedHtml;
                                $mailer->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], ["\n", "\n", "\n", "\n\n"], $row['rendered_message']));
                                $mailer->send();
                                $st  = 'sent';
                                $msg = 'Email sent via SMTP';
                            } catch (MailerException $e) {
                                $st  = 'failed';
                                $msg = 'SMTP Error: ' . ($mailer->ErrorInfo ?: $e->getMessage());
                            } catch (\Throwable $e) {
                                $st  = 'failed';
                                $msg = 'Email Error: ' . $e->getMessage();
                            }
                        }

                        if ($updStmt) {
                            $updStmt->bind_param('ssi', $st, $msg, $logId);
                            $updStmt->execute();
                        }
                    }
                    if ($mailer) {
                        try {
                            $mailer->smtpClose();
                        } catch (\Throwable $e) {
                        }
                    }
                }
            }

            if ($updStmt) {
                $updStmt->close();
            }
        }

        // Recalculate accurate campaign counters
        $stats = $this->syncCampaignStats($campaignId);

        echo json_encode([
            'success'            => true,
            'campaign_id'        => $campaignId,
            'processed_in_batch' => count($pendingRows),
            'remaining'          => $stats['remaining'],
            'total_jobs'         => $stats['total_jobs'],
            'sms_sent'           => $stats['sms_sent'],
            'sms_failed'         => $stats['sms_failed'],
            'email_sent'         => $stats['email_sent'],
            'email_failed'       => $stats['email_failed'],
            'status'             => $stats['status'],
            'done'               => ($stats['remaining'] === 0),
        ]);
        exit;
    }

    /**
     * AJAX: Re-queue failed items of a campaign for retry.
     */
    public function retryFailedAjax()
    {
        $this->requireAuth(true);
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
            exit;
        }

        $campaignId = (int)($_POST['campaign_id'] ?? 0);
        if ($campaignId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid campaign ID.']);
            exit;
        }

        $this->db->query("
            UPDATE ced_broadcast_logs
            SET status = 'pending', gateway_response = NULL, sent_at = NULL
            WHERE campaign_id = {$campaignId}
              AND status = 'failed'
        ");
        $affected = (int)$this->db->affected_rows;

        if ($affected <= 0) {
            echo json_encode(['success' => false, 'message' => 'No failed recipients found to retry.']);
            exit;
        }

        $this->db->query("UPDATE ced_broadcast_campaigns SET status = 'processing', completed_at = NULL WHERE id = {$campaignId}");

        echo json_encode([
            'success'     => true,
            'campaign_id' => $campaignId,
            'retry_count' => $affected,
        ]);
        exit;
    }

    /**
     * AJAX: Recent campaign history list.
     */
    public function historyAjax()
    {
        $this->requireAuth(true);
        header('Content-Type: application/json; charset=utf-8');

        $rows = [];
        $res = $this->db->query("
            SELECT
                c.*,
                COALESCE(sk.skipped_count, 0) AS skipped_count,
                COALESCE(e.full_name, u.full_name, u.user_name, CONCAT('User #', c.created_by)) AS creator_name
            FROM ced_broadcast_campaigns c
            LEFT JOIN (
                SELECT campaign_id, COUNT(*) AS skipped_count
                FROM ced_broadcast_logs
                WHERE status = 'skipped_invalid'
                GROUP BY campaign_id
            ) sk ON sk.campaign_id = c.id
            LEFT JOIN users u ON u.id = c.created_by
            LEFT JOIN employees e ON (u.employee_id = e.id OR e.user_id = u.id)
            ORDER BY c.id DESC
            LIMIT 50
        ");
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $rows[] = [
                    'id'                 => (int)$r['id'],
                    'campaign_title'     => $r['campaign_title'],
                    'channel'            => $r['channel'],
                    'is_testing'         => (int)$r['is_testing'] === 1,
                    'total_clients'      => (int)$r['total_clients'],
                    'total_jobs'         => (int)$r['total_jobs'],
                    'sms_sent_count'     => (int)$r['sms_sent_count'],
                    'sms_failed_count'   => (int)$r['sms_failed_count'],
                    'email_sent_count'   => (int)$r['email_sent_count'],
                    'email_failed_count' => (int)$r['email_failed_count'],
                    'skipped_count'      => (int)$r['skipped_count'],
                    'status'             => $r['status'],
                    'creator_name'       => $r['creator_name'],
                    'created_at'         => $r['created_at'],
                    'completed_at'       => $r['completed_at'],
                ];
            }
        }

        echo json_encode(['success' => true, 'campaigns' => $rows]);
        exit;
    }

    /**
     * AJAX: Per-recipient delivery logs for a campaign.
     */
    public function campaignDetailsAjax()
    {
        $this->requireAuth(true);
        header('Content-Type: application/json; charset=utf-8');

        $campaignId = (int)($_GET['campaign_id'] ?? 0);
        if ($campaignId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid campaign ID.']);
            exit;
        }

        $qCamp = $this->db->query("SELECT * FROM ced_broadcast_campaigns WHERE id = {$campaignId} LIMIT 1");
        $campaign = $qCamp ? $qCamp->fetch_assoc() : null;
        if (!$campaign) {
            echo json_encode(['success' => false, 'message' => 'Campaign not found.']);
            exit;
        }

        $logs = [];
        $qLogs = $this->db->query("
            SELECT
                l.id, l.client_name, l.flat_name, l.channel, l.recipient_contact,
                l.subject, l.rendered_message, l.status, l.gateway_response, l.sent_at,
                p.name AS project_name
            FROM ced_broadcast_logs l
            LEFT JOIN projects p ON p.id = l.project_id
            WHERE l.campaign_id = {$campaignId}
            ORDER BY l.id ASC
        ");
        if ($qLogs) {
            while ($r = $qLogs->fetch_assoc()) {
                $logs[] = $r;
            }
        }

        echo json_encode([
            'success'  => true,
            'campaign' => $campaign,
            'logs'     => $logs,
        ]);
        exit;
    }

    /**
     * AJAX: Mark a skipped/failed recipient log as manually contacted (WhatsApp / Call / Manual SMS).
     */
    public function markManualContactedAjax()
    {
        $this->requireAuth(true);
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
            exit;
        }

        $logId  = (int)($_POST['log_id'] ?? 0);
        $method = trim((string)($_POST['method'] ?? 'WhatsApp'));
        $note   = trim((string)($_POST['note'] ?? ''));

        if ($logId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid log ID.']);
            exit;
        }

        $allowedMethods = ['WhatsApp', 'Manual SMS', 'Phone Call', 'Manual Email', 'Other'];
        if (!in_array($method, $allowedMethods, true)) {
            $method = 'WhatsApp';
        }

        $qLog = $this->db->query("SELECT id, campaign_id, status FROM ced_broadcast_logs WHERE id = {$logId} LIMIT 1");
        $logRow = $qLog ? $qLog->fetch_assoc() : null;
        if (!$logRow) {
            echo json_encode(['success' => false, 'message' => 'Log entry not found.']);
            exit;
        }

        $actorName = trim((string)($_SESSION['user']['full_name'] ?? ($_SESSION['user']['user_name'] ?? 'CED Head')));
        $respText  = "Manual ({$method}) by {$actorName}" . ($note !== '' ? " — {$note}" : '');
        $sentAt    = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare("
            UPDATE ced_broadcast_logs
            SET status = 'manual_sent', gateway_response = ?, sent_at = ?
            WHERE id = ?
        ");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Database error updating log status.']);
            exit;
        }
        $stmt->bind_param('ssi', $respText, $sentAt, $logId);
        $ok = $stmt->execute();
        $stmt->close();

        if (!$ok) {
            echo json_encode(['success' => false, 'message' => 'Failed to update status.']);
            exit;
        }

        $campaignId = (int)$logRow['campaign_id'];
        $stats = $this->syncCampaignStats($campaignId);

        echo json_encode([
            'success'          => true,
            'log_id'           => $logId,
            'campaign_id'      => $campaignId,
            'status'           => 'manual_sent',
            'gateway_response' => $respText,
            'sent_at'          => $sentAt,
            'stats'            => $stats,
        ]);
        exit;
    }

    /* ==========================================================================
     * INTERNAL DATA & HELPER METHODS
     * ========================================================================== */

    protected function activeBookingSql(string $alias = 'lb'): string
    {
        return "{$alias}.deleted_at IS NULL
            AND {$alias}.booking_status NOT IN ('refunded', 'cancelled')
            AND ({$alias}.landshare_status IS NULL OR {$alias}.landshare_status != 'cancelled')
            AND {$alias}.id NOT IN (
                SELECT crr.booking_id
                FROM client_refund_requests crr
                WHERE crr.booking_id IS NOT NULL
                  AND crr.status IN ('pending', 'approved', 'refunded')
            )";
    }

    protected function validPaymentSql(string $alias = 'lp'): string
    {
        return "{$alias}.is_trashed = 0
            AND COALESCE({$alias}.is_emi_discount, 0) = 0
            AND ({$alias}.verification_status IS NULL OR {$alias}.verification_status != 'rejected')";
    }

    protected function fetchAllActiveProjects(): array
    {
        $list = [];
        $res = $this->db->query("
            SELECT id, name
            FROM projects
            WHERE deleted_at IS NULL
              AND TRIM(COALESCE(name, '')) != ''
            ORDER BY name ASC
        ");
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $list[] = ['id' => (int)$r['id'], 'name' => $r['name']];
            }
        }
        return $list;
    }

    protected function fetchActiveCedAgents(): array
    {
        $list = [];
        $seen = [];
        $res = $this->db->query("
            SELECT DISTINCT
                u.id AS user_id,
                COALESCE(NULLIF(TRIM(e.full_name), ''), NULLIF(TRIM(u.full_name), ''), TRIM(u.user_name)) AS full_name
            FROM users u
            LEFT JOIN employees e ON (u.employee_id = e.id OR e.user_id = u.id)
            LEFT JOIN roles r ON r.id = u.role_id
            LEFT JOIN departments d ON d.id = e.department_id
            WHERE (
                e.department_id IN (4, 29)
                OR u.role_id IN (7, 37, 39)
                OR u.secondary_role_id IN (7, 37, 39)
                OR r.name LIKE '%client%'
                OR r.name LIKE '%recovery%'
                OR d.name LIKE '%client%'
                OR d.name LIKE '%ced%'
                OR u.id IN (SELECT DISTINCT user_id FROM ced_project_assignments)
            )
            AND TRIM(COALESCE(e.full_name, u.full_name, u.user_name, '')) != ''
            ORDER BY full_name ASC
        ");
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $uId = (int)$r['user_id'];
                if ($uId <= 0 || isset($seen[$uId])) {
                    continue;
                }
                $seen[$uId] = true;
                $list[] = ['user_id' => $uId, 'full_name' => $r['full_name']];
            }
        }
        return $list;
    }

    /**
     * Core filtered client query combining Land Share, Dev Charge, Regular/Advance EMI,
     * Upcoming EMI schedule, and latest Follow-up date.
     */
    protected function queryFilteredClients(array $filters): array
    {
        $activeBookingCond = $this->activeBookingSql('lb');
        $validPaymentCond  = $this->validPaymentSql('lp');
        $today             = $this->db->real_escape_string(date('Y-m-d'));

        $where = ["p.deleted_at IS NULL", $activeBookingCond];

        if (!empty($filters['booking_ids'])) {
            $inB = implode(',', array_map('intval', $filters['booking_ids']));
            $where[] = "lb.id IN ({$inB})";
        }

        if (!empty($filters['project_ids'])) {
            $inP = implode(',', array_map('intval', $filters['project_ids']));
            $where[] = "lb.project_id IN ({$inP})";
        }

        if (!empty($filters['agent_id'])) {
            $aId = (int)$filters['agent_id'];
            $where[] = "EXISTS (
                SELECT 1 FROM ced_project_assignments cpa
                WHERE cpa.project_id = lb.project_id
                  AND cpa.status = 'active'
                  AND cpa.user_id = {$aId}
            )";
        }

        if (!empty($filters['search'])) {
            $q = $this->db->real_escape_string($filters['search']);
            $where[] = "(
                COALESCE(l.lead_name, '') LIKE '%{$q}%'
                OR COALESCE(l.full_name, '') LIKE '%{$q}%'
                OR COALESCE(l.phone, '') LIKE '%{$q}%'
                OR COALESCE(l.phone_number, '') LIKE '%{$q}%'
                OR COALESCE(l.email, '') LIKE '%{$q}%'
                OR COALESCE(f.flat_name, '') LIKE '%{$q}%'
                OR COALESCE(p.name, '') LIKE '%{$q}%'
            )";
        }

        $whereSql = implode(' AND ', $where);

        $sql = "
            SELECT
                lb.id AS booking_id,
                lb.lead_id,
                lb.project_id,
                p.name AS project_name,
                COALESCE(NULLIF(TRIM(l.lead_name), ''), NULLIF(TRIM(l.full_name), ''), CONCAT('Client #', lb.lead_id)) AS client_name,
                COALESCE(NULLIF(TRIM(l.phone), ''), NULLIF(TRIM(l.phone_number), ''), '') AS phone,
                TRIM(COALESCE(l.`2nd_owner_phone`, '')) AS second_owner_phone,
                TRIM(COALESCE(l.email, '')) AS email,
                TRIM(COALESCE(l.alt_email, '')) AS alt_email,
                TRIM(COALESCE(l.`2nd_owner_email`, '')) AS second_owner_email,
                COALESCE(f.flat_name, CONCAT('Flat #', lb.flat_id)) AS flat_name,
                lb.booking_status,
                COALESCE(f.land_share_value, 0) AS total_land_share,
                COALESCE(lb.discount_amount, 0) AS discount_amount,
                COALESCE(f.development_charges, 0) AS total_dc_ask,
                COALESCE(pay.land_share_paid, 0) AS land_share_paid,
                COALESCE(pay.total_dc_received, 0) AS total_dc_received,
                COALESCE(pay.total_emi_paid, 0) AS total_emi_paid
            FROM lead_bookings lb
            INNER JOIN projects p ON p.id = lb.project_id
            LEFT JOIN leads l ON l.id = lb.lead_id
            LEFT JOIN flats f ON f.id = lb.flat_id
            LEFT JOIN (
                SELECT
                    lp.booking_id,
                    COALESCE(SUM(CASE WHEN lp.payment_type IN ('booking_money', 'registration', 'land_share') THEN lp.amount ELSE 0 END), 0) AS land_share_paid,
                    COALESCE(SUM(CASE WHEN lp.payment_type = 'development_charge' THEN lp.amount ELSE 0 END), 0) AS total_dc_received,
                    COALESCE(SUM(CASE WHEN lp.payment_type IN ('emi', 'emi_due_settlement') THEN lp.amount ELSE 0 END), 0) AS total_emi_paid
                FROM lead_payments lp
                WHERE {$validPaymentCond}
                  AND lp.payment_type IN ('booking_money', 'registration', 'land_share', 'development_charge', 'emi', 'emi_due_settlement')
                GROUP BY lp.booking_id
            ) pay ON pay.booking_id = lb.id
            WHERE {$whereSql}
            ORDER BY p.name ASC, f.flat_name ASC, lb.id ASC
        ";

        $bookings = [];
        $res = $this->db->query($sql);
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $bookings[(int)$r['booking_id']] = $r;
            }
        }

        if (empty($bookings)) {
            return [];
        }

        $inBookingIds = implode(',', array_keys($bookings));

        // 2. EMI asked strictly from Project-Level Active EMIs (is_active = 1; 0 if none active)
        $emiDemand = [];
        $qSched = $this->db->query("
            SELECT
                lb.id AS booking_id,
                COALESCE(NULLIF(f.price_value, 0), lb.agreement_value, 0) AS total_price,
                COALESCE(f.land_share_value, 0) AS land_share,
                COALESCE(f.development_charges, 0) AS dev_charge,
                COALESCE(
                    NULLIF(lb.total_emi_no, 0),
                    NULLIF((SELECT MAX(lp_e.total_emi_no) FROM lead_payments lp_e WHERE lp_e.booking_id = lb.id AND lp_e.is_trashed = 0), 0),
                    NULLIF(pes.total_emi_no, 0),
                    36
                ) AS total_emi_no,
                COALESCE(act.active_emi_count, 0) AS active_emi_count
            FROM lead_bookings lb
            LEFT JOIN flats f ON f.id = lb.flat_id
            LEFT JOIN project_emi_schedules pes ON pes.project_id = lb.project_id
            LEFT JOIN (
                SELECT project_id, COUNT(*) AS active_emi_count
                FROM project_emi_schedule_items
                WHERE is_active = 1
                GROUP BY project_id
            ) act ON act.project_id = lb.project_id
            WHERE lb.id IN ({$inBookingIds})
        ");
        if ($qSched) {
            while ($sr = $qSched->fetch_assoc()) {
                $totalEmiNo = max(1, (int)$sr['total_emi_no']);
                if ($totalEmiNo > 120) {
                    $totalEmiNo = 42;
                }
                $constructionFee = max(0.0, round((float)$sr['total_price'] - ((float)$sr['land_share'] + (float)$sr['dev_charge'])));
                $perEmiAmount    = ($totalEmiNo > 0) ? round($constructionFee / $totalEmiNo) : 0.0;
                $activeCount     = min($totalEmiNo, (int)$sr['active_emi_count']);

                $emiDemand[(int)$sr['booking_id']] = [
                    'total_sched_rows' => $activeCount,
                    'asked_amount'     => round($activeCount * $perEmiAmount, 2),
                ];
            }
        }

        // 3. Next upcoming unpaid EMI per booking (due_date >= today)
        $upcomingMap = [];
        $qUp = $this->db->query("
            SELECT
                es.lead_booking_id AS booking_id,
                es.emi_no,
                es.due_date,
                es.emi_amount
            FROM emi_schedules es
            WHERE es.lead_booking_id IN ({$inBookingIds})
              AND es.status NOT IN ('paid', 'cancelled')
              AND es.due_date >= '{$today}'
            ORDER BY es.lead_booking_id ASC, es.due_date ASC, es.emi_no ASC
        ");
        if ($qUp) {
            while ($ur = $qUp->fetch_assoc()) {
                $bId = (int)$ur['booking_id'];
                if (!isset($upcomingMap[$bId])) {
                    $upcomingMap[$bId] = [
                        'next_emi_no'     => (int)$ur['emi_no'],
                        'next_emi_date'   => $ur['due_date'],
                        'next_emi_amount' => round((float)$ur['emi_amount'], 2),
                    ];
                }
            }
        }

        // 4. Latest follow-up date per booking
        $followupMap = [];
        $qFu = $this->db->query("
            SELECT booking_id, MAX(next_follow_up_date) AS next_follow_up_date
            FROM emi_followups
            WHERE booking_id IN ({$inBookingIds})
              AND next_follow_up_date IS NOT NULL
            GROUP BY booking_id
        ");
        if ($qFu) {
            while ($fr = $qFu->fetch_assoc()) {
                $followupMap[(int)$fr['booking_id']] = $fr['next_follow_up_date'];
            }
        }

        require_once dirname(__DIR__) . '/services/SmsService.php';
        $smsService = new SmsService($this->db);

        // Compute upcoming EMI window cutoff if filter requested
        $upcomingCutoff = null;
        $upWindow = $filters['upcoming_emi_days'] ?? '';
        if ($upWindow === '7') {
            $upcomingCutoff = date('Y-m-d', strtotime('+7 days'));
        } elseif ($upWindow === '15') {
            $upcomingCutoff = date('Y-m-d', strtotime('+15 days'));
        } elseif ($upWindow === '30') {
            $upcomingCutoff = date('Y-m-d', strtotime('+30 days'));
        } elseif ($upWindow === 'this_month') {
            $upcomingCutoff = date('Y-m-t');
        }

        $todayStr     = date('Y-m-d');
        $plus5DaysStr = date('Y-m-d', strtotime('+5 days'));

        $results = [];
        foreach ($bookings as $bId => $r) {
            $totalLs  = round((float)$r['total_land_share'], 2);
            $discount = round((float)$r['discount_amount'], 2);
            $lsPaid   = round((float)$r['land_share_paid'], 2);
            $lsDue    = round(max(0.0, $totalLs - $discount - $lsPaid), 2);
            $isReg    = ($totalLs > 0 && round($totalLs - $discount - $lsPaid, 2) <= 0);

            $dcAsk    = round((float)$r['total_dc_ask'], 2);
            $dcRcvd   = round((float)$r['total_dc_received'], 2);
            $dcDue    = round(max(0.0, $dcAsk - $dcRcvd), 2);

            $emiPaid  = round((float)$r['total_emi_paid'], 2);
            $ed       = $emiDemand[$bId] ?? ['total_sched_rows' => 0, 'asked_amount' => 0.0];
            $netReg   = round(min($emiPaid, $ed['asked_amount']), 2);
            $advPaid  = round(max(0.0, $emiPaid - $netReg), 2);
            $emiDue   = round(max(0.0, $ed['asked_amount'] - $netReg), 2);

            $totalDue = round($lsDue + $dcDue + $emiDue, 2);

            $upInfo       = $upcomingMap[$bId] ?? null;
            $nextEmiNo    = $upInfo ? $upInfo['next_emi_no'] : null;
            $nextEmiDate  = $upInfo ? $upInfo['next_emi_date'] : null;
            $nextEmiAmt   = $upInfo ? $upInfo['next_emi_amount'] : 0.0;
            $advCoversEmi = ($nextEmiAmt > 0 && $advPaid >= $nextEmiAmt);

            $nextFollowup = $followupMap[$bId] ?? null;

            $phoneVal   = $smsService->validatePhoneNumber($r['phone']);
            $phoneValid = !empty($phoneVal['valid']);
            $emailValid = ($r['email'] !== '' && filter_var($r['email'], FILTER_VALIDATE_EMAIL) !== false);

            // Apply post-aggregation filters (skipped when querying exact booking_ids for campaign creation)
            if (empty($filters['booking_ids'])) {
                $dueFilter = $filters['due_filter'] ?? '';
                if ($dueFilter === 'any_due' && $totalDue <= 0) continue;
                if ($dueFilter === 'land_share_due' && $lsDue <= 0) continue;
                if ($dueFilter === 'dc_due' && $dcDue <= 0) continue;
                if ($dueFilter === 'emi_due' && $emiDue <= 0) continue;
                if ($dueFilter === 'no_due' && $totalDue > 0) continue;

                if (isset($filters['min_due']) && $filters['min_due'] !== null && $totalDue < $filters['min_due']) {
                    continue;
                }

                if ($upcomingCutoff !== null) {
                    if (!$nextEmiDate || $nextEmiDate < $todayStr || $nextEmiDate > $upcomingCutoff) {
                        continue;
                    }
                    if (!empty($filters['exclude_advance_covered']) && $advCoversEmi) {
                        continue;
                    }
                }

                $fuFilter = $filters['followup_filter'] ?? '';
                if ($fuFilter === 'today' && $nextFollowup !== $todayStr) continue;
                if ($fuFilter === 'upcoming_5d' && (!$nextFollowup || $nextFollowup <= $todayStr || $nextFollowup > $plus5DaysStr)) continue;
                if ($fuFilter === 'overdue' && (!$nextFollowup || $nextFollowup >= $todayStr)) continue;

                $bkFilter = $filters['booking_status'] ?? '';
                if ($bkFilter === 'registered' && !$isReg) continue;
                if ($bkFilter === 'booked' && $isReg) continue;

                $ctFilter = $filters['contact_filter'] ?? '';
                if ($ctFilter === 'has_phone' && !$phoneValid) continue;
                if ($ctFilter === 'has_email' && !$emailValid) continue;
                if ($ctFilter === 'has_both' && (!$phoneValid || !$emailValid)) continue;
            }

            $results[] = [
                'booking_id'         => $bId,
                'lead_id'            => (int)$r['lead_id'],
                'project_id'         => (int)$r['project_id'],
                'project_name'       => $r['project_name'],
                'client_name'        => $r['client_name'],
                'flat_name'          => $r['flat_name'],
                'phone'              => $r['phone'],
                'normalized_phone'   => $phoneValid ? $phoneVal['normalized'] : '',
                'phone_valid'        => $phoneValid,
                'second_owner_phone' => $r['second_owner_phone'],
                'email'              => $r['email'],
                'email_valid'        => $emailValid,
                'alt_email'          => $r['alt_email'],
                'second_owner_email' => $r['second_owner_email'],
                'is_registered'      => $isReg,
                'booking_status'     => $r['booking_status'],
                'land_share_due'     => $lsDue,
                'dc_due'             => $dcDue,
                'regular_emi_due'    => $emiDue,
                'advance_paid'       => $advPaid,
                'total_due'          => $totalDue,
                'next_emi_no'        => $nextEmiNo,
                'next_emi_date'      => $nextEmiDate,
                'next_emi_amount'    => $nextEmiAmt,
                'advance_covers_emi' => $advCoversEmi,
                'next_followup_date' => $nextFollowup,
            ];
        }

        return $results;
    }

    protected function containsAnyPlaceholder(string $text, array $placeholders): bool
    {
        foreach ($placeholders as $tag) {
            if (stripos($text, $tag) !== false) {
                return true;
            }
        }
        return false;
    }

    protected function renderPlaceholders(string $template, array $client): string
    {
        $nextDateFmt = !empty($client['next_emi_date']) ? date('d M Y', strtotime($client['next_emi_date'])) : 'N/A';

        $replacements = [
            '{client_name}'     => $client['client_name'] ?? 'Valued Client',
            '{project_name}'    => $client['project_name'] ?? '',
            '{flat_name}'       => $client['flat_name'] ?? '',
            '{total_due}'       => number_format((float)($client['total_due'] ?? 0), 0),
            '{land_share_due}'  => number_format((float)($client['land_share_due'] ?? 0), 0),
            '{dc_due}'          => number_format((float)($client['dc_due'] ?? 0), 0),
            '{emi_due}'         => number_format((float)($client['regular_emi_due'] ?? 0), 0),
            '{next_emi_date}'   => $nextDateFmt,
            '{next_emi_amount}' => number_format((float)($client['next_emi_amount'] ?? 0), 0),
        ];

        return str_ireplace(array_keys($replacements), array_values($replacements), $template);
    }

    protected function createBatchMailer(array $config): ?PHPMailer
    {
        try {
            $smtpHost    = $config['smtp_host'] ?? 'mail.dpremiumhomes.com';
            $smtpPort    = (int)($config['smtp_port'] ?? 465);
            $senderEmail = $config['smtp_user'] ?? 'no-reply@dpremiumhomes.com';
            $smtpPass    = $config['smtp_pass'] ?? '';
            $smtpSecure  = $config['smtp_secure'] ?? 'ssl';

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->CharSet       = 'UTF-8';
            $mail->SMTPDebug     = 0;
            $mail->Timeout       = 10;
            $mail->SMTPKeepAlive = true;
            $mail->Host          = $smtpHost;
            $mail->SMTPAuth      = true;
            $mail->Username      = $senderEmail;
            $mail->Password      = $smtpPass;
            $mail->SMTPSecure    = (strtolower($smtpSecure) === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port          = $smtpPort;
            $mail->setFrom($senderEmail, 'The Premium Homes Ltd');
            return $mail;
        } catch (\Throwable $e) {
            error_log('[CedClientBroadcast] Mailer init error: ' . $e->getMessage());
            return null;
        }
    }

    protected function wrapEmailHtml(string $subject, string $bodyContent): string
    {
        $hasHtmlTags = ($bodyContent !== strip_tags($bodyContent));
        $formattedBody = $hasHtmlTags ? $bodyContent : nl2br(htmlspecialchars($bodyContent, ENT_QUOTES, 'UTF-8'));
        $safeSubject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
        $year = date('Y');

        return "
        <div style='font-family: \"Segoe UI\", Arial, sans-serif; background:#f4f6f8; padding:24px 12px;'>
            <div style='max-width:620px; margin:0 auto; background:#ffffff; border-radius:10px; overflow:hidden; border:1px solid #e2e8f0; box-shadow:0 3px 12px rgba(15,23,42,0.06);'>
                <div style='background:linear-gradient(135deg, #0d5c3a, #15803d); color:#ffffff; padding:20px 26px;'>
                    <div style='font-size:11px; letter-spacing:1.2px; text-transform:uppercase; opacity:0.85; margin-bottom:4px;'>The Premium Homes Ltd &middot; Client Experience</div>
                    <h2 style='margin:0; font-size:18px; font-weight:700;'>{$safeSubject}</h2>
                </div>
                <div style='padding:26px; color:#334155; font-size:14.5px; line-height:1.7;'>
                    {$formattedBody}
                    <div style='margin-top:28px; padding-top:16px; border-top:1px solid #e2e8f0; font-size:13px; color:#475569;'>
                        Best Regards,<br>
                        <strong>Client Experience Department (CED)</strong><br>
                        The Premium Homes Ltd
                    </div>
                </div>
                <div style='background:#f8fafc; padding:14px 24px; text-align:center; font-size:11.5px; color:#94a3b8; border-top:1px solid #f1f5f9;'>
                    &copy; {$year} The Premium Homes Ltd. All rights reserved.
                </div>
            </div>
        </div>";
    }

    protected function syncCampaignStats(int $campaignId): array
    {
        $stats = [
            'remaining'    => 0,
            'total_jobs'   => 0,
            'sms_sent'     => 0,
            'sms_failed'   => 0,
            'email_sent'   => 0,
            'email_failed' => 0,
            'status'       => 'processing',
        ];

        $q = $this->db->query("
            SELECT
                SUM(CASE WHEN status != 'skipped_invalid' THEN 1 ELSE 0 END) AS total_jobs,
                SUM(CASE WHEN status IN ('pending', 'processing') THEN 1 ELSE 0 END) AS remaining,
                SUM(CASE WHEN channel = 'sms'   AND status IN ('sent', 'skipped_testing', 'manual_sent') THEN 1 ELSE 0 END) AS sms_sent,
                SUM(CASE WHEN channel = 'sms'   AND status = 'failed' THEN 1 ELSE 0 END) AS sms_failed,
                SUM(CASE WHEN channel = 'email' AND status IN ('sent', 'skipped_testing', 'manual_sent') THEN 1 ELSE 0 END) AS email_sent,
                SUM(CASE WHEN channel = 'email' AND status = 'failed' THEN 1 ELSE 0 END) AS email_failed
            FROM ced_broadcast_logs
            WHERE campaign_id = {$campaignId}
        ");
        if ($q && ($row = $q->fetch_assoc())) {
            $stats['total_jobs']   = (int)$row['total_jobs'];
            $stats['remaining']    = (int)$row['remaining'];
            $stats['sms_sent']     = (int)$row['sms_sent'];
            $stats['sms_failed']   = (int)$row['sms_failed'];
            $stats['email_sent']   = (int)$row['email_sent'];
            $stats['email_failed'] = (int)$row['email_failed'];
        }

        $totalSent   = $stats['sms_sent'] + $stats['email_sent'];
        $totalFailed = $stats['sms_failed'] + $stats['email_failed'];

        if ($stats['remaining'] === 0) {
            if ($totalFailed === 0) {
                $stats['status'] = 'completed';
            } elseif ($totalSent > 0) {
                $stats['status'] = 'partial_failed';
            } else {
                $stats['status'] = 'failed';
            }
            $completedSql = ", completed_at = NOW()";
        } else {
            $stats['status'] = 'processing';
            $completedSql = "";
        }

        $stEsc = $this->db->real_escape_string($stats['status']);
        $this->db->query("
            UPDATE ced_broadcast_campaigns
            SET total_jobs = {$stats['total_jobs']},
                sms_sent_count = {$stats['sms_sent']},
                sms_failed_count = {$stats['sms_failed']},
                email_sent_count = {$stats['email_sent']},
                email_failed_count = {$stats['email_failed']},
                status = '{$stEsc}'
                {$completedSql}
            WHERE id = {$campaignId}
        ");

        return $stats;
    }
}
