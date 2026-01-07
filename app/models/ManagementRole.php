<?php
class ManagementRole extends Model
{
    protected $db;
    protected $table = 'management_categories';
    protected $rolesTable = 'management_roles'; // Add roles table

    public function __construct($db)
    {
        $this->db = $db;
    }

    // ==================== ROLES METHODS ====================
    
    // Get all roles
    public function getAllRoles()
    {
        $sql = "SELECT mr.*, u.full_name as created_by_name
                FROM {$this->rolesTable} mr
                LEFT JOIN users u ON mr.created_by = u.id
                ORDER BY 
                    CASE WHEN mr.status = 1 THEN 1 ELSE 2 END,
                    mr.role_name ASC,
                    mr.created_at DESC";
        
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

    // Get all active roles
    public function getAllActiveRoles()
    {
        $sql = "SELECT mr.*, u.full_name as created_by_name
                FROM {$this->rolesTable} mr
                LEFT JOIN users u ON mr.created_by = u.id
                WHERE mr.status = 1
                ORDER BY mr.role_name ASC, mr.created_at DESC";
        
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
                FROM {$this->rolesTable} mr
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
            // Check if role name already exists for the same person
            if ($this->roleExists($data['role_name'], $data['person_name'])) {
                return [
                    'success' => false,
                    'message' => 'This role and person combination already exists.'
                ];
            }

            $sql = "INSERT INTO {$this->rolesTable} 
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
            // Check if role exists (excluding current role)
            if ($this->roleExists($data['role_name'], $data['person_name'], $data['id'])) {
                return [
                    'success' => false,
                    'message' => 'This role and person combination already exists.'
                ];
            }

            $sql = "UPDATE {$this->rolesTable} SET
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

            $sql = "DELETE FROM {$this->rolesTable} WHERE id = ?";
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
    public function toggleRoleStatus($id)
    {
        try {
            $sql = "UPDATE {$this->rolesTable} 
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

    // Check if role exists
    private function roleExists($roleName, $personName, $excludeId = null)
    {
        $sql = "SELECT id FROM {$this->rolesTable} 
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

    // ==================== CATEGORIES METHODS ====================

    // Get all active categories
    public function getAllActiveCategories()
    {
        $sql = "SELECT mc.*, u.full_name as created_by_name
                FROM {$this->table} mc
                LEFT JOIN users u ON mc.created_by = u.id
                WHERE mc.status = 'active'
                ORDER BY mc.category_name ASC, mc.created_at DESC";
        
        $result = $this->db->query($sql);
        
        if (!$result) {
            error_log("Database error: " . $this->db->error);
            return [];
        }
        
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        
        $result->free();
        return $categories;
    }

    // Get all categories (including inactive)
    public function getAllCategories()
    {
        $sql = "SELECT mc.*, u.full_name as created_by_name
                FROM {$this->table} mc
                LEFT JOIN users u ON mc.created_by = u.id
                ORDER BY 
                    CASE WHEN mc.status = 'active' THEN 1 ELSE 2 END,
                    mc.category_name ASC,
                    mc.created_at DESC";
        
        $result = $this->db->query($sql);
        
        if (!$result) {
            error_log("Database error: " . $this->db->error);
            return [];
        }
        
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        
        $result->free();
        return $categories;
    }

    // Get category by ID
    public function getCategoryById($id)
    {
        $sql = "SELECT mc.*, u.full_name as created_by_name
                FROM {$this->table} mc
                LEFT JOIN users u ON mc.created_by = u.id
                WHERE mc.id = ?";
        
        $stmt = $this->db->prepare($sql);
        
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return false;
        }
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $category = $result->fetch_assoc();
        
        $stmt->close();
        return $category;
    }

    // Add new category
    public function addCategory($data)
    {
        try {
            // Check if category name already exists
            if ($this->categoryNameExists($data['category_name'])) {
                return [
                    'success' => false,
                    'message' => 'Category name already exists.'
                ];
            }

            $sql = "INSERT INTO {$this->table} 
                    (category_name, description, status, created_by)
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
                "sssi",
                $data['category_name'],
                $data['description'],
                $data['status'],
                $data['created_by']
            );
            
            $result = $stmt->execute();
            $insertId = $this->db->insert_id;
            $stmt->close();
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Category added successfully!',
                    'id' => $insertId
                ];
            } else {
                error_log("Execute failed: " . $this->db->error);
                return [
                    'success' => false,
                    'message' => 'Failed to add category.'
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

    // Update category
    public function updateCategory($data)
    {
        try {
            // Check if category name already exists (excluding current category)
            if ($this->categoryNameExists($data['category_name'], $data['id'])) {
                return [
                    'success' => false,
                    'message' => 'Category name already exists.'
                ];
            }

            $sql = "UPDATE {$this->table} SET
                    category_name = ?,
                    description = ?,
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
                "sssi",
                $data['category_name'],
                $data['description'],
                $data['status'],
                $data['id']
            );
            
            $result = $stmt->execute();
            $stmt->close();
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Category updated successfully!'
                ];
            } else {
                error_log("Execute failed: " . $this->db->error);
                return [
                    'success' => false,
                    'message' => 'Failed to update category.'
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

    // Delete category
    public function deleteCategory($id)
    {
        try {
            // Check if category is being used in transactions
            if ($this->categoryInUse($id)) {
                return [
                    'success' => false,
                    'message' => 'Cannot delete category. It is being used in cash out transactions.'
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
                    'message' => 'Category deleted successfully!'
                ];
            } else {
                error_log("Execute failed: " . $this->db->error);
                return [
                    'success' => false,
                    'message' => 'Failed to delete category.'
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

    // Toggle category status
    public function toggleStatus($id)
    {
        try {
            $sql = "UPDATE {$this->table} 
                    SET status = IF(status = 'active', 'inactive', 'active') 
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

    // Check if category name already exists
    private function categoryNameExists($categoryName, $excludeId = null)
    {
        $sql = "SELECT id FROM {$this->table} 
                WHERE category_name = ?";
        
        if ($excludeId) {
            $sql .= " AND id != ?";
        }
        
        $stmt = $this->db->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        if ($excludeId) {
            $stmt->bind_param("si", $categoryName, $excludeId);
        } else {
            $stmt->bind_param("s", $categoryName);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }

    // Check if category is being used in cash out transactions
    private function categoryInUse($categoryId)
    {
        $sql = "SELECT id FROM management_cashout WHERE category_id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("i", $categoryId);
        $stmt->execute();
        $result = $stmt->get_result();
        $inUse = $result->num_rows > 0;
        $stmt->close();
        
        return $inUse;
    }

    // Get count of categories by status
    public function getCategoryCount($status = null)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        
        if ($status) {
            $sql .= " WHERE status = ?";
        }
        
        $stmt = $this->db->prepare($sql);
        
        if (!$stmt) {
            return 0;
        }
        
        if ($status) {
            $stmt->bind_param("s", $status);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['count'] ?? 0;
    }

    // Get categories with transaction counts
    public function getCategoriesWithTransactionCounts()
    {
        $sql = "SELECT 
                    mc.*,
                    u.full_name as created_by_name,
                    COUNT(mco.id) as transaction_count,
                    COALESCE(SUM(mco.amount), 0) as total_amount
                FROM {$this->table} mc
                LEFT JOIN users u ON mc.created_by = u.id
                LEFT JOIN management_cashout mco ON mc.id = mco.category_id
                WHERE mc.status = 'active'
                GROUP BY mc.id
                ORDER BY mc.category_name ASC";
        
        $result = $this->db->query($sql);
        
        if (!$result) {
            error_log("Database error: " . $this->db->error);
            return [];
        }
        
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        
        $result->free();
        return $categories;
    }
}