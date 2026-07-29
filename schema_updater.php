<?php
$conn = new mysqli("127.0.0.1", "root", "", "tphl_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Helper function to add column if not exists
function addColumnIfNotExists($conn, $table, $column, $definition) {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($result && $result->num_rows == 0) {
        $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
        if ($conn->query($sql) === TRUE) {
            echo "Added $column to $table\n";
        } else {
            echo "Error adding $column to $table: " . $conn->error . "\n";
        }
    } else {
        echo "Column $column already exists in $table\n";
    }
}

// 1. con_material_categories
addColumnIfNotExists($conn, 'con_material_categories', 'gl_account_id', 'INT NULL DEFAULT NULL');

// 2. work_types
addColumnIfNotExists($conn, 'work_types', 'gl_account_id', 'INT NULL DEFAULT NULL');

// We might also need expense categories mapped
addColumnIfNotExists($conn, 'expense_categories', 'gl_account_id', 'INT NULL DEFAULT NULL');

$conn->close();
echo "Schema Update Complete!\n";
