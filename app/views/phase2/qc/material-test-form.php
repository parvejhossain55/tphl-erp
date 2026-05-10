<?php
$base_url = $GLOBALS['base_url'];
?>
<div class="mb-4 pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4><?= htmlspecialchars($page_title ?? 'Create Material Test') ?></h4>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $base_url ?>/qc-dashboard" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>
</div>

<section class="section">
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="card__wrapper shadow-sm">
        <div class="card-header bg-white border-bottom pb-3 mb-3">
            <h5 class="card-title m-2">Material Lab Test Form</h5>
        </div>
        <form action="<?= $base_url ?>/qc-material-test-store" method="POST" enctype="multipart/form-data">
            <div class="card-body">
                <!-- Source Toggle -->
                <div class="row mb-4">
                    <div class="col-12">
                        <label class="form-label fw-bold d-block">Test Source <span class="text-danger">*</span></label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="test_source" id="sourceGrn" value="grn" checked>
                            <label class="form-check-label fw-semibold text-primary" for="sourceGrn">
                                <i class="bi bi-truck me-1"></i> Material Receipt (GRN)
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="test_source" id="sourceOther" value="other">
                            <label class="form-check-label fw-semibold text-success" for="sourceOther">
                                <i class="bi bi-building me-1"></i> Other QC Test (Site Work)
                            </label>
                        </div>
                    </div>
                </div>

                <!-- GRN Fields -->
                <div class="row" id="grnFields">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Material Receiving (Challan) <span class="text-danger">*</span></label>
                        <!-- NOTE: No 'select2' class — we manually init below to avoid double-init with footer.php -->
                        <select name="material_receiving_id" id="materialReceivingSelect" class="form-select" required>
                            <option value="">-- Select Received Material (Challan) --</option>
                            <?php foreach ($receivings as $rec): ?>
                                <option value="<?= $rec['id'] ?>">
                                    GRN-<?= htmlspecialchars($rec['id']) ?>
                                    <?php if (!empty($rec['project_name'])): ?> | <?= htmlspecialchars($rec['project_name']) ?><?php endif; ?>
                                    <?php if (!empty($rec['receiving_date'])): ?> | <?= htmlspecialchars($rec['receiving_date']) ?><?php endif; ?>
                                    | Status: <?= htmlspecialchars($rec['overall_status'] ?? 'N/A') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Test Name <span class="text-danger">*</span></label>
                        <input type="text" name="test_name" id="testNameInput" class="form-control" placeholder="e.g. Concrete Cylinder Test, Rod Grade 60" required>
                    </div>
                </div>

                <!-- Other QC Test Fields -->
                <div id="otherFields" style="display:none;">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Project <span class="text-danger">*</span></label>
                            <select name="project_id" id="projectIdSelect" class="form-select">
                                <option value="">-- Select Project --</option>
                                <?php foreach ($projects as $project): ?>
                                    <option value="<?= $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Work Type <span class="text-danger">*</span></label>
                            <select name="work_type_id" id="workTypeSelect" class="form-select">
                                <option value="">-- Select Work Type --</option>
                                <?php foreach ($workTypes as $wt): ?>
                                    <option value="<?= $wt['id'] ?>"><?= htmlspecialchars($wt['work_type_name'] ?? $wt['name'] ?? 'Unknown') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Test Type <span class="text-danger">*</span></label>
                            <select name="test_type_id" id="testTypeSelect" class="form-select">
                                <option value="">-- Select Test Type --</option>
                                <?php foreach ($testTypes as $tt): ?>
                                    <option value="<?= $tt['id'] ?>"><?= htmlspecialchars($tt['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Common Test Info Fields -->
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Test Date</label>
                        <input type="date" name="test_date" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Age of Sample (Days)</label>
                        <input type="number" name="age_of_sample" class="form-control" placeholder="e.g. 7 or 28" min="0">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Required Value</label>
                        <input type="text" name="required_value" class="form-control" placeholder="e.g. 3500 PSI">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3 col-6 mb-3">
                        <label class="form-label fw-bold">Result Value</label>
                        <input type="text" name="result_value" class="form-control" placeholder="e.g. 3200 PSI">
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <label class="form-label fw-bold">Test Status <span class="text-danger">*</span></label>
                        <select name="test_status" class="form-select" required>
                            <option value="pass">Pass</option>
                            <option value="fail">Fail</option>
                            <option value="partially_passed">Partially Passed</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <label class="form-label fw-bold">Attachment (Optional)</label>
                        <input type="file" name="test_attachment" class="form-control" accept="image/*,.pdf">
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <label class="form-label fw-bold">Lab Report Link</label>
                        <input type="url" name="lab_report_link" class="form-control" placeholder="https://...">
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Save Test Result
                </button>
            </div>
        </form>
    </div>

    <!-- Container to show materials under the selected receiving/challan -->
    <div id="receiving-items-container" class="mt-4" style="display:none;">
        <div class="card__wrapper shadow-sm">
            <div class="card-header bg-white border-bottom pb-2 mb-2">
                <h6 class="card-title m-0"><i class="bi bi-box-seam text-primary me-2"></i>Materials in this Challan</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover mb-0" id="receiving-items-table">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Material Name</th>
                                <th>Vendor</th>
                                <th>Received Qty</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Filled by AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function() {
    // ── Toggle source fields ──────────────────────────────────────────────
    function toggleSourceFields() {
        var grn   = document.getElementById('sourceGrn');
        var grnF  = document.getElementById('grnFields');
        var othF  = document.getElementById('otherFields');
        var mrs   = document.getElementById('materialReceivingSelect');
        var tni   = document.getElementById('testNameInput');
        var pid   = document.getElementById('projectIdSelect');
        var wt    = document.getElementById('workTypeSelect');
        var tt    = document.getElementById('testTypeSelect');
        var cont  = document.getElementById('receiving-items-container');

        if (grn && grn.checked) {
            grnF.style.display = 'flex';
            othF.style.display = 'none';
            mrs.required = true;
            tni.required = true;
            pid.required = false;
            wt.required  = false;
            tt.required  = false;
            cont.style.display = mrs.value ? 'block' : 'none';
        } else {
            grnF.style.display = 'none';
            othF.style.display = 'block';
            mrs.required = false;
            tni.required = false;
            pid.required = true;
            wt.required  = true;
            tt.required  = true;
            cont.style.display = 'none';
        }
    }

    document.getElementById('sourceGrn').addEventListener('change', toggleSourceFields);
    document.getElementById('sourceOther').addEventListener('change', toggleSourceFields);
    toggleSourceFields();

    // ── Load challan items via AJAX ───────────────────────────────────────
    window._qcLoadReceivingItems = function(id) {
        var container = document.getElementById('receiving-items-container');
        var tbody     = document.querySelector('#receiving-items-table tbody');
        if (!id) {
            container.style.display = 'none';
            tbody.innerHTML = '';
            return;
        }
        fetch('<?= $base_url ?>/qc-ajax-receiving-items?receiving_id=' + id)
            .then(function(res) { return res.json(); })
            .then(function(data) {
                tbody.innerHTML = '';
                if (data && data.length > 0) {
                    data.forEach(function(item, index) {
                        var cls = item.status === 'Accepted' ? 'bg-success'
                                : (item.status === 'Rejected'  ? 'bg-danger' : 'bg-secondary');
                        var tr = document.createElement('tr');
                        tr.innerHTML = '<td>' + (index+1) + '</td>'
                            + '<td class="fw-bold">' + item.material_name + '</td>'
                            + '<td>' + (item.vendor_name || 'N/A') + '</td>'
                            + '<td>' + item.received_qty + ' ' + item.unit + '</td>'
                            + '<td><span class="badge ' + cls + '">' + (item.status || 'Pending') + '</span></td>';
                        tbody.appendChild(tr);
                    });
                    container.style.display = 'block';
                } else {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No materials found for this challan.</td></tr>';
                    container.style.display = 'block';
                }
            })
            .catch(function() { container.style.display = 'none'; });
    };

    // Native change listener — always works
    document.getElementById('materialReceivingSelect').addEventListener('change', function() {
        window._qcLoadReceivingItems(this.value);
    });
})();

// ── Run AFTER footer.php's global initSelect2() completes ─────────────────
// footer.php calls initSelect2() inside $(document).ready().
// We use a 300ms timeout so our destroy+reinit runs AFTER that.
if (typeof jQuery !== 'undefined') {
    jQuery(document).ready(function($) {
        setTimeout(function() {
            var qcSelects = [
                '#materialReceivingSelect',
                '#projectIdSelect',
                '#workTypeSelect',
                '#testTypeSelect'
            ];
            qcSelects.forEach(function(sel) {
                if ($(sel).length) {
                    // Destroy any existing Select2 on this element
                    if ($(sel).data('select2')) {
                        $(sel).select2('destroy');
                    }
                    // Re-init with correct settings
                    $(sel).select2({
                        allowClear: true,
                        width: '100%',
                        dropdownParent: $(sel).parent()
                    });
                }
            });

            // Wire Select2 jQuery change event for challan
            $('#materialReceivingSelect').on('change', function() {
                window._qcLoadReceivingItems($(this).val());
            });

        }, 300);
    });
}
</script>