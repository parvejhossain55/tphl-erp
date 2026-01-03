<?php
class ManagementCategory extends Model
{
    protected $db;
    protected $table = 'management_categories';

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Get all active categories
    public function getAllActiveCategories()
    {
        $sql = "SELECT mc.*, u.full_name as created_by_name
                FROM {$this->table} mc
                LEFT JOIN users u ON mc.created_by = u.id
                WHERE mc.status = 1
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

    // Get all categories (including inactive)
    public function getAllCategories()
    {
        $sql = "SELECT mc.*, u.full_name as created_by_name
                FROM {$this->table} mc
                LEFT JOIN users u ON mc.created_by = u.id
                ORDER BY mc.status DESC, mc.category_name ASC";
        
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
            if ($this->categoryExists($data['category_name'])) {
                return [
                    'success' => false,
                    'message' => 'This category already exists.'
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
                "ssii",
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
            if ($this->categoryExists($data['category_name'], $data['id'])) {
                return [
                    'success' => false,
                    'message' => 'This category already exists.'
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
                "ssii",
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
                    'message' => 'Cannot delete category. It is being used in transactions.'
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

    // Check if category name exists
    private function categoryExists($categoryName, $excludeId = null)
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

    // Check if category is being used in transactions
    private function categoryInUse($categoryId)
    {
        // Check in cashin transactions
        $sql = "SELECT id FROM management_cashin WHERE category_id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("i", $categoryId);
            $stmt->execute();
            $result = $stmt->get_result();
            $inUseCashin = $result->num_rows > 0;
            $stmt->close();
            
            if ($inUseCashin) {
                return true;
            }
        }
        
        // Check in cashout transactions
        $sql = "SELECT id FROM management_cashout WHERE category_id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("i", $categoryId);
            $stmt->execute();
            $result = $stmt->get_result();
            $inUseCashout = $result->num_rows > 0;
            $stmt->close();
            
            return $inUseCashout;
        }
        
        return false;
    }

    // Get statistics
    public function getStatistics()
    {
        $sql = "SELECT 
                COUNT(*) as total_categories,
                SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as active_categories,
                SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as inactive_categories
                FROM {$this->table}";
        
        $result = $this->db->query($sql);
        
        if (!$result) {
            return [
                'total_categories' => 0,
                'active_categories' => 0,
                'inactive_categories' => 0
            ];
        }
        
        $stats = $result->fetch_assoc();
        $result->free();
        
        return $stats;
    }
}