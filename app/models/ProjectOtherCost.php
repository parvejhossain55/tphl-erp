<?php

class ProjectOtherCost extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        parent::__construct($db);
    }

    /* =========================
       Get All Requests
    ========================== */
    public function getAll($status = null)
    {
        $query = "
            SELECT 
                poc.*,
                p.name AS project_name,
                cb.full_name AS created_by_name,
                qc.full_name AS qc_name,
                mb.full_name AS management_name,
                pb.full_name AS payment_by_name
            FROM project_other_costs poc
            LEFT JOIN projects p ON poc.project_id = p.id
            LEFT JOIN users cb ON poc.created_by_id = cb.id
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
    
    public function getByUserId($user_id)
    {
        $sql = $this->db->prepare("
            SELECT 
                poc.*,
                p.name AS project_name,
                cb.full_name AS created_by_name,
                qc.full_name AS qc_name,
                mb.full_name AS management_name,
                pb.full_name AS payment_by_name
            FROM project_other_costs poc
            LEFT JOIN projects p ON poc.project_id = p.id
            LEFT JOIN users cb ON poc.created_by_id = cb.id
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
        $sql = $this->db->prepare("
            SELECT 
                poc.*,
                p.name AS project_name,
                cb.full_name AS created_by_name
            FROM project_other_costs poc
            LEFT JOIN projects p ON poc.project_id = p.id
            LEFT JOIN users cb ON poc.created_by_id = cb.id
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
        $sql = $this->db->prepare("
            INSERT INTO project_other_costs 
            (project_id, rcost_type, purpose, request_amount, created_by_id, status) 
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");

        $sql->bind_param(
            "issdi",
            $data['project_id'],
            $data['rcost_type'],
            $data['purpose'],
            $data['request_amount'],
            $data['created_by_id']
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
}
