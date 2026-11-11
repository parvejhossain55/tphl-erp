
    <div class="pagetitle d-flex justify-content-between align-items-center">
        <div>
            <h3>Project Other Costs</h3>
           
            </nav>
        </div>
        <div>
            <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#createRequestModal">
                <i class="bi bi-plus-circle me-1"></i> Create Request
            </button>
        </div>
    </div>

    <section class="section">
        <!-- Executive Management Approval Schedule Notice -->
        <?php include __DIR__ . '/../layouts/management_approval_notice.php'; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php 
        $userId = $_SESSION['user']['id'] ?? ($userId ?? null);
        $isApprover = in_array($roleId, [1, 3, 6, 9, 13, 15, 17, 20, 22, 24, 25, 30, 32, 33]) || !empty($isReportingBoss);
        $showApprovalTab = !empty($allApprovalRequests) || $isApprover;
        $activeTab = $_GET['tab'] ?? (($isApprover || !empty($pendingApprovalRequests)) ? 'pending-approval' : 'my-requests');
        ?>

        <!-- Nav tabs -->
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $activeTab === 'my-requests' ? 'active' : '' ?>" id="my-requests-tab" data-bs-toggle="tab" data-bs-target="#my-requests" type="button" role="tab" aria-controls="my-requests" aria-selected="<?= $activeTab === 'my-requests' ? 'true' : 'false' ?>">My Requests</button>
            </li>
            <?php if($isApprover || !empty($pendingApprovalRequests)): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $activeTab === 'pending-approval' ? 'active' : '' ?>" id="pending-approval-tab" data-bs-toggle="tab" data-bs-target="#pending-approval" type="button" role="tab" aria-controls="pending-approval" aria-selected="<?= $activeTab === 'pending-approval' ? 'true' : 'false' ?>">Pending My Approval</button>
            </li>
            <?php endif; ?>
            <?php if($showApprovalTab): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $activeTab === 'all-approval' ? 'active' : '' ?>" id="all-approval-tab" data-bs-toggle="tab" data-bs-target="#all-approval" type="button" role="tab" aria-controls="all-approval" aria-selected="<?= $activeTab === 'all-approval' ? 'true' : 'false' ?>">All Processed / Requests</button>
            </li>
            <?php endif; ?>
        </ul>

        <!-- Tab panes -->
        <div class="tab-content mt-3" id="myTabContent">
            
            <!-- My Requests Tab -->
            <div class="tab-pane fade <?= $activeTab === 'my-requests' ? 'show active' : '' ?>" id="my-requests" role="tabpanel" aria-labelledby="my-requests-tab">
                <div class="card shadow-sm">
                    <div class="card-body mt-3 table-responsive">
                        <table class="table table-hover table-striped custom-table mb-0 datatable">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Project</th>
                                    <th>Cost Type</th>
                                    <th>Amount</th>
                                    <th>Attachment</th>
                                    <th>Status</th>
                                    <th>Created By & Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($myRequests)): ?>
                                    <?php foreach ($myRequests as $index => $row): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= htmlspecialchars($row['project_name']) ?></td>
                                            <td><?= htmlspecialchars(ProjectOtherCost::getCostTypeLabel($row['rcost_type'])) ?></td>
                                            <td>
                                                <div><?= number_format($row['request_amount'], 2) ?></div>
                                                <?php if (!empty($row['approved_amount']) && $row['approved_amount'] != $row['request_amount']): ?>
                                                    <small class="text-success d-block">Appr: <?= number_format($row['approved_amount'], 2) ?></small>
                                                <?php endif; ?>
                                                <?php if ($row['status'] === 'paid' && !empty($row['payment_amount'])): ?>
                                                    <small class="text-primary d-block font-monospace fw-bold">Paid: <?= number_format($row['payment_amount'], 2) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($row['attachment'])): 
                                                    $rowAtt = getProjectOtherCostFileDetails($row['attachment']);
                                                ?>
                                                    <?php if ($rowAtt['exists']): ?>
                                                        <a href="<?= $rowAtt['url'] ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="<?= htmlspecialchars($rowAtt['filename']) ?>"><i class="bi bi-paperclip"></i> View</a>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark border border-warning" title="File Missing: <?= htmlspecialchars($rowAtt['filename']) ?>"><i class="fa-solid fa-triangle-exclamation me-1"></i> Missing</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($row['dept_head_status'] === 'pending'): ?>
                                                    <span class="badge bg-warning text-dark mb-1">
                                                        <i class="bi bi-clock me-1"></i>Dept Head Pending
                                                    </span>
                                                    <?php if (!empty($row['designated_dh_name'])): ?>
                                                        <div class="text-muted" style="font-size: 0.75rem;">
                                                            Approver: <strong><?= htmlspecialchars($row['designated_dh_name']) ?></strong>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php elseif ($row['status'] == 'pending' && ($row['purchase_head_status'] ?? 'not_required') === 'pending'): ?>
                                                    <span class="badge bg-warning text-dark mb-1">
                                                        <i class="bi bi-clock me-1"></i>Purchase Head Pending
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-<?= $row['status'] == 'pending' ? 'warning' : ($row['status'] == 'paid' ? 'success' : ($row['status'] == 'rejected' ? 'danger' : 'info')) ?>">
                                                        <?= ucfirst(str_replace('_', ' ', $row['status'])) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($row['created_by_name'] ?? 'N/A') ?></div>
                                                <div class="text-muted" style="font-size: 0.85rem;"><?= date('d M Y h:i A', strtotime($row['created_at'])) ?></div>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="<?= $GLOBALS['base_url'] ?>/project-other-costs/review/<?= $row['id'] ?>" class="btn btn-sm btn-info shadow-sm" title="View Status">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <?php if ($row['status'] === 'paid'): ?>
                                                        <a href="<?= $GLOBALS['base_url'] ?>/expense-adjustments/create?parent_type=other_cost&cashout_request_id=<?= $row['id'] ?>&project_id=<?= $row['project_id'] ?>" class="btn btn-sm btn-outline-warning shadow-sm" title="Settlement / Adjustment">
                                                            <i class="bi bi-sliders"></i>
                                                        </a>
                                                        <a href="<?= $GLOBALS['base_url'] ?>/print-debit-voucher?module=project_other_costs&id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary shadow-sm" title="Print Debit Voucher">
                                                            <i class="bi bi-printer"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if (in_array($row['status'], ['pending', 'qc_approved'])): ?>
                                                        <a href="<?= $GLOBALS['base_url'] ?>/project-other-costs/delete/<?= $row['id'] ?>" class="btn btn-sm btn-danger shadow-sm" onclick="return confirm('Are you sure you want to delete this request?');" title="Delete">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pending Approval Requests Tab -->
            <?php if($isApprover || !empty($pendingApprovalRequests)): ?>
            <div class="tab-pane fade <?= $activeTab === 'pending-approval' ? 'show active' : '' ?>" id="pending-approval" role="tabpanel" aria-labelledby="pending-approval-tab">
                <div class="card shadow-sm">
                    <div class="card-body mt-3 table-responsive">
                        <table class="table table-hover table-striped custom-table mb-0 datatable">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Project</th>
                                    <th>Cost Type</th>
                                    <th>Request Amount</th>
                                    <th>Approved Amount</th>
                                    <th>Attachment</th>
                                    <th>Status</th>
                                    <th>Created By & Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($pendingApprovalRequests)): ?>
                                    <?php foreach ($pendingApprovalRequests as $index => $row): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= htmlspecialchars($row['project_name']) ?></td>
                                            <td><?= htmlspecialchars(ProjectOtherCost::getCostTypeLabel($row['rcost_type'])) ?></td>
                                            <td><?= number_format($row['request_amount'], 2) ?></td>
                                            <td><?= !empty($row['approved_amount']) ? number_format($row['approved_amount'], 2) : '-' ?></td>
                                            <td>
                                                <?php if (!empty($row['attachment'])): 
                                                    $rowAtt = getProjectOtherCostFileDetails($row['attachment']);
                                                ?>
                                                    <?php if ($rowAtt['exists']): ?>
                                                        <a href="<?= $rowAtt['url'] ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="<?= htmlspecialchars($rowAtt['filename']) ?>"><i class="bi bi-paperclip"></i> View</a>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark border border-warning" title="File Missing: <?= htmlspecialchars($rowAtt['filename']) ?>"><i class="fa-solid fa-triangle-exclamation me-1"></i> Missing</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($row['status'] == 'pending'): ?>
                                                    <span class="badge bg-warning text-dark mb-1">Pending</span>
                                                <?php elseif ($row['status'] == 'qc_approved'): ?>
                                                    <span class="badge bg-info text-white mb-1">Qc approved</span>
                                                <?php elseif ($row['status'] == 'management_approved'): ?>
                                                    <span class="badge bg-primary text-white mb-1">Management approved</span>
                                                <?php elseif ($row['status'] == 'paid'): ?>
                                                    <span class="badge bg-success mb-1">Paid</span>
                                                <?php elseif ($row['status'] == 'rejected'): ?>
                                                    <span class="badge bg-danger mb-1">Rejected</span>
                                                <?php endif; ?>
                                                
                                                <?php if ($row['dept_head_status'] === 'approved'): ?>
                                                    <div class="text-success" style="font-size: 0.75rem;">
                                                        <i class="bi bi-check-circle"></i> Dept Head Approved
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (($row['purchase_head_status'] ?? '') === 'approved'): ?>
                                                    <div class="text-success" style="font-size: 0.75rem;">
                                                        <i class="bi bi-check-circle"></i> Purchase Head Approved
                                                    </div>
                                                <?php elseif ($row['status'] == 'pending' && $row['dept_head_status'] !== 'pending' && ($row['purchase_head_status'] ?? '') === 'pending'): ?>
                                                    <div class="text-warning" style="font-size: 0.75rem;">
                                                        <i class="bi bi-clock"></i> Pending with Purchase Head
                                                    </div>
                                                <?php elseif (($row['purchase_head_status'] ?? '') === 'rejected'): ?>
                                                    <div class="text-danger" style="font-size: 0.75rem;">
                                                        <i class="bi bi-x-circle"></i> Rejected by Purchase Head
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($row['created_by_name'] ?? 'N/A') ?></div>
                                                <div class="text-muted" style="font-size: 0.85rem;"><?= date('d M Y h:i A', strtotime($row['created_at'])) ?></div>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <?php
                                                     $canApprove = false;
                                                    
                                                    $creatorReportingUserId = (int)($row['creator_reporting_user_id'] ?? 0);
                                                    $isDeptHead = ($row['dept_head_status'] === 'pending' && ($creatorReportingUserId === (int)$userId || $roleId === 1));
                                                    
                                                    if ($isDeptHead) $canApprove = true;
                                                    $phPendingRow = (($row['purchase_head_status'] ?? 'not_required') === 'pending');
                                                    if ($row['status'] == 'pending' && $row['dept_head_status'] !== 'pending' && $phPendingRow && in_array($roleId, [17, 1])) $canApprove = true;
                                                    if ($row['status'] == 'pending' && $row['dept_head_status'] !== 'pending' && !$phPendingRow && in_array($roleId, [13, 24, 25, 1])) $canApprove = true;
                                                    if ($row['status'] == 'qc_approved' && in_array($roleId, [3, 15, 20, 22, 32, 1])) $canApprove = true;
                                                    if ($row['status'] == 'management_approved' && in_array($roleId, [6, 30, 1])) $canApprove = true;
                                                    ?>
                                                    
                                                    <?php if ($canApprove): ?>
                                                        <a href="<?= $GLOBALS['base_url'] ?>/project-other-costs/review/<?= $row['id'] ?>" class="btn btn-sm btn-success rounded-2 px-3 shadow-sm">
                                                            <i class="fa-solid fa-up-right-from-square me-1"></i>Review / Process
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="<?= $GLOBALS['base_url'] ?>/project-other-costs/review/<?= $row['id'] ?>" class="btn btn-sm btn-info rounded-2 px-3 shadow-sm text-white">
                                                            <i class="bi bi-eye me-1"></i>View Module Page
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- All Approval Requests Tab -->
            <?php if($showApprovalTab): ?>
            <div class="tab-pane fade <?= $activeTab === 'all-approval' ? 'show active' : '' ?>" id="all-approval" role="tabpanel" aria-labelledby="all-approval-tab">
                <div class="card shadow-sm">
                    <div class="card-body mt-3 table-responsive">
                        <table class="table table-hover table-striped custom-table mb-0 datatable">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Project</th>
                                    <th>Cost Type</th>
                                    <th>Request Amount</th>
                                    <th>Approved Amount</th>
                                    <th>Attachment</th>
                                    <th>Status</th>
                                    <th>Created By & Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($allApprovalRequests)): ?>
                                    <?php foreach ($allApprovalRequests as $index => $row): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= htmlspecialchars($row['project_name']) ?></td>
                                            <td><?= htmlspecialchars(ProjectOtherCost::getCostTypeLabel($row['rcost_type'])) ?></td>
                                            <td><?= number_format($row['request_amount'], 2) ?></td>
                                            <td><?= !empty($row['approved_amount']) ? number_format($row['approved_amount'], 2) : '-' ?></td>
                                            <td>
                                                <?php if (!empty($row['attachment'])): 
                                                    $rowAtt = getProjectOtherCostFileDetails($row['attachment']);
                                                ?>
                                                    <?php if ($rowAtt['exists']): ?>
                                                        <a href="<?= $rowAtt['url'] ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="<?= htmlspecialchars($rowAtt['filename']) ?>"><i class="bi bi-paperclip"></i> View</a>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark border border-warning" title="File Missing: <?= htmlspecialchars($rowAtt['filename']) ?>"><i class="fa-solid fa-triangle-exclamation me-1"></i> Missing</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($row['status'] == 'pending'): ?>
                                                    <span class="badge bg-warning text-dark mb-1">Pending</span>
                                                <?php elseif ($row['status'] == 'qc_approved'): ?>
                                                    <span class="badge bg-info text-white mb-1">Qc approved</span>
                                                <?php elseif ($row['status'] == 'management_approved'): ?>
                                                    <span class="badge bg-primary text-white mb-1">Management approved</span>
                                                <?php elseif ($row['status'] == 'paid'): ?>
                                                    <span class="badge bg-success mb-1">Paid</span>
                                                <?php elseif ($row['status'] == 'rejected'): ?>
                                                    <span class="badge bg-danger mb-1">Rejected</span>
                                                <?php endif; ?>
                                                
                                                <?php if ($row['dept_head_status'] === 'approved'): ?>
                                                    <div class="text-success" style="font-size: 0.75rem;">
                                                        <i class="bi bi-check-circle"></i> Dept Head Approved
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (($row['purchase_head_status'] ?? '') === 'approved'): ?>
                                                    <div class="text-success" style="font-size: 0.75rem;">
                                                        <i class="bi bi-check-circle"></i> Purchase Head Approved
                                                    </div>
                                                <?php elseif ($row['status'] == 'pending' && $row['dept_head_status'] !== 'pending' && ($row['purchase_head_status'] ?? '') === 'pending'): ?>
                                                    <div class="text-warning" style="font-size: 0.75rem;">
                                                        <i class="bi bi-clock"></i> Pending with Purchase Head
                                                    </div>
                                                <?php elseif (($row['purchase_head_status'] ?? '') === 'rejected'): ?>
                                                    <div class="text-danger" style="font-size: 0.75rem;">
                                                        <i class="bi bi-x-circle"></i> Rejected by Purchase Head
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($row['created_by_name'] ?? 'N/A') ?></div>
                                                <div class="text-muted" style="font-size: 0.85rem;"><?= date('d M Y h:i A', strtotime($row['created_at'])) ?></div>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <?php
                                                     $canApprove = false;
                                                    
                                                    $creatorReportingUserId = (int)($row['creator_reporting_user_id'] ?? 0);
                                                    $isDeptHead = ($row['dept_head_status'] === 'pending' && ($creatorReportingUserId === (int)$userId || $roleId === 1));
                                                    
                                                    if ($isDeptHead) $canApprove = true;
                                                    $phPendingRow = (($row['purchase_head_status'] ?? 'not_required') === 'pending');
                                                    if ($row['status'] == 'pending' && $row['dept_head_status'] !== 'pending' && $phPendingRow && in_array($roleId, [17, 1])) $canApprove = true;
                                                    if ($row['status'] == 'pending' && $row['dept_head_status'] !== 'pending' && !$phPendingRow && in_array($roleId, [13, 24, 25, 1])) $canApprove = true;
                                                    if ($row['status'] == 'qc_approved' && in_array($roleId, [3, 15, 20, 22, 32, 1])) $canApprove = true;
                                                    if ($row['status'] == 'management_approved' && in_array($roleId, [6, 30, 1])) $canApprove = true;
                                                    ?>
                                                    
                                                    <?php if ($canApprove): ?>
                                                        <a href="<?= $GLOBALS['base_url'] ?>/project-other-costs/review/<?= $row['id'] ?>" class="btn btn-sm btn-success rounded-2 px-3 shadow-sm">
                                                            <i class="fa-solid fa-up-right-from-square me-1"></i>Review / Process
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="<?= $GLOBALS['base_url'] ?>/project-other-costs/review/<?= $row['id'] ?>" class="btn btn-sm btn-info rounded-2 px-3 shadow-sm text-white">
                                                            <i class="bi bi-eye me-1"></i>View Module Page
                                                        </a>
                                                    <?php endif; ?>

                                                    <?php if ($row['status'] === 'paid'): ?>
                                                        <a href="<?= $GLOBALS['base_url'] ?>/expense-adjustments/create?parent_type=other_cost&cashout_request_id=<?= $row['id'] ?>&project_id=<?= $row['project_id'] ?>" class="btn btn-sm btn-outline-warning rounded-2 px-2 shadow-sm" title="Settlement / Adjustment">
                                                            <i class="bi bi-sliders me-1"></i>Settlement
                                                        </a>
                                                        <a href="<?= $GLOBALS['base_url'] ?>/print-debit-voucher?module=project_other_costs&id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-2 px-2 shadow-sm" title="Print Debit Voucher">
                                                            <i class="bi bi-printer me-1"></i>Voucher
                                                        </a>
                                                    <?php endif; ?>

                                                    <?php if (in_array($row['status'], ['pending', 'qc_approved']) && ($row['created_by_id'] == $userId || $roleId == 1)): ?>
                                                        <a href="<?= $GLOBALS['base_url'] ?>/project-other-costs/delete/<?= $row['id'] ?>" class="btn btn-sm btn-danger rounded-2 px-3 shadow-sm" onclick="return confirm('Are you sure you want to delete this request?');" title="Delete">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
    </section>

    <!-- Create Request Modal -->
    <div class="modal fade" id="createRequestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form action="<?= $GLOBALS['base_url'] ?>/project-other-costs/store" method="POST" enctype="multipart/form-data">
                <div class="modal-content shadow-lg border-0">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle text-primary me-2"></i>Create Other Cost Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label mb-0 fw-semibold">Project(s) <span class="text-danger">*</span></label>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-link btn-xs p-0 text-decoration-none" id="modalSelectAllProjects" style="font-size: 0.75rem;">Select All</button>
                                        <span class="text-muted" style="font-size: 0.75rem;">|</span>
                                        <button type="button" class="btn btn-link btn-xs p-0 text-decoration-none text-muted" id="modalClearAllProjects" style="font-size: 0.75rem;">Clear</button>
                                    </div>
                                </div>
                                <select name="project_ids[]" id="modalProjectSelect" class="form-select select2" multiple="multiple" required style="width: 100%;" data-placeholder="Select single or multiple projects...">
                                    <?php if (!empty($projects)): ?>
                                        <?php foreach ($projects as $project): ?>
                                            <option value="<?= $project['id'] ?>" data-name="<?= htmlspecialchars($project['name']) ?>"><?= htmlspecialchars($project['name']) ?></option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="">No projects available</option>
                                    <?php endif; ?>
                                </select>
                                <div class="form-text text-muted mt-1" style="font-size: 0.78rem;">
                                    <i class="bi bi-info-circle me-1 text-primary"></i>Select 1 or multiple projects. Total amount will be distributed equally per project.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Cost Type <span class="text-danger">*</span></label>
                                <select name="rcost_type" id="modalCostTypeSelect" class="form-select select2" required style="width: 100%;" data-placeholder="Select or search cost type...">
                                    <option value="">Select or search cost type...</option>
                                    <?php 
                                    $types = $costTypes ?? (class_exists('ProjectOtherCost') ? ProjectOtherCost::getCostTypes() : []);
                                    foreach ($types as $val => $label): 
                                    ?>
                                        <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <?= renderCOADropdown($coaList ?? [], null, 'account_id', false, 'othercostmodal') ?>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Total Request Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text fw-bold bg-light">৳</span>
                                <input type="number" step="0.01" min="0.01" name="request_amount" id="modalRequestAmount" class="form-control" required placeholder="0.00">
                            </div>

                            <!-- Live Project Distribution Preview -->
                            <div id="modalDistributionPreview" class="alert alert-info py-2 px-3 mt-2 mb-0 border-info-subtle rounded-3" style="display: none;">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <span class="extra-small">
                                        <i class="bi bi-calculator me-1 text-primary"></i>
                                        <strong>Cost Distribution:</strong> 
                                        <span id="modalDistPerProject" class="fw-bold text-success fs-14">৳ 0.00</span> / project 
                                        (<span id="modalDistProjectCount" class="fw-semibold">0</span> projects selected)
                                    </span>
                                    <span class="badge bg-primary px-2 py-1" id="modalDistTotalBadge">Total: ৳ 0.00</span>
                                </div>
                                <div class="mt-2 pt-1 border-top border-info-subtle d-flex flex-wrap gap-1" id="modalDistList">
                                    <!-- Dynamic project chips with allocated amounts -->
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Attachment (Optional)</label>
                            <input type="file" name="creator_attachment" class="form-control" accept="image/*,.pdf,.doc,.docx">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Purpose / Description <span class="text-danger">*</span></label>
                            <textarea name="purpose" class="form-control" rows="3" required placeholder="Enter purpose of the cost"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold"><i class="bi bi-check2-circle me-1"></i> Submit Request</button>
                    </div>
                </div>
            </form>
        </div>
    </div>


<!-- DataTables and Multi-Project Distribution Setup -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof $.fn.DataTable !== 'undefined') {
            $('.datatable').DataTable();
        }

        // Live Cost Distribution Calculation Helper
        function updateModalDistribution() {
            const select = $('#modalProjectSelect');
            const selectedOptions = select.find('option:selected');
            const count = selectedOptions.length;
            const totalAmount = parseFloat($('#modalRequestAmount').val()) || 0;
            const previewEl = $('#modalDistributionPreview');

            if (count > 0 && totalAmount > 0) {
                const perProject = (totalAmount / count);
                $('#modalDistPerProject').text('৳ ' + perProject.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('#modalDistProjectCount').text(count);
                $('#modalDistTotalBadge').text('Total: ৳ ' + totalAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));

                let chipsHtml = '';
                selectedOptions.each(function() {
                    const pName = $(this).data('name') || $(this).text();
                    chipsHtml += `<span class="badge bg-white text-dark border extra-small px-2 py-1"><i class="bi bi-building text-primary me-1"></i>${pName}: <strong class="text-success">৳ ${perProject.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong></span>`;
                });
                $('#modalDistList').html(chipsHtml);
                previewEl.slideDown(150);
            } else {
                previewEl.slideUp(150);
            }
        }

        // Bind events for live calculation
        $('#modalProjectSelect').on('change', updateModalDistribution);
        $('#modalRequestAmount').on('input', updateModalDistribution);

        // Select All / Clear buttons
        $('#modalSelectAllProjects').on('click', function(e) {
            e.preventDefault();
            $('#modalProjectSelect option').prop('selected', true);
            $('#modalProjectSelect').trigger('change');
        });

        $('#modalClearAllProjects').on('click', function(e) {
            e.preventDefault();
            $('#modalProjectSelect option').prop('selected', false);
            $('#modalProjectSelect').trigger('change');
        });

        // Re-initialize Select2 properly when modal opens
        $('#createRequestModal').on('shown.bs.modal', function() {
            $('#modalCostTypeSelect').select2({
                dropdownParent: $('#createRequestModal'),
                width: '100%',
                placeholder: 'Select or search cost type...',
                allowClear: true
            });
            $('#modalProjectSelect').select2({
                dropdownParent: $('#createRequestModal'),
                width: '100%',
                placeholder: 'Select single or multiple projects...'
            });
        });
    });
</script>

<style>
    /* Select2 Multi-Select Choice Badges */
    .select2-container--default .select2-selection--multiple {
        background-color: #ffffff !important;
        border: 1px solid #dee2e6 !important;
        border-radius: 6px !important;
        min-height: 40px !important;
        padding: 4px 8px !important;
        display: flex !important;
        align-items: center !important;
        flex-wrap: wrap !important;
        box-sizing: border-box !important;
    }
    .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: #198754 !important;
        box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.15) !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #e8f5e9 !important;
        border: 1px solid #a3cfbb !important;
        border-radius: 6px !important;
        color: #0f5132 !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        padding: 3px 10px 3px 24px !important;
        margin: 2px 4px 2px 0 !important;
        position: relative !important;
        display: inline-flex !important;
        align-items: center !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__display {
        color: #0f5132 !important;
        font-weight: 600 !important;
        padding-left: 2px !important;
        padding-right: 0 !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        position: absolute !important;
        left: 6px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        border: none !important;
        background: transparent !important;
        color: #0f5132 !important;
        font-size: 14px !important;
        font-weight: bold !important;
        line-height: 1 !important;
        padding: 0 !important;
        cursor: pointer !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
        color: #dc3545 !important;
        background: transparent !important;
    }
    .select2-container--default .select2-selection--multiple .select2-search--inline .select2-search__field {
        margin: 0 !important;
        padding: 2px 6px !important;
        height: 28px !important;
        line-height: 28px !important;
        font-size: 13px !important;
        color: #333333 !important;
        width: auto !important;
        min-width: 60px !important;
        resize: none !important;
        border: none !important;
        outline: none !important;
        background: transparent !important;
        box-shadow: none !important;
    }
    [bd-theme="bd-theme-dark"] .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: rgba(25, 135, 84, 0.25) !important;
        border-color: rgba(25, 135, 84, 0.45) !important;
        color: #86efac !important;
    }
    [bd-theme="bd-theme-dark"] .select2-container--default .select2-selection--multiple .select2-selection__choice__display,
    [bd-theme="bd-theme-dark"] .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: #86efac !important;
    }
    /* Single Select (Cost Type & COA) */
    .select2-container--default .select2-selection--single {
        background-color: #ffffff !important;
        border: 1px solid #dee2e6 !important;
        border-radius: 6px !important;
        height: 40px !important;
        padding: 5px 12px !important;
        display: flex !important;
        align-items: center !important;
        box-sizing: border-box !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 26px !important;
        padding-left: 0 !important;
        color: #212529 !important;
        font-size: 14px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 38px !important;
        right: 10px !important;
    }
    .select2-container--default.select2-container--focus .select2-selection--single,
    .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #198754 !important;
        box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.15) !important;
    }

    /* Dark Mode Enhancements for Select2 */
    [bd-theme="bd-theme-dark"] .select2-container--default .select2-selection--single {
        background-color: #1b2430 !important;
        border-color: #334155 !important;
    }
    [bd-theme="bd-theme-dark"] .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #f1f5f9 !important;
    }
    [bd-theme="bd-theme-dark"] .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #94a3b8 !important;
    }
    [bd-theme="bd-theme-dark"] .select2-container--default .select2-selection--single .select2-selection__arrow b {
        border-color: #94a3b8 transparent transparent transparent !important;
    }
    [bd-theme="bd-theme-dark"] .select2-dropdown {
        background-color: #1b2430 !important;
        border-color: #334155 !important;
        color: #f1f5f9 !important;
    }
    [bd-theme="bd-theme-dark"] .select2-dropdown .select2-search__field {
        background-color: #0f172a !important;
        border: 1px solid #334155 !important;
        color: #f1f5f9 !important;
    }
    [bd-theme="bd-theme-dark"] .select2-results__option {
        color: #cbd5e1 !important;
    }
    [bd-theme="bd-theme-dark"] .select2-results__option--highlighted,
    [bd-theme="bd-theme-dark"] .select2-results__option--highlighted[aria-selected] {
        background-color: rgba(25, 135, 84, 0.25) !important;
        color: #34d399 !important;
    }
</style>

