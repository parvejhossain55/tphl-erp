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

            // 4. Check & add is_repibee column if missing
            $repibeeCols = $pdo->query("SHOW COLUMNS FROM `leads` LIKE 'is_repibee'")->fetchAll();
            if (empty($repibeeCols)) {
                $pdo->exec("ALTER TABLE `leads` ADD COLUMN `is_repibee` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_automated`");
                try {
                    $pdo->exec("CREATE INDEX `idx_leads_is_repibee` ON `leads` (`is_repibee`)");
                } catch (Throwable $t) {}
            }

            // 5. Check & add is_brocure_leads column if missing
            $brocureCols = $pdo->query("SHOW COLUMNS FROM `leads` LIKE 'is_brocure_leads'")->fetchAll();
            if (empty($brocureCols)) {
                $pdo->exec("ALTER TABLE `leads` ADD COLUMN `is_brocure_leads` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_repibee`");
                try {
                    $pdo->exec("CREATE INDEX `idx_leads_is_brocure` ON `leads` (`is_brocure_leads`)");
                } catch (Throwable $t) {}
            }

            // 6. Ensure `lead_automation_project_rules` table exists and auto-seed from config if empty
            $synchronizer->ensureSchema($pdo);
        } catch (Throwable $e) {
            error_log("LeadAutomation ensureSchema error: " . $e->getMessage());
        }
    }

    /**
     * Check authorization for Role 10, Admin, or users with Digital Marketing permissions.
     * Role 11 (Executive Of Digital Marketing Team) is strictly restricted.
     */
    private function checkAccess(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userRole = (int)($_SESSION['user']['role_id'] ?? 0);
        $secondaryRole = (int)($_SESSION['user']['secondary_role_id'] ?? 0);
        $primaryRole = (int)($_SESSION['user']['primary_role_id'] ?? 0);
        $userId = (int)($_SESSION['user']['id'] ?? 0);

        // Strictly block Executive Of Digital Marketing Team (Role 11)
        if ($userRole === 11 || $secondaryRole === 11 || $primaryRole === 11) {
            if (function_exists('setError')) {
                setError("You do not have permission to access Lead Automation.");
            }
            header("Location: {$GLOBALS['base_url']}/my-dashboard");
            exit;
        }

        $allowedRoles = [1, 3, 10, 33, 34, 35]; // Admin, Super Admin, DM Head, Management

        $hasPerm = function_exists('hasPermission') && hasPermission("Digital Marketing Panel");

        $isAuthorized = in_array($userRole, $allowedRoles, true)
            || in_array($secondaryRole, $allowedRoles, true)
            || in_array($primaryRole, $allowedRoles, true)
            || $userId === 94
            || $hasPerm;

        if (!$isAuthorized) {
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

        $teams = array_values(array_filter($this->teamsModel->getAll(), fn($t) => (int)($t['status'] ?? 1) === 1));
        $sheetUrl = $this->automationConfig['google_sheet']['url'] ?? '';

        $projects = [];
        try {
            $pRes = $this->db->query("SELECT id, name FROM `projects` WHERE deleted_at IS NULL ORDER BY name ASC");
            if ($pRes instanceof mysqli_result) {
                $projects = $pRes->fetch_all(MYSQLI_ASSOC);
            } elseif ($pRes) {
                $projects = $pRes->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Throwable $t) {}

        $platformReport = $this->getAutomatedPlatformReport(['date_preset' => 'today']);

        $whatsappLogsStats = ['total' => 0, 'pending' => 0, 'synced' => 0, 'duplicate_skipped' => 0];
        try {
            $syncHelper = new LeadSynchronizer($this->automationConfig);
            $pdo = $syncHelper->getDb();
            $syncHelper->ensureSchema($pdo);
            $wStmt = $pdo->query("
                SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN sync_status = 'pending' THEN 1 ELSE 0 END) AS pending,
                    SUM(CASE WHEN sync_status = 'synced' THEN 1 ELSE 0 END) AS synced,
                    SUM(CASE WHEN sync_status = 'duplicate_skipped' THEN 1 ELSE 0 END) AS duplicate_skipped
                FROM `whatsapp_leads_logs`
            ");
            if ($wStmt && ($wRow = $wStmt->fetch(PDO::FETCH_ASSOC))) {
                $whatsappLogsStats = [
                    'total'             => (int)($wRow['total'] ?? 0),
                    'pending'           => (int)($wRow['pending'] ?? 0),
                    'synced'            => (int)($wRow['synced'] ?? 0),
                    'duplicate_skipped' => (int)($wRow['duplicate_skipped'] ?? 0),
                ];
            }
        } catch (Throwable $t) {}

        return $this->view("digital-marketing-team/lead-automation", [
            "current_route"     => "lead-automation-upload",
            "page_title"        => "Lead Automation Upload & Distribution",
            "teams"             => $teams,
            "projects"          => $projects,
            "sheet_url"         => $sheetUrl,
            "gemini_active"     => !empty($this->automationConfig['gemini']['enabled']) && !empty($this->automationConfig['gemini']['api_key']),
            "platformReport"    => $platformReport,
            "whatsappLogsStats" => $whatsappLogsStats,
        ]);
    }

    /**
     * Dedicated Page: Team & Project Setup for Lead Automation
     */
    public function teamProjectSetup()
    {
        $this->checkAccess();
        $this->ensureSchema();

        $teams = array_values(array_filter($this->teamsModel->getAll(), fn($t) => (int)($t['status'] ?? 1) === 1));

        return $this->view("digital-marketing-team/team-project-setup", [
            "current_route" => "lead-automation-upload",
            "page_title"    => "Team & Project Setup · Lead Automation",
            "teams"         => $teams,
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
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');
        @ini_set('memory_limit', '512M');
        ob_start();

        try {
            $sheetUrl = trim($_POST['sheet_url'] ?? '');
            if (empty($sheetUrl)) {
                $sheetUrl = null;
            }

            $currentUserId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
            $synchronizer = new LeadSynchronizer($this->automationConfig, $currentUserId);
            $result = $synchronizer->runSync($sheetUrl);

            $tabCount = count($result['tabs_synced'] ?? []);
            $tabSuffix = $tabCount > 1 ? sprintf(" across %d sheet tabs", $tabCount) : "";
            while (ob_get_level() > 0) {
                @ob_end_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status'  => 'success',
                'message' => sprintf(
                    "Sync finished%s! %d new leads added (Pending distribution), %d duplicates skipped.",
                    $tabSuffix,
                    $result['inserted'] ?? 0,
                    $result['skipped_duplicate'] ?? 0
                ),
                'data'    => $result
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;

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
                $where[] = "(l.is_repibee = 1 OR LOWER(TRIM(COALESCE(l.platform, ''))) IN ('whatsapp', 'wa', 'whats app', 'whatsappp', 'whataapp', 'whatsapp lead', 'whstsapp') OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%whatsapp%' OR LOWER(TRIM(COALESCE(l.platform, ''))) = 'wa')";
            } elseif ($platformParam === 'replibee') {
                $where[] = "l.is_repibee = 1";
            } elseif ($platformParam === 'meta_inbox') {
                $where[] = "(LOWER(TRIM(COALESCE(l.platform, ''))) IN ('meta inbox', 'meta') OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%inbox%')";
            } elseif ($platformParam === 'google_form') {
                $where[] = "(LOWER(TRIM(COALESCE(l.platform, ''))) IN ('google form', 'google', 'googleform') OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%google%')";
            } elseif ($platformParam === 'brochure' || $platformParam === 'brochure_leads') {
                $where[] = "(l.is_brocure_leads = 1 OR LOWER(TRIM(COALESCE(l.platform, ''))) IN ('brochure leads', 'brocure leads', 'brochure lead', 'brocure lead', 'brochure') OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%brochure%' OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%brocure%')";
            } elseif ($platformParam === 'others') {
                $where[] = "(NOT (
                    LOWER(TRIM(COALESCE(l.platform, ''))) IN ('facebook', 'fb', 'fb(lead form)', 'lead form', 'leadform', 'leadfrom', 'meta form', 'meta inform')
                    OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%fb%'
                    OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%facebook%'
                    OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%lead form%'
                    OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%leadform%'
                    OR l.is_repibee = 1
                    OR LOWER(TRIM(COALESCE(l.platform, ''))) IN ('whatsapp', 'wa', 'whats app', 'whatsappp', 'whataapp', 'whatsapp lead', 'whstsapp')
                    OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%whatsapp%'
                    OR LOWER(TRIM(COALESCE(l.platform, ''))) = 'wa'
                    OR LOWER(TRIM(COALESCE(l.platform, ''))) IN ('meta inbox', 'meta')
                    OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%inbox%'
                    OR LOWER(TRIM(COALESCE(l.platform, ''))) IN ('google form', 'google', 'googleform')
                    OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%google%'
                    OR l.is_brocure_leads = 1
                    OR LOWER(TRIM(COALESCE(l.platform, ''))) IN ('brochure leads', 'brocure leads', 'brochure lead', 'brocure lead', 'brochure')
                    OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%brochure%'
                    OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%brocure%'
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

                if (strpos($searchLower, 'replibee') !== false) {
                    $searchParts[] = "l.is_repibee = 1";
                }
                if (strpos($searchLower, 'brochure') !== false || strpos($searchLower, 'brocure') !== false) {
                    $searchParts[] = "l.is_brocure_leads = 1";
                }

                $where[] = "(" . implode(" OR ", $searchParts) . ")";
                $params = array_merge($params, $searchParams);
            }

            $whereSql = implode(" AND ", $where);

            // Fetch leads with team names, project names, and creator user
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
                        l.is_automated,
                        l.is_repibee,
                        l.is_brocure_leads,
                        l.platform,
                        l.created_by,
                        l.created_at,
                        l.created_at AS sync_datetime,
                        t.name as team_name,
                        p.name as project_name,
                        u.full_name as creator_name,
                        u.user_name as creator_username
                    FROM `leads` l
                    LEFT JOIN `teams` t ON l.team_id = t.id
                    LEFT JOIN `projects` p ON l.project_id = p.id
                    LEFT JOIN `users` u ON l.created_by = u.id
                    WHERE {$whereSql}
                    ORDER BY l.id DESC
                    LIMIT {$limit} OFFSET {$offset}";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($leads as &$lead) {
                // Determine who synced / added the lead
                if (!empty($lead['creator_name'])) {
                    $lead['synced_by'] = $lead['creator_name'];
                } elseif (!empty($lead['creator_username'])) {
                    $lead['synced_by'] = $lead['creator_username'];
                } elseif ((int)($lead['is_repibee'] ?? 0) === 1) {
                    $lead['synced_by'] = 'Replibee Bot';
                } elseif ((int)($lead['is_brocure_leads'] ?? 0) === 1) {
                    $lead['synced_by'] = 'Brochure API';
                } elseif ((int)($lead['is_automated'] ?? 0) === 1) {
                    $lead['synced_by'] = 'Sheet Automation';
                } else {
                    $lead['synced_by'] = 'System';
                }

                // Format GMT+6 sync date and time
                $dtRaw = $lead['sync_datetime'] ?? $lead['created_at'];
                if (!empty($dtRaw)) {
                    $dt = new DateTime($dtRaw);
                    $lead['formatted_sync_date'] = $dt->format('d M Y');
                    $lead['formatted_sync_time'] = $dt->format('h:i A');
                    $lead['formatted_sync_datetime'] = $dt->format('d M Y, h:i A');
                } else {
                    $lead['formatted_sync_date'] = 'N/A';
                    $lead['formatted_sync_time'] = '';
                    $lead['formatted_sync_datetime'] = 'N/A';
                }
            }
            unset($lead);

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
                         WHERE t.status = 1
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
            WHEN l.is_brocure_leads = 1
                 OR LOWER(TRIM(COALESCE(l.platform, ''))) IN ('brochure leads', 'brocure leads', 'brochure lead', 'brocure lead', 'brochure') 
                 OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%brochure%' 
                 OR LOWER(TRIM(COALESCE(l.platform, ''))) LIKE '%brocure%' 
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
        WHERE t.status = 1
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

    /**
     * Normalize Bangladesh / International Phone Number for reliable duplicate detection
     */
    private function normalizeWhatsAppPhone(?string $rawPhone): string
    {
        $p = trim((string)$rawPhone);
        if ($p === '') {
            return '';
        }
        $p = preg_replace('/^p:/i', '', $p);
        $p = preg_replace('/[\s\-\(\)\.]+/', '', $p);
        $p = preg_replace('/^\+?88(?=01[3-9]\d{8}$)/', '', $p);
        if (preg_match('/^[1-9]\d{9}$/', $p)) {
            $p = '0' . $p;
        }
        return $p;
    }

    /**
     * API Endpoint: Repibee WhatsApp Lead Ingestion Webhook / Receiver
     * Stores all incoming Replibee/WhatsApp webhook leads into `whatsapp_leads_logs` staging table first
     * so zero leads are lost. Digital Marketing team can inspect them in the WhatsApp Leads sidebar
     * and sync them into the main `leads` table via "Sync WhatsApp Leads".
     */
    public function repibeeWebhook()
    {
        header('Content-Type: application/json; charset=utf-8');

        // 1. Verify HTTP Method (Allow POST for webhooks, and GET for endpoint health/handshake checks)
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'HEAD') {
                http_response_code(200);
                echo json_encode([
                    'status'  => 'success',
                    'message' => 'TPHL ERP Webhook endpoint is active and listening for POST events.'
                ]);
                exit;
            }
            http_response_code(405);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Method Not Allowed. Only POST requests are accepted.'
            ]);
            exit;
        }

        // 2. Authentication: Validate API Key or Signature
        $repibeeConfig = $this->automationConfig['repibee'] ?? [];
        $expectedKey = $repibeeConfig['api_key'] ?? 'tphl_repibee_secret_key_2026_x89f2a';
        $signingSecret = $repibeeConfig['signing_secret'] ?? '';
        
        $providedKey = trim((string)(
            $_GET['api_key'] ?? 
            $_GET['token'] ?? 
            $_SERVER['HTTP_X_API_KEY'] ?? 
            $_SERVER['HTTP_AUTHORIZATION'] ?? 
            $_POST['api_key'] ?? 
            ''
        ));
        if (str_starts_with($providedKey, 'Bearer ')) {
            $providedKey = substr($providedKey, 7);
        }

        $rawBody = file_get_contents('php://input');

        $isAuthorized = false;
        if (!empty($providedKey) && hash_equals($expectedKey, $providedKey)) {
            $isAuthorized = true;
        }

        // Verify HMAC-SHA256 signature if Replibee sent X-Webhook-Signature
        $sigHeader = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
        if (!$isAuthorized && !empty($sigHeader) && !empty($signingSecret)) {
            $computed = hash_hmac('sha256', $rawBody, $signingSecret);
            if (hash_equals($computed, $sigHeader)) {
                $isAuthorized = true;
            }
        }

        if (!$isAuthorized) {
            http_response_code(401);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Unauthorized access. Please provide valid API key in URL query parameter (?api_key=...) or X-API-KEY header.'
            ]);
            exit;
        }

        // 3. Parse JSON Body
        $payload = json_decode($rawBody, true);
        if (empty($payload) && !empty($_POST)) {
            $payload = $_POST;
        }

        // 4. Handle Replibee Webhook Ping / Handshake Verification
        $eventName = strtolower(trim((string)($payload['event'] ?? $_SERVER['HTTP_X_WEBHOOK_EVENT'] ?? '')));
        if (
            $eventName === 'webhook.test' || 
            $eventName === 'ping' || 
            $eventName === 'endpoint.test' || 
            $eventName === 'endpoint.created' ||
            str_contains($eventName, 'test') || 
            str_contains($eventName, 'ping') || 
            str_contains($eventName, 'verify') || 
            isset($payload['ping']) || 
            isset($payload['test'])
        ) {
            echo json_encode([
                'status'  => 'success',
                'message' => 'TPHL ERP Webhook endpoint verified successfully.'
            ]);
            exit;
        }

        if (empty($payload) || !is_array($payload)) {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Invalid or empty JSON payload.'
            ]);
            exit;
        }

        // 5. Unpack Replibee Webhook Envelope (data object or direct payload or batch array)
        $leadsToProcess = [];
        if (isset($payload['leads']) && is_array($payload['leads'])) {
            $leadsToProcess = $payload['leads'];
        } elseif (isset($payload['data']) && is_array($payload['data'])) {
            $leadsToProcess = isset($payload['data'][0]) && is_array($payload['data'][0]) ? $payload['data'] : [$payload['data']];
        } else {
            $leadsToProcess = [$payload];
        }

        $currentUserId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
        $synchronizer = new LeadSynchronizer($this->automationConfig, $currentUserId);
        $pdo = $synchronizer->getDb();
        $synchronizer->ensureSchema($pdo);

        $synchronizer->log(sprintf("[Replibee Webhook] Inbound request received with %d item(s) -> storing in whatsapp_leads_logs.", count($leadsToProcess)));

        $loggedCount = 0;
        $invalidCount = 0;
        $errorsCount = 0;
        $processedResults = [];
        $webhookDupIndex = $this->buildWhatsAppLogsDuplicateIndex($pdo);

        $insertLogStmt = $pdo->prepare("
            INSERT INTO `whatsapp_leads_logs` (
                `full_name`, `phone_number`, `email`, `campaign_name`, `project_interest`, `source_page`,
                `city`, `country`, `address`,
                `which_apartment_size_are_you_interested_in`,
                `which_floor_are_you_for_shop`,
                `when_are_you_planning_to_buy`,
                `would_you_like_to_schedule_a_site_visit`,
                `when_is_the_best_time_to_contact_you`,
                `qa_json`, `raw_payload`,
                `sync_status`, `sync_note`, `matched_project_id`, `created_at`
            ) VALUES (
                :full_name, :phone_number, :email, :campaign_name, :project_interest, :source_page,
                :city, :country, :address,
                :which_apartment_size_are_you_interested_in,
                :which_floor_are_you_for_shop,
                :when_are_you_planning_to_buy,
                :would_you_like_to_schedule_a_site_visit,
                :when_is_the_best_time_to_contact_you,
                :qa_json, :raw_payload,
                :sync_status, :sync_note, :matched_project_id, NOW()
            )
        ");

        $duplicateSkippedCount = 0;
        foreach ($leadsToProcess as $idx => $item) {
            if (!is_array($item)) continue;

            // Use unified duplicate-checked ingestion into `whatsapp_leads_logs`
            try {
                $ingestRes = $this->ingestReplibeeItemToLogs($item, $pdo, $synchronizer, 'webhook', $webhookDupIndex);
                if ($ingestRes['action'] === 'inserted') {
                    $loggedCount++;
                } elseif ($ingestRes['action'] === 'invalid_logged') {
                    $invalidCount++;
                } elseif ($ingestRes['action'] === 'duplicate_in_logs') {
                    $duplicateSkippedCount++;
                }
                $processedResults[] = [
                    'index'              => $idx,
                    'status'             => $ingestRes['action'] === 'duplicate_in_logs' ? 'duplicate_skipped_in_logs' : ($ingestRes['action'] === 'invalid_logged' ? 'invalid_logged' : 'logged_pending_sync'),
                    'log_id'             => $ingestRes['log_id'],
                    'full_name'          => $ingestRes['full_name'],
                    'phone_number'       => $ingestRes['phone_number'],
                    'campaign_name'      => $ingestRes['campaign_name'],
                    'matched_project_id' => $ingestRes['matched_project_id'],
                ];
            } catch (Throwable $e) {
                $errorsCount++;
                $processedResults[] = [
                    'index'   => $idx,
                    'status'  => 'error',
                    'message' => 'Log Insert Error: ' . $e->getMessage(),
                ];
            }
        }

        $synchronizer->log(sprintf(
            "[Replibee Webhook] Finished: %d item(s) -> %d logged as pending, %d duplicate skipped, %d invalid, %d errors.",
            count($leadsToProcess),
            $loggedCount,
            $duplicateSkippedCount,
            $invalidCount,
            $errorsCount
        ));

        http_response_code($errorsCount > 0 && $loggedCount === 0 && $duplicateSkippedCount === 0 ? 400 : 200);
        echo json_encode([
            'status'                  => ($loggedCount > 0 || $duplicateSkippedCount > 0 || $invalidCount > 0) ? 'success' : 'error',
            'message'                 => sprintf("Logged %d WhatsApp lead(s) to staging table (`whatsapp_leads_logs`) ready for sync (%d duplicate skipped).", $loggedCount, $duplicateSkippedCount),
            'logged_count'            => $loggedCount,
            'duplicate_skipped_count' => $duplicateSkippedCount,
            'invalid_count'           => $invalidCount,
            'errors_count'            => $errorsCount,
            'results'                 => count($processedResults) === 1 ? $processedResults[0] : $processedResults
        ]);
        exit;
    }

    /**
     * Build an in-memory duplicate index of `whatsapp_leads_logs` so both Webhook and REST API Backup
     * never insert duplicate leads into `whatsapp_leads_logs`.
     */
    private function buildWhatsAppLogsDuplicateIndex(PDO $pdo): array
    {
        $index = [
            'by_uuid'           => [],
            'by_phone_project'  => [],
            'by_phone_campaign' => [],
            'by_email_project'  => [],
        ];

        try {
            $stmt = $pdo->query("
                SELECT
                    id, repibee_uuid, phone_number, email, campaign_name, matched_project_id,
                    which_apartment_size_are_you_interested_in,
                    which_floor_are_you_for_shop,
                    when_are_you_planning_to_buy,
                    would_you_like_to_schedule_a_site_visit,
                    when_is_the_best_time_to_contact_you,
                    qa_json
                FROM `whatsapp_leads_logs`
                ORDER BY id ASC
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $uuid = trim((string)($row['repibee_uuid'] ?? ''));
                if ($uuid !== '') {
                    $index['by_uuid'][$uuid] = $row;
                }

                $normP = $this->normalizeWhatsAppPhone($row['phone_number'] ?? '');
                $pId   = (int)($row['matched_project_id'] ?? 0);
                $camp  = strtolower(trim((string)($row['campaign_name'] ?? '')));

                if ($normP !== '') {
                    if ($pId > 0) {
                        $index['by_phone_project'][$normP . '_' . $pId] = $row;
                    }
                    if ($camp !== '') {
                        $index['by_phone_campaign'][$normP . '_' . $camp] = $row;
                    }
                }

                $em = strtolower(trim((string)($row['email'] ?? '')));
                if ($em !== '' && !in_array($em, ['1@gmail.com', 'a@b.com', 't@t.com', 'test@test.com'], true)) {
                    if ($pId > 0) {
                        $index['by_email_project'][$em . '_' . $pId] = $row;
                    }
                }
            }
        } catch (Throwable $t) {}

        return $index;
    }

    /**
     * Shared Replibee Lead Ingestion into `whatsapp_leads_logs` (used by both Webhook & REST API Backup).
     * Performs duplicate check against `whatsapp_leads_logs` before inserting, and enriches existing log rows
     * if new chatbot answers (`which_apartment_size_are_you_interested_in`, Q&A) arrived later.
     */
    private function ingestReplibeeItemToLogs(
        array $item,
        PDO $pdo,
        LeadSynchronizer $synchronizer,
        string $ingestSource,
        array &$dupIndex
    ): array {
        $contact        = is_array($item['contact'] ?? null) ? $item['contact'] : [];
        $source         = is_array($item['source'] ?? null) ? $item['source'] : [];
        $location       = is_array($item['location'] ?? null) ? $item['location'] : [];
        $interest       = is_array($item['interest'] ?? null) ? $item['interest'] : [];
        $automationData = is_array($item['automation_data'] ?? null) ? $item['automation_data'] : [];
        $customFields   = is_array($item['custom_fields'] ?? null) ? $item['custom_fields'] : [];
        $timestamps     = is_array($item['timestamps'] ?? null) ? $item['timestamps'] : [];

        // Extract unique Replibee lead UUID / ID
        $repibeeUuid = trim((string)($item['uuid'] ?? $item['id'] ?? $source['conversation_hash_id'] ?? ''));

        // Primary contact extraction
        $fullName = trim((string)($contact['name'] ?? $item['full_name'] ?? $item['name'] ?? $item['client_name'] ?? $item['customer_name'] ?? ''));
        $phone    = trim((string)($contact['phone'] ?? $contact['mobile'] ?? $contact['whatsapp'] ?? $item['phone_number'] ?? $item['phone'] ?? $item['mobile'] ?? $item['whatsapp'] ?? ''));
        $email    = trim((string)($contact['email'] ?? $item['email'] ?? ''));

        // Custom questions & chatbot Q&A extraction
        $whichApartmentSize   = $item['which_apartment_size_are_you_interested_in'] ?? null;
        $whichFloorForShop    = $item['which_floor_are_you_for_shop'] ?? null;
        $whenPlanningToBuy    = $item['when_are_you_planning_to_buy'] ?? null;
        $siteVisit            = $item['would_you_like_to_schedule_a_site_visit'] ?? null;
        $bestTimeContact      = $item['when_is_the_best_time_to_contact_you'] ?? null;
        $chatbotProjectAnswer = '';
        $qaPairs              = [];

        if (!empty($automationData)) {
            foreach ($automationData as $step) {
                $stepItems = (is_array($step) && isset($step['question'])) ? [$step] : (is_array($step) ? $step : []);
                foreach ($stepItems as $qa) {
                    if (!is_array($qa)) continue;
                    $qRaw = trim((string)($qa['question'] ?? $qa['label'] ?? $qa['key'] ?? ''));
                    $a    = trim((string)($qa['answer'] ?? $qa['value'] ?? ''));
                    if ($a === '') continue;

                    if ($qRaw !== '') {
                        $qaPairs[] = ['question' => $qRaw, 'answer' => $a];
                    }
                    $q = mb_strtolower($qRaw, 'UTF-8');

                    if ($phone === '') {
                        if (
                            str_contains($q, 'ফোন') || str_contains($q, 'নাম্বার') || str_contains($q, 'মোবাইল') ||
                            str_contains($q, 'নম্বর') || str_contains($q, 'phone') || str_contains($q, 'mobile') ||
                            str_contains($q, 'number') || str_contains($q, 'whatsapp')
                        ) {
                            $phone = $a;
                        } elseif (preg_match('/(?:\+?88)?01[3-9][\d\s\-]{8,11}/', $a, $mPhone)) {
                            $phone = $mPhone[0];
                        }
                    }

                    if ($fullName === '' && (str_contains($q, 'নাম') || str_contains($q, 'name')) && !str_contains($q, 'project') && !str_contains($q, 'প্রজেক্ট')) {
                        $fullName = $a;
                    }

                    if ($email === '' && (str_contains($q, 'ইমেইল') || str_contains($q, 'email') || filter_var($a, FILTER_VALIDATE_EMAIL))) {
                        if (filter_var($a, FILTER_VALIDATE_EMAIL)) {
                            $email = $a;
                        }
                    }

                    if ($chatbotProjectAnswer === '' && (str_contains($q, 'প্রজেক্ট') || str_contains($q, 'প্রোজেক্ট') || str_contains($q, 'project') || str_contains($q, 'property') || str_contains($q, 'লোকেশন') || str_contains($q, 'location'))) {
                        $chatbotProjectAnswer = $a;
                    }

                    if ((str_contains($q, 'সাইজ') || str_contains($q, 'size') || str_contains($q, 'apartment') || str_contains($q, 'shop') || str_contains($q, 'স্কয়ার') || str_contains($q, 'sft')) && empty($whichApartmentSize)) {
                        $whichApartmentSize = $a;
                    } elseif ((str_contains($q, 'ফ্লোর') || str_contains($q, 'floor')) && empty($whichFloorForShop)) {
                        $whichFloorForShop = $a;
                    } elseif ((str_contains($q, 'কেনার') || str_contains($q, 'পরিকল্পনা') || str_contains($q, 'planning') || str_contains($q, 'buy')) && empty($whenPlanningToBuy)) {
                        $whenPlanningToBuy = $a;
                    } elseif ((str_contains($q, 'ভিজিট') || str_contains($q, 'visit')) && empty($siteVisit)) {
                        $siteVisit = $a;
                    } elseif ((str_contains($q, 'যোগাযোগ') || str_contains($q, 'সময়') || str_contains($q, 'contact') || str_contains($q, 'time')) && empty($bestTimeContact)) {
                        $bestTimeContact = $a;
                    }
                }
            }
        }

        if (!empty($customFields)) {
            foreach ($customFields as $k => $v) {
                $kStr = trim((string)$k);
                $kLower = mb_strtolower($kStr, 'UTF-8');
                $vVal = is_string($v) ? trim($v) : (is_scalar($v) ? (string)$v : json_encode($v, JSON_UNESCAPED_UNICODE));
                if ($vVal === '') continue;

                $qaPairs[] = ['question' => $kStr, 'answer' => $vVal];

                if ($phone === '' && (str_contains($kLower, 'phone') || str_contains($kLower, 'mobile') || str_contains($kLower, 'number') || str_contains($kLower, 'whatsapp') || preg_match('/^(?:\+?88)?01[3-9]\d{8}$/', $vVal))) {
                    $phone = $vVal;
                }
                if ($fullName === '' && (str_contains($kLower, 'name') || str_contains($kLower, 'নাম')) && !str_contains($kLower, 'project')) {
                    $fullName = $vVal;
                }
                if ($chatbotProjectAnswer === '' && (str_contains($kLower, 'project') || str_contains($kLower, 'property') || str_contains($kLower, 'campaign'))) {
                    $chatbotProjectAnswer = $vVal;
                }

                if ((str_contains($kLower, 'size') || str_contains($kLower, 'apartment')) && empty($whichApartmentSize)) {
                    $whichApartmentSize = $vVal;
                } elseif (str_contains($kLower, 'floor') && empty($whichFloorForShop)) {
                    $whichFloorForShop = $vVal;
                } elseif (str_contains($kLower, 'planning') && empty($whenPlanningToBuy)) {
                    $whenPlanningToBuy = $vVal;
                } elseif (str_contains($kLower, 'visit') && empty($siteVisit)) {
                    $siteVisit = $vVal;
                } elseif (str_contains($kLower, 'contact') && empty($bestTimeContact)) {
                    $bestTimeContact = $vVal;
                }
            }
        }

        $cleanPhone = $this->normalizeWhatsAppPhone($phone);

        // Campaign name & interest products resolution
        $interestProducts = '';
        if (!empty($interest['products'])) {
            $interestProducts = is_array($interest['products']) ? implode(', ', $interest['products']) : trim((string)$interest['products']);
        }
        $brandName  = trim((string)($interest['brand_name'] ?? $interest['model'] ?? ''));
        $sourcePage = trim((string)($source['page'] ?? $source['social_profile_name'] ?? $source['source'] ?? 'Replibee WhatsApp'));
        // Clean phone suffix from page name (e.g. "The Nature at Cox’s Bazar (+880 1341-663333)" -> "The Nature at Cox’s Bazar")
        $cleanPageTitle = trim((string)preg_replace('/\s*\(\+?\d[\d\s\-]+\)\s*$/u', '', $sourcePage));
        $cleanPageTitle = trim((string)preg_replace('/^WhatsApp\s*[·\-\|]\s*/iu', '', $cleanPageTitle));

        $projectInterestParts = array_values(array_unique(array_filter([
            $interestProducts,
            $brandName,
            $chatbotProjectAnswer,
            trim((string)($item['project_name'] ?? $item['project'] ?? '')),
            trim((string)($item['campaign_name'] ?? $item['campaign'] ?? '')),
        ], fn($v) => $v !== '')));
        $projectInterest = !empty($projectInterestParts) ? implode(' | ', $projectInterestParts) : null;

        $campaign = '';
        foreach ([
            $item['campaign_name'] ?? '',
            $interestProducts,
            $brandName,
            $chatbotProjectAnswer,
            $item['project_name'] ?? '',
            $item['campaign'] ?? '',
            $source['campaign'] ?? '',
            $cleanPageTitle,
            $sourcePage,
        ] as $candCamp) {
            $candCamp = trim((string)$candCamp);
            if ($candCamp !== '') {
                $campaign = $candCamp;
                break;
            }
        }
        if ($campaign === '') {
            $campaign = 'WhatsApp Lead';
        }

        // Match project for preview & accurate project-level duplicate detection
        $secondaryContext = trim(implode(' ', array_filter([
            $projectInterest ?? '',
            $cleanPageTitle,
            $sourcePage,
            implode(' ', array_map(fn($p) => $p['answer'] ?? '', $qaPairs))
        ])));
        $projectMatch = $synchronizer->matchProject($campaign, $secondaryContext);
        $matchedProjectId = !empty($projectMatch['project_id']) ? (int)$projectMatch['project_id'] : null;

        if (!empty($projectMatch['project_name']) && $matchedProjectId !== null) {
            $campLowerCheck = strtolower($campaign);
            if ($campLowerCheck === '' || $campLowerCheck === 'whatsapp lead' || $campLowerCheck === 'the premium homes ltd' || $campLowerCheck === 'the premium homes ltd.') {
                $campaign = $projectMatch['project_name'] . ' (WhatsApp)';
            }
        }
        $campLower = strtolower($campaign);
        $emailLower = strtolower($email);

        // Parse created_at timestamp (convert to Asia/Dhaka +06:00 if ISO timestamp provided)
        $rawCreatedAt = trim((string)($timestamps['created_at'] ?? $item['created_at'] ?? ''));
        $createdAtLocal = date('Y-m-d H:i:s');
        if ($rawCreatedAt !== '') {
            $ts = strtotime($rawCreatedAt);
            if ($ts !== false && $ts > 0) {
                $dtObj = new DateTime('@' . $ts);
                $dtObj->setTimezone(new DateTimeZone('Asia/Dhaka'));
                $createdAtLocal = $dtObj->format('Y-m-d H:i:s');
            }
        }

        // DUPLICATE CHECK AGAINST `whatsapp_leads_logs`
        $existingLog = null;
        if ($repibeeUuid !== '' && isset($dupIndex['by_uuid'][$repibeeUuid])) {
            $existingLog = $dupIndex['by_uuid'][$repibeeUuid];
        }
        if ($existingLog === null && $cleanPhone !== '') {
            if ($matchedProjectId !== null && $matchedProjectId > 0 && isset($dupIndex['by_phone_project'][$cleanPhone . '_' . $matchedProjectId])) {
                $existingLog = $dupIndex['by_phone_project'][$cleanPhone . '_' . $matchedProjectId];
            } elseif (($matchedProjectId === null || $matchedProjectId === 0) && $campLower !== '' && isset($dupIndex['by_phone_campaign'][$cleanPhone . '_' . $campLower])) {
                $existingLog = $dupIndex['by_phone_campaign'][$cleanPhone . '_' . $campLower];
            }
        }
        if ($existingLog === null && $emailLower !== '' && !in_array($emailLower, ['1@gmail.com', 'a@b.com', 't@t.com', 'test@test.com'], true)) {
            if ($matchedProjectId !== null && $matchedProjectId > 0 && isset($dupIndex['by_email_project'][$emailLower . '_' . $matchedProjectId])) {
                $existingLog = $dupIndex['by_email_project'][$emailLower . '_' . $matchedProjectId];
            }
        }

        if ($existingLog !== null) {
            $existingLogId = (int)$existingLog['id'];
            $enrichUpdates = [];
            $enrichParams  = [];

            if ($repibeeUuid !== '' && empty($existingLog['repibee_uuid'])) {
                $enrichUpdates[] = "`repibee_uuid` = ?";
                $enrichParams[]  = $repibeeUuid;
                $existingLog['repibee_uuid'] = $repibeeUuid;
            }
            $qMap = [
                'which_apartment_size_are_you_interested_in' => $whichApartmentSize,
                'which_floor_are_you_for_shop'               => $whichFloorForShop,
                'when_are_you_planning_to_buy'               => $whenPlanningToBuy,
                'would_you_like_to_schedule_a_site_visit'   => $siteVisit,
                'when_is_the_best_time_to_contact_you'       => $bestTimeContact,
            ];
            foreach ($qMap as $col => $newVal) {
                $newValStr = trim((string)($newVal ?? ''));
                $oldValStr = trim((string)($existingLog[$col] ?? ''));
                if ($newValStr !== '' && $oldValStr === '') {
                    $enrichUpdates[] = "`{$col}` = ?";
                    $enrichParams[]  = $newValStr;
                    $existingLog[$col] = $newValStr;
                }
            }
            if (!empty($qaPairs) && empty($existingLog['qa_json'])) {
                $qaJsonStr = json_encode($qaPairs, JSON_UNESCAPED_UNICODE);
                $enrichUpdates[] = "`qa_json` = ?";
                $enrichParams[]  = $qaJsonStr;
                $existingLog['qa_json'] = $qaJsonStr;
            }

            $wasEnriched = false;
            if (!empty($enrichUpdates)) {
                $enrichParams[] = $existingLogId;
                try {
                    $uStmt = $pdo->prepare("UPDATE `whatsapp_leads_logs` SET " . implode(', ', $enrichUpdates) . " WHERE `id` = ?");
                    $uStmt->execute($enrichParams);
                    $wasEnriched = true;
                } catch (Throwable $t) {}
            }

            return [
                'action'             => 'duplicate_in_logs',
                'enriched'           => $wasEnriched,
                'log_id'             => $existingLogId,
                'full_name'          => $fullName,
                'phone_number'       => $cleanPhone,
                'campaign_name'      => $campaign,
                'matched_project_id' => $matchedProjectId,
            ];
        }

        // Not in `whatsapp_leads_logs` -> Insert new staging log row
        $isInvalid  = ($cleanPhone === '' && $fullName === '' && $email === '');
        $syncStatus = $isInvalid ? 'invalid' : 'pending';
        $sourceLabel = ($ingestSource === 'rest_api') ? 'Replibee REST API Backup' : 'Replibee Webhook';
        $syncNote   = $isInvalid
            ? "Missing phone_number, full_name, and email in {$sourceLabel}."
            : "Received via {$sourceLabel} · Ready to sync";

        $insertLogStmt = $pdo->prepare("
            INSERT INTO `whatsapp_leads_logs` (
                `repibee_uuid`, `ingest_source`,
                `full_name`, `phone_number`, `email`, `campaign_name`, `project_interest`, `source_page`,
                `city`, `country`, `address`,
                `which_apartment_size_are_you_interested_in`,
                `which_floor_are_you_for_shop`,
                `when_are_you_planning_to_buy`,
                `would_you_like_to_schedule_a_site_visit`,
                `when_is_the_best_time_to_contact_you`,
                `qa_json`, `raw_payload`,
                `sync_status`, `sync_note`, `matched_project_id`, `created_at`
            ) VALUES (
                :repibee_uuid, :ingest_source,
                :full_name, :phone_number, :email, :campaign_name, :project_interest, :source_page,
                :city, :country, :address,
                :which_apartment_size_are_you_interested_in,
                :which_floor_are_you_for_shop,
                :when_are_you_planning_to_buy,
                :would_you_like_to_schedule_a_site_visit,
                :when_is_the_best_time_to_contact_you,
                :qa_json, :raw_payload,
                :sync_status, :sync_note, :matched_project_id, :created_at
            )
        ");

        $qaJsonVal = !empty($qaPairs) ? json_encode($qaPairs, JSON_UNESCAPED_UNICODE) : null;
        $insertLogStmt->execute([
            ':repibee_uuid'                              => $repibeeUuid !== '' ? $repibeeUuid : null,
            ':ingest_source'                             => $ingestSource,
            ':full_name'                                => $fullName !== '' ? $fullName : 'WhatsApp Lead',
            ':phone_number'                             => $cleanPhone !== '' ? $cleanPhone : ($phone !== '' ? $phone : null),
            ':email'                                    => $email !== '' ? $email : null,
            ':campaign_name'                            => $campaign,
            ':project_interest'                         => $projectInterest ?? $campaign,
            ':source_page'                              => $sourcePage,
            ':city'                                     => $location['city'] ?? $location['area'] ?? $item['city'] ?? null,
            ':country'                                  => $location['country'] ?? $item['country'] ?? 'Bangladesh',
            ':address'                                  => $location['address'] ?? $item['address'] ?? null,
            ':which_apartment_size_are_you_interested_in' => $whichApartmentSize,
            ':which_floor_are_you_for_shop'             => $whichFloorForShop,
            ':when_are_you_planning_to_buy'             => $whenPlanningToBuy,
            ':would_you_like_to_schedule_a_site_visit' => $siteVisit,
            ':when_is_the_best_time_to_contact_you'     => $bestTimeContact,
            ':qa_json'                                  => $qaJsonVal,
            ':raw_payload'                              => json_encode($item, JSON_UNESCAPED_UNICODE),
            ':sync_status'                              => $syncStatus,
            ':sync_note'                                => $syncNote,
            ':matched_project_id'                       => $matchedProjectId,
            ':created_at'                               => $createdAtLocal,
        ]);

        $logId = (int)$pdo->lastInsertId();

        // Update in-memory duplicate index so duplicates within the same batch are caught
        $newRowRef = [
            'id'                                         => $logId,
            'repibee_uuid'                               => $repibeeUuid,
            'phone_number'                               => $cleanPhone,
            'email'                                      => $emailLower,
            'campaign_name'                              => $campaign,
            'matched_project_id'                         => $matchedProjectId,
            'which_apartment_size_are_you_interested_in' => $whichApartmentSize,
            'which_floor_are_you_for_shop'               => $whichFloorForShop,
            'when_are_you_planning_to_buy'               => $whenPlanningToBuy,
            'would_you_like_to_schedule_a_site_visit'   => $siteVisit,
            'when_is_the_best_time_to_contact_you'       => $bestTimeContact,
            'qa_json'                                    => $qaJsonVal,
        ];
        if ($repibeeUuid !== '') {
            $dupIndex['by_uuid'][$repibeeUuid] = $newRowRef;
        }
        if ($cleanPhone !== '') {
            if ($matchedProjectId !== null && $matchedProjectId > 0) {
                $dupIndex['by_phone_project'][$cleanPhone . '_' . $matchedProjectId] = $newRowRef;
            }
            if ($campLower !== '') {
                $dupIndex['by_phone_campaign'][$cleanPhone . '_' . $campLower] = $newRowRef;
            }
        }
        if ($emailLower !== '' && !in_array($emailLower, ['1@gmail.com', 'a@b.com', 't@t.com', 'test@test.com'], true)) {
            if ($matchedProjectId !== null && $matchedProjectId > 0) {
                $dupIndex['by_email_project'][$emailLower . '_' . $matchedProjectId] = $newRowRef;
            }
        }

        return [
            'action'             => $isInvalid ? 'invalid_logged' : 'inserted',
            'log_id'             => $logId,
            'full_name'          => $fullName,
            'phone_number'       => $cleanPhone,
            'campaign_name'      => $campaign,
            'matched_project_id' => $matchedProjectId,
        ];
    }

    /**
     * Pull leads from Replibee Merchant REST API (`GET https://replibee.com/api/v1/merchant/leads`)
     * and store any missing leads into `whatsapp_leads_logs` with duplicate checking.
     */
    private function pullReplibeeLeadsFromRestApi(
        PDO $pdo,
        LeadSynchronizer $synchronizer,
        string $dateFilter = 'today',
        int $maxPages = 3
    ): array {
        $repibeeConfig = $this->automationConfig['repibee'] ?? [];
        $baseUrl       = trim((string)($repibeeConfig['merchant_api_url'] ?? 'https://replibee.com/api/v1/merchant/leads'));
        $apiKey        = trim((string)($repibeeConfig['merchant_api_key'] ?? ''));
        $brandId       = trim((string)($repibeeConfig['merchant_brand_id'] ?? ''));

        $stats = [
            'fetched_from_api' => 0,
            'newly_logged'     => 0,
            'already_in_logs'  => 0,
            'enriched_logs'    => 0,
            'invalid_logged'   => 0,
            'skipped_old_date' => 0,
            'api_error'        => null,
        ];

        if ($apiKey === '' || $brandId === '') {
            $stats['api_error'] = 'Missing Replibee merchant_api_key or merchant_brand_id in config.';
            return $stats;
        }

        $tzDhaka = new DateTimeZone('Asia/Dhaka');
        $nowDhaka = new DateTime('now', $tzDhaka);
        $todayStr = $nowDhaka->format('Y-m-d');
        $yesterdayStr = (clone $nowDhaka)->modify('-1 day')->format('Y-m-d');
        $sevenDaysStr = (clone $nowDhaka)->modify('-6 days')->format('Y-m-d');
        $thirtyDaysStr = (clone $nowDhaka)->modify('-30 days')->format('Y-m-d');

        $dateFilter = strtolower(trim($dateFilter));
        if ($dateFilter === 'today') {
            $cutoffStart = $todayStr;
            $cutoffEnd   = $todayStr;
        } elseif ($dateFilter === 'yesterday') {
            $cutoffStart = $yesterdayStr;
            $cutoffEnd   = $yesterdayStr;
        } elseif ($dateFilter === '7days' || $dateFilter === 'this_week') {
            $cutoffStart = $sevenDaysStr;
            $cutoffEnd   = $todayStr;
        } else {
            // 'all' or 'recent' -> pull up to last 30 days within $maxPages
            $cutoffStart = $thirtyDaysStr;
            $cutoffEnd   = $todayStr;
        }

        $dupIndex = $this->buildWhatsAppLogsDuplicateIndex($pdo);

        for ($page = 1; $page <= $maxPages; $page++) {
            $sep = str_contains($baseUrl, '?') ? '&' : '?';
            $url = $baseUrl . $sep . 'per_page=100&page=' . $page;

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 25,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER     => [
                    'Accept: application/json',
                    'X-API-Key: ' . $apiKey,
                    'X-Brand-Id: ' . $brandId,
                    'User-Agent: TPHL-ERP-LeadSync/1.0',
                ],
            ]);
            $rawResponse = curl_exec($ch);
            $httpCode    = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr     = curl_error($ch);
            curl_close($ch);

            if ($rawResponse === false || $httpCode !== 200) {
                $errBody = is_string($rawResponse) ? substr($rawResponse, 0, 200) : '';
                $stats['api_error'] = $curlErr ?: "Replibee API returned HTTP {$httpCode}: {$errBody}";
                $synchronizer->log("[Replibee REST API] Pull warning on page {$page}: " . $stats['api_error']);
                break;
            }

            $decoded = json_decode($rawResponse, true);
            $items   = is_array($decoded['data'] ?? null) ? $decoded['data'] : [];
            if (empty($items)) {
                break;
            }

            $olderItemsOnPage = 0;
            foreach ($items as $item) {
                if (!is_array($item)) continue;
                $stats['fetched_from_api']++;

                // Check lead created_at date in Asia/Dhaka timezone
                $rawCreated = trim((string)($item['timestamps']['created_at'] ?? $item['created_at'] ?? ''));
                $itemDateStr = $todayStr;
                if ($rawCreated !== '') {
                    $ts = strtotime($rawCreated);
                    if ($ts !== false && $ts > 0) {
                        $dt = new DateTime('@' . $ts);
                        $dt->setTimezone($tzDhaka);
                        $itemDateStr = $dt->format('Y-m-d');
                    }
                }

                if ($itemDateStr > $cutoffEnd) {
                    continue;
                }
                if ($itemDateStr < $cutoffStart) {
                    $stats['skipped_old_date']++;
                    $olderItemsOnPage++;
                    continue;
                }

                $res = $this->ingestReplibeeItemToLogs($item, $pdo, $synchronizer, 'rest_api', $dupIndex);
                if ($res['action'] === 'inserted') {
                    $stats['newly_logged']++;
                } elseif ($res['action'] === 'invalid_logged') {
                    $stats['invalid_logged']++;
                } elseif ($res['action'] === 'duplicate_in_logs') {
                    $stats['already_in_logs']++;
                    if (!empty($res['enriched'])) {
                        $stats['enriched_logs']++;
                    }
                }
            }

            $lastPage = (int)($decoded['meta']['last_page'] ?? 1);
            // Replibee returns leads newest-first; if we already hit leads older than cutoffStart, stop paging
            if ($page >= $lastPage || $olderItemsOnPage >= 15) {
                break;
            }
        }

        $synchronizer->log(sprintf(
            "[Replibee REST API Backup] Pull (%s): %d fetched -> %d newly logged to whatsapp_leads_logs, %d already in logs (%d enriched), %d older skipped.",
            $dateFilter,
            $stats['fetched_from_api'],
            $stats['newly_logged'],
            $stats['already_in_logs'],
            $stats['enriched_logs'],
            $stats['skipped_old_date']
        ));

        return $stats;
    }

    /**
     * AJAX/API: Pull leads from Replibee Merchant REST API into `whatsapp_leads_logs` with duplicate checking
     */
    public function ajaxFetchReplibeeApiLogs()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $isCronOrApi = (!empty($_GET['api_key']) || !empty($_SERVER['HTTP_X_API_KEY']));
        if (!$isCronOrApi && empty($_SESSION['user_id'])) {
            $this->checkAccess();
        }

        header('Content-Type: application/json; charset=utf-8');
        set_time_limit(120);

        try {
            $currentUserId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
            $synchronizer  = new LeadSynchronizer($this->automationConfig, $currentUserId);
            $pdo           = $synchronizer->getDb();
            $synchronizer->ensureSchema($pdo);

            $input      = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $dateFilter = strtolower(trim((string)($input['date_filter'] ?? $_GET['date_filter'] ?? 'today')));
            if ($dateFilter === '' || $dateFilter === 'custom') {
                $dateFilter = '7days';
            }
            $maxPages = max(1, min(10, (int)($input['max_pages'] ?? $_GET['max_pages'] ?? ($dateFilter === 'today' ? 2 : 4))));

            $apiStats = $this->pullReplibeeLeadsFromRestApi($pdo, $synchronizer, $dateFilter, $maxPages);

            $globalPending = (int)$pdo->query("SELECT COUNT(*) FROM `whatsapp_leads_logs` WHERE `sync_status` = 'pending'")->fetchColumn();
            $globalTotal   = (int)$pdo->query("SELECT COUNT(*) FROM `whatsapp_leads_logs`")->fetchColumn();

            if (!empty($apiStats['api_error']) && $apiStats['fetched_from_api'] === 0) {
                http_response_code(502);
                echo json_encode([
                    'status'         => 'error',
                    'message'        => 'Replibee REST API Error: ' . $apiStats['api_error'],
                    'api_stats'      => $apiStats,
                    'global_pending' => $globalPending,
                    'global_total'   => $globalTotal,
                ]);
                exit;
            }

            $enrichMsg = $apiStats['enriched_logs'] > 0 ? " ({$apiStats['enriched_logs']} existing log(s) enriched with new Q&A)" : "";
            echo json_encode([
                'status'         => 'success',
                'message'        => sprintf(
                    "Replibee REST API Pull Completed! %d new lead(s) added to staging logs, %d duplicate(s) already in logs skipped%s.",
                    $apiStats['newly_logged'],
                    $apiStats['already_in_logs'],
                    $enrichMsg
                ),
                'api_stats'      => $apiStats,
                'global_pending' => $globalPending,
                'global_total'   => $globalTotal,
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Replibee REST API Pull Failed: ' . $e->getMessage(),
            ]);
        }
        exit;
    }

    /**
     * AJAX: Fetch WhatsApp / Replibee Leads from `whatsapp_leads_logs` for the 50vw Right-to-Left Drawer
     */
    public function ajaxGetWhatsAppLogs()
    {
        $this->checkAccess();
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        try {
            $synchronizer = new LeadSynchronizer($this->automationConfig);
            $pdo = $synchronizer->getDb();
            $synchronizer->ensureSchema($pdo);

            $statusFilter = strtolower(trim((string)($_GET['sync_status'] ?? 'all')));
            $dateFilter   = strtolower(trim((string)($_GET['date_filter'] ?? 'all')));
            $startDate    = trim((string)($_GET['start_date'] ?? ''));
            $endDate      = trim((string)($_GET['end_date'] ?? ''));
            $search       = trim((string)($_GET['search'] ?? ''));
            $page         = max(1, (int)($_GET['page'] ?? 1));
            $limitRaw     = $_GET['limit'] ?? 50;
            $limit        = ($limitRaw === 'all') ? 2000 : max(10, min(500, (int)$limitRaw));
            $offset       = ($page - 1) * $limit;

            // Build Date WHERE clause (shared between KPI summary and list)
            $dateWhere = [];
            $dateParams = [];
            $today = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $sevenDaysAgo = date('Y-m-d', strtotime('-6 days'));

            if ($dateFilter === 'today') {
                $dateWhere[] = "DATE(w.created_at) = ?";
                $dateParams[] = $today;
            } elseif ($dateFilter === 'yesterday') {
                $dateWhere[] = "DATE(w.created_at) = ?";
                $dateParams[] = $yesterday;
            } elseif ($dateFilter === '7days' || $dateFilter === 'this_week') {
                $dateWhere[] = "DATE(w.created_at) BETWEEN ? AND ?";
                $dateParams[] = $sevenDaysAgo;
                $dateParams[] = $today;
            } elseif ($dateFilter === 'custom') {
                if ($startDate !== '' && $endDate !== '') {
                    $dateWhere[] = "DATE(w.created_at) BETWEEN ? AND ?";
                    $dateParams[] = $startDate;
                    $dateParams[] = $endDate;
                } elseif ($startDate !== '') {
                    $dateWhere[] = "DATE(w.created_at) >= ?";
                    $dateParams[] = $startDate;
                } elseif ($endDate !== '') {
                    $dateWhere[] = "DATE(w.created_at) <= ?";
                    $dateParams[] = $endDate;
                }
            }

            $dateSql = !empty($dateWhere) ? ('WHERE ' . implode(' AND ', $dateWhere)) : '';

            // 1. KPI counts for the selected date filter
            $kpiSql = "
                SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN w.sync_status = 'pending' THEN 1 ELSE 0 END) AS pending,
                    SUM(CASE WHEN w.sync_status = 'synced' THEN 1 ELSE 0 END) AS synced,
                    SUM(CASE WHEN w.sync_status = 'duplicate_skipped' THEN 1 ELSE 0 END) AS duplicate_skipped,
                    SUM(CASE WHEN w.sync_status = 'invalid' THEN 1 ELSE 0 END) AS invalid
                FROM `whatsapp_leads_logs` w
                {$dateSql}
            ";
            $kpiStmt = $pdo->prepare($kpiSql);
            $kpiStmt->execute($dateParams);
            $kpiRow = $kpiStmt->fetch(PDO::FETCH_ASSOC) ?: [];

            // Also get global all-time pending count for the top button badge
            $globalPending = (int)$pdo->query("SELECT COUNT(*) FROM `whatsapp_leads_logs` WHERE `sync_status` = 'pending'")->fetchColumn();
            $globalTotal   = (int)$pdo->query("SELECT COUNT(*) FROM `whatsapp_leads_logs`")->fetchColumn();

            // 2. Build full list WHERE clause
            $listWhere = $dateWhere;
            $listParams = $dateParams;

            if ($statusFilter !== '' && $statusFilter !== 'all') {
                $listWhere[] = "w.sync_status = ?";
                $listParams[] = $statusFilter;
            }

            if ($search !== '') {
                $like = '%' . $search . '%';
                $listWhere[] = "(w.full_name LIKE ? OR w.phone_number LIKE ? OR w.email LIKE ? OR w.campaign_name LIKE ? OR w.project_interest LIKE ? OR w.source_page LIKE ? OR w.qa_json LIKE ?)";
                array_push($listParams, $like, $like, $like, $like, $like, $like, $like);
            }

            $whereSql = !empty($listWhere) ? ('WHERE ' . implode(' AND ', $listWhere)) : '';

            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM `whatsapp_leads_logs` w {$whereSql}");
            $countStmt->execute($listParams);
            $filteredTotal = (int)$countStmt->fetchColumn();

            $sql = "
                SELECT
                    w.*,
                    COALESCE(p.name, p2.name) AS project_name,
                    COALESCE(t.name, t2.name) AS team_name,
                    l.distribute AS lead_distribute
                FROM `whatsapp_leads_logs` w
                LEFT JOIN `leads` l ON w.synced_lead_id = l.id AND l.deleted_at IS NULL
                LEFT JOIN `projects` p ON l.project_id = p.id
                LEFT JOIN `projects` p2 ON w.matched_project_id = p2.id
                LEFT JOIN `teams` t ON l.team_id = t.id
                LEFT JOIN `teams` t2 ON w.assigned_team_id = t2.id
                {$whereSql}
                ORDER BY
                    (CASE WHEN w.sync_status = 'pending' THEN 0 ELSE 1 END) ASC,
                    w.id DESC
                LIMIT {$limit} OFFSET {$offset}
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($listParams);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            foreach ($rows as &$r) {
                $r['id'] = (int)$r['id'];
                $r['synced_lead_id'] = !empty($r['synced_lead_id']) ? (int)$r['synced_lead_id'] : null;
                $r['matched_project_id'] = !empty($r['matched_project_id']) ? (int)$r['matched_project_id'] : null;
                $r['assigned_team_id'] = !empty($r['assigned_team_id']) ? (int)$r['assigned_team_id'] : null;
                $r['qa_parsed'] = !empty($r['qa_json']) ? (json_decode($r['qa_json'], true) ?: []) : [];
            }
            unset($r);

            echo json_encode([
                'status' => 'success',
                'stats'  => [
                    'total'             => (int)($kpiRow['total'] ?? 0),
                    'pending'           => (int)($kpiRow['pending'] ?? 0),
                    'synced'            => (int)($kpiRow['synced'] ?? 0),
                    'duplicate_skipped' => (int)($kpiRow['duplicate_skipped'] ?? 0),
                    'invalid'           => (int)($kpiRow['invalid'] ?? 0),
                    'global_pending'    => $globalPending,
                    'global_total'      => $globalTotal,
                ],
                'pagination' => [
                    'total' => $filteredTotal,
                    'page'  => $page,
                    'limit' => $limit,
                    'pages' => $limit > 0 ? (int)ceil($filteredTotal / $limit) : 1,
                ],
                'logs' => $rows,
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Failed to load WhatsApp logs: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * AJAX/API: Sync WhatsApp Leads from `whatsapp_leads_logs` staging table into the main `leads` table
     * Performs smart duplicate detection (by normalized phone + project/campaign), matches project via dynamic rules,
     * assigns active sales team via Round-Robin, and updates `whatsapp_leads_logs.sync_status`.
     */
    public function ajaxSyncWhatsAppLeads()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $isCronOrApi = (!empty($_GET['api_key']) || !empty($_SERVER['HTTP_X_API_KEY']));
        if (!$isCronOrApi && empty($_SESSION['user_id'])) {
            $this->checkAccess();
        }

        header('Content-Type: application/json; charset=utf-8');
        set_time_limit(180);

        try {
            $currentUserId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
            $synchronizer = new LeadSynchronizer($this->automationConfig, $currentUserId);
            $pdo = $synchronizer->getDb();
            $synchronizer->ensureSchema($pdo);

            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $specificLogIds = !empty($input['log_ids']) && is_array($input['log_ids'])
                ? array_values(array_filter(array_map('intval', $input['log_ids']), fn($id) => $id > 0))
                : [];
            $forceDuplicate = !empty($input['force_duplicate']);
            $retrySkipped   = !empty($input['retry_skipped']);

            // 0. Backup Plan: Automatically pull Today's leads from Replibee REST API into `whatsapp_leads_logs`
            // (with duplicate check) before syncing to `leads`, unless syncing specific row IDs or skip_api_pull=1.
            $apiPullStats = null;
            if (empty($specificLogIds) && empty($input['skip_api_pull'])) {
                try {
                    $apiPullDateFilter = strtolower(trim((string)($input['api_date_filter'] ?? 'today')));
                    $apiPullStats = $this->pullReplibeeLeadsFromRestApi($pdo, $synchronizer, $apiPullDateFilter ?: 'today', 2);
                } catch (Throwable $apiEx) {
                    $synchronizer->log("[WhatsApp Leads Sync] REST API backup pull warning: " . $apiEx->getMessage());
                }
            }

            // 1. Fetch candidate log rows from `whatsapp_leads_logs`
            if (!empty($specificLogIds)) {
                $placeholders = implode(',', array_fill(0, count($specificLogIds), '?'));
                $logStmt = $pdo->prepare("SELECT * FROM `whatsapp_leads_logs` WHERE `id` IN ({$placeholders}) ORDER BY `id` ASC");
                $logStmt->execute($specificLogIds);
            } elseif ($retrySkipped) {
                $logStmt = $pdo->query("SELECT * FROM `whatsapp_leads_logs` WHERE `sync_status` IN ('pending', 'duplicate_skipped') ORDER BY `id` ASC");
            } else {
                $logStmt = $pdo->query("SELECT * FROM `whatsapp_leads_logs` WHERE `sync_status` = 'pending' ORDER BY `id` ASC");
            }
            $logsToSync = $logStmt ? $logStmt->fetchAll(PDO::FETCH_ASSOC) : [];

            $totalCandidate = count($logsToSync);
            if ($totalCandidate === 0) {
                $globalPending = (int)$pdo->query("SELECT COUNT(*) FROM `whatsapp_leads_logs` WHERE `sync_status` = 'pending'")->fetchColumn();
                $globalTotal   = (int)$pdo->query("SELECT COUNT(*) FROM `whatsapp_leads_logs`")->fetchColumn();
                echo json_encode([
                    'status'             => 'success',
                    'message'            => 'No pending WhatsApp leads in logs table to sync. All caught up!',
                    'inserted_count'     => 0,
                    'skipped_duplicates' => 0,
                    'invalid_count'      => 0,
                    'total_processed'    => 0,
                    'global_pending'     => $globalPending,
                    'global_total'       => $globalTotal,
                'api_pull_stats'     => $apiPullStats,
                ]);
                exit;
            }

            // 2. Pre-index existing non-deleted leads in `leads` for fast, accurate duplicate checking
            // Rule: A lead is duplicate ONLY if the same customer (normalized phone or valid email)
            // already exists for the SAME project_id (when project_id > 0) OR the SAME non-empty campaign_name (when project_id is null/0).
            // Never match blank phones or NULL campaign_name!
            $existingPhoneByProject  = [];
            $existingPhoneByCampaign = [];
            $existingEmailByProject  = [];

            $dupStmt = $pdo->query("SELECT id, project_id, campaign_name, phone_number, phone, email FROM `leads` WHERE deleted_at IS NULL");
            while ($row = $dupStmt->fetch(PDO::FETCH_ASSOC)) {
                $leadId = (int)$row['id'];
                $pId = (int)($row['project_id'] ?? 0);
                $campLower = strtolower(trim((string)($row['campaign_name'] ?? '')));

                foreach (['phone_number', 'phone'] as $f) {
                    $normP = $this->normalizeWhatsAppPhone($row[$f] ?? '');
                    if ($normP !== '') {
                        if ($pId > 0) {
                            $existingPhoneByProject[$normP . '_' . $pId] = $leadId;
                        }
                        if ($campLower !== '') {
                            $existingPhoneByCampaign[$normP . '_' . $campLower] = $leadId;
                        }
                    }
                }

                $em = strtolower(trim((string)($row['email'] ?? '')));
                if ($em !== '' && !in_array($em, ['1@gmail.com', 'a@b.com', 't@t.com', 'test@test.com'], true)) {
                    if ($pId > 0) {
                        $existingEmailByProject[$em . '_' . $pId] = $leadId;
                    }
                }
            }

            $insertedCount = 0;
            $skippedDuplicates = 0;
            $invalidCount = 0;

            $updateLogStmt = $pdo->prepare("
                UPDATE `whatsapp_leads_logs`
                SET
                    `phone_number`       = :phone_number,
                    `campaign_name`      = :campaign_name,
                    `sync_status`        = :sync_status,
                    `sync_note`          = :sync_note,
                    `synced_lead_id`     = :synced_lead_id,
                    `matched_project_id` = :matched_project_id,
                    `assigned_team_id`   = :assigned_team_id,
                    `synced_at`          = NOW()
                WHERE `id` = :id
            ");

            foreach ($logsToSync as $logRow) {
                $logId = (int)$logRow['id'];

                // If already synced and not explicitly forced, skip re-inserting
                if (($logRow['sync_status'] ?? '') === 'synced' && !empty($logRow['synced_lead_id']) && !$forceDuplicate) {
                    continue;
                }

                $fullName = trim((string)($logRow['full_name'] ?? ''));
                $rawPhone = trim((string)($logRow['phone_number'] ?? ''));
                $normPhone = $this->normalizeWhatsAppPhone($rawPhone);
                $email = strtolower(trim((string)($logRow['email'] ?? '')));
                $campaign = trim((string)($logRow['campaign_name'] ?? ''));
                $projectInterest = trim((string)($logRow['project_interest'] ?? ''));
                $sourcePage = trim((string)($logRow['source_page'] ?? ''));

                // If phone or project was inside qa_json/raw_payload and not extracted earlier, try extracting now
                if ($normPhone === '' && !empty($logRow['raw_payload'])) {
                    if (preg_match('/(?:\+?88)?01[3-9]\d{8}/', (string)$logRow['raw_payload'], $m)) {
                        $normPhone = $this->normalizeWhatsAppPhone($m[0]);
                    }
                }

                if ($normPhone === '' && $email === '' && ($fullName === '' || $fullName === 'WhatsApp Lead')) {
                    $invalidCount++;
                    $updateLogStmt->execute([
                        ':phone_number'       => $rawPhone ?: null,
                        ':campaign_name'      => $campaign ?: 'WhatsApp Lead',
                        ':sync_status'        => 'invalid',
                        ':sync_note'          => 'Skipped: Missing valid phone number and customer name.',
                        ':synced_lead_id'     => null,
                        ':matched_project_id' => $logRow['matched_project_id'] ?: null,
                        ':assigned_team_id'   => null,
                        ':id'                 => $logId,
                    ]);
                    continue;
                }

                // Match Project & Eligible Active Teams via LeadSynchronizer
                $secondaryText = trim($projectInterest . ' ' . $sourcePage . ' ' . ($logRow['qa_json'] ?? ''));
                $projectMatch = $synchronizer->matchProject($campaign, $secondaryText);
                $projectId = !empty($projectMatch['project_id']) ? (int)$projectMatch['project_id'] : null;

                // If campaign was generic ("The Premium Homes Ltd" or "WhatsApp Lead") and a project matched, enrich campaign_name
                if (!empty($projectMatch['project_name']) && $projectId !== null) {
                    $campLowerCheck = strtolower($campaign);
                    if ($campLowerCheck === '' || $campLowerCheck === 'whatsapp lead' || $campLowerCheck === 'the premium homes ltd' || $campLowerCheck === 'the premium homes ltd.') {
                        $campaign = $projectMatch['project_name'] . ' (WhatsApp)';
                    }
                }
                if ($campaign === '') {
                    $campaign = 'WhatsApp Lead';
                }
                $campLower = strtolower($campaign);

                // Smart Duplicate Check (unless user explicitly clicked Force Sync on this row)
                $duplicateLeadId = null;
                if (!$forceDuplicate) {
                    if ($normPhone !== '') {
                        if ($projectId !== null && $projectId > 0 && isset($existingPhoneByProject[$normPhone . '_' . $projectId])) {
                            $duplicateLeadId = $existingPhoneByProject[$normPhone . '_' . $projectId];
                        } elseif (($projectId === null || $projectId === 0) && $campLower !== '' && isset($existingPhoneByCampaign[$normPhone . '_' . $campLower])) {
                            $duplicateLeadId = $existingPhoneByCampaign[$normPhone . '_' . $campLower];
                        }
                    }

                    if ($duplicateLeadId === null && $email !== '' && !in_array($email, ['1@gmail.com', 'a@b.com', 't@t.com', 'test@test.com'], true)) {
                        if ($projectId !== null && $projectId > 0 && isset($existingEmailByProject[$email . '_' . $projectId])) {
                            $duplicateLeadId = $existingEmailByProject[$email . '_' . $projectId];
                        }
                    }
                }

                if ($duplicateLeadId !== null) {
                    // Backfill `which_apartment_size_are_you_interested_in` on existing lead if missing
                    $aptSizeVal = trim((string)($logRow['which_apartment_size_are_you_interested_in'] ?? ''));
                    if ($aptSizeVal !== '') {
                        try {
                            $bfStmt = $pdo->prepare("
                                UPDATE `leads`
                                SET `which_apartment_size_are_you_interested_in` = ?
                                WHERE `id` = ? AND (`which_apartment_size_are_you_interested_in` IS NULL OR `which_apartment_size_are_you_interested_in` = '')
                            ");
                            $bfStmt->execute([$aptSizeVal, $duplicateLeadId]);
                        } catch (Throwable $t) {}
                    }
                    $skippedDuplicates++;
                    $updateLogStmt->execute([
                        ':phone_number'       => $normPhone !== '' ? $normPhone : ($rawPhone ?: null),
                        ':campaign_name'      => $campaign,
                        ':sync_status'        => 'duplicate_skipped',
                        ':sync_note'          => "Duplicate skipped · Exists in Lead #LD-{$duplicateLeadId}",
                        ':synced_lead_id'     => $duplicateLeadId,
                        ':matched_project_id' => $projectId,
                        ':assigned_team_id'   => null,
                        ':id'                 => $logId,
                    ]);
                    continue;
                }

                // Resolve active Sales Team via Round-Robin
                $eligibleTeams = $projectMatch['teams'] ?? [];
                $teamId = $synchronizer->resolveNextTeamId($projectId, $eligibleTeams, $pdo);

                $leadData = [
                    'campaign_name'                            => $campaign,
                    'platform'                                 => 'whatsapp',
                    'which_apartment_size_are_you_interested_in' => $logRow['which_apartment_size_are_you_interested_in'] ?? null,
                    'which_floor_are_you_for_shop'             => $logRow['which_floor_are_you_for_shop'] ?? null,
                    'when_are_you_planning_to_buy'             => $logRow['when_are_you_planning_to_buy'] ?? null,
                    'would_you_like_to_schedule_a_site_visit' => $logRow['would_you_like_to_schedule_a_site_visit'] ?? null,
                    'when_is_the_best_time_to_contact_you'     => $logRow['when_is_the_best_time_to_contact_you'] ?? null,
                    'full_name'                                => $fullName !== '' ? $fullName : 'WhatsApp Lead',
                    'phone_number'                             => $normPhone !== '' ? $normPhone : ($rawPhone ?: null),
                    'email'                                    => $email !== '' ? $email : null,
                    'city'                                     => $logRow['city'] ?? null,
                    'country'                                  => $logRow['country'] ?? 'Bangladesh',
                    'address'                                  => $logRow['address'] ?? null,
                    'project_id'                               => $projectId,
                    'team_id'                                  => $teamId,
                    'distribute'                               => 0,
                    'is_automated'                             => 1,
                    'is_repibee'                               => 1,
                    'is_brocure_leads'                         => 0,
                    'created_by'                               => $currentUserId,
                    'created_at'                               => date('Y-m-d H:i:s'),
                ];

                $insertId = $synchronizer->insertLeadIntoDatabase($leadData, $pdo);
                $insertedCount++;

                // Update in-memory duplicate maps so duplicates within the same batch are also caught
                if ($normPhone !== '') {
                    if ($projectId !== null && $projectId > 0) {
                        $existingPhoneByProject[$normPhone . '_' . $projectId] = $insertId;
                    }
                    if ($campLower !== '') {
                        $existingPhoneByCampaign[$normPhone . '_' . $campLower] = $insertId;
                    }
                }
                if ($email !== '' && $projectId !== null && $projectId > 0) {
                    $existingEmailByProject[$email . '_' . $projectId] = $insertId;
                }

                $updateLogStmt->execute([
                    ':phone_number'       => $normPhone !== '' ? $normPhone : ($rawPhone ?: null),
                    ':campaign_name'      => $campaign,
                    ':sync_status'        => 'synced',
                    ':sync_note'          => "Synced to Lead #LD-{$insertId}",
                    ':synced_lead_id'     => $insertId,
                    ':matched_project_id' => $projectId,
                    ':assigned_team_id'   => $teamId,
                    ':id'                 => $logId,
                ]);
            }

            $synchronizer->log(sprintf(
                "[WhatsApp Leads Sync] Completed: %d processed -> %d newly inserted, %d duplicates skipped, %d invalid.",
                $totalCandidate,
                $insertedCount,
                $skippedDuplicates,
                $invalidCount
            ));

            $globalPending = (int)$pdo->query("SELECT COUNT(*) FROM `whatsapp_leads_logs` WHERE `sync_status` = 'pending'")->fetchColumn();
            $globalTotal   = (int)$pdo->query("SELECT COUNT(*) FROM `whatsapp_leads_logs`")->fetchColumn();

            echo json_encode([
                'status'             => 'success',
                'message'            => sprintf(
                    "WhatsApp Leads Sync Completed! Processed %d log(s): %d newly inserted into Leads, %d duplicates skipped%s.",
                    $totalCandidate,
                    $insertedCount,
                    $skippedDuplicates,
                    $invalidCount > 0 ? ", {$invalidCount} invalid" : ""
                ),
                'inserted_count'     => $insertedCount,
                'skipped_duplicates' => $skippedDuplicates,
                'invalid_count'      => $invalidCount,
                'total_processed'    => $totalCandidate,
                'global_pending'     => $globalPending,
                'global_total'       => $globalTotal,
                'api_pull_stats'     => $apiPullStats,
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'WhatsApp Leads Sync Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    /**
     * AJAX (Local Testing Helper): Simulate realistic incoming Replibee WhatsApp Webhook leads
     * Inserts sample pending webhook leads into `whatsapp_leads_logs` so the user can test
     * the Drawer inspection, Filtering, Duplicate Skipping, and Sync to `leads` locally in 1 click.
     */
    public function ajaxSimulateWhatsAppWebhook()
    {
        $this->checkAccess();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $synchronizer = new LeadSynchronizer($this->automationConfig);
            $pdo = $synchronizer->getDb();
            $synchronizer->ensureSchema($pdo);

            $randSuffix = rand(100, 999);
            $samplePayloads = [
                [
                    'full_name'        => 'Tanvir Hasan (Test WA)',
                    'phone_number'     => '01711' . rand(100000, 999999),
                    'email'            => 'tanvir.test' . $randSuffix . '@gmail.com',
                    'campaign_name'    => 'The Premium Crown Tower || WhatsApp Bot',
                    'project_interest' => 'The Premium Crown Tower | Ati Condominium',
                    'source_page'      => 'The Premium Homes Ltd (WhatsApp)',
                    'city'             => 'Mohammadpur, Dhaka',
                    'country'          => 'Bangladesh',
                    'address'          => 'Bosila Road, Mohammadpur',
                    'apt_size'         => '1550 Sq.Ft (3 Bed)',
                    'floor'            => '6th Floor',
                    'plan_buy'         => 'Within 1-2 Months',
                    'site_visit'       => 'Yes, this Friday afternoon',
                    'best_time'        => 'Evening (6 PM - 9 PM)',
                    'qa'               => [
                        ['question' => 'আপনি কোন প্রজেক্টে আগ্রহী?', 'answer' => 'The Premium Crown Tower'],
                        ['question' => 'কত স্কয়ার ফিটের অ্যাপার্টমেন্ট খুঁজছেন?', 'answer' => '1550 Sq.Ft (3 Bed)'],
                        ['question' => 'আপনি কি সাইট ভিজিট করতে চান?', 'answer' => 'Yes, this Friday afternoon'],
                        ['question' => 'যোগাযোগের উপযুক্ত সময় কোনটি?', 'answer' => 'Evening (6 PM - 9 PM)'],
                    ],
                ],
                [
                    'full_name'        => 'Farzana Rahman (Test WA)',
                    'phone_number'     => '01819' . rand(100000, 999999),
                    'email'            => 'farzana.wa' . $randSuffix . '@gmail.com',
                    'campaign_name'    => 'The Premium Canvas of Happiness || Bashundhara',
                    'project_interest' => 'The Premium Canvas of Happiness',
                    'source_page'      => 'The Premium Homes Ltd (WhatsApp)',
                    'city'             => 'Bashundhara R/A, Dhaka',
                    'country'          => 'Bangladesh',
                    'address'          => 'Block I, Bashundhara R/A',
                    'apt_size'         => '2150 Sq.Ft',
                    'floor'            => '8th Floor',
                    'plan_buy'         => 'Immediate / Ready to Book',
                    'site_visit'       => 'Yes, Saturday 11 AM',
                    'best_time'        => 'Morning (10 AM - 1 PM)',
                    'qa'               => [
                        ['question' => 'Preferred Project', 'answer' => 'The Premium Canvas of Happiness'],
                        ['question' => 'Apartment Size', 'answer' => '2150 Sq.Ft'],
                        ['question' => 'Site Visit Schedule', 'answer' => 'Yes, Saturday 11 AM'],
                    ],
                ],
            ];

            $insStmt = $pdo->prepare("
                INSERT INTO `whatsapp_leads_logs` (
                    `full_name`, `phone_number`, `email`, `campaign_name`, `project_interest`, `source_page`,
                    `city`, `country`, `address`,
                    `which_apartment_size_are_you_interested_in`,
                    `which_floor_are_you_for_shop`,
                    `when_are_you_planning_to_buy`,
                    `would_you_like_to_schedule_a_site_visit`,
                    `when_is_the_best_time_to_contact_you`,
                    `qa_json`, `raw_payload`,
                    `sync_status`, `sync_note`, `matched_project_id`, `created_at`
                ) VALUES (
                    :full_name, :phone_number, :email, :campaign_name, :project_interest, :source_page,
                    :city, :country, :address,
                    :apt_size, :floor, :plan_buy, :site_visit, :best_time,
                    :qa_json, :raw_payload,
                    'pending', 'Simulated Replibee Webhook · Ready to sync', :matched_project_id, NOW()
                )
            ");

            $insertedIds = [];
            foreach ($samplePayloads as $sp) {
                $m = $synchronizer->matchProject($sp['campaign_name'], $sp['project_interest']);
                $rawPayload = [
                    'event'   => 'lead.created',
                    'source'  => ['page' => $sp['source_page'], 'platform' => 'whatsapp'],
                    'contact' => ['name' => $sp['full_name'], 'phone' => $sp['phone_number'], 'email' => $sp['email']],
                    'interest' => ['products' => [$sp['project_interest']]],
                    'location' => ['city' => $sp['city'], 'country' => $sp['country'], 'address' => $sp['address']],
                    'automation_data' => [$sp['qa']],
                ];

                $insStmt->execute([
                    ':full_name'          => $sp['full_name'],
                    ':phone_number'       => $sp['phone_number'],
                    ':email'              => $sp['email'],
                    ':campaign_name'      => $sp['campaign_name'],
                    ':project_interest'   => $sp['project_interest'],
                    ':source_page'        => $sp['source_page'],
                    ':city'               => $sp['city'],
                    ':country'            => $sp['country'],
                    ':address'            => $sp['address'],
                    ':apt_size'           => $sp['apt_size'],
                    ':floor'              => $sp['floor'],
                    ':plan_buy'           => $sp['plan_buy'],
                    ':site_visit'         => $sp['site_visit'],
                    ':best_time'          => $sp['best_time'],
                    ':qa_json'            => json_encode($sp['qa'], JSON_UNESCAPED_UNICODE),
                    ':raw_payload'        => json_encode($rawPayload, JSON_UNESCAPED_UNICODE),
                    ':matched_project_id' => $m['project_id'] ?? null,
                ]);
                $insertedIds[] = (int)$pdo->lastInsertId();
            }

            $globalPending = (int)$pdo->query("SELECT COUNT(*) FROM `whatsapp_leads_logs` WHERE `sync_status` = 'pending'")->fetchColumn();
            $globalTotal   = (int)$pdo->query("SELECT COUNT(*) FROM `whatsapp_leads_logs`")->fetchColumn();

            echo json_encode([
                'status'         => 'success',
                'message'        => 'Simulated 2 incoming Replibee WhatsApp webhook leads into whatsapp_leads_logs (Pending Sync)!',
                'inserted_ids'   => $insertedIds,
                'global_pending' => $globalPending,
                'global_total'   => $globalTotal,
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Simulation failed: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * AJAX/API: Sync Brochure Leads from external API (https://api.dpremiumhomes.com/api/get-brochure-leads)
     * Maps: name -> full_name, phone -> phone_number, email -> email, property_id -> project_id
     * Platform: 'brochure'
     * Duplicate rule: Skip if same phone or email already exists for this same project.
     */
    public function ajaxSyncBrochureLeads()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $isCronOrApi = (!empty($_GET['api_key']) || !empty($_SERVER['HTTP_X_API_KEY']));
        if (!$isCronOrApi && empty($_SESSION['user_id'])) {
            $this->checkAccess();
        }

        header('Content-Type: application/json; charset=utf-8');
        set_time_limit(180);

        try {
            $this->ensureSchema();
            $currentUserId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
            $synchronizer = new LeadSynchronizer($this->automationConfig, $currentUserId);
            $pdo = $synchronizer->getDb();

            $apiUrl = 'https://api.dpremiumhomes.com/api/get-brochure-leads';
            
            // 1. Fetch data from API
            $rawJson = null;
            if (function_exists('curl_init')) {
                $ch = curl_init($apiUrl);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 30,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_USERAGENT      => 'TPHL-ERP-BrochureSync/1.0',
                ]);
                $rawJson = curl_exec($ch);
                curl_close($ch);
            }

            if (empty($rawJson)) {
                $rawJson = @file_get_contents($apiUrl);
            }

            if (empty($rawJson)) {
                throw new RuntimeException("Failed to fetch brochure leads from {$apiUrl}. Check network connection.");
            }

            $response = json_decode($rawJson, true);
            $leads = $response['leads'] ?? ($response['data'] ?? (is_array($response) ? $response : []));

            if (!is_array($leads)) {
                throw new RuntimeException("Invalid JSON format received from brochure leads API.");
            }

            $totalFetched = count($leads);
            if ($totalFetched === 0) {
                echo json_encode([
                    'status'             => 'success',
                    'message'            => 'No brochure leads found to sync.',
                    'inserted_count'     => 0,
                    'skipped_duplicates' => 0,
                    'total_fetched'      => 0
                ]);
                exit;
            }

            // 2. Pre-index existing leads by (normalized_phone + project_id) and (email + project_id)
            $existingPhoneProject = [];
            $existingEmailProject = [];
            $dupIndexStmt = $pdo->query("SELECT project_id, phone_number, phone, email, alt_email FROM `leads` WHERE deleted_at IS NULL");
            while ($row = $dupIndexStmt->fetch(PDO::FETCH_ASSOC)) {
                $pId = (string)($row['project_id'] ?? '');

                foreach (['phone_number', 'phone'] as $f) {
                    $p = trim($row[$f] ?? '');
                    if (!empty($p)) {
                        $clean = preg_replace('/^p:/i', '', $p);
                        $clean = preg_replace('/[\s\-\(\)]/', '', $clean);
                        $clean = preg_replace('/^\+88/', '', $clean);
                        $existingPhoneProject[$clean . '_' . $pId] = true;
                    }
                }

                foreach (['email', 'alt_email'] as $f) {
                    $em = strtolower(trim($row[$f] ?? ''));
                    if (!empty($em) && !in_array($em, ['1@gmail.com', 'a@b.com', 't@t.com', 'test@test.com'])) {
                        $existingEmailProject[$em . '_' . $pId] = true;
                    }
                }
            }

            // 3. Project Map & Eligible Teams
            $projectsMap = [];
            $pStmt = $pdo->query("SELECT id, name FROM `projects`");
            while ($pRow = $pStmt->fetch(PDO::FETCH_ASSOC)) {
                $projectsMap[(int)$pRow['id']] = $pRow['name'];
            }

            // Project eligible teams from dynamic automation rules (DB + config fallback)
            $projectTeamsMap = [];
            foreach ($synchronizer->getDynamicProjectsMap() as $kw => $pInfo) {
                if (!empty($pInfo['project_id']) && !empty($pInfo['teams'])) {
                    $projectTeamsMap[(int)$pInfo['project_id']] = $pInfo['teams'];
                }
            }

            // All active teams fallback
            $allActiveTeamIds = [];
            try {
                $tStmt = $pdo->query("SELECT id FROM `teams` WHERE `status` = 1 ORDER BY id ASC");
                $allActiveTeamIds = $tStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
            } catch (Throwable $t) {}

            $syncPeriod = strtolower(trim((string)($_GET['period'] ?? $_POST['period'] ?? 'today')));
            $todayDate = date('Y-m-d');
            $yesterdayDate = date('Y-m-d', strtotime('-1 day'));

            $insertedCount = 0;
            $skippedDuplicates = 0;
            $skippedOldDate = 0;
            $skippedInvalid = 0;
            $seenInBatch = [];

            // 4. Process each brochure lead
            foreach ($leads as $lead) {
                if (!is_array($lead)) continue;

                $leadCreatedAt = !empty($lead['created_at']) ? $lead['created_at'] : '';
                $leadTimestamp = !empty($leadCreatedAt) ? strtotime($leadCreatedAt) : 0;
                $leadDateBst = $leadTimestamp > 0 ? date('Y-m-d', $leadTimestamp + 6 * 3600) : '';

                // Daily / Recent Filter: Default to 'today' (last 48 hours / yesterday and today in BST)
                // This ensures daily leads from the last 24-48 hours are synced, while excluding old historical leads from months ago.
                $cutoff48h = strtotime('-48 hours');
                if ($syncPeriod === 'today') {
                    if ($leadTimestamp < $cutoff48h && $leadDateBst < $yesterdayDate) {
                        $skippedOldDate++;
                        continue;
                    }
                } elseif ($syncPeriod === 'strict_today') {
                    if ($leadDateBst !== $todayDate) {
                        $skippedOldDate++;
                        continue;
                    }
                } elseif ($syncPeriod === '7days') {
                    if ($leadTimestamp < strtotime('-7 days')) {
                        $skippedOldDate++;
                        continue;
                    }
                }

                $name = trim((string)($lead['name'] ?? ''));
                $rawPhone = trim((string)($lead['phone'] ?? ''));
                $cleanPhone = preg_replace('/^p:/i', '', $rawPhone);
                $cleanPhone = preg_replace('/[\s\-\(\)]/', '', $cleanPhone);
                $normPhone = preg_replace('/^\+88/', '', $cleanPhone);
                $email = strtolower(trim((string)($lead['email'] ?? '')));
                $projectId = !empty($lead['property_id']) ? (int)$lead['property_id'] : null;
                $projectIdKey = (string)($projectId ?? '');

                if (empty($cleanPhone) && empty($email) && empty($name)) {
                    $skippedInvalid++;
                    continue;
                }

                $isDuplicate = false;

                // Check 1: Phone duplicate for this project
                if (!empty($normPhone) && isset($existingPhoneProject[$normPhone . '_' . $projectIdKey])) {
                    $isDuplicate = true;
                }

                // Check 2: Email duplicate for this project
                if (!$isDuplicate && !empty($email) && !in_array($email, ['1@gmail.com', 'a@b.com', 't@t.com', 'test@test.com'])) {
                    if (isset($existingEmailProject[$email . '_' . $projectIdKey])) {
                        $isDuplicate = true;
                    }
                }

                // Check 3: Duplicate within current incoming batch
                if (!$isDuplicate) {
                    if (!empty($normPhone) && isset($seenInBatch['p_' . $normPhone . '_' . $projectIdKey])) {
                        $isDuplicate = true;
                    }
                    if (!$isDuplicate && !empty($email) && !in_array($email, ['1@gmail.com', 'a@b.com', 't@t.com', 'test@test.com']) && isset($seenInBatch['e_' . $email . '_' . $projectIdKey])) {
                        $isDuplicate = true;
                    }
                }

                if ($isDuplicate) {
                    $skippedDuplicates++;
                    continue;
                }

                // Resolve project name & eligible teams
                $projectName = $projectId && isset($projectsMap[$projectId]) ? $projectsMap[$projectId] : ($projectId ? "Project #{$projectId}" : 'Brochure Lead');
                $campaignName = $projectName . ' (Brochure)';
                $eligibleTeams = $projectTeamsMap[$projectId] ?? $allActiveTeamIds;
                $assignedTeamId = $synchronizer->resolveNextTeamId($projectId, $eligibleTeams, $pdo);

                // When syncing leads into ERP, set created_at to current sync time so that newly imported
                // leads immediately appear under Today's queue & platform dashboard.
                // Store original website download timestamp in description for full traceability.
                $webSubmissionNote = $leadTimestamp > 0 
                    ? ("Brochure downloaded: " . date('Y-m-d H:i:s', $leadTimestamp + 6 * 3600) . " BST") 
                    : '';
                $syncCreatedAt = date('Y-m-d H:i:s');

                $leadData = [
                    'campaign_name'    => $campaignName,
                    'platform'         => 'brochure',
                    'full_name'        => !empty($name) ? $name : 'Brochure Lead',
                    'phone_number'     => !empty($cleanPhone) ? $cleanPhone : null,
                    'email'            => !empty($email) ? $email : null,
                    'project_id'       => $projectId,
                    'team_id'          => $assignedTeamId,
                    'distribute'       => 0, // Pending Review for Digital Marketing team
                    'is_automated'     => 1,
                    'is_repibee'       => 0,
                    'is_brocure_leads' => 1,
                    'description'      => $webSubmissionNote,
                    'created_by'       => $_SESSION['user']['id'] ?? null,
                    'created_at'       => $syncCreatedAt,
                ];

                try {
                    $synchronizer->insertLeadIntoDatabase($leadData, $pdo);
                    $insertedCount++;

                    // Mark as seen so duplicates later in batch are caught
                    if (!empty($normPhone)) {
                        $seenInBatch['p_' . $normPhone . '_' . $projectIdKey] = true;
                        $existingPhoneProject[$normPhone . '_' . $projectIdKey] = true;
                    }
                    if (!empty($email) && !in_array($email, ['1@gmail.com', 'a@b.com', 't@t.com', 'test@test.com'])) {
                        $seenInBatch['e_' . $email . '_' . $projectIdKey] = true;
                        $existingEmailProject[$email . '_' . $projectIdKey] = true;
                    }
                } catch (Throwable $ex) {
                    error_log("Brochure Lead Insert Error: " . $ex->getMessage());
                }
            }

            $synchronizer->log(sprintf(
                "[Brochure Sync] Process finished: %d newly inserted, %d duplicates skipped, %d old historical leads excluded.",
                $insertedCount,
                $skippedDuplicates,
                $skippedOldDate
            ));

            echo json_encode([
                'status'             => 'success',
                'message'            => sprintf("Brochure Leads Sync Completed! Processed recent leads (%s): %d newly inserted, %d duplicates skipped, %d old historical leads excluded.", $todayDate, $insertedCount, $skippedDuplicates, $skippedOldDate),
                'inserted_count'     => $insertedCount,
                'skipped_duplicates' => $skippedDuplicates,
                'skipped_old_date'   => $skippedOldDate,
                'total_fetched'      => $totalFetched,
                'sync_period'        => $syncPeriod,
                'target_date'        => $todayDate
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Brochure Leads Sync Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * AJAX: Get all configured Team & Project Setup rules, all ERP projects, and all Sales Teams
     */
    public function ajaxGetProjectRules()
    {
        $this->checkAccess();
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        try {
            $synchronizer = new LeadSynchronizer($this->automationConfig);
            $pdo = $synchronizer->getDb();
            $synchronizer->ensureSchema($pdo);

            // 1. Fetch all active sales teams
            $teams = [];
            $teamsMap = [];
            try {
                $tStmt = $pdo->query("SELECT id, name, status FROM `teams` WHERE `status` = 1 ORDER BY id ASC");
                while ($tRow = $tStmt->fetch(PDO::FETCH_ASSOC)) {
                    $tid = (int)$tRow['id'];
                    $teams[] = [
                        'id'     => $tid,
                        'name'   => $tRow['name'],
                        'status' => (int)($tRow['status'] ?? 1)
                    ];
                    $teamsMap[$tid] = $tRow['name'];
                }
            } catch (Throwable $t) {}

            // 2. Fetch all non-deleted ERP projects
            $allProjects = [];
            $projectsById = [];
            try {
                $pStmt = $pdo->query("SELECT id, name, project_sale_status FROM `projects` WHERE deleted_at IS NULL ORDER BY (CASE WHEN project_sale_status = 'On Sale' THEN 0 ELSE 1 END), name ASC");
                while ($pRow = $pStmt->fetch(PDO::FETCH_ASSOC)) {
                    $pid = (int)$pRow['id'];
                    $item = [
                        'id'                  => $pid,
                        'name'                => $pRow['name'],
                        'project_sale_status' => $pRow['project_sale_status'] ?? 'On Sale',
                    ];
                    $allProjects[] = $item;
                    $projectsById[$pid] = $item;
                }
            } catch (Throwable $t) {}

            // 3. Fetch configured project rules
            $rStmt = $pdo->query("
                SELECT r.*, p.name AS db_project_name, p.project_sale_status
                FROM `lead_automation_project_rules` r
                LEFT JOIN `projects` p ON r.project_id = p.id
                ORDER BY r.is_active DESC, (CASE WHEN p.project_sale_status = 'On Sale' THEN 0 ELSE 1 END), COALESCE(p.name, r.project_name) ASC
            ");
            $rawRules = $rStmt ? $rStmt->fetchAll(PDO::FETCH_ASSOC) : [];

            $rules = [];
            $configuredProjectIds = [];

            foreach ($rawRules as $r) {
                $pid = (int)$r['project_id'];
                $configuredProjectIds[$pid] = true;

                $teamIdsRaw = json_decode((string)($r['team_ids'] ?? '[]'), true);
                if (!is_array($teamIdsRaw)) {
                    $teamIdsRaw = array_filter(array_map('intval', explode(',', (string)($r['team_ids'] ?? ''))));
                }
                $teamIds = array_values(array_filter(array_map('intval', $teamIdsRaw), fn($id) => $id > 0 && isset($teamsMap[$id])));

                $teamNames = [];
                foreach ($teamIds as $tid) {
                    $teamNames[] = $teamsMap[$tid];
                }

                $kwList = array_values(array_filter(array_map('trim', preg_split('/[,;\n]+/', (string)($r['keywords'] ?? '')))));

                $rules[] = [
                    'id'                  => (int)$r['id'],
                    'project_id'          => $pid,
                    'project_name'        => !empty($r['db_project_name']) ? $r['db_project_name'] : $r['project_name'],
                    'project_sale_status' => $r['project_sale_status'] ?? 'On Sale',
                    'keywords'            => implode(', ', $kwList),
                    'keywords_list'       => $kwList,
                    'team_ids'            => $teamIds,
                    'team_names'          => $teamNames,
                    'is_active'           => (int)($r['is_active'] ?? 1),
                    'updated_at'          => $r['updated_at'] ?? $r['created_at'] ?? null,
                ];
            }

            // 4. Identify On-Sale projects that are not yet configured in automation
            $unconfiguredOnSale = [];
            foreach ($allProjects as $proj) {
                $pid = (int)$proj['id'];
                $pNameLower = strtolower(trim($proj['name']));
                if ($pNameLower === 'in progress' || str_contains($pNameLower, 'test project')) {
                    continue;
                }
                if (($proj['project_sale_status'] ?? '') === 'On Sale' && !isset($configuredProjectIds[$pid])) {
                    $unconfiguredOnSale[] = $proj;
                }
            }

            echo json_encode([
                'status' => 'success',
                'data'   => [
                    'rules'                => $rules,
                    'all_projects'         => $allProjects,
                    'unconfigured_on_sale' => $unconfiguredOnSale,
                    'teams'                => $teams,
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
     * AJAX: Save (Add or Update) a Project & Team Setup Rule
     */
    public function ajaxSaveProjectRule()
    {
        $this->checkAccess();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $projectId = (int)($input['project_id'] ?? 0);
            $keywordsRaw = trim((string)($input['keywords'] ?? ''));
            $teamIdsInput = $input['team_ids'] ?? [];
            $isActive = isset($input['is_active']) ? ((int)$input['is_active'] ? 1 : 0) : 1;
            $rematchCommon = !empty($input['rematch_common']);

            if ($projectId <= 0) {
                throw new InvalidArgumentException("Please select a valid project.");
            }

            if (!is_array($teamIdsInput)) {
                $teamIdsInput = array_filter(explode(',', (string)$teamIdsInput));
            }
            $teamIds = array_values(array_unique(array_filter(array_map('intval', $teamIdsInput), fn($id) => $id > 0)));

            if (empty($teamIds)) {
                throw new InvalidArgumentException("Please select at least one sales team for this project.");
            }

            $synchronizer = new LeadSynchronizer($this->automationConfig);
            $pdo = $synchronizer->getDb();
            $synchronizer->ensureSchema($pdo);

            // Lookup project name from `projects` table
            $pStmt = $pdo->prepare("SELECT id, name FROM `projects` WHERE id = ? LIMIT 1");
            $pStmt->execute([$projectId]);
            $projRow = $pStmt->fetch(PDO::FETCH_ASSOC);
            $projectName = $projRow['name'] ?? trim((string)($input['project_name'] ?? "Project #{$projectId}"));

            // Clean or auto-generate keywords if empty
            $kwParts = preg_split('/[,;\n]+/', $keywordsRaw);
            $cleanKeywords = [];
            foreach ($kwParts as $kw) {
                $k = strtolower(trim($kw));
                if ($k !== '' && !in_array($k, $cleanKeywords, true)) {
                    $cleanKeywords[] = $k;
                }
            }

            if (empty($cleanKeywords)) {
                $fullLower = strtolower(trim(preg_replace('/\s+/', ' ', $projectName)));
                $shortLower = trim(preg_replace('/^(the\s+premium\s+|the\s+)/i', '', $fullLower));
                if ($shortLower !== '' && $shortLower !== $fullLower) {
                    $cleanKeywords[] = $shortLower;
                }
                $cleanKeywords[] = $fullLower;
            }

            $keywordsStr = implode(', ', $cleanKeywords);
            $teamIdsJson = json_encode($teamIds);
            $userId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;

            $upsertSql = "
                INSERT INTO `lead_automation_project_rules`
                    (`project_id`, `project_name`, `keywords`, `team_ids`, `is_active`, `updated_by`, `created_at`, `updated_at`)
                VALUES
                    (?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    `project_name` = VALUES(`project_name`),
                    `keywords`     = VALUES(`keywords`),
                    `team_ids`     = VALUES(`team_ids`),
                    `is_active`    = VALUES(`is_active`),
                    `updated_by`   = VALUES(`updated_by`),
                    `updated_at`   = NOW()
            ";
            $stmt = $pdo->prepare($upsertSql);
            $stmt->execute([$projectId, $projectName, $keywordsStr, $teamIdsJson, $isActive, $userId]);

            $synchronizer->clearProjectCache();

            // Optionally re-match existing automated "Common" leads (project_id IS NULL OR project_id = 0)
            $rematchedCount = 0;
            if ($rematchCommon && $isActive === 1) {
                $commonStmt = $pdo->query("SELECT id, campaign_name, team_id FROM `leads` WHERE deleted_at IS NULL AND is_automated = 1 AND (project_id IS NULL OR project_id = 0)");
                $commonLeads = $commonStmt ? $commonStmt->fetchAll(PDO::FETCH_ASSOC) : [];
                foreach ($commonLeads as $cLead) {
                    $m = $synchronizer->matchProject((string)($cLead['campaign_name'] ?? ''));
                    if (!empty($m['project_id']) && (int)$m['project_id'] === $projectId) {
                        $existingTeam = (int)($cLead['team_id'] ?? 0);
                        $newTeamId = $existingTeam > 0 ? $existingTeam : $synchronizer->resolveNextTeamId($projectId, $teamIds, $pdo);
                        $uStmt = $pdo->prepare("UPDATE `leads` SET `project_id` = ?, `team_id` = ? WHERE `id` = ?");
                        $uStmt->execute([$projectId, $newTeamId, (int)$cLead['id']]);
                        $rematchedCount++;
                    }
                }
            }

            $extraMsg = $rematchedCount > 0 ? " Also auto-matched {$rematchedCount} Common lead(s) to {$projectName}!" : "";

            echo json_encode([
                'status'          => 'success',
                'message'         => "Saved setup for \"{$projectName}\" with " . count($teamIds) . " team(s)!" . $extraMsg,
                'rematched_count' => $rematchedCount
            ]);
        } catch (Throwable $e) {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * AJAX: Toggle Active/Inactive status of a Project Rule
     */
    public function ajaxToggleProjectRule()
    {
        $this->checkAccess();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $projectId = (int)($input['project_id'] ?? 0);
            $isActive = (int)($input['is_active'] ?? 0) ? 1 : 0;

            if ($projectId <= 0) {
                throw new InvalidArgumentException("Invalid project ID.");
            }

            $synchronizer = new LeadSynchronizer($this->automationConfig);
            $pdo = $synchronizer->getDb();
            $synchronizer->ensureSchema($pdo);

            $userId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
            $stmt = $pdo->prepare("UPDATE `lead_automation_project_rules` SET `is_active` = ?, `updated_by` = ?, `updated_at` = NOW() WHERE `project_id` = ?");
            $stmt->execute([$isActive, $userId, $projectId]);

            $synchronizer->clearProjectCache();

            echo json_encode([
                'status'  => 'success',
                'message' => $isActive ? 'Project automation rule activated.' : 'Project automation rule paused.'
            ]);
        } catch (Throwable $e) {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * AJAX: Delete a Project Rule
     */
    public function ajaxDeleteProjectRule()
    {
        $this->checkAccess();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $projectId = (int)($input['project_id'] ?? 0);

            if ($projectId <= 0) {
                throw new InvalidArgumentException("Invalid project ID.");
            }

            $synchronizer = new LeadSynchronizer($this->automationConfig);
            $pdo = $synchronizer->getDb();
            $synchronizer->ensureSchema($pdo);

            $stmt = $pdo->prepare("DELETE FROM `lead_automation_project_rules` WHERE `project_id` = ?");
            $stmt->execute([$projectId]);

            $synchronizer->clearProjectCache();

            echo json_encode([
                'status'  => 'success',
                'message' => 'Project automation setup removed.'
            ]);
        } catch (Throwable $e) {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
}
