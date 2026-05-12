<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-purchase-requests">Purchase Requests</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-purchase-request/<?= $pr['id'] ?>">PR-<?= $pr['id'] ?></a></li>
                <li class="breadcrumb-item active">Add Quotation</li>
            </ol>
        </nav>
    </div>
    <a href="<?= $base_url ?>/con-purchase-request/<?= $pr['id'] ?>" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to PR
    </a>
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
                                <th class="text-end">Total Amount</th>
                                <th>Valid Until</th>
                                <th>Attachment</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($existing as $i => $eq): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($eq['vendor_name']) ?></td>
                                <td>৳ <?= number_format($eq['total_amount'] ?? 0, 2) ?></td>
                                <td><?= $eq['validity_date'] ? date('d M Y', strtotime($eq['validity_date'])) : '—' ?></td>
                                <td>
                                    <?php if (!empty($eq['attachment'])): ?>
                                        <a href="<?= $base_url ?>/public/uploads/vendor_quotation/<?= htmlspecialchars($eq['attachment']) ?>"
                                           target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-2">
                                            <i class="bi bi-paperclip me-1"></i>View
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-info"><?= $eq['status'] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Add New Quotation -->
            <div class="card__wrapper shadow-sm">
                <div class="card__header">
                    <h6 class="card__title mb-0">
                        <i class="bi bi-receipt me-2"></i>Add New Vendor Quotation
                    </h6>
                </div>
                <div class="card__body p-4">
                    <form action="<?= $base_url ?>/con-quotation-store" method="POST"
                          id="quotationForm" enctype="multipart/form-data">
                        <input type="hidden" name="request_id" value="<?= $pr['id'] ?>">

                        <div class="row mb-4">
                            <div class="col-md-5">
                                <label class="form-label fw-bold">Vendor <span class="text-danger">*</span></label>
                                <select name="vendor_id" class="form-select" required>
                                    <option value="">-- Select Vendor --</option>
                                    <?php foreach ($vendors as $v): ?>
                                        <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Validity Date</label>
                                <input type="date" name="validity_date" class="form-control" required>
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
                                        <th style="width:18%;" class="text-center">
                                            <i class="bi bi-paperclip me-1"></i>Attachment
                                            <small class="text-muted d-block fw-normal" style="font-size:10px">(per vendor)</small>
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
                                            <input type="hidden" name="material_id[]" value="<?= $item['material_id'] ?>">
                                        </td>
                                        <td class="text-muted"><?= number_format($item['quantity'], 3) ?></td>
                                        <td>
                                            <input type="number" step="0.001" name="quantity[]"
                                                   class="form-control quot-qty" required min="0.001"
                                                   value="<?= $item['quantity'] ?>">
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" name="unit_price[]"
                                                   class="form-control quot-price" required min="0"
                                                   placeholder="0.00">
                                        </td>
                                        <?php if ($idx === 0): ?>
                                        <td class="text-center align-middle" rowspan="<?= count($prItems) ?>"
                                            style="background:rgba(99,102,241,.04);vertical-align:middle">
                                            <input type="file" name="attachment" id="attachment"
                                                   class="form-control form-control-sm"
                                                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                                            <div class="form-text text-center" style="font-size:10px">
                                                PDF, Image, Word
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
                                <i class="bi bi-save me-1"></i> Save Quotation
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
});
</script>
