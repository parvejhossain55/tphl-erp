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
            'type' => $_POST['type'] ?? '',
            'created_by'      => $_SESSION['user']['id'] ?? null,
        ];

        if ($this->payslipItemModel->create($data)) {
            setSuccess("Payslip item created successfully.");
            header("Location: {$GLOBALS['base_url']}/payslip-items");
            exit;
        } else {
            setError("Failed to create payslip item.");
        }
    }
    public function update($data, $id)
    {
        $stmt = $this->db->prepare("
        UPDATE bookings SET
            lead_id = ?,
            project_id = ?,
            flat_id = ?,
            created_by = ?,
            agreement_value = ?,
            booking_date = ?,
            booking_status = ?
        WHERE id = ?
    ");

        $stmt->bind_param(
            "iiiidssi",
            $data['lead_id'],
            $data['project_id'],
            $data['flat_id'],
            $data['created_by'],
            $data['agreement_value'],
            $data['booking_date'],
            $data['booking_status'],
            $id
        );

        return $stmt->execute();
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
