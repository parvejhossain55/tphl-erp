<?php
$page_title = "Clients Cash in History";
$page = 'Clients';
$addPage = 'add transaction';
$addPage = true;
$show_breadcrumb = true;

$userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
$userRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
$canEditBookingInfo = in_array($userRoleId, [1, 3, 6, 20, 30, 32, 33, 36]) 
                    || (function_exists('isAccountsHead') && isAccountsHead()) 
                    || (function_exists('isAccountsExecutive') && isAccountsExecutive())
                    || strpos($userRoleName, 'account') !== false 
                    || strpos($userRoleName, 'admin') !== false;

$isAccExec = function_exists('isAccountsExecutive') ? isAccountsExecutive() : ($userRoleId === 36);
$canBulkDelete = (in_array($userRoleId, [1, 3, 6, 30]) || strpos($userRoleName, 'admin') !== false || strpos($userRoleName, 'management') !== false) && !$isAccExec;

$bookingId = (int)($clientDetails['booking_id'] ?? ($_GET['booking_id'] ?? 0));

$isClientsCommunicatedRole = (function_exists('isClientsCommunicated') && isClientsCommunicated())
    || ($userRoleId === 37)
    || ((int)($_SESSION['user']['secondary_role_id'] ?? 0) === 37)
    || ((int)($_SESSION['user']['id'] ?? 0) === 359);
?>

<script>
window.openEditPaymentDateModal = function(target) {
    if (!target) return;
    var btn = null;
    if (target.nodeType) {
        btn = target.closest ? target.closest('.btn-edit-payment-date, [data-payment-id]') : target;
    } else if (typeof jQuery !== 'undefined' && target instanceof jQuery) {
        btn = target[0];
    } else if (target.target) {
        btn = target.target.closest ? target.target.closest('.btn-edit-payment-date, [data-payment-id]') : target.target;
    }

    if (!btn) return;

    var pId = btn.getAttribute('data-payment-id') || '';
    var bId = btn.getAttribute('data-booking-id') || '';
    var pType = btn.getAttribute('data-payment-type') || 'emi';
    var pLabel = btn.getAttribute('data-payment-label') || 'Payment';
    var pDetails = btn.getAttribute('data-payment-details') || pLabel;
    var amount = btn.getAttribute('data-amount') || '0.00';
    var pDate = btn.getAttribute('data-payment-date') || '';

    var elPaymentId = document.getElementById('epd_payment_id');
    var elBookingId = document.getElementById('epd_booking_id');
    var elPaymentType = document.getElementById('epd_payment_type');
    var elDetails = document.getElementById('epd_details');
    var elAmount = document.getElementById('epd_amount');
    var elPaymentDate = document.getElementById('epd_payment_date');
    var elModalTitle = document.getElementById('epd_modal_title_text');
    var elTypeName = document.getElementById('epd_type_name');
    var elInputLabel = document.getElementById('epd_input_label');

    if (elPaymentId) elPaymentId.value = pId;
    if (elBookingId) elBookingId.value = bId;
    if (elPaymentType) elPaymentType.value = pType;
    if (elDetails) elDetails.textContent = pDetails;
    if (elAmount) elAmount.textContent = 'TK. ' + amount;
    if (elPaymentDate) elPaymentDate.value = pDate;
    if (elModalTitle) elModalTitle.textContent = 'Edit ' + pLabel + ' Date';
    if (elTypeName) elTypeName.textContent = pLabel;
    if (elInputLabel) elInputLabel.textContent = pLabel + ' Payment Date';
};
</script>

<div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
    <div>
        <strong><i class="fa-light fa-circle-info me-1"></i> Notice:</strong> If you see any data missing (e.g., missing EMI numbers, missing landshare fee) or errors in calculations, it may be due to incomplete booking data or project setup. Please <strong>contact the sales team first</strong> to resolve these issues before proceeding.
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>

<div class="row">
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
                        <strong>Booking Date:</strong> <span class="fw-semibold text-dark" id="displayBookingDate"><?= !empty($clientDetails['booking_date']) ? date('d M, Y', strtotime($clientDetails['booking_date'])) : 'N/A' ?></span>
                    </span>
                    <span class="text-muted d-inline-flex align-items-center">
                        <i class="fa-light fa-user-tie mx-2"></i>
                        <strong>Sales By:</strong>&nbsp;<span class="fw-semibold text-dark" id="displaySalesAgent"><?= htmlspecialchars($clientDetails['agent_name'] ?? 'N/A') ?><?= !empty($clientDetails['agent_employee_id']) ? ' [EMP: ' . htmlspecialchars($clientDetails['agent_employee_id']) . ']' : '' ?></span>
                        <?php if ($canEditBookingInfo): ?>
                            <button type="button" class="btn btn-sm btn-outline-warning text-dark fw-bold ms-2 py-0 px-2" data-bs-toggle="modal" data-bs-target="#editBookingInfoModal" title="Edit Booking Date, Sales Agent & Total EMI" style="font-size: 11px;">
                                <i class="fa-solid fa-pen-to-square me-1"></i> Edit Date, Sales & EMI
                            </button>
                        <?php endif; ?>
                    </span>
                </div>
                
                <div class="mt-3 d-flex align-items-center gap-2 flex-wrap">
                    <a href="<?= $GLOBALS['base_url'] ?>/client-emi-tracking/<?= $bookingId ?>" class="btn btn-sm text-white fw-bold shadow-sm" style="background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%); border: none; padding: 6px 14px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 6px rgba(59, 130, 246, 0.35);" title="Open Full EMI Statement & Tracking Page">
                        <i class="fa-solid fa-chart-pie"></i>
                        <span>EMI Tracking</span>
                    </a>

                    <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#transferNoteModal">
                        <i class="fa-light fa-exchange-alt me-1"></i> Transfer Note
                    </button>
                    
                    <?php if ($canEditBookingInfo): ?>
                        <button type="button" class="btn btn-sm btn-outline-warning text-dark fw-bold" data-bs-toggle="modal" data-bs-target="#editBookingInfoModal">
                            <i class="fa-solid fa-pen-to-square me-1"></i> Edit Booking Info
                        </button>
                    <?php endif; ?>
                    
                    <?php if (isset($clientDetails['transfer_status']) && $clientDetails['transfer_status'] == 1): ?>
                        <span class="badge bg-warning text-dark">Transferred</span>
                    <?php else: ?>
                        <span class="badge bg-success">Current</span>
                    <?php endif; ?>
                    
                    <?php if (isset($refundDetails) && $refundDetails['status'] === 'approved'): ?>
                        <span class="badge bg-danger"><i class="fa-light fa-undo me-1"></i>Refund Approved</span>
                        <?php if ($refundPaidAmount > 0): ?>
                            <span class="badge" style="background:#2d8cf0; color:#fff;"><i class="fa-light fa-money-bill-transfer me-1"></i>Refunded Amount: TK.<?= number_format($refundPaidAmount, 2) ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <?php
        $isShop = !empty($clientDetails['is_shop']) && $clientDetails['is_shop'] == 1;
        $devTarget = (float)($isShop ? ($paymentSummary['shop_service_charge_target'] ?? 0) : ($paymentSummary['flat_development_charges'] ?? 0));
        $devPaid   = (float)($isShop ? ($paymentSummary['shop_service_charge_paid_total']  ?? 0) : ($paymentSummary['development_charge_paid']  ?? 0));
        $devRemain = max(0, $devTarget - $devPaid);
        $svcRate   = (float)($isShop ? ($paymentSummary['shop_service_charge_pct'] ?? 0) : ($paymentSummary['service_charge_per_emi']   ?? 0));
        $landRemain= (float)($paymentSummary['remaining_land_share']     ?? 0);
        // Shops: Development Charge (flats.development_charges) tracked separately from Shop Service Charge
        $shopDevTarget = $isShop ? (float)($paymentSummary['flat_development_charges'] ?? 0) : 0;
        $shopDevPaid   = $isShop ? (float)($paymentSummary['development_charge_paid_total'] ?? 0) : 0;
        $shopDevRemain = max(0, $shopDevTarget - $shopDevPaid);
        $showShopDev   = $isShop && ($shopDevTarget > 0 || $shopDevPaid > 0); // only show Dev. Charge cards for shops if charges exist
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
        <!-- 2. Booking Money Paid -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-handshake"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Booking Money Paid</h6>
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
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title text-success">TK.<?= number_format($paymentSummary['discount_amount'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 4. Registration Paid -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-file-contract"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Registration Paid</h6>
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
                    <div class="card__icon"><span><i class="fa-light fa-map-marker-alt"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Total Land Share Value</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($paymentSummary['total_land_share'] ?? 0, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 6. Landshare Paid -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-landmark"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Landshare Paid</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <?php if (!empty($paymentSummary['discount_amount']) && (float)$paymentSummary['discount_amount'] == (float)$paymentSummary['total_land_share']): ?>
                                <h4 class="card__title text-success" style="font-size: 14px;">Paid by Discount Approved</h4>
                            <?php else: ?>
                                <h4 class="card__title">TK.<?= number_format($paymentSummary['landshare_paid'] ?? $paymentSummary['total_land_share_paid'] ?? 0, 2) ?></h4>
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
                    <div class="card__icon"><span><i class="fa-light fa-hourglass-half"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Remaining Land Share</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <?php if (!empty($paymentSummary['discount_amount']) && (float)$paymentSummary['discount_amount'] == (float)$paymentSummary['total_land_share']): ?>
                                <h4 class="card__title text-success" style="font-size: 14px;">Paid by Discount Approved</h4>
                            <?php else: ?>
                                <h4 class="card__title <?= $landRemain > 0 ? 'text-danger' : 'text-success' ?>">
                                    TK.<?= number_format($landRemain, 2) ?>
                                </h4>
                            <?php endif; ?>
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
                    <div class="card__title-wrap" style="width: 100%;">
                        <div class="d-flex justify-content-between align-items-center mb-10">
                            <h6 class="card__sub-title mb-0"><?= $isShop ? 'Shop Service Charge' : 'Development Charges' ?></h6>
                            <?php if ($isShop && !empty($clientDetails['flat_id']) && $canEditBookingInfo): ?>
                                <button type="button" 
                                        class="btn btn-sm btn-outline-warning text-dark fw-bold py-0 px-2" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editShopServiceChargeModal" 
                                        title="Edit Shop Service Charge" 
                                        style="font-size: 11px; height: 23px; display: inline-flex; align-items: center; border-radius: 4px;">
                                    <i class="fa-solid fa-pen-to-square me-1"></i>Edit
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title">TK.<?= number_format($devTarget, 2) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROW 3: Dev Charges Status & Construction -->
        <!-- 9. Dev Charges Paid -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon"><span><i class="fa-light fa-tools"></i></span></div>
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
                    <div class="card__icon"><span><i class="fa-light fa-tools"></i></span></div>
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

        <!-- ROW 4: Construction Remaining & EMI -->
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
                            <h4 class="card__title text-primary">
                                TK.<?= number_format($paymentSummary['total_bulk_emi_discount'] ?? 0, 2) ?>
                            </h4>
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
                                <a href="<?= $GLOBALS['base_url'] ?>/client-emi-tracking/<?= $bookingId ?>" 
                                   class="btn btn-sm btn-primary py-0 px-2 fw-semibold" 
                                   title="View Full EMI Tracking & Statement" 
                                   style="font-size: 11px; height: 23px; display: inline-flex; align-items: center; border-radius: 4px; background: #2563eb; border-color: #2563eb;">
                                    <i class="fa-solid fa-chart-line me-1"></i>Track EMIs
                                </a>
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
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php if (!$isShop): ?>
        <!-- 16. Service Charge Rate (Residential Flats Only) -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span style="color:#e67e22;"><i class="fa-light fa-percent"></i></span>
                    </div>
                    <div class="card__title-wrap" style="width: 100%;">
                        <div class="d-flex justify-content-between align-items-center mb-10">
                            <h6 class="card__sub-title mb-0">Service Charge Rate</h6>
                        </div>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title" style="color:#e67e22;"><?= (float)round($svcRate, 4) ?>% <small style="font-size:13px;color:#999;">per EMI</small></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 17. Total Service Fee Collected (Residential Flats Only) -->
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
        <?php endif; ?>
        <!-- 18. Last Payment -->
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
        <!-- 19. Bulk EMI Discount (conditional) -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-1">
                    <div class="card__icon">
                        <span style="color:#6c5ffc;"><i class="fa-light fa-tags"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Bulk EMI Discount</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <div class="d-flex flex-column align-items-start">
                                <h4 class="card__title" style="color:#6c5ffc;">
                                    TK.<?= number_format($paymentSummary['total_bulk_emi_discount'], 2) ?>
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
        <?php endif; ?>

    </div>


    <!-- Outstanding EMI Due Alert -->
    <?php
        $totalEmiDue = $paymentSummary['total_emi_due'] ?? 0;
    ?>
    <?php if ($totalEmiDue > 0): ?>
    <div class="col-xxl-12 mb-3">
        <div class="d-flex align-items-center justify-content-between mb-0 p-3"
             style="border-radius:10px;background:rgba(108,95,252,0.1);border:1px solid rgba(108,95,252,0.25);box-shadow:0 2px 10px rgba(108,95,252,0.1);">
            <div>
                <i class="fa-light fa-info-circle me-2" style="color:#6c5ffc;"></i>
                <strong style="color:#6c5ffc;">Outstanding EMI Due: TK.<?= number_format($totalEmiDue, 2) ?></strong>
                &mdash; <?= count($outstandingDues) ?> EMI payment(s) have unpaid balances.
                <small class="d-block mt-1 text-muted">This will be <strong>automatically cleared</strong> from your next EMI payment (oldest due first).</small>
            </div>
            <a href="<?= $GLOBALS['base_url'] ?>/<?= $isShop ? 'shop-client-cash-in-form' : 'client-cash-in-form' ?>?booking_id=<?= $clientDetails['booking_id'] ?>"
               class="btn btn-sm text-white" style="background:#6c5ffc;border-color:#6c5ffc;">
                <i class="fa-light fa-plus me-1"></i> Add EMI Payment
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Transaction History Table -->
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="card__header">
                <h5 class="card__title">Transaction History</h5>
            </div>
            <div class="col-xxl-12 mb-4">


                <div class="mt-3 d-flex flex-wrap gap-2 align-items-center mb-3">
                    <?php 
                        $rfStatus = $refundDetails['status'] ?? ((($clientDetails['booking_status'] ?? '') === 'refunded') ? 'approved' : '');
                        $isClientsCommOrCed = (function_exists('isClientsCommunicated') && isClientsCommunicated()) 
                            || in_array((int)($_SESSION['user']['role_id'] ?? 0), [7, 37], true)
                            || strpos(strtolower($_SESSION['user']['role_name'] ?? ''), 'communicat') !== false
                            || strpos(strtolower($_SESSION['user']['role_name'] ?? ''), 'client experience') !== false;
                    ?>
                    <?php if ($rfStatus === 'approved'): ?>
                        <a href="<?= $GLOBALS['base_url'] ?>/client-refund-create/<?= $clientDetails['booking_id'] ?>" class="btn btn-danger">
                            <i class="fa-light fa-money-bill-wave me-2"></i> Refund Paid Request
                        </a>
                    <?php elseif ($rfStatus === 'refunded'): ?>
                        <button class="btn btn-secondary" disabled>
                            <i class="fa-light fa-check-circle me-2"></i> Refunded Paid
                        </button>
                    <?php else: ?>
                        <?php if ($userRoleId === 7 || $userRoleId === 37 || (function_exists('isCreditRecovery') && isCreditRecovery()) || strpos(strtolower($_SESSION['user']['role_name'] ?? ''), 'client experience') !== false): ?>
                            <a href="<?= $GLOBALS['base_url'] ?>/ced-add-transaction-form?booking_id=<?= $clientDetails['booking_id'] ?>" class="btn btn-success">
                                <i class="fa-light fa-plus me-2"></i> Add Transaction
                            </a>
                            <button type="button" class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#applyPreviousDiscountModal">
                                <i class="fa-solid fa-tags me-1"></i> Apply Previous Discount
                            </button>
                        <?php elseif (!$isClientsCommunicatedRole && (!function_exists('isAccountsExecutive') || !isAccountsExecutive())): ?>
                            <a href="<?= $GLOBALS['base_url'] ?>/<?= $isShop ? 'shop-client-cash-in-form' : 'client-cash-in-form' ?>?booking_id=<?= $clientDetails['booking_id'] ?>" class="btn btn-primary">
                                <i class="fa-light fa-plus me-2"></i> Add Transaction
                            </a>
                            <button type="button" class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#applyPreviousDiscountModal">
                                <i class="fa-solid fa-tags me-1"></i> Apply Previous Discount
                            </button>
                            <a href="<?= $GLOBALS['base_url'] ?>/client-refund-create/<?= $clientDetails['booking_id'] ?>" class="btn btn-danger">
                                <i class="fa-light fa-undo me-2"></i> Refund
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if (!function_exists('isAccountsExecutive') || !isAccountsExecutive()): ?>
                    <a href="<?= $GLOBALS['base_url'] ?>/csv-import-form" class="btn btn-info text-white">
                        <i class="fa-light fa-file-csv me-2"></i> CSV Import
                    </a>
                    <?php endif; ?>
                    <a href="<?= $base_url ?>/client-statement/<?= $clientDetails['booking_id'] ?>" target="_blank" class="btn btn-success">
                        <i class="fa-light fa-file-invoice me-2"></i> Get Statement
                    </a>
                    <a href="<?= $base_url ?>/discount-statement/<?= $clientDetails['booking_id'] ?>" target="_blank" class="btn" style="background: linear-gradient(135deg, #6c5ffc, #8b5cf6); color:#fff; border:none;">
                        <i class="fa-light fa-tags me-2"></i> Discount Statement
                    </a>
                    <?php if ($canEditBookingInfo ?? false): ?>
                    <button type="button" class="btn btn-warning text-dark fw-bold" data-bs-toggle="modal" data-bs-target="#editBookingInfoModal">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit Booking Info
                    </button>
                    <?php endif; ?>
                    
                    <!-- Bulk Actions -->
                    <button type="button" class="btn btn-success" id="btnBulkVerify" disabled>
                        <i class="fa-solid fa-check-double me-1"></i> Verify Selected (<span id="bulkSelectedCount">0</span>)
                    </button>
                    <button type="button" class="btn" id="btnBulkReceipt" style="background-color: #6c5ffc; color: #fff;" disabled>
                        <i class="fa-solid fa-file-invoice me-1"></i> Generate Combined Receipt
                    </button>
                    <?php if ($canBulkDelete): ?>
                    <button type="button" class="btn btn-danger" id="btnBulkDelete" disabled>
                        <i class="fa-solid fa-trash me-1"></i> Move Selected to Trash (<span id="bulkDeleteCount">0</span>)
                    </button>
                    <?php endif; ?>

                    <div class="ms-auto d-flex align-items-center">
                        <div class="form-check form-switch p-2 bg-light border rounded mb-0">
                            <input class="form-check-input ms-0 me-2" type="checkbox" id="globalSendSmsToggle">
                            <label class="form-check-label fw-semibold text-primary" for="globalSendSmsToggle" style="cursor: pointer;">
                                <i class="fa-solid fa-envelope me-1"></i> Send SMS on Verify
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Payment Type Filter and Search -->
                <div class="mb-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex flex-wrap align-items-center gap-2">
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
                        <button type="button" class="btn btn-sm btn-outline-info payment-filter" data-filter="others">Others</button>
                    </div>
                    
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <div class="form-check form-switch m-0" style="background:rgba(255, 193, 7, 0.2); padding: 5px 10px 5px 35px; border-radius: 20px; border: 1px solid rgba(255, 193, 7, 0.4);">
                            <input class="form-check-input" type="checkbox" id="autoSelectLatestPending" style="cursor: pointer;">
                            <label class="form-check-label fw-bold text-dark" for="autoSelectLatestPending" style="cursor: pointer; font-size: 13px;">
                                Same Transaction (Latest Pending)
                            </label>
                        </div>
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <span class="input-group-text bg-light"><i class="fa-light fa-search"></i></span>
                            <input type="text" id="transactionSearchInput" class="form-control" placeholder="Search Trx ID / Batch / Ref...">
                        </div>
                    </div>
                </div>

                <div class="table__wrapper table-responsive">
                    <table class="table table-hover" id="transactionTable">
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align: center;">
                                    <input type="checkbox" id="selectAllTransactions" class="form-check-input">
                                </th>
                                <th style="white-space:nowrap;">Date</th>
                                <th>Payment Type</th>
                                <th>Details</th>
                                <th style="white-space:nowrap;">Amount</th>
                                <th style="white-space:nowrap;">Due Amount</th>
                                <th style="white-space:nowrap;">Land Share</th>
                                <th style="white-space:nowrap;">Dev. Charge</th>
                                <?php if ($isShop): ?>
                                <th style="white-space:nowrap;">Shop Svc Charge</th>
                                <?php endif; ?>
                                <th style="white-space:nowrap;">Service Fee</th>
                                <th>Method</th>
                                <th>Bank</th>
                                <th>Trx ID</th>
                                <th>Status</th>
                                <th>Handover To</th>
                                <th>Verification</th>
                                <th>Verified By</th>
                                <th style="white-space:nowrap;">Batch ID</th>
                                <th style="white-space:nowrap;min-width:90px;">Document</th>
                                <th>Added By</th>
                                <th style="white-space:nowrap;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <?php 
                                    $vs = $transaction['verification_status'] ?? 'pending';
                                ?>
                                <tr data-payment-id="<?= $transaction['id'] ?>" 
                                    data-batch-id="<?= htmlspecialchars($transaction['batch_id'] ?? '') ?>"
                                    data-trx-id="<?= htmlspecialchars($transaction['transaction_ref'] ?? '') ?>"
                                    data-verification-status="<?= $vs ?>"
                                    data-payment-type="<?= htmlspecialchars($transaction['payment_type'] ?? '') ?>">
                                    <td style="text-align: center;">
                                        <?php if (is_numeric($transaction['id'])): ?>
                                            <input type="checkbox" class="form-check-input row-checkbox" value="<?= $transaction['id'] ?>">
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span><?php echo $transaction['formatted_date']; ?></span>
                                        <?php if (($transaction['payment_type'] ?? '') === 'booking_money' && $canEditBookingInfo): ?>
                                            <button type="button" class="btn btn-link p-0 ms-1 text-primary"
                                                    style="text-decoration:none; vertical-align: middle;"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editBookingInfoModal" 
                                                    title="Edit Booking Date, Sales Agent & Total EMI">
                                                <i class="fa-solid fa-pen-to-square" style="font-size:12px;"></i>
                                            </button>
                                        <?php elseif ($canEditBookingInfo && !empty($transaction['id'])): ?>
                                            <?php 
                                                $trxDateYmd = !empty($transaction['payment_date']) ? date('Y-m-d', strtotime($transaction['payment_date'])) : '';
                                                $trxLabel = $transaction['installment_label'] ?? 'Payment';
                                                $trxDetails = $transaction['payment_details'] ?? ($transaction['payment_type'] ?? 'Payment');
                                            ?>
                                            <button type="button" class="btn btn-link p-0 ms-1 text-primary btn-edit-payment-date"
                                                    style="text-decoration:none; vertical-align: middle;"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editPaymentDateModal"
                                                    data-payment-id="<?= (int)($transaction['id'] ?? 0) ?>"
                                                    data-booking-id="<?= (int)$bookingId ?>"
                                                    data-payment-type="<?= htmlspecialchars($transaction['payment_type'] ?? 'emi', ENT_QUOTES) ?>"
                                                    data-payment-label="<?= htmlspecialchars($trxLabel, ENT_QUOTES) ?>"
                                                    data-payment-details="<?= htmlspecialchars($trxDetails, ENT_QUOTES) ?>"
                                                    data-amount="<?= htmlspecialchars(number_format((float)($transaction['amount'] ?? 0), 2), ENT_QUOTES) ?>"
                                                    data-payment-date="<?= $trxDateYmd ?>"
                                                    data-formatted-date="<?= htmlspecialchars($transaction['formatted_date'] ?? '', ENT_QUOTES) ?>"
                                                    onclick="openEditPaymentDateModal(this)"
                                                    title="Edit <?= htmlspecialchars($trxLabel, ENT_QUOTES) ?> Payment Date">
                                                <i class="fa-solid fa-pen-to-square" style="font-size:12px; pointer-events: none;"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= ($transaction['payment_type'] ?? '') === 'others' ? 'bg-info text-dark' : 'bg-primary' ?>">
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
                                    <td><?php echo htmlspecialchars($transaction['payment_details'] ?? '', ENT_QUOTES); ?></td>
                                    <td style="white-space:nowrap;">
                                        <?php
                                            $isEmi       = ($transaction['payment_type'] === 'emi');
                                            $grossAmount = (float)($transaction['amount'] ?? 0);
                                            $serviceFee  = (float)($transaction['service_fee'] ?? 0);
                                            $netAmount   = $isEmi ? ($grossAmount - $serviceFee) : $grossAmount;
                                        ?>
                                        TK.<?= number_format($netAmount, 2) ?>
                                        <?php if ($isEmi && $serviceFee > 0): ?>
                                            <br><span class="text-muted" style="font-size:11px;white-space:nowrap;">
                                                Gross: TK.<?= number_format($grossAmount, 2) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <!-- Due Amount -->
                                     <td style="white-space:nowrap;">
                                         <?php if (!empty($transaction['due_emi_amount']) && $transaction['due_emi_amount'] > 0): ?>
                                             TK.<?php echo number_format($transaction['due_emi_amount'], 2); ?>
                                         <?php else: ?>
                                             <span class="text-muted">-</span>
                                         <?php endif; ?>
                                     </td>
                                    <td style="white-space:nowrap;">
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
                                    <td style="white-space:nowrap;">
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
                                        <?php if (($transaction['payment_method'] ?? '') === 'bank' || ($transaction['payment_method'] ?? '') === 'cheque'): ?>
                                            <?php if (!empty($transaction['bank_name'])): ?>
                                                <div style="font-size:11px;">
                                                    <strong>From:</strong> <?= htmlspecialchars($transaction['bank_name']) ?>
                                                    <?= !empty($transaction['bank_account_no']) ? '<br>A/C: ' . htmlspecialchars($transaction['bank_account_no']) : '' ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($transaction['company_bank_name'])): ?>
                                                <div style="font-size:11px; margin-top: 4px; padding-top: 4px; border-top: 1px dashed #ddd;">
                                                    <strong class="text-primary"><i class="fa-light fa-building-columns me-1"></i>To:</strong> <?= htmlspecialchars($transaction['company_bank_name']) ?>
                                                    <?= !empty($transaction['company_account_name']) ? '<br>' . htmlspecialchars($transaction['company_account_name']) : '' ?>
                                                    <?= !empty($transaction['company_account_number']) ? '<br>A/C: ' . htmlspecialchars($transaction['company_account_number']) : '' ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (empty($transaction['bank_name']) && empty($transaction['company_bank_name'])): ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
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
                                    <?php 
                                        $vs = $transaction['verification_status'] ?? 'pending';
                                        $hs = $transaction['handover_status'] ?? null;
                                    ?>
                                    <!-- HANDOVER TO -->
                                    <td>
                                        <?php if (!empty($transaction['handover_to'])): ?>
                                            <span class="badge bg-info text-dark">
                                                <?= htmlspecialchars($transaction['handover_to_name'] ?? 'Mgmt') ?>
                                            </span>
                                            <?php if ($transaction['handover_status'] === 'pending'): ?>
                                                <small class="d-block text-warning mt-1"><i class="fa-light fa-clock"></i> Pending</small>
                                            <?php elseif ($transaction['handover_status'] === 'approved'): ?>
                                                <small class="d-block text-success mt-1"><i class="fa-light fa-check"></i> Approved</small>
                                            <?php elseif ($transaction['handover_status'] === 'rejected'): ?>
                                                <small class="d-block text-danger mt-1"><i class="fa-light fa-times"></i> Rejected</small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?php if ($vs === 'pending'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-info py-0 px-1" onclick="openHandoverModal(<?= $transaction['id'] ?>)">
                                                    <i class="fa-light fa-handshake"></i> Handover
                                                </button>
                                            <?php else: ?>
                                                
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>

                                    <!-- VERIFICATION CONTROL -->
                                    <td class="ver-cell" id="ver-cell-<?= $transaction['id'] ?>">
                                        <?php
                                            if ($vs === 'verified'):
                                        ?>
                                            <span class="badge bg-success ver-badge">
                                                <i class="fa-light fa-check-circle me-1"></i>Verified
                                            </span>
                                        <?php elseif ($vs === 'rejected'): ?>
                                            <span class="badge bg-danger ver-badge">
                                                <i class="fa-light fa-times-circle me-1"></i>Rejected
                                            </span>
                                            <?php if (!empty($transaction['rejection_reason'])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger ms-1 py-0 px-1"
                                                onclick="showRejectionReason('<?= htmlspecialchars(addslashes($transaction['rejection_reason']), ENT_QUOTES) ?>')"
                                                title="View rejection reason" style="font-size:11px;">
                                                <i class="fa-light fa-comment-exclamation"></i>
                                            </button>
                                            <?php endif; ?>
                                        <?php else: /* pending */ ?>
                                            <?php
                                                // ── ACCOUNTS-ONLY: show action buttons only to Accounts/Admin/Executive ──
                                                $canVerify = in_array((int)($_SESSION['user']['role_id'] ?? 0), [1, 3, 6, 30, 36]) || (function_exists('isAccountsExecutive') && isAccountsExecutive()) || hasPermission('Cash In Verify');
                                            ?>
                                            <?php if ($canVerify): ?>
                                                <?php if ($hs === 'pending'): ?>
                                                    <span class="badge bg-warning text-dark"><i class="fa-light fa-lock"></i> Awaiting Mgmt</span>
                                                <?php else: ?>
                                                    <div class="d-flex gap-1">
                                                        <button type="button"
                                                            class="btn btn-sm btn-verify"
                                                            onclick="<?= ($hs === 'rejected') ? "openForceVerifyModal({$transaction['id']})" : "verifyPayment({$transaction['id']}, 'verify')" ?>"
                                                            title="Verify this payment">
                                                            <i class="fa-light fa-check"></i> Verify
                                                        </button>
                                                <button type="button"
                                                    class="btn btn-sm btn-reject"
                                                    onclick="openRejectModal(<?= $transaction['id'] ?>)"
                                                    title="Reject this payment">
                                                    <i class="fa-light fa-times"></i> Reject
                                                </button>
                                            </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                            <span class="badge bg-warning text-dark">
                                                <i class="fa-light fa-clock me-1"></i>Pending
                                            </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <!-- VERIFIED BY -->
                                    <td id="verified-by-<?= $transaction['id'] ?>">
                                        <div><?= htmlspecialchars($transaction['verified_by_name'] ?? '-') ?></div>
                                        <?php if (!empty($transaction['verified_at'])): ?>
                                            <div class="text-muted mt-1" style="font-size: 0.85em;">
                                                <i class="fa-light fa-clock me-1"></i><?= date('d M, y h:i A', strtotime($transaction['verified_at'])) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- BATCH ID COLUMN -->
                                    <td style="white-space:nowrap;">
                                        <?php 
                                            $rawBatchId = trim((string)($transaction['batch_id'] ?? ''));
                                            if (!empty($rawBatchId)): 
                                        ?>
                                            <span class="badge bg-light text-dark border font-monospace py-1 px-2" 
                                                  style="font-size: 11px; cursor: pointer; letter-spacing: 0.3px;" 
                                                  title="Batch ID: <?= htmlspecialchars($rawBatchId) ?> (Click to copy)" 
                                                  onclick="navigator.clipboard.writeText('<?= htmlspecialchars($rawBatchId, ENT_QUOTES) ?>'); Swal.fire({toast:true, position:'top-end', icon:'success', title:'Batch ID copied!', showConfirmButton:false, timer:1500});">
                                                <i class="fa-solid fa-layer-group text-primary me-1"></i><?= htmlspecialchars($rawBatchId) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border py-1 px-2" style="font-size: 11px; font-weight: normal;">
                                                Single / No Batch
                                            </span>
                                        <?php endif; ?>

                                        <?php 
                                            $hasNote = !empty($transaction['force_verify_note']) || !empty($transaction['handover_rejection_reason']) || !empty($transaction['rejection_reason']);
                                            if ($hasNote): 
                                                $notesHtml = "";
                                                if (!empty($transaction['rejection_reason'])) {
                                                    $notesHtml .= "<strong>Accounts Rejection:</strong><br>" . nl2br(htmlspecialchars($transaction['rejection_reason'])) . "<br><br>";
                                                }
                                                if (!empty($transaction['handover_rejection_reason'])) {
                                                    $notesHtml .= "<strong>Management Rejection:</strong><br>" . nl2br(htmlspecialchars($transaction['handover_rejection_reason'])) . "<br><br>";
                                                }
                                                if (!empty($transaction['force_verify_note'])) {
                                                    $notesHtml .= "<strong>Force Verify Note:</strong><br>" . nl2br(htmlspecialchars($transaction['force_verify_note']));
                                                }
                                        ?>
                                            <button type="button" class="btn btn-sm btn-outline-info ms-1 py-0 px-1"
                                                onclick="showTransactionNote(`<?= htmlspecialchars(addslashes($notesHtml), ENT_QUOTES) ?>`)"
                                                title="View Notes" style="font-size: 11px; vertical-align: middle;">
                                                <i class="fa-light fa-note-sticky"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <!-- DOCUMENT COLUMN — visible regardless of verification_status -->
                                    <td style="white-space:nowrap;">
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
                                    </td>
                                     <td>
                                         <div><?php echo !empty($transaction['created_by_name']) ? htmlspecialchars($transaction['created_by_name']) : '<span class="text-muted">System</span>'; ?></div>
                                         <?php if (!empty($transaction['updated_by_name']) && stripos($transaction['updated_by_name'], 'Management') === false): ?>
                                             <div class="text-muted mt-1 border-top pt-1" style="font-size: 0.85em;" title="Last Updated By">
                                                 <i class="fa-light fa-pen-to-square me-1"></i><?= htmlspecialchars($transaction['updated_by_name']) ?>
                                                 <?php if (!empty($transaction['updated_at'])): ?>
                                                     <br><small>(<?= date('d M, y h:i A', strtotime($transaction['updated_at'])) ?>)</small>
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
                                        
                                        <div class="d-flex align-items-center justify-content-start gap-10">
                                            <?php if (!empty($transaction['id']) && strpos($transaction['id'], 'booking-') === false): ?>
                                                <?php $isVerified = ($transaction['verification_status'] ?? 'pending') === 'verified'; ?>
                                                <!-- INVOICE BUTTON -->
                                                <a href="<?= $GLOBALS['base_url'] ?>/payment-invoice?payment_id=<?= $transaction['id'] ?>"
                                                    class="table__icon invoice"
                                                    style="color: #fff; background-color: #6c5ffc;"
                                                    title="View Invoice"
                                                    target="_blank">
                                                    <i class="fa-light fa-file-invoice"></i>
                                                </a>
                                                <?php 
                                                    $isAccountsHead = function_exists('isAccountsHead') ? isAccountsHead() : (($_SESSION['user']['role_id'] ?? 0) == 30);
                                                    $isAccExec = function_exists('isAccountsExecutive') ? isAccountsExecutive() : ((int)($_SESSION['user']['role_id'] ?? 0) === 36);
                                                    $isManagementRole = in_array((int)($_SESSION['user']['role_id'] ?? 0), [1, 3, 33, 34, 35]) || strpos(strtolower($_SESSION['user']['role_name'] ?? ''), 'management') !== false || strpos(strtolower($_SESSION['user']['role_name'] ?? ''), 'admin') !== false;
                                                    $isEditableByManagement = $isManagementRole && in_array($transaction['payment_type'] ?? '', ['development_charge', 'land_share', 'shop_service_charge', 'emi', 'others']);
                                                    $canEdit = ((!$isVerified && $transaction['payment_type'] !== 'booking_money') || $isAccountsHead || $isEditableByManagement) && !$isAccExec;
                                                    $canDelete = !$isVerified && !$isAccExec; // Keep delete logic standard
                                                    $canTrash = in_array((int)($_SESSION['user']['role_id'] ?? 0), [1, 3, 6, 30]) && !$isAccExec;
                                                ?>
                                                <?php 
                                                    $isBookingOrReg = in_array($transaction['payment_type'], ['booking_money', 'registration']);
                                                ?>
                                                <!-- EDIT BUTTON -->
                                                <?php if (($transaction['payment_type'] ?? '') === 'booking_money'): ?>
                                                    <?php if ($canEditBookingInfo): ?>
                                                    <button type="button" class="table__icon edit" style="border:none; cursor:pointer;" data-bs-toggle="modal" data-bs-target="#editBookingInfoModal" title="Edit Booking Date, Sales Agent & Total EMI">
                                                        <i class="fa-sharp fa-light fa-pen"></i>
                                                    </button>
                                                    <?php else: ?>
                                                    <span class="table__icon disabled" title="Booking Date editable by Accounts only" style="background-color: #f3f6f9; color: #a5aeb7;">
                                                        <i class="fa-regular fa-lock"></i>
                                                    </span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?php if ($canEditBookingInfo && !empty($transaction['id'])): ?>
                                                    <?php 
                                                        $trxPaymentDateYmd = !empty($transaction['payment_date']) ? date('Y-m-d', strtotime($transaction['payment_date'])) : '';
                                                        $trxLabel = $transaction['installment_label'] ?? 'Payment';
                                                        $trxDetails = $transaction['payment_details'] ?? ($transaction['payment_type'] ?? 'Payment');
                                                    ?>
                                                    <button type="button" 
                                                            class="table__icon edit border-0 btn-edit-payment-date" 
                                                            style="color:#fff;background-color:#0284c7;cursor:pointer;"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editPaymentDateModal"
                                                            data-payment-id="<?= (int)($transaction['id'] ?? 0) ?>"
                                                            data-booking-id="<?= (int)$bookingId ?>"
                                                            data-payment-type="<?= htmlspecialchars($transaction['payment_type'] ?? 'emi', ENT_QUOTES) ?>"
                                                            data-payment-label="<?= htmlspecialchars($trxLabel, ENT_QUOTES) ?>"
                                                            data-payment-details="<?= htmlspecialchars($trxDetails, ENT_QUOTES) ?>"
                                                            data-amount="<?= htmlspecialchars(number_format((float)($transaction['amount'] ?? 0), 2), ENT_QUOTES) ?>"
                                                            data-payment-date="<?= $trxPaymentDateYmd ?>"
                                                            data-formatted-date="<?= htmlspecialchars($transaction['formatted_date'] ?? '', ENT_QUOTES) ?>"
                                                            onclick="openEditPaymentDateModal(this)"
                                                            title="Edit <?= htmlspecialchars($trxLabel, ENT_QUOTES) ?> Payment Date">
                                                        <i class="fa-solid fa-calendar-days" style="pointer-events: none;"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                    <?php if ($canEdit): ?>
                                                    <a href="<?= $GLOBALS['base_url'] ?>/edit-client-cash-in/<?= $transaction['id'] ?>"
                                                        class="table__icon edit"
                                                        title="Edit Full Transaction">
                                                        <i class="fa-sharp fa-light fa-pen"></i>
                                                    </a>
                                                    <?php elseif (!$canEditBookingInfo): ?>
                                                        <?php if ($isVerified): ?>
                                                        <span class="table__icon disabled" title="Verified — cannot edit" style="background-color: #d4edda; color: #155724;">
                                                            <i class="fa-regular fa-lock"></i>
                                                        </span>
                                                        <?php else: ?>
                                                        <span class="table__icon disabled" title="Payment Cannot Be Edited" style="background-color: #f3f6f9; color: #a5aeb7;">
                                                            <i class="fa-regular fa-lock"></i>
                                                        </span>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                <?php endif; ?>

                                                <!-- TRASH / DELETE BUTTON -->
                                                <?php if ($canTrash): ?>
                                                <a href="#" class="delete-btn-custom table__icon delete"
                                                    onclick="trashTransaction(<?= $transaction['id'] ?>, <?= $clientDetails['booking_id'] ?? 'null' ?>); return false;"
                                                    title="Move to Trash">
                                                    <i class="fa-regular fa-trash"></i>
                                                </a>
                                                <?php elseif ($canDelete): ?>
                                                <a href="#" class="delete-btn-custom table__icon delete"
                                                    onclick="deleteTransaction(<?= $transaction['id'] ?>); return false;"
                                                    title="Permanently Delete">
                                                    <i class="fa-regular fa-trash"></i>
                                                </a>
                                                <?php else: ?>
                                                    <span class="table__icon disabled" title="Verified — cannot delete" style="background-color: #d4edda; color: #155724;">
                                                        <i class="fa-regular fa-lock"></i>
                                                    </span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <?php if ($canEditBookingInfo): ?>
                                                <button type="button" class="table__icon edit" style="border:none; cursor:pointer;" data-bs-toggle="modal" data-bs-target="#editBookingInfoModal" title="Edit Booking Date & Sales Agent">
                                                    <i class="fa-sharp fa-light fa-pen"></i>
                                                </button>
                                                <?php else: ?>
                                                <span class="table__icon disabled" title="System Generated">
                                                    <i class="fa-regular fa-lock"></i>
                                                </span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- VIRTUAL EMI SCHEDULE (Yield to Maturity & CED Scheduling) -->
                <?php if (!empty($virtualEmiSchedule)): ?>
                <div class="mt-4 pt-4 border-top">
                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                        <h5 class="mb-0"><i class="fa-light fa-calendar-check me-2"></i>EMI Maturity Schedule</h5>
                        <div class="d-flex align-items-center gap-2">
                            <a href="<?= $GLOBALS['base_url'] ?>/client-emi-schedule-print/<?= htmlspecialchars($bookingId) ?>" target="_blank" class="btn btn-sm btn-outline-secondary shadow-sm" title="Print EMI Maturity Schedule">
                                <i class="fa-solid fa-print me-1"></i> Print Schedule
                            </a>
                            <span class="badge bg-light text-muted border px-2 py-1 small">
                                <i class="fa-solid fa-eye me-1"></i>Accounts View (Dates scheduled by CED)
                            </span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" style="font-size: 14px;">
                            <thead style="background-color: #f8f9fa;">
                                <tr>
                                    <th>EMI No</th>
                                    <th>Scheduled / Due Date</th>
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
                                        case 'paid_on_time':
                                            $maturityBadge = '<span class="badge bg-success"><i class="fa-solid fa-circle-check me-1"></i>Paid On Time</span>';
                                            break;
                                        case 'paid_late':
                                            $delayText = (!empty($emi['delay_days']) && $emi['delay_days'] > 0) ? ' (' . $emi['delay_days'] . 'd delay)' : '';
                                            $maturityBadge = '<span class="badge text-white" style="background:#ea580c;"><i class="fa-solid fa-clock-rotate-left me-1"></i>Paid Late' . $delayText . '</span>';
                                            break;
                                        case 'matured': 
                                            $maturityBadge = '<span class="badge bg-info text-dark" style="background-color: #0dcaf0; color: #000;"><i class="fa-solid fa-check me-1"></i>Matured</span>'; 
                                            break;
                                        case 'pending': 
                                            $maturityBadge = '<span class="badge bg-warning text-dark"><i class="fa-solid fa-hourglass-half me-1"></i>Pending (Current Month)</span>'; 
                                            break;
                                        case 'due': 
                                            $overdueText = (!empty($emi['overdue_days']) && $emi['overdue_days'] > 0) ? ' (' . $emi['overdue_days'] . 'd overdue)' : '';
                                            $maturityBadge = '<span class="badge bg-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i>Overdue' . $overdueText . '</span>'; 
                                            break;
                                        case 'unscheduled':
                                            $maturityBadge = '<span class="badge bg-light text-secondary border"><i class="fa-regular fa-clock me-1"></i>Unscheduled</span>';
                                            break;
                                        case 'future': 
                                            $maturityBadge = '<span class="badge bg-secondary">Future</span>'; 
                                            break;
                                        default:
                                            $maturityBadge = '<span class="badge bg-secondary">' . htmlspecialchars(ucfirst($emi['maturity_status'])) . '</span>';
                                            break;
                                    }
                                    
                                    $statusBadge = '';
                                    if ($emi['status'] === 'paid') $statusBadge = '<span class="badge bg-success">Paid</span>';
                                    elseif ($emi['status'] === 'partially_paid') $statusBadge = '<span class="badge bg-warning text-dark">Partially Paid</span>';
                                    else $statusBadge = '<span class="text-muted">Unpaid</span>';
                                ?>
                                <tr>
                                    <td>
                                        <strong>#<?= $emi['emi_no'] ?></strong> 
                                        <?php if (!empty($emi['is_virtual']) && !empty($emi['due_date'])): ?>
                                            <small class="text-muted">(Proj.)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($emi['due_date'])): ?>
                                            <div class="d-flex align-items-center gap-1 flex-wrap">
                                                <span class="fw-semibold"><?= date('d M, Y', strtotime($emi['due_date'])) ?></span>
                                                <?php if (!empty($emi['is_date_custom'])): ?>
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 10px;" title="Fixed by <?= htmlspecialchars($emi['fixed_by_name'] ?? 'CED') ?> on <?= !empty($emi['fixed_at']) ? date('d M, Y', strtotime($emi['fixed_at'])) : '' ?>">
                                                        <i class="fa-solid fa-user-check me-1"></i>Fixed by <?= htmlspecialchars($emi['fixed_by_name'] ?? 'CED') ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted border" style="font-size: 10px;">(Proj.)</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($emi['notes'])): ?>
                                                <div class="small text-muted fst-italic mt-1" style="font-size: 11px;">
                                                    <i class="fa-light fa-comment-dots me-1"></i><?= htmlspecialchars($emi['notes']) ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 11px;">
                                                <i class="fa-regular fa-calendar-xmark me-1"></i>Not Scheduled
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>TK. <?= number_format($emi['amount'], 2) ?></td>
                                    <td>
                                        TK. <?= number_format($emi['paid_amount'], 2) ?>
                                        <?php if (!empty($emi['last_payment_date'])): ?>
                                            <div class="small text-muted" style="font-size: 11px;">
                                                <i class="fa-light fa-calendar-check me-1"></i>Cleared: <?= date('d M, Y', strtotime($emi['last_payment_date'])) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
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
<!-- ===== REJECTION REASON MODAL ===== -->
<div class="modal fade" id="rejectReasonModal" tabindex="-1" aria-labelledby="rejectReasonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="border-bottom: 2px solid #fee2e2;">
                <h5 class="modal-title" id="rejectReasonModalLabel" style="color:#b91c1c;">
                    <i class="fa-light fa-times-circle me-2"></i>Reject Payment
                </h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">
                    <i class="fa-light fa-info-circle me-1"></i>
                    Please provide a reason for rejecting this payment. This will be recorded for audit purposes.
                </p>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Rejection Reason <span class="text-danger">*</span></label>
                    <textarea id="rejectReasonInput"
                        class="form-control"
                        rows="3"
                        placeholder="e.g. Cheque bounced, account number mismatch, insufficient proof of payment…"
                        style="resize: vertical;"></textarea>
                    <div id="rejectReasonError" class="text-danger small mt-1" style="display:none;">
                        <i class="fa-light fa-exclamation-circle me-1"></i>Rejection reason is required.
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f1f5f9;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa-light fa-arrow-left me-1"></i> Cancel
                </button>
                <button type="button" id="confirmRejectBtn" class="btn"
                    style="background:#dc2626; color:#fff; border:none;">
                    <i class="fa-light fa-times-circle me-1"></i> Confirm Rejection
                </button>
            </div>
        </div>
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
                    <div class="col-md-5 border-end">
                        <h6 class="mb-3">Add New Note</h6>
                        <input type="hidden" id="followupBookingId">
                        <input type="hidden" id="followupEmiNo">
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
                    <div class="col-md-7">
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

<!-- ═══════════════════════════════════════════════════════
     Document Preview Modal  (Accounts view)
     ═══════════════════════════════════════════════════════ -->
<div class="modal fade" id="accDocumentModal" tabindex="-1" aria-labelledby="accDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="accDocumentModalLabel">
                    <i class="fa-light fa-file-image me-2"></i>Transaction Documents
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="accDocumentModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
            <div class="modal-footer">
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


<!-- Edit Shop Service Charge Modal -->
<?php if ($isShop && !empty($clientDetails['flat_id'])): ?>
<div class="modal fade" id="editShopServiceChargeModal" tabindex="-1" aria-labelledby="editShopServiceChargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="editShopServiceChargeModalLabel">
                    <i class="fa-solid fa-pen-to-square text-warning me-2"></i>Edit Shop Service Charge
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= $GLOBALS['base_url'] ?>/update-shop-service-charge" method="POST" id="editShopServiceChargeForm">
                <input type="hidden" name="booking_id" value="<?= $clientDetails['booking_id'] ?? '' ?>">
                <input type="hidden" name="flat_id" value="<?= $clientDetails['flat_id'] ?? '' ?>">
                <div class="modal-body p-4">
                    <div class="mb-3 p-3 rounded-3" style="background: #eef6ff; border: 1px solid #bfdbfe; color: #1e40af; font-size: 13px; line-height: 1.6;">
                        <div class="d-flex align-items-start gap-2">
                            <i class="fa-solid fa-circle-info text-primary mt-1" style="font-size: 15px;"></i>
                            <div>
                                Updating <strong>Shop Service Charge</strong> will update the charge in <code style="background: #dbeafe; color: #1e40af; padding: 2px 5px; border-radius: 4px; font-weight: 600;">flats</code> table for unit <strong><?= htmlspecialchars($clientDetails['unit_name'] ?? '') ?></strong>.<br>
                                Total Price: <strong>TK.<?= number_format($paymentSummary['total_price'] ?? 0, 2) ?></strong><br>
                                The remaining construction fee and EMI amounts will recalculate automatically based on the new charge amount.
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="shop_service_charge_input" class="form-label fw-bold">
                            <i class="fa-light fa-coins text-warning me-1"></i> Shop Service Charge (BDT) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">TK.</span>
                            <input type="number" 
                                   step="0.01" 
                                   min="0" 
                                   name="shop_service_charge" 
                                   id="shop_service_charge_input" 
                                   class="form-control" 
                                   value="<?= (float)($clientDetails['shop_service_charge'] ?? 0) ?>" 
                                   required>
                        </div>
                        <small class="text-muted d-block mt-1">Enter the total fixed shop service charge amount in BDT.</small>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveShopServiceCharge">
                        <i class="fa-solid fa-check me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
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
            <form action="<?= $GLOBALS['base_url'] ?>/update-booking-info" method="POST" id="editBookingInfoForm">
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
                        <label for="booking_date_input" class="form-label fw-bold">
                            <i class="fa-light fa-calendar-days text-primary me-1"></i> Booking Date <span class="text-danger">*</span>
                        </label>
                        <input type="date" 
                               name="booking_date" 
                               id="booking_date_input" 
                               class="form-control" 
                               value="<?= !empty($clientDetails['booking_date']) ? date('Y-m-d', strtotime($clientDetails['booking_date'])) : '' ?>" 
                               required>
                        <small class="text-muted d-block mt-1">Updates booking date and payment date for booking money.</small>
                    </div>

                    <div class="mb-3">
                        <label for="sales_by_id_select" class="form-label fw-bold">
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
                        <select name="sales_by_id" id="sales_by_id_select" class="form-select select2" data-placeholder="Select Sales Agent" required style="width: 100%;">
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
                        <label for="total_emi_no_input" class="form-label fw-bold d-flex justify-content-between align-items-center">
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
                                   id="total_emi_no_input" 
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
                    <button type="submit" class="btn btn-warning text-dark fw-bold px-4" id="btnSaveBookingInfo">
                        <i class="fa-solid fa-save me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Payment Date -->
<div class="modal fade" id="editPaymentDateModal" tabindex="-1" aria-labelledby="editPaymentDateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="editPaymentDateModalLabel">
                    <i class="fa-solid fa-pen-to-square text-warning me-2"></i><span id="epd_modal_title_text">Edit Payment Date</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= $GLOBALS['base_url'] ?>/update-payment-date" method="POST" id="editPaymentDateForm">
                <input type="hidden" name="payment_id" id="epd_payment_id" value="">
                <input type="hidden" name="booking_id" id="epd_booking_id" value="<?= htmlspecialchars($bookingId) ?>">
                <input type="hidden" name="payment_type" id="epd_payment_type" value="emi">
                <input type="hidden" name="redirect_to" value="clients-cash-in-history/<?= htmlspecialchars($bookingId) ?>">

                <div class="modal-body p-4">
                    <div class="mb-3 p-3 rounded-3" style="background: #eef6ff; border: 1px solid #bfdbfe; color: #1e40af; font-size: 13px; line-height: 1.6;">
                        <div class="d-flex align-items-start gap-2">
                            <i class="fa-solid fa-circle-info text-primary mt-1" style="font-size: 15px;"></i>
                            <div>
                                Updating <strong id="epd_type_name">Payment Date</strong> will only update this specific payment record (<span class="fw-bold text-dark" id="epd_details">EMI Payment</span> - <span class="fw-bold text-success" id="epd_amount">TK. 0.00</span>).<br>
                                Verification status and other payment records will remain unchanged.
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="epd_payment_date" class="form-label fw-bold">
                            <i class="fa-light fa-calendar-days text-primary me-1"></i> <span id="epd_input_label">Payment Date</span> <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="payment_date" id="epd_payment_date" class="form-control" required>
                        <small class="text-muted d-block mt-1">Updates payment date only for this specific transaction.</small>
                    </div>
                </div>

                <div class="modal-footer bg-light px-4 py-3 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold px-4" id="btnSavePaymentDate">
                        <i class="fa-solid fa-save me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Transfer Note Modal -->
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
                    <input type="hidden" name="source_page" value="accounts">
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

<!-- Handover Modal -->
<div class="modal fade" id="handoverModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="handoverForm" onsubmit="submitHandover(event)">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Initiate Handover</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="handoverPaymentId" name="payment_id">
                    <div class="mb-3">
                        <label class="form-label">Select Management User <span class="text-danger">*</span></label>
                        <select name="handover_to" id="handoverSelect" class="form-select" required>
                            <option value="">-- Select Employee --</option>
                            <?php 
                                // Need to fetch management users or load via AJAX. We'll load them here if available or rely on the view data.
                                // Actually, we might need to fetch them. Since we don't have them in this view by default, let's assume we can get them.
                                $mgmtUsersQuery = $this->db->query("SELECT id, full_name, email FROM users WHERE role_id IN (1, 3)");
                                while ($mgmtUser = $mgmtUsersQuery->fetch_assoc()):
                            ?>
                                <option value="<?= $mgmtUser['id'] ?>">
                                    <?= htmlspecialchars($mgmtUser['full_name']) ?> <?= !empty($mgmtUser['email']) ? '('.htmlspecialchars($mgmtUser['email']).')' : '' ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Confirm Handover</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Force Verify Modal -->
<div class="modal fade" id="forceVerifyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-dark"><i class="fa-light fa-triangle-exclamation"></i> Force Verify Rejected Handover</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>This transaction's handover was <strong>rejected</strong> by management.</p>
                <p>Are you sure you want to bypass this and verify the payment as paid?</p>
                <div class="mb-3">
                    <label class="form-label">Note for Management (Required) <span class="text-danger">*</span></label>
                    <textarea id="forceVerifyNote" class="form-control" rows="3" required placeholder="Explain why you are force-verifying this payment..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="submitForceVerify()">Force Verify</button>
            </div>
        </div>
    </div>
</div>

<!-- EMI Tracking Modal Component -->
<?php include __DIR__ . '/emi-tracking-modal.php'; ?>

    <script>
        const BASE_URL = '<?= $GLOBALS['base_url'] ?>';

        // ─── Show Rejection Reason Modal ───────────────────────────────────
        function showRejectionReason(reason) {
            document.getElementById('rejectionReasonText').textContent = reason;
            new bootstrap.Modal(document.getElementById('viewRejectionReasonModal')).show();
        }
        // ─── Document Modal ───────────────────────────────────────────────────
        function openDocumentModal(paymentId) {
            const modal = new bootstrap.Modal(document.getElementById('accDocumentModal'));
            const body  = document.getElementById('accDocumentModalBody');

            body.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Loading documents…</p>
                </div>`;
            modal.show();

            fetch(BASE_URL + '/get-transaction-attachments?payment_id=' + paymentId)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        body.innerHTML = `<div class="alert alert-warning">${data.message}</div>`;
                        return;
                    }
                    const vsBadges = {
                        'verified': '<span class="badge bg-success"><i class="fa-light fa-check-circle me-1"></i>Verified</span>',
                        'rejected': '<span class="badge bg-danger"><i class="fa-light fa-times-circle me-1"></i>Rejected</span>',
                        'pending':  '<span class="badge bg-warning text-dark"><i class="fa-light fa-clock me-1"></i>Pending</span>',
                    };
                    const vsBadge = vsBadges[data.verification_status] || vsBadges['pending'];

                    let html = `
                        <div class="d-flex flex-wrap gap-3 mb-3 align-items-center">
                            <div><strong>Payment ID:</strong> #${data.payment_id}</div>
                            <div><strong>Type:</strong> ${(data.payment_type||'').replace(/_/g,' ').toUpperCase()}</div>
                            <div><strong>Amount:</strong> TK.${parseFloat(data.amount||0).toLocaleString('en-BD',{minimumFractionDigits:2})}</div>
                            <div><strong>Date:</strong> ${data.payment_date||'-'}</div>
                            <div><strong>Status:</strong> ${vsBadge}</div>
                            ${data.verified_by_name ? '<div><strong>Verified By:</strong> '+data.verified_by_name+' <span class="text-muted ms-1" style="font-size:0.9em;">(' + (data.verified_at ? new Date(data.verified_at).toLocaleString('en-GB', {day:'2-digit', month:'short', year:'2-digit', hour:'2-digit', minute:'2-digit', hour12:true}) : '') + ')</span></div>' : ''}
                            ${data.updated_by_name ? '<div><strong>Last Updated By:</strong> '+data.updated_by_name+' <span class="text-muted ms-1" style="font-size:0.9em;">(' + (data.updated_at ? new Date(data.updated_at).toLocaleString('en-GB', {day:'2-digit', month:'short', year:'2-digit', hour:'2-digit', minute:'2-digit', hour12:true}) : '') + ')</span></div>' : ''}
                            ${data.rejection_reason ? '<div class="w-100"><div class="alert alert-danger py-2 mb-0"><strong>Rejection Reason:</strong> '+data.rejection_reason+'</div></div>' : ''}
                        </div><hr>`;

                    if (!data.files || data.files.length === 0) {
                        html += `<div class="alert alert-info"><i class="fa-light fa-info-circle me-1"></i>No documents attached to this transaction.</div>`;
                    } else {
                        html += `<div class="row">`;
                        data.files.forEach((filePath, idx) => {
                            const viewUrl     = BASE_URL + '/view-transaction-file?file=' + encodeURIComponent(filePath);
                            const downloadUrl = viewUrl + '&download=1';
                            const ext         = filePath.split('.').pop().toLowerCase();
                            const isImage     = ['jpg','jpeg','jfif','png','gif','webp'].includes(ext);
                            const isPDF       = ext === 'pdf';
                            const fileName    = filePath.split('/').pop();
                            if (isImage) {
                                html += `
                                <div class="col-md-4 col-sm-6">
                                    <div class="card h-100">
                                        <a href="${viewUrl}" target="_blank">
                                            <img src="${viewUrl}" class="card-img-top"
                                                 style="height:200px;object-fit:contain;background:#f8f9fa;padding:8px;"
                                                 alt="Document ${idx+1}"
                                                 onerror="this.style.display='none'; this.insertAdjacentHTML('afterend', '<div class=\'d-flex flex-column align-items-center justify-content-center bg-light text-muted\' style=\'height:200px;\'><i class=\'fa-light fa-image-slash mb-2\' style=\'font-size:2.5rem;\'></i><small>File not found on server</small></div>');">
                                        </a>
                                        <div class="card-body p-2 d-flex justify-content-between align-items-center">
                                            <small class="text-muted text-truncate" style="max-width:70%" title="${fileName}">${fileName}</small>
                                            <a href="${downloadUrl}" class="btn btn-sm btn-outline-secondary" title="Download">
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
                                            <a href="${viewUrl}" target="_blank" class="btn btn-sm btn-outline-danger">
                                                <i class="fa-light fa-eye me-1"></i>View PDF
                                            </a>
                                            <a href="${downloadUrl}" class="btn btn-sm btn-outline-secondary" title="Download PDF">
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
                                        <a href="${downloadUrl}" class="btn btn-sm btn-outline-secondary">
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
                    body.innerHTML = `<div class="alert alert-danger">Failed to load documents. Please try again.</div>`;
                });
        }

        // ─── Delete ──────────────────────────────────────
        // ─── Delete (SweetAlert confirm) ──────────────────────────────────────────
        function deleteTransaction(transactionId) {
            const currentBookingId = <?= $clientDetails['booking_id'] ?? 'null' ?>;
            Swal.fire({
                title: 'Delete Transaction?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fa-light fa-trash me-1"></i> Yes, Delete',
                cancelButtonText: 'Cancel',
            }).then(result => {
                if (result.isConfirmed) {
                    if (currentBookingId) {
                        window.location.href = BASE_URL + '/delete-transaction?payment_id=' + transactionId + '&booking_id=' + currentBookingId;
                    } else {
                        Swal.fire('Error', 'Missing booking information.', 'error')
                            .then(() => location.reload());
                    }
                } else {
                    // User dismissed (Cancel / Esc) — reload to stay in sync
                    location.reload();
                }
            });
        }

        // ─── Rejection modal ─────────────────────────────
        let _rejectPaymentId = null;
        function openRejectModal(paymentId) {
            _rejectPaymentId = paymentId;
            document.getElementById('rejectReasonInput').value = '';
            document.getElementById('rejectReasonError').style.display = 'none';
            new bootstrap.Modal(document.getElementById('rejectReasonModal')).show();
        }

        document.addEventListener('DOMContentLoaded', function () {
            const confirmRejectBtn = document.getElementById('confirmRejectBtn');
            if (confirmRejectBtn) {
                confirmRejectBtn.addEventListener('click', function () {
                    const reason = document.getElementById('rejectReasonInput').value.trim();
                    if (!reason) {
                        document.getElementById('rejectReasonError').style.display = 'block';
                        return;
                    }
                    verifyPayment(_rejectPaymentId, 'reject', reason);
                    bootstrap.Modal.getInstance(document.getElementById('rejectReasonModal')).hide();
                });
            }
        });

        // ─── Handover Logic ─────────────────────────────
        function openHandoverModal(paymentId) {
            document.getElementById('handoverPaymentId').value = paymentId;
            document.getElementById('handoverSelect').value = '';
            new bootstrap.Modal(document.getElementById('handoverModal')).show();
        }

        function submitHandover(e) {
            e.preventDefault();
            const paymentId = document.getElementById('handoverPaymentId').value;
            const handoverTo = document.getElementById('handoverSelect').value;

            if (!handoverTo) return;

            fetch(BASE_URL + '/assign-handover', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `payment_id=${paymentId}&handover_to=${handoverTo}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Handover requested successfully',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message || 'Failed to initiate handover', 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error', 'Network error', 'error');
            });
        }

        // ─── Force Verify Logic ─────────────────────────────
        let _forceVerifyPaymentId = null;
        function openForceVerifyModal(paymentId) {
            _forceVerifyPaymentId = paymentId;
            document.getElementById('forceVerifyNote').value = '';
            new bootstrap.Modal(document.getElementById('forceVerifyModal')).show();
        }

        function submitForceVerify() {
            const note = document.getElementById('forceVerifyNote').value.trim();
            const sendSms = document.getElementById('globalSendSmsToggle').checked ? 1 : 0;
            if (!note) {
                Swal.fire('Error', 'A note is required for force verification', 'error');
                return;
            }
            bootstrap.Modal.getInstance(document.getElementById('forceVerifyModal')).hide();
            _doVerifyRequest(_forceVerifyPaymentId, 'verify', '', note, sendSms);
        }

        // ─── Shared Note Modal ─────────────────────────────
        function showTransactionNote(noteHtml) {
            Swal.fire({
                title: 'Transaction Notes',
                html: '<div style="text-align: left; font-size: 14px;">' + noteHtml + '</div>',
                icon: 'info',
                confirmButtonText: 'Close'
            });
        }

        // ─── Core verify/reject AJAX (with SweetAlert2) ────────────────────
        function verifyPayment(paymentId, action, rejectionReason = '') {
            // ── Pre-confirm for Verify action only
            if (action === 'verify') {
                Swal.fire({
                    title: 'Verify Payment?',
                    text: 'This will mark the payment as verified. This action cannot be undone.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#16a34a',
                    cancelButtonColor:  '#6c757d',
                    confirmButtonText:  '<i class="fa-light fa-check me-1"></i> Yes, Verify',
                    cancelButtonText:   'Cancel',
                }).then(result => {
                    if (result.isConfirmed) {
                        let sendSms = document.getElementById('globalSendSmsToggle').checked ? 1 : 0;
                        _doVerifyRequest(paymentId, action, rejectionReason, '', sendSms);
                    } else {
                        // User dismissed (Cancel / Esc) — reload to stay in sync
                        location.reload();
                    }
                });
            } else {
                // Reject flows through the rejection-reason modal first, then calls here directly
                _doVerifyRequest(paymentId, action, rejectionReason);
            }
        }

        function _doVerifyRequest(paymentId, action, rejectionReason = '', forceVerifyNote = '', sendSms = 0) {
            const cell = document.getElementById('ver-cell-' + paymentId);
            if (cell) cell.innerHTML = '<span class="text-muted small"><i class="fa-light fa-spinner fa-spin"></i> Processing…</span>';

            const body = new URLSearchParams({ payment_id: paymentId, action, rejection_reason: rejectionReason, force_verify_note: forceVerifyNote, send_sms: sendSms });

            fetch(BASE_URL + '/verify-payment', { method: 'POST', body })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        // Update verification badge live
                        if (cell) {
                            if (res.status === 'verified') {
                                cell.innerHTML = '<span class="badge bg-success ver-badge"><i class="fa-light fa-check-circle me-1"></i>Verified</span>';
                            } else {
                                cell.innerHTML = '<span class="badge bg-danger ver-badge"><i class="fa-light fa-times-circle me-1"></i>Rejected</span>';
                            }
                        }
                        // Update "Verified By" cell
                        const vbCell = document.getElementById('verified-by-' + paymentId);
                        if (vbCell && res.verified_by_name) {
                            let timeHtml = res.verified_at ? `<div class="text-muted mt-1" style="font-size: 0.85em;"><i class="fa-light fa-clock me-1"></i>${new Date(res.verified_at).toLocaleString('en-GB', {day:'2-digit', month:'short', year:'2-digit', hour:'2-digit', minute:'2-digit', hour12:true})}</div>` : '';
                            vbCell.innerHTML = `<div>${res.verified_by_name}</div>${timeHtml}`;
                        }
                        // Update status badge to Paid when verified
                        if (res.status === 'verified') {
                            const row = cell ? cell.closest('tr') : null;
                            if (row) {
                                const statusCell = row.querySelector('td:nth-child(10)');
                                if (statusCell) {
                                    statusCell.innerHTML = '<span class="badge bg-success">Paid</span>';
                                }
                            }
                        }
                        // SweetAlert2 success popup — reload after it closes
                        Swal.fire({
                            icon: res.status === 'verified' ? 'success' : 'info',
                            title: res.status === 'verified' ? 'Payment Verified!' : 'Payment Rejected',
                            text:  res.message,
                            timer: 2500,
                            timerProgressBar: true,
                            showConfirmButton: false,
                        }).then(() => location.reload());
                    } else {
                        if (cell) cell.innerHTML = '<span class="badge bg-warning text-dark"><i class="fa-light fa-clock me-1"></i>Pending</span>';
                        Swal.fire({
                            icon: 'error',
                            title: 'Action Failed',
                            text:  res.message || 'Something went wrong. Please try again.',
                        }).then(() => location.reload());
                    }
                })
                .catch(() => {
                    if (cell) cell.innerHTML = '<span class="badge bg-warning text-dark"><i class="fa-light fa-clock me-1"></i>Pending</span>';
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text:  'Could not reach the server. Please check your connection and try again.',
                    }).then(() => location.reload());
                });
        }

        // ─── SweetAlert2 helper (replaces old toast) ───────────────────────
        function showVerificationToast(msg, type) {
            toast.style.cssText = 'top:20px;right:20px;z-index:9999;min-width:280px;';
            toast.innerHTML = '<i class="fa-light fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + ' me-2"></i>' + msg;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
    </script>

    <style>
        .card__icon span {
            color: var(--clr-theme-primary);
            background: rgba(108, 95, 252, 0.1);
            width: 40px; height: 40px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; font-size: 16px;
        }
        .card__title { font-size: 1.25rem; }

        /* Verify / Reject inline buttons */
        .btn-verify {
            background: #dcfce7; color: #15803d;
            border: 1px solid #86efac;
            font-size: 11px; padding: 3px 8px; border-radius: 6px;
            font-weight: 600; white-space: nowrap;
            transition: background .15s;
        }
        .btn-verify:hover { background: #16a34a; color: #fff; }

        .btn-reject {
            background: #fee2e2; color: #b91c1c;
            border: 1px solid #fca5a5;
            font-size: 11px; padding: 3px 8px; border-radius: 6px;
            font-weight: 600; white-space: nowrap;
            transition: background .15s;
        }
        .btn-reject:hover { background: #dc2626; color: #fff; }

        .ver-toast { animation: slideInRight .3s ease; }
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(40px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        @media (min-width: 975px) {
            .dataTables_wrapper .dataTables_filter input[type=search] {
                border: 1px solid var(--clr-border-1); color: var(--clr-text-body);
                padding: 0 10px; margin-inline-start: 10px;
                height: 36px; border-radius: 6px; width: 380px;
            }
        }
        @media (max-width: 975px) {
            .card__wrapper .card__body h3 { font-size: 1.3rem; }
            .card__title { font-size: 1rem; }
        }
        @media (max-width: 768px) { .btn { font-size: 14px; padding: 6px 24px; } }
        @media (min-width: 575px) { .position { justify-content: end !important; } }
        @media (max-width: 480px) {
            .btn { font-size: 16px; padding: 6px 21px; }
            .position { justify-content: start !important; }
        }
        @media (max-width: 400px) { .btn { font-size: 14px; padding: 6px 14px; } }

        /* Filter button active states */
        .payment-filter.active { font-weight: 600; }
        .payment-filter[data-filter="all"].active { background:#6c757d; color:#fff; border-color:#6c757d; }
        .payment-filter[data-filter="booking_money"].active { background:#0d6efd; color:#fff; border-color:#0d6efd; }
        .payment-filter[data-filter="registration"].active { background:#0dcaf0; color:#fff; border-color:#0dcaf0; }
        .payment-filter[data-filter="land_share"].active { background:#198754; color:#fff; border-color:#198754; }
        .payment-filter[data-filter="development_charge"].active { background:#ffc107; color:#000; border-color:#ffc107; }
        .payment-filter[data-filter="emi"].active { background:#dc3545; color:#fff; border-color:#dc3545; }
        .payment-filter[data-filter="emi_due_settlement"].active { background:#212529; color:#fff; border-color:#212529; }
        .payment-filter[data-filter="others"].active { background:#0dcaf0; color:#000; border-color:#0dcaf0; }
        
        /* Fix SweetAlert z-index behind bootstrap modals */
        .swal2-container { z-index: 99999 !important; }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var filters = document.querySelectorAll('.payment-filter');
        var rows = document.querySelectorAll('#transactionTable tbody tr[data-payment-type]');
        var transactionSearchInput = document.getElementById('transactionSearchInput');

        function updateSelectAllState() {
            var selectAllCheckbox = document.getElementById('selectAllTransactions');
            var rowCheckboxes = document.querySelectorAll('.row-checkbox');
            if (selectAllCheckbox) {
                var visibleCheckboxes = Array.from(rowCheckboxes).filter(function(c) { return c.closest('tr').style.display !== 'none'; });
                var allVisibleChecked = visibleCheckboxes.length > 0 && visibleCheckboxes.every(function(c) { return c.checked; });
                selectAllCheckbox.checked = allVisibleChecked;
            }
        }

        function applyFilters() {
            var activeBtn = document.querySelector('.payment-filter.active');
            var filter = activeBtn ? activeBtn.getAttribute('data-filter') : 'all';
            var searchTerm = transactionSearchInput ? transactionSearchInput.value.toLowerCase().trim() : '';

            rows.forEach(function(row) {
                var typeMatch = (filter === 'all' || row.getAttribute('data-payment-type') === filter);
                var trxId = row.getAttribute('data-trx-id') || '';
                var batchId = row.getAttribute('data-batch-id') || '';
                var searchMatch = (searchTerm === '' || trxId.toLowerCase().includes(searchTerm) || batchId.toLowerCase().includes(searchTerm));

                if (typeMatch && searchMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            updateSelectAllState();
        }

        filters.forEach(function(btn) {
            btn.addEventListener('click', function() {
                filters.forEach(function(b) { b.classList.remove('active'); });
                btn.classList.add('active');
                applyFilters();
            });
        });
        
        if (transactionSearchInput) {
            transactionSearchInput.addEventListener('input', applyFilters);
        }
        
        // --- BULK ACTION LOGIC ---
        const selectAllCheckbox = document.getElementById('selectAllTransactions');
        const rowCheckboxes = document.querySelectorAll('.row-checkbox');
        const btnBulkVerify = document.getElementById('btnBulkVerify');
        const btnBulkReceipt = document.getElementById('btnBulkReceipt');
        const bulkSelectedCount = document.getElementById('bulkSelectedCount');
        
        function updateBulkActionState() {
            let checkedCount = 0;
            let allPending = true;
            
            rowCheckboxes.forEach(cb => {
                if (cb.checked) {
                    checkedCount++;
                    let tr = cb.closest('tr');
                    if (tr.getAttribute('data-verification-status') !== 'pending') {
                        allPending = false;
                    }
                }
            });
            
            bulkSelectedCount.textContent = checkedCount;
            const bulkDeleteCount = document.getElementById('bulkDeleteCount');
            if (bulkDeleteCount) {
                bulkDeleteCount.textContent = checkedCount;
            }
            const btnBulkDelete = document.getElementById('btnBulkDelete');
            
            if (checkedCount > 0) {
                btnBulkVerify.disabled = !allPending;
                btnBulkReceipt.disabled = false;
                if (btnBulkDelete) btnBulkDelete.disabled = false;
            } else {
                btnBulkVerify.disabled = true;
                btnBulkReceipt.disabled = true;
                if (btnBulkDelete) btnBulkDelete.disabled = true;
            }
        }
        
        // Handle "Select All" click
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                const isChecked = this.checked;
                rowCheckboxes.forEach(cb => {
                    // Only check visible rows
                    if (cb.closest('tr').style.display !== 'none') {
                        cb.checked = isChecked;
                    }
                });
                updateBulkActionState();
            });
        }
        
        // Handle individual row checkbox click + Smart Batch Selection
        rowCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                const tr = this.closest('tr');
                const batchId = tr.getAttribute('data-batch-id');
                const isChecked = this.checked;
                
                // If it has a batch_id, auto-select other rows with the same batch_id
                if (batchId && batchId !== '') {
                    rowCheckboxes.forEach(otherCb => {
                        const otherTr = otherCb.closest('tr');
                        if (otherTr.getAttribute('data-batch-id') === batchId) {
                            otherCb.checked = isChecked;
                        }
                    });
                }
                
                // Update "Select All" state
                updateSelectAllState();
                
                updateBulkActionState();
            });
        });
        
        // Auto-select latest pending batch
        const autoSelectLatestPending = document.getElementById('autoSelectLatestPending');
        if (autoSelectLatestPending) {
            autoSelectLatestPending.addEventListener('change', function() {
                if (this.checked) {
                    let latestPendingBatchId = null;
                    let latestPendingTrxId = null;
                    
                    // Find the first (latest) row that is pending
                    for (let i = 0; i < rowCheckboxes.length; i++) {
                        let tr = rowCheckboxes[i].closest('tr');
                        if (tr.getAttribute('data-verification-status') === 'pending') {
                            latestPendingBatchId = tr.getAttribute('data-batch-id');
                            latestPendingTrxId = tr.getAttribute('data-trx-id');
                            break; // Rows are ordered DESC, so the first one is the latest
                        }
                    }
                    
                    if (latestPendingBatchId || latestPendingTrxId) {
                        rowCheckboxes.forEach(cb => {
                            let tr = cb.closest('tr');
                            let bId = tr.getAttribute('data-batch-id');
                            let tId = tr.getAttribute('data-trx-id');
                            let isPending = tr.getAttribute('data-verification-status') === 'pending';
                            
                            if (latestPendingBatchId && latestPendingBatchId !== '') {
                                if (bId === latestPendingBatchId) cb.checked = true;
                                else cb.checked = false;
                            } else if (latestPendingTrxId && latestPendingTrxId !== '') {
                                if (tId === latestPendingTrxId && isPending) cb.checked = true;
                                else cb.checked = false;
                            } else {
                                cb.checked = false;
                            }
                        });
                    } else {
                        this.checked = false;
                        Swal.fire({
                            icon: 'info',
                            title: 'No Pending Transactions',
                            text: 'There are no pending transactions to select.',
                            confirmButtonColor: '#6c5ffc'
                        });
                    }
                } else {
                    rowCheckboxes.forEach(cb => cb.checked = false);
                }
                updateSelectAllState();
                updateBulkActionState();
            });
        }
        
        // Bulk Verify Button Click
        if (btnBulkVerify) {
            btnBulkVerify.addEventListener('click', function() {
                const selectedIds = Array.from(rowCheckboxes)
                                         .filter(cb => cb.checked)
                                         .map(cb => cb.value);
                
                if (selectedIds.length === 0) return;
                
                // Calculate total amount for the modal
                let totalAmount = 0;
                rowCheckboxes.forEach(cb => {
                    if(cb.checked) {
                        let tr = cb.closest('tr');
                        // Find amount cell. Note: Need to be careful with formatting commas.
                        // Assuming Amount is in a specific cell or we fetch from DB, but we can do a pre-flight ajax call
                        // or just show the count. Let's do a confirmation with count.
                    }
                });
                
                Swal.fire({
                    title: 'Verify ' + selectedIds.length + ' Transactions?',
                    text: 'Are you sure you want to verify all selected transactions?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Verify All',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const sendSms = document.getElementById('globalSendSmsToggle') && document.getElementById('globalSendSmsToggle').checked ? 1 : 0;
                        
                        $.ajax({
                            url: '<?= $GLOBALS['base_url'] ?>/account-client/bulkVerify',
                            type: 'POST',
                            data: { payment_ids: selectedIds, send_sms: sendSms },
                            dataType: 'json',
                            success: function(res) {
                                if(res.success) {
                                    Swal.fire('Verified!', res.message, 'success').then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire('Error', res.message, 'error');
                                }
                            },
                            error: function() {
                                Swal.fire('Error', 'Failed to connect to server.', 'error');
                            }
                        });
                    }
                });
            });
        }
        
        // Bulk Receipt Button Click
        if (btnBulkReceipt) {
            btnBulkReceipt.addEventListener('click', function() {
                const selectedIds = Array.from(rowCheckboxes)
                                         .filter(cb => cb.checked)
                                         .map(cb => cb.value);
                
                if (selectedIds.length === 0) return;
                
                // Open receipt in new tab
                const idsParam = selectedIds.join(',');
                window.open('<?= $GLOBALS['base_url'] ?>/payment-invoice?payment_id=' + idsParam, '_blank');
            });
        }
        
        // Bulk Delete Button Click
        const btnBulkDelete = document.getElementById('btnBulkDelete');
        if (btnBulkDelete) {
            btnBulkDelete.addEventListener('click', function() {
                const selectedIds = Array.from(rowCheckboxes)
                                         .filter(cb => cb.checked && !isNaN(cb.value) && parseInt(cb.value, 10) > 0)
                                         .map(cb => parseInt(cb.value, 10));

                if (selectedIds.length === 0) {
                    Swal.fire('No Selection', 'Please select at least one transaction to delete.', 'info');
                    return;
                }

                const currentBookingId = <?= (int)($bookingId ?? ($clientDetails['booking_id'] ?? 0)) ?>;
                if (!currentBookingId) {
                    Swal.fire('Error', 'Missing booking ID.', 'error');
                    return;
                }

                Swal.fire({
                    title: 'Move ' + selectedIds.length + ' Transactions to Trash?',
                    html: `Are you sure you want to move <b>${selectedIds.length}</b> selected transaction(s) to Trash?<br><br>
                           <span class="text-muted small">They will be removed from the ledger and safely stored in the <b>Trash Client Payment</b> list (recording who moved them and when).</span>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e65100',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fa-solid fa-trash me-1"></i> Yes, Move to Trash',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Moving to Trash...',
                            text: 'Please wait while transactions are being processed.',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        $.ajax({
                            url: '<?= $GLOBALS['base_url'] ?>/account-client/bulkDelete',
                            type: 'POST',
                            data: {
                                payment_ids: selectedIds,
                                booking_id: currentBookingId
                            },
                            dataType: 'json',
                            success: function(res) {
                                if (res.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Moved to Trash!',
                                        text: res.message,
                                        confirmButtonColor: '#006837'
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire('Failed', res.message || 'Could not move transactions to trash.', 'error');
                                }
                            },
                            error: function(xhr) {
                                let msg = 'Failed to connect to server.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    msg = xhr.responseJSON.message;
                                }
                                Swal.fire('Error', msg, 'error');
                            }
                        });
                    }
                });
            });
        }
        
    });

    function openEmiFollowupModal(bookingId, emiNo) {
        $('#followupBookingId').val(bookingId);
        $('#followupEmiNo').val(emiNo);
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
            data: { booking_id: bookingId, emi_no: emiNo },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var html = '';
                    if (response.data.length === 0) {
                        html = '<div class="text-center text-muted py-4">No follow up notes yet.</div>';
                    } else {
                        response.data.forEach(function(note) {
                            html += '<div class="card mb-2 shadow-sm border-0 bg-light">';
                            html += '<div class="card-body p-3">';
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


    function trashTransaction(paymentId, bookingId) {
        Swal.fire({
            title: 'Are you sure to move in trash?',
            text: "You can revert this later because it is going to the trash.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff9800',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, move to trash!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `<?= $GLOBALS['base_url'] ?>/trash-client-payment?payment_id=${paymentId}&booking_id=${bookingId}`;
            }
        });
    }

    // Focus on EMI input when opened via edit-emi-btn
    $(document).on('click', '.edit-emi-btn', function () {
        $('#editBookingInfoModal').data('focus-target', 'emi');
    });

    // Initialize Select2 in editBookingInfoModal
    $('#editBookingInfoModal').on('shown.bs.modal', function () {
        $('#sales_by_id_select').select2({
            dropdownParent: $('#editBookingInfoModal'),
            width: '100%'
        });

        if ($(this).data('focus-target') === 'emi') {
            var emiInput = $('#total_emi_no_input');
            if (emiInput.length) {
                emiInput.focus().select();
            }
            $(this).removeData('focus-target');
        }
    });

    // Handle AJAX submit for editBookingInfoForm
    $('#editBookingInfoForm').on('submit', function (e) {
        e.preventDefault();
        var form = $(this);
        var btn = $('#btnSaveBookingInfo');
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

    // Handle AJAX submit for editShopServiceChargeForm
    $('#editShopServiceChargeForm').on('submit', function (e) {
        e.preventDefault();
        var form = $(this);
        var btn = $('#btnSaveShopServiceCharge');
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
                    $('#editShopServiceChargeModal').modal('hide');
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
                        text: res.message || 'Could not update shop service charge.'
                    });
                }
            },
            error: function () {
                btn.prop('disabled', false).html(originalBtnText);
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Unable to connect to the server. Please try again.'
                });
            }
        });
    });

    // Global function to populate Edit Payment Date Modal
    window.openEditPaymentDateModal = function(target) {
        if (!target) return;
        var btn = null;
        if (target.nodeType) {
            btn = target.closest ? target.closest('.btn-edit-payment-date, [data-payment-id]') : target;
        } else if (typeof jQuery !== 'undefined' && target instanceof jQuery) {
            btn = target[0];
        } else if (target.target) {
            btn = target.target.closest ? target.target.closest('.btn-edit-payment-date, [data-payment-id]') : target.target;
        }

        if (!btn) return;

        var pId = btn.getAttribute('data-payment-id') || '';
        var bId = btn.getAttribute('data-booking-id') || '';
        var pType = btn.getAttribute('data-payment-type') || 'emi';
        var pLabel = btn.getAttribute('data-payment-label') || 'Payment';
        var pDetails = btn.getAttribute('data-payment-details') || pLabel;
        var amount = btn.getAttribute('data-amount') || '0.00';
        var pDate = btn.getAttribute('data-payment-date') || '';

        var elPaymentId = document.getElementById('epd_payment_id');
        var elBookingId = document.getElementById('epd_booking_id');
        var elPaymentType = document.getElementById('epd_payment_type');
        var elDetails = document.getElementById('epd_details');
        var elAmount = document.getElementById('epd_amount');
        var elPaymentDate = document.getElementById('epd_payment_date');
        var elModalTitle = document.getElementById('epd_modal_title_text');
        var elTypeName = document.getElementById('epd_type_name');
        var elInputLabel = document.getElementById('epd_input_label');

        if (elPaymentId) elPaymentId.value = pId;
        if (elBookingId) elBookingId.value = bId;
        if (elPaymentType) elPaymentType.value = pType;
        if (elDetails) elDetails.textContent = pDetails;
        if (elAmount) elAmount.textContent = 'TK. ' + amount;
        if (elPaymentDate) elPaymentDate.value = pDate;
        if (elModalTitle) elModalTitle.textContent = 'Edit ' + pLabel + ' Date';
        if (elTypeName) elTypeName.textContent = pLabel;
        if (elInputLabel) elInputLabel.textContent = pLabel + ' Payment Date';
    };

    // Handle AJAX submit for editPaymentDateForm
    $('#editPaymentDateForm').on('submit', function (e) {
        e.preventDefault();
        var form = $(this);
        var btn = $('#btnSavePaymentDate');
        var originalBtnText = btn.html();

        var paymentId = $('#epd_payment_id').val();
        if (!paymentId) {
            Swal.fire({
                icon: 'error',
                title: 'Missing Reference',
                text: 'Invalid payment reference ID. Please re-open the edit window and try again.'
            });
            return;
        }

        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...');

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize() + '&is_ajax=1',
            dataType: 'json',
            success: function (res) {
                btn.prop('disabled', false).html(originalBtnText);
                if (res.success) {
                    $('#editPaymentDateModal').modal('hide');
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
                        text: res.message || 'Could not update payment date.'
                    });
                }
            },
            error: function () {
                btn.prop('disabled', false).html(originalBtnText);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Server error while updating payment date.'
                });
            }
        });
    });

    // Listen on show.bs.modal for Bootstrap 5 native event
    var epdModalEl = document.getElementById('editPaymentDateModal');
    if (epdModalEl) {
        epdModalEl.addEventListener('show.bs.modal', function (e) {
            var trigger = e.relatedTarget || document.activeElement;
            if (trigger) {
                window.openEditPaymentDateModal(trigger);
            }
        });
    }

    // Also support jQuery delegated click and show.bs.modal
    if (typeof jQuery !== 'undefined') {
        jQuery(document).on('click', '.btn-edit-payment-date', function(e) {
            window.openEditPaymentDateModal(this);
        });

        jQuery('#editPaymentDateModal').on('show.bs.modal', function(e) {
            var trigger = e.relatedTarget || (e.originalEvent && e.originalEvent.relatedTarget);
            if (trigger) {
                window.openEditPaymentDateModal(trigger);
            }
        });
    }
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
