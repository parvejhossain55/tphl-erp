<style>
/* ==========================================================================
   HOD & Team Performance Dashboard Styles (Leadership Workspace)
   ========================================================================== */
.tphl-team-container {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    color: #1e293b;
    width: 100%;
    max-width: 100%;
    padding: 0 4px;
    margin: 0;
}

/* Header & Tags */
.team-header-tag {
    font-size: 11px;
    font-weight: 800;
    color: #15803d;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}
.team-header-title {
    font-size: 26px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
}

/* Mode Pill Switch */
.team-mode-pills {
    display: inline-flex;
    background: #f1f5f9;
    border-radius: 20px;
    padding: 3px;
    border: 1px solid #e2e8f0;
}
.team-mode-pill {
    padding: 6px 16px;
    font-size: 12.5px;
    font-weight: 600;
    color: #64748b;
    border-radius: 16px;
    text-decoration: none;
    transition: all 0.2s;
    display: inline-block;
}
.team-mode-pill:hover {
    color: #0f172a;
}
.team-mode-pill.active {
    background: #ffffff;
    color: #15803d;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.team-btn-export {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    color: #334155;
    padding: 7px 18px;
    font-size: 13px;
    font-weight: 600;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    transition: all 0.2s;
}
.team-btn-export:hover {
    background: #f8fafc;
    color: #0f172a;
    border-color: #cbd5e1;
}

.team-btn-add {
    background: #15803d;
    border: 1px solid #15803d;
    color: #ffffff;
    padding: 7px 20px;
    font-size: 13px;
    font-weight: 600;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    transition: all 0.2s;
    box-shadow: 0 2px 6px rgba(21, 128, 61, 0.25);
}
.team-btn-add:hover {
    background: #166534;
    border-color: #166534;
    color: #ffffff;
}

/* Filter Card */
.team-filter-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 10px 18px;
    margin-bottom: 16px;
}
.team-filter-label {
    font-size: 11.5px;
    font-weight: 800;
    color: #64748b;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    margin-right: 8px;
}
.team-select-pill {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 6px 14px;
    font-size: 12.5px;
    font-weight: 600;
    color: #334155;
    outline: none;
    cursor: pointer;
    transition: all 0.2s;
}
.team-select-pill:focus {
    border-color: #16a34a;
    box-shadow: 0 0 0 2px rgba(22, 163, 74, 0.15);
}

/* 6 KPI Cards Grid */
.team-kpi-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 12px;
    margin-bottom: 16px;
}
@media (max-width: 1280px) {
    .team-kpi-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
@media (max-width: 768px) {
    .team-kpi-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 480px) {
    .team-kpi-grid {
        grid-template-columns: 1fr;
    }
}

.team-kpi-card {
    background: #ffffff;
    border: 1px solid #f1f5f9;
    border-radius: 14px;
    padding: 18px 20px;
    position: relative;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    overflow: hidden;
}
.team-kpi-card::after {
    content: "";
    position: absolute;
    bottom: -15px;
    right: -15px;
    width: 65px;
    height: 65px;
    border-radius: 50%;
    opacity: 0.12;
    pointer-events: none;
}
.team-kpi-card.kpi-green::after { background: #16a34a; }
.team-kpi-card.kpi-amber::after { background: #d97706; }
.team-kpi-card.kpi-red::after   { background: #dc2626; }
.team-kpi-card.kpi-purple::after{ background: #8b5cf6; }

.team-kpi-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}
.team-kpi-title {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}
.team-kpi-icon {
    width: 22px;
    height: 22px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
}
.team-kpi-val {
    font-size: 26px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.1;
    margin-bottom: 6px;
}
.team-kpi-sub {
    font-size: 11.5px;
    color: #64748b;
    font-weight: 500;
}

/* Charts Section */
.team-charts-row {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 16px;
    margin-bottom: 16px;
}
@media (max-width: 992px) {
    .team-charts-row {
        grid-template-columns: 1fr;
    }
}
.team-chart-card {
    background: #ffffff;
    border: 1px solid #f1f5f9;
    border-radius: 14px;
    padding: 22px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}
.team-chart-title {
    font-size: 16px;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 2px;
}
.team-chart-subtitle {
    font-size: 12px;
    color: #64748b;
    margin-bottom: 0;
}

/* Workload Balance List */
.workload-list {
    max-height: 280px;
    overflow-y: auto;
    padding-right: 4px;
}
.workload-item {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
    padding: 6px 10px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}
.workload-item:hover {
    background: #f8fafc;
    border-color: #e2e8f0;
    transform: translateX(3px);
}
.workload-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #f1f5f9;
    color: #334155;
    font-size: 11px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e2e8f0;
    flex-shrink: 0;
}
.workload-name {
    width: 80px;
    font-size: 12.5px;
    font-weight: 600;
    color: #334155;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.workload-bar-wrap {
    flex-grow: 1;
    height: 7px;
    background: #f1f5f9;
    border-radius: 4px;
    overflow: hidden;
}
.workload-bar-fill {
    height: 100%;
    background: #059669;
    border-radius: 4px;
}
.workload-count {
    width: 24px;
    text-align: right;
    font-size: 12px;
    font-weight: 700;
    color: #475569;
}

.workload-callout {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 10px;
    padding: 10px 14px;
    margin-top: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.workload-callout-icon {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #dcfce7;
    color: #15803d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 800;
}

/* Bottom Table Section */
.team-table-card {
    background: #ffffff;
    border: 1px solid #f1f5f9;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    overflow: hidden;
}
.team-table-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    border-bottom: 1px solid #f1f5f9;
    flex-wrap: gap;
}
.team-tabs {
    display: flex;
    align-items: center;
    gap: 18px;
}
.team-tab-item {
    font-size: 13.5px;
    font-weight: 600;
    color: #64748b;
    text-decoration: none;
    padding-bottom: 12px;
    margin-bottom: -15px;
    border-bottom: 2px solid transparent;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.team-tab-item:hover {
    color: #0f172a;
}
.team-tab-item.active {
    color: #15803d;
    border-bottom-color: #15803d;
    font-weight: 700;
}
.team-tab-pill {
    background: #f1f5f9;
    color: #475569;
    font-size: 11px;
    font-weight: 700;
    padding: 1px 7px;
    border-radius: 10px;
}
.team-tab-item.active .team-tab-pill {
    background: #dcfce7;
    color: #15803d;
}

.team-table {
    width: 100%;
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0;
}
.team-table th {
    background: #f8fafc;
    font-size: 11px;
    font-weight: 800;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 12px 18px;
    border-bottom: 1px solid #e2e8f0;
}
.team-table td {
    padding: 14px 18px;
    font-size: 13px;
    color: #334155;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
}
.team-table tbody tr:hover {
    background-color: #f8fafc;
}

/* Performance Badges */
.badge-perf-excellent {
    background: #f0fdf4;
    color: #15803d;
    border: 1px solid #bbf7d0;
    font-size: 11.5px;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 16px;
    display: inline-block;
}
.badge-perf-strong {
    background: #ecfdf5;
    color: #059669;
    border: 1px solid #a7f3d0;
    font-size: 11.5px;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 16px;
    display: inline-block;
}
.badge-perf-ontrack {
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
    font-size: 11.5px;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 16px;
    display: inline-block;
}
.badge-perf-watch {
    background: #fffbeb;
    color: #d97706;
    border: 1px solid #fde68a;
    font-size: 11.5px;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 16px;
    display: inline-block;
}
.badge-perf-atrisk {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
    font-size: 11.5px;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 16px;
    display: inline-block;
}

.trend-up {
    color: #16a34a;
    font-weight: 700;
    font-size: 12px;
}
.trend-down {
    color: #dc2626;
    font-weight: 700;
    font-size: 12px;
}

/* Member Table Avatar */
.member-avatar-wrap {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #f1f5f9;
    color: #1e293b;
    font-size: 11.5px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e2e8f0;
    margin-right: 10px;
    flex-shrink: 0;
}
.member-avatar-img {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 10px;
    border: 1px solid #e2e8f0;
}

/* My Work Slide Drawer & Form Styles */
.mw-btn-create {
    background-color: #087F5B;
    color: #FFFFFF !important;
    font-weight: 700;
    font-size: 12.5px;
    line-height: 15px;
    padding: 8px 14px;
    border-radius: 6px;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: background-color 0.15s ease;
}
.mw-btn-create:hover {
    background-color: #066649;
    color: #FFFFFF !important;
}
.mw-toast-wrap { position: fixed; top: 20px; right: 24px; z-index: 10050; display: flex; flex-direction: column; gap: 10px; pointer-events: none; }
.mw-toast { pointer-events: auto; min-width: 280px; max-width: 420px; padding: 12px 16px; border-radius: 8px; background: #fff; border: 1px solid #E2EAE5; border-left: 4px solid #087F5B; box-shadow: 0 10px 28px rgba(20,45,38,.14); display: flex; align-items: center; gap: 10px; font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 500; color: #142D26; animation: mwToastIn .22s ease; }
.mw-toast.error { border-left-color: #C92A2A; }
.mw-toast .mw-toast-icon { font-size: 16px; color: #087F5B; flex-shrink: 0; }
.mw-toast.error .mw-toast-icon { color: #C92A2A; }
.mw-toast .mw-toast-close { margin-left: auto; background: none; border: 0; color: #65776F; cursor: pointer; padding: 2px 4px; font-size: 13px; }
@keyframes mwToastIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
.mw-multi { position: relative; }
.mw-multi-btn { text-align: left; width: 100%; cursor: pointer; }
.mw-multi-panel { display: none; position: absolute; z-index: 50; left: 0; right: 0; top: 100%; margin-top: 4px; background: #fff; border: 1px solid #E2EAE5; border-radius: 8px; box-shadow: 0 6px 16px rgba(0,0,0,.08); max-height: 240px; overflow-y: auto; padding: 8px; }
.mw-multi.open .mw-multi-panel { display: block; }
.mw-multi-search { margin-bottom: 6px; position: sticky; top: 0; z-index: 1; background: #fff; }
.mw-multi-item { display: flex; align-items: center; gap: 8px; padding: 5px 6px; border-radius: 6px; cursor: pointer; font-size: 13px; margin: 0; }
.mw-multi-item:hover { background: #F4FAF7; }

.mw-drawer-backdrop {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(20, 45, 38, 0.45);
    backdrop-filter: blur(2px);
    z-index: 9998;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.28s ease, visibility 0.28s ease;
}
.mw-drawer-backdrop.show { opacity: 1; visibility: visible; }
.mw-drawer {
    position: fixed;
    top: 0; right: 0; bottom: 0;
    left: 54.38%;
    background: #FFFFFF;
    z-index: 9999;
    box-shadow: -12px 0 40px rgba(0, 0, 0, 0.16);
    display: flex;
    flex-direction: column;
    transform: translateX(100%);
    transition: transform 0.32s cubic-bezier(0.16, 1, 0.3, 1);
    border-left: 1px solid #E2EAE5;
}
.mw-drawer.show { transform: translateX(0); }
@media (max-width: 1100px) { .mw-drawer { left: 35%; } }
@media (max-width: 768px) { .mw-drawer { left: 0; width: 100%; } }
.mw-drawer-header { padding: 22px 28px 16px; border-bottom: 1px solid #E2EAE5; position: relative; flex-shrink: 0; }
.mw-drawer-title-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; }
.mw-drawer-title { font-weight: 700; font-size: 20px; line-height: 24px; color: #142D26; margin: 0; }
.mw-drawer-close { background: transparent; border: none; font-size: 18px; color: #65776F; cursor: pointer; padding: 4px 8px; border-radius: 4px; line-height: 1; transition: all 0.15s ease; }
.mw-drawer-close:hover { color: #142D26; background: #F5F7F6; }
.mw-drawer-subtitle { font-weight: 400; font-size: 12px; line-height: 15px; color: #65776F; margin: 0; }
.mw-drawer-meta-bar { padding: 10px 28px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #F0F4F2; background: #FAFCFA; flex-shrink: 0; }
.mw-status-badge-todo { background: #F5F7F6; border: 1px solid #E2EAE5; border-radius: 4px; font-weight: 600; font-size: 11px; line-height: 13px; color: #142D26; padding: 4px 10px; display: inline-block; }
.mw-required-note { font-weight: 400; font-size: 11px; line-height: 13px; color: #65776F; }
.mw-drawer-body { padding: 24px 28px; overflow-y: auto; flex-grow: 1; }
.mw-form-group { margin-bottom: 18px; }
.mw-form-label { display: block; font-weight: 600; font-size: 12px; line-height: 15px; color: #142D26; margin-bottom: 6px; }
.mw-form-label span.req { color: #DC2626; }
.mw-form-input, .mw-form-select, .mw-form-textarea {
    width: 100%; background: #FFFFFF; border: 1px solid #D1DDD6; border-radius: 6px;
    padding: 9px 12px; font-size: 13px; line-height: 18px; color: #142D26; outline: none;
    transition: border-color 0.15s ease, box-shadow 0.15s ease; box-sizing: border-box;
}
.mw-form-input:focus, .mw-form-select:focus, .mw-form-textarea:focus {
    border-color: #087F5B; box-shadow: 0 0 0 3px rgba(8, 127, 91, 0.12);
}
.mw-form-row { display: flex; gap: 16px; }
.mw-form-col { flex: 1; min-width: 0; }
.mw-checkbox-wrap { display: flex; align-items: center; gap: 8px; margin: 14px 0 16px; cursor: pointer; user-select: none; }
.mw-checkbox-wrap input[type="checkbox"] { width: 16px; height: 16px; accent-color: #087F5B; cursor: pointer; }
.mw-checkbox-wrap span { font-weight: 500; font-size: 12.5px; color: #142D26; }
.mw-callout-box { background: #EBF7EE; border: 1px solid #C3E6CD; border-radius: 8px; padding: 12px 16px; display: flex; gap: 12px; align-items: flex-start; margin-top: 18px; margin-bottom: 12px; }
.mw-callout-icon { color: #087F5B; font-size: 16px; margin-top: 1px; }
.mw-callout-title { font-weight: 600; font-size: 12.5px; line-height: 16px; color: #087F5B; margin-bottom: 2px; }
.mw-callout-sub { font-weight: 400; font-size: 11.5px; line-height: 15px; color: #4A6B5D; }
.mw-drawer-footer { padding: 16px 28px 18px; border-top: 1px solid #E2EAE5; background: #FFFFFF; flex-shrink: 0; }
.mw-drawer-footer-actions { display: flex; justify-content: flex-start; align-items: center; gap: 12px; }
.mw-drawer-btn { padding: 9px 18px; border-radius: 6px; font-weight: 600; font-size: 13px; line-height: 16px; cursor: pointer; transition: all 0.15s ease; display: inline-flex; align-items: center; justify-content: center; }
.mw-drawer-btn.secondary { background: #FFFFFF; border: 1px solid #D1DDD6; color: #142D26; }
.mw-drawer-btn.secondary:hover { background: #F5F7F6; border-color: #B5C6BC; }
.mw-drawer-btn.primary { background: #087F5B; border: 1px solid #087F5B; color: #FFFFFF; }
.mw-drawer-btn.primary:hover { background: #066649; border-color: #066649; }
</style>

<div class="tphl-team-container py-3 px-3">
    <!-- Flash Messages -->
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2 fs-5 text-success"></i>
            <div class="fw-semibold"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-5 text-danger"></i>
            <div class="fw-semibold"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Top Header (Matching Screenshot) -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <div class="team-header-tag mb-1">LEADERSHIP WORKSPACE</div>
            <?php if (!empty($viewingHod)): ?>
                <h3 class="team-header-title mb-1">
                    <?= htmlspecialchars($viewingHod['full_name']) ?>'s Team Performance
                </h3>
                <p class="text-muted small mb-0">
                    <?= htmlspecialchars($viewingHod['designation_name'] ?? 'Head of Department') ?> &bull;
                    <span class="fw-semibold text-success"><?= htmlspecialchars($viewingHod['department_name'] ?? 'Department') ?></span>
                    &nbsp;|&nbsp;
                    <a href="<?= $GLOBALS['base_url'] ?>/management-task-overview-v2" class="text-decoration-none fw-semibold">
                        <i class="bi bi-arrow-left me-1"></i>Back to Management Overview
                    </a>
                </p>
            <?php else: ?>
                <h3 class="team-header-title mb-1">HOD & Team Performance</h3>
                <p class="text-muted small mb-0">Review delivery, workload and individual contribution across your team.</p>
            <?php endif; ?>
        </div>
        <div class="d-flex align-items-center flex-wrap gap-3">
            <!-- Mode Switch (My Performance vs My Team) -->
            <div class="team-mode-pills">
                <a href="<?= $GLOBALS['base_url'] ?>/my-work-dashboard" class="team-mode-pill">My Performance</a>
                <a href="<?= $GLOBALS['base_url'] ?>/team-performance-v2" class="team-mode-pill active">My Team</a>
            </div>

            <!-- Export Button -->
            <a href="<?= $GLOBALS['base_url'] ?>/team-performance-v2?export=csv&<?= http_build_query($_GET) ?>" id="teamExportBtn" class="team-btn-export">
                <i class="bi bi-box-arrow-down"></i> Export
            </a>

            <!-- Assign Task Button (Opens Right Drawer) -->
            <button type="button" class="team-btn-add" onclick="openAssignTaskDrawer()">
                <i class="bi bi-plus-lg"></i> Assign Task
            </button>
        </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="team-filter-card">
        <form action="<?= $GLOBALS['base_url'] ?>/team-performance-v2" method="GET" id="teamFilterForm" class="d-flex align-items-center flex-wrap gap-2">
            <?php if (!empty($viewingHod)): ?>
                <input type="hidden" name="hod_id" value="<?= (int)$viewingHod['employee_id'] ?>">
            <?php endif; ?>
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">

            <span class="team-filter-label">FILTER BY</span>

            <!-- Month Filter -->
            <select name="month" id="filterMonth" class="team-select-pill">
                <option value="all_time" <?= ($monthFilter === 'all_time') ? 'selected' : '' ?>>All Time</option>
                <?php 
                    for ($m = 0; $m < 6; $m++) {
                        $ym = date('Y-m', strtotime("-$m months"));
                        $label = date('F Y', strtotime("-$m months"));
                        $sel = ($monthFilter === $ym || (empty($monthFilter) && $m === 0)) ? 'selected' : '';
                        echo "<option value=\"$ym\" $sel>$label</option>";
                    }
                ?>
            </select>

            <!-- Team Members Filter -->
            <select name="member_id" id="filterMember" class="team-select-pill">
                <option value="">All Team Members</option>
                <?php if (!empty($teamEmployees)): ?>
                    <?php foreach ($teamEmployees as $te): ?>
                        <option value="<?= $te['employee_id'] ?>" <?= (!empty($memberFilter) && ((int)$memberFilter === (int)$te['employee_id'] || (int)$memberFilter === (int)($te['user_id'] ?? 0))) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($te['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>

            <!-- Project Filter -->
            <select name="project_id" id="filterProject" class="team-select-pill">
                <option value="">All Projects</option>
                <?php if (!empty($projects)): ?>
                    <?php foreach ($projects as $proj): ?>
                        <option value="<?= $proj['id'] ?>" <?= ($projectFilter == $proj['id']) ? 'selected' : '' ?>>
                            <?= (!empty($proj['is_team_project']) ? '👥 [Team] ' : '') . htmlspecialchars($proj['name']) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>

            <!-- Status Filter -->
            <select name="status" id="filterStatus" class="team-select-pill">
                <option value="">All Status</option>
                <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="in_progress" <?= $statusFilter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="overdue" <?= $statusFilter === 'overdue' ? 'selected' : '' ?>>Overdue</option>
            </select>

            <!-- Type Filter -->
            <select name="type" id="filterType" class="team-select-pill">
                <option value="assigned_self" <?= $typeFilter === 'assigned_self' ? 'selected' : '' ?>>Assigned + Self</option>
                <option value="assigned" <?= $typeFilter === 'assigned' ? 'selected' : '' ?>>Assigned Only</option>
                <option value="self" <?= $typeFilter === 'self' ? 'selected' : '' ?>>Self Only</option>
            </select>

            <!-- Action Buttons: Apply & Reset -->
            <div class="d-flex align-items-center gap-1 ms-1">
                <button type="submit" id="btnApplyFilters" class="btn btn-sm btn-success rounded-pill px-3 fw-semibold text-white d-inline-flex align-items-center gap-1" style="font-size: 12.5px; height: 32px; background-color: #15803d; border-color: #15803d;">
                    <i class="bi bi-funnel-fill"></i> Apply
                </button>
                <a href="<?= $GLOBALS['base_url'] ?>/team-performance-v2<?= !empty($viewingHod) ? '?hod_id=' . (int)$viewingHod['employee_id'] : '' ?>" id="btnResetFilters" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-semibold d-inline-flex align-items-center gap-1" style="font-size: 12.5px; height: 32px;" title="Reset filters to default">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <div id="teamDashboardContent" style="transition: opacity 0.2s ease;">
    <!-- 6 KPI Cards Grid (Matching Screenshot) -->
    <div class="team-kpi-grid">
        <!-- 1. Team Tasks -->
        <div class="team-kpi-card kpi-green">
            <div class="team-kpi-header">
                <span class="team-kpi-title">TEAM TASKS</span>
                <span class="team-kpi-icon bg-light text-success"><i class="bi bi-grid-fill"></i></span>
            </div>
            <div class="team-kpi-val"><?= str_pad($kpi['total_tasks'], 2, '0', STR_PAD_LEFT) ?></div>
            <div class="team-kpi-sub text-success"><?= $kpi['tasks_this_month'] > 0 ? '+' . $kpi['tasks_this_month'] . ' tasks this month' : '0 tasks this month' ?></div>
        </div>

        <!-- 2. Completed -->
        <div class="team-kpi-card kpi-green">
            <div class="team-kpi-header">
                <span class="team-kpi-title">COMPLETED</span>
                <span class="team-kpi-icon bg-success-subtle text-success"><i class="bi bi-check2"></i></span>
            </div>
            <div class="team-kpi-val"><?= str_pad($kpi['completed'], 2, '0', STR_PAD_LEFT) ?></div>
            <div class="team-kpi-sub text-success"><?= $kpi['total_tasks'] > 0 ? $kpi['completion_rate'] . '% completion rate' : 'No completed tasks' ?></div>
        </div>

        <!-- 3. In Progress -->
        <div class="team-kpi-card kpi-amber">
            <div class="team-kpi-header">
                <span class="team-kpi-title">IN PROGRESS</span>
                <span class="team-kpi-icon bg-warning-subtle text-warning"><i class="bi bi-clock"></i></span>
            </div>
            <div class="team-kpi-val"><?= str_pad($kpi['in_progress'], 2, '0', STR_PAD_LEFT) ?></div>
            <div class="team-kpi-sub text-warning"><?= $kpi['in_progress'] > 0 ? $kpi['due_this_week'] . ' due this week' : 'No active tasks' ?></div>
        </div>

        <!-- 4. Overdue -->
        <div class="team-kpi-card kpi-red">
            <div class="team-kpi-header">
                <span class="team-kpi-title">OVERDUE</span>
                <span class="team-kpi-icon bg-danger-subtle text-danger"><i class="bi bi-exclamation"></i></span>
            </div>
            <div class="team-kpi-val"><?= str_pad($kpi['overdue'], 2, '0', STR_PAD_LEFT) ?></div>
            <div class="team-kpi-sub text-danger"><?= $kpi['total_tasks'] > 0 ? $kpi['overdue_pct'] . '% of team tasks' : 'No overdue tasks' ?></div>
        </div>

        <!-- 5. On-Time Rate -->
        <div class="team-kpi-card kpi-purple">
            <div class="team-kpi-header">
                <span class="team-kpi-title">ON TIME RATE</span>
                <span class="team-kpi-icon bg-primary-subtle text-primary"><i class="bi bi-shield-check"></i></span>
            </div>
            <div class="team-kpi-val"><?= $kpi['on_time_rate'] ?>%</div>
            <div class="team-kpi-sub text-primary"><?= $kpi['total_tasks'] > 0 ? $kpi['on_time_rate'] . '% on-time delivery' : 'No delivery data' ?></div>
        </div>

        <!-- 6. Avg Team Score -->
        <div class="team-kpi-card kpi-purple">
            <div class="team-kpi-header">
                <span class="team-kpi-title">AVG. TEAM SCORE</span>
                <span class="team-kpi-icon bg-secondary-subtle text-secondary"><i class="bi bi-pencil-square"></i></span>
            </div>
            <div class="team-kpi-val"><?= $kpi['avg_team_score'] ?></div>
            <div class="team-kpi-sub text-secondary"><?= $kpi['team_health_label'] ?></div>
        </div>
    </div>

    <!-- Charts Row: Team Delivery Trend & Workload Balance (Matching Screenshot) -->
    <div class="team-charts-row">
        <!-- Left: Team Delivery Trend Chart -->
        <div class="team-chart-card">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h5 class="team-chart-title">Team Delivery Trend</h5>
                    <p class="team-chart-subtitle">Completed, assigned and overdue tasks by week</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="team-mode-pills" style="padding: 2px;">
                        <button type="button" class="perf-time-pill active" onclick="changeDeliveryTime(event, '4W')">4W</button>
                        <button type="button" class="perf-time-pill" onclick="changeDeliveryTime(event, '3M')">3M</button>
                        <button type="button" class="perf-time-pill" onclick="changeDeliveryTime(event, '6M')">6M</button>
                    </div>
                </div>
            </div>

            <!-- Legend -->
            <div class="d-flex align-items-center gap-3 mb-2 small text-muted">
                <span><span class="d-inline-block rounded-circle me-1" style="width: 8px; height: 8px; background-color: #16a34a;"></span> Completed</span>
                <span><span class="d-inline-block rounded-circle me-1" style="width: 8px; height: 8px; background-color: #3b82f6;"></span> Assigned</span>
                <span><span class="d-inline-block rounded-circle me-1" style="width: 8px; height: 8px; background-color: #ef4444;"></span> Overdue</span>
            </div>

            <!-- ApexChart -->
            <div id="teamDeliveryChart"
                 style="min-height: 240px;"
                 data-weeks="<?= htmlspecialchars(json_encode($trendWeeks ?? []), ENT_QUOTES, 'UTF-8') ?>"
                 data-completed="<?= htmlspecialchars(json_encode($trendCompleted ?? []), ENT_QUOTES, 'UTF-8') ?>"
                 data-assigned="<?= htmlspecialchars(json_encode($trendAssigned ?? []), ENT_QUOTES, 'UTF-8') ?>"
                 data-overdue="<?= htmlspecialchars(json_encode($trendOverdue ?? []), ENT_QUOTES, 'UTF-8') ?>"></div>
        </div>

        <!-- Right: Workload Balance -->
        <div class="team-chart-card d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <h5 class="team-chart-title mb-0">Workload Balance</h5>
                    <span class="badge bg-light text-secondary border px-2 py-1 rounded-pill" style="font-size: 10.5px; cursor: pointer;" onclick="openWorkloadFormulaInfo()" title="Click to view calculation formula">
                        <i class="bi bi-info-circle me-1 text-success"></i> How it works
                    </span>
                </div>
                <p class="team-chart-subtitle mb-3">Open tasks per team member <span class="text-success small fw-semibold ms-1">(Click member to view)</span></p>

                <!-- Workload List -->
                <div class="workload-list">
                    <?php if (!empty($workloadBars)): ?>
                        <?php foreach ($workloadBars as $wb): ?>
                            <div class="workload-item" 
                                 onclick="openWorkloadBreakdown(<?= htmlspecialchars(json_encode($wb), ENT_QUOTES, 'UTF-8') ?>)" 
                                 title="Click to view workload calculation & open tasks for <?= htmlspecialchars($wb['full_name'] ?? $wb['name']) ?>">
                                <div class="workload-avatar"><?= $wb['initials'] ?></div>
                                <div class="workload-name" title="<?= htmlspecialchars($wb['full_name'] ?? $wb['name']) ?>"><?= htmlspecialchars($wb['name']) ?></div>
                                <div class="workload-bar-wrap">
                                    <div class="workload-bar-fill" style="width: <?= $wb['bar_pct'] ?>%;"></div>
                                </div>
                                <div class="workload-count d-flex align-items-center gap-1 justify-content-end">
                                    <span><?= str_pad($wb['open_tasks'], 2, '0', STR_PAD_LEFT) ?></span>
                                    <i class="bi bi-chevron-right text-muted" style="font-size: 11px;"></i>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted small">No active team workloads recorded.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Bottom Callout (Matching Screenshot) -->
            <div class="workload-callout">
                <div class="workload-callout-icon">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div>
                    <div class="fw-bold text-dark" style="font-size: 12.5px;">Workload Status</div>
                    <div class="text-muted" style="font-size: 11.5px;"><?= !empty($highestWorkloadMember) ? htmlspecialchars($highestWorkloadMember) . ' has the highest active workload' : 'All team workloads are clear and balanced' ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Section: Team Members Performance Table (Matching Screenshot) -->
    <div class="team-table-card">
        <!-- Table Header Tabs & Action Buttons -->
        <div class="team-table-header">
            <div class="team-tabs">
                <a href="<?= $GLOBALS['base_url'] ?>/team-performance-v2?tab=all&<?= http_build_query(array_diff_key($_GET, ['tab' => ''])) ?>" data-tab="all" class="team-tab-item <?= $tab === 'all' ? 'active' : '' ?>">
                    All Members <span class="team-tab-pill"><?= $allMembersCount ?></span>
                </a>
                <a href="<?= $GLOBALS['base_url'] ?>/team-performance-v2?tab=top_performers&<?= http_build_query(array_diff_key($_GET, ['tab' => ''])) ?>" data-tab="top_performers" class="team-tab-item <?= $tab === 'top_performers' ? 'active' : '' ?>">
                    Top Performers <span class="team-tab-pill"><?= $topPerformersCount ?></span>
                </a>
                <a href="<?= $GLOBALS['base_url'] ?>/team-performance-v2?tab=needs_attention&<?= http_build_query(array_diff_key($_GET, ['tab' => ''])) ?>" data-tab="needs_attention" class="team-tab-item <?= $tab === 'needs_attention' ? 'active' : '' ?>">
                    Needs Attention <span class="team-tab-pill"><?= $needsAttentionCount ?></span>
                </a>
            </div>

            <div class="d-flex align-items-center gap-2">
                <a id="teamSortScoreBtn" href="<?= $GLOBALS['base_url'] ?>/team-performance-v2?sort=<?= $sort === 'score_desc' ? 'score_asc' : 'score_desc' ?>&<?= http_build_query(array_diff_key($_GET, ['sort' => ''])) ?>" class="btn btn-sm btn-light border rounded-pill px-3 fw-semibold text-secondary" style="font-size: 12.5px;">
                    <i class="bi bi-filter me-1"></i> Sort by Score
                </a>
            </div>
        </div>

        <!-- Table Data -->
        <div class="table-responsive">
            <table class="team-table">
                <thead>
                    <tr>
                        <th>TEAM MEMBER</th>
                        <th>TOTAL</th>
                        <th>ASSIGNED</th>
                        <th>SELF TASK</th>
                        <th>COMPLETED</th>
                        <th>OVERDUE</th>
                        <th>ON-TIME</th>
                        <th>COMPLETION RATE</th>
                        <th>PERFORMANCE</th>
                        <th>TREND</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($members)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <div class="mb-2 text-success" style="font-size: 40px; opacity: 0.6;">
                                    <i class="bi bi-people"></i>
                                </div>
                                <h6 class="fw-bold text-dark mb-1">No team members found</h6>
                                <p class="small text-muted mb-0">Employees with this reporting line will appear here.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $totalMembersRendered = count($members); $tabMatchIdx = 0; ?>
                        <?php foreach ($members as $rowIdx => $m): 
                            $nameParts = preg_split('/\s+/', trim($m['full_name']));
                            $initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
                            $isTrendUp = str_starts_with($m['trend'], '+');
                            $isTopPerformer = !empty($m['is_top_performer']);
                            $isNeedsAttention = !empty($m['needs_attention']);
                            $matchesCurrentTab = ($tab === 'all')
                                || ($tab === 'top_performers' && $isTopPerformer)
                                || ($tab === 'needs_attention' && $isNeedsAttention);
                            $hideRow = !$matchesCurrentTab || ($tabMatchIdx >= 25);
                            if ($matchesCurrentTab) $tabMatchIdx++;
                        ?>
                        <tr class="team-member-row<?= $hideRow ? ' d-none' : '' ?>" data-top-performer="<?= $isTopPerformer ? '1' : '0' ?>" data-needs-attention="<?= $isNeedsAttention ? '1' : '0' ?>" data-tab-match="<?= $matchesCurrentTab ? '1' : '0' ?>" style="cursor: pointer;" onclick="openMemberTasksOffcanvas(<?= $m['employee_id'] ?>, '<?= htmlspecialchars($m['full_name'], ENT_QUOTES) ?>')">
                            <!-- Team Member -->
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($m['photo'])): ?>
                                        <img src="<?= getImage('users', $m['photo']) ?>" alt="Avatar" class="member-avatar-img">
                                    <?php else: ?>
                                        <div class="member-avatar-wrap"><?= $initials ?></div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size: 13.5px;"><?= htmlspecialchars($m['full_name']) ?></div>
                                        <div class="text-muted" style="font-size: 11.5px;"><?= htmlspecialchars($m['designation']) ?></div>
                                    </div>
                                </div>
                            </td>

                            <!-- Total -->
                            <td class="fw-semibold text-secondary"><?= $m['total'] ?></td>

                            <!-- Assigned -->
                            <td class="text-secondary"><?= $m['assigned'] ?></td>

                            <!-- Self Task -->
                            <td class="text-secondary"><?= $m['self'] ?></td>

                            <!-- Completed -->
                            <td class="fw-semibold text-dark"><?= $m['completed'] ?></td>

                            <!-- Overdue -->
                            <td class="<?= $m['overdue'] > 0 ? 'text-danger fw-semibold' : 'text-secondary' ?>"><?= $m['overdue'] ?></td>

                            <!-- On-Time -->
                            <td class="text-secondary"><?= $m['on_time_pct'] ?>%</td>

                            <!-- Completion Rate -->
                            <td style="min-width: 140px;">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 6px; background-color: #e2e8f0; border-radius: 4px;">
                                        <div class="progress-bar" style="width: <?= $m['completion_rate'] ?>%; background-color: <?= $m['completion_rate'] >= 70 ? '#059669' : ($m['completion_rate'] >= 50 ? '#d97706' : '#dc2626') ?>; border-radius: 4px;"></div>
                                    </div>
                                    <span class="text-muted fw-bold" style="font-size: 11.5px; min-width: 32px;"><?= $m['completion_rate'] ?>%</span>
                                </div>
                            </td>

                            <!-- Performance Badge -->
                            <td>
                                <span class="<?= $m['badge_class'] ?>"><?= $m['badge'] ?></span>
                            </td>

                            <!-- Trend -->
                            <td class="<?= $isTrendUp ? 'trend-up' : 'trend-down' ?>">
                                <?= $isTrendUp ? '↑' : '↓' ?> <?= ltrim($m['trend'], '+-') ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <tr id="teamTabEmptyRow" class="d-none">
                        <td colspan="10" class="text-center py-5 text-muted">
                            <div class="mb-2 text-success" style="font-size: 40px; opacity: 0.6;">
                                <i class="bi bi-people"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">No team members found</h6>
                            <p class="small text-muted mb-0">No members match this tab under the current filters.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php if (!empty($members)): ?>
        <div id="teamTableScrollSentinel" class="text-center py-3 text-muted small border-top<?= ($tabMatchIdx ?? count($members)) <= 25 ? ' d-none' : '' ?>" style="background: #f8fafc;">
            Showing <span id="teamTableVisibleCount"><?= min(25, $tabMatchIdx ?? count($members)) ?></span> of <span id="teamTableTotalCount"><?= $tabMatchIdx ?? count($members) ?></span> members — scroll down to load more
        </div>
        <?php endif; ?>
    </div>
    </div><!-- /#teamDashboardContent -->
</div>

<!-- ========================================================================
     Modal: Create Team Project
     ======================================================================== -->
<div class="modal fade" id="modalCreateTeamProject" tabindex="-1" aria-labelledby="modalCreateTeamProjectLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <div>
                    <span class="team-header-tag">PROJECT MANAGEMENT</span>
                    <h5 class="modal-title fw-bold text-dark" id="modalCreateTeamProjectLabel">Create New Team Project</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= $GLOBALS['base_url'] ?>/team-performance-v2/create-project" method="POST" id="formCreateTeamProject">
                <input type="hidden" name="is_team_project" value="1">
                <div class="modal-body pt-3">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Team Project Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="newTeamProjNameInput" class="form-control rounded-3" placeholder="e.g. Sales Campaign Q4, Operation Overhaul..." required autocomplete="off">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Project Theme Color</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="color" name="color" class="form-control form-control-color rounded-circle p-1 border-0" value="#16a34a" title="Choose project theme color" style="width: 40px; height: 40px; cursor: pointer;">
                            <span class="small text-muted">Pick a theme color to identify this project badge across all team boards.</span>
                        </div>
                    </div>

                    <div class="alert alert-light border small text-muted mb-0">
                        <i class="bi bi-people-fill text-success me-1"></i>
                        This Team Project will be shared with all members reporting to this department, enabling team-wide task assignment, progress tracking, and management overview.
                    </div>
                    <div id="createTeamProjectFeedback" class="mt-2 text-center" style="display: none;"></div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 fw-semibold px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="btnSaveTeamProject" class="btn btn-success rounded-3 fw-semibold px-4 text-white" style="background-color: #15803d; border-color: #15803d;">Create Team Project</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================
     Right Slide Drawer: Assign Task (My Work Full Task Creator)
     ======================================================================== -->
<div class="mw-drawer-backdrop" id="mwDrawerBackdrop" onclick="closeAssignTaskDrawer()"></div>
<div class="mw-drawer" id="mwCreateTaskDrawer">
    <!-- Header -->
    <div class="mw-drawer-header">
        <div class="mw-drawer-title-row">
            <h3 class="mw-drawer-title" id="mwDrawerTitle">Assign Task to Team Member</h3>
            <button type="button" class="mw-drawer-close" id="btnCloseCreateTask" onclick="closeAssignTaskDrawer()" title="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <p class="mw-drawer-subtitle" id="mwDrawerSubtitle">Define the output, owner and deadline.</p>
    </div>

    <!-- Status & Required Meta Bar -->
    <div class="mw-drawer-meta-bar">
        <span class="mw-status-badge-todo" id="mwDrawerStatusBadge">Status: To do</span>
        <span class="mw-required-note">Required fields marked <span class="text-danger">*</span></span>
    </div>

    <!-- Form & Body -->
    <form id="mwCreateTaskForm" enctype="multipart/form-data" class="d-flex flex-column flex-grow-1 overflow-hidden" style="margin: 0;">
        <input type="hidden" name="edit_task_id" id="drawerEditTaskId" value="">
        <div class="mw-drawer-body">
            <!-- Task Title -->
            <div class="mw-form-group">
                <label class="mw-form-label">Task title <span class="req">*</span></label>
                <input type="text" name="title" id="drawerTaskTitle" class="mw-form-input" 
                       placeholder="Client ledger: verify opening balances" required autocomplete="off">
            </div>

            <?php
            $currentUserId = (int)($userId ?? ($_SESSION['user']['id'] ?? 0));
            $mwMulti = function (string $name, string $placeholder, bool $single = false, ?int $selectedId = null, string $id = '') use ($usersList) { ?>
                <div class="mw-multi" <?= $id !== '' ? 'id="' . htmlspecialchars($id) . '"' : '' ?> data-single="<?= $single ? '1' : '0' ?>" data-clearable="<?= ($single && $selectedId === null) ? '1' : '0' ?>" data-placeholder="<?= htmlspecialchars($placeholder) ?>">
                    <button type="button" class="mw-form-select mw-multi-btn"><span class="mw-multi-text"><?= htmlspecialchars($placeholder) ?></span></button>
                    <div class="mw-multi-panel">
                        <input type="text" class="mw-form-input mw-multi-search" placeholder="Search...">
                        <?php foreach (($usersList ?? []) as $u): if (trim((string)$u['full_name']) === '') continue; 
                            $rawCode = !empty($u['employee_code']) ? trim((string)$u['employee_code']) : '';
                            $empCode = preg_replace('/^EMP-?/i', '', $rawCode);
                            $displayName = trim((string)$u['full_name']) . ($empCode !== '' ? " ({$empCode})" : '');
                        ?>
                            <label class="mw-multi-item">
                                <input type="<?= $single ? 'radio' : 'checkbox' ?>" name="<?= htmlspecialchars($name) ?>" value="<?= (int)$u['id'] ?>" data-name="<?= htmlspecialchars($displayName) ?>" <?= ($selectedId !== null && (int)$u['id'] === $selectedId) ? 'checked' : '' ?>>
                                <span><?= htmlspecialchars($displayName) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php }; ?>

            <!-- Row 1: Owner & Due Date -->
            <div class="mw-form-row mw-form-group">
                <div class="mw-form-col">
                    <label class="mw-form-label">Owner</label>
                    <?php
                    $ownerName = $_SESSION['user']['full_name'] ?? ($_SESSION['user']['name'] ?? '');
                    $ownerEmpCode = !empty($_SESSION['user']['employee_id']) ? $_SESSION['user']['employee_id'] : '';
                    foreach (($usersList ?? []) as $u) {
                        if ((int)$u['id'] === $currentUserId) {
                            $ownerName = $u['full_name'];
                            if (!empty($u['employee_code'])) {
                                $ownerEmpCode = $u['employee_code'];
                            }
                            break;
                        }
                    }
                    $ownerEmpCode = preg_replace('/^EMP-?/i', '', trim((string)$ownerEmpCode));
                    $ownerDisplay = trim($ownerName) . ($ownerEmpCode !== '' ? " ({$ownerEmpCode})" : '');
                    ?>
                    <div id="taskOwnerSelect">
                        <input type="text" class="mw-form-input" value="<?= htmlspecialchars($ownerDisplay) ?>" readonly style="background:#F5F7F6;cursor:not-allowed;">
                    </div>
                </div>
                <div class="mw-form-col">
                    <label class="mw-form-label">Deadline <span class="req">*</span></label>
                    <input type="date" name="deadline" id="drawerDeadline" class="mw-form-input" value="<?= date('Y-m-d', strtotime('+3 days')) ?>" required>
                </div>
            </div>

            <!-- Row 2: Priority -->
            <div class="mw-form-row mw-form-group">
                <div class="mw-form-col">
                    <label class="mw-form-label">Priority</label>
                    <select name="priority" class="mw-form-select">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
            </div>

            <!-- Row 3: Real estate project -->
            <div class="mw-form-row mw-form-group">
                <div class="mw-form-col">
                    <label class="mw-form-label">Real estate project</label>
                    <select name="project_id" class="mw-form-select">
                        <option value="">Not linked</option>
                        <?php foreach (($realEstateProjectsList ?? []) as $rp): ?>
                            <option value="<?= (int)$rp['id'] ?>"><?= htmlspecialchars($rp['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mw-form-col">
                    <label class="mw-form-label">&nbsp;</label>
                    <label class="mw-checkbox-wrap" style="margin-top:8px;">
                        <input type="checkbox" name="is_recurring" value="1" id="chkIsRecurring">
                        <span>Is Recurring</span>
                    </label>
                </div>
            </div>

            <div class="mw-form-group" id="rowRecurrenceEnd" style="display:none;">
                <label class="mw-form-label">Repeat</label>
                <select name="recurrence_type" id="taskRecurrenceType" class="mw-form-select">
                    <option value="">Select...</option>
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                </select>
            </div>

            <!-- Expected output (textarea) -->
            <div class="mw-form-group">
                <label class="mw-form-label">Expected output</label>
                <textarea name="expected_output" id="drawerExpectedOutput" class="mw-form-textarea" rows="2" 
                          placeholder="Describe the expected deliverable, results, or outcome..."></textarea>
            </div>

            <!-- Acceptance Checklist (multiple allowed, optional) -->
            <div class="mw-form-group">
                <label class="mw-form-label">Acceptance checklist</label>
                <div id="expectedOutputList">
                    <div class="d-flex align-items-start gap-2 mb-2 mw-expected-row">
                        <textarea name="description[]" id="drawerDescription" class="mw-form-textarea" rows="1" 
                                  placeholder="e.g. Verify report totals, send summary to team..."></textarea>
                        <button type="button" class="mw-btn-create" id="btnAddExpectedOutput" title="Add another checklist item" style="white-space:nowrap;">
                            <i class="fa-solid fa-plus"></i> Add
                        </button>
                    </div>
                </div>
            </div>

            <!-- Assigned to & CC -->
            <div class="mw-form-row mw-form-group">
                <div class="mw-form-col">
                    <label class="mw-form-label">Assigned to</label>
                    <?php $mwMulti('assigned_to_id', 'Select Assigned to...', true, $currentUserId, 'taskAssignedToSelect'); ?>
                </div>
                <div class="mw-form-col">
                    <label class="mw-form-label">CC</label>
                    <?php $mwMulti('cc_ids[]', 'Select CC...'); ?>
                </div>
            </div>

            <!-- Row 4: Reviewer & Collaborators -->
            <div id="sectionReviewFields" class="mw-form-row mw-form-group">
                <div class="mw-form-col">
                    <label class="mw-form-label">Reviewer</label>
                    <?php $mwMulti('reviewer_id', 'Select Reviewer...', true, null, 'taskReviewerSelect'); ?>
                </div>
                <div class="mw-form-col">
                    <label class="mw-form-label">Collaborators</label>
                    <?php $mwMulti('collaborator_ids[]', 'Select Collaborators...'); ?>
                </div>
            </div>

            <!-- Attachment -->
            <div class="mw-form-group">
                <label class="mw-form-label">Attachment</label>
                <input type="file" name="attachment" id="drawerAttachment" class="mw-form-input">
            </div>

            <!-- Start Date -->
            <div class="mw-form-group">
                <label class="mw-form-label">Start Date</label>
                <input type="date" name="start_date" class="mw-form-input">
            </div>

            <!-- Callout Notice Box -->
            <div class="mw-callout-box" id="mwTaskNoticeBox">
                <div class="mw-callout-icon">
                    <i class="fa-regular fa-bell"></i>
                </div>
                <div class="mw-callout-content">
                    <div class="mw-callout-title" id="noticeOwnerText">Owner will receive the assignment.</div>
                    <div class="mw-callout-sub" id="noticeReviewerText">Reviewer will be notified when the task is submitted for review.</div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mw-drawer-footer">
            <div class="mw-drawer-footer-actions">
                <div class="d-flex gap-2">
                    <button type="button" class="mw-drawer-btn secondary" id="btnCancelDrawer" onclick="closeAssignTaskDrawer()">Cancel</button>
                </div>
                <button type="submit" class="mw-drawer-btn primary" id="btnSubmitDrawer">
                    <i class="fa-solid fa-plus me-1"></i> Create & assign
                </button>
            </div>
        </div>
    </form>
</div>

<!-- ========================================================================
     Modal: Workload Balance Breakdown (Read-Only Calculation Details)
     ======================================================================== -->
<div class="modal fade" id="modalWorkloadBreakdown" tabindex="-1" aria-labelledby="modalWorkloadBreakdownLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-bottom py-3 px-4" style="background: linear-gradient(135deg, #f8fafc 0%, #f0fdf4 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="workload-avatar shadow-sm me-2" id="wbModalAvatar" style="width: 48px; height: 48px; font-size: 17px; background: #dcfce7; color: #15803d; border: 2px solid #86efac;"></div>
                    <div>
                        <div class="team-header-tag mb-0" style="color: #059669;">WORKLOAD BREAKDOWN</div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="wbModalMemberName">Member Name</h5>
                        <div class="text-muted" style="font-size: 12px;" id="wbModalDesignation">Designation</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="background: #ffffff;">
                <!-- Calculation Formula Banner -->
                <div class="p-3 rounded-4 mb-4" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                        <span class="fw-bold text-dark" style="font-size: 13px;">
                            <i class="bi bi-calculator me-1 text-success"></i> Workload Summary
                        </span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill fw-semibold" style="font-size: 11px;">
                            Open Tasks = In Progress + Overdue
                        </span>
                    </div>

                    <div class="row g-2.5 text-center">
                        <div class="col-sm-4 col-12">
                            <div class="p-3 rounded-3 bg-white border shadow-sm h-100">
                                <div class="text-muted text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.6px;">In Progress</div>
                                <div class="fw-bold text-warning-emphasis fs-3 my-1" id="wbModalInProgress">0</div>
                                <div class="text-muted" style="font-size: 11px;">Pending, &lt;100%</div>
                            </div>
                        </div>
                        <div class="col-sm-4 col-12">
                            <div class="p-3 rounded-3 bg-white border shadow-sm h-100">
                                <div class="text-muted text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.6px;">Overdue</div>
                                <div class="fw-bold text-danger fs-3 my-1" id="wbModalOverdue">0</div>
                                <div class="text-muted" style="font-size: 11px;">Deadline passed, &lt;100%</div>
                            </div>
                        </div>
                        <div class="col-sm-4 col-12">
                            <div class="p-3 rounded-3 border shadow-sm h-100" style="border-color: #86efac !important; background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%) !important;">
                                <div class="text-success text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.6px;">Total Open Workload</div>
                                <div class="fw-bold text-success fs-3 my-1" id="wbModalTotalOpen">0</div>
                                <div class="text-success fw-semibold" style="font-size: 11px;">Active Workload Count</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 pt-2.5 border-top d-flex align-items-center justify-content-between flex-wrap gap-2 text-muted" style="font-size: 11.5px;">
                        <span><i class="bi bi-check-circle-fill me-1 text-success"></i> Completed tasks (<strong class="text-dark" id="wbModalCompleted">0</strong>) are excluded from active workload.</span>
                        <span>Total Tasks: <strong class="text-dark" id="wbModalTotal">0</strong></span>
                    </div>
                </div>

                <!-- Tasks Listing Header & Filter Tabs -->
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <h6 class="fw-bold text-dark mb-0" style="font-size: 14px;">
                        <i class="bi bi-list-check me-1 text-success"></i> Task Breakdown
                    </h6>
                    <div class="btn-group btn-group-sm rounded-pill overflow-hidden border" role="group">
                        <button type="button" class="btn btn-light active py-1 px-3 fw-semibold" style="font-size: 12px;" id="wbFilterBtnOpen" onclick="filterWbModalTasks('open')">Open (<span id="wbTabOpenCount">0</span>)</button>
                        <button type="button" class="btn btn-light py-1 px-3 fw-semibold" style="font-size: 12px;" id="wbFilterBtnAll" onclick="filterWbModalTasks('all')">All (<span id="wbTabAllCount">0</span>)</button>
                    </div>
                </div>

                <!-- Loading State -->
                <div id="wbModalLoading" class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm text-success me-2" role="status"></div>
                    Loading task details...
                </div>

                <!-- Tasks List -->
                <div id="wbModalTasksList" style="display: none; max-height: 360px; overflow-y: auto; padding-right: 4px;"></div>
            </div>
            <div class="modal-footer border-top bg-light py-2.5 px-4 d-flex justify-content-between align-items-center">
                <span class="text-muted small">
                    <i class="bi bi-shield-check me-1 text-success"></i> Workload summary view
                </span>
                <button type="button" class="btn btn-success btn-sm rounded-pill px-4 fw-semibold" style="background-color: #15803d; border-color: #15803d;" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================
     Right Offcanvas: Specific Member's Todo Tasks
     ======================================================================== -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasMemberTasks" aria-labelledby="offcanvasMemberTasksLabel" style="width: 580px; max-width: 95vw;">
    <div class="offcanvas-header p-4 pb-2 border-bottom-0">
        <div>
            <div class="team-header-tag">MEMBER WORKSPACE</div>
            <h4 class="fw-bold mb-0 text-dark" id="offcanvasMemberTitle">Team Member Tasks</h4>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-4 pt-2">
        <div id="memberTasksLoading" class="text-center py-5 text-muted">
            <div class="spinner-border spinner-border-sm text-success me-2" role="status"></div>
            Loading tasks...
        </div>
        <div id="memberTasksList" style="display: none;"></div>
    </div>
</div>

<!-- ApexCharts Script Initialization -->
<script>
window.teamDeliveryChartInstance = null;

window.renderTeamDeliveryChart = function () {
    const chartEl = document.querySelector("#teamDeliveryChart");
    if (!chartEl) return;

    if (window.teamDeliveryChartInstance) {
        try { window.teamDeliveryChartInstance.destroy(); } catch (e) {}
        window.teamDeliveryChartInstance = null;
    }

    let trendWeeks = [];
    let completedSeries = [];
    let assignedSeries = [];
    let overdueSeries = [];
    try {
        trendWeeks = JSON.parse(chartEl.dataset.weeks || '[]');
        completedSeries = JSON.parse(chartEl.dataset.completed || '[]');
        assignedSeries = JSON.parse(chartEl.dataset.assigned || '[]');
        overdueSeries = JSON.parse(chartEl.dataset.overdue || '[]');
    } catch (e) {}

    const deliveryOptions = {
        series: [
            {
                name: 'Assigned',
                type: 'column',
                data: assignedSeries
            },
            {
                name: 'Completed',
                type: 'line',
                data: completedSeries
            },
            {
                name: 'Overdue',
                type: 'line',
                data: overdueSeries
            }
        ],
        chart: {
            height: 250,
            type: 'line',
            toolbar: { show: false },
            fontFamily: 'inherit'
        },
        stroke: {
            width: [0, 3, 3],
            curve: 'smooth',
            dashArray: [0, 0, 4]
        },
        markers: {
            size: [0, 4, 4],
            strokeWidth: 2,
            hover: {
                size: 6
            }
        },
        plotOptions: {
            bar: {
                columnWidth: '35%',
                borderRadius: 4
            }
        },
        colors: ['#bfdbfe', '#16a34a', '#ef4444'],
        dataLabels: { enabled: false },
        xaxis: {
            categories: trendWeeks,
            labels: {
                style: { colors: '#94a3b8', fontSize: '11px' }
            },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: [
            {
                seriesName: 'Assigned',
                min: 0,
                forceNiceScale: true,
                labels: {
                    style: { colors: '#94a3b8', fontSize: '11px' },
                    formatter: function (val) {
                        return Math.round(val);
                    }
                },
                title: {
                    text: 'Assigned / Completed',
                    style: { color: '#94a3b8', fontSize: '10.5px', fontWeight: 500 }
                }
            },
            {
                seriesName: 'Completed',
                show: false
            },
            {
                seriesName: 'Overdue',
                opposite: true,
                min: 0,
                forceNiceScale: true,
                labels: {
                    style: { colors: '#ef4444', fontSize: '11px' },
                    formatter: function (val) {
                        return Math.round(val);
                    }
                },
                title: {
                    text: 'Overdue',
                    style: { color: '#ef4444', fontSize: '10.5px', fontWeight: 500 }
                }
            }
        ],
        tooltip: {
            shared: true,
            intersect: false,
            y: {
                formatter: function (val) {
                    return val + ' tasks';
                }
            }
        },
        grid: {
            borderColor: '#f1f5f9',
            strokeDashArray: 4
        },
        legend: { show: false }
    };

    window.teamDeliveryChartInstance = new ApexCharts(chartEl, deliveryOptions);
    window.teamDeliveryChartInstance.render();
};

document.addEventListener("DOMContentLoaded", function () {
    window.renderTeamDeliveryChart();
});

function changeDeliveryTime(event, period) {
    document.querySelectorAll('.perf-time-pill').forEach(el => el.classList.remove('active'));
    event.target.classList.add('active');
}

let currentMemberEmpId = 0;
let currentMemberEmpName = '';

let currentWbMember = null;
let currentWbTasks = [];
let currentWbFilter = 'open';

function openWorkloadFormulaInfo() {
    alert("Workload Balance Calculation Formula:\n\n1. In Progress Tasks: Status is pending/in-progress with <100% progress and deadline has not passed.\n2. Overdue Tasks: Deadline has passed and task is <100% progress.\n\nTotal Open Tasks = In Progress + Overdue.\n\nCompleted tasks (100%) are excluded from the workload count.");
}

function getMemberTasksQueryUrl(empId) {
    const curParams = new URLSearchParams(window.location.search);
    const qs = new URLSearchParams();
    ['month', 'project_id', 'status', 'type'].forEach(k => {
        const val = curParams.get(k);
        if (val) qs.set(k, val);
    });
    const qStr = qs.toString();
    return '<?= $GLOBALS['base_url'] ?>/team-performance-v2/member/' + empId + (qStr ? '?' + qStr : '');
}

function openWorkloadBreakdown(wb) {
    if (!wb) return;
    currentWbMember = wb;
    currentMemberEmpId = wb.employee_id;
    currentMemberEmpName = wb.full_name || wb.name;

    document.getElementById('wbModalAvatar').textContent = wb.initials || 'TM';
    document.getElementById('wbModalMemberName').textContent = wb.full_name || wb.name;
    document.getElementById('wbModalDesignation').textContent = wb.designation || 'Team Member';
    document.getElementById('wbModalInProgress').textContent = wb.in_progress || 0;
    document.getElementById('wbModalOverdue').textContent = wb.overdue || 0;
    document.getElementById('wbModalTotalOpen').textContent = wb.open_tasks || 0;
    document.getElementById('wbModalCompleted').textContent = wb.completed || 0;
    document.getElementById('wbModalTotal').textContent = wb.total || 0;

    const modalEl = document.getElementById('modalWorkloadBreakdown');
    const bsModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    bsModal.show();

    const loading = document.getElementById('wbModalLoading');
    const list = document.getElementById('wbModalTasksList');
    loading.style.display = 'block';
    list.style.display = 'none';

    fetch(getMemberTasksQueryUrl(wb.employee_id))
        .then(res => res.json())
        .then(data => {
            loading.style.display = 'none';
            list.style.display = 'block';

            if (data.success && data.tasks) {
                currentWbTasks = data.tasks;
                renderWbModalTasks();
            } else {
                list.innerHTML = '<div class="text-center py-4 text-muted small">No task details found for this member.</div>';
            }
        })
        .catch(err => {
            loading.style.display = 'none';
            list.style.display = 'block';
            list.innerHTML = '<div class="alert alert-danger small mb-0">Failed to load member tasks.</div>';
        });
}

function filterWbModalTasks(filterType) {
    currentWbFilter = filterType;
    document.getElementById('wbFilterBtnOpen').classList.toggle('active', filterType === 'open');
    document.getElementById('wbFilterBtnAll').classList.toggle('active', filterType === 'all');
    renderWbModalTasks();
}

function renderWbModalTasks() {
    const list = document.getElementById('wbModalTasksList');
    if (!list) return;

    const todayStr = new Date().toISOString().split('T')[0];
    let openCount = 0;
    let allCount = currentWbTasks.length;

    const taskItems = currentWbTasks.map(t => {
        const isComp = (t.status === 'completed' || t.progress >= 100);
        const isOverdue = !isComp && t.due_date_raw && t.due_date_raw < todayStr;
        const isOpen = !isComp;
        if (isOpen) openCount++;

        return { ...t, isComp, isOverdue, isOpen };
    });

    document.getElementById('wbTabOpenCount').textContent = openCount;
    document.getElementById('wbTabAllCount').textContent = allCount;

    const filtered = taskItems.filter(t => currentWbFilter === 'all' ? true : t.isOpen);

    if (filtered.length === 0) {
        list.innerHTML = `
            <div class="text-center py-4 text-muted">
                <i class="bi bi-check-circle text-success fs-2 opacity-50 mb-1 d-block"></i>
                <div class="fw-semibold">No ${currentWbFilter === 'open' ? 'open' : ''} tasks found</div>
                <div class="small text-muted">${currentWbFilter === 'open' ? 'All assigned tasks for this member have been completed!' : 'No tasks recorded.'}</div>
            </div>
        `;
        return;
    }

    let html = '<div class="d-flex flex-column gap-2">';
    filtered.forEach(t => {
        const isTeam = t.is_team_project == 1;

        let statusBadge = '';
        if (t.isComp) {
            statusBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 rounded-pill" style="font-size: 10.5px;"><i class="bi bi-check-circle-fill me-1"></i>Completed</span>';
        } else if (t.isOverdue) {
            statusBadge = '<span class="badge bg-danger text-white px-2.5 py-1 rounded-pill" style="font-size: 10.5px;"><i class="bi bi-exclamation-triangle-fill me-1"></i>Overdue</span>';
        } else {
            statusBadge = '<span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2.5 py-1 rounded-pill" style="font-size: 10.5px;"><i class="bi bi-hourglass-split me-1"></i>In Progress</span>';
        }

        const prioBadge = t.priority === 1 ? '<span class="badge bg-danger-subtle text-danger py-0.5 px-2 rounded-pill" style="font-size: 10px;">Urgent</span>' :
                         (t.priority === 2 ? '<span class="badge bg-warning-subtle text-dark py-0.5 px-2 rounded-pill" style="font-size: 10px;">High</span>' : '');

        html += `
            <div class="p-3 border rounded-3 shadow-sm" style="background: ${t.isOverdue ? '#fff5f5' : '#ffffff'}; border-color: ${t.isOverdue ? '#fecaca' : '#e2e8f0'} !important;">
                <div class="d-flex align-items-center justify-content-between mb-1.5 gap-2">
                    <div class="d-flex align-items-center gap-2 flex-grow-1 text-truncate">
                        ${prioBadge}
                        <span class="fw-bold text-dark text-truncate" style="font-size: 13.5px; ${t.isComp ? 'text-decoration: line-through; opacity: 0.7;' : ''}">${t.title}</span>
                    </div>
                    <div class="d-flex align-items-center gap-1 flex-shrink-0">
                        ${statusBadge}
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between text-muted mb-2" style="font-size: 11.5px;">
                    <span>
                        ${isTeam ? '<span class="badge bg-success-subtle text-success py-0.5 px-1.5 me-1"><i class="bi bi-people-fill me-1"></i>Team</span>' : '<i class="bi bi-folder2 text-secondary me-1"></i>'}
                        ${t.project_name}
                    </span>
                    <span class="${t.isOverdue ? 'text-danger fw-bold' : ''}">
                        <i class="bi bi-calendar3 me-1"></i> Due: ${t.due_date}
                    </span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="progress flex-grow-1" style="height: 6px; background: #e2e8f0; border-radius: 4px;">
                        <div class="progress-bar ${t.isComp ? 'bg-success' : (t.isOverdue ? 'bg-danger' : (t.progress >= 50 ? 'bg-success' : (t.progress > 0 ? 'bg-warning' : 'bg-secondary')))}" style="width: ${t.progress}%;"></div>
                    </div>
                    <span class="fw-bold text-dark" style="font-size: 11px; min-width: 32px; text-align: right;">${t.progress}%</span>
                </div>
            </div>
        `;
    });
    html += '</div>';
    list.innerHTML = html;
}

function openMemberTasksOffcanvas(empId, empName) {
    currentMemberEmpId = empId;
    currentMemberEmpName = empName;
    document.getElementById('offcanvasMemberTitle').textContent = empName + ' - Active Tasks';
    const loading = document.getElementById('memberTasksLoading');
    const list = document.getElementById('memberTasksList');
    
    loading.style.display = 'block';
    list.style.display = 'none';

    const offcanvasEl = document.getElementById('offcanvasMemberTasks');
    const bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl) || new bootstrap.Offcanvas(offcanvasEl);
    bsOffcanvas.show();

    fetch(getMemberTasksQueryUrl(empId))
        .then(res => res.json())
        .then(data => {
            loading.style.display = 'none';
            list.style.display = 'block';
            
            if (data.success && data.tasks && data.tasks.length > 0) {
                const todayStr = new Date().toISOString().split('T')[0];
                let html = '<div class="list-group list-group-flush">';
                data.tasks.forEach(t => {
                    const isComp = (t.status === 'completed' || t.progress >= 100);
                    const isOverdue = !isComp && t.due_date_raw && t.due_date_raw < todayStr;
                    const isTeam = t.is_team_project == 1;
                    const statusBadge = isComp
                        ? '<span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 11px;">Completed</span>'
                        : (isOverdue
                            ? '<span class="badge bg-danger text-white" style="font-size: 11px;">Overdue</span>'
                            : '<span class="badge bg-warning-subtle text-dark border border-warning-subtle" style="font-size: 11px;">In Progress</span>');
                    html += `
                        <div class="list-group-item px-0 py-3 border-bottom">
                            <div class="d-flex align-items-center justify-content-between mb-1 gap-2">
                                <span class="fw-bold text-dark" style="font-size: 14px; ${isComp ? 'text-decoration: line-through; opacity: 0.75;' : ''}">${t.title}</span>
                                <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                    ${statusBadge}
                                    <span class="badge ${isComp ? 'bg-success text-white' : (isOverdue ? 'bg-danger-subtle text-danger border border-danger-subtle' : (t.progress > 0 ? 'bg-warning-subtle text-dark border' : 'bg-light text-secondary border'))}" style="font-size: 11px; min-width: 44px; text-align: center;">
                                        ${t.progress}%
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between text-muted small">
                                <span>
                                    ${isTeam ? '<span class="badge bg-success-subtle text-success py-0 px-1 me-1"><i class="bi bi-people-fill me-1"></i>Team</span>' : '<i class="bi bi-folder2 text-secondary me-1"></i>'}
                                    ${t.project_name}
                                </span>
                                <span class="${isOverdue ? 'text-danger fw-semibold' : ''}"><i class="bi bi-calendar3 me-1"></i> ${t.due_date}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 mt-2">
                                <div class="progress flex-grow-1" style="height: 7px; background: #e2e8f0; border-radius: 4px;">
                                    <div class="progress-bar ${isComp ? 'bg-success' : (isOverdue ? 'bg-danger' : (t.progress >= 50 ? 'bg-success' : (t.progress > 0 ? 'bg-warning' : 'bg-secondary')))}" style="width: ${t.progress}%;"></div>
                                </div>
                                <span class="fw-bold text-dark" style="font-size: 11.5px; min-width: 34px; text-align: right;">${t.progress}%</span>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                list.innerHTML = html;
            } else {
                list.innerHTML = `
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-clipboard-check text-success fs-1 opacity-50 mb-2"></i>
                        <p class="mb-0 fw-semibold">No tasks found for this member</p>
                        <p class="small text-muted">Click "+ Assign Task" above to assign items.</p>
                    </div>
                `;
            }
        })
        .catch(err => {
            loading.style.display = 'none';
            list.style.display = 'block';
            list.innerHTML = '<div class="alert alert-danger small">Failed to load member tasks.</div>';
        });
}

document.addEventListener("DOMContentLoaded", function () {

    const formCreateProject = document.getElementById('formCreateTeamProject');
    if (formCreateProject) {
        formCreateProject.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('btnSaveTeamProject');
            const feedback = document.getElementById('createTeamProjectFeedback');
            const originalText = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Creating...';

            const formData = new FormData(formCreateProject);

            fetch(formCreateProject.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.project) {
                    submitBtn.innerHTML = '<i class="bi bi-check2 me-1"></i> Created!';
                    feedback.style.display = 'block';
                    feedback.innerHTML = '<div class="alert alert-success py-2 px-3 small rounded-3 mb-0">Team Project created successfully!</div>';
                    
                    const assignSelect = document.getElementById('assignTaskProjectId');
                    if (assignSelect) {
                        const opt = document.createElement('option');
                        opt.value = data.project.id;
                        opt.textContent = '👥 ' + data.project.name;
                        opt.selected = true;
                        assignSelect.appendChild(opt);
                    }

                    setTimeout(() => {
                        const modalEl = document.getElementById('modalCreateTeamProject');
                        const bsModal = bootstrap.Modal.getInstance(modalEl);
                        if (bsModal) bsModal.hide();
                        window.location.reload();
                    }, 800);
                } else {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                    feedback.style.display = 'block';
                    feedback.innerHTML = '<div class="alert alert-danger py-2 px-3 small rounded-3 mb-0">' + (data.message || 'Failed to create team project') + '</div>';
                }
            })
            .catch(err => {
                console.error("Project creation failed:", err);
                formCreateProject.submit();
            });
        });
    }
});

// Right Slide Drawer (My Work Full Task Creator) Logic
const mwBaseUrl = '<?= $GLOBALS['base_url'] ?>';
const mwCurrentUserId = '<?= (int)($userId ?? ($_SESSION['user']['id'] ?? 0)) ?>';

function showMwToast(message, type = 'success') {
    let wrap = document.getElementById('mwToastWrap');
    if (!wrap) {
        wrap = document.createElement('div');
        wrap.id = 'mwToastWrap';
        wrap.className = 'mw-toast-wrap';
        document.body.appendChild(wrap);
    }
    const el = document.createElement('div');
    const isErr = type === 'error';
    el.className = 'mw-toast' + (isErr ? ' error' : '');
    const icon = isErr ? 'fa-circle-exclamation' : 'fa-circle-check';
    el.innerHTML = `<i class="fa-solid ${icon} mw-toast-icon"></i><span></span><button type="button" class="mw-toast-close" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>`;
    el.querySelector('span').textContent = message;
    el.querySelector('.mw-toast-close').addEventListener('click', () => el.remove());
    wrap.appendChild(el);
    setTimeout(() => { if (el.parentNode) el.remove(); }, 4500);
}

function mwPicked(id) {
    const i = document.querySelector('#' + id + ' input:checked');
    return { value: i ? i.value : '', name: i ? i.dataset.name : '' };
}

function updateNoticeBox() {
    const taskOwnerSelect = document.getElementById('taskAssignedToSelect');
    const taskReviewerSelect = document.getElementById('taskReviewerSelect');
    const noticeOwnerText = document.getElementById('noticeOwnerText');
    const noticeReviewerText = document.getElementById('noticeReviewerText');

    if (taskOwnerSelect && noticeOwnerText) {
        const o = mwPicked('taskAssignedToSelect');
        const firstName = (o.name || 'Owner').trim().split(' ')[0];
        noticeOwnerText.innerText = `${firstName} will receive the assignment.`;
    }
    if (taskReviewerSelect && noticeReviewerText) {
        const r = mwPicked('taskReviewerSelect');
        if (r.value) {
            const firstNameR = r.name.trim().split(' ')[0];
            noticeReviewerText.innerText = `${firstNameR} will be notified when the task is submitted for review.`;
            noticeReviewerText.style.display = 'block';
        } else {
            noticeReviewerText.style.display = 'none';
        }
    }
}

function resetAssignDrawerForm() {
    const formCreateTask = document.getElementById('mwCreateTaskForm');
    if (!formCreateTask) return;
    formCreateTask.reset();

    const editIdInput = document.getElementById('drawerEditTaskId');
    if (editIdInput) editIdInput.value = '';

    const expectedList = document.getElementById('expectedOutputList');
    if (expectedList) {
        expectedList.querySelectorAll('.mw-expected-row').forEach((r, idx) => { if (idx > 0) r.remove(); });
    }

    const rowRecurrenceEnd = document.getElementById('rowRecurrenceEnd');
    if (rowRecurrenceEnd) rowRecurrenceEnd.style.display = 'none';
    const selRec = document.getElementById('taskRecurrenceType');
    if (selRec) selRec.required = false;

    document.querySelectorAll('.mw-multi').forEach(b => {
        const s = b.querySelector('.mw-multi-search');
        if (s) s.value = '';
        b.querySelectorAll('.mw-multi-item').forEach(l => l.style.display = '');
        if (b._refresh) b._refresh();
    });
    updateNoticeBox();
}

function openAssignTaskDrawer(prefillUserId = null) {
    const drawer = document.getElementById('mwCreateTaskDrawer');
    const backdrop = document.getElementById('mwDrawerBackdrop');
    if (!drawer) return;

    resetAssignDrawerForm();

    if (prefillUserId) {
        const box = document.getElementById('taskAssignedToSelect');
        if (box) {
            const radios = box.querySelectorAll('input[type="radio"]');
            radios.forEach(r => {
                r.checked = (String(r.value) === String(prefillUserId));
            });
            if (box._refresh) box._refresh();
            updateNoticeBox();
        }
    }

    if (backdrop) backdrop.classList.add('show');
    drawer.classList.add('show');
    document.body.style.overflow = 'hidden';

    setTimeout(() => {
        const titleInput = document.getElementById('drawerTaskTitle');
        if (titleInput) titleInput.focus();
    }, 320);
}

function closeAssignTaskDrawer() {
    const drawer = document.getElementById('mwCreateTaskDrawer');
    const backdrop = document.getElementById('mwDrawerBackdrop');
    if (drawer) drawer.classList.remove('show');
    if (backdrop) backdrop.classList.remove('show');
    document.body.style.overflow = '';
    resetAssignDrawerForm();
}

document.addEventListener('DOMContentLoaded', function() {
    const taskOwnerSelect = document.getElementById('taskAssignedToSelect');
    const taskReviewerSelect = document.getElementById('taskReviewerSelect');
    if (taskOwnerSelect) taskOwnerSelect.addEventListener('change', updateNoticeBox);
    if (taskReviewerSelect) taskReviewerSelect.addEventListener('change', updateNoticeBox);
    updateNoticeBox();

    // Recurring toggle
    const chkIsRecurring = document.getElementById('chkIsRecurring');
    const rowRecurrenceEnd = document.getElementById('rowRecurrenceEnd');
    if (chkIsRecurring && rowRecurrenceEnd) {
        chkIsRecurring.addEventListener('change', () => {
            rowRecurrenceEnd.style.display = chkIsRecurring.checked ? 'block' : 'none';
            const selRec = document.getElementById('taskRecurrenceType');
            if (selRec) selRec.required = chkIsRecurring.checked;
        });
    }

    // Multi-select dropdowns
    document.querySelectorAll('.mw-multi').forEach(box => {
        const txt = box.querySelector('.mw-multi-text');
        const checks = box.querySelectorAll('input[type=checkbox], input[type=radio]');
        const refresh = () => {
            const names = [...checks].filter(i => i.checked).map(i => i.dataset.name);
            txt.innerText = names.length === 0 ? box.dataset.placeholder : (names.length <= 2 ? names.join(', ') : names.length + ' selected');
        };
        box._refresh = refresh;
        box.querySelector('.mw-multi-btn').addEventListener('click', () => {
            const wasOpen = box.classList.contains('open');
            document.querySelectorAll('.mw-multi.open').forEach(b => b.classList.remove('open'));
            if (!wasOpen) box.classList.add('open');
        });
        checks.forEach(i => i.addEventListener('change', () => {
            if (box.dataset.single === '1' && i.checked) {
                checks.forEach(o => { if (o !== i) o.checked = false; });
                box.classList.remove('open');
            }
            refresh();
            box.dispatchEvent(new Event('change', { bubbles: true }));
        }));
        checks.forEach(i => i.addEventListener('click', () => {
            if (i.type !== 'radio' || box.dataset.clearable !== '1') return;
            if (i.dataset.was === '1') {
                i.checked = false;
                i.dataset.was = '0';
                box.classList.remove('open');
                refresh();
                box.dispatchEvent(new Event('change', { bubbles: true }));
            } else {
                checks.forEach(o => o.dataset.was = '0');
                i.dataset.was = '1';
            }
        }));
        refresh();
        box.querySelector('.mw-multi-search').addEventListener('input', function () {
            const q = this.value.toLowerCase();
            box.querySelectorAll('.mw-multi-item').forEach(l => l.style.display = l.innerText.toLowerCase().includes(q) ? '' : 'none');
        });
    });

    document.addEventListener('click', e => {
        document.querySelectorAll('.mw-multi.open').forEach(b => { if (!b.contains(e.target)) b.classList.remove('open'); });
    }, true);

    // Acceptance checklist dynamic rows
    const expectedList = document.getElementById('expectedOutputList');
    const btnAddExpected = document.getElementById('btnAddExpectedOutput');
    if (expectedList && btnAddExpected) {
        btnAddExpected.addEventListener('click', function () {
            const row = document.createElement('div');
            row.className = 'd-flex align-items-start gap-2 mb-2 mw-expected-row';
            row.innerHTML = '<textarea name="description[]" class="mw-form-textarea" rows="1" placeholder="Another checklist item"></textarea>' +
                '<button type="button" class="mw-btn-create" title="Remove" style="white-space:nowrap;background:#C92A2A;"><i class="fa-solid fa-xmark"></i></button>';
            row.querySelector('button').addEventListener('click', () => row.remove());
            expectedList.appendChild(row);
            row.querySelector('textarea').focus();
        });
    }

    // AJAX Form Submit to My Work store-task endpoint
    const formCreateTask = document.getElementById('mwCreateTaskForm');
    const btnSubmitDrawer = document.getElementById('btnSubmitDrawer');
    if (formCreateTask) {
        formCreateTask.addEventListener('submit', function(e) {
            e.preventDefault();
            const ownerVal = mwPicked('taskAssignedToSelect').value || mwCurrentUserId;
            const reviewerVal = mwPicked('taskReviewerSelect').value;
            if (reviewerVal && ownerVal && String(reviewerVal) === String(ownerVal) &&
                !confirm('Assignee and Reviewer are the same person. Continue?')) {
                return;
            }

            if (btnSubmitDrawer) {
                btnSubmitDrawer.disabled = true;
                btnSubmitDrawer.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Creating...';
            }

            const formData = new FormData(formCreateTask);
            fetch(`${mwBaseUrl}/my-work/store-task?ajax=1`, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(async res => {
                const text = await res.text();
                try { return JSON.parse(text); }
                catch (_) { throw new Error('Invalid server response.'); }
            })
            .then(data => {
                if (data.status === 'success') {
                    closeAssignTaskDrawer();
                    showMwToast(data.message || 'Task created and assigned successfully.', 'success');
                    setTimeout(() => window.location.reload(), 900);
                } else {
                    showMwToast(data.message || 'Failed to create task.', 'error');
                }
            })
            .catch(err => {
                console.error('Error creating task:', err);
                showMwToast(err.message || 'An error occurred while creating the task.', 'error');
            })
            .finally(() => {
                if (btnSubmitDrawer) {
                    btnSubmitDrawer.disabled = false;
                    btnSubmitDrawer.innerHTML = '<i class="fa-solid fa-plus me-1"></i> Create & assign';
                }
            });
        });
    }
});

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const drawer = document.getElementById('mwCreateTaskDrawer');
        if (drawer && drawer.classList.contains('show')) {
            closeAssignTaskDrawer();
        }
    }
});

// Client-side tab switching (without page reload) + on-scroll pagination (25 rows per batch)
window.teamSentinelObserver = null;
window.initTeamTableInteractions = function () {
    const batchSize = 25;
    const sentinel = document.getElementById('teamTableScrollSentinel');
    const emptyRow = document.getElementById('teamTabEmptyRow');
    const tabLinks = document.querySelectorAll('.team-tabs .team-tab-item[data-tab]');
    let loading = false;

    if (window.teamSentinelObserver) {
        try { window.teamSentinelObserver.disconnect(); } catch (e) {}
        window.teamSentinelObserver = null;
    }

    function updateSentinelCounts() {
        const matchingRows = Array.from(document.querySelectorAll('tr.team-member-row[data-tab-match="1"]'));
        const visibleCount = matchingRows.filter(r => !r.classList.contains('d-none')).length;
        const totalMatching = matchingRows.length;

        if (emptyRow) {
            emptyRow.classList.toggle('d-none', totalMatching > 0);
        }

        if (sentinel) {
            const visibleCountEl = document.getElementById('teamTableVisibleCount');
            const totalCountEl = document.getElementById('teamTableTotalCount');
            if (visibleCountEl) visibleCountEl.textContent = String(visibleCount);
            if (totalCountEl) totalCountEl.textContent = String(totalMatching);
            sentinel.classList.toggle('d-none', visibleCount >= totalMatching || totalMatching === 0);
        }
    }

    function applyTeamTab(tabName, updateHistory) {
        tabLinks.forEach(link => {
            link.classList.toggle('active', link.dataset.tab === tabName);
        });

        const tabInput = document.querySelector('#teamFilterForm input[name="tab"]');
        if (tabInput) tabInput.value = tabName;

        const allRows = document.querySelectorAll('tr.team-member-row');
        let shownInBatch = 0;
        allRows.forEach(row => {
            let matches = true;
            if (tabName === 'top_performers') {
                matches = row.dataset.topPerformer === '1';
            } else if (tabName === 'needs_attention') {
                matches = row.dataset.needsAttention === '1';
            }
            row.dataset.tabMatch = matches ? '1' : '0';
            if (matches && shownInBatch < batchSize) {
                row.classList.remove('d-none');
                shownInBatch++;
            } else {
                row.classList.add('d-none');
            }
        });

        updateSentinelCounts();

        if (updateHistory) {
            try {
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tabName);
                window.history.replaceState({}, '', url.toString());

                const sortBtn = document.getElementById('teamSortScoreBtn');
                if (sortBtn) {
                    const sortUrl = new URL(sortBtn.href, window.location.origin);
                    sortUrl.searchParams.set('tab', tabName);
                    sortBtn.href = sortUrl.toString();
                }
            } catch (e) {}
        }
    }

    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            applyTeamTab(this.dataset.tab || 'all', true);
        });
    });

    updateSentinelCounts();

    function revealNextBatch() {
        const curSentinel = document.getElementById('teamTableScrollSentinel');
        if (loading || !curSentinel) return;
        const hiddenMatchingRows = document.querySelectorAll('tr.team-member-row[data-tab-match="1"].d-none');
        if (hiddenMatchingRows.length === 0) {
            updateSentinelCounts();
            return;
        }
        loading = true;
        const countToReveal = Math.min(batchSize, hiddenMatchingRows.length);
        for (let i = 0; i < countToReveal; i++) {
            hiddenMatchingRows[i].classList.remove('d-none');
        }
        updateSentinelCounts();
        loading = false;
    }

    if (sentinel && 'IntersectionObserver' in window) {
        window.teamSentinelObserver = new IntersectionObserver((entries) => {
            if (entries.some(entry => entry.isIntersecting)) {
                revealNextBatch();
            }
        }, { rootMargin: '200px 0px' });
        window.teamSentinelObserver.observe(sentinel);
    }

    window._teamRevealNextBatch = revealNextBatch;
};

window.addEventListener('scroll', function() {
    const sentinel = document.getElementById('teamTableScrollSentinel');
    if (!sentinel || sentinel.classList.contains('d-none')) return;
    const rect = sentinel.getBoundingClientRect();
    if (rect.top <= window.innerHeight + 200 && typeof window._teamRevealNextBatch === 'function') {
        window._teamRevealNextBatch();
    }
}, { passive: true });

window.initTeamTableInteractions();

// AJAX Filter Apply & Reset (without full page reload)
(function() {
    const filterForm = document.getElementById('teamFilterForm');
    const resetBtn = document.getElementById('btnResetFilters');
    const applyBtn = document.getElementById('btnApplyFilters');
    const defaultMonth = <?= json_encode(date('Y-m')) ?>;

    function loadTeamPerformanceAjax(targetUrl, pushState) {
        const contentWrap = document.getElementById('teamDashboardContent');
        if (!contentWrap) {
            window.location.href = targetUrl;
            return;
        }

        const origApplyHtml = applyBtn ? applyBtn.innerHTML : '';
        if (applyBtn) {
            applyBtn.disabled = true;
            applyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Loading';
        }
        contentWrap.style.opacity = '0.5';
        contentWrap.style.pointerEvents = 'none';

        fetch(targetUrl, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.text();
        })
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const newContent = doc.getElementById('teamDashboardContent');
            if (!newContent) {
                window.location.href = targetUrl;
                return;
            }

            contentWrap.innerHTML = newContent.innerHTML;
            contentWrap.style.opacity = '1';
            contentWrap.style.pointerEvents = '';

            // Sync Export button href
            const newExport = doc.getElementById('teamExportBtn');
            const curExport = document.getElementById('teamExportBtn');
            if (newExport && curExport) {
                curExport.href = newExport.href;
            }

            // Update browser URL without reload
            if (pushState) {
                window.history.pushState({ url: targetUrl }, '', targetUrl);
            }

            // Re-render ApexChart & Table interactions
            if (typeof window.renderTeamDeliveryChart === 'function') {
                window.renderTeamDeliveryChart();
            }
            if (typeof window.initTeamTableInteractions === 'function') {
                window.initTeamTableInteractions();
            }
        })
        .catch(() => {
            window.location.href = targetUrl;
        })
        .finally(() => {
            if (applyBtn) {
                applyBtn.disabled = false;
                applyBtn.innerHTML = origApplyHtml;
            }
        });
    }

    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const params = new URLSearchParams(new FormData(filterForm));
            const targetUrl = filterForm.action + '?' + params.toString();
            loadTeamPerformanceAjax(targetUrl, true);
        });
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const monthEl = document.getElementById('filterMonth');
            const memberEl = document.getElementById('filterMember');
            const projectEl = document.getElementById('filterProject');
            const statusEl = document.getElementById('filterStatus');
            const typeEl = document.getElementById('filterType');
            const tabEl = filterForm ? filterForm.querySelector('input[name="tab"]') : null;
            const sortEl = filterForm ? filterForm.querySelector('input[name="sort"]') : null;

            if (monthEl) monthEl.value = defaultMonth;
            if (memberEl) memberEl.value = '';
            if (projectEl) projectEl.value = '';
            if (statusEl) statusEl.value = '';
            if (typeEl) typeEl.value = 'assigned_self';
            if (tabEl) tabEl.value = 'all';
            if (sortEl) sortEl.value = 'score_desc';

            loadTeamPerformanceAjax(resetBtn.href, true);
        });
    }

    window.addEventListener('popstate', function() {
        loadTeamPerformanceAjax(window.location.href, false);
    });
})();
</script>
