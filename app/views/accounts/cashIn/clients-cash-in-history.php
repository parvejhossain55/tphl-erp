<?php
$page_title = "Clients Cash in History";
$page = 'Clients';
$addPage = 'add transaction';
$addPage = true;
$show_breadcrumb = true;
?>

<div class="row">
    <!-- Client Header Section -->
    <div class="col-xxl-12 mb-4">

        <div class="card__wrapper">
            <div class="card__body">
                <h3 class="mb-2 card__icon">
                    <i class="fa-light fa-user-circle me-2"></i>
                    <?= htmlspecialchars($clientDetails['client_name'] ?? 'N/A') ?>
                </h3>
                <div class="d-flex flex-wrap gap-3">
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
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-money-bill"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Price</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title">৳<?= number_format($paymentSummary['total_price'] ?? 0, 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-hand-holding-usd"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Land Share</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title">৳<?= number_format($paymentSummary['booking_money'] ?? 0, 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-file-certificate"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Registration Money</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title">৳<?= number_format($paymentSummary['registration_money'] ?? 0, 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php 
    // Calculate Service Fee (10% of construction fee paid)
    $constructionFee = $paymentSummary['construction_fee'] ?? 0;
    $constructionFeePaid = $paymentSummary['construction_fee_paid'] ?? 0;
    
    // Calculate 10% service fee from the paid amount
    $serviceFee = $constructionFeePaid * 0.10;
    
    // Construction fee paid after deducting service fee
    $constructionFeePaidAfterService = $constructionFeePaid - $serviceFee;
    
    // Recalculate remaining construction fee
    $remainingConstructionFee = $constructionFee - $constructionFeePaidAfterService;
    ?>

    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-building"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Construction Fee</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title">৳<?= number_format($constructionFee, 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-money-check-dollar"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Construction Fee Paid</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title">৳<?= number_format($constructionFeePaidAfterService, 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-hand-holding-dollar"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Service Fee (10%)</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title">৳<?= number_format($serviceFee, 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-money-check-dollar"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Remaining Construction Fee</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title">৳<?= number_format($remainingConstructionFee, 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-calculator"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Average EMI Amount</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title">৳<?= number_format($paymentSummary['avg_emi_amount'] ?? 0, 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-list-ol"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total EMI</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title"><?= $paymentSummary['total_emi'] ?? 0 ?> </h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-check-circle"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Completed EMI</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title"><?= $paymentSummary['completed_emi'] ?? 0 ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-clock"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Pending EMI</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title"><?= $paymentSummary['pending_emi'] ?? 0 ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-calendar-check"></i></span>
                </div>
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

    <!-- Transaction History Table -->
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="card__header">
                <h5 class="card__title">Transaction History</h5>
            </div>
            <div class="col-xxl-12 mb-4">
                <div class="mt-3 d-flex gap-2 position ">
                    <a href="<?= $GLOBALS['base_url'] ?>/client-cash-in-form?booking_id=<?= $clientDetails['booking_id'] ?>" class="btn btn-primary mb-3">
                        <i class="fa-light fa-plus me-2"></i> Add Transaction
                    </a>
                    <a href="<?= $base_url ?>/client-statement/<?= $clientDetails['booking_id'] ?>" target="_blank" class="btn btn-primary mb-3">
                        <i class="fa-light fa-file-invoice me-2"></i> Get Statement
                    </a>

                </div>
                <div class="table__wrapper table-responsive">
                    <table class="table mb-20 multiple_tables">
                        <thead>
                            <tr class="table__title table__sort">
                                <th>Date</th>
                                <th>Installment</th>
                                <th>Paid Amount</th>
                                <th>Due Ammount</th>
                                <th>Status</th>
                                <th>Method</th>
                                <th>Payment Details</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody class="table__body">
                            <?php if (!empty($transactions)): ?>
                                <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td>
                                            <?php
                                            if (isset($transaction['payment_date'])) {
                                                echo date('d M, Y', strtotime($transaction['payment_date']));
                                            } elseif (isset($transaction['booking_date'])) {
                                                echo date('d M, Y', strtotime($transaction['booking_date']));
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($transaction['payment_type'] === 'emi'): ?>
                                                <span class="bd-badge bg-primary">
                                                    <?= $transaction['installment_label'] ?? 'EMI' ?>
                                                </span>
                                            <?php elseif ($transaction['payment_type'] === 'booking_money'): ?>
                                                <span class="bd-badge bg-secondary">
                                                    BOOKING
                                                </span>
                                            <?php else: ?>
                                                <span class="bd-badge bg-info">
                                                    <?= ucfirst(str_replace('_', ' ', $transaction['payment_type'])) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong>৳<?= number_format($transaction['amount'] ?? 0, 2) ?></strong>
                                        </td>
                                                                <td>
                            <?php 
                            $dueAmount = 0;
                            
                            // For EMI transactions, get due_emi_amount from emi_schedules
                            if ($transaction['payment_type'] === 'emi' && isset($transaction['due_emi_amount'])) {
                                $dueAmount = floatval($transaction['due_emi_amount']);
                            }
                            
                            // Display due amount with styling
                            if ($dueAmount > 0): 
                            ?>
                                <div class="d-flex align-items-center gap-1">
                                    <strong style="color: #e74c3c;">৳<?= number_format($dueAmount, 2) ?></strong>
                                </div>
                            <?php else: ?>
                                <span class="text-muted" style="font-size: 18px;">—</span>
                            <?php endif; ?>
                        </td>
                                        <td>
                                            <?php if (isset($transaction['status_class']) && isset($transaction['status_text'])): ?>
                                                <span class="bd-badge <?= $transaction['status_class'] ?>">
                                                    <?= $transaction['status_text'] ?>
                                                </span>
                                            <?php else: ?>
                                                <?php if (($transaction['payment_status'] ?? 'paid') == 'paid'): ?>
                                                    <span class="bd-badge bg-success">Paid</span>
                                                <?php elseif (($transaction['payment_status'] ?? '') == 'partially_paid'): ?>
                                                    <span class="bd-badge bg-info">Partially Paid</span>
                                                <?php elseif (($transaction['payment_status'] ?? '') == 'pending'): ?>
                                                    <span class="bd-badge bg-warning">Pending</span>
                                                <?php else: ?>
                                                    <span class="bd-badge bg-danger">Failed</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="bd-badge bg-light text-dark">
                                                <?= ucfirst($transaction['payment_method'] ?? 'agreement') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small>
                                                <?php if (($transaction['payment_method'] ?? '') === 'bank'): ?>
                                                    <?= $transaction['bank_name'] ? htmlspecialchars($transaction['bank_name']) : '' ?>
                                                    <?= $transaction['bank_account_no'] ? ' (' . htmlspecialchars($transaction['bank_account_no']) . ')' : '' ?>
                                                <?php elseif (($transaction['payment_method'] ?? '') === 'cheque'): ?>
                                                    Cheque No: <?= $transaction['cheque_no'] ? htmlspecialchars($transaction['cheque_no']) : 'N/A' ?>
                                                    <?= $transaction['bank_name'] ? ' - ' . htmlspecialchars($transaction['bank_name']) : '' ?>
                                                <?php elseif (($transaction['payment_method'] ?? '') === 'online'): ?>
                                                    <?= $transaction['online_type'] ? htmlspecialchars($transaction['online_type']) : 'Online' ?>
                                                <?php elseif (($transaction['payment_method'] ?? '') === 'agreement'): ?>
                                                    Land     <style>
        /* ==================== PRINT & PDF STYLES (Unchanged) ==================== */
        @page {
            size: A4;
            margin: 15mm;
        }

        @media print {

            .no-print,
            .no-pdf {
                display: none !important;
            }

            body {
                margin: 0 !important;
                padding: 0 !important;
                width: 210mm;
                height: 297mm;
                font-size: 10pt;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .statement-container {
                width: 210mm !important;
                min-height: 297mm !important;
                margin: 0 !important;
                padding: 5mm !important;
                box-shadow: none !important;
                border: none !important;
            }

            .company-header {
                flex-direction: row !important;
            }

            .statement-info {
                text-align: right !important;
                border-left: 1px solid #ccc !important;
                border-top: none !important;
                padding-left: 15px !important;
                padding-top: 0 !important;
            }

            .details-grid {
                grid-template-columns: 1fr 1fr !important;
            }

            .transaction-table {
                font-size: 8pt !important;
            }
        }

        /* ==================== BASE STYLES (Responsive) ==================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f5f5;
            color: #000;
            line-height: 1.3;
            font-size: clamp(10pt, 2vw, 12pt);
            margin: 0;
            padding: clamp(10px, 3vw, 20px);
        }

        .statement-container {
            max-width: 210mm;
            background: #fff;
            padding: clamp(12px, 3vw, 20px);
            margin: 0 auto;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* ==================== HEADER (Responsive) ==================== */
        .statement-header {
            margin-bottom: clamp(15px, 5vw, 30px);
            border-bottom: 2px solid #000;
            padding-bottom: clamp(10px, 2vw, 15px);
        }

        .company-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 15px;
        }

        .company-info {
            flex: 2;
        }

        .company-name {
            font-size: clamp(14px, 3vw, 18px);
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .company-details {
            font-size: clamp(9px, 2vw, 11px);
            line-height: 1.4;
        }

        .statement-info {
            flex: 1;
            text-align: right;
            border-left: 1px solid #ccc;
            padding-left: 15px;
        }

        .statement-title {
            font-size: clamp(14px, 3vw, 18px);
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .statement-meta {
            font-size: clamp(9px, 2vw, 11px);
        }

        .meta-row {
            margin-bottom: 5px;
        }

        .meta-label {
            font-weight: bold;
            display: inline-block;
            min-width: 90px;
        }



        .period-filter .filter-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }

        .period-filter select,
        .period-filter input[type="date"] {
            padding: 8px 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            background-color: #fff;
        }

        #customDateRange {
            background: #f9fafb;
            padding: 6px 8px;
            border-radius: 6px;
            border: 1px dashed #d1d5db;
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .period-filter button {
            padding: 8px 16px;
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: #ffffff;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        /* ==================== DETAILS SECTION (Responsive) ==================== */
        .details-section {
            margin-bottom: clamp(15px, 3vw, 20px);
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: clamp(10px, 2vw, 15px);
            margin-bottom: 15px;
        }

        .details-box {
            border: 1px solid #000;
            padding: clamp(10px, 2vw, 15px);
            font-size: clamp(11px, 2vw, 12px);
        }

        .details-title {
            font-weight: bold;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #000;
            text-transform: uppercase;
            font-size: clamp(12px, 2vw, 13px);
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            gap: 10px;
        }

        .detail-value {
            font-weight: bold;
            text-align: right;
            word-break: break-word;
        }

        /* ==================== TRANSACTION TABLE (Responsive with Scroll) ==================== */
        .transaction-section {
            margin-bottom: 20px;
        }

        .section-title {
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #000;
            font-size: clamp(13px, 2.5vw, 14px);
        }

        .transaction-table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 0 -5px;
            padding: 0 5px;
        }

        .transaction-table {
            width: 100%;
            border-collapse: collapse;
            font-size: clamp(9px, 2vw, 11px);
            min-width: 343px;
        }

        .transaction-table th,
        .transaction-table td {
            border: 1px solid #000;
            padding: clamp(6px, 1.5vw, 10px) clamp(4px, 1vw, 8px);
            vertical-align: middle;
        }

        .transaction-table th {
            background: #f8f8f8;
            font-weight: bold;
            text-transform: uppercase;
            font-size: clamp(8px, 1.8vw, 10px);
            white-space: nowrap;
        }

        .transaction-table .amount {
            text-align: right;
            font-weight: bold;
            font-family: 'Courier New', monospace;
        }

        .status-paid {
            font-weight: bold;
        }

        .status-pending {
            font-style: italic;
            color: #666;
        }

        /* ==================== TOTALS (Responsive) ==================== */
        .totals-section {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #000;
        }

        .totals-table {
            width: 100%;
            font-size: clamp(12px, 2.5vw, 13px);
            margin-bottom: 15px;
        }

        .totals-table td {
            padding: 8px 0;
        }

        .totals-label {
            text-align: right;
            padding-right: clamp(10px, 3vw, 20px);
            font-weight: bold;
            width: 70%;
        }

        .totals-value {
            text-align: right;
            font-weight: bold;
            font-family: 'Courier New', monospace;
            border-bottom: 1px solid #000;
            width: 30%;
            padding-left: 10px;
        }

        .grand-total td {
            padding-top: 10px;
            border-top: 2px solid #000;
            font-size: clamp(13px, 2.5vw, 14px);
        }

        .amount-in-words {
            padding-top: 15px;
            font-style: italic;
            text-align: right;
            font-size: clamp(10px, 2vw, 11px);
            color: #333;
        }

        .calculation-note {
            margin-top: 15px;
            padding: 10px;
            background: #f8f8f8;
            border: 1px solid #ccc;
            font-size: clamp(9px, 1.8vw, 10px);
        }

        /* ==================== ACTION BUTTONS (Responsive) ==================== */
        .action-buttons {
            position: sticky;
            top: 10px;
            z-index: 1000;
            display: flex;
            justify-content: end;
            gap: 10px;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 18px;
            font-size: 13px;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
            white-space: nowrap;
            text-decoration: none;
        }

        .btn:hover {
            opacity: 0.95;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.15);
            transform: translateY(-1px);
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: #fff;
        }

        .btn-print {
            background: linear-gradient(135deg, #ff3c00, #cc2f00);
            color: #fff;
        }

        .btn-back {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: #fff;
        }

        /* ==================== MOBILE RESPONSIVENESS ==================== */
        @media (max-width: 768px) {
            .company-header {
                flex-direction: column;
                gap: 15px;
            }

            .statement-info {
                text-align: left;
                border-left: none;
                border-top: 1px solid #ccc;
                padding-left: 0;
                padding-top: 15px;
            }

            .period-filter .filter-row {
                flex-direction: column;
                align-items: stretch;
            }

            .period-filter select,
            .period-filter input[type="date"],
            .period-filter button {
                width: 100%;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .meta-label {
                min-width: 70px;
            }
        }

        @media (max-width: 480px) {
            .details-grid {
                grid-template-columns: 1fr;
            }

            .transaction-table {
                min-width: 343px;
                width: 100%
            }
        }

        /* ==================== PDF GENERATION CLASS ==================== */
        .pdf-generating .statement-container {
            width: 210mm !important;
            min-height: 297mm !important;
            padding: 15mm !important;
        }

        .pdf-generating .company-header {
            flex-direction: row !important;
        }

        .pdf-generating .statement-info {
            text-align: right !important;
            border-left: 1px solid #ccc !important;
            border-top: none !important;
            padding-left: 15px !important;
            padding-top: 0 !important;
        }

        .pdf-generating .details-grid {
            grid-template-columns: 1fr 1fr !important;
        }

        .pdf-generating .transaction-table {
            font-size: 8pt !important;
        }
    </style> Agreement
                                                <?php else: ?>
                                                    Cash Payment
                                                <?php endif; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center justify-content-start gap-10">
                                                <?php if (!empty($transaction['id']) && strpos($transaction['id'], 'booking-') === false): ?>
                                                    <!-- INVOICE BUTTON - First button -->
                                                    <a href="<?= $GLOBALS['base_url'] ?>/payment-invoice?payment_id=<?= $transaction['id'] ?>"
                                                        class="table__icon invoice"
                                                        style="color: #fff; background-color: #6c5ffc;"
                                                        title="View Invoice"
                                                        target="_blank">
                                                        <i class="fa-light fa-file-invoice"></i>
                                                    </a>
                                                    <!-- EDIT BUTTON - Second button -->
                                                    <a href="<?= $GLOBALS['base_url'] ?>/edit-client-cash-in/<?= $transaction['id'] ?>"
                                                        class="table__icon edit"
                                                        title="Edit">
                                                        <i class="fa-sharp fa-light fa-pen"></i>
                                                    </a>
                                                    <!-- DELETE BUTTON - Third button -->
                                                    <a href="#" class="removeBtn table__icon delete"
                                                        onclick="deleteTransaction(<?= $transaction['id'] ?>); return false;"
                                                        title="Delete">
                                                        <i class="fa-regular fa-trash"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="table__icon disabled" title="System Generated">
                                                        <i class="fa-regular fa-lock"></i>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fa-light fa-receipt fa-2x mb-3"></i><br>
                                            No transactions found
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function editTransaction(transactionId) {
            // This is now handled by the link
        }

        function deleteTransaction(transactionId, bookingId) {
            if (confirm('Are you sure you want to delete this transaction? This action cannot be undone.')) {
                let currentBookingId = <?= $clientDetails['id'] ?? 'null' ?>;

                if (currentBookingId) {
                    window.location.href = '<?= $GLOBALS['base_url'] ?>/delete-transaction?payment_id=' + transactionId + '&booking_id=' + currentBookingId;
                } else {
                    alert('Cannot delete: Missing booking information');
                }
            }
        }

        function deleteBookingMoney(bookingId) {
            if (confirm('Are you sure you want to delete this booking? This will delete ALL related transactions and cannot be undone.')) {
                window.location.href = '<?= $GLOBALS['base_url'] ?>/delete-booking-money?booking_id=' + bookingId;
            }
        }
    </script>

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
    .card__wrapper .card__body h3{
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
.position{
    justify-content: end !important;
}
    }
    @media (max-width: 575px) {

    }
    @media (max-width: 480px) {
    .btn {
    font-size: 16px;
    padding: 6px 21px;
}
.position{
    justify-content: start !important;
}
    }
    @media (max-width: 400px) {
.btn {
    font-size: 14px;
    padding: 6px 14px;
}
    }

</style>