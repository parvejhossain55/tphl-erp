<?php

class MealProject extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: db();
        parent::__construct($this->db);
    }

    /**
     * Get all meal projects ordered by newest first
     */
    public function getAll()
    {
        $stmt = $this->db->prepare("SELECT * FROM meal_projects ORDER BY from_date DESC, id DESC");
        if (!$stmt) {
            return [];
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $data = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $data;
    }

    /**
     * Get meal project by ID
     */
    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM meal_projects WHERE id = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $project = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $project;
    }

    /**
     * Get latest active meal project
     */
    public function getLatest()
    {
        $stmt = $this->db->prepare("SELECT * FROM meal_projects ORDER BY from_date DESC, id DESC LIMIT 1");
        if (!$stmt) {
            return null;
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $project = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $project;
    }

    /**
     * Create a new meal project
     */
    public function create($data)
    {
        $name = trim($data['name'] ?? '');
        $standardAmount = (float) ($data['standard_meal_amount'] ?? 120.00);
        $fromDate = trim($data['from_date'] ?? '');
        $toDate = trim($data['to_date'] ?? '');
        $payrollProjectId = !empty($data['payroll_project_id']) ? (int)$data['payroll_project_id'] : null;
        $createdBy = !empty($data['created_by']) ? (int)$data['created_by'] : null;

        $stmt = $this->db->prepare("
            INSERT INTO meal_projects (name, standard_meal_amount, from_date, to_date, payroll_project_id, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("sdssii", $name, $standardAmount, $fromDate, $toDate, $payrollProjectId, $createdBy);
        $result = $stmt->execute();
        $insertId = $stmt->insert_id;
        $stmt->close();

        return $result ? $insertId : false;
    }

    /**
     * Update project
     */
    public function update($id, $data)
    {
        $name = trim($data['name'] ?? '');
        $standardAmount = (float) ($data['standard_meal_amount'] ?? 120.00);
        $fromDate = trim($data['from_date'] ?? '');
        $toDate = trim($data['to_date'] ?? '');
        $status = $data['status'] ?? 'active';

        $stmt = $this->db->prepare("
            UPDATE meal_projects 
            SET name = ?, standard_meal_amount = ?, from_date = ?, to_date = ?, status = ?
            WHERE id = ?
        ");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("sdsssi", $name, $standardAmount, $fromDate, $toDate, $status, $id);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    /**
     * Update only standard meal amount for a project
     */
    public function updateStandardRate($id, $rate)
    {
        $id = (int)$id;
        $rate = (float)$rate;
        $stmt = $this->db->prepare("UPDATE meal_projects SET standard_meal_amount = ? WHERE id = ?");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("di", $rate, $id);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    /**
     * Delete project and related records
     */
    public function delete($id)
    {
        $id = (int)$id;
        $this->db->query("DELETE FROM meal_records WHERE meal_project_id = $id");
        $this->db->query("DELETE FROM meal_guest_records WHERE meal_project_id = $id");
        $stmt = $this->db->prepare("DELETE FROM meal_projects WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $res = $stmt->execute();
            $stmt->close();
            return $res;
        }
        return false;
    }
}
