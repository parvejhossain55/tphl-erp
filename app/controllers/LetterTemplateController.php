<?php

class LetterTemplateController extends Controller
{
    protected $db;
    protected $employeeModel;
    protected $templateModel;
    protected $letterModel;
    protected $hrSignatureModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->employeeModel = new Employee($this->db);
        $this->templateModel = new LetterTemplate($this->db);
        $this->letterModel = new Letter($this->db);
        require_once __DIR__ . '/../models/HrSignature.php';
        $this->hrSignatureModel = new HrSignature($this->db);
        $this->hrSignatureModel->seedLegacyIfEmpty();
    }

    // ─── AJAX: Get Employee basic info ────────────────────────────────────────
    public function getEmployeeInfo()
    {
        if (!isset($_GET['employee_id'])) {
            echo json_encode(['success' => false]);
            return;
        }

        $empId = (int) $_GET['employee_id'];
        $employee = $this->employeeModel->find($empId);

        if ($employee) {
            echo json_encode([
                'success' => true,
                'full_name' => $employee['full_name'] ?? '',
                'employee_id' => $employee['employee_id'] ?? '',
                'department_id' => $employee['department_id'] ?? '',
                'designation_id' => $employee['designation_id'] ?? '',
                'designation_name' => $employee['designation_name'] ?? '',
                'department_name' => $employee['department_name'] ?? '',
                'joining_date' => $employee['joining_date'] ?? '',
                'resign_date' => $employee['resign_date'] ?? '',
                'gender' => $employee['gender'] ?? '',
                'email' => $employee['email'] ?? '',
            ]);
        } else {
            echo json_encode(['success' => false]);
        }
    }

    // ─── AJAX: Get Employee salary for salary certificate ─────────────────────
    public function getEmployeeSalary()
    {
        if (!isset($_GET['employee_id'])) {
            echo json_encode(['success' => false]);
            return;
        }

        $empId = (int) $_GET['employee_id'];
        $employee = $this->employeeModel->find($empId);

        if ($employee) {
            $gross = (float) ($employee['gross_salary'] ?? 0);
            $basic = (float) ($employee['basic_salary'] ?? 0);
            if ($basic <= 0 && $gross > 0) {
                $basic = round($gross * 0.50, 2);
            }

            $items = [];
            $hasPayslip = false;

            // Check if employee has payslip breakdown in payslips table
            $payslipStmt = $this->db->prepare("
                SELECT house_rent_allowance, medical_allowance, conveyance_allowance, recreation_allowance, duty_allowance
                FROM payslips 
                WHERE employee_id = ? 
                ORDER BY payslip_month DESC, id DESC 
                LIMIT 1
            ");
            if ($payslipStmt) {
                $payslipStmt->bind_param("i", $empId);
                $payslipStmt->execute();
                $payslip = $payslipStmt->get_result()->fetch_assoc();
                $payslipStmt->close();

                if ($payslip) {
                    $hasPayslip = true;
                    if ($basic > 0) {
                        $items[] = ['label' => 'Basic Salary', 'amount' => $basic];
                    }
                    if (!empty($payslip['house_rent_allowance']) && (float) $payslip['house_rent_allowance'] > 0) {
                        $items[] = ['label' => 'House Rent Allowance', 'amount' => (float) $payslip['house_rent_allowance']];
                    }
                    if (!empty($payslip['medical_allowance']) && (float) $payslip['medical_allowance'] > 0) {
                        $items[] = ['label' => 'Medical Allowance', 'amount' => (float) $payslip['medical_allowance']];
                    }
                    if (!empty($payslip['conveyance_allowance']) && (float) $payslip['conveyance_allowance'] > 0) {
                        $items[] = ['label' => 'Conveyance', 'amount' => (float) $payslip['conveyance_allowance']];
                    }
                    if (!empty($payslip['recreation_allowance']) && (float) $payslip['recreation_allowance'] > 0) {
                        $items[] = ['label' => 'Recreation Allowance', 'amount' => (float) $payslip['recreation_allowance']];
                    }
                    if (!empty($payslip['duty_allowance']) && (float) $payslip['duty_allowance'] > 0) {
                        $items[] = ['label' => 'Duty Allowance', 'amount' => (float) $payslip['duty_allowance']];
                    }
                }
            }

            // Fallback standard structure if no payslip found, but gross salary exists
            if (!$hasPayslip && $gross > 0) {
                $houseRent = round($gross * 0.25, 2);
                $medical = round($gross * 0.15, 2);
                $conveyance = round($gross * 0.05, 2);
                $recreation = round($gross - $basic - $houseRent - $medical - $conveyance, 2);
                if ($recreation < 0) $recreation = 0;

                $items[] = ['label' => 'Basic Salary', 'amount' => $basic];
                $items[] = ['label' => 'House Rent', 'amount' => $houseRent];
                $items[] = ['label' => 'Medical Allowance', 'amount' => $medical];
                $items[] = ['label' => 'Conveyance', 'amount' => $conveyance];
                if ($recreation > 0) {
                    $items[] = ['label' => 'Others Allowance', 'amount' => $recreation];
                }
            }

            $currentMonth = date('Y-m');
            $refNumber = 'TPHL/HR/Salary Certificate/' . date('F-Y');

            echo json_encode([
                'success' => true,
                'basic_salary' => $basic,
                'gross_salary' => $gross,
                'items' => $items,
                'letter_date' => date('Y-m-d'),
                'certificate_month' => $currentMonth,
                'ref_number' => $refNumber,
                'designation_id' => $employee['designation_id'],
                'designation_name' => $employee['designation_name'] ?? '',
                'department_id' => $employee['department_id'],
                'department_name' => $employee['department_name'] ?? '',
                'employee_code' => $employee['employee_id'] ?? '',
                'joining_date' => $employee['joining_date'] ?? '',
                'employee_type' => $employee['employee_type'] ?? 'permanent',
            ]);
        } else {
            echo json_encode(['success' => false]);
        }
    }

    // ─── AJAX: Get templates by letter type ───────────────────────────────────
    public function getTemplatesForType()
    {
        $type = $_GET['type'] ?? null;

        if (!$type) {
            echo json_encode(['success' => false]);
            return;
        }

        $templates = $this->templateModel->getByType($type);
        echo json_encode([
            'success' => true,
            'templates' => $templates,
        ]);
    }

    // ─── AJAX: Get single template body ───────────────────────────────────────
    public function getTemplate()
    {
        $id = $_GET['id'] ?? null;

        if (!$id) {
            echo json_encode(['success' => false]);
            return;
        }

        $template = $this->templateModel->find($id);
        if ($template) {
            echo json_encode(['success' => true, 'template' => $template]);
        } else {
            echo json_encode(['success' => false]);
        }
    }

    // ─── List all generated letters ───────────────────────────────────────────
    public function generateLetter()
    {
        $designation_id = !empty($_GET['designation_id']) ? (int) $_GET['designation_id'] : null;
        $letter_type = !empty($_GET['type']) ? trim($_GET['type']) : (!empty($_GET['letter_type']) ? trim($_GET['letter_type']) : null);
        $search = !empty($_GET['search']) ? trim($_GET['search']) : null;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = 15;

        $totalLetters = $this->letterModel->countAllWithDetails($designation_id, $letter_type, $search);
        $totalPages = max(1, (int) ceil($totalLetters / $limit));
        $letters = $this->letterModel->getAllWithDetails($designation_id, $letter_type, $search, $page, $limit);

        require_once __DIR__ . '/../models/Designation.php';
        $designationModel = new Designation($this->db);
        $designations = $designationModel->getAll();

        $this->view("hrm/letter-templates/generate-employee-letter", [
            "letters" => $letters,
            "designations" => $designations,
            "totalLetters" => $totalLetters,
            "totalPages" => $totalPages,
            "currentPage" => $page,
            "limit" => $limit,
            "search" => $search,
            "designation_id" => $designation_id,
            "letter_type" => $letter_type,
            "officialSignatures" => $this->hrSignatureModel->getAllActive(),
        ]);
    }

    // ─── Show generate form ───────────────────────────────────────────────────
    public function create()
    {
        $employees = $this->employeeModel->getAll();
        $templates = $this->templateModel->getAll();

        require_once __DIR__ . '/../models/Department.php';
        require_once __DIR__ . '/../models/Designation.php';
        $departmentModel = new Department($this->db);
        $designationModel = new Designation($this->db);

        $departments = $departmentModel->getAll();
        $designations = $designationModel->getAll();

        $this->view("hrm/letter-templates/create-letter-template", [
            "employees" => $employees,
            "templates" => $templates,
            "departments" => $departments,
            "designations" => $designations,
            "officialSignatures" => $this->hrSignatureModel->getAllActive(),
        ]);
    }

    // ─── Store new letter ─────────────────────────────────────────────────────
    public function store()
    {
        $data = $_POST;
        $employee = $this->employeeModel->find($data['employee_id']);

        // Template placeholder replace (only for non-salary_certificate)
        $template = null;
        if (!empty($data['template_id'])) {
            $template = $this->templateModel->find($data['template_id']);
        }

        // Prioritize user's edited body from editor, fallback to template body if empty
        $body = !empty(trim($data['letter_body'] ?? '')) ? $data['letter_body'] : ($template['body'] ?? '');
        if ($data['letter_type'] !== 'salary_certificate') {
            $body = $this->replacePlaceholders($body, $employee, $data);
        }

        // Build salary items from POST (salary_certificate only)
        $salaryItems = [];
        if ($data['letter_type'] === 'salary_certificate') {
            $labels = $_POST['item_label'] ?? [];
            $amounts = $_POST['item_amount'] ?? [];
            $itemTypes = $_POST['item_type_row'] ?? [];

            foreach ($labels as $i => $label) {
                if (trim($label) === '' || !isset($amounts[$i]))
                    continue;
                $salaryItems[] = [
                    'item_type' => $itemTypes[$i] ?? 'earning',
                    'label' => trim($label),
                    'amount' => (float) $amounts[$i],
                    'sort_order' => $i,
                ];
            }

            // Auto-calculate gross & net
            $gross = 0;
            $deduction = 0;
            foreach ($salaryItems as $item) {
                if ($item['item_type'] === 'earning')
                    $gross += $item['amount'];
                if ($item['item_type'] === 'deduction')
                    $deduction += $item['amount'];
            }
            $data['gross_salary'] = $gross;
            $data['net_salary'] = $gross - $deduction;
            $scOpening = trim($data['sc_opening_paragraph'] ?? '');
            $scClosing = trim($data['sc_closing_paragraph'] ?? '');
            $body = json_encode([
                'opening' => $scOpening,
                'closing' => $scClosing
            ], JSON_UNESCAPED_UNICODE);
        }

        $letterDate = !empty($data['letter_date']) ? $data['letter_date'] : (!empty($data['sc_letter_date']) ? $data['sc_letter_date'] : date('Y-m-d'));
        $refNumber = !empty($data['ref_number']) ? $data['ref_number'] : (!empty($data['sc_ref_number']) ? $data['sc_ref_number'] : null);

        // Process signature choice
        $customSignaturePath = null;
        $includeSignature = 1;
        $sigChoice = $data['signature_choice'] ?? ($data['include_signature'] ?? '1');

        if ($sigChoice === '0' || $sigChoice === 'none') {
            $includeSignature = 0;
            $customSignaturePath = null;
        } elseif (strpos($sigChoice, 'official_') === 0) {
            $includeSignature = 1;
            $sigId = (int) str_replace('official_', '', $sigChoice);
            $sigObj = $this->hrSignatureModel->find($sigId);
            if ($sigObj) {
                $customSignaturePath = $sigObj['signature_path'];
                if (empty($data['signatory_name'])) {
                    $data['signatory_name'] = $sigObj['signatory_name'];
                }
                if (empty($data['signatory_title'])) {
                    $data['signatory_title'] = $sigObj['signatory_title'];
                }
            }
        } elseif ($sigChoice === '1' || $sigChoice === 'official') {
            $includeSignature = 1;
            $defaultSig = $this->hrSignatureModel->getDefault();
            if ($defaultSig) {
                $customSignaturePath = $defaultSig['signature_path'];
                if (empty($data['signatory_name'])) {
                    $data['signatory_name'] = $defaultSig['signatory_name'];
                }
                if (empty($data['signatory_title'])) {
                    $data['signatory_title'] = $defaultSig['signatory_title'];
                }
            }
        } elseif ($sigChoice === 'custom') {
            $includeSignature = 1;
            if (!empty($_FILES['custom_signature_file']['tmp_name']) && is_uploaded_file($_FILES['custom_signature_file']['tmp_name'])) {
                $baseDir = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
                $sigDir = $baseDir . '/public/assets/images/hrm/signatures';
                if (!is_dir($sigDir)) {
                    mkdir($sigDir, 0777, true);
                }
                $filename = 'sig_' . time() . '_' . mt_rand(1000, 9999) . '.png';
                $destPath = $sigDir . '/' . $filename;
                $threshold = isset($data['sig_threshold']) ? (int)$data['sig_threshold'] : 205;
                $extractResult = SignatureExtractor::extractAndSave($_FILES['custom_signature_file']['tmp_name'], $destPath, $threshold);
                if ($extractResult['success']) {
                    $customSignaturePath = 'public/assets/images/hrm/signatures/' . $filename;
                }
            }
        }

        $saveData = [
            "employee_id" => $data['employee_id'],
            "designation_id" => !empty($data['designation_id']) ? (int) $data['designation_id'] : ($employee['designation_id'] ?? null),
            "department_id" => !empty($data['department_id']) ? (int) $data['department_id'] : ($employee['department_id'] ?? null),
            "letter_type" => $data['letter_type'],
            "template_id" => (!empty($data['template_id'])) ? $data['template_id'] : null,
            "subject" => !empty($data['subject']) ? $data['subject'] : ($data['sc_subject'] ?? 'Salary Certificate'),
            "body" => $body,
            "letter_date" => $letterDate,
            "ref_number" => $refNumber,
            "joining_date" => (!empty($data['joining_date'])) ? $data['joining_date'] : (!empty($data['sc_joining_date']) ? $data['sc_joining_date'] : null),
            "effective_date" => (!empty($data['effective_date'])) ? $data['effective_date'] : (!empty($data['sc_effective_date']) ? $data['sc_effective_date'] : null),
            "current_salary" => (!empty($data['current_salary'])) ? $data['current_salary'] : null,
            "revised_salary" => (!empty($data['revised_salary'])) ? $data['revised_salary'] : null,
            "new_designation" => (!empty($data['new_designation'])) ? $data['new_designation'] : null,
            "signatory_name" => $data['signatory_name'] ?? '',
            "signatory_title" => $data['signatory_title'] ?? '',
            "include_signature" => $includeSignature,
            "custom_signature" => $customSignaturePath,
            "cc_email" => (!empty($data['cc_email'])) ? $data['cc_email'] : null,
            "send_to_email" => (!empty($data['send_to_email'])) ? (int) $data['send_to_email'] : 0,
            "status" => $data['letter_status'] ?? 'draft',
            "purpose" => $data['purpose'] ?? null,
            "certificate_month" => $data['certificate_month'] ?? null,
            "gross_salary" => $data['gross_salary'] ?? null,
            "net_salary" => $data['net_salary'] ?? null,
        ];

        $letterId = $this->letterModel->create($saveData);

        if (!$letterId) {
            die("Database Error (Create Letter): " . $this->db->error);
        }

        // Save salary items
        if (!empty($salaryItems)) {
            $this->letterModel->createSalaryItems((int) $letterId, $salaryItems);
        }

        // Send to employee email if enabled
        if (!empty($data['send_to_email'])) {
            $this->sendLetterEmail((int) $letterId);
        }

        header("Location: " . $GLOBALS['base_url'] . "/generate-letter");
    }

    // ─── Placeholder replace ──────────────────────────────────────────────────
    private function replacePlaceholders($body, $employee, $data)
    {
        if (empty($body)) {
            return '';
        }

        $companyName = function_exists('getSetting') ? getSetting('website_title', 'The Premium Homes Limited') : 'The Premium Homes Limited';
        if (stripos($companyName, 'Homes') === false && stripos($companyName, 'Home') !== false) {
            $companyName = str_ireplace('Home', 'Homes', $companyName);
        }

        $designationName = '';
        if (!empty($data['designation_id'])) {
            $dStmt = $this->db->prepare("SELECT name FROM designations WHERE id = ?");
            if ($dStmt) {
                $dStmt->bind_param("i", $data['designation_id']);
                $dStmt->execute();
                $dRow = $dStmt->get_result()->fetch_assoc();
                $designationName = $dRow['name'] ?? '';
                $dStmt->close();
            }
        }
        if (empty($designationName)) {
            $designationName = $employee['designation_name'] ?? '';
        }

        $departmentName = '';
        if (!empty($data['department_id'])) {
            $dpStmt = $this->db->prepare("SELECT name FROM departments WHERE id = ?");
            if ($dpStmt) {
                $dpStmt->bind_param("i", $data['department_id']);
                $dpStmt->execute();
                $dpRow = $dpStmt->get_result()->fetch_assoc();
                $departmentName = $dpRow['name'] ?? '';
                $dpStmt->close();
            }
        }
        if (empty($departmentName)) {
            $departmentName = $employee['department_name'] ?? '';
        }

        $joiningDate = !empty($data['joining_date']) ? date('F d, Y', strtotime($data['joining_date'])) : (!empty($employee['joining_date']) ? date('F d, Y', strtotime($employee['joining_date'])) : '');
        $letterDate = !empty($data['letter_date']) ? date('F d, Y', strtotime($data['letter_date'])) : date('F d, Y');
        $effectiveDate = !empty($data['effective_date']) ? date('F d, Y', strtotime($data['effective_date'])) : (!empty($employee['resign_date']) ? date('F d, Y', strtotime($employee['resign_date'])) : date('F d, Y'));

        $currentSalary = !empty($data['current_salary']) ? number_format((float) $data['current_salary'], 2) : (!empty($employee['gross_salary']) ? number_format((float) $employee['gross_salary'], 2) : '');
        $revisedSalary = !empty($data['revised_salary']) ? number_format((float) $data['revised_salary'], 2) : '';

        // Gender pronoun detection
        $isFemale = false;
        $nameLower = strtolower($employee['full_name'] ?? '');
        $femaleKeywords = ['ms.', 'mrs.', 'miss', 'khatun', 'begum', 'akter', 'akther', 'sultana', 'jahan', 'nahar', 'parvin', 'nasrin', 'farzana', 'roksana', 'shammi', 'bornali'];
        foreach ($femaleKeywords as $kw) {
            if (strpos($nameLower, $kw) !== false) {
                $isFemale = true;
                break;
            }
        }
        $her_his = $isFemale ? 'her' : 'his';
        $she_he = $isFemale ? 'she' : 'he';
        $her_him = $isFemale ? 'her' : 'him';
        $Her_His = $isFemale ? 'Her' : 'His';
        $She_He = $isFemale ? 'She' : 'He';

        $replace = [
            "{{company_name}}" => $companyName,
            "{{employee_name}}" => $employee['full_name'] ?? '',
            "{{employee_code}}" => $employee['employee_id'] ?? '',
            "{{designation}}" => $designationName,
            "{{department}}" => $departmentName,
            "{{address}}" => $employee['address'] ?? '',
            "{{joining_date}}" => $joiningDate,
            "{{effective_date}}" => $effectiveDate,
            "{{release_date}}" => $effectiveDate,
            "{{separation_date}}" => $effectiveDate,
            "{{current_salary}}" => $currentSalary,
            "{{revised_salary}}" => $revisedSalary,
            "{{new_designation}}" => $data['new_designation'] ?? '',
            "{{letter_date}}" => $letterDate,
            "{{ref_number}}" => $data['ref_number'] ?? '',
            "{{her_his}}" => $her_his,
            "{{she_he}}" => $she_he,
            "{{her_him}}" => $her_him,
            "{{Her_His}}" => $Her_His,
            "{{She_He}}" => $She_He,
        ];

        return str_replace(array_keys($replace), array_values($replace), $body);
    }

    // ─── List letter templates ────────────────────────────────────────────────
    public function template()
    {
        $designation_id = $_GET['designation_id'] ?? null;
        $type = $_GET['type'] ?? null;

        $templates = $this->templateModel->getAll($designation_id, $type);

        $designationModel = new Designation($this->db);
        $designations = $designationModel->getAll();

        $this->view("hrm/letter-templates/list-letter-templates", [
            "templates" => $templates,
            "designations" => $designations,
        ]);
    }

    public function storeLetterTemplate()
    {
        $this->templateModel->create($_POST);
        header("Location: " . $GLOBALS['base_url'] . "/letter-templates");
    }

    public function edit($id)
    {
        $template = $this->templateModel->find($id);

        require_once __DIR__ . '/../models/Designation.php';
        $designationModel = new Designation($this->db);
        $designations = $designationModel->getAll();

        $this->view("hrm/letter-templates/edit-letter-template", [
            "template" => $template,
            "designations" => $designations,
        ]);
    }

    public function update($id)
    {
        $this->templateModel->update($id, $_POST);
        header("Location: " . $GLOBALS['base_url'] . "/letter-templates");
    }

    public function delete($id)
    {
        $this->templateModel->delete($id);
        header("Location: " . $GLOBALS['base_url'] . "/letter-templates");
    }

    // ─── View a letter ────────────────────────────────────────────────────────
    public function viewLetter($id)
    {
        $letter = $this->letterModel->find($id);

        if ($letter['letter_type'] === 'salary_certificate') {
            $salaryItems = $this->letterModel->getSalaryItems((int) $id);
            $this->view("hrm/letter-templates/salary-certificate-view", [
                "letter" => $letter,
                "salaryItems" => $salaryItems,
            ]);
        } else {
            $this->view("hrm/letter-templates/view-letter", ["letter" => $letter]);
        }
    }

    // ─── Print a letter ───────────────────────────────────────────────────────
    public function printLetter($id)
    {
        $letter = $this->letterModel->find($id);

        if ($letter['letter_type'] === 'salary_certificate') {
            $salaryItems = $this->letterModel->getSalaryItems((int) $id);
            $this->view("hrm/letter-templates/salary-certificate-print", [
                "letter" => $letter,
                "salaryItems" => $salaryItems,
            ], false);
        } else {
            $this->view("hrm/letter-templates/print-letter", ["letter" => $letter], false);
        }
    }

    // ─── Delete a letter ──────────────────────────────────────────────────────
    public function deleteLetter($id)
    {
        $this->letterModel->delete($id);
        header("Location: " . $GLOBALS['base_url'] . "/generate-letter");
    }

    // ─── Render Letter to Dompdf Instance ─────────────────────────────────────
    public function renderLetterPDF($id)
    {
        ini_set('memory_limit', '512M');

        $letter = $this->letterModel->find($id);
        if (!$letter) {
            return null;
        }

        // Base64 encode the letterhead background image for 100% reliable Dompdf rendering
        $bgPath = realpath(__DIR__ . '/../../public/assets/email-template/new-letter-template.jpeg');
        $bgBase64 = ($bgPath && file_exists($bgPath)) ? ('data:image/jpeg;base64,' . base64_encode(file_get_contents($bgPath))) : '';

        // Base64 encode signature (custom letter signature or default official HR signature)
        $sigPath = null;
        if (!empty($letter['custom_signature'])) {
            $baseDir = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
            $candidatePath = $baseDir . '/' . ltrim($letter['custom_signature'], '/\\');
            if (file_exists($candidatePath)) {
                $sigPath = realpath($candidatePath);
            }
        }
        if (!$sigPath) {
            $defaultSig = $this->hrSignatureModel->getDefault();
            if ($defaultSig && !empty($defaultSig['signature_path'])) {
                $baseDir = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
                $candidate = $baseDir . '/' . ltrim($defaultSig['signature_path'], '/\\');
                if (file_exists($candidate)) {
                    $sigPath = realpath($candidate);
                }
            }
            if (!$sigPath) {
                $sigPath = realpath(__DIR__ . '/../../public/assets/images/hrm/hr_signature.png');
            }
        }
        $sigBase64 = ($sigPath && file_exists($sigPath)) ? ('data:image/png;base64,' . base64_encode(file_get_contents($sigPath))) : '';

        // Render HTML
        ob_start();
        extract(['letter' => $letter, 'bgBase64' => $bgBase64, 'sigBase64' => $sigBase64]);

        if ($letter['letter_type'] === 'salary_certificate') {
            $salaryItems = $this->letterModel->getSalaryItems((int) $id);
            extract(['salaryItems' => $salaryItems]);
            include __DIR__ . '/../views/hrm/letter-templates/salary-certificate-pdf.php';
        } else {
            include __DIR__ . '/../views/hrm/letter-templates/pdf-letter.php';
        }

        $html = ob_get_clean();

        // Modern Dompdf Engine for 100% pixel-perfect HTML/CSS rendering identical to View
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        $options->set('dpi', 96);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $code = !empty($letter['employee_code']) ? $letter['employee_code'] : $letter['id'];
        $filename = str_replace([' ', '/'], '_', $letter['letter_type']) . '_' . $code . '.pdf';

        return [
            'letter' => $letter,
            'dompdf' => $dompdf,
            'pdfContent' => $dompdf->output(),
            'filename' => $filename,
        ];
    }

    // ─── Generate PDF (Dompdf Engine - Stream to Browser) ─────────────────────
    public function generatePDFLetter($id)
    {
        $rendered = $this->renderLetterPDF($id);
        if (!$rendered) {
            die("Letter not found");
        }

        if (ob_get_length()) {
            ob_end_clean();
        }

        // Prevent browser caching so users always see the latest updated PDF
        if (!headers_sent()) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
        }

        $rendered['dompdf']->stream($rendered['filename'], ['Attachment' => 0]);
        exit;
    }

    // ─── Send Generated Letter to Employee via Email with PDF Attachment ─────
    private function sendLetterEmail($letterId)
    {
        $rendered = $this->renderLetterPDF($letterId);
        if (!$rendered) {
            return false;
        }

        $letter = $rendered['letter'];
        $recipientEmail = trim($letter['employee_email'] ?? '');
        if (empty($recipientEmail)) {
            $emp = $this->employeeModel->find($letter['employee_id']);
            $recipientEmail = trim($emp['email'] ?? '');
        }

        if (empty($recipientEmail)) {
            error_log("Cannot dispatch letter email: Employee #{$letter['employee_id']} has no email registered.");
            return false;
        }

        $config = require __DIR__ . '/../config/config.php';
        $companyTitle = function_exists('getSetting') ? getSetting('website_title', 'The Premium Homes Limited') : 'The Premium Homes Limited';
        $employeeName = $letter['full_name'] ?? 'Employee';
        $letterSubject = !empty($letter['subject']) ? $letter['subject'] : ucwords(str_replace('_', ' ', $letter['letter_type']));

        require_once __DIR__ . '/../../vendor/autoload.php';

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->SMTPDebug = 0;
            $mail->Host       = $config['smtp_host'] ?? 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $config['smtp_user'] ?? 'farzana.tphl13@gmail.com';
            $mail->Password   = $config['smtp_pass'] ?? 'hvptjfnsprecofmi';
            $mail->SMTPSecure = $config['smtp_secure'] ?? 'ssl';
            $mail->Port       = (int) ($config['smtp_port'] ?? 465);

            $mail->setFrom($mail->Username, $companyTitle . ' HR');
            $mail->addAddress($recipientEmail, $employeeName);

            if (!empty($letter['cc_email'])) {
                $ccList = array_map('trim', explode(',', $letter['cc_email']));
                foreach ($ccList as $cc) {
                    if (filter_var($cc, FILTER_VALIDATE_EMAIL)) {
                        $mail->addCC($cc);
                    }
                }
            }

            $mail->isHTML(true);
            $mail->Subject = $letterSubject . ' - ' . $companyTitle;
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; font-size: 14px; color: #333; line-height: 1.6;'>
                    <p>Dear <strong>" . htmlspecialchars($employeeName) . "</strong>,</p>
                    <p>Please find attached your official <strong>" . htmlspecialchars($letterSubject) . "</strong> issued by <strong>" . htmlspecialchars($companyTitle) . "</strong>.</p>
                    <p>If you have any questions regarding this letter, please feel free to reach out to the Human Resources Department.</p>
                    <br>
                    <p>Warm regards,<br>
                    <strong>Human Resources Department</strong><br>
                    " . htmlspecialchars($companyTitle) . "</p>
                </div>
            ";
            $mail->AltBody = "Dear {$employeeName},\n\nPlease find attached your official {$letterSubject} issued by {$companyTitle}.\n\nWarm regards,\nHuman Resources Department\n{$companyTitle}";

            // Attach PDF in memory
            $mail->addStringAttachment($rendered['pdfContent'], $rendered['filename'], 'base64', 'application/pdf');

            $mail->send();
            return true;
        } catch (\Exception $e) {
            error_log("Failed to send letter email: " . $e->getMessage());
            return false;
        }
    }

    // ─── Edit letter form ─────────────────────────────────────────────────────
    public function editLetter($id)
    {
        $letter = $this->letterModel->find($id);
        if (!$letter) {
            die("Letter not found");
        }

        $employees = $this->employeeModel->getAll();
        $templates = $this->templateModel->getAll();

        require_once __DIR__ . '/../models/Department.php';
        require_once __DIR__ . '/../models/Designation.php';
        $departmentModel = new Department($this->db);
        $designationModel = new Designation($this->db);

        $departments = $departmentModel->getAll();
        $designations = $designationModel->getAll();

        if ($letter['letter_type'] === 'salary_certificate') {
            $salaryItems = $this->letterModel->getSalaryItems((int) $id);
            $this->view("hrm/letter-templates/edit-generated-letter", [
                "letter" => $letter,
                "employees" => $employees,
                "templates" => $templates,
                "departments" => $departments,
                "designations" => $designations,
                "salaryItems" => $salaryItems,
                "officialSignatures" => $this->hrSignatureModel->getAllActive(),
            ]);
        } else {
            $this->view("hrm/letter-templates/edit-generated-letter", [
                "letter" => $letter,
                "employees" => $employees,
                "templates" => $templates,
                "departments" => $departments,
                "designations" => $designations,
                "salaryItems" => [],
                "officialSignatures" => $this->hrSignatureModel->getAllActive(),
            ]);
        }
    }

    // ─── Update letter ────────────────────────────────────────────────────────
    public function updateLetter($id)
    {
        $data = $_POST;
        $employee = $this->employeeModel->find($data['employee_id']);

        $body = $data['letter_body'] ?? '';
        if ($data['letter_type'] !== 'salary_certificate') {
            $body = $this->replacePlaceholders($body, $employee, $data);
        }

        $salaryItems = [];
        if ($data['letter_type'] === 'salary_certificate') {
            $labels = $_POST['item_label'] ?? [];
            $amounts = $_POST['item_amount'] ?? [];
            $itemTypes = $_POST['item_type_row'] ?? [];

            foreach ($labels as $i => $label) {
                if (trim($label) === '' || !isset($amounts[$i]))
                    continue;
                $salaryItems[] = [
                    'item_type' => $itemTypes[$i] ?? 'earning',
                    'label' => trim($label),
                    'amount' => (float) $amounts[$i],
                    'sort_order' => $i,
                ];
            }

            $gross = 0;
            $deduction = 0;
            foreach ($salaryItems as $item) {
                if ($item['item_type'] === 'earning')
                    $gross += $item['amount'];
                if ($item['item_type'] === 'deduction')
                    $deduction += $item['amount'];
            }
            $data['gross_salary'] = $gross;
            $data['net_salary'] = $gross - $deduction;
            $scOpening = trim($data['sc_opening_paragraph'] ?? '');
            $scClosing = trim($data['sc_closing_paragraph'] ?? '');
            $body = json_encode([
                'opening' => $scOpening,
                'closing' => $scClosing
            ], JSON_UNESCAPED_UNICODE);
        }

        $letterDate = !empty($data['letter_date']) ? $data['letter_date'] : (!empty($data['sc_letter_date']) ? $data['sc_letter_date'] : date('Y-m-d'));
        $refNumber = !empty($data['ref_number']) ? $data['ref_number'] : (!empty($data['sc_ref_number']) ? $data['sc_ref_number'] : null);
        $subject = !empty($data['subject']) ? $data['subject'] : ($data['sc_subject'] ?? 'Salary Certificate');

        $currentLetter = $this->letterModel->find($id);
        $customSignaturePath = $currentLetter['custom_signature'] ?? null;
        $sigChoice = $data['signature_choice'] ?? ($data['include_signature'] ?? '1');

        $includeSignature = 1;
        if ($sigChoice === '0' || $sigChoice === 'none') {
            $includeSignature = 0;
            $customSignaturePath = null;
        } elseif (strpos($sigChoice, 'official_') === 0) {
            $includeSignature = 1;
            $sigId = (int) str_replace('official_', '', $sigChoice);
            $sigObj = $this->hrSignatureModel->find($sigId);
            if ($sigObj) {
                $customSignaturePath = $sigObj['signature_path'];
                if (empty($data['signatory_name'])) {
                    $data['signatory_name'] = $sigObj['signatory_name'];
                }
                if (empty($data['signatory_title'])) {
                    $data['signatory_title'] = $sigObj['signatory_title'];
                }
            }
        } elseif ($sigChoice === '1' || $sigChoice === 'official') {
            $includeSignature = 1;
            $defaultSig = $this->hrSignatureModel->getDefault();
            if ($defaultSig) {
                $customSignaturePath = $defaultSig['signature_path'];
                if (empty($data['signatory_name'])) {
                    $data['signatory_name'] = $defaultSig['signatory_name'];
                }
                if (empty($data['signatory_title'])) {
                    $data['signatory_title'] = $defaultSig['signatory_title'];
                }
            }
        } elseif ($sigChoice === 'keep_current') {
            // Keep existing signature path as is
            $includeSignature = 1;
        } elseif ($sigChoice === 'custom') {
            $includeSignature = 1;
            if (!empty($_FILES['custom_signature_file']['tmp_name']) && is_uploaded_file($_FILES['custom_signature_file']['tmp_name'])) {
                $baseDir = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
                $sigDir = $baseDir . '/public/assets/images/hrm/signatures';
                if (!is_dir($sigDir)) {
                    mkdir($sigDir, 0777, true);
                }
                $filename = 'sig_' . time() . '_' . mt_rand(1000, 9999) . '.png';
                $destPath = $sigDir . '/' . $filename;
                $threshold = isset($data['sig_threshold']) ? (int)$data['sig_threshold'] : 205;
                $extractResult = SignatureExtractor::extractAndSave($_FILES['custom_signature_file']['tmp_name'], $destPath, $threshold);
                if ($extractResult['success']) {
                    if (!empty($currentLetter['custom_signature'])) {
                        $oldFile = $baseDir . '/' . ltrim($currentLetter['custom_signature'], '/\\');
                        if (file_exists($oldFile) && strpos($oldFile, 'signatures/sig_custom_') !== false) {
                            @unlink($oldFile);
                        }
                    }
                    $customSignaturePath = 'public/assets/images/hrm/signatures/' . $filename;
                }
            }
        }

        $updateData = [
            "employee_id" => $data['employee_id'],
            "designation_id" => $data['designation_id'] ?? null,
            "department_id" => $data['department_id'] ?? null,
            "letter_type" => $data['letter_type'],
            "template_id" => (!empty($data['template_id'])) ? $data['template_id'] : null,
            "subject" => $subject,
            "body" => $body,
            "letter_date" => $letterDate,
            "ref_number" => $refNumber,
            "joining_date" => (!empty($data['joining_date'])) ? $data['joining_date'] : (!empty($data['sc_joining_date']) ? $data['sc_joining_date'] : null),
            "effective_date" => (!empty($data['effective_date'])) ? $data['effective_date'] : (!empty($data['sc_effective_date']) ? $data['sc_effective_date'] : null),
            "current_salary" => (!empty($data['current_salary'])) ? $data['current_salary'] : null,
            "revised_salary" => (!empty($data['revised_salary'])) ? $data['revised_salary'] : null,
            "new_designation" => (!empty($data['new_designation'])) ? $data['new_designation'] : null,
            "signatory_name" => $data['signatory_name'] ?? '',
            "signatory_title" => $data['signatory_title'] ?? '',
            "include_signature" => $includeSignature,
            "custom_signature" => $customSignaturePath,
            "cc_email" => (!empty($data['cc_email'])) ? $data['cc_email'] : null,
            "send_to_email" => (!empty($data['send_to_email'])) ? (int) $data['send_to_email'] : 0,
            "status" => $data['letter_status'] ?? 'draft',
            "purpose" => $data['purpose'] ?? null,
            "certificate_month" => $data['certificate_month'] ?? null,
            "gross_salary" => $data['gross_salary'] ?? null,
            "net_salary" => $data['net_salary'] ?? null,
        ];

        if (!$this->letterModel->update($id, $updateData)) {
            die("Database Error (Update Letter): " . $this->db->error);
        }

        // Replace salary items
        if ($data['letter_type'] === 'salary_certificate') {
            $this->letterModel->deleteSalaryItems((int) $id);
            if (!empty($salaryItems)) {
                $this->letterModel->createSalaryItems((int) $id, $salaryItems);
            }
        }

        // Send to employee email if enabled
        if (!empty($data['send_to_email'])) {
            $this->sendLetterEmail((int) $id);
        }

        header("Location: " . $GLOBALS['base_url'] . "/generate-letter");
    }

    // ─── Multi-Signature Management: Save New Official Signature ──────────────
    public function saveOfficialSignature()
    {
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_POST['is_ajax']);

        $name = trim($_POST['signatory_name'] ?? '');
        $title = trim($_POST['signatory_title'] ?? '');
        $isDefault = !empty($_POST['is_default']) ? 1 : 0;

        if (empty($name) || empty($title)) {
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => 'Please provide signatory name and designation/title.']);
                return;
            }
            $_SESSION['error'] = 'Please provide signatory name and designation/title.';
            header("Location: " . $GLOBALS['base_url'] . "/generate-letter");
            return;
        }

        if (empty($_FILES['official_signature']['tmp_name']) || !is_uploaded_file($_FILES['official_signature']['tmp_name'])) {
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => 'No signature file was selected.']);
                return;
            }
            $_SESSION['error'] = 'No signature file was selected.';
            header("Location: " . $GLOBALS['base_url'] . "/generate-letter");
            return;
        }

        $baseDir = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
        $targetDir = $baseDir . '/public/assets/images/hrm/signatures';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($name));
        $filename = 'sig_official_' . $safeName . '_' . time() . '.png';
        $targetFile = $targetDir . '/' . $filename;
        $relPath = 'public/assets/images/hrm/signatures/' . $filename;

        $threshold = isset($_POST['sig_threshold']) ? (int)$_POST['sig_threshold'] : 205;
        $result = SignatureExtractor::extractAndSave(
            $_FILES['official_signature']['tmp_name'],
            $targetFile,
            $threshold
        );

        if ($result['success']) {
            $createdId = $this->hrSignatureModel->create([
                'signatory_name' => $name,
                'signatory_title' => $title,
                'signature_path' => $relPath,
                'is_default' => $isDefault
            ]);

            if ($createdId) {
                if ($isAjax) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Official HR signature added successfully!',
                        'id' => $createdId,
                        'signatures' => $this->hrSignatureModel->getAllActive(),
                        'base_url' => $GLOBALS['base_url']
                    ]);
                    return;
                }
                $_SESSION['success'] = 'Official HR signature added successfully!';
            } else {
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => 'Failed to save signature in database.']);
                    return;
                }
                $_SESSION['error'] = 'Failed to save signature in database.';
            }
        } else {
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $result['message']]);
                return;
            }
            $_SESSION['error'] = 'Failed to extract signature: ' . $result['message'];
        }

        header("Location: " . $GLOBALS['base_url'] . "/generate-letter");
    }

    // ─── Multi-Signature Management: Delete Signature ─────────────────────────
    public function deleteOfficialSignature()
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid signature ID.']);
            return;
        }

        $deleted = $this->hrSignatureModel->delete($id);
        if ($deleted) {
            echo json_encode([
                'success' => true,
                'message' => 'Official signature deleted successfully.',
                'signatures' => $this->hrSignatureModel->getAllActive(),
                'base_url' => $GLOBALS['base_url']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete signature.']);
        }
    }

    // ─── Multi-Signature Management: Set Default Signature ────────────────────
    public function setDefaultOfficialSignature()
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid signature ID.']);
            return;
        }

        $ok = $this->hrSignatureModel->setDefault($id);
        if ($ok) {
            echo json_encode([
                'success' => true,
                'message' => 'Default signatory updated successfully.',
                'signatures' => $this->hrSignatureModel->getAllActive(),
                'base_url' => $GLOBALS['base_url']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update default signature.']);
        }
    }

    // ─── Multi-Signature Management: Get All Active Signatures (AJAX) ─────────
    public function getOfficialSignaturesAjax()
    {
        $signatures = $this->hrSignatureModel->getAllActive();
        echo json_encode([
            'success' => true,
            'signatures' => $signatures,
            'base_url' => $GLOBALS['base_url']
        ]);
    }

    // ─── Backwards Compatibility: Update Legacy hr_signature.png ─────────────
    public function updateOfficialSignature()
    {
        if (!empty($_POST['signatory_name'])) {
            return $this->saveOfficialSignature();
        }

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_POST['is_ajax']);

        if (empty($_FILES['official_signature']['tmp_name']) || !is_uploaded_file($_FILES['official_signature']['tmp_name'])) {
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => 'No signature file was selected.']);
                return;
            }
            $_SESSION['error'] = 'No signature file was selected.';
            header("Location: " . $GLOBALS['base_url'] . "/generate-letter");
            return;
        }

        $baseDir = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
        $targetDir = $baseDir . '/public/assets/images/hrm/signatures';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $filename = 'sig_official_default_' . time() . '.png';
        $targetFile = $targetDir . '/' . $filename;
        $relPath = 'public/assets/images/hrm/signatures/' . $filename;

        $threshold = isset($_POST['sig_threshold']) ? (int)$_POST['sig_threshold'] : 205;
        $result = SignatureExtractor::extractAndSave(
            $_FILES['official_signature']['tmp_name'],
            $targetFile,
            $threshold
        );

        if ($result['success']) {
            $createdId = $this->hrSignatureModel->create([
                'signatory_name' => 'HR Department',
                'signatory_title' => 'Authorized Official',
                'signature_path' => $relPath,
                'is_default' => 1
            ]);

            if ($isAjax) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Official HR signature updated successfully!',
                    'url' => $GLOBALS['base_url'] . '/' . $relPath . '?v=' . time(),
                    'signatures' => $this->hrSignatureModel->getAllActive(),
                    'base_url' => $GLOBALS['base_url']
                ]);
                return;
            }
            $_SESSION['success'] = 'Official HR signature updated successfully!';
        } else {
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $result['message']]);
                return;
            }
            $_SESSION['error'] = 'Failed to extract signature: ' . $result['message'];
        }

        header("Location: " . $GLOBALS['base_url'] . "/generate-letter");
    }
}
