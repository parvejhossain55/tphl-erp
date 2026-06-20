<?php
class CommunityController extends Controller
{
    protected $db;
    protected $deparmentModel;
    protected $roleModel;
    public function __construct($db)
    {
        $this->db = $db;
        $this->deparmentModel = new Community($this->db);
    }
    public function index()
    {

        // Get communitiess
        $communities = $this->deparmentModel->getAll();

        $this->view("sales-developments/communities/communities", [
            "current_route"   => "community-lists",
            "page_title"      => "communities List",
            "communities"     => $communities,
        ]);
    }


    // Handle form submission
    public function store()
    {
        $data = [
            'name'   => $_POST['name'] ?? '',
            'status' => isset($_POST['status']) ? 1 : 0, // checkbox
            'created_by'      => $_SESSION['user']['id'] ?? null,
        ];

        if ($this->deparmentModel->create($data)) {
            setSuccess("Community created successfully.");
            header("Location: {$GLOBALS['base_url']}/community-lists");
            exit;
        } else {
            setError("Failed to create community.");
            echo "<h3 style='color:red'>Failed to create communities</h3>";
        }
    }
    public function update()
    {
        $data = [
            'id'     => $_POST['id'] ?? 0,
            'name'   => $_POST['name'] ?? '',
            'status' => isset($_POST['status']) ? intval($_POST['status']) : 0,
            'created_by'      => $_SESSION['user']['id'] ?? null,
        ];

        // debug removed

        if ($this->deparmentModel->update($data)) {
            header("Location: {$GLOBALS['base_url']}/community-lists");
            exit;
        } else {
            echo "Failed to update communities";
        }
    }




    public function delete()
    {
        $id = $_POST['id'] ?? 0;

        if ($this->deparmentModel->delete($id)) {
            setSuccess("Community deleted successfully.");
            header("Location: {$GLOBALS['base_url']}/community-lists");
            exit;
        } else {
            setError("Failed to delete community.");
            header("Location: {$GLOBALS['base_url']}/community-lists");
            exit;
        }
    }
}
