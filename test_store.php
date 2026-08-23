<?php
require 'app/config/config.php';
require 'app/core/Model.php';
require 'app/core/Controller.php';
require 'app/models/CompanyBankAccount.php';
require 'app/controllers/SiteManage/BankAccountController.php';
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$c = new \SiteManage\BankAccountController($db);
$_POST['bank_name']='test';
$_POST['account_name']='test';
$_POST['account_number']='123';
$_POST['status']='active';
$_SESSION['user']['id']=1;
$c->storeCompanyBankAjax();
