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

        return $this->view("digital-marketing-team/lead-automation", [
            "current_route" => "lead-automation-upload",
            "page_title"    => "Lead Automation Upload & Distribution",
            "teams"         => $teams,
            "projects"      => $projects,
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
}
