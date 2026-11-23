<?php
class DepartmentController extends Controller
{
    protected $db;
    protected $deparmentModel;
    protected $roleModel;
    protected $empTaskModel;
    public function __construct($db)
    {
        $this->db = $db;
        $this->deparmentModel = new Department($this->db);
        $this->empTaskModel = new EmployeeTask($this->db);
    }
    public function index()
    {
        
        // Get departments
        $departments = $this->deparmentModel->getAll();

        $this->view("hrm/departments/departments", [
            "current_route"   => "departments",
            "page_title"      => "Departments List",
            "departments"     => $departments,
        
        ]);
    }


    // Handle form submission
    public function store()
    {
        $data = [
            'name'   => $_POST['name'] ?? '',
            'status' => isset($_POST['status']) ? 1 : 0, // checkbox
            'created_by'      => $_SESSION['user']['id'] ?? null,
        ];

        if ($this->deparmentModel->create($data)) {
            setSuccess("Department created successfully.");
            header("Location: {$GLOBALS['base_url']}/departments");
            exit;
        } else {
            setError("Failed to create department.");
        }
    }
    public function update()
    {
        $data = [
            'id'     => $_POST['id'] ?? 0,
            'name'   => $_POST['name'] ?? '',
            'status' => isset($_POST['status']) ? intval($_POST['status']) : 0,
            'created_by'      => $_SESSION['user']['id'] ?? null,
        ];

        // print_r($data); // debug: check values

        if ($this->deparmentModel->update($data)) {
            setSuccess("Department updated successfully.");
            header("Location: {$GLOBALS['base_url']}/departments");
            exit;
        } else {
            setError("Failed to update department.");
            // echo "Failed to update department";
        }
    }




    public function show()
    {
        $this->view("tasks/view-task", [
            "current_route" => "show-task",
            "active" => "show-task",
            "psge_title" => "Show Task",
            "show_breadcrumb" => true,
        ]);
    }

    public function delete()
    {
        $id = $_POST['id'] ?? 0;

        if ($id && $this->deparmentModel->delete($id)) {
            setSuccess("Department deleted successfully.");
            header("Location: {$GLOBALS['base_url']}/departments");
            exit;
        } else {
            echo "<h3 style='color:red'>Failed to delete department</h3>";
        }
    }

    /**
     * Public read-only JSON endpoint returning active departments.
     */
    public function publicList()
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $departments = $this->deparmentModel->getActive();
        if ($departments === false) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to fetch departments']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'count'   => count($departments),
            'data'    => $departments,
        ]);
        exit;
    }

}
