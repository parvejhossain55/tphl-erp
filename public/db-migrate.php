<?php
/**
 * Database Schema Migration / Sync Script for TPHL-ERP Phase 2
 * This script syncs database tables and columns between local and live VPS environments.
 * Usage: Upload to live server and navigate to: http://<your-domain>/db-migrate.php
 * WARNING: Delete this file after successful execution for security reasons.
 */

// Error reporting for migration feedback
error_reporting(E_ALL);
ini_set('display_errors', 1);

$root = dirname(__DIR__);
$configPath = $root . "/app/config/config.php";

if (!file_exists($configPath)) {
    die("Error: Config file not found at " . htmlspecialchars($configPath));
}

// Load database credentials
$config = require $configPath;
$dbHost = $config['db_host'] ?? 'localhost';
$dbUser = $config['db_user'] ?? 'root';
$dbPass = $config['db_pass'] ?? '';
$dbName = $config['db_name'] ?? '';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Database Sync & Migration</title>
    <link href='https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap' rel='stylesheet'>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; padding: 40px; margin: 0; line-height: 1.5; }
        .container { max-width: 900px; margin: 0 auto; background: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); border: 1px solid #e2e8f0; overflow: hidden; }
        .header { background: #0f172a; color: #ffffff; padding: 24px 32px; display: flex; align-items: center; justify-content: space-between; }
        .header h1 { font-size: 20px; font-weight: 800; margin: 0; }
        .header .badge { background: #3b82f6; font-size: 11px; font-weight: 700; padding: 4px 12px; border-radius: 9999px; text-transform: uppercase; }
        .content { padding: 32px; }
        .log-section { margin-bottom: 24px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
        .log-title { background: #f1f5f9; padding: 12px 18px; font-weight: 700; font-size: 13.5px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; }
        .log-body { padding: 16px 18px; font-family: monospace; font-size: 12.5px; background: #fafafa; white-space: pre-wrap; color: #334155; }
        .success { color: #15803d; font-weight: 600; }
        .error { color: #b91c1c; font-weight: 600; background: #fef2f2; padding: 2px 6px; border-radius: 4px; }
        .info { color: #2563eb; }
        .footer { background: #f8fafc; padding: 20px 32px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; text-align: center; }
        .btn-delete { background: #ef4444; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer; text-decoration: none; }
    </style>
</head>
<body>

<div class='container'>
    <div class='header'>
        <h1>Database Migration Tool</h1>
        <span class='badge'>Schema Sync</span>
    </div>
    <div class='content'>";

echo "<h3>Connecting to database: <code style='background:#f1f5f9;padding:2px 6px;border-radius:4px;'>$dbName</code> on <code style='background:#f1f5f9;padding:2px 6px;border-radius:4px;'>$dbHost</code></h3>";

// Establish Database Connection
$db = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($db->connect_error) {
    echo "<div class='error' style='padding:16px;'>Database Connection Failed: " . htmlspecialchars($db->connect_error) . "</div>";
    echo "</div></div></body></html>";
    exit;
}

$db->set_charset("utf8mb4");

// Helper function to check if table exists
function tableExists($db, $tableName) {
    $result = $db->query("SHOW TABLES LIKE '" . $db->real_escape_string($tableName) . "'");
    return ($result && $result->num_rows > 0);
}

// Helper function to check if column exists
function columnExists($db, $tableName, $columnName) {
    if (!tableExists($db, $tableName)) return false;
    $result = $db->query("SHOW COLUMNS FROM `" . $db->real_escape_string($tableName) . "` LIKE '" . $db->real_escape_string($columnName) . "'");
    return ($result && $result->num_rows > 0);
}

// Helper function to check constraint exists
function constraintExists($db, $tableName, $constraintName) {
    $result = $db->query("
        SELECT CONSTRAINT_NAME 
        FROM information_schema.TABLE_CONSTRAINTS 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = '" . $db->real_escape_string($tableName) . "' 
          AND CONSTRAINT_NAME = '" . $db->real_escape_string($constraintName) . "'
    ");
    return ($result && $result->num_rows > 0);
}

// Helper function to run query and return log
function executeMigrationQuery($db, $sql, $description) {
    echo "Executing: <strong class='info'>$description</strong>...\n";
    if ($db->query($sql)) {
        echo "<span class='success'>[SUCCESS]</span> Query run successfully.\n\n";
        return true;
    } else {
        echo "<span class='error'>[FAILED]</span> Error: " . $db->error . "\n\n";
        return false;
    }
}

// ---------------------------------------------------------
// SECTION 1: initial_estimate_budgets Table Fixes
// ---------------------------------------------------------
echo "<div class='log-section'>
        <div class='log-title'><span>1. Table: initial_estimate_budgets</span></div>
        <div class='log-body'>";

if (tableExists($db, 'initial_estimate_budgets')) {
    $budgetColumns = [
        'work_type_id'       => "bigint(20) unsigned DEFAULT NULL AFTER architecture_id",
        'budget_link'        => "varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL AFTER other_costs",
        'work_details'       => "text COLLATE utf8mb4_general_ci DEFAULT NULL AFTER budget_link",
        'other_cost_detail'  => "text COLLATE utf8mb4_general_ci DEFAULT NULL AFTER work_details",
        'pm_approved_by'     => "int DEFAULT NULL AFTER status_reason",
        'pm_approval_date'   => "datetime DEFAULT NULL AFTER pm_approved_by",
        'mgmt_approved_by'   => "int DEFAULT NULL AFTER pm_approval_date",
        'mgmt_approval_date' => "datetime DEFAULT NULL AFTER mgmt_approved_by",
    ];

    foreach ($budgetColumns as $col => $definition) {
        if (!columnExists($db, 'initial_estimate_budgets', $col)) {
            $sql = "ALTER TABLE `initial_estimate_budgets` ADD COLUMN `$col` $definition";
            executeMigrationQuery($db, $sql, "Adding column '$col' to 'initial_estimate_budgets'");
        } else {
            echo "Column '$col' already exists in 'initial_estimate_budgets'.\n";
        }
    }
} else {
    echo "<span class='error'>Error: Table 'initial_estimate_budgets' does not exist in the database!</span>\n";
}

echo "</div></div>";

// ---------------------------------------------------------
// SECTION 2: initial_estimate_items and labors Table Fixes
// ---------------------------------------------------------
echo "<div class='log-section'>
        <div class='log-title'><span>2. Tables: initial_estimate_items & labors</span></div>
        <div class='log-body'>";

if (tableExists($db, 'initial_estimate_items')) {
    if (!columnExists($db, 'initial_estimate_items', 'work_type_id')) {
        $sql = "ALTER TABLE `initial_estimate_items` ADD COLUMN `work_type_id` bigint(20) unsigned DEFAULT NULL AFTER design_type_id";
        executeMigrationQuery($db, $sql, "Adding column 'work_type_id' to 'initial_estimate_items'");
    } else {
        echo "Column 'work_type_id' already exists in 'initial_estimate_items'.\n";
    }
}

if (tableExists($db, 'initial_estimate_labors')) {
    if (!columnExists($db, 'initial_estimate_labors', 'work_type_id')) {
        $sql = "ALTER TABLE `initial_estimate_labors` ADD COLUMN `work_type_id` bigint(20) unsigned DEFAULT NULL";
        executeMigrationQuery($db, $sql, "Adding column 'work_type_id' to 'initial_estimate_labors'");
    } else {
        echo "Column 'work_type_id' already exists in 'initial_estimate_labors'.\n";
    }
}

echo "</div></div>";

// ---------------------------------------------------------
// SECTION 2.5: project_phases and project_drawings Table Fixes
// ---------------------------------------------------------
echo "<div class='log-section'>
        <div class='log-title'><span>2.5. Tables: project_phases & project_drawings</span></div>
        <div class='log-body'>";

if (tableExists($db, 'project_phases')) {
    if (!columnExists($db, 'project_phases', 'phase_type')) {
        $sql = "ALTER TABLE `project_phases` ADD COLUMN `phase_type` enum('pre_construction','construction','finishing') COLLATE utf8mb4_general_ci DEFAULT 'construction' AFTER updated_at";
        executeMigrationQuery($db, $sql, "Adding column 'phase_type' to 'project_phases'");
    } else {
        echo "Column 'phase_type' already exists in 'project_phases'.\n";
    }
} else {
    echo "<span class='error'>Error: Table 'project_phases' does not exist!</span>\n";
}

if (tableExists($db, 'project_drawings')) {
    if (!columnExists($db, 'project_drawings', 'phase_id')) {
        $sql = "ALTER TABLE `project_drawings` ADD COLUMN `phase_id` int(10) unsigned DEFAULT NULL COMMENT 'Optional construction phase link' AFTER project_id";
        executeMigrationQuery($db, $sql, "Adding column 'phase_id' to 'project_drawings'");
    } else {
        echo "Column 'phase_id' already exists in 'project_drawings'.\n";
    }
}

echo "</div></div>";

// ---------------------------------------------------------
// SECTION 3: phase_monthly_targets Table Fixes
// ---------------------------------------------------------
echo "<div class='log-section'>
        <div class='log-title'><span>3. Table: phase_monthly_targets</span></div>
        <div class='log-body'>";

if (!tableExists($db, 'phase_monthly_targets')) {
    $sql = "CREATE TABLE `phase_monthly_targets` (
      `id` int unsigned NOT NULL AUTO_INCREMENT,
      `project_id` int NOT NULL,
      `phase_id` int unsigned NOT NULL,
      `target_month` date NOT NULL COMMENT 'First day of month e.g. 2026-08-01',
      `target_month_number` tinyint NOT NULL COMMENT '1-12',
      `target_year` year NOT NULL,
      `total_target_weight_pct` decimal(5,2) DEFAULT NULL COMMENT '% of total project this month contributes',
      `planning_status` enum('Draft','In Review','Approved') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Draft',
      `responsible_team` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
      `contractor_id` int unsigned DEFAULT NULL,
      `created_by` int DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uk_pmt_month` (`project_id`,`phase_id`,`target_month`),
      KEY `idx_pmt_phase` (`phase_id`),
      KEY `fk_pmt_contractor` (`contractor_id`),
      CONSTRAINT `fk_pmt_contractor` FOREIGN KEY (`contractor_id`) REFERENCES `contractors` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
      CONSTRAINT `fk_pmt_phase` FOREIGN KEY (`phase_id`) REFERENCES `project_phases` (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Module 3: Monthly target plan header';";
    
    executeMigrationQuery($db, $sql, "Creating table 'phase_monthly_targets'");
} else {
    echo "Table 'phase_monthly_targets' already exists.\n";
    
    // Check missing columns
    if (!columnExists($db, 'phase_monthly_targets', 'contractor_id')) {
        $sql = "ALTER TABLE `phase_monthly_targets` ADD COLUMN `contractor_id` int unsigned DEFAULT NULL AFTER responsible_team";
        executeMigrationQuery($db, $sql, "Adding column 'contractor_id' to 'phase_monthly_targets'");
        
        // Add foreign key constraint
        if (!constraintExists($db, 'phase_monthly_targets', 'fk_pmt_contractor')) {
            $sql = "ALTER TABLE `phase_monthly_targets` ADD CONSTRAINT `fk_pmt_contractor` FOREIGN KEY (`contractor_id`) REFERENCES `contractors` (`id`) ON DELETE SET NULL ON UPDATE CASCADE";
            executeMigrationQuery($db, $sql, "Adding foreign key 'fk_pmt_contractor' to 'phase_monthly_targets'");
        }
    } else {
        echo "Column 'contractor_id' already exists in 'phase_monthly_targets'.\n";
    }
}

echo "</div></div>";

// ---------------------------------------------------------
// SECTION 4: phase_monthly_target_items Table Fixes
// ---------------------------------------------------------
echo "<div class='log-section'>
        <div class='log-title'><span>4. Table: phase_monthly_target_items</span></div>
        <div class='log-body'>";

if (!tableExists($db, 'phase_monthly_target_items')) {
    $sql = "CREATE TABLE `phase_monthly_target_items` (
      `id` int unsigned NOT NULL AUTO_INCREMENT,
      `target_id` int unsigned NOT NULL,
      `work_type_id` bigint unsigned DEFAULT NULL COMMENT 'Optional ref to work_types for categorization',
      `work_item_name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
      `phase_item_id` int DEFAULT NULL,
      `floor_type` enum('Basement','Ground_Floor','Typical_Floor','Roof') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Typical_Floor',
      `floor_no` int DEFAULT NULL,
      `unit` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'CFT / MT / SFT / Bags / Nos / Days',
      `monthly_target_qty` decimal(12,2) DEFAULT NULL,
      `actual_qty` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Actual quantity achieved',
      `planned_progress_pct` decimal(5,2) NOT NULL DEFAULT '0.00',
      `actual_progress_pct` decimal(5,2) NOT NULL DEFAULT '0.00',
      `recovery_notes` text COLLATE utf8mb4_general_ci,
      `start_date` date DEFAULT NULL,
      `end_date` date DEFAULT NULL,
      `responsible_team` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
      `contractor_id` int unsigned DEFAULT NULL,
      `priority` enum('High','Medium','Low') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Medium',
      `status` enum('Draft','Ready','Approved','At Risk','Critical') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Draft',
      `week_1_qty` decimal(12,2) NOT NULL DEFAULT '0.00',
      `week_2_qty` decimal(12,2) NOT NULL DEFAULT '0.00',
      `week_3_qty` decimal(12,2) NOT NULL DEFAULT '0.00',
      `week_4_qty` decimal(12,2) NOT NULL DEFAULT '0.00',
      `sort_order` int NOT NULL DEFAULT '0',
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_pmti_target` (`target_id`),
      KEY `fk_pmti_contractor` (`contractor_id`),
      KEY `idx_pmti_floor` (`floor_no`),
      CONSTRAINT `fk_pmti_contractor` FOREIGN KEY (`contractor_id`) REFERENCES `contractors` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
      CONSTRAINT `fk_pmti_target` FOREIGN KEY (`target_id`) REFERENCES `phase_monthly_targets` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Module 3: Work items under each monthly target plan';";

    executeMigrationQuery($db, $sql, "Creating table 'phase_monthly_target_items'");
} else {
    echo "Table 'phase_monthly_target_items' already exists.\n";
    
    // Check columns
    $itemColumns = [
        'actual_qty'          => "decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Actual quantity achieved' AFTER monthly_target_qty",
        'actual_progress_pct' => "decimal(5,2) NOT NULL DEFAULT '0.00' AFTER planned_progress_pct",
        'contractor_id'       => "int unsigned DEFAULT NULL AFTER responsible_team",
    ];

    foreach ($itemColumns as $col => $definition) {
        if (!columnExists($db, 'phase_monthly_target_items', $col)) {
            $sql = "ALTER TABLE `phase_monthly_target_items` ADD COLUMN `$col` $definition";
            executeMigrationQuery($db, $sql, "Adding column '$col' to 'phase_monthly_target_items'");
            
            if ($col === 'contractor_id' && !constraintExists($db, 'phase_monthly_target_items', 'fk_pmti_contractor')) {
                $sql = "ALTER TABLE `phase_monthly_target_items` ADD CONSTRAINT `fk_pmti_contractor` FOREIGN KEY (`contractor_id`) REFERENCES `contractors` (`id`) ON DELETE SET NULL ON UPDATE CASCADE";
                executeMigrationQuery($db, $sql, "Adding foreign key 'fk_pmti_contractor' to 'phase_monthly_target_items'");
            }
        } else {
            echo "Column '$col' already exists in 'phase_monthly_target_items'.\n";
        }
    }
}

echo "</div></div>";

// ---------------------------------------------------------
// SECTION 5: phase_weekly_progress_items Table Fixes
// ---------------------------------------------------------
echo "<div class='log-section'>
        <div class='log-title'><span>5. Table: phase_weekly_progress_items</span></div>
        <div class='log-body'>";

if (tableExists($db, 'phase_weekly_progress_items')) {
    $progressColumns = [
        '1st_day_qty'   => "decimal(12,2) NOT NULL DEFAULT '0.00' AFTER achieved_qty",
        '2nd_day_qty'   => "decimal(12,2) NOT NULL DEFAULT '0.00' AFTER 1st_day_qty",
        '3rd_day_qty'   => "decimal(12,2) NOT NULL DEFAULT '0.00' AFTER 2nd_day_qty",
        '4th_day_qty'   => "decimal(12,2) NOT NULL DEFAULT '0.00' AFTER 3rd_day_qty",
        '5th_day_qty'   => "decimal(12,2) NOT NULL DEFAULT '0.00' AFTER 4th_day_qty",
        '6_th_day_qty'  => "decimal(12,2) NOT NULL DEFAULT '0.00' AFTER 5th_day_qty",
        '7th_day_qty'   => "decimal(12,2) NOT NULL DEFAULT '0.00' AFTER 6_th_day_qty",
        'contractor_id' => "int unsigned DEFAULT NULL AFTER responsible_team",
    ];

    foreach ($progressColumns as $col => $definition) {
        if (!columnExists($db, 'phase_weekly_progress_items', $col)) {
            $sql = "ALTER TABLE `phase_weekly_progress_items` ADD COLUMN `$col` $definition";
            executeMigrationQuery($db, $sql, "Adding column '$col' to 'phase_weekly_progress_items'");
            
            if ($col === 'contractor_id' && !constraintExists($db, 'phase_weekly_progress_items', 'fk_pwpi_contractor')) {
                $sql = "ALTER TABLE `phase_weekly_progress_items` ADD CONSTRAINT `fk_pwpi_contractor` FOREIGN KEY (`contractor_id`) REFERENCES `contractors` (`id`) ON DELETE SET NULL ON UPDATE CASCADE";
                executeMigrationQuery($db, $sql, "Adding foreign key 'fk_pwpi_contractor' to 'phase_weekly_progress_items'");
            }
        } else {
            echo "Column '$col' already exists in 'phase_weekly_progress_items'.\n";
        }
    }
} else {
    echo "Table 'phase_weekly_progress_items' does not exist yet (skipped).\n";
}

echo "</div></div>";

// ---------------------------------------------------------
// SECTION 6: Additional Phase 2 / VPS Schema Fixes
// ---------------------------------------------------------
echo "<div class='log-section'>
        <div class='log-title'><span>6. Additional Phase 2 / VPS Schema Sync</span></div>
        <div class='log-body'>";

// projects
if (tableExists($db, 'projects')) {
    if (!columnExists($db, 'projects', 'workflow_version')) {
        $sql = "ALTER TABLE `projects` ADD COLUMN `workflow_version` enum('legacy', 'phase_based') NOT NULL DEFAULT 'legacy' AFTER `name`";
        executeMigrationQuery($db, $sql, "Adding 'workflow_version' to 'projects'");
    } else {
        echo "Column 'workflow_version' already exists in 'projects'.\n";
    }
}

// work_types
if (tableExists($db, 'work_types')) {
    if (!columnExists($db, 'work_types', 'default_phase_name')) {
        $sql = "ALTER TABLE `work_types` ADD COLUMN `default_phase_name` varchar(150) DEFAULT NULL AFTER `design_type_id`";
        executeMigrationQuery($db, $sql, "Adding 'default_phase_name' to 'work_types'");
    } else {
        echo "Column 'default_phase_name' already exists in 'work_types'.\n";
    }
}

// boq_master
if (tableExists($db, 'boq_master')) {
    if (!columnExists($db, 'boq_master', 'target_id')) {
        $sql = "ALTER TABLE `boq_master` ADD COLUMN `target_id` int(11) DEFAULT NULL AFTER `created_at`";
        executeMigrationQuery($db, $sql, "Adding 'target_id' to 'boq_master'");
    } else {
        echo "Column 'target_id' already exists in 'boq_master'.\n";
    }
    if (!columnExists($db, 'boq_master', 'design_type_id')) {
        $sql = "ALTER TABLE `boq_master` ADD COLUMN `design_type_id` int(11) DEFAULT NULL AFTER `target_id`";
        executeMigrationQuery($db, $sql, "Adding 'design_type_id' to 'boq_master'");
    } else {
        echo "Column 'design_type_id' already exists in 'boq_master'.\n";
    }
}

// daily_work_item_logs
if (tableExists($db, 'daily_work_item_logs')) {
    if (!columnExists($db, 'daily_work_item_logs', 'work_type_id')) {
        $sql = "ALTER TABLE `daily_work_item_logs` ADD COLUMN `work_type_id` bigint(20) unsigned DEFAULT NULL AFTER `target_item_id`";
        executeMigrationQuery($db, $sql, "Adding 'work_type_id' to 'daily_work_item_logs'");
    } else {
        echo "Column 'work_type_id' already exists in 'daily_work_item_logs'.\n";
    }
    if (!columnExists($db, 'daily_work_item_logs', 'item_status')) {
        $sql = "ALTER TABLE `daily_work_item_logs` ADD COLUMN `item_status` varchar(30) NOT NULL DEFAULT 'In Progress' AFTER `remarks`";
        executeMigrationQuery($db, $sql, "Adding 'item_status' to 'daily_work_item_logs'");
    } else {
        echo "Column 'item_status' already exists in 'daily_work_item_logs'.\n";
    }
    if (!columnExists($db, 'daily_work_item_logs', 'responsible_team')) {
        $sql = "ALTER TABLE `daily_work_item_logs` ADD COLUMN `responsible_team` varchar(100) DEFAULT NULL AFTER `item_status`";
        executeMigrationQuery($db, $sql, "Adding 'responsible_team' to 'daily_work_item_logs'");
    } else {
        echo "Column 'responsible_team' already exists in 'daily_work_item_logs'.\n";
    }
}

// phase_weekly_progress
if (tableExists($db, 'phase_weekly_progress')) {
    $pwpColumns = [
        'qc_approved_id' => "int unsigned DEFAULT NULL AFTER `material_received`",
        'qc_status'      => "varchar(50) NOT NULL DEFAULT 'Pending' AFTER `qc_approved_id`",
        'qc_image'       => "varchar(255) DEFAULT NULL AFTER `qc_status`",
        'qc_comment'     => "text DEFAULT NULL AFTER `qc_image`",
        'pm_approved_id' => "int unsigned DEFAULT NULL AFTER `qc_comment`",
        'pm_status'      => "varchar(50) NOT NULL DEFAULT 'Pending' AFTER `pm_approved_id`",
        'pm_comment'     => "text DEFAULT NULL AFTER `pm_status`"
    ];
    foreach ($pwpColumns as $col => $definition) {
        if (!columnExists($db, 'phase_weekly_progress', $col)) {
            $sql = "ALTER TABLE `phase_weekly_progress` ADD COLUMN `$col` $definition";
            executeMigrationQuery($db, $sql, "Adding column '$col' to 'phase_weekly_progress'");
        } else {
            echo "Column '$col' already exists in 'phase_weekly_progress'.\n";
        }
    }
}

echo "</div></div>";

// Close database connection
$db->close();

echo "
        <h2 style='color:#15803d;'>Sync & Migration Completed Successfully!</h2>
        <p>Your live database has been updated with the missing tables and columns required for Phase 2 construction modules.</p>
        <p style='background:#fee2e2; border: 1px solid #fca5a5; padding: 12px 18px; border-radius: 8px; font-size:13px; color:#991b1b;'>
            <strong>IMPORTANT:</strong> For security reasons, please delete this file (<code>public/db-migrate.php</code>) from the live server immediately after testing!
        </p>
    </div>
    <div class='footer'>
        TPHL-ERP Database Sync Utility &bull; Run on " . date('Y-m-d H:i:s') . "
    </div>
</div>

</body>
</html>";
