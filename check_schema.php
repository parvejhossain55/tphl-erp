<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db'); 
if ($db->connect_error) die('Connection failed');

echo "Users: ";
$res = $db->query('DESCRIBE users');
if ($res) while($row = $res->fetch_assoc()) echo $row['Field'] . ' ';
echo "\nRoles: ";
$res = $db->query('DESCRIBE roles');
if ($res) while($row = $res->fetch_assoc()) echo $row['Field'] . ' ';
echo "\nCashout Requests: ";
$res = $db->query('DESCRIBE cashout_requests');
if ($res) while($row = $res->fetch_assoc()) echo $row['Field'] . ' ';
echo "\nCashout Payments: ";
$res = $db->query('DESCRIBE cashout_payments');
if ($res) while($row = $res->fetch_assoc()) echo $row['Field'] . ' ';
