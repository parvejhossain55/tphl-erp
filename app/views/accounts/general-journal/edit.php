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
                <i class="fa-light fa-edit"></i> Edit Journal Entry
            </h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="<?= $base_url ?>/general-journal/view/<?= $journal['id'] ?>" class="btn btn-outline-secondary">
                <i class="fa-light fa-arrow-left"></i> Cancel
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <form action="<?= $base_url ?>/general-journal/update/<?= $journal['id'] ?>" method="POST" id="journalForm">
                
                <!-- HEADER SECTION -->
                <h5 class="mb-3 border-bottom pb-2 text-primary">Journal Header</h5>
                <div class="row mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Journal Number</label>
                        <input type="text" class="form-control bg-light" name="journal_number" value="<?= htmlspecialchars($journal['journal_number']) ?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control bg-light" name="date" value="<?= htmlspecialchars($journal['date']) ?>" readonly>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Voucher Type</label>
                        <select class="form-select" name="voucher_type">
                            <option value="General" <?= $journal['voucher_type'] == 'General' ? 'selected' : '' ?>>General (JV)</option>
                            <option value="Opening Balance" <?= $journal['voucher_type'] == 'Opening Balance' ? 'selected' : '' ?>>Opening Balance</option>
                            <option value="Adjustment" <?= $journal['voucher_type'] == 'Adjustment' ? 'selected' : '' ?>>Adjustment</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Project</label>
                        <select class="form-select" name="project_id">
                            <option value="">-- Select Project --</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?= $project['id'] ?>" <?= ($journal['project_id'] == $project['id']) ? 'selected' : '' ?>><?= htmlspecialchars($project['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Office</label>
                        <select class="form-select" name="office">
                            <option value="">-- Select Office --</option>
                            <option value="Corporate Office" <?= ($journal['office'] == 'Corporate Office') ? 'selected' : '' ?>>Corporate Office</option>
                            <option value="Site Office" <?= ($journal['office'] == 'Site Office') ? 'selected' : '' ?>>Site Office</option>
                            <option value="Site Office 2" <?= ($journal['office'] == 'Site Office 2') ? 'selected' : '' ?>>Site Office 2</option>
                            <option value="Zonal Office" <?= ($journal['office'] == 'Zonal Office') ? 'selected' : '' ?>>Zonal Office</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Narration (Header)</label>
                    <textarea class="form-control" name="main_narration" rows="2" placeholder="Brief description..."><?= htmlspecialchars($journal['narration']) ?></textarea>
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
                                <th style="width: 42%">Account <span class="text-danger">*</span></th>
                                <th style="width: 23%">Narration</th>
                                <th style="width: 12%">Debit (BDT)</th>
                                <th style="width: 12%">Credit (BDT)</th>
                                <th style="width: 11%" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="detailsTbody">
                            <?php foreach ($details as $index => $detail): ?>
                            <tr>
                                <td style="width: 42%">
                                    <div class="account-cascade-container" data-account-id="<?= $detail['account_id'] ?>">
                                        <input type="hidden" class="selected-account-id" name="account_id[]" required value="<?= $detail['account_id'] ?>">
                                        <div class="mb-1">
                                            <select class="form-select form-select-sm select2-cascade lvl-main-select">
                                                <option value="">1. Main Category (e.g. Assets)</option>
                                            </select>
                                        </div>
                                        <div class="mb-1">
                                            <select class="form-select form-select-sm select2-cascade lvl-sub-select">
                                                <option value="">2. Sub Category (e.g. Non-Current Assets)</option>
                                            </select>
                                        </div>
                                        <div class="mb-1">
                                            <select class="form-select form-select-sm select2-cascade lvl-parent-select">
                                                <option value="">3. Parent Head (e.g. Property Plant & Eq)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <select class="form-select form-select-sm select2-cascade lvl-ledger-select">
                                                <option value="">4. Final Account Head (e.g. Furniture & Fixtures) *</option>
                                            </select>
                                        </div>
                                    </div>
                                </td>
                                <td><input type="text" class="form-control" name="narration[]" value="<?= htmlspecialchars($detail['narration']) ?>" placeholder="Line description"></td>
                                <td><input type="number" step="0.01" min="0" class="form-control debit-input text-end" name="debit[]" value="<?= $detail['debit'] == 0 ? '' : $detail['debit'] ?>" placeholder="0.00" onkeyup="calcTotals()"></td>
                                <td><input type="number" step="0.01" min="0" class="form-control credit-input text-end" name="credit[]" value="<?= $detail['credit'] == 0 ? '' : $detail['credit'] ?>" placeholder="0.00" onkeyup="calcTotals()"></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)"><i class="fa-light fa-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="2" class="text-end text-uppercase">Totals:</th>
                                <th class="text-end"><h5 class="mb-0 text-success" id="totalDebit"><?= number_format($journal['total_debit'], 2) ?></h5></th>
                                <th class="text-end"><h5 class="mb-0 text-danger" id="totalCredit"><?= number_format($journal['total_credit'], 2) ?></h5></th>
                                <th>
                                    <input type="hidden" name="total_debit_val" id="total_debit_val" value="<?= $journal['total_debit'] ?>">
                                    <input type="hidden" name="total_credit_val" id="total_credit_val" value="<?= $journal['total_credit'] ?>">
                                </th>
                            </tr>
                            <tr>
                                <th colspan="2" class="text-end text-uppercase border-bottom-0">Difference:</th>
                                <th colspan="2" class="text-center border-bottom-0">
                                    <h5 class="mb-0" id="diffDisplay">0.00</h5>
                                </th>
                                <th class="border-bottom-0"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-success btn-lg px-5" id="submitBtn">
                        <i class="fa-light fa-save"></i> Update Journal Entry
                    </button>
                    <p class="text-muted small mt-2" id="validationMsg" style="display: none;">Total Debits must equal Total Credits to save.</p>
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
                            <select class="form-select select2-quick-parent" name="parent_id" id="qParentSelect">
                                <option value="">-- Search & Select Parent Account --</option>
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
    const accountsMap = {};
    allAccounts.forEach(acc => {
        accountsMap[acc.id] = acc;
    });

    function getDirectChildren(parentId) {
        if (!parentId) return [];
        return allAccounts.filter(acc => acc.parent_id == parentId);
    }

    function getDescendantIds(parentId) {
        if (!parentId) return [];
        let descendants = [];
        let queue = [parentId];
        while (queue.length > 0) {
            let curr = queue.shift();
            let children = allAccounts.filter(acc => acc.parent_id == curr);
            children.forEach(c => {
                descendants.push(c.id);
                queue.push(c.id);
            });
        }
        return descendants;
    }

    function populateSelectOptions($select, options, selectedVal, placeholderText) {
        let currentVal = selectedVal !== undefined ? selectedVal : ($select.val() || '');
        $select.empty();
        $select.append(`<option value="">${placeholderText}</option>`);
        options.forEach(acc => {
            let codeStr = acc.account_code ? `[${acc.account_code}] ` : '';
            let isSel = (acc.id == currentVal) ? 'selected' : '';
            $select.append(`<option value="${acc.id}" ${isSel}>${codeStr}${acc.account_name}</option>`);
        });
        if ($select.hasClass('select2-hidden-accessible')) {
            $select.trigger('change.select2');
        }
    }

    let isCascadeSyncing = false;

    function syncRowCascade(container, triggeredLevel) {
        if (isCascadeSyncing) return;
        isCascadeSyncing = true;

        const $c = $(container);
        const $main = $c.find('.lvl-main-select');
        const $sub = $c.find('.lvl-sub-select');
        const $parent = $c.find('.lvl-parent-select');
        const $ledger = $c.find('.lvl-ledger-select');
        const $hidden = $c.find('.selected-account-id');

        let mainVal = $main.val();
        let subVal = $sub.val();
        let parentVal = $parent.val();
        let ledgerVal = $ledger.val() || $c.attr('data-account-id') || $hidden.val() || '';

        // 1. REVERSE AUTO-FILL & ANCESTOR TRACING (Bottom -> Up)
        if ((triggeredLevel === 'ledger' || triggeredLevel === 'init') && ledgerVal) {
            let acc = accountsMap[ledgerVal];
            if (acc) {
                if (acc.level === 'parent') {
                    parentVal = acc.id;
                    ledgerVal = '';
                } else if (acc.level === 'sub') {
                    subVal = acc.id;
                    parentVal = '';
                    ledgerVal = '';
                } else if (acc.level === 'main' || !acc.parent_id) {
                    mainVal = acc.id;
                    subVal = '';
                    parentVal = '';
                    ledgerVal = '';
                }

                let curr = acc;
                while (curr && curr.parent_id) {
                    let p = accountsMap[curr.parent_id];
                    if (p) {
                        if (p.level === 'parent') parentVal = p.id;
                        else if (p.level === 'sub') subVal = p.id;
                        else if (p.level === 'main' || !p.parent_id) mainVal = p.id;
                        curr = p;
                    } else break;
                }
            }
        } else if (triggeredLevel === 'parent' && parentVal) {
            let acc = accountsMap[parentVal];
            if (acc) {
                let curr = acc;
                while (curr && curr.parent_id) {
                    let p = accountsMap[curr.parent_id];
                    if (p) {
                        if (p.level === 'sub') subVal = p.id;
                        else if (p.level === 'main' || !p.parent_id) mainVal = p.id;
                        curr = p;
                    } else break;
                }
            }
            ledgerVal = '';
        } else if (triggeredLevel === 'sub' && subVal) {
            let acc = accountsMap[subVal];
            if (acc && acc.parent_id) {
                mainVal = acc.parent_id;
            }
            parentVal = '';
            ledgerVal = '';
        } else if (triggeredLevel === 'main') {
            subVal = '';
            parentVal = '';
            ledgerVal = '';
        }

        // 2. POPULATE FILTERED OPTIONS (Top -> Down)
        const mainList = allAccounts.filter(a => a.level === 'main' || !a.parent_id);
        populateSelectOptions($main, mainList, mainVal, '1. Main Category (e.g. Assets)');

        let subList = mainVal ? getDirectChildren(mainVal) : allAccounts.filter(a => a.level === 'sub');
        populateSelectOptions($sub, subList, subVal, '2. Sub Category (e.g. Non-Current Assets)');

        let parentList = [];
        if (subVal) {
            parentList = getDirectChildren(subVal);
        } else if (mainVal) {
            let descIds = getDescendantIds(mainVal);
            parentList = allAccounts.filter(a => a.level === 'parent' && descIds.includes(a.id));
        } else {
            parentList = allAccounts.filter(a => a.level === 'parent');
        }
        populateSelectOptions($parent, parentList, parentVal, '3. Parent Head (e.g. Property Plant & Eq)');

        let ledgerList = [];
        if (parentVal) {
            ledgerList = getDirectChildren(parentVal);
        } else if (subVal) {
            let descIds = getDescendantIds(subVal);
            ledgerList = allAccounts.filter(a => (a.level === 'ledger' || !a.level) && descIds.includes(a.id));
        } else if (mainVal) {
            let descIds = getDescendantIds(mainVal);
            ledgerList = allAccounts.filter(a => (a.level === 'ledger' || !a.level) && descIds.includes(a.id));
        } else {
            ledgerList = allAccounts.filter(a => a.level === 'ledger' || !a.level || a.parent_id);
        }
        populateSelectOptions($ledger, ledgerList, ledgerVal, '4. Final Account Head (e.g. Furniture & Fixtures) *');

        // Set final account ID for submission
        let finalChosenId = ledgerVal || parentVal || subVal || mainVal || '';
        $hidden.val(finalChosenId);

        isCascadeSyncing = false;
    }

    function createAccountCascadeHtml(preselectedId = '') {
        return `
        <div class="account-cascade-container" data-account-id="${preselectedId}">
            <input type="hidden" class="selected-account-id" name="account_id[]" required value="${preselectedId}">
            <div class="mb-1">
                <select class="form-select form-select-sm select2-cascade lvl-main-select">
                    <option value="">1. Main Category (e.g. Assets)</option>
                </select>
            </div>
            <div class="mb-1">
                <select class="form-select form-select-sm select2-cascade lvl-sub-select">
                    <option value="">2. Sub Category (e.g. Non-Current Assets)</option>
                </select>
            </div>
            <div class="mb-1">
                <select class="form-select form-select-sm select2-cascade lvl-parent-select">
                    <option value="">3. Parent Head (e.g. Property Plant & Eq)</option>
                </select>
            </div>
            <div>
                <select class="form-select form-select-sm select2-cascade lvl-ledger-select">
                    <option value="">4. Final Account Head (e.g. Furniture & Fixtures) *</option>
                </select>
            </div>
        </div>
        `;
    }

    function initRowSelect2($row) {
        $row.find('.select2-cascade').select2({
            width: '100%'
        });

        const $c = $row.find('.account-cascade-container');

        $row.find('.lvl-main-select').on('change', function() {
            syncRowCascade($c, 'main');
        });
        $row.find('.lvl-sub-select').on('change', function() {
            syncRowCascade($c, 'sub');
        });
        $row.find('.lvl-parent-select').on('change', function() {
            syncRowCascade($c, 'parent');
        });
        $row.find('.lvl-ledger-select').on('change', function() {
            syncRowCascade($c, 'ledger');
        });
    }

    function addRow(preselectedId = '') {
        const cascadeHtml = createAccountCascadeHtml(preselectedId);
        const row = `
        <tr>
            <td style="width: 42%">${cascadeHtml}</td>
            <td><input type="text" class="form-control" name="narration[]" placeholder="Line description"></td>
            <td><input type="number" step="0.01" min="0" class="form-control debit-input text-end" name="debit[]" placeholder="0.00" onkeyup="calcTotals()"></td>
            <td><input type="number" step="0.01" min="0" class="form-control credit-input text-end" name="credit[]" placeholder="0.00" onkeyup="calcTotals()"></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)"><i class="fa-light fa-trash"></i></button></td>
        </tr>
        `;
        const $newRow = $(row);
        $('#detailsTbody').append($newRow);
        initRowSelect2($newRow);

        const $container = $newRow.find('.account-cascade-container');
        if (preselectedId) {
            syncRowCascade($container, 'ledger');
        } else {
            syncRowCascade($container, 'init');
        }
    }

    function removeRow(btn) {
        if ($('#detailsTbody tr').length <= 2) {
            alert("A journal entry requires at least 2 detail rows.");
            return;
        }
        $(btn).closest('tr').remove();
        calcTotals();
    }

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
                    accountsMap[acc.id] = acc;

                    // Add a new row and select the new account
                    addRow(acc.id);

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

    function initQuickParentSelect2() {
        $('#qParentSelect').select2({
            dropdownParent: $('#quickAddCoaModal'),
            width: '100%',
            placeholder: '-- Search & Select Parent Account --'
        });
    }

    function updateQuickParentOptions() {
        const selectedLevel = $('input[name="level_add"]:checked').val();
        const $parentSelect = $('#qParentSelect');
        const currVal = $parentSelect.val();
        $parentSelect.empty();

        if (selectedLevel === 'main') {
            $('#qParentGroup').hide();
            $parentSelect.prop('required', false);
        } else {
            $('#qParentGroup').show();
            $parentSelect.prop('required', true);
            
            let eligibleParents = [];
            if (selectedLevel === 'sub') {
                eligibleParents = allAccounts.filter(a => a.level === 'main' || !a.parent_id);
                $parentSelect.append('<option value="">-- Search Main Category Parent --</option>');
            } else if (selectedLevel === 'parent') {
                eligibleParents = allAccounts.filter(a => a.level === 'sub');
                $parentSelect.append('<option value="">-- Search Sub Category Parent --</option>');
            } else {
                eligibleParents = allAccounts.filter(a => a.level === 'parent');
                $parentSelect.append('<option value="">-- Search Parent Head --</option>');
            }

            if (eligibleParents.length === 0) {
                eligibleParents = allAccounts.filter(a => a.level !== 'ledger');
            }

            eligibleParents.forEach(a => {
                let codeStr = a.account_code ? `[${a.account_code}] ` : '';
                let lvlStr = a.level ? ` (${a.level.toUpperCase()})` : '';
                let isSel = (a.id == currVal) ? 'selected' : '';
                $parentSelect.append(`<option value="${a.id}" ${isSel}>${codeStr}${a.account_name}${lvlStr}</option>`);
            });
        }

        if ($parentSelect.hasClass('select2-hidden-accessible')) {
            $parentSelect.trigger('change.select2');
        }
    }

    $(document).ready(function() {
        $('#detailsTbody tr').each(function() {
            initRowSelect2($(this));
            const $container = $(this).find('.account-cascade-container');
            const accId = $container.attr('data-account-id');
            if (accId) {
                syncRowCascade($container, 'ledger');
            } else {
                syncRowCascade($container, 'init');
            }
        });
        calcTotals();

        // Select2 for quick add modal parent dropdown
        $('#quickAddCoaModal').on('shown.bs.modal', function() {
            initQuickParentSelect2();
            updateQuickParentOptions();
        });

        $('input[name="level_add"]').on('change', function() {
            updateQuickParentOptions();
        });
    });
<?php $page_script = ob_get_clean(); ?>
