<?php
class RolePermissionController extends Controller
{
    protected $permissionModel;
    protected $roleModel;
    public function __construct($db = null)
    {
        $this->permissionModel = new Permission($db);
        $this->roleModel = new Role($db);
    }
  // List all permissions
    public function index()
    {
        $roles = $this->roleModel->getRolesWithPermissions();
        $permissions = $this->permissionModel->getAll();
        $this->view("permissions/role-permission-list", [
            "current_route" => "role-permissions",
            "active" => "role-permissions",
            "page_title" => "Permissions List",
            "show_breadcrumb" => true,
            "permissions" => $permissions,
            "roles" => $roles,
        ]);

          
    }

    // Show create form
    public function permission()
    {
            $permissions = $this->permissionModel->getAll();
        $this->view("permissions/permissions", [
            "current_route" => "permissions",
            "active" => "permissions",
            "page_title" => "Create Permission",
            "show_breadcrumb" => true,
            "permissions" => $permissions
            
        ]);
    }

    // Handle create submission
public function store()
{
    $data = [
        'name' => $_POST['name'] ?? '',
        'status' => isset($_POST['status']) ? (int)$_POST['status'] : 0
    ];

    $result = $this->permissionModel->create($data);

    if ($result) {
        setSuccess('Permission Created Successfully');
        // Global base_url use
        header("Location: {$GLOBALS['base_url']}/permissions");
        exit;
    } else {
         setSuccess('Permission Created Failed');
        // echo "<h3 style='color: red;'>Failed to Create Permission</h3>";
        // echo "<pre>";
        // var_dump($this->permissionModel->getDb()->error);
        // echo "</pre>";
    }

}
    // Show edit form
    public function edit($id)
    {
        $permission = $this->permissionModel->getById($id);
        $this->view("permissions/edit", [
            "current_route" => "edit-permission",
            "active" => "edit-permission",
            "page_title" => "Edit Permission",
            "show_breadcrumb" => true,
            "permission" => $permission
        ]);
    }

    // Handle update submission
public function update()
{
    $data = [
        'id'     => $_POST['id'] ?? 0,          
        'name'   => $_POST['name'] ?? '',        
        'status' => isset($_POST['status']) ? intval($_POST['status']) : 0, 
    ];

    // print_r($data); // debug: check values

    if ($this->permissionModel->update($data)) {
        setSuccess("Permission updated successfully.");
        header("Location: {$GLOBALS['base_url']}/permissions");
        exit;
    } else {
        setError("Failed to update permission.");
        // echo "Failed to update permission";
    }
}


    // Delete permission
    public function delete()
    {
           $id = $_POST['id'] ?? 0;
        if ($this->permissionModel->delete($id)) {
            header("Location: {$GLOBALS['base_url']}/permissions");
        } else {
            echo "Error deleting permission";
        }
    }

    
}
