<?php
/**
 * Accounts Head Approval Status & Action Card Component
 * 
 * Reusable across all 13 modules show/details pages.
 * Displays Accounts Head (Role 30 - Md. Mamunoor Rashid [1309]) verification status,
 * enforces locked payment state until approval, and provides single-click approval/rejection.
 */

if (!isset($db)) {
    $db = $GLOBALS['db'] ?? ($this->db ?? null);
}

if (!isset($ahInfo)) {
    if (!empty($sourceTable) && !empty($sourceId) && $db) {
        if (!class_exists('ApprovalRequest')) {
            require_once __DIR__ . '/../../models/ApprovalRequest.php';
        }
        $ahInfo = ApprovalRequest::getAccountsHeadApprovalInfo($db, $sourceTable, $sourceId);
    } else {
        $ahInfo = null;
    }
}

// If no approval request found for this entity, only render modals/scripts, skip top card
$renderCard = (!empty($ahInfo) && !empty($ahInfo['has_request']));

$currentUserId = (int)($_SESSION['user']['id'] ?? 0);
$currentUserRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
$currentRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));

$isAccountsHeadUser = ($currentUserRoleId === 30) || strpos($currentRoleName, 'accounts head') !== false;
$isManagementUser = in_array($currentUserRoleId, [1, 3], true) || strpos($currentRoleName, 'super admin') !== false || strpos($currentRoleName, 'admin') !== false;
$isAccountsStaffUser = ($currentUserRoleId === 6) || (strpos($currentRoleName, 'account') !== false && !$isAccountsHeadUser);
$canUserApproveAccountsHead = $isAccountsHeadUser || $isManagementUser;

$isPending = !empty($ahInfo['is_pending_accounts_head']);
$isApproved = !empty($ahInfo['is_accounts_head_approved']);
$isRejected = ($ahInfo['accounts_head_status'] ?? '') === 'rejected';

$designatedName = $ahInfo['designated_official']['name'] ?? 'Md. Mamunoor Rashid';
$designatedEmpId = $ahInfo['designated_official']['employee_id'] ?? '1309';
$designatedDesig = $ahInfo['designated_official']['designation'] ?? 'Assistant General Manager (AGM) - Finance & Accounts';

$approverName = !empty($ahInfo['accounts_head_name']) ? $ahInfo['accounts_head_name'] : $designatedName;
$approverEmpId = !empty($ahInfo['accounts_head_employee_code']) ? $ahInfo['accounts_head_employee_code'] : $designatedEmpId;
$approverDesig = !empty($ahInfo['accounts_head_designation']) ? $ahInfo['accounts_head_designation'] : $designatedDesig;
$approvedAt = !empty($ahInfo['accounts_head_at']) ? date('d M, Y h:i A', strtotime($ahInfo['accounts_head_at'])) : null;
$approvalNote = $ahInfo['accounts_head_note'] ?? null;
?>

<?php if ($renderCard): ?>
<div class="card border shadow-sm mb-4" id="accountsHeadApprovalCard" style="border-radius: 10px; overflow: hidden;">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between py-2 px-3 <?= $isApproved ? 'bg-success-subtle border-success-subtle' : ($isRejected ? 'bg-danger-subtle border-danger-subtle' : 'bg-warning-subtle border-warning-subtle') ?>">
        <div class="d-flex align-items-center gap-2">
            <?php if ($isApproved): ?>
                <span class="badge bg-success text-white rounded-pill px-3 py-1 extra-small fw-bold">
                    <i class="fa-solid fa-circle-check me-1"></i>Accounts Head Approved
                </span>
                <span class="text-success extra-small fw-semibold">Payment Disbursable by Accounts</span>
            <?php elseif ($isRejected): ?>
                <span class="badge bg-danger text-white rounded-pill px-3 py-1 extra-small fw-bold">
                    <i class="fa-solid fa-circle-xmark me-1"></i>Rejected by Accounts Head
                </span>
            <?php else: ?>
                <span class="badge bg-warning text-dark rounded-pill px-3 py-1 extra-small fw-bold">
                    <i class="fa-solid fa-clock me-1"></i>Pending for Accounts Head Approval
                </span>
                <span class="text-dark extra-small fw-semibold">Accounts Payment Locked Pending Approval</span>
            <?php endif; ?>
        </div>
        <div class="extra-small text-muted">
            Stage: <strong>Accounts Head (Role 30)</strong>
        </div>
    </div>

    <div class="card-body p-3">
        <div class="row align-items-center">
            <!-- Official Info Column -->
            <div class="col-md-7 col-12 mb-2 mb-md-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 <?= $isApproved ? 'bg-success text-white' : ($isRejected ? 'bg-danger text-white' : 'bg-warning text-dark') ?>" style="width: 44px; height: 44px; font-size: 18px;">
                        <i class="fa-solid <?= $isApproved ? 'fa-user-check' : ($isRejected ? 'fa-user-xmark' : 'fa-user-shield') ?>"></i>
                    </div>
                    <div>
                        <div class="extra-small text-muted text-uppercase fw-bold">
                            <?= $isApproved ? 'Approved By Accounts Head' : ($isRejected ? 'Rejected By Accounts Head' : 'Designated Accounts Head Approver') ?>
                        </div>
                        <div class="fw-bold text-dark fs-14">
                            <?= htmlspecialchars($isApproved ? $approverName : $designatedName) ?>
                            <span class="badge bg-light text-secondary border extra-small ms-1">
                                ID: <?= htmlspecialchars($isApproved ? $approverEmpId : $designatedEmpId) ?>
                            </span>
                        </div>
                        <div class="extra-small text-muted">
                            <?= htmlspecialchars($isApproved ? $approverDesig : $designatedDesig) ?>
                        </div>
                        <?php if ($isApproved && $approvedAt): ?>
                            <div class="extra-small text-success mt-1">
                                <i class="fa-solid fa-calendar-check me-1"></i>Approved on: <strong><?= $approvedAt ?></strong>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($approvalNote)): ?>
                            <div class="extra-small text-muted fst-italic mt-1">
                                <i class="fa-solid fa-comment-dots me-1"></i>"<?= htmlspecialchars($approvalNote) ?>"
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Action / Status Notice Column -->
            <div class="col-md-5 col-12 text-md-end text-start">
                <?php if ($isPending): ?>
                    <?php if ($canUserApproveAccountsHead): ?>
                        <div class="d-flex flex-wrap gap-2 justify-content-md-end align-items-center">
                            <button type="button" class="btn btn-outline-danger btn-sm px-3 fw-semibold" onclick="triggerAhReject(<?= (int)$ahInfo['approval_request_id'] ?>)">
                                <i class="fa-solid fa-xmark me-1"></i>Reject
                            </button>
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm" id="btnAhApproveDirect" onclick="triggerAhApprove(<?= (int)$ahInfo['approval_request_id'] ?>)">
                                <i class="fa-solid fa-stamp me-1"></i>Approve as Accounts Head
                            </button>
                        </div>
                    <?php elseif ($isAccountsStaffUser): ?>
                        <div class="p-2 bg-light rounded border text-start d-inline-block">
                            <div class="extra-small text-muted fw-bold">
                                <i class="fa-solid fa-lock text-warning me-1"></i>Awaiting Accounts Head Sign-off
                            </div>
                            <div class="extra-small text-secondary">
                                Payment disbursement will unlock once <strong><?= htmlspecialchars($designatedName) ?> [<?= htmlspecialchars($designatedEmpId) ?>]</strong> approves.
                            </div>
                        </div>
                    <?php else: ?>
                        <span class="badge bg-light text-muted border px-3 py-2 extra-small">
                            <i class="fa-solid fa-hourglass-half text-warning me-1"></i>Awaiting Accounts Head Verification
                        </span>
                    <?php endif; ?>
                <?php elseif ($isApproved): ?>
                    <div class="text-md-end text-start">
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 extra-small fw-bold">
                            <i class="fa-solid fa-check-double me-1"></i>Payment Authorized
                        </span>
                        <div class="extra-small text-muted mt-1">
                            Disbursement permitted by Role 6 & 30
                        </div>
                    </div>
                <?php elseif ($isRejected): ?>
                    <div class="text-md-end text-start">
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-2 extra-small fw-bold">
                            <i class="fa-solid fa-ban me-1"></i>Payment Authorization Denied
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ACCOUNTS HEAD DIRECT APPROVAL MODAL -->
<div class="modal fade" id="modalAhApproveDirect" tabindex="-1" aria-labelledby="modalAhApproveDirectLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header bg-success text-white py-2 px-3">
                <h6 class="modal-title fw-bold fs-14 mb-0" id="modalAhApproveDirectLabel">
                    <i class="fa-solid fa-stamp me-2"></i>Accounts Head Approval Verification
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <input type="hidden" id="ahCardApproveReqId" value="">
                
                <!-- Designated Official Profile Card -->
                <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 mb-3 border">
                    <div class="avatar bg-success text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 44px; height: 44px; flex-shrink: 0; font-size: 1.1rem;">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark fs-14"><?= htmlspecialchars($designatedName) ?> <span class="badge bg-success-subtle text-success border border-success-subtle ms-1"><?= htmlspecialchars($designatedEmpId) ?></span></div>
                        <div class="text-muted extra-small"><?= htmlspecialchars($designatedDesig) ?></div>
                        <div class="text-success extra-small fw-semibold mt-1"><i class="fa-solid fa-shield-check me-1"></i>Role 30 · Accounts Head Sign-off</div>
                    </div>
                </div>

                <div class="alert alert-info py-2 px-3 extra-small mb-3 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-circle-info fs-14 text-info"></i>
                    <span>Once approved, Accounts (Role 6) and Accounts Head (Role 30) will be authorized to disburse payment.</span>
                </div>

                <div class="mb-3">
                    <label for="ahCardApproveNote" class="form-label fw-semibold extra-small mb-1">Approval Note (Optional):</label>
                    <textarea class="form-control form-control-sm" id="ahCardApproveNote" rows="3" placeholder="Enter approval note or instructions..."></textarea>
                </div>

                <div id="ahApproveMsg" class="d-none"></div>
            </div>
            <div class="modal-footer py-2 px-3 bg-light border-top-0 d-flex justify-content-between">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success btn-sm fw-bold px-3 shadow-sm" id="btnSubmitAhApprove" onclick="submitAhApproveAction()">
                    <i class="fa-solid fa-check me-1"></i>Approve & Authorize Payment
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ACCOUNTS HEAD DIRECT REJECTION MODAL -->
<div class="modal fade" id="modalAhRejectDirect" tabindex="-1" aria-labelledby="modalAhRejectDirectLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header bg-danger text-white py-2 px-3">
                <h6 class="modal-title fw-bold fs-14 mb-0" id="modalAhRejectDirectLabel">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>Accounts Head Rejection
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <input type="hidden" id="ahCardRejectReqId" value="">
                
                <div class="alert alert-danger py-2 px-3 extra-small mb-3 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation fs-14 text-danger"></i>
                    <span>Rejecting this request will halt accounts payment authorization.</span>
                </div>

                <div class="mb-3">
                    <label for="ahCardRejectNote" class="form-label fw-semibold extra-small mb-1">Rejection Reason <span class="text-danger">*</span>:</label>
                    <textarea class="form-control form-control-sm" id="ahCardRejectNote" rows="3" placeholder="Please enter the reason for rejection..." required></textarea>
                </div>

                <div id="ahRejectMsg" class="d-none"></div>
            </div>
            <div class="modal-footer py-2 px-3 bg-light border-top-0 d-flex justify-content-between">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm fw-bold px-3 shadow-sm" id="btnSubmitAhReject" onclick="submitAhRejectAction()">
                    <i class="fa-solid fa-ban me-1"></i>Reject Request
                </button>
            </div>
        </div>
    </div>
</div>

<script>
window.triggerAhApprove = function(reqId) {
    if (!reqId) return;
    const reqInput = document.getElementById('ahCardApproveReqId');
    if (reqInput) reqInput.value = reqId;
    const noteArea = document.getElementById('ahCardApproveNote');
    if (noteArea) noteArea.value = 'Approved by Accounts Head (Md. Mamunoor Rashid [1309]). Proceed for payment.';
    const msgEl = document.getElementById('ahApproveMsg');
    if (msgEl) { msgEl.className = 'd-none'; msgEl.innerHTML = ''; }
    
    const modalEl = document.getElementById('modalAhApproveDirect');
    if (modalEl) {
        let modal = bootstrap.Modal.getInstance(modalEl);
        if (!modal) modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
};

window.triggerAhReject = function(reqId) {
    if (!reqId) return;
    const reqInput = document.getElementById('ahCardRejectReqId');
    if (reqInput) reqInput.value = reqId;
    const noteArea = document.getElementById('ahCardRejectNote');
    if (noteArea) noteArea.value = '';
    const msgEl = document.getElementById('ahRejectMsg');
    if (msgEl) { msgEl.className = 'd-none'; msgEl.innerHTML = ''; }
    
    const modalEl = document.getElementById('modalAhRejectDirect');
    if (modalEl) {
        let modal = bootstrap.Modal.getInstance(modalEl);
        if (!modal) modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
};

window.submitAhApproveAction = function() {
    const reqId = document.getElementById('ahCardApproveReqId')?.value;
    const note = document.getElementById('ahCardApproveNote')?.value || 'Approved by Accounts Head';
    const btn = document.getElementById('btnSubmitAhApprove');
    const msgEl = document.getElementById('ahApproveMsg');

    if (!reqId) return;
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Approving...';
    }

    fetch('<?= $GLOBALS['base_url'] ?>/approval-requests/approve-accounts-head', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            approval_request_id: reqId,
            note: note
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const modalEl = document.getElementById('modalAhApproveDirect');
            if (modalEl) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Approved!',
                    text: data.message || 'Accounts Head approved successfully.',
                    timer: 1200,
                    showConfirmButton: false
                }).then(() => {
                    window.location.reload();
                });
            } else {
                window.location.reload();
            }
        } else {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check me-1"></i>Approve & Authorize Payment';
            }
            if (msgEl) {
                msgEl.className = 'alert alert-danger py-2 px-3 extra-small mb-0';
                msgEl.innerHTML = '<i class="fa-solid fa-circle-exclamation me-1"></i>' + (data.message || 'Approval failed');
            }
        }
    })
    .catch(err => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check me-1"></i>Approve & Authorize Payment';
        }
        if (msgEl) {
            msgEl.className = 'alert alert-danger py-2 px-3 extra-small mb-0';
            msgEl.innerHTML = '<i class="fa-solid fa-circle-exclamation me-1"></i>Error: ' + err;
        }
    });
};

window.submitAhRejectAction = function() {
    const reqId = document.getElementById('ahCardRejectReqId')?.value;
    const note = document.getElementById('ahCardRejectNote')?.value?.trim();
    const btn = document.getElementById('btnSubmitAhReject');
    const msgEl = document.getElementById('ahRejectMsg');

    if (!reqId) return;
    if (!note) {
        if (msgEl) {
            msgEl.className = 'alert alert-warning py-2 px-3 extra-small mb-0';
            msgEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i>Please provide a rejection reason.';
        }
        return;
    }

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Rejecting...';
    }

    fetch('<?= $GLOBALS['base_url'] ?>/approval-requests/reject-accounts-head', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            approval_request_id: reqId,
            note: note
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const modalEl = document.getElementById('modalAhRejectDirect');
            if (modalEl) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Rejected!',
                    text: data.message || 'Request rejected by Accounts Head.',
                    timer: 1200,
                    showConfirmButton: false
                }).then(() => {
                    window.location.reload();
                });
            } else {
                window.location.reload();
            }
        } else {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-ban me-1"></i>Reject Request';
            }
            if (msgEl) {
                msgEl.className = 'alert alert-danger py-2 px-3 extra-small mb-0';
                msgEl.innerHTML = '<i class="fa-solid fa-circle-exclamation me-1"></i>' + (data.message || 'Rejection failed');
            }
        }
    })
    .catch(err => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-ban me-1"></i>Reject Request';
        }
        if (msgEl) {
            msgEl.className = 'alert alert-danger py-2 px-3 extra-small mb-0';
            msgEl.innerHTML = '<i class="fa-solid fa-circle-exclamation me-1"></i>Error: ' + err;
        }
    });
};
</script>
