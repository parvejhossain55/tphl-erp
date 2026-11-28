<?php
$base_url  = $GLOBALS['base_url'];
$projects  = $projects  ?? [];
$materials = $materials ?? [];
$pr        = $pr        ?? [];
$items     = $items     ?? [];
?>
<div class="pagetitle d-flex justify-content-between align-items-center">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-purchase-requests">Purchase Requests</a></li>
                <li class="breadcrumb-item active">Edit Request #<?= $pr['id'] ?></li>
            </ol>
        </nav>
    </div>
    <a href="<?= $base_url ?>/con-purchase-request/<?= $pr['id'] ?>" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Details
    </a>
</div>

<section class="section">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="card__wrapper shadow-sm">
                <div class="card__header d-flex justify-content-between align-items-center">
                    <h6 class="card__title mb-0">
                        <i class="bi bi-pencil-square me-2 text-primary"></i>
                        <?= (($pr['status'] ?? '') === 'PO Rejected') ? 'Revise Rejected Purchase Request #' . $pr['id'] : 'Edit Draft Purchase Request #' . $pr['id'] ?>
                    </h6>
                    <?php if (($pr['status'] ?? '') === 'PO Rejected'): ?>
                        <span class="badge bg-danger text-white px-3 py-1"><i class="bi bi-exclamation-octagon-fill me-1"></i>PO Rejected (Revision Mode)</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark"><i class="bi bi-file-earmark me-1"></i>Draft Status</span>
                    <?php endif; ?>
                </div>
                <div class="card__body p-4">
                    <?php if (($pr['status'] ?? '') === 'PO Rejected'): ?>
                    <div class="alert alert-danger border-2 border-danger p-4 mb-4 shadow-sm" style="border-radius:12px; background:#fff5f5;">
                        <div class="d-flex align-items-start gap-3">
                            <div class="p-3 rounded-circle text-danger bg-white shadow-xs" style="border:2px solid #fca5a5; width:48px; height:48px; display:flex; align-items:center; justify-content:center;">
                                <i class="bi bi-exclamation-octagon-fill fs-4"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                    <span class="badge bg-danger fs-6 px-3 py-1">Action Required: Correction & Revision</span>
                                    <?php if (!empty($pr['revision_count']) && (int)$pr['revision_count'] > 0): ?>
                                        <span class="badge bg-warning text-dark">Current Revision #<?= $pr['revision_count'] ?></span>
                                    <?php endif; ?>
                                </div>
                                <h5 class="fw-bold text-dark mb-1">
                                    Approver Rejection Feedback / Instructions
                                </h5>
                                <p class="text-muted mb-2" style="font-size:13px;">
                                    The approver has rejected the Purchase Order and returned this requisition for revision.
                                    Please adjust unit quantities, delete items that are not needed, or update notes below.
                                </p>
                                <div class="p-3 rounded bg-white text-dark border border-danger-subtle mt-2 shadow-xs">
                                    <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold" style="font-size:11px;">
                                            <i class="bi bi-chat-quote-fill me-1"></i> Rejection Reason / Note
                                        </span>
                                        <span class="small text-muted" style="font-size:11px;">
                                            <?php if (!empty($pr['po_rejected_by_name'])): ?>
                                                From: <strong><?= htmlspecialchars($pr['po_rejected_by_name']) ?></strong>
                                                (<?= htmlspecialchars($pr['po_rejected_by_role'] ?? 'Management') ?>) &bull;
                                            <?php endif; ?>
                                            <?= !empty($pr['po_rejected_at']) ? date('d M Y, h:i A', strtotime($pr['po_rejected_at'])) : '' ?>
                                        </span>
                                    </div>
                                    <div class="text-danger fw-bold fs-6" style="white-space:pre-wrap; line-height:1.5;">
                                        <?= htmlspecialchars($pr['po_rejection_note'] ?? 'No specific remarks provided.') ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <form action="<?= $base_url ?>/con-purchase-request-update/<?= $pr['id'] ?>" method="POST" id="prEditForm" enctype="multipart/form-data">
                        <input type="hidden" name="pr_type" value="<?= htmlspecialchars($pr['pr_type'] ?? 'boq_linked') ?>">
                        <input type="hidden" name="bom_id" value="<?= htmlspecialchars($pr['bom_id'] ?? '') ?>">

                        <!-- Header Information -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Project <span class="text-danger">*</span></label>
                                <select name="project_id" class="form-select select2" required>
                                    <option value="">-- Select Project --</option>
                                    <?php foreach ($projects as $p): ?>
                                        <option value="<?= $p['id'] ?>" <?= ($pr['project_id'] == $p['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($p['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-calendar-event me-1 text-warning"></i>
                                    Required Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="required_date" id="prRequiredDate" class="form-control" value="<?= htmlspecialchars($pr['required_date'] ?? '') ?>" min="<?= date('Y-m-d') ?>">
                                
                                <div class="mt-1" style="font-size:11px; color:#92400e; line-height:1.4">
                                    <i class="bi bi-shield-exclamation text-warning me-1"></i>
                                    <strong>Policy:</strong> Minimum 30 days notice required.
                                </div>

                                <div id="urgentBadgeContainer" class="mt-2 <?= !empty($pr['is_urgent']) ? '' : 'd-none' ?>">
                                    <div class="alert alert-danger py-1 px-2 mb-0 d-flex align-items-center justify-content-between" style="font-size:11.5px; border-radius:6px">
                                        <div>
                                            <i class="bi bi-lightning-charge-fill text-danger me-1"></i>
                                            <strong>Urgent Requisition</strong>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-link text-danger p-0 text-decoration-none fw-bold" id="btnCancelUrgent" style="font-size:11px">
                                            Revert
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Notes</label>
                                <input type="text" name="notes" class="form-control" value="<?= htmlspecialchars($pr['notes'] ?? '') ?>" placeholder="Urgency, purpose...">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold"><i class="bi bi-paperclip me-1 text-primary"></i> Attachment</label>
                                <input type="file" name="attachment" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp,.xlsx,.xls">
                                <?php if (!empty($pr['attachment'])): ?>
                                    <div class="mt-1 small">
                                        <a href="<?= $base_url . $pr['attachment'] ?>" target="_blank" class="text-primary">
                                            <i class="bi bi-file-earmark-pdf me-1"></i> Current Attachment
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Urgent Reason Box -->
                        <div id="urgentReasonBox" class="row mb-3 <?= !empty($pr['is_urgent']) ? '' : 'd-none' ?>">
                            <div class="col-md-12">
                                <div class="p-3 rounded" style="background:#fef2f2; border:1.5px solid #fca5a5;">
                                    <label class="form-label fw-bold text-danger mb-1" style="font-size:12px">
                                        <i class="bi bi-exclamation-octagon-fill me-1"></i>
                                        Urgent Justification / Reason <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="urgent_reason" id="prUrgentReason" class="form-control"
                                           value="<?= htmlspecialchars($pr['urgent_reason'] ?? '') ?>"
                                           placeholder="State why this is urgent...">
                                    <input type="hidden" name="is_urgent" id="prIsUrgent" value="<?= !empty($pr['is_urgent']) ? '1' : '0' ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Purpose / Description</label>
                                <textarea name="purpose" class="form-control" rows="2" placeholder="Purpose of this purchase request..."><?= htmlspecialchars($pr['purpose'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Items Table -->
                        <h6 class="fw-bold mb-3"><i class="bi bi-box-seam me-2 text-primary"></i>Requested Materials</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle" id="editItemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:28%;">Material</th>
                                        <th style="width:8%; font-size:12px;" class="text-center">Unit</th>
                                        <th style="width:16%; font-size:12px;" class="text-center">
                                            Current Stock
                                            <i class="bi bi-info-circle text-muted" title="Auto-fetched project stock. Editable if physical count differs."></i>
                                        </th>
                                        <th style="width:16%; font-size:12px;">Quantity</th>
                                        <th style="width:18%; font-size:12px;">
                                            Next 3 Months Req.
                                            <i class="bi bi-info-circle text-muted" title="Estimated units required over next 3 months"></i>
                                        </th>
                                        <th style="width:14%; font-size:12px;" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="editItemsBody">
                                    <?php foreach ($items as $idx => $it): ?>
                                    <tr data-mat-id="<?= (int)$it['material_id'] ?>">
                                        <td>
                                            <select name="material_id[]" class="form-select item-material-select" required>
                                                <option value="">-- Select Material --</option>
                                                <?php foreach ($materials as $m): ?>
                                                    <option value="<?= $m['id'] ?>" data-unit="<?= htmlspecialchars($m['unit']) ?>" <?= ($it['material_id'] == $m['id']) ? 'selected' : '' ?>>
                                                        [<?= htmlspecialchars($m['category_name']) ?>] <?= htmlspecialchars($m['name']) ?> (<?= htmlspecialchars($m['unit']) ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td class="text-center fw-bold item-unit-display"><?= htmlspecialchars($it['unit'] ?? '—') ?></td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.001" min="0" name="current_stock[]" class="form-control pr-stock-input text-end"
                                                       placeholder="0.00" value="<?= isset($it['current_stock']) ? (float)$it['current_stock'] : '0.00' ?>">
                                                <span class="input-group-text item-unit-sub" style="font-size:11px"><?= htmlspecialchars($it['unit'] ?? '-') ?></span>
                                            </div>
                                            <div class="pr-stock-hint text-muted mt-1" style="font-size:10px;line-height:1.2"></div>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.001" min="0.001" name="quantity[]" class="form-control text-end" value="<?= (float)$it['quantity'] ?>" required>
                                                <span class="input-group-text item-unit-sub" style="font-size:11px"><?= htmlspecialchars($it['unit'] ?? '-') ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.001" min="0" name="next_3_months_requirement[]" class="form-control text-end"
                                                       placeholder="Optional" value="<?= isset($it['next_3_months_requirement']) && (float)$it['next_3_months_requirement'] > 0 ? (float)$it['next_3_months_requirement'] : '' ?>">
                                                <span class="input-group-text item-unit-sub" style="font-size:11px"><?= htmlspecialchars($it['unit'] ?? '-') ?></span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('tr').remove();">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="editAddRow">
                                <i class="bi bi-plus-circle me-1"></i> Add Material Row
                            </button>
                        </div>

                        <div class="mt-4 text-end">
                            <a href="<?= $base_url ?>/con-purchase-request/<?= $pr['id'] ?>" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" name="status" value="Draft" class="btn btn-outline-primary me-2">
                                <i class="bi bi-save me-1"></i> Save Draft
                            </button>
                            <?php if (($pr['status'] ?? '') === 'PO Rejected'): ?>
                                <button type="submit" name="status" value="Approved" class="btn btn-success px-4 fw-bold">
                                    <i class="bi bi-check2-circle me-1"></i> Save & Ready for CST / Re-PO
                                </button>
                            <?php else: ?>
                                <button type="submit" name="status" value="Submitted" class="btn btn-primary px-4 fw-bold">
                                    <i class="bi bi-send me-1"></i> Submit for PM Review
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════════════════════════
     LEAD TIME POLICY CONFIRMATION MODAL (< 30 DAYS)
═════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="leadTimeModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="leadTimeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:540px">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px; overflow:hidden">
            <div class="modal-header text-white" style="background:linear-gradient(135deg,#dc2626,#ea580c); border:none; padding:18px 24px">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-shield-exclamation fs-4"></i>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="leadTimeModalLabel">
                            Procurement Lead Time Notice (&lt; 30 Days)
                        </h5>
                    </div>
                </div>
            </div>
            <div class="modal-body p-4 text-start">
                <div class="alert alert-warning d-flex align-items-start gap-3 mb-3" style="border-radius:12px; background:#fffbeb; border:1.5px solid #fde68a">
                    <i class="bi bi-exclamation-triangle-fill fs-3 text-warning flex-shrink-0 mt-1"></i>
                    <div>
                        <div class="fw-bold text-dark mb-1" style="font-size:13.5px">
                            Standard 30-Day Lead Time Policy
                        </div>
                        <div style="font-size:12px; color:#92400e; line-height:1.45">
                            Standard company procurement requires Purchase Requests to be placed at least <strong>30 days</strong> before the required date.
                            Your selected required date is only <strong><span id="modalLeadDays" class="text-danger fw-bold fs-6">0</span> days</strong> away from today.
                        </div>
                    </div>
                </div>

                <div class="p-3 rounded mb-3" style="background:#fef2f2; border:1px solid #fee2e2; font-size:12px; color:#991b1b; line-height:1.45">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    <strong>Important:</strong> Requisitions submitted with less than 30 days notice will <strong>not receive regular approval</strong> unless formally declared and justified as an <strong>Urgent Purchase Request</strong>.
                </div>

                <div class="text-secondary mb-1" style="font-size:12.5px; line-height:1.4">
                    Would you like to declare this requisition as an <strong>Urgent Request</strong>, or adjust your required date to comply with standard company policy?
                </div>
            </div>
            <div class="modal-footer bg-light px-4 py-3 d-flex justify-content-between gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3" id="btnChangeToCompliantDate">
                    <i class="bi bi-calendar-check me-1"></i> Change Date (30+ Days)
                </button>
                <button type="button" class="btn btn-danger btn-sm px-3 fw-bold" id="btnConfirmUrgent">
                    <i class="bi bi-lightning-charge-fill me-1"></i> Yes, Mark as Urgent
                </button>
            </div>
        </div>
    </div>
</div>
<!-- /LEAD TIME POLICY MODAL -->

<script>
const BASE_URL = '<?= $base_url ?>';

document.addEventListener('DOMContentLoaded', function() {
    const editItemsBody = document.getElementById('editItemsBody');
    const editAddRow    = document.getElementById('editAddRow');
    const prProjectSelect = document.querySelector('select[name="project_id"]');
    const prRequiredDate  = document.getElementById('prRequiredDate');

    function fetchStockForRow(matSelectEl, trEl = null) {
        const tr = trEl || matSelectEl.closest('tr');
        if (!tr) return;
        const materialId = (matSelectEl && matSelectEl.value) ? matSelectEl.value : (tr.dataset.matId || '');
        const projectId  = prProjectSelect ? prProjectSelect.value : '';

        const stockInput = tr.querySelector('.pr-stock-input');
        const stockHint  = tr.querySelector('.pr-stock-hint');
        if (!stockInput) return;

        if (!materialId || !projectId) {
            if (!projectId && stockHint) {
                stockHint.innerHTML = '<span class="text-warning" style="font-size:10px">⚠️ Select project first</span>';
            }
            return;
        }

        if (stockHint) stockHint.innerHTML = '<span class="text-muted" style="font-size:10px"><i class="bi bi-arrow-repeat spin me-1"></i>Checking stock...</span>';

        fetch(BASE_URL + '/con-ajax-material-stock?material_id=' + encodeURIComponent(materialId) + '&project_id=' + encodeURIComponent(projectId))
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const stockVal = parseFloat(data.current_stock) || 0;
                    stockInput.value = stockVal.toFixed(3);
                    if (stockHint) {
                        if (data.found && stockVal > 0) {
                            stockHint.innerHTML = `<span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:10px">✓ Project Stock: ${stockVal}</span>`;
                        } else {
                            stockHint.innerHTML = `<span class="text-muted" style="font-size:10px">No stock in project warehouse</span>`;
                        }
                    }
                }
            })
            .catch(() => {
                if (stockHint) stockHint.innerHTML = '';
            });
    }

    if (prProjectSelect) {
        prProjectSelect.addEventListener('change', function() {
            editItemsBody.querySelectorAll('tr').forEach(tr => {
                const sel = tr.querySelector('.item-material-select');
                if (sel && sel.value) fetchStockForRow(sel, tr);
            });
        });
    }

    editItemsBody.addEventListener('change', function(e) {
        if (e.target.classList.contains('item-material-select')) {
            const opt = e.target.options[e.target.selectedIndex];
            const unit = opt ? opt.getAttribute('data-unit') : '—';
            const tr = e.target.closest('tr');
            if (tr) {
                const uDisp = tr.querySelector('.item-unit-display');
                if (uDisp) uDisp.textContent = unit || '—';
                tr.querySelectorAll('.item-unit-sub').forEach(el => el.textContent = unit || '-');
                tr.dataset.matId = e.target.value;
                fetchStockForRow(e.target, tr);
            }
        }
    });

    editAddRow.addEventListener('click', function() {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <select name="material_id[]" class="form-select item-material-select" required>
                    <option value="">-- Select Material --</option>
                    <?php foreach ($materials as $m): ?>
                        <option value="<?= $m['id'] ?>" data-unit="<?= htmlspecialchars($m['unit']) ?>">
                            [<?= htmlspecialchars($m['category_name']) ?>] <?= htmlspecialchars($m['name']) ?> (<?= htmlspecialchars($m['unit']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td class="text-center fw-bold item-unit-display">—</td>
            <td>
                <div class="input-group input-group-sm">
                    <input type="number" step="0.001" min="0" name="current_stock[]" class="form-control pr-stock-input text-end"
                           placeholder="0.00" value="0.00">
                    <span class="input-group-text item-unit-sub" style="font-size:11px">-</span>
                </div>
                <div class="pr-stock-hint text-muted mt-1" style="font-size:10px;line-height:1.2"></div>
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <input type="number" step="0.001" min="0.001" name="quantity[]" class="form-control text-end" placeholder="Qty" required>
                    <span class="input-group-text item-unit-sub" style="font-size:11px">-</span>
                </div>
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <input type="number" step="0.001" min="0" name="next_3_months_requirement[]" class="form-control text-end"
                           placeholder="Optional" value="">
                    <span class="input-group-text item-unit-sub" style="font-size:11px">-</span>
                </div>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('tr').remove();">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        editItemsBody.appendChild(tr);
    });

    // ── Lead time logic ──
    function checkLeadTime(dateStr) {
        if (!dateStr) return;
        const selectedDate = new Date(dateStr);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        selectedDate.setHours(0, 0, 0, 0);

        const diffMs = selectedDate.getTime() - today.getTime();
        const diffDays = Math.ceil(diffMs / (1000 * 60 * 60 * 24));

        if (diffDays < 30) {
            document.getElementById('modalLeadDays').textContent = diffDays < 0 ? '0 (Past date)' : diffDays;
            const modalEl = document.getElementById('leadTimeModal');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        } else {
            document.getElementById('prIsUrgent').value = '0';
            document.getElementById('urgentBadgeContainer').classList.add('d-none');
            document.getElementById('urgentReasonBox').classList.add('d-none');
            const reasonInput = document.getElementById('prUrgentReason');
            if (reasonInput) reasonInput.removeAttribute('required');
        }
    }

    if (prRequiredDate) {
        prRequiredDate.addEventListener('change', function () {
            checkLeadTime(this.value);
        });
    }

    document.getElementById('btnConfirmUrgent')?.addEventListener('click', function () {
        const modalEl = document.getElementById('leadTimeModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();

        document.getElementById('prIsUrgent').value = '1';
        document.getElementById('urgentBadgeContainer').classList.remove('d-none');
        document.getElementById('urgentReasonBox').classList.remove('d-none');
        const reasonInput = document.getElementById('prUrgentReason');
        if (reasonInput) {
            reasonInput.setAttribute('required', 'required');
            setTimeout(() => reasonInput.focus(), 300);
        }
    });

    document.getElementById('btnChangeToCompliantDate')?.addEventListener('click', function () {
        const modalEl = document.getElementById('leadTimeModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();

        const compliant = new Date();
        compliant.setDate(compliant.getDate() + 30);
        const yyyy = compliant.getFullYear();
        const mm   = String(compliant.getMonth() + 1).padStart(2, '0');
        const dd   = String(compliant.getDate()).padStart(2, '0');
        prRequiredDate.value = `${yyyy}-${mm}-${dd}`;

        document.getElementById('prIsUrgent').value = '0';
        document.getElementById('urgentBadgeContainer').classList.add('d-none');
        document.getElementById('urgentReasonBox').classList.add('d-none');
        const reasonInput = document.getElementById('prUrgentReason');
        if (reasonInput) reasonInput.removeAttribute('required');
    });

    document.getElementById('btnCancelUrgent')?.addEventListener('click', function () {
        const compliant = new Date();
        compliant.setDate(compliant.getDate() + 30);
        const yyyy = compliant.getFullYear();
        const mm   = String(compliant.getMonth() + 1).padStart(2, '0');
        const dd   = String(compliant.getDate()).padStart(2, '0');
        prRequiredDate.value = `${yyyy}-${mm}-${dd}`;

        document.getElementById('prIsUrgent').value = '0';
        document.getElementById('urgentBadgeContainer').classList.add('d-none');
        document.getElementById('urgentReasonBox').classList.add('d-none');
        const reasonInput = document.getElementById('prUrgentReason');
        if (reasonInput) reasonInput.removeAttribute('required');
    });

    // Form submit validation
    document.getElementById('prEditForm').addEventListener('submit', function (e) {
        const isUrgentVal = document.getElementById('prIsUrgent').value;
        const urgentReasonVal = document.getElementById('prUrgentReason').value.trim();
        const rd = prRequiredDate ? prRequiredDate.value : '';

        if (rd) {
            const d = new Date(rd);
            const t = new Date();
            t.setHours(0,0,0,0);
            d.setHours(0,0,0,0);
            const days = Math.ceil((d - t) / (1000 * 60 * 60 * 24));

            if (days < 30 && isUrgentVal !== '1') {
                e.preventDefault();
                checkLeadTime(rd);
                return false;
            }
        }

        if (isUrgentVal === '1' && !urgentReasonVal) {
            e.preventDefault();
            alert('This Purchase Request is marked Urgent (< 30 days notice). Please specify the Urgent Justification / Reason.');
            document.getElementById('prUrgentReason').focus();
            return false;
        }
    });
});
</script>
