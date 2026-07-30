<?php
$page = 'Manpower Requisition Lists';
$show_breadcrumb = true;
$statusFilter = $statusFilter ?? 'all';
$hrFilter     = $hrFilter ?? 'all';
$counts       = $counts ?? ['total_count' => 0, 'pending_count' => 0, 'approved_count' => 0, 'rejected_count' => 0, 'hr_processing_count' => 0, 'hr_completed_count' => 0, 'hr_not_needed_count' => 0];

$roleId = (int)($_SESSION['user']['role_id'] ?? 0);
$roleName = strtolower($_SESSION['user']['role_name'] ?? '');
$isManagement = in_array($roleId, [1, 3]) || hasPermission("Manpower Requisition");
$isHr = in_array($roleId, [1, 3, 4]) 
        || hasPermission("HRM Management") 
        || hasPermission("Hr Manage")
        || str_contains($roleName, 'hr');
?>

<div class="row">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold text-dark">Manpower Requisition Management</h4>
            <p class="text-muted small mb-0">Track, review, and manage employee recruitment requisitions & hiring processing.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $GLOBALS['base_url'] ?>/create-requisition" class="btn btn-primary shadow-sm" style="background: linear-gradient(135deg, #10b981, #059669); border:none;">
                <i class="fa-light fa-plus me-1"></i> Add Requisition
            </a>
        </div>
    </div>

    <!-- Summary Statistics Cards -->
    <div class="row g-3 mb-4">
        <!-- 1. PENDING APPROVAL CARD (Primary Interactive Card requested by user) -->
        <div class="col-xl-3 col-md-6">
            <a href="<?= $GLOBALS['base_url'] ?>/requisition-lists?status=pending&open_pending=1" class="text-decoration-none">
                <div class="card shadow-sm border-0 h-100 position-relative overflow-hidden" 
                     style="background: linear-gradient(135deg, #fffcf5 0%, #ffffff 100%); border-left: 5px solid #f59e0b !important; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-uppercase fw-semibold text-warning small" style="letter-spacing: 0.05em; font-size: 0.75rem;">
                                    Pending Approvals
                                </span>
                                <h2 class="fw-extrabold my-1 text-dark"><?= (int)($counts['pending_count'] ?? 0) ?></h2>
                                <span class="badge bg-warning text-dark px-2 py-1 rounded-pill" style="font-size: 0.7rem;">
                                    <i class="fa-solid fa-hourglass-half me-1"></i> Awaiting Management
                                </span>
                            </div>
                            <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                 style="width: 52px; height: 52px; background: rgba(245, 158, 11, 0.12); color: #d97706;">
                                <i class="fa-solid fa-user-clock fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- 2. HR HIRING IN PROGRESS CARD -->
        <div class="col-xl-3 col-md-6">
            <a href="<?= $GLOBALS['base_url'] ?>/requisition-lists?status=approved&hr_status=on_processing" class="text-decoration-none">
                <div class="card shadow-sm border-0 h-100 position-relative overflow-hidden" 
                     style="background: linear-gradient(135deg, #f0f9ff 0%, #ffffff 100%); border-left: 5px solid #0284c7 !important; transition: transform 0.2s;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-uppercase fw-semibold text-info small" style="letter-spacing: 0.05em; font-size: 0.75rem;">
                                    HR On Processing
                                </span>
                                <h2 class="fw-extrabold my-1 text-dark"><?= (int)($counts['hr_processing_count'] ?? 0) ?></h2>
                                <span class="badge bg-info text-dark px-2 py-1 rounded-pill" style="font-size: 0.7rem; background-color: #38bdf8 !important;">
                                    <i class="fa-solid fa-spinner fa-spin me-1"></i> Active Recruitment
                                </span>
                            </div>
                            <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                 style="width: 52px; height: 52px; background: rgba(2, 132, 199, 0.12); color: #0284c7;">
                                <i class="fa-solid fa-user-gear fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- 3. HIRING COMPLETED CARD -->
        <div class="col-xl-3 col-md-6">
            <a href="<?= $GLOBALS['base_url'] ?>/requisition-lists?status=approved&hr_status=hiring_completed" class="text-decoration-none">
                <div class="card shadow-sm border-0 h-100 position-relative overflow-hidden" 
                     style="background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%); border-left: 5px solid #10b981 !important; transition: transform 0.2s;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-uppercase fw-semibold text-success small" style="letter-spacing: 0.05em; font-size: 0.75rem;">
                                    Hiring Completed
                                </span>
                                <h2 class="fw-extrabold my-1 text-dark"><?= (int)($counts['hr_completed_count'] ?? 0) ?></h2>
                                <span class="badge bg-success px-2 py-1 rounded-pill" style="font-size: 0.7rem;">
                                    <i class="fa-solid fa-user-check me-1"></i> Positions Filled
                                </span>
                            </div>
                            <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                 style="width: 52px; height: 52px; background: rgba(16, 185, 129, 0.12); color: #059669;">
                                <i class="fa-solid fa-user-check fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- 4. TOTAL REQUISITIONS CARD -->
        <div class="col-xl-3 col-md-6">
            <a href="<?= $GLOBALS['base_url'] ?>/requisition-lists?status=all" class="text-decoration-none">
                <div class="card shadow-sm border-0 h-100 position-relative overflow-hidden" 
                     style="background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%); border-left: 5px solid #64748b !important; transition: transform 0.2s;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-uppercase fw-semibold text-secondary small" style="letter-spacing: 0.05em; font-size: 0.75rem;">
                                    Total Requisitions
                                </span>
                                <h2 class="fw-extrabold my-1 text-dark"><?= (int)($counts['total_count'] ?? 0) ?></h2>
                                <span class="text-muted small" style="font-size: 0.75rem;">All Requisitions</span>
                            </div>
                            <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                 style="width: 52px; height: 52px; background: rgba(100, 116, 139, 0.12); color: #475569;">
                                <i class="fa-solid fa-file-signature fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Filter Pills & Table Card -->
    <div class="card__wrapper border-0 shadow-sm rounded-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between p-3 border-bottom bg-light rounded-top gap-2">
            <div class="btn-group btn-group-sm flex-wrap" role="group">
                <a href="<?= $GLOBALS['base_url'] ?>/requisition-lists?status=all" 
                   class="btn <?= ($statusFilter === 'all' && $hrFilter === 'all') ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    All (<?= (int)($counts['total_count'] ?? 0) ?>)
                </a>
                <a href="<?= $GLOBALS['base_url'] ?>/requisition-lists?status=pending" 
                   class="btn <?= $statusFilter === 'pending' ? 'btn-warning text-dark fw-semibold' : 'btn-outline-secondary' ?>">
                    <i class="fa-solid fa-hourglass-half me-1"></i> Pending Management (<?= (int)($counts['pending_count'] ?? 0) ?>)
                </a>
                <a href="<?= $GLOBALS['base_url'] ?>/requisition-lists?status=approved&hr_status=on_processing" 
                   class="btn <?= ($statusFilter === 'approved' && $hrFilter === 'on_processing') ? 'btn-info text-dark fw-semibold' : 'btn-outline-secondary' ?>">
                    <i class="fa-solid fa-spinner me-1"></i> HR On Processing (<?= (int)($counts['hr_processing_count'] ?? 0) ?>)
                </a>
                <a href="<?= $GLOBALS['base_url'] ?>/requisition-lists?status=approved&hr_status=hiring_completed" 
                   class="btn <?= ($statusFilter === 'approved' && $hrFilter === 'hiring_completed') ? 'btn-success' : 'btn-outline-secondary' ?>">
                    <i class="fa-solid fa-user-check me-1"></i> Hiring Completed (<?= (int)($counts['hr_completed_count'] ?? 0) ?>)
                </a>
                <a href="<?= $GLOBALS['base_url'] ?>/requisition-lists?status=approved&hr_status=not_needed_now" 
                   class="btn <?= ($statusFilter === 'approved' && $hrFilter === 'not_needed_now') ? 'btn-secondary' : 'btn-outline-secondary' ?>">
                    Not Needed Now (<?= (int)($counts['hr_not_needed_count'] ?? 0) ?>)
                </a>
                <a href="<?= $GLOBALS['base_url'] ?>/requisition-lists?status=rejected" 
                   class="btn <?= $statusFilter === 'rejected' ? 'btn-danger' : 'btn-outline-secondary' ?>">
                    Rejected (<?= (int)($counts['rejected_count'] ?? 0) ?>)
                </a>
            </div>
            <span class="text-muted small">Showing <?= count($requisitions) ?> items</span>
        </div>

        <div class="table-responsive p-2">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small text-muted">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Department</th>
                        <th>Requested By</th>
                        <th>Reports To</th>
                        <th>Job Title / Position</th>
                        <th>Target Date</th>
                        <th>Mgmt Status</th>
                        <th>HR Recruitment Status</th>
                        <th class="text-end" style="min-width: 130px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                     <?php if (!empty($requisitions)): ?>
                        <?php foreach ($requisitions as $index => $req): ?>
                        <tr>
                            <td class="fw-bold text-muted"><?= $index + 1 ?></td>
                            <td>
                                <span class="fw-semibold text-dark"><?= esc($req['department_name'] ?? 'General') ?></span>
                            </td>
                            <td><?= esc($req['requested_by_name'] ?? 'N/A') ?></td>
                            <td>
                                <small class="text-muted"><?= esc($req['report_to_name'] ?? 'N/A') ?></small>
                            </td>
                            <td>
                                <strong class="text-primary"><?= esc($req['job_title']) ?></strong>
                                <?php if (!empty($req['no_of_vacancies'])): ?>
                                    <span class="badge bg-light text-dark border ms-1"><?= $req['no_of_vacancies'] ?> Vacancies</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="small"><?= formatDate($req['to_be_filled_before']) ?></span>
                            </td>
                            <td>
                                <?php if ($req['status'] === 'approved'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        <i class="fa-solid fa-circle-check me-1"></i> Approved
                                    </span>
                                <?php elseif ($req['status'] === 'rejected'): ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                        <i class="fa-solid fa-circle-xmark me-1"></i> Rejected
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                        <i class="fa-solid fa-hourglass-half me-1"></i> Pending
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($req['status'] === 'approved'): ?>
                                    <?php 
                                    $hrSt = $req['hr_status'] ?? 'on_processing';
                                    if ($hrSt === 'hiring_completed'): ?>
                                        <span class="badge bg-success text-white px-2 py-1">
                                            <i class="fa-solid fa-user-check me-1"></i> Hiring Completed
                                        </span>
                                    <?php elseif ($hrSt === 'not_needed_now'): ?>
                                        <span class="badge bg-secondary text-white px-2 py-1">
                                            <i class="fa-solid fa-circle-pause me-1"></i> Not Needed Now
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-info text-dark px-2 py-1" style="background-color:#38bdf8 !important;">
                                            <i class="fa-solid fa-spinner me-1"></i> On Processing
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted small">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= $GLOBALS['base_url'] ?>/view-requisition/<?= $req['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fa-light fa-eye me-1"></i> View & Process
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                     <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-folder-open fs-2 mb-2 d-block opacity-50"></i>
                                No manpower requisitions found for this filter.
                            </td>
                        </tr>
                     <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>