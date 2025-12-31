<?php

class PayrollController extends Controller
{
    protected $db;
    protected $employeeModel;
    protected $payslipItemModel;
    protected $paymentModel;
    public function __construct($db)
    {
        $this->db = $db;
        $this->employeeModel = new Employee($this->db);
        $this->payslipItemModel = new PayslipItem($this->db);
        $this->paymentModel = new Payslip($this->db);
    }

    public function index()
    {
        $totalEmployees = $this->employeeModel->getTotalCount();
        $payslips = $this->paymentModel->getAllWithEmployee();
        $paidCount = $this->paymentModel->getPaidCount();
        $pendingCount = $this->paymentModel->getPendingCount();

        //  Pass all data to view
        $this->view("payrolls/payslips/payslip-lists", [
            "current_route" => "payslip-lists",
            "page_title"    => "Payroll Lists",
            "payslips" => $payslips,
            "totalEmployees" => $totalEmployees,
            "paidCount" => $paidCount,
            "pendingCount" => $pendingCount,

        ]);
    }
    public function create()
    {
        $employees = $this->employeeModel->getAllWithDesignation();
        $items = $this->payslipItemModel->getAll();
        $this->view("payrolls/payslips/create-payslip", [
            "current_route" => "create-payslip",
            "page_title"    => "Create Payslip",
            "employees"     => $employees,
            "items"         => $items,
        ]);
    }
    public function store()
    {
        if (empty($_POST['payslip_payload'])) {
            die('Invalid Payslip Data');
        }

        $payload = json_decode($_POST['payslip_payload'], true);

        $payslipModel = new Payslip($this->db);
        $detailsModel = new PayslipDetail($this->db);

        $payslipId = $payslipModel->create([
            'employee_id'     => $payload['employee_id'],
            'designation_id'  => $payload['designation_id'],
            'payslip_month'   => date('Y-m-01'),
            'total_earning'   => $payload['total_earning'],
            'total_deduction' => $payload['total_deduction'],
            'net_salary'      => $payload['net_salary'],
            'status'          => 'pending'
        ]);

        // Earnings
        foreach ($payload['earnings'] as $itemId => $amount) {
            if ($amount > 0) {
                $detailsModel->create($payslipId, $itemId, $amount);
            }
        }

        // Deductions
        foreach ($payload['deductions'] as $itemId => $amount) {
            if ($amount > 0) {
                $detailsModel->create($payslipId, $itemId, $amount);
            }
        }

        // Clear localStorage
        echo "<script>
        localStorage.removeItem('payslip_data');
        window.location.href = '{$GLOBALS['base_url']}/payslip-lists';
    </script>";
    }
}
