<?php
namespace Phase2;

use Controller;
use Con_SecurityOperations;
use Project;

class Con_SecurityOperationsController extends Controller
{
    protected $db;
    protected $model;
    protected $projectModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->model = new \Con_SecurityOperations($db);
        $this->projectModel = new \Project($db);
    }

    private function getMyAssignedProjects()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        $filter = "1=1";
        // If not Admin (1) or Management/Approvers (3, 15, 22), filter by assigned roles
        if (!in_array($roleId, [1, 3, 15, 22])) {
            $filter = "(pm_id = $userId OR site_engineer_id = $userId OR landman_id = $userId OR struct_engineer_id = $userId OR draftsman_id = $userId OR architect_id = $userId OR estimator_id = $userId OR qc_id = $userId OR operator_id = $userId OR created_by = $userId)";
        }

        $rProjs = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL AND $filter ORDER BY name ASC");
        return $rProjs ? $rProjs->fetch_all(MYSQLI_ASSOC) : [];
    }

    // =========================================================================
    // SYSTEMS
    // =========================================================================
    
    public function systemLists()
    {
        $systems = $this->model->getAllSystems();
        $this->view('phase2/security/system-lists', [
            'current_route' => 'con-security-systems',
            'page_title' => 'Security Systems',
            'systems' => $systems
        ]);
    }

    public function systemCreate()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 15, 20, 22]) && $_SESSION['user']['role_id'] != 20) {
            // Approvers cannot create, only role 20 (Site Operator) and admins can
            if($_SESSION['user']['role_id'] != 1) {
                $_SESSION['error'] = 'You do not have permission to create security systems.';
                header('Location: ' . $GLOBALS['base_url'] . '/con-security-systems');
                exit;
            }
        }

        $projects = $this->getMyAssignedProjects();
        $this->view('phase2/security/system-create', [
            'current_route' => 'con-security-systems/create',
            'page_title' => 'Add Security System',
            'projects' => $projects
        ]);
    }

    public function systemStore()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'project_id' => $_POST['project_id'],
                'system_type' => $_POST['system_type'],
                'vendor_id' => !empty($_POST['vendor_id']) ? $_POST['vendor_id'] : null,
                'installation_date' => !empty($_POST['installation_date']) ? $_POST['installation_date'] : null,
                'status' => $_POST['status'] ?? 'Active',
                'created_by' => $_SESSION['user']['id']
            ];

            if ($this->model->createSystem($data)) {
                $_SESSION['success'] = "Security System added successfully.";
            } else {
                $_SESSION['error'] = "Failed to add security system.";
            }
            header('Location: ' . $GLOBALS['base_url'] . '/con-security-systems');
            exit;
        }
    }

    public function systemView()
    {
        $id = $_GET['id'] ?? 0;
        $systems = $this->model->getAllSystems();
        $system = null;
        foreach($systems as $s) {
            if($s['id'] == $id) {
                $system = $s; break;
            }
        }
        
        $this->view('phase2/security/system-view', [
            'current_route' => 'con-security-systems',
            'page_title' => 'System Details',
            'system' => $system
        ]);
    }

    // =========================================================================
    // EQUIPMENTS
    // =========================================================================

    public function equipmentLists()
    {
        $equipments = $this->model->getAllEquipments();
        $this->view('phase2/security/equipment-lists', [
            'current_route' => 'con-security-equipments',
            'page_title' => 'Security Equipments',
            'equipments' => $equipments
        ]);
    }

    public function equipmentCreate()
    {
        $projects = $this->getMyAssignedProjects();
        $systems = $this->model->getAllSystems();
        
        $this->view('phase2/security/equipment-create', [
            'current_route' => 'con-security-equipments/create',
            'page_title' => 'Add Equipment',
            'projects' => $projects,
            'systems' => $systems
        ]);
    }

    public function equipmentStore()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'project_id' => $_POST['project_id'],
                'security_system_id' => $_POST['security_system_id'],
                'system_type' => $_POST['system_type'],
                'equipment_name' => $_POST['equipment_name'],
                'serial_number' => $_POST['serial_number'] ?? null,
                'mac_address' => $_POST['mac_address'] ?? null,
                'location_area' => $_POST['location_area'] ?? null,
                'warranty_expiry' => !empty($_POST['warranty_expiry']) ? $_POST['warranty_expiry'] : null,
                'status' => $_POST['status'] ?? 'Good',
                'created_by' => $_SESSION['user']['id']
            ];

            if ($this->model->createEquipment($data)) {
                $_SESSION['success'] = "Equipment added successfully.";
            } else {
                $_SESSION['error'] = "Failed to add equipment.";
            }
            header('Location: ' . $GLOBALS['base_url'] . '/con-security-equipments');
            exit;
        }
    }

    public function equipmentView()
    {
        $id = $_GET['id'] ?? 0;
        $equipments = $this->model->getAllEquipments();
        $equipment = null;
        foreach($equipments as $e) {
            if($e['id'] == $id) {
                $equipment = $e; break;
            }
        }
        
        $this->view('phase2/security/equipment-view', [
            'current_route' => 'con-security-equipments',
            'page_title' => 'Equipment Details',
            'equipment' => $equipment
        ]);
    }

    // =========================================================================
    // LOGS
    // =========================================================================

    public function logLists()
    {
        $logs = $this->model->getAllLogs();
        $this->view('phase2/security/log-lists', [
            'current_route' => 'con-security-logs',
            'page_title' => 'Security Daily Logs',
            'logs' => $logs
        ]);
    }

    public function logCreate()
    {
        $projects = $this->getMyAssignedProjects();
        
        $this->view('phase2/security/log-create', [
            'current_route' => 'con-security-logs/create',
            'page_title' => 'Submit Daily Log',
            'projects' => $projects
        ]);
    }

    public function logStore()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Process the checklist array into JSON
            $checklist = [
                'cctv' => $_POST['check_cctv'] ?? 'Okay',
                'access_control' => $_POST['check_access'] ?? 'Okay',
                'intercom' => $_POST['check_intercom'] ?? 'Okay',
                'bms' => $_POST['check_bms'] ?? 'Okay',
                'fire_alarm' => $_POST['check_fire'] ?? 'Okay'
            ];

            $condition_status = 'All Okay';
            if (in_array('Issue', $checklist)) {
                $condition_status = 'Issue Found';
            }

            $data = [
                'project_id' => $_POST['project_id'],
                'operator_id' => $_SESSION['user']['id'],
                'log_date' => $_POST['log_date'] ?? date('Y-m-d'),
                'checklist_data' => json_encode($checklist),
                'condition_status' => $condition_status,
                'remarks' => $_POST['remarks'] ?? null
            ];

            if ($this->model->createLog($data)) {
                $_SESSION['success'] = "Daily log submitted successfully.";
                
                // If issue found, we could auto-generate an incident here (Auto-Incident Generation as discussed)
                if ($condition_status == 'Issue Found') {
                    $incidentData = [
                        'project_id' => $data['project_id'],
                        'security_system_id' => null, // Auto generated might not know which system specifically without extra logic
                        'equipment_id' => null,
                        'issue_title' => 'Auto-Generated Incident from Daily Log',
                        'description' => "Issue reported during daily log on " . $data['log_date'] . ". Remarks: " . $data['remarks'],
                        'priority' => 'Medium',
                        'reported_by' => $_SESSION['user']['id']
                    ];
                    $this->model->createIncident($incidentData);
                }

            } else {
                $_SESSION['error'] = "Failed to submit log.";
            }
            header('Location: ' . $GLOBALS['base_url'] . '/con-security-logs');
            exit;
        }
    }

    public function logView()
    {
        $id = $_GET['id'] ?? 0;
        $logs = $this->model->getAllLogs();
        $log = null;
        foreach($logs as $l) {
            if($l['id'] == $id) {
                $log = $l; break;
            }
        }
        
        $this->view('phase2/security/log-view', [
            'current_route' => 'con-security-logs',
            'page_title' => 'Log Details',
            'log' => $log
        ]);
    }

    public function logApprove()
    {
        if (!in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 15, 22])) {
            $_SESSION['error'] = 'You do not have permission to resolve logs.';
            header('Location: ' . $GLOBALS['base_url'] . '/con-security-logs');
            exit;
        }

        $id = $_GET['id'] ?? 0;
        if ($id && $this->model->resolveLog($id, $_SESSION['user']['id'])) {
            $_SESSION['success'] = "Log marked as Resolved.";
        } else {
            $_SESSION['error'] = "Failed to resolve log.";
        }
        header('Location: ' . $GLOBALS['base_url'] . '/con-security-logs');
        exit;
    }

    // =========================================================================
    // INCIDENTS
    // =========================================================================

    public function incidentLists()
    {
        $incidents = $this->model->getAllIncidents();
        $this->view('phase2/security/incident-lists', [
            'current_route' => 'con-security-incidents',
            'page_title' => 'Security Incidents',
            'incidents' => $incidents
        ]);
    }

    public function incidentCreate()
    {
        $projects = $this->getMyAssignedProjects();
        $equipments = $this->model->getAllEquipments();
        $systems = $this->model->getAllSystems();

        $this->view('phase2/security/incident-create', [
            'current_route' => 'con-security-incidents/create',
            'page_title' => 'Report Incident',
            'projects' => $projects,
            'systems' => $systems,
            'equipments' => $equipments
        ]);
    }

    public function incidentStore()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'project_id' => $_POST['project_id'],
                'security_system_id' => !empty($_POST['security_system_id']) ? $_POST['security_system_id'] : null,
                'equipment_id' => !empty($_POST['equipment_id']) ? $_POST['equipment_id'] : null,
                'issue_title' => $_POST['issue_title'],
                'description' => $_POST['description'],
                'priority' => $_POST['priority'] ?? 'Medium',
                'reported_by' => $_SESSION['user']['id']
            ];

            if ($this->model->createIncident($data)) {
                $_SESSION['success'] = "Incident reported successfully.";
            } else {
                $_SESSION['error'] = "Failed to report incident.";
            }
            header('Location: ' . $GLOBALS['base_url'] . '/con-security-incidents');
            exit;
        }
    }

    public function incidentApprove()
    {
        if (!in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 15, 22])) {
            $_SESSION['error'] = 'You do not have permission to resolve incidents.';
            header('Location: ' . $GLOBALS['base_url'] . '/con-security-incidents');
            exit;
        }

        $id = $_GET['id'] ?? 0;
        if ($id && $this->model->resolveIncident($id, $_SESSION['user']['id'])) {
            $_SESSION['success'] = "Incident marked as Resolved.";
        } else {
            $_SESSION['error'] = "Failed to resolve incident.";
        }
        header('Location: ' . $GLOBALS['base_url'] . '/con-security-incidents');
        exit;
    }

    public function incidentView()
    {
        $id = $_GET['id'] ?? 0;
        $incidents = $this->model->getAllIncidents();
        $incident = null;
        foreach($incidents as $inc) {
            if($inc['id'] == $id) {
                $incident = $inc; break;
            }
        }
        
        $this->view('phase2/security/incident-view', [
            'current_route' => 'con-security-incidents/view',
            'page_title' => 'Incident Details',
            'incident' => $incident
        ]);
    }
}
