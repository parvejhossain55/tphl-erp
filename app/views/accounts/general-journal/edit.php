<?php 
// Layout handled by Controller via master.php
?>

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
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="addRow()">
                        <i class="fa-light fa-plus"></i> Add Row
                    </button>
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
                            <?php foreach ($details as $index => $detail): ?>
                            <tr>
                                <td>
                                    <div class="row gx-2">
                                        <div class="col-12 mb-2">
                                            <?php 
                                            // Find parent ID of the selected account to set main category
                                            $parentId = '';
                                            foreach($accounts as $acc) {
                                                if($acc['id'] == $detail['account_id']) {
                                                    $parentId = $acc['parent_id'];
                                                    break;
                                                }
                                            }
                                            ?>
                                            <select class="form-select main-category-select" onchange="filterSubCategories(this)">
                                                <option value="">-- Main Category Filter --</option>
                                                <?php foreach ($accounts as $acc): ?>
                                                    <?php if(empty($acc['parent_id'])): ?>
                                                        <option value="<?= $acc['id'] ?>" <?= ($acc['id'] == $parentId) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($acc['account_code'] . ' - ' . $acc['account_name']) ?> (<?= $acc['account_type'] ?>)
                                                        </option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <select class="form-select sub-category-select" name="account_id[]" required>
                                                <option value="">-- Select Sub Category / Account --</option>
                                                <?php foreach ($accounts as $acc): ?>
                                                    <?php if(!empty($acc['parent_id'])): ?>
                                                        <option value="<?= $acc['id'] ?>" <?= ($acc['id'] == $detail['account_id']) ? 'selected' : '' ?>>
                                                            [<?= htmlspecialchars($acc['account_code']) ?>] <?= htmlspecialchars($acc['account_name']) ?> (<?= htmlspecialchars($acc['account_type']) ?>)
                                                        </option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
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
