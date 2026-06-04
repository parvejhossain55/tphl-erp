<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-wastage">Wastage Management</a></li>
                <li class="breadcrumb-item active">Record Wastage</li>
            </ol>
        </nav>
    </div>
</div>

<div class="card__wrapper shadow-sm">
    <div class="card__body">
        <form action="<?= $base_url ?>/con-wastage-store" method="POST" enctype="multipart/form-data">
            
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Select Project <span class="text-danger">*</span></label>
                    <select name="project_id" class="form-select select2" required>
                        <option value="">-- Choose Project --</option>
                        <?php foreach($projects as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Select Warehouse <span class="text-danger">*</span></label>
                    <select name="warehouse_id" class="form-select select2" required>
                        <option value="">-- Choose Warehouse --</option>
                        <?php foreach($warehouses as $w): ?>
                            <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <h6 class="fw-bold mb-3 border-bottom pb-2 text-primary">Wastage Items</h6>
            
            <div id="items-container">
                <div class="item-row p-3 mb-3" style=" border-radius:10px; border:1px solid #e9ecef;">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Material <span class="text-danger">*</span></label>
                            <select name="material_id[]" class="form-select select2" required>
                                <option value="">-- Select Material --</option>
                                <?php foreach($materials as $m): ?>
                                    <option value="<?= $m['id'] ?>">
                                        <?= htmlspecialchars($m['name']) ?> [<?= htmlspecialchars($m['category_name'] ?? '') ?>]
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label fw-bold">Quantity <span class="text-danger">*</span></label>
                            <input type="number" step="0.001" name="quantity[]" class="form-control" required placeholder="0.00">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Selling Amount (৳) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="selling_amount[]" class="form-control" required placeholder="0.00">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Upload Photo/Doc <span class="text-danger">*</span></label>
                            <input type="file" name="document[]" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-10 mb-2">
                            <label class="form-label fw-bold">Condition Note</label>
                            <input type="text" name="condition_note[]" class="form-control" placeholder="Describe the condition of this wastage">
                        </div>
                        <div class="col-md-2 mb-2 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-danger w-100 btn-remove-item" disabled><i class="bi bi-trash"></i> Remove</button>
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" id="btn-add-item" class="btn btn-outline-primary btn-sm mb-4">
                <i class="bi bi-plus-circle"></i> Add Another Material
            </button>

            <hr>
            <div class="text-end">
                <a href="<?= $base_url ?>/con-wastage" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-success fw-bold px-4">
                    <i class="bi bi-check-circle"></i> Submit Request
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('items-container');
    const btnAdd = document.getElementById('btn-add-item');

    btnAdd.addEventListener('click', function() {
        // Clone the first item row
        const row = container.querySelector('.item-row').cloneNode(true);
        
        // Clean up any cloned select2 artifacts
        row.querySelectorAll('.select2-container').forEach(el => el.remove());
        
        // Reset inputs and selects
        const inputs = row.querySelectorAll('input, select');
        inputs.forEach(input => {
            if(input.tagName === 'SELECT') {
                input.value = '';
                input.classList.remove('select2-hidden-accessible');
                input.removeAttribute('data-select2-id');
                input.removeAttribute('aria-hidden');
                // Also remove option's data-select2-id
                input.querySelectorAll('option').forEach(opt => opt.removeAttribute('data-select2-id'));
            } else {
                input.value = '';
            }
        });
        
        row.querySelector('.btn-remove-item').disabled = false;
        container.appendChild(row);

        // Initialize select2 on the newly added row
        if(typeof $ !== 'undefined' && $.fn.select2) {
            $(row).find('.select2').each(function() {
                var placeholder = $(this).data('placeholder') || 'Select option';
                $(this).select2({
                    placeholder: placeholder,
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $(this).parent()
                });
            });
        }
    });

    container.addEventListener('click', function(e) {
        if(e.target.closest('.btn-remove-item')) {
            const btn = e.target.closest('.btn-remove-item');
            if(!btn.disabled) {
                btn.closest('.item-row').remove();
            }
        }
    });

});
</script>
