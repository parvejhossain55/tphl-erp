<?php
$page_title = "Vendor Enrollment Requests";
$page = 'Vendor Enrollment Requests';
$show_breadcrumb = true;
$base_url = $GLOBALS['base_url'];
?>

<style>
    .enrollment-kpi-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.03);
        transition: transform 0.2s, box-shadow 0.2s;
        text-decoration: none !important;
        color: inherit;
    }
    .enrollment-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.06);
    }
    .enrollment-kpi-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
    }
    .stepper-pipeline {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .stepper-pip {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        font-weight: 700;
        cursor: pointer;
    }
    .stepper-line {
        width: 8px;
        height: 2px;
        background: #cbd5e1;
    }
    .timeline-vertical {
        position: relative;
        padding-left: 28px;
    }
    .timeline-vertical::before {
        content: '';
        position: absolute;
        left: 11px;
        top: 8px;
        bottom: 8px;
        width: 2px;
        background: #e2e8f0;
    }
    .timeline-item {
        position: relative;
        margin-bottom: 20px;
    }
    .timeline-dot {
        position: absolute;
        left: -28px;
        top: 2px;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        color: #fff;
    }
    .pulse-glow-danger {
        box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
        animation: pulseDanger 1.8s infinite;
    }
    @keyframes pulseDanger {
        0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
        70% { box-shadow: 0 0 0 8px rgba(220, 53, 69, 0); }
        100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
    }
</style>

<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-1 text-dark fw-bold"><i class="fa-solid fa-user-tie text-primary me-2"></i>Vendor Enrollment Requests</h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/home">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/approval-requests">Approvals</a></li>
                <li class="breadcrumb-item active">Vendor Enrollment Requests</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $base_url ?>/con-vendor-create" class="btn btn-primary btn-sm px-3 shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Add New Vendor
        </a>
        <a href="<?= $base_url ?>/con-vendors" class="btn btn-outline-secondary btn-sm px-3">
            <i class="bi bi-list-ul me-1"></i> Vendor Directory
        </a>
    </div>
</div>

<!-- KPI Summary Cards -->
<div class="row  mb-4">
    <div class="col-xl-3 col-md-6">
        <a href="<?= $base_url ?>/vendor-enrollment-requests?tab=action_required" class="enrollment-kpi-card <?= ($currentTab === 'action_required') ? 'border-danger bg-light' : '' ?>">
            <div class="enrollment-kpi-icon bg-danger-subtle text-danger <?= ($counts['actionable'] > 0) ? 'pulse-glow-danger' : '' ?>">
                <i class="bi bi-exclamation-octagon-fill"></i>
            </div>
            <div>
                <div class="text-muted small fw-bold text-uppercase">Awaiting My Action</div>
                <div class="fs-4 fw-bold text-danger"><?= $counts['actionable'] ?></div>
            </div>
        </a>
    </div>
    <div class="col-xl-3 col-md-6">
        <a href="<?= $base_url ?>/vendor-enrollment-requests?tab=pending" class="enrollment-kpi-card <?= ($currentTab === 'pending') ? 'border-warning bg-light' : '' ?>">
            <div class="enrollment-kpi-icon bg-warning-subtle text-warning">
                <i class="bi bi-clock-history"></i>
            </div>
            <div>
                <div class="text-muted small fw-bold text-uppercase">In Progress / Pending</div>
                <div class="fs-4 fw-bold text-warning"><?= $counts['pending'] ?></div>
            </div>
        </a>
    </div>
    <div class="col-xl-3 col-md-6">
        <a href="<?= $base_url ?>/vendor-enrollment-requests?tab=approved" class="enrollment-kpi-card <?= ($currentTab === 'approved') ? 'border-success bg-light' : '' ?>">
            <div class="enrollment-kpi-icon bg-success-subtle text-success">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div>
                <div class="text-muted small fw-bold text-uppercase">Approved & Active</div>
                <div class="fs-4 fw-bold text-success"><?= $counts['approved'] ?></div>
            </div>
        </a>
    </div>
    <div class="col-xl-3 col-md-6">
        <a href="<?= $base_url ?>/vendor-enrollment-requests?tab=rejected" class="enrollment-kpi-card <?= ($currentTab === 'rejected') ? 'border-secondary bg-light' : '' ?>">
            <div class="enrollment-kpi-icon bg-secondary-subtle text-secondary">
                <i class="bi bi-x-circle-fill"></i>
            </div>
            <div>
                <div class="text-muted small fw-bold text-uppercase">Rejected Requests</div>
                <div class="fs-4 fw-bold text-secondary"><?= $counts['rejected'] ?></div>
            </div>
        </a>
    </div>
</div>

<!-- Main Table Card -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-bottom">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <!-- Tabs -->
            <ul class="nav nav-pills gap-2 mb-0">
                <li class="nav-item">
                    <a class="nav-link py-1 px-3 <?= ($currentTab === 'action_required') ? 'active bg-danger' : 'text-dark' ?>" 
                       href="<?= $base_url ?>/vendor-enrollment-requests?tab=action_required">
                        <i class="bi bi-bell me-1"></i> Awaiting My Action
                        <?php if ($counts['actionable'] > 0): ?>
                            <span class="badge bg-white text-danger rounded-pill ms-1"><?= $counts['actionable'] ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-1 px-3 <?= ($currentTab === 'pending') ? 'active' : 'text-dark' ?>" 
                       href="<?= $base_url ?>/vendor-enrollment-requests?tab=pending">
                        In Progress (<?= $counts['pending'] ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-1 px-3 <?= ($currentTab === 'approved') ? 'active' : 'text-dark' ?>" 
                       href="<?= $base_url ?>/vendor-enrollment-requests?tab=approved">
                        Approved (<?= $counts['approved'] ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-1 px-3 <?= ($currentTab === 'rejected') ? 'active' : 'text-dark' ?>" 
                       href="<?= $base_url ?>/vendor-enrollment-requests?tab=rejected">
                        Rejected (<?= $counts['rejected'] ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-1 px-3 <?= ($currentTab === 'all') ? 'active' : 'text-dark' ?>" 
                       href="<?= $base_url ?>/vendor-enrollment-requests?tab=all">
                        All Records (<?= $counts['total'] ?>)
                    </a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-3 flex-wrap">
                <!-- Per page selector -->
                <div class="d-flex align-items-center gap-1">
                    <label class="small text-muted text-nowrap mb-0">Show:</label>
                    <select class="form-select form-select-sm" style="width: auto;" onchange="window.location.href=this.value;">
                        <?php
                        $buildUrlV = function($pp) use ($base_url, $currentTab, $search) {
                            $u = "{$base_url}/vendor-enrollment-requests?tab=" . urlencode($currentTab);
                            if (!empty($search)) $u .= "&search=" . urlencode($search);
                            $u .= "&per_page=" . urlencode($pp);
                            return $u;
                        };
                        ?>
                        <option value="<?= $buildUrlV('50') ?>" <?= ($perPage === '50') ? 'selected' : '' ?>>50 / page</option>
                        <option value="<?= $buildUrlV('100') ?>" <?= ($perPage === '100') ? 'selected' : '' ?>>100 / page</option>
                        <option value="<?= $buildUrlV('all') ?>" <?= ($perPage === 'all') ? 'selected' : '' ?>>View All (<?= $totalRecords ?>)</option>
                    </select>
                </div>

                <!-- Search Form -->
                <form action="<?= $base_url ?>/vendor-enrollment-requests" method="GET" class="d-flex gap-2 mb-0">
                    <input type="hidden" name="tab" value="<?= htmlspecialchars($currentTab) ?>">
                    <?php if ($perPage !== '50'): ?>
                        <input type="hidden" name="per_page" value="<?= htmlspecialchars($perPage) ?>">
                    <?php endif; ?>
                    <div class="input-group input-group-sm" style="width: 220px;">
                        <input type="text" name="search" class="form-control" placeholder="Search vendor..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                    <?php if (!empty($search)): ?>
                        <a href="<?= $base_url ?>/vendor-enrollment-requests?tab=<?= urlencode($currentTab) ?><?= ($perPage !== '50') ? '&per_page=' . urlencode($perPage) : '' ?>" class="btn btn-sm btn-outline-danger">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 custom-table">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 50px;">#</th>
                        <th>Vendor & Company</th>
                        <th>Contact Information</th>
                        <th>Submitted By</th>
                        <th>Approval Flow Stepper</th>
                        <th>Current Stage</th>
                        <th>Status</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($requests)): ?>
                        <?php foreach ($requests as $idx => $r): ?>
                            <?php
                            $step = $r['current_step'] ?? 'dept_head';
                            $status = $r['status'] ?? 'pending_approval';
                            $isAct = !empty($r['is_actionable']);
                            $hasCompany = !empty(trim($r['company_name'] ?? ''));
                            $displayVendorName = $hasCompany ? trim($r['company_name']) : $r['name'];
                            ?>
                            <tr class="<?= $isAct ? 'table-warning-subtle' : '' ?>">
                                <td class="ps-3 fw-bold text-muted"><?= ($offset ?? 0) + $idx + 1 ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($displayVendorName) ?></div>
                                    <?php if ($hasCompany && !empty($r['name'])): ?>
                                        <div class="small text-muted"><i class="bi bi-person me-1"></i><?= htmlspecialchars($r['name']) ?></div>
                                    <?php endif; ?>
                                    <div class="small text-muted">
                                        ID: #<?= $r['id'] ?>
                                        <?php if (!empty($r['nid_file'])): ?>
                                            &bull; <a href="<?= $base_url ?>/<?= htmlspecialchars($r['nid_file']) ?>" target="_blank" class="text-success text-decoration-none"><i class="bi bi-file-earmark-check me-1"></i>NID</a>
                                        <?php endif; ?>
                                        <?php if (!empty($r['trade_license_file'])): ?>
                                            &bull; <a href="<?= $base_url ?>/<?= htmlspecialchars($r['trade_license_file']) ?>" target="_blank" class="text-success text-decoration-none"><i class="bi bi-file-earmark-check me-1"></i>Trade License</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($r['phone'])): ?>
                                        <div><i class="bi bi-telephone me-1 text-muted"></i><?= htmlspecialchars($r['phone']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($r['email'])): ?>
                                        <div class="small text-muted"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($r['email']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($r['address'])): ?>
                                        <div class="small text-truncate text-muted" style="max-width: 200px;" title="<?= htmlspecialchars($r['address']) ?>"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($r['address']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($r['created_by_name'] ?? 'System') ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($r['created_by_role_name'] ?? 'User') ?> &bull; <?= htmlspecialchars($r['created_by_dept_name'] ?? 'Construction') ?></div>
                                    <div class="small text-muted"><i class="bi bi-calendar-event me-1"></i><?= date('d M Y, h:i A', strtotime($r['created_at'])) ?></div>
                                </td>
                                <td>
                                    <!-- Stepper 5-Step Pipeline -->
                                    <div class="stepper-pipeline">
                                        <!-- Step 1: Dept Head -->
                                        <?php
                                        $s1Bg = ($r['dept_head_status'] === 'approved') ? 'bg-success text-white' : (($r['dept_head_status'] === 'rejected') ? 'bg-danger text-white' : (($r['dept_head_status'] === 'skipped') ? 'bg-secondary text-white' : (($step === 'dept_head') ? 'bg-warning text-dark ring' : 'bg-light text-muted border')));
                                        $s1Icon = ($r['dept_head_status'] === 'approved') ? '✓' : (($r['dept_head_status'] === 'rejected') ? '✕' : (($r['dept_head_status'] === 'skipped') ? '—' : '1'));
                                        ?>
                                        <div class="stepper-pip <?= $s1Bg ?>" title="1. Dept Head: <?= ucfirst($r['dept_head_status']) ?>"><?= $s1Icon ?></div>
                                        <div class="stepper-line"></div>

                                        <!-- Step 2: Purchase Head -->
                                        <?php
                                        $s2Bg = ($r['purchase_head_status'] === 'approved') ? 'bg-success text-white' : (($r['purchase_head_status'] === 'rejected') ? 'bg-danger text-white' : (($r['purchase_head_status'] === 'skipped') ? 'bg-secondary text-white' : (($step === 'purchase_head') ? 'bg-warning text-dark ring' : 'bg-light text-muted border')));
                                        $s2Icon = ($r['purchase_head_status'] === 'approved') ? '✓' : (($r['purchase_head_status'] === 'rejected') ? '✕' : (($r['purchase_head_status'] === 'skipped') ? '—' : '2'));
                                        ?>
                                        <div class="stepper-pip <?= $s2Bg ?>" title="2. Purchase Head: <?= ucfirst($r['purchase_head_status']) ?>"><?= $s2Icon ?></div>
                                        <div class="stepper-line"></div>

                                        <!-- Step 3: Admin & Operation Head -->
                                        <?php
                                        $s3Bg = ($r['role_20_status'] === 'approved') ? 'bg-success text-white' : (($r['role_20_status'] === 'rejected') ? 'bg-danger text-white' : (($r['role_20_status'] === 'skipped') ? 'bg-secondary text-white' : (($step === 'role_20') ? 'bg-warning text-dark ring' : 'bg-light text-muted border')));
                                        $s3Icon = ($r['role_20_status'] === 'approved') ? '✓' : (($r['role_20_status'] === 'rejected') ? '✕' : (($r['role_20_status'] === 'skipped') ? '—' : '3'));
                                        ?>
                                        <div class="stepper-pip <?= $s3Bg ?>" title="3. Admin & Operation Head: <?= ucfirst($r['role_20_status']) ?>"><?= $s3Icon ?></div>
                                        <div class="stepper-line"></div>

                                        <!-- Step 4: Board of Director -->
                                        <?php
                                        $s4Bg = ($r['role_38_status'] === 'approved') ? 'bg-success text-white' : (($r['role_38_status'] === 'rejected') ? 'bg-danger text-white' : (($r['role_38_status'] === 'skipped') ? 'bg-secondary text-white' : (($step === 'role_38') ? 'bg-warning text-dark ring' : 'bg-light text-muted border')));
                                        $s4Icon = ($r['role_38_status'] === 'approved') ? '✓' : (($r['role_38_status'] === 'rejected') ? '✕' : (($r['role_38_status'] === 'skipped') ? '—' : '4'));
                                        ?>
                                        <div class="stepper-pip <?= $s4Bg ?>" title="4. Board of Director: <?= ucfirst($r['role_38_status']) ?>"><?= $s4Icon ?></div>
                                        <div class="stepper-line"></div>

                                        <!-- Step 5: Management -->
                                        <?php
                                        $s5Bg = ($r['management_status'] === 'approved') ? 'bg-success text-white' : (($r['management_status'] === 'rejected') ? 'bg-danger text-white' : (($step === 'management') ? 'bg-warning text-dark ring' : 'bg-light text-muted border'));
                                        $s5Icon = ($r['management_status'] === 'approved') ? '✓' : (($r['management_status'] === 'rejected') ? '✕' : '5');
                                        ?>
                                        <div class="stepper-pip <?= $s5Bg ?>" title="5. Management: <?= ucfirst($r['management_status']) ?>"><?= $s5Icon ?></div>
                                    </div>
                                    <div class="small text-muted mt-1" style="font-size: 11px;">
                                        1.Dept Head &bull; 2.Purchase &bull; 3.Operation Head &bull; 4.Director &bull; 5.Mgmt
                                    </div>
                                </td>
                                <td>
                                    <?php if ($status === 'pending_approval'): ?>
                                        <?php if ($step === 'dept_head'): ?>
                                            <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Dept Head Review</span>
                                            <div class="small text-muted mt-1" style="font-size: 11px;">
                                                <span class="text-secondary fw-semibold">Pending with:</span><br>
                                                <i class="bi bi-person me-1"></i><?= !empty($r['dept_head_name']) ? htmlspecialchars($r['dept_head_name']) : 'Department Head' ?>
                                            </div>
                                        <?php elseif ($step === 'purchase_head'): ?>
                                            <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Purchase Head Review</span>
                                            <div class="small text-muted mt-1" style="font-size: 11px;">
                                                <span class="text-secondary fw-semibold">Pending with:</span><br>
                                                <i class="bi bi-person-badge me-1"></i>Purchase Team Leader
                                            </div>
                                        <?php elseif ($step === 'role_20'): ?>
                                            <span class="badge bg-info text-dark"><i class="bi bi-clock me-1"></i>Operation Head Review</span>
                                            <div class="small text-muted mt-1" style="font-size: 11px;">
                                                <span class="text-secondary fw-semibold">Pending with:</span><br>
                                                <i class="bi bi-person-badge me-1"></i>Admin &amp; Operation Head
                                            </div>
                                        <?php elseif ($step === 'role_38'): ?>
                                            <span class="badge bg-primary"><i class="bi bi-clock me-1"></i>Director Review</span>
                                            <div class="small text-muted mt-1" style="font-size: 11px;">
                                                <span class="text-secondary fw-semibold">Pending with:</span><br>
                                                <i class="bi bi-person-badge me-1"></i>Board of Director
                                            </div>
                                        <?php elseif ($step === 'management'): ?>
                                            <span class="badge bg-danger text-white"><i class="bi bi-hourglass-split me-1"></i>Management Review</span>
                                            <div class="small text-muted mt-1" style="font-size: 11px;">
                                                <span class="text-secondary fw-semibold">Pending with:</span><br>
                                                <i class="bi bi-people me-1"></i>Management
                                            </div>
                                        <?php endif; ?>
                                    <?php elseif ($status === 'active'): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Approved &amp; Active</span>
                                    <?php elseif ($status === 'rejected'): ?>
                                        <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Rejected</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($status === 'pending_approval'): ?>
                                        <span class="badge bg-warning-subtle text-warning border border-warning">Pending</span>
                                    <?php elseif ($status === 'active'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success">Active</span>
                                    <?php elseif ($status === 'rejected'): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger">Rejected</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= htmlspecialchars(ucfirst($status)) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3" style="white-space: nowrap;">
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-action-review" 
                                            data-id="<?= $r['id'] ?>" data-name="<?= htmlspecialchars($displayVendorName) ?>" title="Review & View Details">
                                        <i class="bi bi-eye me-1"></i> Review
                                    </button>

                                    <?php if ($isAct): ?>
                                        <button type="button" class="btn btn-sm btn-success ms-1 btn-quick-approve"
                                                data-id="<?= $r['id'] ?>" data-name="<?= htmlspecialchars($displayVendorName) ?>" title="Quick Approve">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger ms-1 btn-quick-reject"
                                                data-id="<?= $r['id'] ?>" data-name="<?= htmlspecialchars($displayVendorName) ?>" title="Reject">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                    <h5>No enrollment requests found</h5>
                                    <p class="small mb-0">There are currently no requests in this tab.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if (!empty($totalRecords) && $totalRecords > 0): ?>
            <div class="card-footer bg-white py-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="small text-muted">
                    Showing <strong><?= min(($offset ?? 0) + 1, $totalRecords) ?></strong> to <strong><?= min(($offset ?? 0) + count($requests), $totalRecords) ?></strong> of <strong><?= $totalRecords ?></strong> vendors
                </div>
                <div>
                    <?php
                    $pUrlV = $base_url . "/vendor-enrollment-requests?tab=" . urlencode($currentTab);
                    if (!empty($search)) $pUrlV .= "&search=" . urlencode($search);
                    if ($perPage !== '50') $pUrlV .= "&per_page=" . urlencode($perPage);
                    echo paginateLinksSecondary($pUrlV, $currentPage, $totalPages);
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Review & Action Modal -->
<div class="modal fade" id="enrollmentModal" tabindex="-1" aria-labelledby="enrollmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fw-bold" id="enrollmentModalLabel">
                    <i class="bi bi-shield-check me-2"></i>Vendor Enrollment Review & Approval
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="modalLoaderArea">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="mt-2 text-muted">Loading enrollment details...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div class="modal fade" id="rejectReasonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= $base_url ?>/enrollment-request/action" method="POST" id="rejectionForm">
                <input type="hidden" name="type" value="vendor">
                <input type="hidden" name="id" id="rejectTargetId" value="">
                <input type="hidden" name="action" value="reject">

                <div class="modal-header bg-danger text-white py-3">
                    <h5 class="modal-title fw-bold"><i class="bi bi-x-circle me-2"></i>Reject Vendor Enrollment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted">Please provide the reason for rejecting <strong id="rejectTargetName">this vendor</strong>. The reason will be logged and visible in the audit trail.</p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="note" class="form-control" rows="3" required placeholder="Specify why this vendor enrollment is rejected..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm px-3 fw-bold">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalEl = document.getElementById('enrollmentModal');
    const bsModal = new bootstrap.Modal(modalEl);
    const loaderArea = document.getElementById('modalLoaderArea');

    // Review & View Details
    document.querySelectorAll('.btn-action-review').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            loadDetails(id);
        });
    });

    // Quick Approve
    document.querySelectorAll('.btn-quick-approve').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            if (confirm(`Are you sure you want to approve the enrollment request for "${name}"?`)) {
                submitAction(id, 'approve', '');
            }
        });
    });

    // Quick Reject
    document.querySelectorAll('.btn-quick-reject').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            openRejectModal(id, name);
        });
    });

    function openRejectModal(id, name) {
        document.getElementById('rejectTargetId').value = id;
        document.getElementById('rejectTargetName').innerText = name;
        new bootstrap.Modal(document.getElementById('rejectReasonModal')).show();
    }

    function loadDetails(id) {
        bsModal.show();
        loaderArea.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="mt-2 text-muted">Loading vendor enrollment details...</div>
            </div>
        `;

        fetch(`<?= $base_url ?>/api/enrollment-request/details?type=vendor&id=${id}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    loaderArea.innerHTML = `<div class="alert alert-danger">${data.message || 'Failed to load details.'}</div>`;
                    return;
                }
                renderModalContent(data.data, data.logs);
            })
            .catch(err => {
                loaderArea.innerHTML = `<div class="alert alert-danger">Error: ${err.message}</div>`;
            });
    }

    function renderModalContent(v, logs) {
        const isAct = v.is_actionable;
        const currentStep = v.current_step;

        const getStepBadge = (st) => {
            if (st === 'approved') return '<span class="badge bg-success"><i class="bi bi-check me-1"></i>Approved</span>';
            if (st === 'rejected') return '<span class="badge bg-danger"><i class="bi bi-x me-1"></i>Rejected</span>';
            if (st === 'skipped') return '<span class="badge bg-secondary">Skipped</span>';
            return '<span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Pending</span>';
        };

        const getStepDotBg = (st, isCurrent) => {
            if (st === 'approved') return 'bg-success';
            if (st === 'rejected') return 'bg-danger';
            if (st === 'skipped') return 'bg-secondary';
            if (isCurrent) return 'bg-warning text-dark';
            return 'bg-light border text-muted';
        };

        let html = `
            <div class="row g-4">
                <!-- Left Column: Details -->
                <div class="col-lg-6">
                    <div class="card border border-light-subtle shadow-sm h-100">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-card-text me-1 text-primary"></i>Vendor Information</h6>
                        </div>
                        <div class="card-body p-3">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="text-muted" style="width: 150px;">Business Name:</td>
                                    <td class="fw-bold fs-6">${escapeHtml((v.company_name && v.company_name.trim()) ? v.company_name : v.name)}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Contact Person:</td>
                                    <td class="fw-semibold">${escapeHtml(v.name || '—')}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Phone:</td>
                                    <td>${escapeHtml(v.phone || '—')}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Email:</td>
                                    <td>${escapeHtml(v.email || '—')}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Address:</td>
                                    <td>${escapeHtml(v.address || '—')}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">NID:</td>
                                    <td>
                                        ${escapeHtml(v.nid_number || '—')}
                                        ${v.nid_file ? `<a href="<?= $base_url ?>/${escapeHtml(v.nid_file)}" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2 ms-2" style="font-size: 11px;"><i class="bi bi-file-earmark-text me-1"></i>View NID Doc</a>` : '<span class="badge bg-secondary ms-1">No Doc</span>'}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Trade License:</td>
                                    <td>
                                        ${escapeHtml(v.trade_license_number || '—')}
                                        ${v.trade_license_file ? `<a href="<?= $base_url ?>/${escapeHtml(v.trade_license_file)}" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2 ms-2" style="font-size: 11px;"><i class="bi bi-file-earmark-text me-1"></i>View Trade License</a>` : '<span class="badge bg-secondary ms-1">No Doc</span>'}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Current Status:</td>
                                    <td>
                                        <span class="badge ${v.status === 'active' ? 'bg-success' : (v.status === 'rejected' ? 'bg-danger' : 'bg-warning text-dark')}">
                                            ${v.status.toUpperCase()}
                                        </span>
                                    </td>
                                </tr>
                            </table>

                            <hr class="my-3">

                            <div class="small">
                                <div class="text-muted fw-bold mb-1"><i class="bi bi-person me-1"></i>Submitted By:</div>
                                <div class="fw-bold">${escapeHtml(v.created_by_name || 'System')}</div>
                                <div class="text-muted">${escapeHtml(v.created_by_role_name || '')} &bull; ${escapeHtml(v.created_by_dept_name || '')}</div>
                                <div class="text-muted">${v.created_at}</div>
                            </div>

                            ${v.rejection_reason ? `
                                <div class="alert alert-danger mt-3 py-2 px-3">
                                    <strong class="d-block"><i class="bi bi-exclamation-triangle-fill me-1"></i>Rejection Reason:</strong>
                                    ${escapeHtml(v.rejection_reason)}
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>

                <!-- Right Column: 5-Stage Approval Pipeline -->
                <div class="col-lg-6">
                    <div class="card border border-light-subtle shadow-sm h-100">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-diagram-3 me-1 text-primary"></i>Multi-Stage Approval Pipeline</h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="timeline-vertical">

                                <!-- Stage 1: Department Head -->
                                <div class="timeline-item">
                                    <div class="timeline-dot ${getStepDotBg(v.dept_head_status, currentStep === 'dept_head')}">
                                        ${v.dept_head_status === 'approved' ? '✓' : (v.dept_head_status === 'rejected' ? '✕' : (v.dept_head_status === 'skipped' ? '—' : '1'))}
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong class="text-dark">1. Department Head / Reporting Boss</strong>
                                        ${getStepBadge(v.dept_head_status)}
                                    </div>
                                    <div class="small text-muted">
                                        ${v.dept_head_status === 'skipped' ? '<span class="text-info">Auto-skipped (Creator is Dept Head)</span>' : ''}
                                        ${v.dept_head_status === 'approved' ? `Approved by: <strong>${escapeHtml(v.dept_head_by_name || 'Approver')}</strong> &bull; ${v.dept_head_at || ''}` : ''}
                                        ${v.dept_head_status === 'pending' ? (v.dept_head_name ? `Awaiting: <strong>${escapeHtml(v.dept_head_name)}</strong>` : 'Awaiting Dept Head') : ''}
                                        ${v.dept_head_note ? `<div class="mt-1 text-secondary fst-italic">"${escapeHtml(v.dept_head_note)}"</div>` : ''}
                                    </div>
                                </div>

                                <!-- Stage 2: Purchase Head -->
                                <div class="timeline-item">
                                    <div class="timeline-dot ${getStepDotBg(v.purchase_head_status, currentStep === 'purchase_head')}">
                                        ${v.purchase_head_status === 'approved' ? '✓' : (v.purchase_head_status === 'rejected' ? '✕' : (v.purchase_head_status === 'skipped' ? '—' : '2'))}
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong class="text-dark">2. Purchase Head</strong>
                                        ${getStepBadge(v.purchase_head_status)}
                                    </div>
                                    <div class="small text-muted">
                                        ${v.purchase_head_status === 'approved' ? `Approved by: <strong>${escapeHtml(v.purchase_head_by_name || 'Purchase Head')}</strong> &bull; ${v.purchase_head_at || ''}` : ''}
                                        ${v.purchase_head_status === 'pending' ? 'Awaiting Purchase Head approval' : ''}
                                        ${v.purchase_head_status === 'skipped' ? '<span class="text-info">Auto-skipped (Creator is Purchase Head)</span>' : ''}
                                        ${v.purchase_head_note ? `<div class="mt-1 text-secondary fst-italic">"${escapeHtml(v.purchase_head_note)}"</div>` : ''}
                                    </div>
                                </div>

                                <!-- Stage 3: Admin & Operation Head -->
                                <div class="timeline-item">
                                    <div class="timeline-dot ${getStepDotBg(v.role_20_status, currentStep === 'role_20')}">
                                        ${v.role_20_status === 'approved' ? '✓' : (v.role_20_status === 'rejected' ? '✕' : (v.role_20_status === 'skipped' ? '—' : '3'))}
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong class="text-dark">3. Admin & Operation Head</strong>
                                        ${getStepBadge(v.role_20_status)}
                                    </div>
                                    <div class="small text-muted">
                                        ${v.role_20_status === 'approved' ? `Approved by: <strong>${escapeHtml(v.role_20_by_name || 'Admin & Operation Head')}</strong> &bull; ${v.role_20_at || ''}` : ''}
                                        ${v.role_20_status === 'pending' ? 'Awaiting Admin & Operation Head approval' : ''}
                                        ${v.role_20_note ? `<div class="mt-1 text-secondary fst-italic">"${escapeHtml(v.role_20_note)}"</div>` : ''}
                                    </div>
                                </div>

                                <!-- Stage 4: Board of Director -->
                                <div class="timeline-item">
                                    <div class="timeline-dot ${getStepDotBg(v.role_38_status, currentStep === 'role_38')}">
                                        ${v.role_38_status === 'approved' ? '✓' : (v.role_38_status === 'rejected' ? '✕' : (v.role_38_status === 'skipped' ? '—' : '4'))}
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong class="text-dark">4. Board of Director</strong>
                                        ${getStepBadge(v.role_38_status)}
                                    </div>
                                    <div class="small text-muted">
                                        ${v.role_38_status === 'approved' ? `Approved by: <strong>${escapeHtml(v.role_38_by_name || 'Board of Director')}</strong> &bull; ${v.role_38_at || ''}` : ''}
                                        ${v.role_38_status === 'pending' ? 'Awaiting Board of Director approval' : ''}
                                        ${v.role_38_note ? `<div class="mt-1 text-secondary fst-italic">"${escapeHtml(v.role_38_note)}"</div>` : ''}
                                    </div>
                                </div>

                                <!-- Stage 5: Management -->
                                <div class="timeline-item mb-0">
                                    <div class="timeline-dot ${getStepDotBg(v.management_status, currentStep === 'management')}">
                                        ${v.management_status === 'approved' ? '✓' : (v.management_status === 'rejected' ? '✕' : '5')}
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong class="text-dark">5. Management</strong>
                                        ${getStepBadge(v.management_status)}
                                    </div>
                                    <div class="small text-muted">
                                        ${v.management_status === 'approved' ? `Approved by: <strong>${escapeHtml(v.management_by_name || 'Management')}</strong> &bull; ${v.management_at || ''}` : ''}
                                        ${v.management_status === 'pending' ? 'Awaiting final management sign-off' : ''}
                                        ${v.management_note ? `<div class="mt-1 text-secondary fst-italic">"${escapeHtml(v.management_note)}"</div>` : ''}
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Section (if user is actionable) -->
            ${isAct ? `
                <div class="card border border-warning shadow-sm mt-4 bg-light">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-2 mb-2 text-dark">
                            <i class="bi bi-shield-lock-fill text-warning fs-5"></i>
                            <h6 class="mb-0 fw-bold">Action Required: Your approval is required for this stage!</h6>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Remarks / Approval Note (Optional):</label>
                            <input type="text" id="modalApprovalNote" class="form-control form-control-sm" placeholder="Enter remarks...">
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-danger btn-sm px-3" onclick="triggerReject(${v.id}, '${escapeHtml(v.name)}')">
                                <i class="bi bi-x-circle me-1"></i> Reject
                            </button>
                            <button type="button" class="btn btn-success btn-sm px-4 fw-bold" onclick="triggerApprove(${v.id})">
                                <i class="bi bi-check-circle me-1"></i> Approve Stage
                            </button>
                        </div>
                    </div>
                </div>
            ` : ''}

            <!-- Audit Trail Logs -->
            ${logs && logs.length > 0 ? `
                <div class="mt-4">
                    <h6 class="fw-bold mb-2 small text-uppercase text-muted"><i class="bi bi-journal-text me-1"></i>Audit Trail Log:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered bg-white mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Step</th>
                                    <th>Action</th>
                                    <th>Actioned By</th>
                                    <th>Date & Time</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${logs.map(l => `
                                    <tr>
                                        <td>${escapeHtml(l.step_name)}</td>
                                        <td><span class="badge ${l.status === 'approved' ? 'bg-success' : 'bg-danger'}">${l.status}</span></td>
                                        <td>${escapeHtml(l.user_name || 'System')} <span class="text-muted">(${escapeHtml(l.role_name || '')})</span></td>
                                        <td>${l.actioned_at}</td>
                                        <td>${escapeHtml(l.remarks || '—')}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            ` : ''}
        `;

        loaderArea.innerHTML = html;
    }

    window.triggerApprove = function(id) {
        const note = document.getElementById('modalApprovalNote') ? document.getElementById('modalApprovalNote').value : '';
        submitAction(id, 'approve', note);
    };

    window.triggerReject = function(id, name) {
        bsModal.hide();
        openRejectModal(id, name);
    };

    function submitAction(id, action, note) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= $base_url ?>/enrollment-request/action';

        const fields = {
            type: 'vendor',
            id: id,
            action: action,
            note: note
        };

        for (const k in fields) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = k;
            input.value = fields[k];
            form.appendChild(input);
        }

        document.body.appendChild(form);
        form.submit();
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"']/g, function(m) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[m];
        });
    }
});
</script>

