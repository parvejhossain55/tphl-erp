<?php
$base_url   = $GLOBALS['base_url'];
$page_title = $page_title ?? 'Vendor Payment Requests';
$canProcessPayment = $canProcessPayment ?? false;
$canApprove = $canApprove ?? false;
?>
<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h3 class="page-title fw-semibold fs-18 mb-1"><?= $page_title ?></h3>
            <div class="d-flex">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item active">Vendor Payment Requests</li>
                </ol>
            </div>
        </div>
        <div class="my-auto">
            <a href="<?= $base_url ?>/con-vendor-payment-create" class="btn btn-primary btn-sm shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> New Request
            </a>
        </div>
    </div>

    <!-- Executive Management Approval Schedule Notice -->
    <?php include __DIR__ . '/../../layouts/management_approval_notice.php'; ?>

    <!-- Quick Status Filter Pills / KPI Badges -->
    <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
        <a href="<?= $base_url ?>/con-vendor-payment-requests" 
           class="btn btn-sm <?= empty($filters['status']) ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3 fw-semibold">
            All Requests <span class="badge bg-light text-dark ms-1"><?= number_format($filterCounts['total'] ?? 0) ?></span>
        </a>
        <a href="<?= $base_url ?>/con-vendor-payment-requests?status=pending_qc" 
           class="btn btn-sm <?= (($filters['status'] ?? '') === 'pending_qc') ? 'btn-warning text-dark' : 'btn-outline-warning text-dark' ?> rounded-pill px-3 fw-semibold">
            <i class="bi bi-hourglass-split me-1"></i> Pending QC 
            <span class="badge bg-warning text-dark ms-1"><?= number_format($filterCounts['pending_qc'] ?? 0) ?></span>
        </a>
        <a href="<?= $base_url ?>/con-vendor-payment-requests?status=pending_apex" 
           class="btn btn-sm <?= (($filters['status'] ?? '') === 'pending_apex') ? 'btn-info text-white' : 'btn-outline-info' ?> rounded-pill px-3 fw-semibold">
            <i class="bi bi-shield-check me-1"></i> Pending Apex/Mgmt 
            <span class="badge bg-info text-white ms-1"><?= number_format($filterCounts['pending_apex'] ?? 0) ?></span>
        </a>
        <a href="<?= $base_url ?>/con-vendor-payment-requests?status=apex_approved" 
           class="btn btn-sm <?= (in_array(($filters['status'] ?? ''), ['apex_approved', 'approved'])) ? 'btn-success' : 'btn-outline-success' ?> rounded-pill px-3 fw-semibold">
            <i class="bi bi-check-circle me-1"></i> Management Approved 
            <span class="badge bg-success text-white ms-1"><?= number_format($filterCounts['apex_approved'] ?? 0) ?></span>
        </a>
        <a href="<?= $base_url ?>/con-vendor-payment-requests?status=ready_to_pay" 
           class="btn btn-sm <?= (in_array(($filters['status'] ?? ''), ['ready_to_pay', 'partially_paid'])) ? 'btn-primary' : 'btn-outline-primary' ?> rounded-pill px-3 fw-semibold">
            <i class="bi bi-wallet2 me-1"></i> Ready to Pay (Partially Paid) 
            <span class="badge bg-primary text-white ms-1"><?= number_format($filterCounts['ready_to_pay'] ?? 0) ?></span>
        </a>
        <a href="<?= $base_url ?>/con-vendor-payment-requests?status=fully_paid" 
           class="btn btn-sm <?= (($filters['status'] ?? '') === 'fully_paid') ? 'btn-dark' : 'btn-outline-dark' ?> rounded-pill px-3 fw-semibold">
            <i class="bi bi-cash-stack me-1"></i> Fully Paid 
            <span class="badge bg-dark text-white ms-1"><?= number_format($filterCounts['fully_paid'] ?? 0) ?></span>
        </a>
    </div>

    <!-- Multi-Criteria Search & Filter Toolbar -->
    <div class="card custom-card shadow-sm border-0 mb-3 bg-white">
        <div class="card-body p-3">
            <form method="GET" action="<?= $base_url ?>/con-vendor-payment-requests" id="vprFilterForm">
                <input type="hidden" name="per_page" id="vprHiddenPerPage" value="<?= (int)($pagination['perPage'] ?? 25) ?>">
                <div class="row g-2 align-items-end">
                    <!-- Project Filter -->
                    <div class="col-xl-3 col-lg-3 col-md-6">
                        <label class="form-label text-uppercase fw-bold text-secondary" style="font-size: 11px;">
                            Project Scope
                        </label>
                        <select name="project_id" class="form-control form-control-sm select2">
                            <option value="">All Projects</option>
                            <?php foreach ($projectsList ?? [] as $proj): ?>
                                <option value="<?= $proj['id'] ?>" <?= ((string)($filters['project_id'] ?? '') === (string)$proj['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($proj['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Material Category Filter -->
                    <div class="col-xl-2 col-lg-3 col-md-6">
                        <label class="form-label text-uppercase fw-bold text-secondary" style="font-size: 11px;">
                            Material Category
                        </label>
                        <select name="category_id" class="form-control form-control-sm select2">
                            <option value="">All Categories</option>
                            <?php foreach ($materialCategories ?? [] as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ((string)($filters['category_id'] ?? '') === (string)$cat['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Vendor Filter -->
                    <div class="col-xl-2 col-lg-3 col-md-6">
                        <label class="form-label text-uppercase fw-bold text-secondary" style="font-size: 11px;">
                            Vendor
                        </label>
                        <select name="vendor_id" class="form-control form-control-sm select2">
                            <option value="">All Vendors</option>
                            <?php foreach ($vendorsList ?? [] as $vnd): ?>
                                <?php
                                $vComp = trim($vnd['company_name'] ?? '');
                                $vName = trim($vnd['name'] ?? '');
                                if (!empty($vComp) && !empty($vName) && strcasecmp($vComp, $vName) !== 0) {
                                    $vLabel = $vComp . ' (' . $vName . ')';
                                } else {
                                    $vLabel = !empty($vComp) ? $vComp : $vName;
                                }
                                ?>
                                <option value="<?= $vnd['id'] ?>" <?= ((string)($filters['vendor_id'] ?? '') === (string)$vnd['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($vLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Workflow & Payment Status Filter -->
                    <div class="col-xl-2 col-lg-3 col-md-6">
                        <label class="form-label text-uppercase fw-bold text-secondary" style="font-size: 11px;">
                            Status / Apex Payment
                        </label>
                        <select name="status" class="form-control form-control-sm select2">
                            <option value="">All Statuses</option>
                            <option value="ready_to_pay" <?= (in_array(($filters['status'] ?? ''), ['ready_to_pay', 'partially_paid'])) ? 'selected' : '' ?>>Ready to Pay (Partially Paid)</option>
                            <option value="apex_approved" <?= (in_array(($filters['status'] ?? ''), ['apex_approved', 'approved'])) ? 'selected' : '' ?>>Management / Apex Approved</option>
                            <option value="fully_paid" <?= (($filters['status'] ?? '') === 'fully_paid') ? 'selected' : '' ?>>Fully Paid</option>
                            <option value="pending" <?= (($filters['status'] ?? '') === 'pending' || ($filters['status'] ?? '') === 'all_pending') ? 'selected' : '' ?>>All Pending (QC & Apex)</option>
                            <option value="pending_qc" <?= (($filters['status'] ?? '') === 'pending_qc') ? 'selected' : '' ?>>Pending QC</option>
                            <option value="pending_apex" <?= (($filters['status'] ?? '') === 'pending_apex') ? 'selected' : '' ?>>Pending Apex / Mgmt</option>
                            <option value="rejected" <?= (($filters['status'] ?? '') === 'rejected') ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>

                    <!-- Search Input & Buttons -->
                    <div class="col-xl-3 col-lg-12 col-md-12">
                        <label class="form-label text-uppercase fw-bold text-secondary" style="font-size: 11px;">
                            Keyword Search
                        </label>
                        <div class="input-group input-group-sm" style="height: 36px;">
                            <input type="text" name="search" class="form-control" placeholder="Search Bill #, Invoice #, PO #, Vendor, Requester..." 
                                   value="<?= htmlspecialchars($filters['search'] ?? '') ?>" style="font-size: 12.5px;">
                            <button type="submit" class="btn btn-primary px-3 fw-semibold">
                                Filter
                            </button>
                            <?php if (!empty(array_filter($filters ?? []))): ?>
                                <a href="<?= $base_url ?>/con-vendor-payment-requests" class="btn btn-outline-danger" title="Clear all filters">
                                    Reset
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>


    <!-- Vendor Payment Requests List -->
    <div class="card custom-card shadow-sm" id="vprListCard">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 bg-light">
            <div>
                <h6 class="card-title mb-0 fw-bold text-dark">
                    <i class="bi bi-card-list me-2 text-primary"></i> Vendor Payment Requests & Disbursal List
                </h6>
                <small class="text-muted">Track payment request approval workflow, management approvals, and accounts disbursement records.</small>
            </div>
        </div>

        <!-- Table Top Toolbar: Show Entries + Instant Live Search -->
        <div class="px-3 pt-3 pb-2 d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom bg-white">
            <div class="d-flex align-items-center gap-2 fs-13">
                <label for="vprPerPageSelect" class="mb-0 text-muted fw-semibold">Show</label>
                <select id="vprPerPageSelect" class="form-select form-select-sm" style="width: auto; min-width: 72px;">
                    <?php foreach ([10, 25, 50, 100] as $ppOpt): ?>
                        <option value="<?= $ppOpt ?>" <?= ((int)($pagination['perPage'] ?? 25) === $ppOpt) ? 'selected' : '' ?>><?= $ppOpt ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="text-muted">entries</span>
            </div>

            <div class="d-flex align-items-center gap-2" style="min-width: 280px; max-width: 420px; width: 100%;">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white text-muted border-end-0">
                        <i class="fa-solid fa-magnifying-glass" id="vprSearchIcon"></i>
                        <span class="spinner-border spinner-border-sm text-primary d-none" id="vprSearchSpinner" role="status"></span>
                    </span>
                    <input type="text" id="vprLiveSearch" class="form-control border-start-0"
                           placeholder="Search any key (VPR #, Bill #, PO #, Project, Vendor, Material, Status, Amount)..."
                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>" autocomplete="off">
                    <button type="button" id="vprSearchClear" class="btn btn-outline-secondary <?= empty($filters['search']) ? 'd-none' : '' ?>" title="Clear search">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body p-0 position-relative">
            <div id="vprLoadingOverlay" class="d-none position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(255,255,255,0.65); z-index: 10;">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
            <div class="table-responsive">
                <table id="vprTable" class="table table-bordered table-hover align-middle text-nowrap mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" style="width: 40px;">#</th>
                            <th>Req ID &amp; Bill #</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Project, PO & Category</th>
                            <th>Vendor</th>
                            <th class="text-end">Invoice (৳)</th>
                            <th class="text-end">Net Payable (৳)</th>
                            <th class="text-end">Paid (৳)</th>
                            <th class="text-end">Due (৳)</th>
                            <th>Status</th>
                            <th>Submitted By</th>
                            <th>Approved By</th>
                            <th>Payment By (Accounts)</th>
                            <th class="text-center">Attachment</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="vprTableBody">
                        <?php include __DIR__ . '/partials/payment-rows.php'; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Server-Side Pagination Footer -->
        <div class="card-footer bg-white py-3 border-top" id="vprPaginationContainer">
            <?php include __DIR__ . '/partials/payment-pagination.php'; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var baseUrl       = <?= json_encode($base_url . '/con-vendor-payment-requests') ?>;
    var tbody         = document.getElementById('vprTableBody');
    var pagContainer  = document.getElementById('vprPaginationContainer');
    var liveSearch    = document.getElementById('vprLiveSearch');
    var clearBtn      = document.getElementById('vprSearchClear');
    var perPageSelect = document.getElementById('vprPerPageSelect');
    var filterForm    = document.getElementById('vprFilterForm');
    var topSearchInp  = filterForm ? filterForm.querySelector('input[name="search"]') : null;
    var overlay       = document.getElementById('vprLoadingOverlay');
    var searchIcon    = document.getElementById('vprSearchIcon');
    var searchSpinner = document.getElementById('vprSearchSpinner');

    var currentPage   = <?= (int)($pagination['currentPage'] ?? 1) ?>;
    var currentPerPage = <?= (int)($pagination['perPage'] ?? 25) ?>;
    var debounceTimer = null;
    var activeController = null;

    function getParams(page) {
        var params = new URLSearchParams();
        if (filterForm) {
            var proj = filterForm.querySelector('select[name="project_id"]');
            var cat  = filterForm.querySelector('select[name="category_id"]');
            var vnd  = filterForm.querySelector('select[name="vendor_id"]');
            var st   = filterForm.querySelector('select[name="status"]');
            if (proj && proj.value) params.set('project_id', proj.value);
            if (cat && cat.value)   params.set('category_id', cat.value);
            if (vnd && vnd.value)   params.set('vendor_id', vnd.value);
            if (st && st.value)     params.set('status', st.value);
        }
        var q = liveSearch ? liveSearch.value.trim() : '';
        if (q) params.set('search', q);
        params.set('per_page', currentPerPage);
        params.set('page', page || 1);
        return params;
    }

    function setLoading(isLoading) {
        if (overlay) overlay.classList.toggle('d-none', !isLoading);
        if (searchIcon) searchIcon.classList.toggle('d-none', isLoading);
        if (searchSpinner) searchSpinner.classList.toggle('d-none', !isLoading);
    }

    function fetchPage(page, scrollToTable) {
        currentPage = page || 1;
        var params = getParams(currentPage);

        // Keep browser URL synced without reloading
        var cleanUrl = baseUrl + (params.toString() ? '?' + params.toString() : '');
        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '', cleanUrl);
        }

        // Sync top filter search input
        if (topSearchInp && liveSearch && topSearchInp.value !== liveSearch.value) {
            topSearchInp.value = liveSearch.value;
        }
        if (clearBtn && liveSearch) {
            clearBtn.classList.toggle('d-none', liveSearch.value.trim() === '');
        }

        params.set('ajax', '1');

        if (activeController && typeof activeController.abort === 'function') {
            activeController.abort();
        }
        activeController = (typeof AbortController !== 'undefined') ? new AbortController() : null;

        setLoading(true);
        fetch(baseUrl + '?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: activeController ? activeController.signal : undefined
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data && data.success) {
                tbody.innerHTML = data.rows_html;
                pagContainer.innerHTML = data.pagination_html;
                currentPage = data.current_page || 1;
                if (scrollToTable) {
                    var card = document.getElementById('vprListCard');
                    if (card) card.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        })
        .catch(function (err) {
            if (err && err.name === 'AbortError') return;
            console.error('VPR pagination load error:', err);
        })
        .finally(function () {
            setLoading(false);
        });
    }

    // Live search on typing (300ms debounce)
    if (liveSearch) {
        liveSearch.addEventListener('input', function () {
            if (clearBtn) clearBtn.classList.toggle('d-none', this.value.trim() === '');
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                fetchPage(1, false);
            }, 300);
        });
        liveSearch.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(debounceTimer);
                fetchPage(1, false);
            }
        });
    }

    // Clear search button
    if (clearBtn && liveSearch) {
        clearBtn.addEventListener('click', function () {
            liveSearch.value = '';
            clearBtn.classList.add('d-none');
            liveSearch.focus();
            fetchPage(1, false);
        });
    }

    // Keep top search bar synced with live search
    if (topSearchInp && liveSearch) {
        topSearchInp.addEventListener('input', function () {
            liveSearch.value = this.value;
            if (clearBtn) clearBtn.classList.toggle('d-none', this.value.trim() === '');
        });
    }

    // Change Show entries per page
    if (perPageSelect) {
        perPageSelect.addEventListener('change', function () {
            currentPerPage = parseInt(this.value, 10) || 25;
            var hiddenPp = document.getElementById('vprHiddenPerPage');
            if (hiddenPp) hiddenPp.value = currentPerPage;
            fetchPage(1, false);
        });
    }

    // Intercept pagination link clicks for instant AJAX page switching
    if (pagContainer) {
        pagContainer.addEventListener('click', function (e) {
            var link = e.target.closest('.vpr-page-link');
            if (!link) return;
            var li = link.closest('.page-item');
            if (li && (li.classList.contains('disabled') || li.classList.contains('active'))) {
                e.preventDefault();
                return;
            }
            var targetPage = parseInt(link.getAttribute('data-page'), 10);
            if (targetPage > 0) {
                e.preventDefault();
                fetchPage(targetPage, true);
            }
        });
    }
});
</script>

<!-- Edit Vendor Payment Request Modal (Before 1st Approval — List View) -->
<div class="modal custom-modal fade" id="editVprIndexModal" tabindex="-1" aria-labelledby="editVprIndexModalLabel" aria-hidden="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header pb-3 mb-2 border-bottom">
                <h5 class="modal-title fw-bold text-dark" id="editVprIndexModalLabel">
                    <i class="fa-solid fa-pen-to-square me-2 text-warning"></i> Edit Vendor Payment Request <span id="edit_vpr_title_id"></span>
                </h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body p-0">
                <form method="POST" action="" id="editVprIndexForm">
                    <input type="hidden" name="redirect_to" value="index">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Payment Type <span class="text-danger">*</span></label>
                        <select name="payment_type" id="edit_vpr_type" class="form-select" required>
                            <option value="Advance">Advance</option>
                            <option value="Partial">Partial</option>
                            <option value="Final">Final</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Invoice Number</label>
                        <input type="text" name="invoice_number" id="edit_vpr_inv" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Invoice Amount (৳) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="1" name="invoice_amount" id="edit_vpr_amt" class="form-control" required>
                    </div>
                    <div class="modal-footer px-0 pb-0 pt-3 border-top d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">
                            <i class="fa-solid fa-save me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
(function() {
    function initVprIndexEditModal() {
        if (typeof jQuery === 'undefined' || typeof bootstrap === 'undefined') {
            setTimeout(initVprIndexEditModal, 50);
            return;
        }
        jQuery(function($) {
            if ($('#editVprIndexModal').length && $('#editVprIndexModal').parent().prop('tagName') !== 'BODY') {
                $('#editVprIndexModal').appendTo('body');
            }
            function populateVpr(btn) {
                var raw = btn.attr('data-item');
                if (!raw) return;
                try {
                    var d = (typeof raw === 'object') ? raw : JSON.parse(raw);
                    $('#editVprIndexForm').attr('action', '<?= $GLOBALS['base_url'] ?>/con-vendor-payment-update/' + (d.id || ''));
                    $('#edit_vpr_title_id').text('#' + (d.id || ''));
                    $('#edit_vpr_type').val(d.payment_type || 'Final');
                    $('#edit_vpr_inv').val(d.invoice_number || '');
                    $('#edit_vpr_amt').val(d.invoice_amount || '');
                } catch (e) {}
            }
            $('#editVprIndexModal').on('show.bs.modal', function(e) {
                if (e.relatedTarget) populateVpr($(e.relatedTarget));
            });
            $(document).on('click', '.btn-edit-vpr-row', function() {
                populateVpr($(this));
            });
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initVprIndexEditModal);
    } else {
        initVprIndexEditModal();
    }
})();
</script>
