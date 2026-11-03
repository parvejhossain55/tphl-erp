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
        $selectedYear = isset($_GET['year']) && $_GET['year'] !== '' ? $_GET['year'] : $currentYear;
        $selectedMonth = isset($_GET['month']) && $_GET['month'] !== '' ? $_GET['month'] : $currentMonth;
        $selectedStatus = isset($_GET['status']) ? $_GET['status'] : '';
        $selectedProjectId = isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int) $_GET['project_id'] : null;

        // Fetch payroll projects
        $projectRes = $this->db->query('SELECT * FROM payroll_projects ORDER BY id DESC');
        $payroll_projects = [];
        if ($projectRes) {
            while ($pRow = $projectRes->fetch_assoc()) {
                $payroll_projects[] = $pRow;
            }
        }

        // If project selected, auto-sync year and month if not explicitly overridden
        if ($selectedProjectId) {
            $projQuery = $this->db->query('SELECT * FROM payroll_projects WHERE id = ' . $selectedProjectId);
            if ($projQuery && $projData = $projQuery->fetch_assoc()) {
                if (empty($_GET['year']) || empty($_GET['month'])) {
                    if (!empty($projData['is_custom_date']) && !empty($projData['start_date'])) {
                        $selectedYear = date('Y', strtotime($projData['start_date']));
                        $selectedMonth = date('m', strtotime($projData['start_date']));
                    } elseif (!empty($projData['date'])) {
                        $parts = explode('-', $projData['date']);
                        if (count($parts) >= 2) {
                            $selectedYear = $parts[0];
                            $selectedMonth = $parts[1];
                        }
                    }
                }
            }
        }

        // Fetch payslips with status and project filter
        $payslips = $this->paymentModel->getAllWithEmployeeFiltered($selectedYear, $selectedMonth, $selectedStatus, $selectedProjectId);

        $totalEmployees = $this->employeeModel->getTotalCount();
        $paidCount = $this->paymentModel->getPaidCount($selectedYear, $selectedMonth, $selectedProjectId);
        $paidSalarySum = $this->paymentModel->getPaidSalarySum($selectedYear, $selectedMonth, $selectedProjectId);
        $unpaidSalarySum = $this->paymentModel->getUnpaidSalarySum($selectedYear, $selectedMonth, $selectedProjectId);

        if ($selectedProjectId) {
            $pendingCount = $this->paymentModel->getPendingCount($selectedYear, $selectedMonth, $selectedProjectId);
        } else {
            $pendingCount = $totalEmployees - $paidCount;
        }

        $companyBanks = $this->bankModel->getAll();

        // Fetch all local employee IDs, names, departments, and designations for Tipsoi matching
        $localQuery = $this->db->query('
            SELECT e.employee_id, e.full_name, dept.name AS department_name, desg.name AS designation_name 
            FROM employees AS e
            LEFT JOIN departments AS dept ON e.department_id = dept.id
            LEFT JOIN designations AS desg ON e.designation_id = desg.id
        ');
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

        // Fetch draft salary certificates (only shown when All Projects is selected / no specific project)
        $draftPayslips = [];
        if (empty($selectedProjectId)) {
            $draftPayslips = $this->paymentModel->getDraftSalaryCertificates($selectedYear, $selectedMonth);
        }

        $this->view('payrolls/payslips/payslip-lists', [
            'current_route' => 'payslip-lists',
            'page_title' => 'Payroll Lists',
            'payslips' => $payslips,
            'draftPayslips' => $draftPayslips,
            'totalEmployees' => $totalEmployees,
            'paidCount' => $paidCount,
            'pendingCount' => $pendingCount,
            'paidSalarySum' => $paidSalarySum,
            'unpaidSalarySum' => $unpaidSalarySum,
            'companyBanks' => $companyBanks,
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'selectedStatus' => $selectedStatus,
            'payroll_projects' => $payroll_projects,
            'selectedProjectId' => $selectedProjectId,
            'allLocalEmployees' => $allLocalEmployees,
        ]);
    }

    public function create()
    {
        $selectedYear = isset($_GET['year']) && $_GET['year'] !== '' ? (int) $_GET['year'] : (int) date('Y');
        $selectedMonth = isset($_GET['month']) && $_GET['month'] !== '' ? (int) $_GET['month'] : (int) date('m');
        $defaultPaydate = sprintf('%04d-%02d-01', $selectedYear, $selectedMonth);

        $employees = $this->employeeModel->getAllWithDesignation();
        $items = $this->payslipItemModel->getAll();

        $salaryItemsByEmployee = [];
        $salaryItemsQuery = $this->db->query('SELECT employee_id, payslip_item_id, amount FROM employee_salary_items');
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
        $currentMonthStr = sprintf('%04d-%02d', $selectedYear, $selectedMonth);
        foreach ($employees as &$emp) {
            $emp['advance_salary'] = $advanceSalaryModel->getMonthlyDeductionAmount($emp['id'], $currentMonthStr);
        }
        unset($emp);

        $this->view('payrolls/payslips/create-payslip', [
            'current_route' => 'create-payslip',
            'page_title' => 'Create Payslip',
            'employees' => $employees,
            'items' => $items,
            'defaultPaydate' => $defaultPaydate,
            'hasExplicitDate' => (isset($_GET['year']) && $_GET['year'] !== '') || (isset($_GET['month']) && $_GET['month'] !== ''),
            'preselectedEmployeeId' => isset($_GET['employee_id']) ? (int) $_GET['employee_id'] : null,
            'coaList' => getCOAExpenseList($this->db)
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

        $isDraft = (!empty($payload['is_draft']) && $payload['is_draft'] == 1) ||
            (!empty($_POST['is_draft']) && $_POST['is_draft'] == 1) ||
            (!empty($_POST['submit_action']) && $_POST['submit_action'] === 'draft');

        $status = 'pending';
        if (!empty($_POST['submit_action']) && $_POST['submit_action'] === 'on_hold') {
            $status = 'on_hold';
        } elseif ($isDraft) {
            $status = 'draft';
        }

        $paydate = !empty($payload['paydate']) ? $payload['paydate'] : (!empty($_POST['paydate']) ? $_POST['paydate'] : date('Y-m-d'));
        $payslipMonth = date('Y-m-01', strtotime($paydate));

        $payslipId = $payslipModel->create([
            'employee_id' => $payload['employee_id'],
            'payroll_project_id' => null,  // Standalone salary certificate / manual payslip - independent of projects
            'designation_id' => $payload['designation_id'],
            'payslip_month' => $payslipMonth,
            'total_earning' => $payload['total_earning'],
            'total_deduction' => $payload['total_deduction'],
            'net_salary' => $payload['net_salary'],
            'status' => $status,
            'upload_by' => 'manual',
            'created_by' => $_SESSION['user']['id'],
            'account_id' => !empty($_POST['account_id']) ? (int) $_POST['account_id'] : null,
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
                $stmtItem = $this->db->prepare('SELECT name FROM payslip_items WHERE id = ?');
                $stmtItem->bind_param('i', $itemId);
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
            $_SESSION['success'] = 'Payslip deleted successfully.';
        }

        header('Location: ' . $GLOBALS['base_url'] . '/payslip-lists');
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
            $_SESSION['error'] = 'Payslip ID or payment method missing.';
            header('Location: ' . $GLOBALS['base_url'] . '/payslip-lists');
            exit;
        }

        $companyBankId = $companyBankId ?: null;  // nullable

        // Mandatory Accounts Head Approval Guard
        if (!class_exists('ApprovalRequest')) {
            require_once __DIR__ . '/../models/ApprovalRequest.php';
        }
        $ahCheck = ApprovalRequest::getAccountsHeadApprovalInfo($this->db, 'payslips', (int) $payslipId);
        if ($ahCheck && !empty($ahCheck['has_request']) && empty($ahCheck['is_accounts_head_approved'])) {
            $_SESSION['error'] = 'Payment cannot be disbursed. Approval from Accounts Head (Md. Mamunoor Rashid [1309]) is pending.';
            header('Location: ' . $GLOBALS['base_url'] . '/payslip-lists');
            exit;
        }

        $success = $this->paymentModel->payPayslip($payslipId, $paymentMethod, $companyBankId);

        if ($success) {
            $_SESSION['success'] = 'Payslip paid successfully.';
        } else {
            $_SESSION['error'] = 'Failed to update payslip. Please try again.';
        }

        header('Location: ' . $GLOBALS['base_url'] . '/payslip-lists');
        exit;
    }

    public function show($id)
    {
        $payslip = $this->paymentModel->getByIdWithItems($id);

        $this->view('payrolls/payslips/show-payslip', [
            'current_route' => 'show-payslip',
            'page_title' => 'Payslip Details',
            'payslip' => $payslip
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
        $this->view('payrolls/payslips/import-payslips', [
            'current_route' => 'payslip-lists',
            'page_title' => 'Import Payslips'
        ]);
    }

    public function importCSV()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Please select a valid CSV file.';
            header('Location: ' . $GLOBALS['base_url'] . '/import-payslips');
            exit;
        }

        $file = $_FILES['csv_file']['tmp_name'];
        if (($handle = fopen($file, 'r')) !== FALSE) {
            $header = fgetcsv($handle, 1000, ',');
            if (!$header) {
                $_SESSION['error'] = 'Empty CSV file.';
                header('Location: ' . $GLOBALS['base_url'] . '/import-payslips');
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
                    $_SESSION['error'] = 'Missing required column in CSV: ' . $req;
                    header('Location: ' . $GLOBALS['base_url'] . '/import-payslips');
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
                $stmt = $this->db->prepare('SELECT id FROM payslip_items WHERE name = ? LIMIT 1');
                $stmt->bind_param('s', $info['name']);
                $stmt->execute();
                $res = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($res) {
                    $itemIds[$idx] = $res['id'];
                } else {
                    $stmtCreate = $this->db->prepare('INSERT INTO payslip_items (name, type, created_by, created_at) VALUES (?, ?, ?, NOW())');
                    $createdBy = $_SESSION['user']['id'] ?? 1;
                    $stmtCreate->bind_param('ssi', $info['name'], $info['type'], $createdBy);
                    $stmtCreate->execute();
                    $itemIds[$idx] = $this->db->insert_id;
                    $stmtCreate->close();
                }
            }

            $rowNum = 1;
            $errors = [];

            while (($row = fgetcsv($handle, 1000, ',')) !== FALSE) {
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
                $stmt = $this->db->prepare('SELECT id, designation_id FROM employees WHERE email = ? LIMIT 1');
                $stmt->bind_param('s', $email);
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

                    $bankSearch = '%' . $companyBankName . '%';
                    $stmt = $this->db->prepare("SELECT id FROM company_bank_accounts WHERE bank_name LIKE ? AND status = 'active' LIMIT 1");
                    $stmt->bind_param('s', $bankSearch);
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
                $_SESSION['error'] = implode('<br>', $errors);
                header('Location: ' . $GLOBALS['base_url'] . '/import-payslips');
                exit;
            } else {
                $this->db->commit();
                $_SESSION['success'] = 'All payslips imported and paid successfully!';
                header('Location: ' . $GLOBALS['base_url'] . '/payslip-lists');
                exit;
            }
        } else {
            $_SESSION['error'] = 'Failed to open CSV file.';
            header('Location: ' . $GLOBALS['base_url'] . '/import-payslips');
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
            $headers[] = $item['name'] . ' (' . $item['type'] . ')';
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

        $projectRes = $this->db->query('SELECT * FROM payroll_projects ORDER BY id DESC');
        $payroll_projects = [];
        if ($projectRes) {
            while ($pRow = $projectRes->fetch_assoc()) {
                $payroll_projects[] = $pRow;
            }
        }

        $items = $this->payslipItemModel->getAll();
        $selected_project_id = isset($_GET['project_id']) ? (int) $_GET['project_id'] : 0;

        $this->view('payrolls/payslips/auto-create-payslip', [
            'current_route' => 'auto-generate-payslip',
            'page_title' => 'Auto Generate Payslips',
            'departments' => $departments,
            'payroll_projects' => $payroll_projects,
            'selected_project_id' => $selected_project_id,
            'items' => $items
        ]);
    }

    public function getDepartmentAttendance()
    {
        header('Content-Type: application/json');

        $departmentId = isset($_GET['department_id']) ? (int) $_GET['department_id'] : 0;
        $projectId = isset($_GET['project_id']) ? (int) $_GET['project_id'] : 0;
        $monthStr = isset($_GET['month']) ? trim($_GET['month']) : '';  // e.g. "2026-07"

        if (!$projectId) {
            echo json_encode(['success' => false, 'message' => 'Payroll Project is required.']);
            exit;
        }

        if (!$monthStr) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters. Monthly parameter is required.']);
            exit;
        }

        // Check if Payroll Workplace data has been saved for this project
        $hasSavedWorkspace = false;
        $psCheck = $this->db->query("SELECT COUNT(*) as cnt FROM payslips WHERE payroll_project_id = $projectId");
        if ($psCheck && ($row = $psCheck->fetch_assoc()) && $row['cnt'] > 0) {
            $hasSavedWorkspace = true;
        }

        if (!$hasSavedWorkspace) {
            echo json_encode([
                'success' => false,
                'message' => 'No saved workspace data found for this Payroll Project. Please review and click "Save Changes" in Payroll Workplace first before generating payslips.'
            ]);
            exit;
        }

        // 1. Fetch only employees that were saved in the workspace and NOT deselected (is_excluded = 0)
        $sql = "
            SELECT e.id, e.full_name, e.employee_id, e.email, e.gross_salary, e.basic_salary, e.designation_id,
                   e.workplace, e.joining_date, e.per_meal_amount, e.account_number, e.bank_name, e.account_holder_name,
                   IF(e.account_number IS NOT NULL AND TRIM(e.account_number) != '', 'bank', 'cash') AS payment_method,
                   desg.name AS designation_name,
                   dept.name AS department_name,
                   p.id AS saved_payslip_id,
                   p.status AS saved_status,
                   p.is_excluded,
                   p.recreation_allowance,
                   p.conveyance_allowance,
                   p.medical_allowance,
                   p.house_rent_allowance,
                   p.advance_salary,
                   p.meal_count,
                   p.subsidized_lunch,
                   p.late_unpaid_absent_days,
                   p.unpaid_absent_amount,
                   p.arrears_deduction,
                   p.others_deduction,
                   p.punctuality_bonus,
                   p.overtime,
                   p.arrears_earning,
                   p.duty_allowance,
                   p.tax,
                   p.bank_amount,
                   p.cash_amount,
                   p.salary_hold,
                   p.net_salary,
                   p.total_earning,
                   p.total_deduction,
                   p.remarks,
                   p.attendance_remarks
            FROM payslips p
            JOIN employees e ON p.employee_id = e.id
            LEFT JOIN designations desg ON e.designation_id = desg.id
            LEFT JOIN departments dept ON e.department_id = dept.id
            WHERE p.payroll_project_id = $projectId
              AND (p.is_excluded = 0 OR p.is_excluded IS NULL)
        ";
        if ($departmentId > 0) {
            $sql .= " AND e.department_id = $departmentId";
        }
        $sql .= ' ORDER BY e.employee_id ASC';

        $res = $this->db->query($sql);
        $employees = [];
        if ($res) {
            while ($emp = $res->fetch_assoc()) {
                $employees[] = $emp;
            }
        }

        if (empty($employees)) {
            echo json_encode([
                'success' => false,
                'message' => 'All employees in this project were either deselected in the workspace or belong to other departments.'
            ]);
            exit;
        }

        $allPayslipItems = $this->payslipItemModel->getAll();

        // 2. Fetch saved itemized payslip_details for each employee
        foreach ($employees as &$emp) {
            $emp['configured_items'] = [];
            $payslipId = (int) ($emp['saved_payslip_id'] ?? 0);
            if ($payslipId > 0) {
                $detRes = $this->db->query("SELECT payslip_item_id, amount FROM payslip_details WHERE payslip_id = $payslipId");
                if ($detRes) {
                    while ($dRow = $detRes->fetch_assoc()) {
                        $emp['configured_items'][$dRow['payslip_item_id']] = (float) $dRow['amount'];
                    }
                }
            }

            // Sync attendance and meal fields from workspace saved data
            $emp['matched_in_tipsoi'] = true;
            $emp['absent_days'] = (float) ($emp['late_unpaid_absent_days'] ?? 0);
            $emp['late_unpaid_absent_days'] = (float) ($emp['late_unpaid_absent_days'] ?? 0);
            $emp['tipsoi_present'] = (float) ($emp['meal_count'] ?? 0);
            $emp['absent_deduction'] = (float) ($emp['unpaid_absent_amount'] ?? 0);
        }
        unset($emp);

        echo json_encode([
            'success' => true,
            'employees' => $employees,
            'payslip_items' => $allPayslipItems
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

        $payslipMonth = $payload['payslip_month'] . '-01';  // Format as YYYY-MM-01
        $payslipDate = !empty($payload['payslip_date']) ? $payload['payslip_date'] : date('Y-m-d');
        $payrollProjectId = (int) $payload['payroll_project_id'];
        $generatedBy = $_SESSION['user']['id'] ?? 1;

        $detailsModel = new PayslipDetail($this->db);
        $approvalModel = new ApprovalRequest($this->db);

        // Begin Transaction
        $this->db->begin_transaction();

        $generatedCount = 0;
        $skippedCount = 0;
        $skippedNames = [];

        try {
            foreach ($payload['employees'] as $empData) {
                // Check if employee has a payslip for this month and project
                $stmtCheck = $this->db->prepare("
                    SELECT id, status FROM payslips WHERE employee_id = ? AND DATE_FORMAT(payslip_month, '%Y-%m') = ? AND payroll_project_id = ? LIMIT 1
                ");
                $monthStr = date('Y-m', strtotime($payslipMonth));
                $stmtCheck->bind_param('isi', $empData['employee_id'], $monthStr, $payrollProjectId);
                $stmtCheck->execute();
                $exists = $stmtCheck->get_result()->fetch_assoc();
                $stmtCheck->close();

                // If already marked as paid, skip it so we don't accidentally overwrite a disbursed payslip
                if ($exists && $exists['status'] === 'paid') {
                    $skippedCount++;
                    $skippedNames[] = $empData['employee_name'] . ' (Already Paid)';
                    continue;
                }

                $employeeId = (int) $empData['employee_id'];
                $designationId = (int) ($empData['designation_id'] ?? 0);
                $totalEarning = (float) ($empData['total_earning'] ?? 0);
                $totalDeduction = (float) ($empData['total_deduction'] ?? 0);
                $netSalary = (float) ($empData['net_salary'] ?? 0);
                $salaryHoldString = trim((string) ($empData['salary_hold'] ?? ''));
                $bankAmount = (float) ($empData['bank_amount'] ?? 0);
                $cashAmount = (float) ($empData['cash_amount'] ?? 0);
                $lateUnpaidAbsentDays = (float) ($empData['late_unpaid_absent_days'] ?? $empData['absent_days'] ?? 0);
                $unpaidAbsentAmount = (float) ($empData['unpaid_absent_amount'] ?? 0);
                $recreationAllowance = (float) ($empData['recreation_allowance'] ?? 0);
                $conveyanceAllowance = (float) ($empData['conveyance_allowance'] ?? 0);
                $medicalAllowance = (float) ($empData['medical_allowance'] ?? 0);
                $houseRentAllowance = (float) ($empData['house_rent_allowance'] ?? 0);
                $advanceSalary = (float) ($empData['advance_salary'] ?? 0);
                $mealCount = (float) ($empData['meal_count'] ?? 0);
                $subsidizedLunch = (float) ($empData['subsidized_lunch'] ?? 0);
                $arrearsDeduction = (float) ($empData['arrears_deduction'] ?? 0);
                $othersDeduction = (float) ($empData['others_deduction'] ?? 0);
                $punctualityBonus = (float) ($empData['punctuality_bonus'] ?? 0);
                $overtime = (float) ($empData['overtime'] ?? 0);
                $arrearsEarning = (float) ($empData['arrears_earning'] ?? 0);
                $dutyAllowance = (float) ($empData['duty_allowance'] ?? 0);
                $tax = (float) ($empData['tax'] ?? 0);
                $remarks = trim((string) ($empData['remarks'] ?? ''));
                $attRemarks = trim((string) ($empData['attendance_remarks'] ?? ''));

                if ($exists) {
                    $payslipId = (int) $exists['id'];
                    $stmtUp = $this->db->prepare("
                        UPDATE payslips SET
                            status = 'pending',
                            is_excluded = 0,
                            designation_id = ?,
                            payslip_month = ?,
                            total_earning = ?,
                            total_deduction = ?,
                            net_salary = ?,
                            salary_hold = ?,
                            bank_amount = ?,
                            cash_amount = ?,
                            late_unpaid_absent_days = ?,
                            remarks = ?,
                            attendance_remarks = ?,
                            recreation_allowance = ?,
                            conveyance_allowance = ?,
                            medical_allowance = ?,
                            house_rent_allowance = ?,
                            advance_salary = ?,
                            meal_count = ?,
                            subsidized_lunch = ?,
                            unpaid_absent_amount = ?,
                            arrears_deduction = ?,
                            others_deduction = ?,
                            punctuality_bonus = ?,
                            overtime = ?,
                            arrears_earning = ?,
                            duty_allowance = ?,
                            tax = ?,
                            created_by = ?
                        WHERE id = ?
                    ");
                    if (!$stmtUp) {
                        throw new Exception('Prepare update failed: ' . $this->db->error);
                    }
                    $stmtUp->bind_param(
                        'isdddsdddssddddddddddddddddii',
                        $designationId,
                        $payslipMonth,
                        $totalEarning,
                        $totalDeduction,
                        $netSalary,
                        $salaryHoldString,
                        $bankAmount,
                        $cashAmount,
                        $lateUnpaidAbsentDays,
                        $remarks,
                        $attRemarks,
                        $recreationAllowance,
                        $conveyanceAllowance,
                        $medicalAllowance,
                        $houseRentAllowance,
                        $advanceSalary,
                        $mealCount,
                        $subsidizedLunch,
                        $unpaidAbsentAmount,
                        $arrearsDeduction,
                        $othersDeduction,
                        $punctualityBonus,
                        $overtime,
                        $arrearsEarning,
                        $dutyAllowance,
                        $tax,
                        $generatedBy,
                        $payslipId
                    );
                    $stmtUp->execute();
                    $stmtUp->close();
                } else {
                    $stmtIns = $this->db->prepare("
                        INSERT INTO payslips (
                            employee_id, payroll_project_id, designation_id, payslip_month,
                            status, is_excluded, upload_by, created_by,
                            total_earning, total_deduction, net_salary,
                            salary_hold, bank_amount, cash_amount, late_unpaid_absent_days,
                            remarks, attendance_remarks,
                            recreation_allowance, conveyance_allowance, medical_allowance, house_rent_allowance,
                            advance_salary, meal_count, subsidized_lunch, unpaid_absent_amount,
                            arrears_deduction, others_deduction,
                            punctuality_bonus, overtime, arrears_earning, duty_allowance,
                            tax
                        ) VALUES (
                            ?, ?, ?, ?,
                            'pending', 0, 'manual', ?,
                            ?, ?, ?,
                            ?, ?, ?, ?,
                            ?, ?,
                            ?, ?, ?, ?,
                            ?, ?, ?, ?,
                            ?, ?,
                            ?, ?, ?, ?,
                            ?
                        )
                    ");
                    if (!$stmtIns) {
                        throw new Exception('Prepare insert failed: ' . $this->db->error);
                    }
                    $stmtIns->bind_param(
                        'iiisidddsdddssdddddddddddddddd',
                        $employeeId,
                        $payrollProjectId,
                        $designationId,
                        $payslipMonth,
                        $generatedBy,
                        $totalEarning,
                        $totalDeduction,
                        $netSalary,
                        $salaryHoldString,
                        $bankAmount,
                        $cashAmount,
                        $lateUnpaidAbsentDays,
                        $remarks,
                        $attRemarks,
                        $recreationAllowance,
                        $conveyanceAllowance,
                        $medicalAllowance,
                        $houseRentAllowance,
                        $advanceSalary,
                        $mealCount,
                        $subsidizedLunch,
                        $unpaidAbsentAmount,
                        $arrearsDeduction,
                        $othersDeduction,
                        $punctualityBonus,
                        $overtime,
                        $arrearsEarning,
                        $dutyAllowance,
                        $tax
                    );
                    $stmtIns->execute();
                    $payslipId = $stmtIns->insert_id;
                    $stmtIns->close();
                }

                if (!$payslipId) {
                    throw new Exception('Failed to persist payslip for ' . $empData['employee_name'] . '. DB Error: ' . $this->db->error);
                }

                // 2. Refresh payslip_details
                $this->db->query("DELETE FROM payslip_details WHERE payslip_id = $payslipId");
                if (!empty($empData['earnings'])) {
                    foreach ($empData['earnings'] as $itemId => $amount) {
                        $amt = (float) $amount;
                        if ($amt > 0) {
                            $detailsModel->create($payslipId, (int) $itemId, $amt);
                        }
                    }
                }
                if (!empty($empData['deductions'])) {
                    foreach ($empData['deductions'] as $itemId => $amount) {
                        $amt = (float) $amount;
                        if ($amt > 0) {
                            $detailsModel->create($payslipId, (int) $itemId, $amt);
                        }
                    }
                }

                // 3. Handle Advance Salary deduction
                if ($advanceSalary > 0) {
                    if (!class_exists('AdvanceSalary')) {
                        require_once __DIR__ . '/../models/AdvanceSalary.php';
                    }
                    $advanceSalaryModel = new AdvanceSalary($this->db);
                    $chkAdv = $this->db->query("SELECT id FROM advance_salaries WHERE deducted_payslip_id = $payslipId");
                    if (!$chkAdv || $chkAdv->num_rows == 0) {
                        $advanceRequests = $advanceSalaryModel->getPaidAdvancesByEmployee($employeeId);
                        $remainingDeduction = $advanceSalary;
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

                // 4. Create or Update Approval Request
                $chkAppr = $this->db->query("SELECT id FROM approval_requests WHERE source_table = 'payslips' AND source_id = $payslipId LIMIT 1");
                if ($chkAppr && $rowAppr = $chkAppr->fetch_assoc()) {
                    $apprId = (int) $rowAppr['id'];
                    $stmtApprUp = $this->db->prepare('UPDATE approval_requests SET request_amount = ?, meta_data = ? WHERE id = ?');
                    $metaData = json_encode([
                        'employee_name' => $empData['employee_name'],
                        'net_salary' => $netSalary,
                        'month' => $payslipMonth
                    ]);
                    $stmtApprUp->bind_param('dsi', $netSalary, $metaData, $apprId);
                    $stmtApprUp->execute();
                    $stmtApprUp->close();
                } else {
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
                            'net_salary' => $netSalary,
                            'month' => $payslipMonth
                        ]),
                        'request_amount' => $netSalary
                    ]);
                }

                $generatedCount++;
            }

            // Commit Transaction
            $this->db->commit();

            if ($generatedCount === 0 && $skippedCount > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'No payslips were generated. Selected employees already have finalized/paid payslips: ' . implode(', ', $skippedNames)
                ]);
            } else {
                $msg = "Generated $generatedCount payslips successfully and submitted for approval.";
                if ($skippedCount > 0) {
                    $msg .= " Skipped $skippedCount employees who were already paid: " . implode(', ', $skippedNames);
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

        // Mandatory Accounts Head Approval Guard
        $idListStr = implode(',', $payslipIds);
        $checkAh = $this->db->query("
            SELECT ar.source_id 
            FROM approval_requests ar 
            WHERE ar.source_table = 'payslips' 
              AND ar.source_id IN ($idListStr) 
              AND ar.accounts_head_status != 'approved'
        ");
        if ($checkAh && $checkAh->num_rows > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Payment cannot be disbursed. One or more selected payslips are pending approval from Accounts Head (Md. Mamunoor Rashid [1309]).'
            ]);
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
                $stmtBank = $this->db->prepare('SELECT bank_name, account_name, account_number FROM company_bank_accounts WHERE id = ?');
                $stmtBank->bind_param('i', $bankAccountId);
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
            $narration = "Payroll Disbursement for $monthText via $bankName (Count: " . count($payslipsToPay) . ' payslips)';

            $stmtJ = $this->db->prepare("
                INSERT INTO journals 
                (journal_number, date, voucher_type, narration, total_debit, total_credit, status, created_by, created_at, source_module, source_ref_id, entry_type, approval_status)
                VALUES (?, ?, ?, ?, ?, ?, 'posted', ?, NOW(), 'payroll', 0, 'disbursement', 'approved')
            ");
            $voucherType = 'Payment';
            $stmtJ->bind_param('ssssddi', $journalNum, $sampleDate, $voucherType, $narration, $totalAmount, $totalAmount, $userId);
            $stmtJ->execute();
            $journalId = $stmtJ->insert_id;
            $stmtJ->close();

            // 2. Insert Debit & Credit details in 'journal_details'
            // Debit: Salaries & Wages (580003)
            $stmtD1 = $this->db->prepare('
                INSERT INTO journal_details (journal_id, account_id, debit, credit, narration)
                VALUES (?, ?, ?, 0.00, ?)
            ');
            $narrDebit = "Salaries & Wages Expense for $monthText";
            $stmtD1->bind_param('iids', $journalId, $salaryCoaId, $totalAmount, $narrDebit);
            $stmtD1->execute();
            $stmtD1->close();

            // Credit: Cash at Bank (114400) or Head Office Cash (114100-03)
            $stmtD2 = $this->db->prepare('
                INSERT INTO journal_details (journal_id, account_id, debit, credit, narration)
                VALUES (?, ?, 0.00, ?, ?)
            ');
            $narrCredit = "Disbursed via $bankName for $monthText Payroll";
            $stmtD2->bind_param('iids', $journalId, $payCoaId, $totalAmount, $narrCredit);
            $stmtD2->execute();
            $stmtD2->close();

            // 3. Update payslips status to PAID & link journal_voucher_id
            $stmtUpd = $this->db->prepare("
                UPDATE payslips 
                SET status = 'paid', payment_status = 'paid', bank_account_id = ?, disbursed_by = ?, disbursed_at = NOW(), journal_voucher_id = ?
                WHERE id = ?
            ");
            foreach ($payslipsToPay as $p) {
                $stmtUpd->bind_param('iiii', $bankAccountId, $userId, $journalId, $p['id']);
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

    /**
     * Send payslip email to an individual employee
     */
    public function sendSinglePayslipEmail()
    {
        header('Content-Type: application/json');
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $input = file_get_contents('php://input');
        $payload = json_decode($input, true);
        $payslipId = (int) ($payload['payslip_id'] ?? ($_POST['payslip_id'] ?? 0));

        if ($payslipId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid payslip ID provided.']);
            exit;
        }

        $userId = (int) ($_SESSION['user']['id'] ?? 1);
        require_once __DIR__ . '/../services/PayrollMailService.php';
        $mailService = new PayrollMailService($this->db);
        $result = $mailService->sendPayslipEmail($payslipId, $userId);

        echo json_encode($result);
        exit;
    }

    /**
     * Send payslip emails in bulk to selected employees
     */
    public function sendBulkPayslipEmail()
    {
        header('Content-Type: application/json');
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $input = file_get_contents('php://input');
        $payload = json_decode($input, true);
        $payslipIds = $payload['payslip_ids'] ?? [];

        if (empty($payslipIds) || !is_array($payslipIds)) {
            echo json_encode(['success' => false, 'message' => 'No payslips selected for sending email.']);
            exit;
        }

        $payslipIds = array_filter(array_map('intval', $payslipIds));
        if (empty($payslipIds)) {
            echo json_encode(['success' => false, 'message' => 'Invalid payslip IDs selected.']);
            exit;
        }

        $userId = (int) ($_SESSION['user']['id'] ?? 1);
        require_once __DIR__ . '/../services/PayrollMailService.php';
        $mailService = new PayrollMailService($this->db);

        $sentCount = 0;
        $skippedCount = 0;
        $failedCount = 0;
        $details = [];

        foreach ($payslipIds as $id) {
            $res = $mailService->sendPayslipEmail($id, $userId);
            if ($res['success']) {
                $sentCount++;
            } else {
                if (stripos($res['message'], 'no valid email') !== false) {
                    $skippedCount++;
                } else {
                    $failedCount++;
                }
                $details[] = $res['message'];
            }
        }

        $summaryMsg = "Successfully emailed {$sentCount} payslip(s).";
        if ($skippedCount > 0) {
            $summaryMsg .= " {$skippedCount} skipped due to missing email.";
        }
        if ($failedCount > 0) {
            $summaryMsg .= " {$failedCount} failed to deliver.";
        }

        echo json_encode([
            'success' => $sentCount > 0,
            'message' => $summaryMsg,
            'sent_count' => $sentCount,
            'skipped_count' => $skippedCount,
            'failed_count' => $failedCount,
            'errors' => $details
        ]);
        exit;
    }

    /**
     * Bulk delete payslips
     */
    public function bulkDelete()
    {
        header('Content-Type: application/json');
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $input = file_get_contents('php://input');
        $payload = json_decode($input, true);
        $payslipIds = $payload['payslip_ids'] ?? [];

        if (empty($payslipIds) || !is_array($payslipIds)) {
            echo json_encode(['success' => false, 'message' => 'No payslips selected for deletion.']);
            exit;
        }

        $payslipIds = array_filter(array_map('intval', $payslipIds));
        if (empty($payslipIds)) {
            echo json_encode(['success' => false, 'message' => 'Invalid payslip selection.']);
            exit;
        }

        $deletedCount = 0;
        $this->db->begin_transaction();
        try {
            foreach ($payslipIds as $id) {
                // Do not delete paid payslips
                $chk = $this->db->query("SELECT status FROM payslips WHERE id = $id LIMIT 1");
                if ($chk && $row = $chk->fetch_assoc()) {
                    if ($row['status'] === 'paid') {
                        continue;
                    }
                }
                $this->paymentModel->delete($id);
                $deletedCount++;
            }
            $this->db->commit();

            echo json_encode([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} payslip(s)."
            ]);
            exit;
        } catch (\Throwable $e) {
            $this->db->rollback();
            echo json_encode(['success' => false, 'message' => 'Bulk deletion failed: ' . $e->getMessage()]);
            exit;
        }
    }

    public function workspace()
    {
        // Current month/year
        $currentYear = date('Y');
        $currentMonth = date('m');

        // GET param use, fallback to current month/year
        $selectedYear = isset($_GET['year']) && $_GET['year'] !== 'undefined' && $_GET['year'] !== '' ? $_GET['year'] : $currentYear;
        $selectedMonth = isset($_GET['month']) && $_GET['month'] !== 'undefined' && $_GET['month'] !== '' ? str_pad($_GET['month'], 2, '0', STR_PAD_LEFT) : $currentMonth;
        $selectedProject = isset($_GET['payroll_project_id']) && $_GET['payroll_project_id'] !== 'undefined' ? $_GET['payroll_project_id'] : '';

        // If custom date range supplied in GET, initialize them upfront
        $targetStartDate = (!empty($_GET['from_date']) && $_GET['from_date'] !== 'undefined') ? $_GET['from_date'] : '';
        $targetEndDate = (!empty($_GET['to_date']) && $_GET['to_date'] !== 'undefined') ? $_GET['to_date'] : '';
        $filterType = (isset($_GET['filter_type']) && $_GET['filter_type'] === 'custom') || (!empty($targetStartDate) && !empty($targetEndDate)) ? 'custom' : 'monthly';

        // If project selected, fetch project date to ensure year/month/effective dates are synchronized with the project
        if ($selectedProject) {
            $projQuery = $this->db->query('SELECT * FROM payroll_projects WHERE id = ' . (int) $selectedProject);
            if ($projQuery && $projData = $projQuery->fetch_assoc()) {
                if (!empty($projData['is_custom_date']) && !empty($projData['start_date']) && !empty($projData['end_date'])) {
                    $filterType = 'custom';
                    $targetStartDate = $projData['start_date'];
                    $targetEndDate = $projData['end_date'];
                    $selectedYear = date('Y', strtotime($targetStartDate));
                    $selectedMonth = date('m', strtotime($targetStartDate));
                } elseif (!empty($projData['date'])) {
                    $filterType = 'monthly';
                    $parts = explode('-', $projData['date']);
                    if (count($parts) >= 2) {
                        $selectedYear = $parts[0];
                        $selectedMonth = $parts[1];
                        $targetStartDate = sprintf('%04d-%02d-01', (int) $selectedYear, (int) $selectedMonth);
                        $targetEndDate = date('Y-m-t', strtotime($targetStartDate));
                    }
                }
            }
        }

        // Auto-detect matching Payroll Project if not explicitly provided
        if (empty($selectedProject)) {
            // Priority A: Check if specified meal_project is explicitly linked to a payroll project
            $selectedMealPid = isset($_GET['meal_project_id']) && $_GET['meal_project_id'] !== '' && $_GET['meal_project_id'] !== 'undefined' ? (int) $_GET['meal_project_id'] : 0;
            if ($selectedMealPid > 0) {
                $mpChk = $this->db->query('SELECT payroll_project_id, from_date, to_date FROM meal_projects WHERE id = ' . $selectedMealPid);
                if ($mpChk && $mpR = $mpChk->fetch_assoc()) {
                    if (!empty($mpR['payroll_project_id'])) {
                        $selectedProject = (int) $mpR['payroll_project_id'];
                    }
                    if (empty($targetStartDate) && !empty($mpR['from_date']) && !empty($mpR['to_date'])) {
                        $targetStartDate = $mpR['from_date'];
                        $targetEndDate   = $mpR['to_date'];
                        $filterType      = 'custom';
                    }
                }
            }

            // Priority B: Match payroll_projects by exact or overlapping custom date range
            if (empty($selectedProject) && !empty($targetStartDate) && !empty($targetEndDate)) {
                $escStart = $this->db->real_escape_string($targetStartDate);
                $escEnd   = $this->db->real_escape_string($targetEndDate);
                $projMatch = $this->db->query("
                    SELECT id, name, is_custom_date, start_date, end_date, date 
                    FROM payroll_projects 
                    WHERE (start_date = '$escStart' AND end_date = '$escEnd')
                       OR (is_custom_date = 1 AND start_date <= '$escEnd' AND end_date >= '$escStart')
                    ORDER BY (start_date = '$escStart' AND end_date = '$escEnd') DESC, id DESC 
                    LIMIT 1
                ");
                if ($projMatch && $pMatch = $projMatch->fetch_assoc()) {
                    $selectedProject = (int) $pMatch['id'];
                }
            }

            // Priority C: Match payroll_projects by monthly date code (YYYY-MM)
            if (empty($selectedProject) && $filterType === 'monthly' && !empty($selectedYear) && !empty($selectedMonth)) {
                $mCode = sprintf('%04d-%02d', (int) $selectedYear, (int) $selectedMonth);
                $projMatchMonth = $this->db->query("SELECT id FROM payroll_projects WHERE date = '$mCode' ORDER BY id DESC LIMIT 1");
                if ($projMatchMonth && $pmRow = $projMatchMonth->fetch_assoc()) {
                    $selectedProject = (int) $pmRow['id'];
                }
            }

            // If project was auto-detected, ensure authoritative project metadata is synchronized
            if (!empty($selectedProject)) {
                $projQuery = $this->db->query('SELECT * FROM payroll_projects WHERE id = ' . (int) $selectedProject);
                if ($projQuery && $projData = $projQuery->fetch_assoc()) {
                    if (!empty($projData['is_custom_date']) && !empty($projData['start_date']) && !empty($projData['end_date'])) {
                        $filterType = 'custom';
                        $targetStartDate = $projData['start_date'];
                        $targetEndDate = $projData['end_date'];
                        $selectedYear = date('Y', strtotime($targetStartDate));
                        $selectedMonth = date('m', strtotime($targetStartDate));
                    }
                }
            }
        }

        if (empty($selectedYear)) {
            $selectedYear = $currentYear;
        }
        if (empty($selectedMonth)) {
            $selectedMonth = $currentMonth;
        }
        if (empty($targetStartDate) || empty($targetEndDate)) {
            $targetStartDate = sprintf('%04d-%02d-01', (int) $selectedYear, (int) $selectedMonth);
            $targetEndDate = date('Y-m-t', strtotime($targetStartDate));
        }

        // Fetch dynamic payslip items
        $items = $this->payslipItemModel->getAll();  // returns id, name, type etc.

        $projectRes = $this->db->query('SELECT * FROM payroll_projects ORDER BY id DESC');
        $payroll_projects = [];
        if ($projectRes) {
            while ($pRow = $projectRes->fetch_assoc()) {
                $payroll_projects[] = $pRow;
            }
        }

        $escapedTargetStart = $this->db->real_escape_string($targetStartDate);
        $escapedTargetEnd = $this->db->real_escape_string($targetEndDate);

        // Build an exhaustive query joining payslips, employees, departments, designations
        $query = '
        SELECT p.*, e.id as real_emp_id_fk,
               e.full_name, e.employee_id as emp_id, e.email, e.workplace, 
               e.joining_date, e.account_holder_name, e.account_number, e.bank_name,
               e.gross_salary as emp_gross, e.basic_salary as emp_basic,
               e.deleted_at,
               e.per_meal_amount, e.is_meal_deduction, e.per_meal_percentage,
               dept.name as department_name, desg.name as designation_name,
               p.late_unpaid_absent_days as saved_late_unpaid_absent_days,
               p.unpaid_absent_amount as saved_unpaid_absent_amount,
               override_att.tipsoi_present, override_att.tipsoi_absent, override_att.tipsoi_leave, override_att.tipsoi_late,
               override_att.late_unpaid_absent_days as att_late_unpaid_absent_days,
               override_att.deduction as att_deduction,
               override_att.final_late as att_final_late,
               override_att.update_at as att_updated_at
        FROM employees e
        LEFT JOIN departments dept ON e.department_id = dept.id
        LEFT JOIN designations desg ON e.designation_id = desg.id
        LEFT JOIN payslips p ON e.id = p.employee_id 
            ' . ($selectedProject ? ' AND p.payroll_project_id = ' . (int) $selectedProject : ' AND p.payroll_project_id = 0') . '
        LEFT JOIN (
            SELECT employee_id, present as tipsoi_present, absent as tipsoi_absent, leave_days as tipsoi_leave, late as tipsoi_late, deduction, final_late, late_unpaid_absent_days, update_at 
            FROM attendance_monthly_update
            WHERE payroll_project_id = ' . ((int) $selectedProject ?: '0') . "
        ) override_att ON e.id = override_att.employee_id
        WHERE e.employee_status = 'active'
          AND (
              p.id IS NOT NULL 
              OR (
                  (e.joining_date IS NULL OR e.joining_date <= '$escapedTargetEnd')
                  AND (e.deleted_at IS NULL OR e.deleted_at >= '$escapedTargetStart')
              )
          )
        ORDER BY e.employee_id ASC 
    ";

        // Fetch all meal projects (newest first)
        $meal_projects = [];
        $mProjRes = $this->db->query('SELECT id, name, standard_meal_amount, from_date, to_date, payroll_project_id FROM meal_projects ORDER BY id DESC, from_date DESC');
        if ($mProjRes) {
            while ($mp = $mProjRes->fetch_assoc()) {
                $meal_projects[] = $mp;
            }
        }

        // Determine active/selected meal project automatically by project link or date range
        $selectedMealProjectId = isset($_GET['meal_project_id']) && $_GET['meal_project_id'] !== '' && $_GET['meal_project_id'] !== 'undefined' ? (int) $_GET['meal_project_id'] : 0;
        $activeMealProject = null;

        // Priority 1: Check if any meal project is explicitly linked to selected payroll_project_id
        if ($selectedProject) {
            foreach ($meal_projects as $mp) {
                if (!empty($mp['payroll_project_id']) && (int) $mp['payroll_project_id'] === (int) $selectedProject) {
                    $activeMealProject = $mp;
                    $selectedMealProjectId = (int) $mp['id'];
                    break;
                }
            }
        }

        // Priority 2: Exact timeframe match with targetStartDate and targetEndDate
        if (!$activeMealProject && !empty($targetStartDate) && !empty($targetEndDate)) {
            foreach ($meal_projects as $mp) {
                if ($mp['from_date'] === $targetStartDate && $mp['to_date'] === $targetEndDate) {
                    $activeMealProject = $mp;
                    $selectedMealProjectId = (int) $mp['id'];
                    break;
                }
            }
        }

        // Priority 3: Smart auto-detect based on timeframe overlap (e.g. Sep 26 - Oct 25)
        if (!$activeMealProject && !empty($targetStartDate) && !empty($targetEndDate)) {
            foreach ($meal_projects as $mp) {
                if ($mp['from_date'] <= $targetEndDate && $mp['to_date'] >= $targetStartDate) {
                    $activeMealProject = $mp;
                    $selectedMealProjectId = (int) $mp['id'];
                    break;
                }
            }
        }

        // Priority 4: If explicit GET param was passed
        if (!$activeMealProject && $selectedMealProjectId > 0) {
            foreach ($meal_projects as $mp) {
                if ((int) $mp['id'] === $selectedMealProjectId) {
                    $activeMealProject = $mp;
                    break;
                }
            }
        }

        // Fallback: only if no specific date range was requested, pick the latest meal project if exists
        if (!$activeMealProject && empty($targetStartDate) && empty($targetEndDate) && !empty($meal_projects)) {
            $activeMealProject = $meal_projects[0];
            $selectedMealProjectId = (int) $activeMealProject['id'];
        }

        // Auto-save the link in DB if not yet set
        if ($selectedProject && !empty($activeMealProject['id']) && empty($activeMealProject['payroll_project_id'])) {
            $matchedMid = (int) $activeMealProject['id'];
            $this->db->query("UPDATE meal_projects SET payroll_project_id = " . (int)$selectedProject . " WHERE id = $matchedMid");
        }

        // Pre-fetch tracked meal counts from meal_records for this active meal project
        $mealCountsByEmp = [];
        if ($activeMealProject) {
            $mPid = (int) $activeMealProject['id'];
            $mealStmt = $this->db->prepare('
                SELECT employee_id, COUNT(*) as meal_count 
                FROM meal_records 
                WHERE meal_project_id = ? AND is_consumed = 1 
                GROUP BY employee_id
            ');
            if ($mealStmt) {
                $mealStmt->bind_param('i', $mPid);
                $mealStmt->execute();
                $mealRes = $mealStmt->get_result();
                if ($mealRes) {
                    while ($mRow = $mealRes->fetch_assoc()) {
                        $mealCountsByEmp[(int) $mRow['employee_id']] = (int) $mRow['meal_count'];
                    }
                }
                $mealStmt->close();
            }
        }

        $res = $this->db->query($query);
        $payslips = [];
        if ($res) {
            // Pre-fetch employee_salary_items for initial fallback
            $configuredItems = [];
            $esQuery = $this->db->query('SELECT employee_id, payslip_item_id, amount FROM employee_salary_items');
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
            $monthStr = $selectedYear && $selectedMonth ? $selectedYear . '-' . str_pad($selectedMonth, 2, '0', STR_PAD_LEFT) : date('Y-m');

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

        $this->view('payrolls/payslips/workspace', [
            'current_route' => 'payroll-workspace',
            'page_title' => 'Payroll Workplace',
            'payslips' => $payslips,
            'items' => $items,
            'selectedProject' => $selectedProject,
            'filterType' => $filterType,
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'payroll_projects' => $payroll_projects,
            'meal_projects' => $meal_projects,
            'activeMealProject' => $activeMealProject,
            'selectedMealProjectId' => $selectedMealProjectId,
            'mealCountsByEmp' => $mealCountsByEmp,
            'selectedFromDate' => $targetStartDate,
            'selectedToDate' => $targetEndDate,
            'cycleTotalDays' => max(1, (int) round((strtotime($targetEndDate) - strtotime($targetStartDate)) / 86400) + 1)
        ]);
    }

    public function storePayrollProject()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = isset($_POST['name']) ? $this->db->real_escape_string($_POST['name']) : '';
            $project_type = isset($_POST['project_type']) ? $_POST['project_type'] : 'monthly';
            $date = isset($_POST['date']) ? $this->db->real_escape_string($_POST['date']) : '';
            $start_date = isset($_POST['start_date']) ? $this->db->real_escape_string($_POST['start_date']) : '';
            $end_date = isset($_POST['end_date']) ? $this->db->real_escape_string($_POST['end_date']) : '';

            $is_custom = ($project_type === 'custom') ? 1 : 0;

            if (empty($name)) {
                echo "<script>alert('Project name is required.'); window.location.href='{$GLOBALS['base_url']}/payroll-workspace';</script>";
                exit;
            }
            if ($is_custom && (empty($start_date) || empty($end_date))) {
                echo "<script>alert('Start date and end date are required for custom projects.'); window.location.href='{$GLOBALS['base_url']}/payroll-workspace';</script>";
                exit;
            }
            if (!$is_custom && empty($date)) {
                echo "<script>alert('Project date is required for monthly projects.'); window.location.href='{$GLOBALS['base_url']}/payroll-workspace';</script>";
                exit;
            }

            if ($is_custom) {
                // For custom projects, the 'date' field could be null or derived (e.g. from start_date month) to satisfy UI backward compatibility.
                $date = date('Y-m', strtotime($start_date));
            }

            $stmt = $this->db->prepare('INSERT INTO payroll_projects (name, date, is_custom_date, start_date, end_date) VALUES (?, ?, ?, ?, ?)');

            if (!$stmt) {
                echo "<script>alert('Database error: Failed to prepare statement.'); window.location.href='{$GLOBALS['base_url']}/payroll-workspace';</script>";
                exit;
            }

            $stmt->bind_param('ssiss', $name, $date, $is_custom, $start_date, $end_date);

            if ($stmt->execute()) {
                $newProjId = $stmt->insert_id;

                // Auto-link matching Meal Project based on date range or manual input
                $meal_project_id = !empty($_POST['meal_project_id']) ? (int) $_POST['meal_project_id'] : 0;
                if ($meal_project_id <= 0 && $newProjId > 0) {
                    $checkStart = $is_custom ? $start_date : ($date . '-01');
                    $checkEnd   = $is_custom ? $end_date : date('Y-m-t', strtotime($checkStart));
                    $escCS = $this->db->real_escape_string($checkStart);
                    $escCE = $this->db->real_escape_string($checkEnd);

                    $mMatch = $this->db->query("
                        SELECT id FROM meal_projects 
                        WHERE (from_date = '$escCS' AND to_date = '$escCE')
                           OR (from_date <= '$escCE' AND to_date >= '$escCS')
                        ORDER BY (from_date = '$escCS' AND to_date = '$escCE') DESC, id DESC LIMIT 1
                    ");
                    if ($mMatch && $mRow = $mMatch->fetch_assoc()) {
                        $meal_project_id = (int) $mRow['id'];
                    }
                }

                if ($meal_project_id > 0 && $newProjId > 0) {
                    $this->db->query("UPDATE meal_projects SET payroll_project_id = $newProjId WHERE id = $meal_project_id");
                }
                echo "<script>window.location.href='{$GLOBALS['base_url']}/payroll-workspace?payroll_project_id={$newProjId}';</script>";
            } else {
                echo "<script>alert('Failed to create project.'); window.location.href='{$GLOBALS['base_url']}/payroll-workspace';</script>";
            }
            $stmt->close();
        }
    }

    public function salaryHold()
    {
        return $this->salaryHoldPage();
    }

    public function exportSalaryHoldPDF()
    {
        $currentYear = date('Y');
        $currentMonth = date('m');

        $selectedProject = !empty($_GET['payroll_project_id']) ? (int) $_GET['payroll_project_id'] : (!empty($_GET['project_id']) ? (int) $_GET['project_id'] : 0);
        $selectedYear = (!empty($_GET['year'])) ? (int) $_GET['year'] : (int) $currentYear;
        $selectedMonth = (!empty($_GET['month'])) ? (int) $_GET['month'] : (int) $currentMonth;

        $projectName = '';
        if ($selectedProject > 0) {
            $pStmt = $this->db->prepare('SELECT name, date FROM payroll_projects WHERE id = ?');
            $pStmt->bind_param('i', $selectedProject);
            $pStmt->execute();
            $pRes = $pStmt->get_result();
            if ($pRow = $pRes->fetch_assoc()) {
                $projectName = $pRow['name'];
                if (empty($_GET['year']) || empty($_GET['month'])) {
                    if (!empty($pRow['date'])) {
                        $pDateParts = explode('-', $pRow['date']);
                        if (count($pDateParts) >= 2) {
                            $selectedYear = (int) $pDateParts[0];
                            $selectedMonth = (int) $pDateParts[1];
                        }
                    }
                }
            }
            $pStmt->close();
        }

        if ($selectedProject > 0) {
            $stmt = $this->db->prepare("
                SELECT p.id, p.payslip_month, p.net_salary, p.total_earning, p.salary_hold, p.remarks,
                       e.full_name, e.employee_id as emp_id, e.gross_salary,
                       dept.name as department_name, desg.name as designation_name
                FROM payslips p
                JOIN employees e ON p.employee_id = e.id
                LEFT JOIN departments dept ON e.department_id = dept.id
                LEFT JOIN designations desg ON e.designation_id = desg.id
                WHERE p.payroll_project_id = ?
                  AND YEAR(p.payslip_month) = ?
                  AND MONTH(p.payslip_month) = ?
                  AND LOWER(TRIM(p.salary_hold)) = 'hold'
                ORDER BY e.employee_id ASC
            ");
            $stmt->bind_param('iii', $selectedProject, $selectedYear, $selectedMonth);
        } else {
            $stmt = $this->db->prepare("
                SELECT p.id, p.payslip_month, p.net_salary, p.total_earning, p.salary_hold, p.remarks,
                       e.full_name, e.employee_id as emp_id, e.gross_salary,
                       dept.name as department_name, desg.name as designation_name
                FROM payslips p
                JOIN employees e ON p.employee_id = e.id
                LEFT JOIN departments dept ON e.department_id = dept.id
                LEFT JOIN designations desg ON e.designation_id = desg.id
                WHERE YEAR(p.payslip_month) = ?
                  AND MONTH(p.payslip_month) = ?
                  AND LOWER(TRIM(p.salary_hold)) = 'hold'
                ORDER BY e.employee_id ASC
            ");
            $stmt->bind_param('ii', $selectedYear, $selectedMonth);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        $holds = [];
        $totalHeldAmount = 0.0;
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $heldAmt = floatval($row['net_salary'] > 0 ? $row['net_salary'] : ($row['gross_salary'] ?? 0));
                $row['effective_amount'] = $heldAmt;
                $totalHeldAmount += $heldAmt;
                $holds[] = $row;
            }
        }
        $stmt->close();

        $pdf = new \TCPDF('L', 'mm', 'A4');
        $pdf->SetCreator('TPHL ERP');
        $pdf->SetAuthor('HR Department');
        $pdf->SetTitle('Salary Hold Report');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', '', 9);

        $monthName = date('F', mktime(0, 0, 0, $selectedMonth, 1));
        $projectLabel = !empty($projectName) ? 'Project: ' . htmlspecialchars($projectName) . ' | ' : '';

        $html = '
        <div style="text-align:center;">
            <h1 style="color:#078134; margin:0; font-size:22px;">The Premium Homes Ltd</h1>
            <h3 style="color:#28B463; margin:3px 0; font-size:15px;">Salary Hold Report</h3>
            <p style="font-size:11px; color:#555; margin:2px 0;">' . $projectLabel . 'Period: ' . $monthName . ' ' . $selectedYear . '</p>
            <p style="font-size:11px; margin:2px 0;"><b>Total Employees on Hold:</b> ' . count($holds) . ' &nbsp;&nbsp;|&nbsp;&nbsp; <b>Total Held Amount:</b> ৳ ' . number_format($totalHeldAmount, 2) . '</p>
        </div>
        <br><br>
        <table border="1" cellpadding="6" cellspacing="0" width="100%">
            <thead>
                <tr style="background-color:#078134; color:#ffffff; text-align:center; font-weight:bold; font-size:10px;">
                    <th width="6%">SL</th>
                    <th width="12%">Employee ID</th>
                    <th width="24%">Name</th>
                    <th width="18%">Department</th>
                    <th width="18%">Designation</th>
                    <th width="12%">Held Amount (৳)</th>
                    <th width="10%">Remarks</th>
                </tr>
            </thead>
            <tbody>';

        $sl = 1;
        if (empty($holds)) {
            $html .= '<tr><td colspan="7" style="text-align:center; padding:15px;"><b>No salary holds found for this period.</b></td></tr>';
        } else {
            foreach ($holds as $hold) {
                $html .= '<tr style="font-size:9px;">
                    <td width="6%" style="text-align:center;">' . $sl++ . '</td>
                    <td width="12%" style="text-align:center;">' . htmlspecialchars($hold['emp_id'] ?? '') . '</td>
                    <td width="24%"><b>' . htmlspecialchars($hold['full_name'] ?? '') . '</b></td>
                    <td width="18%">' . htmlspecialchars($hold['department_name'] ?? '-') . '</td>
                    <td width="18%">' . htmlspecialchars($hold['designation_name'] ?? '-') . '</td>
                    <td width="12%" style="text-align:right; color:#c0392b; font-weight:bold;">৳ ' . number_format($hold['effective_amount'] ?? 0, 2) . '</td>
                    <td width="10%" style="text-align:center;">' . htmlspecialchars($hold['remarks'] ?? '-') . '</td>
                </tr>';
            }
            $html .= '<tr style="background-color:#f2f4f4; font-weight:bold; font-size:10px;">
                <td colspan="5" style="text-align:right;">Total Held Amount:</td>
                <td style="text-align:right; color:#c0392b;">৳ ' . number_format($totalHeldAmount, 2) . '</td>
                <td></td>
            </tr>';
        }

        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        if (ob_get_length()) {
            ob_end_clean();
        }
        $filename = 'Salary_Hold_Report_' . (!empty($projectName) ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $projectName) . '_' : '') . $monthName . '_' . $selectedYear . '.pdf';
        $pdf->Output($filename, 'D');
        exit;
    }

    public function exportWorkspacePDF()
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        $currentYear = date('Y');
        $currentMonth = date('m');

        $selectedYear = (!empty($_GET['year'])) ? $_GET['year'] : $currentYear;
        $selectedMonth = (!empty($_GET['month'])) ? $_GET['month'] : $currentMonth;

        $payrollProjectId = (int)($_POST['payroll_project_id'] ?? $_GET['payroll_project_id'] ?? 0);
        $projectName = trim($_POST['project_name'] ?? '');

        if (empty($projectName) && $payrollProjectId > 0) {
            $pQuery = $this->db->query("SELECT name, date FROM payroll_projects WHERE id = $payrollProjectId LIMIT 1");
            if ($pQuery && $pRow = $pQuery->fetch_assoc()) {
                $projectName = $pRow['name'];
                if (empty($_GET['year']) && !empty($pRow['date'])) {
                    $dp = explode('-', $pRow['date']);
                    if (count($dp) >= 2) {
                        $selectedYear = $dp[0];
                        $selectedMonth = $dp[1];
                    }
                }
            }
        }

        $monthName = date('F', mktime(0, 0, 0, (int)$selectedMonth, 1));

        // Setup PDF Generator
        $pdf = new \TCPDF('L', 'mm', 'A2', true, 'UTF-8', false);
        $pdf->SetCreator('TPHL ERP');
        $pdf->SetAuthor('Admin');
        $pdf->SetTitle('Payroll Workplace Report');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(8, 8, 8);
        $pdf->SetAutoPageBreak(TRUE, 10);
        $pdf->setFontSubsetting(false); // Subsetting disabled drastically reduces memory consumption & boosts speed
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 6.5);

        // Check if custom POST data is set from the client-side
        if (!empty($_POST['table_data']) && !empty($_POST['table_headers'])) {
            $tableData = json_decode($_POST['table_data'], true) ?: [];
            $tableHeaders = json_decode($_POST['table_headers'], true) ?: [];
            $tableFooters = !empty($_POST['table_footers']) ? (json_decode($_POST['table_footers'], true) ?: []) : [];

            $projectTitleStr = !empty($projectName) ? ' &mdash; ' . htmlspecialchars($projectName) : '';
            $html = '
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 6px;">
                <tr>
                    <td style="text-align: center;">
                        <h1 style="color: #078134; margin: 0; padding: 0; font-size: 16px; font-weight: bold;">The Premium Homes Ltd</h1>
                        <h3 style="color: #1a5632; margin: 2px 0; font-size: 11px;">Payroll Workplace Report' . $projectTitleStr . ' (' . htmlspecialchars($monthName . ' ' . $selectedYear) . ')</h3>
                        <p style="font-size: 9px; color: #555555; margin: 0;"><b>Total Employees: </b> ' . count($tableData) . '</p>
                    </td>
                </tr>
            </table>
            <br>
            <table border="1" cellpadding="2" cellspacing="0" width="100%" style="font-size: 6px; border-collapse: collapse;">
                <thead>
                    <tr style="background-color: #078134; color: #ffffff; text-align: center; font-weight: bold;">';

            foreach ($tableHeaders as $th) {
                $html .= '<th style="padding: 3px 1px;">' . htmlspecialchars($th) . '</th>';
            }
            $html .= '</tr></thead><tbody>';

            if (empty($tableData)) {
                $html .= '<tr><td colspan="' . count($tableHeaders) . '" style="text-align: center; padding: 10px;"><b>No employees found for this period.</b></td></tr>';
            } else {
                foreach ($tableData as $row) {
                    $html .= '<tr>';
                    foreach ($row as $colIdx => $cell) {
                        $th = $tableHeaders[$colIdx] ?? '';
                        $thLower = strtolower($th);
                        $align = 'center';

                        if (strpos($thLower, 'name') !== false || strpos($thLower, 'department') !== false || strpos($thLower, 'designation') !== false || strpos($thLower, 'workplace') !== false || strpos($thLower, 'remarks') !== false) {
                            $align = 'left';
                        } elseif (strpos($thLower, 'amount') !== false || strpos($thLower, 'salary') !== false || strpos($thLower, 'gross') !== false || strpos($thLower, 'daily') !== false || strpos($thLower, 'payable') !== false || strpos($thLower, 'bank') !== false || strpos($thLower, 'cash') !== false || strpos($thLower, 'allowance') !== false || strpos($thLower, 'deduction') !== false || strpos($thLower, 'lunch') !== false || strpos($thLower, 'bonus') !== false || strpos($thLower, 'overtime') !== false || strpos($thLower, 'arrear') !== false || strpos($thLower, 'tax') !== false) {
                            $align = 'right';
                        }

                        $html .= '<td style="text-align: ' . $align . '; padding: 2px 1px;">' . htmlspecialchars((string)($cell ?? '')) . '</td>';
                    }
                    $html .= '</tr>';
                }
            }
            $html .= '</tbody>';

            if (!empty($tableFooters) && count($tableFooters) === count($tableHeaders)) {
                $html .= '<tfoot><tr style="background-color: #d1e7dd; color: #0f5132; font-weight: bold; font-size: 6.5px;">';
                foreach ($tableFooters as $colIdx => $fcell) {
                    $th = $tableHeaders[$colIdx] ?? '';
                    $thLower = strtolower($th);
                    $align = 'center';
                    if (strpos($thLower, 'name') !== false || strpos($thLower, 'department') !== false || strpos($thLower, 'designation') !== false) {
                        $align = 'left';
                    } elseif (strpos($thLower, 'amount') !== false || strpos($thLower, 'salary') !== false || strpos($thLower, 'gross') !== false || strpos($thLower, 'daily') !== false || strpos($thLower, 'payable') !== false || strpos($thLower, 'bank') !== false || strpos($thLower, 'cash') !== false || strpos($thLower, 'allowance') !== false || strpos($thLower, 'deduction') !== false || strpos($thLower, 'lunch') !== false || strpos($thLower, 'bonus') !== false || strpos($thLower, 'overtime') !== false || strpos($thLower, 'arrear') !== false || strpos($thLower, 'tax') !== false) {
                        $align = 'right';
                    }
                    $html .= '<td style="text-align: ' . $align . '; padding: 2px 1px;">' . htmlspecialchars((string)($fcell ?? '')) . '</td>';
                }
                $html .= '</tr></tfoot>';
            }

            $html .= '</table>';

            $pdf->writeHTML($html, true, false, true, false, '');
            if (ob_get_length()) {
                ob_end_clean();
            }
            $pdfFilename = 'Payroll_Workspace_Report_' . (!empty($projectName) ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $projectName) . '_' : '') . $monthName . '_' . $selectedYear . '.pdf';
            $pdf->Output($pdfFilename, 'I');
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
            WHERE e.employee_status = 'active' " . ($payrollProjectId > 0 ? " AND p.payroll_project_id = $payrollProjectId" : " AND YEAR(p.payslip_month) = '$selectedYear' AND MONTH(p.payslip_month) = '$selectedMonth'") . "
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

        $projectTitleStr = !empty($projectName) ? ' &mdash; ' . htmlspecialchars($projectName) : '';
        $html = '
        <div style="text-align:center;">
            <h1 style="color:#078134;">The Premium Homes Ltd</h1>
            <h3 style="color:#28B463;">Payroll Workplace Report' . $projectTitleStr . ' (' . htmlspecialchars($monthName . ' ' . $selectedYear) . ')</h3>
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
                        $val = round($basic * 0.1, 2);
                    } elseif (strpos($nameStr, 'conveyance') !== false) {
                        $val = round($basic * 0.1, 2);
                    } elseif (strpos($nameStr, 'medical') !== false) {
                        $val = round($basic * 0.3, 2);
                    } elseif (strpos($nameStr, 'house rent') !== false) {
                        $val = round($basic * 0.5, 2);
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

        if (ob_get_length()) {
            ob_end_clean();
        }
        $filename = 'Payroll_Workspace_Report_' . (!empty($projectName) ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $projectName) . '_' : '') . $monthName . '_' . $selectedYear . '.pdf';
        $pdf->Output($filename, 'I');
        exit;
    }

    public function editPayrollProject($id)
    {
        $id = (int) $id;
        $projectRes = $this->db->query("SELECT * FROM payroll_projects WHERE id = $id");
        $project = $projectRes->fetch_assoc();

        if (!$project) {
            echo 'Project not found.';
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

        // Strict priority: resolve canonical timeframe from payroll_projects
        if ($projectId > 0) {
            $projRes = $this->db->query('SELECT date, is_custom_date, start_date, end_date FROM payroll_projects WHERE id = ' . $projectId);
            if ($projRes && $pRow = $projRes->fetch_assoc()) {
                if (!empty($pRow['is_custom_date']) && !empty($pRow['start_date'])) {
                    $year = (int) date('Y', strtotime($pRow['start_date']));
                    $month = (int) date('m', strtotime($pRow['start_date']));
                } elseif (!empty($pRow['date'])) {
                    $parts = explode('-', $pRow['date']);
                    if (count($parts) >= 2) {
                        $year = (int) $parts[0];
                        $month = (int) $parts[1];
                    }
                }
            }
        }
        if ($year < 2000)
            $year = (int) date('Y');
        if ($month < 1 || $month > 12)
            $month = (int) date('m');

        $payslipMonth = sprintf('%04d-%02d-01', $year, $month);

        // Pre-fetch employee designation mapping
        $empDesgMap = [];
        $edRes = $this->db->query('SELECT id, designation_id FROM employees');
        if ($edRes) {
            while ($ed = $edRes->fetch_assoc()) {
                $empDesgMap[(int) $ed['id']] = !empty($ed['designation_id']) ? (int) $ed['designation_id'] : null;
            }
        }

        $this->db->begin_transaction();
        try {
            foreach ($input['rows'] as $row) {
                $payslipId = (int) ($row['payslip_id'] ?? 0);
                $employeeId = (int) $row['employee_id'];
                $designationId = $empDesgMap[$employeeId] ?? null;
                $netSalary = (float) $row['net_salary'];
                $totalEarning = (float) $row['total_earning'];
                $totalDeduction = (float) $row['total_deduction'];
                $salaryHoldString = trim((string) ($row['salary_hold'] ?? ''));
                $bankAmount = (float) ($row['bank_amount'] ?? 0);
                $cashAmount = (float) ($row['cash_amount'] ?? 0);
                $paymentMethod = ($cashAmount > 0 && $bankAmount <= 0) ? 'cash' : 'bank';
                $lateUnpaidAbsentDays = (float) ($row['late_unpaid_absent_days'] ?? 0);
                $remarks = trim((string) ($row['remarks'] ?? ''));
                $attRemarks = trim((string) ($row['attendance_remarks'] ?? ''));
                $isExcluded = isset($row['is_excluded']) ? (int) $row['is_excluded'] : 0;
                $details = $row['details'] ?? [];

                $recreation_allowance = (float) ($row['recreation_allowance'] ?? 0);
                $conveyance_allowance = (float) ($row['conveyance_allowance'] ?? 0);
                $medical_allowance = (float) ($row['medical_allowance'] ?? 0);
                $house_rent_allowance = (float) ($row['house_rent_allowance'] ?? 0);
                $advance_salary = (float) ($row['advance_salary'] ?? 0);
                $meal_count = (float) ($row['meal_count'] ?? 0);
                $subsidized_lunch = (float) ($row['subsidized_lunch'] ?? 0);
                $unpaid_absent_amount = (float) ($row['unpaid_absent_amount'] ?? 0);
                $arrears_deduction = (float) ($row['arrears_deduction'] ?? 0);
                $others_deduction = (float) ($row['others_deduction'] ?? 0);
                $punctuality_bonus = (float) ($row['punctuality_bonus'] ?? 0);
                $overtime = (float) ($row['overtime'] ?? 0);
                $arrears_earning = (float) ($row['arrears_earning'] ?? 0);
                $duty_allowance = (float) ($row['duty_allowance'] ?? 0);
                $tax = (float) ($row['tax'] ?? 0);

                $existingStatus = 'draft';
                $checkStmt = $this->db->prepare('SELECT id, status FROM payslips WHERE employee_id=? AND payroll_project_id=?');
                if ($checkStmt) {
                    $checkStmt->bind_param('ii', $employeeId, $projectId);
                    $checkStmt->execute();
                    $checkRes = $checkStmt->get_result();
                    if ($checkRow = $checkRes->fetch_assoc()) {
                        $payslipId = (int) $checkRow['id'];
                        $existingStatus = $checkRow['status'] ?? 'draft';
                    }
                    $checkStmt->close();
                }

                // If already paid, retain 'paid', otherwise preserve or set as 'draft' until officially generated
                $saveStatus = ($existingStatus === 'paid') ? 'paid' : 'draft';

                if ($payslipId > 0) {
                    $stmt = $this->db->prepare('UPDATE payslips SET designation_id=?, payment_method=?, payslip_month=?, status=?, total_earning=?, total_deduction=?, net_salary=?, salary_hold=?, bank_amount=?, cash_amount=?, late_unpaid_absent_days=?, remarks=?, attendance_remarks=?, is_excluded=?, recreation_allowance=?, conveyance_allowance=?, medical_allowance=?, house_rent_allowance=?, advance_salary=?, meal_count=?, subsidized_lunch=?, unpaid_absent_amount=?, arrears_deduction=?, others_deduction=?, punctuality_bonus=?, overtime=?, arrears_earning=?, duty_allowance=?, tax=? WHERE id=?');
                    if ($stmt) {
                        $stmt->bind_param('isssdddsdddssidddddddddddddddi', $designationId, $paymentMethod, $payslipMonth, $saveStatus, $totalEarning, $totalDeduction, $netSalary, $salaryHoldString, $bankAmount, $cashAmount, $lateUnpaidAbsentDays, $remarks, $attRemarks, $isExcluded, $recreation_allowance, $conveyance_allowance, $medical_allowance, $house_rent_allowance, $advance_salary, $meal_count, $subsidized_lunch, $unpaid_absent_amount, $arrears_deduction, $others_deduction, $punctuality_bonus, $overtime, $arrears_earning, $duty_allowance, $tax, $payslipId);
                        $stmt->execute();
                        $stmt->close();
                    } else {
                        throw new Exception('Payslips update failed: ' . $this->db->error);
                    }
                } else {
                    $stmt = $this->db->prepare('INSERT INTO payslips (employee_id, payroll_project_id, designation_id, payment_method, payslip_month, status, total_earning, total_deduction, net_salary, salary_hold, bank_amount, cash_amount, late_unpaid_absent_days, remarks, attendance_remarks, is_excluded, recreation_allowance, conveyance_allowance, medical_allowance, house_rent_allowance, advance_salary, meal_count, subsidized_lunch, unpaid_absent_amount, arrears_deduction, others_deduction, punctuality_bonus, overtime, arrears_earning, duty_allowance, tax) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    if ($stmt) {
                        $stmt->bind_param('iiisssdddsdddssiddddddddddddddd', $employeeId, $projectId, $designationId, $paymentMethod, $payslipMonth, $saveStatus, $totalEarning, $totalDeduction, $netSalary, $salaryHoldString, $bankAmount, $cashAmount, $lateUnpaidAbsentDays, $remarks, $attRemarks, $isExcluded, $recreation_allowance, $conveyance_allowance, $medical_allowance, $house_rent_allowance, $advance_salary, $meal_count, $subsidized_lunch, $unpaid_absent_amount, $arrears_deduction, $others_deduction, $punctuality_bonus, $overtime, $arrears_earning, $duty_allowance, $tax);
                        $stmt->execute();
                        $payslipId = $stmt->insert_id;
                        $stmt->close();
                    } else {
                        throw new Exception('Payslips insert failed: ' . $this->db->error);
                    }
                }

                if ($payslipId > 0) {
                    $this->db->query("DELETE FROM payslip_details WHERE payslip_id = $payslipId");

                    // Build full item map if $details was empty
                    $resolvedDetails = !empty($details) ? $details : [];
                    if (empty($resolvedDetails)) {
                        if (!isset($itemLookupMap)) {
                            $itemLookupMap = [];
                            $itemRes = $this->db->query('SELECT id, name, type FROM payslip_items');
                            if ($itemRes) {
                                while ($it = $itemRes->fetch_assoc()) {
                                    $lname = strtolower(trim($it['name']));
                                    if (strpos($lname, 'house rent') !== false)
                                        $itemLookupMap['house_rent'] = (int) $it['id'];
                                    elseif (strpos($lname, 'medical') !== false)
                                        $itemLookupMap['medical'] = (int) $it['id'];
                                    elseif (strpos($lname, 'conveyance') !== false)
                                        $itemLookupMap['conveyance'] = (int) $it['id'];
                                    elseif (strpos($lname, 'recreation') !== false)
                                        $itemLookupMap['recreation'] = (int) $it['id'];
                                    elseif (strpos($lname, 'advance') !== false)
                                        $itemLookupMap['advance'] = (int) $it['id'];
                                    elseif (strpos($lname, 'lunch') !== false || strpos($lname, 'meal') !== false)
                                        $itemLookupMap['lunch'] = (int) $it['id'];
                                    elseif (strpos($lname, 'absent') !== false || strpos($lname, 'lwp') !== false)
                                        $itemLookupMap['absent'] = (int) $it['id'];
                                    elseif (strpos($lname, 'punctuality') !== false)
                                        $itemLookupMap['punctuality'] = (int) $it['id'];
                                    elseif (strpos($lname, 'overtime') !== false)
                                        $itemLookupMap['overtime'] = (int) $it['id'];
                                    elseif (strpos($lname, 'arrear') !== false && $it['type'] === 'earning')
                                        $itemLookupMap['arrear_earning'] = (int) $it['id'];
                                    elseif (strpos($lname, 'arrear') !== false && $it['type'] === 'deduction')
                                        $itemLookupMap['arrear_deduction'] = (int) $it['id'];
                                    elseif (strpos($lname, 'duty') !== false || strpos($lname, 'food') !== false || strpos($lname, 'car') !== false)
                                        $itemLookupMap['duty'] = (int) $it['id'];
                                    elseif (strpos($lname, 'other') !== false && $it['type'] === 'deduction')
                                        $itemLookupMap['other_deduct'] = (int) $it['id'];
                                    elseif (strpos($lname, 'tax') !== false)
                                        $itemLookupMap['tax'] = (int) $it['id'];
                                }
                            }
                            if (!isset($itemLookupMap['lunch'])) {
                                $chk = $this->db->query("SELECT id FROM payslip_items WHERE LOWER(name) LIKE '%lunch%' OR LOWER(name) LIKE '%meal%' LIMIT 1");
                                if ($chk && $cr = $chk->fetch_assoc()) {
                                    $itemLookupMap['lunch'] = (int) $cr['id'];
                                } else {
                                    $this->db->query("INSERT INTO payslip_items (name, type, created_at) VALUES ('Subsidized Lunch', 'deduction', NOW())");
                                    $itemLookupMap['lunch'] = (int) $this->db->insert_id;
                                }
                            }
                            if (!isset($itemLookupMap['absent'])) {
                                $chkA = $this->db->query("SELECT id FROM payslip_items WHERE LOWER(name) LIKE '%absent%' OR LOWER(name) LIKE '%lwp%' LIMIT 1");
                                if ($chkA && $ca = $chkA->fetch_assoc()) {
                                    $itemLookupMap['absent'] = (int) $ca['id'];
                                }
                            }
                        }

                        if ($house_rent_allowance > 0 && isset($itemLookupMap['house_rent']))
                            $resolvedDetails[$itemLookupMap['house_rent']] = $house_rent_allowance;
                        if ($medical_allowance > 0 && isset($itemLookupMap['medical']))
                            $resolvedDetails[$itemLookupMap['medical']] = $medical_allowance;
                        if ($conveyance_allowance > 0 && isset($itemLookupMap['conveyance']))
                            $resolvedDetails[$itemLookupMap['conveyance']] = $conveyance_allowance;
                        if ($recreation_allowance > 0 && isset($itemLookupMap['recreation']))
                            $resolvedDetails[$itemLookupMap['recreation']] = $recreation_allowance;
                        if ($advance_salary > 0 && isset($itemLookupMap['advance']))
                            $resolvedDetails[$itemLookupMap['advance']] = $advance_salary;
                        if ($subsidized_lunch > 0 && isset($itemLookupMap['lunch']))
                            $resolvedDetails[$itemLookupMap['lunch']] = $subsidized_lunch;
                        if ($unpaid_absent_amount > 0 && isset($itemLookupMap['absent']))
                            $resolvedDetails[$itemLookupMap['absent']] = $unpaid_absent_amount;
                        if ($punctuality_bonus > 0 && isset($itemLookupMap['punctuality']))
                            $resolvedDetails[$itemLookupMap['punctuality']] = $punctuality_bonus;
                        if ($overtime > 0 && isset($itemLookupMap['overtime']))
                            $resolvedDetails[$itemLookupMap['overtime']] = $overtime;
                        if ($arrears_earning > 0 && isset($itemLookupMap['arrear_earning']))
                            $resolvedDetails[$itemLookupMap['arrear_earning']] = $arrears_earning;
                        if ($arrears_deduction > 0 && isset($itemLookupMap['arrear_deduction']))
                            $resolvedDetails[$itemLookupMap['arrear_deduction']] = $arrears_deduction;
                        if ($duty_allowance > 0 && isset($itemLookupMap['duty']))
                            $resolvedDetails[$itemLookupMap['duty']] = $duty_allowance;
                        if ($others_deduction > 0 && isset($itemLookupMap['other_deduct']))
                            $resolvedDetails[$itemLookupMap['other_deduct']] = $others_deduction;
                        if ($tax > 0 && isset($itemLookupMap['tax']))
                            $resolvedDetails[$itemLookupMap['tax']] = $tax;
                    }

                    $detStmt = $this->db->prepare('INSERT INTO payslip_details (payslip_id, payslip_item_id, amount) VALUES (?, ?, ?)');
                    if ($detStmt) {
                        foreach ($resolvedDetails as $itemId => $amt) {
                            $amt = (float) $amt;
                            if ($amt > 0) {
                                $iId = (int) $itemId;
                                $detStmt->bind_param('iid', $payslipId, $iId, $amt);
                                $detStmt->execute();
                            }
                        }
                        $detStmt->close();
                    } else {
                        throw new Exception('Payslip details insert failed: ' . $this->db->error);
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
        $projectRes = $this->db->query('SELECT * FROM payroll_projects ORDER BY id DESC');
        $payroll_projects = [];
        if ($projectRes) {
            while ($pRow = $projectRes->fetch_assoc()) {
                $payroll_projects[] = $pRow;
            }
        }

        $selectedProjectId = isset($_GET['payroll_project_id']) && $_GET['payroll_project_id'] !== '' ? (int) $_GET['payroll_project_id'] : (isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int) $_GET['project_id'] : null);

        $this->view('payrolls/payslips/attendance-report', [
            'current_route' => 'payroll-attendance-report',
            'page_title' => 'Payroll Attendance Report',
            'payroll_projects' => $payroll_projects,
            'selected_project_id' => $selectedProjectId
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

            $stmt = $this->db->prepare('
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
            ');

            if (!$stmt) {
                throw new Exception('SQL Prepare failed: ' . $this->db->error);
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
                    'iidddddddddd',
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

        $payrollProjectId = isset($_GET['payroll_project_id']) && $_GET['payroll_project_id'] !== '' ? (int) $_GET['payroll_project_id'] : (isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int) $_GET['project_id'] : null);

        $projectName = '';
        if ($payrollProjectId) {
            $pStmt = $this->db->prepare('SELECT name, date, is_custom_date, start_date, end_date FROM payroll_projects WHERE id = ?');
            $pStmt->bind_param('i', $payrollProjectId);
            $pStmt->execute();
            $pRes = $pStmt->get_result();
            if ($pRow = $pRes->fetch_assoc()) {
                $projectName = $pRow['name'];
                if (empty($_GET['from_date']) || empty($_GET['to_date'])) {
                    if (!empty($pRow['is_custom_date']) && !empty($pRow['start_date']) && !empty($pRow['end_date'])) {
                        $fromDateStr = $pRow['start_date'];
                        $toDateStr = $pRow['end_date'];
                    } elseif (!empty($pRow['date'])) {
                        $parts = explode('-', $pRow['date']);
                        if (count($parts) >= 2) {
                            $y = (int) $parts[0];
                            $m = (int) $parts[1];
                            $fromDateStr = sprintf('%04d-%02d-01', $y, $m);
                            $toDateStr = date('Y-m-t', strtotime($fromDateStr));
                        }
                    }
                }
            }
            $pStmt->close();
        }

        // Fetch DB Overrides from attendance_monthly_update if project is selected
        $dbOverrides = [];
        if ($payrollProjectId) {
            $oRes = $this->db->query("SELECT * FROM attendance_monthly_update WHERE payroll_project_id = $payrollProjectId");
            if ($oRes) {
                while ($oRow = $oRes->fetch_assoc()) {
                    $dbOverrides[$oRow['employee_id']] = $oRow;
                }
            }
        }

        // Force UTC or Dhaka boundaries for Tipsoi API
        $oldTimezone = date_default_timezone_get();
        date_default_timezone_set('Asia/Dhaka');

        $fromTime = strtotime($fromDateStr . ' 00:00:00') * 1000;
        $toTime = strtotime($toDateStr . ' 23:59:59') * 1000;

        date_default_timezone_set($oldTimezone);

        // Attempt to fetch Tipsoi attendance logs safely
        $tipsoiAttendance = null;
        $tipsoiSuccess = false;
        try {
            $token = $apiService->getBearerToken();
            if ($token) {
                $url = 'https://app.tipsoi.ai/inovace-client/api/v1/attendance?' . http_build_query([
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
                curl_setopt($ch, CURLOPT_TIMEOUT, 120);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200 && $response) {
                    $data = json_decode($response, true);
                    if (isset($data['attendance']) && is_array($data['attendance'])) {
                        $tipsoiAttendance = $data['attendance'];
                        $tipsoiSuccess = true;
                    }
                }
            }
        } catch (\Throwable $e) {
            $tipsoiSuccess = false;
        }

        // Fetch local ERP employees
        $escapedToDate = $this->db->real_escape_string($toDateStr);
        $escapedFromDate = $this->db->real_escape_string($fromDateStr);

        $localSql = "SELECT e.id, e.employee_id, e.email, e.full_name, e.joining_date, e.resign_date, e.workplace, e.department_id,
                            d.name as department, des.name as designation 
                     FROM employees e 
                     LEFT JOIN departments d ON e.department_id = d.id 
                     LEFT JOIN designations des ON e.designation_id = des.id 
                     WHERE (e.employee_status = 'active' OR e.resign_date >= '$escapedFromDate')
                       AND (e.joining_date IS NULL OR e.joining_date <= '$escapedToDate')
                     ORDER BY CAST(e.employee_id AS UNSIGNED) ASC, e.employee_id ASC";
        $localQuery = $this->db->query($localSql);
        $localEmployees = [];
        if ($localQuery) {
            while ($row = $localQuery->fetch_assoc()) {
                $localEmployees[] = $row;
            }
        }

        // Fetch Custom Holidays for the date range
        require_once dirname(__DIR__) . '/models/Holiday.php';
        $holidayModel = new Holiday($this->db);
        $customHolidays = $holidayModel->getHolidayMapForRange($fromDateStr, $toDateStr);

        // Helpers
        $normalizeId = function ($id) {
            $str = trim((string) ($id ?? ''));
            return preg_match('/^\d+$/', $str) ? (string) (int) $str : $str;
        };

        $parseTimeToMinutes = function ($timeStr) {
            if (empty($timeStr) || trim($timeStr) === '' || trim($timeStr) === '-')
                return null;
            if (preg_match('/^(\d+):(\d+)\s*(AM|PM)$/i', trim($timeStr), $m)) {
                $h = (int) $m[1];
                $min = (int) $m[2];
                $ampm = strtoupper($m[3]);
                if ($ampm === 'PM' && $h !== 12)
                    $h += 12;
                elseif ($ampm === 'AM' && $h === 12)
                    $h = 0;
                return $h * 60 + $min;
            }
            return null;
        };

        // Aggregate Data
        $aggregatedData = [];
        $index = 1;

        foreach ($localEmployees as $localMatch) {
            $leIdNorm = $normalizeId($localMatch['employee_id']);
            $leNameNorm = strtolower(trim($localMatch['full_name'] ?? ''));

            // Match Tipsoi record
            $emp = null;
            if ($tipsoiSuccess && !empty($tipsoiAttendance)) {
                foreach ($tipsoiAttendance as $tEmp) {
                    $tOfficeId = $normalizeId($tEmp['employeeOfficeId'] ?? $tEmp['employeeId'] ?? '');
                    $tName = strtolower(trim($tEmp['employeeName'] ?? ''));
                    if (($tOfficeId !== '' && $tOfficeId === $leIdNorm) || ($tName !== '' && $tName === $leNameNorm)) {
                        $emp = $tEmp;
                        break;
                    }
                }
            }

            $summ = $emp['summary'] ?? [];
            $attMap = $emp['attendance'] ?? [];

            $approvedLates = 0;
            foreach ($attMap as $ts => $dayAtt) {
                $lateStatus = strtolower(trim((string) ($dayAtt['lateApprovalStatus'] ?? '')));
                if ($lateStatus === 'accepted' || $lateStatus === 'approved') {
                    $approvedLates++;
                }
            }

            if ($approvedLates === 0 && !empty($summ['totalApprovedLateApplication'])) {
                $approvedLates = (int) round(((int) $summ['totalApprovedLateApplication']) / 2);
            }

            $present = isset($summ['totalPresent']) ? (int) $summ['totalPresent'] : 0;
            $absent = isset($summ['totalAbsent']) ? (int) $summ['totalAbsent'] : 0;
            $leave = isset($summ['totalLeave']) ? (float) $summ['totalLeave'] : 0.0;
            $dayOff = isset($summ['totalWeeklyOffdays']) ? (int) $summ['totalWeeklyOffdays'] : 0;
            if (!empty($summ['totalHolidays'])) {
                $dayOff += (int) $summ['totalHolidays'];
            }

            // Align Custom Holidays (Global, Department-wise, or Employee-wise)
            $empRowId = (int) ($localMatch['id'] ?? 0);
            $empDeptId = (int) ($localMatch['department_id'] ?? 0);

            foreach ($attMap as $ts => &$dayAttRef) {
                $rawDate = $dayAttRef['date'] ?? ($dayAttRef['attendanceDate'] ?? null);
                $dayDate = null;
                if (!empty($rawDate)) {
                    $s = trim((string)$rawDate);
                    if (preg_match('/^(\d{1,2})[.\/-](\d{1,2})[.\/-](\d{4})/', $s, $m)) {
                        $dayDate = sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
                    } elseif (preg_match('/^(\d{4})[.\/-](\d{1,2})[.\/-](\d{1,2})/', $s, $m)) {
                        $dayDate = sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
                    }
                }
                if (!$dayDate && is_numeric($ts) && $ts > 0) {
                    $sec = $ts > 9999999999 ? (int)($ts / 1000) : (int)$ts;
                    $dt = new DateTime("@$sec");
                    $dt->setTimezone(new DateTimeZone('Asia/Dhaka'));
                    $dayDate = $dt->format('Y-m-d');
                }

                if (!$dayDate) continue;

                $customTitle = $customHolidays['employee'][$empRowId][$dayDate]
                    ?? ($customHolidays['department'][$empDeptId][$dayDate]
                    ?? ($customHolidays['global'][$dayDate] ?? null));

                if ($customTitle) {
                    $dayAttRef['isCustomHoliday'] = 1;
                    $isOff = !empty($dayAttRef['isOffday']) || !empty($dayAttRef['isHoliday']);
                    $isPres = !empty($dayAttRef['isPresent']) || (!empty($dayAttRef['firstLoggedTime']) && trim($dayAttRef['firstLoggedTime']) !== '-');
                    $isLeave = !empty($dayAttRef['isLeave']);

                    if (!$isOff) {
                        if (!$isPres && !$isLeave) {
                            $dayAttRef['isHoliday'] = 1;
                            $absent = max(0, $absent - 1);
                            $dayOff += 1;
                        } elseif ($isPres) {
                            $dayAttRef['isHolidayPresent'] = 1;
                        }
                    }
                }
            }
            unset($dayAttRef);

            $totalPeriodDays = count($attMap) ?: 30;
            $workingDays = max(0, $totalPeriodDays - $dayOff);

            if (!$emp || empty($emp['summary'])) {
                $workingDays = 0;
                $present = 0;
                $absent = 0;
                $leave = 0;
                $dayOff = 0;
                foreach ($attMap as $ts => $dayAtt) {
                    $workingDays++;
                    if (!empty($dayAtt['isLeave'])) {
                        $leave += !empty($dayAtt['isHalfDay']) ? 0.5 : 1;
                    } elseif (!empty($dayAtt['isPresent'])) {
                        $present++;
                    } elseif (!empty($dayAtt['isOffday']) || !empty($dayAtt['isHoliday'])) {
                        $dayOff++;
                        $workingDays--;
                    } else {
                        $absent++;
                    }
                }
            }

            if (($present + $absent + $leave) > $workingDays) {
                $workingDays = min($totalPeriodDays, (int) round($present + $absent + $leave));
                $dayOff = max(0, $totalPeriodDays - $workingDays);
            }

            $finalLate = isset($summ['totalLateEntry']) ? (int) $summ['totalLateEntry'] : 0;
            $late = $approvedLates + $finalLate;

            $deduction = floor($finalLate / 3);
            $lateUnpaidAbsentDays = round($absent + $deduction, 2);

            // Apply DB Overrides from attendance_monthly_update if present
            $empPk = (int) $localMatch['id'];
            if (isset($dbOverrides[$empPk])) {
                $ov = $dbOverrides[$empPk];
                $workingDays = isset($ov['working_days']) ? (float) $ov['working_days'] : $workingDays;
                $present = isset($ov['present']) ? (float) $ov['present'] : $present;
                $absent = isset($ov['absent']) ? (float) $ov['absent'] : $absent;
                $leave = isset($ov['leave_days']) ? (float) $ov['leave_days'] : $leave;
                $dayOff = isset($ov['dayoff']) ? (float) $ov['dayoff'] : $dayOff;
                $late = isset($ov['late']) ? (float) $ov['late'] : $late;
                $approvedLates = isset($ov['approved_lates']) ? (float) $ov['approved_lates'] : $approvedLates;
                $finalLate = isset($ov['final_late']) ? (float) $ov['final_late'] : $finalLate;
                $deduction = isset($ov['deduction']) ? (float) $ov['deduction'] : $deduction;
                $lateUnpaidAbsentDays = isset($ov['late_unpaid_absent_days']) ? (float) $ov['late_unpaid_absent_days'] : $lateUnpaidAbsentDays;
            }

            // Apply Attendance Status Filter
            $statusFilter = isset($_GET['attendance_status']) ? trim($_GET['attendance_status']) : 'all';
            if ($statusFilter === 'late') {
                if (!($late > 0 || $approvedLates > 0 || $finalLate > 0)) {
                    continue;
                }
            } elseif ($statusFilter === 'present') {
                if (!($present > 0 && $absent == 0 && $late == 0 && $approvedLates == 0 && $finalLate == 0)) {
                    continue;
                }
            } elseif ($statusFilter === 'absent') {
                if (!($absent > 0)) {
                    continue;
                }
            } elseif ($statusFilter === 'leave') {
                if (!($leave > 0)) {
                    continue;
                }
            } elseif ($statusFilter === 'deduction') {
                if (!($deduction > 0 || $lateUnpaidAbsentDays > 0)) {
                    continue;
                }
            }

            $idCheck = $emp ? 'OK' : ($tipsoiSuccess ? 'Tipsoi Missing' : 'Offline');
            $nameCheck = $emp ? 'OK' : ($tipsoiSuccess ? 'Tipsoi Missing' : 'Offline');

            $aggregatedData[] = [
                'sl' => $index++,
                'name' => $localMatch['full_name'] ?? ($emp['employeeName'] ?? '-'),
                'id' => $localMatch['employee_id'] ?? ($emp['employeeOfficeId'] ?? '-'),
                'designation' => $localMatch['designation'] ?? ($emp['designationName'] ?? '-'),
                'department' => $localMatch['department'] ?? ($emp['departmentName'] ?? '-'),
                'workplace' => $localMatch['workplace'] ?? ($emp['workplaceName'] ?? '-'),
                'empType' => 'Regular',
                'workingDays' => $workingDays,
                'present' => $present,
                'absent' => $absent,
                'leave' => $leave,
                'dayOff' => $dayOff,
                'late' => $late,
                'approvedLates' => $approvedLates,
                'finalLate' => $finalLate,
                'deduction' => $deduction,
                'lateUnpaidAbsentDays' => $lateUnpaidAbsentDays,
                'joiningDate' => !empty($localMatch['joining_date']) ? $localMatch['joining_date'] : '-',
                'resignDate' => !empty($localMatch['resign_date']) ? $localMatch['resign_date'] : '-',
                'idCheck' => $idCheck,
                'nameCheck' => $nameCheck
            ];
        }

        // Generate TCPDF (Landscape A2 for 23 columns)
        $pdf = new \TCPDF('L', PDF_UNIT, 'A2', true, 'UTF-8', false);
        $pdf->SetCreator('HRM System');
        $pdf->SetTitle("Payroll Attendance Report - $fromDateStr to $toDateStr");

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->SetFillColor(7, 129, 52);  // Theme Green header
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(0, 15, 'The Premium Homes Ltd.', 0, 1, 'C', true);

        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetFillColor(255, 255, 255);  // Reset
        $pdf->SetTextColor(0, 0, 0);
        $projectTitleStr = !empty($projectName) ? 'Project: ' . htmlspecialchars($projectName) . ' | ' : '';
        $statusLabels = [
            'late' => 'Late',
            'present' => 'Present',
            'absent' => 'Absent',
            'leave' => 'On Leave',
            'deduction' => 'Has Deduction'
        ];
        $statusTitleStr = (!empty($statusFilter) && isset($statusLabels[$statusFilter])) ? ' | Status: ' . $statusLabels[$statusFilter] : '';
        $pdf->Cell(0, 10, 'Payroll Attendance Report: ' . $projectTitleStr . date('F d, Y', strtotime($fromDateStr)) . ' to ' . date('F d, Y', strtotime($toDateStr)) . $statusTitleStr, 0, 1, 'C', true);
        $pdf->Ln();  // no space before headings

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(7, 129, 52);  // table header color
        $pdf->SetTextColor(255, 255, 255);

        // A2 landscape width is ~574mm left after 20mm margins.
        $widths = [
            10,  // SL
            45,  // Name
            18,  // ID
            40,  // Designation
            35,  // Department
            35,  // Workplace
            25,  // Type
            18,  // Work Days
            16,  // Present
            16,  // Absent
            15,  // Leave
            18,  // Day Off
            12,  // Late
            20,  // App Lates
            20,  // Final Late
            20,  // Deduct
            22,  // Unpaid
            22,  // Join Date
            22,  // Resign Date
            20,  // Effect Date
            35,  // Remarks
            20,  // ID Check
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

            $pdf->Cell($widths[13], 8, (string) $row['approvedLates'], 1, 0, 'C', $fill);
            $pdf->Cell($widths[14], 8, (string) $row['finalLate'], 1, 0, 'C', $fill);
            $pdf->Cell($widths[15], 8, number_format((float) $row['deduction'], 2), 1, 0, 'R', $fill);
            $pdf->Cell($widths[16], 8, (string) $row['lateUnpaidAbsentDays'], 1, 0, 'C', $fill);

            $pdf->Cell($widths[17], 8, $row['joiningDate'], 1, 0, 'C', $fill);  // Join Date
            $pdf->Cell($widths[18], 8, $row['resignDate'], 1, 0, 'C', $fill);  // Resign Date
            $pdf->Cell($widths[19], 8, '-', 1, 0, 'C', $fill);  // Effect Date
            $pdf->Cell($widths[20], 8, '-', 1, 0, 'C', $fill);  // Remarks

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

        $cleanProjectName = !empty($projectName) ? preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($projectName)) . '_' : '';
        $pdf->Output("payroll_attendance_{$cleanProjectName}{$fromDateStr}_to_{$toDateStr}.pdf", 'I');
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

        // Fetch payroll projects
        $projectRes = $this->db->query('SELECT * FROM payroll_projects ORDER BY id DESC');
        $payroll_projects = [];
        if ($projectRes) {
            while ($pRow = $projectRes->fetch_assoc()) {
                $payroll_projects[] = $pRow;
            }
        }

        $payrollProjectId = isset($_GET['payroll_project_id']) && $_GET['payroll_project_id'] !== '' ? (int) $_GET['payroll_project_id'] : null;

        // Auto-synchronize dates from selected project (project dates take authoritative precedence)
        if ($payrollProjectId) {
            foreach ($payroll_projects as $prj) {
                if ($prj['id'] == $payrollProjectId) {
                    if (!empty($prj['is_custom_date']) && !empty($prj['start_date']) && !empty($prj['end_date'])) {
                        $filterType = 'custom';
                        $fromDate = $prj['start_date'];
                        $toDate = $prj['end_date'];
                    } elseif (!empty($prj['date'])) {
                        $filterType = 'monthly';
                        $parts = explode('-', $prj['date']);
                        if (count($parts) >= 2) {
                            $currentYear = $parts[0];
                            $currentMonth = $parts[1];
                            $_GET['year'] = $parts[0];
                            $_GET['month'] = $parts[1];
                        }
                    }
                    break;
                }
            }
        }

        if ($filterType === 'monthly') {
            $m = $_GET['month'] ?? $currentMonth;
            $y = $_GET['year'] ?? $currentYear;
            $fromDate = date('Y-m-d', mktime(0, 0, 0, $m, 1, $y));
            $toDate = date('Y-m-t', mktime(0, 0, 0, $m, 1, $y));
        } elseif ($filterType === 'custom') {
            $fromDate = $_GET['from_date'] ?? $fromDate;
            $toDate = $_GET['to_date'] ?? $toDate;
        }

        $localSql = 'SELECT e.id as real_emp_id_fk, e.employee_id, e.full_name, desg.name AS designation, dept.name AS department,
                            e.workplace, e.joining_date, e.gross_salary, e.basic_salary
                     FROM employees e
                     LEFT JOIN designations desg ON e.designation_id = desg.id
                     LEFT JOIN departments dept ON e.department_id = dept.id
                     WHERE e.joining_date >= ? AND e.joining_date <= ?
                     ORDER BY e.joining_date ASC';

        $stmt = $this->db->prepare($localSql);
        $stmt->bind_param('ss', $fromDate, $toDate);
        $stmt->execute();
        $res = $stmt->get_result();

        $localEmployees = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $localEmployees[] = $row;
            }
        }
        $stmt->close();

        $projectEmployeeIds = [];
        $hasProjectRecords = false;
        if ($payrollProjectId) {
            $projQ = $this->db->query("SELECT employee_id FROM payslips WHERE payroll_project_id = $payrollProjectId UNION SELECT employee_id FROM attendance_monthly_update WHERE payroll_project_id = $payrollProjectId");
            if ($projQ && $projQ->num_rows > 0) {
                $hasProjectRecords = true;
                while ($pr = $projQ->fetch_assoc()) {
                    $projectEmployeeIds[$pr['employee_id']] = true;
                }
            }
        }

        $employees = [];
        foreach ($localEmployees as $localMatch) {
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

        $page_title = 'Payroll Joining List';
        $current_route = 'payroll-joining-list';

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
        $defaultFrom = date('Y-m-01');
        $defaultTo = date('Y-m-t');

        $filterType = $_GET['filter_type'] ?? 'monthly';
        $fromDate = $defaultFrom;
        $toDate = $defaultTo;

        $payrollProjectId = isset($_GET['payroll_project_id']) && $_GET['payroll_project_id'] !== '' ? (int) $_GET['payroll_project_id'] : null;

        $projectName = '';
        if ($payrollProjectId) {
            $pStmt = $this->db->prepare('SELECT name, date, is_custom_date, start_date, end_date FROM payroll_projects WHERE id = ?');
            $pStmt->bind_param('i', $payrollProjectId);
            $pStmt->execute();
            $pRes = $pStmt->get_result();
            if ($pRow = $pRes->fetch_assoc()) {
                $projectName = $pRow['name'];
                if (empty($_GET['from_date']) && empty($_GET['month'])) {
                    if (!empty($pRow['is_custom_date']) && !empty($pRow['start_date']) && !empty($pRow['end_date'])) {
                        $filterType = 'custom';
                        $fromDate = $pRow['start_date'];
                        $toDate = $pRow['end_date'];
                    } elseif (!empty($pRow['date'])) {
                        $parts = explode('-', $pRow['date']);
                        if (count($parts) >= 2) {
                            $_GET['year'] = $parts[0];
                            $_GET['month'] = $parts[1];
                        }
                    }
                }
            }
            $pStmt->close();
        }

        if ($filterType === 'monthly') {
            $m = $_GET['month'] ?? $currentMonth;
            $y = $_GET['year'] ?? $currentYear;
            $fromDate = date('Y-m-d', mktime(0, 0, 0, $m, 1, $y));
            $toDate = date('Y-m-t', mktime(0, 0, 0, $m, 1, $y));
        } elseif ($filterType === 'custom') {
            $fromDate = $_GET['from_date'] ?? $fromDate;
            $toDate = $_GET['to_date'] ?? $toDate;
        }

        $localSql = 'SELECT e.id as real_emp_id_fk, e.employee_id, e.full_name, desg.name AS designation, dept.name AS department,
                            e.workplace, e.joining_date, e.gross_salary, e.basic_salary
                     FROM employees e
                     LEFT JOIN designations desg ON e.designation_id = desg.id
                     LEFT JOIN departments dept ON e.department_id = dept.id
                     WHERE e.joining_date >= ? AND e.joining_date <= ?
                     ORDER BY e.joining_date ASC';

        $stmt = $this->db->prepare($localSql);
        $stmt->bind_param('ss', $fromDate, $toDate);
        $stmt->execute();
        $res = $stmt->get_result();

        $localEmployees = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $localEmployees[] = $row;
            }
        }
        $stmt->close();

        $projectEmployeeIds = [];
        $hasProjectRecords = false;
        if ($payrollProjectId) {
            $projQ = $this->db->query("SELECT employee_id FROM payslips WHERE payroll_project_id = $payrollProjectId UNION SELECT employee_id FROM attendance_monthly_update WHERE payroll_project_id = $payrollProjectId");
            if ($projQ && $projQ->num_rows > 0) {
                $hasProjectRecords = true;
                while ($pr = $projQ->fetch_assoc()) {
                    $projectEmployeeIds[$pr['employee_id']] = true;
                }
            }
        }

        $employees = [];
        foreach ($localEmployees as $localMatch) {
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
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', '', 9);

        $projectHeader = !empty($projectName) ? 'Project: ' . htmlspecialchars($projectName) . ' | ' : '';

        $html = '
        <div style="text-align:center;">
            <h1 style="color:#078134; margin:0; font-size:22px;">The Premium Homes Ltd</h1>
            <h3 style="color:#28B463; margin:3px 0; font-size:15px;">Joining List Report</h3>
            <p style="font-size:11px; color:#555; margin:2px 0;">' . $projectHeader . 'Date Range: ' . date('F d, Y', strtotime($fromDate)) . ' to ' . date('F d, Y', strtotime($toDate)) . '</p>
            <p style="font-size:11px; margin:2px 0;"><b>Total Records: </b> ' . count($employees) . '</p>
        </div>
        <br><br>
        <table border="1" cellpadding="5" cellspacing="0" width="100%">
            <thead>
                <tr style="background-color:#078134; color:#ffffff; font-weight:bold; font-size:10px;">
                    <th width="5%" style="text-align:center;">SL</th>
                    <th width="10%" style="text-align:center;">ID</th>
                    <th width="18%" style="text-align:left;">Name</th>
                    <th width="22%" style="text-align:left;">Department - Designation</th>
                    <th width="15%" style="text-align:center;">Workplace</th>
                    <th width="10%" style="text-align:center;">Joining Date</th>
                    <th width="10%" style="text-align:right;">Gross Salary</th>
                    <th width="10%" style="text-align:right;">Basic Salary</th>
                </tr>
            </thead>
            <tbody>';

        $sl = 1;
        if (empty($employees)) {
            $html .= '<tr><td colspan="8" style="text-align:center; padding:15px;"><b>No joined employees found for this period.</b></td></tr>';
        } else {
            foreach ($employees as $row) {
                $deptDesig = ($row['department'] ?? '-') . ' - ' . ($row['designation'] ?? '-');
                $html .= '<tr style="font-size:9px;">
                            <td width="5%" style="text-align:center;">' . $sl++ . '</td>
                            <td width="10%" style="text-align:center;">' . htmlspecialchars($row['employee_id']) . '</td>
                            <td width="18%" style="text-align:left;"><b>' . htmlspecialchars($row['full_name']) . '</b></td>
                            <td width="22%" style="text-align:left;">' . htmlspecialchars($deptDesig) . '</td>
                            <td width="15%" style="text-align:center;">' . htmlspecialchars($row['workplace'] ?? '-') . '</td>
                            <td width="10%" style="text-align:center;">' . htmlspecialchars($row['joining_date']) . '</td>
                            <td width="10%" style="text-align:right;">৳ ' . htmlspecialchars(number_format($row['gross_salary'] ?? 0, 2)) . '</td>
                            <td width="10%" style="text-align:right;">৳ ' . htmlspecialchars(number_format($row['basic_salary'] ?? 0, 2)) . '</td>
                          </tr>';
            }
        }

        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        if (ob_get_length()) {
            ob_end_clean();
        }
        $filename = 'Joining_List_Report_' . (!empty($projectName) ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $projectName) . '_' : '') . $fromDate . '_to_' . $toDate . '.pdf';
        $pdf->Output($filename, 'D');
        exit;
    }

    public function resignList()
    {
        $currentYear = date('Y');
        $currentMonth = date('m');
        $defaultFrom = date('Y-m-01');
        $defaultTo = date('Y-m-t');

        $filterType = $_GET['filter_type'] ?? 'monthly';
        $fromDate = $defaultFrom;
        $toDate = $defaultTo;

        // Fetch payroll projects
        $projectRes = $this->db->query('SELECT * FROM payroll_projects ORDER BY id DESC');
        $payroll_projects = [];
        if ($projectRes) {
            while ($pRow = $projectRes->fetch_assoc()) {
                $payroll_projects[] = $pRow;
            }
        }

        $payrollProjectId = isset($_GET['payroll_project_id']) && $_GET['payroll_project_id'] !== '' ? (int) $_GET['payroll_project_id'] : null;

        // Auto-synchronize dates from selected project if not explicitly customized
        if ($payrollProjectId) {
            foreach ($payroll_projects as $prj) {
                if ($prj['id'] == $payrollProjectId) {
                    if (empty($_GET['month']) && empty($_GET['from_date'])) {
                        if (!empty($prj['date'])) {
                            $parts = explode('-', $prj['date']);
                            if (count($parts) >= 2) {
                                $_GET['year'] = $parts[0];
                                $_GET['month'] = $parts[1];
                            }
                        }
                    }
                    break;
                }
            }
        }

        if ($filterType === 'monthly') {
            $m = $_GET['month'] ?? $currentMonth;
            $y = $_GET['year'] ?? $currentYear;
            $fromDate = date('Y-m-d', mktime(0, 0, 0, $m, 1, $y));
            $toDate = date('Y-m-t', mktime(0, 0, 0, $m, 1, $y));
        } elseif ($filterType === 'custom') {
            $fromDate = $_GET['from_date'] ?? $defaultFrom;
            $toDate = $_GET['to_date'] ?? $defaultTo;
        }

        $sql = 'SELECT e.id as real_emp_id_fk, e.employee_id, e.full_name, desg.name AS designation, dept.name AS department,
                       e.workplace, e.resign_date, e.employee_status, e.gross_salary
                FROM employees e 
                LEFT JOIN designations desg ON e.designation_id = desg.id
                LEFT JOIN departments dept ON e.department_id = dept.id
                WHERE e.resign_date >= ? AND e.resign_date <= ?
                ORDER BY e.resign_date ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ss', $fromDate, $toDate);
        $stmt->execute();
        $res = $stmt->get_result();

        $localEmployees = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $localEmployees[] = $row;
            }
        }
        $stmt->close();

        // Project filter logic
        $projectEmployeeIds = [];
        $hasProjectRecords = false;
        if ($payrollProjectId) {
            $projQ = $this->db->query("SELECT employee_id FROM payslips WHERE payroll_project_id = $payrollProjectId UNION SELECT employee_id FROM attendance_monthly_update WHERE payroll_project_id = $payrollProjectId");
            if ($projQ && $projQ->num_rows > 0) {
                $hasProjectRecords = true;
                while ($pr = $projQ->fetch_assoc()) {
                    $projectEmployeeIds[$pr['employee_id']] = true;
                }
            }
        }

        $employees = [];
        foreach ($localEmployees as $row) {
            if ($payrollProjectId && $hasProjectRecords && !isset($projectEmployeeIds[$row['real_emp_id_fk']])) {
                continue;
            }
            $employees[] = $row;
        }

        $page_title = 'Resign List';
        $current_route = 'payroll-resign-list';

        $this->view('payrolls/payslips/resign-list', [
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

    public function exportResignListPDF()
    {
        $currentYear = date('Y');
        $currentMonth = date('m');
        $defaultFrom = date('Y-m-01');
        $defaultTo = date('Y-m-t');

        $filterType = $_GET['filter_type'] ?? 'monthly';
        $fromDate = $defaultFrom;
        $toDate = $defaultTo;

        $payrollProjectId = isset($_GET['payroll_project_id']) && $_GET['payroll_project_id'] !== '' ? (int) $_GET['payroll_project_id'] : null;

        $projectName = '';
        if ($payrollProjectId) {
            $pStmt = $this->db->prepare('SELECT name, date FROM payroll_projects WHERE id = ?');
            $pStmt->bind_param('i', $payrollProjectId);
            $pStmt->execute();
            $pRes = $pStmt->get_result();
            if ($pRow = $pRes->fetch_assoc()) {
                $projectName = $pRow['name'];
                if (empty($_GET['month']) && empty($_GET['from_date'])) {
                    if (!empty($pRow['date'])) {
                        $parts = explode('-', $pRow['date']);
                        if (count($parts) >= 2) {
                            $_GET['year'] = $parts[0];
                            $_GET['month'] = $parts[1];
                        }
                    }
                }
            }
            $pStmt->close();
        }

        if ($filterType === 'monthly') {
            $m = $_GET['month'] ?? $currentMonth;
            $y = $_GET['year'] ?? $currentYear;
            $fromDate = date('Y-m-d', mktime(0, 0, 0, $m, 1, $y));
            $toDate = date('Y-m-t', mktime(0, 0, 0, $m, 1, $y));
        } elseif ($filterType === 'custom') {
            $fromDate = $_GET['from_date'] ?? $defaultFrom;
            $toDate = $_GET['to_date'] ?? $defaultTo;
        }

        $sql = 'SELECT e.id as real_emp_id_fk, e.employee_id, e.full_name, desg.name AS designation, dept.name AS department,
                       e.workplace, e.resign_date, e.employee_status, e.gross_salary
                FROM employees e 
                LEFT JOIN designations desg ON e.designation_id = desg.id
                LEFT JOIN departments dept ON e.department_id = dept.id
                WHERE e.resign_date >= ? AND e.resign_date <= ?
                ORDER BY e.resign_date ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ss', $fromDate, $toDate);
        $stmt->execute();
        $res = $stmt->get_result();

        $localEmployees = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $localEmployees[] = $row;
            }
        }
        $stmt->close();

        $projectEmployeeIds = [];
        $hasProjectRecords = false;
        if ($payrollProjectId) {
            $projQ = $this->db->query("SELECT employee_id FROM payslips WHERE payroll_project_id = $payrollProjectId UNION SELECT employee_id FROM attendance_monthly_update WHERE payroll_project_id = $payrollProjectId");
            if ($projQ && $projQ->num_rows > 0) {
                $hasProjectRecords = true;
                while ($pr = $projQ->fetch_assoc()) {
                    $projectEmployeeIds[$pr['employee_id']] = true;
                }
            }
        }

        $employees = [];
        foreach ($localEmployees as $row) {
            if ($payrollProjectId && $hasProjectRecords && !isset($projectEmployeeIds[$row['real_emp_id_fk']])) {
                continue;
            }
            $employees[] = $row;
        }

        $pdf = new \TCPDF('L', 'mm', 'A4');
        $pdf->SetCreator('TPHL ERP');
        $pdf->SetAuthor('Admin');
        $pdf->SetTitle('Resign List Report');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', '', 9);

        $projectHeader = !empty($projectName) ? 'Project: ' . htmlspecialchars($projectName) . ' | ' : '';

        $html = '
        <div style="text-align:center;">
            <h1 style="color:#078134; margin:0; font-size:22px;">The Premium Homes Ltd</h1>
            <h3 style="color:#28B463; margin:3px 0; font-size:15px;">Resign List Report</h3>
            <p style="font-size:11px; color:#555; margin:2px 0;">' . $projectHeader . 'Date Range: ' . date('F d, Y', strtotime($fromDate)) . ' to ' . date('F d, Y', strtotime($toDate)) . '</p>
            <p style="font-size:11px; margin:2px 0;"><b>Total Records: </b> ' . count($employees) . '</p>
        </div>
        <br><br>
        <table border="1" cellpadding="5" cellspacing="0" width="100%">
            <thead>
                <tr style="background-color:#078134; color:#ffffff; font-weight:bold; font-size:10px;">
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
        if (empty($employees)) {
            $html .= '<tr><td colspan="8" style="text-align:center; padding:15px;"><b>No resigned employees found for this period.</b></td></tr>';
        } else {
            foreach ($employees as $row) {
                $deptDesig = ($row['department'] ?? '-') . ' - ' . ($row['designation'] ?? '-');
                $statusText = ucfirst($row['employee_status'] ?? '-');
                if (strtolower($row['employee_status'] ?? '') === 'resigned') {
                    $statusText = 'Resigned';
                }
                $html .= '<tr style="font-size:9px;">
                            <td width="5%" style="text-align:center;">' . $sl++ . '</td>
                            <td width="10%" style="text-align:center;">' . htmlspecialchars($row['employee_id']) . '</td>
                            <td width="18%" style="text-align:left;"><b>' . htmlspecialchars($row['full_name']) . '</b></td>
                            <td width="25%" style="text-align:left;">' . htmlspecialchars($deptDesig) . '</td>
                            <td width="12%" style="text-align:center;">' . htmlspecialchars($row['workplace'] ?? '-') . '</td>
                            <td width="12%" style="text-align:center;">' . htmlspecialchars($row['resign_date'] ?? '-') . '</td>
                            <td width="8%" style="text-align:center;">' . htmlspecialchars($statusText) . '</td>
                            <td width="10%" style="text-align:right;">৳ ' . htmlspecialchars(number_format($row['gross_salary'] ?? 0, 2)) . '</td>
                          </tr>';
            }
        }

        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        if (ob_get_length()) {
            ob_end_clean();
        }
        $filename = 'Resign_List_Report_' . (!empty($projectName) ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $projectName) . '_' : '') . $fromDate . '_to_' . $toDate . '.pdf';
        $pdf->Output($filename, 'D');
        exit;
    }

    public function detailedSummary()
    {
        $projectRes = $this->db->query('SELECT * FROM payroll_projects ORDER BY id DESC');
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
                WHERE p.payroll_project_id = ? 
                  AND p.status != 'draft' 
                  AND (p.is_excluded = 0 OR p.is_excluded IS NULL)
                ORDER BY dept.name ASC, e.employee_id ASC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $payrollProjectId);
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

        $page_title = 'Detailed Summary';
        $current_route = 'payroll-detailed-summary';

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
            echo 'Payroll Project is required.';
            exit;
        }

        $projName = '';
        $projDate = '';
        $q = $this->db->query("SELECT name, date FROM payroll_projects WHERE id = $payrollProjectId");
        if ($q && $r = $q->fetch_assoc()) {
            $projName = $r['name'];
            $projDate = $r['date'] ?? '';
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
            WHERE p.payroll_project_id = ? 
              AND p.status != 'draft' 
              AND (p.is_excluded = 0 OR p.is_excluded IS NULL)
            ORDER BY dept.name ASC, e.employee_id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $payrollProjectId);
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
            <p>Project: ' . htmlspecialchars($projName) . ($projDate ? ' (' . htmlspecialchars($projDate) . ')' : '') . '</p>
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
        $pdf->Output('Detailed_Summary_Report_' . preg_replace('/[^a-zA-Z0-9]/', '_', $projName) . '.pdf', 'I');
        exit;
    }

    public function summaryReport()
    {
        $projectRes = $this->db->query("SELECT * FROM payroll_projects ORDER BY COALESCE(NULLIF(date, ''), start_date, '1970-01-01') ASC, id ASC");
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
            $projectIds = array_map('intval', array_column($payroll_projects, 'id'));
            $fromIndex = array_search($fromProjectId, $projectIds);
            $toIndex = array_search($toProjectId, $projectIds);

            if ($fromIndex !== false && $toIndex !== false) {
                $startIndex = min($fromIndex, $toIndex);
                $endIndex = max($fromIndex, $toIndex);
                $targetProjects = array_slice($payroll_projects, $startIndex, $endIndex - $startIndex + 1);
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
            $targetProjectIds = array_map('intval', array_column($targetProjects, 'id'));
            $inClause = implode(',', $targetProjectIds);

            $sql = "
                SELECT p.id, p.payroll_project_id, p.net_salary, p.payment_method, p.employee_id,
                       COALESCE(p.tax, 0) as tax_deducted,
                       COALESCE(p.bank_amount, 0) as bank_amount,
                       COALESCE(p.cash_amount, 0) as cash_amount
                FROM payslips p
                WHERE p.payroll_project_id IN ($inClause)
                  AND p.status != 'draft'
                  AND (p.is_excluded = 0 OR p.is_excluded IS NULL)
            ";

            $res = $this->db->query($sql);

            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $pid = (int) $row['payroll_project_id'];

                    if (!isset($data['No of employees'][$pid]))
                        continue;

                    $netSalary = (float) $row['net_salary'];
                    $taxDeducted = (float) $row['tax_deducted'];
                    $bankAmount = (float) $row['bank_amount'];
                    $cashAmount = (float) $row['cash_amount'];

                    $data['No of employees'][$pid] += 1;
                    $data['Net Salary (BDT)'][$pid] += $netSalary;
                    $data['Tax Deducted(BDT)'][$pid] += $taxDeducted;
                    $data['Net Payable(BDT)'][$pid] += $netSalary;

                    if ($bankAmount > 0 || $cashAmount > 0) {
                        $data['Salary via Bank(BDT)'][$pid] += $bankAmount;
                        $data['Salary via Cash(BDT)'][$pid] += $cashAmount;
                    } else {
                        $method = strtolower($row['payment_method'] ?? '');
                        if ($method === 'bank' || $method === 'bank transfer') {
                            $data['Salary via Bank(BDT)'][$pid] += $netSalary;
                        } elseif ($method === 'cash') {
                            $data['Salary via Cash(BDT)'][$pid] += $netSalary;
                        }
                    }
                }
            }
        }

        $page_title = 'Payroll Summary Report';
        $current_route = 'payroll-summary-report';

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
            echo 'Both From and To Payroll Projects are required for PDF export.';
            exit;
        }

        $projectRes = $this->db->query("SELECT * FROM payroll_projects ORDER BY COALESCE(NULLIF(date, ''), start_date, '1970-01-01') ASC, id ASC");
        $payroll_projects = [];
        if ($projectRes) {
            while ($pRow = $projectRes->fetch_assoc()) {
                $payroll_projects[] = $pRow;
            }
        }

        $targetProjects = [];
        $projectIds = array_map('intval', array_column($payroll_projects, 'id'));
        $fromIndex = array_search($fromProjectId, $projectIds);
        $toIndex = array_search($toProjectId, $projectIds);

        if ($fromIndex !== false && $toIndex !== false) {
            $startIndex = min($fromIndex, $toIndex);
            $endIndex = max($fromIndex, $toIndex);
            $targetProjects = array_slice($payroll_projects, $startIndex, $endIndex - $startIndex + 1);
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
            $targetProjectIds = array_map('intval', array_column($targetProjects, 'id'));
            $inClause = implode(',', $targetProjectIds);

            $sql = "
                SELECT p.id, p.payroll_project_id, p.net_salary, p.payment_method, p.employee_id,
                       COALESCE(p.tax, 0) as tax_deducted,
                       COALESCE(p.bank_amount, 0) as bank_amount,
                       COALESCE(p.cash_amount, 0) as cash_amount
                FROM payslips p
                WHERE p.payroll_project_id IN ($inClause)
                  AND p.status != 'draft'
                  AND (p.is_excluded = 0 OR p.is_excluded IS NULL)
            ";

            $res = $this->db->query($sql);

            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $pid = (int) $row['payroll_project_id'];

                    if (!isset($data['No of employees'][$pid]))
                        continue;

                    $netSalary = (float) $row['net_salary'];
                    $taxDeducted = (float) $row['tax_deducted'];
                    $bankAmount = (float) $row['bank_amount'];
                    $cashAmount = (float) $row['cash_amount'];

                    $data['No of employees'][$pid] += 1;
                    $data['Net Salary (BDT)'][$pid] += $netSalary;
                    $data['Tax Deducted(BDT)'][$pid] += $taxDeducted;
                    $data['Net Payable(BDT)'][$pid] += $netSalary;

                    if ($bankAmount > 0 || $cashAmount > 0) {
                        $data['Salary via Bank(BDT)'][$pid] += $bankAmount;
                        $data['Salary via Cash(BDT)'][$pid] += $cashAmount;
                    } else {
                        $method = strtolower($row['payment_method'] ?? '');
                        if ($method === 'bank' || $method === 'bank transfer') {
                            $data['Salary via Bank(BDT)'][$pid] += $netSalary;
                        } elseif ($method === 'cash') {
                            $data['Salary via Cash(BDT)'][$pid] += $netSalary;
                        }
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

        $rangeSubtitle = '';
        if (!empty($targetProjects)) {
            $firstProj = $targetProjects[0];
            $lastProj = end($targetProjects);
            $rangeSubtitle = '<p style="font-size:11px; color:#555; margin:2px 0;">Range: ' . htmlspecialchars($firstProj['name']) . ' (' . htmlspecialchars($firstProj['date'] ?? '') . ') to ' . htmlspecialchars($lastProj['name']) . ' (' . htmlspecialchars($lastProj['date'] ?? '') . ')</p>';
        }

        $html = '
        <div style="text-align:center;">
            <h1 style="color:#078134; margin:0; font-size:22px;">The Premium Homes Ltd</h1>
            <h3 style="color:#28B463; margin:3px 0; font-size:15px;">Payroll Summary Report</h3>
            ' . $rangeSubtitle . '
        </div>
        <br>
        <table border="1" cellpadding="5" width="100%">
            <thead>
                <tr style="background-color:#078134; color:#ffffff; font-weight:bold; text-align:center;">
                    <th width="30%">Particulars</th>';

        foreach ($targetProjects as $proj) {
            $title = $proj['name'] . '<br><small>(' . ($proj['date'] ?? '') . ')</small>';
            $html .= '<th width="15%">' . $title . '</th>';
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
        $pdf->Output('Payroll_Summary_Report_' . time() . '.pdf', 'I');
        exit;
    }

    public function salaryReport()
    {
        $reportType = $_GET['report_type'] ?? 'department';
        $payrollProjectId = isset($_GET['payroll_project_id']) && $_GET['payroll_project_id'] !== '' ? (int) $_GET['payroll_project_id'] : null;

        $projectRes = $this->db->query('SELECT * FROM payroll_projects ORDER BY id DESC');
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
                        WHERE p.payroll_project_id = ? 
                          AND p.status != 'draft' 
                          AND (p.is_excluded = 0 OR p.is_excluded IS NULL)
                        GROUP BY d.id
                        ORDER BY d.name ASC";
                $stmt = $this->db->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param('i', $payrollProjectId);
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
                            SUM(CASE 
                                WHEN (p.bank_amount > 0 OR p.cash_amount > 0) THEN COALESCE(p.bank_amount, 0)
                                WHEN LOWER(COALESCE(p.payment_method, '')) LIKE '%bank%' THEN p.net_salary
                                ELSE 0 
                            END) AS bank_amount,
                            SUM(CASE 
                                WHEN (p.bank_amount > 0 OR p.cash_amount > 0) THEN COALESCE(p.cash_amount, 0)
                                WHEN LOWER(COALESCE(p.payment_method, '')) LIKE '%cash%' THEN p.net_salary
                                ELSE 0 
                            END) AS cash_amount
                        FROM payslips p
                        JOIN employees e ON p.employee_id = e.id
                        LEFT JOIN office_branches ob ON e.office_branch_id = ob.id
                        WHERE p.payroll_project_id = ? 
                          AND p.status != 'draft' 
                          AND (p.is_excluded = 0 OR p.is_excluded IS NULL)
                        GROUP BY ob.id
                        ORDER BY office_name ASC";
                $stmt = $this->db->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param('i', $payrollProjectId);
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
            echo 'Payroll Project is required for PDF export.';
            exit;
        }

        $projName = 'None';
        $projDate = '';
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
                    WHERE p.payroll_project_id = ? 
                      AND p.status != 'draft' 
                      AND (p.is_excluded = 0 OR p.is_excluded IS NULL)
                    GROUP BY d.id
                    ORDER BY d.name ASC";
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $payrollProjectId);
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
                        SUM(CASE 
                            WHEN (p.bank_amount > 0 OR p.cash_amount > 0) THEN COALESCE(p.bank_amount, 0)
                            WHEN LOWER(COALESCE(p.payment_method, '')) LIKE '%bank%' THEN p.net_salary
                            ELSE 0 
                        END) AS bank_amount,
                        SUM(CASE 
                            WHEN (p.bank_amount > 0 OR p.cash_amount > 0) THEN COALESCE(p.cash_amount, 0)
                            WHEN LOWER(COALESCE(p.payment_method, '')) LIKE '%cash%' THEN p.net_salary
                            ELSE 0 
                        END) AS cash_amount
                    FROM payslips p
                    JOIN employees e ON p.employee_id = e.id
                    LEFT JOIN office_branches ob ON e.office_branch_id = ob.id
                    WHERE p.payroll_project_id = ? 
                      AND p.status != 'draft' 
                      AND (p.is_excluded = 0 OR p.is_excluded IS NULL)
                    GROUP BY ob.id
                    ORDER BY office_name ASC";
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $payrollProjectId);
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
        $pdf->Output('Salary_Report_' . ucfirst($reportType) . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $projName) . '.pdf', 'I');
        exit;
    }

    public function exportSalaryReportCSV()
    {
        $reportType = $_GET['report_type'] ?? 'department';
        $payrollProjectId = isset($_GET['payroll_project_id']) && $_GET['payroll_project_id'] !== '' ? (int) $_GET['payroll_project_id'] : null;

        if (!$payrollProjectId) {
            echo 'Payroll Project is required for CSV export.';
            exit;
        }

        $projName = 'None';
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
                    WHERE p.payroll_project_id = ? 
                      AND p.status != 'draft' 
                      AND (p.is_excluded = 0 OR p.is_excluded IS NULL)
                    GROUP BY d.id
                    ORDER BY d.name ASC";
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $payrollProjectId);
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
                        SUM(CASE 
                            WHEN (p.bank_amount > 0 OR p.cash_amount > 0) THEN COALESCE(p.bank_amount, 0)
                            WHEN LOWER(COALESCE(p.payment_method, '')) LIKE '%bank%' THEN p.net_salary
                            ELSE 0 
                        END) AS bank_amount,
                        SUM(CASE 
                            WHEN (p.bank_amount > 0 OR p.cash_amount > 0) THEN COALESCE(p.cash_amount, 0)
                            WHEN LOWER(COALESCE(p.payment_method, '')) LIKE '%cash%' THEN p.net_salary
                            ELSE 0 
                        END) AS cash_amount
                    FROM payslips p
                    JOIN employees e ON p.employee_id = e.id
                    LEFT JOIN office_branches ob ON e.office_branch_id = ob.id
                    WHERE p.payroll_project_id = ? 
                      AND p.status != 'draft' 
                      AND (p.is_excluded = 0 OR p.is_excluded IS NULL)
                    GROUP BY ob.id
                    ORDER BY office_name ASC";
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $payrollProjectId);
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

        $filename = 'Salary_Report_' . ucfirst($reportType) . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $projName) . '.csv';
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
        $selectedProject = isset($_GET['payroll_project_id']) ? $_GET['payroll_project_id'] : (isset($_GET['project_id']) ? $_GET['project_id'] : '');

        // Fetch payroll projects
        $projectRes = $this->db->query('SELECT * FROM payroll_projects ORDER BY id DESC');
        $payroll_projects = [];
        if ($projectRes) {
            while ($pRow = $projectRes->fetch_assoc()) {
                $payroll_projects[] = $pRow;
            }
        }

        // Default to first project if not specified
        if (empty($selectedProject) && !empty($payroll_projects)) {
            $selectedProject = $payroll_projects[0]['id'];
        }

        // Auto-sync year & month from selected project if not explicitly set in $_GET
        if (!empty($selectedProject)) {
            foreach ($payroll_projects as $prj) {
                if ($prj['id'] == $selectedProject) {
                    if (empty($_GET['year']) || empty($_GET['month'])) {
                        if (!empty($prj['date'])) {
                            $dateParts = explode('-', $prj['date']);
                            if (count($dateParts) >= 2) {
                                $selectedYear = $dateParts[0];
                                $selectedMonth = $dateParts[1];
                            }
                        }
                    }
                    break;
                }
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
            $stmt = $this->db->prepare("
                SELECT p.id as payslip_id, p.net_salary, p.total_earning, p.salary_hold, p.remarks,
                       e.employee_id, e.full_name, e.gross_salary, dept.name as department_name, desg.name as designation_name
                FROM payslips p
                JOIN employees e ON p.employee_id = e.id
                LEFT JOIN departments dept ON e.department_id = dept.id
                LEFT JOIN designations desg ON e.designation_id = desg.id
                WHERE p.payroll_project_id = ? 
                  AND YEAR(p.payslip_month) = ? 
                  AND MONTH(p.payslip_month) = ?
                  AND LOWER(TRIM(p.salary_hold)) = 'hold'
                ORDER BY e.employee_id ASC
            ");
            $projIdInt = (int) $selectedProject;
            $yearInt = (int) $selectedYear;
            $monthInt = (int) $selectedMonth;
            $stmt->bind_param('iii', $projIdInt, $yearInt, $monthInt);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res) {
                while ($h = $res->fetch_assoc()) {
                    $h['effective_amount'] = floatval($h['net_salary'] > 0 ? $h['net_salary'] : ($h['gross_salary'] ?? 0));
                    $holds[] = $h;
                }
            }
            $stmt->close();
        }

        $this->view('payrolls/payslips/salary_hold', [
            'current_route' => 'payroll-salary-hold',
            'page_title' => 'Salary Hold Management',
            'payroll_projects' => $payroll_projects,
            'employeesList' => $employeesList,
            'holds' => $holds,
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'selectedProject' => $selectedProject
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
        $payslipMonth = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01';

        // Check if payslip already exists
        $checkStmt = $this->db->prepare('SELECT id FROM payslips WHERE employee_id = ? AND payroll_project_id = ? AND YEAR(payslip_month) = ? AND MONTH(payslip_month) = ?');
        $checkStmt->bind_param('iiii', $employeeId, $projectId, $year, $month);
        $checkStmt->execute();
        $res = $checkStmt->get_result();

        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $payslipId = $row['id'];
            $updateStmt = $this->db->prepare("UPDATE payslips SET salary_hold = 'Hold' WHERE id = ?");
            $updateStmt->bind_param('i', $payslipId);
            $updateStmt->execute();
            $updateStmt->close();
        } else {
            $grossSalary = 0.0;
            $eStmt = $this->db->prepare('SELECT gross_salary FROM employees WHERE id = ?');
            $eStmt->bind_param('i', $employeeId);
            $eStmt->execute();
            $eRes = $eStmt->get_result();
            if ($eRow = $eRes->fetch_assoc()) {
                $grossSalary = floatval($eRow['gross_salary'] ?? 0);
            }
            $eStmt->close();

            $draftStatus = 'draft';
            $insertStmt = $this->db->prepare("INSERT INTO payslips (employee_id, payroll_project_id, payslip_month, salary_hold, net_salary, total_earning, status) VALUES (?, ?, ?, 'Hold', ?, ?, ?)");
            $insertStmt->bind_param('iisdds', $employeeId, $projectId, $payslipMonth, $grossSalary, $grossSalary, $draftStatus);
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
        $updateStmt->bind_param('i', $payslipId);
        if ($updateStmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Salary Hold removed.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove Salary Hold.']);
        }
        $updateStmt->close();
    }

    private function getLiquidityData($payrollProjectId)
    {
        $totalRequirement = 0.0;
        $totalBankRequirement = 0.0;
        $totalCashRequirement = 0.0;
        $bankEmployees = [];
        $cashEmployees = [];

        if ($payrollProjectId) {
            $sql = "
                SELECT p.id as payslip_id, p.net_salary, p.payment_method, p.bank_amount, p.cash_amount,
                       e.id as emp_id_pk, e.employee_id, e.full_name, 
                       e.bank_name, e.account_number, e.account_holder_name,
                       COALESCE(dept.name, 'Unassigned') as department_name,
                       COALESCE(desg.name, 'N/A') as designation_name
                FROM payslips p
                JOIN employees e ON p.employee_id = e.id
                LEFT JOIN departments dept ON e.department_id = dept.id
                LEFT JOIN designations desg ON e.designation_id = desg.id
                WHERE p.payroll_project_id = ?
                  AND (p.is_excluded = 0 OR p.is_excluded IS NULL)
                ORDER BY dept.name ASC, e.employee_id ASC
            ";
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $payrollProjectId);
                $stmt->execute();
                $res = $stmt->get_result();

                if ($res) {
                    while ($row = $res->fetch_assoc()) {
                        $netSalary = (float) $row['net_salary'];
                        $bankAmount = (float) ($row['bank_amount'] ?? 0);
                        $cashAmount = (float) ($row['cash_amount'] ?? 0);
                        $method = strtolower(trim($row['payment_method'] ?? ''));

                        $totalRequirement += $netSalary;

                        if ($bankAmount > 0 && $cashAmount > 0) {
                            $totalBankRequirement += $bankAmount;
                            $bankRow = $row;
                            $bankRow['payment_type'] = 'Bank';
                            $bankRow['disbursement_amount'] = $bankAmount;
                            $bankEmployees[] = $bankRow;

                            $totalCashRequirement += $cashAmount;
                            $cashRow = $row;
                            $cashRow['payment_type'] = 'Cash';
                            $cashRow['disbursement_amount'] = $cashAmount;
                            $cashEmployees[] = $cashRow;
                        } elseif ($bankAmount > 0) {
                            $totalBankRequirement += $netSalary;
                            $bankRow = $row;
                            $bankRow['payment_type'] = 'Bank';
                            $bankRow['disbursement_amount'] = $netSalary;
                            $bankEmployees[] = $bankRow;
                        } elseif ($cashAmount > 0) {
                            $totalCashRequirement += $netSalary;
                            $cashRow = $row;
                            $cashRow['payment_type'] = 'Cash';
                            $cashRow['disbursement_amount'] = $netSalary;
                            $cashEmployees[] = $cashRow;
                        } else {
                            if ($method === 'cash') {
                                $totalCashRequirement += $netSalary;
                                $row['payment_type'] = 'Cash';
                                $row['disbursement_amount'] = $netSalary;
                                $cashEmployees[] = $row;
                            } else {
                                $totalBankRequirement += $netSalary;
                                $row['payment_type'] = 'Bank';
                                $row['disbursement_amount'] = $netSalary;
                                $bankEmployees[] = $row;
                            }
                        }
                    }
                }
                $stmt->close();
            }
        }

        return [
            'totalRequirement' => $totalRequirement,
            'totalBankRequirement' => $totalBankRequirement,
            'totalCashRequirement' => $totalCashRequirement,
            'bankEmployees' => $bankEmployees,
            'cashEmployees' => $cashEmployees,
        ];
    }

    public function liquidityReport()
    {
        $projectRes = $this->db->query('SELECT * FROM payroll_projects ORDER BY id DESC');
        $payroll_projects = [];
        if ($projectRes) {
            while ($pRow = $projectRes->fetch_assoc()) {
                $payroll_projects[] = $pRow;
            }
        }

        $selectedProjectId = isset($_GET['payroll_project_id']) && $_GET['payroll_project_id'] !== ''
            ? (int) $_GET['payroll_project_id']
            : (isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int) $_GET['project_id'] : null);

        if (!$selectedProjectId && !empty($payroll_projects)) {
            $selectedProjectId = (int) $payroll_projects[0]['id'];
        }

        $selectedProject = null;
        if ($selectedProjectId) {
            foreach ($payroll_projects as $p) {
                if ((int) $p['id'] === $selectedProjectId) {
                    $selectedProject = $p;
                    break;
                }
            }
        }

        $data = $this->getLiquidityData($selectedProjectId);

        $this->view('payrolls/payslips/liquidity-report', [
            'page_title' => 'Liquidity Management Report',
            'current_route' => 'payroll-liquidity-report',
            'payroll_projects' => $payroll_projects,
            'selected_project_id' => $selectedProjectId,
            'selectedProject' => $selectedProject,
            'totalRequirement' => $data['totalRequirement'],
            'totalBankRequirement' => $data['totalBankRequirement'],
            'totalCashRequirement' => $data['totalCashRequirement'],
            'bankEmployees' => $data['bankEmployees'],
            'cashEmployees' => $data['cashEmployees'],
        ]);
    }

    public function exportLiquidityReportPDF()
    {
        $selectedProjectId = isset($_GET['payroll_project_id']) && $_GET['payroll_project_id'] !== ''
            ? (int) $_GET['payroll_project_id']
            : (isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int) $_GET['project_id'] : null);

        if (!$selectedProjectId) {
            echo 'Payroll Project is required for PDF export.';
            exit;
        }

        $projName = 'Payroll Project';
        $projDate = '';
        $projRes = $this->db->query("SELECT name, date, start_date, end_date FROM payroll_projects WHERE id = $selectedProjectId");
        if ($projRes && $pRow = $projRes->fetch_assoc()) {
            $projName = $pRow['name'];
            $projDate = !empty($pRow['date']) ? $pRow['date'] : ($pRow['start_date'] ?? '');
        }

        $data = $this->getLiquidityData($selectedProjectId);

        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator('TPHL-ERP');
        $pdf->SetAuthor('TPHL-ERP');
        $pdf->SetTitle('Liquidity Management Report - ' . $projName);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage('L');
        $pdf->SetFont('helvetica', '', 9);

        $html = '<div style="text-align:center;">';
        $html .= '<h2 style="margin-bottom:4px; color:#1a1a1a;">Liquidity Management Report</h2>';
        $html .= '<h4 style="margin-top:0; color:#555555;">Project: ' . htmlspecialchars($projName) . ($projDate ? ' (' . htmlspecialchars($projDate) . ')' : '') . '</h4>';
        $html .= '</div><br>';

        // 3 Summary Cards
        $html .= '<table cellpadding="8" cellspacing="4" style="width:100%; margin-bottom:15px;">
            <tr>
                <td style="background-color:#e8f5e9; border:1px solid #c8e6c9; border-radius:4px; text-align:center;">
                    <span style="font-size:10px; color:#2e7d32; font-weight:bold;">TOTAL PAYROLL REQUIREMENT</span><br>
                    <span style="font-size:15px; font-weight:bold; color:#1b5e20;">BDT ' . number_format($data['totalRequirement'], 2) . '</span>
                </td>
                <td style="background-color:#e3f2fd; border:1px solid #bbdefb; border-radius:4px; text-align:center;">
                    <span style="font-size:10px; color:#1565c0; font-weight:bold;">BANK TRANSFER REQUIREMENT</span><br>
                    <span style="font-size:15px; font-weight:bold; color:#0d47a1;">BDT ' . number_format($data['totalBankRequirement'], 2) . '</span><br>
                    <span style="font-size:9px; color:#555;">(' . count($data['bankEmployees']) . ' Employees)</span>
                </td>
                <td style="background-color:#fff3e0; border:1px solid #ffe0b2; border-radius:4px; text-align:center;">
                    <span style="font-size:10px; color:#e65100; font-weight:bold;">CASH IN HAND REQUIREMENT</span><br>
                    <span style="font-size:15px; font-weight:bold; color:#bf360c;">BDT ' . number_format($data['totalCashRequirement'], 2) . '</span><br>
                    <span style="font-size:9px; color:#555;">(' . count($data['cashEmployees']) . ' Employees)</span>
                </td>
            </tr>
        </table><br>';

        // Table 1: Bank Transfer List
        $html .= '<h3 style="color:#0d47a1; margin-bottom:6px;">1. Bank Transfer Disbursement List</h3>';
        $html .= '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse; width:100%; font-size:9px;">
            <thead>
                <tr style="background-color:#0d47a1; color:#ffffff; font-weight:bold;">
                    <th width="5%" style="text-align:center;">SL</th>
                    <th width="12%" style="text-align:center;">Employee ID</th>
                    <th width="20%">Employee Name</th>
                    <th width="15%">Department</th>
                    <th width="10%" style="text-align:center;">Payment Type</th>
                    <th width="15%">Bank Name</th>
                    <th width="13%">Account Number</th>
                    <th width="10%" style="text-align:right;">Amount (BDT)</th>
                </tr>
            </thead>
            <tbody>';

        if (empty($data['bankEmployees'])) {
            $html .= '<tr><td colspan="8" style="text-align:center; padding:10px; color:#777;">No bank disbursement records found.</td></tr>';
        } else {
            $sl = 1;
            foreach ($data['bankEmployees'] as $bEmp) {
                $html .= '<tr>
                    <td style="text-align:center;">' . $sl++ . '</td>
                    <td style="text-align:center; font-weight:bold;">' . htmlspecialchars($bEmp['employee_id']) . '</td>
                    <td>' . htmlspecialchars($bEmp['full_name']) . '</td>
                    <td>' . htmlspecialchars($bEmp['department_name']) . '</td>
                    <td style="text-align:center;">' . htmlspecialchars($bEmp['payment_type'] ?? 'Bank') . '</td>
                    <td>' . htmlspecialchars($bEmp['bank_name'] ?: 'N/A') . '</td>
                    <td>' . htmlspecialchars($bEmp['account_number'] ?: 'N/A') . '</td>
                    <td style="text-align:right; font-weight:bold;">' . number_format($bEmp['disbursement_amount'], 2) . '</td>
                </tr>';
            }
        }

        $html .= '</tbody>
            <tfoot>
                <tr style="background-color:#e3f2fd; font-weight:bold;">
                    <td colspan="7" style="text-align:right;">Total Bank Transfer:</td>
                    <td style="text-align:right; color:#0d47a1;">' . number_format($data['totalBankRequirement'], 2) . '</td>
                </tr>
            </tfoot>
        </table><br><br>';

        // Table 2: Cash in Hand List
        $html .= '<h3 style="color:#bf360c; margin-bottom:6px;">2. Cash in Hand Disbursement List</h3>';
        $html .= '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse; width:100%; font-size:9px;">
            <thead>
                <tr style="background-color:#bf360c; color:#ffffff; font-weight:bold;">
                    <th width="6%" style="text-align:center;">SL</th>
                    <th width="16%" style="text-align:center;">Employee ID</th>
                    <th width="30%">Employee Name</th>
                    <th width="24%">Department</th>
                    <th width="12%" style="text-align:center;">Payment Type</th>
                    <th width="12%" style="text-align:right;">Amount (BDT)</th>
                </tr>
            </thead>
            <tbody>';

        if (empty($data['cashEmployees'])) {
            $html .= '<tr><td colspan="6" style="text-align:center; padding:10px; color:#777;">No cash disbursement records found.</td></tr>';
        } else {
            $sl = 1;
            foreach ($data['cashEmployees'] as $cEmp) {
                $html .= '<tr>
                    <td style="text-align:center;">' . $sl++ . '</td>
                    <td style="text-align:center; font-weight:bold;">' . htmlspecialchars($cEmp['employee_id']) . '</td>
                    <td>' . htmlspecialchars($cEmp['full_name']) . '</td>
                    <td>' . htmlspecialchars($cEmp['department_name']) . '</td>
                    <td style="text-align:center;">' . htmlspecialchars($cEmp['payment_type'] ?? 'Cash') . '</td>
                    <td style="text-align:right; font-weight:bold;">' . number_format($cEmp['disbursement_amount'], 2) . '</td>
                </tr>';
            }
        }

        $html .= '</tbody>
            <tfoot>
                <tr style="background-color:#fff3e0; font-weight:bold;">
                    <td colspan="5" style="text-align:right;">Total Cash in Hand:</td>
                    <td style="text-align:right; color:#bf360c;">' . number_format($data['totalCashRequirement'], 2) . '</td>
                </tr>
            </tfoot>
        </table>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $cleanProj = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($projName));
        $pdf->Output("Liquidity_Management_Report_{$cleanProj}.pdf", 'I');
        exit;
    }

    public function exportLiquidityReportCSV()
    {
        $selectedProjectId = isset($_GET['payroll_project_id']) && $_GET['payroll_project_id'] !== ''
            ? (int) $_GET['payroll_project_id']
            : (isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int) $_GET['project_id'] : null);

        if (!$selectedProjectId) {
            echo 'Payroll Project is required for CSV export.';
            exit;
        }

        $projName = 'Payroll Project';
        $projRes = $this->db->query("SELECT name, date, start_date, end_date FROM payroll_projects WHERE id = $selectedProjectId");
        if ($projRes && $pRow = $projRes->fetch_assoc()) {
            $projName = $pRow['name'];
        }

        $data = $this->getLiquidityData($selectedProjectId);

        $cleanProj = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($projName));
        $filename = "Liquidity_Management_Report_{$cleanProj}_" . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM for Excel compatibility
        fwrite($out, "\u{FEFF}");

        // Header Title
        fputcsv($out, ['LIQUIDITY MANAGEMENT REPORT']);
        fputcsv($out, ['Project Name', $projName]);
        fputcsv($out, ['Generated At', date('Y-m-d H:i:s')]);
        fputcsv($out, []);

        // KPI Summary Block
        fputcsv($out, ['--- LIQUIDITY SUMMARY ---']);
        fputcsv($out, ['Metric', 'Amount (BDT)', 'Employee Count']);
        fputcsv($out, ['Total Payroll Requirement', number_format($data['totalRequirement'], 2, '.', ''), count($data['bankEmployees']) + count($data['cashEmployees'])]);
        fputcsv($out, ['Bank Transfer Requirement', number_format($data['totalBankRequirement'], 2, '.', ''), count($data['bankEmployees'])]);
        fputcsv($out, ['Cash in Hand Requirement', number_format($data['totalCashRequirement'], 2, '.', ''), count($data['cashEmployees'])]);
        fputcsv($out, []);

        // Bank Transfer List
        fputcsv($out, ['--- 1. BANK TRANSFER DISBURSEMENT LIST ---']);
        fputcsv($out, ['SL', 'Employee ID', 'Employee Name', 'Department', 'Payment Type', 'Bank Name', 'Account Number', 'Amount (BDT)']);
        $sl = 1;
        foreach ($data['bankEmployees'] as $bEmp) {
            fputcsv($out, [
                $sl++,
                $bEmp['employee_id'],
                $bEmp['full_name'],
                $bEmp['department_name'],
                $bEmp['payment_type'] ?? 'Bank',
                $bEmp['bank_name'] ?: 'N/A',
                $bEmp['account_number'] ? "'" . $bEmp['account_number'] : 'N/A',
                number_format($bEmp['disbursement_amount'], 2, '.', '')
            ]);
        }
        fputcsv($out, ['', '', '', '', '', '', 'Total Bank Transfer', number_format($data['totalBankRequirement'], 2, '.', '')]);
        fputcsv($out, []);

        // Cash in Hand List
        fputcsv($out, ['--- 2. CASH IN HAND DISBURSEMENT LIST ---']);
        fputcsv($out, ['SL', 'Employee ID', 'Employee Name', 'Department', 'Payment Type', 'Amount (BDT)']);
        $sl = 1;
        foreach ($data['cashEmployees'] as $cEmp) {
            fputcsv($out, [
                $sl++,
                $cEmp['employee_id'],
                $cEmp['full_name'],
                $cEmp['department_name'],
                $cEmp['payment_type'] ?? 'Cash',
                number_format($cEmp['disbursement_amount'], 2, '.', '')
            ]);
        }
        fputcsv($out, ['', '', '', '', 'Total Cash in Hand', number_format($data['totalCashRequirement'], 2, '.', '')]);

        fclose($out);
        exit;
    }
}
