<?php

class LetterTemplateController extends Controller
{
    protected $db;
    protected $employeeModel;
    protected $templateModel;
    protected $letterModel;

    public function __construct($db)
    {
        $this->db            = $db;
        $this->employeeModel = new Employee($this->db);
        $this->templateModel = new LetterTemplate($this->db);
        $this->letterModel   = new Letter($this->db);
    }

    // ─── AJAX: Get Employee basic info ────────────────────────────────────────
    public function getEmployeeInfo()
    {
        if (!isset($_GET['employee_id'])) {
            echo json_encode(['success' => false]);
            return;
        }

        $empId    = (int)$_GET['employee_id'];
        $employee = $this->employeeModel->find($empId);

        if ($employee) {
            echo json_encode([
                'success'        => true,
                'department_id'  => $employee['department_id'],
                'designation_id' => $employee['designation_id'],
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

        $empId    = (int)$_GET['employee_id'];
        $employee = $this->employeeModel->find($empId);

        if ($employee) {
            echo json_encode([
                'success'         => true,
                'basic_salary'    => $employee['basic_salary']    ?? 0,
                'gross_salary'    => $employee['gross_salary']    ?? 0,
                'designation_id'  => $employee['designation_id'],
                'designation_name'=> $employee['designation_name'] ?? '',
                'department_id'   => $employee['department_id'],
                'department_name' => $employee['department_name']  ?? '',
                'employee_code'   => $employee['employee_id']      ?? '',
                'joining_date'    => $employee['joining_date']     ?? '',
                'employee_type'   => $employee['employee_type']    ?? 'permanent',
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
            'success'   => true,
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
        $designation_id = $_GET['designation_id'] ?? null;
        $letter_type    = $_GET['type']           ?? null;

        $letters = $this->letterModel->getAllWithDetails($designation_id, $letter_type);

        require_once __DIR__ . '/../models/Designation.php';
        $designationModel = new Designation($this->db);
        $designations     = $designationModel->getAll();

        $this->view("hrm/letter-templates/generate-employee-letter", [
            "letters"      => $letters,
            "designations" => $designations,
        ]);
    }

    // ─── Show generate form ───────────────────────────────────────────────────
    public function create()
    {
        $employees = $this->employeeModel->getAll();
        $templates = $this->templateModel->getAll();

        require_once __DIR__ . '/../models/Department.php';
        require_once __DIR__ . '/../models/Designation.php';
        $departmentModel  = new Department($this->db);
        $designationModel = new Designation($this->db);

        $departments  = $departmentModel->getAll();
        $designations = $designationModel->getAll();

        $this->view("hrm/letter-templates/create-letter-template", [
            "employees"    => $employees,
            "templates"    => $templates,
            "departments"  => $departments,
            "designations" => $designations,
        ]);
    }

    // ─── Store new letter ─────────────────────────────────────────────────────
    public function store()
    {
        $data     = $_POST;
        $employee = $this->employeeModel->find($data['employee_id']);

        // Template placeholder replace (only for non-salary_certificate)
        $template = null;
        if (!empty($data['template_id'])) {
            $template = $this->templateModel->find($data['template_id']);
        }

        $body = $data['letter_body'] ?? '';
        if ($template && $data['letter_type'] !== 'salary_certificate') {
            $body = $this->replacePlaceholders($template['body'], $employee, $data);
        }

        // Build salary items from POST (salary_certificate only)
        $salaryItems = [];
        if ($data['letter_type'] === 'salary_certificate') {
            $labels    = $_POST['item_label']    ?? [];
            $amounts   = $_POST['item_amount']   ?? [];
            $itemTypes = $_POST['item_type_row'] ?? [];

            foreach ($labels as $i => $label) {
                if (trim($label) === '' || !isset($amounts[$i])) continue;
                $salaryItems[] = [
                    'item_type'  => $itemTypes[$i] ?? 'earning',
                    'label'      => trim($label),
                    'amount'     => (float)$amounts[$i],
                    'sort_order' => $i,
                ];
            }

            // Auto-calculate gross & net
            $gross = 0; $deduction = 0;
            foreach ($salaryItems as $item) {
                if ($item['item_type'] === 'earning')   $gross     += $item['amount'];
                if ($item['item_type'] === 'deduction') $deduction += $item['amount'];
            }
            $data['gross_salary'] = $gross;
            $data['net_salary']   = $gross - $deduction;
            $body = ''; // No free-text body for salary certificate
        }

        $letterDate = !empty($data['letter_date']) ? $data['letter_date'] : (!empty($data['sc_letter_date']) ? $data['sc_letter_date'] : date('Y-m-d'));
        $refNumber  = !empty($data['ref_number']) ? $data['ref_number'] : (!empty($data['sc_ref_number']) ? $data['sc_ref_number'] : null);

        $saveData = [
            "employee_id"      => $data['employee_id'],
            "designation_id"   => $employee['designation_id'] ?? null,
            "department_id"    => $employee['department_id']  ?? null,
            "letter_type"      => $data['letter_type'],
            "template_id"      => (!empty($data['template_id'])) ? $data['template_id'] : null,
            "subject"          => !empty($data['subject']) ? $data['subject'] : ($data['sc_subject'] ?? 'Salary Certificate'),
            "body"             => $body,
            "letter_date"      => $letterDate,
            "ref_number"       => $refNumber,
            "joining_date"     => (!empty($data['joining_date']))   ? $data['joining_date']   : null,
            "effective_date"   => (!empty($data['effective_date']))   ? $data['effective_date']   : null,
            "current_salary"   => (!empty($data['current_salary']))   ? $data['current_salary']   : null,
            "revised_salary"   => (!empty($data['revised_salary']))   ? $data['revised_salary']   : null,
            "new_designation"  => (!empty($data['new_designation']))  ? $data['new_designation']  : null,
            "signatory_name"   => $data['signatory_name']  ?? '',
            "signatory_title"  => $data['signatory_title'] ?? '',
            "cc_email"         => (!empty($data['cc_email'])) ? $data['cc_email'] : null,
            "send_to_email"    => (!empty($data['send_to_email'])) ? (int)$data['send_to_email'] : 0,
            "status"           => $data['letter_status']     ?? 'draft',
            "purpose"          => $data['purpose']           ?? null,
            "certificate_month"=> $data['certificate_month'] ?? null,
            "gross_salary"     => $data['gross_salary']      ?? null,
            "net_salary"       => $data['net_salary']        ?? null,
        ];

        $letterId = $this->letterModel->create($saveData);

        if (!$letterId) {
            die("Database Error (Create Letter): " . $this->db->error);
        }

        // Save salary items
        if (!empty($salaryItems)) {
            $this->letterModel->createSalaryItems((int)$letterId, $salaryItems);
        }

        header("Location: " . $GLOBALS['base_url'] . "/generate-letter");
    }

    // ─── Placeholder replace ──────────────────────────────────────────────────
    private function replacePlaceholders($body, $employee, $data)
    {
        $replace = [
            "{{employee_name}}"  => $employee['full_name']        ?? '',
            "{{employee_code}}"  => $employee['employee_id']      ?? '',
            "{{designation}}"    => $employee['designation_name'] ?? '',
            "{{department}}"     => $employee['department_name']  ?? '',
            "{{joining_date}}"   => $data['joining_date']         ?? '',
            "{{current_salary}}" => $data['current_salary']       ?? '',
            "{{revised_salary}}" => $data['revised_salary']       ?? '',
            "{{new_designation}}"=> $data['new_designation']      ?? '',
            "{{letter_date}}"    => $data['letter_date']          ?? '',
        ];

        return str_replace(array_keys($replace), array_values($replace), $body);
    }

    // ─── List letter templates ────────────────────────────────────────────────
    public function template()
    {
        $designation_id = $_GET['designation_id'] ?? null;
        $type           = $_GET['type']           ?? null;

        $templates = $this->templateModel->getAll($designation_id, $type);

        $designationModel = new Designation($this->db);
        $designations     = $designationModel->getAll();

        $this->view("hrm/letter-templates/list-letter-templates", [
            "templates"    => $templates,
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
        $designations     = $designationModel->getAll();

        $this->view("hrm/letter-templates/edit-letter-template", [
            "template"     => $template,
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
            $salaryItems = $this->letterModel->getSalaryItems((int)$id);
            $this->view("hrm/letter-templates/salary-certificate-view", [
                "letter"      => $letter,
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
            $salaryItems = $this->letterModel->getSalaryItems((int)$id);
            $this->view("hrm/letter-templates/salary-certificate-print", [
                "letter"      => $letter,
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

    // ─── Generate PDF ─────────────────────────────────────────────────────────
    public function generatePDFLetter($id)
    {
        // Increase memory limit for TCPDF image processing
        ini_set('memory_limit', '512M');

        $letter = $this->letterModel->find($id);
        if (!$letter) {
            die("Letter not found");
        }

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('TPHL ERP');
        $pdf->SetTitle(ucwords(str_replace('_', ' ', $letter['letter_type'])) . " - " . $letter['employee_code']);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(20, 20, 20);
        $pdf->SetAutoPageBreak(TRUE, 20);
        $pdf->setCellPaddings(1, 1, 1, 1);
        $pdf->AddPage();

        // ── Try to add logo via native Image() (much less memory than HTML <img>) ──
        $logoPath = realpath(__DIR__ . '/../../../public/assets/images/logo/tphl_logo.png');
        if ($logoPath && file_exists($logoPath)) {
            $pdf->Image($logoPath, 20, 12, 40, 0, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
            $pdf->SetY(24); // move below logo
        }

        // ── Company info (right side, text only) ──
        $pdf->SetXY(70, 12);
        $pdf->SetFont('helvetica', 'B', 13);
        $pdf->SetTextColor(0, 100, 0);
        $pdf->Cell(0, 8, getSetting('website_title', 'The Premium Homes Ltd.'), 0, 1, 'R');
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->SetX(70);
        $pdf->Cell(0, 5, getSetting('address', 'Corporate Office, Dhaka, Bangladesh'), 0, 1, 'R');
        $pdf->SetX(70);
        $pdf->Cell(0, 5, 'Email: ' . getSetting('email', 'hr@tphl.com.bd'), 0, 1, 'R');

        // ── Horizontal rule ──
        $pdf->SetY($pdf->GetY() + 3);
        $pdf->SetDrawColor(0, 100, 0);
        $pdf->SetLineWidth(0.6);
        $pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
        $pdf->SetY($pdf->GetY() + 5);

        // ── Now render the letter body HTML ──
        ob_start();
        extract(['letter' => $letter]);

        if ($letter['letter_type'] === 'salary_certificate') {
            $salaryItems = $this->letterModel->getSalaryItems((int)$id);
            extract(['salaryItems' => $salaryItems]);
            include __DIR__ . '/../views/hrm/letter-templates/salary-certificate-pdf.php';
        } else {
            include __DIR__ . '/../views/hrm/letter-templates/pdf-letter.php';
        }

        $html = ob_get_clean();

        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->writeHTML($html, true, false, true, false, '');

        $filename = str_replace([' ', '/'], '_', $letter['letter_type']) . '_' . $letter['employee_code'] . '.pdf';

        if (ob_get_length()) ob_end_clean();
        $pdf->Output($filename, 'I');
        exit;
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
        $departmentModel  = new Department($this->db);
        $designationModel = new Designation($this->db);

        $departments  = $departmentModel->getAll();
        $designations = $designationModel->getAll();

        if ($letter['letter_type'] === 'salary_certificate') {
            $salaryItems = $this->letterModel->getSalaryItems((int)$id);
            $this->view("hrm/letter-templates/edit-generated-letter", [
                "letter"       => $letter,
                "employees"    => $employees,
                "templates"    => $templates,
                "departments"  => $departments,
                "designations" => $designations,
                "salaryItems"  => $salaryItems,
            ]);
        } else {
            $this->view("hrm/letter-templates/edit-generated-letter", [
                "letter"       => $letter,
                "employees"    => $employees,
                "templates"    => $templates,
                "departments"  => $departments,
                "designations" => $designations,
                "salaryItems"  => [],
            ]);
        }
    }

    // ─── Update letter ────────────────────────────────────────────────────────
    public function updateLetter($id)
    {
        $data     = $_POST;
        $employee = $this->employeeModel->find($data['employee_id']);

        $body = $data['letter_body'] ?? '';
        if (!empty($data['template_id']) && $data['letter_type'] !== 'salary_certificate') {
            $body = $this->replacePlaceholders($body, $employee, $data);
        }

        $salaryItems = [];
        if ($data['letter_type'] === 'salary_certificate') {
            $labels    = $_POST['item_label']    ?? [];
            $amounts   = $_POST['item_amount']   ?? [];
            $itemTypes = $_POST['item_type_row'] ?? [];

            foreach ($labels as $i => $label) {
                if (trim($label) === '' || !isset($amounts[$i])) continue;
                $salaryItems[] = [
                    'item_type'  => $itemTypes[$i] ?? 'earning',
                    'label'      => trim($label),
                    'amount'     => (float)$amounts[$i],
                    'sort_order' => $i,
                ];
            }

            $gross = 0; $deduction = 0;
            foreach ($salaryItems as $item) {
                if ($item['item_type'] === 'earning')   $gross     += $item['amount'];
                if ($item['item_type'] === 'deduction') $deduction += $item['amount'];
            }
            $data['gross_salary'] = $gross;
            $data['net_salary']   = $gross - $deduction;
            $body = '';
        }

        $letterDate = !empty($data['letter_date']) ? $data['letter_date'] : (!empty($data['sc_letter_date']) ? $data['sc_letter_date'] : date('Y-m-d'));
        $refNumber  = !empty($data['ref_number']) ? $data['ref_number'] : (!empty($data['sc_ref_number']) ? $data['sc_ref_number'] : null);
        $subject    = !empty($data['subject']) ? $data['subject'] : ($data['sc_subject'] ?? 'Salary Certificate');

        $updateData = [
            "employee_id"      => $data['employee_id'],
            "designation_id"   => $data['designation_id'] ?? null,
            "department_id"    => $data['department_id']  ?? null,
            "letter_type"      => $data['letter_type'],
            "template_id"      => (!empty($data['template_id'])) ? $data['template_id'] : null,
            "subject"          => $subject,
            "body"             => $body,
            "letter_date"      => $letterDate,
            "ref_number"       => $refNumber,
            "joining_date"     => (!empty($data['joining_date']))   ? $data['joining_date']   : null,
            "effective_date"   => (!empty($data['effective_date']))   ? $data['effective_date']   : null,
            "current_salary"   => (!empty($data['current_salary']))   ? $data['current_salary']   : null,
            "revised_salary"   => (!empty($data['revised_salary']))   ? $data['revised_salary']   : null,
            "new_designation"  => (!empty($data['new_designation']))  ? $data['new_designation']  : null,
            "signatory_name"   => $data['signatory_name']  ?? '',
            "signatory_title"  => $data['signatory_title'] ?? '',
            "cc_email"         => (!empty($data['cc_email'])) ? $data['cc_email'] : null,
            "send_to_email"    => (!empty($data['send_to_email'])) ? (int)$data['send_to_email'] : 0,
            "status"           => $data['letter_status']     ?? 'draft',
            "purpose"          => $data['purpose']           ?? null,
            "certificate_month"=> $data['certificate_month'] ?? null,
            "gross_salary"     => $data['gross_salary']      ?? null,
            "net_salary"       => $data['net_salary']        ?? null,
        ];

        if (!$this->letterModel->update($id, $updateData)) {
            die("Database Error (Update Letter): " . $this->db->error);
        }

        // Replace salary items
        if ($data['letter_type'] === 'salary_certificate') {
            $this->letterModel->deleteSalaryItems((int)$id);
            if (!empty($salaryItems)) {
                $this->letterModel->createSalaryItems((int)$id, $salaryItems);
            }
        }

        header("Location: " . $GLOBALS['base_url'] . "/generate-letter");
    }
}
