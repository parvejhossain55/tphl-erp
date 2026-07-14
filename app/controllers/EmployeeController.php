<?php
class EmployeeController extends Controller
{
    protected $db;
    protected $employeeModel;
    protected $userModel;
    protected $roleModel;
    protected $departmentModel;
    protected $designationModel;
    protected $teamsModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->employeeModel = new Employee($this->db);
        $this->departmentModel = new Department($this->db);
        $this->designationModel = new Designation($this->db); // 
        $this->roleModel = new Role($this->db); // 

        $this->userModel = new User($this->db);
        $this->teamsModel = new Team($this->db);
    }

    // Employee List Page
    public function index()
    {
        $searchTerm = $_GET['q'] ?? '';
        $departmentFilter = $_GET['department_id'] ?? '';
        $teamFilter = $_GET['team_id'] ?? '';


        $currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $perPage = 10;
        $offset = ($currentPage - 1) * $perPage;

        // Total employee count with filters
        $totalEmployees = $this->employeeModel->countAll($searchTerm, $departmentFilter, $teamFilter);
        $totalPages = ceil($totalEmployees / $perPage);

        // Get paginated employees with search & department filter
        $employees = $this->employeeModel->getPaginated($perPage, $offset, $searchTerm, $departmentFilter, $teamFilter);

        // Departments for filter dropdown
        $departments = $this->departmentModel->getAll();

        // Teams for filter dropdown
        $teams = $this->teamsModel->getAll();

        $this->view("hrm/employees/employees", [
            "current_route"      => "employee-lists",
            "page_title"         => "Employee List",
            "currentPage"        => $currentPage,
            "totalPages"         => $totalPages,
            "totalEmployees"     => $totalEmployees,
            "perPage"            => $perPage,
            "employees"          => $employees,
            "departments"        => $departments,
            "teams"              => $teams,
            "searchTerm"         => $searchTerm,
            "selectedDepartment" => $departmentFilter,
            "selectedTeam"       => $teamFilter
        ]);
    }


    // Create Employee Page
    public function create()
    {
        $departments = $this->departmentModel->getAll();
        $roles = $this->roleModel->getAllFilterRole();
        $designations = $this->designationModel->getAll();
        $teams = $this->teamsModel->getAll();
        $this->view("hrm/employees/create-employee", [
            "current_route"   => "create-employee",
            "active"          => "create-employee",
            "page_title"      => "Add Employee",
            "show_breadcrumb" => true,
            "departments"     => $departments,
            "designations"    => $designations,
            "roles"           => $roles,
            "teams"           => $teams

        ]);
    }

    public function store()
    {
        try {

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // checkbox value
            $makeUser = $_POST['make_user'] ?? 0;

            // password check
            $plainPassword = trim($_POST['password'] ?? '');

            if ($makeUser == 1 && empty($plainPassword)) {
                throw new Exception("Password is required when creating user account!");
            }

            $hashedPassword = ($makeUser == 1)
                ? password_hash($plainPassword, PASSWORD_DEFAULT)
                : '';

            // Employee data
            $employeeData = [
                'employee_id'     => trim($_POST['employee_id'] ?? ''),
                'full_name'       => trim($_POST['full_name'] ?? ''),
                'username'        => trim($_POST['username'] ?? ''),
                'email'           => trim($_POST['email'] ?? ''),
                'father_name'     => trim($_POST['father_name'] ?? ''),
                'phone'           => trim($_POST['phone'] ?? ''),
                'emergency_contact' => trim($_POST['emergency_contact'] ?? ''),
                'qualification'   => trim($_POST['qualification'] ?? ''),
                'gross_salary'    => trim($_POST['gross_salary'] ?? ''),
                'basic_salary'    => trim($_POST['basic_salary'] ?? ''),
                'experience'      => trim($_POST['experience'] ?? ''),
                'workplace'       => trim($_POST['workplace'] ?? ''),
                'nid'             => trim($_POST['nid'] ?? ''),
                'address'         => trim($_POST['address'] ?? ''),
                'pass_num'        => trim($_POST['pass_num'] ?? ''),
                'department_id'   => (int)($_POST['department_id'] ?? 0),
                'designation_id'  => (int)($_POST['designation_id'] ?? 0),
                'dob'             => !empty($_POST['dob']) ? $_POST['dob'] : null,
                'joining_date'    => !empty($_POST['joining_date']) ? $_POST['joining_date'] : null,
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
                'employee_type'   => $_POST['employee_type'],
                'employee_status' => $_POST['employee_status'],
                'responsibilities' => $_POST['responsibilities'],
                'team_id' => (int)($_POST['team_id'] ?? 0),
                'is_sales_co_leader' => (int)($_POST['is_sales_co_leader'] ?? 0),
            ];

            $photoInputName = 'photo';

            // Create Employee
            $employee_id = $this->employeeModel->create($employeeData, $photoInputName);

            if (!$employee_id) {
                throw new Exception("Employee creation failed in model!");
            }

            // Insert leave balances
            $leaveTypesResult = $this->db->query("SELECT id, total_days, is_paid FROM leave_types");

            if (!$leaveTypesResult) {
                throw new Exception("Leave types fetch failed: " . $this->db->error);
            }

            $leaveTypes = $leaveTypesResult->fetch_all(MYSQLI_ASSOC);

            $stmtBalance = $this->db->prepare(
                "INSERT INTO employee_leave_balances (employee_id, leave_type_id, remaining_days) VALUES (?, ?, ?)"
            );

            if (!$stmtBalance) {
                throw new Exception("Prepare failed (leave balance): " . $this->db->error);
            }

            foreach ($leaveTypes as $lt) {
                $remaining = ($lt['is_paid'] == 1) ? $lt['total_days'] : 0;

                if (!$stmtBalance->bind_param("iii", $employee_id, $lt['id'], $remaining)) {
                    throw new Exception("Bind failed: " . $stmtBalance->error);
                }

                if (!$stmtBalance->execute()) {
                    throw new Exception("Execute failed: " . $stmtBalance->error);
                }
            }

            // Create user if needed
            if ($makeUser == 1) {

                $userData = prepareUserData($_POST, true);

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
                $userData['team_id']        = $employeeData['team_id'];

                $user_id = $this->userModel->create($userData);

                if (!$user_id) {
                    // rollback employee
                    $this->db->query("DELETE FROM employees WHERE id = $employee_id");

                    throw new Exception("User creation failed!");
                } else {
                    // Link the employee to the newly created user
                    $this->db->query("UPDATE employees SET user_id = $user_id WHERE id = $employee_id");
                }
            }

            setSuccess("Employee created successfully!");
            header("Location: {$GLOBALS['base_url']}/employee-lists");
            exit;
        } catch (Exception $e) {

            //  exact error show (DEV mode)
            $_SESSION['error'] = "Error: " . $e->getMessage();

            header("Location: {$GLOBALS['base_url']}/create-employee");
            exit;
        }
    }


    // Edit Employee Page
    public function edit($id)
    {
        $teams = $this->teamsModel->getAll();
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
            "teams"    => $teams
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

        $existingEmployee = $this->employeeModel->find($id);
        if (!$existingEmployee) {
            $_SESSION['error'] = "Employee not found!";
            header("Location: {$GLOBALS['base_url']}/employee-lists");
            exit;
        }

        $makeUser = $_POST['make_user'] ?? 0;
        $plainPassword = trim($_POST['password'] ?? '');
        $existingUserId = $existingEmployee['user_id'] ?? null;

        // User account linking check
        if (!$existingUserId) {
            // First check by employee_id 
            $userByEmpResult = $this->db->query("SELECT id FROM users WHERE employee_id = $id LIMIT 1");
            if ($userByEmpResult && $userByEmpResult->num_rows > 0) {
                $userRow = $userByEmpResult->fetch_assoc();
                $existingUserId = $userRow['id'];
                $this->db->query("UPDATE employees SET user_id = $existingUserId WHERE id = $id");
            } elseif ($makeUser == 1) {
                // Check if user already exists with this email but is not linked
                $emailToCheck = trim($_POST['email'] ?? '');
                if ($emailToCheck) {
                    $existingUserByEmail = $this->userModel->getByEmail($emailToCheck);
                    if ($existingUserByEmail) {
                        $existingUserId = $existingUserByEmail['id'];
                        // Link them now
                        $this->db->query("UPDATE employees SET user_id = $existingUserId WHERE id = $id");
                    }
                }
            }
        }

        // password validation only for NEW user account
        if ($makeUser == 1 && !$existingUserId && empty($plainPassword)) {
            $_SESSION['error'] = "Password is required when creating a new user account!";
            header("Location: {$GLOBALS['base_url']}/edit-employee/$id");
            exit;
        }

        $hashedPassword = $existingEmployee['password'] ?? '';
        if (!empty($plainPassword)) {
            $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
        }

        // Employee data
        $employeeData = [
            'employee_id'          => trim($_POST['employee_id'] ?? ''),
            'full_name'            => trim($_POST['full_name'] ?? ''),
            'username'             => trim($_POST['username'] ?? ''),
            'email'                => trim($_POST['email'] ?? ''),
            'father_name'          => trim($_POST['father_name'] ?? ''),
            'phone'                => trim($_POST['phone'] ?? ''),
            'emergency_contact'    => trim($_POST['emergency_contact'] ?? ''),
            'qualification'        => trim($_POST['qualification'] ?? ''),
            'gross_salary'         => trim($_POST['gross_salary'] ?? ''),
            'basic_salary'         => trim($_POST['basic_salary'] ?? ''),
            'experience'           => trim($_POST['experience'] ?? ''),
            'nid'                  => trim($_POST['nid'] ?? ''),
            'address' => !empty($_POST['address'])
                ? trim($_POST['address'])
                : 'N/A',
            'pass_num'             => trim($_POST['pass_num'] ?? ''),
            'department_id'        => (int)($_POST['department_id'] ?? 0),
            'designation_id'       => (int)($_POST['designation_id'] ?? 0),
            'dob'                  => !empty($_POST['dob']) ? $_POST['dob'] : null,
            'joining_date'         => !empty($_POST['joining_date']) ? $_POST['joining_date'] : null,
            'account_holder_name'  => trim($_POST['account_holder_name'] ?? ''),
            'account_number'       => trim($_POST['account_number'] ?? ''),
            'bank_name'            => trim($_POST['bank_name'] ?? ''),
            'branch_name'          => trim($_POST['branch_name'] ?? ''),
            'social_media1'        => trim($_POST['social_media1'] ?? ''),
            'social_media2'        => trim($_POST['social_media2'] ?? ''),
            'social_media3'        => trim($_POST['social_media3'] ?? ''),
            'created_by'           => $_SESSION['user']['id'] ?? null,
            'employee_type'        => $_POST['employee_type'],
            'employee_status'      => $_POST['employee_status'],
            'responsibilities'     => $_POST['responsibilities'],
            'team_id'              => (int)($_POST['team_id'] ?? 0),
            'is_sales_co_leader'   => (int)($_POST['is_sales_co_leader'] ?? 0),
            'make_user'            => $makeUser,
            'password'             => $hashedPassword
        ];

        if (!$this->employeeModel->update($id, $employeeData, 'photo')) {
            $_SESSION['error'] = "Employee update failed!";
            header("Location: {$GLOBALS['base_url']}/edit-employee/$id");
            exit;
        }

        // User account handling


        if ($existingUserId) {
            $userData = [
                'full_name'      => $employeeData['full_name'],
                'user_name'      => $employeeData['username'],
                'phone'          => $employeeData['phone'],
                'email'          => $employeeData['email'],
                'nid'            => $employeeData['nid'],
                'designation_id' => $employeeData['designation_id'],
                'address'        => $employeeData['address'],
                'team_id'        => $employeeData['team_id'],
                'role_id'        => (int)($_POST['role_id'] ?? 0),
                'employee_id'    => $id,
            ];

            if (!empty($plainPassword)) {
                $userData['password'] = $plainPassword;
            }

            $this->userModel->update($userData, $existingUserId);
        } elseif ($makeUser == 1) {
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
                'team_id'        => $employeeData['team_id'],
                'password'       => $plainPassword,
            ];

            $user_id = $this->userModel->create($userData);

            if ($user_id) {
                // Update employee with user_id
                $this->db->query("UPDATE employees SET user_id = $user_id WHERE id = $id");
            }
        }


        setSuccess("Employee updated successfully!");

        header("Location: {$GLOBALS['base_url']}/employee-lists");
        exit;
    }


    public function exportExcel()
    {
        $employees = $this->employeeModel->getAllForExport();

        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=employees.xls");

        // Only used columns
        echo "S/L\tEmployee ID\tFull Name\tUsername\tEmail\tFather Name\tPhone\tEmergency Contact\tQualification\tAddress\tDepartment\tDesignation\tDOB\tWorkplace\tJoining Date\tGross Salary\tBasic Salary\tEmployee Status\n";

        $sl = 1;

        foreach ($employees as $emp) {
            echo $sl++ . "\t" .
                ($emp['employee_id'] ?? '') . "\t" .
                ($emp['full_name'] ?? '') . "\t" .
                ($emp['username'] ?? '') . "\t" .
                ($emp['email'] ?? '') . "\t" .
                ($emp['father_name'] ?? '') . "\t" .
                ($emp['phone'] ?? '') . "\t" .
                ($emp['emergency_contact'] ?? '') . "\t" .
                ($emp['qualification'] ?? '') . "\t" .
                ($emp['address'] ?? '') . "\t" .
                ($emp['department_name'] ?? '') . "\t" .
                ($emp['designation_name'] ?? '') . "\t" .
                ($emp['dob'] ?? '') . "\t" .
                ($emp['workplace'] ?? '') . "\t" .
                ($emp['joining_date'] ?? '') . "\t" .
                ($emp['gross_salary'] ?? '') . "\t" .
                ($emp['basic_salary'] ?? '') . "\t" .
                ($emp['employee_status'] ?? '') . "\t";
        }

        exit;
    }
    public function exportPDF()
    {
        $employees = $this->employeeModel->getAllForExport();
        $totalEmployees = count($employees);

        $pdf = new \TCPDF('L', 'mm', 'A4');
        $pdf->SetCreator('Your App');
        $pdf->SetAuthor('Admin');
        $pdf->SetTitle('Employee List');

        $pdf->AddPage();

        $html = '
    <div style="text-align:center;">
        <h1 style="color:#078134;">The Premium Homes Ltd</h1>
        <h3 style="color:#28B463;">Employee List Report</h3>
        <p style="font-size:12px;">
            <b>Total Employees: </b> ' . $totalEmployees . '
        </p>
    </div>

    <br>

    <table border="1" cellpadding="4">
        <thead>
            <tr style="background-color:#078134; color:#ffffff;">
                <th>S/L</th>
                <th>Emp ID</th>
                <th>Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Father</th>
                <th>Phone</th>
                <th>Emergency</th>
                <th>Qualification</th>
                <th>Address</th>
                <th>Department</th>
                <th>Designation</th>
                <th>DOB</th>
                <th>Workplace</th>
                <th>Joining</th>
                <th>Gross</th>
                <th>Basic</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
    ';

        $sl = 1;

        foreach ($employees as $emp) {
            $html .= '<tr>
            <td>' . $sl++ . '</td>
            <td>' . ($emp['employee_id'] ?? '') . '</td>
            <td>' . ($emp['full_name'] ?? '') . '</td>
            <td>' . ($emp['username'] ?? '') . '</td>
            <td>' . ($emp['email'] ?? '') . '</td>
            <td>' . ($emp['father_name'] ?? '') . '</td>
            <td>' . ($emp['phone'] ?? '') . '</td>
            <td>' . ($emp['emergency_contact'] ?? '') . '</td>
            <td>' . ($emp['qualification'] ?? '') . '</td>
            <td>' . ($emp['address'] ?? '') . '</td>
            <td>' . ($emp['department_name'] ?? '') . '</td>
            <td>' . ($emp['designation_name'] ?? '') . '</td>
            <td>' . ($emp['dob'] ?? '') . '</td>
            <td>' . ($emp['workplace'] ?? '') . '</td>
            <td>' . ($emp['joining_date'] ?? '') . '</td>
            <td>' . ($emp['gross_salary'] ?? '') . '</td>
            <td>' . ($emp['basic_salary'] ?? '') . '</td>
            <td>' . ($emp['employee_status'] ?? '') . '</td>
        </tr>';
        }

        $html .= '</tbody></table>';

        $pdf->SetFont('helvetica', '', 8);
        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->Output('employees.pdf', 'D');
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



    public function externalEmployeesView()
    {
        $this->view("hrm/employees/external-employees", [
            "current_route" => "hrm-external-employees",
            "page_title"    => "Tipsoi Employees"
        ]);
    }
}
