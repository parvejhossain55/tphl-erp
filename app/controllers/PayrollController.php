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
        $currentYear = date('Y');
        $currentMonth = date('m');

        // GET param use, fallback to current month/year
        $selectedYear = isset($_GET['year']) ? $_GET['year'] : $currentYear;
        $selectedMonth = isset($_GET['month']) ? $_GET['month'] : $currentMonth;
        $selectedStatus = isset($_GET['status']) ? $_GET['status'] : '';
        $selectedProjectId = isset($_GET['project_id']) && (int)$_GET['project_id'] > 0 ? (int)$_GET['project_id'] : null;

        // Fetch all payroll projects for dropdown
        $payroll_projects = [];
        $projectRes = $this->db->query("SELECT id, name, date FROM payroll_projects ORDER BY id DESC");
        if ($projectRes) {
            while ($pRow = $projectRes->fetch_assoc()) {
                $payroll_projects[] = $pRow;
            }
        }

        // Fetch payslips with status + project filter
        $payslips = $this->paymentModel->getAllWithEmployeeFiltered($selectedYear, $selectedMonth, $selectedStatus, $selectedProjectId);

        $totalEmployees = $this->employeeModel->getTotalCount();
        $paidCount = $this->paymentModel->getPaidCount($selectedYear, $selectedMonth);
        $pendingCount = $totalEmployees - $paidCount;
        $paidSalarySum = $this->paymentModel->getPaidSalarySum($selectedYear, $selectedMonth);
        $unpaidSalarySum = $this->paymentModel->getUnpaidSalarySum($selectedYear, $selectedMonth);
        $companyBanks = $this->bankModel->getAll();

        // Fetch all local employee IDs, names, departments, and designations for Tipsoi matching
        $localQuery = $this->db->query("
            SELECT e.employee_id, e.full_name, dept.name AS department_name, desg.name AS designation_name 
            FROM employees AS e
            LEFT JOIN departments AS dept ON e.department_id = dept.id
            LEFT JOIN designations AS desg ON e.designation_id = desg.id
        ");
        $allLocalEmployees = [];
        if ($localQuery) {
            while ($row = $localQuery->fetch_assoc()) {
                $allLocalEmployees[] = [
                    'employee_id' => trim($row['employee_id'] ?? ''),
                    'full_name' => trim($row['full_name'] ?? ''),
                    'department_name' => trim($row['department_name'] ?? 'N/A'),
                    'designation_name' => trim($row['designation_name'] ?? 'N/A')
                ];
            }
        }

        $this->view("payrolls/payslips/payslip-lists", [
            "current_route" => "payslip-lists",
            "page_title" => "Payroll Lists",
            "payslips" => $payslips,
            "totalEmployees" => $totalEmployees,
            "paidCount" => $paidCount,
            "pendingCount" => $pendingCount,
            "paidSalarySum" => $paidSalarySum,
            "unpaidSalarySum" => $unpaidSalarySum,
            "companyBanks" => $companyBanks,
            "selectedYear" => $selectedYear,
            "selectedMonth" => $selectedMonth,
            "selectedStatus" => $selectedStatus,
            "selectedProjectId" => $selectedProjectId,
            "payroll_projects" => $payroll_projects,
            "allLocalEmployees" => $allLocalEmployees,
        ]);
    }

    public function create()
    {
        $employees = $this->employeeModel->getAllWithDesignation();
        $items = $this->payslipItemModel->getAll();

        $salaryItemsByEmployee = [];
        $salaryItemsQuery = $this->db->query("SELECT employee_id, payslip_item_id, amount FROM employee_salary_items");
        if ($salaryItemsQuery) {
            while ($salaryItem = $salaryItemsQuery->fetch_assoc()) {
                $salaryItemsByEmployee[(int) $salaryItem['employee_id']][(int) $salaryItem['payslip_item_id']] = (float) $salaryItem['amount'];
            }
        }

        foreach ($employees as &$employee) {
            $employee['salary_items'] = $salaryItemsByEmployee[(int) $employee['id']] ?? [];
        }
        unset($employee);

        if (!class_exists('AdvanceSalary')) {
            require_once __DIR__ . '/../models/AdvanceSalary.php';
        }
        $advanceSalaryModel = new AdvanceSalary($this->db);
        $currentMonth = date('Y-m');
        foreach ($employees as &$emp) {
            $emp['advance_salary'] = $advanceSalaryModel->getMonthlyDeductionAmount($emp['id'], $currentMonth);
        }
        unset($emp);

        $this->view("payrolls/payslips/create-payslip", [
            "current_route" => "create-payslip",
            "page_title" => "Create Payslip",
            "employees" => $employees,
            "items" => $items,

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

        $isDraft = (!empty($payload['is_draft']) && $payload['is_draft'] == 1)
            || (!empty($_POST['is_draft']) && $_POST['is_draft'] == 1)
            || (!empty($_POST['submit_action']) && $_POST['submit_action'] === 'draft');

        $status = 'pending';
        if (!empty($_POST['submit_action']) && $_POST['submit_action'] === 'on_hold') {
            $status = 'on_hold';
        } elseif ($isDraft) {
            $status = 'draft';
        }

        $payslipId = $payslipModel->create([
            'employee_id' => $payload['employee_id'],
            'designation_id' => $payload['designation_id'],
            'payslip_month' => date('Y-m-01'),
            'total_earning' => $payload['total_earning'],
            'total_deduction' => $payload['total_deduction'],
            'net_salary' => $payload['net_salary'],
            'status' => $status,
            'upload_by' => 'manual',
            'created_by' => $_SESSION['user']['id'],
        ]);

        foreach ($payload['earnings'] as $itemId => $amount) {
            if ($amount > 0) {
                $detailsModel->create($payslipId, $itemId, $amount);
            }
        }

        foreach ($payload['deductions'] as $itemId => $amount) {
            if ($amount > 0) {
                $detailsModel->create($payslipId, $itemId, $amount);

                // Track Advance Salary deduction
                $stmtItem = $this->db->prepare("SELECT name FROM payslip_items WHERE id = ?");
                $stmtItem->bind_param("i", $itemId);
                $stmtItem->execute();
                $itemRow = $stmtItem->get_result()->fetch_assoc();
                $stmtItem->close();

                if ($itemRow && strtolower(trim($itemRow['name'])) === 'advance salary') {
                    if (!class_exists('AdvanceSalary')) {
                        require_once __DIR__ . '/../models/AdvanceSalary.php';
                    }
                    $advanceSalaryModel = new AdvanceSalary($this->db);
                    $advanceRequests = $advanceSalaryModel->getPaidAdvancesByEmployee($payload['employee_id']);
                    $remainingDeduction = (float) $amount;
                    foreach ($advanceRequests as $advReq) {
                        if ($remainingDeduction <= 0)
                            break;
                        $advAmount = (float) $advReq['amount'];
                        $paidBack = (float) ($advReq['paid_back_amount'] ?? 0);
                        $canDeduct = $advAmount - $paidBack;

                        if ($canDeduct > 0) {
                            $toDeduct = min($remainingDeduction, $canDeduct);
                            $advanceSalaryModel->addDeduction($advReq['id'], $toDeduct, $payslipId);
                            $remainingDeduction -= $toDeduct;
                        }
                    }
                }
            }
        }

        // CREATE APPROVAL REQUEST ONLY IF NOT DRAFT OR ON_HOLD
        if ($status !== 'draft' && $status !== 'on_hold') {
            $approvalModel->create([
                'department' => 'HR',
                'request_type' => 'salary',
                'title' => 'Salary Approval',
                'source_table' => 'payslips',
                'source_id' => $payslipId,
                'request_amount' => $payload['net_salary'],
                'requested_by' => $_SESSION['user']['id'],
                'approve_role' => 'management',
                'meta_data' => json_encode([
                    'employee_id' => $payload['employee_id'],
                    'month' => date('d F Y')
                ])
            ]);
        }


        echo "<script>
        localStorage.removeItem('payslip_data');
        window.location.href = '{$GLOBALS['base_url']}/payslip-lists';
    </script>";
    }

    public function delete($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $payslipId = (int) $id;
        if ($payslipId) {
            $this->paymentModel->delete($payslipId);
            $_SESSION['success'] = "Payslip deleted successfully.";
        }

        header("Location: " . $GLOBALS['base_url'] . "/payslip-lists");
        exit;
    }

    public function bulkDelete()
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $input = file_get_contents('php://input');
        $payload = json_decode($input, true);

        if (empty($payload) || empty($payload['payslip_ids']) || !is_array($payload['payslip_ids'])) {
            echo json_encode(['success' => false, 'message' => 'No payslips selected for deletion.']);
            exit;
        }

        $deletedCount = 0;
        foreach ($payload['payslip_ids'] as $id) {
            $payslipId = (int)$id;
            if ($payslipId) {
                $this->paymentModel->delete($payslipId);
                $deletedCount++;
            }
        }

        echo json_encode(['success' => true, 'message' => "$deletedCount payslips deleted successfully."]);
        exit;
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
            "page_title" => "Payslip Details",
            "payslip" => $payslip
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

    public function showImportForm()
    {
        $this->view("payrolls/payslips/import-payslips", [
            "current_route" => "payslip-lists",
            "page_title" => "Import Payslips"
        ]);
    }

    public function importCSV()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = "Please select a valid CSV file.";
            header("Location: " . $GLOBALS['base_url'] . "/import-payslips");
            exit;
        }

        $file = $_FILES['csv_file']['tmp_name'];
        if (($handle = fopen($file, "r")) !== FALSE) {
            $header = fgetcsv($handle, 1000, ",");
            if (!$header) {
                $_SESSION['error'] = "Empty CSV file.";
                header("Location: " . $GLOBALS['base_url'] . "/import-payslips");
                exit;
            }

            // Clean header column names (trim and remove BOM)
            $header = array_map(function ($col) {
                return trim(preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $col));
            }, $header);

            // Required standard columns
            $required = ['employee_email', 'payslip_month', 'payment_method', 'company_bank_name', 'total_earning', 'total_deduction', 'net_salary'];

            // Validate headers
            foreach ($required as $req) {
                if (!in_array($req, $header)) {
                    $_SESSION['error'] = "Missing required column in CSV: " . $req;
                    header("Location: " . $GLOBALS['base_url'] . "/import-payslips");
                    exit;
                }
            }

            // Find dynamic item columns
            $dynamicCols = [];
            foreach ($header as $idx => $colName) {
                if (!in_array($colName, $required)) {
                    if (preg_match('/^(.*?)\s*\((earning|deduction)\)$/i', $colName, $matches)) {
                        $itemName = trim($matches[1]);
                        $itemType = strtolower($matches[2]);
                        $dynamicCols[$idx] = [
                            'name' => $itemName,
                            'type' => $itemType
                        ];
                    } else {
                        $dynamicCols[$idx] = [
                            'name' => $colName,
                            'type' => 'earning'
                        ];
                    }
                }
            }

            // Begin DB Transaction
            $this->db->begin_transaction();

            $payslipModel = new Payslip($this->db);
            $detailsModel = new PayslipDetail($this->db);

            // Pre-process payslip items to get/create their ids
            $itemIds = [];
            foreach ($dynamicCols as $idx => $info) {
                $stmt = $this->db->prepare("SELECT id FROM payslip_items WHERE name = ? LIMIT 1");
                $stmt->bind_param("s", $info['name']);
                $stmt->execute();
                $res = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($res) {
                    $itemIds[$idx] = $res['id'];
                } else {
                    $stmtCreate = $this->db->prepare("INSERT INTO payslip_items (name, type, created_by, created_at) VALUES (?, ?, ?, NOW())");
                    $createdBy = $_SESSION['user']['id'] ?? 1;
                    $stmtCreate->bind_param("ssi", $info['name'], $info['type'], $createdBy);
                    $stmtCreate->execute();
                    $itemIds[$idx] = $this->db->insert_id;
                    $stmtCreate->close();
                }
            }

            $rowNum = 1;
            $errors = [];

            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $rowNum++;

                if (empty(array_filter($row))) {
                    continue;
                }

                // Map row to header
                $data = [];
                foreach ($header as $i => $col) {
                    $val = isset($row[$i]) ? trim($row[$i]) : '';
                    // Clean value from hidden characters/BOM
                    $val = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $val);
                    $data[$col] = $val;
                }

                $email = $data['employee_email'];
                $month = $data['payslip_month'];
                $paymentMethod = strtolower($data['payment_method']);
                $companyBankName = $data['company_bank_name'];
                $totalEarning = (float) $data['total_earning'];
                $totalDeduction = (float) $data['total_deduction'];
                $netSalary = (float) $data['net_salary'];

                // Parse and format payslip_month to YYYY-MM-01
                $monthCleaned = trim($month);
                if (preg_match('/^(\d{1,2})[-\/](\d{4})$/', $monthCleaned, $matches)) {
                    $monthCleaned = $matches[2] . '-' . str_pad($matches[1], 2, '0', STR_PAD_LEFT) . '-01';
                } else {
                    $monthCleaned = str_replace('/', '-', $monthCleaned);
                }

                $time = strtotime($monthCleaned);
                if ($time) {
                    $formattedMonth = date('Y-m-01', $time);
                } else {
                    $errors[] = "Row {$rowNum}: Invalid date format for payslip_month: '{$month}'. Please use YYYY-MM-DD (e.g. 2026-06-01).";
                    continue;
                }

                // 1. Match Employee by email in employees table
                $stmt = $this->db->prepare("SELECT id, designation_id FROM employees WHERE email = ? LIMIT 1");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $emp = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$emp) {
                    $errors[] = "Row {$rowNum}: Employee not found with email '{$email}'.";
                    continue;
                }

                // 2. Match Bank if payment method is bank
                $companyBankId = null;
                if ($paymentMethod === 'bank') {
                    if (empty($companyBankName)) {
                        $errors[] = "Row {$rowNum}: Company bank name is required when payment method is 'bank'.";
                        continue;
                    }

                    $bankSearch = "%" . $companyBankName . "%";
                    $stmt = $this->db->prepare("SELECT id FROM company_bank_accounts WHERE bank_name LIKE ? AND status = 'active' LIMIT 1");
                    $stmt->bind_param("s", $bankSearch);
                    $stmt->execute();
                    $bank = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    if (!$bank) {
                        $errors[] = "Row {$rowNum}: Active company bank account not found matching name '{$companyBankName}'.";
                        continue;
                    }
                    $companyBankId = $bank['id'];
                }

                if (!empty($errors)) {
                    continue;
                }

                // 3. Create Payslip (status 'paid')
                $payslipData = [
                    'employee_id' => $emp['id'],
                    'designation_id' => $emp['designation_id'],
                    'payslip_month' => $formattedMonth,
                    'total_earning' => $totalEarning,
                    'total_deduction' => $totalDeduction,
                    'net_salary' => $netSalary,
                    'status' => 'paid',
                    'payment_method' => $paymentMethod,
                    'company_bank_id' => $companyBankId,
                    'upload_by' => 'csv',
                    'created_by' => $_SESSION['user']['id'] ?? 1,
                ];

                try {
                    $payslipId = $payslipModel->create($payslipData);

                    // 4. Save payslip details
                    foreach ($dynamicCols as $idx => $info) {
                        $amount = isset($row[$idx]) ? (float) $row[$idx] : 0.0;
                        if ($amount > 0) {
                            $itemId = $itemIds[$idx];
                            $detailsModel->create($payslipId, $itemId, $amount);
                        }
                    }
                } catch (\Throwable $th) {
                    $errors[] = "Row {$rowNum}: Database insertion failed. Reason: " . $th->getMessage();
                }
            }

            fclose($handle);

            if (!empty($errors)) {
                $this->db->rollback();
                $_SESSION['error'] = implode("<br>", $errors);
                header("Location: " . $GLOBALS['base_url'] . "/import-payslips");
                exit;
            } else {
                $this->db->commit();
                $_SESSION['success'] = "All payslips imported and paid successfully!";
                header("Location: " . $GLOBALS['base_url'] . "/payslip-lists");
                exit;
            }
        } else {
            $_SESSION['error'] = "Failed to open CSV file.";
            header("Location: " . $GLOBALS['base_url'] . "/import-payslips");
            exit;
        }
    }

    public function downloadSampleCSV()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=sample_payslips.csv');

        $output = fopen('php://output', 'w');

        // Load active payslip items to include them as headers
        $items = $this->payslipItemModel->getAll();

        $headers = [
            'employee_email',
            'payslip_month',
            'payment_method',
            'company_bank_name',
            'total_earning',
            'total_deduction',
            'net_salary'
        ];

        foreach ($items as $item) {
            $headers[] = $item['name'] . " (" . $item['type'] . ")";
        }

        fputcsv($output, $headers);

        // Add a sample row
        $sampleRow = [
            'fahim@gmail.com',
            date('Y-m-01'),
            'bank',
            'City Bank',
            '25000.00',
            '1000.00',
            '24000.00'
        ];

        // Fill dynamic columns with sample values matching headers
        foreach ($items as $item) {
            if ($item['type'] === 'earning') {
                $sampleRow[] = '25000.00';
            } else {
                $sampleRow[] = '1000.00';
            }
        }

        fputcsv($output, $sampleRow);
        fclose($output);
        exit;
    }

    public function showAutoGenerateForm()
    {
        $departmentModel = new Department($this->db);
        $departments = $departmentModel->getAll();

        $projectRes = $this->db->query("SELECT * FROM payroll_projects ORDER BY id DESC");
        $payroll_projects = [];
        if ($projectRes) {
            while ($pRow = $projectRes->fetch_assoc()) {
                $payroll_projects[] = $pRow;
            }
        }

        $items = $this->payslipItemModel->getAll();

        $this->view("payrolls/payslips/auto-create-payslip", [
            "current_route" => "auto-generate-payslip",
            "page_title" => "Auto Generate Payslips",
            "departments" => $departments,
            "payroll_projects" => $payroll_projects,
            "items" => $items
        ]);
    }

    public function getDepartmentAttendance()
    {
        header('Content-Type: application/json');

        $departmentId = isset($_GET['department_id']) ? (int) $_GET['department_id'] : 0;
        $projectId = isset($_GET['project_id']) ? (int) $_GET['project_id'] : 0;
        $monthStr = isset($_GET['month']) ? trim($_GET['month']) : ''; // e.g. "2026-07"

        if (!$monthStr) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters. Monthly parameter is required.']);
            exit;
        }

        // 1. Fetch active employees
        $sql = "
            SELECT e.id, e.full_name, e.employee_id, e.email, e.gross_salary, e.basic_salary, e.designation_id, desg.name AS designation_name
            FROM employees e
            LEFT JOIN designations desg ON e.designation_id = desg.id
            WHERE e.employee_status = 'active'
        ";
        if ($departmentId > 0) {
            $sql .= " AND e.department_id = $departmentId";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $employees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Show all active employees (requested to not filter by project anymore)

        // 2. Fetch all configured salary items for these employees
        $configuredSalaryItems = [];
        if (!empty($employees)) {
            $employeeIds = array_column($employees, 'id');
            $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));
            $stmt = $this->db->prepare("
                SELECT * FROM employee_salary_items WHERE employee_id IN ($placeholders)
            ");
            $types = str_repeat('i', count($employeeIds));
            $stmt->bind_param($types, ...$employeeIds);
            $stmt->execute();
            $salaryItemsRaw = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            foreach ($salaryItemsRaw as $sir) {
                $configuredSalaryItems[$sir['employee_id']][$sir['payslip_item_id']] = $sir['amount'];
            }
        }

        // 3. Fetch local attendance from attendance_monthly_update specific to this project
        $localAttMap = [];
        if ($projectId > 0) {
            $stmt = $this->db->prepare("SELECT employee_id, present, absent, leave_days, late, late_unpaid_absent_days FROM attendance_monthly_update WHERE payroll_project_id = ?");
            $stmt->bind_param("i", $projectId);
            $stmt->execute();
            $attRes = $stmt->get_result();
            while ($ar = $attRes->fetch_assoc()) {
                $localAttMap[$ar['employee_id']] = $ar;
            }
            $stmt->close();
        }

        // 4. Calculate absent days for each employee (set to 0 as requested)
        foreach ($employees as &$emp) {
            $empId = $emp['id'];

            $emp['matched_in_tipsoi'] = false;
            $emp['absent_days'] = 0;
            $emp['absent_deduction'] = 0;

            // Pre-fill configured allowances/deductions
            $emp['configured_items'] = $configuredSalaryItems[$emp['id']] ?? [];
        }

        // Advance Salary Integration
        if (!class_exists('AdvanceSalary')) {
            require_once __DIR__ . '/../models/AdvanceSalary.php';
        }
        $advanceSalaryModel = new AdvanceSalary($this->db);
        $allPayslipItems = $this->payslipItemModel->getAll();

        $advanceSalaryItemId = null;
        foreach ($allPayslipItems as $item) {
            if (strtolower(trim($item['name'])) === 'advance salary' && strtolower($item['type']) === 'deduction') {
                $advanceSalaryItemId = $item['id'];
                break;
            }
        }

        if (!$advanceSalaryItemId) {
            $this->payslipItemModel->create(['name' => 'Advance Salary', 'type' => 'deduction']);
            $allPayslipItems = $this->payslipItemModel->getAll();
            foreach ($allPayslipItems as $item) {
                if (strtolower(trim($item['name'])) === 'advance salary' && strtolower($item['type']) === 'deduction') {
                    $advanceSalaryItemId = $item['id'];
                    break;
                }
            }
        }

        foreach ($employees as &$emp) {
            $unadjustedAdvance = $advanceSalaryModel->getMonthlyDeductionAmount($emp['id'], $monthStr);
            if ($unadjustedAdvance > 0 && $advanceSalaryItemId) {
                $emp['configured_items'][$advanceSalaryItemId] = $unadjustedAdvance;
            }
        }

        // 5. Load active payslip items to display as table headers
        $payslipItems = $allPayslipItems;

        echo json_encode([
            'success' => true,
            'employees' => $employees,
            'payslip_items' => $payslipItems
        ]);
        exit;
    }

    public function storeBulkPayslips()
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $input = file_get_contents('php://input');
        $payload = json_decode($input, true);

        if (empty($payload) || empty($payload['employees']) || empty($payload['payslip_month']) || empty($payload['payroll_project_id'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid payslip data. Payroll Project is required.']);
            exit;
        }

        $payslipMonth = $payload['payslip_month'] . '-01'; // Format as YYYY-MM-01
        $payslipDate = !empty($payload['payslip_date']) ? $payload['payslip_date'] : date('Y-m-d');
        $payrollProjectId = (int) $payload['payroll_project_id'];
        $generatedBy = $_SESSION['user']['id'] ?? 1;

        $payslipModel = new Payslip($this->db);
        $detailsModel = new PayslipDetail($this->db);
        $approvalModel = new ApprovalRequest($this->db);

        // Begin Transaction
        $this->db->begin_transaction();

        $generatedCount = 0;
        $skippedCount = 0;
        $skippedNames = [];

        try {
            foreach ($payload['employees'] as $empData) {
                // Double check if employee has already generated payslip for this month and project
                $stmtCheck = $this->db->prepare("
                    SELECT id FROM payslips WHERE employee_id = ? AND DATE_FORMAT(payslip_month, '%Y-%m') = ? AND payroll_project_id = ? LIMIT 1
                ");
                $monthStr = date('Y-m', strtotime($payslipMonth));
                $stmtCheck->bind_param("isi", $empData['employee_id'], $monthStr, $payrollProjectId);
                $stmtCheck->execute();
                $exists = $stmtCheck->get_result()->fetch_assoc();
                $stmtCheck->close();

                if ($exists) {
                    $skippedCount++;
                    $skippedNames[] = $empData['employee_name'];
                    continue;
                }

                // 1. Create Payslip
                $payslipId = $payslipModel->create([
                    'employee_id' => $empData['employee_id'],
                    'payroll_project_id' => $payrollProjectId,
                    'designation_id' => $empData['designation_id'],
                    'payslip_month' => $payslipMonth,
                    'total_earning' => $empData['total_earning'],
                    'total_deduction' => $empData['total_deduction'],
                    'net_salary' => $empData['net_salary'],
                    'status' => 'pending',
                    'upload_by' => 'manual',
                    'created_by' => $generatedBy,
                ]);

                if (!$payslipId) {
                    throw new Exception("Failed to create payslip for " . $empData['employee_name'] . ". DB Error: " . $this->db->error);
                }

                // 2. Insert Earning Details
                if (!empty($empData['earnings'])) {
                    foreach ($empData['earnings'] as $itemId => $amount) {
                        $amt = (float) $amount;
                        if ($amt > 0) {
                            $detailsModel->create($payslipId, $itemId, $amt);
                        }
                    }
                }

                // 3. Insert Deduction Details
                if (!empty($empData['deductions'])) {
                    foreach ($empData['deductions'] as $itemId => $amount) {
                        $amt = (float) $amount;
                        if ($amt > 0) {
                            $detailsModel->create($payslipId, $itemId, $amt);

                            // Mark Advance Salary as deducted
                            $stmtItem = $this->db->prepare("SELECT name FROM payslip_items WHERE id = ?");
                            $stmtItem->bind_param("i", $itemId);
                            $stmtItem->execute();
                            $itemRow = $stmtItem->get_result()->fetch_assoc();
                            $stmtItem->close();

                            if ($itemRow && strtolower(trim($itemRow['name'])) === 'advance salary') {
                                if (!class_exists('AdvanceSalary')) {
                                    require_once __DIR__ . '/../models/AdvanceSalary.php';
                                }
                                $advanceSalaryModel = new AdvanceSalary($this->db);
                                $advanceRequests = $advanceSalaryModel->getPaidAdvancesByEmployee($empData['employee_id']);
                                $remainingDeduction = (float) $amt;
                                foreach ($advanceRequests as $advReq) {
                                    if ($remainingDeduction <= 0)
                                        break;
                                    $advAmount = (float) $advReq['amount'];
                                    $paidBack = (float) ($advReq['paid_back_amount'] ?? 0);
                                    $canDeduct = $advAmount - $paidBack;

                                    if ($canDeduct > 0) {
                                        $toDeduct = min($remainingDeduction, $canDeduct);
                                        $advanceSalaryModel->addDeduction($advReq['id'], $toDeduct, $payslipId);
                                        $remainingDeduction -= $toDeduct;
                                    }
                                }
                            }
                        }
                    }
                }

                // 4. Create Approval Request
                $approvalModel->create([
                    'department' => 'HR',
                    'request_type' => 'salary',
                    'title' => 'Salary Approval',
                    'source_table' => 'payslips',
                    'source_id' => $payslipId,
                    'requested_days' => 0,
                    'requested_by' => $generatedBy,
                    'approve_role' => 'management',
                    'meta_data' => json_encode([
                        'employee_name' => $empData['employee_name'],
                        'net_salary' => $empData['net_salary'],
                        'month' => $payslipMonth
                    ]),
                    'request_amount' => $empData['net_salary']
                ]);

                $generatedCount++;
            }

            // Commit Transaction
            $this->db->commit();

            if ($generatedCount === 0 && $skippedCount > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'No payslips were generated. All selected employees already have payslips generated for this month: ' . implode(', ', $skippedNames)
                ]);
            } else {
                $msg = "Generated $generatedCount payslips successfully and submitted for approval.";
                if ($skippedCount > 0) {
                    $msg .= " Skipped $skippedCount employees who already had payslips for this month: " . implode(', ', $skippedNames);
                }
                echo json_encode(['success' => true, 'message' => $msg]);
            }
            exit;

        } catch (Exception $e) {
            $this->db->rollback();
            echo json_encode(['success' => false, 'message' => 'Failed to generate payslips: ' . $e->getMessage()]);
            exit;
        }
    }

    public function disburseBatch()
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $input = file_get_contents('php://input');
        $payload = json_decode($input, true);

        if (empty($payload) || empty($payload['payslip_ids']) || !is_array($payload['payslip_ids'])) {
            echo json_encode(['success' => false, 'message' => 'No payslips selected for disbursement.']);
            exit;
        }

        $bankAccountId = (int) ($payload['bank_account_id'] ?? 0);
        $payslipIds = array_map('intval', $payload['payslip_ids']);
        $userId = $_SESSION['user']['id'] ?? 1;

        if (empty($payslipIds)) {
            echo json_encode(['success' => false, 'message' => 'Invalid payslips selected.']);
            exit;
        }

        $this->db->begin_transaction();

        try {
            // Fetch payslips to disburse
            $placeholders = implode(',', array_fill(0, count($payslipIds), '?'));
            $stmt = $this->db->prepare("
                SELECT p.*, e.full_name as employee_name
                FROM payslips p
                LEFT JOIN employees e ON p.employee_id = e.id
                WHERE p.id IN ($placeholders) AND p.status != 'paid'
            ");
            $types = str_repeat('i', count($payslipIds));
            $stmt->bind_param($types, ...$payslipIds);
            $stmt->execute();
            $payslipsToPay = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            if (empty($payslipsToPay)) {
                echo json_encode(['success' => false, 'message' => 'Selected payslips are already paid or invalid.']);
                exit;
            }

            // Fetch dynamic COA IDs for Salaries & Wages and Bank/Cash
            $stmtCoa1 = $this->db->prepare("SELECT id FROM chart_of_accounts WHERE account_code = '580003' LIMIT 1");
            $stmtCoa1->execute();
            $salaryCoaRow = $stmtCoa1->get_result()->fetch_assoc();
            $salaryCoaId = $salaryCoaRow ? (int) $salaryCoaRow['id'] : 1;
            $stmtCoa1->close();

            $stmtCoa2 = $this->db->prepare("SELECT id FROM chart_of_accounts WHERE account_code = '114400' LIMIT 1");
            $stmtCoa2->execute();
            $bankCoaRow = $stmtCoa2->get_result()->fetch_assoc();
            $bankCoaId = $bankCoaRow ? (int) $bankCoaRow['id'] : 1;
            $stmtCoa2->close();

            $stmtCoa3 = $this->db->prepare("SELECT id FROM chart_of_accounts WHERE account_code = '114100-03' LIMIT 1");
            $stmtCoa3->execute();
            $cashCoaRow = $stmtCoa3->get_result()->fetch_assoc();
            $cashCoaId = $cashCoaRow ? (int) $cashCoaRow['id'] : $bankCoaId;
            $stmtCoa3->close();

            // Get bank details or default cash
            $bankName = 'Head Office Cash Account';
            $payCoaId = $cashCoaId;
            if ($bankAccountId > 0) {
                $stmtBank = $this->db->prepare("SELECT bank_name, account_name, account_number FROM company_bank_accounts WHERE id = ?");
                $stmtBank->bind_param("i", $bankAccountId);
                $stmtBank->execute();
                $bankRes = $stmtBank->get_result()->fetch_assoc();
                $stmtBank->close();
                if ($bankRes) {
                    $bankName = $bankRes['bank_name'] . ' (' . $bankRes['account_number'] . ')';
                    $payCoaId = $bankCoaId;
                }
            }

            // Calculate total net salary sum & determine month
            $totalAmount = 0;
            $monthText = date('F Y');
            $sampleDate = date('Y-m-d');
            foreach ($payslipsToPay as $p) {
                $totalAmount += (float) $p['net_salary'];
                if (!empty($p['payslip_month'])) {
                    $monthText = date('F Y', strtotime($p['payslip_month']));
                    $sampleDate = date('Y-m-t', strtotime($p['payslip_month']));
                }
            }

            // 1. Create 1 Batch Journal Entry in 'journals'
            $journalNum = 'JRN-PAYROLL-' . date('Ym', strtotime($sampleDate)) . '-' . rand(100, 999);
            $narration = "Payroll Disbursement for $monthText via $bankName (Count: " . count($payslipsToPay) . " payslips)";

            $stmtJ = $this->db->prepare("
                INSERT INTO journals 
                (journal_number, date, voucher_type, narration, total_debit, total_credit, status, created_by, created_at, source_module, source_ref_id, entry_type, approval_status)
                VALUES (?, ?, ?, ?, ?, ?, 'posted', ?, NOW(), 'payroll', 0, 'disbursement', 'approved')
            ");
            $voucherType = 'Payment';
            $stmtJ->bind_param("ssssddi", $journalNum, $sampleDate, $voucherType, $narration, $totalAmount, $totalAmount, $userId);
            $stmtJ->execute();
            $journalId = $stmtJ->insert_id;
            $stmtJ->close();

            // 2. Insert Debit & Credit details in 'journal_details'
            // Debit: Salaries & Wages (580003)
            $stmtD1 = $this->db->prepare("
                INSERT INTO journal_details (journal_id, account_id, debit, credit, narration)
                VALUES (?, ?, ?, 0.00, ?)
            ");
            $narrDebit = "Salaries & Wages Expense for $monthText";
            $stmtD1->bind_param("iids", $journalId, $salaryCoaId, $totalAmount, $narrDebit);
            $stmtD1->execute();
            $stmtD1->close();

            // Credit: Cash at Bank (114400) or Head Office Cash (114100-03)
            $stmtD2 = $this->db->prepare("
                INSERT INTO journal_details (journal_id, account_id, debit, credit, narration)
                VALUES (?, ?, 0.00, ?, ?)
            ");
            $narrCredit = "Disbursed via $bankName for $monthText Payroll";
            $stmtD2->bind_param("iids", $journalId, $payCoaId, $totalAmount, $narrCredit);
            $stmtD2->execute();
            $stmtD2->close();

            // 3. Update payslips status to PAID & link journal_voucher_id
            $stmtUpd = $this->db->prepare("
                UPDATE payslips 
                SET status = 'paid', paid_via_account_id = ?, disbursed_by = ?, disbursed_at = NOW(), journal_id = ?
                    bank_account_id = ?, disbursed_by = ?, disbursed_at = NOW(), journal_voucher_id = ?
                WHERE id = ?
            ");
            foreach ($payslipsToPay as $p) {
                $stmtUpd->bind_param("iiii", $bankAccountId, $userId, $journalId, $p['id']);
                $stmtUpd->execute();
            }
            $stmtUpd->close();

            $this->db->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Successfully disbursed ' . count($payslipsToPay) . ' payslips (Total: ৳' . number_format($totalAmount, 2) . ') and posted Journal Entry (' . $journalNum . ').',
                'journal_number' => $journalNum,
                'disbursed_count' => count($payslipsToPay),
                'total_amount' => $totalAmount
            ]);
            exit;

        } catch (Exception $e) {
            $this->db->rollback();
            echo json_encode(['success' => false, 'message' => 'Disbursement failed: ' . $e->getMessage()]);
            exit;
        }
    }

    public function workspace()
    {
        // Current month/year
        $currentYear = date('Y');
        $currentMonth = date('m');

        // GET param use, fallback to current month/year
        $selectedYear = isset($_GET['year']) ? $_GET['year'] : $currentYear;
        $selectedMonth = isset($_GET['month']) ? $_GET['month'] : $currentMonth;
        $selectedProject = isset($_GET['payroll_project_id']) ? $_GET['payroll_project_id'] : '';

        // Fetch dynamic payslip items
        $items = $this->payslipItemModel->getAll(); // returns id, name, type etc.

        $projectRes = $this->db->query("SELECT * FROM payroll_projects ORDER BY id DESC");
        $payroll_projects = [];
        if ($projectRes) {
            while ($pRow = $projectRes->fetch_assoc()) {
                $payroll_projects[] = $pRow;
            }
        }

        // Build an exhaustive query joining payslips, employees, departments, designations
        $query = "
        SELECT p.*, e.id as real_emp_id_fk,
               e.full_name, e.employee_id as emp_id, e.email, e.workplace, 
               e.joining_date, e.account_holder_name, e.account_number, e.bank_name,
               e.gross_salary as emp_gross, e.basic_salary as emp_basic,
               e.deleted_at,
               e.per_meal_amount,
               dept.name as department_name, desg.name as designation_name,
               override_att.tipsoi_present, override_att.tipsoi_absent, override_att.tipsoi_leave, override_att.tipsoi_late, override_att.late_unpaid_absent_days
        FROM employees e
        LEFT JOIN departments dept ON e.department_id = dept.id
        LEFT JOIN designations desg ON e.designation_id = desg.id
        LEFT JOIN payslips p ON e.id = p.employee_id 
            " . ($selectedYear && $selectedMonth ? " AND YEAR(p.payslip_month) = '$selectedYear' AND MONTH(p.payslip_month) = '$selectedMonth'" : "") . "
            " . ($selectedProject ? " AND p.payroll_project_id = " . (int) $selectedProject : "") . "
        LEFT JOIN (
            SELECT employee_id, present as tipsoi_present, absent as tipsoi_absent, leave_days as tipsoi_leave, late as tipsoi_late, late_unpaid_absent_days 
            FROM attendance_monthly_update
            WHERE payroll_project_id = " . ((int) $selectedProject ?: '0') . "
        ) override_att ON e.id = override_att.employee_id
        WHERE e.employee_status = 'active'
        ORDER BY e.employee_id ASC 
    ";

        $res = $this->db->query($query);
        $payslips = [];
        if ($res) {
            // Pre-fetch employee_salary_items for initial fallback
            $configuredItems = [];
            $esQuery = $this->db->query("SELECT employee_id, payslip_item_id, amount FROM employee_salary_items");
            if ($esQuery) {
                while ($esRow = $esQuery->fetch_assoc()) {
                    $configuredItems[$esRow['employee_id']][$esRow['payslip_item_id']] = (float) $esRow['amount'];
                }
            }

            if (!class_exists('AdvanceSalary')) {
                require_once __DIR__ . '/../models/AdvanceSalary.php';
            }
            $advanceSalaryModel = new AdvanceSalary($this->db);
            $advanceSalaryItemId = null;
            foreach ($items as $i) {
                if (strtolower(trim($i['name'])) === 'advance salary') {
                    $advanceSalaryItemId = $i['id'];
                    break;
                }
            }
            $monthStr = $selectedYear && $selectedMonth ? $selectedYear . '-' . str_pad($selectedMonth, 2, "0", STR_PAD_LEFT) : date('Y-m');

            while ($row = $res->fetch_assoc()) {
                $pid = (int) $row['id'];
                $empId = (int) $row['real_emp_id_fk'];

                $details = [];
                if ($pid > 0) {
                    $detQuery = "
                        SELECT payslip_item_id, amount 
                        FROM payslip_details 
                        WHERE payslip_id = $pid
                    ";
                    $detRes = $this->db->query($detQuery);
                    if ($detRes) {
                        while ($drow = $detRes->fetch_assoc()) {
                            $details[$drow['payslip_item_id']] = (float) $drow['amount'];
                        }
                    }
                } else {
                    // Fallback to configured defaults if payslip is not yet saved
                    if (isset($configuredItems[$empId])) {
                        foreach ($configuredItems[$empId] as $itemid => $amt) {
                            $details[$itemid] = $amt;
                        }
                    }

                    if ($advanceSalaryItemId) {
                        $monthlyAdvance = $advanceSalaryModel->getMonthlyDeductionAmount($empId, $monthStr);
                        if ($monthlyAdvance > 0) {
                            $details[$advanceSalaryItemId] = $monthlyAdvance;
                        }
                    }
                }

                $row['dynamic_details'] = $details;
                $payslips[] = $row;
            }
        }

        $this->view("payrolls/payslips/workspace", [
            "current_route" => "payroll-workspace",
            "page_title" => "Payroll Workplace",
            "payslips" => $payslips,
            "items" => $items,
            "selectedYear" => $selectedYear,
            "selectedMonth" => $selectedMonth,
            "payroll_projects" => $payroll_projects
        ]);
    }

    public function storePayrollProject()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = isset($_POST['name']) ? $this->db->real_escape_string($_POST['name']) : '';
            $date = isset($_POST['date']) ? $this->db->real_escape_string($_POST['date']) : '';

            if (empty($name) || empty($date)) {
                echo "<script>alert('Project name and date are required.'); window.location.href='{$GLOBALS['base_url']}/payroll-workspace';</script>";
                exit;
            }

            $stmt = $this->db->prepare("INSERT INTO payroll_projects (name, date) VALUES (?, ?)");
            
            if (!$stmt) {
                echo "<script>alert('Database error: Failed to prepare statement.'); window.location.href='{$GLOBALS['base_url']}/payroll-workspace';</script>";
                exit;
            }

            $stmt->bind_param("ss", $name, $date);

            if ($stmt->execute()) {
                echo "<script>window.location.href='{$GLOBALS['base_url']}/payroll-workspace';</script>";
            } else {
                echo "<script>alert('Failed to create project.'); window.location.href='{$GLOBALS['base_url']}/payroll-workspace';</script>";
            }
            $stmt->close();
        }
    }

    public function salaryHold()
    {
        $currentYear = date('Y');
        $currentMonth = date('m');

        $selectedYear = (!empty($_GET['year'])) ? $_GET['year'] : $currentYear;
        $selectedMonth = (!empty($_GET['month'])) ? $_GET['month'] : $currentMonth;

        $query = "
            SELECT p.id, p.payslip_month, p.net_salary, p.status,
                   e.full_name, e.employee_id as emp_id
            FROM payslips p
            LEFT JOIN employees e ON p.employee_id = e.id
            WHERE e.employee_status = 'active' 
              AND YEAR(p.payslip_month) = '$selectedYear' 
              AND MONTH(p.payslip_month) = '$selectedMonth'
              AND p.status = 'on_hold'
            ORDER BY e.employee_id ASC
        ";

        $res = $this->db->query($query);
        $holds = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $holds[] = $row;
            }
        }

        $this->view("payrolls/payslips/salary-hold", [
            "current_route" => "payroll-salary-hold",
            "page_title" => "Salary Hold",
            "holds" => $holds,
            "selectedYear" => $selectedYear,
            "selectedMonth" => $selectedMonth
        ]);
    }

    public function exportSalaryHoldPDF()
    {
        $currentYear = date('Y');
        $currentMonth = date('m');

        $selectedYear = (!empty($_GET['year'])) ? $_GET['year'] : $currentYear;
        $selectedMonth = (!empty($_GET['month'])) ? $_GET['month'] : $currentMonth;

        $query = "
            SELECT p.id, p.payslip_month, p.net_salary, p.status,
                   e.full_name, e.employee_id as emp_id
            FROM payslips p
            LEFT JOIN employees e ON p.employee_id = e.id
            WHERE e.employee_status = 'active'
              AND YEAR(p.payslip_month) = '$selectedYear' 
              AND MONTH(p.payslip_month) = '$selectedMonth'
              AND p.status = 'on_hold'
            ORDER BY e.employee_id ASC
        ";

        $res = $this->db->query($query);
        $holds = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $holds[] = $row;
            }
        }

        $pdf = new \TCPDF('L', 'mm', 'A4');
        $pdf->SetCreator('TPHL ERP');
        $pdf->SetAuthor('Admin');
        $pdf->SetTitle('Salary Hold Report');
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', '', 10);

        $monthName = date('F', mktime(0, 0, 0, $selectedMonth, 1));

        $html = '
        <div style="text-align:center;">
            <h1 style="color:#078134;">The Premium Homes Ltd</h1>
            <h3 style="color:#28B463;">Salary Hold Report (' . $monthName . ' ' . $selectedYear . ')</h3>
            <p><b>Total Held: </b> ' . count($holds) . '</p>
        </div>
        <br>
        <table border="1" cellpadding="5" width="100%">
            <thead>
                <tr style="background-color:#078134; color:#ffffff; text-align:center; font-weight:bold;">
                    <th>Serial</th>
                    <th>Name</th>
                    <th>Employee ID</th>
                    <th>Amount</th>
                    <th>Remark</th>
                </tr>
            </thead>
            <tbody>';

        $sl = 1;
        if (empty($holds)) {
            $html .= '<tr><td colspan="5" style="text-align:center;"><b>No salary holds found for this period.</b></td></tr>';
        }
        foreach ($holds as $hold) {
            $html .= '<tr style="text-align:center;">
                <td>' . $sl++ . '</td>
                <td>' . htmlspecialchars($hold['full_name'] ?? '') . '</td>
                <td>' . htmlspecialchars($hold['emp_id'] ?? '') . '</td>
                <td>' . number_format($hold['net_salary'] ?? 0, 2) . '</td>
                <td>' . htmlspecialchars($hold['remarks'] ?? '') . '</td>
            </tr>';
        }

        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        ob_end_clean();
        $pdf->Output('Salary_Hold_Report_' . $monthName . '_' . $selectedYear . '.pdf', 'D');
        exit;
    }

    public function exportWorkspacePDF()
    {
        $currentYear = date('Y');
        $currentMonth = date('m');

        $selectedYear = (!empty($_GET['year'])) ? $_GET['year'] : $currentYear;
        $selectedMonth = (!empty($_GET['month'])) ? $_GET['month'] : $currentMonth;

        $monthName = date('F', mktime(0, 0, 0, $selectedMonth, 1));

        // Setup PDF Generator
        $pdf = new \TCPDF('L', 'mm', 'A2');
        $pdf->SetCreator('TPHL ERP');
        $pdf->SetAuthor('Admin');
        $pdf->SetTitle('Payroll Workplace Report');
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(TRUE, 10);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 7);

        // Check if custom POST data is set from the client-side
        if (!empty($_POST['table_data']) && !empty($_POST['table_headers'])) {
            $tableData = json_decode($_POST['table_data'], true) ?: [];
            $tableHeaders = json_decode($_POST['table_headers'], true) ?: [];

            $html = '
            <div style="text-align:center;">
                <h1 style="color:#078134;">The Premium Homes Ltd</h1>
                <h3 style="color:#28B463;">Payroll Workplace Report (' . $monthName . ' ' . $selectedYear . ')</h3>
                <p style="font-size:14px;"><b>Total Employees: </b> ' . count($tableData) . '</p>
            </div>
            <br>
            <table border="1" cellpadding="3" width="100%">
                <thead>
                    <tr style="background-color:#078134; color:#ffffff; text-align:center; font-weight:bold;">';

            foreach ($tableHeaders as $th) {
                $html .= '<th>' . htmlspecialchars($th) . '</th>';
            }
            $html .= '</tr></thead><tbody>';

            if (empty($tableData)) {
                $html .= '<tr><td colspan="' . count($tableHeaders) . '" style="text-align:center;"><b>No payslips found for this period.</b></td></tr>';
            } else {
                foreach ($tableData as $row) {
                    $html .= '<tr style="text-align:center;">';
                    foreach ($row as $cell) {
                        $html .= '<td>' . htmlspecialchars($cell ?? '') . '</td>';
                    }
                    $html .= '</tr>';
                }
            }
            $html .= '</tbody></table>';

            $pdf->writeHTML($html, true, false, true, false, '');
            ob_end_clean();
            $pdf->Output('Payroll_Workspace_Report_' . $monthName . '_' . $selectedYear . '.pdf', 'D');
            exit;
        }

        // ORIGINAL LOGIC FALLBACK
        $items = $this->payslipItemModel->getAll();

        $query = "
            SELECT p.*, 
                   e.full_name, e.employee_id as emp_id, e.email, e.workplace, 
                   e.joining_date, e.account_holder_name, e.account_number, e.bank_name,
                   e.gross_salary as emp_gross, e.basic_salary as emp_basic,
                   e.deleted_at,
                   e.per_meal_amount,
                   dept.name as department_name, desg.name as designation_name
            FROM payslips p
            LEFT JOIN employees e ON p.employee_id = e.id
            LEFT JOIN departments dept ON e.department_id = dept.id
            LEFT JOIN designations desg ON e.designation_id = desg.id
            WHERE e.employee_status = 'active' AND YEAR(p.payslip_month) = '$selectedYear' AND MONTH(p.payslip_month) = '$selectedMonth'
            ORDER BY e.employee_id ASC
        ";

        $res = $this->db->query($query);
        $payslips = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $pid = (int) $row['id'];
                $detQuery = "SELECT payslip_item_id, amount FROM payslip_details WHERE payslip_id = $pid";
                $detRes = $this->db->query($detQuery);
                $details = [];
                if ($detRes) {
                    while ($drow = $detRes->fetch_assoc()) {
                        $details[$drow['payslip_item_id']] = (float) $drow['amount'];
                    }
                }
                $row['dynamic_details'] = $details;
                $payslips[] = $row;
            }
        }

        $html = '
        <div style="text-align:center;">
            <h1 style="color:#078134;">The Premium Homes Ltd</h1>
            <h3 style="color:#28B463;">Payroll Workplace Report (' . $monthName . ' ' . $selectedYear . ')</h3>
            <p style="font-size:14px;"><b>Total Employees: </b> ' . count($payslips) . '</p>
        </div>
        <br>
        <table border="1" cellpadding="3" width="100%">
            <thead>
                <tr style="background-color:#078134; color:#ffffff; text-align:center; font-weight:bold;">
                    <th>SL</th>
                    <th>Department</th>
                    <th>Name</th>
                    <th>ID</th>
                    <th>Workplace</th>
                    <th>Designation</th>
                    <th>Basic Salary</th>';

        foreach ($items as $item) {
            if ($item['type'] === 'earning') {
                $html .= '<th>' . htmlspecialchars($item['name']) . '</th>';
            }
        }

        $html .= '
                    <th>Gross</th>
                    <th>Daily Salary</th>
                    <th>Advance Salary</th>
                    <th>Meal Count</th>
                    <th>Sub Lunch</th>
                    <th>L/U/A (Days)</th>
                    <th>L/U/A (Amt)</th>';

        foreach ($items as $item) {
            if ($item['type'] === 'deduction') {
                $lname = strtolower($item['name']);
                if (strpos($lname, 'advance') === false && strpos($lname, 'lunch') === false && strpos($lname, 'absent') === false) {
                    $html .= '<th>' . htmlspecialchars($item['name']) . '</th>';
                }
            }
        }

        $html .= '
                    <th>Punctuality Bonus</th>
                    <th>Overtime</th>
                    <th>Arrear</th>
                    <th>Other Allow</th>
                    <th>Joining Date</th>
                    <th>Resign Date</th>
                    <th>Eff. Days</th>
                    <th>Net Salary</th>
                    <th>Tax</th>
                    <th>Net Payable</th>
                    <th>Bank</th>
                    <th>Cash</th>
                    <th>A/C Name</th>
                    <th>A/C No</th>
                    <th>Amount</th>
                    <th>ID Check</th>
                </tr>
            </thead>
            <tbody>';

        $sl = 1;
        if (empty($payslips)) {
            $html .= '<tr><td colspan="30" style="text-align:center;"><b>No payslips found for this period.</b></td></tr>';
        }
        foreach ($payslips as $p) {
            $gross = (float) $p['emp_gross'];
            $basic = (float) $p['emp_basic'];
            $daily = $gross / 30;
            $netSalary = (float) $p['net_salary'];
            $paymentMethod = strtolower($p['payment_method']);
            $bank = ($paymentMethod == 'bank' || $paymentMethod == 'bank transfer') ? $netSalary : 0;
            $cash = ($paymentMethod == 'cash') ? $netSalary : 0;

            $advanceSalary = 0;
            $mealCount = 0;
            $subsidizeLunch = 0;
            $unpaidAbsents = 0;
            $punctBonus = 0;
            $overtime = 0;
            $arrear = 0;
            $tax = 0;
            $otherAllow = 0;

            foreach ($items as $i) {
                $amt = isset($p['dynamic_details'][$i['id']]) ? (float) $p['dynamic_details'][$i['id']] : 0;
                $lname = strtolower($i['name']);
                if (strpos($lname, 'advance') !== false) {
                    $advanceSalary += $amt;
                }
                if (strpos($lname, 'lunch') !== false || strpos($lname, 'meal') !== false) {
                    $subsidizeLunch += $amt;
                    $perMeal = (float) ($p['per_meal_amount'] ?? 0);
                    if ($perMeal > 0 && $amt > 0) {
                        $mealCount = $amt / $perMeal;
                    }
                }
                if (strpos($lname, 'lwp') !== false || strpos($lname, 'absent') !== false) {
                    $unpaidAbsents += $amt;
                }
                if (strpos($lname, 'punctuality') !== false) {
                    $punctBonus += $amt;
                }
                if (strpos($lname, 'overtime') !== false) {
                    $overtime += $amt;
                }
                if (strpos($lname, 'arrear') !== false) {
                    $arrear += $amt;
                }
                if (strpos($lname, 'tax') !== false) {
                    $tax += $amt;
                }
                if (strpos($lname, 'duty') !== false || strpos($lname, 'food') !== false || strpos($lname, 'car') !== false) {
                    $otherAllow += $amt;
                }
            }

            $html .= '<tr style="text-align:center;">
                <td>' . $sl++ . '</td>
                <td>' . htmlspecialchars($p['department_name'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($p['full_name']) . '</td>
                <td>' . htmlspecialchars($p['emp_id']) . '</td>
                <td>' . htmlspecialchars($p['workplace'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($p['designation_name'] ?? 'N/A') . '</td>
                <td>' . number_format($basic, 2) . '</td>';

            foreach ($items as $item) {
                if ($item['type'] === 'earning') {
                    $val = 0;
                    $nameStr = strtolower($item['name']);
                    if (strpos($nameStr, 'recreation') !== false) {
                        $val = round($basic * 0.10, 2);
                    } elseif (strpos($nameStr, 'conveyance') !== false) {
                        $val = round($basic * 0.10, 2);
                    } elseif (strpos($nameStr, 'medical') !== false) {
                        $val = round($basic * 0.30, 2);
                    } elseif (strpos($nameStr, 'house rent') !== false) {
                        $val = round($basic * 0.50, 2);
                    } else {
                        $val = $p['dynamic_details'][$item['id']] ?? 0;
                    }
                    $html .= '<td>' . number_format($val, 2) . '</td>';
                }
            }

            $html .= '
                <td>' . number_format($gross, 2) . '</td>
                <td>' . number_format($daily, 2) . '</td>
                <td>' . number_format($advanceSalary, 2) . '</td>
                <td>' . $mealCount . '</td>
                <td>' . number_format($subsidizeLunch, 2) . '</td>
                <td>0</td>
                <td>' . number_format($unpaidAbsents, 2) . '</td>';

            foreach ($items as $item) {
                if ($item['type'] === 'deduction') {
                    $lname = strtolower($item['name']);
                    if (strpos($lname, 'advance') === false && strpos($lname, 'lunch') === false && strpos($lname, 'absent') === false) {
                        $html .= '<td>' . number_format($p['dynamic_details'][$item['id']] ?? 0, 2) . '</td>';
                    }
                }
            }

            $joinDate = !empty($p['joining_date']) ? date('d-M-y', strtotime($p['joining_date'])) : '';
            $resignDate = !empty($p['deleted_at']) ? date('d-M-y', strtotime($p['deleted_at'])) : '';

            $html .= '
                <td>' . ($punctBonus > 0 ? number_format($punctBonus, 2) : '0.00') . '</td>
                <td>' . ($overtime > 0 ? number_format($overtime, 2) : '0.00') . '</td>
                <td>' . ($arrear > 0 ? number_format($arrear, 2) : '0.00') . '</td>
                <td>' . ($otherAllow > 0 ? number_format($otherAllow, 2) : '0.00') . '</td>
                <td>' . ($joinDate ?: '&nbsp;') . '</td>
                <td>' . ($resignDate ?: '&nbsp;') . '</td>
                <td>30</td>
                <td>' . number_format($netSalary, 2) . '</td>
                <td>' . number_format($tax, 2) . '</td>
                <td>' . number_format($netSalary, 2) . '</td>
                <td>' . number_format($bank, 2) . '</td>
                <td>' . number_format($cash, 2) . '</td>
                <td>' . (!empty($p['account_holder_name']) ? htmlspecialchars($p['account_holder_name']) : '&nbsp;') . '</td>
                <td>' . (!empty($p['account_number']) ? htmlspecialchars($p['account_number']) : '&nbsp;') . '</td>
                <td>' . number_format($bank, 2) . '</td>
                <td>&nbsp;</td>
            </tr>';
        }

        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        ob_end_clean();
        $pdf->Output('Payroll_Workspace_Report_' . $monthName . '_' . $selectedYear . '.pdf', 'D');
        exit;
    }

    public function editPayrollProject($id)
    {
        $id = (int) $id;
        $projectRes = $this->db->query("SELECT * FROM payroll_projects WHERE id = $id");
        $project = $projectRes->fetch_assoc();

        if (!$project) {
            echo "Project not found.";
            exit;
        }

        $this->view('payrolls/payslips/edit-payroll-project', [
            'current_route' => 'payroll-project-list',
            'page_title' => 'Edit Payroll Project',
            'project' => $project
        ]);
    }

    public function saveWorkspacePayslips()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid method.']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || empty($input['project_id']) || empty($input['rows'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid data payload.']);
            exit;
        }

        $projectId = (int) $input['project_id'];
        $year = (int) ($input['year'] ?? 0);
        $month = (int) ($input['month'] ?? 0);

        // Strict fallback to project's attached database date if UI failed to convey valid timeframe
        if ($year < 2000 || $month < 1 || $month > 12) {
            $projRes = $this->db->query("SELECT date FROM payroll_projects WHERE id = " . $projectId);
            if ($projRes && $pRow = $projRes->fetch_assoc()) {
                if (!empty($pRow['date'])) {
                    $parts = explode('-', $pRow['date']);
                    if (count($parts) >= 2) {
                        $year = (int) $parts[0];
                        $month = (int) $parts[1];
                    }
                }
            }
            if ($year < 2000)
                $year = (int) date('Y');
            if ($month < 1 || $month > 12)
                $month = (int) date('m');
        }

        $payslipMonth = sprintf('%04d-%02d-01', $year, $month);

        $this->db->begin_transaction();
        try {
            foreach ($input['rows'] as $row) {
                $payslipId = (int) ($row['payslip_id'] ?? 0);
                $employeeId = (int) $row['employee_id'];
                $netSalary = (float) $row['net_salary'];
                $totalEarning = (float) $row['total_earning'];
                $totalDeduction = (float) $row['total_deduction'];
                $salaryHoldString = trim((string) ($row['salary_hold'] ?? ''));
                $bankAmount = (float) ($row['bank_amount'] ?? 0);
                $cashAmount = (float) ($row['cash_amount'] ?? 0);
                $lateUnpaidAbsentDays = (float) ($row['late_unpaid_absent_days'] ?? 0);
                $remarks = trim((string) ($row['remarks'] ?? ''));
                $attRemarks = trim((string) ($row['attendance_remarks'] ?? ''));
                $isExcluded = isset($row['is_excluded']) ? (int) $row['is_excluded'] : 0;
                $details = $row['details'] ?? [];

                if ($payslipId > 0) {
                    $stmt = $this->db->prepare("UPDATE payslips SET total_earning=?, total_deduction=?, net_salary=?, salary_hold=?, bank_amount=?, cash_amount=?, late_unpaid_absent_days=?, remarks=?, attendance_remarks=?, is_excluded=? WHERE id=?");
                    if ($stmt) {
                        $stmt->bind_param("dddsdddssii", $totalEarning, $totalDeduction, $netSalary, $salaryHoldString, $bankAmount, $cashAmount, $lateUnpaidAbsentDays, $remarks, $attRemarks, $isExcluded, $payslipId);
                        $stmt->execute();
                        $stmt->close();
                    } else {
                        throw new Exception("Payslips update failed: " . $this->db->error);
                    }
                } else {
                    $stmt = $this->db->prepare("INSERT INTO payslips (employee_id, payroll_project_id, payslip_month, total_earning, total_deduction, net_salary, salary_hold, bank_amount, cash_amount, late_unpaid_absent_days, remarks, attendance_remarks, is_excluded) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt) {
                        $stmt->bind_param("iisdddsdddssi", $employeeId, $projectId, $payslipMonth, $totalEarning, $totalDeduction, $netSalary, $salaryHoldString, $bankAmount, $cashAmount, $lateUnpaidAbsentDays, $remarks, $attRemarks, $isExcluded);
                        $stmt->execute();
                        $payslipId = $stmt->insert_id;
                        $stmt->close();
                    } else {
                        throw new Exception("Payslips insert failed: " . $this->db->error);
                    }
                }

                if ($payslipId > 0) {
                    $this->db->query("DELETE FROM payslip_details WHERE payslip_id = $payslipId");
                    $detStmt = $this->db->prepare("INSERT INTO payslip_details (payslip_id, payslip_item_id, amount) VALUES (?, ?, ?)");
                    if ($detStmt) {
                        foreach ($details as $itemId => $amt) {
                            $amt = (float) $amt;
                            if ($amt > 0) {
                                $iId = (int) $itemId;
                                $detStmt->bind_param("iid", $payslipId, $iId, $amt);
                                $detStmt->execute();
                            }
                        }
                        $detStmt->close();
                    } else {
                        throw new Exception("Payslip details insert failed: " . $this->db->error);
                    }
                }
            }
            $this->db->commit();
            echo json_encode(['success' => true, 'message' => 'Successfully saved payslips.']);
        } catch (Exception $e) {
            $this->db->rollback();
            echo json_encode(['success' => false, 'message' => 'Operation failed: ' . $e->getMessage()]);
        }
        exit;
    }

    public function attendanceReport()
    {
        $projectRes = $this->db->query("SELECT * FROM payroll_projects ORDER BY id DESC");
        $payroll_projects = [];
        if ($projectRes) {
            while ($pRow = $projectRes->fetch_assoc()) {
                $payroll_projects[] = $pRow;
            }
        }

        $this->view("payrolls/payslips/attendance-report", [
            "current_route" => "payroll-attendance-report",
            "page_title" => "Payroll Attendance Report",
            "payroll_projects" => $payroll_projects
        ]);
    }

    public function getAttendanceOverrides()
    {
        $project_id = isset($_GET['project_id']) ? (int) $_GET['project_id'] : 0;

        $query = "SELECT * FROM attendance_monthly_update WHERE payroll_project_id = $project_id";
        $res = $this->db->query($query);

        $data = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                // Front-end expects it mapped by employee_id !
                $data[$row['employee_id']] = $row;
            }
        }

        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    public function saveAttendanceModifications()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || empty($input['rows'])) {
            echo json_encode(['success' => false, 'message' => 'No payload received.']);
            exit;
        }

        $projectId = (int) ($input['project_id'] ?? 0);
        if (!$projectId) {
            echo json_encode(['success' => false, 'message' => 'Payroll Project ID is required.']);
            exit;
        }

        $targetMonth = $input['target_month'] ?? date('Y-m-d');

        $this->db->begin_transaction();
        try {
            $successCount = 0;

            $stmt = $this->db->prepare("
                INSERT INTO attendance_monthly_update 
                (employee_id, payroll_project_id, working_days, present, absent, leave_days, dayoff, late, approved_lates, final_late, deduction, late_unpaid_absent_days) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                working_days = VALUES(working_days),
                present = VALUES(present),
                absent = VALUES(absent),
                leave_days = VALUES(leave_days),
                dayoff = VALUES(dayoff),
                late = VALUES(late),
                approved_lates = VALUES(approved_lates),
                final_late = VALUES(final_late),
                deduction = VALUES(deduction),
                late_unpaid_absent_days = VALUES(late_unpaid_absent_days)
            ");

            if (!$stmt) {
                throw new Exception("SQL Prepare failed: " . $this->db->error);
            }

            foreach ($input['rows'] as $row) {
                $empId = (int) $row['employee_id'];
                $working_days = (float) ($row['working_days'] ?? 0);
                $present = (float) ($row['present'] ?? 0);
                $absent = (float) ($row['absent'] ?? 0);
                $leave = (float) ($row['leave'] ?? 0);
                $dayoff = (float) ($row['dayoff'] ?? 0);
                $late = (float) ($row['late'] ?? 0);
                $approved_lates = (float) ($row['approved_lates'] ?? 0);
                $final_late = (float) ($row['final_late'] ?? 0);
                $deduction = (float) ($row['deduction'] ?? 0);
                $late_unpaid_absent_days = (float) ($row['late_unpaid_absent_days'] ?? 0);

                $stmt->bind_param(
                    "iidddddddddd",
                    $empId,
                    $projectId,
                    $working_days,
                    $present,
                    $absent,
                    $leave,
                    $dayoff,
                    $late,
                    $approved_lates,
                    $final_late,
                    $deduction,
                    $late_unpaid_absent_days
                );

                if ($stmt->execute()) {
                    $successCount++;
                }
            }
            $stmt->close();
            $this->db->commit();

            echo json_encode([
                'success' => true,
                'message' => "Successfully overlayed $successCount records."
            ]);
        } catch (Exception $e) {
            $this->db->rollback();
            echo json_encode(['success' => false, 'message' => 'Operation failed: ' . $e->getMessage()]);
        }
        exit;
    }

    public function exportAttendancePDF()
    {
        // Require Tipsoi External API logic if not already loaded
        if (!class_exists('ExternalApiService')) {
            require_once __DIR__ . '/../services/ExternalApiService.php';
        }
        $apiService = new ExternalApiService($this->db);

        $fromDateStr = $_GET['from_date'] ?? date('Y-m-d');
        $toDateStr = $_GET['to_date'] ?? date('Y-m-d');

        // Force UTC or Dhaka boundaries
        $oldTimezone = date_default_timezone_get();
        date_default_timezone_set('Asia/Dhaka');

        $fromTime = strtotime($fromDateStr . ' 00:00:00') * 1000;
        $toTime = strtotime($toDateStr . ' 23:59:59') * 1000;

        date_default_timezone_set($oldTimezone);

        $token = $apiService->getBearerToken();
        if (!$token) {
            die("Failed to authenticate with Tipsoi API.");
        }

        $url = "https://app.tipsoi.ai/inovace-client/api/v1/attendance?" . http_build_query([
            'officeId' => 4699,
            'order' => 'asc',
            'pageNumber' => 0,
            'perPage' => 2000,
            'reportType' => 'department',
            'sortKey' => 'reportPosition',
            'status' => 1,
            'from' => $fromTime,
            'to' => $toTime
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);
        if ($httpCode !== 200 || !isset($data['attendance'])) {
            die("Failed to retrieve attendance logs from Tipsoi API.");
        }

        // Fetch local employees for name/ID checks
        $localQuery = $this->db->query("SELECT employee_id, email, full_name FROM employees");
        $localEmployees = [];
        if ($localQuery) {
            while ($row = $localQuery->fetch_assoc()) {
                $localEmployees[] = [
                    'employee_id' => trim($row['employee_id'] ?? ''),
                    'email' => strtolower(trim($row['email'] ?? '')),
                    'full_name' => strtolower(trim($row['full_name'] ?? ''))
                ];
            }
        }

        // Aggregate Data
        $aggregatedData = [];
        $index = 1;
        foreach ($data['attendance'] as $emp) {
            $officeId = trim($emp['employeeOfficeId'] ?? $emp['employeeId']);
            $officeIdNorm = preg_match('/^\d+$/', $officeId) ? (string) (int) $officeId : $officeId;
            $nameNorm = strtolower(trim($emp['employeeName'] ?? ''));

            $isLocal = false;
            foreach ($localEmployees as $le) {
                $leIdNorm = preg_match('/^\d+$/', $le['employee_id']) ? (string) (int) $le['employee_id'] : $le['employee_id'];
                if (($officeIdNorm !== '' && $leIdNorm === $officeIdNorm) || ($nameNorm !== '' && $le['full_name'] === $nameNorm)) {
                    $isLocal = true;
                    break;
                }
            }

            $workingDays = 0;
            $present = 0;
            $absent = 0;
            $leave = 0;
            $dayOff = 0;
            $late = 0;

            $shiftStartStr = $emp['shiftStartTime'] ?? '';
            $shiftStartMin = null;
            if (preg_match('/^(\d+):(\d+)\s*(AM|PM)$/i', trim($shiftStartStr), $m)) {
                $h = (int) $m[1];
                $min = (int) $m[2];
                $ampm = strtoupper($m[3]);
                if ($ampm === 'PM' && $h !== 12)
                    $h += 12;
                elseif ($ampm === 'AM' && $h === 12)
                    $h = 0;
                $shiftStartMin = $h * 60 + $min;
            } else {
                $shiftStartMin = 570; // 09:30 AM default
            }
            $lateThreshold = $shiftStartMin + 10;

            $attMap = $emp['attendance'] ?? [];
            foreach ($attMap as $ts => $dayAtt) {
                $workingDays++;

                $isLateOverride = false;
                if (!empty($dayAtt['isPresent']) && !empty($dayAtt['firstLoggedTime']) && $dayAtt['firstLoggedTime'] !== '-' && $dayAtt['firstLoggedTime'] !== ' ') {
                    if (preg_match('/^(\d+):(\d+)\s*(AM|PM)$/i', trim($dayAtt['firstLoggedTime']), $zm)) {
                        $zh = (int) $zm[1];
                        $zmin = (int) $zm[2];
                        $zampm = strtoupper($zm[3]);
                        if ($zampm === 'PM' && $zh !== 12)
                            $zh += 12;
                        elseif ($zampm === 'AM' && $zh === 12)
                            $zh = 0;
                        $checkInMin = $zh * 60 + $zmin;
                        if ($checkInMin > $lateThreshold)
                            $isLateOverride = true;
                    }
                }

                if (!empty($dayAtt['isLeave'])) {
                    $leave++;
                } else if (!empty($dayAtt['isPresent'])) {
                    $present++;
                    if ($isLateOverride)
                        $late++;
                } else if (!empty($dayAtt['isOffday']) || !empty($dayAtt['isHoliday'])) {
                    $dayOff++;
                    $workingDays--;
                } else {
                    $absent++;
                }
            }

            $aggregatedData[] = [
                'sl' => $index++,
                'name' => $emp['employeeName'] ?? '-',
                'id' => $emp['employeeOfficeId'] ?? '-',
                'designation' => $emp['designationName'] ?? '-',
                'department' => $emp['departmentName'] ?? '-',
                'workplace' => $emp['workplaceName'] ?? '-',
                'empType' => 'Regular',
                'workingDays' => $workingDays,
                'present' => $present,
                'absent' => $absent,
                'leave' => $leave,
                'dayOff' => $dayOff,
                'late' => $late,
                'approvedLates' => 0,
                'finalLate' => $late,
                'deduction' => 0,
                'lateUnpaidAbsentDays' => $absent,
                'idCheck' => $isLocal ? 'OK' : 'Mismatch',
                'nameCheck' => $isLocal ? 'OK' : 'Mismatch'
            ];
        }

        // Generate TCPDF (Landscape A2 for 23 columns)
        // Adjust column sizes precisely so they do not overlap
        $pdf = new TCPDF('L', PDF_UNIT, 'A2', true, 'UTF-8', false);
        $pdf->SetCreator('HRM System');
        $pdf->SetTitle("Payroll Attendance Report - $fromDateStr to $toDateStr");

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->SetFillColor(7, 129, 52); // Theme Green header
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(0, 15, 'The Premium Homes Ltd.', 0, 1, 'C', true);

        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetFillColor(255, 255, 255); // Reset
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 10, "Payroll Attendance Report: " . date('F d, Y', strtotime($fromDateStr)) . " to " . date('F d, Y', strtotime($toDateStr)), 0, 1, 'C', true);
        $pdf->Ln(); // no space before headings

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(7, 129, 52); // table header color
        $pdf->SetTextColor(255, 255, 255);

        // A2 landscape width is ~574mm left after 20mm margins.
        $widths = [
            10, // SL
            45, // Name
            18, // ID
            40, // Designation
            35, // Department
            35, // Workplace
            25, // Type
            18, // Work Days
            16, // Present
            16, // Absent
            15, // Leave
            18, // Day Off
            12, // Late
            20, // App Lates
            20, // Final Late
            20, // Deduct
            22, // Unpaid
            20, // Join
            22, // Resign
            22, // Effect
            35, // Remarks
            20, // ID Check
            25  // Name Check
        ];

        $headers = [
            'SL',
            'Employee Name',
            'ID',
            'Designation',
            'Department',
            'Workplace',
            'Type',
            'Work Days',
            'Present',
            'Absent',
            'Leave',
            'Day Off',
            'Late',
            'App Lates',
            'Final Late',
            'Deduct',
            'Unpaid',
            'Join Date',
            'Resign Date',
            'Effect Date',
            'Remarks',
            'ID Check',
            'Name Check'
        ];

        // Draw Headers
        foreach ($headers as $index => $h) {
            $pdf->Cell($widths[$index], 10, $h, 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetFont('helvetica', '', 9);
        $fill = false;
        foreach ($aggregatedData as $row) {
            if ($fill)
                $pdf->SetFillColor(245, 245, 245);
            else
                $pdf->SetFillColor(255, 255, 255);

            $pdf->Cell($widths[0], 8, $row['sl'], 1, 0, 'C', $fill);
            // Limit characters to avoid breaking row height
            $pdf->Cell($widths[1], 8, mb_substr($row['name'], 0, 23), 1, 0, 'L', $fill);
            $pdf->Cell($widths[2], 8, $row['id'], 1, 0, 'C', $fill);
            $pdf->Cell($widths[3], 8, mb_substr($row['designation'], 0, 23), 1, 0, 'L', $fill);
            $pdf->Cell($widths[4], 8, mb_substr($row['department'], 0, 18), 1, 0, 'L', $fill);
            $pdf->Cell($widths[5], 8, mb_substr($row['workplace'], 0, 18), 1, 0, 'L', $fill);
            $pdf->Cell($widths[6], 8, $row['empType'], 1, 0, 'C', $fill);
            $pdf->Cell($widths[7], 8, $row['workingDays'], 1, 0, 'C', $fill);

            $pdf->SetTextColor(0, 128, 0);
            $pdf->Cell($widths[8], 8, $row['present'], 1, 0, 'C', $fill);
            $pdf->SetTextColor(0, 0, 0);

            $pdf->SetTextColor(255, 0, 0);
            $pdf->Cell($widths[9], 8, $row['absent'], 1, 0, 'C', $fill);
            $pdf->SetTextColor(0, 0, 0);

            $pdf->Cell($widths[10], 8, $row['leave'], 1, 0, 'C', $fill);
            $pdf->Cell($widths[11], 8, $row['dayOff'], 1, 0, 'C', $fill);

            $pdf->SetTextColor(255, 140, 0);
            $pdf->Cell($widths[12], 8, $row['late'], 1, 0, 'C', $fill);
            $pdf->SetTextColor(0, 0, 0);

            $pdf->Cell($widths[13], 8, '-', 1, 0, 'C', $fill);
            $pdf->Cell($widths[14], 8, $row['finalLate'], 1, 0, 'C', $fill);
            $pdf->Cell($widths[15], 8, '-', 1, 0, 'C', $fill);
            $pdf->Cell($widths[16], 8, $row['lateUnpaidAbsentDays'], 1, 0, 'C', $fill);

            $pdf->Cell($widths[17], 8, '-', 1, 0, 'C', $fill); // Join
            $pdf->Cell($widths[18], 8, '-', 1, 0, 'C', $fill); // Resign
            $pdf->Cell($widths[19], 8, '-', 1, 0, 'C', $fill); // Effect
            $pdf->Cell($widths[20], 8, '-', 1, 0, 'C', $fill); // Remarks

            if ($row['idCheck'] === 'OK')
                $pdf->SetTextColor(0, 128, 0);
            else
                $pdf->SetTextColor(255, 0, 0);
            $pdf->Cell($widths[21], 8, $row['idCheck'], 1, 0, 'C', $fill);

            if ($row['nameCheck'] === 'OK')
                $pdf->SetTextColor(0, 128, 0);
            else
                $pdf->SetTextColor(255, 0, 0);
            $pdf->Cell($widths[22], 8, $row['nameCheck'], 1, 0, 'C', $fill);
            $pdf->SetTextColor(0, 0, 0);

            $pdf->Ln();

            $fill = !$fill;
        }

        $pdf->Output("payroll_attendance_{$fromDateStr}_to_{$toDateStr}.pdf", 'D');
    }

    public function joiningList()
    {
        $currentYear = date('Y');
        $currentMonth = date('m');
        $defaultFrom = date('Y-m-01');
        $defaultTo = date('Y-m-t');

        $filterType = $_GET['filter_type'] ?? 'monthly';
        $fromDate = $defaultFrom;
        $toDate = $defaultTo;

        if ($filterType === 'monthly') {
            $m = $_GET['month'] ?? $currentMonth;
            $y = $_GET['year'] ?? $currentYear;
            $fromDate = date('Y-m-d', mktime(0, 0, 0, $m, 1, $y));
            $toDate = date('Y-m-t', mktime(0, 0, 0, $m, 1, $y));
        } elseif ($filterType === 'custom') {
            $fromDate = $_GET['from_date'] ?? $defaultFrom;
            $toDate = $_GET['to_date'] ?? $defaultTo;
        }

        $projectRes = $this->db->query("SELECT * FROM payroll_projects ORDER BY id DESC");
        $payroll_projects = [];
        if ($projectRes) {
            while ($pRow = $projectRes->fetch_assoc()) {
                $payroll_projects[] = $pRow;
            }
        }

        $page_title = "Payroll Joining List";
        $current_route = "payroll-joining-list";

        $payrollProjectId = isset($_GET['payroll_project_id']) && $_GET['payroll_project_id'] !== '' ? (int) $_GET['payroll_project_id'] : null;

        $localSql = "SELECT e.id as real_emp_id_fk, e.employee_id, e.full_name, desg.name AS designation, dept.name AS department,
                            e.workplace, e.joining_date, e.gross_salary, e.basic_salary
                     FROM employees e
                     LEFT JOIN designations desg ON e.designation_id = desg.id
                     LEFT JOIN departments dept ON e.department_id = dept.id
                     WHERE e.joining_date >= ? AND e.joining_date <= ?
                     ORDER BY e.joining_date ASC";

        $stmt = $this->db->prepare($localSql);
        $stmt->bind_param("ss", $fromDate, $toDate);
        $stmt->execute();
        $res = $stmt->get_result();

        $localEmployees = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $localEmployees[] = $row;
            }
        }

        $projectEmployeeIds = [];
        if ($payrollProjectId) {
            $projQ = $this->db->query("SELECT employee_id FROM payslips WHERE payroll_project_id = $payrollProjectId UNION SELECT employee_id FROM attendance_monthly_update WHERE payroll_project_id = $payrollProjectId");
            if ($projQ) {
                while ($pr = $projQ->fetch_assoc()) {
                    $projectEmployeeIds[$pr['employee_id']] = true;
                }
            }
        }

        $employees = [];
        foreach ($localEmployees as $localMatch) {
            if ($payrollProjectId && !isset($projectEmployeeIds[$localMatch['real_emp_id_fk']])) {
                continue;
            }

            $employees[] = [
                'employee_id' => $localMatch['employee_id'],
                'full_name' => $localMatch['full_name'] ?? '-',
                'department' => $localMatch['department'] ?? '-',
                'designation' => $localMatch['designation'] ?? '-',
                'workplace' => $localMatch['workplace'] ?? '-',
                'joining_date' => $localMatch['joining_date'],
                'gross_salary' => $localMatch['gross_salary'] ?? 0,
                'basic_salary' => $localMatch['basic_salary'] ?? 0,
            ];
        }

        $this->view('payrolls/payslips/joining-list', [
            'page_title' => $page_title,
            'current_route' => $current_route,
            'payroll_projects' => $payroll_projects,
            'employees' => $employees,
            'filter_type' => $filterType,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'selected_month' => $_GET['month'] ?? $currentMonth,
            'selected_year' => $_GET['year'] ?? $currentYear,
            'payroll_project_id' => $payrollProjectId
        ]);
    }

    public function exportJoiningListPDF()
    {
        $currentYear = date('Y');
        $currentMonth = date('m');
        $defaultFrom = date('Y-m-d');
        $defaultTo = date('Y-m-d');

        $filterType = $_GET['filter_type'] ?? 'monthly';
        $fromDate = $defaultFrom;
        $toDate = $defaultTo;

        if ($filterType === 'monthly') {
            $m = $_GET['month'] ?? $currentMonth;
            $y = $_GET['year'] ?? $currentYear;
            $fromDate = date('Y-m-d', mktime(0, 0, 0, $m, 1, $y));
            $toDate = date('Y-m-t', mktime(0, 0, 0, $m, 1, $y));
        } elseif ($filterType === 'custom') {
            $fromDate = $_GET['from_date'] ?? $defaultFrom;
            $toDate = $_GET['to_date'] ?? $defaultTo;
        }

        $payrollProjectId = isset($_GET['payroll_project_id']) && $_GET['payroll_project_id'] !== '' ? (int) $_GET['payroll_project_id'] : null;

        $employees = [];
        // Fetch Local Employees matching the date range
        $localSql = "SELECT e.id as real_emp_id_fk, e.employee_id, e.full_name, desg.name AS designation, dept.name AS department,
                            e.workplace, e.joining_date, e.gross_salary, e.basic_salary
                     FROM employees e
                     LEFT JOIN designations desg ON e.designation_id = desg.id
                     LEFT JOIN departments dept ON e.department_id = dept.id
                     WHERE e.joining_date >= ? AND e.joining_date <= ?
                     ORDER BY e.joining_date ASC";

        $stmt = $this->db->prepare($localSql);
        $stmt->bind_param("ss", $fromDate, $toDate);
        $stmt->execute();
        $res = $stmt->get_result();

        $localEmployees = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $localEmployees[] = $row;
            }
        }

        $projectEmployeeIds = [];
        if ($payrollProjectId) {
            $projQ = $this->db->query("SELECT employee_id FROM payslips WHERE payroll_project_id = $payrollProjectId UNION SELECT employee_id FROM attendance_monthly_update WHERE payroll_project_id = $payrollProjectId");
            if ($projQ) {
                while ($pr = $projQ->fetch_assoc()) {
                    $projectEmployeeIds[$pr['employee_id']] = true;
                }
            }
        }

        foreach ($localEmployees as $localMatch) {
            if ($payrollProjectId && !isset($projectEmployeeIds[$localMatch['real_emp_id_fk']])) {
                continue;
            }

            $employees[] = [
                'employee_id' => $localMatch['employee_id'],
                'full_name' => $localMatch['full_name'] ?? '-',
                'department' => $localMatch['department'] ?? '-',
                'designation' => $localMatch['designation'] ?? '-',
                'workplace' => $localMatch['workplace'] ?? '-',
                'joining_date' => $localMatch['joining_date'],
                'gross_salary' => $localMatch['gross_salary'] ?? 0,
                'basic_salary' => $localMatch['basic_salary'] ?? 0,
            ];
        }

        $pdf = new \TCPDF('L', 'mm', 'A4');
        $pdf->SetCreator('TPHL ERP');
        $pdf->SetAuthor('Admin');
        $pdf->SetTitle('Joining List Report');
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', '', 9);

        $html = '
        <div style="text-align:center;">
            <h1 style="color:#078134;">The Premium Homes Ltd</h1>
            <h3 style="color:#28B463;">Joining List Report </h3>
            <p>Date Range: ' . date('F d, Y', strtotime($fromDate)) . ' to ' . date('F d, Y', strtotime($toDate)) . '</p>
            <p><b>Total Records: </b> ' . count($employees) . '</p>
        </div>
        <br>
        <table border="1" cellpadding="5" width="100%">
            <thead>
                <tr style="background-color:#078134; color:#ffffff; text-align:center; font-weight:bold;">
                    <th width="5%">SL</th>
                    <th width="10%">ID</th>
                    <th width="18%">Name</th>
                    <th width="22%">Department - Designation</th>
                    <th width="15%">Workplace</th>
                    <th width="10%">Joining Date</th>
                    <th width="10%">Gross Salary</th>
                    <th width="10%">Basic Salary</th>
                </tr>
            </thead>
            <tbody>';

        $sl = 1;
        foreach ($employees as $row) {
            $deptDesig = ($row['department'] ?? '-') . ' - ' . ($row['designation'] ?? '-');
            $html .= '<tr style="text-align:center;">
                        <td width="5%">' . $sl++ . '</td>
                        <td width="10%">' . htmlspecialchars($row['employee_id']) . '</td>
                        <td width="18%" style="text-align:left;">' . htmlspecialchars($row['full_name']) . '</td>
                        <td width="22%" style="text-align:left;">' . htmlspecialchars($deptDesig) . '</td>
                        <td width="15%">' . htmlspecialchars($row['workplace'] ?? '-') . '</td>
                        <td width="10%">' . htmlspecialchars($row['joining_date']) . '</td>
                        <td width="10%">' . htmlspecialchars(number_format($row['gross_salary'] ?? 0, 2)) . '</td>
                        <td width="10%">' . htmlspecialchars(number_format($row['basic_salary'] ?? 0, 2)) . '</td>
                      </tr>';
        }

        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('Joining_List_Report_' . $fromDate . '_to_' . $toDate . '.pdf', 'D');
        exit;
    }

    public function resignList()
    {
        $currentYear = date('Y');
        $currentMonth = date('m');
        $defaultFrom = date('Y-m-d');
        $defaultTo = date('Y-m-d');

        $filterType = $_GET['filter_type'] ?? 'monthly';
        $fromDate = $defaultFrom;
        $toDate = $defaultTo;

        if ($filterType === 'monthly') {
            $m = $_GET['month'] ?? $currentMonth;
            $y = $_GET['year'] ?? $currentYear;
            $fromDate = date('Y-m-d', mktime(0, 0, 0, $m, 1, $y));
            $toDate = date('Y-m-t', mktime(0, 0, 0, $m, 1, $y));
        } elseif ($filterType === 'custom') {
            $fromDate = $_GET['from_date'] ?? $defaultFrom;
            $toDate = $_GET['to_date'] ?? $defaultTo;
        }

        $sql = "SELECT e.employee_id, e.full_name, desg.name AS designation, dept.name AS department,
                       e.workplace, e.resign_date, e.employee_status, e.gross_salary
                FROM employees e 
                LEFT JOIN designations desg ON e.designation_id = desg.id
                LEFT JOIN departments dept ON e.department_id = dept.id
                WHERE e.resign_date >= ? AND e.resign_date <= ?
                ORDER BY e.resign_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $fromDate, $toDate);
        $stmt->execute();
        $res = $stmt->get_result();

        $employees = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $employees[] = $row;
            }
        }

        $page_title = "Resign List";
        $current_route = "payroll-resign-list";

        $this->view('payrolls/payslips/resign-list', [
            'page_title' => $page_title,
            'current_route' => $current_route,
            'employees' => $employees,
            'filter_type' => $filterType,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'selected_month' => $_GET['month'] ?? $currentMonth,
            'selected_year' => $_GET['year'] ?? $currentYear
        ]);
    }

    public function exportResignListPDF()
    {
        $currentYear = date('Y');
        $currentMonth = date('m');
        $defaultFrom = date('Y-m-d');
        $defaultTo = date('Y-m-d');

        $filterType = $_GET['filter_type'] ?? 'monthly';
        $fromDate = $defaultFrom;
        $toDate = $defaultTo;

        if ($filterType === 'monthly') {
            $m = $_GET['month'] ?? $currentMonth;
            $y = $_GET['year'] ?? $currentYear;
            $fromDate = date('Y-m-d', mktime(0, 0, 0, $m, 1, $y));
            $toDate = date('Y-m-t', mktime(0, 0, 0, $m, 1, $y));
        } elseif ($filterType === 'custom') {
            $fromDate = $_GET['from_date'] ?? $defaultFrom;
            $toDate = $_GET['to_date'] ?? $defaultTo;
        }

        $sql = "SELECT e.employee_id, e.full_name, desg.name AS designation, dept.name AS department,
                       e.workplace, e.resign_date, e.employee_status, e.gross_salary
                FROM employees e 
                LEFT JOIN designations desg ON e.designation_id = desg.id
                LEFT JOIN departments dept ON e.department_id = dept.id
                WHERE e.resign_date >= ? AND e.resign_date <= ?
                ORDER BY e.resign_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $fromDate, $toDate);
        $stmt->execute();
        $res = $stmt->get_result();

        $employees = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $employees[] = $row;
            }
        }

        $pdf = new \TCPDF('L', 'mm', 'A4');
        $pdf->SetCreator('TPHL ERP');
        $pdf->SetAuthor('Admin');
        $pdf->SetTitle('Resign List Report');
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', '', 9);

        // Alignment properly matched with standard column counts
        $html = '
        <div style="text-align:center;">
            <h1 style="color:#078134;">The Premium Homes Ltd</h1>
            <h3 style="color:#28B463;">Resign List Report </h3>
            <p>Date Range: ' . date('F d, Y', strtotime($fromDate)) . ' to ' . date('F d, Y', strtotime($toDate)) . '</p>
            <p><b>Total Records: </b> ' . count($employees) . '</p>
        </div>
        <br>
        <table border="1" cellpadding="5" width="100%">
            <thead>
                <tr style="background-color:#078134; color:#ffffff; font-weight:bold;">
                    <th width="5%" style="text-align:center;">SL</th>
                    <th width="10%" style="text-align:center;">ID</th>
                    <th width="18%" style="text-align:left;">Name</th>
                    <th width="25%" style="text-align:left;">Department - Designation</th>
                    <th width="12%" style="text-align:center;">Workplace</th>
                    <th width="12%" style="text-align:center;">Last Working Day</th>
                    <th width="8%" style="text-align:center;">Status</th>
                    <th width="10%" style="text-align:center;">Salary</th>
                </tr>
            </thead>
            <tbody>';

        $sl = 1;
        foreach ($employees as $row) {
            $deptDesig = ($row['department'] ?? '-') . ' - ' . ($row['designation'] ?? '-');
            $statusText = ucfirst($row['employee_status'] ?? '-');
            if (strtolower($row['employee_status'] ?? '') === 'resigned') {
                $statusText = 'Resigned';
            }
            $html .= '<tr>
                        <td width="5%" style="text-align:center;">' . $sl++ . '</td>
                        <td width="10%" style="text-align:center;">' . htmlspecialchars($row['employee_id']) . '</td>
                        <td width="18%" style="text-align:left;">' . htmlspecialchars($row['full_name']) . '</td>
                        <td width="25%" style="text-align:left;">' . htmlspecialchars($deptDesig) . '</td>
                        <td width="12%" style="text-align:center;">' . htmlspecialchars($row['workplace'] ?? '-') . '</td>
                        <td width="12%" style="text-align:center;">' . htmlspecialchars($row['resign_date'] ?? '-') . '</td>
                        <td width="8%" style="text-align:center;">' . htmlspecialchars($statusText) . '</td>
                        <td width="10%" style="text-align:center;">' . htmlspecialchars(number_format($row['gross_salary'] ?? 0, 2)) . '</td>
                      </tr>';
        }

        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('Resign_List_Report_' . $fromDate . '_to_' . $toDate . '.pdf', 'D');
        exit;
    }

    public function detailedSummary()
    {
        $projectRes = $this->db->query("SELECT * FROM payroll_projects ORDER BY id DESC");
        $payroll_projects = [];
        if ($projectRes) {
            while ($pRow = $projectRes->fetch_assoc()) {
                $payroll_projects[] = $pRow;
            }
        }

        $payrollProjectId = isset($_GET['payroll_project_id']) && $_GET['payroll_project_id'] !== '' ? (int) $_GET['payroll_project_id'] : null;

        $departmentsData = [];

        if ($payrollProjectId) {
            $sql = "
                SELECT p.id, p.net_salary,
                       e.full_name, e.employee_id as emp_id,
                       COALESCE(dept.name, 'Unassigned') as department_name, 
                       desg.name as designation_name
                FROM payslips p
                LEFT JOIN employees e ON p.employee_id = e.id
                LEFT JOIN departments dept ON e.department_id = dept.id
                LEFT JOIN designations desg ON e.designation_id = desg.id
                WHERE p.payroll_project_id = ? AND e.employee_status = 'active'
                ORDER BY dept.name ASC, e.employee_id ASC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $payrollProjectId);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $dept = $row['department_name'];
                    if (!isset($departmentsData[$dept])) {
                        $departmentsData[$dept] = [
                            'employees' => [],
                            'total_payable' => 0
                        ];
                    }
                    $departmentsData[$dept]['employees'][] = $row;
                    $departmentsData[$dept]['total_payable'] += (float) $row['net_salary'];
                }
            }
        }

        $page_title = "Detailed Summary";
        $current_route = "payroll-detailed-summary";

        $this->view('payrolls/payslips/detailed-summary', [
            'page_title' => $page_title,
            'current_route' => $current_route,
            'departmentsData' => $departmentsData,
            'payroll_projects' => $payroll_projects,
            'payroll_project_id' => $payrollProjectId
        ]);
    }

    public function exportDetailedSummaryPDF()
    {
        $payrollProjectId = isset($_GET['payroll_project_id']) && $_GET['payroll_project_id'] !== '' ? (int) $_GET['payroll_project_id'] : null;

        if (!$payrollProjectId) {
            echo "Payroll Project is required.";
            exit;
        }

        $projName = '';
        $q = $this->db->query("SELECT name, date FROM payroll_projects WHERE id = $payrollProjectId");
        if ($q && $r = $q->fetch_assoc()) {
            $projName = $r['name'];
        }

        $sql = "
            SELECT p.id, p.net_salary,
                   e.full_name, e.employee_id as emp_id,
                   COALESCE(dept.name, 'Unassigned') as department_name, 
                   desg.name as designation_name
            FROM payslips p
            LEFT JOIN employees e ON p.employee_id = e.id
            LEFT JOIN departments dept ON e.department_id = dept.id
            LEFT JOIN designations desg ON e.designation_id = desg.id
            WHERE p.payroll_project_id = ? AND e.employee_status = 'active'
            ORDER BY dept.name ASC, e.employee_id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $payrollProjectId);
        $stmt->execute();
        $res = $stmt->get_result();

        $departmentsData = [];
        $totalEmp = 0;
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $dept = $row['department_name'];
                if (!isset($departmentsData[$dept])) {
                    $departmentsData[$dept] = [
                        'employees' => [],
                        'total_payable' => 0
                    ];
                }
                $departmentsData[$dept]['employees'][] = $row;
                $departmentsData[$dept]['total_payable'] += (float) $row['net_salary'];
                $totalEmp++;
            }
        }

        $pdf = new \TCPDF('P', 'mm', 'A4');
        $pdf->SetCreator('TPHL ERP');
        $pdf->SetAuthor('Admin');
        $pdf->SetTitle('Detailed Summary Report');
        $pdf->SetMargins(10, 15, 10);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', '', 9);

        $html = '
        <div style="text-align:center;">
            <h1 style="color:#078134;">The Premium Homes Ltd</h1>
            <h3 style="color:#28B463;">Detailed Summary Report </h3>
            <p>Project: ' . htmlspecialchars($projName) . '</p>
            <p><b>Total Employees: </b> ' . $totalEmp . '</p>
        </div>
        <br>
        <table border="1" cellpadding="5" width="100%">
            <thead>
                <tr style="background-color:#078134; color:#ffffff; font-weight:bold;">
                    <th width="15%" style="text-align:center;">Department</th>
                    <th width="6%" style="text-align:center;">SL</th>
                    <th width="24%" style="text-align:left;">Employee Name</th>
                    <th width="10%" style="text-align:center;">ID</th>
                    <th width="15%" style="text-align:left;">Designation</th>
                    <th width="15%" style="text-align:right;">Net Payable</th>
                    <th width="15%" style="text-align:right;">Total Payable</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($departmentsData as $deptName => $deptInfo) {
            $empCount = count($deptInfo['employees']);
            $totalPay = number_format($deptInfo['total_payable'], 2);
            $sl = 1;

            foreach ($deptInfo['employees'] as $index => $emp) {
                $html .= '<tr>';

                if ($index === 0) {
                    $html .= '<td width="15%" rowspan="' . $empCount . '" style="text-align:center; vertical-align:middle;">' . htmlspecialchars($deptName) . '</td>';
                }

                $html .= '<td width="6%" style="text-align:center;">' . $sl++ . '</td>';
                $html .= '<td width="24%" style="text-align:left;">' . htmlspecialchars($emp['full_name'] ?? '') . '</td>';
                $html .= '<td width="10%" style="text-align:center;">' . htmlspecialchars($emp['emp_id'] ?? '') . '</td>';
                $html .= '<td width="15%" style="text-align:left;">' . htmlspecialchars($emp['designation_name'] ?? '-') . '</td>';
                $html .= '<td width="15%" style="text-align:right;">' . number_format($emp['net_salary'] ?? 0, 2) . '</td>';

                if ($index === 0) {
                    $html .= '<td width="15%" rowspan="' . $empCount . '" style="text-align:right; vertical-align:middle; font-weight:bold;">' . $totalPay . '</td>';
                }

                $html .= '</tr>';
            }
        }

        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('Detailed_Summary_Report_' . preg_replace('/[^a-zA-Z0-9]/', '_', $projName) . '.pdf', 'D');
        exit;
    }

    public function summaryReport()
    {
        $projectRes = $this->db->query("SELECT * FROM payroll_projects ORDER BY id ASC");
        $payroll_projects = [];
        if ($projectRes) {
            while ($pRow = $projectRes->fetch_assoc()) {
                $payroll_projects[] = $pRow;
            }
        }

        $fromProjectId = isset($_GET['from_project_id']) && $_GET['from_project_id'] !== '' ? (int) $_GET['from_project_id'] : null;
        $toProjectId = isset($_GET['to_project_id']) && $_GET['to_project_id'] !== '' ? (int) $_GET['to_project_id'] : null;

        $targetProjects = [];
        if ($fromProjectId && $toProjectId) {
            $minId = min($fromProjectId, $toProjectId);
            $maxId = max($fromProjectId, $toProjectId);
            foreach ($payroll_projects as $proj) {
                if ($proj['id'] >= $minId && $proj['id'] <= $maxId) {
                    $targetProjects[] = $proj;
                }
            }
        }

        $data = [
            'No of employees' => [],
            'Net Salary (BDT)' => [],
            'Tax Deducted(BDT)' => [],
            'Net Payable(BDT)' => [],
            'Salary via Bank(BDT)' => [],
            'Salary via Cash(BDT)' => []
        ];

        // Pre-fill structure
        foreach ($targetProjects as $proj) {
            $pid = $proj['id'];
            foreach (array_keys($data) as $key) {
                $data[$key][$pid] = 0;
            }
        }

        if (!empty($targetProjects)) {
            $minId = min($fromProjectId, $toProjectId);
            $maxId = max($fromProjectId, $toProjectId);

            $sql = "
                SELECT p.id, p.payroll_project_id, p.net_salary, p.payment_method, p.employee_id,
                       (SELECT SUM(pd.amount) 
                        FROM payslip_details pd 
                        JOIN payslip_items pi ON pd.payslip_item_id = pi.id 
                        WHERE pd.payslip_id = p.id AND pi.type = 'deduction' AND LOWER(pi.name) LIKE '%tax%') as tax_deducted
                FROM payslips p
                WHERE p.payroll_project_id >= ? AND p.payroll_project_id <= ?
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ii", $minId, $maxId);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $pid = $row['payroll_project_id'];

                    if (!isset($data['No of employees'][$pid]))
                        continue;

                    $data['No of employees'][$pid] += 1;
                    $data['Net Salary (BDT)'][$pid] += (float) $row['net_salary'];
                    $data['Tax Deducted(BDT)'][$pid] += (float) $row['tax_deducted'];
                    $data['Net Payable(BDT)'][$pid] += (float) $row['net_salary'];

                    $method = strtolower($row['payment_method'] ?? '');
                    if ($method === 'bank' || $method === 'bank transfer') {
                        $data['Salary via Bank(BDT)'][$pid] += (float) $row['net_salary'];
                    } elseif ($method === 'cash') {
                        $data['Salary via Cash(BDT)'][$pid] += (float) $row['net_salary'];
                    }
                }
            }
        }

        $page_title = "Payroll Summary Report";
        $current_route = "payroll-summary-report";

        $this->view('payrolls/payslips/summary-report', [
            'page_title' => $page_title,
            'current_route' => $current_route,
            'payroll_projects' => $payroll_projects,
            'from_project_id' => $fromProjectId,
            'to_project_id' => $toProjectId,
            'targetProjects' => $targetProjects,
            'data' => $data
        ]);
    }

    public function exportSummaryReportPDF()
    {
        $fromProjectId = isset($_GET['from_project_id']) && $_GET['from_project_id'] !== '' ? (int) $_GET['from_project_id'] : null;
        $toProjectId = isset($_GET['to_project_id']) && $_GET['to_project_id'] !== '' ? (int) $_GET['to_project_id'] : null;

        if (!$fromProjectId || !$toProjectId) {
            echo "Both From and To Payroll Projects are required for PDF export.";
            exit;
        }

        $projectRes = $this->db->query("SELECT * FROM payroll_projects ORDER BY id ASC");
        $payroll_projects = [];
        if ($projectRes) {
            while ($pRow = $projectRes->fetch_assoc()) {
                $payroll_projects[] = $pRow;
            }
        }

        $targetProjects = [];
        $minId = min($fromProjectId, $toProjectId);
        $maxId = max($fromProjectId, $toProjectId);
        foreach ($payroll_projects as $proj) {
            if ($proj['id'] >= $minId && $proj['id'] <= $maxId) {
                $targetProjects[] = $proj;
            }
        }

        $data = [
            'No of employees' => [],
            'Net Salary (BDT)' => [],
            'Tax Deducted(BDT)' => [],
            'Net Payable(BDT)' => [],
            'Salary via Bank(BDT)' => [],
            'Salary via Cash(BDT)' => []
        ];

        // Pre-fill structure
        foreach ($targetProjects as $proj) {
            $pid = $proj['id'];
            foreach (array_keys($data) as $key) {
                $data[$key][$pid] = 0;
            }
        }

        if (!empty($targetProjects)) {
            $sql = "
                SELECT p.id, p.payroll_project_id, p.net_salary, p.payment_method, p.employee_id,
                       (SELECT SUM(pd.amount) 
                        FROM payslip_details pd 
                        JOIN payslip_items pi ON pd.payslip_item_id = pi.id 
                        WHERE pd.payslip_id = p.id AND pi.type = 'deduction' AND LOWER(pi.name) LIKE '%tax%') as tax_deducted
                FROM payslips p
                WHERE p.payroll_project_id >= ? AND p.payroll_project_id <= ?
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ii", $minId, $maxId);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $pid = $row['payroll_project_id'];

                    if (!isset($data['No of employees'][$pid]))
                        continue;

                    $data['No of employees'][$pid] += 1;
                    $data['Net Salary (BDT)'][$pid] += (float) $row['net_salary'];
                    $data['Tax Deducted(BDT)'][$pid] += (float) $row['tax_deducted'];
                    $data['Net Payable(BDT)'][$pid] += (float) $row['net_salary'];

                    $method = strtolower($row['payment_method'] ?? '');
                    if ($method === 'bank' || $method === 'bank transfer') {
                        $data['Salary via Bank(BDT)'][$pid] += (float) $row['net_salary'];
                    } elseif ($method === 'cash') {
                        $data['Salary via Cash(BDT)'][$pid] += (float) $row['net_salary'];
                    }
                }
            }
        }

        $pdf = new \TCPDF('L', 'mm', 'A4');
        $pdf->SetCreator('TPHL ERP');
        $pdf->SetAuthor('Admin');
        $pdf->SetTitle('Payroll Summary Report');
        $pdf->SetMargins(10, 15, 10);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', '', 9);

        $html = '
        <div style="text-align:center;">
            <h1 style="color:#078134;">The Premium Homes Ltd</h1>
            <h3 style="color:#28B463;">Payroll Summary Report </h3>
        </div>
        <br>
        <table border="1" cellpadding="5" width="100%">
            <thead>
                <tr style="background-color:#078134; color:#ffffff; font-weight:bold; text-align:center;">
                    <th width="30%">Particulars</th>';

        foreach ($targetProjects as $proj) {
            $title = $proj['name'] . ' (' . ($proj['date'] ?? '') . ')';
            $html .= '<th width="15%">' . htmlspecialchars($title) . '</th>';
        }

        $html .= '  <th width="12%">Growth in (%)<br>Before Last</th>
                    <th width="12%">Growth in (%)<br>Last</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($data as $key => $monthlyValues) {
            $html .= '<tr style="text-align:center;">
                <td style="text-align:left; font-weight:bold;">' . htmlspecialchars($key) . '</td>';

            foreach ($targetProjects as $proj) {
                $pid = $proj['id'];
                $val = isset($monthlyValues[$pid]) ? $monthlyValues[$pid] : 0;
                $html .= '<td>' . (($key === 'No of employees') ? (int) $val : number_format($val, 2)) . '</td>';
            }

            $count = count($targetProjects);
            $valLast = 0;
            $valSecondLast = 0;
            $valThirdLast = 0;

            if ($count >= 1)
                $valLast = $monthlyValues[$targetProjects[$count - 1]['id']] ?? 0;
            if ($count >= 2)
                $valSecondLast = $monthlyValues[$targetProjects[$count - 2]['id']] ?? 0;
            if ($count >= 3)
                $valThirdLast = $monthlyValues[$targetProjects[$count - 3]['id']] ?? 0;

            $growthLast = 0;
            if ($valSecondLast != 0) {
                $growthLast = ($valLast - $valSecondLast) / $valSecondLast * 100;
            }

            $growthSecondLast = 0;
            if ($valThirdLast != 0) {
                $growthSecondLast = ($valSecondLast - $valThirdLast) / $valThirdLast * 100;
            }

            $formatG = function ($val) {
                $str = number_format($val, 2) . '%';
                if ($val > 0)
                    return '<span style="color:green;">+' . $str . '</span>';
                if ($val < 0)
                    return '<span style="color:red;">' . $str . '</span>';
                return '<span style="color:gray;">' . $str . '</span>';
            };

            $html .= '<td>' . $formatG($growthSecondLast) . '</td>
                      <td>' . $formatG($growthLast) . '</td>
            </tr>';
        }

        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('Payroll_Summary_Report_' . time() . '.pdf', 'D');
        exit;
    }

    public function salaryReport()
    {
        $reportType = $_GET['report_type'] ?? 'department';
        $payrollProjectId = isset($_GET['payroll_project_id']) && $_GET['payroll_project_id'] !== '' ? (int) $_GET['payroll_project_id'] : null;

        $projectRes = $this->db->query("SELECT * FROM payroll_projects ORDER BY id DESC");
        $payroll_projects = [];
        if ($projectRes) {
            while ($pRow = $projectRes->fetch_assoc()) {
                $payroll_projects[] = $pRow;
            }
        }

        $data = [];
        if ($payrollProjectId) {
            if ($reportType === 'department') {
                $sql = "SELECT 
                            d.name AS dept_name, 
                            COUNT(p.id) AS no_of_emp,
                            SUM(p.net_salary) AS total_amount
                        FROM payslips p
                        JOIN employees e ON p.employee_id = e.id
                        JOIN departments d ON e.department_id = d.id
                        WHERE p.payroll_project_id = ? AND e.employee_status = 'active'
                          AND p.status != 'cancelled'
                        GROUP BY d.id
                        ORDER BY d.name ASC";
                $stmt = $this->db->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("i", $payrollProjectId);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($res) {
                        while ($row = $res->fetch_assoc()) {
                            $data[] = $row;
                        }
                    }
                    $stmt->close();
                }
            } else {
                $sql = "SELECT 
                            IFNULL(ob.name, 'Unassigned') AS office_name, 
                            COUNT(p.id) AS no_of_emp,
                            SUM(p.net_salary) AS total_amount,
                            SUM(CASE WHEN LOWER(p.payment_method) LIKE '%bank%' THEN p.net_salary ELSE 0 END) AS bank_amount,
                            SUM(CASE WHEN LOWER(p.payment_method) LIKE '%cash%' THEN p.net_salary ELSE 0 END) AS cash_amount
                        FROM payslips p
                        JOIN employees e ON p.employee_id = e.id
                        LEFT JOIN office_branches ob ON e.office_branch_id = ob.id
                        WHERE p.payroll_project_id = ? AND e.employee_status = 'active'
                          AND p.status != 'cancelled'
                        GROUP BY ob.id
                        ORDER BY office_name ASC";
                $stmt = $this->db->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("i", $payrollProjectId);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($res) {
                        while ($row = $res->fetch_assoc()) {
                            $data[] = $row;
                        }
                    }
                    $stmt->close();
                }
            }
        }

        $this->view('payrolls/payslips/salary-report', [
            'reportType' => $reportType,
            'payroll_projects' => $payroll_projects,
            'payroll_project_id' => $payrollProjectId,
            'data' => $data
        ]);
    }

    public function exportSalaryReportPDF()
    {
        $reportType = $_GET['report_type'] ?? 'department';
        $payrollProjectId = isset($_GET['payroll_project_id']) && $_GET['payroll_project_id'] !== '' ? (int) $_GET['payroll_project_id'] : null;

        if (!$payrollProjectId) {
            echo "Payroll Project is required for PDF export.";
            exit;
        }

        $projName = "None";
        $projDate = "";
        $projRes = $this->db->query("SELECT name, date FROM payroll_projects WHERE id = $payrollProjectId");
        if ($projRes && $pRow = $projRes->fetch_assoc()) {
            $projName = $pRow['name'];
            $projDate = $pRow['date'];
        }

        $data = [];
        if ($reportType === 'department') {
            $sql = "SELECT 
                        d.name AS dept_name, 
                        COUNT(p.id) AS no_of_emp,
                        SUM(p.net_salary) AS total_amount
                    FROM payslips p
                    JOIN employees e ON p.employee_id = e.id
                    JOIN departments d ON e.department_id = d.id
                    WHERE p.payroll_project_id = ? AND e.employee_status = 'active' 
                      AND p.status != 'cancelled'
                    GROUP BY d.id
                    ORDER BY d.name ASC";
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("i", $payrollProjectId);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res) {
                    while ($row = $res->fetch_assoc()) {
                        $data[] = $row;
                    }
                }
                $stmt->close();
            }
        } else {
            $sql = "SELECT 
                        IFNULL(ob.name, 'Unassigned') AS office_name, 
                        COUNT(p.id) AS no_of_emp,
                        SUM(p.net_salary) AS total_amount,
                        SUM(CASE WHEN LOWER(p.payment_method) LIKE '%bank%' THEN p.net_salary ELSE 0 END) AS bank_amount,
                        SUM(CASE WHEN LOWER(p.payment_method) LIKE '%cash%' THEN p.net_salary ELSE 0 END) AS cash_amount
                    FROM payslips p
                    JOIN employees e ON p.employee_id = e.id
                    LEFT JOIN office_branches ob ON e.office_branch_id = ob.id
                    WHERE p.payroll_project_id = ? AND e.employee_status = 'active' 
                      AND p.status != 'cancelled'
                    GROUP BY ob.id
                    ORDER BY office_name ASC";
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("i", $payrollProjectId);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res) {
                    while ($row = $res->fetch_assoc()) {
                        $data[] = $row;
                    }
                }
                $stmt->close();
            }
        }

        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->SetCreator('TPHL-ERP');
        $pdf->SetAuthor('TPHL-ERP');
        $pdf->SetTitle('Salary Report - ' . ucfirst($reportType) . ' Wise');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage('L');
        $pdf->SetFont('helvetica', '', 10);

        $html = '<h2 style="text-align:center;">Salary Report (' . ucfirst($reportType) . ' Wise)</h2>';
        $html .= '<h4 style="text-align:center;">Payroll Project: ' . htmlspecialchars($projName) . ' (' . htmlspecialchars($projDate ?? '') . ')</h4><br>';

        $html .= '<table border="1" cellpadding="5" cellspacing="0">';
        if ($reportType === 'department') {
            $html .= '<thead>
                        <tr style="background-color:#078134; color:#ffffff; font-weight:bold;">
                            <th width="10%" style="text-align:center;">SL</th>
                            <th width="40%" style="text-align:left;">Department Name</th>
                            <th width="25%" style="text-align:center;">No of Employees</th>
                            <th width="25%" style="text-align:right;">Amount</th>
                        </tr>
                      </thead><tbody>';
            $sl = 1;
            foreach ($data as $row) {
                $html .= '<tr>
                            <td width="10%" style="text-align:center;">' . $sl++ . '</td>
                            <td width="40%" style="text-align:left;">' . htmlspecialchars($row['dept_name']) . '</td>
                            <td width="25%" style="text-align:center;">' . $row['no_of_emp'] . '</td>
                            <td width="25%" style="text-align:right;">' . number_format($row['total_amount'], 2) . '</td>
                          </tr>';
            }
        } else {
            $html .= '<thead>
                        <tr style="background-color:#078134; color:#ffffff; font-weight:bold;">
                            <th width="30%" style="text-align:left;">Office Name</th>
                            <th width="15%" style="text-align:center;">No of Employees</th>
                            <th width="20%" style="text-align:right;">Total</th>
                            <th width="15%" style="text-align:right;">Bank</th>
                            <th width="20%" style="text-align:right;">Cash</th>
                        </tr>
                      </thead><tbody>';
            foreach ($data as $row) {
                $html .= '<tr>
                            <td width="30%" style="text-align:left;">' . htmlspecialchars($row['office_name']) . '</td>
                            <td width="15%" style="text-align:center;">' . $row['no_of_emp'] . '</td>
                            <td width="20%" style="text-align:right;">' . number_format($row['total_amount'], 2) . '</td>
                            <td width="15%" style="text-align:right;">' . number_format($row['bank_amount'], 2) . '</td>
                            <td width="20%" style="text-align:right;">' . number_format($row['cash_amount'], 2) . '</td>
                          </tr>';
            }
        }

        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('Salary_Report_' . ucfirst($reportType) . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $projName) . '.pdf', 'D');
        exit;
    }

    public function exportSalaryReportCSV()
    {
        $reportType = $_GET['report_type'] ?? 'department';
        $payrollProjectId = isset($_GET['payroll_project_id']) && $_GET['payroll_project_id'] !== '' ? (int) $_GET['payroll_project_id'] : null;

        if (!$payrollProjectId) {
            echo "Payroll Project is required for CSV export.";
            exit;
        }

        $projName = "None";
        $projRes = $this->db->query("SELECT name FROM payroll_projects WHERE id = $payrollProjectId");
        if ($projRes && $pRow = $projRes->fetch_assoc()) {
            $projName = $pRow['name'];
        }

        $data = [];
        if ($reportType === 'department') {
            $sql = "SELECT 
                        d.name AS dept_name, 
                        COUNT(p.id) AS no_of_emp,
                        SUM(p.net_salary) AS total_amount
                    FROM payslips p
                    JOIN employees e ON p.employee_id = e.id
                    JOIN departments d ON e.department_id = d.id
                    WHERE p.payroll_project_id = ? AND e.employee_status = 'active' 
                      AND p.status != 'cancelled'
                    GROUP BY d.id
                    ORDER BY d.name ASC";
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("i", $payrollProjectId);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res) {
                    while ($row = $res->fetch_assoc()) {
                        $data[] = $row;
                    }
                }
                $stmt->close();
            }
        } else {
            $sql = "SELECT 
                        IFNULL(ob.name, 'Unassigned') AS office_name, 
                        COUNT(p.id) AS no_of_emp,
                        SUM(p.net_salary) AS total_amount,
                        SUM(CASE WHEN LOWER(p.payment_method) LIKE '%bank%' THEN p.net_salary ELSE 0 END) AS bank_amount,
                        SUM(CASE WHEN LOWER(p.payment_method) LIKE '%cash%' THEN p.net_salary ELSE 0 END) AS cash_amount
                    FROM payslips p
                    JOIN employees e ON p.employee_id = e.id
                    LEFT JOIN office_branches ob ON e.office_branch_id = ob.id
                    WHERE p.payroll_project_id = ? AND e.employee_status = 'active' 
                      AND p.status != 'cancelled'
                    GROUP BY ob.id
                    ORDER BY office_name ASC";
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("i", $payrollProjectId);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res) {
                    while ($row = $res->fetch_assoc()) {
                        $data[] = $row;
                    }
                }
                $stmt->close();
            }
        }

        $filename = "Salary_Report_" . ucfirst($reportType) . "_" . preg_replace('/[^a-zA-Z0-9]/', '_', $projName) . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        $output = fopen('php://output', 'w');

        if ($reportType === 'department') {
            fputcsv($output, ['Serial', 'Department Name', 'No of Employees', 'Amount']);
            $sl = 1;
            foreach ($data as $row) {
                fputcsv($output, [
                    $sl++,
                    $row['dept_name'],
                    $row['no_of_emp'],
                    number_format($row['total_amount'], 2, '.', '')
                ]);
            }
        } else {
            fputcsv($output, ['Office Name', 'No of Employees', 'Total', 'Bank', 'Cash']);
            foreach ($data as $row) {
                fputcsv($output, [
                    $row['office_name'],
                    $row['no_of_emp'],
                    number_format($row['total_amount'], 2, '.', ''),
                    number_format($row['bank_amount'], 2, '.', ''),
                    number_format($row['cash_amount'], 2, '.', '')
                ]);
            }
        }
        fclose($output);
        exit;
    }

    public function salaryHoldPage()
    {
        $currentYear = date('Y');
        $currentMonth = date('m');

        $selectedYear = isset($_GET['year']) ? $_GET['year'] : $currentYear;
        $selectedMonth = isset($_GET['month']) ? $_GET['month'] : $currentMonth;
        $selectedProject = isset($_GET['payroll_project_id']) ? $_GET['payroll_project_id'] : '';

        // Fetch payroll projects
        $projectRes = $this->db->query("SELECT * FROM payroll_projects ORDER BY id DESC");
        $payroll_projects = [];
        if ($projectRes) {
            while ($pRow = $projectRes->fetch_assoc()) {
                $payroll_projects[] = $pRow;
            }
        }

        // Fetch all active employees (for the select dropdown)
        $empRes = $this->db->query("SELECT id, employee_id, full_name, phone FROM employees WHERE employee_status = 'active' ORDER BY employee_id ASC");
        $employeesList = [];
        if ($empRes) {
            while ($eRow = $empRes->fetch_assoc()) {
                $employeesList[] = $eRow;
            }
        }

        $holds = [];
        if ($selectedProject && $selectedYear && $selectedMonth) {
            $monthStr = $selectedYear . '-' . str_pad($selectedMonth, 2, "0", STR_PAD_LEFT);
            $query = "
                SELECT p.id as payslip_id, e.employee_id, e.full_name, dept.name as department_name, desg.name as designation_name
                FROM payslips p
                JOIN employees e ON p.employee_id = e.id
                LEFT JOIN departments dept ON e.department_id = dept.id
                LEFT JOIN designations desg ON e.designation_id = desg.id
                WHERE p.payroll_project_id = " . (int) $selectedProject . " 
                  AND YEAR(p.payslip_month) = '$selectedYear' 
                  AND MONTH(p.payslip_month) = '$selectedMonth'
                  AND LOWER(p.salary_hold) = 'hold'
            ";
            $res = $this->db->query($query);
            if ($res) {
                while ($h = $res->fetch_assoc()) {
                    $holds[] = $h;
                }
            }
        }

        $this->view("payrolls/payslips/salary_hold", [
            "current_route" => "payroll-salary-hold",
            "page_title" => "Salary Hold Management",
            "payroll_projects" => $payroll_projects,
            "employeesList" => $employeesList,
            "holds" => $holds,
            "selectedYear" => $selectedYear,
            "selectedMonth" => $selectedMonth,
            "selectedProject" => $selectedProject
        ]);
    }

    public function salaryHoldSave()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || empty($input['project_id']) || empty($input['year']) || empty($input['month']) || empty($input['employee_id'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid or missing data.']);
            return;
        }

        $projectId = (int) $input['project_id'];
        $year = (int) $input['year'];
        $month = (int) $input['month'];
        $employeeId = (int) $input['employee_id'];
        $payslipMonth = $year . '-' . str_pad($month, 2, "0", STR_PAD_LEFT) . '-01';

        // Check if payslip already exists
        $checkStmt = $this->db->prepare("SELECT id FROM payslips WHERE employee_id = ? AND payroll_project_id = ? AND YEAR(payslip_month) = ? AND MONTH(payslip_month) = ?");
        $checkStmt->bind_param("iiii", $employeeId, $projectId, $year, $month);
        $checkStmt->execute();
        $res = $checkStmt->get_result();

        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $payslipId = $row['id'];
            $updateStmt = $this->db->prepare("UPDATE payslips SET salary_hold = 'Hold' WHERE id = ?");
            $updateStmt->bind_param("i", $payslipId);
            $updateStmt->execute();
            $updateStmt->close();
        } else {
            $insertStmt = $this->db->prepare("INSERT INTO payslips (employee_id, payroll_project_id, payslip_month, salary_hold) VALUES (?, ?, ?, 'Hold')");
            $insertStmt->bind_param("iis", $employeeId, $projectId, $payslipMonth);
            $insertStmt->execute();
            $insertStmt->close();
        }
        $checkStmt->close();

        echo json_encode(['success' => true, 'message' => 'Employee salary marked on Hold successfully.']);
    }

    public function salaryHoldRemove()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || empty($input['payslip_id'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid or missing payslip ID.']);
            return;
        }

        $payslipId = (int) $input['payslip_id'];
        $updateStmt = $this->db->prepare("UPDATE payslips SET salary_hold = '' WHERE id = ?");
        $updateStmt->bind_param("i", $payslipId);
        if ($updateStmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Salary Hold removed.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove Salary Hold.']);
        }
        $updateStmt->close();
    }
}
