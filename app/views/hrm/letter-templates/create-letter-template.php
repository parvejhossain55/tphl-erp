<style>
    @media (max-width: 767.98px) {
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
                <h5 class="card__heading-title">Generate Employee Letter</h5>
                <a href="<?= $GLOBALS['base_url'] ?>/generate-letter" class="btn btn-secondary btn-sm">
                    <i class="fa fa-arrow-left me-1"></i> Back to List
                </a>
            </div>

            <form action="<?= $GLOBALS['base_url'] ?>/store-generate-letter" method="POST" enctype="multipart/form-data" id="letterForm">

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
                                    <select name="employee_id" id="employee_id" class="form-control select2"
                                        onchange="onEmployeeChange()">
                                        <option value="">Select Employee</option>
                                        <?php if (!empty($employees)): ?>
                                            <?php foreach ($employees as $emp): ?>
                                                <option value="<?= $emp['id'] ?>"
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
                                                    data-email="<?= htmlspecialchars($emp['email'] ?? '') ?>">
                                                    <?= htmlspecialchars($emp['full_name']) ?>
                                                    (<?= htmlspecialchars($emp['employee_id']) ?>)
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
                                    <select class="form-control" name="letter_type" id="letter_type"
                                        onchange="onLetterTypeChange()">
                                        <option value="">— Select Letter Type —</option>
                                        <option value="appointment">Appointment Letter</option>
                                        <option value="joining">Joining Letter</option>
                                        <option value="promotion">Promotion Letter</option>
                                        <option value="increment">Salary Increment Letter</option>
                                        <option value="experience">Experience Certificate</option>
                                        <option value="noc">No Objection Certificate (NOC)</option>
                                        <option value="salary_certificate">💼 Salary Certificate</option>
                                        <option value="show_cause">Show Cause Letter</option>
                                        <option value="warning">Warning Letter</option>
                                        <option value="caution">Caution Letter</option>
                                        <option value="improvement">Improvement Letter</option>
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
                                    <select class="form-control select2" name="designation_id" id="designation_id"
                                        required>
                                        <option value="">— Select Designation —</option>
                                        <?php foreach ($designations as $desig): ?>
                                            <option value="<?= $desig['id'] ?>">
                                                <?= htmlspecialchars($desig['designation_name'] . ' (' . ($desig['department_name'] ?? 'N/A') . ')') ?>
                                            </option>
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
                                    <select class="form-control select2" name="department_id" id="department_id"
                                        required>
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
                                <div class="form__input-title"><label for="letter_date">Letter Date
                                        <span>*</span></label></div>
                                <div class="form__input"><input class="form-control" name="letter_date" id="letter_date"
                                        type="date"></div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title"><label for="ref_number">Reference Number <span
                                            class="text-muted">Optional</span></label></div>
                                <div class="form__input"><input class="form-control" name="ref_number" id="ref_number"
                                        type="text" placeholder="e.g. HR/2024/001"></div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title"><label for="joining_date">Joining Date</label></div>
                                <div class="form__input"><input class="form-control" name="joining_date"
                                        id="joining_date" type="date"></div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title"><label for="effective_date">Effective Date <span
                                            class="text-muted">Optional</span></label></div>
                                <div class="form__input"><input class="form-control" name="effective_date"
                                        id="effective_date" type="date"></div>
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
                                <textarea class="form-control" id="tinymce_simple_textarea" name="letter_body" rows="4"
                                    placeholder="Enter letter content..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══ Section: Salary & Promotion Info (hidden for salary_certificate) ═══ -->
                <div id="section_salary_promo" class="card__wrapper mb-20">
                    <div class="card__title-wrap mb-15">
                        <h6 class="card__heading-title">Salary Information <small class="text-muted">(For Increment /
                                Promotion Letter)</small></h6>
                    </div>
                    <div class="row g-20 gy-20">
                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title"><label for="current_salary">Current Salary <span
                                            class="text-muted">Optional</span></label></div>
                                <div class="form__input"><input class="form-control" name="current_salary"
                                        id="current_salary" type="number" placeholder="e.g. 50000" value=""></div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title"><label for="revised_salary">Revised Salary <span
                                            class="text-muted">Optional</span></label></div>
                                <div class="form__input"><input class="form-control" name="revised_salary"
                                        id="revised_salary" type="number" placeholder="e.g. 60000" value=""></div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title"><label for="new_designation">New Position <span
                                            class="text-muted">Optional</span></label></div>
                                <div class="form__input"><input class="form-control" name="new_designation"
                                        id="new_designation" type="text" placeholder="e.g. Senior Engineer"></div>
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
                                <div class="form__input"><input class="form-control" name="sc_letter_date"
                                        id="sc_letter_date" type="date" value="<?= date('Y-m-d') ?>"></div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title"><label>Certificate Month <span>*</span></label></div>
                                <div class="form__input">
                                    <input class="form-control" name="certificate_month" id="certificate_month"
                                        type="month" placeholder="e.g. 2026-06" value="<?= date('Y-m') ?>">
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title"><label>Reference Number <span
                                            class="text-muted">Optional</span></label></div>
                                <div class="form__input"><input class="form-control" name="sc_ref_number"
                                        id="sc_ref_number" type="text"
                                        placeholder="TPHL/HR/Salary Certificate/September-2026" value="TPHL/HR/Salary Certificate/<?= date('F-Y') ?>"></div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title"><label>Subject <span>*</span></label></div>
                                <div class="form__input"><input class="form-control" name="sc_subject" id="sc_subject"
                                        type="text" value="Salary Certificate"></div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title"><label>Purpose <span>*</span></label></div>
                                <div class="form__input">
                                    <select class="form-control" name="purpose" id="purpose"
                                        onchange="toggleOtherPurpose()">
                                        <option value="">— Select Purpose —</option>
                                        <option value="Final Settlement" selected>Final Settlement</option>
                                        <option value="Bank Loan">Bank Loan</option>
                                        <option value="Visa Application">Visa Application</option>
                                        <option value="Tenancy / House Rent">Tenancy / House Rent</option>
                                        <option value="Personal Use">Personal Use</option>
                                        <option value="Other">Other (specify)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title"><label>Employment Joining Date</label></div>
                                <div class="form__input"><input class="form-control" name="sc_joining_date" id="sc_joining_date" type="date"></div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title"><label>Separation / End Date <span class="text-muted">(Leave blank if currently employed)</span></label></div>
                                <div class="form__input"><input class="form-control" name="sc_effective_date" id="sc_effective_date" type="date"></div>
                            </div>
                        </div>

                        <div class="col-lg-12" id="other_purpose_box" style="display:none;">
                            <div class="from__input-box">
                                <div class="form__input-title"><label>Specify Purpose</label></div>
                                <div class="form__input">
                                    <input class="form-control" type="text" id="purpose_other_text"
                                        placeholder="Enter purpose...">
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
                        <div class="section-label">➖ Deductions <small class="text-muted fw-normal">(Optional)</small>
                        </div>
                        <div id="deductions_container"></div>
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
                    <input type="hidden" name="net_salary" id="net_salary_hidden">

                </div>

                <!-- ═══ Section: Signatory ═══════════════════════════════════ -->
                <div class="card__wrapper mb-20">
                    <div class="card__title-wrap mb-15">
                        <h6 class="card__heading-title">Signatory Details</h6>
                    </div>
                    <div class="row g-20 gy-20">

                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title"><label for="signatory_name">Signed By
                                        <span>*</span></label></div>
                                <div class="form__input"><input class="form-control" name="signatory_name"
                                        id="signatory_name" type="text" placeholder="Full Name" value="Mohammad Jasim Uddin Khan" required></div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title"><label for="signatory_title">Designation / Title
                                        <span>*</span></label></div>
                                <div class="form__input"><input class="form-control" name="signatory_title"
                                        id="signatory_title" type="text" placeholder="e.g. Chief Executive Officer"
                                        value="Chief Executive Officer" required></div>
                            </div>
                        </div>

                        <?php
                        $baseDir = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4);
                        $hasOfficialSig = file_exists($baseDir . '/public/assets/images/hrm/hr_signature.png');
                        ?>
                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title"><label for="signature_choice">Digital Seal & Signature</label></div>
                                <div class="form__input">
                                    <select class="form-control" name="signature_choice" id="signature_choice" onchange="handleSignatureChoice(this.value)">
                                        <?php if ($hasOfficialSig): ?>
                                            <option value="official" selected>Yes — Include Official HR Signature</option>
                                            <option value="custom">🖊️ Upload Custom Signature (Paper Photo/Scan)</option>
                                            <option value="none">No — Leave Blank (Physical Sign)</option>
                                        <?php else: ?>
                                            <option value="none" selected>No — Leave Blank (Physical Sign)</option>
                                            <option value="custom">🖊️ Upload Custom Signature (Paper Photo/Scan)</option>
                                            <option value="official" disabled>Official HR Signature (Not uploaded yet)</option>
                                        <?php endif; ?>
                                    </select>
                                    <input type="hidden" name="include_signature" id="include_signature" value="<?= $hasOfficialSig ? '1' : '0' ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Custom Signature Upload Box (Hidden by default) -->
                        <div class="col-12" id="custom_sig_wrapper" style="display: none;">
                            <div class="card p-3 border rounded shadow-sm bg-light mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold text-dark"><i class="fa-solid fa-wand-magic-sparkles text-primary me-1"></i> Auto-Extracted Signature Upload</span>
                                    <span class="badge bg-primary text-white">AI Background Remover</span>
                                </div>
                                <p class="text-muted small mb-3">
                                    Upload any mobile photo or paper scan with a signature. Our intelligent extractor automatically removes shadows and off-white paper backgrounds, enhances ink contrast, and crops it cleanly with 100% transparency.
                                </p>
                                <div class="row align-items-center g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Choose Signature Photo / Scan</label>
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
                                <div class="form__input-title"><label for="cc_email">CC Email <span
                                            class="text-muted">Optional</span></label></div>
                                <div class="form__input"><input class="form-control" name="cc_email" id="cc_email"
                                        type="email" placeholder="e.g. hr@company.com" value=""></div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title"><label for="send_to_email">Send to Employee Email <span
                                            class="text-muted">Optional</span></label></div>
                                <div class="form__input">
                                    <select class="form-control" name="send_to_email" id="send_to_email">
                                        <option value="0">No — Don't send email</option>
                                        <option value="1">Yes — Send via email</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title"><label for="letter_status">Letter Status
                                        <span>*</span></label></div>
                                <div class="form__input">
                                    <select class="form-control" name="letter_status" id="letter_status" required>
                                        <option value="draft" selected>Draft</option>
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
    // ─── Placeholder Replacement Engine ────────────────────────────────────────
    window.currentRawTemplateSubject = '';
    window.currentRawTemplateBody = '';

    function replacePlaceholdersInText(text) {
        if (!text) return '';
        var empSelect = document.getElementById('employee_id');
        if (!empSelect || empSelect.selectedIndex < 0) return text;
        var opt = empSelect.options[empSelect.selectedIndex];
        if (!opt || !opt.value) return text;

        var empName = opt.dataset.name || '';
        var empCode = opt.dataset.code || '';
        var desigName = opt.dataset.designationName || '';
        var deptName = opt.dataset.departmentName || '';
        var joiningRaw = opt.dataset.joining || '';
        var resignRaw = opt.dataset.resign || '';
        var gender = (opt.dataset.gender || '').toLowerCase();

        // Check if designation or department dropdown is selected
        var dSelect = document.getElementById('designation_id');
        if (dSelect && dSelect.selectedIndex > 0) {
            var dText = dSelect.options[dSelect.selectedIndex].text;
            desigName = dText.replace(/\s*\(.*\)$/, '').trim();
        }
        var dpSelect = document.getElementById('department_id');
        if (dpSelect && dpSelect.selectedIndex > 0) {
            deptName = dpSelect.options[dpSelect.selectedIndex].text.trim();
        }

        var joinInput = document.getElementById('joining_date');
        if (joinInput && joinInput.value) joiningRaw = joinInput.value;

        var effInput = document.getElementById('effective_date');
        if (effInput && effInput.value) resignRaw = effInput.value;

        function formatLongDate(dateStr) {
            if (!dateStr) return '';
            var parts = dateStr.split('-');
            if (parts.length === 3) {
                var y = parseInt(parts[0], 10);
                var m = parseInt(parts[1], 10) - 1;
                var d = parseInt(parts[2], 10);
                var months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                var dayStr = (d < 10 ? '0' : '') + d;
                return months[m] + ' ' + dayStr + ', ' + y;
            }
            return dateStr;
        }

        var joiningFormatted = formatLongDate(joiningRaw);
        var releaseFormatted = formatLongDate(resignRaw);

        // Gender pronoun detection
        var isFemale = (gender === 'female');
        if (!isFemale) {
            var femaleKeywords = ['ms.', 'mrs.', 'miss', 'khatun', 'begum', 'akter', 'akther', 'sultana', 'jahan', 'nahar', 'parvin', 'nasrin', 'farzana', 'roksana', 'shammi', 'bornali'];
            var nl = empName.toLowerCase();
            for (var i = 0; i < femaleKeywords.length; i++) {
                if (nl.indexOf(femaleKeywords[i]) !== -1) {
                    isFemale = true;
                    break;
                }
            }
        }

        var sheHe = isFemale ? 'she' : 'he';
        var SheHe = isFemale ? 'She' : 'He';
        var herHis = isFemale ? 'her' : 'his';
        var HerHis = isFemale ? 'Her' : 'His';
        var herHim = isFemale ? 'her' : 'him';
        var companyName = 'The Premium Homes Limited';

        var res = text;
        res = res.replace(/\{\{\s*employee_name\s*\}\}/gi, empName);
        res = res.replace(/\{\{\s*name\s*\}\}/gi, empName);
        res = res.replace(/\{\{\s*employee_code\s*\}\}/gi, empCode);
        res = res.replace(/\{\{\s*employee_id\s*\}\}/gi, empCode);
        res = res.replace(/\{\{\s*designation\s*\}\}/gi, desigName);
        res = res.replace(/\{\{\s*department\s*\}\}/gi, deptName);
        res = res.replace(/\{\{\s*company_name\s*\}\}/gi, companyName);
        res = res.replace(/\{\{\s*joining_date\s*\}\}/gi, joiningFormatted);
        res = res.replace(/\{\{\s*release_date\s*\}\}/gi, releaseFormatted);
        res = res.replace(/\{\{\s*effective_date\s*\}\}/gi, releaseFormatted);
        res = res.replace(/\{\{\s*separation_date\s*\}\}/gi, releaseFormatted);
        res = res.replace(/\{\{\s*she_he\s*\}\}/g, sheHe);
        res = res.replace(/\{\{\s*She_He\s*\}\}/g, SheHe);
        res = res.replace(/\{\{\s*her_his\s*\}\}/g, herHis);
        res = res.replace(/\{\{\s*Her_His\s*\}\}/g, HerHis);
        res = res.replace(/\{\{\s*her_him\s*\}\}/g, herHim);

        return res;
    }

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
        var desigId = opt.dataset.designationId || opt.dataset.designation || '';
        var deptId = opt.dataset.departmentId || opt.dataset.department || '';
        if (typeof $ !== 'undefined') {
            $('#designation_id').val(desigId).trigger('change');
            $('#department_id').val(deptId).trigger('change');
        }

        // Auto-fill dates
        var joinDate = opt.dataset.joining || '';
        var resignDate = opt.dataset.resign || '';
        var joinInput = document.getElementById('joining_date');
        if (joinInput && (!joinInput.value || joinInput.value === '') && joinDate) {
            joinInput.value = joinDate;
        }
        var scJoinInput = document.getElementById('sc_joining_date');
        if (scJoinInput && (!scJoinInput.value || scJoinInput.value === '') && joinDate) {
            scJoinInput.value = joinDate;
        }

        var effInput = document.getElementById('effective_date');
        if (effInput && (!effInput.value || effInput.value === '') && resignDate) {
            effInput.value = resignDate;
        }
        var scEffInput = document.getElementById('sc_effective_date');
        if (scEffInput && (!scEffInput.value || scEffInput.value === '') && resignDate) {
            scEffInput.value = resignDate;
        }

        var letDateInput = document.getElementById('letter_date');
        if (letDateInput && (!letDateInput.value || letDateInput.value === '')) {
            letDateInput.value = '<?= date('Y-m-d') ?>';
        }
        var scDateInput = document.getElementById('sc_letter_date');
        if (scDateInput && (!scDateInput.value || scDateInput.value === '')) {
            scDateInput.value = '<?= date('Y-m-d') ?>';
        }

        // Live update placeholders in TinyMCE & Subject if template body is loaded
        if (window.currentRawTemplateBody) {
            var replacedBody = replacePlaceholdersInText(window.currentRawTemplateBody);
            if (typeof tinymce !== 'undefined' && tinymce.get('tinymce_simple_textarea')) {
                tinymce.get('tinymce_simple_textarea').setContent(replacedBody);
            }
            var textareaEl = document.getElementById('tinymce_simple_textarea');
            if (textareaEl) textareaEl.value = replacedBody;

            if (window.currentRawTemplateSubject) {
                var replacedSubj = replacePlaceholdersInText(window.currentRawTemplateSubject);
                var subjectEl = document.getElementById('subject');
                if (subjectEl) subjectEl.value = replacedSubj;
            }
        } else if (typeof tinymce !== 'undefined' && tinymce.get('tinymce_simple_textarea')) {
            var curContent = tinymce.get('tinymce_simple_textarea').getContent();
            if (curContent && curContent.indexOf('{{') !== -1) {
                tinymce.get('tinymce_simple_textarea').setContent(replacePlaceholdersInText(curContent));
            }
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
                    var container = document.getElementById('earnings_container');
                    container.innerHTML = '';

                    if (data.items && data.items.length > 0) {
                        data.items.forEach(function (it) {
                            addSalaryRowWithData('earning', it.label, it.amount);
                        });
                    } else if (data.basic_salary) {
                        addSalaryRowWithData('earning', 'Basic Salary', data.basic_salary);
                    }

                    // Auto-fill dates & ref number if empty
                    var scDate = document.getElementById('sc_letter_date');
                    if (scDate && (!scDate.value || scDate.value === '') && data.letter_date) {
                        scDate.value = data.letter_date;
                    }
                    var certM = document.getElementById('certificate_month');
                    if (certM && (!certM.value || certM.value === '') && data.certificate_month) {
                        certM.value = data.certificate_month;
                    }
                    var refNum = document.getElementById('sc_ref_number');
                    if (refNum && (!refNum.value || refNum.value === '') && data.ref_number) {
                        refNum.value = data.ref_number;
                    }
                    var scJoin = document.getElementById('sc_joining_date');
                    if (scJoin && (!scJoin.value || scJoin.value === '') && data.joining_date) {
                        scJoin.value = data.joining_date;
                    }

                    recalcTotals();
                }
            })
            .catch(error => console.error('Error fetching employee salary:', error));
    }

    // ─── Letter Type Change ─────────────────────────────────────────────────────
    function onLetterTypeChange() {
        var type = document.getElementById('letter_type').value;
        var isSalaryCert = (type === 'salary_certificate');

        var stdSection = document.getElementById('section_standard_details');
        var salaryPromo = document.getElementById('section_salary_promo');
        var certSection = document.getElementById('section_salary_cert');

        stdSection.style.display = isSalaryCert ? 'none' : '';
        salaryPromo.style.display = isSalaryCert ? 'none' : '';
        certSection.style.display = isSalaryCert ? '' : 'none';

        // Disable required fields in hidden section so browser validation doesn't block
        var stdRequiredFields = stdSection.querySelectorAll('[required], [name="subject"], [name="letter_date"], [name="letter_body"]');
        stdRequiredFields.forEach(function (el) {
            el.disabled = isSalaryCert;
        });

        // Smart defaults for letter types
        var refInput = document.getElementById('ref_number');
        var subjInput = document.getElementById('subject');

        if (type === 'experience') {
            if (refInput && !refInput.value) refInput.value = 'TPHL/HR/Experience Certificate/<?= date('M-Y') ?>';
            if (subjInput && !subjInput.value) subjInput.value = 'Experience Certificate';
        } else if (type === 'caution') {
            if (subjInput && !subjInput.value) subjInput.value = 'Caution Letter for Negligence and Lack of Oversight on Site';
        }

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
            var scDate = document.getElementById('sc_letter_date');
            if (scDate && !scDate.value) scDate.value = '<?= date('Y-m-d') ?>';
            var certM = document.getElementById('certificate_month');
            if (certM && !certM.value) certM.value = '<?= date('Y-m') ?>';
            var refNum = document.getElementById('sc_ref_number');
            if (refNum && !refNum.value) refNum.value = 'TPHL/HR/Salary Certificate/<?= date('F-Y') ?>';
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
        var container = document.getElementById(containerId);

        var borderColor = itemType === 'earning' ? '#0d6efd' : '#dc3545';
        var suggestions = ['Basic Salary', 'House Rent', 'Medical Allowance', 'Conveyance', 'Mobile Allowance', 'Food Allowance', 'Others Allowance'];
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
            var amt = parseFloat(row.querySelector('input[name="item_amount[]"]').value) || 0;
            gross += amt;
        });
        document.querySelectorAll('#deductions_container .salary-row-item').forEach(function (row) {
            var amt = parseFloat(row.querySelector('input[name="item_amount[]"]').value) || 0;
            deduction += amt;
        });

        var net = gross - deduction;
        document.getElementById('gross_display').textContent = 'BDT ' + formatNum(gross);
        document.getElementById('net_display').textContent = 'BDT ' + formatNum(net);
        document.getElementById('gross_salary_hidden').value = gross.toFixed(2);
        document.getElementById('net_salary_hidden').value = net.toFixed(2);
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
        document.getElementById('other_purpose_box').style.display = (val === 'Other') ? '' : 'none';
    }

    // ─── Before form submit: validation + sync ───────────────────────────────────
    document.getElementById('letterForm').addEventListener('submit', function (e) {
        if (typeof tinymce !== 'undefined') {
            tinymce.triggerSave();
        }

        var type = document.getElementById('letter_type').value;
        if (!type) { e.preventDefault(); alert('Please select a letter type.'); return; }

        if (type === 'salary_certificate') {
            var scDate = document.getElementById('sc_letter_date').value;
            var certMonth = document.getElementById('certificate_month').value;
            var purposeSel = document.getElementById('purpose');

            // Validate required cert fields
            if (!scDate) { e.preventDefault(); alert('Please select a letter date.'); return; }
            if (!certMonth) { e.preventDefault(); alert('Please select the certificate month.'); return; }
            if (!purposeSel.value) { e.preventDefault(); alert('Please select a purpose.'); return; }

            // Handle "Other" purpose
            if (purposeSel.value === 'Other') {
                var otherText = document.getElementById('purpose_other_text').value.trim();
                if (!otherText) { e.preventDefault(); alert('Please specify the purpose.'); return; }
                purposeSel.value = otherText;
            }
            // Re-enable disabled fields so they are included in POST data
            var stdSection = document.getElementById('section_standard_details');
            stdSection.querySelectorAll('[disabled]').forEach(function (el) { el.disabled = false; });

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

    function resetSalaryCert() {
        document.getElementById('earnings_container').innerHTML = '';
        document.getElementById('deductions_container').innerHTML = '';
        recalcTotals();
    }

    // ─── Template loading (for non-salary-cert types) ───────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        var lt = document.getElementById('letter_type');
        if (lt) lt.addEventListener('change', onLetterTypeChange);
    });

    function loadTemplatesForType() {
        var letterType = document.getElementById('letter_type').value;
        var templateSelect = document.getElementById('template_id');
        if (!letterType) {
            templateSelect.innerHTML = '<option value="">— No Template —</option>';
            return;
        }
        fetch('<?= $GLOBALS['base_url'] ?>/get-templates-for-type?type=' + encodeURIComponent(letterType))
            .then(r => r.json())
            .then(data => {
                if (data.success && data.templates && data.templates.length > 0) {
                    var html = '<option value="">— Select Template —</option>';
                    data.templates.forEach(t => { html += '<option value="' + t.id + '">' + t.name + '</option>'; });
                    templateSelect.innerHTML = html;

                    // Automatically select first template and load content
                    templateSelect.value = data.templates[0].id;
                    loadTemplate();
                } else {
                    templateSelect.innerHTML = '<option value="">— No Template —</option>';
                }
            })
            .catch(error => console.error('Error loading templates for type:', error));
    }

    function loadTemplate() {
        var tid = document.getElementById('template_id').value;
        if (!tid) return;
        fetch('<?= $GLOBALS['base_url'] ?>/get-template?id=' + encodeURIComponent(tid))
            .then(r => r.json())
            .then(data => {
                if (data.success && data.template) {
                    window.currentRawTemplateSubject = data.template.subject || '';
                    window.currentRawTemplateBody = data.template.body || '';

                    var replacedSubject = replacePlaceholdersInText(window.currentRawTemplateSubject);
                    var replacedBody = replacePlaceholdersInText(window.currentRawTemplateBody);

                    var subjectEl = document.getElementById('subject');
                    if (subjectEl) {
                        subjectEl.value = replacedSubject;
                    }

                    if (typeof tinymce !== 'undefined' && tinymce.get('tinymce_simple_textarea')) {
                        tinymce.get('tinymce_simple_textarea').setContent(replacedBody);
                    }
                    var textareaEl = document.getElementById('tinymce_simple_textarea');
                    if (textareaEl) {
                        textareaEl.value = replacedBody;
                    }
                }
            })
            .catch(error => console.error('Error loading template:', error));
    }

    function handleSignatureChoice(choice) {
        var wrapper = document.getElementById('custom_sig_wrapper');
        var hiddenInc = document.getElementById('include_signature');
        if (choice === 'custom') {
            if (wrapper) wrapper.style.display = 'block';
            if (hiddenInc) hiddenInc.value = '1';
        } else if (choice === 'none') {
            if (wrapper) wrapper.style.display = 'none';
            if (hiddenInc) hiddenInc.value = '0';
        } else {
            if (wrapper) wrapper.style.display = 'none';
            if (hiddenInc) hiddenInc.value = '1';
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