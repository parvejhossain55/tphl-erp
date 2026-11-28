<?php
$base_url = $base_url ?? ($GLOBALS['base_url'] ?? '/TPHL-ERP');
$userRole = $userRole ?? ($_SESSION['user']['role_id'] ?? 0);
$offset   = $offset ?? 0;

$statusMap = [
    'draft'          => 'warning',
    'submitted'      => 'info',
    'verified'       => 'primary',
    'qc_approved'    => 'secondary',
    'approved'       => 'success',
    'rejected'       => 'danger',
    'paid'           => 'secondary',
    'partially_paid' => 'info',
    'fully_paid'     => 'secondary',
];
?>
<?php if (!empty($bills)): ?>
    <?php
    $cbEditMap = [];
    if (function_exists('db')) {
        $cbDb = db();
        if ($cbDb) {
            require_once __DIR__ . '/../../../../services/RequisitionRevisionService.php';
            $cbEditMap = (new RequisitionRevisionService($cbDb))->getLastEditMap('contractor_bill_requests');
        }
    }
    ?>
    <?php foreach ($bills as $i => $b): ?>
        <?php
        $badge = $statusMap[$b['status']] ?? 'secondary';
        $statusLabel = str_replace('_', ' ', ucfirst($b['status']));
        $serialNo = $offset + $i + 1;
        ?>
        <tr>
            <td>
                <a href="<?= $base_url ?>/con-contractor-bill/<?= $b['id'] ?>" class="fw-bold text-primary text-decoration-none" title="View Bill Details">
                    CB-<?= $b['id'] ?>
                </a>
                <div class="text-muted" style="font-size: 11px;">#<?= $serialNo ?></div>
            </td>
            <td>
                <span class="fw-semibold text-dark"><?= htmlspecialchars($b['project_name'] ?? '-') ?></span>
                <?php if (!empty($b['target_id'])): ?>
                    <br>
                    <small class="text-success fw-semibold">
                        <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($b['target_phase_name'] ?? '') ?> 
                        <span class="text-muted">(Week <?= htmlspecialchars($b['target_week_number'] ?? '') ?>)</span>
                    </small>
                <?php endif; ?>
            </td>
            <td>
                <span class="fw-semibold text-dark"><?= htmlspecialchars($b['operator_name'] ?? '-') ?></span><br>
                <?php if (!empty($b['operator_trade'])): ?>
                    <span class="badge bg-light text-primary border border-primary-subtle mt-1" style="font-size: 11px;">
                        <i class="bi bi-tools me-1"></i><?= htmlspecialchars(ucwords(str_replace('_', ' ', $b['operator_trade']))) ?>
                    </span>
                <?php elseif (!empty($b['operator_role'])): ?>
                    <small class="text-muted"><?= htmlspecialchars($b['operator_role']) ?></small>
                <?php endif; ?>
            </td>
            <td>
                <span class="badge bg-<?= ($b['bill_type'] === 'Advance') ? 'warning' : (($b['bill_type'] === 'Retention') ? 'info' : 'primary') ?>-transparent">
                    <?= htmlspecialchars($b['bill_type'] ?? 'Progress') ?>
                </span>
            </td>
            <td><?= !empty($b['bill_date']) ? date('d M Y', strtotime($b['bill_date'])) : '-' ?></td>
            <td class="fw-semibold text-end">৳ <?= number_format($b['bill_amount'] ?? 0, 2) ?></td>
            <td>
                <span class="badge bg-<?= $badge ?>"><?= $statusLabel ?></span>
                <?php if ($b['status'] === 'submitted'): ?>
                    <div class="text-warning fw-semibold mt-1" style="font-size: 10px;"><i class="bi bi-hourglass-split"></i> Pending PM</div>
                <?php elseif ($b['status'] === 'verified' && empty($b['purchase_head_approved_by_id'])): ?>
                    <div class="text-warning fw-semibold mt-1" style="font-size: 10px;"><i class="bi bi-hourglass-split"></i> Pending Purchase Head</div>
                    <?php if (!empty($b['purchase_team_leader_by_name'])): ?>
                        <div class="text-info" style="font-size: 9.5px;"><i class="bi bi-check2"></i> TL: <?= htmlspecialchars($b['purchase_team_leader_by_name']) ?></div>
                    <?php endif; ?>
                <?php elseif ($b['status'] === 'verified' && !empty($b['purchase_head_approved_by_id'])): ?>
                    <div class="text-primary fw-semibold mt-1" style="font-size: 10px;"><i class="bi bi-shield-check"></i> Pending QC Audit</div>
                <?php endif; ?>
            </td>
            <td>
                <div class="fw-medium text-dark"><?= htmlspecialchars($b['created_by_name'] ?? '-') ?></div>
                <?php if (!empty($b['created_at'])): ?>
                    <div class="text-muted" style="font-size: 10.5px;"><?= date('d M Y, h:i A', strtotime($b['created_at'])) ?></div>
                <?php endif; ?>
                <?php $cbEdit = $cbEditMap[(int)($b['id'] ?? 0)] ?? null; ?>
                <?php if (!empty($cbEdit)): ?>
                    <div class="mt-1" style="font-size:10px; color:#b45309; line-height:1.25;" title="<?= htmlspecialchars($cbEdit['change_summary'] ?? 'Edited') ?>">
                        <i class="bi bi-pencil-square me-1"></i>Edited: <strong><?= htmlspecialchars($cbEdit['edited_by_name']) ?></strong>
                        <?php if (!empty($cbEdit['created_at'])): ?>
                            <div class="text-muted" style="font-size:9.5px;"><?= date('d M Y, h:i A', strtotime($cbEdit['created_at'])) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </td>
            <td>
                <?php $hasApprover = false; ?>
                <div class="d-flex flex-column gap-1" style="font-size: 11px; min-width: 140px;">
                    <?php if (!empty($b['verified_by_name'])): 
                        $hasApprover = true;
                        $pmDate = !empty($b['verified_at']) ? date('d M Y', strtotime($b['verified_at'])) : '';
                    ?>
                        <div class="p-1 rounded bg-light border border-info-subtle mb-1">
                            <div class="d-flex align-items-center justify-content-between gap-1">
                                <span class="badge bg-info text-white px-1" style="font-size: 8.5px; font-weight: 600;">PM</span>
                                <span class="fw-semibold text-dark text-truncate" title="<?= htmlspecialchars($b['verified_by_name']) ?>"><?= htmlspecialchars($b['verified_by_name']) ?></span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between text-muted mt-1" style="font-size: 9.5px;">
                                <span class="text-truncate"><?= htmlspecialchars($b['verified_by_role'] ?? 'PM Head') ?></span>
                                <?php if ($pmDate): ?>
                                    <span class="text-nowrap ms-1 text-primary fw-medium"><?= $pmDate ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($b['purchase_head_approved_by_name'])): 
                        $hasApprover = true;
                        $phDate = !empty($b['purchase_head_approved_at']) ? date('d M Y', strtotime($b['purchase_head_approved_at'])) : '';
                    ?>
                        <div class="p-1 rounded bg-light border border-warning-subtle mb-1">
                            <div class="d-flex align-items-center justify-content-between gap-1">
                                <span class="badge bg-warning text-dark px-1" style="font-size: 8.5px; font-weight: 600;">PH</span>
                                <span class="fw-semibold text-dark text-truncate" title="<?= htmlspecialchars($b['purchase_head_approved_by_name']) ?>"><?= htmlspecialchars($b['purchase_head_approved_by_name']) ?></span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between text-muted mt-1" style="font-size: 9.5px;">
                                <span class="text-truncate"><?= htmlspecialchars($b['purchase_head_approved_by_role'] ?? 'Purchase Head') ?></span>
                                <?php if ($phDate): ?>
                                    <span class="text-nowrap ms-1 text-primary fw-medium"><?= $phDate ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($b['qc_approved_by_name'])): 
                        $hasApprover = true;
                        $qcDate = !empty($b['qc_approved_at']) ? date('d M Y', strtotime($b['qc_approved_at'])) : '';
                    ?>
                        <div class="p-1 rounded bg-light border border-primary-subtle mb-1">
                            <div class="d-flex align-items-center justify-content-between gap-1">
                                <span class="badge bg-primary text-white px-1" style="font-size: 8.5px; font-weight: 600;">QC</span>
                                <span class="fw-semibold text-dark text-truncate" title="<?= htmlspecialchars($b['qc_approved_by_name']) ?>"><?= htmlspecialchars($b['qc_approved_by_name']) ?></span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between text-muted mt-1" style="font-size: 9.5px;">
                                <span class="text-truncate"><?= htmlspecialchars($b['qc_approved_by_role'] ?? 'QC & Audit') ?></span>
                                <?php if ($qcDate): ?>
                                    <span class="text-nowrap ms-1 text-primary fw-medium"><?= $qcDate ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($b['approved_by_name'])): 
                        $hasApprover = true;
                        $mgmtDate = !empty($b['approved_at']) ? date('d M Y', strtotime($b['approved_at'])) : (!empty($b['updated_at']) && in_array($b['status'], ['approved', 'paid', 'partially_paid', 'fully_paid']) ? date('d M Y', strtotime($b['updated_at'])) : '');
                    ?>
                        <div class="p-1 rounded bg-light border border-success-subtle">
                            <div class="d-flex align-items-center justify-content-between gap-1">
                                <span class="badge bg-success text-white px-1" style="font-size: 8.5px; font-weight: 600;">Mgmt</span>
                                <span class="fw-semibold text-success text-truncate" title="<?= htmlspecialchars($b['approved_by_name']) ?>"><?= htmlspecialchars($b['approved_by_name']) ?></span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between text-muted mt-1" style="font-size: 9.5px;">
                                <span class="text-truncate"><?= htmlspecialchars($b['approved_by_role'] ?? 'Management') ?></span>
                                <?php if ($mgmtDate): ?>
                                    <span class="text-nowrap ms-1 text-success fw-medium"><?= $mgmtDate ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!$hasApprover): ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </div>
            </td>
            <td>
                <?php if (!empty($b['payment_by_name'])): ?>
                    <span class="text-primary fw-semibold"><?= htmlspecialchars($b['payment_by_name']) ?></span>
                <?php else: ?>
                    <span class="text-muted">—</span>
                <?php endif; ?>
            </td>
            <td>
                <div class="d-flex align-items-center gap-1">
                    <a href="<?= $base_url ?>/con-contractor-bill/<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary" title="View Bill">
                        <i class="bi bi-eye"></i>
                    </a>
                    <?php if (in_array($b['status'], ['approved', 'paid', 'partially_paid', 'fully_paid'])): ?>
                        <a href="<?= $base_url ?>/print-debit-voucher?module=contractor_bill&id=<?= $b['id'] ?><?= in_array($b['status'], ['paid', 'partially_paid', 'fully_paid']) ? '' : '&is_approval=1' ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="<?= in_array($b['status'], ['paid', 'partially_paid', 'fully_paid']) ? 'Payment / Debit Voucher' : 'Approval Voucher' ?>">
                            <i class="bi bi-printer"></i>
                        </a>
                    <?php endif; ?>
                    <?php if (in_array($b['status'], ['paid', 'partially_paid', 'fully_paid']) || !empty($b['payment_date']) || !empty($b['payment_by_name'])): ?>
                        <a href="<?= $base_url ?>/expense-adjustments/create?parent_type=contractor&cashout_request_id=<?= $b['id'] ?>&project_id=<?= $b['project_id'] ?>" class="btn btn-sm btn-outline-warning" title="Settlement / Adjustment">
                            <i class="bi bi-sliders"></i>
                        </a>
                    <?php endif; ?>
                    <?php if (!in_array($b['status'], ['approved', 'paid', 'partially_paid', 'fully_paid'])): ?>
                        <?php if (hasPermission('Contractor Bill') || in_array($userRole, [1, 3, 33, 17, 23, 31])): ?>
                            <a href="<?= $base_url ?>/con-contractor-bill-edit/<?= $b['id'] ?>" class="btn btn-sm btn-outline-info" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="<?= $base_url ?>/con-contractor-bill-delete/<?= $b['id'] ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this bill?');">
                                <i class="bi bi-trash"></i>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr>
        <td colspan="11" class="text-center text-muted py-4">
            <i class="bi bi-inbox fs-2 d-block mb-1 text-muted"></i>
            No contractor bills found matching your filter criteria.
            <?php if (hasPermission('Contractor Bill') || in_array($userRole, [1, 3, 33, 17, 23, 31, 20, 32, 12])): ?>
                <div class="mt-2">
                    <a href="<?= $base_url ?>/con-contractor-bill-create" class="btn btn-sm btn-primary">Create New Bill</a>
                </div>
            <?php endif; ?>
        </td>
    </tr>
<?php endif; ?>

