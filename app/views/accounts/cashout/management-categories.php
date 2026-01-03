<?php
$page_title = "Manage Categories";
$page = 'Management';
$addPage = true;
$show_breadcrumb = true;
?>
<style>
.card__icon span {
    color: var(--clr-theme-primary);
    background: rgba(108, 95, 252, 0.1);
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    text-align: center;
    justify-content: center;
    align-items: center;
    font-size: 16px;
}
.card__title {
    font-size: 1.25rem;
}
</style>
<div class="row">
    <!-- Statistics Cards -->
    <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-layer-group"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Categories</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title fs-6"><?= $statistics['total_categories'] ?? 0 ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-check-circle"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Active Categories</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title fs-6"><?= $statistics['active_categories'] ?? 0 ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-1">
                <div class="card__icon">
                    <span><i class="fa-light fa-times-circle"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Inactive Categories</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title fs-6"><?= $statistics['inactive_categories'] ?? 0 ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Table -->
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="card__header d-flex justify-content-between align-items-center mb-3">
                <h5 class="card__title" style="margin-top: -13px;">Management Categories</h5>
                <div class="d-flex gap-2">
                    <a href="<?= $GLOBALS['base_url'] ?>/management-cash-out" class="btn btn-secondary">
                        <i class="fa-light fa-arrow-left me-2"></i> Back to Transactions
                    </a>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="fa-light fa-plus me-2"></i> Add New Category
                    </button>
                </div>
            </div>
            <div class="card__body">
                <div id="alertContainer"></div>

                <div class="table__wrapper table-responsive">
                    <table class="table mb-20 multiple_tables">
                        <thead>
                            <tr class="table__title table__sort">
                                <th>#</th>
                                <th>Category Name</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody class="table__body" id="categoriesTableBody">
                            <?php if (!empty($categories)): ?>
                                <?php foreach ($categories as $index => $category): ?>
                                    <tr id="category-row-<?= $category['id'] ?>">
                                        <td><?= $index + 1 ?></td>
                                        <td><strong><?= htmlspecialchars($category['category_name']) ?></strong></td>
                                        <td>
                                            <small><?= htmlspecialchars($category['description'] ?? 'N/A') ?></small>
                                        </td>
                                        <td>
                                            <?php if ($category['status'] == 1): ?>
                                                <span class="bd-badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="bd-badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($category['created_by_name'] ?? 'N/A') ?></td>
                                        <td><?= date('d M, Y', strtotime($category['created_at'])) ?></td>
                                        <td>
                                            <div class="d-flex align-items-center justify-content-start gap-10">
                                                <a href="#" class="table__icon edit" onclick="editCategory(<?= $category['id'] ?>)" title="Edit">
                                                    <i class="fa-sharp fa-light fa-pen"></i>
                                                </a>
                                                <a href="#" class="table__icon" onclick="toggleStatus(<?= $category['id'] ?>)" title="Toggle Status">
                                                    <i class="fa-light fa-toggle-on"></i>
                                                </a>
                                                <a href="#" class="removeBtn table__icon delete" onclick="deleteCategory(<?= $category['id'] ?>)" title="Delete">
                                                    <i class="fa-regular fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fa-light fa-layer-group fa-2x mb-3"></i><br>
                                            No categories found
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Category</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="addCategoryForm">
                    <div class="mb-3">
                        <label class="form-label">Category Name: <span class="text-danger">*</span></label>
                        <input type="text" name="category_name" class="form-control" 
                               placeholder="e.g., Office Supplies, Travel, Utilities" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description:</label>
                        <textarea name="description" class="form-control" rows="3" 
                                  placeholder="Optional description for this category"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status:</label>
                        <select name="status" class="form-control">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="saveCategory()">
                    <i class="fa-light fa-check me-2"></i> Save Category
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="editCategoryForm">
                    <input type="hidden" name="id" id="edit_category_id">
                    <div class="mb-3">
                        <label class="form-label">Category Name: <span class="text-danger">*</span></label>
                        <input type="text" name="category_name" id="edit_category_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description:</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status:</label>
                        <select name="status" id="edit_status" class="form-control">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="updateCategory()">
                    <i class="fa-light fa-check me-2"></i> Update Category
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function showAlert(message, type = 'success') {
    const alertContainer = document.getElementById('alertContainer');
    if (!alertContainer) {
        console.error('Alert container not found');
        return;
    }
    
    alertContainer.innerHTML = '';
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    alertContainer.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

function saveCategory() {
    const form = document.getElementById('addCategoryForm');
    if (!form) {
        console.error('Add category form not found');
        return;
    }
    
    const formData = new FormData(form);
    
    const saveBtn = event.target;
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fa-light fa-spinner fa-spin me-2"></i> Saving...';
    
    fetch('<?= $GLOBALS['base_url'] ?>/add-management-category', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text();
    })
    .then(text => {
        console.log('Raw response:', text);
        const jsonStart = text.indexOf('{');
        const jsonEnd = text.lastIndexOf('}') + 1;
        const cleanJson = jsonStart >= 0 ? text.substring(jsonStart, jsonEnd) : text;
        console.log('Clean JSON:', cleanJson);
        try {
            const data = JSON.parse(cleanJson);
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
            
            if (data.success) {
                const modalElement = document.getElementById('addCategoryModal');
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    modal.hide();
                }
                
                setTimeout(() => {
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => backdrop.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.removeProperty('overflow');
                    document.body.style.removeProperty('padding-right');
                }, 300);
                
                form.reset();
                showAlert(data.message, 'success');
                
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showAlert(data.message || 'Error adding category.', 'danger');
            }
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response text:', text);
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
            showAlert('Invalid response from server. Check console for details.', 'danger');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
        showAlert('Error adding category: ' + error.message, 'danger');
    });
}

function editCategory(categoryId) {
    if (event) event.preventDefault();
    
    console.log('Editing category:', categoryId);
    
    fetch(`<?= $GLOBALS['base_url'] ?>/get-category-details?id=${categoryId}`)
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text();
    })
    .then(text => {
        console.log('Raw response:', text);
        const jsonStart = text.indexOf('{');
        const jsonEnd = text.lastIndexOf('}') + 1;
        const cleanJson = jsonStart >= 0 ? text.substring(jsonStart, jsonEnd) : text;
        console.log('Clean JSON:', cleanJson);
        try {
            const data = JSON.parse(cleanJson);
            
            if (data.error) {
                showAlert(data.error, 'danger');
                return;
            }
            
            console.log('Category data:', data);
            
            const idField = document.getElementById('edit_category_id');
            const categoryNameField = document.getElementById('edit_category_name');
            const descriptionField = document.getElementById('edit_description');
            const statusField = document.getElementById('edit_status');
            
            if (!idField || !categoryNameField || !descriptionField || !statusField) {
                console.error('One or more form fields not found');
                showAlert('Form fields not found. Please refresh the page.', 'danger');
                return;
            }
            
            idField.value = data.id || '';
            categoryNameField.value = data.category_name || '';
            descriptionField.value = data.description || '';
            statusField.value = data.status || '1';
            
            console.log('Form populated with:', {
                id: idField.value,
                category_name: categoryNameField.value,
                description: descriptionField.value,
                status: statusField.value
            });
            
            const modalElement = document.getElementById('editCategoryModal');
            if (!modalElement) {
                console.error('Edit modal not found');
                showAlert('Edit modal not found. Please refresh the page.', 'danger');
                return;
            }
            
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response text:', text);
            showAlert('Invalid response from server. Check console for details.', 'danger');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        showAlert('Error loading category: ' + error.message, 'danger');
    });
}

function updateCategory() {
    const form = document.getElementById('editCategoryForm');
    if (!form) {
        console.error('Edit category form not found');
        return;
    }
    
    const formData = new FormData(form);
    console.log('Updating category with data:', Object.fromEntries(formData));
    
    const updateBtn = event.target;
    const originalText = updateBtn.innerHTML;
    updateBtn.disabled = true;
    updateBtn.innerHTML = '<i class="fa-light fa-spinner fa-spin me-2"></i> Updating...';
    
    fetch('<?= $GLOBALS['base_url'] ?>/update-management-category', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text();
    })
    .then(text => {
        console.log('Raw response:', text);
        const jsonStart = text.indexOf('{');
        const jsonEnd = text.lastIndexOf('}') + 1;
        const cleanJson = jsonStart >= 0 ? text.substring(jsonStart, jsonEnd) : text;
        console.log('Clean JSON:', cleanJson);
        try {
            const data = JSON.parse(cleanJson);
            updateBtn.disabled = false;
            updateBtn.innerHTML = originalText;
            
            if (data.success) {
                const modalElement = document.getElementById('editCategoryModal');
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    modal.hide();
                }
                
                setTimeout(() => {
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => backdrop.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.removeProperty('overflow');
                    document.body.style.removeProperty('padding-right');
                }, 300);
                
                form.reset();
                showAlert(data.message, 'success');
                
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showAlert(data.message || 'Error updating category.', 'danger');
            }
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response text:', text);
            updateBtn.disabled = false;
            updateBtn.innerHTML = originalText;
            showAlert('Invalid response from server. Check console for details.', 'danger');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        updateBtn.disabled = false;
        updateBtn.innerHTML = originalText;
        showAlert('Error updating category: ' + error.message, 'danger');
    });
}

function toggleStatus(categoryId) {
    if (event) event.preventDefault();
    
    if (!confirm('Are you sure you want to toggle the status of this category?')) {
        return;
    }
    
    console.log('Toggling status for category:', categoryId);
    
    fetch(`<?= $GLOBALS['base_url'] ?>/toggle-category-status?id=${categoryId}`, {
        method: 'POST'
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text();
    })
    .then(text => {
        console.log('Raw response:', text);
        const jsonStart = text.indexOf('{');
        const jsonEnd = text.lastIndexOf('}') + 1;
        const cleanJson = jsonStart >= 0 ? text.substring(jsonStart, jsonEnd) : text;
        console.log('Clean JSON:', cleanJson);
        try {
            const data = JSON.parse(cleanJson);
            
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showAlert(data.message || 'Error toggling status.', 'danger');
            }
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response text:', text);
            showAlert('Invalid response from server. Check console for details.', 'danger');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        showAlert('Error toggling status: ' + error.message, 'danger');
    });
}

function deleteCategory(categoryId) {
    if (event) event.preventDefault();
    
    if (!confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
        return;
    }
    
    console.log('Deleting category:', categoryId);
    
    fetch(`<?= $GLOBALS['base_url'] ?>/delete-management-category?id=${categoryId}`, {
        method: 'POST'
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text();
    })
    .then(text => {
        console.log('Raw response:', text);
        const jsonStart = text.indexOf('{');
        const jsonEnd = text.lastIndexOf('}') + 1;
        const cleanJson = jsonStart >= 0 ? text.substring(jsonStart, jsonEnd) : text;
        console.log('Clean JSON:', cleanJson);
        try {
            const data = JSON.parse(cleanJson);
            
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showAlert(data.message || 'Error deleting category.', 'danger');
            }
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response text:', text);
            showAlert('Invalid response from server. Check console for details.', 'danger');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        showAlert('Error deleting category: ' + error.message, 'danger');
    });
}

// Clean up modals on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded, initializing modal cleanup');
    
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('overflow');
    document.body.style.removeProperty('padding-right');
    
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function() {
            setTimeout(() => {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => backdrop.remove());
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
            }, 100);
        });
    });
});
</script>