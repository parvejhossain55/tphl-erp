<?php
/**
 * Partial: Vendor Payment Request Table Rows
 * Variables expected: $requests, $base_url, $canProcessPayment, $pagination
 */
$startOffset = (int)($pagination['offset'] ?? 0);
?>
<?php if (!empty($requests)): ?>
    <?php foreach ($requests as $index => $req): ?>
        <?php
        $pt = $req['payment_type'] ?? 'Final';
        $ptClass = $pt === 'Advance' ? 'bg-primary' : ($pt === 'Partial' ? 'bg-warning text-dark' : 'bg-success');

        $statusMap = [
            'Draft'          => 'warning text-dark',
            'Submitted'      => 'info text-white',
            'QC Approved'    => 'primary',
            'Approved'       => 'success',
            'Rejected'       => 'danger',
            'Paid'           => 'secondary',
            'Partially_Paid' => 'info text-white',
            'Fully_Paid'     => 'success',
        ];
        $badge = $statusMap[$req['status'] ?? ''] ?? 'secondary';
        $statusLabel = str_replace('_', ' ', $req['status'] ?? 'Draft');

        $alreadyPaid = (float)($req['already_paid'] ?? 0);
        $netPay      = (float)($req['net_payable'] ?? 0);
        $dueAmt      = $req['due_amount'] !== null
                        ? (float)$req['due_amount']
                        : max(0, $netPay - $alreadyPaid);
        $canPayThis  = in_array($req['status'], ['Approved', 'Partially_Paid']) && $dueAmt > 0;
        ?>
        <tr>
            <td class="text-center fw-bold text-muted"><?= $startOffset + $index + 1 ?></td>
            <td class="fw-bold">
                <a href="<?= $base_url ?>/con-vendor-payment-show/<?= $req['id'] ?>" class="text-primary fs-13">
                    VPR-<?= $req['id'] ?>
                </a>
                <?php if (!empty($req['invoice_number'])): ?>
                    <br>
                    <span class="badge bg-light text-dark border extra-small mt-1" style="font-size: 10.5px;" title="Vendor Bill / Invoice #">
                        <i class="bi bi-receipt me-1 text-primary"></i><?= htmlspecialchars($req['invoice_number']) ?>
                    </span>
                <?php endif; ?>
            </td>
            <td><span class="badge <?= $ptClass ?>"><?= $pt ?></span></td>
            <td><?= !empty($req['created_at']) ? date('d M Y', strtotime($req['created_at'])) : '—' ?></td>
            <td>
                <span class="fw-semibold text-dark"><?= htmlspecialchars($req['project_name'] ?? '—') ?></span>
                <?php if (!empty($req['purchase_order_id'])): ?>
                    <br>
                    <a href="<?= $base_url ?>/con-purchase-order/<?= $req['purchase_order_id'] ?>" class="badge bg-light text-primary border" style="font-size: 10.5px;">
                        <i class="bi bi-box-seam me-1"></i>PO-<?= $req['purchase_order_id'] ?>
                    </a>
                <?php endif; ?>
                <?php if (!empty($req['category_names'])): ?>
                    <br>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle mt-1" style="font-size: 10px;" title="Materials: <?= htmlspecialchars($req['material_names'] ?? '') ?>">
                        <i class="bi bi-boxes me-1"></i><?= htmlspecialchars($req['category_names']) ?>
                    </span>
                <?php endif; ?>
            </td>
            <td><span class="badge bg-secondary"><?= htmlspecialchars($req['vendor_name'] ?? '—') ?></span></td>
            <td class="text-end">
                <div class="fw-bold text-dark">৳ <?= number_format((float)($req['invoice_amount'] ?? 0), 2) ?></div>
                <?php if (!empty($req['invoice_number'])): ?>
                    <small class="text-muted d-block" style="font-size: 10px;" title="Vendor Bill / Invoice #">
                        Bill: <span class="fw-semibold text-secondary"><?= htmlspecialchars($req['invoice_number']) ?></span>
                    </small>
                <?php endif; ?>
            </td>
            <td class="text-end fw-bold">৳ <?= number_format((float)($req['net_payable'] ?? 0), 2) ?></td>
            <td class="text-end fw-bold <?= $alreadyPaid > 0 ? 'text-success' : 'text-muted' ?>">
                <?= $alreadyPaid > 0 ? '৳ ' . number_format($alreadyPaid, 2) : '—' ?>
            </td>
            <td class="text-end fw-bold <?= $dueAmt > 0 ? 'text-danger' : ($alreadyPaid > 0 ? 'text-success' : 'text-muted') ?>">
                <?php if ($alreadyPaid <= 0 && $dueAmt <= 0): ?>
                    —
                <?php elseif ($dueAmt <= 0 && $alreadyPaid > 0): ?>
                    <span class="text-success"><i class="bi bi-check-circle-fill"></i> Paid</span>
                <?php else: ?>
                    ৳ <?= number_format($dueAmt, 2) ?>
                <?php endif; ?>
            </td>
            <td>
                <span class="badge bg-<?= $badge ?>"><?= $statusLabel ?></span>
                <?php if (($req['status'] ?? '') === 'Submitted'): ?>
                    <br>
                    <?php if (empty($req['purchase_head_approved_by_id'])): ?>
                        <small class="text-primary fw-semibold" style="font-size: 9.5px;"><i class="bi bi-clock"></i> Pending Purchase Head</small>
                    <?php else: ?>
                        <small class="text-info fw-semibold" style="font-size: 9.5px;"><i class="bi bi-clock"></i> Pending QC Audit</small>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
            <td>
                <div class="d-flex align-items-center gap-1">
                    <i class="bi bi-person-circle text-secondary fs-13"></i>
                    <span class="fw-semibold text-dark fs-12"><?= htmlspecialchars($req['created_by_name'] ?? '—') ?></span>
                </div>
                <?php if (!empty($req['created_at'])): ?>
                    <div class="text-muted" style="font-size: 10px; margin-top: 2px;">
                        <i class="bi bi-clock me-1"></i><?= date('d M Y, h:i A', strtotime($req['created_at'])) ?>
                    </div>
                <?php endif; ?>
            </td>
            <td>
                <div class="small" style="font-size: 11px; line-height: 1.35;">
                    <div class="mb-1">
                        <strong>PH:</strong>
                        <?php if (!empty($req['purchase_head_approved_by_name'])): ?>
                            <span class="text-success fw-semibold"><?= htmlspecialchars($req['purchase_head_approved_by_name']) ?></span>
                        <?php elseif (($req['status'] ?? '') === 'Submitted'): ?>
                            <span class="badge bg-primary text-white" style="font-size:9.5px">Pending PH</span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <strong>QC:</strong> 
                        <?php if (!empty($req['qc_approved_by_name'])): ?>
                            <span class="text-success fw-semibold"><?= htmlspecialchars($req['qc_approved_by_name']) ?></span>
                            <?php if (isset($req['qc_approved_amount']) && (float)$req['qc_approved_amount'] > 0): ?>
                                <br><span class="badge bg-info text-white" style="font-size:9.5px">৳ <?= number_format((float)$req['qc_approved_amount'], 2) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($req['qc_approved_at'])): ?>
                                <br><small class="text-muted" style="font-size:9.5px"><?= date('d M Y', strtotime($req['qc_approved_at'])) ?></small>
                            <?php endif; ?>
                        <?php elseif ($req['status'] === 'Submitted'): ?>
                            <span class="badge bg-warning text-dark" style="font-size:9.5px">Pending QC</span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </div>
                    <div class="mt-1">
                        <strong>Mgmt:</strong>
                        <?php if (!empty($req['approved_by_name'])): ?>
                            <span class="text-success fw-semibold"><?= htmlspecialchars($req['approved_by_name']) ?></span>
                            <?php if (!empty($req['approved_by_role'])): ?>
                                <br><small class="text-muted fst-italic" style="font-size:9.5px"><?= htmlspecialchars($req['approved_by_role']) ?></small>
                            <?php endif; ?>
                        <?php elseif (in_array($req['status'], ['Submitted', 'QC Approved'])): ?>
                            <span class="badge bg-info text-white" style="font-size:9.5px">Pending Mgmt</span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </div>
                </div>
            </td>
            <td>
                <?php if (!empty($req['payment_by_name'])): ?>
                    <span class="text-primary fw-semibold" style="font-size: 11.5px;"><?= htmlspecialchars($req['payment_by_name']) ?></span>
                    <br><small class="text-success fw-bold" style="font-size: 10.5px">Paid: ৳ <?= number_format($alreadyPaid, 2) ?></small>
                <?php else: ?>
                    <span class="text-muted small">—</span>
                <?php endif; ?>
            </td>
            <td class="text-center">
                <?php if (!empty($req['payment_attachment'])): ?>
                    <a href="<?= $base_url ?>/<?= htmlspecialchars($req['payment_attachment']) ?>" target="_blank"
                       class="btn btn-sm btn-outline-secondary" title="View Attachment">
                        <i class="bi bi-paperclip"></i>
                    </a>
                <?php else: ?>
                    <span class="text-muted">—</span>
                <?php endif; ?>
            </td>
            <td class="text-center text-nowrap">
                <div class="d-inline-flex gap-1 justify-content-center flex-nowrap">
                    <?php if ($canPayThis && $canProcessPayment): ?>
                        <a href="<?= $base_url ?>/con-vendor-payment-show/<?= $req['id'] ?>#disburse" 
                           class="btn btn-sm btn-success" title="Disburse Payment">
                            <i class="bi bi-wallet2 me-1"></i> Pay
                        </a>
                    <?php endif; ?>
                    <?php if ($alreadyPaid > 0): ?>
                        <a href="<?= $base_url ?>/expense-adjustments/create?parent_type=vendor&cashout_request_id=<?= $req['id'] ?>&project_id=<?= $req['project_id'] ?>" 
                           class="btn btn-sm btn-outline-warning" title="Settlement / Adjustment">
                            <i class="bi bi-sliders"></i>
                        </a>
                    <?php endif; ?>
                    <a href="<?= $base_url ?>/con-vendor-payment-show/<?= $req['id'] ?>" 
                       class="btn btn-sm btn-outline-primary" title="View Details">
                        <i class="bi bi-eye"></i>
                    </a>
                    <?php
                    $vprRowUid = (int)($_SESSION['user']['id'] ?? 0);
                    $vprRowRid = (int)($_SESSION['user']['role_id'] ?? 0);
                    $vprRowUnapproved = in_array($req['status'] ?? '', ['Draft', 'Submitted'], true)
                        && empty($req['purchase_team_leader_by_id'])
                        && empty($req['purchase_head_approved_by_id'])
                        && empty($req['qc_approved_by_id'])
                        && empty($req['approved_by']);
                    if ($vprRowUnapproved && (((int)($req['created_by'] ?? 0) === $vprRowUid) || in_array($vprRowRid, [1, 3], true))):
                        $vprRowPayload = [
                            'id'             => (int)$req['id'],
                            'payment_type'   => $req['payment_type'] ?? 'Final',
                            'invoice_number' => $req['invoice_number'] ?? '',
                            'invoice_amount' => $req['invoice_amount'] ?? 0,
                        ];
                    ?>
                        <button type="button"
                                class="btn btn-sm btn-warning text-dark btn-edit-vpr-row"
                                title="Edit Request"
                                data-bs-toggle="modal"
                                data-bs-target="#editVprIndexModal"
                                data-item="<?= htmlspecialchars(json_encode($vprRowPayload), ENT_QUOTES, 'UTF-8') ?>">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr>
        <td colspan="16" class="text-center text-muted py-4">
            <i class="bi bi-inbox fs-4 d-block mb-1 opacity-50"></i>
            No vendor payment requests found. <a href="<?= $base_url ?>/con-vendor-payment-create">Create one</a>
        </td>
    </tr>
<?php endif; ?>
