<?php

class User extends Model
{


    protected $table = "users";
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Get all users with paginate
    public function getPaginated($limit, $offset, $search = '', $role = '')
    {
        $sql = "SELECT
                u.*,
                e.id AS employee_id,
                e.photo AS employee_photo,
                e.make_user,
                d.name AS designation_name,
                r.name AS role_name
            FROM users u
            LEFT JOIN employees e ON e.user_id = u.id
            LEFT JOIN designations AS d ON d.id = u.designation_id
            LEFT JOIN roles AS r ON r.id = u.role_id
            WHERE 1=1";

        $params = [];
        $types = "";

        if ($search) {
            $sql .= " AND (u.full_name LIKE ? OR u.phone LIKE ? OR u.email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $types .= "sss";
        }

        if ($role) {
            $sql .= " AND u.role_id = ?";
            $params[] = $role;
            $types .= "i";
        }

        $sql .= " ORDER BY u.id DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // for sales team Head
    public function getPaginatedWithSales($limit, $offset, $search = '', $role = '')
    {
        $sql = "SELECT
                u.*,
                e.id AS employee_id,
                e.photo AS employee_photo,
                e.make_user,
                d.name AS designation_name,
                r.name AS role_name
            FROM users u
            LEFT JOIN employees e ON e.user_id = u.id
            LEFT JOIN designations AS d ON d.id = u.designation_id
            LEFT JOIN roles AS r ON r.id = u.role_id
            WHERE r.name IN ('Sales Manager', 'Sales Executive')
            ";

        $params = [];
        $types = "";

        if ($search) {
            $sql .= " AND (u.full_name LIKE ? OR u.phone LIKE ? OR u.email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $types .= "sss";
        }

        if ($role) {
            $sql .= " AND u.role_id = ?";
            $params[] = $role;
            $types .= "i";
        }

        $sql .= " ORDER BY u.id DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    public function getPaginatedWithSalesExecutive($limit, $offset, $search = '', $role = '')
    {
        $sql = "SELECT
                u.*,
                e.id AS employee_id,
                e.photo AS employee_photo,
                e.make_user,
                d.name AS designation_name,
                r.name AS role_name
            FROM users u
            LEFT JOIN employees e ON e.user_id = u.id
            LEFT JOIN designations AS d ON d.id = u.designation_id
            LEFT JOIN roles AS r ON r.id = u.role_id
            WHERE r.name  = 'Sales Executive'
            ";

        $params = [];
        $types = "";

        if ($search) {
            $sql .= " AND (u.full_name LIKE ? OR u.phone LIKE ? OR u.email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $types .= "sss";
        }

        if ($role) {
            $sql .= " AND u.role_id = ?";
            $params[] = $role;
            $types .= "i";
        }

        $sql .= " ORDER BY u.id DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function countAllForSales($search = '', $role = '')
    {
        $sql = "SELECT COUNT(*) as total
            FROM users u
            LEFT JOIN roles r ON r.id = u.role_id
            WHERE 1";

        $params = [];
        $types = "";

        // Default: count only Sales Head + Sales Executive
        if (!$role) {
            $sql .= " AND r.name IN ('Sales Manager', 'Sales Executive')";
        }

        if ($search) {
            $sql .= " AND (u.full_name LIKE ? OR u.phone LIKE ? OR u.email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $types .= "sss";
        }

        if ($role) {
            $sql .= " AND u.role_id = ?";
            $params[] = $role;
            $types .= "i";
        }

        $stmt = $this->db->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'];
    }
    public function countAllForSalesExecutive($search = '')
    {
        $sql = "SELECT COUNT(*) as total
            FROM users u
            LEFT JOIN roles r ON r.id = u.role_id
            WHERE r.name = 'Sales Executive'";

        $params = [];
        $types = "";

        if ($search) {
            $sql .= " AND (u.full_name LIKE ? OR u.phone LIKE ? OR u.email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $types .= "sss";
        }

        $stmt = $this->db->prepare($sql);

        if ($params) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        return $result['total'];
    }

    // for sales team Head
    public function getAll()
    {


        $sql = $this->db->prepare("
        SELECT
            u.id, 
            des.name  as designation_name,
            r.name  as role_name,
           u.full_name  From users AS u
           LEFT JOIN designations des ON u.designation_id = des.id
           LEFT JOIN roles r ON u.role_id = r.id
    ");
        if (!$sql->execute()) {
            return []; // Error handle koro
        }

        $result = $sql->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    public function getAllRoleUser()
    {


        $sql = $this->db->prepare("  SELECT
                u.*,
                e.id AS employee_id,
                e.photo AS employee_photo,
                e.make_user,
                d.name AS designation_name,
                r.name AS role_name
            FROM users u
            LEFT JOIN employees e ON e.user_id = u.id
            LEFT JOIN designations AS d ON d.id = u.designation_id
            LEFT JOIN roles AS r ON r.id = u.role_id
           WHERE r.name IS NOT NULL AND r.name IN('Sales Executive','Sales Manager','Sales Team Head')

    ");
        if (!$sql->execute()) {
            return []; // Error handle koro
        }

        $result = $sql->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }


    public function create($data)
    {
        // Photo upload
        $photo = uploadImage('userphoto', 'users', 'fixed', 300, 300);

        // Ensure password is hashed
        $password = ensureHashedPassword($data['password']);

        // Assign array elements to variables (needed for bind_param)
        $full_name      = $data['full_name'] ?? '';
        $employee_id    = $data['employee_id'] ?? null;
        $phone          = $data['phone'] ?? '';
        $email          = $data['email'] ?? '';
        $designation_id = (int)($data['designation_id'] ?? 0);
        $address        = $data['address'] ?? '';
        $nid            = $data['nid'] ?? '';
        $role_id        = (int)($data['role_id'] ?? 2); // default role
        $user_name      = $data['user_name'] ?? '';
        $media_link1    = $data['media_link1'] ?? '';
        $media_link2    = $data['media_link2'] ?? '';
        $created_by     = (int)($data['created_by'] ?? 0);

        $stmt = $this->db->prepare(
            "INSERT INTO users
        (full_name, employee_id, phone, email, designation_id, address, nid, role_id, user_name, password, photo, media_link1, media_link2, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            error_log("User creation prepare failed: " . $this->db->error);
            return false;
        }

        $stmt->bind_param(
            "sissississsssi",
            $full_name,
            $employee_id,
            $phone,
            $email,
            $designation_id,
            $address,
            $nid,
            $role_id,
            $user_name,
            $password,
            $photo,
            $media_link1,
            $media_link2,
            $created_by
        );

        if ($stmt->execute()) {
            return $this->db->insert_id; // Return inserted ID
        } else {
            error_log("User creation execute failed: " . $stmt->error);
            return false;
        }
    }


    public function countAll($search = '', $role = '')
    {
        $sql = "SELECT COUNT(*) as total FROM users WHERE 1";
        $params = [];
        $types = "";

        if ($search) {
            $sql .= " AND (full_name LIKE ? OR phone LIKE ? OR email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $types .= "sss";
        }

        if ($role) {
            $sql .= " AND role_id = ?";
            $params[] = $role;
            $types .= "i";
        }

        $stmt = $this->db->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'];
    }

    public function find($id)
    {
        $stmt = $this->db->prepare("
        SELECT 
            u.*,
            e.id AS employee_id,
            e.photo AS employee_photo,
            d.name AS designation_name
        FROM users u
        LEFT JOIN employees e ON e.user_id = u.id
        LEFT JOIN designations d ON d.id = u.designation_id
        WHERE u.id = ?
        LIMIT 1
    ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function update($data, $id)
    {
        // Ensure $data is array
        if (!is_array($data)) return false;

        // Photo handling
        $photo = $data['photo'] ?? null;
        if (isset($_FILES['photo']) && !empty($_FILES['photo']['tmp_name'])) {
            $photo = uploadImage('photo', 'users', 'fixed', 300, 300);
        }

        // Password handling
        $password = !empty($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : null;

        // Assign all values to variables (needed for bind_param)
        $full_name      = $data['full_name'] ?? '';
        $employee_id    = $data['employee_id'] ?? null;
        $phone          = $data['phone'] ?? '';
        $email          = $data['email'] ?? '';
        $designation_id = (int)($data['designation_id'] ?? 0);
        $address        = $data['address'] ?? '';
        $nid            = $data['nid'] ?? '';
        $role_id        = (int)($data['role_id'] ?? 2);
        $user_name      = $data['user_name'] ?? '';
        $media_link1    = $data['media_link1'] ?? '';
        $media_link2    = $data['media_link2'] ?? '';
        $created_by     = (int)($data['created_by'] ?? 0);

        // Use COALESCE in SQL to avoid overwriting with NULL
        $stmt = $this->db->prepare(
            "UPDATE users SET
            full_name = ?,
            employee_id = ?,
            phone = ?,
            email = ?,
            designation_id = ?,
            address = ?,
            nid = ?,
            role_id = ?,
            user_name = ?,
            password = COALESCE(?, password),
            photo = COALESCE(?, photo),
            media_link1 = ?,
            media_link2 = ?,
            created_by = ?
        WHERE id = ?"
        );

        if (!$stmt) {
            throw new \Exception("User update prepare failed: " . $this->db->error);
        }

        // For bind_param, null-safe variables
        $pwd = $password ?: null;
        $ph  = $photo ?: null;

        if (!$stmt->bind_param(
            "sissississssssi",
            $full_name,
            $employee_id,
            $phone,
            $email,
            $designation_id,
            $address,
            $nid,
            $role_id,
            $user_name,
            $pwd,
            $ph,
            $media_link1,
            $media_link2,
            $created_by,
            $id
        )) {
            throw new \Exception("User update bind_param failed: " . $stmt->error);
        }

        if (!$stmt->execute()) {
            throw new \Exception("User update execute failed: " . $stmt->error);
        }

        return true;
    }


    public function getPermissionsByRole($role_id)
    {
        $stmt = $this->db->prepare(
            "SELECT p.name
         FROM permissions p
         JOIN role_permissions rp ON p.id = rp.permission_id
         WHERE rp.role_id = ?"
        );

        $stmt->bind_param("i", $role_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $permissions = [];
        while ($row = $result->fetch_assoc()) {
            $permissions[] = $row['name'];
        }
        return $permissions;
    }
    public function updateLogoutTime($userId)
    {
        $stmt = $this->db->prepare("UPDATE users SET last_logout = NOW() WHERE id = ?");
        return $stmt->execute([$userId]);
    }

    public function getEmployeeByUserId($userId)
    {
        $stmt = $this->db->prepare("SELECT photo FROM employees WHERE user_id = ? LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getByEmailCaseInsensitive($email)
    {
        $email = trim($email);

        // First try exact match
        $stmt = $this->db->prepare(
            "SELECT u.*, LOWER(r.name) AS role_name 
         FROM users u 
         LEFT JOIN roles r ON u.role_id = r.id
         WHERE u.email = ?"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        // If not found, try case-insensitive
        $stmt2 = $this->db->prepare(
            "SELECT u.*, LOWER(r.name) AS role_name 
         FROM users u 
         LEFT JOIN roles r ON u.role_id = r.id
         WHERE LOWER(u.email) = LOWER(?)"
        );

        $stmt2->bind_param("s", $email);
        $stmt2->execute();
        $result2 = $stmt2->get_result();

        if ($result2->num_rows > 0) {
            return $result2->fetch_assoc();
        }

        return null;
    }

    // Existing getByEmail method 
    public function getByEmail($email)
    {
        $stmt = $this->db->prepare(
            "SELECT u.*, LOWER(r.name) AS role_name 
         FROM users u 
         JOIN roles r ON u.role_id = r.id
         WHERE u.email = ?"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return null;
        }

        return $result->fetch_assoc();
    }

    public function delete($id)
    {

        // Optional: check if user exists
        $user = $this->find($id);
        if (!$user) {
            setError("User not found.");
            return false;
        }

        // Delete the user
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            setSuccess("User deleted successfully.");
            return true;
        } else {
            setError("Failed to delete user.");
            return false;
        }
    }

    // executive target 

    public function getAllTargets($userId)
    {
        $sql = "SELECT * FROM sales_targets 
            WHERE sales_user_id = ?
            ORDER BY month ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }


    /**
     * Insert or update target
     */
    public function saveTarget($userId, $month, $siteVisit, $bookingTarget, $bookingValue)
    {
        $sql = "
            INSERT INTO sales_targets
            (sales_user_id, month, site_visit_target, booking_target, booking_value_target)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                site_visit_target = VALUES(site_visit_target),
                booking_target = VALUES(booking_target),
                booking_value_target = VALUES(booking_value_target)
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "isiii",
            $userId,
            $month,
            $siteVisit,
            $bookingTarget,
            $bookingValue
        );
        return $stmt->execute();
    }

    public function getTargetProgress($userId, $month, $year)
    {
        // Booking
        $sql1 = "SELECT COUNT(*) AS deals_closed, 
                    SUM(agreement_value - IFNULL(discount_amount,0)) AS total_value
             FROM lead_bookings
             WHERE sales_by_id = ? AND booking_status = 'completed' 
                   AND MONTH(booking_date) = ? AND YEAR(booking_date) = ?";
        $stmt1 = $this->db->prepare($sql1);
        $stmt1->bind_param("iii", $userId, $month, $year);
        $stmt1->execute();
        $booking = $stmt1->get_result()->fetch_assoc();

        // Visits
        $sql2 = "SELECT COUNT(*) AS visits_done
             FROM lead_follow_ups
             WHERE assigned_with = ? AND follow_up_type='visit' 
                   AND status='completed' AND MONTH(follow_up_date)=? 
                   AND YEAR(follow_up_date)=?";
        $stmt2 = $this->db->prepare($sql2);
        $stmt2->bind_param("iii", $userId, $month, $year);
        $stmt2->execute();
        $visits = $stmt2->get_result()->fetch_assoc();

        return [
            'deals_closed' => $booking['deals_closed'] ?? 0,
            'total_booking_value' => $booking['total_value'] ?? 0,
            'visits_done' => $visits['visits_done'] ?? 0
        ];
    }

    // Get target for user + month
    public function getTarget($userId, $month){
        $sql = "SELECT * FROM sales_targets WHERE sales_user_id = ? AND DATE_FORMAT(month,'%Y-%m') = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("is", $userId, $month);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
 // Get completed site visits for a month
    public function getMonthlyVisitCompleted($userId, $month){
        $sql = "SELECT COUNT(*) as completed FROM lead_follow_ups
                WHERE assigned_with=? AND follow_up_type='visit' AND status='completed'
                  AND DATE_FORMAT(follow_up_date,'%Y-%m')=?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("is", $userId, $month);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['completed'] ?? 0;
    }

    // Get completed bookings for a month
  public function getMonthlyBookingCompleted($userId, $month)
{
    $sql = "
        SELECT COUNT(*) AS total
        FROM lead_bookings
        WHERE sales_by_id = ?
          AND DATE_FORMAT(booking_date, '%Y-%m') = ?
          AND booking_status IN ('completed','booked')
    ";
    $stmt = $this->db->prepare($sql);
    $stmt->bind_param("is", $userId, $month);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return (int)($result['total'] ?? 0);
}

// Total Deals Closed (all time, not month restricted)
public function getDealsClosedAllTime($userId)
{
    $sql = "
        SELECT COUNT(DISTINCT lf.lead_id) AS total
        FROM lead_follow_ups lf
        JOIN leads l ON l.id = lf.lead_id
        WHERE lf.assigned_with = ?
          AND lf.follow_up_type = 'visit'
          AND lf.status != 'canceled'
    ";
    $stmt = $this->db->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return (int)($result['total'] ?? 0);
}

// Total Deals Won (all time, not month restricted)
public function getDealsWonAllTime($userId)
{
    $sql = "
        SELECT COUNT(*) AS total
        FROM leads
        WHERE last_lead_action = 'booked'
          AND assigned_id = ?
    ";
    $stmt = $this->db->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return (int)($result['total'] ?? 0);
}

public function getAllDealsLost($userId)
{
    $sql = "
        SELECT COUNT(DISTINCT l.id) AS total
        FROM leads l
        LEFT JOIN lead_follow_ups lf ON lf.lead_id = l.id
        WHERE l.assigned_id = ?
          AND (
               l.last_lead_action = 'canceled'
               OR (lf.follow_up_type = 'visit' AND lf.status = 'canceled')
          )
    ";
    $stmt = $this->db->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return (int)($result['total'] ?? 0);
}


public function getDealsClosed($userId, $month)
{
    $sql = "
        SELECT COUNT(DISTINCT lf.lead_id) AS total
        FROM lead_follow_ups lf
        JOIN leads l ON l.id = lf.lead_id
        WHERE lf.assigned_with = ?
          AND DATE_FORMAT(lf.follow_up_date, '%Y-%m') = ?
          AND lf.follow_up_type = 'visit'
          AND lf.status != 'canceled'
    ";
    $stmt = $this->db->prepare($sql);
    $stmt->bind_param("is", $userId, $month);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return (int)($result['total'] ?? 0);
}

public function getDealsWon($userId, $month)
{
    $sql = "
        SELECT COUNT(*) AS total
        FROM leads
        WHERE last_lead_action = 'booked'
          AND assigned_id  = ?
          AND DATE_FORMAT(updated_at, '%Y-%m') = ?
    ";
    $stmt = $this->db->prepare($sql);
    $stmt->bind_param("is", $userId, $month);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return (int)($result['total'] ?? 0);
}




public function getDealsLost($userId, $month)
{
    $sql = "
        SELECT COUNT(*) AS total
        FROM leads l
        LEFT JOIN lead_follow_ups lf ON lf.lead_id = l.id
        WHERE l.assigned_id = ?
          AND DATE_FORMAT(l.updated_at, '%Y-%m') = ?
          AND (
               l.last_lead_action = 'canceled'
               OR (lf.follow_up_type = 'visit' AND lf.status = 'canceled')
          )
    ";
    $stmt = $this->db->prepare($sql);
    $stmt->bind_param("is", $userId, $month);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return (int)($result['total'] ?? 0);
}






    
}
