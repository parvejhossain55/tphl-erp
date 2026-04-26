<style>
@media print {

    /* পুরো page reset */
    body {
        margin: 0 !important;
        padding: 0 !important;
    }

    /* sidebar, navbar force remove */
    .app-sidebar,
    .sidebar,
    .navbar,
    .header,
    .app__header__area,
    .app-header,
    .action-bar {
        display: none !important;
    }

    /* Reset page wrapper margins/padding that leave empty space */
    .page__full-wrapper,
    .page__body-wrapper {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }

    /* Bootstrap grid remove */
    .row {
        margin: 0 !important;
        display: block !important;
    }

    [class*="col-"] {
        width: 100% !important;
        max-width: 100% !important;
        flex: unset !important;
    }

    /* wrapper full width */
    .card__wrapper {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
    }

    /* print button hide */
    .btn-print,
    .btn-reset {
        display: none !important;
    }
}
</style>
<div id="printArea">
    <div class="row">

        <div class="card__wrapper p-0 w-100">

            <!-- Header -->
            <div class="form-header">
                <h1 class="org-name">The Premium Homes Limited</h1>
                <p class="form-title">Manpower Requisition Form</p>
                <hr class="gold-rule" />
            </div>

            <!-- Notice -->
            <div class="notice-bar">
                <strong>Note:</strong> This MR has to be filled for all the positions vacant at any level. The MR must have the approval of the <strong>CEO/DCEO</strong> for the vacant position. Please complete the requisition thoroughly. The filled MR has to be submitted to HR department for further a <!-- Section: Manpower Required By -->
                <div class="section-head">Manpower Required By</div>
                <form action="<?= $GLOBALS['base_url'] ?>/store-requisition" method="POST">

                    <div class="form-body">

                        <div class="field-row">
                            <div class="field-cell full">
                                <label class="field-label">Name</label>
                                <input type="text" class="form-control-plain" value="<?= $_SESSION['user']['full_name'] ?>" readonly />
                            </div>
                            <div class="field-cell">
                                <label class="field-label">Designation</label>
                                <input type="text" class="form-control-plain" value="<?= ucwords($_SESSION['user']['designation_name'] ?? 'N/A') ?>" readonly />
                            </div>
                        </div>

                        <div class="field-row">
                            <div class="field-cell full">
                                <label class="field-label">Department</label>
                                <input type="text" class="form-control-plain" value="<?= ucwords($_SESSION['user']['department_name'] ?? 'N/A') ?>" readonly />
                            </div>
                            <div class="field-cell">
                                <label class="field-label">Submission Date</label>
                                <input type="date" class="form-control-plain" value="<?= date('Y-m-d') ?>" readonly />
                            </div>
                        </div>

                    </div><!-- /form-body -->

                    <!-- Section: Manpower Details -->
                    <div class="section-head">Manpower Details</div>

                    <div class="form-body">

                        <div class="field-row">
                            <div class="field-cell full">
                                <label class="field-label">Job Title</label>
                                <input type="text" name="job_title" class="form-control-plain" placeholder="e.g. Senior Sales Executive" required />
                            </div>
                            <div class="field-cell">
                                <label class="field-label">Job Experience</label>
                                <input type="text" name="job_experience" class="form-control-plain" placeholder="e.g. 3–5 years" />
                            </div>
                        </div>

                        <div class="field-row">
                            <div class="field-cell full">
                                <label class="field-label">No. of Vacancies</label>
                                <input type="number" name="no_of_vacancies" class="form-control-plain" placeholder="e.g. 2" min="1" required />
                            </div>
                            <div class="field-cell">
                                <label class="field-label">To Be Filled Before Date</label>
                                <input type="date" name="to_be_filled_before" class="form-control-plain" required />
                            </div>
                        </div>

                        <div class="field-row">
                            <div class="field-cell full">
                                <label class="field-label">Age Limits</label>
                                <input type="text" name="age_limit" class="form-control-plain" placeholder="e.g. 25–40 years" />
                            </div>
                            <div class="field-cell">
                                <label class="field-label">Reports To</label>
                                <select name="report_to" class="form-control-plain" required>
                                    <option value="">Select Manager</option>
                                    <?php foreach ($users as $u): ?>
                                        <option value="<?= $u['id'] ?>"><?= $u['full_name'] ?> (<?= $u['designation_name'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Recruitment Type -->
                        <div class="recruit-row">
                            <div class="recruit-label-col">
                                <label class="field-label">Recruitment Type</label>
                            </div>
                            <div class="recruit-checks">
                                <label class="recruit-option">
                                    <input type="checkbox" name="req_project" value="1" />
                                    <span>Project Based</span>
                                </label>
                                <label class="recruit-option">
                                    <input type="checkbox" name="req_replacement" id="req_replacement" value="1" onchange="toggleReplacementField()" />
                                    <span>Replacement</span>
                                </label>
                                <label class="recruit-option">
                                    <input type="checkbox" name="req_new" value="1" />
                                    <span>New Hiring</span>
                                </label>
                            </div>
                        </div>

                        <div class="field-row">
                            <div class="field-cell" style="flex:1">
                                <label class="field-label">Project Name</label>
                                <select name="project_id" class="form-control-plain">
                                    <option value="">Select Project (Optional)</option>
                                    <?php foreach ($projects as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= $p['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="field-row replacement-for-row" style="display:none;">
                            <div class="field-cell" style="flex:1">
                                <label class="field-label">Replacement For</label>
                                <select name="replacement_for_id" class="form-control-plain">
                                    <option value="">Select Employee</option>
                                    <?php foreach ($employees as $emp): ?>
                                        <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['full_name']) ?> (<?= htmlspecialchars($emp['designation_name'] ?? 'N/A') ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="textarea-cell">
                            <label class="field-label">Reason For New Hiring</label>
                            <textarea name="reason" class="form-control-plain textarea" placeholder="Describe the reason for this new hire…"></textarea>
                        </div>

                    </div>

                    <!-- Section: Job Description -->
                    <div class="section-head">Job Description in Details</div>

                    <div class="form-body" style="padding-top:0; padding-bottom:0;">
                        <div id="jd-list">
                            <!-- 7 rows by default -->
                            <?php for ($i = 1; $i <= 7; $i++): ?>
                                <div class="jd-row">
                                    <div class="jd-num"><?= $i ?></div>
                                    <input type="text" name="job_description[]" class="form-control-plain" placeholder="Responsibility or duty…" />
                                </div>
                            <?php endfor; ?>
                        </div>
                        <div style="padding: 10px 14px 16px; border-bottom: 1px solid var(--border);">
                            <button type="button" onclick="addJDRow()" style="background:none;border:1px dashed var(--border);color:var(--gold);font-size:0.78rem;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;padding:6px 18px;cursor:pointer;border-radius:2px;">+ Add Row</button>
                        </div>
                    </div>

                    <!-- Section: Qualifications -->
                    <div class="section-head">Qualifications & Skills</div>

                    <div class="form-body">
                        <div class="textarea-cell" style="border-bottom:1px solid var(--border);">
                            <label class="field-label">Educational Qualification</label>
                            <textarea name="education_qualification" class="form-control-plain textarea" placeholder="e.g. Bachelor's degree in Business Administration or related field…"></textarea>
                        </div>

                        <div class="textarea-cell" style="padding-top:12px;">
                            <label class="field-label">Special Knowledge / Skills</label>
                            <textarea name="special_skills" class="form-control-plain textarea" style="min-height:80px" placeholder="e.g. Proficiency in MS Office, AutoCAD, strong communication skills…"></textarea>
                        </div>
                    </div>

                    <!-- Approval Note -->
                    <div class="approval-section">
                        <div class="section-head" style="border-top:none;">Note on Approval</div>
                        <div class="form-body" style="padding-top: 15px;">
                            <p style="font-size: 0.85rem; color: var(--muted);">Upon submission, this requisition will be automatically sent to <strong>Management</strong> for approval. You can track the status in the Requisition List.</p>
                        </div>
                    </div>

                    <!-- Action Bar -->
                    <div class="action-bar">
                        <button type="button" class="btn-reset" onclick="resetForm()">Reset</button>
                        <button type="submit" class="btn-print" style="background: var(--gold); color: white;">Submit Requisition</button>
                        <button type="button" class="btn-print" onclick="window.print()">🖨 Print / Save PDF</button>
                    </div>

                </form>

            </div><!-- /form-wrapper -->

            <script>
                let jdCount = 7;

                function addJDRow() {
                    jdCount++;
                    const list = document.getElementById('jd-list');
                    const row = document.createElement('div');
                    row.className = 'jd-row';
                    row.innerHTML = `<div class="jd-num">${jdCount}</div><input type="text" name="job_description[]" class="form-control-plain" placeholder="Responsibility or duty…"/>`;
                    list.appendChild(row);
                }

                function resetForm() {
                    if (confirm('Reset all fields?')) {
                        document.querySelector('form').reset();
                    }
                }

                function toggleReplacementField() {
                    const checkbox = document.getElementById('req_replacement');
                    const row = document.querySelector('.replacement-for-row');
                    if (checkbox && checkbox.checked) {
                        row.style.display = 'flex';
                    } else {
                        row.style.display = 'none';
                        row.querySelector('select').value = '';
                    }
                }
            </script>
        </div>
    </div>