<?php
/**
 * Dedicated Page: Team & Project Setup for Lead Automation
 * TPHL ERP - Digital Marketing / Management
 */
?>
<style>
    :root {
        --bg-page: #f8fafc;
        --bg-card: #ffffff;
        --border: #e2e8f0;
        --border-subtle: #f1f5f9;
        --text-heading: #0f172a;
        --text-body: #334155;
        --text-muted: #64748b;
        --brand-green: #087d59;
        --brand-green-hover: #066346;
        --brand-green-light: #eaf6f0;
        --radius-sm: 6px;
        --radius-md: 8px;
    }

    .setup-page-container {
        padding: 1rem 0.5rem 2.5rem;
        font-family: inherit;
        max-width: 1440px;
    }

    /* Minimal Top Header */
    .setup-top-bar {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 12px 16px;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
    }

    /* Compact KPI Strip */
    .setup-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
        margin-bottom: 12px;
    }
    @media (max-width: 992px) {
        .setup-kpi-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 576px) {
        .setup-kpi-grid { grid-template-columns: 1fr; }
    }

    .setup-kpi-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 10px 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .setup-kpi-label {
        font-size: 10.5px;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.03em;
        margin-bottom: 2px;
    }
    .setup-kpi-sub {
        font-size: 11px;
        color: #94a3b8;
    }
    .setup-kpi-val {
        font-size: 20px;
        font-weight: 800;
        line-height: 1;
        color: var(--text-heading);
        background: #f8fafc;
        border: 1px solid var(--border-subtle);
        padding: 6px 10px;
        border-radius: 6px;
        min-width: 44px;
        text-align: center;
    }

    /* Minimal Unconfigured On-Sale Card (Yellow Card) */
    .unconfig-banner {
        background: #fffdf5;
        border: 1px solid #fde68a;
        border-radius: var(--radius-md);
        padding: 9px 14px;
        margin-bottom: 12px;
    }
    .unconfig-proj-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #ffffff;
        border: 1px solid #fcd34d;
        color: #92400e;
        padding: 2px 8px;
        border-radius: 5px;
        font-size: 11px;
        font-weight: 600;
        line-height: 1.4;
        cursor: pointer;
        transition: all 0.12s ease;
    }
    .unconfig-proj-pill i {
        font-size: 9px;
        opacity: 0.75;
    }
    .unconfig-proj-pill:hover {
        background: #f59e0b;
        color: #ffffff;
        border-color: #d97706;
    }

    /* Compact Team Selector Pills in Form */
    .setup-team-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 10px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background: #ffffff;
        font-size: 11.5px;
        font-weight: 600;
        color: #334155;
        cursor: pointer;
        user-select: none;
        transition: all 0.12s ease;
    }
    .setup-team-pill:hover {
        border-color: #087d59;
        background: #f8fafc;
    }
    .setup-team-pill .pill-check-box {
        width: 14px;
        height: 14px;
        border-radius: 4px;
        border: 1.5px solid #94a3b8;
        background: #ffffff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 9px;
        color: transparent;
        transition: all 0.12s ease;
        flex-shrink: 0;
    }
    .setup-team-pill.checked {
        background: #eaf6f0;
        border-color: #087d59;
        color: #065f46;
    }
    .setup-team-pill.checked .pill-check-box {
        background: #087d59;
        border-color: #087d59;
        color: #ffffff;
    }

    /* Clean Option Toggle Buttons in Form Row */
    .setup-option-box {
        display: flex;
        align-items: center;
        gap: 8px;
        height: 35px;
    }
    .setup-opt-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        height: 35px;
        padding: 0 11px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background: #f8fafc;
        font-size: 11.5px;
        font-weight: 600;
        color: #475569;
        cursor: pointer;
        user-select: none;
        transition: all 0.12s ease;
        white-space: nowrap;
        flex: 1;
        justify-content: center;
    }
    .setup-opt-pill:hover {
        border-color: #94a3b8;
        background: #f1f5f9;
    }
    .setup-opt-pill .opt-dot {
        width: 14px;
        height: 14px;
        border-radius: 4px;
        border: 1.5px solid #94a3b8;
        background: #ffffff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 9px;
        color: transparent;
        transition: all 0.12s ease;
        flex-shrink: 0;
    }
    .setup-opt-pill.active {
        background: #eaf6f0;
        border-color: #087d59;
        color: #065f46;
    }
    .setup-opt-pill.active .opt-dot {
        background: #087d59;
        border-color: #087d59;
        color: #ffffff;
    }

    /* Clean Toggle Switch for Table */
    .clean-switch {
        position: relative;
        display: inline-block;
        width: 32px;
        height: 18px;
        vertical-align: middle;
        cursor: pointer;
        margin: 0;
    }
    .clean-switch input {
        opacity: 0;
        width: 0;
        height: 0;
        position: absolute;
    }
    .clean-switch-slider {
        position: absolute;
        inset: 0;
        background-color: #cbd5e1;
        border-radius: 999px;
        transition: 0.18s ease;
    }
    .clean-switch-slider::before {
        content: "";
        position: absolute;
        height: 14px;
        width: 14px;
        left: 2px;
        bottom: 2px;
        background-color: #ffffff;
        border-radius: 50%;
        transition: 0.18s ease;
        box-shadow: 0 1px 2px rgba(0,0,0,0.15);
    }
    .clean-switch input:checked + .clean-switch-slider {
        background-color: #087d59;
    }
    .clean-switch input:checked + .clean-switch-slider::before {
        transform: translateX(14px);
    }

    /* Minimal Badges in Configured Projects Table */
    .kw-badge {
        display: inline-block;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #475569;
        font-size: 10.5px;
        font-weight: 600;
        padding: 1px 6px;
        border-radius: 4px;
        line-height: 1.4;
    }
    .team-alloc-wrap {
        display: flex;
        flex-wrap: wrap;
        gap: 3px;
        align-items: center;
    }
    .team-alloc-badge {
        display: inline-flex;
        align-items: center;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #166534;
        font-size: 10px;
        font-weight: 600;
        padding: 1px 6px;
        border-radius: 4px;
        line-height: 1.4;
        white-space: nowrap;
    }
    .team-more-toggle {
        display: inline-flex;
        align-items: center;
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        color: #334155;
        font-size: 10px;
        font-weight: 700;
        padding: 1px 6px;
        border-radius: 4px;
        line-height: 1.4;
        cursor: pointer;
        user-select: none;
        transition: all 0.12s ease;
    }
    .team-more-toggle:hover {
        background: #e2e8f0;
        color: #0f172a;
    }

    /* Clean Minimal Table */
    .setup-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }
    .setup-table th {
        background: #f8fafc;
        color: var(--text-muted);
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        padding: 9px 14px;
        text-align: left;
        border-bottom: 1px solid var(--border);
    }
    .setup-table td {
        padding: 8px 14px;
        border-bottom: 1px solid var(--border-subtle);
        vertical-align: middle;
    }
    .setup-table tr:hover td {
        background: #fcfcfd;
    }

    .custom-toast {
        background: #0f172a;
        color: #ffffff;
        padding: 9px 16px;
        border-radius: 6px;
        font-size: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .custom-toast.toast-success { background: #087d59; }
    .custom-toast.toast-error { background: #dc2626; }
</style>

<div class="setup-page-container">
    <!-- Top Header Bar -->
    <div class="setup-top-bar">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h5 class="fw-bold mb-0 text-dark" style="font-size: 16px;">Team & Project Setup</h5>
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0" style="font-size: 10px; font-weight: 600;">
                    Round-Robin Active
                </span>
            </div>
            <p class="text-muted mb-0" style="font-size: 11.5px; margin-top: 2px;">
                Configure project campaign keywords and monthly Sales Team allocations for automatic lead distribution.
            </p>
        </div>

        <a href="<?= $GLOBALS['base_url'] ?>/lead-automation-upload" class="btn btn-outline-secondary btn-sm px-3 fw-semibold d-inline-flex align-items-center gap-1" style="height: 31px; font-size: 12px;">
            <i class="fa-solid fa-arrow-left" style="font-size: 11px;"></i>
            <span>Back to Lead Automation</span>
        </a>
    </div>

    <!-- 4 Compact KPI Summary Cards -->
    <div class="setup-kpi-grid">
        <div class="setup-kpi-card">
            <div>
                <div class="setup-kpi-label">Configured</div>
                <div class="setup-kpi-sub">Total mapped projects</div>
            </div>
            <div class="setup-kpi-val" id="kpiTotalConfigured">0</div>
        </div>
        <div class="setup-kpi-card">
            <div>
                <div class="setup-kpi-label">Active Rules</div>
                <div class="setup-kpi-sub">Routing live leads</div>
            </div>
            <div class="setup-kpi-val text-success" id="kpiActiveRules">0</div>
        </div>
        <div class="setup-kpi-card">
            <div>
                <div class="setup-kpi-label">Unconfigured (On Sale)</div>
                <div class="setup-kpi-sub">Needs team assignment</div>
            </div>
            <div class="setup-kpi-val text-warning" id="kpiUnconfiguredOnSale">0</div>
        </div>
        <div class="setup-kpi-card">
            <div>
                <div class="setup-kpi-label">Sales Teams</div>
                <div class="setup-kpi-sub">Available for rotation</div>
            </div>
            <div class="setup-kpi-val text-primary" id="kpiTotalTeams"><?= count($teams ?? []) ?></div>
        </div>
    </div>

    <!-- Minimal Yellow Card: Unconfigured "On Sale" Projects -->
    <div id="unconfiguredOnSaleBanner" class="unconfig-banner d-none">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
            <div class="fw-bold text-dark d-flex align-items-center gap-1" style="font-size: 11.5px;">
                <i class="fa-solid fa-circle-exclamation text-warning" style="font-size: 12px;"></i>
                <span>Unconfigured On-Sale Projects (<span id="unconfiguredCountText">0</span>)</span>
                <span class="fw-normal text-muted ms-1" style="font-size: 11px;">— Click a project to configure</span>
            </div>
        </div>
        <div id="unconfiguredProjectsPills" class="d-flex flex-wrap gap-1 mt-1"></div>
    </div>

    <!-- Data Entry / Edit Card -->
    <div class="card border-0 shadow-none mb-3" id="setupFormCard" style="border-radius: 8px; border: 1px solid #e2e8f0 !important;">
        <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center border-bottom" style="border-color: #f1f5f9 !important;">
            <span class="fw-bold text-dark" id="setupFormHeaderTitle" style="font-size: 13px;">
                <i class="fa-solid fa-plus-circle text-success me-1"></i> Add / Update Project & Team Allocation
            </span>
            <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 text-muted" style="font-size: 11.5px;" onclick="resetSetupForm()">
                <i class="fa-solid fa-rotate-left me-1"></i>Clear Form
            </button>
        </div>
        <div class="card-body p-3 bg-white" style="border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;">
            <div class="row g-3 align-items-end">
                <!-- 1. Select Project -->
                <div class="col-lg-4 col-md-6">
                    <label class="form-label fw-bold text-dark mb-1" style="font-size: 11.5px;">
                        1. Select Project <span class="text-danger">*</span>
                    </label>
                    <select id="setupProjectSelect" class="form-select form-select-sm" style="height: 35px; font-size: 12px;" onchange="onSetupProjectSelectChange(this.value)">
                        <option value="">-- Choose ERP Project --</option>
                    </select>
                </div>

                <!-- 2. Matching Keywords -->
                <div class="col-lg-5 col-md-6">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-bold text-dark mb-0" style="font-size: 11.5px;">
                            2. Campaign Matching Keywords <span class="text-danger">*</span>
                        </label>
                        <button type="button" class="btn btn-link p-0 text-success fw-semibold text-decoration-none d-inline-flex align-items-center gap-1" style="font-size: 11px;" onclick="autoSuggestKeywordsForSelectedProject()">
                            <i class="fa-solid fa-wand-magic-sparkles" style="font-size: 10px;"></i>
                            <span>Auto-generate</span>
                        </button>
                    </div>
                    <input type="text" id="setupKeywordsInput" class="form-control form-control-sm" style="height: 35px; font-size: 12px;" placeholder="e.g. green paradise, paradise, tpgp (comma separated)">
                </div>

                <!-- 3. Status & Re-Match Option -->
                <div class="col-lg-3 col-md-12">
                    <label class="form-label fw-bold text-dark mb-1" style="font-size: 11.5px;">
                        3. Automation Options
                    </label>
                    <div class="setup-option-box">
                        <input type="checkbox" id="setupIsActiveToggle" class="d-none" checked>
                        <div id="pillIsActiveToggle" class="setup-opt-pill active" onclick="toggleSetupOptionPill('setupIsActiveToggle', 'pillIsActiveToggle')" title="Enable or pause automatic routing for this project">
                            <span class="opt-dot"><i class="fa-solid fa-check"></i></span>
                            <span>Active</span>
                        </div>

                        <input type="checkbox" id="setupRematchCommonCheck" class="d-none" checked>
                        <div id="pillRematchCommon" class="setup-opt-pill active" onclick="toggleSetupOptionPill('setupRematchCommonCheck', 'pillRematchCommon')" title="Automatically assign existing 'Common' leads matching these keywords">
                            <span class="opt-dot"><i class="fa-solid fa-check"></i></span>
                            <span>Fix "Common" Leads</span>
                        </div>
                    </div>
                </div>

                <!-- 4. Sales Teams Multi-Select Grid -->
                <div class="col-12 mt-2 pt-1">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-1">
                        <label class="form-label fw-bold text-dark mb-0" style="font-size: 11.5px;">
                            4. Assign Sales Teams <span class="text-danger">*</span>
                            <span id="setupSelectedTeamsBadge" class="badge bg-success-subtle text-success border border-success-subtle ms-1" style="font-size: 10.5px;">0 selected</span>
                        </label>
                        <div class="d-flex align-items-center gap-1">
                            <input type="text" id="setupTeamSearchInput" class="form-control form-control-sm py-0 px-2" style="width: 145px; height: 24px; font-size: 11px;" placeholder="Filter teams..." oninput="filterSetupTeamPills(this.value)">
                            <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 10.5px; height: 24px;" onclick="toggleAllSetupTeams(true)">All</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 10.5px; height: 24px;" onclick="toggleAllSetupTeams(false)">None</button>
                        </div>
                    </div>

                    <div id="setupTeamsCheckboxGrid" class="d-flex flex-wrap gap-1 p-2 border rounded" style="background: #f8fafc; border-color: #e2e8f0 !important;">
                        <span class="text-muted small">Loading teams...</span>
                    </div>
                </div>

                <!-- Submit Row -->
                <div class="col-12 d-flex justify-content-end align-items-center gap-2 mt-2 pt-2 border-top" style="border-color: #f1f5f9 !important;">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" style="height: 31px; font-size: 11.5px;" onclick="resetSetupForm()">Reset</button>
                    <button type="button" id="btnSaveProjectRule" class="btn btn-success btn-sm fw-bold px-3 d-inline-flex align-items-center gap-1" style="height: 31px; font-size: 11.5px; background: #087d59; border-color: #087d59;" onclick="saveProjectSetupRule()">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span id="btnSaveProjectRuleText">Save Setup</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Configured Projects Table Card -->
    <div class="card border-0 shadow-none" style="border-radius: 8px; border: 1px solid #e2e8f0 !important;">
        <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom" style="border-color: #f1f5f9 !important;">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-bold text-dark" style="font-size: 13px;">Configured Projects & Team Allocations</span>
                <span class="badge bg-light text-secondary border rounded-pill" style="font-size: 10.5px;" id="configuredRulesCountBadge">0 projects</span>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <select id="setupTableStatusFilter" class="form-select form-select-sm py-0" style="width: 150px; height: 28px; font-size: 11.5px;" onchange="renderSetupRulesTable()">
                    <option value="">All Configured</option>
                    <option value="On Sale">🟢 On Sale Only</option>
                    <option value="Sold Out">⚪ Sold Out Only</option>
                    <option value="active">✅ Active Only</option>
                    <option value="paused">⏸️ Paused Only</option>
                </select>
                <input type="text" id="setupTableSearchInput" class="form-control form-control-sm py-0 px-2" style="width: 210px; height: 28px; font-size: 11.5px;" placeholder="Search project, keyword, team..." oninput="renderSetupRulesTable()">
            </div>
        </div>
        <div class="table-responsive">
            <table class="setup-table mb-0">
                <thead>
                    <tr>
                        <th style="width: 22%;">Project Name</th>
                        <th style="width: 9%;">Status</th>
                        <th style="width: 25%;">Matching Keywords</th>
                        <th style="width: 31%;">Assigned Teams (Round-Robin)</th>
                        <th style="width: 5%; text-align: center;">Active</th>
                        <th style="width: 8%; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="setupRulesTableBody">
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <span class="spinner-border spinner-border-sm text-success me-1"></span> Loading project configurations...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const baseUrl = "<?= $GLOBALS['base_url'] ?>";
    let setupAllRules = [];
    let setupAllProjects = [];
    let setupAllTeams = [];
    let setupSelectedTeamIds = new Set();
    let expandedTeamRows = new Set();

    document.addEventListener('DOMContentLoaded', () => {
        loadProjectSetupRules();
    });

    async function loadProjectSetupRules() {
        try {
            const response = await fetch(`${baseUrl}/lead-automation/project-rules?_t=${Date.now()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            const res = await response.json();
            if (res.status !== 'success') {
                showSetupToast(res.message || 'Failed to load setup rules', 'error');
                return;
            }

            const data = res.data || {};
            setupAllRules = data.rules || [];
            setupAllProjects = data.all_projects || [];
            setupAllTeams = data.teams || [];
            const unconfiguredOnSale = data.unconfigured_on_sale || [];

            // Update KPI Cards
            const activeCount = setupAllRules.filter(r => parseInt(r.is_active) === 1).length;
            if (document.getElementById('kpiTotalConfigured')) document.getElementById('kpiTotalConfigured').innerText = setupAllRules.length;
            if (document.getElementById('kpiActiveRules')) document.getElementById('kpiActiveRules').innerText = activeCount;
            if (document.getElementById('kpiUnconfiguredOnSale')) document.getElementById('kpiUnconfiguredOnSale').innerText = unconfiguredOnSale.length;
            if (document.getElementById('kpiTotalTeams')) document.getElementById('kpiTotalTeams').innerText = setupAllTeams.length;

            populateSetupProjectSelect();
            renderSetupTeamCheckboxes();
            renderUnconfiguredOnSaleBanner(unconfiguredOnSale);
            renderSetupRulesTable();
        } catch (err) {
            showSetupToast('Error loading Team & Project Setup: ' + err.message, 'error');
        }
    }

    function populateSetupProjectSelect() {
        const select = document.getElementById('setupProjectSelect');
        if (!select) return;

        const currentVal = select.value;
        const configuredMap = {};
        setupAllRules.forEach(r => {
            configuredMap[r.project_id] = r;
        });

        const onSaleProjects = setupAllProjects.filter(p => p.project_sale_status === 'On Sale');
        const otherProjects = setupAllProjects.filter(p => p.project_sale_status !== 'On Sale');

        let html = `<option value="">-- Choose ERP Project --</option>`;

        if (onSaleProjects.length > 0) {
            html += `<optgroup label="🟢 On Sale Projects (${onSaleProjects.length})">`;
            onSaleProjects.forEach(p => {
                const isCfg = !!configuredMap[p.id];
                const badge = isCfg ? '✓ Configured' : '⚠️ Needs Setup';
                html += `<option value="${p.id}">${escapeHtml(p.name)} (${badge})</option>`;
            });
            html += `</optgroup>`;
        }

        if (otherProjects.length > 0) {
            html += `<optgroup label="⚪ Sold Out / Other Projects (${otherProjects.length})">`;
            otherProjects.forEach(p => {
                const isCfg = !!configuredMap[p.id];
                const badge = isCfg ? '✓ Configured' : 'Sold Out';
                html += `<option value="${p.id}">${escapeHtml(p.name)} (${badge})</option>`;
            });
            html += `</optgroup>`;
        }

        select.innerHTML = html;
        if (currentVal) {
            select.value = currentVal;
        }
    }

    function renderSetupTeamCheckboxes() {
        const container = document.getElementById('setupTeamsCheckboxGrid');
        if (!container) return;

        if (!setupAllTeams || setupAllTeams.length === 0) {
            container.innerHTML = `<span class="text-muted small">No teams available</span>`;
            return;
        }

        const query = (document.getElementById('setupTeamSearchInput')?.value || '').toLowerCase().trim();

        let html = '';
        setupAllTeams.forEach(team => {
            const tid = parseInt(team.id);
            const isChecked = setupSelectedTeamIds.has(tid);
            const matchesSearch = !query || String(team.name || '').toLowerCase().includes(query);
            const displayStyle = matchesSearch ? '' : 'display:none;';

            html += `
                <div class="setup-team-pill ${isChecked ? 'checked' : ''}" style="${displayStyle}" data-team-id="${tid}" data-team-name="${escapeHtml(team.name)}" onclick="toggleSetupTeam(${tid})">
                    <span class="pill-check-box"><i class="fa-solid fa-check"></i></span>
                    <span>${escapeHtml(team.name)}</span>
                </div>
            `;
        });

        container.innerHTML = html;
        updateSetupSelectedTeamsBadge();
    }

    function filterSetupTeamPills(query) {
        const q = (query || '').toLowerCase().trim();
        document.querySelectorAll('#setupTeamsCheckboxGrid .setup-team-pill').forEach(pill => {
            const name = (pill.getAttribute('data-team-name') || '').toLowerCase();
            pill.style.display = (!q || name.includes(q)) ? 'inline-flex' : 'none';
        });
    }

    function toggleSetupTeam(teamId) {
        teamId = parseInt(teamId);
        if (setupSelectedTeamIds.has(teamId)) {
            setupSelectedTeamIds.delete(teamId);
        } else {
            setupSelectedTeamIds.add(teamId);
        }
        renderSetupTeamCheckboxes();
    }

    function toggleSetupOptionPill(inputId, pillId, forceState = null) {
        const inputEl = document.getElementById(inputId);
        const pillEl = document.getElementById(pillId);
        if (!inputEl || !pillEl) return;

        const nextState = (forceState !== null) ? !!forceState : !inputEl.checked;
        inputEl.checked = nextState;
        if (nextState) {
            pillEl.classList.add('active');
        } else {
            pillEl.classList.remove('active');
        }
    }

    function toggleAllSetupTeams(selectAll) {
        if (selectAll) {
            setupAllTeams.forEach(t => setupSelectedTeamIds.add(parseInt(t.id)));
        } else {
            setupSelectedTeamIds.clear();
        }
        renderSetupTeamCheckboxes();
    }

    function updateSetupSelectedTeamsBadge() {
        const badge = document.getElementById('setupSelectedTeamsBadge');
        if (!badge) return;
        const count = setupSelectedTeamIds.size;
        badge.innerText = `${count} selected`;
    }

    function renderUnconfiguredOnSaleBanner(unconfiguredList) {
        const banner = document.getElementById('unconfiguredOnSaleBanner');
        const countEl = document.getElementById('unconfiguredCountText');
        const pillsEl = document.getElementById('unconfiguredProjectsPills');
        if (!banner || !pillsEl) return;

        if (!unconfiguredList || unconfiguredList.length === 0) {
            banner.classList.add('d-none');
            return;
        }

        banner.classList.remove('d-none');
        if (countEl) countEl.innerText = unconfiguredList.length;

        pillsEl.innerHTML = unconfiguredList.map(p => `
            <span class="unconfig-proj-pill" onclick="quickSelectProjectForSetup(${p.id})" title="Click to configure ${escapeHtml(p.name)}">
                <i class="fa-solid fa-plus"></i> ${escapeHtml(p.name)}
            </span>
        `).join('');
    }

    function quickSelectProjectForSetup(projectId) {
        const select = document.getElementById('setupProjectSelect');
        if (select) {
            select.value = String(projectId);
            onSetupProjectSelectChange(projectId);
            const card = document.getElementById('setupFormCard');
            if (card) {
                card.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    }

    function generateSuggestedKeywords(projectName) {
        if (!projectName) return '';
        const clean = String(projectName).replace(/\([^)]*\)/g, '').replace(/\s+/g, ' ').trim().toLowerCase();
        const withoutPrefix = clean.replace(/^(the\s+premium\s+|the\s+)/i, '').trim();
        const list = [];
        if (withoutPrefix && withoutPrefix !== clean) {
            list.push(withoutPrefix);
        }
        if (clean) {
            list.push(clean);
        }
        return list.join(', ');
    }

    function onSetupProjectSelectChange(projectId) {
        projectId = parseInt(projectId || 0);
        const kwInput = document.getElementById('setupKeywordsInput');
        const activeToggle = document.getElementById('setupIsActiveToggle');
        const headerTitle = document.getElementById('setupFormHeaderTitle');
        const saveBtnText = document.getElementById('btnSaveProjectRuleText');

        if (!projectId) {
            resetSetupForm();
            return;
        }

        const existingRule = setupAllRules.find(r => parseInt(r.project_id) === projectId);
        const projectObj = setupAllProjects.find(p => parseInt(p.id) === projectId);
        const projectName = existingRule ? existingRule.project_name : (projectObj ? projectObj.name : '');

        if (existingRule) {
            if (kwInput) kwInput.value = existingRule.keywords || generateSuggestedKeywords(projectName);
            toggleSetupOptionPill('setupIsActiveToggle', 'pillIsActiveToggle', parseInt(existingRule.is_active) === 1);
            setupSelectedTeamIds = new Set((existingRule.team_ids || []).map(id => parseInt(id)));
            if (headerTitle) {
                headerTitle.innerHTML = `<i class="fa-solid fa-pen-to-square text-primary me-1"></i> Editing: <span class="text-success">${escapeHtml(projectName)}</span>`;
            }
            if (saveBtnText) {
                saveBtnText.innerText = `Update Setup`;
            }
        } else {
            if (kwInput) kwInput.value = generateSuggestedKeywords(projectName);
            toggleSetupOptionPill('setupIsActiveToggle', 'pillIsActiveToggle', true);
            setupSelectedTeamIds.clear();
            if (headerTitle) {
                headerTitle.innerHTML = `<i class="fa-solid fa-plus-circle text-success me-1"></i> New Setup: <span class="text-primary">${escapeHtml(projectName)}</span>`;
            }
            if (saveBtnText) {
                saveBtnText.innerText = `Save Setup`;
            }
        }

        renderSetupTeamCheckboxes();
    }

    function autoSuggestKeywordsForSelectedProject() {
        const select = document.getElementById('setupProjectSelect');
        const projectId = parseInt(select?.value || 0);
        if (!projectId) {
            showSetupToast('Please select a project first.', 'error');
            return;
        }
        const proj = setupAllProjects.find(p => parseInt(p.id) === projectId);
        if (proj) {
            document.getElementById('setupKeywordsInput').value = generateSuggestedKeywords(proj.name);
        }
    }

    function resetSetupForm() {
        const select = document.getElementById('setupProjectSelect');
        if (select) select.value = '';
        const kwInput = document.getElementById('setupKeywordsInput');
        if (kwInput) kwInput.value = '';
        toggleSetupOptionPill('setupIsActiveToggle', 'pillIsActiveToggle', true);
        toggleSetupOptionPill('setupRematchCommonCheck', 'pillRematchCommon', true);
        const headerTitle = document.getElementById('setupFormHeaderTitle');
        if (headerTitle) {
            headerTitle.innerHTML = `<i class="fa-solid fa-plus-circle text-success me-1"></i> Add / Update Project & Team Allocation`;
        }
        const saveBtnText = document.getElementById('btnSaveProjectRuleText');
        if (saveBtnText) {
            saveBtnText.innerText = `Save Setup`;
        }
        setupSelectedTeamIds.clear();
        renderSetupTeamCheckboxes();
    }

    async function saveProjectSetupRule() {
        const projectId = parseInt(document.getElementById('setupProjectSelect')?.value || 0);
        const keywords = (document.getElementById('setupKeywordsInput')?.value || '').trim();
        const isActive = document.getElementById('setupIsActiveToggle')?.checked ? 1 : 0;
        const rematchCommon = document.getElementById('setupRematchCommonCheck')?.checked ? 1 : 0;
        const teamIds = Array.from(setupSelectedTeamIds);

        if (!projectId) {
            showSetupToast('Please select a project first.', 'error');
            return;
        }
        if (teamIds.length === 0) {
            showSetupToast('Please select at least one Sales Team.', 'error');
            return;
        }

        const btn = document.getElementById('btnSaveProjectRule');
        if (btn) btn.disabled = true;

        try {
            const response = await fetch(`${baseUrl}/lead-automation/project-rules/save`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    project_id: projectId,
                    keywords: keywords,
                    team_ids: teamIds,
                    is_active: isActive,
                    rematch_common: rematchCommon
                })
            });

            const res = await response.json();
            if (res.status === 'success') {
                showSetupToast(res.message || 'Project & Team setup saved!', 'success');
                resetSetupForm();
                await loadProjectSetupRules();
            } else {
                showSetupToast(res.message || 'Failed to save setup', 'error');
            }
        } catch (err) {
            showSetupToast('Error saving setup: ' + err.message, 'error');
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    function toggleExpandTeamsRow(projectId) {
        projectId = parseInt(projectId);
        if (expandedTeamRows.has(projectId)) {
            expandedTeamRows.delete(projectId);
        } else {
            expandedTeamRows.add(projectId);
        }
        renderSetupRulesTable();
    }

    function renderTeamBadgesCompact(projectId, teamNames) {
        if (!teamNames || teamNames.length === 0) {
            return '<span class="text-danger small fw-bold" style="font-size: 11px;">No Teams</span>';
        }

        const maxVisible = 4;
        const isExpanded = expandedTeamRows.has(parseInt(projectId));
        const visibleTeams = isExpanded ? teamNames : teamNames.slice(0, maxVisible);
        const hiddenCount = teamNames.length - visibleTeams.length;

        let html = `<div class="team-alloc-wrap">`;
        visibleTeams.forEach(t => {
            html += `<span class="team-alloc-badge">${escapeHtml(t)}</span>`;
        });

        if (hiddenCount > 0) {
            html += `<span class="team-more-toggle" onclick="toggleExpandTeamsRow(${projectId})" title="Click to view all ${teamNames.length} teams">+${hiddenCount} more</span>`;
        } else if (isExpanded && teamNames.length > maxVisible) {
            html += `<span class="team-more-toggle" onclick="toggleExpandTeamsRow(${projectId})">Show less</span>`;
        }
        html += `</div>`;
        return html;
    }

    function renderSetupRulesTable() {
        const tbody = document.getElementById('setupRulesTableBody');
        const countBadge = document.getElementById('configuredRulesCountBadge');
        if (!tbody) return;

        const searchQ = (document.getElementById('setupTableSearchInput')?.value || '').toLowerCase().trim();
        const statusFilter = document.getElementById('setupTableStatusFilter')?.value || '';

        const filtered = setupAllRules.filter(r => {
            if (statusFilter === 'On Sale' && r.project_sale_status !== 'On Sale') return false;
            if (statusFilter === 'Sold Out' && r.project_sale_status === 'On Sale') return false;
            if (statusFilter === 'active' && parseInt(r.is_active) !== 1) return false;
            if (statusFilter === 'paused' && parseInt(r.is_active) === 1) return false;

            if (searchQ) {
                const hay = `${r.project_name} ${r.keywords} ${(r.team_names || []).join(' ')}`.toLowerCase();
                if (!hay.includes(searchQ)) return false;
            }
            return true;
        });

        if (countBadge) {
            countBadge.innerText = `${filtered.length} of ${setupAllRules.length}`;
        }

        if (filtered.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        No matching project configurations found.
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = filtered.map(r => {
            const isOnSale = r.project_sale_status === 'On Sale';
            const isActive = parseInt(r.is_active) === 1;
            const kwBadges = (r.keywords_list || []).map(k => `<span class="kw-badge">${escapeHtml(k)}</span>`).join(' ');
            const teamBadgesHtml = renderTeamBadgesCompact(r.project_id, r.team_names || []);

            return `
                <tr style="${!isActive ? 'opacity: 0.6; background: #f8fafc;' : ''}">
                    <td>
                        <div class="fw-semibold text-dark" style="font-size: 12px;">${escapeHtml(r.project_name)}</div>
                    </td>
                    <td>
                        <span class="badge ${isOnSale ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border'}" style="font-size: 10px; font-weight: 600; padding: 2px 6px;">
                            ${escapeHtml(r.project_sale_status || 'On Sale')}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex flex-wrap gap-1">${kwBadges || '<span class="text-muted small">None</span>'}</div>
                    </td>
                    <td>${teamBadgesHtml}</td>
                    <td class="text-center">
                        <label class="clean-switch" title="${isActive ? 'Active (Click to pause)' : 'Paused (Click to activate)'}">
                            <input type="checkbox" ${isActive ? 'checked' : ''} onchange="toggleProjectSetupActive(${r.project_id}, this.checked)">
                            <span class="clean-switch-slider"></span>
                        </label>
                    </td>
                    <td class="text-end">
                        <div class="d-inline-flex align-items-center gap-1">
                            <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size: 10.5px; height: 24px;" onclick="quickSelectProjectForSetup(${r.project_id})" title="Edit Teams & Keywords">
                                <i class="fa-solid fa-pen" style="font-size: 9.5px;"></i> Edit
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" style="font-size: 10.5px; height: 24px;" onclick="deleteProjectSetupRule(${r.project_id}, '${escapeJs(r.project_name)}')" title="Remove from Automation">
                                <i class="fa-solid fa-trash-can" style="font-size: 9.5px;"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    async function toggleProjectSetupActive(projectId, isChecked) {
        try {
            const response = await fetch(`${baseUrl}/lead-automation/project-rules/toggle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    project_id: projectId,
                    is_active: isChecked ? 1 : 0
                })
            });
            const res = await response.json();
            if (res.status === 'success') {
                showSetupToast(res.message, 'info');
                await loadProjectSetupRules();
            } else {
                showSetupToast(res.message || 'Toggle failed', 'error');
            }
        } catch (err) {
            showSetupToast('Error: ' + err.message, 'error');
        }
    }

    async function deleteProjectSetupRule(projectId, projectName) {
        if (!confirm(`Are you sure you want to remove "${projectName}" from Lead Automation setup?`)) {
            return;
        }
        try {
            const response = await fetch(`${baseUrl}/lead-automation/project-rules/delete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ project_id: projectId })
            });
            const res = await response.json();
            if (res.status === 'success') {
                showSetupToast(res.message || 'Removed project rule.', 'info');
                await loadProjectSetupRules();
            } else {
                showSetupToast(res.message || 'Delete failed', 'error');
            }
        } catch (err) {
            showSetupToast('Error: ' + err.message, 'error');
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text).replace(/[&<>"']/g, m => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        })[m]);
    }

    function escapeJs(str) {
        return (str || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
    }

    function showSetupToast(message, type = 'info') {
        let container = document.getElementById('setupToastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'setupToastContainer';
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
