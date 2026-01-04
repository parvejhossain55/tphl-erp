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

        $totalEmployees = $this->employeeModel->getTotalCount();
        $payslips = $this->paymentModel->getAllWithEmployee();
        $paidCount = $this->paymentModel->getPaidCount();
        $pendingCount = $this->paymentModel->getPendingCount();
        // Get all company banks
        $companyBanks = $this->bankModel->getAll();
        //  Pass all data to view
        $this->view("payrolls/payslips/payslip-lists", [
            "current_route" => "payslip-lists",
            "page_title"    => "Payroll Lists",
            "payslips" => $payslips,
            "totalEmployees" => $totalEmployees,
            "paidCount" => $paidCount,
            "pendingCount" => $pendingCount,
            "companyBanks" => $companyBanks,

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






    // public function downloadPDF($id)
    // {
    //     ini_set('memory_limit', '1024M'); // Increase memory
    //     require_once __DIR__ . '/../../vendor/autoload.php';

    //     $payslip = $this->paymentModel->getByIdWithItems($id);
    //     if (!$payslip) die('Payslip not found');

    //     ob_start();
    //     $this->view("payrolls/payslips/payslip-download", ["payslip" => $payslip]);
    //     $html = ob_get_clean();

    //     $mpdf = new \Mpdf\Mpdf(['tempDir' => __DIR__ . '/../../tmp']); // tempDir optional
    //     $mpdf->WriteHTML($html);
    //     $mpdf->Output('payslip_'.$payslip['id'].'.pdf','D');
    // }



    public function downloadPDF($id)
    {
        ini_set('memory_limit', '1024M');
        require_once __DIR__ . '/../../vendor/autoload.php';

        $payslip = $this->paymentModel->getByIdWithItems($id);
        if (!$payslip) {
            die('Payslip not found');
        }

        ob_start();
        $isPdf = true;
        $this->view("payrolls/payslips/payslip-download", [
            "current_route" => "download-payslip",
            "isPdf" => $isPdf,
            "payslip" => $payslip
        ]);
        $html = ob_get_clean();

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_top' => 15,
            'margin_bottom' => 15,
            'margin_left' => 15,
            'margin_right' => 15,
            'default_font' => 'dejavusans'
        ]);

        $mpdf->WriteHTML($html);
        $mpdf->Output(
            'Payslip_' . $payslip['employee_name'] . '.pdf',
            \Mpdf\Output\Destination::INLINE
        );
    }
}
