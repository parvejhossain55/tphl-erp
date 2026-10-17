<?php

require_once __DIR__ . '/../services/LeadAutomation/LeadSynchronizer.php';

class LeadAutomationController extends Controller
{
    protected $db;
    protected $leadModel;
    protected $teamsModel;
    protected array $automationConfig;

    public function __construct($db)
    {
        $this->db = $db;
        $this->leadModel = new Lead($this->db);
        $this->teamsModel = new Team($this->db);
        $this->automationConfig = require __DIR__ . '/../config/lead_automation.php';
    }

    /**
     * Check authorization for Role 10, 11, Admin, or users with Digital Marketing permissions
     */
    private function checkAccess(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userRole = (int)($_SESSION['user']['role_id'] ?? 0);
        $secondaryRole = (int)($_SESSION['user']['secondary_role_id'] ?? 0);
        $allowedRoles = [1, 10, 11, 33, 34, 35]; // Admin, DM Head, DM Exec, Management

        $hasPerm = function_exists('hasPermission') && (hasPermission("Digital Marketing Panel") || hasPermission("Upload CSV"));

        if (!in_array($userRole, $allowedRoles, true) && !in_array($secondaryRole, $allowedRoles, true) && !$hasPerm) {
            if (function_exists('setError')) {
                setError("You do not have permission to access Lead Automation.");
            }
            header("Location: {$GLOBALS['base_url']}/my-dashboard");
            exit;
        }
    }

    /**
     * Main Dashboard Page
     */
    public function index()
    {
        $this->checkAccess();

        $teams = $this->teamsModel->getAll();
        $sheetUrl = $this->automationConfig['google_sheet']['url'] ?? '';

        return $this->view("digital-marketing-team/lead-automation", [
            "current_route" => "lead-automation-upload",
            "page_title"    => "Lead Automation Upload & Distribution",
            "teams"         => $teams,
            "sheet_url"     => $sheetUrl,
            "gemini_active" => !empty($this->automationConfig['gemini']['enabled']) && !empty($this->automationConfig['gemini']['api_key']),
        ]);
    }

    /**
     * AJAX: Trigger Scrape from Google Sheet
     */
    public function ajaxScrape()
    {
        $this->checkAccess();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $sheetUrl = trim($_POST['sheet_url'] ?? '');
            if (empty($sheetUrl)) {
                $sheetUrl = null;
            }

            $synchronizer = new LeadSynchronizer($this->automationConfig);
            $result = $synchronizer->runSync($sheetUrl);

            echo json_encode([
                'status'  => 'success',
                'message' => sprintf(
                    "Sync finished! %d new leads added (Pending distribution), %d duplicates skipped.",
                    $result['inserted'] ?? 0,
                    $result['skipped_duplicate'] ?? 0
                ),
                'data'    => $result
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * AJAX: Fetch Leads List with Filter & Stats
     */
    public function ajaxListLeads()
    {
        $this->checkAccess();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $synchronizer = new LeadSynchronizer($this->automationConfig);
            $pdo = $synchronizer->getDb();

            $teamId = isset($_GET['team_id']) && $_GET['team_id'] !== '' ? (int)$_GET['team_id'] : null;
            $distribute = isset($_GET['distribute']) && $_GET['distribute'] !== '' ? (int)$_GET['distribute'] : null;
            $search = trim($_GET['search'] ?? '');
            $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 500) : 100;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

            // Only fetch automated leads synced via Google Sheets / Meta Automation
            $where = ["l.deleted_at IS NULL", "l.is_automated = 1"];
            $params = [];

            if ($teamId !== null) {
                $where[] = "l.team_id = ?";
                $params[] = $teamId;
            }

            if ($distribute !== null) {
                $where[] = "l.distribute = ?";
                $params[] = $distribute;
            }

            if (!empty($search)) {
                $where[] = "(l.full_name LIKE ? OR l.phone_number LIKE ? OR l.campaign_name LIKE ? OR l.id = ?)";
                $searchTerm = "%{$search}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = is_numeric($search) ? (int)$search : 0;
            }

            $whereSql = implode(" AND ", $where);

            // Fetch leads with team names and project names
            $sql = "SELECT 
                        l.id, 
                        l.full_name, 
                        l.phone_number, 
                        l.email, 
                        l.job_title, 
                        l.company_name, 
                        l.city, 
                        l.country,
                        l.campaign_name, 
                        l.which_apartment_size_are_you_interested_in,
                        l.which_floor_are_you_for_shop,
                        l.when_are_you_planning_to_buy,
                        l.would_you_like_to_schedule_a_site_visit,
                        l.when_is_the_best_time_to_contact_you,
                        l.project_id, 
                        l.team_id, 
                        l.distribute, 
                        l.created_at,
                        t.name as team_name,
                        p.name as project_name
                    FROM `leads` l
                    LEFT JOIN `teams` t ON l.team_id = t.id
                    LEFT JOIN `projects` p ON l.project_id = p.id
                    WHERE {$whereSql}
                    ORDER BY l.id DESC
                    LIMIT {$limit} OFFSET {$offset}";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Total count for current filter
            $countSql = "SELECT COUNT(*) FROM `leads` l WHERE {$whereSql}";
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute($params);
            $totalCount = (int)$countStmt->fetchColumn();

            // Overall Stats (Scoped to automated sync leads only)
            $statsSql = "SELECT 
                            COUNT(*) as total_leads,
                            SUM(CASE WHEN distribute = 0 THEN 1 ELSE 0 END) as pending_leads,
                            SUM(CASE WHEN distribute = 1 THEN 1 ELSE 0 END) as approved_leads
                         FROM `leads` WHERE deleted_at IS NULL AND is_automated = 1";
            $stats = $pdo->query($statsSql)->fetch(PDO::FETCH_ASSOC);

            // Fetch Teams with count of automated leads
            $teamsSql = "SELECT 
                            t.id, 
                            t.name, 
                            COUNT(l.id) as total_leads,
                            SUM(CASE WHEN l.distribute = 0 THEN 1 ELSE 0 END) as pending_leads
                         FROM `teams` t
                         LEFT JOIN `leads` l ON l.team_id = t.id AND l.deleted_at IS NULL AND l.is_automated = 1
                         GROUP BY t.id, t.name
                         ORDER BY t.id ASC";
            $teams = $pdo->query($teamsSql)->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'data'   => [
                    'leads'       => $leads,
                    'total_count' => $totalCount,
                    'stats'       => $stats,
                    'teams'       => $teams,
                ]
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * AJAX: Approve Leads (set distribute = 1 so team leaders & agents receive them)
     */
    public function ajaxApprove()
    {
        $this->checkAccess();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $leadIds = $input['lead_ids'] ?? ($_POST['lead_ids'] ?? []);
            $teamId = isset($input['team_id']) && $input['team_id'] !== '' ? (int)$input['team_id'] : (!empty($_POST['team_id']) ? (int)$_POST['team_id'] : null);
            $approveAll = !empty($input['all_pending']) || !empty($_POST['all_pending']);

            $synchronizer = new LeadSynchronizer($this->automationConfig);
            $pdo = $synchronizer->getDb();

            // Direct All-Pending or Team-Wise All-Pending
            if ($approveAll) {
                if ($teamId) {
                    $updateSql = "UPDATE `leads` SET `distribute` = 1, `updated_at` = NOW() WHERE `distribute` = 0 AND `deleted_at` IS NULL AND `is_automated` = 1 AND `team_id` = ?";
                    $stmt = $pdo->prepare($updateSql);
                    $stmt->execute([$teamId]);
                } else {
                    $updateSql = "UPDATE `leads` SET `distribute` = 1, `updated_at` = NOW() WHERE `distribute` = 0 AND `deleted_at` IS NULL AND `is_automated` = 1";
                    $stmt = $pdo->prepare($updateSql);
                    $stmt->execute();
                }
                $affected = $stmt->rowCount();
                echo json_encode([
                    'status'  => 'success',
                    'message' => "Successfully approved {$affected} pending lead(s) for team distribution!",
                    'count'   => $affected
                ]);
                exit;
            }

            if (empty($leadIds)) {
                throw new InvalidArgumentException("No lead IDs provided for approval.");
            }

            if (!is_array($leadIds)) {
                $leadIds = [(int)$leadIds];
            }

            $leadIds = array_map('intval', $leadIds);
            $placeholders = implode(',', array_fill(0, count($leadIds), '?'));

            $updateSql = "UPDATE `leads` SET `distribute` = 1, `updated_at` = NOW() WHERE `id` IN ({$placeholders})";
            $stmt = $pdo->prepare($updateSql);
            $stmt->execute($leadIds);
            $affected = $stmt->rowCount();
            $totalSelected = count($leadIds);

            echo json_encode([
                'status'  => 'success',
                'message' => $affected > 0 
                    ? "Successfully approved and distributed {$affected} lead(s) to sales teams!" 
                    : "Selected {$totalSelected} lead(s) are already approved and visible to sales teams.",
                'count'   => $affected ?: $totalSelected
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * AJAX: Unapprove Leads (revert distribute = 0)
     */
    public function ajaxUnapprove()
    {
        $this->checkAccess();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $leadIds = $input['lead_ids'] ?? ($_POST['lead_ids'] ?? []);

            if (empty($leadIds)) {
                throw new InvalidArgumentException("No lead IDs provided.");
            }

            if (!is_array($leadIds)) {
                $leadIds = [(int)$leadIds];
            }

            $leadIds = array_map('intval', $leadIds);
            $placeholders = implode(',', array_fill(0, count($leadIds), '?'));

            $synchronizer = new LeadSynchronizer($this->automationConfig);
            $pdo = $synchronizer->getDb();

            $updateSql = "UPDATE `leads` SET `distribute` = 0, `updated_at` = NOW() WHERE `id` IN ({$placeholders})";
            $stmt = $pdo->prepare($updateSql);
            $stmt->execute($leadIds);

            echo json_encode([
                'status'  => 'success',
                'message' => "Reverted " . count($leadIds) . " lead(s) back to Pending status.",
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Webhook / Cron Sync Endpoint
     */
    public function syncCron()
    {
        header('Content-Type: application/json; charset=utf-8');

        $token = $_GET['token'] ?? '';
        $expectedToken = $this->automationConfig['secret_token'] ?? 'ERP_LEAD_SYNC_SECURE_TOKEN_2026';

        if (empty($token) || !hash_equals($expectedToken, $token)) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized token.']);
            exit;
        }

        try {
            $sheetUrl = $_GET['sheet_url'] ?? null;
            $synchronizer = new LeadSynchronizer($this->automationConfig);
            $result = $synchronizer->runSync($sheetUrl);

            echo json_encode([
                'status' => 'success',
                'data'   => $result
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
}
