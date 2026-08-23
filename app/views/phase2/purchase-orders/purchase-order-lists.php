<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Purchase Orders</li>
            </ol>
        </nav>
    </div>
    <div class="mb-4 d-flex gap-2 align-items-center">
        <?php if (!in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 15, 20, 22])): ?>
        <a href="<?= $base_url ?>/con-material-receiving-import"
           class="btn btn-success btn-sm fw-bold">
            <i class="bi bi-cloud-upload me-1"></i> Bulk CSV Import
        </a>
        <?php endif; ?>
    </div>
</div>

    <div class="card__wrapper shadow-sm">
        <div class="card__body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped custom-table mb-0 multiple-table">
                    <thead class="table-light">
                        <tr>
                            <th>PO #</th>
                            <th>Project</th>
                            <th class="text-end">Total Amount</th>
                            <th>Status</th>
                            <th class="text-center">Approvals</th>
                            <th>Order Date</th>
                            <th>Created By</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($orders)): ?>
                            <?php foreach ($orders as $po): ?>
                            <tr>
                                <td><span class="fw-bold text-primary">PO-<?= $po['id'] ?></span></td>
                                <td class="fw-bold">
                                    <?= htmlspecialchars($po['project_name']) ?>
                                    <?php if (!empty($po['remarks'])): ?>
                                        <div class="text-muted small fw-normal mt-1" style="font-size:11px" title="Remarks">
                                            <i class="bi bi-chat-left-text me-1 text-primary"></i><?= htmlspecialchars($po['remarks']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($po['latest_note_author'])): ?>
                                        <div class="small fw-normal mt-1 p-1 rounded" style="font-size:10px; background:rgba(139,92,246,.08); color:#6d28d9; border: 1px solid rgba(139,92,246,.2);" title="<?= htmlspecialchars($po['latest_note_text']) ?>">
                                            <i class="fal fa-comment-dots me-1"></i><strong><?= htmlspecialchars($po['latest_note_author']) ?>:</strong> <?= htmlspecialchars(mb_strimwidth($po['latest_note_text'], 0, 30, '...')) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">৳ <?= number_format($po['total_amount'] ?? 0, 2) ?></td>
                                <td>
                                     <?php
                                     $badge = match($po['status']) {
                                         'Issued'             => 'bg-info',
                                         'Purchase Head Approved' => 'bg-warning text-dark',
                                         'QC Approved'        => 'bg-primary',
                                         'Approved'           => 'bg-success',
                                         'Partially Received' => 'bg-warning text-dark',
                                         'Fully Received'     => 'bg-success',
                                         'Cancelled'          => 'bg-danger',
                                         default              => 'bg-secondary',
                                     };
                                     ?>
                                     <span class="badge <?= $badge ?>"><?= $po['status'] ?></span>
                                     <?php
                                     $creatorRole = (int)($po['creator_role_id'] ?? 0);
                                     $phNeeded = in_array($creatorRole, [23, 31]);
                                     
                                     if ($po['status'] === 'Issued'): ?>
                                         <div class="mt-1 text-danger" style="font-size: 11px; font-weight: 600;">
                                             <i class="bi bi-hourglass-split"></i> Pending: <?= $phNeeded ? 'Purchase Head' : 'QC & Internal Audit' ?>
                                         </div>
                                     <?php elseif ($po['status'] === 'Purchase Head Approved'): ?>
                                         <div class="mt-1 text-danger" style="font-size: 11px; font-weight: 600;">
                                             <i class="bi bi-hourglass-split"></i> Pending: QC & Internal Audit
                                         </div>
                                     <?php elseif ($po['status'] === 'QC Approved'): ?>
                                         <div class="mt-1 text-danger" style="font-size: 11px; font-weight: 600;">
                                             <i class="bi bi-hourglass-split"></i> Pending: Operation Head
                                         </div>
                                     <?php endif; ?>
                                </td>
                                <td class="text-center">
                                     <?php
                                     $phOk = !empty($po['purchase_head_approved_by_id']);
                                     $qcOk = !empty($po['qc_approved_by_id']);
                                     $opOk = !empty($po['op_head_approved_by_id']);
                                     $creatorRole = (int)($po['creator_role_id'] ?? 0);
                                     $phNeeded = in_array($creatorRole, [23, 31]);
                                     $totalSteps = $phNeeded ? 3 : 2;
                                     $doneSteps = ($phNeeded && $phOk ? 1 : 0) + ($qcOk ? 1 : 0) + ($opOk ? 1 : 0);
                                     $apvColor = ($doneSteps == $totalSteps) ? '#059669' : ($doneSteps > 0 ? '#2563eb' : '#6b7280');
                                     ?>
                                     <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;
                                       background:rgba(0,0,0,.05);color:<?= $apvColor ?>">
                                       <?= $doneSteps ?>/<?= $totalSteps ?>
                                       <?= ($phNeeded && $phOk)?'<span title="Purchase Head">PH✓</span>':'' ?>
                                       <?= $qcOk?'<span title="QC & Internal Audit">QC✓</span>':'' ?>
                                       <?= $opOk?'<span title="Operation Head">OP✓</span>':'' ?>
                                     </span>
                                </td>
                                <td class="text-muted small"><?= date('d M Y', strtotime($po['order_date'])) ?></td>
                                <td><?= htmlspecialchars($po['created_by_name'] ?? '—') ?></td>
                                <td class="text-center">
                                    <a href="<?= $base_url ?>/con-purchase-order/<?= $po['id'] ?>"
                                       class="btn btn-sm btn-outline-info" title="View PO">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-clipboard-x me-2"></i>No purchase orders yet.
                                    POs are auto-generated from approved CST comparisons.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
