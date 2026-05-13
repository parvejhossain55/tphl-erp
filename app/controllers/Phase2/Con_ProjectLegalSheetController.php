<?php
namespace Phase2;

use Con_ProjectLegalSheet;
use Controller;
use Project;

class Con_ProjectLegalSheetController extends Controller {
    protected $db;
    private $sheetModel;
    private $projectModel;

    public function __construct($db = null) {
        $this->db = $db;

        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user'])) {
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }

        $this->sheetModel = new Con_ProjectLegalSheet($db);
        
        // Also need project model for dropdowns
        $this->projectModel = new Project($db);
    }

    public function index() {
        $sheets = $this->sheetModel->getAll();
        
        $this->view('phase2/project_legal_sheets/legal-sheet-lists', [
            'current_route' => 'legal-sheet-lists',
            'page_title' => 'Project Legal Sheets',
            'sheets' => $sheets
        ]);
    }

    private function getProjects() {
        $uid = (int)($_SESSION['user']['id'] ?? 0);
        $rid = (int)($_SESSION['user']['role_id'] ?? 0);

        if (in_array($rid, [1, 3, 22])) {
            // Admin / super user — see all projects
            $sql    = "SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name";
            $result = $this->db->query($sql);
        } else {
            // Show projects where this user is assigned in ANY role
            $sql = "SELECT id, name FROM projects
                    WHERE deleted_at IS NULL
                      AND (
                          pm_id              = ? OR
                          site_engineer_id   = ? OR
                          landman_id         = ? OR
                          struct_engineer_id = ? OR
                          operation_head_id  = ? OR
                          draftsman_id       = ? OR
                          architect_id       = ? OR
                          estimator_id       = ? OR
                          qc_id              = ? OR
                          operator_id        = ? OR
                          created_by         = ?
                      )
                    ORDER BY name";
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param(
                    "iiiiiiiiiii",
                    $uid, $uid, $uid, $uid, $uid,
                    $uid, $uid, $uid, $uid, $uid, $uid
                );
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                // Fallback: show all if prepare fails
                $result = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name");
            }
        }

        if (!$result) {
            return []; // DB error — return empty array safely
        }
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function create() {
        $projects = $this->getProjects();
        
        $this->view('phase2/project_legal_sheets/create-legal-sheet', [
            'current_route' => 'create-legal-sheet',
            'page_title' => 'Add New Legal Sheet',
            'projects' => $projects
        ]);
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'project_id' => $_POST['project_id'] ?? 0,
                'sheet_type' => $_POST['sheet_type'] ?? '',
                'title' => $_POST['title'] ?? '',
                'version' => $_POST['version'] ?? 'V1',
                'link' => $_POST['link'] ?? '',
                'remark' => $_POST['remark'] ?? '',
                'submitted_by' => $_SESSION['user']['id']
            ];

            if (empty($data['project_id']) || empty($data['sheet_type']) || empty($data['title']) || empty($data['link'])) {
                setError('Please fill in all required fields.');
                header("Location: {$GLOBALS['base_url']}/create-legal-sheet");
                exit;
            }

            if ($this->sheetModel->create($data)) {
                setSuccess('Legal sheet added successfully.');
                header("Location: {$GLOBALS['base_url']}/legal-sheet-lists");
            } else {
                setError('Failed to add legal sheet.');
                header("Location: {$GLOBALS['base_url']}/create-legal-sheet");
            }
            exit;
        }
    }

    public function edit($id) {
        $sheet = $this->sheetModel->getById($id);
        if (!$sheet) {
            setError('Sheet not found.');
            header("Location: {$GLOBALS['base_url']}/legal-sheet-lists");
            exit;
        }
        
        $projects = $this->getProjects();

        $this->view('phase2/project_legal_sheets/edit-legal-sheet', [
            'current_route' => 'legal-sheet-lists',
            'page_title' => 'Edit Legal Sheet',
            'sheet' => $sheet,
            'projects' => $projects
        ]);
    }

    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'project_id' => $_POST['project_id'] ?? 0,
                'sheet_type' => $_POST['sheet_type'] ?? '',
                'title' => $_POST['title'] ?? '',
                'version' => $_POST['version'] ?? 'V1',
                'link' => $_POST['link'] ?? '',
                'remark' => $_POST['remark'] ?? ''
            ];

            if ($this->sheetModel->update($id, $data)) {
                setSuccess('Legal sheet updated successfully.');
            } else {
                setError('Failed to update legal sheet.');
            }
            header("Location: {$GLOBALS['base_url']}/legal-sheet-lists");
            exit;
        }
    }

    public function delete($id) {
        if ($this->sheetModel->delete($id)) {
            setSuccess('Legal sheet deleted successfully.');
        } else {
            setError('Failed to delete legal sheet.');
        }
        header("Location: {$GLOBALS['base_url']}/legal-sheet-lists");
        exit;
    }
}
