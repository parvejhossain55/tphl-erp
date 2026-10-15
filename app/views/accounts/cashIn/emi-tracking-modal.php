<?php
/**
 * EMI Payment & Maturity Tracking Modal
 * 
 * Provides an interactive breakdown of:
 * - Total Scheduled EMIs (e.g., 48 or 36)
 * - Requested EMIs for Payment (e.g., 24 or auto-calculated matured-to-date)
 * - Paid EMIs count & collected amount
 * - Remaining Due EMIs count & Outstanding Amount
 * - Future unrequested installments
 * - Detailed table with interactive filters and export/copy
 */

$vSchedule = $virtualEmiSchedule ?? [];
$totEmi = (int)($paymentSummary['total_emi'] ?? (count($vSchedule) > 0 ? count($vSchedule) : 36));
if ($totEmi <= 0) $totEmi = 36;
$completedCount = (int)($paymentSummary['completed_emi'] ?? 0);
$perEmiAmt = (float)($paymentSummary['per_emi_amount'] ?? 0);
$consFee = (float)($paymentSummary['construction_fee'] ?? 0);
$bookingId = (int)($clientDetails['booking_id'] ?? 0);
$clientName = htmlspecialchars($clientDetails['client_name'] ?? 'Client');
$projectName = htmlspecialchars($clientDetails['project_name'] ?? 'Project');
$unitName = htmlspecialchars($clientDetails['unit_name'] ?? 'Unit');
?>

<!-- ═══════════════════════════════════════════════════════════════════════════════ -->
<!-- ══ EMI TRACKING & MATURITY MODAL ══ -->
<!-- ═══════════════════════════════════════════════════════════════════════════════ -->
<div class="modal custom-modal fade" id="emiTrackingModal" tabindex="-1" role="dialog" aria-labelledby="emiTrackingModalLabel" aria-hidden="true" style="z-index: 105000 !important;">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="z-index: 105001 !important;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            
            <!-- Modal Header -->
            <div class="modal-header py-3 px-4 text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-bottom: 2px solid #3b82f6;">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: rgba(59, 130, 246, 0.2); color: #60a5fa; font-size: 20px;">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h5 class="modal-title fw-bold mb-0 text-white" id="emiTrackingModalLabel">EMI Payment & Maturity Tracking</h5>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold" style="font-size: 11px;">Booking #<?= $bookingId ?></span>
                        </div>
                        <small class="text-white-50">
                            <strong><?= $clientName ?></strong> &bull; <?= $projectName ?> (<?= $unitName ?>)
                        </small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="<?= $GLOBALS['base_url'] ?>/client-emi-tracking/<?= $bookingId ?>" class="btn btn-sm btn-primary rounded-pill px-3 py-1 text-white border-0 fw-semibold" style="background: #2563eb;" title="Open as Full Dedicated Page">
                        <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Full Page
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-light rounded-pill px-3 py-1" onclick="window.printEmiTrackingSummary()" title="Print Summary Report">
                        <i class="fa-solid fa-print me-1"></i> Print
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-3 py-1 text-white" onclick="window.copyEmiTrackingText()" title="Copy Summary for WhatsApp/Email">
                        <i class="fa-solid fa-copy me-1"></i> Copy
                    </button>
                    <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4 bg-light">

                <!-- 1. Interactive Requested EMI Control Card -->
                <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden" style="background: #ffffff; border-left: 5px solid #3b82f6 !important;">
                    <div class="card-body p-3">
                        <div class="row align-items-center ">
                            <div class="col-lg-5 col-md-6">
                                <label class="form-label text-dark fw-bold mb-1 d-flex align-items-center gap-1" style="font-size: 13px;">
                                    <i class="fa-solid fa-sliders text-primary"></i> Requested EMIs for Payment:
                                    <span class="badge bg-primary text-white ms-1" id="badgeRequestedCountDisplay" style="font-size: 12px;">--</span>
                                    <span class="text-muted fw-normal" style="font-size: 11px;">of <?= $totEmi ?> total scheduled</span>
                                </label>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <button type="button" class="btn btn-sm btn-outline-secondary px-2 py-1" onclick="window.adjustRequestedEmis(-1)" title="Decrease by 1">
                                        <i class="fa-solid fa-minus"></i>
                                    </button>
                                    <input type="range" class="form-range flex-grow-1" id="sliderRequestedEmis" min="1" max="<?= $totEmi ?>" value="1" oninput="window.setRequestedEmis(this.value)">
                                    <button type="button" class="btn btn-sm btn-outline-secondary px-2 py-1" onclick="window.adjustRequestedEmis(1)" title="Increase by 1">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                    <div class="input-group input-group-sm" style="width: 80px;">
                                        <input type="number" class="form-control text-center fw-bold text-primary" id="numRequestedEmis" min="1" max="<?= $totEmi ?>" value="1" onchange="window.setRequestedEmis(this.value)">
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-7 col-md-6">
                                <label class="form-label text-muted extra-small mb-1 d-block">Quick Presets / Scenarios:</label>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <button type="button" class="btn btn-xs btn-outline-primary active py-1 px-2 fw-semibold preset-btn" id="btnPresetMatured" onclick="window.applyEmiPreset('matured')" style="font-size: 11px;">
                                        <i class="fa-solid fa-calendar-day me-1"></i> Up to Today (<span id="txtMaturedPresetCount">--</span>)
                                    </button>
                                    <?php if ($totEmi >= 12): ?>
                                    <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2 fw-semibold preset-btn" id="btnPreset12" onclick="window.applyEmiPreset(12)" style="font-size: 11px;">
                                        12 EMIs (1st Year)
                                    </button>
                                    <?php endif; ?>
                                    <?php if ($totEmi >= 24): ?>
                                    <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2 fw-semibold preset-btn" id="btnPreset24" onclick="window.applyEmiPreset(24)" style="font-size: 11px;">
                                        24 EMIs (2nd Year)
                                    </button>
                                    <?php endif; ?>
                                    <?php if ($totEmi >= 36 && $totEmi != 24): ?>
                                    <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2 fw-semibold preset-btn" id="btnPreset36" onclick="window.applyEmiPreset(36)" style="font-size: 11px;">
                                        36 EMIs
                                    </button>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2 fw-semibold preset-btn" id="btnPresetAll" onclick="window.applyEmiPreset(<?= $totEmi ?>)" style="font-size: 11px;">
                                        All <?= $totEmi ?> Scheduled
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. 4 Primary KPI Summary Cards -->
                <div class="row  mb-4">
                    
                    <!-- KPI 1: Total Scheduled EMIs -->
                    <div class="col-xl-3 col-lg-6 col-md-6">
                        <div class="p-3 bg-white rounded-3 shadow-sm border border-primary-subtle h-100 position-relative overflow-hidden">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-primary-subtle text-primary fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">1. Total Scheduled</span>
                                <div class="rounded-circle p-2 bg-primary-subtle text-primary" style="width: 32px; height: 32px; display:flex; align-items:center; justify-content:center;">
                                    <i class="fa-solid fa-calendar-days fs-14"></i>
                                </div>
                            </div>
                            <h3 class="fw-bold text-dark mb-1"><span id="kpiTotalScheduled"><?= $totEmi ?></span> <span class="fs-6 text-muted fw-normal">EMIs</span></h3>
                            <div class="extra-small text-muted" style="font-size: 11px; line-height: 1.4;">
                                Total Construction Fee: <strong class="text-dark">৳<?= number_format($consFee) ?></strong><br>
                                Rate: <strong class="text-dark">৳<?= number_format($perEmiAmt) ?></strong> / month
                            </div>
                        </div>
                    </div>

                    <!-- KPI 2: Requested EMIs for Payment -->
                    <div class="col-xl-3 col-lg-6 col-md-6">
                        <div class="p-3 bg-white rounded-3 shadow-sm border border-info-subtle h-100 position-relative overflow-hidden">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-info-subtle text-info fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">2. Payment Requested</span>
                                <div class="rounded-circle p-2 bg-info-subtle text-info" style="width: 32px; height: 32px; display:flex; align-items:center; justify-content:center;">
                                    <i class="fa-solid fa-file-invoice-dollar fs-14"></i>
                                </div>
                            </div>
                            <h3 class="fw-bold text-info mb-1"><span id="kpiRequestedCount">0</span> <span class="fs-6 text-muted fw-normal">EMIs</span></h3>
                            <div class="extra-small text-muted" style="font-size: 11px; line-height: 1.4;">
                                Billed / Demanded: <strong class="text-dark" id="kpiRequestedAmount">৳0</strong><br>
                                Tenure Share: <strong class="text-info" id="kpiRequestedPct">0%</strong> of agreement
                            </div>
                        </div>
                    </div>

                    <!-- KPI 3: Paid EMIs (within requested) -->
                    <div class="col-xl-3 col-lg-6 col-md-6">
                        <div class="p-3 bg-white rounded-3 shadow-sm border border-success-subtle h-100 position-relative overflow-hidden">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-success-subtle text-success fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">3. Paid (Within Requested)</span>
                                <div class="rounded-circle p-2 bg-success-subtle text-success" style="width: 32px; height: 32px; display:flex; align-items:center; justify-content:center;">
                                    <i class="fa-solid fa-circle-check fs-14"></i>
                                </div>
                            </div>
                            <h3 class="fw-bold text-success mb-1"><span id="kpiPaidCount">0</span> <span class="fs-6 text-muted fw-normal">EMIs</span></h3>
                            <div class="extra-small text-muted" style="font-size: 11px; line-height: 1.4;">
                                Total Collected: <strong class="text-success" id="kpiPaidAmount">৳0</strong><br>
                                Collection Rate: <strong class="text-success" id="kpiPaidRate">0%</strong> of requested
                            </div>
                        </div>
                    </div>

                    <!-- KPI 4: Remaining Due EMIs & Outstanding Amount -->
                    <div class="col-xl-3 col-lg-6 col-md-6">
                        <div class="p-3 bg-white rounded-3 shadow-sm border border-danger-subtle h-100 position-relative overflow-hidden" style="background: linear-gradient(180deg, #ffffff 0%, #fff5f5 100%);">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-danger text-white fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">4. Remaining Due & Outstanding</span>
                                <div class="rounded-circle p-2 bg-danger-subtle text-danger" style="width: 32px; height: 32px; display:flex; align-items:center; justify-content:center;">
                                    <i class="fa-solid fa-triangle-exclamation fs-14"></i>
                                </div>
                            </div>
                            <h3 class="fw-bold text-danger mb-1"><span id="kpiDueCount">0</span> <span class="fs-6 text-muted fw-normal">EMIs Due</span></h3>
                            <div class="extra-small text-muted" style="font-size: 11px; line-height: 1.4;">
                                Outstanding Amount: <strong class="text-danger fs-6 d-inline-block" id="kpiOutstandingAmount">৳0</strong><br>
                                <span id="kpiDueDetailsText" class="text-secondary" style="font-size: 10px;">--</span>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- 3. Multi-Segment Progress Track Bar -->
                <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2" style="font-size: 12px;">
                        <span class="fw-bold text-dark"><i class="fa-solid fa-bars-progress me-1 text-primary"></i> Installment Fulfillment Progress:</span>
                        <div class="d-flex align-items-center gap-3">
                            <span class="text-success extra-small"><i class="fa-solid fa-square me-1"></i> Paid: <strong id="barPaidText">0</strong></span>
                            <span class="text-danger extra-small"><i class="fa-solid fa-square me-1"></i> Due/Overdue: <strong id="barDueText">0</strong></span>
                            <span class="text-secondary extra-small"><i class="fa-solid fa-square me-1"></i> Future: <strong id="barFutureText">0</strong></span>
                        </div>
                    </div>
                    <div class="progress" style="height: 12px; border-radius: 6px; background-color: #e2e8f0;">
                        <div class="progress-bar bg-success" role="progressbar" id="progressBarPaid" style="width: 0%;" title="Paid EMIs"></div>
                        <div class="progress-bar bg-danger" role="progressbar" id="progressBarDue" style="width: 0%;" title="Due / Overdue EMIs"></div>
                        <div class="progress-bar bg-secondary opacity-50" role="progressbar" id="progressBarFuture" style="width: 0%;" title="Future Unrequested EMIs"></div>
                    </div>
                </div>

                <!-- 4. Detailed Breakdown Table Header & Filters -->
                <div class="card border-0 shadow-sm rounded-3 bg-white overflow-hidden">
                    <div class="card-header bg-white py-3 px-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-table-list me-1 text-primary"></i> Installment Breakdown Table</h6>
                            <span class="badge bg-secondary-subtle text-secondary" id="badgeTableScope">Requested EMIs (1 - 0)</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
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
                            <div class="input-group input-group-sm" style="width: 150px;">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                                <input type="text" class="form-control border-start-0" id="searchEmiTable" placeholder="Filter..." onkeyup="window.searchEmiTable(this.value)">
                            </div>
                        </div>
                    </div>

                    <!-- 5. Table Body -->
                    <div class="table-responsive" style="max-height: 420px;">
                        <table class="table table-hover table-striped align-middle mb-0" id="emiTrackingDetailTable" style="font-size: 13px;">
                            <thead class="table-light sticky-top" style="z-index: 10;">
                                <tr>
                                    <th class="text-center" style="width: 70px;">EMI #</th>
                                    <th>Due Date</th>
                                    <th class="text-end">Scheduled Amount</th>
                                    <th class="text-end">Paid Amount</th>
                                    <th class="text-end">Remaining Due</th>
                                    <th class="text-center" style="width: 130px;">Payment Status</th>
                                    <th class="text-center" style="width: 140px;">Maturity Status</th>
                                    <th class="text-center no-print" style="width: 90px;">Follow-up</th>
                                </tr>
                            </thead>
                            <tbody id="emiTrackingTableBody">
                                <!-- Populated by JS -->
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="2" class="text-end">Summary Total:</td>
                                    <td class="text-end text-dark" id="footSumScheduled">৳0</td>
                                    <td class="text-end text-success" id="footSumPaid">৳0</td>
                                    <td class="text-end text-danger fs-6" id="footSumDue">৳0</td>
                                    <td colspan="3" class="text-center text-muted extra-small" id="footSumNotes">--</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-white py-2 px-4 d-flex justify-content-between align-items-center">
                <div class="extra-small text-muted">
                    <i class="fa-solid fa-info-circle me-1 text-primary"></i> 
                    Outstanding amount represents actual unpaid installments within the selected requested scope.
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.copyEmiTrackingText()">
                        <i class="fa-solid fa-copy me-1"></i> Copy Summary
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════════ -->
<!-- ══ EMI TRACKING JAVASCRIPT ENGINE ══ -->
<!-- ═══════════════════════════════════════════════════════════════════════════════ -->
<script>
(function() {
    // Inject server schedule data safely
    const vScheduleData = <?= json_encode($vSchedule ?? []) ?>;
    const totalScheduledCount = <?= (int)$totEmi ?>;
    const perEmiRate = <?= (float)$perEmiAmt ?>;
    const bookingMeta = {
        id: <?= (int)$bookingId ?>,
        client: <?= json_encode($clientName) ?>,
        project: <?= json_encode($projectName) ?>,
        unit: <?= json_encode($unitName) ?>,
        totalFee: <?= (float)$consFee ?>,
        perEmi: perEmiRate
    };

    let activeFilter = 'requested'; // 'requested' | 'due' | 'paid' | 'all'
    let currentRequestedCount = 1;
    let autoMaturedCount = 1;

    // Helper: Format BDT currency
    function fmtBDT(num) {
        const n = Math.round(Number(num || 0));
        return '৳' + n.toLocaleString('en-IN');
    }

    // Helper: Normalize schedule item
    function getNormalizedSchedule() {
        if (Array.isArray(vScheduleData) && vScheduleData.length > 0) {
            return vScheduleData;
        }
        // Fallback generator if empty
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

    // Determine default matured count up to today's date
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

    // Recalculate & Render Tracking View
    function renderEmiTracking() {
        const reqCount = currentRequestedCount;
        const total = totalScheduledCount;

        // Update inputs
        const slider = document.getElementById('sliderRequestedEmis');
        const numInput = document.getElementById('numRequestedEmis');
        const badgeReq = document.getElementById('badgeRequestedCountDisplay');
        const txtMat = document.getElementById('txtMaturedPresetCount');

        if (slider) slider.value = reqCount;
        if (numInput) numInput.value = reqCount;
        if (badgeReq) badgeReq.innerText = `${reqCount} EMIs`;
        if (txtMat) txtMat.innerText = `${autoMaturedCount} EMIs`;

        // Calculate metrics
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

        // KPI updates
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

        // Progress bar
        const futureCount = Math.max(0, total - reqCount);
        const pPaid = Math.round((paidCount / total) * 100);
        const pDue = Math.round((dueCount / total) * 100);
        const pFuture = Math.max(0, 100 - pPaid - pDue);

        const barPaid = document.getElementById('progressBarPaid');
        const barDue = document.getElementById('progressBarDue');
        const barFuture = document.getElementById('progressBarFuture');
        const txtBPaid = document.getElementById('barPaidText');
        const txtBDue = document.getElementById('barDueText');
        const txtBFuture = document.getElementById('barFutureText');

        if (barPaid) barPaid.style.width = `${pPaid}%`;
        if (barDue) barDue.style.width = `${pDue}%`;
        if (barFuture) barFuture.style.width = `${pFuture}%`;

        if (txtBPaid) txtBPaid.innerText = `${paidCount} (${pPaid}%)`;
        if (txtBDue) txtBDue.innerText = `${dueCount} (${pDue}%)`;
        if (txtBFuture) txtBFuture.innerText = `${futureCount} (${pFuture}%)`;

        // Filter Counts
        const cntFilterReq = document.getElementById('cntFilterReq');
        const cntFilterDue = document.getElementById('cntFilterDue');
        const cntFilterPaid = document.getElementById('cntFilterPaid');
        if (cntFilterReq) cntFilterReq.innerText = reqCount;
        if (cntFilterDue) cntFilterDue.innerText = dueCount;
        if (cntFilterPaid) cntFilterPaid.innerText = paidCount;

        // Render Table Rows
        renderTableRows();
    }

    // Render Table Rows according to activeFilter & search
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

            // Filter check
            if (activeFilter === 'requested' && !isRequested) return;
            if (activeFilter === 'due' && (!isRequested || !isDue)) return;
            if (activeFilter === 'paid' && (!isRequested || !isPaid)) return;

            // Search filter
            const dateStr = emi.due_date || '';
            const rowText = `#${emiNum} ${dateStr} ${amt} ${paid} ${due} ${emi.status} ${emi.maturity_status}`.toLowerCase();
            if (searchInput && !rowText.includes(searchInput)) return;

            visibleCount++;
            sumSched += amt;
            sumPaid += paid;
            sumDue += due;

            // Status Badge
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

            // Maturity Badge
            let maturityBadge = '';
            const mStat = emi.maturity_status || 'future';
            if (mStat === 'paid' || mStat === 'matured') {
                maturityBadge = '<span class="badge bg-success-subtle text-success"><i class="fa-solid fa-check-double me-1"></i>Cleared</span>';
            } else if (mStat === 'due') {
                maturityBadge = '<span class="badge bg-danger text-white fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i>Overdue</span>';
            } else if (mStat === 'pending') {
                maturityBadge = '<span class="badge bg-warning text-dark fw-bold"><i class="fa-solid fa-clock me-1"></i>Current Month</span>';
            } else {
                maturityBadge = '<span class="badge bg-light text-muted border">Future Installment</span>';
            }

            // Scope row styling
            const trClass = !isRequested ? 'opacity-75 bg-light' : (isDue ? (isPartial ? 'table-warning-subtle' : '') : '');

            html += `
                <tr class="${trClass}">
                    <td class="text-center">
                        <strong class="${isRequested ? 'text-primary' : 'text-muted'}">#${emiNum}</strong>
                        ${!isRequested ? '<span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size:9px;">Unrequested</span>' : ''}
                    </td>
                    <td>
                        <div class="fw-semibold text-dark">${dateStr !== 'N/A' ? dateStr : 'To be scheduled'}</div>
                        ${emi.is_virtual ? '<small class="text-muted" style="font-size:10px;">(Projected Schedule)</small>' : '<small class="text-success" style="font-size:10px;">(Confirmed Schedule)</small>'}
                    </td>
                    <td class="text-end fw-semibold text-dark">${fmtBDT(amt)}</td>
                    <td class="text-end text-success fw-bold">${fmtBDT(paid)}</td>
                    <td class="text-end ${due > 0 ? 'text-danger fw-bold fs-13' : 'text-muted'}">${due > 0 ? fmtBDT(due) : '৳0'}</td>
                    <td class="text-center">${statusBadge}</td>
                    <td class="text-center">${maturityBadge}</td>
                    <td class="text-center no-print">
                        <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size: 11px;" onclick="window.openEmiFollowupModalFromTracking(${bookingMeta.id}, ${emiNum})">
                            <i class="fa-solid fa-comment-dots"></i> ${emi.followup_count > 0 ? emi.followup_count : ''}
                        </button>
                    </td>
                </tr>
            `;
        });

        if (visibleCount === 0) {
            html = '<tr><td colspan="8" class="text-center py-4 text-muted"><i class="fa-solid fa-filter fa-2x mb-2 d-block opacity-50"></i>No installments match the selected filter.</td></tr>';
        }

        tbody.innerHTML = html;

        // Update Table Footer Sums
        const footSumSched = document.getElementById('footSumScheduled');
        const footSumP = document.getElementById('footSumPaid');
        const footSumD = document.getElementById('footSumDue');
        const footNotes = document.getElementById('footSumNotes');

        if (footSumSched) footSumSched.innerText = fmtBDT(sumSched);
        if (footSumP) footSumP.innerText = fmtBDT(sumPaid);
        if (footSumD) footSumD.innerText = fmtBDT(sumDue);
        if (footNotes) footNotes.innerText = `${visibleCount} installment(s) shown in view`;
    }

    // Public API attached to window
    window.setRequestedEmis = function(val) {
        let v = parseInt(val, 10);
        if (isNaN(v)) v = 1;
        v = Math.max(1, Math.min(v, totalScheduledCount));
        currentRequestedCount = v;

        // Reset preset buttons styling
        document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active', 'btn-primary', 'btn-outline-primary'));
        document.querySelectorAll('.preset-btn').forEach(b => b.classList.add('btn-outline-secondary'));

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
            console.error('Clipboard error:', err);
            prompt('Copy summary manually:', text);
        });
    };

    window.openEmiFollowupModalFromTracking = function(bookingId, emiNo) {
        const fModal = document.getElementById('emiFollowupModal');
        if (fModal) {
            fModal.style.zIndex = '106000';
            fModal.style.position = 'fixed';
        }
        if (typeof openEmiFollowupModal === 'function') {
            openEmiFollowupModal(bookingId, emiNo);
            setTimeout(() => {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                if (backdrops.length > 1) {
                    backdrops[backdrops.length - 1].style.zIndex = '105990';
                }
            }, 60);
        } else {
            alert('Followup for EMI #' + emiNo);
        }
    };

    window.printEmiTrackingSummary = function() {
        window.print();
    };

    // Modal show listener: initialize view
    const modalEl = document.getElementById('emiTrackingModal');
    if (modalEl) {
        modalEl.addEventListener('show.bs.modal', function() {
            renderEmiTracking();
        });
    }

    // Auto initialize on load
    document.addEventListener('DOMContentLoaded', function() {
        renderEmiTracking();
    });
})();
</script>
