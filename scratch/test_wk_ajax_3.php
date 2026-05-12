<?php
session_start();
$_SESSION['user'] = ['id'=>1, 'role_id'=>1]; // mock admin login
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/db.php'; // or wherever it is
// Let's just bootstrap the app
$_SERVER['REQUEST_URI'] = '/con-ajax-weekly/13';
require_once __DIR__ . '/../public/index.php';
