<?php
/**
 * Shared List-Row "Resubmit Against Rejection" Modal
 * Any list view can add a button with:
 *   class="btn-resubmit-rejected-row"
 *   data-module="land_fund_requisitions" (or vendors / contractors)
 *   data-id="123"
 *   data-reason="Optional rejection reason"
 *   data-redirect="/con-land-requisitions"
 */
if (!defined('TPHL_RESUBMIT_REJECTED_MODAL_RENDERED')):
define('TPHL_RESUBMIT_REJECTED_MODAL_RENDERED', true);
?>
<div class="modal custom-modal fade" id="globalResubmitRejectedModal" tabindex="-1" aria-labelledby="globalResubmitRejectedModalLabel" aria-hidden="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header pb-3 mb-2 border-bottom">
                <h5 class="modal-title fw-bold text-dark" id="globalResubmitRejectedModalLabel">
                    <i class="fa-solid fa-rotate-right me-2 text-danger"></i> Resubmit for Review (Against Rejection)
                </h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body p-0">
                <form method="POST" action="<?= $GLOBALS['base_url'] ?>/requisition-revision/resubmit-rejected" enctype="multipart/form-data">
                    <input type="hidden" name="module_table" id="grr_module_table" value="">
                    <input type="hidden" name="source_id" id="grr_source_id" value="">
                    <input type="hidden" name="redirect_url" id="grr_redirect_url" value="">

                    <div id="grr_reason_box" class="rounded-2 py-2 px-3 small mb-3 d-none" style="background:#fef2f2;border:1px solid #fecaca;color:#7f1d1d;">
                        <strong style="color:#b91c1c;">Rejection Reason:</strong> <span id="grr_reason_text"></span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Valid Cause / Clarification <span class="text-danger">*</span></label>
                        <textarea name="appeal_note" id="grr_appeal_note" class="form-control" rows="4" placeholder="Explain clearly with valid facts/data why this rejected request should be re-reviewed..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Supporting Proof / Attachment <span class="text-muted fw-normal">(Optional — PDF, Image, Excel, Doc; max 10MB)</span></label>
                        <input type="file" name="appeal_attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx">
                    </div>

                    <div class="modal-footer px-0 pb-0 pt-3 border-top d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger px-4 fw-bold">
                            <i class="fa-solid fa-paper-plane me-1"></i> Resubmit for Review
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
(function() {
    function initGlobalResubmitRejectedModal() {
        if (typeof jQuery === 'undefined' || typeof bootstrap === 'undefined') {
            setTimeout(initGlobalResubmitRejectedModal, 50);
            return;
        }
        jQuery(function($) {
            var $m = $('#globalResubmitRejectedModal');
            if ($m.length && $m.parent().prop('tagName') !== 'BODY') {
                $m.appendTo('body');
            }
            $(document).on('click', '.btn-resubmit-rejected-row', function() {
                var $btn = $(this);
                $('#grr_module_table').val($btn.attr('data-module') || '');
                $('#grr_source_id').val($btn.attr('data-id') || '');
                $('#grr_redirect_url').val($btn.attr('data-redirect') || '');
                $('#grr_appeal_note').val('');
                var reason = ($btn.attr('data-reason') || '').trim();
                if (reason) {
                    $('#grr_reason_text').text(reason);
                    $('#grr_reason_box').removeClass('d-none');
                } else {
                    $('#grr_reason_box').addClass('d-none');
                }
                var modalEl = document.getElementById('globalResubmitRejectedModal');
                if (modalEl) {
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                }
            });
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initGlobalResubmitRejectedModal);
    } else {
        initGlobalResubmitRejectedModal();
    }
})();
</script>
<?php endif; ?>
