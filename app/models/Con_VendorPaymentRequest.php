<?php

class Con_VendorPaymentRequest extends Model
{
    protected $table = 'vendor_payment_requests';

    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->db = $db ?? $GLOBALS['db'];
        $this->ensureDocumentsTable();
        $this->ensurePurchaseApprovalColumns();
    }

    /**
     * Ensure Purchase Team Leader & Purchase Head approval columns exist on vendor_payment_requests
     */
    public function ensurePurchaseApprovalColumns()
    {
        static $checked = false;
        if ($checked || !$this->db) {
            return;
        }
        $checked = true;

        $colCheck = $this->db->query("SHOW COLUMNS FROM `{$this->table}` LIKE 'purchase_head_approved_by_id'");
        if (!$colCheck || $colCheck->num_rows === 0) {
            @$this->db->query("ALTER TABLE `{$this->table}` 
                ADD COLUMN `purchase_team_leader_by_id` INT(11) DEFAULT NULL AFTER `created_by`,
                ADD COLUMN `purchase_team_leader_at` DATETIME DEFAULT NULL AFTER `purchase_team_leader_by_id`,
                ADD COLUMN `purchase_team_leader_note` TEXT DEFAULT NULL AFTER `purchase_team_leader_at`,
                ADD COLUMN `purchase_head_approved_by_id` INT(11) DEFAULT NULL AFTER `purchase_team_leader_note`,
                ADD COLUMN `purchase_head_approved_at` DATETIME DEFAULT NULL AFTER `purchase_head_approved_by_id`,
                ADD COLUMN `purchase_head_note` TEXT DEFAULT NULL AFTER `purchase_head_approved_at`");

            // Auto-clear Purchase Head step for bills submitted directly by Purchase Head (Role 17) or Management (Role 1, 3)
            @$this->db->query("
                UPDATE `{$this->table}` pr
                INNER JOIN `users` u ON u.id = pr.created_by
                SET pr.purchase_head_approved_by_id = pr.created_by,
                    pr.purchase_head_approved_at = pr.created_at,
                    pr.purchase_head_note = 'Auto-approved upon submission by Head of Purchase / Management'
                WHERE pr.purchase_head_approved_by_id IS NULL
                  AND u.role_id IN (1, 3, 17)
            ");

            // Auto-mark Team Leader review for bills submitted by Team Leader (Role 31)
            @$this->db->query("
                UPDATE `{$this->table}` pr
                INNER JOIN `users` u ON u.id = pr.created_by
                SET pr.purchase_team_leader_by_id = pr.created_by,
                    pr.purchase_team_leader_at = pr.created_at,
                    pr.purchase_team_leader_note = 'Submitted by Purchase Team Leader'
                WHERE pr.purchase_team_leader_by_id IS NULL
                  AND u.role_id = 31
            ");

            // Route currently pending Submitted bills (without Purchase Head approval) to purchase_head in approval_requests
            @$this->db->query("
                UPDATE `approval_requests` ar
                INNER JOIN `{$this->table}` pr ON pr.id = ar.source_id AND ar.source_table = 'vendor_payment_requests'
                SET ar.approve_role = 'purchase_head'
                WHERE pr.status = 'Submitted'
                  AND pr.purchase_head_approved_by_id IS NULL
                  AND ar.status = 'pending'
            ");
        }

        $accColCheck = $this->db->query("SHOW COLUMNS FROM `{$this->table}` LIKE 'account_id'");
        if (!$accColCheck || $accColCheck->num_rows === 0) {
            @$this->db->query("ALTER TABLE `{$this->table}` ADD COLUMN `account_id` INT(11) DEFAULT NULL");
        }

        $chkEa = @$this->db->query("SHOW COLUMNS FROM `expense_adjustments` LIKE 'parent_type'");
        if ($chkEa && $chkEa->num_rows === 0) {
            @$this->db->query("ALTER TABLE `expense_adjustments` ADD COLUMN `parent_type` VARCHAR(50) NOT NULL DEFAULT 'cashout'");
        }
    }

    /**
     * Ensure vendor_payment_request_documents table exists
     */
    public function ensureDocumentsTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `vendor_payment_request_documents` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `vendor_payment_request_id` INT UNSIGNED NOT NULL,
            `document_path` VARCHAR(255) DEFAULT NULL,
            `note` TEXT DEFAULT NULL,
            `uploaded_by` INT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_vpr_id` (`vendor_payment_request_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        @$this->db->query($sql);
    }

    // =========================================================================
    // READ
    // =========================================================================

    /**
     * Get all vendor payment requests with relations + approved_by/payment_by names
     * @param int|null $userId  If set, only show requests created by this user or for projects they are assigned to
     * @param array $assignedProjectIds  List of project IDs assigned to the user
     */
    /**
     * Get all vendor payment requests with relations + approved_by/payment_by names
     * @param int|null $userId  If set, only show requests created by this user or for projects they are assigned to
     * @param array $assignedProjectIds  List of project IDs assigned to the user
     * @param array $filters  Optional search/filter parameters: project_id, vendor_id, category_id, status, search
     */
    public function getAll($userId = null, $assignedProjectIds = [], $filters = [])
    {
        // Auto-heal / sync missing records in central approval_requests before querying
        try {
            if (!class_exists('ApprovalRequest')) {
                require_once __DIR__ . '/ApprovalRequest.php';
            }
            $appReqModel = new \ApprovalRequest($this->db);
            $appReqModel->syncMissingVendorPayments();
        } catch (\Throwable $t) {
            // graceful fail
        }

        $sql = "SELECT pr.*,
                       v.name       AS vendor_name,
                       p.name       AS project_name,
                       po.id        AS po_number,
                       u.full_name  AS created_by_name,
                       u.role_id    AS created_by_role_id,
                       ptl.full_name AS purchase_team_leader_by_name,
                       ph.full_name AS purchase_head_approved_by_name,
                       ab.full_name AS approved_by_name,
                       ab_r.name    AS approved_by_role,
                       qc.full_name AS qc_approved_by_name,
                       qc_r.name    AS qc_approved_by_role,
                       pb.full_name AS payment_by_name,
                       COALESCE(txn_sum.total_paid, 0) AS already_paid,
                       ar.id AS approval_request_id,
                       ar.approve_role,
                       ar.op_head_status,
                       ar.accounts_head_status,
                       ar.status AS approval_request_status
                FROM {$this->table} pr
                LEFT JOIN approval_requests ar ON (ar.source_table = 'vendor_payment_requests' AND ar.source_id = pr.id)
                LEFT JOIN vendors v          ON v.id  = pr.vendor_id
                LEFT JOIN projects p         ON p.id  = pr.project_id
                LEFT JOIN purchase_orders po ON po.id = pr.purchase_order_id
                LEFT JOIN users u            ON u.id  = pr.created_by
                LEFT JOIN users ptl          ON ptl.id = pr.purchase_team_leader_by_id
                LEFT JOIN users ph           ON ph.id = pr.purchase_head_approved_by_id
                LEFT JOIN users ab           ON ab.id = pr.approved_by
                LEFT JOIN roles ab_r         ON ab_r.id = ab.role_id
                LEFT JOIN users qc           ON qc.id = pr.qc_approved_by_id
                LEFT JOIN roles qc_r         ON qc_r.id = qc.role_id
                LEFT JOIN (
                    SELECT t1.ref_id, t1.processed_by
                    FROM con_financial_transactions t1
                    INNER JOIN (
                        SELECT MAX(id) AS max_id
                        FROM con_financial_transactions
                        WHERE ref_type = 'vendor_payment'
                        GROUP BY ref_id
                    ) t2 ON t1.id = t2.max_id
                ) latest_txn ON latest_txn.ref_id = pr.id
                LEFT JOIN users pb ON pb.id = latest_txn.processed_by
                LEFT JOIN (
                    SELECT ref_id, SUM(amount_paid) AS total_paid
                    FROM con_financial_transactions
                    WHERE ref_type = 'vendor_payment'
                    GROUP BY ref_id
                ) txn_sum ON txn_sum.ref_id = pr.id";

        $whereClauses = [];
        if ($userId !== null) {
            $userCond = "pr.created_by = " . (int)$userId;
            if (!empty($assignedProjectIds)) {
                $idList = implode(',', array_map('intval', $assignedProjectIds));
                $whereClauses[] = "($userCond OR pr.project_id IN ($idList))";
            } else {
                $whereClauses[] = $userCond;
            }
        }

        // 1. Project filter
        if (!empty($filters['project_id']) && is_numeric($filters['project_id'])) {
            $whereClauses[] = "pr.project_id = " . (int)$filters['project_id'];
        }

        // 2. Vendor filter
        if (!empty($filters['vendor_id']) && is_numeric($filters['vendor_id'])) {
            $whereClauses[] = "pr.vendor_id = " . (int)$filters['vendor_id'];
        }

        // 3. Material Category filter (through PO items & materials)
        if (!empty($filters['category_id']) && is_numeric($filters['category_id'])) {
            $catId = (int)$filters['category_id'];
            $whereClauses[] = "pr.purchase_order_id IN (
                SELECT poi.purchase_order_id 
                FROM purchase_order_items poi 
                JOIN con_materials m ON m.id = poi.material_id 
                WHERE m.category_id = {$catId}
            )";
        }

        // 4. Status / Workflow Stage filter (Pending, Apex Approved, Payment done)
        if (!empty($filters['status'])) {
            $st = trim($filters['status']);
            if ($st === 'my_applicable') {
                $sessionUserRole = (int)($_SESSION['user']['role_id'] ?? 0);
                $sessionRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
                $isChiefOfficer = (in_array($sessionUserRole, [33, 34, 35], true) || in_array($sessionRoleName, ['chief officer', 'management viewer', 'seal ceo'], true));

                $isFullManagement = (in_array($sessionUserRole, [1, 3, 38], true)
                    || strpos($sessionRoleName, 'director') !== false
                    || strpos($sessionRoleName, 'dceo') !== false
                    || strpos($sessionRoleName, 'management') !== false
                    || strpos($sessionRoleName, 'super admin') !== false)
                    && !$isChiefOfficer;

                $isRole20 = (in_array($sessionUserRole, [20, 32], true)
                    || strpos($sessionRoleName, 'operation head') !== false
                    || strpos($sessionRoleName, 'admin & operation') !== false)
                    && !$isChiefOfficer;

                $isAccountsHead = ($sessionUserRole === 30 || strpos($sessionRoleName, 'accounts head') !== false) && !$isChiefOfficer;
                $isAccountsStaff = ($sessionUserRole === 6 || (strpos($sessionRoleName, 'account') !== false && !$isAccountsHead)) && !$isChiefOfficer;

                $isQC = ($sessionUserRole === 24
                    || strpos($sessionRoleName, 'qc') !== false
                    || strpos($sessionRoleName, 'quality controller') !== false)
                    && !$isChiefOfficer;

                $isPurchaseHead = (in_array($sessionUserRole, [17, 31], true)
                    || strpos($sessionRoleName, 'purchase head') !== false
                    || strpos($sessionRoleName, 'head of purchase') !== false
                    || strpos($sessionRoleName, 'purchase team leader') !== false)
                    && !$isChiefOfficer;

                if ($isPurchaseHead) {
                    $whereClauses[] = "(pr.status = 'Submitted' AND pr.purchase_head_approved_by_id IS NULL)";
                } elseif ($isQC) {
                    $whereClauses[] = "(pr.status = 'Submitted' AND pr.purchase_head_approved_by_id IS NOT NULL)";
                } elseif ($isRole20) {
                    $whereClauses[] = "(pr.status = 'QC Approved' AND (ar.op_head_status IS NULL OR ar.op_head_status != 'approved'))";
                } elseif ($isAccountsHead) {
                    $whereClauses[] = "((pr.status = 'QC Approved' AND ar.op_head_status = 'approved' AND (ar.accounts_head_status IS NULL OR ar.accounts_head_status != 'approved')) OR (pr.status = 'Approved' AND (ar.accounts_head_status IS NULL OR ar.accounts_head_status != 'approved')))";
                } elseif ($isFullManagement) {
                    // For Management: either items waiting on final management sign-off, or requests awaiting Op Head / Accounts Head that they can act on
                    $whereClauses[] = "(
                        (pr.status = 'QC Approved' AND (
                            (ar.op_head_status = 'approved' AND ar.accounts_head_status = 'approved' AND (ar.request_amount > 10000.01 OR pr.net_payable > 10000.01))
                            OR (ar.op_head_status IS NULL OR ar.op_head_status != 'approved')
                            OR (ar.accounts_head_status IS NULL OR ar.accounts_head_status != 'approved')
                        ))
                        OR (pr.status = 'Submitted')
                    )";
                } elseif ($isAccountsStaff) {
                    $whereClauses[] = "(pr.status IN ('Approved', 'Partially_Paid') AND ar.accounts_head_status = 'approved' AND (pr.due_amount > 0 OR (pr.net_payable - COALESCE(txn_sum.total_paid, 0)) > 0))";
                } else {
                    // Other creators / viewers: their own pending requests
                    $whereClauses[] = "(pr.status IN ('Submitted', 'QC Approved'))";
                }
            } elseif ($st === 'pending' || $st === 'all_pending') {
                $whereClauses[] = "pr.status IN ('Submitted', 'QC Approved')";
            } elseif ($st === 'pending_purchase_head') {
                $whereClauses[] = "pr.status = 'Submitted' AND pr.purchase_head_approved_by_id IS NULL";
            } elseif ($st === 'pending_qc') {
                $whereClauses[] = "pr.status = 'Submitted' AND pr.purchase_head_approved_by_id IS NOT NULL";
            } elseif ($st === 'pending_apex' || $st === 'pending_mgmt') {
                $whereClauses[] = "pr.status = 'QC Approved'";
            } elseif ($st === 'ready_to_pay' || $st === 'partially_paid') {
                $whereClauses[] = "pr.status = 'Partially_Paid'";
            } elseif ($st === 'apex_approved' || $st === 'approved' || $st === 'newly_approved') {
                $whereClauses[] = "pr.status = 'Approved'";
            } elseif ($st === 'fully_paid') {
                $whereClauses[] = "pr.status IN ('Fully_Paid', 'Paid')";
            } elseif ($st === 'all_paid') {
                $whereClauses[] = "pr.status IN ('Partially_Paid', 'Fully_Paid', 'Paid')";
            } elseif ($st === 'rejected') {
                $whereClauses[] = "pr.status = 'Rejected'";
            } else {
                $escSt = $this->db->real_escape_string($st);
                $whereClauses[] = "pr.status = '{$escSt}'";
            }
        }

        // 5. Keyword search filter
        if (!empty($filters['search'])) {
            $rawKw = trim($filters['search']);
            $kw = $this->db->real_escape_string($rawKw);
            $cleanId = preg_replace('/[^0-9]/', '', $rawKw);
            $idCond = !empty($cleanId) ? " OR pr.id = " . (int)$cleanId . " OR po.id = " . (int)$cleanId : "";
            $whereClauses[] = "(
                pr.id LIKE '%{$kw}%'
                {$idCond}
                OR pr.invoice_number LIKE '%{$kw}%'
                OR v.name LIKE '%{$kw}%'
                OR v.company_name LIKE '%{$kw}%'
                OR p.name LIKE '%{$kw}%'
                OR po.id LIKE '%{$kw}%'
                OR u.full_name LIKE '%{$kw}%'
            )";
        }

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }

        $sql .= " ORDER BY 
            CASE 
                WHEN pr.status = 'Submitted' THEN 1
                WHEN pr.status = 'QC Approved' THEN 2
                WHEN pr.status = 'Approved' THEN 3
                WHEN pr.status = 'Partially_Paid' THEN 4
                WHEN pr.status = 'Draft' THEN 5
                WHEN pr.status IN ('Fully_Paid', 'Paid') THEN 6
                WHEN pr.status = 'Rejected' THEN 7
                ELSE 8
            END ASC,
            pr.id DESC";

        $result = $this->db->query($sql);
        $records = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        // Attach Material Categories and Material items map
        if (!empty($records)) {
            $poIds = array_unique(array_filter(array_column($records, 'purchase_order_id')));
            if (!empty($poIds)) {
                $poIdList = implode(',', array_map('intval', $poIds));
                $poCats = [];
                $resMat = $this->db->query("
                    SELECT poi.purchase_order_id, 
                           GROUP_CONCAT(DISTINCT mc.id) as category_ids,
                           GROUP_CONCAT(DISTINCT mc.name SEPARATOR ', ') as category_names,
                           GROUP_CONCAT(DISTINCT m.name SEPARATOR ', ') as material_names
                    FROM purchase_order_items poi
                    JOIN con_materials m ON m.id = poi.material_id
                    JOIN con_material_categories mc ON mc.id = m.category_id
                    WHERE poi.purchase_order_id IN ({$poIdList})
                    GROUP BY poi.purchase_order_id
                ");
                if ($resMat) {
                    while ($r = $resMat->fetch_assoc()) {
                        $poCats[$r['purchase_order_id']] = $r;
                    }
                }

                foreach ($records as &$rec) {
                    $pId = $rec['purchase_order_id'] ?? null;
                    $rec['category_names'] = $poCats[$pId]['category_names'] ?? null;
                    $rec['category_ids']   = $poCats[$pId]['category_ids'] ?? null;
                    $rec['material_names'] = $poCats[$pId]['material_names'] ?? null;
                }
            }
        }

        return $records;
    }

    /**
     * Get counts for quick filter KPI pills
     */
    public function getFilterCounts($userId = null, $assignedProjectIds = [])
    {
        $whereUser = "";
        if ($userId !== null) {
            $userCond = "created_by = " . (int)$userId;
            if (!empty($assignedProjectIds)) {
                $idList = implode(',', array_map('intval', $assignedProjectIds));
                $whereUser = " WHERE ($userCond OR project_id IN ($idList))";
            } else {
                $whereUser = " WHERE $userCond";
            }
        }

        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'Submitted' AND purchase_head_approved_by_id IS NULL THEN 1 ELSE 0 END) as pending_purchase_head,
                    SUM(CASE WHEN status = 'Submitted' AND purchase_head_approved_by_id IS NOT NULL THEN 1 ELSE 0 END) as pending_qc,
                    SUM(CASE WHEN status = 'QC Approved' THEN 1 ELSE 0 END) as pending_apex,
                    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as apex_approved,
                    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as newly_approved, 
                    SUM(CASE WHEN status = 'Partially_Paid' THEN 1 ELSE 0 END) as ready_to_pay,
                    SUM(CASE WHEN status = 'Partially_Paid' THEN 1 ELSE 0 END) as partially_paid,
                    SUM(CASE WHEN status IN ('Fully_Paid', 'Paid') THEN 1 ELSE 0 END) as fully_paid,
                    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
                FROM {$this->table} {$whereUser}";

        $res = $this->db->query($sql);
        $counts = $res ? $res->fetch_assoc() : [
            'total'                 => 0,
            'pending_purchase_head' => 0,
            'pending_qc'            => 0,
            'pending_apex'          => 0,
            'newly_approved'        => 0,
            'apex_approved'         => 0,
            'ready_to_pay'          => 0,
            'partially_paid'        => 0,
            'fully_paid'            => 0,
            'rejected'              => 0
        ];

        // Dynamic count for My Applicable
        $myAppCond = "";
        $sessionUserRole = (int)($_SESSION['user']['role_id'] ?? 0);
        $sessionRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
        $isChiefOfficer = (in_array($sessionUserRole, [33, 34, 35], true) || in_array($sessionRoleName, ['chief officer', 'management viewer', 'seal ceo'], true));

        $isFullManagement = (in_array($sessionUserRole, [1, 3, 38], true)
            || strpos($sessionRoleName, 'director') !== false
            || strpos($sessionRoleName, 'dceo') !== false
            || strpos($sessionRoleName, 'management') !== false
            || strpos($sessionRoleName, 'super admin') !== false)
            && !$isChiefOfficer;

        $isRole20 = (in_array($sessionUserRole, [20, 32], true)
            || strpos($sessionRoleName, 'operation head') !== false
            || strpos($sessionRoleName, 'admin & operation') !== false)
            && !$isChiefOfficer;

        $isAccountsHead = ($sessionUserRole === 30 || strpos($sessionRoleName, 'accounts head') !== false) && !$isChiefOfficer;
        $isAccountsStaff = ($sessionUserRole === 6 || (strpos($sessionRoleName, 'account') !== false && !$isAccountsHead)) && !$isChiefOfficer;

        $isQC = ($sessionUserRole === 24
            || strpos($sessionRoleName, 'qc') !== false
            || strpos($sessionRoleName, 'quality controller') !== false)
            && !$isChiefOfficer;

        $isPurchaseHead = (in_array($sessionUserRole, [17, 31], true)
            || strpos($sessionRoleName, 'purchase head') !== false
            || strpos($sessionRoleName, 'head of purchase') !== false
            || strpos($sessionRoleName, 'purchase team leader') !== false)
            && !$isChiefOfficer;

        if ($isPurchaseHead) {
            $myAppCond = "pr.status = 'Submitted' AND pr.purchase_head_approved_by_id IS NULL";
        } elseif ($isQC) {
            $myAppCond = "pr.status = 'Submitted' AND pr.purchase_head_approved_by_id IS NOT NULL";
        } elseif ($isRole20) {
            $myAppCond = "pr.status = 'QC Approved' AND (ar.op_head_status IS NULL OR ar.op_head_status != 'approved')";
        } elseif ($isAccountsHead) {
            $myAppCond = "((pr.status = 'QC Approved' AND ar.op_head_status = 'approved' AND (ar.accounts_head_status IS NULL OR ar.accounts_head_status != 'approved')) OR (pr.status = 'Approved' AND (ar.accounts_head_status IS NULL OR ar.accounts_head_status != 'approved')))";
        } elseif ($isFullManagement) {
            $myAppCond = "(
                (pr.status = 'QC Approved' AND (
                    (ar.op_head_status = 'approved' AND ar.accounts_head_status = 'approved' AND (ar.request_amount > 10000.01 OR pr.net_payable > 10000.01))
                    OR (ar.op_head_status IS NULL OR ar.op_head_status != 'approved')
                    OR (ar.accounts_head_status IS NULL OR ar.accounts_head_status != 'approved')
                ))
                OR (pr.status = 'Submitted')
            )";
        } elseif ($isAccountsStaff) {
            $myAppCond = "pr.status IN ('Approved', 'Partially_Paid') AND ar.accounts_head_status = 'approved' AND (pr.due_amount > 0 OR pr.due_amount IS NULL)";
        } else {
            $myAppCond = "pr.status IN ('Submitted', 'QC Approved')";
        }

        $myAppUserWhere = "";
        if ($userId !== null) {
            $userCond = "pr.created_by = " . (int)$userId;
            if (!empty($assignedProjectIds)) {
                $idList = implode(',', array_map('intval', $assignedProjectIds));
                $myAppUserWhere = " AND ($userCond OR pr.project_id IN ($idList))";
            } else {
                $myAppUserWhere = " AND $userCond";
            }
        }

        $myAppSql = "SELECT COUNT(*) as cnt 
                     FROM {$this->table} pr
                     LEFT JOIN approval_requests ar ON (ar.source_table = 'vendor_payment_requests' AND ar.source_id = pr.id)
                     WHERE {$myAppCond} {$myAppUserWhere}";
        $myAppRes = @$this->db->query($myAppSql);
        $counts['my_applicable'] = ($myAppRes && $myAppRow = $myAppRes->fetch_assoc()) ? (int)$myAppRow['cnt'] : 0;

        return $counts;
    }

    /**
     * Get all active material categories for filter dropdown
     */
    public function getMaterialCategories()
    {
        $categories = [];
        $res = $this->db->query("SELECT id, name FROM con_material_categories ORDER BY name ASC");
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $categories[] = $r;
            }
        }
        return $categories;
    }

    public function find($id)
    {
        $stmt = $this->db->prepare(
            "SELECT pr.*,
                    v.name        AS vendor_name,
                    p.name        AS project_name,
                    po.id         AS po_number,
                    u.full_name   AS created_by_name,
                    u.role_id     AS created_by_role_id,
                    ptl.full_name AS purchase_team_leader_by_name,
                    ph.full_name  AS purchase_head_approved_by_name,
                    ab.full_name  AS approved_by_name,
                    ab_r.name     AS approved_by_role,
                    qc.full_name  AS qc_approved_by_name
             FROM {$this->table} pr
             LEFT JOIN vendors v          ON v.id   = pr.vendor_id
             LEFT JOIN projects p         ON p.id   = pr.project_id
             LEFT JOIN purchase_orders po ON po.id  = pr.purchase_order_id
             LEFT JOIN users u            ON u.id   = pr.created_by
             LEFT JOIN users ptl          ON ptl.id = pr.purchase_team_leader_by_id
             LEFT JOIN users ph           ON ph.id  = pr.purchase_head_approved_by_id
             LEFT JOIN users ab           ON ab.id  = pr.approved_by
             LEFT JOIN roles ab_r         ON ab_r.id = ab.role_id
             LEFT JOIN users qc           ON qc.id  = pr.qc_approved_by_id
             WHERE pr.id = ?"
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_assoc() : null;
    }

    /** Get all payment requests for a specific Purchase Order */
    public function getByPurchaseOrder($poId)
    {
        $stmt = $this->db->prepare(
            "SELECT pr.*,
                    v.name AS vendor_name,
                    u.full_name AS created_by_name,
                    COALESCE(paid.total_paid, 0) AS total_paid
             FROM {$this->table} pr
             LEFT JOIN vendors v ON v.id = pr.vendor_id
             LEFT JOIN users u   ON u.id = pr.created_by
             LEFT JOIN (
                 SELECT ref_id, SUM(amount_paid) AS total_paid
                 FROM con_financial_transactions
                 WHERE ref_type = 'vendor_payment'
                 GROUP BY ref_id
             ) paid ON paid.ref_id = pr.id
             WHERE pr.purchase_order_id = ?
             ORDER BY pr.id ASC"
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('i', $poId);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    // =========================================================================
    // HELPER: FETCH UNLINKED PENALTIES
    // =========================================================================

    public function getPendingPenaltySum($vendorId)
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(penalty_amount), 0) AS total_penalty
             FROM vendor_penalties
             WHERE vendor_id = ? AND payment_request_id IS NULL"
        );
        if (!$stmt) {
            return 0.0;
        }
        $stmt->bind_param('i', $vendorId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        return (float)($row['total_penalty'] ?? 0);
    }

    // =========================================================================
    // HELPER: TOTAL ADVANCE PAID FOR A PURCHASE ORDER
    // =========================================================================

    public function getTotalAdvancePaid($poId)
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(cft.amount_paid), 0) AS total_advance
             FROM con_financial_transactions cft
             INNER JOIN {$this->table} vpr ON vpr.id = cft.ref_id
             WHERE cft.ref_type = 'vendor_payment'
               AND vpr.purchase_order_id = ?
               AND cft.payment_type = 'Advance'"
        );
        if (!$stmt) {
            return 0.0;
        }
        $stmt->bind_param('i', $poId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        return (float)($row['total_advance'] ?? 0);
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    public function create($data)
    {
        $this->db->begin_transaction();
        try {
            $paymentType     = $data['payment_type'] ?? 'Final';
            $purchaseOrderId = (int)($data['purchase_order_id'] ?? 0);
            $deliveryVerified = ($paymentType === 'Advance') ? 0 : (int)($data['delivery_verified'] ?? 0);
            $penaltyAmt      = ($paymentType === 'Advance') ? 0.00 : (float)($data['penalty_deducted'] ?? 0);
            $invoiceAmt      = (float)($data['invoice_amount'] ?? 0);
            $netPayable      = ($paymentType === 'Advance')
                                    ? $invoiceAmt
                                    : (float)($data['net_payable'] ?? $invoiceAmt);
            $createdBy       = (int)($data['created_by'] ?? 0);
            $projectId       = (int)($data['project_id'] ?? 0);
            $vendorId        = (int)($data['vendor_id'] ?? 0);

            $invoiceNumber   = !empty($data['invoice_number']) ? trim($data['invoice_number']) : null;
            if (empty($invoiceNumber) && $purchaseOrderId > 0) {
                $invoiceNumber = 'INV-PO-' . $purchaseOrderId;
            }

            // INSERT — matches actual table columns
            $attachmentPath = $data['payment_attachment'] ?? null;
            $accountId      = !empty($data['account_id']) ? (int)$data['account_id'] : null;
            $stmt = $this->db->prepare(
                "INSERT INTO {$this->table}
                    (project_id, vendor_id, purchase_order_id, payment_type, invoice_number,
                     invoice_amount, penalty_amount, net_payable_amount, net_payable,
                     delivery_verified, payment_attachment, status, created_by, account_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Submitted', ?, ?)"
            );
            $stmt->bind_param(
                'iiissddddssii',
                $projectId,
                $vendorId,
                $purchaseOrderId,
                $paymentType,
                $invoiceNumber,
                $invoiceAmt,
                $penaltyAmt,
                $netPayable,      // net_payable_amount
                $netPayable,      // net_payable (same value)
                $deliveryVerified,
                $attachmentPath,
                $createdBy,
                $accountId
            );
            $stmt->execute();
            $requestId = $this->db->insert_id;

            if (!$requestId) {
                throw new \Exception("Insert returned 0 id");
            }

            // Link pending penalties to this request (only for non-Advance)
            if ($paymentType !== 'Advance' && $vendorId > 0) {
                $updStmt = $this->db->prepare(
                    "UPDATE vendor_penalties
                     SET payment_request_id = ?
                     WHERE vendor_id = ? AND payment_request_id IS NULL"
                );
                $updStmt->bind_param('ii', $requestId, $vendorId);
                $updStmt->execute();
            }

            // Auto-handle Purchase Team Leader / Purchase Head step if submitted by Role 31, Role 17, or Management (1, 3)
            $creatorRoleId = (int)($data['creator_role_id'] ?? ($_SESSION['user']['role_id'] ?? 0));
            if (in_array($creatorRoleId, [1, 3, 17], true)) {
                $autoNote = ($creatorRoleId === 17)
                    ? 'Submitted directly by Head of Purchase'
                    : 'Submitted directly by Management';
                $phStmt = $this->db->prepare(
                    "UPDATE {$this->table}
                     SET purchase_head_approved_by_id = ?,
                         purchase_head_approved_at = NOW(),
                         purchase_head_note = ?
                     WHERE id = ?"
                );
                if ($phStmt) {
                    $phStmt->bind_param('isi', $createdBy, $autoNote, $requestId);
                    $phStmt->execute();
                    $phStmt->close();
                }
            } elseif ($creatorRoleId === 31) {
                $tlNote = 'Submitted by Purchase Team Leader / Sr. Executive';
                $tlStmt = $this->db->prepare(
                    "UPDATE {$this->table}
                     SET purchase_team_leader_by_id = ?,
                         purchase_team_leader_at = NOW(),
                         purchase_team_leader_note = ?
                     WHERE id = ?"
                );
                if ($tlStmt) {
                    $tlStmt->bind_param('isi', $createdBy, $tlNote, $requestId);
                    $tlStmt->execute();
                    $tlStmt->close();
                }
            }

            $this->db->commit();
            return $requestId;

        } catch (\Throwable $e) {
            $this->db->rollback();
            error_log("VPR create failed: " . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // PURCHASE TEAM LEADER (OPTIONAL REVIEW) & PURCHASE HEAD (MANDATORY)
    // =========================================================================

    public function reviewPurchaseTeamLeader($id, $userId, $note = '')
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET purchase_team_leader_by_id = ?,
                 purchase_team_leader_at = NOW(),
                 purchase_team_leader_note = ?
             WHERE id = ? AND status = 'Submitted'"
        );
        if (!$stmt) return false;
        $stmt->bind_param('isi', $userId, $note, $id);
        return $stmt->execute();
    }

    public function approvePurchaseHead($id, $userId, $note = '')
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET purchase_head_approved_by_id = ?,
                 purchase_head_approved_at = NOW(),
                 purchase_head_note = ?
             WHERE id = ? AND status = 'Submitted'"
        );
        if (!$stmt) return false;
        $stmt->bind_param('isi', $userId, $note, $id);
        return $stmt->execute();
    }

    public function rejectPurchaseHead($id, $userId, $note = '')
    {
        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare(
                "UPDATE {$this->table}
                 SET status = 'Rejected',
                     purchase_head_approved_by_id = ?,
                     purchase_head_approved_at = NOW(),
                     purchase_head_note = ?,
                     management_note = COALESCE(NULLIF(?, ''), management_note)
                 WHERE id = ? AND status = 'Submitted'"
            );
            $stmt->bind_param('issi', $userId, $note, $note, $id);
            $stmt->execute();

            // Unlink penalties so they can be applied to future requests
            $updStmt = $this->db->prepare(
                "UPDATE vendor_penalties
                 SET payment_request_id = NULL
                 WHERE payment_request_id = ?"
            );
            $updStmt->bind_param('i', $id);
            $updStmt->execute();

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollback();
            return false;
        }
    }

    // =========================================================================
    // QC AUDIT APPROVE & REJECT
    // =========================================================================

    public function approveQC($id, $userId, $qcNote = '', $qcApprovedAmount = null)
    {
        // Auto-check and create qc_approved_amount column if missing
        $colChk = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'qc_approved_amount'");
        $hasQcAmt = ($colChk && $colChk->num_rows > 0);
        if (!$hasQcAmt) {
            @$this->db->query("ALTER TABLE {$this->table} ADD COLUMN qc_approved_amount DECIMAL(16,4) NULL DEFAULT NULL AFTER net_payable");
            $colChk = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'qc_approved_amount'");
            $hasQcAmt = ($colChk && $colChk->num_rows > 0);
        }

        if ($qcApprovedAmount === null || !$hasQcAmt) {
            $stmt = $this->db->prepare(
                "UPDATE {$this->table}
                 SET status = 'QC Approved', 
                     qc_verified = 1, 
                     qc_approved_by_id = ?, 
                     qc_approved_at = NOW(), 
                     qc_note = ?
                 WHERE id = ? AND status = 'Submitted'"
            );
            $stmt->bind_param('isi', $userId, $qcNote, $id);
        } else {
            $stmt = $this->db->prepare(
                "UPDATE {$this->table}
                 SET status = 'QC Approved', 
                     qc_verified = 1, 
                     qc_approved_by_id = ?, 
                     qc_approved_at = NOW(), 
                     qc_note = ?,
                     qc_approved_amount = ?
                 WHERE id = ? AND status = 'Submitted'"
            );
            $stmt->bind_param('isdi', $userId, $qcNote, $qcApprovedAmount, $id);
        }
        return $stmt->execute();
    }

    public function rejectQC($id, $userId, $qcNote = '')
    {
        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare(
                "UPDATE {$this->table}
                 SET status = 'Rejected', 
                     qc_approved_by_id = ?, 
                     qc_approved_at = NOW(), 
                     qc_note = ?
                 WHERE id = ? AND status = 'Submitted'"
            );
            $stmt->bind_param('isi', $userId, $qcNote, $id);
            $stmt->execute();

            // Unlink penalties so they can be applied to future requests
            $updStmt = $this->db->prepare(
                "UPDATE vendor_penalties
                 SET payment_request_id = NULL
                 WHERE payment_request_id = ?"
            );
            $updStmt->bind_param('i', $id);
            $updStmt->execute();

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollback();
            return false;
        }
    }

    // =========================================================================
    // APPROVE — now tracks approved_by and sets approved_amount + due_amount
    // =========================================================================

    public function approve($id, $userId, $approvedAmount = null)
    {
        $request = $this->find($id);
        if (!$request || !in_array($request['status'], ['Submitted', 'QC Approved', 'Approved', 'Partially_Paid'])) return false;

        if ($approvedAmount === null || $approvedAmount <= 0) {
            $approvedAmount = (float)($request['net_payable'] ?? 0);
        }

        // Calculate total paid so far using Con_FinancialTransaction model
        $txnModel = new \Con_FinancialTransaction($this->db);
        $totalPaid = $txnModel->getTotalPaidForSource('vendor_payment', $id);

        $netPayable = (float)($request['net_payable'] ?? 0);

        // Determine VPR status based on payment history
        $newStatus = 'Approved';
        if ($totalPaid > 0) {
            $newStatus = 'Partially_Paid';
        }

        // Due amount for Accounts is the approved amount minus what has been paid so far
        $dueAmount = max(0, $approvedAmount - $totalPaid);

        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET status = ?, approved_amount = ?, approved_by = ?, due_amount = ?
             WHERE id = ?"
        );
        $stmt->bind_param('sdidi', $newStatus, $approvedAmount, $userId, $dueAmount, $id);
        return $stmt->execute();
    }

    // =========================================================================
    // REJECT — now tracks approved_by & supports Submitted, QC Approved, and Approved
    // =========================================================================

    public function reject($id, $userId, $reason = '')
    {
        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare(
                "UPDATE {$this->table}
                 SET status = 'Rejected', approved_by = ?, management_note = COALESCE(NULLIF(?, ''), management_note)
                 WHERE id = ? AND status IN ('Submitted', 'QC Approved', 'Approved', 'Partially_Paid')"
            );
            $stmt->bind_param('isi', $userId, $reason, $id);
            $stmt->execute();

            // Unlink penalties so they can be applied to future requests
            $updStmt = $this->db->prepare(
                "UPDATE vendor_penalties
                 SET payment_request_id = NULL
                 WHERE payment_request_id = ?"
            );
            $updStmt->bind_param('i', $id);
            $updStmt->execute();

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollback();
            return false;
        }
    }
}
