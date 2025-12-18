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

        $flats = $this->flatModel->getAll();

        $this->view("flats/flat-lists", [
            "current_route" => "flat-lists",
            "page_title"    => "Flat List",
            "flats"      => $flats,
        ]);
    }

    /* =========================
       Create Form
    ========================== */
    
    public function create()
    {
        $projects   = $this->projectModel->getAll();
        $units = $this->unitModel->getAll();

        $this->view("flats/create-flat", [
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
        'unit_id'                => $_POST['unit_id'] ?? null,
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
    ];

    // ======================
    // Basic Validation
    // ======================
    if (empty($data['project_id']) || empty($data['unit_id'])) {
        setError("Project and Unit are required");
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
        $ownerUsers = $this->projectModel->getAll();
        $leadData = $this->flatModel->find($id);
    $locations = $this->projectModel->getAll();
        $this->view("leads/edit-lead", [
            "current_route"   => "edit-lead",
            "page_title"      => "Edit Lead",
            "leadData"        => $leadData,
            "ownerUsers"        => $ownerUsers,
            "locations"        => $locations,
            "show_breadcrumb" => true,
        ]);
    }

    public function update($id)
    {
        
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Collect form data
        $leadData = [
            'lead_name' => $_POST['lead_name'] ?? null,
            'lead_type' => $_POST['lead_type'] ?? null,
            'company' => $_POST['company'] ?? null,
            'value_amount' => $_POST['value_amount'] ?? 0,
            'currency' => $_POST['currency'] ?? 'BDT',
            'phone' => $_POST['phone'] ?? null,
            'alt_phone_number' => $_POST['alt_phone_number'] ?? null,
            'email' => $_POST['email'] ?? null,
            'source_type_id' => $_POST['source_type_id'] ?? null,
            'owner_id' => $_POST['owner_id'] ?? null,
            'address' => $_POST['address'] ?? null,
            'pref_location' => $_POST['pref_location'] ?? null,
            'designation' => $_POST['designation'] ?? null,
            'project_type' => $_POST['project_type'] ?? null,
            'interest_on' => $_POST['interest_on'] ?? null,
            'pref_flat_size' => $_POST['pref_flat_size'] ?? null,
            'communication_type' => $_POST['communication_type'] ?? null,
            'tags' => $_POST['tags'] ?? null,
            'description' => $_POST['description'] ?? null,
            'visibility' => $_POST['visibility'] ?? 'public',
            'status' => $_POST['status'] ?? 'active',
            'lead_quality' => $_POST['lead_quality'] ?? 'Pending',
        ];
        
        // Update lead
         try {
        $this->flatModel->update($id, $leadData);
        setSuccess("Lead updated successfully!");
        header("Location: {$GLOBALS['base_url']}/lead-lists");
        exit;
    } catch (Exception $e) {
        setError($e->getMessage());
        $_SESSION['old'] = $_POST;
        header("Location: {$GLOBALS['base_url']}/edit-lead/{$id}");
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
