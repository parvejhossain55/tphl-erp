

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
                <div class="d-flex overflow-auto" style="-webkit-overflow-scrolling:touch;">
                    <ul class="nav nav-tabs border-0 flex-nowrap" role="tablist" style="min-width:max-content;">
                        <li class="nav-item">
                            <a class="nav-link active fw-semibold text-primary" data-bs-toggle="tab" href="#materials-tab" role="tab">
                                <i class="bi bi-box-seam me-1"></i> <span class="d-none d-sm-inline">Materials &</span> Stocks
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fw-semibold text-primary" data-bs-toggle="tab" href="#purchases-tab" role="tab">
                                <i class="bi bi-cart3 me-1"></i> <span class="d-none d-sm-inline">Purchase</span> Transactions
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fw-semibold text-primary" data-bs-toggle="tab" href="#payments-tab" role="tab">
                                <i class="bi bi-wallet2 me-1"></i> <span class="d-none d-sm-inline">Payment</span> Requests
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fw-semibold text-primary" data-bs-toggle="tab" href="#vendor-audit-tab" role="tab">
                                <i class="bi bi-shop me-1"></i> <span class="d-none d-sm-inline">Vendor</span> Audits
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fw-semibold text-primary" data-bs-toggle="tab" href="#contractor-audit-tab" role="tab">
                                <i class="bi bi-person-workspace me-1"></i> <span class="d-none d-sm-inline">Contractor</span> Audits
                            </a>
                        </li>
                    </ul>
                </div>
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

                    <!-- Vendor Audits Tab -->
                    <div class="tab-pane" id="vendor-audit-tab" role="tabpanel">
                        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
                            <div>
                                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-shop text-primary me-2"></i>Vendor Audits: Material Procurement & Payment Tracking</h6>
                                <span class="text-muted fs-12">Analyze materials taken, total PO count, payments disbursed, and outstanding dues per vendor</span>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="card border mb-3 shadow-none bg-light-subtle">
                            <div class="card-body p-3">
                                <div class="row g-2 align-items-end">
                                    <div class="col-lg-3 col-md-6">
                                        <label class="form-label fs-12 fw-semibold text-dark mb-1"><i class="bi bi-shop me-1 text-primary"></i>Vendor</label>
                                        <select class="form-select form-select-sm select2" id="vendor_filter" data-placeholder="All Vendors">
                                            <option value="">All Vendors</option>
                                            <?php if (!empty($vendors)): ?>
                                                <?php foreach ($vendors as $v): ?>
                                                    <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['name']) ?><?= !empty($v['company_name']) ? ' (' . htmlspecialchars($v['company_name']) . ')' : '' ?></option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <label class="form-label fs-12 fw-semibold text-dark mb-1"><i class="bi bi-building me-1 text-info"></i>Project</label>
                                        <select class="form-select form-select-sm select2" id="vendor_project_filter" data-placeholder="All Projects">
                                            <option value="">All Projects</option>
                                            <?php if (!empty($projects)): ?>
                                                <?php foreach ($projects as $p): ?>
                                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-lg-2 col-md-6">
                                        <label class="form-label fs-12 fw-semibold text-dark mb-1"><i class="bi bi-tag me-1 text-warning"></i>Status</label>
                                        <select class="form-select form-select-sm" id="vendor_status_filter">
                                            <option value="">All Statuses</option>
                                            <option value="Approved">Approved</option>
                                            <option value="QC Approved">QC Approved</option>
                                            <option value="Fully Received">Fully Received</option>
                                            <option value="Partially Received">Partially Received</option>
                                            <option value="Issued">Issued</option>
                                            <option value="Partially_Paid">Partially Paid</option>
                                            <option value="Fully_Paid">Fully Paid</option>
                                            <option value="Cancelled">Cancelled</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-2 col-md-6">
                                        <label class="form-label fs-12 fw-semibold text-dark mb-1"><i class="bi bi-search me-1 text-secondary"></i>Search</label>
                                        <input type="text" class="form-control form-control-sm" id="vendor_search_input" placeholder="Keyword / Material...">
                                    </div>
                                    <div class="col-lg-2 col-md-12 d-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-primary flex-fill" id="btn_filter_vendor"><i class="bi bi-search me-1"></i>Filter</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn_reset_vendor" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KPI Summary Cards -->
                        <div class="row g-3 mb-3">
                            <div class="col-xl col-md-4 col-sm-6">
                                <div class="card border-0 shadow-sm mb-0 h-100" style="background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%); border-left: 4px solid #4f46e5 !important;">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <span class="fs-12 fw-semibold text-muted text-uppercase">Total POs</span>
                                                <h4 class="mb-0 fw-bold text-dark mt-1" id="vendor_kpi_pos"><span class="spinner-border spinner-border-sm text-primary"></span></h4>
                                            </div>
                                            <div class="avatar avatar-md bg-indigo text-white rounded-3">
                                                <i class="bi bi-file-earmark-text fs-18"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl col-md-4 col-sm-6">
                                <div class="card border-0 shadow-sm mb-0 h-100" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-left: 4px solid #16a34a !important;">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <span class="fs-12 fw-semibold text-muted text-uppercase">Material Types</span>
                                                <h4 class="mb-0 fw-bold text-dark mt-1" id="vendor_kpi_materials"><span class="spinner-border spinner-border-sm text-success"></span></h4>
                                            </div>
                                            <div class="avatar avatar-md bg-success text-white rounded-3">
                                                <i class="bi bi-boxes fs-18"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl col-md-4 col-sm-6">
                                <div class="card border-0 shadow-sm mb-0 h-100" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-left: 4px solid #0284c7 !important;">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <span class="fs-12 fw-semibold text-muted text-uppercase">Total Amount</span>
                                                <h4 class="mb-0 fw-bold text-dark mt-1" id="vendor_kpi_amount"><span class="spinner-border spinner-border-sm text-info"></span></h4>
                                            </div>
                                            <div class="avatar avatar-md bg-info text-white rounded-3">
                                                <i class="bi bi-cash-stack fs-18"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl col-md-6 col-sm-6">
                                <div class="card border-0 shadow-sm mb-0 h-100" style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border-left: 4px solid #059669 !important;">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <span class="fs-12 fw-semibold text-muted text-uppercase">Total Paid</span>
                                                <h4 class="mb-0 fw-bold text-success mt-1" id="vendor_kpi_paid"><span class="spinner-border spinner-border-sm text-success"></span></h4>
                                            </div>
                                            <div class="avatar avatar-md bg-success-subtle text-success rounded-3 border border-success">
                                                <i class="bi bi-check-circle fs-18"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl col-md-6 col-sm-12">
                                <div class="card border-0 shadow-sm mb-0 h-100" style="background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%); border-left: 4px solid #e11d48 !important;">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <span class="fs-12 fw-semibold text-muted text-uppercase">Total Due</span>
                                                <h4 class="mb-0 fw-bold text-danger mt-1" id="vendor_kpi_due"><span class="spinner-border spinner-border-sm text-danger"></span></h4>
                                            </div>
                                            <div class="avatar avatar-md bg-danger text-white rounded-3">
                                                <i class="bi bi-exclamation-octagon fs-18"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Materials Acquired Breakdown Box -->
                        <div class="card border mb-3 shadow-none" id="vendor_materials_summary_card">
                            <div class="card-header bg-light py-2 px-3 d-flex align-items-center justify-content-between">
                                <span class="fw-bold fs-13 text-dark"><i class="bi bi-box2-fill text-primary me-2"></i>Materials Acquired / Procured Summary (<span id="vendor_name_heading" class="text-primary">All Vendors</span>)</span>
                                <button class="btn btn-sm btn-link p-0 text-muted" type="button" data-bs-toggle="collapse" data-bs-target="#vendorMaterialsCollapse">
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                            </div>
                            <div class="collapse show" id="vendorMaterialsCollapse">
                                <div class="card-body p-3" id="vendor_materials_breakdown_body" style="max-height: 220px; overflow-y: auto;">
                                    <span class="text-muted fs-12">Loading material breakdown...</span>
                                </div>
                            </div>
                        </div>

                        <!-- Purchase Orders & Payments Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover text-nowrap w-100" id="vendorPoTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>PO #</th>
                                        <th>Vendor</th>
                                        <th>Project</th>
                                        <th>Materials Taken (Against PO)</th>
                                        <th class="text-end">PO Amount (৳)</th>
                                        <th class="text-end">Paid (৳)</th>
                                        <th class="text-end">Due (৳)</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- AJAX Data -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Contractor Audits Tab -->
                    <div class="tab-pane" id="contractor-audit-tab" role="tabpanel">
                        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
                            <div>
                                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-person-workspace text-primary me-2"></i>Contractor Audits: Project Bills & Payment Verification</h6>
                                <span class="text-muted fs-12">Audit contractor bills by trade/expertise, project allocations, approved claims, paid amounts, and remaining dues</span>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="card border mb-3 shadow-none bg-light-subtle">
                            <div class="card-body p-3">
                                <div class="row g-2 align-items-end">
                                    <div class="col-lg-3 col-md-6">
                                        <label class="form-label fs-12 fw-semibold text-dark mb-1"><i class="bi bi-people me-1 text-primary"></i>Contractor & Trade</label>
                                        <select class="form-select form-select-sm select2" id="contractor_filter" data-placeholder="All Contractors">
                                            <option value="">All Contractors</option>
                                            <?php if (!empty($contractors)): ?>
                                                <?php foreach ($contractors as $c): ?>
                                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?><?= !empty($c['trade']) ? ' — ' . htmlspecialchars(ucwords(str_replace('_', ' ', $c['trade']))) : (!empty($c['role']) ? ' (' . htmlspecialchars($c['role']) . ')' : '') ?></option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <label class="form-label fs-12 fw-semibold text-dark mb-1"><i class="bi bi-building me-1 text-info"></i>Project</label>
                                        <select class="form-select form-select-sm select2" id="contractor_project_filter" data-placeholder="All Projects">
                                            <option value="">All Projects</option>
                                            <?php if (!empty($projects)): ?>
                                                <?php foreach ($projects as $p): ?>
                                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-lg-2 col-md-6">
                                        <label class="form-label fs-12 fw-semibold text-dark mb-1"><i class="bi bi-tag me-1 text-warning"></i>Status</label>
                                        <select class="form-select form-select-sm" id="contractor_status_filter">
                                            <option value="">All Statuses</option>
                                            <option value="submitted">Submitted</option>
                                            <option value="verified">Verified</option>
                                            <option value="qc_approved">QC Approved</option>
                                            <option value="approved">Approved</option>
                                            <option value="partially_paid">Partially Paid</option>
                                            <option value="paid">Paid</option>
                                            <option value="rejected">Rejected</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-2 col-md-6">
                                        <label class="form-label fs-12 fw-semibold text-dark mb-1"><i class="bi bi-search me-1 text-secondary"></i>Search</label>
                                        <input type="text" class="form-control form-control-sm" id="contractor_search_input" placeholder="Work desc / Bill #...">
                                    </div>
                                    <div class="col-lg-2 col-md-12 d-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-primary flex-fill" id="btn_filter_contractor"><i class="bi bi-search me-1"></i>Filter</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn_reset_contractor" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KPI Summary Cards -->
                        <div class="row g-3 mb-3">
                            <div class="col-xl col-md-4 col-sm-6">
                                <div class="card border-0 shadow-sm mb-0 h-100" style="background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%); border-left: 4px solid #4f46e5 !important;">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <span class="fs-12 fw-semibold text-muted text-uppercase">Total Bills</span>
                                                <h4 class="mb-0 fw-bold text-dark mt-1" id="contractor_kpi_bills"><span class="spinner-border spinner-border-sm text-primary"></span></h4>
                                            </div>
                                            <div class="avatar avatar-md bg-indigo text-white rounded-3">
                                                <i class="bi bi-receipt fs-18"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl col-md-4 col-sm-6">
                                <div class="card border-0 shadow-sm mb-0 h-100" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border-left: 4px solid #f59e0b !important;">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <span class="fs-12 fw-semibold text-muted text-uppercase">Total Requested</span>
                                                <h4 class="mb-0 fw-bold text-warning mt-1" id="contractor_kpi_requested"><span class="spinner-border spinner-border-sm text-warning"></span></h4>
                                            </div>
                                            <div class="avatar avatar-md bg-warning text-white rounded-3">
                                                <i class="bi bi-cash-coin fs-18"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl col-md-4 col-sm-6">
                                <div class="card border-0 shadow-sm mb-0 h-100" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-left: 4px solid #16a34a !important;">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <span class="fs-12 fw-semibold text-muted text-uppercase">Total Approved</span>
                                                <h4 class="mb-0 fw-bold text-success mt-1" id="contractor_kpi_approved"><span class="spinner-border spinner-border-sm text-success"></span></h4>
                                            </div>
                                            <div class="avatar avatar-md bg-success text-white rounded-3">
                                                <i class="bi bi-shield-check fs-18"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl col-md-6 col-sm-6">
                                <div class="card border-0 shadow-sm mb-0 h-100" style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border-left: 4px solid #059669 !important;">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <span class="fs-12 fw-semibold text-muted text-uppercase">Total Paid</span>
                                                <h4 class="mb-0 fw-bold text-success mt-1" id="contractor_kpi_paid"><span class="spinner-border spinner-border-sm text-success"></span></h4>
                                            </div>
                                            <div class="avatar avatar-md bg-success-subtle text-success rounded-3 border border-success">
                                                <i class="bi bi-check-circle fs-18"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl col-md-6 col-sm-12">
                                <div class="card border-0 shadow-sm mb-0 h-100" style="background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%); border-left: 4px solid #e11d48 !important;">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <span class="fs-12 fw-semibold text-muted text-uppercase">Total Due</span>
                                                <h4 class="mb-0 fw-bold text-danger mt-1" id="contractor_kpi_due"><span class="spinner-border spinner-border-sm text-danger"></span></h4>
                                            </div>
                                            <div class="avatar avatar-md bg-danger text-white rounded-3">
                                                <i class="bi bi-exclamation-octagon fs-18"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contractor Bills Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover text-nowrap w-100" id="contractorBillTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Bill #</th>
                                        <th>Contractor (Trade)</th>
                                        <th>Project</th>
                                        <th>Bill Type & Description</th>
                                        <th class="text-end">Requested (৳)</th>
                                        <th class="text-end">Approved (৳)</th>
                                        <th class="text-end">Paid (৳)</th>
                                        <th class="text-end">Due (৳)</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- AJAX Data -->
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
/* ── Audit Trail Timeline ────────────────────────────────── */
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

/* ── Mobile Responsive Fixes ─────────────────────────────── */
@media (max-width: 767.98px) {
    /* DataTables controls */
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        text-align: left !important;
        margin-bottom: 8px;
    }
    .dataTables_wrapper .dataTables_filter input {
        width: 100% !important;
        margin-left: 0 !important;
    }
    .dataTables_wrapper .dataTables_info {
        text-align: center;
        font-size: 11px;
        padding-top: 8px;
    }
    .dataTables_wrapper .dataTables_paginate {
        text-align: center !important;
        margin-top: 8px;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 4px 8px !important;
        font-size: 11px !important;
    }
    /* Audit trail card layout */
    .audit-step .audit-card .d-flex {
        flex-direction: column;
        gap: 6px;
    }
    .audit-step .audit-card .d-flex .text-end {
        text-align: left !important;
        min-width: auto !important;
    }
    /* Filter button alignment */
    .page-header-breadcrumb h1 {
        font-size: 16px !important;
    }
    /* Tab labels */
    .nav-tabs .nav-link {
        font-size: 12px;
        padding: 8px 10px;
        white-space: nowrap;
    }
}
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
                    let actions = '<a href="'+url+'" target="_blank" class="btn btn-sm btn-outline-info me-1" title="View Details"><i class="bi bi-eye"></i> Details</a>';

                    let status = (row.mgmt_status || '').toLowerCase();
                    let isPaid = (parseFloat(row.paid_amount) > 0) || ['paid', 'fully_paid', 'partially_paid'].includes(status);
                    let module = '';
                    if (row.request_type === 'Cashout') {
                        module = 'cashout_requests';
                    } else if (row.request_type === 'Vendor Payment') {
                        module = 'vendor_payment';
                    } else if (row.request_type === 'Contractor Bill') {
                        module = 'contractor_bill';
                    }

                    if (isPaid && module) {
                        let voucherUrl = base_url + '/print-debit-voucher?module=' + encodeURIComponent(module) + '&id=' + row.ref_id;
                        actions += '<a href="' + voucherUrl + '" target="_blank" class="btn btn-sm btn-primary rounded-2 px-2 shadow-sm fw-semibold me-1" title="View, Print & Download Payment Voucher"><i class="fa-solid fa-file-invoice-dollar me-1"></i>Payment Voucher</a>';
                    }

                    actions += '<button class="btn btn-sm btn-outline-primary" onclick="openAuditTrail(\'' + row.request_type + '\', \'' + row.ref_id + '\')" title="Audit Trail"><i class="bi bi-clock-history me-1"></i>Audit Trail</button>';
                    return actions;
                }
            }
        ]
    });

    // Helper for formatting BDT currency
    function formatBDT(amount) {
        var num = parseFloat(amount) || 0;
        return '৳ ' + num.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Initialize Select2 if available
    if ($.fn.select2) {
        $('#vendor_filter, #vendor_project_filter, #contractor_filter, #contractor_project_filter').select2({
            width: '100%'
        });
    }

    // Vendor Purchase Orders & Payments DataTable
    var vendorPoTable = $('#vendorPoTable').DataTable({
        responsive: true,
        processing: true,
        serverSide: false,
        ajax: {
            url: '<?= $GLOBALS['base_url'] ?>/api/qc-audit/vendor-pos',
            data: function(d) {
                d.vendor_id = $('#vendor_filter').val();
                d.project_id = $('#vendor_project_filter').val();
                d.status = $('#vendor_status_filter').val();
                d.search = $('#vendor_search_input').val();
            },
            dataSrc: function(json) {
                if (json.summary) {
                    $('#vendor_kpi_pos').text((json.summary.total_pos || 0).toLocaleString());
                    $('#vendor_kpi_materials').text((json.summary.material_count || 0).toLocaleString());
                    $('#vendor_kpi_amount').text(formatBDT(json.summary.total_amount));
                    $('#vendor_kpi_paid').text(formatBDT(json.summary.total_paid));
                    $('#vendor_kpi_due').text(formatBDT(json.summary.total_due));

                    var selectedVendorText = $('#vendor_filter option:selected').text();
                    if ($('#vendor_filter').val()) {
                        $('#vendor_name_heading').text(selectedVendorText);
                    } else {
                        $('#vendor_name_heading').text('All Vendors');
                    }

                    var mbHtml = '';
                    if (json.summary.materials_breakdown && json.summary.materials_breakdown.length > 0) {
                        json.summary.materials_breakdown.forEach(function(m) {
                            var qtyStr = parseFloat(m.total_quantity || 0).toLocaleString() + (m.unit ? ' ' + m.unit : '');
                            var amtStr = formatBDT(m.total_amount);
                            mbHtml += '<span class="badge bg-white text-dark border shadow-sm p-2 me-2 mb-2 fs-12 d-inline-flex align-items-center gap-1">' +
                                '<i class="bi bi-box-seam text-primary"></i>' +
                                '<strong class="text-dark">' + m.material_name + '</strong>: ' +
                                '<span class="text-primary fw-semibold">' + qtyStr + '</span>' +
                                '<span class="text-muted ms-1 small">(' + amtStr + ')</span>' +
                                '</span>';
                        });
                        $('#vendor_materials_summary_card').show();
                    } else {
                        mbHtml = '<span class="text-muted fs-12">No materials found for this selection.</span>';
                    }
                    $('#vendor_materials_breakdown_body').html(mbHtml);
                }
                return json.data || [];
            }
        },
        columns: [
            { 
                data: 'order_date',
                render: function(data) {
                    if (!data) return '-';
                    var d = new Date(data);
                    return isNaN(d.getTime()) ? data : d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                }
            },
            { 
                data: 'po_id',
                render: function(data) {
                    let base_url = '<?= $GLOBALS['base_url'] ?>';
                    return '<a href="' + base_url + '/con-purchase-order/' + data + '" target="_blank" class="fw-bold text-primary">#PO-' + data + '</a>';
                }
            },
            { 
                data: 'vendor_name',
                render: function(data, type, row) {
                    var out = '<span class="fw-semibold text-dark">' + (data || '-') + '</span>';
                    if (row.vendor_phone) {
                        out += '<br><small class="text-muted"><i class="bi bi-telephone me-1"></i>' + row.vendor_phone + '</small>';
                    }
                    return out;
                }
            },
            { data: 'project_name', defaultContent: '<span class="text-muted">-</span>' },
            { 
                data: 'materials_summary',
                render: function(data, type, row) {
                    if (!data || data === 'No items specified') return '<span class="text-muted small">No items</span>';
                    try {
                        var items = JSON.parse(row.items_json || '[]');
                        if (items.length > 0) {
                            var badges = '';
                            items.slice(0, 3).forEach(function(it) {
                                badges += '<span class="badge bg-light text-primary border me-1 mb-1" style="font-size:11px;">' +
                                    it.name + ' (' + parseFloat(it.qty).toLocaleString() + (it.unit ? ' ' + it.unit : '') + ')' +
                                    '</span>';
                            });
                            if (items.length > 3) {
                                badges += '<span class="badge bg-secondary-subtle text-secondary" style="font-size:10px;">+' + (items.length - 3) + ' more</span>';
                            }
                            return badges;
                        }
                    } catch(e) {}
                    return '<small class="text-muted">' + data + '</small>';
                }
            },
            { 
                data: 'po_total_amount',
                className: 'text-end fw-semibold',
                render: function(data) { return formatBDT(data); }
            },
            { 
                data: 'paid_amount',
                className: 'text-end fw-semibold text-success',
                render: function(data) { return formatBDT(data); }
            },
            { 
                data: 'due_amount',
                className: 'text-end fw-semibold text-danger',
                render: function(data) { return formatBDT(data); }
            },
            { 
                data: null,
                render: function(data, type, row) {
                    var status = row.po_status || 'Pending';
                    var badgeClass = 'bg-secondary';
                    if (status === 'Approved') badgeClass = 'bg-success';
                    else if (status === 'QC Approved') badgeClass = 'bg-info';
                    else if (status === 'Fully Received') badgeClass = 'bg-primary';
                    else if (status === 'Partially Received') badgeClass = 'bg-warning text-dark';
                    else if (status === 'Issued') badgeClass = 'bg-info text-dark';
                    else if (status === 'Cancelled') badgeClass = 'bg-danger';

                    var html = '<span class="badge ' + badgeClass + '">' + status + '</span>';
                    if (row.vpr_status) {
                        var payBadge = (row.vpr_status === 'Fully_Paid') ? 'bg-success' : (row.vpr_status === 'Partially_Paid' ? 'bg-warning text-dark' : 'bg-secondary');
                        html += '<br><span class="badge ' + payBadge + ' mt-1" style="font-size:10px;">Pay: ' + row.vpr_status.replace('_', ' ') + '</span>';
                    }
                    return html;
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    let base_url = '<?= $GLOBALS['base_url'] ?>';
                    let poUrl = base_url + '/con-purchase-order/' + row.po_id;
                    let actions = '<a href="' + poUrl + '" target="_blank" class="btn btn-sm btn-outline-info me-1" title="View PO Details"><i class="bi bi-eye"></i> PO</a>';
                    if (row.vpr_id) {
                        let vprUrl = base_url + '/con-vendor-payment-show/' + row.vpr_id;
                        actions += '<a href="' + vprUrl + '" target="_blank" class="btn btn-sm btn-outline-secondary me-1" title="View Vendor Payment Request"><i class="bi bi-wallet2"></i> Bill</a>';

                        let isPaid = (parseFloat(row.paid_amount) > 0) || ['Paid', 'Partially_Paid', 'Fully_Paid'].includes(row.vpr_status);
                        if (isPaid) {
                            let voucherUrl = base_url + '/print-debit-voucher?module=vendor_payment&id=' + row.vpr_id;
                            actions += '<a href="' + voucherUrl + '" target="_blank" class="btn btn-sm btn-primary rounded-2 px-2 shadow-sm fw-semibold me-1" title="View, Print & Download Payment Voucher"><i class="fa-solid fa-file-invoice-dollar me-1"></i>Payment Voucher</a>';
                        }

                        actions += '<button class="btn btn-sm btn-outline-primary" onclick="openAuditTrail(\'Vendor Payment\', \'' + row.vpr_id + '\')" title="Audit Trail"><i class="bi bi-clock-history"></i></button>';
                    }
                    return actions;
                }
            }
        ]
    });

    // Contractor Bills DataTable
    var contractorBillTable = $('#contractorBillTable').DataTable({
        responsive: true,
        processing: true,
        serverSide: false,
        ajax: {
            url: '<?= $GLOBALS['base_url'] ?>/api/qc-audit/contractor-bills',
            data: function(d) {
                d.contractor_id = $('#contractor_filter').val();
                d.project_id = $('#contractor_project_filter').val();
                d.status = $('#contractor_status_filter').val();
                d.search = $('#contractor_search_input').val();
            },
            dataSrc: function(json) {
                if (json.summary) {
                    $('#contractor_kpi_bills').text((json.summary.total_bills || 0).toLocaleString());
                    $('#contractor_kpi_requested').text(formatBDT(json.summary.total_requested));
                    $('#contractor_kpi_approved').text(formatBDT(json.summary.total_approved));
                    $('#contractor_kpi_paid').text(formatBDT(json.summary.total_paid));
                    $('#contractor_kpi_due').text(formatBDT(json.summary.total_due));
                }
                return json.data || [];
            }
        },
        columns: [
            { 
                data: 'bill_date',
                render: function(data) {
                    if (!data) return '-';
                    var d = new Date(data);
                    return isNaN(d.getTime()) ? data : d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                }
            },
            { 
                data: 'bill_id',
                render: function(data) {
                    let base_url = '<?= $GLOBALS['base_url'] ?>';
                    return '<a href="' + base_url + '/con-contractor-bill/' + data + '" target="_blank" class="fw-bold text-primary">#BILL-' + data + '</a>';
                }
            },
            { 
                data: 'contractor_name',
                render: function(data, type, row) {
                    var out = '<span class="fw-semibold text-dark">' + (data || '-') + '</span>';
                    if (row.contractor_trade) {
                        out += '<br><span class="badge bg-light text-primary border border-primary-subtle" style="font-size: 11px;"><i class="bi bi-tools me-1"></i>' + row.contractor_trade.replace('_', ' ') + '</span>';
                    } else if (row.contractor_role) {
                        out += '<br><small class="text-muted">' + row.contractor_role + '</small>';
                    }
                    if (row.contractor_phone) {
                        out += '<br><small class="text-muted"><i class="bi bi-telephone me-1"></i>' + row.contractor_phone + '</small>';
                    }
                    return out;
                }
            },
            { data: 'project_name', defaultContent: '<span class="text-muted">-</span>' },
            { 
                data: null,
                render: function(data, type, row) {
                    var typeBadge = (row.bill_type === 'Advance') ? 'bg-warning-transparent' : ((row.bill_type === 'Retention') ? 'bg-info-transparent' : 'bg-primary-transparent');
                    var out = '<span class="badge ' + typeBadge + ' mb-1">' + (row.bill_type || 'Progress') + '</span>';
                    if (row.work_description) {
                        var desc = row.work_description.length > 50 ? row.work_description.substring(0, 50) + '...' : row.work_description;
                        out += '<br><small class="text-muted" title="' + row.work_description.replace(/"/g, '&quot;') + '">' + desc + '</small>';
                    }
                    return out;
                }
            },
            { 
                data: 'bill_amount',
                className: 'text-end fw-semibold',
                render: function(data) { return formatBDT(data); }
            },
            { 
                data: 'approved_amount',
                className: 'text-end fw-semibold text-info',
                render: function(data) { return formatBDT(data); }
            },
            { 
                data: 'paid_amount',
                className: 'text-end fw-semibold text-success',
                render: function(data) { return formatBDT(data); }
            },
            { 
                data: 'due_amount',
                className: 'text-end fw-semibold text-danger',
                render: function(data) { return formatBDT(data); }
            },
            { 
                data: 'bill_status',
                render: function(data) {
                    var status = (data || 'submitted').toLowerCase();
                    var badge = 'secondary';
                    if (status === 'draft') badge = 'warning';
                    else if (status === 'submitted') badge = 'info';
                    else if (status === 'verified') badge = 'primary';
                    else if (status === 'qc_approved') badge = 'info';
                    else if (status === 'approved') badge = 'success';
                    else if (status === 'partially_paid') badge = 'warning';
                    else if (status === 'paid' || status === 'fully_paid') badge = 'success';
                    else if (status === 'rejected') badge = 'danger';
                    return '<span class="badge bg-' + badge + '">' + status.replace('_', ' ').toUpperCase() + '</span>';
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    let base_url = '<?= $GLOBALS['base_url'] ?>';
                    let billUrl = base_url + '/con-contractor-bill/' + row.bill_id;
                    let actions = '<a href="' + billUrl + '" target="_blank" class="btn btn-sm btn-outline-info me-1" title="View Bill Details"><i class="bi bi-eye"></i> Details</a>';

                    let isPaid = (parseFloat(row.paid_amount) > 0) || ['paid', 'partially_paid', 'fully_paid'].includes((row.bill_status || '').toLowerCase());
                    if (isPaid) {
                        let voucherUrl = base_url + '/print-debit-voucher?module=contractor_bill&id=' + row.bill_id;
                        actions += '<a href="' + voucherUrl + '" target="_blank" class="btn btn-sm btn-primary rounded-2 px-2 shadow-sm fw-semibold me-1" title="View, Print & Download Payment Voucher"><i class="fa-solid fa-file-invoice-dollar me-1"></i>Payment Voucher</a>';
                    }

                    actions += '<button class="btn btn-sm btn-outline-primary" onclick="openAuditTrail(\'Contractor Bill\', \'' + row.bill_id + '\')" title="Audit Trail"><i class="bi bi-clock-history"></i></button>';
                    return actions;
                }
            }
        ]
    });

    // Filter & Reset buttons for Vendor POs
    $('#btn_filter_vendor').on('click', function() {
        vendorPoTable.ajax.reload();
    });
    $('#vendor_filter, #vendor_project_filter, #vendor_status_filter').on('change', function() {
        vendorPoTable.ajax.reload();
    });
    $('#vendor_search_input').on('keyup', function(e) {
        if (e.key === 'Enter') vendorPoTable.ajax.reload();
    });
    $('#btn_reset_vendor').on('click', function() {
        $('#vendor_filter').val('').trigger('change');
        $('#vendor_project_filter').val('').trigger('change');
        $('#vendor_status_filter').val('');
        $('#vendor_search_input').val('');
        vendorPoTable.ajax.reload();
    });

    // Filter & Reset buttons for Contractor Bills
    $('#btn_filter_contractor').on('click', function() {
        contractorBillTable.ajax.reload();
    });
    $('#contractor_filter, #contractor_project_filter, #contractor_status_filter').on('change', function() {
        contractorBillTable.ajax.reload();
    });
    $('#contractor_search_input').on('keyup', function(e) {
        if (e.key === 'Enter') contractorBillTable.ajax.reload();
    });
    $('#btn_reset_contractor').on('click', function() {
        $('#contractor_filter').val('').trigger('change');
        $('#contractor_project_filter').val('').trigger('change');
        $('#contractor_status_filter').val('');
        $('#contractor_search_input').val('');
        contractorBillTable.ajax.reload();
    });

    // Responsive tab resize
    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust().responsive.recalc();
    });
});
<?php $page_script = ob_get_clean(); ?>

