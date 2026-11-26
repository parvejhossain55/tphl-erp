<?php
// TPHL-ERP - Vendor Financial Ledger & Dashboard View
// 100% English UI, Clean Professional Aesthetics
?>
<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="mb-1 fw-bold text-dark">
            <i class="fa-solid fa-book-bookmark text-primary me-2"></i><?= htmlspecialchars($page_title) ?>
        </h4>
        <nav>
            <ol class="breadcrumb mb-0" style="font-size: 13px;">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-vendors">Vendors</a></li>
                <?php if (!empty($selectedVendor)): ?>
                    <?php $selVendorDisplay = !empty(trim($selectedVendor['company_name'] ?? '')) ? trim($selectedVendor['company_name']) : $selectedVendor['name']; ?>
                    <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-vendor-ledger">Vendor Ledger</a></li>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($selVendorDisplay) ?></li>
                <?php else: ?>
                    <li class="breadcrumb-item active">Vendor Ledger &amp; Dashboard</li>
                <?php endif; ?>
            </ol>
        </nav>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php if (!empty($selectedVendor)): ?>
            <a href="<?= $base_url ?>/con-vendor-ledger" class="btn btn-outline-secondary btn-sm px-3">
                <i class="fa-solid fa-arrow-left me-1"></i> All Vendors Summary
            </a>
            <a href="<?= $base_url ?>/con-vendor-ledger-print/<?= $selectedVendor['id'] ?>?<?= http_build_query(array_filter(['project_id' => $selectedProjectId, 'start_date' => $startDate, 'end_date' => $endDate])) ?>" 
               target="_blank" class="btn btn-primary btn-sm px-3 shadow-sm">
                <i class="fa-solid fa-print me-1"></i> Print Statement
            </a>
            <a href="<?= $base_url ?>/con-vendor/<?= $selectedVendor['id'] ?>" class="btn btn-outline-primary btn-sm px-3">
                <i class="fa-solid fa-id-card me-1"></i> Vendor Profile
            </a>
        <?php else: ?>
            <a href="<?= $base_url ?>/con-vendor-ledger-print?<?= http_build_query(array_filter(['project_id' => $selectedProjectId, 'start_date' => $startDate, 'end_date' => $endDate, 'search' => $search])) ?>" 
               target="_blank" class="btn btn-outline-secondary btn-sm px-3">
                <i class="fa-solid fa-print me-1"></i> Print Summary
            </a>
            <a href="<?= $base_url ?>/con-vendors" class="btn btn-outline-primary btn-sm px-3">
                <i class="fa-solid fa-users-gear me-1"></i> Manage Vendors
            </a>
            <a href="<?= $base_url ?>/con-vendor-payment-requests" class="btn btn-primary btn-sm px-3 shadow-sm">
                <i class="bi bi-wallet2 me-1"></i> Payment Requests
            </a>
        <?php endif; ?>
    </div>
</div>

<section class="section">
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- 1. DYNAMIC KPI CARDS (Real-time Financial Snapshot)                -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="row  mb-4">
        <!-- Card 1: Total PO Value -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3" 
                 style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 4px solid #2563eb !important;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase fw-bold text-primary" style="font-size: 11.5px; letter-spacing: 0.5px;">
                        <?= $selectedVendor ? 'Vendor PO Total' : 'Total Vendor POs' ?>
                    </span>
                    <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center shadow-sm" style="width: 36px; height: 36px;">
                        <i class="fa-solid fa-file-invoice-dollar fs-6"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-1" id="kpiPoAmount">
                    ৳ <?= number_format($kpiMetrics['total_po_amount'] ?? 0, 2) ?>
                </h3>
                <div class="d-flex align-items-center text-muted" style="font-size: 12px;">
                    <i class="fa-solid fa-boxes-stacked me-1 text-primary"></i>
                    <span><strong><?= number_format($kpiMetrics['total_pos_count'] ?? 0) ?></strong> Purchase Orders</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Total Paid Amount -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3" 
                 style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-left: 4px solid #16a34a !important;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase fw-bold text-success" style="font-size: 11.5px; letter-spacing: 0.5px;">
                        Total Paid Disbursed
                    </span>
                    <div class="rounded-circle bg-white text-success d-flex align-items-center justify-content-center shadow-sm" style="width: 36px; height: 36px;">
                        <i class="fa-solid fa-circle-check fs-6"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-success mb-1" id="kpiPaidAmount">
                    ৳ <?= number_format($kpiMetrics['total_paid_amount'] ?? 0, 2) ?>
                </h3>
                <div class="d-flex align-items-center text-muted" style="font-size: 12px;">
                    <i class="fa-solid fa-money-bill-transfer me-1 text-success"></i>
                    <?php 
                    $poTotal = (float)($kpiMetrics['total_po_amount'] ?? 0);
                    $paidTotal = (float)($kpiMetrics['total_paid_amount'] ?? 0);
                    $paidPercent = $poTotal > 0 ? min(100, round(($paidTotal / $poTotal) * 100, 1)) : ($paidTotal > 0 ? 100 : 0);
                    ?>
                    <span><strong><?= $paidPercent ?>%</strong> Payment Cleared</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Total Outstanding Due -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3" 
                 style="background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); border-left: 4px solid #ea580c !important;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase fw-bold text-warning-emphasis" style="font-size: 11.5px; letter-spacing: 0.5px;">
                        Outstanding Due
                    </span>
                    <div class="rounded-circle bg-white text-danger d-flex align-items-center justify-content-center shadow-sm" style="width: 36px; height: 36px;">
                        <i class="fa-solid fa-hourglass-half fs-6"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-danger mb-1" id="kpiDueAmount">
                    ৳ <?= number_format($kpiMetrics['total_due_amount'] ?? 0, 2) ?>
                </h3>
                <div class="d-flex align-items-center text-muted" style="font-size: 12px;">
                    <i class="fa-solid fa-clock-rotate-left me-1 text-danger"></i>
                    <span>Net Payable to Vendors</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Vendor / Project Scope -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3" 
                 style="background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%); border-left: 4px solid #9333ea !important;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase fw-bold text-purple" style="font-size: 11.5px; letter-spacing: 0.5px; color: #7e22ce;">
                        <?= $selectedVendor ? 'Vendor Rating & Status' : 'Active Vendors' ?>
                    </span>
                    <div class="rounded-circle bg-white d-flex align-items-center justify-content-center shadow-sm" style="width: 36px; height: 36px; color: #7e22ce;">
                        <i class="fa-solid <?= $selectedVendor ? 'fa-star text-warning' : 'fa-building-shield' ?> fs-6"></i>
                    </div>
                </div>
                <?php if ($selectedVendor): ?>
                    <h3 class="fw-bold text-dark mb-1">
                        <?= number_format((float)($selectedVendor['rating'] ?? 5.0), 1) ?> <span class="fs-6 text-muted">/ 5.0</span>
                    </h3>
                    <div class="d-flex align-items-center text-muted" style="font-size: 12px;">
                        <span class="badge <?= $selectedVendor['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?> py-0 px-2">
                            <?= ucfirst($selectedVendor['status']) ?>
                        </span>
                        <span class="ms-2"><?= htmlspecialchars($selectedVendor['phone'] ?? '') ?></span>
                    </div>
                <?php else: ?>
                    <h3 class="fw-bold text-dark mb-1" id="kpiVendorCount">
                        <?= number_format($kpiMetrics['total_vendors'] ?? 0) ?>
                    </h3>
                    <div class="d-flex align-items-center text-muted" style="font-size: 12px;">
                        <i class="fa-solid fa-circle-check me-1 text-success"></i>
                        <span>Registered Vendors in ERP</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- 2. INTERACTIVE FILTER BAR                                          -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= $base_url ?>/con-vendor-ledger" id="vendorLedgerFilterForm" class="row g-2 align-items-end">
                <!-- Vendor Select -->
                <div class="col-lg-3 col-md-6">
                    <label class="form-label small fw-semibold text-muted mb-1">
                        <i class="fa-solid fa-user-tie text-primary me-1"></i>Select Vendor
                    </label>
                    <select name="vendor_id" id="filterVendorId" class="form-select form-select-sm select2" data-placeholder="All Vendors (<?= count($allVendors) ?>)">
                        <option value="">All Vendors (<?= count($allVendors) ?>)</option>
                        <?php foreach ($allVendors as $v): ?>
                            <option value="<?= $v['id'] ?>" <?= ($selectedVendorId == $v['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($v['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Project Select -->
                <div class="col-lg-3 col-md-6">
                    <label class="form-label small fw-semibold text-muted mb-1">
                        <i class="fa-solid fa-building-columns text-primary me-1"></i>Filter by Project
                    </label>
                    <select name="project_id" id="filterProjectId" class="form-select form-select-sm select2" data-placeholder="All Projects (<?= count($projects) ?>)">
                        <option value="">All Projects (<?= count($projects) ?>)</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= ($selectedProjectId == $p['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Quick Period Select -->
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1">
                        <i class="fa-regular fa-calendar-days text-primary me-1"></i>Date Period
                    </label>
                    <select name="period" id="filterPeriod" class="form-select form-select-sm" onchange="handlePeriodChange()">
                        <option value="all_time" <?= ($period === 'all_time') ? 'selected' : '' ?>>All Time</option>
                        <option value="this_month" <?= ($period === 'this_month') ? 'selected' : '' ?>>This Month</option>
                        <option value="last_month" <?= ($period === 'last_month') ? 'selected' : '' ?>>Last Month</option>
                        <option value="last_30_days" <?= ($period === 'last_30_days') ? 'selected' : '' ?>>Last 30 Days</option>
                        <option value="this_year" <?= ($period === 'this_year') ? 'selected' : '' ?>>This Year</option>
                        <option value="custom" <?= ($period === 'custom') ? 'selected' : '' ?>>Custom Range</option>
                    </select>
                </div>

                <!-- Date Range Inputs (Custom Period) -->
                <div class="col-lg-2 col-md-4" id="customDateRangeBox" style="<?= ($period === 'custom') ? 'display:block;' : 'display:none;' ?>">
                    <div class="row g-1">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-muted mb-1" style="font-size:11px;">From</label>
                            <input type="date" name="start_date" id="filterStartDate" class="form-control form-control-sm" value="<?= htmlspecialchars($startDate ?? '') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-muted mb-1" style="font-size:11px;">To</label>
                            <input type="date" name="end_date" id="filterEndDate" class="form-control form-control-sm" value="<?= htmlspecialchars($endDate ?? '') ?>">
                        </div>
                    </div>
                </div>

                <!-- Search Input -->
                <div class="col-lg-<?= ($period === 'custom') ? '2' : '2' ?> col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1">
                        <i class="fa-solid fa-magnifying-glass text-primary me-1"></i>Search Keyword
                    </label>
                    <input type="text" name="search" id="filterSearch" class="form-control form-control-sm" 
                           placeholder="Vendor, Phone, PO#..." value="<?= htmlspecialchars($search ?? '') ?>">
                </div>

                <!-- Filter Action Buttons -->
                <div class="col-lg-<?= ($period === 'custom') ? '12' : '2' ?> col-md-4 d-flex gap-1 justify-content-end">
                    <button type="submit" class="btn btn-primary btn-sm px-3 flex-grow-1 flex-lg-grow-0">
                        <i class="fa-solid fa-filter me-1"></i> Apply
                    </button>
                    <a href="<?= $base_url ?>/con-vendor-ledger" class="btn btn-light border btn-sm px-3" title="Reset Filters">
                        <i class="fa-solid fa-arrow-rotate-left"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($selectedVendor)): ?>
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- 3. SINGLE VENDOR DETAILED LEDGER & PROJECT BREAKDOWN               -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->

        <!-- Vendor Profile Header Banner -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
            <div class="card-body p-4 bg-white">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold shadow-sm"
                             style="width: 60px; height: 60px; font-size: 24px;">
                            <?= strtoupper(substr($selVendorDisplay, 0, 1)) ?>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($selVendorDisplay) ?></h4>
                                <span class="badge <?= $selectedVendor['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?> rounded-pill px-2">
                                    <?= ucfirst($selectedVendor['status']) ?>
                                </span>
                            </div>
                            <div class="d-flex align-items-center gap-3 text-muted mt-1 flex-wrap" style="font-size: 13px;">
                                <?php if (!empty(trim($selectedVendor['company_name'] ?? '')) && !empty($selectedVendor['name'])): ?>
                                    <span><i class="fa-solid fa-user me-1 text-primary"></i><?= htmlspecialchars($selectedVendor['name']) ?></span>
                                <?php endif; ?>
                                <span><i class="fa-solid fa-phone me-1 text-primary"></i><?= htmlspecialchars($selectedVendor['phone'] ?: 'N/A') ?></span>
                                <span><i class="fa-solid fa-envelope me-1 text-primary"></i><?= htmlspecialchars($selectedVendor['email'] ?: 'N/A') ?></span>
                                <span><i class="fa-solid fa-location-dot me-1 text-primary"></i><?= htmlspecialchars($selectedVendor['address'] ?: 'N/A') ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="small text-muted mb-1">Current Ledger Balance Due</div>
                        <h3 class="fw-bold <?= ($kpiMetrics['total_due_amount'] > 0) ? 'text-danger' : 'text-success' ?> mb-0">
                            ৳ <?= number_format($kpiMetrics['total_due_amount'] ?? 0, 2) ?>
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Project-Wise Paid & Due Breakdown Section -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="fa-solid fa-chart-pie text-primary me-2"></i>Project-Wise Financial Breakdown
                </h6>
                <span class="badge bg-light text-dark border" style="font-size: 11.5px;">
                    Supplied to <?= count($projectBreakdown) ?> Project(s)
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small text-uppercase" style="font-size: 11px;">
                            <tr>
                                <th class="ps-4">Project Name</th>
                                <th class="text-center">Purchase Orders</th>
                                <th class="text-end">Total PO Value (৳)</th>
                                <th class="text-end">Total Paid (৳)</th>
                                <th class="text-end">Outstanding Due (৳)</th>
                                <th style="width: 180px;">Payment Progress</th>
                                <th class="text-center pe-4">Last Payment</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($projectBreakdown)): ?>
                                <?php 
                                $subPoTotal = 0;
                                $subPaidTotal = 0;
                                $subDueTotal = 0;
                                ?>
                                <?php foreach ($projectBreakdown as $pb): ?>
                                    <?php 
                                    $subPoTotal += (float)$pb['total_po_amount'];
                                    $subPaidTotal += (float)$pb['paid_amount'];
                                    $subDueTotal += (float)$pb['due_amount'];
                                    $barClass = $pb['progress_percent'] >= 100 ? 'bg-success' : ($pb['progress_percent'] > 50 ? 'bg-primary' : 'bg-warning');
                                    ?>
                                    <tr>
                                        <td class="ps-4">
                                            <a href="<?= $base_url ?>/con-vendor-ledger?vendor_id=<?= $selectedVendor['id'] ?>&project_id=<?= $pb['project_id'] ?>" 
                                               class="fw-bold text-dark text-decoration-none">
                                                <i class="fa-solid fa-building me-1 text-primary"></i><?= htmlspecialchars($pb['project_name']) ?>
                                            </a>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2">
                                                <?= $pb['po_count'] ?> POs
                                            </span>
                                        </td>
                                        <td class="text-end fw-bold text-dark">
                                            ৳ <?= number_format($pb['total_po_amount'], 2) ?>
                                        </td>
                                        <td class="text-end fw-bold text-success">
                                            ৳ <?= number_format($pb['paid_amount'], 2) ?>
                                        </td>
                                        <td class="text-end fw-bold <?= ($pb['due_amount'] > 0) ? 'text-danger' : 'text-muted' ?>">
                                            ৳ <?= number_format($pb['due_amount'], 2) ?>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 7px; background-color: #e2e8f0; border-radius: 4px;">
                                                    <div class="progress-bar <?= $barClass ?>" role="progressbar" 
                                                         style="width: <?= min(100, $pb['progress_percent']) ?>%; border-radius: 4px;"></div>
                                                </div>
                                                <small class="text-muted fw-semibold" style="font-size: 11px; min-width: 38px;">
                                                    <?= $pb['progress_percent'] ?>%
                                                </small>
                                            </div>
                                        </td>
                                        <td class="text-center pe-4 small text-muted">
                                            <?= $pb['last_payment_date'] ? date('d M, Y', strtotime($pb['last_payment_date'])) : '—' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <!-- Summary Row -->
                                <tr class="table-light fw-bold">
                                    <td class="ps-4 text-uppercase">Total Across Projects</td>
                                    <td class="text-center"><?= array_sum(array_column($projectBreakdown, 'po_count')) ?> POs</td>
                                    <td class="text-end text-dark">৳ <?= number_format($subPoTotal, 2) ?></td>
                                    <td class="text-end text-success">৳ <?= number_format($subPaidTotal, 2) ?></td>
                                    <td class="text-end text-danger">৳ <?= number_format($subDueTotal, 2) ?></td>
                                    <td colspan="2"></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="fa-solid fa-inbox me-2"></i>No project orders found for this vendor matching the filters.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Chronological Running Balance Ledger Statement -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="fa-solid fa-list-check text-primary me-2"></i>Chronological Ledger Statement (Running Balance)
                    </h6>
                    <small class="text-muted">Standard audit trail of all Purchase Orders billed and Payment Vouchers disbursed</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                        <i class="fa-solid fa-print me-1"></i> Quick Print
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 custom-table">
                        <thead class="table-light text-muted small text-uppercase" style="font-size: 11px;">
                            <tr>
                                <th class="ps-4" style="width: 110px;">Date</th>
                                <th style="width: 130px;">Transaction Type</th>
                                <th style="width: 120px;">Ref / PO #</th>
                                <th>Project</th>
                                <th>Particulars &amp; Notes</th>
                                <th class="text-end text-nowrap">Billed / Debit (৳)</th>
                                <th class="text-end text-nowrap">Paid / Credit (৳)</th>
                                <th class="text-end text-nowrap" style="width: 150px;">Running Due (৳)</th>
                                <th class="text-center" style="width: 90px;">Status</th>
                                <th class="text-center pe-4" style="width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($ledgerEntries)): ?>
                                <?php 
                                $totalDebit = 0;
                                $totalCredit = 0;
                                ?>
                                <?php foreach ($ledgerEntries as $entry): ?>
                                    <?php 
                                    $entryType = $entry['entry_type'] ?? '';
                                    $isPo = ($entryType === 'PURCHASE_ORDER');
                                    $isOfficeProc = ($entryType === 'OFFICE_PROCUREMENT');
                                    $isOfficePay = ($entryType === 'OFFICE_PAYMENT');
                                    $totalDebit += (float)$entry['debit_amount'];
                                    $totalCredit += (float)$entry['credit_amount'];
                                    ?>
                                    <tr>
                                        <td class="ps-4 small text-muted text-nowrap">
                                            <i class="fa-regular fa-calendar me-1 text-primary"></i>
                                            <?= $entry['tx_date'] ? date('d M, Y', strtotime($entry['tx_date'])) : '—' ?>
                                        </td>
                                        <td>
                                            <?php if ($isPo): ?>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" style="font-size: 11px;">
                                                    <i class="fa-solid fa-file-invoice me-1"></i> Purchase Order
                                                </span>
                                            <?php elseif ($isOfficeProc): ?>
                                                <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1" style="font-size: 11px;">
                                                    <i class="fa-solid fa-boxes-stacked me-1"></i> Office Procurement
                                                </span>
                                            <?php elseif ($isOfficePay): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 11px;">
                                                    <i class="fa-solid fa-money-bill-wave me-1"></i> Office Payment
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 11px;">
                                                    <i class="fa-solid fa-receipt me-1"></i> Payment Paid
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-bold">
                                            <?php if ($isPo): ?>
                                                <a href="<?= $base_url ?>/con-purchase-order/<?= $entry['ref_id'] ?>" 
                                                   class="text-primary text-decoration-none" title="View Purchase Order">
                                                    <?= htmlspecialchars($entry['ref_no']) ?>
                                                </a>
                                            <?php elseif ($isOfficeProc || $isOfficePay): ?>
                                                <a href="<?= $base_url ?>/asset-extra-purchase/<?= $entry['ref_id'] ?>" 
                                                   class="text-info text-decoration-none" title="View Office Procurement">
                                                    <?= htmlspecialchars($entry['ref_no']) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-dark font-monospace" style="font-size: 12px;">
                                                    <?= htmlspecialchars($entry['ref_no']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border" style="font-size: 11px;">
                                                <?= htmlspecialchars($entry['project_name']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 280px;" title="<?= htmlspecialchars($entry['description']) ?>">
                                                <?= htmlspecialchars($entry['description']) ?>
                                            </div>
                                        </td>
                                        <!-- Debit / Invoiced -->
                                        <td class="text-end fw-bold text-dark text-nowrap">
                                            <?= ($entry['debit_amount'] > 0) ? '৳ ' . number_format($entry['debit_amount'], 2) : '—' ?>
                                        </td>
                                        <!-- Credit / Paid -->
                                        <td class="text-end fw-bold text-success text-nowrap">
                                            <?= ($entry['credit_amount'] > 0) ? '৳ ' . number_format($entry['credit_amount'], 2) : '—' ?>
                                        </td>
                                        <!-- Running Balance -->
                                        <td class="text-end fw-bold text-nowrap <?= ($entry['running_balance'] > 0) ? 'text-danger' : 'text-success' ?>" 
                                            style="background-color: <?= ($entry['running_balance'] > 0) ? '#fef2f2' : '#f0fdf4' ?>;">
                                            ৳ <?= number_format($entry['running_balance'], 2) ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge <?= ($entry['status_badge'] === 'Paid' || $entry['status_badge'] === 'Disbursed' || $entry['status_badge'] === 'Approved') ? 'bg-success' : 'bg-secondary' ?>" style="font-size: 10px;">
                                                <?= htmlspecialchars(ucfirst($entry['status_badge'])) ?>
                                            </span>
                                        </td>
                                        <td class="text-center pe-4">
                                            <a href="<?= $base_url . $entry['action_url'] ?>" class="btn btn-light btn-sm py-0 px-2 border" 
                                               title="View Source Details" style="font-size: 11px;">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <!-- Final Statement Total Row -->
                                <tr class="table-light fw-bold" style="font-size: 13.5px; border-top: 2px solid #cbd5e1;">
                                    <td colspan="5" class="ps-4 text-uppercase">
                                        Total Invoiced &amp; Paid (Current Period)
                                    </td>
                                    <td class="text-end text-dark">৳ <?= number_format($totalDebit, 2) ?></td>
                                    <td class="text-end text-success">৳ <?= number_format($totalCredit, 2) ?></td>
                                    <td class="text-end text-danger" style="background-color: #fee2e2;">
                                        ৳ <?= number_format($kpiMetrics['total_due_amount'], 2) ?>
                                    </td>
                                    <td colspan="2"></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-file-circle-question fs-3 d-block mb-2 text-secondary"></i>
                                        No purchase orders or payment transactions found for this vendor matching the filters.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- 4. MASTER VENDORS FINANCIAL SUMMARY LIST                           -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="fa-solid fa-building-user text-primary me-2"></i>Vendors Financial Summary &amp; Ledger Index
                    </h6>
                    <small class="text-muted">Showing financial health, total purchase orders, disbursed payments, and outstanding dues per vendor</small>
                </div>
                <div class="text-muted small">
                    Total Records: <strong><?= number_format($totalRecords ?? 0) ?></strong>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 custom-table">
                        <thead class="table-light text-muted small text-uppercase" style="font-size: 11px;">
                            <tr>
                                <th class="ps-4" style="width: 50px;">#</th>
                                <th>Vendor Details</th>
                                <th>Projects Supplied</th>
                                <th class="text-center">Total POs</th>
                                <th class="text-end">Total PO Value (৳)</th>
                                <th class="text-end">Total Paid (৳)</th>
                                <th class="text-end">Outstanding Due (৳)</th>
                                <th class="text-center">Last Payment</th>
                                <th class="text-center">Status</th>
                                <th class="text-center pe-4" style="width: 140px;">Ledger Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($vendorsSummary)): ?>
                                <?php foreach ($vendorsSummary as $idx => $vs): ?>
                                    <tr>
                                        <td class="ps-4 text-muted small">
                                            <?= ($offset ?? 0) + $idx + 1 ?>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php $vsDisplay = !empty(trim($vs['company_name'] ?? '')) ? trim($vs['company_name']) : $vs['name']; ?>
                                                <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold" 
                                                     style="width: 34px; height: 34px; font-size: 14px;">
                                                    <?= strtoupper(substr($vsDisplay, 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <a href="<?= $base_url ?>/con-vendor-ledger/<?= $vs['id'] ?>?<?= http_build_query(array_filter(['project_id' => $selectedProjectId, 'period' => $period, 'start_date' => $startDate, 'end_date' => $endDate])) ?>" 
                                                       class="fw-bold text-dark text-decoration-none">
                                                        <?= htmlspecialchars($vsDisplay) ?>
                                                    </a>
                                                    <div class="text-muted small" style="font-size: 11px;">
                                                        <?php if (!empty(trim($vs['company_name'] ?? '')) && !empty($vs['name'])): ?>
                                                            <i class="fa-solid fa-user me-1"></i><?= htmlspecialchars($vs['name']) ?> &bull;
                                                        <?php endif; ?>
                                                        <i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($vs['phone'] ?: 'No Phone') ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($vs['projects_summary'])): ?>
                                                <div class="text-truncate" style="max-width: 220px;" title="<?= htmlspecialchars($vs['projects_summary']) ?>">
                                                    <span class="badge bg-light text-dark border" style="font-size: 11px;">
                                                        <i class="fa-solid fa-building me-1 text-primary"></i><?= htmlspecialchars($vs['projects_summary']) ?>
                                                    </span>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2">
                                                <?= $vs['total_pos'] ?> POs
                                            </span>
                                        </td>
                                        <td class="text-end fw-bold text-dark text-nowrap">
                                            ৳ <?= number_format($vs['total_po_amount'], 2) ?>
                                        </td>
                                        <td class="text-end fw-bold text-success text-nowrap">
                                            ৳ <?= number_format($vs['total_paid'], 2) ?>
                                        </td>
                                        <td class="text-end fw-bold text-nowrap <?= ($vs['total_due'] > 0) ? 'text-danger' : 'text-muted' ?>">
                                            <?php if ($vs['total_due'] > 0): ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" style="font-size: 12px;">
                                                    ৳ <?= number_format($vs['total_due'], 2) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border px-2 py-1" style="font-size: 12px;">
                                                    ৳ 0.00
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center small text-muted text-nowrap">
                                            <?= $vs['last_payment_date'] ? date('d M, Y', strtotime($vs['last_payment_date'])) : '—' ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge <?= $vs['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>" style="font-size: 10px;">
                                                <?= ucfirst($vs['status']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center pe-4 text-nowrap">
                                            <div class="d-flex align-items-center justify-content-center gap-1">
                                                <a href="<?= $base_url ?>/con-vendor-ledger/<?= $vs['id'] ?>?<?= http_build_query(array_filter(['project_id' => $selectedProjectId, 'period' => $period, 'start_date' => $startDate, 'end_date' => $endDate])) ?>" 
                                                   class="btn btn-sm btn-primary py-1 px-2" style="font-size: 11.5px;" title="View Full Detailed Ledger">
                                                    <i class="fa-solid fa-book-open me-1"></i> View Ledger
                                                </a>
                                                <a href="<?= $base_url ?>/con-vendor-ledger-print/<?= $vs['id'] ?>?<?= http_build_query(array_filter(['project_id' => $selectedProjectId, 'start_date' => $startDate, 'end_date' => $endDate])) ?>" 
                                                   target="_blank" class="btn btn-sm btn-outline-secondary py-1 px-2" style="font-size: 11.5px;" title="Print Statement">
                                                    <i class="fa-solid fa-print"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-inbox fs-3 d-block mb-2 text-secondary"></i>
                                        No vendors found matching your search or filters.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if (!empty($totalPages) && $totalPages > 1): ?>
                    <div class="d-flex justify-content-between align-items-center flex-wrap p-3 border-top">
                        <div class="small text-muted mb-2 mb-sm-0">
                            Showing <?= min($totalRecords, $offset + 1) ?> to <?= min($totalRecords, $offset + $perPage) ?> of <?= $totalRecords ?> vendors
                        </div>
                        <ul class="pagination pagination-sm mb-0">
                            <!-- Previous -->
                            <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= $base_url ?>/con-vendor-ledger?<?= http_build_query(array_merge($filters, ['page' => $currentPage - 1])) ?>">
                                    &laquo; Prev
                                </a>
                            </li>
                            <!-- Page Numbers -->
                            <?php 
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min($totalPages, $currentPage + 2);
                            for ($p = $startPage; $p <= $endPage; $p++): 
                            ?>
                                <li class="page-item <?= ($p == $currentPage) ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= $base_url ?>/con-vendor-ledger?<?= http_build_query(array_merge($filters, ['page' => $p])) ?>">
                                        <?= $p ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <!-- Next -->
                            <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= $base_url ?>/con-vendor-ledger?<?= http_build_query(array_merge($filters, ['page' => $currentPage + 1])) ?>">
                                    Next &raquo;
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</section>

<style>
/* Select2 Compact Modern Styling in Vendor Ledger Filter Bar */
#vendorLedgerFilterForm .select2-container--default .select2-selection--single {
    height: 33px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    background: #ffffff;
    display: flex;
    align-items: center;
}
#vendorLedgerFilterForm .select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #1e293b;
    font-size: 13px;
    font-weight: 500;
    line-height: 31px;
    padding-left: 10px;
    padding-right: 24px;
}
#vendorLedgerFilterForm .select2-container--default .select2-selection--single .select2-selection__placeholder {
    color: #64748b;
}
#vendorLedgerFilterForm .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 31px;
    right: 8px;
}
#vendorLedgerFilterForm .select2-container--default .select2-selection--single:focus,
#vendorLedgerFilterForm .select2-container--default.select2-container--open .select2-selection--single {
    border-color: #2563eb;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
}
.select2-dropdown {
    border: 1px solid #cbd5e1 !important;
    border-radius: 6px !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05) !important;
    font-size: 13px !important;
    z-index: 10050 !important;
}
.select2-search--dropdown {
    padding: 6px 8px !important;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
.select2-search--dropdown .select2-search__field {
    border: 1px solid #cbd5e1 !important;
    border-radius: 6px !important;
    padding: 5px 10px !important;
    font-size: 12.5px !important;
    background: #ffffff !important;
    outline: none !important;
}
.select2-search--dropdown .select2-search__field:focus {
    border-color: #2563eb !important;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15) !important;
}
</style>

<script>
function handlePeriodChange() {
    const period = document.getElementById('filterPeriod').value;
    const customBox = document.getElementById('customDateRangeBox');
    if (period === 'custom') {
        customBox.style.display = 'block';
    } else {
        customBox.style.display = 'none';
    }
}
</script>

<?php
$page_script = '
$(document).ready(function() {
    if (typeof $.fn.select2 === "function") {
        $("#filterVendorId").select2({
            width: "100%",
            placeholder: "All Vendors (' . count($allVendors) . ')",
            allowClear: true
        });
        $("#filterProjectId").select2({
            width: "100%",
            placeholder: "All Projects (' . count($projects) . ')",
            allowClear: true
        });
    }
});
';
?>
