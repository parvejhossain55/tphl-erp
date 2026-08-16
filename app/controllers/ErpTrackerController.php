<?php
require_once __DIR__ . '/../models/ErpTracker.php';
require_once __DIR__ . '/../core/TrackerMailService.php';
require_once __DIR__ . '/../models/User.php';

class ErpTrackerController extends Controller
{
    private $tracker;
    private $db;
    private $mailService;

    public function __construct()
    {
        global $db;
        $this->db = $db;
        $this->tracker = new ErpTracker($db);
        $this->mailService = new TrackerMailService($db);
    }

    private function checkErpAccess($requireManagement = false)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user']['id'])) {
            header("Location: {$GLOBALS['base_url']}/admin-login");
            exit;
        }

        $user_id = $_SESSION['user']['id'];
        $role_id = $_SESSION['user']['role_id'];
        
        // 1 = Super Admin, 3 = Management/Admin, 33 = Chief Officer
        $isManagement = in_array($role_id, [1, 3, 33]);

        if ($requireManagement && !$isManagement) {
            die("Access Denied. Management only.");
        }

        if (!$isManagement) {
            $stmt = $this->db->prepare("SELECT id FROM erp_tracker_champions WHERE user_id = ? LIMIT 1");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                die("Access Denied. You are not a designated ERP Champion.");
            }
        }
    }

    public function dashboard()
    {
        $this->checkErpAccess();

        $user_id = $_SESSION['user']['id'];
        $role_id = $_SESSION['user']['role_id'];
        
        $it_role = $this->getRoleId('IT Team');
        
        $hasAccess = in_array($role_id, [1, 3, 33, $it_role]);

        if (isset($_GET['session_id'])) {
            $activeSession = $this->tracker->getSession($_GET['session_id']);
        } else {
            $activeSession = $this->tracker->getActiveSession();
        }
        $stats = [];
        $rankings = [];
        if ($activeSession) {
            $stats = $this->tracker->getDashboardStats($activeSession['id']);
            $rankings = $this->tracker->getDepartmentRankings($activeSession['id']);
        }
        
        $allSessions = $this->tracker->getAllSessions();

        $data = [
            'activeSession' => $activeSession,
            'stats' => $stats,
            'rankings' => $rankings,
            'allSessions' => $allSessions,
            'hasManagementAccess' => $hasAccess,
            'role_id' => $role_id,
            'it_role' => $it_role
        ];

        $this->view('erp-tracker/dashboard', $data);
    }

    public function createSession()
    {
        $this->checkErpAccess(true); // Management only
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $meeting_date = $_POST['meeting_date'] ?? date('Y-m-d');
            $topic = $_POST['topic'] ?? 'Weekly ERP Tracking';
            $user_id = $_SESSION['user']['id'];

            $sessionId = $this->tracker->createSession($meeting_date, $topic, $user_id);

            if ($sessionId) {
                // Send email to champions
                $this->mailService->notifyChampionsOfNewSession($topic, $meeting_date);
                $_SESSION['success'] = "New tracker session created and notifications sent.";
            } else {
                $_SESSION['error'] = "A session for this week already exists.";
            }
        }
        header("Location: {$GLOBALS['base_url']}/erp-tracker");
        exit;
    }

    public function closeSession($id)
    {
        $this->checkErpAccess(true); // Management only
        
        if ($this->tracker->closeSession($id)) {
            $_SESSION['success'] = "Session closed successfully.";
        } else {
            $_SESSION['error'] = "Failed to close session.";
        }
        
        header("Location: {$GLOBALS['base_url']}/erp-tracker");
        exit;
    }

    public function form()
    {
        $this->checkErpAccess();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user']['id'])) {
            header("Location: {$GLOBALS['base_url']}/admin-login");
            exit;
        }

        $user_id = $_SESSION['user']['id'];
        
        // Find user's department from employees table
        // Since employees are linked to users.
        $sql = "SELECT e.department_id, d.name as department_name FROM users u 
                LEFT JOIN employees e ON u.employee_id = e.id 
                LEFT JOIN departments d ON e.department_id = d.id 
                WHERE u.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $userInfo = $stmt->get_result()->fetch_assoc();

        if (!$userInfo || !$userInfo['department_id']) {
            $_SESSION['error'] = "You are not assigned to any department. Cannot fill tracker form.";
            header("Location: {$GLOBALS['base_url']}/erp-tracker");
            exit;
        }

        $activeSession = $this->tracker->getActiveSession();
        if (!$activeSession) {
            $_SESSION['error'] = "No active ERP Tracker session is currently open.";
            header("Location: {$GLOBALS['base_url']}/erp-tracker");
            exit;
        }

        $submission = $this->tracker->getSubmission($activeSession['id'], $userInfo['department_id']);
        $actions = $this->tracker->getActions($activeSession['id'], $userInfo['department_id']);

        // Fetch all users for the "owner" dropdown
        $usersRes = $this->db->query("SELECT id, full_name FROM users");
        $allUsers = [];
        while($u = $usersRes->fetch_assoc()) $allUsers[] = $u;

        $data = [
            'activeSession' => $activeSession,
            'submission' => $submission,
            'actions' => $actions,
            'department_id' => $userInfo['department_id'],
            'department_name' => $userInfo['department_name'],
            'allUsers' => $allUsers
        ];

        $this->view('erp-tracker/form', $data);
    }

    public function submitForm()
    {
        $this->checkErpAccess();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $session_id = $_POST['session_id'];
            $department_id = $_POST['department_id'];
            $champion_user_id = $_SESSION['user']['id'];
            
            $subData = [
                'session_id' => $session_id,
                'department_id' => $department_id,
                'champion_user_id' => $champion_user_id,
                'paperless_current' => $_POST['paperless_current'] ?? '',
                'paperless_target' => $_POST['paperless_target'] ?? '',
                'paperless_deadline' => $_POST['paperless_deadline'] ?? null,
                'issues_desc' => $_POST['issues_desc'] ?? '',
                'issues_priority' => $_POST['issues_priority'] ?? 'Low',
                'it_support_req' => isset($_POST['it_support_req']) ? 1 : 0,
                'it_support_desc' => $_POST['it_support_desc'] ?? '',
                'feature_req' => $_POST['feature_req'] ?? ''
            ];

            // Save main submission
            $this->tracker->saveSubmission($subData);

            // Update existing (carried forward) actions status
            if (isset($_POST['old_action_id'])) {
                foreach ($_POST['old_action_id'] as $idx => $act_id) {
                    $status = $_POST['old_status'][$idx];
                    $reason = $_POST['old_reason'][$idx] ?? '';
                    if ($status === 'Pending' && empty($reason)) {
                        // Pending reason is mandatory!
                        $_SESSION['error'] = "Pending reason is mandatory for carried forward actions.";
                        header("Location: {$GLOBALS['base_url']}/erp-tracker/form");
                        exit;
                    }
                    $this->tracker->updateAction($act_id, $status, $reason);
                }
            }

            // Insert new actions
            if (isset($_POST['new_action_name'])) {
                $champion_name = $_SESSION['user']['full_name'] ?? 'ERP Champion';
                $session = $this->tracker->getSession($session_id);
                $topic = $session['topic'];
                
                // Get department name
                $sqlDept = "SELECT name FROM departments WHERE id = ?";
                $stmt = $this->db->prepare($sqlDept);
                $stmt->bind_param("i", $department_id);
                $stmt->execute();
                $deptName = $stmt->get_result()->fetch_assoc()['name'] ?? 'Unknown';

                foreach ($_POST['new_action_name'] as $idx => $name) {
                    if (trim($name) !== '') {
                        $owner_id = $_POST['new_owner_id'][$idx];
                        $priority = $_POST['new_priority'][$idx];
                        $deadline = $_POST['new_deadline'][$idx];
                        
                        $action_id = $this->tracker->addAction($session_id, $department_id, $name, $owner_id, $priority, $deadline);
                        
                        // Send Email to Owner
                        if ($action_id) {
                            $action = $this->tracker->getAction($action_id);
                            if ($action) {
                                $owner = [
                                    'email' => $action['owner_email'],
                                    'full_name' => $action['owner_name']
                                ];
                                $this->mailService->notifyTaskOwner($owner, $champion_name, $deptName, $topic, $name, $priority, $deadline);
                            }
                        }
                    }
                }
            }

            $_SESSION['success'] = "Department tracking form submitted successfully.";
            header("Location: {$GLOBALS['base_url']}/erp-tracker/form");
            exit;
        }
    }

    public function viewSubmission($dept_id)
    {
        $this->checkErpAccess(true); // Management only
        
        if (isset($_GET['session_id'])) {
            $activeSession = $this->tracker->getSession($_GET['session_id']);
        } else {
            $activeSession = $this->tracker->getActiveSession();
        }

        if (!$activeSession) {
            $_SESSION['error'] = "No active tracker session.";
            header("Location: {$GLOBALS['base_url']}/erp-tracker");
            exit;
        }

        $session_id = $activeSession['id'];
        $submission = $this->tracker->getSubmission($session_id, $dept_id);
        $actions = $this->tracker->getActions($session_id, $dept_id);

        $sql = "SELECT name FROM departments WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $dept_id);
        $stmt->execute();
        $dept_name = $stmt->get_result()->fetch_assoc()['name'] ?? 'Unknown Department';

        $data = [
            'activeSession' => $activeSession,
            'submission' => $submission,
            'actions' => $actions,
            'department_name' => $dept_name,
            'current_route' => 'erp-tracker'
        ];

        $this->view('erp-tracker/view', $data);
    }

    public function champions()
    {
        $this->checkErpAccess(true); // Management only

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user']['id'])) {
            header("Location: {$GLOBALS['base_url']}/admin-login");
            exit;
        }

        $role_id = $_SESSION['user']['role_id'];
        $it_role = $this->getRoleId('IT Team');
        
        if (!in_array($role_id, [1, 3, $it_role])) {
            die("Access Denied: You do not have permission to manage champions.");
        }

        // Fetch all departments for dropdown
        $departments = [];
        $resDept = $this->db->query("SELECT id, name FROM departments ORDER BY name ASC");
        while ($d = $resDept->fetch_assoc()) $departments[] = $d;

        // Fetch current champions
        $champions = [];
        $sql = "SELECT c.id, c.department_id, c.user_id, d.name as dept_name, u.full_name as user_name, u.email 
                FROM erp_tracker_champions c
                JOIN departments d ON c.department_id = d.id
                JOIN users u ON c.user_id = u.id
                ORDER BY d.name ASC, u.full_name ASC";
        $resChamp = $this->db->query($sql);
        if ($resChamp) {
            while ($c = $resChamp->fetch_assoc()) {
                $champions[] = $c;
            }
        }

        $data = [
            'departments' => $departments,
            'champions' => $champions
        ];

        $this->view('erp-tracker/champions', $data);
    }

    public function getDepartmentUsers($dept_id)
    {
        $this->checkErpAccess(true); // Management only
        
        header('Content-Type: application/json');
        
        // Ensure user is authorized
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user']['id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        // Fetch users who belong to this department
        $sql = "SELECT u.id, u.full_name FROM users u 
                JOIN employees e ON u.employee_id = e.id 
                WHERE e.department_id = ?
                ORDER BY u.full_name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $dept_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        $users = [];
        while ($row = $res->fetch_assoc()) {
            $users[] = $row;
        }

        echo json_encode(['status' => 'success', 'data' => $users]);
        exit;
    }

    public function addChampion()
    {
        $this->checkErpAccess(true); // Management only

        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dept_id = $_POST['department_id'] ?? null;
            $user_id = $_POST['user_id'] ?? null;
            
            if (empty($dept_id) || empty($user_id)) {
                $_SESSION['error'] = "Please select both a department and an employee.";
            } else {
                $sql = "INSERT IGNORE INTO erp_tracker_champions (department_id, user_id) VALUES (?, ?)";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("ii", $dept_id, $user_id);
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Champion designated successfully.";
                } else {
                    $_SESSION['error'] = "Failed to add champion.";
                }
            }
        }
        
        header("Location: {$GLOBALS['base_url']}/erp-tracker/champions");
        exit;
    }

    public function removeChampion($id)
    {
        $this->checkErpAccess(true); // Management only

        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $sql = "DELETE FROM erp_tracker_champions WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Champion removed successfully.";
        } else {
            $_SESSION['error'] = "Failed to remove champion.";
        }
        
        header("Location: {$GLOBALS['base_url']}/erp-tracker/champions");
        exit;
    }


    public function deleteSession($id)
    {
        $this->checkErpAccess(true); // Management only
        
        if ($this->tracker->deleteSession($id)) {
            $_SESSION['success'] = "Session deleted successfully.";
        } else {
            $_SESSION['error'] = "Failed to delete session.";
        }
        
        header("Location: {$GLOBALS['base_url']}/erp-tracker");
        exit;
    }

    private function getRoleId($name)
    {
        $sql = "SELECT id FROM roles WHERE name = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            return $res->fetch_assoc()['id'];
        }
        return -1;
    }
}
