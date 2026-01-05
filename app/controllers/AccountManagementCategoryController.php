<?php
class AccountManagementCategoryController extends Controller
{
    protected $db;
    protected $categoryModel;
        protected $managementModel;   // ✅ MISSING
    protected $roleModel; 

public function __construct($db)
{
    $this->db = $db;
    $this->managementModel = new AccountManagement($this->db);
    $this->roleModel = new ManagementRole($this->db);  // This actually manages categories, not roles
    if (session_status() === PHP_SESSION_NONE) session_start();
}

    // Display categories list
    public function index()
    {
        $categories = $this->categoryModel->getAllCategories();
        $statistics = $this->categoryModel->getStatistics();

        $this->view("accounts/cashout/management-categories", [
            "current_route" => "management-categories",
            "page_title" => "Manage Categories",
            "categories" => $categories,
            "statistics" => $statistics
        ]);
    }

    // Get category details (AJAX)
    public function getCategoryDetails()
    {
        $categoryId = $_GET['id'] ?? null;
        if (!$categoryId) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Category ID required']);
            exit;
        }
        
        header('Content-Type: application/json');
        $category = $this->categoryModel->getCategoryById($categoryId);
        if (!$category) {
            echo json_encode(['error' => 'Category not found']);
        } else {
            echo json_encode($category);
        }
        exit;
    }

    // Add new category (AJAX)
    public function addCategory()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $created_by = $this->getCurrentUserId();
            
            $data = [
                'category_name' => trim($_POST['category_name'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'status' => $_POST['status'] ?? 1,
                'created_by' => $created_by
            ];

            if (empty($data['category_name'])) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Category name is required.'
                ]);
                exit;
            }

            try {
                $result = $this->categoryModel->addCategory($data);
                header('Content-Type: application/json');
                echo json_encode($result);
            } catch (Exception $e) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ]);
            }
            exit;
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }

    // Update category (AJAX)
    public function updateCategory()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $categoryId = $_POST['id'] ?? null;
            if (!$categoryId) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid category ID'
                ]);
                exit;
            }

            $data = [
                'id' => $categoryId,
                'category_name' => trim($_POST['category_name'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'status' => $_POST['status'] ?? 1
            ];

            if (empty($data['category_name'])) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Category name is required.'
                ]);
                exit;
            }

            try {
                $result = $this->categoryModel->updateCategory($data);
                header('Content-Type: application/json');
                echo json_encode($result);
            } catch (Exception $e) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ]);
            }
            exit;
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }

    // Delete category (AJAX)
    public function deleteCategory()
    {
        $categoryId = $_GET['id'] ?? null;
        if (!$categoryId) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Invalid category ID'
            ]);
            exit;
        }

        try {
            $result = $this->categoryModel->deleteCategory($categoryId);
            header('Content-Type: application/json');
            echo json_encode($result);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    // Toggle category status (AJAX)
    public function toggleCategoryStatus()
    {
        $categoryId = $_GET['id'] ?? null;
        if (!$categoryId) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Invalid category ID'
            ]);
            exit;
        }

        try {
            $result = $this->categoryModel->toggleStatus($categoryId);
            header('Content-Type: application/json');
            echo json_encode($result);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    // Helper method to get current user ID from session
    private function getCurrentUserId()
    {
        if (isset($_SESSION['user']['id']) && !empty($_SESSION['user']['id'])) {
            $userId = $_SESSION['user']['id'];
            if ($this->validateUserExists($userId)) {
                return $userId;
            }
        }
        return $this->getFirstValidUserId();
    }

    // Validate if user exists in database
    private function validateUserExists($userId)
    {
        $sql = "SELECT id FROM users WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }

    // Get first valid user ID from database
    private function getFirstValidUserId()
    {
        $sql = "SELECT id FROM users ORDER BY id LIMIT 1";
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['id'];
        }
        
        return 1;
    }
}