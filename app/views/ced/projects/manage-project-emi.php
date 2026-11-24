<?php
$totalProjects = count($projects ?? []);
$underConstructionCount = 0;
$configuredCount = 0;
foreach ($projects as $p) {
    if ((int)($p['under_construction'] ?? 0) === 1) $underConstructionCount++;
    if (!empty($p['schedule_id'])) $configuredCount++;
}
$notUnderConstructionCount = $totalProjects - $underConstructionCount;
?>
<style>
    .emi-card {
        border-radius: 14px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(0, 0, 0, 0.06);
        background: #ffffff;
    }
    .summary-metric-card {
        border-radius: 12px;
        padding: 18px 20px;
        border: 1px solid rgba(15, 23, 42, 0.06);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
        display: flex;
        align-items: center;
        gap: 14px;
        height: 100%;
        background: #ffffff;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .summary-metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
    }
    .metric-icon-box {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }
    .metric-label {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: #64748b;
        line-height: 1.3;
    }
    .metric-value {
        font-size: 24px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.2;
        margin-top: 3px;
    }
</style>

<div class="container-fluid py-3">
    <!-- PAGE HEADER -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold mb-1" style="color: #0f172a;">
                <i class="fa-solid fa-calendar-days text-primary me-2"></i> Manage Project EMI
            </h4>
            <p class="text-muted small mb-0">Set project-wise EMI schedule defaults and configure whether each project is Under Construction or Not.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $base_url ?>/manage-projects-for-ced" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                <i class="fa-solid fa-diagram-project me-1"></i> Manage Projects for CED
            </a>
            <a href="<?= $base_url ?>/ced-collection-statement" class="btn btn-outline-success btn-sm rounded-pill px-3">
                <i class="fa-solid fa-file-invoice-dollar me-1"></i> Collection Statement
            </a>
        </div>
    </div>

    <!-- METRICS SUMMARY CARDS -->
    <div class="row gx-3 gy-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="summary-metric-card" style="border-left: 4px solid #2563eb;">
                <div class="metric-icon-box" style="background: #eff6ff; color: #2563eb;">
                    <i class="fa-solid fa-city"></i>
                </div>
                <div style="min-width: 0;">
                    <div class="metric-label">Total Projects</div>
                    <div class="metric-value"><?= number_format($totalProjects) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="summary-metric-card" style="border-left: 4px solid #f59e0b;">
                <div class="metric-icon-box" style="background: #fef3c7; color: #d97706;">
                    <i class="fa-solid fa-helmet-safety"></i>
                </div>
                <div style="min-width: 0;">
                    <div class="metric-label">Under Construction</div>
                    <div class="metric-value"><?= number_format($underConstructionCount) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="summary-metric-card" style="border-left: 4px solid #0ea5e9;">
                <div class="metric-icon-box" style="background: #e0f2fe; color: #0284c7;">
                    <i class="fa-solid fa-building-circle-check"></i>
                </div>
                <div style="min-width: 0;">
                    <div class="metric-label">Not Under Construction</div>
                    <div class="metric-value"><?= number_format($notUnderConstructionCount) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="summary-metric-card" style="border-left: 4px solid #16a34a;">
                <div class="metric-icon-box" style="background: #dcfce7; color: #16a34a;">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
                <div style="min-width: 0;">
                    <div class="metric-label">EMI Configured</div>
                    <div class="metric-value"><?= number_format($configuredCount) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- PROJECT LIST TABLE -->
    <div class="emi-card overflow-hidden">
        <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2 bg-light-subtle">
            <div class="fw-bold text-dark d-flex align-items-center gap-2">
                <i class="fa-solid fa-list text-primary"></i>
                <span>Project-wise EMI Schedule List</span>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <select id="constructionFilter" class="form-select form-select-sm" style="width: 190px;" onchange="filterProjectEmiTable()">
                    <option value="">All Construction Status</option>
                    <option value="1">Under Construction</option>
                    <option value="0">Not Under Construction</option>
                </select>
                <div class="input-group input-group-sm" style="width: 240px;">
                    <span class="input-group-text bg-white text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" id="projectEmiSearch" class="form-control" placeholder="Search project or officer..." value="<?= htmlspecialchars($search ?? '') ?>" onkeyup="filterProjectEmiTable()">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="projectEmiTable">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width: 50px;">#</th>
                        <th style="min-width: 210px;">Project Name</th>
                        <th class="text-center" style="min-width: 165px;">Construction Status</th>
                        <th style="min-width: 160px;">Assigned CED Officer</th>
                        <th class="text-center" style="width: 110px;">Bookings</th>
                        <th class="text-center" style="min-width: 120px;">EMI Start Date</th>
                        <th class="text-center" style="width: 105px;">Total EMIs</th>
                        <th class="text-center" style="width: 100px;">Due Day</th>
                        <th class="text-end" style="min-width: 135px;">Default EMI (BDT)</th>
                        <th class="text-center text-nowrap" style="min-width: 195px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($projects)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">No projects found.</td>
                        </tr>
                    <?php else:
                        $sl = 1;
                        foreach ($projects as $p):
                            $isUC = (int)($p['under_construction'] ?? 0) === 1;
                            $hasSched = !empty($p['schedule_id']);
                    ?>
                        <tr class="project-emi-row"
                            data-name="<?= htmlspecialchars(strtolower($p['project_name'] . ' ' . $p['ced_officer_name'])) ?>"
                            data-uc="<?= $isUC ? '1' : '0' ?>">
                            <td class="text-center text-muted fw-bold"><?= $sl++ ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($p['project_name']) ?></div>
                                <small class="text-muted">ID: #<?= (int)$p['project_id'] ?></small>
                            </td>
                            <td class="text-center">
                                <?php if ($isUC): ?>
                                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1">
                                        <i class="fa-solid fa-helmet-safety text-warning me-1"></i> Under Construction
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                                        <i class="fa-solid fa-circle-minus me-1"></i> Not Under Construction
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="small fw-semibold text-dark"><?= htmlspecialchars($p['ced_officer_name']) ?></span>
                            </td>
                            <td class="text-center font-monospace">
                                <span class="badge bg-light text-dark border"><?= number_format((int)$p['active_bookings']) ?></span>
                            </td>
                            <td class="text-center font-monospace">
                                <?= !empty($p['emi_start_date']) ? date('d M, Y', strtotime($p['emi_start_date'])) : '<span class="text-muted">Not Set</span>' ?>
                            </td>
                            <td class="text-center font-monospace">
                                <?= $hasSched && !empty($p['total_emi_no']) ? (int)$p['total_emi_no'] . ' mos' : '<span class="text-muted">-</span>' ?>
                            </td>
                            <td class="text-center font-monospace">
                                <?= $hasSched && !empty($p['emi_due_day']) ? 'Day ' . (int)$p['emi_due_day'] : '<span class="text-muted">-</span>' ?>
                            </td>
                            <td class="text-end font-monospace fw-semibold">
                                <?= ($hasSched && $p['default_emi_amount'] !== null) ? '৳' . number_format((float)$p['default_emi_amount'], 2) : '<span class="text-muted">-</span>' ?>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex align-items-center justify-content-center gap-1 flex-nowrap">
                                    <button type="button"
                                        class="btn btn-sm <?= $hasSched ? 'btn-outline-primary' : 'btn-primary' ?> rounded-2 px-2 py-1 text-nowrap"
                                        style="font-size: 12px;"
                                        onclick='openProjectEmiModal(<?= json_encode([
                                            "project_id"         => (int)$p["project_id"],
                                            "project_name"       => $p["project_name"],
                                            "under_construction" => $isUC ? 1 : 0,
                                            "emi_start_date"     => $p["emi_start_date"] ?? "",
                                            "total_emi_no"       => $p["total_emi_no"] ?? 36,
                                            "emi_due_day"        => $p["emi_due_day"] ?? 10,
                                            "default_emi_amount" => $p["default_emi_amount"] ?? "",
                                            "remarks"            => $p["remarks"] ?? ""
                                        ]) ?>)'>
                                        <i class="fa-solid fa-sliders me-1"></i><?= $hasSched ? 'Edit' : 'Set EMI' ?>
                                    </button>
                                    <button type="button"
                                        class="btn btn-sm btn-outline-success rounded-2 px-2 py-1 text-nowrap"
                                        style="font-size: 12px;"
                                        onclick='openEmiListModal(<?= (int)$p["project_id"] ?>, <?= json_encode($p["project_name"]) ?>)'>
                                        <i class="fa-solid fa-list-ol me-1"></i>EMI List
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- SET / EDIT PROJECT EMI MODAL -->
<div class="modal fade" id="projectEmiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-calendar-days text-primary me-2"></i>
                    <span id="modalProjectTitle">Set Project EMI Schedule</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="projectEmiForm" onsubmit="saveProjectEmiSchedule(event)">
                <div class="modal-body p-4">
                    <input type="hidden" name="project_id" id="emiProjectId">

                    <div class="mb-3 p-3 rounded-3 bg-light border">
                        <label class="form-label fw-bold text-dark mb-1">Construction Status</label>
                        <select class="form-select" name="under_construction" id="emiUnderConstruction">
                            <option value="1">Under Construction (Yes)</option>
                            <option value="0">Not Under Construction (Ready / Completed)</option>
                        </select>
                        <small class="text-muted d-block mt-1">Set whether this project is currently under construction or not.</small>
                    </div>

                    <div class="row gx-3 gy-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">EMI Start Date</label>
                            <input type="date" class="form-control" name="emi_start_date" id="emiStartDate">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Total EMIs (Months)</label>
                            <input type="number" class="form-control" name="total_emi_no" id="emiTotalNo" min="1" max="240" value="36" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Monthly Due Day (1 - 31)</label>
                            <input type="number" class="form-control" name="emi_due_day" id="emiDueDay" min="1" max="31" value="10" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Default EMI Amount (BDT)</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="default_emi_amount" id="emiDefaultAmount" placeholder="Optional">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Remarks</label>
                            <input type="text" class="form-control" name="remarks" id="emiRemarks" maxlength="255" placeholder="Optional schedule note...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" id="saveEmiBtn">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Save Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- INDIVIDUAL EMI LIST & DUE DATES MODAL -->
<div class="modal fade" id="emiListModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom">
                <div>
                    <h5 class="modal-title fw-bold mb-0">
                        <i class="fa-solid fa-list-ol text-success me-2"></i>
                        <span id="emiListModalTitle">Project EMI Schedule List</span>
                    </h5>
                    <small class="text-muted" id="emiListModalSubtitle">Select individual due dates for each EMI installment</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="emiListForm" onsubmit="saveEmiListItems(event)">
                <input type="hidden" name="project_id" id="emiListProjectId">
                <div class="modal-body p-0">
                    <div id="emiListLoading" class="text-center py-5 text-muted">
                        <i class="fa-solid fa-spinner fa-spin fa-2x mb-2 text-primary"></i>
                        <div>Loading EMI schedule list...</div>
                    </div>
                    <div id="emiListTableWrap" class="table-responsive" style="display: none; max-height: 60vh;">
                        <table class="table table-hover table-sm align-middle mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="text-center" style="width: 90px;">EMI No</th>
                                    <th class="text-center" style="width: 115px;">Status</th>
                                    <th style="width: 170px;">Due Date</th>
                                    <th style="width: 155px;">Amount (BDT)</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody id="emiListTbody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-top justify-content-between">
                    <small class="text-muted" id="emiListCountInfo"></small>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light border rounded-pill px-3" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success rounded-pill px-4" id="saveEmiListBtn">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function filterProjectEmiTable() {
    const q = (document.getElementById('projectEmiSearch').value || '').toLowerCase().trim();
    const uc = document.getElementById('constructionFilter').value;
    document.querySelectorAll('.project-emi-row').forEach(row => {
        const name = row.getAttribute('data-name') || '';
        const rowUc = row.getAttribute('data-uc') || '';
        const matchQ = !q || name.includes(q);
        const matchUc = uc === '' || rowUc === uc;
        row.style.display = (matchQ && matchUc) ? '' : 'none';
    });
}

function openProjectEmiModal(data) {
    document.getElementById('modalProjectTitle').textContent = data.project_name + ' — EMI Schedule';
    document.getElementById('emiProjectId').value = data.project_id;
    document.getElementById('emiUnderConstruction').value = String(data.under_construction);
    document.getElementById('emiStartDate').value = data.emi_start_date || '';
    document.getElementById('emiTotalNo').value = data.total_emi_no || 36;
    document.getElementById('emiDueDay').value = data.emi_due_day || 10;
    document.getElementById('emiDefaultAmount').value = data.default_emi_amount !== null ? data.default_emi_amount : '';
    document.getElementById('emiRemarks').value = data.remarks || '';
    const modal = new bootstrap.Modal(document.getElementById('projectEmiModal'));
    modal.show();
}

function saveProjectEmiSchedule(e) {
    e.preventDefault();
    const btn = document.getElementById('saveEmiBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...';

    const formData = new FormData(document.getElementById('projectEmiForm'));
    fetch('<?= $base_url ?>/manage-project-emi/save', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            window.location.reload();
        } else {
            alert(res.message || 'Failed to save schedule.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Save Schedule';
        }
    })
    .catch(() => {
        alert('Network error while saving schedule.');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Save Schedule';
    });
}

function openEmiListModal(projectId, projectName) {
    document.getElementById('emiListProjectId').value = projectId;
    document.getElementById('emiListModalTitle').textContent = projectName + ' — Total EMI List';
    document.getElementById('emiListLoading').style.display = '';
    document.getElementById('emiListTableWrap').style.display = 'none';
    document.getElementById('emiListTbody').innerHTML = '';
    document.getElementById('emiListCountInfo').textContent = '';

    const modal = new bootstrap.Modal(document.getElementById('emiListModal'));
    modal.show();

    fetch('<?= $base_url ?>/manage-project-emi/items/' + projectId)
        .then(r => r.json())
        .then(res => {
            document.getElementById('emiListLoading').style.display = 'none';
            if (!res.success) {
                alert(res.message || 'Failed to load EMI list.');
                return;
            }

            document.getElementById('emiListModalSubtitle').textContent =
                'Total EMIs: ' + res.total_emi_no + ' | Start: ' + (res.emi_start_date || 'N/A') + ' | Default Due Day: ' + res.emi_due_day;

            const tbody = document.getElementById('emiListTbody');
            let html = '';
            res.items.forEach(item => {
                const amtVal = (item.emi_amount !== null && item.emi_amount !== undefined) ? item.emi_amount : '';
                const remVal = item.remarks ? String(item.remarks).replace(/"/g, '&quot;') : '';
                const isActive = parseInt(item.is_active || 0, 10) === 1;
                const rowStyle = isActive ? '' : 'opacity: 0.6;';
                const btnClass = isActive ? 'btn-success' : 'btn-outline-secondary';
                const btnHtml  = isActive
                    ? '<i class="fa-solid fa-circle-check me-1"></i>Active'
                    : '<i class="fa-solid fa-circle-pause me-1"></i>Deactive';
                html += `
                    <tr class="emi-item-row" style="${rowStyle}">
                        <td class="text-center fw-bold text-primary">
                            <input type="hidden" name="emi_no[]" value="${item.emi_no}">
                            <input type="hidden" name="is_active[]" class="emi-active-input" value="${isActive ? 1 : 0}">
                            EMI #${item.emi_no}
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-xs ${btnClass} rounded-pill px-2 py-1 text-nowrap emi-toggle-btn" style="font-size: 11px; min-width: 86px;" onclick="toggleEmiItemActive(this)">
                                ${btnHtml}
                            </button>
                        </td>
                        <td>
                            <input type="date" class="form-control form-control-sm" name="due_date[]" value="${item.due_date || ''}" required>
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" class="form-control form-control-sm" name="emi_amount[]" value="${amtVal}" placeholder="Optional">
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm" name="remarks[]" value="${remVal}" maxlength="255" placeholder="Note...">
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
            updateEmiListCounts();
            document.getElementById('emiListTableWrap').style.display = '';
        })
        .catch(() => {
            document.getElementById('emiListLoading').style.display = 'none';
            alert('Network error while loading EMI list.');
        });
}

function toggleEmiItemActive(btn) {
    const row = btn.closest('tr');
    const input = row.querySelector('.emi-active-input');
    const currentEmiNo = parseInt(row.querySelector('input[name="emi_no[]"]').value, 10);
    const isNowActive = input.value !== '1';
    const allRows = Array.from(document.querySelectorAll('#emiListTbody .emi-item-row'));

    if (isNowActive) {
        // Block activating EMI #N if any previous EMI (< N) is still Deactive
        let firstInactivePrev = null;
        for (const r of allRows) {
            const no = parseInt(r.querySelector('input[name="emi_no[]"]').value, 10);
            const activeVal = r.querySelector('.emi-active-input').value;
            if (no < currentEmiNo && activeVal !== '1') {
                firstInactivePrev = no;
                break;
            }
        }
        if (firstInactivePrev !== null) {
            const msg = `EMI #${firstInactivePrev} is currently Deactive. Please activate EMI #${firstInactivePrev} first before activating EMI #${currentEmiNo}.`;
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Sequential Activation Required', text: msg, confirmButtonColor: '#0d5c3a' });
            } else {
                alert(msg);
            }
            return;
        }
    } else {
        // Block deactivating EMI #N if any later EMI (> N) is still Active
        let lastActiveNext = null;
        for (const r of allRows) {
            const no = parseInt(r.querySelector('input[name="emi_no[]"]').value, 10);
            const activeVal = r.querySelector('.emi-active-input').value;
            if (no > currentEmiNo && activeVal === '1') {
                lastActiveNext = no;
            }
        }
        if (lastActiveNext !== null) {
            const msg = `EMI #${lastActiveNext} is still Active. Please deactivate EMI #${lastActiveNext} first before deactivating EMI #${currentEmiNo}.`;
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Sequential Deactivation Required', text: msg, confirmButtonColor: '#0d5c3a' });
            } else {
                alert(msg);
            }
            return;
        }
    }

    input.value = isNowActive ? '1' : '0';

    if (isNowActive) {
        btn.classList.remove('btn-outline-secondary');
        btn.classList.add('btn-success');
        btn.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i>Active';
        row.style.opacity = '1';
    } else {
        btn.classList.remove('btn-success');
        btn.classList.add('btn-outline-secondary');
        btn.innerHTML = '<i class="fa-solid fa-circle-pause me-1"></i>Deactive';
        row.style.opacity = '0.6';
    }
    updateEmiListCounts();
}

function updateEmiListCounts() {
    const inputs = document.querySelectorAll('#emiListTbody .emi-active-input');
    let active = 0;
    inputs.forEach(inp => { if (inp.value === '1') active++; });
    const deactive = inputs.length - active;
    document.getElementById('emiListCountInfo').textContent =
        'Total: ' + inputs.length + ' | Active: ' + active + ' | Deactive: ' + deactive;
}

function saveEmiListItems(e) {
    e.preventDefault();
    const btn = document.getElementById('saveEmiListBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...';

    const formData = new FormData(document.getElementById('emiListForm'));
    fetch('<?= $base_url ?>/manage-project-emi/items/save', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Save';
        if (res.success) {
            const modalEl = document.getElementById('emiListModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        } else {
            alert(res.message || 'Failed to save due dates.');
        }
    })
    .catch(() => {
        alert('Network error while saving due dates.');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Save';
    });
}
</script>
