<?php
/**
 * Lead Intake & Automation Distribution Dashboard
 * TPHL ERP - Digital Marketing Role 10 & 11
 */
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
    .allocation-section {
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 14px;
        margin-bottom: 1rem;
    }

    @media (max-width: 992px) {
        .allocation-section { grid-template-columns: 1fr; }
    }

    .team-allocation-box {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 14px 16px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    }

    .teams-horizontal-scroll {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 8px;
        max-height: 155px;
        overflow-y: auto;
        padding-right: 4px;
    }

    .team-metric-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: var(--radius-sm);
        padding: 8px 10px;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .team-metric-card:hover, .team-metric-card.active {
        background: #ffffff;
        border-color: #087d59;
        box-shadow: 0 2px 6px rgba(8, 125, 89, 0.12);
    }

    .team-card-name {
        font-size: 11.5px;
        font-weight: 700;
        color: var(--text-heading);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .team-card-assigned {
        font-size: 12px;
        font-weight: 700;
        color: #087d59;
        margin: 2px 0 1px;
    }

    .team-card-sub {
        font-size: 10px;
        color: #d97706;
        font-weight: 600;
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
        overflow: hidden;
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
        padding: 8px 18px;
        border-bottom: 1px solid #f1f5f9;
        background: #fafafa;
        flex-wrap: wrap;
        gap: 8px;
    }

    .table-responsive {
        width: 100%;
        overflow-x: auto;
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
            <a href="<?= htmlspecialchars($sheet_url) ?>" target="_blank" class="small fw-semibold text-decoration-none text-success d-inline-flex align-items-center gap-1 ms-1" title="Open Google Sheet">
                View live sheet <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 10px;"></i>
            </a>
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
                <a href="javascript:void(0)" class="small fw-semibold text-success text-decoration-none" onclick="filterByTeam('')">Show all teams &rarr;</a>
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
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <div style="width: 240px;">
                    <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search name, phone or ID..." oninput="debounceSearch()">
                </div>

                <select id="teamFilterDropdown" class="form-select form-select-sm" style="width: 180px;" onchange="onTeamDropdownChange(this.value)">
                    <option value="">All Sales Teams</option>
                    <?php foreach ($teams as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="d-flex align-items-center gap-2">
                <span id="selectionCountText" class="small fw-bold text-success" style="display:none;">0 selected</span>
                <button id="btnBulkApprove" class="btn btn-success btn-sm fw-bold px-3" onclick="bulkApproveSelected()" disabled>
                    <i class="fa-solid fa-check me-1"></i> Assign & Approve Leads
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
                        <th style="min-width: 110px; text-align: right;">Action</th>
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

<!-- Toast Container -->
<div id="toastContainer"></div>

<script>
    const baseUrl = "<?= $GLOBALS['base_url'] ?>";
    let currentTabStatus = '0'; // Default: Pending Approval (distribute = 0)
    let currentTeamId = '';
    let currentPage = 1;
    let limit = 50;
    let totalCount = 0;
    let searchTimeout = null;
    let selectedLeadIds = new Set();

    document.addEventListener('DOMContentLoaded', () => {
        loadLeads();
    });

    // Trigger Scrape from Google Sheet
    async function triggerScrape() {
        const btn = document.getElementById('btnScrape');
        const spinner = document.getElementById('btnScrapeSpinner');
        const icon = document.getElementById('btnScrapeIcon');

        btn.disabled = true;
        spinner.classList.remove('d-none');
        icon.classList.add('d-none');

        showToast('Connecting to Google Sheet & parsing with Gemini AI...', 'info');

        try {
            const formData = new FormData();
            formData.append('sheet_url', '<?= addslashes($sheet_url) ?>');

            const response = await fetch(`${baseUrl}/lead-automation/scrape`, {
                method: 'POST',
                body: formData
            });

            const res = await response.json();

            if (res.status === 'success') {
                showToast(res.message, 'success');
                loadLeads();
            } else {
                showToast('Sync Failed: ' + (res.message || 'Unknown error'), 'error');
            }
        } catch (err) {
            showToast('Network error while syncing: ' + err.message, 'error');
        } finally {
            btn.disabled = false;
            spinner.classList.add('d-none');
            icon.classList.remove('d-none');
        }
    }

    function setTabFilter(status) {
        currentTabStatus = status;
        currentPage = 1;
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        if (status === '') document.getElementById('tabAll').classList.add('active');
        else if (status === '0') document.getElementById('tabPending').classList.add('active');
        else if (status === '1') document.getElementById('tabApproved').classList.add('active');
        loadLeads();
    }

    function filterByTeam(teamId) {
        currentTeamId = teamId;
        currentPage = 1;
        document.getElementById('teamFilterDropdown').value = teamId;
        updateApproveAllButtonLabel();
        loadLeads();
    }

    function onTeamDropdownChange(teamId) {
        currentTeamId = teamId;
        currentPage = 1;
        updateApproveAllButtonLabel();
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

    function debounceSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentPage = 1;
            loadLeads();
        }, 350);
    }

    function changePage(direction) {
        currentPage += direction;
        if (currentPage < 1) currentPage = 1;
        loadLeads();
    }

    // Main AJAX Load Function
    async function loadLeads() {
        const searchVal = document.getElementById('searchInput').value.trim();
        const offset = (currentPage - 1) * limit;

        const params = new URLSearchParams({
            distribute: currentTabStatus,
            team_id   : currentTeamId,
            search    : searchVal,
            limit     : limit,
            offset    : offset
        });

        try {
            const response = await fetch(`${baseUrl}/lead-automation/leads-ajax?${params.toString()}`);
            const result = await response.json();

            if (result.status === 'success') {
                const data = result.data;
                totalCount = data.total_count || 0;
                renderStats(data.stats, data.teams);
                renderTeamCards(data.teams);
                renderTable(data.leads, totalCount);
                updatePaginationControls();
            } else {
                showToast(result.message || 'Error fetching leads', 'error');
            }
        } catch (err) {
            console.error('Fetch error:', err);
            showToast('Failed to load leads from server', 'error');
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

    function renderTeamCards(teams) {
        const container = document.getElementById('teamsContainer');
        if (!teams || teams.length === 0) {
            container.innerHTML = '<span class="text-muted small">No teams configured</span>';
            return;
        }

        let cardsHtml = '';
        teams.forEach(team => {
            const isSelected = String(currentTeamId) === String(team.id);
            cardsHtml += `
                <div class="team-metric-card ${isSelected ? 'active' : ''}" onclick="filterByTeam('${team.id}')">
                    <div class="team-card-name" title="${escapeHtml(team.name)}">${escapeHtml(team.name)}</div>
                    <div class="team-card-assigned">${team.total_leads || 0} leads</div>
                    <div class="team-card-sub">${team.pending_leads || 0} pending</div>
                </div>
            `;
        });
        container.innerHTML = cardsHtml;
    }

    function renderTable(leads, count) {
        const tbody = document.getElementById('leadsTableBody');
        document.getElementById('paginationText').innerText = `Showing ${leads.length} of ${count} leads`;

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
            const teamName = lead.team_name ? lead.team_name : (lead.team_id ? 'Team #' + lead.team_id : 'Unassigned');
            const projectName = lead.project_name ? lead.project_name : (lead.project_id ? 'Project #' + lead.project_id : 'Unassigned');

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
                        <div class="fw-semibold text-dark">${escapeHtml(projectName)}</div>
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
                        <div class="fw-bold small text-dark">${escapeHtml(teamName)}</div>
                        ${isApproved ? `
                            <span class="tag-approved"><i class="fa-solid fa-check me-1"></i> Approved</span>
                        ` : `
                            <span class="tag-pending"><i class="fa-regular fa-clock me-1"></i> Pending</span>
                        `}
                    </td>
                    <td class="text-end">
                        ${isApproved ? `
                            <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 11px;" onclick="unapproveLead(${lead.id})">
                                Undo
                            </button>
                        ` : `
                            <button type="button" class="btn btn-success btn-sm py-0 px-2 fw-semibold" style="font-size: 11px;" onclick="approveSingleLead(${lead.id})">
                                Approve &rarr;
                            </button>
                        `}
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
        const btn = document.getElementById('btnBulkApprove');
        const label = document.getElementById('selectionCountText');

        if (count > 0) {
            btn.disabled = false;
            btn.innerHTML = `<i class="fa-solid fa-check me-1"></i> Assign & Approve ${count} Leads`;
            label.style.display = 'inline';
            label.innerText = `${count} selected`;
        } else {
            btn.disabled = true;
            btn.innerHTML = `<i class="fa-solid fa-check me-1"></i> Assign & Approve Leads`;
            label.style.display = 'none';
        }

        const allCheckboxes = document.querySelectorAll('.lead-cb');
        const selectAll = document.getElementById('selectAllCheckbox');
        if (allCheckboxes.length > 0) {
            selectAll.checked = Array.from(allCheckboxes).every(cb => cb.checked);
        } else {
            selectAll.checked = false;
        }
    }

    function updatePaginationControls() {
        const totalPages = Math.ceil(totalCount / limit) || 1;
        document.getElementById('pageNumberText').innerText = `Page ${currentPage} of ${totalPages}`;
        document.getElementById('btnPrevPage').disabled = (currentPage <= 1);
        document.getElementById('btnNextPage').disabled = (currentPage >= totalPages);
    }

    async function approveSingleLead(id) {
        try {
            const response = await fetch(`${baseUrl}/lead-automation/approve`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ lead_ids: [id] })
            });
            const res = await response.json();
            if (res.status === 'success') {
                showToast(`Lead #${id} distributed & approved!`, 'success');
                selectedLeadIds.delete(id);
                loadLeads();
            } else {
                showToast(res.message || 'Approval failed', 'error');
            }
        } catch (err) {
            showToast('Error: ' + err.message, 'error');
        }
    }

    async function bulkApproveSelected() {
        if (selectedLeadIds.size === 0) return;
        const ids = Array.from(selectedLeadIds);

        try {
            const response = await fetch(`${baseUrl}/lead-automation/approve`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ lead_ids: ids })
            });
            const res = await response.json();
            if (res.status === 'success') {
                showToast(res.message, 'success');
                selectedLeadIds.clear();
                loadLeads();
            } else {
                showToast(res.message || 'Approval failed', 'error');
            }
        } catch (err) {
            showToast('Error: ' + err.message, 'error');
        }
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
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    all_pending: true,
                    team_id: currentTeamId || null
                })
            });
            const approveRes = await approveResp.json();
            if (approveRes.status === 'success') {
                showToast(approveRes.message || 'Leads approved successfully!', 'success');
                selectedLeadIds.clear();
                loadLeads();
            } else {
                showToast(approveRes.message || 'Approval failed', 'error');
            }
        } catch (err) {
            showToast('Error: ' + err.message, 'error');
        }
    }

    async function unapproveLead(id) {
        try {
            const response = await fetch(`${baseUrl}/lead-automation/unapprove`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ lead_ids: [id] })
            });
            const res = await response.json();
            if (res.status === 'success') {
                showToast(`Lead #${id} reverted back to Pending.`, 'info');
                loadLeads();
            }
        } catch (err) {
            showToast('Error: ' + err.message, 'error');
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

    function showToast(message, type = 'info') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `custom-toast ${type === 'success' ? 'toast-success' : (type === 'error' ? 'toast-error' : '')}`;
        toast.innerHTML = `
            <span>${escapeHtml(message)}</span>
        `;
        container.appendChild(toast);
        setTimeout(() => {
            toast.style.transition = 'opacity 0.3s ease';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    }
</script>
