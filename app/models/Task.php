<?php

class Task extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        return parent::__construct($db);
    }
    public function getPaginated($limit, $offset, $search = '')
    {
        // Base SQL
        $sql = "SELECT 
                t.*, 
                e.full_name AS employee_name, 
                e.photo AS employee_photo, 
                e.id AS employee_code,
                u.full_name AS assigned_by_name,
                u.photo AS user_photo,
                u.email AS assigned_by_email
            FROM tasks AS t
            JOIN users AS e ON t.user_id = e.id
            JOIN users AS u ON t.assigned_by = u.id
            WHERE 1";

        $params = [];
        $types = "";

        // Search by title
        if (!empty($search)) {
            $sql .= " AND t.title LIKE ?";
            $params[] = "%$search%";
            $types .= "s";
        }

        // Add LIMIT & OFFSET
        $sql .= " ORDER BY t.id DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        // Prepare statement
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }

        // Bind parameters dynamically
        $stmt->bind_param($types, ...$params);

        // Execute & fetch
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
        $sql = "SELECT COUNT(*) as total FROM users WHERE 1";
        $params = [];
        $types = "";

        if ($search) {
            $sql .= "  AND (t.title LIKE ?)";
            $params[] = "%$search%";
            $types .= "s";
        }


        $stmt = $this->db->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'];
    }

    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO tasks
        (title, user_id, description, start_date, deadline, employee_accept, status, progress, priority,assigned_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?,?)"
        );
        $stmt->bind_param(
            "sisssisssi",
            $data['title'],
            $data['user_id'],
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
        $stmt = $this->db->prepare("
        SELECT 
            t.*, 

            -- Employee
            e.full_name AS employee_name,
            e.email AS employee_email,
            e.photo AS emp_photo,
            des_emp.name AS employee_designation,

            -- Assigned By
            u.full_name AS assigned_by_name,
            u.email AS assigned_by_email,
            u.photo AS assigned_photo,
            des_assign.name AS assigned_by_designation

        FROM tasks t

        JOIN users e 
            ON t.user_id = e.id

        JOIN users u 
            ON t.assigned_by = u.id

        LEFT JOIN designations des_emp 
            ON e.designation_id = des_emp.id

        LEFT JOIN designations des_assign 
            ON u.designation_id = des_assign.id

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
                    user_id = ?
                WHERE id = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param(
                "sssssisii",
                $data['title'],
                $data['description'],
                $data['start_date'],
                $data['deadline'],
                $data['priority'],
                $data['progress'],
                $data['status'],
                $newEmployeeId,
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
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
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


    public function updateProgress($taskId, $progress)
    {
        // Start transaction
        $this->db->begin_transaction();

        try {
            //  Update task progress
            $status = ($progress >= 100) ? 'completed' : 'in_progress';

            $stmt = $this->db->prepare("
            UPDATE tasks
            SET progress = ?, status = ?
            WHERE id = ?
        ");
            $stmt->bind_param("isi", $progress, $status, $taskId);
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
}
