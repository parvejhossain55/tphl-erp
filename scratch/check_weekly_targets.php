<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
$res = $db->query("SELECT id, monthly_target_qty, week_1_qty, week_2_qty, week_3_qty, week_4_qty FROM phase_monthly_target_items WHERE target_id = 1");
print_r($res->fetch_all(MYSQLI_ASSOC));
