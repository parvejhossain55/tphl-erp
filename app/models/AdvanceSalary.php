<?php

class AdvanceSalary extends Model
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
        $this->ensureTableExists();
    }

    private function ensureTableExists()
    {
        if (!isset($this->db))
            return;

        $sql = "CREATE TABLE IF NOT EXISTS `advance_salaries` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `employee_id` INT NOT NULL,
            `request_date` DATE NOT NULL,
            `amount` DECIMAL(10,2) NOT NULL,
            `reason` TEXT NULL,
            `status` ENUM('pending_hr', 'pending_accounts', 'paid', 'deducted', 'rejected') DEFAULT 'pending_hr',
            `approved_date` DATETIME NULL,
            `approval_request_id` INT NULL,
            `deducted_payslip_id` INT NULL,
            `created_by` INT,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $this->db->query($sql);
        @$this->db->query("ALTER TABLE advance_salaries ADD COLUMN deduction_start_month VARCHAR(7) NULL AFTER reason");
        @$this->db->query("ALTER TABLE advance_salaries ADD COLUMN deduction_end_month VARCHAR(7) NULL AFTER deduction_start_month");
        @$this->db->query("ALTER TABLE advance_salaries ADD COLUMN deduction_months INT NULL AFTER deduction_end_month");
        @$this->db->query("ALTER TABLE advance_salaries ADD COLUMN paid_back_amount DECIMAL(10,2) DEFAULT 0.00 AFTER amount");
        @$this->db->query("ALTER TABLE advance_salaries ADD COLUMN approved_date DATETIME NULL AFTER status");
        @$this->db->query("ALTER TABLE advance_salaries MODIFY COLUMN status ENUM('pending', 'pending_hr', 'pending_accounts', 'approved', 'paid', 'deducted', 'rejected') DEFAULT 'pending_hr'");
        @$this->db->query("UPDATE advance_salaries SET status = 'pending_hr' WHERE status = '' OR status = 'pending' OR status IS NULL");
    }

    public function getAll()
    {
        $sql = "SELECT a.*, e.full_name as employee_name, e.employee_id as emp_code 
                FROM advance_salaries a 
                LEFT JOIN employees e ON a.employee_id = e.id 
                ORDER BY a.id DESC";
        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            return [];
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $data;
    }

    public function getByEmployee($employee_id)
    {
        $sql = "SELECT * FROM advance_salaries WHERE employee_id = ? ORDER BY id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $data;
    }

    public function getUnadjustedPaidAdvance($employee_id)
    {
        $sql = "SELECT SUM(amount) as total_unadjusted FROM advance_salaries WHERE employee_id = ? AND status = 'paid'";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result['total_unadjusted'] ?? 0;
    }

    public function getMonthlyDeductionAmount($employee_id, $target_month)
    {
        $sql = "SELECT amount, deduction_months, deduction_start_month, deduction_end_month, paid_back_amount FROM advance_salaries WHERE employee_id = ? AND status = 'paid' AND (amount - IFNULL(paid_back_amount, 0)) > 0";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $advances = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        $total_monthly_deduction = 0;
        foreach ($advances as $adv) {
            $deduction_start = $adv['deduction_start_month'];
            $deduction_end = $adv['deduction_end_month'];
            $amount = (float) $adv['amount'];
            $paid_back = (float) ($adv['paid_back_amount'] ?? 0);
            $months = (int) $adv['deduction_months'];
            $remaining = $amount - $paid_back;

            if ($remaining <= 0)
                continue;

            if ($deduction_start && $deduction_end) {
                if ($target_month >= $deduction_start && $target_month <= $deduction_end) {
                    $installment = $months > 0 ? round($amount / $months, 2) : $remaining;
                    if ($installment > $remaining) {
                        $installment = $remaining;
                    }
                    $total_monthly_deduction += $installment;
                }
            } else {
                $total_monthly_deduction += $remaining;
            }
        }
        return $total_monthly_deduction;
    }

    public function getPaidAdvancesByEmployee($employee_id)
    {
        $sql = "SELECT id, amount, paid_back_amount FROM advance_salaries WHERE employee_id = ? AND status = 'paid' AND (amount - IFNULL(paid_back_amount, 0)) > 0 ORDER BY id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $data;
    }

    public function create($data)
    {
        $sql = "INSERT INTO advance_salaries (employee_id, request_date, amount, reason, deduction_start_month, deduction_end_month, deduction_months, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "isdsssssi",
            $data['employee_id'],
            $data['request_date'],
            $data['amount'],
            $data['reason'],
            $data['deduction_start_month'],
            $data['deduction_end_month'],
            $data['deduction_months'],
            $data['status'],
            $data['created_by']
        );
        $res = $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $res ? $id : false;
    }

    public function updateStatus($id, $status)
    {
        if ($status === 'pending_accounts') {
            $sql = "UPDATE advance_salaries SET status = ?, approved_date = NOW() WHERE id = ?";
        } else {
            $sql = "UPDATE advance_salaries SET status = ? WHERE id = ?";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $status, $id);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    public function updateApprovalRequestId($id, $approval_request_id)
    {
        $sql = "UPDATE advance_salaries SET approval_request_id = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $approval_request_id, $id);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    public function markAsDeducted($id, $payslip_id)
    {
        $sql = "UPDATE advance_salaries SET status = 'deducted', deducted_payslip_id = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $payslip_id, $id);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    public function addDeduction($id, $deducted_amount, $payslip_id)
    {
        // Add to paid_back_amount and update status if fully paid
        $sql = "UPDATE advance_salaries SET paid_back_amount = IFNULL(paid_back_amount, 0) + ?, deducted_payslip_id = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("dii", $deducted_amount, $payslip_id, $id);
        $res = $stmt->execute();
        $stmt->close();

        // Check if fully paid
        $checkSql = "UPDATE advance_salaries SET status = 'deducted' WHERE id = ? AND paid_back_amount >= amount";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $checkStmt->close();

        return $res;
    }
    public function payAdvance($id, $paid_amount, $document_path)
    {
        $status = 'paid';
        $sql = "UPDATE advance_salaries SET status = ?, paid_amount = ?, payment_document = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sdsi", $status, $paid_amount, $document_path, $id);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }
}
