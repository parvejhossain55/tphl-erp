<?php
return [


    // =========================================================================
    // Controller: CustomerController
    // =========================================================================
    'customer-lists' => ['controller' => 'CustomerController', 'method' => 'index'],
    'create-customer' => ['controller' => 'CustomerController', 'method' => 'create'],

    // =========================================================================
    // Controller: LeadController
    // =========================================================================
    'lead-lists' => ['controller' => 'LeadController', 'method' => 'index'],
    'leads-ajax-data' => ['controller' => 'LeadController', 'method' => 'getLeadsAjax'],
    'leads-export-excel' => ['controller' => 'LeadController', 'method' => 'exportExcel'],
    'leads-ajax-debug' => ['controller' => 'LeadController', 'method' => 'debugLeadsAjax'],
    'create-lead' => ['controller' => 'LeadController', 'method' => 'create'],
    'store-lead' => ['controller' => 'LeadController', 'method' => 'store'],
    'store-location-lead' => ['controller' => 'LeadController', 'method' => 'storeLocation'],
    'store-source-lead' => ['controller' => 'LeadController', 'method' => 'storeSource'],
    'edit-lead/(\d+)' => ['controller' => 'LeadController', 'method' => 'edit'],
    'view-lead/(\d+)' => ['controller' => 'LeadController', 'method' => 'show'],
    'leads-import-csv' => ['controller' => 'LeadController', 'method' => 'importCsv'],
    'leads-import-csv-confirm' => ['controller' => 'LeadController', 'method' => 'confirmImportCsv'],
    'leads-import-csv-cancel' => ['controller' => 'LeadController', 'method' => 'cancelImportCsv'],
    'dm-leads-csv-template' => ['controller' => 'LeadController', 'method' => 'downloadDmLeadsCsvTemplate'],
    'bulk-leads-import-csv' => ['controller' => 'LeadController', 'method' => 'importCsvBulkLeads'],
    'bulk-leads-import-execute' => ['controller' => 'LeadController', 'method' => 'executeCsvBulkLeads'],
    'delete-lead-ajax' => ['controller' => 'LeadController', 'method' => 'deleteLeadAjax'],
    'ignore-csv-lead-ajax' => ['controller' => 'LeadController', 'method' => 'ignoreCsvLeadAjax'],
    'onboarding-clients' => ['controller' => 'LeadController', 'method' => 'showOnboardClient'],
    'update-lead/(\d+)' => ['controller' => 'LeadController', 'method' => 'update'],
    'update-last_leadAction/(\d+)' => ['controller' => 'LeadController', 'method' => 'updateLastleadAction'],
    'lead-delete' => ['controller' => 'LeadController', 'method' => 'delete'],
    'assign-multiple-leads' => ['controller' => 'LeadController', 'method' => 'assignMultipleLeads'],
    'check-leads-duplicates' => ['controller' => 'LeadController', 'method' => 'checkLeadsDuplicates'],
    'reassign-duplicate-group' => ['controller' => 'LeadController', 'method' => 'reassignDuplicateGroup'],

    // =========================================================================
    // Controller: DigitalMarketingController
    // =========================================================================
    'digital-marketing-dashboard' => ['controller' => 'DigitalMarketingController', 'method' => 'index'],
    'digital-marketing-executive-dashboard' => ['controller' => 'DigitalMarketingController', 'method' => 'executiveDashboard'],
    'campaign-leads' => ['controller' => 'DigitalMarketingController', 'method' => 'executiveDashboard'],
    'dm-exec-leads-ajax' => ['controller' => 'DigitalMarketingController', 'method' => 'getLeadsAjax'],
    'dm-head-leads-ajax' => ['controller' => 'DigitalMarketingController', 'method' => 'getLeadsAjax'],
    'delete-lead-permanent' => ['controller' => 'DigitalMarketingController', 'method' => 'deletePermanent'],
    'delete-leads-permanent-multiple' => ['controller' => 'DigitalMarketingController', 'method' => 'deletePermanentMultiple'],
    'store-platform-budget' => ['controller' => 'DigitalMarketingController', 'method' => 'storeBudget'],
    'brochure-leads' => ['controller' => 'DigitalMarketingController', 'method' => 'brochureLeads'],
    'forms' => ['controller' => 'DigitalMarketingController', 'method' => 'forms'],
    'forms/(\d+)/responses' => ['controller' => 'DigitalMarketingController', 'method' => 'formResponses'],
    'digital-marketing-upload-report' => ['controller' => 'DigitalMarketingController', 'method' => 'uploadReport'],
    'team-lead-report' => ['controller' => 'DigitalMarketingController', 'method' => 'teamLeadReport'],
    'team-lead-report/export-excel' => ['controller' => 'DigitalMarketingController', 'method' => 'exportTeamLeadReportExcel'],
    'team-lead-report/lead-details-ajax' => ['controller' => 'DigitalMarketingController', 'method' => 'getTeamLeadReportDetailsAjax'],
    'all-platform-leads' => ['controller' => 'DigitalMarketingController', 'method' => 'allPlatformLeads'],
    'all-platform-leads-export' => ['controller' => 'DigitalMarketingController', 'method' => 'exportAllPlatformLeadsExcel'],

    // =========================================================================
    // Controller: OnboardClientController
    // =========================================================================
    'onboard-access-client-lists' => ['controller' => 'OnboardClientController', 'method' => 'index'],
    'create-onboard-client' => ['controller' => 'OnboardClientController', 'method' => 'create'],
    'store-onboard-client' => ['controller' => 'OnboardClientController', 'method' => 'store'],
    'edit-onboard-client/(\d+)' => ['controller' => 'OnboardClientController', 'method' => 'edit'],
    'update-onboard-client/(\d+)' => ['controller' => 'OnboardClientController', 'method' => 'update'],
    'get-lead-booking-details/(\d+)' => ['controller' => 'OnboardClientController', 'method' => 'getLeadBookingDetails'],
    'get-lead-all-bookings/(\d+)' => ['controller' => 'OnboardClientController', 'method' => 'getLeadAllBookings'],
    'assign-onboard-client' => ['controller' => 'OnboardClientController', 'method' => 'assignMultipleLeads'],
    'onboard-client-bulk-upload' => ['controller' => 'OnboardClientController', 'method' => 'bulkUploadForm'],
    'onboard-client-bulk-preview' => ['controller' => 'OnboardClientController', 'method' => 'bulkPreview'],
    'onboard-client-bulk-store' => ['controller' => 'OnboardClientController', 'method' => 'bulkStore'],

    // =========================================================================
    // Controller: BookingController
    // =========================================================================
    'bulk-bookings-import-csv' => ['controller' => 'BookingController', 'method' => 'importCsvBulkBookings'],
    'bulk-bookings-import-execute' => ['controller' => 'BookingController', 'method' => 'executeCsvBulkBookings'],
    'booking-leads/(\d+)' => ['controller' => 'BookingController', 'method' => 'index'],
    'booking-store' => ['controller' => 'BookingController', 'method' => 'store'],
    'booking-update' => ['controller' => 'BookingController', 'method' => 'update'],
    'booking-delete/(\d+)' => ['controller' => 'BookingController', 'method' => 'delete'],
    'booking-quick-refund' => ['controller' => 'BookingController', 'method' => 'quickRefund'],
    'get-flats-by-project/(\d+)' => ['controller' => 'BookingController', 'method' => 'getFlatsByProject'],
    'update-flats-by-project' => ['controller' => 'BookingController', 'method' => 'update'],

    // =========================================================================
    // Controller: PaymentBookingController
    // =========================================================================
    'bulk-payments-import-csv' => ['controller' => 'PaymentBookingController', 'method' => 'importCsvBulkPayments'],
    'bulk-payments-validate-csv' => ['controller' => 'PaymentBookingController', 'method' => 'validateCsvBulkPayments'],
    'payments-booking/(\d+)' => ['controller' => 'PaymentBookingController', 'method' => 'index'],
    'payment-booking-store' => ['controller' => 'PaymentBookingController', 'method' => 'store'],
    'payment-booking-update' => ['controller' => 'PaymentBookingController', 'method' => 'update'],
    'payment-booking-delete/(\d+)' => ['controller' => 'PaymentBookingController', 'method' => 'delete'],
    'payment-booking-invoice/(\d+)' => ['controller' => 'PaymentBookingController', 'method' => 'invoice'],
    'payment-booking-combine' => ['controller' => 'PaymentBookingController', 'method' => 'combinePayments'],

    // =========================================================================
    // Controller: SalesTeamKpiController
    // =========================================================================
    'sales-team-performance' => ['controller' => 'SalesTeamKpiController', 'method' => 'index'],

    // =========================================================================
    // Controller: LeadSourceController
    // =========================================================================
    'leadSource-lists' => ['controller' => 'LeadSourceController', 'method' => 'index'],
    'store-leadSource' => ['controller' => 'LeadSourceController', 'method' => 'store'],
    'edit-leadSource' => ['controller' => 'LeadSourceController', 'method' => 'edit'],
    'update-leadSource' => ['controller' => 'LeadSourceController', 'method' => 'update'],
    'delete-leadSource' => ['controller' => 'LeadSourceController', 'method' => 'delete'],

    // =========================================================================
    // Controller: LocationController
    // =========================================================================
    'location-lists' => ['controller' => 'LocationController', 'method' => 'index'],
    'store-location' => ['controller' => 'LocationController', 'method' => 'store'],
    'update-location' => ['controller' => 'LocationController', 'method' => 'update'],
    'delete-location' => ['controller' => 'LocationController', 'method' => 'delete'],

    // =========================================================================
    // Controller: AmenityController
    // =========================================================================
    'amenities' => ['controller' => 'AmenityController', 'method' => 'index'],
    'create-amenity' => ['controller' => 'AmenityController', 'method' => 'create'],
    'store-amenity' => ['controller' => 'AmenityController', 'method' => 'store'],
    'view-amenity/(\d+)' => ['controller' => 'AmenityController', 'method' => 'show'],
    'edit-amenity/(\d+)' => ['controller' => 'AmenityController', 'method' => 'edit'],
    'update-amenity/(\d+)' => ['controller' => 'AmenityController', 'method' => 'update'],
    'delete-amenity' => ['controller' => 'AmenityController', 'method' => 'delete'],

    // =========================================================================
    // Controller: UnitController
    // =========================================================================
    'unit-lists' => ['controller' => 'UnitController', 'method' => 'index'],
    'store-unit' => ['controller' => 'UnitController', 'method' => 'store'],
    'update-unit' => ['controller' => 'UnitController', 'method' => 'update'],
    'delete-unit' => ['controller' => 'UnitController', 'method' => 'delete'],

    // =========================================================================
    // Controller: CommunityController
    // =========================================================================
    'community-lists' => ['controller' => 'CommunityController', 'method' => 'index'],
    'store-community' => ['controller' => 'CommunityController', 'method' => 'store'],
    'update-community' => ['controller' => 'CommunityController', 'method' => 'update'],
    'delete-community' => ['controller' => 'CommunityController', 'method' => 'delete'],

    // =========================================================================
    // Controller: ProjectController
    // =========================================================================
    'project-lists' => ['controller' => 'ProjectController', 'method' => 'index'],
    'create-project' => ['controller' => 'ProjectController', 'method' => 'create'],
    'store-project' => ['controller' => 'ProjectController', 'method' => 'store'],
    'edit-project/(\d+)' => ['controller' => 'ProjectController', 'method' => 'edit'],
    'project-details/(\d+)' => ['controller' => 'ProjectController', 'method' => 'show'],
    'update-project/(\d+)' => ['controller' => 'ProjectController', 'method' => 'update'],
    'delete-project' => ['controller' => 'ProjectController', 'method' => 'delete'],
    'bulk-projects-import-csv' => ['controller' => 'ProjectController', 'method' => 'importCsvBulkProjects'],

    // =========================================================================
    // Controller: FlatController
    // =========================================================================
    'flat-lists' => ['controller' => 'FlatController', 'method' => 'index'],
    'ajax-flat-lists' => ['controller' => 'FlatController', 'method' => 'ajaxFlatList'],
    'create-flat' => ['controller' => 'FlatController', 'method' => 'create'],
    'store-flat' => ['controller' => 'FlatController', 'method' => 'store'],
    'edit-flat/(\d+)' => ['controller' => 'FlatController', 'method' => 'edit'],
    'flat-details/(\d+)' => ['controller' => 'FlatController', 'method' => 'show'],
    'update-flat/(\d+)' => ['controller' => 'FlatController', 'method' => 'update'],
    'delete-flat' => ['controller' => 'FlatController', 'method' => 'delete'],
    'bulk-flats-import-csv' => ['controller' => 'FlatController', 'method' => 'importCsvBulkFlats'],
    'bulk-suite-import-csv' => ['controller' => 'FlatController', 'method' => 'importCsvBulkSuite'],
    'bulk-shops-import-csv' => ['controller' => 'FlatController', 'method' => 'importCsvBulkShops'],

    // =========================================================================
    // Controller: FollowUpController
    // =========================================================================
    'follow-up-leads/(\d+)' => ['controller' => 'FollowUpController', 'method' => 'index'],
    'store-follow-up' => ['controller' => 'FollowUpController', 'method' => 'store'],
    'update-follow-up' => ['controller' => 'FollowUpController', 'method' => 'update'],
    'delete-follow-up' => ['controller' => 'FollowUpController', 'method' => 'delete'],
    'get-flats/(\d+)' => ['controller' => 'FollowUpController', 'method' => 'getFlats'],
    'get-follow-up-history/(\d+)' => ['controller' => 'FollowUpController', 'method' => 'getFollowUpHistoryAjax'],

    // =========================================================================
    // Controller: SalesDashboardController
    // =========================================================================
    'sales-dashboard' => ['controller' => 'SalesDashboardController', 'method' => 'index'],
    'sales-executive-teams' => ['controller' => 'SalesDashboardController', 'method' => 'salesTeam'],
    'set-sales-target/(\d+)' => ['controller' => 'SalesDashboardController', 'method' => 'salesTarget'],
    'save-sales-target/(\d+)' => ['controller' => 'SalesDashboardController', 'method' => 'storeSalesTarget'],
    'update-sales-target' => ['controller' => 'SalesDashboardController', 'method' => 'updateSalesTarget'],
    'get-project-flats' => ['controller' => 'SalesDashboardController', 'method' => 'getProjectFlats'],
    'get-target-totals' => ['controller' => 'SalesDashboardController', 'method' => 'getTargetTotals'],

    // =========================================================================
    // Controller: SalesHeadDashboardController
    // =========================================================================
    'sales-team-head-dashboard' => ['controller' => 'SalesHeadDashboardController', 'method' => 'index'],
    'sales-team-view' => ['controller' => 'SalesHeadDashboardController', 'method' => 'salesTeamView'],
    'sales-report' => ['controller' => 'SalesHeadDashboardController', 'method' => 'salesReport'],

    // =========================================================================
    // Controller: ContactController
    // =========================================================================
    'contact-lists' => ['controller' => 'ContactController', 'method' => 'index'],
    'contacts/update-status' => ['controller' => 'ContactController', 'method' => 'updateStatus'],

    // =========================================================================
    // Controller: WhatsappMarketing\WhatsappDashboardController
    // =========================================================================
    'whatsapp-dashboard' => ['controller' => 'WhatsappMarketing\WhatsappDashboardController', 'method' => 'dashboard'],
    'update-campaignAction/(\d+)' => ['controller' => 'WhatsappMarketing\WhatsappDashboardController', 'method' => 'updateCampaignAction'],
    'contacts-whatsapp' => ['controller' => 'WhatsappMarketing\WhatsappDashboardController', 'method' => 'contactLeads'],
    'report-campaign' => ['controller' => 'WhatsappMarketing\WhatsappDashboardController', 'method' => 'reports'],

    // =========================================================================
    // Controller: WhatsappMarketing\WhatsappCampaignController
    // =========================================================================
    'campaign-contacts' => ['controller' => 'WhatsappMarketing\WhatsappCampaignController', 'method' => 'index'],
    'store-campaign' => ['controller' => 'WhatsappMarketing\WhatsappCampaignController', 'method' => 'store'],
];
