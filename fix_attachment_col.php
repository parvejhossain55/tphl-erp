<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$r = $db->query("SHOW COLUMNS FROM quotations LIKE 'attachment'");
$row = $r->fetch_assoc();
echo "Column: " . ($row['Field'] ?? 'NOT FOUND') . " | Type: " . ($row['Type'] ?? 'N/A') . "\n";

// Fix to VARCHAR if it's INT
if (isset($row['Type']) && str_contains(strtolower($row['Type']), 'int')) {
    $alter = $db->query("ALTER TABLE quotations MODIFY attachment VARCHAR(255) NULL DEFAULT NULL");
    echo $alter ? "Fixed: column changed to VARCHAR(255)\n" : "Error: " . $db->error . "\n";
} else {
    echo "Type is OK\n";
}
