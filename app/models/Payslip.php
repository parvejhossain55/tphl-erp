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

// Get Paid Count for specific month/year
public function getPaidCount($year = null, $month = null)
{
    $sql = "SELECT COUNT(*) AS paid_count FROM payslips WHERE status = 'paid'";
    $params = [];
    $types = "";

    if (!empty($year)) {
        $sql .= " AND YEAR(payslip_month) = ?";
        $params[] = $year;
        $types .= "i";
    }

    if (!empty($month)) {
        $sql .= " AND MONTH(payslip_month) = ?";
        $params[] = $month;
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

// Get Pending Count for specific month/year
public function getPendingCount($year = null, $month = null)
{
    $sql = "SELECT COUNT(*) AS pending_count FROM payslips WHERE status = 'pending'";
    $params = [];
    $types = "";

    if (!empty($year)) {
        $sql .= " AND YEAR(payslip_month) = ?";
        $params[] = $year;
        $types .= "i";
    }

    if (!empty($month)) {
        $sql .= " AND MONTH(payslip_month) = ?";
        $params[] = $month;
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
            (employee_id, designation_id, payslip_month, total_earning, total_deduction, net_salary, status, company_bank_id, payment_method, upload_by, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $status = $data['status'] ?? 'pending';
        $companyBankId = isset($data['company_bank_id']) ? $data['company_bank_id'] : null;
        $paymentMethod = isset($data['payment_method']) ? $data['payment_method'] : null;
        $uploadBy = $data['upload_by'] ?? 'manual';

        $stmt->bind_param(
            "iisdddsissi",
            $data['employee_id'],
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

        //  Delete the department
        $stmt2 = $this->db->prepare("DELETE FROM payslips WHERE id = ?");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $stmt2->close();

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

        if (!$payslip) return null;

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

        // 3 Attach items to payslip array
        $payslip['items'] = $items;

        return $payslip;
    }


    public function getPaidSalarySum($year, $month)
    {
        $query = "
        SELECT SUM(net_salary) AS total_paid
        FROM payslips
        WHERE status = ?
          AND YEAR(payslip_month) = ?
          AND MONTH(payslip_month) = ?
    ";

        $stmt = $this->db->prepare($query);
        $status = 'paid';
        $stmt->bind_param("sii", $status, $year, $month);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (float) ($row['total_paid'] ?? 0);
    }


    public function getUnpaidSalarySum($year, $month)
    {
        $query = "
        SELECT SUM(net_salary) AS total_unpaid
        FROM payslips
        WHERE status IN ('pending', 'approved')
          AND YEAR(payslip_month) = ?
          AND MONTH(payslip_month) = ?
    ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $year, $month);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (float) ($row['total_unpaid'] ?? 0);
    }




    public function getAllWithEmployeeFiltered($year = null, $month = null, $status = null)
    {
        if (empty($year)) $year = date('Y');
        if (empty($month)) $month = date('m');

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
                    ar.status AS approval_status
                FROM employees AS e
                LEFT JOIN designations AS desg ON e.designation_id = desg.id
                LEFT JOIN departments AS dept ON e.department_id = dept.id
                LEFT JOIN payslips AS p ON p.employee_id = e.id AND YEAR(p.payslip_month) = ? AND MONTH(p.payslip_month) = ?
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
                WHERE (p.id IS NOT NULL AND p.status != 'paid') OR (p.id IS NULL)
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
                    ar.status AS approval_status
                FROM payslips AS p
                LEFT JOIN employees AS e ON p.employee_id = e.id
                LEFT JOIN designations AS d ON e.designation_id = d.id
                LEFT JOIN departments AS dept ON e.department_id = dept.id
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
            ";

            if ($status === 'paid') {
                $sql .= " AND p.status = 'paid'";
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



    public function getDueEmployeePayslip($month)
    {
        $sql = "SELECT e.id, e.full_name, e.employee_id, e.email, e.designation, e.designation_id, e.basic_salary
                FROM employees e
                LEFT JOIN payslips p 
                ON p.employee_id = e.id AND DATE_FORMAT(p.month, '%Y-%m') = ?
                WHERE p.id IS NULL";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) die("Prepare failed: " . $this->db->error);

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
