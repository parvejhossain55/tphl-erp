<?php
/**
 * Dedicated Page: Client EMI Tracking & Full Statement Breakdown
 * Route: /client-emi-tracking/{bookingId}
 */

$page_title = "EMI Tracking & Statement - " . htmlspecialchars($clientDetails['client_name'] ?? 'Client');
$page = 'Clients';
$show_breadcrumb = true;

$vSchedule = $virtualEmiSchedule ?? [];
$totEmi = (int)($paymentSummary['total_emi'] ?? (count($vSchedule) > 0 ? count($vSchedule) : 36));
if ($totEmi <= 0) $totEmi = 36;
$completedCount = (int)($paymentSummary['completed_emi'] ?? 0);
$perEmiAmt = (float)($paymentSummary['per_emi_amount'] ?? 0);
$consFee = (float)($paymentSummary['construction_fee'] ?? 0);
$bId = (int)($clientDetails['booking_id'] ?? 0);
$clientName = htmlspecialchars($clientDetails['client_name'] ?? 'Client');
$projectName = htmlspecialchars($clientDetails['project_name'] ?? 'Project');
$unitName = htmlspecialchars($clientDetails['unit_name'] ?? 'Unit');
$phone = htmlspecialchars($clientDetails['phone'] ?? 'N/A');
$bookingDate = !empty($clientDetails['booking_date']) ? date('d M, Y', strtotime($clientDetails['booking_date'])) : 'N/A';
$salesAgent = htmlspecialchars($clientDetails['agent_name'] ?? 'N/A');
$baseUrl = $GLOBALS['base_url'] ?? (defined('BASE_URL') ? BASE_URL : '/TPHL-ERP');
?>

<style>
/* ═══════════════════════════════════════════════════════════════
   PRINT STYLES: Clean 1-Page Official Statement
   ═══════════════════════════════════════════════════════════════ */
@media print {
    /* 1. Hide universal print pad, logo header, side borders, and layout noise */
    .universal-print-pad,
    .upp-header,
    .upp-left-border,
    .upp-right-border,
    .upp-watermark,
    .upp-footer,
    .sidebar__area, 
    .header__area, 
    .app-header, 
    .no-print, 
    .btn.no-print, 
    .breadcrumb, 
    .filter-controls, 
    footer,
    .screen-header-banner,
    .control-card-section,
    .kpi-cards-section,
    .progress-bar-card,
    .card-header-screen {
        display: none !important;
        visibility: hidden !important;
    }

    /* 2. Override layout margins/padding completely so content uses 100% of printable A4 width */
    body .app__slide-wrapper,
    html body .app__slide-wrapper,
    body .main-content,
    html body .main-content,
    .main__content,
    .card__wrapper,
    body, 
    html {
        background: #ffffff !important;
        margin: 0 !important;
        margin-top: 0 !important;
        margin-bottom: 0 !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
        height: auto !important;
        min-height: auto !important;
        overflow: visible !important;
    }

    /* 3. Show clean statement header right at the top */
    .print-statement-header {
        display: block !important;
        width: 100% !important;
        margin: 0 !important;
        margin-top: 0 !important;
        padding-top: 0 !important;
        margin-bottom: 6px !important;
    }

    /* 4. Table styling: 8-column proportional fixed layout so Maturity Status & Follow-up NEVER clip */
    .card {
        box-shadow: none !important;
        border: none !important;
        margin: 0 !important;
        padding: 0 !important;
        background: transparent !important;
    }
    .table-responsive {
        overflow: visible !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    #emiTrackingDetailTable {
        font-size: 8.5px !important;
        width: 100% !important;
        table-layout: fixed !important;
        border-collapse: collapse !important;
    }
    #emiTrackingDetailTable tr {
        page-break-inside: avoid !important;
    }
    #emiTrackingDetailTable th,
    #emiTrackingDetailTable td {
        padding: 1.5px 2px !important;
        border: 1px solid #cbd5e1 !important;
        line-height: 1.15 !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }
    #emiTrackingDetailTable th {
        font-size: 8px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        background-color: #f1f5f9 !important;
        color: #0f172a !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    #emiTrackingDetailTable th.col-emi,
    #emiTrackingDetailTable td.col-emi {
        width: 6% !important;
        text-align: center !important;
    }
    #emiTrackingDetailTable th.col-date,
    #emiTrackingDetailTable td.col-date {
        width: 18% !important;
        white-space: normal !important;
    }
    #emiTrackingDetailTable th.col-sched,
    #emiTrackingDetailTable td.col-sched {
        width: 14% !important;
        text-align: right !important;
    }
    #emiTrackingDetailTable th.col-paid,
    #emiTrackingDetailTable td.col-paid {
        width: 13% !important;
        text-align: right !important;
    }
    #emiTrackingDetailTable th.col-due,
    #emiTrackingDetailTable td.col-due {
        width: 13% !important;
        text-align: right !important;
    }
    #emiTrackingDetailTable th.col-pstatus,
    #emiTrackingDetailTable td.col-pstatus {
        width: 12% !important;
        text-align: center !important;
    }
    #emiTrackingDetailTable th.col-mstatus,
    #emiTrackingDetailTable td.col-mstatus {
        width: 13% !important;
        text-align: center !important;
    }
    #emiTrackingDetailTable th.col-followup,
    #emiTrackingDetailTable td.col-followup {
        width: 11% !important;
        text-align: center !important;
    }
    #emiTrackingDetailTable .badge {
        padding: 1px 3px !important;
        font-size: 7.5px !important;
        font-weight: 700 !important;
        border-radius: 2px !important;
        box-shadow: none !important;
        display: inline-block !important;
        white-space: nowrap !important;
    }
    #emiTrackingDetailTable .emi-number-tag {
        font-size: 9px !important;
    }
    #emiTrackingDetailTable .date-title {
        font-size: 8.5px !important;
    }
    #emiTrackingDetailTable .proj-tag,
    #emiTrackingDetailTable .conf-tag {
        font-size: 7.5px !important;
    }
    #emiTrackingDetailTable tfoot td {
        background-color: #f8fafc !important;
        font-size: 9px !important;
        padding: 2px 3px !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    .print-only {
        display: inline-block !important;
    }
}


.print-only {
    display: none;
}

/* ═══════════════════════════════════════════════════════════════
   SCREEN STYLES: Executive ERP Theme
   ═══════════════════════════════════════════════════════════════ */
.preset-btn {
    border: 1px solid #d1d5db;
    color: #374151;
    background-color: #ffffff;
    transition: all 0.2s ease;
}
.preset-btn:hover {
    background-color: rgba(25, 135, 84, 0.08);
    border-color: var(--clr-theme-primary, #198754);
    color: var(--clr-theme-primary, #198754);
}
.preset-btn.active {
    background-color: var(--clr-theme-primary, #198754) !important;
    color: #ffffff !important;
    border-color: var(--clr-theme-primary, #198754) !important;
    box-shadow: 0 2px 8px rgba(25, 135, 84, 0.3) !important;
}

.table-filter-btn {
    border: 1px solid #e5e7eb;
    color: #4b5563;
    font-size: 12px;
    padding: 6px 14px;
    background: #ffffff;
    transition: all 0.15s ease;
}
.table-filter-btn:hover {
    background-color: #f3f4f6;
    color: #111827;
}
.table-filter-btn.active {
    font-weight: 700 !important;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}
#filterAllReq.active {
    background-color: var(--clr-theme-primary, #198754) !important;
    border-color: var(--clr-theme-primary, #198754) !important;
    color: #ffffff !important;
}
#filterDue.active {
    background-color: #dc2626 !important;
    border-color: #dc2626 !important;
    color: #ffffff !important;
}
#filterPaid.active {
    background-color: #16a34a !important;
    border-color: #16a34a !important;
    color: #ffffff !important;
}
#filterAllSched.active {
    background-color: #374151 !important;
    border-color: #374151 !important;
    color: #ffffff !important;
}

.kpi-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 18px 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    height: 100%;
}
.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
}
</style>

<div class="row">
    <!-- Header Section (Screen Only - Clean Modern ERP Style) -->
    <div class="col-12 mb-4 screen-header-banner">
        <div class="card__wrapper border-0 rounded-4 p-4 shadow-sm" style="background: #ffffff; border: 1px solid #e2e8f0; border-top: 4px solid var(--clr-theme-primary, #198754) !important;">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 58px; height: 58px; background: rgba(25, 135, 84, 0.1); color: var(--clr-theme-primary, #198754); font-size: 26px; flex-shrink: 0;">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <h2 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.3px; font-size: 1.65rem;"><?= $clientName ?></h2>
                            <span class="badge fw-bold shadow-sm px-2.5 py-1" style="background: rgba(25, 135, 84, 0.12); color: #166534; font-size: 12px; border: 1px solid rgba(25, 135, 84, 0.25); border-radius: 6px;">
                                <i class="fa-solid fa-hashtag me-0.5"></i> Booking <?= $bId ?>
                            </span>
                            <?php if (!empty($clientDetails['is_shop']) && $clientDetails['is_shop'] == 1): ?>
                                <span class="badge bg-warning-subtle text-warning-emphasis fw-bold shadow-sm px-2.5 py-1 border border-warning-subtle" style="font-size: 11px; border-radius: 6px;">
                                    <i class="fa-solid fa-store me-1"></i> Shop Client
                                </span>
                            <?php else: ?>
                                <span class="badge bg-info-subtle text-info-emphasis fw-bold shadow-sm px-2.5 py-1 border border-info-subtle" style="font-size: 11px; border-radius: 6px;">
                                    <i class="fa-solid fa-building me-1"></i> Flat Client
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex flex-wrap gap-3 mt-2 text-muted" style="font-size: 13px;">
                            <span><i class="fa-solid fa-city me-1" style="color: var(--clr-theme-primary, #198754);"></i> <strong>Project:</strong> <?= $projectName ?> (<?= $unitName ?>)</span>
                            <span><i class="fa-solid fa-phone me-1 text-primary"></i> <strong>Phone:</strong> <?= $phone ?></span>
                            <span><i class="fa-solid fa-calendar-check me-1 text-info"></i> <strong>Booking Date:</strong> <?= $bookingDate ?></span>
                            <span><i class="fa-solid fa-user-tie me-1 text-secondary"></i> <strong>Sales Agent:</strong> <?= $salesAgent ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex align-items-center gap-2 no-print flex-wrap">
                    <a href="<?= $baseUrl ?>/clients-cash-in-history/<?= $bId ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-2 fw-semibold">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back to History
                    </a>
                    <button type="button" class="btn btn-sm rounded-pill px-3 py-2 fw-semibold text-white shadow-sm" onclick="window.copyEmiTrackingText()" style="background: #25D366; border: none;">
                        <i class="fa-brands fa-whatsapp me-1"></i> WhatsApp Summary
                    </button>
                    <button type="button" class="btn btn-sm rounded-pill px-3.5 py-2 fw-bold text-white shadow-sm" onclick="window.print()" style="background: var(--clr-theme-primary, #198754); border: none;">
                        <i class="fa-solid fa-print me-1"></i> Print Statement
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 1. Interactive Requested EMI Control Card -->
    <div class="col-12 mb-4 control-card-section no-print">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden" style="background: #ffffff; border-left: 5px solid var(--clr-theme-primary, #198754) !important; border: 1px solid #e5e7eb;">
            <div class="card-body p-4">
                <div class="row align-items-center ">
                    <div class="col-lg-5 col-md-6 border-end-lg pe-lg-4">
                        <label class="form-label text-dark fw-bold mb-1 d-flex align-items-center gap-1" style="font-size: 14px;">
                            <i class="fa-solid fa-sliders" style="color: var(--clr-theme-primary, #198754);"></i> Requested EMIs for Payment:
                            <span class="badge text-white ms-2 px-2.5 py-1 fw-bold" id="badgeRequestedCountDisplay" style="background: var(--clr-theme-primary, #198754); font-size: 13px; border-radius: 6px;">--</span>
                            <span class="text-muted fw-normal ms-1" style="font-size: 12px;">of <?= $totEmi ?> total scheduled</span>
                        </label>
                        <div class="d-flex align-items-center gap-2 mt-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary px-2.5 py-1 rounded-2" onclick="window.adjustRequestedEmis(-1)" title="Decrease by 1">
                                <i class="fa-solid fa-minus"></i>
                            </button>
                            <input type="range" class="form-range flex-grow-1" id="sliderRequestedEmis" min="1" max="<?= $totEmi ?>" value="1" oninput="window.setRequestedEmis(this.value)" style="accent-color: var(--clr-theme-primary, #198754);">
                            <button type="button" class="btn btn-sm btn-outline-secondary px-2.5 py-1 rounded-2" onclick="window.adjustRequestedEmis(1)" title="Increase by 1">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                            <div class="input-group input-group-sm" style="width: 85px;">
                                <input type="number" class="form-control text-center fw-bold fs-6 rounded-2" id="numRequestedEmis" min="1" max="<?= $totEmi ?>" value="1" onchange="window.setRequestedEmis(this.value)" style="color: var(--clr-theme-primary, #198754); border: 1.5px solid var(--clr-theme-primary, #198754);">
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-7 col-md-6 ps-lg-4">
                        <label class="form-label text-muted extra-small mb-2 d-block fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">
                            <i class="fa-solid fa-wand-magic-sparkles me-1" style="color: var(--clr-theme-primary, #198754);"></i> Quick Presets / Scenarios:
                        </label>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <button type="button" class="btn btn-sm py-1.5 px-3 fw-semibold preset-btn rounded-pill active" id="btnPresetMatured" onclick="window.applyEmiPreset('matured')" style="font-size: 12px;">
                                <i class="fa-solid fa-calendar-day me-1"></i> Up to Today (<span id="txtMaturedPresetCount">--</span>)
                            </button>
                            <?php if ($totEmi >= 12): ?>
                            <button type="button" class="btn btn-sm py-1.5 px-3 fw-semibold preset-btn rounded-pill" id="btnPreset12" onclick="window.applyEmiPreset(12)" style="font-size: 12px;">
                                12 EMIs (1st Year)
                            </button>
                            <?php endif; ?>
                            <?php if ($totEmi >= 24): ?>
                            <button type="button" class="btn btn-sm py-1.5 px-3 fw-semibold preset-btn rounded-pill" id="btnPreset24" onclick="window.applyEmiPreset(24)" style="font-size: 12px;">
                                24 EMIs (2nd Year)
                            </button>
                            <?php endif; ?>
                            <?php if ($totEmi >= 36 && $totEmi != 24): ?>
                            <button type="button" class="btn btn-sm py-1.5 px-3 fw-semibold preset-btn rounded-pill" id="btnPreset36" onclick="window.applyEmiPreset(36)" style="font-size: 12px;">
                                36 EMIs (3rd Year)
                            </button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-sm py-1.5 px-3 fw-semibold preset-btn rounded-pill" id="btnPresetAll" onclick="window.applyEmiPreset(<?= $totEmi ?>)" style="font-size: 12px;">
                                All <?= $totEmi ?> Scheduled
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. 4 Primary KPI Summary Cards -->
    <div class="col-12 mb-4 kpi-cards-section no-print">
        <div class="row ">
            
            <!-- KPI 1: Total Scheduled EMIs -->
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card border-0 rounded-4 shadow-sm p-3 h-100 position-relative overflow-hidden" style="background: #ffffff; border: 1px solid #e5e7eb !important;">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge fw-bold text-uppercase px-2 py-1 rounded-2" style="background: rgba(25, 135, 84, 0.1); color: #198754; font-size: 11px; letter-spacing: 0.5px;">1. Total Scheduled</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: rgba(25, 135, 84, 0.12); color: #198754;">
                            <i class="fa-solid fa-calendar-days fs-14"></i>
                        </div>
                    </div>
                    <h2 class="fw-bold mb-1" style="color: #111827;"><span id="kpiTotalScheduled"><?= $totEmi ?></span> <span class="fs-6 text-muted fw-normal">EMIs</span></h2>
                    <div class="text-muted" style="font-size: 12px; line-height: 1.5;">
                        Total Construction Fee: <strong class="text-dark">৳<?= number_format($consFee) ?></strong><br>
                        Rate: <strong class="text-dark">৳<?= number_format($perEmiAmt) ?></strong> / month
                    </div>
                </div>
            </div>

            <!-- KPI 2: Requested EMIs for Payment -->
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card border-0 rounded-4 shadow-sm p-3 h-100 position-relative overflow-hidden" style="background: #ffffff; border: 1px solid #e5e7eb !important;">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge fw-bold text-uppercase px-2 py-1 rounded-2" style="background: rgba(14, 116, 144, 0.1); color: #0e7490; font-size: 11px; letter-spacing: 0.5px;">2. Payment Requested</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: rgba(14, 116, 144, 0.12); color: #0e7490;">
                            <i class="fa-solid fa-file-invoice-dollar fs-14"></i>
                        </div>
                    </div>
                    <h2 class="fw-bold mb-1" style="color: #0e7490;"><span id="kpiRequestedCount">0</span> <span class="fs-6 text-muted fw-normal">EMIs</span></h2>
                    <div class="text-muted" style="font-size: 12px; line-height: 1.5;">
                        Billed / Demanded: <strong class="text-dark" id="kpiRequestedAmount">৳0</strong><br>
                        Tenure Share: <strong style="color: #0e7490;" id="kpiRequestedPct">0%</strong> of agreement
                    </div>
                </div>
            </div>

            <!-- KPI 3: Paid EMIs (within requested) -->
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card border-0 rounded-4 shadow-sm p-3 h-100 position-relative overflow-hidden" style="background: #ffffff; border: 1px solid #e5e7eb !important;">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge fw-bold text-uppercase px-2 py-1 rounded-2" style="background: rgba(22, 163, 74, 0.1); color: #16a34a; font-size: 11px; letter-spacing: 0.5px;">3. Paid (Within Requested)</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: rgba(22, 163, 74, 0.12); color: #16a34a;">
                            <i class="fa-solid fa-circle-check fs-14"></i>
                        </div>
                    </div>
                    <h2 class="fw-bold mb-1" style="color: #16a34a;"><span id="kpiPaidCount">0</span> <span class="fs-6 text-muted fw-normal">EMIs</span></h2>
                    <div class="text-muted" style="font-size: 12px; line-height: 1.5;">
                        Total Collected: <strong class="text-success" id="kpiPaidAmount">৳0</strong><br>
                        Collection Rate: <strong class="text-success" id="kpiPaidRate">0%</strong> of requested
                    </div>
                </div>
            </div>

            <!-- KPI 4: Remaining Due EMIs & Outstanding Amount -->
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card border-0 rounded-4 shadow-sm p-3 h-100 position-relative overflow-hidden" style="background: #ffffff; border: 1px solid #fee2e2 !important;">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge fw-bold text-uppercase px-2 py-1 rounded-2" style="background: rgba(220, 38, 38, 0.1); color: #dc2626; font-size: 11px; letter-spacing: 0.5px;">4. Remaining Due & Outstanding</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: rgba(220, 38, 38, 0.12); color: #dc2626;">
                            <i class="fa-solid fa-triangle-exclamation fs-14"></i>
                        </div>
                    </div>
                    <h2 class="fw-bold mb-1" style="color: #dc2626;"><span id="kpiDueCount">0</span> <span class="fs-6 text-muted fw-normal">EMIs Due</span></h2>
                    <div class="text-muted" style="font-size: 12px; line-height: 1.5;">
                        Outstanding Amount: <strong class="fs-5 d-inline-block" style="color: #dc2626;" id="kpiOutstandingAmount">৳0</strong><br>
                        <span id="kpiDueDetailsText" class="text-secondary" style="font-size: 11px;">--</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- 3. Multi-Segment Progress Track Bar -->
    <div class="col-12 mb-4 progress-bar-card no-print">
        <div class="card border-0 shadow-sm rounded-4 bg-white p-3.5" style="border: 1px solid #e5e7eb !important;">
            <div class="d-flex justify-content-between align-items-center mb-2" style="font-size: 13px;">
                <span class="fw-bold text-dark"><i class="fa-solid fa-bars-progress me-1.5" style="color: var(--clr-theme-primary, #198754);"></i> Installment Fulfillment Progress:</span>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-success fw-semibold"><i class="fa-solid fa-square me-1"></i> Paid: <strong id="barPaidText">0</strong></span>
                    <span class="text-danger fw-semibold"><i class="fa-solid fa-square me-1"></i> Due/Overdue: <strong id="barDueText">0</strong></span>
                    <span class="text-secondary fw-semibold"><i class="fa-solid fa-square me-1"></i> Future: <strong id="barFutureText">0</strong></span>
                </div>
            </div>
            <div class="progress" style="height: 12px; border-radius: 6px; background-color: #f1f5f9;">
                <div class="progress-bar" role="progressbar" id="progressBarPaid" style="width: 0%; background-color: #16a34a;" title="Paid EMIs"></div>
                <div class="progress-bar" role="progressbar" id="progressBarDue" style="width: 0%; background-color: #ef4444;" title="Due / Overdue EMIs"></div>
                <div class="progress-bar" role="progressbar" id="progressBarFuture" style="width: 0%; background-color: #cbd5e1;" title="Future Unrequested EMIs"></div>
            </div>
        </div>
    </div>

    <!-- 4. Detailed Breakdown Table Header & Filters -->
    <div class="col-12 mb-4 print-statement-container">
        <!-- PRINT STATEMENT HEADER (Appears ONLY in Print) -->
        <div class="print-statement-header">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #198754; padding-bottom: 6px; margin-bottom: 8px;">
                <div>
                    <h2 style="margin: 0; font-size: 18px; font-weight: 800; color: #198754; text-transform: uppercase; letter-spacing: 0.5px;">
                        THE PREMIUM HOMES LTD.
                    </h2>
                    <div style="font-size: 10px; color: #475569; margin-top: 2px; line-height: 1.35;">
                        Corporate Office: 23/2,Land View Commercial Center, 9th Floor 28 Gulshan North C/A, Gulshan Circle-2, Dhaka<br>
                        Phone: 8801958253300 | Website: https://dpremiumhomes.com | Email: info@dpremiumhomes.com
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="display: inline-block; background: #198754; color: #ffffff; font-weight: 800; font-size: 11px; padding: 3px 10px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.5px; -webkit-print-color-adjust: exact; print-color-adjust: exact;">
                        EMI PAYMENT STATEMENT
                    </div>
                    <div style="font-size: 9.5px; color: #64748b; margin-top: 3px;">
                        Date: <?= date('d M, Y h:i A') ?>
                    </div>
                </div>
            </div>

            <!-- Client & Agreement Snapshot (Compact 2-row table) -->
            <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; margin-bottom: 10px; font-size: 10.5px; -webkit-print-color-adjust: exact; print-color-adjust: exact;">
                <table style="width: 100%; border-collapse: collapse; line-height: 1.5;">
                    <tr>
                        <td style="width: 25%;"><strong>Client:</strong> <?= $clientName ?></td>
                        <td style="width: 25%;"><strong>Booking ID:</strong> #<?= $bId ?> (<?= !empty($clientDetails['is_shop']) ? 'Shop' : 'Flat' ?>)</td>
                        <td style="width: 25%;"><strong>Project:</strong> <?= $projectName ?> (<?= $unitName ?>)</td>
                        <td style="width: 25%;"><strong>Phone:</strong> <?= $phone ?></td>
                    </tr>
                    <tr>
                        <td><strong>Total Scheduled:</strong> <span id="printTotalScheduled"><?= $totEmi ?> EMIs (৳<?= number_format($consFee) ?>)</span></td>
                        <td><strong>Payment Requested:</strong> <span id="printRequestedSummary">--</span></td>
                        <td><strong>Total Collected:</strong> <strong style="color: #16a34a;" id="printPaidSummary">--</strong></td>
                        <td><strong>Remaining Due:</strong> <strong style="color: #dc2626;" id="printDueSummary">--</strong></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden" style="border: 1px solid #e5e7eb !important;">
            <div class="card-header card-header-screen bg-white py-3 px-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-table-list me-2" style="color: var(--clr-theme-primary, #198754);"></i> Installment Breakdown Table</h5>
                    <span class="badge px-2.5 py-1" style="background: rgba(25, 135, 84, 0.1); color: var(--clr-theme-primary, #198754); font-size: 11.5px; border-radius: 6px;" id="badgeTableScope">Requested EMIs (1 - 0)</span>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap no-print">
                    <!-- Filter pills -->
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary active table-filter-btn" id="filterAllReq" onclick="window.filterEmiTable('requested')">
                            Requested (<span id="cntFilterReq">0</span>)
                        </button>
                        <button type="button" class="btn btn-outline-danger table-filter-btn" id="filterDue" onclick="window.filterEmiTable('due')">
                            Due Only (<span id="cntFilterDue">0</span>)
                        </button>
                        <button type="button" class="btn btn-outline-success table-filter-btn" id="filterPaid" onclick="window.filterEmiTable('paid')">
                            Paid Only (<span id="cntFilterPaid">0</span>)
                        </button>
                        <button type="button" class="btn btn-outline-secondary table-filter-btn" id="filterAllSched" onclick="window.filterEmiTable('all')">
                            All <?= $totEmi ?> Scheduled
                        </button>
                    </div>
                    <!-- Search in table -->
                    <div class="input-group input-group-sm" style="width: 180px;">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" class="form-control border-start-0" id="searchEmiTable" placeholder="Search date, no..." onkeyup="window.searchEmiTable(this.value)">
                    </div>
                </div>
            </div>

            <!-- Table Body -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="emiTrackingDetailTable" style="font-size: 13px;">
                    <thead style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <tr>
                            <th class="text-center text-muted fw-bold col-emi" style="width: 70px; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px;">EMI #</th>
                            <th class="text-muted fw-bold col-date" style="font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px;">Due Date</th>
                            <th class="text-end text-muted fw-bold col-sched" style="font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px;">Scheduled Amount</th>
                            <th class="text-end text-muted fw-bold col-paid" style="font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px;">Paid Amount</th>
                            <th class="text-end text-muted fw-bold col-due" style="font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px;">Remaining Due</th>
                            <th class="text-center text-muted fw-bold col-pstatus" style="width: 130px; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px;">Payment Status</th>
                            <th class="text-center text-muted fw-bold col-mstatus" style="width: 130px; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px;">Maturity Status</th>
                            <th class="text-center text-muted fw-bold col-followup" style="width: 105px; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px;">Follow-up</th>
                        </tr>
                    </thead>
                    <tbody id="emiTrackingTableBody">
                        <!-- Populated by JavaScript -->
                    </tbody>
                    <tfoot class="fw-bold" id="emiTrackingTableFooter" style="background-color: #f8fafc; border-top: 2px solid #e2e8f0;">
                        <tr>
                            <td colspan="2" class="text-end text-muted text-uppercase" style="font-size: 12px; letter-spacing: 0.5px;">Summary Total:</td>
                            <td class="text-end text-dark" id="footSumSched">৳0</td>
                            <td class="text-end text-success" id="footSumPaid">৳0</td>
                            <td class="text-end text-danger fs-6" id="footSumDue">৳0</td>
                            <td colspan="3" class="text-muted extra-small" id="footScopeDesc">--</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        </div>
    </div>

</div>

<!-- ===== EMI FOLLOW UP MODAL ===== -->
<div class="modal custom-modal fade" id="emiFollowupModal" tabindex="-1" role="dialog" aria-labelledby="emiFollowupModalLabel" aria-hidden="true" style="z-index: 106000 !important;">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="z-index: 106001 !important;">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header py-3 px-4 text-white" style="background: linear-gradient(135deg, #135737 0%, var(--clr-theme-primary, #198754) 100%);">
                <h5 class="modal-title text-white fw-bold" id="emiFollowupModalLabel">
                    <i class="fa-solid fa-comment-dots me-2"></i>EMI Follow Up Notes
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div class="row g-4">
                    <div class="col-md-5 border-end pe-md-4">
                        <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-plus-circle me-1" style="color: var(--clr-theme-primary, #198754);"></i> Add Follow-up Note</h6>
                        <input type="hidden" id="followupBookingId" value="<?= $bId ?>">
                        <input type="hidden" id="followupEmiNo" value="0">
                        
                        <div class="mb-3">
                            <label class="form-label fs-13 fw-semibold text-muted">Installment</label>
                            <input type="text" id="followupEmiDisplay" class="form-control form-control-sm fw-bold bg-white" readonly value="EMI #">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fs-13 fw-semibold text-muted">Date of Activity <span class="text-danger">*</span></label>
                            <input type="date" id="followupActivityDate" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fs-13 fw-semibold text-muted">Mode of Activity <span class="text-danger">*</span></label>
                            <select id="followupActivityMode" class="form-select form-select-sm" required>
                                <option value="">Select Mode</option>
                                <option value="Phone Call" selected>Phone Call</option>
                                <option value="WhatsApp">WhatsApp</option>
                                <option value="SMS">SMS</option>
                                <option value="Email">Email</option>
                                <option value="Meeting">Meeting</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fs-13 fw-semibold text-muted">Call Status</label>
                            <select id="followupCallStatus" class="form-select form-select-sm">
                                <option value="Outgoing" selected>Outgoing</option>
                                <option value="Incoming">Incoming</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fs-13 fw-semibold text-muted">Reason for Activity <span class="text-danger">*</span></label>
                            <select id="followupActivityReason" class="form-select form-select-sm" required>
                                <option value="EMI Payment Follow-up" selected>EMI Payment Follow-up</option>
                                <option value="Payment Reminder (General)">Payment Reminder (General)</option>
                                <option value="Overdue Payment Follow-up">Overdue Payment Follow-up</option>
                                <option value="Commitment Date Extension">Commitment Date Extension</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fs-13 fw-semibold text-muted">Note <span class="text-danger">*</span></label>
                            <textarea id="followupNoteInput" class="form-control" rows="3" placeholder="Type your follow up note here..." required></textarea>
                        </div>
                        <button type="button" class="btn w-100 fw-bold shadow-sm text-white" onclick="window.submitEmiFollowup()" style="background-color: var(--clr-theme-primary, #198754); border-color: var(--clr-theme-primary, #198754);">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Note
                        </button>
                    </div>
                    <div class="col-md-7 ps-md-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-clock-rotate-left me-1" style="color: var(--clr-theme-primary, #198754);"></i> Follow Up History</h6>
                            <span class="badge bg-secondary-subtle text-secondary" id="followupCountBadge">0 entries</span>
                        </div>
                        <div id="followupHistoryContainer" style="max-height: 400px; overflow-y: auto;" class="pe-1">
                            <div class="text-center text-muted py-4">Loading history...</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white py-2 px-4">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════════ -->
<!-- ══ JAVASCRIPT ENGINE ══ -->
<!-- ═══════════════════════════════════════════════════════════════════════════════ -->
<script>
(function() {
    const vScheduleData = <?= json_encode($vSchedule ?? []) ?>;
    const totalScheduledCount = <?= (int)$totEmi ?>;
    const perEmiRate = <?= (float)$perEmiAmt ?>;
    const bookingMeta = {
        id: <?= (int)$bId ?>,
        client: <?= json_encode($clientName) ?>,
        project: <?= json_encode($projectName) ?>,
        unit: <?= json_encode($unitName) ?>,
        totalFee: <?= (float)$consFee ?>,
        perEmi: perEmiRate
    };

    let activeFilter = 'requested'; // 'requested' | 'due' | 'paid' | 'all'
    let currentRequestedCount = 1;
    let autoMaturedCount = 1;

    function fmtBDT(num) {
        const n = Math.round(Number(num || 0));
        return '৳' + n.toLocaleString('en-IN');
    }

    function getNormalizedSchedule() {
        if (Array.isArray(vScheduleData) && vScheduleData.length > 0) {
            return vScheduleData;
        }
        const list = [];
        for (let i = 1; i <= totalScheduledCount; i++) {
            list.push({
                emi_no: i,
                due_date: 'N/A',
                amount: perEmiRate,
                paid_amount: 0,
                due_amount: perEmiRate,
                status: 'unpaid',
                maturity_status: 'future',
                is_virtual: true,
                followup_count: 0
            });
        }
        return list;
    }

    const scheduleList = getNormalizedSchedule();

    function detectMaturedCount() {
        const now = new Date();
        const curYm = now.getFullYear() * 100 + (now.getMonth() + 1);
        let count = 0;
        scheduleList.forEach(emi => {
            if (emi.due_date && emi.due_date !== 'N/A') {
                const parts = emi.due_date.split('-');
                if (parts.length >= 2) {
                    const emiYm = parseInt(parts[0], 10) * 100 + parseInt(parts[1], 10);
                    if (emiYm <= curYm) {
                        count++;
                    }
                }
            }
        });
        if (count <= 0) count = <?= max(1, $completedCount) ?>;
        return Math.min(totalScheduledCount, Math.max(1, count));
    }

    autoMaturedCount = detectMaturedCount();
    currentRequestedCount = autoMaturedCount;

    function renderEmiTracking() {
        const reqCount = currentRequestedCount;
        const total = totalScheduledCount;

        const slider = document.getElementById('sliderRequestedEmis');
        const numInput = document.getElementById('numRequestedEmis');
        const badgeDisp = document.getElementById('badgeRequestedCountDisplay');
        const txtMatured = document.getElementById('txtMaturedPresetCount');

        if (slider) slider.value = reqCount;
        if (numInput) numInput.value = reqCount;
        if (badgeDisp) badgeDisp.innerText = `${reqCount} EMIs`;
        if (txtMatured) txtMatured.innerText = `${autoMaturedCount} EMIs`;

        let reqAmount = 0;
        let paidCount = 0;
        let paidAmount = 0;
        let dueCount = 0;
        let dueAmount = 0;
        let partialCount = 0;

        scheduleList.forEach((emi, idx) => {
            const emiNum = idx + 1;
            const amt = Number(emi.amount || perEmiRate);
            const paid = Number(emi.paid_amount || 0);
            const due = Number(emi.due_amount || 0);

            if (emiNum <= reqCount) {
                reqAmount += amt;
                paidAmount += paid;
                dueAmount += due;

                if (due <= 0 && paid > 0) {
                    paidCount++;
                } else if (due > 0) {
                    dueCount++;
                    if (paid > 0) partialCount++;
                }
            }
        });

        const kpiReqCount = document.getElementById('kpiRequestedCount');
        const kpiReqAmount = document.getElementById('kpiRequestedAmount');
        const kpiReqPct = document.getElementById('kpiRequestedPct');
        const kpiPaidCount = document.getElementById('kpiPaidCount');
        const kpiPaidAmount = document.getElementById('kpiPaidAmount');
        const kpiPaidRate = document.getElementById('kpiPaidRate');
        const kpiDueCount = document.getElementById('kpiDueCount');
        const kpiOutstandingAmount = document.getElementById('kpiOutstandingAmount');
        const kpiDueDetails = document.getElementById('kpiDueDetailsText');

        const reqPctVal = total > 0 ? Math.round((reqCount / total) * 100) : 0;
        const paidRateVal = reqAmount > 0 ? Math.round((paidAmount / reqAmount) * 100) : 0;

        if (kpiReqCount) kpiReqCount.innerText = reqCount;
        if (kpiReqAmount) kpiReqAmount.innerText = fmtBDT(reqAmount);
        if (kpiReqPct) kpiReqPct.innerText = `${reqPctVal}%`;

        if (kpiPaidCount) kpiPaidCount.innerText = paidCount;
        if (kpiPaidAmount) kpiPaidAmount.innerText = fmtBDT(paidAmount);
        if (kpiPaidRate) kpiPaidRate.innerText = `${paidRateVal}%`;

        if (kpiDueCount) kpiDueCount.innerText = dueCount;
        if (kpiOutstandingAmount) kpiOutstandingAmount.innerText = fmtBDT(dueAmount);
        if (kpiDueDetails) {
            if (partialCount > 0) {
                kpiDueDetails.innerText = `Includes ${partialCount} partially paid installment(s)`;
            } else if (dueCount > 0) {
                kpiDueDetails.innerText = `${dueCount} installment(s) pending payment`;
            } else {
                kpiDueDetails.innerText = `All ${reqCount} requested installments fully cleared ✓`;
            }
        }

        const printReq = document.getElementById('printRequestedSummary');
        const printPaid = document.getElementById('printPaidSummary');
        const printDue = document.getElementById('printDueSummary');

        if (printReq) printReq.innerText = `${reqCount} EMIs (${fmtBDT(reqAmount)})`;
        if (printPaid) printPaid.innerText = `${paidCount} EMIs (${fmtBDT(paidAmount)})`;
        if (printDue) printDue.innerText = `${dueCount} EMIs (${fmtBDT(dueAmount)})`;

        const barPaid = document.getElementById('progressBarPaid');
        const barDue = document.getElementById('progressBarDue');
        const barFuture = document.getElementById('progressBarFuture');
        const barPaidText = document.getElementById('barPaidText');
        const barDueText = document.getElementById('barDueText');
        const barFutureText = document.getElementById('barFutureText');

        const futureCount = Math.max(0, total - reqCount);
        const pctPaid = total > 0 ? (paidCount / total) * 100 : 0;
        const pctDue = total > 0 ? (dueCount / total) * 100 : 0;
        const pctFuture = total > 0 ? (futureCount / total) * 100 : 0;

        if (barPaid) barPaid.style.width = `${pctPaid}%`;
        if (barDue) barDue.style.width = `${pctDue}%`;
        if (barFuture) barFuture.style.width = `${pctFuture}%`;

        if (barPaidText) barPaidText.innerText = `${paidCount} (${Math.round(pctPaid)}%)`;
        if (barDueText) barDueText.innerText = `${dueCount} (${Math.round(pctDue)}%)`;
        if (barFutureText) barFutureText.innerText = `${futureCount} (${Math.round(pctFuture)}%)`;

        const cntFilterReq = document.getElementById('cntFilterReq');
        const cntFilterDue = document.getElementById('cntFilterDue');
        const cntFilterPaid = document.getElementById('cntFilterPaid');
        if (cntFilterReq) cntFilterReq.innerText = reqCount;
        if (cntFilterDue) cntFilterDue.innerText = dueCount;
        if (cntFilterPaid) cntFilterPaid.innerText = paidCount;

        renderTableRows();
    }

    function renderTableRows() {
        const tbody = document.getElementById('emiTrackingTableBody');
        if (!tbody) return;

        const reqCount = currentRequestedCount;
        const searchInput = (document.getElementById('searchEmiTable')?.value || '').toLowerCase().trim();
        const badgeScope = document.getElementById('badgeTableScope');

        if (badgeScope) {
            if (activeFilter === 'all') {
                badgeScope.innerText = `All Scheduled EMIs (1 - ${totalScheduledCount})`;
            } else if (activeFilter === 'due') {
                badgeScope.innerText = `Due / Unpaid within Requested (1 - ${reqCount})`;
            } else if (activeFilter === 'paid') {
                badgeScope.innerText = `Paid within Requested (1 - ${reqCount})`;
            } else {
                badgeScope.innerText = `All Requested Scope (1 - ${reqCount})`;
            }
        }

        let html = '';
        let sumSched = 0;
        let sumPaid = 0;
        let sumDue = 0;
        let visibleCount = 0;

        scheduleList.forEach((emi, idx) => {
            const emiNum = idx + 1;
            const isRequested = (emiNum <= reqCount);
            const amt = Number(emi.amount || perEmiRate);
            const paid = Number(emi.paid_amount || 0);
            const due = Number(emi.due_amount || 0);
            const isPaid = (due <= 0 && paid > 0);
            const isDue = (due > 0);
            const isPartial = (paid > 0 && due > 0);

            if (activeFilter === 'requested' && !isRequested) return;
            if (activeFilter === 'due' && (!isRequested || !isDue)) return;
            if (activeFilter === 'paid' && (!isRequested || !isPaid)) return;

            const dateStr = emi.due_date || '';
            const rowText = `#${emiNum} ${dateStr} ${amt} ${paid} ${due} ${emi.status} ${emi.maturity_status}`.toLowerCase();
            if (searchInput && !rowText.includes(searchInput)) return;

            visibleCount++;
            sumSched += amt;
            sumPaid += paid;
            sumDue += due;

            let statusBadge = '';
            if (isPaid) {
                statusBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold px-2 py-1"><i class="fa-solid fa-check me-1"></i>Paid</span>';
            } else if (isPartial) {
                statusBadge = '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-semibold px-2 py-1"><i class="fa-solid fa-adjust me-1"></i>Partial</span>';
            } else if (isRequested) {
                statusBadge = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-semibold px-2 py-1"><i class="fa-solid fa-circle-exclamation me-1"></i>Unpaid</span>';
            } else {
                statusBadge = '<span class="badge bg-light text-secondary border px-2 py-1">Future</span>';
            }

            let maturityBadge = '';
            const mStat = emi.maturity_status || 'future';
            if (mStat === 'paid' || mStat === 'matured') {
                maturityBadge = '<span class="badge bg-success-subtle text-success px-2 py-1"><i class="fa-solid fa-check-double me-1"></i>Cleared</span>';
            } else if (mStat === 'due') {
                maturityBadge = '<span class="badge bg-danger text-white fw-bold px-2 py-1"><i class="fa-solid fa-triangle-exclamation me-1"></i>Overdue</span>';
            } else if (mStat === 'pending') {
                maturityBadge = '<span class="badge bg-warning text-dark fw-bold px-2 py-1"><i class="fa-solid fa-clock me-1"></i>Current Month</span>';
            } else {
                maturityBadge = '<span class="badge bg-light text-muted border px-2 py-1">Future Installment</span>';
            }

            html += `
                <tr class="${!isRequested ? 'opacity-75 bg-light-subtle' : ''}">
                    <td class="text-center col-emi">
                        <strong class="fs-6 emi-number-tag" style="color: var(--clr-theme-primary, #198754);">#${emiNum}</strong>
                    </td>
                    <td class="col-date">
                        <div class="fw-semibold text-dark date-title">${dateStr !== 'N/A' ? dateStr : 'To be scheduled'}</div>
                        ${emi.is_virtual ? '<small class="text-muted proj-tag" style="font-size:9.5px;">(Projected)</small>' : '<small class="text-success conf-tag" style="font-size:9.5px;">(Confirmed)</small>'}
                    </td>
                    <td class="text-end fw-semibold text-dark col-sched">${fmtBDT(amt)}</td>
                    <td class="text-end text-success fw-bold col-paid">${fmtBDT(paid)}</td>
                    <td class="text-end col-due ${due > 0 ? 'text-danger fw-bold fs-6' : 'text-muted'}">${due > 0 ? fmtBDT(due) : '৳0'}</td>
                    <td class="text-center col-pstatus">${statusBadge}</td>
                    <td class="text-center col-mstatus">${maturityBadge}</td>
                    <td class="text-center col-followup">
                        <!-- Screen: Interactive Button -->
                        <button type="button" class="btn btn-sm py-0 px-2 fw-semibold no-print" style="font-size: 11px; height: 26px; border: 1px solid rgba(25, 135, 84, 0.4); color: #198754; background: rgba(25, 135, 84, 0.05); display: inline-flex; align-items: center; gap: 4px; border-radius: 6px;" onclick="window.openEmiFollowupModal(${bookingMeta.id}, ${emiNum})">
                            <i class="fa-solid fa-comment-dots"></i> <span>Notes</span> ${emi.followup_count > 0 ? '<span class="badge rounded-pill ms-1" style="background: #198754; color: #fff; font-size: 9px;">' + emi.followup_count + '</span>' : ''}
                        </button>
                        <!-- Print: Clean Text Tag -->
                        <span class="print-only">
                            ${emi.followup_count > 0 ? '<span class="badge bg-light text-dark border" style="padding:1px 3px;">' + emi.followup_count + ' Note' + (emi.followup_count > 1 ? 's' : '') + '</span>' : '<span class="text-muted">-</span>'}
                        </span>
                    </td>
                </tr>
            `;
        });

        if (visibleCount === 0) {
            html = '<tr><td colspan="8" class="text-center py-5 text-muted"><i class="fa-solid fa-filter fa-2x mb-2 d-block opacity-50"></i>No installments match the selected filter.</td></tr>';
        }

        tbody.innerHTML = html;

        const footSumSched = document.getElementById('footSumSched');
        const footSumPaid = document.getElementById('footSumPaid');
        const footSumDue = document.getElementById('footSumDue');
        const footScopeDesc = document.getElementById('footScopeDesc');

        if (footSumSched) footSumSched.innerText = fmtBDT(sumSched);
        if (footSumPaid) footSumPaid.innerText = fmtBDT(sumPaid);
        if (footSumDue) footSumDue.innerText = fmtBDT(sumDue);
        if (footScopeDesc) footScopeDesc.innerText = `${visibleCount} installment(s) shown in view`;
    }

    window.setRequestedEmis = function(val) {
        let v = parseInt(val, 10);
        if (isNaN(v)) v = 1;
        if (v < 1) v = 1;
        if (v > totalScheduledCount) v = totalScheduledCount;
        currentRequestedCount = v;

        document.querySelectorAll('.preset-btn').forEach(btn => {
            btn.classList.remove('active', 'btn-outline-primary');
            btn.classList.add('btn-outline-secondary');
        });

        if (v === autoMaturedCount) {
            const b = document.getElementById('btnPresetMatured');
            if (b) { b.classList.add('active', 'btn-outline-primary'); b.classList.remove('btn-outline-secondary'); }
        } else if (v === 12) {
            const b = document.getElementById('btnPreset12');
            if (b) { b.classList.add('active', 'btn-outline-primary'); b.classList.remove('btn-outline-secondary'); }
        } else if (v === 24) {
            const b = document.getElementById('btnPreset24');
            if (b) { b.classList.add('active', 'btn-outline-primary'); b.classList.remove('btn-outline-secondary'); }
        } else if (v === 36) {
            const b = document.getElementById('btnPreset36');
            if (b) { b.classList.add('active', 'btn-outline-primary'); b.classList.remove('btn-outline-secondary'); }
        } else if (v === totalScheduledCount) {
            const b = document.getElementById('btnPresetAll');
            if (b) { b.classList.add('active', 'btn-outline-primary'); b.classList.remove('btn-outline-secondary'); }
        }

        renderEmiTracking();
    };

    window.adjustRequestedEmis = function(delta) {
        window.setRequestedEmis(currentRequestedCount + delta);
    };

    window.applyEmiPreset = function(preset) {
        if (preset === 'matured') {
            window.setRequestedEmis(autoMaturedCount);
        } else {
            window.setRequestedEmis(preset);
        }
    };

    window.filterEmiTable = function(filter) {
        activeFilter = filter;
        document.querySelectorAll('.table-filter-btn').forEach(btn => btn.classList.remove('active'));
        if (filter === 'requested') document.getElementById('filterAllReq')?.classList.add('active');
        if (filter === 'due') document.getElementById('filterDue')?.classList.add('active');
        if (filter === 'paid') document.getElementById('filterPaid')?.classList.add('active');
        if (filter === 'all') document.getElementById('filterAllSched')?.classList.add('active');
        renderTableRows();
    };

    window.searchEmiTable = function(val) {
        renderTableRows();
    };

    window.copyEmiTrackingText = function() {
        const reqCount = currentRequestedCount;
        const total = totalScheduledCount;
        let reqAmount = 0, paidCount = 0, paidAmount = 0, dueCount = 0, dueAmount = 0;

        scheduleList.forEach((emi, idx) => {
            if (idx + 1 <= reqCount) {
                const amt = Number(emi.amount || perEmiRate);
                const paid = Number(emi.paid_amount || 0);
                const due = Number(emi.due_amount || 0);
                reqAmount += amt;
                paidAmount += paid;
                dueAmount += due;
                if (due <= 0 && paid > 0) paidCount++;
                else if (due > 0) dueCount++;
            }
        });

        const text = `*EMI Tracking Statement*\n` +
                     `Client: ${bookingMeta.client} (Booking #${bookingMeta.id})\n` +
                     `Project: ${bookingMeta.project} (${bookingMeta.unit})\n` +
                     `------------------------------------\n` +
                     `• Total Scheduled EMIs: ${total} (৳${Math.round(bookingMeta.totalFee).toLocaleString('en-IN')})\n` +
                     `• Payment Requested EMIs: ${reqCount} (৳${Math.round(reqAmount).toLocaleString('en-IN')})\n` +
                     `• Paid EMIs: ${paidCount} (৳${Math.round(paidAmount).toLocaleString('en-IN')})\n` +
                     `• Remaining Due EMIs: ${dueCount}\n` +
                     `• Total Outstanding Due: ৳${Math.round(dueAmount).toLocaleString('en-IN')}\n` +
                     `• Future Installments: ${total - reqCount} EMIs\n` +
                     `------------------------------------\n` +
                     `Generated on: ${new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })}`;

        navigator.clipboard.writeText(text).then(() => {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Copied to Clipboard!',
                    text: 'EMI tracking summary has been copied and is ready to share.',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                alert('Copied to clipboard!\n\n' + text);
            }
        }).catch(err => {
            prompt('Copy summary manually:', text);
        });
    };

    // Follow Up Handler
    window.openEmiFollowupModal = function(bookingId, emiNo) {
        document.getElementById('followupBookingId').value = bookingId;
        document.getElementById('followupEmiNo').value = emiNo;
        document.getElementById('followupEmiDisplay').value = `EMI #${emiNo}`;
        document.getElementById('followupNoteInput').value = '';
        
        const container = document.getElementById('followupHistoryContainer');
        if (container) container.innerHTML = '<div class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Loading history...</div>';
        
        const modalEl = document.getElementById('emiFollowupModal');
        if (modalEl && typeof bootstrap !== 'undefined') {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
        
        window.loadEmiFollowups(bookingId, emiNo);
    };

    window.loadEmiFollowups = function(bookingId, emiNo) {
        fetch(`<?= $baseUrl ?>/account-client/getEmiFollowups?booking_id=${bookingId}&emi_no=${emiNo}`)
            .then(res => res.json())
            .then(response => {
                const container = document.getElementById('followupHistoryContainer');
                const badge = document.getElementById('followupCountBadge');
                if (!container) return;

                if (response.success && Array.isArray(response.data)) {
                    if (badge) badge.innerText = `${response.data.length} entries`;
                    if (response.data.length === 0) {
                        container.innerHTML = '<div class="text-center text-muted py-4"><i class="fa-regular fa-comment-slash fa-2x mb-2 d-block opacity-50"></i>No follow up notes yet for this installment.</div>';
                        return;
                    }

                    let html = '';
                    response.data.forEach(note => {
                        html += `
                            <div class="card mb-2 shadow-sm border-0 bg-white rounded-3 p-3">
                                <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 11px;">
                                    <span class="fw-bold text-dark"><i class="fa-solid fa-user me-1 text-primary"></i> ${note.author_name || 'System'}</span>
                                    <span class="text-muted"><i class="fa-regular fa-clock me-1"></i> ${note.activity_date || ''}</span>
                                </div>
                                <div class="mb-2 d-flex flex-wrap gap-1" style="font-size: 10px;">
                                    ${note.activity_mode ? '<span class="badge bg-primary-subtle text-primary">' + note.activity_mode + '</span>' : ''}
                                    ${note.call_status ? '<span class="badge bg-info-subtle text-info">' + note.call_status + '</span>' : ''}
                                    ${note.activity_reason ? '<span class="badge bg-secondary-subtle text-secondary">' + note.activity_reason + '</span>' : ''}
                                </div>
                                <p class="mb-0 text-dark" style="font-size: 13px; line-height: 1.4;">${note.note || ''}</p>
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<div class="text-danger p-3">Failed to load follow-up history.</div>';
                }
            })
            .catch(() => {
                const container = document.getElementById('followupHistoryContainer');
                if (container) container.innerHTML = '<div class="text-danger p-3">Error connecting to server.</div>';
            });
    };

    window.submitEmiFollowup = function() {
        const bookingId = document.getElementById('followupBookingId').value;
        const emiNo = document.getElementById('followupEmiNo').value;
        const note = (document.getElementById('followupNoteInput').value || '').trim();
        const activityDate = document.getElementById('followupActivityDate').value;
        const activityMode = document.getElementById('followupActivityMode').value;
        const callStatus = document.getElementById('followupCallStatus').value;
        const activityReason = document.getElementById('followupActivityReason').value;

        if (!note || !activityDate || !activityMode || !activityReason) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Required Fields', text: 'Please fill in date, mode, reason and note.' });
            } else {
                alert('Please fill in date, mode, reason and note.');
            }
            return;
        }

        const formData = new FormData();
        formData.append('booking_id', bookingId);
        formData.append('emi_no', emiNo);
        formData.append('note', note);
        formData.append('activity_date', activityDate);
        formData.append('activity_mode', activityMode);
        formData.append('call_status', callStatus);
        formData.append('activity_reason', activityReason);

        fetch(`<?= $baseUrl ?>/account-client/saveEmiFollowup`, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('followupNoteInput').value = '';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'success', title: 'Saved!', text: 'Follow-up note recorded.', timer: 1500, showConfirmButton: false });
                }
                window.loadEmiFollowups(bookingId, emiNo);

                // Update follow up count in schedule list locally
                const targetEmi = scheduleList.find(e => Number(e.emi_no) === Number(emiNo));
                if (targetEmi) {
                    targetEmi.followup_count = (targetEmi.followup_count || 0) + 1;
                    renderTableRows();
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to save note' });
                } else {
                    alert(data.message || 'Failed to save note');
                }
            }
        })
        .catch(() => {
            alert('Failed to connect to server.');
        });
    };

    // Auto initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        renderEmiTracking();
    });
})();
</script>
