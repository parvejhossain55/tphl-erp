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
        $this->ensureSchema();
    }

    /**
     * Self-healing schema auto-migration for any environment (Local, Staging, Live)
     */
    public function ensureSchema(): void
    {
        try {
            $synchronizer = new LeadSynchronizer($this->automationConfig);
            $pdo = $synchronizer->getDb();

            // 1. Check & add distribute column if missing
            $cols = $pdo->query("SHOW COLUMNS FROM `leads` LIKE 'distribute'")->fetchAll();
            if (empty($cols)) {
                $pdo->exec("ALTER TABLE `leads` ADD COLUMN `distribute` TINYINT(1) NOT NULL DEFAULT 1");
                try {
                    $pdo->exec("CREATE INDEX `idx_leads_distribute` ON `leads` (`distribute`)");
                } catch (Throwable $t) {}
            } else {
                // Ensure default is 1 for manual and standard CRM uploads
                $pdo->exec("ALTER TABLE `leads` MODIFY COLUMN `distribute` TINYINT(1) NOT NULL DEFAULT 1");
            }

            // 2. Check & add is_automated column if missing
            $autoCols = $pdo->query("SHOW COLUMNS FROM `leads` LIKE 'is_automated'")->fetchAll();
            if (empty($autoCols)) {
                $pdo->exec("ALTER TABLE `leads` ADD COLUMN `is_automated` TINYINT(1) NOT NULL DEFAULT 0");
                try {
                    $pdo->exec("CREATE INDEX `idx_leads_is_automated` ON `leads` (`is_automated`)");
                } catch (Throwable $t) {}
            }

            // 3. Check & add approved_by and approved_at columns if missing
            $apprCols = $pdo->query("SHOW COLUMNS FROM `leads` LIKE 'approved_by'")->fetchAll();
            if (empty($apprCols)) {
                $pdo->exec("ALTER TABLE `leads` ADD COLUMN `approved_by` INT NULL");
                try {
                    $pdo->exec("CREATE INDEX `idx_leads_approved_by` ON `leads` (`approved_by`)");
                } catch (Throwable $t) {}
            }
            $apprAtCols = $pdo->query("SHOW COLUMNS FROM `leads` LIKE 'approved_at'")->fetchAll();
            if (empty($apprAtCols)) {
                $pdo->exec("ALTER TABLE `leads` ADD COLUMN `approved_at` DATETIME NULL");
            }
        } catch (Throwable $e) {
            error_log("LeadAutomation ensureSchema error: " . $e->getMessage());
        }
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

        $projects = [];
        try {
            $pStmt = $this->db->query("SELECT id, name FROM `projects` WHERE deleted_at IS NULL ORDER BY name ASC");
            $projects = $pStmt ? $pStmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (Throwable $t) {}

        $platformReport = $this->getAutomatedPlatformReport(['date_preset' => 'today']);

        return $this->view("digital-marketing-team/lead-automation", [
            "current_route"  => "lead-automation-upload",
            "page_title"     => "Lead Automation Upload & Distribution",
            "teams"          => $teams,
            "projects"       => $projects,
            "sheet_url"      => $sheetUrl,
            "gemini_active"  => !empty($this->automationConfig['gemini']['enabled']) && !empty($this->automationConfig['gemini']['api_key']),
            "platformReport" => $platformReport,
        ]);
    }

    /**
     * AJAX: Trigger Scrape from Google Sheet
     */
    public function ajaxScrape()
    {
        $this->checkAccess();
        header('Content-Type: application/json; charset=utf-8');

        @set_time_limit(300);
        @ini_set('max_execution_time', '300');

        try {
            $sheetUrl = trim($_POST['sheet_url'] ?? '');
            if (empty($sheetUrl)) {
                $sheetUrl = null;
            }

            $synchronizer = new LeadSynchronizer($this->automationConfig);
            $result = $synchronizer->runSync($sheetUrl);

            $tabCount = count($result['tabs_synced'] ?? []);
            $tabSuffix = $tabCount > 1 ? sprintf(" across %d sheet tabs", $tabCount) : "";

            echo json_encode([
                'status'  => 'success',
                'message' => sprintf(
                    "Sync finished%s! %d new leads added (Pending distribution), %d duplicates skipped.",
                    $tabSuffix,
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
        header('Cache-Control: no-cache, no-store, must-revalidate');

        try {
            $synchronizer = new LeadSynchronizer($this->automationConfig);
            $pdo = $synchronizer->getDb();

            $teamParam = trim((string)($_GET['team_id'] ?? ''));
            $distribute = isset($_GET['distribute']) && $_GET['distribute'] !== '' ? (int)$_GET['distribute'] : null;
            $search = trim($_GET['search'] ?? '');
            
            // Date Filter: all (default), today, yesterday, this_week / weekly, custom
            $dateFilter = strtolower(trim((string)($_GET['date_filter'] ?? 'all')));
            $startDate = trim((string)($_GET['start_date'] ?? ''));
            $endDate = trim((string)($_GET['end_date'] ?? ''));

            // Limit: support 'all' / -1 or numeric (default 30)
            $limitParam = strtolower(trim((string)($_GET['limit'] ?? '30')));
            if ($limitParam === 'all' || $limitParam === '-1' || (int)$limitParam >= 5000) {
                $limit = 100000;
                $offset = 0;
            } else {
                $limit = max(1, min((int)$limitParam, 5000));
                $offset = max(0, (int)($_GET['offset'] ?? 0));
            }

            // Check if is_automated column exists on this database (self-healing)
            $hasAutomatedCol = false;
            try {
                $checkCols = $pdo->query("SHOW COLUMNS FROM `leads` LIKE 'is_automated'")->fetchAll();
                if (empty($checkCols)) {
                    $this->ensureSchema();
                    $checkCols = $pdo->query("SHOW COLUMNS FROM `leads` LIKE 'is_automated'")->fetchAll();
                }
                $hasAutomatedCol = !empty($checkCols);
            } catch (Throwable $t) {
                $hasAutomatedCol = false;
            }

            // Build Date Filter Condition
            $dateConditionSql = "";
            $dateParams = [];

            if ($dateFilter === 'today') {
                $dateConditionSql = "DATE(l.created_at) = CURDATE()";
            } elseif ($dateFilter === 'yesterday') {
                $dateConditionSql = "DATE(l.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            } elseif ($dateFilter === 'this_week' || $dateFilter === 'weekly') {
                $dateConditionSql = "DATE(l.created_at) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND DATE(l.created_at) <= CURDATE()";
            } elseif ($dateFilter === 'custom') {
                if (!empty($startDate) && !empty($endDate)) {
                    $dateConditionSql = "DATE(l.created_at) >= ? AND DATE(l.created_at) <= ?";
                    $dateParams = [$startDate, $endDate];
                } elseif (!empty($startDate)) {
                    $dateConditionSql = "DATE(l.created_at) >= ?";
                    $dateParams = [$startDate];
                } elseif (!empty($endDate)) {
                    $dateConditionSql = "DATE(l.created_at) <= ?";
                    $dateParams = [$endDate];
                }
            }

            // Only fetch automated leads synced via Google Sheets / Meta Automation
            $where = ["l.deleted_at IS NULL"];
            if ($hasAutomatedCol) {
                $where[] = "l.is_automated = 1";
            }
            $params = [];

            // Apply Date Filter to query
            if (!empty($dateConditionSql)) {
                $where[] = $dateConditionSql;
                $params = array_merge($params, $dateParams);
            }

            // Team filter: specific team ID, 'unassigned' (no team), or 'assigned' (any team)
            if ($teamParam === 'unassigned') {
                $where[] = "(l.team_id IS NULL OR l.team_id = 0)";
            } elseif ($teamParam === 'assigned') {
                $where[] = "(l.team_id IS NOT NULL AND l.team_id > 0)";
            } elseif (is_numeric($teamParam) && (int)$teamParam > 0) {
                $where[] = "l.team_id = ?";
                $params[] = (int)$teamParam;
            }

            if ($distribute !== null) {
                $where[] = "l.distribute = ?";
                $params[] = $distribute;
            }

            // Project filter
            $projectParam = trim((string)($_GET['project_id'] ?? ''));
            if ($projectParam === 'common' || $projectParam === '0') {
                $where[] = "(l.project_id IS NULL OR l.project_id = 0)";
            } elseif (is_numeric($projectParam) && (int)$projectParam > 0) {
                $where[] = "l.project_id = ?";
                $params[] = (int)$projectParam;
            }

            // Platform filter
            $platformParam = strtolower(trim((string)($_GET['platform'] ?? '')));
            if ($platformParam === 'facebook' || $platformParam === 'facebook_form') {
                $where[] = "(LOWER(TRIM(COALESCE(l.platform, ''))) IN ('facebook', 'fb', 'fb(lead form)', 'lead form', 'leadform', 'leadfrom', 'meta form', 'meta inform') OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%fb%' OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%facebook%' OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%lead form%' OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%leadform%')";
            } elseif ($platformParam === 'whatsapp') {
                $where[] = "(LOWER(TRIM(COALESCE(l.platform, ''))) IN ('whatsapp', 'wa', 'whats app', 'whatsappp', 'whataapp', 'whatsapp lead', 'whstsapp') OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%whatsapp%' OR LOWER(TRIM(COALESCE(l.platform, ''))) = 'wa')";
            } elseif ($platformParam === 'meta_inbox') {
                $where[] = "(LOWER(TRIM(COALESCE(l.platform, ''))) IN ('meta inbox', 'meta') OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%inbox%')";
            } elseif ($platformParam === 'google_form') {
                $where[] = "(LOWER(TRIM(COALESCE(l.platform, ''))) IN ('google form', 'google', 'googleform') OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%google%')";
            } elseif ($platformParam === 'brochure' || $platformParam === 'brochure_leads') {
                $where[] = "(LOWER(TRIM(COALESCE(l.platform, ''))) IN ('brochure leads', 'brocure leads', 'brochure lead', 'brocure lead', 'brochure') OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%brochure%')";
            } elseif ($platformParam === 'others') {
                $where[] = "(NOT (
                    LOWER(TRIM(COALESCE(l.platform, ''))) IN ('facebook', 'fb', 'fb(lead form)', 'lead form', 'leadform', 'leadfrom', 'meta form', 'meta inform')
                    OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%fb%'
                    OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%facebook%'
                    OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%lead form%'
                    OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%leadform%'
                    OR LOWER(TRIM(COALESCE(l.platform, ''))) IN ('whatsapp', 'wa', 'whats app', 'whatsappp', 'whataapp', 'whatsapp lead', 'whstsapp')
                    OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%whatsapp%'
                    OR LOWER(TRIM(COALESCE(l.platform, ''))) = 'wa'
                    OR LOWER(TRIM(COALESCE(l.platform, ''))) IN ('meta inbox', 'meta')
                    OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%inbox%'
                    OR LOWER(TRIM(COALESCE(l.platform, ''))) IN ('google form', 'google', 'googleform')
                    OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%google%'
                    OR LOWER(TRIM(COALESCE(l.platform, ''))) IN ('brochure leads', 'brocure leads', 'brochure lead', 'brocure lead', 'brochure')
                    OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%brochure%'
                ))";
            }

            // Search by Name, Phone, Campaign, Team Name, Project Name, ID, or "unassigned" keyword
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $searchTerm = "%{$search}%";

                $searchParts = [
                    "l.full_name LIKE ?",
                    "l.phone_number LIKE ?",
                    "l.campaign_name LIKE ?",
                    "t.name LIKE ?",
                    "p.name LIKE ?",
                    "l.id = ?"
                ];
                $searchParams = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, is_numeric($search) ? (int)$search : 0];

                if (strpos($searchLower, 'unassign') !== false) {
                    $searchParts[] = "(l.team_id IS NULL OR l.team_id = 0)";
                } elseif (strpos($searchLower, 'assign') !== false) {
                    $searchParts[] = "(l.team_id IS NOT NULL AND l.team_id > 0)";
                }

                $where[] = "(" . implode(" OR ", $searchParts) . ")";
                $params = array_merge($params, $searchParams);
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
            $countSql = "SELECT COUNT(*) FROM `leads` l LEFT JOIN `teams` t ON l.team_id = t.id LEFT JOIN `projects` p ON l.project_id = p.id WHERE {$whereSql}";
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute($params);
            $totalCount = (int)$countStmt->fetchColumn();

            // Date condition for stats queries (table name leads without alias `l.`)
            $autoWhere = $hasAutomatedCol ? " AND is_automated = 1" : "";
            $statsDateSql = "";
            if (!empty($dateConditionSql)) {
                $statsDateSql = " AND " . str_replace('l.created_at', 'created_at', $dateConditionSql);
            }

            // Stats scoped to the chosen date filter (e.g. today, yesterday, weekly, custom)
            $statsSql = "SELECT 
                            COALESCE(COUNT(*), 0) as total_leads,
                            COALESCE(SUM(CASE WHEN distribute = 0 THEN 1 ELSE 0 END), 0) as pending_leads,
                            COALESCE(SUM(CASE WHEN distribute = 1 THEN 1 ELSE 0 END), 0) as approved_leads,
                            COALESCE(SUM(CASE WHEN (team_id IS NULL OR team_id = 0) THEN 1 ELSE 0 END), 0) as unassigned_leads,
                            COALESCE(SUM(CASE WHEN (team_id IS NOT NULL AND team_id > 0) THEN 1 ELSE 0 END), 0) as assigned_leads
                         FROM `leads` 
                         WHERE deleted_at IS NULL{$autoWhere}{$statsDateSql}";
            $statsStmt = $pdo->prepare($statsSql);
            $statsStmt->execute($dateParams);
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

            // All-time totals for reference
            $allTimeSql = "SELECT 
                                COALESCE(COUNT(*), 0) as total_leads,
                                COALESCE(SUM(CASE WHEN distribute = 0 THEN 1 ELSE 0 END), 0) as pending_leads,
                                COALESCE(SUM(CASE WHEN distribute = 1 THEN 1 ELSE 0 END), 0) as approved_leads
                           FROM `leads` 
                           WHERE deleted_at IS NULL{$autoWhere}";
            $allTimeStats = $pdo->query($allTimeSql)->fetch(PDO::FETCH_ASSOC);

            // Fetch Teams with count of automated leads for the chosen date filter
            $autoJoin = $hasAutomatedCol ? " AND l.is_automated = 1" : "";
            $teamDateJoin = !empty($dateConditionSql) ? " AND {$dateConditionSql}" : "";
            $teamsSql = "SELECT 
                            t.id, 
                            t.name, 
                            COALESCE(COUNT(l.id), 0) as total_leads,
                            COALESCE(SUM(CASE WHEN l.distribute = 0 THEN 1 ELSE 0 END), 0) as pending_leads
                         FROM `teams` t
                         LEFT JOIN `leads` l ON l.team_id = t.id AND l.deleted_at IS NULL{$autoJoin}{$teamDateJoin}
                         GROUP BY t.id, t.name
                         ORDER BY t.id ASC";
            $teamsStmt = $pdo->prepare($teamsSql);
            $teamsStmt->execute($dateParams);
            $teams = $teamsStmt->fetchAll(PDO::FETCH_ASSOC);

            // Check unassigned leads scoped to date filter
            $unassignedStatsSql = "SELECT 
                                     COALESCE(COUNT(*), 0) as total_unassigned,
                                     COALESCE(SUM(CASE WHEN distribute = 0 THEN 1 ELSE 0 END), 0) as pending_unassigned
                                   FROM `leads` 
                                   WHERE deleted_at IS NULL AND (team_id IS NULL OR team_id = 0){$autoWhere}{$statsDateSql}";
            $unassignedStmt = $pdo->prepare($unassignedStatsSql);
            $unassignedStmt->execute($dateParams);
            $unassignedStats = $unassignedStmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'data'   => [
                    'leads'            => $leads,
                    'total_count'      => $totalCount,
                    'stats'            => $stats,
                    'all_time_stats'   => $allTimeStats,
                    'teams'            => $teams,
                    'unassigned_stats' => $unassignedStats,
                    'date_filter'      => $dateFilter,
                    'start_date'       => $startDate,
                    'end_date'         => $endDate,
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
     * Supports:
     * - Optional target_team_id: Assigns selected leads to the chosen team and auto-approves (distribute = 1) in one step!
     * - Optional target_project_id: Assigns selected leads to a specific project.
     * - Bulk all-pending or specific lead_ids list.
     */
    public function ajaxApprove()
    {
        $this->checkAccess();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $leadIds = $input['lead_ids'] ?? ($_POST['lead_ids'] ?? []);
            $teamId = isset($input['team_id']) && $input['team_id'] !== '' ? (int)$input['team_id'] : (!empty($_POST['team_id']) ? (int)$_POST['team_id'] : null);
            $targetTeamId = isset($input['target_team_id']) && $input['target_team_id'] !== '' ? (int)$input['target_team_id'] : (!empty($_POST['target_team_id']) ? (int)$_POST['target_team_id'] : null);
            $targetProjectId = isset($input['target_project_id']) && $input['target_project_id'] !== '' ? (int)$input['target_project_id'] : (!empty($_POST['target_project_id']) ? (int)$_POST['target_project_id'] : null);
            $approveAll = !empty($input['all_pending']) || !empty($_POST['all_pending']);

            $synchronizer = new LeadSynchronizer($this->automationConfig);
            $pdo = $synchronizer->getDb();

            $approverId = !empty($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;

            // Direct All-Pending or Team-Wise All-Pending
            if ($approveAll) {
                if ($teamId) {
                    $updateSql = "UPDATE `leads` SET `distribute` = 1, `approved_by` = ?, `approved_at` = NOW(), `updated_at` = NOW() WHERE `distribute` = 0 AND `deleted_at` IS NULL AND `is_automated` = 1 AND `team_id` = ?";
                    $stmt = $pdo->prepare($updateSql);
                    $stmt->execute([$approverId, $teamId]);
                } else {
                    $updateSql = "UPDATE `leads` SET `distribute` = 1, `approved_by` = ?, `approved_at` = NOW(), `updated_at` = NOW() WHERE `distribute` = 0 AND `deleted_at` IS NULL AND `is_automated` = 1";
                    $stmt = $pdo->prepare($updateSql);
                    $stmt->execute([$approverId]);
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

            $leadIds = array_values(array_filter(array_map('intval', $leadIds), fn($id) => $id > 0));
            if (empty($leadIds)) {
                throw new InvalidArgumentException("No valid lead IDs provided.");
            }

            $placeholders = implode(',', array_fill(0, count($leadIds), '?'));

            // Build dynamic SET clauses based on whether target_team_id / target_project_id are provided
            $setClauses = ["`distribute` = 1", "`approved_by` = ?", "`approved_at` = NOW()", "`updated_at` = NOW()"];
            $params = [$approverId];

            if ($targetTeamId !== null && $targetTeamId > 0) {
                $setClauses[] = "`team_id` = ?";
                $params[] = $targetTeamId;
            }

            if ($targetProjectId !== null && $targetProjectId > 0) {
                $setClauses[] = "`project_id` = ?";
                $params[] = $targetProjectId;
            }

            $setSql = implode(", ", $setClauses);
            $updateSql = "UPDATE `leads` SET {$setSql} WHERE `id` IN ({$placeholders})";
            $params = array_merge($params, $leadIds);

            $stmt = $pdo->prepare($updateSql);
            $stmt->execute($params);
            $affected = $stmt->rowCount();
            $totalSelected = count($leadIds);

            // Fetch team name for the message if assigned
            $teamName = '';
            if ($targetTeamId !== null && $targetTeamId > 0) {
                try {
                    $tStmt = $pdo->prepare("SELECT name FROM `teams` WHERE id = ?");
                    $tStmt->execute([$targetTeamId]);
                    $teamName = $tStmt->fetchColumn() ?: "Team #{$targetTeamId}";
                } catch (Throwable $t) {}
            }

            $successMsg = $teamName
                ? "Successfully assigned {$totalSelected} lead(s) to {$teamName} and auto-approved for distribution!"
                : ($affected > 0
                    ? "Successfully approved and distributed {$affected} lead(s) to sales teams!"
                    : "Selected {$totalSelected} lead(s) are approved and distributed.");

            echo json_encode([
                'status'  => 'success',
                'message' => $successMsg,
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
     * AJAX: Assign & Approve Leads (Alias for ajaxApprove with target_team_id)
     */
    public function ajaxAssignApprove()
    {
        return $this->ajaxApprove();
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
     * AJAX: Permanently Delete Leads (bulk or single, team-wise or list of IDs)
     * Completely removes them from the database (leads and all related child records)
     * so they will not appear anywhere in team lead lists or CRM views.
     */
    public function ajaxDelete()
    {
        $this->checkAccess();
        header('Content-Type: application/json; charset=utf-8');

        $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $secondaryRoleId = (int)($_SESSION['user']['secondary_role_id'] ?? 0);
        $primaryRoleId = (int)($_SESSION['user']['primary_role_id'] ?? 0);
        $userRoleName = strtolower(trim((string)($_SESSION['user']['role_name'] ?? '')));

        // Role 11 (Executive of Digital Marketing Team) is strictly forbidden from deleting leads
        $isDMExecutive = ($userRoleId === 11 || $secondaryRoleId === 11 || $primaryRoleId === 11 || strpos($userRoleName, 'executive of digital marketing') !== false);
        $isManagementUser = in_array($userRoleId, [1, 3, 33, 34, 35], true)
            || in_array($secondaryRoleId, [1, 3, 33, 34, 35], true)
            || in_array($primaryRoleId, [1, 3, 33, 34, 35], true)
            || ((int)($_SESSION['user']['id'] ?? 0) === 94)
            || strpos($userRoleName, 'admin') !== false
            || strpos($userRoleName, 'management') !== false
            || strpos($userRoleName, 'director') !== false
            || strpos($userRoleName, 'chief') !== false;
        $isHeadOfDM = ($userRoleId === 10 || $secondaryRoleId === 10 || $primaryRoleId === 10 || strpos($userRoleName, 'head of digital marketing') !== false);

        if ($isDMExecutive || (!$isManagementUser && !$isHeadOfDM)) {
            http_response_code(403);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Permission denied: Executive of Digital Marketing Team (Role 11) is not allowed to delete leads.'
            ]);
            exit;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $leadIds = $input['lead_ids'] ?? ($_POST['lead_ids'] ?? []);
            $teamId = isset($input['team_id']) && $input['team_id'] !== '' ? (int)$input['team_id'] : (!empty($_POST['team_id']) ? (int)$_POST['team_id'] : null);
            $allApproved = !empty($input['all_approved']) || !empty($_POST['all_approved']);
            $allForTeam = !empty($input['all_for_team']) || !empty($_POST['all_for_team']);

            $synchronizer = new LeadSynchronizer($this->automationConfig);
            $pdo = $synchronizer->getDb();

            // Handle bulk team-wise purge (approved leads or all automated leads for team)
            if ($allApproved || $allForTeam) {
                $where = ["is_automated = 1"];
                $params = [];

                if ($allApproved && !$allForTeam) {
                    $where[] = "distribute = 1";
                }

                if ($teamId) {
                    $where[] = "team_id = ?";
                    $params[] = $teamId;
                }

                $whereSql = implode(" AND ", $where);
                $fetchStmt = $pdo->prepare("SELECT id FROM `leads` WHERE {$whereSql}");
                $fetchStmt->execute($params);
                $leadIds = $fetchStmt->fetchAll(PDO::FETCH_COLUMN);

                if (empty($leadIds)) {
                    echo json_encode([
                        'status'  => 'success',
                        'message' => 'No matching leads found to delete.',
                        'count'   => 0
                    ]);
                    exit;
                }
            }

            if (empty($leadIds)) {
                throw new InvalidArgumentException("No lead IDs provided for deletion.");
            }

            if (!is_array($leadIds)) {
                $leadIds = [(int)$leadIds];
            }

            $leadIds = array_values(array_filter(array_map('intval', $leadIds), fn($id) => $id > 0));
            if (empty($leadIds)) {
                throw new InvalidArgumentException("No valid lead IDs provided.");
            }

            // Perform permanent deletion in transaction across all related tables
            $pdo->beginTransaction();

            $placeholders = implode(',', array_fill(0, count($leadIds), '?'));

            // 1. lead_payments associated with bookings
            try {
                $stmt = $pdo->prepare("DELETE FROM `lead_payments` WHERE booking_id IN (SELECT id FROM `lead_bookings` WHERE lead_id IN ({$placeholders}))");
                $stmt->execute($leadIds);
            } catch (Throwable $t) {}

            // 2. lead_bookings
            try {
                $stmt = $pdo->prepare("DELETE FROM `lead_bookings` WHERE lead_id IN ({$placeholders})");
                $stmt->execute($leadIds);
            } catch (Throwable $t) {}

            // 3. lead_follow_ups
            try {
                $stmt = $pdo->prepare("DELETE FROM `lead_follow_ups` WHERE lead_id IN ({$placeholders})");
                $stmt->execute($leadIds);
            } catch (Throwable $t) {}

            // 4. lead_delete_requests
            try {
                $stmt = $pdo->prepare("DELETE FROM `lead_delete_requests` WHERE lead_id IN ({$placeholders})");
                $stmt->execute($leadIds);
            } catch (Throwable $t) {}

            // 5. lead_assignment_history
            try {
                $stmt = $pdo->prepare("DELETE FROM `lead_assignment_history` WHERE lead_id IN ({$placeholders})");
                $stmt->execute($leadIds);
            } catch (Throwable $t) {}

            // 6. lead_call_logs
            try {
                $stmt = $pdo->prepare("DELETE FROM `lead_call_logs` WHERE lead_id IN ({$placeholders})");
                $stmt->execute($leadIds);
            } catch (Throwable $t) {}

            // 7. client_refund_requests
            try {
                $stmt = $pdo->prepare("DELETE FROM `client_refund_requests` WHERE lead_id IN ({$placeholders})");
                $stmt->execute($leadIds);
            } catch (Throwable $t) {}

            // 8. onboard_clients
            try {
                $stmt = $pdo->prepare("DELETE FROM `onboard_clients` WHERE lead_id IN ({$placeholders})");
                $stmt->execute($leadIds);
            } catch (Throwable $t) {}

            // 9. whatsapp_messages
            try {
                $stmt = $pdo->prepare("DELETE FROM `whatsapp_messages` WHERE lead_id IN ({$placeholders})");
                $stmt->execute($leadIds);
            } catch (Throwable $t) {}

            // 10. leads table
            $delStmt = $pdo->prepare("DELETE FROM `leads` WHERE id IN ({$placeholders})");
            $delStmt->execute($leadIds);
            $deletedCount = $delStmt->rowCount();

            $pdo->commit();

            echo json_encode([
                'status'  => 'success',
                'message' => "Successfully permanently deleted {$deletedCount} lead(s) from database and team lists.",
                'count'   => $deletedCount
            ]);
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
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

    /**
     * AJAX: Get Automated Platform Stats & Team Ranking
     */
    public function ajaxPlatformStats()
    {
        $this->checkAccess();
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        try {
            $filters = [
                'date_preset' => $_GET['date_preset'] ?? $_POST['date_preset'] ?? 'today',
                'start_date'  => $_GET['start_date'] ?? $_POST['start_date'] ?? '',
                'end_date'    => $_GET['end_date'] ?? $_POST['end_date'] ?? '',
                'project_id'  => $_GET['project_id'] ?? $_POST['project_id'] ?? '',
                'platform'    => $_GET['platform'] ?? $_POST['platform'] ?? '',
                'team_id'     => $_GET['team_id'] ?? $_POST['team_id'] ?? '',
                'status'      => $_GET['status'] ?? $_POST['status'] ?? '',
            ];

            $report = $this->getAutomatedPlatformReport($filters);

            echo json_encode([
                'status' => 'success',
                'data'   => $report
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
     * Compute automated leads platform report & team ranking
     */
    public function getAutomatedPlatformReport(array $filters = []): array
    {
        try {
            $synchronizer = new LeadSynchronizer($this->automationConfig);
            $pdo = $synchronizer->getDb();
        } catch (Throwable $e) {
            $pdo = $this->db;
        }

        // Check if is_automated column exists (self-healing)
        $hasAutomatedCol = false;
        try {
            $checkCols = $pdo->query("SHOW COLUMNS FROM `leads` LIKE 'is_automated'")->fetchAll();
            $hasAutomatedCol = !empty($checkCols);
        } catch (Throwable $t) {
            $hasAutomatedCol = false;
        }

        $where = ["l.deleted_at IS NULL"];
        if ($hasAutomatedCol) {
            $where[] = "l.is_automated = 1";
        }
        $params = [];

        // Date Preset Resolution
        $datePreset = strtolower(trim((string)($filters['date_preset'] ?? 'today')));
        $startDate = trim((string)($filters['start_date'] ?? ''));
        $endDate = trim((string)($filters['end_date'] ?? ''));
        $dateLabel = '';
        $daysCount = 1;

        if ($datePreset === 'today') {
            $where[] = "DATE(l.created_at) = CURDATE()";
            $dateLabel = date('d M Y');
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d');
            $daysCount = 1;
        } elseif ($datePreset === 'yesterday') {
            $where[] = "DATE(l.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            $dateLabel = date('d M Y', strtotime('-1 day'));
            $startDate = date('Y-m-d', strtotime('-1 day'));
            $endDate = date('Y-m-d', strtotime('-1 day'));
            $daysCount = 1;
        } elseif ($datePreset === '7days' || $datePreset === 'last_7_days') {
            $where[] = "DATE(l.created_at) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND DATE(l.created_at) <= CURDATE()";
            $dateLabel = date('d M', strtotime('-6 days')) . ' - ' . date('d M Y');
            $startDate = date('Y-m-d', strtotime('-6 days'));
            $endDate = date('Y-m-d');
            $daysCount = 7;
        } elseif ($datePreset === 'cycle' || $datePreset === 'reporting_cycle') {
            $day = (int)date('j');
            if ($day >= 26) {
                $cycleStart = date('Y-m-26');
                $cycleEnd = date('Y-m-25', strtotime('+1 month'));
            } else {
                $cycleStart = date('Y-m-26', strtotime('-1 month'));
                $cycleEnd = date('Y-m-25');
            }
            $where[] = "DATE(l.created_at) >= ? AND DATE(l.created_at) <= ?";
            $params[] = $cycleStart;
            $params[] = $cycleEnd;
            $dateLabel = date('d M', strtotime($cycleStart)) . ' - ' . date('d M Y', strtotime($cycleEnd));
            $startDate = $cycleStart;
            $endDate = $cycleEnd;
            $daysCount = (int)round((strtotime($cycleEnd) - strtotime($cycleStart)) / 86400) + 1;
        } elseif ($datePreset === 'custom') {
            if (!empty($startDate) && !empty($endDate)) {
                $where[] = "DATE(l.created_at) >= ? AND DATE(l.created_at) <= ?";
                $params[] = $startDate;
                $params[] = $endDate;
                $dateLabel = date('d M Y', strtotime($startDate)) . ' - ' . date('d M Y', strtotime($endDate));
                $daysCount = max(1, (int)round((strtotime($endDate) - strtotime($startDate)) / 86400) + 1);
            } elseif (!empty($startDate)) {
                $where[] = "DATE(l.created_at) >= ?";
                $params[] = $startDate;
                $dateLabel = 'From ' . date('d M Y', strtotime($startDate));
            } elseif (!empty($endDate)) {
                $where[] = "DATE(l.created_at) <= ?";
                $params[] = $endDate;
                $dateLabel = 'Until ' . date('d M Y', strtotime($endDate));
            } else {
                $dateLabel = 'All Time';
            }
        } else {
            // all time
            $datePreset = 'all';
            $dateLabel = 'All Time';
            $daysCount = 0;
        }

        // Project filter
        $projectFilter = trim((string)($filters['project_id'] ?? ''));
        if ($projectFilter === 'common' || $projectFilter === '0') {
            $where[] = "(l.project_id IS NULL OR l.project_id = 0)";
        } elseif (is_numeric($projectFilter) && (int)$projectFilter > 0) {
            $where[] = "l.project_id = ?";
            $params[] = (int)$projectFilter;
        }

        // Platform filter
        $platformFilter = strtolower(trim((string)($filters['platform'] ?? '')));
        if ($platformFilter === 'facebook' || $platformFilter === 'facebook_form') {
            $where[] = "(LOWER(TRIM(COALESCE(l.platform, ''))) IN ('facebook', 'fb', 'fb(lead form)', 'lead form', 'leadform', 'leadfrom', 'meta form', 'meta inform') OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%fb%' OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%facebook%' OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%lead form%' OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%leadform%')";
        } elseif ($platformFilter === 'whatsapp') {
            $where[] = "(LOWER(TRIM(COALESCE(l.platform, ''))) IN ('whatsapp', 'wa', 'whats app', 'whatsappp', 'whataapp', 'whatsapp lead', 'whstsapp') OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%whatsapp%' OR LOWER(TRIM(COALESCE(l.platform, ''))) = 'wa')";
        } elseif ($platformFilter === 'meta_inbox') {
            $where[] = "(LOWER(TRIM(COALESCE(l.platform, ''))) IN ('meta inbox', 'meta') OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%inbox%')";
        } elseif ($platformFilter === 'google_form') {
            $where[] = "(LOWER(TRIM(COALESCE(l.platform, ''))) IN ('google form', 'google', 'googleform') OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%google%')";
        } elseif ($platformFilter === 'brochure' || $platformFilter === 'brochure_leads') {
            $where[] = "(LOWER(TRIM(COALESCE(l.platform, ''))) IN ('brochure leads', 'brocure leads', 'brochure lead', 'brocure lead', 'brochure') OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%brochure%')";
        } elseif ($platformFilter === 'others') {
            $where[] = "(NOT (
                LOWER(TRIM(COALESCE(l.platform, ''))) IN ('facebook', 'fb', 'fb(lead form)', 'lead form', 'leadform', 'leadfrom', 'meta form', 'meta inform')
                OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%fb%'
                OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%facebook%'
                OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%lead form%'
                OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%leadform%'
                OR LOWER(TRIM(COALESCE(l.platform, ''))) IN ('whatsapp', 'wa', 'whats app', 'whatsappp', 'whataapp', 'whatsapp lead', 'whstsapp')
                OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%whatsapp%'
                OR LOWER(TRIM(COALESCE(l.platform, ''))) = 'wa'
                OR LOWER(TRIM(COALESCE(l.platform, ''))) IN ('meta inbox', 'meta')
                OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%inbox%'
                OR LOWER(TRIM(COALESCE(l.platform, ''))) IN ('google form', 'google', 'googleform')
                OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%google%'
                OR LOWER(TRIM(COALESCE(l.platform, ''))) IN ('brochure leads', 'brocure leads', 'brochure lead', 'brocure lead', 'brochure')
                OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%brochure%'
            ))";
        }

        // Team filter
        $teamFilter = trim((string)($filters['team_id'] ?? ''));
        if ($teamFilter === 'unassigned') {
            $where[] = "(l.team_id IS NULL OR l.team_id = 0)";
        } elseif ($teamFilter === 'assigned') {
            $where[] = "(l.team_id IS NOT NULL AND l.team_id > 0)";
        } elseif (is_numeric($teamFilter) && (int)$teamFilter > 0) {
            $where[] = "l.team_id = ?";
            $params[] = (int)$teamFilter;
        }

        // Status / Assignment filter
        $statusFilter = strtolower(trim((string)($filters['status'] ?? '')));
        if ($statusFilter === 'assigned') {
            $where[] = "(l.team_id IS NOT NULL AND l.team_id > 0)";
        } elseif ($statusFilter === 'needs_assignment' || $statusFilter === 'unassigned') {
            $where[] = "(l.team_id IS NULL OR l.team_id = 0)";
        } elseif ($statusFilter === 'pending' || $statusFilter === 'awaiting_approval') {
            $where[] = "l.distribute = 0";
        } elseif ($statusFilter === 'approved') {
            $where[] = "l.distribute = 1";
        }

        $whereSql = implode(" AND ", $where);

        // CASE expression for platform grouping
        $casePlatform = "CASE
            WHEN LOWER(TRIM(COALESCE(l.platform, ''))) IN ('meta inbox', 'meta') 
                 OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%inbox%' 
                 THEN 'meta_inbox'
            WHEN LOWER(TRIM(COALESCE(l.platform, ''))) IN ('whatsapp', 'wa', 'whats app', 'whatsappp', 'whataapp', 'whatsapp lead', 'whstsapp') 
                 OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%whatsapp%' 
                 OR LOWER(TRIM(COALESCE(l.platform, ''))) = 'wa'
                 THEN 'whatsapp'
            WHEN LOWER(TRIM(COALESCE(l.platform, ''))) IN ('google form', 'google', 'googleform') 
                 OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%google%' 
                 THEN 'google_form'
            WHEN LOWER(TRIM(COALESCE(l.platform, ''))) IN ('brochure leads', 'brocure leads', 'brochure lead', 'brocure lead', 'brochure') 
                 OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%brochure%' 
                 THEN 'brochure'
            WHEN LOWER(TRIM(COALESCE(l.platform, ''))) IN ('facebook', 'fb', 'fb(lead form)', 'lead form', 'leadform', 'leadfrom', 'meta form', 'meta inform') 
                 OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%fb%' 
                 OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%facebook%' 
                 OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%lead form%' 
                 OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%leadform%' 
                 THEN 'facebook_form'
            ELSE 'others'
        END";

        // 1. KPI & Platform summary query
        $kpiSql = "SELECT 
            COUNT(*) as total_received,
            COALESCE(SUM(CASE WHEN l.team_id IS NOT NULL AND l.team_id > 0 THEN 1 ELSE 0 END), 0) as assigned_to_teams,
            COALESCE(SUM(CASE WHEN l.team_id IS NULL OR l.team_id = 0 THEN 1 ELSE 0 END), 0) as needs_assignment,
            COALESCE(SUM(CASE WHEN l.distribute = 0 THEN 1 ELSE 0 END), 0) as awaiting_approval,
            COALESCE(SUM(CASE WHEN {$casePlatform} = 'facebook_form' THEN 1 ELSE 0 END), 0) as fb_count,
            COALESCE(SUM(CASE WHEN {$casePlatform} = 'whatsapp' THEN 1 ELSE 0 END), 0) as wa_count,
            COALESCE(SUM(CASE WHEN {$casePlatform} = 'meta_inbox' THEN 1 ELSE 0 END), 0) as mi_count,
            COALESCE(SUM(CASE WHEN {$casePlatform} = 'google_form' THEN 1 ELSE 0 END), 0) as gf_count,
            COALESCE(SUM(CASE WHEN {$casePlatform} = 'brochure' THEN 1 ELSE 0 END), 0) as br_count,
            COALESCE(SUM(CASE WHEN {$casePlatform} = 'others' THEN 1 ELSE 0 END), 0) as others_count
        FROM `leads` l
        WHERE {$whereSql}";

        $kpiStmt = $pdo->prepare($kpiSql);
        $kpiStmt->execute($params);
        $kpiRaw = $kpiStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $totalReceived = (int)($kpiRaw['total_received'] ?? 0);
        $assignedToTeams = (int)($kpiRaw['assigned_to_teams'] ?? 0);
        $needsAssignment = (int)($kpiRaw['needs_assignment'] ?? 0);
        $awaitingApproval = (int)($kpiRaw['awaiting_approval'] ?? 0);

        $assignedPct = $totalReceived > 0 ? round(($assignedToTeams / $totalReceived) * 100, 1) : 0.0;

        $fbCount = (int)($kpiRaw['fb_count'] ?? 0);
        $waCount = (int)($kpiRaw['wa_count'] ?? 0);
        $miCount = (int)($kpiRaw['mi_count'] ?? 0);
        $gfCount = (int)($kpiRaw['gf_count'] ?? 0);
        $brCount = (int)($kpiRaw['br_count'] ?? 0);
        $othersCount = (int)($kpiRaw['others_count'] ?? 0);

        $fbPct = $totalReceived > 0 ? round(($fbCount / $totalReceived) * 100, 1) : 0.0;
        $waPct = $totalReceived > 0 ? round(($waCount / $totalReceived) * 100, 1) : 0.0;
        $miPct = $totalReceived > 0 ? round(($miCount / $totalReceived) * 100, 1) : 0.0;
        $gfPct = $totalReceived > 0 ? round(($gfCount / $totalReceived) * 100, 1) : 0.0;
        $brPct = $totalReceived > 0 ? round(($brCount / $totalReceived) * 100, 1) : 0.0;
        $othersPct = $totalReceived > 0 ? round(($othersCount / $totalReceived) * 100, 1) : 0.0;

        // 2. Project-wise breakdown table
        $projSql = "SELECT 
            CASE WHEN l.project_id IS NULL OR l.project_id = 0 THEN 0 ELSE l.project_id END as project_id,
            CASE WHEN l.project_id IS NULL OR l.project_id = 0 THEN 'Common' ELSE COALESCE(p.name, 'Common') END as project_name,
            COUNT(l.id) as total,
            COALESCE(SUM(CASE WHEN {$casePlatform} = 'facebook_form' THEN 1 ELSE 0 END), 0) as fb_count,
            COALESCE(SUM(CASE WHEN {$casePlatform} = 'whatsapp' THEN 1 ELSE 0 END), 0) as wa_count,
            COALESCE(SUM(CASE WHEN {$casePlatform} = 'meta_inbox' THEN 1 ELSE 0 END), 0) as mi_count,
            COALESCE(SUM(CASE WHEN {$casePlatform} = 'google_form' THEN 1 ELSE 0 END), 0) as gf_count,
            COALESCE(SUM(CASE WHEN {$casePlatform} = 'brochure' THEN 1 ELSE 0 END), 0) as br_count,
            COALESCE(SUM(CASE WHEN {$casePlatform} = 'others' THEN 1 ELSE 0 END), 0) as others_count
        FROM `leads` l
        LEFT JOIN `projects` p ON l.project_id = p.id
        WHERE {$whereSql}
        GROUP BY 
            CASE WHEN l.project_id IS NULL OR l.project_id = 0 THEN 0 ELSE l.project_id END,
            CASE WHEN l.project_id IS NULL OR l.project_id = 0 THEN 'Common' ELSE COALESCE(p.name, 'Common') END
        ORDER BY (CASE WHEN project_name = 'Common' THEN 0 ELSE 1 END), total DESC";

        $projStmt = $pdo->prepare($projSql);
        $projStmt->execute($params);
        $projects = $projStmt->fetchAll(PDO::FETCH_ASSOC);

        // Sum column totals
        $totFb = 0; $totWa = 0; $totMi = 0; $totGf = 0; $totBr = 0; $totOthers = 0; $grandTotal = 0;
        foreach ($projects as &$pr) {
            $pr['project_id'] = (int)$pr['project_id'];
            $pr['total'] = (int)$pr['total'];
            $pr['fb_count'] = (int)$pr['fb_count'];
            $pr['wa_count'] = (int)$pr['wa_count'];
            $pr['mi_count'] = (int)$pr['mi_count'];
            $pr['gf_count'] = (int)$pr['gf_count'];
            $pr['br_count'] = (int)$pr['br_count'];
            $pr['others_count'] = (int)$pr['others_count'];

            $totFb += $pr['fb_count'];
            $totWa += $pr['wa_count'];
            $totMi += $pr['mi_count'];
            $totGf += $pr['gf_count'];
            $totBr += $pr['br_count'];
            $totOthers += $pr['others_count'];
            $grandTotal += $pr['total'];
        }
        unset($pr);

        // 3. Top allocated teams ranked serial-wise (highest to lowest)
        $teamsSql = "SELECT 
            t.id, 
            t.name, 
            COALESCE(COUNT(l.id), 0) as allocated_count,
            COALESCE(SUM(CASE WHEN l.distribute = 1 THEN 1 ELSE 0 END), 0) as approved_count,
            COALESCE(SUM(CASE WHEN l.distribute = 0 THEN 1 ELSE 0 END), 0) as pending_count
        FROM `teams` t
        LEFT JOIN `leads` l ON l.team_id = t.id AND {$whereSql}
        GROUP BY t.id, t.name
        ORDER BY allocated_count DESC, t.name ASC";

        $teamsStmt = $pdo->prepare($teamsSql);
        $teamsStmt->execute($params);
        $rawTeams = $teamsStmt->fetchAll(PDO::FETCH_ASSOC);

        $rankedTeams = [];
        $rank = 1;
        $totalTeamAllocated = 0;
        foreach ($rawTeams as $t) {
            $totalTeamAllocated += (int)$t['allocated_count'];
        }

        foreach ($rawTeams as $t) {
            $alloc = (int)$t['allocated_count'];
            $pct = $totalTeamAllocated > 0 ? round(($alloc / $totalTeamAllocated) * 100, 1) : 0.0;
            $rankedTeams[] = [
                'rank'            => $rank++,
                'id'              => (int)$t['id'],
                'name'            => $t['name'],
                'allocated_count' => $alloc,
                'approved_count'  => (int)$t['approved_count'],
                'pending_count'   => (int)$t['pending_count'],
                'pct'             => $pct
            ];
        }

        return [
            'kpi' => [
                'leads_received'    => $totalReceived,
                'assigned_to_teams' => $assignedToTeams,
                'assigned_pct'      => $assignedPct,
                'needs_assignment'  => $needsAssignment,
                'awaiting_approval' => $awaitingApproval,
            ],
            'platforms' => [
                'facebook_form' => ['count' => $fbCount, 'pct' => $fbPct],
                'whatsapp'      => ['count' => $waCount, 'pct' => $waPct],
                'meta_inbox'    => ['count' => $miCount, 'pct' => $miPct],
                'google_form'   => ['count' => $gfCount, 'pct' => $gfPct],
                'brochure'      => ['count' => $brCount, 'pct' => $brPct],
                'others'        => ['count' => $othersCount, 'pct' => $othersPct],
            ],
            'projects' => $projects,
            'totals'   => [
                'facebook_form' => $totFb,
                'whatsapp'      => $totWa,
                'meta_inbox'    => $totMi,
                'google_form'   => $totGf,
                'brochure'      => $totBr,
                'others'        => $totOthers,
                'grand_total'   => $grandTotal,
            ],
            'teams'    => $rankedTeams,
            'meta'     => [
                'date_preset'           => $datePreset,
                'date_label'            => $dateLabel,
                'start_date'            => $startDate,
                'end_date'              => $endDate,
                'days_count'            => $daysCount,
                'active_projects_count' => count($projects),
                'active_teams_count'    => count($rawTeams),
            ]
        ];
    }
}
