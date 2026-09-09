<?php

class Payslip extends Model
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Get all Departments with paginate

    public function getAllWithEmployee()
    {
        $stmt = $this->db->prepare("SELECT p.*,e.id AS employee_id,e.full_name AS employee_name , e.photo,e.email,e.joining_date,d.name AS desgination_name 
            FROM payslips AS p 
            LEFT JOIN employees AS e ON p.employee_id = e.id 
            LEFT JOIN designations AS d ON p.designation_id = d.id
            ORDER BY p.id DESC");

        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $data;
    }

    // Get Paid Count for specific month/year and optional project
    public function getPaidCount($year = null, $month = null, $projectId = null)
    {
        $sql = "SELECT COUNT(*) AS paid_count FROM payslips WHERE status = 'paid' AND (is_excluded = 0 OR is_excluded IS NULL)";
        $params = [];
        $types = "";

        if (!empty($projectId)) {
            $sql .= " AND payroll_project_id = ?";
            $params[] = (int) $projectId;
            $types .= "i";
        }

        if (!empty($year)) {
            $sql .= " AND YEAR(payslip_month) = ?";
            $params[] = (int) $year;
            $types .= "i";
        }

        if (!empty($month)) {
            $sql .= " AND MONTH(payslip_month) = ?";
            $params[] = (int) $month;
            $types .= "i";
        }

        $stmt = $this->db->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();

        return $data['paid_count'] ?? 0;
    }

    // Get Pending Count for specific month/year and optional project
    public function getPendingCount($year = null, $month = null, $projectId = null)
    {
        $sql = "SELECT COUNT(*) AS pending_count FROM payslips WHERE status = 'pending' AND (is_excluded = 0 OR is_excluded IS NULL)";
        $params = [];
        $types = "";

        if (!empty($projectId)) {
            $sql .= " AND payroll_project_id = ?";
            $params[] = (int) $projectId;
            $types .= "i";
        }

        if (!empty($year)) {
            $sql .= " AND YEAR(payslip_month) = ?";
            $params[] = (int) $year;
            $types .= "i";
        }

        if (!empty($month)) {
            $sql .= " AND MONTH(payslip_month) = ?";
            $params[] = (int) $month;
            $types .= "i";
        }

        $stmt = $this->db->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();

        return $data['pending_count'] ?? 0;
    }

    // Create a new role

    public function create($data)
    {
        $stmt = $this->db->prepare("
        INSERT INTO payslips
        (employee_id, payroll_project_id, designation_id, payslip_month, total_earning, total_deduction, net_salary, status, company_bank_id, payment_method, upload_by, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

        $status = $data['status'] ?? 'pending';
        $companyBankId = isset($data['company_bank_id']) ? $data['company_bank_id'] : null;
        $paymentMethod = !empty($data['payment_method']) ? $data['payment_method'] : 'bank';
        $uploadBy = $data['upload_by'] ?? 'manual';
        $payrollProjectId = $data['payroll_project_id'] ?? null;

        $stmt->bind_param(
            "iiisdddsissi",
            $data['employee_id'],
            $payrollProjectId,
            $data['designation_id'],
            $data['payslip_month'],
            $data['total_earning'],
            $data['total_deduction'],
            $data['net_salary'],
            $status,
            $companyBankId,
            $paymentMethod,
            $uploadBy,
            $data['created_by']
        );

        $stmt->execute();
        return $this->db->insert_id;
    }


    // Update role name + permissions
    public function update($data)
    {
        // Prepare update statement
        $stmt = $this->db->prepare("UPDATE payslips SET name = ?, type = ? WHERE id = ?");
        if (!$stmt) {
            echo "Prepare failed: " . $this->db->error;
            return false;
        }

        $stmt->bind_param(
            "ssi",
            $data['name'],
            $data['type'],
            $data['id']
        );

        if (!$stmt->execute()) {
            echo "Execute failed: " . $stmt->error;
            return false;
        }

        $stmt->close();
        return true;
    }

    public function delete($id)
    {
        $stmt1 = $this->db->prepare("DELETE FROM payslip_details WHERE payslip_id = ?");
        $stmt1->bind_param("i", $id);
        $stmt1->execute();
        $stmt1->close();

        $stmt2 = $this->db->prepare("DELETE FROM payslips WHERE id = ?");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $stmt2->close();

        $stmt3 = $this->db->prepare("DELETE FROM approval_requests WHERE source_table = 'payslips' AND source_id = ?");
        $stmt3->bind_param("i", $id);
        $stmt3->execute();
        $stmt3->close();

        return true;
    }

    public function payPayslip($payslipId, $paymentMethod, $companyBankId = null)
    {
        $stmt = $this->db->prepare("
        UPDATE payslips 
        SET status = 'paid', 
            payment_method = ?, 
            company_bank_id = ?, 
            updated_at = NOW() 
        WHERE id = ?
    ");
        $stmt->bind_param("sii", $paymentMethod, $companyBankId, $payslipId);
        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        return $affectedRows > 0; // returns true if updated
    }

    public function getByIdWithItems($id)
    {
        //  Get main payslip
        $stmt = $this->db->prepare("
        SELECT p.*, cb.bank_name, cb.account_name, cb.account_number,e.employee_id,e.full_name AS employee_name, e.email,e.joining_date,e.phone,e.basic_salary,
        d.name AS designation_name ,dept.name AS department_name
        FROM payslips AS p
        LEFT JOIN company_bank_accounts AS cb ON p.company_bank_id = cb.id
        LEFT JOIN employees AS e ON p.employee_id = e.id
        LEFT JOIN designations AS d ON p.designation_id = d.id
        LEFT JOIN departments AS dept ON e.department_id = dept.id
        WHERE p.id = ?
    ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $payslip = $result->fetch_assoc();
        $stmt->close();

        if (!$payslip)
            return null;

        // Ensure basic_salary is populated
        $basicSalary = (float) ($payslip['basic_salary'] ?? 0);
        $totalEarning = (float) ($payslip['total_earning'] ?? 0);
        if ($basicSalary <= 0 && $totalEarning > 0) {
            $basicSalary = round($totalEarning * 0.50, 2);
            $payslip['basic_salary'] = $basicSalary;
        }

        // 2 Get item details for this payslip
        $stmt2 = $this->db->prepare("
        SELECT pd.*, pi.name AS item_name, pi.type AS item_type
        FROM payslip_details AS pd
        LEFT JOIN payslip_items AS pi ON pd.payslip_item_id = pi.id
        WHERE pd.payslip_id = ?
    ");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $itemsResult = $stmt2->get_result();
        $items = $itemsResult->fetch_all(MYSQLI_ASSOC);
        $stmt2->close();

        // 3 If items are empty or missing standard allowances, synthesize from payslips columns & rules
        $hasEarningItems = false;
        $hasDeductionItems = false;
        foreach ($items as $it) {
            if (($it['item_type'] ?? '') === 'earning') $hasEarningItems = true;
            if (($it['item_type'] ?? '') === 'deduction') $hasDeductionItems = true;
        }

        if (!$hasEarningItems) {
            $hr = (float) ($payslip['house_rent_allowance'] ?? 0);
            $med = (float) ($payslip['medical_allowance'] ?? 0);
            $conv = (float) ($payslip['conveyance_allowance'] ?? 0);
            $rec = (float) ($payslip['recreation_allowance'] ?? 0);
            $punc = (float) ($payslip['punctuality_bonus'] ?? 0);
            $ot = (float) ($payslip['overtime'] ?? 0);
            $arr = (float) ($payslip['arrears_earning'] ?? 0);
            $duty = (float) ($payslip['duty_allowance'] ?? 0);

            $sumColAllowances = $hr + $med + $conv + $rec + $punc + $ot + $arr + $duty;

            // If columns had no allowances, use 50% basic and 50/30/10/10 of basic
            if ($sumColAllowances <= 0 && $totalEarning > 0) {
                $basicSalary = round($totalEarning * 0.50, 2);
                $hr = round($basicSalary * 0.50, 2);
                $med = round($basicSalary * 0.30, 2);
                $conv = round($basicSalary * 0.10, 2);
                $rec = round($basicSalary * 0.10, 2);
            } elseif ($sumColAllowances > 0 && $totalEarning > $sumColAllowances) {
                // Ensure basic_salary harmonizes with actual total_earning
                $basicSalary = round($totalEarning - $sumColAllowances, 2);
            }
            $payslip['basic_salary'] = $basicSalary;

            if ($hr > 0) $items[] = ['item_name' => 'House Rent Allowance', 'item_type' => 'earning', 'amount' => $hr];
            if ($med > 0) $items[] = ['item_name' => 'Medical Allowance', 'item_type' => 'earning', 'amount' => $med];
            if ($conv > 0) $items[] = ['item_name' => 'Conveyance Allowance', 'item_type' => 'earning', 'amount' => $conv];
            if ($rec > 0) $items[] = ['item_name' => 'Recreation Allowance', 'item_type' => 'earning', 'amount' => $rec];
            if ($punc > 0) $items[] = ['item_name' => 'Punctuality Bonus', 'item_type' => 'earning', 'amount' => $punc];
            if ($ot > 0) $items[] = ['item_name' => 'Overtime', 'item_type' => 'earning', 'amount' => $ot];
            if ($arr > 0) $items[] = ['item_name' => 'Arrears', 'item_type' => 'earning', 'amount' => $arr];
            if ($duty > 0) $items[] = ['item_name' => 'Duty Allowance', 'item_type' => 'earning', 'amount' => $duty];
        } else {
            // If items exist, verify basic salary
            $itemEarningsSum = 0;
            foreach ($items as $it) {
                if (($it['item_type'] ?? '') === 'earning') {
                    $itemEarningsSum += (float)($it['amount'] ?? 0);
                }
            }
            if ($totalEarning > $itemEarningsSum) {
                $payslip['basic_salary'] = round($totalEarning - $itemEarningsSum, 2);
            }
        }

        if (!$hasDeductionItems) {
            if ((float)($payslip['advance_salary'] ?? 0) > 0) {
                $items[] = ['item_name' => 'Advance Salary', 'item_type' => 'deduction', 'amount' => (float)$payslip['advance_salary']];
            }
            if ((float)($payslip['subsidized_lunch'] ?? 0) > 0) {
                $items[] = ['item_name' => 'Subsidized Lunch', 'item_type' => 'deduction', 'amount' => (float)$payslip['subsidized_lunch']];
            }
            if ((float)($payslip['unpaid_absent_amount'] ?? 0) > 0) {
                $items[] = ['item_name' => 'Unpaid Absent', 'item_type' => 'deduction', 'amount' => (float)$payslip['unpaid_absent_amount']];
            }
            if ((float)($payslip['arrears_deduction'] ?? 0) > 0) {
                $items[] = ['item_name' => 'Arrears Deduction', 'item_type' => 'deduction', 'amount' => (float)$payslip['arrears_deduction']];
            }
            if ((float)($payslip['others_deduction'] ?? 0) > 0) {
                $items[] = ['item_name' => 'Others Deduction', 'item_type' => 'deduction', 'amount' => (float)$payslip['others_deduction']];
            }
            if ((float)($payslip['tax'] ?? 0) > 0) {
                $items[] = ['item_name' => 'Tax', 'item_type' => 'deduction', 'amount' => (float)$payslip['tax']];
            }
        }

        // 4 Attach items to payslip array
        $payslip['items'] = $items;

        return $payslip;
    }


    public function getPaidSalarySum($year = null, $month = null, $projectId = null)
    {
        $query = "SELECT SUM(net_salary) AS total_paid FROM payslips WHERE status = ? AND (is_excluded = 0 OR is_excluded IS NULL)";
        $params = ['paid'];
        $types = "s";

        if (!empty($projectId)) {
            $query .= " AND payroll_project_id = ?";
            $params[] = (int) $projectId;
            $types .= "i";
        }

        if (!empty($year)) {
            $query .= " AND YEAR(payslip_month) = ?";
            $params[] = (int) $year;
            $types .= "i";
        }

        if (!empty($month)) {
            $query .= " AND MONTH(payslip_month) = ?";
            $params[] = (int) $month;
            $types .= "i";
        }

        $stmt = $this->db->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (float) ($row['total_paid'] ?? 0);
    }


    public function getUnpaidSalarySum($year = null, $month = null, $projectId = null)
    {
        $query = "SELECT SUM(net_salary) AS total_unpaid FROM payslips WHERE status IN ('pending', 'approved') AND (is_excluded = 0 OR is_excluded IS NULL)";
        $params = [];
        $types = "";

        if (!empty($projectId)) {
            $query .= " AND payroll_project_id = ?";
            $params[] = (int) $projectId;
            $types .= "i";
        }

        if (!empty($year)) {
            $query .= " AND YEAR(payslip_month) = ?";
            $params[] = (int) $year;
            $types .= "i";
        }

        if (!empty($month)) {
            $query .= " AND MONTH(payslip_month) = ?";
            $params[] = (int) $month;
            $types .= "i";
        }

        $stmt = $this->db->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (float) ($row['total_unpaid'] ?? 0);
    }




    public function getAllWithEmployeeFiltered($year = null, $month = null, $status = null, $projectId = null)
    {
        if (empty($year))
            $year = date('Y');
        if (empty($month))
            $month = date('m');

        $projectId = ($projectId && (int)$projectId > 0) ? (int)$projectId : null;

        if ($projectId) {
            // Project-Centric Query
            $sql = "
                SELECT 
                    p.id AS id,
                    e.id AS employee_id,
                    e.employee_id AS employee_office_id,
                    e.full_name AS employee_name,
                    e.photo AS photo,
                    e.email AS email,
                    e.joining_date AS joining_date,
                    d.name AS desgination_name,
                    dept.name AS department_name,
                    p.total_earning,
                    p.total_deduction,
                    COALESCE(p.net_salary, e.gross_salary) AS net_salary,
                    p.status,
                    p.payment_method,
                    p.company_bank_id,
                    p.upload_by,
                    ar.approved_at AS approval_time,
                    ar.status AS approval_status,
                    pd.amount AS lwp_amount,
                    e.gross_salary AS emp_gross_salary
                FROM payslips AS p
                LEFT JOIN employees AS e ON p.employee_id = e.id
                LEFT JOIN designations AS d ON e.designation_id = d.id
                LEFT JOIN departments AS dept ON e.department_id = dept.id
                LEFT JOIN payslip_details AS pd ON pd.payslip_id = p.id AND pd.payslip_item_id = 4
                LEFT JOIN (
                    SELECT *
                    FROM (
                        SELECT *,
                                ROW_NUMBER() OVER (PARTITION BY source_id ORDER BY approved_at DESC) AS rn
                        FROM approval_requests
                        WHERE source_table = 'payslips' AND status IN ('approved','partially_approved')
                    ) AS t
                    WHERE rn = 1
                ) AS ar ON ar.source_id = p.id
                WHERE p.payroll_project_id = ?
            ";

            $params = [$projectId];
            $types = "i";

            if (!empty($year)) {
                $sql .= " AND YEAR(p.payslip_month) = ?";
                $params[] = (int) $year;
                $types .= "i";
            }
            if (!empty($month)) {
                $sql .= " AND MONTH(p.payslip_month) = ?";
                $params[] = (int) $month;
                $types .= "i";
            }

            if ($status === 'paid') {
                $sql .= " AND p.status = 'paid' AND (p.is_excluded = 0 OR p.is_excluded IS NULL)";
            } elseif ($status === 'draft') {
                $sql .= " AND p.status = 'draft'";
            } elseif ($status === 'unpaid') {
                $sql .= " AND p.status NOT IN ('paid', 'draft') AND (p.is_excluded = 0 OR p.is_excluded IS NULL)";
            } else {
                $sql .= " AND p.status != 'draft' AND (p.is_excluded = 0 OR p.is_excluded IS NULL)";
            }

            $sql .= " ORDER BY p.id DESC";

            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                die("Prepare failed: " . $this->db->error);
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $data;
        }

        // Legacy / Month-Year Query (when no specific project is selected)
        if ($status === 'unpaid') {
            $sql = "
                SELECT 
                    p.id AS id,
                    e.id AS employee_id,
                    e.employee_id AS employee_office_id,
                    e.full_name AS employee_name,
                    e.photo AS photo,
                    e.email AS email,
                    e.joining_date AS joining_date,
                    desg.name AS desgination_name,
                    dept.name AS department_name,
                    p.total_earning,
                    p.total_deduction,
                    COALESCE(p.net_salary, e.gross_salary) AS net_salary,
                    COALESCE(p.status, 'unpaid') AS status,
                    p.payment_method,
                    p.company_bank_id,
                    p.upload_by,
                    ar.approved_at AS approval_time,
                    ar.status AS approval_status,
                    pd.amount AS lwp_amount,
                    e.gross_salary AS emp_gross_salary
                FROM employees AS e
                LEFT JOIN designations AS desg ON e.designation_id = desg.id
                LEFT JOIN departments AS dept ON e.department_id = dept.id
                LEFT JOIN payslips AS p ON p.employee_id = e.id AND YEAR(p.payslip_month) = ? AND MONTH(p.payslip_month) = ?
                LEFT JOIN payslip_details AS pd ON pd.payslip_id = p.id AND pd.payslip_item_id = 4
                LEFT JOIN (
                    SELECT *
                    FROM (
                        SELECT *,
                                ROW_NUMBER() OVER (PARTITION BY source_id ORDER BY approved_at DESC) AS rn
                        FROM approval_requests
                        WHERE source_table = 'payslips' AND status IN ('approved','partially_approved')
                    ) AS t
                    WHERE rn = 1
                ) AS ar ON ar.source_id = p.id
                WHERE ((p.id IS NOT NULL AND p.status NOT IN ('paid', 'draft')) OR (p.id IS NULL))
                  AND (p.is_excluded = 0 OR p.is_excluded IS NULL)
                ORDER BY p.id DESC, e.full_name ASC
            ";
        } else {
            $sql = "
                SELECT 
                    p.id AS id,
                    e.id AS employee_id,
                    e.employee_id AS employee_office_id,
                    e.full_name AS employee_name,
                    e.photo AS photo,
                    e.email AS email,
                    e.joining_date AS joining_date,
                    d.name AS desgination_name,
                    dept.name AS department_name,
                    p.total_earning,
                    p.total_deduction,
                    COALESCE(p.net_salary, e.gross_salary) AS net_salary,
                    p.status,
                    p.payment_method,
                    p.company_bank_id,
                    p.upload_by,
                    ar.approved_at AS approval_time,
                    ar.status AS approval_status,
                    pd.amount AS lwp_amount,
                    e.gross_salary AS emp_gross_salary
                FROM payslips AS p
                LEFT JOIN employees AS e ON p.employee_id = e.id
                LEFT JOIN designations AS d ON e.designation_id = d.id
                LEFT JOIN departments AS dept ON e.department_id = dept.id
                LEFT JOIN payslip_details AS pd ON pd.payslip_id = p.id AND pd.payslip_item_id = 4
                LEFT JOIN (
                    SELECT *
                    FROM (
                        SELECT *,
                                ROW_NUMBER() OVER (PARTITION BY source_id ORDER BY approved_at DESC) AS rn
                        FROM approval_requests
                        WHERE source_table = 'payslips' AND status IN ('approved','partially_approved')
                    ) AS t
                    WHERE rn = 1
                ) AS ar ON ar.source_id = p.id
                WHERE YEAR(p.payslip_month) = ? AND MONTH(p.payslip_month) = ?
                  AND (p.is_excluded = 0 OR p.is_excluded IS NULL)
            ";

            if ($status === 'paid') {
                $sql .= " AND p.status = 'paid'";
            } elseif ($status === 'draft') {
                $sql .= " AND p.status = 'draft'";
            } else {
                $sql .= " AND p.status != 'draft'";
            }

            $sql .= " ORDER BY p.id DESC";
        }

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }

        $stmt->bind_param("ii", $year, $month);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $data;
    }



    public function getDraftSalaryCertificates($year = null, $month = null)
    {
        $sql = "
            SELECT 
                p.id AS id,
                e.id AS employee_id,
                e.employee_id AS employee_office_id,
                e.full_name AS employee_name,
                e.photo AS photo,
                e.email AS email,
                e.joining_date AS joining_date,
                d.name AS desgination_name,
                dept.name AS department_name,
                p.total_earning,
                p.total_deduction,
                COALESCE(p.net_salary, e.gross_salary) AS net_salary,
                p.status,
                p.payment_method,
                p.company_bank_id,
                p.upload_by,
                p.payslip_month,
                p.email_sent_at,
                e.gross_salary AS emp_gross_salary
            FROM payslips AS p
            LEFT JOIN employees AS e ON p.employee_id = e.id
            LEFT JOIN designations AS d ON e.designation_id = d.id
            LEFT JOIN departments AS dept ON e.department_id = dept.id
            WHERE p.status = 'draft' AND (p.payroll_project_id IS NULL OR p.payroll_project_id = 0)
        ";
        $params = [];
        $types = "";

        if (!empty($year)) {
            $sql .= " AND YEAR(p.payslip_month) = ?";
            $params[] = (int) $year;
            $types .= "i";
        }
        if (!empty($month)) {
            $sql .= " AND MONTH(p.payslip_month) = ?";
            $params[] = (int) $month;
            $types .= "i";
        }

        $sql .= " ORDER BY p.id DESC";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $data = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $data;
    }

    public function getDueEmployeePayslip($month)
    {
        $sql = "SELECT e.id, e.full_name, e.employee_id, e.email, e.designation, e.designation_id, e.basic_salary
                FROM employees e
                LEFT JOIN payslips p 
                ON p.employee_id = e.id AND DATE_FORMAT(p.month, '%Y-%m') = ?
                WHERE p.id IS NULL";

        $stmt = $this->db->prepare($sql);
        if (!$stmt)
            die("Prepare failed: " . $this->db->error);

        $stmt->bind_param("s", $month);
        $stmt->execute();
        $result = $stmt->get_result();

        $employees = [];
        while ($row = $result->fetch_assoc()) {
            $employees[] = $row;
        }

        $stmt->close();
        return $employees;
    }
}
