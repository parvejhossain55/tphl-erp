<style>
    @media (max-width: 767.98px) {
        /* Side-by-side filter/action buttons on mobile */
        .submit__btn {
            display: flex !important;
            gap: 10px !important;
            width: 100% !important;
            justify-content: center !important;
        }

        .submit__btn button,
        .submit__btn a {
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

    .salary-row-item:hover {
        border-color: #6c757d;
    }

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

    .btn-add-row:hover {
        background: #e8f0ff;
    }

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
                <h5 class="card__heading-title">Edit Generated Letter</h5>
                <a href="<?= $GLOBALS['base_url'] ?>/generate-letter" class="btn btn-secondary btn-sm">
                    <i class="fa fa-arrow-left me-1"></i> Back to List
                </a>
            </div>

            <form action="<?= $GLOBALS['base_url'] ?>/update-letter/<?= $letter['id'] ?>" method="POST" enctype="multipart/form-data" id="editLetterForm">

                <!-- Section: Employee Info -->
                <div class="card__wrapper mb-20">
                    <div class="card__title-wrap mb-15">
                        <h6 class="card__heading-title">Employee Information</h6>
                    </div>

                    <div class="row g-20 gy-20">

                        <!-- Employee Select -->
                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="employee_id">Select Employee <span>*</span></label>
                                </div>
                                <div class="form__input">
                                    <select name="employee_id" id="employee_id" class="form-control select2" onchange="autoSelectDeptDesig()">
                                        <option value="">Select Employee</option>
                                        <?php if (!empty($employees)): ?>
                                            <?php foreach ($employees as $emp): ?>
                                                <option
                                                    value="<?= $emp['id'] ?>"
                                                    data-name="<?= htmlspecialchars($emp['full_name']) ?>"
                                                    data-code="<?= htmlspecialchars($emp['employee_id']) ?>"
                                                    data-designation="<?= $emp['designation_id'] ?>"
                                                    data-designation-id="<?= $emp['designation_id'] ?>"
                                                    data-designation-name="<?= htmlspecialchars($emp['designation_name'] ?? '') ?>"
                                                    data-department="<?= $emp['department_id'] ?>"
                                                    data-department-id="<?= $emp['department_id'] ?>"
                                                    data-department-name="<?= htmlspecialchars($emp['department_name'] ?? '') ?>"
                                                    data-joining="<?= $emp['joining_date'] ?? '' ?>"
                                                    data-resign="<?= $emp['resign_date'] ?? '' ?>"
                                                    data-gender="<?= $emp['gender'] ?? '' ?>"
                                                    data-email="<?= htmlspecialchars($emp['email'] ?? '') ?>"
                                                    <?= $emp['id'] == $letter['employee_id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($emp['full_name']) ?> (<?= htmlspecialchars($emp['employee_id']) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Letter Type -->
                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="letter_type">Letter Type <span>*</span></label>
                                </div>
                                <div class="form__input">
                                    <select class="form-control" name="letter_type" id="letter_type" onchange="onLetterTypeChange()">
                                        <option value="">— Select Letter Type —</option>
                                        <option value="appointment" <?= $letter['letter_type'] == 'appointment' ? 'selected' : '' ?>>Appointment Letter</option>
                                        <option value="joining" <?= $letter['letter_type'] == 'joining' ? 'selected' : '' ?>>Joining Letter</option>
                                        <option value="promotion" <?= $letter['letter_type'] == 'promotion' ? 'selected' : '' ?>>Promotion Letter</option>
                                        <option value="increment" <?= $letter['letter_type'] == 'increment' ? 'selected' : '' ?>>Salary Increment Letter</option>
                                        <option value="experience" <?= $letter['letter_type'] == 'experience' ? 'selected' : '' ?>>Experience Certificate</option>
                                        <option value="noc" <?= $letter['letter_type'] == 'noc' ? 'selected' : '' ?>>No Objection Certificate (NOC)</option>
                                        <option value="salary_certificate" <?= $letter['letter_type'] == 'salary_certificate' ? 'selected' : '' ?>>💼 Salary Certificate</option>
                                        <option value="show_cause" <?= $letter['letter_type'] == 'show_cause' ? 'selected' : '' ?>>Show Cause Letter</option>
                                        <option value="warning" <?= $letter['letter_type'] == 'warning' ? 'selected' : '' ?>>Warning Letter</option>
                                        <option value="caution" <?= $letter['letter_type'] == 'caution' ? 'selected' : '' ?>>Caution Letter</option>
                                        <option value="improvement" <?= $letter['letter_type'] == 'improvement' ? 'selected' : '' ?>>Improvement Letter</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Designation Select -->
                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="designation_id">Designation <span>*</span></label>
                                </div>
                                <div class="form__input">
                                    <select class="form-control select2" name="designation_id" id="designation_id" required>
                                        <option value="">— Select Designation —</option>
                                        <?php if (!empty($designations)): ?>
                                            <?php foreach ($designations as $desig): ?>
                                                <option value="<?= $desig['id'] ?>" <?= $desig['id'] == $letter['designation_id'] ? 'selected' : '' ?>><?= htmlspecialchars($desig['designation_name'] . ' (' . ($desig['department_name'] ?? 'N/A') . ')') ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Department Select -->
                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="department_id">Department <span>*</span></label>
                                </div>
                                <div class="form__input">
                                    <select class="form-control select2" name="department_id" id="department_id" required>
                                        <option value="">— Select Department —</option>
                                        <?php if (!empty($departments)): ?>
                                            <?php foreach ($departments as $dept): ?>
                                                <option value="<?= $dept['id'] ?>" <?= $dept['id'] == $letter['department_id'] ? 'selected' : '' ?>><?= $dept['name'] ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

                <!-- Section: Standard Letter Details -->
                <div id="section_standard_details" class="card__wrapper mb-20">
                    <div class="card__title-wrap mb-15">
                        <h6 class="card__heading-title">Letter Details</h6>
                    </div>

                    <div class="row g-20 gy-20">

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="letter_date">Letter Date <span>*</span></label>
                                </div>
                                <div class="form__input">
                                    <input class="form-control" name="letter_date" id="letter_date" type="date" value="<?= htmlspecialchars((string)($letter['letter_date'] ?? '')) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="ref_number">Reference Number <span class="text-muted">Optional</span></label>
                                </div>
                                <div class="form__input">
                                    <input class="form-control" name="ref_number" id="ref_number" type="text" placeholder="e.g. HR/2024/001" value="<?= htmlspecialchars((string)($letter['ref_number'] ?? '')) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="joining_date">Joining Date</label>
                                </div>
                                <div class="form__input">
                                    <input class="form-control" name="joining_date" id="joining_date" type="date" value="<?= htmlspecialchars((string)($letter['joining_date'] ?? '')) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="effective_date">Effective Date <span class="text-muted">Optional</span></label>
                                </div>
                                <div class="form__input">
                                    <input class="form-control" name="effective_date" id="effective_date" type="date" value="<?= htmlspecialchars((string)($letter['effective_date'] ?? '')) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <label>Subject *</label>
                            <input type="text" name="subject" id="subject" class="form-control" value="<?= htmlspecialchars((string)($letter['subject'] ?? '')) ?>">
                        </div>

                        <div class="col-lg-6">
                            <label>Select Template <span class="text-muted">Optional</span></label>
                            <select name="template_id" id="template_id" class="form-control" onchange="loadTemplate()">
                                <option value="">— No Template —</option>
                                <?php if (!empty($templates)): ?>
                                    <?php foreach ($templates as $tmpl): ?>
                                        <?php if ($tmpl['type'] == $letter['letter_type']): ?>
                                            <option value="<?= $tmpl['id'] ?>" <?= $tmpl['id'] == $letter['template_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($tmpl['name']) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label>Letter Body *</label>
                            <div class="from__input-box">
                                <textarea class="form-control" id="tinymce_simple_textarea" name="letter_body" rows="4" placeholder="Enter letter content..."><?= htmlspecialchars((string)($letter['body'] ?? '')) ?></textarea>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Section: Salary & Promotion Info -->
                <div id="section_salary_promo" class="card__wrapper mb-20">
                    <div class="card__title-wrap mb-15">
                        <h6 class="card__heading-title">Salary Information <small class="text-muted">(For Increment / Promotion Letter)</small></h6>
                    </div>

                    <div class="row g-20 gy-20">

                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="current_salary">Current Salary <span class="text-muted">Optional</span></label>
                                </div>
                                <div class="form__input">
                                    <input class="form-control" name="current_salary" id="current_salary" type="number" placeholder="e.g. 50000" value="<?= htmlspecialchars((string)($letter['current_salary'] ?? '')) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="revised_salary">Revised Salary <span class="text-muted">Optional</span></label>
                                </div>
                                <div class="form__input">
                                    <input class="form-control" name="revised_salary" id="revised_salary" type="number" placeholder="e.g. 60000" value="<?= htmlspecialchars((string)($letter['revised_salary'] ?? '')) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="new_designation">New Position <span class="text-muted">Optional</span></label>
                                </div>
                                <div class="form__input">
                                    <input class="form-control" name="new_designation" id="new_designation" type="text" placeholder="e.g. Senior Engineer" value="<?= htmlspecialchars((string)($letter['new_designation'] ?? '')) ?>">
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Section: Salary Certificate (visible only for salary_certificate) -->
                <?php
                $isSalaryCert = ($letter['letter_type'] === 'salary_certificate');
                $knownPurposes = ['Bank Loan', 'Visa Application', 'Tenancy / House Rent', 'Personal Use'];
                $currPurpose = $letter['purpose'] ?? '';
                $isOtherPurpose = (!empty($currPurpose) && !in_array($currPurpose, $knownPurposes));

                $scBodyData = json_decode($letter['body'] ?? '', true);
                $scOpening = $scBodyData['opening'] ?? '';
                $scClosing = $scBodyData['closing'] ?? '';

                if (empty($scOpening)) {
                    $rawName = trim($letter['full_name'] ?? '');
                    $empCode = $letter['employee_code'] ?? ($letter['employee_id'] ?? '');
                    $desigName = $letter['designation_name'] ?? 'N/A';
                    $gender = strtolower($letter['gender'] ?? '');
                    $sheHe = ($gender === 'female') ? 'she' : 'he';
                    $herHis = ($gender === 'female') ? 'her' : 'his';
                    $herHim = ($gender === 'female') ? 'her' : 'him';
                    $empType = !empty($letter['employee_type']) ? ucfirst($letter['employee_type']) : 'Permanent';
                    $companyName = function_exists('getSetting') ? getSetting('website_title', 'The Premium Homes Limited') : 'The Premium Homes Limited';

                    $startDate = !empty($letter['joining_date']) ? date('d F Y', strtotime($letter['joining_date'])) : 'N/A';
                    $endDate = !empty($letter['effective_date']) ? date('d F Y', strtotime($letter['effective_date'])) : '';
                    $periodText = !empty($endDate) ? ('from ' . $startDate . ' to ' . $endDate) : ('from ' . $startDate . ' to present');

                    $scOpening = "This is to certify that **" . $rawName . "** (Employee ID: " . $empCode . ") was a " . $empType . " employee of **" . $companyName . "** and " . $sheHe . " was employed as **" . $desigName . "** " . $periodText . ".\n\nAccording to our records, " . $herHis . " monthly salary breakdown was as follows:";
                }

                if (empty($scClosing)) {
                    $gender = strtolower($letter['gender'] ?? '');
                    $herHis = ($gender === 'female') ? 'her' : 'his';
                    $herHim = ($gender === 'female') ? 'her' : 'him';
                    $purposeText = !empty($letter['purpose']) ? strtolower($letter['purpose']) : 'final settlement';
                    $scClosing = "This certificate is issued upon " . $herHis . " request for " . $purposeText . " without any liability on the part of this company.\n\nWe wish " . $herHim . " all the very best.";
                }
                ?>
                <div id="section_salary_cert" class="card__wrapper mb-20" style="<?= $isSalaryCert ? '' : 'display:none;' ?>">
                    <div class="card__title-wrap mb-15">
                        <h6 class="card__heading-title">💼 Salary Certificate Details</h6>
                    </div>
                    <div class="row g-20 gy-20 mb-20">

                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title"><label>Letter Date <span>*</span></label></div>
                                <div class="form__input"><input class="form-control" name="sc_letter_date"
                                        id="sc_letter_date" type="date" value="<?= htmlspecialchars((string)($letter['letter_date'] ?? '')) ?>"></div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title"><label>Certificate Month <span>*</span></label></div>
                                <div class="form__input">
                                    <input class="form-control" name="certificate_month" id="certificate_month"
                                        type="month" placeholder="e.g. 2026-06" value="<?= htmlspecialchars((string)($letter['certificate_month'] ?? '')) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title"><label>Reference Number <span
                                            class="text-muted">Optional</span></label></div>
                                <div class="form__input"><input class="form-control" name="sc_ref_number"
                                        id="sc_ref_number" type="text"
                                        placeholder="TPHL/HR/Salary Certificate/June-2026" value="<?= htmlspecialchars((string)($letter['ref_number'] ?? '')) ?>"></div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title"><label>Subject <span>*</span></label></div>
                                <div class="form__input"><input class="form-control" name="sc_subject" id="sc_subject"
                                        type="text" value="<?= htmlspecialchars((string)($letter['subject'] ?? 'Salary Certificate')) ?>"></div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title"><label>Purpose <span>*</span></label></div>
                                <div class="form__input">
                                    <select class="form-control" name="purpose" id="purpose"
                                        onchange="toggleOtherPurpose()">
                                        <option value="">— Select Purpose —</option>
                                        <option value="Final Settlement" <?= (empty($currPurpose) || $currPurpose === 'Final Settlement') ? 'selected' : '' ?>>Final Settlement</option>
                                        <option value="Bank Loan" <?= $currPurpose === 'Bank Loan' ? 'selected' : '' ?>>Bank Loan</option>
                                        <option value="Visa Application" <?= $currPurpose === 'Visa Application' ? 'selected' : '' ?>>Visa Application</option>
                                        <option value="Tenancy / House Rent" <?= $currPurpose === 'Tenancy / House Rent' ? 'selected' : '' ?>>Tenancy / House Rent</option>
                                        <option value="Personal Use" <?= $currPurpose === 'Personal Use' ? 'selected' : '' ?>>Personal Use</option>
                                        <option value="Other" <?= $isOtherPurpose ? 'selected' : '' ?>>Other (specify)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title"><label>Employment Joining Date</label></div>
                                <div class="form__input"><input class="form-control" name="sc_joining_date" id="sc_joining_date" type="date" value="<?= htmlspecialchars((string)($letter['joining_date'] ?? $letter['emp_joining_date'] ?? '')) ?>"></div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title"><label>Separation / End Date <span class="text-muted">(Leave blank if currently employed)</span></label></div>
                                <div class="form__input"><input class="form-control" name="sc_effective_date" id="sc_effective_date" type="date" value="<?= htmlspecialchars((string)($letter['effective_date'] ?? $letter['emp_resign_date'] ?? '')) ?>"></div>
                            </div>
                        </div>

                        <div class="col-lg-12" id="other_purpose_box" style="<?= $isOtherPurpose ? '' : 'display:none;' ?>">
                            <div class="from__input-box">
                                <div class="form__input-title"><label>Specify Purpose</label></div>
                                <div class="form__input">
                                    <input class="form-control" type="text" id="purpose_other_text"
                                        placeholder="Enter purpose..." value="<?= $isOtherPurpose ? htmlspecialchars($currPurpose) : '' ?>">
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- ── Earnings Table ─────────────────────────────────── -->
                    <div class="mb-20">
                        <div class="section-label">💰 Earnings</div>
                        <div id="earnings_container">
                            <?php if (!empty($salaryItems['earning'])): ?>
                                <?php foreach ($salaryItems['earning'] as $idx => $item): ?>
                                    <div class="salary-row-item d-flex align-items-center gap-2" id="row_edit_earn_<?= $idx ?>" style="border-left:3px solid #0d6efd;">
                                        <input type="hidden" name="item_type_row[]" value="earning">
                                        <input type="text" name="item_label[]" class="form-control form-control-sm" placeholder="Label" value="<?= htmlspecialchars($item['label']) ?>" style="flex:2;" oninput="recalcTotals()">
                                        <input type="number" name="item_amount[]" class="form-control form-control-sm text-end" placeholder="Amount" value="<?= (float)$item['amount'] ?>" style="flex:1; max-width:160px;" oninput="recalcTotals()" step="0.01" min="0">
                                        <button type="button" class="btn btn-sm btn-outline-danger px-2" onclick="removeSalaryRow('row_edit_earn_<?= $idx ?>')" title="Remove"><i class="fa fa-times"></i></button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn-add-row mt-2" onclick="addSalaryRow('earning')">
                            <i class="fa fa-plus-circle me-1"></i> Add Earning Row
                        </button>
                    </div>

                    <!-- ── Deductions Table ───────────────────────────────── -->
                    <div class="mb-20">
                        <div class="section-label">➖ Deductions <small class="text-muted fw-normal">(Optional)</small>
                        </div>
                        <div id="deductions_container">
                            <?php if (!empty($salaryItems['deduction'])): ?>
                                <?php foreach ($salaryItems['deduction'] as $idx => $item): ?>
                                    <div class="salary-row-item d-flex align-items-center gap-2" id="row_edit_deduct_<?= $idx ?>" style="border-left:3px solid #dc3545;">
                                        <input type="hidden" name="item_type_row[]" value="deduction">
                                        <input type="text" name="item_label[]" class="form-control form-control-sm" placeholder="Label" value="<?= htmlspecialchars($item['label']) ?>" style="flex:2;" oninput="recalcTotals()">
                                        <input type="number" name="item_amount[]" class="form-control form-control-sm text-end" placeholder="Amount" value="<?= (float)$item['amount'] ?>" style="flex:1; max-width:160px;" oninput="recalcTotals()" step="0.01" min="0">
                                        <button type="button" class="btn btn-sm btn-outline-danger px-2" onclick="removeSalaryRow('row_edit_deduct_<?= $idx ?>')" title="Remove"><i class="fa fa-times"></i></button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn-add-row mt-2" style="border-color:#dc3545; color:#dc3545;"
                            onclick="addSalaryRow('deduction')">
                            <i class="fa fa-minus-circle me-1"></i> Add Deduction Row
                        </button>
                    </div>

                    <!-- ── Totals ─────────────────────────────────────────── -->
                    <div class="row g-10 mt-10">
                        <div class="col-lg-6">
                            <div class="gross-box d-flex justify-content-between align-items-center">
                                <span>Gross Salary (Total Earnings)</span>
                                <span id="gross_display">BDT <?= number_format((float)($letter['gross_salary'] ?? 0), 2) ?></span>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="net-box d-flex justify-content-between align-items-center">
                                <span>Net Salary (After Deductions)</span>
                                <span id="net_display">BDT <?= number_format((float)($letter['net_salary'] ?? 0), 2) ?></span>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="gross_salary" id="gross_salary_hidden" value="<?= (float)($letter['gross_salary'] ?? 0) ?>">
                    <input type="hidden" name="net_salary" id="net_salary_hidden" value="<?= (float)($letter['net_salary'] ?? 0) ?>">

                    <!-- ── Editable Certificate Body Paragraphs ── -->
                    <div class="mt-20 pt-20 border-top">
                        <div class="d-flex justify-content-between align-items-center mb-10">
                            <div class="section-label mb-0">📝 Certificate Body Paragraphs <small class="text-muted fw-normal">(Editable)</small></div>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetSalaryCertParagraphs(true)">
                                <i class="fa-solid fa-rotate-left me-1"></i> Reset to Default Text
                            </button>
                        </div>
                        <p class="text-muted small mb-15">
                            You can customize or edit the certificate opening and closing paragraphs directly below:
                        </p>

                        <div class="row g-15">
                            <div class="col-12">
                                <label class="form-label fw-bold text-dark small mb-1">Opening Paragraph (Above Salary Table)</label>
                                <textarea class="form-control" name="sc_opening_paragraph" id="sc_opening_paragraph" rows="4" placeholder="Opening paragraph..."><?= htmlspecialchars($scOpening) ?></textarea>
                                <div class="form-text small text-muted">This text appears before the salary breakdown table. Supports Markdown bold <code>**text**</code>.</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold text-dark small mb-1">Closing Paragraph (Below Salary Table)</label>
                                <textarea class="form-control" name="sc_closing_paragraph" id="sc_closing_paragraph" rows="3" placeholder="Closing paragraph..."><?= htmlspecialchars($scClosing) ?></textarea>
                                <div class="form-text small text-muted">This text appears after the salary table and before the signature. Supports Markdown bold <code>**text**</code>.</div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Section: Signatory -->
                <div class="card__wrapper mb-20">
                    <div class="card__title-wrap mb-15">
                        <h6 class="card__heading-title">Signatory Details</h6>
                    </div>

                    <div class="row g-20 gy-20">

                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="signatory_name">Signed By <span>*</span></label>
                                </div>
                                <div class="form__input">
                                    <input class="form-control" name="signatory_name" id="signatory_name" type="text" placeholder="Full Name" value="<?= htmlspecialchars((string)($letter['signatory_name'] ?? '')) ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="signatory_title">Designation / Title <span>*</span></label>
                                </div>
                                <div class="form__input">
                                    <input class="form-control" name="signatory_title" id="signatory_title" type="text" placeholder="e.g. Chief Executive Officer" value="<?= htmlspecialchars((string)($letter['signatory_title'] ?? '')) ?>" required>
                                </div>
                            </div>
                        </div>

                        <?php
                        $baseDir = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4);
                        $hasCustomSig = !empty($letter['custom_signature']) && file_exists($baseDir . '/' . ltrim($letter['custom_signature'], '/\\'));
                        $hasOfficialSig = file_exists($baseDir . '/public/assets/images/hrm/hr_signature.png');
                        $isIncluded = (!isset($letter['include_signature']) || $letter['include_signature'] == 1);
                        ?>
                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title d-flex justify-content-between align-items-center">
                                    <label for="signature_choice">Digital Seal & Signature</label>
                                    <a href="javascript:void(0)" class="text-primary small fw-semibold text-decoration-none" data-bs-toggle="modal" data-bs-target="#officialSignatureModal">
                                        <i class="fa-solid fa-gear me-1"></i> Manage
                                    </a>
                                </div>
                                <div class="form__input">
                                    <select class="form-control" name="signature_choice" id="signature_choice" onchange="handleSignatureChoice(this.value)">
                                        <?php if ($hasCustomSig): ?>
                                            <option value="keep_current" <?= $isIncluded ? 'selected' : '' ?>>Keep Current Attached Signature</option>
                                        <?php endif; ?>

                                        <?php if (!empty($officialSignatures)): ?>
                                            <?php foreach ($officialSignatures as $sig): ?>
                                                <?php 
                                                $isThisSig = ($hasCustomSig && $letter['custom_signature'] === $sig['signature_path']);
                                                $isSelected = (!$hasCustomSig && $isIncluded && $sig['is_default'] == 1);
                                                ?>
                                                <option value="official_<?= $sig['id'] ?>" 
                                                        data-name="<?= htmlspecialchars($sig['signatory_name']) ?>" 
                                                        data-title="<?= htmlspecialchars($sig['signatory_title']) ?>"
                                                        <?= ($isThisSig || $isSelected) ? 'selected' : '' ?>>
                                                    Official: <?= htmlspecialchars($sig['signatory_name']) ?> (<?= htmlspecialchars($sig['signatory_title']) ?>)<?= ($sig['is_default'] == 1) ? ' [Default]' : '' ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php elseif ($hasOfficialSig): ?>
                                            <option value="official" <?= (!$hasCustomSig && $isIncluded) ? 'selected' : '' ?>>Yes — Include Official HR Signature</option>
                                        <?php endif; ?>

                                        <option value="custom">🖊️ Upload New Custom Signature (Photo/Scan)</option>
                                        <option value="none" <?= !$isIncluded ? 'selected' : '' ?>>No — Leave Blank (Physical Sign)</option>
                                    </select>
                                    <input type="hidden" name="include_signature" id="include_signature" value="<?= $isIncluded ? '1' : '0' ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Custom Signature Upload Box -->
                        <div class="col-12" id="custom_sig_wrapper" style="display: <?= ($sigChoice === 'custom') ? 'block' : 'none' ?>;">
                            <div class="card p-3 border rounded shadow-sm bg-light mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold text-dark"><i class="fa-solid fa-wand-magic-sparkles text-primary me-1"></i> Auto-Extracted Signature Upload</span>
                                    <span class="badge bg-primary text-white">AI Background Remover</span>
                                </div>
                                <p class="text-muted small mb-3">
                                    Upload any mobile photo or paper scan with a signature. Our extractor automatically removes shadows, off-white paper backgrounds, enhances ink contrast, and crops it cleanly with 100% transparency.
                                </p>
                                <div class="row align-items-center g-3">
                                    <?php if ($hasCustomSig): ?>
                                        <div class="col-12 mb-2">
                                            <div class="p-2 border rounded bg-white d-flex align-items-center gap-3">
                                                <div style="background-image: repeating-linear-gradient(45deg, #e2e8f0 25%, transparent 25%, transparent 75%, #e2e8f0 75%, #e2e8f0), repeating-linear-gradient(45deg, #e2e8f0 25%, #ffffff 25%, #ffffff 75%, #e2e8f0 75%, #e2e8f0); background-size: 16px 16px; background-position: 0 0, 8px 8px; padding: 4px; border-radius: 4px;">
                                                    <img src="<?= $GLOBALS['base_url'] . '/' . ltrim($letter['custom_signature'], '/\\') ?>" alt="Current Custom Signature" style="height: 45px; max-width: 160px; object-fit: contain;">
                                                </div>
                                                <span class="small text-muted"><i class="fa-solid fa-circle-check text-success me-1"></i> Current custom signature is attached. Uploading a new photo below will replace it.</span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Choose New Signature Photo / Scan</label>
                                        <input type="file" name="custom_signature_file" id="custom_signature_file" class="form-control" accept="image/*">
                                        <div class="form-text small">Accepts JPG, PNG, WEBP, or HEIC scans from phones/scanners.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Live Extraction Preview (Transparent Output)</label>
                                        <div id="sig_preview_box" class="p-2 border rounded text-center" style="min-height: 80px; display: flex; align-items: center; justify-content: center; background-image: repeating-linear-gradient(45deg, #e2e8f0 25%, transparent 25%, transparent 75%, #e2e8f0 75%, #e2e8f0), repeating-linear-gradient(45deg, #e2e8f0 25%, #ffffff 25%, #ffffff 75%, #e2e8f0 75%, #e2e8f0); background-size: 16px 16px; background-position: 0 0, 8px 8px;">
                                            <span id="sig_preview_placeholder" class="text-muted small">Select an image to preview transparent extraction</span>
                                            <canvas id="sig_preview_canvas" style="display: none; max-height: 70px; max-width: 100%; object-fit: contain;"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="cc_email">CC Email <span class="text-muted">Optional</span></label>
                                </div>
                                <div class="form__input">
                                    <input class="form-control" name="cc_email" id="cc_email" type="email" placeholder="e.g. hr@company.com" value="<?= htmlspecialchars((string)($letter['cc_email'] ?? '')) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="send_to_email">Send to Employee Email <span class="text-muted">Optional</span></label>
                                </div>
                                <div class="form__input">
                                    <select class="form-control" name="send_to_email" id="send_to_email">
                                        <option value="0" <?= $letter['send_to_email'] == 0 ? 'selected' : '' ?>>No — Don't send email</option>
                                        <option value="1" <?= $letter['send_to_email'] == 1 ? 'selected' : '' ?>>Yes — Send via email</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="letter_status">Letter Status <span>*</span></label>
                                </div>
                                <div class="form__input">
                                    <select class="form-control" name="letter_status" id="letter_status" required>
                                        <option value="">— Select Status —</option>
                                        <option value="draft" <?= $letter['status'] == 'draft' ? 'selected' : '' ?>>Draft</option>
                                        <option value="issued" <?= $letter['status'] == 'issued' ? 'selected' : '' ?>>Issued</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="submit__btn text-center">
                    <a href="<?= $GLOBALS['base_url'] ?>/generate-letter" class="btn btn-secondary me-2">Cancel</a>
                    <button class="btn btn-primary" type="submit">Update Letter</button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
    function autoSelectDeptDesig() {
        var empSelect = document.getElementById('employee_id');
        var selected = empSelect.options[empSelect.selectedIndex];

        if (!selected || !selected.value) {
            if (typeof $ !== 'undefined') {
                $('#designation_id').val('').trigger('change');
                $('#department_id').val('').trigger('change');
            } else {
                document.getElementById('designation_id').value = '';
                document.getElementById('department_id').value = '';
            }
            return;
        }

        var desigId = selected.dataset.designation || '';
        var deptId = selected.dataset.department || '';

        if (typeof $ !== 'undefined') {
            $('#designation_id').val(desigId).trigger('change');
            $('#department_id').val(deptId).trigger('change');
        } else {
            document.getElementById('designation_id').value = desigId;
            document.getElementById('department_id').value = deptId;
        }
    }

    // ─── Letter Type Change ─────────────────────────────────────────────────────
    function onLetterTypeChange() {
        var type = document.getElementById('letter_type').value;
        var isSalaryCert = (type === 'salary_certificate');

        var stdSection = document.getElementById('section_standard_details');
        var salaryPromo = document.getElementById('section_salary_promo');
        var certSection = document.getElementById('section_salary_cert');

        if (stdSection) stdSection.style.display = isSalaryCert ? 'none' : '';
        if (salaryPromo) salaryPromo.style.display = isSalaryCert ? 'none' : '';
        if (certSection) certSection.style.display = isSalaryCert ? '' : 'none';

        // Disable required fields in hidden section so browser validation doesn't block
        if (stdSection) {
            var stdRequiredFields = stdSection.querySelectorAll('[required], [name="subject"], [name="letter_date"], [name="letter_body"]');
            stdRequiredFields.forEach(function (el) {
                el.disabled = isSalaryCert;
            });
        }

        if (!isSalaryCert) {
            loadTemplatesForType();
        }
    }

    // ─── Add salary row ─────────────────────────────────────────────────────────
    var rowCounter = 100;
    function addSalaryRow(itemType) {
        addSalaryRowWithData(itemType, '', 0);
    }

    function addSalaryRowWithData(itemType, label, amount) {
        rowCounter++;
        var containerId = itemType === 'earning' ? 'earnings_container' : 'deductions_container';
        var container = document.getElementById(containerId);

        var borderColor = itemType === 'earning' ? '#0d6efd' : '#dc3545';
        var suggestions = ['Basic Salary', 'House Rent Allowance', 'Medical Allowance', 'Conveyance', 'Mobile Allowance', 'Food Allowance', 'Others Allowance'];
        var suggestList = suggestions.map(s => '<option value="' + s + '">').join('');

        var html = '<div class="salary-row-item d-flex align-items-center gap-2" id="row_' + rowCounter + '" style="border-left:3px solid ' + borderColor + ';">';
        html += '<input type="hidden" name="item_type_row[]" value="' + itemType + '">';
        html += '<datalist id="suggest_' + rowCounter + '">' + suggestList + '</datalist>';
        html += '<input type="text" name="item_label[]" class="form-control form-control-sm" placeholder="Label (e.g. House Rent)" list="suggest_' + rowCounter + '" value="' + htmlEscape(label) + '" style="flex:2;" oninput="recalcTotals()">';
        html += '<input type="number" name="item_amount[]" class="form-control form-control-sm text-end" placeholder="Amount" value="' + (amount || '') + '" style="flex:1; max-width:160px;" oninput="recalcTotals()" step="0.01" min="0">';
        html += '<button type="button" class="btn btn-sm btn-outline-danger px-2" onclick="removeSalaryRow(\'row_' + rowCounter + '\')" title="Remove"><i class="fa fa-times"></i></button>';
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
        var gross = 0;
        var deduction = 0;

        document.querySelectorAll('#earnings_container .salary-row-item').forEach(function (row) {
            var input = row.querySelector('input[name="item_amount[]"]');
            var amt = input ? (parseFloat(input.value) || 0) : 0;
            gross += amt;
        });
        document.querySelectorAll('#deductions_container .salary-row-item').forEach(function (row) {
            var input = row.querySelector('input[name="item_amount[]"]');
            var amt = input ? (parseFloat(input.value) || 0) : 0;
            deduction += amt;
        });

        var net = gross - deduction;
        var grossDisp = document.getElementById('gross_display');
        var netDisp = document.getElementById('net_display');
        var grossHid = document.getElementById('gross_salary_hidden');
        var netHid = document.getElementById('net_salary_hidden');

        if (grossDisp) grossDisp.textContent = 'BDT ' + formatNum(gross);
        if (netDisp) netDisp.textContent = 'BDT ' + formatNum(net);
        if (grossHid) grossHid.value = gross.toFixed(2);
        if (netHid) netHid.value = net.toFixed(2);
    }

    function formatNum(n) {
        return n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function htmlEscape(str) {
        return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    // ─── Purpose "Other" toggle ─────────────────────────────────────────────────
    function toggleOtherPurpose() {
        var val = document.getElementById('purpose').value;
        var box = document.getElementById('other_purpose_box');
        if (box) box.style.display = (val === 'Other') ? '' : 'none';
    }

    // ─── Form submit handler ────────────────────────────────────────────────────
    document.getElementById('editLetterForm').addEventListener('submit', function (e) {
        if (typeof tinymce !== 'undefined') {
            tinymce.triggerSave();
        }

        var type = document.getElementById('letter_type').value;
        if (!type) { e.preventDefault(); alert('Please select a letter type.'); return; }

        if (type === 'salary_certificate') {
            var scDate = document.getElementById('sc_letter_date').value;
            var certMonth = document.getElementById('certificate_month').value;
            var purposeSel = document.getElementById('purpose');

            if (!scDate) { e.preventDefault(); alert('Please select a letter date.'); return; }
            if (!certMonth) { e.preventDefault(); alert('Please select the certificate month.'); return; }
            if (!purposeSel.value) { e.preventDefault(); alert('Please select a purpose.'); return; }

            if (purposeSel.value === 'Other') {
                var otherText = document.getElementById('purpose_other_text').value.trim();
                if (!otherText) { e.preventDefault(); alert('Please specify the purpose.'); return; }
                purposeSel.value = otherText;
            }

            // Re-enable disabled fields so they are included in POST data
            var stdSection = document.getElementById('section_standard_details');
            if (stdSection) {
                stdSection.querySelectorAll('[disabled]').forEach(function (el) { el.disabled = false; });
            }

            // Sync salary-cert inputs → standard POST fields
            document.getElementById('letter_date').value = scDate;
            document.getElementById('ref_number').value = document.getElementById('sc_ref_number').value;
            document.getElementById('subject').value = document.getElementById('sc_subject').value;

            var scJoinVal = document.getElementById('sc_joining_date');
            if (scJoinVal && scJoinVal.value) {
                document.getElementById('joining_date').value = scJoinVal.value;
            }
            var scEffVal = document.getElementById('sc_effective_date');
            if (scEffVal && scEffVal.value) {
                document.getElementById('effective_date').value = scEffVal.value;
            }

            recalcTotals();
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        onLetterTypeChange();
        recalcTotals();
    });

    function resetSalaryCertParagraphs(force) {
        var empSel = document.getElementById('employee_id');
        var opt = (empSel && empSel.selectedIndex >= 0) ? empSel.options[empSel.selectedIndex] : null;

        var rawName = '<?= htmlspecialchars(trim($letter["full_name"] ?? "")) ?>';
        if (opt && opt.dataset && opt.dataset.fullname) rawName = opt.dataset.fullname;

        var empCode = '<?= htmlspecialchars($letter["employee_code"] ?? ($letter["employee_id"] ?? "")) ?>';
        if (opt && opt.dataset && opt.dataset.code) empCode = opt.dataset.code;

        var gender = '<?= strtolower($letter["gender"] ?? "") ?>';
        if (opt && opt.dataset && opt.dataset.gender) gender = opt.dataset.gender.toLowerCase();
        var sheHe = (gender === 'female') ? 'she' : 'he';
        var herHis = (gender === 'female') ? 'her' : 'his';
        var herHim = (gender === 'female') ? 'her' : 'him';

        var desigSel = document.getElementById('designation_id');
        var desigName = (desigSel && desigSel.selectedIndex >= 0 && desigSel.options[desigSel.selectedIndex].text)
            ? desigSel.options[desigSel.selectedIndex].text.replace(/\s*\(.*?\)\s*/, '').trim()
            : '<?= htmlspecialchars($letter["designation_name"] ?? "N/A") ?>';

        var companyName = '<?= htmlspecialchars(function_exists("getSetting") ? getSetting("website_title", "The Premium Homes Limited") : "The Premium Homes Limited") ?>';

        var joinVal = document.getElementById('sc_joining_date') ? document.getElementById('sc_joining_date').value : '<?= $letter["joining_date"] ?? "" ?>';
        var effVal = document.getElementById('sc_effective_date') ? document.getElementById('sc_effective_date').value : '<?= $letter["effective_date"] ?? "" ?>';

        var startDate = joinVal ? formatDateLong(joinVal) : 'N/A';
        var endDate = effVal ? formatDateLong(effVal) : '';
        var periodText = endDate ? ('from ' + startDate + ' to ' + endDate) : ('from ' + startDate + ' to present');

        var purposeSel = document.getElementById('purpose');
        var purposeVal = 'final settlement';
        if (purposeSel) {
            if (purposeSel.value === 'Other') {
                var otherText = document.getElementById('purpose_other_text');
                purposeVal = (otherText && otherText.value.trim()) ? otherText.value.trim() : 'personal use';
            } else if (purposeSel.value) {
                purposeVal = purposeSel.value.toLowerCase();
            }
        }

        var defaultOpening = 'This is to certify that **' + rawName + '** (Employee ID: ' + empCode + ') was a Permanent employee of **' + companyName + '** and ' + sheHe + ' was employed as **' + desigName + '** ' + periodText + '.\n\nAccording to our records, ' + herHis + ' monthly salary breakdown was as follows:';

        var defaultClosing = 'This certificate is issued upon ' + herHis + ' request for ' + purposeVal + ' without any liability on the part of this company.\n\nWe wish ' + herHim + ' all the very best.';

        var scOpEl = document.getElementById('sc_opening_paragraph');
        var scClEl = document.getElementById('sc_closing_paragraph');
        if (scOpEl) scOpEl.value = defaultOpening;
        if (scClEl) scClEl.value = defaultClosing;
    }

    function formatDateLong(dateStr) {
        if (!dateStr) return '';
        var parts = dateStr.split('-');
        if (parts.length !== 3) return dateStr;
        var d = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
        if (isNaN(d.getTime())) return dateStr;
        var day = ('0' + d.getDate()).slice(-2);
        var months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        return day + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
    }

    function loadTemplatesForType() {
        var letterType = document.getElementById('letter_type').value;
        var templateSelect = document.getElementById('template_id');

        if (!letterType) {
            templateSelect.innerHTML = '<option value="">— No Template —</option>';
            return;
        }

        fetch('<?= $GLOBALS['base_url'] ?>/get-templates-for-type?type=' + encodeURIComponent(letterType))
            .then(response => response.json())
            .then(data => {
                if (data.success && data.templates) {
                    let html = '<option value="">— No Template —</option>';
                    data.templates.forEach(template => {
                        html += '<option value="' + template.id + '">' + template.name + '</option>';
                    });
                    templateSelect.innerHTML = html;
                } else {
                    templateSelect.innerHTML = '<option value="">— No Template —</option>';
                }
            })
            .catch(error => {
                console.error('Error loading templates:', error);
                templateSelect.innerHTML = '<option value="">— No Template —</option>';
            });
    }

    function loadTemplate() {
        var templateId = document.getElementById('template_id').value;
        if (!templateId) return;

        fetch('<?= $GLOBALS['base_url'] ?>/get-template?id=' + encodeURIComponent(templateId))
            .then(response => response.json())
            .then(data => {
                if (data.success && data.template) {
                    document.getElementById('subject').value = data.template.subject || '';
                    if (typeof tinymce !== 'undefined' && tinymce.get('tinymce_simple_textarea')) {
                        tinymce.get('tinymce_simple_textarea').setContent(data.template.body || '');
                    } else {
                        document.getElementById('tinymce_simple_textarea').value = data.template.body || '';
                    }
                }
            })
            .catch(error => console.error('Error loading template:', error));
    }

    function handleSignatureChoice(choice) {
        var wrapper = document.getElementById('custom_sig_wrapper');
        var hiddenInc = document.getElementById('include_signature');
        var sigSelect = document.getElementById('signature_choice');
        var nameInput = document.getElementById('signatory_name');
        var titleInput = document.getElementById('signatory_title');

        if (choice === 'custom') {
            if (wrapper) wrapper.style.display = 'block';
            if (hiddenInc) hiddenInc.value = '1';
        } else if (choice === 'none') {
            if (wrapper) wrapper.style.display = 'none';
            if (hiddenInc) hiddenInc.value = '0';
        } else {
            if (wrapper) wrapper.style.display = 'none';
            if (hiddenInc) hiddenInc.value = '1';

            if (choice !== 'keep_current' && sigSelect && sigSelect.selectedIndex >= 0) {
                var selectedOption = sigSelect.options[sigSelect.selectedIndex];
                var optName = selectedOption.getAttribute('data-name');
                var optTitle = selectedOption.getAttribute('data-title');
                if (optName && nameInput) nameInput.value = optName;
                if (optTitle && titleInput) titleInput.value = optTitle;
            }
        }
    }

    // Client-side live signature extraction preview
    var sigFileInput = document.getElementById('custom_signature_file');
    if (sigFileInput) {
        sigFileInput.addEventListener('change', function (e) {
            var file = e.target.files[0];
            if (!file) return;
            var reader = new FileReader();
            reader.onload = function (event) {
                var img = new Image();
                img.onload = function () {
                    var canvas = document.getElementById('sig_preview_canvas');
                    var placeholder = document.getElementById('sig_preview_placeholder');
                    if (!canvas) return;

                    var w = img.width;
                    var h = img.height;
                    var maxD = 1200;
                    if (w > maxD || h > maxD) {
                        var scale = Math.min(maxD / w, maxD / h);
                        w = Math.floor(w * scale);
                        h = Math.floor(h * scale);
                    }

                    var offCanvas = document.createElement('canvas');
                    offCanvas.width = w;
                    offCanvas.height = h;
                    var offCtx = offCanvas.getContext('2d');
                    offCtx.drawImage(img, 0, 0, w, h);

                    var imgData = offCtx.getImageData(0, 0, w, h);
                    var data = imgData.data;

                    var minX = w, maxX = 0, minY = h, maxY = 0;
                    var threshold = 205;

                    for (var y = 0; y < h; y++) {
                        for (var x = 0; x < w; x++) {
                            var idx = (y * w + x) * 4;
                            var r = data[idx];
                            var g = data[idx + 1];
                            var b = data[idx + 2];
                            var lum = 0.299 * r + 0.587 * g + 0.114 * b;

                            if (lum >= threshold) {
                                data[idx + 3] = 0;
                            } else {
                                var alphaRatio = (threshold - lum) / threshold;
                                var alpha = Math.min(255, Math.floor(alphaRatio * 255 * 1.5));
                                data[idx + 3] = alpha;
                                data[idx] = Math.max(0, r - 50);
                                data[idx + 1] = Math.max(0, g - 50);
                                data[idx + 2] = Math.max(0, b - 40);

                                if (x < minX) minX = x;
                                if (x > maxX) maxX = x;
                                if (y < minY) minY = y;
                                if (y > maxY) maxY = y;
                            }
                        }
                    }

                    offCtx.putImageData(imgData, 0, 0);

                    if (maxX >= minX && maxY >= minY) {
                        var pad = 10;
                        minX = Math.max(0, minX - pad);
                        minY = Math.max(0, minY - pad);
                        maxX = Math.min(w - 1, maxX + pad);
                        maxY = Math.min(h - 1, maxY + pad);
                        var cropW = maxX - minX + 1;
                        var cropH = maxY - minY + 1;

                        canvas.width = cropW;
                        canvas.height = cropH;
                        var ctx = canvas.getContext('2d');
                        ctx.clearRect(0, 0, cropW, cropH);
                        ctx.drawImage(offCanvas, minX, minY, cropW, cropH, 0, 0, cropW, cropH);
                    } else {
                        canvas.width = w;
                        canvas.height = h;
                        var ctx = canvas.getContext('2d');
                        ctx.drawImage(offCanvas, 0, 0);
                    }

                    if (placeholder) placeholder.style.display = 'none';
                    canvas.style.display = 'inline-block';
                };
                img.src = event.target.result;
            };
            reader.readAsDataURL(file);
        });
    }
</script>

<?php include __DIR__ . '/modal-signatures-manager.php'; ?>
