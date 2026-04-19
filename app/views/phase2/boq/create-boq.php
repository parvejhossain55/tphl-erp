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
    <div class="row justify-content-center">
        <div class="col-lg-11">
            <div class="card__wrapper shadow-sm">
                <div class="card__header">
                    <h6 class="card__title mb-0">
                        <i class="bi bi-clipboard-data me-2"></i>Set New Bill of Quantities
                    </h6>
                </div>
                <div class="card__body p-4">
                    <form action="<?= $base_url ?>/con-boq-store" method="POST" id="boqForm">

                        <!-- Header Fields -->
                        <div class="row  mb-4">
                            <div class="col-md-5">
                                <label class="form-label fw-bold">Project <span class="text-danger">*</span></label>
                                <select name="project_id" class="form-select" required>
                                    <option value="">-- Select Project --</option>
                                    <?php foreach ($projects as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Version</label>
                                <input type="text" name="version" class="form-control" value="1.0" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Initial Status</label>
                                <select name="status" class="form-select">
                                    <option value="Draft">Draft</option>
                                    <option value="Submitted">Submitted</option>
                                </select>
                            </div>
                        </div>

                        <hr class="my-3">

                        <!-- BOQ Items Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle" id="boqItemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:40%;">Material <span class="text-danger">*</span></th>
                                        <th style="width:18%;">Quantity <span class="text-danger">*</span></th>
                                        <th style="width:18%;">Est. Unit Cost <span class="text-danger">*</span></th>
                                        <th style="width:16%;" class="text-end">Total</th>
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
                                                        <?= htmlspecialchars($m['name']) ?> (<?= $m['unit'] ?>)
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
                                            <input type="number" step="0.01" name="unit_cost[]"
                                                   class="form-control boq-cost" required min="0">
                                        </td>
                                        <td class="text-end fw-bold boq-row-total">0.00</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger boq-remove-row"
                                                    style="display:none;" title="Remove">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold py-3">Grand Total Estimated Cost:</td>
                                        <td class="text-end fw-bold py-3 text-primary fs-6">
                                            <span id="boqGrandTotal">0.00</span>
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
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
document.addEventListener('DOMContentLoaded', function () {
    var tbody = document.getElementById('boqItemsBody');

    // Recalculate totals
    function recalc() {
        var grandTotal = 0;
        tbody.querySelectorAll('tr').forEach(function (row) {
            var qty  = parseFloat(row.querySelector('.boq-qty').value)  || 0;
            var cost = parseFloat(row.querySelector('.boq-cost').value) || 0;
            var total = qty * cost;
            row.querySelector('.boq-row-total').textContent = total.toLocaleString(undefined, {
                minimumFractionDigits: 2, maximumFractionDigits: 2
            });
            grandTotal += total;
        });
        document.getElementById('boqGrandTotal').textContent = grandTotal.toLocaleString(undefined, {
            minimumFractionDigits: 2, maximumFractionDigits: 2
        });
    }

    // Material change → update unit label
    tbody.addEventListener('change', function (e) {
        if (e.target.classList.contains('boq-material')) {
            var opt  = e.target.options[e.target.selectedIndex];
            var unit = opt.getAttribute('data-unit') || '-';
            e.target.closest('tr').querySelector('.boq-unit').textContent = unit;
        }
    });

    // Qty / Cost input → recalc
    tbody.addEventListener('input', function (e) {
        if (e.target.classList.contains('boq-qty') || e.target.classList.contains('boq-cost')) {
            recalc();
        }
    });

    // Add Row
    document.getElementById('boqAddRow').addEventListener('click', function () {
        var firstRow = tbody.querySelector('tr');
        var newRow   = firstRow.cloneNode(true);
        newRow.querySelectorAll('input').forEach(function (inp) { inp.value = ''; });
        newRow.querySelector('.boq-material').selectedIndex = 0;
        newRow.querySelector('.boq-unit').textContent = '-';
        newRow.querySelector('.boq-row-total').textContent = '0.00';
        newRow.querySelector('.boq-remove-row').style.display = '';
        tbody.appendChild(newRow);
    });

    // Remove Row
    tbody.addEventListener('click', function (e) {
        var btn = e.target.closest('.boq-remove-row');
        if (btn) {
            btn.closest('tr').remove();
            recalc();
        }
    });
});
</script>
