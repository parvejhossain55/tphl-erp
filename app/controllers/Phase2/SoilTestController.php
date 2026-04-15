<?php
namespace Phase2;

use Controller;
use Con_SoilTest;

class SoilTestController extends Controller {
    protected $db;
    protected $model;

    public function __construct($db) {
        $this->db = $db;
        $this->model = new Con_SoilTest($db);
        
        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login");
            exit;
        }
    }

    public function index() {
        $tests = $this->model->getAll();
        
        $this->view('phase2/soil_tests/index', [
            'tests' => $tests,
            'title' => 'Soil Tests'
        ]);
    }
}
