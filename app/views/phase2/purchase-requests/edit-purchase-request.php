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
                        <i class="bi bi-pencil-square me-2 text-primary"></i>Edit Draft Purchase Request #<?= $pr['id'] ?>
                    </h6>
                    <span class="badge bg-warning text-dark"><i class="bi bi-file-earmark me-1"></i>Draft Status</span>
                </div>
                <div class="card__body p-4">
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
                                <label class="form-label fw-bold">Required Date</label>
                                <input type="date" name="required_date" class="form-control" value="<?= htmlspecialchars($pr['required_date'] ?? '') ?>">
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
                                        <th style="width:50%;">Material <span class="text-danger">*</span></th>
                                        <th style="width:15%; font-size:13px;" class="text-center">Unit</th>
                                        <th style="width:25%; font-size:13px;">Quantity <span class="text-danger">*</span></th>
                                        <th style="width:10%;" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="editItemsBody">
                                    <?php foreach ($items as $idx => $it): ?>
                                    <tr>
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
                                            <input type="number" step="0.001" min="0.001" name="quantity[]" class="form-control" value="<?= (float)$it['quantity'] ?>" required>
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
                            <a href="<?= $base_url ?>/con-purchase-requests" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" name="status" value="Draft" class="btn btn-outline-primary me-2">
                                <i class="bi bi-save me-1"></i> Save Changes (Draft)
                            </button>
                            <button type="submit" name="status" value="Submitted" class="btn btn-primary px-4 fw-bold" onclick="return confirm('Submit this Purchase Request for PM review?')">
                                <i class="bi bi-send me-1"></i> Submit for PM Review
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editItemsBody = document.getElementById('editItemsBody');
    const editAddRow = document.getElementById('editAddRow');

    editItemsBody.addEventListener('change', function(e) {
        if (e.target.classList.contains('item-material-select')) {
            const opt = e.target.options[e.target.selectedIndex];
            const unit = opt ? opt.getAttribute('data-unit') : '—';
            const tr = e.target.closest('tr');
            if (tr) {
                const uDisp = tr.querySelector('.item-unit-display');
                if (uDisp) uDisp.textContent = unit || '—';
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
                <input type="number" step="0.001" min="0.001" name="quantity[]" class="form-control" placeholder="Qty" required>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('tr').remove();">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        editItemsBody.appendChild(tr);
    });
});
</script>
