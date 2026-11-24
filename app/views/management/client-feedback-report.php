<?php
$page_title      = $page_title ?? 'Client Feedback Report';
$page            = 'Management';
$show_breadcrumb = true;
$base_url        = $base_url ?? ($GLOBALS['base_url'] ?? '');
?>

<style>
:root {
    --cf-brand: #0d5c3a;
    --cf-brand-hover: #09452b;
    --cf-brand-soft: #e8f5e9;
    --cf-brand-border: #bbf7d0;
    --cf-border: #e2e8f0;
    --cf-text: #0f172a;
    --cf-muted: #64748b;
}

.cf-wrap {
    width: 100%;
    max-width: 100%;
    padding: 16px 18px 36px;
    box-sizing: border-box;
    overflow-x: clip;
}

.cf-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;
}
.cf-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}
.cf-header-icon {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: var(--cf-brand-soft);
    color: var(--cf-brand);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 19px;
    flex-shrink: 0;
}
.cf-header-title {
    margin: 0;
    font-size: 19px;
    font-weight: 700;
    color: var(--cf-brand);
}
.cf-header-sub {
    margin: 0;
    font-size: 12.5px;
    color: var(--cf-muted);
}

.cf-card {
    background: #ffffff;
    border: 1px solid var(--cf-border);
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    min-width: 0;
}

/* KPI Grid (5 cards) */
.cf-kpi-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 16px;
}
.cf-kpi {
    background: #ffffff;
    border: 1px solid var(--cf-border);
    border-radius: 11px;
    padding: 14px 15px;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: transform 0.12s ease, border-color 0.12s ease;
}
.cf-kpi.clickable {
    cursor: pointer;
}
.cf-kpi.clickable:hover {
    border-color: var(--cf-brand);
    transform: translateY(-1px);
}
.cf-kpi-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}
.cf-kpi-icon.green  { background: #e8f5e9; color: #0d5c3a; }
.cf-kpi-icon.teal   { background: #f0fdfa; color: #0f766e; }
.cf-kpi-icon.blue   { background: #eff6ff; color: #1d4ed8; }
.cf-kpi-icon.red    { background: #fef2f2; color: #dc2626; }
.cf-kpi-icon.amber  { background: #fffbeb; color: #b45309; }
.cf-kpi-label {
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: var(--cf-muted);
}
.cf-kpi-val {
    font-size: 20px;
    font-weight: 700;
    color: var(--cf-text);
    line-height: 1.25;
}
.cf-kpi-sub {
    font-size: 11px;
    color: var(--cf-muted);
}

/* 5-Area Stacked Breakdown */
.cf-breakdown-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 12px;
}
.cf-area-box {
    border: 1px solid #f1f5f9;
    background: #f8fafc;
    border-radius: 10px;
    padding: 12px;
}
.cf-area-title {
    font-size: 12px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 6px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 4px;
}
.cf-stacked-bar {
    height: 10px;
    border-radius: 99px;
    overflow: hidden;
    display: flex;
    background: #e2e8f0;
    margin-bottom: 8px;
}
.cf-seg-exc { background: #15803d; }
.cf-seg-gd  { background: #0ea5e9; }
.cf-seg-avg { background: #f59e0b; }
.cf-seg-pr  { background: #dc2626; }
.cf-area-counts {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 3px 8px;
    font-size: 11px;
    color: #475569;
}

/* Filter Grid */
.cf-filter-grid {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 10px 12px;
    align-items: end;
}
.cf-filter-field { min-width: 0; }
.cf-filter-field.span-2 { grid-column: span 2; }
.cf-filter-label {
    display: block;
    font-size: 11.5px;
    font-weight: 600;
    color: #475569;
    margin-bottom: 4px;
}
.cf-wrap .form-control-sm,
.cf-wrap .form-select-sm {
    min-height: 34px;
    font-size: 12.5px;
    border-color: #cbd5e1;
    border-radius: 7px;
}
.cf-wrap .form-control-sm:focus,
.cf-wrap .form-select-sm:focus {
    border-color: var(--cf-brand);
    box-shadow: 0 0 0 3px rgba(13, 92, 58, 0.12);
}

.cf-btn-brand {
    background: var(--cf-brand);
    border-color: var(--cf-brand);
    color: #ffffff;
}
.cf-btn-brand:hover {
    background: var(--cf-brand-hover);
    border-color: var(--cf-brand-hover);
    color: #ffffff;
}

/* Table */
.cf-table {
    width: 100%;
    font-size: 12.5px;
    border-collapse: collapse;
    margin-bottom: 0;
}
.cf-table thead th {
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
.cf-table tbody td {
    padding: 10px 12px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.cf-table tbody tr:hover {
    background: #f8fafc;
}

.cf-rating-pill {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 99px;
    font-size: 11px;
    font-weight: 600;
    text-transform: capitalize;
    white-space: nowrap;
}
.cf-r-excellent { background: #dcfce7; color: #166534; }
.cf-r-good      { background: #e0f2fe; color: #075985; }
.cf-r-average   { background: #fef3c7; color: #92400e; }
.cf-r-poor      { background: #fee2e2; color: #991b1b; }

.cf-mini-dots {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}
.cf-mini-dot {
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 5px;
    font-weight: 600;
}

.cf-act-btn {
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
}
.cf-act-btn:hover {
    background: var(--cf-brand-soft);
    border-color: var(--cf-brand-border);
    color: var(--cf-brand);
}
.cf-act-btn.wa {
    width: 30px;
    padding: 0;
    border-color: #bbf7d0;
    background: #f0fdf4;
    color: #15803d;
}
.cf-act-btn.wa:hover {
    background: #15803d;
    border-color: #15803d;
    color: #ffffff;
}

@media (max-width: 1279px) {
    .cf-kpi-grid,
    .cf-breakdown-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .cf-filter-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (max-width: 767px) {
    .cf-kpi-grid,
    .cf-breakdown-grid,
    .cf-filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .cf-filter-field.span-2 { grid-column: span 2; }
}
@media (max-width: 480px) {
    .cf-kpi-grid,
    .cf-breakdown-grid,
    .cf-filter-grid { grid-template-columns: minmax(0, 1fr); }
    .cf-filter-field.span-2 { grid-column: span 1; }
}
</style>

<div class="cf-wrap">
    <!-- Header -->
    <div class="cf-header">
        <div class="cf-header-left">
            <div class="cf-header-icon">
                <i class="fa-solid fa-star-half-stroke"></i>
            </div>
            <div>
                <h4 class="cf-header-title">Client Feedback Report</h4>
                <p class="cf-header-sub">Reception QR &amp; website visit ratings, service quality breakdown, and Management follow-up tracker</p>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" id="cfBtnApiInfo" class="btn btn-sm btn-light border text-dark">
                <i class="fa-solid fa-code text-success me-1"></i>API Endpoint
            </button>
            <a href="#" id="cfBtnExportCsv" class="btn btn-sm cf-btn-brand">
                <i class="fa-solid fa-file-csv me-1"></i>Export CSV
            </a>
        </div>
    </div>

    <!-- 5 Executive KPI Cards -->
    <div class="cf-kpi-grid">
        <div class="cf-kpi">
            <div class="cf-kpi-icon green"><i class="fa-solid fa-comments"></i></div>
            <div>
                <div class="cf-kpi-label">Total Responses</div>
                <div class="cf-kpi-val" id="cfKpiTotal">0</div>
                <div class="cf-kpi-sub" id="cfKpiToday">Today: 0</div>
            </div>
        </div>
        <div class="cf-kpi">
            <div class="cf-kpi-icon teal"><i class="fa-solid fa-award"></i></div>
            <div>
                <div class="cf-kpi-label">Overall Score</div>
                <div class="cf-kpi-val" id="cfKpiScore">0.0 / 4</div>
                <div class="cf-kpi-sub" id="cfKpiPosPct">0% Positive</div>
            </div>
        </div>
        <div class="cf-kpi">
            <div class="cf-kpi-icon blue"><i class="fa-solid fa-thumbs-up"></i></div>
            <div>
                <div class="cf-kpi-label">Excellent / Good</div>
                <div class="cf-kpi-val text-success" id="cfKpiPositive">0</div>
                <div class="cf-kpi-sub">Overall Experience</div>
            </div>
        </div>
        <div class="cf-kpi clickable" id="cfCardPoor" title="Click to filter feedback with any Poor rating">
            <div class="cf-kpi-icon red"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div>
                <div class="cf-kpi-label">Needs Attention</div>
                <div class="cf-kpi-val text-danger" id="cfKpiPoor">0</div>
                <div class="cf-kpi-sub">Has Poor rating(s)</div>
            </div>
        </div>
        <div class="cf-kpi clickable" id="cfCardCallback" title="Click to filter visitors who requested contact">
            <div class="cf-kpi-icon amber"><i class="fa-solid fa-phone-volume"></i></div>
            <div>
                <div class="cf-kpi-label">Callback Requested</div>
                <div class="cf-kpi-val" style="color:#b45309;" id="cfKpiCallback">0</div>
                <div class="cf-kpi-sub" id="cfKpiPending">Pending review: 0</div>
            </div>
        </div>
    </div>

    <!-- 5-Category Rating Breakdown -->
    <div class="cf-card p-3 mb-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div class="fw-bold text-dark" style="font-size:13.5px;">
                <i class="fa-solid fa-chart-bar me-1" style="color:var(--cf-brand);"></i>Service Area Breakdown (5 Rating Categories)
            </div>
            <div class="d-flex flex-wrap gap-3 small text-muted" style="font-size:11.5px;">
                <span><span class="d-inline-block rounded-circle me-1" style="width:9px;height:9px;background:#15803d;"></span>Excellent</span>
                <span><span class="d-inline-block rounded-circle me-1" style="width:9px;height:9px;background:#0ea5e9;"></span>Good</span>
                <span><span class="d-inline-block rounded-circle me-1" style="width:9px;height:9px;background:#f59e0b;"></span>Average</span>
                <span><span class="d-inline-block rounded-circle me-1" style="width:9px;height:9px;background:#dc2626;"></span>Poor</span>
            </div>
        </div>
        <div class="cf-breakdown-grid" id="cfBreakdownGrid">
            <div class="text-muted small py-2">Loading category breakdown...</div>
        </div>
    </div>

    <!-- Smart Filter Bar -->
    <div class="cf-card p-3 mb-3">
        <div class="cf-filter-grid">
            <div class="cf-filter-field">
                <label class="cf-filter-label" for="cfDatePreset">Date Range</label>
                <select id="cfDatePreset" class="form-select form-select-sm">
                    <option value="">All Time</option>
                    <option value="today">Today</option>
                    <option value="7d">Last 7 Days</option>
                    <option value="30d">Last 30 Days</option>
                    <option value="this_month">This Month</option>
                    <option value="custom">Custom Range</option>
                </select>
            </div>
            <div class="cf-filter-field">
                <label class="cf-filter-label" for="cfOverallRating">Rating Filter</label>
                <select id="cfOverallRating" class="form-select form-select-sm">
                    <option value="">All Ratings</option>
                    <option value="excellent">Overall: Excellent</option>
                    <option value="good">Overall: Good</option>
                    <option value="average">Overall: Average</option>
                    <option value="poor">Overall: Poor</option>
                    <option value="any_poor">Any Area Rated Poor</option>
                </select>
            </div>
            <div class="cf-filter-field">
                <label class="cf-filter-label" for="cfMayContact">Contact Permission</label>
                <select id="cfMayContact" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="1">Yes (May Contact)</option>
                    <option value="0">No</option>
                </select>
            </div>
            <div class="cf-filter-field">
                <label class="cf-filter-label" for="cfFollowupStatus">Follow-up Status</label>
                <select id="cfFollowupStatus" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="reviewed">Reviewed</option>
                    <option value="contacted">Contacted</option>
                    <option value="resolved">Resolved</option>
                </select>
            </div>
            <div class="cf-filter-field">
                <label class="cf-filter-label" for="cfSearch">Search</label>
                <input type="text" id="cfSearch" class="form-control form-control-sm" placeholder="Name, phone, comment...">
            </div>
            <div class="cf-filter-field">
                <label class="cf-filter-label">&nbsp;</label>
                <div class="d-flex gap-1">
                    <button type="button" id="cfBtnApply" class="btn btn-sm cf-btn-brand flex-grow-1">
                        <i class="fa-solid fa-filter me-1"></i>Filter
                    </button>
                    <button type="button" id="cfBtnReset" class="btn btn-sm btn-light border" title="Reset Filters">
                        <i class="fa-solid fa-rotate-left"></i>
                    </button>
                </div>
            </div>

            <div class="cf-filter-field d-none" id="cfCustomFromWrap">
                <label class="cf-filter-label" for="cfFromDate">From Date</label>
                <input type="date" id="cfFromDate" class="form-control form-control-sm">
            </div>
            <div class="cf-filter-field d-none" id="cfCustomToWrap">
                <label class="cf-filter-label" for="cfToDate">To Date</label>
                <input type="date" id="cfToDate" class="form-control form-control-sm">
            </div>
        </div>
        <div class="mt-2 pt-2 border-top d-flex align-items-center justify-content-between">
            <div class="form-check small mb-0">
                <input class="form-check-input" type="checkbox" id="cfHasComment">
                <label class="form-check-label text-muted" for="cfHasComment">Show only feedback with written comments</label>
            </div>
            <small class="text-muted" id="cfResultCountLabel">0 record(s)</small>
        </div>
    </div>

    <!-- Feedback Table -->
    <div class="cf-card overflow-hidden">
        <div class="table-responsive">
            <table class="cf-table">
                <thead>
                    <tr>
                        <th style="width:45px;">#</th>
                        <th>Date &amp; Time</th>
                        <th>Visitor</th>
                        <th>Area Ratings (B / P / R / E)</th>
                        <th>Overall</th>
                        <th>Comment</th>
                        <th>Contact?</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="cfTableBody">
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-spinner fa-spin me-1"></i>Loading feedback report...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Feedback Detail & Follow-up Modal -->
<div class="modal fade" id="cfDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius:14px; overflow:hidden;">
            <div class="modal-header py-3 px-4 text-white border-0" style="background:linear-gradient(135deg, #0d5c3a, #15803d);">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-star-half-stroke"></i>
                    <h6 class="modal-title mb-0 fw-bold" id="cfDetailModalTitle">Feedback Details</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="cfDetailContent"></div>
                <hr class="my-3">
                <input type="hidden" id="cfEditId" value="">
                <div class="fw-bold small text-dark mb-2">Management Follow-up Update</div>
                <div style="display:grid; grid-template-columns: 180px 1fr; gap:10px;" class="mb-2">
                    <div>
                        <label class="cf-filter-label" for="cfEditStatus">Status</label>
                        <select id="cfEditStatus" class="form-select form-select-sm">
                            <option value="pending">Pending</option>
                            <option value="reviewed">Reviewed</option>
                            <option value="contacted">Contacted</option>
                            <option value="resolved">Resolved</option>
                        </select>
                    </div>
                    <div>
                        <label class="cf-filter-label" for="cfEditNote">Internal Note</label>
                        <input type="text" id="cfEditNote" class="form-control form-control-sm" placeholder="e.g. Called client & apologized for wait time...">
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2 px-4 bg-light border-top">
                <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Close</button>
                <button type="button" id="cfBtnSaveFollowup" class="btn btn-sm cf-btn-brand px-4 fw-semibold">
                    <i class="fa-solid fa-check me-1"></i>Save Status
                </button>
            </div>
        </div>
    </div>
</div>

<!-- API Integration Info Modal -->
<div class="modal fade" id="cfApiInfoModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:14px; overflow:hidden;">
            <div class="modal-header py-3 px-4 text-white border-0" style="background:linear-gradient(135deg, #0d5c3a, #15803d);">
                <h6 class="modal-title mb-0 fw-bold"><i class="fa-solid fa-code me-2"></i>Website Feedback Submit API</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 small">
                <div class="mb-2"><strong>Endpoint:</strong> <code>POST <?= htmlspecialchars($base_url) ?>/api/client-feedback/submit</code></div>
                <div class="mb-2 text-muted">Accepts <code>application/json</code> or form-data from <code>dpremiumhomes.com/client-feedback</code>:</div>
<pre class="bg-light border rounded p-3 mb-0" style="font-size:12px;">{
  "behaviour_attitude": "excellent",
  "professionalism_courtesy": "good",
  "response_time_speed": "average",
  "office_environment": "excellent",
  "overall_experience": "good",
  "full_name": "Optional Name",
  "mobile_number": "01XXXXXXXXX",
  "comments": "Optional comments or suggestions",
  "may_contact": true
}</pre>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const baseUrl = <?= json_encode($base_url) ?>;
    let feedbackRows = [];
    let debounceTimer = null;

    function escHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    }

    function ratingPill(val) {
        const v = String(val || '').toLowerCase();
        return `<span class="cf-rating-pill cf-r-${escHtml(v)}">${escHtml(v || '—')}</span>`;
    }

    function statusBadge(st) {
        const map = {
            pending: 'bg-warning text-dark',
            reviewed: 'bg-info text-dark',
            contacted: 'bg-primary',
            resolved: 'bg-success'
        };
        return `<span class="badge ${map[st] || 'bg-secondary'} text-capitalize">${escHtml(st || 'pending')}</span>`;
    }

    function collectFilters() {
        const preset = document.getElementById('cfDatePreset').value;
        return {
            date_preset: preset === 'custom' ? '' : preset,
            from_date: preset === 'custom' ? document.getElementById('cfFromDate').value : '',
            to_date: preset === 'custom' ? document.getElementById('cfToDate').value : '',
            overall_rating: document.getElementById('cfOverallRating').value,
            may_contact: document.getElementById('cfMayContact').value,
            followup_status: document.getElementById('cfFollowupStatus').value,
            has_comment: document.getElementById('cfHasComment').checked ? '1' : '0',
            search: document.getElementById('cfSearch').value.trim()
        };
    }

    function updateExportLink() {
        const params = new URLSearchParams(collectFilters());
        document.getElementById('cfBtnExportCsv').href = baseUrl + '/client-feedback-report/export?' + params.toString();
    }

    function renderBreakdown(list) {
        const wrap = document.getElementById('cfBreakdownGrid');
        if (!list || !list.length) {
            wrap.innerHTML = '<div class="text-muted small">No rating data yet.</div>';
            return;
        }
        wrap.innerHTML = list.map(b => {
            const tot = Math.max(1, b.total || 0);
            const pExc = b.total ? Math.round((b.excellent / tot) * 100) : 0;
            const pGd  = b.total ? Math.round((b.good / tot) * 100) : 0;
            const pAvg = b.total ? Math.round((b.average / tot) * 100) : 0;
            const pPr  = b.total ? Math.round((b.poor / tot) * 100) : 0;
            return `
                <div class="cf-area-box">
                    <div class="cf-area-title">
                        <span>${escHtml(b.label)}</span>
                        <span class="badge bg-white text-dark border">${b.avg_score}/4</span>
                    </div>
                    <div class="cf-stacked-bar" title="Excellent: ${pExc}% | Good: ${pGd}% | Average: ${pAvg}% | Poor: ${pPr}%">
                        <div class="cf-seg-exc" style="width:${pExc}%"></div>
                        <div class="cf-seg-gd"  style="width:${pGd}%"></div>
                        <div class="cf-seg-avg" style="width:${pAvg}%"></div>
                        <div class="cf-seg-pr"  style="width:${pPr}%"></div>
                    </div>
                    <div class="cf-area-counts">
                        <span>Exc: <strong>${b.excellent}</strong> (${pExc}%)</span>
                        <span>Good: <strong>${b.good}</strong> (${pGd}%)</span>
                        <span>Avg: <strong>${b.average}</strong> (${pAvg}%)</span>
                        <span class="${b.poor > 0 ? 'text-danger fw-semibold' : ''}">Poor: <strong>${b.poor}</strong> (${pPr}%)</span>
                    </div>
                </div>
            `;
        }).join('');
    }

    function renderTable() {
        const tbody = document.getElementById('cfTableBody');
        document.getElementById('cfResultCountLabel').textContent = `${feedbackRows.length} record(s)`;

        if (!feedbackRows.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                        <i class="fa-regular fa-face-smile fs-3 d-block mb-2 text-secondary"></i>
                        No client feedback records match your filters yet.
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = feedbackRows.map((r, idx) => {
            const visitor = r.full_name ? escHtml(r.full_name) : '<span class="text-muted fst-italic">Anonymous</span>';
            const phoneHtml = r.mobile_number ? `<div class="text-muted" style="font-size:11px;">${escHtml(r.mobile_number)}</div>` : '';
            const cmtPreview = r.comments
                ? escHtml(r.comments.substring(0, 75) + (r.comments.length > 75 ? '...' : ''))
                : '<span class="text-muted">—</span>';

            const cleanDigits = String(r.mobile_number || '').replace(/[^\d]/g, '');
            const waDigits = (cleanDigits.length === 11 && cleanDigits.startsWith('01')) ? ('88' + cleanDigits) : cleanDigits;
            const waBtn = (r.may_contact && waDigits.length >= 10)
                ? `<a href="https://wa.me/${waDigits}" target="_blank" rel="noopener" class="cf-act-btn wa" title="WhatsApp visitor"><i class="fa-brands fa-whatsapp"></i></a>`
                : '';

            const mini = [
                { k: 'B', full: 'Behaviour & Attitude', v: r.behaviour_attitude },
                { k: 'P', full: 'Professionalism & Courtesy', v: r.professionalism_courtesy },
                { k: 'R', full: 'Response Time / Speed', v: r.response_time_speed },
                { k: 'E', full: 'Office Environment', v: r.office_environment }
            ].map(m => `<span class="cf-mini-dot cf-r-${escHtml(m.v)}" title="${m.full}: ${m.v}">${m.k}: ${m.v.charAt(0).toUpperCase()}</span>`).join('');

            return `
                <tr>
                    <td class="text-muted">${idx + 1}</td>
                    <td class="text-nowrap small">${escHtml(r.created_at)}</td>
                    <td><div class="fw-semibold text-dark">${visitor}</div>${phoneHtml}</td>
                    <td><div class="cf-mini-dots">${mini}</div></td>
                    <td>${ratingPill(r.overall_experience)}</td>
                    <td style="max-width:230px; white-space:normal;">${cmtPreview}</td>
                    <td>${r.may_contact ? '<span class="badge bg-success-subtle text-success border border-success-subtle">Yes</span>' : '<span class="badge bg-light text-muted border">No</span>'}</td>
                    <td>${statusBadge(r.followup_status)}</td>
                    <td class="text-end text-nowrap">
                        <div class="d-inline-flex gap-1">
                            <button type="button" class="cf-act-btn cf-view-btn" data-id="${r.id}"><i class="fa-regular fa-eye"></i> View</button>
                            ${waBtn}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        tbody.querySelectorAll('.cf-view-btn').forEach(btn => {
            btn.addEventListener('click', () => openDetailModal(parseInt(btn.dataset.id, 10)));
        });
    }

    function openDetailModal(id) {
        const r = feedbackRows.find(x => x.id === id);
        if (!r) return;

        document.getElementById('cfDetailModalTitle').textContent = `Feedback #${r.id} — ${r.full_name || 'Anonymous Visitor'}`;
        document.getElementById('cfEditId').value = r.id;
        document.getElementById('cfEditStatus').value = r.followup_status || 'pending';
        document.getElementById('cfEditNote').value = r.followup_note || '';

        const ratingsHtml = [
            ['Behaviour & Attitude', r.behaviour_attitude],
            ['Professionalism & Courtesy', r.professionalism_courtesy],
            ['Response Time / Speed', r.response_time_speed],
            ['Office Environment', r.office_environment],
            ['Overall Experience', r.overall_experience]
        ].map(([lbl, val]) => `
            <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                <span class="fw-medium text-dark small">${lbl}</span>
                ${ratingPill(val)}
            </div>
        `).join('');

        document.getElementById('cfDetailContent').innerHTML = `
            <div class="d-flex flex-wrap justify-content-between gap-2 bg-light border rounded-3 p-3 mb-3 small">
                <div><strong>Visitor:</strong> ${escHtml(r.full_name || 'Anonymous')}</div>
                <div><strong>Mobile:</strong> ${escHtml(r.mobile_number || 'Not provided')}</div>
                <div><strong>May Contact:</strong> ${r.may_contact ? '<span class="text-success fw-bold">Yes</span>' : 'No'}</div>
                <div><strong>Submitted:</strong> ${escHtml(r.created_at)}</div>
            </div>
            <div class="mb-3">${ratingsHtml}</div>
            <div>
                <div class="small fw-bold text-dark mb-1">Comments / Suggestions</div>
                <div class="p-3 bg-light border rounded-3 small" style="white-space:pre-wrap;">${escHtml(r.comments || 'No written comment provided.')}</div>
            </div>
            ${r.followed_up_by_name ? `<div class="mt-2 text-muted" style="font-size:11px;">Last updated by <strong>${escHtml(r.followed_up_by_name)}</strong> at ${escHtml(r.followed_up_at || '')}</div>` : ''}
        `;

        bootstrap.Modal.getOrCreateInstance(document.getElementById('cfDetailModal')).show();
    }

    function loadFeedbacks() {
        updateExportLink();
        const params = new URLSearchParams(collectFilters());
        fetch(baseUrl + '/client-feedback-report/list?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;
            const k = res.kpis || {};
            document.getElementById('cfKpiTotal').textContent    = k.total || 0;
            document.getElementById('cfKpiToday').textContent    = `Today: ${k.today_count || 0}`;
            document.getElementById('cfKpiScore').textContent    = `${k.avg_score || 0} / 4`;
            document.getElementById('cfKpiPosPct').textContent   = `${k.positive_pct || 0}% Positive`;
            document.getElementById('cfKpiPositive').textContent = k.positive_count || 0;
            document.getElementById('cfKpiPoor').textContent     = k.poor_count || 0;
            document.getElementById('cfKpiCallback').textContent = k.callback_requested || 0;
            document.getElementById('cfKpiPending').textContent  = `Pending review: ${k.pending_followup || 0}`;

            renderBreakdown(res.breakdown || []);
            feedbackRows = res.rows || [];
            renderTable();
        })
        .catch(() => {
            document.getElementById('cfTableBody').innerHTML =
                '<tr><td colspan="9" class="text-center py-4 text-danger">Failed to load feedback data.</td></tr>';
        });
    }

    document.getElementById('cfDatePreset').addEventListener('change', function() {
        const isCustom = (this.value === 'custom');
        document.getElementById('cfCustomFromWrap').classList.toggle('d-none', !isCustom);
        document.getElementById('cfCustomToWrap').classList.toggle('d-none', !isCustom);
        if (!isCustom) loadFeedbacks();
    });

    ['cfOverallRating', 'cfMayContact', 'cfFollowupStatus', 'cfHasComment', 'cfFromDate', 'cfToDate'].forEach(id => {
        document.getElementById(id)?.addEventListener('change', loadFeedbacks);
    });

    document.getElementById('cfSearch').addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(loadFeedbacks, 350);
    });
    document.getElementById('cfBtnApply').addEventListener('click', loadFeedbacks);

    document.getElementById('cfBtnReset').addEventListener('click', () => {
        document.getElementById('cfDatePreset').value = '';
        document.getElementById('cfCustomFromWrap').classList.add('d-none');
        document.getElementById('cfCustomToWrap').classList.add('d-none');
        document.getElementById('cfFromDate').value = '';
        document.getElementById('cfToDate').value = '';
        document.getElementById('cfOverallRating').value = '';
        document.getElementById('cfMayContact').value = '';
        document.getElementById('cfFollowupStatus').value = '';
        document.getElementById('cfHasComment').checked = false;
        document.getElementById('cfSearch').value = '';
        loadFeedbacks();
    });

    document.getElementById('cfCardPoor').addEventListener('click', () => {
        document.getElementById('cfOverallRating').value = 'any_poor';
        loadFeedbacks();
    });
    document.getElementById('cfCardCallback').addEventListener('click', () => {
        document.getElementById('cfMayContact').value = '1';
        loadFeedbacks();
    });

    document.getElementById('cfBtnApiInfo').addEventListener('click', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('cfApiInfoModal')).show();
    });

    document.getElementById('cfBtnSaveFollowup').addEventListener('click', async function() {
        const id     = document.getElementById('cfEditId').value;
        const status = document.getElementById('cfEditStatus').value;
        const note   = document.getElementById('cfEditNote').value.trim();
        if (!id) return;

        this.disabled = true;
        try {
            const fd = new FormData();
            fd.append('id', id);
            fd.append('followup_status', status);
            fd.append('followup_note', note);

            const resp = await fetch(baseUrl + '/client-feedback-report/update-status', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            });
            const res = await resp.json();
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('cfDetailModal'))?.hide();
                loadFeedbacks();
            } else {
                alert(res.message || 'Failed to update status.');
            }
        } finally {
            this.disabled = false;
        }
    });

    document.addEventListener('DOMContentLoaded', loadFeedbacks);
})();
</script>
