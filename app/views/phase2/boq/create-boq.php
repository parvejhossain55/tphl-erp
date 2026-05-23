<?php
$base_url   = $GLOBALS['base_url'];
$page_title = $page_title ?? 'New Bill of Quantities';
$months     = ['','January','February','March','April','May','June',
               'July','August','September','October','November','December'];
?>
<div class="pagetitle d-flex justify-content-between align-items-center">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-boq">BOQ</a></li>
                <li class="breadcrumb-item active">New BOQ</li>
            </ol>
        </nav>
    </div>
    <a href="<?= $base_url ?>/con-boq" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<section class="section">
    <?php if (!empty($targetData)): ?>
        <?php 
            $target = $targetData;
            // Ensure no duplicate variable conflicts and include the full target view
            include __DIR__ . '/../target-fixed/show-target.php'; 
        ?>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="card__wrapper shadow-sm">
                <div class="card__header" style="background-color: #f8f9fa;">
                    <h6 class="card__title p-1 text-muted">
                        <i class="bi bi-clipboard-data me-2 text-primary"></i>Create Bill of Quantities (BOM)
                    </h6>
                </div>
                <div class="card__body p-4 border-top">
                    <form action="<?= $base_url ?>/con-boq-store" method="POST" id="boqForm">

                        <!-- Hidden: target_id & design_type_id saved on target selection -->
                        <input type="hidden" name="target_id"      id="target_id_hidden" value="<?= $target_id ?: '' ?>">
                        <input type="hidden" name="design_type_id" id="design_type_id_hidden" value="<?= $targetData['design_type_id'] ?? '' ?>">

                        <!-- ── Row 1: Project + Version + Status ── -->
                        <div class="row mb-3 ">
                            <div class="col-md-5">
                                <label class="form-label fw-bold">Project <span class="text-danger">*</span></label>
                                <?php if (!empty($targetData)): ?>
                                    <input type="hidden" name="project_id" value="<?= $targetData['project_id'] ?>">
                                    <select id="project_id" class="form-select" disabled>
                                        <?php foreach ($projects as $p): ?>
                                            <option value="<?= $p['id'] ?>" <?= ($targetData['project_id'] == $p['id']) ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <select name="project_id" id="project_id" class="form-select" required>
                                        <option value="">-- Select Project --</option>
                                        <?php foreach ($projects as $p): ?>
                                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-3 d-none">
                                <label class="form-label fw-bold">Version</label>
                                <input type="text" name="version" class="form-control" value="1.0" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Initial Status</label>
                                <select name="status" class="form-select">
                                    <option value="Draft">Draft</option>
                                    <option value="Submitted">Submitted</option>
                                </select>
                            </div>
                        </div>

                        <!-- ── Row 2: Target Selection (loads after project select) ── -->
                        <div class="row mb-3" <?= !empty($targetData) ? 'style="display:none;"' : '' ?>>
                            <div class="col-md-8">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-bullseye me-1 text-primary"></i>
                                    Linked Target
                                    <span class="text-muted fw-normal" style="font-size:11px">(optional — select project first)</span>
                                </label>
                                <select id="target_select" class="form-select" disabled>
                                    <option value="">-- Select Project First --</option>
                                </select>
                                <div id="target_loading" class="text-muted small mt-1" style="display:none">
                                    <i class="bi bi-hourglass-split me-1"></i> Loading targets...
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-layers me-1 text-info"></i>Design Type
                                </label>
                                <input type="text" id="design_type_display" class="form-control bg-light" readonly
                                       placeholder="Auto-filled from target" value="<?= htmlspecialchars($targetData['design_type_name'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- ── Target Info Card (shown when target selected) ── -->
                        <div id="targetInfoCard" class="alert alert-info <?= empty($targetData) ? 'd-none' : '' ?> mb-3 py-2" style="font-size:12.5px">
                            <i class="bi bi-info-circle me-1"></i>
                            <strong>Selected Target:</strong>
                            <span id="targetInfoText">
                                <?php if (!empty($targetData)): ?>
                                    <strong><?= htmlspecialchars($targetData['target_title'] ?? '—') ?></strong> | 
                                    Period: <?= $months[(int)($targetData['month'] ?? 0)] ?? '' ?> <?= $targetData['year'] ?? '' ?> <?= $targetData['week_number'] ? ' · Week ' . $targetData['week_number'] : '' ?> | 
                                    Target: <strong><?= number_format($targetData['target_percentage'] ?? 0, 1) ?>%</strong> | 
                                    Design: <strong><?= htmlspecialchars($targetData['design_type_name'] ?? '—') ?></strong>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </span>
                        </div>

                        <hr class="my-3">

                        <!-- ── BOQ Items Table ── -->
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle" id="boqItemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:45%;">Material <span class="text-danger">*</span></th>
                                        <th style="width:22%;">Quantity <span class="text-danger">*</span></th>
                                        <th style="width:25%;">Required Date <span class="text-danger">*</span></th>
                                        <th style="width:8%;" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="boqItemsBody">
                                    <tr>
                                        <td>
                                            <select name="material_id[]" class="form-select boq-material" required>
                                                <option value="">-- Select Material --</option>
                                                <?php foreach ($materials as $m): ?>
                                                    <option value="<?= $m['id'] ?>" data-unit="<?= $m['unit'] ?>">
                                                        <?= htmlspecialchars($m['name']) ?> [<?= htmlspecialchars($m['category_name'] ?? 'Uncategorized') ?>] (<?= htmlspecialchars($m['unit']) ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <div class="input-group">
                                                <input type="number" step="0.001" name="quantity[]"
                                                       class="form-control boq-qty" required min="0.001">
                                                <span class="input-group-text boq-unit">-</span>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="date" name="required_date[]"
                                                   class="form-control boq-req-date" required>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger boq-remove-row"
                                                    style="display:none;" title="Remove">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="boqAddRow">
                                <i class="bi bi-plus-circle me-1"></i> Add Item
                            </button>
                        </div>

                        <div class="mt-5 text-end">
                            <a href="<?= $base_url ?>/con-boq" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 fw-bold">
                                <i class="bi bi-save me-1"></i> Save BOQ
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
const BASE_URL  = '<?= $base_url ?>';
const MONTHS    = <?= json_encode($months) ?>;

// ── Stored material options HTML (for cloning rows) ──
const materialOptionsHTML = document.querySelector('.boq-material').innerHTML;

// ── Project → Load Targets ───────────────────────────────────────────────
document.getElementById('project_id').addEventListener('change', function () {
    const pid = this.value;
    const sel = document.getElementById('target_select');
    const load = document.getElementById('target_loading');

    // Reset target
    sel.innerHTML = '<option value="">-- Select Target --</option>';
    sel.disabled  = true;
    document.getElementById('target_id_hidden').value      = '';
    document.getElementById('design_type_id_hidden').value = '';
    document.getElementById('design_type_display').value   = '';
    document.getElementById('targetInfoCard').classList.add('d-none');

    if (!pid) {
        sel.innerHTML = '<option value="">-- Select Project First --</option>';
        return;
    }

    load.style.display = 'block';

    fetch(BASE_URL + '/con-ajax-boq-targets?project_id=' + pid)
        .then(r => r.json())
        .then(data => {
            load.style.display = 'none';
            sel.innerHTML = '<option value="">-- No Target (General BOQ) --</option>';
            if (data.targets && data.targets.length > 0) {
                data.targets.forEach(t => {
                    const wk  = t.week_number ? ' · Week ' + t.week_number : '';
                    const mon = MONTHS[parseInt(t.month)] || '';
                    let label = t.target_title
                        + ' [' + mon + ' ' + t.year + wk + ']'
                        + (t.design_type_name ? ' — ' + t.design_type_name : '');
                    if (t.boq_id) {
                        label += ' (BOM Already Created)';
                    }

                    const opt = document.createElement('option');
                    opt.value = t.id;
                    opt.textContent = label;
                    opt.dataset.designTypeId   = t.design_type_id   || '';
                    opt.dataset.designTypeName  = t.design_type_name  || '';
                    opt.dataset.targetTitle     = t.target_title;
                    opt.dataset.targetPct       = t.target_percentage || '';
                    opt.dataset.year            = t.year;
                    opt.dataset.month           = t.month;
                    opt.dataset.week            = t.week_number || '';
                    opt.dataset.boqId           = t.boq_id           || '';
                    sel.appendChild(opt);
                });
            } else {
                sel.innerHTML += '<option disabled>No targets found for this project</option>';
            }
            sel.disabled = false;
        })
        .catch(() => {
            load.style.display = 'none';
            sel.innerHTML = '<option value="">-- Error loading targets --</option>';
            sel.disabled  = false;
        });
});

// ── Target Selection → Auto-fill design type ─────────────────────────────
document.getElementById('target_select').addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];

    // Store in hidden fields
    document.getElementById('target_id_hidden').value      = this.value || '';
    document.getElementById('design_type_id_hidden').value = opt.dataset.designTypeId || '';

    // Display design type
    document.getElementById('design_type_display').value =
        opt.dataset.designTypeName || (this.value ? '—' : '');

    // Info card
    const card = document.getElementById('targetInfoCard');
    const txt  = document.getElementById('targetInfoText');
    const saveBtn = document.querySelector('#boqForm button[type="submit"]');

    if (this.value) {
        const mon = MONTHS[parseInt(opt.dataset.month)] || '';
        const wk  = opt.dataset.week ? ' · Week ' + opt.dataset.week : '';
        let info  = '<strong>' + (opt.dataset.targetTitle || '—') + '</strong>';
        info     += ' | Period: ' + mon + ' ' + (opt.dataset.year || '') + wk;
        if (opt.dataset.targetPct) info += ' | Target: <strong>' + parseFloat(opt.dataset.targetPct).toFixed(1) + '%</strong>';
        if (opt.dataset.designTypeName) info += ' | Design: <strong>' + opt.dataset.designTypeName + '</strong>';
        
        if (opt.dataset.boqId) {
            info += '<br><div class="alert alert-danger mt-2 mb-0 py-2 fw-semibold" style="font-size:12.5px;"><i class="bi bi-exclamation-triangle-fill me-1"></i> A BOM has already been created for this target. <a href="' + BASE_URL + '/con-boq-show/' + opt.dataset.boqId + '" class="fw-bold text-danger text-decoration-underline" target="_blank">View BOM #' + opt.dataset.boqId + '</a>. Saving is disabled to prevent duplicates.</div>';
            if (saveBtn) saveBtn.disabled = true;
        } else {
            if (saveBtn) saveBtn.disabled = false;
        }
        
        txt.innerHTML = info;
        card.classList.remove('d-none');
    } else {
        card.classList.add('d-none');
        if (saveBtn) saveBtn.disabled = false;
    }
});

// ── BOQ Item Event Listeners ─────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    var tbody = document.getElementById('boqItemsBody');

    tbody.addEventListener('change', function (e) {
        if (e.target.classList.contains('boq-material')) {
            var opt  = e.target.options[e.target.selectedIndex];
            var unit = opt.getAttribute('data-unit') || '-';
            e.target.closest('tr').querySelector('.boq-unit').textContent = unit;
        }
    });

    document.getElementById('boqAddRow').addEventListener('click', function () {
        var firstRow = tbody.querySelector('tr');
        var newRow   = firstRow.cloneNode(true);
        // Reset material select with all options
        const matSel = newRow.querySelector('.boq-material');
        matSel.innerHTML = materialOptionsHTML;
        matSel.selectedIndex = 0;
        newRow.querySelectorAll('input').forEach(function (inp) { inp.value = ''; });
        newRow.querySelector('.boq-unit').textContent = '-';
        newRow.querySelector('.boq-remove-row').style.display = '';
        tbody.appendChild(newRow);
    });

    tbody.addEventListener('click', function (e) {
        var btn = e.target.closest('.boq-remove-row');
        if (btn) {
            btn.closest('tr').remove();
        }
    });
});
</script>
