<?php
$page_title = "Financial Dashboard";
$page = 'Accounts';
$show_breadcrumb = true;
?>

<style>
/* Custom styling for the vertical tabs to match modern dashboard style */
.financial-tabs .nav-pills .nav-link {
    border-radius: 8px;
    margin-bottom: 8px;
    padding: 12px 16px;
    font-weight: 500;
    text-align: left;
    color: #495057;
    background: #fff;
    border: 1px solid #e9ecef;
    transition: all 0.2s ease-in-out;
}
.financial-tabs .nav-pills .nav-link:hover {
    background: #f8f9fa;
    border-color: #dee2e6;
}
.financial-tabs .nav-pills .nav-link.active {
    background-color: #20c997; /* Matches the Aiksol tester teal color */
    color: white;
    border-color: #20c997;
    box-shadow: 0 4px 6px rgba(32, 201, 151, 0.2);
}
.financial-tabs .nav-pills .nav-link i {
    width: 24px;
    text-align: center;
    margin-right: 8px;
    font-size: 1.1em;
}

/* Horizontal Sub-tabs */
.sub-tabs {
    border-bottom: 1px solid #dee2e6;
    margin-bottom: 20px;
}
.sub-tabs .nav-link {
    color: #6c757d;
    border-bottom: 2px solid transparent;
    padding: 10px 20px;
    font-weight: 500;
    border-radius: 0;
}
.sub-tabs .nav-link:hover {
    color: #495057;
}
.sub-tabs .nav-link.active {
    color: #20c997;
    border-bottom: 2px solid #20c997;
    background: transparent;
    font-weight: 600;
}

/* Dashboard Card */
.dashboard-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    background: #fff;
}
.dashboard-card .card-body {
    padding: 1.5rem;
}

/* Status Badges */
.badge-status-approved { background-color: #28a745; color: white; }
.badge-status-pending { background-color: #ffc107; color: #212529; }
.badge-status-rejected { background-color: #dc3545; color: white; }
.badge-status-paid { background-color: #198754; color: white; }
.badge-status-draft { background-color: #fd7e14; color: white; }
</style>

<div class="row">
    <!-- Top Filter Section -->
    <div class="col-12 mb-4">
        <div class="card dashboard-card">
            <div class="card-body py-3 d-flex align-items-center justify-content-between">
                <h5 class="mb-0 text-muted"><i class="fa-light fa-filter"></i> Filters</h5>
                <div class="d-flex align-items-center">
                    <label class="me-2 fw-bold">Project:</label>
                    <select id="projectFilter" class="form-select form-select-sm" style="min-width: 250px;" onchange="reloadAllTables()">
                        <option value="">All Projects</option>
                        <?php if(!empty($projects)): ?>
                            <?php foreach($projects as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['project_name']) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Left Navigation Sidebar -->
    <div class="col-md-3 col-xl-2 financial-tabs mb-4">
        <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
            <a href="<?= $base_url ?>/accounts/coa" class="nav-link text-primary fw-bold mb-2 border border-info" style="background: #f0fdfa;">
                <i class="fa-light fa-sitemap text-info"></i> Chart of Accounts (COA)
            </a>
            <button class="nav-link active" id="v-pills-receipts-tab" data-bs-toggle="pill" data-bs-target="#v-pills-receipts" type="button" role="tab" onclick="initReceiptsTable()">
                <i class="fa-light fa-receipt"></i> Receipts (Cash In)
            </button>
            <button class="nav-link" id="v-pills-expense-tab" data-bs-toggle="pill" data-bs-target="#v-pills-expense" type="button" role="tab" onclick="loadExpense('fund')">
                <i class="fa-light fa-file-invoice-dollar"></i> Expense Vouchers
            </button>
            <button class="nav-link" id="v-pills-journal-tab" data-bs-toggle="pill" data-bs-target="#v-pills-journal" type="button" role="tab" onclick="initJournalTable()">
                <i class="fa-light fa-sliders"></i> Expense Adjustments
            </button>
            <button class="nav-link" id="v-pills-general-journal-tab" data-bs-toggle="pill" data-bs-target="#v-pills-general-journal" type="button" role="tab" onclick="initGeneralJournalTable()">
                <i class="fa-light fa-book"></i> Journal Entry (JV)
            </button>
            <button class="nav-link" id="v-pills-clients-tab" data-bs-toggle="pill" data-bs-target="#v-pills-clients" type="button" role="tab" onclick="initClientsTable()">
                <i class="fa-light fa-users"></i> Client Receivables
            </button>
            <button class="nav-link" id="v-pills-refunds-tab" data-bs-toggle="pill" data-bs-target="#v-pills-refunds" type="button" role="tab" onclick="initRefundsTable()">
                <i class="fa-light fa-undo"></i> Client Refunds
            </button>
            <button class="nav-link" id="v-pills-reports-tab" data-bs-toggle="pill" data-bs-target="#v-pills-reports" type="button" role="tab">
                <i class="fa-light fa-chart-pie"></i> Financial Reports (P/L, B/S)
            </button>
        </div>
    </div>
    
    <!-- Main Content Area -->
    <div class="col-md-9 col-xl-10">
        <div class="card dashboard-card">
            <div class="card-body">
                <div class="tab-content" id="v-pills-tabContent">
                
                    <!-- ================= RECEIPTS TAB ================= -->
                    <div class="tab-pane fade show active" id="v-pills-receipts" role="tabpanel">
                        <ul class="nav nav-pills sub-tabs" id="receipts-sub-tabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="pill" type="button">All Receipts</button>
                            </li>
                        </ul>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped w-100" id="receiptsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Sr</th>
                                        <th>Entry Date</th>
                                        <th>Voucher</th>
                                        <th>Client Name</th>
                                        <th>Amount</th>
                                        <th>Type</th>
                                        <th>Added By</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- ================= EXPENSE VOUCHER TAB ================= -->
                    <div class="tab-pane fade" id="v-pills-expense" role="tabpanel">
                        <ul class="nav nav-pills sub-tabs" id="expense-sub-tabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="pill" type="button" onclick="loadExpense('fund')">Fund Requisition</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="pill" type="button" onclick="loadExpense('cashout')">Cashout</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="pill" type="button" onclick="loadExpense('vendor')">Vendor Payment</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="pill" type="button" onclick="loadExpense('contractor')">Contractor Bill</button>
                            </li>
                        </ul>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped w-100" id="expenseTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Sr</th>
                                        <th>Entry Date</th>
                                        <th>Voucher</th>
                                        <th>Amount</th>
                                        <th>Narration</th>
                                        <th>Added By</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- ================= EXPENSE ADJUSTMENTS TAB ================= -->
                    <div class="tab-pane fade" id="v-pills-journal" role="tabpanel">
                        <h4 class="mb-4">Expense Adjustments</h4>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped w-100" id="journalTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Sr</th>
                                        <th>Entry Date</th>
                                        <th>Voucher</th>
                                        <th>Amount</th>
                                        <th>Type</th>
                                        <th>Narration</th>
                                        <th>Added By</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- ================= GENERAL JOURNAL TAB ================= -->
                    <div class="tab-pane fade" id="v-pills-general-journal" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">General Journal Entries</h4>
                            <div>
                                <a href="<?= $base_url ?>/accounts/coa" class="btn btn-outline-secondary me-2">
                                    <i class="fa-light fa-sitemap"></i> Manage Accounts
                                </a>
                                <a href="<?= $base_url ?>/general-journal/create" class="btn btn-primary">
                                    <i class="fa-light fa-plus"></i> New Journal Entry
                                </a>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped w-100" id="generalJournalTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Sr</th>
                                        <th>Journal No</th>
                                        <th>Date</th>
                                        <th>Project</th>
                                        <th>Office</th>
                                        <th>Type</th>
                                        <th>Total Debit</th>
                                        <th>Total Credit</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- ================= CLIENTS TAB ================= -->
                    <div class="tab-pane fade" id="v-pills-clients" role="tabpanel">
                        <h4 class="mb-4">Clients Overview</h4>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped w-100" id="clientsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Sr</th>
                                        <th>Booking ID</th>
                                        <th>Client Name</th>
                                        <th>Phone</th>
                                        <th>Total Price</th>
                                        <th>Total Received</th>
                                        <th>Total Pending</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- ================= REFUNDS TAB ================= -->
                    <div class="tab-pane fade" id="v-pills-refunds" role="tabpanel">
                        <h4 class="mb-4">Refunded Requests</h4>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped w-100" id="refundsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Sr</th>
                                        <th>Refund Date</th>
                                        <th>Client Name</th>
                                        <th>Project & Flat</th>
                                        <th>Total Paid Before</th>
                                        <th>Retention Amt</th>
                                        <th>Refund Amt</th>
                                        <th>Reason</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- ================= FINANCIAL REPORTS TAB ================= -->
                    <div class="tab-pane fade" id="v-pills-reports" role="tabpanel">
                        <h4 class="mb-4"><i class="fa-light fa-chart-pie me-2"></i>Financial Reports</h4>
                        <p class="text-muted mb-4">View detailed financial statements based on your approved journal entries. Filter by project, office, and date range.</p>
                        
                        <div class="row">
                            <!-- Chart of Accounts Card -->
                            <div class="col-md-3">
                                <div class="card h-100 border-0 shadow-sm" style="border-top: 4px solid #17a2b8 !important;">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="fa-light fa-sitemap fa-3x text-info"></i>
                                        </div>
                                        <h5 class="fw-bold">Chart of Accounts</h5>
                                        <p class="text-muted small">Manage 4-Tier Accounts Hierarchy (Assets, Liabilities, Equity, Revenue, Expenses).</p>
                                        <a href="<?= $base_url ?>/accounts/coa" class="btn btn-outline-info mt-2">
                                            <i class="fa-light fa-sitemap me-1"></i> View COA
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Profit & Loss Card -->
                            <div class="col-md-3">
                                <div class="card h-100 border-0 shadow-sm" style="border-top: 4px solid #20c997 !important;">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="fa-light fa-chart-line fa-3x text-success"></i>
                                        </div>
                                        <h5 class="fw-bold">Profit & Loss</h5>
                                        <p class="text-muted small">Income Statement showing Revenue vs Expenses and Net Income for a selected period.</p>
                                        <a href="<?= $base_url ?>/reports/profit-loss" class="btn btn-outline-success mt-2">
                                            <i class="fa-light fa-eye me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Balance Sheet Card -->
                            <div class="col-md-3">
                                <div class="card h-100 border-0 shadow-sm" style="border-top: 4px solid #0d6efd !important;">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="fa-light fa-scale-balanced fa-3x text-primary"></i>
                                        </div>
                                        <h5 class="fw-bold">Balance Sheet</h5>
                                        <p class="text-muted small">Statement of Financial Position showing Assets, Liabilities, and Equity as of a specific date.</p>
                                        <a href="<?= $base_url ?>/reports/balance-sheet" class="btn btn-outline-primary mt-2">
                                            <i class="fa-light fa-eye me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Cashflow Card -->
                            <div class="col-md-3">
                                <div class="card h-100 border-0 shadow-sm" style="border-top: 4px solid #6f42c1 !important;">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="fa-light fa-money-bill-transfer fa-3x text-purple" style="color: #6f42c1;"></i>
                                        </div>
                                        <h5 class="fw-bold">Cashflow</h5>
                                        <p class="text-muted small">Cashflow Statement showing Cash Inflows and Outflows through Cash and Bank accounts.</p>
                                        <a href="<?= $base_url ?>/reports/cashflow" class="btn btn-outline-secondary mt-2" style="border-color: #6f42c1; color: #6f42c1;">
                                            <i class="fa-light fa-eye me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
    let receiptsTable, expenseTable, journalTable, clientsTable, refundsTable;
    
    // Helper to format currency
    const formatCurrency = (amount) => {
        return parseFloat(amount || 0).toLocaleString('en-BD', { style: 'currency', currency: 'BDT' });
    };

    // Helper to get status badge
    const getStatusBadge = (status) => {
        if (!status) return '-';
        status = status.toLowerCase();
        let badgeClass = 'bg-secondary';
        if (['approved', 'verified', 'qc_approved', 'paid', 'fully_paid', 'settled', 'refunded'].includes(status)) {
            badgeClass = 'badge-status-approved';
        } else if (['pending', 'draft', 'submitted'].includes(status)) {
            badgeClass = 'badge-status-pending';
        } else if (['rejected', 'failed'].includes(status)) {
            badgeClass = 'badge-status-rejected';
        }
        return `<span class="badge ${badgeClass} text-uppercase px-2 py-1 rounded-pill"><small>${status.replace(/_/g, ' ')}</small></span>`;
    };

    // Helper to format date
    const formatDate = (dateStr) => {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    };

    $(document).ready(function() {
        // Save selected tab in localStorage when user clicks any tab
        $('#v-pills-tab button[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
            let targetId = $(e.target).attr('data-bs-target');
            if (targetId) {
                localStorage.setItem('active_financial_tab', targetId);
            }
        });

        // Restore active tab from localStorage or URL hash
        let savedTab = window.location.hash || localStorage.getItem('active_financial_tab');
        if (savedTab && $(savedTab + '-tab').length) {
            let tabBtn = document.querySelector(savedTab + '-tab');
            if (tabBtn) {
                let bsTab = new bootstrap.Tab(tabBtn);
                bsTab.show();
                $(tabBtn).trigger('click');
            }
        } else {
            initReceiptsTable();
        }
    });

    function reloadAllTables() {
        if ($.fn.DataTable.isDataTable('#receiptsTable')) receiptsTable.ajax.reload();
        if ($.fn.DataTable.isDataTable('#expenseTable')) expenseTable.ajax.reload();
        if ($.fn.DataTable.isDataTable('#journalTable')) journalTable.ajax.reload();
        if ($.fn.DataTable.isDataTable('#clientsTable')) clientsTable.ajax.reload();
        if ($.fn.DataTable.isDataTable('#refundsTable')) refundsTable.ajax.reload();
        if ($.fn.DataTable.isDataTable('#generalJournalTable')) generalJournalTable.ajax.reload();
    }

    function initReceiptsTable() {
        if ($.fn.DataTable.isDataTable('#receiptsTable')) return;
        receiptsTable = $('#receiptsTable').DataTable({
            ajax: {
                url: window.base_url + '/financial-dashboard/get-receipts',
                data: function(d) {
                    d.project_id = $('#projectFilter').val();
                }
            },
            columns: [
                { data: null, render: (data, type, row, meta) => meta.row + 1 },
                { data: 'entry_date', render: (data) => formatDate(data) },
                { data: 'voucher_no' },
                { data: 'client_name', render: (data) => data ? `<strong>${data}</strong>` : 'N/A' },
                { data: 'amount', render: (data) => formatCurrency(data) },
                { data: 'narration', render: (data) => data ? data.replace(/_/g, ' ').toUpperCase() : '-' },
                { data: 'added_by_name' },
                { data: 'status', render: (data) => getStatusBadge(data) },
                { data: null, render: (data, type, row) => {
                    let url = window.base_url + '/payment-invoice/' + row.voucher_id;
                    return `
                        <a href="${url}" class="btn btn-sm btn-outline-secondary" title="View"><i class="fa-light fa-eye"></i></a>
                    `;
                }}
            ],
            order: [[1, 'desc']],
            pageLength: 25
        });
    }

    let currentExpenseType = 'fund';
    function loadExpense(type) {
        currentExpenseType = type;
        if ($.fn.DataTable.isDataTable('#expenseTable')) {
            expenseTable.ajax.reload();
        } else {
            expenseTable = $('#expenseTable').DataTable({
                ajax: {
                    url: window.base_url + '/financial-dashboard/get-vouchers',
                    data: function(d) {
                        d.type = currentExpenseType;
                        d.project_id = $('#projectFilter').val();
                    }
                },
                columns: [
                    { data: null, render: (data, type, row, meta) => meta.row + 1 },
                    { data: 'entry_date', render: (data) => formatDate(data) },
                    { data: 'voucher_no' },
                    { data: 'amount', render: (data) => formatCurrency(data) },
                    { data: 'narration', render: (data) => data ? `<span title="${data}">${data.length > 30 ? data.substring(0,30)+'...' : data}</span>` : '-' },
                    { data: 'added_by_name' },
                    { data: 'status', render: (data) => getStatusBadge(data) },
                    { data: null, render: (data, type, row) => {
                    let url = '#';
                    if (currentExpenseType === 'fund') url = window.base_url + '/fund-requisitions/view/' + row.voucher_id;
                    else if (currentExpenseType === 'cashout') url = window.base_url + '/view-cashout-request?id=' + row.voucher_id;
                    else if (currentExpenseType === 'vendor') url = window.base_url + '/con-vendor-payment-show/' + row.voucher_id;
                    else if (currentExpenseType === 'contractor') url = window.base_url + '/con-contractor-bill/' + row.voucher_id;
                    return `
                        <a href="${url}" class="btn btn-sm btn-outline-secondary" title="View"><i class="fa-light fa-eye"></i></a>
                    `;
                }}
                ],
                order: [[1, 'desc']],
                pageLength: 25
            });
        }
    }

    function initJournalTable() {
        if ($.fn.DataTable.isDataTable('#journalTable')) return;
        journalTable = $('#journalTable').DataTable({
            ajax: {
                url: window.base_url + '/financial-dashboard/get-adjustments',
                data: function(d) {
                    d.project_id = $('#projectFilter').val();
                }
            },
            columns: [
                { data: null, render: (data, type, row, meta) => meta.row + 1 },
                { data: 'entry_date', render: (data) => formatDate(data) },
                { data: 'voucher_no' },
                { data: 'amount', render: (data) => formatCurrency(data) },
                { data: 'adjustment_type', render: (data) => data ? data.toUpperCase() : '-' },
                { data: 'narration', render: (data) => data ? `<span title="${data}">${data.length > 30 ? data.substring(0,30)+'...' : data}</span>` : '-' },
                { data: 'added_by_name' },
                { data: 'status', render: (data) => getStatusBadge(data) },
                { data: null, render: (data, type, row) => {
                    let url = window.base_url + '/expense-adjustments/show?id=' + row.voucher_id;
                    return `
                        <a href="${url}" class="btn btn-sm btn-outline-secondary" title="View"><i class="fa-light fa-eye"></i></a>
                    `;
                }}
            ],
            order: [[1, 'desc']],
            pageLength: 25
        });
    }

    function initClientsTable() {
        if ($.fn.DataTable.isDataTable('#clientsTable')) return;
        clientsTable = $('#clientsTable').DataTable({
            ajax: {
                url: window.base_url + '/financial-dashboard/get-clients',
                data: function(d) {
                    d.project_id = $('#projectFilter').val();
                }
            },
            columns: [
                { data: null, render: (data, type, row, meta) => meta.row + 1 },
                { data: 'booking_id', render: (data) => 'BKG-'+data },
                { data: 'full_name', render: (data) => `<strong>${data}</strong>` },
                { data: 'phone' },
                { data: 'total_price', render: (data) => formatCurrency(data) },
                { data: 'total_received', render: (data) => `<span class="text-success fw-bold">${formatCurrency(data)}</span>` },
                { data: 'total_pending', render: (data) => {
                    let amt = parseFloat(data || 0);
                    if (amt > 0) {
                        return `<span class="badge bg-danger rounded-pill px-2 py-1">${formatCurrency(amt)}</span>`;
                    }
                    return '<span class="badge bg-success rounded-pill px-2 py-1">Clear</span>';
                }},
                { data: 'booking_id', render: (data) => `
                    <a href="${window.base_url}/clients-cash-in-history/${data}" class="btn btn-sm btn-outline-primary" title="View Client Ledger">
                        <i class="fa-light fa-book-open"></i> Ledger
                    </a>
                `}
            ],
            order: [[6, 'desc']], // Sort by pending amounts descending
            pageLength: 25
        });
    }

    function initRefundsTable() {
        if ($.fn.DataTable.isDataTable('#refundsTable')) return;
        refundsTable = $('#refundsTable').DataTable({
            ajax: {
                url: window.base_url + '/financial-dashboard/get-refunds',
                data: function(d) {
                    d.project_id = $('#projectFilter').val();
                }
            },
            columns: [
                { data: null, render: (data, type, row, meta) => meta.row + 1 },
                { data: 'refunded_at', render: (data) => formatDate(data) },
                { data: 'client_name', render: (data) => `<strong>${data}</strong>` },
                { data: null, render: (data, type, row) => `${row.project_name || '-'} <br><small class="text-muted">${row.flat_no || ''}</small>` },
                { data: 'total_paid', render: (data) => formatCurrency(data) },
                { data: 'retention_amount', render: (data) => formatCurrency(data) },
                { data: 'refund_amount', render: (data) => `<span class="text-danger fw-bold">${formatCurrency(data)}</span>` },
                { data: 'reason', render: (data) => data ? `<span title="${data}">${data.length > 30 ? data.substring(0,30)+'...' : data}</span>` : '-' },
                { data: 'booking_id', render: (data) => {
                    if (!data) return '-';
                    return `
                    <a href="${window.base_url}/clients-cash-in-history/${data}" class="btn btn-sm btn-outline-primary" title="View Client Ledger">
                        <i class="fa-light fa-book-open"></i> Ledger
                    </a>
                    `;
                }}
            ],
            order: [[1, 'desc']],
            pageLength: 25
        });
    }

    function initGeneralJournalTable() {
        if ($.fn.DataTable.isDataTable('#generalJournalTable')) return;
        
        generalJournalTable = $('#generalJournalTable').DataTable({
            ajax: {
                url: window.base_url + '/financial-dashboard/get-general-journals',
                dataSrc: 'data',
                data: function(d) {
                    d.project_id = $('#projectFilter').val();
                }
            },
            columns: [
                { data: null, render: (data, type, row, meta) => meta.row + 1 },
                { data: 'journal_number', render: (data) => `<strong>${data}</strong>` },
                { data: 'date', render: (data) => formatDate(data) },
                { data: 'project_name', render: (data) => data ? `<span class="badge bg-info text-dark">${data}</span>` : '<span class="text-muted">Office</span>' },
                { data: 'office', render: (data) => data ? `<span class="badge bg-secondary">${data}</span>` : '-' },
                { data: 'voucher_type' },
                { data: 'total_debit', render: (data) => `<span class="text-success">${formatCurrency(data)}</span>` },
                { data: 'total_credit', render: (data) => `<span class="text-danger">${formatCurrency(data)}</span>` },
                { data: 'status', render: (data) => getStatusBadge(data) },
                { data: 'id', render: (data) => `
                    <a href="${window.base_url}/general-journal/view/${data}" class="btn btn-sm btn-outline-info" title="View Journal">
                        <i class="fa-light fa-eye"></i> View
                    </a>
                `}
            ],
            order: [[2, 'desc']],
            pageLength: 25
        });
    }
<?php $page_script = ob_get_clean(); ?>
