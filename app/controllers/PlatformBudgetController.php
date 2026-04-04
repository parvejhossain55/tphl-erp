<?php

class PlatformBudgetController extends Controller
{
    protected $db;
    protected $platformBudgetModel;
    protected $taskModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->platformBudgetModel = new PlatformBudget($this->db);
    }

    public function index()
    {

        // Pass all data to view
        $items = $this->platformBudgetModel->getAll();
        $this->view("digital-marketing-team/digital-marketing-head-dashboard", [
            "current_route" => "platform-budgets",
            "page_title"    => "Platform Budgets",
            "items"    => $items,

        ]);
    }


    // Handle form submission
    public function store()
    {
        $data = [
          
            'created_by'      => $_SESSION['user']['id'] ?? null,
        ];

        if ($this->platformBudgetModel->create($data)) {
            setSuccess("Platform budget created successfully.");
            header("Location: {$GLOBALS['base_url']}/platform-budgets");
            exit;
        } else {
            setError("Failed to create platform budget.");
        }
    }
 


    public function delete()
    {
        $id = $_POST['id'] ?? 0;

        if ($this->platformBudgetModel->delete($id)) {
            setSuccess("Platform budget deleted successfully.");
        } else {
            setError("Failed to delete platform budget.");
        }
    }
}
