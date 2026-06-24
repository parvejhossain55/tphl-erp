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
                cc_email, send_to_email, status
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");

        // Types: 
        // employee_id (i), designation_id (i), department_id (i), 
        // letter_type (s), template_id (i), 
        // subject (s), body (s), 
        // letter_date (s), ref_number (s), 
        // joining_date (s), effective_date (s), 
        // current_salary (s), revised_salary (s), new_designation (s), 
        // signatory_name (s), signatory_title (s), 
        // cc_email (s), send_to_email (i), status (s)
        // Total: 19 placeholders

        $template_id = !empty($data['template_id']) ? (int)$data['template_id'] : null;
        $send_to_email = !empty($data['send_to_email']) ? (int)$data['send_to_email'] : 0;

        $stmt->bind_param(
            "iiisissssssssssssis",
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
            $data['status']
        );

        return $stmt->execute();
    }

    public function getAllWithDetails($designation_id = null, $letter_type = null)
    {
        $sql = "SELECT l.id, l.employee_id, l.designation_id, l.department_id, l.letter_type, l.template_id,
                       l.subject, l.body, l.letter_date, l.ref_number, l.joining_date, l.effective_date,
                       l.current_salary, l.revised_salary, l.new_designation, l.signatory_name, l.signatory_title,
                       l.cc_email, l.send_to_email, l.status, l.created_at, l.updated_at,
                       e.full_name, e.employee_id as employee_code, d.name as designation_name, dept.name as department_name
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
                   e.full_name, e.employee_id as employee_code, e.address, d.name as designation_name, dept.name as department_name
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
                status = ?
            WHERE id = ?
        ");

        $template_id = !empty($data['template_id']) ? (int)$data['template_id'] : null;
        $send_to_email = !empty($data['send_to_email']) ? (int)$data['send_to_email'] : 0;
        $id = (int)$id;

        $stmt->bind_param(
            "iiisissssssssssssisi",
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
            $id
        );

        return $stmt->execute();
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM letters WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
