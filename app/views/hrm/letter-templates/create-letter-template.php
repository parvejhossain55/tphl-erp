<style>
@media (max-width: 767.98px) {
    .submit__btn {
        display: flex !important;
        gap: 10px !important;
        width: 100% !important;
        justify-content: center !important;
    }
    .submit__btn button, .submit__btn a {
        flex: 1 !important;
        margin: 0 !important;
        text-align: center !important;
        display: inline-flex !important;
        justify-content: center !important;
        align-items: center !important;
        height: 42px !important;
        font-size: 14px !important;
    }
}
.salary-row-item {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 10px 14px;
    margin-bottom: 8px;
    transition: all 0.2s;
}
.salary-row-item:hover { border-color: #6c757d; }
.btn-add-row {
    border: 2px dashed #0d6efd;
    color: #0d6efd;
    background: transparent;
    border-radius: 8px;
    padding: 8px 18px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
    width: 100%;
}
.btn-add-row:hover { background: #e8f0ff; }
.gross-box {
    background: linear-gradient(135deg, #1a3c6b, #2563a8);
    color: #fff;
    border-radius: 10px;
    padding: 14px 20px;
    font-size: 16px;
    font-weight: 700;
    letter-spacing: 0.5px;
}
.net-box {
    background: linear-gradient(135deg, #155724, #198754);
    color: #fff;
    border-radius: 10px;
    padding: 14px 20px;
    font-size: 16px;
    font-weight: 700;
}
.section-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #6c757d;
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 6px;
    margin-bottom: 12px;
}
</style>

<div class="row">
    <div class="card__wrapper">
        <div class="col-xxl-12 col-xl-12 col-lg-12">

            <div class="card__title-wrap mb-20 d-flex align-items-center justify-content-between">
                <h5 class="card__heading-title">Generate Employee Letter</h5>
                <a href="<?= $GLOBALS['base_url'] ?>/generate-letter" class="btn btn-secondary btn-sm">
                    <i class="fa fa-arrow-left me-1"></i> Back to List
                </a>
            </div>

            <form action="<?= $GLOBALS['base_url'] ?>/store-generate-letter" method="POST" id="letterForm">

                <!-- ═══ Section: Employee Info ═══════════════════════════════ -->
                <div class="card__wrapper mb-20">
                    <div class="card__title-wrap mb-15">
                        <h6 class="card__heading-title">Employee Information</h6>
                    </div>
                    <div class="row g-20 gy-20">

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="employee_id">Select Employee <span>*</span></label>
                                </div>
                                <div class="form__input">
                                    <select name="employee_id" id="employee_id" class="form-control select2" onchange="onEmployeeChange()">
                                        <option value="">Select Employee</option>
                                        <?php if (!empty($employees)): ?>
                                            <?php foreach ($employees as $emp): ?>
                                                <option
                                                    value="<?= $emp['id'] ?>"
                                                    data-designation="<?= $emp['designation_id'] ?>"
                                                    data-department="<?= $emp['department_id'] ?>">
                                                    <?= htmlspecialchars($emp['full_name']) ?> (<?= htmlspecialchars($emp['employee_id']) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="letter_type">Letter Type <span>*</span></label>
                                </div>
                                <div class="form__input">
                                    <select class="form-control" name="letter_type" id="letter_type" onchange="onLetterTypeChange()">
                                        <option value="">— Select Letter Type —</option>
                                        <option value="appointment">Appointment Letter</option>
                                        <option value="joining">Joining Letter</option>
                                        <option value="promotion">Promotion Letter</option>
                                        <option value="increment">Salary Increment Letter</option>
                                        <option value="experience">Experience Certificate</option>
                                        <option value="noc">No Objection Certificate (NOC)</option>
                                        <option value="salary_certificate">💼 Salary Certificate</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="designation_id">Designation <span>*</span></label>
                                </div>
                                <div class="form__input">
                                    <select class="form-control select2" name="designation_id" id="designation_id" required>
                                        <option value="">— Select Designation —</option>
                                        <?php foreach ($designations as $desig): ?>
                                            <option value="<?= $desig['id'] ?>"><?= htmlspecialchars($desig['designation_name'] . ' (' . ($desig['department_name'] ?? 'N/A') . ')') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="department_id">Department <span>*</span></label>
                                </div>
                                <div class="form__input">
                                    <select class="form-control select2" name="department_id" id="department_id" required>
                                        <option value="">— Select Department —</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?= $dept['id'] ?>"><?= $dept['name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ═══ Section: Standard Letter Details (hidden for salary_certificate) ═══ -->
                <div id="section_standard_details" class="card__wrapper mb-20">
                    <div class="card__title-wrap mb-15">
                        <h6 class="card__heading-title">Letter Details</h6>
                    </div>
                    <div class="row g-20 gy-20">

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title"><label for="letter_date">Letter Date <span>*</span></label></div>
                                <div class="form__input"><input class="form-control" name="letter_date" id="letter_date" type="date"></div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title"><label for="ref_number">Reference Number <span class="text-muted">Optional</span></label></div>
                                <div class="form__input"><input class="form-control" name="ref_number" id="ref_number" type="text" placeholder="e.g. HR/2024/001"></div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title"><label for="joining_date">Joining Date</label></div>
                                <div class="form__input"><input class="form-control" name="joining_date" id="joining_date" type="date"></div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title"><label for="effective_date">Effective Date <span class="text-muted">Optional</span></label></div>
                                <div class="form__input"><input class="form-control" name="effective_date" id="effective_date" type="date"></div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <label>Subject *</label>
                            <input type="text" name="subject" id="subject" class="form-control" required>
                        </div>

                        <div class="col-lg-6">
                            <label>Select Template <span class="text-muted">Optional</span></label>
                            <select name="template_id" id="template_id" class="form-control" onchange="loadTemplate()">
                                <option value="">— No Template —</option>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label>Letter Body *</label>
                            <div class="from__input-box">
                                <textarea class="form-control" id="tinymce_simple_textarea" name="letter_body" rows="4" placeholder="Enter letter content..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══ Section: Salary & Promotion Info (hidden for salary_certificate) ═══ -->
                <div id="section_salary_promo" class="card__wrapper mb-20">
                    <div class="card__title-wrap mb-15">
                        <h6 class="card__heading-title">Salary Information <small class="text-muted">(For Increment / Promotion Letter)</small></h6>
                    </div>
                    <div class="row g-20 gy-20">
                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title"><label for="current_salary">Current Salary <span class="text-muted">Optional</span></label></div>
                                <div class="form__input"><input class="form-control" name="current_salary" id="current_salary" type="number" placeholder="e.g. 50000" value=""></div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title"><label for="revised_salary">Revised Salary <span class="text-muted">Optional</span></label></div>
                                <div class="form__input"><input class="form-control" name="revised_salary" id="revised_salary" type="number" placeholder="e.g. 60000" value=""></div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title"><label for="new_designation">New Position <span class="text-muted">Optional</span></label></div>
                                <div class="form__input"><input class="form-control" name="new_designation" id="new_designation" type="text" placeholder="e.g. Senior Engineer"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══ Section: Salary Certificate (visible only for salary_certificate) ═══ -->
                <div id="section_salary_cert" class="card__wrapper mb-20" style="display:none;">
                    <div class="card__title-wrap mb-15">
                        <h6 class="card__heading-title">💼 Salary Certificate Details</h6>
                    </div>
                    <div class="row g-20 gy-20 mb-20">

                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title"><label>Letter Date <span>*</span></label></div>
                                <div class="form__input"><input class="form-control" name="letter_date" id="sc_letter_date" type="date"></div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title"><label>Certificate Month <span>*</span></label></div>
                                <div class="form__input">
                                    <input class="form-control" name="certificate_month" id="certificate_month" type="month"
                                           placeholder="e.g. 2026-06">
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title"><label>Reference Number <span class="text-muted">Optional</span></label></div>
                                <div class="form__input"><input class="form-control" name="ref_number" id="sc_ref_number" type="text" placeholder="TPHL/HR/Salary Certificate/June-2026"></div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title"><label>Subject <span>*</span></label></div>
                                <div class="form__input"><input class="form-control" name="subject" id="sc_subject" type="text" value="Salary Certificate"></div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title"><label>Purpose <span>*</span></label></div>
                                <div class="form__input">
                                    <select class="form-control" name="purpose" id="purpose" onchange="toggleOtherPurpose()">
                                        <option value="">— Select Purpose —</option>
                                        <option value="Bank Loan">Bank Loan</option>
                                        <option value="Visa Application">Visa Application</option>
                                        <option value="Tenancy / House Rent">Tenancy / House Rent</option>
                                        <option value="Personal Use">Personal Use</option>
                                        <option value="Other">Other (specify)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6" id="other_purpose_box" style="display:none;">
                            <div class="from__input-box">
                                <div class="form__input-title"><label>Specify Purpose</label></div>
                                <div class="form__input">
                                    <input class="form-control" type="text" id="purpose_other_text" placeholder="Enter purpose...">
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- ── Earnings Table ─────────────────────────────────── -->
                    <div class="mb-20">
                        <div class="section-label">💰 Earnings</div>
                        <div id="earnings_container"></div>
                        <button type="button" class="btn-add-row mt-2" onclick="addSalaryRow('earning')">
                            <i class="fa fa-plus-circle me-1"></i> Add Earning Row
                        </button>
                    </div>

                    <!-- ── Deductions Table ───────────────────────────────── -->
                    <div class="mb-20">
                        <div class="section-label">➖ Deductions <small class="text-muted fw-normal">(Optional)</small></div>
                        <div id="deductions_container"></div>
                        <button type="button" class="btn-add-row mt-2" style="border-color:#dc3545; color:#dc3545;" onclick="addSalaryRow('deduction')">
                            <i class="fa fa-minus-circle me-1"></i> Add Deduction Row
                        </button>
                    </div>

                    <!-- ── Totals ─────────────────────────────────────────── -->
                    <div class="row g-10 mt-10">
                        <div class="col-lg-6">
                            <div class="gross-box d-flex justify-content-between align-items-center">
                                <span>Gross Salary (Total Earnings)</span>
                                <span id="gross_display">BDT 0.00</span>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="net-box d-flex justify-content-between align-items-center">
                                <span>Net Salary (After Deductions)</span>
                                <span id="net_display">BDT 0.00</span>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="gross_salary" id="gross_salary_hidden">
                    <input type="hidden" name="net_salary"   id="net_salary_hidden">

                </div>

                <!-- ═══ Section: Signatory ═══════════════════════════════════ -->
                <div class="card__wrapper mb-20">
                    <div class="card__title-wrap mb-15">
                        <h6 class="card__heading-title">Signatory Details</h6>
                    </div>
                    <div class="row g-20 gy-20">

                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title"><label for="signatory_name">Signed By <span>*</span></label></div>
                                <div class="form__input"><input class="form-control" name="signatory_name" id="signatory_name" type="text" placeholder="Full Name" required></div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title"><label for="signatory_title">Designation / Title <span>*</span></label></div>
                                <div class="form__input"><input class="form-control" name="signatory_title" id="signatory_title" type="text" placeholder="e.g. Chief Executive Officer" required></div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title"><label for="cc_email">CC Email <span class="text-muted">Optional</span></label></div>
                                <div class="form__input"><input class="form-control" name="cc_email" id="cc_email" type="email" placeholder="e.g. hr@company.com" value=""></div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title"><label for="send_to_email">Send to Employee Email <span class="text-muted">Optional</span></label></div>
                                <div class="form__input">
                                    <select class="form-control" name="send_to_email" id="send_to_email">
                                        <option value="0">No — Don't send email</option>
                                        <option value="1">Yes — Send via email</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title"><label for="letter_status">Letter Status <span>*</span></label></div>
                                <div class="form__input">
                                    <select class="form-control" name="letter_status" id="letter_status" required>
                                        <option value="">— Select Status —</option>
                                        <option value="draft">Draft</option>
                                        <option value="issued">Issued</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="submit__btn text-center">
                    <button class="btn btn-secondary me-2" type="reset" onclick="resetSalaryCert()">Reset</button>
                    <button class="btn btn-primary" type="submit" id="submitBtn">Generate Letter</button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
// ─── Employee Change ────────────────────────────────────────────────────────
function onEmployeeChange() {
    var sel = document.getElementById('employee_id');
    var opt = sel.options[sel.selectedIndex];

    if (!opt || !opt.value) {
        if (typeof $ !== 'undefined') {
            $('#designation_id').val('').trigger('change');
            $('#department_id').val('').trigger('change');
        }
        return;
    }

    // Auto-fill designation & department
    var desigId = opt.dataset.designation || '';
    var deptId  = opt.dataset.department  || '';
    if (typeof $ !== 'undefined') {
        $('#designation_id').val(desigId).trigger('change');
        $('#department_id').val(deptId).trigger('change');
    }

    // If salary_certificate, also fetch salary info
    var letterType = document.getElementById('letter_type').value;
    if (letterType === 'salary_certificate') {
        fetchEmployeeSalary(opt.value);
    }
}

// ─── Fetch salary from AJAX ─────────────────────────────────────────────────
function fetchEmployeeSalary(empId) {
    if (!empId) return;
    fetch('<?= $GLOBALS['base_url'] ?>/get-employee-salary?employee_id=' + empId)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Auto-fill basic salary as first earning row if earnings empty
                var container = document.getElementById('earnings_container');
                if (container.children.length === 0) {
                    addSalaryRowWithData('earning', 'Basic Salary', data.basic_salary || 0);
                }
            }
        });
}

// ─── Letter Type Change ─────────────────────────────────────────────────────
function onLetterTypeChange() {
    var type = document.getElementById('letter_type').value;
    var isSalaryCert = (type === 'salary_certificate');

    var stdSection    = document.getElementById('section_standard_details');
    var salaryPromo   = document.getElementById('section_salary_promo');
    var certSection   = document.getElementById('section_salary_cert');

    stdSection.style.display  = isSalaryCert ? 'none' : '';
    salaryPromo.style.display = isSalaryCert ? 'none' : '';
    certSection.style.display = isSalaryCert ? ''     : 'none';

    // Disable required fields in hidden section so browser validation doesn't block
    var stdRequiredFields = stdSection.querySelectorAll('[required], [name="subject"], [name="letter_date"], [name="letter_body"]');
    stdRequiredFields.forEach(function(el) {
        el.disabled = isSalaryCert;
    });

    // load templates for other types
    if (!isSalaryCert) {
        loadTemplatesForType();
    }

    // If salary cert and employee already selected → auto-fetch salary
    if (isSalaryCert) {
        var empSel = document.getElementById('employee_id');
        if (empSel && empSel.value) {
            fetchEmployeeSalary(empSel.value);
        }
    }
}

// ─── Add salary row (blank) ─────────────────────────────────────────────────
var rowCounter = 0;
function addSalaryRow(itemType) {
    addSalaryRowWithData(itemType, '', 0);
}

function addSalaryRowWithData(itemType, label, amount) {
    rowCounter++;
    var containerId = itemType === 'earning' ? 'earnings_container' : 'deductions_container';
    var container   = document.getElementById(containerId);

    var borderColor  = itemType === 'earning' ? '#0d6efd' : '#dc3545';
    var suggestions  = ['Basic Salary','House Rent Allowance','Medical Allowance','Conveyance','Mobile Allowance','Food Allowance','Others Allowance'];
    var suggestList  = suggestions.map(s => '<option value="'+s+'">').join('');

    var html = '<div class="salary-row-item d-flex align-items-center gap-2" id="row_'+rowCounter+'" style="border-left:3px solid '+borderColor+';">';
    html += '<input type="hidden" name="item_type_row[]" value="'+itemType+'">';
    html += '<datalist id="suggest_'+rowCounter+'">'+suggestList+'</datalist>';
    html += '<input type="text" name="item_label[]" class="form-control form-control-sm" placeholder="Label (e.g. House Rent)" list="suggest_'+rowCounter+'" value="'+htmlEscape(label)+'" style="flex:2;" oninput="recalcTotals()">';
    html += '<input type="number" name="item_amount[]" class="form-control form-control-sm text-end" placeholder="Amount" value="'+(amount||'')+'" style="flex:1; max-width:160px;" oninput="recalcTotals()" step="0.01" min="0">';
    html += '<button type="button" class="btn btn-sm btn-outline-danger px-2" onclick="removeSalaryRow(\'row_'+rowCounter+'\')" title="Remove"><i class="fa fa-times"></i></button>';
    html += '</div>';

    container.insertAdjacentHTML('beforeend', html);
    recalcTotals();
}

function removeSalaryRow(id) {
    var el = document.getElementById(id);
    if (el) { el.remove(); recalcTotals(); }
}

// ─── Recalculate totals ─────────────────────────────────────────────────────
function recalcTotals() {
    var gross     = 0;
    var deduction = 0;

    document.querySelectorAll('#earnings_container .salary-row-item').forEach(function(row) {
        var amt = parseFloat(row.querySelector('input[name="item_amount[]"]').value) || 0;
        gross += amt;
    });
    document.querySelectorAll('#deductions_container .salary-row-item').forEach(function(row) {
        var amt = parseFloat(row.querySelector('input[name="item_amount[]"]').value) || 0;
        deduction += amt;
    });

    var net = gross - deduction;
    document.getElementById('gross_display').textContent = 'BDT ' + formatNum(gross);
    document.getElementById('net_display').textContent   = 'BDT ' + formatNum(net);
    document.getElementById('gross_salary_hidden').value = gross.toFixed(2);
    document.getElementById('net_salary_hidden').value   = net.toFixed(2);
}

function formatNum(n) {
    return n.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
}
function htmlEscape(str) {
    return String(str).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ─── Purpose "Other" toggle ─────────────────────────────────────────────────
function toggleOtherPurpose() {
    var val = document.getElementById('purpose').value;
    document.getElementById('other_purpose_box').style.display = (val === 'Other') ? '' : 'none';
}

// ─── Before form submit: validation + sync ───────────────────────────────────
document.getElementById('letterForm').addEventListener('submit', function(e) {
    var type = document.getElementById('letter_type').value;
    if (!type) { e.preventDefault(); alert('Please select a letter type.'); return; }

    if (type === 'salary_certificate') {
        var scDate    = document.getElementById('sc_letter_date').value;
        var certMonth = document.getElementById('certificate_month').value;
        var purposeSel = document.getElementById('purpose');

        // Validate required cert fields
        if (!scDate)           { e.preventDefault(); alert('Please select a letter date.');        return; }
        if (!certMonth)        { e.preventDefault(); alert('Please select the certificate month.'); return; }
        if (!purposeSel.value) { e.preventDefault(); alert('Please select a purpose.');            return; }

        // Handle "Other" purpose
        if (purposeSel.value === 'Other') {
            var otherText = document.getElementById('purpose_other_text').value.trim();
            if (!otherText) { e.preventDefault(); alert('Please specify the purpose.'); return; }
            purposeSel.value = otherText;
        }
        // Re-enable disabled fields so they are included in POST data
        var stdSection = document.getElementById('section_standard_details');
        stdSection.querySelectorAll('[disabled]').forEach(function(el) { el.disabled = false; });

        // Sync salary-cert inputs → standard POST fields
        document.getElementById('letter_date').value = scDate;
        document.getElementById('ref_number').value  = document.getElementById('sc_ref_number').value;
        document.getElementById('subject').value      = document.getElementById('sc_subject').value;

        recalcTotals();
    }
});

function resetSalaryCert() {
    document.getElementById('earnings_container').innerHTML   = '';
    document.getElementById('deductions_container').innerHTML = '';
    recalcTotals();
}

// ─── Template loading (for non-salary-cert types) ───────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    var lt = document.getElementById('letter_type');
    if (lt) lt.addEventListener('change', onLetterTypeChange);
});

function loadTemplatesForType() {
    var letterType   = document.getElementById('letter_type').value;
    var templateSelect = document.getElementById('template_id');
    if (!letterType) {
        templateSelect.innerHTML = '<option value="">— No Template —</option>';
        return;
    }
    fetch('<?= $GLOBALS['base_url'] ?>/get-templates-for-type?type=' + encodeURIComponent(letterType))
        .then(r => r.json())
        .then(data => {
            if (data.success && data.templates) {
                var html = '<option value="">— No Template —</option>';
                data.templates.forEach(t => { html += '<option value="'+t.id+'">'+t.name+'</option>'; });
                templateSelect.innerHTML = html;
            } else {
                templateSelect.innerHTML = '<option value="">— No Template —</option>';
            }
        });
}

function loadTemplate() {
    var tid = document.getElementById('template_id').value;
    if (!tid) return;
    fetch('<?= $GLOBALS['base_url'] ?>/get-template?id=' + encodeURIComponent(tid))
        .then(r => r.json())
        .then(data => {
            if (data.success && data.template) {
                document.getElementById('subject').value       = data.template.subject || '';
                document.getElementById('letter_body').value   = data.template.body    || '';
            }
        });
}
</script>