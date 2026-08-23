<?php
$_POST['bank_name'] = 'BRAC Bank';
$_POST['account_name'] = 'TPHL';
$_POST['account_number'] = '12345';
$_POST['status'] = 'active';
$_SESSION['user']['id'] = 1;

$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');

// simulate what BankAccountController does
require 'app/core/Controller.php';
require 'app/models/CompanyBankAccount.php';
require 'app/controllers/SiteManage/BankAccountController.php';

$controller = new \SiteManage\BankAccountController($db);
$controller->storeCompanyBankAjax();
