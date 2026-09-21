<?php
/**
 * print-po-sheet.php
 * Reusable professional printable sheet for Purchase Order & CST Approval.
 * Included by print-purchase-order.php (standalone) and show-purchase-order.php (inline print).
 */
$base_url = $GLOBALS['base_url'] ?? '';
$po = $po ?? [];
$items = $items ?? [];
$prDetails = $prDetails ?? [];
$selectedVendor = $selectedVendor ?? [];
$cstInfo = $cstInfo ?? [];
$cstMatrix = $cstMatrix ?? [];
$quotationsList = $quotationsList ?? [];
$negotiationNotes = $negotiationNotes ?? [];
$siteSettings = $siteSettings ?? [];
$vendorSelectionData = $vendorSelectionData ?? null;

$creatorRole = (int)($po['creator_role_id'] ?? 0);
$phNeeded = in_array($creatorRole, [23, 31]);
$phApproved = !empty($po['purchase_head_approved_by_id']);
$qcApproved = !empty($po['qc_approved_by_id']);
$opApproved = !empty($po['op_head_approved_by_id']);
$fullyApproved = ($phNeeded ? $phApproved : true) && $qcApproved && $opApproved;

$poTotalValue = $poTotalValue ?? 0;
if ($poTotalValue <= 0 && !empty($items)) {
    foreach ($items as $itm) {
        $poTotalValue += (float)($itm['quantity'] * $itm['unit_price']);
    }
}

// Logo resolution
$logoUrl = '';
if (!empty($siteSettings['white_logo'])) {
    $logoUrl = $base_url . '/public/uploads/settings/' . $siteSettings['white_logo'];
} elseif (!empty($siteSettings['logo'])) {
    $logoUrl = $base_url . '/public/uploads/settings/' . $siteSettings['logo'];
} else {
    $logoUrl = $base_url . '/public/assets/images/logo.png';
}

$companyName = !empty($siteSettings['company_name']) ? $siteSettings['company_name'] : 'TROPICAL HOMES LIMITED';
$corporateAddress = !empty($siteSettings['address']) ? $siteSettings['address'] : '23/2, SEL HUQ SKY PARK, LEVEL-4, SHYMOLI, DHAKA.';
$companyPhone = !empty($siteSettings['phone']) ? $siteSettings['phone'] : '+880 1740 362 000';
$companyEmail = !empty($siteSettings['email']) ? $siteSettings['email'] : 'info@tropicalhomesltd.com';
$companyWeb   = !empty($siteSettings['website_url']) ? $siteSettings['website_url'] : 'www.tropicalhomesltd.com';
?>

<style>
    /* ── Scope CSS inside .po-print-sheet-root ─────────────────── */
    .po-print-sheet-root {
        font-family: 'Inter', Arial, Helvetica, sans-serif !important;
        font-size: 11px !important;
        color: #0f172a !important;
        background: #fff !important;
        line-height: 1.45 !important;
        width: 100% !important;
        max-width: 960px !important;
        margin: 0 auto !important;
        box-sizing: border-box !important;
    }
    .po-print-sheet-root * {
        box-sizing: border-box !important;
    }

    /* Header */
    .po-print-sheet-root .po-doc-header {
        display: flex !important;
        justify-content: space-between !important;
        align-items: flex-start !important;
        border-bottom: 2px solid #0f172a !important;
        padding-bottom: 12px !important;
        margin-bottom: 14px !important;
    }
    .po-print-sheet-root .po-company-logo {
        max-height: 56px !important;
        max-width: 260px !important;
        object-fit: contain !important;
    }
    .po-print-sheet-root .po-company-name {
        font-size: 16px !important;
        font-weight: 900 !important;
        color: #0f172a !important;
        letter-spacing: 0.5px !important;
        text-transform: uppercase !important;
        margin-bottom: 2px !important;
    }
    .po-print-sheet-root .po-company-sub {
        font-size: 9.5px !important;
        color: #475569 !important;
        line-height: 1.35 !important;
    }
    .po-print-sheet-root .po-title-badge {
        display: inline-block !important;
        background: #0f172a !important;
        color: #fff !important;
        padding: 4px 12px !important;
        font-size: 12px !important;
        font-weight: 800 !important;
        letter-spacing: 1px !important;
        text-transform: uppercase !important;
        border-radius: 4px !important;
    }
    .po-print-sheet-root .po-ref-no {
        font-size: 16px !important;
        font-weight: 800 !important;
        color: #0369a1 !important;
        margin-top: 4px !important;
        font-family: monospace !important;
    }

    /* Meta Grid */
    .po-print-sheet-root .po-meta-grid {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 12px !important;
        margin-bottom: 12px !important;
    }
    .po-print-sheet-root .po-meta-card {
        border: 1px solid #cbd5e1 !important;
        border-radius: 5px !important;
        padding: 8px 12px !important;
        background: #f8fafc !important;
    }
    .po-print-sheet-root .po-meta-card-title {
        font-size: 9.5px !important;
        font-weight: 800 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.8px !important;
        color: #475569 !important;
        border-bottom: 1px solid #e2e8f0 !important;
        padding-bottom: 4px !important;
        margin-bottom: 6px !important;
        display: flex !important;
        justify-content: space-between !important;
    }
    .po-print-sheet-root .po-meta-row {
        display: flex !important;
        justify-content: space-between !important;
        font-size: 10.5px !important;
        line-height: 1.45 !important;
        margin-bottom: 2px !important;
    }
    .po-print-sheet-root .po-meta-row:last-child {
        margin-bottom: 0 !important;
    }
    .po-print-sheet-root .po-meta-label {
        color: #64748b !important;
        font-weight: 600 !important;
    }
    .po-print-sheet-root .po-meta-val {
        color: #0f172a !important;
        font-weight: 700 !important;
        text-align: right !important;
        max-width: 65% !important;
    }

    /* Vendor Highlight */
    .po-print-sheet-root .po-vendor-box {
        border: 1.5px solid #059669 !important;
        background: #f0fdf4 !important;
        border-radius: 5px !important;
        padding: 8px 12px !important;
        margin-bottom: 12px !important;
    }
    .po-print-sheet-root .po-vendor-badge {
        background: #059669 !important;
        color: #fff !important;
        padding: 2px 7px !important;
        font-size: 9px !important;
        font-weight: 800 !important;
        border-radius: 3px !important;
        text-transform: uppercase !important;
    }
    .po-print-sheet-root .po-vendor-grid {
        display: grid !important;
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 8px !important;
        font-size: 10px !important;
        margin-top: 6px !important;
    }
    .po-print-sheet-root .po-vg-label {
        font-size: 8.5px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        color: #047857 !important;
    }
    .po-print-sheet-root .po-vg-val {
        font-weight: 700 !important;
        color: #064e3b !important;
    }

    /* Section Title */
    .po-print-sheet-root .po-sec-title {
        font-size: 9.5px !important;
        font-weight: 800 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.8px !important;
        color: #0f172a !important;
        background: #e2e8f0 !important;
        padding: 4px 8px !important;
        border-left: 4px solid #0f172a !important;
        margin: 12px 0 6px 0 !important;
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
    }

    /* Tables */
    .po-print-sheet-root .po-print-table {
        width: 100% !important;
        border-collapse: collapse !important;
        font-size: 10px !important;
        margin-bottom: 6px !important;
    }
    .po-print-sheet-root .po-print-table th, 
    .po-print-sheet-root .po-print-table td {
        border: 1px solid #cbd5e1 !important;
        padding: 4px 7px !important;
        vertical-align: middle !important;
    }
    .po-print-sheet-root .po-print-table th {
        background: #1e293b !important;
        color: #fff !important;
        font-weight: 700 !important;
        font-size: 9.5px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.3px !important;
    }
    .po-print-sheet-root .po-print-table tbody tr:nth-child(even) {
        background: #f8fafc !important;
    }

    .po-print-sheet-root .po-words-box {
        background: #f8fafc !important;
        border: 1px dashed #94a3b8 !important;
        padding: 5px 10px !important;
        font-size: 10px !important;
        margin-bottom: 12px !important;
        border-radius: 4px !important;
    }

    /* Badges */
    .po-print-sheet-root .po-badge-status {
        display: inline-block !important;
        padding: 1px 6px !important;
        border-radius: 3px !important;
        font-weight: 800 !important;
        font-size: 8.5px !important;
        text-transform: uppercase !important;
    }
    .po-print-sheet-root .po-badge-approved { background: #dcfce7 !important; color: #166534 !important; border: 1px solid #86efac !important; }
    .po-print-sheet-root .po-badge-pending  { background: #fef3c7 !important; color: #92400e !important; border: 1px solid #fcd34d !important; }
    .po-print-sheet-root .po-badge-alt      { background: #f1f5f9 !important; color: #64748b !important; }

    /* Signatures */
    .po-print-sheet-root .po-sig-container {
        display: grid !important;
        grid-template-columns: repeat(5, 1fr) !important;
        gap: 10px !important;
        margin-top: 32px !important;
        margin-bottom: 12px !important;
        page-break-inside: avoid !important;
    }
    .po-print-sheet-root .po-sig-block {
        text-align: center !important;
    }
    .po-print-sheet-root .po-sig-line {
        border-top: 1px solid #475569 !important;
        padding-top: 3px !important;
        margin-top: 24px !important;
        font-size: 9.5px !important;
        font-weight: 700 !important;
        color: #0f172a !important;
    }
    .po-print-sheet-root .po-sig-sub {
        font-size: 8.5px !important;
        color: #64748b !important;
    }

    /* Footer */
    .po-print-sheet-root .po-doc-footer {
        border-top: 1px solid #cbd5e1 !important;
        padding-top: 5px !important;
        margin-top: 8px !important;
        display: flex !important;
        justify-content: space-between !important;
        font-size: 8.5px !important;
        color: #64748b !important;
    }
</style>

<div class="po-print-sheet-root">

    <!-- 1. Header & Letterhead -->
    <div class="po-doc-header">
        <div style="display:flex;align-items:center;">
            <?php if (!empty($logoUrl)): ?>
                <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Company Logo" class="po-company-logo">
            <?php endif; ?>
        </div>
        <div style="text-align:right">
            <div class="po-title-badge">PURCHASE ORDER & CST APPROVAL SHEET</div>
            <div class="po-ref-no">PO-<?= htmlspecialchars($po['id']) ?></div>
            <div style="font-size:9.5px;color:#64748b;font-weight:600">Against Requisition: PR #<?= htmlspecialchars($po['request_id'] ?? '') ?></div>
            <div style="margin-top:4px">
                <span class="po-badge-status <?= $fullyApproved ? 'po-badge-approved' : 'po-badge-pending' ?>">
                    Status: <?= htmlspecialchars($po['status'] ?? 'Issued') ?>
                </span>
            </div>
        </div>
    </div>

    <!-- 2. PO Meta & Source Purchase Request Grid -->
    <div class="po-meta-grid">
        <!-- PO Meta Card -->
        <div class="po-meta-card">
            <div class="po-meta-card-title">
                <span>Purchase Order Details</span>
                <span style="font-family:monospace;color:#0369a1">#PO-<?= htmlspecialchars($po['id']) ?></span>
            </div>
            <div class="po-meta-row">
                <span class="po-meta-label">Project:</span>
                <span class="po-meta-val"><?= htmlspecialchars($po['project_name'] ?? '—') ?></span>
            </div>
            <div class="po-meta-row">
                <span class="po-meta-label">PO Date:</span>
                <span class="po-meta-val"><?= !empty($po['order_date']) ? date('d M Y', strtotime($po['order_date'])) : date('d M Y', strtotime($po['created_at'])) ?></span>
            </div>
            <div class="po-meta-row">
                <span class="po-meta-label">PO Prepared By:</span>
                <span class="po-meta-val"><?= htmlspecialchars($po['created_by_name'] ?? '—') ?></span>
            </div>
            <div class="po-meta-row">
                <span class="po-meta-label">PO Status:</span>
                <span class="po-meta-val"><?= htmlspecialchars($po['status'] ?? '—') ?></span>
            </div>
        </div>

        <!-- PR Meta Card -->
        <div class="po-meta-card">
            <div class="po-meta-card-title">
                <span>Source Purchase Request (Requisition)</span>
                <span style="font-family:monospace;color:#d97706">PR #<?= htmlspecialchars($po['request_id'] ?? '') ?></span>
            </div>
            <div class="po-meta-row">
                <span class="po-meta-label">PR Order No:</span>
                <span class="po-meta-val" style="font-family:monospace">PR-<?= htmlspecialchars($po['request_id'] ?? '') ?> (ID: #<?= htmlspecialchars($po['request_id'] ?? '') ?>)</span>
            </div>
            <div class="po-meta-row">
                <span class="po-meta-label">Requested By:</span>
                <span class="po-meta-val"><?= htmlspecialchars($prDetails['requester_name'] ?? '—') ?></span>
            </div>
            <div class="po-meta-row">
                <span class="po-meta-label">Required Date:</span>
                <span class="po-meta-val"><?= !empty($prDetails['required_date']) ? date('d M Y', strtotime($prDetails['required_date'])) : '—' ?></span>
            </div>
            <div class="po-meta-row">
                <span class="po-meta-label">Requisition Purpose:</span>
                <span class="po-meta-val"><?= htmlspecialchars($prDetails['purpose'] ?? ($po['pr_notes'] ?? '—')) ?></span>
            </div>
            <div class="po-meta-row">
                <span class="po-meta-label">Requisition Status:</span>
                <span class="po-meta-val"><?= htmlspecialchars($prDetails['status'] ?? '—') ?></span>
            </div>
        </div>
    </div>

    <!-- 3. Selected / Awarded Vendor Box -->
    <div class="po-vendor-box">
        <div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #a7f3d0;padding-bottom:4px;margin-bottom:6px">
            <div style="display:flex;align-items:center;gap:6px">
                <span class="po-vendor-badge">Awarded / Selected Vendor</span>
                <strong style="font-size:11.5px;color:#064e3b"><?= htmlspecialchars($selectedVendor['name'] ?? ($po['vendor_name'] ?? '—')) ?></strong>
                <?php if (!empty($selectedVendor['company_name'])): ?>
                    <span style="color:#047857;font-size:10.5px">(<?= htmlspecialchars($selectedVendor['company_name']) ?>)</span>
                <?php endif; ?>
            </div>
            <div style="font-size:9.5px;color:#047857;font-family:monospace;font-weight:700">
                Vendor ID: #<?= htmlspecialchars($selectedVendor['id'] ?? ($po['vendor_id'] ?? '—')) ?>
            </div>
        </div>
        <div class="po-vendor-grid">
            <div>
                <div class="po-vg-label">Phone Contact</div>
                <div class="po-vg-val"><?= !empty($selectedVendor['phone']) ? htmlspecialchars($selectedVendor['phone']) : '—' ?></div>
            </div>
            <div>
                <div class="po-vg-label">Email Address</div>
                <div class="po-vg-val"><?= !empty($selectedVendor['email']) ? htmlspecialchars($selectedVendor['email']) : '—' ?></div>
            </div>
            <div style="grid-column: span 2">
                <div class="po-vg-label">Office / Billing Address</div>
                <div class="po-vg-val"><?= !empty($selectedVendor['address']) ? htmlspecialchars($selectedVendor['address']) : '—' ?></div>
            </div>
        </div>
    </div>

    <!-- 4. PO Ordered Items Table -->
    <div class="po-sec-title">
        <span>1. Purchase Order Items</span>
        <span style="font-size:8.5px;font-weight:600"><?= count($items) ?> item(s)</span>
    </div>

    <table class="po-print-table">
        <thead>
            <tr>
                <th style="width:25px;text-align:center">#</th>
                <th>Material / Item Description</th>
                <th>Category</th>
                <th style="width:55px;text-align:center">Unit</th>
                <th style="width:80px;text-align:right">Quantity</th>
                <th style="width:95px;text-align:right">Unit Price (BDT)</th>
                <th style="width:115px;text-align:right">Total Amount (BDT)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $calcGrandTotal = 0;
            foreach ($items as $idx => $itm): 
                $lineTotal = (float)($itm['quantity'] * $itm['unit_price']);
                $calcGrandTotal += $lineTotal;
            ?>
                <tr>
                    <td style="text-align:center"><?= $idx + 1 ?></td>
                    <td>
                        <strong><?= htmlspecialchars($itm['material_name']) ?></strong>
                    </td>
                    <td><?= htmlspecialchars($itm['category_name'] ?? 'Uncategorized') ?></td>
                    <td style="text-align:center"><?= htmlspecialchars($itm['unit']) ?></td>
                    <td style="text-align:right"><?= number_format((float)$itm['quantity'], 3) ?></td>
                    <td style="text-align:right">৳ <?= number_format((float)$itm['unit_price'], 2) ?></td>
                    <td style="text-align:right;font-weight:700">৳ <?= number_format($lineTotal, 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background:#f1f5f9;font-weight:800">
                <td colspan="6" style="text-align:right;padding:6px 8px">TOTAL ORDER VALUE (BDT):</td>
                <td style="text-align:right;padding:6px 8px;font-size:11.5px;color:#0f172a">৳ <?= number_format($calcGrandTotal, 2) ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="po-words-box">
        <strong style="color:#0f172a;text-transform:uppercase;font-size:9px;margin-right:4px">Amount in Words:</strong>
        <span><?= function_exists('amountInWords') ? amountInWords($calcGrandTotal) : '' ?></span>
    </div>

    <?php if (!empty($po['remarks'])): ?>
        <div style="background:#f8fafc;border:1px solid #cbd5e1;border-radius:4px;padding:5px 8px;margin-bottom:10px;font-size:9.5px">
            <strong style="color:#0f172a">PO Remarks:</strong>
            <span style="color:#334155;white-space:pre-wrap;"><?= htmlspecialchars($po['remarks']) ?></span>
        </div>
    <?php endif; ?>

    <!-- 5. Comparative Statement (CST) Details & Rates Comparison -->
    <div class="po-sec-title">
        <span>2. Comparative Statement (CST) & Vendor Evaluation</span>
        <span style="font-size:8.5px;font-weight:600">
            CST Prepared By: <strong><?= htmlspecialchars($cstInfo['prepared_by_name'] ?? ($vendorSelectionData['selector_name'] ?? '—')) ?></strong>
            <?php if (!empty($cstInfo['created_at'])): ?>
                (<?= date('d M Y', strtotime($cstInfo['created_at'])) ?>)
            <?php endif; ?>
        </span>
    </div>

    <!-- Participating Vendors Summary -->
    <?php if (!empty($quotationsList)): ?>
        <table class="po-print-table">
            <thead>
                <tr>
                    <th style="width:25px;text-align:center">#</th>
                    <th>Participating Vendor</th>
                    <th>Company Name</th>
                    <th>Contact Info</th>
                    <th style="width:110px;text-align:right">Quoted Total</th>
                    <th style="width:120px;text-align:center">CST Evaluation</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $selVendorId = $selectedVendor['id'] ?? ($po['vendor_id'] ?? 0);
                foreach ($quotationsList as $qIdx => $q): 
                    $isSelected = ($selVendorId && (int)$q['vendor_id'] === (int)$selVendorId);
                ?>
                    <tr style="<?= $isSelected ? 'background:#ecfdf5;font-weight:600;' : '' ?>">
                        <td style="text-align:center"><?= $qIdx + 1 ?></td>
                        <td>
                            <strong><?= htmlspecialchars($q['vendor_name'] ?? '—') ?></strong>
                            <span style="font-size:8.5px;color:#64748b;font-family:monospace">(ID: #<?= htmlspecialchars($q['vendor_id'] ?? '') ?>)</span>
                        </td>
                        <td><?= htmlspecialchars($q['vendor_company_name'] ?? '—') ?></td>
                        <td>
                            <?= htmlspecialchars($q['vendor_phone'] ?? '—') ?>
                            <?php if (!empty($q['vendor_email'])): ?>
                                <span style="font-size:8.5px;color:#64748b">| <?= htmlspecialchars($q['vendor_email']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right;font-weight:700">
                            <?php if (isset($q['total_quoted_amount'])): ?>
                                ৳ <?= number_format((float)$q['total_quoted_amount'], 2) ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center">
                            <?php if ($isSelected): ?>
                                <span class="po-badge-status po-badge-approved">✓ Selected Vendor</span>
                            <?php else: ?>
                                <span class="po-badge-status po-badge-alt">Alternative</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- CST Material Price Comparison Matrix -->
    <?php if (isset($cstMatrix) && !empty($cstMatrix['vendors'])): ?>
        <?php
        $materials = $cstMatrix['materials'] ?? [];
        $vendors   = $cstMatrix['vendors']   ?? [];
        $matrix    = $cstMatrix['matrix']    ?? [];
        $totals    = $cstMatrix['totals']    ?? [];
        $vendorIds = array_keys($vendors);
        $selectedVendorId = $po['vendor_id'];
        ?>
        <div style="font-size:9.5px;font-weight:700;color:#0f172a;margin:5px 0 3px 0">
            Material-by-Material Rate Comparison (CST Matrix):
        </div>
        <table class="po-print-table">
            <thead>
                <tr>
                    <th style="width:25px;text-align:center">#</th>
                    <th>Material Description</th>
                    <th style="width:65px;text-align:right">PR Qty</th>
                    <?php foreach ($vendors as $vid => $vname): ?>
                        <th style="text-align:center;<?= ($vid == $selectedVendorId) ? 'background:#065f46;' : '' ?>">
                            <?= htmlspecialchars($vname) ?>
                            <?php if ($vid == $selectedVendorId): ?>
                                <br><span style="font-size:7.5px;text-transform:none;background:#10b981;color:#fff;padding:1px 3px;border-radius:2px">Selected</span>
                            <?php endif; ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($materials as $mIdx => $mat): 
                    $mid = $mat['material_id'];
                ?>
                    <tr>
                        <td style="text-align:center"><?= $mIdx + 1 ?></td>
                        <td>
                            <strong><?= htmlspecialchars($mat['material_name']) ?></strong>
                            <span style="color:#64748b;font-size:8.5px">(<?= htmlspecialchars($mat['unit']) ?>)</span>
                        </td>
                        <td style="text-align:right"><?= number_format((float)$mat['quantity'], 3) ?></td>
                        <?php foreach ($vendorIds as $vid): 
                            $cell = $matrix[$mid][$vid] ?? null;
                            $isSelectedCol = ($vid == $selectedVendorId);
                        ?>
                            <td style="text-align:center;<?= $isSelectedCol ? 'background:#f0fdf4;font-weight:700;' : '' ?>">
                                <?php if ($cell): ?>
                                    <div>৳ <?= number_format($cell['unit_price'], 2) ?></div>
                                    <div style="font-size:8.5px;color:#64748b">৳ <?= number_format($cell['total_price'], 2) ?></div>
                                <?php else: ?>
                                    <span style="color:#94a3b8">—</span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f1f5f9;font-weight:700">
                    <td colspan="3" style="text-align:right">Total Evaluated Value:</td>
                    <?php foreach ($vendorIds as $vid): ?>
                        <td style="text-align:center;<?= ($vid == $selectedVendorId) ? 'background:#ecfdf5;color:#047857;font-weight:800;' : '' ?>">
                            ৳ <?= number_format($totals[$vid] ?? 0, 2) ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>

    <!-- Rate Observation Notes (if any) -->
    <?php if (!empty($negotiationNotes)): ?>
        <div style="margin-top:5px;background:#fdf4ff;border:1px solid #f0abfc;border-radius:4px;padding:5px 8px;font-size:9.5px">
            <strong style="color:#86198f">Rate Negotiation / Observation Notes:</strong>
            <?php foreach ($negotiationNotes as $n): ?>
                <div style="margin-top:2px;border-top:1px dashed #f5d0fe;padding-top:2px">
                    <span style="font-weight:700;color:#701a75"><?= htmlspecialchars($n['author_name']) ?></span>
                    <span style="color:#a21caf;font-size:8.5px">(<?= date('d M Y, h:i A', strtotime($n['created_at'])) ?>):</span>
                    <span style="color:#4a044e"><?= htmlspecialchars($n['note_text']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- 6. Approval Workflow & Audit Trail -->
    <div class="po-sec-title" style="margin-top:12px">
        <span>3. Approval Workflow & Verification Details</span>
        <span style="font-size:8.5px;font-weight:600"><?= $fullyApproved ? 'Fully Approved' : 'In Approval Process' ?></span>
    </div>

    <table class="po-print-table">
        <thead>
            <tr>
                <th style="width:130px">Stage</th>
                <th style="width:160px">Authority / Official</th>
                <th style="width:85px;text-align:center">Status</th>
                <th style="width:135px">Date & Time</th>
                <th>Remarks / Notes</th>
            </tr>
        </thead>
        <tbody>
            <!-- PR Requester -->
            <tr>
                <td><strong>1. Purchase Requisition</strong></td>
                <td><?= htmlspecialchars($prDetails['requester_name'] ?? '—') ?></td>
                <td style="text-align:center"><span class="po-badge-status po-badge-approved">Submitted</span></td>
                <td><?= !empty($prDetails['created_at']) ? date('d M Y, h:i A', strtotime($prDetails['created_at'])) : '—' ?></td>
                <td><?= htmlspecialchars($prDetails['purpose'] ?? 'Purchase Requisition Generated') ?></td>
            </tr>
            <!-- CST Evaluator -->
            <tr>
                <td><strong>2. CST Comparison</strong></td>
                <td><?= htmlspecialchars($cstInfo['prepared_by_name'] ?? ($vendorSelectionData['selector_name'] ?? '—')) ?></td>
                <td style="text-align:center"><span class="po-badge-status po-badge-approved">Prepared</span></td>
                <td><?= !empty($cstInfo['created_at']) ? date('d M Y, h:i A', strtotime($cstInfo['created_at'])) : '—' ?></td>
                <td>Comparative statement evaluated and awarded vendor recommended.</td>
            </tr>
            <!-- PO Prepared -->
            <tr>
                <td><strong>3. PO Generation</strong></td>
                <td><?= htmlspecialchars($po['created_by_name'] ?? '—') ?></td>
                <td style="text-align:center"><span class="po-badge-status po-badge-approved">Issued</span></td>
                <td><?= !empty($po['created_at']) ? date('d M Y, h:i A', strtotime($po['created_at'])) : date('d M Y', strtotime($po['order_date'] ?? 'now')) ?></td>
                <td>Purchase Order created against PR #<?= htmlspecialchars($po['request_id'] ?? '') ?>.</td>
            </tr>
            <!-- Purchase Head -->
            <?php if ($phNeeded): ?>
            <tr>
                <td><strong>4. Purchase Head</strong></td>
                <td><?= !empty($po['ph_approver_name']) ? htmlspecialchars($po['ph_approver_name']) : 'Purchase Head' ?></td>
                <td style="text-align:center">
                    <span class="po-badge-status <?= $phApproved ? 'po-badge-approved' : 'po-badge-pending' ?>">
                        <?= $phApproved ? 'Approved' : 'Pending' ?>
                    </span>
                </td>
                <td><?= !empty($po['purchase_head_approved_at']) ? date('d M Y, h:i A', strtotime($po['purchase_head_approved_at'])) : '—' ?></td>
                <td><?= !empty($po['purchase_head_note']) ? htmlspecialchars($po['purchase_head_note']) : '—' ?></td>
            </tr>
            <?php endif; ?>
            <!-- QC & Internal Audit -->
            <tr>
                <td><strong><?= $phNeeded ? '5' : '4' ?>. QC & Internal Audit</strong></td>
                <td><?= !empty($po['qc_approver_name']) ? htmlspecialchars($po['qc_approver_name']) : 'QC & Audit Authority' ?></td>
                <td style="text-align:center">
                    <span class="po-badge-status <?= $qcApproved ? 'po-badge-approved' : 'po-badge-pending' ?>">
                        <?= $qcApproved ? 'Approved' : 'Pending' ?>
                    </span>
                </td>
                <td><?= !empty($po['qc_approved_at']) ? date('d M Y, h:i A', strtotime($po['qc_approved_at'])) : '—' ?></td>
                <td><?= !empty($po['qc_note']) ? htmlspecialchars($po['qc_note']) : '—' ?></td>
            </tr>
            <!-- Operation Head -->
            <tr>
                <td><strong><?= $phNeeded ? '6' : '5' ?>. Operation Head</strong></td>
                <td><?= !empty($po['op_approver_name']) ? htmlspecialchars($po['op_approver_name']) : 'Operation Head' ?></td>
                <td style="text-align:center">
                    <span class="po-badge-status <?= $opApproved ? 'po-badge-approved' : 'po-badge-pending' ?>">
                        <?= $opApproved ? 'Approved' : 'Pending' ?>
                    </span>
                </td>
                <td><?= !empty($po['op_head_approved_at']) ? date('d M Y, h:i A', strtotime($po['op_head_approved_at'])) : '—' ?></td>
                <td><?= !empty($po['op_head_note']) ? htmlspecialchars($po['op_head_note']) : '—' ?></td>
            </tr>
        </tbody>
    </table>

    <!-- 7. Official Signatures -->
    <div class="po-sig-container">
        <div class="po-sig-block">
            <div class="po-sig-line"><?= htmlspecialchars($po['created_by_name'] ?? 'PO Prepared By') ?></div>
            <div class="po-sig-sub">Prepared By (PO)</div>
        </div>
        <div class="po-sig-block">
            <div class="po-sig-line"><?= htmlspecialchars($cstInfo['prepared_by_name'] ?? ($vendorSelectionData['selector_name'] ?? 'CST Evaluator')) ?></div>
            <div class="po-sig-sub">CST Prepared / Evaluated</div>
        </div>
        <div class="po-sig-block">
            <div class="po-sig-line"><?= !empty($po['ph_approver_name']) ? htmlspecialchars($po['ph_approver_name']) : ($phNeeded ? 'Pending Approval' : 'N/A') ?></div>
            <div class="po-sig-sub">Head of Purchase</div>
        </div>
        <div class="po-sig-block">
            <div class="po-sig-line"><?= !empty($po['qc_approver_name']) ? htmlspecialchars($po['qc_approver_name']) : 'QC & Audit' ?></div>
            <div class="po-sig-sub">QC & Internal Audit</div>
        </div>
        <div class="po-sig-block">
            <div class="po-sig-line"><?= !empty($po['op_approver_name']) ? htmlspecialchars($po['op_approver_name']) : 'Operation Head' ?></div>
            <div class="po-sig-sub">Head of Operations / MD</div>
        </div>
    </div>

    <!-- 8. Document Footer -->
    <div class="po-doc-footer">
        <div>
            This is an official computer-generated Purchase Order & CST Approval Sheet from TPHL ERP System.
        </div>
        <div>
            Printed on: <?= date('d M Y, h:i A') ?>
        </div>
    </div>

</div>
