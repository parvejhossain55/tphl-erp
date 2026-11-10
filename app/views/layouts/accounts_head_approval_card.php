<?php
/**
 * Accounts Head & Operation Head Approval Status & Action Card Component
 * 
 * Reusable across all 13 modules show/details pages.
 * Displays:
 * 1. For requests > ৳1,00,000: Mandatory Admin & Operation Head (Role 20/32) approval step
 *    prior to Management (Role 3/1) and Accounts Head (Role 30) sign-off.
 * 2. For requests <= ৳1,00,000: Either Role 20 or Role 3 direct approval.
 * 3. Accounts Head (Role 30 - Md. Mamunoor Rashid [1309]) verification status.
 * 4. Locked payment state until Accounts Head approval, with single-click approval/rejection.
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

// If no approval request found for this entity (or explicitly hidden for minimal layout), only render modals/scripts, skip top card
$renderCard = empty($hideAhTopCard) && (!empty($ahInfo) && !empty($ahInfo['has_request']));

if (empty($coaList)) {
    $dbForCoa = $GLOBALS['db'] ?? ($this->db ?? (function_exists('db') ? db() : null));
    if ($dbForCoa && function_exists('getCOAExpenseList')) {
        $coaList = getCOAExpenseList($dbForCoa);
    }
}

$currentUserId = (int)($_SESSION['user']['id'] ?? 0);
$currentUserRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
$currentRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
$currentUserName = strtolower(trim($_SESSION['user']['user_name'] ?? ''));
$currentUserFullName = strtolower(trim($_SESSION['user']['full_name'] ?? ''));

$currentUserSecRoleId = (int)($_SESSION['user']['secondary_role_id'] ?? 0);
$currentUserPriRoleId = (int)($_SESSION['user']['primary_role_id'] ?? 0);

// Role 30 Accounts Head check
$isAccountsHeadUser = in_array(30, [$currentUserRoleId, $currentUserSecRoleId, $currentUserPriRoleId], true) 
    || strpos($currentRoleName, 'accounts head') !== false;

$isManagementUser = in_array($currentUserRoleId, [1, 3, 38], true) 
    || strpos($currentRoleName, 'super admin') !== false 
    || strpos($currentRoleName, 'board of director') !== false 
    || ($currentUserRoleId === 1);

$isAccountsStaffUser = ($currentUserRoleId === 6) 
    || (strpos($currentRoleName, 'account') !== false && !$isAccountsHeadUser && !$isManagementUser);

$isOpHeadUser = in_array($currentUserRoleId, [20, 32], true) || strpos($currentRoleName, 'operation head') !== false || strpos($currentRoleName, 'admin & operation') !== false;

// ONLY Accounts Head (Role 30) or Super Admin (1) can approve as Accounts Head
$canUserApproveAccountsHead = $isAccountsHeadUser || ($currentUserRoleId === 1);
$canUserApproveOpHead = $isOpHeadUser || in_array($currentUserRoleId, [1, 3], true);

$isOver1Lac = !empty($ahInfo['is_over_1lac']);
$isVprOrCbrCard = in_array(strtolower($sourceTable ?? ''), ['vendor_payment_requests', 'contractor_bill_requests'], true);
$requiresOpHeadStage = ($isOver1Lac || $isVprOrCbrCard) && ($sourceTable ?? '') !== 'payslips';
$isReadyForOpHead = isset($ahInfo['is_ready_for_op_head']) ? (bool)$ahInfo['is_ready_for_op_head'] : true;
$reqAmount = (float)($ahInfo['request_amount'] ?? 0);
$reqStatus = strtolower($ahInfo['status'] ?? 'pending');

// Operation Head Stage Status
$opHeadStatus = strtolower($ahInfo['op_head_status'] ?? 'pending');
$isOpHeadApproved = !empty($ahInfo['is_op_head_approved']);
$isOpHeadPending = !empty($ahInfo['is_pending_op_head']);
$isOpHeadRejected = ($opHeadStatus === 'rejected');
// Legacy: Management approved a > 1L request directly (without Op Head) -> Op Head stage bypassed, does not block Accounts Head
$isOpHeadBypassed = !empty($ahInfo['is_op_head_bypassed']);

$opHeadName = !empty($ahInfo['op_head_name']) ? $ahInfo['op_head_name'] : 'Admin & Operation Head';
$opHeadCode = !empty($ahInfo['op_head_code']) ? $ahInfo['op_head_code'] : '';
$opHeadDesig = !empty($ahInfo['op_head_designation']) ? $ahInfo['op_head_designation'] : 'Admin & Operation Head';
$opHeadApprovedAt = !empty($ahInfo['op_head_at']) ? date('d M, Y h:i A', strtotime($ahInfo['op_head_at'])) : null;
$opHeadNote = $ahInfo['op_head_note'] ?? null;

// Accounts Head Stage Status
$isAhPending = !empty($ahInfo['is_pending_accounts_head']);
$isAhApproved = !empty($ahInfo['is_accounts_head_approved']);
$isAhRejected = ($ahInfo['accounts_head_status'] ?? '') === 'rejected';

$designatedName = $ahInfo['designated_name'] ?? 'Accounts Head';
$designatedEmpId = $ahInfo['designated_emp_id'] ?? '';
$designatedDesig = $ahInfo['designated_designation'] ?? 'Accounts Head';

$ahApproverName = !empty($ahInfo['accounts_head_name']) ? $ahInfo['accounts_head_name'] : $designatedName;
$ahApproverEmpId = !empty($ahInfo['accounts_head_employee_code']) ? $ahInfo['accounts_head_employee_code'] : $designatedEmpId;
$ahApproverDesig = !empty($ahInfo['accounts_head_designation']) ? $ahInfo['accounts_head_designation'] : $designatedDesig;
$ahApprovedAt = !empty($ahInfo['accounts_head_at']) ? date('d M, Y h:i A', strtotime($ahInfo['accounts_head_at'])) : null;
$ahApprovalNote = $ahInfo['accounts_head_note'] ?? null;
?>

<?php if ($renderCard): ?>

    <!-- TIER 1: ADMIN & OPERATION HEAD STAGE (DISPLAYED FOR REQUESTS > ৳10,000 OR VENDOR/CONTRACTOR BILLS) -->
    <?php if ($requiresOpHeadStage): ?>
    <div class="card border shadow-sm mb-3" id="operationHeadApprovalCard" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2 py-2.5 px-4 <?= $isOpHeadApproved ? 'bg-success-subtle border-success-subtle' : ($isOpHeadRejected ? 'bg-danger-subtle border-danger-subtle' : 'bg-warning-subtle border-warning-subtle') ?>" style="padding-top: 10px; padding-bottom: 10px;">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="badge bg-dark text-white rounded-pill px-2.5 py-1 extra-small fw-bold">
                    <i class="fa-solid fa-layer-group me-1"></i><?= $isOver1Lac ? 'Step 1 of 3 (> ৳10,000 Flow)' : 'Operation Head Approval (≤ ৳10,000 Flow)' ?>
                </span>
                <?php if ($isOpHeadApproved): ?>
                    <span class="badge bg-success text-white rounded-pill px-3 py-1 extra-small fw-bold">
                        <i class="fa-solid fa-circle-check me-1"></i>Operation Head Approved
                    </span>
                    <span class="text-success extra-small fw-semibold ms-1">Forwarded to Accounts Head (Role 30)</span>
                <?php elseif ($isOpHeadRejected): ?>
                    <span class="badge bg-danger text-white rounded-pill px-3 py-1 extra-small fw-bold">
                        <i class="fa-solid fa-circle-xmark me-1"></i>Rejected by Operation Head
                    </span>
                <?php elseif ($isOpHeadBypassed): ?>
                    <span class="badge bg-secondary text-white rounded-pill px-3 py-1 extra-small fw-bold">
                        <i class="fa-solid fa-forward me-1"></i>Op Head Step Bypassed
                    </span>
                    <span class="text-muted extra-small fw-semibold ms-1">Management approved directly (legacy)</span>
                <?php elseif (!$isReadyForOpHead): ?>
                    <span class="badge bg-secondary text-white rounded-pill px-3 py-1 extra-small fw-bold">
                        <i class="fa-solid fa-lock me-1"></i>Locked — Awaiting QC &amp; Purchase Head
                    </span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark rounded-pill px-3 py-1 extra-small fw-bold">
                        <i class="fa-solid fa-clock me-1"></i>Pending Admin & Operation Head Approval
                    </span>
                    <span class="text-danger extra-small fw-semibold ms-1">Mandatory Step before Accounts Head<?= $isOver1Lac ? ' &amp; Management' : '' ?></span>
                <?php endif; ?>
            </div>
            <div class="extra-small text-muted">
                Amount: <strong class="text-dark">৳<?= number_format($reqAmount, 2) ?> (<?= $isOver1Lac ? '&gt; ৳10,000' : '&le; ৳10,000' ?>)</strong>
            </div>
        </div>

        <div class="card-body py-3 px-4">
            <div class="row align-items-center g-3">
                <!-- Info Column -->
                <div class="col-md-7 col-12">
                    <div class="d-flex align-items-start gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 mt-1 <?= $isOpHeadApproved ? 'bg-success text-white' : ($isOpHeadRejected ? 'bg-danger text-white' : 'bg-warning text-dark') ?>" style="width: 46px; height: 46px; font-size: 18px;">
                            <i class="fa-solid <?= $isOpHeadApproved ? 'fa-user-check' : ($isOpHeadRejected ? 'fa-user-xmark' : 'fa-user-shield') ?>"></i>
                        </div>
                        <div class="lh-base">
                            <div class="extra-small text-muted text-uppercase fw-bold mb-1" style="letter-spacing: 0.3px;">
                                <?= $isOpHeadApproved ? 'Approved By Admin & Operation Head' : ($isOpHeadRejected ? 'Rejected By Admin & Operation Head' : 'Designated Approver (Role 20 / 32)') ?>
                            </div>
                            <div class="fw-bold text-dark fs-14 mb-1 d-flex align-items-center flex-wrap gap-1">
                                <span><?= htmlspecialchars($isOpHeadApproved ? $opHeadName : 'Admin & Operation Head') ?></span>
                                <?php if (!empty($opHeadCode)): ?>
                                    <span class="badge bg-light text-secondary border extra-small ms-1">
                                        ID: <?= htmlspecialchars($opHeadCode) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="extra-small text-muted mb-1">
                                <?= htmlspecialchars($opHeadDesig) ?>
                            </div>
                            <?php if ($isOpHeadApproved && $opHeadApprovedAt): ?>
                                <div class="extra-small text-success mt-1">
                                    <i class="fa-solid fa-calendar-check me-1"></i>Approved on: <strong><?= $opHeadApprovedAt ?></strong>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($opHeadNote)): ?>
                                <div class="extra-small text-muted fst-italic mt-1">
                                    <i class="fa-solid fa-comment-dots me-1"></i>"<?= htmlspecialchars($opHeadNote) ?>"
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Action Column -->
                <div class="col-md-5 col-12 text-md-end text-start">
                    <?php if (!$isReadyForOpHead && !$isOpHeadApproved && !$isOpHeadRejected): ?>
                        <div class="p-2.5 px-3 bg-light rounded-2 border text-start d-inline-block">
                            <div class="extra-small text-muted fw-bold mb-0.5">
                                <i class="fa-solid fa-lock text-warning me-1"></i>Awaiting Previous Approvals
                            </div>
                            <div class="extra-small text-secondary">
                                Requires Head of Purchase &amp; QC Audit approval before Operation Head stage unlocks.
                            </div>
                        </div>
                    <?php elseif ($isOpHeadPending): ?>
                        <?php if ($isOpHeadUser): ?>
                            <div class="d-flex flex-wrap gap-2 justify-content-md-end align-items-center">
                                <button type="button" class="btn btn-outline-danger btn-sm px-3 fw-semibold" onclick="triggerOpHeadReject(<?= (int)$ahInfo['approval_request_id'] ?>)">
                                    <i class="fa-solid fa-xmark me-1"></i>Reject
                                </button>
                                <button type="button" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm" id="btnOpHeadApproveDirect" onclick="triggerOpHeadApprove(<?= (int)$ahInfo['approval_request_id'] ?>)">
                                    <i class="fa-solid fa-stamp me-1"></i>Approve (Operation Head)
                                </button>
                            </div>
                        <?php elseif ($isManagementUser): ?>
                            <div class="p-2.5 px-3 bg-warning-subtle rounded-2 border border-warning text-start d-inline-block">
                                <div class="extra-small text-dark fw-bold mb-0.5">
                                    <i class="fa-solid fa-shield-halved text-warning me-1"></i>Pending Operation Head Approval
                                </div>
                                <div class="extra-small text-secondary">
                                    Awaiting Admin &amp; Operation Head (Role 20) sign-off (or use Management Override below).
                                </div>
                            </div>
                        <?php else: ?>
                            <span class="badge bg-light text-muted border px-3 py-2 extra-small">
                                <i class="fa-solid fa-hourglass-half text-warning me-1"></i>Awaiting Admin &amp; Operation Head Sign-off
                            </span>
                        <?php endif; ?>
                    <?php elseif ($isOpHeadApproved): ?>
                        <div class="text-md-end text-start">
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 extra-small fw-bold">
                                <i class="fa-solid fa-check-double me-1"></i>Step Completed
                            </span>
                            <div class="extra-small text-muted mt-1.5">
                                Forwarded to Accounts Head (Role 30) for payment verification
                            </div>
                        </div>
                    <?php elseif ($isOpHeadRejected): ?>
                        <div class="text-md-end text-start">
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-2 extra-small fw-bold">
                                <i class="fa-solid fa-ban me-1"></i>Rejected by Operation Head
                            </span>
                        </div>
                    <?php elseif ($isOpHeadBypassed): ?>
                        <div class="text-md-end text-start">
                            <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-3 py-2 extra-small fw-bold">
                                <i class="fa-solid fa-forward me-1"></i>Not Required Anymore
                            </span>
                            <div class="extra-small text-muted mt-1.5">
                                Management already approved &mdash; forwarded to Accounts Head
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- TIER 2: ACCOUNTS HEAD APPROVAL CARD (MANDATORY BEFORE PAYMENT DISBURSEMENT) -->
    <div class="card border shadow-sm mb-4" id="accountsHeadApprovalCard" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2 py-2.5 px-4 <?= $isAhApproved ? 'bg-success-subtle border-success-subtle' : ($isAhRejected ? 'bg-danger-subtle border-danger-subtle' : 'bg-warning-subtle border-warning-subtle') ?>" style="padding-top: 10px; padding-bottom: 10px;">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <?php if ($isOver1Lac): ?>
                    <span class="badge bg-dark text-white rounded-pill px-2.5 py-1 extra-small fw-bold">
                        <i class="fa-solid fa-layer-group me-1"></i>Step 2 of 3 (Verification &amp; Approval before Management)
                    </span>
                <?php else: ?>
                    <span class="badge bg-secondary text-white rounded-pill px-2.5 py-1 extra-small fw-bold">
                        <i class="fa-solid fa-shield-halved me-1"></i>Payment Approval (≤ ৳10,000)
                    </span>
                <?php endif; ?>

                <?php if ($isAhApproved): ?>
                    <span class="badge bg-success text-white rounded-pill px-3 py-1 extra-small fw-bold">
                        <i class="fa-solid fa-circle-check me-1"></i>Accounts Head Approved
                    </span>
                    <?php if ($isOver1Lac && !$ahInfo['is_mgmt_approved']): ?>
                        <span class="text-primary extra-small fw-semibold ms-1">Forwarded to Management (Role 1/3) for Sign-off</span>
                    <?php else: ?>
                        <span class="text-success extra-small fw-semibold ms-1">Payment Disbursable by Accounts (Role 6)</span>
                    <?php endif; ?>
                <?php elseif ($isAhRejected): ?>
                    <span class="badge bg-danger text-white rounded-pill px-3 py-1 extra-small fw-bold">
                        <i class="fa-solid fa-circle-xmark me-1"></i>Rejected by Accounts Head
                    </span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark rounded-pill px-3 py-1 extra-small fw-bold">
                        <i class="fa-solid fa-clock me-1"></i>Pending Accounts Head Approval
                    </span>
                    <span class="text-dark extra-small fw-semibold ms-1">Accounts Payment Locked Pending Sign-off</span>
                <?php endif; ?>
            </div>
            <div class="extra-small text-muted">
                Designated: <strong class="text-dark"><?= htmlspecialchars($designatedName) ?> [<?= htmlspecialchars($designatedEmpId) ?>]</strong>
            </div>
        </div>

        <div class="card-body py-3 px-4">
            <div class="row align-items-center g-3">
                <!-- Official Info Column -->
                <div class="col-md-7 col-12">
                    <div class="d-flex align-items-start gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 mt-1 <?= $isAhApproved ? 'bg-success text-white' : ($isAhRejected ? 'bg-danger text-white' : 'bg-warning text-dark') ?>" style="width: 46px; height: 46px; font-size: 18px;">
                            <i class="fa-solid <?= $isAhApproved ? 'fa-user-check' : ($isAhRejected ? 'fa-user-xmark' : 'fa-user-shield') ?>"></i>
                        </div>
                        <div class="lh-base">
                            <div class="extra-small text-muted text-uppercase fw-bold mb-1" style="letter-spacing: 0.3px;">
                                <?= $isAhApproved ? 'Approved By Accounts Head' : ($isAhRejected ? 'Rejected By Accounts Head' : 'Designated Accounts Head Approver') ?>
                            </div>
                            <div class="fw-bold text-dark fs-14 mb-1 d-flex align-items-center flex-wrap gap-1">
                                <span><?= htmlspecialchars($isAhApproved ? $ahApproverName : $designatedName) ?></span>
                                <span class="badge bg-light text-secondary border extra-small ms-1">
                                    ID: <?= htmlspecialchars($isAhApproved ? $ahApproverEmpId : $designatedEmpId) ?>
                                </span>
                            </div>
                            <div class="extra-small text-muted mb-1">
                                <?= htmlspecialchars($isAhApproved ? $ahApproverDesig : $designatedDesig) ?>
                            </div>
                            <?php if ($isAhApproved && $ahApprovedAt): ?>
                                <div class="extra-small text-success mt-1">
                                    <i class="fa-solid fa-calendar-check me-1"></i>Approved on: <strong><?= $ahApprovedAt ?></strong>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($ahApprovalNote)): ?>
                                <div class="extra-small text-muted fst-italic mt-1">
                                    <i class="fa-solid fa-comment-dots me-1"></i>"<?= htmlspecialchars($ahApprovalNote) ?>"
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Action / Status Notice Column -->
                <div class="col-md-5 col-12 text-md-end text-start">
                    <?php if ($requiresOpHeadStage && !$isOpHeadApproved && !$isOpHeadBypassed): ?>
                        <div class="p-2.5 px-3 bg-light rounded-2 border text-start d-inline-block">
                            <div class="extra-small text-muted fw-bold mb-0.5">
                                <i class="fa-solid fa-lock text-warning me-1"></i>Awaiting Previous Approvals
                            </div>
                            <div class="extra-small text-secondary">
                                Requires Admin &amp; Operation Head (Role 20) sign-off before Accounts Head stage unlocks.
                            </div>
                        </div>
                    <?php elseif ($isAhPending): ?>
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
                            <div class="p-2.5 px-3 bg-light rounded-2 border text-start d-inline-block">
                                <div class="extra-small text-muted fw-bold mb-0.5">
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
                    <?php elseif ($isAhApproved): ?>
                        <div class="text-md-end text-start">
                            <?php if ($isOver1Lac && empty($ahInfo['is_mgmt_approved'])): ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2 extra-small fw-bold">
                                    <i class="fa-solid fa-check-double me-1"></i>Step 2 Completed
                                </span>
                                <div class="extra-small text-muted mt-1.5">
                                    Forwarded to Management (Role 1/3) for Executive Approval
                                </div>
                            <?php else: ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 extra-small fw-bold">
                                    <i class="fa-solid fa-check-double me-1"></i>Payment Authorized
                                </span>
                                <div class="extra-small text-muted mt-1.5">
                                    Disbursement permitted by Role 6 &amp; 30
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($isAhRejected): ?>
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

<!-- OPERATION HEAD DIRECT APPROVAL MODAL -->
<div class="modal fade" id="modalOpHeadApproveDirect" tabindex="-1" aria-labelledby="modalOpHeadApproveDirectLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header bg-primary text-white py-2 px-3">
                <h6 class="modal-title fw-bold fs-14 mb-0" id="modalOpHeadApproveDirectLabel">
                    <i class="fa-solid fa-shield-check me-2"></i>Admin &amp; Operation Head Approval (> ৳10,000)
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <input type="hidden" id="opHeadCardApproveReqId" value="">
                
                <div class="alert alert-info py-2 px-3 extra-small mb-3 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-circle-info fs-14 text-primary"></i>
                    <span>This request exceeds ৳10,000. Your approval will advance it to Accounts Head (Role 30) for payment verification.</span>
                </div>

                <div class="mb-3">
                    <label for="opHeadCardApproveNote" class="form-label fw-semibold extra-small mb-1">Approval Note (Optional):</label>
                    <textarea class="form-control form-control-sm" id="opHeadCardApproveNote" rows="3" placeholder="Enter approval note or instructions..."></textarea>
                </div>

                <div id="opHeadApproveMsg" class="d-none"></div>
            </div>
            <div class="modal-footer py-2 px-3 bg-light border-top-0 d-flex justify-content-between">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm fw-bold px-3 shadow-sm" id="btnSubmitOpHeadApprove" onclick="submitOpHeadApproveAction()">
                    <i class="fa-solid fa-check me-1"></i>Confirm &amp; Forward to Accounts Head
                </button>
            </div>
        </div>
    </div>
</div>

<!-- OPERATION HEAD DIRECT REJECTION MODAL -->
<div class="modal fade" id="modalOpHeadRejectDirect" tabindex="-1" aria-labelledby="modalOpHeadRejectDirectLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header bg-danger text-white py-2 px-3">
                <h6 class="modal-title fw-bold fs-14 mb-0" id="modalOpHeadRejectDirectLabel">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>Operation Head Rejection
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <input type="hidden" id="opHeadCardRejectReqId" value="">
                
                <div class="alert alert-danger py-2 px-3 extra-small mb-3 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation fs-14 text-danger"></i>
                    <span>Rejecting this request will halt the approval process.</span>
                </div>

                <div class="mb-3">
                    <label for="opHeadCardRejectNote" class="form-label fw-semibold extra-small mb-1">Rejection Reason <span class="text-danger">*</span>:</label>
                    <textarea class="form-control form-control-sm" id="opHeadCardRejectNote" rows="3" placeholder="Please enter the reason for rejection..." required></textarea>
                </div>

                <div id="opHeadRejectMsg" class="d-none"></div>
            </div>
            <div class="modal-footer py-2 px-3 bg-light border-top-0 d-flex justify-content-between">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm fw-bold px-3 shadow-sm" id="btnSubmitOpHeadReject" onclick="submitOpHeadRejectAction()">
                    <i class="fa-solid fa-ban me-1"></i>Reject Request
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ACCOUNTS HEAD DIRECT APPROVAL MODAL -->
<div class="modal fade" id="modalAhApproveDirect" tabindex="-1" aria-labelledby="modalAhApproveDirectLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header bg-success text-white py-2 px-3">
                <h6 class="modal-title fw-bold fs-14 mb-0" id="modalAhApproveDirectLabel">
                    <i class="fa-solid fa-stamp me-2"></i>Accounts Head Approval
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <input type="hidden" id="ahCardApproveReqId" value="">
                
                <!-- Requisition / Request Title & Approved Amount -->
                <div class="mb-3 p-3 bg-light rounded-3 border">
                    <div class="text-muted extra-small text-uppercase fw-bold mb-1">Requisition / Request Title</div>
                    <div class="fw-bold text-dark fs-14" id="ahCardReqTitle">Sales Incentive Claim</div>
                    <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                        <span class="text-muted extra-small">Approved Amount:</span>
                        <span class="fw-bold text-success fs-15" id="ahCardReqAmount">--</span>
                    </div>
                </div>

                <!-- Designated Official Profile Card -->
                <div class="d-flex align-items-center gap-3 p-2 bg-info-subtle rounded-3 mb-3 border border-info-subtle">
                    <div class="avatar bg-info text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px; flex-shrink: 0;">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                    <div>
                        <div class="text-muted extra-small text-uppercase fw-bold">Designated Approver:</div>
                        <div class="fw-bold text-dark fs-13"><?= htmlspecialchars($designatedName) ?> <span class="badge bg-success text-white ms-1"><?= htmlspecialchars($designatedEmpId) ?></span> (<?= htmlspecialchars($designatedDesig) ?>)</div>
                    </div>
                </div>

                <!-- Chart of Accounts Category -->
                <div class="mb-3">
                    <label for="ahCardCoaId" class="form-label extra-small fw-bold text-muted text-uppercase">
                        Chart of Accounts Category <span class="text-danger">(Required for posting)</span>
                    </label>
                    <select class="form-select form-select-sm" id="ahCardCoaId">
                        <option value="">-- Select Expense / Asset Ledger --</option>
                        <?php if(!empty($coaList)): ?>
                            <?php 
                                $currentGroup = '';
                                foreach($coaList as $coa): 
                                    $group = !empty($coa['parent_name']) ? $coa['parent_name'] : ($coa['account_type'] ?? 'Expense');
                                    if($group !== $currentGroup):
                                        if($currentGroup !== '') echo '</optgroup>';
                                        echo '<optgroup label="'.htmlspecialchars($group).'">';
                                        $currentGroup = $group;
                                    endif;
                            ?>
                                <option value="<?= $coa['id'] ?>"><?= htmlspecialchars($coa['account_code'] . ' - ' . $coa['account_name']) ?></option>
                            <?php endforeach; ?>
                            <?php if($currentGroup !== '') echo '</optgroup>'; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="ahCardApproveNote" class="form-label extra-small fw-bold text-muted text-uppercase">Approval / Rejection Note (Optional)</label>
                    <textarea class="form-control form-control-sm" id="ahCardApproveNote" rows="2" placeholder="e.g. Verified and approved for payment disbursement."></textarea>
                </div>

                <div id="ahApproveMsg" class="d-none"></div>
            </div>
            <div class="modal-footer py-2 px-3 bg-light border-top-0 d-flex justify-content-between">
                <button type="button" class="btn btn-outline-danger btn-sm px-3" onclick="submitAhRejectAction()">
                    <i class="fa-solid fa-times me-1"></i>Reject Request
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm" id="btnSubmitAhApprove" onclick="submitAhApproveAction()">
                        <i class="fa-solid fa-check me-1"></i>Approve as Accounts Head
                    </button>
                </div>
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
// --- Operation Head Actions ---
window.triggerOpHeadApprove = function(reqId) {
    if (!reqId) return;
    const reqInput = document.getElementById('opHeadCardApproveReqId');
    if (reqInput) reqInput.value = reqId;
    const noteArea = document.getElementById('opHeadCardApproveNote');
    if (noteArea) noteArea.value = 'Approved by Admin & Operation Head. Forwarded to Accounts Head for payment verification.';
    const msgEl = document.getElementById('opHeadApproveMsg');
    if (msgEl) { msgEl.className = 'd-none'; msgEl.innerHTML = ''; }
    
    const modalEl = document.getElementById('modalOpHeadApproveDirect');
    if (modalEl) {
        let modal = bootstrap.Modal.getInstance(modalEl);
        if (!modal) modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
};

window.triggerOpHeadReject = function(reqId) {
    if (!reqId) return;
    const reqInput = document.getElementById('opHeadCardRejectReqId');
    if (reqInput) reqInput.value = reqId;
    const noteArea = document.getElementById('opHeadCardRejectNote');
    if (noteArea) noteArea.value = '';
    const msgEl = document.getElementById('opHeadRejectMsg');
    if (msgEl) { msgEl.className = 'd-none'; msgEl.innerHTML = ''; }
    
    const modalEl = document.getElementById('modalOpHeadRejectDirect');
    if (modalEl) {
        let modal = bootstrap.Modal.getInstance(modalEl);
        if (!modal) modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
};

window.submitOpHeadApproveAction = function() {
    const reqId = document.getElementById('opHeadCardApproveReqId')?.value;
    const note = document.getElementById('opHeadCardApproveNote')?.value || 'Approved by Admin & Operation Head';
    const btn = document.getElementById('btnSubmitOpHeadApprove');
    const msgEl = document.getElementById('opHeadApproveMsg');

    if (!reqId) return;
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Processing...';
    }

    fetch('<?= $GLOBALS['base_url'] ?>/approval-requests/approve-operation-head', {
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
            const modalEl = document.getElementById('modalOpHeadApproveDirect');
            if (modalEl) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Approved!',
                    text: data.message || 'Operation Head approval recorded successfully.',
                    timer: 1400,
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
                btn.innerHTML = '<i class="fa-solid fa-check me-1"></i>Confirm & Forward to Accounts Head';
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
            btn.innerHTML = '<i class="fa-solid fa-check me-1"></i>Confirm & Forward to Accounts Head';
        }
        if (msgEl) {
            msgEl.className = 'alert alert-danger py-2 px-3 extra-small mb-0';
            msgEl.innerHTML = '<i class="fa-solid fa-circle-exclamation me-1"></i>Error: ' + err;
        }
    });
};

window.submitOpHeadRejectAction = function() {
    const reqId = document.getElementById('opHeadCardRejectReqId')?.value;
    const note = document.getElementById('opHeadCardRejectNote')?.value?.trim();
    const btn = document.getElementById('btnSubmitOpHeadReject');
    const msgEl = document.getElementById('opHeadRejectMsg');

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

    fetch('<?= $GLOBALS['base_url'] ?>/approval-requests/reject-operation-head', {
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
            const modalEl = document.getElementById('modalOpHeadRejectDirect');
            if (modalEl) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Rejected!',
                    text: data.message || 'Request rejected by Operation Head.',
                    timer: 1400,
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

// --- Accounts Head Actions ---
window.triggerAhApprove = function(reqId, title = '', amount = '') {
    if (!reqId) return;
    const reqInput = document.getElementById('ahCardApproveReqId');
    if (reqInput) reqInput.value = reqId;

    const titleEl = document.getElementById('ahCardReqTitle');
    if (titleEl) titleEl.innerText = title || ('Requisition / Approval Request #' + reqId);

    const amountEl = document.getElementById('ahCardReqAmount');
    if (amountEl) amountEl.innerText = amount ? ('৳ ' + amount) : '--';

    const noteArea = document.getElementById('ahCardApproveNote');
    if (noteArea) noteArea.value = 'Approved by Accounts Head. Proceed for payment.';

    const coaSelect = document.getElementById('ahCardCoaId');
    if (coaSelect) coaSelect.value = '';

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
    const reqInput = document.getElementById('ahCardRejectReqId') || document.getElementById('ahCardApproveReqId');
    if (reqInput) reqInput.value = reqId;
    submitAhRejectAction();
};

window.submitAhApproveAction = function() {
    const reqId = document.getElementById('ahCardApproveReqId')?.value;
    const note = document.getElementById('ahCardApproveNote')?.value || 'Approved by Accounts Head';
    const coaId = document.getElementById('ahCardCoaId')?.value || null;
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
            note: note,
            coa_id: coaId
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
                btn.innerHTML = '<i class="fa-solid fa-check me-1"></i>Approve as Accounts Head';
            }
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Approval Failed',
                    text: data.message || 'Approval failed'
                });
            } else if (msgEl) {
                msgEl.className = 'alert alert-danger py-2 px-3 extra-small mb-0';
                msgEl.innerHTML = '<i class="fa-solid fa-circle-exclamation me-1"></i>' + (data.message || 'Approval failed');
            }
        }
    })
    .catch(err => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check me-1"></i>Approve as Accounts Head';
        }
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: err.toString()
            });
        } else if (msgEl) {
            msgEl.className = 'alert alert-danger py-2 px-3 extra-small mb-0';
            msgEl.innerHTML = '<i class="fa-solid fa-circle-exclamation me-1"></i>Error: ' + err;
        }
    });
};

window.submitAhRejectAction = function() {
    const reqId = document.getElementById('ahCardApproveReqId')?.value || document.getElementById('ahCardRejectReqId')?.value;
    const note = document.getElementById('ahCardApproveNote')?.value || document.getElementById('ahCardRejectNote')?.value || 'Rejected by Accounts Head';

    if (!reqId) return;

    const executeReject = () => {
        const btn = document.getElementById('btnSubmitAhApprove');
        if (btn) btn.disabled = true;

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
                const modalEl = document.getElementById('modalAhApproveDirect');
                if (modalEl) {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Rejected!',
                        text: data.message || 'Request rejected by Accounts Head.',
                        timer: 1400,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    window.location.reload();
                }
            } else {
                if (btn) btn.disabled = false;
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Rejection Failed',
                        text: data.message || 'Rejection failed'
                    });
                } else {
                    alert(data.message || 'Rejection failed');
                }
            }
        })
        .catch(err => {
            if (btn) btn.disabled = false;
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: err.toString()
                });
            } else {
                alert('Error: ' + err);
            }
        });
    };

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Confirm Rejection',
            text: 'Are you sure you want to REJECT this request as Accounts Head?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Reject Request'
        }).then((result) => {
            if (result.isConfirmed) {
                executeReject();
            }
        });
    } else if (confirm('Are you sure you want to REJECT this request as Accounts Head?')) {
        executeReject();
    }
};
</script>
