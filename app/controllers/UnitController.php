<?php
class UnitController extends Controller
{
    protected $db;
    protected $unitModel;
    public function __construct($db)
    {
        $this->db = $db;
        
        $this->unitModel = new Unit($db);
     
    }
public function index()
{
   
    $units = $this->unitModel->getAll();
    $this->view("units/units", [
        "current_route"   => "unit-lists",
        "page_title"      => "unit List",
        "units"      => $units,
       
    ]);
}


    // Handle form submission
public function store()
{
    $data = [
        'name'   => $_POST['name'] ?? '',
    ];

    if ($this->unitModel->create($data)) {
        setSuccess('unit Create successfully');
        header("Location: {$GLOBALS['base_url']}/unit-lists");
        exit;
    } else {
        echo "<h3 style='color:red'>Failed to create units </h3>";
    }
}
public function update()
{
    $data = [
        'id'     => $_POST['id'] ?? 0,          
        'name'   => $_POST['name'] ?? '',        
    ];


    if ($this->unitModel->update($data)) {
        setSuccess('unit Update successfully');
        header("Location: {$GLOBALS['base_url']}/unit-lists");
        exit;
    } else {
        echo "Failed to update department";
    }
}
    public function delete()
{
    $id = $_POST['id'] ?? 0;

    if ($id && $this->unitModel->delete($id)) {
        setSuccess('Unit deleted successfully');
        header("Location: {$GLOBALS['base_url']}/unit-lists");
        exit;
    } else {
        setError('Failed to delete unit');
        header("Location: {$GLOBALS['base_url']}/unit-lists");
        exit;
    }
}

   
}
