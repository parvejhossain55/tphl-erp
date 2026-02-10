<?php

class ProjectController extends Controller
{
    protected $db;

    protected $locationModel;
    protected $communityModel;
    protected $projectModel;

    public function __construct($db)
    {
        $this->db = $db;

        $this->locationModel  = new Location($this->db);
        $this->communityModel = new Community($this->db);
        $this->projectModel   = new Project($this->db);
    }

    /* =========================
       Project List
    ========================== */
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $projects = $this->projectModel->getAll();

        $this->view("sales-developments/projects/project-lists", [
            "current_route" => "project-lists",
            "page_title"    => "Project List",
            "projects"      => $projects,
        ]);
    }

    /* =========================
       Create Form
    ========================== */
    public function create()
    {
        $locations   = $this->locationModel->getAll();
        $communities = $this->communityModel->getAll();

        $this->view("sales-developments/projects/create-project", [
            "current_route"   => "create-project",
            "active"          => "create-project",
            "page_title"      => "Add Project",
            "show_breadcrumb" => true,
            "locations"       => $locations,
            "communities"     => $communities,
        ]);
    }

    /* =========================
       Store Project
    ========================== */
    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // ======================
        // Collect Form Data
        // ======================
        // Helper unique slug generate 
        $slug = generateUniqueSlug($this->db, $_POST['name'], 'projects', 'slug');

        $data = [
            'name'              => $_POST['name'] ?? null,
            'slug'              => $slug,
            'location_id'       => $_POST['location_id'] ?? null,
            'community_id'      => $_POST['community_id'] ?? null,
            'building_type'     => $_POST['building_type'] ?? null,
            'total_share'       => $_POST['total_share'] ?? null,
            'unit_per_floor'    => $_POST['unit_per_floor'] ?? null,
            'front_road'        => $_POST['front_road'] ?? null,
            'types'             => $_POST['types'] ?? null,
            'price_range'       => $_POST['price_range'] ?? null,
            'project_sale_status'       => $_POST['project_sale_status'] ?? null,
            'apartment_count'   => $_POST['apartment_count'] ?? null,
            'hover_text'        => $_POST['hover_text'] ?? null,
            'plot_size'         => $_POST['plot_size'] ?? null,
            'plot_no'           => $_POST['plot_no'] ?? null,
            'block_no'          => $_POST['block_no'] ?? null,
            'brochure_link'     => $_POST['brochure_link'] ?? null,
            'passenger_lift'    => $_POST['passenger_lift'] ?? null,
            'cargo_lift'        => $_POST['cargo_lift'] ?? null,
            // Facilities (checkbox → default 0)
            'rooftop_gardening' => $_POST['rooftop_gardening'] ?? 0,
            'car_parking'       => $_POST['car_parking'] ?? 0,
            'convention'        => $_POST['convention'] ?? 0,
            'electricity_backup' => $_POST['electricity_backup'] ?? 0,
            'amenities_details' => $_POST['amenities_details'] ?? null,
            'map_link'          => $_POST['map_link'] ?? null,
            'description'       => $_POST['description'] ?? null,
            'project_map_location'       => $_POST['project_map_location'] ?? null,
            'created_by'        => $_SESSION['user']['id'] ?? null,
        ];

        // ======================
        // Floor Wise Unit Meta
        // ======================
        $floorWiseUnitMeta = null;
        if (!empty($_POST['units']) && is_array($_POST['units'])) {
            $cleanUnits = [];
            foreach ($_POST['units'] as $unit) {
                if (!empty($unit['unit_name']) && !empty($unit['sqft'])) {
                    $cleanUnits[] = [
                        'unit_name' => trim($unit['unit_name']),
                        'sqft'      => (int) $unit['sqft']
                    ];
                }
            }
            if (!empty($cleanUnits)) {
                $floorWiseUnitMeta = json_encode([
                    'typical_floor' => $cleanUnits
                ]);
            }
        }

        $data['floor_wise_unit_meta'] = $floorWiseUnitMeta;

        // ======================
        // Basic Validation
        // ======================
        if (empty($data['name']) || empty($data['location_id']) || empty($data['community_id'])) {
            setError("Project name, location and community are required");
            header("Location: {$GLOBALS['base_url']}/create-project");
            exit;
        }

        // ======================
        // Store via Model
        // ======================
        if ($this->projectModel->create($data)) {
            setSuccess("Project created successfully!");
            header("Location: {$GLOBALS['base_url']}/project-lists");
            exit;
        }

        setError("Project creation failed");
        header("Location: {$GLOBALS['base_url']}/create-project");
        exit;
    }


    public function show($id)
    {
        $project = $this->projectModel->find($id);

        $this->view("sales-developments/projects/show-project", [
            'current_route' => 'project-details',
            'page_title' => 'project-details',
            'project' => $project,
        ]);
    }

    // Edit Employee Page
    public function edit($id)
    {
        $project = $this->projectModel->find($id);
        $locations   = $this->locationModel->getAll();
        $communities = $this->communityModel->getAll();
        $this->view("sales-developments/projects/edit-project", [
            "current_route"   => "project-lists",
            "page_title"      => "Edit Project",
            "locations"        => $locations,
            "communities"        => $communities,
            "project" => $project,
            "show_breadcrumb" => true,
        ]);
    }

    public function update($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        // Fetch existing project
        $project = $this->projectModel->find($id);
        $data = [
            'name' => $_POST['name'] ?? null,
            'location_id' => $_POST['location_id'] ?? null,
            'community_id' => $_POST['community_id'] ?? null,
            'building_type' => $_POST['building_type'] ?? null,
            'total_share' => $_POST['total_share'] ?? null,
            'unit_per_floor' => $_POST['unit_per_floor'] ?? null,
            'types' => $_POST['types'] ?? null,
            'price_range' => $_POST['price_range'] ?? null,
            'project_sale_status' => $_POST['project_sale_status'] ?? null,
            'apartment_count' => $_POST['apartment_count'] ?? null,
            'front_road' => $_POST['front_road'] ?? null,
            'hover_text' => $_POST['hover_text'] ?? null,
            'plot_size' => $_POST['plot_size'] ?? null,
            'plot_no' => $_POST['plot_no'] ?? null,
            'block_no' => $_POST['block_no'] ?? null,
            'brochure_link' => $_POST['brochure_link'] ?? null,
            'passenger_lift' => $_POST['passenger_lift'] ?? null,
            'cargo_lift' => $_POST['cargo_lift'] ?? null,
            'rooftop_gardening' => isset($_POST['rooftop_gardening']) ? 1 : 0,
            'car_parking' => isset($_POST['car_parking']) ? 1 : 0,
            'convention' => isset($_POST['convention']) ? 1 : 0,
            'electricity_backup' => isset($_POST['electricity_backup']) ? 1 : 0,
            'amenities_details' => $_POST['amenities_details'] ?? null,
            'map_link' => $_POST['map_link'] ?? null,
            'description' => $_POST['description'] ?? null,
            'project_map_location' => $_POST['project_map_location'] ?? null,
            'created_by' => $_SESSION['user']['id'] ?? null,
        ];

        // ======================
        // Create slug if missing
        // ======================
        if (empty($project['slug'])) {
            $data['slug'] = generateUniqueSlug($this->db, $data['name'], 'projects', 'slug');
        }

        // ======================
        // Floor Units
        // ======================
        $floorWiseUnitMeta = null;
        if (!empty($_POST['units']) && is_array($_POST['units'])) {
            $cleanUnits = [];
            foreach ($_POST['units'] as $unit) {
                if (!empty($unit['unit_name']) && !empty($unit['sqft'])) {
                    $cleanUnits[] = [
                        'unit_name' => trim($unit['unit_name']),
                        'sqft' => (int)$unit['sqft']
                    ];
                }
            }
            if (!empty($cleanUnits)) {
                $floorWiseUnitMeta = json_encode(['typical_floor' => $cleanUnits]);
            }
        }
        $data['floor_wise_unit_meta'] = $floorWiseUnitMeta;

        if ($this->projectModel->update($id, $data)) {
            setSuccess("Project updated successfully!");
            header("Location: {$GLOBALS['base_url']}/project-lists");
            exit;
        }

        setError("Project update failed");
        header("Location: {$GLOBALS['base_url']}/edit-project/$id");
        exit;
    }

    public function delete()
    {
        $id = $_POST['id'] ?? 0;
        if ($this->projectModel->softDelete($id)) {
            setSuccess("Project Deleted Temporaly");
            header("Location:{$GLOBALS['base_url']}/project-lists");
        } else {
            setSuccess("Failed To  Deleted Temporaly Project");
            header("Location:{$GLOBALS['base_url']}/project-lists");
        };
    }
}
