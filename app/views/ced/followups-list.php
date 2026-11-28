<?php
$page_title = $page_title ?? "Client Follow Ups";
$page = 'Clients';
$show_breadcrumb = true;
$view_mode = $view_mode ?? 'today';
$active_tab = $active_tab ?? 'today';
$days = (int)($days ?? 5);
$selectedProjectId = $selectedProjectId ?? null;
$search = $search ?? '';

$todayCount = (int)($stats['today_count'] ?? 0);
$upcomingCount = (int)($stats['upcoming_5d_count'] ?? 0);
$overdueCount = (int)($stats['overdue_count'] ?? 0);
$totalScheduled = (int)($stats['total_scheduled_count'] ?? 0);
?>

<style>
    .followup-stat-card {
        border: 0;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        overflow: hidden;
        cursor: pointer;
        text-decoration: none !important;
    }
    .followup-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
    }
    .followup-stat-card .card-body {
        padding: 1.15rem 1.25rem;
    }
    .followup-tab-pill {
        border-radius: 20px;
        font-size: 13px;
        padding: 8px 18px;
        cursor: pointer;
        transition: all 0.15s ease;
        border: 1px solid #dee2e6;
        background: #fff;
        color: #495057;
        font-weight: 500;
        text-decoration: none !important;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .followup-tab-pill:hover {
        background: #f8f9fa;
        border-color: #adb5bd;
        color: #0d6efd;
    }
    .followup-tab-pill.active {
        background: #0d6efd;
        color: #fff !important;
        border-color: #0d6efd;
        font-weight: 600;
        box-shadow: 0 2px 8px rgba(13, 110, 253, 0.35);
    }
    .followup-tab-pill.active-danger {
        background: #dc3545;
        color: #fff !important;
        border-color: #dc3545;
        font-weight: 600;
        box-shadow: 0 2px 8px rgba(220, 53, 69, 0.35);
    }
    .table-followup thead th {
        background: #f8f9fa;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6c757d;
        border-bottom: 2px solid #e9ecef;
        padding: 12px 10px;
        white-space: nowrap;
    }
    .table-followup tbody td {
        font-size: 13px;
        padding: 12px 10px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f3f5;
    }
    .table-followup tbody tr:hover {
        background-color: #fafbfd;
    }
    .animate-pulse {
        animation: pulseAnimation 1.5s infinite;
    }
    @keyframes pulseAnimation {
        0% { transform: scale(1); }
        50% { transform: scale(1.15); }
        100% { transform: scale(1); }
    }
</style>

<div class="container-fluid px-3 px-md-4 py-3">

    <!-- ══ HEADER & ACTIONS ══ -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h4 class="fw-bold mb-0 text-dark">
                    <?php if ($view_mode === 'today' && $active_tab !== 'overdue'): ?>
                        <i class="fa-solid fa-calendar-day text-danger me-2"></i>Today's Scheduled Follow Ups
                    <?php elseif ($active_tab === 'overdue'): ?>
                        <i class="fa-solid fa-triangle-exclamation text-danger me-2"></i>Overdue Follow Ups
                    <?php else: ?>
                        <i class="fa-solid fa-calendar-days text-info me-2"></i>Upcoming Client Follow Ups (Next <?= $days ?> Days)
                    <?php endif; ?>
                </h4>
            </div>
            <p class="text-muted extra-small mb-0 mt-1">
                Monitor and execute timely EMI and installment follow-up calls, notes, and commitments with clients.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= $GLOBALS['base_url'] ?>/client-wise-due-tracking" class="btn btn-outline-secondary btn-sm py-1 px-3">
                <i class="fa-solid fa-money-bill-trend-up me-1"></i> Due Tracking & Mgt
            </a>
            <a href="<?= $GLOBALS['base_url'] ?>/export-followups?mode=<?= $active_tab ?>&days=<?= $days ?>&project_id=<?= $selectedProjectId ?? '' ?>" class="btn btn-outline-primary btn-sm py-1 px-3">
                <i class="fa-solid fa-file-csv me-1"></i> Export CSV
            </a>
        </div>
    </div>

    <!-- ══ SUMMARY STAT CARDS ══ -->
    <div class="row  mb-4">
        <!-- 1. Today Follow Ups -->
        <div class="col-xl-3 col-sm-6">
            <a href="<?= $GLOBALS['base_url'] ?>/today-follow-ups" class="card followup-stat-card h-100 <?= ($view_mode === 'today' && $active_tab !== 'overdue') ? 'border-primary border-2' : '' ?>">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted extra-small fw-semibold text-uppercase">Today's Follow Ups</span>
                            <h3 class="fw-bold my-1 text-danger"><?= number_format($todayCount) ?></h3>
                            <span class="badge bg-danger-subtle text-danger extra-small fw-semibold">
                                <i class="fa-solid fa-bell me-1"></i>Due Today
                            </span>
                        </div>
                        <div class="avatar avatar-md bg-danger-subtle text-danger rounded-3 d-flex align-items-center justify-content-center" style="width:48px; height:48px;">
                            <i class="fa-solid fa-calendar-day fs-4"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- 2. Upcoming Follow Ups (Next 5 Days) -->
        <div class="col-xl-3 col-sm-6">
            <a href="<?= $GLOBALS['base_url'] ?>/upcoming-follow-ups" class="card followup-stat-card h-100 <?= ($view_mode === 'upcoming') ? 'border-info border-2' : '' ?>">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted extra-small fw-semibold text-uppercase">Upcoming (Next 5 Days)</span>
                            <h3 class="fw-bold my-1 text-info"><?= number_format($upcomingCount) ?></h3>
                            <span class="badge bg-info-subtle text-info extra-small fw-semibold">
                                <i class="fa-solid fa-clock me-1"></i>Next 5 Days
                            </span>
                        </div>
                        <div class="avatar avatar-md bg-info-subtle text-info rounded-3 d-flex align-items-center justify-content-center" style="width:48px; height:48px;">
                            <i class="fa-solid fa-calendar-days fs-4"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- 3. Overdue Follow Ups -->
        <div class="col-xl-3 col-sm-6">
            <a href="<?= $GLOBALS['base_url'] ?>/today-follow-ups?filter=overdue" class="card followup-stat-card h-100 <?= ($active_tab === 'overdue') ? 'border-danger border-2' : '' ?>">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted extra-small fw-semibold text-uppercase">Past / Overdue Follow Ups</span>
                            <h3 class="fw-bold my-1 text-dark"><?= number_format($overdueCount) ?></h3>
                            <span class="badge bg-warning-subtle text-dark extra-small fw-semibold">
                                <i class="fa-solid fa-triangle-exclamation text-warning me-1"></i>Before Today
                            </span>
                        </div>
                        <div class="avatar avatar-md bg-warning-subtle text-dark rounded-3 d-flex align-items-center justify-content-center" style="width:48px; height:48px;">
                            <i class="fa-solid fa-calendar-xmark fs-4 text-warning"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- 4. Total Scheduled Follow Ups -->
        <div class="col-xl-3 col-sm-6">
            <div class="card followup-stat-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted extra-small fw-semibold text-uppercase">Total Scheduled Follow Ups</span>
                            <h3 class="fw-bold my-1 text-primary"><?= number_format($totalScheduled) ?></h3>
                            <span class="badge bg-primary-subtle text-primary extra-small fw-semibold">
                                <i class="fa-solid fa-clipboard-list-check me-1"></i>All Queued
                            </span>
                        </div>
                        <div class="avatar avatar-md bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center" style="width:48px; height:48px;">
                            <i class="fa-solid fa-calendar-check fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ TAB PILLS & FILTER BAR ══ -->
    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <!-- Navigation Tabs -->
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <a href="<?= $GLOBALS['base_url'] ?>/today-follow-ups" 
                       class="followup-tab-pill <?= ($view_mode === 'today' && $active_tab !== 'overdue') ? 'active-danger' : '' ?>">
                        <i class="fa-solid fa-calendar-day"></i>
                        <span>Today's Follow Ups</span>
                        <span class="badge rounded-pill <?= ($view_mode === 'today' && $active_tab !== 'overdue') ? 'bg-white text-danger' : 'bg-danger text-white' ?> ms-1">
                            <?= $todayCount ?>
                        </span>
                    </a>

                    <a href="<?= $GLOBALS['base_url'] ?>/upcoming-follow-ups" 
                       class="followup-tab-pill <?= ($view_mode === 'upcoming') ? 'active' : '' ?>">
                        <i class="fa-solid fa-calendar-days"></i>
                        <span>Upcoming (Next 5 Days)</span>
                        <span class="badge rounded-pill <?= ($view_mode === 'upcoming') ? 'bg-white text-primary' : 'bg-info text-white' ?> ms-1">
                            <?= $upcomingCount ?>
                        </span>
                    </a>

                    <a href="<?= $GLOBALS['base_url'] ?>/today-follow-ups?filter=overdue" 
                       class="followup-tab-pill <?= ($active_tab === 'overdue') ? 'active' : '' ?>">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>Overdue Follow Ups</span>
                        <span class="badge rounded-pill <?= ($active_tab === 'overdue') ? 'bg-white text-dark' : 'bg-secondary text-white' ?> ms-1">
                            <?= $overdueCount ?>
                        </span>
                    </a>
                </div>

                <!-- Filters & Search Form -->
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <!-- Project Selector -->
                    <select id="followupProjectSelect" class="form-select form-select-sm" style="min-width: 180px;" onchange="applyFollowupFilter()">
                        <option value="">All Projects</option>
                        <?php if (!empty($projects)): ?>
                            <?php foreach ($projects as $prj): ?>
                                <option value="<?= $prj['id'] ?>" <?= ((int)$selectedProjectId === (int)$prj['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($prj['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>

                    <!-- Days Filter (if upcoming) -->
                    <?php if ($view_mode === 'upcoming'): ?>
                        <select id="followupDaysSelect" class="form-select form-select-sm" style="min-width: 140px;" onchange="applyFollowupFilter()">
                            <option value="5" <?= ($days === 5) ? 'selected' : '' ?>>Next 5 Days</option>
                            <option value="7" <?= ($days === 7) ? 'selected' : '' ?>>Next 7 Days</option>
                            <option value="15" <?= ($days === 15) ? 'selected' : '' ?>>Next 15 Days</option>
                            <option value="30" <?= ($days === 30) ? 'selected' : '' ?>>Next 30 Days</option>
                        </select>
                    <?php endif; ?>

                    <!-- Live Search Box -->
                    <div class="input-group input-group-sm" style="width: 240px;">
                        <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" id="followupSearchInput" class="form-control border-start-0" placeholder="Search client, phone, flat..." value="<?= htmlspecialchars($search) ?>" onkeyup="onFollowupSearchKeyup(event)">
                        <?php if (!empty($search)): ?>
                            <button class="btn btn-outline-secondary border-start-0" type="button" onclick="clearFollowupSearch()"><i class="fa-solid fa-times"></i></button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ MAIN FOLLOW UP LIST TABLE ══ -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary-subtle text-primary fw-semibold px-2 py-1">Follow-up List</span>
                <span class="text-muted extra-small" id="followupInfoText">
                    Showing <?= min($totalRecords, ($currentPage - 1) * $limit + 1) ?> to <?= min($totalRecords, $currentPage * $limit) ?> of <?= $totalRecords ?> records
                </span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label class="form-label extra-small text-muted mb-0">Show:</label>
                <select id="followupLimitSelect" class="form-select form-select-sm py-0" style="width:70px;" onchange="applyFollowupFilter()">
                    <option value="10" <?= ($limit == 10) ? 'selected' : '' ?>>10</option>
                    <option value="25" <?= ($limit == 25) ? 'selected' : '' ?>>25</option>
                    <option value="50" <?= ($limit == 50) ? 'selected' : '' ?>>50</option>
                    <option value="100" <?= ($limit == 100) ? 'selected' : '' ?>>100</option>
                </select>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-followup align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 120px;">Booking & Schedule</th>
                            <th style="min-width: 200px;">Client Information</th>
                            <th style="min-width: 170px;">Project & Flat</th>
                            <th style="min-width: 160px;">Follow Up Target</th>
                            <th style="min-width: 220px;">Next Follow Up Note / Promise</th>
                            <th style="min-width: 250px;">Last Activity Details</th>
                            <th class="text-center" style="width: 130px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="followupTableBody">
                        <?= (new AccountClientController($GLOBALS['db'] ?? null))->renderFollowupRowsHtml($records, $active_tab) ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ══ PAGINATION ══ -->
        <div class="card-footer bg-white py-3 px-4 border-top">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3" id="followupPaginationContainer">
                <?= (new AccountClientController($GLOBALS['db'] ?? null))->generateAjaxPagination($currentPage, $totalPages, $totalRecords, $limit, 'loadFollowupsPage') ?>
            </div>
        </div>
    </div>

</div>

<!-- ===== CLIENT EMI FOLLOW UP MODAL ===== -->
<div class="modal fade" id="clientEmiFollowupModal" tabindex="-1" aria-labelledby="clientEmiFollowupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header" style="background: linear-gradient(135deg, #1e3a5f, #2563eb); color:#fff; border:0; padding:16px 24px;">
                <h5 class="modal-title fw-bold" id="clientEmiFollowupModalLabel">
                    <i class="fa-light fa-comment-dots me-2"></i>EMI Follow Up Notes & Schedule — <span id="followup_modal_client_name" class="badge bg-white text-primary fs-14">Client</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <!-- Left Column: Add Follow Up Form -->
                    <div class="col-lg-5 border-end pe-lg-4">
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                            <h6 class="text-primary mb-0 fw-bold" id="modalFollowupFormHeading"><i class="fa-light fa-plus-circle me-1"></i>Add New Follow Up Note</h6>
                            <span class="badge bg-primary-subtle text-primary extra-small" id="modalFollowupFormBadge">Follow-up Log</span>
                        </div>
                        <input type="hidden" id="modalFollowupBookingId">
                        <input type="hidden" id="modalFollowupEditId" value="">
                        
                        <div class="mb-3" id="modalFollowupProjectContainer">
                            <label class="form-label fs-12 fw-semibold text-muted mb-1">Project & Flat</label>
                            <select id="modalFollowupBookingSelect" class="form-select form-select-sm" onchange="onFollowupBookingChange()">
                                <!-- dynamically loaded -->
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fs-12 fw-semibold text-muted mb-1">Installment / EMI</label>
                            <select id="modalFollowupEmiSelect" class="form-select form-select-sm" onchange="loadClientEmiFollowups($('#modalFollowupBookingId').val())">
                                <option value="0">General / Overall Follow Up</option>
                                <option value="999">Development Charge</option>
                            </select>
                        </div>
                        
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fs-12 fw-semibold text-muted mb-1">Date of Activity <span class="text-danger">*</span></label>
                                <input type="date" id="modalFollowupActivityDate" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-12 fw-semibold text-muted mb-1">Mode of Activity <span class="text-danger">*</span></label>
                                <select id="modalFollowupActivityMode" class="form-select form-select-sm">
                                    <option value="">Select Mode</option>
                                    <option value="Phone Call" selected>Phone Call</option>
                                    <option value="WhatsApp">WhatsApp</option>
                                    <option value="SMS">SMS</option>
                                    <option value="Email">Email</option>
                                    <option value="Meeting">Meeting</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6" id="modalFollowupCallStatusGroup">
                                <label class="form-label fs-12 fw-semibold text-muted mb-1">Call Status</label>
                                <select id="modalFollowupCallStatus" class="form-select form-select-sm">
                                    <option value="">Select Call Status</option>
                                    <option value="Outgoing" selected>Outgoing</option>
                                    <option value="Incoming">Incoming</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-12 fw-semibold text-muted mb-1">Reason for Activity <span class="text-danger">*</span></label>
                                <select id="modalFollowupActivityReason" class="form-select form-select-sm">
                                    <option value="">Select Reason</option>
                                    <option value="EMI Payment Follow-up" selected>EMI Payment Follow-up</option>
                                    <option value="Payment Reminder (General)">Payment Reminder (General)</option>
                                    <option value="Development Charge Follow-up">Development Charge Follow-up</option>
                                    <option value="Overdue Payment Follow-up">Overdue Payment Follow-up</option>
                                    <option value="Commitment Date Extension">Commitment Date Extension</option>
                                    <option value="Handover / Registration Discussion">Handover / Registration Discussion</option>
                                    <option value="Document Collection">Document Collection</option>
                                    <option value="Client Query / Issue Resolution">Client Query / Issue Resolution</option>
                                    <option value="Others">Others</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fs-12 fw-semibold text-muted mb-1">Discussion Note / Remarks <span class="text-danger">*</span></label>
                            <textarea id="modalFollowupNoteInput" class="form-control form-control-sm" rows="3" placeholder="Enter follow up discussion details, client feedback, or notes..."></textarea>
                        </div>

                        <!-- 2 New Optional Fields: Next Follow Up Date & Next Follow Up Note -->
                        <div class="p-3 mb-3 rounded-3" style="background:#f0f7ff; border:1px dashed #93c5fd;">
                            <div class="d-flex align-items-center gap-1 mb-2 text-primary fw-semibold fs-12">
                                <i class="fa-solid fa-calendar-clock"></i> Next Follow Up Planning (Optional)
                            </div>
                            <div class="mb-2">
                                <label class="form-label fs-12 fw-medium text-dark mb-1">Next Follow Up Date</label>
                                <input type="date" id="modalFollowupNextDate" class="form-control form-control-sm">
                                <small class="text-muted extra-small">If scheduled, client appears in Today / Upcoming follow-up lists</small>
                            </div>
                            <div>
                                <label class="form-label fs-12 fw-medium text-dark mb-1">Next Follow Up Note / Promise</label>
                                <textarea id="modalFollowupNextNote" class="form-control form-control-sm" rows="2" placeholder="Next plan, commitment amount, or reminder note..."></textarea>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary w-100 shadow-sm py-2" id="saveFollowupBtn" onclick="saveClientEmiFollowup()">
                                <i class="fa-light fa-paper-plane me-1"></i>Save Follow Up Note
                            </button>
                            <button type="button" class="btn btn-outline-secondary py-2 px-3 d-none text-nowrap" id="cancelEditFollowupBtn" onclick="cancelEditFollowup()">
                                Cancel
                            </button>
                        </div>
                        
                    </div>
                    
                    <!-- Right Column: Follow Up History -->
                    <div class="col-lg-7 ps-lg-4">
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                            <h6 class="text-dark mb-0 fw-bold"><i class="fa-light fa-clock-rotate-left me-1"></i>Previous Follow Up History</h6>
                            <span class="badge bg-secondary-subtle text-secondary" id="modalFollowupHistoryCountBadge">0 notes</span>
                        </div>
                        <div id="modalFollowupHistoryContainer" style="max-height: 480px; overflow-y: auto; padding-right: 4px;">
                            <div class="text-center text-muted py-5">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div>Loading history...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-4 d-flex justify-content-between">
                <span class="text-muted extra-small"><i class="fa-solid fa-circle-info me-1"></i>Follow-ups update client engagement metrics and automated queues.</span>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    const followupBaseUrl = '<?= $GLOBALS['base_url'] ?>';
    const followupViewMode = '<?= $view_mode ?>';
    let followupActiveTab = '<?= $active_tab ?>';
    let followupCurrentPage = <?= (int)$currentPage ?>;
    let followupSearchTimer = null;
    let clientEmiFollowupModalInstance = null;
    let loadedFollowupsById = {};
    const defaultFollowupActivityDate = '<?= date('Y-m-d') ?>';

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function applyFollowupFilter() {
        loadFollowupsPage(1);
    }

    function onFollowupSearchKeyup(e) {
        clearTimeout(followupSearchTimer);
        followupSearchTimer = setTimeout(() => {
            loadFollowupsPage(1);
        }, 350);
    }

    function clearFollowupSearch() {
        document.getElementById('followupSearchInput').value = '';
        loadFollowupsPage(1);
    }

    function loadFollowupsPage(page = 1) {
        followupCurrentPage = page;
        const projectEl = document.getElementById('followupProjectSelect');
        const projectId = projectEl ? projectEl.value : '';
        const searchVal = document.getElementById('followupSearchInput') ? document.getElementById('followupSearchInput').value.trim() : '';
        const limitVal = document.getElementById('followupLimitSelect') ? document.getElementById('followupLimitSelect').value : 25;
        const daysEl = document.getElementById('followupDaysSelect');
        const daysVal = daysEl ? daysEl.value : 5;

        const tableBody = document.getElementById('followupTableBody');
        tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-muted"><div class="spinner-border text-primary spinner-border-sm me-2"></div>Loading follow-ups...</td></tr>';

        const endpoint = (followupViewMode === 'upcoming') 
            ? `${followupBaseUrl}/upcoming-follow-ups` 
            : `${followupBaseUrl}/today-follow-ups`;

        const queryParams = new URLSearchParams({
            ajax: '1',
            page: page,
            limit: limitVal,
            project_id: projectId,
            search: searchVal,
            filter: followupActiveTab,
            days: daysVal
        });

        fetch(`${endpoint}?${queryParams.toString()}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    tableBody.innerHTML = data.table_html;
                    const pagin = document.getElementById('followupPaginationContainer');
                    if (pagin) pagin.innerHTML = data.pagination_html || '';
                    const infoText = document.getElementById('followupInfoText');
                    if (infoText) infoText.textContent = data.info_text || '';
                } else {
                    tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-danger">Failed to load follow-ups.</td></tr>';
                }
            })
            .catch(err => {
                console.error(err);
                tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-danger">Network error loading records.</td></tr>';
            });
    }

    // Modal logic
    function openClientEmiFollowupModal(bookingId, clientName, totalEmi = 36, leadId = null, editFollowupId = null) {
        document.getElementById('followup_modal_client_name').textContent = clientName || 'Client';
        document.getElementById('modalFollowupBookingId').value = bookingId;
        cancelEditFollowup();
        
        const bookingSelect = document.getElementById('modalFollowupBookingSelect');
        if (bookingSelect) {
            bookingSelect.innerHTML = '<option value="' + bookingId + '">Loading project details...</option>';
            bookingSelect.disabled = true;
        }

        populateEmiSelect(totalEmi);
        document.getElementById('modalFollowupHistoryContainer').innerHTML = '<div class="text-center text-muted py-5"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Loading history...</div>';
        
        const modalEl = document.getElementById('clientEmiFollowupModal');
        if (modalEl) {
            clientEmiFollowupModalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            clientEmiFollowupModalInstance.show();
        }
        
        if (leadId) {
            fetch(`${followupBaseUrl}/get-client-lands?lead_id=${leadId}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.lands && data.lands.length > 0) {
                        let opts = '';
                        data.lands.forEach(land => {
                            const selected = (land.booking_id == bookingId) ? 'selected' : '';
                            opts += `<option value="${land.booking_id}" data-emi="${land.total_emi_count || 36}" ${selected}>${escapeHtml(land.project_name)} - ${escapeHtml(land.unit_name)} (Floor ${escapeHtml(land.flat_no || '')})</option>`;
                        });
                        if (bookingSelect) {
                            bookingSelect.innerHTML = opts;
                            bookingSelect.disabled = false;
                        }
                    } else {
                        if (bookingSelect) {
                            bookingSelect.innerHTML = '<option value="' + bookingId + '">Current Booking #' + bookingId + '</option>';
                            bookingSelect.disabled = false;
                        }
                    }
                })
                .catch(err => {
                    if (bookingSelect) {
                        bookingSelect.innerHTML = '<option value="' + bookingId + '">Current Booking #' + bookingId + '</option>';
                        bookingSelect.disabled = false;
                    }
                });
        } else {
            if (bookingSelect) {
                bookingSelect.innerHTML = '<option value="' + bookingId + '">Current Booking #' + bookingId + '</option>';
                bookingSelect.disabled = false;
            }
        }

        loadClientEmiFollowups(bookingId, editFollowupId);
    }

    function startEditFollowup(followupId) {
        const note = loadedFollowupsById[String(followupId)];
        if (!note) return;

        document.getElementById('modalFollowupEditId').value = note.id;

        const emiSelect = document.getElementById('modalFollowupEmiSelect');
        if (emiSelect) {
            const emiVal = String(note.emi_no ?? 0);
            if (!Array.from(emiSelect.options).some(opt => opt.value === emiVal)) {
                const extraOpt = document.createElement('option');
                extraOpt.value = emiVal;
                extraOpt.textContent = (emiVal === '999') ? 'Development Charge' : (emiVal === '0' ? 'General / Overall Follow Up' : 'EMI #' + emiVal);
                emiSelect.appendChild(extraOpt);
            }
            emiSelect.value = emiVal;
        }

        document.getElementById('modalFollowupActivityDate').value = (note.activity_date || '').substring(0, 10);
        document.getElementById('modalFollowupActivityMode').value = note.activity_mode || 'Phone Call';
        document.getElementById('modalFollowupCallStatus').value = note.call_status || '';
        document.getElementById('modalFollowupActivityReason').value = note.activity_reason || 'EMI Payment Follow-up';
        document.getElementById('modalFollowupNoteInput').value = note.note || '';
        document.getElementById('modalFollowupNextDate').value = (note.next_follow_up_date || '').substring(0, 10);
        document.getElementById('modalFollowupNextNote').value = note.next_follow_up_note || '';

        const heading = document.getElementById('modalFollowupFormHeading');
        if (heading) heading.innerHTML = '<i class="fa-solid fa-pen-to-square me-1 text-warning"></i>Edit Follow Up Note';
        const badge = document.getElementById('modalFollowupFormBadge');
        if (badge) {
            badge.className = 'badge bg-warning-subtle text-dark extra-small';
            badge.textContent = 'Editing #' + note.id;
        }
        const saveBtn = document.getElementById('saveFollowupBtn');
        if (saveBtn) {
            saveBtn.className = 'btn btn-warning text-dark fw-semibold w-100 shadow-sm py-2';
            saveBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i>Update Follow Up Note';
        }
        const cancelBtn = document.getElementById('cancelEditFollowupBtn');
        if (cancelBtn) cancelBtn.classList.remove('d-none');

        document.querySelectorAll('#modalFollowupHistoryContainer .followup-history-card').forEach(card => {
            card.classList.toggle('border', card.dataset.followupId === String(followupId));
            card.classList.toggle('border-warning', card.dataset.followupId === String(followupId));
        });
    }

    function cancelEditFollowup() {
        const editIdEl = document.getElementById('modalFollowupEditId');
        if (editIdEl) editIdEl.value = '';
        const noteEl = document.getElementById('modalFollowupNoteInput');
        if (noteEl) noteEl.value = '';
        const actDateEl = document.getElementById('modalFollowupActivityDate');
        if (actDateEl) actDateEl.value = defaultFollowupActivityDate;
        const actModeEl = document.getElementById('modalFollowupActivityMode');
        if (actModeEl) actModeEl.value = 'Phone Call';
        const callStatusEl = document.getElementById('modalFollowupCallStatus');
        if (callStatusEl) callStatusEl.value = 'Outgoing';
        const reasonEl = document.getElementById('modalFollowupActivityReason');
        if (reasonEl) reasonEl.value = 'EMI Payment Follow-up';
        const nextDateEl = document.getElementById('modalFollowupNextDate');
        if (nextDateEl) nextDateEl.value = '';
        const nextNoteEl = document.getElementById('modalFollowupNextNote');
        if (nextNoteEl) nextNoteEl.value = '';

        const heading = document.getElementById('modalFollowupFormHeading');
        if (heading) heading.innerHTML = '<i class="fa-light fa-plus-circle me-1"></i>Add New Follow Up Note';
        const badge = document.getElementById('modalFollowupFormBadge');
        if (badge) {
            badge.className = 'badge bg-primary-subtle text-primary extra-small';
            badge.textContent = 'Follow-up Log';
        }
        const saveBtn = document.getElementById('saveFollowupBtn');
        if (saveBtn) {
            saveBtn.className = 'btn btn-primary w-100 shadow-sm py-2';
            saveBtn.innerHTML = '<i class="fa-light fa-paper-plane me-1"></i>Save Follow Up Note';
        }
        const cancelBtn = document.getElementById('cancelEditFollowupBtn');
        if (cancelBtn) cancelBtn.classList.add('d-none');

        document.querySelectorAll('#modalFollowupHistoryContainer .followup-history-card').forEach(card => {
            card.classList.remove('border', 'border-warning');
        });
    }

    function onFollowupBookingChange() {
        const bookingSelect = document.getElementById('modalFollowupBookingSelect');
        if (!bookingSelect) return;
        
        const selectedOption = bookingSelect.options[bookingSelect.selectedIndex];
        const bookingId = selectedOption.value;
        const totalEmi = selectedOption.getAttribute('data-emi') || 36;
        
        document.getElementById('modalFollowupBookingId').value = bookingId;
        cancelEditFollowup();
        populateEmiSelect(totalEmi);
        loadClientEmiFollowups(bookingId);
    }

    function populateEmiSelect(totalEmi) {
        const emiSelect = document.getElementById('modalFollowupEmiSelect');
        if (emiSelect) {
            let optionsHtml = '<option value="0">General / Overall Follow Up</option>';
            optionsHtml += '<option value="999">Development Charge</option>';
            const maxEmi = parseInt(totalEmi) || 36;
            for (let i = 1; i <= maxEmi; i++) {
                optionsHtml += `<option value="${i}">EMI #${i}</option>`;
            }
            emiSelect.innerHTML = optionsHtml;
        }
    }

    function loadClientEmiFollowups(bookingId, editFollowupId = null) {
        if (!bookingId) return;
        $.ajax({
            url: `${followupBaseUrl}/account-client/getEmiFollowups`,
            type: 'GET',
            data: { booking_id: bookingId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    loadedFollowupsById = {};
                    let html = '';
                    const countBadge = document.getElementById('modalFollowupHistoryCountBadge');
                    if (countBadge) {
                        countBadge.textContent = (response.data ? response.data.length : 0) + ' note(s)';
                    }
                    if (!response.data || response.data.length === 0) {
                        html = '<div class="text-center text-muted py-5"><i class="fa-light fa-comment-slash d-block fs-2 mb-2 opacity-50"></i>No follow up notes added yet. Use the form on the left to add the first follow up note.</div>';
                    } else {
                        response.data.forEach(function(note) {
                            loadedFollowupsById[String(note.id)] = note;
                            const label = note.purpose_label || (note.emi_no == 999 ? 'Development Charge' : (note.emi_no == 0 ? 'General Follow Up' : 'EMI #' + note.emi_no));
                            const badgeClass = note.purpose_badge_class || (note.emi_no == 999 ? 'bg-warning text-dark' : (note.emi_no == 0 ? 'bg-secondary text-white' : 'bg-primary text-white'));
                            const borderClr = note.emi_no == 999 ? '#f59e0b' : (note.emi_no == 0 ? '#64748b' : '#2563eb');

                            let nextFollowupBlock = '';
                            if (note.next_follow_up_date) {
                                const nextDateDisplay = note.next_follow_up_date_formatted || note.next_follow_up_date;
                                const nextNoteDisplay = note.next_follow_up_note ? `<div class="text-dark fst-italic mt-1">${escapeHtml(note.next_follow_up_note)}</div>` : '';
                                nextFollowupBlock = `
                                    <div class="mt-2 p-2 rounded bg-info-subtle border-start border-3 border-info fs-12 text-dark">
                                        <div class="fw-semibold text-primary"><i class="fa-solid fa-calendar-check me-1"></i>Next Scheduled Follow Up: <strong>${escapeHtml(nextDateDisplay)}</strong></div>
                                        ${nextNoteDisplay}
                                    </div>
                                `;
                            }

                            html += `
                            <div class="card mb-2 shadow-sm border-0 followup-history-card" data-followup-id="${note.id}" style="background:#f8fafc; border-left: 4px solid ${borderClr} !important;">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2 gap-2">
                                        <span class="badge ${badgeClass} fs-11 px-2 py-1">
                                            <i class="fa-light fa-tag me-1"></i>Follow Up for: <strong>${escapeHtml(label)}</strong>
                                        </span>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="text-muted fs-11"><i class="fa-light fa-clock me-1"></i>${note.created_at_formatted || note.created_at || ''}</span>
                                            <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size:11px;" onclick="startEditFollowup(${note.id})" title="Edit this follow-up">
                                                <i class="fa-solid fa-pen-to-square me-1"></i>Edit
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mb-2" style="font-size:12px; color:#475569;">
                                        ${note.activity_date ? `<span><i class="fa-light fa-calendar me-1 text-primary"></i>${escapeHtml(note.activity_date)}</span>` : ''}
                                        ${note.activity_mode ? `<span class="ms-3"><i class="fa-light fa-phone-volume me-1 text-info"></i>${escapeHtml(note.activity_mode)}</span>` : ''}
                                        ${note.call_status ? `<span class="ms-2 badge ${note.call_status.toLowerCase() === 'incoming' ? 'bg-success text-white' : 'bg-primary text-white'}" style="font-size:10px;"><i class="fa-light ${note.call_status.toLowerCase() === 'incoming' ? 'fa-arrow-down-left' : 'fa-arrow-up-right'} me-1"></i>${escapeHtml(note.call_status)}</span>` : ''}
                                        ${note.activity_reason ? `<span class="ms-3"><i class="fa-light fa-bullseye me-1 text-warning"></i>${escapeHtml(note.activity_reason)}</span>` : ''}
                                    </div>
                                    <p class="mb-2 text-dark fs-13 fw-medium" style="white-space:pre-line;">${escapeHtml(note.note || '')}</p>
                                    ${nextFollowupBlock}
                                    <div class="text-muted fs-11 text-end mt-2 pt-1 border-top">
                                        <span><i class="fa-light fa-user me-1"></i>By ${escapeHtml(note.author_name || 'Staff')}</span>
                                    </div>
                                </div>
                            </div>`;
                        });
                    }
                    $('#modalFollowupHistoryContainer').html(html);
                    if (editFollowupId) {
                        startEditFollowup(editFollowupId);
                    }
                } else {
                    $('#modalFollowupHistoryContainer').html('<div class="text-danger p-3 text-center">Failed to load history.</div>');
                }
            },
            error: function() {
                $('#modalFollowupHistoryContainer').html('<div class="text-danger p-3 text-center">Error connecting to server.</div>');
            }
        });
    }

    function saveClientEmiFollowup() {
        const editId = ($('#modalFollowupEditId').val() || '').trim();
        const isEditing = editId !== '';
        const bookingId = $('#modalFollowupBookingId').val();
        const emiNo = $('#modalFollowupEmiSelect').val() ?? 0;
        const note = $('#modalFollowupNoteInput').val().trim();
        const activityDate = $('#modalFollowupActivityDate').val();
        const activityMode = $('#modalFollowupActivityMode').val();
        const callStatus = $('#modalFollowupCallStatus').val() || '';
        const activityReason = $('#modalFollowupActivityReason').val();
        const nextDate = $('#modalFollowupNextDate').val() || '';
        const nextNote = $('#modalFollowupNextNote').val().trim();
        
        if (!note || !activityDate || !activityMode || !activityReason) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Required Fields Missing',
                    text: 'Please fill in Note, Activity Date, Mode, and Reason.',
                });
            } else {
                alert('Please fill in Note, Activity Date, Mode, and Reason.');
            }
            return;
        }

        const saveBtn = document.getElementById('saveFollowupBtn');
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>${isEditing ? 'Updating...' : 'Saving...'}`;
        }

        const payload = {
            booking_id: bookingId,
            emi_no: emiNo,
            note: note,
            activity_date: activityDate,
            activity_mode: activityMode,
            call_status: callStatus,
            activity_reason: activityReason,
            next_follow_up_date: nextDate,
            next_follow_up_note: nextNote
        };
        if (isEditing) {
            payload.followup_id = editId;
        }
        
        $.ajax({
            url: isEditing
                ? `${followupBaseUrl}/account-client/updateEmiFollowup`
                : `${followupBaseUrl}/account-client/saveEmiFollowup`,
            type: 'POST',
            data: payload,
            dataType: 'json',
            success: function(response) {
                if (saveBtn) {
                    saveBtn.disabled = false;
                }
                if (response.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: isEditing ? 'Updated!' : 'Saved!',
                            text: isEditing ? 'Follow up note updated successfully.' : 'Follow up note recorded successfully.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                    cancelEditFollowup();
                    loadClientEmiFollowups(bookingId);
                    
                    // Reload current list page so updated next follow up notes/dates reflect
                    loadFollowupsPage(followupCurrentPage);
                } else {
                    if (saveBtn) {
                        saveBtn.innerHTML = isEditing
                            ? '<i class="fa-solid fa-check me-1"></i>Update Follow Up Note'
                            : '<i class="fa-light fa-paper-plane me-1"></i>Save Follow Up Note';
                    }
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed',
                            text: response.message || (isEditing ? "Failed to update note." : "Failed to save note.")
                        });
                    } else {
                        alert(response.message || (isEditing ? "Failed to update note." : "Failed to save note."));
                    }
                }
            },
            error: function() {
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = isEditing
                        ? '<i class="fa-solid fa-check me-1"></i>Update Follow Up Note'
                        : '<i class="fa-light fa-paper-plane me-1"></i>Save Follow Up Note';
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: isEditing ? 'An error occurred while updating the note.' : 'An error occurred while saving the note.'
                    });
                } else {
                    alert(isEditing ? 'An error occurred while updating the note.' : 'An error occurred while saving the note.');
                }
            }
        });
    }
</script>
