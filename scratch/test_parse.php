<?php
session_start();
$_SESSION['user'] = ['id' => 1];

require 'core/Model.php';
require 'app/models/CashoutRequest.php';
$db = new mysqli('localhost','root','','tphl_db');
$m = new CashoutRequest($db);

$res = $m->parseCSV('dummy_cashouts_test.csv');
print_r($res['errors']);
?>
