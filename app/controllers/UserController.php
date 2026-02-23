<?php
class UserController extends Controller
{
    protected $db;
    protected $userModel;
    protected $designationModel;
    protected $roleModel;
    public function __construct($db)
    {
        $this->db = $db;
        $this->designationModel = new Designation($this->db);
        $this->userModel = new User($this->db);
        $this->roleModel = new Role($this->db);
    }
    public function index()
    {
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
        $designations = $this->designationModel->getAll();
        $roles = $this->roleModel->getAll();

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
        $user = $this->userModel->find($id);
        $designations = $this->designationModel->getAll();
        $roles = $this->roleModel->getAll();
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

        // ===== VALIDATION =====
        $errors = [];

        if (empty($data['full_name'])) {
            $errors[] = "Full Name is required";
        }

        if (empty($data['email'])) {
            $errors[] = "Email is required";
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }

        if (!empty($errors)) {
            setError(implode('<br>', $errors));
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }

        // ===== PASSWORD LOGIC =====
        if (empty($data['password'])) {
            unset($data['password']);
        }

        // ===== UPDATE =====
        try {

            if ($this->userModel->update($data, $id)) {

                setSuccess('User updated successfully');
                header("Location: {$GLOBALS['base_url']}/user-lists");
                exit;
            } else {
                setError('Failed to update user');
                header("Location: {$GLOBALS['base_url']}/edit-user/{$id}");
                exit;
            }
        } catch (\Throwable $th) {

            setError('Failed to update user');
            error_log('User update error: ' . $th->__toString());
            header("Location: {$GLOBALS['base_url']}/edit-user/{$id}");
            exit;
        }
    }

    public function delete()
    {
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
