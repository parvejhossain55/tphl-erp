<?php

class PayslipItemController extends Controller
{
    protected $db;
    protected $payslipItemModel;
    protected $taskModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->payslipItemModel = new PayslipItem($this->db);
        $this->taskModel = new Task($this->db);
    }

    public function index()
    {
      
        // Pass all data to view
        $items = $this->payslipItemModel->getAll();
        $this->view("payrolls/payslip-items/payslip-items", [
            "current_route" => "payslip-items",
            "page_title"    => "Payslip Items Lists",
            "items"    => $items,
           
        ]);
    }


       // Handle form submission
public function store()
{
    $data = [
        'name'   => $_POST['name'] ?? '',
        'type' => $_POST['type'] , // checkbox
    ];

    if ($this->payslipItemModel->create($data)) {
        header("Location: {$GLOBALS['base_url']}/payslip-items");
        exit;
    } else {
        echo "<h3 style='color:red'>Failed to create department</h3>";
    }
}
public function update()
{
    $data = [
        'id'     => $_POST['id'] ?? 0,          
        'name'   => $_POST['name'] ?? '',        
        'status' => isset($_POST['status']) ? intval($_POST['status']) : 0, 
    ];

    // print_r($data); // debug: check values

    if ($this->payslipItemModel->update($data)) {
        header("Location: {$GLOBALS['base_url']}/payslip-items");
        exit;
    } else {
        echo "Failed to update department";
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

    if ($id && $this->payslipItemModel->delete($id)) {
        header("Location: {$GLOBALS['base_url']}/payslip-items");
        exit;
    } else {
        echo "<h3 style='color:red'>Failed to delete department</h3>";
    }
}
   
}