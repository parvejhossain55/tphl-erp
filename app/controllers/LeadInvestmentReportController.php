<?php

require_once __DIR__ . '/../services/LeadAutomation/LeadSynchronizer.php';

class LeadInvestmentReportController extends Controller
{
    protected $db;
    protected array $automationConfig;

    public function __construct($db)
    {
        $this->db = $db;
        $this->automationConfig = file_exists(__DIR__ . '/../config/lead_automation.php')
            ? require __DIR__ . '/../config/lead_automation.php'
            : [];
        $this->ensureSchema();
    }

    /**
     * Get PDO instance via LeadSynchronizer or fallback
     */
    private function getPdo(): PDO
    {
        $synchronizer = new LeadSynchronizer($this->automationConfig);
        return $synchronizer->getDb();
    }

    /**
     * Strictly authorize Management (Admin, Super Admin, Director, Chief Officer)
     * and Head of Digital Marketing Team (Role 10).
     * Role 11 (Executive of Digital Marketing Team) is blocked.
     */
    private function checkAccess(bool $isAjax = false): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userRole = (int)($_SESSION['user']['role_id'] ?? 0);
        $secondaryRole = (int)($_SESSION['user']['secondary_role_id'] ?? 0);
        $primaryRole = (int)($_SESSION['user']['primary_role_id'] ?? 0);
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $userRoleName = strtolower(trim((string)($_SESSION['user']['role_name'] ?? '')));

        // Block Executive of Digital Marketing Team (Role 11)
        if ($userRole === 11 || $secondaryRole === 11 || $primaryRole === 11 || strpos($userRoleName, 'executive of digital marketing') !== false) {
            if ($isAjax) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'error', 'message' => 'Permission denied.']);
                exit;
            }
            if (function_exists('setError')) {
                setError("You do not have permission to access Leads Investment Report.");
            }
            header("Location: {$GLOBALS['base_url']}/my-dashboard");
            exit;
        }

        $allowedRoles = [1, 3, 10, 33, 34, 35];
        $hasPerm = function_exists('hasPermission') && hasPermission("Digital Marketing Panel");

        $isAuthorized = in_array($userRole, $allowedRoles, true)
            || in_array($secondaryRole, $allowedRoles, true)
            || in_array($primaryRole, $allowedRoles, true)
            || $userId === 94
            || $hasPerm
            || strpos($userRoleName, 'admin') !== false
            || strpos($userRoleName, 'management') !== false
            || strpos($userRoleName, 'director') !== false
            || strpos($userRoleName, 'head of digital marketing') !== false;

        if (!$isAuthorized) {
            if ($isAjax) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
                exit;
            }
            if (function_exists('setError')) {
                setError("You do not have permission to access Leads Investment Report.");
            }
            header("Location: {$GLOBALS['base_url']}/my-dashboard");
            exit;
        }
    }

    /**
     * Auto-create required tables for Leads Investment Report
     */
    public function ensureSchema(): void
    {
        try {
            $pdo = $this->getPdo();

            // 1. Daily / Custom-Range Actual Spend Table
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `lead_investment_spends` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `spend_date` DATE NOT NULL,
                    `spend_end_date` DATE NULL,
                    `platform` VARCHAR(100) NOT NULL DEFAULT 'Meta Ads',
                    `ad_account` VARCHAR(120) NOT NULL DEFAULT 'TPHL_ATI',
                    `project_id` INT NOT NULL DEFAULT 0,
                    `campaign_name` VARCHAR(255) NULL,
                    `ad_set_name` VARCHAR(255) NULL,
                    `ad_name` VARCHAR(255) NULL,
                    `ad_id` VARCHAR(150) NULL,
                    `currency` VARCHAR(10) NOT NULL DEFAULT 'USD',
                    `actual_spend` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                    `spend_usd` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                    `fx_rate` DECIMAL(10,2) NOT NULL DEFAULT 133.00,
                    `bdt_equivalent` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                    `source_reference` VARCHAR(255) NULL,
                    `notes` TEXT NULL,
                    `created_by` INT NULL,
                    `updated_by` INT NULL,
                    `updated_by_name` VARCHAR(150) NULL,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    `deleted_at` DATETIME NULL,
                    KEY `idx_lis_spend_date` (`spend_date`, `spend_end_date`),
                    KEY `idx_lis_project_platform` (`project_id`, `platform`),
                    KEY `idx_lis_deleted` (`deleted_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            ");

            // Auto-add spend_end_date on existing installations
            $chkEndDate = $pdo->query("SHOW COLUMNS FROM `lead_investment_spends` LIKE 'spend_end_date'");
            if ($chkEndDate && $chkEndDate->rowCount() === 0) {
                $pdo->exec("ALTER TABLE `lead_investment_spends` ADD COLUMN `spend_end_date` DATE NULL AFTER `spend_date`");
            }

            // 2. Project x Platform Budget & Target CPL Table
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `lead_investment_budgets` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `project_id` INT NOT NULL,
                    `platform` VARCHAR(100) NOT NULL DEFAULT 'Meta Ads',
                    `daily_budget_usd` DECIMAL(14,2) NOT NULL DEFAULT 100.00,
                    `monthly_budget_usd` DECIMAL(14,2) NOT NULL DEFAULT 3000.00,
                    `target_cpl_usd` DECIMAL(10,2) NOT NULL DEFAULT 0.45,
                    `updated_by` INT NULL,
                    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY `uq_lib_proj_plat` (`project_id`, `platform`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            ");

            // 3. Audit / Edit History Table for Daily Spend Entries
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `lead_investment_spend_history` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `spend_id` INT NOT NULL,
                    `action_type` VARCHAR(50) NOT NULL DEFAULT 'created',
                    `old_spend_usd` DECIMAL(14,2) NULL,
                    `new_spend_usd` DECIMAL(14,2) NULL,
                    `old_fx_rate` DECIMAL(10,2) NULL,
                    `new_fx_rate` DECIMAL(10,2) NULL,
                    `old_ad_id` VARCHAR(150) NULL,
                    `new_ad_id` VARCHAR(150) NULL,
                    `change_note` TEXT NULL,
                    `changed_by` INT NULL,
                    `changed_by_name` VARCHAR(150) NULL,
                    `changed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    KEY `idx_lish_spend_id` (`spend_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            ");

            // 4. Ensure required lead classification columns exist on `leads`
            $requiredLeadCols = [
                'is_brocure_leads' => 'TINYINT(1) NOT NULL DEFAULT 0',
                'is_repibee'       => 'TINYINT(1) NOT NULL DEFAULT 0',
                'is_automated'     => 'TINYINT(1) NOT NULL DEFAULT 0',
                'distribute'       => 'TINYINT(1) NOT NULL DEFAULT 0',
            ];
            foreach ($requiredLeadCols as $col => $def) {
                $chk = $pdo->query("SHOW COLUMNS FROM `leads` LIKE '{$col}'");
                if ($chk && $chk->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE `leads` ADD COLUMN `{$col}` {$def}");
                }
            }
        } catch (Throwable $e) {
            error_log("LeadInvestmentReportController ensureSchema error: " . $e->getMessage());
        }
    }

    /**
     * Main Landing Page: Leads Investment Report (4 Tabs: Overview, Daily spend entry, Ad performance, Statements)
     */
    public function index()
    {
        $this->checkAccess(false);
        $pdo = $this->getPdo();

        // Fetch active ERP projects (prioritize On Sale & configured projects)
        $projects = [];
        try {
            $stmt = $pdo->query("
                SELECT p.id, p.name, p.project_status,
                       CASE WHEN r.id IS NOT NULL AND r.is_active = 1 THEN 1 ELSE 0 END AS is_configured
                FROM `projects` p
                LEFT JOIN `lead_automation_project_rules` r ON r.project_id = p.id
                WHERE p.deleted_at IS NULL
                ORDER BY is_configured DESC, p.name ASC
            ");
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $t) {
            try {
                $stmt = $pdo->query("SELECT id, name FROM `projects` WHERE deleted_at IS NULL ORDER BY name ASC");
                $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Throwable $e2) {}
        }

        // Fetch distinct ad accounts & campaigns for quick autocomplete / dropdowns
        $adAccounts = ['TPHL_ATI', 'TPHL_ASHULIA', 'TPHL_GULSHAN', 'TPHL_NATURE', 'TPHL_GOOGLE_MAIN'];
        try {
            $accStmt = $pdo->query("SELECT DISTINCT `ad_account` FROM `lead_investment_spends` WHERE `deleted_at` IS NULL AND `ad_account` IS NOT NULL AND `ad_account` != '' ORDER BY `ad_account` ASC");
            $dbAccs = $accStmt->fetchAll(PDO::FETCH_COLUMN);
            foreach ($dbAccs as $a) {
                if (!in_array($a, $adAccounts, true)) {
                    $adAccounts[] = $a;
                }
            }
        } catch (Throwable $t) {}

        $campaigns = [];
        try {
            $cStmt = $pdo->query("
                SELECT DISTINCT TRIM(`campaign_name`) AS camp
                FROM `leads`
                WHERE `deleted_at` IS NULL
                  AND `campaign_name` IS NOT NULL
                  AND TRIM(`campaign_name`) != ''
                ORDER BY `id` DESC
                LIMIT 120
            ");
            $campaigns = array_values(array_filter($cStmt->fetchAll(PDO::FETCH_COLUMN)));
        } catch (Throwable $t) {}

        $currentUserName = $_SESSION['user']['name']
            ?? $_SESSION['user']['full_name']
            ?? $_SESSION['user']['username']
            ?? 'Authorized Operator';

        return $this->view("digital-marketing-team/lead-investment-report", [
            "current_route"   => "lead-investment-report",
            "page_title"      => "Leads Investment Report",
            "projects"        => $projects,
            "adAccounts"      => $adAccounts,
            "campaigns"       => $campaigns,
            "currentUserName" => $currentUserName,
        ]);
    }

    /**
     * Normalize Platform name to standard reporting buckets:
     * 'Meta Ads', 'Google Ads', 'WhatsApp Ads', 'TikTok Ads', 'Others'
     */
    private function normalizePlatformName(string $raw): string
    {
        $v = strtolower(trim($raw));
        if ($v === '' || $v === 'meta' || $v === 'meta ads' || strpos($v, 'facebook') !== false || strpos($v, 'fb') !== false || strpos($v, 'inbox') !== false || strpos($v, 'meta') !== false) {
            return 'Meta Ads';
        }
        if (strpos($v, 'google') !== false || $v === 'gf' || strpos($v, 'youtube') !== false || strpos($v, 'search') !== false) {
            return 'Google Ads';
        }
        if (strpos($v, 'whatsapp') !== false || $v === 'wa' || strpos($v, 'replibee') !== false || strpos($v, 'repibee') !== false) {
            return 'WhatsApp Ads';
        }
        if (strpos($v, 'tiktok') !== false) {
            return 'TikTok Ads';
        }
        return 'Others';
    }

    /**
     * SQL CASE expression to classify `leads` rows into investment platform vs organic:
     * - Organic: `is_brocure_leads = 1` or platform contains 'brochure'/'organic'/'walk-in'/'referral'
     * - Paid Google Ads: platform contains 'google' / 'gf'
     * - Paid WhatsApp: `is_repibee = 1` or platform contains 'whatsapp' / 'wa'
     * - Paid Meta Ads: facebook form, meta inbox, lead form, etc.
     */
    private function getLeadPlatformSql(string $alias = 'l'): string
    {
        return "CASE
            WHEN {$alias}.is_brocure_leads = 1
                 OR LOWER(TRIM(COALESCE({$alias}.platform, ''))) LIKE '%brochure%'
                 OR LOWER(TRIM(COALESCE({$alias}.platform, ''))) LIKE '%brocure%'
                 OR LOWER(TRIM(COALESCE({$alias}.platform, ''))) LIKE '%organic%'
                 OR LOWER(TRIM(COALESCE({$alias}.uploaded_from, ''))) LIKE '%organic%'
                 THEN 'Organic'
            WHEN LOWER(TRIM(COALESCE({$alias}.platform, ''))) LIKE '%google%'
                 OR LOWER(TRIM(COALESCE({$alias}.platform, ''))) IN ('gf', 'google_form', 'googleform', 'google ads')
                 THEN 'Google Ads'
            WHEN {$alias}.is_repibee = 1
                 OR LOWER(TRIM(COALESCE({$alias}.platform, ''))) LIKE '%whatsapp%'
                 OR LOWER(TRIM(COALESCE({$alias}.platform, ''))) = 'wa'
                 THEN 'WhatsApp Ads'
            ELSE 'Meta Ads'
        END";
    }

    /**
     * Resolve Date Preset ('daily', 'weekly', 'monthly', 'yearly', 'custom') into [startDate, endDate, daysCount, label]
     */
    private function resolveDateRange(array $filters): array
    {
        $preset = strtolower(trim((string)($filters['period'] ?? 'daily')));
        $start = trim((string)($filters['start_date'] ?? ''));
        $end = trim((string)($filters['end_date'] ?? ''));

        if ($preset === 'daily') {
            $d = (!empty($start) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) ? $start : date('Y-m-d');
            return [
                'period'     => 'daily',
                'start_date' => $d,
                'end_date'   => $d,
                'days'       => 1,
                'label'      => date('d M Y', strtotime($d)) . ' – ' . date('d M Y', strtotime($d)),
            ];
        }

        if ($preset === 'weekly') {
            $endD = (!empty($end) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) ? $end : date('Y-m-d');
            $startD = date('Y-m-d', strtotime($endD . ' -6 days'));
            return [
                'period'     => 'weekly',
                'start_date' => $startD,
                'end_date'   => $endD,
                'days'       => 7,
                'label'      => date('d M Y', strtotime($startD)) . ' – ' . date('d M Y', strtotime($endD)),
            ];
        }

        if ($preset === 'monthly') {
            $ref = (!empty($start) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) ? $start : date('Y-m-d');
            $startD = date('Y-m-01', strtotime($ref));
            $endD = date('Y-m-t', strtotime($ref));
            $days = max(1, (int)round((strtotime($endD) - strtotime($startD)) / 86400) + 1);
            return [
                'period'     => 'monthly',
                'start_date' => $startD,
                'end_date'   => $endD,
                'days'       => $days,
                'label'      => date('d M Y', strtotime($startD)) . ' – ' . date('d M Y', strtotime($endD)),
            ];
        }

        if ($preset === 'yearly') {
            $yr = (!empty($start) && preg_match('/^\d{4}/', $start)) ? substr($start, 0, 4) : date('Y');
            $startD = "{$yr}-01-01";
            $endD = "{$yr}-12-31";
            return [
                'period'     => 'yearly',
                'start_date' => $startD,
                'end_date'   => $endD,
                'days'       => 365,
                'label'      => date('d M Y', strtotime($startD)) . ' – ' . date('d M Y', strtotime($endD)),
            ];
        }

        // Custom range
        $startD = (!empty($start) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) ? $start : date('Y-m-d');
        $endD = (!empty($end) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) ? $end : $startD;
        if ($startD > $endD) {
            [$startD, $endD] = [$endD, $startD];
        }
        $days = max(1, (int)round((strtotime($endD) - strtotime($startD)) / 86400) + 1);

        return [
            'period'     => 'custom',
            'start_date' => $startD,
            'end_date'   => $endD,
            'days'       => $days,
            'label'      => date('d M Y', strtotime($startD)) . ' – ' . date('d M Y', strtotime($endD)),
        ];
    }

    /**
     * AJAX: Main Dashboard Data for all 4 Tabs (Overview, Daily Spend Entry, Ad Performance, Statements)
     */
    public function ajaxDashboardData(): void
    {
        $this->checkAccess(true);
        header('Content-Type: application/json; charset=utf-8');

        try {
            $filters = [
                'period'     => $_GET['period'] ?? 'daily',
                'start_date' => $_GET['start_date'] ?? '',
                'end_date'   => $_GET['end_date'] ?? '',
                'project_id' => $_GET['project_id'] ?? '',
                'platform'   => $_GET['platform'] ?? '',
                'account_q'  => trim((string)($_GET['account_q'] ?? '')),
            ];

            $data = $this->computeDashboardData($filters);

            echo json_encode([
                'status' => 'success',
                'data'   => $data,
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ]);
        }
        exit;
    }

    /**
     * Compute complete dashboard & statement dataset for given filters
     */
    private function computeDashboardData(array $filters): array
    {
        $pdo = $this->getPdo();

        // Auto-rematch any unassigned "Common" leads first so project attribution is 100% up-to-date
        try {
            $synchronizer = new LeadSynchronizer($this->automationConfig);
            $synchronizer->rematchUnassignedCommonLeads($pdo);
        } catch (Throwable $ignore) {}

        $dateInfo = $this->resolveDateRange($filters);
        $startDate = $dateInfo['start_date'];
        $endDate = $dateInfo['end_date'];
        $daysCount = $dateInfo['days'];

        $projectFilter = trim((string)($filters['project_id'] ?? ''));
        $platformFilter = trim((string)($filters['platform'] ?? ''));
        $accountQuery = trim((string)($filters['account_q'] ?? ''));

        // 1. Load Project names map
        $projectNames = [0 => 'Common / Unmapped Project'];
        $pStmt = $pdo->query("SELECT id, name FROM `projects`");
        foreach ($pStmt->fetchAll(PDO::FETCH_ASSOC) as $pr) {
            $projectNames[(int)$pr['id']] = $pr['name'];
        }

            // 2. Load Budgets & Target CPL map keyed by "project_id|platform"
            $budgetsMap = [];
            $bStmt = $pdo->query("SELECT * FROM `lead_investment_budgets`");
            foreach ($bStmt->fetchAll(PDO::FETCH_ASSOC) as $bRow) {
                $bKey = (int)$bRow['project_id'] . '|' . $this->normalizePlatformName((string)$bRow['platform']);
                $budgetsMap[$bKey] = [
                    'daily_budget_usd'   => (float)$bRow['daily_budget_usd'],
                    'monthly_budget_usd' => (float)$bRow['monthly_budget_usd'],
                    'target_cpl_usd'     => (float)$bRow['target_cpl_usd'],
                ];
            }

            // 3. Query Spend Entries overlapping the selected Date Range
            $spendWhere = [
                "s.deleted_at IS NULL",
                "s.spend_date <= ?",
                "COALESCE(s.spend_end_date, s.spend_date) >= ?",
            ];
            $spendParams = [$endDate, $startDate];

            if ($projectFilter !== '' && is_numeric($projectFilter)) {
                $spendWhere[] = "s.project_id = ?";
                $spendParams[] = (int)$projectFilter;
            }
            if ($platformFilter !== '') {
                $spendWhere[] = "s.platform = ?";
                $spendParams[] = $platformFilter;
            }
            if ($accountQuery !== '') {
                $spendWhere[] = "(s.ad_account LIKE ? OR s.campaign_name LIKE ? OR s.ad_name LIKE ? OR s.ad_id LIKE ?)";
                $likeQ = '%' . $accountQuery . '%';
                array_push($spendParams, $likeQ, $likeQ, $likeQ, $likeQ);
            }

            $spendSql = "
                SELECT s.*, COALESCE(p.name, 'Common / Unmapped') AS project_name
                FROM `lead_investment_spends` s
                LEFT JOIN `projects` p ON p.id = s.project_id
                WHERE " . implode(" AND ", $spendWhere) . "
                ORDER BY COALESCE(s.spend_end_date, s.spend_date) DESC, s.spend_date DESC, s.id DESC
            ";
            $sStmt = $pdo->prepare($spendSql);
            $sStmt->execute($spendParams);
            $spendEntries = $sStmt->fetchAll(PDO::FETCH_ASSOC);

            // Pre-compute each spend entry's date span, overlap with [$startDate, $endDate], and scoped spend
            foreach ($spendEntries as &$seRef) {
                $eStart = (string)$seRef['spend_date'];
                $rawEnd = trim((string)($seRef['spend_end_date'] ?? ''));
                $eEnd = ($rawEnd !== '' && $rawEnd >= $eStart) ? $rawEnd : $eStart;
                $eTotalDays = max(1, (int)round((strtotime($eEnd) - strtotime($eStart)) / 86400) + 1);

                $ovStart = $eStart > $startDate ? $eStart : $startDate;
                $ovEnd = $eEnd < $endDate ? $eEnd : $endDate;
                $ovDays = max(1, (int)round((strtotime($ovEnd) - strtotime($ovStart)) / 86400) + 1);

                $ratio = ($ovDays >= $eTotalDays) ? 1.0 : ($ovDays / $eTotalDays);
                $fullUsd = (float)$seRef['spend_usd'];
                $fullBdt = (float)$seRef['bdt_equivalent'];

                $seRef['_entry_start']   = $eStart;
                $seRef['_entry_end']     = $eEnd;
                $seRef['_entry_days']    = $eTotalDays;
                $seRef['_overlap_start'] = $ovStart;
                $seRef['_overlap_end']   = $ovEnd;
                $seRef['_overlap_days']  = $ovDays;
                $seRef['_is_prorated']   = ($ovDays < $eTotalDays);
                $seRef['_scoped_usd']    = ($ratio === 1.0) ? $fullUsd : round($fullUsd * $ratio, 2);
                $seRef['_scoped_bdt']    = ($ratio === 1.0) ? $fullBdt : round($fullBdt * $ratio, 2);
            }
            unset($seRef);

            // 4. Query Leads from `leads` table using DATE(l.created_at) (strictly invariant to approval/distribution date!)
            $leadPlatCase = $this->getLeadPlatformSql('l');
            $leadWhere = [
                "l.deleted_at IS NULL",
                "DATE(l.created_at) >= ?",
                "DATE(l.created_at) <= ?",
            ];
            $leadParams = [$startDate, $endDate];

            // Include automated leads and campaign leads
            $leadWhere[] = "(l.is_automated = 1 OR l.is_repibee = 1 OR l.is_brocure_leads = 1 OR (l.campaign_name IS NOT NULL AND TRIM(l.campaign_name) != ''))";

            if ($projectFilter !== '' && is_numeric($projectFilter)) {
                if ((int)$projectFilter === 0) {
                    $leadWhere[] = "(l.project_id IS NULL OR l.project_id = 0)";
                } else {
                    $leadWhere[] = "l.project_id = ?";
                    $leadParams[] = (int)$projectFilter;
                }
            }

            if ($accountQuery !== '') {
                $leadWhere[] = "(l.campaign_name LIKE ?)";
                $leadParams[] = '%' . $accountQuery . '%';
            }

            $leadSql = "
                SELECT 
                    COALESCE(l.project_id, 0) AS project_id,
                    DATE(l.created_at) AS lead_date,
                    ({$leadPlatCase}) AS norm_platform,
                    TRIM(COALESCE(l.campaign_name, '')) AS campaign_name,
                    COUNT(*) AS leads_count
                FROM `leads` l
                WHERE " . implode(" AND ", $leadWhere) . "
                GROUP BY COALESCE(l.project_id, 0), DATE(l.created_at), ({$leadPlatCase}), TRIM(COALESCE(l.campaign_name, ''))
            ";
            $lStmt = $pdo->prepare($leadSql);
            $lStmt->execute($leadParams);
            $leadGroups = $lStmt->fetchAll(PDO::FETCH_ASSOC);

            // Check which projects have dedicated 'WhatsApp Ads' spend entries in this range
            $projectsWithDedicatedWaSpend = [];
            foreach ($spendEntries as $se) {
                $normP = $this->normalizePlatformName((string)$se['platform']);
                if ($normP === 'WhatsApp Ads') {
                    $projectsWithDedicatedWaSpend[(int)$se['project_id']] = true;
                }
            }

            // Separate Organic Leads vs Paid Leads and aggregate by Project x Platform
            $organicLeadsCount = 0;
            $paidLeadsByProjPlat = [];     // key: "project_id|platform" => count
            $paidLeadsByDateProjPlat = []; // key: "date|project_id|platform" => count
            $paidLeadsByCampaign = [];     // key: "date|project_id|platform|lower_campaign" => count
            $paidLeadsByProjCamp = [];     // key: "project_id|platform|lower_campaign" => count

            foreach ($leadGroups as $lg) {
                $pid = (int)$lg['project_id'];
                $lDate = (string)$lg['lead_date'];
                $plat = (string)$lg['norm_platform'];
                $camp = strtolower(trim((string)$lg['campaign_name']));
                $cnt = (int)$lg['leads_count'];

                if ($plat === 'Organic') {
                    $organicLeadsCount += $cnt;
                    continue;
                }

                // If WhatsApp lead arrives and project does NOT have a separate 'WhatsApp Ads' spend entry,
                // attribute it under 'Meta Ads' (Click-to-WhatsApp Meta campaign) unless filtered specifically for WhatsApp Ads
                $effectivePlat = $plat;
                if ($plat === 'WhatsApp Ads' && empty($projectsWithDedicatedWaSpend[$pid]) && $platformFilter !== 'WhatsApp Ads') {
                    $effectivePlat = 'Meta Ads';
                }

                if ($platformFilter !== '' && $effectivePlat !== $platformFilter && $plat !== $platformFilter) {
                    continue;
                }

                $ppKey = "{$pid}|{$effectivePlat}";
                $dppKey = "{$lDate}|{$pid}|{$effectivePlat}";

                $paidLeadsByProjPlat[$ppKey] = ($paidLeadsByProjPlat[$ppKey] ?? 0) + $cnt;
                $paidLeadsByDateProjPlat[$dppKey] = ($paidLeadsByDateProjPlat[$dppKey] ?? 0) + $cnt;

                if ($camp !== '') {
                    $dcKey = "{$lDate}|{$pid}|{$effectivePlat}|{$camp}";
                    $pcKey = "{$pid}|{$effectivePlat}|{$camp}";
                    $paidLeadsByCampaign[$dcKey] = ($paidLeadsByCampaign[$dcKey] ?? 0) + $cnt;
                    $paidLeadsByProjCamp[$pcKey] = ($paidLeadsByProjCamp[$pcKey] ?? 0) + $cnt;
                }
            }

            // 5. Build Project x Platform Performance Rows (combining Spend rows + Paid Lead rows)
            $projPlatMatrix = [];
            $unmappedSpendRowsCount = 0;
            $unmappedSpendUsdTotal = 0.0;

            foreach ($spendEntries as $se) {
                $pid = (int)$se['project_id'];
                $plat = $this->normalizePlatformName((string)$se['platform']);
                $ppKey = "{$pid}|{$plat}";

                if (!isset($projPlatMatrix[$ppKey])) {
                    $projPlatMatrix[$ppKey] = [
                        'project_id'   => $pid,
                        'project_name' => $projectNames[$pid] ?? ($se['project_name'] ?: 'Common / Unmapped'),
                        'platform'     => $plat,
                        'spend_usd'    => 0.0,
                        'spend_bdt'    => 0.0,
                        'paid_leads'   => 0,
                        'entries_cnt'  => 0,
                    ];
                }

                $sUsd = (float)$se['_scoped_usd'];
                $sBdt = (float)$se['_scoped_bdt'];
                $projPlatMatrix[$ppKey]['spend_usd'] += $sUsd;
                $projPlatMatrix[$ppKey]['spend_bdt'] += $sBdt;
                $projPlatMatrix[$ppKey]['entries_cnt']++;

                $adIdentifier = trim((string)($se['ad_id'] ?? '') . (string)($se['ad_name'] ?? ''));
                if ($adIdentifier === '') {
                    $unmappedSpendRowsCount++;
                    $unmappedSpendUsdTotal += $sUsd;
                }
            }

            // Also include any Project x Platform that received Paid Leads in this date range even if spend hasn't been entered yet
            foreach ($paidLeadsByProjPlat as $ppKey => $leadCnt) {
                [$pidStr, $plat] = explode('|', $ppKey, 2);
                $pid = (int)$pidStr;
                if (!isset($projPlatMatrix[$ppKey])) {
                    $projPlatMatrix[$ppKey] = [
                        'project_id'   => $pid,
                        'project_name' => $projectNames[$pid] ?? 'Common / Unmapped',
                        'platform'     => $plat,
                        'spend_usd'    => 0.0,
                        'spend_bdt'    => 0.0,
                        'paid_leads'   => 0,
                        'entries_cnt'  => 0,
                    ];
                }
                $projPlatMatrix[$ppKey]['paid_leads'] = $leadCnt;
            }

            // Enrich Project x Platform rows with Budget, CPL, Remaining, Decision Signal
            $projectPlatformRows = [];
            $totalSpendUsd = 0.0;
            $totalSpendBdt = 0.0;
            $totalMatchedPaidLeads = 0;
            $totalAllocatedBudgetUsd = 0.0;

            // Project-wise aggregation for "Where is investment working?" chart
            $projectChartAgg = [];

            foreach ($projPlatMatrix as $ppKey => $row) {
                $pid = (int)$row['project_id'];
                $plat = $row['platform'];
                $spendUsd = round((float)$row['spend_usd'], 2);
                $spendBdt = round((float)$row['spend_bdt'], 2);
                $paidLeads = (int)$row['paid_leads'];

                // Resolve budget & target CPL for this Project x Platform
                $bInfo = $budgetsMap[$ppKey] ?? null;
                $targetCpl = $bInfo ? (float)$bInfo['target_cpl_usd'] : ($plat === 'Google Ads' ? 0.60 : 0.45);
                $dailyBudget = $bInfo ? (float)$bInfo['daily_budget_usd'] : ($spendUsd > 0 ? round(($spendUsd / max(1, $daysCount)) * 1.5, 2) : 0.00);

                // Scale budget by number of days in selected range (or monthly budget if monthly)
                if ($dateInfo['period'] === 'monthly' && $bInfo && $bInfo['monthly_budget_usd'] > 0) {
                    $periodBudget = round((float)$bInfo['monthly_budget_usd'], 2);
                } else {
                    $periodBudget = round($dailyBudget * $daysCount, 2);
                }

                $cpl = ($paidLeads > 0 && $spendUsd > 0) ? round($spendUsd / $paidLeads, 2) : null;
                $remaining = round($periodBudget - $spendUsd, 2);

                // Determine Decision Signal
                if ($spendUsd <= 0 && $paidLeads > 0) {
                    $decision = 'Spend pending entry';
                    $decisionTone = 'warning';
                } elseif ($paidLeads === 0 && $spendUsd > 0) {
                    $decision = '— / No leads';
                    $decisionTone = 'danger';
                } elseif ($cpl !== null) {
                    if ($cpl <= $targetCpl * 0.78) {
                        $decision = 'Efficient • review quality';
                        $decisionTone = 'success';
                    } elseif ($cpl <= $targetCpl) {
                        $decision = 'On target';
                        $decisionTone = 'success';
                    } else {
                        $decision = 'Above target CPL';
                        $decisionTone = 'warning';
                    }
                } else {
                    $decision = 'No activity';
                    $decisionTone = 'muted';
                }

                $totalSpendUsd += $spendUsd;
                $totalSpendBdt += $spendBdt;
                $totalMatchedPaidLeads += $paidLeads;
                $totalAllocatedBudgetUsd += $periodBudget;

                $projectPlatformRows[] = [
                    'project_id'   => $pid,
                    'project_name' => $row['project_name'],
                    'platform'     => $plat,
                    'budget_usd'   => $periodBudget,
                    'daily_budget' => $dailyBudget,
                    'spend_usd'    => $spendUsd,
                    'spend_bdt'    => $spendBdt,
                    'paid_leads'   => $paidLeads,
                    'cpl_usd'      => $cpl,
                    'target_cpl'   => $targetCpl,
                    'remaining'    => $remaining,
                    'decision'     => $decision,
                    'decision_tone'=> $decisionTone,
                ];

                if ($spendUsd > 0 || $paidLeads > 0) {
                    if (!isset($projectChartAgg[$pid])) {
                        $projectChartAgg[$pid] = [
                            'project_id'   => $pid,
                            'project_name' => $row['project_name'],
                            'spend_usd'    => 0.0,
                            'paid_leads'   => 0,
                        ];
                    }
                    $projectChartAgg[$pid]['spend_usd'] += $spendUsd;
                    $projectChartAgg[$pid]['paid_leads'] += $paidLeads;
                }
            }

            // Sort Project x Platform table by Spend DESC, then Paid Leads DESC
            usort($projectPlatformRows, function ($a, $b) {
                if ($b['spend_usd'] !== $a['spend_usd']) {
                    return $b['spend_usd'] <=> $a['spend_usd'];
                }
                return $b['paid_leads'] <=> $a['paid_leads'];
            });

            // Build "Where is investment working?" bars sorted by Spend DESC
            $investmentBars = array_values($projectChartAgg);
            usort($investmentBars, fn($a, $b) => ($b['spend_usd'] <=> $a['spend_usd']) ?: ($b['paid_leads'] <=> $a['paid_leads']));
            $maxProjectSpend = 0.0;
            foreach ($investmentBars as $ib) {
                if ($ib['spend_usd'] > $maxProjectSpend) {
                    $maxProjectSpend = (float)$ib['spend_usd'];
                }
            }
            foreach ($investmentBars as &$ib) {
                $ib['spend_usd'] = round((float)$ib['spend_usd'], 2);
                $ib['cpl_usd'] = ($ib['paid_leads'] > 0 && $ib['spend_usd'] > 0)
                    ? round($ib['spend_usd'] / $ib['paid_leads'], 2)
                    : null;
                $ib['width_pct'] = $maxProjectSpend > 0
                    ? max(8, round(($ib['spend_usd'] / $maxProjectSpend) * 100, 1))
                    : ($ib['paid_leads'] > 0 ? 25 : 8);
            }
            unset($ib);

            // 6. Enrich Individual Spend Entries & Build Ad Performance Ranking (Tab 2 & Tab 3)
            // Distribute each spend entry's scoped spend across its overlapping days for exact daily/multi-day lead attribution
            $groupSpendSum = [];
            $campGroupSpendSum = [];
            foreach ($spendEntries as $se) {
                $pid = (int)$se['project_id'];
                $plat = $this->normalizePlatformName((string)$se['platform']);
                $campLower = strtolower(trim((string)($se['campaign_name'] ?? '')));
                $ovStartTs = strtotime((string)$se['_overlap_start']);
                $ovEndTs = strtotime((string)$se['_overlap_end']);
                $ovDays = max(1, (int)$se['_overlap_days']);
                $dailyUsd = ((float)$se['_scoped_usd']) / $ovDays;

                for ($ts = $ovStartTs; $ts <= $ovEndTs; $ts = strtotime('+1 day', $ts)) {
                    $dStr = date('Y-m-d', $ts);
                    $gKey = "{$dStr}|{$pid}|{$plat}";
                    $groupSpendSum[$gKey] = ($groupSpendSum[$gKey] ?? 0.0) + $dailyUsd;
                    if ($campLower !== '') {
                        $cKey = "{$dStr}|{$pid}|{$plat}|{$campLower}";
                        $campGroupSpendSum[$cKey] = ($campGroupSpendSum[$cKey] ?? 0.0) + $dailyUsd;
                    }
                }
            }

            $enrichedSpendEntries = [];
            $adPerformanceGroups = [];

            foreach ($spendEntries as $se) {
                $pid = (int)$se['project_id'];
                $plat = $this->normalizePlatformName((string)$se['platform']);
                $eStart = (string)$se['_entry_start'];
                $eEnd = (string)$se['_entry_end'];
                $eDays = (int)$se['_entry_days'];
                $ovStartTs = strtotime((string)$se['_overlap_start']);
                $ovEndTs = strtotime((string)$se['_overlap_end']);
                $ovDays = max(1, (int)$se['_overlap_days']);
                $sUsd = (float)$se['_scoped_usd'];
                $sBdt = (float)$se['_scoped_bdt'];
                $dailyUsd = $sUsd / $ovDays;
                $campLower = strtolower(trim((string)($se['campaign_name'] ?? '')));
                $adName = trim((string)($se['ad_name'] ?? ''));
                $adId = trim((string)($se['ad_id'] ?? ''));
                $isMappedAd = ($adName !== '' || $adId !== '');

                // Sum matched paid leads across every overlapping day of this spend entry
                $matchedLeadsFloat = 0.0;
                for ($ts = $ovStartTs; $ts <= $ovEndTs; $ts = strtotime('+1 day', $ts)) {
                    $dStr = date('Y-m-d', $ts);
                    $dppKey = "{$dStr}|{$pid}|{$plat}";
                    $dcKey = "{$dStr}|{$pid}|{$plat}|{$campLower}";

                    if ($campLower !== '' && isset($paidLeadsByCampaign[$dcKey])) {
                        $totCampDateLeads = (int)$paidLeadsByCampaign[$dcKey];
                        $totCampDateSpend = (float)($campGroupSpendSum[$dcKey] ?? 0.0);
                        if ($totCampDateSpend > 0 && $dailyUsd > 0) {
                            $matchedLeadsFloat += $totCampDateLeads * ($dailyUsd / $totCampDateSpend);
                        } else {
                            $matchedLeadsFloat += $totCampDateLeads;
                        }
                    } elseif (isset($paidLeadsByDateProjPlat[$dppKey])) {
                        $totDateLeads = (int)$paidLeadsByDateProjPlat[$dppKey];
                        $totDateSpend = (float)($groupSpendSum[$dppKey] ?? 0.0);
                        if ($totDateSpend > 0 && $dailyUsd > 0) {
                            $matchedLeadsFloat += $totDateLeads * ($dailyUsd / $totDateSpend);
                        } else {
                            $matchedLeadsFloat += $totDateLeads;
                        }
                    }
                }
                $matchedLeads = (int)round($matchedLeadsFloat);

                $entryCpl = ($matchedLeads > 0 && $sUsd > 0) ? round($sUsd / $matchedLeads, 2) : null;

                $isRange = ($eEnd > $eStart);
                $dateLabel = $isRange
                    ? (date('d M Y', strtotime($eStart)) . ' – ' . date('d M Y', strtotime($eEnd)) . " ({$eDays}d)")
                    : date('d M Y', strtotime($eStart));

                $enrichedSpendEntries[] = [
                    'id'               => (int)$se['id'],
                    'spend_date'       => $eStart,
                    'spend_end_date'   => $eEnd,
                    'entry_days'       => $eDays,
                    'overlap_days'     => $ovDays,
                    'is_range'         => $isRange,
                    'is_prorated'      => (bool)$se['_is_prorated'],
                    'spend_date_label' => $dateLabel,
                    'platform'         => $plat,
                    'ad_account'       => $se['ad_account'],
                    'project_id'       => $pid,
                    'project_name'     => $projectNames[$pid] ?? ($se['project_name'] ?: 'Common / Unmapped'),
                    'campaign_name'    => $se['campaign_name'] ?? '',
                    'ad_set_name'      => $se['ad_set_name'] ?? '',
                    'ad_name'          => $adName,
                    'ad_id'            => $adId,
                    'is_mapped_ad'     => $isMappedAd,
                    'currency'         => $se['currency'] ?? 'USD',
                    'actual_spend'     => (float)$se['actual_spend'],
                    'full_spend_usd'   => round((float)$se['spend_usd'], 2),
                    'spend_usd'        => round($sUsd, 2),
                    'fx_rate'          => (float)$se['fx_rate'],
                    'bdt_equivalent'   => round($sBdt, 2),
                    'source_reference' => $se['source_reference'] ?? '',
                    'notes'            => $se['notes'] ?? '',
                    'paid_leads'       => $matchedLeads,
                    'cpl_usd'          => $entryCpl,
                    'updated_by_name'  => $se['updated_by_name'] ?: 'Authorized Operator',
                    'updated_at'       => $se['updated_at'],
                ];

                // Group for Ad Performance tab
                $adGroupKey = $isMappedAd
                    ? "{$pid}|{$plat}|" . strtolower($adName . '|' . $adId)
                    : "{$pid}|{$plat}|__unmapped__";

                $ppKey = "{$pid}|{$plat}";
                $bInfo = $budgetsMap[$ppKey] ?? null;
                $targetCpl = $bInfo ? (float)$bInfo['target_cpl_usd'] : ($plat === 'Google Ads' ? 0.60 : 0.45);

                if (!isset($adPerformanceGroups[$adGroupKey])) {
                    $adPerformanceGroups[$adGroupKey] = [
                        'is_mapped'    => $isMappedAd,
                        'ad_name'      => $isMappedAd ? ($adName ?: ($se['campaign_name'] ?: 'Ad Entry')) : 'Project-only • Unmapped',
                        'ad_id'        => $isMappedAd ? ($adId ?: 'MAPPED-AD') : '',
                        'project_id'   => $pid,
                        'project_name' => $projectNames[$pid] ?? 'Common / Unmapped',
                        'platform'     => $plat,
                        'spend_usd'    => 0.0,
                        'paid_leads'   => 0,
                        'target_cpl'   => $targetCpl,
                    ];
                }
                $adPerformanceGroups[$adGroupKey]['spend_usd'] += $sUsd;
                $adPerformanceGroups[$adGroupKey]['paid_leads'] += $matchedLeads;
            }

            // Finalize Ad Performance rows & sort mapped ads by CPL ASC (best performing first), unmapped at bottom
            $adPerformanceRows = [];
            foreach ($adPerformanceGroups as $ag) {
                $sUsd = round((float)$ag['spend_usd'], 2);
                $leads = (int)$ag['paid_leads'];
                $targetCpl = (float)$ag['target_cpl'];

                if (!$ag['is_mapped']) {
                    $cpl = null;
                    $signal = 'Map ad before comparing';
                    $signalTone = 'warning';
                } elseif ($leads === 0) {
                    $cpl = null;
                    $signal = '— / No leads';
                    $signalTone = 'danger';
                } else {
                    $cpl = round($sUsd / $leads, 2);
                    if ($cpl < $targetCpl * 0.85) {
                        $signal = 'Below target CPL';
                        $signalTone = 'success';
                    } elseif ($cpl <= $targetCpl) {
                        $signal = 'On target';
                        $signalTone = 'success';
                    } else {
                        $signal = 'Review creative / intent';
                        $signalTone = 'warning';
                    }
                }

                $adPerformanceRows[] = [
                    'is_mapped'    => $ag['is_mapped'],
                    'ad_name'      => $ag['ad_name'],
                    'ad_id'        => $ag['ad_id'],
                    'project_id'   => $ag['project_id'],
                    'project_name' => $ag['project_name'],
                    'platform'     => $ag['platform'],
                    'spend_usd'    => $sUsd,
                    'paid_leads'   => $leads,
                    'cpl_usd'      => $cpl,
                    'target_cpl'   => $ag['is_mapped'] ? $targetCpl : null,
                    'signal'       => $signal,
                    'signal_tone'  => $signalTone,
                ];
            }

            usort($adPerformanceRows, function ($a, $b) {
                if ($a['is_mapped'] !== $b['is_mapped']) {
                    return $a['is_mapped'] ? -1 : 1;
                }
                if ($a['cpl_usd'] === null && $b['cpl_usd'] === null) {
                    return $b['spend_usd'] <=> $a['spend_usd'];
                }
                if ($a['cpl_usd'] === null) return 1;
                if ($b['cpl_usd'] === null) return -1;
                return $a['cpl_usd'] <=> $b['cpl_usd'];
            });

            // Generate Smart Decision Notes for Ad Performance tab
            $decisionNotes = [];
            $bestRow = null;
            $worstRow = null;
            foreach ($projectPlatformRows as $ppr) {
                if ($ppr['cpl_usd'] !== null && $ppr['spend_usd'] > 0) {
                    if ($bestRow === null || $ppr['cpl_usd'] < $bestRow['cpl_usd']) {
                        $bestRow = $ppr;
                    }
                    if ($ppr['cpl_usd'] > $ppr['target_cpl'] && ($worstRow === null || $ppr['cpl_usd'] > $worstRow['cpl_usd'])) {
                        $worstRow = $ppr;
                    }
                }
            }
            if ($bestRow) {
                $decisionNotes[] = sprintf(
                    "%s: lowest matched CPL at $%s (%d paid leads from $%s spend). Check qualified leads and sales outcomes before increasing budget.",
                    $bestRow['project_name'],
                    number_format((float)$bestRow['cpl_usd'], 2),
                    (int)$bestRow['paid_leads'],
                    number_format((float)$bestRow['spend_usd'], 2)
                );
            }
            if ($worstRow) {
                $decisionNotes[] = sprintf(
                    "%s: project CPL $%s versus target $%s on %s. Review targeting and lead quality%s.",
                    $worstRow['project_name'],
                    number_format((float)$worstRow['cpl_usd'], 2),
                    number_format((float)$worstRow['target_cpl'], 2),
                    $worstRow['platform'],
                    $unmappedSpendRowsCount > 0 ? '; complete missing ad mapping' : ''
                );
            }
            if (empty($decisionNotes)) {
                $decisionNotes[] = "Add daily ad spend entries and map ad IDs to compare creative-level CPL against project target thresholds.";
            }

            // Overall KPIs
            $totalSpendUsd = round($totalSpendUsd, 2);
            $totalSpendBdt = round($totalSpendBdt, 2);
            $weightedCpl = ($totalMatchedPaidLeads > 0 && $totalSpendUsd > 0)
                ? round($totalSpendUsd / $totalMatchedPaidLeads, 2)
                : null;
            $budgetUtilisationPct = ($totalAllocatedBudgetUsd > 0 && $totalSpendUsd > 0)
                ? round(($totalSpendUsd / $totalAllocatedBudgetUsd) * 100, 1)
                : 0.0;
            $effectiveFxRate = ($totalSpendUsd > 0 && $totalSpendBdt > 0)
                ? round($totalSpendBdt / $totalSpendUsd, 2)
                : 133.00;

            return [
                'date_info' => $dateInfo,
                'kpis'      => [
                    'actual_spend_usd'       => $totalSpendUsd,
                    'actual_spend_bdt'       => $totalSpendBdt,
                    'effective_fx_rate'      => $effectiveFxRate,
                    'matched_paid_leads'     => $totalMatchedPaidLeads,
                    'weighted_cpl_usd'       => $weightedCpl,
                    'total_budget_usd'       => round($totalAllocatedBudgetUsd, 2),
                    'total_remaining_usd'    => round($totalAllocatedBudgetUsd - $totalSpendUsd, 2),
                    'budget_utilisation_pct' => $budgetUtilisationPct,
                    'unmapped_spend_rows'    => $unmappedSpendRowsCount,
                    'unmapped_spend_usd'     => round($unmappedSpendUsdTotal, 2),
                    'organic_leads_excluded' => $organicLeadsCount,
                ],
                'investment_bars'       => $investmentBars,
                'project_platform_rows' => $projectPlatformRows,
                'spend_entries'         => $enrichedSpendEntries,
                'ad_performance_rows'   => $adPerformanceRows,
                'decision_notes'        => $decisionNotes,
            ];
    }

    /**
     * Export Professionally Formatted Excel (.xlsx) Workbook using PhpSpreadsheet
     */
    public function exportExcel(): void
    {
        $this->checkAccess(false);

        $filters = [
            'period'     => $_GET['period'] ?? 'daily',
            'start_date' => $_GET['start_date'] ?? '',
            'end_date'   => $_GET['end_date'] ?? '',
            'project_id' => $_GET['project_id'] ?? '',
            'platform'   => $_GET['platform'] ?? '',
            'account_q'  => trim((string)($_GET['account_q'] ?? '')),
        ];

        $data = $this->computeDashboardData($filters);
        $dateInfo = $data['date_info'] ?? [];
        $kpis = $data['kpis'] ?? [];
        $rows = $data['project_platform_rows'] ?? [];
        $spendEntries = $data['spend_entries'] ?? [];

        $currentUserName = $_SESSION['user']['name']
            ?? $_SESSION['user']['full_name']
            ?? $_SESSION['user']['username']
            ?? 'Authorized Operator';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        // =====================================================================
        // SHEET 1: Leads Investment Statement (Project x Platform)
        // =====================================================================
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Investment Statement');

        // Brand Header Row 1
        $sheet->setCellValue('A1', 'THE PREMIUM HOMES LTD. — LEADS INVESTMENT STATEMENT');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0A7C48'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Scope Metadata Row 2
        $scopeLabel = sprintf(
            'Scope: %s  |  Timezone: Asia/Dhaka  |  Currency: USD  |  Generated by: %s • %s',
            $dateInfo['label'] ?? date('d M Y'),
            $currentUserName,
            date('d M Y, h:i A')
        );
        $sheet->setCellValue('A2', $scopeLabel);
        $sheet->mergeCells('A2:H2');
        $sheet->getStyle('A2:H2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '1E293B']],
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E8F5EE'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(22);

        // Table Header Row 4
        $headers = [
            'S/L',
            'Project',
            'Platform',
            'Actual Spend (USD)',
            'Paid Leads',
            'CPL (USD)',
            'Budget (USD)',
            'Remaining (USD)',
        ];
        $sheet->fromArray($headers, null, 'A4');
        $sheet->getStyle('A4:H4')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '0A7C48']],
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E8F5EE'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color'       => ['rgb' => 'C6E6D4'],
                ],
            ],
        ]);
        $sheet->getStyle('B4:C4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getRowDimension(4)->setRowHeight(26);

        $rowNum = 5;
        foreach ($rows as $idx => $r) {
            $platShort = $r['platform'] === 'Meta Ads' ? 'Meta' : $r['platform'];
            $sheet->setCellValue('A' . $rowNum, $idx + 1);
            $sheet->setCellValue('B' . $rowNum, $r['project_name']);
            $sheet->setCellValue('C' . $rowNum, $platShort);
            $sheet->setCellValue('D' . $rowNum, (float)$r['spend_usd']);
            $sheet->setCellValue('E' . $rowNum, (int)$r['paid_leads']);
            if ($r['cpl_usd'] !== null) {
                $sheet->setCellValue('F' . $rowNum, (float)$r['cpl_usd']);
                $sheet->getStyle('F' . $rowNum)->getNumberFormat()->setFormatCode('$#,##0.00');
            } else {
                $sheet->setCellValue('F' . $rowNum, '—');
                $sheet->getStyle('F' . $rowNum)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            }
            $sheet->setCellValue('G' . $rowNum, (float)$r['budget_usd']);
            $sheet->setCellValue('H' . $rowNum, (float)$r['remaining']);

            $sheet->getStyle("D{$rowNum}")->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle("E{$rowNum}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("G{$rowNum}:H{$rowNum}")->getNumberFormat()->setFormatCode('$#,##0.00');

            // Alternating Zebra Fill
            if ($idx % 2 === 1) {
                $sheet->getStyle("A{$rowNum}:H{$rowNum}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }

            $sheet->getRowDimension($rowNum)->setRowHeight(21);
            $rowNum++;
        }

        $lastDataRow = max(4, $rowNum - 1);
        if ($lastDataRow >= 5) {
            $sheet->getStyle("A5:A{$lastDataRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E5:E{$lastDataRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("A5:H{$lastDataRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color'       => ['rgb' => 'E2E8F0'],
                    ],
                ],
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ]);
        }

        // Grand Total Row (appears once at the bottom)
        $sheet->setCellValue('A' . $rowNum, '');
        $sheet->setCellValue('B' . $rowNum, 'Grand total');
        $sheet->setCellValue('C' . $rowNum, 'All');
        $sheet->setCellValue('D' . $rowNum, (float)($kpis['actual_spend_usd'] ?? 0));
        $sheet->setCellValue('E' . $rowNum, (int)($kpis['matched_paid_leads'] ?? 0));
        if (($kpis['weighted_cpl_usd'] ?? null) !== null) {
            $sheet->setCellValue('F' . $rowNum, (float)$kpis['weighted_cpl_usd']);
            $sheet->getStyle('F' . $rowNum)->getNumberFormat()->setFormatCode('$#,##0.00');
        } else {
            $sheet->setCellValue('F' . $rowNum, '—');
            $sheet->getStyle('F' . $rowNum)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        }
        $sheet->setCellValue('G' . $rowNum, (float)($kpis['total_budget_usd'] ?? 0));
        $sheet->setCellValue('H' . $rowNum, (float)($kpis['total_remaining_usd'] ?? 0));

        $sheet->getStyle("D{$rowNum}")->getNumberFormat()->setFormatCode('$#,##0.00');
        $sheet->getStyle("E{$rowNum}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("E{$rowNum}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("G{$rowNum}:H{$rowNum}")->getNumberFormat()->setFormatCode('$#,##0.00');

        $sheet->getStyle("A{$rowNum}:H{$rowNum}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '0A7C48']],
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E8F5EE'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color'       => ['rgb' => '0A7C48'],
                ],
            ],
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension($rowNum)->setRowHeight(25);

        // Footer Summary Lines
        $summaryRow1 = $rowNum + 2;
        $sheet->setCellValue('A' . $summaryRow1, sprintf(
            'Total spend: USD %s  •  BDT equivalent: BDT %s at effective rate %s',
            number_format((float)($kpis['actual_spend_usd'] ?? 0), 2),
            number_format((float)($kpis['actual_spend_bdt'] ?? 0), 2),
            number_format((float)($kpis['effective_fx_rate'] ?? 133), 2)
        ));
        $sheet->mergeCells("A{$summaryRow1}:H{$summaryRow1}");
        $sheet->getStyle("A{$summaryRow1}")->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('0A7C48');

        $summaryRow2 = $rowNum + 3;
        $sheet->setCellValue('A' . $summaryRow2, sprintf(
            'Method: summed spend ÷ summed matched paid leads. Organic leads excluded (%d). Ad-unmapped spend: $%s.',
            (int)($kpis['organic_leads_excluded'] ?? 0),
            number_format((float)($kpis['unmapped_spend_usd'] ?? 0), 2)
        ));
        $sheet->mergeCells("A{$summaryRow2}:H{$summaryRow2}");
        $sheet->getStyle("A{$summaryRow2}")->getFont()->setSize(9)->getColor()->setRGB('64748B');

        // Column widths for Sheet 1
        $widths = [
            'A' => 8,
            'B' => 34,
            'C' => 16,
            'D' => 20,
            'E' => 15,
            'F' => 15,
            'G' => 18,
            'H' => 18,
        ];
        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        // Repeat rows 1-4 on every printed Excel page as well
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 4);
        $sheet->freezePane('A5');

        // =====================================================================
        // SHEET 2: Daily Spend Entries (Ad-level breakdown)
        // =====================================================================
        if (!empty($spendEntries)) {
            $sheet2 = $spreadsheet->createSheet();
            $sheet2->setTitle('Daily Spend Entries');

            $sheet2->setCellValue('A1', 'THE PREMIUM HOMES LTD. — DAILY AD SPEND ENTRIES');
            $sheet2->mergeCells('A1:L1');
            $sheet2->getStyle('A1:L1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0A7C48'],
                ],
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ]);
            $sheet2->getRowDimension(1)->setRowHeight(28);

            $headers2 = ['Date', 'Project', 'Platform', 'Ad Account', 'Campaign', 'Ad / Ad ID', 'Spend (USD)', 'FX Rate', 'BDT Equivalent', 'Paid Leads', 'CPL (USD)', 'Updated By'];
            $sheet2->fromArray($headers2, null, 'A3');
            $sheet2->getStyle('A3:L3')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '0A7C48']],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E8F5EE'],
                ],
            ]);
            $sheet2->getRowDimension(3)->setRowHeight(24);

            $r2 = 4;
            foreach ($spendEntries as $se) {
                $adLabel = $se['ad_name']
                    ? ($se['ad_id'] && $se['ad_id'] !== $se['ad_name'] ? "{$se['ad_name']} ({$se['ad_id']})" : $se['ad_name'])
                    : ($se['ad_id'] ?: 'Project-only • Unmapped');
                $sheet2->setCellValue('A' . $r2, $se['spend_date_label']);
                $sheet2->setCellValue('B' . $r2, $se['project_name']);
                $sheet2->setCellValue('C' . $r2, $se['platform']);
                $sheet2->setCellValue('D' . $r2, $se['ad_account']);
                $sheet2->setCellValue('E' . $r2, $se['campaign_name']);
                $sheet2->setCellValue('F' . $r2, $adLabel);
                $sheet2->setCellValue('G' . $r2, (float)$se['spend_usd']);
                $sheet2->setCellValue('H' . $r2, (float)$se['fx_rate']);
                $sheet2->setCellValue('I' . $r2, (float)$se['bdt_equivalent']);
                $sheet2->setCellValue('J' . $r2, (int)$se['paid_leads']);
                $sheet2->setCellValue('K' . $r2, $se['cpl_usd'] !== null ? (float)$se['cpl_usd'] : '—');
                $sheet2->setCellValue('L' . $r2, $se['updated_by_name']);

                $sheet2->getStyle("G{$r2}")->getNumberFormat()->setFormatCode('$#,##0.00');
                $sheet2->getStyle("H{$r2}:I{$r2}")->getNumberFormat()->setFormatCode('#,##0.00');
                if ($se['cpl_usd'] !== null) {
                    $sheet2->getStyle("K{$r2}")->getNumberFormat()->setFormatCode('$#,##0.00');
                }
                $r2++;
            }
            foreach (range('A', 'L') as $c) {
                $sheet2->getColumnDimension($c)->setAutoSize(true);
            }
            $spreadsheet->setActiveSheetIndex(0);
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $fileName = 'TPHL_Leads_Investment_Statement_' . ($dateInfo['start_date'] ?? date('Y-m-d')) . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * AJAX: Real-time Match Preview for "Add daily / custom-range actual spend" form
     * Queries `leads` on `DATE(created_at)` between `:spend_date` and `:spend_end_date` + `project_id` + `platform`
     */
    public function ajaxMatchPreview(): void
    {
        $this->checkAccess(true);
        header('Content-Type: application/json; charset=utf-8');

        try {
            $pdo = $this->getPdo();

            $spendDate = trim((string)($_GET['spend_date'] ?? date('Y-m-d')));
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $spendDate)) {
                $spendDate = date('Y-m-d');
            }
            $rawEndDate = trim((string)($_GET['spend_end_date'] ?? ''));
            $spendEndDate = (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawEndDate) && $rawEndDate >= $spendDate)
                ? $rawEndDate
                : $spendDate;
            $daysCount = max(1, (int)round((strtotime($spendEndDate) - strtotime($spendDate)) / 86400) + 1);

            $projectId = (int)($_GET['project_id'] ?? 0);
            $platform = $this->normalizePlatformName((string)($_GET['platform'] ?? 'Meta Ads'));
            $campaignName = trim((string)($_GET['campaign_name'] ?? ''));
            $adId = trim((string)($_GET['ad_id'] ?? ''));
            $adAccount = trim((string)($_GET['ad_account'] ?? ''));
            $currency = strtoupper(trim((string)($_GET['currency'] ?? 'USD')));
            $actualSpend = (float)($_GET['actual_spend'] ?? 0);
            $fxRate = max(1.0, (float)($_GET['fx_rate'] ?? 133.00));

            $spendUsd = ($currency === 'BDT') ? round($actualSpend / $fxRate, 2) : round($actualSpend, 2);
            $bdtEquivalent = ($currency === 'BDT') ? round($actualSpend, 2) : round($actualSpend * $fxRate, 2);

            $leadPlatCase = $this->getLeadPlatformSql('l');

            $where = [
                "l.deleted_at IS NULL",
                "DATE(l.created_at) >= ?",
                "DATE(l.created_at) <= ?",
                "l.project_id = ?",
                "(l.is_automated = 1 OR l.is_repibee = 1 OR l.is_brocure_leads = 1 OR (l.campaign_name IS NOT NULL AND TRIM(l.campaign_name) != ''))",
            ];
            $params = [$spendDate, $spendEndDate, $projectId];

            if ($platform === 'Meta Ads') {
                // Meta Ads matches Meta Form, Meta Inbox, and WhatsApp CTWA leads
                $where[] = "({$leadPlatCase}) IN ('Meta Ads', 'WhatsApp Ads')";
            } else {
                $where[] = "({$leadPlatCase}) = ?";
                $params[] = $platform;
            }

            // First count all project+platform paid leads across the selected date(s)
            $sqlAll = "SELECT COUNT(*) FROM `leads` l WHERE " . implode(" AND ", $where);
            $stmtAll = $pdo->prepare($sqlAll);
            $stmtAll->execute($params);
            $projectPlatformLeads = (int)$stmtAll->fetchColumn();

            // If campaign is specified, check if campaign-specific leads exist
            $matchedLeads = $projectPlatformLeads;
            $scopePrefix = $daysCount > 1 ? "Date range ({$daysCount} days)" : "Same date";
            $matchBasis = "{$scopePrefix} + project + platform" . ($adId !== '' ? " + ad ID" : "");

            if ($campaignName !== '') {
                $campWhere = $where;
                $campParams = $params;
                $campWhere[] = "LOWER(TRIM(COALESCE(l.campaign_name, ''))) LIKE ?";
                $campParams[] = '%' . strtolower($campaignName) . '%';

                $stmtCamp = $pdo->prepare("SELECT COUNT(*) FROM `leads` l WHERE " . implode(" AND ", $campWhere));
                $stmtCamp->execute($campParams);
                $campCount = (int)$stmtCamp->fetchColumn();
                if ($campCount > 0) {
                    $matchedLeads = $campCount;
                    $matchBasis = "{$scopePrefix} + project + platform + campaign" . ($adId !== '' ? " + ad ID" : "");
                }
            }

            // Check if an exact duplicate entry already exists for (spend_date, spend_end_date, platform, ad_account, project_id, ad_id)
            $dupStmt = $pdo->prepare("
                SELECT id, spend_usd, actual_spend
                FROM `lead_investment_spends`
                WHERE deleted_at IS NULL
                  AND spend_date = ?
                  AND COALESCE(spend_end_date, spend_date) = ?
                  AND platform = ?
                  AND ad_account = ?
                  AND project_id = ?
                  AND COALESCE(ad_id, '') = ?
                LIMIT 1
            ");
            $dupStmt->execute([$spendDate, $spendEndDate, $platform, $adAccount, $projectId, $adId]);
            $existingEntry = $dupStmt->fetch(PDO::FETCH_ASSOC);

            // Also check if another overlapping entry exists on partially overlapping dates
            $ovStmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM `lead_investment_spends`
                WHERE deleted_at IS NULL
                  AND spend_date <= ?
                  AND COALESCE(spend_end_date, spend_date) >= ?
                  AND platform = ?
                  AND ad_account = ?
                  AND project_id = ?
                  AND COALESCE(ad_id, '') = ?
                  AND NOT (spend_date = ? AND COALESCE(spend_end_date, spend_date) = ?)
            ");
            $ovStmt->execute([$spendEndDate, $spendDate, $platform, $adAccount, $projectId, $adId, $spendDate, $spendEndDate]);
            $overlappingCount = (int)$ovStmt->fetchColumn();

            $cplUsd = ($matchedLeads > 0 && $spendUsd > 0) ? round($spendUsd / $matchedLeads, 2) : null;

            echo json_encode([
                'status' => 'success',
                'data'   => [
                    'matched_paid_leads'    => $matchedLeads,
                    'project_date_leads'    => $projectPlatformLeads,
                    'days_count'            => $daysCount,
                    'spend_usd'             => $spendUsd,
                    'daily_avg_usd'         => round($spendUsd / $daysCount, 2),
                    'bdt_equivalent'        => $bdtEquivalent,
                    'calculated_cpl_usd'    => $cplUsd,
                    'match_basis'           => $matchBasis,
                    'existing_duplicate_id' => $existingEntry ? (int)$existingEntry['id'] : null,
                    'overlapping_count'     => $overlappingCount,
                ],
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * AJAX: Save or Update Daily / Custom-Range Actual Spend Entry (with duplicate protection & audit history)
     */
    public function ajaxSaveSpend(): void
    {
        $this->checkAccess(true);
        header('Content-Type: application/json; charset=utf-8');

        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $pdo = $this->getPdo();

            $editId = isset($input['id']) && (int)$input['id'] > 0 ? (int)$input['id'] : 0;
            $spendDate = trim((string)($input['spend_date'] ?? ''));
            $rawEndDate = trim((string)($input['spend_end_date'] ?? ''));
            $platform = $this->normalizePlatformName((string)($input['platform'] ?? 'Meta Ads'));
            $adAccount = trim((string)($input['ad_account'] ?? ''));
            $projectId = (int)($input['project_id'] ?? 0);
            $campaignName = trim((string)($input['campaign_name'] ?? ''));
            $adSetName = trim((string)($input['ad_set_name'] ?? ''));
            $adRawInput = trim((string)($input['ad_input'] ?? ''));
            $adName = trim((string)($input['ad_name'] ?? ''));
            $adId = trim((string)($input['ad_id'] ?? ''));

            // If user entered combined "RC Video 01 / DEMO-AD-001" in single field, split cleanly
            if ($adRawInput !== '' && $adName === '' && $adId === '') {
                if (strpos($adRawInput, '/') !== false) {
                    [$partName, $partId] = array_map('trim', explode('/', $adRawInput, 2));
                    $adName = $partName;
                    $adId = $partId;
                } else {
                    $adName = $adRawInput;
                    $adId = $adRawInput;
                }
            }

            $currency = strtoupper(trim((string)($input['currency'] ?? 'USD')));
            if (!in_array($currency, ['USD', 'BDT'], true)) {
                $currency = 'USD';
            }

            $actualSpend = (float)($input['actual_spend'] ?? 0);
            $fxRate = max(1.0, (float)($input['fx_rate'] ?? 133.00));
            $sourceRef = trim((string)($input['source_reference'] ?? ''));
            $notes = trim((string)($input['notes'] ?? ''));

            if ($spendDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $spendDate)) {
                throw new InvalidArgumentException("Please provide a valid Spend Start Date.");
            }
            $spendEndDate = ($rawEndDate !== '') ? $rawEndDate : $spendDate;
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $spendEndDate)) {
                throw new InvalidArgumentException("Please provide a valid Spend End Date.");
            }
            if ($spendEndDate < $spendDate) {
                throw new InvalidArgumentException("Spend End Date cannot be earlier than Start Date.");
            }
            if ($adAccount === '') {
                throw new InvalidArgumentException("Ad Account is required.");
            }
            if ($projectId <= 0) {
                throw new InvalidArgumentException("Please select a Project.");
            }
            if ($campaignName === '') {
                throw new InvalidArgumentException("Campaign name is required.");
            }
            if ($actualSpend <= 0) {
                throw new InvalidArgumentException("Actual spend must be greater than 0.");
            }

            $spendUsd = ($currency === 'BDT') ? round($actualSpend / $fxRate, 2) : round($actualSpend, 2);
            $bdtEquivalent = ($currency === 'BDT') ? round($actualSpend, 2) : round($actualSpend * $fxRate, 2);
            $daysCount = max(1, (int)round((strtotime($spendEndDate) - strtotime($spendDate)) / 86400) + 1);
            $rangeSuffix = $daysCount > 1
                ? sprintf(' [Range: %s – %s (%dd)]', date('d M Y', strtotime($spendDate)), date('d M Y', strtotime($spendEndDate)), $daysCount)
                : '';

            $userId = (int)($_SESSION['user']['id'] ?? 0);
            $userName = $_SESSION['user']['name']
                ?? $_SESSION['user']['full_name']
                ?? $_SESSION['user']['username']
                ?? 'Authorized Operator';

            // Check duplicate entry if not explicitly editing by ID
            if ($editId === 0) {
                $dupStmt = $pdo->prepare("
                    SELECT * FROM `lead_investment_spends`
                    WHERE deleted_at IS NULL
                      AND spend_date = ?
                      AND COALESCE(spend_end_date, spend_date) = ?
                      AND platform = ?
                      AND ad_account = ?
                      AND project_id = ?
                      AND COALESCE(ad_id, '') = ?
                    LIMIT 1
                ");
                $dupStmt->execute([$spendDate, $spendEndDate, $platform, $adAccount, $projectId, $adId]);
                $dupRow = $dupStmt->fetch(PDO::FETCH_ASSOC);
                if ($dupRow) {
                    $editId = (int)$dupRow['id'];
                }
            }

            if ($editId > 0) {
                // Fetch existing record for audit history
                $oldStmt = $pdo->prepare("SELECT * FROM `lead_investment_spends` WHERE id = ? LIMIT 1");
                $oldStmt->execute([$editId]);
                $oldRow = $oldStmt->fetch(PDO::FETCH_ASSOC);

                $updStmt = $pdo->prepare("
                    UPDATE `lead_investment_spends`
                    SET `spend_date` = ?,
                        `spend_end_date` = ?,
                        `platform` = ?,
                        `ad_account` = ?,
                        `project_id` = ?,
                        `campaign_name` = ?,
                        `ad_set_name` = ?,
                        `ad_name` = ?,
                        `ad_id` = ?,
                        `currency` = ?,
                        `actual_spend` = ?,
                        `spend_usd` = ?,
                        `fx_rate` = ?,
                        `bdt_equivalent` = ?,
                        `source_reference` = ?,
                        `notes` = ?,
                        `updated_by` = ?,
                        `updated_by_name` = ?,
                        `updated_at` = NOW()
                    WHERE `id` = ?
                ");
                $updStmt->execute([
                    $spendDate, $spendEndDate, $platform, $adAccount, $projectId,
                    $campaignName, $adSetName, $adName, $adId,
                    $currency, $actualSpend, $spendUsd, $fxRate, $bdtEquivalent,
                    $sourceRef, $notes, $userId, $userName, $editId
                ]);

                // Record audit log
                $auditNote = ($notes ?: 'Updated spend entry') . $rangeSuffix;
                $histStmt = $pdo->prepare("
                    INSERT INTO `lead_investment_spend_history`
                    (`spend_id`, `action_type`, `old_spend_usd`, `new_spend_usd`, `old_fx_rate`, `new_fx_rate`, `old_ad_id`, `new_ad_id`, `change_note`, `changed_by`, `changed_by_name`)
                    VALUES (?, 'updated', ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $histStmt->execute([
                    $editId,
                    $oldRow ? $oldRow['spend_usd'] : null,
                    $spendUsd,
                    $oldRow ? $oldRow['fx_rate'] : null,
                    $fxRate,
                    $oldRow ? $oldRow['ad_id'] : null,
                    $adId,
                    $auditNote,
                    $userId,
                    $userName,
                ]);

                echo json_encode([
                    'status'  => 'success',
                    'action'  => 'updated',
                    'id'      => $editId,
                    'message' => 'Existing spend entry updated (no double counting).',
                ]);
                exit;
            }

            // Insert new spend entry
            $insStmt = $pdo->prepare("
                INSERT INTO `lead_investment_spends`
                (`spend_date`, `spend_end_date`, `platform`, `ad_account`, `project_id`, `campaign_name`, `ad_set_name`, `ad_name`, `ad_id`,
                 `currency`, `actual_spend`, `spend_usd`, `fx_rate`, `bdt_equivalent`, `source_reference`, `notes`,
                 `created_by`, `updated_by`, `updated_by_name`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insStmt->execute([
                $spendDate, $spendEndDate, $platform, $adAccount, $projectId,
                $campaignName, $adSetName, $adName, $adId,
                $currency, $actualSpend, $spendUsd, $fxRate, $bdtEquivalent,
                $sourceRef, $notes, $userId, $userId, $userName
            ]);
            $newId = (int)$pdo->lastInsertId();

            // Record initial creation in history
            $auditNote = ($notes ?: 'Initial spend entry') . $rangeSuffix;
            $histStmt = $pdo->prepare("
                INSERT INTO `lead_investment_spend_history`
                (`spend_id`, `action_type`, `old_spend_usd`, `new_spend_usd`, `old_fx_rate`, `new_fx_rate`, `old_ad_id`, `new_ad_id`, `change_note`, `changed_by`, `changed_by_name`)
                VALUES (?, 'created', NULL, ?, NULL, ?, NULL, ?, ?, ?, ?)
            ");
            $histStmt->execute([
                $newId, $spendUsd, $fxRate, $adId,
                $auditNote,
                $userId, $userName
            ]);

            echo json_encode([
                'status'  => 'success',
                'action'  => 'created',
                'id'      => $newId,
                'message' => $daysCount > 1
                    ? "Custom range spend ({$daysCount} days) saved successfully!"
                    : 'Daily actual spend saved successfully!',
            ]);
        } catch (Throwable $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * AJAX: Soft-delete a spend entry
     */
    public function ajaxDeleteSpend(): void
    {
        $this->checkAccess(true);
        header('Content-Type: application/json; charset=utf-8');

        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) {
                throw new InvalidArgumentException("Invalid spend entry ID.");
            }

            $pdo = $this->getPdo();
            $stmt = $pdo->prepare("UPDATE `lead_investment_spends` SET `deleted_at` = NOW() WHERE `id` = ?");
            $stmt->execute([$id]);

            echo json_encode([
                'status'  => 'success',
                'message' => 'Spend entry removed.',
            ]);
        } catch (Throwable $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * AJAX: Fetch Edit History for a Spend Entry
     */
    public function ajaxSpendHistory(): void
    {
        $this->checkAccess(true);
        header('Content-Type: application/json; charset=utf-8');

        try {
            $spendId = (int)($_GET['spend_id'] ?? 0);
            $pdo = $this->getPdo();

            $stmt = $pdo->prepare("
                SELECT * FROM `lead_investment_spend_history`
                WHERE `spend_id` = ?
                ORDER BY `changed_at` DESC, `id` DESC
            ");
            $stmt->execute([$spendId]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'data'   => $history,
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * AJAX: Save or Update Project x Platform Budget & Target CPL
     */
    public function ajaxSaveBudget(): void
    {
        $this->checkAccess(true);
        header('Content-Type: application/json; charset=utf-8');

        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $projectId = (int)($input['project_id'] ?? 0);
            $platform = $this->normalizePlatformName((string)($input['platform'] ?? 'Meta Ads'));
            $dailyBudget = max(0.0, (float)($input['daily_budget_usd'] ?? 0));
            $monthlyBudget = max(0.0, (float)($input['monthly_budget_usd'] ?? ($dailyBudget * 30)));
            $targetCpl = max(0.01, (float)($input['target_cpl_usd'] ?? 0.45));

            if ($projectId <= 0) {
                throw new InvalidArgumentException("Please select a valid project.");
            }

            $userId = (int)($_SESSION['user']['id'] ?? 0);
            $pdo = $this->getPdo();

            $stmt = $pdo->prepare("
                INSERT INTO `lead_investment_budgets`
                (`project_id`, `platform`, `daily_budget_usd`, `monthly_budget_usd`, `target_cpl_usd`, `updated_by`)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    `daily_budget_usd` = VALUES(`daily_budget_usd`),
                    `monthly_budget_usd` = VALUES(`monthly_budget_usd`),
                    `target_cpl_usd` = VALUES(`target_cpl_usd`),
                    `updated_by` = VALUES(`updated_by`),
                    `updated_at` = NOW()
            ");
            $stmt->execute([$projectId, $platform, $dailyBudget, $monthlyBudget, $targetCpl, $userId]);

            echo json_encode([
                'status'  => 'success',
                'message' => 'Project budget & Target CPL updated!',
            ]);
        } catch (Throwable $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * AJAX: Fetch distinct campaigns for a selected project from `leads`
     */
    public function ajaxCampaignsList(): void
    {
        $this->checkAccess(true);
        header('Content-Type: application/json; charset=utf-8');

        try {
            $projectId = (int)($_GET['project_id'] ?? 0);
            $pdo = $this->getPdo();

            if ($projectId > 0) {
                $stmt = $pdo->prepare("
                    SELECT DISTINCT TRIM(`campaign_name`) AS camp
                    FROM `leads`
                    WHERE `deleted_at` IS NULL
                      AND `project_id` = ?
                      AND `campaign_name` IS NOT NULL
                      AND TRIM(`campaign_name`) != ''
                    ORDER BY `id` DESC
                    LIMIT 50
                ");
                $stmt->execute([$projectId]);
            } else {
                $stmt = $pdo->query("
                    SELECT DISTINCT TRIM(`campaign_name`) AS camp
                    FROM `leads`
                    WHERE `deleted_at` IS NULL
                      AND `campaign_name` IS NOT NULL
                      AND TRIM(`campaign_name`) != ''
                    ORDER BY `id` DESC
                    LIMIT 80
                ");
            }

            $list = array_values(array_filter($stmt->fetchAll(PDO::FETCH_COLUMN)));
            echo json_encode(['status' => 'success', 'data' => $list]);
        } catch (Throwable $e) {
            echo json_encode(['status' => 'error', 'data' => []]);
        }
        exit;
    }
}

