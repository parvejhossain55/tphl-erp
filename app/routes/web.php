<?php
return [

    // =========================================================================
    // Controller: LoginController
    // =========================================================================
    '' => ['controller' => 'LoginController', 'method' => 'index'],
    'admin-login' => ['controller' => 'LoginController', 'method' => 'index'],
    'logout' => ['controller' => 'LoginController', 'method' => 'logout'],
    'update-activity' => ['controller' => 'LoginController', 'method' => 'updateActivity'],
    'switch-role' => ['controller' => 'SwitchRoleController', 'method' => 'switchRole'],

    // =========================================================================
    // Controller: HomeController
    // =========================================================================
    'home' => ['controller' => 'HomeController', 'method' => 'index'],
    'approve-request/(\d+)' => ['controller' => 'HomeController', 'method' => 'approveFull'],
    'partial-approve-request/(\d+)' => ['controller' => 'HomeController', 'method' => 'approvePartial'],
    'reject-request/(\d+)' => ['controller' => 'HomeController', 'method' => 'reject'],
    'show-request/(\d+)' => ['controller' => 'HomeController', 'method' => 'show'],

    // =========================================================================
    // Controller: ApprovalRequestController
    // =========================================================================
    'approval-requests' => ['controller' => 'ApprovalRequestController', 'method' => 'index'],

    // =========================================================================
    // Controller: UserController
    // =========================================================================
    'user-lists' => ['controller' => 'UserController', 'method' => 'index'],
    'create-user' => ['controller' => 'UserController', 'method' => 'create'],
    'store-user' => ['controller' => 'UserController', 'method' => 'store'],
    'edit-user/(\d+)' => ['controller' => 'UserController', 'method' => 'edit'],
    'user-profile/(\d+)' => ['controller' => 'UserController', 'method' => 'show'],
    'update-user/(\d+)' => ['controller' => 'UserController', 'method' => 'update'],
    'delete-user' => ['controller' => 'UserController', 'method' => 'delete'],

    // =========================================================================
    // Controller: UserActivityLogController
    // =========================================================================
    'user-activity-log' => ['controller' => 'UserActivityLogController', 'method' => 'index'],
    'user-activity-log/employees-by-dept' => ['controller' => 'UserActivityLogController', 'method' => 'employeesByDept'],
    'user-activity-log/employee-summary' => ['controller' => 'UserActivityLogController', 'method' => 'employeeSummary'],

    // =========================================================================
    // Controller: RolePermissionController
    // =========================================================================
    'role-permissions' => ['controller' => 'RolePermissionController', 'method' => 'index'],
    'permissions' => ['controller' => 'RolePermissionController', 'method' => 'permission'],
    'store-permission' => ['controller' => 'RolePermissionController', 'method' => 'store'],
    'update-permission' => ['controller' => 'RolePermissionController', 'method' => 'update'],
    'delete-permission' => ['controller' => 'RolePermissionController', 'method' => 'delete'],

    // =========================================================================
    // Controller: RoleController
    // =========================================================================
    'store-role' => ['controller' => 'RoleController', 'method' => 'store'],
    'get-role' => ['controller' => 'RoleController', 'method' => 'edit'],
    'update-role' => ['controller' => 'RoleController', 'method' => 'update'],

    // =========================================================================
    // Controller: DepartmentController
    // =========================================================================
    'departments' => ['controller' => 'DepartmentController', 'method' => 'index'],
    'store-department' => ['controller' => 'DepartmentController', 'method' => 'store'],
    'update-department' => ['controller' => 'DepartmentController', 'method' => 'update'],
    'delete-department' => ['controller' => 'DepartmentController', 'method' => 'delete'],
    'api/departments' => ['controller' => 'DepartmentController', 'method' => 'publicList'],

    // =========================================================================
    // Controller: DesignationController
    // =========================================================================
    'designations' => ['controller' => 'DesignationController', 'method' => 'index'],
    'store-designation' => ['controller' => 'DesignationController', 'method' => 'store'],
    'update-designation' => ['controller' => 'DesignationController', 'method' => 'update'],
    'delete-designation' => ['controller' => 'DesignationController', 'method' => 'delete'],

    // =========================================================================
    // Controller: MobilePunchReportController
    // =========================================================================
    'mobile-punch-report' => ['controller' => 'MobilePunchReportController', 'method' => 'mobilePunchReport'],
    'export-mobile-punch-report' => ['controller' => 'MobilePunchReportController', 'method' => 'exportMobilePunchReport'],
    'export-mobile-punch-report-pdf' => ['controller' => 'MobilePunchReportController', 'method' => 'exportMobilePunchReportPDF'],

    // =========================================================================
    // Controller: ManpowerRequisitionController
    // =========================================================================
    'requisition-lists' => ['controller' => 'ManpowerRequisitionController', 'method' => 'index'],
    'create-requisition' => ['controller' => 'ManpowerRequisitionController', 'method' => 'create'],
    'store-requisition' => ['controller' => 'ManpowerRequisitionController', 'method' => 'store'],
    'view-requisition/(\d+)' => ['controller' => 'ManpowerRequisitionController', 'method' => 'find'],
    'approve-requisition/(\d+)' => ['controller' => 'ManpowerRequisitionController', 'method' => 'approve'],
    'reject-requisition/(\d+)' => ['controller' => 'ManpowerRequisitionController', 'method' => 'reject'],
    'update-hr-requisition-status/(\d+)' => ['controller' => 'ManpowerRequisitionController', 'method' => 'updateHrStatus'],

    // =========================================================================
    // Controller: EmailStationController
    // =========================================================================
    'email-station' => ['controller' => 'EmailStationController', 'method' => 'index'],
    'create-email-template' => ['controller' => 'EmailStationController', 'method' => 'createEmailTemplate'],
    'store-email-template' => ['controller' => 'EmailStationController', 'method' => 'storeEmailTemplate'],
    'create-email-alert' => ['controller' => 'EmailStationController', 'method' => 'createEmailAlert'],
    'store-email-alert' => ['controller' => 'EmailStationController', 'method' => 'storeEmailAlert'],

    // =========================================================================
    // Controller: SiteManage\SiteSettingController
    // =========================================================================
    'site-settings' => ['controller' => 'SiteManage\SiteSettingController', 'method' => 'index'],
    'store-site-setting' => ['controller' => 'SiteManage\SiteSettingController', 'method' => 'storeOrUpdate'],

    // =========================================================================
    // Controller: SiteManage\FlatPurchaseRuleController
    // =========================================================================
    'flat-purchase-rules' => ['controller' => 'SiteManage\FlatPurchaseRuleController', 'method' => 'index'],
    'store-flat-purchase-rule' => ['controller' => 'SiteManage\FlatPurchaseRuleController', 'method' => 'store'],
    'update-flat-purchase-rule' => ['controller' => 'SiteManage\FlatPurchaseRuleController', 'method' => 'update'],
    'delete-flat-purchase-rule' => ['controller' => 'SiteManage\FlatPurchaseRuleController', 'method' => 'delete'],

    // =========================================================================
    // Controller: ProjectCategoryController
    // =========================================================================
    'project-category-lists' => ['controller' => 'ProjectCategoryController', 'method' => 'index'],
    'create-project-category' => ['controller' => 'ProjectCategoryController', 'method' => 'create'],
    'store-project-category' => ['controller' => 'ProjectCategoryController', 'method' => 'store'],
    'edit-project-category/(\d+)' => ['controller' => 'ProjectCategoryController', 'method' => 'edit'],
    'update-project-category/(\d+)' => ['controller' => 'ProjectCategoryController', 'method' => 'update'],
    'delete-project-category' => ['controller' => 'ProjectCategoryController', 'method' => 'delete'],

    // =========================================================================
    // Controller: ProjectInventoryController
    // =========================================================================
    'inventory-lists' => ['controller' => 'ProjectInventoryController', 'method' => 'index'],

    // =========================================================================
    // Controller: TeamController
    // =========================================================================
    'teams' => ['controller' => 'TeamController', 'method' => 'index'],
    'store-team' => ['controller' => 'TeamController', 'method' => 'store'],
    'update-team' => ['controller' => 'TeamController', 'method' => 'update'],
    'delete-team' => ['controller' => 'TeamController', 'method' => 'delete'],
    'team-manager-history' => ['controller' => 'TeamController', 'method' => 'managerHistory'],
    'assign-manager-teams' => ['controller' => 'TeamController', 'method' => 'assignManagerTeams'],

    // =========================================================================
    // Controller: ProjectProgressController
    // =========================================================================
    'project-progress-lists' => ['controller' => 'ProjectProgressController', 'method' => 'index'],
    'create-project-progress' => ['controller' => 'ProjectProgressController', 'method' => 'create'],
    'store-project-progress' => ['controller' => 'ProjectProgressController', 'method' => 'store'],
    'edit-project-progress/(\d+)' => ['controller' => 'ProjectProgressController', 'method' => 'edit'],
    'update-project-progress/(\d+)' => ['controller' => 'ProjectProgressController', 'method' => 'update'],
    'delete-project-progress/(\d+)' => ['controller' => 'ProjectProgressController', 'method' => 'delete'],
    'delete-project-progress' => ['controller' => 'ProjectProgressController', 'method' => 'delete'],

    // =========================================================================
    // Controller: CameraController
    // =========================================================================
    'camera-lists' => ['controller' => 'CameraController', 'method' => 'index'],
    'create-camera' => ['controller' => 'CameraController', 'method' => 'create'],
    'store-camera' => ['controller' => 'CameraController', 'method' => 'store'],
    'edit-camera/(\d+)' => ['controller' => 'CameraController', 'method' => 'edit'],
    'update-camera/(\d+)' => ['controller' => 'CameraController', 'method' => 'update'],
    'delete-camera/(\d+)' => ['controller' => 'CameraController', 'method' => 'delete'],
    'delete-camera' => ['controller' => 'CameraController', 'method' => 'delete'],

    // =========================================================================
    // Controller: Ai\ChatController
    // =========================================================================
    'ai-chatbox' => ['controller' => 'Ai\ChatController', 'method' => 'handleMessage'],

    // =========================================================================
    // Controller: TutorialController
    // =========================================================================
    'tutorial-video' => ['controller' => 'TutorialController', 'method' => 'redirect'],
    'tutorial-video-sales' => ['controller' => 'TutorialController', 'method' => 'redirectSales'],

    // =========================================================================
    // Controller: ExpenseAdjustmentController
    // =========================================================================
    'expense-adjustments' => ['controller' => 'ExpenseAdjustmentController', 'method' => 'index'],
    'expense-adjustments/create' => ['controller' => 'ExpenseAdjustmentController', 'method' => 'create'],
    'expense-adjustments/store' => ['controller' => 'ExpenseAdjustmentController', 'method' => 'store'],
    'expense-adjustments/show' => ['controller' => 'ExpenseAdjustmentController', 'method' => 'show'],
    'expense-adjustments/qc-approve' => ['controller' => 'ExpenseAdjustmentController', 'method' => 'qcApprove'],
    'expense-adjustments/approve' => ['controller' => 'ExpenseAdjustmentController', 'method' => 'approve'],
    'expense-adjustments/reject' => ['controller' => 'ExpenseAdjustmentController', 'method' => 'reject'],
    'expense-adjustments/add-payment' => ['controller' => 'ExpenseAdjustmentController', 'method' => 'addPayment'],

    // =========================================================================
    // Controller: ApiController (REST endpoints for AI and Client)
    // =========================================================================
    'api/pending-approvals' => ['controller' => 'ApiController', 'method' => 'pendingApprovals'],
    'api/project-timeline-progress/(\d+)' => ['controller' => 'ApiController', 'method' => 'projectTimelineProgress'],
    'api/site-engineer-performance' => ['controller' => 'ApiController', 'method' => 'siteEngineerPerformance'],
    'api/project-stock-levels/(\d+)' => ['controller' => 'ApiController', 'method' => 'projectStockLevels'],
    'api/sales-executive-performance' => ['controller' => 'ApiController', 'method' => 'salesExecutivePerformance'],
    'api/flat-inventory-summary' => ['controller' => 'ApiController', 'method' => 'flatInventorySummary'],
    'api/flat-inventory-summary/(\d+)' => ['controller' => 'ApiController', 'method' => 'flatInventorySummary'],
    'api/financial-summary' => ['controller' => 'ApiController', 'method' => 'financialSummary'],
    'api/client-payment-info' => ['controller' => 'ApiController', 'method' => 'clientPaymentInfo'],
    'api/user-payment-info' => ['controller' => 'ApiController', 'method' => 'clientPaymentInfo'],
    'user-payment-info' => ['controller' => 'ApiController', 'method' => 'clientPaymentInfo'],
    'client-payment-info' => ['controller' => 'ApiController', 'method' => 'clientPaymentInfo'],
    'api/project-expenses' => ['controller' => 'ApiController', 'method' => 'projectExpenses'],
    'api/client-authenticate' => ['controller' => 'ApiController', 'method' => 'clientAuthenticate'],
    'api/client-details' => ['controller' => 'ApiController', 'method' => 'clientDetails'],

    // =========================================================================
    // Controller: ClientFeedbackController (Management Portal + Public API)
    // =========================================================================
    'client-feedback-report' => ['controller' => 'ClientFeedbackController', 'method' => 'index'],
    'client-feedback-report/list' => ['controller' => 'ClientFeedbackController', 'method' => 'listAjax'],
    'client-feedback-report/update-status' => ['controller' => 'ClientFeedbackController', 'method' => 'updateStatusAjax'],
    'client-feedback-report/export' => ['controller' => 'ClientFeedbackController', 'method' => 'exportCsv'],
    'api/client-feedback/submit' => ['controller' => 'ClientFeedbackController', 'method' => 'submitApi'],

    // =========================================================================
    // Controller: ErpTrackerController
    // =========================================================================
    'erp-tracker' => ['controller' => 'ErpTrackerController', 'method' => 'dashboard'],
    'erp-tracker/session/create' => ['controller' => 'ErpTrackerController', 'method' => 'createSession'],
    'erp-tracker/session/close/(\d+)' => ['controller' => 'ErpTrackerController', 'method' => 'closeSession'],
    'erp-tracker/session/delete/(\d+)' => ['controller' => 'ErpTrackerController', 'method' => 'deleteSession'],
    'erp-tracker/form' => ['controller' => 'ErpTrackerController', 'method' => 'form'],
    'erp-tracker/form/submit' => ['controller' => 'ErpTrackerController', 'method' => 'submitForm'],
    'erp-tracker/view/(\d+)' => ['controller' => 'ErpTrackerController', 'method' => 'viewSubmission'],
    'erp-tracker/champions' => ['controller' => 'ErpTrackerController', 'method' => 'champions'],
    'erp-tracker/champions/add' => ['controller' => 'ErpTrackerController', 'method' => 'addChampion'],
    'erp-tracker/champions/remove/(\d+)' => ['controller' => 'ErpTrackerController', 'method' => 'removeChampion'],
    'erp-tracker/api/get-dept-users/(\d+)' => ['controller' => 'ErpTrackerController', 'method' => 'getDepartmentUsers'],

    // =========================================================================
    // Controller: IncentiveController
    // =========================================================================
    'my-incentives' => ['controller' => 'IncentiveController', 'method' => 'myIncentives'],
    'my-team-incentives' => ['controller' => 'IncentiveController', 'method' => 'teamIncentives'],
    'incentive-management' => ['controller' => 'IncentiveController', 'method' => 'manageIncentives'],
    'incentives/claim' => ['controller' => 'IncentiveController', 'method' => 'claimIncentive'],
    'incentives/assign-external' => ['controller' => 'IncentiveController', 'method' => 'assignExternalIncentive'],
    'incentives/approve' => ['controller' => 'IncentiveController', 'method' => 'approveClaim'],
    'incentives/reject' => ['controller' => 'IncentiveController', 'method' => 'rejectClaim'],
    'incentives/disburse' => ['controller' => 'IncentiveController', 'method' => 'disbursePayment'],
    'incentive-config' => ['controller' => 'IncentiveController', 'method' => 'configSettings'],
    'incentives/save-settings' => ['controller' => 'IncentiveController', 'method' => 'saveSettings'],
    'incentives/save-slab' => ['controller' => 'IncentiveController', 'method' => 'saveSlab'],
    'incentives/save-hierarchy' => ['controller' => 'IncentiveController', 'method' => 'saveHierarchy'],
    'incentives/claim-tl-override' => ['controller' => 'IncentiveController', 'method' => 'claimTlOverride'],
    'incentives/claim-all-tl-overrides' => ['controller' => 'IncentiveController', 'method' => 'claimAllTlOverrides'],
    'incentives/claim-designated' => ['controller' => 'IncentiveController', 'method' => 'claimDesignatedIncentive'],
    'incentives/confirm-receipt' => ['controller' => 'IncentiveController', 'method' => 'confirmReceipt'],

    // =========================================================================
    // Controller: ApprovalRequestController (All Approval Requests Dashboard)
    // =========================================================================
    'all-approval-requests' => ['controller' => 'ApprovalRequestController', 'method' => 'allApprovalRequests'],
    'branch-disbursements' => ['controller' => 'ApprovalRequestController', 'method' => 'branchDisbursements'],
    'my-approval-history' => ['controller' => 'MyApprovalHistoryController', 'method' => 'index'],
    'todo' => ['controller' => 'TodoListController', 'method' => 'index'],
    'todo/store' => ['controller' => 'TodoListController', 'method' => 'store'],
    'todo/complete' => ['controller' => 'TodoListController', 'method' => 'complete'],
    'todo/cron' => ['controller' => 'TodoListController', 'method' => 'cron'],
    'todo/delete' => ['controller' => 'TodoListController', 'method' => 'delete'],
    'todo/update' => ['controller' => 'TodoListController', 'method' => 'update'],
    'todo/test-email' => ['controller' => 'TodoListController', 'method' => 'testEmail'],
    'batch-process-approval-requests' => ['controller' => 'ApprovalRequestController', 'method' => 'batchProcessApprovalRequests'],
    'approval-requests/approve-sales-head' => ['controller' => 'ApprovalRequestController', 'method' => 'approveSalesHead'],
    'approval-requests/reject-sales-head' => ['controller' => 'ApprovalRequestController', 'method' => 'rejectSalesHead'],
    'approval-requests/approve-accounts-head' => ['controller' => 'ApprovalRequestController', 'method' => 'approveAccountsHead'],
    'approval-requests/reject-accounts-head' => ['controller' => 'ApprovalRequestController', 'method' => 'rejectAccountsHead'],
    'approval-requests/approve-operation-head' => ['controller' => 'ApprovalRequestController', 'method' => 'approveOperationHead'],
    'approval-requests/reject-operation-head' => ['controller' => 'ApprovalRequestController', 'method' => 'rejectOperationHead'],
    'approval-requests/approve-management' => ['controller' => 'ApprovalRequestController', 'method' => 'approveManagement'],
    'approval-requests/reject-management' => ['controller' => 'ApprovalRequestController', 'method' => 'rejectManagement'],
    'api/actionable-approval-alerts' => ['controller' => 'ApprovalRequestController', 'method' => 'getActionableAlerts'],

    // =========================================================================
    // Vendor & Contractor Enrollment Approval Routes
    // =========================================================================
    'vendor-enrollment-requests' => ['controller' => 'EnrollmentApprovalController', 'method' => 'vendorRequests'],
    'contractor-enrollment-requests' => ['controller' => 'EnrollmentApprovalController', 'method' => 'contractorRequests'],
    'enrollment-request/action' => ['controller' => 'EnrollmentApprovalController', 'method' => 'processAction'],
    'api/enrollment-request/details' => ['controller' => 'EnrollmentApprovalController', 'method' => 'getDetails'],

    // =========================================================================
    // Live COA & Journal Migration Routes
    // =========================================================================
    'run-live-coa-migration' => ['controller' => 'LiveCoaMigrationController', 'method' => 'run'],
    'live_coa_and_journal_migration.php' => ['controller' => 'LiveCoaMigrationController', 'method' => 'run'],
    'update-fr-journals' => ['controller' => 'LiveCoaMigrationController', 'method' => 'updateFrJournals'],

    // =========================================================================
    // Live Restore Bookings, Payments & EMI Routes
    // =========================================================================
    'run-restore-bookings-payments' => ['controller' => 'LiveRestoreBookingsController', 'method' => 'run'],
    'restore-bookings-data' => ['controller' => 'LiveRestoreBookingsController', 'method' => 'run'],
    // =========================================================================
    // Controller: SalesActivityReportController
    // =========================================================================
    'sales-activity-report' => ['controller' => 'SalesActivityReportController', 'method' => 'index'],
    'sales-activity-team-performance' => ['controller' => 'SalesActivityReportController', 'method' => 'teamPerformance'],
    'team-performance-export' => ['controller' => 'SalesActivityReportController', 'method' => 'exportTeamPerformanceExcel'],
    'team-performance/export' => ['controller' => 'SalesActivityReportController', 'method' => 'exportTeamPerformanceExcel'],

    // =========================================================================
    // Controller: ExpenseAnalyticsController (Company Expense Analytics Dashboard)
    // =========================================================================
    'expense-analytics' => ['controller' => 'ExpenseAnalyticsController', 'method' => 'index'],
    'expense-analytics/ajax-ledger' => ['controller' => 'ExpenseAnalyticsController', 'method' => 'getLedgerAjax'],
    'expense-analytics/export' => ['controller' => 'ExpenseAnalyticsController', 'method' => 'exportExcel'],

    // =========================================================================
    // Controller: NotificationController (Dedicated Notifications Page)
    // =========================================================================
    'notifications' => ['controller' => 'NotificationController', 'method' => 'index'],
    'notifications/unread-count' => ['controller' => 'NotificationController', 'method' => 'unreadCount'],
    'notifications/mark-read' => ['controller' => 'NotificationController', 'method' => 'markRead'],
    'notifications/mark-all-read' => ['controller' => 'NotificationController', 'method' => 'markAllRead'],

    // Central Requisition Revision (Send Back for Review)
    'requisition-revision/send-back' => ['controller' => 'RequisitionRevisionController', 'method' => 'sendBack'],
    'requisition-revision/resubmit-rejected' => ['controller' => 'RequisitionRevisionController', 'method' => 'resubmitRejected'],
];



