<div class="row">
    <div class="card__wrapper">
        <div class="col-xxl-12 col-xl-12 col-lg-12">

            <div class="card__title-wrap mb-20">
                <h5 class="card__heading-title">Generate Employee Letter</h5>
            </div>

            <form action="<?= $GLOBALS['base_url'] ?>/store-generate-letter" method="POST">

                <!-- Section: Employee Info -->
                <div class="card__wrapper mb-20">
                    <div class="card__title-wrap mb-15">
                        <h6 class="card__heading-title">Employee Information</h6>
                    </div>

                    <div class="row g-20 gy-20">

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="employee_select">Select Employee <span>*</span></label>
                                </div>
                                <div class="form__input">
                                    <select name="employee_id" id="employee_id" class="form-control" onchange="autoSelectDeptDesig()">
                                        <option value="">Select Employee</option>
                                        <?php foreach ($employees as $emp): ?>
                                            <option value="<?= $emp['id'] ?>">
                                                <?= $emp['full_name'] ?>
                                            </option>
                                        <?php endforeach; ?>
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
                                    <select class="form-control" name="letter_type" id="letter_type" required>
                                        <option value="">— Select Letter Type —</option>
                                        <option value="appointment">Appointment Letter</option>
                                        <option value="joining">Joining Letter</option>
                                        <option value="promotion">Promotion Letter</option>
                                        <option value="increment">Salary Increment Letter</option>
                                        <option value="experience">Experience Certificate</option>
                                        <option value="noc">No Objection Certificate (NOC)</option>
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
                                    <select class="form-control" name="designation_id" id="designation_id" required>
                                        <option value="">— Select Designation —</option>
                                        <?php foreach ($designations as $designation): ?>
                                            <option value="<?= $designation['id'] ?>"><?= $designation['designation_name'] ?></option>
                                        <?php endforeach ?>
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
                                    <select class="form-control" name="department_id" id="department_id" required>
                                        <option value="">— Select Department —</option>
                                        <?php foreach ($departments as $department): ?>
                                            <option value="<?= $department['id'] ?>"><?= $department['name'] ?></option>
                                        <?php endforeach ?>
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
                                    <input class="form-control" name="letter_date" id="letter_date" type="date" required>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="ref_number">Reference Number <span class="text-muted">Optional</span></label>
                                </div>
                                <div class="form__input">
                                    <input class="form-control" name="ref_number" id="ref_number" type="text" placeholder="e.g. HR/2024/001">
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="joining_date">Joining Date <span>*</span></label>
                                </div>
                                <div class="form__input">
                                    <input class="form-control" name="joining_date" id="joining_date" type="date" required>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="effective_date">Effective Date <span class="text-muted">Optional</span></label>
                                </div>
                                <div class="form__input">
                                    <input class="form-control" name="effective_date" id="effective_date" type="date">
                                </div>
                            </div>
                        </div>


                        <div class="col-lg-6">
                            <label>Subject *</label>
                            <input type="text" name="subject" class="form-control" required>
                        </div>

                        <div class="col-md-12">
                            <label>Letter Body *</label>
                            <textarea name="letter_body" class="form-control" required></textarea>
                        </div>

                        <div class="col-lg-6">
                            <label>Template</label>
                            <select name="template_id" class="form-control">
                                <option value="">Select Template</option>
                                <?php foreach ($templates as $t): ?>
                                    <option value="<?= $t['id'] ?>"><?= $t['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
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
                                    <input class="form-control" name="current_salary" id="current_salary" type="number" placeholder="e.g. 50000">
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="revised_salary">Revised Salary <span class="text-muted">Optional</span></label>
                                </div>
                                <div class="form__input">
                                    <input class="form-control" name="revised_salary" id="revised_salary" type="number" placeholder="e.g. 60000">
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="new_designation">New Position <span class="text-muted">Optional</span></label>
                                </div>
                                <div class="form__input">
                                    <input class="form-control" name="new_designation" id="new_designation" type="text" placeholder="e.g. Senior Engineer">
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
                                    <input class="form-control" name="signatory_name" id="signatory_name" type="text" placeholder="Full Name" required>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="signatory_title">Designation / Title <span>*</span></label>
                                </div>
                                <div class="form__input">
                                    <input class="form-control" name="signatory_title" id="signatory_title" type="text" placeholder="e.g. Chief Executive Officer" required>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="cc_email">CC Email <span class="text-muted">Optional</span></label>
                                </div>
                                <div class="form__input">
                                    <input class="form-control" name="cc_email" id="cc_email" type="email" placeholder="e.g. hr@company.com">
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
                                        <option value="0">No — Don't send email</option>
                                        <option value="1">Yes — Send via email</option>
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
                                        <option value="draft">Draft</option>
                                        <option value="issued">Issued</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="submit__btn text-center">
                    <button class="btn btn-secondary me-2" type="reset">Reset</button>
                    <button class="btn btn-primary" type="submit">Generate Letter</button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
    function autoSelectDeptDesig() {
        var empSelect = document.getElementById('employee_id');
        if (!empSelect || empSelect.selectedIndex === -1) return;

        var empId = empSelect.value;
        if (!empId) {
            var deptSelect = document.getElementById('department_id');
            var desigSelect = document.getElementById('designation_id');
            if (deptSelect) deptSelect.value = '';
            if (desigSelect) desigSelect.value = '';
            return;
        }

        // Fetch via AJAX
        fetch('<?= $GLOBALS['base_url'] ?>/get-employee-info-ajax?employee_id=' + empId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    var deptSelect = document.getElementById('department_id');
                    var desigSelect = document.getElementById('designation_id');

                    if (deptSelect && data.department_id) {
                        deptSelect.value = data.department_id;
                    }
                    if (desigSelect && data.designation_id) {
                        desigSelect.value = data.designation_id;
                    }
                }
            })
            .catch(error => console.error('Error fetching employee info:', error));
    }
</script>