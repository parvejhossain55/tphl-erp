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
            "transferableUsers" => $this->userModel->getSalesTransferableUsers(),
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
        'follow_up_type' => !empty($_POST['follow_up_type']) ? $_POST['follow_up_type'] : null,
        'status' => $_POST['status'] ?? 'pending',
        'discussion_note' => $_POST['discussion_note'] ?? null,
        'next_follow_up_date' => $_POST['next_follow_up_date'] ?? null,
        'next_action' => $next_action,
        'assigned_with' => !empty($_POST['assigned_with']) ? (int)$_POST['assigned_with'] : null,
        'created_by' => $_SESSION['user']['id'] ?? null,
        'follow_up_agent_id' => !empty($_POST['follow_up_agent_id']) ? (int)$_POST['follow_up_agent_id'] : null,
        'lead_status' => $_POST['lead_status'] ?? null,
        'interested_location_name' => !empty($_POST['interested_location_name']) ? trim($_POST['interested_location_name']) : null,
        'next_follow_up_plan_notes' => $_POST['next_follow_up_plan_notes'] ?? null,
    ];
    if (($followUpData['lead_status'] ?? '') !== 'Interested in Other Location') {
        $followUpData['interested_location_name'] = null;
    }

    // Validation: If lead_status is 'No Response', next_follow_up_date is mandatory up to 4 attempts
    $leadId = (int)$followUpData['lead_id'];
    $leadStatus = trim($followUpData['lead_status'] ?? '');
    $nextFollowUpDate = !empty($followUpData['next_follow_up_date']) ? trim($followUpData['next_follow_up_date']) : null;
    $isAjax = !empty($_POST['is_ajax']);

    if (strcasecmp($leadStatus, 'No Response') === 0) {
        $nrCount = $this->followUpModel->getNoResponseCountByLead($leadId);
        if ($nrCount < 4 && empty($nextFollowUpDate)) {
            $attemptNo = $nrCount + 1;
            $msg = "Next Follow Up Date is mandatory for 'No Response' (Attempt {$attemptNo} of 4)!";
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => $msg]);
                exit;
            }
            setError($msg);
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "{$GLOBALS['base_url']}/follow-up-leads/{$leadId}"));
            exit;
        }
    }

    $currentUserId = $_SESSION['user']['id'] ?? 0;

    // Handle User-level Transfer (transfer_to_user_id takes priority)
    if ($followUpData['lead_status'] === 'Lead Transferred' && !empty($_POST['transfer_to_user_id'])) {
        $transfer_to_user_id = (int)$_POST['transfer_to_user_id'];
        $lead_id = $followUpData['lead_id'];

        // Get previous lead assignment details
        $prevLeadStmt = $this->db->prepare("SELECT assigned_id, team_id FROM leads WHERE id = ? LIMIT 1");
        $prevLeadStmt->bind_param("i", $lead_id);
        $prevLeadStmt->execute();
        $prevLeadData = $prevLeadStmt->get_result()->fetch_assoc();
        $prevLeadStmt->close();
        $oldAssignedId = !empty($prevLeadData['assigned_id']) ? (int)$prevLeadData['assigned_id'] : null;
        $oldTeamId     = !empty($prevLeadData['team_id']) ? (int)$prevLeadData['team_id'] : null;

        // Look up the target user's team_id
        $userStmt = $this->db->prepare("SELECT team_id FROM users WHERE id = ? LIMIT 1");
        $newTeamId = null;
        if ($userStmt) {
            $userStmt->bind_param("i", $transfer_to_user_id);
            $userStmt->execute();
            $userResult = $userStmt->get_result()->fetch_assoc();
            $newTeamId = $userResult['team_id'] ?? null;
            $userStmt->close();
        }

        if ($newTeamId) {
            $updateLead = $this->db->prepare("UPDATE leads SET assigned_id = ?, assigned_by = ?, assigned_at = CURRENT_TIMESTAMP, team_id = ?, initial_team_id = COALESCE(initial_team_id, ?) WHERE id = ?");
            if ($updateLead) {
                $updateLead->bind_param("iiiii", $transfer_to_user_id, $currentUserId, $newTeamId, $newTeamId, $lead_id);
                $updateLead->execute();
                $updateLead->close();
            }
        } else {
            $updateLead = $this->db->prepare("UPDATE leads SET assigned_id = ?, assigned_by = ?, assigned_at = CURRENT_TIMESTAMP WHERE id = ?");
            if ($updateLead) {
                $updateLead->bind_param("iii", $transfer_to_user_id, $currentUserId, $lead_id);
                $updateLead->execute();
                $updateLead->close();
            }
        }

        // Log transfer history
        $histStmt = $this->db->prepare("
            INSERT INTO lead_assignment_history 
            (lead_id, from_user_id, to_user_id, from_team_id, to_team_id, assigned_by, assigned_at, action_type, notes)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), 'transferred', 'Transferred during follow-up')
        ");
        if ($histStmt) {
            $tTeam = $newTeamId ? (int)$newTeamId : $oldTeamId;
            $histStmt->bind_param("iiiiii", $lead_id, $oldAssignedId, $transfer_to_user_id, $oldTeamId, $tTeam, $currentUserId);
            $histStmt->execute();
            $histStmt->close();
        }

        Notification::notifyLeadAssignment(
            $this->db,
            'lead_reassigned',
            [$transfer_to_user_id],
            (int)$currentUserId,
            [(int)$lead_id],
            [
                'team_id'       => (int)($newTeamId ?: $oldTeamId),
                'assigned_date' => date('Y-m-d'),
            ]
        );
    }
    // Handle Team Re-assignment (team-level, when no specific user selected)
    elseif ($followUpData['lead_status'] === 'Lead Transferred' && !empty($_POST['team_id'])) {
        $team_id = (int)$_POST['team_id'];
        $lead_id = $followUpData['lead_id'];

        $prevLeadStmt = $this->db->prepare("SELECT assigned_id, team_id FROM leads WHERE id = ? LIMIT 1");
        $prevLeadStmt->bind_param("i", $lead_id);
        $prevLeadStmt->execute();
        $prevLeadData = $prevLeadStmt->get_result()->fetch_assoc();
        $prevLeadStmt->close();
        $oldAssignedId = !empty($prevLeadData['assigned_id']) ? (int)$prevLeadData['assigned_id'] : null;
        $oldTeamId     = !empty($prevLeadData['team_id']) ? (int)$prevLeadData['team_id'] : null;
        
        $updateTeam = $this->db->prepare("UPDATE leads SET team_id = ?, initial_team_id = COALESCE(initial_team_id, ?), assigned_id = NULL, assigned_by = ? WHERE id = ?");
        if ($updateTeam) {
            $updateTeam->bind_param("iiii", $team_id, $team_id, $currentUserId, $lead_id);
            $updateTeam->execute();
            $updateTeam->close();
        }

        $histStmt = $this->db->prepare("
            INSERT INTO lead_assignment_history 
            (lead_id, from_user_id, to_user_id, from_team_id, to_team_id, assigned_by, assigned_at, action_type, notes)
            VALUES (?, ?, 0, ?, ?, ?, NOW(), 'transferred', 'Transferred to team pool during follow-up')
        ");
        if ($histStmt) {
            $histStmt->bind_param("iiiii", $lead_id, $oldAssignedId, $oldTeamId, $team_id, $currentUserId);
            $histStmt->execute();
            $histStmt->close();
        }

        $leaderIds = Notification::getTeamLeaderUserIds($this->db, $team_id);
        if (!empty($leaderIds)) {
            Notification::notifyLeadAssignment(
                $this->db,
                'lead_team_assigned',
                $leaderIds,
                (int)$currentUserId,
                [(int)$lead_id],
                [
                    'team_id'       => $team_id,
                    'assigned_date' => date('Y-m-d'),
                ]
            );
        }
    }

    // Update Lead Type and optional drop reason
    if (!empty($followUpData['lead_status'])) {
        $lead_status = $followUpData['lead_status'];
        $lead_id = $followUpData['lead_id'];
        $dropReason = !empty($_POST['drop_reason']) ? trim($_POST['drop_reason']) : null;
        $dropNote   = !empty($_POST['drop_note']) ? trim($_POST['drop_note']) : null;

        if ($lead_status === 'Interested in Other Location') {
            $locName = $followUpData['interested_location_name'];
            $updateLeadType = $this->db->prepare("UPDATE leads SET lead_type = ?, interested_location_name = ? WHERE id = ?");
            if ($updateLeadType) {
                $updateLeadType->bind_param("ssi", $lead_status, $locName, $lead_id);
                $updateLeadType->execute();
                $updateLeadType->close();
            }
        } elseif ($dropReason !== null || $dropNote !== null) {
            $updateLeadType = $this->db->prepare("UPDATE leads SET lead_type = ?, drop_reason = ?, drop_note = ? WHERE id = ?");
            if ($updateLeadType) {
                $updateLeadType->bind_param("sssi", $lead_status, $dropReason, $dropNote, $lead_id);
                $updateLeadType->execute();
                $updateLeadType->close();
            }
        } else {
            $updateLeadType = $this->db->prepare("UPDATE leads SET lead_type = ? WHERE id = ?");
            if ($updateLeadType) {
                $updateLeadType->bind_param("si", $lead_status, $lead_id);
                $updateLeadType->execute();
                $updateLeadType->close();
            }
        }
    }

    // Create follow-up
    $created = $this->followUpModel->create($followUpData);

    $leadId = (int)$_POST['lead_id'];
    $isAjax = !empty($_POST['is_ajax']);

    if ($created) {
        // Auto-set first_contacted_at on lead if not already set
        $touchStmt = $this->db->prepare("UPDATE leads SET first_contacted_at = COALESCE(first_contacted_at, NOW()) WHERE id = ?");
        if ($touchStmt) {
            $touchStmt->bind_param("i", $leadId);
            $touchStmt->execute();
            $touchStmt->close();
        }

        // If follow-up status is updated to an active status (not No Response), clear any stale not_received call_status
        if (!empty($followUpData['lead_status']) && !in_array($followUpData['lead_status'], ['No Response', 'Invalid Lead'])) {
            $callUpd = $this->db->prepare("UPDATE leads SET call_status = 'received' WHERE id = ? AND call_status = 'not_received'");
            if ($callUpd) {
                $callUpd->bind_param("i", $leadId);
                $callUpd->execute();
                $callUpd->close();
            }
        }

        setSuccess("Follow-up created successfully!");
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Follow-up created successfully!']);
            exit;
        }
    } else {
        $dbError = $_SESSION['flash_error'] ?? '';
        $errorMsg = !empty($dbError) ? "Failed to create follow-up! DB Error: " . $dbError : "Failed to create follow-up!";
        setError($errorMsg);
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $errorMsg]);
            exit;
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
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAjax = !empty($_POST['is_ajax']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        $flatId = $_POST['flat_id'] ?? null;
        $flatId = ($flatId === '' || $flatId === '0') ? null : (int)$flatId;

        $projectId = $_POST['project_id'] ?? null;
        $projectId = ($projectId === '' || $projectId === '0') ? null : (int)$projectId;

        $followUpId = (int)($_POST['id'] ?? 0);
        $leadId = (int)($_POST['lead_id'] ?? 0);

        // Security check: Only creator or Admin (role_id 1) can edit
        $currentUserId = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? null;
        $currentRole = $_SESSION['user']['role_id'] ?? null;

        $existing = $this->followUpModel->getById($followUpId);
        if (!$existing) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Follow-up not found!']);
                exit;
            }
            setError('Follow-up not found!');
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "{$GLOBALS['base_url']}/my-assigned-leads"));
            exit;
        }

        if ($currentRole != 1 && (int)$existing['created_by'] !== (int)$currentUserId) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'You can only edit follow-ups created by you!']);
                exit;
            }
            setError('You can only edit follow-ups created by you!');
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "{$GLOBALS['base_url']}/my-assigned-leads"));
            exit;
        }

        $allowedNextActions = ['site visit', 'booking', 'call_back', 'take_time'];
        $next_action = $_POST['next_action'] ?? null;
        if (!in_array($next_action, $allowedNextActions)) {
            $next_action = null;
        }

        $followUpData = [
            'id' => $followUpId,
            'lead_id' => $leadId,
            'project_id' => $projectId,
            'flat_id' => $flatId,   // ✅ NULL or flats.id only
            'follow_up_date' => $_POST['follow_up_date'] ?? date('Y-m-d'),
            'follow_up_time' => !empty($_POST['follow_up_time']) ? $_POST['follow_up_time'] : null,
            'follow_up_type' => !empty($_POST['follow_up_type']) ? $_POST['follow_up_type'] : null,
            'status' => $_POST['status'] ?? 'pending',
            'discussion_note' => $_POST['discussion_note'] ?? null,
            'next_follow_up_date' => $_POST['next_follow_up_date'] ?: null,
            'next_action' => $next_action,
            'follow_up_agent_id' => !empty($_POST['follow_up_agent_id']) ? (int)$_POST['follow_up_agent_id'] : null,
            'lead_status' => $_POST['lead_status'] ?? null,
            'interested_location_name' => !empty($_POST['interested_location_name']) ? trim($_POST['interested_location_name']) : null,
            'next_follow_up_plan_notes' => $_POST['next_follow_up_plan_notes'] ?? null,
        ];
        if (($followUpData['lead_status'] ?? '') !== 'Interested in Other Location') {
            $followUpData['interested_location_name'] = null;
        }

        // Validation: If lead_status is 'No Response', next_follow_up_date is mandatory up to 4 attempts
        $leadStatus = trim($followUpData['lead_status'] ?? '');
        $nextFollowUpDate = !empty($followUpData['next_follow_up_date']) ? trim($followUpData['next_follow_up_date']) : null;
        if (strcasecmp($leadStatus, 'No Response') === 0) {
            $nrCount = $this->followUpModel->getNoResponseCountByLead($leadId, $followUpId);
            if ($nrCount < 4 && empty($nextFollowUpDate)) {
                $attemptNo = $nrCount + 1;
                $msg = "Next Follow Up Date is mandatory for 'No Response' (Attempt {$attemptNo} of 4)!";
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'error', 'message' => $msg]);
                    exit;
                }
                setError($msg);
                header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "{$GLOBALS['base_url']}/my-assigned-leads"));
                exit;
            }
        }

        // Update Lead Transferred if provided
        if (($followUpData['lead_status'] ?? '') === 'Lead Transferred' && !empty($_POST['team_id'])) {
            $team_id = (int)$_POST['team_id'];
            $updateTeam = $this->db->prepare("UPDATE leads SET team_id = ?, initial_team_id = COALESCE(initial_team_id, ?) WHERE id = ?");
            if ($updateTeam) {
                $updateTeam->bind_param("iii", $team_id, $team_id, $leadId);
                $updateTeam->execute();
                $leaderIds = Notification::getTeamLeaderUserIds($this->db, $team_id);
                if (!empty($leaderIds)) {
                    Notification::notifyLeadAssignment(
                        $this->db,
                        'lead_team_assigned',
                        $leaderIds,
                        (int)($_SESSION['user']['id'] ?? 0),
                        [(int)$leadId],
                        [
                            'team_id'       => $team_id,
                            'assigned_date' => date('Y-m-d'),
                        ]
                    );
                }
            }
        }

        // Update Lead Type if provided
        if (!empty($followUpData['lead_status'])) {
            $lead_status = $followUpData['lead_status'];
            if ($lead_status === 'Interested in Other Location') {
                $locName = $followUpData['interested_location_name'];
                $updateLeadType = $this->db->prepare("UPDATE leads SET lead_type = ?, interested_location_name = ? WHERE id = ?");
                if ($updateLeadType) {
                    $updateLeadType->bind_param("ssi", $lead_status, $locName, $leadId);
                    $updateLeadType->execute();
                    $updateLeadType->close();
                }
            } else {
                $updateLeadType = $this->db->prepare("UPDATE leads SET lead_type = ? WHERE id = ?");
                if ($updateLeadType) {
                    $updateLeadType->bind_param("si", $lead_status, $leadId);
                    $updateLeadType->execute();
                    $updateLeadType->close();
                }
            }
        }

        $updated = $this->followUpModel->update($followUpData);

        if ($updated) {
            // If follow-up status is updated to an active status (not No Response), clear any stale not_received call_status
            if (!empty($followUpData['lead_status']) && !in_array($followUpData['lead_status'], ['No Response', 'Invalid Lead'])) {
                $callUpd = $this->db->prepare("UPDATE leads SET call_status = 'received' WHERE id = ? AND call_status = 'not_received'");
                if ($callUpd) {
                    $callUpd->bind_param("i", $leadId);
                    $callUpd->execute();
                    $callUpd->close();
                }
            }

            setSuccess("Follow-up updated successfully!");
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success', 'message' => 'Follow-up updated successfully!']);
                exit;
            }
        } else {
            $dbError = $_SESSION['flash_error'] ?? '';
            $errorMsg = !empty($dbError) ? "Failed to update follow-up! DB Error: " . $dbError : "Failed to update follow-up!";
            setError($errorMsg);
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => $errorMsg]);
                exit;
            }
        }

        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "{$GLOBALS['base_url']}/follow-up-leads/{$leadId}"));
        exit;
    }


    public function getFollowUpHistoryAjax($leadId)
    {
        if (ob_get_length()) ob_clean();
        
        $sql = "SELECT f.*, p.name as project_name, fl.flat_name, u.full_name as agent_name, l.full_name as lead_name
                FROM lead_follow_ups f
                LEFT JOIN projects p ON f.project_id = p.id
                LEFT JOIN flats fl ON f.flat_id = fl.id
                LEFT JOIN users u ON f.created_by = u.id
                LEFT JOIN leads l ON f.lead_id = l.id
                WHERE f.lead_id = ? AND f.deleted_at IS NULL
                ORDER BY f.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $leadId);
        $stmt->execute();
        $result = $stmt->get_result();
        $followUps = $result->fetch_all(MYSQLI_ASSOC);
        
        header('Content-Type: application/json');
        
        $json = json_encode($followUps, JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            echo json_encode(['error' => 'JSON encoding failed: ' . json_last_error_msg()]);
        } else {
            echo $json;
        }
        exit;
    }

    // Delete Employee
    public function delete($id) {}
}
