<?php

class Shift extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->db = $db ?: db();
    }

    /**
     * Get all shifts with count of active employees assigned
     */
    public function getAll()
    {
        $sql = "
            SELECT 
                s.*,
                COUNT(e.id) AS assigned_employees_count
            FROM shifts s
            LEFT JOIN employees e ON e.shift_id = s.id AND e.deleted_at IS NULL
            GROUP BY s.id
            ORDER BY s.id ASC
        ";

        $res = $this->db->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Find single shift by ID
     */
    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM shifts WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_assoc() : null;
    }

    /**
     * Create new shift
     */
    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO shifts (name, start_time, end_time, grace_minutes, half_day_threshold_time, min_working_hours, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $name = trim($data['name']);
        $startTime = $data['start_time'];
        $endTime = $data['end_time'];
        $graceMinutes = (int)($data['grace_minutes'] ?? 10);
        $halfDayTime = $data['half_day_threshold_time'] ?? '13:00:00';
        $minWorkingHours = (float)($data['min_working_hours'] ?? 8.00);
        $status = (int)($data['status'] ?? 1);

        $stmt->bind_param("sssisdi", $name, $startTime, $endTime, $graceMinutes, $halfDayTime, $minWorkingHours, $status);
        $success = $stmt->execute();
        $insertId = $stmt->insert_id;
        $stmt->close();

        return $success ? $insertId : false;
    }

    /**
     * Update shift
     */
    public function update($id, $data)
    {
        $stmt = $this->db->prepare("
            UPDATE shifts 
            SET name = ?, start_time = ?, end_time = ?, grace_minutes = ?, half_day_threshold_time = ?, min_working_hours = ?, status = ?
            WHERE id = ?
        ");

        $name = trim($data['name']);
        $startTime = $data['start_time'];
        $endTime = $data['end_time'];
        $graceMinutes = (int)($data['grace_minutes'] ?? 10);
        $halfDayTime = $data['half_day_threshold_time'] ?? '13:00:00';
        $minWorkingHours = (float)($data['min_working_hours'] ?? 8.00);
        $status = (int)($data['status'] ?? 1);

        $stmt->bind_param("sssisdii", $name, $startTime, $endTime, $graceMinutes, $halfDayTime, $minWorkingHours, $status, $id);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Delete shift (if no employees assigned)
     */
    public function delete($id)
    {
        // Check if employees are assigned
        $chk = $this->db->prepare("SELECT COUNT(*) AS total FROM employees WHERE shift_id = ? AND deleted_at IS NULL");
        $chk->bind_param("i", $id);
        $chk->execute();
        $cnt = $chk->get_result()->fetch_assoc()['total'] ?? 0;
        $chk->close();

        if ($cnt > 0) {
            return ['success' => false, 'message' => "Cannot delete shift: {$cnt} employees are currently assigned to this shift."];
        }

        $stmt = $this->db->prepare("DELETE FROM shifts WHERE id = ?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();

        return ['success' => $success, 'message' => $success ? 'Shift deleted successfully.' : 'Failed to delete shift.'];
    }

    /**
     * Assign shift to an employee
     */
    public function assignEmployeeShift($employeeId, $shiftId)
    {
        $stmt = $this->db->prepare("UPDATE employees SET shift_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $shiftId, $employeeId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    /**
     * Assign shift to entire department
     */
    public function assignDepartmentShift($departmentId, $shiftId)
    {
        $stmt = $this->db->prepare("UPDATE employees SET shift_id = ? WHERE department_id = ? AND deleted_at IS NULL");
        $stmt->bind_param("ii", $shiftId, $departmentId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
}
