<?php
class OnboardClientController extends Controller
{
    protected $db;

    protected $userModel;
    protected $leadModel;
    protected $onboardClient;
    protected $leadBookingModel;
    public function __construct($db)
    {
        $this->db = $db;
        $this->userModel = new User($this->db);
        $this->leadModel = new Lead($this->db);
        $this->onboardClient = new OnboardClient($this->db);
        $this->leadBookingModel = new BookingLead($this->db);
    }
    
    public function index()
    {
        $assignedUsers = $this->userModel->getAllRoleUser();
        $year  = $_GET['year']  ?? null;
        $month = $_GET['month'] ?? null;
        $owners = $this->userModel->getAll();
        $onboardAccessClients = $this->onboardClient->getAll();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $message = "";

        $this->view("sales-developments/accessOnboardClient/access-onboard-clients", [
            "current_route" => "onboard-access-client-lists",
            "page_title" => "Onboard Client List",
            "owners" => $owners,
            "onboardAccessClients" => $onboardAccessClients,
            "message" => $message,
            "month" => $month,
            "year" => $year,

            "assignedUsers" => $assignedUsers,

        ]);
    }


    public function create()
    {
        $year  = $_GET['year']  ?? null;
        $month = $_GET['month'] ?? null;
        $onboardClients = $this->leadBookingModel->getAllOnboardClient($year, $month);
        $this->view("sales-developments/accessOnboardClient/create-login-onboard-client", [
            "current_route"   => "onboard-access-client-lists",
            "page_title"      => "Add Onboard client access",
            "onboardClients" => $onboardClients,
        ]);
    }

    // Handle form submission
    // Store onboard client

    public function store()
    {
        $leadId        = $_POST['lead_id'] ?? 0;
        $email         = trim($_POST['email'] ?? '');
        $login_email   = trim($_POST['login_email'] ?? '');
        $password      = trim($_POST['password'] ?? '');
        $created_by    = $_SESSION['user']['id'] ?? null;

        if (empty($leadId) || empty($login_email) || empty($password)) {
            setError("Required fields are missing");
            header("Location: {$GLOBALS['base_url']}/onboard-access-client-lists");
            exit;
        }

        $links = [
            'cameraLink1'        => $_POST['cameraLink1'] ?? null,
            'cameraLink2'        => $_POST['cameraLink2'] ?? null,
            'moneyReceiptLink'   => $_POST['moneyReceiptLink'] ?? null,
            'purchaseDetailsLink' => $_POST['purchaseDetailsLink'] ?? null
        ];

        $newId = $this->onboardClient->create( $leadId, $email, $password, $links, $created_by, $login_email);

        if ($newId) {
            setSuccess("Client login access created successfully");
        } else {
            setError("Failed to create onboard client or client already onboarded");
        }

        header("Location: {$GLOBALS['base_url']}/onboard-access-client-lists");
        exit;
    }
    // Controller
    public function getLeadBookingDetails($id = null)
    {
        $leadBookingId = $id ?? $_GET['lead_booking_id'] ?? 0;
        if (!$leadBookingId) {
            echo json_encode(['success' => false]);
            exit;
        }

        $sql = "SELECT lb.id, lb.project_id, lb.flat_id,
                   p.name AS project_name,
                   f.flat_name
            FROM lead_bookings lb
            LEFT JOIN projects p ON lb.project_id = p.id
            LEFT JOIN flats f ON lb.flat_id = f.id
            WHERE lb.id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $leadBookingId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        header('Content-Type: application/json'); // Make sure browser parses as JSON
        echo json_encode($result ? ['success' => true, 'data' => $result] : ['success' => false]);
        exit;
    }


    public function edit($id)
    {
        $onboardClient = $this->onboardClient->find($id);


        // Pass user to view
        $this->view("sales-developments/accessOnboardClient/edit-access-onboard-client", [
            "current_route" => "edit-onboard-client",
            "active" => "edit-onboard-client",
            "page_title" => "Edit User",
            "show_breadcrumb" => true,
            "onboardClient" => $onboardClient
        ]);
    }

    // public function show($id)
    // {
    //     $user = $this->userModel->find($id);
    //     $designations = $this->designationModel->getAll();
    //     $roles = $this->roleModel->getAll();
    //     if (!$user) {
    //         setError("User Not Found");
    //     }
    //     $this->view("users/show-user", [
    //         "current_route" => 'user-profile',
    //         "active" => 'user-profile',
    //         "page_title" => 'User Profile',
    //         "user" => $user,
    //     ]);
    // }


    public function update($id)
    {
        // Required: onboard client ID


        // Collect form data
        $data = [
            'email'              => $_POST['email'] ?? '',
            'login_email'        => $_POST['login_email'] ?? '',
            'password'           => $_POST['password'] ?? null, // optional
            'cameraLink1'        => $_POST['cameraLink1'] ?? null,
            'cameraLink2'        => $_POST['cameraLink2'] ?? null,
            'moneyReceiptLink'   => $_POST['moneyReceiptLink'] ?? null,
            'purchaseDetailsLink' => $_POST['purchaseDetailsLink'] ?? null,
        ];

        // Update using model
        if ($this->onboardClient->update($id, $data)) {
            setSuccess("Client details updated successfully");
        } else {
            setError("Failed to update client details");
        }

        // Redirect back to list
        header("Location: {$GLOBALS['base_url']}/onboard-access-client-lists");
        exit;
    }
    public function bulkUploadForm()
    {
        $this->view("sales-developments/accessOnboardClient/bulk-upload", []);
    }

    public function bulkPreview()
    {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            setError("Please upload a valid CSV file.");
            header("Location: {$GLOBALS['base_url']}/onboard-client-bulk-upload");
            exit;
        }

        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");
        if ($handle === FALSE) {
            setError("Failed to open the CSV file.");
            header("Location: {$GLOBALS['base_url']}/onboard-client-bulk-upload");
            exit;
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            setError("CSV file is empty or invalid.");
            header("Location: {$GLOBALS['base_url']}/onboard-client-bulk-upload");
            exit;
        }

        // Clean headers
        $headers = array_map('trim', $headers);
        $headers = array_map('strtolower', $headers);

        $requiredHeaders = ['name', 'phone', 'login_email', 'password'];
        foreach ($requiredHeaders as $req) {
            if (!in_array($req, $headers)) {
                setError("Missing required column: " . $req);
                header("Location: {$GLOBALS['base_url']}/onboard-client-bulk-upload");
                exit;
            }
        }

        $previewData = [];
        $validData = [];
        $rowNum = 1;

        while (($data = fgetcsv($handle)) !== FALSE) {
            $rowNum++;
            if (count($data) !== count($headers)) {
                continue; // Skip malformed rows
            }

            $rowData = array_combine($headers, array_map('trim', $data));
            
            $name = $rowData['name'] ?? '';
            $phone = $rowData['phone'] ?? '';
            $loginEmail = $rowData['login_email'] ?? '';
            $password = $rowData['password'] ?? '';
            $contactEmail = $rowData['contact_email'] ?? '';
            
            $error = null;
            $bookingCount = 0;
            $leadId = null;

            if (empty($name) || empty($phone) || empty($loginEmail) || empty($password)) {
                $error = "Name, phone, login_email, and password are required.";
            } else {
                // Find Lead
                $leadInfo = $this->leadModel->findDuplicate($name, $phone);
                if (!$leadInfo) {
                    $error = "Lead not found in database for Name: $name, Phone: $phone";
                } else {
                    $leadId = $leadInfo['id'];
                    
                    // Check if already onboarded
                    $checkSql = "SELECT id FROM onboard_clients WHERE lead_id = ?";
                    $checkStmt = $this->db->prepare($checkSql);
                    $checkStmt->bind_param("i", $leadId);
                    $checkStmt->execute();
                    if ($checkStmt->get_result()->fetch_assoc()) {
                        $error = "Client is already onboarded.";
                    } else {
                        // Check bookings
                        $bookingCount = $this->leadBookingModel->countBookingsByLeadId($leadId);
                        if ($bookingCount == 0) {
                            $error = "Lead found, but no completed/booked bookings exist.";
                        }
                    }
                }
            }

            $previewRow = [
                'row_num' => $rowNum,
                'name' => $name,
                'phone' => $phone,
                'login_email' => $loginEmail,
                'contact_email' => $contactEmail,
                'password' => $password,
                'booking_count' => $bookingCount,
                'lead_id' => $leadId,
                'error' => $error
            ];

            $previewData[] = $previewRow;
            
            if (!$error) {
                $validData[] = $previewRow;
            }
        }

        fclose($handle);

        $this->view("sales-developments/accessOnboardClient/bulk-preview", [
            'previewData' => $previewData,
            'validData' => $validData
        ]);
    }

    public function bulkStore()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['valid_data'])) {
            setError("No valid data received.");
            header("Location: {$GLOBALS['base_url']}/onboard-client-bulk-upload");
            exit;
        }

        $validData = json_decode($_POST['valid_data'], true);
        if (!is_array($validData)) {
            setError("Invalid data format.");
            header("Location: {$GLOBALS['base_url']}/onboard-client-bulk-upload");
            exit;
        }

        $createdBy = $_SESSION['user']['id'] ?? null;
        $successCount = 0;

        foreach ($validData as $row) {
            $leadId = $row['lead_id'];
            $email = $row['contact_email'];
            $loginEmail = $row['login_email'];
            $password = $row['password'];
            
            // Empty links for bulk upload
            $links = [
                'cameraLink1' => null,
                'cameraLink2' => null,
                'moneyReceiptLink' => null,
                'purchaseDetailsLink' => null
            ];

            $newId = $this->onboardClient->create($leadId, $email, $password, $links, $createdBy, $loginEmail);
            if ($newId) {
                $successCount++;
            }
        }

        setSuccess("$successCount clients have been successfully onboarded.");
        header("Location: {$GLOBALS['base_url']}/onboard-access-client-lists");
        exit;
    }
}
