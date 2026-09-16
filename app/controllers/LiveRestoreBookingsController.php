<?php

class LiveRestoreBookingsController extends Controller
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function run()
    {
        @set_time_limit(600);
        @ini_set('memory_limit', '512M');
        header('Content-Type: text/html; charset=utf-8');

        $dbNameRes = $this->db->query("SELECT DATABASE() as db_name");
        $currentDb = $dbNameRes ? ($dbNameRes->fetch_assoc()['db_name'] ?? 'Unknown') : 'Unknown';

        // Check counts
        $getCounts = function() {
            $counts = [];
            foreach (['lead_bookings', 'lead_payments', 'emi_schedules', 'emi_followups'] as $table) {
                $res = $this->db->query("SELECT COUNT(*) as c FROM `{$table}`");
                $counts[$table] = $res ? ($res->fetch_assoc()['c'] ?? 0) : 'N/A';
            }
            return $counts;
        };

        $countsBefore = $getCounts();
        $serverSqlPath = BASE_PATH . '/schema/custom-migration/restore_bookings_payments_emi.sql';
        $serverSqlExists = file_exists($serverSqlPath);
        $serverSqlSizeMb = $serverSqlExists ? round(filesize($serverSqlPath) / (1024 * 1024), 2) : 0;

        $action = $_POST['action'] ?? $_GET['action'] ?? null;
        $isPost = ($_SERVER['REQUEST_METHOD'] === 'POST');

        echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Live Database Restore - Bookings & Payments</title>";
        echo "<style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #0f172a; color: #f8fafc; padding: 30px; margin: 0; line-height: 1.6; }
            .container { max-width: 900px; margin: 0 auto; background: #1e293b; padding: 30px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
            h1 { color: #38bdf8; margin-top: 0; font-size: 24px; border-bottom: 2px solid #334155; padding-bottom: 12px; }
            .badge { display: inline-block; padding: 4px 10px; border-radius: 6px; font-weight: bold; font-size: 13px; }
            .badge-db { background: #0284c7; color: #fff; }
            .badge-warn { background: #eab308; color: #000; }
            .badge-success { background: #22c55e; color: #fff; }
            .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0; }
            .stat-card { background: #0f172a; padding: 15px; border-radius: 8px; border: 1px solid #334155; text-align: center; }
            .stat-val { font-size: 22px; font-weight: bold; color: #f1f5f9; }
            .stat-label { font-size: 12px; color: #94a3b8; text-transform: uppercase; }
            .btn { display: inline-block; padding: 12px 24px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; transition: all 0.2s; }
            .btn-primary { background: #0284c7; color: #fff; }
            .btn-primary:hover { background: #0369a1; }
            .btn-success { background: #16a34a; color: #fff; }
            .btn-success:hover { background: #15803d; }
            .btn-danger { background: #dc2626; color: #fff; }
            .btn-danger:hover { background: #b91c1c; }
            .box { background: #0f172a; padding: 20px; border-radius: 8px; border: 1px solid #334155; margin-bottom: 20px; }
            .log-box { background: #020617; border: 1px solid #1e293b; padding: 20px; border-radius: 8px; font-family: 'Courier New', monospace; font-size: 13px; color: #4ade80; max-height: 450px; overflow-y: auto; margin-top: 20px; white-space: pre-wrap; }
            .log-info { color: #38bdf8; }
            .log-success { color: #4ade80; font-weight: bold; }
            .log-warn { color: #fbbf24; }
            .log-err { color: #f87171; font-weight: bold; }
            .alert-danger { background: rgba(220, 38, 38, 0.15); border: 1px solid #dc2626; color: #fca5a5; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
            .input-file { background: #1e293b; border: 1px solid #475569; color: #fff; padding: 10px; border-radius: 6px; width: 100%; box-sizing: border-box; }
        </style></head><body>";

        echo "<div class='container'>";
        echo "<h1>🔄 Database Emergency Restore Tool (Bookings, Payments, EMI)</h1>";
        echo "<p>Connected Database: <span class='badge badge-db'>{$currentDb}</span></p>";

        echo "<div class='stats-grid'>";
        foreach ($countsBefore as $tbl => $cnt) {
            echo "<div class='stat-card'>";
            echo "<div class='stat-val'>" . number_format((int)$cnt) . "</div>";
            echo "<div class='stat-label'>" . htmlspecialchars($tbl) . "</div>";
            echo "</div>";
        }
        echo "</div>";

        if ($isPost && ($action === 'execute_server' || $action === 'upload_execute')) {
            echo "<div class='log-box'>";
            echo "<span class='log-info'>[START] Initiating Database Restore for {$currentDb}...</span>\n";

            $sourceFile = null;
            if ($action === 'execute_server') {
                if (!$serverSqlExists) {
                    echo "<span class='log-err'>[ERROR] Server SQL file not found at: {$serverSqlPath}</span>\n";
                    echo "</div></div></body></html>";
                    return;
                }
                $sourceFile = $serverSqlPath;
                echo "<span class='log-info'>[SOURCE] Using server file: {$serverSqlPath} ({$serverSqlSizeMb} MB)</span>\n";
            } elseif ($action === 'upload_execute') {
                if (empty($_FILES['sql_file']['tmp_name']) || !is_uploaded_file($_FILES['sql_file']['tmp_name'])) {
                    echo "<span class='log-err'>[ERROR] Please select a valid SQL file to upload!</span>\n";
                    echo "</div></div></body></html>";
                    return;
                }
                $sourceFile = $_FILES['sql_file']['tmp_name'];
                $uploadSizeMb = round(filesize($sourceFile) / (1024 * 1024), 2);
                echo "<span class='log-info'>[SOURCE] Uploaded file received: {$_FILES['sql_file']['name']} ({$uploadSizeMb} MB)</span>\n";
            }

            $startTime = microtime(true);
            $handle = fopen($sourceFile, 'r');
            if (!$handle) {
                echo "<span class='log-err'>[ERROR] Unable to open SQL source file.</span>\n";
                echo "</div></div></body></html>";
                return;
            }

            $this->db->query("SET FOREIGN_KEY_CHECKS = 0");
            $this->db->query("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");

            $stmtCount = 0;
            $errorCount = 0;
            $currentStmt = '';

            while (($line = fgets($handle)) !== false) {
                $trimmed = trim($line);
                if ($trimmed === '' || strpos($trimmed, '--') === 0) {
                    continue;
                }
                $currentStmt .= $line;
                if (substr($trimmed, -1) === ';') {
                    $queryToRun = trim($currentStmt);
                    $currentStmt = '';

                    if ($queryToRun !== '') {
                        $stmtCount++;
                        $res = $this->db->query($queryToRun);
                        if (!$res) {
                            $errorCount++;
                            $err = $this->db->error;
                            $preview = substr(str_replace(["\r", "\n"], ' ', $queryToRun), 0, 70);
                            echo "<span class='log-err'>[QUERY #{$stmtCount} ERROR] {$err} | SQL: {$preview}...</span>\n";
                        } else {
                            if (stripos($queryToRun, 'TRUNCATE') === 0 || stripos($queryToRun, 'INSERT INTO') === 0 || stripos($queryToRun, 'UPDATE flats') === 0) {
                                $preview = substr(str_replace(["\r", "\n"], ' ', $queryToRun), 0, 80);
                                echo "<span class='log-info'>[STEP #{$stmtCount}] OK: {$preview}...</span>\n";
                                @ob_flush();
                                @flush();
                            }
                        }
                    }
                }
            }
            fclose($handle);

            $this->db->query("SET FOREIGN_KEY_CHECKS = 1");

            $timeTaken = round(microtime(true) - $startTime, 2);
            $countsAfter = $getCounts();

            echo "\n<span class='log-success'>======================================================</span>\n";
            echo "<span class='log-success'>[SUCCESS] Restore completed in {$timeTaken} seconds!</span>\n";
            echo "<span class='log-info'>Queries Executed: {$stmtCount} | Errors: {$errorCount}</span>\n";
            echo "<span class='log-success'>======================================================</span>\n";
            echo "</div>";

            echo "<h3 style='color:#38bdf8; margin-top:25px;'>Updated Database Counts:</h3>";
            echo "<div class='stats-grid'>";
            foreach ($countsAfter as $tbl => $cnt) {
                echo "<div class='stat-card'>";
                echo "<div class='stat-val' style='color:#4ade80;'>" . number_format((int)$cnt) . "</div>";
                echo "<div class='stat-label'>" . htmlspecialchars($tbl) . "</div>";
                echo "</div>";
            }
            echo "</div>";

            echo "<div style='margin-top:25px; display:flex; gap:15px;'>";
            echo "<a href='/TPHL-ERP/clients-cash-in' target='_blank' class='btn btn-primary'>View Clients Cash-In</a>";
            echo "<a href='/TPHL-ERP/lead-lists' target='_blank' class='btn btn-success'>View Lead Lists</a>";
            echo "<a href='/TPHL-ERP/sales-dashboard' target='_blank' class='btn btn-primary'>View Sales Dashboard</a>";
            echo "<a href='/TPHL-ERP/run-restore-bookings-payments' class='btn' style='background:#475569; color:#fff;'>Back to Restore Tool</a>";
            echo "</div>";

        } else {
            echo "<div class='alert-danger'>";
            echo "<strong>⚠️ Warning:</strong> This operation will <strong>TRUNCATE</strong> and re-populate the tables: <code>lead_bookings</code>, <code>lead_payments</code>, <code>emi_schedules</code>, and <code>emi_followups</code> from the backup file, and re-sync flat booked statuses.";
            echo "</div>";

            // Method A: Execute Server File
            echo "<div class='box'>";
            echo "<h3 style='margin-top:0; color:#38bdf8;'>Method 1: Run Server Backup File (Recommended)</h3>";
            if ($serverSqlExists) {
                echo "<p>Found backup file on server: <code>schema/custom-migration/restore_bookings_payments_emi.sql</code> ({$serverSqlSizeMb} MB)</p>";
                echo "<form method='POST' onsubmit='return confirm(\"Are you sure you want to restore bookings, payments and EMI schedules on {$currentDb}?\");'>";
                echo "<input type='hidden' name='action' value='execute_server'>";
                echo "<button type='submit' class='btn btn-success'>🚀 Execute Server Backup File Now</button>";
                echo "</form>";
            } else {
                echo "<p style='color:#fbbf24;'>Server backup file not found at: <code>{$serverSqlPath}</code></p>";
                echo "<p>You can upload the file below directly from your computer.</p>";
            }
            echo "</div>";

            // Method B: Upload File
            echo "<div class='box'>";
            echo "<h3 style='margin-top:0; color:#38bdf8;'>Method 2: Upload SQL File from Your Computer</h3>";
            echo "<p>Select <code>restore_bookings_payments_emi.sql</code> from your local PC to upload and execute on the live server:</p>";
            echo "<form method='POST' enctype='multipart/form-data' onsubmit='return confirm(\"Are you sure you want to upload and restore this SQL file to {$currentDb}?\");'>";
            echo "<input type='hidden' name='action' value='upload_execute'>";
            echo "<div style='margin-bottom: 15px;'><input type='file' name='sql_file' accept='.sql' required class='input-file'></div>";
            echo "<button type='submit' class='btn btn-primary'>📤 Upload & Execute Restore</button>";
            echo "</form>";
            echo "</div>";
        }

        echo "</div></body></html>";
    }
}
