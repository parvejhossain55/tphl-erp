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

        // Save where the user came from so we can redirect back after creating a follow-up
        // Only save if referer is NOT the follow-up page itself (avoids overwriting on page refresh)
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $followUpUrl = $GLOBALS['base_url'] . '/follow-up-leads';
        if (!empty($referer) && strpos($referer, $followUpUrl) === false) {
            $_SESSION['follow_up_return_url'] = $referer;
        }

        $leads = [];
        if ($lead_id) {
            $singleLead = $this->leadModel->find($lead_id);
            if ($singleLead) {
                $leads[] = $singleLead;
            }
        } else {
            $leads = $this->leadModel->getAll(); // fallback
        }
        $followUpLeads = [];

        if ($lead_id) {
            $followUpLeads = $this->followUpModel->getByLead($lead_id);
        }
        $assignedUsers = $this->userModel->getAllRoleUser();
        $projects = $this->projectModel->getAll(); // optional
        $flats = [];       // Flat options are loaded dynamically via AJAX in getFlats()

        // Fetch teams for re-assignment
        $teamModel = new Team($this->db);
        $teams = $teamModel->getAll();

        // Follow-up Agent dropdown: always show only same-team users
        // Uses the logged-in user's own team_id from session
        $teamId = $_SESSION['user']['team_id'] ?? null;

        if (!empty($teamId)) {
            $teamMembers = $this->userModel->getUsersByTeamAndRole($teamId);
        } else {
            $teamMembers = []; // no team assigned → empty
        }

        $this->view("sales-developments/followUp-leads/followUpLeads", [
            "current_route" => "follow-up-leads",
            "page_title" => "Follow Up Lead List",
            "followUpLeads" => $followUpLeads,
            "projects" => $projects,
            "flats" => $flats,
            "leads" => $leads,
            "selected_lead_id" => $lead_id,
            "assignedUsers" => $assignedUsers,
            "teams" => $teams,
            "teamMembers" => $teamMembers,
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
        'follow_up_agent_id' => !empty($_POST['follow_up_agent_id']) ? (int)$_POST['follow_up_agent_id'] : null,
        'lead_status' => $_POST['lead_status'] ?? null,
        'next_follow_up_plan_notes' => $_POST['next_follow_up_plan_notes'] ?? null,
    ];

    // Handle Team Re-assignment
    if ($followUpData['lead_status'] === 'Lead Transferred' && !empty($_POST['team_id'])) {
        $team_id = (int)$_POST['team_id'];
        $lead_id = $followUpData['lead_id'];
        
        $updateTeam = $this->db->prepare("UPDATE leads SET team_id = ?, assigned_id = NULL WHERE id = ?");
        if ($updateTeam) {
            $updateTeam->bind_param("ii", $team_id, $lead_id);
            $updateTeam->execute();
        }
    }

    // Update Lead Type
    if (!empty($followUpData['lead_status'])) {
        $lead_status = $followUpData['lead_status'];
        $lead_id = $followUpData['lead_id'];
        $updateLeadType = $this->db->prepare("UPDATE leads SET lead_type = ? WHERE id = ?");
        if ($updateLeadType) {
            $updateLeadType->bind_param("si", $lead_status, $lead_id);
            $updateLeadType->execute();
        }
    }

    // Create follow-up
    $created = $this->followUpModel->create($followUpData);

    $leadId = $_POST['lead_id'];
    if ($created) {
        setSuccess("Follow-up created successfully!");
    } else {
        $dbError = $_SESSION['flash_error'] ?? '';
        if (!empty($dbError)) {
            setError("Failed to create follow-up! DB Error: " . $dbError);
        } else {
            setError("Failed to create follow-up!");
        }
    }

    // Redirect back to the page the user originally came from
    // (stored in session when follow-up page was loaded)
    if (!empty($_SESSION['follow_up_return_url'])) {
        $returnUrl = $_SESSION['follow_up_return_url'];
        unset($_SESSION['follow_up_return_url']); // clear after use
        
        // Remove any existing hash first
        $hashPos = strpos($returnUrl, '#');
        if ($hashPos !== false) {
            $returnUrl = substr($returnUrl, 0, $hashPos);
        }
        
        $returnUrl .= '#lead-' . (int)$leadId;
        header("Location: " . $returnUrl);
        exit;
    }
    // Fallback: go to follow-up-leads page
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
            'follow_up_agent_id' => !empty($_POST['follow_up_agent_id']) ? (int)$_POST['follow_up_agent_id'] : null,
            'lead_status' => $_POST['lead_status'] ?? null,
            'next_follow_up_plan_notes' => $_POST['next_follow_up_plan_notes'] ?? null,
        ];

        // Update Lead Type if provided
        if (!empty($followUpData['lead_status'])) {
            $lead_status = $followUpData['lead_status'];
            $lead_id = $followUpData['lead_id'];
            $updateLeadType = $this->db->prepare("UPDATE leads SET lead_type = ? WHERE id = ?");
            if ($updateLeadType) {
                $updateLeadType->bind_param("si", $lead_status, $lead_id);
                $updateLeadType->execute();
            }
        }


        if ($this->followUpModel->update($followUpData)) {
            setSuccess("Follow-up updated successfully!");
        } else {
            $dbError = $_SESSION['flash_error'] ?? '';
            if (!empty($dbError)) {
                setError("Failed to update follow-up! DB Error: " . $dbError);
            } else {
                setError("Failed to update follow-up!");
            }
        }
        $leadId = $_POST['lead_id'];
        header("Location: {$GLOBALS['base_url']}/follow-up-leads/{$leadId}");
        exit;
    }



    // Delete Employee
    public function delete($id) {}
}
