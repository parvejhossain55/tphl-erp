<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
$_SESSION['user'] = ['id' => 1, 'role_id' => 1];

require_once 'app/config/config.php';
// Need to include core classes if they aren't autoloaded.
// The app seems to use composer autoload or manual requires in public/index.php.
// Let's just require index.php and override the URI!
$_SERVER['REQUEST_URI'] = '/TPHL-ERP/con-purchase-pending-report';
$_SERVER['REQUEST_METHOD'] = 'GET';
require 'public/index.php';
