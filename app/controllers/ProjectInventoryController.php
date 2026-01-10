<?php

class ProjectInventoryController extends Controller
{
    protected $db;

    protected $projectModel;
    protected $flatModel;

    public function __construct($db)
    {
        $this->db = $db;

        $this->projectModel   = new Project($this->db);
        $this->flatModel   = new FlatModel($this->db);
    }

    /* =========================
       Project List
    ========================== */
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $inventoryProjects = $this->projectModel->inventoryGetAll();

        $projects = $this->projectModel->getAll();
        $totalProjects = count($projects);
        $flatCounts = $this->flatModel->getFlatStatusCount();

        $bookedFlats    = $flatCounts['booked'] ?? 0;
        $availableFlats = $flatCounts['available'] ?? 0;
        $this->view("sales-developments/inventory-manage/inventory-lists", [
            "current_route" => "inventory-lists",
            "page_title"    => "Inventory List",
            "inventoryProjects"      => $inventoryProjects,
            "bookedFlats"      => $bookedFlats,
            "availableFlats"      => $availableFlats,
            "totalProjects"      => $totalProjects,
        ]);
    }

    /* =========================
       Create Form
    ========================== */





    // Delete Employee
    public function delete($id) {}
}
