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

    public function index()
    {
        // Current month/year
          $employees   = $this->employeeModel->getAll();
        $designations = $this->getDesignations(); // fetch all designations
        $departments  = $this->getDepartments();  // fetch all departments
        $this->view("hrm/letter-templates/generate-employee-letter", [
            "current_route"  => "generate-letter",
              "employees" => $employees,
            "designations" => $designations,
            "departments" => $departments
           
        ]);
    }

    public function create()
    {
        $employees = $this->employeeModel->getAllWithDesignation();
        $items = $this->payslipItemModel->getAll();
        $this->view("hrm/letter-templates/create-letter", [
            "current_route"  => "generate-letter",
           
        ]);
    }


    
    public function generateLetterPost() {
        $employee_id = $_POST['employee_id'];
        $letter_type = $_POST['letter_type'];
        $template_id = $_POST['template_id'] ?? null;

        $employee = $this->employeeModel->getById($employee_id);

        if ($template_id) {
            $template = $this->templateModel->getById($template_id);
        } else {
            $templates = $this->templateModel->getByTypeAndDesignation($letter_type, $employee['designation_id']);
            $template = $templates[0]; // first match
        }

        // Replace placeholders dynamically
        $letter_body = str_replace(
            ['{{employee_name}}', '{{designation}}', '{{department}}', '{{joining_date}}', '{{responsibilities}}'],
            [$employee['full_name'], $employee['designation_name'], $employee['department_name'], $employee['joining_date'], $employee['responsibilities']],
            $template['body']
        );

        $subject = $_POST['subject'] ?? $template['subject'];

        // Save or generate PDF logic here...
        echo "<h3>$subject</h3>";
        echo "<p>$letter_body</p>";
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

    // private function view($path, $data) {
    //     extract($data);
    //     include __DIR__ . "/../views/$path.php";
    // }
   
}

