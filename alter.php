<?php
$db = new mysqli("127.0.0.1", "root", "", "tphl_db");
if ($db->connect_error) die('Connection failed: ' . $db->connect_error);
$sql = "ALTER TABLE leads ADD COLUMN alt_email VARCHAR(255) NULL AFTER email";
if ($db->query($sql) === TRUE) echo "Column alt_email added successfully";
else echo "Error adding column: " . $db->error;
