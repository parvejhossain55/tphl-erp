<?php

class Role extends Model
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Get all roles
    public function getAll()
    {
        $stmt = $this->db->prepare("SELECT * FROM roles ORDER BY id DESC");
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Create a new role
// Create a new role
public function create($data)
{
    $this->db->begin_transaction();

    try {
        // Insert role (without created_by)
        $stmt = $this->db->prepare("INSERT INTO roles (name) VALUES (?)");
        if (!$stmt) throw new Exception($this->db->error);
        $stmt->bind_param("s", $data['name']);
        if (!$stmt->execute()) throw new Exception($stmt->error);

        $role_id = $this->db->insert_id;

        // Insert role permissions
        if (!empty($data['permissions']) && is_array($data['permissions'])) {
            $stmt_perm = $this->db->prepare(
                "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)"
            );
            if (!$stmt_perm) throw new Exception($this->db->error);

            foreach ($data['permissions'] as $perm_id) {
                $stmt_perm->bind_param("ii", $role_id, $perm_id);
                if (!$stmt_perm->execute()) throw new Exception($stmt_perm->error);
            }
        }

        $this->db->commit();
        return true;

    } catch (Exception $e) {
        $this->db->rollback();
        echo "CREATE ERROR: " . $e->getMessage();
        return false;
    }
}

// Update role name + permissions






public function getRolesWithPermissions()
{
    $sql = "
        SELECT r.id AS role_id, r.name AS role_name, rp.permission_id, p.name AS permission_name
        FROM roles r
        LEFT JOIN role_permissions rp ON r.id = rp.role_id
        LEFT JOIN permissions p ON rp.permission_id = p.id
        ORDER BY r.id ASC
    ";

    $result = $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);

    $roles = [];
    foreach ($result as $row) {
        $id = $row['role_id'];
        if (!isset($roles[$id])) {
            $roles[$id] = [
                'role_id' => $id,
                'role_name' => $row['role_name'],
                'permissions' => [] // store permission IDs
            ];
        }
        if ($row['permission_id']) {
            $roles[$id]['permissions'][] = $row['permission_id'];
        }
    }
    return $roles;
}


 // Update role name + permissions
public function update($data)
{
    $this->db->begin_transaction();

    try {
        // Update role name
        $stmt = $this->db->prepare("UPDATE roles SET name = ?, created_by = ? WHERE id = ?");
        if (!$stmt) throw new Exception($this->db->error);
        $stmt->bind_param("sii", $data['name'], $data['created_by'], $data['id']);
        if (!$stmt->execute()) throw new Exception($stmt->error);

        // Delete old permissions
        $del = $this->db->prepare("DELETE FROM role_permissions WHERE role_id = ?");
        if (!$del) throw new Exception($this->db->error);
        $del->bind_param("i", $data['id']);
        if (!$del->execute()) throw new Exception($del->error);

        // Insert new permissions
        if (!empty($data['permissions']) && is_array($data['permissions'])) {
            $stmt_perm = $this->db->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            if (!$stmt_perm) throw new Exception($this->db->error);

            foreach ($data['permissions'] as $perm_id) {
                $stmt_perm->bind_param("ii", $data['id'], $perm_id);
                if (!$stmt_perm->execute()) throw new Exception($stmt_perm->error);
            }
        }

        $this->db->commit();
        return true;

    } catch (Exception $e) {
        $this->db->rollback();
        echo "ERROR: " . $e->getMessage();
        return false;
    }
}


    // Get single role with its permissions
    public function getRoleById($id)
    {
        $sql = "
            SELECT r.id AS role_id, r.name AS role_name, rp.permission_id
            FROM roles r
            LEFT JOIN role_permissions rp ON r.id = rp.role_id
            WHERE r.id = ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if (empty($result)) return null;

        $role = [
            "id" => $result[0]['role_id'],
            "name" => $result[0]['role_name'],
            "permissions" => []
        ];

        foreach ($result as $row) {
            if ($row['permission_id']) {
                $role['permissions'][] = $row['permission_id'];
            }
        }

        return $role;
    }




    // Get role with its permissions

}
