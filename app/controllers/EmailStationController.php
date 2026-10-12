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

    public function storeEmailAlert()
    {
        // dd($_POST);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $submissionType = $_POST['submission_type'] ?? 'draft';

            // 1. Sanitize and retrieve POST input matching form field names
            $email_template_id = trim($_POST['email_template_id'] ?? '');
            $subject = trim($_POST['subject'] ?? '');
            $body = trim($_POST['body'] ?? ''); // Contains TinyMCE HTML content
            $status = 'draft'; // Default status according to table schema
            $filterType = trim($_POST['filter_type'] ?? '');
            $targetType = trim($_POST['filter_type'] ?? '');

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
                    'target_type' => $filterType,
                    'status' => $status,
                    'created_by' => $_SESSION['user']['id']
                ]);

                if (!$emailAlertID) {
                    setError("Failed to create email alert");
                    header("Location: " . $GLOBALS['base_url'] . "/create-email-alert");
                    exit();
                }

                if ($_POST['filter_type'] != 'all_employees') {
                    $targetIds = $_POST['targetIds'] ?? [];
                    $this->emailAlertTargetModel->createBatch(
                        $emailAlertID,
                        $_POST['filter_type'],
                        $targetIds
                    );
                }

                if ($submissionType === 'send') {

                    if ($targetType == 'department') {
                        $emails = $this->getEmployeesEmailByDepartmentIds($targetIds);
                    } elseif ($targetType == 'designation') {
                        $emails = $this->getEmployeesEmailByDesignationIds($targetIds);
                    } elseif ($targetType == 'specific_employees') {
                        $emails = $this->getEmployeesEmailByEmployeeIds($targetIds);
                    }

                    // Handle send logic
                    $isMailSent = $this->sendEmail($emails, $subject, $body);

                    if ($isMailSent) {
                        $this->emailAlertModel->updateStatus($emailAlertID, 'sent');
                    } else {
                        $this->emailAlertModel->updateStatus($emailAlertID, 'failed');
                    }
                } elseif ($submissionType === 'draft') {
                    // Handle draft logic
                    $this->emailAlertModel->updateStatus($emailAlertID, 'draft');
                    // Success redirect
                    setSuccess("Email Alert saved as draft successfully!");
                    header("Location: {$GLOBALS['base_url']}/email-station");
                }


            } catch (Exception $e) {
                //  exact error show (DEV mode)
                setError($e->getMessage());
                // $_SESSION['error'] = "Error: " . $e->getMessage();

                header("Location: {$GLOBALS['base_url']}/create-email-alert");
                exit;
            }
        }
    }

    public function sendEmail(array $toEmails, $subject, $body)
    {
        if (empty($toEmails)) {
            return false;
        }

        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';

        try {
            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'farzana.tphl13@gmail.com';
            $mail->Password = 'hvptjfnsprecofmi';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            // Sender
            $mail->setFrom(
                'farzana.tphl13@gmail.com',
                'Company HR System'
            );

            $mail->addReplyTo(
                'no-reply@yourdomain.com',
                'Information'
            );

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);

            $failedEmails = [];

            foreach ($toEmails as $toEmail) {

                // Clear previous recipient
                $mail->clearAddresses();

                $toEmail = trim($toEmail);

                if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
                    $failedEmails[] = $toEmail;
                    continue;
                }

                $mail->addAddress($toEmail);

                try {
                    $mail->send();
                } catch (Exception $e) {
                    $failedEmails[] = $toEmail;

                    error_log(
                        "Email could not be sent to {$toEmail}. " .
                        "Mailer Error: {$mail->ErrorInfo}"
                    );
                }
            }

            // Return true only if ALL emails were sent
            return empty($failedEmails);

        } catch (Exception $e) {

            error_log(
                "Email configuration error. " .
                "Mailer Error: {$mail->ErrorInfo}"
            );

            return false;
        }
    }


    public function getColumnById($id, $table, $column)
    {
        // Sanitize SQL identifiers
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);

        $id = (int) $id;

        $sql = "SELECT {$column} 
                FROM {$table} 
                WHERE id = ?
                LIMIT 1";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }

        $stmt->bind_param("i", $id);

        if (!$stmt->execute()) {
            die("Query failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $stmt->close();

        return $row ? $row[$column] : null;
    }

    public function getEmployeesEmailByDepartmentIds(array $departmentIds)
    {
        if (empty($departmentIds)) {
            return [];
        }

        // Convert IDs to integers
        $departmentIds = array_map('intval', $departmentIds);

        // Create ?, ?, ? placeholders
        $placeholders = implode(',', array_fill(0, count($departmentIds), '?'));

        $sql = "SELECT DISTINCT email
                FROM employees
                WHERE department_id IN ($placeholders)
                AND email IS NOT NULL
                AND email != ''";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }

        // Build bind_param types dynamically
        $types = str_repeat('i', count($departmentIds));

        $bindParams = [$types];
        foreach ($departmentIds as $key => $value) {
            $bindParams[] = &$departmentIds[$key];
        }

        call_user_func_array([$stmt, 'bind_param'], $bindParams);

        if (!$stmt->execute()) {
            die("Query failed: " . $stmt->error);
        }

        $result = $stmt->get_result();

        $emails = [];

        while ($row = $result->fetch_assoc()) {
            $emails[] = $row['email'];
        }

        $stmt->close();

        return $emails;
    }

    public function getEmployeesEmailByDesignationIds(array $designationIds)
    {
        if (empty($designationIds)) {
            return [];
        }

        // Convert IDs to integers
        $designationIds = array_map('intval', $designationIds);

        // Create ?, ?, ? placeholders
        $placeholders = implode(',', array_fill(0, count($designationIds), '?'));

        $sql = "SELECT DISTINCT email
                FROM employees
                WHERE designation_id IN ($placeholders)
                AND email IS NOT NULL
                AND email != ''";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }

        // Build bind_param types dynamically
        $types = str_repeat('i', count($designationIds));

        $bindParams = [$types];
        foreach ($designationIds as $key => $value) {
            $bindParams[] = &$designationIds[$key];
        }

        call_user_func_array([$stmt, 'bind_param'], $bindParams);

        if (!$stmt->execute()) {
            die("Query failed: " . $stmt->error);
        }

        $result = $stmt->get_result();

        $emails = [];

        while ($row = $result->fetch_assoc()) {
            $emails[] = $row['email'];
        }

        $stmt->close();

        return $emails;
    }

    public function getEmployeesEmailByEmployeeIds(array $employeeIds)
    {
        if (empty($employeeIds)) {
            return [];
        }

        // Convert IDs to integers
        $employeeIds = array_map('intval', $employeeIds);

        // Create ?, ?, ? placeholders
        $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));

        $sql = "SELECT DISTINCT email
                FROM employees
                WHERE id IN ($placeholders)
                AND email IS NOT NULL
                AND email != ''";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }

        // Build bind_param types dynamically
        $types = str_repeat('i', count($employeeIds));

        $bindParams = [$types];
        foreach ($employeeIds as $key => $value) {
            $bindParams[] = &$employeeIds[$key];
        }

        call_user_func_array([$stmt, 'bind_param'], $bindParams);

        if (!$stmt->execute()) {
            die("Query failed: " . $stmt->error);
        }

        $result = $stmt->get_result();

        $emails = [];

        while ($row = $result->fetch_assoc()) {
            $emails[] = $row['email'];
        }

        $stmt->close();

        return $emails;
    }
}