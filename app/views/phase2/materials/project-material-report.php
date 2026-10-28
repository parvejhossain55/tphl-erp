<?php
$base_url = $GLOBALS['base_url'] ?? '';
/**
 * Project-wise Material Report View
 * Route: con-project-materials-report
 * Controller: Phase2\Con_MaterialController@projectMaterialReport
 */

function fmtCurrency($val) {
    return '&#2547;' . number_format((float)$val, 2);
}
function fmtLakhReport($val) {
    if ($val >= 100000) return '&#2547;' . number_format($val / 100000, 2) . 'L';
    if ($val >= 1000)   return '&#2547;' . number_format($val / 1000, 2) . 'K';
    return '&#2547;' . number_format($val, 2);
}
?>

<main id="main" class="main">
    <div class="pagetitle d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fw-bold fs-4 mb-1"><?= htmlspecialchars($page_title) ?></h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/con-dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item">Materials & Inventory</li>
                    <li class="breadcrumb-item active">Project Material Report</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2 mt-2 mt-sm-0">
            <button class="btn btn-outline-secondary btn-sm shadow-sm" onclick="window.print()">
                <i class="fas fa-print me-1"></i> Print
            </button>
            <a href="<?= $GLOBALS['base_url'] ?>/con-stocks" class="btn btn-primary btn-sm shadow-sm">
                <i class="fas fa-layer-group me-1"></i> Stock Levels
            </a>
        </div>
    </div>

    <section class="section">

        <!-- ══════════════════════════════════════
             PROFESSIONAL KPI METRIC CARDS
        ════════════════════════════════════════ -->
        <div class="row  mb-4">
            <!-- 1. Total Ordered Items -->
            <div class="col-xl-3 col-md-6 col-12">
                <div class="card pm-pro-card pm-card-blue h-100 border-0 shadow-sm">
                    <div class="card-body p-3 p-xl-4 d-flex align-items-center justify-content-between">
                        <div class="pe-2">
                            <div class="pm-pro-label text-uppercase">Total Ordered Items</div>
                            <h3 class="pm-pro-value mb-1" id="kpi_total_orders"><?= number_format($totalOrders ?? 0) ?></h3>
                            <div class="pm-pro-sub text-muted">Across all purchase orders</div>
                        </div>
                        <div class="pm-pro-icon pm-icon-blue">
                            <i class="fas fa-boxes-stacked"></i>
                        </div>
                    </div>
                    <div class="pm-card-accent-bar bg-primary"></div>
                </div>
            </div>

            <!-- 2. Total PO Value -->
            <div class="col-xl-3 col-md-6 col-12">
                <div class="card pm-pro-card pm-card-emerald h-100 border-0 shadow-sm">
                    <div class="card-body p-3 p-xl-4 d-flex align-items-center justify-content-between">
                        <div class="pe-2">
                            <div class="pm-pro-label text-uppercase">Total PO Value</div>
                            <h3 class="pm-pro-value mb-1 text-emerald" id="kpi_total_value"><?= fmtLakhReport($totalOrderValue ?? 0) ?></h3>
                            <div class="pm-pro-sub text-muted" id="kpi_total_value_sub"><?= fmtCurrency($totalOrderValue ?? 0) ?></div>
                        </div>
                        <div class="pm-pro-icon pm-icon-emerald">
                            <i class="fas fa-coins"></i>
                        </div>
                    </div>
                    <div class="pm-card-accent-bar bg-success"></div>
                </div>
            </div>

            <!-- 3. Received Items -->
            <div class="col-xl-3 col-md-6 col-12">
                <div class="card pm-pro-card pm-card-cyan h-100 border-0 shadow-sm">
                    <div class="card-body p-3 p-xl-4 d-flex align-items-center justify-content-between">
                        <div class="pe-2">
                            <div class="pm-pro-label text-uppercase">Received at Site</div>
                            <h3 class="pm-pro-value mb-1 text-cyan" id="kpi_received_count"><?= number_format($receivedCount ?? 0) ?></h3>
                            <div class="pm-pro-sub text-muted">Delivered & logged at site</div>
                        </div>
                        <div class="pm-pro-icon pm-icon-cyan">
                            <i class="fas fa-truck-ramp-box"></i>
                        </div>
                    </div>
                    <div class="pm-card-accent-bar bg-info"></div>
                </div>
            </div>

            <!-- 4. Pending Receipts -->
            <div class="col-xl-3 col-md-6 col-12">
                <div class="card pm-pro-card pm-card-amber h-100 border-0 shadow-sm">
                    <div class="card-body p-3 p-xl-4 d-flex align-items-center justify-content-between">
                        <div class="pe-2">
                            <div class="pm-pro-label text-uppercase">Pending Receipts</div>
                            <h3 class="pm-pro-value mb-1 text-amber" id="kpi_pending_count"><?= number_format($notReceivedCount ?? 0) ?></h3>
                            <div class="pm-pro-sub text-muted">Awaiting delivery / logging</div>
                        </div>
                        <div class="pm-pro-icon pm-icon-amber">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                    </div>
                    <div class="pm-card-accent-bar bg-warning"></div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════
             SEARCH & FILTER CARD
        ════════════════════════════════════════ -->
        <div class="card shadow-sm border-0 mb-4 pm-filter-card">
            <div class="card-header bg-transparent py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="pm-filter-pill-icon"><i class="fas fa-filter text-primary"></i></span>
                    <h6 class="m-0 fw-bold text-dark">Filter Project Material Records</h6>
                </div>
                <div>
                    <span class="badge bg-light text-secondary border px-2 py-1" id="filterStatusBadge">
                        <i class="fas fa-circle-info me-1"></i> Server-side AJAX Active
                    </span>
                </div>
            </div>
            <div class="card-body p-3 p-xl-4">
                <form id="filterForm">
                    <div class="row  align-items-end">

                        <!-- Project Dropdown -->
                        <div class="col-xl-3 col-md-6 col-12">
                            <label class="form-label fw-semibold small text-muted mb-1">Project Name</label>
                            <select name="project_id" id="filter_project_id" class="form-select select2-filter">
                                <option value="">All Projects</option>
                                <?php foreach ($projects as $proj): ?>
                                    <option value="<?= $proj['id'] ?>" <?= (!empty($filters['project_id']) && $filters['project_id'] == $proj['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($proj['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Material Category Dropdown -->
                        <div class="col-xl-3 col-md-6 col-12">
                            <label class="form-label fw-semibold small text-muted mb-1">Material Category</label>
                            <select name="category_id" id="filter_category_id" class="form-select select2-filter">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= (!empty($filters['category_id']) && $filters['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Material Name Dropdown -->
                        <div class="col-xl-3 col-md-6 col-12">
                            <label class="form-label fw-semibold small text-muted mb-1">Material Name</label>
                            <select name="material_id" id="filter_material_id" class="form-select select2-filter">
                                <option value="">All Materials</option>
                                <?php foreach ($allMaterials as $mat): ?>
                                    <option value="<?= $mat['id'] ?>" <?= (!empty($filters['material_id']) && $filters['material_id'] == $mat['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($mat['name']) ?><?= !empty($mat['unit']) ? ' (' . htmlspecialchars($mat['unit']) . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Date Range -->
                        <div class="col-xl-3 col-md-6 col-12">
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label fw-semibold small text-muted mb-1">From Date</label>
                                    <input type="date" name="start_date" id="filter_start_date" class="form-control" value="<?= htmlspecialchars($filters['start_date'] ?? '') ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold small text-muted mb-1">To Date</label>
                                    <input type="date" name="end_date" id="filter_end_date" class="form-control" value="<?= htmlspecialchars($filters['end_date'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="col-12 text-end pt-2">
                            <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm" id="btnFilterSubmit">
                                <i class="fas fa-search me-1"></i> Filter Report
                            </button>
                            <button type="button" class="btn btn-outline-secondary px-3 ms-2 fw-semibold" id="btnFilterReset">
                                <i class="fas fa-undo me-1"></i> Reset
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- ══════════════════════════════════════
             DATA TABLE CARD (AJAX Server-Side)
        ════════════════════════════════════════ -->
        <div class="card shadow-sm border-0 pm-table-card">
            <div class="card-header bg-transparent py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="card-title m-0 fw-bold text-dark">Material Procurement & Receiving Records</h5>
                        <span class="badge bg-primary-subtle text-primary border" id="recordCountBadge">
                            <i class="fas fa-spinner fa-spin me-1"></i> Loading records...
                        </span>
                    </div>
                    <small class="text-muted">Live server-side paginated report across projects, POs, CST vendors, and site receiving</small>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive p-3">
                    <table class="table table-hover align-middle custom-table" id="reportTable" style="width:100%">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th style="min-width: 140px;">Project</th>
                                <th style="min-width: 160px;">Material & Category</th>
                                <th style="min-width: 130px;">CST Vendor</th>
                                <th style="min-width: 105px;">PO & PR No.</th>
                                <th style="min-width: 140px;">Requester & Dates</th>
                                <th style="min-width: 90px;" class="text-end">Order Qty</th>
                                <th style="min-width: 95px;" class="text-end">Unit Price</th>
                                <th style="min-width: 105px;" class="text-end">Total Amount</th>
                                <th style="min-width: 170px;">PO Approvals</th>
                                <th style="min-width: 190px;">Material Received / Logged</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Server-side AJAX dynamically loads rows here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </section>
</main>

<!-- ══════════════════════════════════════
     STYLES (Light & Dark Theme Compatible)
════════════════════════════════════════ -->
<style>
/* ── Modern Professional KPI Cards ── */
.pm-pro-card {
    position: relative;
    background: #ffffff;
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid #e2e8f0 !important;
}
.pm-pro-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04) !important;
}

.pm-card-accent-bar {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
}

.pm-pro-label {
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    color: #64748b;
    margin-bottom: 2px;
}

.pm-pro-value {
    font-size: 1.85rem;
    font-weight: 800;
    line-height: 1.2;
    color: #0f172a;
}

.pm-pro-sub {
    font-size: 0.78rem;
    color: #64748b;
}

/* Icon Wrappers */
.pm-pro-icon {
    width: 52px;
    height: 52px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
    transition: transform 0.2s ease;
}
.pm-pro-card:hover .pm-pro-icon {
    transform: scale(1.08);
}

.pm-icon-blue    { background: #eff6ff; color: #2563eb; }
.pm-icon-emerald { background: #ecfdf5; color: #059669; }
.pm-icon-cyan    { background: #ecfeff; color: #0891b2; }
.pm-icon-amber   { background: #fffbeb; color: #d97706; }

.text-emerald { color: #059669 !important; }
.text-cyan    { color: #0891b2 !important; }
.text-amber   { color: #d97706 !important; }

/* Filter Pill Icon */
.pm-filter-pill-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #eff6ff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

/* Approval badge items */
.pm-approval-item {
    font-size: 11.5px;
    line-height: 1.3;
}

/* Select2 Form Alignment */
.select2-container .select2-selection--single {
    height: 38px !important;
    border-radius: 6px !important;
    border: 1px solid #ced4da !important;
    display: flex !important;
    align-items: center !important;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 36px !important;
    padding-left: 10px !important;
    color: #212529 !important;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px !important;
}

/* DataTables Processing State */
.dataTables_wrapper .dataTables_processing {
    background: rgba(255, 255, 255, 0.9) !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 8px !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08) !important;
    padding: 12px 24px !important;
    font-weight: 600 !important;
    color: #0f172a !important;
    z-index: 100 !important;
}

/* ── Dark Theme Support ── */
[bd-theme="bd-theme-dark"] .pm-pro-card,
[bd-theme="bd-theme-dark"] .pm-filter-card,
[bd-theme="bd-theme-dark"] .pm-table-card {
    background-color: #1e293b !important;
    border-color: #334155 !important;
}
[bd-theme="bd-theme-dark"] .pm-pro-value {
    color: #f8fafc !important;
}
[bd-theme="bd-theme-dark"] .pm-pro-label,
[bd-theme="bd-theme-dark"] .pm-pro-sub {
    color: #94a3b8 !important;
}
[bd-theme="bd-theme-dark"] .pm-icon-blue    { background: rgba(37, 99, 235, 0.2) !important; color: #60a5fa !important; }
[bd-theme="bd-theme-dark"] .pm-icon-emerald { background: rgba(5, 150, 105, 0.2) !important; color: #34d399 !important; }
[bd-theme="bd-theme-dark"] .pm-icon-cyan    { background: rgba(8, 145, 178, 0.2) !important; color: #38bdf8 !important; }
[bd-theme="bd-theme-dark"] .pm-icon-amber   { background: rgba(217, 119, 6, 0.2) !important; color: #fbbf24 !important; }
[bd-theme="bd-theme-dark"] .pm-filter-pill-icon { background: rgba(37, 99, 235, 0.2) !important; }

[bd-theme="bd-theme-dark"] .card-title,
[bd-theme="bd-theme-dark"] .fw-bold.text-dark,
[bd-theme="bd-theme-dark"] .fw-semibold.text-dark {
    color: #f8fafc !important;
}
[bd-theme="bd-theme-dark"] .pm-filter-card .form-control,
[bd-theme="bd-theme-dark"] .pm-filter-card .form-select {
    background-color: #0f172a !important;
    border-color: #334155 !important;
    color: #f8fafc !important;
}
[bd-theme="bd-theme-dark"] .select2-container--default .select2-selection--single {
    background-color: #0f172a !important;
    border-color: #334155 !important;
}
[bd-theme="bd-theme-dark"] .select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #f8fafc !important;
}
[bd-theme="bd-theme-dark"] .table-hover tbody tr:hover td {
    background-color: rgba(255, 255, 255, 0.03) !important;
}
[bd-theme="bd-theme-dark"] .dataTables_wrapper .dataTables_processing {
    background: rgba(30, 41, 59, 0.95) !important;
    border-color: #334155 !important;
    color: #f8fafc !important;
}
</style>

<!-- ══════════════════════════════════════
     SCRIPTS (Safe Polling Initializer)
════════════════════════════════════════ -->
<script>
(function() {
    function initReportScripts() {
        if (typeof jQuery === 'undefined' || typeof bootstrap === 'undefined') {
            setTimeout(initReportScripts, 50);
            return;
        }

        jQuery(function($) {
            // Initialize Select2 on filter dropdowns
            if ($.fn.select2) {
                $('.select2-filter').select2({
                    width: '100%',
                    placeholder: 'Select option...',
                    allowClear: true
                });
            }

            var reportTable = null;

            // Initialize Server-side AJAX DataTable
            if ($.fn.DataTable) {
                reportTable = $('#reportTable').DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    pageLength: 25,
                    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    order: [[0, 'desc']],
                    ajax: {
                        url: '<?= $GLOBALS['base_url'] ?>/con-project-materials-report',
                        type: 'GET',
                        data: function(d) {
                            d.ajax = 1;
                            d.project_id  = $('#filter_project_id').val();
                            d.category_id = $('#filter_category_id').val();
                            d.material_id = $('#filter_material_id').val();
                            d.start_date  = $('#filter_start_date').val();
                            d.end_date    = $('#filter_end_date').val();
                        }
                    },
                    dom: '<""Bf>rt<"d-flex flex-wrap justify-content-between align-items-center mt-3"ip>',
                    buttons: [
                        { extend: 'excel', text: '<i class="fas fa-file-excel me-1"></i> Excel', className: 'btn btn-sm btn-outline-success me-1 shadow-sm' },
                        { extend: 'csv', text: '<i class="fas fa-file-csv me-1"></i> CSV', className: 'btn btn-sm btn-outline-info me-1 shadow-sm' },
                        { extend: 'print', text: '<i class="fas fa-print me-1"></i> Print', className: 'btn btn-sm btn-outline-secondary shadow-sm' }
                    ],
                    language: {
                        search: "Quick Search Table:",
                        searchPlaceholder: "Search material, PO, PR, vendor, receiver...",
                        lengthMenu: "Show _MENU_ entries",
                        processing: '<div class="text-center py-2"><div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div> Loading report data...</div>',
                        emptyTable: "No material records found matching your filters.",
                        zeroRecords: "No matching records found."
                    },
                    columnDefs: [
                        { targets: [0, 9, 10], orderable: false }
                    ],
                    drawCallback: function(settings) {
                        var json = settings.json;
                        if (json && json.summaryStats) {
                            // Dynamically update the 4 KPI cards
                            $('#kpi_total_orders').text(json.summaryStats.totalOrdersFormatted);
                            $('#kpi_total_value').html(json.summaryStats.totalOrderValueLakh);
                            $('#kpi_total_value_sub').html(json.summaryStats.totalOrderValueFormatted);
                            $('#kpi_received_count').text(json.summaryStats.receivedCountFormatted);
                            $('#kpi_pending_count').text(json.summaryStats.notReceivedCountFormatted);

                            // Update record count badge
                            var totalRec = json.recordsTotal || 0;
                            var filteredRec = json.recordsFiltered || 0;
                            if (filteredRec < totalRec) {
                                $('#recordCountBadge').html('<i class="fas fa-filter me-1"></i> ' + filteredRec.toLocaleString() + ' of ' + totalRec.toLocaleString() + ' records');
                            } else {
                                $('#recordCountBadge').html('<i class="fas fa-list me-1"></i> ' + totalRec.toLocaleString() + ' total records');
                            }
                        }
                    }
                });
            }

            // Bind filter form submission to table reload
            $('#filterForm').on('submit', function(e) {
                e.preventDefault();
                if (reportTable) {
                    reportTable.ajax.reload();
                }
            });

            // Bind reset button
            $('#btnFilterReset').on('click', function(e) {
                e.preventDefault();
                $('#filter_project_id').val('').trigger('change');
                $('#filter_category_id').val('').trigger('change');
                $('#filter_material_id').val('').trigger('change');
                $('#filter_start_date').val('');
                $('#filter_end_date').val('');
                if (reportTable) {
                    reportTable.ajax.reload();
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initReportScripts);
    } else {
        initReportScripts();
    }
})();
</script>
