<?php

class Letter extends Model
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO letters (
                employee_id, designation_id, department_id,
                letter_type, template_id,
                subject, body,
                letter_date, ref_number,
                joining_date, effective_date,
                current_salary, revised_salary, new_designation,
                signatory_name, signatory_title,
                cc_email, send_to_email, status,
                purpose, certificate_month, gross_salary, net_salary
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");

        if (!$stmt) {
            die("SQL Prepare Error (Create Letter): " . $this->db->error);
        }

        $template_id       = !empty($data['template_id'])     ? (int)$data['template_id']   : null;
        $send_to_email     = !empty($data['send_to_email'])   ? (int)$data['send_to_email'] : 0;
        $purpose           = !empty($data['purpose'])         ? $data['purpose']            : null;
        $certificate_month = !empty($data['certificate_month']) ? $data['certificate_month'] : null;
        $gross_salary      = isset($data['gross_salary'])  && $data['gross_salary']  !== '' ? (float)$data['gross_salary']  : null;
        $net_salary        = isset($data['net_salary'])    && $data['net_salary']    !== '' ? (float)$data['net_salary']    : null;

        $stmt->bind_param(
            "iiisissssssssssssisssdd",
            $data['employee_id'],
            $data['designation_id'],
            $data['department_id'],
            $data['letter_type'],
            $template_id,
            $data['subject'],
            $data['body'],
            $data['letter_date'],
            $data['ref_number'],
            $data['joining_date'],
            $data['effective_date'],
            $data['current_salary'],
            $data['revised_salary'],
            $data['new_designation'],
            $data['signatory_name'],
            $data['signatory_title'],
            $data['cc_email'],
            $send_to_email,
            $data['status'],
            $purpose,
            $certificate_month,
            $gross_salary,
            $net_salary
        );

        if (!$stmt->execute()) {
            die("SQL Execute Error (Create Letter): " . $stmt->error);
        }

        return $this->db->insert_id;
    }

    public function getAllWithDetails($designation_id = null, $letter_type = null)
    {
        $sql = "SELECT l.id, l.employee_id, l.designation_id, l.department_id, l.letter_type, l.template_id,
                       l.subject, l.body, l.letter_date, l.ref_number, l.joining_date, l.effective_date,
                       l.current_salary, l.revised_salary, l.new_designation, l.signatory_name, l.signatory_title,
                       l.cc_email, l.send_to_email, l.status, l.created_at, l.updated_at,
                       l.purpose, l.certificate_month, l.gross_salary, l.net_salary,
                       e.full_name, e.employee_id as employee_code,
                       d.name as designation_name, dept.name as department_name
                FROM letters l
                LEFT JOIN employees e ON l.employee_id = e.id
                LEFT JOIN designations d ON l.designation_id = d.id
                LEFT JOIN departments dept ON l.department_id = dept.id
                WHERE 1=1";

        if ($designation_id) {
            $sql .= " AND l.designation_id = " . (int)$designation_id;
        }
        if ($letter_type) {
            $sql .= " AND l.letter_type = '" . $this->db->real_escape_string($letter_type) . "'";
        }

        $sql .= " ORDER BY l.id DESC";

        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function find($id)
    {
        $stmt = $this->db->prepare("
            SELECT l.id, l.employee_id, l.designation_id, l.department_id, l.letter_type, l.template_id,
                   l.subject, l.body, l.letter_date, l.ref_number, l.joining_date, l.effective_date,
                   l.current_salary, l.revised_salary, l.new_designation, l.signatory_name, l.signatory_title,
                   l.cc_email, l.send_to_email, l.status, l.created_at, l.updated_at,
                   l.purpose, l.certificate_month, l.gross_salary, l.net_salary,
                   e.full_name, e.employee_id as employee_code, e.address, e.employee_type,
                   e.joining_date as emp_joining_date,
                   d.name as designation_name, dept.name as department_name
            FROM letters l
            LEFT JOIN employees e ON l.employee_id = e.id
            LEFT JOIN designations d ON l.designation_id = d.id
            LEFT JOIN departments dept ON l.department_id = dept.id
            WHERE l.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function update($id, $data)
    {
        $stmt = $this->db->prepare("
            UPDATE letters SET
                employee_id = ?,
                designation_id = ?,
                department_id = ?,
                letter_type = ?,
                template_id = ?,
                subject = ?,
                body = ?,
                letter_date = ?,
                ref_number = ?,
                joining_date = ?,
                effective_date = ?,
                current_salary = ?,
                revised_salary = ?,
                new_designation = ?,
                signatory_name = ?,
                signatory_title = ?,
                cc_email = ?,
                send_to_email = ?,
                status = ?,
                purpose = ?,
                certificate_month = ?,
                gross_salary = ?,
                net_salary = ?
            WHERE id = ?
        ");

        if (!$stmt) {
            die("SQL Prepare Error (Update Letter): " . $this->db->error);
        }

        $template_id       = !empty($data['template_id'])       ? (int)$data['template_id']   : null;
        $send_to_email     = !empty($data['send_to_email'])     ? (int)$data['send_to_email'] : 0;
        $purpose           = !empty($data['purpose'])           ? $data['purpose']            : null;
        $certificate_month = !empty($data['certificate_month']) ? $data['certificate_month']  : null;
        $gross_salary      = isset($data['gross_salary'])  && $data['gross_salary']  !== '' ? (float)$data['gross_salary']  : null;
        $net_salary        = isset($data['net_salary'])    && $data['net_salary']    !== '' ? (float)$data['net_salary']    : null;
        $id = (int)$id;

        $stmt->bind_param(
            "iiisissssssssssssisssddi",
            $data['employee_id'],
            $data['designation_id'],
            $data['department_id'],
            $data['letter_type'],
            $template_id,
            $data['subject'],
            $data['body'],
            $data['letter_date'],
            $data['ref_number'],
            $data['joining_date'],
            $data['effective_date'],
            $data['current_salary'],
            $data['revised_salary'],
            $data['new_designation'],
            $data['signatory_name'],
            $data['signatory_title'],
            $data['cc_email'],
            $send_to_email,
            $data['status'],
            $purpose,
            $certificate_month,
            $gross_salary,
            $net_salary,
            $id
        );

        if (!$stmt->execute()) {
            die("SQL Execute Error (Update Letter): " . $stmt->error);
        }

        return true;
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM letters WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // ─── Salary Items Methods ──────────────────────────────────────────────────

    /**
     * Insert multiple salary items for a letter
     */
    public function createSalaryItems(int $letterId, array $items): void
    {
        if (empty($items)) return;

        $stmt = $this->db->prepare("
            INSERT INTO letter_salary_items (letter_id, item_type, label, amount, sort_order)
            VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($items as $i => $item) {
            $type   = $item['item_type'];
            $label  = $item['label'];
            $amount = (float)($item['amount'] ?? 0);
            $order  = (int)($item['sort_order'] ?? $i);
            $stmt->bind_param("issdi", $letterId, $type, $label, $amount, $order);
            $stmt->execute();
        }
        $stmt->close();
    }

    /**
     * Get all salary items for a letter, grouped by type
     */
    public function getSalaryItems(int $letterId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM letter_salary_items
            WHERE letter_id = ?
            ORDER BY item_type DESC, sort_order ASC
        ");
        $stmt->bind_param("i", $letterId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $grouped = ['earning' => [], 'deduction' => []];
        foreach ($rows as $row) {
            $grouped[$row['item_type']][] = $row;
        }
        return $grouped;
    }

    /**
     * Delete all salary items for a letter (before update)
     */
    public function deleteSalaryItems(int $letterId): void
    {
        $stmt = $this->db->prepare("DELETE FROM letter_salary_items WHERE letter_id = ?");
        $stmt->bind_param("i", $letterId);
        $stmt->execute();
        $stmt->close();
    }
}
