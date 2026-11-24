<?php
// Disable layout for this page
$disable_layout = true;

// Filter out failed, unverified pending, AND rejected (verification_status) transactions
$validTransactions = array_values(array_filter($transactions, function ($t) {
    $payStatus = strtolower($t['payment_status'] ?? '');
    $verStatus = strtolower($t['verification_status'] ?? 'pending');
    
    // Exclude rejected verification or failed payments
    if ($verStatus === 'rejected' || $payStatus === 'failed') {
        return false;
    }
    
    // Include if verified by accounts OR payment status is paid/partially_paid
    if ($verStatus === 'verified' || in_array($payStatus, ['paid', 'partially_paid'], true)) {
        return true;
    }
    
    return false;
}));

// Ensure transactions are sorted in business order:
// 1. Booking Money, 2. Registration/Land Share, 3. Dev Charge, 4. EMI Installments (Advance -> Regular -> Settlement), 5. Others
if (class_exists('AccountClient')) {
    $sortModel = new AccountClient();
    $sortModel->sortTransactions($validTransactions);
} else {
    usort($validTransactions, function ($a, $b) {
        $getGroup = function ($t) {
            $type = strtolower($t['payment_type'] ?? '');
            if ($type === 'booking_money') return 1;
            if (in_array($type, ['registration', 'land_share', 'mutation_cost'], true)) return 2;
            if (in_array($type, ['development_charge', 'construction_fee', 'shop_service_charge'], true)) return 3;
            if ($type === 'emi' 
                || $type === 'emi_due_settlement' 
                || (!empty($t['settlement_type']) && $t['settlement_type'] === 'emi_due_settlement')
                || !empty($t['emi_no']) 
                || !empty($t['parent_emi_no'])
                || stripos($t['installment_label'] ?? '', 'installment') !== false
                || stripos($t['payment_details'] ?? '', 'installment') !== false
                || stripos($t['installment_label'] ?? '', 'settlement') !== false
                || stripos($t['payment_details'] ?? '', 'settlement') !== false
                || stripos($t['installment_label'] ?? '', 'emi') !== false
                || stripos($t['payment_details'] ?? '', 'emi') !== false
            ) {
                return 4;
            }
            return 5;
        };
        $gA = $getGroup($a);
        $gB = $getGroup($b);
        if ($gA !== $gB) return $gA <=> $gB;
        if ($gA === 4) {
            $isSettlement = function ($t) {
                return (!empty($t['settlement_type']) && $t['settlement_type'] === 'emi_due_settlement') 
                    || (($t['payment_type'] ?? '') === 'emi_due_settlement')
                    || (stripos($t['installment_label'] ?? '', 'settlement') !== false)
                    || (stripos($t['payment_details'] ?? '', 'settlement') !== false);
            };

            $getNo = function ($t) use ($isSettlement) {
                if ($isSettlement($t)) {
                    if (!empty($t['parent_emi_no'])) return (int)$t['parent_emi_no'];
                    if (!empty($t['installment_label']) && preg_match('/(\d+)/', $t['installment_label'], $m)) return (int)$m[1];
                    if (!empty($t['payment_details']) && preg_match('/(\d+)/', $t['payment_details'], $m)) return (int)$m[1];
                    if (!empty($t['emi_no'])) return (int)$t['emi_no'];
                    return 9999;
                }
                if (!empty($t['emi_no'])) return (int)$t['emi_no'];
                if (!empty($t['parent_emi_no'])) return (int)$t['parent_emi_no'];
                if (!empty($t['installment_label']) && preg_match('/(\d+)/', $t['installment_label'], $m)) return (int)$m[1];
                if (!empty($t['payment_details']) && preg_match('/(\d+)/', $t['payment_details'], $m)) return (int)$m[1];
                return 9999;
            };
            $noA = $getNo($a);
            $noB = $getNo($b);
            if ($noA !== $noB) return $noA <=> $noB;
            $getSub = function ($t) use ($isSettlement) {
                if ($isSettlement($t)) return 3;
                if (strtolower($t['payment_status'] ?? '') === 'partially_paid') return 1;
                return 2;
            };
            $sA = $getSub($a);
            $sB = $getSub($b);
            if ($sA !== $sB) return $sA <=> $sB;
        }
        $dA = $a['payment_date'] ?? '';
        $dB = $b['payment_date'] ?? '';
        if ($dA !== $dB) return strcmp($dA, $dB);
        return ((int)($a['id'] ?? 0)) <=> ((int)($b['id'] ?? 0));
    });
}

// Compute totals
$totalPrice       = (float)($paymentSummary['total_price'] ?? 0);
$bookingDiscount  = (float)(!empty($paymentSummary['discount_amount']) ? $paymentSummary['discount_amount'] : ($clientDetails['discount_amount'] ?? 0));
$agreementValue   = (float)($paymentSummary['agreement_value'] ?? $totalPrice);

// Pre-calculate verified/valid transactions totals (Paid amount, EMI discounts, etc.)
$actualPaid       = 0;
$totalEmiDiscount = 0;
$othersPaid       = 0;

foreach ($validTransactions as $t) {
    $isEmiDiscount = (!empty($t['is_emi_discount']) && $t['is_emi_discount'] == 1);
    $bulkDisc      = (float)($t['bulk_emi_discount'] ?? 0);
    $amt           = (float)($t['amount'] ?? 0);
    $pType         = strtolower($t['payment_type'] ?? '');

    if ($isEmiDiscount) {
        $totalEmiDiscount += $amt;
        continue; // Exclude EMI discount from actual cash paid
    }

    if ($bulkDisc > 0) {
        $totalEmiDiscount += $bulkDisc;
    }

    if ($pType === 'others' || $pType === 'penalty_settlement') {
        $othersPaid += $amt;
    }

    $actualPaid += $amt;
}

// Track penalty payments & carried-forward penalty due
$totalPenaltyPaidStmt = 0.0;
foreach ($validTransactions as $t) {
    $totalPenaltyPaidStmt += (float)($t['penalty_paid'] ?? 0);
}
$carriedPenaltyDue = round((float)($paymentSummary['total_penalty_due'] ?? ($clientDetails['total_penalty_due'] ?? 0)), 2);

// Bulk/EMI discount: prioritize sum from validTransactions, fallback to paymentSummary
$bulkEmiDiscount = max($totalEmiDiscount, (float)($paymentSummary['total_bulk_emi_discount'] ?? 0));

// Total discounts = Booking-time discount + EMI discount(s)
$totalDiscounts  = $bookingDiscount + $bulkEmiDiscount;

// Calculate remaining balance (deducts flat price payments, booking discount, and EMI discounts; adds live accrued overdue penalty + carried-forward penalty due)
$accruedPenalty = round((float)($paymentSummary['accrued_penalty'] ?? ($clientDetails['accrued_penalty'] ?? 0)), 2);
$totalPenaltyOutstanding = round($accruedPenalty + $carriedPenaltyDue, 2);
$unitPaid = max(0, $actualPaid - $othersPaid);
if ($totalPrice > 0) {
    $principalRemaining = max(0, round($totalPrice - $bookingDiscount - $unitPaid - $bulkEmiDiscount));
    $remainingBalance   = round($principalRemaining + $totalPenaltyOutstanding, 2);
} else {
    $remainingBalance   = (float)($paymentSummary['total_due'] ?? 0);
}

// Consolidate verified transactions by payment date (1 row per date, summing amounts)
$dateGroups = [];
$bookingDiscountApplied = false;
$lastKnownEmiNo = 0;

foreach ($validTransactions as $txn) {
    $amount      = (float)($txn['amount'] ?? 0);
    $paymentType = $txn['payment_type'] ?? '';
    $discount    = (float)($txn['bulk_emi_discount'] ?? 0);

    // Apply booking discount once on the booking_money date
    if ($paymentType === 'booking_money' && $bookingDiscount > 0 && !$bookingDiscountApplied) {
        $discount += $bookingDiscount;
        $bookingDiscountApplied = true;
    }

    $emiNo = $txn['emi_no'] ?? null;
    if (!empty($emiNo)) {
        $lastKnownEmiNo = max($lastKnownEmiNo, (int)$emiNo);
    }

    $chequeNo  = trim((string)($txn['cheque_no'] ?? ''));
    $transRef  = trim((string)($txn['transaction_ref'] ?? ''));
    $bankName  = trim((string)(!empty($txn['bank_name']) ? $txn['bank_name'] : ($txn['bank_display_name'] ?? '')));
    $payMethod = ucfirst(trim((string)($txn['payment_method'] ?? '')));

    $rawDate   = !empty($txn['payment_date']) ? strtotime($txn['payment_date']) : false;
    $dateKey   = $rawDate ? date('Y-m-d', $rawDate) : '0000-00-00';
    $payDate   = $rawDate ? date('d-M-Y', $rawDate) : '—';

    // Determine clean term label
    $term = $txn['installment_label'] ?? strtoupper(str_replace('_', ' ', $paymentType));
    $isRegularEmi = false;
    $resolvedEmiNo = null;

    if ($paymentType === 'booking_money') {
        $term = 'Booking';
    } elseif ($paymentType === 'land_share') {
        $term = 'Land Share';
    } elseif ($paymentType === 'registration') {
        $term = 'Registration';
    } elseif ($paymentType === 'development_charge') {
        $term = 'Dev. Charge';
    } elseif ($paymentType === 'emi' || $paymentType === 'emi_due_settlement') {
        if (!empty($txn['settlement_type']) && $txn['settlement_type'] === 'emi_due_settlement') {
            $term = $txn['installment_label'] ?? $term;
        } else {
            if (!$emiNo) {
                $lastKnownEmiNo++;
                $emiNo = $lastKnownEmiNo;
            }
            $isPartial = (!empty($txn['payment_status']) && strtolower($txn['payment_status']) === 'partially_paid');
            $resolvedEmiNo = (int)$emiNo;
            if ($isPartial) {
                $term = 'Adv. Installment ' . $resolvedEmiNo;
            } else {
                $isRegularEmi = true;
                $term = 'Installment ' . $resolvedEmiNo;
            }
        }
    } elseif ($paymentType === 'shop_service_charge') {
        $term = 'Shop Svc Charge';
    } elseif ($paymentType === 'penalty_settlement') {
        $term = 'Late Penalty Settlement';
    } elseif ($paymentType === 'others') {
        $otherPurpose = !empty($txn['other_purpose']) ? $txn['other_purpose'] : 'Extra Charges';
        $term = 'Others (' . $otherPurpose . ')';
    }

    // Payment mode & bank display
    $isEmiDiscount = !empty($txn['is_emi_discount']) && $txn['is_emi_discount'] == 1;
    if ($isEmiDiscount) {
        $discount = $amount;
        $amount   = 0.0;
        $modeLabel = 'Discount Paid';
        $chequeOrRef = '';
    } else {
        $payTypeDisplay = !empty($bankName) ? 'Bank Transfer' : ($payMethod ?: 'Cash');
        $modeLabel = !empty($bankName) ? ($payTypeDisplay . ' (' . $bankName . ')') : $payTypeDisplay;
        $chequeOrRef = !empty($chequeNo) ? $chequeNo : $transRef;
    }

    $receiptNo = 'REC-' . str_pad($txn['id'] ?? '0', 6, '0', STR_PAD_LEFT);

    if (!isset($dateGroups[$dateKey])) {
        $dateGroups[$dateKey] = [
            'date_key'        => $dateKey,
            'formatted_date'  => $payDate,
            'regular_emi_nos' => [],
            'other_terms'     => [],
            'receipts'        => [],
            'modes'           => [],
            'refs'            => [],
            'discount'        => 0.0,
            'paid_amount'     => 0.0,
            'unit_deductible' => 0.0,
            'txn_count'       => 0,
            'is_discount_only'=> true,
        ];
    }

    $g = &$dateGroups[$dateKey];
    if ($isRegularEmi && $resolvedEmiNo !== null) {
        $g['regular_emi_nos'][] = $resolvedEmiNo;
    } elseif (!in_array($term, $g['other_terms'], true)) {
        $g['other_terms'][] = $term;
    }

    if (!in_array($receiptNo, $g['receipts'], true)) {
        $g['receipts'][] = $receiptNo;
    }
    if (!empty($modeLabel) && !in_array($modeLabel, $g['modes'], true)) {
        $g['modes'][] = $modeLabel;
    }
    if (!empty($chequeOrRef) && !in_array($chequeOrRef, $g['refs'], true)) {
        $g['refs'][] = $chequeOrRef;
    }

    $g['discount']    += $discount;
    $g['paid_amount'] += $amount;
    if (!$isEmiDiscount) {
        $g['is_discount_only'] = false;
    }
    if (strtolower($paymentType) !== 'others' && strtolower($paymentType) !== 'penalty_settlement') {
        $g['unit_deductible'] += ($amount + $discount);
    }
    $g['txn_count']++;
    unset($g);
}

// Sort consolidated rows chronologically by payment date
ksort($dateGroups);

// Build final formatted display strings per date
foreach ($dateGroups as &$g) {
    $termParts = [];
    if (!empty($g['regular_emi_nos'])) {
        $nos = array_values(array_unique($g['regular_emi_nos']));
        sort($nos);
        $cnt = count($nos);
        if ($cnt === 1) {
            $termParts[] = 'Installment ' . $nos[0];
        } elseif (($nos[$cnt - 1] - $nos[0]) === ($cnt - 1)) {
            $termParts[] = 'Installment ' . $nos[0] . '–' . $nos[$cnt - 1];
        } else {
            $termParts[] = 'Installment ' . implode(', ', $nos);
        }
    }
    foreach ($g['other_terms'] as $ot) {
        $termParts[] = $ot;
    }
    $g['term_display'] = implode(', ', $termParts);

    $rc = count($g['receipts']);
    if ($rc <= 2) {
        $g['receipt_display'] = implode(', ', $g['receipts']);
    } else {
        $g['receipt_display'] = $g['receipts'][0] . ' – ' . $g['receipts'][$rc - 1] . ' (' . $rc . ')';
    }

    $g['mode_display'] = implode('; ', $g['modes']);
    $g['ref_display']  = !empty($g['refs']) ? implode(', ', $g['refs']) : '—';
}
unset($g);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Statement - <?= htmlspecialchars($clientDetails['client_name'] ?? 'N/A') ?></title>
    <style>
        @page { size: A4 portrait; margin: 10mm; }

        @media print {
            .no-print { display: none !important; }

            html, body {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                font-size: 7pt;
                background: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .statement-container {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 auto !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
            }

            /* Header compact */
            .statement-header { margin-bottom: 3mm !important; padding-bottom: 2mm !important; }
            .company-header { flex-direction: row !important; gap: 10px !important; }
            .company-name { font-size: 12pt !important; }
            .company-details { font-size: 7pt !important; }
            .statement-info { text-align: right !important; border-left: 1px solid #ccc !important; border-top: none !important; padding-left: 8px !important; padding-top: 0 !important; }
            .statement-title { font-size: 12pt !important; margin-bottom: 4px !important; }
            .statement-meta { font-size: 7pt !important; }

            /* Info grid compact */
            .info-grid { grid-template-columns: 1fr 1fr !important; gap: 3mm !important; margin-bottom: 3mm !important; }
            .info-box { padding: 4px 6px !important; font-size: 7pt !important; }
            .info-title { margin-bottom: 3px !important; padding-bottom: 2px !important; font-size: 7pt !important; }
            .info-row { margin-bottom: 1px !important; }

            /* Summary strip – force single row */
            .summary-strip {
                display: flex !important;
                flex-wrap: nowrap !important;
                margin-bottom: 3mm !important;
                page-break-inside: avoid;
            }
            .summary-cell { padding: 4px 5px !important; border-bottom: none !important; border-right: 1px solid #000 !important; flex: 1 1 auto !important; }
            .summary-cell:last-child { border-right: none !important; }
            .summary-cell .s-label { font-size: 6pt !important; }
            .summary-cell .s-value { font-size: 10pt !important; }

            /* Table fit to page */
            .table-wrapper { overflow: visible !important; }

            .main-table {
                font-size: 6.5pt !important;
                min-width: 0 !important;
                width: 100% !important;
                table-layout: fixed;
            }

            .main-table th, .main-table td {
                padding: 2px 3px !important;
                word-wrap: break-word;
                overflow-wrap: break-word;
            }

            .main-table th { font-size: 6pt !important; white-space: normal !important; }
            .main-table .sub-header th { font-size: 5.5pt !important; }
            .main-table .amount { font-size: 6.5pt !important; }

            /* Prevent page breaks inside table */
            .main-table { page-break-inside: auto; }
            .main-table tr { page-break-inside: avoid; }
            .main-table thead { display: table-header-group; }

            /* Footer compact */
            .statement-footer { font-size: 6pt !important; padding: 3px 5px !important; margin-top: 2mm !important; }
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f0f2f5;
            color: #000;
            line-height: 1.3;
            font-size: 11px;
            padding: 10px;
        }

        .statement-container {
            max-width: 1400px;
            background: #fff;
            padding: 15px;
            margin: 0 auto;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* ─── Header ─── */
        .statement-header {
            margin-bottom: 12px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .company-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
        }

        .company-info { flex: 2; }
        .company-name { font-size: 16px; font-weight: bold; text-transform: uppercase; margin-bottom: 3px; }
        .company-details { font-size: 10px; line-height: 1.4; }
        .statement-info { flex: 1; text-align: right; border-left: 1px solid #ccc; padding-left: 15px; }
        .statement-title { font-size: 16px; font-weight: bold; text-transform: uppercase; margin-bottom: 8px; }
        .statement-meta { font-size: 10px; }
        .meta-row { margin-bottom: 3px; }
        .meta-label { font-weight: bold; display: inline-block; min-width: 80px; }

        /* ─── Client + Property Info ─── */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 12px;
        }

        .info-box { border: 1px solid #000; padding: 10px; font-size: 11px; }
        .info-title { font-weight: bold; margin-bottom: 6px; padding-bottom: 5px; border-bottom: 1px solid #000; text-transform: uppercase; font-size: 11px; }
        .info-row { display: flex; justify-content: flex-start; gap: 10px; margin-bottom: 4px; }
        .info-row > span:first-child { min-width: 45px; }
        .info-val { font-weight: bold; text-align: left; }

        /* ─── Financial Summary Strip (TOP) ─── */
        .summary-strip {
            display: flex;
            flex-wrap: nowrap;
            margin-bottom: 15px;
            border: 2px solid #000;
        }

        .summary-cell {
            flex: 1;
            padding: 10px 12px;
            text-align: center;
            border-right: 1px solid #000;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 0;
        }

        .summary-cell:last-child { border-right: none; }

        .summary-cell .s-label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #555;
            margin-bottom: 4px;
        }

        .summary-cell .s-value {
            font-size: 15px;
            font-weight: bold;
            font-family: 'Courier New', monospace;
        }

        .summary-cell.highlight {
            /* Kept class but removed black bg per user request */
        }

        .summary-cell.highlight .s-label { color: #555; }
        .summary-cell.highlight .s-value { color: #000; }
        .summary-cell.positive .s-value { color: #060; }
        .summary-cell.negative .s-value { color: #c00; }

        /* ─── Main Table ─── */
        .table-wrapper { overflow-x: auto; }

        .main-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        .main-table th, .main-table td {
            border: 1px solid #000;
            padding: 5px 6px;
            vertical-align: middle;
        }

        .main-table th {
            background: #d9e2f3;
            font-weight: bold;
            text-align: center;
            font-size: 9px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .main-table .sub-header th {
            background: #e2efda;
            font-size: 8px;
        }

        .main-table .amount {
            text-align: right;
            font-family: 'Courier New', monospace;
            white-space: nowrap;
        }

        .main-table .center { text-align: center; }

        .main-table tbody tr:nth-child(even) { background: #fafafa; }

        .main-table .discount-row td { background: #fef9e7; }

        .main-table .total-row {
            background: #d9e2f3 !important;
            font-weight: bold;
        }

        .main-table .total-row td { padding: 7px 6px; border-top: 2px solid #000; }

        /* ─── Footer Note ─── */
        .statement-footer {
            margin-top: 15px;
            padding: 8px;
            background: #f8f8f8;
            border: 1px solid #ccc;
            font-size: 9px;
            line-height: 1.5;
        }

        /* ─── Action Buttons ─── */
        .action-buttons {
            position: sticky; top: 5px; z-index: 1000;
            display: flex; justify-content: end; gap: 8px;
            padding: 8px; margin-bottom: 15px; flex-wrap: wrap;
        }

        .btn {
            padding: 8px 14px; font-size: 12px; font-weight: 600;
            border: none; border-radius: 5px; cursor: pointer;
            display: inline-flex; align-items: center; gap: 5px;
            text-decoration: none; white-space: nowrap;
        }

        .btn:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn-print { background: linear-gradient(135deg, #ff3c00, #cc2f00); color: #fff; }
        .btn-back { background: linear-gradient(135deg, #007bff, #0056b3); color: #fff; }

        .period-filter { display: flex; align-items: center; gap: 8px; margin-right: auto; }
        .period-filter select, .period-filter input[type="date"] {
            padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 5px; font-size: 12px;
        }
        .period-filter button {
            padding: 6px 14px; background: linear-gradient(135deg, #2563eb, #1e40af);
            color: #fff; border: none; border-radius: 5px; font-size: 12px; font-weight: 600; cursor: pointer;
        }
        #customDateRange { display: flex; gap: 6px; align-items: center; }

        @media (max-width: 768px) {
            .company-header { flex-direction: column; }
            .statement-info { text-align: left; border-left: none; border-top: 1px solid #ccc; padding-left: 0; padding-top: 10px; }
            .info-grid { grid-template-columns: 1fr; }
            .summary-strip { flex-wrap: wrap; }
            .summary-cell { flex: 1 1 50%; border-bottom: 1px solid #000; }
            .summary-cell:nth-child(even) { border-right: none; }
            .summary-cell:last-child { flex: 1 1 100%; border-right: none; border-bottom: none; }
            .action-buttons { flex-direction: column; }
            .btn { width: 100%; justify-content: center; }
            .period-filter { flex-direction: column; align-items: stretch; width: 100%; }
        }
    </style>
</head>

<body>

    <!-- Action Buttons -->
    <div class="action-buttons no-print">
        <div class="period-filter">
            <select id="periodSelect" onchange="filterByPeriod()">
                <option value="all" <?= ($period === 'all') ? 'selected' : '' ?>>All Transactions</option>
                <option value="3" <?= ($period === '3') ? 'selected' : '' ?>>Last 3 Months</option>
                <option value="6" <?= ($period === '6') ? 'selected' : '' ?>>Last 6 Months</option>
                <option value="12" <?= ($period === '12') ? 'selected' : '' ?>>Last 12 Months</option>
                <option value="custom" <?= ($period === 'custom') ? 'selected' : '' ?>>Custom Range</option>
            </select>
            <div id="customDateRange" style="display: <?= ($period === 'custom') ? 'flex' : 'none' ?>;">
                <input type="date" id="fromDate" value="<?= $fromDate ?? '' ?>">
                <span>to</span>
                <input type="date" id="toDate" value="<?= $toDate ?? '' ?>">
            </div>
            <button onclick="applyFilter()">Apply</button>
        </div>
        <button onclick="window.print()" class="btn btn-print">Print Statement</button>
        <a href="<?= $GLOBALS['base_url'] ?>/clients-cash-in-history?booking_id=<?= $clientDetails['booking_id'] ?>" class="btn btn-back">Back to History</a>
    </div>

    <div class="statement-container" id="statement-content">
        <!-- Header -->
        <div class="statement-header">
            <div class="company-header">
                <div class="company-info">
                    <div class="company-name">The Premium Homes Ltd.</div>
                    <div class="company-details">
                        Land View Commercial Center, 9th Floor 28 Gulshan North C/A, <br> Gulshan Circle-2, Dhaka<br>
                        Phone: +8801958253300 | Email: info@dpremiumhomes.com
                    </div>
                </div>
                <div class="statement-info">
                    <div class="statement-title">Account Statement</div>
                    <div class="statement-meta">
                        <div class="meta-row">
                            <span class="meta-label">Statement No:</span>
                            STMT-<?= str_pad($clientDetails['booking_id'], 6, '0', STR_PAD_LEFT) ?>
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">Generated:</span>
                            <?= date('d-M-Y') ?>
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">Period:</span>
                            <?= $periodText ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Client & Property Details -->
        <div class="info-box" style="margin-bottom: 12px; padding: 0;">
            <div class="info-grid" style="margin-bottom: 0; gap: 0;">
                <div style="padding: 10px; border-right: 1px solid #000;">
                    <div class="info-title">Client Information</div>
                    <div class="info-row"><span>Name:</span><span class="info-val"><?= htmlspecialchars($clientDetails['client_name'] ?? 'N/A') ?></span></div>
                    <div class="info-row"><span>Phone:</span><span class="info-val"><?= htmlspecialchars($clientDetails['phone'] ?? 'N/A') ?></span></div>
                    <div class="info-row"><span>Email:</span><span class="info-val"><?= htmlspecialchars($clientDetails['email'] ?? 'N/A') ?></span></div>
                </div>
                <div style="padding: 10px;">
                    <div class="info-title">Property Details</div>
                    <div class="info-row"><span>Project:</span><span class="info-val"><?= htmlspecialchars($clientDetails['project_name'] ?? 'N/A') ?></span></div>
                    <div class="info-row"><span>Unit:</span><span class="info-val"><?= htmlspecialchars($clientDetails['unit_name'] ?? 'N/A') ?></span></div>
                    <div class="info-row"><span>Floor:</span><span class="info-val"><?= htmlspecialchars($clientDetails['flat_no'] ?? 'N/A') ?></span></div>
                </div>
            </div>
        </div>

        <!-- ═══ FINANCIAL SUMMARY (TOP) ═══ -->
        <div class="summary-strip">
            <div class="summary-cell">
                <div class="s-label">Total Price <span style="text-transform:none; font-size:7px;">(Approximate)</span></div>
                <div class="s-value">৳<?= number_format($totalPrice, 2) ?></div>
            </div>
            <div class="summary-cell positive">
                <div class="s-label">Total Paid</div>
                <div class="s-value">৳<?= number_format($actualPaid, 2) ?></div>
            </div>
            <div class="summary-cell">
                <div class="s-label">Total Discount</div>
                <div class="s-value">৳<?= number_format($totalDiscounts, 2) ?></div>
                <?php if ($bookingDiscount > 0 && $bulkEmiDiscount > 0): ?>
                    <div style="font-size:7.5px; color:#6b7280; margin-top:2px; font-weight:normal;">
                        Booking: ৳<?= number_format($bookingDiscount, 0) ?> | EMI: ৳<?= number_format($bulkEmiDiscount, 0) ?>
                    </div>
                <?php elseif ($bookingDiscount > 0): ?>
                    <div style="font-size:7.5px; color:#6b7280; margin-top:2px; font-weight:normal;">
                        Booking: ৳<?= number_format($bookingDiscount, 0) ?>
                    </div>
                <?php elseif ($bulkEmiDiscount > 0): ?>
                    <div style="font-size:7.5px; color:#6b7280; margin-top:2px; font-weight:normal;">
                        EMI Discount: ৳<?= number_format($bulkEmiDiscount, 0) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="summary-cell">
                <div class="s-label">Payment Dates</div>
                <div class="s-value"><?= count($dateGroups) ?></div>
                <?php if (count($validTransactions) > count($dateGroups)): ?>
                    <div style="font-size:7.5px; color:#6b7280; margin-top:2px; font-weight:normal;">
                        (<?= count($validTransactions) ?> Receipts)
                    </div>
                <?php endif; ?>
            </div>
            <div class="summary-cell highlight">
                <div class="s-label">Outstanding Balance <span style="text-transform:none; font-size:7px;">(Approximate)</span></div>
                <div class="s-value">
                    <?php if ($remainingBalance <= 0): ?>
                        FULLY PAID
                    <?php else: ?>
                        ৳<?= number_format($remainingBalance, 2) ?>
                    <?php endif; ?>
                </div>
                <?php if ($totalPenaltyOutstanding > 0): ?>
                    <div style="font-size:7.5px; color:#b45309; margin-top:2px; font-weight:bold;">
                        Incl. Penalty Due: ৳<?= number_format($totalPenaltyOutstanding, 2) ?>
                    </div>
                <?php endif; ?>
                <?php if ($totalPenaltyPaidStmt > 0): ?>
                    <div style="font-size:7.5px; color:#0f766e; margin-top:1px; font-weight:normal;">
                        Penalty Paid: ৳<?= number_format($totalPenaltyPaidStmt, 2) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ═══ DATE-WISE CONSOLIDATED TRANSACTION TABLE ═══ -->
        <div class="table-wrapper">
            <table class="main-table">
                <thead>
                    <tr>
                        <th style="width:4%;">SL</th>
                        <th style="width:10%;">Date</th>
                        <th style="width:16%;">Particulars / Term</th>
                        <th style="width:14%;">Receipt No.</th>
                        <th style="width:18%;">Payment Mode &amp; Bank</th>
                        <th style="width:8%;">Cheque /<br>Txn ID</th>
                        <th style="width:6%;">Discount</th>
                        <th style="width:10%;">Paid<br>Amount</th>
                        <th style="width:14%;">Remaining<br>Balance<br><span style="text-transform:none; font-size:6.5px; font-weight:normal;">(Approximate)</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($dateGroups)): ?>
                        <?php
                        $sl = 0;
                        $runningBalance = $totalPrice;
                        $totalDiscount  = 0;
                        $totalPaidCol   = 0;

                        foreach ($dateGroups as $row):
                            $sl++;
                            $rowDiscount = $row['discount'];
                            $rowPaid     = $row['paid_amount'];

                            $totalDiscount += $rowDiscount;
                            $totalPaidCol  += $rowPaid;

                            // Running balance (Others payments are extra incidental costs and do not deduct from flat price)
                            $runningBalance -= $row['unit_deductible'];
                            if ($runningBalance < 0) $runningBalance = 0;
                        ?>
                        <tr<?= $row['is_discount_only'] ? ' class="discount-row"' : '' ?>>
                            <td class="center"><?= $sl ?></td>
                            <td class="center" style="white-space:nowrap;"><?= htmlspecialchars($row['formatted_date']) ?></td>
                            <td><?= htmlspecialchars($row['term_display']) ?></td>
                            <td class="center" style="font-size:8.5px;"><?= htmlspecialchars($row['receipt_display']) ?></td>
                            <td>
                                <?php if ($row['is_discount_only']): ?>
                                    <span style="background:#f59e0b; color:#fff; padding:2px 6px; border-radius:4px; font-size:8.5px; white-space:nowrap;">Discount Paid</span>
                                <?php else: ?>
                                    <?= htmlspecialchars($row['mode_display']) ?>
                                <?php endif; ?>
                            </td>
                            <td class="center"><?= htmlspecialchars($row['ref_display']) ?></td>
                            <td class="amount"><?= $rowDiscount > 0 ? number_format($rowDiscount, 0) : '' ?></td>
                            <td class="amount"><strong><?= number_format($rowPaid, 0) ?></strong></td>
                            <td class="amount"><?= number_format($runningBalance, 0) ?></td>
                        </tr>
                        <?php endforeach; ?>

                        <?php if ($bookingDiscount > 0 && empty($bookingDiscountApplied)): ?>
                        <tr class="discount-row">
                            <td class="center"><?= ++$sl ?></td>
                            <td class="center">—</td>
                            <td><strong>Booking Discount</strong></td>
                            <td class="center">—</td>
                            <td><span style="background:#10b981; color:#fff; padding:2px 6px; border-radius:4px; font-size:8.5px; white-space:nowrap;">Booking Discount</span></td>
                            <td class="center">—</td>
                            <td class="amount"><strong><?= number_format($bookingDiscount, 0) ?></strong></td>
                            <td class="amount">0</td>
                            <td class="amount"><?php $runningBalance -= $bookingDiscount; if ($runningBalance < 0) $runningBalance = 0; echo number_format($runningBalance, 0); ?></td>
                        </tr>
                        <?php $totalDiscount += $bookingDiscount; ?>
                        <?php endif; ?>

                        <?php if ($accruedPenalty > 0): ?>
                        <tr class="discount-row">
                            <td class="center"><?= ++$sl ?></td>
                            <td class="center"><?= date('d-M-Y') ?></td>
                            <td><strong>Overdue EMI Late Penalty (5% Auto-Accrued)</strong></td>
                            <td class="center">—</td>
                            <td><span style="background:#dc2626; color:#fff; padding:2px 6px; border-radius:4px; font-size:8.5px; white-space:nowrap;">Auto Penalty</span></td>
                            <td class="center">—</td>
                            <td class="amount"></td>
                            <td class="amount" style="color:#b45309;"><strong>+<?= number_format($accruedPenalty, 2) ?></strong></td>
                            <td class="amount"><?php $runningBalance = round(max(0, $runningBalance) + $accruedPenalty, 2); echo number_format($runningBalance, 2); ?></td>
                        </tr>
                        <?php endif; ?>

                        <!-- Totals Row -->
                        <tr class="total-row">
                            <td colspan="6" style="text-align:right;"><strong>TOTAL</strong></td>
                            <td class="amount"><?= $totalDiscount > 0 ? number_format($totalDiscount, 0) : '' ?></td>
                            <td class="amount"><strong><?= number_format($totalPaidCol, 0) ?></strong></td>
                            <td class="amount"><strong><?= number_format(max(0, $runningBalance), $accruedPenalty > 0 ? 2 : 0) ?></strong></td>
                        </tr>

                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align:center; padding:20px;">
                                No transactions found for the selected period.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Footer Note -->
     <div class="statement-footer">
            <strong>Note:</strong> This statement includes only verified transactions. Multiple payments made on the same date are consolidated into a single date-wise entry. Rejected and pending payments are excluded from this report.
            <?php if ($totalDiscounts > 0): ?>
            <?php if ($bookingDiscount > 0): ?> Booking discount of ৳<?= number_format($bookingDiscount, 2) ?> has been applied.<?php endif; ?>
            <?php if ($bulkEmiDiscount > 0): ?> EMI discount of ৳<?= number_format($bulkEmiDiscount, 2) ?> has been applied.<?php endif; ?>
            <?php endif; ?>
            <br><em>Statement generated on <?= date('d M Y \a\t h:i A') ?> — The Premium Homes Ltd.</em>
        </div>
    </div>

    <script>
        function filterByPeriod() {
            const period = document.getElementById('periodSelect').value;
            document.getElementById('customDateRange').style.display = period === 'custom' ? 'flex' : 'none';
        }

        function applyFilter() {
            const period = document.getElementById('periodSelect').value;
            const bookingId = <?= $clientDetails['booking_id'] ?>;
            let url = '<?= $GLOBALS['base_url'] ?>/client-statement?booking_id=' + bookingId + '&period=' + period;

            if (period === 'custom') {
                const fromDate = document.getElementById('fromDate').value;
                const toDate = document.getElementById('toDate').value;
                if (!fromDate || !toDate) { alert('Please select both from and to dates'); return; }
                url += '&from=' + fromDate + '&to=' + toDate;
            }

            window.location.href = url;
        }
    </script>
</body>

</html>
