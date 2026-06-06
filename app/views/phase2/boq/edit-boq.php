<div class="pagetitle d-flex justify-content-between align-items-center">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-boq">BOQ</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>
    <a href="<?= $base_url ?>/con-boq-show/<?= $boq['id'] ?>" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Details
    </a>
</div>

<section class="section">
    <div class="row justify-content-center">
        <div class="col-lg-11">
            <div class="card__wrapper shadow-sm">
                <div class="card__header">
                    <h6 class="card__title mb-0">
                        <i class="bi bi-pencil-square me-2"></i>Edit BOQ — <?= htmlspecialchars($boq['project_name']) ?>
                    </h6>
                </div>
                <div class="card__body p-4">
                    <form action="<?= $base_url ?>/con-boq-update/<?= $boq['id'] ?>" method="POST" id="editBoqForm">

                        <!-- Header Fields -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-5">
                                <label class="form-label fw-bold">Project <span class="text-danger">*</span></label>
                                <select name="project_id" id="project_id" class="form-select" required>
                                    <option value="">-- Select Project --</option>
                                    <?php foreach ($projects as $p): ?>
                                        <option value="<?= $p['id'] ?>"
                                            <?= ($p['id'] == $boq['project_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($p['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Version</label>
                                <input type="text" name="version" class="form-control"
                                       value="<?= htmlspecialchars($boq['version']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Current Status</label>
                                <input type="text" class="form-control" value="<?= $boq['status'] ?>" disabled>
                            </div>
                        </div>

                        <hr class="my-3">
                        <!-- BOQ Items Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle" id="editBoqTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:40%;">Material <span class="text-danger">*</span></th>
                                        <th style="width:12%;" class="text-center">Stock</th>
                                        <th style="width:18%;">Quantity <span class="text-danger">*</span></th>
                                        <th style="width:22%;">Required Date <span class="text-danger">*</span></th>
                                        <th style="width:8%;" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="editBoqBody">
                                    <?php if (!empty($items)): ?>
                                        <?php foreach ($items as $idx => $item): ?>
                                        <tr>
                                            <td>
                                                <select name="material_id[]" class="form-select boq-material" required>
                                                    <option value="">-- Select --</option>
                                                     <?php foreach ($materials as $m): ?>
                                                         <option value="<?= $m['id'] ?>" data-unit="<?= $m['unit'] ?>"
                                                             <?= ($m['id'] == $item['material_id']) ? 'selected' : '' ?>>
                                                             <?= htmlspecialchars($m['name']) ?> [<?= htmlspecialchars($m['category_name'] ?? 'Uncategorized') ?>] (<?= htmlspecialchars($m['unit']) ?>)
                                                         </option>
                                                     <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-primary check-stock-btn d-inline-flex align-items-center gap-1" style="border-radius: 6px; font-weight: 500;" title="Show Stock">
                                                    <i class="bi bi-eye-fill"></i> Show
                                                </button>
                                            </td>
                                            <td>
                                                <div class="input-group">
                                                    <input type="number" step="0.001" name="quantity[]"
                                                           class="form-control boq-qty" required min="0.001"
                                                           value="<?= $item['quantity'] ?>">
                                                    <span class="input-group-text boq-unit"><?= $item['unit'] ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="date" name="required_date[]"
                                                       class="form-control boq-req-date" required
                                                       value="<?= htmlspecialchars($item['required_date'] ?? '') ?>">
                                            </td>
                                            <td class="text-center">
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger boq-remove-row"
                                                        <?= $idx === 0 ? 'style="display:none;"' : '' ?>
                                                        title="Remove">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td>
                                                <select name="material_id[]" class="form-select boq-material" required>
                                                    <option value="">-- Select --</option>
                                                     <?php foreach ($materials as $m): ?>
                                                         <option value="<?= $m['id'] ?>" data-unit="<?= $m['unit'] ?>">
                                                             <?= htmlspecialchars($m['name']) ?> [<?= htmlspecialchars($m['category_name'] ?? 'Uncategorized') ?>] (<?= htmlspecialchars($m['unit']) ?>)
                                                         </option>
                                                     <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-primary check-stock-btn d-inline-flex align-items-center gap-1" style="border-radius: 6px; font-weight: 500;" title="Show Stock">
                                                    <i class="bi bi-eye-fill"></i> Show
                                                </button>
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
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger boq-remove-row"
                                                        style="display:none;" title="Remove">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="editBoqAddRow">
                                <i class="bi bi-plus-circle me-1"></i> Add Item
                            </button>
                        </div>

                        <div class="mt-5 text-end">
                            <a href="<?= $base_url ?>/con-boq-show/<?= $boq['id'] ?>"
                               class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-warning px-4 fw-bold">
                                <i class="bi bi-save me-1"></i> Update BOQ
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
const BASE_URL = '<?= $base_url ?>';

document.addEventListener('DOMContentLoaded', function () {
    var tbody = document.getElementById('editBoqBody');

    // Material change → unit label
    tbody.addEventListener('change', function (e) {
        if (e.target.classList.contains('boq-material')) {
            var opt = e.target.options[e.target.selectedIndex];
            var unit = opt.getAttribute('data-unit') || '-';
            e.target.closest('tr').querySelector('.boq-unit').textContent = unit;
        }
    });

    // Add Row
    document.getElementById('editBoqAddRow').addEventListener('click', function () {
        var firstRow = tbody.querySelector('tr');
        var newRow = firstRow.cloneNode(true);
        newRow.querySelectorAll('input').forEach(function (inp) { inp.value = ''; });
        newRow.querySelector('.boq-material').selectedIndex = 0;
        newRow.querySelector('.boq-unit').textContent = '-';
        newRow.querySelector('.boq-remove-row').style.display = '';
        tbody.appendChild(newRow);
    });

    // Remove & Stock check buttons
    tbody.addEventListener('click', function (e) {
        var removeBtn = e.target.closest('.boq-remove-row');
        if (removeBtn) {
            removeBtn.closest('tr').remove();
        }

        var stockBtn = e.target.closest('.check-stock-btn');
        if (stockBtn) {
            checkStock(stockBtn);
        }
    });
});

function checkStock(btn) {
    const row = btn.closest('tr');
    const matSelect = row.querySelector('.boq-material');
    const materialId = matSelect.value;
    
    // Select project field (handles both hidden and select element)
    const projectField = document.querySelector('[name="project_id"]');
    const projectId = projectField ? projectField.value : '';

    if (!projectId) {
        Swal.fire({
            icon: 'warning',
            title: 'Project Required',
            text: 'Please select a Project first.',
            confirmButtonColor: 'var(--clr-theme-primary)'
        });
        return;
    }

    if (!materialId) {
        Swal.fire({
            icon: 'warning',
            title: 'Material Required',
            text: 'Please select a Material first.',
            confirmButtonColor: 'var(--clr-theme-primary)'
        });
        return;
    }

    // Show loading indicator
    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

    fetch(`${BASE_URL}/con-ajax-material-stock?project_id=${projectId}&material_id=${materialId}`)
        .then(response => response.json())
        .then(data => {
            // Reset button state
            btn.disabled = false;
            btn.innerHTML = origHtml;

            if (data.success) {
                let warehouseListHtml = '';
                if (data.warehouses.length > 0) {
                    data.warehouses.forEach(w => {
                        warehouseListHtml += `
                            <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                <div>
                                    <i class="bi bi-house-door text-muted me-2"></i>
                                    <span class="fw-semibold">${w.warehouse_name}</span>
                                </div>
                                <span class="badge bg-light text-dark border fw-bold">${w.current_quantity.toFixed(3)} ${data.unit}</span>
                            </div>
                        `;
                    });
                } else {
                    warehouseListHtml = `
                        <div class="list-group-item text-center py-3 text-muted bg-light">
                            <i class="bi bi-exclamation-circle me-1 text-warning"></i>
                            No warehouses assigned or found for this project.
                        </div>
                    `;
                }

                const badgeClass = data.total_stock > 0 ? 'bg-success' : 'bg-danger';

                Swal.fire({
                    title: '<span style="font-size:18px; font-weight:700;"><i class="bi bi-box-seam text-primary me-2"></i>Project-wise Stock Inquiry</span>',
                    html: `
                        <div class="text-start" style="font-size:14px; font-family:inherit;">
                            <div class="mb-2 pb-2 border-bottom">
                                <strong>Project:</strong> <span class="text-primary fw-semibold">${data.project_name}</span>
                            </div>
                            <div class="mb-3">
                                <strong>Material:</strong> <span class="text-dark fw-semibold">${data.material_name}</span>
                            </div>
                            <div class="card shadow-sm border-0 mb-3 overflow-hidden" style="border: 1px solid #dee2e6 !important;">
                                <div class="card-header bg-light py-2 px-3 border-bottom-0">
                                    <h6 class="mb-0 text-secondary fw-bold" style="font-size: 12.5px;">
                                        <i class="bi bi-houses me-1 text-info"></i> Warehouse Stock Breakdown
                                    </h6>
                                </div>
                                <div class="list-group list-group-flush border-top">
                                    ${warehouseListHtml}
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded border" style="border: 1px solid #dee2e6 !important;">
                                <span class="fw-bold text-secondary">Total Available Stock:</span>
                                <span class="badge ${badgeClass} px-3 py-2 fs-6 fw-bold" style="letter-spacing: 0.5px;">
                                    ${data.total_stock.toFixed(3)} ${data.unit}
                                </span>
                            </div>
                        </div>
                    `,
                    showCloseButton: true,
                    confirmButtonText: 'Close',
                    confirmButtonColor: 'var(--clr-theme-primary)',
                    customClass: {
                        popup: 'shadow-lg border-0'
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Failed to fetch stock.',
                    confirmButtonColor: '#ef4444'
                });
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = origHtml;
            Swal.fire({
                icon: 'error',
                title: 'Network Error',
                text: 'Something went wrong while connecting to the server. Please try again.',
                confirmButtonColor: '#ef4444'
            });
        });
}
</script>
