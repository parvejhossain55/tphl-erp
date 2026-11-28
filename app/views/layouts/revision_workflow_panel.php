<?php
/**
 * Reusable Send-Back-for-Review & Resubmission Banner + Modal
 *
 * Expected variables before include:
 *   $revModuleTable      (string) e.g. 'fund_requisitions'
 *   $revSourceId         (int)
 *   $revCreatorUserId    (int)
 *   $revCanSendBack      (bool)   whether current user is an active approver for this stage
 *   $revReturnStage      (string) e.g. 'dept_head', 'qc', 'op_head', 'accounts_head', 'mgmt'
 *   $revReturnStageLabel (string) e.g. 'QC & Internal Audit'
 *   $revRedirectUrl      (string) relative URL e.g. '/fund-requisitions/view/948'
 *   $revEditModalTarget  (string) optional '#editFundReqModal' (or '' if using $revEditLinkUrl)
 *   $revEditLinkUrl      (string) optional link URL (e.g. for Contractor Bill edit page)
 *   $revLatest           (array|null) latest 'open' or 'resubmitted' row from RequisitionRevisionService
 */

$revLatest           = $revLatest ?? null;
$revCanSendBack      = !empty($revCanSendBack);
$revCurrentUserId    = (int)($_SESSION['user']['id'] ?? 0);
$revCurrentRoleId    = (int)($_SESSION['user']['role_id'] ?? 0);
$revIsCreatorOrAdmin = ($revCurrentUserId === (int)($revCreatorUserId ?? 0)) || in_array($revCurrentRoleId, [1, 3], true);
$revIsOpen           = ($revLatest && ($revLatest['status'] ?? '') === 'open');
$revIsResubmitted    = ($revLatest && ($revLatest['status'] ?? '') === 'resubmitted');
$revModalDomId       = 'sendBackReviewModal_' . preg_replace('/[^a-zA-Z0-9_]/', '', (string)($revModuleTable ?? 'req'));
$revAppealModalDomId = 'resubmitRejectedModal_' . preg_replace('/[^a-zA-Z0-9_]/', '', (string)($revModuleTable ?? 'req'));

// Fetch permanent Last Edited By audit log & auto-detect rejected state
$revLastEdit    = $revLastEdit ?? null;
$revRecentEdits = [];
$revRejInfo     = ['is_rejected' => false, 'stage' => '', 'stage_label' => '', 'reason' => ''];
if (!empty($revModuleTable) && !empty($revSourceId)) {
    $revDbConn = $GLOBALS['db'] ?? ($this->db ?? null);
    if ($revDbConn) {
        if (!class_exists('RequisitionRevisionService')) {
            require_once __DIR__ . '/../../services/RequisitionRevisionService.php';
        }
        $revPanelSvc = new RequisitionRevisionService($revDbConn);
        if ($revLastEdit === null) {
            $revLastEdit = $revPanelSvc->getLastEdit((string)$revModuleTable, (int)$revSourceId);
        }
        $revRecentEdits = $revPanelSvc->getRecentEdits((string)$revModuleTable, (int)$revSourceId, 4);
        $revRejInfo = $revPanelSvc->getRejectionStatus((string)$revModuleTable, (int)$revSourceId);
    }
}
$revIsRejected = !empty($revRejInfo['is_rejected']);
?>

<?php if (!empty($revLastEdit)): ?>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 py-2 mb-3 rounded-3 border bg-light-subtle text-dark small no-print" style="border-left: 4px solid #6366f1 !important;">
        <div>
            <i class="fa-solid fa-user-pen text-primary me-1"></i>
            <strong>Last Edited By:</strong>
            <span class="fw-semibold"><?= htmlspecialchars($revLastEdit['edited_by_name'] ?? 'User') ?></span>
            <?php if (!empty($revLastEdit['edited_by_role'])): ?>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1"><?= htmlspecialchars($revLastEdit['edited_by_role']) ?></span>
            <?php endif; ?>
            <?php if (!empty($revLastEdit['change_summary'])): ?>
                <span class="text-muted ms-2">• <?= htmlspecialchars($revLastEdit['change_summary']) ?></span>
            <?php endif; ?>
        </div>
        <div class="text-muted" style="font-size:11.5px;">
            <i class="fa-regular fa-clock me-1"></i><?= !empty($revLastEdit['created_at']) ? date('d M Y, h:i A', strtotime($revLastEdit['created_at'])) : '—' ?>
        </div>
    </div>
<?php endif; ?>

<?php if ($revIsOpen): ?>
    <div class="shadow-sm d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 mb-3 rounded-3 no-print" style="background:#fffbeb; border:1px solid #fde68a; border-left:4px solid #f59e0b;">
        <div class="d-flex align-items-start gap-3" style="flex:1; min-width:260px;">
            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px;background:#fef3c7;color:#d97706;border:1px solid #fde68a;">
                <i class="fa-solid fa-rotate-left"></i>
            </div>
            <div style="flex:1;">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h6 class="fw-bold mb-0" style="color:#92400e;font-size:14px;">
                        Review &amp; Correction Requested by <?= htmlspecialchars($revLatest['requested_by_name'] ?? 'Approver') ?>
                    </h6>
                    <span class="badge" style="background:#fef3c7;color:#92400e;border:1px solid #fcd34d;font-size:11px;"><?= htmlspecialchars($revLatest['return_stage_label'] ?? 'Approver') ?></span>
                </div>
                <div class="small mb-2 px-3 py-2 rounded-2" style="background:#ffffff;border:1px solid #fde68a;color:#1f2937;">
                    <strong style="color:#92400e;">Review Note:</strong> <?= nl2br(htmlspecialchars(trim($revLatest['review_note'] ?? ''))) ?>
                </div>
                <div style="font-size:11.5px;color:#78350f;">
                    <i class="fa-regular fa-clock me-1"></i>Requested on <?= !empty($revLatest['created_at']) ? date('d M Y, h:i A', strtotime($revLatest['created_at'])) : '—' ?>
                    • After update &amp; resubmission, returns directly to <strong><?= htmlspecialchars($revLatest['return_stage_label'] ?? 'Approver') ?></strong>.
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <?php if ($revIsCreatorOrAdmin): ?>
                <?php if (!empty($revEditModalTarget)): ?>
                    <button type="button" class="btn btn-warning text-dark btn-sm fw-bold shadow-sm px-3" data-bs-toggle="modal" data-bs-target="<?= htmlspecialchars($revEditModalTarget) ?>">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit &amp; Resubmit
                    </button>
                <?php elseif (!empty($revEditLinkUrl)): ?>
                    <a href="<?= htmlspecialchars($revEditLinkUrl) ?>" class="btn btn-warning text-dark btn-sm fw-bold shadow-sm px-3">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit &amp; Resubmit
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <span class="badge bg-dark text-white px-3 py-2">Awaiting Creator Resubmission</span>
            <?php endif; ?>
        </div>
    </div>
<?php elseif ($revIsResubmitted): ?>
    <?php $revIsAppeal = (($revLatest['revision_type'] ?? 'review') === 'rejection_appeal'); ?>
    <div class="shadow-sm d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 mb-3 rounded-3 no-print" style="background:#f0f9ff; border:1px solid #bae6fd; border-left:4px solid #0284c7;">
        <div class="d-flex align-items-start gap-3" style="flex:1; min-width:260px;">
            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px;background:#e0f2fe;color:#0284c7;border:1px solid #bae6fd;">
                <i class="fa-solid fa-check-double"></i>
            </div>
            <div style="flex:1;">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h6 class="fw-bold mb-0" style="color:#0c4a6e;font-size:14px;">
                        <?php if ($revIsAppeal): ?>
                            Creator Resubmitted Against Rejection
                        <?php else: ?>
                            Creator Updated &amp; Resubmitted
                        <?php endif; ?>
                    </h6>
                    <span class="badge" style="background:#e0f2fe;color:#0369a1;border:1px solid #7dd3fc;font-size:11px;">Returned to <?= htmlspecialchars($revLatest['return_stage_label'] ?? 'Approver') ?></span>
                </div>
                <div class="small mb-2 px-3 py-2 rounded-2" style="background:#ffffff;border:1px solid #bae6fd;color:#1f2937;">
                    <div class="mb-1">
                        <strong style="color:#475569;"><?= $revIsAppeal ? 'Previous Rejection Note' : 'Original Review Note' ?> (<?= htmlspecialchars($revLatest['requested_by_name'] ?? 'Approver') ?>):</strong>
                        <?= nl2br(htmlspecialchars(trim($revLatest['review_note'] ?? ''))) ?>
                    </div>
                    <?php if ($revLatest['old_amount'] !== null && $revLatest['new_amount'] !== null && abs((float)$revLatest['old_amount'] - (float)$revLatest['new_amount']) > 0.001): ?>
                        <div class="mb-1">
                            <strong style="color:#475569;">Amount Updated:</strong>
                            <span class="text-muted text-decoration-line-through me-1">৳<?= number_format((float)$revLatest['old_amount'], 2) ?></span>
                            <i class="fa-solid fa-arrow-right mx-1 text-primary"></i>
                            <span class="fw-bold text-primary">৳<?= number_format((float)$revLatest['new_amount'], 2) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($revLatest['resubmitted_note'])): ?>
                        <div class="mb-1">
                            <strong style="color:#0369a1;"><?= $revIsAppeal ? 'Creator Valid Cause / Justification:' : 'Creator Response:' ?></strong>
                            <?= nl2br(htmlspecialchars(trim($revLatest['resubmitted_note']))) ?>
                        </div>
                    <?php endif; ?>
                    <?php
                    $revFieldChanges = array_values(array_filter($revRecentEdits, function ($e) {
                        return stripos((string)($e['change_summary'] ?? ''), 'Resubmitted against rejection') !== 0;
                    }));
                    if (!empty($revFieldChanges)):
                    ?>
                        <div class="mt-2 pt-2 border-top" style="border-color:#e0f2fe !important;">
                            <div class="fw-bold mb-1" style="color:#0f172a;font-size:12px;">
                                <i class="fa-solid fa-list-check text-primary me-1"></i> Updated Information by Creator:
                            </div>
                            <?php foreach (array_slice($revFieldChanges, 0, 3) as $fc): ?>
                                <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 mb-1" style="font-size:12px;">
                                    <span style="color:#1e293b;">
                                        <strong><?= htmlspecialchars($fc['edited_by_name'] ?? 'Creator') ?>:</strong>
                                        <?= htmlspecialchars($fc['change_summary'] ?? 'Details updated') ?>
                                    </span>
                                    <span class="text-muted text-nowrap" style="font-size:11px;">
                                        <i class="fa-regular fa-clock me-1"></i><?= !empty($fc['created_at']) ? date('d M Y, h:i A', strtotime($fc['created_at'])) : '—' ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-3" style="font-size:11.5px;color:#0369a1;">
                    <span><i class="fa-regular fa-clock me-1"></i>Resubmitted on <?= !empty($revLatest['resubmitted_at']) ? date('d M Y, h:i A', strtotime($revLatest['resubmitted_at'])) : '—' ?></span>
                    <?php if (!empty($revLatest['attachment_path'])): ?>
                        <a href="<?= $GLOBALS['base_url'] ?>/public/<?= htmlspecialchars($revLatest['attachment_path']) ?>" target="_blank" class="btn btn-sm btn-primary py-1 px-2 fw-semibold" style="font-size:11.5px;">
                            <i class="fa-solid fa-paperclip me-1"></i> View / Download Supporting Proof
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php elseif ($revIsRejected): ?>
    <div class="shadow-sm d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 mb-3 rounded-3 no-print" style="background:#fef2f2; border:1px solid #fecaca; border-left:4px solid #dc2626;">
        <div class="d-flex align-items-start gap-3" style="flex:1; min-width:260px;">
            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px;background:#fee2e2;color:#dc2626;border:1px solid #fecaca;">
                <i class="fa-solid fa-ban"></i>
            </div>
            <div style="flex:1;">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h6 class="fw-bold mb-0" style="color:#991b1b;font-size:14px;">
                        Rejected at <?= htmlspecialchars($revRejInfo['stage_label'] ?: 'Approval Stage') ?>
                    </h6>
                    <span class="badge" style="background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5;font-size:11px;">Rejected</span>
                </div>
                <?php if (!empty($revRejInfo['reason'])): ?>
                    <div class="small mb-2 px-3 py-2 rounded-2" style="background:#ffffff;border:1px solid #fecaca;color:#1f2937;">
                        <strong style="color:#b91c1c;">Rejection Reason:</strong> <?= nl2br(htmlspecialchars(trim($revRejInfo['reason']))) ?>
                    </div>
                <?php endif; ?>
                <div style="font-size:11.5px;color:#7f1d1d;">
                    <?php if ($revIsCreatorOrAdmin): ?>
                        <i class="fa-solid fa-circle-info me-1"></i>Have valid justification or supporting documents? Resubmit against this rejection to return it directly to <strong><?= htmlspecialchars($revRejInfo['stage_label'] ?: 'the rejecting approver') ?></strong> for re-review.
                    <?php else: ?>
                        <i class="fa-solid fa-circle-info me-1"></i>The creator can resubmit against this rejection with valid cause and supporting proof.
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php if ($revIsCreatorOrAdmin): ?>
            <button type="button" class="btn btn-danger btn-sm fw-bold shadow-sm flex-shrink-0 px-3 py-2" data-bs-toggle="modal" data-bs-target="#<?= $revAppealModalDomId ?>">
                <i class="fa-solid fa-pen-to-square me-1"></i> Edit &amp; Resubmit for Review
            </button>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($revCanSendBack && !$revIsOpen): ?>
<!-- Send Back for Review Modal -->
<div class="modal custom-modal fade" id="<?= $revModalDomId ?>" tabindex="-1" aria-labelledby="<?= $revModalDomId ?>Label" aria-hidden="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header pb-3 mb-2 border-bottom">
                <h5 class="modal-title fw-bold text-dark" id="<?= $revModalDomId ?>Label">
                    <i class="fa-solid fa-rotate-left me-2 text-warning"></i> Send Back for Review &amp; Correction
                </h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body p-0">
                <form method="POST" action="<?= $GLOBALS['base_url'] ?>/requisition-revision/send-back">
                    <input type="hidden" name="module_table" value="<?= htmlspecialchars($revModuleTable) ?>">
                    <input type="hidden" name="source_id" value="<?= (int)$revSourceId ?>">
                    <input type="hidden" name="return_stage" value="<?= htmlspecialchars($revReturnStage) ?>">
                    <input type="hidden" name="return_stage_label" value="<?= htmlspecialchars($revReturnStageLabel) ?>">
                    <input type="hidden" name="redirect_url" value="<?= htmlspecialchars($revRedirectUrl) ?>">

                    <div class="alert alert-light border small text-dark mb-3">
                        <i class="fa-solid fa-circle-info text-primary me-1"></i>
                        This will unlock the requisition for the creator to edit and resubmit. Previous approvals will remain intact, and once resubmitted it will return directly to <strong><?= htmlspecialchars($revReturnStageLabel) ?></strong>.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Review / Correction Instructions <span class="text-danger">*</span></label>
                        <textarea name="review_note" class="form-control" rows="4" placeholder="Explain what needs to be updated or corrected..." required></textarea>
                    </div>

                    <div class="modal-footer px-0 pb-0 pt-3 border-top d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning text-dark px-4 fw-bold">
                            <i class="fa-solid fa-paper-plane me-1"></i> Send Back for Review
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
(function() {
    function initRevModal_<?= $revModalDomId ?>() {
        if (typeof jQuery === 'undefined' || typeof bootstrap === 'undefined') {
            setTimeout(initRevModal_<?= $revModalDomId ?>, 50);
            return;
        }
        jQuery(function($) {
            var $m = $('#<?= $revModalDomId ?>');
            if ($m.length && $m.parent().prop('tagName') !== 'BODY') {
                $m.appendTo('body');
            }
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initRevModal_<?= $revModalDomId ?>);
    } else {
        initRevModal_<?= $revModalDomId ?>();
    }
})();
</script>
<?php endif; ?>

<?php if ($revIsRejected && $revIsCreatorOrAdmin): ?>
<?php
$revEditFieldsCfg = class_exists('RequisitionRevisionService')
    ? RequisitionRevisionService::getEditableFieldsConfig((string)$revModuleTable)
    : [];
$revEditRec = [];
if (!empty($revModuleTable) && !empty($revSourceId)) {
    $revDbForRec = $GLOBALS['db'] ?? ($this->db ?? null);
    if ($revDbForRec) {
        $resRec = @$revDbForRec->query("SELECT * FROM `{$revModuleTable}` WHERE id = " . (int)$revSourceId . " LIMIT 1");
        if ($resRec && $resRec->num_rows > 0) {
            $revEditRec = $resRec->fetch_assoc();
        }
    }
}
?>
<!-- Unified Edit & Resubmit Against Rejection Modal -->
<div class="modal custom-modal fade" id="<?= $revAppealModalDomId ?>" tabindex="-1" aria-labelledby="<?= $revAppealModalDomId ?>Label" aria-hidden="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header pb-3 mb-2 border-bottom">
                <h5 class="modal-title fw-bold text-dark" id="<?= $revAppealModalDomId ?>Label">
                    <i class="fa-solid fa-pen-to-square me-2 text-danger"></i> Edit &amp; Resubmit for Review (Against Rejection)
                </h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body p-0">
                <form method="POST" action="<?= $GLOBALS['base_url'] ?>/requisition-revision/resubmit-rejected" enctype="multipart/form-data">
                    <input type="hidden" name="module_table" value="<?= htmlspecialchars($revModuleTable) ?>">
                    <input type="hidden" name="source_id" value="<?= (int)$revSourceId ?>">
                    <input type="hidden" name="redirect_url" value="<?= htmlspecialchars($revRedirectUrl) ?>">

                    <?php if (!empty($revRejInfo['reason'])): ?>
                        <div class="rounded-2 py-2 px-3 small mb-3" style="background:#fef2f2;border:1px solid #fecaca;color:#7f1d1d;">
                            <strong style="color:#b91c1c;"><i class="fa-solid fa-ban me-1"></i>Rejection Reason (<?= htmlspecialchars($revRejInfo['stage_label'] ?: 'Approver') ?>):</strong>
                            <?= nl2br(htmlspecialchars(trim($revRejInfo['reason']))) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($revEditFieldsCfg)): ?>
                        <div class="p-3 rounded-2 mb-3" style="background:#f8fafc;border:1px solid #e2e8f0;">
                            <div class="fw-bold text-dark small mb-2">
                                <i class="fa-solid fa-sliders text-primary me-1"></i> Update Request Information (Amount, Qty, Description, etc.)
                            </div>
                            <div class="row g-2">
                                <?php foreach ($revEditFieldsCfg as $col => $meta):
                                    if (!array_key_exists($col, $revEditRec)) continue;
                                    $val = $revEditRec[$col] ?? '';
                                    $colClass = 'col-md-' . (int)($meta['col'] ?? 6);
                                ?>
                                    <div class="<?= $colClass ?>">
                                        <label class="form-label fw-semibold small text-dark mb-1"><?= htmlspecialchars($meta['label']) ?></label>
                                        <?php if (($meta['type'] ?? 'text') === 'textarea'): ?>
                                            <textarea name="edit_fields[<?= htmlspecialchars($col) ?>]" class="form-control form-control-sm" rows="2"><?= htmlspecialchars((string)$val) ?></textarea>
                                        <?php elseif (($meta['type'] ?? 'text') === 'number'): ?>
                                            <input type="number" step="<?= htmlspecialchars($meta['step'] ?? '0.01') ?>" min="0" name="edit_fields[<?= htmlspecialchars($col) ?>]" class="form-control form-control-sm" value="<?= htmlspecialchars((string)$val) ?>">
                                        <?php elseif (($meta['type'] ?? 'text') === 'date'): ?>
                                            <input type="date" name="edit_fields[<?= htmlspecialchars($col) ?>]" class="form-control form-control-sm" value="<?= !empty($val) ? htmlspecialchars(date('Y-m-d', strtotime((string)$val))) : '' ?>">
                                        <?php else: ?>
                                            <input type="text" name="edit_fields[<?= htmlspecialchars($col) ?>]" class="form-control form-control-sm" value="<?= htmlspecialchars((string)$val) ?>">
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Valid Cause / Clarification for Approver <span class="text-danger">*</span></label>
                        <textarea name="appeal_note" class="form-control" rows="3" placeholder="Explain clearly what was updated or why this request should be re-reviewed..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Supporting Proof / Attachment <span class="text-muted fw-normal">(Optional — PDF, Image, Excel, Doc; max 10MB)</span></label>
                        <input type="file" name="appeal_attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx">
                    </div>

                    <div class="modal-footer px-0 pb-0 pt-3 border-top d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger px-4 fw-bold">
                            <i class="fa-solid fa-paper-plane me-1"></i> Save &amp; Resubmit for Review
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
(function() {
    function initAppealModal_<?= $revAppealModalDomId ?>() {
        if (typeof jQuery === 'undefined' || typeof bootstrap === 'undefined') {
            setTimeout(initAppealModal_<?= $revAppealModalDomId ?>, 50);
            return;
        }
        jQuery(function($) {
            var $m = $('#<?= $revAppealModalDomId ?>');
            if ($m.length && $m.parent().prop('tagName') !== 'BODY') {
                $m.appendTo('body');
            }
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAppealModal_<?= $revAppealModalDomId ?>);
    } else {
        initAppealModal_<?= $revAppealModalDomId ?>();
    }
})();
</script>
<?php endif; ?>
