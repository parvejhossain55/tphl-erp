<?php
$base = $GLOBALS['base_url'];
?>
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h1 class="page-title fw-semibold fs-18 mb-0">New Labor Requisition</h1>
            <nav><ol class="breadcrumb mb-0 mt-1">
                <li class="breadcrumb-item"><a href="<?php echo $base; ?>/con-labor-requisitions">Labor Requisitions</a></li>
                <li class="breadcrumb-item active">New</li>
            </ol></nav>
        </div>
        <a href="<?php echo $base; ?>/con-labor-requisitions" class="btn btn-light btn-sm border">
            <i class="fa-solid fa-arrow-left me-1"></i> Back
        </a>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form action="<?php echo $base; ?>/con-labor-requisition-store" method="POST" enctype="multipart/form-data">
        <div class="row">

            <!-- LEFT: Main Form -->
            <div class="col-lg-8">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title fs-14"><i class="fa-solid fa-hard-hat me-2 text-warning"></i> Requisition Details</div>
                    </div>
                    <div class="card-body">

                        <div class="row">
                            <!-- Project -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold fs-13">Project <span class="text-danger">*</span></label>
                                <select name="project_id" id="projectSelect" class="form-select select2" required>
                                    <option value="">-- Select Project --</option>
                                    <?php foreach ($projects as $p): ?>
                                        <option value="<?php echo $p['id']; ?>">
                                            <?php echo htmlspecialchars($p['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Phase (optional) -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold fs-13">Phase <small class="text-muted">(Optional)</small></label>
                                <select name="phase_id" class="form-select select2">
                                    <option value="">-- Select Phase --</option>
                                    <?php foreach ($phases as $ph): ?>
                                        <option value="<?php echo $ph['id']; ?>">
                                            <?php echo htmlspecialchars($ph['phase_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Requisition Date -->
                            <div class="col-md-4">
                                <label class="form-label fw-semibold fs-13">Requisition Date <span class="text-danger">*</span></label>
                                <input type="date" name="requisition_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>

                            <!-- Work From / To -->
                            <div class="col-md-4">
                                <label class="form-label fw-semibold fs-13">Work Start Date</label>
                                <input type="date" name="work_date_from" class="form-control" id="workDateFrom">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold fs-13">Work End Date</label>
                                <input type="date" name="work_date_to" class="form-control" id="workDateTo">
                            </div>

                            <!-- Labor Type -->
                            <div class="col-md-4">
                                <label class="form-label fw-semibold fs-13">Labor Type <span class="text-danger">*</span></label>
                                <select name="labor_type" class="form-select" required>
                                    <option value="General">General</option>
                                    <option value="Skilled">Skilled</option>
                                    <option value="Unskilled">Unskilled</option>
                                    <option value="Mason">Mason</option>
                                    <option value="Helper">Helper</option>
                                    <option value="Supervisor">Supervisor</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <!-- Labor Count -->
                            <div class="col-md-4">
                                <label class="form-label fw-semibold fs-13">Number of Laborers <span class="text-danger">*</span></label>
                                <input type="number" name="labor_count" id="laborCount" class="form-control" min="1" value="1" required>
                            </div>

                            <!-- Per Labor Rate -->
                            <div class="col-md-4">
                                <label class="form-label fw-semibold fs-13">Rate per Labor/Day (৳) <span class="text-danger">*</span></label>
                                <input type="number" name="per_labor_rate" id="laborRate" class="form-control" step="0.01" min="0" value="0" required>
                            </div>

                            <!-- Working Days -->
                            <div class="col-md-4">
                                <label class="form-label fw-semibold fs-13">Working Days <span class="text-danger">*</span></label>
                                <input type="number" name="working_days" id="workingDays" class="form-control" step="0.5" min="0.5" value="1" required>
                            </div>

                            <!-- Total Amount (auto-calc) -->
                            <div class="col-md-8">
                                <label class="form-label fw-semibold fs-13">Estimated Total Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text fw-bold">৳</span>
                                    <input type="text" id="totalAmount" class="form-control fw-bold text-success fs-15 bg-light" readonly value="0.00">
                                </div>
                                <div class="form-text text-muted">= Laborers × Rate × Days (auto-calculated)</div>
                            </div>

                            <!-- Account Head -->
                            <div class="col-12 mt-3">
                                <?= renderCOADropdown($coaList ?? [], null, 'account_id', false, 'laborreq') ?>
                            </div>

                            <!-- Purpose -->
                            <div class="col-12">
                                <label class="form-label fw-semibold fs-13">Purpose / Work Description <span class="text-danger">*</span></label>
                                <textarea name="purpose" class="form-control" rows="3" placeholder="Describe the work to be done by these laborers..." required></textarea>
                            </div>

                            <!-- Remarks -->
                            <div class="col-12">
                                <label class="form-label fw-semibold fs-13">Remarks <small class="text-muted">(Optional)</small></label>
                                <textarea name="remarks" class="form-control" rows="2" placeholder="Any additional notes..."></textarea>
                            </div>

                            <!-- Attachment -->
                            <div class="col-12">
                                <label class="form-label fw-semibold fs-13">Supporting Document / Photo <small class="text-muted">(Optional)</small></label>
                                <input type="file" name="attachment" class="form-control" accept="image/*,application/pdf">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Summary -->
            <div class="col-lg-4">
                <div class="card custom-card">
                    <div class="card-header bg-warning-transparent">
                        <div class="card-title fs-14 text-warning"><i class="fa-solid fa-circle-info me-2"></i> Summary</div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3 p-3 rounded bg-light border text-center">
                            <div class="text-muted fs-12 text-uppercase fw-semibold mb-1">Estimated Total</div>
                            <div class="fs-24 fw-bold text-success" id="summaryTotal">৳ 0.00</div>
                            <div class="fs-11 text-muted mt-1" id="summaryBreakdown">0 laborer × ৳0 × 1 day</div>
                        </div>
                        <div class="alert alert-info py-2 fs-12 mb-0">
                            <i class="fa-solid fa-info-circle me-1"></i>
                            <strong>Approval Flow:</strong><br>
                            <ol class="mb-0 mt-1 ps-3">
                                <li>You submit this requisition</li>
                                <li>Project Manager approves</li>
                                <li>You mark work as done</li>
                                <li>You request payment</li>
                                <li>QC → PM → Management approve</li>
                                <li>Accounts processes payment</li>
                            </ol>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fa-solid fa-paper-plane me-2"></i> Submit Requisition
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

<script>
(function() {
    var count = document.getElementById('laborCount');
    var rate  = document.getElementById('laborRate');
    var days  = document.getElementById('workingDays');
    var total = document.getElementById('totalAmount');
    var sumT  = document.getElementById('summaryTotal');
    var sumB  = document.getElementById('summaryBreakdown');

    function calc() {
        var c = parseFloat(count.value) || 0;
        var r = parseFloat(rate.value)  || 0;
        var d = parseFloat(days.value)  || 0;
        var t = c * r * d;
        var fmt = '৳ ' + t.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
        total.value = t.toFixed(2);
        sumT.textContent = fmt;
        sumB.textContent = c + ' laborer(s) × ৳' + r.toFixed(0) + ' × ' + d + ' day(s)';
    }

    count.addEventListener('input', calc);
    rate.addEventListener('input', calc);
    days.addEventListener('input', calc);

    // Auto-calc working days from date range
    var from = document.getElementById('workDateFrom');
    var to   = document.getElementById('workDateTo');
    function calcDays() {
        if (from.value && to.value) {
            var diff = (new Date(to.value) - new Date(from.value)) / (1000*60*60*24) + 1;
            if (diff > 0) { days.value = diff; calc(); }
        }
    }
    from.addEventListener('change', calcDays);
    to.addEventListener('change', calcDays);
})();
</script>

