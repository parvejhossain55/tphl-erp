<?php
class LocationController extends Controller
{
    protected $db;
    protected $locationModel;
    public function __construct($db)
    {
        $this->db = $db;
        
        $this->locationModel = new Location($db);
     
    }
public function index()
{
   
    $locations = $this->locationModel->getAll();
    $this->view("locations/locations", [
        "current_route"   => "location-lists",
        "page_title"      => "Location List",
        "locations"      => $locations,
       
    ]);
}


    // Handle form submission
public function store()
{
    $data = [
        'name'   => $_POST['name'] ?? '',
    ];

    if ($this->locationModel->create($data)) {
        setSuccess('Location Create successfully');
        header("Location: {$GLOBALS['base_url']}/location-lists");
        exit;
    } else {
        echo "<h3 style='color:red'>Failed to create Locations </h3>";
    }
}
public function update()
{
    $data = [
        'id'     => $_POST['id'] ?? 0,          
        'name'   => $_POST['name'] ?? '',        
    ];


    if ($this->locationModel->update($data)) {
        setSuccess('Location Update successfully');
        header("Location: {$GLOBALS['base_url']}/location-lists");
        exit;
    } else {
        echo "Failed to update department";
    }
}
    public function delete()
{
    $id = $_POST['id'] ?? 0;

    if ($id && $this->locationModel->delete($id)) {
         setSuccess('Location delete successfully');
        header("Location: {$GLOBALS['base_url']}/location-lists");
        exit;
    } else {
        echo "<h3 style='color:red'>Failed to delete Locations</h3>";
    }
}

   
}
