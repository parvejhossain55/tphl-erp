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
            header("Location: {$GLOBALS['base_url']}/community-lists");
            exit;
        } else {
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

        print_r($data); // debug: check values

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

        if ($id && $this->deparmentModel->delete($id)) {
            header("Location: {$GLOBALS['base_url']}/community-lists");
            exit;
        } else {
            echo "<h3 style='color:red'>Failed to delete communities</h3>";
        }
    }
}
