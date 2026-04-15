<?php
namespace Phase2;

use Controller;
use Con_DesignType;

class DesignTypeController extends Controller {
    protected $db;
    protected $model;

    public function __construct($db) {
        $this->db = $db;
        $this->model = new Con_DesignType($db);
        
        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login");
            exit;
        }
    }

    public function index() {
        $types = $this->model->getAll();
        
        $this->view('phase2/design_types/index', [
            'types' => $types,
            'title' => 'Design Types'
        ]);
    }
}
