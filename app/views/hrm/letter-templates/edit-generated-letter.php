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
        height: 42px !important; /* Force a clean and consistent height */
        font-size: 14px !important;
    }
}
</style>

<div class="row">
    <div class="card__wrapper">
        <div class="col-xxl-12 col-xl-12 col-lg-12">

            <div class="card__title-wrap mb-20">
                <h5 class="card__heading-title">Edit Generated Letter</h5>
            </div>

            <form action="<?= $GLOBALS['base_url'] ?>/update-letter/<?= $letter['id'] ?>" method="POST">

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
                                                    data-designation="<?= $emp['designation_id'] ?>"
                                                    data-department="<?= $emp['department_id'] ?>"
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
                                    <select class="form-control" name="letter_type" id="letter_type" >
                                        <option value="">— Select Letter Type —</option>
                                        <option value="appointment" <?= $letter['letter_type'] == 'appointment' ? 'selected' : '' ?>>Appointment Letter</option>
                                        <option value="joining" <?= $letter['letter_type'] == 'joining' ? 'selected' : '' ?>>Joining Letter</option>
                                        <option value="promotion" <?= $letter['letter_type'] == 'promotion' ? 'selected' : '' ?>>Promotion Letter</option>
                                        <option value="increment" <?= $letter['letter_type'] == 'increment' ? 'selected' : '' ?>>Salary Increment Letter</option>
                                        <option value="experience" <?= $letter['letter_type'] == 'experience' ? 'selected' : '' ?>>Experience Certificate</option>
                                        <option value="noc" <?= $letter['letter_type'] == 'noc' ? 'selected' : '' ?>>No Objection Certificate (NOC)</option>
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
                                                <option value="<?= $desig['id'] ?>" <?= $desig['id'] == $letter['designation_id'] ? 'selected' : '' ?>><?= $desig['designation_name'] ?></option>
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

                <!-- Section: Letter Details -->
                <div class="card__wrapper mb-20">
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
                                    <input class="form-control" name="letter_date" id="letter_date" type="date" value="<?= htmlspecialchars($letter['letter_date']) ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="ref_number">Reference Number <span class="text-muted">Optional</span></label>
                                </div>
                                <div class="form__input">
                                    <input class="form-control" name="ref_number" id="ref_number" type="text" placeholder="e.g. HR/2024/001" value="<?= htmlspecialchars($letter['ref_number']) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="joining_date">Joining Date <span>*</span></label>
                                </div>
                                <div class="form__input">
                                    <input class="form-control" name="joining_date" id="joining_date" type="date" value="<?= htmlspecialchars($letter['joining_date']) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="effective_date">Effective Date <span class="text-muted">Optional</span></label>
                                </div>
                                <div class="form__input">
                                    <input class="form-control" name="effective_date" id="effective_date" type="date" value="<?= htmlspecialchars($letter['effective_date']) ?>">
                                </div>
                            </div>
                        </div>


                        <div class="col-lg-6">
                            <label>Subject *</label>
                            <input type="text" name="subject" id="subject" class="form-control" value="<?= htmlspecialchars($letter['subject']) ?>" required>
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
                                <textarea class="form-control" id="tinymce_simple_textarea" name="letter_body" rows="4" placeholder="Enter key responsibilities..."><?= htmlspecialchars($letter['body']) ?></textarea>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Section: Salary Info -->
                <div class="card__wrapper mb-20">
                    <div class="card__title-wrap mb-15">
                        <h6 class="card__heading-title">Salary Information <small class="text-muted">(For Increment Letter)</small></h6>
                    </div>

                    <div class="row g-20 gy-20">

                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="current_salary">Current Salary <span class="text-muted">Optional</span></label>
                                </div>
                                <div class="form__input">
                                    <input class="form-control" name="current_salary" id="current_salary" type="number" placeholder="e.g. 50000" value="<?= htmlspecialchars($letter['current_salary']) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="revised_salary">Revised Salary <span class="text-muted">Optional</span></label>
                                </div>
                                <div class="form__input">
                                    <input class="form-control" name="revised_salary" id="revised_salary" type="number" placeholder="e.g. 60000" value="<?= htmlspecialchars($letter['revised_salary']) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="new_designation">New Position <span class="text-muted">Optional</span></label>
                                </div>
                                <div class="form__input">
                                    <input class="form-control" name="new_designation" id="new_designation" type="text" placeholder="e.g. Senior Engineer" value="<?= htmlspecialchars($letter['new_designation']) ?>">
                                </div>
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
                                    <input class="form-control" name="signatory_name" id="signatory_name" type="text" placeholder="Full Name" value="<?= htmlspecialchars($letter['signatory_name']) ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="signatory_title">Designation / Title <span>*</span></label>
                                </div>
                                <div class="form__input">
                                    <input class="form-control" name="signatory_title" id="signatory_title" type="text" placeholder="e.g. Chief Executive Officer" value="<?= htmlspecialchars($letter['signatory_title']) ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="cc_email">CC Email <span class="text-muted">Optional</span></label>
                                </div>
                                <div class="form__input">
                                    <input class="form-control" name="cc_email" id="cc_email" type="email" placeholder="e.g. hr@company.com" value="<?= htmlspecialchars($letter['cc_email']) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
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

                        <div class="col-lg-6">
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

    // Use data attributes directly from the <option>
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

    document.addEventListener('DOMContentLoaded', function() {
        var letterTypeSelect = document.getElementById('letter_type');
        if (letterTypeSelect) {
            letterTypeSelect.addEventListener('change', loadTemplatesForType);
        }
    });

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

        if (!templateId) {
            return;
        }

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
</script>
