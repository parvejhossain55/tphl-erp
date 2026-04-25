<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 0);

mysqli_report(MYSQLI_REPORT_OFF);

// for producttion error hide 
require_once("../app/helpers/helper.php");
require_once __DIR__ . '/../vendor/autoload.php';
//// Maintenance Mode Check
// if (file_exists(__DIR__ . '/maintenance.flag')) {
//     http_response_code(503); // Service Unavailable
//     echo "<h1 style=\"color: #333; text-align: center;\">Site is under maintenance</h1><p style=\"color: #666; text-align: center;\">We'll be back soon!</p>";
//     exit;
// }

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Enable debug
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

$root = dirname(__DIR__);
$GLOBALS['base_url'] = '/TPHL-ERP';

spl_autoload_register(function ($class) use ($root) {
    $class = str_replace('\\', '/', $class);

    $paths = [
        $root . "/app/core/$class.php",
        $root . "/app/models/$class.php",
        $root . "/app/controllers/$class.php",
        $root . "/app/controllers/Phase2/$class.php",   // Phase 2 controllers
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
    "update-communication-type" => ["controller" => "UserDashboardController", "method" => "updateCommunicationStatus"],
    "update-lead-status" => [
        "controller" => "UserDashboardController",
        "method" => "updateLeadStatus"
    ],
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
    "employees-excel-download" => ["controller" => "EmployeeController", "method" => "exportExcel"],
    "employees-pdf-download" => ["controller" => "EmployeeController", "method" => "exportPDF"],

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
    "department-kpi" => ["controller" => "HrmDepartmentReportController", "method" => "departmentKPIReport"],
    "department-kpi-pdf" => ["controller" => "HrmDepartmentReportController", "method" => "departmentKPIReportPDF"],

    // Letter Templates CRUD
    "letter-templates" => ["controller" => "LetterTemplateController", "method" => "template"],
    "store-letter-template" => ["controller" => "LetterTemplateController", "method" => "storeLetterTemplate"],
    "edit-letter-template/(\d+)" => ["controller" => "LetterTemplateController", "method" => "edit"],
    "update-letter-template/(\d+)" => ["controller" => "LetterTemplateController", "method" => "update"],
    "delete-letter-template/(\d+)" => ["controller" => "LetterTemplateController", "method" => "delete"],

    // Generate employee letter
    "generate-letter" => ["controller" => "LetterTemplateController", "method" => "generateLetter"],
    "create-letter-template" => ["controller" => "LetterTemplateController", "method" => "create"],
    "store-generate-letter" => ["controller" => "LetterTemplateController", "method" => "store"],
    "get-employee-info-ajax" => ["controller" => "LetterTemplateController", "method" => "getEmployeeInfo"],
    "get-templates-for-type" => ["controller" => "LetterTemplateController", "method" => "getTemplatesForType"],
    "get-template" => ["controller" => "LetterTemplateController", "method" => "getTemplate"],
    "view-letter/(\d+)" => ["controller" => "LetterTemplateController", "method" => "viewLetter"],
    "print-letter/(\d+)" => ["controller" => "LetterTemplateController", "method" => "printLetter"],
    "pdf-letter/(\d+)" => ["controller" => "LetterTemplateController", "method" => "generatePDFLetter"],
    "delete-letter/(\d+)" => ["controller" => "LetterTemplateController", "method" => "deleteLetter"],
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

    // holiday controller
    "holiday-lists" => ["controller" => "HolidayController", "method" => "index"],
    "store-holiday" => ["controller" => "HolidayController", "method" => "store"],
    "update-holiday" => ["controller" => "HolidayController", "method" => "update"],
    "delete-holiday" => ["controller" => "HolidayController", "method" => "delete"],

    // Day Off Controller
    "day-off" => ["controller" => "DayOffController", "method" => "dayOffManage"],
    "get-employee-days-off/(\d+)" => ["controller" => "DayOffController", "method" => "getEmployeeDaysOff"],
    "update-day-off" => ["controller" => "DayOffController", "method" => "updateDayOff"],


    // "update-task-progress" => ["controller" => "EmployeeDashboardController", "method" => "updateTaskProgress"],


    //==================END hrm ========================


    // ================ Manpower Requisition ================

    "requisition-lists" => ["controller" => "ManpowerRequisitionController", "method" => "index"],
    "create-requisition" => ["controller" => "ManpowerRequisitionController", "method" => "create"],
    "store-requisition" => ["controller" => "ManpowerRequisitionController", "method" => "store"],
    "view-requisition/(\d+)" => ["controller" => "ManpowerRequisitionController", "method" => "find"],
    // ================END Manpower Requisition ================



    // ============ employee task sheets
    "store-update-task" => ["controller" => "EmployeeTaskController", "method" => "save"],
    "task-sheets" => ["controller" => "EmployeeTaskController", "method" => "index"],
    // leads
    "lead-lists" => ["controller" => "LeadController", "method" => "index"],
    "leads-ajax-data" => ["controller" => "LeadController", "method" => "getLeadsAjax"],
    "leads-ajax-debug" => ["controller" => "LeadController", "method" => "debugLeadsAjax"],
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


    // upload Csv From Csv Digital Marketing
    "digital-marketing-dashboard" => ["controller" => "DigitalMarketingController", "method" => "index"],
    "digital-marketing-executive-dashboard" => ["controller" => "DigitalMarketingController", "method" => "executiveDashboard"],
    "delete-lead-permanent" => ["controller" => "DigitalMarketingController", "method" => "deletePermanent"],
    "delete-leads-permanent-multiple" => ["controller" => "DigitalMarketingController",   "method" => "deletePermanentMultiple"],
    "store-platform-budget" => ["controller" => "DigitalMarketingController", "method" => "storeBudget"],

    // == access onboard clients ==

    "onboard-access-client-lists" => ["controller" => "OnboardClientController", "method" => "index"],
    "create-onboard-client" => ["controller" => "OnboardClientController", "method" => "create"],
    "store-onboard-client" => ["controller" => "OnboardClientController", "method" => "store"],

    "edit-onboard-client/(\d+)" => ["controller" => "OnboardClientController", "method" => "edit"],
    "update-onboard-client/(\d+)" => ["controller" => "OnboardClientController", "method" => "update"],

    "get-lead-booking-details/(\d+)" => ["controller" => "OnboardClientController", "method" => "getLeadBookingDetails"],
    "assign-onboard-client" => ["controller" => "OnboardClientController", "method" => "assignMultipleLeads"],


    // === Sales team performance report==
    "sales-team-performance" => ["controller" => "SalesTeamKpiController", "method" => "index"],



    // leads Source
    "leadSource-lists" => ["controller" => "LeadSourceController", "method" => "index"],
    // "create-leadSource" => ["controller" => "LeadSourceController", "method" => "create"],
    "store-leadSource" => ["controller" => "LeadSourceController", "method" => "store"],
    "edit-leadSource" => ["controller" => "LeadSourceController", "method" => "edit"],
    "update-leadSource" => ["controller" => "LeadSourceController", "method" => "update"],
    "delete-leadSource" => ["controller" => "LeadSourceController", "method" => "delete"],

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

    // teams
    'teams' => ['controller' => 'TeamController', 'method' => 'index'],
    'store-team' => ['controller' => 'TeamController', 'method' => 'store'],
    'update-team' => ['controller' => 'TeamController', 'method' => 'update'],
    'delete-team' => ['controller' => 'TeamController', 'method' => 'delete'],


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
    "get-client-profile" => [
        "controller" => "AccountClientController",
        "method" => "getClientProfile"
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

    // Client Refund Routes
    "client-refunds"              => ["controller" => "ClientRefundController", "method" => "index"],
    "client-refund-create/(\d+)"  => ["controller" => "ClientRefundController", "method" => "create"],
    "client-refund-store"         => ["controller" => "ClientRefundController", "method" => "store"],
    "client-refund/(\d+)"         => ["controller" => "ClientRefundController", "method" => "show"],
    "client-refund-approve/(\d+)" => ["controller" => "ClientRefundController", "method" => "approve"],
    "client-refund-reject/(\d+)"  => ["controller" => "ClientRefundController", "method" => "reject"],

    //rafi End

    // ==================== FINANCIAL VERIFICATION GATEWAY ====================
    'verify-payment'             => ['controller' => 'AccountClientController', 'method' => 'verifyPayment'],
    'get-banks'                  => ['controller' => 'AccountClientController', 'method' => 'getBanks'],
    'upload-transaction-proof'   => ['controller' => 'AccountClientController', 'method' => 'uploadTransactionProof'],
    'get-transaction-attachments' => ['controller' => 'AccountClientController', 'method' => 'getTransactionAttachments'],

    // ==================== BANK REGISTRY ====================
    'bank-registry'              => ['controller' => 'BankRegistryController', 'method' => 'index'],
    'store-bank'                 => ['controller' => 'BankRegistryController', 'method' => 'store'],
    'update-bank'                => ['controller' => 'BankRegistryController', 'method' => 'update'],
    'delete-bank'                => ['controller' => 'BankRegistryController', 'method' => 'delete'],
    'toggle-bank-status'         => ['controller' => 'BankRegistryController', 'method' => 'toggleStatus'],

    // ==================== CED PAYMENT TRACKING ====================
    'ced-payment-tracking'       => ['controller' => 'AccountClientController', 'method' => 'cedPaymentTracking'],

    // ==================== CED ADD TRANSACTION ====================
    'ced-add-transaction-form'       => ['controller' => 'AccountClientController', 'method' => 'showCedTransactionForm'],
    'ced-add-transaction-form/(\d+)' => ['controller' => 'AccountClientController', 'method' => 'showCedTransactionForm'],
    'ced-add-transaction'            => ['controller' => 'AccountClientController', 'method' => 'addCedTransaction'],

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
// =============================================================================
// PHASE 2 — CONSTRUCTION MANAGEMENT ROUTES
// =============================================================================
// ── Module 1: Pre-Construction ──────────────────────────────────────────────
$routes['con-dashboard'] = ['controller' => 'Phase2\Con_DashboardController', 'method' => 'index'];
$routes['con-land-visibility-studies'] = ['controller' => 'Phase2\LandVisibilityStudyController', 'method' => 'index'];
$routes['con-land-visibility-studies/create'] = ['controller' => 'Phase2\LandVisibilityStudyController', 'method' => 'create'];
$routes['con-land-visibility-studies/store'] = ['controller' => 'Phase2\LandVisibilityStudyController', 'method' => 'store'];
$routes['con-land-visibility-studies/edit'] = ['controller' => 'Phase2\LandVisibilityStudyController', 'method' => 'edit'];
$routes['con-land-visibility-studies/update'] = ['controller' => 'Phase2\LandVisibilityStudyController', 'method' => 'update'];
$routes['delete-land-visibility-study']         = ['controller' => 'Phase2\LandVisibilityStudyController', 'method' => 'delete'];

$routes['con-architect-engineering'] = ['controller' => 'Phase2\ArchitectEngineeringController', 'method' => 'index'];
$routes['con-architect-engineering/create'] = ['controller' => 'Phase2\ArchitectEngineeringController', 'method' => 'create'];
$routes['con-architect-engineering/store'] = ['controller' => 'Phase2\ArchitectEngineeringController', 'method' => 'store'];
$routes['con-architect-engineering/edit'] = ['controller' => 'Phase2\ArchitectEngineeringController', 'method' => 'edit'];
$routes['con-architect-engineering/update'] = ['controller' => 'Phase2\ArchitectEngineeringController', 'method' => 'update'];
$routes['delete-architect-engineering']         = ['controller' => 'Phase2\ArchitectEngineeringController', 'method' => 'delete'];

$routes['con-design-types'] = ['controller' => 'Phase2\DesignTypeController', 'method' => 'index'];
$routes['con-design-types/create'] = ['controller' => 'Phase2\DesignTypeController', 'method' => 'create'];
$routes['con-design-types/store'] = ['controller' => 'Phase2\DesignTypeController', 'method' => 'store'];
$routes['con-design-types/edit'] = ['controller' => 'Phase2\DesignTypeController', 'method' => 'edit'];
$routes['con-design-types/update'] = ['controller' => 'Phase2\DesignTypeController', 'method' => 'update'];
$routes['delete-design-type']                   = ['controller' => 'Phase2\DesignTypeController', 'method' => 'delete'];

$routes['con-project-drawings'] = ['controller' => 'Phase2\ProjectDrawingController', 'method' => 'index'];
$routes['con-project-drawings/create'] = ['controller' => 'Phase2\ProjectDrawingController', 'method' => 'create'];
$routes['con-project-drawings/store'] = ['controller' => 'Phase2\ProjectDrawingController', 'method' => 'store'];
$routes['con-project-drawings/edit'] = ['controller' => 'Phase2\ProjectDrawingController', 'method' => 'edit'];
$routes['con-project-drawings/update'] = ['controller' => 'Phase2\ProjectDrawingController', 'method' => 'update'];
$routes['delete-project-drawing']               = ['controller' => 'Phase2\ProjectDrawingController', 'method' => 'delete'];

$routes['con-soil-tests'] = ['controller' => 'Phase2\SoilTestController', 'method' => 'index'];
$routes['con-soil-tests/create'] = ['controller' => 'Phase2\SoilTestController', 'method' => 'create'];
$routes['con-soil-tests/store'] = ['controller' => 'Phase2\SoilTestController', 'method' => 'store'];
$routes['con-soil-tests/edit'] = ['controller' => 'Phase2\SoilTestController', 'method' => 'edit'];
$routes['con-soil-tests/update'] = ['controller' => 'Phase2\SoilTestController', 'method' => 'update'];
$routes['delete-soil-test']                     = ['controller' => 'Phase2\SoilTestController', 'method' => 'delete'];

// \u2500\u2500 Dashboard + Project Detail + AJAX Tab Routes \u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500
$routes['con-dashboard']           = ['controller' => 'Phase2\Con_DashboardController', 'method' => 'index'];
$routes['con-projects']            = ['controller' => 'Phase2\Con_DashboardController', 'method' => 'projectsList'];
$routes['con-project/(\d+)']       = ['controller' => 'Phase2\Con_DashboardController', 'method' => 'projectDetail'];
$routes['con-ajax-workplan/(\d+)'] = ['controller' => 'Phase2\Con_DashboardController', 'method' => 'ajaxWorkPlan'];
$routes['con-ajax-targets/(\d+)']  = ['controller' => 'Phase2\Con_DashboardController', 'method' => 'ajaxTargets'];
$routes['con-ajax-weekly/(\d+)']   = ['controller' => 'Phase2\Con_DashboardController', 'method' => 'ajaxWeeklyData'];
// ── Role-Specific Dashboards ───────────────────────────────────────────────
$routes['qc-dashboard']                   = ['controller' => 'Phase2\Con_DashboardController', 'method' => 'qcDashboard'];
$routes['purchase-team-lead-dashboard']   = ['controller' => 'Phase2\Con_DashboardController', 'method' => 'purchaseTeamLeadDashboard'];
$routes['draft-dashboard']                = ['controller' => 'Phase2\Con_DashboardController', 'method' => 'draftDashboard'];
$routes['land-dashboard']                 = ['controller' => 'Phase2\Con_DashboardController', 'method' => 'landDashboard'];
$routes['project-architecture-dashboard'] = ['controller' => 'Phase2\Con_DashboardController', 'method' => 'projectArchitectureDashboard'];
$routes['estimator-dashboard']            = ['controller' => 'Phase2\Con_DashboardController', 'method' => 'estimatorDashboard'];

// ── Operators / Contractors ────────────────────────────────────────────────
$routes['con-contractors']              = ['controller' => 'Phase2\Con_ContractorController', 'method' => 'index'];
$routes['con-contractor-create']        = ['controller' => 'Phase2\Con_ContractorController', 'method' => 'create'];
$routes['con-contractor-store']         = ['controller' => 'Phase2\Con_ContractorController', 'method' => 'store'];
$routes['con-contractor-edit/(\d+)']    = ['controller' => 'Phase2\Con_ContractorController', 'method' => 'edit'];
$routes['con-contractor-update/(\d+)']  = ['controller' => 'Phase2\Con_ContractorController', 'method' => 'update'];
$routes['con-contractor-delete']        = ['controller' => 'Phase2\Con_ContractorController', 'method' => 'delete'];

// ── Contractor Bill Requests ───────────────────────────────────────────────
$routes['con-contractor-bills']              = ['controller' => 'Phase2\Con_ContractorBillController', 'method' => 'index'];
$routes['con-contractor-bill-create']        = ['controller' => 'Phase2\Con_ContractorBillController', 'method' => 'create'];
$routes['con-contractor-bill-store']         = ['controller' => 'Phase2\Con_ContractorBillController', 'method' => 'store'];
$routes['con-contractor-bill/(\d+)']         = ['controller' => 'Phase2\Con_ContractorBillController', 'method' => 'show'];
$routes['con-contractor-bill-verify/(\d+)']  = ['controller' => 'Phase2\Con_ContractorBillController', 'method' => 'verify'];
$routes['con-contractor-bill-approve/(\d+)'] = ['controller' => 'Phase2\Con_ContractorBillController', 'method' => 'approve'];
$routes['con-contractor-bill-reject/(\d+)']  = ['controller' => 'Phase2\Con_ContractorBillController', 'method' => 'reject'];
$routes['con-contractor-bill-payment/(\d+)'] = ['controller' => 'Phase2\Con_ContractorBillController', 'method' => 'addPayment'];

// ── Operational Fund Requests ──────────────────────────────────────────────
$routes['con-fund-requests']           = ['controller' => 'Phase2\Con_FundRequestController', 'method' => 'index'];
$routes['con-fund-request-create']     = ['controller' => 'Phase2\Con_FundRequestController', 'method' => 'create'];
$routes['con-fund-request-store']      = ['controller' => 'Phase2\Con_FundRequestController', 'method' => 'store'];
$routes['con-fund-request/(\d+)']      = ['controller' => 'Phase2\Con_FundRequestController', 'method' => 'show'];
$routes['con-fund-request-approve/(\d+)']  = ['controller' => 'Phase2\Con_FundRequestController', 'method' => 'approve'];
$routes['con-fund-request-reject/(\d+)']   = ['controller' => 'Phase2\Con_FundRequestController', 'method' => 'reject'];
$routes['con-fund-request-payment/(\d+)']  = ['controller' => 'Phase2\Con_FundRequestController', 'method' => 'addPayment'];

// ── Materials / Inventory ──────────────────────────────────────────────────
$routes['con-materials']               = ['controller' => 'Phase2\Con_MaterialController', 'method' => 'index'];
$routes['con-material-create']         = ['controller' => 'Phase2\Con_MaterialController', 'method' => 'create'];
$routes['con-material-store']          = ['controller' => 'Phase2\Con_MaterialController', 'method' => 'store'];
$routes['con-material-edit/(\d+)']     = ['controller' => 'Phase2\Con_MaterialController', 'method' => 'edit'];
$routes['con-material-update/(\d+)']   = ['controller' => 'Phase2\Con_MaterialController', 'method' => 'update'];

$routes['con-warehouses']              = ['controller' => 'Phase2\WarehouseController', 'method' => 'index'];
$routes['con-warehouse-create']        = ['controller' => 'Phase2\WarehouseController', 'method' => 'create'];
$routes['con-warehouse-store']         = ['controller' => 'Phase2\WarehouseController', 'method' => 'store'];
$routes['con-warehouse-edit/(\d+)']    = ['controller' => 'Phase2\WarehouseController', 'method' => 'edit'];
$routes['con-warehouse-update/(\d+)']  = ['controller' => 'Phase2\WarehouseController', 'method' => 'update'];

$routes['con-stocks']                  = ['controller' => 'Phase2\Con_MaterialController', 'method' => 'stocks']; // Added to existing Con_MaterialController for simplicity

// ── Vendors ────────────────────────────────────────────────────────────────
$routes['con-vendors']                 = ['controller' => 'Phase2\Con_VendorController', 'method' => 'index'];
$routes['con-vendor-create']           = ['controller' => 'Phase2\Con_VendorController', 'method' => 'create'];
$routes['con-vendor-store']            = ['controller' => 'Phase2\Con_VendorController', 'method' => 'store'];
$routes['con-vendor-edit/(\d+)']       = ['controller' => 'Phase2\Con_VendorController', 'method' => 'edit'];
$routes['con-vendor-update/(\d+)']     = ['controller' => 'Phase2\Con_VendorController', 'method' => 'update'];
$routes['con-vendor/(\d+)']            = ['controller' => 'Phase2\Con_VendorController', 'method' => 'show'];
$routes['con-vendor-delete']           = ['controller' => 'Phase2\Con_VendorController', 'method' => 'delete'];

// ── Purchase Requests ──────────────────────────────────────────────────────
$routes['con-purchase-requests']       = ['controller' => 'Phase2\Con_PurchaseRequestController', 'method' => 'index'];
$routes['con-purchase-request-create'] = ['controller' => 'Phase2\Con_PurchaseRequestController', 'method' => 'create'];
$routes['con-purchase-request-store']  = ['controller' => 'Phase2\Con_PurchaseRequestController', 'method' => 'store'];
$routes['con-purchase-request/(\d+)']  = ['controller' => 'Phase2\Con_PurchaseRequestController', 'method' => 'show'];
$routes['con-purchase-request-approve/(\d+)'] = ['controller' => 'Phase2\Con_PurchaseRequestController', 'method' => 'approve'];
$routes['con-boq-remaining/(\d+)']    = ['controller' => 'Phase2\Con_PurchaseRequestController', 'method' => 'boqRemaining'];
$routes['con-ajax-pr-boqs']           = ['controller' => 'Phase2\Con_PurchaseRequestController', 'method' => 'ajaxGetProjectBoqs'];

// ── Quotations & CST ──────────────────────────────────────────────────────
$routes['con-quotation-create/(\d+)']  = ['controller' => 'Phase2\Con_QuotationController', 'method' => 'create'];
$routes['con-quotation-store']         = ['controller' => 'Phase2\Con_QuotationController', 'method' => 'store'];
$routes['con-quotation-compare/(\d+)'] = ['controller' => 'Phase2\Con_QuotationController', 'method' => 'compare'];
$routes['con-quotation-select']        = ['controller' => 'Phase2\Con_QuotationController', 'method' => 'selectWinner'];

// ── Purchase Orders ────────────────────────────────────────────────────────
$routes['con-purchase-orders']         = ['controller' => 'Phase2\Con_PurchaseOrderController', 'method' => 'index'];
$routes['con-purchase-order/(\d+)']    = ['controller' => 'Phase2\Con_PurchaseOrderController', 'method' => 'show'];
$routes['con-purchase-order-generate/(\d+)'] = ['controller' => 'Phase2\Con_PurchaseOrderController', 'method' => 'generate'];
$routes['con-purchase-order-approve/(\d+)']  = ['controller' => 'Phase2\\Con_PurchaseOrderController', 'method' => 'approvePO'];

// ── Material Receiving / GRN ───────────────────────────────────────────────
$routes['con-material-receivings']             = ['controller' => 'Phase2\Con_MaterialReceivingController', 'method' => 'index'];
$routes['con-material-receiving-create/(\d+)'] = ['controller' => 'Phase2\Con_MaterialReceivingController', 'method' => 'create'];
$routes['con-material-receiving-store']        = ['controller' => 'Phase2\Con_MaterialReceivingController', 'method' => 'store'];
$routes['con-material-receiving-qc/(\d+)']     = ['controller' => 'Phase2\Con_MaterialReceivingController', 'method' => 'qcReview'];

// ── Vendor Payment Requests ────────────────────────────────────────────────
$routes['con-vendor-payment-requests']          = ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'index'];
$routes['con-vendor-payment-create']            = ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'create'];
$routes['con-vendor-payment-store']             = ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'store'];
$routes['con-vendor-payment-show/(\d+)']        = ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'show'];
$routes['con-vendor-payment-approve/(\d+)']     = ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'approve'];
$routes['con-vendor-payment-reject/(\d+)']      = ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'reject'];
$routes['con-vendor-payment-ajax-penalty']      = ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'ajaxGetPenalty'];
$routes['con-vendor-payment-ajax-advance']      = ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'ajaxGetAdvanceSummary'];
$routes['con-vendor-payment-payment/(\d+)']     = ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'addPayment'];

// ── BOQ ───────────────────────────────────────────────────────────────────
$routes['con-boq']                     = ['controller' => 'Phase2\Con_BOQController', 'method' => 'index'];
$routes['con-boq-create']              = ['controller' => 'Phase2\Con_BOQController', 'method' => 'create'];
$routes['con-boq-store']               = ['controller' => 'Phase2\Con_BOQController', 'method' => 'store'];
$routes['con-boq-show/(\d+)']          = ['controller' => 'Phase2\Con_BOQController', 'method' => 'show'];
$routes['con-boq-edit/(\d+)']          = ['controller' => 'Phase2\Con_BOQController', 'method' => 'edit'];
$routes['con-boq-update/(\d+)']        = ['controller' => 'Phase2\Con_BOQController', 'method' => 'update'];
$routes['con-boq-approve/(\d+)']       = ['controller' => 'Phase2\Con_BOQController', 'method' => 'approve'];
$routes['con-boq-delete']              = ['controller' => 'Phase2\Con_BOQController', 'method' => 'delete'];
$routes['con-ajax-boq-targets']        = ['controller' => 'Phase2\Con_BOQController', 'method' => 'ajaxGetTargetsByProject'];


// (Working Plan + Budget routes moved below to avoid duplicates — see lines 839+)

// ── Target Fixed ──────────────────────────────────────────────────────────
$routes['con-targets']                 = ['controller' => 'Phase2\Con_TargetFixedController', 'method' => 'index'];
$routes['con-target-create']           = ['controller' => 'Phase2\Con_TargetFixedController', 'method' => 'create'];
$routes['con-target-store']            = ['controller' => 'Phase2\Con_TargetFixedController', 'method' => 'store'];
$routes['con-target/(\d+)']            = ['controller' => 'Phase2\Con_TargetFixedController', 'method' => 'show'];

$routes['con-target-approve/(\d+)']    = ['controller' => 'Phase2\Con_TargetFixedController', 'method' => 'approve'];

// ── Daily Progress Reports ────────────────────────────────────────────────
$routes['con-weekly-reports']           = ['controller' => 'Phase2\Con_WeeklyReportController', 'method' => 'index'];
$routes['con-weekly-report-create']     = ['controller' => 'Phase2\Con_WeeklyReportController', 'method' => 'create'];
$routes['con-weekly-report-store']      = ['controller' => 'Phase2\Con_WeeklyReportController', 'method' => 'store'];
$routes['con-weekly-report/(\d+)']      = ['controller' => 'Phase2\Con_WeeklyReportController', 'method' => 'show'];
$routes['con-weekly-report-approve/(\d+)'] = ['controller' => 'Phase2\Con_WeeklyReportController', 'method' => 'approve'];
$routes['con-ajax-work-plans']          = ['controller' => 'Phase2\Con_WeeklyReportController', 'method' => 'ajaxWorkPlans'];
$routes['con-ajax-report-targets']      = ['controller' => 'Phase2\Con_WeeklyReportController', 'method' => 'ajaxTargets'];
$routes['con-vendor-payment-ajax-penalty']  = ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'ajaxGetPenalty'];
$routes['con-vendor-payment-ajax-advance']  = ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'ajaxGetAdvanceSummary'];
$routes['con-ajax-po-by-vendor']            = ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'ajaxGetPOsByVendor'];
$routes['con-ajax-po-by-project']          = ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'ajaxGetPOsByProject'];
// Legacy aliases
$routes['con-daily-reports']            = ['controller' => 'Phase2\Con_WeeklyReportController', 'method' => 'index'];
$routes['con-daily-report-create']      = ['controller' => 'Phase2\Con_WeeklyReportController', 'method' => 'create'];
$routes['con-daily-report/(\d+)']       = ['controller' => 'Phase2\Con_WeeklyReportController', 'method' => 'show'];

// ── Material Usage Log ────────────────────────────────────────────────────
$routes['con-usage-logs']              = ['controller' => 'Phase2\Con_MaterialUsageController', 'method' => 'index'];
$routes['con-usage-log-create']        = ['controller' => 'Phase2\Con_MaterialUsageController', 'method' => 'create'];
$routes['con-usage-log-store']         = ['controller' => 'Phase2\Con_MaterialUsageController', 'method' => 'store'];

// ── Vendor Payments ───────────────────────────────────────────────────────
$routes['con-vendor-payments']              = ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'index'];
$routes['con-vendor-payment-create']        = ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'create'];
$routes['con-vendor-payment-store']         = ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'store'];
$routes['con-vendor-payment/(\d+)']         = ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'show'];
$routes['con-vendor-payment-ajax-penalty']  = ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'ajaxGetPenalty'];
$routes['con-vendor-payment-ajax-advance']  = ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'ajaxGetAdvanceSummary'];
$routes['con-ajax-po-by-vendor']            = ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'ajaxGetPOsByVendor'];

// ── Financial Ledger ──────────────────────────────────────────────────────
$routes['con-ledger']                  = ['controller' => 'Phase2\Con_FinancialLedgerController', 'method' => 'index'];
$routes['con-ledger-project/(\d+)']    = ['controller' => 'Phase2\Con_FinancialLedgerController', 'method' => 'byProject'];

// ── Pre-Construction ──────────────────────────────────────────────────────
$routes['con-pre-construction']        = ['controller' => 'Phase2\Con_PreConstructionController', 'method' => 'index'];
$routes['con-land-study-store']        = ['controller' => 'Phase2\Con_PreConstructionController', 'method' => 'storeLandStudy'];
$routes['con-drawing-store']           = ['controller' => 'Phase2\Con_PreConstructionController', 'method' => 'storeDrawing'];
$routes['con-soil-test-store']         = ['controller' => 'Phase2\Con_PreConstructionController', 'method' => 'storeSoilTest'];

// ── Initial Estimate Budgets ──────────────────────────────────────────────
$routes['con-estimates']              = ['controller' => 'Phase2\Con_InitialEstimateController', 'method' => 'index'];
$routes['con-estimate-create']        = ['controller' => 'Phase2\Con_InitialEstimateController', 'method' => 'create'];
$routes['con-estimate-store']         = ['controller' => 'Phase2\Con_InitialEstimateController', 'method' => 'store'];
$routes['con-estimate-show/(\d+)']    = ['controller' => 'Phase2\Con_InitialEstimateController', 'method' => 'show'];
$routes['con-estimate-edit/(\d+)']    = ['controller' => 'Phase2\Con_InitialEstimateController', 'method' => 'edit'];
$routes['con-estimate-update/(\d+)']  = ['controller' => 'Phase2\Con_InitialEstimateController', 'method' => 'update'];
$routes['con-estimate-approve/(\d+)'] = ['controller' => 'Phase2\Con_InitialEstimateController', 'method' => 'approveBudget'];
$routes['con-estimate-cancel/(\d+)']  = ['controller' => 'Phase2\Con_InitialEstimateController', 'method' => 'cancelBudget'];
$routes['con-ajax-arch-by-project']     = ['controller' => 'Phase2\\Con_InitialEstimateController', 'method' => 'ajaxArchByProject'];
$routes['con-ajax-designs-by-project']  = ['controller' => 'Phase2\\Con_InitialEstimateController', 'method' => 'ajaxDesignsByProject'];

// ── Material Issues (Stock OUT) ───────────────────────────────────────────
$routes['con-material-issues']               = ['controller' => 'Phase2\Con_MaterialIssueController', 'method' => 'index'];
$routes['con-material-issue-create']         = ['controller' => 'Phase2\Con_MaterialIssueController', 'method' => 'create'];
$routes['con-material-issue-store']          = ['controller' => 'Phase2\Con_MaterialIssueController', 'method' => 'store'];
$routes['con-material-issue/(\d+)']          = ['controller' => 'Phase2\Con_MaterialIssueController', 'method' => 'show'];

// ── Vendor Penalties ──────────────────────────────────────────────────────
$routes['con-vendor-penalties']              = ['controller' => 'Phase2\Con_VendorPenaltyController', 'method' => 'index'];
$routes['con-vendor-penalty-create']         = ['controller' => 'Phase2\Con_VendorPenaltyController', 'method' => 'create'];
$routes['con-vendor-penalty-store']          = ['controller' => 'Phase2\Con_VendorPenaltyController', 'method' => 'store'];
$routes['con-vendor-penalty-delete']         = ['controller' => 'Phase2\Con_VendorPenaltyController', 'method' => 'delete'];

// ── Target Fixed — missing routes ─────────────────────────────────────────
$routes['con-target-edit/(\d+)']       = ['controller' => 'Phase2\\Con_TargetFixedController', 'method' => 'edit'];
$routes['con-target-update/(\d+)']     = ['controller' => 'Phase2\\Con_TargetFixedController', 'method' => 'update'];
$routes['con-target-gain/(\d+)']       = ['controller' => 'Phase2\\Con_TargetFixedController', 'method' => 'updateGain'];

// ── Financial Transactions ─────────────────────────────────────────────────
$routes['con-transactions']               = ['controller' => 'Phase2\\Con_FinancialTransactionController', 'method' => 'index'];
$routes['con-pending-payments']           = ['controller' => 'Phase2\\Con_FinancialTransactionController', 'method' => 'pendingList'];
$routes['con-process-payment/([^/]+)/(\d+)'] = ['controller' => 'Phase2\\Con_FinancialTransactionController', 'method' => 'process'];
$routes['con-payment-store']              = ['controller' => 'Phase2\\Con_FinancialTransactionController', 'method' => 'store'];
$routes['con-transaction/(\d+)']          = ['controller' => 'Phase2\\Con_FinancialTransactionController', 'method' => 'show'];

// =============================================================================
// END PHASE 2 ROUTES
// =============================================================================

// ── Working Plans ─────────────────────────────────────────────────────────────
$routes['con-working-plans']              = ['controller' => 'Phase2\Con_WorkingPlanController', 'method' => 'index'];
$routes['con-working-plan-create']        = ['controller' => 'Phase2\Con_WorkingPlanController', 'method' => 'create'];
$routes['con-working-plan-store']         = ['controller' => 'Phase2\Con_WorkingPlanController', 'method' => 'store'];
$routes['con-working-plan-edit/(\d+)']    = ['controller' => 'Phase2\Con_WorkingPlanController', 'method' => 'edit'];
$routes['con-working-plan-update/(\d+)']  = ['controller' => 'Phase2\Con_WorkingPlanController', 'method' => 'update'];
$routes['con-working-plan-delete/(\d+)']  = ['controller' => 'Phase2\Con_WorkingPlanController', 'method' => 'delete'];

// ── Budget Extension Requests ─────────────────────────────────────────────────
$routes['con-budget-extends']             = ['controller' => 'Phase2\Con_WorkingPlanController', 'method' => 'budgetExtends'];
$routes['con-budget-extend-create']       = ['controller' => 'Phase2\Con_WorkingPlanController', 'method' => 'budgetExtendCreate'];
$routes['con-budget-extend-store']        = ['controller' => 'Phase2\Con_WorkingPlanController', 'method' => 'budgetExtendStore'];
$routes['con-budget-extend-approve/(\d+)']= ['controller' => 'Phase2\Con_WorkingPlanController', 'method' => 'approveExtend'];
$routes['con-budget-extend-reject/(\d+)'] = ['controller' => 'Phase2\Con_WorkingPlanController', 'method' => 'rejectExtend'];

// ── AJAX: Cascade project → plans + budgets ───────────────────────────────
$routes['con-ajax-project-data']           = ['controller' => 'Phase2\Con_WorkingPlanController', 'method' => 'ajaxGetProjectData'];
// ── AJAX: Cascade project → targets ──────────────────────────────────────
$routes['con-ajax-project-targets']        = ['controller' => 'Phase2\Con_TargetFixedController', 'method' => 'ajaxGetProjectTargets'];


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
