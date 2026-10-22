<?php
/**
 * Fund Requisition – Management / Operation Head approval breakdown
 * Shows who approved how much (incl. multiple partial approvals) inside the
 * "Approval Status & Pending At" column of the All Approval Requests list.
 *
 * Expects: $req (row from ApprovalRequest::getAllApprovalHistory)
 */
$frMgmtApprovals = (($req['source_table'] ?? '') === 'fund_requisitions' && !empty($req['mgmt_approvals']) && is_array($req['mgmt_approvals']))
    ? $req['mgmt_approvals']
    : [];
if (!empty($frMgmtApprovals)):
    $frIsPartial   = (($req['fr_status'] ?? '') === 'partially_approved');
    $frApprovedTot = (float)($req['fr_mgmt_approved_total'] ?? 0);
    $frRemaining   = (float)($req['fr_mgmt_remaining'] ?? 0);
?>
    <div class="mt-2 pt-1 border-top <?= $frIsPartial ? 'border-info-subtle' : 'border-success-subtle' ?> text-start">
        <span class="badge <?= $frIsPartial ? 'bg-info-subtle text-info border border-info-subtle' : 'bg-success-subtle text-success border border-success-subtle' ?> extra-small fw-semibold d-inline-block mb-1" style="font-size: 9.5px; padding: 2px 6px;">
            <i class="fa-solid fa-user-shield me-1"></i><?= $frIsPartial ? 'Last Approved: Management (Partial)' : 'Management Approved' ?>
        </span>
        <?php foreach ($frMgmtApprovals as $fa): ?>
            <div class="mb-1">
                <div class="extra-small text-dark fw-bold" style="font-size: 11px;">
                    <i class="fa-solid fa-user-check text-success me-1"></i><?= htmlspecialchars($fa['name'] ?? 'Management') ?>
                    <?php if (!empty($fa['emp_code'])): ?>
                        <span class="badge bg-light text-secondary border extra-small"><?= htmlspecialchars($fa['emp_code']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="extra-small" style="font-size: 10.5px;">
                    <span class="fw-bold <?= ($fa['action'] ?? '') === 'partially_approved' ? 'text-info' : 'text-success' ?>">
                        <?= ($fa['action'] ?? '') === 'partially_approved' ? 'Partially approved' : 'Approved' ?>: ৳ <?= number_format((float)($fa['amount'] ?? 0), 2) ?>
                    </span>
                    <span class="text-muted">(<?= htmlspecialchars($fa['stage_label'] ?? 'Management') ?>)</span>
                </div>
                <?php if (!empty($fa['created_at'])): ?>
                    <span class="text-muted extra-small d-block" style="font-size: 10px;">
                        <i class="fa-solid fa-calendar-check me-1"></i><?= date('d M, Y h:i A', strtotime($fa['created_at'])) ?>
                    </span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if ($frIsPartial): ?>
            <div class="extra-small mt-1 p-1 rounded bg-light border" style="font-size: 10px;">
                Approved: <strong class="text-success">৳ <?= number_format($frApprovedTot, 2) ?></strong>
                &nbsp;|&nbsp; Remaining: <strong class="text-danger">৳ <?= number_format($frRemaining, 2) ?></strong>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
