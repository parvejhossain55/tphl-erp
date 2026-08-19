<?php
class EmailStationController extends Controller
{
    protected $db;
    protected $employeeModel;
    protected $userModel;
    protected $roleModel;
    protected $departmentModel;
    protected $designationModel;
    protected $teamsModel;
    protected $emailTemplateModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->employeeModel = new Employee($this->db);
        $this->departmentModel = new Department($this->db);
        $this->designationModel = new Designation($this->db); // 
        $this->roleModel = new Role($this->db); // 

        $this->emailTemplateModel = new EmailTemplate($this->db);
        $this->userModel = new User($this->db);
        $this->teamsModel = new Team($this->db);
    }

    public function index()
    {
        return $this->view('email-station/email-station', [
            'current_route' => 'email-station',
        ]);
    }

    public function createEmailTemplate()
    {
        return $this->view('email-station/create-email-template', [
            'current_route' => 'create-email-template',
        ]);
    }

    public function storeEmailTemplate()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 1. Sanitize and retrieve POST input matching form field names
            $template_name = trim($_POST['template_name'] ?? '');
            $subject = trim($_POST['subject'] ?? '');
            $body = trim($_POST['body'] ?? ''); // Contains TinyMCE HTML content
            $status = 'active'; // Default status according to table schema

            // 2. Validate required fields
            if (empty($template_name) || empty($subject) || empty($body)) {
                // Redirect back or display error
                setError("Please fill all the required fields");
                header("Location: " . $GLOBALS['base_url'] . "/create-email-template");
                exit();
            }

            try {
                $templateID = $this->emailTemplateModel->create([
                    'template_name' => $template_name,
                    'subject' => $subject,
                    'body' => $body,
                    'status' => $status
                ]);

                if ($templateID) {
                    // Success redirect
                    setSuccess("Email Template created successfully!");
                    header("Location: {$GLOBALS['base_url']}/email-station");
                    exit;
                }

            } catch (Exception $e) {
                //  exact error show (DEV mode)
                $_SESSION['error'] = "Error: " . $e->getMessage();

                header("Location: {$GLOBALS['base_url']}/create-email-template");
                exit;
            }
        }
    }
}