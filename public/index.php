<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("../app/helpers/helper.php");
// Enable debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

$root = dirname(__DIR__);
$GLOBALS['base_url'] = '/TPHL-ERP';


// folder er moddhe o chaile folder kore controller nite parbo abr folder chara o controller
spl_autoload_register(function ($class) use ($root) {
    $class = str_replace('\\', '/', $class);

    $paths = [
        $root . "/app/core/$class.php",
        $root . "/app/models/$class.php",
        $root . "/app/controllers/$class.php",
    ];

    foreach ($paths as $file) {
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

// Load config
$config = require $root . "/app/config/config.php";

// Create DB
$database = new Database($config);
$db = $database->conn;

// Routes (regex for ID)
$routes = [
    ""                 => ["controller" => "LoginController", "method" => "index"],
    "admin-login"           => ["controller" => "LoginController", "method" => "index"],
    "home"                 => ["controller" => "HomeController", "method" => "index"],
    "logout"             => ["controller" => "LoginController", "method" => "logout"],


    // users
    "user-lists"           => ["controller" => "UserController", "method" => "index"],
    "create-user"          => ["controller" => "UserController", "method" => "create"],
    "store-user"          => ["controller" => "UserController", "method" => "store"],
    "edit-user/(\d+)"          => ["controller" => "UserController", "method" => "edit"],
    "user-profile/(\d+)"          => ["controller" => "UserController", "method" => "show"],
    "update-user/(\d+)"          => ["controller" => "UserController", "method" => "update"],
    "delete-user"          => ["controller" => "UserController", "method" => "delete"],


    "customer-lists"       => ["controller" => "CustomerController", "method" => "index"],
    "create-customer"      => ["controller" => "CustomerController", "method" => "create"],
    // permission
    "role-permissions"          => ["controller" => "RolePermissionController", "method" => "index"],
    "permissions"    => ["controller" => "RolePermissionController", "method" => "permission"],
    "store-permission"     => ["controller" => "RolePermissionController", "method" => "store"],
    "update-permission"     => ["controller" => "RolePermissionController", "method" => "update"],
    "delete-permission"     => ["controller" => "RolePermissionController", "method" => "delete"],
    // Roles
    "store-role"           => ["controller" => "RoleController", "method" => "store"],
    "get-role"      => ["controller" => "RoleController", "method" => "edit"],
    "update-role"   => ["controller" => "RoleController", "method" => "update"],
    //hrm

    "hrm-dashboard" => ["controller" => "HrmDashboardController", "method" => "index"],
    // Tasks

    'task-lists' => ["controller" => "TaskController", "method" => "index"],
    'create-task' => ["controller" => "TaskController", "method" => "create"],
    'store-task' => ["controller" => "TaskController", "method" => "store"],
    'show-task/(\d+)' => ["controller" => "TaskController", "method" => "show"],
    'edit-task/(\d+)' => ["controller" => "TaskController", "method" => "edit"],
    'update-task/(\d+)' => ["controller" => "TaskController", "method" => "update"],
    'delete-task/(\d+)' => ["controller" => "TaskController", "method" => "delete"],

    // department 
    'departments' => ['controller' => 'DepartmentController', 'method' => "index"],
    'store-department' => ['controller' => 'DepartmentController', 'method' => "store"],
    'update-department' => ['controller' => 'DepartmentController', 'method' => "update"],
    'delete-department' => ['controller' => 'DepartmentController', 'method' => "delete"],
    // designations 
    'designations' => ['controller' => 'DesignationController', 'method' => "index"],
    'store-designation' => ['controller' => 'DesignationController', 'method' => "store"],
    'update-designation' => ['controller' => 'DesignationController', 'method' => "update"],
    'delete-designation' => ['controller' => 'DesignationController', 'method' => "delete"],

    // employees
    "employee-lists" => ["controller" => "EmployeeController", "method" => "index"],
    "create-employee" => ["controller" => "EmployeeController", "method" => "create"],
    "store-employee" => ["controller" => "EmployeeController", "method" => "store"],
    "edit-employee/(\d+)" => ["controller" => "EmployeeController", "method" => "edit"],
    "update-employee/(\d+)" => ["controller" => "EmployeeController", "method" => "update"],
    "employee-profile/(\d+)" => ["controller" => "EmployeeController", "method" => "show"],
    "delete-employee" => ["controller" => "EmployeeController", "method" => "delete"],

    //employee dashboard
    "employee-dashboard" => ["controller" => "EmployeeDashboardController", "method" => "index"],
    "update-task-status" => ["controller" => "EmployeeDashboardController", "method" => "updateTaskStatus"],
    "update-task-progress" => ["controller" => "EmployeeDashboardController", "method" => "updateTaskProgress"],

    // leads
    "lead-lists" => ["controller" => "LeadController", "method" => "index"],
    "create-lead" => ["controller" => "LeadController", "method" => "create"],
    "store-lead" => ["controller" => "LeadController", "method" => "store"],
    "edit-lead/(\d+)" => ["controller" => "LeadController", "method" => "edit"],
    "view-lead/(\d+)" => ["controller" => "LeadController", "method" => "show"],
    "leads-import-csv" => ["controller" => "LeadController", "method" => "importCsv"],
    "update-lead/(\d+)" => ["controller" => "LeadController", "method" => "update"],
    "delete-lead/(\d+)" => ["controller" => "LeadController", "method" => "delete"],
    // leads Source
    "leadSource-lists" => ["controller" => "LeadSourceController", "method" => "index"],
    // "create-leadSource" => ["controller" => "LeadSourceController", "method" => "create"],
    "store-leadSource" => ["controller" => "LeadSourceController", "method" => "store"],
    "edit-leadSource/(\d+)" => ["controller" => "LeadSourceController", "method" => "edit"],
    "update-leadSource/(\d+)" => ["controller" => "LeadSourceController", "method" => "update"],
    "delete-leadSource/(\d+)" => ["controller" => "LeadSourceController", "method" => "delete"],

    //blogs
    "blog-lists" => ["controller" => "BlogController", "method" => "index"],
    "create-blog" => ["controller" => "BlogController", "method" => "create"],
    "store-blog" => ["controller" => "BlogController", "method" => "store"],
    "blog-details/(\d+)" => ["controller" => "BlogController", "method" => "show"],
    "edit-blog/(\d+)" => ["controller" => "BlogController", "method" => "edit"],
    "update-blog/(\d+)" => ["controller" => "BlogController", "method" => "update"],
    "delete-blog/(\d+)" => ["controller" => "BlogController", "method" => "delete"],

    //location
    "location-lists" => ["controller" => "LocationController", "method" => "index"],
    "store-location" => ["controller" => "LocationController", "method" => "store"],
    "update-location" => ["controller" => "LocationController", "method" => "update"],
    "delete-location" => ["controller" => "LocationController", "method" => "delete"],
    //Units
    "unit-lists" => ["controller" => "UnitController", "method" => "index"],
    "store-unit" => ["controller" => "UnitController", "method" => "store"],
    "update-unit" => ["controller" => "UnitController", "method" => "update"],
    "delete-unit" => ["controller" => "UnitController", "method" => "delete"],
    //community
    "community-lists" => ["controller" => "CommunityController", "method" => "index"],
    "store-community" => ["controller" => "CommunityController", "method" => "store"],
    "update-community" => ["controller" => "CommunityController", "method" => "update"],
    "delete-community" => ["controller" => "CommunityController", "method" => "delete"],

    // Leave controller 
    "leave-types" => ["controller" => "Leaves\\LeaveTypeController", "method" => "index"],
    "store-leave-type" => ["controller" => "Leaves\\LeaveTypeController", "method" => "store"],
    "update-leave-type" => ["controller" => "Leaves\\LeaveTypeController", "method" => "update"],
    "delete-leave-type" => ["controller" => "Leaves\\LeaveTypeController", "method" => "delete"],


    // Site controller 
    "slider-manage" => ["controller" => "SiteManage\\SliderController", "method" => "index"],
    "store-slider" => ["controller" => "SiteManage\\SliderController", "method" => "store"],
    // "store-leave-type" => ["controller" => "Leaves\\LeaveTypeController","method" => "store" ],

    // site-Setting 
    "site-settings" => ["controller" => "SiteManage\\SiteSettingController", "method" => "index"],
    "store-site-setting" => ["controller" => "SiteManage\\SiteSettingController", "method" => "storeOrUpdate"],
    // flat purchase rules
    "flat-purchase-rules" => ["controller" => "SiteManage\\FlatPurchaseRuleController", "method" => "index"],
    "store-flat-purchase-rule" => ["controller" => "SiteManage\\FlatPurchaseRuleController", "method" => "store"],
    "update-flat-purchase-rule" => ["controller" => "SiteManage\\FlatPurchaseRuleController", "method" => "update"],
    "delete-flat-purchase-rule" => ["controller" => "SiteManage\\FlatPurchaseRuleController", "method" => "delete"],

    //project
    "project-lists" => ["controller" => "ProjectController", "method" => "index"],
    "create-project" => ["controller" => "ProjectController", "method" => "create"],
    "store-project" => ["controller" => "ProjectController", "method" => "store"],
    "edit-project/(\d+)" => ["controller" => "ProjectController", "method" => "edit"],
    "projects-import-csv" => ["controller" => "ProjectController", "method" => "importCsv"],
    "update-project/(\d+)" => ["controller" => "ProjectController", "method" => "update"],
    "delete-project/(\d+)" => ["controller" => "ProjectController", "method" => "delete"],

    //flat
    "flat-lists" => ["controller" => "FlatController", "method" => "index"],
    "create-flat" => ["controller" => "FlatController", "method" => "create"],
    "store-flat" => ["controller" => "FlatController", "method" => "store"],
    "edit-flat/(\d+)" => ["controller" => "FlatController", "method" => "edit"],
    "flats-import-csv" => ["controller" => "FlatController", "method" => "importCsv"],
    "update-flat/(\d+)" => ["controller" => "FlatController", "method" => "update"],
    "delete-flat/(\d+)" => ["controller" => "FlatController", "method" => "delete"],

    //follow-up-leads
    "follow-up-leads/(\d+)" => ["controller" => "FollowUpController", "method" => "index"],
    "store-follow-up" => ["controller" => "FollowUpController", "method" => "store"],
    "update-follow-up" => ["controller" => "FollowUpController", "method" => "update"],
    "delete-follow-up" => ["controller" => "FollowUpController", "method" => "delete"],
    "get-flats/(\d+)" => ["controller" => "FollowUpController", "method" => "getFlats"],

    // booking
    "booking-leads/(\d+)" => ["controller" => "BookingController", "method" => "index"],
    "booking-store" => ["controller" => "BookingController", "method" => "store"],
    "get-flats-by-project/(\d+)" => ["controller" => "BookingController", "method" => "getFlatsByProject"],
    "update-flats-by-project" => ["controller" => "BookingController", "method" => "update"],


    // payments_booking
    "payments-booking/(\d+)" => ["controller" => "PaymentBookingController", "method" => "index"],
    "payment-booking-store" => ["controller" => "PaymentBookingController", "method" => "store"],
    "payment-booking-invoice/(\d+)" => ["controller" => "PaymentBookingController", "method" => "invoice"],

    //SalesDashboard

    "sales-dashboard" => ["controller" => "SalesDashboardController", "method" => "index"],


    //payrolls
    //--payslips lists
    "payslip-lists" => ["controller" => "PayrollController", "method" => "index"],
    "create-payslip" => ["controller" => "PayrollController", "method" => "create"],
    "store-payslip" => ["controller" => "PayrollController", "method" => "store"],
    // 03-01-2025
    "pay-payslip" => ["controller" => "PayrollController", "method" => "updatePay"],
    "show-payslip/(\d+)" => ["controller" => "PayrollController", "method" => "show"],
    "print-payslip/(\d+)" => ["controller" => "PayrollController", "method" => "print"],

    "download-payslip/(\d+)" => ["controller" => "PayrollController", "method" => "downloadPDF"],

    //--payslip Item
    "payslip-items" => ["controller" => "PayslipItemController", "method" => "index"],
    "store-payslip-item" => ["controller" => "PayslipItemController", "method" => "store"],
    "update-payslip-item" => ["controller" => "PayslipItemController", "method" => "update"],
    "delete-payslip-item" => ["controller" => "PayslipItemController", "method" => "delete"],


    //company bank accounts
    "company-bank-accounts" => ["controller" => "BankAccountController", "method" => "index"],




    //Rafi Start  Accounts
    // ==================== ACCOUNTS====================
    'dashboard' => ["controller" => "DashboardController", "method" => "index"],
    'account-dashboard' => ["controller" => "DashboardController", "method" => "index"],
    'dashboard/get-statistics' => ["controller" => "DashboardController", "method" => "getStatistics"],

    // ==================== CLIENT CASH IN ROUTES ====================

    "clients-cash-in" => ["controller" => "AccountClientController", "method" => "index"],
    // client's cash in history 
    "clients-cash-in-history" => ["controller" => "AccountClientController", "method" => "indexTwo"],
    "clients-cash-in-history/(\d+)" => ["controller" => "AccountClientController", "method" => "indexTwo"],
    // Add transaction route
    "add-transaction" => ["controller" => "AccountClientController", "method" => "addTransaction"],
    "edit-client-cash-in/(\d+)" => ["controller" => "AccountClientController", "method" => "showEditTransactionForm"],
    "edit-client-cash-in" => ["controller" => "AccountClientController", "method" => "showEditTransactionForm"],
    "client-cash-in-form" => ["controller" => "AccountClientController", "method" => "showAddTransactionForm"],
    "update-transaction" => ["controller" => "AccountClientController", "method" => "updateTransaction"],
    "delete-transaction" => ["controller" => "AccountClientController", "method" => "deleteTransaction"],

    // ==================== MANAGEMENT CASH IN ROUTES ====================
    'management-cash-in' => ["controller" => "AccountManagementController", "method" => "index"],
    'add-management-cash-in' => ["controller" => "AccountManagementController", "method" => "showAddForm"],
    'store-management-cash-in' => ["controller" => "AccountManagementController", "method" => "storeTransaction"],
    'edit-management-cash-in' => ["controller" => "AccountManagementController", "method" => "showEditForm"],
    'update-management-cash-in' => ["controller" => "AccountManagementController", "method" => "updateTransaction"],
    'delete-management-cash-in' => ["controller" => "AccountManagementController", "method" => "deleteTransaction"],
    'get-management-transaction-details' => ["controller" => "AccountManagementController", "method" => "getTransactionDetails"],

    // ==================== MANAGEMENT ROLES ROUTES ====================
    'management-roles' => ["controller" => "AccountManagementController", "method" => "manageRoles"],
    'get-role-details' => ["controller" => "AccountManagementController", "method" => "getRoleDetails"],
    'add-role' => ["controller" => "AccountManagementController", "method" => "addRole"],
    'update-roles' => ["controller" => "AccountManagementController", "method" => "updateRole"],
    'delete-role' => ["controller" => "AccountManagementController", "method" => "deleteRole"],
    'toggle-role-status' => ["controller" => "AccountManagementController", "method" => "toggleRoleStatus"],

    // Add these routes to your routes array

    // ==================== CLIENT INVOICE ROUTES ====================
    'payment-invoice' => ["controller" => "AccountClientController", "method" => "viewPaymentInvoice"],
    'payment-invoice/(\d+)' => ["controller" => "AccountClientController", "method" => "viewPaymentInvoice"],
    // ==================== CLIENT STATEMENT ROUTES ====================
    'client-statement' => [
        "controller" => "AccountClientController",
        "method" => "viewClientStatement"
    ],
    'client-statement/(\d+)' => [
        "controller" => "AccountClientController",
        "method" => "viewClientStatement"
    ],


    'management-cash-in-statement' => ["controller" => "AccountManagementController", "method" => "viewManagementStatement"],
    'management-cash-in-invoice' => ["controller" => "AccountManagementController", "method" => "viewTransactionInvoice"],
    'management-cash-out-statement' => ["controller" => "AccountManagementCashoutController", "method" => "showStatement"],
    'management-cash-out-invoice' => ["controller" => "AccountManagementCashoutController", "method" => "showInvoice"],
    // ==================== MANAGEMENT CASH OUT ROUTES ====================
    'management-cash-out' => ["controller" => "AccountManagementCashoutController", "method" => "index"],
    'add-management-cash-out' => ["controller" => "AccountManagementCashoutController", "method" => "showAddForm"],
    'store-management-cash-out' => ["controller" => "AccountManagementCashoutController", "method" => "storeTransaction"],
    'edit-management-cash-out' => ["controller" => "AccountManagementCashoutController", "method" => "showEditForm"],
    'update-management-cash-out' => ["controller" => "AccountManagementCashoutController", "method" => "updateTransaction"],
    'delete-management-cash-out' => ["controller" => "AccountManagementCashoutController", "method" => "deleteTransaction"],
    'get-cashout-transaction-details' => ["controller" => "AccountManagementCashoutController", "method" => "getTransactionDetails"],
    'export-management-cash-out' => ["controller" => "AccountManagementCashoutController", "method" => "exportTransactions"],


    // ==================== MANAGEMENT CATEGORIES ROUTES ====================
    'management-categories' => ["controller" => "AccountManagementCategoryController", "method" => "index"],
    'get-category-details' => ["controller" => "AccountManagementCategoryController", "method" => "getCategoryDetails"],
    'add-management-category' => ["controller" => "AccountManagementCategoryController", "method" => "addCategory"],
    'update-management-category' => ["controller" => "AccountManagementCategoryController", "method" => "updateCategory"],
    'delete-management-category' => ["controller" => "AccountManagementCategoryController", "method" => "deleteCategory"],
    'toggle-category-status' => ["controller" => "AccountManagementCategoryController", "method" => "toggleCategoryStatus"],

    //===================Customer Experience Department====================
    'clients-cashin-view' => ["controller" => "AccountClientController", "method" => "indexView"],
    'clients-cash-in-history-view' => ["controller" => "AccountClientController", "method" => "indexTwoView"],
    'clients-cash-in-history-view/(\d+)' => ["controller" => "AccountClientController", "method" => "indexTwoView"],


    // ==================== CENTRAL STATEMENT ROUTES ====================
    'central-statement' => ["controller" => "CentralStatementController", "method" => "index"],
    'central-statement-export' => ["controller" => "CentralStatementController", "method" => "exportCSV"],
    'central-statement-stats' => ["controller" => "CentralStatementController", "method" => "getQuickStats"],
    //rafi End


];

$route = trim(strtok($_SERVER["REQUEST_URI"], '?'), '/');

// Remove base folder if present
$base_folder = 'TPHL-ERP';

if (strpos($route, $base_folder) === 0) {
    $route = substr($route, strlen($base_folder));
}

$route = trim($route, '/');

// Skip routes that don't need login
$skip_routes = ["admin-login", "admin-registration", "logout"];

// কোন routes skip হবে
if (!in_array($route, $skip_routes)) {
    checkLogin();
}


$matched = false;
$params = [];

foreach ($routes as $pattern => $routeInfo) {
    if (preg_match("#^$pattern$#", $route, $matches)) {
        $controllerName = $routeInfo['controller'];
        $methodName = $routeInfo['method'];
        $params = array_slice($matches, 1); // ID captured here
        $matched = true;
        break;
    }
}

if (!$matched) {
    die("404 - Route not found: $route");
}

// Instantiate controller
$controller = new $controllerName($db);

// Call method with ID param if exists
call_user_func_array([$controller, $methodName], $params);
