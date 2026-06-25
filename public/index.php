<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// error_reporting(E_ALL);
// ini_set('display_errors', 0);

mysqli_report(MYSQLI_REPORT_OFF);

// for producttion error hide 
require_once("../app/helpers/helper.php");
require_once __DIR__ . '/../vendor/autoload.php';
//// Maintenance Mode Check
// if (file_exists(__DIR__ . '/maintenance.flag')) {
//     http_response_code(503);
//     $maintenancePage = __DIR__ . '/maintenance.html';
//     if (file_exists($maintenancePage)) {
//         readfile($maintenancePage);
//     } else {
//         echo "<h1 style=\"color:#2d4a54;text-align:center;font-family:sans-serif;margin-top:15%\">We're Down for Maintenance</h1><p style=\"color:#788395;text-align:center\">We'll be back soon!</p>";
//     }
//     exit;
// }

// Removed duplicate session_start() — already called at line 2-4
// Enable debug (local dev)
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
        $root . "/app/controllers/Phase2/$class.php",   // Phase 2 controllers
        $root . "/app/services/$class.php",
        $root . "/app/helpers/$class.php",
        $root . "/app/ai/$class.php",
        $root . "/app/$class.php"                       // Direct namespace-to-folder matching under app/
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
    "update-activity" => ["controller" => "LoginController", "method" => "updateActivity"],
    "qc-audit-dashboard" => ["controller" => "QcAuditController", "method" => "dashboard"],
    "qc-advanced-audit" => ["controller" => "QcAuditController", "method" => "advancedAudit"],
    "api/qc-audit/materials" => ["controller" => "QcAuditController", "method" => "getAuditMaterials"],
    "api/qc-audit/purchases" => ["controller" => "QcAuditController", "method" => "getAuditPurchases"],
    "api/qc-audit/payments" => ["controller" => "QcAuditController", "method" => "getAuditPayments"],
    "qc-requests" => ["controller" => "QcAuditController", "method" => "pendingRequests"],
    "qc-approve-request" => ["controller" => "QcAuditController", "method" => "approveRequest"],
    "api/qc-audit/audit-trail" => ["controller" => "QcAuditController", "method" => "getAuditTrail"],

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
    // User Activity Log (Admin / Management only)
    "user-activity-log" => ["controller" => "UserActivityLogController", "method" => "index"],
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
    "edit-letter/(\d+)" => ["controller" => "LetterTemplateController", "method" => "editLetter"],
    "update-letter/(\d+)" => ["controller" => "LetterTemplateController", "method" => "updateLetter"],
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
    "bulk-leads-import-csv" => ["controller" => "LeadController", "method" => "importCsvBulkLeads"],
    "onboarding-clients" => ["controller" => "LeadController", "method" => "showOnboardClient"],
    "update-lead/(\d+)" => ["controller" => "LeadController", "method" => "update"],
    "update-last_leadAction/(\d+)" => ["controller" => "LeadController", "method" => "updateLastleadAction"],

    "lead-delete" => ["controller" => "LeadController", "method" => "delete"],
    "assign-multiple-leads" => ["controller" => "LeadController", "method" => "assignMultipleLeads"],


    // upload Csv From Csv Digital Marketing
    "digital-marketing-dashboard" => ["controller" => "DigitalMarketingController", "method" => "index"],
    "digital-marketing-executive-dashboard" => ["controller" => "DigitalMarketingController", "method" => "executiveDashboard"],
    "dm-exec-leads-ajax" => ["controller" => "DigitalMarketingController", "method" => "getLeadsAjax"],
    "dm-head-leads-ajax" => ["controller" => "DigitalMarketingController", "method" => "getLeadsAjax"],
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
    "bulk-bookings-import-csv" => ["controller" => "BookingController", "method" => "importCsvBulkBookings"],
    "bulk-payments-import-csv" => ["controller" => "PaymentBookingController", "method" => "importCsvBulkPayments"],
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



    //location
    "location-lists" => ["controller" => "LocationController", "method" => "index"],
    "store-location" => ["controller" => "LocationController", "method" => "store"],
    "update-location" => ["controller" => "LocationController", "method" => "update"],
    "delete-location" => ["controller" => "LocationController", "method" => "delete"],

    // Amenities
    "amenities" => ["controller" => "AmenityController", "method" => "index"],
    "create-amenity" => ["controller" => "AmenityController", "method" => "create"],
    "store-amenity" => ["controller" => "AmenityController", "method" => "store"],
    "view-amenity/(\d+)" => ["controller" => "AmenityController", "method" => "show"],
    "edit-amenity/(\d+)" => ["controller" => "AmenityController", "method" => "edit"],
    "update-amenity/(\d+)" => ["controller" => "AmenityController", "method" => "update"],
    "delete-amenity" => ["controller" => "AmenityController", "method" => "delete"],
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

    // ==========SALES & BUISENESS======//

    //project
    "project-lists" => ["controller" => "ProjectController", "method" => "index"],
    "create-project" => ["controller" => "ProjectController", "method" => "create"],
    "store-project" => ["controller" => "ProjectController", "method" => "store"],
    "edit-project/(\d+)" => ["controller" => "ProjectController", "method" => "edit"],
    "project-details/(\d+)" => ["controller" => "ProjectController", "method" => "show"],
    "update-project/(\d+)" => ["controller" => "ProjectController", "method" => "update"],
    "delete-project" => ["controller" => "ProjectController", "method" => "delete"],
    "bulk-projects-import-csv" => ["controller" => "ProjectController", "method" => "importCsvBulkProjects"],

    //flat
    "flat-lists" => ["controller" => "FlatController", "method" => "index"],
    "ajax-flat-lists" => ["controller" => "FlatController", "method" => "ajaxFlatList"],
    "create-flat" => ["controller" => "FlatController", "method" => "create"],
    "store-flat" => ["controller" => "FlatController", "method" => "store"],
    "edit-flat/(\d+)" => ["controller" => "FlatController", "method" => "edit"],
    "flat-details/(\d+)" => ["controller" => "FlatController", "method" => "show"],
    "update-flat/(\d+)" => ["controller" => "FlatController", "method" => "update"],
    "delete-flat" => ["controller" => "FlatController", "method" => "delete"],
    "bulk-flats-import-csv" => ["controller" => "FlatController", "method" => "importCsvBulkFlats"],
    "bulk-shops-import-csv" => ["controller" => "FlatController", "method" => "importCsvBulkShops"],

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

    // payslip import
    "import-payslips" => ["controller" => "PayrollController", "method" => "showImportForm"],
    "execute-import-payslips" => ["controller" => "PayrollController", "method" => "importCSV"],
    "download-sample-payslips-csv" => ["controller" => "PayrollController", "method" => "downloadSampleCSV"],
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
    "add-shop-transaction" => ["controller" => "AccountShopClientController", "method" => "addShopTransaction"],
    "edit-client-cash-in/(\d+)" => ["controller" => "AccountClientController", "method" => "showEditTransactionForm"],
    "edit-client-cash-in" => ["controller" => "AccountClientController", "method" => "showEditTransactionForm"],
    "client-cash-in-form" => ["controller" => "AccountClientController", "method" => "showAddTransactionForm"],
    "shop-client-cash-in-form" => ["controller" => "AccountShopClientController", "method" => "showShopTransactionForm"],
    "update-transaction" => ["controller" => "AccountClientController", "method" => "updateTransaction"],
    "delete-transaction" => ["controller" => "AccountClientController", "method" => "deleteTransaction"],

    // ==================== CSV BULK IMPORT ROUTES ====================
    'csv-import-form'     => ['controller' => 'AccountClientController', 'method' => 'showCSVImportForm'],
    'csv-import-preview'  => ['controller' => 'AccountClientController', 'method' => 'previewCSVImport'],
    'csv-import-execute'  => ['controller' => 'AccountClientController', 'method' => 'executeCSVImport'],
    'csv-import-template' => ['controller' => 'AccountClientController', 'method' => 'downloadCSVTemplate'],

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
    'discount-statement' => [
        "controller" => "AccountClientController",
        "method" => "discountStatement"
    ],
    'discount-statement/(\d+)' => [
        "controller" => "AccountClientController",
        "method" => "discountStatement"
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
    'save-transfer-note' => ["controller" => "AccountClientController", "method" => "saveTransferNote"],

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

    // Cashout CSV Import Routes
    "cashout-csv-import-form"     => ["controller" => "CashoutRequestController", "method" => "showCSVImportForm"],
    "cashout-csv-import-preview"  => ["controller" => "CashoutRequestController", "method" => "previewCSVImport"],
    "cashout-csv-import-execute"  => ["controller" => "CashoutRequestController", "method" => "executeCSVImport"],
    "cashout-csv-import-template" => ["controller" => "CashoutRequestController", "method" => "downloadCSVTemplate"],

    // Client Refund Routes
    "client-refunds"              => ["controller" => "ClientRefundController", "method" => "index"],
    "client-refund-create/(\d+)"  => ["controller" => "ClientRefundController", "method" => "create"],
    "client-refund-store"         => ["controller" => "ClientRefundController", "method" => "store"],
    "client-refund/(\d+)"         => ["controller" => "ClientRefundController", "method" => "show"],
    "client-refund-approve/(\d+)" => ["controller" => "ClientRefundController", "method" => "approve"],
    "client-refund-reject/(\d+)"  => ["controller" => "ClientRefundController", "method" => "reject"],
    "client-refund-delete/(\d+)"  => ["controller" => "ClientRefundController", "method" => "delete"],

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

    // ── Legal Sheets ────────────────────────────────────────────────
    'legal-sheet-lists'           => ['controller' => 'Phase2\Con_ProjectLegalSheetController', 'method' => 'index'],
    'create-legal-sheet'          => ['controller' => 'Phase2\Con_ProjectLegalSheetController', 'method' => 'create'],
    'legal-sheet-store'           => ['controller' => 'Phase2\Con_ProjectLegalSheetController', 'method' => 'store'],
    'edit-legal-sheet/(\d+)'      => ['controller' => 'Phase2\Con_ProjectLegalSheetController', 'method' => 'edit'],
    'legal-sheet-update/(\d+)'    => ['controller' => 'Phase2\Con_ProjectLegalSheetController', 'method' => 'update'],
    'delete-legal-sheet/(\d+)'    => ['controller' => 'Phase2\Con_ProjectLegalSheetController', 'method' => 'delete'],
    'legal-sheet-import-csv'      => ['controller' => 'Phase2\Con_ProjectLegalSheetController', 'method' => 'importCsv'],

];

$request_uri = $_SERVER['REQUEST_URI'];
$route = trim(strtok($request_uri, '?'), '/');

$base_folder = 'TPHL-ERP';
if (strpos($route, $base_folder) === 0) {
    $route = substr($route, strlen($base_folder));
}
$route = trim($route, '/');

if ($route === 'log-client-debug') {
    header('Content-Type: application/json');
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if ($data) {
        $logFile = dirname(__DIR__) . '/logs/client_debug.log';
        $timestamp = date('Y-m-d H:i:s');
        $message = sprintf(
            "[%s] URL: %s | Type: %s | Message: %s | Details: %s\n",
            $timestamp,
            $data['url'] ?? 'N/A',
            $data['type'] ?? 'info',
            $data['message'] ?? '',
            json_encode($data['details'] ?? [], JSON_UNESCAPED_SLASHES)
        );
        file_put_contents($logFile, $message, FILE_APPEND);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    }
    exit;
}

$routes['tutorial-video'] = ['controller' => 'TutorialController', 'method' => 'redirect'];

/**
 *  GLOBAL AUTH CHECK
 */
// =============================================================================
// PHASE 2 — CONSTRUCTION MANAGEMENT ROUTES
// =============================================================================
// ── Module 1: Pre-Construction ──────────────────────────────────────────────
$routes['con-dashboard'] = ['controller' => 'Phase2\Con_DashboardController', 'method' => 'index'];
$routes['all-projects-overview'] = ['controller' => 'Phase2\Con_ProjectOverviewDashboardController', 'method' => 'index'];
$routes['con-land-visibility-studies'] = ['controller' => 'Phase2\LandVisibilityStudyController', 'method' => 'index'];
$routes['con-land-visibility-studies/create'] = ['controller' => 'Phase2\LandVisibilityStudyController', 'method' => 'create'];
$routes['con-land-visibility-studies/store'] = ['controller' => 'Phase2\LandVisibilityStudyController', 'method' => 'store'];
$routes['con-land-visibility-studies/edit'] = ['controller' => 'Phase2\LandVisibilityStudyController', 'method' => 'edit'];
$routes['con-land-visibility-studies/update'] = ['controller' => 'Phase2\LandVisibilityStudyController', 'method' => 'update'];
$routes['delete-land-visibility-study']         = ['controller' => 'Phase2\LandVisibilityStudyController', 'method' => 'delete'];

// Land Acquisitions routes
$routes['con-land-acquisitions'] = ['controller' => 'Phase2\LandAcquisitionController', 'method' => 'index'];
$routes['con-land-acquisitions/create'] = ['controller' => 'Phase2\LandAcquisitionController', 'method' => 'create'];
$routes['con-land-acquisitions/store'] = ['controller' => 'Phase2\LandAcquisitionController', 'method' => 'store'];
$routes['con-land-acquisitions/edit'] = ['controller' => 'Phase2\LandAcquisitionController', 'method' => 'edit'];
$routes['con-land-acquisitions/update'] = ['controller' => 'Phase2\LandAcquisitionController', 'method' => 'update'];
$routes['con-land-acquisitions/delete'] = ['controller' => 'Phase2\LandAcquisitionController', 'method' => 'delete'];

// Land Requisitions routes
$routes['con-land-requisitions'] = ['controller' => 'Phase2\LandRequisitionController', 'method' => 'index'];
$routes['con-land-requisitions/create'] = ['controller' => 'Phase2\LandRequisitionController', 'method' => 'create'];
$routes['con-land-requisitions/store'] = ['controller' => 'Phase2\LandRequisitionController', 'method' => 'store'];
$routes['con-land-requisitions/approve'] = ['controller' => 'Phase2\LandRequisitionController', 'method' => 'approve'];
$routes['con-land-requisitions/pay'] = ['controller' => 'Phase2\LandRequisitionController', 'method' => 'pay'];
$routes['con-land-requisitions/delete'] = ['controller' => 'Phase2\LandRequisitionController', 'method' => 'delete'];

// Land Monthly Reports routes
$routes['con-land-monthly-reports'] = ['controller' => 'Phase2\LandReportController', 'method' => 'index'];
$routes['con-land-monthly-reports/generate'] = ['controller' => 'Phase2\LandReportController', 'method' => 'generate'];
$routes['con-land-ledger'] = ['controller' => 'Phase2\LandReportController', 'method' => 'ledger'];

// Fund Requisitions routes
$routes['fund-requisitions'] = ['controller' => 'Phase2\FundRequisitionController', 'method' => 'index'];
$routes['fund-requisitions/store'] = ['controller' => 'Phase2\FundRequisitionController', 'method' => 'store'];
$routes['fund-requisitions/approve'] = ['controller' => 'Phase2\FundRequisitionController', 'method' => 'approve'];
$routes['fund-requisitions/pay'] = ['controller' => 'Phase2\FundRequisitionController', 'method' => 'pay'];
$routes['fund-requisitions/delete'] = ['controller' => 'Phase2\FundRequisitionController', 'method' => 'delete'];
$routes['fund-requisitions/show/(\d+)'] = ['controller' => 'Phase2\FundRequisitionController', 'method' => 'show'];
$routes['fund-requisitions/store-expense'] = ['controller' => 'Phase2\FundRequisitionController', 'method' => 'storeExpense'];
$routes['fund-requisitions/delete-expense'] = ['controller' => 'Phase2\FundRequisitionController', 'method' => 'deleteExpense'];

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

$routes['con-work-types']                = ['controller' => 'Phase2\Con_WorkTypeController', 'method' => 'index'];
$routes['con-work-type-create']          = ['controller' => 'Phase2\Con_WorkTypeController', 'method' => 'create'];
$routes['con-work-type-store']           = ['controller' => 'Phase2\Con_WorkTypeController', 'method' => 'store'];
$routes['con-work-type-edit/(\d+)']      = ['controller' => 'Phase2\Con_WorkTypeController', 'method' => 'edit'];
$routes['con-work-type-update/(\d+)']    = ['controller' => 'Phase2\Con_WorkTypeController', 'method' => 'update'];
$routes['con-work-type-delete']          = ['controller' => 'Phase2\Con_WorkTypeController', 'method' => 'delete'];

$routes['con-project-drawings'] = ['controller' => 'Phase2\ProjectDrawingController', 'method' => 'index'];
$routes['con-project-drawings/create'] = ['controller' => 'Phase2\ProjectDrawingController', 'method' => 'create'];
$routes['con-project-drawings/store'] = ['controller' => 'Phase2\ProjectDrawingController', 'method' => 'store'];
$routes['con-project-drawings/edit'] = ['controller' => 'Phase2\ProjectDrawingController', 'method' => 'edit'];
$routes['con-project-drawings/update'] = ['controller' => 'Phase2\ProjectDrawingController', 'method' => 'update'];
$routes['delete-project-drawing']               = ['controller' => 'Phase2\ProjectDrawingController', 'method' => 'delete'];

$routes['structure-design'] = ['controller' => 'Phase2\ProjectStructuralDesignController', 'method' => 'index'];
$routes['structure-design/create'] = ['controller' => 'Phase2\ProjectStructuralDesignController', 'method' => 'create'];
$routes['structure-design/store'] = ['controller' => 'Phase2\ProjectStructuralDesignController', 'method' => 'store'];
$routes['structure-design/edit'] = ['controller' => 'Phase2\ProjectStructuralDesignController', 'method' => 'edit'];
$routes['structure-design/update'] = ['controller' => 'Phase2\ProjectStructuralDesignController', 'method' => 'update'];
$routes['delete-structure-design'] = ['controller' => 'Phase2\ProjectStructuralDesignController', 'method' => 'delete'];

$routes['con-soil-tests'] = ['controller' => 'Phase2\SoilTestController', 'method' => 'index'];
$routes['con-soil-tests/create'] = ['controller' => 'Phase2\SoilTestController', 'method' => 'create'];
$routes['con-soil-tests/store'] = ['controller' => 'Phase2\SoilTestController', 'method' => 'store'];
$routes['con-soil-tests/edit'] = ['controller' => 'Phase2\SoilTestController', 'method' => 'edit'];
$routes['con-soil-tests/update'] = ['controller' => 'Phase2\SoilTestController', 'method' => 'update'];
$routes['delete-soil-test']                     = ['controller' => 'Phase2\SoilTestController', 'method' => 'delete'];

// ── Dashboard + Project Detail + AJAX Tab Routes ─────────────────────────────
$routes['con-dashboard']           = ['controller' => 'Phase2\Con_DashboardController', 'method' => 'index'];
$routes['con-projects']            = ['controller' => 'Phase2\Con_DashboardController', 'method' => 'projectsList'];
$routes['con-project/(\d+)']       = ['controller' => 'Phase2\Con_DashboardController', 'method' => 'projectDetail'];

// Phase-wise Construction - Module 1: Project Timeline Setup
$routes['con-project-timeline/(\d+)']        = ['controller' => 'Phase2\Con_ProjectTimelineController', 'method' => 'index'];
$routes['con-project-timeline-store']         = ['controller' => 'Phase2\Con_ProjectTimelineController', 'method' => 'store'];
$routes['con-project-timeline-edit/(\d+)']    = ['controller' => 'Phase2\Con_ProjectTimelineController', 'method' => 'edit'];
$routes['con-project-timeline-update/(\d+)']  = ['controller' => 'Phase2\Con_ProjectTimelineController', 'method' => 'update'];
$routes['con-floor-setup/(\d+)']             = ['controller' => 'Phase2\Con_ProjectTimelineController', 'method' => 'floorSetup'];
$routes['con-floor-setup-save/(\d+)']        = ['controller' => 'Phase2\Con_ProjectTimelineController', 'method' => 'floorSetupSave'];

// Phase-wise Construction - Module 2: Phase Plan
$routes['con-phase-plan/(\d+)']         = ['controller' => 'Phase2\Con_PhasePlanController', 'method' => 'index'];
$routes['con-phase-plan-update/(\d+)']  = ['controller' => 'Phase2\Con_PhasePlanController', 'method' => 'update'];
$routes['con-phase-add']                 = ['controller' => 'Phase2\Con_PhasePlanController', 'method' => 'addPhase'];
$routes['con-phase-delete/(\d+)']       = ['controller' => 'Phase2\Con_PhasePlanController', 'method' => 'delete'];
$routes['con-phase-template-json']       = ['controller' => 'Phase2\Con_PhasePlanController', 'method' => 'ajaxGetTemplate'];

// Phase-wise Construction - Module 3: Phase Sub-items (BOQ Level 2)
$routes['con-phase-items/(\d+)']            = ['controller' => 'Phase2\Con_PhaseItemController', 'method' => 'index'];
$routes['con-phase-items-update/(\d+)']     = ['controller' => 'Phase2\Con_PhaseItemController', 'method' => 'update'];
$routes['con-phase-items-delete/(\d+)']     = ['controller' => 'Phase2\Con_PhaseItemController', 'method' => 'delete'];
$routes['con-phase-items-progress']         = ['controller' => 'Phase2\Con_PhaseItemController', 'method' => 'ajaxUpdateProgress'];
$routes['con-phase-item-add']                 = ['controller' => 'Phase2\Con_PhaseItemController', 'method' => 'addItem'];
$routes['con-phase-item-edit']                = ['controller' => 'Phase2\Con_PhaseItemController', 'method' => 'editItem'];

// Phase-wise Construction - Module 4: Monthly Target Planner
$routes['con-monthly-targets/(\d+)']        = ['controller' => 'Phase2\Con_MonthlyTargetController', 'method' => 'index'];
$routes['con-monthly-target/(\d+)']         = ['controller' => 'Phase2\Con_MonthlyTargetController', 'method' => 'show'];
$routes['con-monthly-target-create/(\d+)']  = ['controller' => 'Phase2\Con_MonthlyTargetController', 'method' => 'create'];
$routes['con-monthly-target-status/(\d+)']  = ['controller' => 'Phase2\Con_MonthlyTargetController', 'method' => 'updateStatus'];
$routes['con-monthly-target-save/(\d+)']    = ['controller' => 'Phase2\Con_MonthlyTargetController', 'method' => 'saveItems'];
$routes['con-monthly-item-add']             = ['controller' => 'Phase2\Con_MonthlyTargetController', 'method' => 'addItem'];
$routes['con-monthly-item-delete/(\d+)']    = ['controller' => 'Phase2\Con_MonthlyTargetController', 'method' => 'deleteItem'];
$routes['con-monthly-target-delete/(\d+)']  = ['controller' => 'Phase2\Con_MonthlyTargetController', 'method' => 'deleteTarget'];
$routes['con-monthly-worktypes']            = ['controller' => 'Phase2\Con_MonthlyTargetController', 'method' => 'ajaxWorkTypes'];
$routes['con-project-plan-targets']         = ['controller' => 'Phase2\Con_MonthlyTargetController', 'method' => 'siteEngineerTargets'];
$routes['con-monthly-item-daily-breakdown'] = ['controller' => 'Phase2\Con_MonthlyTargetController', 'method' => 'saveDailyPctBreakdown'];

// Phase-wise Construction - Module 5: Daily / Weekly Progress Entry
$routes['con-weekly-progress/(\d+)']        = ['controller' => 'Phase2\Con_PhaseWeeklyProgressController', 'method' => 'show'];
$routes['con-weekly-progress-save']         = ['controller' => 'Phase2\Con_PhaseWeeklyProgressController', 'method' => 'saveDailyLog'];
$routes['con-weekly-progress-submit']       = ['controller' => 'Phase2\Con_PhaseWeeklyProgressController', 'method' => 'submitWeeklyUpdate'];
$routes['con-weekly-progress-delete/(\d+)']  = ['controller' => 'Phase2\Con_PhaseWeeklyProgressController', 'method' => 'deleteDailyLogItem'];
$routes['con-weekly-progress-qc-approve']   = ['controller' => 'Phase2\Con_PhaseWeeklyProgressController', 'method' => 'qcApprove'];
$routes['con-weekly-progress-pm-approve']   = ['controller' => 'Phase2\Con_PhaseWeeklyProgressController', 'method' => 'pmApprove'];

// Phase-wise Construction - Module 6: Monthly Review
$routes['con-monthly-review/(\d+)']                                              = ['controller' => 'Phase2\Con_MonthlyReviewController', 'method' => 'index'];
$routes['con-monthly-review/(\d+)/phase/(\d+)/month/([\d-]+)']                  = ['controller' => 'Phase2\Con_MonthlyReviewController', 'method' => 'show'];

// Phase-wise Construction - Module 7: Risk Dashboard
$routes['con-risk-dashboard/(\d+)']          = ['controller' => 'Phase2\Con_RiskDashboardController', 'method' => 'index'];
$routes['con-risk-dashboard-store']          = ['controller' => 'Phase2\Con_RiskDashboardController', 'method' => 'storeRisk'];
$routes['con-risk-dashboard-update-status']  = ['controller' => 'Phase2\Con_RiskDashboardController', 'method' => 'updateRiskStatus'];
$routes['con-risk-dashboard-delete/(\d+)']   = ['controller' => 'Phase2\Con_RiskDashboardController', 'method' => 'deleteRisk'];

// Phase-wise Construction - Module 8: Recovery Plan
$routes['con-recovery-plan/(\d+)']           = ['controller' => 'Phase2\Con_RecoveryPlanController', 'method' => 'index'];
$routes['con-recovery-plan-create']          = ['controller' => 'Phase2\Con_RecoveryPlanController', 'method' => 'createPlan'];
$routes['con-recovery-plan-action-store']    = ['controller' => 'Phase2\Con_RecoveryPlanController', 'method' => 'storeActionItem'];
$routes['con-recovery-plan-action-update']   = ['controller' => 'Phase2\Con_RecoveryPlanController', 'method' => 'updateActionStatus'];

$routes['con-ajax-workplan/(\d+)'] = ['controller' => 'Phase2\Con_DashboardController', 'method' => 'ajaxWorkPlan'];
$routes['con-ajax-targets/(\d+)']  = ['controller' => 'Phase2\Con_DashboardController', 'method' => 'ajaxTargets'];
$routes['con-ajax-weekly/(\d+)']   = ['controller' => 'Phase2\Con_DashboardController', 'method' => 'ajaxWeeklyData'];
$routes['con-ajax-workorders/(\d+)']= ['controller' => 'Phase2\Con_DashboardController', 'method' => 'ajaxWorkOrders'];
// ── Role-Specific Dashboards ───────────────────────────────────────────────
$routes['operations-dashboard']           = ['controller' => 'Phase2\Con_DashboardController', 'method' => 'operationsDashboard'];
$routes['con-site-engineer-dashboard']    = ['controller' => 'Phase2\Con_DashboardController', 'method' => 'siteEngineerDashboard'];
$routes['purchase-team-lead-dashboard']   = ['controller' => 'Phase2\Con_DashboardController', 'method' => 'purchaseTeamLeadDashboard'];
$routes['draft-dashboard']                = ['controller' => 'Phase2\Con_DashboardController', 'method' => 'draftDashboard'];
$routes['land-dashboard']                 = ['controller' => 'Phase2\Con_DashboardController', 'method' => 'landDashboard'];
$routes['project-architecture-dashboard']        = ['controller' => 'Phase2\Con_DashboardController', 'method' => 'projectArchitectureDashboard'];
$routes['project-structural-design-dashboard']   = ['controller' => 'Phase2\Con_DashboardController', 'method' => 'projectStructuralDesignDashboard'];
$routes['estimator-dashboard']                   = ['controller' => 'Phase2\Con_DashboardController', 'method' => 'estimatorDashboard'];
$routes['purchase-executive-dashboard']          = ['controller' => 'Phase2\Con_DashboardController', 'method' => 'purchaseExecutiveDashboard'];

// Monthly work report
$routes['con-monthly-work-report']               = ['controller' => 'Phase2\Con_MonthlyWorkReportController', 'method' => 'index'];

// ── Site IT & Security Operations ──────────────────────────────────────────────
$routes['con-security-systems'] = ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'systemLists'];
$routes['con-security-systems/create'] = ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'systemCreate'];
$routes['con-security-systems/store'] = ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'systemStore'];
$routes['con-security-systems/edit'] = ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'systemEdit'];
$routes['con-security-systems/update'] = ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'systemUpdate'];
$routes['con-security-systems/view'] = ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'systemView'];

$routes['con-security-equipments'] = ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'equipmentLists'];
$routes['con-security-equipments/create'] = ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'equipmentCreate'];
$routes['con-security-equipments/store'] = ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'equipmentStore'];
$routes['con-security-equipments/view'] = ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'equipmentView'];

$routes['con-security-logs'] = ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'logLists'];
$routes['con-security-logs/create'] = ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'logCreate'];
$routes['con-security-logs/store'] = ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'logStore'];
$routes['con-security-logs/approve'] = ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'logApprove'];
$routes['con-security-logs/view'] = ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'logView'];

$routes['con-security-incidents'] = ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'incidentLists'];
$routes['con-security-incidents/create'] = ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'incidentCreate'];
$routes['con-security-incidents/store'] = ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'incidentStore'];
$routes['con-security-incidents/approve'] = ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'incidentApprove'];
$routes['con-security-incidents/view'] = ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'incidentView'];

// ── QC Module ──────────────────────────────────────────────────────────────
$routes['qc-dashboard']                = ['controller' => 'Phase2\QCController', 'method' => 'index'];
$routes['qc-inspection-create']        = ['controller' => 'Phase2\QCController', 'method' => 'createInspection'];
$routes['qc-inspection/(\d+)']         = ['controller' => 'Phase2\QCController', 'method' => 'showInspection'];
$routes['qc-inspection-store']         = ['controller' => 'Phase2\QCController', 'method' => 'storeInspection'];
$routes['qc-material-test-create']     = ['controller' => 'Phase2\QCController', 'method' => 'createMaterialTest'];
$routes['qc-material-test-store']      = ['controller' => 'Phase2\QCController', 'method' => 'storeMaterialTest'];
$routes['qc-ajax-checklists']          = ['controller' => 'Phase2\QCController', 'method' => 'ajaxGetChecklists'];
$routes['qc-ajax-receiving-items']     = ['controller' => 'Phase2\QCController', 'method' => 'ajaxGetReceivingItems'];

$routes['qc-test-types']               = ['controller' => 'Phase2\QCController', 'method' => 'testTypesIndex'];
$routes['qc-test-types-store']         = ['controller' => 'Phase2\QCController', 'method' => 'testTypesStore'];
$routes['qc-test-types-update']        = ['controller' => 'Phase2\QCController', 'method' => 'testTypesUpdate'];
$routes['qc-test-types-delete']        = ['controller' => 'Phase2\\QCController', 'method' => 'testTypesDelete'];

// ── QC Checklist Management ────────────────────────────────────────────────
$routes['qc-checklists']               = ['controller' => 'Phase2\\QCController', 'method' => 'checklistsIndex'];
$routes['qc-checklist-store']          = ['controller' => 'Phase2\\QCController', 'method' => 'checklistStore'];
$routes['qc-checklist-update']         = ['controller' => 'Phase2\\QCController', 'method' => 'checklistUpdate'];
$routes['qc-checklist-delete']         = ['controller' => 'Phase2\\QCController', 'method' => 'checklistDelete'];
// ── Operators / Contractors ────────────────────────────────────────────────

$routes['con-contractors']              = ['controller' => 'Phase2\Con_ContractorController', 'method' => 'index'];
$routes['con-contractor-create']        = ['controller' => 'Phase2\Con_ContractorController', 'method' => 'create'];
$routes['con-contractor-store']         = ['controller' => 'Phase2\Con_ContractorController', 'method' => 'store'];
$routes['con-contractor-edit/(\d+)']    = ['controller' => 'Phase2\Con_ContractorController', 'method' => 'edit'];
$routes['con-contractor-update/(\d+)']  = ['controller' => 'Phase2\Con_ContractorController', 'method' => 'update'];
$routes['con-contractor-delete']        = ['controller' => 'Phase2\Con_ContractorController', 'method' => 'delete'];
$routes['con-contractors-import']       = ['controller' => 'Phase2\Con_ContractorController', 'method' => 'importCsv'];

// ── Contractor Bill Requests ───────────────────────────────────────────────
$routes['con-contractor-bills']              = ['controller' => 'Phase2\Con_ContractorBillController', 'method' => 'index'];
$routes['con-contractor-bill-create']        = ['controller' => 'Phase2\Con_ContractorBillController', 'method' => 'create'];
$routes['con-contractor-bill-store']         = ['controller' => 'Phase2\Con_ContractorBillController', 'method' => 'store'];
$routes['con-contractor-bill/(\d+)']         = ['controller' => 'Phase2\Con_ContractorBillController', 'method' => 'show'];
$routes['con-contractor-bill-verify/(\d+)']  = ['controller' => 'Phase2\Con_ContractorBillController', 'method' => 'verify'];
$routes['con-contractor-bill-approve/(\d+)'] = ['controller' => 'Phase2\Con_ContractorBillController', 'method' => 'approve'];
$routes['con-contractor-bill-reject/(\d+)']  = ['controller' => 'Phase2\Con_ContractorBillController', 'method' => 'reject'];
$routes['con-contractor-bill-payment/(\d+)'] = ['controller' => 'Phase2\Con_ContractorBillController', 'method' => 'addPayment'];
$routes['con-ajax-project-targets']           = ['controller' => 'Phase2\Con_ContractorBillController', 'method' => 'ajaxGetTargetsByProject'];

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
$routes['con-material-store-ajax']     = ['controller' => 'Phase2\Con_MaterialController', 'method' => 'ajaxStoreMaterial'];
$routes['con-material-edit/(\d+)']     = ['controller' => 'Phase2\Con_MaterialController', 'method' => 'edit'];
$routes['con-material-update/(\d+)']   = ['controller' => 'Phase2\Con_MaterialController', 'method' => 'update'];
$routes['con-materials-import']        = ['controller' => 'Phase2\Con_MaterialController', 'method' => 'importCsv'];
$routes['con-material-delete/(\d+)']   = ['controller' => 'Phase2\Con_MaterialController', 'method' => 'delete'];
$routes['con-material-category-store']          = ['controller' => 'Phase2\Con_MaterialController', 'method' => 'storeCategory'];
$routes['con-material-category-update/(\d+)']   = ['controller' => 'Phase2\Con_MaterialController', 'method' => 'updateCategory'];
$routes['con-material-category-delete/(\d+)']   = ['controller' => 'Phase2\Con_MaterialController', 'method' => 'deleteCategory'];

$routes['con-warehouses']              = ['controller' => 'Phase2\WarehouseController', 'method' => 'index'];
$routes['con-warehouse-create']        = ['controller' => 'Phase2\WarehouseController', 'method' => 'create'];
$routes['con-warehouse-store']         = ['controller' => 'Phase2\WarehouseController', 'method' => 'store'];
$routes['con-warehouse-edit/(\d+)']    = ['controller' => 'Phase2\WarehouseController', 'method' => 'edit'];
$routes['con-warehouse-update/(\d+)']  = ['controller' => 'Phase2\WarehouseController', 'method' => 'update'];
$routes['con-warehouses-import']       = ['controller' => 'Phase2\WarehouseController', 'method' => 'importCsv'];

$routes['con-stocks']                  = ['controller' => 'Phase2\Con_MaterialController', 'method' => 'stocks']; // Added to existing Con_MaterialController for simplicity

// ── Vendors ────────────────────────────────────────────────────────────────
$routes['con-vendors']                 = ['controller' => 'Phase2\Con_VendorController', 'method' => 'index'];
$routes['con-vendor-create']           = ['controller' => 'Phase2\Con_VendorController', 'method' => 'create'];
$routes['con-vendor-store']            = ['controller' => 'Phase2\Con_VendorController', 'method' => 'store'];
$routes['con-vendor-edit/(\d+)']       = ['controller' => 'Phase2\Con_VendorController', 'method' => 'edit'];
$routes['con-vendor-update/(\d+)']     = ['controller' => 'Phase2\Con_VendorController', 'method' => 'update'];
$routes['con-vendor/(\d+)']            = ['controller' => 'Phase2\Con_VendorController', 'method' => 'show'];
$routes['con-vendor-delete']           = ['controller' => 'Phase2\Con_VendorController', 'method' => 'delete'];
$routes['con-vendor-import']           = ['controller' => 'Phase2\Con_VendorController', 'method' => 'importCsv'];

// ── Purchase Requests ──────────────────────────────────────────────────────
$routes['con-purchase-requests']       = ['controller' => 'Phase2\Con_PurchaseRequestController', 'method' => 'index'];
$routes['con-purchase-request-create'] = ['controller' => 'Phase2\Con_PurchaseRequestController', 'method' => 'create'];
$routes['con-purchase-request-store']  = ['controller' => 'Phase2\Con_PurchaseRequestController', 'method' => 'store'];
$routes['con-purchase-request/(\d+)']  = ['controller' => 'Phase2\Con_PurchaseRequestController', 'method' => 'show'];
$routes['con-purchase-request-approve/(\d+)'] = ['controller' => 'Phase2\Con_PurchaseRequestController', 'method' => 'approve'];
$routes['con-purchase-request-assign/(\d+)'] = ['controller' => 'Phase2\Con_PurchaseRequestController', 'method' => 'assignExecutive'];
$routes['con-boq-remaining/(\d+)']    = ['controller' => 'Phase2\Con_PurchaseRequestController', 'method' => 'boqRemaining'];
$routes['con-ajax-pr-boqs']           = ['controller' => 'Phase2\Con_PurchaseRequestController', 'method' => 'ajaxGetProjectBoqs'];

// ── Quotations & CST ──────────────────────────────────────────────────────
$routes['con-quotation-create/(\d+)']  = ['controller' => 'Phase2\Con_QuotationController', 'method' => 'create'];
$routes['con-quotation-store']         = ['controller' => 'Phase2\Con_QuotationController', 'method' => 'store'];
$routes['con-quotation-compare/(\d+)'] = ['controller' => 'Phase2\Con_QuotationController', 'method' => 'compare'];
$routes['con-quotation-select']        = ['controller' => 'Phase2\Con_QuotationController', 'method' => 'selectWinner'];
$routes['con-quotation-edit/(\d+)']    = ['controller' => 'Phase2\Con_QuotationController', 'method' => 'edit'];
$routes['con-quotation-update/(\d+)']  = ['controller' => 'Phase2\Con_QuotationController', 'method' => 'update'];
$routes['con-quotation-delete/(\d+)']  = ['controller' => 'Phase2\Con_QuotationController', 'method' => 'delete'];

// ── Purchase Orders ────────────────────────────────────────────────────────
$routes['con-purchase-orders']         = ['controller' => 'Phase2\Con_PurchaseOrderController', 'method' => 'index'];
$routes['con-purchase-order/(\d+)']    = ['controller' => 'Phase2\Con_PurchaseOrderController', 'method' => 'show'];
$routes['con-purchase-order-generate/(\d+)'] = ['controller' => 'Phase2\Con_PurchaseOrderController', 'method' => 'generate'];
$routes['con-purchase-order-approve/(\d+)']  = ['controller' => 'Phase2\Con_PurchaseOrderController', 'method' => 'approvePO'];
$routes['con-purchase-order-reject/(\d+)']   = ['controller' => 'Phase2\Con_PurchaseOrderController', 'method' => 'rejectPO'];

$routes['con-material-receivings']             = ['controller' => 'Phase2\Con_MaterialReceivingController', 'method' => 'index'];
$routes['con-material-receiving-create/(\d+)'] = ['controller' => 'Phase2\Con_MaterialReceivingController', 'method' => 'create'];
$routes['con-material-receiving-store']        = ['controller' => 'Phase2\Con_MaterialReceivingController', 'method' => 'store'];
$routes['con-material-receiving-qc/(\d+)']     = ['controller' => 'Phase2\Con_MaterialReceivingController', 'method' => 'qcReview'];
$routes['con-material-receiving-pm/(\d+)']     = ['controller' => 'Phase2\Con_MaterialReceivingController', 'method' => 'pmStore'];

// ── Material Receiving CSV Import ──────────────────────────────────────────
$routes['con-material-receiving-import']          = ['controller' => 'Phase2\Con_MaterialReceivingImportController', 'method' => 'index'];
$routes['con-material-receiving-import-process']  = ['controller' => 'Phase2\Con_MaterialReceivingImportController', 'method' => 'import'];
$routes['con-material-receiving-import-template'] = ['controller' => 'Phase2\Con_MaterialReceivingImportController', 'method' => 'downloadTemplate'];

// ── Material Returns ───────────────────────────────────────────────────────
$routes['con-material-returns']             = ['controller' => 'Phase2\Con_MaterialReturnController', 'method' => 'index'];
$routes['con-material-returns/update/(\d+)'] = ['controller' => 'Phase2\Con_MaterialReturnController', 'method' => 'updateStatus'];

// ── Wastage Management ───────────────────────────────────────────────────
$routes['con-wastage']                             = ['controller' => 'Phase2\Con_WastageController', 'method' => 'index'];
$routes['con-wastage-create']                      = ['controller' => 'Phase2\Con_WastageController', 'method' => 'create'];
$routes['con-wastage-store']                       = ['controller' => 'Phase2\Con_WastageController', 'method' => 'store'];
$routes['con-wastage-show/(\d+)']                  = ['controller' => 'Phase2\Con_WastageController', 'method' => 'show'];
$routes['con-wastage-approve-qc/(\d+)']            = ['controller' => 'Phase2\Con_WastageController', 'method' => 'approveQC'];
$routes['con-wastage-approve-purchase/(\d+)']      = ['controller' => 'Phase2\Con_WastageController', 'method' => 'approvePurchase'];
$routes['con-wastage-receive-payment/(\d+)']       = ['controller' => 'Phase2\Con_WastageController', 'method' => 'receivePayment'];

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
$routes['con-ajax-material-stock']     = ['controller' => 'Phase2\Con_BOQController', 'method' => 'ajaxGetMaterialStock'];
$routes['con-ajax-check-boq-limits']     = ['controller' => 'Phase2\Con_BOQController', 'method' => 'ajaxCheckBOQLimits'];


// (Working Plan + Budget routes moved below to avoid duplicates — see lines 839+)

// ── Target Fixed ──────────────────────────────────────────────────────────


// ── Daily Progress Reports ────────────────────────────────────────────────
$routes['con-weekly-reports']           = ['controller' => 'Phase2\Con_WeeklyReportController', 'method' => 'index'];
$routes['con-weekly-report-create']     = ['controller' => 'Phase2\Con_WeeklyReportController', 'method' => 'create'];
$routes['con-weekly-report-store']      = ['controller' => 'Phase2\Con_WeeklyReportController', 'method' => 'store'];
$routes['con-weekly-report/(\d+)']      = ['controller' => 'Phase2\Con_WeeklyReportController', 'method' => 'show'];
$routes['con-weekly-report-approve/(\d+)'] = ['controller' => 'Phase2\Con_WeeklyReportController', 'method' => 'approve'];
$routes['con-ajax-work-plans']          = ['controller' => 'Phase2\Con_WeeklyReportController', 'method' => 'ajaxWorkPlans'];
$routes['con-ajax-report-targets']      = ['controller' => 'Phase2\Con_WeeklyReportController', 'method' => 'ajaxTargets'];
$routes['con-vendor-payment-ajax-penalty']      = ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'ajaxGetPenalty'];
$routes['con-vendor-payment-ajax-advance']      = ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'ajaxGetAdvanceSummary'];
$routes['con-vendor-payment-ajax-receivable']   = ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'ajaxGetVendorReceivable'];
$routes['con-ajax-po-by-vendor']                = ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'ajaxGetPOsByVendor'];
$routes['con-ajax-po-by-project']               = ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'ajaxGetPOsByProject'];
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
$routes['con-vendor-payment-show/(\d+)']    = ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'show'];
$routes['con-vendor-payment-document/(\d+)']= ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'addDocument'];
$routes['con-vendor-payment-approve/(\d+)'] = ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'approve'];
$routes['con-vendor-payment-reject/(\d+)']  = ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'reject'];
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

// ── CSV Imports (Pre-Construction Modules) ────────────────────────────────
$routes['con-land-visibility-import']        = ['controller' => 'Phase2\\LandVisibilityStudyController',      'method' => 'importCsv'];
$routes['con-architect-engineering-import']  = ['controller' => 'Phase2\\ArchitectEngineeringController',     'method' => 'importCsv'];
$routes['con-design-type-import']            = ['controller' => 'Phase2\\DesignTypeController',               'method' => 'importCsv'];
$routes['con-project-drawing-import']        = ['controller' => 'Phase2\\ProjectDrawingController',           'method' => 'importCsv'];
$routes['con-structure-design-import']       = ['controller' => 'Phase2\\ProjectStructuralDesignController',  'method' => 'importCsv'];
$routes['con-soil-test-import']              = ['controller' => 'Phase2\\SoilTestController',                 'method' => 'importCsv'];
$routes['con-work-type-import']              = ['controller' => 'Phase2\\Con_WorkTypeController',             'method' => 'importCsv'];

// ── CSV Imports Batch 3 (Estimation & Targets) ────────────────────────────
$routes['con-estimate-import']               = ['controller' => 'Phase2\\Con_InitialEstimateController',      'method' => 'importCsv'];

// ── Initial Estimate Budgets ──────────────────────────────────────────────
$routes['con-estimates']              = ['controller' => 'Phase2\Con_InitialEstimateController', 'method' => 'index'];
$routes['con-estimate-create']        = ['controller' => 'Phase2\Con_InitialEstimateController', 'method' => 'create'];
$routes['con-estimate-store']         = ['controller' => 'Phase2\Con_InitialEstimateController', 'method' => 'store'];
$routes['con-estimate-show/(\d+)']    = ['controller' => 'Phase2\Con_InitialEstimateController', 'method' => 'show'];
$routes['con-estimate-edit/(\d+)']    = ['controller' => 'Phase2\Con_InitialEstimateController', 'method' => 'edit'];
$routes['con-estimate-update/(\d+)']  = ['controller' => 'Phase2\Con_InitialEstimateController', 'method' => 'update'];
$routes['con-estimate-approve/(\d+)'] = ['controller' => 'Phase2\Con_InitialEstimateController', 'method' => 'approveBudget'];
$routes['con-estimate-cancel/(\d+)']  = ['controller' => 'Phase2\Con_InitialEstimateController', 'method' => 'cancelBudget'];
$routes['con-estimate-print/(\d+)']   = ['controller' => 'Phase2\Con_InitialEstimateController', 'method' => 'printView'];
$routes['con-ajax-arch-by-project']     = ['controller' => 'Phase2\\Con_InitialEstimateController', 'method' => 'ajaxArchByProject'];
$routes['con-ajax-designs-by-project']  = ['controller' => 'Phase2\\Con_InitialEstimateController', 'method' => 'ajaxDesignsByProject'];
$routes['con-estimate-ajax-add-material'] = ['controller' => 'Phase2\\Con_InitialEstimateController', 'method' => 'ajaxAddMaterial'];

// ── Material Issues (Stock OUT) ───────────────────────────────────────────
$routes['con-material-issues']               = ['controller' => 'Phase2\Con_MaterialIssueController', 'method' => 'index'];
$routes['con-material-issue-create']         = ['controller' => 'Phase2\Con_MaterialIssueController', 'method' => 'create'];
$routes['con-material-issue-store']          = ['controller' => 'Phase2\Con_MaterialIssueController', 'method' => 'store'];
$routes['con-material-issue-csv-import']     = ['controller' => 'Phase2\Con_MaterialIssueController', 'method' => 'importCsv'];
$routes['con-material-issue/(\d+)']          = ['controller' => 'Phase2\Con_MaterialIssueController', 'method' => 'show'];


// ── Vendor Penalties ──────────────────────────────────────────────────────
$routes['con-ajax-wo-drawings']            = ['controller' => 'Phase2\\Con_WorkOrderController', 'method' => 'ajaxGetDrawings'];
$routes['con-ajax-wo-check']               = ['controller' => 'Phase2\\Con_WorkOrderController', 'method' => 'ajaxCheckExistingWO'];
$routes['con-ajax-wo-work-types']          = ['controller' => 'Phase2\\Con_WorkOrderController', 'method' => 'ajaxGetWorkTypesByDesign'];
$routes['con-vendor-penalties']              = ['controller' => 'Phase2\Con_VendorPenaltyController', 'method' => 'index'];
$routes['con-vendor-penalty-create']         = ['controller' => 'Phase2\Con_VendorPenaltyController', 'method' => 'create'];
$routes['con-vendor-penalty-store']          = ['controller' => 'Phase2\Con_VendorPenaltyController', 'method' => 'store'];
$routes['con-vendor-penalty-update-status']  = ['controller' => 'Phase2\Con_VendorPenaltyController', 'method' => 'updateStatus'];
$routes['con-vendor-penalty-delete']         = ['controller' => 'Phase2\Con_VendorPenaltyController', 'method' => 'delete'];

// ── Target Fixed — missing routes ─────────────────────────────────────────

// ── Work Orders ────────────────────────────────────────────────────────────
$routes['con-work-orders']                 = ['controller' => 'Phase2\\Con_WorkOrderController', 'method' => 'index'];
$routes['con-work-order-create']           = ['controller' => 'Phase2\\Con_WorkOrderController', 'method' => 'create'];
$routes['con-work-order-store']            = ['controller' => 'Phase2\\Con_WorkOrderController', 'method' => 'store'];
$routes['con-work-order/(\d+)']            = ['controller' => 'Phase2\\Con_WorkOrderController', 'method' => 'show'];
$routes['con-work-order-edit/(\d+)']       = ['controller' => 'Phase2\\Con_WorkOrderController', 'method' => 'edit'];
$routes['con-work-order-update/(\d+)']     = ['controller' => 'Phase2\\Con_WorkOrderController', 'method' => 'update'];
$routes['con-work-order-approve/(\d+)']    = ['controller' => 'Phase2\\Con_WorkOrderController', 'method' => 'approve'];
$routes['con-work-order-reject/(\d+)']     = ['controller' => 'Phase2\\Con_WorkOrderController', 'method' => 'reject'];
$routes['con-work-order-complete/(\d+)']   = ['controller' => 'Phase2\\Con_WorkOrderController', 'method' => 'markComplete'];
$routes['con-work-order-qc-approve/(\d+)'] = ['controller' => 'Phase2\\Con_WorkOrderController', 'method' => 'qcApprove'];
$routes['con-work-order-delete']           = ['controller' => 'Phase2\\Con_WorkOrderController', 'method' => 'delete'];
$routes['con-work-order-print/(\d+)']      = ['controller' => 'Phase2\\Con_WorkOrderController', 'method' => 'printView'];
$routes['con-ajax-wo-by-project']          = ['controller' => 'Phase2\\Con_WorkOrderController', 'method' => 'ajaxGetByProject'];
$routes['con-ajax-wo-details']             = ['controller' => 'Phase2\\Con_WorkOrderController', 'method' => 'ajaxGetDetails'];
$routes['con-ajax-wo-work-plans']          = ['controller' => 'Phase2\\Con_WorkOrderController', 'method' => 'ajaxGetWorkPlans'];
$routes['con-ajax-wo-targets']             = ['controller' => 'Phase2\\Con_WorkOrderController', 'method' => 'ajaxGetTargets'];
$routes['con-ajax-wo-drawings']            = ['controller' => 'Phase2\\Con_WorkOrderController', 'method' => 'ajaxGetDrawings'];
$routes['con-ajax-wo-check']               = ['controller' => 'Phase2\\Con_WorkOrderController', 'method' => 'ajaxCheckExistingWO'];

// ── Financial Transactions ─────────────────────────────────────────────────
$routes['con-transactions']               = ['controller' => 'Phase2\\Con_FinancialTransactionController', 'method' => 'index'];
$routes['con-pending-payments']           = ['controller' => 'Phase2\\Con_FinancialTransactionController', 'method' => 'pendingList'];
$routes['con-process-payment/([^/]+)/(\d+)'] = ['controller' => 'Phase2\\Con_FinancialTransactionController', 'method' => 'process'];
$routes['con-payment-store']              = ['controller' => 'Phase2\\Con_FinancialTransactionController', 'method' => 'store'];
$routes['con-transaction/(\d+)']          = ['controller' => 'Phase2\\Con_FinancialTransactionController', 'method' => 'show'];

// ── Expense Adjustments ──────────────────────────────────────────────────────
$routes['expense-adjustments']           = ['controller' => 'ExpenseAdjustmentController', 'method' => 'index'];
$routes['expense-adjustments/create']    = ['controller' => 'ExpenseAdjustmentController', 'method' => 'create'];
$routes['expense-adjustments/store']     = ['controller' => 'ExpenseAdjustmentController', 'method' => 'store'];
$routes['expense-adjustments/show']      = ['controller' => 'ExpenseAdjustmentController', 'method' => 'show']; // Will use $_GET['id']
$routes['expense-adjustments/approve']   = ['controller' => 'ExpenseAdjustmentController', 'method' => 'approve'];
$routes['expense-adjustments/reject']    = ['controller' => 'ExpenseAdjustmentController', 'method' => 'reject'];
$routes['expense-adjustments/add-payment'] = ['controller' => 'ExpenseAdjustmentController', 'method' => 'addPayment'];
// ── Asset Management Module ───────────────────────────────────────────────

$routes['office-assets']                                   = ['controller' => 'Phase2\\AssetManagementController', 'method' => 'index'];
$routes['office-assets/store']                             = ['controller' => 'Phase2\\AssetManagementController', 'method' => 'store'];
$routes['office-assets/show/(\d+)']                        = ['controller' => 'Phase2\\AssetManagementController', 'method' => 'show'];
$routes['office-assets/update-code/(\d+)']                 = ['controller' => 'Phase2\\AssetManagementController', 'method' => 'updateAssetCode'];
$routes['office-assets/update/(\d+)']                      = ['controller' => 'Phase2\\AssetManagementController', 'method' => 'update'];
$routes['office-assets/transfer/(\d+)']                    = ['controller' => 'Phase2\\AssetManagementController', 'method' => 'transfer'];
$routes['office-assets/condition/(\d+)']                   = ['controller' => 'Phase2\\AssetManagementController', 'method' => 'updateCondition'];
$routes['office-assets/dispose/(\d+)']                     = ['controller' => 'Phase2\\AssetManagementController', 'method' => 'dispose'];
$routes['asset-products']                                  = ['controller' => 'Phase2\\AssetManagementController', 'method' => 'products'];
$routes['asset-products/store']                            = ['controller' => 'Phase2\\AssetManagementController', 'method' => 'storeProduct'];
$routes['asset-products/update']                           = ['controller' => 'Phase2\\AssetManagementController', 'method' => 'updateProduct'];
$routes['asset-products/delete']                           = ['controller' => 'Phase2\\AssetManagementController', 'method' => 'deleteProduct'];
$routes['asset-extra-purchases']                    = ['controller' => 'Phase2\\AssetManagementController', 'method' => 'extraPurchases'];
$routes['asset-extra-purchases/store']              = ['controller' => 'Phase2\\AssetManagementController', 'method' => 'storeExtraPurchase'];
$routes['asset-extra-purchases/approve/(\d+)']      = ['controller' => 'Phase2\\AssetManagementController', 'method' => 'approveExtraPurchase'];
$routes['asset-extra-purchases/purchased/(\d+)']    = ['controller' => 'Phase2\\AssetManagementController', 'method' => 'markPurchased'];
$routes['asset-extra-purchases/confirm/(\d+)']      = ['controller' => 'Phase2\\AssetManagementController', 'method' => 'confirmPurchased'];

// =============================================================================
// END PHASE 2 ROUTES
// =============================================================================

// ── Working Plans ─────────────────────────────────────────────────────────────
$routes['con-project-progress-report/(\d+)'] = ['controller' => 'Phase2\Con_DashboardController', 'method' => 'projectProgressReport'];
$routes['con-progress-report']               = ['controller' => 'Phase2\Con_ProgressReportController', 'method' => 'index'];

// ── Budget Extension Requests ─────────────────────────────────────────────────
$routes['con-budget-extends']              = ['controller' => 'Phase2\Con_BudgetExtendController', 'method' => 'index'];
$routes['con-budget-extend-create']        = ['controller' => 'Phase2\Con_BudgetExtendController', 'method' => 'create'];
$routes['con-budget-extend-store']         = ['controller' => 'Phase2\Con_BudgetExtendController', 'method' => 'store'];
$routes['con-budget-extend-approve/(\d+)'] = ['controller' => 'Phase2\Con_BudgetExtendController', 'method' => 'approve'];
$routes['con-budget-extend-reject/(\d+)']  = ['controller' => 'Phase2\Con_BudgetExtendController', 'method' => 'reject'];

// ── AJAX: Cascade project → plans + budgets ───────────────────────────────
// ── AJAX: Cascade project → targets ──────────────────────────────────────

// ==============================================================================
// QC & INTERNAL AUDIT MODULE
// ==============================================================================
$routes['qc-audit-dashboard']        = ['controller' => 'QcAuditController', 'method' => 'dashboard'];
$routes['qc-advanced-audit']         = ['controller' => 'QcAuditController', 'method' => 'advancedAudit'];
$routes['qc-audit/materials']        = ['controller' => 'QcAuditController', 'method' => 'getAuditMaterials'];
$routes['qc-audit/purchases']        = ['controller' => 'QcAuditController', 'method' => 'getAuditPurchases'];
$routes['qc-audit/payments']         = ['controller' => 'QcAuditController', 'method' => 'getAuditPayments'];
$routes['qc-requests']               = ['controller' => 'QcAuditController', 'method' => 'pendingRequests'];
$routes['qc-audit/approve']          = ['controller' => 'QcAuditController', 'method' => 'approveRequest'];
$routes['qc-audit/trail']            = ['controller' => 'QcAuditController', 'method' => 'getAuditTrail'];

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
