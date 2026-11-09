<?php

class Con_ContractorBill extends Model
{
    protected $table = 'contractor_bill_requests';

    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->db = $db ?? $GLOBALS['db'];
        $this->ensurePurchaseApprovalColumns();
    }

    /**
     * Ensure Purchase Team Leader & Purchase Head approval columns exist on contractor_bill_requests
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
                ADD COLUMN `purchase_team_leader_by_id` INT(11) DEFAULT NULL AFTER `verified_at`,
                ADD COLUMN `purchase_team_leader_at` DATETIME DEFAULT NULL AFTER `purchase_team_leader_by_id`,
                ADD COLUMN `purchase_team_leader_note` TEXT DEFAULT NULL AFTER `purchase_team_leader_at`,
                ADD COLUMN `purchase_head_approved_by_id` INT(11) DEFAULT NULL AFTER `purchase_team_leader_note`,
                ADD COLUMN `purchase_head_approved_at` DATETIME DEFAULT NULL AFTER `purchase_head_approved_by_id`,
                ADD COLUMN `purchase_head_note` TEXT DEFAULT NULL AFTER `purchase_head_approved_at`");

            // For bills submitted by PM (15, 22) or Purchase (17, 23, 31) or Management (1, 3) that are still 'submitted', auto-verify PM stage
            @$this->db->query("
                UPDATE `{$this->table}` cbr
                INNER JOIN `users` u ON u.id = cbr.created_by
                SET cbr.status = 'verified',
                    cbr.verified_by = cbr.created_by,
                    cbr.verified_at = cbr.created_at,
                    cbr.verification_note = 'Auto-verified upon submission'
                WHERE cbr.status = 'submitted'
                  AND u.role_id IN (1, 3, 15, 22, 17, 23, 31)
            ");

            // For bills submitted by Purchase Head (Role 17) or Management (1, 3), auto-clear Purchase Head stage
            @$this->db->query("
                UPDATE `{$this->table}` cbr
                INNER JOIN `users` u ON u.id = cbr.created_by
                SET cbr.purchase_head_approved_by_id = cbr.created_by,
                    cbr.purchase_head_approved_at = cbr.created_at,
                    cbr.purchase_head_note = 'Submitted directly by Head of Purchase / Management'
                WHERE cbr.purchase_head_approved_by_id IS NULL
                  AND u.role_id IN (1, 3, 17)
            ");

            // For bills submitted by Team Leader (Role 31), auto-record Team Leader review
            @$this->db->query("
                UPDATE `{$this->table}` cbr
                INNER JOIN `users` u ON u.id = cbr.created_by
                SET cbr.purchase_team_leader_by_id = cbr.created_by,
                    cbr.purchase_team_leader_at = cbr.created_at,
                    cbr.purchase_team_leader_note = 'Submitted by Purchase Team Leader'
                WHERE cbr.purchase_team_leader_by_id IS NULL
                  AND u.role_id = 31
            ");

            // Route currently pending 'verified' bills (without Purchase Head approval) to purchase_head in approval_requests
            @$this->db->query("
                UPDATE `approval_requests` ar
                INNER JOIN `{$this->table}` cbr ON cbr.id = ar.source_id AND ar.source_table = 'contractor_bill_requests'
                SET ar.approve_role = 'purchase_head'
                WHERE cbr.status = 'verified'
                  AND cbr.purchase_head_approved_by_id IS NULL
                  AND ar.status = 'pending'
            ");
        }

        $accColCheck = $this->db->query("SHOW COLUMNS FROM `{$this->table}` LIKE 'account_id'");
        if (!$accColCheck || $accColCheck->num_rows === 0) {
            @$this->db->query("ALTER TABLE `{$this->table}` ADD COLUMN `account_id` INT(11) DEFAULT NULL");
        }
    }

    /**
     * Get all bills with relations + approved_by/payment_by names
     * @param int|null $userId      If set, show bills created_by this user
     * @param array    $projectIds  If set, also show bills for these project_ids (e.g. site engineer assigned projects)
     */
    public function getAllWithRelations($userId = null, array $projectIds = [], $contractorId = null)
    {
        $sql = "SELECT cbr.*,
                       oo.name  AS operator_name,
                       oo.role  AS operator_role,
                       oo.trade AS operator_trade,
                       p.name   AS project_name,
                       u.full_name  AS created_by_name,
                       u.role_id    AS created_by_role_id,
                       v.full_name  AS verified_by_name,
                       v_r.name     AS verified_by_role,
                       ptl.full_name AS purchase_team_leader_by_name,
                       ph.full_name  AS purchase_head_approved_by_name,
                       ph_r.name     AS purchase_head_approved_by_role,
                       qc_u.full_name AS qc_approved_by_name,
                       qc_r.name    AS qc_approved_by_role,
                       ab.full_name AS approved_by_name,
                       ab_r.name    AS approved_by_role,
                       pb.full_name AS payment_by_name,
                       pwp.week_number AS target_week_number,
                       pwp.week_start_date AS target_week_start_date,
                       pwp.week_end_date AS target_week_end_date,
                       pwp.overall_progress_pct AS target_overall_progress_pct,
                       pp.phase_name AS target_phase_name,
                       pmt.target_month_number,
                       pmt.target_year
                FROM {$this->table} cbr
                LEFT JOIN contractors oo ON oo.id = cbr.operator_id
                LEFT JOIN projects p  ON p.id  = cbr.project_id
                LEFT JOIN users u     ON u.id  = cbr.created_by
                LEFT JOIN users v     ON v.id  = cbr.verified_by
                LEFT JOIN roles v_r   ON v_r.id = v.role_id
                LEFT JOIN users ptl   ON ptl.id = cbr.purchase_team_leader_by_id
                LEFT JOIN users ph    ON ph.id  = cbr.purchase_head_approved_by_id
                LEFT JOIN roles ph_r  ON ph_r.id = ph.role_id
                LEFT JOIN users qc_u  ON qc_u.id = cbr.qc_approved_by_id
                LEFT JOIN roles qc_r  ON qc_r.id = qc_u.role_id
                LEFT JOIN users ab    ON ab.id = cbr.approved_by
                LEFT JOIN roles ab_r  ON ab_r.id = ab.role_id
                LEFT JOIN (
                    SELECT ref_id, processed_by
                    FROM con_financial_transactions
                    WHERE id IN (
                        SELECT MAX(id)
                        FROM con_financial_transactions
                        WHERE ref_type = 'contractor_bill'
                        GROUP BY ref_id
                    )
                ) latest_txn ON latest_txn.ref_id = cbr.id
                LEFT JOIN users pb ON pb.id = latest_txn.processed_by
                LEFT JOIN phase_weekly_progress pwp ON pwp.id = cbr.target_id
                LEFT JOIN project_phases pp ON pp.id = pwp.phase_id
                LEFT JOIN phase_monthly_targets pmt ON pmt.id = pwp.target_id";

        $roleConditions = [];
        if ($userId !== null) {
            $roleConditions[] = "cbr.created_by = " . (int)$userId;
        }

        if (!empty($projectIds)) {
            $safeIds = implode(',', array_map('intval', $projectIds));
            $roleConditions[] = "cbr.project_id IN ($safeIds)";
        }

        $conditions = [];
        if (!empty($roleConditions)) {
            $conditions[] = "(" . implode(' OR ', $roleConditions) . ")";
        }

        if (!empty($contractorId)) {
            $conditions[] = "cbr.operator_id = " . (int)$contractorId;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY cbr.created_at DESC";

        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Build WHERE conditions for filtered bill queries
     */
    protected function buildFilterWhere(array $filters = []): string
    {
        $where = ["1=1"];

        // 1. Role-based restrictions
        $roleConditions = [];
        if (!empty($filters['role_user_id'])) {
            $roleConditions[] = "cbr.created_by = " . (int)$filters['role_user_id'];
        }
        if (!empty($filters['role_project_ids'])) {
            $safeIds = implode(',', array_map('intval', (array)$filters['role_project_ids']));
            if (!empty($safeIds)) {
                $roleConditions[] = "cbr.project_id IN ($safeIds)";
            }
        }
        if (!empty($roleConditions)) {
            $where[] = "(" . implode(' OR ', $roleConditions) . ")";
        }

        // 2. Project filter
        if (!empty($filters['project_id'])) {
            $where[] = "cbr.project_id = " . (int)$filters['project_id'];
        }

        // 3. Creator filter (Submitted By)
        if (!empty($filters['created_by'])) {
            $where[] = "cbr.created_by = " . (int)$filters['created_by'];
        }

        // 4. Contractor filter
        if (!empty($filters['contractor_id'])) {
            $where[] = "cbr.operator_id = " . (int)$filters['contractor_id'];
        }

        // 5. Status filter
        if (!empty($filters['status']) && strtolower($filters['status']) !== 'all') {
            $st = strtolower(trim($filters['status']));
            if ($st === 'paid') {
                $where[] = "cbr.status IN ('paid', 'partially_paid', 'fully_paid')";
            } elseif ($st === 'draft') {
                $where[] = "cbr.status = 'draft'";
            } elseif ($st === 'submitted') {
                $where[] = "cbr.status = 'submitted'";
            } elseif ($st === 'verified') {
                $where[] = "cbr.status = 'verified'";
            } elseif ($st === 'qc approved' || $st === 'qc_approved') {
                $where[] = "cbr.status = 'qc_approved'";
            } elseif ($st === 'approved') {
                $where[] = "cbr.status = 'approved'";
            } elseif ($st === 'rejected') {
                $where[] = "cbr.status = 'rejected'";
            } else {
                $safeSt = $this->db->real_escape_string($st);
                $where[] = "cbr.status = '$safeSt'";
            }
        }

        // 6. Search keyword
        if (!empty($filters['search'])) {
            $s = trim($filters['search']);
            $safeS = $this->db->real_escape_string($s);
            $searchConds = [];

            $digitsOnly = (int)preg_replace('/[^0-9]/', '', $s);
            if ($digitsOnly > 0) {
                $searchConds[] = "cbr.id = {$digitsOnly}";
            }

            $searchConds[] = "p.name LIKE '%{$safeS}%'";
            $searchConds[] = "oo.name LIKE '%{$safeS}%'";
            $searchConds[] = "oo.trade LIKE '%{$safeS}%'";
            $searchConds[] = "u.full_name LIKE '%{$safeS}%'";
            $searchConds[] = "cbr.work_description LIKE '%{$safeS}%'";
            $searchConds[] = "cbr.bill_type LIKE '%{$safeS}%'";
            $searchConds[] = "v.full_name LIKE '%{$safeS}%'";
            $searchConds[] = "qc_u.full_name LIKE '%{$safeS}%'";
            $searchConds[] = "ab.full_name LIKE '%{$safeS}%'";
            $searchConds[] = "pb.full_name LIKE '%{$safeS}%'";

            $where[] = "(" . implode(' OR ', $searchConds) . ")";
        }

        return implode(' AND ', $where);
    }

    /**
     * Get paginated bills with all relations
     */
    public function getFilteredBills(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $whereClause = $this->buildFilterWhere($filters);

        $limitClause = "";
        if ($limit > 0) {
            $limitClause = "LIMIT {$limit} OFFSET {$offset}";
        }

        $sql = "SELECT cbr.*,
                       oo.name  AS operator_name,
                       oo.role  AS operator_role,
                       oo.trade AS operator_trade,
                       p.name   AS project_name,
                       u.full_name  AS created_by_name,
                       u.role_id    AS created_by_role_id,
                       v.full_name  AS verified_by_name,
                       v_r.name     AS verified_by_role,
                       ptl.full_name AS purchase_team_leader_by_name,
                       ph.full_name AS purchase_head_approved_by_name,
                       ph_r.name    AS purchase_head_approved_by_role,
                       qc_u.full_name AS qc_approved_by_name,
                       qc_r.name    AS qc_approved_by_role,
                       ab.full_name AS approved_by_name,
                       ab_r.name    AS approved_by_role,
                       pb.full_name AS payment_by_name,
                       pwp.week_number AS target_week_number,
                       pwp.week_start_date AS target_week_start_date,
                       pwp.week_end_date AS target_week_end_date,
                       pwp.overall_progress_pct AS target_overall_progress_pct,
                       pp.phase_name AS target_phase_name,
                       pmt.target_month_number,
                       pmt.target_year
                FROM {$this->table} cbr
                LEFT JOIN contractors oo ON oo.id = cbr.operator_id
                LEFT JOIN projects p  ON p.id  = cbr.project_id
                LEFT JOIN users u     ON u.id  = cbr.created_by
                LEFT JOIN users v     ON v.id  = cbr.verified_by
                LEFT JOIN roles v_r   ON v_r.id = v.role_id
                LEFT JOIN users ptl   ON ptl.id = cbr.purchase_team_leader_by_id
                LEFT JOIN users ph    ON ph.id  = cbr.purchase_head_approved_by_id
                LEFT JOIN roles ph_r  ON ph_r.id = ph.role_id
                LEFT JOIN users qc_u  ON qc_u.id = cbr.qc_approved_by_id
                LEFT JOIN roles qc_r  ON qc_r.id = qc_u.role_id
                LEFT JOIN users ab    ON ab.id = cbr.approved_by
                LEFT JOIN roles ab_r  ON ab_r.id = ab.role_id
                LEFT JOIN (
                    SELECT ref_id, processed_by
                    FROM con_financial_transactions
                    WHERE id IN (
                        SELECT MAX(id)
                        FROM con_financial_transactions
                        WHERE ref_type = 'contractor_bill'
                        GROUP BY ref_id
                    )
                ) latest_txn ON latest_txn.ref_id = cbr.id
                LEFT JOIN users pb ON pb.id = latest_txn.processed_by
                LEFT JOIN phase_weekly_progress pwp ON pwp.id = cbr.target_id
                LEFT JOIN project_phases pp ON pp.id = pwp.phase_id
                LEFT JOIN phase_monthly_targets pmt ON pmt.id = pwp.target_id
                WHERE {$whereClause}
                ORDER BY cbr.id DESC
                {$limitClause}";

        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get count of filtered bills
     */
    public function getFilteredBillsCount(array $filters = []): int
    {
        $whereClause = $this->buildFilterWhere($filters);

        $sql = "SELECT COUNT(*) AS total_count
                FROM {$this->table} cbr
                LEFT JOIN contractors oo ON oo.id = cbr.operator_id
                LEFT JOIN projects p  ON p.id  = cbr.project_id
                LEFT JOIN users u     ON u.id  = cbr.created_by
                LEFT JOIN users v     ON v.id  = cbr.verified_by
                LEFT JOIN roles v_r   ON v_r.id = v.role_id
                LEFT JOIN users qc_u  ON qc_u.id = cbr.qc_approved_by_id
                LEFT JOIN roles qc_r  ON qc_r.id = qc_u.role_id
                LEFT JOIN users ab    ON ab.id = cbr.approved_by
                LEFT JOIN roles ab_r  ON ab_r.id = ab.role_id
                LEFT JOIN (
                    SELECT ref_id, processed_by
                    FROM con_financial_transactions
                    WHERE id IN (
                        SELECT MAX(id)
                        FROM con_financial_transactions
                        WHERE ref_type = 'contractor_bill'
                        GROUP BY ref_id
                    )
                ) latest_txn ON latest_txn.ref_id = cbr.id
                LEFT JOIN users pb ON pb.id = latest_txn.processed_by
                WHERE {$whereClause}";

        $res = $this->db->query($sql);
        $row = $res ? $res->fetch_assoc() : null;
        return (int)($row['total_count'] ?? 0);
    }

    /**
     * Get status counts for top filter pills
     */
    public function getStatusCounts($roleUserId = null, array $roleProjectIds = []): array
    {
        $where = ["1=1"];
        $roleConditions = [];
        if ($roleUserId !== null) {
            $roleConditions[] = "cbr.created_by = " . (int)$roleUserId;
        }
        if (!empty($roleProjectIds)) {
            $safeIds = implode(',', array_map('intval', (array)$roleProjectIds));
            if (!empty($safeIds)) {
                $roleConditions[] = "cbr.project_id IN ($safeIds)";
            }
        }
        if (!empty($roleConditions)) {
            $where[] = "(" . implode(' OR ', $roleConditions) . ")";
        }
        $whereClause = implode(" AND ", $where);

        $sql = "SELECT 
                    COUNT(*) as count_all,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as count_draft,
                    SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as count_submitted,
                    SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) as count_verified,
                    SUM(CASE WHEN status = 'qc_approved' THEN 1 ELSE 0 END) as count_qc_approved,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as count_approved,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as count_rejected,
                    SUM(CASE WHEN status IN ('paid', 'partially_paid', 'fully_paid') THEN 1 ELSE 0 END) as count_paid
                FROM {$this->table} cbr
                WHERE {$whereClause}";

        $res = $this->db->query($sql);
        $row = $res ? $res->fetch_assoc() : [];
        return [
            'all'         => (int)($row['count_all'] ?? 0),
            'draft'       => (int)($row['count_draft'] ?? 0),
            'submitted'   => (int)($row['count_submitted'] ?? 0),
            'verified'    => (int)($row['count_verified'] ?? 0),
            'qc_approved' => (int)($row['count_qc_approved'] ?? 0),
            'approved'    => (int)($row['count_approved'] ?? 0),
            'rejected'    => (int)($row['count_rejected'] ?? 0),
            'paid'        => (int)($row['count_paid'] ?? 0),
        ];
    }

    /**
     * Get distinct projects that have contractor bills
     */
    public function getDistinctProjects($roleUserId = null, array $roleProjectIds = []): array
    {
        $where = ["p.deleted_at IS NULL"];
        $roleConditions = [];
        if ($roleUserId !== null) {
            $roleConditions[] = "cbr.created_by = " . (int)$roleUserId;
        }
        if (!empty($roleProjectIds)) {
            $safeIds = implode(',', array_map('intval', (array)$roleProjectIds));
            if (!empty($safeIds)) {
                $roleConditions[] = "cbr.project_id IN ($safeIds)";
            }
        }
        if (!empty($roleConditions)) {
            $where[] = "(" . implode(' OR ', $roleConditions) . ")";
        }
        $whereClause = implode(" AND ", $where);

        $sql = "SELECT DISTINCT p.id, p.name 
                FROM projects p
                JOIN {$this->table} cbr ON cbr.project_id = p.id
                WHERE {$whereClause}
                ORDER BY p.name ASC";
        $res = $this->db->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get distinct creators who have submitted contractor bills
     */
    public function getDistinctCreators($roleUserId = null, array $roleProjectIds = []): array
    {
        $where = ["cbr.created_by IS NOT NULL", "u.id IS NOT NULL"];
        $roleConditions = [];
        if ($roleUserId !== null) {
            $roleConditions[] = "cbr.created_by = " . (int)$roleUserId;
        }
        if (!empty($roleProjectIds)) {
            $safeIds = implode(',', array_map('intval', (array)$roleProjectIds));
            if (!empty($safeIds)) {
                $roleConditions[] = "cbr.project_id IN ($safeIds)";
            }
        }
        if (!empty($roleConditions)) {
            $where[] = "(" . implode(' OR ', $roleConditions) . ")";
        }
        $whereClause = implode(" AND ", $where);

        $sql = "SELECT DISTINCT u.id, u.full_name 
                FROM users u
                JOIN {$this->table} cbr ON cbr.created_by = u.id
                WHERE {$whereClause}
                ORDER BY u.full_name ASC";
        $res = $this->db->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function findWithRelations($id)
    {
        $sql = "SELECT cbr.*,
                       oo.name  AS operator_name,
                       oo.phone AS operator_phone,
                       oo.trade AS operator_trade,
                       p.name   AS project_name,
                       u.full_name  AS created_by_name,
                       u.role_id    AS created_by_role_id,
                       v.full_name  AS verified_by_name,
                       v_r.name     AS verified_by_role,
                       ptl.full_name AS purchase_team_leader_by_name,
                       ph.full_name AS purchase_head_approved_by_name,
                       ph_r.name    AS purchase_head_approved_by_role,
                       qc_u.full_name AS qc_approved_by_name,
                       qc_r.name    AS qc_approved_by_role,
                       ab.full_name AS approved_by_name,
                       ab_r.name    AS approved_by_role,
                       pwp.week_number AS target_week_number,
                       pwp.week_start_date AS target_week_start_date,
                       pwp.week_end_date AS target_week_end_date,
                       pwp.overall_progress_pct AS target_overall_progress_pct,
                       pp.phase_name AS target_phase_name,
                       pmt.target_month_number,
                       pmt.target_year
                FROM {$this->table} cbr
                LEFT JOIN contractors oo ON oo.id = cbr.operator_id
                LEFT JOIN projects p  ON p.id  = cbr.project_id
                LEFT JOIN users u     ON u.id  = cbr.created_by
                LEFT JOIN users v     ON v.id  = cbr.verified_by
                LEFT JOIN roles v_r   ON v_r.id = v.role_id
                LEFT JOIN users ptl   ON ptl.id = cbr.purchase_team_leader_by_id
                LEFT JOIN users ph    ON ph.id  = cbr.purchase_head_approved_by_id
                LEFT JOIN roles ph_r  ON ph_r.id = ph.role_id
                LEFT JOIN users qc_u  ON qc_u.id = cbr.qc_approved_by_id
                LEFT JOIN roles qc_r  ON qc_r.id = qc_u.role_id
                LEFT JOIN users ab    ON ab.id = cbr.approved_by
                LEFT JOIN roles ab_r  ON ab_r.id = ab.role_id
                LEFT JOIN phase_weekly_progress pwp ON pwp.id = cbr.target_id
                LEFT JOIN project_phases pp ON pp.id = pwp.phase_id
                LEFT JOIN phase_monthly_targets pmt ON pmt.id = pwp.target_id
                WHERE cbr.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($data)
    {
        $accountId = !empty($data['account_id']) ? (int)$data['account_id'] : null;
        $sql = "INSERT INTO {$this->table}
                (project_id, operator_id, work_order_id, target_id, bill_type, bill_date, work_description, bill_amount, attachment_path, status, created_by, account_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'iiiisssdssii',
            $data['project_id'],
            $data['operator_id'],
            $data['work_order_id'],
            $data['target_id'],
            $data['bill_type'],
            $data['bill_date'],
            $data['work_description'],
            $data['bill_amount'],
            $data['attachment_path'],
            $data['status'],
            $data['created_by'],
            $accountId
        );
        $stmt->execute();
        return $stmt->insert_id;
    }

    /* update */
    public function update($id, $data)
    {
        $fields = [];
        $types  = '';
        $params = [];

        foreach ($data as $col => $val) {
            $fields[] = "`$col` = ?";
            if (is_int($val))   { $types .= 'i'; }
            elseif (is_float($val)) { $types .= 'd'; }
            else                { $types .= 's'; }
            $params[] = $val;
        }

        $types .= 'i';
        $params[] = $id;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        return $stmt->execute();
    }

    /* delete */
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    /* updateStatus — called by verify / approve / pay actions */
    public function updateStatus($id, $data)
    {
        $fields = [];
        $types  = '';
        $params = [];

        foreach ($data as $col => $val) {
            if ($col === 'id') continue;
            $fields[] = "`$col` = ?";
            if (is_int($val))   { $types .= 'i'; }
            elseif (is_float($val)) { $types .= 'd'; }
            else                { $types .= 's'; }
            $params[] = $val;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
        $types  .= 'i';
        $params[] = $id;

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        return $stmt->execute();
    }

    /** Approve with amount and approver tracking */
    public function approve($id, $userId, $approvedAmount = null, $managementNote = null)
    {
        $bill = $this->find($id);
        if (!$bill || !in_array($bill['status'], ['submitted', 'verified', 'qc_approved'])) return false;

        if ($approvedAmount === null || $approvedAmount <= 0) {
            $approvedAmount = (float)($bill['bill_amount'] ?? 0);
        }

        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET status = 'approved', approved_amount = ?, approved_by = ?, due_amount = ?, management_note = ?
             WHERE id = ?"
        );
        $stmt->bind_param('didsi', $approvedAmount, $userId, $approvedAmount, $managementNote, $id);
        return $stmt->execute();
    }

    /**
     * Review & forward by Purchase Team Leader / Sr. Executive (Role 31)
     */
    public function reviewPurchaseTeamLeader($id, $userId, $note = null)
    {
        $bill = $this->find($id);
        if (!$bill || !in_array($bill['status'], ['submitted', 'verified'])) return false;

        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET purchase_team_leader_by_id = ?, purchase_team_leader_at = NOW(), purchase_team_leader_note = ?
             WHERE id = ?"
        );
        $stmt->bind_param('isi', $userId, $note, $id);
        return $stmt->execute();
    }

    /**
     * Approve by Head of Purchase (Role 17)
     */
    public function approvePurchaseHead($id, $userId, $note = null)
    {
        $bill = $this->find($id);
        if (!$bill || !in_array($bill['status'], ['submitted', 'verified'])) return false;

        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET purchase_head_approved_by_id = ?, purchase_head_approved_at = NOW(), purchase_head_note = ?
             WHERE id = ?"
        );
        $stmt->bind_param('isi', $userId, $note, $id);
        return $stmt->execute();
    }

    /** QC Approve */
    public function updateQCStatus($id, $userId, $qcNote = null, $approvedAmount = null)
    {
        $bill = $this->find($id);
        if (!$bill || !in_array($bill['status'], ['submitted', 'verified'])) return false;

        if ($approvedAmount === null || $approvedAmount <= 0) {
            $approvedAmount = (float)($bill['bill_amount'] ?? 0);
        }

        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET status = 'qc_approved', qc_approved_by_id = ?, qc_approved_at = NOW(), qc_note = ?, approved_amount = ?, due_amount = ?
             WHERE id = ?"
        );
        $stmt->bind_param('isddi', $userId, $qcNote, $approvedAmount, $approvedAmount, $id);
        return $stmt->execute();
    }

    /** Reject with approver tracking and reason */
    public function reject($id, $userId, $reason = null)
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET status = 'rejected', approved_by = ?, rejection_reason = ?, management_note = COALESCE(?, management_note)
             WHERE id = ? AND status IN ('submitted', 'verified', 'qc_approved')"
        );
        $stmt->bind_param('issi', $userId, $reason, $reason, $id);
        return $stmt->execute();
    }

    public function getByStatus($status)
    {
        $sql = "SELECT cbr.*, oo.name AS operator_name, p.name AS project_name
                FROM {$this->table} cbr
                LEFT JOIN contractors oo ON oo.id = cbr.operator_id
                LEFT JOIN projects p ON p.id = cbr.project_id
                WHERE cbr.status = ?
                ORDER BY cbr.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $status);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getByTarget($targetId)
    {
        $sql = "SELECT cbr.*, oo.name AS operator_name, p.name AS project_name
                FROM {$this->table} cbr
                LEFT JOIN contractors oo ON oo.id = cbr.operator_id
                LEFT JOIN projects p ON p.id = cbr.project_id
                WHERE cbr.target_id = ?
                ORDER BY cbr.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $targetId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
