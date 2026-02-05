<?php

class EmployeeLeave extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->db = $db;
    }

    /**
     * Get all employee leaves with join to employee & leave_type
     */
    public function getAll()
    {
        $sql = $this->db->prepare("
            SELECT el.*, e.full_name AS employee_name, lt.name AS leave_type_name,lt.total_days
            FROM employee_leaves el
            JOIN users e ON el.user_id = e.id
            JOIN leave_types lt ON el.leave_type_id = lt.id
            ORDER BY el.created_at DESC
        ");

        if (!$sql->execute()) {
            return [];
        }

        $result = $sql->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Create new employee leave
     */
public function create($data)
{
    $sql = "INSERT INTO employee_leaves 
        (user_id, leave_type_id, start_date, end_date, requested_days, approved_days, approval_status, reason)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $this->db->prepare($sql);
    $stmt->bind_param(
        "iissiiss",
        $data['user_id'],
        $data['leave_type_id'],
        $data['start_date'],
        $data['end_date'],
        $data['requested_days'],
        $data['approved_days'],
        $data['approval_status'],
        $data['reason']
    );

    $stmt->execute();
    if ($stmt->error) {
        die("Error inserting leave: " . $stmt->error);
    }
    $insertId = $stmt->insert_id;
    $stmt->close();

    return $insertId;
}

    /**
     * Find employee leave by id
     */
    public function find($id)
    {
        $stmt = $this->db->prepare("
            SELECT el.*, e.full_name AS employee_name, lt.name AS leave_type_name
            FROM employee_leaves el
            JOIN employees e ON el.user_id = e.id
            JOIN leave_types lt ON el.leave_type_id = lt.id
            WHERE el.id = ?
        ");

        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Update employee leave
     */
    public function update($id, $data)
    {
        $stmt = $this->db->prepare("
            UPDATE employee_leaves
            SET user_id = ?, leave_type_id = ?, start_date = ?, end_date = ?, requested_days = ?, 
                approved_days = ?, approval_status = ?, reason = ?, updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->bind_param(
            "iississsi",
            $data['user_id'],
            $data['leave_type_id'],
            $data['start_date'],
            $data['end_date'],
            $data['requested_days'],
            $data['approved_days'],
            $data['approval_status'],
            $data['reason'],
            $id
        );

        if (!$stmt->execute()) {
            die("LEAVE UPDATE FAILED<br>ERROR: " . $stmt->error);
        }

        return true;
    }

    /**
     * Delete employee leave
     */
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM employee_leaves WHERE id = ?");
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            die("LEAVE DELETE FAILED<br>ERROR: " . $stmt->error);
        }
        return true;
    }

    /**
     * Get remaining leave for an employee & leave type
     */
public function getRemainingLeave($user_id, $leave_type_id)
{
    // Make sure column is updated to user_id in DB
    $stmt = $this->db->prepare("
        SELECT remaining_days 
        FROM employee_leave_balances 
        WHERE user_id = ? AND leave_type_id = ?
    ");
    $stmt->bind_param("ii", $user_id, $leave_type_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result) {
        return (int) $result['remaining_days'];
    }

    // Fallback: if balance entry missing, return total_days from leave_types
    $stmt2 = $this->db->prepare("SELECT total_days FROM leave_types WHERE id = ?");
    $stmt2->bind_param("i", $leave_type_id);
    $stmt2->execute();
    $totalDays = $stmt2->get_result()->fetch_assoc()['total_days'] ?? 0;

    return (int) $totalDays;
}


public function getLeaveCount()
{
    $sql = "SELECT approval_status, COUNT(*) as total
            FROM employee_leaves
            GROUP BY approval_status";

    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $counts = [
        'pending'  => 0,
        'approved' => 0, // approved + partial
        'rejected' => 0,
    ];

    while ($row = $result->fetch_assoc()) {
        if ($row['approval_status'] === 'approved' || $row['approval_status'] === 'partial') {
            $counts['approved'] += (int) $row['total'];
        } elseif ($row['approval_status'] === 'pending') {
            $counts['pending'] = (int) $row['total'];
        } elseif ($row['approval_status'] === 'rejected') {
            $counts['rejected'] = (int) $row['total'];
        }
    }

    $stmt->close();

    return $counts;
}

public function totalLeaveCount()
{
    $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM employee_leaves");
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $stmt->close();

    return (int) $row['total'];
}



}
