<?php
return [

    // =========================================================================
    // Controller: UserDashboardController
    // =========================================================================
    'my-dashboard' => ['controller' => 'UserDashboardController', 'method' => 'dashboard'],
    'today-leads' => ['controller' => 'UserDashboardController', 'method' => 'todayLead'],
    'update-call-status' => ['controller' => 'UserDashboardController', 'method' => 'updateCallStatus'],
    'update-communication-type' => ['controller' => 'UserDashboardController', 'method' => 'updateCommunicationStatus'],
    'update-lead-status' => ['controller' => 'UserDashboardController', 'method' => 'updateLeadStatus'],
    'update-call-notes' => ['controller' => 'UserDashboardController', 'method' => 'updateCallNote'],
    'today-follow-up' => ['controller' => 'UserDashboardController', 'method' => 'todayFollowUp'],
    'upcoming-follow-up' => ['controller' => 'UserDashboardController', 'method' => 'myUpcomingFollowLeads'],
    'my-onboarding-projects' => ['controller' => 'UserDashboardController', 'method' => 'myOnboardProject'],
    'my-all-assigned-leads' => ['controller' => 'UserDashboardController', 'method' => 'myAssignedLeads'],
    'follow-up/update-status-note' => ['controller' => 'UserDashboardController', 'method' => 'updateFollowUpStatusNote'],
    'overdue-next-followup' => ['controller' => 'UserDashboardController', 'method' => 'updateFollowUpStatusNote'],

    // =========================================================================
    // Controller: SiteManage\BankAccountController
    // =========================================================================
    'company-bank-accounts' => ['controller' => 'SiteManage\BankAccountController', 'method' => 'index'],
    'store-company-bank-accounts' => ['controller' => 'SiteManage\BankAccountController', 'method' => 'store'],
    'update-company-bank-accounts' => ['controller' => 'SiteManage\BankAccountController', 'method' => 'update'],
    'delete-company-bank-accounts' => ['controller' => 'SiteManage\BankAccountController', 'method' => 'delete'],
    'get-company-banks' => ['controller' => 'SiteManage\BankAccountController', 'method' => 'getCompanyBanksAjax'],
    'store-company-bank-ajax' => ['controller' => 'SiteManage\BankAccountController', 'method' => 'storeCompanyBankAjax'],
    'update-company-bank-ajax' => ['controller' => 'SiteManage\BankAccountController', 'method' => 'updateCompanyBankAjax'],

    // =========================================================================
    // Controller: DashboardController
    // =========================================================================
    'dashboard' => ['controller' => 'DashboardController', 'method' => 'index'],
    'accounts-dashboard' => ['controller' => 'DashboardController', 'method' => 'index'],
    'dashboard/get-statistics' => ['controller' => 'DashboardController', 'method' => 'getStatistics'],

    // =========================================================================
    // Controller: CollectionStatementController
    // =========================================================================
    'collection-statement' => ['controller' => 'CollectionStatementController', 'method' => 'index'],
    'collection-statement-print' => ['controller' => 'CollectionStatementController', 'method' => 'printStatement'],
    'collection-statement-export' => ['controller' => 'CollectionStatementController', 'method' => 'exportCSV'],

    // =========================================================================
    // Controller: AccountClientController
    // =========================================================================
    'clients-cash-in' => ['controller' => 'AccountClientController', 'method' => 'index'],
    'clients-cash-in-print' => ['controller' => 'AccountClientController', 'method' => 'printClientsStatement'],
    'client-wise-due-tracking' => ['controller' => 'AccountClientController', 'method' => 'dueTracking'],
    'client-due-tracking' => ['controller' => 'AccountClientController', 'method' => 'dueTracking'],
    'client-wise-due-tracking-export' => ['controller' => 'AccountClientController', 'method' => 'exportDueTracking'],
    'today-follow-ups' => ['controller' => 'AccountClientController', 'method' => 'todayFollowUps'],
    'today-followups' => ['controller' => 'AccountClientController', 'method' => 'todayFollowUps'],
    'upcoming-follow-ups' => ['controller' => 'AccountClientController', 'method' => 'upcomingFollowUps'],
    'upcoming-followups' => ['controller' => 'AccountClientController', 'method' => 'upcomingFollowUps'],
    'export-followups' => ['controller' => 'AccountClientController', 'method' => 'exportFollowUps'],
    'get-client-lands' => ['controller' => 'AccountClientController', 'method' => 'getClientLands'],
    'get-client-profile' => ['controller' => 'AccountClientController', 'method' => 'getClientProfile'],
    'clients-cash-in-history' => ['controller' => 'AccountClientController', 'method' => 'indexTwo'],
    'clients-cash-in-history/(\d+)' => ['controller' => 'AccountClientController', 'method' => 'indexTwo'],
    'client-emi-tracking' => ['controller' => 'AccountClientController', 'method' => 'clientEmiTracking'],
    'client-emi-tracking/(\d+)' => ['controller' => 'AccountClientController', 'method' => 'clientEmiTracking'],
    'client-emi-schedule-print' => ['controller' => 'AccountClientController', 'method' => 'printEmiSchedule'],
    'client-emi-schedule-print/(\d+)' => ['controller' => 'AccountClientController', 'method' => 'printEmiSchedule'],
    'update-booking-info' => ['controller' => 'AccountClientController', 'method' => 'updateBookingInfo'],
    'update-shop-service-charge' => ['controller' => 'AccountClientController', 'method' => 'updateShopServiceCharge'],
    'add-transaction' => ['controller' => 'AccountClientController', 'method' => 'addTransaction'],
    'edit-client-cash-in/(\d+)' => ['controller' => 'AccountClientController', 'method' => 'showEditTransactionForm'],
    'edit-client-cash-in' => ['controller' => 'AccountClientController', 'method' => 'showEditTransactionForm'],
    'client-cash-in-form' => ['controller' => 'AccountClientController', 'method' => 'showAddTransactionForm'],
    'update-transaction' => ['controller' => 'AccountClientController', 'method' => 'updateTransaction'],
    'delete-transaction' => ['controller' => 'AccountClientController', 'method' => 'deleteTransaction'],
    'delete-booking' => ['controller' => 'AccountClientController', 'method' => 'deleteBooking'],
    'cancel-booking' => ['controller' => 'AccountClientController', 'method' => 'cancelBooking'],
    'csv-import-form' => ['controller' => 'AccountClientController', 'method' => 'showCSVImportForm'],
    'csv-import-preview' => ['controller' => 'AccountClientController', 'method' => 'previewCSVImport'],
    'csv-import-execute' => ['controller' => 'AccountClientController', 'method' => 'executeCSVImport'],
    'csv-import-template' => ['controller' => 'AccountClientController', 'method' => 'downloadCSVTemplate'],
    'ced-dashboard' => ['controller' => 'AccountClientController', 'method' => 'CedView'],
    'ced-report' => ['controller' => 'CedReportController', 'method' => 'index'],
    'ced-central-report' => ['controller' => 'CedReportController', 'method' => 'centralReport'],
    'ced-project-notices' => ['controller' => 'AccountClientController', 'method' => 'cedProjectNotices'],
    'ced-collection-statement' => ['controller' => 'CedCollectionStatementController', 'method' => 'index'],
    'ced-collection-statement-print' => ['controller' => 'CedCollectionStatementController', 'method' => 'printStatement'],
    'ced-collection-statement-export' => ['controller' => 'CedCollectionStatementController', 'method' => 'exportCSV'],
    'manage-projects-for-ced' => ['controller' => 'CedProjectManageController', 'method' => 'manageProjects'],
    'ced-project-assignments/save' => ['controller' => 'CedProjectManageController', 'method' => 'saveAssignmentsAjax'],
    'ced-project-assignments/agent-projects/(\d+)' => ['controller' => 'CedProjectManageController', 'method' => 'getAgentProjectsAjax'],
    'my-projects-history' => ['controller' => 'CedProjectManageController', 'method' => 'myProjectsHistory'],
    'ced-projects-history' => ['controller' => 'CedProjectManageController', 'method' => 'myProjectsHistory'],

    'payment-invoice' => ['controller' => 'AccountClientController', 'method' => 'viewPaymentInvoice'],
    'payment-invoice/(\d+)' => ['controller' => 'AccountClientController', 'method' => 'viewPaymentInvoice'],
    'public-receipt' => ['controller' => 'AccountClientController', 'method' => 'viewPaymentInvoice'],
    'public-receipt/([0-9,]+)' => ['controller' => 'AccountClientController', 'method' => 'viewPaymentInvoice'],
    'download-receipt' => ['controller' => 'AccountClientController', 'method' => 'downloadPublicReceiptPdf'],
    'download-receipt/([0-9,]+)' => ['controller' => 'AccountClientController', 'method' => 'downloadPublicReceiptPdf'],
    'verify-receipt' => ['controller' => 'AccountClientController', 'method' => 'verifyPublicReceipt'],
    'verify-receipt/([0-9,]+)' => ['controller' => 'AccountClientController', 'method' => 'verifyPublicReceipt'],
    'api/verify-receipt' => ['controller' => 'ApiController', 'method' => 'verifyReceipt'],
    'api/verify-receipt/([0-9,]+)' => ['controller' => 'ApiController', 'method' => 'verifyReceipt'],
    'booking-invoice' => ['controller' => 'AccountClientController', 'method' => 'viewBookingInvoice'],
    'booking-invoice/(\d+)' => ['controller' => 'AccountClientController', 'method' => 'viewBookingInvoice'],
    'client-statement' => ['controller' => 'AccountClientController', 'method' => 'viewClientStatement'],
    'client-statement/(\d+)' => ['controller' => 'AccountClientController', 'method' => 'viewClientStatement'],
    'client-datewise-statement' => ['controller' => 'AccountClientController', 'method' => 'viewClientDatewiseStatement'],
    'client-datewise-statement/(\d+)' => ['controller' => 'AccountClientController', 'method' => 'viewClientDatewiseStatement'],
    'discount-statement' => ['controller' => 'AccountClientController', 'method' => 'discountStatement'],
    'discount-statement/(\d+)' => ['controller' => 'AccountClientController', 'method' => 'discountStatement'],
    'clients-cashin-view' => ['controller' => 'AccountClientController', 'method' => 'indexView'],
    'clients-cash-in-history-view' => ['controller' => 'AccountClientController', 'method' => 'indexTwoView'],
    'clients-cash-in-history-view/(\d+)' => ['controller' => 'AccountClientController', 'method' => 'indexTwoView'],
    'update-management-booking-discount' => ['controller' => 'AccountClientController', 'method' => 'updateManagementDiscount'],
    'update-payment-date' => ['controller' => 'AccountClientController', 'method' => 'updatePaymentDate'],
    'save-transfer-note' => ['controller' => 'AccountClientController', 'method' => 'saveTransferNote'],
    'verify-payment' => ['controller' => 'AccountClientController', 'method' => 'verifyPayment'],
    'account-client/bulkVerify' => ['controller' => 'AccountClientController', 'method' => 'bulkVerify'],
    'account-client/bulkDelete' => ['controller' => 'AccountClientController', 'method' => 'bulkDelete'],
    'bulk-delete-transactions' => ['controller' => 'AccountClientController', 'method' => 'bulkDelete'],
    'assign-handover' => ['controller' => 'AccountClientController', 'method' => 'assignHandover'],
    'get-banks' => ['controller' => 'AccountClientController', 'method' => 'getBanks'],
    'upload-transaction-proof' => ['controller' => 'AccountClientController', 'method' => 'uploadTransactionProof'],
    'get-transaction-attachments' => ['controller' => 'AccountClientController', 'method' => 'getTransactionAttachments'],
    'view-transaction-file' => ['controller' => 'AccountClientController', 'method' => 'serveTransactionFile'],
    'ced-payment-tracking' => ['controller' => 'AccountClientController', 'method' => 'cedPaymentTracking'],
    'ced-add-transaction-form' => ['controller' => 'AccountClientController', 'method' => 'showCedTransactionForm'],
    'ced-add-transaction-form/(\d+)' => ['controller' => 'AccountClientController', 'method' => 'showCedTransactionForm'],
    'ced-add-transaction' => ['controller' => 'AccountClientController', 'method' => 'addCedTransaction'],
    
    // EMI Followups & CED Fixed Due Dates
    'account-client/getEmiFollowups' => ['controller' => 'AccountClientController', 'method' => 'getEmiFollowups'],
    'account-client/saveEmiFollowup' => ['controller' => 'AccountClientController', 'method' => 'saveEmiFollowup'],
    'account-client/saveEmiDueDate'  => ['controller' => 'AccountClientController', 'method' => 'saveEmiDueDate'],
    
    // Financial Dashboard
    'financial-dashboard' => ['controller' => 'AccountFinancialController', 'method' => 'index'],
    'financial-dashboard/get-vouchers' => ['controller' => 'AccountFinancialController', 'method' => 'getExpenseVouchers'],
    'financial-dashboard/get-adjustments' => ['controller' => 'AccountFinancialController', 'method' => 'getAdjustments'],
    'financial-dashboard/get-receipts' => ['controller' => 'AccountFinancialController', 'method' => 'getReceipts'],
    'financial-dashboard/get-clients' => ['controller' => 'AccountFinancialController', 'method' => 'getClients'],
    'financial-dashboard/get-refunds' => ['controller' => 'AccountFinancialController', 'method' => 'getRefunds'],
    'financial-dashboard/get-pending-payables' => ['controller' => 'AccountFinancialController', 'method' => 'getPendingPayables'],

    // General Journal
    'general-journal' => ['controller' => 'GeneralJournalController', 'method' => 'index'],
    'general-journal/create' => ['controller' => 'GeneralJournalController', 'method' => 'create'],
    'general-journal/store' => ['controller' => 'GeneralJournalController', 'method' => 'store'],
    'general-journal/view/(\d+)' => ['controller' => 'GeneralJournalController', 'method' => 'show'],
    'general-journal/edit/(\d+)' => ['controller' => 'GeneralJournalController', 'method' => 'edit'],
    'general-journal/update/(\d+)' => ['controller' => 'GeneralJournalController', 'method' => 'update'],
    'general-journal/approve/(\d+)' => ['controller' => 'GeneralJournalController', 'method' => 'approve'],
    'financial-dashboard/get-general-journals' => ['controller' => 'GeneralJournalController', 'method' => 'getJournals'],

    // Chart of Accounts Management
    'accounts/coa' => ['controller' => 'ChartOfAccountsController', 'method' => 'index'],
    'accounts/coa/store' => ['controller' => 'ChartOfAccountsController', 'method' => 'store'],
    'accounts/coa/store-ajax' => ['controller' => 'ChartOfAccountsController', 'method' => 'storeAjax'],
    'accounts/coa/update' => ['controller' => 'ChartOfAccountsController', 'method' => 'update'],
    'accounts/coa/delete/(\d+)' => ['controller' => 'ChartOfAccountsController', 'method' => 'delete'],
    'accounts/coa/delete' => ['controller' => 'ChartOfAccountsController', 'method' => 'delete'],
    'accounts/ledger/(\d+)' => ['controller' => 'ChartOfAccountsController', 'method' => 'ledger'],
    'accounts/ledger' => ['controller' => 'ChartOfAccountsController', 'method' => 'ledger'],

    // Financial Reports
    'reports/balance-sheet' => ['controller' => 'ReportsController', 'method' => 'balanceSheet'],
    'reports/profit-loss' => ['controller' => 'ReportsController', 'method' => 'profitLoss'],
    'reports/cashflow' => ['controller' => 'ReportsController', 'method' => 'cashflow'],
    'reports/trial-balance' => ['controller' => 'ReportsController', 'method' => 'trialBalance'],
    'reports/ap-ageing' => ['controller' => 'ReportsController', 'method' => 'apAgeing'],
    'reports/cash-commitment-forecast' => ['controller' => 'ReportsController', 'method' => 'cashCommitmentForecast'],

    // =========================================================================
    // Controller: AccountShopClientController
    // =========================================================================
    'add-shop-transaction' => ['controller' => 'AccountShopClientController', 'method' => 'addShopTransaction'],
    'shop-client-cash-in-form' => ['controller' => 'AccountShopClientController', 'method' => 'showShopTransactionForm'],

    // =========================================================================
    // Controller: AccountManagementController
    // =========================================================================
    'management-cash-in' => ['controller' => 'AccountManagementController', 'method' => 'index'],
    'add-management-cash-in' => ['controller' => 'AccountManagementController', 'method' => 'showAddForm'],
    'store-management-cash-in' => ['controller' => 'AccountManagementController', 'method' => 'storeTransaction'],
    'edit-management-cash-in' => ['controller' => 'AccountManagementController', 'method' => 'showEditForm'],
    'update-management-cash-in' => ['controller' => 'AccountManagementController', 'method' => 'updateTransaction'],
    'delete-management-cash-in' => ['controller' => 'AccountManagementController', 'method' => 'deleteTransaction'],
    'get-management-transaction-details' => ['controller' => 'AccountManagementController', 'method' => 'getTransactionDetails'],
    'management-roles' => ['controller' => 'AccountManagementController', 'method' => 'manageRoles'],
    'get-role-details' => ['controller' => 'AccountManagementController', 'method' => 'getRoleDetails'],
    'add-role' => ['controller' => 'AccountManagementController', 'method' => 'addRole'],
    'update-roles' => ['controller' => 'AccountManagementController', 'method' => 'updateRole'],
    'delete-role' => ['controller' => 'AccountManagementController', 'method' => 'deleteRole'],
    'toggle-role-status' => ['controller' => 'AccountManagementController', 'method' => 'toggleRoleStatus'],
    'management-cash-in-statement' => ['controller' => 'AccountManagementController', 'method' => 'viewManagementStatement'],
    'management-cash-in-invoice' => ['controller' => 'AccountManagementController', 'method' => 'viewTransactionInvoice'],
    'add-management-role' => ['controller' => 'AccountManagementController', 'method' => 'addRole'],
    'update-management-role' => ['controller' => 'AccountManagementController', 'method' => 'updateRole'],
    'delete-management-role' => ['controller' => 'AccountManagementController', 'method' => 'deleteRole'],

    // =========================================================================
    // Controller: AccountManagementCashoutController
    // =========================================================================
    'management-cash-out-statement' => ['controller' => 'AccountManagementCashoutController', 'method' => 'showStatement'],
    'management-cash-out-invoice' => ['controller' => 'AccountManagementCashoutController', 'method' => 'showInvoice'],
    'management-cash-out' => ['controller' => 'AccountManagementCashoutController', 'method' => 'index'],
    'add-management-cash-out' => ['controller' => 'AccountManagementCashoutController', 'method' => 'showAddForm'],
    'store-management-cash-out' => ['controller' => 'AccountManagementCashoutController', 'method' => 'storeTransaction'],
    'edit-management-cash-out' => ['controller' => 'AccountManagementCashoutController', 'method' => 'showEditForm'],
    'update-management-cash-out' => ['controller' => 'AccountManagementCashoutController', 'method' => 'updateTransaction'],
    'delete-management-cash-out' => ['controller' => 'AccountManagementCashoutController', 'method' => 'deleteTransaction'],
    'get-cashout-transaction-details' => ['controller' => 'AccountManagementCashoutController', 'method' => 'getTransactionDetails'],
    'export-management-cash-out' => ['controller' => 'AccountManagementCashoutController', 'method' => 'exportTransactions'],

    // =========================================================================
    // Controller: ProjectCashoutController
    // =========================================================================
    'projects-cashout' => ['controller' => 'ProjectCashoutController', 'method' => 'index'],

    // =========================================================================
    // Controller: AccountManagementCategoryController
    // =========================================================================
    'management-categories' => ['controller' => 'AccountManagementCategoryController', 'method' => 'index'],
    'get-category-details' => ['controller' => 'AccountManagementCategoryController', 'method' => 'getCategoryDetails'],
    'add-management-category' => ['controller' => 'AccountManagementCategoryController', 'method' => 'addCategory'],
    'update-management-category' => ['controller' => 'AccountManagementCategoryController', 'method' => 'updateCategory'],
    'delete-management-category' => ['controller' => 'AccountManagementCategoryController', 'method' => 'deleteCategory'],
    'toggle-category-status' => ['controller' => 'AccountManagementCategoryController', 'method' => 'toggleCategoryStatus'],

    // =========================================================================
    // Controller: CentralStatementController
    // =========================================================================
    'central-statement' => ['controller' => 'CentralStatementController', 'method' => 'index'],
    'central-statement-store' => ['controller' => 'CentralStatementController', 'method' => 'store'],
    'central-statement-update' => ['controller' => 'CentralStatementController', 'method' => 'update'],
    'central-statement-delete' => ['controller' => 'CentralStatementController', 'method' => 'delete'],
    'central-statement-print' => ['controller' => 'CentralStatementController', 'method' => 'print'],
    'central-statement-export' => ['controller' => 'CentralStatementController', 'method' => 'exportCSV'],
    'central-statement-card-totals' => ['controller' => 'CentralStatementController', 'method' => 'getCardTotals'],
    'central-statement-stats' => ['controller' => 'CentralStatementController', 'method' => 'getQuickStats'],
    'central-statement-get' => ['controller' => 'CentralStatementController', 'method' => 'getStatement'],

    // =========================================================================
    // Controller: CashoutRequestController
    // =========================================================================
    'cashout-requests' => ['controller' => 'CashoutRequestController', 'method' => 'index'],
    'create-cashout-request' => ['controller' => 'CashoutRequestController', 'method' => 'create'],
    'store-cashout-request' => ['controller' => 'CashoutRequestController', 'method' => 'store'],
    'view-cashout-request' => ['controller' => 'CashoutRequestController', 'method' => 'show'],
    'delete-cashout-request' => ['controller' => 'CashoutRequestController', 'method' => 'delete'],
    'admin-cashout-requests' => ['controller' => 'CashoutRequestController', 'method' => 'adminIndex'],
    'admin-view-cashout-request' => ['controller' => 'CashoutRequestController', 'method' => 'adminShow'],
    'approve-cashout-request' => ['controller' => 'CashoutRequestController', 'method' => 'approve'],
    'reject-cashout-request' => ['controller' => 'CashoutRequestController', 'method' => 'reject'],
    'add-cashout-payment' => ['controller' => 'CashoutRequestController', 'method' => 'addPayment'],
    'delete-cashout-payment' => ['controller' => 'CashoutRequestController', 'method' => 'deletePayment'],
    'download-cashout-attachment' => ['controller' => 'CashoutRequestController', 'method' => 'downloadAttachment'],
    'export-cashout-requests' => ['controller' => 'CashoutRequestController', 'method' => 'export'],
    'sync-cashout-approvals' => ['controller' => 'CashoutRequestController', 'method' => 'syncAllApprovals'],
    'cashout-csv-import-form' => ['controller' => 'CashoutRequestController', 'method' => 'showCSVImportForm'],
    'cashout-csv-import-preview' => ['controller' => 'CashoutRequestController', 'method' => 'previewCSVImport'],
    'cashout-csv-import-execute' => ['controller' => 'CashoutRequestController', 'method' => 'executeCSVImport'],
    'cashout-csv-import-template' => ['controller' => 'CashoutRequestController', 'method' => 'downloadCSVTemplate'],

    // =========================================================================
    // Controller: ClientRefundController
    // =========================================================================
    'client-refunds' => ['controller' => 'ClientRefundController', 'method' => 'index'],
    'client-refund-create/(\d+)' => ['controller' => 'ClientRefundController', 'method' => 'create'],
    'client-refund-store' => ['controller' => 'ClientRefundController', 'method' => 'store'],
    'client-refund/(\d+)' => ['controller' => 'ClientRefundController', 'method' => 'show'],
    'client-refund-approve/(\d+)' => ['controller' => 'ClientRefundController', 'method' => 'approve'],
    'client-refund-reject/(\d+)' => ['controller' => 'ClientRefundController', 'method' => 'reject'],
    'client-refund-disburse/(\d+)' => ['controller' => 'ClientRefundController', 'method' => 'disbursePayment'],
    'client-refund-adjust/(\d+)' => ['controller' => 'ClientRefundController', 'method' => 'adjustRetention'],
    'client-refund-delete/(\d+)' => ['controller' => 'ClientRefundController', 'method' => 'delete'],

    // =========================================================================
    // Controller: BankRegistryController
    // =========================================================================
    'bank-registry' => ['controller' => 'BankRegistryController', 'method' => 'index'],
    'store-bank' => ['controller' => 'BankRegistryController', 'method' => 'store'],
    'update-bank' => ['controller' => 'BankRegistryController', 'method' => 'update'],
    'delete-bank' => ['controller' => 'BankRegistryController', 'method' => 'delete'],
    'toggle-bank-status' => ['controller' => 'BankRegistryController', 'method' => 'toggleStatus'],

    // =========================================================================
    // Controller: Phase2\Con_DashboardController
    // =========================================================================
    'con-dashboard' => ['controller' => 'Phase2\Con_DashboardController', 'method' => 'index'],
    'api/con-dashboard/projects-progress' => ['controller' => 'Phase2\Con_DashboardController', 'method' => 'ajaxProjectProgress'],
    'con-projects' => ['controller' => 'Phase2\Con_DashboardController', 'method' => 'projectsList'],
    'con-project/(\d+)' => ['controller' => 'Phase2\Con_DashboardController', 'method' => 'projectDetail'],
    'con-ajax-workplan/(\d+)' => ['controller' => 'Phase2\Con_DashboardController', 'method' => 'ajaxWorkPlan'],
    'con-ajax-targets/(\d+)' => ['controller' => 'Phase2\Con_DashboardController', 'method' => 'ajaxTargets'],
    'con-ajax-weekly/(\d+)' => ['controller' => 'Phase2\Con_DashboardController', 'method' => 'ajaxWeeklyData'],
    'con-ajax-workorders/(\d+)' => ['controller' => 'Phase2\Con_DashboardController', 'method' => 'ajaxWorkOrders'],
    'operations-dashboard' => ['controller' => 'Phase2\Con_DashboardController', 'method' => 'operationsDashboard'],
    'con-site-engineer-dashboard' => ['controller' => 'Phase2\Con_DashboardController', 'method' => 'siteEngineerDashboard'],
    'purchase-team-lead-dashboard' => ['controller' => 'Phase2\Con_DashboardController', 'method' => 'purchaseTeamLeadDashboard'],
    'sr-executive-dashboard' => ['controller' => 'Phase2\Con_DashboardController', 'method' => 'srExecutiveDashboard'],
    'draft-dashboard' => ['controller' => 'Phase2\Con_DashboardController', 'method' => 'draftDashboard'],
    'land-dashboard' => ['controller' => 'Phase2\Con_DashboardController', 'method' => 'landDashboard'],
    'project-architecture-dashboard' => ['controller' => 'Phase2\Con_DashboardController', 'method' => 'projectArchitectureDashboard'],
    'project-structural-design-dashboard' => ['controller' => 'Phase2\Con_DashboardController', 'method' => 'projectStructuralDesignDashboard'],
    'estimator-dashboard' => ['controller' => 'Phase2\Con_DashboardController', 'method' => 'estimatorDashboard'],
    'purchase-executive-dashboard' => ['controller' => 'Phase2\Con_DashboardController', 'method' => 'purchaseExecutiveDashboard'],
    'con-project-progress-report/(\d+)' => ['controller' => 'Phase2\Con_DashboardController', 'method' => 'projectProgressReport'],

    // =========================================================================
    // Controller: Phase2\Con_ProjectOverviewDashboardController
    // =========================================================================
    'all-projects-overview' => ['controller' => 'Phase2\Con_ProjectOverviewDashboardController', 'method' => 'index'],

    // =========================================================================
    // Controller: Phase2\Con_RiskDashboardController
    // =========================================================================
    'con-risk-dashboard/(\d+)' => ['controller' => 'Phase2\Con_RiskDashboardController', 'method' => 'index'],
    'con-risk-dashboard-store' => ['controller' => 'Phase2\Con_RiskDashboardController', 'method' => 'storeRisk'],
    'con-risk-dashboard-update-status' => ['controller' => 'Phase2\Con_RiskDashboardController', 'method' => 'updateRiskStatus'],
    'con-risk-dashboard-delete/(\d+)' => ['controller' => 'Phase2\Con_RiskDashboardController', 'method' => 'deleteRisk'],

    // =========================================================================
    // Controller: VoucherController
    // =========================================================================
    'print-debit-voucher' => ['controller' => 'VoucherController', 'method' => 'printDebitVoucher'],
    'trash-client-payment' => ['controller' => 'AccountClientController', 'method' => 'trashTransaction'],
    'trash-client-payment-list' => ['controller' => 'AccountClientController', 'method' => 'trashList'],
    'restore-client-payment' => ['controller' => 'AccountClientController', 'method' => 'restoreTransaction'],

];