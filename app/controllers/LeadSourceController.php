<?php

Class LeadSourceController extends Controller{
    protected $db ;
    protected $leadSourceModel ;
    public function __construct($db){
        $this->db = $db ;
           $this->leadSourceModel = new LeadSource();
    }
    // in constructor or before use: create model instance
// $this->leadSourceModel = new LeadSource($this->db);

public function index()
{
    $sources = $this->leadSourceModel->getAll();

    $this->view("sales-developments/lead-sources/leadSources", [
        "curent_route" => "leadSource-lists",
        "sources" => $sources,
        "leadData" => [], // empty for new
        "current_route" => "leadSource-lists",
        "page_title" => " Lead Source"
    ]);
   
}


  // store new sourcelead-sources
public function store()
{
    if (session_status() === PHP_SESSION_NONE) session_start();

    // Form data 
    $name = $_POST['name'] ?? null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (!$name) {
        setError("Source name is required!");
        header("Location: {$GLOBALS['base_url']}/leadSource-lists");
        exit;
    }

    // Helper unique slug generate 
    $slug = generateUniqueSlug($this->db, $name);

    $data = [
        'name' => $name,
        'slug' => $slug,
        'is_active' => $is_active
    ];

    // Model call
    if ($this->leadSourceModel->create($data)) {
        setSuccess("Lead source created successfully!");
    } else {
        setError( "Failed to create lead source.");
    }

    header("Location: {$GLOBALS['base_url']}/leadSource-lists");
    exit;
}

public function update()
{
    $id = $_POST['id'] ?? 0;
    $name = $_POST['name'] ?? null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (!$id || !$name) {
        setError("Invalid lead source data!");
        header("Location: {$GLOBALS['base_url']}/leadSource-lists");
        exit;
    }

    $existing = $this->leadSourceModel->find($id);
    if (!$existing) {
        setError("Lead source not found!");
        header("Location: {$GLOBALS['base_url']}/leadSource-lists");
        exit;
    }

    if (strtolower(trim($existing['name'])) === strtolower(trim($name))) {
        $slug = $existing['slug'];
    } else {
        $slug = generateUniqueSlug($this->db, $name, 'lead_sources', 'slug');
    }

    $data = [
        'name' => $name,
        'slug' => $slug,
        'is_active' => $is_active
    ];

    if ($this->leadSourceModel->update($id, $data)) {
        setSuccess("Lead source updated successfully!");
    } else {
        setError("Failed to update lead source.");
    }

    header("Location: {$GLOBALS['base_url']}/leadSource-lists");
    exit;
}

public function delete()
{
    $id = $_POST['id'] ?? 0;

    if ($id && $this->leadSourceModel->delete($id)) {
        setSuccess("Lead source deleted successfully.");
    } else {
        echo "<h3 style='color:red'>Failed to delete lead source</h3>";
    }
}

}