<?php
$config = require 'app/config/config.php';
$conn = new mysqli($config["db_host"], $config["db_user"], $config["db_pass"], $config["db_name"]);
if ($conn->connect_error)
    die("Connection failed");

$sql1 = "ALTER TABLE `letter_templates` MODIFY COLUMN `type` ENUM('appointment','joining','promotion','increment','experience','noc','show_cause','warning','caution','improvement') NOT NULL;";
$sql2 = "ALTER TABLE `letters` MODIFY COLUMN `letter_type` ENUM('appointment','joining','promotion','increment','experience','noc','salary_certificate','show_cause','warning','caution','improvement') NOT NULL;";

if ($conn->query($sql1) === TRUE)
    echo "Updated letter_templates enum\n";
else
    echo "Error updating letter_templates: " . $conn->error . "\n";

if ($conn->query($sql2) === TRUE)
    echo "Updated letters enum\n";
else
    echo "Error updating letters: " . $conn->error . "\n";
