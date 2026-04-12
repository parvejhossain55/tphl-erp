<?php
$page_title = "Bank Registry";
$page = 'Bank Registry';
$addPage = false;
$show_breadcrumb = true;
?>

<div class="row">

    <!-- Header & Add Button -->
    <div class="col-xxl-12 mb-4">
        <div class="card__wrapper">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1"><i class="fa-light fa-building-columns me-2"></i>Bank Registry Management</h4>
                    <p class="text-muted small mb-0">Manage active banks and branches for use in cash-in/cash-out forms.</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBankModal">
                    <i class="fa-light fa-plus me-2"></i>Add New Bank
                </button>
            </div>
        </div>
    </div>

    <!-- Banks Table -->
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table multiple_tables" id="banksTable">
                    <thead>
                        <tr class="table__title table__sort">
                            <th>#</th>
                            <th>Bank Name</th>
                            <th>Branch Name</th>
                            <th>Account Number</th>
                            <th>Routing Number</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php if (!empty($banks)): ?>
                            <?php foreach ($banks as $i => $bank): ?>
                                <tr id="bank-row-<?= $bank['id'] ?>">
                                    <td><?= $i + 1 ?></td>
                                    <td><strong><?= htmlspecialchars($bank['bank_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($bank['branch_name']) ?></td>
                                    <td><span class="text-muted"><?= htmlspecialchars($bank['account_number'] ?? '—') ?></span></td>
                                    <td><span class="text-muted"><?= htmlspecialchars($bank['routing_number'] ?? '—') ?></span></td>
                                    <td>
                                        <?php if ($bank['status'] === 'active'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d M Y', strtotime($bank['created_at'])) ?></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <button class="table__icon edit"
                                                onclick="openEditModal(<?= htmlspecialchars(json_encode($bank)) ?>)"
                                                title="Edit">
                                                <i class="fa-light fa-pen"></i>
                                            </button>
                                            <button class="table__icon"
                                                style="background:#f0fdf4; color:#16a34a;"
                                                onclick="toggleStatus(<?= $bank['id'] ?>)"
                                                title="Toggle Status"
                                                id="toggle-<?= $bank['id'] ?>">
                                                <i class="fa-light fa-toggle-<?= $bank['status'] === 'active' ? 'on' : 'off' ?>"></i>
                                            </button>
                                            <button class="table__icon delete"
                                                onclick="deleteBank(<?= $bank['id'] ?>)"
                                                title="Delete">
                                                <i class="fa-light fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="fa-light fa-building-columns fa-2x mb-3 d-block"></i>
                                    No banks registered yet. Click "Add New Bank" to get started.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Bank Modal -->
<div class="modal fade" id="addBankModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-light fa-building-columns me-2"></i>Add New Bank</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="addBankForm">
                    <div class="mb-3">
                        <label class="form-label">Bank Name <span class="text-danger">*</span></label>
                        <input type="text" name="bank_name" class="form-control" placeholder="e.g. Dutch-Bangla Bank" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Branch Name <span class="text-danger">*</span></label>
                        <input type="text" name="branch_name" class="form-control" placeholder="e.g. Gulshan Branch" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Account Number</label>
                        <input type="text" name="account_number" class="form-control" placeholder="Account number">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Routing Number</label>
                        <input type="text" name="routing_number" class="form-control" placeholder="Optional">
                    </div>
                    <div class="d-flex gap-2 justify-content-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-light fa-save me-2"></i>Save Bank
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Bank Modal -->
<div class="modal fade" id="editBankModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-light fa-pen me-2"></i>Edit Bank</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="editBankForm">
                    <input type="hidden" name="id" id="edit_bank_id">
                    <div class="mb-3">
                        <label class="form-label">Bank Name <span class="text-danger">*</span></label>
                        <input type="text" name="bank_name" id="edit_bank_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Branch Name <span class="text-danger">*</span></label>
                        <input type="text" name="branch_name" id="edit_branch_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Account Number</label>
                        <input type="text" name="account_number" id="edit_account_number" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Routing Number</label>
                        <input type="text" name="routing_number" id="edit_routing_number" class="form-control">
                    </div>
                    <div class="d-flex gap-2 justify-content-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-light fa-save me-2"></i>Update Bank
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const BASE = '<?= $GLOBALS['base_url'] ?>';

// ---- ADD ----
document.getElementById('addBankForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const data = new FormData(this);
    fetch(BASE + '/store-bank', { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showToast('Bank added successfully!', 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(res.message || 'Error saving bank', 'error');
            }
        });
});

// ---- EDIT modal population ----
function openEditModal(bank) {
    document.getElementById('edit_bank_id').value     = bank.id;
    document.getElementById('edit_bank_name').value   = bank.bank_name;
    document.getElementById('edit_branch_name').value = bank.branch_name;
    document.getElementById('edit_account_number').value = bank.account_number || '';
    document.getElementById('edit_routing_number').value = bank.routing_number || '';
    new bootstrap.Modal(document.getElementById('editBankModal')).show();
}

document.getElementById('editBankForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const data = new FormData(this);
    fetch(BASE + '/update-bank', { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showToast('Bank updated!', 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(res.message || 'Update failed', 'error');
            }
        });
});

// ---- TOGGLE Status ----
function toggleStatus(id) {
    fetch(BASE + '/toggle-bank-status', { method: 'POST', body: new URLSearchParams({ id }) })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const row = document.getElementById('bank-row-' + id);
                const badgeEl = row.querySelector('.badge');
                const toggleBtn = document.getElementById('toggle-' + id);
                if (res.new_status === 'active') {
                    badgeEl.className = 'badge bg-success'; badgeEl.textContent = 'Active';
                    toggleBtn.querySelector('i').className = 'fa-light fa-toggle-on';
                } else {
                    badgeEl.className = 'badge bg-secondary'; badgeEl.textContent = 'Inactive';
                    toggleBtn.querySelector('i').className = 'fa-light fa-toggle-off';
                }
            }
        });
}

// ---- DELETE ----
function deleteBank(id) {
    if (!confirm('Delete this bank entry? This action cannot be undone.')) return;
    fetch(BASE + '/delete-bank', { method: 'POST', body: new URLSearchParams({ id }) })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                document.getElementById('bank-row-' + id)?.remove();
                showToast('Bank deleted', 'success');
            } else {
                showToast(res.message || 'Delete failed', 'error');
            }
        });
}

// Simple toast helper
function showToast(msg, type) {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed shadow`;
    toast.style.cssText = 'top:20px;right:20px;z-index:9999;min-width:260px;animation:fadeIn .3s ease';
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2800);
}
</script>

<style>
@keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; } }
.card__icon span {
    color: var(--clr-theme-primary);
    background: rgba(108, 95, 252, 0.1);
    width: 40px; height: 40px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center; font-size: 16px;
}
</style>
