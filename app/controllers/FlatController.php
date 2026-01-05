<?php

class FlatController extends Controller
{
    protected $db;

    protected $projectModel;
    protected $unitModel;
    protected $flatModel;

    public function __construct($db)
    {
        $this->db = $db;

        $this->projectModel  = new Project($this->db);
        $this->unitModel = new Unit($this->db);
        $this->flatModel = new FlatModel($this->db);
    }

    /* =========================
       Project List
    ========================== */
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        //  GET filters
        $search        = $_GET['q'] ?? '';
        $selectedProject = $_GET['project_id'] ?? '';

        //  Data load
        $projects   = $this->projectModel->getAll();
        $flatCounts = $this->flatModel->getFlatStatusCount();

        $bookedFlats    = $flatCounts['booked'] ?? 0;
        $availableFlats = $flatCounts['available'] ?? 0;

        //  Filtered flat list
        $flats = $this->flatModel->getAll($search, $selectedProject);

        //  View
        $this->view("sales-developments/flats/flat-lists", [
            "current_route"     => "flat-lists",
            "page_title"        => "Flat List",

            "flats"             => $flats,
            "projects"          => $projects,

            "bookedFlats"       => $bookedFlats,
            "availableFlats"    => $availableFlats,

            //  IMPORTANT for select & search retain
            "searchTerm"        => $search,
            "selectedProject"   => $selectedProject,
        ]);
    }

    /* =========================
       Create Form
    ========================== */

    public function create()
    {
        $projects   = $this->projectModel->getAll();
        $units = $this->unitModel->getAll();

        //         $flatCounts = $this->flatModel->getFlatStatusCount();

        $this->view("sales-developments/flats/create-flat", [
            "current_route"   => "create-flat",
            "active"          => "create-flat",
            "page_title"      => "Add Flat",
            "show_breadcrumb" => true,
            "projects"       => $projects,

            "units"     => $units,
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
        $data = [
            'project_id'             => $_POST['project_id'] ?? null,
            'flat_name'                => $_POST['flat_name'] ?? null,
            'sqft'                   => $_POST['sqft'] ?? null,
            'bhk'                    => $_POST['bhk'] ?? null,
            'bedroom'                => $_POST['bedroom'] ?? null,
            'bathroom'               => $_POST['bathroom'] ?? null,
            'balcony'                => $_POST['balcony'] ?? null,
            'drawing_dining_kitchen' => isset($_POST['drawing_dining_kitchen']) ? 1 : 0,
            'common_dining'          => isset($_POST['common_dining']) ? 1 : 0,
            'floor_no'               => $_POST['floor_no'] ?? null,
            'facing_site'            => $_POST['facing_site'] ?? null,
            'price_value'            => $_POST['price_value'] ?? null,
            'facilities'             => $_POST['facilities'] ?? null,
            'created_by'              => $_SESSION['user']['id'] ?? null,
        ];

        // ======================
        // Basic Validation
        // ======================
        if (empty($data['project_id'])) {
            setError("Project is required");
            header("Location: {$GLOBALS['base_url']}/create-flat");
            exit;
        }

        // ======================
        // Store via Model
        // ======================
        if ($this->flatModel->create($data)) {
            setSuccess("Flat created successfully!");
            header("Location: {$GLOBALS['base_url']}/flat-lists");
            exit;
        }

        setError("Flat creation failed");
        header("Location: {$GLOBALS['base_url']}/create-flat");
        exit;
    }

    // Edit Employee Page
    public function edit($id)
    {
        $projects   = $this->projectModel->getAll();
        $flat = $this->flatModel->find($id);
        $this->view("sales-developments/flats/edit-flat", [
            "current_route"   => "edit-lead",
            "page_title"      => "Edit Lead",
            "projects"       => $projects,
            "flat"     => $flat,
            "show_breadcrumb" => true,
        ]);
    }

    public function update($id)
    {

        if (session_status() === PHP_SESSION_NONE) session_start();

        // Collect form data
        $data = [
            'project_id'             => $_POST['project_id'] ?? null,
            'flat_name'                => $_POST['flat_name'] ?? null,
            'sqft'                   => $_POST['sqft'] ?? null,
            'bhk'                    => $_POST['bhk'] ?? null,
            'bedroom'                => $_POST['bedroom'] ?? null,
            'bathroom'               => $_POST['bathroom'] ?? null,
            'balcony'                => $_POST['balcony'] ?? null,
            'drawing_dining_kitchen' => isset($_POST['drawing_dining_kitchen']) ? 1 : 0,
            'common_dining'          => isset($_POST['common_dining']) ? 1 : 0,
            'floor_no'               => $_POST['floor_no'] ?? null,
            'facing_site'            => $_POST['facing_site'] ?? null,
            'price_value'            => $_POST['price_value'] ?? null,
            'facilities'             => $_POST['facilities'] ?? null,
            'created_by'             => $_SESSION['user']['id'] ?? null,
        ];

        // Update lead
        try {
            $this->flatModel->update($id, $data);
            setSuccess("Lead updated successfully!");
            header("Location: {$GLOBALS['base_url']}/flat-lists");
            exit;
        } catch (Exception $e) {
            setError($e->getMessage());
            $_SESSION['old'] = $_POST;
            header("Location: {$GLOBALS['base_url']}/edit-flat/{$id}");
            exit;
        }
    }


    public function importCsv()
    {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== 0) {
            setError("CSV upload failed");
            return;
        }

        $fileTmp = $_FILES['csv_file']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));

        if ($ext !== 'csv') {
            setError("Only CSV files allowed");
            return;
        }

        if (($handle = fopen($fileTmp, "r")) !== false) {

            $header = fgetcsv($handle); // first row → header

            while (($row = fgetcsv($handle, 1000, ",")) !== false) {

                // 🔑 CSV → DB column mapping
                $data = array_combine($header, $row);

                // defaults & safety
                $data['value_amount'] = (float)($data['value_amount'] ?? 0);
                $data['source_type_id'] = (int)($data['source_type_id'] ?? 0);
                $data['status'] = (int)($data['status'] ?? 0);
                $data['visibility'] = $data['visibility'] ?? 'public';

                $this->flatModel->create($data);
            }

            fclose($handle);
            setSuccess("CSV imported successfully");
        }
    }




    // Delete Employee
    public function delete($id) {}
}
