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
            "page_title" => "Lead List",
            "owners" => $owners,
            "onboardAccessClients" => $onboardAccessClients,
            "message" => $message,

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
    public function store()
    {
        $leadBookingId = $_POST['lead_booking_id'] ?? 0;
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $created_by = $_SESSION['user']['id'] ?? null;

        // Optional links
        $links = [
            'cameraLink1' => $_POST['cameraLink1'] ?? null,
            'cameraLink2' => $_POST['cameraLink2'] ?? null,
            'moneyReceiptLink' => $_POST['moneyReceiptLink'] ?? null,
            'purchaseDetailsLink' => $_POST['purchaseDetailsLink'] ?? null
        ];

        // Create onboard client
        $newId = $this->onboardClient->create($leadBookingId, $email, $password, $links, $created_by);

        if ($newId) {
            setSuccess("Client access data created successfully");
        } else {
            setError("Failed to create onboard client");
        }

        // Redirect back to onboard client list
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
        'password'           => $_POST['password'] ?? null, // optional
        'cameraLink1'        => $_POST['cameraLink1'] ?? null,
        'cameraLink2'        => $_POST['cameraLink2'] ?? null,
        'moneyReceiptLink'   => $_POST['moneyReceiptLink'] ?? null,
        'purchaseDetailsLink'=> $_POST['purchaseDetailsLink'] ?? null,
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

}
