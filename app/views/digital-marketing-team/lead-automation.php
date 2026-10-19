<?php
/**
 * Lead Intake & Automation Distribution Dashboard
 * TPHL ERP - Digital Marketing Role 10 & 11
 */

$userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
$secondaryRoleId = (int)($_SESSION['user']['secondary_role_id'] ?? 0);
$primaryRoleId = (int)($_SESSION['user']['primary_role_id'] ?? 0);
$userId = (int)($_SESSION['user']['id'] ?? 0);
$userRoleName = strtolower(trim((string)($_SESSION['user']['role_name'] ?? '')));

// Check if user belongs to Management (Admin, Super Admin, Director, Chief Officer, Management Viewer, SEAL CEO)
$isManagementUser = in_array($userRoleId, [1, 3, 33, 34, 35], true)
    || in_array($secondaryRoleId, [1, 3, 33, 34, 35], true)
    || in_array($primaryRoleId, [1, 3, 33, 34, 35], true)
    || $userId === 94
    || strpos($userRoleName, 'admin') !== false
    || strpos($userRoleName, 'management') !== false
    || strpos($userRoleName, 'director') !== false
    || strpos($userRoleName, 'chief') !== false;

// Role 10: Head Of Digital Marketing Team
$isHeadOfDM = ($userRoleId === 10 || $secondaryRoleId === 10 || $primaryRoleId === 10 || strpos($userRoleName, 'head of digital marketing') !== false);

// Only Management and Head Of Digital Marketing Team (Role 10) can view live sheet
$canViewLiveSheet = ($isManagementUser || $isHeadOfDM);
?>
<style>
    :root {
        --bg-page: #f8fafc;
        --bg-card: #ffffff;
        --border: #e2e8f0;
        --border-focus: #087d59;
        --text-heading: #0f172a;
        --text-body: #334155;
        --text-muted: #64748b;
        --text-subtle: #94a3b8;
        --brand-green: #087d59;
        --brand-green-hover: #066346;
        --brand-green-light: #eaf6f0;
        --border-green: #d1eae0;
        --radius-sm: 6px;
        --radius-md: 10px;
        --radius-full: 9999px;
    }

    .lead-auto-container {
        padding: 1.25rem 0.5rem 3rem;
        font-family: inherit;
    }

    /* Page Header */
    .lead-auto-header {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 1.25rem 1.5rem;
        margin-bottom: 1rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }

    .lead-source-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 10px 16px;
        margin-bottom: 1rem;
        flex-wrap: wrap;
        gap: 10px;
    }

    .lead-source-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        color: var(--text-heading);
    }

    .badge-sync-live {
        background: #eaf6f0;
        color: #087d59;
        border: 1px solid #d1eae0;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: var(--radius-full);
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .sync-pulse-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #087d59;
        animation: pulse 1.6s infinite;
    }

    @keyframes pulse {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(8, 125, 89, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(8, 125, 89, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(8, 125, 89, 0); }
    }

    /* Metric Cards */
    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 1rem;
    }

    @media (max-width: 992px) {
        .metrics-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 576px) {
        .metrics-grid { grid-template-columns: 1fr; }
    }

    .metric-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 14px 16px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    }

    .metric-title {
        font-size: 11px;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 4px;
    }

    .metric-value {
        font-size: 26px;
        font-weight: 800;
        line-height: 1.1;
        margin-bottom: 4px;
    }

    .metric-caption {
        font-size: 11px;
        color: var(--text-muted);
    }

    .val-dark { color: #0f172a; }
    .val-green { color: #087d59; }
    .val-amber { color: #d97706; }
    .val-purple { color: #7c3aed; }

    /* Middle Row */
    /* Middle Row */
    .allocation-section {
        display: grid;
        grid-template-columns: 1fr 240px;
        gap: 12px;
        margin-bottom: 1rem;
    }

    @media (max-width: 1350px) {
        .allocation-section { 
            grid-template-columns: 1fr; 
            gap: 12px;
        }
        .lead-action-box {
            flex-direction: row !important;
            align-items: center !important;
            justify-content: space-between !important;
            padding: 12px 18px !important;
            gap: 16px !important;
        }
        .lead-action-box > div:first-child {
            max-width: 60%;
        }
        .lead-action-box > div:last-child {
            min-width: 240px;
        }
    }

    @media (max-width: 768px) {
        .lead-action-box {
            flex-direction: column !important;
            align-items: stretch !important;
        }
        .lead-action-box > div:first-child {
            max-width: 100% !important;
        }
        .lead-action-box > div:last-child {
            min-width: auto !important;
        }
    }

    .team-allocation-box {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 12px 14px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }

    .teams-horizontal-scroll {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        row-gap: 10px;
        column-gap: 8px;
        max-height: none;
        overflow: visible;
        padding-right: 0;
    }

    @media (max-width: 992px) {
        .teams-horizontal-scroll {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            row-gap: 9px;
            column-gap: 8px;
        }
    }
    @media (max-width: 768px) {
        .teams-horizontal-scroll {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            row-gap: 8px;
            column-gap: 7px;
        }
    }
    @media (max-width: 576px) {
        .teams-horizontal-scroll {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            row-gap: 8px;
            column-gap: 6px;
        }
    }

    .team-metric-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 9px;
        padding: 8px 10px;
        cursor: pointer;
        transition: all 0.18s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 70px;
        position: relative;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }
    .team-metric-card:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.06);
        transform: translateY(-1px);
    }

    /* Unassigned card: Soft warning amber background */
    .team-metric-card.team-card-unassigned {
        background: #fffdf5;
        border: 1px solid #fde68a;
        border-left: 4px solid #f59e0b;
        box-shadow: 0 1px 3px rgba(245, 158, 11, 0.08);
    }
    .team-metric-card.team-card-unassigned:hover {
        background: #fef9c3;
        border-color: #f59e0b;
        box-shadow: 0 3px 8px rgba(245, 158, 11, 0.16);
        transform: translateY(-1px);
    }
    .team-metric-card.team-card-unassigned .team-card-name {
        color: #b45309;
    }

    /* Active filter selected state */
    .team-metric-card.active {
        background: #ffffff !important;
        border: 1.5px solid #059669 !important;
        box-shadow: 0 0 0 2px rgba(5, 150, 105, 0.18), 0 3px 8px rgba(5, 150, 105, 0.15) !important;
        transform: translateY(-1px);
    }

    .team-card-name {
        font-size: 11.5px;
        font-weight: 700;
        color: #0f172a;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.2;
        margin-bottom: 3px;
        letter-spacing: -0.01em;
    }

    .team-card-assigned {
        font-size: 15px;
        font-weight: 800;
        color: #059669;
        line-height: 1.15;
        letter-spacing: -0.01em;
    }

    .team-card-sub {
        font-size: 10.5px;
        font-weight: 600;
        color: #ea580c;
        line-height: 1.15;
        margin-top: 2px;
    }

    @media (max-width: 768px) {
        .team-metric-card {
            padding: 7px 8px;
            min-height: 64px;
        }
        .team-card-name {
            font-size: 10.5px;
        }
        .team-card-assigned {
            font-size: 14px;
        }
        .team-card-sub {
            font-size: 10px;
        }
    }

    @media (max-width: 576px) {
        .team-metric-card {
            padding: 6px 7px;
            min-height: 58px;
        }
        .team-card-name {
            font-size: 10px;
        }
        .team-card-assigned {
            font-size: 13px;
        }
        .team-card-sub {
            font-size: 9px;
        }
    }

    .lead-action-box {
        background: #f4faf7;
        border: 1px solid var(--border-green);
        border-radius: var(--radius-md);
        padding: 14px 16px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .btn-action-primary {
        background: #087d59;
        color: #ffffff;
        border: none;
        padding: 9px 12px;
        border-radius: var(--radius-sm);
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        width: 100%;
        transition: background 0.15s ease;
    }

    .btn-action-primary:hover {
        background: #066346;
        color: #fff;
    }

    /* Queue Table Container */
    .queue-container {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        overflow: visible;
    }

    .queue-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 18px;
        border-bottom: 1px solid var(--border);
        flex-wrap: wrap;
        gap: 8px;
    }

    .queue-tabs {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .tab-btn {
        background: transparent;
        border: 1px solid transparent;
        padding: 5px 12px;
        border-radius: var(--radius-full);
        font-size: 12px;
        font-weight: 600;
        color: var(--text-muted);
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .tab-btn:hover {
        color: var(--text-heading);
    }

    .tab-btn.active {
        background: #eaf6f0;
        color: #087d59;
        border: 1px solid #d1eae0;
        font-weight: 700;
    }

    .filter-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 16px;
        border-bottom: 1px solid #f1f5f9;
        background: #fafafa;
        gap: 10px;
        flex-wrap: wrap;
        overflow: visible;
    }

    .btn-toolbar-action {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        height: 32px;
        font-size: 12px;
        font-weight: 700;
        padding: 0 12px;
        border-radius: var(--radius-sm);
        white-space: nowrap;
        flex-shrink: 0;
    }

    .team-picker-btn {
        background: #ffffff !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: var(--radius-sm) !important;
        color: #1e293b !important;
        height: 32px;
        padding: 4px 10px;
        font-size: 12px;
        font-weight: 500;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
        cursor: pointer;
        outline: none;
        transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
    }

    .team-picker-btn:hover {
        background: #f8fafc !important;
        border-color: #087d59 !important;
        color: #087d59 !important;
    }

    .team-picker-btn:focus, .team-picker-btn.show, .team-picker-btn:active {
        background: #ffffff !important;
        border-color: #087d59 !important;
        color: #0f172a !important;
        box-shadow: 0 0 0 2px rgba(8, 125, 89, 0.18) !important;
    }

    .team-picker-item {
        color: var(--text-body);
        transition: background 0.12s ease;
    }
    .team-picker-item:hover, .team-picker-item:focus {
        background-color: var(--brand-green-light);
        color: var(--brand-green);
        font-weight: 600;
    }

    .table-responsive {
        width: 100%;
        overflow-x: auto;
        border-bottom-left-radius: var(--radius-md);
        border-bottom-right-radius: var(--radius-md);
    }

    .leads-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }

    .leads-table th {
        background: #f8fafc;
        color: var(--text-muted);
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        padding: 10px 14px;
        text-align: left;
        border-bottom: 1px solid var(--border);
    }

    .leads-table td {
        padding: 10px 14px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .leads-table tr:hover {
        background: #f8fafc;
    }

    .leads-table tr.selected {
        background: #f0fdf4 !important;
    }

    .tag-approved {
        background: #eaf6f0;
        color: #087d59;
        border: 1px solid #d1eae0;
        font-size: 10.5px;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 4px;
    }

    .tag-pending {
        background: #fef3c7;
        color: #b45309;
        border: 1px solid #fde68a;
        font-size: 10.5px;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 4px;
    }

    .action-btn-link {
        font-size: 11.5px;
        font-weight: 700;
        color: #087d59;
        cursor: pointer;
        text-decoration: none;
    }

    .action-btn-link:hover {
        text-decoration: underline;
    }

    /* Toast Notification */
    #toastContainer {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .custom-toast {
        background: #0f172a;
        color: #ffffff;
        padding: 10px 18px;
        border-radius: 8px;
        font-size: 12.5px;
        box-shadow: 0 4px 14px rgba(0,0,0,0.18);
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideIn 0.25s ease;
    }

    .custom-toast.toast-success { background: #087d59; }
    .custom-toast.toast-error { background: #dc2626; }

    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    /* Table Action Buttons */
    .row-actions-group {
        display: inline-flex;
        align-items: center;
        justify-content: flex-end;
        gap: 6px;
    }

    .btn-row-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 32px;
        padding: 0 12px;
        font-size: 12px;
        font-weight: 600;
        border-radius: 6px;
        border: 1px solid transparent;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.18s ease;
        white-space: nowrap;
        line-height: 1;
        box-sizing: border-box;
    }

    .btn-row-undo {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #475569;
    }
    .btn-row-undo:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
        color: #0f172a;
    }

    .btn-row-approve {
        background: #087d59;
        border-color: #087d59;
        color: #ffffff;
    }
    .btn-row-approve:hover {
        background: #066346;
        border-color: #066346;
        color: #ffffff;
    }

    .btn-row-assign {
        background: #ecfdf5;
        border-color: #a7f3d0;
        color: #065f46;
    }
    .btn-row-assign:hover {
        background: #087d59;
        border-color: #087d59;
        color: #ffffff;
    }

    .btn-row-delete {
        width: 34px;
        height: 32px;
        padding: 0;
        background: #fef2f2;
        border-color: #fecaca;
        color: #dc2626;
        font-size: 15px;
    }
    .btn-row-delete:hover {
        background: #dc2626;
        border-color: #dc2626;
        color: #ffffff;
        transform: scale(1.06);
        box-shadow: 0 2px 6px rgba(220, 38, 38, 0.25);
    }
</style>

<div class="lead-auto-container">
    
    <!-- Header Title Bar -->
    <div class="lead-auto-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h4 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                <i class="fa-solid fa-wand-magic-sparkles text-primary"></i>
                Lead Intake & Automation Distribution
            </h4>
            <p class="text-muted small mb-0">
                Google Sheets / Facebook Meta Lead Ads auto-sync, intelligent Gemini parsing, and fair round-robin team distribution.
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <?php if ($gemini_active): ?>
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 small fw-semibold" title="Gemini 2.5 Flash active">
                    <i class="fa-solid fa-brain me-1"></i> Gemini AI Active
                </span>
            <?php endif; ?>

            <button id="btnScrape" class="btn btn-success btn-sm px-3 fw-bold d-flex align-items-center gap-2" onclick="triggerScrape()">
                <span id="btnScrapeSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                <i id="btnScrapeIcon" class="fa-solid fa-cloud-arrow-down"></i>
                <span>Sync Google Sheet</span>
            </button>
        </div>
    </div>

    <!-- Lead Source Bar -->
    <div class="lead-source-bar">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="small text-muted fw-bold text-uppercase" style="letter-spacing: 0.5px;">Source:</span>
            <span class="lead-source-badge">
                <i class="fa-brands fa-google text-success"></i>
                Google Sheets (Meta Form Leads)
            </span>
            <?php if ($canViewLiveSheet && !empty($sheet_url)): ?>
                <a href="<?= htmlspecialchars($sheet_url) ?>" target="_blank" class="small fw-semibold text-decoration-none text-success d-inline-flex align-items-center gap-1 ms-1" title="Open Google Sheet">
                    View live sheet <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 10px;"></i>
                </a>
            <?php endif; ?>
        </div>

        <div class="d-flex align-items-center gap-3 small text-muted">
            <span class="badge-sync-live">
                <span class="sync-pulse-dot"></span> Auto-Sync Ready
            </span>
            <span class="small"><i class="fa-regular fa-clock me-1"></i> Daily Cron: 2:00 AM</span>
        </div>
    </div>

    <!-- 4 Metric Cards -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-title">Total Leads Received</div>
            <div class="metric-value val-dark" id="statTotalLeads">--</div>
            <div class="metric-caption">Automatically synced from Meta</div>
        </div>

        <div class="metric-card">
            <div class="metric-title">Distributed & Approved</div>
            <div class="metric-value val-green" id="statApprovedLeads">--</div>
            <div class="metric-caption">distribute = 1 (Visible to Sales Team)</div>
        </div>

        <div class="metric-card">
            <div class="metric-title">Awaiting Approval</div>
            <div class="metric-value val-amber" id="statPendingLeads">--</div>
            <div class="metric-caption">distribute = 0 (Pending Review)</div>
        </div>

        <div class="metric-card">
            <div class="metric-title">Active Sales Teams</div>
            <div class="metric-value val-purple" id="statTotalTeams"><?= count($teams ?? []) ?></div>
            <div class="metric-caption">Fair round-robin rotation active</div>
        </div>
    </div>

    <!-- Middle Allocation Section -->
    <div class="allocation-section">
        <!-- Left: Team Allocation Cards -->
        <div class="team-allocation-box">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold mb-0 text-dark small">Sales Team Allocation</h6>
            </div>
            <div class="teams-horizontal-scroll" id="teamsContainer">
                <div class="text-center py-3 text-muted small w-100">
                    <span class="spinner-border spinner-border-sm text-success me-1"></span> Loading team stats...
                </div>
            </div>
        </div>

        <!-- Right: Fast Approval Box -->
        <div class="lead-action-box">
            <div>
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <h6 class="fw-bold mb-0 text-dark small">Pending Distribution Queue</h6>
                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle" id="actionReadyCount">0 pending</span>
                </div>
                <p class="small text-muted mb-3" style="font-size: 11.5px; line-height: 1.4;">
                    New leads are stored with <code>distribute = 0</code>. Approving them changes to <code>distribute = 1</code> and immediately transfers them to respective Sales Team Leaders.
                </p>
            </div>

            <div>
                <button id="btnApproveAllPending" class="btn-action-primary" onclick="bulkApproveAllPending()">
                    <i class="fa-solid fa-check-double me-1"></i> Approve All Pending Leads
                </button>
                <div class="text-center mt-2">
                    <span class="text-muted" style="font-size: 11px;">Automatic duplicate check & fair rotation active</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Leads Queue Table Card -->
    <div class="queue-container">
        <div class="queue-header">
            <div class="d-flex align-items-center gap-2">
                <h6 class="fw-bold mb-0 text-dark">Lead Queue</h6>
                <span class="badge bg-light text-muted border rounded-pill" id="queueTotalPill">0 leads</span>
            </div>

            <div class="queue-tabs">
                <button class="tab-btn" id="tabAll" onclick="setTabFilter('')">All Synced Leads <span id="countTabAll"></span></button>
                <button class="tab-btn active" id="tabPending" onclick="setTabFilter('0')">Pending Approval <span id="countTabPending"></span></button>
                <button class="tab-btn" id="tabApproved" onclick="setTabFilter('1')">Distributed & Approved <span id="countTabApproved"></span></button>
            </div>
        </div>

        <!-- Filter Toolbar -->
        <div class="filter-toolbar">
            <!-- Left: Search & Filter Controls (Pashapashi) -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Search Input -->
                <div style="width: 175px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted px-2"><i class="fa-solid fa-magnifying-glass" style="font-size: 11px;"></i></span>
                        <input type="text" id="searchInput" class="form-control form-control-sm border-start-0 ps-1" placeholder="Search leads..." oninput="debounceSearch()">
                    </div>
                </div>

                <!-- Team Filter Dropdown -->
                <div style="width: 165px;">
                    <select id="teamFilterDropdown" class="form-select form-select-sm" onchange="onTeamDropdownChange(this.value)">
                        <option value="">All Leads (Any Team)</option>
                        <option value="unassigned">⚠️ Unassigned Leads</option>
                        <option value="assigned">✅ Assigned Leads</option>
                        <optgroup label="Filter by Sales Team">
                            <?php foreach ($teams as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>

                <!-- Page Size Select -->
                <div class="d-flex align-items-center gap-1">
                    <span class="small text-muted" style="font-size: 11px; white-space: nowrap;">Show:</span>
                    <select id="pageSizeSelect" class="form-select form-select-sm" style="width: 85px;" onchange="onPageSizeChange(this.value)" title="Rows per page">
                        <option value="10">10 rows</option>
                        <option value="20">20 rows</option>
                        <option value="30" selected>30 rows</option>
                        <option value="40">40 rows</option>
                        <option value="50">50 rows</option>
                        <option value="100">100 rows</option>
                        <option value="all">All rows</option>
                    </select>
                </div>
            </div>

            <!-- Right: Action & Assignment Controls -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Selection Count Badge -->
                <span id="selectionCountText" class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2 small fw-bold" style="display:none; font-size: 11.5px;">0 selected</span>

                <!-- Searchable Choose Team to Assign Dropdown -->
                <div id="assignTeamWrapper" class="position-relative" style="display:none;">
                    <input type="hidden" id="bulkAssignTeamSelect" value="">
                    <div class="dropdown" id="bulkTeamDropdown">
                        <button class="team-picker-btn text-start text-truncate" 
                                type="button" 
                                id="bulkTeamDropdownBtn" 
                                data-bs-toggle="dropdown" 
                                data-bs-auto-close="outside"
                                aria-expanded="false" 
                                style="width: 185px;"
                                title="Choose sales team to assign selected leads">
                            <span id="bulkTeamDropdownLabel" class="text-truncate">Choose Team to Assign...</span>
                            <i class="fa-solid fa-chevron-down text-muted" style="font-size: 10px; flex-shrink: 0;"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-2 shadow border" id="bulkTeamDropdownMenu" style="min-width: 230px; max-height: 320px; font-size: 12px; border-radius: 8px; z-index: 1060;" aria-labelledby="bulkTeamDropdownBtn">
                            <div class="position-relative mb-2">
                                <i class="fa-solid fa-magnifying-glass position-absolute text-muted" style="left: 3px;top: 9px;font-size: 11px;"></i>
                                <input type="text" class="form-control form-control-sm ps-4" id="teamPickerSearchInput" placeholder="Search team..." oninput="filterTeamPickerOptions(this.value)" autocomplete="off">
                            </div>
                            <div id="teamPickerList" style="max-height: 220px; overflow-y: auto;">
                                <a class="dropdown-item py-1 px-2 rounded small text-muted" href="javascript:void(0)" onclick="selectTeamForAssign('', 'Choose Team to Assign...')">
                                    <em>-- Clear Selection --</em>
                                </a>
                                <?php foreach ($teams as $t): ?>
                                    <a class="dropdown-item py-1 px-2 rounded small team-picker-item" href="javascript:void(0)" data-team-id="<?= $t['id'] ?>" data-team-name="<?= htmlspecialchars($t['name']) ?>" onclick="selectTeamForAssign('<?= $t['id'] ?>', '<?= htmlspecialchars(addslashes($t['name'])) ?>')">
                                        <?= htmlspecialchars($t['name']) ?>
                                    </a>
                                <?php endforeach; ?>
                                <div id="teamPickerNoMatch" class="text-muted text-center py-2 small" style="display:none;">No matching team</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons (Using btn-toolbar-action without d-inline-flex) -->
                <button id="btnBulkApprove" class="btn btn-success btn-sm fw-bold px-3 btn-toolbar-action" style="display:none;" onclick="executeDirectBulkApprove()" title="Directly approve selected leads with their currently assigned teams">
                    <i class="fa-solid fa-check" style="font-size: 13px;"></i> <span id="btnBulkApproveText">Approve Selected</span>
                </button>
                <button id="btnBulkAssignApprove" class="btn btn-primary btn-sm fw-bold px-3 btn-toolbar-action" style="display:none;" onclick="handleBulkAssignApprove()" title="Assign chosen team and auto-approve">
                    <i class="fa-solid fa-user-check" style="font-size: 12px;"></i> <span id="btnBulkAssignApproveText">Assign & Approve</span>
                </button>
                <button id="btnBulkUndo" class="btn btn-outline-secondary btn-sm fw-bold px-3 btn-toolbar-action" style="display:none;" onclick="bulkUndoSelected()">
                    <i class="fa-solid fa-rotate-left" style="font-size: 12px;"></i> <span id="btnBulkUndoText">Revert to Pending</span>
                </button>
                <button id="btnBulkDelete" class="btn btn-danger btn-sm fw-bold px-3 btn-toolbar-action" style="display:none;" onclick="bulkDeleteSelected()">
                    <i class="fa-solid fa-trash-can" style="font-size: 13px;"></i> <span id="btnBulkDeleteText">Delete Selected Leads</span>
                </button>
                <button id="btnDeleteTeamApproved" class="btn btn-outline-danger btn-sm fw-bold px-3 btn-toolbar-action" style="display:none;" onclick="bulkDeleteTeamApproved()">
                    <i class="fa-solid fa-trash-can" style="font-size: 13px;"></i> <span id="btnDeleteTeamApprovedText">Delete Team Approved</span>
                </button>
            </div>
        </div>

        <!-- Table View -->
        <div class="table-responsive">
            <table class="leads-table">
                <thead>
                    <tr>
                        <th style="width: 36px; text-align: center;">
                            <input type="checkbox" id="selectAllCheckbox" class="form-check-input" onchange="toggleSelectAll(this.checked)">
                        </th>
                        <th style="min-width: 180px;">Client Info</th>
                        <th style="min-width: 160px;">Matched Project</th>
                        <th style="min-width: 180px;">Campaign Name</th>
                        <th style="min-width: 110px;">Platform</th>
                        <th style="min-width: 110px;">Sync Date</th>
                        <th style="min-width: 140px;">Assigned Team</th>
                        <th style="min-width: 150px; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody id="leadsTableBody">
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <div class="spinner-border spinner-border-sm text-success me-1"></div> Loading leads...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer / Pagination -->
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light small text-muted">
            <span id="paginationText">Showing 0 of 0 leads</span>
            <div class="d-flex align-items-center gap-1">
                <button class="btn btn-outline-secondary btn-sm py-0 px-2" onclick="changePage(-1)" id="btnPrevPage" disabled>&laquo; Prev</button>
                <span class="px-2" id="pageNumberText">Page 1</span>
                <button class="btn btn-outline-secondary btn-sm py-0 px-2" onclick="changePage(1)" id="btnNextPage">Next &raquo;</button>
            </div>
        </div>
    </div>
</div>

<!-- Assign Team & Auto-Approve Modal -->
<div class="modal fade" id="assignTeamModal" tabindex="-1" aria-labelledby="assignTeamModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
        <div class="modal-content shadow border-0" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header bg-success text-white py-3">
                <h6 class="modal-title fw-bold" id="assignTeamModalLabel">
                    <i class="fa-solid fa-user-check me-2"></i>Assign Team & Auto-Approve Leads
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info py-2 px-3 small mb-3 border-0" id="assignModalLeadSummary">
                    Assigning <strong id="assignModalLeadCount">1 lead</strong> to a sales team.
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small text-dark">Select Sales Team</label>
                    <input type="text" class="form-control form-control-sm mb-1" id="modalTeamFilterInput" placeholder="Quick search team name..." oninput="filterModalTeamOptions(this.value)" autocomplete="off">
                    <select id="modalTeamSelect" class="form-select form-select-sm" style="font-size: 13px;">
                        <option value="">-- Keep Current Assigned Team --</option>
                        <?php foreach ($teams as $t): ?>
                            <option value="<?= $t['id'] ?>" class="modal-team-opt"><?= htmlspecialchars($t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-2">
                    <label class="form-label fw-bold small text-muted">Optional: Match / Assign Project</label>
                    <select id="modalProjectSelect" class="form-select form-select-sm" style="font-size: 13px;">
                        <option value="">Keep Existing / Common</option>
                        <?php foreach ($projects ?? [] as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-4 border-top">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success btn-sm fw-bold px-4" id="btnModalConfirmAssign" onclick="confirmModalAssign()">
                    <i class="fa-solid fa-check me-1"></i> Assign & Auto-Approve
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div id="toastContainer"></div>

<script>
    const baseUrl = "<?= $GLOBALS['base_url'] ?>";
    let currentTabStatus = '0'; // Default: Pending Approval (distribute = 0)
    let currentTeamId = '';
    let currentPage = 1;
    let limit = 30;
    let totalCount = 0;
    let searchTimeout = null;
    let selectedLeadIds = new Set();

    document.addEventListener('DOMContentLoaded', () => {
        loadLeads();

        const ddBtn = document.getElementById('bulkTeamDropdownBtn');
        if (ddBtn) {
            ddBtn.addEventListener('shown.bs.dropdown', function () {
                const searchInput = document.getElementById('teamPickerSearchInput');
                if (searchInput) {
                    searchInput.value = '';
                    filterTeamPickerOptions('');
                    setTimeout(() => {
                        try {
                            searchInput.focus({ preventScroll: true });
                        } catch (e) {
                            searchInput.focus();
                        }
                    }, 50);
                }
            });
        }

        const teamSearch = document.getElementById('teamPickerSearchInput');
        if (teamSearch) {
            teamSearch.addEventListener('click', function (e) {
                e.stopPropagation();
            });
            teamSearch.addEventListener('keydown', function (e) {
                e.stopPropagation();
            });
        }
    });

    function filterTeamPickerOptions(query) {
        const q = (query || '').toLowerCase().trim();
        const items = document.querySelectorAll('#teamPickerList .team-picker-item');
        let visibleCount = 0;
        items.forEach(el => {
            const name = (el.getAttribute('data-team-name') || '').toLowerCase();
            if (!q || name.includes(q)) {
                el.style.display = 'block';
                visibleCount++;
            } else {
                el.style.display = 'none';
            }
        });
        const noMatch = document.getElementById('teamPickerNoMatch');
        if (noMatch) {
            noMatch.style.display = (visibleCount === 0) ? 'block' : 'none';
        }
    }

    function selectTeamForAssign(teamId, teamName) {
        const hiddenInput = document.getElementById('bulkAssignTeamSelect');
        if (hiddenInput) {
            hiddenInput.value = teamId;
        }
        const label = document.getElementById('bulkTeamDropdownLabel');
        if (label) {
            label.innerText = teamName || 'Choose Team to Assign...';
            label.title = teamName || 'Choose Team to Assign...';
        }
        const ddBtn = document.getElementById('bulkTeamDropdownBtn');
        if (ddBtn && typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
            const dd = bootstrap.Dropdown.getInstance(ddBtn);
            if (dd) dd.hide();
        }
        const menu = document.getElementById('bulkTeamDropdownMenu');
        if (menu) menu.classList.remove('show');
        if (ddBtn) ddBtn.classList.remove('show');

        updateSelectionUi();
    }

    function filterModalTeamOptions(query) {
        const q = (query || '').toLowerCase().trim();
        const opts = document.querySelectorAll('#modalTeamSelect .modal-team-opt');
        opts.forEach(opt => {
            const text = opt.textContent.toLowerCase();
            opt.style.display = (!q || text.includes(q)) ? '' : 'none';
        });
    }

    // Trigger Scrape from Google Sheet
    async function triggerScrape() {
        const btn = document.getElementById('btnScrape');
        const spinner = document.getElementById('btnScrapeSpinner');
        const icon = document.getElementById('btnScrapeIcon');

        btn.disabled = true;
        spinner.classList.remove('d-none');
        icon.classList.add('d-none');

        showLeadToast('Connecting to Google Sheet & parsing with Gemini AI...', 'info');

        try {
            const formData = new FormData();
            formData.append('sheet_url', '<?= addslashes($sheet_url) ?>');

            const response = await fetch(`${baseUrl}/lead-automation/scrape`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            });

            const res = await response.json();

            if (res.status === 'success') {
                showLeadToast(res.message, 'success');
                loadLeads();
            } else {
                showLeadToast('Sync Failed: ' + (res.message || 'Unknown error'), 'error');
            }
        } catch (err) {
            showLeadToast('Network error while syncing: ' + err.message, 'error');
        } finally {
            btn.disabled = false;
            spinner.classList.add('d-none');
            icon.classList.remove('d-none');
        }
    }

    function setTabFilter(status) {
        currentTabStatus = status;
        currentPage = 1;
        selectedLeadIds.clear();
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        if (status === '') document.getElementById('tabAll').classList.add('active');
        else if (status === '0') document.getElementById('tabPending').classList.add('active');
        else if (status === '1') document.getElementById('tabApproved').classList.add('active');
        updateTeamActionButtons();
        updateSelectionUi();
        loadLeads();
    }

    function filterByTeam(teamId) {
        currentTeamId = teamId;
        currentPage = 1;
        selectedLeadIds.clear();
        document.getElementById('teamFilterDropdown').value = teamId;
        updateApproveAllButtonLabel();
        updateTeamActionButtons();
        updateSelectionUi();
        loadLeads();
    }

    function onTeamDropdownChange(teamId) {
        currentTeamId = teamId;
        currentPage = 1;
        selectedLeadIds.clear();
        updateApproveAllButtonLabel();
        updateTeamActionButtons();
        updateSelectionUi();
        loadLeads();
    }

    function updateApproveAllButtonLabel() {
        const btn = document.getElementById('btnApproveAllPending');
        if (!btn) return;
        if (currentTeamId) {
            const teamOpt = document.querySelector(`#teamFilterDropdown option[value="${currentTeamId}"]`);
            const name = teamOpt ? teamOpt.textContent.trim() : 'Team';
            btn.innerHTML = `<i class="fa-solid fa-check-double me-1"></i> Approve "${escapeHtml(name)}" Pending Leads`;
        } else {
            btn.innerHTML = `<i class="fa-solid fa-check-double me-1"></i> Approve All Pending Leads`;
        }
    }

    function onPageSizeChange(val) {
        limit = (val === 'all') ? 'all' : parseInt(val);
        currentPage = 1;
        loadLeads();
    }

    function debounceSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentPage = 1;
            loadLeads();
        }, 350);
    }

    function changePage(direction) {
        if (limit === 'all') return;
        currentPage += direction;
        if (currentPage < 1) currentPage = 1;
        loadLeads();
    }

    // Main AJAX Load Function
    async function loadLeads() {
        const searchVal = document.getElementById('searchInput').value.trim();
        const offset = (limit === 'all') ? 0 : (currentPage - 1) * limit;

        const params = new URLSearchParams({
            distribute: currentTabStatus,
            team_id   : currentTeamId,
            search    : searchVal,
            limit     : limit,
            offset    : offset,
            _t        : Date.now()
        });

        try {
            const response = await fetch(`${baseUrl}/lead-automation/leads-ajax?${params.toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            const result = await response.json();

            if (result.status === 'success') {
                const data = result.data;
                totalCount = data.total_count || 0;
                renderStats(data.stats, data.teams);
                renderTeamCards(data.teams, data.unassigned_stats);
                renderTable(data.leads, totalCount);
                updatePaginationControls();
                updateTeamActionButtons();
            } else {
                showLeadToast(result.message || 'Error fetching leads', 'error');
            }
        } catch (err) {
            console.error('Fetch error:', err);
            showLeadToast('Failed to load leads from server', 'error');
        }
    }

    function renderStats(stats, teams) {
        const total = parseInt(stats.total_leads || 0);
        const pending = parseInt(stats.pending_leads || 0);
        const approved = parseInt(stats.approved_leads || 0);

        document.getElementById('statTotalLeads').innerText = total.toLocaleString();
        document.getElementById('statPendingLeads').innerText = pending.toLocaleString();
        document.getElementById('statApprovedLeads').innerText = approved.toLocaleString();

        document.getElementById('countTabAll').innerText = `(${total})`;
        document.getElementById('countTabPending').innerText = `(${pending})`;
        document.getElementById('countTabApproved').innerText = `(${approved})`;

        document.getElementById('queueTotalPill').innerText = `${totalCount} leads`;
        document.getElementById('actionReadyCount').innerText = `${pending} pending`;
    }

    function renderTeamCards(teams, unassignedStats) {
        const container = document.getElementById('teamsContainer');
        if (!teams || teams.length === 0) {
            container.innerHTML = '<span class="text-muted small">No teams configured</span>';
            return;
        }

        let cardsHtml = '';

        // Prepend Unassigned Leads card if unassigned leads exist
        if (unassignedStats && parseInt(unassignedStats.total_unassigned || 0) > 0) {
            const isUnassignedSelected = (currentTeamId === 'unassigned');
            const unassignedPending = parseInt(unassignedStats.pending_unassigned || 0);
            const unassignedTotal = parseInt(unassignedStats.total_unassigned || 0);
            cardsHtml += `
                <div class="team-metric-card team-card-unassigned ${isUnassignedSelected ? 'active' : ''}" onclick="filterByTeam('unassigned')">
                    <div class="team-card-name" title="Common Unassigned Leads"><i class="fa-solid fa-triangle-exclamation me-1"></i>Unassigned</div>
                    <div class="team-card-assigned">${unassignedTotal} leads</div>
                    <div class="team-card-sub">${unassignedPending} pending</div>
                </div>
            `;
        }

        teams.forEach(team => {
            const isSelected = String(currentTeamId) === String(team.id);
            const totalLeads = parseInt(team.total_leads || 0);
            const pendingCount = parseInt(team.pending_leads || 0);

            cardsHtml += `
                <div class="team-metric-card ${isSelected ? 'active' : ''}" onclick="filterByTeam('${team.id}')">
                    <div class="team-card-name" title="${escapeHtml(team.name)}">${escapeHtml(team.name)}</div>
                    <div class="team-card-assigned">${totalLeads} leads</div>
                    <div class="team-card-sub">${pendingCount} pending</div>
                </div>
            `;
        });
        container.innerHTML = cardsHtml;
    }

    function renderTable(leads, count) {
        const tbody = document.getElementById('leadsTableBody');
        const displayedText = (limit === 'all') ? `Showing all ${leads.length} of ${count} leads` : `Showing ${leads.length} of ${count} leads`;
        document.getElementById('paginationText').innerText = displayedText;

        if (!leads || leads.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="fa-regular fa-folder-open mb-2" style="font-size: 24px;"></i>
                        <p class="mb-0 fw-semibold">No leads found in this view.</p>
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        leads.forEach(lead => {
            const isSelected = selectedLeadIds.has(parseInt(lead.id));
            const isApproved = String(lead.distribute) === '1';
            const isUnassigned = (!lead.team_id || parseInt(lead.team_id) === 0);
            const isProjectUnassigned = (!lead.project_id || parseInt(lead.project_id) === 0);
            const teamName = isUnassigned ? 'Unassigned (No Team)' : (lead.team_name ? lead.team_name : 'Team #' + lead.team_id);
            const projectName = isProjectUnassigned ? 'Common / Unassigned' : (lead.project_name ? lead.project_name : 'Project #' + lead.project_id);

            html += `
                <tr id="row-${lead.id}" class="${isSelected ? 'selected' : ''}">
                    <td class="text-center">
                        <input type="checkbox" class="form-check-input lead-cb" value="${lead.id}" 
                               ${isSelected ? 'checked' : ''} onchange="toggleSelectLead(${lead.id}, this.checked)">
                    </td>
                    <td>
                        <div class="fw-bold text-dark">${escapeHtml(lead.full_name || 'N/A')}</div>
                        <div class="text-muted small"><i class="fa-solid fa-phone me-1"></i>${escapeHtml(lead.phone_number || 'N/A')} &bull; #LD-${lead.id}</div>
                    </td>
                    <td>
                        <div class="fw-semibold ${isProjectUnassigned ? 'text-secondary' : 'text-dark'}">${escapeHtml(projectName)}</div>
                        <div class="text-muted small">${escapeHtml(lead.city || 'Dhaka')}</div>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark text-truncate" style="max-width: 200px;" title="${escapeHtml(lead.campaign_name || '')}">
                            ${escapeHtml(lead.campaign_name || 'Direct Campaign')}
                        </div>
                        <div class="text-muted small">Meta Instant Form</div>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border">
                            <i class="fa-brands fa-facebook text-primary me-1"></i> Facebook
                        </span>
                    </td>
                    <td>
                        <span class="small text-muted">${formatDate(lead.created_at)}</span>
                    </td>
                    <td>
                        <div class="fw-bold small ${isUnassigned ? 'text-danger' : 'text-dark'}">
                            ${isUnassigned ? '<i class="fa-solid fa-circle-exclamation me-1 text-warning"></i>' : ''}${escapeHtml(teamName)}
                        </div>
                        ${isApproved ? `
                            <span class="tag-approved"><i class="fa-solid fa-check me-1"></i> Approved</span>
                        ` : `
                            <span class="tag-pending"><i class="fa-regular fa-clock me-1"></i> ${isUnassigned ? 'Pending Assign' : 'Pending'}</span>
                        `}
                    </td>
                    <td class="text-end">
                        <div class="row-actions-group">
                            ${isApproved ? `
                                <button type="button" class="btn-row-action btn-row-undo" onclick="unapproveLead(${lead.id})" title="Revert back to Pending">
                                    <i class="fa-solid fa-rotate-left me-1" style="font-size: 11px;"></i> Undo
                                </button>
                            ` : (isUnassigned ? `
                                <button type="button" class="btn-row-action btn-row-assign" onclick="openAssignModal([${lead.id}])" title="Assign Sales Team & Auto-Approve">
                                    <i class="fa-solid fa-user-plus me-1" style="font-size: 11px;"></i> Assign & Approve
                                </button>
                            ` : `
                                <button type="button" class="btn-row-action btn-row-approve" onclick="approveSingleLead(${lead.id})" title="Approve & Distribute to ${escapeHtml(teamName)}">
                                    <i class="fa-solid fa-check me-1" style="font-size: 11px;"></i> Approve
                                </button>
                            `)}
                            <button type="button" class="btn-row-action btn-row-delete" onclick="deleteSingleLead(${lead.id}, '${escapeHtml(lead.full_name || 'Lead')}')" title="Permanently Delete Lead from Database">
                                <i class="fa-solid fa-trash-can" style="font-size: 14px;"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
        updateSelectionUi();
    }

    function toggleSelectLead(id, isChecked) {
        id = parseInt(id);
        if (isChecked) selectedLeadIds.add(id);
        else selectedLeadIds.delete(id);

        const row = document.getElementById(`row-${id}`);
        if (row) row.classList.toggle('selected', isChecked);
        updateSelectionUi();
    }

    function toggleSelectAll(isChecked) {
        const checkboxes = document.querySelectorAll('.lead-cb');
        checkboxes.forEach(cb => {
            cb.checked = isChecked;
            const id = parseInt(cb.value);
            if (isChecked) selectedLeadIds.add(id);
            else selectedLeadIds.delete(id);

            const row = document.getElementById(`row-${id}`);
            if (row) row.classList.toggle('selected', isChecked);
        });
        updateSelectionUi();
    }

    function updateSelectionUi() {
        const count = selectedLeadIds.size;
        const btnApprove = document.getElementById('btnBulkApprove');
        const btnAssignApprove = document.getElementById('btnBulkAssignApprove');
        const btnUndo = document.getElementById('btnBulkUndo');
        const btnDelete = document.getElementById('btnBulkDelete');
        const label = document.getElementById('selectionCountText');
        const assignWrapper = document.getElementById('assignTeamWrapper');

        if (count > 0) {
            if (label) {
                label.style.display = 'inline-block';
                label.innerText = `${count} selected`;
            }

            if (btnDelete) {
                btnDelete.style.display = 'inline-flex';
                btnDelete.disabled = false;
                btnDelete.innerHTML = `<i class="fa-solid fa-trash-can" style="font-size: 13px;"></i> <span>Delete Selected (${count})</span>`;
            }

            if (currentTabStatus === '1') {
                // Distributed & Approved tab: Hide bulk approve & assign, show bulk undo & bulk delete
                if (btnApprove) btnApprove.style.display = 'none';
                if (btnAssignApprove) btnAssignApprove.style.display = 'none';
                if (assignWrapper) assignWrapper.style.display = 'none';
                if (btnUndo) {
                    btnUndo.style.display = 'inline-flex';
                    btnUndo.disabled = false;
                    btnUndo.innerHTML = `<i class="fa-solid fa-rotate-left" style="font-size: 12px;"></i> <span>Revert to Pending (${count})</span>`;
                }
            } else {
                // Pending Approval or All Synced Leads
                if (btnUndo) btnUndo.style.display = 'none';
                if (assignWrapper) assignWrapper.style.display = 'inline-block';
                if (btnApprove) {
                    btnApprove.style.display = 'inline-flex';
                    btnApprove.disabled = false;
                    btnApprove.innerHTML = `<i class="fa-solid fa-check" style="font-size: 13px;"></i> <span>Approve (${count})</span>`;
                    btnApprove.title = `Directly approve ${count} selected lead(s) with existing assigned team`;
                }

                if (btnAssignApprove) {
                    const toolbarTeam = document.getElementById('bulkAssignTeamSelect') ? document.getElementById('bulkAssignTeamSelect').value : '';
                    if (toolbarTeam) {
                        const labelEl = document.getElementById('bulkTeamDropdownLabel');
                        const teamName = labelEl ? labelEl.textContent.trim() : ('Team #' + toolbarTeam);
                        btnAssignApprove.style.display = 'inline-flex';
                        btnAssignApprove.disabled = false;
                        btnAssignApprove.innerHTML = `<i class="fa-solid fa-user-check" style="font-size: 12px;"></i> <span>Assign & Approve (${count})</span>`;
                        btnAssignApprove.title = `Assign ${count} lead(s) to ${teamName} and auto-approve`;
                    } else {
                        btnAssignApprove.style.display = 'none';
                    }
                }
            }
        } else {
            if (label) label.style.display = 'none';
            if (btnAssignApprove) btnAssignApprove.style.display = 'none';

            if (currentTabStatus === '1') {
                if (assignWrapper) assignWrapper.style.display = 'none';
                if (btnApprove) btnApprove.style.display = 'none';
                if (btnUndo) {
                    btnUndo.style.display = 'inline-flex';
                    btnUndo.disabled = true;
                    btnUndo.innerHTML = `<i class="fa-solid fa-rotate-left" style="font-size: 12px;"></i> <span>Revert to Pending</span>`;
                }
            } else {
                if (assignWrapper) assignWrapper.style.display = 'inline-block';
                if (btnUndo) btnUndo.style.display = 'none';
                if (btnApprove) {
                    btnApprove.style.display = 'inline-flex';
                    btnApprove.disabled = true;
                    btnApprove.innerHTML = `<i class="fa-solid fa-check" style="font-size: 13px;"></i> <span>Approve Leads</span>`;
                }
            }

            if (btnDelete) {
                btnDelete.style.display = 'none';
                btnDelete.disabled = true;
            }
        }

        const allCheckboxes = document.querySelectorAll('.lead-cb');
        const selectAll = document.getElementById('selectAllCheckbox');
        if (allCheckboxes.length > 0) {
            selectAll.checked = Array.from(allCheckboxes).every(cb => cb.checked);
        } else {
            selectAll.checked = false;
        }

        updateTeamActionButtons();
    }

    async function bulkUndoSelected() {
        if (selectedLeadIds.size === 0) return;
        const ids = Array.from(selectedLeadIds);
        if (!confirm(`Are you sure you want to revert ${ids.length} selected lead(s) back to Pending status?`)) return;

        try {
            const response = await fetch(`${baseUrl}/lead-automation/unapprove`, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ lead_ids: ids })
            });
            const res = await response.json();
            if (res.status === 'success') {
                showLeadToast(res.message || 'Leads reverted to Pending.', 'info');
                selectedLeadIds.clear();
                loadLeads();
            } else {
                showLeadToast(res.message || 'Action failed', 'error');
            }
        } catch (err) {
            showLeadToast('Error: ' + err.message, 'error');
        }
    }

    function updatePaginationControls() {
        if (limit === 'all') {
            document.getElementById('pageNumberText').innerText = `All Rows (${totalCount})`;
            document.getElementById('btnPrevPage').disabled = true;
            document.getElementById('btnNextPage').disabled = true;
            document.getElementById('paginationText').innerText = `Showing all ${totalCount} of ${totalCount} leads`;
            return;
        }
        const totalPages = Math.ceil(totalCount / limit) || 1;
        document.getElementById('pageNumberText').innerText = `Page ${currentPage} of ${totalPages}`;
        document.getElementById('btnPrevPage').disabled = (currentPage <= 1);
        document.getElementById('btnNextPage').disabled = (currentPage >= totalPages);
    }

    async function approveSingleLead(id) {
        try {
            const response = await fetch(`${baseUrl}/lead-automation/approve`, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ lead_ids: [id] })
            });
            const res = await response.json();
            if (res.status === 'success') {
                showLeadToast(`Lead #${id} distributed & approved!`, 'success');
                selectedLeadIds.delete(id);
                loadLeads();
            } else {
                showLeadToast(res.message || 'Approval failed', 'error');
            }
        } catch (err) {
            showLeadToast('Error: ' + err.message, 'error');
        }
    }

    // Modal state for Assign & Approve
    let modalAssignLeadIds = [];

    async function executeDirectBulkApprove() {
        if (selectedLeadIds.size === 0) return;
        const ids = Array.from(selectedLeadIds);

        const btn = document.getElementById('btnBulkApprove');
        let origHtml = '';
        if (btn) {
            origHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status"></span> Approving...`;
        }

        try {
            const response = await fetch(`${baseUrl}/lead-automation/approve`, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ lead_ids: ids })
            });

            const res = await response.json();
            if (res.status === 'success') {
                showLeadToast(res.message || `Successfully approved ${ids.length} lead(s) for sales distribution!`, 'success');
                selectedLeadIds.clear();
                selectTeamForAssign('', 'Choose Team to Assign...');
                loadLeads();
            } else {
                showLeadToast(res.message || 'Approval failed', 'error');
            }
        } catch (err) {
            showLeadToast('Error: ' + err.message, 'error');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = origHtml || `<i class="fa-solid fa-check" style="font-size: 13px;"></i> <span>Approve Leads</span>`;
            }
        }
    }

    function handleBulkAssignApprove() {
        if (selectedLeadIds.size === 0) return;
        const ids = Array.from(selectedLeadIds);
        const toolbarTeam = document.getElementById('bulkAssignTeamSelect') ? document.getElementById('bulkAssignTeamSelect').value : '';

        if (toolbarTeam) {
            const labelEl = document.getElementById('bulkTeamDropdownLabel');
            const teamName = labelEl ? labelEl.textContent.trim() : ('Team #' + toolbarTeam);
            if (confirm(`Assign ${ids.length} selected lead(s) to "${teamName}" and auto-approve immediately?`)) {
                executeAssignAndApprove(ids, toolbarTeam, null);
            }
            return;
        }

        // If no team chosen in toolbar, directly approve with existing assigned teams
        executeDirectBulkApprove();
    }

    function openAssignModal(ids) {
        if (!ids || ids.length === 0) return;
        modalAssignLeadIds = ids;
        document.getElementById('assignModalLeadCount').innerText = `${ids.length} lead${ids.length > 1 ? 's' : ''}`;

        const toolbarTeam = document.getElementById('bulkAssignTeamSelect') ? document.getElementById('bulkAssignTeamSelect').value : '';
        const modalTeamSelect = document.getElementById('modalTeamSelect');
        if (modalTeamSelect) {
            modalTeamSelect.value = toolbarTeam || (isFinite(currentTeamId) && currentTeamId > 0 ? currentTeamId : '');
        }

        const modalEl = document.getElementById('assignTeamModal');
        if (modalEl && typeof bootstrap !== 'undefined') {
            const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modalInstance.show();
        } else if (modalEl && typeof $ !== 'undefined') {
            $('#assignTeamModal').modal('show');
        } else {
            const chosenTeam = prompt(`Assign ${ids.length} lead(s) to Team ID (leave blank to keep existing):`);
            executeAssignAndApprove(ids, chosenTeam || null, null);
        }
    }

    async function confirmModalAssign() {
        const teamSelect = document.getElementById('modalTeamSelect');
        const teamId = teamSelect ? teamSelect.value : '';
        const projSelect = document.getElementById('modalProjectSelect');
        const projectId = projSelect && projSelect.value ? projSelect.value : null;

        const btn = document.getElementById('btnModalConfirmAssign');
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Processing...`;

        try {
            if (!teamId || teamId === 'keep') {
                // Keep existing assigned team, auto-approve directly
                await executeAssignAndApprove(modalAssignLeadIds, null, projectId);
            } else {
                await executeAssignAndApprove(modalAssignLeadIds, teamId, projectId);
            }

            const modalEl = document.getElementById('assignTeamModal');
            if (modalEl && typeof bootstrap !== 'undefined') {
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) modalInstance.hide();
            } else if (modalEl && typeof $ !== 'undefined') {
                $('#assignTeamModal').modal('hide');
            }
        } finally {
            btn.disabled = false;
            btn.innerHTML = `<i class="fa-solid fa-check me-1"></i> Assign & Auto-Approve`;
        }
    }

    async function executeAssignAndApprove(ids, targetTeamId, targetProjectId) {
        try {
            const payload = {
                lead_ids: ids,
                target_team_id: targetTeamId || null,
                target_project_id: targetProjectId || null
            };

            const response = await fetch(`${baseUrl}/lead-automation/assign-approve`, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            });

            const res = await response.json();
            if (res.status === 'success') {
                showLeadToast(res.message, 'success');
                selectedLeadIds.clear();
                selectTeamForAssign('', 'Choose Team to Assign...');
                loadLeads();
            } else {
                showLeadToast(res.message || 'Assign & Approve failed', 'error');
            }
        } catch (err) {
            showLeadToast('Error: ' + err.message, 'error');
        }
    }

    async function bulkApproveSelected() {
        executeDirectBulkApprove();
    }

    async function bulkApproveAllPending() {
        let teamName = '';
        if (currentTeamId) {
            const teamOpt = document.querySelector(`#teamFilterDropdown option[value="${currentTeamId}"]`);
            teamName = teamOpt ? teamOpt.textContent.trim() : 'Selected Team';
        }

        const msg = currentTeamId
            ? `Are you sure you want to approve all pending leads for "${teamName}"?`
            : 'Are you sure you want to approve ALL pending leads in the queue across all teams?';

        if (!confirm(msg)) return;

        try {
            const approveResp = await fetch(`${baseUrl}/lead-automation/approve`, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    all_pending: true,
                    team_id: currentTeamId || null
                })
            });
            const approveRes = await approveResp.json();
            if (approveRes.status === 'success') {
                showLeadToast(approveRes.message || 'Leads approved successfully!', 'success');
                selectedLeadIds.clear();
                loadLeads();
            } else {
                showLeadToast(approveRes.message || 'Approval failed', 'error');
            }
        } catch (err) {
            showLeadToast('Error: ' + err.message, 'error');
        }
    }

    async function unapproveLead(id) {
        try {
            const response = await fetch(`${baseUrl}/lead-automation/unapprove`, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ lead_ids: [id] })
            });
            const res = await response.json();
            if (res.status === 'success') {
                showLeadToast(`Lead #${id} reverted back to Pending.`, 'info');
                loadLeads();
            } else {
                showLeadToast(res.message || 'Action failed', 'error');
            }
        } catch (err) {
            showLeadToast('Error: ' + err.message, 'error');
        }
    }

    async function deleteSingleLead(id, name) {
        const displayName = name ? `"${name}" (#LD-${id})` : `#LD-${id}`;
        if (!confirm(`Are you sure you want to permanently delete lead ${displayName}?\n\nThis will completely delete it from the database and remove it from the team's leads list.`)) {
            return;
        }

        try {
            const response = await fetch(`${baseUrl}/lead-automation/delete`, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ lead_ids: [id] })
            });
            const res = await response.json();
            if (res.status === 'success') {
                showLeadToast(res.message || `Lead #LD-${id} permanently deleted.`, 'success');
                selectedLeadIds.delete(parseInt(id));
                loadLeads();
            } else {
                showLeadToast(res.message || 'Delete failed', 'error');
            }
        } catch (err) {
            showLeadToast('Error: ' + err.message, 'error');
        }
    }

    async function bulkDeleteSelected() {
        if (selectedLeadIds.size === 0) {
            showLeadToast('Please select at least one lead to delete.', 'error');
            return;
        }

        const ids = Array.from(selectedLeadIds);
        const count = ids.length;

        let teamPrompt = '';
        if (currentTeamId) {
            const teamOpt = document.querySelector(`#teamFilterDropdown option[value="${currentTeamId}"]`);
            if (teamOpt) teamPrompt = ` belonging to "${teamOpt.textContent.trim()}"`;
        }

        const confirmMsg = `Are you sure you want to PERMANENTLY DELETE ${count} selected lead(s)${teamPrompt}?\n\nThis will delete them from the database and completely remove them from the team's leads list. This action cannot be undone.`;

        if (!confirm(confirmMsg)) return;

        const btn = document.getElementById('btnBulkDelete');
        if (btn) btn.disabled = true;

        try {
            const response = await fetch(`${baseUrl}/lead-automation/delete`, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ lead_ids: ids })
            });
            const res = await response.json();
            if (res.status === 'success') {
                showLeadToast(res.message || `Successfully deleted ${count} lead(s)!`, 'success');
                selectedLeadIds.clear();
                loadLeads();
            } else {
                showLeadToast(res.message || 'Bulk delete failed', 'error');
            }
        } catch (err) {
            showLeadToast('Error: ' + err.message, 'error');
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    async function bulkDeleteTeamApproved() {
        if (!currentTeamId) {
            showLeadToast('Please select a sales team first.', 'error');
            return;
        }

        const teamOpt = document.querySelector(`#teamFilterDropdown option[value="${currentTeamId}"]`);
        const teamName = teamOpt ? teamOpt.textContent.trim() : 'Selected Team';

        const confirmMsg = `⚠️ DANGER ZONE:\n\nAre you sure you want to permanently delete ALL approved leads distributed to "${teamName}"?\n\nAll approved leads for this team will be permanently deleted from the database and the team's leads list.`;

        if (!confirm(confirmMsg)) return;

        const btn = document.getElementById('btnDeleteTeamApproved');
        if (btn) btn.disabled = true;

        try {
            const response = await fetch(`${baseUrl}/lead-automation/delete`, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    team_id: currentTeamId,
                    all_approved: true
                })
            });
            const res = await response.json();
            if (res.status === 'success') {
                showLeadToast(res.message || `Deleted all approved leads for ${teamName}!`, 'success');
                selectedLeadIds.clear();
                loadLeads();
            } else {
                showLeadToast(res.message || 'Team purge failed', 'error');
            }
        } catch (err) {
            showLeadToast('Error: ' + err.message, 'error');
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    function updateTeamActionButtons() {
        const btnTeamDelete = document.getElementById('btnDeleteTeamApproved');
        const btnTeamDeleteText = document.getElementById('btnDeleteTeamApprovedText');
        if (!btnTeamDelete) return;

        if (currentTeamId && currentTeamId !== 'unassigned' && currentTeamId !== 'assigned' && currentTabStatus === '1') {
            const teamOpt = document.querySelector(`#teamFilterDropdown option[value="${currentTeamId}"]`);
            const name = teamOpt ? teamOpt.textContent.trim() : 'Team';
            if (btnTeamDeleteText) {
                btnTeamDeleteText.innerText = `Delete All "${name}" Approved`;
            }
            btnTeamDelete.style.display = 'inline-flex';
        } else {
            btnTeamDelete.style.display = 'none';
        }
    }

    function formatDate(dateStr) {
        if (!dateStr) return 'N/A';
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text).replace(/[&<>"']/g, m => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        })[m]);
    }

    function showLeadToast(message, type = 'info') {
        let container = document.getElementById('leadToastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'leadToastContainer';
            container.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:99999;display:flex;flex-direction:column;gap:10px;pointer-events:none;';
            document.body.appendChild(container);
        }
        const toast = document.createElement('div');
        toast.className = `custom-toast ${type === 'success' ? 'toast-success' : (type === 'error' ? 'toast-error' : '')}`;
        toast.style.pointerEvents = 'auto';
        toast.innerHTML = `<span>${escapeHtml(message)}</span>`;
        container.appendChild(toast);
        setTimeout(() => {
            toast.style.transition = 'all 0.3s ease';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    }
</script>


<!-- accha jodi gemini api error dey or jodi limit sesh hoy or gemini api koro karone kaj  na kore tahole ki all tabs theke correct data chole asbe? -->