<?php
class ManagementRole extends Model
{
    protected $db;
    protected $table = 'management_roles';

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Get all active roles
    public function getAllActiveRoles()
    {
        $sql = "SELECT mr.*, u.full_name as created_by_name
                FROM {$this->table} mr
                LEFT JOIN users u ON mr.created_by = u.id
                WHERE mr.status = 1
                ORDER BY mr.role_name ASC, mr.person_name ASC";
        
        $result = $this->db->query($sql);
        
        if (!$result) {
            error_log("Database error: " . $this->db->error);
            return [];
        }
        
        $roles = [];
        while ($row = $result->fetch_assoc()) {
            $roles[] = $row;
        }
        
        $result->free();
        return $roles;
    }

    // Get all roles (including inactive)
    public function getAllRoles()
    {
        $sql = "SELECT mr.*, u.full_name as created_by_name
                FROM {$this->table} mr
                LEFT JOIN users u ON mr.created_by = u.id
                ORDER BY mr.status DESC, mr.role_name ASC, mr.person_name ASC";
        
        $result = $this->db->query($sql);
        
        if (!$result) {
            error_log("Database error: " . $this->db->error);
            return [];
        }
        
        $roles = [];
        while ($row = $result->fetch_assoc()) {
            $roles[] = $row;
        }
        
        $result->free();
        return $roles;
    }

    // Get role by ID
    public function getRoleById($id)
    {
        $sql = "SELECT mr.*, u.full_name as created_by_name
                FROM {$this->table} mr
                LEFT JOIN users u ON mr.created_by = u.id
                WHERE mr.id = ?";
        
        $stmt = $this->db->prepare($sql);
        
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return false;
        }
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $role = $result->fetch_assoc();
        
        $stmt->close();
        return $role;
    }

    // Add new role
    public function addRole($data)
    {
        try {
            // Check if the EXACT COMBINATION of role_name AND person_name already exists
            // This allows multiple Directors, but prevents duplicate "Director - John Doe"
            if ($this->rolePersonCombinationExists($data['role_name'], $data['person_name'])) {
                return [
                    'success' => false,
                    'message' => 'This role and person combination already exists.'
                ];
            }

            $sql = "INSERT INTO {$this->table} 
                    (role_name, person_name, status, created_by)
                    VALUES (?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            
            if (!$stmt) {
                error_log("Prepare failed: " . $this->db->error);
                return [
                    'success' => false,
                    'message' => 'Database prepare failed.'
                ];
            }
            
            $stmt->bind_param(
                "ssii",
                $data['role_name'],
                $data['person_name'],
                $data['status'],
                $data['created_by']
            );
            
            $result = $stmt->execute();
            $insertId = $this->db->insert_id;
            $stmt->close();
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Role added successfully!',
                    'id' => $insertId
                ];
            } else {
                error_log("Execute failed: " . $this->db->error);
                return [
                    'success' => false,
                    'message' => 'Failed to add role.'
                ];
            }
        } catch (Exception $e) {
            error_log("Exception: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    // Update role
    public function updateRole($data)
    {
        try {
            // Check if the EXACT COMBINATION of role_name AND person_name already exists
            // (excluding the current role being updated)
            if ($this->rolePersonCombinationExists($data['role_name'], $data['person_name'], $data['id'])) {
                return [
                    'success' => false,
                    'message' => 'This role and person combination already exists.'
                ];
            }

            $sql = "UPDATE {$this->table} SET
                    role_name = ?,
                    person_name = ?,
                    status = ?
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            
            if (!$stmt) {
                error_log("Prepare failed: " . $this->db->error);
                return [
                    'success' => false,
                    'message' => 'Database prepare failed.'
                ];
            }
            
            $stmt->bind_param(
                "ssii",
                $data['role_name'],
                $data['person_name'],
                $data['status'],
                $data['id']
            );
            
            $result = $stmt->execute();
            $stmt->close();
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Role updated successfully!'
                ];
            } else {
                error_log("Execute failed: " . $this->db->error);
                return [
                    'success' => false,
                    'message' => 'Failed to update role.'
                ];
            }
        } catch (Exception $e) {
            error_log("Exception: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    // Delete role
    public function deleteRole($id)
    {
        try {
            // Check if role is being used in transactions
            if ($this->roleInUse($id)) {
                return [
                    'success' => false,
                    'message' => 'Cannot delete role. It is being used in transactions.'
                ];
            }

            $sql = "DELETE FROM {$this->table} WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            
            if (!$stmt) {
                error_log("Prepare failed: " . $this->db->error);
                return [
                    'success' => false,
                    'message' => 'Database prepare failed.'
                ];
            }
            
            $stmt->bind_param("i", $id);
            $result = $stmt->execute();
            $stmt->close();
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Role deleted successfully!'
                ];
            } else {
                error_log("Execute failed: " . $this->db->error);
                return [
                    'success' => false,
                    'message' => 'Failed to delete role.'
                ];
            }
        } catch (Exception $e) {
            error_log("Exception: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    // Toggle role status
    public function toggleStatus($id)
    {
        try {
            $sql = "UPDATE {$this->table} 
                    SET status = IF(status = 1, 0, 1) 
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            
            if (!$stmt) {
                return ['success' => false, 'message' => 'Database error'];
            }
            
            $stmt->bind_param("i", $id);
            $result = $stmt->execute();
            $stmt->close();
            
            return [
                'success' => $result,
                'message' => $result ? 'Status updated successfully!' : 'Failed to update status'
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Check if the EXACT combination of role_name AND person_name exists
    // This allows multiple people with the same role (e.g., multiple Directors)
    // but prevents the same person from having the same role twice
    private function rolePersonCombinationExists($roleName, $personName, $excludeId = null)
    {
        $sql = "SELECT id FROM {$this->table} 
                WHERE role_name = ? AND person_name = ?";
        
        if ($excludeId) {
            $sql .= " AND id != ?";
        }
        
        $stmt = $this->db->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        if ($excludeId) {
            $stmt->bind_param("ssi", $roleName, $personName, $excludeId);
        } else {
            $stmt->bind_param("ss", $roleName, $personName);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }

    // Check if role is being used in transactions
    private function roleInUse($roleId)
    {
        $sql = "SELECT id FROM management_cashin WHERE role_id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("i", $roleId);
        $stmt->execute();
        $result = $stmt->get_result();
        $inUse = $result->num_rows > 0;
        $stmt->close();
        
        return $inUse;
    }

    // Get count of roles by role name (useful for displaying "Director (3)" etc.)
    public function getRoleCount($roleName)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE role_name = ? AND status = 1";
        
        $stmt = $this->db->prepare($sql);
        
        if (!$stmt) {
            return 0;
        }
        
        $stmt->bind_param("s", $roleName);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['count'] ?? 0;
    }
}