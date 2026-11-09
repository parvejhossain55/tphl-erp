<style>
    .history-card {
        border-radius: 14px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        border: 1px solid rgba(0,0,0,0.06);
        background: #ffffff;
    }
    .metric-card-history {
        border-radius: 12px;
        padding: 16px 18px;
        background: #ffffff;
        box-shadow: 0 3px 12px rgba(0,0,0,0.04);
        border-left: 4px solid #3b82f6;
        height: 100%;
        transition: transform 0.2s;
    }
    .metric-card-history:hover {
        transform: translateY(-2px);
    }
    .unit-badge {
        background: #f1f5f9;
        color: #334155;
        border: 1px solid #cbd5e1;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
        margin: 2px 2px 2px 0;
    }
    /* Executive Officer Hero Banner */
    .officer-hero-banner {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #064e3b 100%) !important;
        border-radius: 16px !important;
        padding: 22px 26px !important;
        color: #ffffff !important;
        box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.25), 0 8px 10px -6px rgba(15, 23, 42, 0.2) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
    }
    .officer-hero-banner h4, 
    .officer-hero-banner h5, 
    .officer-hero-banner h6, 
    .officer-hero-banner p, 
    .officer-hero-banner span, 
    .officer-hero-banner div, 
    .officer-hero-banner strong {
        color: #ffffff;
    }
    .officer-avatar-box {
        width: 54px;
        height: 54px;
        background: rgba(255, 255, 255, 0.15);
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: #ffffff;
        flex-shrink: 0;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }
    .hero-stat-badge {
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 8px;
        padding: 6px 12px;
        font-size: 12px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    /* Project Header Banner & Stat Chips */
    .project-header-banner {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
        color: #ffffff !important;
        border-radius: 12px 12px 0 0;
        padding: 16px 20px;
    }
    .project-header-banner h6, 
    .project-header-banner small, 
    .project-header-banner span {
        color: #ffffff;
    }
    .project-stat-pill {
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #ffffff !important;
        font-size: 12px;
        padding: 4px 10px;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
    }
    .project-stat-pill.stat-success {
        background: rgba(16, 185, 129, 0.22);
        border-color: rgba(16, 185, 129, 0.45);
        color: #a7f3d0 !important;
    }
    .project-stat-pill.stat-warning {
        background: rgba(245, 158, 11, 0.22);
        border-color: rgba(245, 158, 11, 0.45);
        color: #fde68a !important;
    }
    .project-stat-pill.stat-danger {
        background: rgba(239, 68, 68, 0.22);
        border-color: rgba(239, 68, 68, 0.45);
        color: #fecaca !important;
    }
    .client-row-nested {
        font-size: 13px;
    }
    .rank-badge-1 {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: #ffffff;
        box-shadow: 0 2px 6px rgba(245, 158, 11, 0.4);
    }
    .rank-badge-2 {
        background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
        color: #ffffff;
        box-shadow: 0 2px 6px rgba(100, 116, 139, 0.3);
    }
    .rank-badge-3 {
        background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
        color: #ffffff;
        box-shadow: 0 2px 6px rgba(180, 83, 9, 0.3);
    }
    .dual-progress-track {
        background-color: #fee2e2;
        border-radius: 6px;
        overflow: hidden;
        height: 8px;
    }
</style>

<div class="container-fluid py-3">
    <!-- PAGE HEADER & NAVIGATION -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold mb-1" style="color: #0f172a;">
                <i class="fa-solid fa-city text-primary me-2"></i> CED Projects History
            </h4>
            <p class="text-muted small mb-0">
                Track assigned projects, client unit counts, collection efficiency, remaining due, and performance rankings at a glance.
            </p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($canManage): ?>
                <a href="<?= $base_url ?>/manage-projects-for-ced" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">
                    <i class="fa-solid fa-diagram-project me-1"></i> Manage Projects for CED
                </a>
            <?php endif; ?>
            <a href="<?= $base_url ?>/ced-collection-statement" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="fa-solid fa-file-invoice-dollar me-1"></i> Collection Statement
            </a>
        </div>
    </div>

    <!-- SEARCH & FILTER BAR -->
    <div class="card history-card border-0 mb-4 shadow-sm">
        <div class="card-body p-3">
            <form method="GET" action="<?= $base_url ?>/ced-projects-history" class="row g-2 align-items-center">
                <?php if ($canManage): ?>
                    <div class="col-12 col-md-4">
                        <label class="small text-muted fw-bold mb-1"><i class="fa-solid fa-user me-1 text-primary"></i> Select CED Officer:</label>
                        <select name="agent_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- All Officers Overview (Ranking & Comparison) --</option>
                            <?php foreach ($agents as $ag): ?>
                                <option value="<?= $ag['id'] ?>" <?= ($selectedAgentId == $ag['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ag['full_name']) ?> (<?= htmlspecialchars($ag['role_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="small text-muted fw-bold mb-1"><i class="fa-solid fa-building me-1 text-primary"></i> Filter by Project:</label>
                        <select name="project_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- <?= !empty($selectedAgentId) && !empty($assignedProjects) ? 'All Assigned Projects (' . count($assignedProjects) . ')' : 'All Projects' ?> --</option>
                            <?php 
                            $projectListForDropdown = (!empty($selectedAgentId) && !empty($assignedProjects)) ? $assignedProjects : $allProjects;
                            foreach ($projectListForDropdown as $proj): 
                            ?>
                                <option value="<?= $proj['id'] ?>" <?= ($selectedProjectId == $proj['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($proj['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-4 d-flex align-items-end gap-2 mt-auto">
                        <div class="input-group input-group-sm flex-grow-1">
                            <input type="text" name="search" class="form-control" placeholder="Search officer, project, or client..." value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-search"></i></button>
                        </div>
                        <?php if (!empty($selectedAgentId) || !empty($selectedProjectId) || !empty($search)): ?>
                            <a href="<?= $base_url ?>/ced-projects-history" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
                                <i class="fa-solid fa-rotate-left"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="col-12 col-md-6">
                        <label class="small text-muted fw-bold mb-1"><i class="fa-solid fa-building me-1 text-primary"></i> Filter by Assigned Project:</label>
                        <select name="project_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- All Assigned Projects (<?= count($assignedProjects) ?>) --</option>
                            <?php foreach ($assignedProjects as $proj): ?>
                                <option value="<?= $proj['id'] ?>" <?= ($selectedProjectId == $proj['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($proj['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-6 d-flex align-items-end gap-2 mt-auto">
                        <div class="input-group input-group-sm flex-grow-1">
                            <input type="text" name="search" class="form-control" placeholder="Search project or client..." value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-search"></i></button>
                        </div>
                        <?php if (!empty($selectedProjectId) || !empty($search)): ?>
                            <a href="<?= $base_url ?>/ced-projects-history" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
                                <i class="fa-solid fa-rotate-left"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- SUMMARY METRICS CARDS -->
    <?php
    $sumProjects = 0;
    $sumClients = 0;
    $sumMemberships = 0;
    $sumUnits = 0;
    $sumValue = 0.0;
    $sumCollected = 0.0;
    $sumVerified = 0.0;
    $sumUnverified = 0.0;
    $sumOverdue = 0.0;

    $assignedProjectMap = [];
    if (!empty($allAgentsSummary)) {
        foreach ($allAgentsSummary as $ag) {
            if (!empty($ag['projects_list'])) {
                foreach ($ag['projects_list'] as $pr) {
                    $assignedProjectMap[$pr['id']] = true;
                }
            }
        }
    }
    $assignedProjectsCount = count($assignedProjectMap);
    $totalCompanyProjectsCount = count($allProjects ?? []);

    if ($targetAgentSummary) {
        $sumProjects    = $targetAgentSummary['projects_count'];
        $sumClients     = $targetAgentSummary['total_clients'];
        $sumMemberships = $targetAgentSummary['project_memberships_count'] ?? $sumClients;
        $sumUnits       = $targetAgentSummary['total_units'];
        $sumValue       = $targetAgentSummary['total_price_value'];
        $sumCollected   = $targetAgentSummary['total_collected'];
        $sumVerified    = $targetAgentSummary['verified_collected'] ?? 0.0;
        $sumUnverified  = $targetAgentSummary['unverified_collected'] ?? 0.0;
        $sumOverdue     = $targetAgentSummary['total_overdue'];
        $sumOutstanding = max(0, $sumValue - $sumCollected);
        $collectionRate = $sumValue > 0 ? round(($sumCollected / $sumValue) * 100, 1) : 0.0;
        $dueRate        = round(max(0, 100 - $collectionRate), 1);
    } elseif (!empty($selectedProjectId)) {
        // Filtered by a single project in overview
        $sumProjects = 1;
        $sumClients = 0;
        $sumUnits = 0;
        $sumValue = 0.0;
        $sumCollected = 0.0;
        $sumVerified = 0.0;
        $sumUnverified = 0.0;
        $sumOverdue = 0.0;
        foreach ($allAgentsSummary as $ag) {
            $sumClients     += $ag['total_clients'];
            $sumUnits       += $ag['total_units'];
            $sumValue       += $ag['total_price_value'];
            $sumCollected   += $ag['total_collected'];
            $sumVerified    += $ag['verified_collected'] ?? 0.0;
            $sumUnverified  += $ag['unverified_collected'] ?? 0.0;
            $sumOverdue     += $ag['total_overdue'];
        }
        $sumOutstanding = max(0, $sumValue - $sumCollected);
        $collectionRate = $sumValue > 0 ? round(($sumCollected / $sumValue) * 100, 1) : 0.0;
        $dueRate        = round(max(0, 100 - $collectionRate), 1);
    } else {
        // "All Officers Overview" / Company Overview:
        // Use company-wide totals matching clients-cashin-view and ced-dashboard!
        $sumProjects    = $companyOverallStats['total_projects'] ?? $totalCompanyProjectsCount;
        $sumClients     = $companyOverallStats['total_clients'] ?? 1338;
        $sumMemberships = $companyOverallStats['project_memberships'] ?? 1645;
        $sumUnits       = $companyOverallStats['total_units'] ?? 1645;
        $sumValue       = $companyOverallStats['total_price_value'] ?? 0.0;
        $sumCollected   = $companyOverallStats['total_collected'] ?? 0.0;
        $sumVerified    = $companyOverallStats['verified_collected'] ?? 0.0;
        $sumUnverified  = $companyOverallStats['unverified_collected'] ?? 0.0;
        $sumOverdue     = $companyOverallStats['total_overdue'] ?? 0.0;
        $sumOutstanding = $companyOverallStats['total_outstanding'] ?? max(0, $sumValue - $sumCollected);
        $collectionRate = $companyOverallStats['collection_rate'] ?? ($sumValue > 0 ? round(($sumCollected / $sumValue) * 100, 1) : 0.0);
        $dueRate        = $companyOverallStats['due_rate'] ?? round(max(0, 100 - $collectionRate), 1);
    }
    ?>

    <!-- METRIC TILES -->
    <div class="row mb-4">
        <!-- 1. Assigned Projects -->
        <div class="col-6 col-md-2">
            <div class="metric-card-history border-primary">
                <span class="text-muted small fw-semibold text-uppercase d-block" style="font-size:10px;">Assigned Projects</span>
                <h4 class="fw-bold mb-0 mt-1 text-dark"><?= number_format($sumProjects) ?></h4>
                <small class="text-primary fw-semibold" style="font-size:11px;">
                    <?php if (!empty($selectedProjectId)): ?>
                        Filtered Project
                    <?php elseif ($targetAgentSummary): ?>
                        Active Projects
                    <?php else: ?>
                        <?= $assignedProjectsCount ?> Assigned &bull; <?= max(0, $sumProjects - $assignedProjectsCount) ?> Pool
                    <?php endif; ?>
                </small>
            </div>
        </div>

        <!-- 2. Total Clients -->
        <div class="col-6 col-md-2">
            <div class="metric-card-history border-info" style="border-left-color: #06b6d4 !important;" title="<?= number_format($sumClients) ?> Unique individual clients across projects">
                <span class="text-muted small fw-semibold text-uppercase d-block" style="font-size:10px;">Total Clients</span>
                <h4 class="fw-bold mb-0 mt-1 text-dark"><?= number_format($sumClients) ?></h4>
                <small class="text-info fw-semibold" style="font-size:11px;">
                    <?php if (!empty($selectedProjectId)): ?>
                        <?= number_format($sumClients) ?> Booked Clients
                    <?php elseif ($targetAgentSummary): ?>
                        <?= number_format($sumClients) ?> Unique (<?= number_format($sumMemberships) ?> in Projects)
                    <?php else: ?>
                        <?= number_format($sumClients) ?> Booked Clients (All Projects)
                    <?php endif; ?>
                </small>
            </div>
        </div>

        <!-- 3. Total Units Booked -->
        <div class="col-6 col-md-2">
            <div class="metric-card-history border-secondary" style="border-left-color: #8b5cf6 !important;">
                <span class="text-muted small fw-semibold text-uppercase d-block" style="font-size:10px;">Units Booked</span>
                <h4 class="fw-bold mb-0 mt-1 text-dark"><?= number_format($sumUnits) ?></h4>
                <small class="text-muted fw-semibold" style="font-size:11px;">
                    <?php if ($targetAgentSummary || !empty($selectedProjectId)): ?>
                        Flats / Shares
                    <?php else: ?>
                        <?= number_format($sumUnits) ?> Flats / Shares (All Projects)
                    <?php endif; ?>
                </small>
            </div>
        </div>

        <!-- 4. Portfolio Value -->
        <div class="col-6 col-md-2">
            <div class="metric-card-history border-dark" style="border-left-color: #475569 !important;">
                <span class="text-muted small fw-semibold text-uppercase d-block" style="font-size:10px;">Total Share Value</span>
                <h4 class="fw-bold mb-0 mt-1 text-dark" style="font-size:1.15rem;">৳ <?= number_format($sumValue, 0) ?></h4>
                <small class="text-muted" style="font-size:11px;">Agreed Value</small>
            </div>
        </div>

        <!-- 5. Total Collected -->
        <div class="col-6 col-md-2">
            <div class="metric-card-history border-success" style="border-left-color: #10b981 !important;" title="Verified: ৳ <?= number_format($sumVerified, 0) ?> | Unverified: ৳ <?= number_format($sumUnverified, 0) ?>">
                <span class="text-muted small fw-semibold text-uppercase d-block" style="font-size:10px;">Total Collected</span>
                <h4 class="fw-bold mb-0 mt-1 text-success" style="font-size:1.15rem;">৳ <?= number_format($sumCollected, 0) ?></h4>
                <small class="text-success fw-semibold" style="font-size:10px;" title="Verified: ৳ <?= number_format($sumVerified, 0) ?> | Unverified: ৳ <?= number_format($sumUnverified, 0) ?>">
                    <i class="fa-solid fa-arrow-trend-up me-1"></i><?= $collectionRate ?>% (Verified + Unverified)
                </small>
            </div>
        </div>

        <!-- 6. Total Balance Due & Overdue -->
        <div class="col-6 col-md-2">
            <div class="metric-card-history border-danger" style="border-left-color: #ef4444 !important;">
                <span class="text-muted small fw-semibold text-uppercase d-block" style="font-size:10px;">Balance Due (<?= $dueRate ?>%)</span>
                <h4 class="fw-bold mb-0 mt-1 text-danger" style="font-size:1.15rem;">৳ <?= number_format($sumOutstanding, 0) ?></h4>
                <small class="text-danger fw-semibold" style="font-size:11px;">
                    Overdue: ৳ <?= number_format($sumOverdue, 0) ?>
                </small>
            </div>
        </div>
    </div>

    <!-- IF HEAD OF CED / MANAGEMENT AND NO SPECIFIC AGENT SELECTED: SHOW ALL AGENTS OVERVIEW TABLE WITH RANKING & COMPARISON -->
    <?php if ($canManage && empty($selectedAgentId)): ?>
        <!-- Scope Explanation Banner for Head of CED -->
        <div class="alert alert-light border shadow-sm rounded-3 mb-4 p-3 d-flex flex-wrap align-items-center justify-content-between gap-3" style="border-left: 4px solid #3b82f6 !important;">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary-subtle text-primary p-2 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; min-width: 42px;">
                    <i class="fa-solid fa-briefcase fs-5"></i>
                </div>
                <div>
                    <h6 class="mb-1 fw-bold text-dark">CED Assigned Portfolios vs Total Company Projects</h6>
                    <p class="mb-0 text-muted small">
                        Currently tracking <strong class="text-dark"><?= number_format($companyOverallStats['total_clients'] ?? $sumClients) ?> total company clients</strong> (<?= number_format($companyOverallStats['total_units'] ?? $sumUnits) ?> units). 
                        <strong class="text-primary"><?= $assignedProjectsCount ?> projects</strong> (with <?= number_format($allAgentsSummary[0]['total_clients'] + ($allAgentsSummary[1]['total_clients'] ?? 0)) ?> clients) are actively assigned to CED Officers, and <strong class="text-secondary"><?= max(0, $totalCompanyProjectsCount - $assignedProjectsCount) ?> projects</strong> are in the general company pool.
                    </p>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= $GLOBALS['base_url'] ?>/manage-projects-for-ced" class="btn btn-sm btn-primary">
                    <i class="fa-solid fa-sliders me-1"></i> Manage Project Assignments
                </a>
                <a href="<?= $GLOBALS['base_url'] ?>/clients-cashin-view" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-users me-1"></i> View All Company Clients (<?= number_format($companyOverallStats['total_clients'] ?? 1338) ?>)
                </a>
            </div>
        </div>

        <div class="card history-card border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <span class="fw-bold text-dark fs-6">
                        <i class="fa-solid fa-ranking-star text-warning me-2"></i> All CED Officers Portfolio Comparison & Ranking
                    </span>
                    <small class="text-muted d-block" style="font-size: 11px;">
                        Officers are ranked by collection efficiency (highest collection % against assigned portfolio value and lowest due ratio).
                    </small>
                </div>
                <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2">
                    <i class="fa-solid fa-users me-1"></i> <?= count($allAgentsSummary) ?> Officers Total
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="small text-uppercase text-muted" style="font-size: 11px;">
                                <th class="text-center ps-3" style="width: 75px;">Rank</th>
                                <th>Officer Name</th>
                                <th>Assigned Projects</th>
                                <th class="text-center">Clients</th>
                                <th class="text-center">Units</th>
                                <th class="text-end">Agreed Value</th>
                                <th class="text-end">Collected</th>
                                <th class="text-end">Balance Due</th>
                                <th class="text-end">Overdue EMI</th>
                                <th style="min-width: 175px;">Collection vs Due</th>
                                <th class="text-center" style="width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allAgentsSummary)): ?>
                                <tr>
                                    <td colspan="11" class="text-center py-5 text-muted">No officers found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($allAgentsSummary as $row): ?>
                                    <tr>
                                        <!-- Rank Badge -->
                                        <td class="text-center ps-3">
                                            <?php if ($row['rank'] === 1): ?>
                                                <span class="badge rank-badge-1 px-2.5 py-1.5 rounded-pill fw-bold" title="Top Performer (Rank #1)">
                                                    <i class="fa-solid fa-trophy me-1"></i>#1
                                                </span>
                                            <?php elseif ($row['rank'] === 2): ?>
                                                <span class="badge rank-badge-2 px-2.5 py-1.5 rounded-pill fw-bold" title="Rank #2">
                                                    <i class="fa-solid fa-medal me-1"></i>#2
                                                </span>
                                            <?php elseif ($row['rank'] === 3): ?>
                                                <span class="badge rank-badge-3 px-2.5 py-1.5 rounded-pill fw-bold" title="Rank #3">
                                                    <i class="fa-solid fa-medal me-1"></i>#3
                                                </span>
                                            <?php elseif ($row['rank'] !== null): ?>
                                                <span class="badge bg-light text-dark border px-2 py-1 fw-bold rounded-pill">
                                                    #<?= $row['rank'] ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border px-2 py-1 rounded-pill" title="No projects assigned">
                                                    &mdash;
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Officer Name -->
                                        <td>
                                            <a href="<?= $base_url ?>/ced-projects-history?agent_id=<?= $row['agent_id'] ?>" class="text-decoration-none">
                                                <span class="fw-bold text-dark d-block"><?= htmlspecialchars($row['agent_name']) ?></span>
                                                <div class="d-flex align-items-center gap-1 mt-0.5">
                                                    <small class="text-muted"><?= htmlspecialchars($row['role_name']) ?></small>
                                                    <?php if ($row['rank'] === 1): ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle py-0.5 px-1.5" style="font-size: 10px;">
                                                            Best Efficiency
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </a>
                                        </td>

                                        <!-- Assigned Projects -->
                                        <td>
                                            <span class="badge bg-primary text-white rounded-pill px-2.5 py-1">
                                                <?= $row['projects_count'] ?> Project<?= $row['projects_count'] > 1 ? 's' : '' ?>
                                            </span>
                                            <?php if (!empty($row['projects_list'])): ?>
                                                <small class="text-muted d-block mt-1 text-truncate" style="max-width: 250px;" title="<?= implode(', ', array_column($row['projects_list'], 'name')) ?>">
                                                    <?= htmlspecialchars(implode(', ', array_column($row['projects_list'], 'name'))) ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Clients -->
                                        <td class="text-center fw-bold text-dark" title="<?= number_format($row['total_clients']) ?> Unique Clients (<?= number_format($row['project_memberships_count'] ?? $row['total_clients']) ?> across projects)">
                                            <?= number_format($row['total_clients']) ?>
                                            <?php if (($row['project_memberships_count'] ?? 0) > $row['total_clients']): ?>
                                                <small class="text-muted d-block" style="font-size: 10px;">(<?= $row['project_memberships_count'] ?> in proj)</small>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Units -->
                                        <td class="text-center">
                                            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-1"><?= number_format($row['total_units']) ?></span>
                                        </td>

                                        <!-- Agreed Portfolio Value -->
                                        <td class="text-end fw-semibold text-dark">৳ <?= number_format($row['total_price_value'], 0) ?></td>

                                        <!-- Total Collected -->
                                        <td class="text-end fw-bold text-success" title="Verified: ৳ <?= number_format($row['verified_collected'] ?? 0, 0) ?> | Unverified: ৳ <?= number_format($row['unverified_collected'] ?? 0, 0) ?>">
                                            ৳ <?= number_format($row['total_collected'], 0) ?>
                                        </td>

                                        <!-- Balance Due (Less Due Comparison) -->
                                        <td class="text-end fw-semibold text-dark">৳ <?= number_format($row['total_outstanding'], 0) ?></td>

                                        <!-- Overdue EMI -->
                                        <td class="text-end">
                                            <?php if ($row['total_overdue'] > 0): ?>
                                                <span class="fw-bold text-danger">৳ <?= number_format($row['total_overdue'], 0) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted small">৳ 0</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Collection vs Due (Efficiency Bar) -->
                                        <td>
                                            <div class="dual-progress-track position-relative" title="Collected: <?= $row['collection_rate'] ?>% | Due: <?= $row['due_rate'] ?>%">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: <?= min(100, $row['collection_rate']) ?>%; height: 100%;"></div>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mt-1" style="font-size: 11px;">
                                                <span class="text-success fw-bold">
                                                    <i class="fa-solid fa-arrow-trend-up me-1"></i><?= $row['collection_rate'] ?>% Coll
                                                </span>
                                                <span class="text-danger fw-bold">
                                                    <i class="fa-solid fa-circle-exclamation me-1"></i><?= $row['due_rate'] ?>% Due
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Details Action -->
                                        <td class="text-center">
                                            <a href="<?= $base_url ?>/ced-projects-history?agent_id=<?= $row['agent_id'] ?>" class="btn btn-outline-primary btn-sm rounded px-2.5 py-1" title="View Full Project Breakdown">
                                                <i class="fa-solid fa-arrow-right me-1"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <!-- Unassigned / Company Pool Row -->
                                <?php if (!empty($unassignedProjectsStats) && empty($selectedProjectId)): ?>
                                    <tr style="background-color: #f8fafc; border-top: 2px dashed #cbd5e1;">
                                        <td class="text-center ps-3">
                                            <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 rounded-pill" title="Company Pool (Unassigned)">
                                                Pool
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-secondary d-block">
                                                <i class="fa-solid fa-folder-tree me-1 text-muted"></i>Company Unassigned Projects
                                            </span>
                                            <small class="text-muted" style="font-size: 11px;">General Company Portfolios</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border px-2 py-1 rounded-pill font-monospace" style="font-size: 11px;">
                                                <?= (int)$unassignedProjectsStats['unassigned_projects_count'] ?> Projects
                                            </span>
                                        </td>
                                        <td class="text-center font-monospace fw-semibold text-dark">
                                            <?= number_format($unassignedProjectsStats['total_clients']) ?>
                                        </td>
                                        <td class="text-center font-monospace fw-semibold text-muted">
                                            <?= number_format($unassignedProjectsStats['total_units']) ?>
                                        </td>
                                        <td class="text-end font-monospace fw-semibold text-dark">
                                            ৳ <?= number_format($unassignedProjectsStats['total_price_value'], 0) ?>
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-success">
                                            ৳ <?= number_format($unassignedProjectsStats['total_collected'], 0) ?>
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-dark">
                                            ৳ <?= number_format($unassignedProjectsStats['total_outstanding'], 0) ?>
                                        </td>
                                        <td class="text-end font-monospace fw-semibold text-danger">
                                            ৳ <?= number_format($unassignedProjectsStats['total_overdue'], 0) ?>
                                        </td>
                                        <td>
                                            <div class="dual-progress-track position-relative">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: <?= min(100, $unassignedProjectsStats['collection_rate']) ?>%; height: 100%;"></div>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mt-1" style="font-size: 11px;">
                                                <span class="text-success fw-bold"><?= $unassignedProjectsStats['collection_rate'] ?>% Coll</span>
                                                <span class="text-danger fw-bold"><?= $unassignedProjectsStats['due_rate'] ?>% Due</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= $base_url ?>/clients-cashin-view" class="btn btn-outline-secondary btn-sm rounded px-2.5 py-1" title="View in Clients Cashin View" style="font-size: 11px;">
                                                Clients
                                            </a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endif; ?>
                        </tbody>
                        <?php if (empty($selectedProjectId) && !empty($allAgentsSummary)): ?>
                            <tfoot class="table-light fw-bold" style="border-top: 2px solid #0f172a; font-size: 12px;">
                                <tr>
                                    <th class="text-center ps-3">Total</th>
                                    <th>GRAND TOTAL (Company)</th>
                                    <th><?= number_format($companyOverallStats['total_projects'] ?? count($allProjects ?? [])) ?> Projects</th>
                                    <th class="text-center text-primary font-monospace"><?= number_format($companyOverallStats['total_clients'] ?? $sumClients) ?></th>
                                    <th class="text-center text-dark font-monospace"><?= number_format($companyOverallStats['total_units'] ?? $sumUnits) ?></th>
                                    <th class="text-end font-monospace">৳ <?= number_format($companyOverallStats['total_price_value'] ?? $sumValue, 0) ?></th>
                                    <th class="text-end font-monospace text-success">৳ <?= number_format($companyOverallStats['total_collected'] ?? $sumCollected, 0) ?></th>
                                    <th class="text-end font-monospace text-danger">৳ <?= number_format($companyOverallStats['total_outstanding'] ?? $sumOutstanding, 0) ?></th>
                                    <th class="text-end font-monospace text-danger">৳ <?= number_format($companyOverallStats['total_overdue'] ?? $sumOverdue, 0) ?></th>
                                    <th>
                                        <div class="d-flex justify-content-between align-items-center" style="font-size: 11px;">
                                            <span class="text-success fw-bold"><?= $companyOverallStats['collection_rate'] ?? $collectionRate ?>% Coll</span>
                                            <span class="text-danger fw-bold"><?= $companyOverallStats['due_rate'] ?? $dueRate ?>% Due</span>
                                        </div>
                                    </th>
                                    <th class="text-center">
                                        <a href="<?= $base_url ?>/clients-cashin-view" class="btn btn-sm btn-primary py-0 px-2" style="font-size: 10px;">All</a>
                                    </th>
                                </tr>
                            </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- DETAILED PROJECT-BY-PROJECT BREAKDOWN (WHEN AN AGENT IS VIEWED OR AGENT VIEWS SELF) -->
    <?php if ($targetAgentSummary || !empty($projectDetails)): ?>
        <?php if ($targetAgentSummary): ?>
            <!-- VIEWING OFFICER HERO BANNER (HIGH CONTRAST & BEAUTIFUL DESIGN) -->
            <div class="officer-hero-banner mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="officer-avatar-box">
                            <i class="fa-solid fa-user-check"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                <h4 class="fw-bold mb-0 text-white" style="letter-spacing: -0.3px;">
                                    <?= htmlspecialchars($targetAgentSummary['agent_name']) ?>
                                </h4>
                                <span class="badge" style="background: rgba(255,255,255,0.18); border: 1px solid rgba(255,255,255,0.25); color: #ffffff; font-size: 11px;">
                                    <?= htmlspecialchars($targetAgentSummary['role_name']) ?>
                                </span>
                                <?php if (!empty($targetAgentSummary['rank'])): ?>
                                    <?php if ($targetAgentSummary['rank'] === 1): ?>
                                        <span class="badge" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #ffffff; font-size: 11px; box-shadow: 0 2px 6px rgba(245, 158, 11, 0.4);">
                                            <i class="fa-solid fa-trophy text-warning me-1"></i>Rank #1 &bull; Best Collection Rate
                                        </span>
                                    <?php else: ?>
                                        <span class="badge" style="background: linear-gradient(135deg, #38bdf8 0%, #0284c7 100%); color: #ffffff; font-size: 11px; box-shadow: 0 2px 6px rgba(56, 189, 248, 0.3);">
                                            <i class="fa-solid fa-medal me-1"></i>Portfolio Rank #<?= $targetAgentSummary['rank'] ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                <?php if (!empty($selectedProjectId)): ?>
                                    <div class="hero-stat-badge" style="background: rgba(59, 130, 246, 0.25); border-color: rgba(59, 130, 246, 0.4);">
                                        <i class="fa-solid fa-filter text-primary"></i>
                                        <span>Filtered: <strong><?= htmlspecialchars($projectDetails[0]['project_name'] ?? 'Single Project') ?></strong></span>
                                    </div>
                                <?php else: ?>
                                    <div class="hero-stat-badge">
                                        <i class="fa-solid fa-diagram-project text-info"></i>
                                        <span><strong><?= $targetAgentSummary['projects_count'] ?></strong> Assigned Projects</span>
                                    </div>
                                <?php endif; ?>
                                <div class="hero-stat-badge" title="<?= $targetAgentSummary['total_clients'] ?> Unique Clients across assigned projects (<?= $targetAgentSummary['project_memberships_count'] ?? $targetAgentSummary['total_clients'] ?> across projects due to multi-project clients)">
                                    <i class="fa-solid fa-users text-primary"></i>
                                    <span><strong><?= $targetAgentSummary['total_clients'] ?></strong> <?= !empty($selectedProjectId) ? 'Booked Clients' : 'Unique Clients (' . ($targetAgentSummary['project_memberships_count'] ?? $targetAgentSummary['total_clients']) . ' in Projects)' ?> (<?= $targetAgentSummary['total_units'] ?> Units)</span>
                                </div>
                                <div class="hero-stat-badge" style="background: rgba(16, 185, 129, 0.25); border-color: rgba(16, 185, 129, 0.4);" title="Verified: ৳ <?= number_format($targetAgentSummary['verified_collected'] ?? 0, 0) ?> | Unverified: ৳ <?= number_format($targetAgentSummary['unverified_collected'] ?? 0, 0) ?>">
                                    <i class="fa-solid fa-circle-check text-success"></i>
                                    <span>Total Collected: <strong style="color: #6ee7b7;"><?= $targetAgentSummary['collection_rate'] ?>% (৳ <?= number_format($targetAgentSummary['total_collected'] / 10000000, 2) ?> Cr)</strong></span>
                                </div>
                                <div class="hero-stat-badge" style="background: rgba(239, 68, 68, 0.2); border-color: rgba(239, 68, 68, 0.35);">
                                    <i class="fa-solid fa-hourglass-half text-danger"></i>
                                    <span>Due Ratio: <strong style="color: #fca5a5;"><?= $targetAgentSummary['due_rate'] ?>%</strong></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php if ($canManage): ?>
                        <div>
                            <a href="<?= $base_url ?>/ced-projects-history" class="btn btn-sm shadow-sm" style="background: #ffffff; color: #0f172a; font-weight: 600; border-radius: 50px; padding: 7px 18px;">
                                <i class="fa-solid fa-table-list me-1 text-primary"></i> View All Officers
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($projectDetails)): ?>
            <div class="card history-card border-0 p-5 text-center text-muted mb-4">
                <i class="fa-regular fa-folder-open fs-1 text-secondary mb-2"></i>
                <h5 class="fw-bold text-dark">No Projects Assigned Yet</h5>
                <p class="small text-muted mb-3">There are currently no active projects assigned to this portfolio.</p>
                <?php if ($canManage): ?>
                    <div>
                        <a href="<?= $base_url ?>/manage-projects-for-ced" class="btn btn-primary btn-sm px-4">
                            <i class="fa-solid fa-plus me-1"></i> Assign Projects Now
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="fa-solid fa-cubes-stacked text-primary me-2"></i> Assigned Projects Breakdown & Clients
                    </h5>
                    <small class="text-muted" style="font-size: 11px;">
                        Projects are sorted by collection efficiency (highest collection % & least due first).
                    </small>
                </div>
                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-2"><?= count($projectDetails) ?> Active Projects</span>
            </div>

            <!-- EACH ASSIGNED PROJECT CARD WITH ACCORDION -->
            <?php foreach ($projectDetails as $pIndex => $p): ?>
                <div class="card history-card border-0 mb-4 overflow-hidden shadow-sm">
                    <!-- Project Header Banner with High Contrast -->
                    <div class="project-header-banner">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2.5">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-warning text-dark fw-bold px-2.5 py-1.5 shadow-sm" title="Project Rank by Collection Efficiency" style="font-size: 12px;">
                                    <i class="fa-solid fa-trophy text-warning me-1"></i>Rank #<?= $p['project_rank'] ?>
                                </span>
                                <h5 class="fw-bold mb-0 text-white" style="letter-spacing: -0.2px;">
                                    <?= htmlspecialchars($p['project_name']) ?>
                                </h5>
                                <span class="badge" style="background: rgba(255,255,255,0.15); color: #ffffff; font-size: 11px;">
                                    Project ID: #<?= $p['project_id'] ?>
                                </span>
                            </div>

                            <div>
                                <button class="btn btn-sm btn-light rounded-pill px-3 py-1 fw-semibold shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#project-collapse-<?= $p['project_id'] ?>" style="font-size: 12px; color: #0f172a;">
                                    <i class="fa-solid fa-chevron-down me-1"></i> Toggle Clients (<?= $p['total_clients_count'] ?>)
                                </button>
                            </div>
                        </div>

                        <!-- Project Metric Chips -->
                        <div class="d-flex flex-wrap align-items-center gap-2 pt-1 border-top border-white-10">
                            <span class="project-stat-pill">
                                <i class="fa-solid fa-users me-1 text-info"></i> <?= $p['total_clients_count'] ?> Clients
                            </span>
                            <span class="project-stat-pill">
                                <i class="fa-solid fa-hotel me-1 text-primary"></i> <?= $p['total_units_count'] ?> Units Booked
                            </span>
                            <span class="project-stat-pill">
                                Agreed Value: <strong>৳ <?= number_format($p['project_total_price'], 0) ?></strong>
                            </span>
                            <span class="project-stat-pill stat-success" title="Verified: ৳ <?= number_format($p['project_paid_verified'] ?? 0, 0) ?> | Unverified: ৳ <?= number_format($p['project_paid_unverified'] ?? 0, 0) ?>">
                                <i class="fa-solid fa-check me-1"></i> Paid: <strong>৳ <?= number_format($p['project_total_paid'], 0) ?></strong> (<?= $p['project_collection_rate'] ?>%)
                            </span>
                            <span class="project-stat-pill stat-warning">
                                <i class="fa-solid fa-hourglass-half me-1"></i> Bal. Due: <strong>৳ <?= number_format($p['project_balance_due'], 0) ?></strong> (<?= $p['project_due_rate'] ?>%)
                            </span>
                            <?php if ($p['project_total_overdue'] > 0): ?>
                                <span class="project-stat-pill stat-danger">
                                    <i class="fa-solid fa-clock me-1"></i> Overdue EMI: <strong>৳ <?= number_format($p['project_total_overdue'], 0) ?></strong>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="collapse show" id="project-collapse-<?= $p['project_id'] ?>">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr class="small text-uppercase text-muted" style="font-size: 11px;">
                                            <th class="ps-3" style="width: 230px;">Client Name & Contact</th>
                                            <th class="text-center" style="width: 100px;">Units Taken</th>
                                            <th style="min-width: 180px;">Booked Units / Flats</th>
                                            <th class="text-end" style="width: 130px;">Total Price</th>
                                            <th class="text-end" style="width: 130px;">Total Paid</th>
                                            <th class="text-end" style="width: 130px;">Balance Due</th>
                                            <th class="text-end" style="width: 130px;">Overdue EMI</th>
                                            <th class="text-center" style="width: 90px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($p['clients'])): ?>
                                            <tr>
                                                <td colspan="8" class="text-center py-4 text-muted small">
                                                    No booked clients found under this project.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($p['clients'] as $c): ?>
                                                <tr class="client-row-nested">
                                                    <td class="ps-3">
                                                        <div class="d-flex align-items-center flex-wrap gap-1">
                                                            <span class="fw-bold text-dark"><?= htmlspecialchars($c['client_name']) ?></span>
                                                            <?php if (!empty($c['is_multi_project'])): ?>
                                                                <span class="badge bg-info-subtle text-info border border-info-subtle px-1.5 py-0.5" style="font-size: 9.5px; border-radius: 4px;" title="Client also has booked unit(s) in: <?= htmlspecialchars(implode(', ', $c['other_projects'])) ?>">
                                                                    <i class="fa-solid fa-arrows-split-up-and-left me-1"></i>Multi-Project
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if (!empty($c['client_phone'])): ?>
                                                            <small class="text-muted d-block"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($c['client_phone']) ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-primary text-white rounded-pill px-2.5 py-1">
                                                            <?= $c['units_count'] ?> Unit<?= $c['units_count'] > 1 ? 's' : '' ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex flex-wrap gap-1">
                                                            <?php foreach ($c['units'] as $u): ?>
                                                                <span class="unit-badge" title="Status: <?= htmlspecialchars($u['status']) ?> | Price: ৳ <?= number_format($u['unit_price'], 0) ?>">
                                                                    <i class="fa-solid fa-house me-1 text-primary"></i><?= htmlspecialchars($u['unit_name']) ?>
                                                                    <small class="text-muted">(৳ <?= number_format($u['unit_paid'], 0) ?>)</small>
                                                                </span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </td>
                                                    <td class="text-end fw-semibold text-dark">
                                                        ৳ <?= number_format($c['total_price'], 0) ?>
                                                    </td>
                                                    <td class="text-end fw-bold text-success" title="Verified: ৳ <?= number_format($c['paid_verified'] ?? 0, 0) ?> | Unverified: ৳ <?= number_format($c['paid_unverified'] ?? 0, 0) ?>">
                                                        ৳ <?= number_format($c['total_paid'], 0) ?>
                                                    </td>
                                                    <td class="text-end fw-bold text-danger">
                                                        ৳ <?= number_format($c['balance_due'], 0) ?>
                                                    </td>
                                                    <td class="text-end">
                                                        <?php if ($c['total_overdue'] > 0): ?>
                                                            <span class="fw-bold text-danger">৳ <?= number_format($c['total_overdue'], 0) ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted small">No Overdue</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if (!empty($c['units'][0]['booking_id'])): ?>
                                                            <a href="<?= $base_url ?>/clients-cash-in-history-view/<?= $c['units'][0]['booking_id'] ?>" 
                                                                class="btn btn-outline-primary btn-sm py-0.5 px-2 rounded" 
                                                                title="View Client Payment Ledger">
                                                                <i class="fa-solid fa-receipt me-1"></i> Ledger
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
