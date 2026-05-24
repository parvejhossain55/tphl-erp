<?php
$addPage = true;
$show_breadcrumb = false;
?>

<h4 class="mb-2">Construction Materials</h4>

<div class="row mb-3">
    <div class="col-xxl-12">
        <div class="card__wrapper border-0 p-0 shadow-none bg-transparent">
            <div class="d-flex flex-column flex-sm-row gap-2 justify-content-end">
                <a href="<?= $GLOBALS['base_url'] ?>/con-material-create" class="btn btn-primary text-nowrap">
                    <i class="fa-light fa-plus me-1"></i> Add Material
                </a>
                <button type="button" class="btn btn-outline-primary text-nowrap" data-bs-toggle="modal" data-bs-target="#manageCategoriesModal">
                    <i class="fa-light fa-tags me-1"></i> Manage Categories
                </button>
                <button type="button" class="btn btn-outline-success text-nowrap" data-bs-toggle="modal" data-bs-target="#importMaterialCsvModal">
                    <i class="bi bi-file-earmark-arrow-up me-1"></i> Import CSV
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Category Filter Card -->
<div class="row mb-3">
    <div class="col-md-4 col-sm-6">
        <div class="card__wrapper py-2 px-3">
            <div class="d-flex align-items-center gap-2">
                <label for="categoryFilter" class="form-label mb-0 text-nowrap fw-semibold" style="font-size: 13px;">
                    <i class="fa-light fa-filter me-1"></i> Filter Category:
                </label>
                <select id="categoryFilter" class="form-select form-select-sm">
                    <option value="all">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                    <option value="none">Uncategorized</option>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table mb-20 hover multiple_tables">
                    <thead>
                        <tr class="table__title">
                            <th style="width: 5%;">#</th>
                            <th style="width: 20%;">Category</th>
                            <th style="width: 45%;">Material Name</th>
                            <th style="width: 10%;">Unit</th>
                            <th style="width: 12%;">Min. Stock Level</th>
                            <th style="width: 8%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php if (!empty($materials)): ?>
                            <?php foreach ($materials as $index => $row): ?>
                                <tr class="material-row" data-category-id="<?= $row['category_id'] ?? 'none' ?>">
                                    <td><span class="serial-number"><?= $index + 1 ?></span></td>
                                    <td>
                                        <span class="category-name-cell" data-cat-id="<?= $row['category_id'] ?? 'none' ?>">
                                            <?php if (!empty($row['category_name'])): ?>
                                                <span class="badge bg-primary text-light border border-primary-subtle px-2 py-1">
                                                    <?= htmlspecialchars($row['category_name']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-primary text-light border border-primary-subtle px-2 py-1">
                                                    Uncategorized
                                                </span>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td class="fw-semibold"><?= htmlspecialchars($row['name']) ?></td>
                                    <td><?= htmlspecialchars($row['unit']) ?></td>
                                    <td><?= number_format($row['min_stock_level'], 3) ?></td>
                                    <td class="table__icon-box">
                                        <div class="d-flex align-items-center justify-content-center gap-10">
                                            <a href="<?= $GLOBALS['base_url'] ?>/con-material-edit/<?= $row['id'] ?>" class="table__icon edit" title="Edit">
                                                <i class="fa-sharp fa-light fa-pen"></i>
                                            </a>
                                            <a href="<?= $GLOBALS['base_url'] ?>/con-material-delete/<?= $row['id'] ?>" class="table__icon delete text-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this material? This will permanently delete the item unless it is in use.')">
                                                <i class="fa-sharp fa-light fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    const base_url = "<?= $GLOBALS['base_url'] ?>"
</script>

<!-- Import CSV Modal: Import Materials -->
<div class="modal fade" id="importMaterialCsvModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-file-earmark-arrow-up me-2 text-success"></i>Import Materials</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">

                <!-- Column Guide -->
                <div class="mb-3">
                    <p class="fw-semibold mb-2" style="font-size:13px;">📋 CSV Column Reference</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0" style="font-size:11.5px;">
                            <thead class="table-light">
                                <tr>
                                    <th>Column Header</th>
                                    <th>Required?</th>
                                    <th>Allowed Values</th>
                                    <th>Example</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>Name</code></td>
                                    <td><span class="badge bg-danger">Required</span></td>
                                    <td>Unique Material Name</td>
                                    <td><em>Cement - Holcim</em></td>
                                </tr>
                                <tr>
                                    <td><code>Unit</code></td>
                                    <td><span class="badge bg-danger">Required</span></td>
                                    <td>Bags, KG, Tons, CFT</td>
                                    <td><em>Bags</em></td>
                                </tr>
                                <tr>
                                    <td><code>Min_Stock_Level</code></td>
                                    <td><span class="badge bg-secondary">Optional</span></td>
                                    <td>Numeric Value</td>
                                    <td><em>50.500</em></td>
                                </tr>
                                <tr>
                                    <td><code>Category</code></td>
                                    <td><span class="badge bg-secondary">Optional</span></td>
                                    <td>An existing category name</td>
                                    <td><em>Cement</em></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2 d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="downloadMaterialSampleCsv()">
                            <i class="bi bi-download me-1"></i> Download Sample CSV
                        </button>
                        <span style="font-size:11px;color:#888">ডাউনলোড করে সঠিক format অনুযায়ী ফিলআপ করুন</span>
                    </div>
                </div>

                <hr class="my-3">

                <!-- Upload Form -->
                <form action="<?= $GLOBALS['base_url'] ?>/con-materials-import" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Upload CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        <div class="form-text">শুধুমাত্র <strong>.csv</strong> ফাইল। Excel থেকে "Save As → CSV UTF-8" করে export করুন।</div>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="bi bi-upload me-1"></i> Import Now
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<!-- Manage Categories Modal -->
<div class="modal fade" id="manageCategoriesModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-light fa-tags me-2 text-primary"></i>Manage Material Categories
                </h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <!-- Alert box for success/error messages inside modal -->
                <div id="modalAlert" class="alert d-none py-2 px-3 mb-3"></div>

                <!-- Add Category Form -->
                <div class="card p-3 mb-4  border-0 shadow-none">
                    <h6 class="mb-3 text-primary"><i class="fa-light fa-plus-circle me-1"></i> Add New Category</h6>
                    <form id="addCategoryForm" class="row g-2">
                        <div class="col-md-5">
                            <input type="text" id="newCatName" class="form-control form-control-sm" placeholder="Category Name (e.g. Steel/Rod)" required>
                        </div>
                        <div class="col-md-5">
                            <input type="text" id="newCatDesc" class="form-control form-control-sm" placeholder="Description (optional)">
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fa-light fa-plus me-1"></i> Add
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Categories Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0" style="font-size: 13px;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 35%;">Category Name</th>
                                <th style="width: 35%;">Description</th>
                                <th style="width: 10%;" class="text-center">Materials</th>
                                <th style="width: 15%;" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="categoryTableBody">
                            <?php foreach ($categories as $index => $cat): ?>
                                <tr data-cat-id="<?= $cat['id'] ?>">
                                    <td class="cat-idx"><?= $index + 1 ?></td>
                                    <td>
                                        <span class="cat-name-span"><?= htmlspecialchars($cat['name']) ?></span>
                                        <input type="text" class="form-control form-control-sm cat-name-input d-none" value="<?= htmlspecialchars($cat['name']) ?>">
                                    </td>
                                    <td>
                                        <span class="cat-desc-span text-muted"><?= htmlspecialchars($cat['description'] ?? '') ?></span>
                                        <input type="text" class="form-control form-control-sm cat-desc-input d-none" value="<?= htmlspecialchars($cat['description'] ?? '') ?>">
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary rounded-pill cat-count-badge"><?= $cat['material_count'] ?? 0 ?></span>
                                    </td>
                                    <td class="text-center">
                                        <div class="cat-action-view">
                                            <button type="button" class="btn btn-sm btn-outline-info p-1 py-0 edit-cat-btn" title="Edit">
                                                <i class="fa-light fa-pen"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger p-1 py-0 delete-cat-btn" title="Delete" <?= ($cat['material_count'] ?? 0) > 0 ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : '' ?>>
                                                <i class="fa-light fa-trash"></i>
                                            </button>
                                        </div>
                                        <div class="cat-action-edit d-none">
                                            <button type="button" class="btn btn-sm btn-success p-1 py-0 save-cat-btn" title="Save">
                                                <i class="fa-light fa-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-secondary p-1 py-0 cancel-cat-btn" title="Cancel">
                                                <i class="fa-light fa-xmark"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function downloadMaterialSampleCsv() {
    const headers = [
        'Name', 'Unit', 'Min_Stock_Level', 'Category'
    ];
    const sampleRow = [
        'Cement - Holcim 50kg', 'Bags', '100.00', 'Cement'
    ];
    
    const csvContent = [
        headers.join(','),
        sampleRow.map(v => '"' + v + '"').join(',')
    ].join('\n');
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = 'materials_template.csv';
    a.click();
    URL.revokeObjectURL(url);
}

document.addEventListener('DOMContentLoaded', function() {
    // -------------------------------------------------------------
    // Main Table Filtering
    // -------------------------------------------------------------
    const categoryFilter = document.getElementById('categoryFilter');
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            const selectedCat = this.value;
            const rows = document.querySelectorAll('.material-row');
            
            rows.forEach(row => {
                const rowCat = row.getAttribute('data-category-id');
                if (selectedCat === 'all') {
                    row.style.display = '';
                } else if (selectedCat === 'none') {
                    row.style.display = (!rowCat || rowCat === 'none' || rowCat === '0') ? '' : 'none';
                } else {
                    row.style.display = (rowCat == selectedCat) ? '' : 'none';
                }
            });
            updateSerialNumbers();
        });
    }

    function updateSerialNumbers() {
        let idx = 1;
        document.querySelectorAll('.material-row').forEach(row => {
            if (row.style.display !== 'none') {
                const serialCell = row.querySelector('.serial-number');
                if (serialCell) {
                    serialCell.textContent = idx++;
                }
            }
        });
    }

    // Helper to escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Helper to show modal alert
    function showModalAlert(message, isSuccess = true) {
        const alertBox = document.getElementById('modalAlert');
        if (alertBox) {
            alertBox.className = `alert py-2 px-3 mb-3 ${isSuccess ? 'alert-success' : 'alert-danger'}`;
            alertBox.textContent = message;
            alertBox.classList.remove('d-none');
            setTimeout(() => {
                alertBox.classList.add('d-none');
            }, 5000);
        }
    }

    // -------------------------------------------------------------
    // Add Category
    // -------------------------------------------------------------
    const addForm = document.getElementById('addCategoryForm');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const nameInput = document.getElementById('newCatName');
            const descInput = document.getElementById('newCatDesc');
            
            const name = nameInput.value.trim();
            const desc = descInput.value.trim();
            
            if (!name) return;

            const formData = new FormData();
            formData.append('name', name);
            formData.append('description', desc);

            fetch(`${base_url}/con-material-category-store`, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showModalAlert('Category added successfully!', true);
                    nameInput.value = '';
                    descInput.value = '';

                    // Add to modal table
                    const tbody = document.getElementById('categoryTableBody');
                    const newIndex = tbody.children.length + 1;
                    const tr = document.createElement('tr');
                    tr.setAttribute('data-cat-id', data.id);
                    tr.innerHTML = `
                        <td class="cat-idx">${newIndex}</td>
                        <td>
                            <span class="cat-name-span">${escapeHtml(data.name)}</span>
                            <input type="text" class="form-control form-control-sm cat-name-input d-none" value="${escapeHtml(data.name)}">
                        </td>
                        <td>
                            <span class="cat-desc-span text-muted">${escapeHtml(desc)}</span>
                            <input type="text" class="form-control form-control-sm cat-desc-input d-none" value="${escapeHtml(desc)}">
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary rounded-pill cat-count-badge">0</span>
                        </td>
                        <td class="text-center">
                            <div class="cat-action-view">
                                <button type="button" class="btn btn-sm btn-outline-info p-1 py-0 edit-cat-btn" title="Edit">
                                    <i class="fa-light fa-pen"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger p-1 py-0 delete-cat-btn" title="Delete">
                                    <i class="fa-light fa-trash"></i>
                                </button>
                            </div>
                            <div class="cat-action-edit d-none">
                                <button type="button" class="btn btn-sm btn-success p-1 py-0 save-cat-btn" title="Save">
                                    <i class="fa-light fa-check"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary p-1 py-0 cancel-cat-btn" title="Cancel">
                                    <i class="fa-light fa-xmark"></i>
                                </button>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);

                    // Add to filter dropdown
                    const filterSelect = document.getElementById('categoryFilter');
                    if (filterSelect) {
                        const opt = document.createElement('option');
                        opt.value = data.id;
                        opt.textContent = data.name;
                        // Insert before the last option (which is Uncategorized)
                        filterSelect.insertBefore(opt, filterSelect.lastElementChild);
                    }
                } else {
                    showModalAlert(data.error || 'Failed to add category.', false);
                }
            })
            .catch(err => {
                console.error(err);
                showModalAlert('An error occurred.', false);
            });
        });
    }

    // -------------------------------------------------------------
    // Inline Edit / Cancel / Save / Delete Category Actions
    // -------------------------------------------------------------
    const categoryTableBody = document.getElementById('categoryTableBody');
    if (categoryTableBody) {
        categoryTableBody.addEventListener('click', function(e) {
            const target = e.target;
            
            // Edit button clicked
            const editBtn = target.closest('.edit-cat-btn');
            if (editBtn) {
                const tr = editBtn.closest('tr');
                tr.querySelector('.cat-name-span').classList.add('d-none');
                tr.querySelector('.cat-name-input').classList.remove('d-none');
                tr.querySelector('.cat-desc-span').classList.add('d-none');
                tr.querySelector('.cat-desc-input').classList.remove('d-none');
                tr.querySelector('.cat-action-view').classList.add('d-none');
                tr.querySelector('.cat-action-edit').classList.remove('d-none');
                return;
            }

            // Cancel button clicked
            const cancelBtn = target.closest('.cancel-cat-btn');
            if (cancelBtn) {
                const tr = cancelBtn.closest('tr');
                tr.querySelector('.cat-name-input').value = tr.querySelector('.cat-name-span').textContent;
                tr.querySelector('.cat-desc-input').value = tr.querySelector('.cat-desc-span').textContent;
                
                tr.querySelector('.cat-name-span').classList.remove('d-none');
                tr.querySelector('.cat-name-input').classList.add('d-none');
                tr.querySelector('.cat-desc-span').classList.remove('d-none');
                tr.querySelector('.cat-desc-input').classList.add('d-none');
                tr.querySelector('.cat-action-view').classList.remove('d-none');
                tr.querySelector('.cat-action-edit').classList.add('d-none');
                return;
            }

            // Save button clicked
            const saveBtn = target.closest('.save-cat-btn');
            if (saveBtn) {
                const tr = saveBtn.closest('tr');
                const catId = tr.getAttribute('data-cat-id');
                const nameVal = tr.querySelector('.cat-name-input').value.trim();
                const descVal = tr.querySelector('.cat-desc-input').value.trim();

                if (!nameVal) {
                    showModalAlert('Category name cannot be empty.', false);
                    return;
                }

                const formData = new FormData();
                formData.append('name', nameVal);
                formData.append('description', descVal);

                fetch(`${base_url}/con-material-category-update/${catId}`, {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showModalAlert('Category updated successfully!', true);
                        tr.querySelector('.cat-name-span').textContent = nameVal;
                        tr.querySelector('.cat-desc-span').textContent = descVal;

                        tr.querySelector('.cat-name-span').classList.remove('d-none');
                        tr.querySelector('.cat-name-input').classList.add('d-none');
                        tr.querySelector('.cat-desc-span').classList.remove('d-none');
                        tr.querySelector('.cat-desc-input').classList.add('d-none');
                        tr.querySelector('.cat-action-view').classList.remove('d-none');
                        tr.querySelector('.cat-action-edit').classList.add('d-none');

                        // Sync in main table category cells
                        document.querySelectorAll(`.category-name-cell[data-cat-id="${catId}"]`).forEach(cell => {
                            cell.innerHTML = `<span class="badge bg-primary text-light border border-primary-subtle px-2 py-1">${escapeHtml(nameVal)}</span>`;
                        });

                        // Sync in filter dropdown
                        const filterSelect = document.getElementById('categoryFilter');
                        if (filterSelect) {
                            const option = filterSelect.querySelector(`option[value="${catId}"]`);
                            if (option) {
                                option.textContent = nameVal;
                            }
                        }
                    } else {
                        showModalAlert(data.error || 'Failed to update category.', false);
                    }
                })
                .catch(err => {
                    console.error(err);
                    showModalAlert('An error occurred.', false);
                });
                return;
            }

            // Delete button clicked
            const deleteBtn = target.closest('.delete-cat-btn');
            if (deleteBtn) {
                const tr = deleteBtn.closest('tr');
                const catId = tr.getAttribute('data-cat-id');
                const catName = tr.querySelector('.cat-name-span').textContent;

                if (!confirm(`Are you sure you want to delete the category "${catName}"?`)) {
                    return;
                }

                fetch(`${base_url}/con-material-category-delete/${catId}`, {
                    method: 'POST'
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showModalAlert('Category deleted successfully!', true);
                        tr.remove();

                        // Re-index modal list numbers
                        document.querySelectorAll('.cat-idx').forEach((td, idx) => {
                            td.textContent = idx + 1;
                        });

                        // Remove from filter dropdown
                        const filterSelect = document.getElementById('categoryFilter');
                        if (filterSelect) {
                            const option = filterSelect.querySelector(`option[value="${catId}"]`);
                            if (option) {
                                option.remove();
                            }
                        }
                    } else {
                        showModalAlert(data.error || 'Failed to delete category.', false);
                    }
                })
                .catch(err => {
                    console.error(err);
                    showModalAlert('An error occurred.', false);
                });
                return;
            }
        });
    }
});
</script>
