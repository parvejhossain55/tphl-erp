<?php
return [

    // =========================================================================
    // Controller: LoginController
    // =========================================================================
    '' => ['controller' => 'LoginController', 'method' => 'index'],
    'admin-login' => ['controller' => 'LoginController', 'method' => 'index'],
    'logout' => ['controller' => 'LoginController', 'method' => 'logout'],
    'update-activity' => ['controller' => 'LoginController', 'method' => 'updateActivity'],

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

    // =========================================================================
    // Controller: CameraController
    // =========================================================================
    'camera-lists' => ['controller' => 'CameraController', 'method' => 'index'],
    'create-camera' => ['controller' => 'CameraController', 'method' => 'create'],
    'store-camera' => ['controller' => 'CameraController', 'method' => 'store'],

    // =========================================================================
    // Controller: Ai\ChatController
    // =========================================================================
    'ai-chatbox' => ['controller' => 'Ai\ChatController', 'method' => 'handleMessage'],

    // =========================================================================
    // Controller: TutorialController
    // =========================================================================
    'tutorial-video' => ['controller' => 'TutorialController', 'method' => 'redirect'],

    // =========================================================================
    // Controller: ExpenseAdjustmentController
    // =========================================================================
    'expense-adjustments' => ['controller' => 'ExpenseAdjustmentController', 'method' => 'index'],
    'expense-adjustments/create' => ['controller' => 'ExpenseAdjustmentController', 'method' => 'create'],
    'expense-adjustments/store' => ['controller' => 'ExpenseAdjustmentController', 'method' => 'store'],
    'expense-adjustments/show' => ['controller' => 'ExpenseAdjustmentController', 'method' => 'show'],
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
    'incentive-management' => ['controller' => 'IncentiveController', 'method' => 'manageIncentives'],
    'incentives/claim' => ['controller' => 'IncentiveController', 'method' => 'claimIncentive'],
    'incentives/assign-external' => ['controller' => 'IncentiveController', 'method' => 'assignExternalIncentive'],
    'incentives/approve' => ['controller' => 'IncentiveController', 'method' => 'approveClaim'],
    'incentives/reject' => ['controller' => 'IncentiveController', 'method' => 'rejectClaim'],
    'incentives/disburse' => ['controller' => 'IncentiveController', 'method' => 'disbursePayment'],

    // =========================================================================
    // Controller: ApprovalRequestController (All Approval Requests Dashboard)
    // =========================================================================
    'all-approval-requests' => ['controller' => 'ApprovalRequestController', 'method' => 'allApprovalRequests'],
    'batch-process-approval-requests' => ['controller' => 'ApprovalRequestController', 'method' => 'batchProcessApprovalRequests'],

    // =========================================================================
    // Live COA & Journal Migration Routes
    // =========================================================================
    'run-live-coa-migration' => ['controller' => 'LiveCoaMigrationController', 'method' => 'run'],
    'live_coa_and_journal_migration.php' => ['controller' => 'LiveCoaMigrationController', 'method' => 'run'],
    'update-fr-journals' => ['controller' => 'LiveCoaMigrationController', 'method' => 'updateFrJournals'],
];

