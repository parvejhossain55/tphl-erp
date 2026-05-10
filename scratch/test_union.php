<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
if ($db->connect_error) die('Error');

$res = $db->query("SELECT 'test' COLLATE utf8mb4_unicode_ci AS col1 UNION ALL SELECT NULL AS col1");
if (!$res) echo "Q1: " . $db->error . "\n";
else echo "Q1: OK\n";

$res2 = $db->query("SELECT 'test' COLLATE utf8mb4_unicode_ci AS col1 UNION ALL SELECT CAST(NULL AS CHAR) COLLATE utf8mb4_unicode_ci AS col1");
if (!$res2) echo "Q2: " . $db->error . "\n";
else echo "Q2: OK\n";

$res3 = $db->query("SELECT 'test' COLLATE utf8mb4_unicode_ci AS col1 UNION ALL SELECT 'test' COLLATE utf8mb4_general_ci AS col1");
if (!$res3) echo "Q3: " . $db->error . "\n";
else echo "Q3: OK\n";
