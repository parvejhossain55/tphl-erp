<?php
session_start();
$_SESSION['user'] = ['id'=>1, 'role_id'=>1]; // mock admin login
$_SERVER['REQUEST_URI'] = '/con-ajax-weekly/13';
require_once __DIR__ . '/../public/index.php';
