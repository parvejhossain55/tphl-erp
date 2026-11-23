<?php
class UserController extends Controller
{
    protected $db;
    protected $userModel;
    protected $designationModel;
    protected $roleModel;
    public function __construct($db)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->db = $db;
        $this->designationModel = new Designation($this->db);
        $this->userModel = new User($this->db);
        $this->roleModel = new Role($this->db);
    }

    private function ensureLoggedIn(): int
    {
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        if ($userId <= 0) {
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }
        return $userId;
    }

    private function canManageUsers(): bool
    {
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $secRoleId = (int)($_SESSION['user']['secondary_role_id'] ?? 0);
        if (in_array($roleId, [1, 3], true) || in_array($secRoleId, [1, 3], true)) {
            return true;
        }
        if ($roleId === 9) {
            return false;
        }
        return function_exists('hasPermission') && hasPermission(['role & permission', 'User Manage']);
    }

    private function denyAccess(): void
    {
        http_response_code(403);
        if (function_exists('setError')) {
            setError("Access Denied: You do not have permission to manage users.");
        }
        header("Location: {$GLOBALS['base_url']}/my-profile");
        exit;
    }

    public function index()
    {
        $this->ensureLoggedIn();
        if (!$this->canManageUsers()) {
            $this->denyAccess();
        }

        $searchTerm = $_GET['q'] ?? '';
        $roleFilter = $_GET['role'] ?? '';

        $currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $perPage = 10;
        $offset = ($currentPage - 1) * $perPage;

        // Total user count with filters
        $totalUsers = $this->userModel->countAll($searchTerm, $roleFilter);
        $totalPages = ceil($totalUsers / $perPage);

        // Get paginated users with search & role filter
        $users = $this->userModel->getPaginated($perPage, $offset, $searchTerm, $roleFilter);

        // Roles for filter dropdown
        $roles = $this->roleModel->getAll();

        $this->view("users/user-lists", [
            "current_route"   => "user-lists",
            "page_title"      => "User List",
            "currentPage"     => $currentPage,
            "totalPages"      => $totalPages,
            "users"           => $users,
            "roles"           => $roles,
            "searchTerm"      => $searchTerm,
            "selectedRole"    => $roleFilter
        ]);
    }


    // Create User Page
    // Create user form
    public function create()
    {
        $this->ensureLoggedIn();
        $loggedInRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        if (!$this->canManageUsers() && $loggedInRoleId !== 5 && !(function_exists('hasPermission') && hasPermission('Add User'))) {
            $this->denyAccess();
        }

        $designations = $this->designationModel->getAll();

        if ($loggedInRoleId == 1) {
            $roles = $this->roleModel->getAll();
        } else {
            // $roles = $this->roleModel->getAllFilterRole();
              $roles = $this->roleModel->getAll();
        }

        $this->view("users/create-user", [
            "current_route"   => "create-user",
            "active"          => "create-user",
            "page_title"      => "Add User",
            "show_breadcrumb" => true,
            "roles"           => $roles,
            "designations"           => $designations,
        ]);
    }

    // Handle form submission
    public function store()
    {
        $this->ensureLoggedIn();
        $loggedInRoleIdCheck = (int)($_SESSION['user']['role_id'] ?? 0);
        if (!$this->canManageUsers() && $loggedInRoleIdCheck !== 5 && !(function_exists('hasPermission') && hasPermission('Add User'))) {
            $this->denyAccess();
        }

        $data = [
            'full_name'        => trim($_POST['full_name'] ?? ''),
            'user_name'        => trim($_POST['user_name'] ?? ''),
            'phone'            => trim($_POST['phone'] ?? ''),
            'email'            => trim($_POST['email'] ?? ''),
            'designation_id'   => $_POST['designation_id'] ?? '',
            'address'          => trim($_POST['address'] ?? ''),
            'nid'              => trim($_POST['nid'] ?? ''),
            'role_id'          => $_POST['role_id'] ?? 0,
            'password'         => $_POST['password'] ?? '',
            'media_link1'      => $_POST['media_link1'] ?? '',
            'media_link2'      => $_POST['media_link2'] ?? '',
            'created_by'       => $_SESSION['user']['id'] ?? null,
        ];

        // ===== VALIDATION =====
        if (empty($data['full_name'])) {
            setError("Full Name is required");
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }

        if (empty($data['email'])) {
            setError("Email is required");
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            setError("Invalid email format");
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }

        if (empty($data['password'])) {
            setError("Password is required");
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }

        if ($this->userModel->create($data)) {

            // Logged-in user role
            $loggedInRoleId = $_SESSION['user']['role_id'] ?? 0;

            if ((int)$loggedInRoleId === 5) {
                setSuccess("User Created successfully");
                header("Location: {$GLOBALS['base_url']}/sales-executive-teams");
            } else {
                setSuccess("User Created successfully");
                header("Location: {$GLOBALS['base_url']}/user-lists");
            }

            exit;
        } else {
            setError("Failed to create user");
        }
    }



    public function edit($id)
    {
        $loggedInUserId = $this->ensureLoggedIn();
        if (!$this->canManageUsers() && (int)$id !== $loggedInUserId) {
            $this->denyAccess();
        }

        $user = $this->userModel->find($id);
        $designations = $this->designationModel->getAll();
        
        $loggedInRoleId = $_SESSION['user']['role_id'] ?? 0;
        if ($loggedInRoleId == 1) {
            $roles = $this->roleModel->getAll();
        } else {
            // $roles = $this->roleModel->getAllFilterRole();
              $roles = $this->roleModel->getAll();
        }
        if (!$user) {
            setError("User not found");
            header("Location: {$GLOBALS['base_url']}/user-lists");
            exit;
        }

        // Pass user to view
        $this->view("users/edit-user", [
            "current_route" => "edit-user",
            "active" => "edit-user",
            "page_title" => "Edit User",
            "show_breadcrumb" => true,
            "user" => $user,
            "designations" => $designations,
            "roles" => $roles,
        ]);
    }

    public function show($id)
    {
        $loggedInUserId = $this->ensureLoggedIn();
        $loggedInRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        if (!$this->canManageUsers() && (int)$id !== $loggedInUserId && $loggedInRoleId !== 5 && !(function_exists('hasPermission') && hasPermission('Sales Team'))) {
            $this->denyAccess();
        }

        $user = $this->userModel->find($id);
        $designations = $this->designationModel->getAll();
        $roles = $this->roleModel->getAll();
        if (!$user) {
            setError("User Not Found");
        }
        $this->view("users/show-user", [
            "current_route" => 'user-profile',
            "active" => 'user-profile',
            "page_title" => 'User Profile',
            "user" => $user,
        ]);
    }


    public function update($id)
    {
        $loggedInUserId = $this->ensureLoggedIn();
        if (!$this->canManageUsers() && (int)$id !== $loggedInUserId) {
            $this->denyAccess();
        }

        $data = [
            'full_name'      => trim($_POST['full_name'] ?? ''),
            'user_name'      => trim($_POST['user_name'] ?? ''),
            'phone'          => trim($_POST['phone'] ?? ''),
            'email'          => trim($_POST['email'] ?? ''),
            'designation_id' => $_POST['designation_id'] ?? '',
            'address'        => trim($_POST['address'] ?? ''),
            'nid'            => trim($_POST['nid'] ?? ''),
            'role_id'        => $_POST['role_id'] ?? 0,
            'password'       => $_POST['password'] ?? '',
            'media_link1'    => $_POST['media_link1'] ?? '',
            'media_link2'    => $_POST['media_link2'] ?? '',
            'created_by'     => $_SESSION['user']['id'] ?? null,
            'employee_id'    => $_POST['employee_id'] ?? null,
        ];

        $redirectTo = $_POST['redirect_to'] ?? $_SERVER['HTTP_REFERER'] ?? "{$GLOBALS['base_url']}/user-lists";

        // Get the user we are updating to verify role constraints
        $existingUser = $this->userModel->find($id);
        if (!$existingUser) {
            setError("User not found");
            header("Location: " . $redirectTo);
            exit;
        }

        // Edit-user form has no team / secondary role fields, so keep the existing values.
        // (Previously team_id was read before $existingUser was loaded, which reset it to 0.)
        $data['team_id']           = $existingUser['team_id'] ?? 0;
        $data['secondary_role_id'] = $existingUser['secondary_role_id'] ?? null;

        // Only Admin (1) and Super Admin/Director (3) can update user roles
        $loggedInUserRole = (int)($_SESSION['user']['role_id'] ?? 0);
        if (!in_array($loggedInUserRole, [1, 3])) {
            $data['role_id'] = $existingUser['role_id'];
        }

        // ===== VALIDATION =====
        $errors = [];
        if (empty($data['full_name'])) $errors[] = "Full Name is required";
        if (empty($data['email'])) $errors[] = "Email is required";
        elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";

        if (!empty($errors)) {
            setError(implode('<br>', $errors));
            header("Location: " . $redirectTo);
            exit;
        }

        // ===== PASSWORD LOGIC =====
        if (empty($data['password'])) unset($data['password']);

        // ===== UPDATE =====
        try {
            if ($this->userModel->update($data, $id)) {
                setSuccess('User updated successfully');
            } else {
                setError('Failed to update user');
            }
        } catch (\Throwable $th) {
            setError('Failed to update user');
            error_log('User update error: ' . $th->__toString());
        }

        // ===== GET ROLE NAME OF UPDATED USER =====
        $stmt = $this->db->prepare("SELECT r.name AS role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $roleResult = $stmt->get_result()->fetch_assoc();
        $roleName = $roleResult['role_name'] ?? '';

        // ===== CONDITIONAL REDIRECT =====
        if (in_array($roleName, ['Admin', 'super Admin'])) {
            // Admins & super Admins → go to user list
            $finalRedirect = "{$GLOBALS['base_url']}/user-lists";
        } else {
            // Everyone else → back to previous page
            $finalRedirect = $redirectTo;
        }

        header("Location: " . $finalRedirect);
        exit;
    }
    public function delete()
    {
        $this->ensureLoggedIn();
        if (!$this->canManageUsers()) {
            $this->denyAccess();
        }

        $id = $_POST['id'] ?? 0;
        if ($id && $this->userModel->delete($id)) {
            setSuccess("User deleted successfully");
            header("Location: {$GLOBALS['base_url']}/user-lists");
            exit;
        } else {
            setError("Failed To delete User");
        }
    }
}
