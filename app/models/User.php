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


        $sql = $this->db->prepare("
        SELECT
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
    ");
        if (!$sql->execute()) {
            return []; // Error handle koro
        }

        $result = $sql->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }


    public function create($data)
    {
        // Require the helper
        // require_once __DIR__ . '/../helpers/password_helper.php';

        // Module name দিয়ে photo upload
        $photo = uploadImage('userphoto', 'users', 'fixed', 300, 300);

        // Ensure password is hashed using helper
        $password = ensureHashedPassword($data['password']);

        $stmt = $this->db->prepare(
            "INSERT INTO users
        (full_name, phone, email, designation_id, address, nid, role_id, user_name, password, photo, media_link1, media_link2,created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)"
        );

        $stmt->bind_param(
            "sssississsssi",
            $data['full_name'],
            $data['phone'],
            $data['email'],
            $data['designation_id'],
            $data['address'],
            $data['nid'],
            $data['role_id'],
            $data['user_name'],
            $password,
            $photo,
            $data['media_link1'],
            $data['media_link2'],
            $data['created_by']
        );

        if ($stmt->execute()) {
            return $this->db->insert_id; // Return inserted ID
        } else {
            error_log("User creation failed: " . $stmt->error);
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

        // Assign all values to variables
        $full_name      = $data['full_name'] ?? '';
        $phone          = $data['phone'] ?? '';
        $email          = $data['email'] ?? '';
        $designation_id = (int)($data['designation_id'] ?? 0);
        $address        = $data['address'] ?? '';
        $nid            = $data['nid'] ?? '';
        $role_id        = (int)($data['role_id'] ?? 2); // default role = 2
        $user_name      = $data['user_name'] ?? '';
        $media_link1    = $data['media_link1'] ?? '';
        $media_link2    = $data['media_link2'] ?? '';
        $created_by     = (int)($data['created_by'] ?? 0);

        $stmt = $this->db->prepare(
            "UPDATE users SET
        full_name = ?,
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

        // Bind parameters: use references for null values
        $pwd = $password ?: null;
        $ph  = $photo ?: null;

        if (!$stmt->bind_param(
            "sssississssssi",
            $full_name,
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
}
