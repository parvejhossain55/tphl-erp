<?php

$userId = (int)($_SESSION['user']['id'] ?? 0);
$roleId = (int)($_SESSION['user']['role_id'] ?? 0);
$designationId = (int)($_SESSION['user']['designation_id'] ?? 0);

// Determine if the user is authorized to request Petty Cash (Site Engineer 12, desig 38, Admin Operation / Head 20, 32, Admin 1, 3)
$isSiteEngineer = ($roleId === 12 || $designationId === 38);
$canRequestPettyCash = ($isSiteEngineer || in_array($roleId, [1, 3, 20, 32], true));
?>

<style>
/* Transport Details Box (Adaptive Light & Dark Theme) */
.transport-details-box {
    background-color: #f8fafc;
    border: 1px solid #e2e8f0 !important;
}
.transport-purpose-box {
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    color: #1e293b;
}

/* Dark Mode Overrides */
html[data-theme-mode="dark"] .transport-details-box,
[data-theme-mode="dark"] .transport-details-box,
[data-bs-theme="dark"] .transport-details-box,
body.dark-theme .transport-details-box {
    background-color: #111c2e !important;
    border-color: rgba(255, 255, 255, 0.12) !important;
}

html[data-theme-mode="dark"] .transport-details-box .form-label,
html[data-theme-mode="dark"] .transport-details-box label,
html[data-theme-mode="dark"] .transport-details-box small,
[data-theme-mode="dark"] .transport-details-box .form-label,
[data-theme-mode="dark"] .transport-details-box label,
[data-theme-mode="dark"] .transport-details-box small,
[data-bs-theme="dark"] .transport-details-box .form-label,
[data-bs-theme="dark"] .transport-details-box label,
[data-bs-theme="dark"] .transport-details-box small {
    color: #cbd5e1 !important;
}

html[data-theme-mode="dark"] .transport-details-box .text-dark,
[data-theme-mode="dark"] .transport-details-box .text-dark,
[data-bs-theme="dark"] .transport-details-box .text-dark {
    color: #f1f5f9 !important;
}

html[data-theme-mode="dark"] .transport-details-box .border-bottom,
[data-theme-mode="dark"] .transport-details-box .border-bottom,
[data-bs-theme="dark"] .transport-details-box .border-bottom {
    border-color: rgba(255, 255, 255, 0.1) !important;
}

html[data-theme-mode="dark"] .transport-details-box .input-group-text,
[data-theme-mode="dark"] .transport-details-box .input-group-text,
[data-bs-theme="dark"] .transport-details-box .input-group-text {
    background-color: rgba(255, 255, 255, 0.05) !important;
    border-color: rgba(255, 255, 255, 0.14) !important;
    color: #94a3b8 !important;
}

html[data-theme-mode="dark"] .transport-details-box .form-control,
html[data-theme-mode="dark"] .transport-details-box .form-select,
[data-theme-mode="dark"] .transport-details-box .form-control,
[data-theme-mode="dark"] .transport-details-box .form-select,
[data-bs-theme="dark"] .transport-details-box .form-control,
[data-bs-theme="dark"] .transport-details-box .form-select {
    background-color: #111c2e !important;
    border-color: rgba(255, 255, 255, 0.14) !important;
    color: #f1f5f9 !important;
}

html[data-theme-mode="dark"] .transport-details-box .transport-purpose-box,
[data-theme-mode="dark"] .transport-details-box .transport-purpose-box,
[data-bs-theme="dark"] .transport-details-box .transport-purpose-box {
    background-color: rgba(255, 255, 255, 0.04) !important;
    border-color: rgba(255, 255, 255, 0.12) !important;
    color: #f1f5f9 !important;
}
</style>

    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb no-print">
            <h1 class="page-title fw-semibold fs-18 mb-0">Fund Requisitions</h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Fund Requisitions</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Alert messages -->
        <div class="row no-print">
            <div class="col-xl-12">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="no-print">
            <?php include __DIR__ . '/../../layouts/management_approval_notice.php'; ?>
        </div>

        <!-- Print-only header section -->
        <div class="print-header d-none text-center mb-4">
            <h2 class="mb-1">TPHL ERP</h2>
            <h4 class="text-muted">Fund Requisition Statement</h4>
            <p class="mb-0">Date: <?= date('d M Y, h:i A') ?></p>
            <p>Generated by: <?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'System User') ?></p>
            <hr>
        </div>

        <!-- Filter Card Wrapper (Matching flat-lists) -->
        <div class="card__wrapper mb-3 no-print">
            <form action="<?= $GLOBALS['base_url'] ?>/fund-requisitions" method="get">
                <input type="hidden" name="view_mode" value="<?= htmlspecialchars($viewMode ?? 'all') ?>">

                <!-- Row 1: Filter inputs -->
                <div class="row g-2 align-items-center mb-2">

                    <!-- Project Filter -->
                    <div class="col-xl-3 col-md-4 col-sm-6">
                        <select class="form-control form-control-sm select2" name="project_id">
                            <option value="">All Projects</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?= $project['id'] ?>"
                                    <?= ($selectedProject ?? '') == $project['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($project['name']) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div class="col-xl-2 col-md-3 col-sm-6">
                        <select class="form-control form-control-sm" name="status">
                            <option value="">All Statuses</option>
                            <option value="pending" <?= ($statusFilter ?? '') === 'pending' ? 'selected' : '' ?>>All Pending Review</option>
                            <option value="pending_dept" <?= ($statusFilter ?? '') === 'pending_dept' ? 'selected' : '' ?>>Pending Dept Head</option>
                            <option value="pending_qc" <?= ($statusFilter ?? '') === 'pending_qc' ? 'selected' : '' ?>>Pending QC Approval</option>
                            <option value="approved" <?= ($statusFilter ?? '') === 'approved' ? 'selected' : '' ?>>Management Approved</option>
                            <option value="paid" <?= ($statusFilter ?? '') === 'paid' ? 'selected' : '' ?>>Paid & Settled</option>
                            <option value="rejected" <?= ($statusFilter ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>

                    <!-- Type Filter -->
                    <div class="col-xl-2 col-md-3 col-sm-6">
                        <select class="form-control form-control-sm" name="requisition_type">
                            <option value="">All Types</option>
                            <option value="petty_cash" <?= ($selectedType ?? '') === 'petty_cash' ? 'selected' : '' ?>>Petty Cash</option>
                            <option value="general" <?= ($selectedType ?? '') === 'general' ? 'selected' : '' ?>>General / Other</option>
                        </select>
                    </div>

                    <!-- Search -->
                    <div class="col-xl-3 col-md-4 col-sm-6">
                        <input type="text" class="form-control form-control-sm" name="search" id="fundSearch"
                            placeholder="Search ID, Requester, Purpose..."
                            value="<?= htmlspecialchars($searchQuery ?? '') ?>">
                    </div>

                    <!-- Filter + Reset -->
                    <div class="col-xl-2 col-md-4 col-sm-6">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-secondary btn-sm flex-fill">
                                <i class="fa-solid fa-filter me-1"></i> Filter
                            </button>
                            <a href="<?= $GLOBALS['base_url'] ?>/fund-requisitions?view_mode=all" class="btn btn-success btn-sm flex-fill">
                                <i class="fa-solid fa-rotate-right me-1"></i> Reset
                            </a>
                        </div>
                    </div>

                </div>

                <!-- Row 2: Date Filters & Action Buttons -->
                <div class="row g-2 align-items-center mt-1">
                    <div class="col-xl-2 col-md-3 col-sm-6">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted fs-11">From</span>
                            <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($startDate ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-3 col-sm-6">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted fs-11">To</span>
                            <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($endDate ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-xl-8 col-md-6 col-sm-12 text-end">
                        <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createRequisitionModal">
                                <i class="fa-solid fa-plus me-1"></i> New Requisition
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
                                <i class="fa-solid fa-print me-1"></i> Print Statement
                            </button>
                        </div>
                    </div>
                </div>

            </form>
        </div>

        <?php
        $pcCount = 0;
        $genCount = 0;
        foreach ($requests as $r) {
            if (($r['requisition_type'] ?? '') === 'petty_cash') $pcCount++;
            if (($r['requisition_type'] ?? '') === 'general') $genCount++;
        }
        ?>

        <!-- Table Card Wrapper (Matching flat-lists) -->
        <div class="row">
            <div class="col-xxl-12">
                <div class="card__wrapper">
                    <?php
                    $viewMode = $viewMode ?? 'all';
                    $base = $GLOBALS['base_url'];
                    $canSeeTabs = in_array($roleId, [1, 3, 33, 34, 35, 20, 32, 13, 24, 25, 6, 30]) || !empty($deptHeadDeptIds);
                    // Build base query string without view_mode or status
                    $baseQs = http_build_query(array_filter([
                        'start_date' => $startDate ?? '',
                        'end_date' => $endDate ?? '',
                        'search' => $searchQuery ?? '',
                        'project_id' => $selectedProject ?? '',
                        'requisition_type' => $selectedType ?? '',
                    ]));
                    ?>
                    <?php if ($canSeeTabs): ?>
                    <div class="d-flex align-items-center gap-2 mb-3 no-print flex-wrap">
                        <a href="<?= $base ?>/fund-requisitions?view_mode=all<?= $baseQs ? '&' . $baseQs : '' ?>" 
                           class="btn btn-sm <?= ($viewMode === 'all' && empty($statusFilter)) ? 'btn-secondary fw-semibold' : 'btn-outline-secondary' ?>">
                            <i class="fa-solid fa-list me-1"></i> All Requisitions
                            <span class="badge <?= ($viewMode === 'all' && empty($statusFilter)) ? 'bg-white text-secondary' : 'bg-secondary text-white' ?> ms-1"><?= (int)($totalAllCount ?? count($requests)) ?></span>
                        </a>
                        <a href="<?= $base ?>/fund-requisitions?view_mode=my_pending<?= $baseQs ? '&' . $baseQs : '' ?>" 
                           class="btn btn-sm <?= ($viewMode === 'my_pending') ? 'btn-primary fw-semibold' : 'btn-outline-primary' ?>">
                            <i class="fa-solid fa-clock-rotate-left me-1"></i> My Pending Action
                            <span class="badge <?= ($viewMode === 'my_pending') ? 'bg-white text-primary' : 'bg-primary text-white' ?> ms-1"><?= (int)($pendingActionCount ?? 0) ?></span>
                        </a>
                        <a href="<?= $base ?>/fund-requisitions?view_mode=my_requests<?= $baseQs ? '&' . $baseQs : '' ?>" 
                           class="btn btn-sm <?= ($viewMode === 'my_requests') ? 'btn-info text-white fw-semibold' : 'btn-outline-info' ?>">
                            <i class="fa-solid fa-user me-1"></i> My Requests
                            <span class="badge <?= ($viewMode === 'my_requests') ? 'bg-white text-info' : 'bg-info text-white' ?> ms-1"><?= (int)($myRequestsCount ?? 0) ?></span>
                        </a>
                        <?php if (in_array($roleId, [1, 3, 6, 30, 20, 32])): ?>
                        <a href="<?= $base ?>/fund-requisitions?view_mode=all&status=approved<?= $baseQs ? '&' . $baseQs : '' ?>" 
                           class="btn btn-sm <?= (($statusFilter ?? '') === 'approved') ? 'btn-success fw-semibold' : 'btn-outline-success' ?>">
                            <i class="fa-solid fa-file-invoice-dollar me-1"></i> Payment Pending
                            <span class="badge <?= (($statusFilter ?? '') === 'approved') ? 'bg-white text-success' : 'bg-success text-white' ?> ms-1"><?= (int)($paymentPendingCount ?? 0) ?></span>
                        </a>
                        <?php endif; ?>
                        <a href="<?= $base ?>/fund-requisitions?view_mode=all&status=rejected<?= $baseQs ? '&' . $baseQs : '' ?>" 
                           class="btn btn-sm <?= (($statusFilter ?? '') === 'rejected') ? 'btn-danger fw-semibold' : 'btn-outline-danger' ?>">
                            <i class="fa-solid fa-ban me-1"></i> Rejected
                            <span class="badge <?= (($statusFilter ?? '') === 'rejected') ? 'bg-white text-danger' : 'bg-danger text-white' ?> ms-1"><?= (int)($rejectedCount ?? 0) ?></span>
                        </a>
                    </div>
                    <?php endif; ?>
                    

                    <!-- Quick Filter Tabs (Petty Cash / General) -->
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 pb-2 border-bottom no-print">
                        <ul class="nav nav-tabs nav-tabs-bordered mb-0" id="fundTabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active fw-semibold" id="tab-all" data-type="" type="button">
                                    All (<span id="count-all"><?= count($requests) ?></span>)
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link fw-semibold text-warning" id="tab-petty" data-type="Petty Cash" type="button">
                                    Petty Cash (<span id="count-petty"><?= $pcCount ?></span>)
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link fw-semibold text-info" id="tab-general" data-type="General" type="button">
                                    General / Other (<span id="count-general"><?= $genCount ?></span>)
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="table__wrapper table-responsive">
                        <table class="table mb-20 text-nowrap" id="fundRequisitionTable">
                            <thead>
                                <tr class="table__title">
                                    <th style="width:40px" class="text-center">#</th>
                                    <th>ID</th>
                                    <th>Date</th>
                                    <th>Requester & Dept</th>
                                    <th>Project</th>
                                    <th>Type / Category</th>
                                    <th class="text-end">Amount</th>
                                    <th>Purpose</th>
                                    <th>Receipt</th>
                                    <th>Status</th>
                                    <th class="text-center no-print" style="width:130px">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="table__body">
                             
                                    <?php foreach ($requests as $index => $req): ?>
                                        <?php renderRow($req, $userId, $roleId, $isSiteEngineer, $index + 1); ?>
                                    <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- ========================================================================= -->
<!-- MODAL: CREATE REQUISITION -->
<!-- ========================================================================= -->
<div class="modal fade" id="createRequisitionModal" tabindex="-1" aria-labelledby="createRequisitionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= $GLOBALS['base_url'] ?>/fund-requisitions/store" method="POST" enctype="multipart/form-data" id="requisitionForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createRequisitionModalLabel">New Fund Requisition</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php 
                    $oldFundReq = $_SESSION['old_fund_requisition'] ?? [];
                    unset($_SESSION['old_fund_requisition']);
                    $userDeptId = !empty($oldFundReq['department_id']) ? (int)$oldFundReq['department_id'] : ($_SESSION['user']['department_id'] ?? 0);
                    $oldReqType = $oldFundReq['requisition_type'] ?? 'general';
                    $oldCategory = $oldFundReq['category'] ?? 'Other Expense';
                    $oldAmount = $oldFundReq['amount'] ?? '';
                    $oldPurpose = $oldFundReq['purpose'] ?? '';
                    $oldLocation = $oldFundReq['location'] ?? '';
                    $oldPurposeToVisit = $oldFundReq['purpose_to_visit'] ?? '';
                    $oldModeOfTransport = $oldFundReq['mode_of_transport'] ?? '';
                    ?>

                    <?php if (!empty($coaList)): ?>
                    <?= renderCOADropdown($coaList, null, 'account_id', false, 'fundreq') ?>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="department_id" class="form-label">Department <span class="text-danger">*</span></label>
                        <select name="department_id" id="department_id" class="form-select" required>
                            <option value="">-- Select Department --</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>" <?= ($dept['id'] == $userDeptId) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="requisition_type" class="form-label">Requisition Type <span class="text-danger">*</span></label>
                        <select name="requisition_type" id="requisition_type" class="form-select" onchange="toggleRequisitionType()" required>
                            <option value="general" <?= ($oldReqType === 'general') ? 'selected' : '' ?>>General / Other Expenses</option>
                            <option value="petty_cash" <?= ($oldReqType === 'petty_cash') ? 'selected' : '' ?> <?= !$canRequestPettyCash ? 'disabled' : '' ?>>
                                Petty Cash <?= !$canRequestPettyCash ? '(Restricted)' : '' ?>
                            </option>
                        </select>
                        <?php if (!$canRequestPettyCash): ?>
                            <small class="text-muted d-block mt-1">
                                <i class="fa-solid fa-triangle-exclamation text-warning me-1"></i> Petty Cash is restricted to Site Engineers, Admin Operation, and Operation Heads.
                            </small>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3" id="categoryGroup" style="<?= ($oldReqType === 'petty_cash') ? 'display: none;' : '' ?>">
                        <label for="category" class="form-label">Expense Category <span class="text-danger">*</span></label>
                        <select name="category" id="category" class="form-select" onchange="toggleTransportFields()">
                            <option value="Transport" <?= ($oldCategory === 'Transport') ? 'selected' : '' ?>>Transport / Conveyance</option>
                            <option value="Food" <?= ($oldCategory === 'Food') ? 'selected' : '' ?>>Food / Entertainment</option>
                            <option value="Office Supplies" <?= ($oldCategory === 'Office Supplies') ? 'selected' : '' ?>>Office Supplies</option>
                            <option value="Other Expense" <?= ($oldCategory === 'Other Expense') ? 'selected' : '' ?>>Other Expense</option>
                        </select>
                    </div>

                    <!-- Dynamic Transport / Conveyance Fields (Location, Purpose to Visit, Mode of Transport) -->
                    <div class="mb-3" id="transportFieldsGroup" style="<?= ($oldReqType !== 'petty_cash' && $oldCategory === 'Transport') ? '' : 'display: none;' ?>">
                        <div class="p-3 rounded-3 transport-details-box">
                            <div class="d-flex align-items-center gap-2 mb-2 pb-1 border-bottom">
                                <i class="fa-solid fa-car-side text-primary"></i>
                                <span class="fw-bold fs-12 text-primary text-uppercase">Transport / Conveyance Details</span>
                            </div>
                            
                            <!-- 1. Location / Destination -->
                            <div class="mb-2">
                                <label for="location" class="form-label fs-12 mb-1 fw-semibold">
                                    Location / Destination <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fa-solid fa-location-dot text-danger"></i></span>
                                    <input type="text" name="location" id="location" class="form-control" placeholder="e.g. Uttara Project / Head Office / Client Site" value="<?= htmlspecialchars($oldLocation) ?>">
                                </div>
                            </div>

                            <!-- 2. Purpose to Visit -->
                            <div class="mb-2">
                                <label for="purpose_to_visit" class="form-label fs-12 mb-1 fw-semibold">
                                    Purpose to Visit <span class="text-danger">*</span>
                                </label>
                                <textarea name="purpose_to_visit" id="purpose_to_visit" class="form-control form-control-sm" rows="2" placeholder="Describe the purpose of visit (e.g. Site inspection, drawing handover, meeting)..."><?= htmlspecialchars($oldPurposeToVisit) ?></textarea>
                            </div>

                            <!-- 3. Mode of Transport -->
                            <div class="mb-0">
                                <label for="mode_of_transport" class="form-label fs-12 mb-1 fw-semibold">
                                    Mode of Transport <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fa-solid fa-van-shuttle text-info"></i></span>
                                    <select name="mode_of_transport" id="mode_of_transport" class="form-select">
                                        <option value="">-- Select Transport Mode --</option>
                                        <option value="Uber / Car" <?= ($oldModeOfTransport === 'Uber / Car') ? 'selected' : '' ?>>Uber / Car / Taxi</option>
                                        <option value="CNG" <?= ($oldModeOfTransport === 'CNG') ? 'selected' : '' ?>>CNG Auto-rickshaw</option>
                                        <option value="Rickshaw" <?= ($oldModeOfTransport === 'Rickshaw') ? 'selected' : '' ?>>Rickshaw</option>
                                        <option value="Bike / Pathao" <?= ($oldModeOfTransport === 'Bike / Pathao') ? 'selected' : '' ?>>Bike / Ride Sharing</option>
                                        <option value="Bus / Public" <?= ($oldModeOfTransport === 'Bus / Public') ? 'selected' : '' ?>>Bus / Public Transport</option>
                                        <option value="Train" <?= ($oldModeOfTransport === 'Train') ? 'selected' : '' ?>>Train</option>
                                        <option value="Air" <?= ($oldModeOfTransport === 'Air') ? 'selected' : '' ?>>Air Flight</option>
                                        <option value="Office Vehicle" <?= ($oldModeOfTransport === 'Office Vehicle') ? 'selected' : '' ?>>Company / Office Vehicle</option>
                                        <option value="Other" <?= ($oldModeOfTransport === 'Other') ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="amount" class="form-label">Requested Amount (BDT) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="amount" class="form-control" placeholder="0.00" step="0.01" min="0.01" value="<?= htmlspecialchars($oldAmount) ?>" required>
                        <small class="text-danger d-none" id="amountLimitWarning">
                            <i class="fa-solid fa-circle-exclamation me-1"></i> Petty Cash requests cannot exceed BDT 10,000.
                        </small>
                    </div>

                    <div class="mb-3">
                        <label for="purpose" class="form-label">Purpose / Description <span class="text-danger">*</span></label>
                        <textarea name="purpose" id="purpose" class="form-control" rows="3" placeholder="Describe the purpose of this request..." required><?= htmlspecialchars($oldPurpose) ?></textarea>
                    </div>

                    <div class="mb-3" id="receiptsUploadSection">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">Receipt / Invoice Attachments <small class="text-muted">(Optional, Max 4)</small></label>
                            <span class="badge bg-light text-secondary border fs-11" id="receiptCountBadge">1/4 Files</span>
                        </div>
                        <div id="receiptInputsContainer" class="d-flex flex-column gap-2">
                            <div class="input-group input-group-sm receipt-input-row">
                                <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-paperclip"></i></span>
                                <input type="file" name="receipts[]" class="form-control" accept="image/*,application/pdf">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" id="addMoreReceiptBtn" style="font-size: 11px;">
                                <i class="fa-solid fa-plus me-1"></i> Add More Image
                            </button>
                            <small class="text-muted fs-11">Max 4 files (Image or PDF, up to 5MB each)</small>
                        </div>
                    </div>



                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Submit Request</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: PROCESS REQUISITION PAYMENT (ACCOUNTS ACTION) -->
<!-- ========================================================================= -->
<div class="modal fade" id="payRequisitionModal" tabindex="-1" aria-labelledby="payRequisitionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= $GLOBALS['base_url'] ?>/fund-requisitions/pay" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="payRequisitionModalLabel">Process Fund Requisition (Accounts)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="payRequisitionId">
                    
                    <div class="mb-3">
                        <div class="bg-light p-3 rounded mb-3 border">
                            <p class="mb-1 text-muted fs-11 uppercase fw-semibold">Requester Details</p>
                            <h6 class="mb-2" id="payRequesterInfo">John Doe - Civil Engineering</h6>
                            
                            <div class="row">
                                <div class="col-6">
                                    <p class="mb-0 text-muted fs-11">Requisition Type</p>
                                    <span class="badge bg-secondary-transparent" id="payRequisitionType">Petty Cash</span>
                                </div>
                                <div class="col-6">
                                    <p class="mb-0 text-muted fs-11 text-end">Amount</p>
                                    <h5 class="mb-0 text-success text-end fw-bold" id="payAmountInfo">BDT 5,000.00</h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="payStatusSelect" class="form-label">Action <span class="text-danger">*</span></label>
                        <select name="status" id="payStatusSelect" class="form-select" onchange="togglePaymentMethodSelect()" required>
                            <option value="paid">Record Disbursement / Paid</option>
                            <option value="rejected">Reject Request</option>
                        </select>
                    </div>

                    <div class="mb-3" id="paymentMethodGroup">
                        <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" id="payment_method" class="form-select">
                            <option value="Cash" selected>Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Cheque">Cheque</option>
                            <option value="bKash">bKash</option>
                            <option value="Nagad">Nagad</option>
                        </select>
                    </div>

                    <div class="mb-3" id="paymentAttachmentGroup">
                        <label for="payment_attachment" class="form-label">Payment Receipt / Bank Slip (Optional)</label>
                        <input type="file" name="payment_attachment" id="payment_attachment" class="form-control" accept="image/*,application/pdf">
                    </div>

                    <div class="mb-3">
                        <label for="accounts_note" class="form-label">Remarks / Note</label>
                        <textarea name="accounts_note" id="accounts_note" class="form-control" rows="2" placeholder="Provide payment remarks..."></textarea>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success btn-sm">Process Payment</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: PROCESS REQUISITION APPROVAL (MANAGEMENT ACTION) -->
<!-- ========================================================================= -->
<div class="modal fade" id="approveRequisitionModal" tabindex="-1" aria-labelledby="approveRequisitionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= $GLOBALS['base_url'] ?>/fund-requisitions/approve" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="approveRequisitionModalLabel">Approve/Reject Requisition (Management)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="approveRequisitionId">
                    
                    <div class="mb-3">
                        <div class="bg-light p-3 rounded mb-3 border">
                            <p class="mb-1 text-muted fs-11 uppercase fw-semibold">Requester Details</p>
                            <h6 class="mb-2" id="approveRequesterInfo">John Doe - Civil Engineering</h6>
                            
                            <div class="row">
                                <div class="col-6">
                                    <p class="mb-0 text-muted fs-11">Requisition Type</p>
                                    <span class="badge bg-secondary-transparent" id="approveRequisitionType">Petty Cash</span>
                                </div>
                                <div class="col-6">
                                    <p class="mb-0 text-muted fs-11 text-end">Amount</p>
                                    <h5 class="mb-0 text-success text-end fw-bold" id="approveAmountInfo">BDT 5,000.00</h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="approveStatusSelect" class="form-label">Approval Action <span class="text-danger">*</span></label>
                        <select name="status" id="approveStatusSelect" class="form-select" required>
                            <option value="approved">Approve Requisition</option>
                            <option value="rejected">Reject Requisition</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="approve_note" class="form-label">Approval Remarks / Note</label>
                        <textarea name="approval_note" id="approve_note" class="form-control" rows="3" placeholder="Provide approval or rejection comments..."></textarea>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary btn-sm">Submit Approval</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: PROCESS REQUISITION APPROVAL (DEPARTMENT HEAD ACTION) -->
<!-- ========================================================================= -->
<div class="modal fade" id="deptApproveRequisitionModal" tabindex="-1" aria-labelledby="deptApproveRequisitionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= $GLOBALS['base_url'] ?>/fund-requisitions/approve-dept" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deptApproveRequisitionModalLabel">Department Head Approval</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="deptApproveRequisitionId">
                    
                    <div class="mb-3">
                        <div class="bg-light p-3 rounded mb-3 border">
                            <p class="mb-1 text-muted fs-11 uppercase fw-semibold">Requester Details</p>
                            <h6 class="mb-2" id="deptApproveRequesterInfo">John Doe - Civil Engineering</h6>
                            
                            <div class="row">
                                <div class="col-6">
                                    <p class="mb-0 text-muted fs-11">Requisition Type</p>
                                    <span class="badge bg-secondary-transparent" id="deptApproveRequisitionType">General</span>
                                </div>
                                <div class="col-6">
                                    <p class="mb-0 text-muted fs-11 text-end">Amount</p>
                                    <h5 class="mb-0 text-success text-end fw-bold" id="deptApproveAmountInfo">BDT 5,000.00</h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="deptApproveStatusSelect" class="form-label">Action <span class="text-danger">*</span></label>
                        <select name="status" id="deptApproveStatusSelect" class="form-select" required>
                            <option value="approved" selected>Approve (Send to Management)</option>
                            <option value="rejected">Reject Request</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="dept_approval_note" class="form-label">Remarks / Note</label>
                        <textarea name="approval_note" id="dept_approval_note" class="form-control" rows="2" placeholder="Provide remarks..."></textarea>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-warning btn-sm">Submit Decision</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: VIEW DETAILS & EXPENSES -->
<!-- ========================================================================= -->
<div class="modal fade" id="viewRequisitionModal" tabindex="-1" aria-labelledby="viewRequisitionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="viewRequisitionModalLabel">Requisition Details & Expense Tracker</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Requisition Info Block -->
                <div class="card custom-card mb-4 border">
                    <div class="card-header bg-light-transparent">
                        <div class="card-title fs-13 text-primary">REQ INFO</div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="text-muted fs-11 uppercase fw-semibold d-block">Requisition ID</label>
                                <span class="fw-semibold fs-13 text-dark" id="vReqId">REQ-000</span>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted fs-11 uppercase fw-semibold d-block">Requested By</label>
                                <span class="fw-semibold fs-13 text-dark" id="vRequester">John Doe</span>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted fs-11 uppercase fw-semibold d-block">Department & Project</label>
                                <span class="fw-semibold fs-13 text-dark" id="vDeptProject">Engineering / Project A</span>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted fs-11 uppercase fw-semibold d-block">Type / Category</label>
                                <span id="vTypeBadge">General</span>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted fs-11 uppercase fw-semibold d-block">Amount</label>
                                <span class="fw-bold fs-14 text-success" id="vAmount">BDT 0.00</span>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted fs-11 uppercase fw-semibold d-block">Attachments</label>
                                <div id="vReceiptLink">—</div>
                            </div>
                            <div class="col-md-12">
                                <label class="text-muted fs-11 uppercase fw-semibold d-block">Purpose</label>
                                <p class="mb-0 text-dark fs-12 text-wrap bg-light p-2 rounded" id="vPurpose">Purpose description goes here...</p>
                            </div>
                            <div class="col-md-12 d-none" id="vTransportDetailsSection">
                                <div class="p-3 rounded-3 transport-details-box">
                                    <div class="d-flex align-items-center gap-2 mb-2 pb-1 border-bottom">
                                        <i class="fa-solid fa-car-side text-primary"></i>
                                        <span class="fw-bold fs-12 text-primary text-uppercase">Transport / Conveyance Details</span>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <small class="text-muted fs-11 uppercase fw-semibold d-block"><i class="fa-solid fa-location-dot me-1 text-danger"></i>Location / Destination</small>
                                            <span class="fs-12 fw-semibold text-dark d-block mt-1" id="vTransportLocation">—</span>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted fs-11 uppercase fw-semibold d-block"><i class="fa-solid fa-van-shuttle me-1 text-info"></i>Mode of Transport</small>
                                            <span class="badge bg-info-subtle text-info border border-info-subtle fs-12 mt-1" id="vTransportMode">—</span>
                                        </div>
                                        <div class="col-12 mt-1">
                                            <small class="text-muted fs-11 uppercase fw-semibold d-block"><i class="fa-solid fa-route me-1 text-primary"></i>Purpose to Visit</small>
                                            <p class="mb-0 fs-12 p-2 rounded transport-purpose-box mt-1" id="vTransportPurpose">—</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Approval / Payment Timeline Block -->
                <div class="row mb-4">
                    <!-- Management Approval Section -->
                    <div class="col-md-6">
                        <div class="card custom-card border h-100 mb-0">
                            <div class="card-header bg-light-transparent">
                                <div class="card-title fs-13 text-info">Management Approval</div>
                            </div>
                            <div class="card-body">
                                <div id="vMgmtSection">
                                    <div class="mb-2">
                                        <span class="fs-11 text-muted">Status:</span>
                                        <span class="badge" id="vMgmtStatus">Pending</span>
                                    </div>
                                    <div class="mb-2 d-none" id="vMgmtApproverGroup">
                                        <span class="fs-11 text-muted">By:</span>
                                        <span class="fw-semibold fs-12 text-dark" id="vMgmtApprover">Admin</span>
                                        <span class="fs-10 text-muted ms-1" id="vMgmtTime">At time</span>
                                    </div>
                                    <div>
                                        <span class="fs-11 text-muted d-block">Remarks:</span>
                                        <p class="mb-0 text-dark fs-12 bg-light p-2 rounded" id="vMgmtNote">—</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Accounts Payment Section -->
                    <div class="col-md-6">
                        <div class="card custom-card border h-100 mb-0">
                            <div class="card-header bg-light-transparent">
                                <div class="card-title fs-13 text-success">Accounts Disbursement</div>
                            </div>
                            <div class="card-body">
                                <div id="vAcctsSection">
                                    <div class="mb-2">
                                        <span class="fs-11 text-muted">Status:</span>
                                        <span class="badge" id="vAcctsStatus">Pending</span>
                                    </div>
                                    <div class="mb-2 d-none" id="vAcctsPayGroup">
                                        <span class="fs-11 text-muted">By:</span>
                                        <span class="fw-semibold fs-12 text-dark" id="vAcctsPayer">Accounts</span>
                                        <span class="fs-10 text-muted ms-1" id="vAcctsTime">At time</span>
                                    </div>
                                    <div class="mb-2 d-none" id="vAcctsMethodGroup">
                                        <span class="fs-11 text-muted">Method:</span>
                                        <span class="fw-semibold fs-12 text-dark" id="vAcctsMethod">Cash</span>
                                    </div>
                                    <div class="mb-2 d-none" id="vAcctsAttachmentGroup">
                                        <span class="fs-11 text-muted">Payment Attachment:</span>
                                        <span id="vAcctsAttachment">—</span>
                                    </div>
                                    <div>
                                        <span class="fs-11 text-muted d-block">Remarks:</span>
                                        <p class="mb-0 text-dark fs-12 bg-light p-2 rounded" id="vAcctsNote">—</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Expenses Section -->
                <div id="vExpensesContainer" class="d-none">
                    <div class="card custom-card border mb-0">
                        <div class="card-header justify-content-between align-items-center bg-light-transparent">
                            <div class="card-title fs-13 ">Actual Expenses Logged</div>
                            <div class="bd-badge bg-primary fs-12 fw-semibold" id="vBalanceTracker">
                                Disbursed: BDT 0.00 | Spent: BDT 0.00 | Balance: BDT 0.00
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <!-- Expense Date Filter Form -->
                            <div class="p-3 border-bottom no-print">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-4 col-sm-6">
                                        <label for="v_exp_start_date" class="form-label fs-11 fw-semibold text-muted mb-1">Start Date</label>
                                        <input type="date" id="v_exp_start_date" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <label for="v_exp_end_date" class="form-label fs-11 fw-semibold text-muted mb-1">End Date</label>
                                        <input type="date" id="v_exp_end_date" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-4 col-sm-12 d-flex gap-1">
                                        <button type="button" class="btn btn-primary btn-sm w-100" id="btnFilterExpenses" onclick="filterModalExpenses()">
                                            <i class="fa-solid fa-filter me-1"></i> Filter
                                        </button>
                                        <button type="button" class="btn btn-light btn-sm px-2 border" id="btnResetExpenses" onclick="resetModalExpenses()">
                                            <i class="fa-solid fa-arrows-rotate"></i>
                                        </button>
                                    </div>
                                    <div class="col-12 d-none" id="vExpenseLimitAlert">
                                        <span class="badge bg-info fs-10 mt-1">
                                            <i class="fa-solid fa-circle-info me-1"></i> Showing the latest 10 expenses by default. Use the filters to view older logs.
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Expense List Table -->
                            <div class="table-responsive border-bottom">
                                <table class="table text-nowrap table-hover mb-0 fs-12">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Purpose</th>
                                            <th>Project</th>
                                            <th class="text-end">Amount</th>
                                            <th>Receipt</th>
                                            <th>By</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="vExpenseListBody">
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-3">No expenses logged yet.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Log New Expense Form -->
                            <div id="vLogExpenseFormContainer" class="p-3 bg-light-transparent border-top">
                                <h6 class="mb-3 text-dark fw-semibold fs-13">Log an Expense Details</h6>
                                <form action="<?= $GLOBALS['base_url'] ?>/fund-requisitions/store-expense" method="POST" enctype="multipart/form-data" id="logExpenseForm">
                                    <input type="hidden" name="fund_requisition_id" id="vLogExpenseReqId">
                                    <div class="row ">
                                        <div class="col-md-6">
                                            <label for="exp_amount" class="form-label fs-11 fw-semibold text-muted mb-1">Spent Amount (BDT) <span class="text-danger">*</span></label>
                                            <input type="number" name="amount" id="exp_amount" class="form-control form-control-sm" placeholder="0.00" step="0.01" required>
                                            <small class="text-danger d-none" id="exp_balance_warning"></small>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="exp_attachment" class="form-label fs-11 fw-semibold text-muted mb-1">Receipt Attachment (Optional)</label>
                                            <input type="file" name="attachment" id="exp_attachment" class="form-control form-control-sm" accept="image/*,application/pdf">
                                        </div>
                                        
                                        <div class="col-md-12">
                                            <label class="form-label fs-11 fw-semibold text-muted d-block mb-1">Project / Expense Type</label>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="project_type" id="project_type_select" value="by_project" checked onchange="toggleExpenseProjectInput()">
                                                <label class="form-check-label fs-12 text-dark" for="project_type_select">System Project</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="project_type" id="project_type_other" value="other_project" onchange="toggleExpenseProjectInput()">
                                                <label class="form-check-label fs-12 text-dark" for="project_type_other">Others</label>
                                            </div>

                                            <div class="mt-2" id="expProjectSelectGroup">
                                                <select name="project_id" id="exp_project_id" class="form-select form-select-sm">
                                                    <option value="">-- Office General / No Project --</option>
                                                </select>
                                            </div>
                                            <div class="mt-2 d-none" id="expProjectOtherGroup">
                                                <input type="text" name="other_project_name" id="exp_other_project_name" class="form-control form-control-sm" placeholder="Specify other purpose/details...">
                                            </div>
                                        </div>

                                        <div class="col-md-12">
                                            <label for="exp_purpose" class="form-label fs-11 fw-semibold text-muted mb-1">Purpose / Description <span class="text-danger">*</span></label>
                                            <textarea name="purpose" id="exp_purpose" class="form-control form-control-sm" rows="2" placeholder="What was this money spent on?" required></textarea>
                                        </div>
                                    </div>
                                    <div class="mt-3 text-end">
                                        <button type="submit" class="btn btn-primary btn-sm px-4">
                                            <i class="fa-solid fa-save me-1"></i> Log Expense
                                        </button>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- ============================================================
                     SETTLEMENT SECTION (visible only when status = paid)
                     ============================================================ -->
                <div id="vSettlementContainer" class="d-none">
                    <div class="card custom-card border mb-0">
                        <div class="card-header justify-content-between align-items-center  bg-opacity-10">
                            <div class="card-title fs-13 text-success fw-bold">
                                <i class="bi bi-patch-check me-1"></i> Settlement & Accountability
                            </div>
                            <span class="badge" id="vSettleBadge">Pending</span>
                        </div>
                        <div class="card-body">
                            <!-- Summary Strip -->
                            <div class="row g-2 mb-3" id="vSettleSummary">
                                <div class="col-md-4">
                                    <div class="p-2 rounded  bg-opacity-10 text-center">
                                        <small class="text-light d-block fs-11">Disbursed</small>
                                        <strong class="text-success" id="vs_disbursed">৳ 0</strong>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-2 rounded bg-primary bg-opacity-10 text-center">
                                        <small class="text-light d-block fs-11">Total Spent</small>
                                        <strong class="text-light" id="vs_spent">৳ 0</strong>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-2 rounded bg-warning bg-opacity-10 text-center" id="vs_balance_card">
                                        <small class="text-lightd-block fs-11">Balance</small>
                                        <strong class="text-light" id="vs_balance">৳ 0</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Settlement Action Buttons -->
                            <div id="vSettleActions"></div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: VIEW PURPOSE DETAILS -->
<!-- ========================================================================= -->
<div class="modal fade" id="purposeModal" tabindex="-1" aria-labelledby="purposeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header ">
                <h5 class="modal-title fs-14 fw-bold" id="purposeModalLabel">
                    <i class="fa-solid fa-align-left text-primary me-2"></i>Requisition Purpose Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3 p-3  rounded border">
                    <small class="text-muted fs-11 text-uppercase fw-semibold d-block">Requester</small>
                    <span class="fw-bold text-dark fs-13" id="purposeRequesterName">---</span>
                </div>
                <div>
                    <label class="form-label fs-11 text-uppercase fw-semibold text-muted mb-1">Full Purpose Description</label>
                    <div class="p-3 bg-light-subtle rounded border fs-12 text-wrap" style="white-space: pre-wrap; max-height: 300px; overflow-y: auto;" id="purposeFullText"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- JAVASCRIPT LOGIC -->
<!-- ========================================================================= -->
<script>
function openPurposeModal(id, fullPurpose, requesterName) {
    document.getElementById('purposeRequesterName').innerText = requesterName;
    document.getElementById('purposeFullText').innerText = fullPurpose;
    var myModal = new bootstrap.Modal(document.getElementById('purposeModal'));
    myModal.show();
}

function toggleTransportFields() {
    var typeEl = document.getElementById('requisition_type');
    var catEl = document.getElementById('category');
    var tGroup = document.getElementById('transportFieldsGroup');
    if (!typeEl || !catEl || !tGroup) return;

    var isTransport = (typeEl.value === 'general' && catEl.value === 'Transport');
    tGroup.style.display = isTransport ? 'block' : 'none';
}

function toggleRequisitionType() {
    var type = document.getElementById('requisition_type').value;
    var categoryGroup = document.getElementById('categoryGroup');
    var amountInput = document.getElementById('amount');
    
    if (type === 'petty_cash') {
        categoryGroup.style.display = 'none';
        amountInput.setAttribute('max', '10000');
        var tGroup = document.getElementById('transportFieldsGroup');
        if (tGroup) tGroup.style.display = 'none';
    } else {
        categoryGroup.style.display = 'block';
        amountInput.removeAttribute('max');
        document.getElementById('amountLimitWarning').classList.add('d-none');
        toggleTransportFields();
    }
}

// Client-side validations
// Dynamic multiple receipts upload (Max 4)
document.addEventListener('DOMContentLoaded', function() {
    toggleTransportFields();

    var addBtn = document.getElementById('addMoreReceiptBtn');
    var container = document.getElementById('receiptInputsContainer');
    var badge = document.getElementById('receiptCountBadge');
    var maxFiles = 4;

    function updateReceiptUI() {
        if (!container || !badge || !addBtn) return;
        var rows = container.querySelectorAll('.receipt-input-row');
        var count = rows.length;
        badge.innerText = count + '/' + maxFiles + ' Files';
        if (count >= maxFiles) {
            addBtn.style.display = 'none';
        } else {
            addBtn.style.display = 'inline-block';
        }
    }

    if (addBtn && container) {
        addBtn.addEventListener('click', function() {
            var rows = container.querySelectorAll('.receipt-input-row');
            if (rows.length < maxFiles) {
                var newRow = document.createElement('div');
                newRow.className = 'input-group input-group-sm receipt-input-row';
                newRow.innerHTML = `
                    <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-paperclip"></i></span>
                    <input type="file" name="receipts[]" class="form-control" accept="image/*,application/pdf">
                    <button type="button" class="btn btn-outline-danger remove-receipt-row-btn" title="Remove this file">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                `;
                container.appendChild(newRow);
                updateReceiptUI();
            }
        });

        container.addEventListener('click', function(e) {
            var btn = e.target.closest('.remove-receipt-row-btn');
            if (btn) {
                var row = btn.closest('.receipt-input-row');
                if (row) {
                    row.remove();
                    updateReceiptUI();
                }
            }
        });
    }
});

document.getElementById('amount').addEventListener('input', function() {
    var type = document.getElementById('requisition_type').value;
    var amount = parseFloat(this.value);
    var warning = document.getElementById('amountLimitWarning');
    
    if (type === 'petty_cash' && amount > 10000) {
        warning.classList.remove('d-none');
    } else {
        warning.classList.add('d-none');
    }
});

document.getElementById('requisitionForm').addEventListener('submit', function(e) {
    var deptEl = document.getElementById('department_id');
    var dept = deptEl ? deptEl.value : '';
    var typeEl = document.getElementById('requisition_type');
    var type = typeEl ? typeEl.value : 'general';
    var catEl = document.getElementById('category');
    var cat = catEl ? catEl.value : '';
    var amountEl = document.getElementById('amount');
    var amount = parseFloat(amountEl ? amountEl.value : 0);
    var purposeEl = document.getElementById('purpose');
    var purpose = purposeEl ? purposeEl.value.trim() : '';
    
    if (!dept) {
        e.preventDefault();
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'warning',
                title: 'Please select a Department.',
                showConfirmButton: false,
                timer: 3500
            });
        } else {
            alert('Please select a Department.');
        }
        if (deptEl) deptEl.focus();
        return false;
    }

    if (type === 'general' && cat === 'Transport') {
        var locEl = document.getElementById('location');
        var visitEl = document.getElementById('purpose_to_visit');
        var modeEl = document.getElementById('mode_of_transport');
        
        var locVal = locEl ? locEl.value.trim() : '';
        var visitVal = visitEl ? visitEl.value.trim() : '';
        var modeVal = modeEl ? modeEl.value.trim() : '';

        if (!locVal) {
            e.preventDefault();
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'warning',
                    title: 'Please specify Location / Destination for Transport requisition.',
                    showConfirmButton: false,
                    timer: 3500
                });
            } else {
                alert('Please specify Location / Destination for Transport requisition.');
            }
            if (locEl) locEl.focus();
            return false;
        }

        if (!visitVal) {
            e.preventDefault();
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'warning',
                    title: 'Please describe Purpose to Visit for Transport requisition.',
                    showConfirmButton: false,
                    timer: 3500
                });
            } else {
                alert('Please describe Purpose to Visit for Transport requisition.');
            }
            if (visitEl) visitEl.focus();
            return false;
        }

        if (!modeVal) {
            e.preventDefault();
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'warning',
                    title: 'Please select Mode of Transport.',
                    showConfirmButton: false,
                    timer: 3500
                });
            } else {
                alert('Please select Mode of Transport.');
            }
            if (modeEl) modeEl.focus();
            return false;
        }
    }

    if (isNaN(amount) || amount <= 0) {
        e.preventDefault();
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'warning',
                title: 'Please enter a valid requested Amount greater than 0.',
                showConfirmButton: false,
                timer: 3500
            });
        } else {
            alert('Please enter a valid requested Amount greater than 0.');
        }
        if (amountEl) amountEl.focus();
        return false;
    }

    if (type === 'petty_cash' && amount > 10000) {
        e.preventDefault();
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'warning',
                title: 'Petty Cash requests cannot exceed BDT 10,000.',
                showConfirmButton: false,
                timer: 3500
            });
        } else {
            alert('Petty Cash requests cannot exceed BDT 10,000.');
        }
        if (amountEl) amountEl.focus();
        return false;
    }

    if (!purpose) {
        e.preventDefault();
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'warning',
                title: 'Please provide the Purpose / Description.',
                showConfirmButton: false,
                timer: 3500
            });
        } else {
            alert('Please provide the Purpose / Description.');
        }
        if (purposeEl) purposeEl.focus();
        return false;
    }
});

// Auto-open modal if redirected back with errors (?open_modal=1)
document.addEventListener('DOMContentLoaded', function() {
    if (window.location.search.includes('open_modal=1')) {
        var modalEl = document.getElementById('createRequisitionModal');
        if (modalEl && typeof bootstrap !== 'undefined') {
            var myModal = new bootstrap.Modal(modalEl);
            myModal.show();
        }
    }
});

function openDeptApproveModal(id, requesterName, deptName, type, amount) {
    document.getElementById('deptApproveRequisitionId').value = id;
    document.getElementById('deptApproveRequesterInfo').innerText = requesterName + ' - ' + deptName;
    document.getElementById('deptApproveAmountInfo').innerText = 'BDT ' + parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2});
    
    var typeLabel = type === 'petty_cash' ? 'Petty Cash' : 'General Requisition';
    var typeSpan = document.getElementById('deptApproveRequisitionType');
    typeSpan.innerText = typeLabel;
    
    if (type === 'petty_cash') {
        typeSpan.className = 'badge bg-warning';
    } else {
        typeSpan.className = 'badge bg-info';
    }
    
    document.getElementById('deptApproveStatusSelect').value = 'approved';
    document.getElementById('dept_approval_note').value = '';
    
    var myModal = new bootstrap.Modal(document.getElementById('deptApproveRequisitionModal'));
    myModal.show();
}

function openDeptRejectModal(id, requesterName, deptName, type, amount) {
    openDeptApproveModal(id, requesterName, deptName, type, amount);
    document.getElementById('deptApproveStatusSelect').value = 'rejected';
}

function openApproveModal(id, requesterName, deptName, type, amount) {
    document.getElementById('approveRequisitionId').value = id;
    document.getElementById('approveRequesterInfo').innerText = requesterName + ' - ' + deptName;
    document.getElementById('approveAmountInfo').innerText = 'BDT ' + parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2});
    
    var typeLabel = type === 'petty_cash' ? 'Petty Cash' : 'General Requisition';
    var typeSpan = document.getElementById('approveRequisitionType');
    typeSpan.innerText = typeLabel;
    
    if (type === 'petty_cash') {
        typeSpan.className = 'badge bg-warning';
    } else {
        typeSpan.className = 'badge bg-info';
    }
    
    document.getElementById('approveStatusSelect').value = 'approved';
    document.getElementById('approve_note').value = '';
    
    var myModal = new bootstrap.Modal(document.getElementById('approveRequisitionModal'));
    myModal.show();
}

function openPayModal(id, requesterName, deptName, type, amount) {
    document.getElementById('payRequisitionId').value = id;
    document.getElementById('payRequesterInfo').innerText = requesterName + ' - ' + deptName;
    document.getElementById('payAmountInfo').innerText = 'BDT ' + parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2});
    
    var typeLabel = type === 'petty_cash' ? 'Petty Cash' : 'General Requisition';
    var typeSpan = document.getElementById('payRequisitionType');
    typeSpan.innerText = typeLabel;
    
    if (type === 'petty_cash') {
        typeSpan.className = 'badge bg-warning-transparent';
    } else {
        typeSpan.className = 'badge bg-info-transparent';
    }
    
    document.getElementById('payStatusSelect').value = 'paid';
    document.getElementById('paymentMethodGroup').style.display = 'block';
    document.getElementById('paymentAttachmentGroup').style.display = 'block';
    document.getElementById('accounts_note').value = '';
    document.getElementById('payment_attachment').value = '';
    
    var myModal = new bootstrap.Modal(document.getElementById('payRequisitionModal'));
    myModal.show();
}

function togglePaymentMethodSelect() {
    var status = document.getElementById('payStatusSelect').value;
    var methodGroup = document.getElementById('paymentMethodGroup');
    var attachmentGroup = document.getElementById('paymentAttachmentGroup');
    if (status === 'rejected') {
        methodGroup.style.display = 'none';
        attachmentGroup.style.display = 'none';
    } else {
        methodGroup.style.display = 'block';
        attachmentGroup.style.display = 'block';
    }
}

function openViewModal(id) {
    document.getElementById('vReqId').innerText = 'REQ-' + id;
    document.getElementById('vRequester').innerText = 'Loading...';
    document.getElementById('vDeptProject').innerText = 'Loading...';
    document.getElementById('vAmount').innerText = 'BDT 0.00';
    document.getElementById('vPurpose').innerText = 'Loading...';
    document.getElementById('vReceiptLink').innerHTML = '—';
    document.getElementById('vLogExpenseReqId').value = id;
    
    // Clear modal date filter inputs
    document.getElementById('v_exp_start_date').value = '';
    document.getElementById('v_exp_end_date').value = '';
    document.getElementById('vExpenseLimitAlert').classList.add('d-none');
    
    // Clear Log Expense Form inputs
    document.getElementById('exp_amount').value = '';
    document.getElementById('exp_purpose').value = '';
    document.getElementById('exp_attachment').value = '';
    document.getElementById('exp_other_project_name').value = '';
    document.getElementById('project_type_select').checked = true;
    toggleExpenseProjectInput();
    
    document.getElementById('vMgmtStatus').innerText = 'Pending';
    document.getElementById('vMgmtStatus').className = 'badge bg-warning-transparent';
    document.getElementById('vMgmtApproverGroup').classList.add('d-none');
    document.getElementById('vMgmtNote').innerText = '—';
    
    document.getElementById('vAcctsStatus').innerText = 'Pending';
    document.getElementById('vAcctsStatus').className = 'badge bg-warning-transparent';
    document.getElementById('vAcctsPayGroup').classList.add('d-none');
    document.getElementById('vAcctsMethodGroup').classList.add('d-none');
    document.getElementById('vAcctsAttachmentGroup').classList.add('d-none');
    document.getElementById('vAcctsNote').innerText = '—';
    
    document.getElementById('vExpensesContainer').classList.add('d-none');
    document.getElementById('vSettlementContainer').classList.add('d-none');
    document.getElementById('vSettleActions').innerHTML = '';
    
    var viewModal = new bootstrap.Modal(document.getElementById('viewRequisitionModal'));
    viewModal.show();
    
    fetchModalExpensesData(id);
}

function fetchModalExpensesData(id, startDate = '', endDate = '') {
    var url = '<?= $GLOBALS['base_url'] ?>/fund-requisitions/show/' + id;
    if (startDate || endDate) {
        url += '?start_date=' + encodeURIComponent(startDate) + '&end_date=' + encodeURIComponent(endDate);
    }
    
    var listBody = document.getElementById('vExpenseListBody');
    listBody.innerHTML = '<tr><td colspan="7" class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> Loading expenses...</td></tr>';
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert(data.message || 'Failed to fetch details');
                return;
            }
            
            var req = data.requisition;
            var expenses = data.expenses;
            var projects = data.projects;
            
            document.getElementById('vRequester').innerText = req.requester_name || 'System User';
            document.getElementById('vDeptProject').innerText = (req.department_name || 'N/A') + ' / ' + (req.project_name || 'Office General');
            document.getElementById('vAmount').innerText = 'BDT ' + parseFloat(req.amount).toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('vPurpose').innerText = req.purpose;

            var tSection = document.getElementById('vTransportDetailsSection');
            if (req.location || req.purpose_to_visit || req.mode_of_transport || req.category === 'Transport') {
                if (tSection) {
                    tSection.classList.remove('d-none');
                    document.getElementById('vTransportLocation').innerText = req.location || '—';
                    document.getElementById('vTransportMode').innerText = req.mode_of_transport || '—';
                    document.getElementById('vTransportPurpose').innerText = req.purpose_to_visit || '—';
                }
            } else {
                if (tSection) tSection.classList.add('d-none');
            }
            
            if (req.receipt_path) {
                var receiptsList = [];
                try {
                    if (typeof req.receipt_path === 'string' && (req.receipt_path.startsWith('[') || req.receipt_path.startsWith('{'))) {
                        receiptsList = JSON.parse(req.receipt_path);
                    } else if (Array.isArray(req.receipt_path)) {
                        receiptsList = req.receipt_path;
                    } else {
                        receiptsList = [req.receipt_path];
                    }
                } catch(e) {
                    receiptsList = [req.receipt_path];
                }
                
                if (Array.isArray(receiptsList) && receiptsList.length > 0) {
                    var html = '<div class="d-flex flex-wrap gap-1">';
                    receiptsList.forEach(function(p, i) {
                        if (!p) return;
                        var fileUrl = p.startsWith('public/') ? '<?= $GLOBALS['base_url'] ?>/' + p : '<?= $GLOBALS['base_url'] ?>/public/' + p.replace(/^\/+/, '');
                        var isPdf = p.toLowerCase().endsWith('.pdf');
                        var icon = isPdf ? 'fa-file-pdf text-danger' : 'fa-paperclip text-primary';
                        html += `<a href="${fileUrl}" target="_blank" class="btn btn-sm btn-light border px-2 py-1 fs-11 d-inline-flex align-items-center gap-1 shadow-xs" title="View Attachment ${i + 1}">
                            <i class="fa-solid ${icon}"></i>
                            <span>Attachment #${i + 1}</span>
                            <i class="fa-solid fa-external-link fs-10 text-muted ms-1"></i>
                        </a>`;
                    });
                    html += '</div>';
                    document.getElementById('vReceiptLink').innerHTML = html;
                } else {
                    document.getElementById('vReceiptLink').innerHTML = '<span class="text-muted fs-11">No Receipt Attached</span>';
                }
            } else {
                document.getElementById('vReceiptLink').innerHTML = '<span class="text-muted fs-11">No Receipt Attached</span>';
            }
            
            var typeBadge = document.getElementById('vTypeBadge');
            if (req.requisition_type === 'petty_cash') {
                typeBadge.className = 'badge bg-warning fs-11';
                typeBadge.innerText = 'Petty Cash (' + (req.category || 'Petty Cash') + ')';
            } else {
                typeBadge.className = 'badge bg-info fs-11';
                typeBadge.innerText = 'General (' + (req.category || 'General') + ')';
            }
            
            var mgmtStatusBadge = document.getElementById('vMgmtStatus');
            if (req.status === 'approved' || req.status === 'paid') {
                mgmtStatusBadge.innerText = 'Approved';
                mgmtStatusBadge.className = 'badge bg-success-transparent fs-11 text-capitalize';
                document.getElementById('vMgmtApproverGroup').classList.remove('d-none');
                document.getElementById('vMgmtApprover').innerText = req.approved_by_name || 'Management';
                document.getElementById('vMgmtTime').innerText = req.approved_at ? 'at ' + req.approved_at : '';
                document.getElementById('vMgmtNote').innerText = req.approval_note || 'No remarks provided.';
            } else if (req.status === 'rejected' && req.approved_by !== null) {
                mgmtStatusBadge.innerText = 'Rejected';
                mgmtStatusBadge.className = 'badge bg-danger-transparent fs-11 text-capitalize';
                document.getElementById('vMgmtApproverGroup').classList.remove('d-none');
                document.getElementById('vMgmtApprover').innerText = req.approved_by_name || 'Management';
                document.getElementById('vMgmtTime').innerText = req.approved_at ? 'at ' + req.approved_at : '';
                document.getElementById('vMgmtNote').innerText = req.approval_note || 'No remarks provided.';
            } else {
                mgmtStatusBadge.innerText = 'Pending';
                mgmtStatusBadge.className = 'badge bg-warning-transparent fs-11 text-capitalize';
                document.getElementById('vMgmtNote').innerText = 'Awaiting Approval (Management or Operation)';
            }
            
            var acctsStatusBadge = document.getElementById('vAcctsStatus');
            if (req.status === 'paid') {
                document.getElementById('vExpensesContainer').classList.remove('d-none');
                
                acctsStatusBadge.innerText = 'Paid';
                acctsStatusBadge.className = 'badge bg-success-transparent fs-11 text-capitalize';
                document.getElementById('vAcctsPayGroup').classList.remove('d-none');
                document.getElementById('vAcctsPayer').innerText = req.paid_by_name || 'Accounts';
                document.getElementById('vAcctsTime').innerText = req.paid_at ? 'at ' + req.paid_at : '';
                
                document.getElementById('vAcctsMethodGroup').classList.remove('d-none');
                document.getElementById('vAcctsMethod').innerText = req.payment_method || 'Cash';
                
                document.getElementById('vAcctsNote').innerText = req.accounts_note || 'No remarks provided.';
                
                if (req.payment_attachment_path) {
                    document.getElementById('vAcctsAttachmentGroup').classList.remove('d-none');
                    document.getElementById('vAcctsAttachment').innerHTML = `
                        <a href="<?= $GLOBALS['base_url'] ?>/${req.payment_attachment_path}" target="_blank" class="btn btn-sm btn-success-light border px-2 py-1 fs-11">
                            <i class="fa-solid fa-receipt me-1"></i> View Payment Slip
                        </a>
                    `;
                } else {
                    document.getElementById('vAcctsAttachmentGroup').classList.add('d-none');
                }
                
                var projectSelect = document.getElementById('exp_project_id');
                projectSelect.innerHTML = '<option value="">-- Office General / No Project --</option>';
                projects.forEach(p => {
                    projectSelect.innerHTML += `<option value="${p.id}">${p.name}</option>`;
                });
                
                // Show or hide default limit alert badge
                var limitAlert = document.getElementById('vExpenseLimitAlert');
                if (data.is_default_limit) {
                    limitAlert.classList.remove('d-none');
                } else {
                    limitAlert.classList.add('d-none');
                }
                
                var totalAmount = (data.effective_approved_amount !== undefined && parseFloat(data.effective_approved_amount) > 0)
                    ? parseFloat(data.effective_approved_amount)
                    : ((parseFloat(req.approved_amount) > 0)
                        ? parseFloat(req.approved_amount)
                        : ((parseFloat(req.qc_approved_amount) > 0)
                            ? parseFloat(req.qc_approved_amount)
                            : parseFloat(req.amount)));
                var totalSpentGlobal = parseFloat(data.total_spent_global || 0);
                listBody.innerHTML = '';
                
                if (expenses.length === 0) {
                    listBody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-3">No expenses logged yet.</td></tr>';
                } else {
                    expenses.forEach(exp => {
                        var amount = parseFloat(exp.amount);
                        
                        var projName = exp.project_name ? exp.project_name : (exp.other_project_name ? exp.other_project_name : '<span class="text-muted fs-11">Office General</span>');
                        var attachmentBtn = exp.attachment_path ? `
                            <a href="<?= $GLOBALS['base_url'] ?>/${exp.attachment_path}" target="_blank" class="btn btn-icon btn-sm btn-light border py-0 px-2" title="View Bill/Receipt">
                                <i class="fa-solid fa-paperclip fs-11"></i>
                            </a>
                        ` : '—';
                        
                        var deleteBtn = '';
                        if (exp.created_by == data.session_user_id || [1, 3].includes(data.session_role_id)) {
                            deleteBtn = `
                                <form action="<?= $GLOBALS['base_url'] ?>/fund-requisitions/delete-expense" method="POST" class="d-inline" onsubmit="return confirm('Delete this expense log?')">
                                    <input type="hidden" name="id" value="${exp.id}">
                                    <button type="submit" class="btn btn-icon btn-sm btn-danger border py-0 px-2" title="Delete">
                                        <i class="fa-solid fa-trash-can fs-11"></i>
                                    </button>
                                </form>
                            `;
                        }
                        
                        var dateFormatted = new Date(exp.created_at).toLocaleDateString('en-GB', {day: 'numeric', month: 'short', year: 'numeric'});
                        
                        listBody.innerHTML += `
                            <tr>
                                <td>${dateFormatted}</td>
                                <td class="text-wrap">${escapeHtml(exp.purpose)}</td>
                                <td>${projName}</td>
                                <td class="text-end fw-semibold">BDT ${amount.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                                <td>${attachmentBtn}</td>
                                <td>${exp.creator_name || 'Staff'}</td>
                                <td>${deleteBtn}</td>
                            </tr>
                        `;
                    });
                }
                
                var remaining = totalAmount - totalSpentGlobal;
                document.getElementById('vBalanceTracker').innerHTML = `
                    Disbursed: BDT ${totalAmount.toLocaleString('en-US', {minimumFractionDigits: 2})} | 
                    Spent: BDT ${totalSpentGlobal.toLocaleString('en-US', {minimumFractionDigits: 2})} | 
                    Balance: BDT ${remaining.toLocaleString('en-US', {minimumFractionDigits: 2})}
                `;

                // ============================================================
                // SETTLEMENT SECTION
                // ============================================================
                document.getElementById('vSettlementContainer').classList.remove('d-none');

                var fmt = (n) => '৳ ' + parseFloat(n).toLocaleString('en-US', {minimumFractionDigits: 2});
                document.getElementById('vs_disbursed').innerText = fmt(totalAmount);
                document.getElementById('vs_spent').innerText = fmt(totalSpentGlobal);
                document.getElementById('vs_balance').innerText = fmt(Math.max(0, remaining));

                // Color balance card
                var bCard = document.getElementById('vs_balance_card');
                bCard.className = remaining > 0
                    ? 'p-2 rounded bg-warning bg-opacity-10 text-center'
                    : 'p-2 rounded bg-secondary bg-opacity-10 text-center';

                var settleBadge = document.getElementById('vSettleBadge');
                var settleStatus = req.settlement_status || 'pending';
                var settleLabel = {pending: 'Pending', settled: 'Settled', extra_requested: 'Extra Fund Requested'}[settleStatus] || settleStatus;
                var settleColor = {pending: 'bg-warning text-dark', settled: 'bg-success', extra_requested: 'bg-info text-dark'}[settleStatus] || 'bg-secondary';
                settleBadge.className = 'badge ' + settleColor;
                settleBadge.innerText = settleLabel;

                var isOwner    = (req.requested_by == data.session_user_id);
                var isAccounts = [1, 6].includes(data.session_role_id);
                var isAdmin    = [1, 3].includes(data.session_role_id);
                var isVerified = req.accounts_settlement_verified == 1;

                var actionsHtml = '';

                if (isVerified) {
                    actionsHtml += `<div class="alert alert-success py-2 mb-2 fs-12">
                        <i class="bi bi-check-circle-fill me-1"></i>
                        Settlement fully verified by Accounts.
                        ${req.accounts_verified_at ? '<small class="d-block text-muted">Verified on: ' + req.accounts_verified_at + '</small>' : ''}
                    </div>`;
                } else {
                    // 1. Requester: mark as settled (all expenses logged)
                    if ((isOwner || isAdmin) && settleStatus === 'pending') {
                        actionsHtml += `
                        <form action="<?= $GLOBALS['base_url'] ?>/fund-requisitions/settle/${req.id}" method="POST" class="d-inline me-1" onsubmit="return confirm('Mark this requisition as settled? All expenses should be logged before doing this.')">
                            <button type="submit" class="btn btn-sm btn-success fw-semibold">
                                <i class="bi bi-check2-circle me-1"></i> Mark as Settled
                            </button>
                        </form>`;
                    }

                    // 2. Requester: return balance (if balance > 0 and not yet returned)
                    if ((isOwner || isAdmin) && remaining > 0 && req.balance_returned == 0) {
                        actionsHtml += `
                        <form action="<?= $GLOBALS['base_url'] ?>/fund-requisitions/balance-return/${req.id}" method="POST" class="d-inline me-1" onsubmit="return confirm('Confirm that you have returned BDT ' + ${remaining.toFixed(2)} + ' to Accounts?')">
                            <button type="submit" class="btn btn-sm btn-outline-warning fw-semibold">
                                <i class="bi bi-arrow-return-left me-1"></i> Return Balance (${fmt(remaining)})
                            </button>
                        </form>`;
                    } else if (req.balance_returned == 1) {
                        actionsHtml += `<span class="badge bg-info text-dark me-2"><i class="bi bi-check me-1"></i>Balance Returned</span>`;
                    }

                    // 3. Requester: request extra fund (if overspent)
                    if ((isOwner || isAdmin) && remaining <= 0 && settleStatus !== 'extra_requested') {
                        actionsHtml += `
                        <button type="button" class="btn btn-sm btn-outline-danger fw-semibold me-1"
                            onclick="openExtraFundForm(${req.id}, ${Math.abs(remaining)})">
                            <i class="bi bi-plus-circle me-1"></i> Request Extra Fund
                        </button>`;
                    } else if (settleStatus === 'extra_requested') {
                        actionsHtml += `<span class="badge bg-danger me-2">Extra Fund Requested</span>`;
                    }

                    // 4. Accounts: verify settlement
                    if (isAccounts && (settleStatus === 'settled' || req.balance_returned == 1)) {
                        actionsHtml += `
                        <form action="<?= $GLOBALS['base_url'] ?>/fund-requisitions/verify-settle/${req.id}" method="POST" class="d-inline me-1" onsubmit="return confirm('Confirm settlement verification?')">
                            <button type="submit" class="btn btn-sm btn-primary fw-semibold">
                                <i class="bi bi-check2-all me-1"></i> Verify Settlement
                            </button>
                        </form>`;
                    }
                }

                // Extra fund form placeholder
                actionsHtml += `<div id="extraFundFormArea" class="mt-3 d-none">
                    <form action="<?= $GLOBALS['base_url'] ?>/fund-requisitions/extra-fund/" id="extraFundForm" method="POST">
                        <div class="row g-2">
                            <div class="col-md-5">
                                <label class="form-label fs-11 fw-semibold text-muted mb-1">Extra Amount Needed (৳)</label>
                                <input type="number" name="extra_amount" class="form-control form-control-sm" step="0.01" min="0.01" required
                                       placeholder="How much more is needed?">
                            </div>
                            <div class="col-md-7">
                                <label class="form-label fs-11 fw-semibold text-muted mb-1">Reason for Extra Fund</label>
                                <input type="text" name="extra_reason" class="form-control form-control-sm" required
                                       placeholder="Explain why extra funds are needed...">
                            </div>
                            <div class="col-12 text-end">
                                <button type="button" class="btn btn-light btn-sm me-1" onclick="document.getElementById('extraFundFormArea').classList.add('d-none')">Cancel</button>
                                <button type="submit" class="btn btn-danger btn-sm">Submit Extra Fund Request</button>
                            </div>
                        </div>
                    </form>
                </div>`;

                document.getElementById('vSettleActions').innerHTML = actionsHtml;
                
                var isOwnerOrAdmin = (req.requested_by == data.session_user_id || [1, 3].includes(data.session_role_id));
                var formContainer = document.getElementById('vLogExpenseFormContainer');
                if (isOwnerOrAdmin && remaining > 0) {
                    formContainer.classList.remove('d-none');
                    document.getElementById('exp_amount').setAttribute('max', remaining);
                    
                    document.getElementById('exp_amount').oninput = function() {
                        var val = parseFloat(this.value || 0);
                        var warning = document.getElementById('exp_balance_warning');
                        if (val > remaining) {
                            warning.innerText = 'Amount exceeds remaining balance of BDT ' + remaining.toLocaleString('en-US', {minimumFractionDigits: 2});
                            warning.classList.remove('d-none');
                        } else {
                            warning.classList.add('d-none');
                        }
                    };
                } else {
                    formContainer.classList.add('d-none');
                }
            } else {
                document.getElementById('vExpensesContainer').classList.add('d-none');
                
                if (req.status === 'rejected' && req.paid_by !== null) {
                    acctsStatusBadge.innerText = 'Rejected by Accounts';
                    acctsStatusBadge.className = 'badge bg-danger-transparent fs-11 text-capitalize';
                    document.getElementById('vAcctsPayGroup').classList.remove('d-none');
                    document.getElementById('vAcctsPayer').innerText = req.paid_by_name || 'Accounts';
                    document.getElementById('vAcctsTime').innerText = req.paid_at ? 'at ' + req.paid_at : '';
                    document.getElementById('vAcctsNote').innerText = req.accounts_note || 'No remarks provided.';
                } else {
                    acctsStatusBadge.innerText = 'Pending';
                    acctsStatusBadge.className = 'badge bg-warning-transparent fs-11 text-capitalize';
                    document.getElementById('vAcctsNote').innerText = 'Awaiting payment disbursement';
                }
            }
        })
        .catch(err => {
            console.error('Error fetching details:', err);
            alert('Error loading details.');
        });
}

function filterModalExpenses() {
    var id = document.getElementById('vLogExpenseReqId').value;
    var startDate = document.getElementById('v_exp_start_date').value;
    var endDate = document.getElementById('v_exp_end_date').value;
    fetchModalExpensesData(id, startDate, endDate);
}

function resetModalExpenses() {
    document.getElementById('v_exp_start_date').value = '';
    document.getElementById('v_exp_end_date').value = '';
    var id = document.getElementById('vLogExpenseReqId').value;
    fetchModalExpensesData(id);
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

function openExtraFundForm(reqId, overspentAmount) {
    var area = document.getElementById('extraFundFormArea');
    if (!area) return;
    area.classList.remove('d-none');
    // Update the form action with correct req id
    var form = document.getElementById('extraFundForm');
    if (form) {
        form.action = '<?= $GLOBALS["base_url"] ?>/fund-requisitions/extra-fund/' + reqId;
    }
    // Pre-fill extra amount with overspent value
    var amtInput = area.querySelector('input[name="extra_amount"]');
    if (amtInput && overspentAmount > 0) {
        amtInput.value = overspentAmount.toFixed(2);
    }
    area.scrollIntoView({behavior: 'smooth', block: 'nearest'});
}

function toggleExpenseProjectInput() {
    var selectRadio = document.getElementById('project_type_select');
    var selectGroup = document.getElementById('expProjectSelectGroup');
    var otherGroup = document.getElementById('expProjectOtherGroup');
    
    if (selectRadio.checked) {
        selectGroup.classList.remove('d-none');
        otherGroup.classList.add('d-none');
        document.getElementById('exp_other_project_name').removeAttribute('required');
    } else {
        selectGroup.classList.add('d-none');
        otherGroup.classList.remove('d-none');
        document.getElementById('exp_other_project_name').setAttribute('required', 'required');
    }
}

// Initial triggers
document.addEventListener("DOMContentLoaded", function() {
    toggleRequisitionType();

    // Custom DataTable for fund-requisitions (matching flat-lists table and pagination)
    if (typeof $.fn.DataTable !== 'undefined') {
        var fundTable = $('#fundRequisitionTable').DataTable({
            destroy: true,
            responsive: true,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            pageLength: 10,
            order: [], // Preserve SQL fr.id DESC order
            columnDefs: [
                { orderable: false, searchable: false, targets: 0 },  // # serial column
                { orderable: false, targets: 8 },                     // Receipt
                { orderable: false, searchable: false, targets: -1 }  // Actions
            ],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Quick filter in table...",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)",
                paginate: {
                    first: '<i class="fa-solid fa-angles-left"></i>',
                    previous: '<i class="fa-solid fa-angle-left"></i>',
                    next: '<i class="fa-solid fa-angle-right"></i>',
                    last: '<i class="fa-solid fa-angles-right"></i>'
                }
            },
            drawCallback: function(settings) {
                var api = this.api();
                var startIndex = api.context[0]._iDisplayStart;
                api.column(0, {search:'applied', order:'applied'}).nodes().each(function(cell, i) {
                    cell.innerHTML = startIndex + i + 1;
                });
            }
        });

        // Quick filter tabs (All, Petty Cash, General)
        $('#fundTabs button').on('click', function(e) {
            e.preventDefault();
            $('#fundTabs button').removeClass('active');
            $(this).addClass('active');

            var filterType = $(this).data('type') || '';
            if (filterType) {
                fundTable.column(5).search(filterType).draw();
            } else {
                fundTable.column(5).search('').draw();
            }
        });
    }
});
</script>

<!-- Edit Fund Requisition Modal (Before 1st Approval — List View) -->
<div class="modal custom-modal fade" id="editFundReqIndexModal" tabindex="-1" aria-labelledby="editFundReqIndexModalLabel" aria-hidden="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header pb-3 mb-2 border-bottom">
                <h5 class="modal-title fw-bold text-dark" id="editFundReqIndexModalLabel">
                    <i class="fa-solid fa-pen-to-square me-2 text-warning"></i> Edit Fund Requisition <span id="edit_fr_title_id"></span>
                </h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body p-0">
                <form method="POST" action="" id="editFundReqIndexForm">
                    <input type="hidden" name="redirect_to" value="index">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Amount (৳) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="1" name="amount" id="edit_fr_amount" class="form-control" required>
                    </div>
                    <div class="mb-3" id="edit_fr_category_wrap">
                        <label class="form-label fw-bold text-dark">Category</label>
                        <input type="text" name="category" id="edit_fr_category" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Purpose / Description <span class="text-danger">*</span></label>
                        <textarea name="purpose" id="edit_fr_purpose" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="row g-2 mb-3 d-none" id="edit_fr_transport_wrap">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Location / Destination</label>
                            <input type="text" name="location" id="edit_fr_location" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Mode of Transport</label>
                            <input type="text" name="mode_of_transport" id="edit_fr_transport" class="form-control">
                        </div>
                        <div class="col-12 mt-2">
                            <label class="form-label fw-bold text-dark">Purpose to Visit</label>
                            <input type="text" name="purpose_to_visit" id="edit_fr_visit" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer px-0 pb-0 pt-3 border-top d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">
                            <i class="fa-solid fa-save me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
(function() {
    function initFundIndexEditModal() {
        if (typeof jQuery === 'undefined' || typeof bootstrap === 'undefined') {
            setTimeout(initFundIndexEditModal, 50);
            return;
        }
        jQuery(function($) {
            if ($('#editFundReqIndexModal').length && $('#editFundReqIndexModal').parent().prop('tagName') !== 'BODY') {
                $('#editFundReqIndexModal').appendTo('body');
            }
            function populateFundEdit(btn) {
                var raw = btn.attr('data-item');
                if (!raw) return;
                try {
                    var d = (typeof raw === 'object') ? raw : JSON.parse(raw);
                    $('#editFundReqIndexForm').attr('action', '<?= $GLOBALS['base_url'] ?>/fund-requisitions/update/' + (d.id || ''));
                    $('#edit_fr_title_id').text('#' + (d.id || ''));
                    $('#edit_fr_amount').val(d.amount || '');
                    if (d.requisition_type === 'petty_cash') {
                        $('#edit_fr_amount').attr('max', '10000');
                        $('#edit_fr_category').val('Petty Cash');
                        $('#edit_fr_category_wrap').addClass('d-none');
                    } else {
                        $('#edit_fr_amount').removeAttr('max');
                        $('#edit_fr_category').val(d.category || 'Other Expense');
                        $('#edit_fr_category_wrap').removeClass('d-none');
                    }
                    $('#edit_fr_purpose').val(d.purpose || '');
                    if (d.category === 'Transport' || (d.location && d.location.length > 0)) {
                        $('#edit_fr_transport_wrap').removeClass('d-none');
                        $('#edit_fr_location').val(d.location || '');
                        $('#edit_fr_transport').val(d.mode_of_transport || '');
                        $('#edit_fr_visit').val(d.purpose_to_visit || '');
                    } else {
                        $('#edit_fr_transport_wrap').addClass('d-none');
                        $('#edit_fr_location').val('');
                        $('#edit_fr_transport').val('');
                        $('#edit_fr_visit').val('');
                    }
                } catch (e) {}
            }
            $('#editFundReqIndexModal').on('show.bs.modal', function(e) {
                if (e.relatedTarget) populateFundEdit($(e.relatedTarget));
            });
            $(document).on('click', '.btn-edit-fund-req', function() {
                populateFundEdit($(this));
            });
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFundIndexEditModal);
    } else {
        initFundIndexEditModal();
    }
})();
</script>


<!-- ========================================================================= -->
<!-- PRINT STYLING & HELPERS -->
<!-- ========================================================================= -->
<style>
@media print {
    /* Hide all non-printable layout elements completely */
    body * {
        visibility: hidden;
    }
    .print-header, .print-header * {
        visibility: visible;
    }
    .custom-card, .custom-card * {
        visibility: visible;
    }
    
    /* Position the print content correctly */
    .print-header {
        display: block !important;
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
    }
    
    .custom-card {
        position: absolute;
        top: 150px;
        left: 0;
        width: 100%;
        border: none !important;
        box-shadow: none !important;
    }
    
    /* Remove action headers and buttons for printing */
    .no-print {
        display: none !important;
        visibility: hidden !important;
    }
    
    /* Adjust table styles for clean print */
    .table {
        width: 100% !important;
        border-collapse: collapse !important;
    }
    .table th, .table td {
        border: 1px solid #ddd !important;
        padding: 8px !important;
        font-size: 11px !important;
    }
}
</style>

<?php

// Helper function to render a single table row
function renderRow($req, $userId, $roleId, $isSiteEngineer, $serial = 1) {
    $statusClass = 'bg-warning-transparent text-warning';
    $statusText = 'Pending';
    if ($req['status'] === 'pending_dept') {
        $statusClass = 'bg-warning-transparent text-dark border border-warning';
        $statusText = 'Pending Dept Head';
    } elseif ($req['status'] === 'pending_qc') {
        $statusClass = 'bg-warning-transparent text-dark border border-warning';
        $statusText = 'Pending QC Head';
    } elseif ($req['status'] === 'pending') {
        $statusClass = 'bg-info-transparent text-info border border-info';
        $statusText = 'Pending Management';
    } elseif ($req['status'] === 'approved') {
        $statusClass = 'bg-primary-transparent text-primary border border-primary';
        $statusText = 'Approved';
    } elseif ($req['status'] === 'partially_approved') {
        $statusClass = 'bg-primary-transparent text-primary border border-primary';
        $statusText = 'Partially Approved (BDT ' . number_format($req['approved_amount'] ?? 0, 2) . ')';
    } elseif ($req['status'] === 'paid') {
        $statusClass = 'bg-success-transparent text-success border border-success';
        $statusText = 'Paid';
    } elseif ($req['status'] === 'partially_paid') {
        $statusClass = 'bg-success-transparent text-success border border-success';
        $statusText = 'Partially Paid (BDT ' . number_format($req['paid_amount'] ?? 0, 2) . ')';
    } elseif ($req['status'] === 'rejected') {
        $statusClass = 'bg-danger text-white fw-bold shadow-sm';
        $statusText = 'Rejected';
    }
    
    $badgeType = ($req['requisition_type'] === 'petty_cash') ? 'badge bg-warning' : 'badge bg-info';
    $typeText = ($req['requisition_type'] === 'petty_cash') ? 'Petty Cash' : 'General';
    ?>
    <tr>
        <td class="text-center fw-bold text-muted"><?= $serial ?></td>
        <td>
            <a href="<?= $GLOBALS['base_url'] ?>/fund-requisitions/view/<?= $req['id'] ?>" class="fw-bold text-primary">
                REQ-<?= $req['id'] ?>
            </a>
        </td>
        <td><?= date('d M Y', strtotime($req['created_at'])) ?></td>
        <td>
            <div class="fw-semibold"><?= htmlspecialchars($req['requester_name'] ?? 'System User') ?></div>
            <?php if (!empty($req['requester_designation_name'])): ?>
                <div class="fs-11 text-secondary"><?= htmlspecialchars($req['requester_designation_name']) ?></div>
            <?php endif; ?>
            <span class="fs-10 text-muted"><?= htmlspecialchars($req['department_name'] ?? 'N/A') ?></span>
        </td>
        <td>
            <?= !empty($req['project_name']) ? htmlspecialchars($req['project_name']) : '<span class="text-muted fs-11">Office General</span>' ?>
            <?php if (!empty($req['asset_code'])): ?>
                <div class="mt-1">
                    <span class="badge bg-secondary-transparent text-dark border"><i class="fa-solid fa-cube me-1"></i> <?= htmlspecialchars($req['asset_code']) ?></span>
                    <div class="fs-11 text-muted mt-1"><?= htmlspecialchars($req['asset_name']) ?></div>
                </div>
            <?php endif; ?>
        </td>
        <td>
            <span class="<?= $badgeType ?> mb-1"><?= $typeText ?></span>
            <div class="fs-11 text-muted"><?= htmlspecialchars($req['category'] ?? 'Miscellaneous') ?></div>
            <?php if (!empty($req['location']) || !empty($req['mode_of_transport'])): ?>
                <div class="fs-10 text-primary mt-1" title="Destination: <?= htmlspecialchars($req['location'] ?? '') ?>">
                    <i class="fa-solid fa-location-dot me-1 text-danger"></i><?= htmlspecialchars($req['location'] ?? '') ?>
                    <?php if (!empty($req['mode_of_transport'])): ?>
                        <span class="text-muted">(<?= htmlspecialchars($req['mode_of_transport']) ?>)</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </td>
        <td class="text-end fw-bold text-dark">
            BDT <?= number_format($req['amount'], 2) ?>
            <?php if (isset($req['qc_approved_amount']) && (float)$req['qc_approved_amount'] > 0): ?>
                <div class="fs-11 text-info fw-normal">QC: BDT <?= number_format((float)$req['qc_approved_amount'], 2) ?></div>
            <?php endif; ?>
            <?php if (in_array($req['status'], ['approved', 'partially_approved', 'paid', 'partially_paid']) && (float)($req['approved_amount'] ?? 0) > 0): ?>
                <div class="fs-11 text-success fw-semibold">Mgmt Approved: BDT <?= number_format((float)$req['approved_amount'], 2) ?></div>
                <?php $frRemainingAmt = max(0, (float)$req['amount'] - (float)$req['approved_amount']); ?>
                <?php if ($frRemainingAmt > 0.009): ?>
                    <div class="fs-11 text-danger fw-normal">Remaining: BDT <?= number_format($frRemainingAmt, 2) ?></div>
                <?php endif; ?>
            <?php endif; ?>
        </td>
        <td class="text-wrap" style="min-width: 250px; max-width: 350px; word-break: break-word;">
            <?php 
            $purposeText = trim($req['purpose'] ?? '');
            $words = preg_split('/\s+/', $purposeText);
            if (count($words) > 20) {
                $shortPurpose = implode(' ', array_slice($words, 0, 20)) . '...';
                ?>
                <div class="fs-12"><?= htmlspecialchars($shortPurpose) ?></div>
                <button type="button" class="btn btn-link btn-sm p-0 fs-11 text-primary text-decoration-none fw-semibold d-inline-flex align-items-center gap-1 mt-1 text-nowrap"
                        onclick="openPurposeModal(<?= $req['id'] ?>, <?= htmlspecialchars(json_encode($purposeText), ENT_QUOTES, 'UTF-8') ?>, '<?= addslashes($req['requester_name'] ?? 'Requester') ?>')">
                    <i class="fa-solid fa-eye"></i> View Details
                </button>
                <?php
            } else {
                ?>
                <div class="fs-12"><?= htmlspecialchars($purposeText) ?></div>
                <?php
            }
            ?>
            <?php if (!empty($req['dept_head_approval_note'])): ?>
                <div class="fs-10 text-warning mt-1">
                    <strong>Dept Note:</strong> <?= htmlspecialchars($req['dept_head_approval_note']) ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($req['qc_approval_note'])): ?>
                <div class="fs-10 text-info mt-1">
                    <strong>QC Note:</strong> <?= htmlspecialchars($req['qc_approval_note']) ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($req['approval_note'])): ?>
                <div class="fs-10 text-info mt-1">
                    <strong>Mgr Note:</strong> <?= htmlspecialchars($req['approval_note']) ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($req['accounts_note'])): ?>
                <div class="fs-10 text-success mt-1">
                    <strong>Accts Note:</strong> <?= htmlspecialchars($req['accounts_note']) ?>
                </div>
            <?php endif; ?>
        </td>
        <td>
            <div class="d-flex gap-1 flex-wrap">
                <?php 
                if (!empty($req['receipt_path'])): 
                    $receiptList = [];
                    $decoded = json_decode($req['receipt_path'], true);
                    if (is_array($decoded)) {
                        $receiptList = $decoded;
                    } else {
                        $receiptList = [$req['receipt_path']];
                    }
                    foreach ($receiptList as $idx => $rPath):
                        if (empty($rPath)) continue;
                        $rUrl = (strpos($rPath, 'public/') === 0 ? $GLOBALS['base_url'] . '/' : $GLOBALS['base_url'] . '/public/') . ltrim($rPath, '/');
                        $isPdf = (strtolower(pathinfo($rPath, PATHINFO_EXTENSION)) === 'pdf');
                ?>
                    <a href="<?= htmlspecialchars($rUrl) ?>" target="_blank" class="btn btn-icon btn-sm btn-light border no-print" title="Receipt #<?= $idx + 1 ?>">
                        <i class="fa-solid <?= $isPdf ? 'fa-file-pdf text-danger' : 'fa-paperclip text-primary' ?>"></i>
                    </a>
                <?php 
                    endforeach;
                endif; 
                ?>
                <?php if (!empty($req['payment_attachment_path'])): ?>
                    <a href="<?= $GLOBALS['base_url'] . '/' . $req['payment_attachment_path'] ?>" target="_blank" class="btn btn-icon btn-sm btn-success-light border no-print" title="Payment Slip">
                        <i class="fa-solid fa-receipt text-success"></i>
                    </a>
                <?php endif; ?>
            </div>
        </td>
        <td>
            <span class="badge <?= $statusClass ?> fs-11">
                <?= $statusText ?>
            </span>
            <?php if ($req['status'] === 'pending_dept'): ?>
                <div class="fs-9 text-muted mt-1">
                    Pending: <?= htmlspecialchars(($req['reporting_boss_name'] ?? 'Dept Head') . (!empty($req['reporting_boss_designation_name']) ? ' (' . $req['reporting_boss_designation_name'] . ')' : '')) ?>
                </div>
            <?php elseif ($req['status'] === 'pending_qc'): ?>
                <div class="fs-9 text-muted mt-1">
                    Pending: QC &amp; Internal Audit Head
                </div>
                <?php if (($req['dept_head_status'] ?? '') === 'approved' && !empty($req['dept_head_approved_by_name'])): ?>
                    <div class="fs-9 text-success mt-1">
                        <i class="fa-solid fa-check-circle me-1"></i>Dept Approved: <?= htmlspecialchars($req['dept_head_approved_by_name'] . (!empty($req['dept_head_approved_by_designation_name']) ? ' (' . $req['dept_head_approved_by_designation_name'] . ')' : '')) ?>
                        <?php if (!empty($req['dept_head_approved_at'])): ?>
                            <div class="text-muted"><?= date('d M Y, h:i A', strtotime($req['dept_head_approved_at'])) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php elseif (in_array($req['status'], ['partially_approved', 'partially_paid'])): ?>
                <div class="fs-9 text-muted mt-1">
                    <?php if (!empty($req['approved_by_name'])): ?>
                        Partially approved by <?= htmlspecialchars($req['approved_by_name'] . (!empty($req['approved_by_designation_name']) ? ' (' . $req['approved_by_designation_name'] . ')' : '')) ?>: BDT <?= number_format((float)($req['approved_amount'] ?? 0), 2) ?><br>
                    <?php endif; ?>
                    Pending: Accounts Head (approved part) / Management (remaining)
                </div>
            <?php elseif ($req['status'] === 'pending'): ?>
                <div class="fs-9 text-muted mt-1">
                    Pending: Management or Operation
                </div>
            <?php elseif ($req['status'] === 'approved' && !empty($req['approved_by_name'])): ?>
                <div class="fs-9 text-muted mt-1">
                    Approved by <?= htmlspecialchars($req['approved_by_name'] . (!empty($req['approved_by_designation_name']) ? ' (' . $req['approved_by_designation_name'] . ')' : '')) ?>
                </div>
            <?php elseif ($req['status'] === 'paid' && !empty($req['paid_by_name'])): ?>
                <div class="fs-9 text-muted mt-1">
                    Paid via <?= htmlspecialchars($req['payment_method'] ?? 'Cash') ?><br>
                    By <?= htmlspecialchars($req['paid_by_name'] . (!empty($req['paid_by_designation_name']) ? ' (' . $req['paid_by_designation_name'] . ')' : '')) ?>
                </div>
            <?php elseif ($req['status'] === 'rejected'): ?>
                <div class="fs-9 text-danger mt-1 fw-semibold">
                    <i class="fa-solid fa-circle-xmark me-1"></i>Rejected
                    <?php if (!empty($req['approved_by_name'])): ?>
                        by <?= htmlspecialchars($req['approved_by_name']) ?>
                    <?php elseif (!empty($req['qc_approved_by_name'])): ?>
                        by QC (<?= htmlspecialchars($req['qc_approved_by_name']) ?>)
                    <?php elseif (!empty($req['dept_head_approved_by_name'])): ?>
                        by Dept (<?= htmlspecialchars($req['dept_head_approved_by_name']) ?>)
                    <?php endif; ?>
                </div>
                <?php 
                $rejectNote = !empty($req['approval_note']) ? $req['approval_note'] : (!empty($req['qc_approval_note']) ? $req['qc_approval_note'] : ($req['dept_head_approval_note'] ?? ''));
                if (!empty($rejectNote)): 
                ?>
                    <div class="fs-9 text-muted mt-1">
                        <strong>Reason:</strong> <?= htmlspecialchars($rejectNote) ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </td>
        <td class="no-print text-center text-nowrap">
            <div class="d-flex align-items-center justify-content-center gap-1 flex-nowrap">
                <!-- View Details button -->
                <a href="<?= $GLOBALS['base_url'] ?>/fund-requisitions/view/<?= $req['id'] ?>"
                   class="btn btn-icon btn-sm btn-info border" title="View Details & Log Expenses">
                    <i class="fa-solid fa-eye"></i>
                </a>

                <?php if ($req['status'] === 'paid'): ?>
                    <!-- View Journal Entry Button -->
                    <a href="<?= $GLOBALS['base_url'] ?>/general-journal"
                       class="btn btn-icon btn-sm btn-outline-success border no-print" title="View Journal Entry in Accounts">
                        <i class="fa-solid fa-book text-success"></i>
                    </a>
                <?php endif; ?>

                <?php 
                $canApproveDept = ($userId == ($req['requester_reporting_boss_id'] ?? 0) || (!empty($req['department_id']) && in_array((int)$req['department_id'], $deptHeadDeptIds ?? [], true)) || in_array($roleId, [1, 3, 33]));
                if ($req['status'] === 'pending_dept' && $canApproveDept): 
                ?>
                    <button type="button" class="btn btn-sm btn-warning d-inline-flex align-items-center gap-1 text-dark" 
                            onclick="openDeptApproveModal(<?= $req['id'] ?>, '<?= addslashes($req['requester_name']) ?>', '<?= addslashes($req['department_name']) ?>', '<?= $req['requisition_type'] ?>', <?= $req['amount'] ?>)">
                        <i class="fa-solid fa-stamp"></i> Dept Approve
                    </button>
                <?php endif; ?>

                <!-- QC Head Approval button -->
                <?php 
                $canApproveQc = in_array($roleId, [13, 24, 25, 1, 3]);
                if ($req['status'] === 'pending_qc' && $canApproveQc): 
                ?>
                    <button type="button" class="btn btn-sm btn-info d-inline-flex align-items-center gap-1 text-white" 
                            onclick="window.location.href='<?= $GLOBALS['base_url'] ?>/fund-requisitions/view/<?= $req['id'] ?>'">
                        <i class="fa-solid fa-stamp"></i> QC Approve
                    </button>
                <?php endif; ?>

                <?php 
                // Management/Operation Approval button
                // Role 1,3: any amount | Role 20,32: only <= 10000
                $canApproveInList = (in_array($roleId, [1, 3, 33])) ||
                                    (in_array($roleId, [20, 32]) && (float)($req['amount'] ?? 0) <= 10000);
                if (in_array($req['status'], ['pending', 'partially_approved', 'partially_paid']) && $canApproveInList): ?>
                    <button type="button" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1" 
                            onclick="window.location.href='<?= $GLOBALS['base_url'] ?>/fund-requisitions/view/<?= $req['id'] ?>'">
                        <i class="fa-solid fa-stamp"></i> <?= $req['status'] === 'pending' ? 'Approve' : 'Approve Remaining' ?>
                    </button>
                <?php endif; ?>

                <!-- Accounts Payment button (role 1, 6, 30 for approved requisitions) -->
                <?php if (in_array($req['status'], ['approved', 'partially_approved', 'partially_paid']) && in_array($roleId, [1, 6, 30])): ?>
                    <button type="button" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1" 
                            onclick="window.location.href='<?= $GLOBALS['base_url'] ?>/fund-requisitions/view/<?= $req['id'] ?>'">
                        <i class="fa-solid fa-file-invoice-dollar"></i> <?= $req['status'] === 'approved' ? 'Pay' : 'Pay Remaining' ?>
                    </button>
                <?php endif; ?>
                
                <?php 
                // Can edit/delete ONLY IF no approval (Dept Head, QC, Management, or Paid) has been granted yet
                $initialExpectedFrStatus = ((int)($req['is_dept_approval_required'] ?? 0) === 1) ? 'pending_dept' : 'pending_qc';
                $isApproved = (
                    ($req['status'] ?? '') !== $initialExpectedFrStatus ||
                    ($req['dept_head_status'] ?? '') === 'approved' ||
                    !empty($req['dept_head_approved_by']) ||
                    !empty($req['qc_approved_by']) ||
                    in_array($req['status'] ?? '', ['approved', 'partially_approved', 'paid', 'partially_paid', 'rejected'], true) ||
                    !empty($req['approved_by']) ||
                    !empty($req['paid_by'])
                );
                $canEditOrDelete = !$isApproved && ((int)($req['requested_by'] ?? 0) === (int)$userId || in_array((int)$roleId, [1, 3], true));
                if ($canEditOrDelete):
                    $fundEditPayload = [
                        'id'                => (int)$req['id'],
                        'requisition_type'  => $req['requisition_type'] ?? 'general',
                        'category'          => $req['category'] ?? 'Other Expense',
                        'amount'            => $req['amount'] ?? 0,
                        'purpose'           => $req['purpose'] ?? '',
                        'location'          => $req['location'] ?? '',
                        'purpose_to_visit'  => $req['purpose_to_visit'] ?? '',
                        'mode_of_transport' => $req['mode_of_transport'] ?? '',
                    ];
                ?>
                    <button type="button"
                            class="btn btn-icon btn-sm btn-warning border text-dark btn-edit-fund-req"
                            title="Edit Request"
                            data-bs-toggle="modal"
                            data-bs-target="#editFundReqIndexModal"
                            data-item="<?= htmlspecialchars(json_encode($fundEditPayload), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <form action="<?= $GLOBALS['base_url'] ?>/fund-requisitions/delete" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this requisition request?')">
                        <input type="hidden" name="id" value="<?= $req['id'] ?>">
                        <button type="submit" class="btn btn-icon btn-sm btn-danger border" title="Delete">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </td>
    </tr>
    <?php
}
?>
