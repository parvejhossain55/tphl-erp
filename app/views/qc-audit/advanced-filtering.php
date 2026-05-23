

        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><?= $page_title ?? 'Advanced Auditing' ?></h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/qc-dashboard">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Advanced Audit</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header pb-0 border-bottom">
                <ul class="nav nav-tabs border-0" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active fw-semibold text-primary" data-bs-toggle="tab" href="#materials-tab" role="tab">
                            <i class="bi bi-box-seam me-1"></i> Materials & Stocks
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold text-primary" data-bs-toggle="tab" href="#purchases-tab" role="tab">
                            <i class="bi bi-cart3 me-1"></i> Purchase Transactions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold text-primary" data-bs-toggle="tab" href="#payments-tab" role="tab">
                            <i class="bi bi-wallet2 me-1"></i> Payment Requests
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    
                    <!-- Materials Tab -->
                    <div class="tab-pane active" id="materials-tab" role="tabpanel">
                        <h6 class="mb-3 fw-bold">Material Requisitions & Stock Observations</h6>
                        
                        <!-- Filters -->
                        <div class="row mb-3">
                            <div class="col-md-3 mb-2 mb-md-0">
                                <label class="form-label">Project</label>
                                <select class="form-select form-select-sm select2" data-placeholder="All Projects" id="mat_project_filter">
                                    <option value="">All Projects</option>
                                    <?php if (isset($projects) && is_array($projects)): ?>
                                        <?php foreach ($projects as $project): ?>
                                            <option value="<?= htmlspecialchars($project['name']) ?>"><?= htmlspecialchars($project['name']) ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2 mb-md-0">
                                <label class="form-label">Material Name</label>
                                <input type="text" class="form-control form-control-sm" placeholder="Search Material...">
                            </div>
                            <div class="col-md-2 mt-md-4 mt-2">
                                <button class="btn btn-sm btn-primary w-100"><i class="bi bi-search me-1"></i>Filter</button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover text-nowrap w-100" id="materialsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Project</th>
                                        <th>Requisition ID</th>
                                        <th>Material</th>
                                        <th>Quantity Requested</th>
                                        <th>Stock Used</th>
                                        <th>Docs</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Purchases Tab -->
                    <div class="tab-pane" id="purchases-tab" role="tabpanel">
                        <h6 class="mb-3 fw-bold">Purchase Transactions & Usage (Time Period)</h6>
                        
                        <!-- Filters -->
                        <div class="row mb-3">
                            <div class="col-md-2 mb-2 mb-md-0">
                                <label class="form-label">Period</label>
                                <select class="form-select form-select-sm">
                                    <option value="yearly">Yearly</option>
                                    <option value="custom">Custom Range</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-2 mb-md-0">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2 mb-2 mb-md-0">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2 mt-md-4 mt-2">
                                <button class="btn btn-sm btn-primary w-100"><i class="bi bi-search me-1"></i>Filter</button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover text-nowrap w-100" id="purchasesTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Project</th>
                                        <th>PO ID</th>
                                        <th>Total Amount</th>
                                        <th>Status</th>
                                        <th>Audit Docs</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Payments Tab -->
                    <div class="tab-pane" id="payments-tab" role="tabpanel">
                        <h6 class="mb-3 fw-bold">Payment Requests (Cashouts, Vendors, Contractors)</h6>
                        
                        <!-- Filters -->
                        <div class="row mb-3">
                            <div class="col-md-3 mb-2 mb-md-0">
                                <label class="form-label">Request Type</label>
                                <select class="form-select form-select-sm">
                                    <option value="">All Types</option>
                                    <option value="cashout">Cashout Request</option>
                                    <option value="vendor">Vendor Payment</option>
                                    <option value="contractor">Contractor Bill</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2 mb-md-0">
                                <label class="form-label">Status</label>
                                <select class="form-select form-select-sm">
                                    <option value="">All Statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="qc_approved">QC Approved</option>
                                    <option value="approved">Management Approved</option>
                                    <option value="paid">Paid</option>
                                </select>
                            </div>
                            <div class="col-md-2 mt-md-4 mt-2">
                                <button class="btn btn-sm btn-primary w-100"><i class="bi bi-search me-1"></i>Filter</button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover text-nowrap w-100" id="paymentsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>QC Status</th>
                                        <th>Mgmt Status</th>
                                        <th>Actions & Docs</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>

<!-- Audit Trail Modal -->
<div class="modal fade" id="auditTrailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="bi bi-clock-history me-2"></i>Audit Trail</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="auditTrailBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Loading audit trail...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
.audit-timeline { position: relative; padding-left: 40px; }
.audit-timeline::before { content: ''; position: absolute; left: 15px; top: 0; bottom: 0; width: 2px; background: #dee2e6; }
.audit-step { position: relative; margin-bottom: 24px; }
.audit-step:last-child { margin-bottom: 0; }
.audit-step .audit-icon { position: absolute; left: -40px; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 14px; z-index: 1; }
.audit-step .audit-card { background: #f8f9fa; border-radius: 8px; padding: 12px 16px; border-left: 3px solid; }
.audit-step .audit-card.border-primary { border-left-color: #0d6efd; }
.audit-step .audit-card.border-warning { border-left-color: #ffc107; }
.audit-step .audit-card.border-success { border-left-color: #198754; }
.audit-step .audit-card.border-info { border-left-color: #0dcaf0; }
.audit-step .audit-card.border-danger { border-left-color: #dc3545; }
.audit-step .audit-card.border-secondary { border-left-color: #6c757d; }
</style>

<script>
function openAuditTrail(type, id) {
    var base_url = '<?= $GLOBALS['base_url'] ?>';
    document.getElementById('auditTrailBody').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Loading audit trail...</p></div>';
    var modal = new bootstrap.Modal(document.getElementById('auditTrailModal'));
    modal.show();

    var xhr = new XMLHttpRequest();
    xhr.open('GET', base_url + '/api/qc-audit/audit-trail?type=' + encodeURIComponent(type) + '&id=' + encodeURIComponent(id));
    xhr.onload = function() {
        if (xhr.status === 200) {
            var res = JSON.parse(xhr.responseText);
            if (res.success && res.trail && res.trail.length > 0) {
                var html = '<div class="audit-timeline">';
                res.trail.forEach(function(step) {
                    var dateStr = 'N/A';
                    if (step.date) {
                        var d = new Date(step.date);
                        dateStr = d.toLocaleString('en-GB', {day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit', hour12:true});
                    }
                    html += '<div class="audit-step">' +
                        '<div class="audit-icon bg-' + step.color + '"><i class="bi ' + step.icon + '"></i></div>' +
                        '<div class="audit-card border-' + step.color + '">' +
                        '<div class="d-flex justify-content-between align-items-start">' +
                        '<div><strong>' + step.action + '</strong><br>' +
                        '<span class="text-muted small">' + step.details + '</span></div>' +
                        '<div class="text-end" style="min-width:120px;">' +
                        '<small class="text-muted">' + dateStr + '</small><br>' +
                        '<span class="badge bg-light text-dark border"><i class="bi bi-person me-1"></i>' + step.user + '</span>' +
                        '</div></div></div></div>';
                });
                html += '</div>';
                document.getElementById('auditTrailBody').innerHTML = html;
            } else {
                document.getElementById('auditTrailBody').innerHTML = '<div class="text-center py-4"><i class="bi bi-inbox text-muted" style="font-size:2rem;"></i><p class="text-muted mt-2">No audit trail data found.</p></div>';
            }
        } else {
            document.getElementById('auditTrailBody').innerHTML = '<div class="alert alert-danger">Failed to load audit trail.</div>';
        }
    };
    xhr.onerror = function() {
        document.getElementById('auditTrailBody').innerHTML = '<div class="alert alert-danger">Network error. Please try again.</div>';
    };
    xhr.send();
}
</script>


<?php ob_start(); ?>
$(document).ready(function() {
    // Materials & Stocks
    $('#materialsTable').DataTable({
        responsive: true,
        ajax: '<?= $GLOBALS['base_url'] ?>/api/qc-audit/materials',
        columns: [
            { data: 'request_date' },
            { data: 'project_name', defaultContent: '-' },
            { data: 'requisition_id' },
            { data: 'material_name' },
            { data: 'requested_quantity' },
            { data: 'stock_available' },
            { 
                data: null, 
                render: function(data, type, row) {
                    let base_url = '<?= $GLOBALS['base_url'] ?>';
                    let url = base_url + '/con-purchase-request/' + row.requisition_id;
                    let statusBadge = '<span class="badge bg-primary me-2">'+row.status+'</span>';
                    let detailsBtn = '<a href="'+url+'" target="_blank" class="btn btn-sm btn-outline-info" title="View Request Details"><i class="bi bi-eye"></i> Details</a>';
                    return statusBadge + detailsBtn;
                }
            }
        ]
    });

    // Custom filtering for Project
    $('#mat_project_filter').on('change', function() {
        $('#materialsTable').DataTable().column(1).search(this.value).draw();
    });

    // Purchase Transactions
    $('#purchasesTable').DataTable({
        responsive: true,
        ajax: '<?= $GLOBALS['base_url'] ?>/api/qc-audit/purchases',
        columns: [
            { data: 'transaction_date' },
            { data: 'project_name', defaultContent: '-' },
            { data: 'transaction_id' },
            { data: 'total_amount' },
            { 
                data: 'status',
                render: function(data) {
                    return '<span class="badge bg-info">'+data+'</span>';
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    let base_url = '<?= $GLOBALS['base_url'] ?>';
                    // We don't have PR or PO distinguishing here easily, assume PO for purchases table
                    let url = base_url + '/con-purchase-order/' + row.transaction_id;
                    return '<a href="'+url+'" target="_blank" class="btn btn-sm btn-outline-info me-1"><i class="bi bi-eye"></i> Details</a>';
                }
            }
        ]
    });

    // Payment Requests
    $('#paymentsTable').DataTable({
        responsive: true,
        ajax: '<?= $GLOBALS['base_url'] ?>/api/qc-audit/payments',
        columns: [
            { data: 'request_date' },
            { data: 'request_type' },
            { data: 'amount' },
            { 
                data: 'qc_status',
                render: function(data) {
                    let color = data === 'QC Approved' ? 'success' : 'warning';
                    return `<span class="badge bg-${color}">${data}</span>`;
                }
            },
            { 
                data: 'mgmt_status',
                render: function(data) {
                    return '<span class="badge bg-secondary">'+data+'</span>';
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    let base_url = '<?= $GLOBALS['base_url'] ?>';
                    let url = '#';
                    if (row.request_type === 'Cashout') {
                        url = base_url + '/view-cashout-request?id=' + row.ref_id;
                    } else if (row.request_type === 'Vendor Payment') {
                        url = base_url + '/con-vendor-payment-show/' + row.ref_id;
                    } else if (row.request_type === 'Contractor Bill') {
                        url = base_url + '/con-contractor-bill/' + row.ref_id;
                    }
                    return '<a href="'+url+'" target="_blank" class="btn btn-sm btn-outline-info me-1"><i class="bi bi-eye"></i> Details</a>' +
                           '<button class="btn btn-sm btn-outline-primary" onclick="openAuditTrail(\'' + row.request_type + '\', \'' + row.ref_id + '\')"><i class="bi bi-clock-history me-1"></i>Audit Trail</button>';
                }
            }
        ]
    });
});
<?php $page_script = ob_get_clean(); ?>

