<div class="container-fluid pt-4">
    <div class="row">
        <div class="col-12">
            <div class="card__wrapper">
                <div class="card__header d-flex justify-content-between">
                    <h5 class="card__title mb-0">Set New Bill of Quantities (BOQ)</h5>
                    <a href="<?= $base_url ?>/con-boq" class="btn btn-secondary btn-sm">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back to List
                    </a>
                </div>
                <div class="card__body p-4">
                    <form action="<?= $base_url ?>/con-boq-store" method="POST">
                        <div class="row g-3">
                            <!-- Project Info -->
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label fw-bold">Select Project <span class="text-danger">*</span></label>
                                <select name="project_id" class="form-select select2" required>
                                    <option value="">-- Choose Project --</option>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?= $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 col-lg-2">
                                <label class="form-label fw-bold">Version</label>
                                <input type="text" name="version" class="form-control" value="1.0" required>
                            </div>
                            <div class="col-md-3 col-lg-3">
                                <label class="form-label fw-bold">Status</label>
                                <select name="status" class="form-select">
                                    <option value="Draft">Draft</option>
                                    <option value="Submitted">Submitted</option>
                                </select>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- BOQ Items -->
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle" id="boq-items-table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 40%;">Material <span class="text-danger">*</span></th>
                                        <th style="width: 20%;">Quantity <span class="text-danger">*</span></th>
                                        <th style="width: 20%;">Est. Unit Cost <span class="text-danger">*</span></th>
                                        <th style="width: 15%;">Total</th>
                                        <th style="width: 5%;" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <select name="material_id[]" class="form-select select2-material" required>
                                                <option value="">-- Select Material --</option>
                                                <?php foreach ($materials as $material): ?>
                                                    <option value="<?= $material['id'] ?>" data-unit="<?= $material['unit'] ?>">
                                                        <?= htmlspecialchars($material['name']) ?> (<?= $material['unit'] ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <div class="input-group">
                                                <input type="number" step="0.001" name="quantity[]" class="form-control qty-input" required>
                                                <span class="input-group-text unit-label">-</span>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" name="unit_cost[]" class="form-control cost-input" required>
                                        </td>
                                        <td class="item-total-display text-end fw-bold">0.00</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-danger remove-row" style="display:none;">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold py-3">Grand Total Estimated Cost:</td>
                                        <td class="text-end fw-bold py-3"><span id="grand-total">0.00</span></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="mt-3">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="add-row">
                                <i class="fa-solid fa-plus me-1"></i> Add Item
                            </button>
                        </div>

                        <div class="mt-5 text-end">
                            <button type="submit" class="btn btn-primary px-5 py-2">
                                <i class="fa-solid fa-save me-1"></i> Save BOQ Setup
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    function calculateTotals() {
        let grandTotal = 0;
        $('#boq-items-table tbody tr').each(function() {
            const qty = parseFloat($(this).find('.qty-input').val()) || 0;
            const cost = parseFloat($(this).find('.cost-input').val()) || 0;
            const total = qty * cost;
            $(this).find('.item-total-display').text(total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            grandTotal += total;
        });
        $('#grand-total').text(grandTotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    }

    $(document).on('change', '.select2-material', function() {
        const unit = $(this).find(':selected').data('unit') || '-';
        $(this).closest('tr').find('.unit-label').text(unit);
    });

    $(document).on('input', '.qty-input, .cost-input', function() {
        calculateTotals();
    });

    $('#add-row').on('click', function() {
        const row = $('#boq-items-table tbody tr:first').clone();
        row.find('input').val('');
        row.find('.unit-label').text('-');
        row.find('.item-total-display').text('0.00');
        row.find('.remove-row').show();
        
        // Re-init select2 if used, or just clear. 
        // Note: Project's layout usually handles vanilla selects well, 
        // but if custom select2 is on, we'd need to re-init.
        
        $('#boq-items-table tbody').append(row);
    });

    $(document).on('click', '.remove-row', function() {
        $(this).closest('tr').remove();
        calculateTotals();
    });
});
</script>
