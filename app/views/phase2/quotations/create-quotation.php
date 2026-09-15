<?php
$qiMap = [];
if (!empty($isEdit) && !empty($quotationItems)) {
    foreach ($quotationItems as $qi) {
        $qiMap[$qi['material_id']] = $qi;
    }
}
?>
<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-purchase-requests">Purchase Requests</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-purchase-request/<?= $pr['id'] ?>">PR-<?= $pr['id'] ?></a></li>
                <li class="breadcrumb-item active"><?= !empty($isEdit) ? 'Edit Quotation' : 'Add Quotation' ?></li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <?php if (!empty($existing) && count($existing) >= 2): ?>
            <a href="<?= $base_url ?>/con-quotation-compare/<?= $pr['id'] ?>" class="btn btn-warning btn-sm fw-bold">
                <i class="bi bi-layout-three-columns me-1"></i> Compare Now →
            </a>
        <?php endif; ?>
        <a href="<?= $base_url ?>/con-purchase-request/<?= $pr['id'] ?>" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to PR
        </a>
    </div>
</div>

<section class="section">
    <div class="row justify-content-center">
        <div class="col-lg-11">

            <!-- Existing Quotations -->
            <?php if (!empty($existing)): ?>
            <div class="card__wrapper shadow-sm mb-4">
                <div class="card__header">
                    <h6 class="card__title mb-0">
                        <i class="bi bi-collection me-2"></i>Existing Quotations (<?= count($existing) ?>)
                    </h6>
                </div>
                <div class="card__body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Vendor</th>
                                <th class="text-end">Unit Rate</th>
                                <th class="text-end">Total Amount</th>
                                <th>Valid Until</th>
                                <th>Attachment</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($existing as $i => $eq): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($eq['vendor_name']) ?></td>
                                <td class="text-end">
                                    <?php if (!empty($eq['items'])): ?>
                                        <?php if (count($eq['items']) === 1): ?>
                                            <span class="fw-bold text-dark">৳ <?= number_format($eq['items'][0]['unit_price'], 2) ?></span>
                                            <?php if (!empty($eq['items'][0]['unit'])): ?>
                                                <small class="text-muted">/ <?= htmlspecialchars($eq['items'][0]['unit']) ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="small text-end">
                                                <?php foreach ($eq['items'] as $qItem): ?>
                                                    <div style="white-space:nowrap;">
                                                        <span class="text-muted"><?= htmlspecialchars($qItem['material_name']) ?>:</span>
                                                        <span class="fw-bold">৳ <?= number_format($qItem['unit_price'], 2) ?></span>
                                                        <?php if (!empty($qItem['unit'])): ?>
                                                            <small class="text-muted">/ <?= htmlspecialchars($qItem['unit']) ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold">৳ <?= number_format($eq['total_amount'] ?? 0, 2) ?></td>
                                <td><?= $eq['validity_date'] ? date('d M Y', strtotime($eq['validity_date'])) : '—' ?></td>
                                <td>
                                    <?php if (!empty($eq['attachment'])): ?>
                                        <a href="<?= $base_url ?>/public/uploads/vendor_quotation/<?= htmlspecialchars($eq['attachment']) ?>"
                                           target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-2 mb-1 d-inline-block">
                                             <i class="bi bi-paperclip me-1"></i>View File
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!empty($eq['drive_link'])): ?>
                                        <a href="<?= htmlspecialchars($eq['drive_link']) ?>"
                                           target="_blank" class="btn btn-sm btn-outline-info py-0 px-2 d-inline-block" title="Open Drive Link">
                                             <i class="bi bi-link-45deg me-1"></i>Drive Link
                                        </a>
                                    <?php endif; ?>
                                    <?php if (empty($eq['attachment']) && empty($eq['drive_link'])): ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-info"><?= $eq['status'] ?></span></td>
                                <td>
                                    <a href="<?= $base_url ?>/con-quotation-edit/<?= $eq['id'] ?>" class="btn btn-sm btn-primary py-0 px-2 me-1">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>
                                    <button class="btn btn-sm btn-danger py-0 px-2 delete-quot-btn" data-id="<?= $eq['id'] ?>">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Add/Edit Quotation Form -->
            <div class="card__wrapper shadow-sm">
                <div class="card__header">
                    <h6 class="card__title mb-0">
                        <i class="bi bi-receipt me-2"></i><?= !empty($isEdit) ? 'Edit Vendor Quotation' : 'Add New Vendor Quotation' ?>
                    </h6>
                </div>
                <div class="card__body p-4">
                    <form action="<?= $base_url ?><?= !empty($isEdit) ? '/con-quotation-update/' . $quotation['id'] : '/con-quotation-store' ?>" method="POST"
                          id="quotationForm" enctype="multipart/form-data">
                        <input type="hidden" name="request_id" value="<?= $pr['id'] ?>">

                        <div class="row mb-4">
                            <div class="col-md-5">
                                <label class="form-label fw-bold">Vendor <span class="text-danger">*</span></label>
                                <?php if (!empty($isEdit)): ?>
                                    <select class="form-select" disabled>
                                        <option value="<?= $quotation['vendor_id'] ?>"><?= htmlspecialchars($quotation['vendor_name']) ?></option>
                                    </select>
                                    <input type="hidden" name="vendor_id" value="<?= $quotation['vendor_id'] ?>">
                                <?php else: ?>
                                    <select name="vendor_id" class="form-select" required>
                                        <option value="">-- Select Vendor --</option>
                                        <?php foreach ($vendors as $v): ?>
                                            <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Validity Date</label>
                                <input type="date" name="validity_date" class="form-control" required
                                       value="<?= !empty($isEdit) ? htmlspecialchars($quotation['validity_date']) : '' ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Project</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($pr['project_name']) ?>" disabled>
                            </div>
                        </div>

                        <hr class="my-3">

                        <!-- Auto-filled from PR items -->
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle" id="quotItemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:30%;">Material</th>
                                        <th style="width:12%;">PR Qty</th>
                                        <th style="width:12%;">Qty <span class="text-danger">*</span></th>
                                        <th style="width:13%;">Unit Price <span class="text-danger">*</span></th>
                                        <th style="width:24%;" class="text-center">
                                            <i class="bi bi-paperclip me-1"></i>Attachment <span class="text-danger">*</span>
                                            <small class="text-muted d-block fw-normal" style="font-size:10px">(File & Optional Drive Link)</small>
                                        </th>
                                        <th style="width:15%;" class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="quotItemsBody">
                                    <?php foreach ($prItems as $idx => $item): ?>
                                    <tr>
                                        <td>
                                            <span class="fw-bold"><?= htmlspecialchars($item['material_name']) ?></span>
                                            <span class="text-muted small">(<?= $item['unit'] ?>)</span>
                                            <br><small class="text-muted">[Category: <?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?>]</small>
                                            <input type="hidden" name="material_id[]" value="<?= $item['material_id'] ?>">
                                        </td>
                                        <td class="text-muted"><?= number_format($item['quantity'], 3) ?></td>
                                        <td>
                                            <input type="number" step="0.001" name="quantity[]"
                                                   class="form-control quot-qty" required min="0.001"
                                                   value="<?= !empty($isEdit) ? htmlspecialchars($qiMap[$item['material_id']]['quantity'] ?? 0) : $item['quantity'] ?>">
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" name="unit_price[]"
                                                   class="form-control quot-price" required min="0"
                                                   placeholder="0.00"
                                                   value="<?= !empty($isEdit) ? htmlspecialchars($qiMap[$item['material_id']]['unit_price'] ?? 0) : '' ?>">
                                        </td>
                                        <?php if ($idx === 0): ?>
                                        <td class="text-start align-middle p-3" rowspan="<?= count($prItems) ?>"
                                            style="background:rgba(var(--clr-theme-primary-rgb),.04);vertical-align:middle;min-width:220px;">
                                            <label class="form-label fw-bold mb-1" style="font-size:11px;">
                                                <i class="bi bi-file-earmark-arrow-up text-primary me-1"></i>Upload Quotation File <span class="text-danger">*</span>
                                            </label>
                                            <input type="file" name="attachment" id="quotAttachmentInput"
                                                   class="form-control form-control-sm mb-1"
                                                   accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx"
                                                   <?= (empty($isEdit) || empty($quotation['attachment'])) ? 'required' : '' ?>>
                                            <div class="form-text text-muted mb-2" style="font-size:10px">
                                                PDF, Image (JPG/PNG/WEBP), Word, Excel
                                            </div>
                                            <?php if (!empty($isEdit) && !empty($quotation['attachment'])): ?>
                                                <div class="mb-2">
                                                    <a href="<?= $base_url ?>/public/uploads/vendor_quotation/<?= htmlspecialchars($quotation['attachment']) ?>"
                                                       target="_blank" class="btn btn-sm btn-outline-success py-0 px-2 w-100" style="font-size:11px">
                                                        <i class="bi bi-paperclip me-1"></i> Current File: <?= htmlspecialchars(substr($quotation['attachment'], 0, 15)) ?>...
                                                    </a>
                                                </div>
                                            <?php endif; ?>

                                            <div class="pt-2 border-top">
                                                <label class="form-label fw-semibold mb-1 text-muted" style="font-size:11px;">
                                                    <i class="bi bi-link-45deg text-info me-1"></i>Drive / PDF Link <small class="text-muted">(Optional)</small>
                                                </label>
                                                <input type="url" name="drive_link" id="quotDriveLinkInput"
                                                       class="form-control form-control-sm"
                                                       placeholder="https://drive.google.com/..."
                                                       value="<?= htmlspecialchars($quotation['drive_link'] ?? '') ?>">
                                                <div class="form-text text-muted" style="font-size:9.5px">
                                                    Optional Google Drive, Dropbox or Web Link
                                                </div>
                                            </div>
                                        </td>
                                        <?php endif; ?>
                                        <td class="text-end fw-bold quot-row-total">0.00</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5" class="text-end fw-bold py-3">Total Quotation Amount:</td>
                                        <td class="text-end fw-bold py-3 text-primary fs-6">
                                            <span id="quotGrandTotal">0.00</span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>


                        <div class="mt-4 text-end">
                            <a href="<?= $base_url ?>/con-purchase-request/<?= $pr['id'] ?>" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 fw-bold">
                                <i class="bi bi-save me-1"></i> <?= !empty($isEdit) ? 'Update Quotation' : 'Save Quotation' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var tbody = document.getElementById('quotItemsBody');

    function recalc() {
        var grandTotal = 0;
        tbody.querySelectorAll('tr').forEach(function (row) {
            var qty   = parseFloat(row.querySelector('.quot-qty').value)   || 0;
            var price = parseFloat(row.querySelector('.quot-price').value) || 0;
            var total = qty * price;
            row.querySelector('.quot-row-total').textContent = total.toLocaleString(undefined, {
                minimumFractionDigits: 2, maximumFractionDigits: 2
            });
            grandTotal += total;
        });
        document.getElementById('quotGrandTotal').textContent = grandTotal.toLocaleString(undefined, {
            minimumFractionDigits: 2, maximumFractionDigits: 2
        });
    }

    tbody.addEventListener('input', function (e) {
        if (e.target.classList.contains('quot-qty') || e.target.classList.contains('quot-price')) {
            recalc();
        }
    });

    // Run once on load to prefill totals in edit mode
    recalc();

    // Dynamic attachment requirement logic
    var fileInput = document.getElementById('quotAttachmentInput');
    var linkInput = document.getElementById('quotDriveLinkInput');
    if (fileInput && linkInput) {
        function checkAttachmentReq() {
            var hasLink = linkInput.value.trim().length > 0;
            if (hasLink) {
                fileInput.removeAttribute('required');
            } else {
                <?php if (empty($isEdit) || empty($quotation['attachment'])): ?>
                fileInput.setAttribute('required', 'required');
                <?php endif; ?>
            }
        }
        linkInput.addEventListener('input', checkAttachmentReq);
        checkAttachmentReq();
    }

    // Delete confirmation handler
    document.querySelectorAll('.delete-quot-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            var id = this.getAttribute('data-id');
            if (confirm('Are you sure you want to delete this quotation? This will also reset any prepared comparative statements (CST) for this PR.')) {
                window.location.href = '<?= $base_url ?>/con-quotation-delete/' + id;
            }
        });
    });
});
</script>
