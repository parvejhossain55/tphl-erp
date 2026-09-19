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

    private function getOfficeBranches()
    {
        $res = $this->db->query("SELECT id, name FROM office_branches WHERE status = 'active' ORDER BY id ASC");
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    // Employee List Page
    public function index()
    {
        $searchTerm = trim($_GET['q'] ?? '');
        $departmentFilter = $_GET['department_id'] ?? '';
        $teamFilter = $_GET['team_id'] ?? '';
        $workplaceFilter = $_GET['workplace'] ?? '';

        $currentPage = max(1, isset($_GET['page']) ? intval($_GET['page']) : 1);
        $perPage = 10;

        // Total employee count with filters
        $totalEmployees = (int) $this->employeeModel->countAll($searchTerm, $departmentFilter, $teamFilter, $workplaceFilter);
        $totalPages = ceil($totalEmployees / $perPage);

        // Reset to last available page if current page exceeds total pages after filtering
        if ($totalPages > 0 && $currentPage > $totalPages) {
            $currentPage = (int) $totalPages;
        }
        $offset = ($currentPage - 1) * $perPage;

        // Get paginated employees with search & department filter
        $employees = $this->employeeModel->getPaginated($perPage, $offset, $searchTerm, $departmentFilter, $teamFilter, $workplaceFilter);

        // Departments for filter dropdown
        $departments = $this->departmentModel->getAll();

        // Teams for filter dropdown
        $teams = $this->teamsModel->getAll();

        // Workplaces for filter dropdown
        $workplaces = $this->employeeModel->getUniqueWorkplaces();

        // Build pagination route preserving all active filters (excluding 'page' and 'url')
        $queryArr = $_GET;
        unset($queryArr['page'], $queryArr['url']);
        $filteredParams = array_filter($queryArr, function ($val) {
            return $val !== '' && $val !== null;
        });
        $queryString = http_build_query($filteredParams);
        $paginationRoute = $GLOBALS['base_url'] . '/employee-lists' . ($queryString ? '?' . $queryString : '');

        $this->view("hrm/employees/employees", [
            "current_route" => "employee-lists",
            "page_title" => "Employee List",
            "currentPage" => $currentPage,
            "totalPages" => $totalPages,
            "totalEmployees" => $totalEmployees,
            "perPage" => $perPage,
            "employees" => $employees,
            "departments" => $departments,
            "teams" => $teams,
            "workplaces" => $workplaces,
            "searchTerm" => $searchTerm,
            "selectedDepartment" => $departmentFilter,
            "selectedTeam" => $teamFilter,
            "selectedWorkplace" => $workplaceFilter,
            "paginationRoute" => $paginationRoute
        ]);
    }


    // Create Employee Page
    public function create()
    {
        $departments = $this->departmentModel->getAll();
        $roles = $this->roleModel->getAllFilterRole();
        $designations = $this->designationModel->getAll();
        $teams = $this->teamsModel->getAll();
        $reportingBosses = $this->userModel->getPotentialReportingBosses();

        $payslipItemModel = new PayslipItem($this->db);
        $payslipItems = $payslipItemModel->getAll();

        $this->view("hrm/employees/create-employee", [
            "current_route" => "create-employee",
            "active" => "create-employee",
            "page_title" => "Add Employee",
            "show_breadcrumb" => true,
            "departments" => $departments,
            "designations" => $designations,
            "roles" => $roles,
            "teams" => $teams,
            "reportingBosses" => $reportingBosses,
            "payslipItems" => $payslipItems,
            "branches" => $this->getOfficeBranches()
        ]);
    }

    public function store()
    {
        try {

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // checkbox value
            $makeUser = (int)($_POST['make_user'] ?? 0);
            $roleId = (int)($_POST['role_id'] ?? 0);
            $secondaryRoleId = !empty($_POST['secondary_role_id']) ? (int)$_POST['secondary_role_id'] : null;
            if ($roleId > 0 || $secondaryRoleId !== null) {
                $makeUser = 1;
            }

            // password check
            $plainPassword = trim($_POST['password'] ?? '');

            if ($makeUser == 1 && empty($plainPassword)) {
                $plainPassword = '12345678';
            }

            $hashedPassword = ($makeUser == 1)
                ? password_hash($plainPassword, PASSWORD_DEFAULT)
                : '';

            // Employee data
            $grossSalary = !empty($_POST['gross_salary']) ? (float) $_POST['gross_salary'] : 0;
            $basicSalary = $grossSalary > 0 ? ($grossSalary * 0.50) : (!empty($_POST['basic_salary']) ? (float) $_POST['basic_salary'] : 0);

            $employeeData = [
                'employee_id' => trim($_POST['employee_id'] ?? ''),
                'full_name' => trim($_POST['full_name'] ?? ''),
                'username' => trim($_POST['username'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'father_name' => trim($_POST['father_name'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'emergency_contact' => trim($_POST['emergency_contact'] ?? ''),
                'qualification' => trim($_POST['qualification'] ?? ''),
                'gross_salary' => $grossSalary,
                'basic_salary' => $basicSalary,
                'per_meal_amount' => (isset($_POST['per_meal_amount']) && is_numeric($_POST['per_meal_amount'])) ? (float) $_POST['per_meal_amount'] : 120.00,
                'is_meal_deduction' => isset($_POST['is_meal_deduction']) ? (int)$_POST['is_meal_deduction'] : 0,
                'per_meal_percentage' => (isset($_POST['per_meal_percentage']) && $_POST['per_meal_percentage'] !== '') ? (float)$_POST['per_meal_percentage'] : 100.00,
                'experience' => trim($_POST['experience'] ?? ''),
                'workplace' => trim($_POST['workplace'] ?? ''),
                'office_branch_id' => !empty($_POST['office_branch_id']) ? (int) $_POST['office_branch_id'] : 2,
                'nid' => trim($_POST['nid'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'pass_num' => trim($_POST['pass_num'] ?? ''),
                'department_id' => (int) ($_POST['department_id'] ?? 0),
                'designation_id' => (int) ($_POST['designation_id'] ?? 0),
                'dob' => $this->sanitizeDate($_POST['dob'] ?? null),
                'joining_date' => $this->sanitizeDate($_POST['joining_date'] ?? null),
                'resign_date' => $this->sanitizeDate($_POST['resign_date'] ?? null),
                'account_holder_name' => trim($_POST['account_holder_name'] ?? ''),
                'account_number' => trim($_POST['account_number'] ?? ''),
                'bank_name' => trim($_POST['bank_name'] ?? ''),
                'branch_name' => trim($_POST['branch_name'] ?? ''),
                'social_media1' => trim($_POST['social_media1'] ?? ''),
                'social_media2' => trim($_POST['social_media2'] ?? ''),
                'social_media3' => trim($_POST['social_media3'] ?? ''),
                'make_user' => $makeUser,
                'password' => $hashedPassword,
                'created_by' => $_SESSION['user']['id'] ?? null,
                'employee_type' => $_POST['employee_type'],
                'employee_status' => !empty($_POST['resign_date']) ? 'resigned' : $_POST['employee_status'],
                'responsibilities' => $_POST['responsibilities'],
                'team_id' => (int) ($_POST['team_id'] ?? 0),
                'is_sales_co_leader' => (int) ($_POST['is_sales_co_leader'] ?? 0),
                'is_dept_head' => (int) ($_POST['is_dept_head'] ?? 0),
                'reporting_user_id' => ((int) ($_POST['is_dept_head'] ?? 0) == 1) ? null : (!empty($_POST['reporting_user_id']) ? (int) $_POST['reporting_user_id'] : null),
            ];

            $photoInputName = 'photo';

            // Create Employee
            $employee_id = $this->employeeModel->create($employeeData, $photoInputName);

            if (!$employee_id) {
                throw new Exception("Employee creation failed in model!");
            }

            // Save employee salary items configuration
            if (!empty($_POST['salary_items']) && is_array($_POST['salary_items'])) {
                $stmtSalaryItem = $this->db->prepare(
                    "INSERT INTO employee_salary_items (employee_id, payslip_item_id, amount) VALUES (?, ?, ?)"
                );
                foreach ($_POST['salary_items'] as $itemId => $amount) {
                    $amt = (float) $amount;
                    if ($amt >= 0) {
                        $stmtSalaryItem->bind_param("iid", $employee_id, $itemId, $amt);
                        $stmtSalaryItem->execute();
                    }
                }
                $stmtSalaryItem->close();
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

                $userData['full_name'] = $employeeData['full_name'];
                $userData['user_name'] = !empty($employeeData['username']) ? $employeeData['username'] : $employeeData['email'];
                $userData['phone'] = $employeeData['phone'];
                $userData['email'] = $employeeData['email'];
                $userData['nid'] = $employeeData['nid'];
                $userData['designation_id'] = $employeeData['designation_id'];
                $userData['address'] = $employeeData['address'];
                $userData['password'] = !empty($plainPassword) ? $plainPassword : '12345678';
                $userData['role_id'] = $roleId ?: ($_POST['role_id'] ?? 2);
                $userData['secondary_role_id'] = $secondaryRoleId;
                $userData['employee_id'] = $employee_id;
                $userData['team_id'] = $employeeData['team_id'];

                $user_id = $this->userModel->create($userData);

                if (!$user_id) {
                    // rollback employee
                    $this->db->query("DELETE FROM employees WHERE id = $employee_id");

                    throw new Exception("User creation failed!");
                } else {
                    // Link the employee to the newly created user
                    $this->db->query("UPDATE employees SET user_id = $user_id, make_user = 1 WHERE id = $employee_id");
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
        $reportingBosses = $this->userModel->getPotentialReportingBosses();

        $payslipItemModel = new PayslipItem($this->db);
        $payslipItems = $payslipItemModel->getAll();

        // Get configured employee salary items
        $stmt = $this->db->prepare("SELECT * FROM employee_salary_items WHERE employee_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $employeeSalaryItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $this->view("hrm/employees/edit-employee", [
            "current_route" => "edit-employee",
            "page_title" => "Edit Employee",
            "show_breadcrumb" => true,
            "employee" => $employee,
            "departments" => $departments,
            "designations" => $designations,
            "roles" => $roles,
            "teams" => $teams,
            "reportingBosses" => $reportingBosses,
            "payslipItems" => $payslipItems,
            "employeeSalaryItems" => $employeeSalaryItems,
            "branches" => $this->getOfficeBranches()
        ]);
    }


    public function show($id)
    {
        $employee = $this->employeeModel->find($id);

        // Fetch disciplinary letter counts
        $countsQuery = $this->db->query("SELECT letter_type, COUNT(*) as count FROM letters WHERE employee_id = " . (int) $id . " AND letter_type IN ('show_cause', 'warning', 'caution', 'improvement') GROUP BY letter_type");
        $disciplinary_counts = [
            'show_cause' => 0,
            'warning' => 0,
            'caution' => 0,
            'improvement' => 0
        ];
        if ($countsQuery) {
            while ($row = $countsQuery->fetch_assoc()) {
                $disciplinary_counts[$row['letter_type']] = (int) $row['count'];
            }
        }

        $this->view("hrm/employees/employee-profile", [
            "current-route" => "employee-profile",
            "active" => "employee-profile",
            "employee" => $employee,
            "disciplinary_counts" => $disciplinary_counts,
        ]);
    }



    public function update($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $existingEmployee = $this->employeeModel->find($id);
        if (!$existingEmployee) {
            setError("Employee not found!");
            $redirectUrl = !empty($_POST['return_url']) ? $_POST['return_url'] : "{$GLOBALS['base_url']}/employee-lists";
            header("Location: {$redirectUrl}");
            exit;
        }

        $roleId = (int) ($_POST['role_id'] ?? 0);
        $secondaryRoleId = !empty($_POST['secondary_role_id']) ? (int) $_POST['secondary_role_id'] : null;
        $makeUser = (int) ($_POST['make_user'] ?? 0);
        if ($roleId > 0 || $secondaryRoleId !== null) {
            $makeUser = 1;
        }

        $plainPassword = trim($_POST['password'] ?? '');
        $submittedEmail = trim($_POST['email'] ?? '');
        $existingUserId = !empty($existingEmployee['user_id']) ? (int) $existingEmployee['user_id'] : null;

        // User account linking check by email
        if ($submittedEmail) {
            $matchedUserByEmail = $this->userModel->getByEmail($submittedEmail);
            if ($matchedUserByEmail && (int)$matchedUserByEmail['id'] !== $existingUserId) {
                $existingUserId = (int) $matchedUserByEmail['id'];
                $this->db->query("UPDATE employees SET user_id = $existingUserId, make_user = 1 WHERE id = $id");
                $this->db->query("UPDATE users SET employee_id = $id WHERE id = $existingUserId");
            }
        }

        if (!$existingUserId) {
            // Check by employee_id 
            $userByEmpResult = $this->db->query("SELECT id FROM users WHERE employee_id = $id LIMIT 1");
            if ($userByEmpResult && $userByEmpResult->num_rows > 0) {
                $userRow = $userByEmpResult->fetch_assoc();
                $existingUserId = (int) $userRow['id'];
                $this->db->query("UPDATE employees SET user_id = $existingUserId, make_user = 1 WHERE id = $id");
            }
        }

        $hashedPassword = $existingEmployee['password'] ?? '';
        if (!empty($plainPassword)) {
            $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
        } elseif (($makeUser == 1 || $roleId > 0) && !$existingUserId && empty($hashedPassword)) {
            // Default password for new user account if none provided
            $plainPassword = '12345678';
            $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
        }

        $grossSalary = !empty($_POST['gross_salary']) ? (float) $_POST['gross_salary'] : 0;
        $basicSalary = $grossSalary > 0 ? ($grossSalary * 0.50) : (!empty($_POST['basic_salary']) ? (float) $_POST['basic_salary'] : 0);

        // Employee data
        $employeeData = [
            'employee_id' => trim($_POST['employee_id'] ?? ''),
            'full_name' => trim($_POST['full_name'] ?? ''),
            'username' => trim($_POST['username'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'father_name' => trim($_POST['father_name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'emergency_contact' => trim($_POST['emergency_contact'] ?? ''),
            'qualification' => trim($_POST['qualification'] ?? ''),
            'gross_salary' => $grossSalary,
            'basic_salary' => $basicSalary,
            'per_meal_amount' => !empty($_POST['per_meal_amount']) ? (float) $_POST['per_meal_amount'] : 0.00,
            'is_meal_deduction' => isset($_POST['is_meal_deduction']) ? (int)$_POST['is_meal_deduction'] : 0,
            'per_meal_percentage' => (isset($_POST['per_meal_percentage']) && $_POST['per_meal_percentage'] !== '') ? (float)$_POST['per_meal_percentage'] : 100.00,
            'experience' => trim($_POST['experience'] ?? ''),
            'nid' => trim($_POST['nid'] ?? ''),
            'address' => !empty($_POST['address'])
                ? trim($_POST['address'])
                : 'N/A',
            'pass_num' => trim($_POST['pass_num'] ?? ''),
            'department_id' => (int) ($_POST['department_id'] ?? 0),
            'designation_id' => (int) ($_POST['designation_id'] ?? 0),
            'dob' => $this->sanitizeDate($_POST['dob'] ?? null),
            'joining_date' => $this->sanitizeDate($_POST['joining_date'] ?? null),
            'resign_date' => $this->sanitizeDate($_POST['resign_date'] ?? null),
            'account_holder_name' => trim($_POST['account_holder_name'] ?? ''),
            'account_number' => trim($_POST['account_number'] ?? ''),
            'bank_name' => trim($_POST['bank_name'] ?? ''),
            'branch_name' => trim($_POST['branch_name'] ?? ''),
            'social_media1' => trim($_POST['social_media1'] ?? ''),
            'social_media2' => trim($_POST['social_media2'] ?? ''),
            'social_media3' => trim($_POST['social_media3'] ?? ''),
            'created_by' => $_SESSION['user']['id'] ?? null,
            'employee_type' => $_POST['employee_type'],
            'employee_status' => !empty($_POST['resign_date']) ? 'resigned' : $_POST['employee_status'],
            'responsibilities' => $_POST['responsibilities'],
            'team_id' => (int) ($_POST['team_id'] ?? 0),
            'workplace' => trim($_POST['workplace'] ?? ''),
            'office_branch_id' => !empty($_POST['office_branch_id']) ? (int) $_POST['office_branch_id'] : 2,
            'is_sales_co_leader' => (int) ($_POST['is_sales_co_leader'] ?? 0),
            'is_dept_head' => (int) ($_POST['is_dept_head'] ?? 0),
            'reporting_user_id' => ((int) ($_POST['is_dept_head'] ?? 0) == 1) ? null : (!empty($_POST['reporting_user_id']) ? (int) $_POST['reporting_user_id'] : null),
            'make_user' => ($existingUserId || $makeUser == 1) ? 1 : 0,
            'password' => $hashedPassword
        ];

        if (!$this->employeeModel->update($id, $employeeData, 'photo')) {
            setError("Employee update failed!");
            $failRedirect = "{$GLOBALS['base_url']}/edit-employee/$id";
            if (!empty($_POST['return_url'])) {
                $failRedirect .= '?return_url=' . urlencode($_POST['return_url']);
            }
            header("Location: {$failRedirect}");
            exit;
        }

        // Update employee salary items configuration
        $this->db->query("DELETE FROM employee_salary_items WHERE employee_id = " . (int) $id);
        if (!empty($_POST['salary_items']) && is_array($_POST['salary_items'])) {
            $stmtSalaryItem = $this->db->prepare(
                "INSERT INTO employee_salary_items (employee_id, payslip_item_id, amount) VALUES (?, ?, ?)"
            );
            foreach ($_POST['salary_items'] as $itemId => $amount) {
                $amt = (float) $amount;
                if ($amt >= 0) {
                    $stmtSalaryItem->bind_param("iid", $id, $itemId, $amt);
                    $stmtSalaryItem->execute();
                }
            }
            $stmtSalaryItem->close();
        }

        // User account handling
        if ($existingUserId) {
            $userData = [
                'full_name' => $employeeData['full_name'],
                'user_name' => !empty($employeeData['username']) ? $employeeData['username'] : $employeeData['email'],
                'phone' => $employeeData['phone'],
                'email' => $employeeData['email'],
                'nid' => $employeeData['nid'],
                'designation_id' => $employeeData['designation_id'],
                'address' => $employeeData['address'],
                'team_id' => $employeeData['team_id'],
                'role_id' => $roleId ?: ($existingEmployee['role_id'] ?? 2),
                'secondary_role_id' => $secondaryRoleId,
                'employee_id' => $id,
            ];

            if (!empty($plainPassword)) {
                $userData['password'] = $plainPassword;
            }

            $this->userModel->update($userData, $existingUserId);
            $this->db->query("UPDATE employees SET user_id = $existingUserId, make_user = 1 WHERE id = $id");
        } elseif ($makeUser == 1 || $roleId > 0) {
            $userData = [
                'full_name' => $employeeData['full_name'],
                'user_name' => !empty($employeeData['username']) ? $employeeData['username'] : $employeeData['email'],
                'phone' => $employeeData['phone'],
                'email' => $employeeData['email'],
                'nid' => $employeeData['nid'],
                'designation_id' => $employeeData['designation_id'],
                'address' => $employeeData['address'],
                'role_id' => $roleId ?: 2,
                'secondary_role_id' => $secondaryRoleId,
                'employee_id' => $id,
                'team_id' => $employeeData['team_id'],
                'password' => !empty($plainPassword) ? $plainPassword : '12345678',
            ];

            $user_id = $this->userModel->create($userData);

            if ($user_id) {
                // Update employee with user_id
                $this->db->query("UPDATE employees SET user_id = $user_id, make_user = 1 WHERE id = $id");
            }
        }

        setSuccess("Employee updated successfully!");

        $redirectUrl = !empty($_POST['return_url']) ? $_POST['return_url'] : "{$GLOBALS['base_url']}/employee-lists";
        header("Location: {$redirectUrl}");
        exit;
    }


    public function exportExcel()
    {
        $searchTerm = $_GET['q'] ?? '';
        $departmentFilter = $_GET['department_id'] ?? '';
        $teamFilter = $_GET['team_id'] ?? '';
        $workplaceFilter = $_GET['workplace'] ?? '';

        $employees = $this->employeeModel->getAllForExport($searchTerm, $departmentFilter, $teamFilter, $workplaceFilter);

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
                ($emp['employee_status'] ?? '') . "\n";
        }

        exit;
    }
    public function exportPDF()
    {
        $searchTerm = $_GET['q'] ?? '';
        $departmentFilter = $_GET['department_id'] ?? '';
        $teamFilter = $_GET['team_id'] ?? '';
        $workplaceFilter = $_GET['workplace'] ?? '';

        $employees = $this->employeeModel->getAllForExport($searchTerm, $departmentFilter, $teamFilter, $workplaceFilter);
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

    public function searchSuggestions()
    {
        $q = $_GET['q'] ?? '';
        if (empty($q)) {
            echo json_encode([]);
            exit;
        }
        $suggestions = $this->employeeModel->getSearchSuggestions($q);
        echo json_encode($suggestions);
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
        $departments = $this->departmentModel->getAll();
        $designations = $this->designationModel->getAll();
        $roles = $this->roleModel->getAllFilterRole();
        $branches = $this->getOfficeBranches();
        $reportingBosses = $this->userModel->getPotentialReportingBosses();

        $this->view("hrm/employees/external-employees", [
            "current_route" => "hrm-external-employees",
            "page_title" => "Tipsoi Employees",
            "departments" => $departments,
            "designations" => $designations,
            "roles" => $roles,
            "branches" => $branches,
            "reportingBosses" => $reportingBosses
        ]);
    }

    public function createTipsoiEmployeeView()
    {
        $this->view("hrm/employees/create-tipsoi-employee", [
            "current_route" => "hrm-create-tipsoi-employee",
            "page_title" => "Create Employee"
        ]);
    }

    /**
     * Safely normalize date inputs to YYYY-MM-DD for MySQL DATE columns
     */
    private function sanitizeDate($dateStr)
    {
        if (empty($dateStr)) {
            return null;
        }
        $trimmed = trim((string)$dateStr);
        if ($trimmed === '' || $trimmed === '0000-00-00') {
            return null;
        }
        $normalized = str_replace('/', '-', $trimmed);
        $ts = strtotime($normalized);
        if ($ts && date('Y-m-d', $ts) !== '1970-01-01') {
            return date('Y-m-d', $ts);
        }
        return null;
    }
}
