<?php
return [

    // =========================================================================
    // Controller: HrmDashboardController
    // =========================================================================
    'hrm-dashboard' => ['controller' => 'HrmDashboardController', 'method' => 'index'],
    'calendar-load' => ['controller' => 'HrmDashboardController', 'method' => 'calenderLoad'],

    // =========================================================================
    // Controller: TaskController
    // =========================================================================
    'task-lists' => ['controller' => 'TaskController', 'method' => 'index'],
    'create-task' => ['controller' => 'TaskController', 'method' => 'create'],
    'store-task' => ['controller' => 'TaskController', 'method' => 'store'],
    'task-dashboard' => ['controller' => 'TaskController', 'method' => 'taskDashboard'],
    'update-task-status' => ['controller' => 'TaskController', 'method' => 'updateTaskStatus'],
    'update-task-progress' => ['controller' => 'TaskController', 'method' => 'updateTaskProgress'],
    'show-task/(\d+)' => ['controller' => 'TaskController', 'method' => 'show'],
    'edit-task/(\d+)' => ['controller' => 'TaskController', 'method' => 'edit'],
    'update-task/(\d+)' => ['controller' => 'TaskController', 'method' => 'update'],
    'delete-task/(\d+)' => ['controller' => 'TaskController', 'method' => 'delete'],

    // =========================================================================
    // Controller: EmployeeController
    // =========================================================================
    'employee-lists' => ['controller' => 'EmployeeController', 'method' => 'index'],
    'create-employee' => ['controller' => 'EmployeeController', 'method' => 'create'],
    'store-employee' => ['controller' => 'EmployeeController', 'method' => 'store'],
    'edit-employee/(\d+)' => ['controller' => 'EmployeeController', 'method' => 'edit'],
    'update-employee/(\d+)' => ['controller' => 'EmployeeController', 'method' => 'update'],
    'employee-profile/(\d+)' => ['controller' => 'EmployeeController', 'method' => 'show'],
    'delete-employee' => ['controller' => 'EmployeeController', 'method' => 'delete'],
    'employees-excel-download' => ['controller' => 'EmployeeController', 'method' => 'exportExcel'],
    'employees-pdf-download' => ['controller' => 'EmployeeController', 'method' => 'exportPDF'],

    // =========================================================================
    // Controller: EmployeeDashboardController
    // =========================================================================
    'employee-dashboard' => ['controller' => 'EmployeeDashboardController', 'method' => 'index'],
    'my-attendance' => ['controller' => 'EmployeeDashboardController', 'method' => 'myAttendance'],
    'my-leaves' => ['controller' => 'EmployeeDashboardController', 'method' => 'myLeaves'],
    'my-profile' => ['controller' => 'EmployeeDashboardController', 'method' => 'myProfile'],
    'my-tipsoi-info' => ['controller' => 'EmployeeDashboardController', 'method' => 'myTipsoiInfo'],
    'api/employee/tipsoi-data' => ['controller' => 'EmployeeDashboardController', 'method' => 'getTipsoiData'],
    'tipsoi-monthly-report' => ['controller' => 'EmployeeDashboardController', 'method' => 'tipsoiMonthlyReport'],
    'tipsoi-daily-report' => ['controller' => 'EmployeeDashboardController', 'method' => 'tipsoiDailyReport'],
    'tipsoi-department-report' => ['controller' => 'EmployeeDashboardController', 'method' => 'tipsoiDepartmentReport'],
    'api/employee/tipsoi-monthly-data' => ['controller' => 'EmployeeDashboardController', 'method' => 'getTipsoiMonthlyData'],
    'api/employee/tipsoi-daily-data' => ['controller' => 'EmployeeDashboardController', 'method' => 'getTipsoiDailyData'],
    'api/employee/tipsoi-department-data' => ['controller' => 'EmployeeDashboardController', 'method' => 'getTipsoiDepartmentData'],
    'update-task-status-employee' => ['controller' => 'EmployeeDashboardController', 'method' => 'updateTaskStatus'],
    'update-task-progress-employee' => ['controller' => 'EmployeeDashboardController', 'method' => 'updateTaskProgress'],

    // =========================================================================
    // Controller: EmpAttendanceController
    // =========================================================================
    'attendance-list' => ['controller' => 'EmpAttendanceController', 'method' => 'index'],
    'create-attendance' => ['controller' => 'EmpAttendanceController', 'method' => 'create'],
    'store-attendance' => ['controller' => 'EmpAttendanceController', 'method' => 'store'],
    'show-attendance/(\d+)' => ['controller' => 'EmpAttendanceController', 'method' => 'show'],
    'edit-attendance/(\d+)' => ['controller' => 'EmpAttendanceController', 'method' => 'edit'],
    'update-attendance/(\d+)' => ['controller' => 'EmpAttendanceController', 'method' => 'update'],
    'delete-attendance' => ['controller' => 'EmpAttendanceController', 'method' => 'delete'],
    'download-excel' => ['controller' => 'EmpAttendanceController', 'method' => 'downloadExcel'],
    'download-pdf' => ['controller' => 'EmpAttendanceController', 'method' => 'downloadPDF'],
    'mobile-punch' => ['controller' => 'EmpAttendanceController', 'method' => 'createMobilePuch'],
    'store-self-mobile-punch' => ['controller' => 'EmpAttendanceController', 'method' => 'storeMobilePunch'],

    // =========================================================================
    // Controller: AttendanceReportController
    // =========================================================================
    'daily-attendance-report' => ['controller' => 'AttendanceReportController', 'method' => 'attendanceReport'],
    'export-daily-report' => ['controller' => 'AttendanceReportController', 'method' => 'exportDailyReport'],
    'export-daily-report-pdf' => ['controller' => 'AttendanceReportController', 'method' => 'exportDailyReportPDF'],

    // =========================================================================
    // Controller: LateReportController
    // =========================================================================
    'daily-late-report' => ['controller' => 'LateReportController', 'method' => 'lateReport'],
    'export-late-report' => ['controller' => 'LateReportController', 'method' => 'exportLateReport'],
    'export-late-report-pdf' => ['controller' => 'LateReportController', 'method' => 'exportLateReportPDF'],

    // =========================================================================
    // Controller: LeaveReportController
    // =========================================================================
    'leave-report' => ['controller' => 'LeaveReportController', 'method' => 'leaveReport'],
    'export-leave-report' => ['controller' => 'LeaveReportController', 'method' => 'exportLeaveReport'],
    'export-leave-report-pdf' => ['controller' => 'LeaveReportController', 'method' => 'exportLeaveReportPDF'],

    // =========================================================================
    // Controller: AbsentReportController
    // =========================================================================
    'absent-report' => ['controller' => 'AbsentReportController', 'method' => 'absentReport'],
    'export-absent-report' => ['controller' => 'AbsentReportController', 'method' => 'exportAbsentReport'],
    'export-absent-report-pdf' => ['controller' => 'AbsentReportController', 'method' => 'exportAbsentReportPDF'],

    // =========================================================================
    // Controller: JobCardReportController
    // =========================================================================
    'job-card-report' => ['controller' => 'JobCardReportController', 'method' => 'index'],
    'export-job-card-csv' => ['controller' => 'JobCardReportController', 'method' => 'exportCSV'],
    'export-job-card-pdf' => ['controller' => 'JobCardReportController', 'method' => 'exportPDF'],

    // =========================================================================
    // Controller: HrmDailyReportController
    // =========================================================================
    'hrm-daily-report' => ['controller' => 'HrmDailyReportController', 'method' => 'index'],
    'export-hrm-daily-csv' => ['controller' => 'HrmDailyReportController', 'method' => 'exportCSV'],
    'export-hrm-daily-pdf' => ['controller' => 'HrmDailyReportController', 'method' => 'exportPDF'],

    // =========================================================================
    // Controller: HrmMonthlyReportController
    // =========================================================================
    'hrm-monthly-report' => ['controller' => 'HrmMonthlyReportController', 'method' => 'index'],
    'export-hrm-monthly-csv' => ['controller' => 'HrmMonthlyReportController', 'method' => 'exportCSV'],
    'export-hrm-monthly-pdf' => ['controller' => 'HrmMonthlyReportController', 'method' => 'exportPDF'],

    // =========================================================================
    // Controller: HrmDepartmentReportController
    // =========================================================================
    'dept-wise-report' => ['controller' => 'HrmDepartmentReportController', 'method' => 'index'],
    'export-hrm-dept-csv' => ['controller' => 'HrmDepartmentReportController', 'method' => 'exportCSV'],
    'export-hrm-dept-pdf' => ['controller' => 'HrmDepartmentReportController', 'method' => 'exportPDF'],
    'department-kpi' => ['controller' => 'HrmDepartmentReportController', 'method' => 'departmentKPIReport'],
    'department-kpi-pdf' => ['controller' => 'HrmDepartmentReportController', 'method' => 'departmentKPIReportPDF'],

    // =========================================================================
    // Controller: LetterTemplateController
    // =========================================================================
    'letter-templates' => ['controller' => 'LetterTemplateController', 'method' => 'template'],
    'store-letter-template' => ['controller' => 'LetterTemplateController', 'method' => 'storeLetterTemplate'],
    'edit-letter-template/(\d+)' => ['controller' => 'LetterTemplateController', 'method' => 'edit'],
    'update-letter-template/(\d+)' => ['controller' => 'LetterTemplateController', 'method' => 'update'],
    'delete-letter-template/(\d+)' => ['controller' => 'LetterTemplateController', 'method' => 'delete'],
    'generate-letter' => ['controller' => 'LetterTemplateController', 'method' => 'generateLetter'],
    'create-letter-template' => ['controller' => 'LetterTemplateController', 'method' => 'create'],
    'store-generate-letter' => ['controller' => 'LetterTemplateController', 'method' => 'store'],
    'get-employee-info-ajax' => ['controller' => 'LetterTemplateController', 'method' => 'getEmployeeInfo'],
    'get-employee-salary' => ['controller' => 'LetterTemplateController', 'method' => 'getEmployeeSalary'],
    'get-templates-for-type' => ['controller' => 'LetterTemplateController', 'method' => 'getTemplatesForType'],
    'get-template' => ['controller' => 'LetterTemplateController', 'method' => 'getTemplate'],
    'view-letter/(\d+)' => ['controller' => 'LetterTemplateController', 'method' => 'viewLetter'],
    'print-letter/(\d+)' => ['controller' => 'LetterTemplateController', 'method' => 'printLetter'],
    'pdf-letter/(\d+)' => ['controller' => 'LetterTemplateController', 'method' => 'generatePDFLetter'],
    'delete-letter/(\d+)' => ['controller' => 'LetterTemplateController', 'method' => 'deleteLetter'],
    'edit-letter/(\d+)' => ['controller' => 'LetterTemplateController', 'method' => 'editLetter'],
    'update-letter/(\d+)' => ['controller' => 'LetterTemplateController', 'method' => 'updateLetter'],

    // =========================================================================
    // Controller: LeaveController
    // =========================================================================
    'leave-lists' => ['controller' => 'LeaveController', 'method' => 'index'],
    'create-employee-leave' => ['controller' => 'LeaveController', 'method' => 'create'],
    'store-employee-leave' => ['controller' => 'LeaveController', 'method' => 'store'],
    'remaining-days' => ['controller' => 'LeaveController', 'method' => 'remainingDays'],
    'store-self-leave-request' => ['controller' => 'LeaveController', 'method' => 'store'],
    'create-self-leave-request' => ['controller' => 'LeaveController', 'method' => 'createSelf'],
    'remaining-days-self' => ['controller' => 'LeaveController', 'method' => 'remainingDaysSelf'],

    // =========================================================================
    // Controller: HolidayController
    // =========================================================================
    'holiday-lists' => ['controller' => 'HolidayController', 'method' => 'index'],
    'store-holiday' => ['controller' => 'HolidayController', 'method' => 'store'],
    'update-holiday' => ['controller' => 'HolidayController', 'method' => 'update'],
    'delete-holiday' => ['controller' => 'HolidayController', 'method' => 'delete'],

    // =========================================================================
    // Controller: DayOffController
    // =========================================================================
    'day-off' => ['controller' => 'DayOffController', 'method' => 'dayOffManage'],
    'hrm-day-off-lists' => ['controller' => 'DayOffController', 'method' => 'hrmDayOffLists'],
    'get-employee-days-off/(\d+)' => ['controller' => 'DayOffController', 'method' => 'getEmployeeDaysOff'],
    'update-day-off' => ['controller' => 'DayOffController', 'method' => 'updateDayOff'],

    // =========================================================================
    // Controller: EmployeeTaskController
    // =========================================================================
    'store-update-task' => ['controller' => 'EmployeeTaskController', 'method' => 'save'],
    'task-sheets' => ['controller' => 'EmployeeTaskController', 'method' => 'index'],

    // =========================================================================
    // Controller: Leaves\LeaveTypeController
    // =========================================================================
    'leave-types' => ['controller' => 'Leaves\LeaveTypeController', 'method' => 'index'],
    'store-leave-type' => ['controller' => 'Leaves\LeaveTypeController', 'method' => 'store'],
    'update-leave-type' => ['controller' => 'Leaves\LeaveTypeController', 'method' => 'update'],
    'delete-leave-type' => ['controller' => 'Leaves\LeaveTypeController', 'method' => 'delete'],

    // =========================================================================
    // Controller: PayrollController
    // =========================================================================
    'payslip-lists' => ['controller' => 'PayrollController', 'method' => 'index'],
    'create-payslip' => ['controller' => 'PayrollController', 'method' => 'create'],
    'store-payslip' => ['controller' => 'PayrollController', 'method' => 'store'],
    'pay-payslip' => ['controller' => 'PayrollController', 'method' => 'updatePay'],
    'show-payslip/(\d+)' => ['controller' => 'PayrollController', 'method' => 'show'],
    'print-payslip/(\d+)' => ['controller' => 'PayrollController', 'method' => 'print'],
    'delete-payslip/(\d+)' => ['controller' => 'PayrollController', 'method' => 'delete'],
    'get-due-employees' => ['controller' => 'PayrollController', 'method' => 'dueEmployeePayslip'],
    'import-payslips' => ['controller' => 'PayrollController', 'method' => 'showImportForm'],
    'execute-import-payslips' => ['controller' => 'PayrollController', 'method' => 'importCSV'],
    'download-sample-payslips-csv' => ['controller' => 'PayrollController', 'method' => 'downloadSampleCSV'],

    // =========================================================================
    // Controller: AdvanceSalaryController
    // =========================================================================
    'advance-salary' => ['controller' => 'AdvanceSalaryController', 'method' => 'index'],
    'store-advance-salary' => ['controller' => 'AdvanceSalaryController', 'method' => 'store'],
    'approve-advance-salary/(\d+)' => ['controller' => 'AdvanceSalaryController', 'method' => 'approve'],
    'pay-advance-salary/(\d+)' => ['controller' => 'AdvanceSalaryController', 'method' => 'pay'],
    'pay-advance-salary' => ['controller' => 'AdvanceSalaryController', 'method' => 'payPost'],

    // =========================================================================
    // Controller: PayslipItemController
    // =========================================================================
    'payslip-items' => ['controller' => 'PayslipItemController', 'method' => 'index'],
    'store-payslip-item' => ['controller' => 'PayslipItemController', 'method' => 'store'],
    'update-payslip-item' => ['controller' => 'PayslipItemController', 'method' => 'update'],
    'delete-payslip-item' => ['controller' => 'PayslipItemController', 'method' => 'delete'],

    // =========================================================================
    // Batch 1: Employee Views & REST API Routes
    // =========================================================================

    'hrm-external-employees' => ['controller' => 'EmployeeController', 'method' => 'externalEmployeesView'],
    'api/hrm/employees' => ['controller' => 'HrmApiController', 'method' => 'getErpEmployees'],
    'api/hrm/external-employees' => ['controller' => 'HrmApiController', 'method' => 'getExternalEmployees'],

    // =========================================================================
    // Batch 2: Attendance & Absence Views & API Routes
    // =========================================================================
    'hrm-attendance-dashboard' => ['controller' => 'EmpAttendanceController', 'method' => 'attendanceDashboardView'],
    'api/hrm/attendance' => ['controller' => 'HrmApiController', 'method' => 'getAttendanceReport'],

    // =========================================================================
    // Batch 3: Leave History & Requests Views & API Routes
    // =========================================================================
    'hrm-leave-history' => ['controller' => 'LeaveController', 'method' => 'leaveHistoryDashboardView'],
    'api/hrm/leave-history' => ['controller' => 'HrmApiController', 'method' => 'getLeaveHistory'],

    // =========================================================================
    // Batch 4: Leave Balances & Analytics Views & API Routes
    // =========================================================================
    'hrm-leave-balances' => ['controller' => 'LeaveController', 'method' => 'leaveBalancesDashboardView'],
    'api/hrm/leave-balances' => ['controller' => 'HrmApiController', 'method' => 'getLeaveBalances'],

    // =========================================================================
    // Batch 5: Tipsoi Pending Requests Views & API Routes
    // =========================================================================
    'hrm-tipsoi-requests' => ['controller' => 'LeaveController', 'method' => 'tipsoiRequestsDashboardView'],
    'api/hrm/tipsoi-requests' => ['controller' => 'HrmApiController', 'method' => 'getTipsoiRequests'],
    'api/hrm/tipsoi-holidays' => ['controller' => 'HrmApiController', 'method' => 'getTipsoiHolidays'],
    'api/hrm/import-tipsoi-holiday' => ['controller' => 'HrmApiController', 'method' => 'importTipsoiHoliday'],
    'hrm-monthly-lwp' => ['controller' => 'LeaveController', 'method' => 'monthlyLwpDashboardView'],

    // Automated Payroll Routes
    'auto-generate-payslip' => ['controller' => 'PayrollController', 'method' => 'showAutoGenerateForm'],
    'api/payroll/department-attendance' => ['controller' => 'PayrollController', 'method' => 'getDepartmentAttendance'],
    'store-bulk-payslips' => ['controller' => 'PayrollController', 'method' => 'storeBulkPayslips'],
    'disburse-batch-payslips' => ['controller' => 'PayrollController', 'method' => 'disburseBatch'],

    // Payroll Workspace
    'payroll-workspace' => ['controller' => 'PayrollController', 'method' => 'workspace'],
    'payroll-workspace-pdf' => ['controller' => 'PayrollController', 'method' => 'exportWorkspacePDF'],

    // Salary Hold
    'payroll-salary-hold' => ['controller' => 'PayrollController', 'method' => 'salaryHold'],
    'payroll-salary-hold-pdf' => ['controller' => 'PayrollController', 'method' => 'exportSalaryHoldPDF'],

    // Payroll Attendance Report
    'payroll-attendance-report' => ['controller' => 'PayrollController', 'method' => 'attendanceReport'],
    'payroll-attendance-pdf' => ['controller' => 'PayrollController', 'method' => 'exportAttendancePDF'],

    // Joining List
    'payroll-joining-list' => ['controller' => 'PayrollController', 'method' => 'joiningList'],
    'payroll-joining-list-pdf' => ['controller' => 'PayrollController', 'method' => 'exportJoiningListPDF'],

    // Resign List
    'payroll-resign-list' => ['controller' => 'PayrollController', 'method' => 'resignList'],
    'payroll-resign-list-pdf' => ['controller' => 'PayrollController', 'method' => 'exportResignListPDF'],

    // Detailed Summary
    'payroll-detailed-summary' => ['controller' => 'PayrollController', 'method' => 'detailedSummary'],
    'payroll-detailed-summary-pdf' => ['controller' => 'PayrollController', 'method' => 'exportDetailedSummaryPDF'],

    // Payroll Summary Report
    'payroll-summary-report' => ['controller' => 'PayrollController', 'method' => 'summaryReport'],
    'payroll-summary-report-pdf' => ['controller' => 'PayrollController', 'method' => 'exportSummaryReportPDF'],
];
