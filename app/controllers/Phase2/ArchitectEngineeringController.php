<?php
namespace Phase2;

use Controller;
use Con_ArchitectEngineering;

class ArchitectEngineeringController extends Controller {
    protected $db;
    protected $model;

    public function __construct($db) {
        $this->db = $db;
        $this->model = new Con_ArchitectEngineering($db);
        
        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login");
            exit;
        }
    }

    public function index() {
        $records = $this->model->getAll();
        
        $this->view('phase2/architect_engineering/architech-engineer', [
            'records' => $records,
            'title' => 'Architect & Engineering'
        ]);
    }
}
