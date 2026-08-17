<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

try { $db->query("ALTER TABLE sales_targets DROP INDEX unique_sales_project_month"); } catch(Exception $e) {}

$sql = "ALTER TABLE sales_targets 
        ADD COLUMN category_id INT NULL AFTER month,
        ADD COLUMN project_ids JSON NULL AFTER category_id";
try { $db->query($sql); } catch(Exception $e) { echo $e->getMessage() . "\n"; }

$sql2 = "ALTER TABLE sales_targets CHANGE COLUMN project_id project_id INT NULL";
try { $db->query($sql2); } catch(Exception $e) { echo $e->getMessage() . "\n"; }

echo "Schema updated successfully!";
