<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-purchase-requests">Purchase Requests</a></li>
                <li class="breadcrumb-item active">PR-<?= $pr['id'] ?></li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $base_url ?>/con-stocks" target="_blank" class="btn btn-info btn-sm text-white"><i class="fal fa-boxes me-1"></i> Show Stock</a>
        <a href="<?= $base_url ?>/con-purchase-requests" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<section class="section">
    <div class="row justify-content-center">
        <div class="col-lg-11">
            <div class="card__wrapper shadow-sm mb-4">
                <div class="card__body p-4">

                    <!-- Header Info -->
                    <div class="row mb-4 g-3">
                        <div class="col-md-3">
                            <label class="small text-uppercase text-muted d-block">PR Number</label>
                            <span class="fw-bold fs-5">PR-<?= $pr['id'] ?></span>
                        </div>
                        <div class="col-md-3">
                            <label class="small text-uppercase text-muted d-block">Project</label>
                            <span class="fw-bold"><?= htmlspecialchars($pr['project_name']) ?></span>
                        </div>
                        <div class="col-md-2">
                            <label class="small text-uppercase text-muted d-block">Status</label>
                            <?php
                            $badge = match($pr['status']) {
                                'Approved'  => 'bg-success',
                                'PO Issued' => 'bg-primary',
                                'Rejected'  => 'bg-danger',
                                'Submitted' => 'bg-info',
                                default     => 'bg-warning text-dark',
                            };
                            ?>
                            <span class="badge <?= $badge ?> px-3"><?= $pr['status'] ?></span>
                        </div>
                        <div class="col-md-2">
                            <label class="small text-uppercase text-muted d-block">Created By</label>
                            <span class="fw-bold"><?= htmlspecialchars($pr['created_by_name'] ?? '—') ?></span>
                        </div>
                        <div class="col-md-2">
                            <label class="small text-uppercase text-muted d-block">Date</label>
                            <span class="fw-bold"><?= date('d M Y', strtotime($pr['created_at'])) ?></span>
                        </div>
                    </div>

                    <!-- Approval Status Panel -->
                    <div class="rounded p-3 mb-3" style="background:<?= in_array($pr['status'], ['Approved','PO Issued']) ? 'rgba(16,185,129,.06)' : 'rgba(245,158,11,.05)' ?>;border:1px solid <?= in_array($pr['status'], ['Approved','PO Issued']) ? 'rgba(16,185,129,.2)' : 'rgba(245,158,11,.18)' ?>">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-3">
                                <div style="width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;
                                    background:<?= in_array($pr['status'], ['Approved','PO Issued']) ? 'rgba(16,185,129,.15)' : 'rgba(245,158,11,.12)' ?>;
                                    color:<?= in_array($pr['status'], ['Approved','PO Issued']) ? '#059669' : '#d97706' ?>">
                                    <i class="fal <?= in_array($pr['status'], ['Approved','PO Issued']) ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                                </div>
                                <div>
                                    <div style="font-size:12px;font-weight:700">Approval Status</div>
                                    <?php if (in_array($pr['status'], ['Approved','PO Issued']) && !empty($pr['approved_by_name'])): ?>
                                        <div style="font-size:11px;font-weight:700;color:#059669">
                                            <?= htmlspecialchars($pr['approved_by_name']) ?>
                                        </div>
                                        <?php if (!empty($pr['approved_by_role'])): ?>
                                            <div style="font-size:10px;color:#6b7280;font-style:italic"><?= htmlspecialchars($pr['approved_by_role']) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($pr['approved_at'])): ?>
                                            <div style="font-size:10px;color:var(--text-muted,#6b7280)">
                                                <?= date('d M Y, h:i A', strtotime($pr['approved_at'])) ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php elseif ($pr['status'] === 'Rejected' && !empty($pr['approved_by_name'])): ?>
                                        <div style="font-size:11px;font-weight:700;color:#dc2626">
                                            Rejected by: <?= htmlspecialchars($pr['approved_by_name']) ?>
                                        </div>
                                    <?php else: ?>
                                        <div style="font-size:11px;color:var(--text-muted,#6b7280)">Awaiting approval</div>
                                    <?php endif; ?>

                                    <?php if (!empty($pr['management_note'])): ?>
                                        <div style="font-size:11px; margin-top:5px;" class="text-muted">
                                            <strong>Note:</strong> <?= htmlspecialchars($pr['management_note']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span style="font-size:10px;font-weight:700;padding:4px 12px;border-radius:20px;
                                background:<?= in_array($pr['status'], ['Approved','PO Issued']) ? 'rgba(16,185,129,.12)' : ($pr['status'] === 'Rejected' ? 'rgba(239,68,68,.12)' : 'rgba(245,158,11,.1)') ?>;
                                color:<?= in_array($pr['status'], ['Approved','PO Issued']) ? '#059669' : ($pr['status'] === 'Rejected' ? '#dc2626' : '#d97706') ?>">
                                <?= in_array($pr['status'], ['Approved','PO Issued']) ? '✓ Approved' : ($pr['status'] === 'Rejected' ? '✗ Rejected' : 'Pending') ?>
                            </span>
                        </div>
                    </div>

                    <?php if (!empty($pr['notes'])): ?>
                    <div class="alert alert-light border-start border-primary border-3 mb-3">
                        <strong>Notes:</strong> <?= htmlspecialchars($pr['notes']) ?>
                    </div>
                    <?php endif; ?>

                    <!-- Items Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Material</th>
                                    <th>Unit</th>
                                    <th class="text-end">Requested Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $i => $item): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($item['material_name']) ?></td>
                                    <td><?= htmlspecialchars($item['unit']) ?></td>
                                    <td class="text-end"><?= number_format($item['quantity'], 3) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Status Actions -->
                    <?php
                    $sessionRole = (int)($_SESSION['user']['role_id'] ?? 0);
                    $canApprove  = in_array($sessionRole, [3, 15, 22]); // Super Admin, Project Manager, PM Head
                    ?>
                    <?php if ($pr['status'] === 'Draft'): ?>
                        <div class="mt-4 p-3 bg-light rounded d-flex justify-content-between align-items-center">
                            <span class="text-muted small">
                                <i class="bi bi-info-circle me-1"></i> This PR is a Draft. Submit or approve it.
                            </span>
                            <div class="d-flex gap-2">
                                <?php if ($canApprove || true): // creator can submit ?>
                                <form action="<?= $base_url ?>/con-purchase-request-approve/<?= $pr['id'] ?>" method="POST" class="d-inline">
                                    <input type="hidden" name="status" value="Submitted">
                                    <button type="submit" class="btn btn-info btn-sm">
                                        <i class="bi bi-send me-1"></i> Submit
                                    </button>
                                </form>
                                <?php endif; ?>
                                <?php if ($canApprove): ?>
                                <form action="<?= $base_url ?>/con-purchase-request-approve/<?= $pr['id'] ?>" method="POST" class="d-inline">
                                    <input type="hidden" name="status" value="Approved">
                                    <div class="mb-2">
                                        <textarea name="approval_note" class="form-control" rows="2" placeholder="Approval Note"></textarea>
                                    </div>
                                    <button type="button" onclick="confirmAction(this, 'Approve this Purchase Request?')" class="btn btn-success btn-sm w-100">
                                        <i class="bi bi-check-circle me-1"></i> Approve
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php elseif ($pr['status'] === 'Submitted'): ?>
                        <div class="mt-4 p-3 bg-light rounded d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <span class="text-muted small w-100 mb-2">
                                <i class="bi bi-clock-history me-1"></i>
                                <?php if ($canApprove): ?>
                                    This PR is Submitted. Please review and Approve or Reject.
                                <?php else: ?>
                                    Awaiting approval by Purchase Team Leader.
                                <?php endif; ?>
                            </span>
                            <?php if ($canApprove): ?>
                            <form action="<?= $base_url ?>/con-purchase-request-approve/<?= $pr['id'] ?>" method="POST" class="d-inline w-100">
                                <div class="mb-2">
                                    <textarea name="approval_note" class="form-control" rows="2" placeholder="Approval / Rejection Note"></textarea>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" name="status" value="Approved" onclick="confirmAction(this, 'Approve this PR?')" class="btn btn-success btn-sm">
                                        <i class="bi bi-check-circle me-1"></i> Approve
                                    </button>
                                    <button type="button" name="status" value="Rejected" onclick="confirmAction(this, 'Reject this PR?')" class="btn btn-danger btn-sm">
                                        <i class="bi bi-x-circle me-1"></i> Reject
                                    </button>
                                </div>
                            </form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Procurement Pipeline Actions -->
            <?php if ($pr['status'] === 'Approved' || $pr['status'] === 'PO Issued'): ?>
            <div class="card__wrapper shadow-sm">
                <div class="card__header">
                    <h6 class="card__title mb-0"><i class="bi bi-diagram-3 me-2"></i>Procurement Pipeline</h6>
                </div>
                <div class="card__body p-4">
                    <div class="row">
                        <!-- Step 1: Quotations -->
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center h-100">
                                <div class="mb-2">
                                    <i class="bi bi-receipt fs-3 text-info"></i>
                                </div>
                                <h6>Step 1: Vendor Quotations</h6>
                                <p class="small text-muted mb-2">
                                    <?= count($quotations) ?> quotation(s) collected
                                </p>
                                <?php if ($pr['status'] === 'Approved'): ?>
                                    <a href="<?= $base_url ?>/con-quotation-create/<?= $pr['id'] ?>"
                                       class="btn btn-outline-info btn-sm">
                                        <i class="bi bi-plus me-1"></i> Add Quotation
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-success"><i class="bi bi-check"></i> Done</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Step 2: CST Compare -->
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center h-100">
                                <div class="mb-2">
                                    <i class="bi bi-layout-three-columns fs-3 text-warning"></i>
                                </div>
                                <h6>Step 2: CST Comparison</h6>
                                <p class="small text-muted mb-2">
                                    <?= $hasCST ? 'Winner selected' : 'Compare & select lowest' ?>
                                </p>
                                <?php if (count($quotations) >= 2): ?>
                                    <a href="<?= $base_url ?>/con-quotation-compare/<?= $pr['id'] ?>"
                                       class="btn btn-outline-warning btn-sm">
                                        <i class="bi bi-eye me-1"></i> <?= $hasCST ? 'View CST' : 'Compare Now' ?>
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Need ≥ 2 quotes</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Step 3: Generate PO -->
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center h-100">
                                <div class="mb-2">
                                    <i class="bi bi-file-earmark-check fs-3 text-success"></i>
                                </div>
                                <h6>Step 3: Purchase Order</h6>
                                <p class="small text-muted mb-2">
                                    <?= ($pr['status'] === 'PO Issued') ? 'PO Generated' : 'Auto-generate from CST' ?>
                                </p>
                                <?php if ($hasCST && $pr['status'] !== 'PO Issued'): ?>
                                    <form action="<?= $base_url ?>/con-purchase-order-generate/<?= $pr['id'] ?>" method="POST" class="d-inline">
                                        <button type="button" onclick="confirmAction(this, 'Generate Purchase Order from CST selections?')" class="btn btn-outline-success btn-sm">
                                            <i class="bi bi-lightning me-1"></i> Generate PO
                                        </button>
                                    </form>
                                <?php elseif ($pr['status'] === 'PO Issued'): ?>
                                    <span class="badge bg-success"><i class="bi bi-check"></i> PO Created</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Complete CST first</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmAction(btn, msg) {
    Swal.fire({
        title: 'Are you sure?',
        text: msg,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, proceed!'
    }).then((result) => {
        if (result.isConfirmed) {
            if (btn.name) {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = btn.name;
                hidden.value = btn.value;
                btn.form.appendChild(hidden);
            }
            btn.form.submit();
        }
    });
}
</script>
