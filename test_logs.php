<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$res = $db->query('SELECT * FROM tipsoi_email_logs');
while($row = $res->fetch_assoc()) {
    print_r($row);
}
