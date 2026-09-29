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

        // New required columns: project_name + flat_name to find lead_id from lead_bookings
        $requiredHeaders = ['project_name', 'flat_name', 'login_email', 'password'];
        foreach ($requiredHeaders as $req) {
            if (!in_array($req, $headers)) {
                setError("Missing required column: " . $req);
                header("Location: {$GLOBALS['base_url']}/onboard-client-bulk-upload");
                exit;
            }
        }

        // Strip BOM from first header if present
        if (isset($headers[0])) {
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        }

        $previewData = [];
        $validData   = [];
        $seenLeadIds = []; // Track duplicate leads in same CSV
        $rowNum      = 1;

        while (($data = fgetcsv($handle)) !== FALSE) {
            $rowNum++;
            if (count($data) !== count($headers)) {
                continue; // Skip malformed rows
            }

            $rowData      = array_combine($headers, array_map('trim', $data));
            $projectName  = $rowData['project_name']  ?? '';
            $flatName     = $rowData['flat_name']      ?? '';
            $loginEmail   = $rowData['login_email']    ?? '';
            $password     = $rowData['password']       ?? '';
            $contactEmail = $rowData['contact_email']  ?? '';

            $error        = null;
            $leadId       = null;
            $leadName     = '';
            $leadPhone    = '';
            $bookingCount = 0;
            $bookingId    = null;

            if (empty($projectName) || empty($flatName) || empty($loginEmail) || empty($password)) {
                $error = "project_name, flat_name, login_email, and password are required.";
            } else {
                // If flat_name has +, comma or / (e.g. "6-A+B", "5A,5B"), use the first flat for booking lookup
                $searchFlat = $flatName;
                if (preg_match('/[\+,\/&]/', $searchFlat)) {
                    $parts = preg_split('/[\+,\/&]/', $searchFlat);
                    $searchFlat = trim($parts[0]);
                }

                // ── Find lead_id via lead_bookings → projects → flats ──────────
                $sql = "SELECT lb.id AS booking_id, lb.lead_id,
                               l.full_name AS lead_name, l.phone AS lead_phone,
                               p.name AS project_name, f.flat_name
                        FROM lead_bookings lb
                        JOIN projects p ON p.id = lb.project_id
                        JOIN flats    f ON f.id = lb.flat_id
                        JOIN leads    l ON l.id = lb.lead_id
                        WHERE p.name = ? AND f.flat_name = ?
                          AND lb.booking_status IN ('booked','completed')
                          AND lb.deleted_at IS NULL
                        LIMIT 1";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("ss", $projectName, $searchFlat);
                $stmt->execute();
                $bookingRow = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                // Fallback lookup: handle hyphen variations, spacing, and project aliases
                if (!$bookingRow) {
                    $cleanFlat = preg_replace('/[^a-zA-Z0-9]/', '', $searchFlat);
                    $projectAliases = [
                        'the premium floral haven' => 'The Premium Floral Heaven',
                        'the premium lakeview terrace' => 'The Premium Lake View Terrace',
                        'the premium studio suits- tpss 4.0' => 'The Premium Studio Suite 4.0',
                        'the premium studio suits- tpss 5.0' => 'The Premium Studio Suite 5.0',
                        'the premium sunleaf garden' => 'THE PREMIUM SUNLEAF GARDEN',
                    ];
                    $cleanProject = $projectAliases[strtolower($projectName)] ?? $projectName;

                    $fallbackSql = "SELECT lb.id AS booking_id, lb.lead_id,
                                           l.full_name AS lead_name, l.phone AS lead_phone,
                                           p.name AS project_name, f.flat_name
                                    FROM lead_bookings lb
                                    JOIN projects p ON p.id = lb.project_id
                                    JOIN flats    f ON f.id = lb.flat_id
                                    JOIN leads    l ON l.id = lb.lead_id
                                    WHERE (p.name = ? OR p.name = ?)
                                      AND (f.flat_name = ? OR REPLACE(REPLACE(f.flat_name, '-', ''), ' ', '') = ?)
                                      AND lb.booking_status IN ('booked','completed')
                                      AND lb.deleted_at IS NULL
                                    LIMIT 1";
                    $fallbackStmt = $this->db->prepare($fallbackSql);
                    $fallbackStmt->bind_param("ssss", $projectName, $cleanProject, $searchFlat, $cleanFlat);
                    $fallbackStmt->execute();
                    $bookingRow = $fallbackStmt->get_result()->fetch_assoc();
                    $fallbackStmt->close();
                }

                if (!$bookingRow) {
                    $error = "No booked/completed booking found for Project: \"$projectName\", Flat: \"$flatName\".";
                } else {
                    $leadId    = $bookingRow['lead_id'];
                    $leadName  = $bookingRow['lead_name'];
                    $leadPhone = $bookingRow['lead_phone'];
                    $bookingId = $bookingRow['booking_id'];

                    // Count total bookings for this lead
                    $bookingCount = $this->leadBookingModel->countBookingsByLeadId($leadId);

                    // Check if already onboarded in database
                    $checkSql  = "SELECT id FROM onboard_clients WHERE lead_id = ?";
                    $checkStmt = $this->db->prepare($checkSql);
                    $checkStmt->bind_param("i", $leadId);
                    $checkStmt->execute();
                    if ($checkStmt->get_result()->fetch_assoc()) {
                        $error = "This lead (\"$leadName\") is already onboarded.";
                    }
                    $checkStmt->close();

                    // Check if already encountered in this same CSV
                    if (!$error) {
                        if (isset($seenLeadIds[$leadId])) {
                            $prevRow = $seenLeadIds[$leadId];
                            $error = "Duplicate Client: Row #$prevRow-এ ইতিমধ্যে অন্তর্ভুক্ত। ক্লায়েন্ট তার ১টি লগইনেই সব ফ্ল্যাট দেখতে পারবেন।";
                        } else {
                            $seenLeadIds[$leadId] = $rowNum;
                        }
                    }
                }
            }

            $previewRow = [
                'row_num'       => $rowNum,
                'project_name'  => $projectName,
                'flat_name'     => $flatName,
                'lead_id'       => $leadId,
                'lead_name'     => $leadName,
                'lead_phone'    => $leadPhone,
                'booking_id'    => $bookingId,
                'booking_count' => $bookingCount,
                'login_email'   => $loginEmail,
                'contact_email' => $contactEmail,
                'password'      => $password,
                'error'         => $error,
            ];

            $previewData[] = $previewRow;
            if (!$error) {
                $validData[] = $previewRow;
            }
        }

        fclose($handle);

        $this->view("sales-developments/accessOnboardClient/bulk-preview", [
            'previewData' => $previewData,
            'validData'   => $validData,
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

        $createdBy    = $_SESSION['user']['id'] ?? null;
        $successCount = 0;

        // Load static user.json if available to auto-populate existing links (video, sheets, drive)
        $staticUsers = [];
        $userJsonPaths = [
            dirname(__DIR__, 4) . '/premiumbackend/storage/app/json/user.json',
            'C:/xampp/htdocs/premiumbackend/storage/app/json/user.json',
        ];
        foreach ($userJsonPaths as $ujPath) {
            if (file_exists($ujPath)) {
                $jsonRaw = @file_get_contents($ujPath);
                $decoded = json_decode($jsonRaw, true);
                if (!empty($decoded['users'])) {
                    $staticUsers = $decoded['users'];
                    break;
                }
            }
        }

        foreach ($validData as $row) {
            $leadId     = $row['lead_id'];
            $email      = $row['contact_email'];
            $loginEmail = $row['login_email'];
            $password   = $row['password'];

            // Auto-populate links from user.json if matched, otherwise null
            $links = [
                'cameraLink1'        => null,
                'cameraLink2'        => null,
                'moneyReceiptLink'   => null,
                'purchaseDetailsLink'=> null,
            ];

            if (!empty($staticUsers)) {
                $cleanLoginEmail = strtolower(trim($loginEmail));
                $cleanRowPhone = preg_replace('/[^0-9]/', '', $row['lead_phone'] ?? '');

                foreach ($staticUsers as $su) {
                    $match = false;
                    if ($cleanLoginEmail && strtolower(trim($su['email'] ?? '')) === $cleanLoginEmail) {
                        $match = true;
                    }
                    if (!$match && strlen($cleanRowPhone) >= 8) {
                        $suPhone = preg_replace('/[^0-9]/', '', $su['phone'] ?? '');
                        if ($suPhone && (str_ends_with($suPhone, substr($cleanRowPhone, -8)) || str_ends_with($cleanRowPhone, substr($suPhone, -8)))) {
                            $match = true;
                        }
                    }

                    if ($match && !empty($su['savedProperties'][0])) {
                        $sp = $su['savedProperties'][0];
                        if(empty($links['cameraLink1'])) $links['cameraLink1'] = !empty($sp['videoLink']) ? $sp['videoLink'] : null;
                        if(empty($links['cameraLink2'])) $links['cameraLink2'] = !empty($sp['videoLink2']) ? $sp['videoLink2'] : null;
                        if(empty($links['moneyReceiptLink'])) $links['moneyReceiptLink'] = !empty($sp['moneyReceiptLink']) ? $sp['moneyReceiptLink'] : null;
                        if(empty($links['purchaseDetailsLink'])) $links['purchaseDetailsLink'] = !empty($sp['purchaseDetailsLink']) ? $sp['purchaseDetailsLink'] : null;
                        break;
                    }
                }
            }

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
