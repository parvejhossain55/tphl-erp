<?php

class User extends Model
{


    protected $table = "users";
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }
    private function baseUserQuery()
    {
        return "FROM users u
        LEFT JOIN employees e ON e.user_id = u.id
        LEFT JOIN designations d ON d.id = u.designation_id
        LEFT JOIN departments dep ON d.department_id = dep.id
        LEFT JOIN teams t ON t.id = u.team_id
        LEFT JOIN roles r ON r.id = u.role_id";
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
                dep.name AS department_name,
                r.name AS role_name,
                t.name AS team_name
            " . $this->baseUserQuery() . "
            WHERE 1=1";

        $params = [];
        $types = "";

        if ($search) {
            $sql .= " AND (u.full_name LIKE ? OR u.phone LIKE ? OR u.email LIKE ?)";
            $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
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

    public function getPaginatedWithSales($limit, $offset, $search = '', $role = '', $teamId = null)
    {
        $sql = "SELECT
                u.*,
                e.id AS employee_id,
                e.photo AS employee_photo,
                e.make_user,
                d.name AS designation_name,
                r.name AS role_name,
                t.name AS team_name,
                (SELECT COUNT(*) FROM leads l WHERE l.assigned_id = u.id AND l.deleted_at IS NULL) AS total_assigned_leads
            " . $this->baseUserQuery() . "
            WHERE (u.role_id IN (5, 8) OR (u.role_id = 9 AND u.team_id IS NOT NULL))";

        $params = [];
        $types = "";

        if ($search) {
            $sql .= " AND (u.full_name LIKE ? OR u.phone LIKE ? OR u.email LIKE ?)";
            $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
            $types .= "sss";
        }

        if ($role) {
            $sql .= " AND u.role_id = ?";
            $params[] = $role;
            $types .= "i";
        }

        if ($teamId) {
            $sql .= " AND u.team_id = ?";
            $params[] = $teamId;
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
public function getPaginatedWithSalesExecutive($limit, $offset, $search = '', $roleId = null, $teamId = null)
{
    $sql = "SELECT
                u.*,
                e.id AS employee_id,
                e.photo AS employee_photo,
                e.make_user,
                d.name AS designation_name,
                dep.name AS department_name,
                r.name AS role_name
            " . $this->baseUserQuery() . "
            WHERE (u.role_id IN (5,8) OR (u.role_id = 9 AND u.team_id IS NOT NULL))"; // important change

    $params = [];
    $types = "";

    // 🔥 TEAM FILTER LOGIC
    if (in_array($roleId, [5, 9]) && !empty($teamId)) {
        $sql .= " AND u.team_id = ?";
        $params[] = $teamId;
        $types .= "i";
    }
    // else → show all (no condition)

    // 🔍 Search filter
    if (!empty($search)) {
        $sql .= " AND (u.full_name LIKE ? OR u.phone LIKE ? OR u.email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $types .= "sss";
    }

    $sql .= " ORDER BY u.id DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    $stmt = $this->db->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

    public function countAllForSales($search = '', $role = '', $teamId = null)
    {
        $sql = "SELECT COUNT(*) as total FROM users u WHERE 1";

        $params = [];
        $types = "";

        if (!$role) {
            $sql .= " AND (u.role_id IN (5, 8) OR (u.role_id = 9 AND u.team_id IS NOT NULL))";
        }

        if ($search) {
            $sql .= " AND (u.full_name LIKE ? OR u.phone LIKE ? OR u.email LIKE ?)";
            $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
            $types .= "sss";
        }

        if ($role) {
            $sql .= " AND u.role_id = ?";
            $params[] = $role;
            $types .= "i";
        }

        if ($teamId) {
            $sql .= " AND u.team_id = ?";
            $params[] = $teamId;
            $types .= "i";
        }

        $stmt = $this->db->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc()['total'];
    }
   public function countAllForSalesExecutive($search = '', $roleId = null, $teamId = null)
{
    $sql = "SELECT COUNT(*) as total FROM users u WHERE (u.role_id IN (5,8) OR (u.role_id = 9 AND u.team_id IS NOT NULL))";

    $params = [];
    $types = "";

    if (in_array($roleId, [5, 9]) && !empty($teamId)) {
        $sql .= " AND u.team_id = ?";
        $params[] = $teamId;
        $types .= "i";
    }

    if (!empty($search)) {
        $sql .= " AND (u.full_name LIKE ? OR u.phone LIKE ? OR u.email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $types .= "sss";
    }

    $stmt = $this->db->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();

    return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
}

    // for sales team Head
    public function getAll()
    {
        $sql = $this->db->prepare("
        SELECT
            u.id, 
            des.name as designation_name,
            r.name as role_name,
            u.full_name,
            e.employee_id as employee_code
        FROM users AS u
        LEFT JOIN employees e ON e.user_id = u.id
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
                IF(r.id IN (5,8,9), r.name, sr.name) AS role_name,
                t.name AS team_name
            FROM users u
            LEFT JOIN employees e ON e.user_id = u.id
            LEFT JOIN designations AS d ON d.id = u.designation_id
            LEFT JOIN teams AS t ON t.id = u.team_id
            LEFT JOIN roles AS r ON r.id = u.role_id
            LEFT JOIN roles AS sr ON sr.id = u.secondary_role_id
           WHERE (r.id IN (5,8,9) OR sr.id IN (5,8,9))
        

    "); // Sales Manager = 5, Sales Executive = 8,sales Team head = 9
        if (!$sql->execute()) {
            return []; // Error handle koro
        }

        $result = $sql->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }


    //for sales team head to get team members



public function getUsersByTeamAndRole($teamId)
{
    $sql = "SELECT 
                u.*,
                e.id AS employee_id,
                e.photo AS employee_photo,
                e.make_user,
                d.name AS designation_name,
                IF(r.id IN (5,8,9), r.name, sr.name) AS role_name,
                t.name AS team_name
            FROM users u
            LEFT JOIN employees e ON e.user_id = u.id
            LEFT JOIN designations d ON d.id = u.designation_id
            LEFT JOIN teams t ON t.id = u.team_id
            LEFT JOIN roles r ON r.id = u.role_id
            LEFT JOIN roles sr ON sr.id = u.secondary_role_id
            WHERE u.team_id = ? 
            AND (
                (r.id IN (5, 8) OR (r.id = 9 AND u.team_id IS NOT NULL))
                OR 
                (sr.id IN (5, 8) OR (sr.id = 9 AND u.team_id IS NOT NULL))
            )";

    $stmt = $this->db->prepare($sql);
    $stmt->bind_param("i", $teamId);
    $stmt->execute();

    $result = $stmt->get_result();
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

    public function getPotentialReportingBosses()
    {
        $sql = "SELECT u.id, u.full_name, r.name as role_name, d.name as designation_name
                FROM users u
                LEFT JOIN roles r ON r.id = u.role_id
                LEFT JOIN designations d ON d.id = u.designation_id
                WHERE u.role_id NOT IN (1, 2, 8, 23) OR u.role_id IS NULL
                ORDER BY u.full_name ASC";
        $stmt = $this->db->prepare($sql);
        if (!$stmt || !$stmt->execute()) {
            return [];
        }
        $result = $stmt->get_result();
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
        $team_id         = (int)($data['team_id'] ?? 0);

        $stmt = $this->db->prepare(
            "INSERT INTO users
        (full_name, employee_id, phone, email, designation_id, address, nid, role_id, user_name, password, photo, media_link1, media_link2, created_by,team_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)"
        );

        if (!$stmt) {
            error_log("User creation prepare failed: " . $this->db->error);
            return false;
        }

        $stmt->bind_param(
            "sissississsssii",
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
            $created_by,
            $team_id
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
            u.employee_id as user_employee_id,
            e.id AS employee_id,
            e.photo AS employee_photo,
            d.name AS designation_name,
            r.name AS role_name
        FROM users u
        LEFT JOIN employees e ON e.user_id = u.id
        LEFT JOIN designations d ON d.id = u.designation_id
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE u.id = ?
        LIMIT 1
    ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function update($data, $id)
    {
        if (!is_array($data)) return false;

        // Photo
        $photo = $data['photo'] ?? null;
        if (isset($_FILES['photo']) && !empty($_FILES['photo']['tmp_name'])) {
            $photo = uploadImage('photo', 'users', 'fixed', 300, 300);
        }

        // Password - Hash if provided
        $password = !empty($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : null;

        $email = $data['email'] ?? '';
        if (!empty($email)) {
            $checkEmail = $this->db->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
            if ($checkEmail) {
                $checkEmail->bind_param("si", $email, $id);
                $checkEmail->execute();
                $dupRes = $checkEmail->get_result();
                if ($dupRes && $dupRes->num_rows > 0) {
                    $currRes = $this->db->query("SELECT email FROM users WHERE id = $id");
                    if ($currRes && $cRow = $currRes->fetch_assoc()) {
                        $email = $cRow['email'];
                    }
                }
                $checkEmail->close();
            }
        }

        // Variables for bind_param (ensuring correct types)
        $full_name      = $data['full_name'] ?? '';
        // employee_id should be integer or null
        $emp_id         = ($data['employee_id'] && (int)$data['employee_id'] > 0) ? (int)$data['employee_id'] : null;
        $phone          = $data['phone'] ?? '';
        $designation_id = (int)($data['designation_id'] ?? 0);
        $address        = $data['address'] ?? '';
        $nid            = $data['nid'] ?? '';
        $role_id        = (int)($data['role_id'] ?? 2);
        $secondary_role_id = isset($data['secondary_role_id']) && $data['secondary_role_id'] !== '' ? (int)$data['secondary_role_id'] : null;
        $user_name      = $data['user_name'] ?? '';
        $media_link1    = $data['media_link1'] ?? '';
        $media_link2    = $data['media_link2'] ?? '';
        $created_by     = (int)($data['created_by'] ?? 0);
        $team_id        = (int)($data['team_id'] ?? 0);

        // Prepare statement
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
            created_by = ?,
            team_id = ?,
            secondary_role_id = ?
        WHERE id = ?"
        );

        if (!$stmt) throw new \Exception("User update prepare failed: " . $this->db->error);

        // Bind parameters
        if (!$stmt->bind_param(
            "sisssssisssssiiii",
            $full_name,
            $emp_id,
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
            $created_by,
            $team_id,
            $secondary_role_id,
            $id
        )) throw new \Exception("User update bind_param failed: " . $stmt->error);

        if (!$stmt->execute()) throw new \Exception("User update execute failed: " . $stmt->error);

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
            "SELECT u.*, LOWER(r.name) AS role_name, des.name AS designation_name, des.id as designation_id,
                    dept.name AS department_name, dept.id AS department_id,
                    e.id AS employee_db_id,
                    e.is_sales_co_leader,
                    e.team_id AS emp_team_id,
                    e.employee_status
             FROM users u
             LEFT JOIN roles r ON u.role_id = r.id
             LEFT JOIN designations des ON u.designation_id = des.id
             LEFT JOIN departments dept ON des.department_id = dept.id
             LEFT JOIN employees e ON e.user_id = u.id
             WHERE u.email = ?"
        );

        if (!$stmt) {
            error_log("getByEmail prepare failed: " . $this->db->error);
            // Fallback: query without employees join
            $stmt = $this->db->prepare(
                "SELECT u.*, LOWER(r.name) AS role_name, des.name AS designation_name, des.id as designation_id
                 FROM users u
                 LEFT JOIN roles r ON u.role_id = r.id
                 LEFT JOIN designations des ON u.designation_id = des.id
                 WHERE u.email = ?"
            );
            if (!$stmt) return null;
        }

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



public function countSalesTeams($roleId = null, $teamId = null)
{
    $sql = "SELECT COUNT(u.id) as total 
            FROM users u
            LEFT JOIN employees e ON e.user_id = u.id
            WHERE (u.role_id IN (5, 8) OR (u.role_id = 9 AND u.team_id IS NOT NULL))
              AND (e.employee_status IS NULL OR e.employee_status NOT IN ('resigned', 'inactive', 'terminated'))";

    $params = [];
    $types  = "";

    // Case 1: Role 5 or 9 AND has team → filter by team
    if (in_array($roleId, [5, 9]) && !empty($teamId)) {
        $sql .= " AND u.team_id = ?";
        $params[] = $teamId;
        $types .= "i";
    }

    // Case 2: Role 5 or 9 BUT NO team → show ALL (no filter)
    // (do nothing extra → default query already correct)

    $stmt = $this->db->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc();
    return $result['total'] ?? 0;
}
    
    // public function getSalesTeams(){
    //     $sql = $this->db->prepare("SELECT * FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name IN ('Sales Manager', 'Sales Executive')");
    //     $sql->execute();
    //     $result = $sql->get_result();
    //     return $result->fetch_all(MYSQLI_ASSOC);
    // }
public function getSalesTeams($roleId = null, $teamId = null, $userId = null)
{
    $sql = "SELECT 
                u.*,
                e.id AS employee_id,
                e.photo AS employee_photo,
                e.make_user,
                d.name AS designation_name,
                IF(r.id IN (5,8,9), r.name, sr.name) AS role_name,
                t.name AS team_name
            FROM users u
            LEFT JOIN employees e ON e.user_id = u.id
            LEFT JOIN designations d ON d.id = u.designation_id
            LEFT JOIN teams t ON t.id = u.team_id
            LEFT JOIN roles r ON r.id = u.role_id
            LEFT JOIN roles sr ON sr.id = u.secondary_role_id
            WHERE (
                (u.role_id IN (5, 8) OR (u.role_id = 9 AND u.team_id IS NOT NULL))
                OR
                (u.secondary_role_id IN (5, 8) OR (u.secondary_role_id = 9 AND u.team_id IS NOT NULL))
            )
              AND (e.employee_status IS NULL OR e.employee_status NOT IN ('resigned', 'inactive', 'terminated'))";

    $params = [];
    $types  = "";

    // 🔥 Role ভিত্তিক filter
    if (in_array($roleId, [5, 8, 9]) && !empty($teamId)) {
        $sql .= " AND u.team_id = ?";
        $params[] = $teamId;
        $types .= "i";
    }
    elseif ($roleId == 8) {
        // Manager → শুধু নিজের team
        $sql .= " AND u.team_id = ?";
        $params[] = $teamId;
        $types .= "i";
    }
    // role 9 → no filter (সব দেখবে)

    $stmt = $this->db->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

    // Get all CED Employees
    public function getCedEmployees()
    {
        $sql = "SELECT u.id, u.full_name, u.phone, u.email, r.name AS role_name 
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                LEFT JOIN employees e ON e.user_id = u.id
                WHERE (r.name LIKE '%ced%' OR r.name LIKE '%client cash in%' OR u.role_id = 7)
                  AND (e.employee_status IS NULL OR e.employee_status NOT IN ('resigned', 'terminated', 'inactive'))
                ORDER BY u.full_name ASC";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            die("Query failed: " . $this->db->error . " | SQL: " . $sql);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
