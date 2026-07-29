<?php 
// Layout handled by Controller via master.php
?>

<div class="container-fluid mt-4">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h2 class="mb-0">
                <i class="fa-light fa-sitemap"></i> Chart of Accounts
            </h2>
            <p class="text-muted">Manage main categories and sub-accounts dynamically.</p>
        </div>
        <div class="col-md-6 text-end">
            <a href="<?= $base_url ?>/financial-dashboard" class="btn btn-outline-secondary me-2">
                <i class="fa-light fa-arrow-left"></i> Back to Dashboard
            </a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAccountModal">
                <i class="fa-light fa-plus"></i> Add Account
            </button>
        </div>
    </div>

    <div class="alert alert-info shadow-sm mb-4">
        <h5 class="alert-heading"><i class="fa-light fa-book-open"></i> Service Manual: Chart of Accounts (Real Estate Standard)</h5>
        <p class="mb-1">This page manages the foundational GL (General Ledger) accounts for the entire double-entry accounting system. All operational modules automatically post debits and credits here based on the account structure.</p>
        <hr>
        <p class="mb-0"><strong>Standard Hierarchy Guide:</strong></p>
        <ul class="mb-0">
            <li><strong>1000 Assets:</strong> Bank/Cash, Receivables, and <strong>Construction Work-in-Progress (WIP)</strong>. All project-tagged expenses are capitalized to WIP.</li>
            <li><strong>2000 Liabilities:</strong> Accounts Payable (Vendors/Contractors/Employees) and Customer Advances (Unearned Revenue).</li>
            <li><strong>3000 Equity:</strong> Owner's capital and Retained Earnings.</li>
            <li><strong>4000 Revenue:</strong> Recognized only when a flat is handed over (transferred from Customer Advances).</li>
            <li><strong>5000 COGS:</strong> Cost of Goods Sold (transferred from WIP upon project handover).</li>
            <li><strong>6000 Expenses:</strong> Head Office operations, salaries, and non-project marketing costs.</li>
        </ul>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle" id="coaTable">
                    <thead class="table-light">
                        <tr>
                            <th>Account Code</th>
                            <th>Main Category</th>
                            <th>Sub Category</th>
                            <th>Account Type</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($accounts as $acc): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($acc['account_code']) ?></strong></td>
                            
                            <?php if (empty($acc['parent_id'])): // It's a Main Category ?>
                                <td><span class="fw-bold text-primary"><?= htmlspecialchars($acc['account_name']) ?></span></td>
                                <td><span class="text-muted">-</span></td>
                            <?php else: // It's a Sub Category ?>
                                <td><span class="text-muted"><?= htmlspecialchars($acc['parent_name']) ?></span></td>
                                <td><span class="fw-bold"><?= htmlspecialchars($acc['account_name']) ?></span></td>
                            <?php endif; ?>

                            <td><span class="badge bg-secondary"><?= htmlspecialchars($acc['account_type']) ?></span></td>
                            <td>
                                <?php if($acc['status'] == 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-info" 
                                    onclick='openEditModal(<?= json_encode($acc) ?>)'>
                                    <i class="fa-light fa-edit"></i> Edit
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Account Modal -->
<div class="modal fade" id="addAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= $base_url ?>/accounts/coa/store" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Category Level</label><br>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="level_add" id="levelMainAdd" value="main" checked>
                            <label class="form-check-label" for="levelMainAdd">Main Category</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="level_add" id="levelSubAdd" value="sub">
                            <label class="form-check-label" for="levelSubAdd">Sub Category</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Account Code *</label>
                        <input type="text" class="form-control" name="account_code" required placeholder="e.g. 1020">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Account Name *</label>
                        <input type="text" class="form-control" name="account_name" required placeholder="e.g. Petty Cash">
                    </div>
                    <div class="mb-3" id="typeGroupAdd">
                        <label class="form-label">Account Type *</label>
                        <select class="form-select" name="account_type" id="typeSelectAdd" required>
                            <option value="">-- Select Type --</option>
                            <option value="Asset">Asset</option>
                            <option value="Liability">Liability</option>
                            <option value="Equity">Equity</option>
                            <option value="Revenue">Revenue</option>
                            <option value="Expense">Expense</option>
                        </select>
                    </div>
                    <div class="mb-3" id="parentGroupAdd" style="display: none;">
                        <label class="form-label">Parent (Main Category) *</label>
                        <select class="form-select" name="parent_id" id="parentSelectAdd">
                            <option value="">-- Select Main Category --</option>
                            <?php foreach ($accounts as $a): ?>
                                <?php if(empty($a['parent_id'])): ?>
                                    <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['account_code'] . ' - ' . $a['account_name']) ?> (<?= $a['account_type'] ?>)</option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Account</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Account Modal -->
<div class="modal fade" id="editAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= $base_url ?>/accounts/coa/update" method="POST">
            <input type="hidden" name="account_id" id="edit_account_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Category Level</label><br>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="level_edit" id="levelMainEdit" value="main">
                            <label class="form-check-label" for="levelMainEdit">Main Category</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="level_edit" id="levelSubEdit" value="sub">
                            <label class="form-check-label" for="levelSubEdit">Sub Category</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Account Code *</label>
                        <input type="text" class="form-control" name="account_code" id="edit_account_code" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Account Name *</label>
                        <input type="text" class="form-control" name="account_name" id="edit_account_name" required>
                    </div>
                    <div class="mb-3" id="typeGroupEdit">
                        <label class="form-label">Account Type *</label>
                        <select class="form-select" name="account_type" id="edit_account_type" required>
                            <option value="Asset">Asset</option>
                            <option value="Liability">Liability</option>
                            <option value="Equity">Equity</option>
                            <option value="Revenue">Revenue</option>
                            <option value="Expense">Expense</option>
                        </select>
                    </div>
                    <div class="mb-3" id="parentGroupEdit" style="display: none;">
                        <label class="form-label">Parent (Main Category) *</label>
                        <select class="form-select" name="parent_id" id="edit_parent_id">
                            <option value="">-- Select Main Category --</option>
                            <?php foreach ($accounts as $a): ?>
                                <?php if(empty($a['parent_id'])): ?>
                                    <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['account_code'] . ' - ' . $a['account_name']) ?> (<?= $a['account_type'] ?>)</option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" id="edit_status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Account</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php ob_start(); ?>
    $(document).ready(function() {
        $('#coaTable').DataTable({
            pageLength: 50,
            order: [[0, 'asc']] // Order by Account Code
        });

        // Bind radio change events for category level toggling
        $('input[name="level_add"]').on('change click', function() {
            toggleLevel('add', $(this).val());
        });
        $('input[name="level_edit"]').on('change click', function() {
            toggleLevel('edit', $(this).val());
        });
    });

    function openEditModal(account) {
        $('#edit_account_id').val(account.id);
        $('#edit_account_code').val(account.account_code);
        $('#edit_account_name').val(account.account_name);
        $('#edit_account_type').val(account.account_type);
        $('#edit_parent_id').val(account.parent_id);
        $('#edit_status').val(account.status);
        
        let levelVal = account.parent_id ? 'sub' : 'main';
        if (levelVal === 'sub') {
            $('#levelSubEdit').prop('checked', true);
        } else {
            $('#levelMainEdit').prop('checked', true);
        }
        toggleLevel('edit', levelVal);

        // Hide the current account from the parent dropdown to prevent self-parenting
        $('#edit_parent_id option').show();
        $('#edit_parent_id option[value="' + account.id + '"]').hide();
        
        var modal = new bootstrap.Modal(document.getElementById('editAccountModal'));
        modal.show();
    }

    function toggleLevel(mode, explicitVal = null) {
        let selectedVal = explicitVal || $('input[name="level_' + mode + '"]:checked').val();
        let isMain = (selectedVal === 'main');
        
        // Target elements relative to the open modal if there are DOM duplicates
        let modalId = mode === 'add' ? '#addAccountModal' : '#editAccountModal';
        let typeSelect = mode === 'add' ? $('#typeSelectAdd') : $('#edit_account_type');
        let typeGroup = mode === 'add' ? $('#typeGroupAdd') : $('#typeGroupEdit');
        let parentSelect = mode === 'add' ? $('#parentSelectAdd') : $('#edit_parent_id');
        let parentGroup = mode === 'add' ? $('#parentGroupAdd') : $('#parentGroupEdit');

        // Force targeting within the specific modal contexts in case of clones
        if ($(modalId).length) {
            typeGroup = $(modalId).find(mode === 'add' ? '#typeGroupAdd' : '#typeGroupEdit');
            typeSelect = $(modalId).find(mode === 'add' ? '#typeSelectAdd' : '#edit_account_type');
            parentGroup = $(modalId).find(mode === 'add' ? '#parentGroupAdd' : '#parentGroupEdit');
            parentSelect = $(modalId).find(mode === 'add' ? '#parentSelectAdd' : '#edit_parent_id');
        }

        if (isMain) {
            typeGroup.show();
            typeSelect.prop('required', true);
            
            parentGroup.hide();
            parentSelect.prop('required', false);
            parentSelect.val('');
        } else {
            typeGroup.hide();
            typeSelect.prop('required', false);
            typeSelect.val('');
            
            parentGroup.show();
            parentSelect.prop('required', true);
        }
    }
<?php $page_script = ob_get_clean(); ?>
