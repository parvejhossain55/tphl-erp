<?php
/**
 * Add importCsv() to DesignTypeController, ProjectStructuralDesignController, Con_WorkTypeController
 */

$controllers = [
    'DesignTypeController.php' => [
        'columns' => ['Name', 'Description'],
        'redirect' => '/con-design-types',
        'insert_logic' => '
            $name = trim($row[\'Name\'] ?? \'\');
            $desc = trim($row[\'Description\'] ?? \'\');
            if (empty($name)) { $errorCount++; $errors[] = "Missing Name"; continue; }
            $stmt = $db->prepare("SELECT id FROM design_types WHERE name = ? LIMIT 1");
            $stmt->bind_param("s", $name); $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) { $errors[] = "Design Type \'$name\' already exists"; $errorCount++; continue; }
            $ins = $db->prepare("INSERT INTO design_types (name, description, created_by) VALUES (?,?,?)");
            $ins->bind_param("ssi", $name, $desc, $userId);
            if ($ins->execute()) { $successCount++; } else { $errorCount++; $errors[] = "Failed to save \'$name\'"; }
        ',
        'route' => 'con-design-types',
    ],
    'ProjectStructuralDesignController.php' => [
        'columns' => ['Project_Name', 'Design_Stage', 'Consultant_Name', 'Submission_Date', 'Remarks'],
        'redirect' => '/structure-design',
        'insert_logic' => '
            $projectName   = trim($row[\'Project_Name\'] ?? \'\');
            $designStage   = trim($row[\'Design_Stage\'] ?? \'\');
            $consultant    = trim($row[\'Consultant_Name\'] ?? \'\');
            $subDate       = trim($row[\'Submission_Date\'] ?? date(\'Y-m-d\'));
            $remarks       = trim($row[\'Remarks\'] ?? \'\');
            if (empty($projectName)) { $errorCount++; $errors[] = "Missing Project_Name"; continue; }
            $stmt = $db->prepare("SELECT id FROM projects WHERE name = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->bind_param("s", $projectName); $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows === 0) { $errorCount++; $errors[] = "Project \'$projectName\' not found"; continue; }
            $projectId = $res->fetch_assoc()[\'id\'];
            $ins = $db->prepare("INSERT INTO project_structural_designs (project_id, design_stage, consultant_name, submission_date, remarks, created_by) VALUES (?,?,?,?,?,?)");
            $ins->bind_param("issssi", $projectId, $designStage, $consultant, $subDate, $remarks, $userId);
            if ($ins->execute()) { $successCount++; } else { $errorCount++; $errors[] = "Failed for Project \'$projectName\'"; }
        ',
        'route' => 'structure-design',
    ],
    'Con_WorkTypeController.php' => [
        'columns' => ['Name', 'Description'],
        'redirect' => '/con-work-types',
        'insert_logic' => '
            $name = trim($row[\'Name\'] ?? \'\');
            $desc = trim($row[\'Description\'] ?? \'\');
            if (empty($name)) { $errorCount++; $errors[] = "Missing Name"; continue; }
            $stmt = $db->prepare("SELECT id FROM work_types WHERE name = ? LIMIT 1");
            $stmt->bind_param("s", $name); $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) { $errors[] = "Work Type \'$name\' already exists"; $errorCount++; continue; }
            $ins = $db->prepare("INSERT INTO work_types (name, description, created_by) VALUES (?,?,?)");
            $ins->bind_param("ssi", $name, $desc, $userId);
            if ($ins->execute()) { $successCount++; } else { $errorCount++; $errors[] = "Failed to save \'$name\'"; }
        ',
        'route' => 'con-work-types',
    ],
];

foreach ($controllers as $filename => $config) {
    $path = __DIR__ . '/app/controllers/Phase2/' . $filename;
    if (!file_exists($path)) { echo "NOT FOUND: $filename\n"; continue; }
    $content = file_get_contents($path);
    if (strpos($content, 'importCsv') !== false) { echo "SKIP (exists): $filename\n"; continue; }
    
    $route    = $config['route'];
    $logic    = $config['insert_logic'];
    $cols     = implode(', ', $config['columns']);

    $method = <<<PHP

    // ─── CSV Import Method ────────────────────────────────────────────────
    public function importCsv() {
        if (\$_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . \$GLOBALS['base_url'] . '/{$route}');
            exit;
        }
        if (!isset(\$_FILES['csv_file']) || \$_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            setError('Please upload a valid CSV file.');
            header('Location: ' . \$GLOBALS['base_url'] . '/{$route}');
            exit;
        }
        \$file = \$_FILES['csv_file']['tmp_name'];
        \$handle = fopen(\$file, 'r');
        if (!\$handle) { setError('Cannot open file.'); header('Location: ' . \$GLOBALS['base_url'] . '/{$route}'); exit; }
        \$header = array_map('trim', fgetcsv(\$handle));
        \$successCount = 0; \$errorCount = 0; \$errors = [];
        \$db = \$this->db;
        \$userId = \$_SESSION['user']['id'] ?? 0;

        while ((\$data = fgetcsv(\$handle)) !== FALSE) {
            if (empty(array_filter(\$data))) continue;
            if (count(\$header) !== count(\$data)) continue;
            \$row = array_combine(\$header, \$data);
            {$logic}
        }
        fclose(\$handle);
        if (\$errorCount > 0) {
            setError("Imported \$successCount. Failed \$errorCount. " . implode(' | ', array_slice(\$errors, 0, 5)));
        } else {
            setSuccess("Successfully imported \$successCount records.");
        }
        header('Location: ' . \$GLOBALS['base_url'] . '/{$route}');
        exit;
    }
PHP;

    // Insert before final closing brace
    $content = preg_replace('/\}\s*$/', $method . "\n}\n", $content);
    file_put_contents($path, $content);
    echo "ADDED importCsv to: $filename\n";
}
echo "Done.\n";
