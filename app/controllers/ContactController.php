<?php
class ContactController extends Controller
{
    protected $db;
    protected $contactModel;
    public function __construct($db)
    {
        $this->db = $db;
        $this->contactModel = new Contact($this->db);
    }
    public function index()
    {


        $this->view("sales-developments/contacts/contact-lists", [
            "current_route"   => "contact-lists",
            "page_title"      => "contacts List",
            "contacts" => $this->contactModel->getAll(),
        ]);
    }
public function updateStatus()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = $_POST['id'];
        $currentStatus = $_POST['current_status'];

        $newStatus = ($currentStatus == 'pending') ? 'contacted' : 'pending';

        $this->contactModel->updateStatus($id, $newStatus);

        header("Location: " . $GLOBALS['base_url'] . "/contact-lists");
        exit();
    }
}

    // Handle form submission
   
  




}
