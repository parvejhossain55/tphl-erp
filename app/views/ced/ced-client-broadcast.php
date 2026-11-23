<?php
$page_title           = $page_title ?? 'CED Client SMS & Email Broadcast';
$page                 = 'CED';
$show_breadcrumb      = true;
$base_url             = $base_url ?? ($GLOBALS['base_url'] ?? '');
$projectsList         = $projects_list ?? [];
$agentsList           = $agents_list ?? [];
$projectAgentMap      = $project_agent_map ?? [];
$globalSmsTesting     = !empty($global_sms_testing);
$preselectedProjectId = (int)($preselected_project_id ?? 0);
$preselectedAgentId   = (int)($preselected_agent_id ?? 0);
?>

<style>
:root {
    --cb-brand: #0d5c3a;
    --cb-brand-hover: #09452b;
    --cb-brand-soft: #e8f5e9;
    --cb-brand-border: #bbf7d0;
    --cb-border: #e2e8f0;
    --cb-surface: #ffffff;
    --cb-subtle: #f8fafc;
    --cb-text: #0f172a;
    --cb-muted: #64748b;
}

.cb-page-wrap {
    width: 100%;
    max-width: 100%;
    padding: 16px 18px 36px;
    box-sizing: border-box;
    overflow-x: clip;
}

/* Header */
.cb-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;
}
.cb-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}
.cb-header-icon {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: var(--cb-brand-soft);
    color: var(--cb-brand);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 19px;
    flex-shrink: 0;
}
.cb-header-title {
    margin: 0;
    font-size: 19px;
    font-weight: 700;
    color: var(--cb-brand);
    letter-spacing: -0.3px;
}
.cb-header-sub {
    font-size: 12.5px;
    color: var(--cb-muted);
    margin: 0;
}
.cb-header-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

/* Cards */
.cb-card {
    background: var(--cb-surface);
    border: 1px solid var(--cb-border);
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    min-width: 0;
}

/* Tabs */
.cb-tabs {
    display: flex;
    gap: 6px;
    border-bottom: 1px solid var(--cb-border);
    margin-bottom: 16px;
    padding-bottom: 0;
}
.cb-tabs .nav-link {
    border: none;
    border-bottom: 2px solid transparent;
    background: transparent;
    color: var(--cb-muted);
    font-weight: 600;
    font-size: 13px;
    padding: 9px 16px;
    border-radius: 8px 8px 0 0;
    transition: all 0.15s ease;
}
.cb-tabs .nav-link:hover {
    color: var(--cb-brand);
    background: #f1f5f9;
}
.cb-tabs .nav-link.active {
    color: var(--cb-brand);
    background: var(--cb-brand-soft);
    border-bottom-color: var(--cb-brand);
}

/* Filter Grid (CSS Grid avoids theme .g-2/.g-3 flex-wrap conflicts) */
.cb-filter-grid {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 10px 12px;
    align-items: end;
}
.cb-filter-field {
    min-width: 0;
}
.cb-filter-field.span-2 {
    grid-column: span 2;
}
.cb-filter-field.span-3 {
    grid-column: span 3;
}
.cb-filter-label {
    display: block;
    font-size: 11.5px;
    font-weight: 600;
    color: #475569;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.cb-page-wrap .form-control-sm,
.cb-page-wrap .form-select-sm {
    min-height: 34px;
    font-size: 12.5px;
    border-color: #cbd5e1;
    border-radius: 7px;
}
.cb-page-wrap .form-control-sm:focus,
.cb-page-wrap .form-select-sm:focus {
    border-color: var(--cb-brand);
    box-shadow: 0 0 0 3px rgba(13, 92, 58, 0.12);
}

/* Align Select2 multi-select cleanly with form-select-sm */
.cb-page-wrap .select2-container {
    width: 100% !important;
    max-width: 100% !important;
}
.cb-page-wrap .select2-container--default .select2-selection--multiple {
    min-height: 34px !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 7px !important;
    padding: 2px 6px !important;
    font-size: 12.5px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
}
.cb-page-wrap .select2-container--default.select2-container--focus .select2-selection--multiple {
    border-color: var(--cb-brand) !important;
    box-shadow: 0 0 0 3px rgba(13, 92, 58, 0.12) !important;
}
.cb-page-wrap .select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: var(--cb-brand-soft) !important;
    border: 1px solid var(--cb-brand-border) !important;
    color: var(--cb-brand) !important;
    font-size: 11.5px;
    margin-top: 2px !important;
    margin-bottom: 2px !important;
    border-radius: 5px !important;
}
.cb-page-wrap .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    color: var(--cb-brand) !important;
}

.cb-filter-actions {
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Brand Buttons */
.cb-btn-brand {
    background-color: var(--cb-brand);
    border-color: var(--cb-brand);
    color: #fff;
}
.cb-btn-brand:hover,
.cb-btn-brand:focus {
    background-color: var(--cb-brand-hover);
    border-color: var(--cb-brand-hover);
    color: #fff;
}
.cb-btn-brand:disabled {
    background-color: #94a3b8;
    border-color: #94a3b8;
    color: #fff;
    cursor: not-allowed;
}
.cb-btn-subtle {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #334155;
    font-size: 11.5px;
    font-weight: 500;
    padding: 4px 10px;
    border-radius: 6px;
    transition: all 0.15s ease;
}
.cb-btn-subtle:hover {
    background: var(--cb-brand-soft);
    border-color: var(--cb-brand-border);
    color: var(--cb-brand);
}

/* KPI Grid */
.cb-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 16px;
}
.cb-kpi {
    background: var(--cb-surface);
    border: 1px solid var(--cb-border);
    border-radius: 10px;
    padding: 13px 15px;
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}
.cb-kpi-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}
.cb-kpi-icon.green { background: #e8f5e9; color: #0d5c3a; }
.cb-kpi-icon.blue  { background: #eff6ff; color: #1d4ed8; }
.cb-kpi-icon.teal  { background: #f0fdfa; color: #0f766e; }
.cb-kpi-icon.red   { background: #fef2f2; color: #dc2626; }
.cb-kpi-body {
    min-width: 0;
}
.cb-kpi-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: var(--cb-muted);
    font-weight: 600;
}
.cb-kpi-val {
    font-size: 20px;
    font-weight: 700;
    color: var(--cb-text);
    line-height: 1.25;
}
.cb-kpi-sub {
    font-size: 11px;
    color: var(--cb-muted);
    margin-top: 2px;
}

/* Main 2-Column Workspace Grid */
.cb-workspace-grid {
    display: grid;
    grid-template-columns: minmax(0, 7fr) minmax(0, 5fr);
    gap: 16px;
    align-items: start;
}

/* Client Table */
.cb-card-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 11px 14px;
    background: var(--cb-subtle);
    border-bottom: 1px solid var(--cb-border);
}
.cb-card-title {
    font-size: 13.5px;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 7px;
}
.cb-table-wrap {
    max-height: 66vh;
    overflow-y: auto;
    overflow-x: auto;
}
.cb-table {
    width: 100%;
    font-size: 12px;
    border-collapse: separate;
    border-spacing: 0;
    margin-bottom: 0;
}
.cb-table thead th {
    position: sticky;
    top: 0;
    z-index: 3;
    background: #f8fafc;
    color: #475569;
    font-weight: 600;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    padding: 9px 10px;
    border-bottom: 1px solid #cbd5e1;
    white-space: nowrap;
}
.cb-table tbody td {
    padding: 9px 10px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.cb-table tbody tr {
    cursor: pointer;
    transition: background-color 0.12s ease;
}
.cb-table tbody tr:hover {
    background-color: #f8fafc;
}
.cb-table tbody tr.is-selected {
    background-color: #f0fdf4;
}
.cb-contact-line {
    font-size: 11.5px;
    line-height: 1.45;
    white-space: nowrap;
}
.cb-due-mini {
    font-size: 11px;
    line-height: 1.35;
    white-space: nowrap;
}

/* Composer */
.cb-composer-sticky {
    position: sticky;
    top: 76px;
}
.cb-composer-body {
    padding: 14px 16px 16px;
}
.cb-segmented {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 4px;
    background: #f1f5f9;
    padding: 4px;
    border-radius: 9px;
    border: 1px solid var(--cb-border);
}
.cb-channel-btn {
    border: none;
    background: transparent;
    color: #475569;
    font-size: 12px;
    font-weight: 600;
    padding: 7px 8px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
}
.cb-channel-btn:hover {
    color: var(--cb-brand);
    background: rgba(255, 255, 255, 0.7);
}
.cb-channel-btn.active {
    background: var(--cb-brand);
    color: #ffffff;
    box-shadow: 0 1px 3px rgba(13, 92, 58, 0.25);
}

.cb-tag-chip {
    display: inline-block;
    font-size: 11px;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    background: #f0fdf4;
    color: var(--cb-brand);
    border: 1px solid var(--cb-brand-border);
    border-radius: 6px;
    padding: 2px 7px;
    margin: 2px 3px 2px 0;
    cursor: pointer;
    user-select: none;
    transition: all 0.15s ease;
}
.cb-tag-chip:hover {
    background: var(--cb-brand);
    color: #fff;
    border-color: var(--cb-brand);
}

.cb-options-box {
    background: var(--cb-subtle);
    border: 1px solid var(--cb-border);
    border-radius: 8px;
    padding: 10px 12px;
}

.cb-empty-state {
    padding: 44px 20px;
    text-align: center;
    color: var(--cb-muted);
}
.cb-empty-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #f1f5f9;
    color: #94a3b8;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    margin-bottom: 10px;
}

/* Preview Modal Styles */
.cb-prev-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    background: #f8fafc;
    border: 1px solid var(--cb-border);
    border-radius: 10px;
    padding: 10px 14px;
    margin-bottom: 16px;
}
.cb-prev-section-title {
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--cb-muted);
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.cb-sms-phone-card {
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    border-radius: 14px;
    padding: 14px;
    margin-bottom: 16px;
}
.cb-sms-phone-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 10px;
    margin-bottom: 12px;
    border-bottom: 1px solid #e2e8f0;
    font-size: 12px;
}
.cb-sms-sender-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: var(--cb-brand);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
}
.cb-sms-bubble {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 12px 15px;
    font-size: 13.5px;
    line-height: 1.55;
    color: #1e293b;
    white-space: pre-wrap;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
}
.cb-email-frame {
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    overflow: hidden;
    background: #f4f6f8;
}
.cb-email-meta-bar {
    background: #ffffff;
    padding: 10px 14px;
    border-bottom: 1px solid #e2e8f0;
    font-size: 12px;
    color: #475569;
}
.cb-email-body-wrap {
    padding: 18px 14px;
}
.cb-email-card {
    max-width: 560px;
    margin: 0 auto;
    background: #ffffff;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    box-shadow: 0 3px 10px rgba(15, 23, 42, 0.06);
}
.cb-email-card-hdr {
    background: linear-gradient(135deg, #0d5c3a, #15803d);
    color: #ffffff;
    padding: 16px 20px;
}
.cb-email-card-body {
    padding: 20px;
    font-size: 13.5px;
    line-height: 1.65;
    color: #334155;
    white-space: pre-wrap;
}
.cb-email-card-ftr {
    background: #f8fafc;
    padding: 10px 16px;
    text-align: center;
    font-size: 11px;
    color: #94a3b8;
    border-top: 1px solid #f1f5f9;
}

/* Delivery Logs Modal Table */
.cb-logs-table {
    width: 100%;
    font-size: 12px;
    border-collapse: collapse;
    margin-bottom: 0;
}
.cb-logs-table thead th {
    background: #f8fafc;
    color: #475569;
    font-weight: 600;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    padding: 10px 12px;
    border-bottom: 1px solid #cbd5e1;
    white-space: nowrap;
}
.cb-logs-table tbody td {
    padding: 10px 12px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.cb-logs-table tbody tr:hover {
    background: #f8fafc;
}
.cb-log-msg-cell {
    max-width: 260px;
    white-space: normal;
    overflow-wrap: anywhere;
    color: #334155;
    line-height: 1.45;
}
.cb-log-gw-cell {
    max-width: 190px;
    white-space: normal;
    overflow-wrap: anywhere;
    color: #475569;
    line-height: 1.4;
}
.cb-log-actions {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
}
.cb-log-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    height: 28px;
    padding: 0 10px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 500;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #334155;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.15s ease;
}
.cb-log-btn:hover {
    background: var(--cb-brand-soft);
    border-color: var(--cb-brand-border);
    color: var(--cb-brand);
}
.cb-log-btn.wa {
    width: 30px;
    padding: 0;
    border-color: #bbf7d0;
    background: #f0fdf4;
    color: #15803d;
}
.cb-log-btn.wa:hover {
    background: #15803d;
    border-color: #15803d;
    color: #ffffff;
}
.cb-log-btn.done {
    border-color: #bbf7d0;
    background: #e8f5e9;
    color: #0d5c3a;
    font-weight: 600;
}
.cb-log-btn.done:hover {
    background: #0d5c3a;
    border-color: #0d5c3a;
    color: #ffffff;
}

/* Responsive Breakpoints */
@media (max-width: 1279px) {
    .cb-filter-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}
@media (max-width: 1099px) {
    .cb-workspace-grid {
        grid-template-columns: minmax(0, 1fr);
    }
    .cb-composer-sticky {
        position: static;
    }
}
@media (max-width: 991px) {
    .cb-kpi-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .cb-filter-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .cb-filter-field.span-2,
    .cb-filter-field.span-3 {
        grid-column: span 2;
    }
}
@media (max-width: 575px) {
    .cb-kpi-grid,
    .cb-filter-grid {
        grid-template-columns: minmax(0, 1fr);
    }
    .cb-filter-field.span-2,
    .cb-filter-field.span-3 {
        grid-column: span 1;
    }
}
</style>

<div class="cb-page-wrap">
    <!-- Page Header -->
    <div class="cb-header">
        <div class="cb-header-left">
            <div class="cb-header-icon">
                <i class="fa-solid fa-paper-plane"></i>
            </div>
            <div>
                <h4 class="cb-header-title">CED Client SMS &amp; Email Broadcast</h4>
                <p class="cb-header-sub">Filter clients by project, due status, or upcoming EMI and dispatch personalized SMS &amp; Email</p>
            </div>
        </div>
        <div class="cb-header-actions">
            <a href="<?= $base_url ?>/ced-project-dashboard" class="btn btn-sm btn-light border text-dark">
                <i class="fa-solid fa-chart-line text-success me-1"></i>Project Dashboard
            </a>
            <a href="<?= $base_url ?>/client-wise-due-tracking" class="btn btn-sm btn-light border text-dark">
                <i class="fa-solid fa-money-bill-trend-up text-success me-1"></i>Due Tracking &amp; Mgt
            </a>
        </div>
    </div>

    <?php if ($globalSmsTesting): ?>
        <div class="alert alert-warning d-flex align-items-center py-2 px-3 mb-3 small rounded-3">
            <i class="fa-solid fa-flask me-2"></i>
            <div><strong>Global SMS Testing Mode is ON</strong> in system config. SMS dispatches will be logged as <code>skipped_testing</code> without calling the Automas gateway.</div>
        </div>
    <?php endif; ?>

    <!-- Tabs -->
    <ul class="nav cb-tabs" id="cbMainTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-compose-btn" data-bs-toggle="tab" data-bs-target="#tab-compose" type="button" role="tab">
                <i class="fa-solid fa-pen-to-square me-1"></i>Compose &amp; Send
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-history-btn" data-bs-toggle="tab" data-bs-target="#tab-history" type="button" role="tab">
                <i class="fa-solid fa-clock-rotate-left me-1"></i>Send History &amp; Logs
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- TAB 1: COMPOSE & SEND -->
        <div class="tab-pane fade show active" id="tab-compose" role="tabpanel">
            <!-- Filter Card -->
            <div class="cb-card p-3 mb-3">
                <div class="cb-filter-grid">
                    <div class="cb-filter-field span-2">
                        <label class="cb-filter-label" for="cbProjectIds">Project(s)</label>
                        <select id="cbProjectIds" class="form-select form-select-sm" multiple data-placeholder="All Projects">
                            <?php foreach ($projectsList as $proj): ?>
                                <option value="<?= (int)$proj['id'] ?>" <?= ($preselectedProjectId === (int)$proj['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($proj['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="cb-filter-field">
                        <label class="cb-filter-label" for="cbAgentId">CED Agent</label>
                        <select id="cbAgentId" class="form-select form-select-sm">
                            <option value="">All Agents</option>
                            <?php foreach ($agentsList as $ag): ?>
                                <option value="<?= (int)$ag['user_id'] ?>" <?= ($preselectedAgentId === (int)$ag['user_id']) ? 'selected' : '' ?>><?= htmlspecialchars($ag['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="cb-filter-field">
                        <label class="cb-filter-label" for="cbDueFilter">Due Filter</label>
                        <select id="cbDueFilter" class="form-select form-select-sm">
                            <option value="">All Clients</option>
                            <option value="any_due">Any Due (&gt; 0)</option>
                            <option value="land_share_due">Land Share Due</option>
                            <option value="dc_due">Dev. Charge (DC) Due</option>
                            <option value="emi_due">Regular EMI Due</option>
                            <option value="no_due">No Due / Clear</option>
                        </select>
                    </div>
                    <div class="cb-filter-field">
                        <label class="cb-filter-label" for="cbUpcomingEmi">Upcoming EMI</label>
                        <select id="cbUpcomingEmi" class="form-select form-select-sm">
                            <option value="">Any</option>
                            <option value="7">Next 7 Days</option>
                            <option value="15">Next 15 Days</option>
                            <option value="30">Next 30 Days</option>
                            <option value="this_month">This Month</option>
                        </select>
                    </div>
                    <div class="cb-filter-field">
                        <label class="cb-filter-label" for="cbMinDue">Min Due (৳)</label>
                        <input type="number" id="cbMinDue" class="form-control form-control-sm" min="0" step="1000" placeholder="e.g. 50000">
                    </div>

                    <div class="cb-filter-field">
                        <label class="cb-filter-label" for="cbFollowupFilter">Follow-up</label>
                        <select id="cbFollowupFilter" class="form-select form-select-sm">
                            <option value="">Any</option>
                            <option value="today">Today's Follow-up</option>
                            <option value="upcoming_5d">Upcoming (5 Days)</option>
                            <option value="overdue">Overdue Follow-up</option>
                        </select>
                    </div>
                    <div class="cb-filter-field">
                        <label class="cb-filter-label" for="cbBookingStatus">Booking Status</label>
                        <select id="cbBookingStatus" class="form-select form-select-sm">
                            <option value="">All Active</option>
                            <option value="registered">Registered</option>
                            <option value="booked">Booked (Unregistered)</option>
                        </select>
                    </div>
                    <div class="cb-filter-field span-3">
                        <label class="cb-filter-label" for="cbSearch">Quick Search</label>
                        <input type="text" id="cbSearch" class="form-control form-control-sm" placeholder="Client name, phone, email, flat, project...">
                    </div>
                    <div class="cb-filter-field">
                        <label class="cb-filter-label">&nbsp;</label>
                        <div class="cb-filter-actions">
                            <button type="button" id="cbBtnFilter" class="btn btn-sm cb-btn-brand flex-grow-1">
                                <i class="fa-solid fa-filter me-1"></i>Apply
                            </button>
                            <button type="button" id="cbBtnReset" class="btn btn-sm btn-light border" title="Reset Filters">
                                <i class="fa-solid fa-rotate-left"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2 pt-2 border-top">
                    <div class="form-check small mb-0">
                        <input class="form-check-input" type="checkbox" id="cbExcludeAdvance" checked>
                        <label class="form-check-label text-muted" for="cbExcludeAdvance">
                            Exclude Advance-covered clients when filtering <strong>Upcoming EMI</strong>
                        </label>
                    </div>
                    <div class="small text-muted" id="cbFilterStatusHint" style="font-size:11.5px;">
                        Filters update client list automatically
                    </div>
                </div>
            </div>

            <!-- KPI Strip -->
            <div class="cb-kpi-grid">
                <div class="cb-kpi">
                    <div class="cb-kpi-icon green"><i class="fa-solid fa-users"></i></div>
                    <div class="cb-kpi-body">
                        <div class="cb-kpi-label">Filtered Clients</div>
                        <div class="cb-kpi-val" id="kpiTotalClients">0</div>
                    </div>
                </div>
                <div class="cb-kpi">
                    <div class="cb-kpi-icon blue"><i class="fa-solid fa-user-check"></i></div>
                    <div class="cb-kpi-body">
                        <div class="cb-kpi-label">Selected Clients</div>
                        <div class="cb-kpi-val text-primary" id="kpiSelectedClients">0</div>
                        <div class="cb-kpi-sub" id="kpiSelectedValidInfo">Valid SMS: 0 &middot; Valid Email: 0</div>
                    </div>
                </div>
                <div class="cb-kpi">
                    <div class="cb-kpi-icon teal"><i class="fa-solid fa-address-book"></i></div>
                    <div class="cb-kpi-body">
                        <div class="cb-kpi-label">Valid Phone / Email</div>
                        <div class="cb-kpi-val">
                            <span style="color:var(--cb-brand);" id="kpiValidPhone">0</span>
                            <span class="text-muted fs-6 fw-normal">/</span>
                            <span class="text-primary" id="kpiValidEmail">0</span>
                        </div>
                    </div>
                </div>
                <div class="cb-kpi">
                    <div class="cb-kpi-icon red"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                    <div class="cb-kpi-body">
                        <div class="cb-kpi-label">Total Due (Filtered)</div>
                        <div class="cb-kpi-val text-danger" id="kpiTotalDue">৳0</div>
                    </div>
                </div>
            </div>

            <!-- Main 2-Column Workspace -->
            <div class="cb-workspace-grid">
                <!-- Left: Client Selection Table -->
                <div class="cb-card overflow-hidden">
                    <div class="cb-card-header">
                        <div class="cb-card-title">
                            <i class="fa-solid fa-users" style="color:var(--cb-brand);"></i>
                            <span>Client Recipients List</span>
                        </div>
                        <div class="d-flex flex-wrap gap-1">
                            <button type="button" class="cb-btn-subtle" id="cbSelectAllBtn">Select All</button>
                            <button type="button" class="cb-btn-subtle" id="cbSelectValidSmsBtn">Valid SMS</button>
                            <button type="button" class="cb-btn-subtle" id="cbSelectValidEmailBtn">Valid Email</button>
                            <button type="button" class="cb-btn-subtle" id="cbClearSelectBtn">Clear</button>
                        </div>
                    </div>

                    <div class="cb-table-wrap">
                        <table class="cb-table" id="cbClientsTable">
                            <thead>
                                <tr>
                                    <th style="width:36px;" class="text-center">
                                        <input type="checkbox" class="form-check-input" id="cbMasterCheck">
                                    </th>
                                    <th>Client / Unit / Project</th>
                                    <th>Contacts</th>
                                    <th class="text-end">Due Breakdown</th>
                                    <th>Next EMI</th>
                                </tr>
                            </thead>
                            <tbody id="cbClientsTbody">
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-spinner fa-spin me-1"></i>Loading clients...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Right: Message Composer -->
                <div class="cb-card cb-composer-sticky overflow-hidden">
                    <div class="cb-card-header">
                        <div class="cb-card-title">
                            <i class="fa-solid fa-envelope-open-text" style="color:var(--cb-brand);"></i>
                            <span>Message Composer</span>
                        </div>
                    </div>

                    <div class="cb-composer-body">
                        <!-- Quick Preset Template (full width so text never clips) -->
                        <div class="mb-2">
                            <label class="cb-filter-label" for="cbPresetSelect">Quick Message Template</label>
                            <select id="cbPresetSelect" class="form-select form-select-sm">
                                <option value="">-- Choose a starter template (optional) --</option>
                                <option value="upcoming_emi">Upcoming EMI Reminder</option>
                                <option value="overdue_due">Overdue Payment Reminder</option>
                                <option value="project_update">General Project Update</option>
                            </select>
                        </div>

                        <!-- Campaign Title -->
                        <div class="mb-2">
                            <label class="cb-filter-label" for="cbCampaignTitle">Campaign Title <span class="text-muted fw-normal">(Optional)</span></label>
                            <input type="text" id="cbCampaignTitle" class="form-control form-control-sm" placeholder="e.g. Nov EMI Reminder - Project X">
                        </div>

                        <!-- Channel Selector -->
                        <div class="mb-3">
                            <label class="cb-filter-label">Send Channel</label>
                            <div class="cb-segmented" role="group">
                                <button type="button" class="cb-channel-btn active" data-channel="sms">
                                    <i class="fa-solid fa-comment-sms me-1"></i>SMS Only
                                </button>
                                <button type="button" class="cb-channel-btn" data-channel="email">
                                    <i class="fa-solid fa-envelope me-1"></i>Email Only
                                </button>
                                <button type="button" class="cb-channel-btn" data-channel="both">
                                    <i class="fa-solid fa-paper-plane me-1"></i>Both
                                </button>
                            </div>
                        </div>

                        <!-- Dynamic Placeholders -->
                        <div class="mb-3">
                            <div class="cb-filter-label mb-1">Click tag to insert at cursor:</div>
                            <div id="cbPlaceholderChips">
                                <span class="cb-tag-chip" data-tag="{client_name}">{client_name}</span>
                                <span class="cb-tag-chip" data-tag="{project_name}">{project_name}</span>
                                <span class="cb-tag-chip" data-tag="{flat_name}">{flat_name}</span>
                                <span class="cb-tag-chip" data-tag="{total_due}">{total_due}</span>
                                <span class="cb-tag-chip" data-tag="{land_share_due}">{land_share_due}</span>
                                <span class="cb-tag-chip" data-tag="{dc_due}">{dc_due}</span>
                                <span class="cb-tag-chip" data-tag="{emi_due}">{emi_due}</span>
                                <span class="cb-tag-chip" data-tag="{next_emi_date}">{next_emi_date}</span>
                                <span class="cb-tag-chip" data-tag="{next_emi_amount}">{next_emi_amount}</span>
                            </div>
                        </div>

                        <!-- SMS Input -->
                        <div id="cbSmsSection" class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="cb-filter-label mb-0" for="cbSmsMessage">SMS Message</label>
                                <small id="cbSmsCounter" class="text-muted" style="font-size:11px;">0 chars | 0 SMS (ASCII)</small>
                            </div>
                            <textarea id="cbSmsMessage" rows="4" class="form-control form-control-sm"
                                      placeholder="Dear {client_name}, ... - TPHL"></textarea>
                        </div>

                        <!-- Email Input -->
                        <div id="cbEmailSection" class="mb-3 d-none">
                            <div class="mb-2">
                                <label class="cb-filter-label" for="cbEmailSubject">Email Subject</label>
                                <input type="text" id="cbEmailSubject" class="form-control form-control-sm"
                                       placeholder="e.g. Payment Reminder — {project_name} ({flat_name})">
                            </div>
                            <div>
                                <label class="cb-filter-label" for="cbEmailBody">Email Body <span class="text-muted fw-normal">(Branded TPHL header/footer auto-added)</span></label>
                                <textarea id="cbEmailBody" rows="6" class="form-control form-control-sm"
                                          placeholder="Dear {client_name},&#10;&#10;..."></textarea>
                            </div>
                        </div>

                        <!-- Options -->
                        <div class="cb-options-box mb-3">
                            <div class="form-check small mb-0">
                                <input class="form-check-input" type="checkbox" id="cbIncludeSecondary">
                                <label class="form-check-label text-dark" for="cbIncludeSecondary">
                                    Also include <strong>Alt Email &amp; 2nd Owner</strong> contacts (if available)
                                </label>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2">
                            <button type="button" id="cbBtnPreview" class="btn btn-sm btn-light border px-3">
                                <i class="fa-solid fa-eye me-1"></i>Preview
                            </button>
                            <button type="button" id="cbBtnSend" class="btn btn-sm cb-btn-brand flex-grow-1 fw-semibold" disabled>
                                <i class="fa-solid fa-paper-plane me-1"></i><span id="cbBtnSendLabel">Send SMS (0 selected)</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: SEND HISTORY & LOGS -->
        <div class="tab-pane fade" id="tab-history" role="tabpanel">
            <div class="cb-card overflow-hidden">
                <div class="cb-card-header">
                    <div class="cb-card-title">
                        <i class="fa-solid fa-list-check" style="color:var(--cb-brand);"></i>
                        <span>Recent Broadcast Campaigns (Last 50)</span>
                    </div>
                    <button type="button" id="cbBtnRefreshHistory" class="cb-btn-subtle">
                        <i class="fa-solid fa-rotate me-1"></i>Refresh
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0" style="font-size:12.5px;">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Campaign Title</th>
                                <th>Channel</th>
                                <th>Mode</th>
                                <th class="text-end">Clients</th>
                                <th class="text-center">SMS (Sent / Fail)</th>
                                <th class="text-center">Email (Sent / Fail)</th>
                                <th class="text-center">Skipped</th>
                                <th>Status</th>
                                <th>Sent By</th>
                                <th>Date</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="cbHistoryTbody">
                            <tr>
                                <td colspan="12" class="text-center py-4 text-muted">Click Refresh or open this tab to load history.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Message Preview Modal -->
<div class="modal fade" id="cbPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius:14px; overflow:hidden;">
            <div class="modal-header py-3 px-4 text-white border-0" style="background:linear-gradient(135deg, #0d5c3a, #15803d);">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-eye"></i>
                    <h6 class="modal-title mb-0 fw-bold">Message Preview</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Sample Client Switcher & Contact Info -->
                <div class="cb-prev-meta">
                    <div style="min-width:0;">
                        <div class="fw-bold text-dark" id="cbPrevClientName">—</div>
                        <div class="small text-muted" id="cbPrevClientSub">—</div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <label class="small text-muted mb-0 text-nowrap" for="cbPrevClientSelect">Preview as:</label>
                        <select id="cbPrevClientSelect" class="form-select form-select-sm" style="min-width:190px; max-width:240px;"></select>
                    </div>
                </div>

                <!-- Dynamic Preview Content -->
                <div id="cbPrevContent"></div>
            </div>
            <div class="modal-footer py-2 px-4 bg-light border-top">
                <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Close</button>
                <button type="button" id="cbPrevProceedSend" class="btn btn-sm cb-btn-brand px-3 fw-semibold">
                    <i class="fa-solid fa-paper-plane me-1"></i>Proceed to Send
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Broadcast Confirmation Modal -->
<div class="modal fade" id="cbConfirmSendModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:14px; overflow:hidden;">
            <div class="modal-header py-3 px-4 text-white border-0" id="cbConfirmHeader" style="background:linear-gradient(135deg, #0d5c3a, #15803d);">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-paper-plane" id="cbConfirmIcon"></i>
                    <h6 class="modal-title mb-0 fw-bold" id="cbConfirmTitle">Confirm Live Broadcast</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="cbConfirmStatsGrid" style="display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px;" class="mb-3"></div>
                <div id="cbConfirmNotice" class="rounded-3 p-3 small d-flex align-items-start gap-2"></div>
            </div>
            <div class="modal-footer py-2 px-4 bg-light border-top">
                <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="cbConfirmProceedBtn" class="btn btn-sm cb-btn-brand px-4 fw-semibold">
                    <i class="fa-solid fa-paper-plane me-1"></i>Yes, Send Now
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Live Dispatch Progress Modal -->
<div class="modal fade" id="cbProgressModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2 text-white" style="background:var(--cb-brand);">
                <h6 class="modal-title mb-0"><i class="fa-solid fa-paper-plane me-2"></i>Dispatching Broadcast</h6>
            </div>
            <div class="modal-body">
                <div class="mb-2 d-flex justify-content-between small fw-semibold">
                    <span id="cbProgLabel">Preparing jobs...</span>
                    <span id="cbProgPct">0%</span>
                </div>
                <div class="progress mb-3" style="height:18px;">
                    <div id="cbProgBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width:0%"></div>
                </div>
                <div style="display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:8px;" class="text-center small">
                    <div class="border rounded p-2">
                        <div class="text-muted" style="font-size:10px;">SMS SENT</div>
                        <div class="fw-bold text-success fs-6" id="cbProgSmsSent">0</div>
                    </div>
                    <div class="border rounded p-2">
                        <div class="text-muted" style="font-size:10px;">SMS FAIL</div>
                        <div class="fw-bold text-danger fs-6" id="cbProgSmsFail">0</div>
                    </div>
                    <div class="border rounded p-2">
                        <div class="text-muted" style="font-size:10px;">EMAIL SENT</div>
                        <div class="fw-bold text-success fs-6" id="cbProgEmailSent">0</div>
                    </div>
                    <div class="border rounded p-2">
                        <div class="text-muted" style="font-size:10px;">EMAIL FAIL</div>
                        <div class="fw-bold text-danger fs-6" id="cbProgEmailFail">0</div>
                    </div>
                </div>
                <div id="cbProgNote" class="mt-2 small text-muted text-center"></div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" id="cbProgCloseBtn" class="btn btn-sm cb-btn-brand d-none" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Campaign Details Modal -->
<div class="modal fade" id="cbDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius:14px; overflow:hidden;">
            <div class="modal-header py-3 px-4 text-white border-0" style="background:linear-gradient(135deg, #0d5c3a, #15803d);">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-list-check"></i>
                    <h6 class="modal-title mb-0 fw-bold" id="cbDetailsTitle">Campaign Delivery Logs</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="px-4 py-2 bg-light border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex flex-wrap gap-2" id="cbLogFilterPills">
                    <button type="button" class="cb-btn-subtle active" data-log-filter="all" id="cbLogPillAll">All (0)</button>
                    <button type="button" class="cb-btn-subtle" data-log-filter="sent" id="cbLogPillSent">Sent (0)</button>
                    <button type="button" class="cb-btn-subtle" data-log-filter="failed" id="cbLogPillFailed">Failed (0)</button>
                    <button type="button" class="cb-btn-subtle" data-log-filter="skipped_invalid" id="cbLogPillSkipped">Skipped / Manual (0)</button>
                </div>
                <button type="button" id="cbBtnExportSkippedCsv" class="cb-log-btn d-none" style="border-color:#bbf7d0; color:#0d5c3a; background:#f0fdf4;">
                    <i class="fa-solid fa-file-csv"></i>Export Skipped / Failed CSV
                </button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="cb-logs-table">
                        <thead>
                            <tr>
                                <th style="width:42px;">#</th>
                                <th>Client / Flat</th>
                                <th>Project</th>
                                <th>Channel</th>
                                <th>Recipient</th>
                                <th>Status</th>
                                <th>Message Preview</th>
                                <th>Gateway / Reason</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="cbDetailsTbody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const baseUrl = <?= json_encode($base_url) ?>;
    const projectAgentMap = <?= json_encode($projectAgentMap) ?>;
    let clientsData = [];
    let selectedIds = new Set();
    let activeChannel = 'sms';
    let lastFocusedInput = null;
    let debounceTimer = null;

    function syncAgentFromSelectedProjects() {
        const agentEl = document.getElementById('cbAgentId');
        if (!agentEl) return;
        const selectedProjects = getSelectedProjectIds();
        if (selectedProjects.length === 0) {
            agentEl.value = '';
            return;
        }
        const mappedAgents = new Set();
        selectedProjects.forEach(pId => {
            if (projectAgentMap[pId]) {
                mappedAgents.add(String(projectAgentMap[pId]));
            }
        });
        if (mappedAgents.size === 1 && selectedProjects.every(pId => !!projectAgentMap[pId])) {
            const singleAgentId = Array.from(mappedAgents)[0];
            if (agentEl.querySelector('option[value="' + singleAgentId + '"]')) {
                agentEl.value = singleAgentId;
                return;
            }
        }
        agentEl.value = '';
    }

    document.addEventListener('DOMContentLoaded', function() {
        if (window.jQuery && jQuery.fn.select2) {
            jQuery('#cbProjectIds').select2({
                width: '100%',
                placeholder: 'All Projects',
                allowClear: true
            }).on('change', function() {
                syncAgentFromSelectedProjects();
                loadClients();
            });
        } else {
            document.getElementById('cbProjectIds').addEventListener('change', function() {
                syncAgentFromSelectedProjects();
                loadClients();
            });
        }
        loadClients();
    });

    const smsEl   = document.getElementById('cbSmsMessage');
    const subjEl  = document.getElementById('cbEmailSubject');
    const bodyEl  = document.getElementById('cbEmailBody');

    [smsEl, subjEl, bodyEl].forEach(el => {
        if (!el) return;
        el.addEventListener('focus', () => { lastFocusedInput = el; });
    });

    function escHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    }

    function fmtMoney(v) {
        return '৳' + Math.round(Number(v || 0)).toLocaleString('en-US');
    }

    function fmtDate(d) {
        if (!d) return 'N/A';
        const dt = new Date(d + 'T00:00:00');
        if (isNaN(dt.getTime())) return d;
        return dt.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function updateSmsCounter() {
        const txt = smsEl.value || '';
        const len = txt.length;
        const isUnicode = /[^\x00-\x7F]/.test(txt);
        let parts = 0;
        if (len > 0) {
            parts = isUnicode
                ? (len <= 70 ? 1 : Math.ceil(len / 67))
                : (len <= 160 ? 1 : Math.ceil(len / 153));
        }
        document.getElementById('cbSmsCounter').textContent =
            `${len} chars | ${parts} SMS (${isUnicode ? 'Unicode/Bangla' : 'ASCII'})`;
    }
    smsEl.addEventListener('input', updateSmsCounter);

    function updateSendButtonLabel() {
        const count = selectedIds.size;
        const sendBtn = document.getElementById('cbBtnSend');
        const lbl = document.getElementById('cbBtnSendLabel');
        const channelLabel = activeChannel === 'sms' ? 'SMS' : (activeChannel === 'email' ? 'Email' : 'SMS & Email');
        sendBtn.disabled = (count === 0);
        lbl.textContent = count > 0
            ? `Send ${channelLabel} to ${count} Client${count > 1 ? 's' : ''}`
            : `Send ${channelLabel} (0 selected)`;
    }

    // Channel toggle
    document.querySelectorAll('.cb-channel-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.cb-channel-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            activeChannel = this.dataset.channel;

            document.getElementById('cbSmsSection').classList.toggle('d-none', activeChannel === 'email');
            document.getElementById('cbEmailSection').classList.toggle('d-none', activeChannel === 'sms');
            updateSendButtonLabel();
        });
    });

    // Placeholder insertion
    document.querySelectorAll('#cbPlaceholderChips .cb-tag-chip').forEach(chip => {
        chip.addEventListener('click', function() {
            const tag = this.dataset.tag;
            let target = lastFocusedInput;
            if (!target || target.closest('.d-none')) {
                target = (activeChannel === 'email') ? bodyEl : smsEl;
            }
            const start = target.selectionStart ?? target.value.length;
            const end   = target.selectionEnd ?? target.value.length;
            target.value = target.value.substring(0, start) + tag + target.value.substring(end);
            target.focus();
            target.selectionStart = target.selectionEnd = start + tag.length;
            if (target === smsEl) updateSmsCounter();
        });
    });

    // Quick preset templates
    const presets = {
        upcoming_emi: {
            sms: 'Dear {client_name}, your upcoming EMI of TK {next_emi_amount} for {project_name} (Unit: {flat_name}) is due on {next_emi_date}. Please pay on time. - TPHL',
            subject: 'Upcoming EMI Reminder — {project_name} ({flat_name})',
            body: 'Dear {client_name},\n\nThis is a gentle reminder that your upcoming EMI of BDT {next_emi_amount} for {project_name} (Unit: {flat_name}) is scheduled on {next_emi_date}.\n\nKindly arrange the payment on or before the due date to avoid any inconvenience.\n\nThank you for your continued trust in The Premium Homes Ltd.'
        },
        overdue_due: {
            sms: 'Dear {client_name}, you have a total due of TK {total_due} (EMI Due: TK {emi_due}) for {project_name} ({flat_name}). Please clear your dues at your earliest convenience. - TPHL',
            subject: 'Payment Due Reminder — {project_name} ({flat_name})',
            body: 'Dear {client_name},\n\nWe would like to bring to your kind attention that your account for {project_name} (Unit: {flat_name}) currently has an outstanding balance:\n\n- Land Share Due: BDT {land_share_due}\n- Development Charge Due: BDT {dc_due}\n- Regular EMI Due: BDT {emi_due}\n- Total Due: BDT {total_due}\n\nWe kindly request you to clear the outstanding amount at your earliest convenience. Please ignore this message if already paid.'
        },
        project_update: {
            sms: 'Dear {client_name}, important update regarding {project_name} (Unit: {flat_name}): [Write update here]. For details, contact CED. - TPHL',
            subject: 'Project Update — {project_name}',
            body: 'Dear {client_name},\n\nWe are pleased to share an update regarding your project {project_name} (Unit: {flat_name}):\n\n[Write your update details here]\n\nIf you have any queries, please feel free to contact our Client Experience Department.'
        }
    };

    document.getElementById('cbPresetSelect').addEventListener('change', function() {
        const p = presets[this.value];
        if (!p) return;
        smsEl.value = p.sms;
        subjEl.value = p.subject;
        bodyEl.value = p.body;
        updateSmsCounter();
    });

    function getSelectedProjectIds() {
        const sel = document.getElementById('cbProjectIds');
        return Array.from(sel.selectedOptions).map(o => o.value).filter(Boolean);
    }

    function collectFilters() {
        return {
            project_ids: getSelectedProjectIds().join(','),
            agent_id: document.getElementById('cbAgentId').value,
            due_filter: document.getElementById('cbDueFilter').value,
            min_due: document.getElementById('cbMinDue').value,
            upcoming_emi_days: document.getElementById('cbUpcomingEmi').value,
            exclude_advance_covered: document.getElementById('cbExcludeAdvance').checked ? '1' : '0',
            followup_filter: document.getElementById('cbFollowupFilter').value,
            booking_status: document.getElementById('cbBookingStatus').value,
            search: document.getElementById('cbSearch').value.trim()
        };
    }

    function updateSelectedCounters() {
        let selCount = 0, validSms = 0, validEmail = 0;
        clientsData.forEach(c => {
            if (selectedIds.has(c.booking_id)) {
                selCount++;
                if (c.phone_valid) validSms++;
                if (c.email_valid) validEmail++;
            }
        });
        document.getElementById('kpiSelectedClients').textContent = selCount;
        document.getElementById('kpiSelectedValidInfo').innerHTML =
            `Valid SMS: ${validSms} &middot; Valid Email: ${validEmail}`;

        const master = document.getElementById('cbMasterCheck');
        if (clientsData.length === 0) {
            master.checked = false;
            master.indeterminate = false;
        } else if (selCount === clientsData.length) {
            master.checked = true;
            master.indeterminate = false;
        } else if (selCount > 0) {
            master.checked = false;
            master.indeterminate = true;
        } else {
            master.checked = false;
            master.indeterminate = false;
        }
        updateSendButtonLabel();
    }

    function resetAllFilters() {
        if (window.jQuery && jQuery.fn.select2) {
            jQuery('#cbProjectIds').val(null).trigger('change.select2');
        } else {
            document.getElementById('cbProjectIds').selectedIndex = -1;
        }
        document.getElementById('cbAgentId').value = '';
        document.getElementById('cbDueFilter').value = '';
        document.getElementById('cbMinDue').value = '';
        document.getElementById('cbUpcomingEmi').value = '';
        document.getElementById('cbExcludeAdvance').checked = true;
        document.getElementById('cbFollowupFilter').value = '';
        document.getElementById('cbBookingStatus').value = '';
        document.getElementById('cbSearch').value = '';
        loadClients();
    }

    function renderClients() {
        const tbody = document.getElementById('cbClientsTbody');
        if (!clientsData.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5">
                        <div class="cb-empty-state">
                            <div class="cb-empty-icon"><i class="fa-solid fa-users-slash"></i></div>
                            <div class="fw-semibold text-dark mb-1">No active clients match the selected filters</div>
                            <div class="small text-muted mb-3">Try adjusting Due Filter, Upcoming EMI window, or clearing filters.</div>
                            <button type="button" class="btn btn-sm btn-light border" id="cbEmptyResetBtn">
                                <i class="fa-solid fa-rotate-left me-1"></i>Reset Filters
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            const er = document.getElementById('cbEmptyResetBtn');
            if (er) er.addEventListener('click', resetAllFilters);
            updateSelectedCounters();
            return;
        }

        let html = '';
        clientsData.forEach(c => {
            const checked = selectedIds.has(c.booking_id);
            const phoneBadge = c.phone_valid
                ? `<span style="color:var(--cb-brand);"><i class="fa-solid fa-circle-check me-1"></i>${escHtml(c.normalized_phone)}</span>`
                : `<span class="text-danger"><i class="fa-solid fa-circle-xmark me-1"></i>${c.phone ? escHtml(c.phone) + ' (Invalid)' : 'No Phone'}</span>`;
            const emailBadge = c.email_valid
                ? `<span class="text-primary"><i class="fa-solid fa-envelope-circle-check me-1"></i>${escHtml(c.email)}</span>`
                : `<span class="text-muted"><i class="fa-regular fa-envelope me-1"></i>${c.email ? escHtml(c.email) + ' (Invalid)' : 'No Email'}</span>`;

            const statusBadge = c.is_registered
                ? '<span class="badge" style="background:#e8f5e9; color:#0d5c3a; font-size:10px;">Registered</span>'
                : '<span class="badge bg-light text-secondary border" style="font-size:10px;">Booked</span>';

            const fuBadge = c.next_followup_date
                ? `<span class="badge bg-warning-subtle text-dark ms-1" style="font-size:10px;" title="Next Follow-up"><i class="fa-regular fa-calendar me-1"></i>${fmtDate(c.next_followup_date)}</span>`
                : '';

            const nextEmiHtml = c.next_emi_date
                ? `<div class="fw-semibold text-dark">${fmtDate(c.next_emi_date)}</div>
                   <div class="text-muted" style="font-size:11px;">EMI #${c.next_emi_no}: ${fmtMoney(c.next_emi_amount)}</div>
                   ${c.advance_covers_emi ? '<span class="badge bg-info-subtle text-info" style="font-size:9.5px;">Advance Covered</span>' : ''}`
                : '<span class="text-muted">—</span>';

            html += `
                <tr class="${checked ? 'is-selected' : ''}" data-booking-id="${c.booking_id}">
                    <td class="text-center">
                        <input type="checkbox" class="form-check-input cb-row-check" value="${c.booking_id}" ${checked ? 'checked' : ''}>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark">${escHtml(c.client_name)}</div>
                        <div class="text-muted" style="font-size:11px;">
                            <span style="color:var(--cb-brand); font-weight:600;">${escHtml(c.project_name)}</span> &middot; ${escHtml(c.flat_name)}
                        </div>
                        <div class="mt-1">${statusBadge}${fuBadge}</div>
                    </td>
                    <td>
                        <div class="cb-contact-line">${phoneBadge}</div>
                        <div class="cb-contact-line">${emailBadge}</div>
                    </td>
                    <td class="text-end">
                        <div class="fw-bold ${c.total_due > 0 ? 'text-danger' : 'text-success'}">${fmtMoney(c.total_due)}</div>
                        <div class="cb-due-mini text-muted">
                            LS: ${fmtMoney(c.land_share_due)} | DC: ${fmtMoney(c.dc_due)}<br>
                            EMI: <span class="${c.regular_emi_due > 0 ? 'text-danger fw-semibold' : ''}">${fmtMoney(c.regular_emi_due)}</span>
                        </div>
                    </td>
                    <td>${nextEmiHtml}</td>
                </tr>
            `;
        });

        tbody.innerHTML = html;

        // Toggle selection via checkbox OR clicking anywhere on the row
        tbody.querySelectorAll('tr[data-booking-id]').forEach(tr => {
            const chk = tr.querySelector('.cb-row-check');
            const id  = parseInt(tr.dataset.bookingId, 10);

            const applyToggle = (isChecked) => {
                if (isChecked) {
                    selectedIds.add(id);
                    tr.classList.add('is-selected');
                } else {
                    selectedIds.delete(id);
                    tr.classList.remove('is-selected');
                }
                updateSelectedCounters();
            };

            chk.addEventListener('change', function() {
                applyToggle(this.checked);
            });

            tr.addEventListener('click', function(e) {
                if (e.target.closest('input[type="checkbox"]') || e.target.closest('a') || e.target.closest('button')) {
                    return;
                }
                chk.checked = !chk.checked;
                applyToggle(chk.checked);
            });
        });

        updateSelectedCounters();
    }

    function loadClients() {
        const tbody = document.getElementById('cbClientsTbody');
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-muted"><i class="fa-solid fa-spinner fa-spin me-1"></i>Loading clients...</td></tr>';

        const params = new URLSearchParams(collectFilters());
        fetch(baseUrl + '/ced-client-broadcast/clients?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-danger">${escHtml(res.message || 'Failed to load clients.')}</td></tr>`;
                return;
            }
            clientsData = res.clients || [];
            selectedIds.clear();

            const s = res.summary || {};
            document.getElementById('kpiTotalClients').textContent = s.total || 0;
            document.getElementById('kpiValidPhone').textContent   = s.valid_phone || 0;
            document.getElementById('kpiValidEmail').textContent   = s.valid_email || 0;
            document.getElementById('kpiTotalDue').textContent     = fmtMoney(s.total_due_sum || 0);

            renderClients();
        })
        .catch(() => {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-danger">Network error while loading clients.</td></tr>';
        });
    }

    // Auto-filter on dropdown & checkbox changes
    ['cbAgentId', 'cbDueFilter', 'cbUpcomingEmi', 'cbExcludeAdvance', 'cbFollowupFilter', 'cbBookingStatus'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', loadClients);
    });

    // Debounced auto-filter on text/number inputs
    ['cbSearch', 'cbMinDue'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(loadClients, 350);
        });
        el.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                clearTimeout(debounceTimer);
                loadClients();
            }
        });
    });

    document.getElementById('cbBtnFilter').addEventListener('click', loadClients);
    document.getElementById('cbBtnReset').addEventListener('click', resetAllFilters);

    // Selection helpers
    document.getElementById('cbMasterCheck').addEventListener('change', function() {
        selectedIds.clear();
        if (this.checked) {
            clientsData.forEach(c => selectedIds.add(c.booking_id));
        }
        renderClients();
    });
    document.getElementById('cbSelectAllBtn').addEventListener('click', () => {
        selectedIds = new Set(clientsData.map(c => c.booking_id));
        renderClients();
    });
    document.getElementById('cbSelectValidSmsBtn').addEventListener('click', () => {
        selectedIds = new Set(clientsData.filter(c => c.phone_valid).map(c => c.booking_id));
        renderClients();
    });
    document.getElementById('cbSelectValidEmailBtn').addEventListener('click', () => {
        selectedIds = new Set(clientsData.filter(c => c.email_valid).map(c => c.booking_id));
        renderClients();
    });
    document.getElementById('cbClearSelectBtn').addEventListener('click', () => {
        selectedIds.clear();
        renderClients();
    });

    function renderSampleClient(template, c) {
        if (!c) return template;
        const map = {
            '{client_name}': c.client_name || 'Valued Client',
            '{project_name}': c.project_name || '',
            '{flat_name}': c.flat_name || '',
            '{total_due}': Math.round(Number(c.total_due || 0)).toLocaleString('en-US'),
            '{land_share_due}': Math.round(Number(c.land_share_due || 0)).toLocaleString('en-US'),
            '{dc_due}': Math.round(Number(c.dc_due || 0)).toLocaleString('en-US'),
            '{emi_due}': Math.round(Number(c.regular_emi_due || 0)).toLocaleString('en-US'),
            '{next_emi_date}': fmtDate(c.next_emi_date),
            '{next_emi_amount}': Math.round(Number(c.next_emi_amount || 0)).toLocaleString('en-US')
        };
        let out = template;
        Object.keys(map).forEach(k => {
            out = out.replace(new RegExp(k.replace(/[{}]/g, '\\$&'), 'gi'), map[k]);
        });
        return out;
    }

    // Preview modal rendering
    function renderPreviewForClient(sample) {
        if (!sample) return;
        document.getElementById('cbPrevClientName').textContent = sample.client_name || 'Valued Client';
        const contactBits = [
            `${sample.project_name || ''} (${sample.flat_name || ''})`,
            sample.phone_valid ? sample.normalized_phone : 'No valid phone',
            sample.email_valid ? sample.email : 'No valid email'
        ];
        document.getElementById('cbPrevClientSub').textContent = contactBits.join('  •  ');

        let html = '';

        if (activeChannel === 'sms' || activeChannel === 'both') {
            const renderedSms = renderSampleClient(smsEl.value.trim() || '(SMS message is empty)', sample);
            const len = renderedSms.length;
            const isUni = /[^\x00-\x7F]/.test(renderedSms);
            const parts = len > 0 ? (isUni ? (len <= 70 ? 1 : Math.ceil(len / 67)) : (len <= 160 ? 1 : Math.ceil(len / 153))) : 0;

            html += `
                <div class="cb-prev-section-title">
                    <span><i class="fa-solid fa-comment-sms me-1" style="color:var(--cb-brand);"></i>SMS Preview</span>
                    <span class="badge bg-light text-dark border fw-normal">${len} chars &middot; ${parts} SMS &middot; ${isUni ? 'Unicode' : 'ASCII'}</span>
                </div>
                <div class="cb-sms-phone-card">
                    <div class="cb-sms-phone-top">
                        <div class="d-flex align-items-center gap-2">
                            <span class="cb-sms-sender-avatar">T</span>
                            <div>
                                <div class="fw-bold text-dark" style="line-height:1.1;">TPHL</div>
                                <div class="text-muted" style="font-size:10.5px;">To: ${escHtml(sample.phone_valid ? sample.normalized_phone : 'No valid phone')}</div>
                            </div>
                        </div>
                        <span class="text-muted" style="font-size:11px;">Just now</span>
                    </div>
                    <div class="cb-sms-bubble">${escHtml(renderedSms)}</div>
                </div>
            `;
        }

        if (activeChannel === 'email' || activeChannel === 'both') {
            const renderedSubj = renderSampleClient(subjEl.value.trim() || '(No Subject)', sample);
            const renderedBody = renderSampleClient(bodyEl.value.trim() || '(Email body is empty)', sample);
            const year = new Date().getFullYear();

            html += `
                <div class="cb-prev-section-title">
                    <span><i class="fa-solid fa-envelope me-1" style="color:var(--cb-brand);"></i>Branded Email Preview</span>
                    <span class="badge bg-light text-dark border fw-normal">HTML Template</span>
                </div>
                <div class="cb-email-frame">
                    <div class="cb-email-meta-bar">
                        <div><strong>From:</strong> The Premium Homes Ltd</div>
                        <div><strong>To:</strong> ${escHtml(sample.email_valid ? sample.email : 'No valid email')}</div>
                        <div><strong>Subject:</strong> <span class="text-dark fw-semibold">${escHtml(renderedSubj)}</span></div>
                    </div>
                    <div class="cb-email-body-wrap">
                        <div class="cb-email-card">
                            <div class="cb-email-card-hdr">
                                <div style="font-size:10px; letter-spacing:1.1px; text-transform:uppercase; opacity:0.85; margin-bottom:3px;">The Premium Homes Ltd &middot; Client Experience</div>
                                <div style="font-size:16px; font-weight:700;">${escHtml(renderedSubj)}</div>
                            </div>
                            <div class="cb-email-card-body">${escHtml(renderedBody)}<div style="margin-top:20px; padding-top:12px; border-top:1px solid #e2e8f0; font-size:12.5px; color:#475569; white-space:normal;">Best Regards,<br><strong>Client Experience Department (CED)</strong><br>The Premium Homes Ltd</div></div>
                            <div class="cb-email-card-ftr">&copy; ${year} The Premium Homes Ltd. All rights reserved.</div>
                        </div>
                    </div>
                </div>
            `;
        }

        document.getElementById('cbPrevContent').innerHTML = html;
    }

    document.getElementById('cbBtnPreview').addEventListener('click', function() {
        const pool = selectedIds.size > 0
            ? clientsData.filter(c => selectedIds.has(c.booking_id))
            : clientsData;
        if (!pool.length) {
            Swal.fire({ title: 'No Client', text: 'Load and select at least one client to preview.', icon: 'info', confirmButtonColor: '#0d5c3a' });
            return;
        }

        const selEl = document.getElementById('cbPrevClientSelect');
        selEl.innerHTML = pool.slice(0, 50).map(c =>
            `<option value="${c.booking_id}">${escHtml(c.client_name)} (${escHtml(c.flat_name)})</option>`
        ).join('');

        renderPreviewForClient(pool[0]);

        const proceedBtn = document.getElementById('cbPrevProceedSend');
        proceedBtn.disabled = (selectedIds.size === 0);

        bootstrap.Modal.getOrCreateInstance(document.getElementById('cbPreviewModal')).show();
    });

    document.getElementById('cbPrevClientSelect').addEventListener('change', function() {
        const bId = parseInt(this.value, 10);
        const found = clientsData.find(c => c.booking_id === bId);
        if (found) renderPreviewForClient(found);
    });

    document.getElementById('cbPrevProceedSend').addEventListener('click', function() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('cbPreviewModal'));
        if (modal) modal.hide();
        setTimeout(() => document.getElementById('cbBtnSend').click(), 220);
    });

    // Run batch dispatch loop
    async function runBatchLoop(campaignId, totalJobs, isTesting) {
        const modalEl  = document.getElementById('cbProgressModal');
        const modal    = bootstrap.Modal.getOrCreateInstance(modalEl);
        const bar      = document.getElementById('cbProgBar');
        const pctEl    = document.getElementById('cbProgPct');
        const lblEl    = document.getElementById('cbProgLabel');
        const closeBtn = document.getElementById('cbProgCloseBtn');
        const noteEl   = document.getElementById('cbProgNote');

        bar.style.width = '0%';
        bar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-success';
        pctEl.textContent = '0%';
        lblEl.textContent = `Processing ${totalJobs} job(s)...`;
        closeBtn.classList.add('d-none');
        noteEl.textContent = isTesting ? 'Dry-Run / Testing Mode ON — no real SMS or Email will be sent.' : 'Please keep this tab open until dispatch finishes.';
        document.getElementById('cbProgSmsSent').textContent   = '0';
        document.getElementById('cbProgSmsFail').textContent   = '0';
        document.getElementById('cbProgEmailSent').textContent = '0';
        document.getElementById('cbProgEmailFail').textContent = '0';

        modal.show();

        let done = false;
        let safetyLoops = 0;
        while (!done && safetyLoops < 500) {
            safetyLoops++;
            const fd = new FormData();
            fd.append('campaign_id', campaignId);
            fd.append('batch_size', '8');

            try {
                const resp = await fetch(baseUrl + '/ced-client-broadcast/process-batch', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd
                });
                const res = await resp.json();
                if (!res.success) {
                    lblEl.textContent = 'Error: ' + (res.message || 'Batch failed');
                    bar.classList.remove('progress-bar-animated', 'bg-success');
                    bar.classList.add('bg-danger');
                    break;
                }

                const total = Math.max(1, res.total_jobs || totalJobs);
                const finished = total - (res.remaining || 0);
                const pct = Math.min(100, Math.round((finished / total) * 100));

                bar.style.width = pct + '%';
                pctEl.textContent = pct + '%';
                lblEl.textContent = res.done
                    ? `Completed (${finished} / ${total})`
                    : `Sending ${finished} / ${total}...`;

                document.getElementById('cbProgSmsSent').textContent   = res.sms_sent || 0;
                document.getElementById('cbProgSmsFail').textContent   = res.sms_failed || 0;
                document.getElementById('cbProgEmailSent').textContent = res.email_sent || 0;
                document.getElementById('cbProgEmailFail').textContent = res.email_failed || 0;

                if (res.done || res.processed_in_batch === 0) {
                    done = true;
                    bar.classList.remove('progress-bar-animated');
                    if (res.status === 'failed') {
                        bar.classList.remove('bg-success');
                        bar.classList.add('bg-danger');
                    } else if (res.status === 'partial_failed') {
                        bar.classList.remove('bg-success');
                        bar.classList.add('bg-warning');
                    }
                }
            } catch (err) {
                lblEl.textContent = 'Network interruption during dispatch.';
                bar.classList.remove('progress-bar-animated', 'bg-success');
                bar.classList.add('bg-danger');
                break;
            }
        }

        closeBtn.classList.remove('d-none');
        loadHistory();
    }

    function showBroadcastConfirmModal(opts) {
        return new Promise(resolve => {
            const modalEl   = document.getElementById('cbConfirmSendModal');
            const headerEl  = document.getElementById('cbConfirmHeader');
            const titleEl   = document.getElementById('cbConfirmTitle');
            const iconEl    = document.getElementById('cbConfirmIcon');
            const statsEl   = document.getElementById('cbConfirmStatsGrid');
            const noticeEl  = document.getElementById('cbConfirmNotice');
            const proceedEl = document.getElementById('cbConfirmProceedBtn');

            if (opts.isTesting) {
                headerEl.style.background = 'linear-gradient(135deg, #b45309, #d97706)';
                titleEl.textContent = 'Confirm Dry-Run Broadcast';
                iconEl.className = 'fa-solid fa-flask';
                proceedEl.innerHTML = '<i class="fa-solid fa-flask me-1"></i>Start Dry-Run';
                noticeEl.style.background = '#fffbeb';
                noticeEl.style.border = '1px solid #fde68a';
                noticeEl.style.color = '#92400e';
                noticeEl.innerHTML = '<i class="fa-solid fa-circle-info mt-1"></i><div><strong>Testing Mode is ON</strong> — Logs will be recorded as <code>skipped_testing</code> without sending any real SMS or Email.</div>';
            } else {
                headerEl.style.background = 'linear-gradient(135deg, #0d5c3a, #15803d)';
                titleEl.textContent = 'Confirm Live Broadcast';
                iconEl.className = 'fa-solid fa-paper-plane';
                proceedEl.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i>Yes, Send Now';
                noticeEl.style.background = '#fef2f2';
                noticeEl.style.border = '1px solid #fecaca';
                noticeEl.style.color = '#991b1b';
                noticeEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation mt-1"></i><div><strong>Live Mode</strong> — Real SMS / Email will be dispatched to the selected clients immediately.</div>';
            }

            const cards = [
                { label: 'Channel', val: opts.channel.toUpperCase(), color: '#0d5c3a' },
                { label: 'Selected Clients', val: opts.selectedCount, color: '#0f172a' },
            ];
            if (opts.needSms) {
                cards.push({ label: 'Valid SMS', val: opts.validSms, color: '#15803d' });
            }
            if (opts.needEmail) {
                cards.push({ label: 'Valid Email', val: opts.validEmail, color: '#1d4ed8' });
            }
            if (opts.skippedEst > 0) {
                cards.push({ label: 'Skipped (Invalid)', val: opts.skippedEst, color: '#b45309' });
            }

            statsEl.style.gridTemplateColumns = `repeat(${Math.min(cards.length, 4)}, minmax(0, 1fr))`;
            statsEl.innerHTML = cards.map(c => `
                <div class="border rounded-3 p-2 text-center bg-light">
                    <div class="text-muted text-uppercase fw-semibold" style="font-size:10px; letter-spacing:0.4px;">${escHtml(c.label)}</div>
                    <div class="fw-bold fs-6 mt-1" style="color:${c.color};">${escHtml(c.val)}</div>
                </div>
            `).join('');

            let resolved = false;
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

            const onConfirm = () => {
                if (resolved) return;
                resolved = true;
                cleanup();
                modal.hide();
                resolve(true);
            };
            const onHide = () => {
                if (resolved) return;
                resolved = true;
                cleanup();
                resolve(false);
            };
            const cleanup = () => {
                proceedEl.removeEventListener('click', onConfirm);
                modalEl.removeEventListener('hidden.bs.modal', onHide);
            };

            proceedEl.addEventListener('click', onConfirm);
            modalEl.addEventListener('hidden.bs.modal', onHide);
            modal.show();
        });
    }

    // Send Broadcast button
    document.getElementById('cbBtnSend').addEventListener('click', async function() {
        if (selectedIds.size === 0) {
            Swal.fire({ title: 'No Clients Selected', text: 'Please select at least one client from the list.', icon: 'warning', confirmButtonColor: '#0d5c3a' });
            return;
        }

        const needSms   = (activeChannel === 'sms' || activeChannel === 'both');
        const needEmail = (activeChannel === 'email' || activeChannel === 'both');

        if (needSms && !smsEl.value.trim()) {
            Swal.fire({ title: 'SMS Required', text: 'Please write your SMS message.', icon: 'warning', confirmButtonColor: '#0d5c3a' });
            smsEl.focus();
            return;
        }
        if (needEmail && (!subjEl.value.trim() || !bodyEl.value.trim())) {
            Swal.fire({ title: 'Email Required', text: 'Please provide both Email Subject and Body.', icon: 'warning', confirmButtonColor: '#0d5c3a' });
            return;
        }

        const isTesting = false;
        let validSms = 0, validEmail = 0, skippedEst = 0;
        clientsData.forEach(c => {
            if (selectedIds.has(c.booking_id)) {
                if (c.phone_valid) validSms++;
                else if (needSms) skippedEst++;
                if (c.email_valid) validEmail++;
                else if (needEmail) skippedEst++;
            }
        });

        const confirmed = await showBroadcastConfirmModal({
            channel: activeChannel,
            selectedCount: selectedIds.size,
            needSms,
            needEmail,
            validSms,
            validEmail,
            skippedEst,
            isTesting
        });

        if (!confirmed) return;

        const btn = this;
        btn.disabled = true;

        try {
            const fd = new FormData();
            fd.append('campaign_title', document.getElementById('cbCampaignTitle').value.trim());
            fd.append('channel', activeChannel);
            fd.append('booking_ids', Array.from(selectedIds).join(','));
            fd.append('sms_message', smsEl.value.trim());
            fd.append('email_subject', subjEl.value.trim());
            fd.append('email_body', bodyEl.value.trim());
            fd.append('include_secondary_contacts', document.getElementById('cbIncludeSecondary').checked ? '1' : '0');
            fd.append('is_testing', isTesting ? '1' : '0');
            fd.append('filter_summary', JSON.stringify(collectFilters()));

            const resp = await fetch(baseUrl + '/ced-client-broadcast/create', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            });
            const res = await resp.json();

            if (!res.success) {
                Swal.fire('Cannot Create Broadcast', res.message || 'Validation failed.', 'error');
                return;
            }

            if (res.total_jobs > 0) {
                await runBatchLoop(res.campaign_id, res.total_jobs, res.is_testing);
                if (res.skipped_invalid > 0) {
                    document.getElementById('cbProgNote').innerHTML =
                        `<span class="text-warning fw-semibold">${res.skipped_invalid} recipient(s) skipped (foreign/invalid/missing contact).</span> Open <strong>Send History → Logs</strong> for manual SMS/WhatsApp &amp; CSV export.`;
                }
            } else {
                loadHistory();
                Swal.fire({
                    title: 'All Selected Contacts Skipped',
                    icon: 'warning',
                    html: `${res.skipped_invalid} recipient(s) had foreign/invalid/missing contacts and were logged as <strong>Skipped / Manual</strong> in Campaign #${res.campaign_id}.`,
                    confirmButtonText: 'View Skipped List',
                    confirmButtonColor: '#0d5c3a'
                }).then(() => openCampaignDetails(res.campaign_id, 'skipped_invalid'));
            }
        } catch (err) {
            Swal.fire('Error', 'Network error while creating broadcast campaign.', 'error');
        } finally {
            updateSendButtonLabel();
        }
    });

    // History tab loading
    function statusBadgeHtml(st) {
        const map = {
            completed: 'bg-success',
            partial_failed: 'bg-warning text-dark',
            failed: 'bg-danger',
            processing: 'bg-primary',
            pending: 'bg-secondary',
            sent: 'bg-success',
            manual_sent: 'bg-teal text-white',
            skipped_testing: 'bg-info text-dark',
            skipped_invalid: 'bg-warning text-dark'
        };
        const label = st === 'skipped_invalid'
            ? 'Skipped (Manual)'
            : (st === 'manual_sent' ? 'Manual Sent' : st);
        const extraStyle = st === 'manual_sent' ? ' style="background:#0f766e;"' : '';
        return `<span class="badge ${map[st] || 'bg-secondary'}"${extraStyle}>${escHtml(label)}</span>`;
    }

    function loadHistory() {
        const tbody = document.getElementById('cbHistoryTbody');
        tbody.innerHTML = '<tr><td colspan="12" class="text-center py-4 text-muted"><i class="fa-solid fa-spinner fa-spin me-1"></i>Loading history...</td></tr>';

        fetch(baseUrl + '/ced-client-broadcast/history', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(res => {
            if (!res.success || !res.campaigns || !res.campaigns.length) {
                tbody.innerHTML = '<tr><td colspan="12" class="text-center py-4 text-muted">No broadcast campaigns found yet.</td></tr>';
                return;
            }

            let html = '';
            res.campaigns.forEach(c => {
                const totalFail = (c.sms_failed_count || 0) + (c.email_failed_count || 0);
                const skipped = c.skipped_count || 0;
                const skippedHtml = skipped > 0
                    ? `<button type="button" class="badge bg-warning text-dark border-0 cb-view-skipped" data-id="${c.id}" style="cursor:pointer;" title="View skipped clients for manual follow-up">${skipped} Manual</button>`
                    : '<span class="text-muted">0</span>';
                html += `
                    <tr>
                        <td>#${c.id}</td>
                        <td class="fw-semibold">${escHtml(c.campaign_title || 'Untitled')}</td>
                        <td><span class="badge bg-light text-dark border text-uppercase">${escHtml(c.channel)}</span></td>
                        <td>${c.is_testing ? '<span class="badge bg-warning text-dark">Testing</span>' : '<span class="badge" style="background:#e8f5e9;color:#0d5c3a;">Live</span>'}</td>
                        <td class="text-end">${c.total_clients}</td>
                        <td class="text-center"><span class="text-success fw-semibold">${c.sms_sent_count}</span> / <span class="text-danger">${c.sms_failed_count}</span></td>
                        <td class="text-center"><span class="text-success fw-semibold">${c.email_sent_count}</span> / <span class="text-danger">${c.email_failed_count}</span></td>
                        <td class="text-center">${skippedHtml}</td>
                        <td>${statusBadgeHtml(c.status)}</td>
                        <td>${escHtml(c.creator_name)}</td>
                        <td class="text-nowrap">${escHtml(c.created_at)}</td>
                        <td class="text-end text-nowrap">
                            <button type="button" class="cb-btn-subtle cb-view-details" data-id="${c.id}">Logs</button>
                            ${totalFail > 0 ? `<button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 ms-1 cb-retry-failed" data-id="${c.id}" data-testing="${c.is_testing ? '1' : '0'}">Retry Failed</button>` : ''}
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;

            tbody.querySelectorAll('.cb-view-details').forEach(btn => {
                btn.addEventListener('click', () => openCampaignDetails(btn.dataset.id, 'all'));
            });
            tbody.querySelectorAll('.cb-view-skipped').forEach(btn => {
                btn.addEventListener('click', () => openCampaignDetails(btn.dataset.id, 'skipped_invalid'));
            });
            tbody.querySelectorAll('.cb-retry-failed').forEach(btn => {
                btn.addEventListener('click', () => retryFailedCampaign(btn.dataset.id, btn.dataset.testing === '1'));
            });
        })
        .catch(() => {
            tbody.innerHTML = '<tr><td colspan="12" class="text-center py-4 text-danger">Failed to load history.</td></tr>';
        });
    }

    let currentDetailLogs = [];
    let currentDetailCampaign = null;
    let currentLogFilter = 'all';

    function formatGatewayResponse(raw) {
        const s = String(raw || '').trim();
        if (!s) return '—';
        if (s.startsWith('[') || s.startsWith('{')) {
            try {
                const parsed = JSON.parse(s);
                const item = Array.isArray(parsed) ? (parsed[0] || {}) : parsed;
                const msgId = item.id || item.message_id || '';
                const st = String(item.status ?? '').toLowerCase();
                if (msgId || st === '0' || st === 'success' || st === 'ok' || st === 'queued' || st === 'submitted') {
                    return msgId ? `Accepted (ID: ${msgId})` : 'Accepted by Gateway';
                }
                if (item.message || item.error) {
                    return String(item.message || item.error);
                }
            } catch (e) {}
        }
        return s;
    }

    function updateDetailLogPills() {
        const sentCnt = currentDetailLogs.filter(l => l.status === 'sent' || l.status === 'skipped_testing' || l.status === 'manual_sent').length;
        const failCnt = currentDetailLogs.filter(l => l.status === 'failed').length;
        const skipCnt = currentDetailLogs.filter(l => l.status === 'skipped_invalid').length;

        document.getElementById('cbLogPillAll').textContent     = `All (${currentDetailLogs.length})`;
        document.getElementById('cbLogPillSent').textContent    = `Sent (${sentCnt})`;
        document.getElementById('cbLogPillFailed').textContent  = `Failed (${failCnt})`;
        document.getElementById('cbLogPillSkipped').textContent = `Skipped / Manual (${skipCnt})`;

        document.getElementById('cbBtnExportSkippedCsv').classList.toggle('d-none', (skipCnt + failCnt) === 0);
    }

    function renderDetailLogsTable() {
        const tbody = document.getElementById('cbDetailsTbody');
        const filtered = currentDetailLogs.filter(l => {
            if (currentLogFilter === 'all') return true;
            if (currentLogFilter === 'sent') return l.status === 'sent' || l.status === 'skipped_testing' || l.status === 'manual_sent';
            return l.status === currentLogFilter;
        });

        if (!filtered.length) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted">No logs match this filter.</td></tr>';
            return;
        }

        let html = '';
        filtered.forEach((l, i) => {
            const fullMsg = l.rendered_message || '';
            const preview = fullMsg.substring(0, 95) + (fullMsg.length > 95 ? '...' : '');
            const cleanDigits = String(l.recipient_contact || '').replace(/[^\d]/g, '');
            const waUrl = (l.channel === 'sms' && cleanDigits.length >= 7)
                ? `https://wa.me/${cleanDigits}?text=${encodeURIComponent(fullMsg)}`
                : '';
            const gwRaw = l.gateway_response || '';
            const gwNice = formatGatewayResponse(gwRaw);
            const canMarkManual = (l.status === 'skipped_invalid' || l.status === 'failed');

            html += `
                <tr>
                    <td class="text-muted">${i + 1}</td>
                    <td>
                        <div class="fw-semibold text-dark">${escHtml(l.client_name)}</div>
                        <div class="text-muted" style="font-size:10.5px;">${escHtml(l.flat_name)}</div>
                    </td>
                    <td class="text-nowrap">${escHtml(l.project_name || '')}</td>
                    <td><span class="badge bg-light text-dark border text-uppercase" style="font-size:10px;">${escHtml(l.channel)}</span></td>
                    <td class="text-nowrap fw-medium">${escHtml(l.recipient_contact)}</td>
                    <td>
                        ${statusBadgeHtml(l.status)}
                        ${l.sent_at ? `<div class="text-muted mt-1" style="font-size:10px;">${escHtml(l.sent_at)}</div>` : ''}
                    </td>
                    <td class="cb-log-msg-cell" title="${escHtml(fullMsg)}">${escHtml(preview)}</td>
                    <td class="cb-log-gw-cell" title="${escHtml(gwRaw)}">${escHtml(gwNice)}</td>
                    <td class="text-end">
                        <div class="cb-log-actions">
                            <button type="button" class="cb-log-btn cb-copy-log-msg" data-msg="${escHtml(fullMsg)}" title="Copy full personalized message">
                                <i class="fa-regular fa-copy"></i> Copy
                            </button>
                            ${waUrl ? `<a href="${waUrl}" target="_blank" rel="noopener" class="cb-log-btn wa" title="Open WhatsApp with message"><i class="fa-brands fa-whatsapp"></i></a>` : ''}
                            ${canMarkManual ? `<button type="button" class="cb-log-btn done cb-mark-manual-btn" data-log-id="${l.id}" data-channel="${escHtml(l.channel)}" title="Mark as manually contacted"><i class="fa-solid fa-check"></i> Mark Done</button>` : ''}
                        </div>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;

        tbody.querySelectorAll('.cb-copy-log-msg').forEach(btn => {
            btn.addEventListener('click', function() {
                const txt = this.dataset.msg || '';
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(txt);
                }
                const orig = this.innerHTML;
                this.innerHTML = '<i class="fa-solid fa-check text-success"></i> Copied';
                setTimeout(() => { this.innerHTML = orig; }, 1400);
            });
        });

        tbody.querySelectorAll('.cb-mark-manual-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const logId   = this.dataset.logId;
                const channel = this.dataset.channel || 'sms';
                if (!logId) return;

                const origHtml = this.innerHTML;
                this.disabled = true;
                this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

                try {
                    const fd = new FormData();
                    fd.append('log_id', logId);
                    fd.append('method', channel === 'email' ? 'Manual Email' : 'Manual SMS');

                    const resp = await fetch(baseUrl + '/ced-client-broadcast/mark-manual', {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: fd
                    });
                    const res = await resp.json();
                    if (!res.success) {
                        this.disabled = false;
                        this.innerHTML = origHtml;
                        Swal.fire({ title: 'Error', text: res.message || 'Failed to update status.', icon: 'error', confirmButtonColor: '#0d5c3a' });
                        return;
                    }

                    const target = currentDetailLogs.find(x => String(x.id) === String(logId));
                    if (target) {
                        target.status = res.status;
                        target.gateway_response = res.gateway_response;
                        target.sent_at = res.sent_at;
                    }

                    updateDetailLogPills();
                    renderDetailLogsTable();
                    loadHistory();
                } catch (err) {
                    this.disabled = false;
                    this.innerHTML = origHtml;
                    Swal.fire({ title: 'Error', text: 'Network error while updating status.', icon: 'error', confirmButtonColor: '#0d5c3a' });
                }
            });
        });
    }

    document.querySelectorAll('#cbLogFilterPills button').forEach(btn => {
        btn.addEventListener('click', function() {
            currentLogFilter = this.dataset.logFilter;
            document.querySelectorAll('#cbLogFilterPills button').forEach(b => {
                b.style.background = '';
                b.style.color = '';
                b.style.borderColor = '';
            });
            this.style.background = 'var(--cb-brand)';
            this.style.color = '#fff';
            this.style.borderColor = 'var(--cb-brand)';
            renderDetailLogsTable();
        });
    });

    document.getElementById('cbBtnExportSkippedCsv').addEventListener('click', function() {
        const rows = currentDetailLogs.filter(l => l.status === 'skipped_invalid' || l.status === 'failed');
        if (!rows.length) return;
        const csvEsc = v => '"' + String(v ?? '').replace(/"/g, '""') + '"';
        const lines = [['Client Name', 'Flat', 'Project', 'Channel', 'Raw Contact', 'Status', 'Reason', 'Personalized Message'].map(csvEsc).join(',')];
        rows.forEach(l => {
            lines.push([
                l.client_name, l.flat_name, l.project_name || '', l.channel,
                l.recipient_contact, l.status, l.gateway_response || '', l.rendered_message || ''
            ].map(csvEsc).join(','));
        });
        const blob = new Blob(['\uFEFF' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `campaign_${currentDetailCampaign ? currentDetailCampaign.id : 'logs'}_manual_followup.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });

    function openCampaignDetails(campaignId, initialFilter = 'all') {
        const tbody = document.getElementById('cbDetailsTbody');
        tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted"><i class="fa-solid fa-spinner fa-spin me-1"></i>Loading logs...</td></tr>';
        document.getElementById('cbDetailsTitle').textContent = `Campaign #${campaignId} — Delivery Logs`;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('cbDetailsModal')).show();

        fetch(baseUrl + '/ced-client-broadcast/details?campaign_id=' + encodeURIComponent(campaignId), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(res => {
            if (!res.success || !res.logs) {
                tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-danger">Failed to load logs.</td></tr>';
                return;
            }
            currentDetailCampaign = res.campaign || null;
            currentDetailLogs = res.logs || [];
            if (currentDetailCampaign) {
                document.getElementById('cbDetailsTitle').textContent = `#${currentDetailCampaign.id} — ${currentDetailCampaign.campaign_title || 'Delivery Logs'}`;
            }

            updateDetailLogPills();

            const targetBtn = document.querySelector(`#cbLogFilterPills button[data-log-filter="${initialFilter}"]`)
                || document.getElementById('cbLogPillAll');
            targetBtn.click();
        });
    }

    async function retryFailedCampaign(campaignId, isTesting) {
        const ok = await Swal.fire({
            title: 'Retry Failed Recipients?',
            text: `Re-dispatch all failed SMS/Email jobs in Campaign #${campaignId}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Retry',
            confirmButtonColor: '#0d5c3a'
        });
        if (!ok.isConfirmed) return;

        const fd = new FormData();
        fd.append('campaign_id', campaignId);
        const resp = await fetch(baseUrl + '/ced-client-broadcast/retry-failed', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        });
        const res = await resp.json();
        if (!res.success) {
            Swal.fire('Retry Failed', res.message || 'No failed items found.', 'error');
            return;
        }
        await runBatchLoop(campaignId, res.retry_count, isTesting);
    }

    document.getElementById('tab-history-btn').addEventListener('shown.bs.tab', loadHistory);
    document.getElementById('cbBtnRefreshHistory').addEventListener('click', loadHistory);
})();
</script>
