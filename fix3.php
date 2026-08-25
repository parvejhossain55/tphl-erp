<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\controllers\PayrollController.php';
$content = file_get_contents($file);

$replace1 = "        $selectedProject = isset($_GET['payroll_project_id']) ? $_GET['payroll_project_id'] : '';\n\n        $selectedEmployees = isset($_GET['employee_ids']) ? (array)$_GET['employee_ids'] : [];\n        $selectedEmployees = array_map('intval', $selectedEmployees);\n        $employeesFilter = !empty($selectedEmployees) ? ' AND e.id IN (' . implode(',', $selectedEmployees) . ')' : '';";

$content = preg_replace('/[ \t]*\ = isset\(\\[\'payroll_project_id\'\]\) \? \\[\'payroll_project_id\'\] : \'\';/', $replace1, $content);

$replace2 = "        $projectRes = $this->db->query(\"SELECT * FROM payroll_projects ORDER BY id DESC\");\n        $payroll_projects = [];\n        if ($projectRes) {\n            while ($pRow = $projectRes->fetch_assoc()) {\n                $payroll_projects[] = $pRow;\n            }\n        }\n\n        $resEmp = $this->db->query(\"SELECT id, employee_id, full_name, department_id FROM employees WHERE employee_status = 'active' ORDER BY full_name ASC\");\n        $allActiveEmployees = [];\n        if ($resEmp) {\n            while ($row = $resEmp->fetch_assoc()) {\n                $allActiveEmployees[] = $row;\n            }\n        }";

$content = preg_replace('/[ \t]*\ = \->db->query\("SELECT \* FROM payroll_projects ORDER BY id DESC"\);\r?\n[ \t]*\ = \[\];\r?\n[ \t]*if \(\\) \{\r?\n[ \t]*while \(\ = \->fetch_assoc\(\)\) \{\r?\n[ \t]*\\[\] = \;\r?\n[ \t]*\}\r?\n[ \t]*\}/m', $replace2, $content);

file_put_contents($file, $content);
echo "Replaced properly";
