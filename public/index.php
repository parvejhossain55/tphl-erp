<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// error_reporting(E_ALL);
// ini_set('display_errors', 0);

// mysqli_report(MYSQLI_REPORT_OFF);

// for producttion error hide 
require_once("../app/helpers/helper.php");
require_once __DIR__ . '/../vendor/autoload.php';
// Enable debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

$root = dirname(__DIR__);
$GLOBALS['base_url'] = '/TPHL-ERP';


spl_autoload_register(function ($class) use ($root) {
    $class = str_replace('\\', '/', $class);

    $paths = [
        $root . "/app/core/$class.php",
        $root . "/app/models/$class.php",
        $root . "/app/controllers/$class.php",
        $root . "/app/services/$class.php",
        $root . "/app/helpers/$class.php",
        $root . "/app/ai/$class.php"
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
    "" => ["controller" => "LoginController", "method" => "index"],
    "admin-login" => ["controller" => "LoginController", "method" => "index"],
    "home" => ["controller" => "HomeController", "method" => "index"],
    "approve-request/(\d+)" => ["controller" => "HomeController", "method" => "approveFull"],
    "partial-approve-request/(\d+)" => ["controller" => "HomeController", "method" => "approvePartial"],
    "reject-request/(\d+)" => ["controller" => "HomeController", "method" => "reject"],
    "show-request/(\d+)" => ["controller" => "HomeController", "method" => "show"],
    "logout" => ["controller" => "LoginController", "method" => "logout"],

    // Approval Request Controller
    // Approval Requests
    "approval-requests" => ["controller" => "ApprovalRequestController", "method" => "index"],

    //End Approval Request Controller


    // users
    "user-lists" => ["controller" => "UserController", "method" => "index"],
    "create-user" => ["controller" => "UserController", "method" => "create"],
    "store-user" => ["controller" => "UserController", "method" => "store"],
    "edit-user/(\d+)" => ["controller" => "UserController", "method" => "edit"],
    "user-profile/(\d+)" => ["controller" => "UserController", "method" => "show"],
    "update-user/(\d+)" => ["controller" => "UserController", "method" => "update"],
    "delete-user" => ["controller" => "UserController", "method" => "delete"],
    "my-dashboard" => ["controller" => "UserDashboardController", "method" => "dashboard"],
    "today-leads" => ["controller" => "UserDashboardController", "method" => "todayLead"],
    "update-call-status" => ["controller" => "UserDashboardController", "method" => "updateCallStatus"],
    "update-call-notes" => ["controller" => "UserDashboardController", "method" => "updateCallNote"],
    "today-follow-up" => ["controller" => "UserDashboardController", "method" => "todayFollowUp"],
    "upcoming-follow-up" => ["controller" => "UserDashboardController", "method" => "myUpcomingFollowLeads"],
    "my-onboarding-projects" => ["controller" => "UserDashboardController", "method" => "myOnboardProject"],
    "my-all-assigned-leads" => ["controller" => "UserDashboardController", "method" => "myAssignedLeads"],
    "follow-up/update-status-note" => ["controller" => "UserDashboardController", "method" => "updateFollowUpStatusNote"],
    "overdue-next-followup" => ["controller" => "UserDashboardController", "method" => "updateFollowUpStatusNote"],


    "customer-lists" => ["controller" => "CustomerController", "method" => "index"],
    "create-customer" => ["controller" => "CustomerController", "method" => "create"],
    // permission
    "role-permissions" => ["controller" => "RolePermissionController", "method" => "index"],
    "permissions" => ["controller" => "RolePermissionController", "method" => "permission"],
    "store-permission" => ["controller" => "RolePermissionController", "method" => "store"],
    "update-permission" => ["controller" => "RolePermissionController", "method" => "update"],
    "delete-permission" => ["controller" => "RolePermissionController", "method" => "delete"],
    // Roles
    "store-role" => ["controller" => "RoleController", "method" => "store"],
    "get-role" => ["controller" => "RoleController", "method" => "edit"],
    "update-role" => ["controller" => "RoleController", "method" => "update"],
    //================== hrm ========================

    "hrm-dashboard" => ["controller" => "HrmDashboardController", "method" => "index"],
    "calendar-load" => ["controller" => "HrmDashboardController", "method" => "calenderLoad"],
    // Tasks

    'task-lists' => ["controller" => "TaskController", "method" => "index"],
    'create-task' => ["controller" => "TaskController", "method" => "create"],
    'store-task' => ["controller" => "TaskController", "method" => "store"],
    'task-dashboard' => ["controller" => "TaskController", "method" => "taskDashboard"],
    "update-task-status" => ["controller" => "TaskController", "method" => "updateTaskStatus"],
    "update-task-progress" => ["controller" => "TaskController", "method" => "updateTaskProgress"],
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
    "my-attendance" => ["controller" => "EmployeeDashboardController", "method" => "myAttendance"],
    "my-leaves" => ["controller" => "EmployeeDashboardController", "method" => "myLeaves"],
    "my-profile" => ["controller" => "EmployeeDashboardController", "method" => "myProfile"],
    "update-task-status-employee" => ["controller" => "EmployeeDashboardController", "method" => "updateTaskStatus"],
    "update-task-progress-employee" => ["controller" => "EmployeeDashboardController", "method" => "updateTaskProgress"],

    // attendance 
    "attendance-list" => ["controller" => "EmpAttendanceController", "method" => "index"],
    "create-attendance" => ["controller" => "EmpAttendanceController", "method" => "create"],
    "store-attendance" => ["controller" => "EmpAttendanceController", "method" => "store"],
    "show-attendance/(\d+)" => ["controller" => "EmpAttendanceController", "method" => "show"],
    "edit-attendance/(\d+)" => ["controller" => "EmpAttendanceController", "method" => "edit"],
    "update-attendance/(\d+)" => ["controller" => "EmpAttendanceController", "method" => "update"],
    "delete-attendance" => ["controller" => "EmpAttendanceController", "method" => "delete"],
    "download-excel" => ["controller" => "EmpAttendanceController", "method" => "downloadExcel"],
    "download-pdf" => ["controller" => "EmpAttendanceController", "method" => "downloadPDF"],
    "mobile-punch" => ["controller" => "EmpAttendanceController", "method" => "createMobilePuch"],
    "store-self-mobile-punch" => ["controller" => "EmpAttendanceController", "method" => "storeMobilePunch"],

    //==== Report
    "daily-attendance-report" => ["controller" => "AttendanceReportController", "method" => "attendanceReport"],
    "export-daily-report" => ["controller" => "AttendanceReportController", "method" => "exportDailyReport"],
    "export-daily-report-pdf" => ["controller" => "AttendanceReportController", "method" => "exportDailyReportPDF"],

    // late report 
     "daily-late-report" => ["controller" => "LateReportController", "method" => "lateReport"],
     "export-late-report" => ["controller" => "LateReportController", "method" => "exportLateReport"],
     "export-late-report-pdf" => ["controller" => "LateReportController", "method" => "exportLateReportPDF"],

    // leave report 
     "leave-report" => ["controller" => "LeaveReportController", "method" => "leaveReport"],
     "export-leave-report" => ["controller" => "LeaveReportController", "method" => "exportLeaveReport"],
     "export-leave-report-pdf" => ["controller" => "LeaveReportController", "method" => "exportLeaveReportPDF"],

    // absent report 
     "absent-report" => ["controller" => "AbsentReportController", "method" => "absentReport"],
     "export-absent-report" => ["controller" => "AbsentReportController", "method" => "exportAbsentReport"],
     "export-absent-report-pdf" => ["controller" => "AbsentReportController", "method" => "exportAbsentReportPDF"],

    // mobile punch report 
     "mobile-punch-report" => ["controller" => "MobilePunchReportController", "method" => "mobilePunchReport"],
     "export-mobile-punch-report" => ["controller" => "MobilePunchReportController", "method" => "exportMobilePunchReport"],
     "export-mobile-punch-report-pdf" => ["controller" => "MobilePunchReportController", "method" => "exportMobilePunchReportPDF"],

    // === ALL REPORTS MODULE ===
    "job-card-report" => ["controller" => "JobCardReportController", "method" => "index"],
    "export-job-card-csv" => ["controller" => "JobCardReportController", "method" => "exportCSV"],
    "export-job-card-pdf" => ["controller" => "JobCardReportController", "method" => "exportPDF"],

    "hrm-daily-report" => ["controller" => "HrmDailyReportController", "method" => "index"],
    "export-hrm-daily-csv" => ["controller" => "HrmDailyReportController", "method" => "exportCSV"],
    "export-hrm-daily-pdf" => ["controller" => "HrmDailyReportController", "method" => "exportPDF"],

    "hrm-monthly-report" => ["controller" => "HrmMonthlyReportController", "method" => "index"],
    "export-hrm-monthly-csv" => ["controller" => "HrmMonthlyReportController", "method" => "exportCSV"],
    "export-hrm-monthly-pdf" => ["controller" => "HrmMonthlyReportController", "method" => "exportPDF"],

    "dept-wise-report" => ["controller" => "HrmDepartmentReportController", "method" => "index"],
    "export-hrm-dept-csv" => ["controller" => "HrmDepartmentReportController", "method" => "exportCSV"],
    "export-hrm-dept-pdf" => ["controller" => "HrmDepartmentReportController", "method" => "exportPDF"],


    //=== End Report

    // Employee Leave Controller
    "leave-lists" => ["controller" => "LeaveController", "method" => "index"],
    "create-employee-leave" => ["controller" => "LeaveController", "method" => "create"],
    "store-employee-leave" => ["controller" => "LeaveController", "method" => "store"],
    "remaining-days" => ["controller" => "LeaveController", "method" => "remainingDays"],

    // self leave 
    "store-self-leave-request" => ["controller" => "LeaveController", "method" => "store"],
    "create-self-leave-request" => ["controller" => "LeaveController", "method" => "createSelf"],
    "remaining-days-self" => ["controller" => "LeaveController", "method" => "remainingDaysSelf"],
    "holiday-lists" => ["controller" => "HolidayController", "method" => "index"],
    
    // Day Off Controller
    "day-off" => ["controller" => "DayOffController", "method" => "dayOffManage"],
    "get-employee-days-off/(\d+)" => ["controller" => "DayOffController", "method" => "getEmployeeDaysOff"],
    "update-day-off" => ["controller" => "DayOffController", "method" => "updateDayOff"],
    
    // "update-task-progress" => ["controller" => "EmployeeDashboardController", "method" => "updateTaskProgress"],


    //==================END hrm ========================
    // leads
    "lead-lists" => ["controller" => "LeadController", "method" => "index"],
    "create-lead" => ["controller" => "LeadController", "method" => "create"],
    "store-lead" => ["controller" => "LeadController", "method" => "store"],
    "store-location-lead" => ["controller" => "LeadController", "method" => "storeLocation"],
    "store-source-lead" => ["controller" => "LeadController", "method" => "storeSource"],
    "edit-lead/(\d+)" => ["controller" => "LeadController", "method" => "edit"],
    "view-lead/(\d+)" => ["controller" => "LeadController", "method" => "show"],
    "leads-import-csv" => ["controller" => "LeadController", "method" => "importCsv"],
    "onboarding-clients" => ["controller" => "LeadController", "method" => "showOnboardClient"],
    "update-lead/(\d+)" => ["controller" => "LeadController", "method" => "update"],
    "update-last_leadAction/(\d+)" => ["controller" => "LeadController", "method" => "updateLastleadAction"],

    "lead-delete" => ["controller" => "LeadController", "method" => "delete"],
    "assign-multiple-leads" => ["controller" => "LeadController", "method" => "assignMultipleLeads"],


    // == access onboard clients ==

    "onboard-access-client-lists" => ["controller" => "OnboardClientController", "method" => "index"],
    "create-onboard-client" => ["controller" => "OnboardClientController", "method" => "create"],
    "store-onboard-client" => ["controller" => "OnboardClientController", "method" => "store"],

    "edit-onboard-client/(\d+)" => ["controller" => "OnboardClientController", "method" => "edit"],
    "update-onboard-client/(\d+)" => ["controller" => "OnboardClientController", "method" => "update"],

    "get-lead-booking-details/(\d+)" => ["controller" => "OnboardClientController", "method" => "getLeadBookingDetails"],
    "assign-onboard-client" => ["controller" => "OnboardClientController", "method" => "assignMultipleLeads"],



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


    //Others Site controller 
    "slider-manage" => ["controller" => "SiteManage\\SliderController", "method" => "index"],
    "store-slider" => ["controller" => "SiteManage\\SliderController", "method" => "store"],
    "update-slider" => ["controller" => "SiteManage\\SliderController", "method" => "update"],
    "delete-slider" => ["controller" => "SiteManage\\SliderController", "method" => "delete"],
    // "store-leave-type" => ["controller" => "Leaves\\LeaveTypeController","method" => "store" ],

    // site-Setting 
    "site-settings" => ["controller" => "SiteManage\\SiteSettingController", "method" => "index"],
    "store-site-setting" => ["controller" => "SiteManage\\SiteSettingController", "method" => "storeOrUpdate"],

    // flat purchase rules
    "flat-purchase-rules" => ["controller" => "SiteManage\\FlatPurchaseRuleController", "method" => "index"],
    "store-flat-purchase-rule" => ["controller" => "SiteManage\\FlatPurchaseRuleController", "method" => "store"],
    "update-flat-purchase-rule" => ["controller" => "SiteManage\\FlatPurchaseRuleController", "method" => "update"],
    "delete-flat-purchase-rule" => ["controller" => "SiteManage\\FlatPurchaseRuleController", "method" => "delete"],

    //company bank accounts
    "company-bank-accounts" => ["controller" => "SiteManage\\BankAccountController", "method" => "index"],
    "store-company-bank-accounts" => ["controller" => "SiteManage\\BankAccountController", "method" => "store"],
    "update-company-bank-accounts" => ["controller" => "SiteManage\\BankAccountController", "method" => "update"],
    "delete-company-bank-accounts" => ["controller" => "SiteManage\\BankAccountController", "method" => "delete"],
    //company bank accounts
    "review-lists" => ["controller" => "ReviewController", "method" => "index"],
    "create-review" => ["controller" => "ReviewController", "method" => "create"],
    "store-review" => ["controller" => "ReviewController", "method" => "store"],
    "edit-review/(\d+)" => ["controller" => "ReviewController", "method" => "edit"],
    "update-review/(\d+)" => ["controller" => "ReviewController", "method" => "update"],
    "delete-review" => ["controller" => "ReviewController", "method" => "delete"],
    // ==========SALES & BUISENESS======//

    //project
    "project-lists" => ["controller" => "ProjectController", "method" => "index"],
    "create-project" => ["controller" => "ProjectController", "method" => "create"],
    "store-project" => ["controller" => "ProjectController", "method" => "store"],
    "edit-project/(\d+)" => ["controller" => "ProjectController", "method" => "edit"],
    "project-details/(\d+)" => ["controller" => "ProjectController", "method" => "show"],
    "update-project/(\d+)" => ["controller" => "ProjectController", "method" => "update"],
    "delete-project" => ["controller" => "ProjectController", "method" => "delete"],

    //flat
    "flat-lists" => ["controller" => "FlatController", "method" => "index"],
    "create-flat" => ["controller" => "FlatController", "method" => "create"],
    "store-flat" => ["controller" => "FlatController", "method" => "store"],
    "edit-flat/(\d+)" => ["controller" => "FlatController", "method" => "edit"],
    "flat-details/(\d+)" => ["controller" => "FlatController", "method" => "show"],
    "update-flat/(\d+)" => ["controller" => "FlatController", "method" => "update"],
    "delete-flat" => ["controller" => "FlatController", "method" => "delete"],

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

    //Sales Head Dashboard

    "sales-dashboard" => ["controller" => "SalesDashboardController", "method" => "index"],
    "inventory-lists" => ["controller" => "ProjectInventoryController", "method" => "index"],
    "sales-executive-teams" => ["controller" => "SalesDashboardController", "method" => "salesTeam"],
    "set-sales-target/(\d+)" => ["controller" => "SalesDashboardController", "method" => "salesTarget"],
    "save-sales-target/(\d+)" => ["controller" => "SalesDashboardController", "method" => "storeSalesTarget"],
    "update-sales-target" => ["controller" => "SalesDashboardController", "method" => "updateSalesTarget"],

    //Sales Team Head Dashboard

    "sales-team-head-dashboard" => ["controller" => "SalesHeadDashboardController", "method" => "index"],
    "sales-team-view" => ["controller" => "SalesHeadDashboardController", "method" => "salesTeamView"],
    "sales-report" => ["controller" => "SalesHeadDashboardController", "method" => "salesReport"],

    // // conatct 
    "contact-lists" => ["controller" => "ContactController", "method" => "index"],
    "contacts/update-status" => ["controller" => "ContactController", "method" => "updateStatus"],

    // ==========END SALES & BUISENESS======//


    //======payrolls===========//
    //--payslips lists
    "payslip-lists" => ["controller" => "PayrollController", "method" => "index"],
    "create-payslip" => ["controller" => "PayrollController", "method" => "create"],
    "store-payslip" => ["controller" => "PayrollController", "method" => "store"],
    // 03-01-2025
    "pay-payslip" => ["controller" => "PayrollController", "method" => "updatePay"],
    "show-payslip/(\d+)" => ["controller" => "PayrollController", "method" => "show"],
    "print-payslip/(\d+)" => ["controller" => "PayrollController", "method" => "print"],
    "get-due-employees" => ["controller" => "PayrollController", "method" => "dueEmployeePayslip"],


    //--payslip Item
    "payslip-items" => ["controller" => "PayslipItemController", "method" => "index"],
    "store-payslip-item" => ["controller" => "PayslipItemController", "method" => "store"],
    "update-payslip-item" => ["controller" => "PayslipItemController", "method" => "update"],
    "delete-payslip-item" => ["controller" => "PayslipItemController", "method" => "delete"],
    //======payrolls==========//

    // ====== Camera Controller ======= //
    "camera-lists" => ["controller" => "CameraController", "method" => "index"],
    "create-camera" => ["controller" => "CameraController", "method" => "create"],
    "store-camera" => ["controller" => "CameraController", "method" => "store"],


    //===== AI ChatBox Generate====//
    "ai-chatbox" => ["controller" => "Ai\\ChatController", "method" => "handleMessage"],
    // =========== Whatsapp marketing ============
    "whatsapp-dashboard" => ["controller" => "WhatsappMarketing\\WhatsappDashboardController", "method" => "dashboard"],
    "update-campaignAction/(\d+)" => ["controller" => "WhatsappMarketing\\WhatsappDashboardController", "method" => "updateCampaignAction"],
    "contacts-whatsapp" => ["controller" => "WhatsappMarketing\\WhatsappDashboardController", "method" => "contactLeads"],
    "report-campaign" => ["controller" => "WhatsappMarketing\\WhatsappDashboardController", "method" => "reports"],

    //==== Whatsapp campaign route==========
    "campaign-contacts" => ["controller" => "WhatsappMarketing\\WhatsappCampaignController", "method" => "index"],
    "store-campaign" => ["controller" => "WhatsappMarketing\\WhatsappCampaignController", "method" => "store"],
    //Rafi Start  Accounts
    // ==================== ACCOUNTS====================
    'dashboard' => ["controller" => "DashboardController", "method" => "index"],
    'accounts-dashboard' => ["controller" => "DashboardController", "method" => "index"],
    'dashboard/get-statistics' => ["controller" => "DashboardController", "method" => "getStatistics"],

    // ==================== CLIENT CASH IN ROUTES ====================

    "clients-cash-in" => ["controller" => "AccountClientController", "method" => "index"],
    "get-client-lands" => [
        "controller" => "AccountClientController",
        "method" => "getClientLands"
    ],
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

    // ========================CED Routes====================================
    // Add this to your routes array (around line 510-520 would be good)
    'ced-dashboard' => ["controller" => "AccountClientController", "method" => "CedView"],
    // ========================CED Routes====================================


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
    'booking-invoice' => ["controller" => "AccountClientController", "method" => "viewBookingInvoice"],
    'booking-invoice/(\d+)' => ["controller" => "AccountClientController", "method" => "viewBookingInvoice"],
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
    'projects-cashout' => ["controller" => "ProjectCashoutController", "method" => "index"],


    // ==================== MANAGEMENT CATEGORIES ROUTES ====================
    'management-categories' => ["controller" => "AccountManagementCategoryController", "method" => "index"],
    'get-category-details' => ["controller" => "AccountManagementCategoryController", "method" => "getCategoryDetails"],
    'add-management-category' => ["controller" => "AccountManagementCategoryController", "method" => "addCategory"],
    'update-management-category' => ["controller" => "AccountManagementCategoryController", "method" => "updateCategory"],
    'delete-management-category' => ["controller" => "AccountManagementCategoryController", "method" => "deleteCategory"],
    'toggle-category-status' => ["controller" => "AccountManagementCategoryController", "method" => "toggleCategoryStatus"],
    'add-management-role' => ["controller" => "AccountManagementController", "method" => "addRole"],
    'update-management-role' => ["controller" => "AccountManagementController", "method" => "updateRole"],
    'delete-management-role' => ["controller" => "AccountManagementController", "method" => "deleteRole"],

    //===================Customer Experience Department====================
    'clients-cashin-view' => ["controller" => "AccountClientController", "method" => "indexView"],
    'clients-cash-in-history-view' => ["controller" => "AccountClientController", "method" => "indexTwoView"],
    'clients-cash-in-history-view/(\d+)' => ["controller" => "AccountClientController", "method" => "indexTwoView"],


    // ==================== CENTRAL STATEMENT ROUTES ====================
    'central-statement' => ["controller" => "CentralStatementController", "method" => "index"],
    'central-statement-store' => ["controller" => "CentralStatementController", "method" => "store"],
    'central-statement-update' => ["controller" => "CentralStatementController", "method" => "update"],
    'central-statement-delete' => ["controller" => "CentralStatementController", "method" => "delete"],
    'central-statement-print' => ["controller" => "CentralStatementController", "method" => "print"],
    'central-statement-export' => ["controller" => "CentralStatementController", "method" => "exportCSV"],
    'central-statement-card-totals' => ["controller" => "CentralStatementController", "method" => "getCardTotals"],
    'central-statement-stats' => ["controller" => "CentralStatementController", "method" => "getQuickStats"],
    'central-statement-get' => ["controller" => "CentralStatementController", "method" => "getStatement"],



    // Add these to your routes.php or wherever you define routes
    // Cashout Request Routes
    "cashout-requests" => ["controller" => "CashoutRequestController", "method" => "index"],
    "create-cashout-request" => ["controller" => "CashoutRequestController", "method" => "create"],
    "store-cashout-request" => ["controller" => "CashoutRequestController", "method" => "store"],
    "view-cashout-request" => ["controller" => "CashoutRequestController", "method" => "show"],
    "delete-cashout-request" => ["controller" => "CashoutRequestController", "method" => "delete"],

    // Admin routes
    "admin-cashout-requests" => ["controller" => "CashoutRequestController", "method" => "adminIndex"],
    "admin-view-cashout-request" => ["controller" => "CashoutRequestController", "method" => "adminShow"],

    // Simplified approval route
    "approve-cashout-request" => ["controller" => "CashoutRequestController", "method" => "approve"],

    // Rejection route
    "reject-cashout-request" => ["controller" => "CashoutRequestController", "method" => "reject"],

    // Payment routes
    "add-cashout-payment" => ["controller" => "CashoutRequestController", "method" => "addPayment"],
    "delete-cashout-payment" => ["controller" => "CashoutRequestController", "method" => "deletePayment"],

    // Utility routes
    "download-cashout-attachment" => ["controller" => "CashoutRequestController", "method" => "downloadAttachment"],
    "export-cashout-requests" => ["controller" => "CashoutRequestController", "method" => "export"],
    "sync-cashout-approvals" => ["controller" => "CashoutRequestController", "method" => "syncAllApprovals"],

    //rafi End


];

$route = trim(strtok($_SERVER["REQUEST_URI"], '?'), '/');

$base_folder = 'TPHL-ERP';
if (strpos($route, $base_folder) === 0) {
    $route = substr($route, strlen($base_folder));
}
$route = trim($route, '/');

/**
 *  GLOBAL AUTH CHECK
 */
$public_routes = [
    '',
    'admin-login',
    'logout',
    'admin-registration'
];

if (!in_array($route, $public_routes)) {
    checkLogin();
} else {
    // logged in user cannot see login page
    if (!empty($_SESSION['user']['id']) && ($route === '' || $route === 'admin-login')) {
        redirectByRole();
    }
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
