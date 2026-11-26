<?php
$prId = (int)($req['id'] ?? 0);
$defaultQty = max(1, (float)($req['quantity'] ?? 1));
$isRevision = (!empty($existingOpo) && ($existingOpo['status'] ?? '') === 'revision_requested');
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-3 page-header-breadcrumb">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/dashboard"><i class="fa-solid fa-house me-1"></i> Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/asset-extra-purchases">Office Procurement Requests</a></li>
                    <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/asset-extra-purchase/<?= $prId ?>">Request #<?= $prId ?></a></li>
                    <li class="breadcrumb-item active">Multi-Vendor CST</li>
                </ol>
            </nav>
            <h4 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                <i class="fa-solid fa-scale-balanced text-primary"></i>
                <span>Multi-Vendor Comparative Statement (CST) — PR #<?= $prId ?></span>
                <?php if ($isRevision): ?>
                    <span class="badge bg-warning text-dark fs-12 px-2.5 py-1"><i class="fa-solid fa-rotate-left me-1"></i>Revision Requested</span>
                <?php endif; ?>
            </h4>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-2 mt-md-0">
            <a href="<?= $GLOBALS['base_url'] ?>/con-vendor-create" target="_blank" class="btn btn-outline-success btn-sm fw-semibold shadow-sm">
                <i class="fa-solid fa-user-plus me-1"></i> Vendor Not Listed? Enroll New Vendor
            </a>
            <?php if (!empty($existingOpo)): ?>
                <a href="<?= $GLOBALS['base_url'] ?>/office-procurement/order/<?= (int)$existingOpo['id'] ?>" class="btn btn-info btn-sm text-white fw-semibold shadow-sm">
                    <i class="fa-solid fa-file-invoice me-1"></i> View <?= htmlspecialchars($existingOpo['opo_number']) ?>
                </a>
            <?php endif; ?>
            <a href="<?= $GLOBALS['base_url'] ?>/asset-extra-purchase/<?= $prId ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to PR
            </a>
        </div>
    </div>

    <!-- Alerts -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm">
            <i class="fa-solid fa-circle-check me-2"></i><?= $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm">
            <i class="fa-solid fa-circle-exclamation me-2"></i><?= $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Revision Banner (If Approver sent back with rate suggestion) -->
    <?php if ($isRevision && !empty($latestRevision)): ?>
    <div class="alert alert-warning border-warning shadow-sm d-flex align-items-start gap-3 mb-3">
        <i class="fa-solid fa-triangle-exclamation fs-4 text-warning mt-1"></i>
        <div class="flex-grow-1">
            <h6 class="fw-bold mb-1 text-dark">
                Rate Review / CST Revision Requested by <?= htmlspecialchars($latestRevision['acted_by_name'] ?? 'Approver') ?>
                <span class="badge bg-dark text-white ms-1 fs-10"><?= htmlspecialchars($latestRevision['stage_name'] ?? '') ?></span>
            </h6>
            <p class="mb-1 fs-13 text-dark"><?= nl2br(htmlspecialchars($latestRevision['note'] ?? '')) ?></p>
            <div class="d-flex flex-wrap gap-3 fs-12 text-muted">
                <?php if (!empty($latestRevision['old_unit_price'])): ?>
                    <span>Previous Rate: <strong>TK <?= number_format((float)$latestRevision['old_unit_price'], 2) ?></strong></span>
                <?php endif; ?>
                <?php if (!empty($latestRevision['suggested_unit_price']) && (float)$latestRevision['suggested_unit_price'] > 0): ?>
                    <span class="text-danger fw-bold">Suggested Rate: TK <?= number_format((float)$latestRevision['suggested_unit_price'], 2) ?></span>
                <?php endif; ?>
                <span><i class="fa-regular fa-clock me-1"></i><?= date('d M Y, h:i A', strtotime($latestRevision['acted_at'])) ?></span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- PR Summary Strip -->
    <div class="card custom-card border-0 shadow-sm mb-3">
        <div class="card-body p-3">
            <div class="row  align-items-center fs-13">
                <div class="col-md-3">
                    <span class="text-muted fs-11 d-block text-uppercase fw-semibold">Item Name &amp; Category</span>
                    <strong class="text-dark fs-14"><?= htmlspecialchars($req['item_name'] ?? '—') ?></strong>
                    <?php if (!empty($req['category_name'])): ?>
                        <span class="badge bg-light text-secondary border ms-1 fs-10"><?= htmlspecialchars($req['category_name']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="col-md-2">
                    <span class="text-muted fs-11 d-block text-uppercase fw-semibold">Requested Qty</span>
                    <strong class="text-primary fs-15"><?= number_format($defaultQty, 0) ?> pcs</strong>
                </div>
                <div class="col-md-2">
                    <span class="text-muted fs-11 d-block text-uppercase fw-semibold">Estimated Total</span>
                    <strong class="text-dark">TK <?= number_format((float)($req['total_price'] ?? 0), 2) ?></strong>
                </div>
                <div class="col-md-2">
                    <span class="text-muted fs-11 d-block text-uppercase fw-semibold">Requested By</span>
                    <strong><?= htmlspecialchars($req['requested_by_name'] ?? '—') ?></strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted fs-11 d-block text-uppercase fw-semibold">Location / Branch</span>
                    <strong><i class="fa-solid fa-location-dot text-danger me-1"></i><?= htmlspecialchars($req['branch_name'] ?? $req['asset_location'] ?? 'Head Office') ?></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Vendor Enrollment Guidance -->
    <div class="alert alert-info-transparent border border-info-subtle fs-12 py-2 px-3 mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <i class="fa-solid fa-circle-info text-info me-1.5"></i>
            Add quotations from multiple active vendors below and select the winning vendor. If a vendor is not in the list, enroll and approve them first via <strong>Vendor Enrollment</strong>, then refresh this page.
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= $GLOBALS['base_url'] ?>/vendor-enrollment-requests?tab=pending" target="_blank" class="btn btn-outline-info btn-sm fw-semibold">
                <i class="fa-solid fa-clock-rotate-left me-1"></i> Vendor Enrollment Status
            </a>
            <a href="<?= $GLOBALS['base_url'] ?>/con-vendor-create" target="_blank" class="btn btn-outline-success btn-sm fw-semibold">
                <i class="fa-solid fa-user-plus me-1"></i> + Vendor Enrollment
            </a>
            <button type="button" class="btn btn-primary btn-sm fw-semibold" onclick="addCstVendorRow()">
                <i class="fa-solid fa-plus me-1"></i> Add Vendor Row
            </button>
        </div>
    </div>

    <!-- Multi-Vendor CST Form -->
    <form action="<?= $GLOBALS['base_url'] ?>/office-procurement/cst/save/<?= $prId ?>" method="POST" enctype="multipart/form-data" id="officeCstForm">
        <div class="card custom-card border-0 shadow-sm mb-3">
            <div class="card-header bg-light py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="card-title mb-0 fw-bold fs-13 text-dark">
                    <i class="fa-solid fa-table-list text-primary me-1.5"></i> Vendor Quotations &amp; Rate Comparison
                </h6>
                <span class="badge bg-success-subtle text-success border border-success-subtle fs-11">
                    Select Winner Radio → Auto-Generates OPO
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="cstVendorTable">
                        <thead class="table-light fs-12">
                            <tr>
                                <th class="text-center" style="width:75px;">Winner</th>
                                <th style="min-width:230px;">Vendor <span class="text-danger">*</span></th>
                                <th style="width:105px;">Qty <span class="text-danger">*</span></th>
                                <th style="width:140px;">Unit Rate (TK) <span class="text-danger">*</span></th>
                                <th style="width:140px;" class="text-end">Total (TK)</th>
                                <th style="width:145px;">Validity Date</th>
                                <th style="min-width:190px;">Quotation File / Link</th>
                                <th style="min-width:170px;">Remarks</th>
                                <th class="text-center" style="width:55px;">Del</th>
                            </tr>
                        </thead>
                        <tbody id="cstRowsContainer">
                            <?php
                            $rowsToRender = !empty($quotations) ? $quotations : [
                                ['vendor_id' => '', 'quantity' => $defaultQty, 'unit_price' => '', 'validity_date' => '', 'attachment' => '', 'drive_link' => '', 'notes' => '', 'is_selected' => 0],
                                ['vendor_id' => '', 'quantity' => $defaultQty, 'unit_price' => '', 'validity_date' => '', 'attachment' => '', 'drive_link' => '', 'notes' => '', 'is_selected' => 0],
                            ];
                            foreach ($rowsToRender as $idx => $qr):
                                $rowTotal = ((float)($qr['quantity'] ?? $defaultQty)) * ((float)($qr['unit_price'] ?? 0));
                            ?>
                            <tr class="cst-row <?= !empty($qr['is_selected']) ? 'table-success' : '' ?>">
                                <td class="text-center">
                                    <input class="form-check-input winner-radio" type="radio" name="selected_index" value="<?= $idx ?>" <?= !empty($qr['is_selected']) ? 'checked' : '' ?> required onchange="highlightSelectedCstRow(this)" style="width:18px;height:18px;cursor:pointer;">
                                    <div class="lowest-badge-slot mt-1"></div>
                                </td>
                                <td style="min-width:270px;">
                                    <div class="d-flex align-items-center gap-1">
                                        <div class="flex-grow-1">
                                            <select name="vendor_id[<?= $idx ?>]" class="form-select form-select-sm cst-vendor-select" style="width:100%;" required>
                                                <option value="">-- Search &amp; Select Active Vendor --</option>
                                                <?php foreach ($vendors as $v): ?>
                                                    <option value="<?= $v['id'] ?>" <?= ((int)($qr['vendor_id'] ?? 0) === (int)$v['id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($v['display_name']) ?><?= !empty($v['phone']) ? ' (' . htmlspecialchars($v['phone']) . ')' : '' ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <a href="<?= $GLOBALS['base_url'] ?>/con-vendor-create" target="_blank" class="btn btn-sm btn-outline-success btn-icon flex-shrink-0" title="Vendor Not Found? Enroll New Vendor">
                                            <i class="fa-solid fa-user-plus"></i>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0.01" name="quantity[<?= $idx ?>]" class="form-control form-control-sm cst-qty" value="<?= htmlspecialchars($qr['quantity'] ?? $defaultQty) ?>" required oninput="recalcCstRow(this)">
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0.01" name="unit_price[<?= $idx ?>]" class="form-control form-control-sm cst-rate fw-bold" value="<?= htmlspecialchars($qr['unit_price'] ?? '') ?>" placeholder="0.00" required oninput="recalcCstRow(this)">
                                </td>
                                <td class="text-end fw-bold text-primary cst-total-cell">
                                    TK <?= number_format($rowTotal, 2) ?>
                                </td>
                                <td>
                                    <input type="date" name="validity_date[<?= $idx ?>]" class="form-control form-control-sm" value="<?= htmlspecialchars($qr['validity_date'] ?? '') ?>">
                                </td>
                                <td>
                                    <input type="file" name="attachment[<?= $idx ?>]" class="form-control form-control-sm mb-1" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx">
                                    <?php if (!empty($qr['attachment'])): ?>
                                        <a href="<?= $GLOBALS['base_url'] ?>/<?= htmlspecialchars($qr['attachment']) ?>" target="_blank" class="badge bg-info-subtle text-info text-decoration-none fs-10">
                                            <i class="fa-solid fa-paperclip me-1"></i>Current Doc
                                        </a>
                                    <?php endif; ?>
                                    <input type="url" name="drive_link[<?= $idx ?>]" class="form-control form-control-sm mt-1" value="<?= htmlspecialchars($qr['drive_link'] ?? '') ?>" placeholder="Or Drive Link (optional)">
                                </td>
                                <td>
                                    <input type="text" name="notes[<?= $idx ?>]" class="form-control form-control-sm" value="<?= htmlspecialchars($qr['notes'] ?? '') ?>" placeholder="Warranty, delivery...">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-icon" onclick="removeCstRow(this)" title="Remove Row">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light py-3 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="fs-13 text-dark">
                    <i class="fa-solid fa-trophy text-warning me-1"></i>
                    Selected OPO Total: <strong class="text-success fs-15" id="cstSelectedTotalDisplay">TK 0.00</strong>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="addCstVendorRow()">
                        <i class="fa-solid fa-plus me-1"></i> Add Another Vendor
                    </button>
                    <button type="submit" class="btn btn-success btn-sm px-4 fw-bold shadow-sm">
                        <i class="fa-solid fa-paper-plane me-1"></i>
                        <?= $isRevision ? 'Update CST & Resubmit OPO for Approval' : 'Submit CST & Generate OPO' ?>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<template id="cstRowTemplate">
    <tr class="cst-row">
        <td class="text-center">
            <input class="form-check-input winner-radio" type="radio" name="selected_index" value="__IDX__" required onchange="highlightSelectedCstRow(this)" style="width:18px;height:18px;cursor:pointer;">
            <div class="lowest-badge-slot mt-1"></div>
        </td>
        <td style="min-width:270px;">
            <div class="d-flex align-items-center gap-1">
                <div class="flex-grow-1">
                    <select name="vendor_id[__IDX__]" class="form-select form-select-sm cst-vendor-select" style="width:100%;" required>
                        <option value="">-- Search &amp; Select Active Vendor --</option>
                        <?php foreach ($vendors as $v): ?>
                            <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['display_name']) ?><?= !empty($v['phone']) ? ' (' . htmlspecialchars($v['phone']) . ')' : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <a href="<?= $GLOBALS['base_url'] ?>/con-vendor-create" target="_blank" class="btn btn-sm btn-outline-success btn-icon flex-shrink-0" title="Vendor Not Found? Enroll New Vendor">
                    <i class="fa-solid fa-user-plus"></i>
                </a>
            </div>
        </td>
        <td>
            <input type="number" step="0.01" min="0.01" name="quantity[__IDX__]" class="form-control form-control-sm cst-qty" value="<?= htmlspecialchars($defaultQty) ?>" required oninput="recalcCstRow(this)">
        </td>
        <td>
            <input type="number" step="0.01" min="0.01" name="unit_price[__IDX__]" class="form-control form-control-sm cst-rate fw-bold" placeholder="0.00" required oninput="recalcCstRow(this)">
        </td>
        <td class="text-end fw-bold text-primary cst-total-cell">TK 0.00</td>
        <td>
            <input type="date" name="validity_date[__IDX__]" class="form-control form-control-sm">
        </td>
        <td>
            <input type="file" name="attachment[__IDX__]" class="form-control form-control-sm mb-1" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx">
            <input type="url" name="drive_link[__IDX__]" class="form-control form-control-sm mt-1" placeholder="Or Drive Link (optional)">
        </td>
        <td>
            <input type="text" name="notes[__IDX__]" class="form-control form-control-sm" placeholder="Warranty, delivery...">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger btn-icon" onclick="removeCstRow(this)" title="Remove Row">
                <i class="fa-solid fa-trash"></i>
            </button>
        </td>
    </tr>
</template>

<script>
var cstRowIndex = <?= count($rowsToRender) ?>;

function initCstVendorSelect2(scopeEl) {
    if (typeof jQuery === 'undefined' || !jQuery.fn || !jQuery.fn.select2) {
        return;
    }
    var $targets = scopeEl ? jQuery(scopeEl).find('.cst-vendor-select') : jQuery('#cstRowsContainer .cst-vendor-select');
    $targets.each(function() {
        var $el = jQuery(this);
        if (!$el.hasClass('select2-hidden-accessible')) {
            $el.select2({
                placeholder: '-- Search Vendor by Name or Phone --',
                allowClear: true,
                width: '100%'
            });
        }
    });
}

function addCstVendorRow() {
    var tpl = document.getElementById('cstRowTemplate').innerHTML;
    var html = tpl.replace(/__IDX__/g, cstRowIndex++);
    var tbody = document.getElementById('cstRowsContainer');
    tbody.insertAdjacentHTML('beforeend', html);
    var newRow = tbody.lastElementChild;
    initCstVendorSelect2(newRow);
    updateSelectedTotalSummary();
}

function removeCstRow(btn) {
    var tbody = document.getElementById('cstRowsContainer');
    if (tbody.querySelectorAll('tr.cst-row').length <= 1) {
        alert('At least one vendor quotation row is required.');
        return;
    }
    btn.closest('tr').remove();
    if (!tbody.querySelector('input.winner-radio:checked')) {
        var firstRadio = tbody.querySelector('input.winner-radio');
        if (firstRadio) {
            firstRadio.checked = true;
            highlightSelectedCstRow(firstRadio);
        }
    }
    updateSelectedTotalSummary();
}

function recalcCstRow(el) {
    var tr = el.closest('tr');
    var qty = parseFloat(tr.querySelector('.cst-qty').value) || 0;
    var rate = parseFloat(tr.querySelector('.cst-rate').value) || 0;
    var total = qty * rate;
    tr.querySelector('.cst-total-cell').textContent = 'TK ' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    updateSelectedTotalSummary();
}

function highlightSelectedCstRow(radio) {
    document.querySelectorAll('#cstRowsContainer tr.cst-row').forEach(function(r) {
        r.classList.remove('table-success');
    });
    if (radio && radio.checked) {
        radio.closest('tr').classList.add('table-success');
    }
    updateSelectedTotalSummary();
}

function updateLowestBadges() {
    var rows = document.querySelectorAll('#cstRowsContainer tr.cst-row');
    var minRate = Infinity;
    rows.forEach(function(r) {
        var rate = parseFloat(r.querySelector('.cst-rate').value) || 0;
        if (rate > 0 && rate < minRate) minRate = rate;
        var slot = r.querySelector('.lowest-badge-slot');
        if (slot) slot.innerHTML = '';
    });
    if (minRate < Infinity) {
        rows.forEach(function(r) {
            var rate = parseFloat(r.querySelector('.cst-rate').value) || 0;
            var slot = r.querySelector('.lowest-badge-slot');
            if (slot && rate === minRate) {
                slot.innerHTML = '<span class="badge bg-success-subtle text-success border border-success-subtle fs-9">Lowest</span>';
            }
        });
    }
}

function updateSelectedTotalSummary() {
    updateLowestBadges();
    var checked = document.querySelector('#cstRowsContainer input.winner-radio:checked');
    var total = 0;
    var disp = document.getElementById('cstSelectedTotalDisplay');
    if (checked) {
        var tr = checked.closest('tr');
        var qty = parseFloat(tr.querySelector('.cst-qty').value) || 0;
        var rate = parseFloat(tr.querySelector('.cst-rate').value) || 0;
        total = qty * rate;
        if (disp) {
            disp.className = 'text-success fs-15';
            disp.textContent = 'TK ' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
    } else if (disp) {
        disp.className = 'text-danger fs-13';
        disp.textContent = 'Please select a winning vendor';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var cstForm = document.getElementById('officeCstForm');
    if (cstForm) {
        cstForm.addEventListener('submit', function(e) {
            var checked = document.querySelector('#cstRowsContainer input.winner-radio:checked');
            if (!checked) {
                e.preventDefault();
                alert('Please select a winning vendor (Winner radio button) before submitting CST & generating OPO.');
                return false;
            }
        });
    }
});

(function() {
    function waitForSelect2AndInit() {
        updateSelectedTotalSummary();
        if (typeof jQuery === 'undefined' || !jQuery.fn || !jQuery.fn.select2) {
            setTimeout(waitForSelect2AndInit, 80);
            return;
        }
        initCstVendorSelect2();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', waitForSelect2AndInit);
    } else {
        waitForSelect2AndInit();
    }
})();
</script>
