<style>
    /* 5 Columns per row responsive grid for compact mini-cards */
    .module-mini-card-col {
        flex: 0 0 20%;
        max-width: 20%;
        padding: 4px;
        margin-bottom: 6px;
    }
    @media (max-width: 1400px) {
        .module-mini-card-col {
            flex: 0 0 20%;
            max-width: 20%;
        }
    }
    @media (max-width: 1200px) {
        .module-mini-card-col {
            flex: 0 0 25%;
            max-width: 25%;
        }
    }
    @media (max-width: 992px) {
        .module-mini-card-col {
            flex: 0 0 33.333333%;
            max-width: 33.333333%;
        }
    }
    @media (max-width: 768px) {
        .module-mini-card-col {
            flex: 0 0 50%;
            max-width: 50%;
        }
    }
    @media (max-width: 500px) {
        .module-mini-card-col {
            flex: 0 0 100%;
            max-width: 100%;
        }
    }

    .module-mini-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 8px 10px;
        transition: all 0.2s ease-in-out;
        cursor: pointer;
        display: flex;
        align-items: center;
        text-decoration: none !important;
        position: relative;
        min-height: 64px;
        height: 64px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        user-select: none;
    }
    .module-mini-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 14px rgba(0, 0, 0, 0.07);
        border-color: #cbd5e1;
    }
    .module-mini-card.is-active-filter {
        border: 2px solid #3b82f6 !important;
        background: #f0f7ff !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.18) !important;
    }
    .pulse-dot-amber {
        display: inline-block;
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background-color: #f59e0b;
        animation: pulseAnimationAmber 1.6s infinite;
        margin-right: 3px;
    }
    @keyframes pulseAnimationAmber {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 4px rgba(245, 158, 11, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
    }

    /* Table Rows - Light & Dark Mode */
    .clickable-approval-row.actionable-approval-row td {
        background-color: #f8fafc;
    }
    .clickable-approval-row:hover td {
        background-color: rgba(var(--bs-primary-rgb), 0.06) !important;
    }
    .hover-primary:hover {
        color: var(--bs-primary) !important;
    }

    /* Dark Mode Themes: Supports [bd-theme="bd-theme-dark"], [data-bs-theme="dark"], body.dark-mode, body.dark-only, html.dark, body.dark, [data-theme="dark"] */
    [bd-theme="bd-theme-dark"] #approvalRequestsTable,
    [data-bs-theme="dark"] #approvalRequestsTable,
    body.dark-mode #approvalRequestsTable,
    body.dark-only #approvalRequestsTable,
    html.dark #approvalRequestsTable,
    body.dark #approvalRequestsTable,
    [data-theme="dark"] #approvalRequestsTable {
        background-color: #1F2937 !important;
        border: 1px solid #374151 !important;
        color: #e5e7eb !important;
    }

    /* Filter Toolbar & Card Header/Footer in Dark Mode */
    [bd-theme="bd-theme-dark"] #approvalRequestsTable .card-header,
    [data-bs-theme="dark"] #approvalRequestsTable .card-header,
    body.dark-mode #approvalRequestsTable .card-header,
    body.dark-only #approvalRequestsTable .card-header,
    html.dark #approvalRequestsTable .card-header,
    body.dark #approvalRequestsTable .card-header,
    [data-theme="dark"] #approvalRequestsTable .card-header,
    [bd-theme="bd-theme-dark"] #approvalRequestsTable .card-footer,
    [data-bs-theme="dark"] #approvalRequestsTable .card-footer,
    body.dark-mode #approvalRequestsTable .card-footer,
    body.dark-only #approvalRequestsTable .card-footer,
    html.dark #approvalRequestsTable .card-footer,
    body.dark #approvalRequestsTable .card-footer,
    [data-theme="dark"] #approvalRequestsTable .card-footer {
        background-color: #1F2937 !important;
        border-color: #374151 !important;
        color: #e5e7eb !important;
    }

    /* Bulk Action Bar / Light Panes in Dark Mode */
    [bd-theme="bd-theme-dark"] #approvalRequestsTable .bg-light,
    [data-bs-theme="dark"] #approvalRequestsTable .bg-light,
    body.dark-mode #approvalRequestsTable .bg-light,
    body.dark-only #approvalRequestsTable .bg-light,
    html.dark #approvalRequestsTable .bg-light,
    body.dark #approvalRequestsTable .bg-light,
    [data-theme="dark"] #approvalRequestsTable .bg-light {
        background-color: #182230 !important;
        border-color: #374151 !important;
        color: #9ca3af !important;
    }

    /* Filter inputs & selects in Dark Mode */
    [bd-theme="bd-theme-dark"] #approvalRequestsTable .form-control,
    [data-bs-theme="dark"] #approvalRequestsTable .form-control,
    body.dark-mode #approvalRequestsTable .form-control,
    body.dark-only #approvalRequestsTable .form-control,
    html.dark #approvalRequestsTable .form-control,
    body.dark #approvalRequestsTable .form-control,
    [data-theme="dark"] #approvalRequestsTable .form-control,
    [bd-theme="bd-theme-dark"] #approvalRequestsTable .form-select,
    [data-bs-theme="dark"] #approvalRequestsTable .form-select,
    body.dark-mode #approvalRequestsTable .form-select,
    body.dark-only #approvalRequestsTable .form-select,
    html.dark #approvalRequestsTable .form-select,
    body.dark #approvalRequestsTable .form-select,
    [data-theme="dark"] #approvalRequestsTable .form-select {
        background-color: #111827 !important;
        border-color: #4b5563 !important;
        color: #f3f4f6 !important;
    }

    /* Main Table & DataTables Dark Styles */
    [bd-theme="bd-theme-dark"] #allApprovalRequestsTable,
    [data-bs-theme="dark"] #allApprovalRequestsTable,
    body.dark-mode #allApprovalRequestsTable,
    body.dark-only #allApprovalRequestsTable,
    html.dark #allApprovalRequestsTable,
    body.dark #allApprovalRequestsTable,
    [data-theme="dark"] #allApprovalRequestsTable {
        --bs-table-bg: #1F2937 !important;
        --bs-table-accent-bg: transparent !important;
        --bs-table-hover-bg: #2d3b4e !important;
        background-color: #1F2937 !important;
        color: #e5e7eb !important;
        border-color: #374151 !important;
    }

    /* Table Head in Dark Mode */
    [bd-theme="bd-theme-dark"] #allApprovalRequestsTable thead,
    [data-bs-theme="dark"] #allApprovalRequestsTable thead,
    body.dark-mode #allApprovalRequestsTable thead,
    body.dark-only #allApprovalRequestsTable thead,
    html.dark #allApprovalRequestsTable thead,
    body.dark #allApprovalRequestsTable thead,
    [data-theme="dark"] #allApprovalRequestsTable thead,
    [bd-theme="bd-theme-dark"] #allApprovalRequestsTable thead th,
    [data-bs-theme="dark"] #allApprovalRequestsTable thead th,
    body.dark-mode #allApprovalRequestsTable thead th,
    body.dark-only #allApprovalRequestsTable thead th,
    html.dark #allApprovalRequestsTable thead th,
    body.dark #allApprovalRequestsTable thead th,
    [data-theme="dark"] #allApprovalRequestsTable thead th {
        background-color: #111827 !important;
        color: #9ca3af !important;
        border-color: #374151 !important;
    }

    /* Regular Table Rows & Cells in Dark Mode */
    [bd-theme="bd-theme-dark"] #allApprovalRequestsTable tbody tr,
    [data-bs-theme="dark"] #allApprovalRequestsTable tbody tr,
    body.dark-mode #allApprovalRequestsTable tbody tr,
    body.dark-only #allApprovalRequestsTable tbody tr,
    html.dark #allApprovalRequestsTable tbody tr,
    body.dark #allApprovalRequestsTable tbody tr,
    [data-theme="dark"] #allApprovalRequestsTable tbody tr,
    [bd-theme="bd-theme-dark"] #allApprovalRequestsTable tbody tr > td,
    [data-bs-theme="dark"] #allApprovalRequestsTable tbody tr > td,
    body.dark-mode #allApprovalRequestsTable tbody tr > td,
    body.dark-only #allApprovalRequestsTable tbody tr > td,
    html.dark #allApprovalRequestsTable tbody tr > td,
    body.dark #allApprovalRequestsTable tbody tr > td,
    [data-theme="dark"] #allApprovalRequestsTable tbody tr > td,
    [bd-theme="bd-theme-dark"] #allApprovalRequestsTable.dataTable tbody tr > td,
    [data-bs-theme="dark"] #allApprovalRequestsTable.dataTable tbody tr > td,
    body.dark-mode #allApprovalRequestsTable.dataTable tbody tr > td,
    body.dark-only #allApprovalRequestsTable.dataTable tbody tr > td,
    html.dark #allApprovalRequestsTable.dataTable tbody tr > td,
    body.dark #allApprovalRequestsTable.dataTable tbody tr > td,
    [data-theme="dark"] #allApprovalRequestsTable.dataTable tbody tr > td {
        background-color: #1F2937 !important;
        color: #e5e7eb !important;
        border-color: #374151 !important;
    }

    /* Actionable Table Rows in Dark Mode (Subtle distinct dark tint) */
    [bd-theme="bd-theme-dark"] #allApprovalRequestsTable tbody tr.actionable-approval-row,
    [data-bs-theme="dark"] #allApprovalRequestsTable tbody tr.actionable-approval-row,
    body.dark-mode #allApprovalRequestsTable tbody tr.actionable-approval-row,
    body.dark-only #allApprovalRequestsTable tbody tr.actionable-approval-row,
    html.dark #allApprovalRequestsTable tbody tr.actionable-approval-row,
    body.dark #allApprovalRequestsTable tbody tr.actionable-approval-row,
    [data-theme="dark"] #allApprovalRequestsTable tbody tr.actionable-approval-row,
    [bd-theme="bd-theme-dark"] #allApprovalRequestsTable tbody tr.actionable-approval-row > td,
    [data-bs-theme="dark"] #allApprovalRequestsTable tbody tr.actionable-approval-row > td,
    body.dark-mode #allApprovalRequestsTable tbody tr.actionable-approval-row > td,
    body.dark-only #allApprovalRequestsTable tbody tr.actionable-approval-row > td,
    html.dark #allApprovalRequestsTable tbody tr.actionable-approval-row > td,
    body.dark #allApprovalRequestsTable tbody tr.actionable-approval-row > td,
    [data-theme="dark"] #allApprovalRequestsTable tbody tr.actionable-approval-row > td {
        background-color: #242f3e !important;
    }

    /* Hover Table Rows in Dark Mode */
    [bd-theme="bd-theme-dark"] #allApprovalRequestsTable tbody tr.clickable-approval-row:hover,
    [data-bs-theme="dark"] #allApprovalRequestsTable tbody tr.clickable-approval-row:hover,
    body.dark-mode #allApprovalRequestsTable tbody tr.clickable-approval-row:hover,
    body.dark-only #allApprovalRequestsTable tbody tr.clickable-approval-row:hover,
    html.dark #allApprovalRequestsTable tbody tr.clickable-approval-row:hover,
    body.dark #allApprovalRequestsTable tbody tr.clickable-approval-row:hover,
    [data-theme="dark"] #allApprovalRequestsTable tbody tr.clickable-approval-row:hover,
    [bd-theme="bd-theme-dark"] #allApprovalRequestsTable tbody tr.clickable-approval-row:hover > td,
    [data-bs-theme="dark"] #allApprovalRequestsTable tbody tr.clickable-approval-row:hover > td,
    body.dark-mode #allApprovalRequestsTable tbody tr.clickable-approval-row:hover > td,
    body.dark-only #allApprovalRequestsTable tbody tr.clickable-approval-row:hover > td,
    html.dark #allApprovalRequestsTable tbody tr.clickable-approval-row:hover > td,
    body.dark #allApprovalRequestsTable tbody tr.clickable-approval-row:hover > td,
    [data-theme="dark"] #allApprovalRequestsTable tbody tr.clickable-approval-row:hover > td {
        background-color: #2d3b4e !important;
    }

    /* Dark Mode Text Overrides */
    [bd-theme="bd-theme-dark"] #allApprovalRequestsTable .text-dark,
    [data-bs-theme="dark"] #allApprovalRequestsTable .text-dark,
    body.dark-mode #allApprovalRequestsTable .text-dark,
    body.dark-only #allApprovalRequestsTable .text-dark,
    html.dark #allApprovalRequestsTable .text-dark,
    body.dark #allApprovalRequestsTable .text-dark,
    [data-theme="dark"] #allApprovalRequestsTable .text-dark,
    [bd-theme="bd-theme-dark"] #approvalRequestsTable .text-dark,
    [data-bs-theme="dark"] #approvalRequestsTable .text-dark,
    body.dark-mode #approvalRequestsTable .text-dark,
    body.dark-only #approvalRequestsTable .text-dark,
    html.dark #approvalRequestsTable .text-dark,
    body.dark #approvalRequestsTable .text-dark,
    [data-theme="dark"] #approvalRequestsTable .text-dark {
        color: #f3f4f6 !important;
    }

    [bd-theme="bd-theme-dark"] #allApprovalRequestsTable a.text-dark:hover,
    [data-bs-theme="dark"] #allApprovalRequestsTable a.text-dark:hover,
    body.dark-mode #allApprovalRequestsTable a.text-dark:hover,
    body.dark-only #allApprovalRequestsTable a.text-dark:hover,
    html.dark #allApprovalRequestsTable a.text-dark:hover,
    body.dark #allApprovalRequestsTable a.text-dark:hover,
    [data-theme="dark"] #allApprovalRequestsTable a.text-dark:hover {
        color: #60a5fa !important;
    }

    /* Preserve dark text for badges with light/vibrant backgrounds like yellow/warning */
    [bd-theme="bd-theme-dark"] #allApprovalRequestsTable .badge.text-dark,
    [data-bs-theme="dark"] #allApprovalRequestsTable .badge.text-dark,
    body.dark-mode #allApprovalRequestsTable .badge.text-dark,
    body.dark-only #allApprovalRequestsTable .badge.text-dark,
    html.dark #allApprovalRequestsTable .badge.text-dark,
    body.dark #allApprovalRequestsTable .badge.text-dark,
    [data-theme="dark"] #allApprovalRequestsTable .badge.text-dark {
        color: #111827 !important;
    }

    /* Buttons inside Table in Dark Mode */
    [bd-theme="bd-theme-dark"] #allApprovalRequestsTable .btn-light,
    [data-bs-theme="dark"] #allApprovalRequestsTable .btn-light,
    body.dark-mode #allApprovalRequestsTable .btn-light,
    body.dark-only #allApprovalRequestsTable .btn-light,
    html.dark #allApprovalRequestsTable .btn-light,
    body.dark #allApprovalRequestsTable .btn-light,
    [data-theme="dark"] #allApprovalRequestsTable .btn-light {
        background-color: #374151 !important;
        border-color: #4b5563 !important;
        color: #e5e7eb !important;
    }

    /* Status Mini-Cards / Badges in Dark Mode */
    [bd-theme="bd-theme-dark"] #allApprovalRequestsTable .bg-warning-subtle,
    [data-bs-theme="dark"] #allApprovalRequestsTable .bg-warning-subtle,
    body.dark-mode #allApprovalRequestsTable .bg-warning-subtle,
    body.dark-only #allApprovalRequestsTable .bg-warning-subtle,
    html.dark #allApprovalRequestsTable .bg-warning-subtle,
    body.dark #allApprovalRequestsTable .bg-warning-subtle,
    [data-theme="dark"] #allApprovalRequestsTable .bg-warning-subtle {
        background-color: rgba(245, 158, 11, 0.16) !important;
        border-color: rgba(245, 158, 11, 0.35) !important;
    }

    [bd-theme="bd-theme-dark"] #allApprovalRequestsTable .bg-primary-subtle,
    [data-bs-theme="dark"] #allApprovalRequestsTable .bg-primary-subtle,
    body.dark-mode #allApprovalRequestsTable .bg-primary-subtle,
    body.dark-only #allApprovalRequestsTable .bg-primary-subtle,
    html.dark #allApprovalRequestsTable .bg-primary-subtle,
    body.dark #allApprovalRequestsTable .bg-primary-subtle,
    [data-theme="dark"] #allApprovalRequestsTable .bg-primary-subtle {
        background-color: rgba(59, 130, 246, 0.16) !important;
        border-color: rgba(59, 130, 246, 0.35) !important;
    }

    [bd-theme="bd-theme-dark"] #allApprovalRequestsTable .bg-success-subtle,
    [data-bs-theme="dark"] #allApprovalRequestsTable .bg-success-subtle,
    body.dark-mode #allApprovalRequestsTable .bg-success-subtle,
    body.dark-only #allApprovalRequestsTable .bg-success-subtle,
    html.dark #allApprovalRequestsTable .bg-success-subtle,
    body.dark #allApprovalRequestsTable .bg-success-subtle,
    [data-theme="dark"] #allApprovalRequestsTable .bg-success-subtle {
        background-color: rgba(16, 185, 129, 0.16) !important;
        border-color: rgba(16, 185, 129, 0.35) !important;
    }

    [bd-theme="bd-theme-dark"] #allApprovalRequestsTable .badge.bg-light,
    [data-bs-theme="dark"] #allApprovalRequestsTable .badge.bg-light,
    body.dark-mode #allApprovalRequestsTable .badge.bg-light,
    body.dark-only #allApprovalRequestsTable .badge.bg-light,
    html.dark #allApprovalRequestsTable .badge.bg-light,
    body.dark #allApprovalRequestsTable .badge.bg-light,
    [data-theme="dark"] #allApprovalRequestsTable .badge.bg-light {
        background-color: #374151 !important;
        color: #9ca3af !important;
        border-color: #4b5563 !important;
    }

    /* Top Module Mini Cards in Dark Mode */
    [bd-theme="bd-theme-dark"] .module-mini-card,
    [data-bs-theme="dark"] .module-mini-card,
    body.dark-mode .module-mini-card,
    body.dark-only .module-mini-card,
    html.dark .module-mini-card,
    body.dark .module-mini-card,
    [data-theme="dark"] .module-mini-card {
        background-color: #1F2937 !important;
        border-color: #374151 !important;
        color: #e5e7eb !important;
    }

    [bd-theme="bd-theme-dark"] .module-mini-card .text-dark,
    [data-bs-theme="dark"] .module-mini-card .text-dark,
    body.dark-mode .module-mini-card .text-dark,
    body.dark-only .module-mini-card .text-dark,
    html.dark .module-mini-card .text-dark,
    body.dark .module-mini-card .text-dark,
    [data-theme="dark"] .module-mini-card .text-dark {
        color: #f3f4f6 !important;
    }

    [bd-theme="bd-theme-dark"] .module-mini-card.is-active-filter,
    [data-bs-theme="dark"] .module-mini-card.is-active-filter,
    body.dark-mode .module-mini-card.is-active-filter,
    body.dark-only .module-mini-card.is-active-filter,
    html.dark .module-mini-card.is-active-filter,
    body.dark .module-mini-card.is-active-filter,
    [data-theme="dark"] .module-mini-card.is-active-filter {
        background-color: #1e3a5f !important;
        border-color: #3b82f6 !important;
    }

    /* Page title & headings in Dark Mode */
    [bd-theme="bd-theme-dark"] .container-fluid .text-dark,
    [data-bs-theme="dark"] .container-fluid .text-dark,
    body.dark-mode .container-fluid .text-dark,
    body.dark-only .container-fluid .text-dark,
    html.dark .container-fluid .text-dark,
    body.dark .container-fluid .text-dark,
    [data-theme="dark"] .container-fluid .text-dark {
        color: #f3f4f6 !important;
    }
</style>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-bold mb-1 text-dark">
                <i class="fa-solid fa-list-check text-primary me-2"></i>All System Approval Requests & Payout Tracking
            </h3>
            <p class="text-muted small mb-0">Unified dashboard for Management & Accounts to monitor all company requests
                across Sales, Construction Finance, HR, and Accounts.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= $base_url ?>/branch-disbursements" class="btn btn-sm btn-outline-success rounded-pill px-3 shadow-sm fw-semibold">
                <i class="fa-solid fa-building-columns me-1"></i>Office Payout Audit
            </a>
            <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fs-7 fw-semibold">
                <i class="fa-solid fa-shield-halved me-1"></i> Management & Accounts Audit Panel
            </span>
        </div>
    </div>

    <!-- Executive Management Approval Schedule Notice -->
    <?php include __DIR__ . '/../layouts/management_approval_notice.php'; ?>

    <!-- Company Dashboard Summary Metrics Row -->
    <div class="row mb-4">
        <!-- Total System Requests -->
        <div class="col-xl-3 col-md-6 col-sm-6">
            <a href="<?= $base_url ?>/all-approval-requests?status=all<?= !empty($filters['department']) ? '&department=' . urlencode($filters['department']) : '' ?><?= !empty($filters['source_table']) ? '&source_table=' . urlencode($filters['source_table']) : '' ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-primary hover-shadow transition-all">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold">Total Requests Created</span>
                            <div class="avatar avatar-sm bg-primary-subtle text-primary rounded-2">
                                <i class="fa-solid fa-folder-tree"></i>
                            </div>
                        </div>
                        <h3 class="fw-bold mb-1 text-dark">
                            <?= number_format($companyMetrics['total_requests']) ?> Items
                        </h3>
                        <span class="text-muted extra-small">Across all company departments &bull; View All</span>
                    </div>
                </div>
            </a>
        </div>

        <!-- Pending Approval Queue -->
        <div class="col-xl-3 col-md-6 col-sm-6">
            <a href="<?= $base_url ?>/all-approval-requests?status=all_pending<?= !empty($filters['department']) ? '&department=' . urlencode($filters['department']) : '' ?><?= !empty($filters['source_table']) ? '&source_table=' . urlencode($filters['source_table']) : '' ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-warning hover-shadow transition-all">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold">Pending Approval Queue</span>
                            <div class="avatar avatar-sm bg-warning-subtle text-warning rounded-2">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </div>
                        </div>
                        <h3 class="fw-bold mb-1 text-warning">৳
                            <?= number_format($companyMetrics['pending_amount'], 2) ?>
                        </h3>
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle extra-small rounded-pill">
                            <?= $companyMetrics['pending_count'] ?> Requests Awaiting Review
                        </span>
                    </div>
                </div>
            </a>
        </div>

        <!-- Total Approved Amount -->
        <div class="col-xl-3 col-md-6 col-sm-6">
            <a href="<?= $base_url ?>/all-approval-requests?status=pending_payout<?= !empty($filters['department']) ? '&department=' . urlencode($filters['department']) : '' ?><?= !empty($filters['source_table']) ? '&source_table=' . urlencode($filters['source_table']) : '' ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-info hover-shadow transition-all">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold">Total Approved Value</span>
                            <div class="avatar avatar-sm bg-info-subtle text-info rounded-2">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                        </div>
                        <h3 class="fw-bold mb-1 text-info">৳
                            <?= number_format($companyMetrics['approved_amount'], 2) ?>
                        </h3>
                        <span class="badge bg-info-subtle text-info border border-info-subtle extra-small rounded-pill">
                            <?= $companyMetrics['approved_count'] ?> Approved Requests (View Queue)
                        </span>
                    </div>
                </div>
            </a>
        </div>

        <!-- Total Accounts Disbursed / Paid -->
        <div class="col-xl-3 col-md-6 col-sm-6">
            <a href="<?= $base_url ?>/all-approval-requests?status=paid<?= !empty($filters['department']) ? '&department=' . urlencode($filters['department']) : '' ?><?= !empty($filters['source_table']) ? '&source_table=' . urlencode($filters['source_table']) : '' ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-success hover-shadow transition-all">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold">Total Accounts Disbursed</span>
                            <div class="avatar avatar-sm bg-success-subtle text-success rounded-2">
                                <i class="fa-solid fa-money-bill-wave"></i>
                            </div>
                        </div>
                        <h3 class="fw-bold mb-1 text-success">৳
                            <?= number_format($companyMetrics['total_disbursed'], 2) ?>
                        </h3>
                        <span class="badge bg-success-subtle text-success border border-success-subtle extra-small rounded-pill">
                            <i class="fa-solid fa-check-double me-1"></i>View Paid & Settled History
                        </span>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Module-Wise Requisition & Expense Tracking (Compact Cards: 5 Per Row) -->
    <?php if (!empty($moduleMetrics)): 
        $currentStatus = $filters['status'] ?? '';
        $isAllPendingMode = ($currentStatus === 'all_pending');
        $isMyPendingMode = (empty($currentStatus) || $currentStatus === 'pending' || $currentStatus === 'my_pending');
        $modeTitle = $isAllPendingMode ? 'All Departments Pending' : ($isMyPendingMode ? 'My Actionable Pending' : ucfirst($currentStatus));
        $totalCardPendingCount = array_sum(array_column($moduleMetrics, 'pending_count'));
        $totalCardPendingAmount = array_sum(array_column($moduleMetrics, 'pending_amount'));
    ?>
        <div class="mb-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-2">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <h6 class="fw-bold text-dark mb-0 d-flex align-items-center">
                        <i class="fa-solid fa-boxes-stacked text-primary me-2"></i>Expense & Requisition Modules
                    </h6>
                    <!-- View mode indicator pill -->
                    <span class="badge <?= $isAllPendingMode ? 'bg-indigo-subtle text-indigo border border-indigo-subtle' : 'bg-primary-subtle text-primary border border-primary-subtle' ?> rounded-pill px-2 py-1 extra-small fw-semibold" id="cardViewModeBadge">
                        <i class="fa-solid <?= $isAllPendingMode ? 'fa-building-shield' : 'fa-user-check' ?> me-1"></i>
                        Showing: <?= htmlspecialchars($modeTitle) ?> (<?= number_format($totalCardPendingCount) ?> items &bull; ৳<?= number_format($totalCardPendingAmount, 0) ?>)
                    </span>
                </div>
                <div class="d-flex align-items-center gap-2 mt-2 mt-sm-0">
                    <!-- Quick Switch Toggle: My Actionable vs All Departments Pending -->
                    <div class="btn-group btn-group-sm p-0 rounded-pill bg-light border shadow-sm">
                        <a href="<?= $base_url ?>/all-approval-requests?status=my_pending<?= !empty($filters['department']) ? '&department=' . urlencode($filters['department']) : '' ?><?= !empty($filters['source_table']) ? '&source_table=' . urlencode($filters['source_table']) : '' ?>" 
                           class="btn btn-xs rounded-pill px-3 py-1 fw-semibold <?= $isMyPendingMode ? 'btn-primary text-white shadow-sm' : 'text-muted' ?>"
                           style="font-size: 11px;">
                           <i class="fa-solid fa-user-check me-1"></i>My Actionable
                        </a>
                        <a href="<?= $base_url ?>/all-approval-requests?status=all_pending<?= !empty($filters['department']) ? '&department=' . urlencode($filters['department']) : '' ?><?= !empty($filters['source_table']) ? '&source_table=' . urlencode($filters['source_table']) : '' ?>" 
                           class="btn btn-xs rounded-pill px-3 py-1 fw-semibold <?= $isAllPendingMode ? 'btn-primary text-white shadow-sm' : 'text-muted' ?>"
                           style="font-size: 11px; <?= $isAllPendingMode ? 'background-color: #6366f1; border-color: #6366f1;' : '' ?>">
                           <i class="fa-solid fa-building-shield me-1"></i>All Dept Pending
                        </a>
                    </div>
                    <?php if (!empty($filters['source_table'])): ?>
                        <a href="<?= $base_url ?>/all-approval-requests?status=<?= urlencode($currentStatus) ?><?= !empty($filters['department']) ? '&department=' . urlencode($filters['department']) : '' ?>#approvalRequestsTable" 
                           class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1 text-decoration-none extra-small" title="Clear Module Filter">
                            <i class="fa-solid fa-circle-xmark me-1"></i><?= htmlspecialchars($moduleMetrics[$filters['source_table']]['name'] ?? $filters['source_table']) ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 5 Cards Per Row Compact Grid -->
            <div class="d-flex flex-wrap" style="margin-left: -4px; margin-right: -4px;" id="moduleCardsGrid">
                <?php foreach ($moduleMetrics as $modKey => $mod): 
                    $isActive = ($filters['source_table'] ?? '') === $modKey;
                    $hasPending = $mod['pending_count'] > 0;
                    if ($isActive) {
                        $cardClickUrl = $base_url . "/all-approval-requests?status=" . urlencode($currentStatus) . (!empty($filters['department']) ? '&department=' . urlencode($filters['department']) : '') . "#approvalRequestsTable";
                    } else {
                        $cardClickUrl = $base_url . "/all-approval-requests?source_table=" . urlencode($modKey) . "&status=" . urlencode($currentStatus) . (!empty($filters['department']) ? '&department=' . urlencode($filters['department']) : '') . "#approvalRequestsTable";
                    }
                    $portalUrl = !empty($mod['portal_url']) ? $base_url . '/' . $mod['portal_url'] : '#';
                ?>
                    <div class="module-mini-card-col">
                        <div class="module-mini-card <?= $isActive ? 'is-active-filter' : '' ?>" 
                             id="card-<?= htmlspecialchars($modKey) ?>"
                             onclick="window.location.href='<?= $cardClickUrl ?>'"
                             data-mod-key="<?= htmlspecialchars($modKey) ?>"
                             data-pending-count="<?= (int)$mod['pending_count'] ?>"
                             data-pending-amount="<?= (float)$mod['pending_amount'] ?>"
                             title="Click to <?= $isActive ? 'clear filter' : 'filter by ' . htmlspecialchars($mod['name']) ?>">
                            <div class="d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center gap-2 text-truncate me-1">
                                    <div class="mini-card-icon rounded-2 d-flex align-items-center justify-content-center flex-shrink-0"
                                         style="background-color: <?= $mod['accent_hex'] ?>18; color: <?= $mod['accent_hex'] ?>; width: 34px; height: 34px;">
                                        <i class="<?= $mod['icon'] ?> fs-14"></i>
                                    </div>
                                    <div class="text-truncate">
                                        <div class="fw-bold text-dark text-truncate" style="font-size: 11.5px; line-height: 1.25;" title="<?= htmlspecialchars($mod['name']) ?>">
                                            <?= htmlspecialchars($mod['name']) ?>
                                        </div>
                                        <div class="extra-small text-truncate" style="font-size: 10.5px; line-height: 1.2;">
                                            <?php if ($hasPending): ?>
                                                <span class="text-dark fw-semibold">৳ <?= number_format($mod['pending_amount'], 0) ?></span>
                                            <?php else: ?>
                                                <span class="text-success"><i class="fa-solid fa-circle-check extra-small me-1"></i>Clear</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                    <span class="badge <?= $hasPending ? 'bg-warning text-dark' : 'bg-light text-muted border' ?> rounded-pill px-2 py-1 extra-small fw-bold mod-badge-count" 
                                          data-mod-key="<?= htmlspecialchars($modKey) ?>"
                                          style="font-size: 10px;">
                                        <?= $mod['pending_count'] ?><?= $hasPending ? ' Req' : '' ?>
                                    </span>
                                    <?php if ($isActive): ?>
                                        <i class="fa-solid fa-check text-primary extra-small ms-1"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content Table Card -->
    <div class="card border-0 shadow-sm rounded-3" id="approvalRequestsTable">
        <!-- Filter Toolbar -->
        <div class="card-header bg-white py-3 border-bottom">
            <form method="GET" action="<?= $base_url ?>/all-approval-requests" class="row align-items-center g-2">
                <!-- Search Req # / Bill # -->
                <div class="col-lg-3 col-md-6">
                    <label class="form-label text-muted extra-small fw-bold text-uppercase mb-1">
                        <i class="fa-solid fa-magnifying-glass text-primary me-1"></i>Search Req # / Bill #
                    </label>
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" id="topApprovalSearchInput" class="form-control form-control-sm" 
                               placeholder="Search Req #, Bill #, Title, Requester..." 
                               value="<?= htmlspecialchars($filters['search'] ?? '') ?>" autocomplete="off">
                        <button class="btn btn-primary" type="submit" id="btnSubmitTopSearch" title="Search All Records in Database">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                        <?php if (!empty($filters['search'])): ?>
                            <a href="<?= $base_url ?>/all-approval-requests<?= !empty($filters['status']) ? '?status=' . urlencode($filters['status']) : '' ?><?= !empty($filters['department']) ? '&department=' . urlencode($filters['department']) : '' ?><?= !empty($filters['source_table']) ? '&source_table=' . urlencode($filters['source_table']) : '' ?>" 
                               class="btn btn-outline-danger" title="Clear Search Filter">
                                <i class="fa-solid fa-xmark"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Department Filter -->
                <div class="col-lg-2 col-md-6">
                    <label class="form-label text-muted extra-small fw-bold text-uppercase mb-1">Department</label>
                    <select name="department" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Departments</option>
                        <?php if (!empty($departmentsList)): ?>
                            <?php foreach ($departmentsList as $deptName): ?>
                                <option value="<?= htmlspecialchars($deptName) ?>" <?= strtolower($filters['department'] ?? '') === strtolower($deptName) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($deptName) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="col-lg-3 col-md-6">
                    <label class="form-label text-muted extra-small fw-bold text-uppercase mb-1">Approval Status</label>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="" <?= empty($filters['status']) || $filters['status'] === 'pending' || $filters['status'] === 'my_pending' ? 'selected' : '' ?>>My Actionable Pending (Default)</option>
                        <option value="pending_accounts_head" <?= ($filters['status'] ?? '') === 'pending_accounts_head' ? 'selected' : '' ?>>Approved — Pending Accounts Head</option>
                        <option value="pending_payout" <?= ($filters['status'] ?? '') === 'pending_payout' ? 'selected' : '' ?>>Approved — Pending Accounts Payout</option>
                        <option value="paid" <?= ($filters['status'] ?? '') === 'paid' ? 'selected' : '' ?>>Paid & Settled History</option>
                        <option value="approved" <?= ($filters['status'] ?? '') === 'approved' ? 'selected' : '' ?>>All Approved & Paid</option>
                        <option value="rejected" <?= ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected History</option>
                        <option value="all_pending" <?= ($filters['status'] ?? '') === 'all_pending' ? 'selected' : '' ?>>All Departments Pending</option>
                        <option value="all" <?= ($filters['status'] ?? '') === 'all' ? 'selected' : '' ?>>All Company Requests</option>
                    </select>
                </div>

                <!-- Source Table / Module Filter -->
                <div class="col-lg-3 col-md-6">
                    <label class="form-label text-muted extra-small fw-bold text-uppercase mb-1">Module / Source Type</label>
                    <select name="source_table" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Approval Source Types</option>
                        <option value="fund_requisitions" <?= ($filters['source_table'] ?? '') === 'fund_requisitions' ? 'selected' : '' ?>>Fund Requisitions / Petty Cash</option>
                        <option value="project_incentive_claims" <?= ($filters['source_table'] ?? '') === 'project_incentive_claims' ? 'selected' : '' ?>>Sales Incentive Claims</option>
                        <option value="vendor_payment_requests" <?= ($filters['source_table'] ?? '') === 'vendor_payment_requests' ? 'selected' : '' ?>>Vendor Payment Requests (Construction)</option>
                        <option value="contractor_bill_requests" <?= ($filters['source_table'] ?? '') === 'contractor_bill_requests' ? 'selected' : '' ?>>Contractor Bills (Construction)</option>
                        <option value="con_labor_requisitions" <?= ($filters['source_table'] ?? '') === 'con_labor_requisitions' ? 'selected' : '' ?>>Labor Bill by SE (Construction)</option>
                        <option value="operational_fund_requests" <?= ($filters['source_table'] ?? '') === 'operational_fund_requests' ? 'selected' : '' ?>>Operational Fund / Requisitions</option>
                        <option value="cashout_requests" <?= ($filters['source_table'] ?? '') === 'cashout_requests' ? 'selected' : '' ?>>Cashout Requests (Accounts)</option>
                        <option value="advance_salaries" <?= ($filters['source_table'] ?? '') === 'advance_salaries' ? 'selected' : '' ?>>Advance Salary Requests (HR & Accounts)</option>
                        <option value="payslips" <?= ($filters['source_table'] ?? '') === 'payslips' ? 'selected' : '' ?>>Payslips & Salary (HR)</option>
                        <option value="project_other_costs" <?= ($filters['source_table'] ?? '') === 'project_other_costs' ? 'selected' : '' ?>>Project Other Costs (Construction)</option>
                        <option value="project_extra_assets" <?= in_array($filters['source_table'] ?? '', ['project_extra_assets', 'purchase_requests']) ? 'selected' : '' ?>>Office Procurement Requests</option>
                        <option value="client_refund_requests" <?= ($filters['source_table'] ?? '') === 'client_refund_requests' ? 'selected' : '' ?>>Client Refund Requests</option>
                        <option value="con_work_orders" <?= ($filters['source_table'] ?? '') === 'con_work_orders' ? 'selected' : '' ?>>Work Orders (Construction)</option>
                        <option value="lead_bookings" <?= ($filters['source_table'] ?? '') === 'lead_bookings' ? 'selected' : '' ?>>Booking Discounts (Sales)</option>
                        <option value="manpower_requisitions" <?= ($filters['source_table'] ?? '') === 'manpower_requisitions' ? 'selected' : '' ?>>Manpower Requisitions</option>
                        <option value="land_fund_requisitions" <?= in_array($filters['source_table'] ?? '', ['land_fund_requisitions', 'con_land_requisitions']) ? 'selected' : '' ?>>Land Fund Requisitions</option>
                    </select>
                </div>

                <!-- Reset Filter Button -->
                <div class="col-lg-1 col-md-6 text-end">
                    <a href="<?= $base_url ?>/all-approval-requests" class="btn btn-sm btn-outline-secondary w-100" title="Reset Filters">
                        <i class="fa-solid fa-arrow-rotate-left"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Bulk Action & Page Length Bar -->
        <?php
        $userRoleId = (int) ($_SESSION['user']['role_id'] ?? 0);
        $sessionRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
        $isChiefOfficer = (in_array($userRoleId, [33, 34, 35], true) || in_array($sessionRoleName, ['chief officer', 'management viewer', 'seal ceo'], true));
        ?>
        <div class="p-3 bg-light border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2">
                <?php if (!$isChiefOfficer): ?>
                    <span class="badge bg-primary fs-12 px-3 py-2" id="selectedApprovalCountBadge">0 Selected</span>
                    <button type="button" class="btn btn-sm btn-success fw-bold px-3"
                        onclick="processBulkApprovalAction('approve')">
                        <i class="fa-solid fa-circle-check me-1"></i> Bulk Approve Selected
                    </button>
                    <button type="button" class="btn btn-sm btn-danger fw-bold px-3"
                        onclick="processBulkApprovalAction('reject')">
                        <i class="fa-solid fa-circle-xmark me-1"></i> Bulk Reject Selected
                    </button>
                <?php else: ?>
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-12 px-3 py-2">
                        <i class="fa-solid fa-eye me-1"></i> View-Only Access (Role: <?= htmlspecialchars($_SESSION['user']['role_name'] ?? 'View-Only') ?>)
                    </span>
                <?php endif; ?>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label class="form-label mb-0 fs-13 fw-bold text-muted">Rows per page:</label>
                <select id="approvalPageLengthSelect" class="form-select form-select-sm" style="width: 110px;"
                    onchange="changeApprovalTablePageLength(this.value)">
                    <option value="25" <?= ($pagination['perPage'] ?? 25) == 25 ? 'selected' : '' ?>>25 Rows</option>
                    <option value="50" <?= ($pagination['perPage'] ?? 25) == 50 ? 'selected' : '' ?>>50 Rows</option>
                    <option value="70" <?= ($pagination['perPage'] ?? 25) == 70 ? 'selected' : '' ?>>70 Rows</option>
                    <option value="100" <?= ($pagination['perPage'] ?? 25) == 100 ? 'selected' : '' ?>>100 Rows</option>
                    <option value="200" <?= ($pagination['perPage'] ?? 25) == 200 ? 'selected' : '' ?>>200 Rows</option>
                </select>
            </div>
        </div>
        
        <!-- Approval History Table -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="allApprovalRequestsTable">
                    <thead class="bg-light text-muted extra-small text-uppercase">
                        <tr>
                            <th class="ps-3" style="width: 40px;">
                                <input type="checkbox" id="masterApprovalCheckbox" class="form-check-input"
                                    onclick="toggleSelectAllApprovalRequests(this)" <?= $isChiefOfficer ? 'disabled title="Bulk approval disabled for Chief Officer"' : '' ?>>
                            </th>
                            <th>Req # & Date</th>
                            <th>Department & Module</th>
                            <th>Purpose / Title</th>
                            <th>Requested By</th>
                            <th>Requested Amount</th>
                            <th>Approved Amount</th>
                            <th class="text-center">Approval Status & Pending At</th>
                            <th class="text-center">Accounts Payout</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($approvalHistory)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-folder-open fs-3 d-block mb-2"></i>
                                    No approval requests found matching your filter criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($approvalHistory as $req): ?>
                                <?php
                                // Direct module target URL resolution so clicking review/view opens the exact module page!
                                $targetUrl = 'javascript:void(0)';
                                $sourceTable = strtolower($req['source_table'] ?? '');
                                $requestType = strtolower($req['request_type'] ?? '');

                                if ($sourceTable === 'lead_payments') {
                                    if (!empty($req['booking_id'])) {
                                        $targetUrl = $base_url . '/clients-cash-in-history-view/' . $req['booking_id'];
                                    } else {
                                        $targetUrl = $base_url . '/ced-dashboard';
                                    }
                                } elseif ($sourceTable === 'lead_bookings') {
                                    $bookingId = $req['source_id'] ?? $req['booking_id'] ?? null;
                                    if (!empty($bookingId)) {
                                        $targetUrl = $base_url . '/clients-cash-in-history-view/' . $bookingId;
                                    } else {
                                        $targetUrl = $base_url . '/ced-dashboard';
                                    }
                                } elseif ($sourceTable === 'project_incentive_claims') {
                                    $targetUrl = $base_url . '/incentive-management?claim_id=' . (int)$req['source_id'];
                                } elseif ($sourceTable === 'vendor_payment_requests') {
                                    $targetUrl = $base_url . '/con-vendor-payment-show/' . $req['source_id'];
                                } elseif ($sourceTable === 'contractor_bill_requests') {
                                    $targetUrl = $base_url . '/con-contractor-bill/' . $req['source_id'];
                                } elseif ($sourceTable === 'con_land_requisitions' || $sourceTable === 'land_fund_requisitions') {
                                    $targetUrl = $base_url . '/con-land-requisitions?pay_id=' . $req['source_id'];
                                } elseif ($sourceTable === 'con_labor_requisitions') {
                                    $targetUrl = $base_url . '/con-labor-requisition/' . $req['source_id'];
                                } elseif ($sourceTable === 'con_work_orders') {
                                    $targetUrl = $base_url . '/con-work-order/' . $req['source_id'];
                                } elseif ($sourceTable === 'fund_requisitions' || $sourceTable === 'operational_fund_requests') {
                                    $targetUrl = $base_url . '/fund-requisitions/view/' . $req['source_id'];
                                } elseif ($sourceTable === 'cashout_requests') {
                                    $targetUrl = $base_url . '/view-cashout-request?id=' . $req['source_id'];
                                } elseif ($sourceTable === 'payslips' || $sourceTable === 'salary_requests' || $requestType === 'salary') {
                                    $targetUrl = $base_url . '/show-payslip/' . $req['source_id'];
                                } elseif ($sourceTable === 'client_refund_requests') {
                                    $targetUrl = $base_url . '/client-refund/' . $req['source_id'];
                                } elseif ($sourceTable === 'employee_leaves' || $sourceTable === 'leave') {
                                    $targetUrl = $base_url . '/hrm-leave-history';
                                } elseif ($sourceTable === 'manpower_requisitions') {
                                    $targetUrl = $base_url . '/manpower-requisitions';
                                } elseif ($sourceTable === 'advance_salaries') {
                                    $targetUrl = $base_url . '/advance-salary';
                                } elseif ($sourceTable === 'project_other_costs') {
                                    $targetUrl = $base_url . '/project-other-costs/review/' . $req['source_id'];
                                } elseif ($sourceTable === 'project_extra_assets' || $sourceTable === 'purchase_requests') {
                                    $targetUrl = $base_url . '/asset-extra-purchase/' . $req['source_id'];
                                } elseif ($sourceTable === 'expense_adjustments') {
                                    $targetUrl = $base_url . '/expense-adjustments/show?id=' . $req['source_id'];
                                }
                                
                                if ($targetUrl !== 'javascript:void(0)') {
                                    $targetUrl .= (strpos($targetUrl, '?') !== false ? '&' : '?') . 'ref=all_approvals';
                                }

                                $reqApproveRole = strtolower($req['approve_role'] ?? 'management');
                                if (empty($reqApproveRole)) {
                                    $reqApproveRole = 'management';
                                }
                                $isMgmtRequest = ($reqApproveRole === 'management');
                                $userRoleId = (int) ($_SESSION['user']['role_id'] ?? 0);
                                $sessionRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
                                $isChiefOfficer = (in_array($userRoleId, [33, 34, 35], true) || in_array($sessionRoleName, ['chief officer', 'management viewer', 'seal ceo'], true));

                                $isManagementUser = (in_array($userRoleId, [1, 3, 38], true) || in_array($sessionRoleName, ['super admin', 'admin', 'management', 'board of director', 'board of directors', 'director', 'dceo'], true) || strpos($sessionRoleName, 'director') !== false || strpos($sessionRoleName, 'dceo') !== false) && !$isChiefOfficer;
                                $isSiteOperator = in_array($userRoleId, [20, 32], true) || strpos($sessionRoleName, 'operation head') !== false || strpos($sessionRoleName, 'admin & operation') !== false;
                                $isAccountsHeadUser = ($userRoleId === 30) || strpos($sessionRoleName, 'accounts head') !== false;
                                $isAccountsStaffUser = ($userRoleId === 6) || (strpos($sessionRoleName, 'account') !== false && !$isAccountsHeadUser);
                                $isAccountsUser = in_array($userRoleId, [6, 30], true) || strpos($sessionRoleName, 'account') !== false;
                                $isQcUser = in_array($userRoleId, [13, 24, 25], true) || strpos($sessionRoleName, 'qc') !== false || strpos($sessionRoleName, 'audit') !== false;
                                $isHrUser = ($userRoleId === 4) || strpos($sessionRoleName, 'hr') !== false;
                                $isPurchaseUser = in_array($userRoleId, [17, 23, 31], true) || strpos($sessionRoleName, 'purchase') !== false;
                                $isDeptHeadUser = in_array($userRoleId, [8, 9, 10, 11, 12, 14, 15, 16, 18, 19], true) || strpos($sessionRoleName, 'head') !== false || strpos($sessionRoleName, 'manager') !== false;

                                $userSecondaryRoleId = (int) ($_SESSION['user']['secondary_role_id'] ?? 0);
                                if ($userSecondaryRoleId === 0 && !empty($_SESSION['user']['id'])) {
                                    static $secondaryRoleCache = [];
                                    $uid = (int)$_SESSION['user']['id'];
                                    if (!isset($secondaryRoleCache[$uid])) {
                                        $conn = $GLOBALS['db'] ?? ($this->db ?? null);
                                        $secId = 0;
                                        if ($conn) {
                                            $res = $conn->query("SELECT secondary_role_id FROM users WHERE id = {$uid} LIMIT 1");
                                            if ($res && $r = $res->fetch_assoc()) {
                                                $secId = (int)($r['secondary_role_id'] ?? 0);
                                            }
                                        }
                                        $secondaryRoleCache[$uid] = $secId;
                                    }
                                    $userSecondaryRoleId = $secondaryRoleCache[$uid];
                                }
                                $isSalesHeadUser = ($userRoleId === 9 || $userSecondaryRoleId === 9 || strpos($sessionRoleName, 'sales head') !== false || strpos($sessionRoleName, 'sales team head') !== false || strpos($sessionRoleName, 'head of sales') !== false || $isManagementUser || $isSiteOperator || in_array($userRoleId, [20, 32], true));

                                $statusLower = strtolower($req['status'] ?? '');
                                $isPendingStatus = in_array($statusLower, ['pending', 'partially_approved', '']);
                                $reqAmount = (float)($req['request_amount'] ?? 0);
                                $payoutStatus = $req['payout_status'] ?? '';
                                $ahStatus = strtolower($req['accounts_head_status'] ?? 'pending');
                                $isFrPartialMgmt = ($sourceTable === 'fund_requisitions' && $statusLower === 'partially_approved');
                                $isPendingAccountsHead = ($sourceTable !== 'project_incentive_claims') ? (($ahStatus !== 'approved') && (
                                    $reqApproveRole === 'accounts_head'
                                    || $statusLower === 'approved'
                                    || $req['status'] === 'approved'
                                    || $isFrPartialMgmt
                                    || ($sourceTable === 'payslips' && $isAccountsHeadUser && $isPendingStatus)
                                )) : ($reqApproveRole === 'accounts_head' && $isPendingStatus);
                                $isAccountsHeadApproved = ($ahStatus === 'approved');

                                // Determine whether this request is actionable by the logged-in user right now
                                $isActionableByMe = false;
                                $canUserApprove = false;
                                if ($isChiefOfficer) {
                                    // Chief Officer has view-only access across all requests; strictly cannot approve or reject
                                    $isActionableByMe = false;
                                    $canUserApprove = false;
                                } elseif ($sourceTable === 'project_incentive_claims') {
                                    if ($reqApproveRole === 'sales_head' && $isPendingStatus) {
                                        if ($isSalesHeadUser) {
                                            $isActionableByMe = true;
                                            $canUserApprove = true;
                                        }
                                    } elseif ($reqApproveRole === 'accounts_head' && $isPendingStatus) {
                                        if ($isAccountsHeadUser || $userRoleId === 1 || $isManagementUser) {
                                            $isActionableByMe = true;
                                            $canUserApprove = true;
                                        }
                                    } elseif ($reqApproveRole === 'management' && $isPendingStatus) {
                                        if ($isManagementUser) {
                                            $isActionableByMe = true;
                                            $canUserApprove = true;
                                        }
                                    } elseif ($statusLower === 'approved') {
                                        if ($isAccountsUser) {
                                            $isActionableByMe = true;
                                            $canUserApprove = true;
                                        }
                                    }
                                } elseif ($isPendingAccountsHead) {
                                    // Pending Accounts Head approval: Actionable by Accounts Head (Role 30) and Super Admin (Role 1)
                                    if ($isAccountsHeadUser || (int)($_SESSION['user']['role_id'] ?? 0) === 1) {
                                        $isActionableByMe = true;
                                        $canUserApprove = true;
                                    } else {
                                        $isActionableByMe = false;
                                        $canUserApprove = false;
                                    }
                                } elseif ($isAccountsHeadApproved && in_array($payoutStatus, ['Pending Accounts Payout', 'Partially Paid'])) {
                                    // Approved by Accounts Head & awaiting payout: Actionable by BOTH Accounts (Role 6) and Accounts Head (Role 30)
                                    if ($isAccountsUser) {
                                        $isActionableByMe = true;
                                        $canUserApprove = true;
                                    }
                                } elseif ($isAccountsUser) {
                                    if (in_array($payoutStatus, ['Pending Accounts Payout', 'Partially Paid']) || ($reqApproveRole === 'accounts' && in_array($statusLower, ['pending', 'approved', 'partially_approved', 'partially_paid']) && $isAccountsHeadApproved)) {
                                        $isActionableByMe = true;
                                        $canUserApprove = true;
                                    }
                                } elseif ($sourceTable === 'client_refund_requests') {
                                    if ($isPendingStatus) {
                                        $rfSalesSt = strtolower($req['sales_head_status'] ?? 'pending');
                                        $rfQcSt    = strtolower($req['qc_status'] ?? 'pending');
                                        $rfOpSt    = strtolower($req['op_head_status'] ?? 'pending');
                                        if ($rfSalesSt !== 'approved') {
                                            $rfStage = 'sales_manager';
                                        } elseif ($rfQcSt !== 'approved') {
                                            $rfStage = 'qc';
                                        } elseif ($rfOpSt !== 'approved') {
                                            $rfStage = 'op_head';
                                        } else {
                                            $rfStage = 'management';
                                        }
                                        if ($rfStage === 'sales_manager' && ($userRoleId === 38 || $isSalesHeadUser || $userRoleId === 3)) {
                                            $isActionableByMe = true;
                                            $canUserApprove = true;
                                        } elseif ($rfStage === 'qc' && ($isQcUser || $userRoleId === 3)) {
                                            $isActionableByMe = true;
                                            $canUserApprove = true;
                                        } elseif ($rfStage === 'op_head' && ($isSiteOperator || $userRoleId === 3)) {
                                            $isActionableByMe = true;
                                            $canUserApprove = true;
                                        } elseif ($rfStage === 'management' && $userRoleId === 3) {
                                            $isActionableByMe = true;
                                            $canUserApprove = true;
                                        }
                                    }
                                } elseif ($isSiteOperator) {
                                    $reqOpStatus = strtolower($req['op_head_status'] ?? 'pending');
                                    if ($isPendingStatus && $sourceTable !== 'payslips' && in_array($reqApproveRole, ['management', 'operations', '']) && $reqOpStatus !== 'approved') {
                                        $isActionableByMe = true;
                                        $canUserApprove = true;
                                    }
                                } elseif ($isManagementUser) {
                                    if ($isPendingStatus) {
                                        // Management (Role 1, 3) can always review and approve any pending bill at any stage (with override confirmation on the module page)
                                        $canUserApprove = true;
                                        $reqOpStatus = strtolower($req['op_head_status'] ?? 'pending');
                                        $isOver10k = ((float)($req['request_amount'] ?? 0) > 10000.01);
                                        if ((in_array($reqApproveRole, ['management', 'operations', '']) || $sourceTable === 'payslips')) {
                                            if ($isOver10k && $sourceTable !== 'payslips' && ($reqOpStatus !== 'approved' || !$isAccountsHeadApproved)) {
                                                $isActionableByMe = false;
                                            } elseif (!$isOver10k && in_array($sourceTable, ['vendor_payment_requests', 'contractor_bill_requests'], true)) {
                                                $isActionableByMe = false;
                                            } else {
                                                $isActionableByMe = true;
                                            }
                                        }
                                    }
                                } elseif ($isQcUser) {
                                    if ($isPendingStatus && in_array($reqApproveRole, ['qc', 'qc_head'])) {
                                        $isActionableByMe = true;
                                        $canUserApprove = true;
                                    }
                                } elseif ($isPurchaseUser) {
                                    if ($isPendingStatus && in_array($reqApproveRole, ['purchase_head', 'purchase', 'purchase team leader', 'purchase_lead'])) {
                                        if ($reqApproveRole === 'purchase_head' && $userRoleId !== 17) {
                                            $isActionableByMe = false;
                                            $canUserApprove = false;
                                        } else {
                                            $isActionableByMe = true;
                                            $canUserApprove = true;
                                        }
                                    }
                                } elseif ($isHrUser) {
                                    if ($isPendingStatus && in_array($reqApproveRole, ['hr', 'hr_head'])) {
                                        $isActionableByMe = true;
                                        $canUserApprove = true;
                                    }
                                } elseif ($isDeptHeadUser) {
                                    if ($isPendingStatus && ($reqApproveRole === 'dept_head' || $reqApproveRole === 'dept_head_' . ($_SESSION['user']['id'] ?? 0) || ($reqApproveRole === 'pm' && in_array($userRoleId, [15, 22], true)))) {
                                        $isActionableByMe = true;
                                        $canUserApprove = true;
                                    }
                                } else {
                                    if ($isPendingStatus && !empty($reqApproveRole) && strpos($sessionRoleName, $reqApproveRole) !== false) {
                                        $isActionableByMe = true;
                                        $canUserApprove = true;
                                    }
                                }

                                $rowActionableClass = $isActionableByMe ? ' actionable-approval-row border-start border-primary border-3' : '';
                                ?>
                                <tr data-source-table="<?= htmlspecialchars($req['source_table']) ?>" <?= !empty($targetUrl) ? 'data-href="' . htmlspecialchars($targetUrl) . '" class="clickable-approval-row' . $rowActionableClass . '" style="cursor: pointer;"' : '' ?>>
                                    <td class="ps-3">
                                        <input type="checkbox" class="approval-req-checkbox form-check-input"
                                            value="<?= $req['id'] ?>" <?= ((!in_array($req['status'], ['pending', 'partially_approved', 'partially_paid']) && !($isPendingAccountsHead && ($isAccountsHeadUser || $isManagementUser))) || !$canUserApprove || $isChiefOfficer) ? 'disabled' : '' ?> onchange="updateBulkApprovalCount()">
                                    </td>
                                    <!-- Req # & Date -->
                                    <td class="text-nowrap">
                                        <?php
                                        // Standardized Requisition / Bill ID for all modules
                                        $displayReqCode = '';
                                        if ($sourceTable === 'vendor_payment_requests' || $sourceTable === 'vendor_payments') {
                                            $displayReqCode = 'VPR-' . (int)$req['source_id'];
                                        } elseif ($sourceTable === 'contractor_bill_requests' || $sourceTable === 'contractor_bills') {
                                            $displayReqCode = 'CB-' . (int)$req['source_id'];
                                        } elseif ($sourceTable === 'fund_requisitions' || $sourceTable === 'operational_fund_requests') {
                                            $displayReqCode = 'REQ-' . (int)$req['source_id'];
                                        } elseif ($sourceTable === 'con_labor_requisitions' || $sourceTable === 'labor_requisitions') {
                                            $displayReqCode = !empty($req['requisition_no']) ? htmlspecialchars($req['requisition_no']) : ('LR-' . (int)$req['source_id']);
                                        } elseif ($sourceTable === 'project_other_costs') {
                                            $displayReqCode = 'POC-' . (int)$req['source_id'];
                                        } elseif ($sourceTable === 'project_extra_assets' || $sourceTable === 'purchase_requests') {
                                            $displayReqCode = 'PR-' . (int)$req['source_id'];
                                        } elseif ($sourceTable === 'land_fund_requisitions' || $sourceTable === 'con_land_requisitions') {
                                            $displayReqCode = 'LFR-' . (int)$req['source_id'];
                                        } elseif ($sourceTable === 'cashout_requests') {
                                            $displayReqCode = 'CSH-' . (int)$req['source_id'];
                                        } elseif ($sourceTable === 'expense_adjustments') {
                                            $displayReqCode = 'ADJ-' . (int)$req['source_id'];
                                        } elseif ($sourceTable === 'client_refund_requests') {
                                            $displayReqCode = 'REF-' . (int)$req['source_id'];
                                        } elseif ($sourceTable === 'payslips' || $sourceTable === 'salary_requests') {
                                            $displayReqCode = 'PAY-' . (int)$req['source_id'];
                                        } elseif ($sourceTable === 'project_incentive_claims') {
                                            $displayReqCode = 'INC-' . date('Y', strtotime($req['requested_at'] ?? 'now')) . '-' . str_pad((int)$req['source_id'], 3, '0', STR_PAD_LEFT);
                                        } elseif ($sourceTable === 'advance_salaries') {
                                            $displayReqCode = 'ADV-' . (int)$req['source_id'];
                                        } elseif ($sourceTable === 'lead_bookings') {
                                            $displayReqCode = 'BK-' . (int)$req['source_id'];
                                        } elseif ($sourceTable === 'con_work_orders') {
                                            $displayReqCode = 'WO-' . (int)$req['source_id'];
                                        } else {
                                            $displayReqCode = !empty($req['source_id']) ? ('#' . (int)$req['source_id']) : ('#REQ-' . str_pad($req['id'], 5, '0', STR_PAD_LEFT));
                                        }
                                        ?>
                                        <?php if (!empty($targetUrl)): ?>
                                            <a href="<?= $targetUrl ?>" class="text-decoration-none d-block">
                                                <span class="fw-bold text-dark"><?= $displayReqCode ?></span>
                                                <?php if ($sourceTable === 'vendor_payment_requests' && !empty($req['po_number'])): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded px-1 ms-1 extra-small">PO-<?= htmlspecialchars($req['po_number']) ?></span>
                                                <?php endif; ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="fw-bold text-dark d-block"><?= $displayReqCode ?></span>
                                            <?php if ($sourceTable === 'vendor_payment_requests' && !empty($req['po_number'])): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded px-1 ms-1 extra-small">PO-<?= htmlspecialchars($req['po_number']) ?></span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <span class="text-muted extra-small">
                                            <?= date('d M, Y', strtotime($req['requested_at'])) ?>
                                        </span>
                                    </td>

                                    <!-- Department & Module -->
                                    <td class="text-nowrap">
                                        <div class="fw-semibold text-dark fs-12 mb-1">
                                            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $req['department'] ?? 'General'))) ?>
                                        </div>
                                        <span class="badge bg-light text-secondary border fw-normal extra-small px-2 py-0.5 rounded-pill">
                                            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $req['request_type'] ?? $req['source_table']))) ?>
                                        </span>
                                    </td>

                                    <!-- Title -->
                                    <td style="max-width: 250px;">
                                        <div class="fw-semibold text-dark text-truncate"
                                            title="<?= htmlspecialchars($req['title']) ?>">
                                            <?php if (!empty($targetUrl)): ?>
                                                <a href="<?= $targetUrl ?>" class="text-dark text-decoration-none hover-primary">
                                                    <?= htmlspecialchars($req['title']) ?>
                                                </a>
                                            <?php else: ?>
                                                <?= htmlspecialchars($req['title']) ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($sourceTable === 'expense_adjustments'): ?>
                                            <div class="mt-1 d-flex flex-wrap gap-1">
                                                <?php if (($req['request_type'] ?? '') === 'Extra Expense' || strpos(strtolower($req['title'] ?? ''), 'extra') !== false): ?>
                                                    <span class="badge bg-warning text-dark border border-warning-subtle extra-small fw-bold">
                                                        <i class="fa-solid fa-plus-circle me-1"></i>Extra Amount Request
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-info-subtle text-info border border-info-subtle extra-small fw-bold">
                                                        <i class="fa-solid fa-arrow-rotate-left me-1"></i>Return Unused Money
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($req['project_name'])): ?>
                                            <div class="mt-1">
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle extra-small">
                                                    <i class="fa-solid fa-building me-1"></i><?= htmlspecialchars($req['project_name']) ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($req['attachment'])): ?>
                                            <div class="mt-1">
                                                <a href="<?= $base_url ?>/<?= htmlspecialchars(ltrim($req['attachment'], '/')) ?>" target="_blank" class="badge bg-primary-subtle text-primary border border-primary-subtle extra-small text-decoration-none" onclick="event.stopPropagation();" title="View Attachment / Supporting Document">
                                                    <i class="fa-solid fa-paperclip me-1"></i>Attachment Document
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Requested By -->
                                    <td class="text-nowrap">
                                        <div class="fw-semibold text-dark fs-12">
                                            <?= htmlspecialchars($req['requester_name'] ?? 'N/A') ?>
                                        </div>
                                        <?php if (!empty($req['requester_code'])): ?>
                                            <span class="text-muted extra-small">
                                                ID: <?= htmlspecialchars($req['requester_code']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Requested Amount -->
                                    <td>
                                        <?php
                                        $displayAmount = $req['request_amount'];
                                        if ($sourceTable === 'advance_salaries' && $displayAmount <= 0) {
                                            $db = $GLOBALS['db'] ?? null;
                                            if ($db) {
                                                $res = $db->query("SELECT amount FROM advance_salaries WHERE id = " . (int) $req['source_id']);
                                                if ($res && $row = $res->fetch_assoc()) {
                                                    $displayAmount = $row['amount'];
                                                    // Self-heal the record in the DB
                                                    $db->query("UPDATE approval_requests SET request_amount = {$displayAmount} WHERE id = " . (int) $req['id']);
                                                }
                                            }
                                        }
                                        ?>
                                        <?php if ($displayAmount > 0): ?>
                                            <span class="fw-bold text-dark">৳
                                                <?= number_format($displayAmount, 2) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted extra-small">N/A</span>
                                        <?php endif; ?>
                                        <?php if ($sourceTable === 'vendor_payment_requests' && !empty($req['vpr_payment_type'])): ?>
                                            <?php
                                            $vprType = strtolower($req['vpr_payment_type']);
                                            $typeLabel = match ($vprType) {
                                                'advance' => 'Advance',
                                                'partial' => 'Partially',
                                                'final', 'full', 'fully' => 'Fully',
                                                default => ucfirst($vprType)
                                            };
                                            $typeClass = match ($vprType) {
                                                'advance' => 'bg-primary-subtle text-primary border-primary-subtle',
                                                'partial' => 'bg-warning-subtle text-warning border-warning-subtle',
                                                'final', 'full', 'fully' => 'bg-success-subtle text-success border-success-subtle',
                                                default => 'bg-secondary-subtle text-secondary border-secondary-subtle'
                                            };
                                            ?>
                                            <div class="mt-1"><span class="badge <?= $typeClass ?> border extra-small">
                                                    <?= $typeLabel ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Approved Amount -->
                                    <td>
                                        <?php 
                                        $appAmt = (float)($req['approved_amount'] ?? 0);
                                        $qcAmt = (float)($req['qc_approved_amount'] ?? 0);
                                        $isPendingMgmt = in_array(strtolower($req['approve_role'] ?? ''), ['management', 'operation_head']);
                                        $isQcOnlyApproved = ($qcAmt > 0 && ($appAmt == $qcAmt || $appAmt <= 0) && $isPendingMgmt);
                                        $isFullyApproved = ($appAmt > 0 && !$isQcOnlyApproved);
                                        $reqAmt = (float)($req['request_amount'] ?? 0);
                                        ?>
                                        <?php if ($isQcOnlyApproved): ?>
                                            <span class="fw-bold text-primary">৳ <?= number_format($qcAmt, 2) ?></span>
                                            <div class="mt-1">
                                                <span class="badge bg-info-subtle text-info border border-info-subtle extra-small" style="font-size: 9px; padding: 2px 6px;">
                                                    <i class="bi bi-shield-check me-1"></i>QC Approved
                                                </span>
                                            </div>
                                            <?php if ($reqAmt > $qcAmt): ?>
                                                <div class="extra-small text-danger fw-semibold mt-0.5" style="font-size: 10px;" title="Original Requested: ৳ <?= number_format($reqAmt, 2) ?>">
                                                    <i class="fa-solid fa-arrow-down me-1"></i>Reduced by ৳ <?= number_format($reqAmt - $qcAmt, 2) ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($req['qc_approver_name'])): ?>
                                                <div class="extra-small text-muted mt-1 lh-sm" style="font-size: 10px;">
                                                    <div class="text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($req['qc_approver_name']) ?>">
                                                        <i class="fa-solid fa-user-check text-primary me-1"></i><?= htmlspecialchars($req['qc_approver_name']) ?>
                                                    </div>
                                                    <?php if (!empty($req['qc_approved_at'])): ?>
                                                        <div class="text-muted" style="font-size: 9.5px;">
                                                            <i class="fa-regular fa-clock me-1"></i><?= date('d M Y, h:i A', strtotime($req['qc_approved_at'])) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($req['qc_note'])): ?>
                                                        <div class="fst-italic text-secondary text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($req['qc_note']) ?>">
                                                            <i class="fa-solid fa-comment-dots text-muted me-1"></i>"<?= htmlspecialchars($req['qc_note']) ?>"
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php elseif ($isFullyApproved): ?>
                                            <span class="fw-bold text-success">৳ <?= number_format($appAmt, 2) ?></span>
                                            <?php if ($reqAmt > $appAmt): ?>
                                                <div class="extra-small text-danger fw-semibold mt-0.5" style="font-size: 10px;" title="Original Requested: ৳ <?= number_format($reqAmt, 2) ?>">
                                                    <i class="fa-solid fa-arrow-down me-1"></i>Reduced by ৳ <?= number_format($reqAmt - $appAmt, 2) ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php
                                            $apprDisplayName = (!empty($req['approved_by']) && (int)$req['approved_by'] !== (int)($req['accounts_head_by'] ?? 0) && stripos($req['approver_name'] ?? '', 'accounts') === false)
                                                ? ($req['approver_name'] ?? 'Management')
                                                : (!empty($req['op_head_name']) ? $req['op_head_name'] : ($req['approver_name'] ?? 'Management'));
                                            $apprDisplayAt = (!empty($req['approved_by']) && (int)$req['approved_by'] !== (int)($req['accounts_head_by'] ?? 0) && stripos($req['approver_name'] ?? '', 'accounts') === false)
                                                ? ($req['approved_at'] ?? null)
                                                : (!empty($req['op_head_at']) ? $req['op_head_at'] : ($req['approved_at'] ?? null));
                                            ?>
                                            <?php if (!empty($apprDisplayName)): ?>
                                                <div class="extra-small text-muted mt-1 lh-sm" style="font-size: 10px;">
                                                    <div class="text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($apprDisplayName) ?>">
                                                        <i class="fa-solid fa-user-check text-success me-1"></i><?= htmlspecialchars($apprDisplayName) ?>
                                                    </div>
                                                    <?php if (!empty($apprDisplayAt)): ?>
                                                        <div class="text-muted" style="font-size: 9.5px;">
                                                            <i class="fa-regular fa-clock me-1"></i><?= date('d M Y, h:i A', strtotime($apprDisplayAt)) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted extra-small">--</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Approval Status & Pending At -->
                                    <td class="text-center">
                                        <?php if ($sourceTable === 'project_incentive_claims' && $statusLower === 'rejected'): ?>
                                            <div class="d-flex flex-column gap-1 text-center" style="max-width: 190px; margin: 0 auto;">
                                                <span class="badge bg-danger text-white rounded-pill px-2.5 py-1 extra-small fw-semibold">
                                                    <i class="fa-solid fa-circle-xmark me-1"></i>Rejected
                                                </span>
                                                <span class="extra-small text-muted fw-semibold">
                                                    By: <?= htmlspecialchars($req['approver_name'] ?? 'Management') ?>
                                                </span>
                                                <?php if (!empty($req['approved_at'])): ?>
                                                    <span class="extra-small text-muted" style="font-size: 8.5px;"><?= date('d M, Y h:i A', strtotime($req['approved_at'])) ?></span>
                                                <?php endif; ?>
                                                <?php 
                                                $rejReason = !empty($req['rejection_reason']) ? $req['rejection_reason'] : (!empty($req['approval_note']) ? $req['approval_note'] : (!empty($req['sales_head_note']) ? $req['sales_head_note'] : (!empty($req['accounts_head_note']) ? $req['accounts_head_note'] : '')));
                                                ?>
                                                <?php if (!empty($rejReason)): ?>
                                                    <span class="badge bg-light text-danger border border-danger-subtle text-wrap extra-small mt-1 px-1.5 py-0.5 text-start" style="font-size: 9px;" title="Rejection Reason">
                                                        <i class="fa-solid fa-comment-dots me-1"></i><?= htmlspecialchars($rejReason) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif ($sourceTable === 'project_incentive_claims'): ?>
                                            <?php
                                            $shDone = (strtolower($req['sales_head_status'] ?? '') === 'approved' || strtolower($req['op_head_status'] ?? '') === 'approved');
                                            $shName = !empty($req['sales_head_name']) ? $req['sales_head_name'] : (!empty($req['op_head_name']) ? $req['op_head_name'] : 'Sales Head / Op Head');
                                            $shDate = !empty($req['sales_head_at']) ? $req['sales_head_at'] : (!empty($req['op_head_at']) ? $req['op_head_at'] : null);
                                            $shBadgeText = (!empty($req['op_head_status']) && strtolower($req['op_head_status']) === 'approved' && empty($req['sales_head_by'])) ? 'Op Head' : 'Sales Head / Op';

                                            $ahDone = (strtolower($req['accounts_head_status'] ?? '') === 'approved');
                                            $ahName = !empty($req['accounts_head_name']) ? $req['accounts_head_name'] : 'Accounts Head';
                                            $ahDate = !empty($req['accounts_head_at']) ? $req['accounts_head_at'] : null;

                                            $mgmtDone = in_array(strtolower($req['status'] ?? ''), ['approved', 'paid']);
                                            $mgmtName = !empty($req['approver_name']) ? $req['approver_name'] : 'Management';
                                            $mgmtDate = !empty($req['approved_at']) ? $req['approved_at'] : null;
                                            ?>
                                            <div class="d-flex flex-column gap-1 text-start" style="min-width: 180px; max-width: 220px; margin: 0 auto;">
                                                <!-- Step 1: Sales Head -->
                                                <?php if ($shDone): ?>
                                                    <div class="px-1.5 py-1 rounded bg-success-subtle border border-success-subtle d-flex align-items-center justify-content-between gap-1"
                                                         title="Sales Head Approved • <?= htmlspecialchars($shName) ?><?= !empty($shDate) ? ' • ' . date('d M, Y h:i A', strtotime($shDate)) : '' ?>" style="font-size: 10px; line-height: 1.2;">
                                                        <div class="d-flex align-items-center gap-1 text-truncate me-1">
                                                            <i class="fa-solid fa-check text-success flex-shrink-0" style="font-size: 9.5px;"></i>
                                                            <span class="fw-bold text-dark text-truncate" style="max-width: 105px;" title="<?= htmlspecialchars($shName) ?>"><?= htmlspecialchars($shName) ?></span>
                                                            <span class="badge bg-success text-white p-0 px-1 flex-shrink-0" style="font-size: 8px;"><?= htmlspecialchars($shBadgeText ?? 'SH / Op') ?></span>
                                                        </div>
                                                        <?php if (!empty($shDate)): ?>
                                                            <span class="text-muted flex-shrink-0" style="font-size: 8.5px; white-space: nowrap;"><?= date('d M, h:i A', strtotime($shDate)) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="px-1.5 py-1 rounded bg-warning-subtle border border-warning-subtle d-flex align-items-center justify-content-between gap-1" style="font-size: 10px; line-height: 1.2;">
                                                        <div class="d-flex align-items-center gap-1 text-truncate me-1">
                                                            <i class="fa-solid fa-hourglass-half text-warning flex-shrink-0" style="font-size: 9px;"></i>
                                                            <span class="text-dark fw-semibold text-truncate">Pending Sales Head or Op Head</span>
                                                        </div>
                                                        <span class="badge bg-warning text-dark p-0 px-1 flex-shrink-0" style="font-size: 8px;">Step 1</span>
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Step 2: Accounts Head -->
                                                <?php if ($ahDone): ?>
                                                    <div class="px-1.5 py-1 rounded bg-primary-subtle border border-primary-subtle d-flex align-items-center justify-content-between gap-1"
                                                         title="Accounts Head Approved • <?= htmlspecialchars($ahName) ?><?= !empty($ahDate) ? ' • ' . date('d M, Y h:i A', strtotime($ahDate)) : '' ?>" style="font-size: 10px; line-height: 1.2;">
                                                        <div class="d-flex align-items-center gap-1 text-truncate me-1">
                                                            <i class="fa-solid fa-check-double text-primary flex-shrink-0" style="font-size: 9.5px;"></i>
                                                            <span class="fw-bold text-dark text-truncate" style="max-width: 105px;" title="<?= htmlspecialchars($ahName) ?>"><?= htmlspecialchars($ahName) ?></span>
                                                            <span class="badge bg-primary text-white p-0 px-1 flex-shrink-0" style="font-size: 8px;">AH</span>
                                                        </div>
                                                        <?php if (!empty($ahDate)): ?>
                                                            <span class="text-muted flex-shrink-0" style="font-size: 8.5px; white-space: nowrap;"><?= date('d M, h:i A', strtotime($ahDate)) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php elseif ($shDone): ?>
                                                    <div class="px-1.5 py-1 rounded bg-warning-subtle border border-warning-subtle d-flex align-items-center justify-content-between gap-1" style="font-size: 10px; line-height: 1.2;">
                                                        <div class="d-flex align-items-center gap-1 text-truncate me-1">
                                                            <i class="fa-solid fa-hourglass-half text-warning flex-shrink-0" style="font-size: 9px;"></i>
                                                            <span class="text-dark fw-semibold text-truncate">Pending Accts Head</span>
                                                        </div>
                                                        <span class="badge bg-warning text-dark p-0 px-1 flex-shrink-0" style="font-size: 8px;">Step 2</span>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="px-1.5 py-1 rounded bg-light border text-muted d-flex align-items-center justify-content-between gap-1" style="font-size: 9.5px; line-height: 1.2;">
                                                        <span class="text-muted"><i class="fa-solid fa-lock text-muted me-1"></i>Accts Head (Locked)</span>
                                                        <span class="badge bg-secondary-subtle text-muted p-0 px-1" style="font-size: 7.5px;">Step 2</span>
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Step 3: Management -->
                                                <?php if ($mgmtDone): ?>
                                                    <div class="px-1.5 py-1 rounded bg-success-subtle border border-success-subtle d-flex align-items-center justify-content-between gap-1"
                                                         title="Management Approved • <?= htmlspecialchars($mgmtName) ?><?= !empty($mgmtDate) ? ' • ' . date('d M, Y h:i A', strtotime($mgmtDate)) : '' ?>" style="font-size: 10px; line-height: 1.2;">
                                                        <div class="d-flex align-items-center gap-1 text-truncate me-1">
                                                            <i class="fa-solid fa-circle-check text-success flex-shrink-0" style="font-size: 9.5px;"></i>
                                                            <span class="fw-bold text-dark text-truncate" style="max-width: 105px;" title="<?= htmlspecialchars($mgmtName) ?>"><?= htmlspecialchars($mgmtName) ?></span>
                                                            <span class="badge bg-success text-white p-0 px-1 flex-shrink-0" style="font-size: 8px;">Mgmt</span>
                                                        </div>
                                                        <?php if (!empty($mgmtDate)): ?>
                                                            <span class="text-muted flex-shrink-0" style="font-size: 8.5px; white-space: nowrap;"><?= date('d M, h:i A', strtotime($mgmtDate)) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php elseif ($ahDone): ?>
                                                    <div class="px-1.5 py-1 rounded bg-info-subtle border border-info-subtle d-flex align-items-center justify-content-between gap-1" style="font-size: 10px; line-height: 1.2;">
                                                        <div class="d-flex align-items-center gap-1 text-truncate me-1">
                                                            <i class="fa-solid fa-hourglass-half text-info flex-shrink-0" style="font-size: 9px;"></i>
                                                            <span class="text-dark fw-semibold text-truncate">Pending Mgmt</span>
                                                        </div>
                                                        <span class="badge bg-info text-white p-0 px-1 flex-shrink-0" style="font-size: 8px;">Step 3</span>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="px-1.5 py-1 rounded bg-light border text-muted d-flex align-items-center justify-content-between gap-1" style="font-size: 9.5px; line-height: 1.2;">
                                                        <span class="text-muted"><i class="fa-solid fa-lock text-muted me-1"></i>Management (Locked)</span>
                                                        <span class="badge bg-secondary-subtle text-muted p-0 px-1" style="font-size: 7.5px;">Step 3</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif ($req['status'] === 'pending'): ?>
                                            <?php
                                            $stageLabel = str_replace(['\u0026', '&amp;'], ['&', '&'], $req['pending_stage_label'] ?? 'Pending Review');
                                            $pendingFor = str_replace(['\u0026', '&amp;'], ['&', '&'], $req['pending_for_text'] ?? 'Management');
                                            $pendingFor = trim(preg_replace('/\s*\(Role[^\)]*\)/i', '', $pendingFor));

                                            $badgeStyle = 'background: #fef3c7; color: #92400e;';
                                            if (stripos($stageLabel, 'QC') !== false || stripos($stageLabel, 'Audit') !== false) {
                                                $badgeStyle = 'background: #e0f2fe; color: #075985;';
                                            } elseif (stripos($stageLabel, 'Dept') !== false || stripos($stageLabel, 'Purchase') !== false) {
                                                $badgeStyle = 'background: #f1f5f9; color: #334155;';
                                            } elseif (stripos($stageLabel, 'HR') !== false || stripos($stageLabel, 'PM') !== false) {
                                                $badgeStyle = 'background: #ede9fe; color: #5b21b6;';
                                            } elseif (stripos($stageLabel, 'Accounts') !== false) {
                                                $badgeStyle = 'background: #dcfce7; color: #166534;';
                                            }
                                            ?>
                                            <div class="d-flex flex-column gap-1 text-center" style="max-width: 210px; margin: 0 auto;">
                                                <div>
                                                    <span class="badge rounded-pill px-2.5 py-1 extra-small fw-semibold" style="<?= $badgeStyle ?>">
                                                        <i class="fa-solid fa-clock me-1"></i><?= htmlspecialchars($stageLabel) ?>
                                                    </span>
                                                </div>
                                                <?php if (!empty($pendingFor) && strcasecmp(str_replace('Pending ', '', $stageLabel), $pendingFor) !== 0): ?>
                                                <div class="text-muted" style="font-size: 11px;">
                                                    <?= htmlspecialchars($pendingFor) ?>
                                                </div>
                                                <?php endif; ?>
                                                <?php if (strtolower($req['op_head_status'] ?? '') === 'approved' || !empty($req['op_head_by'])): ?>
                                                    <div class="p-1 px-2 rounded-2 bg-secondary-subtle border border-secondary-subtle text-start d-flex align-items-center justify-content-between"
                                                         title="<?= htmlspecialchars(($req['op_head_name'] ?? 'Admin & Operation Head') . (!empty($req['op_head_at']) ? ' • ' . date('d M, Y h:i A', strtotime($req['op_head_at'])) : '')) ?>">
                                                        <div class="d-flex align-items-center gap-1 text-truncate me-1">
                                                            <i class="fa-solid fa-check text-secondary flex-shrink-0" style="font-size: 10px;"></i>
                                                            <span class="fw-bold text-dark text-truncate" style="font-size: 10.5px; max-width: 110px;">
                                                                <?= htmlspecialchars(!empty($req['op_head_name']) ? $req['op_head_name'] : 'Op Head') ?>
                                                            </span>
                                                            <span class="badge bg-secondary text-white p-0 px-1" style="font-size: 8px;">Op Head</span>
                                                        </div>
                                                        <span class="text-secondary fw-semibold flex-shrink-0" style="font-size: 9.5px;">
                                                            <?= !empty($req['op_head_at']) ? date('d M, h:i A', strtotime($req['op_head_at'])) : 'Approved' ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($isAccountsHeadApproved): ?>
                                                    <div class="p-1 px-2 rounded-2 bg-primary-subtle border border-primary-subtle text-start d-flex align-items-center justify-content-between"
                                                         title="<?= htmlspecialchars(($req['accounts_head_name'] ?? 'Accounts Head') . (!empty($req['accounts_head_at']) ? ' • ' . date('d M, Y h:i A', strtotime($req['accounts_head_at'])) : '')) ?>">
                                                        <div class="d-flex align-items-center gap-1 text-truncate me-1">
                                                            <i class="fa-solid fa-check-double text-primary flex-shrink-0" style="font-size: 10px;"></i>
                                                            <span class="fw-bold text-dark text-truncate" style="font-size: 10.5px; max-width: 110px;">
                                                                <?= htmlspecialchars(!empty($req['accounts_head_name']) ? $req['accounts_head_name'] : 'Accounts Head') ?>
                                                            </span>
                                                            <span class="badge bg-primary text-white p-0 px-1" style="font-size: 8px;">Accts</span>
                                                        </div>
                                                        <span class="text-primary fw-semibold flex-shrink-0" style="font-size: 9.5px;">
                                                            <?= !empty($req['accounts_head_at']) ? date('d M, h:i A', strtotime($req['accounts_head_at'])) : 'Approved' ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                                <?php include __DIR__ . '/partials/fr_mgmt_approvals_block.php'; ?>
                                            </div>

                                        <?php elseif (in_array($req['status'], ['approved', 'partially_approved', 'partially_paid'])): ?>
                                            <div class="d-flex flex-column gap-1 text-start" style="min-width: 180px; max-width: 220px; margin: 0 auto;">
                                                <?php
                                                $hasOpHead = (strtolower($req['op_head_status'] ?? '') === 'approved' || !empty($req['op_head_by']));
                                                $opName = !empty($req['op_head_name']) ? $req['op_head_name'] : 'Admin & Operation Head';
                                                $opDate = !empty($req['op_head_at']) ? $req['op_head_at'] : null;

                                                $ahId = (int)($req['accounts_head_by'] ?? 0);
                                                $opId = (int)($req['op_head_by'] ?? 0);
                                                $appId = (int)($req['approved_by'] ?? 0);
                                                $appName = strtolower(trim($req['approver_name'] ?? ''));
                                                $ahName = strtolower(trim($req['accounts_head_name'] ?? ''));
                                                $opNameLower = strtolower(trim($req['op_head_name'] ?? ''));
                                                $appDes = strtolower(trim($req['approver_designation'] ?? ''));

                                                // Determine if there is a distinct Management approval (not Accounts Head and not Op Head)
                                                $isMgmtDistinct = false;
                                                if (!empty($req['approved_by'])) {
                                                    $isSameAsAh = ($appId > 0 && $appId === $ahId) || ($appName !== '' && $appName === $ahName) || (stripos($appName, 'accounts head') !== false) || (stripos($appDes, 'accounts') !== false);
                                                    $isSameAsOp = ($appId > 0 && $appId === $opId) || ($appName !== '' && $appName === $opNameLower);
                                                    if (!$isSameAsAh && !$isSameAsOp) {
                                                        $isMgmtDistinct = true;
                                                    }
                                                } elseif (!$hasOpHead && !$isAccountsHeadApproved && !empty($req['approver_name'])) {
                                                    $isMgmtDistinct = true;
                                                }
                                                ?>

                                                <!-- Layer 1: Admin & Operation Head Approval -->
                                                <?php if ($hasOpHead): ?>
                                                    <?php
                                                    $opTooltip = trim(
                                                        (!empty($req['op_head_designation']) ? $req['op_head_designation'] : 'Admin & Operation Head') .
                                                        (!empty($opDate) ? ' • ' . date('d M, Y h:i A', strtotime($opDate)) : '')
                                                    );
                                                    ?>
                                                    <div class="px-1.5 py-1 rounded bg-secondary-subtle border border-secondary-subtle d-flex align-items-center justify-content-between gap-1" 
                                                         title="<?= htmlspecialchars($opTooltip) ?>" style="font-size: 10px; line-height: 1.2;">
                                                        <div class="d-flex align-items-center gap-1 text-truncate me-1">
                                                            <i class="fa-solid fa-check text-secondary flex-shrink-0" style="font-size: 9px;"></i>
                                                            <span class="fw-bold text-dark text-truncate" style="max-width: 105px;" title="<?= htmlspecialchars($opName) ?>">
                                                                <?= htmlspecialchars($opName) ?>
                                                            </span>
                                                            <span class="badge bg-secondary text-white p-0 px-1 flex-shrink-0" style="font-size: 8px;">Op Head</span>
                                                        </div>
                                                        <?php if (!empty($opDate)): ?>
                                                            <span class="text-muted flex-shrink-0" style="font-size: 8.5px; white-space: nowrap;"><?= date('d M, h:i A', strtotime($opDate)) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Layer 2: Accounts Head Row (Pending or Approved) -->
                                                <?php if ($isPendingAccountsHead): ?>
                                                    <div class="px-1.5 py-1 rounded bg-warning-subtle border border-warning-subtle d-flex align-items-center justify-content-between gap-1" title="Payment locked until Accounts Head approves" style="font-size: 10px; line-height: 1.2;">
                                                        <div class="d-flex align-items-center gap-1 text-truncate me-1">
                                                            <i class="fa-solid fa-hourglass-half text-warning flex-shrink-0" style="font-size: 9px;"></i>
                                                            <span class="text-dark fw-semibold text-truncate">Pending Accts Head</span>
                                                        </div>
                                                        <span class="badge bg-warning text-dark p-0 px-1 flex-shrink-0" style="font-size: 8.5px;">AH</span>
                                                    </div>
                                                <?php elseif ($isAccountsHeadApproved): ?>
                                                    <?php
                                                    $ahTooltip = trim(
                                                        (!empty($req['accounts_head_designation']) ? $req['accounts_head_designation'] : 'Accounts Head') .
                                                        (!empty($req['accounts_head_at']) ? ' • Approved: ' . date('d M, Y h:i A', strtotime($req['accounts_head_at'])) : '')
                                                    );
                                                    ?>
                                                    <div class="px-1.5 py-1 rounded bg-primary-subtle border border-primary-subtle d-flex align-items-center justify-content-between gap-1" title="<?= htmlspecialchars($ahTooltip) ?>" style="font-size: 10px; line-height: 1.2;">
                                                        <div class="d-flex align-items-center gap-1 text-truncate me-1">
                                                            <i class="fa-solid fa-check-double text-primary flex-shrink-0" style="font-size: 9.5px;"></i>
                                                            <span class="fw-bold text-dark text-truncate" style="max-width: 105px;" title="<?= htmlspecialchars(!empty($req['accounts_head_name']) ? $req['accounts_head_name'] : 'Accounts Head') ?>">
                                                                <?= htmlspecialchars(!empty($req['accounts_head_name']) ? $req['accounts_head_name'] : 'Accounts Head') ?>
                                                            </span>
                                                            <span class="badge bg-primary text-white p-0 px-1 flex-shrink-0" style="font-size: 8px;">Accts</span>
                                                        </div>
                                                        <?php if (!empty($req['accounts_head_at'])): ?>
                                                            <span class="text-muted flex-shrink-0" style="font-size: 8.5px; white-space: nowrap;"><?= date('d M, h:i A', strtotime($req['accounts_head_at'])) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Layer 3: Management Final Approval (Only if distinct Management approved, or multiple partial approvals) -->
                                                <?php if (count($req['mgmt_approvals'] ?? []) > 1 || (($req['fr_status'] ?? '') === 'partially_approved')): ?>
                                                    <?php include __DIR__ . '/partials/fr_mgmt_approvals_block.php'; ?>
                                                <?php elseif ($isMgmtDistinct): ?>
                                                    <?php
                                                    $approverTooltip = trim(
                                                        (!empty($req['approver_designation']) ? $req['approver_designation'] : 'Management Executive') .
                                                        (!empty($req['approver_email']) ? ' • ' . $req['approver_email'] : '') .
                                                        ($req['approved_at'] ? ' • ' . date('d M, Y h:i A', strtotime($req['approved_at'])) : '')
                                                    );
                                                    $mgmtBadgeBg = $req['status'] === 'approved' ? 'bg-success-subtle border-success-subtle' : 'bg-info-subtle border-info-subtle';
                                                    ?>
                                                    <div class="px-1.5 py-1 rounded <?= $mgmtBadgeBg ?> border d-flex align-items-center justify-content-between gap-1" title="<?= htmlspecialchars($approverTooltip) ?>" style="font-size: 10px; line-height: 1.2;">
                                                        <div class="d-flex align-items-center gap-1 text-truncate me-1">
                                                            <i class="fa-solid fa-circle-check text-success flex-shrink-0" style="font-size: 9.5px;"></i>
                                                            <span class="fw-bold text-dark text-truncate" style="max-width: 105px;" title="<?= htmlspecialchars($req['approver_name'] ?? 'Management') ?>">
                                                                <?= htmlspecialchars($req['approver_name'] ?? 'Management') ?>
                                                            </span>
                                                            <span class="badge bg-success text-white p-0 px-1 flex-shrink-0" style="font-size: 8px;">Mgmt</span>
                                                        </div>
                                                        <?php if ($req['approved_at']): ?>
                                                            <span class="text-muted flex-shrink-0" style="font-size: 8.5px; white-space: nowrap;"><?= date('d M, h:i A', strtotime($req['approved_at'])) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                        <?php elseif ($req['status'] === 'rejected'): ?>
                                            <div class="d-flex flex-column gap-1 text-center" style="max-width: 180px; margin: 0 auto;">
                                                <span class="badge bg-danger text-white rounded-pill px-2.5 py-1 extra-small fw-semibold">
                                                    <i class="fa-solid fa-circle-xmark me-1"></i>Rejected
                                                </span>
                                                <span class="extra-small text-muted" title="<?= htmlspecialchars($req['approver_name'] ?? 'Management') ?>">
                                                    By: <?= htmlspecialchars($req['approver_name'] ?? 'Management') ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Accounts Payout Status -->
                                    <td class="text-center">
                                        <?php if ($req['payout_status'] === 'Paid & Settled'): ?>
                                            <?php
                                            $payoutTooltip = trim(
                                                'Paid & Settled: ৳ ' . number_format($req['payout_amount'], 2) .
                                                (!empty($req['disbursed_by_name']) ? ' • Disbursed by: ' . $req['disbursed_by_name'] : '') .
                                                (!empty($req['disbursed_branch_name']) ? ' (' . $req['disbursed_branch_name'] . ')' : '') .
                                                (!empty($req['payout_date']) ? ' • ' . date('d M, Y', strtotime($req['payout_date'])) : '')
                                            );
                                            ?>
                                            <div class="d-flex flex-column gap-1 text-center" style="max-width: 190px; margin: 0 auto;" title="<?= htmlspecialchars($payoutTooltip) ?>">
                                                <div>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 extra-small fw-bold">
                                                        <i class="fa-solid fa-money-check-dollar me-1"></i>Paid: ৳ <?= number_format($req['payout_amount'], 2) ?>
                                                    </span>
                                                </div>
                                                <?php if (!empty($req['disbursed_by_name'])): ?>
                                                    <div class="extra-small text-dark fw-semibold text-truncate" style="font-size: 11px;">
                                                        <i class="fa-solid fa-user-check text-success me-1"></i><?= htmlspecialchars($req['disbursed_by_name']) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="d-flex align-items-center justify-content-center gap-1 extra-small text-muted" style="font-size: 10px;">
                                                    <?php if (!empty($req['disbursed_branch_name'])): ?>
                                                        <span class="badge bg-light text-secondary border p-0 px-1" style="font-size: 9px;">
                                                            <i class="fa-solid fa-building me-1 text-muted"></i><?= htmlspecialchars($req['disbursed_branch_name']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($req['payout_date']): ?>
                                                        <span><?= date('d M, Y', strtotime($req['payout_date'])) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                        <?php elseif ($req['payout_status'] === 'Partially Paid'): ?>
                                            <?php
                                            $payoutTooltip = trim(
                                                'Partially Paid: ৳ ' . number_format($req['payout_amount'], 2) .
                                                (!empty($req['disbursed_by_name']) ? ' • By: ' . $req['disbursed_by_name'] : '') .
                                                (!empty($req['disbursed_branch_name']) ? ' (' . $req['disbursed_branch_name'] . ')' : '') .
                                                (!empty($req['payout_date']) ? ' • ' . date('d M, Y', strtotime($req['payout_date'])) : '')
                                            );
                                            ?>
                                            <div class="d-flex flex-column gap-1 text-center" style="max-width: 190px; margin: 0 auto;" title="<?= htmlspecialchars($payoutTooltip) ?>">
                                                <div>
                                                    <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2.5 py-1 extra-small fw-bold">
                                                        <i class="fa-solid fa-money-check-dollar me-1"></i>Partially Paid
                                                    </span>
                                                </div>
                                                <span class="extra-small fw-bold text-success">৳ <?= number_format($req['payout_amount'], 2) ?></span>
                                                <div class="d-flex align-items-center justify-content-center gap-1 extra-small text-muted" style="font-size: 10px;">
                                                    <?php if (!empty($req['disbursed_by_name'])): ?>
                                                        <span class="text-dark fw-semibold text-truncate"><?= htmlspecialchars($req['disbursed_by_name']) ?></span>
                                                    <?php endif; ?>
                                                    <?php if ($req['payout_date']): ?>
                                                        <span><?= date('d M, Y', strtotime($req['payout_date'])) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                        <?php elseif ($isPendingAccountsHead): ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2.5 py-1 extra-small">
                                                <i class="fa-solid fa-user-clock me-1"></i>Pending Accts Head
                                            </span>
                                        <?php elseif ($req['payout_status'] === 'Pending Accounts Payout' || $req['status'] === 'partially_approved'): ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2.5 py-1 extra-small">
                                                <i class="fa-solid fa-wallet me-1"></i>Pending Payout
                                            </span>
                                        <?php elseif (in_array($req['status'], ['approved', 'partially_approved'])): ?>
                                            <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2.5 py-1 extra-small">
                                                <i class="fa-solid fa-check me-1"></i>Approved
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border extra-small">N/A</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Action Button (Directly routes to specific module page or Debit Voucher!) -->
                                    <td class="pe-4 text-end text-nowrap">
                                        <?php
                                        $isVoucherSupported = true;
                                        $voucherUrl = $base_url . '/print-debit-voucher?module=' . urlencode($sourceTable) . '&id=' . (int)$req['source_id'];
                                        $isPaidOrSettled = in_array($payoutStatus, ['Paid & Settled', 'Settled']) || in_array(strtolower($req['status']), ['paid', 'fully_paid', 'labor_disbursed', 'deducted']);
                                        $isPartiallyPaid = ($payoutStatus === 'Partially Paid') || strtolower($req['status']) === 'partially_paid';
                                        ?>

                                        <?php if ($sourceTable === 'project_incentive_claims'): ?>
                                            <?php
                                            $shDone = (strtolower($req['sales_head_status'] ?? '') === 'approved' || strtolower($req['op_head_status'] ?? '') === 'approved');
                                            $ahDone = (strtolower($req['accounts_head_status'] ?? '') === 'approved');
                                            $mgmtDone = in_array(strtolower($req['status'] ?? ''), ['approved', 'paid']);
                                            $isPaid = in_array(strtolower($req['status'] ?? ''), ['paid']) || ($req['payout_status'] === 'Paid & Settled');
                                            ?>
                                            <?php if ($statusLower === 'rejected'): ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-2 px-2 py-1 extra-small me-1">
                                                    <i class="fa-solid fa-ban me-1"></i>Rejected
                                                </span>
                                                <a href="<?= $targetUrl ?>" class="btn btn-sm btn-light text-dark rounded-2 px-2 border" title="View Incentive Management">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            <?php elseif ($isPaid): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-2 px-2 py-1 extra-small me-1">
                                                    <i class="fa-solid fa-check-circle me-1"></i>Settled
                                                </span>
                                                <a href="<?= $targetUrl ?>" class="btn btn-sm btn-light text-dark rounded-2 px-2 border" title="View Incentive Management">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            <?php elseif ($mgmtDone): ?>
                                                <?php if ($isAccountsUser): ?>
                                                    <a href="<?= $targetUrl ?>" class="btn btn-sm btn-success rounded-2 px-3 shadow-sm fw-semibold me-1" title="Disburse Incentive Payout">
                                                        <i class="fa-solid fa-money-check-dollar me-1"></i>Disburse Payout
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge bg-info-subtle text-info border border-info-subtle rounded-2 px-2 py-1 extra-small me-1">
                                                        <i class="fa-solid fa-clock me-1"></i>Ready for Disbursement
                                                    </span>
                                                <?php endif; ?>
                                                <a href="<?= $targetUrl ?>" class="btn btn-sm btn-light text-dark rounded-2 px-2 border" title="View Incentive Management">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            <?php elseif ($reqApproveRole === 'sales_head'): ?>
                                                <?php if ($canUserApprove): ?>
                                                    <button type="button" class="btn btn-sm btn-primary rounded-2 px-2.5 shadow-sm fw-semibold me-1"
                                                        onclick="approveSalesHead(<?= (int)$req['id'] ?>, '<?= htmlspecialchars(addslashes($req['title'] ?? 'Sales Incentive Claim')) ?>', '<?= number_format($req['request_amount'], 2) ?>')"
                                                        title="Approve as Sales Head or Operation Head">
                                                        <i class="fa-solid fa-check me-1"></i>Approve (Sales Head / Op Head)
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-2 px-2 shadow-sm fw-semibold me-1"
                                                        onclick="rejectSalesHead(<?= (int)$req['id'] ?>)"
                                                        title="Reject Claim">
                                                        <i class="fa-solid fa-xmark"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted border rounded-2 px-2 py-1 extra-small me-1" title="Awaiting Sales Team Head or Operation Head review">
                                                        <i class="fa-solid fa-lock text-warning me-1"></i>Pending Sales Head or Operation Head
                                                    </span>
                                                <?php endif; ?>
                                                <a href="<?= $targetUrl ?>" class="btn btn-sm btn-light text-dark rounded-2 px-2 border" title="View Incentive Management">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            <?php elseif ($reqApproveRole === 'accounts_head'): ?>
                                                <?php if ($canUserApprove): ?>
                                                    <button type="button" class="btn btn-sm btn-primary rounded-2 px-2.5 shadow-sm fw-semibold me-1"
                                                        onclick="openAccountsHeadApprovalModal(<?= (int)$req['id'] ?>, '<?= htmlspecialchars(addslashes($req['title'] ?? '')) ?>', '<?= number_format($req['approved_amount'] > 0 ? $req['approved_amount'] : $req['request_amount'], 2) ?>', 'project_incentive_claims')"
                                                        title="Approve as Accounts Head">
                                                        <i class="fa-solid fa-stamp me-1"></i>Approve (Accounts Head)
                                                    </button>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted border rounded-2 px-2 py-1 extra-small me-1" title="Awaiting Accounts Head verification">
                                                        <i class="fa-solid fa-lock text-warning me-1"></i>Awaiting Accounts Head
                                                    </span>
                                                <?php endif; ?>
                                                <a href="<?= $targetUrl ?>" class="btn btn-sm btn-light text-dark rounded-2 px-2 border" title="View Incentive Management">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            <?php elseif ($reqApproveRole === 'management'): ?>
                                                <?php if ($canUserApprove): ?>
                                                    <button type="button" class="btn btn-sm btn-success rounded-2 px-2.5 shadow-sm fw-semibold me-1"
                                                        onclick="approveManagement(<?= (int)$req['id'] ?>, '<?= htmlspecialchars(addslashes($req['title'] ?? 'Sales Incentive Claim')) ?>', '<?= number_format($req['approved_amount'] > 0 ? $req['approved_amount'] : $req['request_amount'], 2) ?>')"
                                                        title="Approve as Management">
                                                        <i class="fa-solid fa-circle-check me-1"></i>Approve (Management)
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-2 px-2 shadow-sm fw-semibold me-1"
                                                        onclick="rejectManagement(<?= (int)$req['id'] ?>)"
                                                        title="Reject Claim">
                                                        <i class="fa-solid fa-xmark"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted border rounded-2 px-2 py-1 extra-small me-1" title="Awaiting Management approval">
                                                        <i class="fa-solid fa-lock text-warning me-1"></i>Awaiting Management
                                                    </span>
                                                <?php endif; ?>
                                                <a href="<?= $targetUrl ?>" class="btn btn-sm btn-light text-dark rounded-2 px-2 border" title="View Incentive Management">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= $targetUrl ?>" class="btn btn-sm btn-light text-dark rounded-2 px-2 border" title="View Incentive Management">
                                                    <i class="fa-solid fa-eye me-1"></i>View Module Page
                                                </a>
                                            <?php endif; ?>

                                        <?php elseif ($isVoucherSupported && !$isPaidOrSettled): ?>
                                            <a href="<?= $voucherUrl ?>&is_approval=1" target="_blank" class="btn btn-sm btn-outline-info rounded-2 px-2 shadow-sm fw-semibold mb-1" title="View & Print Approval Voucher">
                                                <i class="fa-solid fa-file-signature me-1"></i>Approval Voucher
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($isPartiallyPaid): ?>
                                            <?php if (($isAccountsUser || $isManagementUser) && $isAccountsHeadApproved): ?>
                                                <a href="<?= $targetUrl ?>" class="btn btn-sm btn-success rounded-2 px-2 shadow-sm fw-semibold me-1" title="Disburse remaining amount">
                                                    <i class="fa-solid fa-money-check-dollar me-1"></i>Pay
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($isVoucherSupported): ?>
                                                <a href="<?= $voucherUrl ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-2 px-2 shadow-sm fw-semibold" title="View, Print & Download Payment Voucher">
                                                    <i class="fa-solid fa-file-invoice-dollar me-1"></i>Payment Voucher
                                                </a>
                                            <?php endif; ?>

                                        <?php elseif ($isPaidOrSettled): ?>
                                            <?php if ($sourceTable === 'client_refund_requests'): ?>
                                                <!-- Both Receipts: 1. Debit Voucher (Company) & 2. Client Refund Money Receipt -->
                                                <a href="<?= $voucherUrl ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-2 px-2 shadow-sm fw-semibold me-1" title="Company Debit Voucher (Office Copy)">
                                                    <i class="fa-solid fa-file-invoice-dollar me-1"></i>Debit Voucher
                                                </a>
                                                <a href="<?= $base_url ?>/client-refund-receipt/<?= (int)$req['source_id'] ?>" target="_blank" class="btn btn-sm btn-success rounded-2 px-2 shadow-sm fw-semibold me-1" title="Client Refund Money Receipt (Customer Copy)">
                                                    <i class="fa-solid fa-receipt me-1"></i>Client Receipt
                                                </a>
                                            <?php else: ?>
                                                <?php if ($isVoucherSupported): ?>
                                                    <a href="<?= $voucherUrl ?>" target="_blank" class="btn btn-sm btn-primary rounded-2 px-3 shadow-sm fw-semibold me-1" title="View, Print & Download Payment Voucher">
                                                        <i class="fa-solid fa-file-invoice-dollar me-1"></i>Payment Voucher
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <a href="<?= $targetUrl ?>" class="btn btn-sm btn-light text-dark rounded-2 px-2 border" title="View Module Page">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>

                                        <?php elseif ($isPendingAccountsHead): ?>
                                            <?php if ($isAccountsHeadUser || $isManagementUser): ?>
                                                <button type="button" class="btn btn-sm btn-primary rounded-2 px-3 shadow-sm fw-semibold me-1" 
                                                    onclick="openAccountsHeadApprovalModal(<?= (int)$req['id'] ?>, '<?= htmlspecialchars(addslashes($req['title'] ?? '')) ?>', '<?= number_format($req['approved_amount'] > 0 ? $req['approved_amount'] : $req['request_amount'], 2) ?>')"
                                                    title="Approve as Accounts Head">
                                                    <i class="fa-solid fa-stamp me-1"></i>Approve (Accounts Head)
                                                </button>
                                                <?php if ($isFrPartialMgmt && $isManagementUser): ?>
                                                    <a href="<?= $targetUrl ?>" class="btn btn-sm btn-outline-primary rounded-2 px-2 shadow-sm fw-semibold me-1" title="Approve / Reject the remaining (unapproved) balance">
                                                        <i class="fa-solid fa-up-right-from-square me-1"></i>Remaining
                                                    </a>
                                                <?php endif; ?>
                                                <a href="<?= $targetUrl ?>" class="btn btn-sm btn-light text-dark rounded-2 px-2 border" title="View Module Page">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            <?php elseif ($isAccountsStaffUser): ?>
                                                <span class="badge bg-light text-muted border rounded-2 px-2 py-1 extra-small me-1" title="Payment locked until Accounts Head approves">
                                                    <i class="fa-solid fa-lock text-warning me-1"></i>Awaiting Accounts Head
                                                </span>
                                                <a href="<?= $targetUrl ?>" class="btn btn-sm btn-light text-dark rounded-2 px-2 border" title="View Module Page">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= $targetUrl ?>" class="btn btn-sm btn-light text-dark rounded-2 px-2 border" title="View Module Page">
                                                    <i class="fa-solid fa-eye me-1"></i>View Module Page
                                                </a>
                                            <?php endif; ?>

                                        <?php elseif (($isAccountsUser || $isManagementUser) && in_array($payoutStatus, ['Pending Accounts Payout']) && $isAccountsHeadApproved): ?>
                                            <a href="<?= $targetUrl ?>" class="btn btn-sm btn-success rounded-2 px-3 shadow-sm fw-semibold">
                                                <i class="fa-solid fa-money-check-dollar me-1"></i>Pay / Disburse
                                            </a>

                                        <?php elseif (in_array($req['status'], ['pending', 'partially_approved'])): ?>
                                            <?php if ($isChiefOfficer): ?>
                                                <a href="<?= !empty($targetUrl) && $targetUrl !== 'javascript:void(0)' ? $targetUrl : 'javascript:void(0)' ?>"
                                                    class="btn btn-sm btn-light text-dark rounded-2 px-2 border"
                                                    title="Pending for <?= htmlspecialchars(trim(preg_replace('/\s*\(Role[^\)]*\)/i', '', $req['pending_for_text'] ?? 'Management')) ?: 'Management') ?>">
                                                    <i class="fa-solid fa-eye me-1"></i>View Details
                                                </a>
                                            <?php elseif ($canUserApprove): ?>
                                                <?php if ($sourceTable === 'lead_bookings'): ?>
                                                    <button type="button" class="btn btn-sm btn-primary rounded-2 px-3 shadow-sm fw-semibold"
                                                        onclick="openDiscountApprovalModal(<?= htmlspecialchars(json_encode([
                                                            'id' => (int)$req['id'],
                                                            'source_id' => (int)$req['source_id'],
                                                            'title' => $req['title'] ?? 'Lead Booking Discount Approval',
                                                            'amount' => (float)$req['request_amount'],
                                                            'client_name' => $req['booking_client_name'] ?? '',
                                                            'project_name' => $req['booking_project_name'] ?? '',
                                                            'flat_name' => $req['booking_flat_name'] ?? '',
                                                            'floor_no' => $req['booking_floor_no'] ?? '',
                                                            'sales_agent' => $req['booking_sales_agent'] ?? '',
                                                            'sales_code' => $req['booking_sales_code'] ?? '',
                                                            'agreement_value' => $req['booking_agreement_value'] ?? 0,
                                                            'attachment' => $req['attachment'] ?? '',
                                                            'op_head_status' => $req['op_head_status'] ?? '',
                                                            'op_head_name' => $req['op_head_name'] ?? '',
                                                            'op_head_at' => $req['op_head_at'] ?? '',
                                                            'status' => $req['status'] ?? '',
                                                            'booking_url' => $targetUrl
                                                        ])) ?>)"
                                                        title="Review & Process Booking Discount">
                                                        <i class="fa-solid fa-stamp me-1"></i>Review / Process
                                                    </button>
                                                <?php elseif ($sourceTable === 'client_refund_requests'): ?>
                                                    <button type="button" class="btn btn-sm btn-primary rounded-2 px-3 shadow-sm fw-semibold"
                                                        onclick="openRefundApprovalModal(<?= htmlspecialchars(json_encode([
                                                            'id'         => (int)$req['id'],
                                                            'source_id'  => (int)$req['source_id'],
                                                            'title'      => $req['title'] ?? 'Client Refund Approval',
                                                            'amount'     => (float)$req['request_amount'],
                                                            'stage'      => $req['approve_role'] ?? 'sales_manager',
                                                            'detail_url' => $targetUrl
                                                        ]), ENT_QUOTES, 'UTF-8') ?>)"
                                                        title="Approve or Reject Refund Request">
                                                        <i class="fa-solid fa-stamp me-1"></i>Review / Approve
                                                    </button>
                                                <?php else: ?>
                                                    <a href="<?= $targetUrl ?>" class="btn btn-sm btn-primary rounded-2 px-3 shadow-sm fw-semibold">
                                                        <i class="fa-solid fa-up-right-from-square me-1"></i>Review / Process
                                                    </a>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <?php
                                                $pfClean = trim(preg_replace('/\s*\(Role[^\)]*\)/i', '', str_replace(['\u0026', '&amp;'], ['&', '&'], $req['pending_for_text'] ?? '')));
                                                $opHeadDone = (strtolower($req['op_head_status'] ?? '') === 'approved');
                                                if ($isFrPartialMgmt) {
                                                    $pfShort = $isAccountsHeadApproved ? 'Accounts / Mgmt (Remaining)' : 'Accounts Head';
                                                } else {
                                                    $pfShort = $pfClean !== '' ? $pfClean : 'Other Role';
                                                }
                                                if (mb_strlen($pfShort) > 30) {
                                                    $pfShort = mb_substr($pfShort, 0, 28) . '…';
                                                }
                                                $ahDone = ($ahStatus === 'approved');
                                                $awaitOpHead = ($sourceTable !== 'client_refund_requests' && $reqAmount > 10000.01 && !$opHeadDone && $isManagementUser);
                                                $awaitAh = ($sourceTable !== 'client_refund_requests' && $reqAmount > 10000.01 && $opHeadDone && !$ahDone && $isManagementUser);
                                                if ($awaitOpHead) {
                                                    $lockLabel = 'Awaiting Op Head';
                                                    $lockTitle = 'Amount exceeds ৳10,000 - Admin & Operation Head approval required first';
                                                } elseif ($awaitAh) {
                                                    $lockLabel = 'Awaiting Accounts Head';
                                                    $lockTitle = 'Amount exceeds ৳10,000 - Accounts Head payment verification required before Management';
                                                } else {
                                                    $lockLabel = 'Pending: ' . $pfShort;
                                                    $lockTitle = (($isSiteOperator && $opHeadDone) ? 'You (Admin & Operation Head) already approved. ' : '') . 'Pending for ' . ($pfClean !== '' ? $pfClean : 'Other Role');
                                                }
                                                ?>
                                                <a href="<?= !empty($targetUrl) && $targetUrl !== 'javascript:void(0)' ? $targetUrl : 'javascript:void(0)' ?>"
                                                    class="btn btn-sm btn-light text-muted rounded-2 px-2 border"
                                                    title="<?= htmlspecialchars($lockTitle) ?>">
                                                    <i class="fa-solid <?= ($isSiteOperator && $opHeadDone) ? 'fa-circle-check text-success' : 'fa-lock text-warning' ?> me-1"></i><?= htmlspecialchars($lockLabel) ?>
                                                </a>
                                            <?php endif; ?>

                                        <?php else: ?>
                                            <a href="<?= $targetUrl ?>"
                                                class="btn btn-sm btn-light text-dark rounded-2 px-2 border">
                                                <i class="fa-solid fa-eye me-1"></i>View Module Page
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination & Summary Card Footer -->
        <?php if (!empty($pagination) && $pagination['totalRecords'] > 0): ?>
            <div class="card-footer bg-white py-3 border-top d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="text-muted fs-13">
                    Showing <strong class="text-dark"><?= number_format($pagination['startRecord']) ?></strong> to 
                    <strong class="text-dark"><?= number_format($pagination['endRecord']) ?></strong> of 
                    <strong class="text-primary"><?= number_format($pagination['totalRecords']) ?></strong> entries
                </div>
                
                <?php if ($pagination['totalPages'] > 1): ?>
                    <?php
                    $pageUrl = function($p) use ($pagination, $base_url) {
                        $q = array_merge($_GET, ['page' => $p, 'per_page' => $pagination['perPage']]);
                        return $base_url . '/all-approval-requests?' . http_build_query($q) . '#approvalRequestsTable';
                    };
                    $cur = $pagination['currentPage'];
                    $last = $pagination['totalPages'];
                    ?>
                    <nav aria-label="Approval Pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <!-- Previous Page Link -->
                            <li class="page-item <?= $cur <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= $cur > 1 ? $pageUrl($cur - 1) : 'javascript:void(0)' ?>" aria-label="Previous">
                                    <i class="fa-solid fa-chevron-left me-1"></i> Prev
                                </a>
                            </li>
                            
                            <!-- Page Numbers (with smart sliding window) -->
                            <?php
                            $startPage = max(1, $cur - 2);
                            $endPage = min($last, $cur + 2);
                            if ($startPage > 1) {
                                echo '<li class="page-item"><a class="page-link" href="' . $pageUrl(1) . '">1</a></li>';
                                if ($startPage > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                                }
                            }
                            for ($p = $startPage; $p <= $endPage; $p++) {
                                $active = ($p === $cur) ? 'active' : '';
                                echo '<li class="page-item ' . $active . '"><a class="page-link" href="' . $pageUrl($p) . '">' . $p . '</a></li>';
                            }
                            if ($endPage < $last) {
                                if ($endPage < $last - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="' . $pageUrl($last) . '">' . $last . '</a></li>';
                            }
                            ?>
                            
                            <!-- Next Page Link -->
                            <li class="page-item <?= $cur >= $last ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= $cur < $last ? $pageUrl($cur + 1) : 'javascript:void(0)' ?>" aria-label="Next">
                                    Next <i class="fa-solid fa-chevron-right ms-1"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

<!-- Accounts Head Quick Approval Modal -->
<div class="modal fade" id="accountsHeadApprovalModal" tabindex="-1" aria-labelledby="accountsHeadApprovalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title fw-bold" id="accountsHeadApprovalModalLabel">
                    <i class="fa-solid fa-stamp me-2"></i>Accounts Head Approval
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="p-3 bg-light rounded-3 mb-3 border">
                    <div class="extra-small text-muted text-uppercase fw-bold mb-1">Requisition / Request Title</div>
                    <div class="fw-bold text-dark fs-14" id="ahModalReqTitle">--</div>
                    <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                        <span class="extra-small text-muted">Approved Amount:</span>
                        <span class="fs-15 fw-bold text-success" id="ahModalReqAmount">৳ 0.00</span>
                    </div>
                </div>

                <div class="alert alert-info py-2 px-3 extra-small mb-3 border-info-subtle">
                    <i class="fa-solid fa-user-shield me-1"></i>
                    <strong>Authorized Role:</strong> Accounts Head (Role 30)
                </div>

                <input type="hidden" id="ahModalReqId" value="">
                
                <div class="mb-3" id="ahModalAmountContainer">
                    <label for="ahModalApprovedAmount" class="form-label extra-small fw-bold text-muted text-uppercase">Approved Amount (৳)</label>
                    <input type="number" step="0.01" min="0" class="form-control form-control-sm fw-bold text-success" id="ahModalApprovedAmount" placeholder="Enter approved amount">
                    <div class="form-text extra-small text-muted">You may adjust this amount for partial approval if required.</div>
                </div>
                
                <div class="mb-3" id="ahModalCoaContainer">
                    <label for="ahModalCoaId" class="form-label extra-small fw-bold text-muted text-uppercase">Chart of Accounts Category (Required for Posting)</label>
                    <select class="form-select form-select-sm ah-coa-select2" id="ahModalCoaId" name="coa_id">
                        <option value="">-- Select Expense / Asset Ledger --</option>
                        <?php if(!empty($coaList)): ?>
                            <?php 
                            $currentGroup = '';
                            foreach($coaList as $coa): 
                                $group = $coa['account_type'];
                                if($group !== $currentGroup):
                                    if($currentGroup !== '') echo '</optgroup>';
                                    echo '<optgroup label="'.htmlspecialchars($group).'">';
                                    $currentGroup = $group;
                                endif;
                            ?>
                                <option value="<?= $coa['id'] ?>"><?= htmlspecialchars($coa['account_code'] . ' - ' . $coa['account_name']) ?></option>
                            <?php endforeach; ?>
                            <?php if($currentGroup !== '') echo '</optgroup>'; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="ahModalNote" class="form-label extra-small fw-bold text-muted text-uppercase">Approval / Rejection Note (Optional)</label>
                    <textarea class="form-control" id="ahModalNote" rows="2" placeholder="e.g. Verified and approved for payment disbursement."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light px-4 py-3 d-flex justify-content-between">
                <button type="button" class="btn btn-outline-danger btn-sm px-3" onclick="submitAccountsHeadAction('reject')">
                    <i class="fa-solid fa-times me-1"></i>Reject Request
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm px-4 fw-bold" id="ahModalSubmitBtn" onclick="submitAccountsHeadAction('approve')">
                        <i class="fa-solid fa-check me-1"></i>Approve as Accounts Head
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Lead Booking Discount Approval Modal -->
<div class="modal fade" id="discountApprovalModal" tabindex="-1" aria-labelledby="discountApprovalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">
            <div class="modal-header text-white px-4 py-3" style="background: linear-gradient(135deg, #006837 0%, #004d29 100%);">
                <div>
                    <h6 class="modal-title fw-bold fs-16 mb-0" id="discountApprovalModalLabel">
                        <i class="fa-solid fa-tags me-2 text-warning"></i>Review & Process Booking Discount
                    </h6>
                    <small class="text-white-50" id="discModalReqCode">#REQ-00000</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="discModalReqId" value="">
                <input type="hidden" id="discModalBookingId" value="">

                <!-- Workflow Step Notice -->
                <div id="discModalWorkflowNotice" class="alert alert-warning py-2 px-3 extra-small mb-3 border-warning-subtle">
                    <!-- Dynamic -->
                </div>

                <!-- Client & Unit Info Grid -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <div class="text-muted extra-small text-uppercase fw-bold mb-2 pb-1 border-bottom"><i class="fa-solid fa-user me-1 text-primary"></i> Client Details</div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted extra-small">Client Name:</span>
                                <span class="fw-bold text-dark extra-small text-end" id="discModalClient">--</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted extra-small">Project:</span>
                                <span class="fw-semibold text-dark extra-small text-end" id="discModalProject">--</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted extra-small">Flat / Floor:</span>
                                <span class="fw-semibold text-dark extra-small text-end" id="discModalFlat">--</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <div class="text-muted extra-small text-uppercase fw-bold mb-2 pb-1 border-bottom"><i class="fa-solid fa-receipt me-1 text-success"></i> Financial & Sales Info</div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted extra-small">Sales Agent:</span>
                                <span class="fw-semibold text-dark extra-small text-end" id="discModalSalesAgent">--</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted extra-small">Agreement Value:</span>
                                <span class="fw-bold text-dark extra-small text-end" id="discModalAgreement">--</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center pt-1 border-top">
                                <span class="text-muted extra-small fw-bold">Requested Discount:</span>
                                <span class="fs-15 fw-bold text-success" id="discModalAmount">৳ 0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attachment Container -->
                <div class="mb-3" id="discModalAttachmentContainer" style="display: none;">
                    <!-- Dynamic Attachment -->
                </div>

                <!-- Note Input -->
                <div class="mb-2">
                    <label for="discModalNote" class="form-label extra-small fw-bold text-muted text-uppercase mb-1">Approval / Rejection Note</label>
                    <textarea class="form-control" id="discModalNote" rows="2" placeholder="Enter optional comments or instructions..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light px-4 py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-danger btn-sm px-3" onclick="submitDiscountAction('reject')">
                        <i class="fa-solid fa-times me-1"></i>Reject Discount
                    </button>
                    <a href="#" id="discModalHistoryLink" target="_blank" class="btn btn-outline-info btn-sm px-3" title="Open client transaction & cash-in history">
                        <i class="fa-solid fa-up-right-from-square me-1"></i>Client History
                    </a>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success btn-sm px-4 fw-bold" id="discModalSubmitBtn" onclick="submitDiscountAction('approve')">
                        <i class="fa-solid fa-check me-1"></i>Approve Discount
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let approvalTable;

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof $.fn.DataTable !== 'undefined' && $('#allApprovalRequestsTable tbody tr td[colspan]').length === 0) {
            if ($.fn.DataTable.isDataTable('#allApprovalRequestsTable')) {
                $('#allApprovalRequestsTable').DataTable().destroy();
            }
            approvalTable = $('#allApprovalRequestsTable').DataTable({
                paging: false,
                info: false,
                order: [],
                dom: 'rt', // No search bar inside table card (removed "f")
                language: {
                    search: "_INPUT_",
                    zeroRecords: '<div class="text-center py-4 text-muted"><i class="fa-solid fa-magnifying-glass fs-4 d-block mb-2 text-secondary"></i>No matching approval records found.</div>'
                }
            });

            // Connect top general search bar to DataTables real-time filtering across all columns
            const $topSearch = $('#topApprovalSearchInput');
            if ($topSearch.length) {
                // Initial client-side filter if search parameter was present on load
                if ($topSearch.val()) {
                    approvalTable.search($topSearch.val()).draw();
                }

                // Instant real-time filtering across ANY column key value as user types in top search bar!
                $topSearch.on('input keyup search', function () {
                    const query = $(this).val();
                    approvalTable.search(query).draw();
                });
            }

            // Dynamically update top mini card counts when searching or filtering
            approvalTable.on('search.dt draw.dt', function () {
                const searchVal = approvalTable.search();
                if (!searchVal || searchVal.trim() === '') {
                    // Restore original counts
                    $('.module-mini-card').each(function() {
                        const origCount = parseInt($(this).attr('data-pending-count')) || 0;
                        const $badge = $(this).find('.mod-badge-count');
                        if (origCount > 0) {
                            $badge.removeClass('bg-light text-muted border bg-primary text-white')
                                  .addClass('bg-warning text-dark')
                                  .text(origCount + ' Req');
                        } else {
                            $badge.removeClass('bg-warning text-dark bg-primary text-white')
                                  .addClass('bg-light text-muted border')
                                  .text('0');
                        }
                    });
                } else {
                    // Count matching rows per module
                    const moduleCounts = {};
                    approvalTable.rows({ search: 'applied' }).every(function () {
                        const node = this.node();
                        const srcTable = $(node).attr('data-source-table') || '';
                        if (srcTable) {
                            moduleCounts[srcTable] = (moduleCounts[srcTable] || 0) + 1;
                        }
                    });

                    $('.module-mini-card').each(function() {
                        const modKey = $(this).attr('data-mod-key');
                        const count = moduleCounts[modKey] || 0;
                        const $badge = $(this).find('.mod-badge-count');
                        if (count > 0) {
                            $badge.removeClass('bg-light text-muted border bg-warning text-dark')
                                  .addClass('bg-primary text-white')
                                  .text(count + ' found');
                        } else {
                            $badge.removeClass('bg-warning text-dark bg-primary text-white')
                                  .addClass('bg-light text-muted border')
                                  .text('0');
                        }
                    });
                }
            });
        }

        // Row click navigates to requisition view page (skipping buttons, inputs, links, checkboxes)
        $('#allApprovalRequestsTable tbody').on('click', 'tr.clickable-approval-row', function (e) {
            if ($(e.target).closest('input, button, a, .form-check, .dropdown-menu, .modal').length) {
                return;
            }
            const href = $(this).attr('data-href');
            if (href) {
                window.location.href = href;
            }
        });
    });

    function changeApprovalTablePageLength(val) {
        const url = new URL(window.location.href);
        url.searchParams.set('per_page', val);
        url.searchParams.set('page', '1');
        window.location.href = url.toString();
    }

    function toggleSelectAllApprovalRequests(master) {
        const rows = approvalTable ? approvalTable.rows({ search: 'applied' }).nodes() : document.querySelectorAll('#allApprovalRequestsTable tbody tr');
        $(rows).find('.approval-req-checkbox:not(:disabled)').prop('checked', master.checked);
        updateBulkApprovalCount();
    }

    function getCheckedApprovalCheckboxes() {
        if (approvalTable) {
            return approvalTable.$('.approval-req-checkbox:checked');
        }
        return $('.approval-req-checkbox:checked');
    }

    function updateBulkApprovalCount() {
        const checkedCount = getCheckedApprovalCheckboxes().length;
        const badge = document.getElementById('selectedApprovalCountBadge');
        if (badge) {
            badge.innerText = checkedCount + ' Selected';
        }
    }

    function processBulkApprovalAction(action) {
        const selectedIds = [];
        getCheckedApprovalCheckboxes().each(function () {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            alert('Please select at least one pending approval request.');
            return;
        }

        const actionTitle = action === 'reject' ? 'REJECT' : 'APPROVE';
        if (!confirm(`Are you sure you want to BULK ${actionTitle} ${selectedIds.length} selected request(s)?`)) {
            return;
        }

        fetch('<?= $base_url ?>/batch-process-approval-requests', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                request_ids: selectedIds,
                action: action,
                note: 'Bulk ' + actionTitle + ' from Management All Approval Requests Dashboard'
            })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to process bulk approval.');
                }
            })
            .catch(err => {
                alert('Error processing bulk approval: ' + err);
            });
    }

    function openAccountsHeadApprovalModal(reqId, title, amount, sourceTable = '') {
        document.getElementById('ahModalReqId').value = reqId;
        document.getElementById('ahModalReqTitle').innerText = title;
        document.getElementById('ahModalReqAmount').innerText = '৳ ' + amount;
        const rawAmt = parseFloat(amount.toString().replace(/,/g, '')) || 0;
        const amtInput = document.getElementById('ahModalApprovedAmount');
        if (amtInput) amtInput.value = rawAmt > 0 ? rawAmt.toFixed(2) : '';
        document.getElementById('ahModalNote').value = '';
        const coaContainer = document.getElementById('ahModalCoaContainer');
        if (coaContainer) {
            if (sourceTable === 'project_incentive_claims') {
                coaContainer.style.display = 'none';
            } else {
                coaContainer.style.display = '';
            }
        }
        const modal = new bootstrap.Modal(document.getElementById('accountsHeadApprovalModal'));
        modal.show();
    }

    function submitAccountsHeadAction(action) {
        const reqId = document.getElementById('ahModalReqId').value;
        const note = document.getElementById('ahModalNote').value;
        const btn = document.getElementById('ahModalSubmitBtn');

        if (action === 'reject') {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Confirm Rejection',
                    text: 'Are you sure you want to REJECT this request as Accounts Head?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Reject Request'
                }).then((result) => {
                    if (result.isConfirmed) {
                        executeAhAction(reqId, note, btn, action);
                    }
                });
                return;
            } else if (!confirm('Are you sure you want to REJECT this request as Accounts Head?')) {
                return;
            }
        }

        executeAhAction(reqId, note, btn, action);
    }

    function executeAhAction(reqId, note, btn, action) {

        const endpoint = action === 'reject' 
            ? '<?= $base_url ?>/approval-requests/reject-accounts-head' 
            : '<?= $base_url ?>/approval-requests/approve-accounts-head';

        const coaContainer = document.getElementById('ahModalCoaContainer');
        const isCoaVisible = coaContainer && coaContainer.style.display !== 'none';
        const coaId = document.getElementById('ahModalCoaId') ? document.getElementById('ahModalCoaId').value : null;

        if (action === 'approve' && isCoaVisible && !coaId) {
            alert('Please select a Chart of Accounts category before approving.');
            return;
        }

        if (btn) btn.disabled = true;

        const approvedAmtInput = document.getElementById('ahModalApprovedAmount');
        const approvedAmt = (action === 'approve' && approvedAmtInput && approvedAmtInput.value) ? parseFloat(approvedAmtInput.value) : null;

        fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                approval_request_id: reqId,
                note: note || (action === 'reject' ? 'Rejected by Accounts Head' : 'Approved by Accounts Head'),
                coa_id: coaId,
                approved_amount: approvedAmt
            })
        })
        .then(res => res.json())
        .then(data => {
            if (btn) btn.disabled = false;
            if (data.success) {
                const modalEl = document.getElementById('accountsHeadApprovalModal');
                if (modalEl) {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: (action === 'reject') ? 'Rejected!' : 'Approved!',
                        text: data.message || 'Accounts Head action completed.',
                        timer: 1200,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    window.location.reload();
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Operation Failed',
                        text: data.message || 'Operation failed'
                    });
                } else if (typeof toastr !== 'undefined') {
                    toastr.error(data.message || 'Operation failed');
                } else {
                    console.error('Operation failed:', data.message);
                }
            }
        })
        .catch(err => {
            if (btn) btn.disabled = false;
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error: ' + err
                });
            } else if (typeof toastr !== 'undefined') {
                toastr.error('Error: ' + err);
            } else {
                console.error('Error:', err);
            }
        });
    }

    // =========================================================================
    // Sales Incentive Multi-Stage Approval Handlers (Sales Head & Management)
    // =========================================================================
    function approveSalesHead(reqId, title, amount) {
        const rawAmt = parseFloat(amount.toString().replace(/,/g, '')) || 0;
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Approve Claim (Sales Head)',
                html: `<div class="text-start">
                    <p class="mb-2">Are you sure you want to approve this sales incentive claim as <strong>Sales Team Head</strong>?</p>
                    <div class="p-2 bg-light rounded border mb-2">
                        <strong>Request:</strong> #${String(reqId).padStart(5, '0')}<br>
                        <strong>Title:</strong> ${title}<br>
                        <strong>Claimed Amount:</strong> ৳ ${amount}
                    </div>
                    <div class="mb-2">
                        <label class="form-label extra-small fw-bold">Approved Amount (৳):</label>
                        <input type="number" step="0.01" min="0" id="swalSalesHeadApprovedAmount" class="form-control form-control-sm fw-bold text-success" value="${rawAmt.toFixed(2)}" placeholder="Enter approved amount">
                        <div class="extra-small text-muted mt-1">Adjust if approving a partial amount.</div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label extra-small fw-bold">Approval Note (Optional):</label>
                        <textarea id="swalSalesHeadNote" class="form-control form-control-sm" rows="2" placeholder="e.g. Sales performance verified and approved"></textarea>
                    </div>
                </div>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fa-solid fa-check me-1"></i> Approve & Forward to Accounts Head',
                preConfirm: () => {
                    const note = document.getElementById('swalSalesHeadNote') ? document.getElementById('swalSalesHeadNote').value : '';
                    const appAmt = document.getElementById('swalSalesHeadApprovedAmount') ? document.getElementById('swalSalesHeadApprovedAmount').value : rawAmt;
                    return { note: note, approved_amount: appAmt };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const finalAmt = result.value ? result.value.approved_amount : rawAmt;
                    executeSalesHeadAction(reqId, result.value ? result.value.note : '', 'approve', finalAmt);
                }
            });
        } else {
            const note = prompt('Approve as Sales Head? Enter note (optional):', 'Approved by Sales Team Head');
            if (note !== null) {
                executeSalesHeadAction(reqId, note, 'approve', rawAmt);
            }
        }
    }

    function rejectSalesHead(reqId) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Reject Claim (Sales Head)',
                text: 'Are you sure you want to reject this sales incentive claim?',
                input: 'textarea',
                inputPlaceholder: 'Enter reason for rejection...',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Reject',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Please enter a rejection reason!';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    executeSalesHeadAction(reqId, result.value, 'reject');
                }
            });
        } else {
            const note = prompt('Enter rejection reason:');
            if (note) {
                executeSalesHeadAction(reqId, note, 'reject');
            }
        }
    }

    function executeSalesHeadAction(reqId, note, action, amount = null) {
        const endpoint = action === 'reject'
            ? '<?= $base_url ?>/approval-requests/reject-sales-head'
            : '<?= $base_url ?>/approval-requests/approve-sales-head';

        fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                approval_request_id: reqId,
                note: note || (action === 'reject' ? 'Rejected by Sales Team Head' : 'Approved by Sales Team Head'),
                approved_amount: amount ? parseFloat(amount.toString().replace(/,/g, '')) : null
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: (action === 'reject') ? 'Rejected!' : 'Approved!',
                        text: data.message || 'Action completed successfully.',
                        timer: 1200,
                        showConfirmButton: false
                    }).then(() => window.location.reload());
                } else {
                    alert(data.message || 'Action completed successfully.');
                    window.location.reload();
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Operation failed' });
                } else {
                    alert('Error: ' + data.message);
                }
            }
        })
        .catch(err => {
            alert('Network or server error: ' + err);
        });
    }

    function approveManagement(reqId, title, amount) {
        const rawAmt = parseFloat(amount.toString().replace(/,/g, '')) || 0;
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Approve Claim (Management)',
                html: `<div class="text-start">
                    <p class="mb-2">Confirm final Management sign-off for this sales incentive claim:</p>
                    <div class="p-2 bg-light rounded border mb-2">
                        <strong>Request:</strong> #${String(reqId).padStart(5, '0')}<br>
                        <strong>Title:</strong> ${title}<br>
                        <strong>Current Approved / Requested:</strong> ৳ ${amount}
                    </div>
                    <div class="mb-2">
                        <label class="form-label extra-small fw-bold">Approved Amount (৳):</label>
                        <input type="number" step="0.01" min="0" id="swalMgmtApprovedAmount" class="form-control form-control-sm fw-bold text-success" value="${rawAmt.toFixed(2)}" placeholder="Enter approved amount">
                        <div class="extra-small text-muted mt-1">Adjust if approving a partial amount.</div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label extra-small fw-bold">Management Note (Optional):</label>
                        <textarea id="swalMgmtNote" class="form-control form-control-sm" rows="2" placeholder="e.g. Final approval granted. Ready for disbursement."></textarea>
                    </div>
                </div>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fa-solid fa-check-double me-1"></i> Final Management Approval',
                preConfirm: () => {
                    const note = document.getElementById('swalMgmtNote') ? document.getElementById('swalMgmtNote').value : '';
                    const appAmt = document.getElementById('swalMgmtApprovedAmount') ? document.getElementById('swalMgmtApprovedAmount').value : rawAmt;
                    return { note: note, approved_amount: appAmt };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const finalAmt = result.value ? result.value.approved_amount : rawAmt;
                    executeManagementAction(reqId, result.value ? result.value.note : '', 'approve', finalAmt);
                }
            });
        } else {
            const note = prompt('Approve as Management? Enter note (optional):', 'Approved by Management');
            if (note !== null) {
                executeManagementAction(reqId, note, 'approve', rawAmt);
            }
        }
    }

    function rejectManagement(reqId) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Reject Claim (Management)',
                text: 'Are you sure you want to reject this claim?',
                input: 'textarea',
                inputPlaceholder: 'Enter reason for rejection...',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Reject',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Please enter a rejection reason!';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    executeManagementAction(reqId, result.value, 'reject');
                }
            });
        } else {
            const note = prompt('Enter rejection reason:');
            if (note) {
                executeManagementAction(reqId, note, 'reject');
            }
        }
    }

    function executeManagementAction(reqId, note, action, amount) {
        const endpoint = action === 'reject'
            ? '<?= $base_url ?>/approval-requests/reject-management'
            : '<?= $base_url ?>/approval-requests/approve-management';

        fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                approval_request_id: reqId,
                note: note || (action === 'reject' ? 'Rejected by Management' : 'Approved by Management'),
                approved_amount: amount ? parseFloat(amount.toString().replace(/,/g, '')) : null
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: (action === 'reject') ? 'Rejected!' : 'Approved!',
                        text: data.message || 'Action completed successfully.',
                        timer: 1200,
                        showConfirmButton: false
                    }).then(() => window.location.reload());
                } else {
                    alert(data.message || 'Action completed successfully.');
                    window.location.reload();
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Operation failed' });
                } else {
                    alert('Error: ' + data.message);
                }
            }
        })
        .catch(err => {
            alert('Network or server error: ' + err);
        });
    }

    // =========================================================================
    // Booking Discount Approval Modal Handlers
    // =========================================================================
    let currentDiscountData = null;
    const currentLoggedRoleId = <?= (int)($_SESSION['user']['role_id'] ?? 0) ?>;
    const currentLoggedRoleName = <?= json_encode(strtolower(trim($_SESSION['user']['role_name'] ?? ''))) ?>;

    function openDiscountApprovalModal(data) {
        currentDiscountData = data;
        document.getElementById('discModalReqId').value = data.id;
        document.getElementById('discModalBookingId').value = data.source_id;
        document.getElementById('discModalReqCode').textContent = '#REQ-' + String(data.id).padStart(5, '0');
        document.getElementById('discModalClient').textContent = data.client_name || 'N/A';
        document.getElementById('discModalProject').textContent = data.project_name || 'N/A';
        
        let flatText = '';
        if (data.flat_name) flatText += 'UNIT ' + data.flat_name;
        if (data.floor_no) flatText += (flatText ? ' - ' : '') + 'FLOOR ' + data.floor_no;
        document.getElementById('discModalFlat').textContent = flatText || 'N/A';

        let agentText = data.sales_agent || 'N/A';
        if (data.sales_code) agentText += ' [' + data.sales_code + ']';
        document.getElementById('discModalSalesAgent').textContent = agentText;

        document.getElementById('discModalAgreement').textContent = '৳ ' + parseFloat(data.agreement_value || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('discModalAmount').textContent = '৳ ' + parseFloat(data.amount || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('discModalNote').value = '';

        const historyLink = document.getElementById('discModalHistoryLink');
        if (historyLink) {
            historyLink.href = data.booking_url || ('<?= $base_url ?>/clients-cash-in-history-view/' + data.source_id + '?ref=all_approvals');
        }

        const attContainer = document.getElementById('discModalAttachmentContainer');
        if (data.attachment) {
            const cleanAtt = data.attachment.replace(/^\/+/, '');
            const attUrl = '<?= $base_url ?>/' + cleanAtt;
            const isImage = cleanAtt.match(/\.(jpeg|jpg|png|gif|webp)$/i);
            if (isImage) {
                attContainer.innerHTML = `
                    <div class="p-2 border rounded bg-white shadow-sm">
                        <div class="d-flex align-items-center justify-content-between mb-1 pb-1 border-bottom">
                            <span class="extra-small fw-bold text-muted"><i class="fa-solid fa-paperclip text-success me-1"></i> Discount Supporting Document</span>
                            <a href="${attUrl}" target="_blank" class="btn btn-xs btn-outline-primary py-0 px-2 extra-small"><i class="fa-solid fa-up-right-from-square me-1"></i> Full Size</a>
                        </div>
                        <div class="text-center mt-2">
                            <a href="${attUrl}" target="_blank">
                                <img src="${attUrl}" alt="Discount Attachment" class="img-fluid rounded border shadow-sm" style="max-height: 160px; object-fit: contain;">
                            </a>
                        </div>
                    </div>
                `;
            } else {
                attContainer.innerHTML = `
                    <div class="p-2 border rounded bg-white shadow-sm d-flex align-items-center justify-content-between">
                        <span class="extra-small fw-bold text-muted"><i class="fa-solid fa-file-pdf text-danger me-1"></i> Discount Attachment Document</span>
                        <a href="${attUrl}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-download me-1"></i> View / Download Document</a>
                    </div>
                `;
            }
            attContainer.style.display = 'block';
        } else {
            attContainer.innerHTML = '';
            attContainer.style.display = 'none';
        }

        const isOver1Lac = (parseFloat(data.amount) > 10000.01);
        const isOpHeadApproved = (String(data.op_head_status).toLowerCase() === 'approved');
        const noticeEl = document.getElementById('discModalWorkflowNotice');
        const submitBtn = document.getElementById('discModalSubmitBtn');

        const isRole20 = (currentLoggedRoleId === 20 || currentLoggedRoleId === 32 || currentLoggedRoleName.includes('operation head') || currentLoggedRoleName.includes('admin & operation'));
        const isManagement = (currentLoggedRoleId === 1 || currentLoggedRoleId === 3 || currentLoggedRoleName.includes('management') || currentLoggedRoleName.includes('super admin'));

        if (isOver1Lac) {
            if (!isOpHeadApproved) {
                if (isRole20 && !isManagement) {
                    noticeEl.className = 'alert alert-warning py-2 px-3 extra-small mb-3 border-warning-subtle';
                    noticeEl.innerHTML = '<i class="fa-solid fa-shield-halved me-1 text-warning"></i> <strong>Tier 1 Verification:</strong> Discount exceeds ৳10,000. Approving will verify and forward this request to Management.';
                    submitBtn.innerHTML = '<i class="fa-solid fa-shield-check me-1"></i> Verify & Forward to Management';
                    submitBtn.className = 'btn btn-success btn-sm px-4 fw-bold';
                    submitBtn.disabled = false;
                } else if (isManagement) {
                    noticeEl.className = 'alert alert-info py-2 px-3 extra-small mb-3 border-info-subtle';
                    noticeEl.innerHTML = '<i class="fa-solid fa-crown me-1 text-primary"></i> <strong>Direct Management Approval:</strong> Discount exceeds ৳10,000. As Top Management, you can approve directly (Operation Head verification will be bypassed automatically).';
                    submitBtn.innerHTML = '<i class="fa-solid fa-check-double me-1"></i> Approve Discount (Management Direct)';
                    submitBtn.className = 'btn btn-success btn-sm px-4 fw-bold';
                    submitBtn.disabled = false;
                } else {
                    noticeEl.className = 'alert alert-warning py-2 px-3 extra-small mb-3 border-warning-subtle';
                    noticeEl.innerHTML = '<i class="fa-solid fa-lock me-1"></i> Pending Admin & Operation Head verification.';
                    submitBtn.innerHTML = '<i class="fa-solid fa-lock me-1"></i> Action Restricted';
                    submitBtn.className = 'btn btn-secondary btn-sm px-4 fw-bold';
                    submitBtn.disabled = true;
                }
            } else {
                noticeEl.className = 'alert alert-info py-2 px-3 extra-small mb-3 border-info-subtle';
                noticeEl.innerHTML = '<i class="fa-solid fa-check-circle me-1 text-success"></i> <strong>Tier 1 Approved:</strong> Verified & approved by Admin & Operation Head' + (data.op_head_name ? (' (' + data.op_head_name + ')') : '') + '. Ready for Top Management final sign-off.';
                if (isManagement) {
                    submitBtn.innerHTML = '<i class="fa-solid fa-check-double me-1"></i> Approve Discount (Management)';
                    submitBtn.className = 'btn btn-success btn-sm px-4 fw-bold';
                    submitBtn.disabled = false;
                } else {
                    submitBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Verified by Op Head';
                    submitBtn.className = 'btn btn-secondary btn-sm px-4 fw-bold';
                    submitBtn.disabled = true;
                }
            }
        } else {
            noticeEl.className = 'alert alert-primary py-2 px-3 extra-small mb-3 border-primary-subtle';
            noticeEl.innerHTML = '<i class="fa-solid fa-info-circle me-1"></i> <strong>Direct Approval:</strong> Discount is within ৳10,000 threshold. Approving directly finalizes this booking discount.';
            if (isRole20 || isManagement) {
                submitBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Approve Discount';
                submitBtn.className = 'btn btn-success btn-sm px-4 fw-bold';
                submitBtn.disabled = false;
            } else {
                submitBtn.innerHTML = '<i class="fa-solid fa-lock me-1"></i> Action Restricted';
                submitBtn.className = 'btn btn-secondary btn-sm px-4 fw-bold';
                submitBtn.disabled = true;
            }
        }

        const modalEl = document.getElementById('discountApprovalModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }

    function submitDiscountAction(action) {
        if (!currentDiscountData) return;
        const reqId = currentDiscountData.id;
        const note = document.getElementById('discModalNote').value.trim();
        const isOver1Lac = (parseFloat(currentDiscountData.amount) > 10000.01);
        const isOpHeadApproved = (String(currentDiscountData.op_head_status).toLowerCase() === 'approved');
        const isRole20 = (currentLoggedRoleId === 20 || currentLoggedRoleId === 32 || currentLoggedRoleName.includes('operation head') || currentLoggedRoleName.includes('admin & operation'));
        const isManagement = (currentLoggedRoleId === 1 || currentLoggedRoleId === 3 || currentLoggedRoleName.includes('management') || currentLoggedRoleName.includes('super admin'));

        let endpoint = '';
        if (action === 'reject') {
            endpoint = (isRole20 && !isManagement && (!isOver1Lac || !isOpHeadApproved))
                ? '<?= $base_url ?>/approval-requests/reject-operation-head'
                : '<?= $base_url ?>/approval-requests/reject-management';
        } else {
            endpoint = (isRole20 && !isManagement && (!isOver1Lac || !isOpHeadApproved))
                ? '<?= $base_url ?>/approval-requests/approve-operation-head'
                : '<?= $base_url ?>/approval-requests/approve-management';
        }

        const btn = document.getElementById('discModalSubmitBtn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Processing...';

        fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                approval_request_id: reqId,
                source_table: 'lead_bookings',
                source_id: currentDiscountData.source_id,
                note: note || (action === 'approve' ? 'Approved Booking Discount' : 'Rejected Booking Discount')
            })
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            if (data.success) {
                const modalEl = document.getElementById('discountApprovalModal');
                if (modalEl) {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: action === 'approve' ? 'Discount Approved!' : 'Discount Rejected',
                        text: data.message || 'Operation completed successfully.',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    window.location.reload();
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Operation Failed',
                        text: data.message || 'Failed to process request.'
                    });
                } else {
                    alert(data.message || 'Failed to process request.');
                }
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Error: ' + err
                });
            } else {
                alert('Network Error: ' + err);
            }
        });
    }
</script>

<!-- Client Refund Direct Approval Modal -->
<div class="modal fade" id="refundApprovalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light border-bottom py-2.5">
                <h6 class="modal-title fw-bold mb-0">
                    <i class="fa-solid fa-rotate-left text-danger me-2"></i>Client Refund Approval
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="mb-2">
                    <div class="text-muted fs-11">Request Title</div>
                    <div class="fw-semibold text-dark fs-13" id="rfModTitle">—</div>
                </div>
                <div class="d-flex justify-content-between align-items-center p-2 rounded-2 bg-light border mb-3">
                    <div>
                        <div class="text-muted fs-11">Current Stage</div>
                        <span class="badge bg-warning text-dark fs-11" id="rfModStage">—</span>
                    </div>
                    <div class="text-end">
                        <div class="text-muted fs-11">Refund Amount</div>
                        <strong class="text-success fs-16 font-monospace" id="rfModAmount">৳ 0.00</strong>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label fs-12 fw-semibold text-muted mb-1">Approval / Rejection Note</label>
                    <textarea id="rfModNote" class="form-control form-control-sm" rows="2" placeholder="Optional remarks..."></textarea>
                </div>
                <div class="text-end">
                    <a href="#" id="rfModDetailLink" class="fs-12 text-primary text-decoration-none" target="_blank">
                        <i class="fa-light fa-up-right-from-square me-1"></i>View Full Refund Details
                    </a>
                </div>
            </div>
            <div class="modal-footer bg-light border-top py-2">
                <button type="button" class="btn btn-sm btn-outline-danger" id="rfBtnReject" onclick="submitRefundApproval('reject')">
                    <i class="fa-solid fa-xmark me-1"></i> Reject
                </button>
                <button type="button" class="btn btn-sm btn-success fw-semibold" id="rfBtnApprove" onclick="submitRefundApproval('approve')">
                    <i class="fa-solid fa-check me-1"></i> Approve
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentRefundApproval = null;
const rfStageMap = {
    'sales_manager': 'Stage 1: Related Sales Manager',
    'qc':            'Stage 2: QC Verification',
    'op_head':       'Stage 3: Operation Head',
    'management':    'Stage 4: Super Admin Final Approval'
};

function openRefundApprovalModal(data) {
    currentRefundApproval = data;
    document.getElementById('rfModTitle').innerText = data.title || 'Client Refund';
    document.getElementById('rfModStage').innerText = rfStageMap[data.stage] || data.stage;
    document.getElementById('rfModAmount').innerText = '৳ ' + parseFloat(data.amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('rfModNote').value = '';
    document.getElementById('rfModDetailLink').href = data.detail_url || ('<?= $base_url ?>/client-refund/' + data.source_id);
    const modalEl = document.getElementById('refundApprovalModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
}

function submitRefundApproval(action) {
    if (!currentRefundApproval) return;
    const btn = document.getElementById(action === 'approve' ? 'rfBtnApprove' : 'rfBtnReject');
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processing...';

    const fd = new FormData();
    fd.append('ajax', '1');
    fd.append('note', document.getElementById('rfModNote').value.trim());

    const endpoint = action === 'approve'
        ? '<?= $base_url ?>/client-refund-approve/' + currentRefundApproval.source_id + '?ajax=1'
        : '<?= $base_url ?>/client-refund-reject/' + currentRefundApproval.source_id + '?ajax=1';

    fetch(endpoint, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = orig;
            if (res.success) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'success', title: 'Done', text: res.message }).then(() => window.location.reload());
                } else {
                    alert(res.message);
                    window.location.reload();
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Not Allowed', text: res.message });
                } else {
                    alert(res.message);
                }
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = orig;
            alert('Network error: ' + err);
        });
}
</script>

<style>
.clickable-approval-row:hover td {
    background-color: rgba(var(--bs-primary-rgb), 0.05) !important;
}
.hover-primary:hover {
    color: var(--bs-primary) !important;
}
</style>