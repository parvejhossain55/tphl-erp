<?php
class FollowUpController extends Controller
{
    protected $db;
    protected $userModel;
    protected $leadModel;
    protected $followUpModel;
    protected $projectModel;
    protected $flatModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->userModel = new User($this->db);
        $this->leadModel = new Lead($this->db);

        $this->followUpModel = new FollowUp($this->db);
        $this->projectModel = new Project($this->db);
        $this->flatModel = new FlatModel($this->db);
    }

    // Index page: show leads for follow-up
    public function index($lead_id = null)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $leads = $this->leadModel->getAll(); // for the table/list
        $followUpLeads = [];

        if ($lead_id) {
            $followUpLeads = $this->followUpModel->getByLead($lead_id);
        }
        $assignedUsers = $this->userModel->getAllRoleUser();
        $projects = $this->projectModel->getAll(); // optional
        $flats = $this->flatModel->getAll();       // optional

        $this->view("sales-developments/followUp-leads/followUpLeads", [
            "current_route" => "follow-up-leads",
            "page_title" => "Follow Up Lead List",
            "followUpLeads" => $followUpLeads,
            "projects" => $projects,
            "flats" => $flats,
            "leads" => $leads,
            "selected_lead_id" => $lead_id,
            "assignedUsers" => $assignedUsers,
        ]);

        // print_r($followUpLeads);
    }


    public function getFlats($projectId)
    {
        // Disable any output before JSON
        if (ob_get_length()) ob_clean();  // clear any accidental output

        $flats = $this->flatModel->getFlatsByProject($projectId);
        header('Content-Type: application/json');
        echo json_encode($flats);
        exit;  // make sure nothing else is appended
    }

    // Store new follow-up
public function store()
{
    if (session_status() === PHP_SESSION_NONE) session_start();

    // Handle optional flat_id
    $flat_id = $_POST['flat_id'] ?? null;
    if (empty($flat_id)) {
        $flat_id = null;
    } else {
        $flat_id = (int) $flat_id;
    }

    // Validate next_action against allowed ENUM values
    $allowedNextActions = ['site visit', 'booking', 'call_back', 'take_time'];
    $next_action = $_POST['next_action'] ?? null;
    if (!in_array($next_action, $allowedNextActions)) {
        $next_action = null; // fallback if invalid
    }

    $followUpData = [
        'lead_id' => (int) $_POST['lead_id'],
        'project_id' => !empty($_POST['project_id']) ? (int) $_POST['project_id'] : null,
        'flat_id' => $flat_id,
        'follow_up_date' => $_POST['follow_up_date'] ?? null,
        'follow_up_time' => $_POST['follow_up_time'] ?? null,
        'follow_up_type' => $_POST['follow_up_type'] ?? 'call',
        'status' => $_POST['status'] ?? 'pending',
        'discussion_note' => $_POST['discussion_note'] ?? null,
        'next_follow_up_date' => $_POST['next_follow_up_date'] ?? null,
        'next_action' => $next_action,
        'assigned_with' => !empty($_POST['assigned_with']) ? (int)$_POST['assigned_with'] : null,
        'created_by' => $_SESSION['user']['id'] ?? null,
    ];

    // Create follow-up
    $created = $this->followUpModel->create($followUpData);

    $leadId = $_POST['lead_id'];
    if ($created) {
        setSuccess("Follow-up created successfully!");
    } else {
        setError("Failed to create follow-up!");
    }
    header("Location: {$GLOBALS['base_url']}/follow-up-leads/{$leadId}");
    exit;
}

    public function update()
    {
        $flatId = $_POST['flat_id'] ?? null;
        $flatId = ($flatId === '' || $flatId === '0') ? null : (int)$flatId;

        $followUpData = [
            'id' => (int)$_POST['id'],
            'lead_id' => (int)$_POST['lead_id'],
            'flat_id' => $flatId,   // ✅ NULL or flats.id only
            'follow_up_date' => $_POST['follow_up_date'],
            'follow_up_time' => $_POST['follow_up_time'] ?: null,
            'follow_up_type' => $_POST['follow_up_type'],
            'status' => $_POST['status'] ?? 'pending',
            'discussion_note' => $_POST['discussion_note'] ?? null,
            'next_follow_up_date' => $_POST['next_follow_up_date'] ?: null,
            'next_action' => $_POST['next_action'] ?? null,

        ];


        if ($this->followUpModel->update($followUpData)) {
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
