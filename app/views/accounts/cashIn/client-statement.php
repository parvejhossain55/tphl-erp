<?php
// Disable layout for this page
$disable_layout = true;

// Resolve dynamic project/category branding (The Nature, The Olympus Real Estate, or Default)
require __DIR__ . '/_project-branding.php';

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

// Pre-calculate verified/valid transactions totals (Paid amount, EMI discounts, unique payment dates)
$actualPaid       = 0.0;
$totalEmiDiscount = 0.0;
$othersPaid       = 0.0;
$uniquePayDates   = [];

foreach ($validTransactions as $t) {
    $isEmiDiscount = (!empty($t['is_emi_discount']) && $t['is_emi_discount'] == 1);
    $bulkDisc      = (float)($t['bulk_emi_discount'] ?? 0);
    $amt           = (float)($t['amount'] ?? 0);
    $pType         = strtolower($t['payment_type'] ?? '');

    if (!empty($t['payment_date'])) {
        $uniquePayDates[substr((string)$t['payment_date'], 0, 10)] = true;
    }

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

// Track penalty payments & carried-forward penalty due (strictly from penalty_settlement vouchers)
$totalPenaltyPaidStmt = 0.0;
foreach ($validTransactions as $t) {
    if (strtolower((string)($t['payment_type'] ?? '')) === 'penalty_settlement') {
        $totalPenaltyPaidStmt += (float)($t['penalty_paid'] > 0 ? $t['penalty_paid'] : ($t['amount'] ?? 0));
    }
}
$carriedPenaltyDue = round((float)($paymentSummary['total_penalty_due'] ?? ($clientDetails['total_penalty_due'] ?? 0)), 2);

// Bulk/EMI discount: prioritize sum from validTransactions, fallback to paymentSummary
$bulkEmiDiscount = max($totalEmiDiscount, (float)($paymentSummary['total_bulk_emi_discount'] ?? 0));

// Total discounts = Booking-time discount + EMI discount(s)
$totalDiscounts  = $bookingDiscount + $bulkEmiDiscount;

// Calculate remaining balance
$accruedPenalty = round((float)($paymentSummary['accrued_penalty'] ?? ($clientDetails['accrued_penalty'] ?? 0)), 2);
$totalPenaltyOutstanding = round($accruedPenalty + $carriedPenaltyDue, 2);
$unitPaid = max(0, $actualPaid - $othersPaid);
if ($totalPrice > 0) {
    $principalRemaining = max(0, round($totalPrice - $bookingDiscount - $unitPaid - $bulkEmiDiscount));
    $remainingBalance   = round($principalRemaining + $totalPenaltyOutstanding, 2);
} else {
    $remainingBalance   = (float)($paymentSummary['total_due'] ?? 0);
}

// Normalize transactions & group multi-receipt single deposits (same date + same non-empty Cheque/TXN ref)
$normalizedRows = [];
$bookingDiscountApplied = false;
$lastKnownEmiNo = 0;

foreach ($validTransactions as $txn) {
    $amount      = (float)($txn['amount'] ?? 0);
    $paymentType = (string)($txn['payment_type'] ?? '');
    $discount    = (float)($txn['bulk_emi_discount'] ?? 0);

    if ($paymentType === 'booking_money' && $bookingDiscount > 0 && empty($bookingDiscountApplied)) {
        $discount += $bookingDiscount;
        $bookingDiscountApplied = true;
    }

    $emiNo = $txn['emi_no'] ?? null;
    if (!empty($emiNo)) {
        $lastKnownEmiNo = max($lastKnownEmiNo, (int)$emiNo);
    }

    $chequeNo   = trim((string)($txn['cheque_no'] ?? ''));
    $transRef   = trim((string)($txn['transaction_ref'] ?? ''));
    $bankName   = trim((string)(!empty($txn['bank_name']) ? $txn['bank_name'] : ($txn['bank_display_name'] ?? '')));
    $payMethod  = ucfirst(trim((string)($txn['payment_method'] ?? '')));
    $payDateRaw = !empty($txn['payment_date']) ? substr((string)$txn['payment_date'], 0, 10) : '';
    $payDateFmt = $payDateRaw !== '' ? date('d M Y', strtotime($payDateRaw)) : '—';

    // Particulars / Term label
    $term = $txn['installment_label'] ?? strtoupper(str_replace('_', ' ', $paymentType));
    if ($paymentType === 'booking_money') {
        $term = 'Booking';
    } elseif ($paymentType === 'land_share') {
        $term = 'Land Share';
    } elseif ($paymentType === 'registration') {
        $term = 'Registration';
    } elseif ($paymentType === 'development_charge') {
        $term = 'Development Charge';
    } elseif ($paymentType === 'emi' || $paymentType === 'emi_due_settlement') {
        if (!empty($txn['settlement_type']) && $txn['settlement_type'] === 'emi_due_settlement') {
            $term = $txn['installment_label'] ?? $term;
        } else {
            if (!$emiNo) {
                $lastKnownEmiNo++;
                $emiNo = $lastKnownEmiNo;
            }
            $isPartial = (!empty($txn['payment_status']) && strtolower($txn['payment_status']) === 'partially_paid');
            $term = ($isPartial ? 'Advance ' : '') . 'Installment ' . $emiNo;
        }
    } elseif ($paymentType === 'shop_service_charge') {
        $term = 'Shop Service Charge';
    } elseif ($paymentType === 'penalty_settlement') {
        $term = 'Late Penalty Settlement';
    } elseif ($paymentType === 'others') {
        $otherPurpose = !empty($txn['other_purpose']) ? $txn['other_purpose'] : 'Extra Charges';
        $term = 'Others (' . $otherPurpose . ')';
    }

    $isEmiDiscount = !empty($txn['is_emi_discount']) && $txn['is_emi_discount'] == 1;
    $chequeOrRef = $chequeNo !== '' ? $chequeNo : $transRef;

    if ($isEmiDiscount) {
        $discount = $amount;
        $amount = 0.0;
        $modeAndBank = 'Discount Paid';
        $chequeOrRef = '—';
    } else {
        $modeLabel = $bankName !== '' ? 'Bank Transfer' : ($payMethod !== '' ? $payMethod : 'Cash');
        $modeAndBank = $bankName !== '' ? ($bankName . ' · ' . $modeLabel) : $modeLabel;
    }

    $receiptNo = 'REC-' . str_pad((string)($txn['id'] ?? '0'), 6, '0', STR_PAD_LEFT);
    $deductsPrincipal = (strtolower($paymentType) !== 'others' && strtolower($paymentType) !== 'penalty_settlement');

    // Grouping key: same date + same non-empty Cheque/TXN ref (non-discount) => single deposit with multiple allocations
    $groupKey = null;
    if (!$isEmiDiscount && $payDateRaw !== '' && $chequeOrRef !== '' && $chequeOrRef !== '-' && $chequeOrRef !== '—') {
        $groupKey = $payDateRaw . '|' . strtolower($bankName) . '|' . strtolower($chequeOrRef);
    }

    $normalizedRows[] = [
        'group_key'         => $groupKey,
        'pay_date_raw'      => $payDateRaw,
        'pay_date_fmt'      => $payDateFmt,
        'term'              => $term,
        'receipt_no'        => $receiptNo,
        'mode_and_bank'     => $modeAndBank,
        'ref'               => $chequeOrRef !== '' ? $chequeOrRef : '—',
        'discount'          => $discount,
        'amount'            => $amount,
        'is_emi_discount'   => $isEmiDiscount,
        'deducts_principal' => $deductsPrincipal,
    ];
}

// Build ordered ledger entries (preserving first-seen order while grouping matching deposits)
$ledgerEntries = [];
$groupIndexByKey = [];
foreach ($normalizedRows as $r) {
    $gk = $r['group_key'];
    if ($gk !== null && isset($groupIndexByKey[$gk])) {
        $ledgerEntries[$groupIndexByKey[$gk]]['items'][] = $r;
    } else {
        $idx = count($ledgerEntries);
        $ledgerEntries[] = [
            'is_group' => false,
            'items'    => [$r],
        ];
        if ($gk !== null) {
            $groupIndexByKey[$gk] = $idx;
        }
    }
}
foreach ($ledgerEntries as &$entry) {
    $entry['is_group'] = count($entry['items']) > 1;
}
unset($entry);

$paymentDateCount = count($uniquePayDates);
$receiptTotalCount = count($validTransactions);
$paymentDatesDisplay = $paymentDateCount > 0
    ? ($paymentDateCount . ' Date' . ($paymentDateCount === 1 ? '' : 's') . ' · ' . $receiptTotalCount . ' Receipt' . ($receiptTotalCount === 1 ? '' : 's'))
    : '0 Dates';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Statement - <?= htmlspecialchars($clientDetails['client_name'] ?? 'N/A') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @page { size: A4 landscape; margin: 8mm; }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #F3F5F4;
            color: #14251C;
            line-height: 1.35;
            font-size: 12px;
            padding: 16px;
        }

        /* ─── Action Buttons (Screen Only) ─── */
        .action-buttons {
            max-width: 1440px;
            margin: 0 auto 14px auto;
            position: sticky;
            top: 8px;
            z-index: 1000;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            background: #FFFFFF;
            border: 1px solid #AFB9B4;
            border-radius: 6px;
            flex-wrap: wrap;
        }

        .period-filter { display: flex; align-items: center; gap: 8px; margin-right: auto; flex-wrap: wrap; }
        .period-filter select, .period-filter input[type="date"] {
            padding: 6px 10px;
            border: 1px solid #AFB9B4;
            border-radius: 5px;
            font-size: 12px;
            font-family: inherit;
            color: #14251C;
            background: #FFFFFF;
        }
        .period-filter button {
            padding: 6px 14px;
            background: #146B48;
            color: #FFFFFF;
            border: none;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
        }
        .period-filter button:hover { background: #0F5338; }
        #customDateRange { display: flex; gap: 6px; align-items: center; }

        .btn {
            padding: 7px 14px;
            font-size: 12px;
            font-weight: 600;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            white-space: nowrap;
            font-family: inherit;
        }
        .btn:hover { opacity: 0.92; }
        .btn-print { background: #14251C; color: #FFFFFF; }
        .btn-back { background: #FFFFFF; color: #14251C; border: 1px solid #AFB9B4; }

        /* ─── Statement Sheet ─── */
        .statement-container {
            max-width: 1440px;
            margin: 0 auto;
            background: #FFFFFF;
            padding: 24px 32px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            box-shadow: 0 2px 12px rgba(20, 37, 28, 0.06);
        }

        /* ─── Statement Header ─── */
        .statement-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
        }

        .company-block {
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .company-logo-img {
            height: 52px;
            width: auto;
            max-width: 140px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .company-lines {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .company-name {
            font-weight: 700;
            font-size: 22px;
            line-height: 27px;
            color: <?= ($isOlympusProject || $isNatureProject) ? $stmtThemeDark : '#14251C' ?>;
            text-transform: uppercase;
        }

        .company-sub {
            font-weight: 400;
            font-size: 12px;
            line-height: 15px;
            color: #14251C;
        }

        .doc-details {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 6px;
            text-align: right;
            flex-shrink: 0;
        }

        .doc-title {
            font-weight: 700;
            font-size: 22px;
            line-height: 27px;
            color: <?= ($isOlympusProject || $isNatureProject) ? $stmtThemeDark : '#14251C' ?>;
            text-transform: uppercase;
        }

        .doc-no {
            font-weight: 600;
            font-size: 12px;
            line-height: 15px;
            color: #14251C;
        }

        .doc-meta {
            font-weight: 400;
            font-size: 12px;
            line-height: 15px;
            color: #506058;
        }

        .header-rule {
            width: 100%;
            height: 1px;
            background: <?= ($isOlympusProject || $isNatureProject) ? $stmtThemePrimary : '#222C26' ?>;
        }

        /* ─── Client & Property Details ─── */
        .info-panel {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border: 1px solid #AFB9B4;
            width: 100%;
        }

        .info-col {
            padding: 14px 18px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .info-col-title {
            font-weight: 700;
            font-size: 13px;
            line-height: 16px;
            color: #14251C;
            text-transform: uppercase;
        }

        .info-col-rule {
            width: 100%;
            height: 1px;
            background: #AFB9B4;
        }

        .info-line {
            display: flex;
            align-items: center;
            gap: 8px;
            min-height: 16px;
        }

        .info-label {
            width: 74px;
            flex-shrink: 0;
            font-weight: 500;
            font-size: 12px;
            line-height: 15px;
            color: #506058;
        }

        .info-val {
            font-weight: 600;
            font-size: 13px;
            line-height: 16px;
            color: #14251C;
        }

        /* ─── Account Summary Strip ─── */
        .summary-strip {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            border: 1px solid #AFB9B4;
            width: 100%;
        }

        .summary-cell {
            min-height: 72px;
            padding: 10px 12px;
            background: #FFFFFF;
            border-right: 1px solid #AFB9B4;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 6px;
            text-align: center;
        }

        .summary-cell:last-child { border-right: none; }

        .summary-cell .s-label {
            font-weight: 600;
            font-size: 11px;
            line-height: 13px;
            color: #506058;
            text-transform: uppercase;
        }

        .summary-cell .s-value {
            font-weight: 700;
            font-size: 18px;
            line-height: 22px;
            color: #14251C;
            font-variant-numeric: tabular-nums;
        }

        .summary-cell .s-sub {
            font-size: 10px;
            line-height: 13px;
            color: #506058;
            font-weight: 500;
        }

        /* ─── Payment Ledger Table ─── */
        .table-wrapper {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #AFB9B4;
        }

        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            color: #14251C;
        }

        .ledger-table thead th {
            height: 42px;
            background: #DCE5F3;
            color: #14251C;
            font-weight: 600;
            font-size: 11px;
            line-height: 13px;
            padding: 4px 10px;
            text-align: left;
            border-bottom: 1px solid #AFB9B4;
            white-space: nowrap;
        }

        .ledger-table thead th.center,
        .ledger-table tbody td.center { text-align: center; }

        .ledger-table thead th.num,
        .ledger-table tbody td.num,
        .ledger-table tfoot td.num {
            text-align: right;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .ledger-table tbody tr.std-row td {
            height: 40px;
            background: #FFFFFF;
            padding: 4px 10px;
            border-bottom: 1px solid #AFB9B4;
            vertical-align: middle;
        }

        .ledger-table tbody tr.discount-row td {
            background: #FEF9E7;
        }

        .ledger-table tbody tr.group-parent-row td {
            height: 48px;
            background: #E5F4EB;
            padding: 4px 10px;
            vertical-align: middle;
            border-bottom: none;
        }

        .ledger-table tbody tr.alloc-label-row td {
            background: #F6F8F7;
            padding: 9px 10px 5px 158px;
            border-bottom: none;
        }

        .alloc-badge {
            font-weight: 700;
            font-size: 10px;
            line-height: 12px;
            color: #146B48;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            margin-right: 10px;
        }

        .alloc-hint {
            font-weight: 400;
            font-size: 11px;
            line-height: 13px;
            color: #506058;
        }

        .ledger-table tbody tr.alloc-child-row td {
            height: 34px;
            background: #F6F8F7;
            padding: 4px 10px;
            color: #506058;
            border-bottom: 1px solid #AFB9B4;
            vertical-align: middle;
        }

        .ledger-table tbody tr td.paid-col { font-weight: 600; color: #14251C; }
        .ledger-table tbody tr.alloc-child-row td.alloc-col,
        .ledger-table tbody tr td.alloc-col { font-weight: 600; color: #146B48; }
        .ledger-table tbody tr td.bal-col { font-weight: 400; color: #506058; }
        .ledger-table tbody tr.group-parent-row td.bal-col { font-weight: 600; color: #14251C; }

        .ledger-table tfoot tr.total-row td {
            height: 40px;
            background: #DCE5F3;
            color: #14251C;
            font-weight: 600;
            font-size: 12px;
            padding: 4px 10px;
            vertical-align: middle;
        }

        .tag-pill {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            color: #FFFFFF;
            white-space: nowrap;
        }

        /* ─── Statement Notes ─── */
        .statement-notes {
            background: #F3F5F4;
            border: 1px solid #AFB9B4;
            padding: 10px 12px;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .note-primary {
            font-weight: 500;
            font-size: 11px;
            line-height: 13px;
            color: #506058;
        }

        .note-secondary {
            font-weight: 400;
            font-size: 11px;
            line-height: 13px;
            color: #506058;
        }

        @media screen and (max-width: 900px) {
            body { padding: 8px; }
            .statement-container { padding: 16px; }
            .statement-header { flex-direction: column; }
            .doc-details { align-items: flex-start; text-align: left; }
            .info-panel { grid-template-columns: 1fr; }
            .info-col:first-child { border-right: none; border-bottom: 1px solid #AFB9B4; }
            .summary-strip { grid-template-columns: repeat(2, 1fr); }
            .summary-cell { border-bottom: 1px solid #AFB9B4; }
            .ledger-table tbody tr.alloc-label-row td { padding-left: 16px; }
        }

        @media print {
            .no-print { display: none !important; }
            html, body {
                margin: 0 !important;
                padding: 0 !important;
                background: #FFFFFF !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .statement-container {
                max-width: 100% !important;
                padding: 0 !important;
                box-shadow: none !important;
                gap: 8px !important;
            }
            .statement-header {
                flex-direction: row !important;
                justify-content: space-between !important;
                align-items: flex-start !important;
            }
            .doc-details {
                align-items: flex-end !important;
                text-align: right !important;
                gap: 3px !important;
            }
            .company-lines { gap: 2px !important; }
            .company-name, .doc-title { font-size: 15pt !important; line-height: 18pt !important; }
            .company-sub, .doc-no, .doc-meta { font-size: 8.5pt !important; line-height: 11pt !important; }
            .info-panel {
                grid-template-columns: 1fr 1fr !important;
                page-break-inside: avoid;
            }
            .info-col { padding: 8px 12px !important; gap: 4px !important; }
            .info-col:first-child { border-bottom: none !important; }
            .info-col-title { font-size: 9pt !important; line-height: 12pt !important; }
            .info-label, .info-val { font-size: 8.5pt !important; line-height: 11pt !important; }
            .summary-strip {
                grid-template-columns: repeat(5, 1fr) !important;
                page-break-inside: avoid;
            }
            .summary-cell {
                min-height: 46px !important;
                padding: 6px 8px !important;
                gap: 3px !important;
                border-bottom: none !important;
            }
            .summary-cell .s-label { font-size: 7.5pt !important; line-height: 10pt !important; }
            .summary-cell .s-value { font-size: 11pt !important; line-height: 14pt !important; }
            .table-wrapper { overflow: visible !important; }
            .ledger-table { font-size: 8.5pt !important; }
            .ledger-table thead { display: table-header-group; }
            .ledger-table tr { page-break-inside: avoid; }
            .ledger-table thead th { height: 28px !important; padding: 3px 6px !important; font-size: 8pt !important; }
            .ledger-table tbody tr.std-row td,
            .ledger-table tfoot tr.total-row td { height: 26px !important; padding: 3px 6px !important; }
            .ledger-table tbody tr.group-parent-row td { height: 30px !important; padding: 3px 6px !important; }
            .ledger-table tbody tr.alloc-label-row td { padding: 5px 6px 3px 96px !important; }
            .ledger-table tbody tr.alloc-child-row td { height: 22px !important; padding: 2px 6px !important; }
            .statement-notes { padding: 6px 10px !important; gap: 3px !important; page-break-inside: avoid; }
            .note-primary, .note-secondary { font-size: 8pt !important; line-height: 11pt !important; }
        }
    </style>
</head>

<body>

    <!-- Action Bar (Screen Only) -->
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
                <input type="date" id="fromDate" value="<?= htmlspecialchars($fromDate ?? '') ?>">
                <span>to</span>
                <input type="date" id="toDate" value="<?= htmlspecialchars($toDate ?? '') ?>">
            </div>
            <button type="button" onclick="applyFilter()">Apply</button>
        </div>
        <button type="button" onclick="window.print()" class="btn btn-print">Print Statement</button>
        <a href="<?= $GLOBALS['base_url'] ?>/clients-cash-in-history?booking_id=<?= (int)$clientDetails['booking_id'] ?>" class="btn btn-back">Back to History</a>
    </div>

    <div class="statement-container" id="statement-content">

        <!-- 1. Statement Header -->
        <div class="statement-header">
            <div class="company-block">
                <?php if (!empty($stmtLogoSrc)): ?>
                    <img src="<?= $stmtLogoSrc ?>" alt="<?= htmlspecialchars($stmtCompanyTitle) ?>" class="company-logo-img">
                <?php endif; ?>
                <div class="company-lines">
                    <div class="company-name"><?= htmlspecialchars($stmtCompanyTitle) ?></div>
                    <?php
                    $addrLines = [trim((string)$stmtCompanyAddress)];
                    if (!$isOlympusProject && !$isNatureProject && stripos($stmtCompanyAddress, ', Gulshan Circle-2, Dhaka') !== false) {
                        $addrLines = [
                            trim(str_ireplace(', Gulshan Circle-2, Dhaka', '', $stmtCompanyAddress)),
                            'Gulshan Circle-2, Dhaka',
                        ];
                    }
                    foreach ($addrLines as $addrLine):
                        if ($addrLine === '') continue;
                    ?>
                    <div class="company-sub"><?= htmlspecialchars($addrLine) ?></div>
                    <?php endforeach; ?>
                    <div class="company-sub">
                        Phone <?= htmlspecialchars($stmtCompanyPhone) ?> &nbsp;&middot;&nbsp; <?= htmlspecialchars($stmtCompanyEmail) ?><?= !empty($stmtCompanyWebsite) ? ' &nbsp;&middot;&nbsp; ' . htmlspecialchars($stmtCompanyWebsite) : '' ?>
                    </div>
                </div>
            </div>
            <div class="doc-details">
                <div class="doc-title">ACCOUNT STATEMENT</div>
                <div class="doc-no">Statement No. STMT-<?= str_pad((string)$clientDetails['booking_id'], 6, '0', STR_PAD_LEFT) ?></div>
                <div class="doc-meta">Generated <?= date('d M Y') ?> &nbsp;&middot;&nbsp; <?= htmlspecialchars($periodText ?? 'All transactions') ?></div>
            </div>
        </div>

        <div class="header-rule"></div>

        <!-- 2. Client & Property Details -->
        <div class="info-panel">
            <div class="info-col">
                <div class="info-col-title">CLIENT INFORMATION</div>
                <div class="info-col-rule"></div>
                <div class="info-line">
                    <span class="info-label">Name</span>
                    <span class="info-val"><?= htmlspecialchars($clientDetails['client_name'] ?? 'N/A') ?></span>
                </div>
                <div class="info-line">
                    <span class="info-label">Phone</span>
                    <span class="info-val"><?= htmlspecialchars($clientDetails['phone'] ?? 'N/A') ?></span>
                </div>
                <div class="info-line">
                    <span class="info-label">Email</span>
                    <span class="info-val"><?= htmlspecialchars($clientDetails['email'] ?? 'N/A') ?></span>
                </div>
            </div>
            <div class="info-col">
                <div class="info-col-title">PROPERTY DETAILS</div>
                <div class="info-col-rule"></div>
                <div class="info-line">
                    <span class="info-label">Project</span>
                    <span class="info-val"><?= htmlspecialchars($clientDetails['project_name'] ?? 'N/A') ?></span>
                </div>
                <div class="info-line">
                    <span class="info-label">Unit</span>
                    <span class="info-val"><?= htmlspecialchars($clientDetails['unit_name'] ?? 'N/A') ?></span>
                </div>
                <div class="info-line">
                    <span class="info-label">Floor</span>
                    <span class="info-val"><?= htmlspecialchars($clientDetails['flat_no'] ?? 'N/A') ?></span>
                </div>
            </div>
        </div>

        <!-- 3. Account Summary -->
        <div class="summary-strip">
            <div class="summary-cell">
                <div class="s-label">TOTAL PRICE (APPROX.)</div>
                <div class="s-value">BDT <?= number_format($totalPrice, 2) ?></div>
            </div>
            <div class="summary-cell">
                <div class="s-label">TOTAL PAID</div>
                <div class="s-value">BDT <?= number_format($actualPaid, 2) ?></div>
            </div>
            <div class="summary-cell">
                <div class="s-label">TOTAL DISCOUNT</div>
                <div class="s-value">BDT <?= number_format($totalDiscounts, 2) ?></div>
                <?php if ($bookingDiscount > 0 && $bulkEmiDiscount > 0): ?>
                    <div class="s-sub">Booking: <?= number_format($bookingDiscount, 0) ?> &middot; EMI: <?= number_format($bulkEmiDiscount, 0) ?></div>
                <?php elseif ($bookingDiscount > 0): ?>
                    <div class="s-sub">Booking: <?= number_format($bookingDiscount, 0) ?></div>
                <?php elseif ($bulkEmiDiscount > 0): ?>
                    <div class="s-sub">EMI Discount: <?= number_format($bulkEmiDiscount, 0) ?></div>
                <?php endif; ?>
            </div>
            <div class="summary-cell">
                <div class="s-label">PAYMENT DATES</div>
                <div class="s-value"><?= htmlspecialchars($paymentDatesDisplay) ?></div>
            </div>
            <div class="summary-cell">
                <div class="s-label">OUTSTANDING (APPROX.)</div>
                <div class="s-value">
                    <?= $remainingBalance <= 0 ? 'FULLY PAID' : ('BDT ' . number_format($remainingBalance, 2)) ?>
                </div>
                <?php if ($totalPenaltyOutstanding > 0): ?>
                    <div class="s-sub" style="color:#B45309; font-weight:600;">Incl. Penalty Due: BDT <?= number_format($totalPenaltyOutstanding, 2) ?></div>
                <?php endif; ?>
                <?php if ($totalPenaltyPaidStmt > 0): ?>
                    <div class="s-sub" style="color:#146B48;">Penalty Paid: BDT <?= number_format($totalPenaltyPaidStmt, 2) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 4. Payment Ledger -->
        <div class="table-wrapper">
            <table class="ledger-table">
                <thead>
                    <tr>
                        <th class="center" style="width:42px;">SL</th>
                        <th style="width:106px;">Date</th>
                        <th style="min-width:260px;">Particulars / Term</th>
                        <th style="width:128px;">Receipt No.</th>
                        <th style="width:191px;">Payment Mode &amp; Bank</th>
                        <th style="width:115px;">Cheque / TXN ID</th>
                        <th class="num" style="width:80px;">Discount</th>
                        <th class="num" style="width:125px;">Paid amount</th>
                        <th class="num" style="width:125px;">Allocated</th>
                        <th class="num" style="width:135px;">Remaining balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($ledgerEntries)): ?>
                        <?php
                        $sl = 0;
                        $runningBalance = $totalPrice;
                        $totalDiscountCol = 0.0;
                        $totalPaidCol = 0.0;

                        foreach ($ledgerEntries as $entry):
                            $sl++;
                            if ($entry['is_group']):
                                $items = $entry['items'];
                                $first = $items[0];
                                $allocCount = count($items);
                                $grpDiscount = 0.0;
                                $grpPaid = 0.0;

                                foreach ($items as $it) {
                                    $grpDiscount += $it['discount'];
                                    $grpPaid     += $it['amount'];
                                    if ($it['deducts_principal']) {
                                        $runningBalance -= ($it['amount'] + $it['discount']);
                                    }
                                }
                                if ($runningBalance < 0) $runningBalance = 0;
                                $totalDiscountCol += $grpDiscount;
                                $totalPaidCol     += $grpPaid;
                        ?>
                        <tr class="group-parent-row">
                            <td class="center"><?= $sl ?></td>
                            <td style="white-space:nowrap;"><?= htmlspecialchars($first['pay_date_fmt']) ?></td>
                            <td><strong>Single payment &middot; <?= $allocCount ?> allocations</strong></td>
                            <td><?= $allocCount ?> receipts</td>
                            <td><?= htmlspecialchars($first['mode_and_bank']) ?></td>
                            <td><?= htmlspecialchars($first['ref']) ?></td>
                            <td class="num"><?= $grpDiscount > 0 ? number_format($grpDiscount, 2) : '—' ?></td>
                            <td class="num paid-col"><?= number_format($grpPaid, 2) ?></td>
                            <td class="num"></td>
                            <td class="num bal-col"><?= number_format($runningBalance, 2) ?></td>
                        </tr>
                        <tr class="alloc-label-row">
                            <td colspan="10">
                                <span class="alloc-badge">PAYMENT ALLOCATION</span>
                                <span class="alloc-hint">Receipt-level allocation breakdown</span>
                            </td>
                        </tr>
                        <?php foreach ($items as $it): ?>
                        <tr class="alloc-child-row">
                            <td class="center"></td>
                            <td></td>
                            <td>&#8627;&nbsp; <?= htmlspecialchars($it['term']) ?></td>
                            <td><?= htmlspecialchars($it['receipt_no']) ?></td>
                            <td></td>
                            <td></td>
                            <td class="num"><?= $it['discount'] > 0 ? number_format($it['discount'], 2) : '' ?></td>
                            <td class="num"></td>
                            <td class="num alloc-col"><?= number_format($it['amount'], 2) ?></td>
                            <td class="num"></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php
                            else:
                                $it = $entry['items'][0];
                                $totalDiscountCol += $it['discount'];
                                $totalPaidCol     += $it['amount'];
                                if ($it['deducts_principal']) {
                                    $runningBalance -= ($it['amount'] + $it['discount']);
                                }
                                if ($runningBalance < 0) $runningBalance = 0;
                        ?>
                        <tr class="std-row<?= $it['is_emi_discount'] ? ' discount-row' : '' ?>">
                            <td class="center"><?= $sl ?></td>
                            <td style="white-space:nowrap;"><?= htmlspecialchars($it['pay_date_fmt']) ?></td>
                            <td><?= htmlspecialchars($it['term']) ?></td>
                            <td><?= htmlspecialchars($it['receipt_no']) ?></td>
                            <td>
                                <?php if ($it['is_emi_discount']): ?>
                                    <span class="tag-pill" style="background:#D97706;">Discount Paid</span>
                                <?php else: ?>
                                    <?= htmlspecialchars($it['mode_and_bank']) ?>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($it['ref']) ?></td>
                            <td class="num"><?= $it['discount'] > 0 ? number_format($it['discount'], 2) : '—' ?></td>
                            <td class="num paid-col"><?= number_format($it['amount'], 2) ?></td>
                            <td class="num"></td>
                            <td class="num bal-col"><?= number_format($runningBalance, 2) ?></td>
                        </tr>
                        <?php
                            endif;
                        endforeach;
                        ?>

                        <?php if ($bookingDiscount > 0 && empty($bookingDiscountApplied)): ?>
                            <?php
                            $runningBalance -= $bookingDiscount;
                            if ($runningBalance < 0) $runningBalance = 0;
                            $totalDiscountCol += $bookingDiscount;
                            ?>
                        <tr class="std-row discount-row">
                            <td class="center"><?= ++$sl ?></td>
                            <td>—</td>
                            <td><strong>Booking Discount</strong></td>
                            <td>—</td>
                            <td><span class="tag-pill" style="background:#146B48;">Booking Discount</span></td>
                            <td>—</td>
                            <td class="num"><strong><?= number_format($bookingDiscount, 2) ?></strong></td>
                            <td class="num paid-col">0.00</td>
                            <td class="num"></td>
                            <td class="num bal-col"><?= number_format($runningBalance, 2) ?></td>
                        </tr>
                        <?php endif; ?>

                        <?php if ($accruedPenalty > 0): ?>
                            <?php $runningBalance = round(max(0, $runningBalance) + $accruedPenalty, 2); ?>
                        <tr class="std-row discount-row">
                            <td class="center"><?= ++$sl ?></td>
                            <td style="white-space:nowrap;"><?= date('d M Y') ?></td>
                            <td><strong>Overdue EMI Late Penalty (5% Auto-Accrued)</strong></td>
                            <td>—</td>
                            <td><span class="tag-pill" style="background:#DC2626;">Auto Penalty</span></td>
                            <td>—</td>
                            <td class="num">—</td>
                            <td class="num paid-col" style="color:#B45309;">+<?= number_format($accruedPenalty, 2) ?></td>
                            <td class="num"></td>
                            <td class="num bal-col"><?= number_format($runningBalance, 2) ?></td>
                        </tr>
                        <?php endif; ?>
                    <?php else: ?>
                        <tr class="std-row">
                            <td colspan="10" class="center" style="padding:24px; color:#506058;">
                                No verified transactions found for the selected period.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($ledgerEntries)): ?>
                <tfoot>
                    <tr class="total-row">
                        <td></td>
                        <td></td>
                        <td>TOTAL</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="num"><?= number_format($totalDiscountCol, 2) ?></td>
                        <td class="num"><?= number_format($totalPaidCol, 2) ?></td>
                        <td class="num"></td>
                        <td class="num"><?= number_format(max(0, $runningBalance), 2) ?></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>

        <!-- 5. Statement Notes -->
        <div class="statement-notes">
            <div class="note-primary">
                Includes verified transactions. Amounts are in BDT. Paid amounts are counted once per payment.
                <?php if ($totalDiscounts > 0): ?>
                    <?php if ($bookingDiscount > 0): ?> Booking discount of BDT <?= number_format($bookingDiscount, 2) ?> has been applied.<?php endif; ?>
                    <?php if ($bulkEmiDiscount > 0): ?> EMI discount of BDT <?= number_format($bulkEmiDiscount, 2) ?> has been applied.<?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="note-secondary">
                Statement generated on <?= date('d M Y \a\t h:i A') ?> &middot; <?= htmlspecialchars($stmtCompanyTitle) ?>. Rejected and pending payments are excluded.
            </div>
        </div>

    </div>

    <script>
        function filterByPeriod() {
            const period = document.getElementById('periodSelect').value;
            document.getElementById('customDateRange').style.display = period === 'custom' ? 'flex' : 'none';
        }

        function applyFilter() {
            const period = document.getElementById('periodSelect').value;
            const bookingId = <?= (int)$clientDetails['booking_id'] ?>;
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
