<?php
$page_title = "Manage Management Roles";
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

    .action-transaction-buttons {
        display: flex;
        flex-wrap: wrap;
        justify-content: end;
    }

    @media (max-width: 640px) {
        .card__wrapper {
            padding: 26px 15px;
        }

        .card__header {
            margin-top: 5px;
            flex-direction: column;
        }

        .action-transaction-buttons {
            margin-top: 10px;
            flex-wrap: nowrap
        }

        .btn {
            font-size: 16px;
            border-radius: 6px;
            padding: 8px;
            display: inline-block;
            margin-top: 6px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            font-size: 12px;
            padding: 6px 8px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            font-size: 12px;
            padding: 6px 16px;
        }
    }

    @media (max-width: 575px) {
        .card__wrapper {
            padding: 26px 15px;
        }

        .card__header {
            margin-top: 5px;
            flex-direction: column;
        }

        .action-transaction-buttons {
            margin-top: 10px;
            flex-wrap: nowrap
        }

.btn {
    font-size: 14px;
    border-radius: 6px;
    padding: 12px;
    display: inline-block;
    margin-top: 15px;
}

        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            font-size: 12px;
            padding: 6px 8px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            font-size: 12px;
            padding: 6px 16px;
        }
    }

    @media (max-width: 400px) {
.btn {
    font-size: 12px;
    border-radius: 6px;
    padding: 10px;
    display: inline-block;
    margin-top: 15px;
}
    }
</style>
<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="card__header d-flex justify-content-between align-items-center mb-3">
                <h5 class="card__title" style="margin-top: -13px;">Management Roles</h5>
                <div class="d-flex gap-2">
                    <a href="<?= $GLOBALS['base_url'] ?>/management-cash-in" class="btn btn-secondary">
                        <i class="fa-light fa-arrow-left me-2"></i> Back to Transactions
                    </a>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                        <i class="fa-light fa-plus me-2"></i> Add New Role
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
                                <th>Role Name</th>
                                <th>Person Name</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody class="table__body" id="rolesTableBody">
                            <?php if (!empty($roles)): ?>
                                <?php foreach ($roles as $index => $role): ?>
                                    <tr id="role-row-<?= $role['id'] ?>">
                                        <td><?= $index + 1 ?></td>
                                        <td><strong><?= htmlspecialchars($role['role_name']) ?></strong></td>
                                        <td><?= htmlspecialchars($role['person_name']) ?></td>
                                        <td>
                                            <?php if ($role['status'] == 1): ?>
                                                <span class="bd-badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="bd-badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($role['created_by_name'] ?? 'N/A') ?></td>
                                        <td><?= date('d M, Y', strtotime($role['created_at'])) ?></td>
                                        <td>
                                            <div class="d-flex align-items-center justify-content-start gap-10">
                                                <a href="#" class="table__icon edit" onclick="editRole(<?= $role['id'] ?>)" title="Edit">
                                                    <i class="fa-sharp fa-light fa-pen"></i>
                                                </a>
                                                <a href="#" class="table__icon" onclick="toggleStatus(<?= $role['id'] ?>)" title="Toggle Status">
                                                    <i class="fa-light fa-toggle-on"></i>
                                                </a>
                                                <a href="#" class="removeBtn table__icon delete" onclick="deleteRole(<?= $role['id'] ?>)" title="Delete">
                                                    <i class="fa-regular fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fa-light fa-users-gear fa-2x mb-3"></i><br>
                                            No roles found
                                        </div>
                                    </td>
                                </tr> -->
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Role Modal -->
<div class="modal fade" id="addRoleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Role</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="addRoleForm">
                    <div class="mb-3">
                        <label class="form-label">Role Name: <span class="text-danger">*</span></label>
                        <input type="text" name="role_name" class="form-control" 
                               placeholder="e.g., CEO, Manager, Director" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Person Name: <span class="text-danger">*</span></label>
                        <input type="text" name="person_name" class="form-control" 
                               placeholder="e.g., John Doe" required>
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
                <button type="button" class="btn btn-success" onclick="saveRole()">
                    <i class="fa-light fa-check me-2"></i> Save Role
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Role Modal -->
<div class="modal fade" id="editRoleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Role</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="editRoleForm">
                    <input type="hidden" name="id" id="edit_role_id">
                    <div class="mb-3">
                        <label class="form-label">Role Name: <span class="text-danger">*</span></label>
                        <input type="text" name="role_name" id="edit_role_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Person Name: <span class="text-danger">*</span></label>
                        <input type="text" name="person_name" id="edit_person_name" class="form-control" required>
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
                <button type="button" class="btn btn-success" onclick="updateRole()">
                    <i class="fa-light fa-check me-2"></i> Update Role
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
    
    alertContainer.innerHTML = ''; // Clear existing alerts
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

function saveRole() {
    const form = document.getElementById('addRoleForm');
    if (!form) {
        console.error('Add role form not found');
        return;
    }
    
    const formData = new FormData(form);
    
    // Disable button to prevent double submission
    const saveBtn = event.target;
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fa-light fa-spinner fa-spin me-2"></i> Saving...';
    
    fetch('<?= $GLOBALS['base_url'] ?>/add-management-role', {
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
        // Strip any content before the JSON starts
        const jsonStart = text.indexOf('{');
        const jsonEnd = text.lastIndexOf('}') + 1;
        const cleanJson = jsonStart >= 0 ? text.substring(jsonStart, jsonEnd) : text;
        console.log('Clean JSON:', cleanJson);
        try {
            const data = JSON.parse(cleanJson);
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
            
            if (data.success) {
                // Close modal properly
                const modalElement = document.getElementById('addRoleModal');
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    modal.hide();
                }
                
                // Remove backdrop manually
                setTimeout(() => {
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => backdrop.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.removeProperty('overflow');
                    document.body.style.removeProperty('padding-right');
                }, 300);
                
                // Reset form
                form.reset();
                
                // Show success message
                showAlert(data.message, 'success');
                
                // Reload after a delay to show the alert
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showAlert(data.message || 'Error adding role.', 'danger');
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
        showAlert('Error adding role: ' + error.message, 'danger');
    });
}

function editRole(roleId) {
    if (event) event.preventDefault();
    
    console.log('Editing role:', roleId);
    
    fetch(`<?= $GLOBALS['base_url'] ?>/get-role-details?id=${roleId}`)
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text();
    })
    .then(text => {
        console.log('Raw response:', text);
        // Strip any content before the JSON starts
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
            
            console.log('Role data:', data);
            
            // Populate form fields
            const idField = document.getElementById('edit_role_id');
            const roleNameField = document.getElementById('edit_role_name');
            const personNameField = document.getElementById('edit_person_name');
            const statusField = document.getElementById('edit_status');
            
            if (!idField || !roleNameField || !personNameField || !statusField) {
                console.error('One or more form fields not found');
                showAlert('Form fields not found. Please refresh the page.', 'danger');
                return;
            }
            
            idField.value = data.id || '';
            roleNameField.value = data.role_name || '';
            personNameField.value = data.person_name || '';
            statusField.value = data.status || '1';
            
            console.log('Form populated with:', {
                id: idField.value,
                role_name: roleNameField.value,
                person_name: personNameField.value,
                status: statusField.value
            });
            
            // Show modal
            const modalElement = document.getElementById('editRoleModal');
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
        showAlert('Error loading role: ' + error.message, 'danger');
    });
}

function updateRole() {
    const form = document.getElementById('editRoleForm');
    if (!form) {
        console.error('Edit role form not found');
        return;
    }
    
    const formData = new FormData(form);
    console.log('Updating role with data:', Object.fromEntries(formData));
    
    // Disable button to prevent double submission
    const updateBtn = event.target;
    const originalText = updateBtn.innerHTML;
    updateBtn.disabled = true;
    updateBtn.innerHTML = '<i class="fa-light fa-spinner fa-spin me-2"></i> Updating...';
    
    fetch('<?= $GLOBALS['base_url'] ?>/update-management-role', {
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
        // Strip any content before the JSON starts
        const jsonStart = text.indexOf('{');
        const jsonEnd = text.lastIndexOf('}') + 1;
        const cleanJson = jsonStart >= 0 ? text.substring(jsonStart, jsonEnd) : text;
        console.log('Clean JSON:', cleanJson);
        try {
            const data = JSON.parse(cleanJson);
            updateBtn.disabled = false;
            updateBtn.innerHTML = originalText;
            
            if (data.success) {
                // Close modal properly
                const modalElement = document.getElementById('editRoleModal');
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    modal.hide();
                }
                
                // Remove backdrop manually
                setTimeout(() => {
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => backdrop.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.removeProperty('overflow');
                    document.body.style.removeProperty('padding-right');
                }, 300);
                
                // Reset form
                form.reset();
                
                // Show success message
                showAlert(data.message, 'success');
                
                // Reload after a delay
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showAlert(data.message || 'Error updating role.', 'danger');
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
        showAlert('Error updating role: ' + error.message, 'danger');
    });
}

function toggleStatus(roleId) {
    if (event) event.preventDefault();
    
    if (!confirm('Are you sure you want to toggle the status of this role?')) {
        return;
    }
    
    console.log('Toggling status for role:', roleId);
    
    fetch(`<?= $GLOBALS['base_url'] ?>/toggle-role-status?id=${roleId}`, {
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
        // Strip any content before the JSON starts
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

function deleteRole(roleId) {
    if (event) event.preventDefault();
    
    if (!confirm('Are you sure you want to delete this role? This action cannot be undone.')) {
        return;
    }
    
    console.log('Deleting role:', roleId);
    
    fetch(`<?= $GLOBALS['base_url'] ?>/delete-management-role?id=${roleId}`, {
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
        // Strip any content before the JSON starts
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
                showAlert(data.message || 'Error deleting role.', 'danger');
            }
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response text:', text);
            showAlert('Invalid response from server. Check console for details.', 'danger');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        showAlert('Error deleting role: ' + error.message, 'danger');
    });
}

// Clean up modals on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded, initializing modal cleanup');
    
    // Remove any stuck backdrops
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('overflow');
    document.body.style.removeProperty('padding-right');
    
    // Add event listeners to modals
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