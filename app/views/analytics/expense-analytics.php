<?php
// Layout Header is loaded by Controller::view
?>

<style>
    .module-card-col-5 {
        width: 20%;
        padding: 6px;
    }
    @media (max-width: 1400px) {
        .module-card-col-5 { width: 25%; }
    }
    @media (max-width: 1100px) {
        .module-card-col-5 { width: 33.333%; }
    }
    @media (max-width: 768px) {
        .module-card-col-5 { width: 50%; }
    }
    @media (max-width: 576px) {
        .module-card-col-5 { width: 100%; }
    }

    .expense-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        transition: all 0.22s ease-in-out;
        position: relative;
        cursor: pointer;
    }
    .expense-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 18px rgba(0, 0, 0, 0.07);
        border-color: #cbd5e1;
    }
    .expense-card.active-module {
        border: 2px solid #3b82f6 !important;
        background: #f8faff !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
    }

    .pulse-amber {
        display: inline-block;
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background-color: #f59e0b;
        animation: pulseAmber 1.5s infinite;
        margin-right: 3px;
    }
    @keyframes pulseAmber {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 4px rgba(245, 158, 11, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
    }

    .stat-label {
        font-size: 10.5px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        color: #64748b;
    }
    .stat-value {
        font-size: 13.5px;
        font-weight: 700;
        color: #1e293b;
    }

    .leaderboard-item {
        transition: background 0.15s ease;
        padding: 8px 10px;
        border-radius: 8px;
    }
    .leaderboard-item:hover {
        background: #f1f5f9;
    }

    .preset-btn.active {
        background-color: #3b82f6 !important;
        color: #ffffff !important;
        border-color: #3b82f6 !important;
    }
</style>

<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary-subtle text-primary p-2 rounded-3 fs-5">
                    <i class="fa-solid fa-chart-pie"></i>
                </span>
                <div>
                    <h3 class="fw-bold mb-0 text-dark">Expense Analytics & Cost Intelligence</h3>
                    <p class="text-muted small mb-0">Executive cost dashboard & payout tracking across all 10 requisition modules & projects.</p>
                </div>
            </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <!-- Export to Excel Button -->
            <?php
            $exportParams = http_build_query([
                'project_id' => $filters['project_id'] ?? '',
                'from_date'  => $filters['from_date'] ?? '',
                'to_date'    => $filters['to_date'] ?? '',
                'module'     => $filters['module'] ?? '',
                'search'     => $filters['search'] ?? '',
            ]);
            ?>
            <a href="<?= $base_url ?>/expense-analytics/export?<?= $exportParams ?>" 
               class="btn btn-success btn-sm shadow-sm d-flex align-items-center gap-2 fw-semibold px-3 py-2 rounded-3">
                <i class="fa-solid fa-file-excel fs-6"></i>
                <span>Export Statement (.xlsx)</span>
            </a>

            <a href="<?= $base_url ?>/expense-analytics" class="btn btn-outline-secondary btn-sm shadow-sm px-3 py-2 rounded-3" title="Reset all filters">
                <i class="fa-solid fa-arrows-rotate me-1"></i> Reset
            </a>
        </div>
    </div>

    <!-- Filter Toolbar Card (Strictly 1 Single Line on Desktop) -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= $base_url ?>/expense-analytics" id="analyticsFilterForm">
                <div class="d-flex align-items-end gap-2 flex-nowrap" style="overflow-x: auto; padding-bottom: 2px;">
                    <!-- Project Filter -->
                    <div style="flex: 1.3; min-width: 145px;">
                        <label class="form-label stat-label mb-1 text-nowrap extra-small text-uppercase fw-bold text-secondary">
                            <i class="fa-solid fa-building text-primary me-1"></i> Project Scope
                        </label>
                        <select name="project_id" class="form-select form-select-sm" onchange="this.form.submit()" style="font-size: 12px; height: 34px;">
                            <option value="">🏢 All Projects & General</option>
                            <option value="without_project" <?= ($filters['project_id'] === 'without_project' || $filters['project_id'] === 'none') ? 'selected' : '' ?>>
                                🚫 Without Project / General Overhead
                            </option>
                            <optgroup label="── Active Construction Projects ──">
                                <?php foreach ($projectsList as $proj): ?>
                                    <option value="<?= $proj['id'] ?>" <?= ($filters['project_id'] == $proj['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($proj['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>

                    <!-- Module Filter -->
                    <div style="flex: 1.1; min-width: 130px;">
                        <label class="form-label stat-label mb-1 text-nowrap extra-small text-uppercase fw-bold text-secondary">
                            <i class="fa-solid fa-boxes-stacked text-primary me-1"></i> Expense Module
                        </label>
                        <select name="module" class="form-select form-select-sm" onchange="this.form.submit()" style="font-size: 12px; height: 34px;">
                            <option value="">All 10 Modules</option>
                            <?php foreach ($moduleCards as $mKey => $mDef): ?>
                                <option value="<?= $mKey ?>" <?= ($filters['module'] === $mKey) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($mDef['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Date Range From -->
                    <div style="flex: 0.85; min-width: 110px;">
                        <label class="form-label stat-label mb-1 text-nowrap extra-small text-uppercase fw-bold text-secondary">
                            <i class="fa-regular fa-calendar text-muted me-1"></i> From Date
                        </label>
                        <input type="date" name="from_date" id="filterFromDate" class="form-control form-control-sm" 
                               value="<?= htmlspecialchars($filters['from_date']) ?>" style="font-size: 12px; height: 34px;">
                    </div>

                    <!-- Date Range To -->
                    <div style="flex: 0.85; min-width: 110px;">
                        <label class="form-label stat-label mb-1 text-nowrap extra-small text-uppercase fw-bold text-secondary">
                            <i class="fa-regular fa-calendar-check text-muted me-1"></i> To Date
                        </label>
                        <input type="date" name="to_date" id="filterToDate" class="form-control form-control-sm" 
                               value="<?= htmlspecialchars($filters['to_date']) ?>" style="font-size: 12px; height: 34px;">
                    </div>

                    <!-- Search Input -->
                    <div style="flex: 1.25; min-width: 135px;">
                        <label class="form-label stat-label mb-1 text-nowrap extra-small text-uppercase fw-bold text-secondary">
                            <i class="fa-solid fa-magnifying-glass text-muted me-1"></i> Vendor / Contractor
                        </label>
                        <input type="text" name="search" class="form-control form-control-sm" 
                               placeholder="Search payee or title..." value="<?= htmlspecialchars($filters['search']) ?>" style="font-size: 12px; height: 34px;">
                    </div>

                    <!-- Submit & Reset Action Buttons -->
                    <div class="d-flex align-items-center gap-1" style="flex: 0 0 auto;">
                        <button type="submit" class="btn btn-primary btn-sm px-3 shadow-sm fw-semibold text-nowrap" style="height: 34px;">
                            <i class="fa-solid fa-filter me-1"></i> Filter
                        </button>
                        <?php if (!empty($filters['project_id']) || !empty($filters['module']) || !empty($filters['from_date']) || !empty($filters['to_date']) || !empty($filters['search'])): ?>
                            <a href="<?= $base_url ?>/expense-analytics" class="btn btn-outline-secondary btn-sm px-2 text-nowrap" title="Reset Filters" style="height: 34px;">
                                <i class="fa-solid fa-arrows-rotate"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Date Presets -->
                <div class="d-flex align-items-center gap-1 mt-2 pt-2 border-top">
                    <span class="text-muted extra-small me-2"><i class="fa-solid fa-clock-rotate-left me-1"></i>Quick Date:</span>
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-0 preset-btn" onclick="setDatePreset('all')">All Time</button>
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-0 preset-btn" onclick="setDatePreset('this_month')">This Month</button>
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-0 preset-btn" onclick="setDatePreset('last_month')">Last Month</button>
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-0 preset-btn" onclick="setDatePreset('this_year')">This Year</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Project Insights & Scope Summary Row -->
    <div class="row  mb-4">
        <!-- Selected Scope KPI Summary -->
        <div class="col-xl-7 col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar avatar-xs bg-primary-subtle text-primary rounded-2">
                                <i class="fa-solid fa-layer-group"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($projectSummary['project_name']) ?></h6>
                                <span class="text-muted extra-small">Filtered Expense Scope & Requisition Totals</span>
                            </div>
                        </div>
                        <?php if (!empty($filters['project_id'])): ?>
                            <span class="badge bg-primary rounded-pill px-2 py-1 extra-small">Filtered Project</span>
                        <?php else: ?>
                            <span class="badge bg-light text-muted border rounded-pill px-2 py-1 extra-small">Company-Wide</span>
                        <?php endif; ?>
                    </div>

                    <div class="row g-2 text-center">
                        <div class="col-sm-3 col-6">
                            <div class="p-2 bg-light rounded-2 border">
                                <span class="stat-label d-block text-truncate">Total Requisitions</span>
                                <h5 class="fw-bold text-dark mb-0">৳ <?= number_format($projectSummary['total_amount'], 0) ?></h5>
                                <span class="extra-small text-muted"><?= number_format($projectSummary['total_requests']) ?> Requests</span>
                            </div>
                        </div>
                        <div class="col-sm-3 col-6">
                            <div class="p-2 bg-warning-subtle rounded-2 border border-warning-subtle">
                                <span class="stat-label d-block text-truncate text-warning">Pending Approval</span>
                                <h5 class="fw-bold text-warning mb-0">৳ <?= number_format($projectSummary['pending_amount'], 0) ?></h5>
                                <span class="extra-small text-warning fw-semibold"><?= number_format($projectSummary['pending_count']) ?> Pending</span>
                            </div>
                        </div>
                        <div class="col-sm-3 col-6">
                            <div class="p-2 bg-success-subtle rounded-2 border border-success-subtle">
                                <span class="stat-label d-block text-truncate text-success">Total Paid</span>
                                <h5 class="fw-bold text-success mb-0">৳ <?= number_format($projectSummary['total_paid'], 0) ?></h5>
                                <span class="extra-small text-success">Disbursed (Cash/Bank)</span>
                            </div>
                        </div>
                        <div class="col-sm-3 col-6">
                            <div class="p-2 bg-danger-subtle rounded-2 border border-danger-subtle">
                                <span class="stat-label d-block text-truncate text-danger">Total Due</span>
                                <h5 class="fw-bold text-danger mb-0">৳ <?= number_format($projectSummary['total_due'], 0) ?></h5>
                                <span class="extra-small text-danger">Payable Balance</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Highest Cost Project Highlight & Top 5 Leaderboard -->
        <div class="col-xl-5 col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-warning">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-crown text-warning fs-5"></i>
                            <h6 class="fw-bold mb-0 text-dark">Highest Cost Project Leaderboard</h6>
                        </div>
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill extra-small">Top Spender</span>
                    </div>

                    <?php if (!empty($topProjectsData['highest'])): 
                        $top = $topProjectsData['highest'];
                    ?>
                        <div class="bg-light p-2 rounded-2 mb-2 d-flex align-items-center justify-content-between border">
                            <div class="d-flex align-items-center gap-2 text-truncate me-2">
                                <span class="badge bg-warning text-dark rounded-circle px-2 py-1 fw-bold">#1</span>
                                <div class="text-truncate">
                                    <span class="fw-bold text-dark text-truncate d-block" style="font-size: 13px;" title="<?= htmlspecialchars($top['project_name']) ?>">
                                        <?= htmlspecialchars($top['project_name']) ?>
                                    </span>
                                    <span class="text-muted extra-small">
                                        <?= number_format($top['total_records']) ?> records &bull; Paid: ৳<?= number_format($top['total_paid_amount'], 0) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="text-end flex-shrink-0">
                                <span class="fw-bold text-dark d-block" style="font-size: 13px;">৳ <?= number_format($top['total_req_amount'], 0) ?></span>
                                <span class="badge bg-warning text-dark extra-small rounded-pill"><?= $top['percentage'] ?>% of Top 5</span>
                            </div>
                        </div>

                        <!-- Top 5 Ranked Projects List -->
                        <div class="space-y-1">
                            <?php foreach ($topProjectsData['leaderboard'] as $idx => $pRow): 
                                if ($idx === 0) continue; // Skip #1 already highlighted
                            ?>
                                <div class="leaderboard-item d-flex align-items-center justify-content-between py-1 border-bottom extra-small">
                                    <div class="d-flex align-items-center gap-2 text-truncate me-2">
                                        <span class="text-muted fw-bold">#<?= $idx + 1 ?></span>
                                        <span class="text-truncate fw-semibold text-dark" title="<?= htmlspecialchars($pRow['project_name']) ?>">
                                            <?= htmlspecialchars($pRow['project_name']) ?>
                                        </span>
                                    </div>
                                    <div class="text-end flex-shrink-0">
                                        <span class="fw-bold text-dark me-2">৳ <?= number_format($pRow['total_req_amount'], 0) ?></span>
                                        <span class="text-muted"><?= $pRow['percentage'] ?>%</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small mb-0">No expense project data found for current filters.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 10 Expense Modules Breakdown (5 Cards Per Row) -->
    <div class="mb-4">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="fw-bold text-dark mb-0 d-flex align-items-center">
                <i class="fa-solid fa-boxes-stacked text-primary me-2"></i>
                All 10 Expense & Requisition Modules (5 Per Row)
            </h6>
            <span class="text-muted extra-small">Click any card to filter the expense ledger below</span>
        </div>

        <div class="d-flex flex-wrap" style="margin-left: -6px; margin-right: -6px;">
            <?php foreach ($moduleCards as $modKey => $mod): 
                $isActive = ($filters['module'] ?? '') === $modKey;
                $hasPending = $mod['pending_count'] > 0;
                $filterUrl = $isActive 
                    ? $base_url . '/expense-analytics?' . http_build_query(array_merge($filters, ['module' => ''])) . '#ledgerTable'
                    : $base_url . '/expense-analytics?' . http_build_query(array_merge($filters, ['module' => $modKey])) . '#ledgerTable';
            ?>
                <div class="module-card-col-5">
                    <div class="expense-card p-3 h-100 <?= $isActive ? 'active-module' : '' ?>" 
                         onclick="window.location.href='<?= $filterUrl ?>'"
                         title="Click to <?= $isActive ? 'clear filter' : 'filter by ' . htmlspecialchars($mod['name']) ?>">
                        
                        <!-- Header: Icon, Name, Dept -->
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-2 text-truncate me-1">
                                <div class="avatar avatar-sm rounded-2 d-flex align-items-center justify-content-center flex-shrink-0"
                                     style="background-color: <?= $mod['accent_hex'] ?>18; color: <?= $mod['accent_hex'] ?>; width: 34px; height: 34px;">
                                    <i class="<?= $mod['icon'] ?> fs-6"></i>
                                </div>
                                <div class="text-truncate">
                                    <span class="fw-bold text-dark text-truncate d-block" style="font-size: 12px; line-height: 1.2;" title="<?= htmlspecialchars($mod['name']) ?>">
                                        <?= htmlspecialchars($mod['name']) ?>
                                    </span>
                                    <span class="badge bg-light text-muted border extra-small" style="font-size: 9.5px; padding: 1px 4px;">
                                        <?= htmlspecialchars($mod['dept']) ?>
                                    </span>
                                </div>
                            </div>
                            <?php if ($isActive): ?>
                                <span class="badge bg-primary rounded-circle p-1"><i class="fa-solid fa-check text-white extra-small"></i></span>
                            <?php endif; ?>
                        </div>

                        <!-- Row 1: Total Requisitions & Pending (Side-by-Side) -->
                        <div class="d-flex align-items-center justify-content-between py-1 border-bottom mb-1" style="font-size: 11px;">
                            <span class="text-muted">Total Requests:</span>
                            <span class="fw-bold text-dark"><?= number_format($mod['total_requests']) ?> Req</span>
                        </div>

                        <!-- Row 2: Total Pending -->
                        <div class="d-flex align-items-center justify-content-between py-1 border-bottom mb-1" style="font-size: 11px;">
                            <span class="text-muted">Pending:</span>
                            <?php if ($hasPending): ?>
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle extra-small rounded-pill fw-bold">
                                    <span class="pulse-amber"></span> <?= $mod['pending_count'] ?> (৳<?= number_format($mod['pending_amount'], 0) ?>)
                                </span>
                            <?php else: ?>
                                <span class="badge bg-light text-muted border extra-small rounded-pill">0 Clear</span>
                            <?php endif; ?>
                        </div>

                        <!-- Row 3: Total Amount -->
                        <div class="d-flex align-items-center justify-content-between py-1 border-bottom mb-1" style="font-size: 11px;">
                            <span class="text-muted">Total Amount:</span>
                            <span class="fw-bold text-dark">৳ <?= number_format($mod['total_amount'], 0) ?></span>
                        </div>

                        <!-- Row 4: Paid vs Due -->
                        <div class="d-flex align-items-center justify-content-between pt-1" style="font-size: 11px;">
                            <div class="text-start">
                                <span class="extra-small text-muted d-block" style="font-size: 9.5px;">PAID</span>
                                <span class="fw-bold text-success">৳ <?= number_format($mod['total_paid'], 0) ?></span>
                            </div>
                            <div class="text-end">
                                <span class="extra-small text-muted d-block" style="font-size: 9.5px;">DUE</span>
                                <span class="fw-bold text-danger">৳ <?= number_format($mod['total_due'], 0) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Detailed Expense Ledger Table Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4" id="ledgerTable">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h6 class="fw-bold mb-0 text-dark d-flex align-items-center">
                    <i class="fa-solid fa-list-check text-primary me-2"></i> Detailed Expense & Requisition Ledger
                </h6>
                <span class="text-muted extra-small">Searchable statement of all expenses with payee, project, requisition, approved, paid and due amounts.</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-dark border extra-small rounded-pill px-3 py-1">
                    <?= count($ledgerRecords) ?> records displayed
                </span>
                <?php if (!empty($filters['module'])): ?>
                    <a href="<?= $base_url ?>/expense-analytics?<?= http_build_query(array_merge($filters, ['module' => ''])) ?>" 
                       class="badge bg-danger-subtle text-danger border border-danger-subtle text-decoration-none rounded-pill px-2 py-1 extra-small">
                        <i class="fa-solid fa-xmark me-1"></i> Clear Module: <?= htmlspecialchars($moduleCards[$filters['module']]['name'] ?? '') ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="expenseLedgerDataTable" style="width: 100%;">
                    <thead class="bg-light table-light">
                        <tr class="extra-small text-muted text-uppercase fw-bold">
                            <th style="width: 40px;" class="text-center">#</th>
                            <th style="width: 90px;">Date</th>
                            <th>Expense Module</th>
                            <th>Project Scope</th>
                            <th>Payee / Contractor / Vendor</th>
                            <th>Purpose / Invoice</th>
                            <th class="text-end">Req. Amount</th>
                            <th class="text-end">Approved</th>
                            <th class="text-end">Paid (Disbursed)</th>
                            <th class="text-end">Due (Payable)</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($ledgerRecords)): 
                            $sl = 1;
                            foreach ($ledgerRecords as $r): 
                                $projName = !empty($r['project_name']) ? $r['project_name'] : 'Without Project / General';
                                $isWithoutProj = empty($r['project_name']) || empty($r['project_id']);
                                $statusStr = strtolower($r['status'] ?? '');
                                $statusBadge = 'bg-secondary';
                                if (in_array($statusStr, ['approved', 'management_approved', 'qc_approved'])) $statusBadge = 'bg-primary';
                                elseif (in_array($statusStr, ['paid', 'fully_paid', 'labor_disbursed'])) $statusBadge = 'bg-success';
                                elseif (in_array($statusStr, ['partially_paid'])) $statusBadge = 'bg-info text-dark';
                                elseif (in_array($statusStr, ['pending', 'submitted', 'draft', ''])) $statusBadge = 'bg-warning text-dark';
                                elseif (in_array($statusStr, ['rejected'])) $statusBadge = 'bg-danger';
                        ?>
                            <tr style="font-size: 12px;">
                                <td class="text-center text-muted fw-semibold"><?= $sl++ ?></td>
                                <td class="text-nowrap text-muted">
                                    <?= !empty($r['created_at']) ? date('d-m-Y', strtotime($r['created_at'])) : '-' ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border extra-small">
                                        <?= htmlspecialchars($r['module_name']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($isWithoutProj): ?>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle extra-small">
                                            <i class="fa-solid fa-layer-group me-1"></i> Without Project / General
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle extra-small fw-semibold">
                                            <i class="fa-solid fa-building me-1"></i> <?= htmlspecialchars($projName) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark text-truncate" style="max-width: 180px;" title="<?= htmlspecialchars($r['payee_name']) ?>">
                                        <?= htmlspecialchars($r['payee_name']) ?>
                                    </div>
                                    <span class="extra-small text-muted"><?= htmlspecialchars($r['payee_type']) ?></span>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 220px;" title="<?= htmlspecialchars($r['purpose_title']) ?>">
                                        <?= htmlspecialchars($r['purpose_title']) ?>
                                    </div>
                                </td>
                                <td class="text-end fw-semibold text-dark text-nowrap">
                                    ৳ <?= number_format($r['req_amount'], 2) ?>
                                </td>
                                <td class="text-end text-muted text-nowrap">
                                    ৳ <?= number_format($r['approved_amount'], 2) ?>
                                </td>
                                <td class="text-end fw-bold text-success text-nowrap">
                                    ৳ <?= number_format($r['paid_amount'], 2) ?>
                                </td>
                                <td class="text-end fw-bold text-danger text-nowrap">
                                    ৳ <?= number_format($r['due_amount'], 2) ?>
                                </td>
                                <td class="text-center text-nowrap">
                                    <span class="badge <?= $statusBadge ?> extra-small rounded-pill">
                                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $r['status'] ?? 'pending'))) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-box-open fs-3 d-block mb-2 text-secondary"></i>
                                    No expense records found matching your filters.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Visual Analytics: Interactive ApexCharts Expense Distribution & Comparison -->
    <div class="row g-4 mb-4">
        <!-- Donut Chart: Expense Distribution (Sales Dashboard Style) -->
        <div class="col-xl-6 col-lg-12">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark d-flex align-items-center">
                            <i class="fa-solid fa-chart-pie text-primary me-2"></i> Expense Breakdown Analytics
                        </h6>
                        <span class="text-muted extra-small">Which expense module/category costs occurred in</span>
                    </div>
                    <!-- View and Metric Switchers -->
                    <div class="d-flex align-items-center gap-1">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary btn-xs active fw-semibold" id="btnDonutModeModule" onclick="switchDonutMode('module')">
                                <i class="fa-solid fa-boxes-stacked me-1"></i> By Module
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-xs fw-semibold" id="btnDonutModeProject" onclick="switchDonutMode('project')">
                                <i class="fa-solid fa-building me-1"></i> By Project
                            </button>
                        </div>
                        <div class="btn-group btn-group-sm ms-1" role="group">
                            <button type="button" class="btn btn-outline-secondary btn-xs active" id="btnDonutMetricAmount" onclick="switchDonutMetric('amount')">
                                ৳ Amount
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-xs" id="btnDonutMetricCount" onclick="switchDonutMetric('count')">
                                # Reqs
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body p-3 d-flex align-items-center justify-content-center">
                    <div id="apexExpenseDonutChart" style="width: 100%; min-height: 320px;"></div>
                </div>

                <div class="card-footer bg-light-subtle py-2 px-3 border-top d-flex align-items-center justify-content-between extra-small text-muted">
                    <span id="donutFooterDescription"><i class="fa-solid fa-chart-pie text-primary me-1"></i> Showing module-wise expense distribution</span>
                    <span class="fw-bold text-dark" id="donutFooterTotal">Total: ৳ <?= number_format($chartData['total_expense'], 0) ?></span>
                </div>
            </div>
        </div>

        <!-- Column Chart: Module-Wise Paid vs Due Comparison -->
        <div class="col-xl-6 col-lg-12">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark d-flex align-items-center">
                            <i class="fa-solid fa-chart-column text-success me-2"></i> Module-Wise Paid vs Due Comparison
                        </h6>
                        <span class="text-muted extra-small">Disbursed Paid vs Outstanding Payable Balance</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 extra-small">
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                            <i class="fa-solid fa-circle-check me-1"></i> Paid: ৳ <?= number_format($chartData['total_paid'], 0) ?>
                        </span>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                            <i class="fa-solid fa-clock me-1"></i> Due: ৳ <?= number_format($chartData['total_due'], 0) ?>
                        </span>
                    </div>
                </div>

                <div class="card-body p-3">
                    <div id="apexModuleComparativeBarChart" style="width: 100%; min-height: 320px;"></div>
                </div>

                <div class="card-footer bg-light-subtle py-2 px-3 border-top d-flex align-items-center justify-content-between extra-small text-muted">
                    <span><i class="fa-solid fa-arrow-trend-up text-success me-1"></i> Visualizing payment completion status across modules</span>
                    <span class="fw-semibold text-muted">10 Expense Modules</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts for DataTables and ApexCharts -->
<script>
    // Global chart instance & state for Donut Chart
    let expenseDonutChartInstance = null;
    let currentDonutMode = 'module';   // 'module' or 'project'
    let currentDonutMetric = 'amount'; // 'amount' or 'count'

    // Server-supplied datasets
    const donutDataSets = {
        module: {
            labels: <?= json_encode($chartData['module_labels']) ?>,
            amounts: <?= json_encode($chartData['module_series']) ?>,
            counts: <?= json_encode($chartData['module_counts']) ?>
        },
        project: {
            labels: <?= json_encode($chartData['project_labels']) ?>,
            amounts: <?= json_encode($chartData['project_series']) ?>,
            counts: <?= json_encode($chartData['project_counts']) ?>
        }
    };

    // Helper for Short BDT Currency formatting
    function formatShortBdt(val) {
        val = Number(val) || 0;
        if (val >= 10000000) {
            return (val / 10000000).toFixed(2) + ' Cr';
        } else if (val >= 100000) {
            return (val / 100000).toFixed(2) + ' L';
        } else if (val >= 1000) {
            return (val / 1000).toFixed(1) + 'K';
        }
        return Number(val).toLocaleString('en-IN', { maximumFractionDigits: 0 });
    }

    // Switch Donut Mode: 'module' vs 'project'
    function switchDonutMode(mode) {
        currentDonutMode = mode;
        document.getElementById('btnDonutModeModule').classList.toggle('active', mode === 'module');
        document.getElementById('btnDonutModeProject').classList.toggle('active', mode === 'project');
        updateDonutChart();
    }

    // Switch Donut Metric: 'amount' vs 'count'
    function switchDonutMetric(metric) {
        currentDonutMetric = metric;
        document.getElementById('btnDonutMetricAmount').classList.toggle('active', metric === 'amount');
        document.getElementById('btnDonutMetricCount').classList.toggle('active', metric === 'count');
        updateDonutChart();
    }

    // Dynamic Chart Update
    function updateDonutChart() {
        if (!expenseDonutChartInstance) return;

        const currentDataset = donutDataSets[currentDonutMode];
        const seriesData = (currentDonutMetric === 'amount') ? currentDataset.amounts : currentDataset.counts;
        const labelsData = currentDataset.labels;

        expenseDonutChartInstance.updateOptions({
            series: seriesData,
            labels: labelsData
        });

        // Update footer summary
        const total = seriesData.reduce((a, b) => a + Number(b), 0);
        const totalFooter = document.getElementById('donutFooterTotal');
        const descFooter = document.getElementById('donutFooterDescription');

        if (currentDonutMetric === 'amount') {
            totalFooter.innerHTML = 'Total: ৳ ' + Number(total).toLocaleString('en-IN', { maximumFractionDigits: 0 });
        } else {
            totalFooter.innerHTML = 'Total: ' + Number(total).toLocaleString() + ' Requests';
        }

        if (currentDonutMode === 'module') {
            descFooter.innerHTML = '<i class="fa-solid fa-boxes-stacked text-primary me-1"></i> Showing module-wise expense volume breakdown';
        } else {
            descFooter.innerHTML = '<i class="fa-solid fa-building text-primary me-1"></i> Showing project-wise expense distribution';
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        // 1. Initialize DataTables
        if (typeof $ !== 'undefined' && $.fn.DataTable) {
            $('#expenseLedgerDataTable').DataTable({
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                ordering: true,
                order: [[1, 'desc']],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search ledger table..."
                }
            });
        }

        // 2. Initialize ApexCharts Donut Chart (Sales-Dashboard Radial Gradient Style)
        const initialDataset = donutDataSets[currentDonutMode];
        const initialSeries = (currentDonutMetric === 'amount') ? initialDataset.amounts : initialDataset.counts;
        const initialLabels = initialDataset.labels;

        const donutOptions = {
            series: initialSeries,
            labels: initialLabels,
            chart: {
                type: 'donut',
                height: 320,
                fontFamily: 'inherit'
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '68%',
                        labels: {
                            show: true,
                            name: {
                                show: true,
                                fontSize: '12px',
                                offsetY: -6,
                                color: '#63666b'
                            },
                            value: {
                                show: true,
                                fontSize: '15px',
                                fontWeight: 700,
                                offsetY: 6,
                                color: '#1e293b',
                                formatter: function(val) {
                                    return currentDonutMetric === 'amount'
                                        ? '৳ ' + formatShortBdt(val)
                                        : Number(val).toLocaleString() + ' Reqs';
                                }
                            },
                            total: {
                                show: true,
                                label: 'Total',
                                fontSize: '13px',
                                color: '#7c7f85',
                                formatter: function(w) {
                                    const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                    return currentDonutMetric === 'amount'
                                        ? '৳ ' + formatShortBdt(total)
                                        : Number(total).toLocaleString();
                                }
                            }
                        }
                    }
                }
            },
            stroke: {
                width: 2,
                colors: ['#fff']
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'light',
                    type: 'radial',
                    shadeIntensity: 0.4,
                    gradientToColors: [
                        '#9f00fb',
                        '#13684a',
                        '#da950c',
                        '#FF4560',
                        '#775DD0',
                        '#00E396',
                        '#008FFB',
                        '#FEB019',
                        '#FF8698',
                        '#A796E0'
                    ],
                    opacityFrom: 1,
                    opacityTo: 1,
                    stops: [70, 100]
                }
            },
            colors: [
                'rgb(252, 72, 171)',
                '#12976b',
                '#fecc6a',
                '#ff8698',
                '#a796e0',
                '#00E396',
                '#008FFB',
                '#FEB019',
                '#6366F1',
                '#F59E0B'
            ],
            legend: {
                position: 'right',
                offsetY: 10,
                labels: {
                    colors: '#707174'
                },
                itemMargin: {
                    horizontal: 4,
                    vertical: 3
                },
                formatter: function(seriesName, opts) {
                    const val = opts.w.globals.series[opts.seriesIndex];
                    if (currentDonutMetric === 'amount') {
                        return `${seriesName} (৳ ${formatShortBdt(val)})`;
                    } else {
                        return `${seriesName} (${Number(val).toLocaleString()})`;
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function(val, opts) {
                        const total = opts.globals.seriesTotals.reduce((a, b) => a + b, 0);
                        const pct = total > 0 ? ((val / total) * 100).toFixed(1) + '%' : '0%';
                        if (currentDonutMetric === 'amount') {
                            return '৳ ' + Number(val).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' (' + pct + ')';
                        } else {
                            return Number(val).toLocaleString() + ' requests (' + pct + ')';
                        }
                    }
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function (val) {
                    return val >= 4 ? val.toFixed(1) + '%' : '';
                }
            },
            responsive: [
                {
                    breakpoint: 1300,
                    options: {
                        chart: { height: 300 },
                        legend: { position: 'right', fontSize: '11px' }
                    }
                },
                {
                    breakpoint: 768,
                    options: {
                        chart: { height: 320 },
                        legend: { position: 'bottom' }
                    }
                }
            ]
        };

        const donutEl = document.querySelector("#apexExpenseDonutChart");
        if (donutEl && typeof ApexCharts !== 'undefined') {
            expenseDonutChartInstance = new ApexCharts(donutEl, donutOptions);
            expenseDonutChartInstance.render();
        }

        // 3. Initialize ApexCharts Comparative Bar Chart (Per Module Paid vs Due)
        const modLabels = <?= json_encode($chartData['labels']) ?>;
        const paidSeries = <?= json_encode($chartData['paid_series']) ?>;
        const dueSeries = <?= json_encode($chartData['due_series']) ?>;

        const barOptions = {
            series: [
                { name: 'Paid Amount (৳)', data: paidSeries },
                { name: 'Due Amount (৳)', data: dueSeries }
            ],
            chart: {
                type: 'bar',
                height: 320,
                fontFamily: 'inherit',
                toolbar: { show: false }
            },
            colors: ['#10b981', '#ef4444'],
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    borderRadius: 4
                }
            },
            dataLabels: { enabled: false },
            stroke: { show: true, width: 2, colors: ['transparent'] },
            xaxis: {
                categories: modLabels,
                labels: {
                    rotate: -25,
                    style: { fontSize: '10.5px' }
                }
            },
            yaxis: {
                labels: {
                    formatter: function (val) {
                        if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M';
                        if (val >= 1000) return (val / 1000).toFixed(0) + 'K';
                        return val;
                    }
                }
            },
            fill: { opacity: 1 },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return '৳ ' + Number(val).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    }
                }
            },
            legend: { position: 'top', horizontalAlign: 'right', fontSize: '11px' }
        };

        const barEl = document.querySelector("#apexModuleComparativeBarChart");
        if (barEl && typeof ApexCharts !== 'undefined') {
            new ApexCharts(barEl, barOptions).render();
        }
    });

    // Quick Date Range Preset Helper
    function setDatePreset(type) {
        const fromInput = document.getElementById('filterFromDate');
        const toInput = document.getElementById('filterToDate');
        const today = new Date();

        function formatDate(d) {
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${y}-${m}-${day}`;
        }

        if (type === 'all') {
            fromInput.value = '';
            toInput.value = '';
        } else if (type === 'this_month') {
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            fromInput.value = formatDate(firstDay);
            toInput.value = formatDate(today);
        } else if (type === 'last_month') {
            const firstDay = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            const lastDay = new Date(today.getFullYear(), today.getMonth(), 0);
            fromInput.value = formatDate(firstDay);
            toInput.value = formatDate(lastDay);
        } else if (type === 'this_year') {
            const firstDay = new Date(today.getFullYear(), 0, 1);
            fromInput.value = formatDate(firstDay);
            toInput.value = formatDate(today);
        }

        document.getElementById('analyticsFilterForm').submit();
    }
</script>
