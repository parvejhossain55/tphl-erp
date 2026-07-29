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
            'name'       => $_POST['name'] ?? '',
            'type'       => $_POST['type'] ?? '',
            'percentage' => isset($_POST['percentage']) && $_POST['percentage'] !== '' ? $_POST['percentage'] : null,
            'created_by' => $_SESSION['user']['id'] ?? null,
        ];

        if ($this->payslipItemModel->create($data)) {
            setSuccess("Payslip item created successfully.");
        } else {
            setError("Failed to create payslip item.");
        }
        header("Location: {$GLOBALS['base_url']}/payslip-items");
        exit;
    }

    public function update()
    {
        $id = $_POST['id'] ?? 0;
        $data = [
            'id'         => $id,
            'name'       => $_POST['name'] ?? '',
            'type'       => $_POST['type'] ?? '',
            'percentage' => isset($_POST['percentage']) && $_POST['percentage'] !== '' ? $_POST['percentage'] : null,
        ];

        if ($this->payslipItemModel->update($data)) {
            setSuccess("Payslip item updated successfully.");
        } else {
            setError("Failed to update payslip item.");
        }

        header("Location: {$GLOBALS['base_url']}/payslip-items");
        exit;
    }



    public function delete()
    {
        $id = $_POST['id'] ?? 0;

        try {
            if ($this->payslipItemModel->delete($id)) {
                setSuccess("Payslip item deleted successfully.");
            } else {
                setError("Failed to delete payslip item.");
            }
        } catch (Exception $e) {
            setError("Error: " . $e->getMessage());
        }

        header("Location: {$GLOBALS['base_url']}/payslip-items");
        exit;
    }
}
