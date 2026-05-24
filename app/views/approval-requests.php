<?php require_once 'layouts/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-0">Pending Approval Requests</h4>
            <p class="text-muted small mb-0">List of all requests pending your approval</p>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card__wrapper">
                <div class="card__body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= $_SESSION['success']; ?>
                            <?php unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= $_SESSION['error']; ?>
                            <?php unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Department & Title</th>
                                    <th>Requested By & Date</th>
                                    <th>Details</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($pendingRequests)): ?>
                                    <?php foreach ($pendingRequests as $req): ?>
                                        <tr>
                                            <td>
                                                <h6 class="mb-1"><?= htmlspecialchars($req['department']) ?></h6>
                                                <span class="text-muted small"><?= htmlspecialchars($req['title']) ?></span>
                                            </td>
                                            <td>
                                                <?php if (!empty($req['context']['requester'])): ?>
                                                    <span class="d-block fw-semibold"><?= htmlspecialchars($req['context']['requester']['user_name'] ?? '') ?></span>
                                                    <span class="small text-muted"><?= htmlspecialchars($req['context']['requester']['designation'] ?? '') ?></span>
                                                <?php else: ?>
                                                    <span class="d-block fw-semibold">System</span>
                                                <?php endif; ?>
                                                <div class="small text-muted mt-1"><i class="fal fa-calendar me-1"></i> <?= date('d M Y', strtotime($req['created_at'])) ?></div>
                                            </td>
                                            <td>
                                                <?php if ($req['source_table'] === 'employee_leaves'): 
                                                    $meta = json_decode($req['meta_data'], true);
                                                    $requested_days = $meta['requested_days'] ?? 0;
                                                ?>
                                                    <strong>Requested Days:</strong> <?= $requested_days ?>
                                                <?php elseif (!empty($req['request_amount'])): ?>
                                                    <strong>Requested Amount:</strong> ৳ <?= format_bdt($req['request_amount']) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning text-dark"><?= ucfirst($req['status']) ?></span>
                                            </td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#viewContextModal<?= $req['id'] ?>" title="View Details">
                                                    <i class="fal fa-eye"></i>
                                                </button>
                                                <?php if ($req['request_type'] !== 'manpower_requisition'): ?>
                                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#partialApproveModal<?= $req['id'] ?>" title="Partial Approve">
                                                        <i class="fal fa-edit"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <form method="POST" action="<?= $GLOBALS['base_url'] ?>/approve-request/<?= $req['id'] ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to approve this request?');">
                                                    <button class="btn btn-sm btn-success" title="Full Approve">
                                                        <i class="fal fa-check"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" action="<?= $GLOBALS['base_url'] ?>/reject-request/<?= $req['id'] ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to reject this request?');">
                                                    <button class="btn btn-sm btn-danger" title="Reject">
                                                        <i class="fal fa-times"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>

                                        <!-- Partial Modal -->
                                        <div class="modal fade" id="partialApproveModal<?= $req['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h6 class="modal-title">Partial Approval</h6>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body text-start">
                                                        <form method="POST" action="<?= $GLOBALS['base_url'] ?>/partial-approve-request/<?= $req['id'] ?>">
                                                            <?php if ($req['source_table'] === 'employee_leaves'): 
                                                                $meta = json_decode($req['meta_data'], true);
                                                                $requested_days = $meta['requested_days'] ?? 0;
                                                            ?>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Requested Days</label>
                                                                    <input type="number" class="form-control" value="<?= $requested_days ?>" disabled>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Approve Days</label>
                                                                    <input type="number" class="form-control" name="approved_days" placeholder="Enter approved days" required max="<?= $requested_days ?>">
                                                                </div>
                                                            <?php else: ?>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Requested Amount</label>
                                                                    <input type="text" class="form-control" value="৳ <?= number_format($req['request_amount'], 2) ?>" disabled>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Approve Amount</label>
                                                                    <input type="number" class="form-control" name="approved_amount" placeholder="Enter approved amount" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Note</label>
                                                                    <textarea class="form-control" name="approval_note" placeholder="Enter note here" rows="4"></textarea>
                                                                </div>
                                                            <?php endif; ?>

                                                            <div class="modal-footer px-0 pb-0">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-success">Approve</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- View Context Modal -->
                                        <div class="modal fade" id="viewContextModal<?= $req['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                                <div class="modal-content text-start">
                                                    <div class="modal-header">
                                                        <h6 class="modal-title">Request Details</h6>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body p-4 bg-light">
                                                        <?php renderApprovalContext($req); ?>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <i class="fal fa-folder-open fa-3x mb-3 d-block"></i>
                                            No pending requests available.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'layouts/footer.php'; ?>
