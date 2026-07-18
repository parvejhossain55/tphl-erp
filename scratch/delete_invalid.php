<?php
require 'app/config/config.php';
require 'app/core/Database.php';
$db = (new Database(require 'app/config/config.php'))->conn;
$db->query('DELETE FROM erp_tracker_champions WHERE user_id = 0');
echo "Deleted.";
