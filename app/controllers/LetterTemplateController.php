<?php

class LetterTemplateController extends Controller
{
    protected $db;
    protected $employeeModel;
    protected $templateModel;
    protected $letterModel;
    protected $leaveModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->employeeModel = new Employee($this->db);
        $this->templateModel = new LetterTemplate($this->db);
        $this->letterModel = new Letter($this->db);
    }

    public function getEmployeeInfo()
    {
        if (!isset($_GET['employee_id'])) {
            echo json_encode(['success' => false]);
            return;
        }

        $empId = (int)$_GET['employee_id'];
        $employee = $this->employeeModel->find($empId);

        if ($employee) {
            echo json_encode([
                'success' => true,
                'department_id' => $employee['department_id'],
                'designation_id' => $employee['designation_id']
            ]);
        } else {
            echo json_encode(['success' => false]);
        }
    }

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
            'templates' => $templates
        ]);
    }

    public function getTemplate()
    {
        $id = $_GET['id'] ?? null;

        if (!$id) {
            echo json_encode(['success' => false]);
            return;
        }

        $template = $this->templateModel->find($id);
        if ($template) {
            echo json_encode([
                'success' => true,
                'template' => $template
            ]);
        } else {
            echo json_encode(['success' => false]);
        }
    }

    public function generateLetter()
    {
        $designation_id = $_GET['designation_id'] ?? null;
        $letter_type = $_GET['type'] ?? null;

        $letters = $this->letterModel->getAllWithDetails($designation_id, $letter_type);

        require_once __DIR__ . '/../models/Designation.php';
        $designationModel = new Designation($this->db);
        $designations = $designationModel->getAll();

        // DEBUG: If only 1 letter and id looks wrong, add this to see
        // echo '<pre>'; var_dump($letters); echo '</pre>'; exit;

        $this->view("hrm/letter-templates/generate-employee-letter", [
            "letters" => $letters,
            "designations" => $designations
        ]);
    }
    // show generate form
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
            "designations" => $designations
        ]);
    }

    // store letter
    public function store()
    {
        $data = $_POST;

        // employee load
        $employee = $this->employeeModel->find($data['employee_id']);

        //  template load (optional)
        $template = null;
        if (!empty($data['template_id'])) {
            $template = $this->templateModel->find($data['template_id']);
        }

        // body decide
        $body = $data['letter_body'];

        if ($template) {
            $body = $this->replacePlaceholders($template['body'], $employee, $data);
        }

        // final save data
        $saveData = [
            "employee_id" => $data['employee_id'],
            "designation_id" => $employee['designation_id'], 
            "department_id" => $employee['department_id'],   //  auto
            "letter_type" => $data['letter_type'],
            "template_id" => $data['template_id'] ?? null,
            "subject" => $data['subject'],
            "body" => $body,
            "letter_date" => $data['letter_date'],
            "ref_number" => $data['ref_number'] ?? null,
            "joining_date" => $data['joining_date'],
            "effective_date" => $data['effective_date'] ?? null,
            "current_salary" => $data['current_salary'] ?? null,
            "revised_salary" => $data['revised_salary'] ?? null,
            "new_designation" => $data['new_designation'] ?? null,
            "signatory_name" => $data['signatory_name'],
            "signatory_title" => $data['signatory_title'],
            "cc_email" => $data['cc_email'] ?? null,
            "send_to_email" => $data['send_to_email'] ?? 0,
            "status" => $data['letter_status']
        ];

        $this->letterModel->create($saveData);

        header("Location: " . $GLOBALS['base_url'] . "/generate-letter");
    }

    //  placeholder replace system
    private function replacePlaceholders($body, $employee, $data)
    {
        $replace = [
            "{{employee_name}}" => $employee['full_name'] ?? '',
            "{{employee_code}}" => $employee['employee_id'] ?? '',
            "{{designation}}" => $employee['designation_name'] ?? '',
            "{{department}}" => $employee['department_name'] ?? '',
            "{{joining_date}}" => $data['joining_date'] ?? '',
            "{{current_salary}}" => $data['current_salary'] ?? '',
            "{{revised_salary}}" => $data['revised_salary'] ?? '',
            "{{new_designation}}" => $data['new_designation'] ?? '',
            "{{letter_date}}" => $data['letter_date'] ?? '',
        ];

        return str_replace(array_keys($replace), array_values($replace), $body);
    }
    public function template()
    {
        $designation_id = $_GET['designation_id'] ?? null;
        $type = $_GET['type'] ?? null;

        $templates = $this->templateModel->getAll($designation_id, $type);

        $designationModel = new Designation($this->db);
        $designations = $designationModel->getAll();

        $this->view("hrm/letter-templates/list-letter-templates", [
            "templates" => $templates,
            "designations" => $designations
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
            "designations" => $designations
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

    public function viewLetter($id)
    {
        $letter = $this->letterModel->find($id);
        $this->view("hrm/letter-templates/view-letter", ["letter" => $letter]);
    }

    public function printLetter($id)
    {
        $letter = $this->letterModel->find($id);
        $this->view("hrm/letter-templates/print-letter", ["letter" => $letter], false);
    }

    public function deleteLetter($id)
    {
        $this->letterModel->delete($id);
        header("Location: " . $GLOBALS['base_url'] . "/generate-letter");
    }

    public function generatePDFLetter($id)
    {
        $letter = $this->letterModel->find($id);
        if (!$letter) {
            die("Letter not found");
        }

        // TCPDF is already autoloaded in public/index.php
        
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('TPHL ERP');
        $pdf->SetTitle(ucfirst($letter['letter_type']) . " - " . $letter['employee_code']);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(20, 20, 20);
        $pdf->SetAutoPageBreak(TRUE, 20);
        
        $pdf->AddPage();
        
        // Load HTML from view file to allow user changes to reflect
        ob_start();
        extract(['letter' => $letter]);
        include __DIR__ . '/../views/hrm/letter-templates/pdf-letter.php';
        $html = ob_get_clean();

        $pdf->writeHTML($html, true, false, true, false, '');

        $filename = str_replace(' ', '_', $letter['letter_type']) . "_" . $letter['employee_code'] . ".pdf";
        
        // Clean output buffer to prevent "Some data already output" error
        if (ob_get_length()) ob_end_clean();
        
        $pdf->Output($filename, 'I');
        exit;
    }

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

        $this->view("hrm/letter-templates/edit-generated-letter", [
            "letter" => $letter,
            "employees" => $employees,
            "templates" => $templates,
            "departments" => $departments,
            "designations" => $designations
        ]);
    }

    public function updateLetter($id)
    {
        $data = $_POST;

        $employee = $this->employeeModel->find($data['employee_id']);

        $body = $data['letter_body'];
        if (!empty($data['template_id'])) {
            $body = $this->replacePlaceholders($body, $employee, $data);
        }

        $updateData = [
            "employee_id" => $data['employee_id'],
            "designation_id" => $data['designation_id'],
            "department_id" => $data['department_id'],
            "letter_type" => $data['letter_type'],
            "template_id" => $data['template_id'] ?? null,
            "subject" => $data['subject'],
            "body" => $body,
            "letter_date" => $data['letter_date'],
            "ref_number" => $data['ref_number'] ?? null,
            "joining_date" => $data['joining_date'],
            "effective_date" => $data['effective_date'] ?? null,
            "current_salary" => $data['current_salary'] ?? null,
            "revised_salary" => $data['revised_salary'] ?? null,
            "new_designation" => $data['new_designation'] ?? null,
            "signatory_name" => $data['signatory_name'],
            "signatory_title" => $data['signatory_title'],
            "cc_email" => $data['cc_email'] ?? null,
            "send_to_email" => $data['send_to_email'] ?? 0,
            "status" => $data['letter_status']
        ];

        $this->letterModel->update($id, $updateData);

        header("Location: " . $GLOBALS['base_url'] . "/generate-letter");
    }
}

