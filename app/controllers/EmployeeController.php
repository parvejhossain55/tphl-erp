<?php
class EmployeeController extends Controller
{
    protected $db;
    protected $employeeModel;
    protected $userModel;
    protected $roleModel;
    protected $departmentModel;
    protected $designationModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->employeeModel = new Employee($this->db);
        $this->departmentModel = new Department($this->db);
        $this->designationModel = new Designation($this->db); // 
        $this->roleModel = new Role($this->db); // 

        $this->userModel = new User($this->db);
    }

    // Employee List Page
    public function index()
    {
        $searchTerm = $_GET['q'] ?? '';
        $departmentFilter = $_GET['department_id'] ?? '';


        $currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $perPage = 10;
        $offset = ($currentPage - 1) * $perPage;

        // Total employee count with filters
        $totalEmployees = $this->employeeModel->countAll($searchTerm, $departmentFilter);
        $totalPages = ceil($totalEmployees / $perPage);

        // Get paginated employees with search & department filter
        $employees = $this->employeeModel->getPaginated($perPage, $offset, $searchTerm, $departmentFilter);

        // Departments for filter dropdown
        $departments = $this->departmentModel->getAll();

        $this->view("hrm/employees/employees", [
            "current_route"   => "employee-lists",
            "page_title"      => "Employee List",
            "currentPage"     => $currentPage,
            "totalPages"      => $totalPages,
            "employees"       => $employees,
            "departments"     => $departments,
            "searchTerm"      => $searchTerm,
            "selectedDepartment" => $departmentFilter
        ]);
    }


    // Create Employee Page
    public function create()
    {
        $departments = $this->departmentModel->getAll();
        $roles = $this->roleModel->getAll();
        $designations = $this->designationModel->getAll();
        $employeeCode = $this->employeeModel->getNextEmployeeCode();
        $this->view("hrm/employees/create-employee", [
            "current_route"   => "create-employee",
            "active"          => "create-employee",
            "page_title"      => "Add Employee",
            "show_breadcrumb" => true,
            "departments"     => $departments,
            "designations"    => $designations,

            "employeeCode"    => $employeeCode,
            "roles"           => $roles
        ]);
    }

    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // checkbox value
        $makeUser = $_POST['make_user'] ?? 0;

        // password (only if user create)
        $plainPassword = trim($_POST['password'] ?? '');

        if ($makeUser == 1 && empty($plainPassword)) {
            $_SESSION['error'] = "Password is required when creating user account!";
            header("Location: {$GLOBALS['base_url']}/create-employee");
            exit;
        }

        $hashedPassword = ($makeUser == 1)
            ? password_hash($plainPassword, PASSWORD_DEFAULT)
            : '';

        //  Employee data (NO employee_id here)
        $employeeData = [
            'full_name'       => trim($_POST['full_name'] ?? ''),
            'username'        => trim($_POST['username'] ?? ''),
            'email'           => trim($_POST['email'] ?? ''),
            'father_name'     => trim($_POST['father_name'] ?? ''),
            'phone'           => trim($_POST['phone'] ?? ''),
            'emergency_contact' => trim($_POST['emergency_contact'] ?? ''),
            'qualification'   => trim($_POST['qualification'] ?? ''),
            'basic_salary'    => trim($_POST['basic_salary'] ?? ''),
            'experience'      => trim($_POST['experience'] ?? ''),
            'workplace'       => trim($_POST['workplace'] ?? ''),
            'nid'             => trim($_POST['nid'] ?? ''),
            'address'         => trim($_POST['address'] ?? ''),
            'pass_num'        => trim($_POST['pass_num'] ?? ''),
            'department_id'   => (int)($_POST['department_id'] ?? 0),
            'designation_id'  => (int)($_POST['designation_id'] ?? 0),
            'dob'             => $_POST['dob'] ?? null,
            'joining_date'    => $_POST['joining_date'] ?? null,
            'account_holder_name' => trim($_POST['account_holder_name'] ?? ''),
            'account_number'  => trim($_POST['account_number'] ?? ''),
            'bank_name'       => trim($_POST['bank_name'] ?? ''),
            'branch_name'     => trim($_POST['branch_name'] ?? ''),
            'social_media1'   => trim($_POST['social_media1'] ?? ''),
            'social_media2'   => trim($_POST['social_media2'] ?? ''),
            'social_media3'   => trim($_POST['social_media3'] ?? ''),
            'make_user'       => $makeUser,
            'password'        => $hashedPassword,
            'created_by'      => $_SESSION['user']['id'] ?? null,
            'employee_type'      => $_POST['employee_type'],
            'employee_status'    => $_POST['employee_status']

        ];

        $photoInputName = 'photo';

        //  Create Employee (Model returns employee_id)
        $employee_id = $this->employeeModel->create($employeeData, $photoInputName);


        if (!$employee_id) {
            $_SESSION['error'] = "Employee creation failed!";
            header("Location: {$GLOBALS['base_url']}/create-employee");
            exit;
        }

        // ============================================
        // NEW: Insert initial leave balances
        // ============================================
        $leaveTypes = $this->db->query("SELECT id, total_days, is_paid FROM leave_types")->fetch_all(MYSQLI_ASSOC);

        $stmtBalance = $this->db->prepare(
            "INSERT INTO employee_leave_balances (employee_id, leave_type_id, remaining_days) VALUES (?, ?, ?)"
        );

        foreach ($leaveTypes as $lt) {
            $remaining = ($lt['is_paid'] == 1) ? $lt['total_days'] : 0; // unpaid leave = 0 or N/A in UI
            $stmtBalance->bind_param("iii", $employee_id, $lt['id'], $remaining);
            $stmtBalance->execute();
        }

        //  If user account needed
        if ($makeUser == 1) {

            $userData = prepareUserData($_POST, true);

            // Override from employee
            $userData['full_name']      = $employeeData['full_name'];
            $userData['user_name']      = $employeeData['username'];
            $userData['phone']          = $employeeData['phone'];
            $userData['email']          = $employeeData['email'];
            $userData['nid']            = $employeeData['nid'];
            $userData['designation_id'] = $employeeData['designation_id'];
            $userData['address']        = $employeeData['address'];
            $userData['password']       = $hashedPassword;
            $userData['role_id']        = $_POST['role_id'] ?? null;

            $userData['employee_id']    = $employee_id;

            $user_id = $this->userModel->create($userData);

            if (!$user_id) {
                $this->db->query("DELETE FROM employees WHERE id = $employee_id");
                $_SESSION['error'] = "User creation failed!";
                header("Location: {$GLOBALS['base_url']}/create-employee");
                exit;
            }
        }


        $_SESSION['success'] = "Employee created successfully!";
        header("Location: {$GLOBALS['base_url']}/employee-lists");
        exit;
    }


    // Edit Employee Page
    public function edit($id)
    {
        $employee = $this->employeeModel->find($id);
        $departments = $this->departmentModel->getAll();
        $designations = $this->designationModel->getAll();
        $roles = $this->roleModel->getAll();

        $this->view("hrm/employees/edit-employee", [
            "current_route"   => "edit-employee",
            "page_title"      => "Edit Employee",
            "show_breadcrumb" => true,
            "employee"        => $employee,
            "departments"     => $departments,
            "designations"    => $designations,
            "roles"    => $roles,
        ]);
    }


    public function show($id)
    {
        $employee = $this->employeeModel->find($id);
        $this->view("hrm/employees/employee-profile", [
            "current-route" => "employee-profile",
            "active" => "employee-profile",
            "employee" => $employee,
        ]);
    }



    public function update($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $makeUser = $_POST['make_user'] ?? 0;
        $plainPassword = trim($_POST['password'] ?? '');
        // password validation only if user account is needed
        if ($makeUser == 1 && empty($plainPassword)) {
            $_SESSION['error'] = "Password is required when creating/updating user account!";
            header("Location: {$GLOBALS['base_url']}/edit-employee/$id");
            exit;
        }

        $hashedPassword = null;
        if ($makeUser == 1 && !empty($plainPassword)) {
            $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
        }

        //    2 Employee data
        $employeeData = [
            'full_name'            => trim($_POST['full_name'] ?? ''),
            'username'             => trim($_POST['username'] ?? ''),
            'email'                => trim($_POST['email'] ?? ''),
            'father_name'          => trim($_POST['father_name'] ?? ''),
            'phone'                => trim($_POST['phone'] ?? ''),
            'emergency_contact'    => trim($_POST['emergency_contact'] ?? ''),
            'qualification'        => trim($_POST['qualification'] ?? ''),
            'basic_salary'         => trim($_POST['basic_salary'] ?? ''),
            'experience'           => trim($_POST['experience'] ?? ''),
            'nid'                  => trim($_POST['nid'] ?? ''),
            'address'              => trim($_POST['address'] ?? ''),
            'pass_num'             => trim($_POST['pass_num'] ?? ''),
            'department_id'        => (int)($_POST['department_id'] ?? 0),
            'designation_id'       => (int)($_POST['designation_id'] ?? 0),
            'dob'                  => $_POST['dob'] ?? null,
            'joining_date'         => $_POST['joining_date'] ?? null,
            'account_holder_name'  => trim($_POST['account_holder_name'] ?? ''),
            'account_number'       => trim($_POST['account_number'] ?? ''),
            'bank_name'            => trim($_POST['bank_name'] ?? ''),
            'branch_name'          => trim($_POST['branch_name'] ?? ''),
            'social_media1'        => trim($_POST['social_media1'] ?? ''),
            'social_media2'        => trim($_POST['social_media2'] ?? ''),
            'social_media3'        => trim($_POST['social_media3'] ?? ''),
            'created_by'           => $_SESSION['user']['id'] ?? null,
            'employee_type'      => $_POST['employee_type'],
            'employee_status'    => $_POST['employee_status'],

        ];
        if (!$this->employeeModel->update($id, $employeeData, 'photo')) {
            $_SESSION['error'] = "Employee update failed!";
            header("Location: {$GLOBALS['base_url']}/edit-employee/$id");
            exit;
        }

        //    4 User account handling

        $employee = $this->employeeModel->find($id);
        $existingUserId = $employee['user_id'] ?? null;

        if ($existingUserId) {

            $userData = [
                'full_name'      => $employeeData['full_name'],
                'user_name'      => $employeeData['username'],
                'phone'          => $employeeData['phone'],
                'email'          => $employeeData['email'],
                'nid'            => $employeeData['nid'],
                'designation_id' => $employeeData['designation_id'],
                'address'        => $employeeData['address'],
                'role_id'        => (int)($_POST['role_id'] ?? 0),
            ];

            if (!empty($plainPassword)) {
                $userData['password'] = password_hash($plainPassword, PASSWORD_DEFAULT);
            }

            $this->userModel->update($userData, $existingUserId);
        }

        //  If no user exists AND checkbox checked → create user

        elseif ($makeUser == 1) {

            $userData = [
                'full_name'      => $employeeData['full_name'],
                'user_name'      => $employeeData['username'],
                'phone'          => $employeeData['phone'],
                'email'          => $employeeData['email'],
                'nid'            => $employeeData['nid'],
                'designation_id' => $employeeData['designation_id'],
                'address'        => $employeeData['address'],
                'role_id'        => (int)($_POST['role_id'] ?? 0),
                'employee_id'    => $id,
                'password'       => password_hash($plainPassword, PASSWORD_DEFAULT),
            ];

            $user_id = $this->userModel->create($userData);

            if ($user_id) {
                $stmt = $this->db->prepare("UPDATE employees SET user_id = ? WHERE id = ?");
                $stmt->bind_param("ii", $user_id, $id);
                $stmt->execute();
            }
        }



        $_SESSION['success'] = "Employee updated successfully!";
        header("Location: {$GLOBALS['base_url']}/employee-lists");
        exit;
    }





    // Delete Employee
    public function delete()
    {
        $id = $_POST['id'] ?? 0;
        if ($id && $this->employeeModel->delete($id)) {
            header("Location: {$GLOBALS['base_url']}/employee-lists");
            exit;
        } else {
            echo "<h3 style='color:red'>Failed to delete employee</h3>";
        }
    }
}
