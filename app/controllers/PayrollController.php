<?php

class PayrollController extends Controller
{
    protected $db;
    protected $employeeModel;
    protected $payslipItemModel;
    protected $paymentModel;
    protected $bankModel;
    public function __construct($db)
    {
        $this->db = $db;
        $this->employeeModel = new Employee($this->db);
        $this->payslipItemModel = new PayslipItem($this->db);
        $this->paymentModel = new Payslip($this->db);
        $this->bankModel = new CompanyBankAccount($this->db);
    }

    public function index()
    {
        // Current month/year
        $currentYear  = date('Y');
        $currentMonth = date('m');

        // GET param use, fallback to current month/year
        $selectedYear  = isset($_GET['year']) ? $_GET['year'] : $currentYear;
        $selectedMonth = isset($_GET['month']) ? $_GET['month'] : $currentMonth;

        // Fetch payslips
        $payslips = $this->paymentModel->getAllWithEmployeeFiltered($selectedYear, $selectedMonth);

        $totalEmployees = $this->employeeModel->getTotalCount();
        $paidCount      = $this->paymentModel->getPaidCount($selectedYear, $selectedMonth);
        $pendingCount   = $this->paymentModel->getPendingCount($selectedYear, $selectedMonth);
        $companyBanks   = $this->bankModel->getAll();

        $this->view("payrolls/payslips/payslip-lists", [
            "current_route"  => "payslip-lists",
            "page_title"     => "Payroll Lists",
            "payslips"       => $payslips,
            "totalEmployees" => $totalEmployees,
            "paidCount"      => $paidCount,
            "pendingCount"   => $pendingCount,
            "companyBanks"   => $companyBanks,
            "selectedYear"   => $selectedYear,  // Pass to view
            "selectedMonth"  => $selectedMonth, // Pass to view
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
        $approvalModel = new ApprovalRequest($this->db);

        $payslipId = $payslipModel->create([
            'employee_id'     => $payload['employee_id'],
            'designation_id'  => $payload['designation_id'],
            'payslip_month'   => date('Y-m-01'),
            'total_earning'   => $payload['total_earning'],
            'total_deduction' => $payload['total_deduction'],
            'net_salary'      => $payload['net_salary'],
            'status'          => 'pending',
            'created_by'      => $_SESSION['user']['id'],
        ]);

        foreach ($payload['earnings'] as $itemId => $amount) {
            if ($amount > 0) {
                $detailsModel->create($payslipId, $itemId, $amount);
            }
        }

        foreach ($payload['deductions'] as $itemId => $amount) {
            if ($amount > 0) {
                $detailsModel->create($payslipId, $itemId, $amount);
            }
        }

        //  CREATE APPROVAL REQUEST
        $approvalModel->create([
            'department'      => 'HR',
            'request_type'    => 'salary',
            'title'           => 'Salary Approval',
            'source_table'    => 'payslips',
            'source_id'       => $payslipId,
            'request_amount'  => $payload['net_salary'],
            'requested_by'    => $_SESSION['user']['id'],
            'approve_role'    => 'management',
            'meta_data'       => json_encode([
                'employee_id' => $payload['employee_id'],
                'month' => date('d F Y')
            ])
        ]);


        echo "<script>
        localStorage.removeItem('payslip_data');
        window.location.href = '{$GLOBALS['base_url']}/payslip-lists';
    </script>";
    }



    public function updatePay()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $payslipId = $_POST['payslip_id'] ?? null;
        $paymentMethod = $_POST['payment_method'] ?? null;
        $companyBankId = $_POST['company_bank_id'] ?? null;

        if (!$payslipId || !$paymentMethod) {
            $_SESSION['error'] = "Payslip ID or payment method missing.";
            header("Location: " . $GLOBALS['base_url'] . "/payslip-lists");
            exit;
        }

        $companyBankId = $companyBankId ?: null; // nullable

        $success = $this->paymentModel->payPayslip($payslipId, $paymentMethod, $companyBankId);

        if ($success) {
            $_SESSION['success'] = "Payslip paid successfully.";
        } else {
            $_SESSION['error'] = "Failed to update payslip. Please try again.";
        }

        header("Location: " . $GLOBALS['base_url'] . "/payslip-lists");
        exit;
    }


    public function show($id)
    {

        $payslip = $this->paymentModel->getByIdWithItems($id);

        $this->view("payrolls/payslips/show-payslip", [

            "current_route" => "show-payslip",
            "page_title"    => "Payslip Details",
            "payslip"       => $payslip
        ], false);
    }

    public function dueEmployeePayslip()
    {
        header('Content-Type: application/json');

        $month = $_GET['month'] ?? null;
        if (!$month) {
            echo json_encode([]);
            exit;
        }

        $dueEmployees = $this->paymentModel->getDueEmployeePayslip($month);
        echo json_encode($dueEmployees);
        exit;
    }
}
