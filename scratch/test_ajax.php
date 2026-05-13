<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
$_SESSION['user'] = ['id' => 1, 'permissions' => ['view lead']];

$_SERVER['REQUEST_URI'] = '/TPHL-ERP/ajax-flat-lists';
$_GET['draw'] = 1;
$_GET['start'] = 0;
$_GET['length'] = 1000;
$_GET['search'] = ['value' => ''];
$_GET['order'] = [['column' => 0, 'dir' => 'desc']];

chdir(__DIR__ . '/../public');
require_once __DIR__ . '/../public/index.php';
$controller = new FlatController($db);
$controller->ajaxFlatList();
