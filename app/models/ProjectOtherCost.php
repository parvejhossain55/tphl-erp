<?php

class ProjectOtherCost extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->ensureSchema();
    }

    private function ensureSchema()
    {
        static $checked = false;
        if ($checked || !$this->db) return;
        $checked = true;

        @$this->db->query("
            CREATE TABLE IF NOT EXISTS `project_other_costs` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `project_id` INT(11) NULL DEFAULT NULL,
                `rcost_type` VARCHAR(100) NULL DEFAULT NULL,
                `purpose` TEXT NULL DEFAULT NULL,
                `request_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `approved_amount` DECIMAL(15,2) NULL DEFAULT NULL,
                `payment_amount` DECIMAL(15,2) NULL DEFAULT NULL,
                `created_by_id` INT(11) NOT NULL,
                `attachment` VARCHAR(255) NULL DEFAULT NULL,
                `creator_attachment` VARCHAR(255) NULL DEFAULT NULL,
                `dept_head_status` ENUM('pending','approved','rejected','not_required') DEFAULT 'not_required',
                `dept_head_approved_by` INT(11) NULL DEFAULT NULL,
                `dept_head_approved_at` DATETIME NULL DEFAULT NULL,
                `dept_head_approval_note` TEXT NULL DEFAULT NULL,
                `qc_audit_approved_id` INT(11) NULL DEFAULT NULL,
                `qc_audit_approved_at` DATETIME NULL DEFAULT NULL,
                `qc_audit_note` TEXT NULL DEFAULT NULL,
                `management_apporved_by_id` INT(11) NULL DEFAULT NULL,
                `management_apporved_at` DATETIME NULL DEFAULT NULL,
                `management_apporved_note` TEXT NULL DEFAULT NULL,
                `pc_approved_id` INT(11) NULL DEFAULT NULL,
                `payment_by_id` INT(11) NULL DEFAULT NULL,
                `payment_at` DATETIME NULL DEFAULT NULL,
                `account_id` INT(11) NULL DEFAULT NULL,
                `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
                `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $cols = [
            'dept_head_status' => "ENUM('pending','approved','rejected','not_required') DEFAULT 'not_required'",
            'dept_head_approved_by' => "INT(11) NULL DEFAULT NULL",
            'dept_head_approved_at' => "DATETIME NULL DEFAULT NULL",
            'dept_head_approval_note' => "TEXT NULL DEFAULT NULL",
            'qc_audit_approved_at' => "DATETIME NULL DEFAULT NULL",
            'op_head_status' => "VARCHAR(30) NULL DEFAULT 'pending'",
            'op_head_by' => "INT(11) NULL DEFAULT NULL",
            'op_head_at' => "DATETIME NULL DEFAULT NULL",
            'op_head_note' => "TEXT NULL DEFAULT NULL",
            'accounts_head_status' => "VARCHAR(30) NULL DEFAULT 'pending'",
            'accounts_head_by' => "INT(11) NULL DEFAULT NULL",
            'accounts_head_at' => "DATETIME NULL DEFAULT NULL",
            'accounts_head_note' => "TEXT NULL DEFAULT NULL",
            'management_apporved_at' => "DATETIME NULL DEFAULT NULL",
            'pc_approved_id' => "INT(11) NULL DEFAULT NULL",
            'account_id' => "INT(11) NULL DEFAULT NULL",
            // Purchase Head approval stage (after Dept Head, before QC). Existing rows keep 'not_required'.
            'purchase_head_status' => "ENUM('not_required','pending','approved','rejected') NOT NULL DEFAULT 'not_required'",
            'purchase_head_by' => "INT(11) NULL DEFAULT NULL",
            'purchase_head_at' => "DATETIME NULL DEFAULT NULL",
            'purchase_head_note' => "TEXT NULL DEFAULT NULL"
        ];
        foreach ($cols as $col => $def) {
            $chk = @$this->db->query("SHOW COLUMNS FROM `project_other_costs` LIKE '{$col}'");
            if ($chk && $chk->num_rows === 0) {
                @$this->db->query("ALTER TABLE `project_other_costs` ADD COLUMN `{$col}` {$def}");
            }
        }

        $chkEa = @$this->db->query("SHOW COLUMNS FROM `expense_adjustments` LIKE 'parent_type'");
        if ($chkEa && $chkEa->num_rows === 0) {
            @$this->db->query("ALTER TABLE `expense_adjustments` ADD COLUMN `parent_type` VARCHAR(50) NOT NULL DEFAULT 'cashout'");
        }
    }

    /* =========================
       Sync Pending Dept Head Status
    ========================== */
    public function syncPendingDeptHeadStatus()
    {
        // Automatically sync pending requests when employee reporting_user_id or is_dept_head is assigned or changed
        $this->db->query("
            UPDATE project_other_costs poc
            JOIN employees e ON e.user_id = poc.created_by_id AND e.deleted_at IS NULL
            SET poc.dept_head_status = 'pending'
            WHERE poc.status = 'pending'
              AND poc.dept_head_approved_by IS NULL
              AND e.is_dept_head = 0
              AND e.reporting_user_id IS NOT NULL
              AND poc.dept_head_status != 'pending'
        ");

        $this->db->query("
            UPDATE project_other_costs poc
            JOIN employees e ON e.user_id = poc.created_by_id AND e.deleted_at IS NULL
            SET poc.dept_head_status = 'not_required'
            WHERE poc.status = 'pending'
              AND poc.dept_head_approved_by IS NULL
              AND (e.is_dept_head = 1 OR e.reporting_user_id IS NULL)
              AND poc.dept_head_status != 'not_required'
        ");
    }

    /* =========================
       Get All Requests
    ========================== */
    public function getAll($status = null)
    {
        $this->syncPendingDeptHeadStatus();

        $query = "
            SELECT 
                poc.*,
                p.name AS project_name,
                cb.full_name AS created_by_name,
                dh.full_name AS dh_name,
                qc.full_name AS qc_name,
                mb.full_name AS management_name,
                pb.full_name AS payment_by_name,
                creator_emp.is_dept_head AS creator_is_dept_head,
                creator_emp.reporting_user_id AS creator_reporting_user_id,
                boss_user.full_name AS designated_dh_name,
                boss_des.name AS designated_dh_designation
            FROM project_other_costs poc
            LEFT JOIN projects p ON poc.project_id = p.id
            LEFT JOIN users cb ON poc.created_by_id = cb.id
            LEFT JOIN employees creator_emp ON creator_emp.user_id = poc.created_by_id AND creator_emp.deleted_at IS NULL
            LEFT JOIN users boss_user ON boss_user.id = creator_emp.reporting_user_id
            LEFT JOIN employees boss_emp ON boss_emp.user_id = boss_user.id AND boss_emp.deleted_at IS NULL
            LEFT JOIN designations boss_des ON boss_des.id = boss_emp.designation_id
            LEFT JOIN users dh ON poc.dept_head_approved_by = dh.id
            LEFT JOIN users qc ON poc.qc_audit_approved_id = qc.id
            LEFT JOIN users mb ON poc.management_apporved_by_id = mb.id
            LEFT JOIN users pb ON poc.payment_by_id = pb.id
        ";

        if ($status) {
            $query .= " WHERE poc.status = ?";
        }
        
        $query .= " ORDER BY poc.id DESC";

        $sql = $this->db->prepare($query);

        if ($status) {
            $sql->bind_param("s", $status);
        }

        if (!$sql->execute()) {
            return [];
        }

        $result = $sql->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /* =========================
       Get Requests for Reporting Boss
    ========================== */
    public function getByReportingBossId($bossUserId)
    {
        $this->syncPendingDeptHeadStatus();

        $query = "
            SELECT 
                poc.*,
                p.name AS project_name,
                cb.full_name AS created_by_name,
                dh.full_name AS dh_name,
                qc.full_name AS qc_name,
                mb.full_name AS management_name,
                pb.full_name AS payment_by_name,
                creator_emp.is_dept_head AS creator_is_dept_head,
                creator_emp.reporting_user_id AS creator_reporting_user_id,
                boss_user.full_name AS designated_dh_name,
                boss_des.name AS designated_dh_designation
            FROM project_other_costs poc
            LEFT JOIN projects p ON poc.project_id = p.id
            LEFT JOIN users cb ON poc.created_by_id = cb.id
            LEFT JOIN employees creator_emp ON creator_emp.user_id = poc.created_by_id AND creator_emp.deleted_at IS NULL
            LEFT JOIN users boss_user ON boss_user.id = creator_emp.reporting_user_id
            LEFT JOIN employees boss_emp ON boss_emp.user_id = boss_user.id AND boss_emp.deleted_at IS NULL
            LEFT JOIN designations boss_des ON boss_des.id = boss_emp.designation_id
            LEFT JOIN users dh ON poc.dept_head_approved_by = dh.id
            LEFT JOIN users qc ON poc.qc_audit_approved_id = qc.id
            LEFT JOIN users mb ON poc.management_apporved_by_id = mb.id
            LEFT JOIN users pb ON poc.payment_by_id = pb.id
            WHERE (creator_emp.reporting_user_id = ? OR poc.dept_head_approved_by = ?)
            ORDER BY poc.id DESC
        ";

        $sql = $this->db->prepare($query);
        $sql->bind_param("ii", $bossUserId, $bossUserId);

        if (!$sql->execute()) {
            return [];
        }

        $result = $sql->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    public function getByUserId($user_id)
    {
        $this->syncPendingDeptHeadStatus();

        $sql = $this->db->prepare("
            SELECT 
                poc.*,
                p.name AS project_name,
                cb.full_name AS created_by_name,
                dh.full_name AS dh_name,
                qc.full_name AS qc_name,
                mb.full_name AS management_name,
                pb.full_name AS payment_by_name,
                creator_emp.is_dept_head AS creator_is_dept_head,
                creator_emp.reporting_user_id AS creator_reporting_user_id,
                boss_user.full_name AS designated_dh_name,
                boss_des.name AS designated_dh_designation
            FROM project_other_costs poc
            LEFT JOIN projects p ON poc.project_id = p.id
            LEFT JOIN users cb ON poc.created_by_id = cb.id
            LEFT JOIN employees creator_emp ON creator_emp.user_id = poc.created_by_id AND creator_emp.deleted_at IS NULL
            LEFT JOIN users boss_user ON boss_user.id = creator_emp.reporting_user_id
            LEFT JOIN employees boss_emp ON boss_emp.user_id = boss_user.id AND boss_emp.deleted_at IS NULL
            LEFT JOIN designations boss_des ON boss_des.id = boss_emp.designation_id
            LEFT JOIN users dh ON poc.dept_head_approved_by = dh.id
            LEFT JOIN users qc ON poc.qc_audit_approved_id = qc.id
            LEFT JOIN users mb ON poc.management_apporved_by_id = mb.id
            LEFT JOIN users pb ON poc.payment_by_id = pb.id
            WHERE poc.created_by_id = ?
            ORDER BY poc.id DESC
        ");
        $sql->bind_param("i", $user_id);
        $sql->execute();
        $result = $sql->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /* =========================
       Get Request by ID
    ========================== */
    public function getById($id)
    {
        $this->syncPendingDeptHeadStatus();

        $sql = $this->db->prepare("
            SELECT 
                poc.*,
                p.name AS project_name,
                cb.full_name AS creator_name,
                dh.full_name AS dh_name,
                qc.full_name AS qc_name,
                mb.full_name AS mgmt_name,
                pb.full_name AS pay_name,
                phb.full_name AS ph_name,
                creator_emp.is_dept_head AS creator_is_dept_head,
                creator_emp.reporting_user_id AS creator_reporting_user_id,
                boss_user.full_name AS designated_dh_name,
                boss_des.name AS designated_dh_designation
            FROM project_other_costs poc
            LEFT JOIN projects p ON poc.project_id = p.id
            LEFT JOIN users cb ON poc.created_by_id = cb.id
            LEFT JOIN employees creator_emp ON creator_emp.user_id = poc.created_by_id AND creator_emp.deleted_at IS NULL
            LEFT JOIN users boss_user ON boss_user.id = creator_emp.reporting_user_id
            LEFT JOIN employees boss_emp ON boss_emp.user_id = boss_user.id AND boss_emp.deleted_at IS NULL
            LEFT JOIN designations boss_des ON boss_des.id = boss_emp.designation_id
            LEFT JOIN users dh ON poc.dept_head_approved_by = dh.id
            LEFT JOIN users qc ON poc.qc_audit_approved_id = qc.id
            LEFT JOIN users mb ON poc.management_apporved_by_id = mb.id
            LEFT JOIN users pb ON poc.payment_by_id = pb.id
            LEFT JOIN users phb ON poc.purchase_head_by = phb.id
            WHERE poc.id = ?
        ");
        $sql->bind_param("i", $id);
        $sql->execute();
        $result = $sql->get_result();
        return $result ? $result->fetch_assoc() : null;
    }

    /* =========================
       Create Request
    ========================== */
    public function create($data)
    {
        $attachment = $data['attachment'] ?? null;
        $creator_attachment = $data['creator_attachment'] ?? $attachment;
        $dept_head_status = $data['dept_head_status'] ?? 'pending';

        $sql = $this->db->prepare("
            INSERT INTO project_other_costs 
            (project_id, rcost_type, purpose, request_amount, created_by_id, attachment, creator_attachment, dept_head_status, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");

        $sql->bind_param(
            "issdisss",
            $data['project_id'],
            $data['rcost_type'],
            $data['purpose'],
            $data['request_amount'],
            $data['created_by_id'],
            $attachment,
            $creator_attachment,
            $dept_head_status
        );

        return $sql->execute() ? $this->db->insert_id : false;
    }

    /* =========================
       Update Status / Approvals
    ========================== */
    public function update($id, $data)
    {
        $fields = [];
        $types = "";
        $params = [];

        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            if (is_int($value)) {
                $types .= "i";
            } elseif (is_float($value) || is_double($value)) {
                $types .= "d";
            } else {
                $types .= "s";
            }
            $params[] = $value;
        }

        $types .= "i";
        $params[] = $id;

        $query = "UPDATE project_other_costs SET " . implode(", ", $fields) . " WHERE id = ?";
        $sql = $this->db->prepare($query);
        if (!$sql) {
            return false;
        }

        $sql->bind_param($types, ...$params);

        return $sql->execute();
    }

    /* =========================
       Delete Request
    ========================== */
    public function delete($id)
    {
        $sql = $this->db->prepare("DELETE FROM project_other_costs WHERE id = ?");
        $sql->bind_param("i", $id);
        return $sql->execute();
    }

    /* =========================
       Cost Types Definition & Helper
    ========================== */
    public static function getCostTypes(): array
    {
        return [
            'electricity_bill'                         => 'Electricity Bill',
            'entertainment_bill'                       => 'Entertainment Bill',
            'internet_bill'                            => 'Internet Bill',
            'conveyance_bill'                          => 'Conveyance Bill',
            'labour_bill'                              => 'Labour Bill',
            'rajuk_land_permit_cost'                   => 'Rajuk Land Permit Cost',
            'rajuk_plan_approval_cost'                 => 'Rajuk Plan Approval Cost',
            'rajuk_consultancy_fees_for_land_permit'   => 'Rajuk Consultancy Fees For Land Permit',
            'rajuk_consultancy_fees_for_plan_approval' => 'Rajuk Consultancy Fees for Plan Approval',
            'bashundhara_drainage_charge'              => 'Bashundhara Drainage Charge',
            'bashundhara_boundary_wall_charge'         => 'Bashundhara Boundary wall Charge',
            'bashundhara_file_transfer_fee'            => 'Bashundhara File Transfer Fee',
            'bashundhara_consultancy_fee'              => 'Bashundhara Consultancy Fee',
            'mutation_cost'                            => 'Mutation Cost',
            'soil_test_cost'                           => 'Soil Test Cost',
            'digital_survey'                           => 'Digital Survey',
            'electric_meter_cost'                      => 'Electric Meter Cost',
            'electric_meter_installation'              => 'Electric Meter Installation',
            'fencing_wall_cost'                        => 'Fencing Wall Cost',
            'leed_certification_consultation_fee'      => 'Leed Certification Consultation Fee',
            'leed_certification_usgbc_cost'            => 'Leed Certification U.SGBC Cost',
            'others'                                   => 'Others',
        ];
    }

    public static function getCostTypeLabel(?string $type): string
    {
        if (empty($type)) {
            return '';
        }
        $types = self::getCostTypes();
        if (isset($types[$type])) {
            return $types[$type];
        }
        return ucwords(str_replace('_', ' ', $type));
    }
}

