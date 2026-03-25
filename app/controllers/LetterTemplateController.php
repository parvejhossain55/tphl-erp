<?php

class LetterTemplateController extends Controller
{
    protected $db;
    protected $employeeModel;
    protected $payslipItemModel;
    protected $paymentModel;
    protected $bankModel;
    protected $templateModel;
    public function __construct($db)
    {
        $this->db = $db;
        $this->employeeModel = new Employee($this->db);
        $this->payslipItemModel = new PayslipItem($this->db);
        $this->paymentModel = new Payslip($this->db);
        $this->bankModel = new LetterTemplate($this->db);
    }

    public function index() {
        $templates = $this->templateModel->getAll();
        $this->view("hrm/letter-templates/list-letter-templates", [
            "current_route" => "letter-templates",
            "templates" => $templates
        ]);
    }

    public function create() {
        $designations = $this->getDesignations();
        $this->view("hrm/letter-templates/create-letter-template", [
            "current_route" => "letter-templates",
            "designations" => $designations
        ]);
    }

    public function store() {
        $data = [
            'type' => $_POST['type'] ?? '',
            'designation_id' => $_POST['designation_id'] ?? null,
            'subject' => $_POST['subject'] ?? '',
            'body' => $_POST['body'] ?? ''
        ];
        $this->templateModel->create($data);
        $_SESSION['success'] = "Template created successfully";
        header("Location: " . $GLOBALS['base_url'] . "/letter-templates");
        exit();
    }

    public function edit($id) {
        $template = $this->templateModel->getById($id);
        $designations = $this->getDesignations();
        $this->view("hrm/letter-templates/edit-letter-template", [
            "current_route" => "letter-templates",
            "template" => $template,
            "designations" => $designations
        ]);
    }

    public function update($id) {
        $data = [
            'type' => $_POST['type'] ?? '',
            'designation_id' => $_POST['designation_id'] ?? null,
            'subject' => $_POST['subject'] ?? '',
            'body' => $_POST['body'] ?? ''
        ];
        $this->templateModel->update($id, $data);
        $_SESSION['success'] = "Template updated successfully";
        header("Location: " . $GLOBALS['base_url'] . "/letter-templates");
        exit();
    }

    public function delete($id) {
        $this->templateModel->delete($id);
        $_SESSION['success'] = "Template deleted successfully";
        header("Location: " . $GLOBALS['base_url'] . "/letter-templates");
        exit();
    }

    // Generate Letter features
    public function generateLetter() {
        $employees = $this->employeeModel->getAll();
        $designations = $this->getDesignations();
        $this->view("hrm/letter-templates/generate-employee-letter", [
            "current_route" => "generate-letter",
            "employees" => $employees,
            "designations" => $designations
        ]);
    }

    public function fetchTemplate() {
        $designation_id = $_GET['designation_id'] ?? null;
        $type = $_GET['type'] ?? null;
        $employee_id = $_GET['employee_id'] ?? null;

        if (!$type || !$employee_id) {
            echo json_encode(["status" => "error", "message" => "Missing parameters"]);
            return;
        }

        $templates = $this->templateModel->getByTypeAndDesignation($type, $designation_id);
        if (empty($templates)) {
            echo json_encode(["status" => "error", "message" => "No template found for this designation and type"]);
            return;
        }

        $template = $templates[0]; // Take the first matched
        $employee = $this->employeeModel->getById($employee_id);

        // Replace placeholders dynamically
        $body = str_replace(
            ['{{employee_name}}', '{{designation}}', '{{department}}', '{{joining_date}}', '{{responsibilities}}'],
            [$employee['full_name'], $employee['designation_name'], $employee['department_name'], $employee['joining_date'], $employee['responsibilities']],
            $template['body']
        );

        echo json_encode([
            "status" => "success",
            "subject" => $template['subject'],
            "body" => $body
        ]);
    }

    private function getDesignations() {
        // Fetch designations from DB
        global $db;
        $res = $db->query("SELECT * FROM designations");
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    private function getDepartments() {
        global $db;
        $res = $db->query("SELECT * FROM departments");
        return $res->fetch_all(MYSQLI_ASSOC);
    }
   
}

