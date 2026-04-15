<?php
namespace Phase2;

use Controller;
use Con_LandVisibilityStudy;

class LandVisibilityStudyController extends Controller {
    protected $db;
    protected $model;

    public function __construct($db) {
        $this->db = $db;
        $this->model = new Con_LandVisibilityStudy($db);
        
        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login");
            exit;
        }
    }

    public function index() {
        $studies = $this->model->getAll();
        
        $this->view('phase2/land_visibility_study/land-visibility-study', [
            'studies' => $studies,
            'title' => 'Land Visibility Study'
        ]);
    }

    public function create() {
        // Fetch dependencies like projects if needed
        $projectsQuery = "SELECT id, name FROM projects ORDER BY name ASC";
        $projects = $this->db->query($projectsQuery)->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/land_visibility_study/create-land-visibility-study', [
            'title' => 'Add Land Visibility Study',
            'projects' => $projects
        ]);
    }

    public function store() {
        $data = [
            'project_id' => $_POST['project_id'] ?? 0,
            'land_legal_doc_status' => $_POST['land_legal_doc_status'] ?? 'Pending',
            'digital_survey_report_link' => $_POST['digital_survey_report_link'] ?? null,
            'govt_approval_doc_link' => $_POST['govt_approval_doc_link'] ?? null,
            'others_doc_link' => $_POST['others_doc_link'] ?? null,
            'created_by' => $_SESSION['user']['id']
        ];
        
        if ($this->model->create($data)) {
            setSuccess("Created Successfully");
            header("Location: " . $GLOBALS['base_url'] . "/con-land-visibility-studies");
            exit;
        } else {
            setError("Failed to create record");
            header("Location: " . $GLOBALS['base_url'] . "/con-land-visibility-studies/create");
            exit;
        }
    }

    public function edit() {
        $id = $_GET['id'] ?? 0;
        $study = $this->model->find($id);

        if (!$study) {
            setError("Record not found");
            header("Location: " . $GLOBALS['base_url'] . "/con-land-visibility-studies");
            exit;
        }

        $projectsQuery = "SELECT id, name FROM projects ORDER BY name ASC";
        $projects = $this->db->query($projectsQuery)->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/land_visibility_study/edit-land-visibility-study', [
            'title' => 'Edit Land Visibility Study',
            'study' => $study,
            'projects' => $projects
        ]);
    }

    public function update() {
        $id = $_POST['id'] ?? 0;
        $data = [
            'project_id' => $_POST['project_id'] ?? 0,
            'land_legal_doc_status' => $_POST['land_legal_doc_status'] ?? 'Pending',
            'digital_survey_report_link' => $_POST['digital_survey_report_link'] ?? null,
            'govt_approval_doc_link' => $_POST['govt_approval_doc_link'] ?? null,
            'others_doc_link' => $_POST['others_doc_link'] ?? null
        ];

        if ($this->model->update($id, $data)) {
            setSuccess("Updated Successfully");
        } else {
            setError("Failed to update record");
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-land-visibility-studies");
        exit;
    }

    public function delete() {
        $id = $_POST['id'] ?? 0;
        if ($this->model->delete($id)) {
            setSuccess("Deleted Successfully");
        } else {
            setError("Failed to delete record");
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-land-visibility-studies");
        exit;
    }
}
