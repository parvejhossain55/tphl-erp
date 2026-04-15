<?php
namespace Phase2;

use Controller;
use Con_ProjectDrawing;

class ProjectDrawingController extends Controller {
    protected $db;
    protected $model;

    public function __construct($db) {
        $this->db = $db;
        $this->model = new Con_ProjectDrawing($db);
        
        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login");
            exit;
        }
    }

    public function index() {
        $drawings = $this->model->getAll();
        
        $this->view('phase2/project_drawings/index', [
            'drawings' => $drawings,
            'title' => 'Project Drawings'
        ]);
    }
}
