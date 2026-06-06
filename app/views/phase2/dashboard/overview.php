<?php
$base = $GLOBALS['base_url'];

// Calculate percentages for KPI cards
$pctOnTrack = $totalProjects > 0 ? round(($onTrackCount / $totalProjects) * 100) : 0;
$pctAtRisk  = $totalProjects > 0 ? round(($atRiskCount / $totalProjects) * 100) : 0;
$pctDelayed = $totalProjects > 0 ? round(($delayedCount / $totalProjects) * 100) : 0;
$budgetUsedPct = $totalApprovedBudget > 0 ? round(($totalBudgetUsed / $totalApprovedBudget) * 100, 1) : 0;
?>
<div class="row">
    <div class="col-12">
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

            /* Modern Color Palette & Aesthetics */
            :root {
                --po-bg-card: #ffffff;
                --po-border: rgba(0, 0, 0, 0.05);
                --po-text-muted: #64748b;
                --po-primary: var(--clr-theme-primary, #198754);
                --po-secondary: var(--clr-theme-secondary, #1abc9c);
                --po-primary-rgb: var(--clr-theme-primary-rgb, 25, 135, 84);
                --po-secondary-rgb: var(--clr-theme-secondary-rgb, 26, 188, 156);
            }

            .po-body {
                font-family: 'Inter', sans-serif;
                color: #1e293b;
                background-color: #f8fafc;
                padding: 10px 0;
            }

            /* Dashboard Header Navigation */
            .po-header-section {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 24px;
                flex-wrap: wrap;
                gap: 16px;
                padding: 0 10px;
            }
            .po-header-section h4 {
                font-size: 22px;
                font-weight: 800;
                margin: 0;
                color: #0f172a;
                letter-spacing: -0.6px;
            }
            .po-breadcrumb {
                font-size: 12px;
                color: var(--po-text-muted);
                display: flex;
                align-items: center;
                gap: 6px;
                margin-top: 4px;
            }
            .po-breadcrumb a {
                color: var(--po-primary);
                text-decoration: none;
                font-weight: 600;
            }
            .po-breadcrumb a:hover {
                text-decoration: underline;
            }

            .po-header-actions {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .po-btn-date {
                padding: 8px 16px;
                font-size: 13px;
                font-weight: 600;
                color: #475569;
                background: #ffffff;
                border: 1px solid rgba(0,0,0,0.08);
                border-radius: 10px;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                cursor: pointer;
            }
            .po-btn-filter {
                padding: 8px 16px;
                font-size: 13px;
                font-weight: 600;
                color: #475569;
                background: #ffffff;
                border: 1px solid rgba(0,0,0,0.08);
                border-radius: 10px;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                cursor: pointer;
            }
            .po-btn-export {
                padding: 8px 18px;
                font-size: 13px;
                font-weight: 700;
                color: #ffffff;
                background: linear-gradient(135deg, var(--po-primary), var(--po-secondary));
                border: none;
                border-radius: 10px;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                cursor: pointer;
                box-shadow: 0 4px 12px rgba(var(--po-primary-rgb), 0.2);
                transition: opacity 0.15s;
            }
            .po-btn-export:hover {
                opacity: 0.95;
            }

            /* 8 KPI Cards Grid Layout */
            .po-kpi-grid {
                display: grid;
                grid-template-columns: repeat(8, 1fr);
                gap: 16px;
                margin-bottom: 24px;
                padding: 0 10px;
            }
            @media (max-width: 1600px) {
                .po-kpi-grid {
                    grid-template-columns: repeat(4, 1fr);
                }
            }
            @media (max-width: 992px) {
                .po-kpi-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }
            @media (max-width: 480px) {
                .po-kpi-grid {
                    grid-template-columns: 1fr;
                }
            }

            .po-kpi-card {
                background: var(--po-bg-card);
                border: 1px solid var(--po-border);
                border-radius: 16px;
                padding: 16px;
                display: flex;
                align-items: center;
                gap: 12px;
                position: relative;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.01);
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }
            .po-kpi-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.04);
            }
            .po-kpi-icon-wrap {
                width: 42px;
                height: 42px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 18px;
                flex-shrink: 0;
            }
            .po-kpi-info {
                display: flex;
                flex-direction: column;
                flex: 1;
            }
            .po-kpi-title {
                font-size: 10.5px;
                font-weight: 700;
                text-transform: uppercase;
                color: var(--po-text-muted);
                letter-spacing: 0.4px;
                margin-bottom: 4px;
            }
            .po-kpi-val {
                font-size: 20px;
                font-weight: 800;
                color: #0f172a;
                line-height: 1.1;
                margin-bottom: 2px;
            }
            .po-kpi-desc {
                font-size: 10.5px;
                color: var(--po-text-muted);
            }

            /* Custom Circular Progress Gauge in Card 5 */
            .po-radial-kpi-card {
                background: var(--po-bg-card);
                border: 1px solid var(--po-border);
                border-radius: 16px;
                padding: 12px 16px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.01);
                transition: transform 0.2s ease;
            }
            .po-radial-kpi-card:hover {
                transform: translateY(-2px);
            }
            .po-radial-gauge-container {
                position: relative;
                width: 54px;
                height: 54px;
                flex-shrink: 0;
            }
            .po-radial-gauge-text {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 12px;
                font-weight: 800;
                color: #0f172a;
            }

            /* Premium Card Styling for Main Widgets */
            .po-card-wrapper {
                background: var(--po-bg-card);
                border: 1px solid var(--po-border);
                border-radius: 16px;
                box-shadow: 0 2px 12px rgba(0, 0, 0, 0.01);
                margin-bottom: 24px;
                overflow: hidden;
            }
            .po-card-header {
                padding: 18px 24px;
                border-bottom: 1px solid rgba(0,0,0,0.04);
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 12px;
                background: #ffffff;
            }
            .po-card-title {
                font-size: 14px;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin: 0;
                color: #0f172a;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .po-card-header-link {
                font-size: 12px;
                font-weight: 700;
                color: var(--po-primary);
                text-decoration: none;
                transition: opacity 0.15s;
            }
            .po-card-header-link:hover {
                opacity: 0.8;
            }

            /* Progress Table Overrides */
            .po-table-search-bar {
                padding: 14px 24px;
                background: #fdfdfd;
                border-bottom: 1px solid rgba(0,0,0,0.04);
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 12px;
            }
            .po-search-input-wrap {
                position: relative;
                flex: 1;
                max-width: 320px;
                min-width: 200px;
            }
            .po-search-input-wrap input {
                width: 100%;
                padding: 8px 12px 8px 34px;
                font-size: 12.5px;
                border-radius: 10px;
                border: 1px solid rgba(0,0,0,0.1);
                outline: none;
                background: #fff;
                transition: border-color 0.15s;
            }
            .po-search-input-wrap input:focus {
                border-color: var(--po-primary);
            }
            .po-search-icon {
                position: absolute;
                left: 12px;
                top: 50%;
                transform: translateY(-50%);
                font-size: 12px;
                color: var(--po-text-muted);
            }

            .po-select {
                padding: 8px 14px;
                font-size: 12.5px;
                font-weight: 600;
                color: #334155;
                background: #fff;
                border: 1px solid rgba(0,0,0,0.1);
                border-radius: 10px;
                cursor: pointer;
                outline: none;
            }
            .po-select:focus {
                border-color: var(--po-primary);
            }

            .po-table-container {
                width: 100%;
                overflow-x: auto;
            }
            .po-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 13px;
            }
            .po-table th {
                background: #fcfdfd;
                padding: 14px 16px;
                font-size: 10px;
                font-weight: 700;
                text-transform: uppercase;
                color: var(--po-text-muted);
                letter-spacing: 0.5px;
                text-align: left;
                border-bottom: 1px solid rgba(0,0,0,0.05);
            }
            .po-table td {
                padding: 14px 16px;
                border-bottom: 1px solid rgba(0,0,0,0.04);
            }

            /* Custom Progress Bars */
            .prog-compare-wrap {
                width: 100%;
            }
            .prog-bar-container {
                width: 100%;
                border-radius: 99px;
                overflow: hidden;
            }
            .prog-bar-fill-planned {
                border-radius: 99px;
                transition: width 0.3s ease;
            }
            .prog-bar-fill-actual {
                border-radius: 99px;
                transition: width 0.3s ease;
            }

            /* Status Pills */
            .status-pill {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                font-size: 10px;
                font-weight: 700;
                padding: 3px 10px;
                border-radius: 99px;
                letter-spacing: 0.2px;
            }
            .status-pill-dot {
                width: 6px;
                height: 6px;
                border-radius: 50%;
                display: inline-block;
            }
            .status-ontrack { background: rgba(var(--po-primary-rgb), 0.08); color: var(--po-primary); }
            .status-ontrack .status-pill-dot { background: var(--po-primary); }
            .status-atrisk { background: rgba(245, 158, 11, 0.08); color: #d97706; }
            .status-atrisk .status-pill-dot { background: #f59e0b; }
            .status-delayed { background: rgba(239, 68, 68, 0.08); color: #dc2626; }
            .status-delayed .status-pill-dot { background: #ef4444; }
            .status-planning { background: rgba(59, 130, 246, 0.08); color: #2563eb; }
            .status-planning .status-pill-dot { background: #3b82f6; }
            .status-completed { background: rgba(16, 185, 129, 0.08); color: #059669; }
            .status-completed .status-pill-dot { background: #10b981; }

            .proj-title {
                font-weight: 700;
                color: #0f172a;
                text-decoration: none;
                font-size: 13.5px;
                transition: color 0.15s;
            }
            .proj-title:hover {
                color: var(--po-primary);
            }
            .proj-loc {
                font-size: 11px;
                color: var(--po-text-muted);
                margin-top: 3px;
                display: flex;
                align-items: center;
                gap: 4px;
            }

            /* Phase items container styling */
            .phase-items-container {
                animation: slideDown 0.2s ease-out;
            }
            @keyframes slideDown {
                from { opacity: 0; transform: translateY(-5px); }
                to { opacity: 1; transform: translateY(0); }
            }

            /* Side metric values for charts */
            .po-trend-side-metrics {
                display: flex;
                flex-direction: column;
                justify-content: center;
                gap: 20px;
                padding-left: 20px;
                border-left: 1px solid #f1f5f9;
                min-width: 110px;
            }
            @media (max-width: 768px) {
                .po-trend-side-metrics {
                    flex-direction: row;
                    border-left: none;
                    border-top: 1px solid #f1f5f9;
                    padding-left: 0;
                    padding-top: 16px;
                    justify-content: space-around;
                }
            }

            /* Bottom Widget Grid */
            .po-bottom-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 20px;
                padding: 0 10px;
            }
            @media (max-width: 1400px) {
                .po-bottom-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }
            @media (max-width: 768px) {
                .po-bottom-grid {
                    grid-template-columns: 1fr;
                }
            }

            .po-bottom-card {
                background: #ffffff;
                border: 1px solid var(--po-border);
                border-radius: 16px;
                padding: 20px;
                box-shadow: 0 2px 12px rgba(0, 0, 0, 0.01);
                display: flex;
                flex-direction: column;
                min-height: 320px;
            }
            .po-bottom-card-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 16px;
            }
            .po-bottom-card-title {
                font-size: 12px;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                color: #0f172a;
            }
            .po-bottom-card-link {
                font-size: 11px;
                font-weight: 700;
                color: var(--po-primary);
                text-decoration: none;
            }

            /* Custom List Layouts for Bottom Cards */
            .po-bottom-list {
                display: flex;
                flex-direction: column;
                gap: 12px;
                flex: 1;
            }
            .po-bottom-list-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                padding: 8px 0;
                border-bottom: 1px dashed #f1f5f9;
            }
            .po-bottom-list-item:last-child {
                border-bottom: none;
            }
            .po-item-icon-wrap {
                width: 32px;
                height: 32px;
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 14px;
                flex-shrink: 0;
            }
        </style>

        <div class="po-body">
            <!-- Header section with actions -->
            <div class="po-header-section">
                <div>
                    <h4>Project Progress Overview</h4>
                    <div class="po-breadcrumb">
                        <a href="<?= $base ?>/con-dashboard"><i class="fa-solid fa-house me-1"></i>Dashboard</a>
                        <span>›</span>
                        <span>Overview Dashboard</span>
                    </div>
                </div>
                <div class="po-header-actions">
                    <!-- Date Button -->
                    <button class="po-btn-date">
                        <i class="fa-regular fa-calendar-days"></i>
                        <span><?= date('M d, Y', strtotime('-6 days')) ?> — <?= date('M d, Y') ?></span>
                        <i class="fa-solid fa-angle-down"></i>
                    </button>
                    <!-- Filter Button -->
                    <button class="po-btn-filter">
                        <i class="fa-solid fa-sliders"></i>
                        <span>Filter</span>
                    </button>
                    <!-- Export Report -->
                    <button class="po-btn-export" onclick="window.print()">
                        <i class="fa-solid fa-cloud-arrow-down"></i>
                        <span>Export Report</span>
                    </button>
                </div>
            </div>

            <!-- Top Strip of 8 KPI Cards -->
            <div class="po-kpi-grid">
                <!-- Card 1: Total Projects -->
                <div class="po-kpi-card">
                    <div class="po-kpi-icon-wrap" style="background: rgba(99, 102, 241, 0.08); color: #6366f1;">
                        <i class="fa-solid fa-building"></i>
                    </div>
                    <div class="po-kpi-info">
                        <span class="po-kpi-title">Total Projects</span>
                        <span class="po-kpi-val"><?= $totalProjects ?></span>
                        <span class="po-kpi-desc">Across <?= $locationsCount ?> Locations</span>
                    </div>
                </div>
                
                <!-- Card 2: On Track -->
                <div class="po-kpi-card">
                    <div class="po-kpi-icon-wrap" style="background: rgba(var(--po-primary-rgb), 0.08); color: var(--po-primary);">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div class="po-kpi-info">
                        <span class="po-kpi-title">On Track</span>
                        <span class="po-kpi-val"><?= $onTrackCount ?></span>
                        <span class="po-kpi-desc"><?= $pctOnTrack ?>% of total</span>
                    </div>
                </div>

                <!-- Card 3: At Risk -->
                <div class="po-kpi-card">
                    <div class="po-kpi-icon-wrap" style="background: rgba(245, 158, 11, 0.08); color: #f59e0b;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div class="po-kpi-info">
                        <span class="po-kpi-title">At Risk</span>
                        <span class="po-kpi-val"><?= $atRiskCount ?></span>
                        <span class="po-kpi-desc"><?= $pctAtRisk ?>% of total</span>
                    </div>
                </div>

                <!-- Card 4: Delayed -->
                <div class="po-kpi-card">
                    <div class="po-kpi-icon-wrap" style="background: rgba(239, 68, 68, 0.08); color: #ef4444;">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div class="po-kpi-info">
                        <span class="po-kpi-title">Delayed</span>
                        <span class="po-kpi-val"><?= $delayedCount ?></span>
                        <span class="po-kpi-desc"><?= $pctDelayed ?>% of total</span>
                    </div>
                </div>

                <!-- Card 5: Overall Progress Gauge (Radial Progress) -->
                <div class="po-radial-kpi-card">
                    <div class="po-radial-gauge-container">
                        <svg viewBox="0 0 36 36" style="width: 54px; height: 54px; transform: rotate(-90deg);">
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke="#e2e8f0" stroke-width="3" />
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke="var(--po-primary)" stroke-width="3"
                                    stroke-dasharray="<?= $overallProgressPct ?>, 100" stroke-linecap="round" />
                        </svg>
                        <div class="po-radial-gauge-text"><?= $overallProgressPct ?>%</div>
                    </div>
                    <div class="po-kpi-info">
                        <span class="po-kpi-title">Overall Progress</span>
                        <span class="po-kpi-val" style="font-size: 16px; margin: 0;"><?= $overallProgressPct ?>%</span>
                        <span class="po-kpi-desc">Actual vs Plan</span>
                    </div>
                </div>

                <!-- Card 6: Approved Budget -->
                <div class="po-kpi-card">
                    <div class="po-kpi-icon-wrap" style="background: rgba(139, 92, 246, 0.08); color: #8b5cf6;">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <div class="po-kpi-info">
                        <span class="po-kpi-title">Approved Budget</span>
                        <span class="po-kpi-val"><?= cpFmt($totalApprovedBudget) ?></span>
                        <span class="po-kpi-desc">Total Approved</span>
                    </div>
                </div>

                <!-- Card 7: Budget Used -->
                <div class="po-kpi-card">
                    <div class="po-kpi-icon-wrap" style="background: rgba(16, 185, 129, 0.08); color: #10b981;">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                    <div class="po-kpi-info">
                        <span class="po-kpi-title">Budget Used</span>
                        <span class="po-kpi-val"><?= cpFmt($totalBudgetUsed) ?></span>
                        <span class="po-kpi-desc"><?= $budgetUsedPct ?>% of budget</span>
                    </div>
                </div>

                <!-- Card 8: Pending Approvals -->
                <div class="po-kpi-card">
                    <div class="po-kpi-icon-wrap" style="background: rgba(249, 115, 22, 0.08); color: #f97316;">
                        <i class="fa-solid fa-file-circle-check"></i>
                    </div>
                    <div class="po-kpi-info">
                        <span class="po-kpi-title">Pending Approvals</span>
                        <span class="po-kpi-val"><?= $pendingApprovals ?></span>
                        <span class="po-kpi-desc">Requires action</span>
                    </div>
                </div>
            </div>

            <!-- Middle Widgets Section -->
            <div class="row px-3">
                <!-- Project Progress Table Widget -->
                <div class="col-lg-8 col-12 px-1">
                    <div class="po-card-wrapper">
                        <div class="po-card-header">
                            <h5 class="po-card-title">
                                <i class="fa-solid fa-rectangle-list text-primary"></i>
                                Project Progress Overview
                            </h5>
                            <a href="<?= $base ?>/con-projects" class="po-card-header-link">View All Projects →</a>
                        </div>
                        
                        <!-- Table Filter Controls -->
                        <div class="po-table-search-bar">
                            <div class="po-search-input-wrap">
                                <span class="po-search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                                <input type="text" id="overviewSearch" placeholder="Search project name or location..." onkeyup="onFilterChangeDebounced()">
                            </div>
                            <div class="d-flex gap-2">
                                <select class="po-select" id="overviewStatus" onchange="fetchPaginatedProjects(1)">
                                    <option value="">All Statuses</option>
                                    <option value="On Track">On Track</option>
                                    <option value="At Risk">At Risk</option>
                                    <option value="Delayed">Delayed</option>
                                    <option value="Planning">Planning</option>
                                    <option value="Completed">Completed</option>
                                </select>
                                <select class="po-select" id="overviewSort" onchange="fetchPaginatedProjects(1)">
                                    <option value="name">Sort by Name</option>
                                    <option value="progress-desc" selected>Progress (Highest)</option>
                                    <option value="progress-asc">Progress (Lowest)</option>
                                    <option value="budget-desc">Budget (Highest)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="po-table-container">
                            <table class="po-table" id="projectOverviewTable">
                                <thead>
                                    <tr>
                                        <th style="width: 25%;">Project & Location</th>
                                        <th style="width: 15%;">Current Phase</th>
                                        <th style="width: 30%;">Planned vs Actual Progress</th>
                                        <th style="width: 10%;">Status</th>
                                        <th style="width: 10%;">Handover Target</th>
                                        <th style="width: 8%;">Budget Usage</th>
                                        <th style="width: 2%; text-align: center;"></th>
                                    </tr>
                                </thead>
                                <tbody id="projectTableBody">
                                    <?php $this->view('phase2/dashboard/overview-rows', ['projects' => $projects], false); ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Controls -->
                        <div id="paginationControls">
                            <?php 
                            $this->view('phase2/dashboard/overview-pagination', [
                                'currentPage' => $currentPage,
                                'totalPages' => $totalPages,
                                'totalRecords' => $totalRecords,
                                'perPage' => $perPage,
                                'offset' => $offset
                            ], false); 
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Right Sidebar Widgets -->
                <div class="col-lg-4 col-12 px-1">
                    <!-- PORTFOLIO PROGRESS TREND -->
                    <div class="po-card-wrapper">
                        <div class="po-card-header">
                            <h5 class="po-card-title">
                                <i class="fa-solid fa-chart-line text-primary"></i>
                                Portfolio Progress Trend
                            </h5>
                            <!-- Dynamic controls -->
                            <div class="d-flex gap-2">
                                <select class="po-select" id="trendProjectSelect" style="padding: 4px 8px; font-size: 11px;" onchange="onTrendProjectChange(this.value)">
                                    <option value="0">Portfolio Overview</option>
                                    <?php foreach ($allProjectsList as $ap): ?>
                                        <option value="<?= $ap['id'] ?>"><?= htmlspecialchars($ap['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select class="po-select" id="trendModeSelect" style="padding: 4px 8px; font-size: 11px;" onchange="onTrendModeChange(this.value)">
                                    <option value="monthly">Monthly</option>
                                    <option value="yearly">Yearly</option>
                                    <option value="all_time" selected>All-Time</option>
                                </select>
                                <select class="po-select" id="trendTargetSelect" style="display:none; padding: 4px 8px; font-size: 11px;" onchange="loadTrendData()">
                                    <!-- Populated via AJAX -->
                                </select>
                                <select class="po-select" id="trendYearSelect" style="display:none; padding: 4px 8px; font-size: 11px;" onchange="loadTrendData()">
                                    <?php foreach ($globalYears as $yr): ?>
                                        <option value="<?= $yr ?>" <?= $yr == date('Y') ? 'selected' : '' ?>><?= $yr ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="p-3" style="display: flex; gap: 14px; align-items: stretch; min-height: 250px; flex-wrap: wrap;">
                            <div style="flex: 1; min-width: 180px; position: relative; height: 210px;">
                                <canvas id="progressTrendChart" style="height: 100%; width: 100%;"></canvas>
                            </div>
                            <!-- Side metrics values -->
                            <div class="po-trend-side-metrics">
                                <div>
                                    <div style="font-size: 26px; font-weight: 900; color: var(--po-primary); line-height: 1;" id="chartActualMetric"><?= $overallProgressPct ?>%</div>
                                    <div style="font-size: 10px; font-weight: 700; text-transform: uppercase; color: var(--po-text-muted); margin-top: 4px;">Actual</div>
                                </div>
                                <div>
                                    <div style="font-size: 26px; font-weight: 900; color: #6366f1; line-height: 1;" id="chartPlannedMetric"><?= $overallPlannedProgressPct ?>%</div>
                                    <div style="font-size: 10px; font-weight: 700; text-transform: uppercase; color: var(--po-text-muted); margin-top: 4px;">Planned</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- BUDGET UTILIZATION BY CATEGORY -->
                    <div class="po-card-wrapper">
                        <div class="po-card-header">
                            <h5 class="po-card-title">
                                <i class="fa-solid fa-chart-pie text-success"></i>
                                Budget Utilization by Category
                            </h5>
                        </div>
                        <div class="p-3" style="display: flex; gap: 14px; align-items: center; min-height: 220px; flex-wrap: wrap;">
                            <div style="width: 140px; height: 140px; position: relative; flex-shrink: 0; margin: 0 auto;">
                                <canvas id="budgetBreakdownChart" style="height: 100%; width: 100%;"></canvas>
                                <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; pointer-events: none;">
                                    <span style="font-size: 13px; font-weight: 800; color: #0f172a; line-height: 1;"><?= cpFmt($totalBudgetUsed) ?></span>
                                    <span style="font-size: 8px; font-weight: 600; color: var(--po-text-muted); text-transform: uppercase; margin-top: 4px;">Total Used</span>
                                </div>
                            </div>
                            <!-- Legends with dynamic Crores -->
                            <div id="budgetCategoryLegend" style="display: flex; flex-direction: column; gap: 8px; flex: 1; min-width: 180px;">
                                <!-- Filled in JS -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Row: Status Distribution, Delay Summary, Upcoming Approvals, Alerts -->
            <div class="po-bottom-grid">
                <!-- Card 1: Project Status Distribution -->
                <div class="po-bottom-card">
                    <div class="po-bottom-card-header">
                        <span class="po-bottom-card-title">Project Status Distribution</span>
                    </div>
                    <div style="display: flex; gap: 14px; align-items: center; flex: 1; flex-wrap: wrap; justify-content: center;">
                        <div style="width: 120px; height: 120px; position: relative; flex-shrink: 0;">
                            <canvas id="projectStatusChart" style="height: 100%; width: 100%;"></canvas>
                            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; pointer-events: none;">
                                <span style="font-size: 22px; font-weight: 900; color: #0f172a; line-height: 1;"><?= $totalProjects ?></span>
                                <span style="font-size: 8px; font-weight: 600; color: var(--po-text-muted); text-transform: uppercase; margin-top: 2px;">Projects</span>
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 10px; flex: 1; min-width: 120px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; font-size: 12px;">
                                <span style="display: flex; align-items: center; gap: 6px; font-weight: 600; color: #334155;">
                                    <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--po-primary); display: inline-block;"></span>
                                    On Track
                                </span>
                                <span style="font-weight: 700; color: #0f172a;"><?= $onTrackCount ?> <span style="font-size: 10.5px; color:#94a3b8; font-weight:500;">(<?= $pctOnTrack ?>%)</span></span>
                            </div>
                            <div style="display: flex; align-items: center; justify-content: space-between; font-size: 12px;">
                                <span style="display: flex; align-items: center; gap: 6px; font-weight: 600; color: #334155;">
                                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #f59e0b; display: inline-block;"></span>
                                    At Risk
                                </span>
                                <span style="font-weight: 700; color: #0f172a;"><?= $atRiskCount ?> <span style="font-size: 10.5px; color:#94a3b8; font-weight:500;">(<?= $pctAtRisk ?>%)</span></span>
                            </div>
                            <div style="display: flex; align-items: center; justify-content: space-between; font-size: 12px;">
                                <span style="display: flex; align-items: center; gap: 6px; font-weight: 600; color: #334155;">
                                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #ef4444; display: inline-block;"></span>
                                    Delayed
                                </span>
                                <span style="font-weight: 700; color: #0f172a;"><?= $delayedCount ?> <span style="font-size: 10.5px; color:#94a3b8; font-weight:500;">(<?= $pctDelayed ?>%)</span></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Delay / Risk Summary -->
                <div class="po-bottom-card">
                    <div class="po-bottom-card-header">
                        <span class="po-bottom-card-title">Delay / Risk Summary</span>
                        <a href="<?= $base ?>/con-dashboard" class="po-bottom-card-link">View All Risks →</a>
                    </div>
                    <div class="po-bottom-list">
                        <?php if (empty($risks)): ?>
                            <div style="font-size:11.5px; color: var(--po-text-muted); text-align:center; padding-top:30px;">
                                <i class="fa-solid fa-shield-halved mb-2" style="font-size:18px; opacity:0.3"></i>
                                <div>No open high priority risks found.</div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($risks as $r): ?>
                                <div class="po-bottom-list-item">
                                    <div style="display: flex; align-items: center; gap: 10px; flex: 1;">
                                        <div class="po-item-icon-wrap" style="background: rgba(239, 68, 68, 0.08); color: #ef4444;">
                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                        </div>
                                        <div>
                                            <div style="font-size: 12px; font-weight: 700; color: #1e293b;"><?= htmlspecialchars($r['project_name']) ?></div>
                                            <div style="font-size: 10.5px; color: var(--po-text-muted); margin-top: 2px;" title="<?= htmlspecialchars($r['description']) ?>">
                                                <?= htmlspecialchars(strlen($r['description']) > 32 ? substr($r['description'], 0, 32) . '...' : $r['description']) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <span style="font-size: 9px; font-weight: 800; padding: 2px 8px; border-radius: 4px; background: <?= $r['severity'] === 'Critical' ? 'rgba(239, 68, 68, 0.08)' : 'rgba(245, 158, 11, 0.08)' ?>; color: <?= $r['severity'] === 'Critical' ? '#ef4444' : '#f59e0b' ?>;">
                                        <?= htmlspecialchars($r['severity']) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Card 3: Upcoming Approvals -->
                <div class="po-bottom-card">
                    <div class="po-bottom-card-header">
                        <span class="po-bottom-card-title">Upcoming Approvals</span>
                        <a href="<?= $base ?>/con-dashboard" class="po-bottom-card-link">View All Approvals →</a>
                    </div>
                    <div class="po-bottom-list">
                        <?php if (empty($approvals)): ?>
                            <div style="font-size:11.5px; color: var(--po-text-muted); text-align:center; padding-top:30px;">
                                <i class="fa-solid fa-folder-tree mb-2" style="font-size:18px; opacity:0.3"></i>
                                <div>No pending items require approval.</div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($approvals as $idx => $app): 
                                $dateDiff = time() - strtotime($app['created_at']);
                                $relativeStr = $dateDiff < 86400 ? 'Today' : ($dateDiff < 172800 ? 'Yesterday' : date('M d', strtotime($app['created_at'])));
                                $bgColors = ['rgba(99, 102, 241, 0.08)', 'rgba(16, 185, 129, 0.08)', 'rgba(249, 115, 22, 0.08)', 'rgba(59, 130, 246, 0.08)'];
                                $fgColors = ['#6366f1', '#10b981', '#f97316', '#3b82f6'];
                                $bg = $bgColors[$idx % count($bgColors)];
                                $fg = $fgColors[$idx % count($fgColors)];
                                ?>
                                <div class="po-bottom-list-item" style="cursor: pointer;" onclick="window.location.href='<?= $base . $app['url'] ?>'">
                                    <div style="display: flex; align-items: center; gap: 10px; flex: 1;">
                                        <div class="po-item-icon-wrap" style="background: <?= $bg ?>; color: <?= $fg ?>;">
                                            <i class="fa-solid fa-file-invoice-dollar"></i>
                                        </div>
                                        <div>
                                            <div style="font-size: 12px; font-weight: 700; color: #1e293b;"><?= htmlspecialchars($app['description']) ?></div>
                                            <div style="font-size: 10.5px; color: var(--po-text-muted); margin-top: 2px;">
                                                <?= htmlspecialchars($app['project_name']) ?> &middot; <?= cpFmt($app['amount']) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <span style="font-size: 9px; font-weight: 800; color: #64748b;"><?= $relativeStr ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Card 4: Alerts & Notifications -->
                <div class="po-bottom-card">
                    <div class="po-bottom-card-header">
                        <span class="po-bottom-card-title">Alerts & Notifications</span>
                        <a href="<?= $base ?>/con-dashboard" class="po-bottom-card-link">View All Alerts →</a>
                    </div>
                    <div class="po-bottom-list">
                        <?php foreach ($alerts as $idx => $alert): 
                            $icoBg = $alert['severity'] === 'Warning' ? 'rgba(245, 158, 11, 0.08)' : ($alert['severity'] === 'Critical' ? 'rgba(239, 68, 68, 0.08)' : 'rgba(59, 130, 246, 0.08)');
                            $icoFg = $alert['severity'] === 'Warning' ? '#f59e0b' : ($alert['severity'] === 'Critical' ? '#ef4444' : '#3b82f6');
                            $ico = $alert['type'] === 'Low Stock' ? '<i class="fa-solid fa-boxes-stacked"></i>' : '<i class="fa-solid fa-shield-halved"></i>';
                            ?>
                            <div class="po-bottom-list-item">
                                <div style="display: flex; align-items: center; gap: 10px; flex: 1;">
                                    <div class="po-item-icon-wrap" style="background: <?= $icoBg ?>; color: <?= $icoFg ?>;">
                                        <?= $ico ?>
                                    </div>
                                    <div>
                                        <div style="font-size: 12px; font-weight: 600; color: #1e293b;"><?= htmlspecialchars($alert['message']) ?></div>
                                        <div style="font-size: 10px; color: var(--po-text-muted); margin-top: 2.5px;"><?= htmlspecialchars($alert['type']) ?></div>
                                    </div>
                                </div>
                                <span style="font-size: 9.5px; font-weight: 500; color: var(--po-text-muted); min-width: 50px; text-align: right;"><?= htmlspecialchars($alert['time']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div style="height: 30px;"></div>
        </div>
    </div>
</div>

<!-- JavaScript Integration -->
<script>
let progressTrendChartInstance = null;
let budgetBreakdownChartInstance = null;

// Initialize charts on load
document.addEventListener('DOMContentLoaded', function () {
    // 1. Initialize Trend Chart
    const trendCtx = document.getElementById('progressTrendChart').getContext('2d');
    const defaultLabels = <?= $trendMonths ?>;
    const defaultPlanned = <?= $plannedTrend ?>;
    const defaultActual = <?= $actualTrend ?>;

    progressTrendChartInstance = new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: defaultLabels,
            datasets: [
                {
                    label: 'Planned Progress',
                    data: defaultPlanned,
                    borderColor: 'rgba(99, 102, 241, 0.35)',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    fill: false,
                    tension: 0.15,
                    pointRadius: 0
                },
                {
                    label: 'Actual Progress',
                    data: defaultActual,
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.05)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.2,
                    pointRadius: 4,
                    pointBackgroundColor: '#8b5cf6'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + Number(context.raw).toFixed(2) + '%';
                        }
                    }
                }
            },
            scales: {
                y: {
                    min: 0,
                    max: 100,
                    ticks: {
                        callback: function(value) { return value + '%'; },
                        font: { family: "'Inter', sans-serif" }
                    },
                    grid: { color: 'rgba(0,0,0,0.03)' }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { family: "'Inter', sans-serif", weight: '600' } }
                }
            }
        }
    });

    // 2. Initialize Budget utilization doughnut chart
    const budgetCtx = document.getElementById('budgetBreakdownChart').getContext('2d');
    const rawCategoryData = <?= $budgetCategoryData ?>;
    const categoryColors = ['#198754', '#1abc9c', '#2563eb', '#f59e0b', '#64748b'];

    budgetBreakdownChartInstance = new Chart(budgetCtx, {
        type: 'doughnut',
        data: {
            labels: rawCategoryData.map(d => d.label),
            datasets: [{
                data: rawCategoryData.map(d => d.value),
                backgroundColor: categoryColors,
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            cutout: '72%'
        }
    });

    // Render Legends with dynamic crores
    renderBudgetCategoryLegends(rawCategoryData);

    // 3. Initialize Status Distribution Doughnut Chart
    const statusCtx = document.getElementById('projectStatusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['On Track', 'At Risk', 'Delayed'],
            datasets: [{
                data: [<?= $onTrackCount ?>, <?= $atRiskCount ?>, <?= $delayedCount ?>],
                backgroundColor: ['var(--po-primary)', '#f59e0b', '#ef4444'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            cutout: '72%'
        }
    });

    // Set default selected option styling and trigger data load
    onTrendModeChange('all_time');
});

// Javascript budget currency legends builder
function renderBudgetCategoryLegends(data) {
    const categoryColors = ['#198754', '#1abc9c', '#2563eb', '#f59e0b', '#64748b'];
    const legendContainer = document.getElementById('budgetCategoryLegend');
    legendContainer.innerHTML = '';
    
    const totalVal = data.reduce((acc, curr) => acc + curr.value, 0);
    
    data.forEach((d, idx) => {
        const item = document.createElement('div');
        item.style.display = 'flex';
        item.style.alignItems = 'center';
        item.style.justifyContent = 'space-between';
        item.style.fontSize = '12px';
        
        const formattedVal = cpFmtVal(d.value);
        const pctVal = totalVal > 0 ? ((d.value / totalVal) * 100).toFixed(0) : 0;
        
        item.innerHTML = `
            <div style="display:flex; align-items:center; gap:6px; font-weight:600; color:#475569;">
                <span style="width:8px; height:8px; border-radius:50%; background:${categoryColors[idx % categoryColors.length]}; display:inline-block;"></span>
                ${d.label}
            </div>
            <div style="font-weight:700; color:#0f172a;">
                ${formattedVal} <span style="font-size:10px; color:#94a3b8; font-weight:500;">(${pctVal}%)</span>
            </div>
        `;
        legendContainer.appendChild(item);
    });
}

function cpFmtVal(n) {
    n = parseFloat(n);
    if (n >= 10000000) return '৳' + (n / 10000000).toFixed(2) + ' Cr';
    if (n >= 100000) return '৳' + (n / 100000).toFixed(2) + ' L';
    if (n >= 1000) return '৳' + (n / 1000).toFixed(1) + 'K';
    return '৳' + n.toFixed(0);
}

// Interactive Trend Selector Handling
function onTrendProjectChange(projectId) {
    const modeSelect = document.getElementById('trendModeSelect');
    const targetSelect = document.getElementById('trendTargetSelect');
    const yearSelect = document.getElementById('trendYearSelect');

    if (projectId == "0") {
        // Portfolio average: Monthly disabled
        if (modeSelect.value === 'monthly') {
            modeSelect.value = 'all_time';
        }
        modeSelect.querySelector("option[value='monthly']").disabled = true;
        targetSelect.style.display = 'none';
        
        // Restore default globalYears in year dropdown
        const globalYears = <?= json_encode($globalYears) ?>;
        yearSelect.innerHTML = '';
        globalYears.forEach(yr => {
            const opt = document.createElement('option');
            opt.value = yr;
            opt.textContent = yr;
            if (yr == new Date().getFullYear()) opt.selected = true;
            yearSelect.appendChild(opt);
        });
        
        onTrendModeChange(modeSelect.value);
    } else {
        // Specific project: enable monthly, fetch project timelines range and targets
        modeSelect.querySelector("option[value='monthly']").disabled = false;
        
        fetch(`<?= $base ?>/all-projects-overview?ajax_get_targets=1&project_id=${projectId}`)
            .then(res => res.json())
            .then(data => {
                // Populate targets
                targetSelect.innerHTML = '';
                if (data.targets && data.targets.length > 0) {
                    data.targets.forEach(t => {
                        const opt = document.createElement('option');
                        opt.value = t.id;
                        opt.textContent = t.label;
                        targetSelect.appendChild(opt);
                    });
                } else {
                    const opt = document.createElement('option');
                    opt.value = '0';
                    opt.textContent = 'No Targets Set';
                    targetSelect.appendChild(opt);
                }

                // Populate dynamic years from project timeline
                yearSelect.innerHTML = '';
                if (data.years && data.years.length > 0) {
                    data.years.forEach(yr => {
                        const opt = document.createElement('option');
                        opt.value = yr;
                        opt.textContent = yr;
                        if (yr == new Date().getFullYear()) opt.selected = true;
                        yearSelect.appendChild(opt);
                    });
                } else {
                    const opt = document.createElement('option');
                    opt.value = new Date().getFullYear();
                    opt.textContent = new Date().getFullYear();
                    yearSelect.appendChild(opt);
                }

                loadTrendData();
            });
    }
}

function onTrendModeChange(mode) {
    const targetSelect = document.getElementById('trendTargetSelect');
    const yearSelect = document.getElementById('trendYearSelect');

    if (mode === 'monthly') {
        targetSelect.style.display = 'inline-block';
        yearSelect.style.display = 'none';
    } else if (mode === 'yearly') {
        targetSelect.style.display = 'none';
        yearSelect.style.display = 'inline-block';
    } else {
        targetSelect.style.display = 'none';
        yearSelect.style.display = 'none';
    }

    loadTrendData();
}

function loadTrendData() {
    const projectId = document.getElementById('trendProjectSelect').value;
    const mode = document.getElementById('trendModeSelect').value;
    const targetId = document.getElementById('trendTargetSelect').value;
    const year = document.getElementById('trendYearSelect').value;

    let url = `<?= $base ?>/all-projects-overview?ajax_get_trend_data=1&project_id=${projectId}&mode=${mode}`;
    if (mode === 'monthly') {
        url += `&target_id=${targetId}`;
    } else if (mode === 'yearly') {
        url += `&year=${year}`;
    }

    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (progressTrendChartInstance) {
                // Update labels and datasets
                progressTrendChartInstance.data.labels = data.labels;
                progressTrendChartInstance.data.datasets[0].data = data.planned;
                progressTrendChartInstance.data.datasets[1].data = data.actual;
                
                // Adjust styling based on monthly target weekly breakdown vs cumulative scales
                if (mode === 'monthly') {
                    progressTrendChartInstance.data.datasets[0].pointRadius = 4;
                    progressTrendChartInstance.data.datasets[0].pointBackgroundColor = 'rgba(99, 102, 241, 0.35)';
                    progressTrendChartInstance.data.datasets[0].borderDash = [];
                } else {
                    progressTrendChartInstance.data.datasets[0].pointRadius = 0;
                    progressTrendChartInstance.data.datasets[0].borderDash = [5, 5];
                }
                
                progressTrendChartInstance.update();

                // Compute overall planned and actual metrics from data array to show on sidebar metrics
                const cleanActual = data.actual.filter(x => x !== null);
                const latestActualVal = cleanActual.length > 0 ? cleanActual[cleanActual.length - 1] : 0;
                const latestPlannedVal = data.planned.length > 0 ? data.planned[data.planned.length - 1] : 0;

                document.getElementById('chartActualMetric').textContent = Number(latestActualVal).toFixed(0) + '%';
                document.getElementById('chartPlannedMetric').textContent = Number(latestPlannedVal).toFixed(0) + '%';
            }
        });
}

// Table AJAX Pagination & Search
let debounceTimer = null;
function onFilterChangeDebounced() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        fetchPaginatedProjects(1);
    }, 300);
}

function fetchPaginatedProjects(page) {
    const search = document.getElementById('overviewSearch').value;
    const status = document.getElementById('overviewStatus').value;
    const sort = document.getElementById('overviewSort').value;
    const tbody = document.getElementById('projectTableBody');
    
    tbody.style.opacity = '0.5';

    fetch(`<?= $base ?>/all-projects-overview?ajax=1&page=${page}&search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}&sort=${encodeURIComponent(sort)}`)
        .then(res => res.json())
        .then(data => {
            tbody.innerHTML = data.rows_html;
            document.getElementById('paginationControls').innerHTML = data.pagination_html;
            tbody.style.opacity = '1.0';

            // Animate bars
            document.querySelectorAll('.prog-bar-fill-actual').forEach(bar => {
                const w = bar.style.width;
                bar.style.width = '0';
                setTimeout(() => { bar.style.width = w; }, 100);
            });
        })
        .catch(err => {
            tbody.style.opacity = '1.0';
            console.error('Error fetching paginated projects:', err);
        });
}

// Collapsible nested detail elements toggle
function togglePhaseItems(containerId, toggleIcon) {
    const container = document.getElementById(containerId);
    if (!container) return;

    if (container.style.display === 'none' || container.style.display === '') {
        container.style.display = 'table-row';
        if (toggleIcon) {
            if (toggleIcon.classList.contains('fa-chevron-right')) {
                toggleIcon.classList.remove('fa-chevron-right');
                toggleIcon.classList.add('fa-chevron-down');
            } else if (toggleIcon.classList.contains('fa-eye')) {
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            }
        }
    } else {
        container.style.display = 'none';
        if (toggleIcon) {
            if (toggleIcon.classList.contains('fa-chevron-down')) {
                toggleIcon.classList.remove('fa-chevron-down');
                toggleIcon.classList.add('fa-chevron-right');
            } else if (toggleIcon.classList.contains('fa-eye-slash')) {
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    }
}
</script>
