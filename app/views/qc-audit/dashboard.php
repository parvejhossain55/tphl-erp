

        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><?= $page_title ?? 'QC & Internal Audit' ?></h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?= $page_title ?></li>
                    </ol>
                </nav>
            </div>
        </div>

        <style>
            .quick-link { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-radius: 10px; border: 1px solid #e9ecef; margin-bottom: 10px; transition: all 0.2s; text-decoration: none; color: #333; }
            .quick-link:hover { background: #f0f0ff; border-color: #6c5ffc; transform: translateX(4px); }
            .quick-link .ql-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        </style>

        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header border-bottom-0">
                        <h4 class="card-title">Welcome to QC & Internal Audit Dashboard</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">This module allows you to track and audit all financial transactions, material usage, and stock observation across the system.</p>
                        
                        <div class="row mt-3 mb-4">
                            <div class="col-md-3 mb-3 mb-md-0">
                                <div class="card bg-primary-transparent border border-primary border-opacity-10">
                                    <div class="card-body text-center py-3">
                                        <h5 class="fw-semibold mb-1">Pending QC Approvals</h5>
                                        <h3 class="fw-bold mb-0 text-primary"><?= number_format($stats['pending'] ?? 0) ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3 mb-md-0">
                                <div class="card bg-success-transparent border border-success border-opacity-10">
                                    <div class="card-body text-center py-3">
                                        <h5 class="fw-semibold mb-1">Approved By QC</h5>
                                        <h3 class="fw-bold mb-0 text-success"><?= number_format($stats['approved'] ?? 0) ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <a href="<?= $GLOBALS['base_url'] ?>/qc-requests" class="quick-link">
                                    <div class="ql-icon bg-success-transparent text-success"><i class="bi bi-check2-square"></i></div>
                                    <div>
                                        <strong>QC Approvals</strong><br>
                                        <small class="text-muted">Review & approve pending requests</small>
                                    </div>
                                    <?php if (($stats['pending'] ?? 0) > 0): ?>
                                        <span class="badge bg-danger ms-auto"><?= $stats['pending'] ?></span>
                                    <?php endif; ?>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="<?= $GLOBALS['base_url'] ?>/qc-advanced-audit" class="quick-link">
                                    <div class="ql-icon bg-info-transparent text-info"><i class="bi bi-filter-square"></i></div>
                                    <div>
                                        <strong>Advanced Audit</strong><br>
                                        <small class="text-muted">Materials, Purchases & Payments</small>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
