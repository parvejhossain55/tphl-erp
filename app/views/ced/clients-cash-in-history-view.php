<?php
$page_title = "Clients Cash in History";
$page = 'Clients';
$show_breadcrumb = true;

// Check if there is an error message
if (isset($error_message) && !empty($error_message)):
?>
    <div class="row">
        <div class="col-xxl-12 mb-4">
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="fa-light fa-exclamation-triangle fa-2x me-3"></i>
                <div>
                    <h4 class="alert-heading mb-1">Error Loading Transaction History</h4>
                    <p class="mb-0"><?= htmlspecialchars($error_message) ?></p>
                </div>
            </div>
            <a href="<?= $GLOBALS['base_url'] ?>/clients-cashin-view" class="btn btn-primary">
                <i class="fa-light fa-arrow-left me-2"></i> Back to Clients
            </a>
        </div>
    </div>
<?php else: ?>

<?php
// Check if land share is completed (enables Add Transaction for dev charges & EMI)
$accountModel = new AccountClient();
$isLandShareCompleted = $paymentSummary['is_land_share_completed'] ?? false;

$userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
$userRoleName = strtolower($_SESSION['user']['role_name'] ?? '');
$isManagementUser = ($userRoleId === 1 || $userRoleId === 3 || strpos($userRoleName, 'management') !== false || strpos($userRoleName, 'admin') !== false);
$bookingId = $clientDetails['booking_id'] ?? (isset($_GET['booking_id']) ? $_GET['booking_id'] : 0);
$canEditBookingInfo = in_array($userRoleId, [1, 3, 6, 20, 30, 32, 33, 36]) 
                    || (function_exists('isAccountsHead') && isAccountsHead()) 
                    || (function_exists('isAccountsExecutive') && isAccountsExecutive())
                    || strpos($userRoleName, 'account') !== false 
                    || strpos($userRoleName, 'admin') !== false;
?>

<?php
// Fallback check for discount approval request if not passed directly from controller
if (!isset($discountApprovalReq)) {
    $discountApprovalReq = null;
    $lookupBookingId = (int)($clientDetails['booking_id'] ?? $bookingId ?? 0);
    if ($lookupBookingId > 0) {
        $dbConn = isset($db) ? $db : (isset($accountModel) ? $accountModel->getDb() : null);
        if ($dbConn) {
            $arStmt = $dbConn->prepare("
                SELECT ar.*,
                       u_req.full_name AS requester_name, e_req.employee_id AS requester_code,
                       u_op.full_name AS op_head_name, e_op.employee_id AS op_head_code,
                       u_app.full_name AS approver_name
                FROM approval_requests ar
                LEFT JOIN users u_req ON ar.requested_by = u_req.id
                LEFT JOIN employees e_req ON (e_req.user_id = u_req.id OR e_req.id = u_req.employee_id)
                LEFT JOIN users u_op ON ar.op_head_by = u_op.id
                LEFT JOIN employees e_op ON (e_op.user_id = u_op.id OR e_op.id = u_op.employee_id)
                LEFT JOIN users u_app ON ar.approved_by = u_app.id
                WHERE ar.source_table = 'lead_bookings' AND ar.source_id = ?
                ORDER BY ar.id DESC LIMIT 1
            ");
            if ($arStmt) {
                $arStmt->bind_param("i", $lookupBookingId);
                $arStmt->execute();
                $discountApprovalReq = $arStmt->get_result()->fetch_assoc();
                $arStmt->close();
            }
        }
    }
}
?>

<div class="row">
    <?php if (!empty($discountApprovalReq)): 
        $discReqAmount = (float)($discountApprovalReq['request_amount'] ?? 0);
        $discIsOver1Lac = ($discReqAmount > 100000.01);
        $discOpHeadStatus = strtolower($discountApprovalReq['op_head_status'] ?? '');
        $discStatus = strtolower($discountApprovalReq['status'] ?? 'pending');
        $isRole20OrOpHead = in_array($userRoleId, [20, 32], true) || strpos($userRoleName, 'operation head') !== false || strpos($userRoleName, 'admin & operation') !== false;
        $isMgmtUser = in_array($userRoleId, [1, 3], true) || strpos($userRoleName, 'super admin') !== false || strpos($userRoleName, 'admin') !== false || strpos($userRoleName, 'management') !== false;
        $discAttachment = !empty($discountApprovalReq['attachment']) ? $discountApprovalReq['attachment'] : ($clientDetails['past_approval_attachment'] ?? '');
    ?>
    <!-- Booking Discount Approval Banner -->
    <div class="col-xxl-12 mb-4" id="bookingDiscountApprovalBanner">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden" style="background: #ffffff; border-left: 5px solid <?= $discStatus === 'approved' ? '#10b981' : ($discStatus === 'rejected' ? '#ef4444' : '#f59e0b') ?> !important;">
            <div class="card-header py-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-2" style="background: linear-gradient(135deg, <?= $discStatus === 'approved' ? '#ecfdf5 0%, #f0fdf4 100%' : ($discStatus === 'rejected' ? '#fef2f2 0%, #fff1f2 100%' : '#fffbeb 0%, #fef3c7 100%') ?>); border-bottom: 1px solid rgba(0,0,0,0.06);">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-sm rounded-circle d-flex align-items-center justify-content-center text-white" style="background: <?= $discStatus === 'approved' ? '#10b981' : ($discStatus === 'rejected' ? '#ef4444' : '#f59e0b') ?>; width: 34px; height: 34px;">
                        <i class="fa-solid fa-tags"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold text-dark fs-15">
                            Booking Discount Approval Request
                            <span class="badge bg-light text-secondary border ms-1 extra-small">#REQ-<?= str_pad($discountApprovalReq['id'], 5, '0', STR_PAD_LEFT) ?></span>
                        </h5>
                        <small class="text-muted">Requested on <?= date('d M, Y h:i A', strtotime($discountApprovalReq['created_at'])) ?><?= !empty($discountApprovalReq['requester_name']) ? (' by ' . htmlspecialchars($discountApprovalReq['requester_name'])) : '' ?></small>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <?php if ($discStatus === 'pending'): ?>
                        <?php if ($discIsOver1Lac): ?>
                            <?php if ($discOpHeadStatus !== 'approved'): ?>
                                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold" style="font-size: 12px;">
                                    <i class="fa-solid fa-shield-halved me-1"></i> Tier 1 Pending: Admin & Operation Head (> ৳1,00,000)
                                </span>
                            <?php else: ?>
                                <span class="badge bg-primary text-white px-3 py-2 rounded-pill fw-bold" style="font-size: 12px;">
                                    <i class="fa-solid fa-hourglass-half me-1"></i> Tier 2 Pending: Top Management Sign-Off
                                </span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold" style="font-size: 12px;">
                                <i class="fa-solid fa-hourglass-half me-1"></i> Pending Management / Operation Head
                            </span>
                        <?php endif; ?>
                    <?php elseif ($discStatus === 'approved'): ?>
                        <span class="badge bg-success text-white px-3 py-2 rounded-pill fw-bold" style="font-size: 12px;">
                            <i class="fa-solid fa-check-double me-1"></i> Approved
                        </span>
                    <?php elseif ($discStatus === 'rejected'): ?>
                        <span class="badge bg-danger text-white px-3 py-2 rounded-pill fw-bold" style="font-size: 12px;">
                            <i class="fa-solid fa-times me-1"></i> Rejected
                        </span>
                    <?php endif; ?>

                    <?php if (isset($_GET['ref']) && $_GET['ref'] === 'all_approvals'): ?>
                        <a href="<?= $GLOBALS['base_url'] ?>/all-approval-requests" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1">
                            <i class="fa-solid fa-arrow-left me-1"></i> Back to Approvals
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="row g-4 align-items-center">
                    <!-- Col 1: Financial & Discount Details -->
                    <div class="col-lg-4 col-md-6 border-end">
                        <div class="extra-small text-muted text-uppercase fw-bold mb-1">Discount Amount Requested</div>
                        <div class="d-flex align-items-baseline gap-2 mb-2">
                            <span class="fs-3 fw-bold text-success">৳ <?= number_format($discReqAmount, 2) ?></span>
                            <?php if ($discIsOver1Lac): ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle extra-small fw-semibold">> ৳1,00,000 Threshold</span>
                            <?php else: ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle extra-small fw-semibold">Standard Threshold</span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($discAttachment)): 
                            $cleanAttPath = ltrim($discAttachment, '/');
                            $fullAttUrl = $GLOBALS['base_url'] . '/' . $cleanAttPath;
                        ?>
                            <div class="mt-2 p-2 bg-light rounded-3 border d-flex align-items-center justify-content-between">
                                <span class="extra-small text-dark fw-bold"><i class="fa-solid fa-paperclip text-success me-1"></i> Supporting Document:</span>
                                <a href="<?= $fullAttUrl ?>" target="_blank" class="btn btn-xs btn-outline-primary py-0 px-2 extra-small fw-semibold">
                                    <i class="fa-solid fa-up-right-from-square me-1"></i> View Attachment
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Col 2: 2-Tier Approval Workflow Timeline -->
                    <div class="col-lg-4 col-md-6 border-end">
                        <div class="extra-small text-muted text-uppercase fw-bold mb-2 pb-1 border-bottom">Approval Progress Status</div>
                        
                        <!-- Tier 1 -->
                        <div class="d-flex align-items-start gap-2 mb-2">
                            <div class="mt-1">
                                <?php if ($discOpHeadStatus === 'approved'): ?>
                                    <i class="fa-solid fa-circle-check text-success fs-15"></i>
                                <?php elseif ($discStatus === 'rejected'): ?>
                                    <i class="fa-solid fa-circle-xmark text-danger fs-15"></i>
                                <?php else: ?>
                                    <i class="fa-solid fa-circle-dot text-warning fs-15"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <span class="fw-bold extra-small text-dark d-block">Tier 1: Admin & Operation Head (Role 20)</span>
                                <?php if ($discOpHeadStatus === 'approved'): ?>
                                    <span class="text-success extra-small d-block">
                                        Approved by <?= htmlspecialchars($discountApprovalReq['op_head_name'] ?? 'Op Head') ?>
                                        <?= !empty($discountApprovalReq['op_head_at']) ? (' on ' . date('d M, Y h:i A', strtotime($discountApprovalReq['op_head_at']))) : '' ?>
                                    </span>
                                    <?php if (!empty($discountApprovalReq['op_head_note'])): ?>
                                        <small class="text-muted fst-italic">"<?= htmlspecialchars($discountApprovalReq['op_head_note']) ?>"</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-warning extra-small d-block">Awaiting Verification & Approval</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Tier 2 -->
                        <div class="d-flex align-items-start gap-2">
                            <div class="mt-1">
                                <?php if ($discStatus === 'approved'): ?>
                                    <i class="fa-solid fa-circle-check text-success fs-15"></i>
                                <?php elseif ($discStatus === 'rejected'): ?>
                                    <i class="fa-solid fa-circle-xmark text-danger fs-15"></i>
                                <?php elseif ($discIsOver1Lac && $discOpHeadStatus !== 'approved'): ?>
                                    <i class="fa-solid fa-lock text-muted fs-15"></i>
                                <?php else: ?>
                                    <i class="fa-solid fa-circle-dot text-warning fs-15"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <span class="fw-bold extra-small text-dark d-block">Tier 2: Top Management Sign-Off (Role 3 / 1)</span>
                                <?php if ($discStatus === 'approved'): ?>
                                    <span class="text-success extra-small d-block">
                                        Approved by <?= htmlspecialchars($discountApprovalReq['approver_name'] ?? 'Management') ?>
                                        <?= !empty($discountApprovalReq['approved_at']) ? (' on ' . date('d M, Y h:i A', strtotime($discountApprovalReq['approved_at']))) : '' ?>
                                    </span>
                                <?php elseif ($discIsOver1Lac && $discOpHeadStatus !== 'approved'): ?>
                                    <span class="text-muted extra-small d-block">Awaiting Tier 1 Op Head sign-off first</span>
                                <?php else: ?>
                                    <span class="text-primary extra-small d-block">Ready for Management Final Sign-off</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Col 3: Direct Action Panel -->
                    <div class="col-lg-4 col-md-12">
                        <?php if ($discStatus === 'pending'): ?>
                            <?php if ($isRole20OrOpHead && (!$discIsOver1Lac || $discOpHeadStatus !== 'approved')): ?>
                                <div class="p-3 bg-light rounded-3 border">
                                    <label for="cedCardDiscountNote" class="form-label extra-small fw-bold text-dark text-uppercase mb-1">
                                        <i class="fa-solid fa-pen-nib me-1 text-primary"></i> Op Head Verification Note
                                    </label>
                                    <input type="text" id="cedCardDiscountNote" class="form-control form-control-sm mb-2" placeholder="e.g. Verified client booking & discount terms.">
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-danger px-3 fw-bold" onclick="cedExecuteDiscountAction('reject', 'op_head', <?= (int)$discountApprovalReq['id'] ?>, <?= (int)$discountApprovalReq['source_id'] ?>)">
                                            <i class="fa-solid fa-times me-1"></i> Reject
                                        </button>
                                        <button type="button" class="btn btn-sm btn-success px-3 fw-bold flex-grow-1" id="cedCardDiscountSubmitBtn" onclick="cedExecuteDiscountAction('approve', 'op_head', <?= (int)$discountApprovalReq['id'] ?>, <?= (int)$discountApprovalReq['source_id'] ?>)">
                                            <i class="fa-solid fa-shield-check me-1"></i> <?= $discIsOver1Lac ? 'Verify & Forward to Mgmt' : 'Approve Discount' ?>
                                        </button>
                                    </div>
                                </div>
                            <?php elseif ($isMgmtUser): ?>
                                <?php if ($discIsOver1Lac && $discOpHeadStatus !== 'approved'): ?>
                                    <div class="alert alert-warning py-3 px-3 extra-small mb-0 border-warning-subtle">
                                        <i class="fa-solid fa-lock me-1"></i> <strong>Awaiting Op Head Verification:</strong> This discount exceeds ৳1,00,000. Admin & Operation Head (Role 20) must verify and approve it first before Management final sign-off.
                                    </div>
                                <?php else: ?>
                                    <div class="p-3 bg-light rounded-3 border">
                                        <label for="cedCardDiscountNote" class="form-label extra-small fw-bold text-dark text-uppercase mb-1">
                                            <i class="fa-solid fa-stamp me-1 text-success"></i> Management Sign-off Note
                                        </label>
                                        <input type="text" id="cedCardDiscountNote" class="form-control form-control-sm mb-2" placeholder="e.g. Approved discount for client.">
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-danger px-3 fw-bold" onclick="cedExecuteDiscountAction('reject', 'mgmt', <?= (int)$discountApprovalReq['id'] ?>, <?= (int)$discountApprovalReq['source_id'] ?>)">
                                                <i class="fa-solid fa-times me-1"></i> Reject
                                            </button>
                                            <button type="button" class="btn btn-sm btn-success px-3 fw-bold flex-grow-1" id="cedCardDiscountSubmitBtn" onclick="cedExecuteDiscountAction('approve', 'mgmt', <?= (int)$discountApprovalReq['id'] ?>, <?= (int)$discountApprovalReq['source_id'] ?>)">
                                                <i class="fa-solid fa-check-double me-1"></i> Approve Discount (Management)
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="alert alert-secondary py-3 px-3 extra-small mb-0 border">
                                    <i class="fa-solid fa-info-circle me-1"></i> Approval action is awaiting Admin & Operation Head / Management sign-off.
                                </div>
                            <?php endif; ?>
                        <?php elseif ($discStatus === 'approved'): ?>
                            <div class="alert alert-success py-3 px-3 extra-small mb-0 border-success-subtle">
                                <i class="fa-solid fa-circle-check me-1 fs-14"></i>
                                <strong>Discount Finalized:</strong> TK. <?= number_format($discountApprovalReq['approved_amount'] ?? $discReqAmount, 2) ?> approved by <?= htmlspecialchars($discountApprovalReq['approver_name'] ?? 'Management') ?>.
                            </div>
                        <?php elseif ($discStatus === 'rejected'): ?>
                            <div class="alert alert-danger py-3 px-3 extra-small mb-0 border-danger-subtle">
                                <i class="fa-solid fa-circle-xmark me-1 fs-14"></i>
                                <strong>Discount Rejected:</strong> This discount request was rejected.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Client Header Section -->
    <div class="col-xxl-12 mb-4">
        <div class="card__wrapper">
            <div class="card__body">
                <h3 class="mb-2 card__icon">
                    <i class="fa-light fa-user-circle me-2"></i>
                    <?= htmlspecialchars($clientDetails['client_name'] ?? 'N/A') ?>
                </h3>
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    <span class="text-muted">
                        <i class="fa-light fa-building mx-2"></i>
                        <strong>Project:</strong> <?= htmlspecialchars($clientDetails['project_name'] ?? 'N/A') ?>
                    </span>
                    <span class="text-muted">
                        <i class="fa-light fa-home mx-2"></i>
                        <strong>Flat:</strong> UNIT <?= htmlspecialchars($clientDetails['unit_name'] ?? '') ?> - FLOOR <?= htmlspecialchars($clientDetails['flat_no'] ?? 'N/A') ?>
                    </span>
                    <span class="text-muted">
                        <i class="fa-light fa-phone mx-2"></i>
                        <strong>Phone:</strong> <?= htmlspecialchars($clientDetails['phone'] ?? 'N/A') ?>
                    </span>
                    <span class="text-muted">
                        <i class="fa-light fa-calendar-check mx-2"></i>
                        <strong>Booking Date:</strong> <span class="fw-semibold text-dark" id="cedDisplayBookingDate"><?= !empty($clientDetails['booking_date']) ? date('d M, Y', strtotime($clientDetails['booking_date'])) : 'N/A' ?></span>
                    </span>
                    <span class="text-muted d-inline-flex align-items-center">
                        <i class="fa-light fa-user-tie mx-2"></i>
                        <strong>Sales By:</strong>&nbsp;<span class="fw-semibold text-dark" id="cedDisplaySalesAgent"><?= htmlspecialchars($clientDetails['agent_name'] ?? 'N/A') ?><?= !empty($clientDetails['agent_employee_id']) ? ' [EMP: ' . htmlspecialchars($clientDetails['agent_employee_id']) . ']' : '' ?></span>
                        <?php if ($canEditBookingInfo): ?>
                            <button type="button" class="btn btn-sm btn-outline-warning text-dark fw-bold ms-2 py-0 px-2" data-bs-toggle="modal" data-bs-target="#editBookingInfoModal" title="Edit Booking Date, Sales Agent & Total EMI" style="font-size: 11px;">
                                <i class="fa-solid fa-pen-to-square me-1"></i> Edit Date, Sales & EMI
                            </button>
                        <?php endif; ?>
                    </span>
                </div>
                
                <div class="mt-3 d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#transferNoteModal">
                        <i class="fa-light fa-exchange-alt me-1"></i> Transfer Note
                    </button>
                    
                    <?php if ($canEditBookingInfo): ?>
                        <button type="button" class="btn btn-sm btn-outline-warning text-dark fw-bold" data-bs-toggle="modal" data-bs-target="#editBookingInfoModal">
                            <i class="fa-solid fa-pen-to-square me-1"></i> Edit Booking Info
                        </button>
                    <?php endif; ?>
                    
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="openEmiFollowupModal(<?= $clientDetails['booking_id'] ?? 0 ?>, 0, <?= (int)($clientDetails['total_emi_count'] ?? 36) ?>, 'add')">
                        <i class="fa-light fa-comment-dots me-1"></i> Follow Up
                    </button>
                    
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="openEmiFollowupModal(<?= $clientDetails['booking_id'] ?? 0 ?>, 0, <?= (int)($clientDetails['total_emi_count'] ?? 36) ?>, 'list')">
                        <i class="fa-light fa-list me-1"></i> Follow Up List
                    </button>

                    <?php if (isset($clientDetails['transfer_status']) && $clientDetails['transfer_status'] == 1): ?>
                        <span class="badge bg-warning text-dark">Transferred</span>
                    <?php else: ?>
                        <span class="badge bg-success">Current</span>
                    <?php endif; ?>
                    
                    <?php 
                        $isAccountsUser = in_array($userRoleId, [1, 3, 6, 10, 30]);
                        $bookingRefunded = (($clientDetails['booking_status'] ?? '') === 'refunded');
                    ?>
                    <?php if (!empty($refundDetails) || $bookingRefunded): ?>
                        <?php 
                            $rfStatus = $refundDetails['status'] ?? ($bookingRefunded ? 'approved' : '');
                            $rfAmount = (float)($refundDetails['refund_amount'] ?? $refundPaidAmount);
                        ?>
                        <?php if ($rfStatus === 'pending'): ?>
                            <span class="badge bg-warning text-dark"><i class="fa-light fa-clock me-1"></i>Refund Requested (Pending Approval)</span>
                        <?php elseif ($rfStatus === 'approved'): ?>
                            <span class="badge bg-info text-white"><i class="fa-light fa-hourglass-half me-1"></i>Refund Request (Amount Not Paid)</span>
                            <?php if ($isAccountsUser): ?>
                                <a href="<?= $GLOBALS['base_url'] ?>/client-refund-create/<?= $bookingId ?>" class="btn btn-sm btn-danger ms-1">
                                    <i class="fa-light fa-money-bill-wave me-1"></i>Refund Paid Request
                                </a>
                            <?php endif; ?>
                        <?php elseif ($rfStatus === 'refunded'): ?>
                            <span class="badge bg-success"><i class="fa-light fa-check-circle me-1"></i>Refunded Paid</span>
                            <?php if ($rfAmount > 0): ?>
                                <span class="badge" style="background:#2d8cf0; color:#fff;"><i class="fa-light fa-money-bill-transfer me-1"></i>Refunded Amount: TK.<?= number_format($rfAmount, 2) ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <?php
        $isShop    = !empty($clientDetails['is_shop']) && $clientDetails['is_shop'] == 1;
        $devTarget = (float)($isShop ? ($paymentSummary['shop_service_charge_target'] ?? 0) : ($paymentSummary['flat_development_charges'] ?? 0));
        $devPaid   = (float)($isShop ? ($paymentSummary['shop_service_charge_paid_total'] ?? 0) : ($paymentSummary['development_charge_paid']  ?? 0));
        $devRemain = max(0, $devTarget - $devPaid);
        // Shops: Development Charge (flats.development_charges) tracked separately from Shop Service Charge
        $shopDevTarget = $isShop ? (float)($paymentSummary['flat_development_charges'] ?? 0) : 0;
        $shopDevPaid   = $isShop ? (float)($paymentSummary['development_charge_paid_total'] ?? 0) : 0;
        $shopDevRemain = max(0, $shopDevTarget - $shopDevPaid);
        $showShopDev   = $isShop; // always show Dev. Charge cards for shops

        // Compute all Due / Expired / Overdue EMIs
        $dueEmisList = [];
        $today = new \DateTime();
        $totalDueEmiAmount = 0;
        
        $outstandingDues = $accountModel->getOutstandingEmiDues($clientDetails['booking_id'] ?? 0);
        if (!empty($outstandingDues)) {
            foreach ($outstandingDues as $od) {
                $emiNo = (int)($od['emi_no'] ?? 0);
                $dueDateStr = $od['payment_date'] ?? null;
                $dueAmt = (float)($od['due_emi_amount'] ?? 0);
                if ($dueAmt > 0) {
                    $isExpired = false;
                    $daysDiff = 0;
                    $monthsDiff = 0;
                    if (!empty($dueDateStr)) {
                        $dObj = new \DateTime($dueDateStr);
                        if ($dObj < $today) {
                            $isExpired = true;
                            $diff = $today->diff($dObj);
                            $daysDiff = $diff->days;
                            $monthsDiff = ($diff->y * 12) + $diff->m;
                        }
                    }
                    $dueEmisList[$emiNo] = [
                        'emi_no' => $emiNo,
                        'due_date' => $dueDateStr,
                        'due_amount' => $dueAmt,
                        'paid_amount' => (float)($od['amount'] ?? 0),
                        'type' => 'partial',
                        'type_label' => 'Partial Balance Due',
                        'badge_class' => 'bg-warning text-dark',
                        'is_expired' => $isExpired,
                        'days_expired' => $daysDiff,
                        'months_expired' => $monthsDiff,
                        'penalty_amount' => 0,
                        'total_payable' => $dueAmt
                    ];
                    $totalDueEmiAmount += $dueAmt;
                }
            }
        }

        if (!empty($virtualEmiSchedule)) {
            foreach ($virtualEmiSchedule as $vEmi) {
                $emiNo = (int)$vEmi['emi_no'];
                $maturity = $vEmi['maturity_status'] ?? '';
                $dueAmt = (float)($vEmi['due_amount'] ?? 0);
                $dueDateStr = $vEmi['due_date'] ?? null;

                $isExpired = false;
                $daysDiff = 0;
                $monthsDiff = 0;
                if (!empty($dueDateStr)) {
                    $dObj = new \DateTime($dueDateStr);
                    if ($dObj < $today) {
                        $isExpired = true;
                        $diff = $today->diff($dObj);
                        $daysDiff = $diff->days;
                        $monthsDiff = ($diff->y * 12) + $diff->m;
                    }
                }

                if (($maturity === 'due' || ($maturity === 'pending' && $isExpired)) && $dueAmt > 0) {
                    if (isset($dueEmisList[$emiNo])) {
                        if (!empty($dueDateStr)) {
                            $dueEmisList[$emiNo]['due_date'] = $dueDateStr;
                        }
                        $dueEmisList[$emiNo]['months_expired'] = $vEmi['months_overdue'] ?? $dueEmisList[$emiNo]['months_expired'];
                        $dueEmisList[$emiNo]['penalty_amount'] = $vEmi['penalty_amount'] ?? 0;
                        $dueEmisList[$emiNo]['total_payable'] = $vEmi['total_payable'] ?? $dueAmt;
                        $dueEmisList[$emiNo]['is_expired'] = true;
                        if ($daysDiff > 0) $dueEmisList[$emiNo]['days_expired'] = $daysDiff;
                    } else {
                        $dueEmisList[$emiNo] = [
                            'emi_no' => $emiNo,
                            'due_date' => $dueDateStr,
                            'due_amount' => $dueAmt,
                            'paid_amount' => (float)($vEmi['paid_amount'] ?? 0),
                            'type' => 'overdue',
                            'type_label' => $maturity === 'due' ? 'Expired / Overdue' : 'Current Month (Expired)',
                            'badge_class' => 'bg-danger text-white',
                            'is_expired' => $isExpired,
                            'days_expired' => $daysDiff,
                            'months_expired' => $vEmi['months_overdue'] ?? $monthsDiff,
                            'penalty_amount' => $vEmi['penalty_amount'] ?? 0,
                            'total_payable' => $vEmi['total_payable'] ?? $dueAmt
                        ];
                        $totalDueEmiAmount += $dueAmt;
                    }
                }
            }
        }
        ksort($dueEmisList);
    ?>
    <div class="row">

        <!-- ROW 1: Agreement Overview -->
        <!-- 1. Total Value -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-money-bill"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Total Value</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['total_price'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 2. Booking Money -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-hand-holding-usd"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Booking Money</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['booking_money'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 3. Discount Amount -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-tag"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Discount Amount</h6>
                        <div class="d-flex flex-wrap align-items-end justify-content-between gap-10">
                            <h4 class="card__title text-success mb-0">TK.<?= number_format($paymentSummary['discount_amount'] ?? 0, 2) ?></h4>
                            <?php if (!empty($clientDetails['past_approval_attachment'])): ?>
                                <a href="<?= $GLOBALS['base_url'] ?>/<?= htmlspecialchars(ltrim($clientDetails['past_approval_attachment'], '/')) ?>" target="_blank" class="badge bg-light text-success border border-success-subtle" title="View discount supporting document">
                                    <i class="fa-solid fa-paperclip me-1"></i> Attachment
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 4. Registration Money -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-file-certificate"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Registration Money</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['registration_paid'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROW 2: Land Share & Dev Charges -->
        <!-- 5. Total Land Share -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-landmark"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Total Land Share</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['total_land_share'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 6. Land Share Paid -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-hand-holding-usd"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Land Share Paid</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <?php if (!empty($paymentSummary['discount_amount']) && (float)$paymentSummary['discount_amount'] == (float)$paymentSummary['total_land_share']): ?>
                                <h4 class="card__title text-success" style="font-size: 14px;">Paid by Discount Approved</h4>
                            <?php else: ?>
                                <h4 class="card__title">TK.<?= number_format($paymentSummary['total_land_share_paid'] ?? 0, 2) ?></h4>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 7. Remaining Land Share -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-scale-balanced"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Remaining Land Share</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['remaining_land_share'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 8. Development Charges -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light <?= $isShop ? 'fa-store' : 'fa-wrench' ?>"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10"><?= $isShop ? 'Shop Service Charge' : 'Development Charges' ?></h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($devTarget, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROW 3: Dev Status & Construction -->
        <!-- 9. Dev Charges Paid -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-hammer"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10"><?= $isShop ? 'Shop Service Charge Paid' : 'Dev. Charges Paid' ?></h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($devPaid, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 10. Dev Charges Remaining -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span style="color:<?= $devRemain > 0 ? '#e74c3c' : '#27ae60' ?>;"><i class="fa-light fa-hourglass-end"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10"><?= $isShop ? 'Shop Svc Charge Remaining' : 'Dev. Charges Remaining' ?></h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title" style="color:<?= $devRemain > 0 ? '#e74c3c' : '#27ae60' ?>;">
                                <?php if ($devRemain <= 0): ?>
                                    Fully Paid ✓
                                <?php else: ?>
                                    TK.<?= number_format($devRemain, 2) ?>
                                <?php endif; ?>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php if ($showShopDev): ?>
        <!-- 10a. Shop Development Charges (Total) -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-wrench"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Development Charges</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($shopDevTarget, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 10b. Shop Dev. Charges Paid -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-hammer"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Dev. Charges Paid</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($shopDevPaid, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 10c. Shop Dev. Charges Remaining -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span style="color:<?= $shopDevRemain > 0 ? '#e74c3c' : '#27ae60' ?>;"><i class="fa-light fa-hourglass-end"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Dev. Charges Remaining</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title" style="color:<?= $shopDevRemain > 0 ? '#e74c3c' : '#27ae60' ?>;">
                                <?php if ($shopDevRemain <= 0): ?>
                                    Fully Paid ✓
                                <?php else: ?>
                                    TK.<?= number_format($shopDevRemain, 2) ?>
                                <?php endif; ?>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <!-- 11. Total Construction Fee -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-building"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Total Construction Fee</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['construction_fee'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 12. Construction Fee Paid -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-money-check-dollar"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Construction Fee Paid</h6>
                        <div class="d-flex flex-column align-items-start">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['construction_fee_paid_after_service'] ?? 0, 2) ?></h4>
                            <?php if (!empty($paymentSummary['unverified_emi_amount']) && $paymentSummary['unverified_emi_amount'] > 0): ?>
                                <div style="margin-top: 4px;">
                                    <span class="badge bg-warning text-dark" style="font-size:11px; font-weight:600; padding:4px 8px;" title="Uploaded EMI payments awaiting verification">
                                        <i class="fa-light fa-clock"></i> +TK.<?= number_format($paymentSummary['unverified_emi_amount'], 2) ?> Pending Verification
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROW 4: CF Remaining & EMI Status -->
        <!-- 13a. Approved EMI Discount -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span style="color:#6c5ffc;"><i class="fa-light fa-tags"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Approved EMI Discount</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <div class="d-flex flex-column align-items-start">
                                <h4 class="card__title text-primary">
                                    TK.<?= number_format($paymentSummary['total_bulk_emi_discount'] ?? 0, 2) ?>
                                </h4>
                                <?php if (!empty($paymentSummary['unverified_bulk_emi_discount']) && $paymentSummary['unverified_bulk_emi_discount'] > 0): ?>
                                    <div style="margin-top: 4px;">
                                        <span class="badge bg-warning text-dark" style="font-size:11px; font-weight:600; padding:4px 8px;" title="Uploaded EMI discounts awaiting verification">
                                            <i class="fa-light fa-clock"></i> +TK.<?= number_format($paymentSummary['unverified_bulk_emi_discount'], 2) ?> Pending Verification
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 13b. After EMI Discount Remaining Cons Fee -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span style="color:#e74c3c;"><i class="fa-light fa-hourglass-end"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">After EMI Discount Remaining Cons Fee</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title" style="color:<?= ($paymentSummary['remaining_construction_fee'] ?? 0) <= 0 ? '#27ae60' : '#e74c3c' ?>;">
                                <?php if (($paymentSummary['remaining_construction_fee'] ?? 0) <= 0): ?>
                                    Fully Paid ✓
                                <?php else: ?>
                                    TK.<?= number_format($paymentSummary['remaining_construction_fee'], 2) ?>
                                <?php endif; ?>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 14. Per EMI Amount -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-calculator"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Per EMI Amount</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['per_emi_amount'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 15. Completed EMI -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-check-circle"></i></span></div>
                    <div class="card__title-wrap" style="width: 100%;">
                        <h6 class="card__sub-title mb-10">Completed EMI</h6>
                        <div class="d-flex flex-column align-items-start">
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="card__title mb-0"><?= $paymentSummary['completed_emi'] ?? 0 ?> / <?= $paymentSummary['total_emi'] ?? 0 ?></h4>
                                <?php if ($canEditBookingInfo): ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-primary py-0 px-2 edit-emi-btn" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editBookingInfoModal" 
                                            data-focus="emi" 
                                            title="Edit Total EMI" 
                                            style="font-size: 11px; height: 23px; display: inline-flex; align-items: center; border-radius: 4px; font-weight: 600;">
                                        <i class="fa-solid fa-pen-to-square me-1"></i>Edit
                                    </button>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($paymentSummary['emi_updated_at'])): ?>
                                <div class="mt-1" style="font-size: 11px; color: #64748b; line-height: 1.3;" title="Last updated by <?= htmlspecialchars($paymentSummary['emi_updated_by_name'] ?? 'User') ?> on <?= date('d M, Y h:i A', strtotime($paymentSummary['emi_updated_at'])) ?>">
                                    <i class="fa-regular fa-clock me-1 text-primary"></i>
                                    <span>Updated <?= !empty($paymentSummary['emi_updated_by_name']) ? 'by <strong class="text-dark">' . htmlspecialchars($paymentSummary['emi_updated_by_name']) . '</strong>' : '' ?> on <?= date('d M, Y', strtotime($paymentSummary['emi_updated_at'])) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($paymentSummary['unverified_emi_count']) && $paymentSummary['unverified_emi_count'] > 0): ?>
                                <div style="margin-top: 4px;">
                                    <span class="badge bg-warning text-dark" style="font-size:11px; font-weight:600; padding:4px 8px;" title="EMI payments awaiting verification">
                                        <i class="fa-light fa-clock"></i> +<?= $paymentSummary['unverified_emi_count'] ?> EMI Pending Verification
                                    </span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($dueEmisList)): ?>
                                <div style="margin-top: 4px;">
                                    <span class="badge bg-danger text-white" style="font-size:11px; font-weight:600; padding:4px 8px;" title="Due / Overdue EMI numbers">
                                        <i class="fa-solid fa-triangle-exclamation me-1"></i> Due: <?= implode(', ', array_map(fn($n) => "#{$n}", array_keys($dueEmisList))) ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 16. Service Charge Rate -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span style="color:#e67e22;"><i class="fa-light fa-percent"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Service Charge Rate</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <?php $svcRate = (float)($isShop ? ($paymentSummary['shop_service_charge_pct'] ?? 0) : ($paymentSummary['service_charge_per_emi'] ?? 0)); ?>
                            <h4 class="card__title" style="color:#e67e22;"><?= (float)round($svcRate, 4) ?>% <small style="font-size:13px;color:#999;"><?= $isShop ? 'of Total Value' : 'per EMI' ?></small></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROW 5: Revenue & Meta -->
        <!-- 17. Total Service Fee Collected -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span style="color:#8e44ad;"><i class="fa-light fa-hand-holding-dollar"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Total Service Fee Collected</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title" style="color:#8e44ad;">TK.<?= number_format($paymentSummary['total_service_fee'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 18. Net Received -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-sack-dollar"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Net Received</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['net_received'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 19. Total Due -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span style="color:#e74c3c;"><i class="fa-light fa-file-invoice-dollar"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Total Due</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title" style="color:<?= ($paymentSummary['total_due'] ?? 0) <= 0 ? '#27ae60' : '#e74c3c' ?>;">
                                <?php if (($paymentSummary['total_due'] ?? 0) <= 0): ?>
                                    All Clear ✓
                                <?php else: ?>
                                    TK.<?= number_format($paymentSummary['total_due'], 2) ?>
                                <?php endif; ?>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 20. Last Payment -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-calendar-check"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Last Payment</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">
                                <?php if ($paymentSummary['last_payment_date']): ?>
                                    <?= date('d M, Y', strtotime($paymentSummary['last_payment_date'])) ?>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (($paymentSummary['total_bulk_emi_discount'] ?? 0) > 0): ?>
        <!-- 21. Bulk EMI Discount (conditional) -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span style="color:#6c5ffc;"><i class="fa-light fa-tags"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Bulk EMI Discount</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title" style="color:#6c5ffc;">
                                TK.<?= number_format($paymentSummary['total_bulk_emi_discount'], 2) ?>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>


    <!-- Overdue & Due EMI Cards Breakdown Section -->
    <?php if (!empty($dueEmisList)): ?>
    <div class="col-xxl-12 mb-4">
        <div class="card__wrapper p-3" style="border-left: 4px solid #ef4444; background: #fffcfc; border-radius: 12px; box-shadow: 0 2px 12px rgba(239, 68, 68, 0.08);">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-danger-subtle text-danger p-2" style="font-size: 16px; border-radius: 8px;">
                        <i class="fa-solid fa-calendar-xmark"></i>
                    </span>
                    <div>
                        <h6 class="mb-0 text-danger fw-bold" style="font-size: 15px;">
                            Overdue & Due Installments (বকেয়া ও মেয়াদোত্তীর্ণ কিস্তিসমূহ)
                        </h6>
                        <small class="text-muted">
                            Total <strong><?= count($dueEmisList) ?></strong> EMI installment(s) currently due / expired.
                        </small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-danger text-white px-3 py-2" style="font-size: 13px; font-weight: 600;">
                        <i class="fa-solid fa-sack-dollar me-1"></i> Total Due: TK. <?= number_format($totalDueEmiAmount, 2) ?>
                    </span>
                    <a href="<?= $GLOBALS['base_url'] ?>/ced-add-transaction-form?booking_id=<?= $clientDetails['booking_id'] ?>"
                       class="btn btn-sm text-white" style="background:#ef4444; border-color:#ef4444; font-weight: 600;">
                        <i class="fa-light fa-plus me-1"></i> Add EMI Payment
                    </a>
                </div>
            </div>

            <!-- Cards Grid for Each Due EMI -->
            <div class="row ">
                <?php foreach ($dueEmisList as $item): ?>
                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12">
                        <div class="card h-100 shadow-none border" style="border-radius: 10px; border-color: #fca5a5 !important; background: #ffffff;">
                            <div class="card-body p-3 d-flex flex-column justify-content-between">
                                <div>
                                    <!-- EMI Badge & Status -->
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="badge bg-danger fs-12 px-2 py-1" style="font-weight: 700;">
                                            <i class="fa-solid fa-receipt me-1"></i>EMI #<?= $item['emi_no'] ?>
                                        </span>
                                        <span class="badge <?= $item['badge_class'] ?>" style="font-size: 10px; font-weight: 600;">
                                            <?= $item['type_label'] ?>
                                        </span>
                                    </div>

                                    <!-- Due Amount -->
                                    <div class="my-2">
                                        <div class="text-muted" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Due Amount</div>
                                        <h4 class="text-danger mb-0 font-monospace" style="font-weight: 700;">
                                            TK. <?= number_format($item['due_amount'], 2) ?>
                                        </h4>
                                        <?php if ($item['paid_amount'] > 0): ?>
                                            <div class="text-muted" style="font-size: 11px; margin-top: 2px;">
                                                Paid: TK. <?= number_format($item['paid_amount'], 2) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Date & Expiration Detail -->
                                    <div class="pt-2 border-top" style="font-size: 12px;">
                                        <div class="text-secondary mb-1">
                                            <i class="fa-regular fa-calendar-xmark text-danger me-1"></i>
                                            Expired / Due Date: <strong class="text-dark"><?= !empty($item['due_date']) ? date('d M, Y', strtotime($item['due_date'])) : 'N/A' ?></strong>
                                        </div>
                                        <?php if ($item['is_expired']): ?>
                                            <div class="text-danger fw-semibold" style="font-size: 11px;">
                                                <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                                Expired <strong><?= $item['days_expired'] ?></strong> days ago <?= $item['months_expired'] > 0 ? "({$item['months_expired']} mo overdue)" : "" ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-warning fw-semibold" style="font-size: 11px;">
                                                <i class="fa-solid fa-clock me-1"></i> Due this month
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($item['penalty_amount']) && $item['penalty_amount'] > 0): ?>
                                            <div class="text-muted mt-1" style="font-size: 11px;">
                                                <i class="fa-solid fa-percent text-danger me-1"></i> Penalty: TK. <?= number_format($item['penalty_amount'], 2) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Action Button -->
                                <div class="mt-3 pt-2 border-top">
                                    <a href="<?= $GLOBALS['base_url'] ?>/ced-add-transaction-form?booking_id=<?= $clientDetails['booking_id'] ?>"
                                       class="btn btn-sm btn-outline-danger w-100 py-1 d-flex align-items-center justify-content-center gap-1"
                                       style="font-size: 11px; font-weight: 600;">
                                        <i class="fa-light fa-plus-circle"></i> Pay EMI #<?= $item['emi_no'] ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-2 pt-2 border-top text-muted" style="font-size: 11px;">
                <i class="fa-light fa-info-circle me-1 text-primary"></i>
                This outstanding balance will be <strong>automatically cleared</strong> from the next EMI payment (oldest due first).
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Transaction History Table -->
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="card__header">
                <h5 class="card__title mb-2">Transaction History</h5>
            </div>
            <div class="col-xxl-12 mb-4">
                    <?php
                        $db = $accountModel->getDb();
                        $stmt = $db->prepare("SELECT id FROM client_refund_requests WHERE booking_id = ? AND status != 'rejected'");
                        $bId = $clientDetails['booking_id'];
                        $stmt->bind_param('i', $bId);
                        $stmt->execute();
                        $hasRefund = $stmt->get_result()->num_rows > 0;
                    ?>
                    <?php if ($hasRefund): ?>
                    <div class="mt-2 alert alert-warning d-flex align-items-center mb-3 border-warning" style="background-color: #fff3cd; color: #856404; width: 100%;" role="alert">
                        <div>
                            <strong>Refund in Progress:</strong> A refund has been initiated for this booking. As a result, the system is no longer accepting new cash-in transactions.
                        </div>
                    </div>
                    <?php endif; ?>

                <div class="mt-3 d-flex gap-2 justify-content-end">
                    <?php if ($hasRefund): ?>
                        <button class="btn btn-secondary mb-3" disabled title="A refund has been requested for this booking">
                            <i class="fa-light fa-ban me-2"></i> Add Transaction (Disabled)
                        </button>
                    <?php else: ?>
                        <a href="<?= $GLOBALS['base_url'] ?>/ced-add-transaction-form?booking_id=<?= $clientDetails['booking_id'] ?>" class="btn btn-primary mb-3">
                            <i class="fa-light fa-plus me-2"></i> Add Transaction
                        </a>
                        <button type="button" class="btn btn-info text-white mb-3" data-bs-toggle="modal" data-bs-target="#applyPreviousDiscountModal">
                            <i class="fa-solid fa-tags me-1"></i> Apply Previous Discount
                        </button>
                    <?php endif; ?>
                    <a href="<?= $GLOBALS['base_url'] ?>/client-statement/<?= $clientDetails['booking_id'] ?>" target="_blank" class="btn btn-primary mb-3">
                        <i class="fa-light fa-file-invoice me-2"></i> Get Statement
                    </a>
                    <a href="<?= $GLOBALS['base_url'] ?>/discount-statement/<?= $clientDetails['booking_id'] ?>" target="_blank" class="btn mb-3" style="background: linear-gradient(135deg, #6c5ffc, #8b5cf6); color:#fff; border:none;">
                        <i class="fa-light fa-tags me-2"></i> Discount Statement
                    </a>
                    <?php if ($canEditBookingInfo ?? false): ?>
                    <button type="button" class="btn btn-warning text-dark fw-bold mb-3" data-bs-toggle="modal" data-bs-target="#editBookingInfoModal">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit Booking Info
                    </button>
                    <?php endif; ?>
                    <?php
                        $canRefund = in_array((int)($_SESSION['user']['role_id'] ?? 0), [1, 3, 6, 8]);
                        $bookingIsActive = in_array($clientDetails['booking_status'] ?? '', ['booked', 'completed']);
                    ?>
                    <?php if ($canRefund && $bookingIsActive && !$hasRefund): ?>
                    <button type="button" class="btn btn-danger mb-3" data-bs-toggle="modal" data-bs-target="#refundRequestModal">
                        <i class="fa-light fa-rotate-left me-2"></i> Refund
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Payment Type Filter -->
                <div class="mb-3 d-flex flex-wrap align-items-center gap-2">
                    <span class="text-muted me-1" style="font-size:13px;font-weight:600;">Filter:</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary payment-filter active" data-filter="all">All</button>
                    <button type="button" class="btn btn-sm btn-outline-primary payment-filter" data-filter="booking_money">Booking Money</button>
                    <button type="button" class="btn btn-sm btn-outline-info payment-filter" data-filter="registration">Registration</button>
                    <button type="button" class="btn btn-sm btn-outline-success payment-filter" data-filter="land_share">Land Share</button>
                    <?php if ($isShop): ?>
                    <button type="button" class="btn btn-sm btn-outline-warning payment-filter" data-filter="shop_service_charge">Shop Svc Charge</button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-sm btn-outline-warning payment-filter" data-filter="development_charge">Dev. Charge</button>
                    <button type="button" class="btn btn-sm btn-outline-danger payment-filter" data-filter="emi">EMI</button>
                    <button type="button" class="btn btn-sm btn-outline-dark payment-filter" data-filter="emi_due_settlement">EMI Settlement</button>
                </div>

                <div class="table__wrapper table-responsive">
                    <table class="table table-hover" id="cedTransactionTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Payment Type</th>
                                <th>Details</th>
                                <th>Amount</th>
                                <th>Due Amount</th>
                                <th>Land Share</th>
                                <th style="white-space:nowrap;">Dev. Charge</th>
                                <?php if ($isShop): ?>
                                <th style="white-space:nowrap;">Shop Svc Charge</th>
                                <?php endif; ?>
                                <th>Service Fee</th>
                                <th>Method</th>
                                <th>Bank</th>
                                <th>Trx ID</th>
                                <th>Status</th>
                                <th>Verification</th>
                                <th>Verified By</th>
                                <th>Document</th>
                                <th>Added / Edited By</th>
                                <th style="white-space:nowrap;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr data-payment-type="<?= htmlspecialchars($transaction['payment_type'] ?? '') ?>">
                                    <td>
                                        <span><?php echo $transaction['formatted_date']; ?></span>
                                        <?php if (in_array($transaction['payment_type'] ?? '', ['booking_money', 'registration'])): ?>
                                            <button type="button" class="btn btn-link p-0 ms-1 text-primary btn-edit-payment-date"
                                                    style="text-decoration:none; vertical-align: middle;"
                                                    data-payment-id="<?= htmlspecialchars($transaction['id'] ?? '') ?>"
                                                    data-booking-id="<?= htmlspecialchars($bookingId ?? '') ?>"
                                                    data-payment-type="<?= htmlspecialchars($transaction['payment_type'] ?? '') ?>"
                                                    data-payment-label="<?= htmlspecialchars($transaction['installment_label'] ?? '') ?>"
                                                    data-payment-details="<?= htmlspecialchars($transaction['payment_details'] ?? '') ?>"
                                                    data-amount="<?= htmlspecialchars(number_format((float)($transaction['amount'] ?? 0), 2)) ?>"
                                                    data-payment-date="<?= htmlspecialchars($transaction['payment_date'] ?? '') ?>"
                                                    data-formatted-date="<?= htmlspecialchars($transaction['formatted_date'] ?? '') ?>"
                                                    title="Edit Date">
                                                <i class="fa-solid fa-pen-to-square" style="font-size:12px;"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo $transaction['installment_label']; ?>
                                        </span>
                                        <?php if (!empty($transaction['bulk_emi_discount']) && $transaction['bulk_emi_discount'] > 0): ?>
                                            <br><span class="badge mt-1" style="background:#6c5ffc;font-size:10px;">
                                                <i class="fa-light fa-tags me-1"></i>Discount: TK.<?= number_format($transaction['bulk_emi_discount'], 2) ?>
                                            </span>
                                            <?php if (!empty($transaction['bulk_pay_amount']) && $transaction['bulk_pay_amount'] > 0): ?>
                                                <br><span class="badge mt-1" style="background:#2d8cf0;font-size:10px;">
                                                    <i class="fa-light fa-layer-group me-1"></i>Against bulk pay: TK.<?= number_format($transaction['bulk_pay_amount'], 2) ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($transaction['payment_details'] ?? '-'); ?></td>
                                    <td>TK.<?php echo number_format($transaction['amount'], 2); ?></td>
                                    <!-- ADD THIS -->
                                     <td>
                                         <?php if (!empty($transaction['due_emi_amount']) && $transaction['due_emi_amount'] > 0): ?>
                                             TK.<?php echo number_format($transaction['due_emi_amount'], 2); ?>
                                         <?php else: ?>
                                             <span class="text-muted">-</span>
                                         <?php endif; ?>
                                     </td>
                                    <td>
                                        <?php if ($transaction['land_share_amount'] > 0): ?>
                                            TK.<?php echo number_format($transaction['land_share_amount'], 2); ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <!-- Dev. Charge -->
                                    <td style="white-space:nowrap;">
                                        <?php if (($transaction['payment_type'] ?? '') === 'development_charge' && (float)($transaction['amount'] ?? 0) > 0): ?>
                                            TK.<?= number_format((float)$transaction['amount'], 2) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($isShop): ?>
                                    <!-- Shop Service Charge -->
                                    <td style="white-space:nowrap;">
                                        <?php if (($transaction['payment_type'] ?? '') === 'shop_service_charge' && (float)($transaction['amount'] ?? 0) > 0): ?>
                                            TK.<?= number_format((float)$transaction['amount'], 2) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                    <td>
                                        <?php if ($transaction['service_fee'] > 0): ?>
                                            TK.<?php echo number_format($transaction['service_fee'], 2); ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $transaction['payment_method'] ?? 'N/A'))); ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($transaction['bank_display_name'])): ?>
                                            <span class="text-primary" style="font-size:12px;">
                                                <i class="fa-light fa-building-columns me-1"></i><?= htmlspecialchars($transaction['bank_display_name']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo !empty($transaction['transaction_ref']) ? htmlspecialchars($transaction['transaction_ref']) : '<span class="text-muted">-</span>'; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $transaction['status_class']; ?>">
                                            <?php echo $transaction['status_text']; ?>
                                        </span>
                                    </td>
                                    <!-- VERIFICATION STATUS -->
                                    <td>
                                        <?php
                                            $vs = $transaction['verification_status'] ?? 'pending';
                                            if ($vs === 'verified'):
                                        ?>
                                            <span class="badge bg-success">
                                                <i class="fa-light fa-check-circle me-1"></i>Verified
                                            </span>
                                        <?php elseif ($vs === 'rejected'): ?>
                                            <span class="badge bg-danger">
                                                <i class="fa-light fa-times-circle me-1"></i>Rejected
                                            </span>
                                            <?php if (!empty($transaction['rejection_reason'])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger ms-1 py-0 px-1"
                                                onclick="showRejectionReason('<?= htmlspecialchars(addslashes($transaction['rejection_reason']), ENT_QUOTES) ?>')"
                                                title="View rejection reason" style="font-size:11px;">
                                                <i class="fa-light fa-comment-exclamation"></i>
                                            </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">
                                                <i class="fa-light fa-clock me-1"></i>Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <!-- VERIFIED BY -->
                                    <td>
                                        <?php if (!empty($transaction['verified_by_name'])): ?>
                                            <?= htmlspecialchars($transaction['verified_by_name']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <!-- DOCUMENT COLUMN — visible regardless of verification_status -->
                                    <td>
                                        <?php
                                            $docFiles = [];
                                            if (!empty($transaction['attachment_paths'])) {
                                                $decoded = json_decode($transaction['attachment_paths'], true);
                                                if (is_array($decoded)) $docFiles = $decoded;
                                            }
                                            if (!empty($transaction['attachment_image'])) {
                                                $legacyPath = 'uploads/payments/' . $transaction['attachment_image'];
                                                if (!in_array($legacyPath, $docFiles)) $docFiles[] = $legacyPath;
                                            }
                                            if (!empty($transaction['attachment_image2'])) {
                                                $legacyPath2 = 'uploads/payments/' . $transaction['attachment_image2'];
                                                if (!in_array($legacyPath2, $docFiles)) $docFiles[] = $legacyPath2;
                                            }
                                            $docCount = count($docFiles);
                                        ?>
                                        <?php if ($docCount > 0 && is_numeric($transaction['id'])): ?>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1"
                                                style="white-space:nowrap;"
                                                onclick="openDocumentModal(<?= (int)$transaction['id'] ?>)"
                                                title="View <?= $docCount ?> document(s)">
                                                <i class="fa-light fa-file-image"></i>
                                                <span class="badge bg-primary rounded-pill" style="font-size:11px;"><?= $docCount ?></span>
                                                <?= $docCount > 1 ? 'Files' : 'File' ?>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>

                                        <?php if (!empty($transaction['penalty_paid']) && $transaction['penalty_paid'] > 0): ?>
                                            <div class="mt-1">
                                                <span class="badge bg-danger-subtle text-danger border border-danger fs-11" title="Late Penalty Paid">
                                                    <i class="fa-light fa-clock-rotate-left me-1"></i>Penalty: TK.<?= number_format($transaction['penalty_paid'], 2) ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($transaction['penalty_waived']) && $transaction['penalty_waived'] > 0): ?>
                                            <div class="mt-1">
                                                <span class="badge bg-warning-subtle text-dark border border-warning fs-11" title="Reason: <?= htmlspecialchars($transaction['waiver_reason'] ?? 'N/A') ?>">
                                                    <i class="fa-light fa-file-signature me-1"></i>Waived: TK.<?= number_format($transaction['penalty_waived'], 2) ?> (<?= ucfirst($transaction['waiver_status'] ?? 'pending') ?>)
                                                </span>
                                                <?php if (!empty($transaction['waiver_attachment'])): ?>
                                                    <a href="<?= $GLOBALS['base_url'] . '/' . htmlspecialchars($transaction['waiver_attachment']) ?>" target="_blank" class="btn btn-link btn-sm p-0 fs-11 ms-1 text-primary" title="View Waiver Proof">
                                                        <i class="fa-light fa-paperclip"></i> Proof
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                     <td>
                                         <div><?php echo !empty($transaction['created_by_name']) ? htmlspecialchars($transaction['created_by_name']) : '<span class="text-muted">System</span>'; ?></div>
                                         <?php if (!empty($transaction['updated_by_name']) && stripos($transaction['updated_by_name'], 'Management') === false): ?>
                                             <div class="text-primary mt-1 border-top pt-1" style="font-size: 0.82em;" title="Last Updated By">
                                                 <i class="fa-solid fa-pen-to-square me-1 text-info"></i>Edited by: <strong><?= htmlspecialchars($transaction['updated_by_name']) ?></strong>
                                                 <?php if (!empty($transaction['updated_at'])): ?>
                                                     <br><span class="text-muted" style="font-size:10px;">(<?= date('d M, Y h:i A', strtotime($transaction['updated_at'])) ?>)</span>
                                                 <?php endif; ?>
                                             </div>
                                         <?php endif; ?>
                                         <?php 
                                             $isCsv = !empty($transaction['csv_import_log_id']) || in_array($transaction['payment_type'] ?? '', ['emi', 'emi_due_settlement', 'development_charge']);
                                         ?>
                                         <?php if ($isCsv): ?>
                                             <br><span class="badge bg-info-subtle text-info border border-info" style="font-size:10px; font-weight:600; margin-top:3px;" title="Uploaded via CSV Bulk Import">
                                                 <i class="fa-light fa-file-csv me-1"></i>CSV Import
                                             </span>
                                         <?php endif; ?>
                                     </td>
                                    
                                    <td>
                                        <div class="d-inline-flex align-items-center gap-1">
                                            <?php
                                                $hasId = (!empty($transaction['id']) && strpos($transaction['id'], 'booking-') === false);
                                                $isVerified = (($transaction['verification_status'] ?? 'pending') === 'verified');
                                            ?>
                                            <?php if ($hasId && $isVerified): ?>
                                                <a href="<?= $GLOBALS['base_url'] ?>/payment-invoice?payment_id=<?= $transaction['id'] ?>"
                                                   class="table__icon invoice"
                                                   style="color:#fff;background-color:#6c5ffc;"
                                                   title="View Invoice" target="_blank">
                                                    <i class="fa-light fa-file-invoice"></i>
                                                </a>
                                            <?php elseif ($hasId): ?>
                                                <span class="table__icon disabled"
                                                      title="Invoice available after verification"
                                                      style="background-color:#fef3cd;color:#856404;cursor:not-allowed;">
                                                    <i class="fa-light fa-file-invoice"></i>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>

                                            <?php
                                                $pType = $transaction['payment_type'] ?? '';
                                                $isEligibleDateEdit = in_array($pType, ['booking_money', 'registration']);
                                            ?>
                                            <?php if ($isEligibleDateEdit): ?>
                                                <button type="button"
                                                        class="table__icon edit border-0 btn-edit-payment-date"
                                                        style="color:#fff;background-color:#0284c7;cursor:pointer;"
                                                        data-payment-id="<?= htmlspecialchars($transaction['id'] ?? '') ?>"
                                                        data-booking-id="<?= htmlspecialchars($bookingId ?? '') ?>"
                                                        data-payment-type="<?= htmlspecialchars($pType) ?>"
                                                        data-payment-label="<?= htmlspecialchars($transaction['installment_label'] ?? '') ?>"
                                                        data-payment-details="<?= htmlspecialchars($transaction['payment_details'] ?? '') ?>"
                                                        data-amount="<?= htmlspecialchars(number_format((float)($transaction['amount'] ?? 0), 2)) ?>"
                                                        data-payment-date="<?= htmlspecialchars($transaction['payment_date'] ?? '') ?>"
                                                        data-formatted-date="<?= htmlspecialchars($transaction['formatted_date'] ?? '') ?>"
                                                        title="Edit Payment Date (<?= $pType === 'booking_money' ? 'Booking' : 'Registration' ?>)">
                                                    <i class="fa-solid fa-calendar-days"></i>
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($hasId && !$isVerified): ?>
                                                <button type="button"
                                                        class="table__icon edit border-0 btn-edit-unverified-payment"
                                                        style="color:#fff;background-color:#0284c7;cursor:pointer;"
                                                        data-payment-id="<?= htmlspecialchars($transaction['id']) ?>"
                                                        data-booking-id="<?= htmlspecialchars($bookingId) ?>"
                                                        data-payment-type="<?= htmlspecialchars($transaction['payment_type'] ?? '') ?>"
                                                        data-amount="<?= htmlspecialchars($transaction['amount'] ?? 0) ?>"
                                                        data-payment-date="<?= htmlspecialchars($transaction['payment_date'] ?? date('Y-m-d')) ?>"
                                                        data-payment-method="<?= htmlspecialchars($transaction['payment_method'] ?? 'cash') ?>"
                                                        data-bank-name="<?= htmlspecialchars($transaction['bank_name'] ?? '') ?>"
                                                        data-bank-id="<?= htmlspecialchars($transaction['bank_id'] ?? '') ?>"
                                                        data-bank-account-no="<?= htmlspecialchars($transaction['bank_account_no'] ?? '') ?>"
                                                        data-cheque-no="<?= htmlspecialchars($transaction['cheque_no'] ?? '') ?>"
                                                        data-online-type="<?= htmlspecialchars($transaction['online_type'] ?? '') ?>"
                                                        data-transaction-ref="<?= htmlspecialchars($transaction['transaction_ref'] ?? '') ?>"
                                                        data-due-emi-amount="<?= htmlspecialchars($transaction['due_emi_amount'] ?? 0) ?>"
                                                        title="Edit Unverified Payment">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($isManagementUser && ($transaction['payment_type'] ?? '') === 'booking_money'): ?>
                                                <button type="button"
                                                        class="table__icon edit border-0"
                                                        style="color:#fff;background-color:#f59e0b;cursor:pointer;"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editManagementDiscountModal"
                                                        title="Edit / Approve Management Discount">
                                                    <i class="fa-solid fa-tags"></i>
                                                </button>
                                            <?php endif; ?>

                                            <?php 
                                                $isEditableByManagement = $isManagementUser && in_array($transaction['payment_type'] ?? '', ['development_charge', 'land_share', 'shop_service_charge', 'emi']);
                                            ?>
                                            <?php if ($hasId && $isEditableByManagement): ?>
                                                <a href="<?= $GLOBALS['base_url'] ?>/edit-client-cash-in/<?= htmlspecialchars($transaction['id']) ?>"
                                                   class="table__icon edit border-0"
                                                   style="color:#fff;background-color:#6c5ffc;"
                                                   title="Edit Transaction (Management Only)">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($isManagementUser && $hasId): ?>
                                                <a href="<?= $GLOBALS['base_url'] ?>/delete-transaction?payment_id=<?= htmlspecialchars($transaction['id']) ?>&booking_id=<?= htmlspecialchars($bookingId) ?>"
                                                   class="table__icon text-white bg-danger border-0"
                                                   onclick="return confirm('Are you sure you want to PERMANENTLY delete this transaction? This action cannot be undone.');"
                                                   title="Permanently Delete Transaction">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- VIRTUAL EMI SCHEDULE (Yield to Maturity) -->
                <?php if (!empty($virtualEmiSchedule)): ?>
                <div class="mt-10" id="emi-schedule-section">
                    <h5 class="mb-3"><i class="fa-light fa-calendar-check me-2"></i>EMI Maturity Schedule</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" style="font-size: 14px;">
                            <thead style="background-color: #f8f9fa;">
                                <tr>
                                    <th>EMI No</th>
                                    <th>Projected/Actual Due Date</th>
                                    <th>Amount</th>
                                    <th>Paid Amount</th>
                                    <th>Due Amount</th>
                                    <th>Payment Status</th>
                                    <th>Maturity Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($virtualEmiSchedule as $emi): 
                                    $maturityBadge = '';
                                    switch($emi['maturity_status']) {
                                        case 'paid': $maturityBadge = '<span class="badge bg-success">Paid</span>'; break;
                                        case 'matured': $maturityBadge = '<span class="badge bg-info text-dark" style="background-color: #0dcaf0; color: #000;">Matured</span>'; break;
                                        case 'pending': $maturityBadge = '<span class="badge bg-warning text-dark">Pending (Current Month)</span>'; break;
                                        case 'due': $maturityBadge = '<span class="badge bg-danger">Due (Overdue)</span>'; break;
                                        case 'future': $maturityBadge = '<span class="badge bg-secondary">Future</span>'; break;
                                    }
                                    
                                    $statusBadge = '';
                                    if ($emi['status'] === 'paid') $statusBadge = '<span class="badge bg-success">Paid</span>';
                                    elseif ($emi['status'] === 'partially_paid') $statusBadge = '<span class="badge bg-warning text-dark">Partially Paid</span>';
                                    else $statusBadge = '<span class="text-muted">Unpaid</span>';
                                ?>
                                <tr>
                                    <td><strong>#<?= $emi['emi_no'] ?></strong> <?= $emi['is_virtual'] ? '<small class="text-muted">(Proj.)</small>' : '' ?></td>
                                    <td><?= date('d M, Y', strtotime($emi['due_date'])) ?></td>
                                    <td>TK. <?= number_format($emi['amount'], 2) ?></td>
                                    <td>TK. <?= number_format($emi['paid_amount'], 2) ?></td>
                                    <td>TK. <?= number_format($emi['due_amount'], 2) ?></td>
                                    <td><?= $statusBadge ?></td>
                                    <td><?= $maturityBadge ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

<!-- ═══════════════════════════════════════════════════════
     Document Preview Modal  (CED — Read-Only)
     ═══════════════════════════════════════════════════════ -->
<div class="modal fade" id="cedDocumentModal" tabindex="-1" aria-labelledby="cedDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cedDocumentModalLabel">
                    <i class="fa-light fa-file-image me-2"></i>Transaction Documents
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="cedDocumentModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <span class="text-muted small">
                    <i class="fa-light fa-lock me-1"></i>
                    Read-only view. Verification is managed by the Accounts module.
                </span>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== REJECTION REASON VIEW MODAL ===== -->
<div class="modal fade" id="viewRejectionReasonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:12px; overflow:hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #dc2626, #b91c1c); color:#fff; border:0;">
                <h5 class="modal-title">
                    <i class="fa-light fa-comment-exclamation me-2"></i>Rejection Reason
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger mb-0" style="border-radius:8px;">
                    <i class="fa-light fa-quote-left me-1 text-danger"></i>
                    <span id="rejectionReasonText"></span>
                    <i class="fa-light fa-quote-right ms-1 text-danger"></i>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f0f0f0;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

    <script>
        const BASE_URL_CED = '<?= $GLOBALS['base_url'] ?>';

        // ─── Show Rejection Reason Modal ───────────────────────────────────
        function showRejectionReason(reason) {
            document.getElementById('rejectionReasonText').textContent = reason;
            new bootstrap.Modal(document.getElementById('viewRejectionReasonModal')).show();
        }
        // ─── Document Modal ───────────────────────────────────────────────────
        function openDocumentModal(paymentId) {
            const modal = new bootstrap.Modal(document.getElementById('cedDocumentModal'));
            const body  = document.getElementById('cedDocumentModalBody');

            body.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Loading documents…</p>
                </div>`;
            modal.show();

            fetch(BASE_URL_CED + '/get-transaction-attachments?payment_id=' + paymentId)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        body.innerHTML = `<div class="alert alert-warning">${data.message}</div>`;
                        return;
                    }

                    const vsBadges = {
                        'verified': '<span class="badge bg-success"><i class="fa-light fa-check-circle me-1"></i>Verified</span>',
                        'rejected': '<span class="badge bg-danger"><i class="fa-light fa-times-circle me-1"></i>Rejected</span>',
                        'pending':  '<span class="badge bg-warning text-dark"><i class="fa-light fa-clock me-1"></i>Pending Verification</span>',
                    };
                    const vsBadge = vsBadges[data.verification_status] || vsBadges['pending'];

                    let html = `
                        <div class="d-flex flex-wrap gap-3 mb-3 align-items-center">
                            <div><strong>Payment ID:</strong> #${data.payment_id}</div>
                            <div><strong>Type:</strong> ${(data.payment_type || '').replace(/_/g,' ').toUpperCase()}</div>
                            <div><strong>Amount:</strong> TK.${parseFloat(data.amount || 0).toLocaleString('en-BD', {minimumFractionDigits:2})}</div>
                            <div><strong>Date:</strong> ${data.payment_date || '-'}</div>
                            <div><strong>Status:</strong> ${vsBadge}</div>
                            ${data.verified_by_name ? '<div><strong>Verified By:</strong> ' + data.verified_by_name + ' <span class="text-muted ms-1" style="font-size:0.9em;">(' + (data.verified_at ? new Date(data.verified_at).toLocaleString('en-GB', {day:'2-digit', month:'short', year:'2-digit', hour:'2-digit', minute:'2-digit', hour12:true}) : '') + ')</span></div>' : ''}
                            ${data.updated_by_name ? '<div><strong>Last Updated By:</strong> '+data.updated_by_name+' <span class="text-muted ms-1" style="font-size:0.9em;">(' + (data.updated_at ? new Date(data.updated_at).toLocaleString('en-GB', {day:'2-digit', month:'short', year:'2-digit', hour:'2-digit', minute:'2-digit', hour12:true}) : '') + ')</span></div>' : ''}
                            ${data.rejection_reason ? '<div class="w-100"><div class="alert alert-danger py-2 mb-0"><strong>Rejection Reason:</strong> ' + data.rejection_reason + '</div></div>' : ''}
                        </div><hr>`;

                    if (!data.files || data.files.length === 0) {
                        html += `<div class="alert alert-info"><i class="fa-light fa-info-circle me-1"></i>No documents attached to this transaction.</div>`;
                    } else {
                        html += `<div class="row ">`;
                        data.files.forEach((filePath, idx) => {
                            const fullUrl  = BASE_URL_CED + '/public/' + filePath;
                            const ext      = filePath.split('.').pop().toLowerCase();
                            const isImage  = ['jpg','jpeg','png','gif','webp'].includes(ext);
                            const isPDF    = ext === 'pdf';
                            const fileName = filePath.split('/').pop();

                            if (isImage) {
                                html += `
                                <div class="col-md-4 col-sm-6">
                                    <div class="card h-100">
                                        <a href="${fullUrl}" target="_blank" title="Open full size">
                                            <img src="${fullUrl}"
                                                 class="card-img-top"
                                                 style="height:200px;object-fit:contain;background:#f8f9fa;padding:8px;"
                                                 alt="Document ${idx+1}"
                                                 onerror="this.onerror=null; this.src='${BASE_URL_CED}/public/assets/img/no-image.png'">
                                        </a>
                                        <div class="card-body p-2 d-flex justify-content-between align-items-center">
                                            <small class="text-muted text-truncate" style="max-width:70%" title="${fileName}">${fileName}</small>
                                            <a href="${fullUrl}" target="_blank" download class="btn btn-sm btn-outline-secondary">
                                                <i class="fa-light fa-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>`;
                            } else if (isPDF) {
                                html += `
                                <div class="col-md-6">
                                    <div class="card h-100 text-center p-3">
                                        <i class="fa-light fa-file-pdf" style="font-size:3rem;color:#dc3545"></i>
                                        <p class="mt-2 mb-2 text-truncate" title="${fileName}"><strong>${fileName}</strong></p>
                                        <div class="d-flex gap-2 justify-content-center">
                                            <a href="${fullUrl}" target="_blank" class="btn btn-sm btn-outline-danger">
                                                <i class="fa-light fa-eye me-1"></i>View PDF
                                            </a>
                                            <a href="${fullUrl}" download class="btn btn-sm btn-outline-secondary">
                                                <i class="fa-light fa-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>`;
                            } else {
                                html += `
                                <div class="col-md-4 col-sm-6">
                                    <div class="card h-100 text-center p-3">
                                        <i class="fa-light fa-file" style="font-size:3rem;color:#6c757d"></i>
                                        <p class="mt-2 mb-2 text-truncate" title="${fileName}">${fileName}</p>
                                        <a href="${fullUrl}" download class="btn btn-sm btn-outline-secondary">
                                            <i class="fa-light fa-download me-1"></i>Download
                                        </a>
                                    </div>
                                </div>`;
                            }
                        });
                        html += `</div>`;
                    }
                    body.innerHTML = html;
                })
                .catch(() => {
                    body.innerHTML = `<div class="alert alert-danger">Failed to load documents. Check your connection and try again.</div>`;
                });
        }
    </script>

<!-- ===== REFUND REQUEST MODAL ===== -->
<div class="modal fade" id="refundRequestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= $GLOBALS['base_url'] ?>/client-refund-store" method="POST" enctype="multipart/form-data">
                <div class="modal-header" style="background: linear-gradient(135deg, #dc2626, #b91c1c); color:#fff; border:0;">
                    <h5 class="modal-title">
                        <i class="fa-light fa-rotate-left me-2"></i>Request Refund
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="booking_id" value="<?= $clientDetails['booking_id'] ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Refund Amount (৳) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="1" name="refund_amount" class="form-control" required placeholder="Enter refund amount">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required placeholder="Provide reason for refund..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Upload Document <span class="text-muted">(Optional)</span></label>
                        <input type="file" name="document" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                        <small class="text-muted">Allowed types: jpg, png, pdf, doc, docx. Max size: 5MB.</small>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #f0f0f0;">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

    <style>
        .card__icon span {
            color: var(--clr-theme-primary);
            background: rgba(108, 95, 252, 0.1);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            text-align: center;
            justify-content: center;
            align-items: center;
            font-size: 16px;
        }

        .card__title {
            font-size: 1.25rem;
        }

        @media (min-width: 975px) {
            .dataTables_wrapper .dataTables_filter input[type=search] {
                border: 1px solid var(--clr-border-1);
                color: var(--clr-text-body);
                padding: 0 10px;
                margin-inline-start: 10px;
                height: 36px;
                border-radius: 6px;
                width: 380px;
            }
        }

        @media (max-width: 975px) {
            .card__wrapper .card__body h3 {
                font-size: 1.3rem;
            }

            .card__title {
                font-size: 1rem;
            }
        }

        @media (max-width: 768px) {
            .btn {
                font-size: 14px;
                padding: 6px 24px;
            }
        }

        @media (min-width: 575px) {
            .position {
                justify-content: end !important;
            }
        }

        @media (max-width: 480px) {
            .btn {
                font-size: 16px;
                padding: 6px 21px;
            }

            .position {
                justify-content: start !important;
            }
        }

        @media (max-width: 400px) {
            .btn {
                font-size: 14px;
                padding: 6px 14px;
            }
        }

        /* Filter button active states */
        .payment-filter.active { font-weight: 600; }
        .payment-filter[data-filter="all"].active { background:#6c757d; color:#fff; border-color:#6c757d; }
        .payment-filter[data-filter="booking_money"].active { background:#0d6efd; color:#fff; border-color:#0d6efd; }
        .payment-filter[data-filter="registration"].active { background:#0dcaf0; color:#fff; border-color:#0dcaf0; }
        .payment-filter[data-filter="land_share"].active { background:#198754; color:#fff; border-color:#198754; }
        .payment-filter[data-filter="development_charge"].active { background:#ffc107; color:#000; border-color:#ffc107; }
        .payment-filter[data-filter="emi"].active { background:#dc3545; color:#fff; border-color:#dc3545; }
        .payment-filter[data-filter="emi_due_settlement"].active { background:#212529; color:#fff; border-color:#212529; }
        
        /* Fix SweetAlert z-index behind bootstrap modals */
        .swal2-container { z-index: 99999 !important; }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var filters = document.querySelectorAll('.payment-filter');
        var rows = document.querySelectorAll('#cedTransactionTable tbody tr[data-payment-type]');

        filters.forEach(function(btn) {
            btn.addEventListener('click', function() {
                filters.forEach(function(b) { b.classList.remove('active'); });
                btn.classList.add('active');

                var filter = btn.getAttribute('data-filter');
                rows.forEach(function(row) {
                    if (filter === 'all' || row.getAttribute('data-payment-type') === filter) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
    });
    
    function openEmiFollowupModal(bookingId, emiNo, totalEmi = 36, mode = 'both') {
        $('#followupBookingId').val(bookingId);
        
        if (mode === 'add') {
            $('#followupFormCol').show().removeClass('col-md-5 border-end').addClass('col-md-12');
            $('#followupHistoryCol').hide();
        } else if (mode === 'list') {
            $('#followupHistoryCol').show().removeClass('col-md-7').addClass('col-md-12');
            $('#followupFormCol').hide();
        } else {
            $('#followupFormCol').show().removeClass('col-md-12').addClass('col-md-5 border-end');
            $('#followupHistoryCol').show().removeClass('col-md-12').addClass('col-md-7');
        }
        
        // Populate EMI select
        var emiSelect = $('#followupEmiNo');
        var optionsHtml = '<option value="0">General / Overall Follow Up</option>';
        optionsHtml += '<option value="999">Development Charge</option>';
        var maxEmi = parseInt(totalEmi) || 36;
        for (let i = 1; i <= maxEmi; i++) {
            optionsHtml += '<option value="' + i + '">EMI #' + i + '</option>';
        }
        emiSelect.html(optionsHtml);
        emiSelect.val(emiNo);

        $('#followupNoteInput').val('');
        $('#followupHistoryContainer').html('<div class="text-center text-muted py-4">Loading history...</div>');
        
        var modal = new bootstrap.Modal(document.getElementById('emiFollowupModal'));
        modal.show();
        
        loadEmiFollowups(bookingId, emiNo);
    }
    
    function loadEmiFollowups(bookingId, emiNo) {
        $.ajax({
            url: '<?= $GLOBALS['base_url'] ?>/account-client/getEmiFollowups',
            type: 'GET',
            data: { booking_id: bookingId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var html = '';
                    if (!response.data || response.data.length === 0) {
                        html = '<div class="text-center text-muted py-4">No follow up notes yet.</div>';
                    } else {
                        response.data.forEach(function(note) {
                            const label = note.purpose_label || (note.emi_no == 999 ? 'Development Charge' : (note.emi_no == 0 ? 'General Follow Up' : 'EMI #' + note.emi_no));
                            const badgeClass = note.purpose_badge_class || (note.emi_no == 999 ? 'bg-warning text-dark' : (note.emi_no == 0 ? 'bg-secondary text-white' : 'bg-primary text-white'));
                            
                            html += '<div class="card mb-2 shadow-sm border-0 bg-light">';
                            html += '<div class="card-body p-3">';
                            html += '<div class="mb-2">';
                            html += '<span class="badge ' + badgeClass + ' fs-11 px-2 py-1"><i class="fa-light fa-tag me-1"></i>' + label + '</span>';
                            html += '</div>';
                            html += '<div class="mb-2" style="font-size:12px; color:#475569;">';
                            if (note.activity_date) html += '<span><i class="fa-light fa-calendar me-1"></i>' + note.activity_date + '</span>';
                            if (note.activity_mode) html += '<span class="ms-3"><i class="fa-light fa-phone-volume me-1"></i>' + note.activity_mode + '</span>';
                            if (note.call_status) {
                                var badgeBg = note.call_status.toLowerCase() === 'incoming' ? 'bg-success' : 'bg-primary';
                                var iconClass = note.call_status.toLowerCase() === 'incoming' ? 'fa-arrow-down-left' : 'fa-arrow-up-right';
                                html += '<span class="ms-2 badge ' + badgeBg + ' text-white" style="font-size:10px;"><i class="fa-light ' + iconClass + ' me-1"></i>' + note.call_status + '</span>';
                            }
                            if (note.activity_reason) html += '<span class="ms-3"><i class="fa-light fa-bullseye me-1"></i>' + note.activity_reason + '</span>';
                            html += '</div>';
                            html += '<p class="mb-2" style="font-size: 14px;">' + (note.note || '') + '</p>';
                            html += '<div class="d-flex justify-content-between text-muted" style="font-size: 12px;">';
                            html += '<span><i class="fa-light fa-user me-1"></i> ' + (note.author_name || 'System') + '</span>';
                            html += '<span><i class="fa-light fa-clock me-1"></i> ' + (note.created_at_formatted || '') + '</span>';
                            html += '</div></div></div>';
                        });
                    }
                    $('#followupHistoryContainer').html(html);
                } else {
                    $('#followupHistoryContainer').html('<div class="text-danger p-3">Failed to load history.</div>');
                }
            },
            error: function() {
                $('#followupHistoryContainer').html('<div class="text-danger p-3">Error connecting to server.</div>');
            }
        });
    }
    
    function saveEmiFollowup() {
        var bookingId = $('#followupBookingId').val();
        var emiNo = $('#followupEmiNo').val();
        var note = $('#followupNoteInput').val().trim();
        var activityDate = $('#followupActivityDate').val();
        var activityMode = $('#followupActivityMode').val();
        var callStatus = $('#followupCallStatus').val() || '';
        var activityReason = $('#followupActivityReason').val();
        
        if (!note || !activityDate || !activityMode || !activityReason) {
            Swal.fire({
                icon: 'error',
                title: 'Fields Required',
                text: 'Please fill in all the required fields (*).',
            });
            return;
        }
        
        $.ajax({
            url: '<?= $GLOBALS['base_url'] ?>/account-client/saveEmiFollowup',
            type: 'POST',
            data: { 
                booking_id: bookingId, 
                emi_no: emiNo, 
                note: note,
                activity_date: activityDate,
                activity_mode: activityMode,
                call_status: callStatus,
                activity_reason: activityReason
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Follow up note saved successfully.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    $('#followupNoteInput').val('');
                    $('#followupCallStatus').val('');
                    loadEmiFollowups(bookingId, emiNo);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Failed',
                        text: response.message || "Failed to save note."
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Server Error',
                    text: 'Please try again later.'
                });
            }
        });
    }
    </script>

<?php endif; ?>

<!-- Edit Booking Info Modal -->
<div class="modal fade" id="editBookingInfoModal" tabindex="-1" aria-labelledby="editBookingInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="editBookingInfoModalLabel">
                    <i class="fa-solid fa-pen-to-square text-warning me-2"></i>Edit Booking, Sales & EMI Info
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= $GLOBALS['base_url'] ?>/update-booking-info" method="POST" id="editBookingInfoFormCed">
                <input type="hidden" name="booking_id" value="<?= $clientDetails['booking_id'] ?? '' ?>">
                <input type="hidden" name="lead_id" value="<?= $clientDetails['lead_id'] ?? '' ?>">
                <div class="modal-body p-4">
                    <div class="mb-3 p-3 rounded-3" style="background: #eef6ff; border: 1px solid #bfdbfe; color: #1e40af; font-size: 13px; line-height: 1.6;">
                        <div class="d-flex align-items-start gap-2">
                            <i class="fa-solid fa-circle-info text-primary mt-1" style="font-size: 15px;"></i>
                            <div>
                                Updating <strong>Booking Date</strong> will update <code style="background: #dbeafe; color: #1e40af; padding: 2px 5px; border-radius: 4px; font-weight: 600;">lead_bookings.booking_date</code> and payment date for <strong>Booking Money</strong>.<br>
                                Updating <strong>Sales By</strong> will update <code style="background: #dbeafe; color: #1e40af; padding: 2px 5px; border-radius: 4px; font-weight: 600;">lead_bookings.sales_by_id</code> and <code style="background: #dbeafe; color: #1e40af; padding: 2px 5px; border-radius: 4px; font-weight: 600;">leads.assigned_id</code>.<br>
                                Updating <strong>Total EMI</strong> will divide remaining construction fee among remaining future EMIs (completed/paid EMIs remain unchanged).
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="ced_booking_date_input" class="form-label fw-bold">
                            <i class="fa-light fa-calendar-days text-primary me-1"></i> Booking Date <span class="text-danger">*</span>
                        </label>
                        <input type="date" 
                               name="booking_date" 
                               id="ced_booking_date_input" 
                               class="form-control" 
                               value="<?= !empty($clientDetails['booking_date']) ? date('Y-m-d', strtotime($clientDetails['booking_date'])) : '' ?>" 
                               required>
                        <small class="text-muted d-block mt-1">Updates booking date and payment date for booking money.</small>
                    </div>

                    <div class="mb-3">
                        <label for="ced_sales_by_id_select" class="form-label fw-bold">
                            <i class="fa-light fa-user-tie text-primary me-1"></i> Sales By (Agent) <span class="text-danger">*</span>
                        </label>
                        <?php 
                            $currentAgentId = $clientDetails['sales_by_id'] ?? $clientDetails['assigned_id'] ?? 0;
                        ?>
                        <?php if (!empty($clientDetails['agent_name'])): ?>
                            <div class="p-2 mb-2 rounded-2 border d-flex align-items-center justify-content-between" style="background-color: #f8fafc; font-size: 13px;">
                                <div>
                                    <span class="text-muted">Current Sales Agent:</span> 
                                    <strong class="text-dark ms-1"><?= htmlspecialchars($clientDetails['agent_name']) ?></strong>
                                </div>
                                <?php if (!empty($clientDetails['agent_employee_id'])): ?>
                                    <span class="badge" style="background-color: #059669; color: #fff; font-size: 11px; padding: 4px 8px;">EMP: <?= htmlspecialchars($clientDetails['agent_employee_id']) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <select name="sales_by_id" id="ced_sales_by_id_select" class="form-select select2" data-placeholder="Select Sales Agent" required style="width: 100%;">
                            <option value="">-- Select Sales Agent --</option>
                            <?php if (!empty($salesUsers)): ?>
                                <?php foreach ($salesUsers as $su): ?>
                                    <?php 
                                        $empIdStr = !empty($su['employee_id']) ? " [EMP: " . htmlspecialchars($su['employee_id']) . "]" : "";
                                        $rname = $su['role_name'] ?? '';
                                        $sname = $su['sec_role_name'] ?? '';
                                        $displayRole = $rname ?: ($sname ?: 'Sales');
                                        $isSelected = ((int)$su['id'] === (int)$currentAgentId);
                                    ?>
                                    <option value="<?= $su['id'] ?>" <?= $isSelected ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($su['full_name']) ?><?= $empIdStr ?> (<?= htmlspecialchars($displayRole) ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <small class="text-muted d-block mt-1">Updates sales_by_id in booking and assigned_id in lead.</small>
                    </div>

                    <div class="mb-3">
                        <label for="ced_total_emi_no_input" class="form-label fw-bold d-flex justify-content-between align-items-center">
                            <span><i class="fa-light fa-list-ol text-primary me-1"></i> Total EMI (Installments) <span class="text-danger">*</span></span>
                            <?php if (!empty($paymentSummary['completed_emi']) && $paymentSummary['completed_emi'] > 0): ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary" style="font-size: 11px; font-weight: 600;">
                                    Completed: <?= (int)$paymentSummary['completed_emi'] ?>
                                </span>
                            <?php endif; ?>
                        </label>
                        <div class="input-group">
                            <input type="number" 
                                   name="total_emi_no" 
                                   id="ced_total_emi_no_input" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($paymentSummary['total_emi'] ?? $clientDetails['booking_total_emi_no'] ?? 0) ?>" 
                                   min="<?= max(1, (int)($paymentSummary['completed_emi'] ?? 0)) ?>" 
                                   max="360"
                                   required>
                            <span class="input-group-text bg-light text-muted">Installments</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <small class="text-muted">
                                Minimum: <?= max(1, (int)($paymentSummary['completed_emi'] ?? 0)) ?> (cannot be less than completed EMIs).
                            </small>
                            <?php if (!empty($paymentSummary['emi_updated_at'])): ?>
                                <small class="text-muted" style="font-size: 11px;">
                                    <i class="fa-regular fa-clock me-1"></i>Last updated: <?= date('d M, Y', strtotime($paymentSummary['emi_updated_at'])) ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-3 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold px-4" id="btnSaveBookingInfoCed">
                        <i class="fa-solid fa-save me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="transferNoteModal" tabindex="-1" aria-labelledby="transferNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="<?= $GLOBALS['base_url'] ?>/save-transfer-note">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="transferNoteModalLabel">Transfer Note</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="booking_id" value="<?= $clientDetails['booking_id'] ?? '' ?>">
                    <input type="hidden" name="source_page" value="ced">
                    <input type="hidden" name="transfer_status" value="<?= htmlspecialchars($clientDetails['transfer_status'] ?? 0) ?>">
                    
                    <div class="mb-3">
                        <label for="transfer_date" class="form-label">Transfer Date</label>
                        <input type="date" class="form-control" id="transfer_date" name="transfer_date" value="<?= htmlspecialchars($clientDetails['transfer_date'] ?? '') ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="transfer_note" class="form-label">Transfer Note</label>
                        <textarea class="form-control" id="transfer_note" name="transfer_note" rows="4"><?= htmlspecialchars($clientDetails['transfer_note'] ?? '') ?></textarea>
                    </div>
                    
                    <?php if (!empty($clientDetails['transfer_updated_by_name'])): ?>
                    <div class="text-muted small mt-2">
                        Last updated by: <strong><?= htmlspecialchars($clientDetails['transfer_updated_by_name']) ?></strong>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Note</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ===== EMI FOLLOW UP MODAL ===== -->
<div class="modal fade" id="emiFollowupModal" tabindex="-1" aria-labelledby="emiFollowupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emiFollowupModalLabel">EMI Follow Up Notes</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal"><i class="fa-solid fa-xmark-large"></i></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-5 border-end" id="followupFormCol">
                        <h6 class="mb-3">Add New Note</h6>
                        <input type="hidden" id="followupBookingId">
                        <div class="mb-3">
                            <label class="form-label fs-13 font-semibold text-muted">Installment / EMI</label>
                            <select id="followupEmiNo" class="form-select form-select-sm" onchange="loadEmiFollowups($('#followupBookingId').val(), this.value)">
                                <option value="0">General / Overall Follow Up</option>
                                <option value="999">Development Charge</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fs-13 font-semibold text-muted">Date of Activity <span class="text-danger">*</span></label>
                            <input type="date" id="followupActivityDate" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fs-13 font-semibold text-muted">Mode of Activity <span class="text-danger">*</span></label>
                            <select id="followupActivityMode" class="form-select form-select-sm">
                                <option value="">Select Mode</option>
                                <option value="Phone Call">Phone Call</option>
                                <option value="WhatsApp">WhatsApp</option>
                                <option value="SMS">SMS</option>
                                <option value="Email">Email</option>
                                <option value="Meeting">Meeting</option>
                            </select>
                        </div>
                        <div class="mb-3" id="followupCallStatusGroup">
                            <label class="form-label fs-13 font-semibold text-muted">Call Status</label>
                            <select id="followupCallStatus" class="form-select form-select-sm">
                                <option value="">Select Call Status</option>
                                <option value="Incoming">Incoming</option>
                                <option value="Outgoing">Outgoing</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fs-13 font-semibold text-muted">Reason for Activity <span class="text-danger">*</span></label>
                            <select id="followupActivityReason" class="form-select form-select-sm">
                                <option value="">Select Reason</option>
                                <option value="EMI Payment Follow-up">EMI Payment Follow-up</option>
                                <option value="Payment Reminder (General)">Payment Reminder (General)</option>
                                <option value="Development Charge Follow-up">Development Charge Follow-up</option>
                                <option value="Overdue Payment Follow-up">Overdue Payment Follow-up</option>
                                <option value="Handover / Registration Discussion">Handover / Registration Discussion</option>
                                <option value="Document Collection">Document Collection</option>
                                <option value="Client Query / Issue Resolution">Client Query / Issue Resolution</option>
                                <option value="Commitment Date Extension">Commitment Date Extension</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fs-13 font-semibold text-muted">Note <span class="text-danger">*</span></label>
                            <textarea id="followupNoteInput" class="form-control" rows="3" placeholder="Type your follow up note here..."></textarea>
                        </div>
                        <button type="button" class="btn btn-primary w-100" onclick="saveEmiFollowup()">Save Note</button>
                    </div>
                    <div class="col-md-7" id="followupHistoryCol">
                        <h6 class="mb-3">Follow Up History</h6>
                        <div id="followupHistoryContainer" style="max-height: 300px; overflow-y: auto;">
                            <div class="text-center text-muted py-4">Loading history...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Management Discount Approval Modal -->
<?php if ($isManagementUser && isset($clientDetails['booking_id'])): ?>
<div class="modal fade" id="editManagementDiscountModal" tabindex="-1" aria-labelledby="editManagementDiscountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff;">
                <h5 class="modal-title text-white" id="editManagementDiscountModalLabel">
                    <i class="fa-solid fa-tags me-2"></i>Edit / Approve Booking Discount
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= $GLOBALS['base_url'] ?>/update-management-booking-discount" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="booking_id" value="<?= (int)$clientDetails['booking_id'] ?>">
                    
                    <!-- Client & Unit info summary card -->
                    <div class="p-3 mb-3 rounded bg-light border">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted fs-13">Client:</span>
                            <span class="fw-bold fs-13"><?= htmlspecialchars($clientDetails['client_name'] ?? 'N/A') ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted fs-13">Project / Unit:</span>
                            <span class="fw-semibold fs-13"><?= htmlspecialchars($clientDetails['project_name'] ?? '') ?> - Unit <?= htmlspecialchars($clientDetails['unit_name'] ?? '') ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted fs-13">Total Land Share (Original):</span>
                            <span class="fw-bold fs-13 text-primary">TK. <?= number_format((float)($clientDetails['total_land_share'] ?? 0), 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted fs-13">Current Remaining Land Share:</span>
                            <?php if (!empty($clientDetails['discount_amount']) && (float)$clientDetails['discount_amount'] == (float)$clientDetails['total_land_share']): ?>
                                <span class="fw-bold fs-13 text-success">Paid by Discount Approved</span>
                            <?php else: ?>
                                <span class="fw-bold fs-13 text-danger">TK. <?= number_format((float)($clientDetails['remaining_land_share'] ?? 0), 2) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Discount Attachment Preview -->
                    <?php if (!empty($clientDetails['past_approval_attachment'])): 
                        $mgmtAttach = $clientDetails['past_approval_attachment'];
                        $mgmtAttachUrl = $GLOBALS['base_url'] . '/' . ltrim($mgmtAttach, '/');
                        $mgmtExt = strtolower(pathinfo($mgmtAttach, PATHINFO_EXTENSION));
                        $isMgmtImg = in_array($mgmtExt, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
                    ?>
                        <div class="p-3 mb-3 rounded border" style="background-color: #f0fdf4; border-color: #bbf7d0 !important;">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-bold text-success fs-13">
                                    <i class="fa-solid fa-paperclip me-1"></i> Discount Supporting Document / Slip
                                </span>
                                <a href="<?= $mgmtAttachUrl ?>" target="_blank" class="btn btn-xs btn-outline-success py-1 px-2" style="font-size: 12px;">
                                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Open Original File
                                </a>
                            </div>
                            <?php if ($isMgmtImg): ?>
                                <div class="text-center mt-2">
                                    <a href="<?= $mgmtAttachUrl ?>" target="_blank">
                                        <img src="<?= $mgmtAttachUrl ?>" alt="Discount Attachment" class="img-fluid rounded border shadow-sm" style="max-height: 180px; object-fit: contain;">
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="small text-muted">
                                    <i class="fa-solid fa-file-lines me-1"></i> <?= htmlspecialchars(basename($mgmtAttach)) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="p-2 mb-3 rounded bg-light border text-muted fs-12">
                            <i class="fa-solid fa-circle-info me-1"></i> No discount attachment uploaded with this booking.
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Approved Discount Amount (TK) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-white">TK.</span>
                            <input type="number" 
                                   step="any" 
                                   min="0" 
                                   max="<?= (float)($clientDetails['total_land_share'] ?? 999999999) ?>"
                                   name="approved_discount" 
                                   id="mgmt_approved_discount_input" 
                                   class="form-control form-control-lg fw-bold text-success" 
                                   value="<?= (float)($clientDetails['approved_amount'] ?? $clientDetails['discount_amount'] ?? 0) ?>" 
                                   required>
                        </div>
                        <small class="text-muted mt-1 d-block" style="font-size: 12px;">
                            <i class="fa-solid fa-circle-info text-primary me-1"></i>
                            This discount will be approved by <strong>Management</strong> and automatically deducted from the client's <strong>Land Share</strong> target and remaining balance.
                        </small>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold px-4">
                        <i class="fa-solid fa-check-circle me-1"></i> Save & Approve Discount
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal: Edit Payment Date (Booking & Registration Only) -->
<div class="modal fade" id="editPaymentDateModal" tabindex="-1" aria-labelledby="editPaymentDateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #0284c7, #0369a1); color: #fff;">
                <h5 class="modal-title text-white" id="editPaymentDateModalLabel">
                    <i class="fa-solid fa-calendar-pen me-2"></i>Edit Payment Date
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= $GLOBALS['base_url'] ?>/update-payment-date" method="POST">
                <input type="hidden" name="payment_id" id="epd_payment_id" value="">
                <input type="hidden" name="booking_id" id="epd_booking_id" value="<?= htmlspecialchars($bookingId ?? '') ?>">
                <input type="hidden" name="payment_type" id="epd_payment_type" value="">
                <input type="hidden" name="redirect_to" value="clients-cash-in-history-view/<?= htmlspecialchars($bookingId ?? '') ?>">

                <div class="modal-body p-4 bg-light">
                    <!-- Summary Card -->
                    <div class="p-3 mb-3 bg-white rounded border shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted fs-13">Payment Type:</span>
                            <span class="badge bg-primary fs-12 px-2 py-1" id="epd_badge">BOOKING</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted fs-13">Details:</span>
                            <strong class="text-dark fs-13" id="epd_details">Booking Money</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted fs-13">Amount:</span>
                            <strong class="text-success fs-14" id="epd_amount">TK. 0.00</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted fs-13">Current Date:</span>
                            <span class="badge bg-secondary-subtle text-secondary border fs-12" id="epd_current_date">-</span>
                        </div>
                    </div>

                    <!-- Date Picker -->
                    <div class="mb-2">
                        <label class="form-label fw-bold text-dark fs-14">
                            New Payment Date <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="payment_date" id="epd_payment_date" class="form-control form-control-lg" required>
                        <small class="text-muted mt-2 d-block" style="font-size: 12px; line-height: 1.4;">
                            <i class="fa-solid fa-circle-info text-info me-1"></i>
                            Only the payment date will be updated. Paid amounts, verification status, and ledger records remain unchanged.
                        </small>
                    </div>
                </div>

                <div class="modal-footer bg-white border-top py-3">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">
                        <i class="fa-solid fa-check me-1"></i> Update Date
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Unverified Transaction -->
<div class="modal fade" id="editUnverifiedPaymentModal" tabindex="-1" aria-labelledby="editUnverifiedPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #0ea5e9, #0284c7); color: #fff;">
                <h5 class="modal-title text-white" id="editUnverifiedPaymentModalLabel">
                    <i class="fa-solid fa-pen-to-square me-2"></i>Edit Unverified Transaction
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= $GLOBALS['base_url'] ?>/update-transaction" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="payment_id" id="edit_unverified_payment_id">
                    <input type="hidden" name="booking_id" id="edit_unverified_booking_id" value="<?= htmlspecialchars($bookingId ?? '') ?>">
                    <input type="hidden" name="redirect_to" value="clients-cash-in-history-view/<?= htmlspecialchars($bookingId ?? '') ?>">

                    <div class="alert alert-info py-2 px-3 mb-3 d-flex align-items-center gap-2" style="font-size: 13px;">
                        <i class="fa-solid fa-circle-info fs-5 text-primary"></i>
                        <div>
                            This transaction is not verified yet. Updating will record your name (<strong><?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Current User') ?></strong>) as the editor.
                        </div>
                    </div>

                    <div class="row ">
                        <!-- Payment Type -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Payment Type <span class="text-danger">*</span></label>
                            <select name="payment_type" id="edit_unverified_payment_type" class="form-select" required>
                                <option value="development_charge">Development Charge</option>
                                <option value="shop_service_charge">Shop Service Charge</option>
                                <option value="emi">EMI Payment</option>
                                <option value="land_share">Land Share</option>
                                <option value="booking_money">Booking Money</option>
                                <option value="registration">Registration</option>
                                <option value="mutation_cost">Mutation Cost</option>
                            </select>
                        </div>

                        <!-- Amount -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Paid Amount (TK) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">TK.</span>
                                <input type="number" step="any" min="1" name="amount" id="edit_unverified_amount" class="form-control fw-bold" required>
                            </div>
                        </div>

                        <!-- Payment Date -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" id="edit_unverified_payment_date" class="form-control" required>
                        </div>

                        <!-- Payment Method -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Payment Method <span class="text-danger">*</span></label>
                            <select name="payment_method" id="edit_unverified_payment_method" class="form-select" required>
                                <option value="cash">Cash</option>
                                <option value="bank">Bank / Cheque</option>
                                <option value="online">Online / MFS</option>
                            </select>
                        </div>

                        <!-- Bank Dropdown -->
                        <div class="col-md-6 edit-bank-group">
                            <label class="form-label fw-bold">Bank Name</label>
                            <select name="bank_id" id="edit_unverified_bank_id" class="form-select">
                                <option value="">-- Select Bank --</option>
                                <?php if (!empty($banks)): ?>
                                    <?php foreach ($banks as $b): ?>
                                        <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['bank_name'] . (!empty($b['branch_name']) ? ' - ' . $b['branch_name'] : '')) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- Account No -->
                        <div class="col-md-6 edit-bank-group">
                            <label class="form-label fw-bold">Bank Account Number</label>
                            <input type="text" name="bank_account_no" id="edit_unverified_bank_account_no" class="form-control" placeholder="Account Number">
                        </div>

                        <!-- Cheque No -->
                        <div class="col-md-6 edit-cheque-group">
                            <label class="form-label fw-bold">Cheque No</label>
                            <input type="text" name="cheque_no" id="edit_unverified_cheque_no" class="form-control" placeholder="Cheque Number">
                        </div>

                        <!-- Online Type -->
                        <div class="col-md-6 edit-online-group">
                            <label class="form-label fw-bold">Online / MFS Type</label>
                            <input type="text" name="online_type" id="edit_unverified_online_type" class="form-control" placeholder="e.g. bKash, Nagad, Card">
                        </div>

                        <!-- Transaction Reference -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Transaction Reference / Trx ID</label>
                            <input type="text" name="transaction_ref" id="edit_unverified_transaction_ref" class="form-control" placeholder="Trx ID / Ref No">
                        </div>

                        <!-- Due Amount (for EMI only) -->
                        <div class="col-md-6 edit-due-group" style="display:none;">
                            <label class="form-label fw-bold">Due EMI Amount (TK)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">TK.</span>
                                <input type="number" step="any" min="0" name="due_emi_amount" id="edit_unverified_due_emi_amount" class="form-control" value="0">
                            </div>
                        </div>

                        <!-- Proof Attachments -->
                        <div class="col-12">
                            <label class="form-label fw-bold">Upload New Document / Proof (Optional)</label>
                            <input type="file" name="proof_files[]" class="form-control" multiple accept="image/*,application/pdf">
                            <small class="text-muted">Max 5MB per file (JPG, PNG, PDF).</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Update Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function toggleEditFields() {
        const method = document.getElementById('edit_unverified_payment_method') ? document.getElementById('edit_unverified_payment_method').value : 'cash';
        const type = document.getElementById('edit_unverified_payment_type') ? document.getElementById('edit_unverified_payment_type').value : '';
        
        const bankGroups = document.querySelectorAll('.edit-bank-group');
        const chequeGroups = document.querySelectorAll('.edit-cheque-group');
        const onlineGroups = document.querySelectorAll('.edit-online-group');
        const dueGroup = document.querySelector('.edit-due-group');

        if (method === 'bank') {
            bankGroups.forEach(el => el.style.display = '');
            chequeGroups.forEach(el => el.style.display = '');
            onlineGroups.forEach(el => el.style.display = 'none');
        } else if (method === 'online') {
            bankGroups.forEach(el => el.style.display = '');
            chequeGroups.forEach(el => el.style.display = 'none');
            onlineGroups.forEach(el => el.style.display = '');
        } else {
            bankGroups.forEach(el => el.style.display = 'none');
            chequeGroups.forEach(el => el.style.display = 'none');
            onlineGroups.forEach(el => el.style.display = 'none');
        }

        if (dueGroup) {
            dueGroup.style.display = (type === 'emi') ? '' : 'none';
        }
    }

    const methodSelect = document.getElementById('edit_unverified_payment_method');
    if (methodSelect) methodSelect.addEventListener('change', toggleEditFields);

    const typeSelect = document.getElementById('edit_unverified_payment_type');
    if (typeSelect) typeSelect.addEventListener('change', toggleEditFields);

    document.querySelectorAll('.btn-edit-unverified-payment').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_unverified_payment_id').value = this.dataset.paymentId || '';
            document.getElementById('edit_unverified_booking_id').value = this.dataset.bookingId || '';
            document.getElementById('edit_unverified_payment_type').value = this.dataset.paymentType || 'development_charge';
            document.getElementById('edit_unverified_amount').value = this.dataset.amount || '';
            document.getElementById('edit_unverified_payment_date').value = this.dataset.paymentDate || '';
            document.getElementById('edit_unverified_payment_method').value = this.dataset.paymentMethod || 'cash';
            document.getElementById('edit_unverified_bank_id').value = this.dataset.bankId || '';
            document.getElementById('edit_unverified_bank_account_no').value = this.dataset.bankAccountNo || '';
            document.getElementById('edit_unverified_cheque_no').value = this.dataset.chequeNo || '';
            document.getElementById('edit_unverified_online_type').value = this.dataset.onlineType || '';
            document.getElementById('edit_unverified_transaction_ref').value = this.dataset.transactionRef || '';
            document.getElementById('edit_unverified_due_emi_amount').value = this.dataset.dueEmiAmount || 0;

            toggleEditFields();

            const modalEl = document.getElementById('editUnverifiedPaymentModal');
            if (modalEl) {
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            }
        });
    });

    // Handle Edit Payment Date click for Booking & Registration
    $(document).on('click', '.btn-edit-payment-date', function(e) {
        e.preventDefault();
        var btn = $(this);
        var pId = btn.attr('data-payment-id') || btn.data('payment-id') || '';
        var bId = btn.attr('data-booking-id') || btn.data('booking-id') || '';
        var pType = btn.attr('data-payment-type') || btn.data('payment-type') || '';
        var pLabel = btn.attr('data-payment-label') || btn.data('payment-label') || (pType === 'booking_money' ? 'BOOKING' : 'REGISTRATION');
        var pDetails = btn.attr('data-payment-details') || btn.data('payment-details') || (pType === 'booking_money' ? 'Booking Money' : 'Registration Money');
        var amount = btn.attr('data-amount') || btn.data('amount') || '0.00';
        var pDate = btn.attr('data-payment-date') || btn.data('payment-date') || '';
        var fDate = btn.attr('data-formatted-date') || btn.data('formatted-date') || pDate;

        $('#epd_payment_id').val(pId);
        $('#epd_booking_id').val(bId);
        $('#epd_payment_type').val(pType);
        $('#epd_badge').text(pLabel);
        $('#epd_details').text(pDetails);
        $('#epd_amount').text('TK. ' + amount);
        $('#epd_current_date').text(fDate);
        $('#epd_payment_date').val(pDate);

        var modalEl = document.getElementById('editPaymentDateModal');
        if (modalEl) {
            try {
                if (window.bootstrap && bootstrap.Modal) {
                    var myModal = (typeof bootstrap.Modal.getOrCreateInstance === 'function') 
                        ? bootstrap.Modal.getOrCreateInstance(modalEl) 
                        : (bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl));
                    myModal.show();
                } else {
                    $('#editPaymentDateModal').modal('show');
                }
            } catch (err) {
                $('#editPaymentDateModal').modal('show');
            }
        }
    });

    // Focus on EMI input when opened via edit-emi-btn
    $(document).on('click', '.edit-emi-btn', function () {
        $('#editBookingInfoModal').data('focus-target', 'emi');
    });

    // Initialize Select2 in editBookingInfoModal for CED
    $('#editBookingInfoModal').on('shown.bs.modal', function () {
        $('#ced_sales_by_id_select').select2({
            dropdownParent: $('#editBookingInfoModal'),
            width: '100%'
        });

        if ($(this).data('focus-target') === 'emi') {
            var emiInput = $('#ced_total_emi_no_input');
            if (emiInput.length) {
                emiInput.focus().select();
            }
            $(this).removeData('focus-target');
        }
    });

    // Handle AJAX submit for editBookingInfoFormCed
    $('#editBookingInfoFormCed').on('submit', function (e) {
        e.preventDefault();
        var form = $(this);
        var btn = $('#btnSaveBookingInfoCed');
        var originalBtnText = btn.html();

        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...');

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize() + '&is_ajax=1',
            dataType: 'json',
            success: function (res) {
                btn.prop('disabled', false).html(originalBtnText);
                if (res.success) {
                    $('#editBookingInfoModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated Successfully!',
                        text: res.message,
                        timer: 1800,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Update Failed',
                        text: res.message || 'Could not update booking info.'
                    });
                }
            },
            error: function () {
                btn.prop('disabled', false).html(originalBtnText);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Server error while updating booking info.'
                });
            }
        });
    });
});
</script>

<!-- Apply Previous Discount Modal -->
<div class="modal fade" id="applyPreviousDiscountModal" tabindex="-1" aria-labelledby="applyPreviousDiscountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header" style="background: linear-gradient(135deg, #17a2b8, #117a8b);">
                <h5 class="modal-title text-white" id="applyPreviousDiscountModalLabel">
                    <i class="fa-solid fa-tags me-2"></i>Apply Previous EMI Discount
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= $GLOBALS['base_url'] ?>/ced-add-transaction" method="POST">
                <div class="modal-body p-4 bg-light">
                    <!-- Hidden inputs for creating the zero-amount transaction -->
                    <input type="hidden" name="booking_id" value="<?= $clientDetails['booking_id'] ?>">
                    <input type="hidden" name="payment_type" value="emi">
                    <input type="hidden" name="apply_emi_discount" value="1">
                    <input type="hidden" name="is_previous_emi_discount" value="1">
                    <input type="hidden" name="amount" value="0">
                    <input type="hidden" name="payment_method" value="cash">

                    <div class="alert alert-info border-info" style="font-size:13px;">
                        <i class="fa-solid fa-circle-info me-1"></i> This feature creates an EMI discount that <strong>will reduce</strong> the remaining number of EMIs based on the discount amount. It's meant for backdating discounts on previous EMIs.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Discount Amount (TK) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-white text-muted">TK</span>
                            <input type="number" 
                                   class="form-control fw-semibold" 
                                   name="bulk_emi_discount" 
                                   required 
                                   min="1"
                                   placeholder="Enter discount amount">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Reference Number (Optional)</label>
                        <input type="text" class="form-control" name="transaction_ref" placeholder="Enter Ref Number or Previous Trx ID">
                    </div>
                </div>
                
                <div class="modal-footer bg-light border-top-0 pt-0 pb-4 pe-4">
                    <button type="button" class="btn btn-outline-secondary px-4 fw-medium" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-info px-4 fw-bold text-white shadow-sm">
                        <i class="fa-solid fa-check-circle me-1"></i> Apply Discount
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function cedExecuteDiscountAction(action, stage, reqId, bookingId) {
    const noteInput = document.getElementById('cedCardDiscountNote');
    const note = noteInput ? noteInput.value.trim() : '';
    const btn = document.getElementById('cedCardDiscountSubmitBtn');
    const originalText = btn ? btn.innerHTML : '';

    let endpoint = '';
    if (stage === 'op_head') {
        endpoint = (action === 'approve') 
            ? '<?= $GLOBALS['base_url'] ?>/approval-requests/approve-operation-head'
            : '<?= $GLOBALS['base_url'] ?>/approval-requests/reject-operation-head';
    } else {
        endpoint = (action === 'approve')
            ? '<?= $GLOBALS['base_url'] ?>/approval-requests/approve-management'
            : '<?= $GLOBALS['base_url'] ?>/approval-requests/reject-management';
    }

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Processing...';
    }

    fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            approval_request_id: reqId,
            source_table: 'lead_bookings',
            source_id: bookingId,
            note: note || (action === 'approve' ? 'Approved Booking Discount' : 'Rejected Booking Discount')
        })
    })
    .then(r => r.json())
    .then(data => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
        if (data.success) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: action === 'approve' ? 'Discount Approved!' : 'Discount Rejected',
                    text: data.message || 'Operation completed successfully.',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location.reload();
                });
            } else {
                alert(data.message || 'Success');
                window.location.reload();
            }
        } else {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Action Failed',
                    text: data.message || 'Failed to process request.'
                });
            } else {
                alert(data.message || 'Failed to process request.');
            }
        }
    })
    .catch(err => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Network Error',
                text: 'Error: ' + err
            });
        } else {
            alert('Network Error: ' + err);
        }
    });
}
</script>