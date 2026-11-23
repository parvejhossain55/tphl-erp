<?php
class RoleController extends Controller
{
    protected $roleModel;
    protected $permissionModel;

    public function __construct($db)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        if ($userId <= 0) {
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $secRoleId = (int)($_SESSION['user']['secondary_role_id'] ?? 0);
        $allowed = in_array($roleId, [1, 3], true)
            || in_array($secRoleId, [1, 3], true)
            || ($roleId !== 9 && function_exists('hasPermission') && hasPermission('role & permission'));
        if (!$allowed) {
            http_response_code(403);
            if (function_exists('setError')) {
                setError("Access Denied: You do not have permission to manage roles & permissions.");
            }
            header("Location: {$GLOBALS['base_url']}/my-profile");
            exit;
        }

        $this->roleModel = new Role($db);
        $this->permissionModel = new Permission($db);
    }

    // List all roles

    public function index()
    {
        $roles = $this->roleModel->getRolesWithPermissions();
        $permissions = $this->permissionModel->getAll();

        $this->view("permissions/permission-list", [
            "roles" => $roles,
            "permissions" => $permissions,
            "page_title" => "Roles Management",
        ]);
    }
    // Show create role form

    // Handle create role form submission
    public function store($data = [])

    {
        $data = $_POST;
        if ($this->roleModel->create($data)) {
            header("Location: {$GLOBALS['base_url']}/role-permissions");
        } else {
            echo "Error creating role";
        }
    }
    public function edit()
    {
        $id = $_GET['id'] ?? 0;
        if (!$id) {
            header("Location: {$GLOBALS['base_url']}/role-permissions");
            exit;
        }

        $role = $this->roleModel->getRoleById($id); // role + permissions
        $permissions = $this->permissionModel->getAll(); // all permissions
        $roles = $this->roleModel->getRolesWithPermissions(); // for table listing

        $this->view("permissions/permission-list", [
            "roles" => $roles,
            "role_edit" => $role,
            "permissions" => $permissions,
            "page_title" => "Edit Role",
        ]);
    }

    // Handle update form submission
    public function update()
    {
        $data = $_POST;
        if ($this->roleModel->update($data)) {
            header("Location: {$GLOBALS['base_url']}/role-permissions");
            exit;
        } else {
            echo "Failed to update role";
        }
    }
}
