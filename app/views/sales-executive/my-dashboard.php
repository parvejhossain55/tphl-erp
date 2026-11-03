<style>
    .dashboard-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.5rem 0;
        margin-bottom: 1.5rem;
        border-radius: 0 0 15px 15px;
    }

    /* ── Accent Color Variables ── */
    .accent-blue      { --accent: #2563eb; --accent-light: #eff6ff; --accent-dark-bg: rgba(37, 99, 235, 0.16); }
    .accent-green     { --accent: #16a34a; --accent-light: #f0fdf4; --accent-dark-bg: rgba(22, 163, 74, 0.16); }
    .accent-amber     { --accent: #d97706; --accent-light: #fffbeb; --accent-dark-bg: rgba(217, 119, 6, 0.16); }
    .accent-indigo    { --accent: #4f46e5; --accent-light: #eef2ff; --accent-dark-bg: rgba(79, 70, 229, 0.16); }
    .accent-emerald   { --accent: #059669; --accent-light: #ecfdf5; --accent-dark-bg: rgba(5, 150, 105, 0.16); }
    .accent-orange    { --accent: #ea580c; --accent-light: #fff7ed; --accent-dark-bg: rgba(234, 88, 12, 0.16); }
    .accent-teal      { --accent: #0d9488; --accent-light: #f0fdfa; --accent-dark-bg: rgba(13, 148, 136, 0.16); }
    .accent-red       { --accent: #dc2626; --accent-light: #fef2f2; --accent-dark-bg: rgba(220, 38, 38, 0.16); }
    .accent-sky       { --accent: #0284c7; --accent-light: #f0f9ff; --accent-dark-bg: rgba(2, 132, 199, 0.16); }
    .accent-cyan      { --accent: #0891b2; --accent-light: #ecfeff; --accent-dark-bg: rgba(8, 145, 178, 0.16); }
    .accent-rose      { --accent: #e11d48; --accent-light: #fff1f2; --accent-dark-bg: rgba(225, 29, 72, 0.16); }
    .accent-purple    { --accent: #7c3aed; --accent-light: #faf5ff; --accent-dark-bg: rgba(124, 58, 237, 0.16); }
    .accent-fuchsia   { --accent: #c026d3; --accent-light: #fdf4ff; --accent-dark-bg: rgba(192, 38, 211, 0.16); }
    .accent-violet    { --accent: #6366f1; --accent-light: #f5f3ff; --accent-dark-bg: rgba(99, 102, 241, 0.16); }
    .accent-slate     { --accent: #475569; --accent-light: #f8fafc; --accent-dark-bg: rgba(71, 85, 105, 0.16); }
    .accent-tangerine { --accent: #ea580c; --accent-light: #fff7ed; --accent-dark-bg: rgba(234, 88, 12, 0.16); }

    /* ── Base Card (Light Mode default) ── */
    .stat-card {
        background: #ffffff;
        border: 1px solid #e2e8f0 !important;
        border-left: 3.5px solid var(--accent, #2563eb) !important;
        border-radius: 8px;
        padding: 0.5rem 0.65rem !important;
        min-height: 64px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        position: relative;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06) !important;
        border-color: #cbd5e1 !important;
        border-left-color: var(--accent, #2563eb) !important;
    }

    .stat-card .stat-value {
        color: #0f172a !important;
        font-size: 1.25rem !important;
        font-weight: 700 !important;
        line-height: 1.15;
        margin: 0 !important;
        letter-spacing: -0.3px;
    }

    .stat-card .stat-label {
        color: #64748b !important;
        font-size: 9.5px !important;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.35px;
        margin-bottom: 2px !important;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: flex;
        align-items: center;
    }

    .stat-card .stat-label i {
        color: var(--accent);
        margin-right: 4px;
    }

    .stat-card .card-hint {
        color: #94a3b8 !important;
        font-size: 9px !important;
        display: block;
        margin-top: 1px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .stat-card .stat-unit {
        font-size: 9.5px;
        font-weight: 600;
        color: #64748b;
    }

    .stat-card .stat-icon-badge {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        flex-shrink: 0;
        background: var(--accent-light, #f1f5f9);
        color: var(--accent, #2563eb);
        transition: transform 0.18s ease;
    }

    .stat-card:hover .stat-icon-badge {
        transform: scale(1.08);
    }

    .stat-pill-badge {
        font-size: 9px;
        font-weight: 600;
        padding: 1.5px 5px;
        border-radius: 9999px;
        line-height: 1.2;
        background: var(--accent-light, #f1f5f9);
        color: var(--accent, #2563eb);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    /* ── Clickable Link & Arrow ── */
    a.stat-card-link {
        display: block;
        text-decoration: none;
        color: inherit;
        border-radius: 8px;
    }
    a.stat-card-link .card-nav-arrow {
        position: absolute;
        top: 5px; 
        right: 7px;
        font-size: 0.55rem;
        color: var(--accent, #64748b);
        opacity: 0;
        transition: opacity 0.18s ease, transform 0.18s ease;
    }
    a.stat-card-link:hover .card-nav-arrow {
        opacity: 0.75;
        transform: translateX(2px);
    }

    /* ── DARK THEME COMPATIBILITY ── */
    [bd-theme="bd-theme-dark"] .stat-card,
    [data-theme="dark"] .stat-card,
    body.dark .stat-card,
    .dark .stat-card {
        background: #1e293b !important;
        border-color: #334155 !important;
        border-left-color: var(--accent) !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25) !important;
    }

    [bd-theme="bd-theme-dark"] .stat-card:hover,
    [data-theme="dark"] .stat-card:hover,
    body.dark .stat-card:hover,
    .dark .stat-card:hover {
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.45) !important;
        border-color: #475569 !important;
        border-left-color: var(--accent) !important;
    }

    [bd-theme="bd-theme-dark"] .stat-card .stat-value,
    [data-theme="dark"] .stat-card .stat-value,
    body.dark .stat-card .stat-value,
    .dark .stat-card .stat-value {
        color: #f8fafc !important;
    }

    [bd-theme="bd-theme-dark"] .stat-card .stat-label,
    [data-theme="dark"] .stat-card .stat-label,
    body.dark .stat-card .stat-label,
    .dark .stat-card .stat-label {
        color: #94a3b8 !important;
    }

    [bd-theme="bd-theme-dark"] .stat-card .card-hint,
    [data-theme="dark"] .stat-card .card-hint,
    body.dark .stat-card .card-hint,
    .dark .stat-card .card-hint {
        color: #64748b !important;
    }

    [bd-theme="bd-theme-dark"] .stat-card .stat-unit,
    [data-theme="dark"] .stat-card .stat-unit,
    body.dark .stat-card .stat-unit,
    .dark .stat-card .stat-unit {
        color: #94a3b8 !important;
    }

    [bd-theme="bd-theme-dark"] .stat-card .stat-icon-badge,
    [data-theme="dark"] .stat-card .stat-icon-badge,
    body.dark .stat-card .stat-icon-badge,
    .dark .stat-card .stat-icon-badge {
        background: var(--accent-dark-bg, rgba(255, 255, 255, 0.08)) !important;
        color: var(--accent) !important;
    }

    [bd-theme="bd-theme-dark"] .stat-pill-badge,
    [data-theme="dark"] .stat-pill-badge,
    body.dark .stat-pill-badge,
    .dark .stat-pill-badge {
        background: var(--accent-dark-bg, rgba(255, 255, 255, 0.1)) !important;
        color: #f8fafc !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    /* ── Responsive Behavior across Devices ── */
    @media (max-width: 576px) {
        .stat-card {
            padding: 0.4rem 0.5rem !important;
            min-height: 58px;
        }
        .stat-card .stat-value {
            font-size: 1.1rem !important;
        }
        .stat-card .stat-label {
            font-size: 8.5px !important;
        }
        .stat-card .card-hint {
            font-size: 8px !important;
        }
        .stat-card .stat-icon-badge {
            width: 26px;
            height: 26px;
            font-size: 0.75rem;
        }
    }

    @media (max-width: 400px) {
        .stat-card .stat-icon-badge {
            display: none !important;
        }
    }

    .chart-container {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .chart-title {
        font-size: 1.5rem;
        font-weight: bold;
        margin-bottom: 1.5rem;
    }

    .info-box {
        background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
        border-left: 4px solid #667eea;
        border-radius: 8px;
        padding: 1rem 1.5rem;
        margin-top: 1.5rem;
    }

    .info-box strong {
        color: #667eea;
    }
</style>

<!-- Dashboard Header -->

<div class="app__slide-wrapper">

    <?php
    $startFilterYear = 2025;
    $endFilterYear = 2035;
    $monthsList = [
        1 => 'January',
        2 => 'February',
        3 => 'March',
        4 => 'April',
        5 => 'May',
        6 => 'June',
        7 => 'July',
        8 => 'August',
        9 => 'September',
        10 => 'October',
        11 => 'November',
        12 => 'December'
    ];

    if (empty($titlePeriod)) {
        if (isset($filterType) && $filterType === 'yearly') {
            $titlePeriod = 'Year ' . ($selectedYear ?? date('Y'));
        } else {
            $titlePeriod = $monthsList[$selectedStartMonth] ?? date('F');
            if ($selectedStartMonth !== $selectedEndMonth) {
                $titlePeriod .= ' - ' . ($monthsList[$selectedEndMonth] ?? date('F'));
            }
            $titlePeriod .= ' ' . ($selectedYear ?? date('Y'));
        }
    }

    $leadQueryDate = !empty($hasFilter) && !empty($fromDate) && !empty($toDate)
        ? ('from_date=' . urlencode($fromDate) . '&to_date=' . urlencode($toDate) . '&date_type=lead_date')
        : '';
    ?>

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="mb-0 fw-bold">Executive Dashboard</h4>
            <p class="mb-0 opacity-75 small text-muted">Summary & Performance Metrics</p>
        </div>
        <?php if (!empty($hasFilter)): ?>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary px-3 py-2" style="font-size: 0.8rem; border-radius: 6px;">
                    <i class="fa-solid fa-calendar-check me-1"></i> <?= htmlspecialchars($titlePeriod) ?>
                </span>
                <a href="<?= $GLOBALS['base_url'] ?>/my-dashboard?preset=all_time" class="btn btn-sm btn-outline-secondary" style="border-radius: 6px;">
                    <i class="fa-solid fa-rotate-left me-1"></i> Reset
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- TOP FILTER TOOLBAR -->
    <div class="card__wrapper shadow-sm p-3 mb-3" style="border-radius: 12px; background: #ffffff;">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <!-- Preset Buttons Group -->
            <div class="btn-group flex-wrap gap-1" role="group" aria-label="Dashboard Presets">
                <a href="?preset=today"
                    class="btn btn-sm <?= ($activePreset ?? '') === 'today' ? 'btn-primary text-white shadow-xs' : 'btn-outline-secondary' ?>"
                    style="border-radius: 6px; font-weight: 600; font-size: 11.5px; padding: 5px 12px;">
                    <i class="fa-solid fa-clock me-1"></i> Today
                </a>

                <a href="?preset=yesterday"
                    class="btn btn-sm <?= ($activePreset ?? '') === 'yesterday' ? 'btn-primary text-white shadow-xs' : 'btn-outline-secondary' ?>"
                    style="border-radius: 6px; font-weight: 600; font-size: 11.5px; padding: 5px 12px;">
                    <i class="fa-solid fa-clock-rotate-left me-1"></i> Yesterday
                </a>

                <a href="?preset=this_month"
                    class="btn btn-sm <?= ($activePreset ?? '') === 'this_month' ? 'btn-primary text-white shadow-xs' : 'btn-outline-secondary' ?>"
                    style="border-radius: 6px; font-weight: 600; font-size: 11.5px; padding: 5px 12px;">
                    <i class="fa-solid fa-calendar-days me-1"></i> This Month
                </a>

                <a href="<?= $GLOBALS['base_url'] ?>/my-dashboard?preset=all_time"
                    class="btn btn-sm <?= empty($hasFilter) || ($activePreset ?? '') === 'all_time' ? 'btn-primary text-white shadow-xs' : 'btn-outline-secondary' ?>"
                    style="border-radius: 6px; font-weight: 600; font-size: 11.5px; padding: 5px 12px;">
                    <i class="fa-solid fa-infinity me-1"></i> All Time
                </a>
            </div>

            <!-- Custom Date Range Form & Month/Year Toggle -->
            <form action="<?= $GLOBALS['base_url'] ?>/my-dashboard" method="GET" class="d-flex flex-wrap gap-2 align-items-center m-0">
                <input type="hidden" name="preset" value="custom">
                <div class="d-flex align-items-center gap-1">
                    <div class="input-group input-group-sm" style="width: auto;">
                        <span class="input-group-text bg-light text-muted" style="font-size: 11px;"><i class="fa-solid fa-calendar-day"></i></span>
                        <input type="date" name="from_date" class="form-control form-control-sm" 
                               value="<?= htmlspecialchars($fromDate ?? '') ?>" 
                               required style="max-width: 140px; font-size: 11.5px;" title="From Date">
                    </div>
                    <span class="small text-muted fw-bold px-1">to</span>
                    <div class="input-group input-group-sm" style="width: auto;">
                        <span class="input-group-text bg-light text-muted" style="font-size: 11px;"><i class="fa-solid fa-calendar-day"></i></span>
                        <input type="date" name="to_date" class="form-control form-control-sm" 
                               value="<?= htmlspecialchars($toDate ?? '') ?>" 
                               required style="max-width: 140px; font-size: 11.5px;" title="To Date">
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary px-3" style="border-radius: 6px; font-weight: 600; height: 31px;">
                        <i class="fa-solid fa-filter me-1"></i> Filter
                    </button>
                </div>

                <div class="d-flex align-items-center gap-1">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="toggleMonthYearCollapse" 
                            style="border-radius: 6px; font-size: 11.5px; height: 31px;" title="Filter by Month/Year">
                        <i class="fa-solid fa-sliders me-1"></i> Month / Year
                    </button>
                    <?php if (!empty($hasFilter)): ?>
                        <a href="<?= $GLOBALS['base_url'] ?>/my-dashboard?preset=all_time" class="btn btn-sm btn-outline-danger" 
                           style="border-radius: 6px; font-size: 11.5px; height: 31px;" title="Reset all filters">
                            <i class="fa-solid fa-rotate-left me-1"></i> Reset
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Collapsible Month / Year Filter Options -->
        <div id="monthYearCollapseSection" style="display: <?= in_array($activePreset ?? '', ['monthly', 'yearly']) ? 'block' : 'none' ?>;" class="pt-2 border-top mt-2">
            <form action="<?= $GLOBALS['base_url'] ?>/my-dashboard" method="GET" class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1" style="font-size: 11px;"><i class="fa-solid fa-layer-group me-1"></i> Filter Type</label>
                    <select class="form-select form-select-sm" name="filter_type" id="filterTypeSelect" style="border-radius: 6px;">
                        <option value="monthly" <?= ($filterType === 'monthly') ? 'selected' : '' ?>>Monthly</option>
                        <option value="yearly" <?= ($filterType === 'yearly') ? 'selected' : '' ?>>Yearly</option>
                    </select>
                </div>
                <div class="col-6 col-md-3" id="yearCol">
                    <label class="form-label small fw-bold text-muted mb-1" style="font-size: 11px;"><i class="fa-solid fa-calendar me-1"></i> Year</label>
                    <select class="form-select form-select-sm" name="year" style="border-radius: 6px;">
                        <?php for ($y = $startFilterYear; $y <= $endFilterYear; $y++): ?>
                            <option value="<?= $y ?>" <?= ($selectedYear == $y) ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2" id="startMonthCol">
                    <label class="form-label small fw-bold text-muted mb-1" style="font-size: 11px;"><i class="fa-solid fa-calendar-day me-1"></i> Start Month</label>
                    <select class="form-select form-select-sm" name="start_month" style="border-radius: 6px;">
                        <?php foreach ($monthsList as $num => $name): ?>
                            <option value="<?= $num ?>" <?= ($selectedStartMonth == $num) ? 'selected' : '' ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2" id="endMonthCol">
                    <label class="form-label small fw-bold text-muted mb-1" style="font-size: 11px;"><i class="fa-solid fa-calendar-day me-1"></i> End Month</label>
                    <select class="form-select form-select-sm" name="end_month" style="border-radius: 6px;">
                        <?php foreach ($monthsList as $num => $name): ?>
                            <option value="<?= $num ?>" <?= ($selectedEndMonth == $num) ? 'selected' : '' ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100" style="border-radius: 6px; height: 31px;">
                        <i class="fa-solid fa-check me-1"></i> Apply
                    </button>
                </div>
            </form>
        </div>

        <!-- Active Filter Badge / Feedback -->
        <?php if (!empty($hasFilter)): ?>
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pt-2 border-top mt-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="small text-muted fw-bold"><i class="fa-solid fa-filter me-1 text-primary"></i> Active Period:</span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1" style="font-size: 12px; border-radius: 6px;">
                        <i class="fa-solid fa-calendar-check me-1"></i> <?= htmlspecialchars($titlePeriod) ?>
                    </span>
                    <span class="small text-muted d-none d-md-inline">&bull; 16 metric cards & chart dynamically synchronized</span>
                </div>
                <a href="<?= $GLOBALS['base_url'] ?>/my-dashboard?preset=all_time" class="btn btn-sm btn-link text-decoration-none text-danger p-0" style="font-size: 11.5px;">
                    <i class="fa-solid fa-circle-xmark me-1"></i> Clear Filter (All-Time)
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- 16 CORE SERIAL METRICS CARDS -->
    <div class="row g-2 mb-3 align-items-stretch">

        <!-- 1. Total Leads Assigned -->
        <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-2">
            <a href="<?= $GLOBALS['base_url'] ?>/my-all-assigned-leads<?= !empty($leadQueryDate) ? '?' . $leadQueryDate : '' ?>" class="stat-card-link">
                <div class="stat-card accent-blue shadow-xs h-100">
                    <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <div class="overflow-hidden pe-1 flex-grow-1">
                            <span class="stat-label"><i class="fa-solid fa-users"></i> <?= !empty($hasFilter) ? 'Period Leads Assigned' : 'Total Leads Assigned' ?></span>
                            <div class="d-flex align-items-baseline gap-1 mt-1">
                                <span class="stat-value"><?= number_format($totalLeadsAssigned ?? $getAllLeadsCount ?? 0) ?></span>
                            </div>
                            <span class="card-hint"><?= !empty($hasFilter) ? 'All-Time: ' . number_format($allTimeLeadsAssigned ?? 0) . ' &rarr;' : 'All assigned leads &rarr;' ?></span>
                        </div>
                        <div class="stat-icon-badge"><i class="fa-solid fa-users"></i></div>
                    </div>
                </div>
            </a>
        </div>

        <!-- 2. Total Leads Contacted (with % Contacted) -->
        <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-2">
            <a href="<?= $GLOBALS['base_url'] ?>/my-all-assigned-leads?filter_card=contacted<?= !empty($leadQueryDate) ? '&' . $leadQueryDate : '' ?>" class="stat-card-link">
                <div class="stat-card accent-green shadow-xs h-100">
                    <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <div class="overflow-hidden pe-1 flex-grow-1">
                            <span class="stat-label"><i class="fa-solid fa-phone-volume"></i> <?= !empty($hasFilter) ? 'Period Contacted' : 'Total Leads Contacted' ?></span>
                            <div class="d-flex align-items-baseline gap-1 mt-1">
                                <span class="stat-value"><?= number_format($totalLeadsContacted ?? 0) ?></span>
                                <span class="stat-pill-badge">
                                    <?= $totalContactedPct ?? 0 ?>%
                                </span>
                            </div>
                            <span class="card-hint"><?= !empty($hasFilter) ? 'All-Time: ' . number_format($allTimeLeadsContacted ?? 0) . ' &rarr;' : 'Contacted leads &rarr;' ?></span>
                        </div>
                        <div class="stat-icon-badge"><i class="fa-solid fa-phone-volume"></i></div>
                    </div>
                </div>
            </a>
        </div>

        <!-- 3. Total Not Contacted -->
        <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-2">
            <a href="<?= $GLOBALS['base_url'] ?>/my-all-assigned-leads?filter_card=not_contacted<?= !empty($leadQueryDate) ? '&' . $leadQueryDate : '' ?>" class="stat-card-link">
                <div class="stat-card accent-amber shadow-xs h-100">
                    <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <div class="overflow-hidden pe-1 flex-grow-1">
                            <span class="stat-label"><i class="fa-solid fa-phone-slash"></i> <?= !empty($hasFilter) ? 'Period Not Contacted' : 'Total Not Contacted' ?></span>
                            <div class="d-flex align-items-baseline gap-1 mt-1">
                                <span class="stat-value"><?= number_format($totalNotContacted ?? 0) ?></span>
                                <span class="stat-pill-badge">
                                    <?= round(100 - ($totalContactedPct ?? 0), 1) ?>%
                                </span>
                            </div>
                            <span class="card-hint"><?= !empty($hasFilter) ? 'All-Time: ' . number_format($allTimeNotContacted ?? 0) . ' &rarr;' : 'Pending initial contact &rarr;' ?></span>
                        </div>
                        <div class="stat-icon-badge"><i class="fa-solid fa-phone-slash"></i></div>
                    </div>
                </div>
            </a>
        </div>

        <!-- 4. Today Leads Assigned -->
        <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-2">
            <a href="<?= $GLOBALS['base_url'] ?>/today-leads" class="stat-card-link">
                <div class="stat-card accent-indigo shadow-xs h-100">
                    <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <div class="overflow-hidden pe-1 flex-grow-1">
                            <span class="stat-label"><i class="fa-solid fa-user-plus"></i> Today Leads Assigned</span>
                            <div class="d-flex align-items-baseline gap-1 mt-1">
                                <span class="stat-value"><?= number_format($todayLeadsAssigned ?? $todayLeadsCount ?? 0) ?></span>
                            </div>
                            <span class="card-hint">Assigned today (<?= date('d M') ?>) &rarr;</span>
                        </div>
                        <div class="stat-icon-badge"><i class="fa-solid fa-user-plus"></i></div>
                    </div>
                </div>
            </a>
        </div>

        <!-- 5. Today Leads Contacted -->
        <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-2">
            <a href="<?= $GLOBALS['base_url'] ?>/today-leads?filter_card=contacted" class="stat-card-link">
                <div class="stat-card accent-emerald shadow-xs h-100">
                    <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <div class="overflow-hidden pe-1 flex-grow-1">
                            <span class="stat-label"><i class="fa-solid fa-headset"></i> Today Leads Contacted</span>
                            <div class="d-flex align-items-baseline gap-1 mt-1">
                                <span class="stat-value"><?= number_format($todayLeadsContacted ?? 0) ?></span>
                            </div>
                            <span class="card-hint">Follow-up complete today &rarr;</span>
                        </div>
                        <div class="stat-icon-badge"><i class="fa-solid fa-headset"></i></div>
                    </div>
                </div>
            </a>
        </div>

        <!-- 6. Today Not Contacted -->
        <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-2">
            <a href="<?= $GLOBALS['base_url'] ?>/today-leads?filter_card=not_contacted" class="stat-card-link">
                <div class="stat-card accent-orange shadow-xs h-100">
                    <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <div class="overflow-hidden pe-1 flex-grow-1">
                            <span class="stat-label"><i class="fa-solid fa-user-clock"></i> Today Not Contacted</span>
                            <div class="d-flex align-items-baseline gap-1 mt-1">
                                <span class="stat-value"><?= number_format($todayNotContacted ?? 0) ?></span>
                            </div>
                            <span class="card-hint">Remaining today leads &rarr;</span>
                        </div>
                        <div class="stat-icon-badge"><i class="fa-solid fa-user-clock"></i></div>
                    </div>
                </div>
            </a>
        </div>

        <!-- 7. Total Follow-Up Complete -->
        <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-2">
            <a href="<?= $GLOBALS['base_url'] ?>/my-all-assigned-leads?filter_card=contacted<?= !empty($leadQueryDate) ? '&' . $leadQueryDate : '' ?>" class="stat-card-link">
                <div class="stat-card accent-teal shadow-xs h-100">
                    <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <div class="overflow-hidden pe-1 flex-grow-1">
                            <span class="stat-label"><i class="fa-solid fa-clipboard-check"></i> Total Follow-Up Complete</span>
                            <div class="d-flex align-items-baseline gap-1 mt-1">
                                <span class="stat-value"><?= number_format($totalFollowUpComplete ?? 0) ?></span>
                            </div>
                            <span class="card-hint">All completed follow-ups &rarr;</span>
                        </div>
                        <div class="stat-icon-badge"><i class="fa-solid fa-clipboard-check"></i></div>
                    </div>
                </div>
            </a>
        </div>

        <!-- 8. Total Follow-Up Missing -->
        <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-2">
            <a href="<?= $GLOBALS['base_url'] ?>/my-all-assigned-leads?filter=missing_followup<?= !empty($leadQueryDate) ? '&' . $leadQueryDate : '' ?>" class="stat-card-link">
                <div class="stat-card accent-red shadow-xs h-100">
                    <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <div class="overflow-hidden pe-1 flex-grow-1">
                            <span class="stat-label"><i class="fa-solid fa-calendar-xmark"></i> <?= !empty($hasFilter) ? 'Period Follow-Up Missing' : 'Total Follow-Up Missing' ?></span>
                            <div class="d-flex align-items-baseline gap-1 mt-1">
                                <span class="stat-value text-danger"><?= number_format($totalFollowUpMissing ?? $missingFollowUpCount ?? 0) ?></span>
                            </div>
                            <span class="card-hint"><?= !empty($hasFilter) ? 'All-Time: ' . number_format($allTimeFollowUpMissing ?? 0) . ' &rarr;' : 'Overdue follow-ups &rarr;' ?></span>
                        </div>
                        <div class="stat-icon-badge"><i class="fa-solid fa-calendar-xmark"></i></div>
                    </div>
                </div>
            </a>
        </div>

        <!-- 9. Today Follow-Ups (Scheduled for Today) -->
        <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-2">
            <a href="<?= $GLOBALS['base_url'] ?>/my-all-assigned-leads?followup_date=<?= date('Y-m-d') ?>" class="stat-card-link">
                <div class="stat-card accent-sky shadow-xs h-100">
                    <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <div class="overflow-hidden pe-1 flex-grow-1">
                            <span class="stat-label"><i class="fa-solid fa-calendar-day"></i> Today Follow-Ups</span>
                            <div class="d-flex align-items-baseline gap-1 mt-1">
                                <span class="stat-value"><?= number_format($todayFollowUps ?? 0) ?></span>
                            </div>
                            <span class="card-hint">Scheduled for today (<?= date('d M') ?>) &rarr;</span>
                        </div>
                        <div class="stat-icon-badge"><i class="fa-solid fa-calendar-day"></i></div>
                    </div>
                </div>
            </a>
        </div>

        <!-- 10. Today Follow-Up Complete -->
        <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-2">
            <a href="<?= $GLOBALS['base_url'] ?>/my-all-assigned-leads?followup_date=<?= date('Y-m-d') ?>" class="stat-card-link">
                <div class="stat-card accent-cyan shadow-xs h-100">
                    <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <div class="overflow-hidden pe-1 flex-grow-1">
                            <span class="stat-label"><i class="fa-solid fa-circle-check"></i> Today Follow-Up Complete</span>
                            <div class="d-flex align-items-baseline gap-1 mt-1">
                                <span class="stat-value"><?= number_format($todayFollowUpComplete ?? 0) ?></span>
                            </div>
                            <span class="card-hint">Completed of today &rarr;</span>
                        </div>
                        <div class="stat-icon-badge"><i class="fa-solid fa-circle-check"></i></div>
                    </div>
                </div>
            </a>
        </div>

        <!-- 11. Remaining Today Follow-Up -->
        <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-2">
            <a href="<?= $GLOBALS['base_url'] ?>/my-all-assigned-leads?followup_date=<?= date('Y-m-d') ?>" class="stat-card-link">
                <div class="stat-card accent-rose shadow-xs h-100">
                    <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <div class="overflow-hidden pe-1 flex-grow-1">
                            <span class="stat-label"><i class="fa-solid fa-hourglass-half"></i> Remaining Today Follow-Up</span>
                            <div class="d-flex align-items-baseline gap-1 mt-1">
                                <span class="stat-value"><?= number_format($remainingTodayFollowUp ?? 0) ?></span>
                            </div>
                            <span class="card-hint">Pending of today &rarr;</span>
                        </div>
                        <div class="stat-icon-badge"><i class="fa-solid fa-hourglass-half"></i></div>
                    </div>
                </div>
            </a>
        </div>

        <!-- 12. Total Sales / Booked (Units) -->
        <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-2">
            <a href="<?= $GLOBALS['base_url'] ?>/my-all-assigned-leads?filter_card=booked" class="stat-card-link">
                <div class="stat-card accent-purple shadow-xs h-100">
                    <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <div class="overflow-hidden pe-1 flex-grow-1">
                            <span class="stat-label"><i class="fa-solid fa-bookmark"></i> Total Sales / Booked</span>
                            <div class="d-flex align-items-baseline gap-1 mt-1">
                                <span class="stat-value"><?= number_format($totalSalesBookedUnits ?? $myTotalShareSold['total_flats'] ?? 0) ?></span>
                                <span class="stat-unit ms-1">Units</span>
                            </div>
                            <span class="card-hint">All booked units &rarr;</span>
                        </div>
                        <div class="stat-icon-badge"><i class="fa-solid fa-bookmark"></i></div>
                    </div>
                </div>
            </a>
        </div>

        <!-- 13. Monthly Booking Target -->
        <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-2">
            <div class="stat-card accent-fuchsia shadow-xs h-100">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div class="overflow-hidden pe-1 flex-grow-1">
                        <span class="stat-label"><i class="fa-solid fa-bullseye"></i> Target</span>
                        <div class="d-flex align-items-baseline gap-1 mt-1">
                            <span class="stat-value"><?= number_format($monthlyBookingTarget ?? 0) ?></span>
                            <span class="stat-unit ms-1">Units</span>
                        </div>
                        <span class="card-hint">From set-target (<?= htmlspecialchars($titlePeriod) ?>)</span>
                    </div>
                    <div class="stat-icon-badge"><i class="fa-solid fa-bullseye"></i></div>
                </div>
            </div>
        </div>

        <!-- 14. Period / Current Month Booking -->
        <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-2">
            <a href="<?= $GLOBALS['base_url'] ?>/my-all-assigned-leads?filter_card=booked<?= !empty($leadQueryDate) ? '&' . $leadQueryDate : '' ?>" class="stat-card-link">
                <div class="stat-card accent-violet shadow-xs h-100">
                    <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <div class="overflow-hidden pe-1 flex-grow-1">
                            <span class="stat-label"><i class="fa-solid fa-file-signature"></i> <?= !empty($hasFilter) ? 'Period Booking' : 'Current Month Booking' ?></span>
                            <div class="d-flex align-items-baseline gap-1 mt-1">
                                <span class="stat-value"><?= number_format($currentMonthBooking ?? $monthlyBookingComplete ?? 0) ?></span>
                                <span class="stat-unit ms-1">Units</span>
                                <?php if (!empty($currentMonthBookedLeads)): ?>
                                    <span class="badge bg-light text-dark ms-1" style="font-size: 8.5px; border: 1px solid #e2e8f0;"><?= $currentMonthBookedLeads ?> Leads</span>
                                <?php endif; ?>
                                <?php if (!empty($monthlyBookingTarget) && (int)$monthlyBookingTarget > 0): ?>
                                    <span class="stat-pill-badge ms-auto">
                                        <?= $bookingAchievement ?? 0 ?>%
                                    </span>
                                <?php endif; ?>
                            </div>
                            <span class="card-hint">Booked in <?= htmlspecialchars($titlePeriod) ?> &rarr;</span>
                        </div>
                        <div class="stat-icon-badge"><i class="fa-solid fa-file-signature"></i></div>
                    </div>
                </div>
            </a>
        </div>

        <!-- 15. Total Visited Complete -->
        <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-2">
            <a href="<?= $GLOBALS['base_url'] ?>/my-all-assigned-leads?last_action=visited" class="stat-card-link">
                <div class="stat-card accent-slate shadow-xs h-100">
                    <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <div class="overflow-hidden pe-1 flex-grow-1">
                            <span class="stat-label"><i class="fa-solid fa-location-dot"></i> Total Visited Complete</span>
                            <div class="d-flex align-items-baseline gap-1 mt-1">
                                <span class="stat-value"><?= number_format($totalVisitedComplete ?? 0) ?></span>
                            </div>
                            <span class="card-hint">All-time visited leads &rarr;</span>
                        </div>
                        <div class="stat-icon-badge"><i class="fa-solid fa-location-dot"></i></div>
                    </div>
                </div>
            </a>
        </div>

        <!-- 16. Period / This Month Visited -->
        <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-2">
            <a href="<?= $GLOBALS['base_url'] ?>/my-all-assigned-leads?last_action=visited<?= !empty($leadQueryDate) ? '&' . $leadQueryDate : '' ?>" class="stat-card-link">
                <div class="stat-card accent-tangerine shadow-xs h-100">
                    <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <div class="overflow-hidden pe-1 flex-grow-1">
                            <span class="stat-label"><i class="fa-solid fa-person-walking"></i> <?= !empty($hasFilter) ? 'Period Visited' : 'This Month Visited' ?></span>
                            <div class="d-flex align-items-baseline gap-1 mt-1">
                                <span class="stat-value"><?= number_format($thisMonthVisited ?? $monthlyVisitComplete ?? 0) ?></span>
                                <?php if (!empty($thisMonthVisitedLeads)): ?>
                                    <span class="badge bg-light text-dark ms-1" style="font-size: 8.5px; border: 1px solid #e2e8f0;"><?= $thisMonthVisitedLeads ?> Leads</span>
                                <?php endif; ?>
                            </div>
                            <span class="card-hint">Visits in <?= htmlspecialchars($titlePeriod) ?> &rarr;</span>
                        </div>
                        <div class="stat-icon-badge"><i class="fa-solid fa-person-walking"></i></div>
                    </div>
                </div>
            </a>
        </div>

    </div>

    <!-- Monthly Performance Chart Section -->
    <div class="card__wrapper shadow-sm p-3">
        <h5>My Performance (<?= htmlspecialchars($titlePeriod) ?>)</h5>
        <div id="monthlyPerformanceChart"></div>
    </div>

    <div class="mb-5"></div>

</div>

<!-- ApexCharts JS -->
<script>
    document.addEventListener("DOMContentLoaded", function () {

        // Toggle Month dropdowns based on Filter Type
        const filterTypeSelect = document.getElementById('filterTypeSelect');
        const startMonthCol = document.getElementById('startMonthCol');
        const endMonthCol = document.getElementById('endMonthCol');
        const filterTypeCol = document.getElementById('filterTypeCol');
        const yearCol = document.getElementById('yearCol');

        if (filterTypeSelect && startMonthCol && endMonthCol && filterTypeCol && yearCol) {
            function updateFilterUI() {
                if (filterTypeSelect.value === 'yearly') {
                    startMonthCol.style.setProperty('display', 'none', 'important');
                    endMonthCol.style.setProperty('display', 'none', 'important');
                    filterTypeCol.className = 'col-12 col-md-4';
                    yearCol.className = 'col-12 col-md-4';
                } else {
                    startMonthCol.style.setProperty('display', 'block', 'important');
                    endMonthCol.style.setProperty('display', 'block', 'important');
                    filterTypeCol.className = 'col-6 col-md-2';
                    yearCol.className = 'col-6 col-md-2';
                }
            }

            filterTypeSelect.addEventListener('change', updateFilterUI);
            updateFilterUI();
        }

        const toggleBtn = document.getElementById('toggleMonthYearCollapse');
        const collapseSection = document.getElementById('monthYearCollapseSection');
        if (toggleBtn && collapseSection) {
            toggleBtn.addEventListener('click', function () {
                if (collapseSection.style.display === 'none' || collapseSection.style.display === '') {
                    collapseSection.style.display = 'block';
                } else {
                    collapseSection.style.display = 'none';
                }
            });
        }

        const monthlyData = {
            labels: [
                'Site Visit Target',
                'Booking Target',
                'Visit Complete',
                'Booking Complete',
                'Visit %',
                'Booking %',
                'Deals Lost'
            ],
            totals: [
                <?= $monthlySiteVisitTarget ?? 0 ?>,
                <?= $monthlyBookingTarget ?? 0 ?>,
                <?= $monthlyVisitComplete ?? $thisMonthVisited ?? 0 ?>,
                <?= $monthlyBookingComplete ?? $currentMonthBooking ?? 0 ?>,
                <?= $visitAchievement ?? 0 ?>,
                <?= $bookingAchievement ?? 0 ?>,
                <?= $dealsLost ?? 0 ?>
            ]
        };

        const colors = ['#4e73df', '#8e7eff', '#36b9cc', '#f6c23e', '#e74a3b', '#7b61ff', '#fd7e14'];

        const chartOptions = {
            chart: {
                type: 'bar',
                height: 350,
                toolbar: {
                    show: false
                }
            },
            series: [{
                name: 'Performance',
                data: monthlyData.totals
            }],
            plotOptions: {
                bar: {
                    distributed: true,
                    borderRadius: 4,
                    columnWidth: '50%'
                }
            },
            dataLabels: {
                enabled: true
            },

            xaxis: {
                categories: monthlyData.labels,
                labels: {
                    rotate: -45,
                    style: {
                        colors: ['#5e4cdc'],
                        fontSize: '11px',
                        fontWeight: 700
                    }
                }
            },

            yaxis: {
                min: 0,
                max: Math.max(...monthlyData.totals) === 0 ? 10 : undefined,
                tickAmount: 5,
                title: {
                    text: 'Count / %',
                    style: {
                        color: '#5e4cdc'
                    }
                },
                labels: {
                    style: {
                        colors: ['#5e4cdc'],
                        fontSize: '11px',
                        fontWeight: 700
                    }
                }
            },

            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        height: 500
                    },
                    plotOptions: {
                        bar: {
                            horizontal: true,
                            columnWidth: '100%',
                            distributed: true,
                        }
                    },
                    xaxis: {
                        labels: {
                            rotate: 0,
                        }
                    },
                    yaxis: {
                        labels: {
                            show: true,
                            style: {
                                fontSize: '10px'
                            }
                        }
                    },
                    legend: {
                        show: false
                    }
                }
            }],

            tooltip: {
                y: {
                    formatter: val => val
                }
            },
            colors: colors
        };

        const monthlyChart = new ApexCharts(
            document.querySelector("#monthlyPerformanceChart"),
            chartOptions
        );
        monthlyChart.render();
    });
</script>
