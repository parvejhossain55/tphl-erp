<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\controllers\PayrollController.php';
$content = file_get_contents($file);

$replace1 = <<<EOD
        $selectedProject = isset($_GET['payroll_project_id']) ? $_GET['payroll_project_id'] : '';
        
        $selectedEmployees = isset($_GET['employee_ids']) ? (array)$_GET['employee_ids'] : [];
        $selectedEmployees = array_map('intval', $selectedEmployees);
        $employeesFilter = !empty($selectedEmployees) ? " AND e.id IN (" . implode(',', $selectedEmployees) . ")" : "";
EOD;

$content = str_replace(
    "        $selectedProject = isset($_GET['payroll_project_id']) ? $_GET['payroll_project_id'] : '';",
    $replace1,
    $content
);

$replace2 = <<<EOD
        $projectRes = $this->db->query("SELECT * FROM payroll_projects ORDER BY id DESC");
        $payroll_projects = [];
        if ($projectRes) {
            while ($pRow = $projectRes->fetch_assoc()) {
                $payroll_projects[] = $pRow;
            }
        }
        
        $resEmp = $this->db->query("SELECT id, employee_id, full_name, department_id FROM employees WHERE employee_status = 'active' ORDER BY full_name ASC");
        $allActiveEmployees = [];
        if ($resEmp) {
            while ($row = $resEmp->fetch_assoc()) {
                $allActiveEmployees[] = $row;
            }
        }
EOD;

$content = str_replace(
    <<<EOD
        $projectRes = $this->db->query("SELECT * FROM payroll_projects ORDER BY id DESC");
        $payroll_projects = [];
        if ($projectRes) {
            while ($pRow = $projectRes->fetch_assoc()) {
                $payroll_projects[] = $pRow;
            }
        }
EOD,
    $replace2,
    $content
);

file_put_contents($file, $content);
echo "Variables injected.";
