<?php

$liveFile  = __DIR__ . '/../schema/custom-migration/tphl_db.sql';
$localFile = __DIR__ . '/../schema/tphl_db2.sql';
$outputSql = __DIR__ . '/../schema/db_live_patch.sql';

if (!file_exists($liveFile)) {
    die("Live SQL file not found at: $liveFile\n");
}
if (!file_exists($localFile)) {
    die("Local SQL file not found at: $localFile\n");
}

echo "Parsing local database schema (Reference)...\n";
$localTables = parseSqlSchema($localFile);

echo "Parsing live server database schema (Target)...\n";
$liveTables = parseSqlSchema($liveFile);

echo "Comparing schemas to find what is missing in the live database...\n";
$missingTables = [];
$missingColumns = [];

// Find missing tables and columns in Live compared to Local
foreach ($localTables as $tableName => $tableData) {
    if (!isset($liveTables[$tableName])) {
        $missingTables[$tableName] = $tableData['full_create_statement'];
    } else {
        foreach ($tableData['columns'] as $colName => $colDef) {
            if (!isset($liveTables[$tableName]['columns'][$colName])) {
                $missingColumns[$tableName][$colName] = $colDef;
            }
        }
    }
}

// Generate SQL patch file
$patchContent = "-- TPHL-ERP Live Server Database Patch Script\n";
$patchContent .= "-- Generated to update live database (tphl_db.sql) to match local database (tphl_db2.sql)\n";
$patchContent .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";

$patchContent .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

if (!empty($missingTables)) {
    $patchContent .= "-- =========================================================================\n";
    $patchContent .= "-- MISSING TABLES TO BE CREATED ON LIVE\n";
    $patchContent .= "-- =========================================================================\n\n";
    foreach ($missingTables as $tableName => $createStatement) {
        $patchContent .= "-- Table: $tableName\n";
        $patchContent .= $createStatement . ";\n\n";
    }
}

if (!empty($missingColumns)) {
    $patchContent .= "-- =========================================================================\n";
    $patchContent .= "-- MISSING COLUMNS TO BE ADDED ON LIVE\n";
    $patchContent .= "-- =========================================================================\n\n";
    foreach ($missingColumns as $tableName => $columns) {
        $patchContent .= "-- Table: $tableName\n";
        foreach ($columns as $colName => $colDef) {
            $patchContent .= "ALTER TABLE `$tableName` ADD COLUMN `$colName` $colDef;\n";
        }
        $patchContent .= "\n";
    }
}

$patchContent .= "SET FOREIGN_KEY_CHECKS = 1;\n";

file_put_contents($outputSql, $patchContent);

// Display summary in terminal
echo "\n--- Comparison Summary (Missing in Live) ---\n";
echo "Total Local Tables: " . count($localTables) . "\n";
echo "Total Live Tables: " . count($liveTables) . "\n";
echo "Missing Tables on Live: " . count($missingTables) . "\n";
foreach (array_keys($missingTables) as $mt) {
    echo "  - $mt\n";
}
echo "Tables with Missing Columns on Live: " . count($missingColumns) . "\n";
foreach ($missingColumns as $tbl => $cols) {
    echo "  - Table '$tbl' is missing columns: " . implode(', ', array_keys($cols)) . "\n";
}

echo "\nSQL Patch written successfully to:\n$outputSql\n";


// Helper to parse SQL schema dump
function parseSqlSchema($filePath) {
    $handle = fopen($filePath, 'r');
    if (!$handle) return [];

    $tables = [];
    $currentTable = null;
    $tableLines = [];

    while (($line = fgets($handle)) !== false) {
        // Detect CREATE TABLE
        if (preg_match('/CREATE TABLE\s+`([^`]+)`/i', $line, $matches)) {
            $currentTable = $matches[1];
            $tables[$currentTable] = [
                'columns' => [],
                'full_create_statement' => $line
            ];
            $tableLines = [];
            continue;
        }

        if ($currentTable !== null) {
            $tableLines[] = $line;

            // Check end of CREATE TABLE
            if (preg_match('/^\s*\)\s*ENGINE\s*=/i', $line) || trim($line) === ');') {
                $tables[$currentTable]['full_create_statement'] .= implode("", $tableLines);
                $currentTable = null;
                continue;
            }

            // Detect column
            if (preg_match('/^\s*`([^`]+)`\s+(.+)$/', $line, $matches)) {
                $colName = $matches[1];
                $colDef  = rtrim(trim($matches[2]), ',');
                $tables[$currentTable]['columns'][$colName] = $colDef;
            }
        }
    }
    fclose($handle);
    return $tables;
}
