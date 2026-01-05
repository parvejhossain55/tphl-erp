<?php
class EmployeeController extends Controller
{
    protected $db;
    protected $employeeModel;
    protected $userModel;
    protected $departmentModel;
    protected $designationModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->employeeModel = new Employee($this->db);
        $this->departmentModel = new Department($this->db);
        $this->designationModel = new Designation($this->db); // 

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
        $designations = $this->designationModel->getAll();

        $this->view("hrm/employees/create-employee", [
            "current_route"   => "create-employee",
            "active"          => "create-employee",
            "page_title"      => "Add Employee",
            "show_breadcrumb" => true,
            "departments"     => $departments,
            "designations"    => $designations
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
            'username'        => trim($_POST['userName'] ?? ''),
            'email'           => trim($_POST['email'] ?? ''),
            'father_name'     => trim($_POST['father_name'] ?? ''),
            'phone'           => trim($_POST['phone'] ?? ''),
            'emergency_contact' => trim($_POST['emergency_contact'] ?? ''),
            'qualification'   => trim($_POST['qualification'] ?? ''),
            'basic_salary'    => trim($_POST['basic_salary'] ?? ''),
            'experience'      => trim($_POST['experience'] ?? ''),
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
        ];

        $photoInputName = 'photo';

        //  Create Employee (Model returns employee_id)
        $employee_id = $this->employeeModel->create($employeeData, $photoInputName);

        if (!$employee_id) {
            $_SESSION['error'] = "Employee creation failed!";
            header("Location: {$GLOBALS['base_url']}/create-employee");
            exit;
        }

        //  If user account needed
        if ($makeUser == 1) {

            // Prepare user data
            $userData = prepareUserData($_POST, true);

            // Override from employee
            $userData['full_name']       = $employeeData['full_name'];
            $userData['user_name']       = $employeeData['username'];
            $userData['phone']           = $employeeData['phone'];
            $userData['email']           = $employeeData['email'];
            $userData['nid']             = $employeeData['nid'];
            $userData['designation_id']  = $employeeData['designation_id'];
            $userData['address']         = $employeeData['address'];
            $userData['password']        = $hashedPassword;

            // Create user
            $user_id = $this->userModel->create($userData);

            if (!$user_id) {
                // rollback employee
                $this->db->query("DELETE FROM employees WHERE id = $employee_id");
                $_SESSION['error'] = "User creation failed!";
                header("Location: {$GLOBALS['base_url']}/create-employee");
                exit;
            }

            // Link user with employee
            $stmt = $this->db->prepare(
                "UPDATE employees SET user_id = ? WHERE id = ?"
            );
            $stmt->bind_param("ii", $user_id, $employee_id);

            if (!$stmt->execute()) {
                $_SESSION['error'] = "Failed to link user with employee!";
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

        $this->view("hrm/employees/edit-employee", [
            "current_route"   => "edit-employee",
            "page_title"      => "Edit Employee",
            "show_breadcrumb" => true,
            "employee"        => $employee,
            "departments"     => $departments,
            "designations"    => $designations
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

        // Password validation only when needed
        if ($makeUser == 1 && empty($plainPassword)) {
            $_SESSION['error'] = "Password is required when creating/updating user account!";
            header("Location: {$GLOBALS['base_url']}/edit-employee?id=$id");
            exit;
        }

        // Hash only if password provided
        $hashedPassword = ($makeUser == 1 && !empty($plainPassword))
            ? password_hash($plainPassword, PASSWORD_DEFAULT)
            : null;

        //  Employee data (NO employee_id)
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
        ];

        $photoInputName = 'photo';

        // ✅ Update employee
        if (!$this->employeeModel->update($id, $employeeData, $photoInputName)) {
            $_SESSION['error'] = "Employee update failed!";
            header("Location: {$GLOBALS['base_url']}/edit-employee?id=$id");
            exit;
        }

        // ✅ Handle user account
        if ($makeUser == 1) {

            $employee = $this->employeeModel->find($id);
            $existingUserId = $employee['user_id'] ?? null;

            $userData = prepareUserData($_POST, true);

            // Override from employee
            $userData['full_name']      = $employeeData['full_name'];
            $userData['user_name']      = $employeeData['username'];
            $userData['phone']          = $employeeData['phone'];
            $userData['email']          = $employeeData['email'];
            $userData['nid']            = $employeeData['nid'];
            $userData['designation_id'] = $employeeData['designation_id'];
            $userData['address']        = $employeeData['address'];

            // Only update password if provided
            if ($hashedPassword !== null) {
                $userData['password'] = $hashedPassword;
            }

            // Create or update user
            if ($existingUserId) {
                $user_id = $this->userModel->update($userData, $existingUserId, 'photo');
            } else {
                $user_id = $this->userModel->create($userData);
            }

            if (!$user_id) {
                $_SESSION['error'] = "Failed to create/update user account!";
                header("Location: {$GLOBALS['base_url']}/edit-employee?id=$id");
                exit;
            }

            // Link employee to user
            $stmt = $this->db->prepare(
                "UPDATE employees SET user_id = ? WHERE id = ?"
            );
            $stmt->bind_param("ii", $user_id, $id);
            $stmt->execute();
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
