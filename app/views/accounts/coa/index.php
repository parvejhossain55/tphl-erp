<?php 
// Layout handled by Controller via master.php
?>

<div class="container-fluid mt-4">
    <!-- Header Area -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h2 class="mb-1  fw-bold">
                <i class="fa-solid fa-sitemap text-primary me-2"></i> Chart of Accounts
            </h2>
            <p class="text-muted mb-0">Standard 4-Tier Real Estate General Ledger Accounts Hierarchy (Assets, Liabilities, Equity, Revenue, Expenses)</p>
        </div>
        <div class="col-md-6 text-end">
            <a href="<?= $base_url ?>/financial-dashboard" class="btn btn-outline-secondary me-2">
                <i class="fa-light fa-arrow-left me-1"></i> Back to Dashboard
            </a>
            <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addAccountModal">
                <i class="fa-light fa-plus me-1"></i> Add New Account
            </button>
        </div>
    </div>

    <!-- Quick Info Cards -->
    <div class="row mb-4">
        <div class="col-md-20 col-sm-6" style="width: 20%;">
            <div class="card border-0 shadow-sm bg-primary  p-3 rounded-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class=" text-uppercase fw-bold">1000 Assets</small>
                        <h5 class="mb-0 fw-bold">সম্পদসমূহ</h5>
                    </div>
                    <i class="fa-light fa-building-columns fa-2x "></i>
                </div>
            </div>
        </div>
        <div class="col-md-20 col-sm-6" style="width: 20%;">
            <div class="card border-0 shadow-sm bg-danger  p-3 rounded-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class=" text-uppercase fw-bold">2000 Liabilities</small>
                        <h5 class="mb-0 fw-bold">দায়সমূহ</h5>
                    </div>
                    <i class="fa-light fa-scale-unbalanced fa-2x "></i>
                </div>
            </div>
        </div>
        <div class="col-md-20 col-sm-6" style="width: 20%;">
            <div class="card border-0 shadow-sm bg-success  p-3 rounded-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class=" text-uppercase fw-bold">3000 Equity</small>
                        <h5 class="mb-0 fw-bold">মূলধন</h5>
                    </div>
                    <i class="fa-light fa-coins fa-2x "></i>
                </div>
            </div>
        </div>
        <div class="col-md-20 col-sm-6" style="width: 20%;">
            <div class="card border-0 shadow-sm bg-info  p-3 rounded-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class=" text-uppercase fw-bold">4000 Revenue</small>
                        <h5 class="mb-0 fw-bold">রাজস্ব ও ইনকাম</h5>
                    </div>
                    <i class="fa-light fa-chart-line-up fa-2x "></i>
                </div>
            </div>
        </div>
        <div class="col-md-20 col-sm-6" style="width: 20%;">
            <div class="card border-0 shadow-sm bg-warning  p-3 rounded-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class=" text-uppercase fw-bold">5000 Expenses</small>
                        <h5 class="mb-0 fw-bold">ব্যয় ও খরচ</h5>
                    </div>
                    <i class="fa-light fa-file-invoice-dollar fa-2x "></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm">
            <i class="fa-light fa-check-circle me-2"></i> <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm">
            <i class="fa-light fa-exclamation-circle me-2"></i> <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Main Table Card -->
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle" id="coaTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 120px;">Code</th>
                            <th>Account Name</th>
                            <th style="width: 110px;">Level</th>
                            <th style="width: 110px;">Type</th>
                            <th>Parent Account</th>
                            <th style="width: 100px;">Status</th>
                            <th class="text-end" style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($accounts as $acc): 
                            $level = $acc['level'] ?? 'ledger';
                            $rootType = strtolower($acc['root_type'] ?? $acc['account_type']);
                            
                            $levelBadge = match($level) {
                                'main' => '<span class="badge bg-primary px-2 py-1"><i class="fa-solid fa-folder me-1"></i>MAIN</span>',
                                'sub' => '<span class="badge bg-info  px-2 py-1"><i class="fa-solid fa-folder-open me-1"></i>SUB</span>',
                                'parent' => '<span class="badge bg-warning  px-2 py-1"><i class="fa-solid fa-sitemap me-1"></i>PARENT</span>',
                                default => '<span class="badge bg-secondary px-2 py-1"><i class="fa-solid fa-file me-1"></i>LEDGER</span>'
                            };

                            $typeBadge = match($rootType) {
                                'asset' => '<span class="badge bg-primary ">Asset</span>',
                                'liability' => '<span class="badge bg-danger ">Liability</span>',
                                'equity' => '<span class="badge bg-success ">Equity</span>',
                                'income', 'revenue' => '<span class="badge bg-info ">Revenue</span>',
                                'expense' => '<span class="badge bg-warning ">Expense</span>',
                                default => '<span class="badge bg-dark">'.ucfirst($rootType).'</span>'
                            };
                            
                            $indent = match($level) {
                                'main' => 'fw-bold text-primary fs-6',
                                'sub' => 'ps-3 fw-bold ',
                                'parent' => 'ps-4 ',
                                default => 'ps-5 text-secondary'
                            };
                        ?>
                        <tr>
                            <td>
                                <span class=" font-monospace border px-2 py-1 fs-6">
                                    <?= htmlspecialchars($acc['account_code']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="<?= $indent ?>">
                                    <?= htmlspecialchars($acc['account_name']) ?>
                                    <?php if (!empty($acc['is_system_account'])): ?>
                                        <span class="text-primary border ms-2" style="font-size: 10px;" title="System Protected Account">
                                            <i class="fa-solid fa-shield-halved me-1"></i>System
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?= $levelBadge ?></td>
                            <td><?= $typeBadge ?></td>
                            <td>
                                <?php if (!empty($acc['parent_name'])): ?>
                                    <small class="text-muted">
                                        <i class="fa-light fa-level-up me-1"></i>
                                        <strong>[<?= htmlspecialchars($acc['parent_code'] ?? '') ?>]</strong> <?= htmlspecialchars($acc['parent_name']) ?>
                                    </small>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($acc['status'] === 'active'): ?>
                                    <span class="badge 
                                    bg-success-subtle text-success border border-success px-2 py-1">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= $base_url ?>/accounts/ledger/<?= $acc['id'] ?>" class="btn btn-sm btn-outline-info me-1" title="View General Ledger Statement">
                                    <i class="fa-light fa-book-open"></i> Ledger
                                </a>
                                <?php if (empty($acc['is_system_account'])): ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                        onclick='openEditModal(<?= json_encode($acc) ?>)'>
                                        <i class="fa-light fa-pen-to-square"></i> Edit
                                    </button>
                                    <a href="<?= $base_url ?>/accounts/coa/delete/<?= $acc['id'] ?>" 
                                        class="btn btn-sm btn-outline-danger" 
                                        onclick="return confirm('Are you sure you want to delete account [<?= htmlspecialchars($acc['account_code']) ?> - <?= htmlspecialchars($acc['account_name']) ?>]?');">
                                        <i class="fa-light fa-trash"></i> Delete
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted border"><i class="fa-solid fa-lock me-1"></i>Locked</span>
                                <?php endif; ?>
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
<!-- Add New Account Modal -->
<div id="addAccountModal" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <form action="<?= $base_url ?>/accounts/coa/store" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-solid fa-plus-circle me-1 text-primary"></i> Add New Chart of Account</h5>
                    <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa-solid fa-xmark-large"></i>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <div class="row gy-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">1. Account Level</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="level_add" id="levelMainAdd" value="main">
                                <label class="btn btn-outline-primary btn-sm" for="levelMainAdd">Main Category</label>

                                <input type="radio" class="btn-check" name="level_add" id="levelSubAdd" value="sub">
                                <label class="btn btn-outline-primary btn-sm" for="levelSubAdd">Sub Category</label>

                                <input type="radio" class="btn-check" name="level_add" id="levelParentAdd" value="parent">
                                <label class="btn btn-outline-primary btn-sm" for="levelParentAdd">Parent Ledger</label>

                                <input type="radio" class="btn-check" name="level_add" id="levelLedgerAdd" value="ledger" checked>
                                <label class="btn btn-outline-primary btn-sm" for="levelLedgerAdd">Ledger (Child Account)</label>
                            </div>
                        </div>

                        <div class="col-12" id="parentGroupAdd">
                            <label class="form-label fw-bold">2. Select Parent Account *</label>
                            <select class="form-select" name="parent_id" id="parentSelectAdd" onchange="fetchNextCode('add')">
                                <option value="">-- Select Parent --</option>
                                <?php foreach ($accounts as $a): ?>
                                    <option value="<?= $a['id'] ?>" data-level="<?= $a['level'] ?>" data-type="<?= $a['account_type'] ?>">
                                        [<?= $a['account_code'] ?>] <?= htmlspecialchars($a['account_name']) ?> (<?= strtoupper($a['level']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12" id="typeGroupAdd" style="display:none;">
                            <label class="form-label fw-bold">Account Root Type *</label>
                            <select class="form-select" name="account_type" id="typeSelectAdd">
                                <option value="Asset">Asset (সম্পদ)</option>
                                <option value="Liability">Liability (দায়)</option>
                                <option value="Equity">Equity (মূলধন)</option>
                                <option value="Revenue">Revenue (আয়)</option>
                                <option value="Expense">Expense (ব্যয়)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">3. Account Code *</label>
                            <div class="input-group">
                                <input type="text" class="form-control font-monospace" name="account_code" id="add_account_code" required placeholder="e.g. 1110-004">
                                <button type="button" class="btn btn-outline-secondary" onclick="fetchNextCode('add')">Auto Code</button>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">4. Account Name *</label>
                            <input type="text" class="form-control" name="account_name" required placeholder="e.g. City Bank Main Account">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4"><i class="fa-solid fa-save me-1"></i> Save Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Account Modal -->
<div id="editAccountModal" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <form action="<?= $base_url ?>/accounts/coa/update" method="POST">
                <input type="hidden" name="account_id" id="edit_account_id">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-solid fa-pen-to-square me-1 text-primary"></i> Edit Account</h5>
                    <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa-solid fa-xmark-large"></i>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <div class="row gy-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Account Level</label>
                            <input type="text" class="form-control bg-light" id="edit_level_display" readonly>
                            <input type="hidden" name="level_edit" id="edit_level">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Account Code *</label>
                            <input type="text" class="form-control font-monospace" name="account_code" id="edit_account_code" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">Parent Account</label>
                            <select class="form-select" name="parent_id" id="edit_parent_id">
                                <option value="">-- No Parent (Main) --</option>
                                <?php foreach ($accounts as $a): ?>
                                    <option value="<?= $a['id'] ?>">[<?= $a['account_code'] ?>] <?= htmlspecialchars($a['account_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-bold">Account Name *</label>
                            <input type="text" class="form-control" name="account_name" id="edit_account_name" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Status</label>
                            <select class="form-select" name="status" id="edit_status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4"><i class="fa-solid fa-save me-1"></i> Update Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php ob_start(); ?>
    $(document).ready(function() {
        // Append modals to body to fix Bootstrap z-index backdrop overlay issue
        $('#addAccountModal').appendTo('body');
        $('#editAccountModal').appendTo('body');

        // Initialize Select2 with Search Option inside Modals
        $('#parentSelectAdd').select2({
            dropdownParent: $('#addAccountModal'),
            width: '100%',
            placeholder: '-- Search & Select Parent Account --'
        }).on('change', function() {
            fetchNextCode('add');
        });

        $('#edit_parent_id').select2({
            dropdownParent: $('#editAccountModal'),
            width: '100%',
            placeholder: '-- Search & Select Parent Account --'
        });

        // Re-initialize Select2 when modal opens to ensure search box focuses properly
        $('#addAccountModal').on('shown.bs.modal', function () {
            $('#parentSelectAdd').select2({
                dropdownParent: $('#addAccountModal'),
                width: '100%'
            });
        });

        $('#editAccountModal').on('shown.bs.modal', function () {
            $('#edit_parent_id').select2({
                dropdownParent: $('#editAccountModal'),
                width: '100%'
            });
        });

        // Initialize DataTables with EXACT 20 Rows per page pagination as requested
        $('#coaTable').DataTable({
            pageLength: 20,
            lengthMenu: [[10, 20, 50, 100, -1], [10, 20, 50, 100, "All"]],
            order: [[0, 'asc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search accounts by code or name..."
            }
        });

        // Trigger level toggle on change
        $('input[name="level_add"]').on('change', function() {
            let level = $(this).val();
            if (level === 'main') {
                $('#parentGroupAdd').hide();
                $('#typeGroupAdd').show();
            } else {
                $('#parentGroupAdd').show();
                $('#typeGroupAdd').hide();
            }
            fetchNextCode('add');
        });
    });

    function fetchNextCode(mode) {
        let parentId = (mode === 'add') ? $('#parentSelectAdd').val() : $('#edit_parent_id').val();
        let level = (mode === 'add') ? $('input[name="level_add"]:checked').val() : $('#edit_level').val();

        $.get(window.base_url + '/accounts/coa', { action: 'getNextCode', parent_id: parentId, level: level }, function(res) {
            if (res && res.code) {
                if (mode === 'add') $('#add_account_code').val(res.code);
            }
        });
    }

    function openEditModal(account) {
        $('#edit_account_id').val(account.id);
        $('#edit_account_code').val(account.account_code);
        $('#edit_account_name').val(account.account_name);
        $('#edit_parent_id').val(account.parent_id).trigger('change');
        $('#edit_status').val(account.status);
        $('#edit_level').val(account.level || 'ledger');
        $('#edit_level_display').val((account.level || 'ledger').toUpperCase());

        var modalElement = document.getElementById('editAccountModal');
        var modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
        modal.show();
    }
<?php $page_script = ob_get_clean(); ?>
