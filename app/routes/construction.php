<?php
return [

    // =========================================================================
    // Controller: QcAuditController
    // =========================================================================
    'qc-audit-dashboard' => ['controller' => 'QcAuditController', 'method' => 'dashboard'],
    'qc-advanced-audit' => ['controller' => 'QcAuditController', 'method' => 'advancedAudit'],
    'api/qc-audit/materials' => ['controller' => 'QcAuditController', 'method' => 'getAuditMaterials'],
    'api/qc-audit/purchases' => ['controller' => 'QcAuditController', 'method' => 'getAuditPurchases'],
    'api/qc-audit/payments' => ['controller' => 'QcAuditController', 'method' => 'getAuditPayments'],
    'qc-requests' => ['controller' => 'QcAuditController', 'method' => 'pendingRequests'],
    'qc-approve-request' => ['controller' => 'QcAuditController', 'method' => 'approveRequest'],
    'api/qc-audit/audit-trail' => ['controller' => 'QcAuditController', 'method' => 'getAuditTrail'],
    'qc-audit/materials' => ['controller' => 'QcAuditController', 'method' => 'getAuditMaterials'],
    'qc-audit/purchases' => ['controller' => 'QcAuditController', 'method' => 'getAuditPurchases'],
    'qc-audit/payments' => ['controller' => 'QcAuditController', 'method' => 'getAuditPayments'],
    'qc-audit/approve' => ['controller' => 'QcAuditController', 'method' => 'approveRequest'],
    'qc-audit/trail' => ['controller' => 'QcAuditController', 'method' => 'getAuditTrail'],

    // =========================================================================
    // Controller: Phase2\Con_ProjectLegalSheetController
    // =========================================================================
    'legal-sheet-lists' => ['controller' => 'Phase2\Con_ProjectLegalSheetController', 'method' => 'index'],
    'create-legal-sheet' => ['controller' => 'Phase2\Con_ProjectLegalSheetController', 'method' => 'create'],
    'legal-sheet-store' => ['controller' => 'Phase2\Con_ProjectLegalSheetController', 'method' => 'store'],
    'edit-legal-sheet/(\d+)' => ['controller' => 'Phase2\Con_ProjectLegalSheetController', 'method' => 'edit'],
    'legal-sheet-update/(\d+)' => ['controller' => 'Phase2\Con_ProjectLegalSheetController', 'method' => 'update'],
    'delete-legal-sheet/(\d+)' => ['controller' => 'Phase2\Con_ProjectLegalSheetController', 'method' => 'delete'],
    'legal-sheet-import-csv' => ['controller' => 'Phase2\Con_ProjectLegalSheetController', 'method' => 'importCsv'],

    // =========================================================================
    // Controller: Phase2\LandVisibilityStudyController
    // =========================================================================
    'con-land-visibility-studies' => ['controller' => 'Phase2\LandVisibilityStudyController', 'method' => 'index'],
    'con-land-visibility-studies/create' => ['controller' => 'Phase2\LandVisibilityStudyController', 'method' => 'create'],
    'con-land-visibility-studies/store' => ['controller' => 'Phase2\LandVisibilityStudyController', 'method' => 'store'],
    'con-land-visibility-studies/edit' => ['controller' => 'Phase2\LandVisibilityStudyController', 'method' => 'edit'],
    'con-land-visibility-studies/update' => ['controller' => 'Phase2\LandVisibilityStudyController', 'method' => 'update'],
    'delete-land-visibility-study' => ['controller' => 'Phase2\LandVisibilityStudyController', 'method' => 'delete'],
    'con-land-visibility-import' => ['controller' => 'Phase2\LandVisibilityStudyController', 'method' => 'importCsv'],

    // =========================================================================
    // Controller: Phase2\LandAcquisitionController
    // =========================================================================
    'con-land-acquisitions' => ['controller' => 'Phase2\LandAcquisitionController', 'method' => 'index'],
    'con-land-acquisitions/create' => ['controller' => 'Phase2\LandAcquisitionController', 'method' => 'create'],
    'con-land-acquisitions/store' => ['controller' => 'Phase2\LandAcquisitionController', 'method' => 'store'],
    'con-land-acquisitions/edit' => ['controller' => 'Phase2\LandAcquisitionController', 'method' => 'edit'],
    'con-land-acquisitions/update' => ['controller' => 'Phase2\LandAcquisitionController', 'method' => 'update'],
    'con-land-acquisitions/delete' => ['controller' => 'Phase2\LandAcquisitionController', 'method' => 'delete'],
    'con-land-acquisitions/execute-import' => ['controller' => 'Phase2\LandAcquisitionController', 'method' => 'executeImport'],
    'con-land-acquisitions/sample-csv' => ['controller' => 'Phase2\LandAcquisitionController', 'method' => 'downloadSampleCsv'],

    // =========================================================================
    // Controller: Phase2\LandRequisitionController
    // =========================================================================
    'con-land-requisitions' => ['controller' => 'Phase2\LandRequisitionController', 'method' => 'index'],
    'con-land-requisitions/create' => ['controller' => 'Phase2\LandRequisitionController', 'method' => 'create'],
    'con-land-requisitions/store' => ['controller' => 'Phase2\LandRequisitionController', 'method' => 'store'],
    'con-land-requisitions/approve' => ['controller' => 'Phase2\LandRequisitionController', 'method' => 'approve'],
    'con-land-requisitions/pay' => ['controller' => 'Phase2\LandRequisitionController', 'method' => 'pay'],
    'con-land-requisitions/delete' => ['controller' => 'Phase2\LandRequisitionController', 'method' => 'delete'],

    // =========================================================================
    // Controller: Phase2\LandReportController
    // =========================================================================
    'con-land-monthly-reports' => ['controller' => 'Phase2\LandReportController', 'method' => 'index'],
    'con-land-monthly-reports/generate' => ['controller' => 'Phase2\LandReportController', 'method' => 'generate'],
    'con-land-ledger' => ['controller' => 'Phase2\LandReportController', 'method' => 'ledger'],

    // =========================================================================
    // Controller: Phase2\FundRequisitionController
    // =========================================================================
    'fund-requisitions' => ['controller' => 'Phase2\FundRequisitionController', 'method' => 'index'],
    'fund-requisitions/store' => ['controller' => 'Phase2\FundRequisitionController', 'method' => 'store'],
    'fund-requisitions/approve' => ['controller' => 'Phase2\FundRequisitionController', 'method' => 'approve'],
    'fund-requisitions/approve-dept' => ['controller' => 'Phase2\FundRequisitionController', 'method' => 'approveDept'],
    'fund-requisitions/pay' => ['controller' => 'Phase2\FundRequisitionController', 'method' => 'pay'],
    'fund-requisitions/delete' => ['controller' => 'Phase2\FundRequisitionController', 'method' => 'delete'],
    'fund-requisitions/show/(\d+)' => ['controller' => 'Phase2\FundRequisitionController', 'method' => 'show'],
    'fund-requisitions/store-expense' => ['controller' => 'Phase2\FundRequisitionController', 'method' => 'storeExpense'],
    'fund-requisitions/delete-expense' => ['controller' => 'Phase2\FundRequisitionController', 'method' => 'deleteExpense'],
    'fund-requisitions/view/(\d+)' => ['controller' => 'Phase2\FundRequisitionController', 'method' => 'showPage'],
    'fund-requisitions/settle/(\d+)' => ['controller' => 'Phase2\FundRequisitionController', 'method' => 'submitSettlement'],
    'fund-requisitions/balance-return/(\d+)' => ['controller' => 'Phase2\FundRequisitionController', 'method' => 'markBalanceReturned'],
    'fund-requisitions/verify-settle/(\d+)' => ['controller' => 'Phase2\FundRequisitionController', 'method' => 'verifySettlement'],
    'fund-requisitions/extra-fund/(\d+)' => ['controller' => 'Phase2\FundRequisitionController', 'method' => 'requestExtraFund'],
    'fund-requisitions/confirm-receipt/(\d+)' => ['controller' => 'Phase2\FundRequisitionController', 'method' => 'confirmReceipt'],

    // =========================================================================
    // Controller: Phase2\ArchitectEngineeringController
    // =========================================================================
    'con-architect-engineering' => ['controller' => 'Phase2\ArchitectEngineeringController', 'method' => 'index'],
    'con-architect-engineering/create' => ['controller' => 'Phase2\ArchitectEngineeringController', 'method' => 'create'],
    'con-architect-engineering/store' => ['controller' => 'Phase2\ArchitectEngineeringController', 'method' => 'store'],
    'con-architect-engineering/edit' => ['controller' => 'Phase2\ArchitectEngineeringController', 'method' => 'edit'],
    'con-architect-engineering/update' => ['controller' => 'Phase2\ArchitectEngineeringController', 'method' => 'update'],
    'delete-architect-engineering' => ['controller' => 'Phase2\ArchitectEngineeringController', 'method' => 'delete'],
    'con-architect-engineering-import' => ['controller' => 'Phase2\ArchitectEngineeringController', 'method' => 'importCsv'],

    // =========================================================================
    // Controller: Phase2\DesignTypeController
    // =========================================================================
    'con-design-types' => ['controller' => 'Phase2\DesignTypeController', 'method' => 'index'],
    'con-design-types/create' => ['controller' => 'Phase2\DesignTypeController', 'method' => 'create'],
    'con-design-types/store' => ['controller' => 'Phase2\DesignTypeController', 'method' => 'store'],
    'con-design-types/edit' => ['controller' => 'Phase2\DesignTypeController', 'method' => 'edit'],
    'con-design-types/update' => ['controller' => 'Phase2\DesignTypeController', 'method' => 'update'],
    'delete-design-type' => ['controller' => 'Phase2\DesignTypeController', 'method' => 'delete'],
    'con-design-type-import' => ['controller' => 'Phase2\DesignTypeController', 'method' => 'importCsv'],

    // =========================================================================
    // Controller: Phase2\Con_WorkTypeController
    // =========================================================================
    'con-work-types' => ['controller' => 'Phase2\Con_WorkTypeController', 'method' => 'index'],
    'con-work-type-create' => ['controller' => 'Phase2\Con_WorkTypeController', 'method' => 'create'],
    'con-work-type-store' => ['controller' => 'Phase2\Con_WorkTypeController', 'method' => 'store'],
    'con-work-type-edit/(\d+)' => ['controller' => 'Phase2\Con_WorkTypeController', 'method' => 'edit'],
    'con-work-type-update/(\d+)' => ['controller' => 'Phase2\Con_WorkTypeController', 'method' => 'update'],
    'con-work-type-delete' => ['controller' => 'Phase2\Con_WorkTypeController', 'method' => 'delete'],
    'con-work-type-import' => ['controller' => 'Phase2\Con_WorkTypeController', 'method' => 'importCsv'],

    // =========================================================================
    // Controller: Phase2\ProjectDrawingController
    // =========================================================================
    'con-project-drawings' => ['controller' => 'Phase2\ProjectDrawingController', 'method' => 'index'],
    'con-project-drawings/create' => ['controller' => 'Phase2\ProjectDrawingController', 'method' => 'create'],
    'con-project-drawings/store' => ['controller' => 'Phase2\ProjectDrawingController', 'method' => 'store'],
    'con-project-drawings/edit' => ['controller' => 'Phase2\ProjectDrawingController', 'method' => 'edit'],
    'con-project-drawings/update' => ['controller' => 'Phase2\ProjectDrawingController', 'method' => 'update'],
    'delete-project-drawing' => ['controller' => 'Phase2\ProjectDrawingController', 'method' => 'delete'],
    'con-project-drawing-import' => ['controller' => 'Phase2\ProjectDrawingController', 'method' => 'importCsv'],

    // =========================================================================
    // Controller: Phase2\ProjectStructuralDesignController
    // =========================================================================
    'structure-design' => ['controller' => 'Phase2\ProjectStructuralDesignController', 'method' => 'index'],
    'structure-design/create' => ['controller' => 'Phase2\ProjectStructuralDesignController', 'method' => 'create'],
    'structure-design/store' => ['controller' => 'Phase2\ProjectStructuralDesignController', 'method' => 'store'],
    'structure-design/edit' => ['controller' => 'Phase2\ProjectStructuralDesignController', 'method' => 'edit'],
    'structure-design/update' => ['controller' => 'Phase2\ProjectStructuralDesignController', 'method' => 'update'],
    'delete-structure-design' => ['controller' => 'Phase2\ProjectStructuralDesignController', 'method' => 'delete'],
    'con-structure-design-import' => ['controller' => 'Phase2\ProjectStructuralDesignController', 'method' => 'importCsv'],

    // =========================================================================
    // Controller: Phase2\SoilTestController
    // =========================================================================
    'con-soil-tests' => ['controller' => 'Phase2\SoilTestController', 'method' => 'index'],
    'con-soil-tests/create' => ['controller' => 'Phase2\SoilTestController', 'method' => 'create'],
    'con-soil-tests/store' => ['controller' => 'Phase2\SoilTestController', 'method' => 'store'],
    'con-soil-tests/edit' => ['controller' => 'Phase2\SoilTestController', 'method' => 'edit'],
    'con-soil-tests/update' => ['controller' => 'Phase2\SoilTestController', 'method' => 'update'],
    'delete-soil-test' => ['controller' => 'Phase2\SoilTestController', 'method' => 'delete'],
    'con-soil-test-import' => ['controller' => 'Phase2\SoilTestController', 'method' => 'importCsv'],

    // =========================================================================
    // Controller: Phase2\Con_ProjectTimelineController
    // =========================================================================
    'con-project-timeline/(\d+)' => ['controller' => 'Phase2\Con_ProjectTimelineController', 'method' => 'index'],
    'con-project-timeline-store' => ['controller' => 'Phase2\Con_ProjectTimelineController', 'method' => 'store'],
    'con-project-timeline-edit/(\d+)' => ['controller' => 'Phase2\Con_ProjectTimelineController', 'method' => 'edit'],
    'con-project-timeline-update/(\d+)' => ['controller' => 'Phase2\Con_ProjectTimelineController', 'method' => 'update'],
    'con-floor-setup/(\d+)' => ['controller' => 'Phase2\Con_ProjectTimelineController', 'method' => 'floorSetup'],
    'con-floor-setup-save/(\d+)' => ['controller' => 'Phase2\Con_ProjectTimelineController', 'method' => 'floorSetupSave'],

    // =========================================================================
    // Controller: Phase2\Con_PhasePlanController
    // =========================================================================
    'con-phase-plan/(\d+)' => ['controller' => 'Phase2\Con_PhasePlanController', 'method' => 'index'],
    'con-phase-plan-update/(\d+)' => ['controller' => 'Phase2\Con_PhasePlanController', 'method' => 'update'],
    'con-phase-add' => ['controller' => 'Phase2\Con_PhasePlanController', 'method' => 'addPhase'],
    'con-phase-delete/(\d+)' => ['controller' => 'Phase2\Con_PhasePlanController', 'method' => 'delete'],
    'con-phase-template-json' => ['controller' => 'Phase2\Con_PhasePlanController', 'method' => 'ajaxGetTemplate'],

    // =========================================================================
    // Controller: Phase2\Con_PhaseItemController
    // =========================================================================
    'con-phase-items/(\d+)' => ['controller' => 'Phase2\Con_PhaseItemController', 'method' => 'index'],
    'con-phase-items-update/(\d+)' => ['controller' => 'Phase2\Con_PhaseItemController', 'method' => 'update'],
    'con-phase-items-delete/(\d+)' => ['controller' => 'Phase2\Con_PhaseItemController', 'method' => 'delete'],
    'con-phase-items-progress' => ['controller' => 'Phase2\Con_PhaseItemController', 'method' => 'ajaxUpdateProgress'],
    'con-phase-item-add' => ['controller' => 'Phase2\Con_PhaseItemController', 'method' => 'addItem'],
    'con-phase-item-edit' => ['controller' => 'Phase2\Con_PhaseItemController', 'method' => 'editItem'],

    // =========================================================================
    // Controller: Phase2\Con_MonthlyTargetController
    // =========================================================================
    'con-monthly-targets/(\d+)' => ['controller' => 'Phase2\Con_MonthlyTargetController', 'method' => 'index'],
    'con-monthly-target/(\d+)' => ['controller' => 'Phase2\Con_MonthlyTargetController', 'method' => 'show'],
    'con-monthly-target-create/(\d+)' => ['controller' => 'Phase2\Con_MonthlyTargetController', 'method' => 'create'],
    'con-monthly-target-status/(\d+)' => ['controller' => 'Phase2\Con_MonthlyTargetController', 'method' => 'updateStatus'],
    'con-monthly-target-save/(\d+)' => ['controller' => 'Phase2\Con_MonthlyTargetController', 'method' => 'saveItems'],
    'con-monthly-item-add' => ['controller' => 'Phase2\Con_MonthlyTargetController', 'method' => 'addItem'],
    'con-monthly-item-delete/(\d+)' => ['controller' => 'Phase2\Con_MonthlyTargetController', 'method' => 'deleteItem'],
    'con-monthly-target-delete/(\d+)' => ['controller' => 'Phase2\Con_MonthlyTargetController', 'method' => 'deleteTarget'],
    'con-monthly-worktypes' => ['controller' => 'Phase2\Con_MonthlyTargetController', 'method' => 'ajaxWorkTypes'],
    'con-project-plan-targets' => ['controller' => 'Phase2\Con_MonthlyTargetController', 'method' => 'siteEngineerTargets'],
    'con-working-plans' => ['controller' => 'Phase2\Con_MonthlyTargetController', 'method' => 'siteEngineerTargets'],
    'con-monthly-item-daily-breakdown' => ['controller' => 'Phase2\Con_MonthlyTargetController', 'method' => 'saveDailyPctBreakdown'],
    'con-ajax-contractor-workorders' => ['controller' => 'Phase2\Con_MonthlyTargetController', 'method' => 'ajaxGetWorkOrders'],

    // =========================================================================
    // Controller: Phase2\Con_PhaseWeeklyProgressController
    // =========================================================================
    'con-weekly-progress/(\d+)' => ['controller' => 'Phase2\Con_PhaseWeeklyProgressController', 'method' => 'show'],
    'con-weekly-progress-save' => ['controller' => 'Phase2\Con_PhaseWeeklyProgressController', 'method' => 'saveDailyLog'],
    'con-weekly-progress-submit' => ['controller' => 'Phase2\Con_PhaseWeeklyProgressController', 'method' => 'submitWeeklyUpdate'],
    'con-weekly-progress-delete/(\d+)' => ['controller' => 'Phase2\Con_PhaseWeeklyProgressController', 'method' => 'deleteDailyLogItem'],
    'con-weekly-progress-qc-approve' => ['controller' => 'Phase2\Con_PhaseWeeklyProgressController', 'method' => 'qcApprove'],
    'con-weekly-progress-pm-approve' => ['controller' => 'Phase2\Con_PhaseWeeklyProgressController', 'method' => 'pmApprove'],

    // =========================================================================
    // Controller: Phase2\Con_MonthlyReviewController
    // =========================================================================
    'con-monthly-review/(\d+)' => ['controller' => 'Phase2\Con_MonthlyReviewController', 'method' => 'index'],
    'con-monthly-review/(\d+)/phase/(\d+)/month/([\d-]+)' => ['controller' => 'Phase2\Con_MonthlyReviewController', 'method' => 'show'],

    // =========================================================================
    // Controller: Phase2\Con_RecoveryPlanController
    // =========================================================================
    'con-recovery-plan/(\d+)' => ['controller' => 'Phase2\Con_RecoveryPlanController', 'method' => 'index'],
    'con-recovery-plan-create' => ['controller' => 'Phase2\Con_RecoveryPlanController', 'method' => 'createPlan'],
    'con-recovery-plan-action-store' => ['controller' => 'Phase2\Con_RecoveryPlanController', 'method' => 'storeActionItem'],
    'con-recovery-plan-action-update' => ['controller' => 'Phase2\Con_RecoveryPlanController', 'method' => 'updateActionStatus'],

    // =========================================================================
    // Controller: Phase2\Con_MonthlyWorkReportController
    // =========================================================================
    'con-monthly-work-report' => ['controller' => 'Phase2\Con_MonthlyWorkReportController', 'method' => 'index'],

    // =========================================================================
    // Controller: Phase2\Con_SecurityOperationsController
    // =========================================================================
    'con-security-systems' => ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'systemLists'],
    'con-security-systems/create' => ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'systemCreate'],
    'con-security-systems/store' => ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'systemStore'],
    'con-security-systems/edit' => ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'systemEdit'],
    'con-security-systems/update' => ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'systemUpdate'],
    'con-security-systems/view' => ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'systemView'],
    'con-security-equipments' => ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'equipmentLists'],
    'con-security-equipments/create' => ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'equipmentCreate'],
    'con-security-equipments/store' => ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'equipmentStore'],
    'con-security-equipments/view' => ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'equipmentView'],
    'con-security-logs' => ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'logLists'],
    'con-security-logs/create' => ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'logCreate'],
    'con-security-logs/store' => ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'logStore'],
    'con-security-logs/approve' => ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'logApprove'],
    'con-security-logs/view' => ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'logView'],
    'con-security-incidents' => ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'incidentLists'],
    'con-security-incidents/create' => ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'incidentCreate'],
    'con-security-incidents/store' => ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'incidentStore'],
    'con-security-incidents/approve' => ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'incidentApprove'],
    'con-security-incidents/view' => ['controller' => 'Phase2\Con_SecurityOperationsController', 'method' => 'incidentView'],

    // =========================================================================
    // Controller: Phase2\QCController
    // =========================================================================
    'qc-dashboard' => ['controller' => 'Phase2\QCController', 'method' => 'index'],
    'qc-inspection-create' => ['controller' => 'Phase2\QCController', 'method' => 'createInspection'],
    'qc-inspection/(\d+)' => ['controller' => 'Phase2\QCController', 'method' => 'showInspection'],
    'qc-inspection-store' => ['controller' => 'Phase2\QCController', 'method' => 'storeInspection'],
    'qc-material-test-create' => ['controller' => 'Phase2\QCController', 'method' => 'createMaterialTest'],
    'qc-material-test-store' => ['controller' => 'Phase2\QCController', 'method' => 'storeMaterialTest'],
    'qc-ajax-checklists' => ['controller' => 'Phase2\QCController', 'method' => 'ajaxGetChecklists'],
    'qc-ajax-receiving-items' => ['controller' => 'Phase2\QCController', 'method' => 'ajaxGetReceivingItems'],
    'qc-test-types' => ['controller' => 'Phase2\QCController', 'method' => 'testTypesIndex'],
    'qc-test-types-store' => ['controller' => 'Phase2\QCController', 'method' => 'testTypesStore'],
    'qc-test-types-update' => ['controller' => 'Phase2\QCController', 'method' => 'testTypesUpdate'],
    'qc-test-types-delete' => ['controller' => 'Phase2\QCController', 'method' => 'testTypesDelete'],
    'qc-checklists' => ['controller' => 'Phase2\QCController', 'method' => 'checklistsIndex'],
    'qc-checklist-store' => ['controller' => 'Phase2\QCController', 'method' => 'checklistStore'],
    'qc-checklist-update' => ['controller' => 'Phase2\QCController', 'method' => 'checklistUpdate'],
    'qc-checklist-delete' => ['controller' => 'Phase2\QCController', 'method' => 'checklistDelete'],

    // =========================================================================
    // Controller: Phase2\Con_ContractorController
    // =========================================================================
    'con-contractors' => ['controller' => 'Phase2\Con_ContractorController', 'method' => 'index'],
    'con-contractor-create' => ['controller' => 'Phase2\Con_ContractorController', 'method' => 'create'],
    'con-contractor-store' => ['controller' => 'Phase2\Con_ContractorController', 'method' => 'store'],
    'con-contractor-edit/(\d+)' => ['controller' => 'Phase2\Con_ContractorController', 'method' => 'edit'],
    'con-contractor-update/(\d+)' => ['controller' => 'Phase2\Con_ContractorController', 'method' => 'update'],
    'con-contractor-delete' => ['controller' => 'Phase2\Con_ContractorController', 'method' => 'delete'],
    'con-contractors-import' => ['controller' => 'Phase2\Con_ContractorController', 'method' => 'importCsv'],

    // =========================================================================
    // Controller: Phase2\Con_ContractorBillController
    // =========================================================================
    'con-contractor-bills' => ['controller' => 'Phase2\Con_ContractorBillController', 'method' => 'index'],
    'con-contractor-bill-create' => ['controller' => 'Phase2\Con_ContractorBillController', 'method' => 'create'],
    'con-contractor-bill-store' => ['controller' => 'Phase2\Con_ContractorBillController', 'method' => 'store'],
    'con-contractor-bill/(\d+)' => ['controller' => 'Phase2\Con_ContractorBillController', 'method' => 'show'],
    'con-contractor-bill-verify/(\d+)' => ['controller' => 'Phase2\Con_ContractorBillController', 'method' => 'verify'],
    'con-contractor-bill-approve/(\d+)' => ['controller' => 'Phase2\Con_ContractorBillController', 'method' => 'approve'],
    'con-contractor-bill-reject/(\d+)' => ['controller' => 'Phase2\Con_ContractorBillController', 'method' => 'reject'],
    'con-contractor-bill-payment/(\d+)' => ['controller' => 'Phase2\Con_ContractorBillController', 'method' => 'addPayment'],
    'con-ajax-project-targets' => ['controller' => 'Phase2\Con_ContractorBillController', 'method' => 'ajaxGetTargetsByProject'],
    'con-contractor-bill-settlement/(\d+)' => ['controller' => 'Phase2\Con_ContractorBillController', 'method' => 'submitSettlement'],
    'con-contractor-bill-balance-return/(\d+)' => ['controller' => 'Phase2\Con_ContractorBillController', 'method' => 'markBalanceReturned'],
    'con-contractor-bill-settlement-verify/(\d+)' => ['controller' => 'Phase2\Con_ContractorBillController', 'method' => 'verifySettlement'],

    // =========================================================================
    // Controller: Phase2\Con_FundRequestController
    // =========================================================================
    'con-fund-requests' => ['controller' => 'Phase2\Con_FundRequestController', 'method' => 'index'],
    'con-fund-request-create' => ['controller' => 'Phase2\Con_FundRequestController', 'method' => 'create'],
    'con-fund-request-store' => ['controller' => 'Phase2\Con_FundRequestController', 'method' => 'store'],
    'con-fund-request/(\d+)' => ['controller' => 'Phase2\Con_FundRequestController', 'method' => 'show'],
    'con-fund-request-approve/(\d+)' => ['controller' => 'Phase2\Con_FundRequestController', 'method' => 'approve'],
    'con-fund-request-reject/(\d+)' => ['controller' => 'Phase2\Con_FundRequestController', 'method' => 'reject'],
    'con-fund-request-payment/(\d+)' => ['controller' => 'Phase2\Con_FundRequestController', 'method' => 'addPayment'],

    // =========================================================================
    // Controller: Phase2\Con_LaborRequisitionController
    // =========================================================================
    'con-labor-requisitions' => ['controller' => 'Phase2\Con_LaborRequisitionController', 'method' => 'index'],
    'con-labor-requisition-create' => ['controller' => 'Phase2\Con_LaborRequisitionController', 'method' => 'create'],
    'con-labor-requisition-store' => ['controller' => 'Phase2\Con_LaborRequisitionController', 'method' => 'store'],
    'con-labor-requisition/(\d+)' => ['controller' => 'Phase2\Con_LaborRequisitionController', 'method' => 'show'],
    'con-labor-req-pm-approve/(\d+)' => ['controller' => 'Phase2\Con_LaborRequisitionController', 'method' => 'pmApprove'],
    'con-labor-req-pm-reject/(\d+)' => ['controller' => 'Phase2\Con_LaborRequisitionController', 'method' => 'pmReject'],
    'con-labor-req-work-complete/(\d+)' => ['controller' => 'Phase2\Con_LaborRequisitionController', 'method' => 'markWorkComplete'],
    'con-labor-req-payment-request/(\d+)' => ['controller' => 'Phase2\Con_LaborRequisitionController', 'method' => 'requestPayment'],
    'con-labor-req-qc-approve/(\d+)' => ['controller' => 'Phase2\Con_LaborRequisitionController', 'method' => 'qcApprove'],
    'con-labor-req-qc-reject/(\d+)' => ['controller' => 'Phase2\Con_LaborRequisitionController', 'method' => 'qcReject'],
    'con-labor-req-pm-pay-approve/(\d+)' => ['controller' => 'Phase2\Con_LaborRequisitionController', 'method' => 'pmPaymentApprove'],
    'con-labor-req-pm-pay-reject/(\d+)' => ['controller' => 'Phase2\Con_LaborRequisitionController', 'method' => 'pmPaymentReject'],
    'con-labor-req-mgmt-approve/(\d+)' => ['controller' => 'Phase2\Con_LaborRequisitionController', 'method' => 'managementApprove'],
    'con-labor-req-mgmt-reject/(\d+)' => ['controller' => 'Phase2\Con_LaborRequisitionController', 'method' => 'managementReject'],
    'con-labor-req-mark-paid/(\d+)' => ['controller' => 'Phase2\Con_LaborRequisitionController', 'method' => 'markPaid'],
    'con-labor-req-mgmt-approve-remaining/(\d+)' => ['controller' => 'Phase2\Con_LaborRequisitionController', 'method' => 'mgmtApproveRemaining'],
    'con-labor-req-labor-disbursed/(\d+)' => ['controller' => 'Phase2\Con_LaborRequisitionController', 'method' => 'markLaborDisbursed'],
    'con-labor-requisition-delete/(\d+)' => ['controller' => 'Phase2\Con_LaborRequisitionController', 'method' => 'delete'],

    // =========================================================================
    // Controller: Phase2\Con_MaterialController
    // =========================================================================
    'con-materials' => ['controller' => 'Phase2\Con_MaterialController', 'method' => 'index'],
    'con-material-create' => ['controller' => 'Phase2\Con_MaterialController', 'method' => 'create'],
    'con-material-store' => ['controller' => 'Phase2\Con_MaterialController', 'method' => 'store'],
    'con-material-store-ajax' => ['controller' => 'Phase2\Con_MaterialController', 'method' => 'ajaxStoreMaterial'],
    'con-material-edit/(\d+)' => ['controller' => 'Phase2\Con_MaterialController', 'method' => 'edit'],
    'con-material-update/(\d+)' => ['controller' => 'Phase2\Con_MaterialController', 'method' => 'update'],
    'con-materials-import' => ['controller' => 'Phase2\Con_MaterialController', 'method' => 'importCsv'],
    'con-material-delete/(\d+)' => ['controller' => 'Phase2\Con_MaterialController', 'method' => 'delete'],
    'con-material-category-store' => ['controller' => 'Phase2\Con_MaterialController', 'method' => 'storeCategory'],
    'con-material-category-update/(\d+)' => ['controller' => 'Phase2\Con_MaterialController', 'method' => 'updateCategory'],
    'con-material-category-delete/(\d+)' => ['controller' => 'Phase2\Con_MaterialController', 'method' => 'deleteCategory'],
    'con-stocks' => ['controller' => 'Phase2\Con_MaterialController', 'method' => 'stocks'],

    // =========================================================================
    // Controller: Phase2\WarehouseController
    // =========================================================================
    'con-warehouses' => ['controller' => 'Phase2\WarehouseController', 'method' => 'index'],
    'con-warehouse-create' => ['controller' => 'Phase2\WarehouseController', 'method' => 'create'],
    'con-warehouse-store' => ['controller' => 'Phase2\WarehouseController', 'method' => 'store'],
    'con-warehouse-edit/(\d+)' => ['controller' => 'Phase2\WarehouseController', 'method' => 'edit'],
    'con-warehouse-update/(\d+)' => ['controller' => 'Phase2\WarehouseController', 'method' => 'update'],
    'con-warehouses-import' => ['controller' => 'Phase2\WarehouseController', 'method' => 'importCsv'],

    // =========================================================================
    // Controller: Phase2\Con_VendorController
    // =========================================================================
    'con-vendors' => ['controller' => 'Phase2\Con_VendorController', 'method' => 'index'],
    'con-vendor-create' => ['controller' => 'Phase2\Con_VendorController', 'method' => 'create'],
    'con-vendor-store' => ['controller' => 'Phase2\Con_VendorController', 'method' => 'store'],
    'con-vendor-edit/(\d+)' => ['controller' => 'Phase2\Con_VendorController', 'method' => 'edit'],
    'con-vendor-update/(\d+)' => ['controller' => 'Phase2\Con_VendorController', 'method' => 'update'],
    'con-vendor/(\d+)' => ['controller' => 'Phase2\Con_VendorController', 'method' => 'show'],
    'con-vendor-delete' => ['controller' => 'Phase2\Con_VendorController', 'method' => 'delete'],
    'con-vendor-import' => ['controller' => 'Phase2\Con_VendorController', 'method' => 'importCsv'],

    // =========================================================================
    // Controller: Phase2\Con_PurchaseRequestController
    // =========================================================================
    'con-purchase-requests' => ['controller' => 'Phase2\Con_PurchaseRequestController', 'method' => 'index'],
    'con-purchase-request-create' => ['controller' => 'Phase2\Con_PurchaseRequestController', 'method' => 'create'],
    'con-purchase-request-store' => ['controller' => 'Phase2\Con_PurchaseRequestController', 'method' => 'store'],
    'con-purchase-request-edit/(\d+)' => ['controller' => 'Phase2\Con_PurchaseRequestController', 'method' => 'edit'],
    'con-purchase-request-update/(\d+)' => ['controller' => 'Phase2\Con_PurchaseRequestController', 'method' => 'update'],
    'con-purchase-request/(\d+)' => ['controller' => 'Phase2\Con_PurchaseRequestController', 'method' => 'show'],
    'con-purchase-request-approve/(\d+)' => ['controller' => 'Phase2\Con_PurchaseRequestController', 'method' => 'approve'],
    'con-purchase-request-delete/(\d+)' => ['controller' => 'Phase2\Con_PurchaseRequestController', 'method' => 'delete'],
    'con-purchase-request-assign/(\d+)' => ['controller' => 'Phase2\Con_PurchaseRequestController', 'method' => 'assignExecutive'],
    'con-boq-remaining/(\d+)' => ['controller' => 'Phase2\Con_PurchaseRequestController', 'method' => 'boqRemaining'],
    'con-ajax-pr-boqs' => ['controller' => 'Phase2\Con_PurchaseRequestController', 'method' => 'ajaxGetProjectBoqs'],
    'con-ajax-add-material' => ['controller' => 'Phase2\Con_PurchaseRequestController', 'method' => 'ajaxAddMaterial'],

    // =========================================================================
    // Controller: Phase2\Con_QuotationController
    // =========================================================================
    'con-quotation-create/(\d+)' => ['controller' => 'Phase2\Con_QuotationController', 'method' => 'create'],
    'con-quotation-store' => ['controller' => 'Phase2\Con_QuotationController', 'method' => 'store'],
    'con-quotation-compare/(\d+)' => ['controller' => 'Phase2\Con_QuotationController', 'method' => 'compare'],
    'con-quotation-select' => ['controller' => 'Phase2\Con_QuotationController', 'method' => 'selectWinner'],
    'con-quotation-edit/(\d+)' => ['controller' => 'Phase2\Con_QuotationController', 'method' => 'edit'],
    'con-quotation-update/(\d+)' => ['controller' => 'Phase2\Con_QuotationController', 'method' => 'update'],
    'con-quotation-delete/(\d+)' => ['controller' => 'Phase2\Con_QuotationController', 'method' => 'delete'],

    // =========================================================================
    // Controller: Phase2\Con_PurchaseOrderController
    // =========================================================================
    'con-purchase-orders' => ['controller' => 'Phase2\Con_PurchaseOrderController', 'method' => 'index'],
    'con-purchase-order/(\d+)' => ['controller' => 'Phase2\Con_PurchaseOrderController', 'method' => 'show'],
    'con-purchase-order-generate/(\d+)' => ['controller' => 'Phase2\Con_PurchaseOrderController', 'method' => 'generate'],
    'con-purchase-order-approve/(\d+)' => ['controller' => 'Phase2\Con_PurchaseOrderController', 'method' => 'approvePO'],
    'con-purchase-order-reject/(\d+)' => ['controller' => 'Phase2\Con_PurchaseOrderController', 'method' => 'rejectPO'],
    'con-purchase-order-note/(\d+)' => ['controller' => 'Phase2\Con_PurchaseOrderController', 'method' => 'addNote'],

    // =========================================================================
    // Controller: Phase2\Con_MaterialReceivingController
    // =========================================================================
    'con-material-receivings' => ['controller' => 'Phase2\Con_MaterialReceivingController', 'method' => 'index'],
    'con-material-receiving-create/(\d+)' => ['controller' => 'Phase2\Con_MaterialReceivingController', 'method' => 'create'],
    'con-material-receiving-store' => ['controller' => 'Phase2\Con_MaterialReceivingController', 'method' => 'store'],
    'con-material-receiving-qc/(\d+)' => ['controller' => 'Phase2\Con_MaterialReceivingController', 'method' => 'qcReview'],
    'con-material-receiving-pm/(\d+)' => ['controller' => 'Phase2\Con_MaterialReceivingController', 'method' => 'pmStore'],

    // =========================================================================
    // Controller: Phase2\Con_MaterialReceivingImportController
    // =========================================================================
    'con-material-receiving-import' => ['controller' => 'Phase2\Con_MaterialReceivingImportController', 'method' => 'index'],
    'con-material-receiving-import-process' => ['controller' => 'Phase2\Con_MaterialReceivingImportController', 'method' => 'import'],
    'con-material-receiving-import-validate' => ['controller' => 'Phase2\Con_MaterialReceivingImportController', 'method' => 'validateCsv'],
    'con-material-receiving-import-template' => ['controller' => 'Phase2\Con_MaterialReceivingImportController', 'method' => 'downloadTemplate'],

    // =========================================================================
    // Controller: Phase2\Con_MaterialReturnController
    // =========================================================================
    'con-material-returns' => ['controller' => 'Phase2\Con_MaterialReturnController', 'method' => 'index'],
    'con-material-returns/update/(\d+)' => ['controller' => 'Phase2\Con_MaterialReturnController', 'method' => 'updateStatus'],

    // =========================================================================
    // Controller: Phase2\Con_WastageController
    // =========================================================================
    'con-wastage' => ['controller' => 'Phase2\Con_WastageController', 'method' => 'index'],
    'con-wastage-create' => ['controller' => 'Phase2\Con_WastageController', 'method' => 'create'],
    'con-wastage-store' => ['controller' => 'Phase2\Con_WastageController', 'method' => 'store'],
    'con-wastage-show/(\d+)' => ['controller' => 'Phase2\Con_WastageController', 'method' => 'show'],
    'con-wastage-approve-qc/(\d+)' => ['controller' => 'Phase2\Con_WastageController', 'method' => 'approveQC'],
    'con-wastage-approve-purchase/(\d+)' => ['controller' => 'Phase2\Con_WastageController', 'method' => 'approvePurchase'],
    'con-wastage-receive-payment/(\d+)' => ['controller' => 'Phase2\Con_WastageController', 'method' => 'receivePayment'],

    // =========================================================================
    // Controller: Phase2\Con_VendorPaymentRequestController
    // =========================================================================
    'con-vendor-payment-requests' => ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'index'],
    'con-vendor-payment-create' => ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'create'],
    'con-vendor-payment-store' => ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'store'],
    'con-vendor-payment-show/(\d+)' => ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'show'],
    'con-vendor-payment-approve/(\d+)' => ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'approve'],
    'con-vendor-payment-reject/(\d+)' => ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'reject'],
    'con-vendor-payment-ajax-penalty' => ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'ajaxGetPenalty'],
    'con-vendor-payment-ajax-advance' => ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'ajaxGetAdvanceSummary'],
    'con-vendor-payment-payment/(\d+)' => ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'addPayment'],
    'con-vendor-payment-ajax-receivable' => ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'ajaxGetVendorReceivable'],
    'con-ajax-po-by-vendor' => ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'ajaxGetPOsByVendor'],
    'con-ajax-po-by-project' => ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'ajaxGetPOsByProject'],
    'con-vendor-payments' => ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'index'],
    'con-vendor-payment-document/(\d+)' => ['controller' => 'Phase2\Con_VendorPaymentRequestController', 'method' => 'addDocument'],

    // =========================================================================
    // Controller: Phase2\Con_BOQController
    // =========================================================================
    'con-boq' => ['controller' => 'Phase2\Con_BOQController', 'method' => 'index'],
    'con-boq-create' => ['controller' => 'Phase2\Con_BOQController', 'method' => 'create'],
    'con-boq-store' => ['controller' => 'Phase2\Con_BOQController', 'method' => 'store'],
    'con-boq-show/(\d+)' => ['controller' => 'Phase2\Con_BOQController', 'method' => 'show'],
    'con-boq-edit/(\d+)' => ['controller' => 'Phase2\Con_BOQController', 'method' => 'edit'],
    'con-boq-update/(\d+)' => ['controller' => 'Phase2\Con_BOQController', 'method' => 'update'],
    'con-boq-approve/(\d+)' => ['controller' => 'Phase2\Con_BOQController', 'method' => 'approve'],
    'con-boq-delete' => ['controller' => 'Phase2\Con_BOQController', 'method' => 'delete'],
    'con-ajax-boq-targets' => ['controller' => 'Phase2\Con_BOQController', 'method' => 'ajaxGetTargetsByProject'],
    'con-ajax-material-stock' => ['controller' => 'Phase2\Con_BOQController', 'method' => 'ajaxGetMaterialStock'],
    'con-ajax-check-boq-limits' => ['controller' => 'Phase2\Con_BOQController', 'method' => 'ajaxCheckBOQLimits'],

    // =========================================================================
    // Controller: Phase2\Con_WeeklyReportController
    // =========================================================================
    'con-weekly-reports' => ['controller' => 'Phase2\Con_WeeklyReportController', 'method' => 'index'],
    'con-weekly-report-create' => ['controller' => 'Phase2\Con_WeeklyReportController', 'method' => 'create'],
    'con-weekly-report-store' => ['controller' => 'Phase2\Con_WeeklyReportController', 'method' => 'store'],
    'con-weekly-report/(\d+)' => ['controller' => 'Phase2\Con_WeeklyReportController', 'method' => 'show'],
    'con-weekly-report-approve/(\d+)' => ['controller' => 'Phase2\Con_WeeklyReportController', 'method' => 'approve'],
    'con-ajax-work-plans' => ['controller' => 'Phase2\Con_WeeklyReportController', 'method' => 'ajaxWorkPlans'],
    'con-ajax-report-targets' => ['controller' => 'Phase2\Con_WeeklyReportController', 'method' => 'ajaxTargets'],
    'con-daily-reports' => ['controller' => 'Phase2\Con_WeeklyReportController', 'method' => 'index'],
    'con-daily-report-create' => ['controller' => 'Phase2\Con_WeeklyReportController', 'method' => 'create'],
    'con-daily-report/(\d+)' => ['controller' => 'Phase2\Con_WeeklyReportController', 'method' => 'show'],

    // =========================================================================
    // Controller: Phase2\Con_MaterialUsageController
    // =========================================================================
    'con-usage-logs' => ['controller' => 'Phase2\Con_MaterialUsageController', 'method' => 'index'],
    'con-usage-log-create' => ['controller' => 'Phase2\Con_MaterialUsageController', 'method' => 'create'],
    'con-usage-log-store' => ['controller' => 'Phase2\Con_MaterialUsageController', 'method' => 'store'],

    // =========================================================================
    // Controller: Phase2\Con_FinancialLedgerController
    // =========================================================================
    'con-ledger' => ['controller' => 'Phase2\Con_FinancialLedgerController', 'method' => 'index'],
    'con-ledger-project/(\d+)' => ['controller' => 'Phase2\Con_FinancialLedgerController', 'method' => 'byProject'],

    // =========================================================================
    // Controller: Phase2\Con_PreConstructionController
    // =========================================================================
    'con-pre-construction' => ['controller' => 'Phase2\Con_PreConstructionController', 'method' => 'index'],
    'con-land-study-store' => ['controller' => 'Phase2\Con_PreConstructionController', 'method' => 'storeLandStudy'],
    'con-drawing-store' => ['controller' => 'Phase2\Con_PreConstructionController', 'method' => 'storeDrawing'],
    'con-soil-test-store' => ['controller' => 'Phase2\Con_PreConstructionController', 'method' => 'storeSoilTest'],

    // =========================================================================
    // Controller: Phase2\Con_InitialEstimateController
    // =========================================================================
    'con-estimate-import' => ['controller' => 'Phase2\Con_InitialEstimateController', 'method' => 'importCsv'],
    'con-estimate-import-excel' => ['controller' => 'Phase2\Con_InitialEstimateController', 'method' => 'importExcel'],
    'con-estimates' => ['controller' => 'Phase2\Con_InitialEstimateController', 'method' => 'index'],
    'con-estimate-create' => ['controller' => 'Phase2\Con_InitialEstimateController', 'method' => 'create'],
    'con-estimate-store' => ['controller' => 'Phase2\Con_InitialEstimateController', 'method' => 'store'],
    'con-estimate-show/(\d+)' => ['controller' => 'Phase2\Con_InitialEstimateController', 'method' => 'show'],
    'con-estimate-edit/(\d+)' => ['controller' => 'Phase2\Con_InitialEstimateController', 'method' => 'edit'],
    'con-estimate-update/(\d+)' => ['controller' => 'Phase2\Con_InitialEstimateController', 'method' => 'update'],
    'con-estimate-approve/(\d+)' => ['controller' => 'Phase2\Con_InitialEstimateController', 'method' => 'approveBudget'],
    'con-estimate-cancel/(\d+)' => ['controller' => 'Phase2\Con_InitialEstimateController', 'method' => 'cancelBudget'],
    'con-estimate-print/(\d+)' => ['controller' => 'Phase2\Con_InitialEstimateController', 'method' => 'printView'],
    'con-ajax-arch-by-project' => ['controller' => 'Phase2\Con_InitialEstimateController', 'method' => 'ajaxArchByProject'],
    'con-ajax-designs-by-project' => ['controller' => 'Phase2\Con_InitialEstimateController', 'method' => 'ajaxDesignsByProject'],
    'con-estimate-ajax-add-material' => ['controller' => 'Phase2\Con_InitialEstimateController', 'method' => 'ajaxAddMaterial'],

    // =========================================================================
    // Controller: Phase2\Con_MaterialIssueController
    // =========================================================================
    'con-material-issues' => ['controller' => 'Phase2\Con_MaterialIssueController', 'method' => 'index'],
    'con-material-issue-create' => ['controller' => 'Phase2\Con_MaterialIssueController', 'method' => 'create'],
    'con-material-issue-store' => ['controller' => 'Phase2\Con_MaterialIssueController', 'method' => 'store'],
    'con-material-issue-csv-import' => ['controller' => 'Phase2\Con_MaterialIssueController', 'method' => 'importCsv'],
    'con-material-issue/(\d+)' => ['controller' => 'Phase2\Con_MaterialIssueController', 'method' => 'show'],

    // =========================================================================
    // Controller: Phase2\Con_WorkOrderController
    // =========================================================================
    'con-ajax-wo-drawings' => ['controller' => 'Phase2\Con_WorkOrderController', 'method' => 'ajaxGetDrawings'],
    'con-ajax-wo-check' => ['controller' => 'Phase2\Con_WorkOrderController', 'method' => 'ajaxCheckExistingWO'],
    'con-ajax-wo-work-types' => ['controller' => 'Phase2\Con_WorkOrderController', 'method' => 'ajaxGetWorkTypesByDesign'],
    'con-work-orders' => ['controller' => 'Phase2\Con_WorkOrderController', 'method' => 'index'],
    'con-work-order-create' => ['controller' => 'Phase2\Con_WorkOrderController', 'method' => 'create'],
    'con-work-order-store' => ['controller' => 'Phase2\Con_WorkOrderController', 'method' => 'store'],
    'con-work-order/(\d+)' => ['controller' => 'Phase2\Con_WorkOrderController', 'method' => 'show'],
    'con-work-order-edit/(\d+)' => ['controller' => 'Phase2\Con_WorkOrderController', 'method' => 'edit'],
    'con-work-order-update/(\d+)' => ['controller' => 'Phase2\Con_WorkOrderController', 'method' => 'update'],
    'con-work-order-approve/(\d+)' => ['controller' => 'Phase2\Con_WorkOrderController', 'method' => 'approve'],
    'con-work-order-reject/(\d+)' => ['controller' => 'Phase2\Con_WorkOrderController', 'method' => 'reject'],
    'con-work-order-complete/(\d+)' => ['controller' => 'Phase2\Con_WorkOrderController', 'method' => 'markComplete'],
    'con-work-order-qc-approve/(\d+)' => ['controller' => 'Phase2\Con_WorkOrderController', 'method' => 'qcApprove'],
    'con-work-order-delete' => ['controller' => 'Phase2\Con_WorkOrderController', 'method' => 'delete'],
    'con-work-order-print/(\d+)' => ['controller' => 'Phase2\Con_WorkOrderController', 'method' => 'printView'],
    'con-ajax-wo-by-project' => ['controller' => 'Phase2\Con_WorkOrderController', 'method' => 'ajaxGetByProject'],
    'con-ajax-wo-details' => ['controller' => 'Phase2\Con_WorkOrderController', 'method' => 'ajaxGetDetails'],
    'con-ajax-wo-work-plans' => ['controller' => 'Phase2\Con_WorkOrderController', 'method' => 'ajaxGetWorkPlans'],
    'con-ajax-wo-targets' => ['controller' => 'Phase2\Con_WorkOrderController', 'method' => 'ajaxGetTargets'],

    // =========================================================================
    // Controller: Phase2\Con_VendorPenaltyController
    // =========================================================================
    'con-vendor-penalties' => ['controller' => 'Phase2\Con_VendorPenaltyController', 'method' => 'index'],
    'con-vendor-penalty-create' => ['controller' => 'Phase2\Con_VendorPenaltyController', 'method' => 'create'],
    'con-vendor-penalty-store' => ['controller' => 'Phase2\Con_VendorPenaltyController', 'method' => 'store'],
    'con-vendor-penalty-update-status' => ['controller' => 'Phase2\Con_VendorPenaltyController', 'method' => 'updateStatus'],
    'con-vendor-penalty-delete' => ['controller' => 'Phase2\Con_VendorPenaltyController', 'method' => 'delete'],

    // =========================================================================
    // Controller: Phase2\Con_FinancialTransactionController
    // =========================================================================
    'con-transactions' => ['controller' => 'Phase2\Con_FinancialTransactionController', 'method' => 'index'],
    'con-pending-payments' => ['controller' => 'Phase2\Con_FinancialTransactionController', 'method' => 'pendingList'],
    'con-process-payment/([^/]+)/(\d+)' => ['controller' => 'Phase2\Con_FinancialTransactionController', 'method' => 'process'],
    'con-payment-store' => ['controller' => 'Phase2\Con_FinancialTransactionController', 'method' => 'store'],
    'con-transaction/(\d+)' => ['controller' => 'Phase2\Con_FinancialTransactionController', 'method' => 'show'],

    // =========================================================================
    // Controller: Phase2\AssetManagementController
    // =========================================================================
    'office-assets' => ['controller' => 'Phase2\AssetManagementController', 'method' => 'index'],
    'office-assets/store' => ['controller' => 'Phase2\AssetManagementController', 'method' => 'store'],
    'office-assets/store-product-quick' => ['controller' => 'Phase2\AssetManagementController', 'method' => 'storeProductQuick'],
    'office-assets/store-category-quick' => ['controller' => 'Phase2\AssetManagementController', 'method' => 'storeCategoryQuick'],
    'office-assets/show/(\d+)' => ['controller' => 'Phase2\AssetManagementController', 'method' => 'show'],
    'office-assets/logs/(\d+)' => ['controller' => 'Phase2\AssetManagementController', 'method' => 'getLogs'],
    'office-assets/update-code/(\d+)' => ['controller' => 'Phase2\AssetManagementController', 'method' => 'updateAssetCode'],
    'office-assets/update/(\d+)' => ['controller' => 'Phase2\AssetManagementController', 'method' => 'update'],
    'office-assets/transfer/(\d+)' => ['controller' => 'Phase2\AssetManagementController', 'method' => 'transfer'],
    'office-assets/bulk-transfer' => ['controller' => 'Phase2\AssetManagementController', 'method' => 'bulkTransfer'],
    'office-assets/condition/(\d+)' => ['controller' => 'Phase2\AssetManagementController', 'method' => 'updateCondition'],
    'office-assets/dispose/(\d+)' => ['controller' => 'Phase2\AssetManagementController', 'method' => 'dispose'],
    'asset-products' => ['controller' => 'Phase2\AssetManagementController', 'method' => 'products'],
    'asset-products/store' => ['controller' => 'Phase2\AssetManagementController', 'method' => 'storeProduct'],
    'asset-products/update' => ['controller' => 'Phase2\AssetManagementController', 'method' => 'updateProduct'],
    'asset-products/delete' => ['controller' => 'Phase2\AssetManagementController', 'method' => 'deleteProduct'],
    'office-assets/repair-requisition/(\d+)' => ['controller' => 'Phase2\AssetManagementController', 'method' => 'storeRepairRequisition'],
    'asset-extra-purchases' => ['controller' => 'Phase2\AssetManagementController', 'method' => 'extraPurchases'],
    'asset-extra-purchases/store' => ['controller' => 'Phase2\AssetManagementController', 'method' => 'storeExtraPurchase'],
    'asset-extra-purchases/approve/(\d+)' => ['controller' => 'Phase2\AssetManagementController', 'method' => 'approveExtraPurchase'],
    'asset-extra-purchases/delete/(\d+)' => ['controller' => 'Phase2\AssetManagementController', 'method' => 'deleteExtraPurchase'],
    'asset-extra-purchases/purchased/(\d+)' => ['controller' => 'Phase2\AssetManagementController', 'method' => 'markPurchased'],
    'asset-extra-purchases/confirm/(\d+)' => ['controller' => 'Phase2\AssetManagementController', 'method' => 'confirmPurchased'],
    'asset-extra-purchases/category/store' => ['controller' => 'Phase2\AssetManagementController', 'method' => 'storeProcurementCategory'],
    'asset-extra-purchases/category/update' => ['controller' => 'Phase2\AssetManagementController', 'method' => 'updateProcurementCategory'],
    'asset-extra-purchases/category/delete' => ['controller' => 'Phase2\AssetManagementController', 'method' => 'deleteProcurementCategory'],

    // =========================================================================
    // Controller: Phase2\Con_ProgressReportController
    // =========================================================================
    'con-progress-report' => ['controller' => 'Phase2\Con_ProgressReportController', 'method' => 'index'],

    // =========================================================================
    // Controller: Phase2\Con_BudgetExtendController
    // =========================================================================
    'con-budget-extends' => ['controller' => 'Phase2\Con_BudgetExtendController', 'method' => 'index'],
    'con-budget-extend-create' => ['controller' => 'Phase2\Con_BudgetExtendController', 'method' => 'create'],
    'con-budget-extend-store' => ['controller' => 'Phase2\Con_BudgetExtendController', 'method' => 'store'],
    'con-budget-extend-approve/(\d+)' => ['controller' => 'Phase2\Con_BudgetExtendController', 'method' => 'approve'],
    'con-budget-extend-reject/(\d+)' => ['controller' => 'Phase2\Con_BudgetExtendController', 'method' => 'reject'],
];
