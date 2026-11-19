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
    'my-created-tasks' => ['controller' => 'TaskController', 'method' => 'myCreatedTasks'],
    'my-created-tasks/store' => ['controller' => 'TaskController', 'method' => 'storeMyCreatedTask'],
    'my-created-tasks/create-project' => ['controller' => 'TaskController', 'method' => 'createTodoProject'],
    'my-created-tasks/update-progress' => ['controller' => 'TaskController', 'method' => 'updateMyCreatedTaskProgress'],
    'my-created-tasks/complete/(\d+)' => ['controller' => 'TaskController', 'method' => 'completeMyCreatedTask'],
    'my-created-tasks/delete/(\d+)' => ['controller' => 'TaskController', 'method' => 'deleteMyCreatedTask'],
    'team-performance' => ['controller' => 'TaskController', 'method' => 'teamPerformance'],
    'team-performance/member/(\d+)' => ['controller' => 'TaskController', 'method' => 'teamMemberTasks'],
    'team-performance/assign-task' => ['controller' => 'TaskController', 'method' => 'assignTeamTask'],
    'team-performance/create-project' => ['controller' => 'TaskController', 'method' => 'createTodoProject'],
    'management-task-overview' => ['controller' => 'TaskV2Controller', 'method' => 'managementTaskOverview'],
    'create-task' => ['controller' => 'TaskController', 'method' => 'create'],
    'store-task' => ['controller' => 'TaskController', 'method' => 'store'],
    'task-dashboard' => ['controller' => 'TaskController', 'method' => 'taskDashboard'],
    'update-task-status' => ['controller' => 'TaskController', 'method' => 'updateTaskStatus'],
    'update-task-progress' => ['controller' => 'TaskController', 'method' => 'updateTaskProgress'],
    'rate-task' => ['controller' => 'TaskController', 'method' => 'rateTask'],
    'show-task/(\d+)' => ['controller' => 'TaskController', 'method' => 'show'],
    'edit-task/(\d+)' => ['controller' => 'TaskController', 'method' => 'edit'],
    'update-task/(\d+)' => ['controller' => 'TaskController', 'method' => 'update'],
    'delete-task/(\d+)' => ['controller' => 'TaskController', 'method' => 'delete'],

    // =========================================================================
    // Controller: TaskV2Controller (Task Manage V2)
    // =========================================================================
    'team-performance-v2' => ['controller' => 'TaskV2Controller', 'method' => 'teamPerformance'],
    'team-performance-v2/member/(\d+)' => ['controller' => 'TaskV2Controller', 'method' => 'teamMemberTasks'],
    'team-performance-v2/assign-task' => ['controller' => 'TaskV2Controller', 'method' => 'assignTeamTask'],
    'team-performance-v2/create-project' => ['controller' => 'TaskV2Controller', 'method' => 'createTodoProject'],
    'management-task-overview-v2' => ['controller' => 'TaskV2Controller', 'method' => 'managementTaskOverview'],

    // =========================================================================
    // Controller: MyWorkController (Personal Workspace)
    // =========================================================================
    'my-work' => ['controller' => 'MyWorkController', 'method' => 'index'],
    'my-work-dashboard' => ['controller' => 'MyWorkController', 'method' => 'index'],
    'my-work/delete/(\d+)' => ['controller' => 'MyWorkController', 'method' => 'deleteTask'],
    'my-work/store-task' => ['controller' => 'MyWorkController', 'method' => 'storeTask'],
    'my-work/task-detail/(\d+)' => ['controller' => 'MyWorkController', 'method' => 'taskDetail'],
    'my-work/task-data/(\d+)' => ['controller' => 'MyWorkController', 'method' => 'taskEditData'],
    'my-work/update-task/(\d+)' => ['controller' => 'MyWorkController', 'method' => 'updateTask'],
    'my-work/complete-task/(\d+)' => ['controller' => 'MyWorkController', 'method' => 'completeTask'],
    'my-work/start-task/(\d+)' => ['controller' => 'MyWorkController', 'method' => 'startTask'],
    'my-work/reviewer-action/(\d+)' => ['controller' => 'MyWorkController', 'method' => 'reviewerAction'],
    'my-work/toggle-checklist/(\d+)' => ['controller' => 'MyWorkController', 'method' => 'toggleChecklist'],
    'my-work/add-comment/(\d+)' => ['controller' => 'MyWorkController', 'method' => 'addComment'],
    'my-work/submit-feedback/(\d+)' => ['controller' => 'MyWorkController', 'method' => 'submitFeedback'],
    'my-work/request-extension/(\d+)' => ['controller' => 'MyWorkController', 'method' => 'requestExtension'],
    'my-work/send-reminder/(\d+)' => ['controller' => 'MyWorkController', 'method' => 'sendReminder'],
    'my-work/recurring-template-data/(\d+)' => ['controller' => 'MyWorkController', 'method' => 'recurringTemplateData'],
    'my-work/update-recurring-template/(\d+)' => ['controller' => 'MyWorkController', 'method' => 'updateRecurringTemplate'],
    'my-work/toggle-recurring-template/(\d+)' => ['controller' => 'MyWorkController', 'method' => 'toggleRecurringTemplate'],
    'my-work/delete-recurring-template/(\d+)' => ['controller' => 'MyWorkController', 'method' => 'deleteRecurringTemplate'],

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
    'api/employee/search-suggestions' => ['controller' => 'EmployeeController', 'method' => 'searchSuggestions'],

    // =========================================================================
    // Controller: EmployeeDashboardController
    // =========================================================================
    'employee-dashboard' => ['controller' => 'EmployeeDashboardController', 'method' => 'index'],
    'my-attendance' => ['controller' => 'EmployeeDashboardController', 'method' => 'myAttendance'],
    'my-leaves' => ['controller' => 'EmployeeDashboardController', 'method' => 'myLeaves'],
    'my-profile' => ['controller' => 'EmployeeDashboardController', 'method' => 'myProfile'],
    'my-profile/update-photo' => ['controller' => 'EmployeeDashboardController', 'method' => 'updateProfilePhoto'],
    'my-profile/update-info' => ['controller' => 'EmployeeDashboardController', 'method' => 'updateProfileInfo'],
    'my-tipsoi-info' => ['controller' => 'EmployeeDashboardController', 'method' => 'myTipsoiInfo'],
    'api/employee/tipsoi-data' => ['controller' => 'EmployeeDashboardController', 'method' => 'getTipsoiData'],
    'api/employee/tipsoi-history' => ['controller' => 'EmployeeDashboardController', 'method' => 'getTipsoiHistory'],
    'api/hrm/devices' => ['controller' => 'EmployeeDashboardController', 'method' => 'getDevices'],
    'api/employee/manual-entry/apply' => ['controller' => 'EmployeeDashboardController', 'method' => 'applyManualEntry'],
    'tipsoi-monthly-report' => ['controller' => 'EmployeeDashboardController', 'method' => 'tipsoiMonthlyReport'],
    'tipsoi-daily-report' => ['controller' => 'EmployeeDashboardController', 'method' => 'tipsoiDailyReport'],
    'tipsoi-department-report' => ['controller' => 'EmployeeDashboardController', 'method' => 'tipsoiDepartmentReport'],
    'api/employee/tipsoi-monthly-data' => ['controller' => 'EmployeeDashboardController', 'method' => 'getTipsoiMonthlyData'],
    'api/employee/tipsoi-daily-data' => ['controller' => 'EmployeeDashboardController', 'method' => 'getTipsoiDailyData'],
    'api/employee/tipsoi-department-data' => ['controller' => 'EmployeeDashboardController', 'method' => 'getTipsoiDepartmentData'],
    'update-task-status-employee' => ['controller' => 'EmployeeDashboardController', 'method' => 'updateTaskStatus'],
    'update-task-progress-employee' => ['controller' => 'EmployeeDashboardController', 'method' => 'updateTaskProgress'],
    'tipsoi-pending-requests' => ['controller' => 'EmployeeDashboardController', 'method' => 'tipsoiPendingRequests'],
    'api/employee/tipsoi-pending-requests' => ['controller' => 'EmployeeDashboardController', 'method' => 'getTipsoiPendingRequests'],
    'api/employee/my-pending-approvals' => ['controller' => 'EmployeeDashboardController', 'method' => 'getMyPendingApprovals'],

    // =========================================================================
    // Controller: AttendanceTestController (Test Modules & Late Approvals)
    // =========================================================================
    'attendance-test' => ['controller' => 'AttendanceTestController', 'method' => 'index'],
    'api/attendance-test/sync' => ['controller' => 'AttendanceTestController', 'method' => 'syncPunches'],
    'my-attendance-test' => ['controller' => 'AttendanceTestController', 'method' => 'myAttendanceTest'],
    'my-employees-test' => ['controller' => 'AttendanceTestController', 'method' => 'myTeamAttendance'],
    'apply-late-approval-test' => ['controller' => 'AttendanceTestController', 'method' => 'applyLateApprovalSelf'],
    'cancel-late-approval-test/(\d+)' => ['controller' => 'AttendanceTestController', 'method' => 'cancelLateApprovalSelf'],
    'supervisor-approve-late/(\d+)' => ['controller' => 'AttendanceTestController', 'method' => 'approveSupervisorLate'],
    'supervisor-reject-late/(\d+)' => ['controller' => 'AttendanceTestController', 'method' => 'rejectSupervisorLate'],
    'hr-approve-late/(\d+)' => ['controller' => 'AttendanceTestController', 'method' => 'approveHrLate'],
    'hr-reject-late/(\d+)' => ['controller' => 'AttendanceTestController', 'method' => 'rejectHrLate'],

    // =========================================================================
    // Controller: ShiftController (Shift & Roster Management)
    // =========================================================================
    'shift-lists' => ['controller' => 'ShiftController', 'method' => 'index'],
    'store-shift' => ['controller' => 'ShiftController', 'method' => 'store'],
    'update-shift/(\d+)' => ['controller' => 'ShiftController', 'method' => 'update'],
    'delete-shift/(\d+)' => ['controller' => 'ShiftController', 'method' => 'delete'],
    'assign-shift' => ['controller' => 'ShiftController', 'method' => 'assignShift'],

    // =========================================================================
    // Controller: LeaveTestController (Leave Management - Test)
    // =========================================================================
    'leave-management-test' => ['controller' => 'LeaveTestController', 'method' => 'index'],
    'store-leave-test' => ['controller' => 'LeaveTestController', 'method' => 'storeLeave'],
    'approve-leave-test/(\d+)' => ['controller' => 'LeaveTestController', 'method' => 'approveLeave'],
    'reject-leave-test/(\d+)' => ['controller' => 'LeaveTestController', 'method' => 'rejectLeave'],
    'api/employee-leave-balances/(\d+)' => ['controller' => 'LeaveTestController', 'method' => 'getEmployeeBalances'],
    'my-leaves-test' => ['controller' => 'LeaveTestController', 'method' => 'myLeavesTest'],
    'apply-leave-test' => ['controller' => 'LeaveTestController', 'method' => 'applyLeaveSelf'],
    'cancel-leave-test/(\d+)' => ['controller' => 'LeaveTestController', 'method' => 'cancelLeaveSelf'],
    'supervisor-approve-leave/(\d+)' => ['controller' => 'LeaveTestController', 'method' => 'approveSupervisorLeave'],
    'supervisor-reject-leave/(\d+)' => ['controller' => 'LeaveTestController', 'method' => 'rejectSupervisorLeave'],

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
    'update-hr-signature' => ['controller' => 'LetterTemplateController', 'method' => 'updateOfficialSignature'],
    'save-hr-signature' => ['controller' => 'LetterTemplateController', 'method' => 'saveOfficialSignature'],
    'delete-hr-signature' => ['controller' => 'LetterTemplateController', 'method' => 'deleteOfficialSignature'],
    'set-default-hr-signature' => ['controller' => 'LetterTemplateController', 'method' => 'setDefaultOfficialSignature'],
    'get-hr-signatures' => ['controller' => 'LetterTemplateController', 'method' => 'getOfficialSignaturesAjax'],

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
    'task-sheets-export' => ['controller' => 'EmployeeTaskController', 'method' => 'exportExcel'],
    'task-sheets/export' => ['controller' => 'EmployeeTaskController', 'method' => 'exportExcel'],
    'all-employee-tasks' => ['controller' => 'EmployeeTaskController', 'method' => 'allEmployeesTask'],
    'all-employee-tasks-export' => ['controller' => 'EmployeeTaskController', 'method' => 'exportAllEmployeesExcel'],
    'all-employee-tasks/export' => ['controller' => 'EmployeeTaskController', 'method' => 'exportAllEmployeesExcel'],

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
    'bulk-delete-payslips' => ['controller' => 'PayrollController', 'method' => 'bulkDelete'],
    'send-payslip-email' => ['controller' => 'PayrollController', 'method' => 'sendSinglePayslipEmail'],
    'send-bulk-payslip-email' => ['controller' => 'PayrollController', 'method' => 'sendBulkPayslipEmail'],

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
    'hrm-create-tipsoi-employee' => ['controller' => 'EmployeeController', 'method' => 'createTipsoiEmployeeView'],
    'api/hrm/employees' => ['controller' => 'HrmApiController', 'method' => 'getErpEmployees'],
    'api/hrm/external-employees' => ['controller' => 'HrmApiController', 'method' => 'getExternalEmployees'],
    'api/hrm/quick-create-employee' => ['controller' => 'HrmApiController', 'method' => 'quickCreateEmployee'],
    'api/hrm/create-tipsoi-employee' => ['controller' => 'HrmApiController', 'method' => 'createTipsoiEmployee'],
    'api/hrm/tipsoi-dropdowns' => ['controller' => 'HrmApiController', 'method' => 'getTipsoiDropdowns'],

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
    'api/hrm/leave-category' => ['controller' => 'HrmApiController', 'method' => 'getLeaveCategories'],
    'api/hrm/applied-leave-list' => ['controller' => 'HrmApiController', 'method' => 'getAppliedLeaveList'],
    'api/hrm/leave-management/approve/(.*)' => ['controller' => 'HrmApiController', 'method' => 'approveTipsoiLeave'],
    'api/hrm/leave-management/cancel/(.*)' => ['controller' => 'HrmApiController', 'method' => 'cancelTipsoiLeave'],
    'api/hrm/leave-management/apply/employee/(.*)' => ['controller' => 'HrmApiController', 'method' => 'applyTipsoiLeave'],
    
    // Late Request APIs
    'api/hrm/late-approval-management/apply/employee/(.*)' => ['controller' => 'HrmApiController', 'method' => 'applyTipsoiLateRequest'],
    'api/hrm/late-approval-management/approval-requests/(.*)' => ['controller' => 'HrmApiController', 'method' => 'getTipsoiLateRequests'],
    'api/hrm/late-approval-management/approve/(.*)' => ['controller' => 'HrmApiController', 'method' => 'approveTipsoiLateRequest'],
    'api/hrm/late-approval-management/reject/(.*)' => ['controller' => 'HrmApiController', 'method' => 'rejectTipsoiLateRequest'],
    'api/hrm/late-approval-management/cancel/(.*)' => ['controller' => 'HrmApiController', 'method' => 'cancelTipsoiLateRequest'],
    'api/hrm/late-approval-management/late-approvals/(.*)' => ['controller' => 'HrmApiController', 'method' => 'getTipsoiLateHistory'],
    'api/hrm/late-approval-management/late-entries/(.*)' => ['controller' => 'HrmApiController', 'method' => 'getTipsoiLateEntries'],

    // Manual Entry Cancel
    'api/hrm/manual-entry/cancel/(.*)' => ['controller' => 'HrmApiController', 'method' => 'cancelTipsoiManualEntry'],

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
    'api/hrm/tipsoi-action' => ['controller' => 'HrmApiController', 'method' => 'handleTipsoiAction'],
    'api/hrm/tipsoi-connect' => ['controller' => 'HrmApiController', 'method' => 'connectTipsoiAccount'],
    'tipsoi-email-logs' => ['controller' => 'LeaveController', 'method' => 'tipsoiEmailLogsView'],
    'api/hrm/tipsoi-email-logs' => ['controller' => 'HrmApiController', 'method' => 'getTipsoiEmailLogs'],
    'api/hrm/tipsoi-late-alerts' => ['controller' => 'HrmApiController', 'method' => 'sendTipsoiLateAlerts'],
    'api/hrm/tipsoi-late-email-logs' => ['controller' => 'HrmApiController', 'method' => 'getTipsoiLateEmailLogs'],
    'api/hrm/send-tipsoi-reminders' => ['controller' => 'HrmApiController', 'method' => 'sendTipsoiReminders'],
    'api/tipsoi/trigger-login-reminder' => ['controller' => 'HrmApiController', 'method' => 'triggerLoginReminder'],
    'api/tipsoi/send-reminders-test' => ['controller' => 'HrmApiController', 'method' => 'testTipsoiReminders'],
    'api/hrm/tipsoi-holidays' => ['controller' => 'HrmApiController', 'method' => 'getTipsoiHolidays'],
    'api/hrm/import-tipsoi-holiday' => ['controller' => 'HrmApiController', 'method' => 'importTipsoiHoliday'],
    'hrm-monthly-lwp' => ['controller' => 'LeaveController', 'method' => 'monthlyLwpDashboardView'],

    // Automated Payroll Routes
    'auto-generate-payslip' => ['controller' => 'PayrollController', 'method' => 'showAutoGenerateForm'],
    'api/payroll/department-attendance' => ['controller' => 'PayrollController', 'method' => 'getDepartmentAttendance'],
    'api/payroll/attendance-overrides' => ['controller' => 'PayrollController', 'method' => 'getAttendanceOverrides'],
    'api/payroll/sync-project-attendance' => ['controller' => 'PayrollController', 'method' => 'apiSyncProjectAttendance'],
    'store-bulk-payslips' => ['controller' => 'PayrollController', 'method' => 'storeBulkPayslips'],
    'disburse-batch-payslips' => ['controller' => 'PayrollController', 'method' => 'disburseBatch'],

    // Payroll Workspace
    'payroll-workspace' => ['controller' => 'PayrollController', 'method' => 'workspace'],
    'payroll-project-store' => ['controller' => 'PayrollController', 'method' => 'storePayrollProject'],
    'delete-payroll-project' => ['controller' => 'PayrollController', 'method' => 'deletePayrollProject'],
    'save-workspace-payslips' => ['controller' => 'PayrollController', 'method' => 'saveWorkspacePayslips'],
    'payroll-workspace-pdf' => ['controller' => 'PayrollController', 'method' => 'exportWorkspacePDF'],

    // Payroll Attendance Report
    'payroll-attendance-report' => ['controller' => 'PayrollController', 'method' => 'attendanceReport'],
    'save-attendance-modifications' => ['controller' => 'PayrollController', 'method' => 'saveAttendanceModifications'],
    'payroll-attendance-pdf' => ['controller' => 'PayrollController', 'method' => 'exportAttendancePDF'],

    // Joining List
    'payroll-joining-list' => ['controller' => 'PayrollController', 'method' => 'joiningList'],
    'payroll-joining-list-pdf' => ['controller' => 'PayrollController', 'method' => 'exportJoiningListPDF'],

    // Resign List
    'payroll-resign-list' => ['controller' => 'PayrollController', 'method' => 'resignList'],
    'payroll-resign-list-pdf' => ['controller' => 'PayrollController', 'method' => 'exportResignListPDF'],

    // Unified Payroll Reports Hub
    'payroll-reports' => ['controller' => 'PayrollController', 'method' => 'payrollReports'],

    // Detailed Summary
    'payroll-detailed-summary' => ['controller' => 'PayrollController', 'method' => 'detailedSummary'],
    'payroll-detailed-summary-pdf' => ['controller' => 'PayrollController', 'method' => 'exportDetailedSummaryPDF'],

    // Payroll Summary Report
    'payroll-summary-report' => ['controller' => 'PayrollController', 'method' => 'summaryReport'],
    'payroll-summary-report-pdf' => ['controller' => 'PayrollController', 'method' => 'exportSummaryReportPDF'],

    // Salary Report
    'payroll-salary-report' => ['controller' => 'PayrollController', 'method' => 'salaryReport'],
    'payroll-salary-report-pdf' => ['controller' => 'PayrollController', 'method' => 'exportSalaryReportPDF'],
    'payroll-salary-report-csv' => ['controller' => 'PayrollController', 'method' => 'exportSalaryReportCSV'],

    // Liquidity Management Report
    'payroll-liquidity-report' => ['controller' => 'PayrollController', 'method' => 'liquidityReport'],
    'payroll-liquidity-report-pdf' => ['controller' => 'PayrollController', 'method' => 'exportLiquidityReportPDF'],
    'payroll-liquidity-report-csv' => ['controller' => 'PayrollController', 'method' => 'exportLiquidityReportCSV'],

    // Salary Hold
    'payroll-salary-hold' => ['controller' => 'PayrollController', 'method' => 'salaryHoldPage'],
    'payroll-salary-hold-save' => ['controller' => 'PayrollController', 'method' => 'salaryHoldSave'],
    'payroll-salary-hold-remove' => ['controller' => 'PayrollController', 'method' => 'salaryHoldRemove'],
    'payroll-salary-hold-pdf' => ['controller' => 'PayrollController', 'method' => 'exportSalaryHoldPDF'],

    // =========================================================================
    // Meal Tracker Module Routes
    // =========================================================================
    'meal-tracker' => ['controller' => 'MealTrackerController', 'method' => 'index'],
    'meal-project-store' => ['controller' => 'MealTrackerController', 'method' => 'storeProject'],
    'api/meal-tracker/branch-data' => ['controller' => 'MealTrackerController', 'method' => 'getBranchGridData'],
    'meal-tracker-save' => ['controller' => 'MealTrackerController', 'method' => 'saveDailyGrid'],
    'meal-tracker-history' => ['controller' => 'MealTrackerController', 'method' => 'history'],
    'api/employee/today-meal-status' => ['controller' => 'MealTrackerController', 'method' => 'getTodayMealStatus'],
    'api/employee/toggle-today-meal' => ['controller' => 'MealTrackerController', 'method' => 'toggleTodayMeal'],
    'api/employee/toggle-all-time-meal' => ['controller' => 'MealTrackerController', 'method' => 'toggleAllTimeMeal'],
    'api/meal-tracker/sync-attendance' => ['controller' => 'MealTrackerController', 'method' => 'syncAttendance'],
    'my-meal-tracker' => ['controller' => 'MealTrackerController', 'method' => 'myMeals'],
    'meal-project-delete' => ['controller' => 'MealTrackerController', 'method' => 'deleteProject'],
    'meal-project-update-rate' => ['controller' => 'MealTrackerController', 'method' => 'updateStandardRate'],

    // =========================================================================
    // HR ATS (Applicant Tracking System) Routes
    // =========================================================================
    'ats-tracker' => ['controller' => 'AtsController', 'method' => 'index'],
    'ats-candidate-store' => ['controller' => 'AtsController', 'method' => 'store'],
    'api/ats/candidate-details/(\d+)' => ['controller' => 'AtsController', 'method' => 'getDetails'],
    'ats-candidate-update/(\d+)' => ['controller' => 'AtsController', 'method' => 'update'],
    'api/ats/candidate-delete' => ['controller' => 'AtsController', 'method' => 'delete'],
    'api/ats/quick-update' => ['controller' => 'AtsController', 'method' => 'quickUpdate'],
    'api/ats/quick-edit-details' => ['controller' => 'AtsController', 'method' => 'quickEditDetails'],
    'api/ats/bulk-update' => ['controller' => 'AtsController', 'method' => 'bulkUpdate'],
    'ats-csv-upload' => ['controller' => 'AtsController', 'method' => 'csvUpload'],
    'ats-sample-csv' => ['controller' => 'AtsController', 'method' => 'downloadSampleCsv'],
    'ats-dropdown-settings' => ['controller' => 'AtsController', 'method' => 'dropdownSettings'],
    'api/ats/dropdown-save' => ['controller' => 'AtsController', 'method' => 'saveDropdownOption'],
    'api/ats/dropdown-toggle' => ['controller' => 'AtsController', 'method' => 'toggleDropdownStatus'],
    'api/ats/dropdown-delete' => ['controller' => 'AtsController', 'method' => 'deleteDropdownOption'],
    'ats-dashboard' => ['controller' => 'AtsController', 'method' => 'dashboard'],
    'ats-export-pdf' => ['controller' => 'AtsController', 'method' => 'exportPdf'],
    'ats-export-excel' => ['controller' => 'AtsController', 'method' => 'exportExcel'],
    'ats-export-csv' => ['controller' => 'AtsController', 'method' => 'exportCsv'],
    'api/ats/upload-cv' => ['controller' => 'AtsController', 'method' => 'uploadCv'],
    'ats-trash' => ['controller' => 'AtsController', 'method' => 'trash'],
    'api/ats/candidate-restore' => ['controller' => 'AtsController', 'method' => 'restore'],
    'api/ats/bulk-restore' => ['controller' => 'AtsController', 'method' => 'bulkRestore'],
    'api/ats/candidate-force-delete' => ['controller' => 'AtsController', 'method' => 'forceDelete'],
    'api/ats/bulk-force-delete' => ['controller' => 'AtsController', 'method' => 'bulkForceDelete'],
    'api/ats/empty-trash' => ['controller' => 'AtsController', 'method' => 'emptyTrash'],
    'ats-activity-logs' => ['controller' => 'AtsController', 'method' => 'activityLogs'],

    // HR ATS Job Postings & Public Career Page APIs
    'ats-jobs' => ['controller' => 'AtsJobController', 'method' => 'index'],
    'api/ats/jobs/store' => ['controller' => 'AtsJobController', 'method' => 'store'],
    'api/ats/jobs/details/(\d+)' => ['controller' => 'AtsJobController', 'method' => 'getDetails'],
    'api/ats/jobs/update/(\d+)' => ['controller' => 'AtsJobController', 'method' => 'update'],
    'api/ats/jobs/status' => ['controller' => 'AtsJobController', 'method' => 'updateStatus'],
    'api/ats/jobs/delete' => ['controller' => 'AtsJobController', 'method' => 'delete'],
    'api/jobs' => ['controller' => 'AtsJobController', 'method' => 'publicJobs'],
    'api/jobs/apply' => ['controller' => 'AtsJobController', 'method' => 'publicApply'],
];

