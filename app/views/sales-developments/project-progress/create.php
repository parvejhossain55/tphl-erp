<?php
$page_title = "Add Project Progress";
$page = "Add Project Progress";
$show_breadcrumb = true;
$selectedProjectId = $selected_project_id ?? null;
?>

<div class="row gy-3 mb-3">
    <div class="col-12">
        <div class="card__wrapper p-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 rounded bg-primary-subtle text-primary">
                        <i class="fa-solid fa-chart-line fs-4"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 text-dark fw-bold">Add Project Progress</h4>
                        <small class="text-muted">Record milestone percentages with automated weighted total calculation</small>
                    </div>
                </div>
                <div>
                    <a href="<?= $GLOBALS['base_url'] ?>/project-progress-lists" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<form action="<?= $GLOBALS['base_url'] ?>/store-project-progress" method="POST" id="progressForm">
    <div class="row g-3">
        <!-- Left Column: Inputs -->
        <div class="col-lg-8 col-12">
            <!-- Basic Details Card -->
            <div class="card__wrapper p-4 mb-3">
                <h5 class="fw-bold text-dark mb-3">
                    <i class="fa-solid fa-building me-2 text-primary"></i>Project & Target Information
                </h5>

                <div class="row g-3">
                    <!-- Project Dropdown -->
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold text-dark">Select Project <span class="text-danger">*</span></label>
                        <select name="project_id" class="form-select select2" required id="projectSelect">
                            <option value="">-- Choose Project --</option>
                            <?php foreach ($projects as $proj): ?>
                                <option value="<?= $proj['id'] ?>" <?= ($selectedProjectId == $proj['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($proj['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Last Updated Date -->
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold text-dark">Last Updated Date <span class="text-danger">*</span></label>
                        <input type="date" name="last_updated_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <!-- Last Month Target -->
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold text-dark">Last Month Target (%)</label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" max="100" name="last_month_target" class="form-control" placeholder="0" value="0">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>

                    <!-- Last Month Achieved -->
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold text-dark">Last Month Achieved (%)</label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" max="100" name="last_month_achieved" class="form-control" placeholder="0" value="0">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Work Category Progress Card -->
            <div class="card__wrapper p-4 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="fa-solid fa-bars-progress me-2 text-primary"></i>Work Category Progress
                    </h5>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                        Live Auto-Calculation
                    </span>
                </div>

                <!-- Structural Work (35% weight) -->
                <div class="p-3 mb-3 rounded border border-primary bg-primary-subtle bg-opacity-10">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-bold text-dark mb-0">
                            <i class="fa-solid fa-cube text-primary me-1"></i> Structural Work
                        </label>
                        <span class="badge bg-primary text-white">Weight: 35% of Total</span>
                    </div>
                    <small class="text-muted d-block mb-2">Piling, foundation, basement, column, beam, roof casting progress.</small>
                    <div class="input-group">
                        <input type="number" step="0.01" min="0" max="100" name="structural_work" id="structural_work" class="form-control calc-input fw-bold" placeholder="0" value="0" oninput="calculateTotalProgress()">
                        <span class="input-group-text bg-white fw-bold">%</span>
                    </div>
                </div>

                <!-- Remaining 6 Categories (65% weight shared equally) -->
                <div class="mb-2">
                    <span class="badge bg-secondary-subtle text-dark border mb-2">Remaining Categories: 65% Total (10.83% each)</span>
                </div>

                <div class="row g-3">
                    <!-- Plaster Work -->
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold text-dark">Plaster Work (%)</label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" max="100" name="plaster_work" id="plaster_work" class="form-control calc-input" placeholder="0" value="0" oninput="calculateTotalProgress()">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>

                    <!-- Brick Work -->
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold text-dark">Brick Work (%)</label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" max="100" name="brick_work" id="brick_work" class="form-control calc-input" placeholder="0" value="0" oninput="calculateTotalProgress()">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>

                    <!-- Electrical Work -->
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold text-dark">Electrical Work (%)</label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" max="100" name="electrical_work" id="electrical_work" class="form-control calc-input" placeholder="0" value="0" oninput="calculateTotalProgress()">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>

                    <!-- Plumbing Work -->
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold text-dark">Plumbing Work (%)</label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" max="100" name="plumbing_work" id="plumbing_work" class="form-control calc-input" placeholder="0" value="0" oninput="calculateTotalProgress()">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>

                    <!-- Tiles Work -->
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold text-dark">Tiles Work (%)</label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" max="100" name="tiles_work" id="tiles_work" class="form-control calc-input" placeholder="0" value="0" oninput="calculateTotalProgress()">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>

                    <!-- Paint Work -->
                    <div class="col-md-6 col-12">
                        <label class="form-label fw-semibold text-dark">Paint Work (%)</label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" max="100" name="paint_work" id="paint_work" class="form-control calc-input" placeholder="0" value="0" oninput="calculateTotalProgress()">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="d-flex align-items-center gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="fa-solid fa-check me-1"></i> Save Progress
                </button>
                <a href="<?= $GLOBALS['base_url'] ?>/project-progress-lists" class="btn btn-light px-4">
                    Cancel
                </a>
            </div>
        </div>

        <!-- Right Column: Auto-Calculation Preview Card -->
        <div class="col-lg-4 col-12">
            <div class="card__wrapper p-4 sticky-top" style="top: 80px;">
                <h5 class="fw-bold text-dark mb-3">
                    <i class="fa-solid fa-calculator me-2 text-primary"></i>Calculation Summary
                </h5>

                <div class="text-center p-4 rounded bg-light border mb-3">
                    <small class="text-muted text-uppercase fw-semibold">Calculated Total Progress</small>
                    <div class="display-4 fw-bold text-primary my-2" id="previewTotalText">0.00%</div>
                    
                    <div class="progress mt-2" style="height: 12px; border-radius: 6px;">
                        <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated"
                             id="previewProgressBar"
                             role="progressbar"
                             style="width: 0%;"
                             aria-valuenow="0"
                             aria-valuemin="0"
                             aria-valuemax="100"></div>
                    </div>
                </div>

                <div class="small border rounded p-3 bg-white">
                    <div class="fw-bold text-dark mb-2">Weight Distribution Formula:</div>
                    <ul class="list-unstyled mb-0 text-muted">
                        <li class="mb-1"><i class="fa-solid fa-circle-check text-primary me-1"></i> <strong>Structural:</strong> 35% contribution</li>
                        <li class="mb-1"><i class="fa-solid fa-circle-check text-secondary me-1"></i> <strong>Other 6 Works:</strong> 65% contribution (10.83% each)</li>
                    </ul>
                    <hr class="my-2">
                    <div class="text-muted" style="font-size: 0.8rem;">
                        <code>Total = (Structural × 0.35) + ((Sum of 6) ÷ 6 × 0.65)</code>
                    </div>
                </div>

                <div class="mt-3 p-3 rounded bg-warning-subtle text-dark small border border-warning-subtle">
                    <i class="fa-solid fa-circle-info text-warning me-1"></i>
                    Values are automatically clamped between <strong>0%</strong> and <strong>100%</strong>.
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function calculateTotalProgress() {
    const getVal = id => {
        const v = parseFloat(document.getElementById(id)?.value);
        return isNaN(v) ? 0 : Math.max(0, Math.min(100, v));
    };

    const structural = getVal('structural_work');
    const plaster    = getVal('plaster_work');
    const brick      = getVal('brick_work');
    const electrical = getVal('electrical_work');
    const plumbing   = getVal('plumbing_work');
    const tiles      = getVal('tiles_work');
    const paint      = getVal('paint_work');

    const othersSum = plaster + brick + electrical + plumbing + tiles + paint;
    const othersAvg = othersSum / 6.0;

    let total = (structural * 0.35) + (othersAvg * 0.65);
    total = Math.max(0, Math.min(100, total));

    const formatted = total.toFixed(2);
    document.getElementById('previewTotalText').innerText = formatted + '%';

    const bar = document.getElementById('previewProgressBar');
    bar.style.width = formatted + '%';
    bar.setAttribute('aria-valuenow', formatted);

    if (total >= 100) {
        bar.className = 'progress-bar bg-success progress-bar-striped progress-bar-animated';
    } else if (total >= 60) {
        bar.className = 'progress-bar bg-primary progress-bar-striped progress-bar-animated';
    } else if (total >= 30) {
        bar.className = 'progress-bar bg-info progress-bar-striped progress-bar-animated';
    } else {
        bar.className = 'progress-bar bg-warning progress-bar-striped progress-bar-animated';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    calculateTotalProgress();
});
</script>
