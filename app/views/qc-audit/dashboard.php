<?php
$addPage = true;
$show_breadcrumb = false;
$base = $GLOBALS['base_url'];

// Helper: format BDT amounts
if (!function_exists('conFmt')) {
    function conFmt($n) {
        $n = (float) $n;
        if ($n >= 10000000) return '৳' . number_format($n / 10000000, 2) . ' Cr';
        if ($n >= 100000) return '৳' . number_format($n / 100000, 2) . ' L';
        if ($n >= 1000) return '৳' . number_format($n / 1000, 1) . 'K';
        return '৳' . number_format($n, 0);
    }
}

$stats = $stats ?? [];
$cashoutRequests = $cashoutRequests ?? [];
$vendorPayments = $vendorPayments ?? [];
$contractorBills = $contractorBills ?? [];
$purchaseOrders = $purchaseOrders ?? [];
?>
<div class="row">
    <div class="col-12">
        <style>
            :root {
                --cd-primary: #198754;
                --cd-dark: #0f5132;
                --cd-light: #20a86a;
                --cd-blue: #3b82f6;
                --cd-green: #10b981;
                --cd-amber: #f59e0b;
                --cd-red: #ef4444;
                --cd-purple: #8b5cf6;
                --cd-indigo: #6366f1;
                --cd-teal: #14b8a6;
                --cd-pink: #ec4899;
            }

            /* ── KPI Cards ────────────────────────────────────────────── */
            .kpi-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 14px;
                margin-bottom: 22px;
            }

            @media (max-width: 1200px) {
                .kpi-grid { grid-template-columns: repeat(2, 1fr); }
            }
            @media (max-width: 576px) {
                .kpi-grid { grid-template-columns: 1fr; gap: 10px; }
            }

            .kpi-card {
                border-radius: 14px;
                padding: 18px 20px;
                position: relative;
                overflow: hidden;
                text-decoration: none !important;
                display: block;
                border: 1px solid var(--border-color, rgba(0, 0, 0, .08));
                transition: transform .18s ease, box-shadow .18s ease;
                background: #fff;
            }

            .kpi-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 8px 28px rgba(0, 0, 0, .12);
            }

            .kpi-accent {
                position: absolute; left: 0; top: 0; width: 4px; height: 100%; border-radius: 14px 0 0 14px;
            }

            .kpi-icon {
                width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center;
                justify-content: center; font-size: 18px; margin-bottom: 12px;
            }

            .kpi-value {
                font-size: 26px; font-weight: 800; line-height: 1; margin-bottom: 4px; color: #111827;
            }

            .kpi-label {
                font-size: 10.5px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 3px;
            }

            .kpi-sub { font-size: 11px; }

            .kpi-badge {
                position: absolute; top: 16px; right: 16px; font-size: 9px; font-weight: 700;
                padding: 4px 10px; border-radius: 20px; letter-spacing: .5px;
            }

            /* ── Project Table ────────────────────────────────────────── */
            .po-table-container { width: 100%; overflow-x: auto; }
            .po-table { width: 100%; border-collapse: collapse; font-size: 13px; }
            .po-table th {
                background: #fcfdfd; padding: 14px 16px; font-size: 10px; font-weight: 700;
                text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; text-align: left;
                border-bottom: 1px solid rgba(0,0,0,0.05);
            }
            .po-table td { padding: 14px 16px; border-bottom: 1px solid rgba(0,0,0,0.04); vertical-align: middle; }
            .po-table tr.proj-row { cursor: pointer; transition: background .15s; }
            .po-table tr.proj-row:hover td { background: rgba(59, 130, 246, .04); }
            
            .status-pill {
                display: inline-flex; align-items: center; gap: 5px; font-size: 10px; font-weight: 700;
                padding: 3px 10px; border-radius: 99px; letter-spacing: 0.2px; text-transform: capitalize;
            }

            /* Breakdown Pills */
            .pending-pill {
                background: #fff; border: 1px solid rgba(0,0,0,.08); border-radius: 12px;
                padding: 14px; display: flex; align-items: center; justify-content: space-between;
                cursor: pointer; transition: transform 0.15s, box-shadow 0.15s;
            }
            .pending-pill:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,.05); }
            .pill-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
            .pill-title { font-size: 12.5px; font-weight: 700; line-height: 1.2; margin-bottom: 2px; }
            .pill-sub { font-size: 11px; color: #64748b; }
            .pill-badge { font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px; }

            .con-panel {
                border-radius: 16px; border: 1px solid rgba(0,0,0,0.08); background: #fff; padding: 24px;
            }
            .sec-title {
                display: flex; align-items: center; font-size: 15px; font-weight: 800; color: #1e293b;
            }
            .sec-title-dot { width: 10px; height: 10px; border-radius: 3px; margin-right: 12px; }
        </style>

        <!-- ═══════════════════════════════ GREEN HERO BANNER ═══════════════════════════════ -->
        <div style="background:linear-gradient(135deg, var(--cd-dark) 0%, var(--cd-primary) 55%, var(--cd-light) 100%);
            border-radius:18px;padding:26px 30px;margin-bottom:22px; position:relative;overflow:hidden">
            <div style="position:absolute;width:260px;height:260px;border-radius:50%;
                background:rgba(255,255,255,.04);top:-80px;right:-60px"></div>
            <div style="position:absolute;width:160px;height:160px;border-radius:50%;
                background:rgba(255,255,255,.03);bottom:-50px;left:-30px"></div>
            
            <div style="position:relative;z-index:2;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:14px">
                <div>
                    <div style="display:inline-flex;align-items:center;gap:7px;
                        background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.22);
                        padding:4px 14px;border-radius:20px;font-size:10.5px;font-weight:700;
                        letter-spacing:1.5px;text-transform:uppercase;color:#fff;margin-bottom:12px">
                        <i class="fa-solid fa-shield-check"></i> QC & Internal Audit
                    </div>
                    <h4 style="font-size:24px;font-weight:900;color:#fff;margin:0 0 5px">Internal Audit Dashboard</h4>
                    <p style="font-size:12.5px;color:rgba(255,255,255,.7);margin:0">
                        <i class="fal fa-calendar me-1"></i>Real-time overview · <?= date('d M Y, h:i A') ?>
                    </p>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                    <a href="<?= $base ?>/qc-requests" style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);
                        color:#fff;border-radius:10px;padding:7px 18px;font-size:12px;font-weight:700;
                        text-decoration:none;display:inline-flex;align-items:center;gap:6px">
                        <i class="fal fa-check-square"></i>QC Approvals
                    </a>
                
                    <a href="<?= $base ?>/qc-advanced-audit" style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);
                        color:#fff;border-radius:10px;padding:7px 18px;font-size:12px;font-weight:700;
                        text-decoration:none;display:inline-flex;align-items:center;gap:6px">
                        <i class="fal fa-filter"></i>Advanced Audit
                    </a>
                </div>
            </div>
        </div>

        <!-- ══════════════════════ ROW 1: 4 KPI CARDS ═══════════════════════════════ -->
        <div class="kpi-grid">

            <!-- 1. Pending Approvals -->
            <a href="<?= $base ?>/qc-requests" class="kpi-card card__wrapper">
                <div class="kpi-accent" style="background:var(--cd-blue)"></div>
                <div class="kpi-icon" style="background:rgba(59,130,246,.12);color:var(--cd-blue)"><i class="fal fa-clock"></i></div>
                <div class="kpi-label" style="color:var(--text-muted)">Pending QC Approvals</div>
                <div class="kpi-value" style="color:var(--cd-blue)"><?= number_format($stats['pending'] ?? 0) ?></div>
                <div class="kpi-sub" style="color:var(--text-muted)">Requests awaiting review</div>
                <?php if (($stats['pending'] ?? 0) > 0): ?>
                    <span class="kpi-badge" style="background:rgba(59,130,246,.12);color:var(--cd-blue)">ACTION REQUIRED</span>
                <?php endif; ?>
            </a>

            <!-- 2. Approved By QC -->
            <a href="<?= $base ?>/qc-requests" class="kpi-card card__wrapper">
                <div class="kpi-accent" style="background:var(--cd-green)"></div>
                <div class="kpi-icon" style="background:rgba(16,185,129,.12);color:var(--cd-green)"><i class="fal fa-check-circle"></i></div>
                <div class="kpi-label" style="color:var(--text-muted)">Approved By QC</div>
                <div class="kpi-value" style="color:var(--cd-green)"><?= number_format($stats['approved'] ?? 0) ?></div>
                <div class="kpi-sub" style="color:var(--text-muted)">Total approved requests</div>
            </a>

            <!-- 3. Pending Vendor Payments -->
            <a href="<?= $base ?>/con-vendor-payment-requests" class="kpi-card card__wrapper">
                <div class="kpi-accent" style="background:var(--cd-amber)"></div>
                <div class="kpi-icon" style="background:rgba(245,158,11,.12);color:var(--cd-amber)"><i class="fal fa-file-invoice-dollar"></i></div>
                <div class="kpi-label" style="color:var(--text-muted)">Pending Vendor Payments</div>
                <div class="kpi-value" style="color:var(--cd-amber)"><?= number_format($stats['vendor_pending'] ?? 0) ?></div>
                <div class="kpi-sub" style="color:var(--text-muted)">Value: <?= conFmt($stats['vendor_pending_total'] ?? 0) ?></div>
            </a>

            <!-- 4. Pending Contractor Bills -->
            <a href="<?= $base ?>/qc-requests" class="kpi-card card__wrapper">
                <div class="kpi-accent" style="background:var(--cd-purple)"></div>
                <div class="kpi-icon" style="background:rgba(139,92,246,.12);color:var(--cd-purple)"><i class="fal fa-hard-hat"></i></div>
                <div class="kpi-label" style="color:var(--text-muted)">Pending Contractor Bills</div>
                <div class="kpi-value" style="color:var(--cd-purple)"><?= number_format($stats['contractor_pending'] ?? 0) ?></div>
                <div class="kpi-sub" style="color:var(--text-muted)">Value: <?= conFmt($stats['contractor_pending_total'] ?? 0) ?></div>
            </a>

        </div>

        <!-- ══════════════════════ ROW 2: 4 MORE CARDS ══════════════════════════════ -->
        <div class="kpi-grid mb-4">

            <!-- 5. Today's Cashout (EOD) -->
            <a href="<?= $base ?>/qc-requests" class="kpi-card card__wrapper">
                <div class="kpi-accent" style="background:var(--cd-teal)"></div>
                <div class="kpi-icon" style="background:rgba(20,184,166,.12);color:var(--cd-teal)"><i class="fal fa-wallet"></i></div>
                <div class="kpi-label" style="color:var(--text-muted)">Today's EOD Cashout</div>
                <div class="kpi-value" style="color:var(--cd-teal)"><?= number_format($stats['today_cashout_count'] ?? 0) ?></div>
                <div class="kpi-sub" style="color:var(--text-muted)">Value: <?= conFmt($stats['today_cashout_total'] ?? 0) ?></div>
            </a>

            <!-- 6. Material Requisitions Pending -->
            <a href="<?= $base ?>/qc-requests" class="kpi-card card__wrapper">
                <div class="kpi-accent" style="background:var(--cd-indigo)"></div>
                <div class="kpi-icon" style="background:rgba(99,102,241,.12);color:var(--cd-indigo)"><i class="fal fa-boxes"></i></div>
                <div class="kpi-label" style="color:var(--text-muted)">Material Reqs (Pending)</div>
                <div class="kpi-value" style="color:var(--cd-indigo)"><?= number_format($stats['material_pending'] ?? 0) ?></div>
                <div class="kpi-sub" style="color:var(--text-muted)">Purchase requests awaiting verify</div>
            </a>

            <!-- 7. POs to Verify -->
            <a href="<?= $base ?>/con-purchase-orders" class="kpi-card card__wrapper">
                <div class="kpi-accent" style="background:var(--cd-pink)"></div>
                <div class="kpi-icon" style="background:rgba(236,72,153,.12);color:var(--cd-pink)"><i class="fal fa-shopping-cart"></i></div>
                <div class="kpi-label" style="color:var(--text-muted)">POs to Verify</div>
                <div class="kpi-value" style="color:var(--cd-pink)"><?= number_format($stats['po_pending'] ?? 0) ?></div>
                <div class="kpi-sub" style="color:var(--text-muted)">Purchase orders (Issued)</div>
            </a>

            <!-- 8. Rejected Requests -->
            <a href="<?= $base ?>/qc-requests" class="kpi-card card__wrapper">
                <div class="kpi-accent" style="background:var(--cd-red)"></div>
                <div class="kpi-icon" style="background:rgba(239,68,68,.12);color:var(--cd-red)"><i class="fal fa-ban"></i></div>
                <div class="kpi-label" style="color:var(--text-muted)">Rejected Requests</div>
                <div class="kpi-value" style="color:var(--cd-red)"><?= number_format($stats['rejected'] ?? 0) ?></div>
                <div class="kpi-sub" style="color:var(--text-muted)">All rejected transactions</div>
            </a>

        </div>

        <!-- ══════════════════════ BREAKDOWN SUMMARY PILLS ══════════════════════════════ -->
        <?php 
        $totalTblPending = count($cashoutRequests) + count($vendorPayments) + count($contractorBills) + count($purchaseOrders);
        ?>
        <div class="panel-section mb-4">
            <div class="section-title d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2 text-warning fw-bold text-uppercase" style="font-size: 12px; letter-spacing: 1px;">
                    <i class="fa-solid fa-circle-exclamation fs-6"></i>
                    PENDING AUDITS BREAKDOWN (CLICK TO SCROLL TO TABLE)
                </div>
                <div class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold">
                    Total <?= $totalTblPending ?> Displayed
                </div>
            </div>
            
            <div class="row g-2">
                <!-- Cashouts -->
                <div class="col-xl-3 col-lg-4 col-sm-6">
                    <div class="pending-pill" onclick="document.getElementById('cashout-section').scrollIntoView({behavior: 'smooth'})">
                        <div class="d-flex align-items-center gap-2">
                            <div class="pill-icon" style="background: rgba(20,184,166,0.1); color: #14b8a6;"><i class="fa-solid fa-wallet"></i></div>
                            <div>
                                <div class="pill-title text-dark">Cashout Requests</div>
                                <div class="pill-sub">Pending / Submitted</div>
                            </div>
                        </div>
                        <div class="pill-badge <?= count($cashoutRequests) > 0 ? 'bg-danger text-white' : 'bg-success text-white' ?>">
                            <?= count($cashoutRequests) ?>
                        </div>
                    </div>
                </div>

                <!-- Vendor Payments -->
                <div class="col-xl-3 col-lg-4 col-sm-6">
                    <div class="pending-pill" onclick="document.getElementById('vendor-section').scrollIntoView({behavior: 'smooth'})">
                        <div class="d-flex align-items-center gap-2">
                            <div class="pill-icon" style="background: rgba(245,158,11,0.1); color: #f59e0b;"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                            <div>
                                <div class="pill-title text-dark">Vendor Payments</div>
                                <div class="pill-sub">Pending Review</div>
                            </div>
                        </div>
                        <div class="pill-badge <?= count($vendorPayments) > 0 ? 'bg-danger text-white' : 'bg-success text-white' ?>">
                            <?= count($vendorPayments) ?>
                        </div>
                    </div>
                </div>

                <!-- Contractor Bills -->
                <div class="col-xl-3 col-lg-4 col-sm-6">
                    <div class="pending-pill" onclick="document.getElementById('contractor-section').scrollIntoView({behavior: 'smooth'})">
                        <div class="d-flex align-items-center gap-2">
                            <div class="pill-icon" style="background: rgba(139,92,246,0.1); color: #8b5cf6;"><i class="fa-solid fa-hard-hat"></i></div>
                            <div>
                                <div class="pill-title text-dark">Contractor Bills</div>
                                <div class="pill-sub">Awaiting Audit</div>
                            </div>
                        </div>
                        <div class="pill-badge <?= count($contractorBills) > 0 ? 'bg-danger text-white' : 'bg-success text-white' ?>">
                            <?= count($contractorBills) ?>
                        </div>
                    </div>
                </div>

                <!-- Purchase Orders -->
                <div class="col-xl-3 col-lg-4 col-sm-6">
                    <div class="pending-pill" onclick="document.getElementById('po-section').scrollIntoView({behavior: 'smooth'})">
                        <div class="d-flex align-items-center gap-2">
                            <div class="pill-icon" style="background: rgba(236,72,153,0.1); color: #ec4899;"><i class="fa-solid fa-shopping-cart"></i></div>
                            <div>
                                <div class="pill-title text-dark">Purchase Orders</div>
                                <div class="pill-sub">Issued (Need Verify)</div>
                            </div>
                        </div>
                        <div class="pill-badge <?= count($purchaseOrders) > 0 ? 'bg-danger text-white' : 'bg-success text-white' ?>">
                            <?= count($purchaseOrders) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════ DATA TABLES ══════════════════════ -->
        
        <!-- 1. Cashout Requests -->
        <div id="cashout-section" class="con-panel card__wrapper mb-4" style="border-color:rgba(20,184,166,.3);background:rgba(20,184,166,.03)">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="sec-title" style="margin-bottom:0">
                    <div class="sec-title-dot" style="background:var(--cd-teal)"></div>
                    <i class="fal fa-wallet me-2" style="color:var(--cd-teal)"></i>
                    Pending Cashout Requests
                </div>
                <a href="<?= $base ?>/qc-requests" style="font-size:11px;color:var(--cd-teal);text-decoration:none">View All
                <i class="fal fa-arrow-right ms-1"></i></a>
            </div>
            <div class="po-table-container">
                <table class="po-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Project</th>
                            <th>Requested By</th>
                            <th>Amount</th>
                            <th>Purpose / Paid To</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($cashoutRequests)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">No pending cashout requests found.</td></tr>
                        <?php else: foreach ($cashoutRequests as $cr): ?>
                            <tr class="proj-row" onclick="window.location='<?= $base ?>/qc-requests'">
                                <td>#<?= htmlspecialchars($cr['id']) ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($cr['project_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($cr['requested_by_name'] ?? 'N/A') ?></td>
                                <td class="fw-bold text-dark"><?= conFmt($cr['amount']) ?></td>
                                <td><?= htmlspecialchars($cr['purpose_paid_to'] ?? '') ?></td>
                                <td style="font-size:11.5px;color:#64748b"><?= date('d M Y', strtotime($cr['created_at'])) ?></td>
                                <td>
                                    <span class="status-pill status-atrisk">
                                        <span class="status-pill-dot"></span><?= htmlspecialchars($cr['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 2. Vendor Payments -->
        <div id="vendor-section" class="con-panel card__wrapper mb-4" style="border-color:rgba(245,158,11,.3);background:rgba(245,158,11,.03)">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="sec-title" style="margin-bottom:0">
                    <div class="sec-title-dot" style="background:var(--cd-amber)"></div>
                    <i class="fal fa-file-invoice-dollar me-2" style="color:var(--cd-amber)"></i>
                    Pending Vendor Payments
                </div>
                <a href="<?= $base ?>/con-vendor-payment-requests" style="font-size:11px;color:var(--cd-amber);text-decoration:none">View All
                <i class="fal fa-arrow-right ms-1"></i></a>
            </div>
            <div class="po-table-container">
                <table class="po-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Project</th>
                            <th>Vendor</th>
                            <th>Payment Type</th>
                            <th>Invoice Amt</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($vendorPayments)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">No pending vendor payments found.</td></tr>
                        <?php else: foreach ($vendorPayments as $vp): ?>
                            <tr class="proj-row" onclick="window.location='<?= $base ?>/con-vendor-payment-requests'">
                                <td>#<?= htmlspecialchars($vp['id']) ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($vp['project_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($vp['vendor_name'] ?? 'N/A') ?></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($vp['payment_type'] ?? '') ?></span></td>
                                <td class="fw-bold text-dark"><?= conFmt($vp['invoice_amount']) ?></td>
                                <td style="font-size:11.5px;color:#64748b"><?= date('d M Y', strtotime($vp['created_at'])) ?></td>
                                <td>
                                    <span class="status-pill status-atrisk">
                                        <span class="status-pill-dot"></span><?= htmlspecialchars($vp['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3. Contractor Bills -->
        <div id="contractor-section" class="con-panel card__wrapper mb-4" style="border-color:rgba(139,92,246,.3);background:rgba(139,92,246,.03)">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="sec-title" style="margin-bottom:0">
                    <div class="sec-title-dot" style="background:var(--cd-purple)"></div>
                    <i class="fal fa-hard-hat me-2" style="color:var(--cd-purple)"></i>
                    Pending Contractor Bills
                </div>
                <a href="<?= $base ?>/qc-requests" style="font-size:11px;color:var(--cd-purple);text-decoration:none">View All
                <i class="fal fa-arrow-right ms-1"></i></a>
            </div>
            <div class="po-table-container">
                <table class="po-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Project</th>
                            <th>Contractor</th>
                            <th>Bill Type</th>
                            <th>Bill Amt</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($contractorBills)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">No pending contractor bills found.</td></tr>
                        <?php else: foreach ($contractorBills as $cb): ?>
                            <tr class="proj-row" onclick="window.location='<?= $base ?>/qc-requests'">
                                <td>#<?= htmlspecialchars($cb['id']) ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($cb['project_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($cb['contractor_name'] ?? 'N/A') ?></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($cb['bill_type'] ?? '') ?></span></td>
                                <td class="fw-bold text-dark"><?= conFmt($cb['bill_amount']) ?></td>
                                <td style="font-size:11.5px;color:#64748b"><?= date('d M Y', strtotime($cb['created_at'])) ?></td>
                                <td>
                                    <span class="status-pill status-atrisk">
                                        <span class="status-pill-dot"></span><?= htmlspecialchars($cb['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 4. Purchase Orders -->
        <div id="po-section" class="con-panel card__wrapper mb-4" style="border-color:rgba(236,72,153,.3);background:rgba(236,72,153,.03)">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="sec-title" style="margin-bottom:0">
                    <div class="sec-title-dot" style="background:var(--cd-pink)"></div>
                    <i class="fal fa-shopping-cart me-2" style="color:var(--cd-pink)"></i>
                    Purchase Orders Awaiting Verification
                </div>
                <a href="<?= $base ?>/con-purchase-orders" style="font-size:11px;color:var(--cd-pink);text-decoration:none">View All
                <i class="fal fa-arrow-right ms-1"></i></a>
            </div>
            <div class="po-table-container">
                <table class="po-table">
                    <thead>
                        <tr>
                            <th>PO #</th>
                            <th>Project</th>
                            <th>Vendor</th>
                            <th>Order Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($purchaseOrders)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No purchase orders found.</td></tr>
                        <?php else: foreach ($purchaseOrders as $po): ?>
                            <tr class="proj-row" onclick="window.location='<?= $base ?>/con-purchase-orders'">
                                <td>#<?= htmlspecialchars($po['id']) ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($po['project_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($po['vendor_name'] ?? 'N/A') ?></td>
                                <td style="font-size:11.5px;color:#64748b"><?= date('d M Y', strtotime($po['order_date'])) ?></td>
                                <td>
                                    <span class="status-pill status-planning">
                                        <span class="status-pill-dot"></span><?= htmlspecialchars($po['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
