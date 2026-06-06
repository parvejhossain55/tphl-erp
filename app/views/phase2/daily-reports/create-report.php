<?php $base_url = $GLOBALS['base_url']; ?>
<div class="container-fluid">
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h4 class="page-title fw-semibold fs-18 mb-0">New Daily Progress Report</h4>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-daily-reports">Daily Reports</a></li>
                <li class="breadcrumb-item active">New Report</li>
            </ol>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-xxl-8 col-xl-10">
            <div class="card custom-card">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-journal-plus me-2 text-primary"></i> Daily Progress Report</h6>
                </div>
                <div class="card-body">
                    <form action="<?= $base_url ?>/con-daily-report-store" method="POST" enctype="multipart/form-data">

                        <div class="row  mb-3">
                            <!-- Project -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Project <span class="text-danger">*</span></label>
                                <select name="project_id" id="project_id" class="form-select" required>
                                    <option value="">-- Select Project --</option>
                                    <?php foreach ($projects as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- Site Engineer -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Site Engineer <span class="text-danger">*</span></label>
                                <select name="site_engineer_id" class="form-select" required>
                                    <option value="">-- Select Engineer --</option>
                                    <?php foreach ($engineers as $e): ?>
                                        <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['full_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Linked Working Plan (auto-cascades from project) -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Linked Working Plan <span class="text-muted small">(optional)</span></label>
                            <select name="work_plan_id" id="work_plan_id" class="form-select" disabled>
                                <option value="">-- Select Project First --</option>
                            </select>
                            <div id="planBudgetInfo" class="alert alert-info mt-2 d-none py-2 small">
                                <i class="bi bi-piggy-bank me-1"></i> <span id="planBudgetText"></span>
                            </div>
                        </div>

                        <div class="row  mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Report Date <span class="text-danger">*</span></label>
                                <input type="date" name="report_date" class="form-control"
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Progress % <span class="text-danger">*</span></label>
                                <input type="number" name="progress_percentage" class="form-control"
                                       step="0.01" min="0" max="100" value="0" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Manpower Count</label>
                                <input type="number" name="manpower_count" class="form-control" min="0" placeholder="e.g. 15">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Weather Condition</label>
                            <select name="weather_condition" class="form-select">
                                <option value="">-- Select --</option>
                                <option value="Sunny">☀️ Sunny</option>
                                <option value="Cloudy">☁️ Cloudy</option>
                                <option value="Rainy">🌧️ Rainy</option>
                                <option value="Windy">💨 Windy</option>
                                <option value="Hot">🌡️ Hot</option>
                                <option value="Foggy">🌫️ Foggy</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Work Done (Details) <span class="text-danger">*</span></label>
                            <textarea name="work_done" class="form-control" rows="5" required
                                      placeholder="Describe today's work in detail..."></textarea>
                        </div>

                        <!-- Progress Image Upload -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold"><i class="fal fa-image me-1 text-primary"></i>Progress Image <span class="text-muted small">(optional)</span></label>
                            <div id="dprImageDropZone" style="
                                border: 2px dashed rgba(var(--clr-theme-primary-rgb),.4);
                                border-radius: 12px;
                                padding: 28px 20px;
                                text-align: center;
                                cursor: pointer;
                                transition: border-color .2s, background .2s;
                                background: rgba(var(--clr-theme-primary-rgb),.03);
                            " onclick="document.getElementById('progress_image').click()" ondragover="event.preventDefault();this.style.borderColor='var(--clr-theme-primary)'" ondragleave="this.style.borderColor='rgba(var(--clr-theme-primary-rgb),.4)'" ondrop="handleDrop(event)">
                                <div id="dprImagePreviewWrap" style="display:none;margin-bottom:12px">
                                    <img id="dprImagePreview" src="" alt="Preview" style="max-width:100%;max-height:220px;border-radius:8px;object-fit:cover;box-shadow:0 4px 12px rgba(0,0,0,.12)">
                                </div>
                                <div id="dprImagePlaceholder">
                                    <i class="fal fa-cloud-upload-alt" style="font-size:32px;color:var(--clr-theme-primary);margin-bottom:8px;display:block"></i>
                                    <div style="font-size:13px;font-weight:600;color:var(--clr-theme-primary)">Click or drag &amp; drop to upload</div>
                                    <div style="font-size:11px;color:var(--text-muted);margin-top:4px">JPG, PNG, WebP, GIF — max 5MB</div>
                                </div>
                                <div id="dprImageFileName" style="font-size:12px;margin-top:8px;color:var(--text-muted)"></div>
                            </div>
                            <input type="file" id="progress_image" name="progress_image" accept="image/*" style="display:none" onchange="previewImage(event)">
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Save Report
                            </button>
                            <a href="<?= $base_url ?>/con-daily-reports" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= $base_url ?>';

document.getElementById('project_id').addEventListener('change', function () {
    const pid     = this.value;
    const planSel = document.getElementById('work_plan_id');
    const infoBox = document.getElementById('planBudgetInfo');
    const infoTxt = document.getElementById('planBudgetText');

    planSel.innerHTML = '<option value="">-- Loading... --</option>';
    planSel.disabled  = true;
    infoBox.classList.add('d-none');

    if (!pid) {
        planSel.innerHTML = '<option value="">-- Select Project First --</option>';
        return;
    }

    fetch(BASE_URL + '/con-ajax-project-data?project_id=' + pid)
        .then(r => r.json())
        .then(data => {
            planSel.innerHTML = '<option value="">(No linked plan)</option>';
            data.work_plans.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.id;
                opt.textContent = p.label;
                opt.dataset.budget = p.budget_label;
                opt.dataset.budgetId = p.initial_budget_id;
                planSel.appendChild(opt);
            });
            planSel.disabled = false;
        });
});

document.getElementById('work_plan_id').addEventListener('change', function () {
    const opt     = this.options[this.selectedIndex];
    const infoBox = document.getElementById('planBudgetInfo');
    const infoTxt = document.getElementById('planBudgetText');

    if (opt.dataset.budget) {
        infoBox.classList.remove('d-none');
        infoTxt.textContent = 'Linked Budget ' + (opt.dataset.budget || '');
    } else {
        infoBox.classList.add('d-none');
    }
});

// ── Progress Image Upload Preview ──────────────────────────────────
function previewImage(event) {
    const file = event.target.files[0];
    if (!file) return;
    showPreview(file);
}

function handleDrop(event) {
    event.preventDefault();
    document.getElementById('dprImageDropZone').style.borderColor = 'rgba(var(--clr-theme-primary-rgb),.4)';
    const file = event.dataTransfer.files[0];
    if (!file || !file.type.startsWith('image/')) return;
    const dt = new DataTransfer();
    dt.items.add(file);
    document.getElementById('progress_image').files = dt.files;
    showPreview(file);
}

function showPreview(file) {
    if (file.size > 5 * 1024 * 1024) {
        alert('File too large! Maximum size is 5MB.');
        return;
    }
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('dprImagePreview').src              = e.target.result;
        document.getElementById('dprImagePreviewWrap').style.display = 'block';
        document.getElementById('dprImagePlaceholder').style.display = 'none';
        document.getElementById('dprImageFileName').textContent      = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
    };
    reader.readAsDataURL(file);
}
</script>
