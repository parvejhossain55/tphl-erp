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
?>

<?php if ($revIsOpen): ?>
    <div class="alert alert-warning border border-warning shadow-sm d-flex flex-wrap align-items-start justify-content-between gap-3 p-3 mb-3 rounded-3 no-print" style="border-left: 5px solid #f59e0b !important;">
        <div class="d-flex align-items-start gap-3">
            <div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center flex-shrink-0" style="width:38px;height:38px;">
                <i class="fa-solid fa-rotate-left"></i>
            </div>
            <div>
                <h6 class="fw-bold text-dark mb-1">
                    Review &amp; Correction Requested by <?= htmlspecialchars($revLatest['requested_by_name'] ?? 'Approver') ?>
                    <span class="badge bg-warning text-dark border border-dark-subtle ms-1"><?= htmlspecialchars($revLatest['return_stage_label'] ?? 'Approver') ?></span>
                </h6>
                <div class="text-dark small mb-1" style="white-space:pre-wrap;">
                    <strong>Review Note:</strong> <?= htmlspecialchars($revLatest['review_note'] ?? '') ?>
                </div>
                <div class="text-muted" style="font-size:11.5px;">
                    Requested on <?= !empty($revLatest['created_at']) ? date('d M Y, h:i A', strtotime($revLatest['created_at'])) : '—' ?>
                    • After the creator updates &amp; resubmits, this request returns directly to <strong><?= htmlspecialchars($revLatest['return_stage_label'] ?? 'Approver') ?></strong>.
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php if ($revIsCreatorOrAdmin): ?>
                <?php if (!empty($revEditModalTarget)): ?>
                    <button type="button" class="btn btn-warning text-dark btn-sm fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="<?= htmlspecialchars($revEditModalTarget) ?>">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit &amp; Resubmit
                    </button>
                <?php elseif (!empty($revEditLinkUrl)): ?>
                    <a href="<?= htmlspecialchars($revEditLinkUrl) ?>" class="btn btn-warning text-dark btn-sm fw-bold shadow-sm">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit &amp; Resubmit
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <span class="badge bg-dark text-white px-2.5 py-2">Awaiting Creator Resubmission</span>
            <?php endif; ?>
        </div>
    </div>
<?php elseif ($revIsResubmitted): ?>
    <div class="alert alert-info border border-info shadow-sm d-flex flex-wrap align-items-start justify-content-between gap-3 p-3 mb-3 rounded-3 no-print" style="border-left: 5px solid #0284c7 !important;">
        <div class="d-flex align-items-start gap-3">
            <div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width:38px;height:38px;">
                <i class="fa-solid fa-check-double"></i>
            </div>
            <div>
                <h6 class="fw-bold text-dark mb-1">
                    Creator Updated &amp; Resubmitted — Returned to <?= htmlspecialchars($revLatest['return_stage_label'] ?? 'Approver') ?>
                </h6>
                <div class="text-dark small mb-1">
                    <strong>Original Review Note (<?= htmlspecialchars($revLatest['requested_by_name'] ?? 'Approver') ?>):</strong>
                    <?= htmlspecialchars($revLatest['review_note'] ?? '') ?>
                </div>
                <?php if ($revLatest['old_amount'] !== null && $revLatest['new_amount'] !== null): ?>
                    <div class="small mb-1">
                        <strong>Amount Change:</strong>
                        <span class="text-muted text-decoration-line-through me-1">৳<?= number_format((float)$revLatest['old_amount'], 2) ?></span>
                        <i class="fa-solid fa-arrow-right mx-1 text-primary"></i>
                        <span class="fw-bold text-primary">৳<?= number_format((float)$revLatest['new_amount'], 2) ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($revLatest['resubmitted_note'])): ?>
                    <div class="text-dark small mb-1">
                        <strong>Creator Response:</strong> <?= htmlspecialchars($revLatest['resubmitted_note']) ?>
                    </div>
                <?php endif; ?>
                <div class="text-muted" style="font-size:11.5px;">
                    Resubmitted on <?= !empty($revLatest['resubmitted_at']) ? date('d M Y, h:i A', strtotime($revLatest['resubmitted_at'])) : '—' ?>
                </div>
            </div>
        </div>
        <?php if ($revCanSendBack): ?>
            <button type="button" class="btn btn-outline-warning btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#<?= $revModalDomId ?>">
                <i class="fa-solid fa-rotate-left me-1"></i> Send Back Again
            </button>
        <?php endif; ?>
    </div>
<?php elseif ($revCanSendBack): ?>
    <div class="alert alert-light border border-warning shadow-sm d-flex flex-wrap align-items-center justify-content-between gap-2 py-2 px-3 mb-3 rounded-3 no-print" style="border-left: 4px solid #f59e0b !important;">
        <div class="small text-dark">
            <i class="fa-solid fa-rotate-left text-warning me-1"></i>
            <strong>Need Correction Before Approving?</strong> Send this request back to the creator with a review note — it will return directly to <strong><?= htmlspecialchars($revReturnStageLabel) ?></strong> after resubmission.
        </div>
        <button type="button" class="btn btn-warning text-dark btn-sm fw-bold shadow-sm flex-shrink-0" data-bs-toggle="modal" data-bs-target="#<?= $revModalDomId ?>">
            <i class="fa-solid fa-rotate-left me-1"></i> Send Back for Review
        </button>
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
