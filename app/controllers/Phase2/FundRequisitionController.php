<?php
namespace Phase2;

use Controller;

class FundRequisitionController extends Controller {
    protected $db;

    public function __construct($db) {
        $this->db = $db;
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login");
            exit;
        }
        $this->ensureFundRequisitionSchema();
    }

    /**
     * Automatically ensure required QC columns and status ENUM values exist in fund_requisitions
     */
    private function ensureFundRequisitionSchema() {
        static $checked = false;
        if ($checked) return;
        $checked = true;

        @$this->db->query("
            CREATE TABLE IF NOT EXISTS `fund_requisitions` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `department_id` INT(11) NOT NULL,
                `project_id` INT(11) NULL DEFAULT NULL,
                `asset_id` INT(11) NULL DEFAULT NULL,
                `requested_by` INT(11) NOT NULL,
                `requisition_type` VARCHAR(50) DEFAULT 'general',
                `category` VARCHAR(100) DEFAULT 'Other Expense',
                `account_id` INT(11) NULL DEFAULT NULL,
                `location` VARCHAR(255) NULL DEFAULT NULL,
                `purpose_to_visit` TEXT NULL DEFAULT NULL,
                `mode_of_transport` VARCHAR(100) NULL DEFAULT NULL,
                `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `approved_amount` DECIMAL(12,2) NULL DEFAULT NULL,
                `paid_amount` DECIMAL(12,2) NULL DEFAULT 0.00,
                `purpose` TEXT NOT NULL,
                `receipt_path` TEXT NULL DEFAULT NULL,
                `status` ENUM('pending_dept','pending_qc','pending','approved','rejected','paid','partially_approved','partially_paid') DEFAULT 'pending',
                `is_dept_approval_required` TINYINT(1) NOT NULL DEFAULT 0,
                `dept_head_status` ENUM('pending','approved','rejected','not_required') DEFAULT 'not_required',
                `dept_head_approved_by` INT(11) NULL DEFAULT NULL,
                `dept_head_approved_at` TIMESTAMP NULL DEFAULT NULL,
                `dept_head_approval_note` TEXT NULL DEFAULT NULL,
                `qc_approved_by` INT(11) NULL DEFAULT NULL,
                `qc_approved_at` TIMESTAMP NULL DEFAULT NULL,
                `qc_approval_note` TEXT NULL DEFAULT NULL,
                `qc_approved_amount` DECIMAL(12,2) NULL DEFAULT NULL,
                `approved_by` INT(11) NULL DEFAULT NULL,
                `approved_at` TIMESTAMP NULL DEFAULT NULL,
                `approval_note` TEXT NULL DEFAULT NULL,
                `paid_by` INT(11) NULL DEFAULT NULL,
                `paid_at` TIMESTAMP NULL DEFAULT NULL,
                `payment_method` VARCHAR(50) NULL DEFAULT NULL,
                `accounts_note` TEXT NULL DEFAULT NULL,
                `payment_attachment_path` VARCHAR(255) NULL DEFAULT NULL,
                `is_received` TINYINT(1) NOT NULL DEFAULT 0,
                `received_at` TIMESTAMP NULL DEFAULT NULL,
                `settlement_status` VARCHAR(50) DEFAULT 'pending',
                `settled_at` TIMESTAMP NULL DEFAULT NULL,
                `settled_by` INT(11) NULL DEFAULT NULL,
                `balance_returned` TINYINT(1) NOT NULL DEFAULT 0,
                `balance_returned_at` TIMESTAMP NULL DEFAULT NULL,
                `accounts_settlement_verified` TINYINT(1) NOT NULL DEFAULT 0,
                `accounts_verified_by` INT(11) NULL DEFAULT NULL,
                `accounts_verified_at` TIMESTAMP NULL DEFAULT NULL,
                `parent_requisition_id` INT(11) NULL DEFAULT NULL,
                `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $colsNeeded = [
            'asset_id' => "INT(11) NULL DEFAULT NULL AFTER project_id",
            'approved_amount' => "DECIMAL(12,2) NULL DEFAULT NULL AFTER amount",
            'paid_amount' => "DECIMAL(12,2) NULL DEFAULT 0.00 AFTER approved_amount",
            'is_dept_approval_required' => "TINYINT(1) NOT NULL DEFAULT 0 AFTER status",
            'dept_head_status' => "ENUM('pending','approved','rejected','not_required') DEFAULT 'not_required' AFTER is_dept_approval_required",
            'dept_head_approved_by' => "INT(11) NULL DEFAULT NULL AFTER dept_head_status",
            'dept_head_approved_at' => "TIMESTAMP NULL DEFAULT NULL AFTER dept_head_approved_by",
            'dept_head_approval_note' => "TEXT NULL DEFAULT NULL AFTER dept_head_approved_at",
            'qc_approved_by' => "INT(11) NULL DEFAULT NULL AFTER dept_head_approval_note",
            'qc_approved_at' => "TIMESTAMP NULL DEFAULT NULL AFTER qc_approved_by",
            'qc_approval_note' => "TEXT NULL DEFAULT NULL AFTER qc_approved_at",
            'qc_approved_amount' => "DECIMAL(12,2) NULL DEFAULT NULL AFTER qc_approval_note",
            'location' => "VARCHAR(255) NULL DEFAULT NULL AFTER category",
            'purpose_to_visit' => "TEXT NULL DEFAULT NULL AFTER location",
            'mode_of_transport' => "VARCHAR(100) NULL DEFAULT NULL AFTER purpose_to_visit",
            'account_id' => "INT(11) NULL DEFAULT NULL AFTER category",
            'payment_attachment_path' => "VARCHAR(255) NULL DEFAULT NULL AFTER accounts_note",
            'is_received' => "TINYINT(1) NOT NULL DEFAULT 0",
            'received_at' => "TIMESTAMP NULL DEFAULT NULL",
            'settlement_status' => "VARCHAR(50) DEFAULT 'pending'",
            'settled_at' => "TIMESTAMP NULL DEFAULT NULL",
            'settled_by' => "INT(11) NULL DEFAULT NULL",
            'balance_returned' => "TINYINT(1) NOT NULL DEFAULT 0",
            'balance_returned_at' => "TIMESTAMP NULL DEFAULT NULL",
            'accounts_settlement_verified' => "TINYINT(1) NOT NULL DEFAULT 0",
            'accounts_verified_by' => "INT(11) NULL DEFAULT NULL",
            'accounts_verified_at' => "TIMESTAMP NULL DEFAULT NULL",
            'parent_requisition_id' => "INT(11) NULL DEFAULT NULL"
        ];

        foreach ($colsNeeded as $col => $definition) {
            $chk = $this->db->query("SHOW COLUMNS FROM fund_requisitions LIKE '{$col}'");
            if ($chk && $chk->num_rows === 0) {
                if (!@$this->db->query("ALTER TABLE fund_requisitions ADD COLUMN {$col} {$definition}")) {
                    // Fallback without AFTER clause if reference column is missing
                    $cleanDef = preg_replace('/\s+AFTER\s+\w+$/i', '', $definition);
                    @$this->db->query("ALTER TABLE fund_requisitions ADD COLUMN {$col} {$cleanDef}");
                }
            }
        }

        // Ensure status ENUM includes pending_dept and pending_qc
        $colStatus = $this->db->query("SHOW COLUMNS FROM fund_requisitions LIKE 'status'");
        if ($colStatus && $r = $colStatus->fetch_assoc()) {
            if (isset($r['Type']) && (strpos($r['Type'], "'pending_qc'") === false || strpos($r['Type'], "'pending_dept'") === false)) {
                @$this->db->query("ALTER TABLE fund_requisitions MODIFY COLUMN status ENUM('pending_dept','pending_qc','pending','approved','rejected','paid','partially_approved','partially_paid') DEFAULT 'pending'");
            }
        }

        // Ensure receipt_path is TEXT for multiple attachments support
        $colReceipt = $this->db->query("SHOW COLUMNS FROM fund_requisitions LIKE 'receipt_path'");
        if ($colReceipt && $rR = $colReceipt->fetch_assoc()) {
            if (isset($rR['Type']) && strpos(strtolower($rR['Type']), 'text') === false) {
                @$this->db->query("ALTER TABLE fund_requisitions MODIFY COLUMN receipt_path TEXT NULL DEFAULT NULL");
            }
        }

        // Ensure fund_requisition_expenses table exists
        @$this->db->query("
            CREATE TABLE IF NOT EXISTS `fund_requisition_expenses` (
              `id` int unsigned NOT NULL AUTO_INCREMENT,
              `fund_requisition_id` int NOT NULL,
              `project_id` int DEFAULT NULL,
              `other_project_name` varchar(255) DEFAULT NULL,
              `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
              `purpose` text NOT NULL,
              `attachment_path` varchar(500) DEFAULT NULL,
              `created_by` int NOT NULL DEFAULT 0,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_fre_req` (`fund_requisition_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Ensure office_assets table and item_name column exist for LEFT JOIN in showPage()
        @$this->db->query("
            CREATE TABLE IF NOT EXISTS `office_assets` (
              `id` int unsigned NOT NULL AUTO_INCREMENT,
              `asset_code` varchar(50) NOT NULL,
              `product_id` int unsigned DEFAULT NULL,
              `item_name` varchar(255) DEFAULT NULL,
              `project_id` int DEFAULT NULL,
              `office_branch_id` int DEFAULT NULL,
              `department_id` int DEFAULT NULL,
              `assigned_to` int DEFAULT NULL,
              `location` varchar(255) DEFAULT 'Head Office',
              `purchase_date` date DEFAULT NULL,
              `purchase_price` decimal(15,2) DEFAULT '0.00',
              `warranty_expiry` date DEFAULT NULL,
              `serial_no` varchar(100) DEFAULT NULL,
              `condition_status` varchar(50) DEFAULT 'new',
              `asset_status` varchar(50) DEFAULT 'in_store',
              `remarks` text,
              `created_by` int NOT NULL DEFAULT 1,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        foreach (['asset_code' => "VARCHAR(50) NULL DEFAULT NULL", 'item_name' => "VARCHAR(255) NULL DEFAULT NULL"] as $oaCol => $oaDef) {
            $chkOa = @$this->db->query("SHOW COLUMNS FROM `office_assets` LIKE '{$oaCol}'");
            if ($chkOa && $chkOa->num_rows === 0) {
                @$this->db->query("ALTER TABLE `office_assets` ADD COLUMN `{$oaCol}` {$oaDef}");
            }
        }
    }

    /**
     * Helper to get all department IDs where a user is the designated department head (is_dept_head = 1)
     */
    private function getUserDeptHeadDepartmentIds(int $userId): array {
        $deptIds = [];
        $res = $this->db->query("
            SELECT department_id FROM employees 
            WHERE (user_id = $userId OR id = (SELECT employee_id FROM users WHERE id = $userId LIMIT 1))
              AND is_dept_head = 1 
              AND department_id IS NOT NULL 
              AND department_id > 0
        ");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $deptIds[] = (int)$row['department_id'];
            }
        }
        return $deptIds;
    }

    /**
     * Check if a user is authorized to view a specific fund requisition
     */
    private function canUserViewRequisition(array $req, int $userId, int $roleId, array $deptHeadDeptIds = []): bool {
        // Management (1, 3, 33, 34, 35), Accounts (6, 30), Operation Head (20, 32), QC & Internal Audit (13, 24, 25)
        if (in_array($roleId, [1, 3, 33, 34, 35, 6, 30, 20, 32, 13, 24, 25], true)) {
            return true;
        }

        // Requester themselves
        if ((int)($req['requested_by'] ?? 0) === $userId) {
            return true;
        }

        // Requester's reporting boss - only if pending dept approval OR they already approved it
        $reportingBossId = (int)($req['requester_reporting_boss_id'] ?? $req['reporting_user_id'] ?? 0);
        if ($reportingBossId === $userId) {
            if ($req['status'] === 'pending_dept' || (int)($req['dept_head_approved_by'] ?? 0) === $userId) {
                return true;
            }
        }

        // Department Head of the requisition's department - only if pending dept approval OR they already approved it
        $reqDeptId = (int)($req['department_id'] ?? 0);
        if ($reqDeptId > 0) {
            $isDeptHead = in_array($reqDeptId, $deptHeadDeptIds, true);
            if (!$isDeptHead && empty($deptHeadDeptIds)) {
                $userDepts = $this->getUserDeptHeadDepartmentIds($userId);
                $isDeptHead = in_array($reqDeptId, $userDepts, true);
            }
            if ($isDeptHead) {
                if ($req['status'] === 'pending_dept' || (int)($req['dept_head_approved_by'] ?? 0) === $userId) {
                    return true;
                }
            }
        }

        return false;
    }

    public function index() {
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        // Management (1, 3, 33, 34, 35), Accounts (6, 30), Operation Head (20, 32), QC & Internal Audit (13, 24, 25) see everything
        $allAccessRoles = [1, 3, 33, 34, 35, 6, 30, 20, 32, 13, 24, 25];
        $deptHeadDeptIds = $this->getUserDeptHeadDepartmentIds($userId);

        $where = "1=1";
        if (!in_array($roleId, $allAccessRoles, true)) {
            // General requester: only their own requests all the time
            $userConditions = [
                "fr.requested_by = $userId"
            ];

            // Department Head or Reporting Boss: only see other people's requests if waiting for their Dept Head approval
            $approvalConditions = [];
            $approvalConditions[] = "e_req.reporting_user_id = $userId";
            if (!empty($deptHeadDeptIds)) {
                $deptList = implode(',', $deptHeadDeptIds);
                $approvalConditions[] = "fr.department_id IN ($deptList)";
            }

            if (!empty($approvalConditions)) {
                $userConditions[] = "(fr.status = 'pending_dept' AND (" . implode(' OR ', $approvalConditions) . "))";
            }

            $where = '(' . implode(' OR ', $userConditions) . ')';
        }

        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';
        $statusFilter = $_GET['status'] ?? '';
        $searchQuery = trim($_GET['search'] ?? '');
        $viewMode = $_GET['view_mode'] ?? 'all'; // Default to 'all' so approved and rejected requisitions are visible in the table below

        // Filter based on selected view mode
        if ($viewMode === 'my_requests') {
            $where .= " AND fr.requested_by = $userId";
        } elseif ($viewMode === 'my_pending') {
            $deptHeadCond = !empty($deptHeadDeptIds) ? "fr.department_id IN (" . implode(',', $deptHeadDeptIds) . ")" : "0";
            if (in_array($roleId, [20, 32], true)) {
                // Operation Head: management-pending items (up to 10 Thousand) OR dept_pending requests under them
                $where .= " AND ((fr.status IN ('pending', 'partially_approved', 'partially_paid') AND fr.amount <= 10000) OR (fr.status = 'pending_dept' AND (e_req.reporting_user_id = $userId OR $deptHeadCond)))";
            } elseif (in_array($roleId, [13, 24, 25], true)) {
                // QC & Internal Audit: show only QC-pending items
                $where .= " AND fr.status = 'pending_qc'";
            } elseif (in_array($roleId, [6, 30], true)) {
                // Accounts: show only approved (ready for payment)
                $where .= " AND fr.status IN ('approved', 'partially_approved', 'partially_paid')";
            } elseif (in_array($roleId, [1, 3, 33, 34, 35], true)) {
                // Management: show pending items across all review stages
                $where .= " AND fr.status IN ('pending_dept', 'pending_qc', 'pending', 'partially_approved', 'partially_paid')";
            } else {
                // Dept Head / Reporting Boss: show dept-pending items
                $where .= " AND (fr.status = 'pending_dept' AND (e_req.reporting_user_id = $userId OR $deptHeadCond))";
            }
        } elseif ($viewMode === 'payment_pending') {
            $where .= " AND fr.status IN ('approved', 'partially_approved')";
        }

        $params = [];
        $types = "";

        if (!empty($startDate)) {
            $where .= " AND DATE(fr.created_at) >= ?";
            $params[] = $startDate;
            $types .= "s";
        }
        if (!empty($endDate)) {
            $where .= " AND DATE(fr.created_at) <= ?";
            $params[] = $endDate;
            $types .= "s";
        }
        if (!empty($statusFilter)) {
            if ($statusFilter === 'pending') {
                $where .= " AND fr.status IN ('pending', 'pending_dept', 'pending_qc')";
            } elseif ($statusFilter === 'pending_qc') {
                $where .= " AND fr.status = 'pending_qc'";
            } else {
                $where .= " AND fr.status = ?";
                $params[] = $statusFilter;
                $types .= "s";
            }
        }
        if (!empty($searchQuery)) {
            $where .= " AND (fr.id LIKE ? OR u_req.full_name LIKE ? OR fr.purpose LIKE ? OR p.name LIKE ? OR fr.status LIKE ?)";
            $searchParam = '%' . $searchQuery . '%';
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= "sssss";
        }

        $selectedProject = $_GET['project_id'] ?? '';
        $selectedType = $_GET['requisition_type'] ?? '';

        if (!empty($selectedProject)) {
            $where .= " AND fr.project_id = ?";
            $params[] = (int)$selectedProject;
            $types .= "i";
        }
        if (!empty($selectedType)) {
            $where .= " AND fr.requisition_type = ?";
            $params[] = $selectedType;
            $types .= "s";
        }

        $limitClause = "";
        $isDefaultLimit = false;

        // Auto-check and add asset_id to fund_requisitions if missing
        $checkCol = $this->db->query("SHOW COLUMNS FROM fund_requisitions LIKE 'asset_id'");
        $hasAssetId = ($checkCol && $checkCol->num_rows > 0);
        if (!$hasAssetId) {
            @$this->db->query("ALTER TABLE fund_requisitions ADD COLUMN asset_id INT(11) NULL AFTER project_id");
            $checkCol = $this->db->query("SHOW COLUMNS FROM fund_requisitions LIKE 'asset_id'");
            $hasAssetId = ($checkCol && $checkCol->num_rows > 0);
        }

        $assetSelect = $hasAssetId ? "oa.asset_code, oa.item_name as asset_name" : "NULL as asset_code, NULL as asset_name";
        $assetJoin = $hasAssetId ? "LEFT JOIN office_assets oa ON fr.asset_id = oa.id" : "";

        // Determine what status requires the current user's action
        $pendingCondition = "0";
        if (in_array($roleId, [1, 3, 33, 34, 35], true)) { // Admin / Management
            $pendingCondition = "fr.status IN ('pending_dept', 'pending_qc', 'pending', 'partially_approved', 'partially_paid')";
        } elseif (in_array($roleId, [13, 24, 25], true)) { // QC
            $pendingCondition = "fr.status = 'pending_qc'";
        } elseif (in_array($roleId, [20, 32], true)) { // Operation Head
            $pendingCondition = "fr.status IN ('pending', 'partially_approved', 'partially_paid')";
        } elseif (in_array($roleId, [6, 30], true)) { // Accounts
            $pendingCondition = "fr.status IN ('approved', 'partially_approved', 'partially_paid')";
        }

        $deptHeadMatch = "e_req.reporting_user_id = $userId" . (!empty($deptHeadDeptIds) ? " OR fr.department_id IN (" . implode(',', $deptHeadDeptIds) . ")" : "");

        $pendingActionSql = "(
            ($pendingCondition) OR 
            (fr.status = 'pending_dept' AND ($deptHeadMatch)) OR 
            (fr.status IN ('paid', 'partially_paid') AND (fr.is_received IS NULL OR fr.is_received = 0) AND fr.requested_by = $userId)
        )";

        $sql = "
            SELECT fr.*, 
                   p.name as project_name,
                   d.name as department_name,
                   u_req.full_name as requester_name,
                   des_req.name as requester_designation_name,
                   u_app.full_name as approved_by_name,
                   des_app.name as approved_by_designation_name,
                   u_paid.full_name as paid_by_name,
                   des_paid.name as paid_by_designation_name,
                   u_dept_app.full_name as dept_head_approved_by_name,
                   des_dept_app.name as dept_head_approved_by_designation_name,
                   dept_dept_app.name as dept_head_approved_by_department_name,
                   u_qc_app.full_name as qc_approved_by_name,
                   des_qc_app.name as qc_approved_by_designation_name,
                   u_boss.full_name as reporting_boss_name,
                   des_boss.name as reporting_boss_designation_name,
                   e_req.reporting_user_id as requester_reporting_boss_id,
                   e_req.is_dept_head as requester_is_dept_head,
                   {$assetSelect}
            FROM fund_requisitions fr
            LEFT JOIN projects p ON fr.project_id = p.id
            LEFT JOIN departments d ON fr.department_id = d.id
            LEFT JOIN users u_req ON fr.requested_by = u_req.id
            LEFT JOIN employees e_req ON (e_req.user_id = u_req.id OR e_req.id = u_req.employee_id)
            LEFT JOIN designations des_req ON des_req.id = COALESCE(e_req.designation_id, u_req.designation_id)
            LEFT JOIN users u_app ON fr.approved_by = u_app.id
            LEFT JOIN designations des_app ON u_app.designation_id = des_app.id
            LEFT JOIN users u_paid ON fr.paid_by = u_paid.id
            LEFT JOIN designations des_paid ON u_paid.designation_id = des_paid.id
            LEFT JOIN users u_dept_app ON fr.dept_head_approved_by = u_dept_app.id
            LEFT JOIN designations des_dept_app ON u_dept_app.designation_id = des_dept_app.id
            LEFT JOIN departments dept_dept_app ON des_dept_app.department_id = dept_dept_app.id
            LEFT JOIN users u_qc_app ON fr.qc_approved_by = u_qc_app.id
            LEFT JOIN designations des_qc_app ON u_qc_app.designation_id = des_qc_app.id
            LEFT JOIN users u_boss ON e_req.reporting_user_id = u_boss.id
            LEFT JOIN employees e_boss ON (e_boss.user_id = u_boss.id OR e_boss.id = u_boss.employee_id)
            LEFT JOIN designations des_boss ON des_boss.id = COALESCE(e_boss.designation_id, u_boss.designation_id)
            {$assetJoin}
            WHERE $where
            ORDER BY 
                CASE WHEN {$pendingActionSql} THEN 1 ELSE 0 END DESC,
                fr.id DESC
            $limitClause
        ";

        $requests = [];
        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res) {
                    $requests = $res->fetch_all(MYSQLI_ASSOC);
                }
            }
        } else {
            $res = $this->db->query($sql);
            if ($res) {
                $requests = $res->fetch_all(MYSQLI_ASSOC);
            } else {
                error_log("FundRequisition Index SQL Error: " . $this->db->error);
            }
        }

        // Projects for create form — restrict to assigned projects for site engineers
        $assignedIds = isset($assignedIds) ? $assignedIds : $this->getAssignedProjectIds($userId);
        if (!in_array($roleId, [1, 3, 33, 6, 30]) && !empty($assignedIds)) {
            $idList = implode(',', $assignedIds);
            $pRes = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL AND id IN ($idList) ORDER BY name ASC");
            $projects = $pRes ? $pRes->fetch_all(MYSQLI_ASSOC) : [];
        } else {
            $pRes = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC");
            $projects = $pRes ? $pRes->fetch_all(MYSQLI_ASSOC) : [];
        }

        $dRes = $this->db->query("SELECT id, name FROM departments ORDER BY name ASC");
        $departments = $dRes ? $dRes->fetch_all(MYSQLI_ASSOC) : [];

        $coaList = getCOAExpenseList($this->db);

        $isAllAccess = in_array($roleId, [1, 3, 33, 34, 35, 6, 30, 20, 32, 13, 24, 25], true);
        
        // 1. Rejected count
        if ($isAllAccess) {
            $rejRes = $this->db->query("SELECT COUNT(*) FROM fund_requisitions WHERE status = 'rejected'");
        } else {
            $rejRes = $this->db->query("SELECT COUNT(*) FROM fund_requisitions WHERE status = 'rejected' AND requested_by = $userId");
        }
        $rejectedCount = $rejRes ? (int)$rejRes->fetch_row()[0] : 0;

        // 2. Pending Action count for current user
        $pendingActionCount = 0;
        if (in_array($roleId, [1, 3, 33, 34, 35, 38], true)) {
            $resDept = $this->db->query("
                SELECT COUNT(DISTINCT fr.id) AS total 
                FROM fund_requisitions fr
                LEFT JOIN users u ON fr.requested_by = u.id
                LEFT JOIN employees e ON (e.user_id = u.id OR e.id = u.employee_id)
                WHERE fr.status = 'pending_dept' 
                  AND (e.reporting_user_id = $userId OR $roleId IN (1, 3))
            ");
            if ($resDept) $pendingActionCount += (int)($resDept->fetch_assoc()['total'] ?? 0);
            $resMgmt = $this->db->query("SELECT COUNT(*) AS total FROM fund_requisitions WHERE status IN ('pending', 'partially_approved', 'partially_paid')");
            if ($resMgmt) $pendingActionCount += (int)($resMgmt->fetch_assoc()['total'] ?? 0);
        } elseif (in_array($roleId, [20, 32], true)) {
            $resMgmt = $this->db->query("
                SELECT COUNT(DISTINCT fr.id) AS total 
                FROM fund_requisitions fr
                LEFT JOIN users u ON fr.requested_by = u.id
                LEFT JOIN employees e ON (e.user_id = u.id OR e.id = u.employee_id)
                WHERE (fr.status IN ('pending', 'partially_approved', 'partially_paid') AND fr.amount <= 10000)
                   OR (fr.status = 'pending_dept' AND e.reporting_user_id = $userId)
            ");
            if ($resMgmt) $pendingActionCount = (int)($resMgmt->fetch_assoc()['total'] ?? 0);
        } elseif (in_array($roleId, [13, 24, 25], true)) {
            $resQc = $this->db->query("SELECT COUNT(*) AS total FROM fund_requisitions WHERE status = 'pending_qc'");
            if ($resQc) $pendingActionCount = (int)($resQc->fetch_assoc()['total'] ?? 0);
        } elseif (in_array($roleId, [6, 30], true)) {
            $resAccts = $this->db->query("SELECT COUNT(*) AS total FROM fund_requisitions WHERE status IN ('approved', 'partially_approved', 'partially_paid')");
            if ($resAccts) $pendingActionCount = (int)($resAccts->fetch_assoc()['total'] ?? 0);
        } else {
            $resDept = $this->db->query("
                SELECT COUNT(DISTINCT fr.id) AS total 
                FROM fund_requisitions fr
                LEFT JOIN users u ON fr.requested_by = u.id
                LEFT JOIN employees e ON (e.user_id = u.id OR e.id = u.employee_id)
                WHERE fr.status = 'pending_dept' 
                  AND e.reporting_user_id = $userId
            ");
            if ($resDept) $pendingActionCount = (int)($resDept->fetch_assoc()['total'] ?? 0);
        }

        // 3. My Requests count
        $myReqRes = $this->db->query("SELECT COUNT(*) FROM fund_requisitions WHERE requested_by = $userId");
        $myRequestsCount = $myReqRes ? (int)$myReqRes->fetch_row()[0] : 0;

        // 4. Payment Pending count
        $payRes = $this->db->query("SELECT COUNT(*) FROM fund_requisitions WHERE status IN ('approved', 'partially_approved')");
        $paymentPendingCount = $payRes ? (int)$payRes->fetch_row()[0] : 0;

        // 5. Total accessible requisitions count
        if ($isAllAccess) {
            $allRes = $this->db->query("SELECT COUNT(*) FROM fund_requisitions");
        } else {
            $allRes = $this->db->query("SELECT COUNT(*) FROM fund_requisitions WHERE requested_by = $userId");
        }
        $totalAllCount = $allRes ? (int)$allRes->fetch_row()[0] : 0;

        $this->view('phase2/fund_requisition/index', [
            'current_route' => 'fund-requisitions',
            'page_title' => 'Fund Requisitions',
            'requests' => $requests,
            'projects' => $projects,
            'departments' => $departments,
            'coaList' => $coaList,
            'roleId' => $roleId,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'statusFilter' => $statusFilter,
            'searchQuery' => $searchQuery,
            'selectedProject' => $selectedProject,
            'selectedType' => $selectedType,
            'isDefaultLimit' => $isDefaultLimit,
            'deptHeadDeptIds' => $deptHeadDeptIds,
            'viewMode' => $viewMode,
            'rejectedCount' => $rejectedCount,
            'pendingActionCount' => $pendingActionCount,
            'myRequestsCount' => $myRequestsCount,
            'paymentPendingCount' => $paymentPendingCount,
            'totalAllCount' => $totalAllCount,
        ]);
    }

    /**
     * Returns an array of project IDs that a user is assigned to
     * (site_engineer_id, pm_id, operation_head_id, qc_id, struct_engineer_id, draftsman_id).
     */
    private function getAssignedProjectIds(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT id FROM projects
            WHERE deleted_at IS NULL
              AND (site_engineer_id   = ?
                OR pm_id              = ?
                OR operation_head_id  = ?
                OR qc_id              = ?
                OR struct_engineer_id = ?
                OR draftsman_id       = ?
                OR landman_id         = ?
                OR architect_id       = ?
                OR estimator_id       = ?
                OR operator_id        = ?
                OR purchase_user_id   = ?)
        ");
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('iiiiiiiiiii', $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $ids = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $ids[] = (int)$row['id'];
            }
        }
        return $ids;
    }

    /**
     * Full view page for a single fund requisition (not AJAX — renders a PHP view).
     */
    public function showPage($id) {
        $id            = (int)$id;
        $sessionUserId = (int)$_SESSION['user']['id'];
        $sessionRoleId = (int)$_SESSION['user']['role_id'];

        $sql = "
            SELECT fr.*,
                   p.name      AS project_name,
                   d.name      AS department_name,
                   u_req.full_name  AS requester_name,
                   des_req.name AS requester_designation_name,
                   u_app.full_name  AS approved_by_name,
                   des_app.name AS approved_by_designation_name,
                   u_paid.full_name AS paid_by_name,
                   des_paid.name AS paid_by_designation_name,
                   u_dept_app.full_name AS dept_head_approved_by_name,
                   des_dept_app.name AS dept_head_approved_by_designation_name,
                   dept_dept_app.name AS dept_head_approved_by_department_name,
                   u_qc_app.full_name AS qc_approved_by_name,
                   des_qc_app.name AS qc_approved_by_designation_name,
                   u_boss.full_name AS reporting_boss_name,
                   des_boss.name AS reporting_boss_designation_name,
                   e_req.reporting_user_id AS requester_reporting_boss_id,
                   e_req.is_dept_head AS requester_is_dept_head,
                   oa.asset_code,
                   oa.item_name AS asset_item_name
            FROM fund_requisitions fr
            LEFT JOIN projects p    ON fr.project_id    = p.id
            LEFT JOIN departments d ON fr.department_id = d.id
            LEFT JOIN office_assets oa ON fr.asset_id   = oa.id
            LEFT JOIN users u_req   ON fr.requested_by  = u_req.id
            LEFT JOIN employees e_req ON (e_req.user_id = u_req.id OR e_req.id = u_req.employee_id)
            LEFT JOIN designations des_req ON des_req.id = COALESCE(e_req.designation_id, u_req.designation_id)
            LEFT JOIN users u_app   ON fr.approved_by   = u_app.id
            LEFT JOIN designations des_app ON u_app.designation_id = des_app.id
            LEFT JOIN users u_paid  ON fr.paid_by       = u_paid.id
            LEFT JOIN designations des_paid ON u_paid.designation_id = des_paid.id
            LEFT JOIN users u_dept_app ON fr.dept_head_approved_by = u_dept_app.id
            LEFT JOIN designations des_dept_app ON u_dept_app.designation_id = des_dept_app.id
            LEFT JOIN departments dept_dept_app ON des_dept_app.department_id = dept_dept_app.id
            LEFT JOIN users u_qc_app ON fr.qc_approved_by = u_qc_app.id
            LEFT JOIN designations des_qc_app ON u_qc_app.designation_id = des_qc_app.id
            LEFT JOIN users u_boss ON e_req.reporting_user_id = u_boss.id
            LEFT JOIN employees e_boss ON (e_boss.user_id = u_boss.id OR e_boss.id = u_boss.employee_id)
            LEFT JOIN designations des_boss ON des_boss.id = COALESCE(e_boss.designation_id, u_boss.designation_id)
            WHERE fr.id = $id
        ";
        $resReq = $this->db->query($sql);
        $req = $resReq ? $resReq->fetch_assoc() : null;
        if (!$req) {
            setError('Requisition not found.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $deptHeadDeptIds = $this->getUserDeptHeadDepartmentIds($sessionUserId);
        if (!$this->canUserViewRequisition($req, $sessionUserId, $sessionRoleId, $deptHeadDeptIds)) {
            setError('You are not authorized to view this requisition.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        // Auto-heal if approved but approved_amount is 0 or null
        if (in_array($req['status'], ['approved', 'paid']) && (empty($req['approved_amount']) || (float)$req['approved_amount'] <= 0)) {
            $effectiveAmt = (!empty($req['qc_approved_amount']) && (float)$req['qc_approved_amount'] > 0)
                ? (float)$req['qc_approved_amount']
                : (float)$req['amount'];
            $this->db->query("UPDATE fund_requisitions SET approved_amount = {$effectiveAmt} WHERE id = {$id} AND (approved_amount IS NULL OR approved_amount = 0)");
            $req['approved_amount'] = $effectiveAmt;
        }

        // Auto-heal if fully paid but status was accidentally reverted to 'approved'
        if ($req['status'] === 'approved' && !empty($req['paid_at']) && (float)($req['paid_amount'] ?? 0) >= (float)($req['approved_amount'] ?? $req['amount'])) {
            $this->db->query("UPDATE fund_requisitions SET status = 'paid' WHERE id = {$id}");
            $req['status'] = 'paid';
        }

        // Ensure paid_at is chronologically after approved_at (1 minute after)
        if (!empty($req['paid_at']) && !empty($req['approved_at']) && strtotime($req['paid_at']) <= strtotime($req['approved_at'])) {
            $this->db->query("UPDATE fund_requisitions SET paid_at = DATE_ADD(approved_at, INTERVAL 1 MINUTE) WHERE id = {$id}");
            $req['paid_at'] = date('Y-m-d H:i:s', strtotime($req['approved_at'] . ' +1 minute'));
        }

        // Expenses
        $resExp = $this->db->query("
            SELECT fre.*, p.name AS project_name, u.full_name AS creator_name
            FROM fund_requisition_expenses fre
            LEFT JOIN projects p ON fre.project_id = p.id
            LEFT JOIN users u    ON fre.created_by = u.id
            WHERE fre.fund_requisition_id = $id
            ORDER BY fre.id DESC
        ");
        $expenses = $resExp ? $resExp->fetch_all(MYSQLI_ASSOC) : [];

        $effectiveApprovedAmount = (!empty($req['approved_amount']) && (float)$req['approved_amount'] > 0)
            ? (float)$req['approved_amount']
            : ((!empty($req['qc_approved_amount']) && (float)$req['qc_approved_amount'] > 0)
                ? (float)$req['qc_approved_amount']
                : (float)$req['amount']);

        $totalSpent = array_sum(array_column($expenses, 'amount'));
        $balance    = max(0, $effectiveApprovedAmount - $totalSpent);

        // Projects dropdown — restrict for restricted roles
        if (!in_array($sessionRoleId, [1, 3, 33, 6, 30])) {
            $assignedIds = $this->getAssignedProjectIds($sessionUserId);
            if (!empty($assignedIds)) {
                $idList   = implode(',', $assignedIds);
                $pResShow = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL AND id IN ($idList) ORDER BY name ASC");
                $projects = $pResShow ? $pResShow->fetch_all(MYSQLI_ASSOC) : [];
            } else {
                $projects = [];
            }
        } else {
            $pResShow = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC");
            $projects = $pResShow ? $pResShow->fetch_all(MYSQLI_ASSOC) : [];
        }

        // Management / Operation Head approval breakdown (who approved how much)
        require_once __DIR__ . '/../../models/FundRequisitionApprovalLog.php';
        $mgmtApprovals = [];
        try {
            $mgmtApprovals = \FundRequisitionApprovalLog::getManagementApprovals($this->db, $id, $req);
        } catch (\Throwable $t) {
            error_log("FR approval log read error: " . $t->getMessage());
        }

        require_once __DIR__ . '/../../services/RequisitionRevisionService.php';
        $revSvc = new \RequisitionRevisionService($this->db);
        $latestRevision = $revSvc->getLatestActiveOrResubmitted('fund_requisitions', $id);

        $this->view('phase2/fund_requisition/show', [
            'mgmtApprovals'           => $mgmtApprovals,
            'latestRevision'          => $latestRevision,
            'current_route'           => 'fund-requisitions',
            'page_title'              => 'Requisition Details — REQ-' . $id,
            'req'                     => $req,
            'expenses'                => $expenses,
            'projects'                => $projects,
            'totalSpent'              => $totalSpent,
            'balance'                 => $balance,
            'effectiveApprovedAmount' => $effectiveApprovedAmount,
            'sessionUserId'           => $sessionUserId,
            'sessionRoleId'           => $sessionRoleId,
            'deptHeadDeptIds'         => $deptHeadDeptIds,
        ]);
    }

    public function store() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $requested_by = (int)($_SESSION['user']['id'] ?? 0);
        if (!$requested_by) {
            setError('Your session has expired. Please log in again.');
            header("Location: " . $GLOBALS['base_url'] . "/login");
            exit;
        }

        $department_id = (int)($_POST['department_id'] ?? 0);
        $project_id = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;
        $requisition_type = trim($_POST['requisition_type'] ?? 'general');
        $category = trim($_POST['category'] ?? 'Other Expense');
        $account_id = !empty($_POST['account_id']) ? (int)$_POST['account_id'] : null;

        $rawAmount = trim(str_replace([',', ' '], '', $_POST['amount'] ?? ''));
        $amount = is_numeric($rawAmount) ? (float)$rawAmount : 0;
        $purpose = trim($_POST['purpose'] ?? '');

        $location = trim($_POST['location'] ?? '');
        $purpose_to_visit = trim($_POST['purpose_to_visit'] ?? '');
        $mode_of_transport = trim($_POST['mode_of_transport'] ?? '');

        // Exact field-by-field validation with specific error messages
        $errors = [];
        if ($department_id <= 0) {
            $errors[] = 'Please select a valid Department.';
        }
        if ($amount <= 0) {
            $errors[] = 'Please enter a valid requested Amount greater than 0.';
        }
        if ($purpose === '') {
            $errors[] = 'Please describe the Purpose / Description of this requisition.';
        }

        // If category is Transport / Conveyance, validate transport-specific fields
        if ($requisition_type === 'general' && $category === 'Transport') {
            if ($location === '') {
                $errors[] = 'Please specify Location / Destination for Transport requisition.';
            }
            if ($purpose_to_visit === '') {
                $errors[] = 'Please describe Purpose to Visit for Transport requisition.';
            }
            if ($mode_of_transport === '') {
                $errors[] = 'Please select or specify Mode of Transport.';
            }
        } else {
            $location = !empty($location) ? $location : null;
            $purpose_to_visit = !empty($purpose_to_visit) ? $purpose_to_visit : null;
            $mode_of_transport = !empty($mode_of_transport) ? $mode_of_transport : null;
        }

        if (!empty($errors)) {
            setError(count($errors) === 1 ? $errors[0] : implode(' • ', $errors));
            $_SESSION['old_fund_requisition'] = [
                'department_id'      => $department_id,
                'project_id'         => $project_id,
                'requisition_type'   => $requisition_type,
                'category'           => $category,
                'amount'             => $rawAmount,
                'purpose'            => $purpose,
                'location'           => $location,
                'purpose_to_visit'   => $purpose_to_visit,
                'mode_of_transport'  => $mode_of_transport,
            ];
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions?open_modal=1");
            exit;
        }

        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $designationId = (int)($_SESSION['user']['designation_id'] ?? 0);

        // Validation for Petty Cash: Site Engineer (role 12 or designation 38), Admin Operation / Operation Head (role 20, 32), and Admin (role 1, 3)
        if ($requisition_type === 'petty_cash') {
            $canPettyCash = in_array($roleId, [1, 3, 12, 20, 32], true) || $designationId === 38;
            if (!$canPettyCash) {
                setError('Petty Cash requisitions can only be requested by Site Engineers, Admin Operation, and Operation Heads.');
                $_SESSION['old_fund_requisition'] = $_POST;
                header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions?open_modal=1");
                exit;
            }
            if ($amount > 10000) {
                setError('Maximum Petty Cash request limit is BDT 10,000.');
                $_SESSION['old_fund_requisition'] = $_POST;
                header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions?open_modal=1");
                exit;
            }
            $category = 'Petty Cash';
            $location = null;
            $purpose_to_visit = null;
            $mode_of_transport = null;
        }

        // Receipt Uploads (Supports multiple files up to 4 images/documents)
        $uploaded_receipts = [];
        $upload_dir = dirname(dirname(dirname(__DIR__))) . '/public/uploads/receipts/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'webp'];

        // 1. Multiple files from dynamic inputs: receipts[]
        if (!empty($_FILES['receipts']['name']) && is_array($_FILES['receipts']['name'])) {
            $count = count($_FILES['receipts']['name']);
            for ($i = 0; $i < min(4, $count); $i++) {
                if (isset($_FILES['receipts']['error'][$i]) && $_FILES['receipts']['error'][$i] === UPLOAD_ERR_OK && !empty($_FILES['receipts']['tmp_name'][$i])) {
                    $ext = strtolower(pathinfo($_FILES['receipts']['name'][$i], PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed_exts)) {
                        $filename = 'receipt_' . time() . '_' . rand(1000, 9999) . '_' . ($i + 1) . '.' . $ext;
                        if (move_uploaded_file($_FILES['receipts']['tmp_name'][$i], $upload_dir . $filename)) {
                            $uploaded_receipts[] = 'uploads/receipts/' . $filename;
                        }
                    }
                }
            }
        }

        // 2. Fallback for single receipt input: receipt
        if (empty($uploaded_receipts) && isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK && !empty($_FILES['receipt']['tmp_name'])) {
            $file_extension = strtolower(pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION));
            if (in_array($file_extension, $allowed_exts)) {
                $filename = 'receipt_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
                if (move_uploaded_file($_FILES['receipt']['tmp_name'], $upload_dir . $filename)) {
                    $uploaded_receipts[] = 'uploads/receipts/' . $filename;
                }
            }
        }

        $receipt_path = !empty($uploaded_receipts) ? json_encode($uploaded_receipts) : null;

        // Check employee record for requested_by user to see if Dept Head approval is required
        $empRes = $this->db->query("
            SELECT e.is_dept_head, e.reporting_user_id 
            FROM employees e 
            LEFT JOIN users u ON (u.employee_id = e.id OR e.user_id = u.id)
            WHERE u.id = $requested_by 
            LIMIT 1
        ");
        $empData = ($empRes && $empRes->num_rows > 0) ? $empRes->fetch_assoc() : null;

        $isDeptHead = (int)($empData['is_dept_head'] ?? 0);
        $reportingUserId = !empty($empData['reporting_user_id']) ? (int)$empData['reporting_user_id'] : null;

        if ($isDeptHead === 0 && !empty($reportingUserId)) {
            $isDeptApprovalRequired = 1;
            $deptHeadStatus = 'pending';
            $status = 'pending_dept';
        } else {
            $isDeptApprovalRequired = 0;
            $deptHeadStatus = 'not_required';
            $status = 'pending_qc';
        }

        $stmt = $this->db->prepare("
            INSERT INTO fund_requisitions 
            (department_id, project_id, requested_by, requisition_type, category, amount, purpose, receipt_path, status, is_dept_approval_required, dept_head_status, account_id, location, purpose_to_visit, mode_of_transport)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iiissdsssisisss", $department_id, $project_id, $requested_by, $requisition_type, $category, $amount, $purpose, $receipt_path, $status, $isDeptApprovalRequired, $deptHeadStatus, $account_id, $location, $purpose_to_visit, $mode_of_transport);

        if ($stmt->execute()) {
            $newReqId = $stmt->insert_id;
            $this->syncApprovalRequest($newReqId);

            // --- Email Notification Start ---
            try {
                require_once __DIR__ . '/../../core/MailServiceForPHPMailer.php';
                $mailService = new \MailService($this->db);
                $link = $GLOBALS['base_url'] . "/fund-requisitions/view/" . $newReqId;

                $reqUserRes = $this->db->query("SELECT full_name FROM users WHERE id = {$requested_by}");
                $requesterName = ($reqUserRes && $reqUserRes->num_rows > 0) ? $reqUserRes->fetch_assoc()['full_name'] : 'Unknown';

                $reqDetails = [
                    'title' => $purpose,
                    'requester_name' => $requesterName,
                    'amount' => $amount
                ];

                $emailSent = false;
                if ($isDeptApprovalRequired == 1 && !empty($reportingUserId)) {
                    $bossRes = $this->db->query("SELECT full_name, email FROM users WHERE id = {$reportingUserId}");
                    if ($bossRes && $bossRes->num_rows > 0) {
                        $bossInfo = $bossRes->fetch_assoc();
                        if (!empty($bossInfo['email'])) {
                            $emailSent = $mailService->sendRequisitionNotification($bossInfo['email'], $bossInfo['full_name'], 'Pending Department Head Approval', 'Fund Requisition', $reqDetails, $link);
                        }
                    }
                } else {
                    $qcRes = $this->db->query("SELECT full_name, email FROM users WHERE role_id = 24 AND email != '' AND email IS NOT NULL");
                    $qcList = [];
                    if ($qcRes) {
                        while ($qc = $qcRes->fetch_assoc()) {
                            $qcList[] = $qc;
                        }
                    }
                    if (!empty($qcList)) {
                        $mailService->sendRequisitionNotificationBatch($qcList, 'Pending QC & Internal Audit Head Approval', 'Fund Requisition', $reqDetails, $link);
                    }
                }
            } catch (\Throwable $t) {
                error_log("Fund requisition creation email notification error: " . $t->getMessage());
            }
            // --- Email Notification End ---

            setSuccess("Fund Requisition submitted successfully.");
        } else {
            setError("Failed to submit request: " . $this->db->error);
        }

        header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
        exit;
    }

    public function approveDept() {
        $sessionUserId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'approved'; // 'approved' or 'rejected'
        $approval_note = trim($_POST['approval_note'] ?? '');

        if (!$id || !in_array($status, ['approved', 'rejected'])) {
            setError('Invalid request or status.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        // Fetch requisition & check if user is allowed (reporting boss OR admin/management)
        $sql = "
            SELECT fr.*, e.reporting_user_id
            FROM fund_requisitions fr
            LEFT JOIN users u ON u.id = fr.requested_by
            LEFT JOIN employees e ON (e.user_id = u.id OR u.employee_id = e.id)
            WHERE fr.id = $id
        ";
        $req = $this->db->query($sql)->fetch_assoc();
        if (!$req) {
            setError('Requisition not found.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $reportingBossId = (int)($req['reporting_user_id'] ?? 0);
        $deptId = (int)($req['department_id'] ?? 0);
        $deptHeadDeptIds = $this->getUserDeptHeadDepartmentIds($sessionUserId);
        $isDeptHead = ($deptId > 0 && in_array($deptId, $deptHeadDeptIds, true));
        $isAllowed = ($sessionUserId === $reportingBossId || $isDeptHead || in_array($roleId, [1, 3, 20, 32, 33], true));

        if (!$isAllowed) {
            setError('Only the designated Department Head, Reporting Boss, or Admin can approve this stage.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        if (in_array($req['status'], ['paid', 'partially_paid'])) {
            setError('Cannot modify an already paid requisition.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions/view/" . $id);
            exit;
        }

        $newDeptStatus = ($status === 'approved') ? 'approved' : 'rejected';
        $newMainStatus = ($status === 'approved') ? 'pending_qc' : 'rejected';

        $stmt = $this->db->prepare("
            UPDATE fund_requisitions 
            SET dept_head_status = ?, 
                dept_head_approved_by = ?, 
                dept_head_approval_note = ?, 
                dept_head_approved_at = CURRENT_TIMESTAMP,
                status = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sissi", $newDeptStatus, $sessionUserId, $approval_note, $newMainStatus, $id);

        if ($stmt->execute()) {
            $this->syncApprovalRequest($id);

            // --- Email Notification Start ---
            require_once __DIR__ . '/../../core/MailServiceForPHPMailer.php';
            $mailService = new \MailService($this->db);
            $link = $GLOBALS['base_url'] . "/fund-requisitions/view/" . $id;

            $reqUserRes = $this->db->query("SELECT full_name FROM users WHERE id = {$req['requested_by']}");
            $requesterName = ($reqUserRes && $reqUserRes->num_rows > 0) ? $reqUserRes->fetch_assoc()['full_name'] : 'Unknown';

            $reqDetails = [
                'title' => $req['purpose'] ?? 'Fund Requisition',
                'requester_name' => $requesterName,
                'amount' => $req['amount'] ?? 0
            ];

            $emailSent = false;
            if ($newDeptStatus === 'approved') {
                $qcRes = $this->db->query("SELECT full_name, email FROM users WHERE role_id = 24 AND email != '' AND email IS NOT NULL");
                $qcList = [];
                if ($qcRes) {
                    while ($qc = $qcRes->fetch_assoc()) {
                        $qcList[] = $qc;
                    }
                }
                if (!empty($qcList)) {
                    $mailService->sendRequisitionNotificationBatch($qcList, 'Pending QC & Internal Audit Head Approval', 'Fund Requisition', $reqDetails, $link);
                }
            } else if ($newDeptStatus === 'rejected') {
                $reqEmailRes = $this->db->query("SELECT email FROM users WHERE id = {$req['requested_by']}");
                if ($reqEmailRes && $reqEmailRes->num_rows > 0) {
                    $reqEmail = $reqEmailRes->fetch_assoc()['email'];
                    if (!empty($reqEmail)) {
                        $mailService->sendRequisitionNotification($reqEmail, $requesterName, 'Rejected by Department Head', 'Fund Requisition', $reqDetails, $link);
                    }
                }
            }
            // --- Email Notification End ---

            if ($emailSent) {
                setSuccess("Department Head status updated: " . $newDeptStatus . ". Notification sent.");
            } else {
                setSuccess("Department Head status updated: " . $newDeptStatus . " (No email was sent).");
            }
        } else {
            setError("Failed to update status: " . $this->db->error);
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? ($GLOBALS['base_url'] . "/fund-requisitions");
        header("Location: " . $referer);
        exit;
    }

    public function approveQc() {
        $sessionUserId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        if (in_array($roleId, [33, 34, 35], true) || (!in_array($roleId, [24, 13, 25, 20, 32, 1, 3], true))) {
            setError('Only QC & Internal Audit Head or Admins can approve this stage.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'approved'; // 'approved' or 'rejected'
        $approval_note = trim($_POST['approval_note'] ?? '');

        if (!$id || !in_array($status, ['approved', 'rejected'])) {
            setError('Invalid request or status.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $sql = "SELECT * FROM fund_requisitions WHERE id = $id";
        $req = $this->db->query($sql)->fetch_assoc();
        if (!$req) {
            setError('Requisition not found.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $isInitialQc = ($req['status'] === 'pending_qc');
        $isQcUpdate  = (!empty($req['qc_approved_by']) && (float)($req['qc_approved_amount'] ?? 0) < (float)$req['amount']);
        $isQcRejectRevoke = (!empty($req['qc_approved_by']) && $status === 'rejected' && !in_array($req['status'], ['paid', 'partially_paid']));

        if (!$isInitialQc && !$isQcUpdate && !$isQcRejectRevoke) {
            setError('Requisition is not waiting for QC approval.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        if (in_array($req['status'], ['paid', 'partially_paid'])) {
            setError('Cannot modify an already paid requisition.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions/view/" . $id);
            exit;
        }

        if ($isInitialQc || $isQcRejectRevoke) {
            $newMainStatus = ($status === 'approved') ? 'pending' : 'rejected';
        } else {
            $newMainStatus = ($status === 'rejected') ? 'rejected' : $req['status'];
        }
        
        $qc_approved_amount = null;
        if ($status === 'approved') {
            $reqAmount = (float)($req['amount'] ?? 0);
            if (isset($_POST['qc_approved_amount']) && is_numeric($_POST['qc_approved_amount'])) {
                $qc_approved_amount = (float)$_POST['qc_approved_amount'];
            } else {
                $qc_approved_amount = $reqAmount;
            }

            if ($qc_approved_amount <= 0) {
                setError('QC approved amount must be greater than 0.');
                $referer = $_SERVER['HTTP_REFERER'] ?? ($GLOBALS['base_url'] . "/fund-requisitions");
                header("Location: " . $referer);
                exit;
            }

            if ($qc_approved_amount > $reqAmount) {
                setError('QC approved amount cannot exceed the requested amount (৳ ' . number_format($reqAmount, 2) . ').');
                $referer = $_SERVER['HTTP_REFERER'] ?? ($GLOBALS['base_url'] . "/fund-requisitions");
                header("Location: " . $referer);
                exit;
            }
        }

        $this->ensureFundRequisitionSchema();

        // Dynamically inspect existing columns to ensure prepared statement never fails
        $existingCols = [];
        $colRes = $this->db->query("SHOW COLUMNS FROM fund_requisitions");
        if ($colRes) {
            while ($cRow = $colRes->fetch_assoc()) {
                $existingCols[$cRow['Field']] = true;
            }
        }

        $fields = ["status = ?"];
        $types = "s";
        $values = [$newMainStatus];

        if (isset($existingCols['qc_approved_by'])) {
            $fields[] = "qc_approved_by = ?";
            $types .= "i";
            $values[] = $sessionUserId;
        }

        if (isset($existingCols['qc_approval_note'])) {
            $fields[] = "qc_approval_note = ?";
            $types .= "s";
            $values[] = $approval_note;
        }

        if (isset($existingCols['qc_approved_amount'])) {
            if ($qc_approved_amount === null) {
                $fields[] = "qc_approved_amount = NULL";
            } else {
                $fields[] = "qc_approved_amount = ?";
                $types .= "d";
                $values[] = $qc_approved_amount;
            }
        }

        if (isset($existingCols['qc_approved_at'])) {
            $fields[] = "qc_approved_at = CURRENT_TIMESTAMP";
        }

        $fieldsStr = implode(", ", $fields);
        $sql = "UPDATE fund_requisitions SET {$fieldsStr} WHERE id = ?";
        $types .= "i";
        $values[] = $id;

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("Failed to prepare QC approval query: " . $this->db->error);
            setError("Database error updating QC approval: " . $this->db->error);
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions/view/" . $id);
            exit;
        }

        $stmt->bind_param($types, ...$values);

        if ($stmt->execute()) {
            $this->syncApprovalRequest($id);

            // --- Fast Batch Email Notification ---
            $emailSent = false;
            try {
                require_once __DIR__ . '/../../core/MailServiceForPHPMailer.php';
                $mailService = new \MailService($this->db);
                $link = $GLOBALS['base_url'] . "/fund-requisitions/view/" . $id;

                $reqUserRes = $this->db->query("SELECT full_name, email FROM users WHERE id = {$req['requested_by']}");
                $requesterInfo = ($reqUserRes && $reqUserRes->num_rows > 0) ? $reqUserRes->fetch_assoc() : null;
                $requesterName = $requesterInfo['full_name'] ?? 'Unknown';

                $reqDetails = [
                    'title' => $req['purpose'] ?? 'Fund Requisition',
                    'requester_name' => $requesterName,
                    'amount' => $qc_approved_amount ?? ($req['amount'] ?? 0),
                    'note' => $approval_note
                ];

                if ($status === 'approved') {
                    $evalAmt = $qc_approved_amount ?? (float)($req['amount'] ?? 0);
                    if ($evalAmt <= 100000) {
                        $mgmtSql = "SELECT DISTINCT full_name, email FROM users WHERE role_id IN (1, 3) AND email != '' AND email IS NOT NULL";
                    } else {
                        $mgmtSql = "SELECT DISTINCT full_name, email FROM users WHERE role_id IN (1, 3) AND email != '' AND email IS NOT NULL";
                    }
                    $mgmtRes = $this->db->query($mgmtSql);
                    $recipients = [];
                    if ($mgmtRes) {
                        while ($mgmt = $mgmtRes->fetch_assoc()) {
                            $recipients[] = $mgmt;
                        }
                    }

                    if (!empty($recipients)) {
                        $emailSent = $mailService->sendRequisitionNotificationBatch($recipients, 'Pending Management Approval', 'Fund Requisition', $reqDetails, $link);
                    }
                } else if ($status === 'rejected') {
                    if ($requesterInfo && !empty($requesterInfo['email'])) {
                        $emailSent = $mailService->sendRequisitionNotification($requesterInfo['email'], $requesterName, 'Rejected by QC & Internal Audit Head', 'Fund Requisition', $reqDetails, $link);
                    }
                }
            } catch (\Throwable $t) {
                error_log("QC approval email notification error: " . $t->getMessage());
            }
            // --- Email Notification End ---

            if ($emailSent) {
                setSuccess("QC status updated with approved amount ৳ " . number_format($qc_approved_amount ?? $req['amount'], 2) . ". Notification sent to Management.");
            } else {
                setSuccess("QC status updated with approved amount ৳ " . number_format($qc_approved_amount ?? $req['amount'], 2) . ".");
            }
        } else {
            setError("Failed to update QC status: " . $this->db->error);
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? ($GLOBALS['base_url'] . "/fund-requisitions");
        header("Location: " . $referer);
        exit;
    }

    public function approve() {
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $roleName = strtolower($_SESSION['user']['role_name'] ?? '');
        if (in_array($roleId, [33, 34, 35], true) || in_array($roleName, ['chief officer', 'management viewer', 'seal ceo'], true) || !in_array($roleId, [1, 3, 20, 32])) {
            setError('Only Management, Admins, or Operation can approve requisitions.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'approved'; // 'approved', 'partially_approved', or 'rejected'
        $approval_note = $_POST['approval_note'] ?? '';
        $approved_by = (int)$_SESSION['user']['id'];
        $new_approved_amount = isset($_POST['approved_amount']) ? (float)$_POST['approved_amount'] : null;

        if (!in_array($status, ['approved', 'partially_approved', 'rejected'])) {
            setError('Invalid status.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $frData = $this->db->query("SELECT * FROM fund_requisitions WHERE id = {$id}")->fetch_assoc();
        if (!$frData) {
            setError('Requisition not found.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        if (in_array($frData['status'], ['paid', 'partially_paid']) && (float)($frData['paid_amount'] ?? 0) > 0) {
            setError('Cannot modify an already paid requisition.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions/view/" . $id);
            exit;
        }

        $amount = (float)($frData['amount'] ?? 0);
        $isOver1Lac = ($amount > 10000.01);
        $roleNameLc = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
        $isSiteOperator = in_array($roleId, [20, 32], true) || strpos($roleNameLc, 'operation head') !== false || strpos($roleNameLc, 'admin & operation') !== false;
        $isManagement = in_array($roleId, [1, 3, 38], true) || in_array($roleNameLc, ['super admin', 'admin', 'management', 'board of director', 'board of directors'], true) || strpos($roleNameLc, 'director') !== false || strpos($roleNameLc, 'dceo') !== false;

        if ($isSiteOperator && !$isManagement) {
            // Operation Head is processing Step 1 after QC (for both <= 10K and > 10K)
            if (!class_exists('ApprovalRequest')) {
                require_once __DIR__ . '/../../models/ApprovalRequest.php';
            }
            $appModel = new \ApprovalRequest($this->db);
            $arRow = $this->db->query("SELECT id FROM approval_requests WHERE source_table = 'fund_requisitions' AND source_id = {$id} ORDER BY id DESC LIMIT 1")->fetch_assoc();
            
            if ($status === 'rejected') {
                if ($arRow) {
                    $appModel->rejectOperationHead((int)$arRow['id'], $approved_by, $approval_note);
                } else {
                    $this->db->query("UPDATE approval_requests SET op_head_status = 'rejected', op_head_by = {$approved_by}, op_head_at = NOW(), op_head_note = '{$this->db->real_escape_string($approval_note)}', status = 'rejected', approve_role = 'rejected' WHERE source_table = 'fund_requisitions' AND source_id = {$id}");
                }
                $this->db->query("UPDATE fund_requisitions SET status = 'rejected', updated_at = NOW() WHERE id = {$id}");
                setSuccess("Requisition rejected by Admin & Operation Head (Role 20).");
                header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions/view/" . $id);
                exit;
            }

            if ($arRow) {
                $appModel->approveOperationHead((int)$arRow['id'], $approved_by, $approval_note);
            } else {
                $this->db->query("UPDATE approval_requests SET op_head_status = 'approved', op_head_by = {$approved_by}, op_head_at = NOW(), op_head_note = '{$this->db->real_escape_string($approval_note)}', approve_role = 'accounts_head' WHERE source_table = 'fund_requisitions' AND source_id = {$id}");
            }
            setSuccess("Requisition approved by Admin & Operation Head (Role 20). Forwarded to Accounts Head (Role 30) for payment verification.");
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions/view/" . $id);
            exit;
        }

        if ($isManagement || in_array($roleId, [20, 32])) {
            $effectiveMax = (float)$frData['amount'];
        } else {
            $effectiveMax = ($frData['qc_approved_amount'] !== null && (float)$frData['qc_approved_amount'] > 0)
                ? (float)$frData['qc_approved_amount']
                : (float)$frData['amount'];
        }

        $cumulative_approved_amount = (float)($frData['approved_amount'] ?? 0);
        $posted_amount = 0;

        if ($status === 'approved') {
            $posted_amount = max(0, $effectiveMax - $cumulative_approved_amount); 
            $cumulative_approved_amount = $effectiveMax;
            if (!empty($frData['paid_at']) && (float)($frData['paid_amount'] ?? 0) >= $cumulative_approved_amount) {
                $status = 'paid';
            } elseif (!empty($frData['paid_at']) && (float)($frData['paid_amount'] ?? 0) > 0) {
                $status = 'partially_paid';
            }
        } elseif ($status === 'partially_approved') {
            if ($new_approved_amount <= 0 || ($cumulative_approved_amount + $new_approved_amount) > $effectiveMax) {
                setError('Invalid approved amount.');
                header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions/view/" . $id);
                exit;
            }
            $cumulative_approved_amount += $new_approved_amount;
            $posted_amount = $new_approved_amount;
            if ($cumulative_approved_amount >= $effectiveMax) {
                if (!empty($frData['paid_at']) && (float)($frData['paid_amount'] ?? 0) >= $cumulative_approved_amount) {
                    $status = 'paid';
                } elseif (!empty($frData['paid_at']) && (float)($frData['paid_amount'] ?? 0) > 0) {
                    $status = 'partially_paid';
                } else {
                    $status = 'approved';
                }
            } else {
                $status = 'partially_approved';
            }
        } elseif ($status === 'rejected') {
            // No change to approved_amount, mark approval_requests as rejected
            $this->db->query("UPDATE approval_requests SET status = 'rejected', approve_role = 'rejected' WHERE source_table = 'fund_requisitions' AND source_id = {$id}");
        }

        // Management Single-Stage Approval (approve ONLY current pending stage)
        $approveMode = $_POST['approve_mode'] ?? 'override';
        if ($isManagement && $approveMode === 'single' && in_array($status, ['approved', 'partially_approved'])) {
            $escSingleNote = $this->db->real_escape_string($approval_note ?: 'Approved by Management');
            $arCheckFr = $this->db->query("SELECT id, op_head_status, accounts_head_status FROM approval_requests WHERE source_table = 'fund_requisitions' AND source_id = {$id} ORDER BY id DESC LIMIT 1");
            $arRowFr = $arCheckFr ? $arCheckFr->fetch_assoc() : null;
            $opHeadFr = strtolower($arRowFr['op_head_status'] ?? 'pending');
            $ahFr     = strtolower($arRowFr['accounts_head_status'] ?? 'pending');

            if (($frData['status'] ?? '') === 'pending_dept' || ($frData['dept_head_status'] ?? '') === 'pending') {
                $this->db->query("UPDATE fund_requisitions SET status = 'pending_qc', dept_head_status = 'approved', dept_head_approved_by = {$approved_by}, dept_head_approved_at = NOW(), dept_head_approval_note = '{$escSingleNote} (On behalf of Dept. Head)' WHERE id = {$id}");
                $this->syncApprovalRequest($id);
                setSuccess('Department Head stage approved by Management. Forwarded to QC & Internal Audit.');
                header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions/view/" . $id);
                exit;
            } elseif (($frData['status'] ?? '') === 'pending_qc' || empty($frData['qc_approved_by'])) {
                $this->db->query("UPDATE fund_requisitions SET status = 'pending', qc_approved_by = {$approved_by}, qc_approved_at = NOW(), qc_approved_amount = {$cumulative_approved_amount}, qc_approval_note = '{$escSingleNote} (On behalf of QC)' WHERE id = {$id}");
                $this->syncApprovalRequest($id);
                setSuccess('QC & Audit stage approved by Management. Forwarded to Operation Head.');
                header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions/view/" . $id);
                exit;
            } elseif ($opHeadFr !== 'approved') {
                $this->db->query("UPDATE approval_requests SET op_head_status = 'approved', op_head_by = {$approved_by}, op_head_at = NOW(), op_head_note = '{$escSingleNote} (On behalf of Operation Head)', approve_role = 'accounts_head' WHERE source_table = 'fund_requisitions' AND source_id = {$id}");
                setSuccess('Operation Head stage approved by Management. Forwarded to Accounts Head.');
                header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions/view/" . $id);
                exit;
            } elseif ($ahFr !== 'approved') {
                $this->db->query("UPDATE approval_requests SET accounts_head_status = 'approved', accounts_head_by = {$approved_by}, accounts_head_at = NOW(), accounts_head_note = '{$escSingleNote} (On behalf of Accounts Head)', approve_role = 'management' WHERE source_table = 'fund_requisitions' AND source_id = {$id}");
                setSuccess('Accounts Head stage approved by Management. Ready for Final Management Approval.');
                header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions/view/" . $id);
                exit;
            }
        }

        // Preserve any pre-existing (legacy) approval in the approval log before overwriting approved_by
        require_once __DIR__ . '/../../models/FundRequisitionApprovalLog.php';
        try {
            \FundRequisitionApprovalLog::backfillIfMissing($this->db, $frData);
        } catch (\Throwable $t) {
            error_log("FR approval log backfill error: " . $t->getMessage());
        }

        $stmt = $this->db->prepare("
            UPDATE fund_requisitions 
            SET status = ?, approved_by = ?, approval_note = ?, approved_at = CURRENT_TIMESTAMP, approved_amount = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sisdi", $status, $approved_by, $approval_note, $cumulative_approved_amount, $id);

        if ($stmt->execute()) {
            // Record who approved how much (supports multiple partial approvals)
            try {
                if ($status === 'rejected') {
                    \FundRequisitionApprovalLog::log($this->db, $id, 'rejected', 0, (float)($frData['approved_amount'] ?? 0), $approved_by, $approval_note);
                } elseif ($posted_amount > 0) {
                    $logAction = ($cumulative_approved_amount + 0.01 >= $effectiveMax) ? 'approved' : 'partially_approved';
                    \FundRequisitionApprovalLog::log($this->db, $id, $logAction, $posted_amount, $cumulative_approved_amount, $approved_by, $approval_note);
                }
            } catch (\Throwable $t) {
                error_log("FR approval log error: " . $t->getMessage());
            }

            if (in_array($status, ['approved', 'partially_approved']) && $isManagement) {
                $escBypassNote = $this->db->real_escape_string(($approval_note ?: 'Approved by Management') . ' (Management Override)');
                $this->db->query("
                    UPDATE fund_requisitions
                    SET dept_head_status = IF(dept_head_status = 'pending', 'approved', dept_head_status),
                        dept_head_approved_by = IF(dept_head_status = 'pending' OR (dept_head_status = 'approved' AND dept_head_approved_by IS NULL), {$approved_by}, dept_head_approved_by),
                        dept_head_approved_at = IF(dept_head_status = 'pending' OR (dept_head_status = 'approved' AND dept_head_approved_at IS NULL), NOW(), dept_head_approved_at),
                        dept_head_approval_note = IF(dept_head_status = 'pending' OR (dept_head_status = 'approved' AND (dept_head_approval_note IS NULL OR dept_head_approval_note = '')), '{$escBypassNote}', dept_head_approval_note),
                        qc_approved_by = COALESCE(qc_approved_by, {$approved_by}),
                        qc_approved_at = COALESCE(qc_approved_at, NOW()),
                        qc_approved_amount = COALESCE(NULLIF(qc_approved_amount, 0), {$cumulative_approved_amount}),
                        qc_approval_note = COALESCE(NULLIF(qc_approval_note, ''), '{$escBypassNote}')
                    WHERE id = {$id}
                ");
            }

            $this->syncApprovalRequest($id);
            if (in_array($status, ['approved', 'partially_approved'])) {
                $escBypassNote = $this->db->real_escape_string(($approval_note ?: 'Approved by Management') . ' (Management Override)');
                if ($isManagement) {
                    $this->db->query("
                        UPDATE approval_requests
                        SET op_head_status = 'approved',
                            op_head_by = COALESCE(op_head_by, {$approved_by}),
                            op_head_at = COALESCE(op_head_at, NOW()),
                            op_head_note = COALESCE(NULLIF(op_head_note, ''), '{$escBypassNote}'),
                            accounts_head_status = 'approved',
                            accounts_head_by = COALESCE(accounts_head_by, {$approved_by}),
                            accounts_head_at = COALESCE(accounts_head_at, NOW()),
                            accounts_head_note = COALESCE(NULLIF(accounts_head_note, ''), '{$escBypassNote}'),
                            approve_role = IF('{$status}' = 'approved', 'accounts', approve_role)
                        WHERE source_table = 'fund_requisitions' AND source_id = {$id}
                    ");
                } else {
                    $this->db->query("UPDATE approval_requests SET op_head_status = 'approved', op_head_by = COALESCE(op_head_by, {$approved_by}), op_head_at = COALESCE(op_head_at, NOW()) WHERE source_table = 'fund_requisitions' AND source_id = {$id}");
                }
            }

            // ====== AUTO POSTING JOURNAL ENTRY (Batch 2/3) ======
            if (in_array($status, ['approved', 'partially_approved']) && $posted_amount > 0) {
                try {
                    require_once dirname(dirname(__DIR__)) . '/services/AutoPostingService.php';
                    $autoService = new \AutoPostingService($this->db);
                    // Fetch the user selected account_id from the requisition, fallback to 5220 if missing
                    $selectedAccountId = !empty($frData['account_id']) ? $frData['account_id'] : $autoService->getAccountIdByCode('5220');
                    $accountsPayableAccId = $autoService->getAccountIdByCode('211000'); // Accounts Payable
                    
                    $autoService->postAccrualEntry(
                        'fund_requisition',
                        $id,
                        $selectedAccountId,
                        $accountsPayableAccId,
                        $posted_amount,
                        "Fund Requisition (FR-{$id}): " . ($frData['purpose'] ?: 'Operational Expense'),
                        $frData['project_id'],
                        'employee',
                        $frData['requested_by'],
                        $approved_by
                    );
                } catch (\Exception $e) {
                    error_log("AutoPosting Exception (Fund Requisition Approve): " . $e->getMessage());
                }
            }
            // ====================================================

            // --- Email Notification Start ---
            require_once __DIR__ . '/../../core/MailServiceForPHPMailer.php';
            $mailService = new \MailService($this->db);
            $link = $GLOBALS['base_url'] . "/fund-requisitions/view/" . $id;

            $reqUserRes = $this->db->query("SELECT full_name, email FROM users WHERE id = {$frData['requested_by']}");
            $requesterInfo = ($reqUserRes && $reqUserRes->num_rows > 0) ? $reqUserRes->fetch_assoc() : null;

            $reqDetails = [
                'title' => $frData['purpose'] ?? 'Fund Requisition',
                'requester_name' => $requesterInfo['full_name'] ?? 'Unknown',
                'amount' => $frData['amount'] ?? 0
            ];

            $emailSent = false;
            if ($status === 'approved' || $status === 'partially_approved') {
                $accRes = $this->db->query("SELECT full_name, email FROM users WHERE role_id IN (6, 30) AND email != '' AND email IS NOT NULL");
                $accList = [];
                if ($accRes) {
                    while ($acc = $accRes->fetch_assoc()) {
                        $accList[] = $acc;
                    }
                }
                if (!empty($accList)) {
                    $mailService->sendRequisitionNotificationBatch($accList, 'Pending Accounts Payout', 'Fund Requisition', $reqDetails, $link);
                }
                if ($requesterInfo && !empty($requesterInfo['email'])) {
                    $mailService->sendRequisitionNotification($requesterInfo['email'], $requesterInfo['full_name'], 'Approved by Management', 'Fund Requisition', $reqDetails, $link);
                }
            } else if ($status === 'rejected') {
                if ($requesterInfo && !empty($requesterInfo['email'])) {
                    $mailService->sendRequisitionNotification($requesterInfo['email'], $requesterInfo['full_name'], 'Rejected by Management', 'Fund Requisition', $reqDetails, $link);
                }
            }
            // --- Email Notification End ---

            if ($emailSent) {
                setSuccess("Requisition status updated to: " . $status . ". Notification sent.");
            } else {
                setSuccess("Requisition status updated to: " . $status . " (No email was sent).");
            }
        } else {
            setError("Failed to update requisition: " . $this->db->error);
        }

        header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
        exit;
    }

    public function pay() {
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $roleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
        $isAllowedToPay = in_array($roleId, [1, 3, 6, 30, 38]) || strpos($roleName, 'account') !== false || in_array($roleName, ['super admin', 'admin', 'management', 'board of director', 'board of directors'], true) || strpos($roleName, 'director') !== false || strpos($roleName, 'dceo') !== false;
        if (!$isAllowedToPay) {
            setError('Only Accounts or Admins can pay requisitions.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'paid'; // 'paid', 'partially_paid', 'rejected'
        $payment_method = $_POST['payment_method'] ?? 'Cash';
        $accounts_note = $_POST['accounts_note'] ?? '';
        $paid_by = (int)$_SESSION['user']['id'];
        $new_paid_amount = isset($_POST['paid_amount']) ? (float)$_POST['paid_amount'] : null;

        if (!in_array($status, ['paid', 'partially_paid', 'rejected'])) {
            setError('Invalid status.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $frData = $this->db->query("SELECT * FROM fund_requisitions WHERE id = {$id}")->fetch_assoc();
        if (!$frData) {
            setError('Requisition not found.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        // STRICT GUARD: Must be approved by Management before Accounts can disburse payment
        if (!in_array($frData['status'], ['approved', 'partially_approved', 'partially_paid'])) {
            setError('Payment cannot be processed! Requisition must be approved by Management first (Current status: ' . htmlspecialchars($frData['status']) . ').');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions/view/" . $id);
            exit;
        }

        // STRICT GUARD: Must be approved by Accounts Head (Role 30 - Md. Mamunoor Rashid) before payment disbursement
        require_once __DIR__ . '/../../models/ApprovalRequest.php';
        $ahCheck = \ApprovalRequest::getAccountsHeadApprovalInfo($this->db, 'fund_requisitions', $id);
        if ($ahCheck['has_request'] && !$ahCheck['is_accounts_head_approved']) {
            setError('Payment cannot be processed! Accounts Head approval (Md. Mamunoor Rashid [1309]) is required before payment disbursement.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions/view/" . $id);
            exit;
        }

        $cumulative_paid_amount = (float)($frData['paid_amount'] ?? 0);
        $posted_amount = 0;

        $targetApprovedAmount = (!empty($frData['approved_amount']) && (float)$frData['approved_amount'] > 0)
            ? (float)$frData['approved_amount']
            : ((!empty($frData['qc_approved_amount']) && (float)$frData['qc_approved_amount'] > 0)
                ? (float)$frData['qc_approved_amount']
                : (float)$frData['amount']);

        if ($status === 'paid') {
            $posted_amount = $targetApprovedAmount - $cumulative_paid_amount;
            $cumulative_paid_amount = $targetApprovedAmount;
            if ($cumulative_paid_amount < (float)$frData['amount'] && $cumulative_paid_amount < $targetApprovedAmount) {
                $status = 'partially_paid';
            }
        } elseif ($status === 'partially_paid') {
            if ($new_paid_amount <= 0 || ($cumulative_paid_amount + $new_paid_amount) > $targetApprovedAmount) {
                setError('Invalid paid amount.');
                header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions/view/" . $id);
                exit;
            }
            $cumulative_paid_amount += $new_paid_amount;
            $posted_amount = $new_paid_amount;
            if ($cumulative_paid_amount >= $targetApprovedAmount) {
                $status = 'paid';
            }
        }

        // Handle payment attachment upload
        $payment_attachment_path = null;
        if (isset($_FILES['payment_attachment']) && $_FILES['payment_attachment']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = dirname(dirname(dirname(__DIR__))) . '/public/uploads/payments/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_extension = pathinfo($_FILES['payment_attachment']['name'], PATHINFO_EXTENSION);
            $filename = 'payment_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
            if (move_uploaded_file($_FILES['payment_attachment']['tmp_name'], $upload_dir . $filename)) {
                $payment_attachment_path = 'uploads/payments/' . $filename;
            }
        }

        if ($payment_attachment_path) {
            $stmt = $this->db->prepare("
                UPDATE fund_requisitions 
                SET status = ?, paid_by = ?, payment_method = ?, accounts_note = ?, payment_attachment_path = ?, paid_at = CURRENT_TIMESTAMP, paid_amount = ?, approved_amount = GREATEST(COALESCE(approved_amount, 0), ?)
                WHERE id = ?
            ");
            $stmt->bind_param("sisssddi", $status, $paid_by, $payment_method, $accounts_note, $payment_attachment_path, $cumulative_paid_amount, $targetApprovedAmount, $id);
        } else {
            $stmt = $this->db->prepare("
                UPDATE fund_requisitions 
                SET status = ?, paid_by = ?, payment_method = ?, accounts_note = ?, paid_at = CURRENT_TIMESTAMP, paid_amount = ?, approved_amount = GREATEST(COALESCE(approved_amount, 0), ?)
                WHERE id = ?
            ");
            $stmt->bind_param("sissddi", $status, $paid_by, $payment_method, $accounts_note, $cumulative_paid_amount, $targetApprovedAmount, $id);
        }

        if ($stmt->execute()) {
            $this->syncApprovalRequest($id);

            // ====== AUTO POSTING DISBURSEMENT VOUCHER (Batch 2/3) ======
            if (in_array($status, ['paid', 'partially_paid']) && $posted_amount > 0) {
                try {
                    require_once dirname(dirname(__DIR__)) . '/services/AutoPostingService.php';
                    $autoService = new \AutoPostingService($this->db);
                    
                    $bankAccId = $autoService->getBankOrCashAccountId($payment_method);
                    $accountsPayableAccId = $autoService->getAccountIdByCode('211000'); // Accounts Payable
                    
                    $autoService->postDisbursementEntry(
                        'fund_requisition',
                        $id,
                        $accountsPayableAccId,
                        $bankAccId,
                        $posted_amount,
                        "Payment Disbursement for Fund Requisition (FR-{$id})",
                        $frData['project_id'],
                        'employee',
                        $frData['requested_by'],
                        $paid_by
                    );
                } catch (\Exception $e) {
                    error_log("AutoPosting Exception (Fund Requisition Pay): " . $e->getMessage());
                }
            }
            // ====================================================

            // --- Email Notification Start ---
            require_once __DIR__ . '/../../core/MailServiceForPHPMailer.php';
            $mailService = new \MailService($this->db);
            $link = $GLOBALS['base_url'] . "/fund-requisitions/view/" . $id;

            $reqUserRes = $this->db->query("SELECT full_name, email FROM users WHERE id = {$frData['requested_by']}");
            $requesterInfo = ($reqUserRes && $reqUserRes->num_rows > 0) ? $reqUserRes->fetch_assoc() : null;

            $reqDetails = [
                'title' => $frData['purpose'] ?? 'Fund Requisition',
                'requester_name' => $requesterInfo['full_name'] ?? 'Unknown',
                'amount' => $frData['amount'] ?? 0
            ];

            if ($status === 'paid' || $status === 'partially_paid') {
                if ($requesterInfo && !empty($requesterInfo['email'])) {
                    $mailService->sendRequisitionNotification($requesterInfo['email'], $requesterInfo['full_name'], 'Payment Disbursed by Accounts', 'Fund Requisition', $reqDetails, $link);
                }
            } else if ($status === 'rejected') {
                if ($requesterInfo && !empty($requesterInfo['email'])) {
                    $mailService->sendRequisitionNotification($requesterInfo['email'], $requesterInfo['full_name'], 'Payment Rejected by Accounts', 'Fund Requisition', $reqDetails, $link);
                }
            }
            // --- Email Notification End ---

            setSuccess("Requisition payment status updated to: " . $status);
        } else {
            setError("Failed to update payment status: " . $this->db->error);
        }

        header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
        exit;
    }

    public function delete() {
        $id = (int)($_POST['id'] ?? 0);
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        if (!$id) {
            setError("Invalid record ID.");
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        // Fetch request to check owner and approval status
        $req = $this->db->query("SELECT * FROM fund_requisitions WHERE id = $id")->fetch_assoc();
        if (!$req) {
            setError("Request not found.");
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        // Check if any approval has already been granted
        $isApproved = (
            $req['dept_head_status'] === 'approved' ||
            !empty($req['dept_head_approved_by']) ||
            in_array($req['status'], ['approved', 'paid']) ||
            !empty($req['approved_by']) ||
            !empty($req['paid_by'])
        );

        if ($isApproved) {
            setError("This requisition cannot be deleted because an approval has already been granted.");
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        // Only creator or Admin/Super Admin can delete before approval
        $allow = ($req['requested_by'] == $userId || in_array($roleId, [1, 3, 33]));

        if ($allow) {
            $this->db->query("DELETE FROM fund_requisitions WHERE id = $id");
            $this->db->query("DELETE FROM approval_requests WHERE source_table = 'fund_requisitions' AND source_id = $id");
            setSuccess("Request deleted successfully.");
        } else {
            setError("Unauthorized to delete this request.");
        }

        header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
        exit;
    }

    public function show($id) {
        $id = (int)$id;
        $sessionUserId = (int)($_SESSION['user']['id'] ?? 0);
        $sessionRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        
        // Fetch requisition details
        $sql = "
            SELECT fr.*, 
                   p.name as project_name,
                   d.name as department_name,
                   u_req.full_name as requester_name,
                   u_app.full_name as approved_by_name,
                   u_paid.full_name as paid_by_name,
                   e_req.reporting_user_id as requester_reporting_boss_id
            FROM fund_requisitions fr
            LEFT JOIN projects p ON fr.project_id = p.id
            LEFT JOIN departments d ON fr.department_id = d.id
            LEFT JOIN users u_req ON fr.requested_by = u_req.id
            LEFT JOIN employees e_req ON (e_req.user_id = u_req.id OR e_req.id = u_req.employee_id)
            LEFT JOIN users u_app ON fr.approved_by = u_app.id
            LEFT JOIN users u_paid ON fr.paid_by = u_paid.id
            WHERE fr.id = $id
        ";
        $req = $this->db->query($sql)->fetch_assoc();
        if (!$req) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Requisition not found']);
            exit;
        }

        if (!$this->canUserViewRequisition($req, $sessionUserId, $sessionRoleId)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'You are not authorized to view this requisition.']);
            exit;
        }

        // Fetch total spent globally (without date filter) for correct balance calculation
        $sumSql = "SELECT SUM(amount) as total_spent FROM fund_requisition_expenses WHERE fund_requisition_id = $id";
        $sumRes = $this->db->query($sumSql)->fetch_assoc();
        $totalSpentGlobal = (float)($sumRes['total_spent'] ?? 0);

        // Fetch expenses (with date range filter if specified, or limit to 10 latest by default)
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';

        $expenseWhere = "fre.fund_requisition_id = $id";
        $params = [];
        $types = "";

        if (!empty($startDate)) {
            $expenseWhere .= " AND DATE(fre.created_at) >= ?";
            $params[] = $startDate;
            $types .= "s";
        }
        if (!empty($endDate)) {
            $expenseWhere .= " AND DATE(fre.created_at) <= ?";
            $params[] = $endDate;
            $types .= "s";
        }

        $limitClause = "";
        $isDefaultLimit = false;
        if (empty($startDate) && empty($endDate)) {
            $limitClause = " LIMIT 10";
            $isDefaultLimit = true;
        }

        $expenseSql = "
            SELECT fre.*, 
                   p.name as project_name,
                   u.full_name as creator_name
            FROM fund_requisition_expenses fre
            LEFT JOIN projects p ON fre.project_id = p.id
            LEFT JOIN users u ON fre.created_by = u.id
            WHERE $expenseWhere
            ORDER BY fre.id DESC
            $limitClause
        ";

        if (!empty($params)) {
            $stmt = $this->db->prepare($expenseSql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $expenses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $expenses = $this->db->query($expenseSql)->fetch_all(MYSQLI_ASSOC);
        }

        // Fetch projects for the add expense form
        // For restricted roles, only show their assigned projects in the dropdown
        $sessionUserId  = (int)$_SESSION['user']['id'];
        $sessionRoleId  = (int)$_SESSION['user']['role_id'];
        if (!in_array($sessionRoleId, [1, 3, 33, 6, 30])) {
            $assignedIds = $this->getAssignedProjectIds($sessionUserId);
            if (!empty($assignedIds)) {
                $idList   = implode(',', $assignedIds);
                $projects = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL AND id IN ($idList) ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
            } else {
                $projects = [];
            }
        } else {
            $projects = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
        }

            $effectiveApprovedAmount = (!empty($req['approved_amount']) && (float)$req['approved_amount'] > 0)
                ? (float)$req['approved_amount']
                : ((!empty($req['qc_approved_amount']) && (float)$req['qc_approved_amount'] > 0)
                    ? (float)$req['qc_approved_amount']
                    : (float)$req['amount']);

            header('Content-Type: application/json');
            echo json_encode([
                'success'                   => true,
                'requisition'               => $req,
                'expenses'                  => $expenses,
                'projects'                  => $projects,
                'session_user_id'           => (int)$_SESSION['user']['id'],
                'session_role_id'           => (int)$_SESSION['user']['role_id'],
                'is_default_limit'          => $isDefaultLimit,
                'total_spent_global'        => $totalSpentGlobal,
                'effective_approved_amount' => $effectiveApprovedAmount,
                'balance_remaining'         => max(0, $effectiveApprovedAmount - $totalSpentGlobal),
            ]);
        exit;
    }

    public function storeExpense() {
        $fund_requisition_id = (int)($_POST['fund_requisition_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $purpose = $_POST['purpose'] ?? '';
        $req = $this->db->query("SELECT * FROM fund_requisitions WHERE id = $fund_requisition_id")->fetch_assoc();
        
        $project_id = $req ? $req['project_id'] : null;
        $other_project_name = null;

        $userId = (int)$_SESSION['user']['id'];
        $roleId = (int)$_SESSION['user']['role_id'];

        // Validate that the requisition exists and is paid
        if (!$req || $req['status'] !== 'paid') {
            setError('Expenses can only be logged for paid requisitions.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        if (!$this->canUserViewRequisition($req, $userId, $roleId)) {
            setError('Unauthorized to log expenses for this requisition.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        if ($amount <= 0 || empty($purpose)) {
            setError('Please specify a valid amount and purpose.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $effectiveApprovedAmount = (!empty($req['approved_amount']) && (float)$req['approved_amount'] > 0)
            ? (float)$req['approved_amount']
            : ((!empty($req['qc_approved_amount']) && (float)$req['qc_approved_amount'] > 0)
                ? (float)$req['qc_approved_amount']
                : (float)$req['amount']);

        // Limit checking
        $sumSql = "SELECT SUM(amount) as total_spent FROM fund_requisition_expenses WHERE fund_requisition_id = $fund_requisition_id";
        $sumRes = $this->db->query($sumSql)->fetch_assoc();
        $total_spent = (float)($sumRes['total_spent'] ?? 0);
        $remaining = max(0, $effectiveApprovedAmount - $total_spent);
        if ($amount > $remaining) {
            setError('Expense amount (BDT ' . number_format($amount, 2) . ') exceeds the remaining approved requisition balance (BDT ' . number_format($remaining, 2) . ').');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions/view/" . $fund_requisition_id);
            exit;
        }

        // Handle attachment upload
        $attachment_path = null;
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = dirname(dirname(dirname(__DIR__))) . '/public/uploads/expenses/';
                if (!is_dir($upload_dir)) {
                    if (!mkdir($upload_dir, 0777, true)) {
                        error_log("Failed to create directory: " . $upload_dir);
                        setError("Failed to create upload directory.");
                    }
                }
                if (is_dir($upload_dir)) {
                    $file_extension = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
                    $filename = 'expense_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
                    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_dir . $filename)) {
                        $attachment_path = 'uploads/expenses/' . $filename;
                    } else {
                        $err = error_get_last();
                        error_log("Failed to move uploaded expense attachment. Error: " . ($err['message'] ?? 'Unknown'));
                        setError("Failed to save uploaded attachment.");
                    }
                }
            } else {
                $upload_errors = [
                    UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                    UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                    UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                    UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
                ];
                $errorCode = $_FILES['attachment']['error'];
                $errorMessage = $upload_errors[$errorCode] ?? 'Unknown upload error.';
                error_log("Expense upload error: " . $errorMessage);
                setError("Attachment upload failed: " . $errorMessage);
            }
        }

        $stmt = $this->db->prepare("
            INSERT INTO fund_requisition_expenses 
            (fund_requisition_id, project_id, other_project_name, amount, purpose, attachment_path, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iisdssi", $fund_requisition_id, $project_id, $other_project_name, $amount, $purpose, $attachment_path, $userId);

        if ($stmt->execute()) {
            setSuccess("Expense logged successfully.");
        } else {
            setError("Failed to log expense: " . $this->db->error);
        }

        header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions/view/" . $fund_requisition_id);
        exit;
    }

    public function deleteExpense() {
        $id = (int)($_POST['id'] ?? 0);
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        if (!$id) {
            setError("Invalid expense ID.");
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $expense = $this->db->query("SELECT * FROM fund_requisition_expenses WHERE id = $id")->fetch_assoc();
        if (!$expense) {
            setError("Expense not found.");
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $fundReqId = (int)$expense['fund_requisition_id'];
        $req = $this->db->query("SELECT requested_by FROM fund_requisitions WHERE id = $fundReqId")->fetch_assoc();
        $isReqOwner = $req && $req['requested_by'] == $userId;

        // Only owner of expense, owner of requisition, or Admin/Management can delete
        if ($expense['created_by'] === $userId || $isReqOwner || in_array($roleId, [1, 3, 33])) {
            // Delete file if exists
            if (!empty($expense['attachment_path'])) {
                $file = dirname(dirname(dirname(__DIR__))) . '/public/' . $expense['attachment_path'];
                if (file_exists($file)) {
                    unlink($file);
                }
            }
            $this->db->query("DELETE FROM fund_requisition_expenses WHERE id = $id");
            setSuccess("Expense deleted successfully.");
        } else {
            setError("Unauthorized to delete this expense.");
        }

        header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions/view/" . $fundReqId);
        exit;
    }

    // =========================================================================
    // SETTLEMENT METHODS
    // =========================================================================

    /**
     * Requester marks the requisition as settled (all expenses logged).
     */
    public function submitSettlement($id) {
        $id     = (int)$id;
        $userId = (int)$_SESSION['user']['id'];
        $roleId = (int)$_SESSION['user']['role_id'];

        $req = $this->db->query("SELECT * FROM fund_requisitions WHERE id = $id")->fetch_assoc();
        if (!$req || $req['status'] !== 'paid') {
            setError('Settlement can only be submitted for paid requisitions.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }
        if ($req['requested_by'] != $userId && !in_array($roleId, [1, 3, 33])) {
            setError('Only the requester or admin can submit settlement.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $stmt = $this->db->prepare("
            UPDATE fund_requisitions
            SET settlement_status = 'settled', settled_at = NOW(), settled_by = ?
            WHERE id = ? AND settlement_status = 'pending'
        ");
        $stmt->bind_param('ii', $userId, $id);
        if ($stmt->execute() && $this->db->affected_rows > 0) {
            setSuccess('Requisition marked as settled. Accounts will verify.');
        } else {
            setError('Could not submit settlement. It may have already been submitted.');
        }
        header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
        exit;
    }

    /**
     * Requester marks that they have returned the remaining balance to accounts.
     */
    public function markBalanceReturned($id) {
        $id     = (int)$id;
        $userId = (int)$_SESSION['user']['id'];
        $roleId = (int)$_SESSION['user']['role_id'];

        $req = $this->db->query("SELECT * FROM fund_requisitions WHERE id = $id")->fetch_assoc();
        if (!$req) {
            setError('Requisition not found.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }
        if ($req['requested_by'] != $userId && !in_array($roleId, [1, 3, 33])) {
            setError('Unauthorized.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $stmt = $this->db->prepare("
            UPDATE fund_requisitions
            SET balance_returned = 1, balance_returned_at = NOW()
            WHERE id = ? AND balance_returned = 0
        ");
        $stmt->bind_param('i', $id);
        if ($stmt->execute() && $this->db->affected_rows > 0) {
            setSuccess('Balance return acknowledged. Accounts will verify.');
        } else {
            setError('Balance already marked as returned.');
        }
        header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
        exit;
    }

    /**
     * Accounts verifies the settlement (confirms everything is settled).
     */
    public function verifySettlement($id) {
        $id     = (int)$id;
        $userId = (int)$_SESSION['user']['id'];
        $roleId = (int)$_SESSION['user']['role_id'];

        if (!in_array($roleId, [1, 6])) {
            setError('Only Accounts can verify settlements.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $req = $this->db->query("SELECT * FROM fund_requisitions WHERE id = $id")->fetch_assoc();
        if (!$req || $req['status'] !== 'paid') {
            setError('Only paid requisitions can be verified.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $stmt = $this->db->prepare("
            UPDATE fund_requisitions
            SET accounts_settlement_verified = 1,
                accounts_verified_by = ?,
                accounts_verified_at = NOW(),
                settlement_status = 'settled'
            WHERE id = ?
        ");
        $stmt->bind_param('ii', $userId, $id);
        if ($stmt->execute()) {
            setSuccess('Settlement verified successfully by Accounts.');
        } else {
            setError('Failed to verify settlement.');
        }
        header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
        exit;
    }

    /**
     * Requester submits an extra fund request when actual spend exceeds requisition amount.
     * Creates a new linked requisition with parent_requisition_id set.
     */
    public function requestExtraFund($id) {
        $id           = (int)$id;
        $userId       = (int)$_SESSION['user']['id'];
        $extraAmount  = (float)($_POST['extra_amount'] ?? 0);
        $reason       = trim($_POST['extra_reason'] ?? '');

        if ($extraAmount <= 0 || empty($reason)) {
            setError('Extra amount and reason are required.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $req = $this->db->query("SELECT * FROM fund_requisitions WHERE id = $id")->fetch_assoc();
        if (!$req || $req['status'] !== 'paid') {
            setError('Extra fund request is only allowed for paid requisitions.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        // Mark parent as extra_requested
        $this->db->query("UPDATE fund_requisitions SET settlement_status = 'extra_requested' WHERE id = $id");

        // Check employee record for requested_by user to see if Dept Head approval is required
        $empRes = $this->db->query("
            SELECT e.is_dept_head, e.reporting_user_id 
            FROM employees e 
            LEFT JOIN users u ON (u.employee_id = e.id OR e.user_id = u.id)
            WHERE u.id = $userId 
            LIMIT 1
        ");
        $empData = ($empRes && $empRes->num_rows > 0) ? $empRes->fetch_assoc() : null;

        $isDeptHead = (int)($empData['is_dept_head'] ?? 0);
        $reportingUserId = !empty($empData['reporting_user_id']) ? (int)$empData['reporting_user_id'] : null;

        if ($isDeptHead === 0 && !empty($reportingUserId)) {
            $isDeptApprovalRequired = 1;
            $deptHeadStatus = 'pending';
            $initialStatus = 'pending_dept';
        } else {
            $isDeptApprovalRequired = 0;
            $deptHeadStatus = 'not_required';
            $initialStatus = 'pending_qc';
        }

        $accountId = !empty($req['account_id']) ? (int)$req['account_id'] : null;

        $stmt = $this->db->prepare("
            INSERT INTO fund_requisitions
            (department_id, project_id, requested_by, requisition_type, category,
             amount, purpose, status, is_dept_approval_required, dept_head_status, account_id, parent_requisition_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            'iiissdssisii',
            $req['department_id'],
            $req['project_id'],
            $userId,
            $req['requisition_type'],
            $req['category'],
            $extraAmount,
            $reason,
            $initialStatus,
            $isDeptApprovalRequired,
            $deptHeadStatus,
            $accountId,
            $id
        );

        if ($stmt->execute()) {
            $newReqId = $stmt->insert_id;
            $this->syncApprovalRequest($newReqId);
            setSuccess('Extra fund request submitted. Awaiting approval.');
        } else {
            setError('Failed to create extra fund request: ' . $this->db->error);
        }
        header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
        exit;
    }
    public function confirmReceipt($id) {
        $id = (int)$id;
        $userId = (int)$_SESSION['user']['id'];
        
        $req = $this->db->query("SELECT requested_by, status, is_received FROM fund_requisitions WHERE id = $id")->fetch_assoc();
        if (!$req) {
            setError('Requisition not found.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        if ($req['requested_by'] != $userId && !in_array($_SESSION['user']['role_id'], [1, 3])) {
            setError('Only the requester can confirm receipt of funds.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions/view/" . $id);
            exit;
        }

        if (!in_array($req['status'], ['paid', 'partially_paid'])) {
            setError('Funds must be disbursed by accounts before you can confirm receipt.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions/view/" . $id);
            exit;
        }
        
        if ($req['is_received']) {
            setError('Receipt already confirmed.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions/view/" . $id);
            exit;
        }

        $stmt = $this->db->prepare("UPDATE fund_requisitions SET is_received = 1, received_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            setSuccess('Receipt of funds has been confirmed successfully.');
        } else {
            setError('Failed to confirm receipt: ' . $this->db->error);
        }
        
        header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions/view/" . $id);
        exit;
    }

    private function syncApprovalRequest($fundReqId) {
        $fundReqId = (int)$fundReqId;
        $sql = "
            SELECT fr.*, d.name AS dept_name
            FROM fund_requisitions fr
            LEFT JOIN departments d ON fr.department_id = d.id
            WHERE fr.id = $fundReqId
            LIMIT 1
        ";
        $res = $this->db->query($sql);
        if (!$res || $res->num_rows == 0) {
            $this->db->query("DELETE FROM approval_requests WHERE source_table = 'fund_requisitions' AND source_id = $fundReqId");
            return;
        }
        $row = $res->fetch_assoc();

        $check = $this->db->query("SELECT id FROM approval_requests WHERE source_table = 'fund_requisitions' AND source_id = $fundReqId");
        
        $deptName = !empty($row['dept_name']) ? $row['dept_name'] : 'General';
        $requestType = $row['requisition_type'];
        $title = "Fund Requisition: " . (mb_strlen($row['purpose']) > 80 ? mb_substr($row['purpose'], 0, 80) . '...' : $row['purpose']);
        $amount = (float)$row['amount'];
        $approvedAmount = (!empty($row['approved_amount']) && (float)$row['approved_amount'] > 0)
            ? (float)$row['approved_amount']
            : ((!empty($row['qc_approved_amount']) && (float)$row['qc_approved_amount'] > 0)
                ? (float)$row['qc_approved_amount']
                : (in_array($row['status'], ['approved', 'paid']) ? $amount : 0));
        if ($approvedAmount == 0 && $row['status'] === 'pending_dept') {
            $approvedAmount = $amount;
        }
        $requestedBy = (int)$row['requested_by'];
        
        // Dynamically resolve approve_role and status according to current stage in approval chain
        $frStatus = $row['status'];
        if ($frStatus === 'pending_dept') {
            $approveRole = 'dept_head';
            $status = 'pending';
        } elseif ($frStatus === 'pending_qc') {
            $approveRole = 'qc';
            $status = 'pending';
        } elseif ($frStatus === 'pending') {
            $approveRole = 'management';
            $status = 'pending';
        } elseif ($frStatus === 'approved') {
            $isAhAppr = false;
            if ($check && $check->num_rows > 0) {
                $arCheckRow = $this->db->query("SELECT accounts_head_status FROM approval_requests WHERE source_table = 'fund_requisitions' AND source_id = $fundReqId ORDER BY id DESC LIMIT 1")->fetch_assoc();
                $isAhAppr = (strtolower($arCheckRow['accounts_head_status'] ?? '') === 'approved');
            }
            $approveRole = $isAhAppr ? 'accounts' : 'accounts_head';
            $status = 'approved';
        } elseif ($frStatus === 'partially_approved') {
            // Management approved a part: approved part goes to Accounts Head / Accounts,
            // remaining balance still stays in Management's queue.
            $approveRole = 'management';
            $status = 'partially_approved';
        } elseif (in_array($frStatus, ['paid', 'partially_paid'])) {
            $approveRole = ($frStatus === 'paid') ? 'completed' : 'accounts';
            $status = ($frStatus === 'paid') ? 'approved' : 'partially_paid';
        } elseif ($frStatus === 'rejected') {
            $approveRole = 'management';
            $status = 'rejected';
        } else {
            $approveRole = 'management';
            $status = 'pending';
        }

        $approvedBy = !empty($row['approved_by']) ? (int)$row['approved_by'] : null;
        $approvedAt = !empty($row['approved_at']) ? $row['approved_at'] : null;
        $approvalNote = !empty($row['approval_note']) ? $row['approval_note'] : null;
        $metaData = json_encode(['purpose' => $row['purpose'], 'category' => $row['category']]);
        $createdAt = $row['created_at'];

        if ($check && $check->num_rows == 0) {
            $stmt = $this->db->prepare("
                INSERT INTO approval_requests 
                (department, request_type, title, source_table, source_id, request_amount, approved_amount, requested_by, approve_role, status, approved_by, approved_at, approval_note, meta_data, created_at)
                VALUES (?, ?, ?, 'fund_requisitions', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if ($stmt) {
                $stmt->bind_param("sssiddississss", $deptName, $requestType, $title, $fundReqId, $amount, $approvedAmount, $requestedBy, $approveRole, $status, $approvedBy, $approvedAt, $approvalNote, $metaData, $createdAt);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            $stmt = $this->db->prepare("
                UPDATE approval_requests 
                SET department = ?, request_type = ?, title = ?, request_amount = ?, approved_amount = ?, approve_role = ?, status = ?, approved_by = ?, approved_at = ?, approval_note = ?
                WHERE source_table = 'fund_requisitions' AND source_id = ?
            ");
            if ($stmt) {
                $stmt->bind_param("sssddssissi", $deptName, $requestType, $title, $amount, $approvedAmount, $approveRole, $status, $approvedBy, $approvedAt, $approvalNote, $fundReqId);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    /**
     * Update Fund Requisition — Creator or Admin ONLY before 1st approval
     */
    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions/view/" . (int)$id);
            exit;
        }

        $id     = (int)$id;
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $isAdmin = in_array($roleId, [1, 3], true);

        $res = $this->db->query("SELECT * FROM fund_requisitions WHERE id = {$id} LIMIT 1");
        $req = ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;
        if (!$req) {
            setError('Fund requisition not found.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $isOwner = ((int)($req['requested_by'] ?? 0) === $userId);
        if (!$isOwner && !$isAdmin) {
            setError('Only the creator or Admin can edit this fund requisition.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions/view/{$id}");
            exit;
        }

        require_once __DIR__ . '/../../services/RequisitionRevisionService.php';
        $revSvc  = new \RequisitionRevisionService($this->db);
        $openRev = $revSvc->getOpenRevision('fund_requisitions', $id);

        $initialExpectedStatus = ((int)($req['is_dept_approval_required'] ?? 0) === 1) ? 'pending_dept' : 'pending_qc';
        $hasAnyApproval = ($req['status'] !== $initialExpectedStatus)
            || ($req['dept_head_status'] ?? '') === 'approved'
            || !empty($req['dept_head_approved_by'])
            || !empty($req['qc_approved_by'])
            || !empty($req['approved_by'])
            || !empty($req['paid_by']);

        if ($hasAnyApproval && empty($openRev)) {
            setError('This fund requisition has already been approved at one or more stages and can no longer be edited.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions/view/{$id}");
            exit;
        }

        $rawAmount = trim(str_replace([',', ' '], '', $_POST['amount'] ?? ''));
        $amount    = is_numeric($rawAmount) ? (float)$rawAmount : 0;
        $category  = trim($_POST['category'] ?? ($req['category'] ?? 'Other Expense'));
        $purpose   = trim($_POST['purpose'] ?? '');
        $location  = trim($_POST['location'] ?? '');
        $purposeToVisit  = trim($_POST['purpose_to_visit'] ?? '');
        $modeOfTransport = trim($_POST['mode_of_transport'] ?? '');

        if ($amount <= 0 || $purpose === '') {
            setError('Please enter a valid amount greater than 0 and a purpose.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions/view/{$id}");
            exit;
        }
        if (($req['requisition_type'] ?? 'general') === 'petty_cash' && $amount > 10000) {
            setError('Maximum Petty Cash request limit is BDT 10,000.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions/view/{$id}");
            exit;
        }

        $locationVal  = ($location !== '') ? $location : null;
        $visitVal     = ($purposeToVisit !== '') ? $purposeToVisit : null;
        $transportVal = ($modeOfTransport !== '') ? $modeOfTransport : null;

        $stmt = $this->db->prepare("
            UPDATE fund_requisitions
            SET category = ?, amount = ?, purpose = ?, location = ?, purpose_to_visit = ?, mode_of_transport = ?, updated_at = NOW()
            WHERE id = ?
        ");
        if ($stmt) {
            $stmt->bind_param("sdssssi", $category, $amount, $purpose, $locationVal, $visitVal, $transportVal, $id);
            if ($stmt->execute()) {
                $stmt->close();
                $this->syncApprovalRequest($id);
                $editSummary = \RequisitionRevisionService::buildChangeSummary([
                    'Amount'   => [$req['amount'] ?? 0, $amount],
                    'Category' => [$req['category'] ?? '', $category],
                    'Purpose'  => [$req['purpose'] ?? '', $purpose],
                ]);
                $revSvc->logEdit('fund_requisitions', $id, $editSummary);
                if (!empty($openRev)) {
                    $revSvc->markResubmitted('fund_requisitions', $id, $amount, trim($_POST['resubmitted_note'] ?? ''));
                    setSuccess("Fund Requisition #{$id} updated & resubmitted directly to {$openRev['return_stage_label']}.");
                } else {
                    setSuccess("Fund Requisition #{$id} updated successfully.");
                }
            } else {
                $stmt->close();
                setError('Failed to update fund requisition.');
            }
        } else {
            setError('Database error while updating fund requisition.');
        }

        $redirectTo = (($_POST['redirect_to'] ?? '') === 'index')
            ? ($GLOBALS['base_url'] . "/fund-requisitions")
            : ($GLOBALS['base_url'] . "/fund-requisitions/view/{$id}");
        header("Location: " . $redirectTo);
        exit;
    }
}
