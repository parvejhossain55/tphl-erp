<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');

$files = [
    'SoilTestController.php' => "
    // ─── CSV Import Method ───────────
    public function importCsv() {
        if (\$_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . \$GLOBALS['base_url'] . '/con-dashboard');
            exit;
        }

        if (!isset(\$_FILES['csv_file']) || \$_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            setError('Please upload a valid CSV file.');
            header('Location: ' . \$GLOBALS['base_url'] . '/con-batch2-import');
            exit;
        }

        \$file = \$_FILES['csv_file']['tmp_name'];
        \$handle = fopen(\$file, 'r');
        if (\$handle === FALSE) { setError('Failed to open CSV file.'); header('Location: ' . \$GLOBALS['base_url'] . '/con-batch2-import'); exit; }

        \$header = array_map('trim', fgetcsv(\$handle));
        \$successCount = 0; \$errorCount = 0; \$errors = [];
        \$db = \$this->db;
        \$userId = \$_SESSION['user']['id'] ?? 0;

        while ((\$data = fgetcsv(\$handle)) !== FALSE) {
            if (empty(array_filter(\$data))) continue;
            \$row = array_combine(\$header, \$data);
            
            \$projectName = trim(\$row['Project_Name'] ?? '');
            \$testingAgency = trim(\$row['Testing_Agency'] ?? '');
            \$reportDate = trim(\$row['Report_Date'] ?? date('Y-m-d'));
            \$bearingCapacity = trim(\$row['Bearing_Capacity'] ?? '');
            \$reportLink = trim(\$row['Report_Link'] ?? '');

            if (empty(\$projectName)) { \$errorCount++; \$errors[] = 'Missing Project_Name'; continue; }

            \$stmt = \$db->prepare('SELECT id FROM projects WHERE name = ? AND deleted_at IS NULL LIMIT 1');
            \$stmt->bind_param('s', \$projectName); \$stmt->execute();
            \$res = \$stmt->get_result();
            if (\$res->num_rows === 0) { \$errorCount++; \$errors[] = \"Project '\$projectName' not found\"; continue; }
            \$projectId = \$res->fetch_assoc()['id'];

            \$insertData = [
                'project_id' => \$projectId,
                'testing_agency' => \$testingAgency,
                'report_date' => \$reportDate,
                'bearing_capacity' => \$bearingCapacity,
                'report_link' => \$reportLink,
                'created_by' => \$userId
            ];

            if (\$this->model->create(\$insertData)) { \$successCount++; } 
            else { \$errorCount++; \$errors[] = \"Failed to save data for Project '\$projectName'.\"; }
        }
        fclose(\$handle);
        if (\$errorCount > 0) { setError(\"Imported \$successCount. Failed \$errorCount. Errors: \" . implode(' | ', array_slice(\$errors, 0, 5))); } 
        else { setSuccess(\"Successfully imported \$successCount records.\"); }
        header('Location: ' . \$GLOBALS['base_url'] . '/con-batch2-import');
        exit;
    }
",
    'ProjectDrawingController.php' => "
    // ─── CSV Import Method ───────────
    public function importCsv() {
        if (\$_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . \$GLOBALS['base_url'] . '/con-dashboard');
            exit;
        }

        if (!isset(\$_FILES['csv_file']) || \$_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            setError('Please upload a valid CSV file.');
            header('Location: ' . \$GLOBALS['base_url'] . '/con-batch2-import');
            exit;
        }

        \$file = \$_FILES['csv_file']['tmp_name'];
        \$handle = fopen(\$file, 'r');
        if (\$handle === FALSE) { setError('Failed to open CSV file.'); header('Location: ' . \$GLOBALS['base_url'] . '/con-batch2-import'); exit; }

        \$header = array_map('trim', fgetcsv(\$handle));
        \$successCount = 0; \$errorCount = 0; \$errors = [];
        \$db = \$this->db;
        \$userId = \$_SESSION['user']['id'] ?? 0;

        while ((\$data = fgetcsv(\$handle)) !== FALSE) {
            if (empty(array_filter(\$data))) continue;
            \$row = array_combine(\$header, \$data);
            
            \$projectName = trim(\$row['Project_Name'] ?? '');
            \$designTypeName = trim(\$row['Design_Type_Name'] ?? '');
            \$version = trim(\$row['Version'] ?? '');
            \$subDate = trim(\$row['Submission_Date'] ?? date('Y-m-d'));
            \$remarks = trim(\$row['Remarks'] ?? '');

            if (empty(\$projectName) || empty(\$designTypeName)) { \$errorCount++; \$errors[] = 'Missing Project/Design Type'; continue; }

            // Project ID
            \$stmt = \$db->prepare('SELECT id FROM projects WHERE name = ? AND deleted_at IS NULL LIMIT 1');
            \$stmt->bind_param('s', \$projectName); \$stmt->execute();
            \$res = \$stmt->get_result();
            if (\$res->num_rows === 0) { \$errorCount++; \$errors[] = \"Project '\$projectName' not found\"; continue; }
            \$projectId = \$res->fetch_assoc()['id'];

            // Design Type ID
            \$stmtD = \$db->prepare('SELECT id FROM design_types WHERE name = ? LIMIT 1');
            \$stmtD->bind_param('s', \$designTypeName); \$stmtD->execute();
            \$resD = \$stmtD->get_result();
            if (\$resD->num_rows === 0) { \$errorCount++; \$errors[] = \"Design Type '\$designTypeName' not found\"; continue; }
            \$designTypeId = \$resD->fetch_assoc()['id'];

            \$insertData = [
                'project_id' => \$projectId,
                'design_type_id' => \$designTypeId,
                'version_number' => \$version,
                'submission_date' => \$subDate,
                'remarks' => \$remarks,
                'created_by' => \$userId
            ];

            if (\$this->model->create(\$insertData)) { \$successCount++; } 
            else { \$errorCount++; \$errors[] = \"Failed to save data for Project '\$projectName'.\"; }
        }
        fclose(\$handle);
        if (\$errorCount > 0) { setError(\"Imported \$successCount. Failed \$errorCount. Errors: \" . implode(' | ', array_slice(\$errors, 0, 5))); } 
        else { setSuccess(\"Successfully imported \$successCount records.\"); }
        header('Location: ' . \$GLOBALS['base_url'] . '/con-batch2-import');
        exit;
    }
",
    'ArchitectEngineeringController.php' => "
    // ─── CSV Import Method ───────────
    public function importCsv() {
        if (\$_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . \$GLOBALS['base_url'] . '/con-dashboard');
            exit;
        }

        if (!isset(\$_FILES['csv_file']) || \$_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            setError('Please upload a valid CSV file.');
            header('Location: ' . \$GLOBALS['base_url'] . '/con-batch2-import');
            exit;
        }

        \$file = \$_FILES['csv_file']['tmp_name'];
        \$handle = fopen(\$file, 'r');
        if (\$handle === FALSE) { setError('Failed to open CSV file.'); header('Location: ' . \$GLOBALS['base_url'] . '/con-batch2-import'); exit; }

        \$header = array_map('trim', fgetcsv(\$handle));
        \$successCount = 0; \$errorCount = 0; \$errors = [];
        \$db = \$this->db;
        \$userId = \$_SESSION['user']['id'] ?? 0;

        while ((\$data = fgetcsv(\$handle)) !== FALSE) {
            if (empty(array_filter(\$data))) continue;
            \$row = array_combine(\$header, \$data);
            
            \$projectName = trim(\$row['Project_Name'] ?? '');
            \$consultantName = trim(\$row['Consultant_Name'] ?? '');
            \$designStage = trim(\$row['Design_Stage'] ?? '');
            \$subDate = trim(\$row['Submission_Date'] ?? date('Y-m-d'));
            \$remarks = trim(\$row['Remarks'] ?? '');

            if (empty(\$projectName)) { \$errorCount++; \$errors[] = 'Missing Project'; continue; }

            // Project ID
            \$stmt = \$db->prepare('SELECT id FROM projects WHERE name = ? AND deleted_at IS NULL LIMIT 1');
            \$stmt->bind_param('s', \$projectName); \$stmt->execute();
            \$res = \$stmt->get_result();
            if (\$res->num_rows === 0) { \$errorCount++; \$errors[] = \"Project '\$projectName' not found\"; continue; }
            \$projectId = \$res->fetch_assoc()['id'];

            \$insertData = [
                'project_id' => \$projectId,
                'consultant_name' => \$consultantName,
                'design_stage' => \$designStage,
                'submission_date' => \$subDate,
                'remarks' => \$remarks,
                'created_by' => \$userId
            ];

            if (\$this->model->create(\$insertData)) { \$successCount++; } 
            else { \$errorCount++; \$errors[] = \"Failed to save data for Project '\$projectName'.\"; }
        }
        fclose(\$handle);
        if (\$errorCount > 0) { setError(\"Imported \$successCount. Failed \$errorCount. Errors: \" . implode(' | ', array_slice(\$errors, 0, 5))); } 
        else { setSuccess(\"Successfully imported \$successCount records.\"); }
        header('Location: ' . \$GLOBALS['base_url'] . '/con-batch2-import');
        exit;
    }
"
];

foreach ($files as $name => $snippet) {
    $path = __DIR__ . '/app/controllers/Phase2/' . $name;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        if (strpos($content, 'importCsv') === false) {
            $content = preg_replace('/\}\s*$/', $snippet . "\n}\n", $content);
            file_put_contents($path, $content);
            echo "Added importCsv to $name\n";
        } else {
            echo "Already exists in $name\n";
        }
    }
}
echo "Done.\n";
