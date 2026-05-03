<?php
/**
 * Inject importCsv() into Batch 3 controllers:
 * - Con_InitialEstimateController
 * - Con_WorkingPlanController
 * - Con_TargetFixedController
 */

$db = new mysqli('localhost', 'root', '', 'tphl_db');

$controllers = [

    // ── Initial Estimate Budgets ──────────────────────────────────────────
    'Con_InitialEstimateController.php' => [
        'redirect' => 'con-estimates',
        'method'   => <<<'PHP'

    // ─── CSV Import ────────────────────────────────────────────────────────
    public function importCsv() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $GLOBALS['base_url'] . '/con-estimates'); exit;
        }
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            setError('Please upload a valid CSV file.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-estimates'); exit;
        }
        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if (!$handle) { setError('Cannot open file.'); header('Location: ' . $GLOBALS['base_url'] . '/con-estimates'); exit; }
        $header = array_map('trim', fgetcsv($handle));
        $successCount = 0; $errorCount = 0; $errors = [];
        $db = $this->db;
        $userId = $_SESSION['user']['id'] ?? 0;

        while (($data = fgetcsv($handle)) !== FALSE) {
            if (empty(array_filter($data))) continue;
            if (count($header) !== count($data)) continue;
            $row = array_combine($header, $data);

            $projectName  = trim($row['Project_Name'] ?? '');
            $workTypeName = trim($row['Work_Type_Name'] ?? '');
            $areaSqft     = (float)($row['Total_Area_Sqft'] ?? 0);
            $matCost      = (float)($row['Material_Cost_Total'] ?? 0);
            $labCost      = (float)($row['Labor_Cost_Total'] ?? 0);
            $otherCost    = (float)($row['Other_Costs'] ?? 0);
            $totalCost    = (float)($row['Total_Estimated_Cost'] ?? ($matCost + $labCost + $otherCost));
            $startDate    = trim($row['Start_Date'] ?? date('Y-m-d'));
            $endDate      = trim($row['End_Date'] ?? date('Y-m-d'));

            if (empty($projectName)) { $errorCount++; $errors[] = "Missing Project_Name"; continue; }

            // Resolve project_id
            $stmt = $db->prepare("SELECT id FROM projects WHERE name = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->bind_param('s', $projectName); $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows === 0) { $errorCount++; $errors[] = "Project '$projectName' not found"; continue; }
            $projectId = $res->fetch_assoc()['id'];

            // Resolve work_type_id (optional)
            $workTypeId = null;
            if (!empty($workTypeName)) {
                $stmtW = $db->prepare("SELECT id FROM work_types WHERE name = ? LIMIT 1");
                $stmtW->bind_param('s', $workTypeName); $stmtW->execute();
                $resW = $stmtW->get_result();
                if ($resW->num_rows > 0) $workTypeId = $resW->fetch_assoc()['id'];
            }

            // Get first architect_engineering record for this project
            $stmtA = $db->prepare("SELECT id FROM architect_engineering WHERE project_id = ? LIMIT 1");
            $stmtA->bind_param('i', $projectId); $stmtA->execute();
            $resA = $stmtA->get_result()->fetch_assoc();
            $archId = $resA ? $resA['id'] : 0;

            $ins = $db->prepare("INSERT INTO initial_estimate_budgets
                (project_id, architecture_id, work_type_id, total_area_sqft, material_cost_total, labor_cost_total, other_costs, total_estimated_cost, start_date, end_date, created_by)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $ins->bind_param('iiidddddss i', $projectId, $archId, $workTypeId, $areaSqft, $matCost, $labCost, $otherCost, $totalCost, $startDate, $endDate, $userId);

            if ($ins->execute()) { $successCount++; } else { $errorCount++; $errors[] = "DB error for '$projectName': " . $db->error; }
        }
        fclose($handle);
        if ($errorCount > 0) { setError("Imported $successCount. Failed $errorCount. " . implode(' | ', array_slice($errors, 0, 5))); }
        else { setSuccess("Successfully imported $successCount records."); }
        header('Location: ' . $GLOBALS['base_url'] . '/con-estimates'); exit;
    }
PHP
    ],

    // ── Working Plans ──────────────────────────────────────────────────────
    'Con_WorkingPlanController.php' => [
        'redirect' => 'con-working-plans',
        'method'   => <<<'PHP'

    // ─── CSV Import ────────────────────────────────────────────────────────
    public function importCsv() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $GLOBALS['base_url'] . '/con-working-plans'); exit;
        }
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            setError('Please upload a valid CSV file.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-working-plans'); exit;
        }
        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if (!$handle) { setError('Cannot open file.'); header('Location: ' . $GLOBALS['base_url'] . '/con-working-plans'); exit; }
        $header = array_map('trim', fgetcsv($handle));
        $successCount = 0; $errorCount = 0; $errors = [];
        $db = $this->db;
        $userId = $_SESSION['user']['id'] ?? 0;

        while (($data = fgetcsv($handle)) !== FALSE) {
            if (empty(array_filter($data))) continue;
            if (count($header) !== count($data)) continue;
            $row = array_combine($header, $data);

            $projectName = trim($row['Project_Name'] ?? '');
            $year        = (int)($row['Year'] ?? 0);
            $month       = (int)($row['Month'] ?? 0);
            $workDesc    = trim($row['Work_Description'] ?? '');
            $plannedQty  = (float)($row['Planned_Quantity'] ?? 0);
            $estCost     = (float)($row['Estimated_Cost'] ?? 0);

            if (empty($projectName) || !$year || !$month || empty($workDesc)) {
                $errorCount++; $errors[] = "Missing required fields in row"; continue;
            }

            $stmt = $db->prepare("SELECT id FROM projects WHERE name = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->bind_param('s', $projectName); $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows === 0) { $errorCount++; $errors[] = "Project '$projectName' not found"; continue; }
            $projectId = $res->fetch_assoc()['id'];

            // Get first initial_budget for this project
            $stmtB = $db->prepare("SELECT id FROM initial_estimate_budgets WHERE project_id = ? LIMIT 1");
            $stmtB->bind_param('i', $projectId); $stmtB->execute();
            $budgetRow = $stmtB->get_result()->fetch_assoc();
            $budgetId = $budgetRow ? $budgetRow['id'] : 0;

            $ins = $db->prepare("INSERT INTO project_working_plans
                (project_id, initial_budget_id, design_type_id, year, month, work_description, planned_quantity, estimated_cost, created_by, approved_status)
                VALUES (?,?,1,?,?,?,?,?,?,'Draft')");
            $ins->bind_param('iiissddsi', $projectId, $budgetId, $year, $month, $workDesc, $plannedQty, $estCost, $userId);

            if ($ins->execute()) { $successCount++; } else { $errorCount++; $errors[] = "DB error: " . $db->error; }
        }
        fclose($handle);
        if ($errorCount > 0) { setError("Imported $successCount. Failed $errorCount. " . implode(' | ', array_slice($errors, 0, 5))); }
        else { setSuccess("Successfully imported $successCount records."); }
        header('Location: ' . $GLOBALS['base_url'] . '/con-working-plans'); exit;
    }
PHP
    ],

    // ── Target Fixed ───────────────────────────────────────────────────────
    'Con_TargetFixedController.php' => [
        'redirect' => 'con-targets',
        'method'   => <<<'PHP'

    // ─── CSV Import ────────────────────────────────────────────────────────
    public function importCsv() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $GLOBALS['base_url'] . '/con-targets'); exit;
        }
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            setError('Please upload a valid CSV file.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-targets'); exit;
        }
        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if (!$handle) { setError('Cannot open file.'); header('Location: ' . $GLOBALS['base_url'] . '/con-targets'); exit; }
        $header = array_map('trim', fgetcsv($handle));
        $successCount = 0; $errorCount = 0; $errors = [];
        $db = $this->db;
        $userId = $_SESSION['user']['id'] ?? 0;

        while (($data = fgetcsv($handle)) !== FALSE) {
            if (empty(array_filter($data))) continue;
            if (count($header) !== count($data)) continue;
            $row = array_combine($header, $data);

            $projectName   = trim($row['Project_Name'] ?? '');
            $targetTitle   = trim($row['Target_Title'] ?? '');
            $year          = (int)($row['Year'] ?? 0);
            $month         = (int)($row['Month'] ?? 0);
            $weekNumber    = !empty($row['Week_Number']) ? (int)$row['Week_Number'] : null;
            $targetPct     = (float)($row['Target_Percentage'] ?? 0);

            if (empty($projectName) || !$year || !$month || $targetPct <= 0) {
                $errorCount++; $errors[] = "Missing required fields"; continue;
            }

            $stmt = $db->prepare("SELECT id FROM projects WHERE name = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->bind_param('s', $projectName); $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows === 0) { $errorCount++; $errors[] = "Project '$projectName' not found"; continue; }
            $projectId = $res->fetch_assoc()['id'];

            if ($weekNumber !== null) {
                $ins = $db->prepare("INSERT INTO target_fixed
                    (project_id, user_id, target_title, year, month, week_number, target_percentage, is_lab_test, approved_by_site_eng, approved_by_qc, approved_by_pc, approved_by_pd, approved_by_manager)
                    VALUES (?,?,?,?,?,?,?,0,0,0,0,0,0)");
                $ins->bind_param('iissiii d', $projectId, $userId, $targetTitle, $year, $month, $weekNumber, $targetPct);
            } else {
                $ins = $db->prepare("INSERT INTO target_fixed
                    (project_id, user_id, target_title, year, month, target_percentage, is_lab_test, approved_by_site_eng, approved_by_qc, approved_by_pc, approved_by_pd, approved_by_manager)
                    VALUES (?,?,?,?,?,?,0,0,0,0,0,0)");
                $ins->bind_param('iissii d', $projectId, $userId, $targetTitle, $year, $month, $targetPct);
            }

            if ($ins->execute()) { $successCount++; } else { $errorCount++; $errors[] = "DB error: " . $db->error; }
        }
        fclose($handle);
        if ($errorCount > 0) { setError("Imported $successCount. Failed $errorCount. " . implode(' | ', array_slice($errors, 0, 5))); }
        else { setSuccess("Successfully imported $successCount records."); }
        header('Location: ' . $GLOBALS['base_url'] . '/con-targets'); exit;
    }
PHP
    ],
];

foreach ($controllers as $filename => $cfg) {
    $path = __DIR__ . '/app/controllers/Phase2/' . $filename;
    if (!file_exists($path)) { echo "NOT FOUND: $filename\n"; continue; }
    $content = file_get_contents($path);
    if (strpos($content, 'importCsv') !== false) { echo "SKIP (exists): $filename\n"; continue; }
    $content = preg_replace('/\}\s*$/', $cfg['method'] . "\n}\n", $content);
    file_put_contents($path, $content);
    echo "ADDED importCsv to: $filename\n";
}

echo "Done.\n";
