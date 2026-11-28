<?php
$base_url     = $base_url ?? ($GLOBALS['base_url'] ?? '/TPHL-ERP');
$page_title   = $page_title ?? 'Contractor Bill Requests';
$totalRecords = $totalRecords ?? count($bills ?? []);
$projects     = $projects ?? [];
$creators     = $creators ?? [];
$contractors  = $contractors ?? [];
$statusCounts = $statusCounts ?? [
    'all'         => $totalRecords,
    'draft'       => 0,
    'submitted'   => 0,
    'verified'    => 0,
    'qc_approved' => 0,
    'approved'    => 0,
    'rejected'    => 0,
    'paid'        => 0
];
$selectedStatus = $filters['status'] ?? 'All';
?>
<style>
/* Filter Toolbar Controls Alignment */
.card-body .form-select-sm {
    border: 1px solid #dfe3e8;
    border-radius: 4px;
    height: 31px;
    padding: 2px 28px 2px 10px;
    font-size: 12.5px;
    font-weight: 500;
    color: #333;
    background-color: #fff;
    cursor: pointer;
}
.card-body .form-select-sm:focus {
    border-color: var(--clr-theme-primary);
    box-shadow: 0 0 0 2px rgba(108, 95, 252, 0.12);
}

/* Select2 Searchable Dropdown Styling in Card Filter Bar */
.card-body .select2-container {
    width: 100% !important;
}
.card-body .select2-container--default .select2-selection--single {
    height: 31px !important;
    border: 1px solid #dfe3e8 !important;
    border-radius: 4px !important;
    background-color: #fff !important;
    display: flex !important;
    align-items: center !important;
    position: relative !important;
}
.card-body .select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #333 !important;
    font-size: 12.5px !important;
    font-weight: 500 !important;
    line-height: 29px !important;
    padding-left: 10px !important;
    padding-right: 40px !important;
    display: block !important;
    width: 100% !important;
    text-overflow: ellipsis !important;
    white-space: nowrap !important;
    overflow: hidden !important;
}
.card-body .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 29px !important;
    right: 6px !important;
    position: absolute !important;
    top: 0 !important;
}
.card-body .select2-container--default .select2-selection--single .select2-selection__clear {
    position: absolute !important;
    right: 22px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    font-size: 15px !important;
    color: #888 !important;
    font-weight: bold !important;
    cursor: pointer !important;
}
.card-body .select2-container--default .select2-selection--single .select2-selection__clear:hover {
    color: #dc3545 !important;
}
.card-body .select2-container--default.select2-container--focus .select2-selection--single,
.card-body .select2-container--default.select2-container--open .select2-selection--single {
    border-color: #0d6efd !important;
    box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.15) !important;
}

/* Dropdown Menu & Search Input Box */
.select2-dropdown {
    border: 1px solid #cbd5e1 !important;
    border-radius: 6px !important;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12) !important;
    font-size: 12.5px !important;
    z-index: 10050 !important;
}
.select2-search--dropdown {
    padding: 6px 8px !important;
    background-color: #f8fafc !important;
    border-bottom: 1px solid #e2e8f0 !important;
}
.select2-search--dropdown .select2-search__field {
    border: 1px solid #cbd5e1 !important;
    border-radius: 4px !important;
    padding: 5px 8px !important;
    font-size: 12px !important;
    outline: none !important;
    width: 100% !important;
    box-sizing: border-box !important;
}
.select2-search--dropdown .select2-search__field:focus {
    border-color: #0d6efd !important;
    box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.15) !important;
}
.select2-results__option {
    padding: 6px 12px !important;
    font-size: 12.5px !important;
}
.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
    background-color: #0d6efd !important;
    color: #fff !important;
}
.select2-container--default .select2-results__option--selected {
    background-color: #e7f1ff !important;
    color: #0d6efd !important;
    font-weight: 600 !important;
}
</style>
<div class="container-fluid">
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="d-flex align-items-center gap-2">
            <h4 class="page-title fw-semibold fs-18 mb-0"><?= $page_title ?></h4>
            <?php if (hasPermission('Contractor Bill') || in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33, 17, 23, 31, 20, 32, 12])): ?>
                <a href="<?= $base_url ?>/con-contractor-bill-create" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i> New Bill
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Executive Management Approval Schedule Notice -->
    <?php include __DIR__ . '/../../layouts/management_approval_notice.php'; ?>

    <!-- Filter Toolbar: Tabs / Pills + Status Dropdown -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="d-flex flex-wrap align-items-center gap-2" id="statusPillsContainer">
            <button type="button" class="btn btn-sm <?= (strtolower($selectedStatus) === 'all') ? 'btn-secondary active' : 'btn-outline-secondary' ?> bill-filter-pill" data-status="All" onclick="filterBillTable('All', this)">
                <i class="bi bi-grid-fill me-1"></i> All <span class="badge bg-secondary ms-1 rounded-pill" id="pillCountAll"><?= $statusCounts['all'] ?></span>
            </button>

            <button type="button" class="btn btn-sm <?= (strtolower($selectedStatus) === 'draft') ? 'btn-warning active' : 'btn-outline-warning' ?> bill-filter-pill" data-status="Draft" onclick="filterBillTable('Draft', this)">
                <i class="bi bi-pencil-fill me-1"></i> Draft 
                <span class="badge bg-warning text-dark ms-1 rounded-pill" id="pillCountDraft"><?= $statusCounts['draft'] ?></span>
            </button>

            <button type="button" class="btn btn-sm <?= (strtolower($selectedStatus) === 'submitted') ? 'btn-info active' : 'btn-outline-info' ?> bill-filter-pill" data-status="Submitted" onclick="filterBillTable('Submitted', this)">
                <i class="bi bi-hourglass-split me-1"></i> Submitted 
                <span class="badge bg-info text-dark ms-1 rounded-pill" id="pillCountSubmitted"><?= $statusCounts['submitted'] ?></span>
            </button>

            <button type="button" class="btn btn-sm <?= (strtolower($selectedStatus) === 'verified') ? 'btn-primary active' : 'btn-outline-primary' ?> bill-filter-pill" data-status="Verified" onclick="filterBillTable('Verified', this)">
                <i class="bi bi-check-circle me-1"></i> PM Verified 
                <span class="badge bg-primary ms-1 rounded-pill" id="pillCountVerified"><?= $statusCounts['verified'] ?></span>
            </button>

            <button type="button" class="btn btn-sm <?= (in_array(strtolower($selectedStatus), ['qc approved', 'qc_approved'])) ? 'btn-secondary active' : 'btn-outline-secondary' ?> bill-filter-pill" data-status="Qc approved" onclick="filterBillTable('Qc approved', this)">
                <i class="bi bi-shield-check me-1"></i> QC Approved 
                <span class="badge bg-secondary ms-1 rounded-pill" id="pillCountQcApproved"><?= $statusCounts['qc_approved'] ?></span>
            </button>

            <button type="button" class="btn btn-sm <?= (strtolower($selectedStatus) === 'approved') ? 'btn-success active' : 'btn-outline-success' ?> bill-filter-pill" data-status="Approved" onclick="filterBillTable('Approved', this)">
                <i class="bi bi-check-circle-fill me-1"></i> Approved 
                <span class="badge bg-success ms-1 rounded-pill" id="pillCountApproved"><?= $statusCounts['approved'] ?></span>
            </button>

            <button type="button" class="btn btn-sm <?= (strtolower($selectedStatus) === 'rejected') ? 'btn-danger active' : 'btn-outline-danger' ?> bill-filter-pill" data-status="Rejected" onclick="filterBillTable('Rejected', this)">
                <i class="bi bi-x-circle-fill me-1"></i> Rejected 
                <span class="badge bg-danger ms-1 rounded-pill" id="pillCountRejected"><?= $statusCounts['rejected'] ?></span>
            </button>

            <button type="button" class="btn btn-sm <?= (strtolower($selectedStatus) === 'paid') ? 'btn-dark active' : 'btn-outline-dark' ?> bill-filter-pill" data-status="Paid" onclick="filterBillTable('Paid', this)">
                <i class="bi bi-cash me-1"></i> Paid 
                <span class="badge bg-dark ms-1 rounded-pill" id="pillCountPaid"><?= $statusCounts['paid'] ?></span>
            </button>
        </div>

        <div class="d-flex align-items-center gap-2">
            <label for="billStatusFilterSelect" class="small text-muted fw-bold mb-0 text-nowrap">
                <i class="bi bi-funnel-fill me-1"></i>Status Filter:
            </label>
            <select id="billStatusFilterSelect" class="form-select form-select-sm shadow-xs" style="min-width: 175px; font-weight: 600;" onchange="onStatusSelectChange(this.value)">
                <option value="All" <?= (strtolower($selectedStatus) === 'all') ? 'selected' : '' ?>>All Statuses (<?= $statusCounts['all'] ?>)</option>
                <option value="Draft" <?= (strtolower($selectedStatus) === 'draft') ? 'selected' : '' ?>>Draft (<?= $statusCounts['draft'] ?>)</option>
                <option value="Submitted" <?= (strtolower($selectedStatus) === 'submitted') ? 'selected' : '' ?>>Submitted (<?= $statusCounts['submitted'] ?>)</option>
                <option value="Verified" <?= (strtolower($selectedStatus) === 'verified') ? 'selected' : '' ?>>PM Verified (<?= $statusCounts['verified'] ?>)</option>
                <option value="Qc approved" <?= (in_array(strtolower($selectedStatus), ['qc approved', 'qc_approved'])) ? 'selected' : '' ?>>QC Approved (<?= $statusCounts['qc_approved'] ?>)</option>
                <option value="Approved" <?= (strtolower($selectedStatus) === 'approved') ? 'selected' : '' ?>>Approved (<?= $statusCounts['approved'] ?>)</option>
                <option value="Rejected" <?= (strtolower($selectedStatus) === 'rejected') ? 'selected' : '' ?>>Rejected (<?= $statusCounts['rejected'] ?>)</option>
                <option value="Paid" <?= (strtolower($selectedStatus) === 'paid') ? 'selected' : '' ?>>Paid (<?= $statusCounts['paid'] ?>)</option>
            </select>
        </div>
    </div>

    <!-- Card with Enhanced Filter Toolbar, Table & AJAX Pagination -->
    <div class="card custom-card shadow-sm border">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2.5">
            <h6 class="card-title mb-0 d-flex align-items-center">
                <i class="bi bi-receipt me-2 text-primary fs-16"></i> 
                <span>All Contractor Bills</span>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill ms-2" id="totalRecordsBadge"><?= $totalRecords ?></span>
            </h6>

            <div class="d-flex align-items-center gap-2">
                <label for="perPageSelect" class="form-label mb-0 text-muted small fw-semibold text-nowrap">Show:</label>
                <select id="perPageSelect" class="form-select form-select-sm" style="width: 80px;" onchange="loadBillsPage(1)">
                    <option value="10" <?= ($perPage == 10) ? 'selected' : '' ?>>10</option>
                    <option value="25" <?= ($perPage == 25 || empty($perPage)) ? 'selected' : '' ?>>25</option>
                    <option value="50" <?= ($perPage == 50) ? 'selected' : '' ?>>50</option>
                    <option value="100" <?= ($perPage == 100) ? 'selected' : '' ?>>100</option>
                    <option value="all" <?= ($perPage === 'all') ? 'selected' : '' ?>>All</option>
                </select>
            </div>
        </div>

        <!-- Filter Controls: Search, Project-wise, Creator (Submitted by), Contractor -->
        <div class="card-body border-bottom bg-light bg-opacity-50 py-2.5 px-3">
            <div class="row g-2 align-items-center">
                <!-- 1. Universal Search (Bill #, Project, Contractor, Creator) -->
                <div class="col-xl-4 col-lg-4 col-md-12">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-muted border-end-0">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" id="billSearchInput" class="form-control border-start-0 ps-0" placeholder="Search Bill #, Project, Contractor, Creator..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                        <button class="btn btn-outline-secondary bg-white border-start-0 border-end-0" type="button" id="clearSearchBtn" title="Clear Search" style="<?= empty($filters['search']) ? 'display: none;' : '' ?>" onclick="clearSearchInput()">
                            <i class="bi bi-x-circle text-muted"></i>
                        </button>
                        <button class="btn btn-success" type="button" onclick="loadBillsPage(1)" title="Search">
                            <i class="bi bi-search me-1"></i>Search
                        </button>
                    </div>
                </div>

                <!-- 2. Project Wise Filter (Searchable Select2) -->
                <div class="col-xl-4 col-lg-4 col-md-6">
                    <select id="projectFilterSelect" class="form-select form-select-sm select2 shadow-xs" data-placeholder="All Projects (<?= count($projects ?? []) ?>)" style="width: 100%;">
                        <option value="">All Projects (<?= count($projects ?? []) ?>)</option>
                        <?php if (!empty($projects)): ?>
                            <?php foreach ($projects as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= (!empty($selectedProjectId) && (int)$selectedProjectId === (int)$p['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- 3. Contractor / Trade Filter (Searchable Select2) -->
                <div class="col-xl-3 col-l col-md-6">
                    <select id="contractorFilterSelect" class="form-select form-select-sm select2 shadow-xs" data-placeholder="All Contractors & Trades (<?= count($contractors ?? []) ?>)" style="width: 100%;">
                        <option value="">All Contractors & Trades (<?= count($contractors ?? []) ?>)</option>
                        <?php if (!empty($contractors)): ?>
                            <?php foreach ($contractors as $c): ?>
                                <?php
                                    $tradeLabel = !empty($c['trade']) ? ucwords(str_replace('_', ' ', $c['trade'])) : (!empty($c['role']) ? $c['role'] : '');
                                    $displayTxt = htmlspecialchars($c['name']) . (!empty($tradeLabel) ? ' (' . htmlspecialchars($tradeLabel) . ')' : '');
                                    $isSelected = (!empty($selectedContractorId) && (int)$selectedContractorId === (int)$c['id']);
                                ?>
                                <option value="<?= $c['id'] ?>" <?= $isSelected ? 'selected' : '' ?>>
                                    <?= $displayTxt ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- 4. Reset Button -->
                <div class="col-xl-1 col-lg-1 col-md-12 d-flex justify-content-end">
                    <button type="button" id="resetFiltersBtn" class="btn btn-sm btn-outline-danger w-100 d-flex align-items-center justify-content-center gap-1" onclick="resetAllFilters()" title="Reset All Filters">
                        <i class="bi bi-arrow-counterclockwise"></i> <span>Reset</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Table Container with Loading Overlay -->
        <div class="card-body p-0 position-relative">
            <div id="billTableLoadingOverlay" class="position-absolute top-0 start-0 w-100 h-100 bg-white bg-opacity-75 d-none align-items-center justify-content-center" style="z-index: 10; min-height: 220px;">
                <div class="d-flex flex-column align-items-center gap-2">
                    <div class="spinner-border text-primary" role="status" style="width: 2.2rem; height: 2.2rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <small class="text-muted fw-semibold">Loading bills...</small>
                </div>
            </div>

            <div class="table-responsive">
                <table id="billTable" class="table table-bordered table-hover text-nowrap mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 80px;">Bill #</th>
                            <th>Project</th>
                            <th>Contractor</th>
                            <th>Bill Type</th>
                            <th>Bill Date</th>
                            <th class="text-end">Amount (৳)</th>
                            <th>Status</th>
                            <th>Submitted By</th>
                            <th>Approved By</th>
                            <th>Payment By</th>
                            <th class="text-center" style="width: 100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="billTableBody">
                        <?php include __DIR__ . '/partials/bill_rows.php'; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination and Summary Footer -->
        <div class="card-footer py-2.5 px-3 bg-white border-top">
            <div id="billPaginationContainer">
                <?= $paginationHtml ?>
            </div>
        </div>
    </div>
</div>

<style>
.custom-card .select2-container--default .select2-selection--single {
    height: 31px;
    border-color: #dee2e6;
    display: flex;
    align-items: center;
}
.custom-card .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 31px;
    font-size: 13px;
    padding-left: 8px;
    color: #333;
}
.custom-card .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 31px;
}
.bill-filter-pill.active {
    box-shadow: 0 2px 4px rgba(0,0,0,0.12);
}
#billTable th {
    vertical-align: middle;
    font-size: 13px;
    font-weight: 600;
}
#billTable td {
    vertical-align: middle;
    font-size: 13px;
}
.pagination-sm .page-link {
    font-size: 12.5px;
}
</style>

<script>
(function() {
    let searchDebounceTimer = null;
    let activeFetchController = null;
    let currentStatus = "<?= htmlspecialchars($selectedStatus) ?>";

    // 1. Status Filter Pills Handling
    window.filterBillTable = function(status, btnElement) {
        currentStatus = status || 'All';

        // Update pills UI
        document.querySelectorAll('.bill-filter-pill').forEach(function(pill) {
            pill.classList.remove('active');
            if (pill.classList.contains('btn-secondary')) pill.classList.replace('btn-secondary', 'btn-outline-secondary');
            if (pill.classList.contains('btn-danger')) pill.classList.replace('btn-danger', 'btn-outline-danger');
            if (pill.classList.contains('btn-success')) pill.classList.replace('btn-success', 'btn-outline-success');
            if (pill.classList.contains('btn-primary')) pill.classList.replace('btn-primary', 'btn-outline-primary');
            if (pill.classList.contains('btn-info')) pill.classList.replace('btn-info', 'btn-outline-info');
            if (pill.classList.contains('btn-dark')) pill.classList.replace('btn-dark', 'btn-outline-dark');
            if (pill.classList.contains('btn-warning')) pill.classList.replace('btn-warning', 'btn-outline-warning');
        });

        if (btnElement) {
            btnElement.classList.add('active');
            if (btnElement.classList.contains('btn-outline-secondary')) btnElement.classList.replace('btn-outline-secondary', 'btn-secondary');
            if (btnElement.classList.contains('btn-outline-danger')) btnElement.classList.replace('btn-outline-danger', 'btn-danger');
            if (btnElement.classList.contains('btn-outline-success')) btnElement.classList.replace('btn-outline-success', 'btn-success');
            if (btnElement.classList.contains('btn-outline-primary')) btnElement.classList.replace('btn-outline-primary', 'btn-primary');
            if (btnElement.classList.contains('btn-outline-info')) btnElement.classList.replace('btn-outline-info', 'btn-info');
            if (btnElement.classList.contains('btn-outline-dark')) btnElement.classList.replace('btn-outline-dark', 'btn-dark');
            if (btnElement.classList.contains('btn-outline-warning')) btnElement.classList.replace('btn-outline-warning', 'btn-warning');
        }

        // Keep dropdown in sync
        const statusSelect = document.getElementById('billStatusFilterSelect');
        if (statusSelect && statusSelect.value !== currentStatus) {
            statusSelect.value = currentStatus;
        }

        loadBillsPage(1);
    };

    window.onStatusSelectChange = function(val) {
        const matchingBtn = document.querySelector('.bill-filter-pill[data-status="' + val + '"]');
        filterBillTable(val, matchingBtn);
    };

    // 2. Clear Search input
    window.clearSearchInput = function() {
        const searchInput = document.getElementById('billSearchInput');
        if (searchInput) {
            searchInput.value = '';
            document.getElementById('clearSearchBtn').style.display = 'none';
            loadBillsPage(1);
        }
    };

    // 3. Reset All Filters
    window.resetAllFilters = function() {
        const searchInput = document.getElementById('billSearchInput');
        if (searchInput) searchInput.value = '';

        const clearBtn = document.getElementById('clearSearchBtn');
        if (clearBtn) clearBtn.style.display = 'none';

        if (typeof jQuery !== 'undefined' && typeof jQuery.fn.select2 === 'function') {
            jQuery('#projectFilterSelect').val('').trigger('change.select2');
            jQuery('#contractorFilterSelect').val('').trigger('change.select2');
        }

        const projSelect = document.getElementById('projectFilterSelect');
        if (projSelect) {
            projSelect.value = '';
        }

        const contractorSelect = document.getElementById('contractorFilterSelect');
        if (contractorSelect) {
            contractorSelect.value = '';
        }

        const perPageSelect = document.getElementById('perPageSelect');
        if (perPageSelect) perPageSelect.value = '25';

        const allPill = document.querySelector('.bill-filter-pill[data-status="All"]');
        filterBillTable('All', allPill);
    };

    // 4. Main AJAX Pagination & Search function
    window.loadBillsPage = function(page) {
        page = page || 1;

        const searchInput      = document.getElementById('billSearchInput');
        const projectSelect    = document.getElementById('projectFilterSelect');
        const contractorSelect = document.getElementById('contractorFilterSelect');
        const perPageSelect    = document.getElementById('perPageSelect');
        const loadingOverlay   = document.getElementById('billTableLoadingOverlay');

        const searchValue      = searchInput ? searchInput.value.trim() : '';
        const projectId        = projectSelect ? projectSelect.value : '';
        const contractorId     = contractorSelect ? contractorSelect.value : '';
        const perPage          = perPageSelect ? perPageSelect.value : '25';

        // Toggle clear search button
        const clearBtn = document.getElementById('clearSearchBtn');
        if (clearBtn) {
            clearBtn.style.display = searchValue !== '' ? 'inline-block' : 'none';
        }

        // Cancel previous request if still running
        if (activeFetchController) {
            activeFetchController.abort();
        }
        activeFetchController = new AbortController();

        // Show loading overlay
        if (loadingOverlay) {
            loadingOverlay.classList.remove('d-none');
            loadingOverlay.classList.add('d-flex');
        }

        // Build query params
        const params = new URLSearchParams();
        params.set('ajax', '1');
        params.set('page', page);
        params.set('per_page', perPage);
        if (searchValue)   params.set('search', searchValue);
        if (projectId)     params.set('project_id', projectId);
        if (contractorId)  params.set('contractor_id', contractorId);
        if (currentStatus && currentStatus !== 'All') params.set('status', currentStatus);

        const url = (window.base_url || '<?= $base_url ?>') + '/con-contractor-bills?' + params.toString();

        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            signal: activeFetchController.signal
        })
        .then(function(res) {
            return res.json();
        })
        .then(function(data) {
            if (data && data.success) {
                // Update table body
                const tbody = document.getElementById('billTableBody');
                if (tbody) tbody.innerHTML = data.table_html;

                // Update pagination
                const pagContainer = document.getElementById('billPaginationContainer');
                if (pagContainer) pagContainer.innerHTML = data.pagination_html;

                // Update total count badge
                const totalBadge = document.getElementById('totalRecordsBadge');
                if (totalBadge) totalBadge.textContent = data.total_records;

                // Update status counts on pills if provided
                if (data.status_counts) {
                    const sc = data.status_counts;
                    if (document.getElementById('pillCountAll'))         document.getElementById('pillCountAll').textContent = sc.all ?? 0;
                    if (document.getElementById('pillCountDraft'))       document.getElementById('pillCountDraft').textContent = sc.draft ?? 0;
                    if (document.getElementById('pillCountSubmitted'))   document.getElementById('pillCountSubmitted').textContent = sc.submitted ?? 0;
                    if (document.getElementById('pillCountVerified'))    document.getElementById('pillCountVerified').textContent = sc.verified ?? 0;
                    if (document.getElementById('pillCountQcApproved'))  document.getElementById('pillCountQcApproved').textContent = sc.qc_approved ?? 0;
                    if (document.getElementById('pillCountApproved'))    document.getElementById('pillCountApproved').textContent = sc.approved ?? 0;
                    if (document.getElementById('pillCountRejected'))    document.getElementById('pillCountRejected').textContent = sc.rejected ?? 0;
                    if (document.getElementById('pillCountPaid'))        document.getElementById('pillCountPaid').textContent = sc.paid ?? 0;
                }

                // Update browser URL state without page refresh
                const stateParams = new URLSearchParams(params);
                stateParams.delete('ajax');
                const newUrl = window.location.pathname + (stateParams.toString() ? '?' + stateParams.toString() : '');
                window.history.replaceState({ path: newUrl }, '', newUrl);
            }
        })
        .catch(function(err) {
            if (err.name !== 'AbortError') {
                console.error("Error loading contractor bills:", err);
            }
        })
        .finally(function() {
            if (loadingOverlay) {
                loadingOverlay.classList.remove('d-flex');
                loadingOverlay.classList.add('d-none');
            }
        });
    };

    // 5. Safe Initializer for Select2 searchable dropdowns & DOM listeners
    function initModuleScripts() {
        // Initialize Select2 with search enabled for Project and Contractor dropdowns
        if (typeof jQuery !== 'undefined' && typeof jQuery.fn.select2 === 'function') {
            const $proj = jQuery('#projectFilterSelect');
            const $cont = jQuery('#contractorFilterSelect');

            if (!$proj.hasClass('select2-hidden-accessible')) {
                $proj.select2({
                    width: '100%',
                    placeholder: $proj.data('placeholder') || 'All Projects',
                    allowClear: true,
                    dropdownParent: jQuery('body')
                });
            }

            if (!$cont.hasClass('select2-hidden-accessible')) {
                $cont.select2({
                    width: '100%',
                    placeholder: $cont.data('placeholder') || 'All Contractors & Trades',
                    allowClear: true,
                    dropdownParent: jQuery('body')
                });
            }

            // Bind change and select2:clear events to reload page 1
            $proj.off('change.billFilter select2:clear.billFilter').on('change.billFilter select2:clear.billFilter', function() {
                window.loadBillsPage(1);
            });

            $cont.off('change.billFilter select2:clear.billFilter').on('change.billFilter select2:clear.billFilter', function() {
                window.loadBillsPage(1);
            });

            // Auto-focus search input inside Select2 dropdown when opened
            jQuery(document).off('select2:open.billAutoSearch').on('select2:open.billAutoSearch', function() {
                setTimeout(function() {
                    const searchField = document.querySelector('.select2-container--open .select2-search__field');
                    if (searchField) {
                        searchField.focus();
                    }
                }, 10);
            });
        } else {
            // Fallback native listener if Select2 is not yet initialized
            const pSel = document.getElementById('projectFilterSelect');
            if (pSel && !pSel.dataset.listenerBound) {
                pSel.dataset.listenerBound = 'true';
                pSel.addEventListener('change', function() {
                    window.loadBillsPage(1);
                });
            }

            const cSel = document.getElementById('contractorFilterSelect');
            if (cSel && !cSel.dataset.listenerBound) {
                cSel.dataset.listenerBound = 'true';
                cSel.addEventListener('change', function() {
                    window.loadBillsPage(1);
                });
            }
        }

        const searchInput = document.getElementById('billSearchInput');
        if (searchInput && !searchInput.dataset.listenerBound) {
            searchInput.dataset.listenerBound = 'true';
            searchInput.addEventListener('input', function() {
                clearTimeout(searchDebounceTimer);
                searchDebounceTimer = setTimeout(function() {
                    window.loadBillsPage(1);
                }, 350);
            });

            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(searchDebounceTimer);
                    window.loadBillsPage(1);
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModuleScripts);
    } else {
        initModuleScripts();
    }
    // Also run on window load and jQuery ready to guarantee Select2 setup
    window.addEventListener('load', initModuleScripts);
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ready(initModuleScripts);
    }
})();
</script>
