<?php

class AmenityController extends Controller
{
    protected $db;
    protected $amenityModel;
    protected $locationModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->amenityModel = new Amenity($db);
        $this->locationModel = new Location($db);
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function index()
    {
        $groups = $this->amenityModel->getAllGroups();

        $this->view("amenities/index", [
            "current_route" => "amenities",
            "page_title"    => "Amenities List",
            "groups"        => $groups,
        ]);
    }

    public function create()
    {
        $locations = $this->locationModel->getAll();

        $this->view("amenities/create", [
            "current_route"   => "create-amenity",
            "page_title"      => "Add Amenities",
            "show_breadcrumb" => true,
            "locations"       => $locations,
        ]);
    }

    public function store()
    {
        $location_id = $_POST['location_id'] ?? null;
        $created_by  = $_SESSION['user']['id'] ?? null;

        if (!$location_id) {
            setError("Location is required");
            header("Location: {$GLOBALS['base_url']}/create-amenity");
            exit;
        }

        if (!empty($_POST['amenity_name']) && is_array($_POST['amenity_name'])) {
            foreach ($_POST['amenity_name'] as $index => $name) {
                if (trim($name) === '') continue;

                $imagePath = '';
                if (!empty($_FILES['amenity_image']['name'][$index])) {
                    // Structure pseudo-FILES array for the helper
                    $fileArray = [
                        'name'     => $_FILES['amenity_image']['name'][$index],
                        'type'     => $_FILES['amenity_image']['type'][$index],
                        'tmp_name' => $_FILES['amenity_image']['tmp_name'][$index],
                        'error'    => $_FILES['amenity_image']['error'][$index],
                        'size'     => $_FILES['amenity_image']['size'][$index],
                    ];
                    
                    // We need a custom upload mechanism since uploadImage expects $_FILES key
                    // Let's implement a manual upload here for simplicity
                    $uploadDir = __DIR__ . '/../../public/uploads/amenities/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $ext = pathinfo($fileArray['name'], PATHINFO_EXTENSION);
                    $fileName = uniqid('amenity_') . '.' . $ext;
                    $targetPath = $uploadDir . $fileName;

                    if (move_uploaded_file($fileArray['tmp_name'], $targetPath)) {
                        $imagePath = $fileName;
                    }
                }

                $this->amenityModel->create([
                    'location_id' => $location_id,
                    'name'        => trim($name),
                    'image'       => $imagePath,
                    'created_by'  => $created_by
                ]);
            }
        }

        setSuccess("Amenities added successfully!");
        header("Location: {$GLOBALS['base_url']}/amenities");
        exit;
    }

    public function show($location_id)
    {
        $locations = $this->locationModel->getAll();
        $amenities = $this->amenityModel->getByLocation($location_id);
        
        $location_name = "Unknown Location";
        foreach ($locations as $loc) {
            if ($loc['id'] == $location_id) {
                $location_name = $loc['name'];
                break;
            }
        }

        if (empty($amenities)) {
            setError("No amenities found for this location");
            header("Location: {$GLOBALS['base_url']}/amenities");
            exit;
        }

        $this->view("amenities/show", [
            "current_route"   => "amenities",
            "page_title"      => "View Amenities - " . $location_name,
            "amenities"       => $amenities,
            "location_name"   => $location_name,
            "show_breadcrumb" => true,
        ]);
    }

    public function edit($location_id)
    {
        $locations = $this->locationModel->getAll();
        $amenities = $this->amenityModel->getByLocation($location_id);

        if (empty($amenities)) {
            setError("No amenities found for this location");
            header("Location: {$GLOBALS['base_url']}/amenities");
            exit;
        }

        $this->view("amenities/edit", [
            "current_route"   => "amenities",
            "page_title"      => "Edit Amenities",
            "locations"       => $locations,
            "amenities"       => $amenities,
            "location_id"     => $location_id,
            "show_breadcrumb" => true,
        ]);
    }

    public function update($location_id)
    {
        $new_location_id = $_POST['location_id'] ?? $location_id;
        $created_by  = $_SESSION['user']['id'] ?? null;

        // Fetch existing amenities to match IDs
        $existingAmenities = $this->amenityModel->getByLocation($location_id);
        $existingIds = array_column($existingAmenities, 'id');
        $submittedIds = $_POST['amenity_id'] ?? [];

        // Determine which ones to delete
        $toDelete = array_diff($existingIds, $submittedIds);
        foreach ($toDelete as $del_id) {
            $this->amenityModel->delete($del_id);
        }

        // Process submitted amenities
        if (!empty($_POST['amenity_name']) && is_array($_POST['amenity_name'])) {
            foreach ($_POST['amenity_name'] as $index => $name) {
                if (trim($name) === '') continue;

                $amenity_id = $_POST['amenity_id'][$index] ?? null;
                $old_image = $_POST['old_image'][$index] ?? '';
                $imagePath = $old_image;

                // Handle file upload if provided
                if (!empty($_FILES['amenity_image']['name'][$index])) {
                    $fileArray = [
                        'name'     => $_FILES['amenity_image']['name'][$index],
                        'type'     => $_FILES['amenity_image']['type'][$index],
                        'tmp_name' => $_FILES['amenity_image']['tmp_name'][$index],
                        'error'    => $_FILES['amenity_image']['error'][$index],
                        'size'     => $_FILES['amenity_image']['size'][$index],
                    ];
                    
                    $uploadDir = __DIR__ . '/../../public/uploads/amenities/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $ext = pathinfo($fileArray['name'], PATHINFO_EXTENSION);
                    $fileName = uniqid('amenity_') . '.' . $ext;
                    $targetPath = $uploadDir . $fileName;

                    if (move_uploaded_file($fileArray['tmp_name'], $targetPath)) {
                        $imagePath = $fileName;
                    }
                }

                if ($amenity_id && in_array($amenity_id, $existingIds)) {
                    // Update existing
                    $this->amenityModel->update([
                        'id'    => $amenity_id,
                        'name'  => trim($name),
                        'image' => $imagePath
                    ]);
                    
                    // Note: If location changed, we should probably update location_id too,
                    // but the model update currently doesn't update location_id. 
                    // Let's do a direct query here if location changed.
                    if ($location_id != $new_location_id) {
                        $stmt = $this->db->prepare("UPDATE amenities SET location_id = ? WHERE id = ?");
                        $stmt->bind_param("ii", $new_location_id, $amenity_id);
                        $stmt->execute();
                    }

                } else {
                    // Create new
                    $this->amenityModel->create([
                        'location_id' => $new_location_id,
                        'name'        => trim($name),
                        'image'       => $imagePath,
                        'created_by'  => $created_by
                    ]);
                }
            }
        }

        setSuccess("Amenities updated successfully!");
        header("Location: {$GLOBALS['base_url']}/amenities");
        exit;
    }

    public function delete()
    {
        $location_id = $_POST['id'] ?? 0;

        if ($location_id && $this->amenityModel->deleteByLocation($location_id)) {
            setSuccess('Amenities deleted successfully');
        } else {
            setError('Failed to delete amenities');
        }
        
        header("Location: {$GLOBALS['base_url']}/amenities");
        exit;
    }
}
