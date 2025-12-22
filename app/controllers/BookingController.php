<?php
class BookingController extends Controller
{
    protected $db;
    protected $userModel;
    protected $leadModel;
    protected $bookingModel;
    protected $projectModel;
    protected $flatModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->userModel = new User($this->db);
        $this->leadModel = new Lead($this->db);

        $this->bookingModel = new BookingLead($this->db);
        $this->projectModel = new Project($this->db);
        $this->flatModel = new FlatModel($this->db);
    }

    // Index page: show leads for follow-up
public function index($lead_id = null)
{
    if (session_status() === PHP_SESSION_NONE) session_start();

    if ($lead_id) {
     
        $lead = $this->leadModel->find($lead_id);

        // Optional: booking records for this lead
        $bookingRecords = $this->bookingModel->getByLead($lead_id);
    } else {
        $lead = null;
        $bookingRecords = [];
    }

    $projects = $this->projectModel->getAll();
    $flats = []; // empty initially

    $this->view("booking-leads/bookingLeads", [
        "lead" => $lead,
        "projects" => $projects,
        "flats" => $flats,
        "bookingRecords" => $bookingRecords,
        "selected_lead_id" => $lead_id,
        "current_route" => "booking-leads",
        "page_title" => "Booking Lead Page",
    ]);
}




    // AJAX: Get flats by project
    public function getFlatsByProject($projectId)
    {
        if (ob_get_length()) ob_clean(); // clear accidental output

        $flats = $this->flatModel->getFlatsWithUnitByProject($projectId);

        header('Content-Type: application/json');
        echo json_encode($flats);
        exit;
    }
    // Store new follow-up
 public function store()
{
    if (session_status() === PHP_SESSION_NONE) session_start();

    $data = [
        'lead_id' => $_POST['lead_id'],
        'project_id' => $_POST['project_id'],
        'flat_id' => $_POST['flat_id'],
        'created_by' => $_SESSION['user']['id'] ?? 1,
        'agreement_value' => $_POST['agreement_value'],
        'booking_date' => $_POST['booking_date'],
        'booking_status' => $_POST['booking_status'] ?? 'active',
    ];

    $created = $this->bookingModel->create($data);

    if ($created) {
        $leadId = $_POST['lead_id'];
        setSuccess("Lead onboarded successfully!");
        header("Location: {$GLOBALS['base_url']}/booking-leads/{$leadId}");
        exit;
    } else {
        setError("Failed to onboard lead!");
          $leadId = $_POST['lead_id'];
        header("Location: {$GLOBALS['base_url']}/booking-leads/{$leadId}");
        exit;
    }
}
    public function update()
    {
        $flatId = $_POST['flat_id'] ?? null;
        $flatId = ($flatId === '' || $flatId === '0') ? null : (int)$flatId;

        $followUpData = [
            'id' => (int)$_POST['id'],
            'lead_id' => (int)$_POST['lead_id'],
            'flat_id' => $flatId,   //  NULL or flats.id only
            'follow_up_date' => $_POST['follow_up_date'],
            'follow_up_time' => $_POST['follow_up_time'] ?: null,
            'follow_up_type' => $_POST['follow_up_type'],
            'status' => $_POST['status'] ?? 'pending',
            'discussion_note' => $_POST['discussion_note'] ?? null,
            'next_follow_up_date' => $_POST['next_follow_up_date'] ?: null,
            'next_action' => $_POST['next_action'] ?? null,
        ];


        if ($this->bookingModel->update($followUpData)) {
            setSuccess("Follow-up updated successfully!");
        } else {
            setError("Failed to update follow-up!");
        }
        $leadId = $_POST['lead_id'];
        header("Location: {$GLOBALS['base_url']}/follow-up-leads/{$leadId}");
        exit;
    }



    // Delete Employee
    public function delete($id) {}
}
