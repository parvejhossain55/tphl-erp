<?php
class CameraController extends Controller
{
    protected $db;
    protected $cameraModel;
    protected $projectModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->cameraModel = new Camera($this->db);
        $this->projectModel = new Project($this->db);
    }
    public function index()
    {

        $cameras = $this->cameraModel->getAll();
        $this->view("/cameras/camera-lists", [

            "current_route"   => "camera-lists",
            "page_title"      => "Camera List",
            "cameras" => $cameras,

        ]);

        // print_r($blogs);
    }

    public function create()
    {
        $projects = $this->projectModel->getAll();

        $this->view("/cameras/create-camera", [
            "current_route"   => "create-camera",
            "page_title"      => "create Camera ",
            "projects"      => $projects,
        ]);
    }

    // Handle form submission
public function store()
{
   if (session_status() === PHP_SESSION_NONE) session_start();

    // Get POST data
    $camera_name  = trim($_POST['camera_name'] ?? '');
    $project_id   = intval($_POST['project_id'] ?? 0);
    $rtsp_url     = trim($_POST['rtsp_url'] ?? '');
    $status       = $_POST['status'] ?? 'active';

    if (!$camera_name || !$project_id || !$rtsp_url) {
      setError("All fields are required!");
     header("Location: {$GLOBALS['base_url']}/create-camera");
        exit;
    }

    // Get project name for slug (needed by helper)
    $project = $this->projectModel->find($project_id);
    if (!$project) {
      setError("Invalid project selected!");
     header("Location: {$GLOBALS['base_url']}/camera");
        exit;
    }
    $project_name = $project['name'];

    // Call helper function to insert camera + generate HLS
    $cameraData = addCameraWithHls(
        $this->db,
        $project_id,
        $project_name,
        $camera_name,
        $rtsp_url,
        $status
    );

    setSuccess( "Camera added successfully! HLS path: " . $cameraData['hls_path']);
     header("Location: {$GLOBALS['base_url']}/camera-lists");
    exit;
}


    public function edit($id)
    {
        $camera = $this->cameraModel->find($id);
        $this->view("/cameras/edit-camera", [
            "current_route" => "edit-camera",
            "page_title"    => "ECamerag",
            "camera"          => $camera,
        ]);
    }
    public function update($id)
    {
           // Fetch existing 
    
    }


    public function show($id)
    {
        $blog = $this->cameraModel->find($id);
        $this->view("blogs/blog-details", [
            "current_route" => "blog-details",
            "active" => "show-task",
            "psge_title" => "Show Task",
            "show_breadcrumb" => true,
            "blog" => $blog,
        ]);
    }

    public function delete()
    {
    
    }
}
