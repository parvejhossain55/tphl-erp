<?php

use PHPMailer\PHPMailer\PHPMailer;
class EmailStationController extends Controller
{
    protected $db;
    protected $employeeModel;
    protected $roleModel;
    protected $departmentModel;
    protected $designationModel;
    protected $emailTemplateModel;
    protected $emailAlertModel;
    protected $emailAlertTargetModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->employeeModel = new Employee($this->db);
        $this->departmentModel = new Department($this->db);
        $this->designationModel = new Designation($this->db); // 
        $this->emailAlertModel = new EmailAlert($this->db);
        $this->emailTemplateModel = new EmailTemplate($this->db);
        $this->departmentModel = new Department($this->db);
        $this->designationModel = new Designation($this->db);
        $this->emailAlertTargetModel = new EmailAlertTarget($this->db);
    }

    public function index()
    {
        $emailAlerts = $this->emailAlertModel->getAllWith('email_templates', 'email_template_id', 'id');

        return $this->view('email-station/email-station', [
            'current_route' => 'email-station',
            'emailAlerts' => $emailAlerts,
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

    public function createEmailAlert()
    {
        $departments = $this->departmentModel->getAll();
        $designations = $this->designationModel->getAll();
        $employees = $this->employeeModel->getAll();
        $emailTemplates = $this->emailTemplateModel->getAll();

        return $this->view('email-station/create-email-alert', [
            'current_route' => 'create-email-alert',
            'departments' => $departments,
            'designations' => $designations,
            'employees' => $employees,
            'emailTemplates' => $emailTemplates,
        ]);
    }

    public function saveAsDraft()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 1. Sanitize and retrieve POST input matching form field names
            $email_template_id = trim($_POST['email_template_id'] ?? '');
            $subject = trim($_POST['subject'] ?? '');
            $body = trim($_POST['body'] ?? ''); // Contains TinyMCE HTML content
            $status = 'draft'; // Default status according to table schema

            // 2. Validate required fields
            if (empty($email_template_id) || empty($subject) || empty($body)) {
                // Redirect back or display error
                setError("Please fill all the required fields");
                header("Location: " . $GLOBALS['base_url'] . "/create-email-alert");
                exit();
            }

            try {
                $emailAlertID = $this->emailAlertModel->create([
                    'email_template_id' => $email_template_id,
                    'subject' => $subject,
                    'body' => $body,
                    'status' => $status,
                    'created_by' => $_SESSION['user']['id']
                ]);

                if ($emailAlertID) {
                    // Success redirect
                    setSuccess("Email Alert saved as draft successfully!");
                    header("Location: {$GLOBALS['base_url']}/email-station");
                    exit;
                }

            } catch (Exception $e) {
                //  exact error show (DEV mode)
                $_SESSION['error'] = "Error: " . $e->getMessage();

                header("Location: {$GLOBALS['base_url']}/create-email-alert");
                exit;
            }
        }
    }

    public function sendMail()
    {
        echo '<pre>';
        print_r($_POST);
        echo '</pre>';
        exit;

        if ($_POST['filter_type'] == 'all_employees') {

        }
    }

    public function storeEmailAlert()
    {
        dd($_POST);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $submissionType = $_POST['submission_type'] ?? 'draft';

            // 1. Sanitize and retrieve POST input matching form field names
            $email_template_id = trim($_POST['email_template_id'] ?? '');
            $subject = trim($_POST['subject'] ?? '');
            $body = trim($_POST['body'] ?? ''); // Contains TinyMCE HTML content
            $status = 'draft'; // Default status according to table schema

            // 2. Validate required fields
            if (empty($email_template_id) || empty($subject) || empty($body)) {
                // Redirect back or display error
                setError("Please fill all the required fields");
                header("Location: " . $GLOBALS['base_url'] . "/create-email-alert");
                exit();
            }

            try {
                $emailAlertID = $this->emailAlertModel->create([
                    'email_template_id' => $email_template_id,
                    'subject' => $subject,
                    'body' => $body,
                    'status' => $status,
                    'created_by' => $_SESSION['user']['id']
                ]);

                // if ($emailAlertID) {
                //     // Success redirect
                //     setSuccess("Email Alert saved as draft successfully!");
                //     header("Location: {$GLOBALS['base_url']}/email-station");
                // }

                if (!$emailAlertID) {
                    setError("Failed to create email alert");
                    header("Location: " . $GLOBALS['base_url'] . "/create-email-alert");
                    exit();
                }
                if ($_POST['filter_type'] != 'all_employees') {
                    $targetIds = $_POST['target_ids'] ?? [];
                    $this->emailAlertTargetModel->createBatch(
                        $emailAlertID,
                        $_POST['filter_type'],
                        $targetIds
                    );
                }

                if ($submissionType === 'send') {
                    // Handle send logic
                    $isMailSent = $this->sendMail();
                    if ($isMailSent) {
                        $this->emailAlertModel->updateStatus($emailAlertID, 'sent');
                    } else {
                        $this->emailAlertModel->updateStatus($emailAlertID, 'failed');
                    }
                } elseif ($submissionType === 'draft') {
                    // Handle draft logic
                    $this->emailAlertModel->updateStatus($emailAlertID, 'draft');
                }

            } catch (Exception $e) {
                //  exact error show (DEV mode)
                $_SESSION['error'] = "Error: " . $e->getMessage();

                header("Location: {$GLOBALS['base_url']}/create-email-alert");
                exit;
            }
        }
    }

    public function sendEmail($toEmail, $recipientName, $subject, $body)
    {
        // 1. Create an instance of PHPMailer; passing `true` enables exceptions
        $mail = new PHPMailer(true);

        try {
            // 2. SMTP Server Configuration
            $mail->isSMTP();                                            // Send using SMTP
            $mail->Host = 'smtp.gmail.com';                       // Set SMTP server (e.g., Gmail, Mailtrap, SendGrid)
            $mail->SMTPAuth = true;                                   // Enable SMTP authentication
            $mail->Username = 'your_email@gmail.com';                 // SMTP username
            $mail->Password = 'your_app_password';                    // SMTP password (use App Password for Gmail)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable implicit TLS encryption (or ENCRYPTION_SMTPS for SSL)
            $mail->Port = 587;                                    // TCP port (587 for TLS, 465 for SSL)

            // 3. Sender & Recipient Setup
            $mail->setFrom('your_email@gmail.com', 'Company HR System');
            $mail->addAddress($toEmail, $recipientName);                // Add recipient email and name
            $mail->addReplyTo('no-reply@yourdomain.com', 'Information');

            // 4. Content Configuration
            $mail->isHTML(true);                                        // Set email format to HTML
            $mail->Subject = $subject;
            $mail->Body = $body;                                     // TinyMCE HTML body content
            $mail->AltBody = strip_tags($body);                         // Plain text version for non-HTML mail clients

            // 5. Send Email
            $mail->send();
            return true;

        } catch (Exception $e) {
            // Log error message or handle exception
            error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }
}