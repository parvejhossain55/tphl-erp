<?php

class Task extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        return parent::__construct($db);
    }
    // public function getPaginated($limit, $offset, $search = '')
    // {
    //     // Base SQL
    //     $sql = "SELECT 
    //             t.*, 
    //             e.full_name AS employee_name, 
    //             e.photo AS employee_photo, 
    //             e.id AS employee_code,
    //             u.full_name AS assigned_by_name,
    //             u.photo AS user_photo,
    //             u.email AS assigned_by_email
    //         FROM tasks AS t
    //         JOIN users AS e ON t.user_id = e.id
    //         JOIN users AS u ON t.assigned_by = u.id
    //         WHERE 1";

    //     $params = [];
    //     $types = "";

    //     // Search by title
    //     if (!empty($search)) {
    //         $sql .= " AND t.title LIKE ?";
    //         $params[] = "%$search%";
    //         $types .= "s";
    //     }

    //     // Add LIMIT & OFFSET
    //     $sql .= " ORDER BY t.id DESC LIMIT ? OFFSET ?";
    //     $params[] = $limit;
    //     $params[] = $offset;
    //     $types .= "ii";

    //     // Prepare statement
    //     $stmt = $this->db->prepare($sql);
    //     if (!$stmt) {
    //         die("Prepare failed: " . $this->db->error);
    //     }

    //     // Bind parameters dynamically
    //     $stmt->bind_param($types, ...$params);

    //     // Execute & fetch
    //     $stmt->execute();
    //     $result = $stmt->get_result();

    //     return $result->fetch_all(MYSQLI_ASSOC);
    // }



    public function getPaginated($limit, $offset, $search = '')
    {
        // Current logged-in user
        $currentUserId = $_SESSION['user']['id'] ?? 0;

        $sql = "SELECT 
       t.*, 
    e.full_name AS employee_name, 
    e.photo AS employee_photo, 
    e.id AS employee_code,
    des.name AS user_designation,
    desig.name AS assigned_designation,
    u.full_name AS assigned_by_name,
    u.photo AS user_photo,
    u.email AS assigned_by_email
FROM tasks AS t
LEFT JOIN users AS e ON t.user_id = e.id
LEFT JOIN users AS u ON t.assigned_by = u.id
LEFT JOIN designations AS des ON e.designation_id = des.id
LEFT JOIN designations AS desig ON u.designation_id = desig.id

        WHERE t.assigned_by = ? AND t.user_id IS NOT NULL";
        // only tasks assigned to current user

        $params = [$currentUserId];
        $types = "i";

        // Search by title
        if (!empty($search)) {
            $sql .= " AND t.title LIKE ?";
            $params[] = "%$search%";
            $types .= "s";
        }

        $sql .= " ORDER BY t.id DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }


    public function getAll()
    {
        $sql = $this->db->prepare("SELECT e.full_name,e.photo,e.user_id,  desg.name AS designation_name, 
                   dept.name AS department_name  FROM users AS e
            JOIN designations AS desg ON e.designation_id = desg.id
            JOIN departments AS dept ON e.department_id = dept.id");

        $sql->execute();
        $result = $sql->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function countAll($search = '')
    {
        $currentUserId = $_SESSION['user']['id'] ?? 0;

        $sql = "SELECT COUNT(*) as total FROM tasks WHERE user_id = ?";
        $params = [$currentUserId];
        $types = "i";

        if (!empty($search)) {
            $sql .= " AND title LIKE ?";
            $params[] = "%$search%";
            $types .= "s";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'] ?? 0;
    }


    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO tasks
        (title, user_id, mentioned_user_ids, cc_user_ids, description, start_date, deadline, employee_accept, status, progress, priority, assigned_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "sisssssisssi",
            $data['title'],
            $data['user_id'],
            $data['mentioned_user_ids'],
            $data['cc_user_ids'],
            $data['description'],
            $data['start_date'],
            $data['deadline'],
            $data['employee_accept'],
            $data['status'],
            $data['progress'],
            $data['priority'],
            $data['assigned_by']
        );

        if ($stmt->execute()) {
            return true;
        } else {
            echo "ERROR: " . $stmt->error;
            return false;
        }
    }

    //employee id find for edit
    public function find($id)
    {
        $stmt = $this->db->prepare(" SELECT 
            t.*, 

            -- Employee
            e.full_name AS employee_name,
            e.email AS employee_email,
            e.photo AS emp_photo,
            des_emp.name AS employee_designation,
            COALESCE(emp_to.employee_id, CONCAT('USR-', e.id)) AS employee_code,

            -- Assigned By
            u.full_name AS assigned_by_name,
            u.email AS assigned_by_email,
            u.photo AS assigned_photo,
            des_assign.name AS assigned_by_designation,
            dep_assign.name AS assigned_by_department,
            COALESCE(emp_by.employee_id, CONCAT('USR-', u.id)) AS assigned_by_emp_code

        FROM tasks t
        LEFT JOIN users e 
            ON t.user_id = e.id
        LEFT JOIN employees emp_to
            ON e.employee_id = emp_to.id

        LEFT JOIN users u 
            ON t.assigned_by = u.id
        LEFT JOIN employees emp_by
            ON u.employee_id = emp_by.id

        LEFT JOIN designations des_emp 
            ON e.designation_id = des_emp.id
        LEFT JOIN designations des_assign 
            ON u.designation_id = des_assign.id
        LEFT JOIN departments dep_assign
            ON des_assign.department_id = dep_assign.id

        WHERE t.id = ?
    ");

        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }


    /**
     * Update employee
     */
    public function update($id, $data)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $oldTask = $this->find($id);
        if (!$oldTask) return false;

        $oldEmployeeId = (int)$oldTask['user_id'];
        $newEmployeeId = (int)$data['user_id'];

        //  Start transaction
        $this->db->begin_transaction();

        try {
            //  Update task
            $sql = "UPDATE tasks SET
                    title = ?, 
                    description = ?, 
                    start_date = ?, 
                    deadline = ?, 
                    priority = ?, 
                    progress = ?, 
                    status = ?,
                    user_id = ?,
                    mentioned_user_ids = ?,
                    cc_user_ids = ?
                WHERE id = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param(
                "sssssissssi",
                $data['title'],
                $data['description'],
                $data['start_date'],
                $data['deadline'],
                $data['priority'],
                $data['progress'],
                $data['status'],
                $newEmployeeId,
                $data['mentioned_user_ids'],
                $data['cc_user_ids'],
                $id
            );

            if (!$stmt->execute()) {
                throw new Exception("Task update failed");
            }

            //  UPDATE notification if employee changed
            if ($oldEmployeeId !== $newEmployeeId) {

                $message = "Task reassigned: " . $data['title'];

                $stmt = $this->db->prepare("
                UPDATE notifications
                SET user_id = ?, message = ?, is_read = 0
                WHERE task_id = ?
            ");

                $stmt->bind_param(
                    "isi",
                    $newEmployeeId,
                    $message,
                    $id
                );

                if (!$stmt->execute()) {
                    throw new Exception("Notification update failed");
                }
            }

            // 5 Commit
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }




    public function delete($id)
    {
        return $this->deleteFromEverywhere($id);
    }

    public function deleteFromEverywhere($id)
    {
        $id = (int)$id;
        $task = $this->find($id);
        if (!$task) {
            return false;
        }

        // 1. Delete associated notifications
        $stmtNotif = $this->db->prepare("DELETE FROM notifications WHERE task_id = ?");
        if ($stmtNotif) {
            $stmtNotif->bind_param("i", $id);
            $stmtNotif->execute();
            $stmtNotif->close();
        }

        // 2. Delete attachment image from disk if exists
        if (!empty($task['attachment_image'])) {
            $filePath = __DIR__ . '/../../public/uploads/tasks/' . $task['attachment_image'];
            if (file_exists($filePath) && is_file($filePath)) {
                @unlink($filePath);
            }
        }

        // 3. Delete task record from tasks table
        $stmt = $this->db->prepare("DELETE FROM tasks WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $res = $stmt->execute();
            $stmt->close();
            return $res;
        }

        return false;
    }


    public function getTaskCount($condition)
    {
        $sql = "SELECT COUNT(*) AS total FROM tasks WHERE $condition";
        $result = $this->db->query($sql)->fetch_assoc();
        return $result['total'] ?? 0;
    }

    public function acceptTask($taskId)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->db->begin_transaction();

        try {
            //  Update task status
            $stmt = $this->db->prepare("
            UPDATE tasks 
            SET employee_accept = 1, status = 'in_progress'
            WHERE id = ?
        ");
            $stmt->bind_param("i", $taskId);
            $stmt->execute();

            //  Mark related notification as read
            $stmt = $this->db->prepare("
            UPDATE notifications 
            SET is_read = 1
            WHERE task_id = ?
        ");
            $stmt->bind_param("i", $taskId);
            $stmt->execute();

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }


    public function updateProgress($taskId, $progress, $remarks = null, $image = null)
    {
        // Start transaction
        $this->db->begin_transaction();

        try {
            //  Update task progress
            $status = ($progress >= 100) ? 'completed' : 'in_progress';

            $sql = "UPDATE tasks SET progress = ?, status = ?";
            $types = "is";
            $params = [$progress, $status];

            if ($remarks !== null) {
                $sql .= ", remarks = ?";
                $types .= "s";
                $params[] = $remarks;
            }
            if ($image !== null) {
                $sql .= ", attachment_image = ?";
                $types .= "s";
                $params[] = $image;
            }

            $sql .= " WHERE id = ?";
            $types .= "i";
            $params[] = $taskId;

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    public function taskNotification()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $loggedUserId = $_SESSION['user']['id'] ?? null;
        if (!$loggedUserId) return [];

        //  Get employee ID
        $stmt = $this->db->prepare("SELECT id FROM employees WHERE user_id = ? LIMIT 1");
        $stmt->bind_param("i", $loggedUserId);
        $stmt->execute();
        $emp = $stmt->get_result()->fetch_assoc();
        if (!$emp) return [];

        $employeeId = (int) $emp['id'];

        //  Fetch notifications ONLY for incomplete tasks
        $stmt = $this->db->prepare("
        SELECT n.*, u.full_name AS assigned_by_name, t.progress, t.status
        FROM notifications n
        LEFT JOIN tasks t ON n.task_id = t.id
        LEFT JOIN users u ON n.assigned_by = u.id
        WHERE n.user_id = ?
          AND n.is_read = 0
          AND (t.status IS NULL OR t.status != 'completed')
        ORDER BY n.created_at DESC
    ");
        $stmt->bind_param("i", $employeeId);
        $stmt->execute();
        $notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return $notifications;
    }
/**
 * Get all pending tasks for a user (regardless of deadline)
 */
public function getPendingTaskCountByUser(int $userId): int
{
    $userId = (int) $userId;

    $sql = "
        SELECT COUNT(*) AS total
        FROM tasks
        WHERE user_id = ?
          AND progress < 100
          AND status != 'completed'
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return (int) ($row['total'] ?? 0);
    }

    return 0;
}



/**
 * Get count of tasks due today or next 3 days for dashboard card
 */
public function getTodayPendingTaskCountByUser(int $userId): int
{
    $userId = (int) $userId;

    $sql = "
        SELECT COUNT(*) AS total
        FROM tasks
        WHERE user_id = {$userId}
          AND progress < 100
          AND status != 'completed'
          AND (deadline IS NULL OR DATE(deadline) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY))
    ";

    $row = $this->db->query($sql)->fetch_assoc();
    return (int) ($row['total'] ?? 0);
}


}
