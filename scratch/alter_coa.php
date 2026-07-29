<?php
$config = require __DIR__ . '/../app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$query = "ALTER TABLE chart_of_accounts ADD COLUMN parent_id INT(11) UNSIGNED NULL DEFAULT NULL AFTER id";
if ($db->query($query)) {
    echo "Column parent_id added successfully.\n";
} else {
    echo "Error adding parent_id: " . $db->error . "\n";
}

$query = "ALTER TABLE chart_of_accounts ADD CONSTRAINT fk_coa_parent FOREIGN KEY (parent_id) REFERENCES chart_of_accounts(id) ON DELETE SET NULL";
if ($db->query($query)) {
    echo "Foreign key constraint added successfully.\n";
} else {
    echo "Error adding constraint: " . $db->error . "\n";
}
