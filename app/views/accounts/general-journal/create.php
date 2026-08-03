<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<style>
/* Select2 Light Mode (Default) */
.select2-container--default .select2-selection--single {
    height: 38px;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    padding: 4px 8px;
    background-color: #ffffff !important;
    color: #212529 !important;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 28px;
    color: #212529 !important;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px;
}
.select2-dropdown {
    background-color: #ffffff !important;
    border: 1px solid #ced4da !important;
    color: #212529 !important;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15) !important;
    z-index: 99999 !important;
}
.select2-search--dropdown {
    padding: 6px !important;
    background-color: #ffffff !important;
}
.select2-search__field {
    background-color: #ffffff !important;
    color: #212529 !important;
    border: 1px solid #ced4da !important;
    border-radius: 4px !important;
    padding: 6px 10px !important;
}
.select2-results__option {
    color: #212529 !important;
    background-color: #ffffff !important;
    padding: 8px 12px !important;
    font-size: 13px;
}
.select2-results__option--highlighted[aria-selected] {
    background-color: #20c997 !important;
    color: #ffffff !important;
}

/* Select2 Dark Mode (Conditional Theme Overrides) */
[bd-theme="bd-theme-dark"] .select2-container--default .select2-selection--single,
body.dark-mode .select2-container--default .select2-selection--single,
html[data-bs-theme="dark"] .select2-container--default .select2-selection--single {
    border: 1px solid #334155 !important;
    background-color: #1e293b !important;
    color: #f8fafc !important;
}
[bd-theme="bd-theme-dark"] .select2-container--default .select2-selection--single .select2-selection__rendered,
body.dark-mode .select2-container--default .select2-selection--single .select2-selection__rendered,
html[data-bs-theme="dark"] .select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #f8fafc !important;
}
[bd-theme="bd-theme-dark"] .select2-dropdown,
body.dark-mode .select2-dropdown,
html[data-bs-theme="dark"] .select2-dropdown {
    background-color: #1e293b !important;
    border: 1px solid #475569 !important;
    color: #f8fafc !important;
}
[bd-theme="bd-theme-dark"] .select2-search--dropdown,
body.dark-mode .select2-search--dropdown,
html[data-bs-theme="dark"] .select2-search--dropdown {
    background-color: #1e293b !important;
}
[bd-theme="bd-theme-dark"] .select2-search__field,
body.dark-mode .select2-search__field,
html[data-bs-theme="dark"] .select2-search__field {
    background-color: #0f172a !important;
    color: #ffffff !important;
    border: 1px solid #475569 !important;
}
[bd-theme="bd-theme-dark"] .select2-results__option,
body.dark-mode .select2-results__option,
html[data-bs-theme="dark"] .select2-results__option {
    color: #cbd5e1 !important;
    background-color: #1e293b !important;
}
</style>

<div class="container-fluid mt-4">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h2 class="mb-0">
                <i class="fa-light fa-book"></i> New Journal Entry
            </h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="<?= $base_url ?>/financial-dashboard" class="btn btn-outline-secondary">
                <i class="fa-light fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <form action="<?= $base_url ?>/general-journal/store" method="POST" id="journalForm">
                
                <!-- HEADER SECTION -->
                <h5 class="mb-3 border-bottom pb-2 text-primary">Journal Header</h5>
                <div class="row mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Journal Number</label>
                        <input type="text" class="form-control bg-light" name="journal_number" value="<?= htmlspecialchars($journal_no) ?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="date" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Voucher Type</label>
                        <select class="form-select" name="voucher_type">
                            <option value="General">General (JV)</option>
                            <option value="Opening Balance">Opening Balance</option>
                            <option value="Adjustment">Adjustment</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Project</label>
                        <select class="form-select" name="project_id">
                            <option value="">-- Select Project --</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?= $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Office</label>
                        <select class="form-select" name="office">
                            <option value="">-- Select Office --</option>
                            <option value="Corporate Office">Corporate Office</option>
                            <option value="Site Office">Site Office</option>
                            <option value="Site Office 2">Site Office 2</option>
                            <option value="Zonal Office">Zonal Office</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Narration (Header)</label>
                    <textarea class="form-control" name="main_narration" rows="2" placeholder="Brief description..."></textarea>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3 mt-5">
                    <h5 class="mb-0 text-success"><i class="fa-light fa-list-check me-2"></i>Journal Details (Debits & Credits)</h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-success btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#quickAddCoaModal">
                            <i class="fa-solid fa-plus-circle me-1"></i> + New Account (COA)
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm shadow-sm" onclick="addRow()">
                            <i class="fa-light fa-plus me-1"></i> Add Row
                        </button>
                    </div>
                </div>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-hover align-middle" id="detailsTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 35%">Account <span class="text-danger">*</span></th>
                                <th style="width: 25%">Narration</th>
                                <th style="width: 15%">Debit (BDT)</th>
                                <th style="width: 15%">Credit (BDT)</th>
                                <th style="width: 10%">Action</th>
                            </tr>
                        </thead>
                        <tbody id="detailsTbody">
                            <!-- Two default rows -->
                            <tr>
                                <td>
                                    <div class="row gx-2">
                                        <div class="col-12 mb-2">
                                            <select class="form-select main-category-select" onchange="filterSubCategories(this)">
                                                <option value="">-- Main Category Filter --</option>
                                                <?php foreach ($accounts as $acc): ?>
                                                    <?php if(empty($acc['parent_id'])): ?>
                                                        <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_code'] . ' - ' . $acc['account_name']) ?> (<?= $acc['account_type'] ?>)</option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <select class="form-select sub-category-select" name="account_id[]" required>
                                                <option value="">-- Select Sub Category / Account --</option>
                                                <?php foreach ($accounts as $acc): ?>
                                                    <?php if(!empty($acc['parent_id'])): ?>
                                                        <option value="<?= $acc['id'] ?>">[<?= htmlspecialchars($acc['account_code']) ?>] <?= htmlspecialchars($acc['account_name']) ?> (<?= htmlspecialchars($acc['account_type']) ?>)</option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </td>
                                <td><input type="text" class="form-control" name="narration[]" placeholder="Line description"></td>
                                <td><input type="number" step="0.01" min="0" class="form-control debit-input text-end" name="debit[]" placeholder="0.00" onkeyup="calcTotals()"></td>
                                <td><input type="number" step="0.01" min="0" class="form-control credit-input text-end" name="credit[]" placeholder="0.00" onkeyup="calcTotals()"></td>
                                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)"><i class="fa-light fa-trash"></i></button></td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="row gx-2">
                                        <div class="col-12 mb-2">
                                            <select class="form-select main-category-select" onchange="filterSubCategories(this)">
                                                <option value="">-- Main Category Filter --</option>
                                                <?php foreach ($accounts as $acc): ?>
                                                    <?php if(empty($acc['parent_id'])): ?>
                                                        <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_code'] . ' - ' . $acc['account_name']) ?> (<?= $acc['account_type'] ?>)</option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <select class="form-select sub-category-select" name="account_id[]" required>
                                                <option value="">-- Select Sub Category / Account --</option>
                                                <?php foreach ($accounts as $acc): ?>
                                                    <?php if(!empty($acc['parent_id'])): ?>
                                                        <option value="<?= $acc['id'] ?>">[<?= htmlspecialchars($acc['account_code']) ?>] <?= htmlspecialchars($acc['account_name']) ?> (<?= htmlspecialchars($acc['account_type']) ?>)</option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </td>
                                <td><input type="text" class="form-control" name="narration[]" placeholder="Line description"></td>
                                <td><input type="number" step="0.01" min="0" class="form-control debit-input text-end" name="debit[]" placeholder="0.00" onkeyup="calcTotals()"></td>
                                <td><input type="number" step="0.01" min="0" class="form-control credit-input text-end" name="credit[]" placeholder="0.00" onkeyup="calcTotals()"></td>
                                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)"><i class="fa-light fa-trash"></i></button></td>
                            </tr>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="2" class="text-end text-uppercase">Totals:</th>
                                <th class="text-end"><h5 class="mb-0 text-success" id="totalDebit">0.00</h5></th>
                                <th class="text-end"><h5 class="mb-0 text-danger" id="totalCredit">0.00</h5></th>
                                <th>
                                    <input type="hidden" name="total_debit_val" id="total_debit_val" value="0">
                                    <input type="hidden" name="total_credit_val" id="total_credit_val" value="0">
                                </th>
                            </tr>
                            <tr>
                                <th colspan="2" class="text-end text-uppercase border-bottom-0">Difference:</th>
                                <th colspan="2" class="text-center border-bottom-0">
                                    <h5 class="mb-0 text-danger" id="diffDisplay">0.00</h5>
                                </th>
                                <th class="border-bottom-0"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary btn-lg px-5" id="submitBtn" disabled>
                        <i class="fa-light fa-save"></i> Save Journal Entry
                    </button>
                    <p class="text-muted small mt-2" id="validationMsg">Total Debits must equal Total Credits and be greater than 0 to save.</p>
                </div>

            </form>
        </div>
    </div>
</div>

<!-- Quick Add New COA Account Modal -->
<div id="quickAddCoaModal" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <form id="quickCoaForm" onsubmit="saveQuickCoa(event)">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-solid fa-plus-circle me-1 text-success"></i> Create New Chart of Account (COA)</h5>
                    <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa-solid fa-xmark-large"></i>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <div id="quickCoaAlert" class="alert alert-danger" style="display:none;"></div>
                    <div class="row gy-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">1. Account Level</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="level_add" id="qLevelMain" value="main">
                                <label class="btn btn-outline-primary btn-sm" for="qLevelMain">Main Category</label>

                                <input type="radio" class="btn-check" name="level_add" id="qLevelSub" value="sub">
                                <label class="btn btn-outline-primary btn-sm" for="qLevelSub">Sub Category</label>

                                <input type="radio" class="btn-check" name="level_add" id="qLevelParent" value="parent">
                                <label class="btn btn-outline-primary btn-sm" for="qLevelParent">Parent Ledger</label>

                                <input type="radio" class="btn-check" name="level_add" id="qLevelLedger" value="ledger" checked>
                                <label class="btn btn-outline-primary btn-sm" for="qLevelLedger">Ledger (Child Account)</label>
                            </div>
                        </div>

                        <div class="col-12" id="qParentGroup">
                            <label class="form-label fw-bold">2. Select Parent Account *</label>
                            <select class="form-select" name="parent_id" id="qParentSelect">
                                <option value="">-- Select Parent --</option>
                                <?php foreach ($accounts as $a): ?>
                                    <option value="<?= $a['id'] ?>">
                                        [<?= $a['account_code'] ?>] <?= htmlspecialchars($a['account_name']) ?> (<?= !empty($a['level']) ? strtoupper($a['level']) : 'ACCOUNT' ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">3. Account Code *</label>
                            <input type="text" class="form-control font-monospace" name="account_code" id="q_account_code" required placeholder="e.g. 1110-005">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">4. Account Name *</label>
                            <input type="text" class="form-control" name="account_name" id="q_account_name" required placeholder="e.g. Brac Bank Main Account">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4" id="saveCoaBtn"><i class="fa-solid fa-save me-1"></i> Save & Select Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php ob_start(); ?>
    const allAccounts = <?= json_encode($accounts) ?>;

    const mainCategoryOptions = `
        <option value="">-- Main Category Filter --</option>
        <?php foreach ($accounts as $acc): ?>
            <?php if(empty($acc['parent_id'])): ?>
                <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_code'] . ' - ' . $acc['account_name']) ?> (<?= $acc['account_type'] ?>)</option>
            <?php endif; ?>
        <?php endforeach; ?>
    `;

    const subCategoryOptionsAll = `
        <option value="">-- Select Sub Category / Account --</option>
        <?php foreach ($accounts as $acc): ?>
            <?php if(!empty($acc['parent_id'])): ?>
                <option value="<?= $acc['id'] ?>">[<?= htmlspecialchars($acc['account_code']) ?>] <?= htmlspecialchars($acc['account_name']) ?> (<?= htmlspecialchars($acc['account_type']) ?>)</option>
            <?php endif; ?>
        <?php endforeach; ?>
    `;

    const accountsHtml = `
        <div class="row gx-2">
            <div class="col-12 mb-2">
                <select class="form-select main-category-select" onchange="filterSubCategories(this)">
                    ${mainCategoryOptions}
                </select>
            </div>
            <div class="col-12">
                <select class="form-select sub-category-select" name="account_id[]" required>
                    ${subCategoryOptionsAll}
                </select>
            </div>
        </div>
    `;

    function saveQuickCoa(e) {
        e.preventDefault();
        $('#quickCoaAlert').hide();
        $('#saveCoaBtn').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...');

        $.ajax({
            url: window.base_url + '/accounts/coa/store-ajax',
            type: 'POST',
            data: $('#quickCoaForm').serialize(),
            dataType: 'json',
            success: function(res) {
                $('#saveCoaBtn').prop('disabled', false).html('<i class="fa-solid fa-save me-1"></i> Save & Select Account');
                if (res.success && res.account) {
                    const acc = res.account;
                    allAccounts.push(acc);

                    // Append to all sub-category dropdowns
                    const newOpt = `<option value="${acc.id}" selected>[${acc.account_code}] ${acc.account_name} (${acc.account_type})</option>`;
                    $('.sub-category-select').append(newOpt).val(acc.id);

                    var modalEl = document.getElementById('quickAddCoaModal');
                    var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                    modal.hide();

                    $('#quickCoaForm')[0].reset();
                    alert("✓ New Chart of Account [" + acc.account_code + " - " + acc.account_name + "] created and selected!");
                } else {
                    $('#quickCoaAlert').text(res.message || 'Failed to create account.').show();
                }
            },
            error: function() {
                $('#saveCoaBtn').prop('disabled', false).html('<i class="fa-solid fa-save me-1"></i> Save & Select Account');
                $('#quickCoaAlert').text('An error occurred while saving the account.').show();
            }
        });
    }

    function filterSubCategories(selectElement) {
        const parentId = $(selectElement).val();
        const subSelect = $(selectElement).closest('td').find('.sub-category-select');
        const selectedVal = subSelect.val();
        
        subSelect.empty();
        subSelect.append('<option value="">-- Select Sub Category / Account --</option>');
        
        if (parentId) {
            const children = allAccounts.filter(acc => acc.parent_id == parentId);
            children.forEach(child => {
                subSelect.append(`<option value="${child.id}" ${child.id == selectedVal ? 'selected' : ''}>[${child.account_code}] ${child.account_name} (${child.account_type})</option>`);
            });
        } else {
            allAccounts.forEach(acc => {
                if (acc.parent_id) {
                    subSelect.append(`<option value="${acc.id}" ${acc.id == selectedVal ? 'selected' : ''}>[${acc.account_code}] ${acc.account_name} (${acc.account_type})</option>`);
                }
            });
        }
    }

    function addRow() {
        const row = `
        <tr>
            <td>${accountsHtml}</td>
            <td><input type="text" class="form-control" name="narration[]" placeholder="Line description"></td>
            <td><input type="number" step="0.01" min="0" class="form-control debit-input text-end" name="debit[]" placeholder="0.00" onkeyup="calcTotals()"></td>
            <td><input type="number" step="0.01" min="0" class="form-control credit-input text-end" name="credit[]" placeholder="0.00" onkeyup="calcTotals()"></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)"><i class="fa-light fa-trash"></i></button></td>
        </tr>
        `;
        $('#detailsTbody').append(row);
    }

    function removeRow(btn) {
        $(btn).closest('tr').remove();
        calcTotals();
    }

    function calcTotals() {
        let totalDebit = 0;
        let totalCredit = 0;

        $('.debit-input').each(function() {
            let val = parseFloat($(this).val()) || 0;
            totalDebit += val;
        });

        $('.credit-input').each(function() {
            let val = parseFloat($(this).val()) || 0;
            totalCredit += val;
        });

        let diff = Math.abs(totalDebit - totalCredit);

        $('#totalDebit').text(totalDebit.toLocaleString('en-BD', {minimumFractionDigits: 2, maximumFractionDigits:2}));
        $('#totalCredit').text(totalCredit.toLocaleString('en-BD', {minimumFractionDigits: 2, maximumFractionDigits:2}));
        $('#total_debit_val').val(totalDebit);
        $('#total_credit_val').val(totalCredit);

        if (diff === 0 && totalDebit > 0) {
            $('#diffDisplay').text('Balanced!').removeClass('text-danger').addClass('text-success');
            $('#submitBtn').prop('disabled', false);
            $('#validationMsg').hide();
        } else {
            $('#diffDisplay').text(diff.toLocaleString('en-BD', {minimumFractionDigits: 2, maximumFractionDigits:2})).removeClass('text-success').addClass('text-danger');
            $('#submitBtn').prop('disabled', true);
            $('#validationMsg').show();
        }
    }
<?php $page_script = ob_get_clean(); ?>
