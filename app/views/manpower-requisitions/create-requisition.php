<style>
    :root {
        --gold: #b8972a;
        --gold-light: #d4af37;
        --dark: #1a1a2e;
        --mid: #2d2d44;
        --surface: #f8f6f1;
        --border: #cfc9b8;
        --text: #2c2c2c;
        --muted: #6b6b6b;
        --accent-bg: #fdfaf3;
    }



    .form-wrapper {
        max-width: 860px;
        margin: 0 auto;
        background: #fff;
        border: 1px solid var(--border);
        box-shadow: 0 8px 40px rgba(0, 0, 0, 0.12), 0 2px 8px rgba(184, 151, 42, 0.08);
    }

    /* ── Header ── */
    .form-header {
        background: #094429;
        padding: 28px 40px 22px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .form-header::before {
        content: '';
        position: absolute;
        inset: 0;
        background: repeating-linear-gradient(45deg,
                transparent,
                transparent 18px,
                rgba(184, 151, 42, 0.06) 18px,
                rgba(184, 151, 42, 0.06) 19px);
    }

    .form-header .org-name {
        font-family: 'Playfair Display', serif;
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--gold-light);
        letter-spacing: 0.04em;
        margin: 0;
        position: relative;
    }

    .form-header .form-title {
        font-family: 'Source Sans 3', sans-serif;
        font-size: 0.95rem;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.75);
        letter-spacing: 0.18em;
        text-transform: uppercase;
        margin: 6px 0 0;
        position: relative;
    }

    .gold-rule {
        border: none;
        height: 2px;
        background: linear-gradient(90deg, transparent, var(--gold-light), transparent);
        margin: 12px auto 0;
        width: 60%;
        position: relative;
    }

    /* ── Notice ── */
    .notice-bar {
        background: var(--accent-bg);
        border-bottom: 1px solid var(--border);
        padding: 12px 32px;
        font-size: 0.78rem;
        color: var(--muted);
        line-height: 1.6;
    }

    .notice-bar strong {
        color: var(--text);
    }

    /* ── Section Header ── */
    .section-head {
        background: var(--dark);
        color: var(--gold-light);
        font-family: 'Playfair Display', serif;
        font-size: 0.82rem;
        font-weight: 600;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        padding: 7px 32px;
        border-top: 1px solid #3a3a55;
    }

    /* ── Form Body ── */
    .form-body {
        padding: 0 32px 32px;
    }

    .field-row {
        display: flex;
        gap: 0;
        border-bottom: 1px solid var(--border);
    }

    .field-row:last-child {
        border-bottom: none;
    }

    .field-cell {
        flex: 1;
        padding: 10px 14px 6px;
        border-right: 1px solid var(--border);
        min-height: 52px;
    }

    .field-cell:last-child {
        border-right: none;
    }

    .field-cell.full {
        flex: 2;
    }

    .field-cell.wide {
        flex: 3;
    }

    label.field-label {
        display: block;
        font-size: 0.72rem;
        font-weight: 600;
        color: var(--muted);
        letter-spacing: 0.08em;
        text-transform: uppercase;
        margin-bottom: 3px;
    }

    .form-control-plain {
        display: block;
        width: 100%;
        border: none;
        border-bottom: 1px solid transparent;
        background: transparent;
        font-family: 'Source Sans 3', sans-serif;
        font-size: 0.92rem;
        color: var(--text);
        padding: 2px 0 4px;
        outline: none;
        transition: border-color 0.2s;
    }

    .form-control-plain:focus {
        border-bottom-color: var(--gold);
    }

    .form-control-plain::placeholder {
        color: #ccc;
    }

    /* ── Recruitment Type ── */
    .recruit-row {
        display: flex;
        align-items: stretch;
        border-bottom: 1px solid var(--border);
    }

    .recruit-label-col {
        padding: 10px 14px 6px;
        border-right: 1px solid var(--border);
        min-width: 160px;
        display: flex;
        align-items: center;
    }

    .recruit-label-col label.field-label {
        margin: 0;
    }

    .recruit-checks {
        display: flex;
        flex: 1;
        align-items: center;
        gap: 0;
    }

    .recruit-option {
        flex: 1;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 20px;
        border-right: 1px solid var(--border);
        cursor: pointer;
        transition: background 0.15s;
    }

    .recruit-option:last-child {
        border-right: none;
    }

    .recruit-option:hover {
        background: var(--accent-bg);
    }

    .recruit-option input[type="checkbox"] {
        appearance: none;
        width: 18px;
        height: 18px;
        border: 2px solid var(--border);
        border-radius: 3px;
        cursor: pointer;
        position: relative;
        flex-shrink: 0;
        transition: border-color 0.2s, background 0.2s;
    }

    .recruit-option input[type="checkbox"]:checked {
        background: var(--gold);
        border-color: var(--gold);
    }

    .recruit-option input[type="checkbox"]:checked::after {
        content: '✓';
        position: absolute;
        color: #fff;
        font-size: 12px;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }

    .recruit-option span {
        font-size: 0.88rem;
        font-weight: 600;
        color: var(--text);
        letter-spacing: 0.02em;
    }

    /* ── Job Description numbered rows ── */
    .jd-row {
        display: flex;
        align-items: center;
        border-bottom: 1px solid var(--border);
        min-height: 46px;
    }

    .jd-num {
        width: 40px;
        text-align: center;
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--gold);
        flex-shrink: 0;
        border-right: 1px solid var(--border);
        align-self: stretch;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .jd-row .form-control-plain {
        padding: 10px 14px;
    }

    /* ── Spacer field ── */
    .textarea-cell {
        padding: 10px 14px 6px;
        border-bottom: 1px solid var(--border);
    }

    .form-control-plain.textarea {
        resize: none;
        min-height: 68px;
    }

    /* ── Approval / Signature ── */
    .approval-section {
        border-top: 2px solid var(--gold);
        margin-top: 28px;
    }

    .approval-row {
        display: flex;
        gap: 0;
        border-bottom: 1px solid var(--border);
    }

    .approval-cell {
        flex: 1;
        padding: 14px 18px;
        border-right: 1px solid var(--border);
    }

    .approval-cell:last-child {
        border-right: none;
    }

    .approval-cell .field-label {
        margin-bottom: 6px;
    }

    .sig-line {
        border-bottom: 1px solid var(--border);
        min-height: 40px;
        margin-top: 8px;
    }

    /* ── Print button ── */
    .btn-print {
        background: var(--dark);
        color: var(--gold-light);
        border: 1px solid var(--gold);
        font-family: 'Source Sans 3', sans-serif;
        font-size: 0.82rem;
        font-weight: 600;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        padding: 10px 32px;
        cursor: pointer;
        transition: background 0.2s, color 0.2s;
    }

    .btn-print:hover {
        background: var(--gold);
        color: var(--dark);
    }

    .btn-reset {
        background: transparent;
        color: var(--muted);
        border: 1px solid var(--border);
        font-family: 'Source Sans 3', sans-serif;
        font-size: 0.82rem;
        font-weight: 600;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        padding: 10px 24px;
        cursor: pointer;
        transition: border-color 0.2s, color 0.2s;
    }

    .btn-reset:hover {
        border-color: #999;
        color: var(--text);
    }

    .action-bar {
        padding: 24px 32px;
        background: var(--accent-bg);
        border-top: 1px solid var(--border);
        display: flex;
        gap: 12px;
        justify-content: flex-end;
    }

    @media print {
        body {
            background: #fff;
            padding: 0;
        }

        .form-wrapper {
            box-shadow: none;
            border: none;
        }

        .action-bar {
            display: none;
        }
    }

    @media (max-width: 600px) {
        .form-body {
            padding: 0 12px 24px;
        }

        .form-header {
            padding: 20px 16px 16px;
        }

        .form-header .org-name {
            font-size: 1.3rem;
        }

        .field-row {
            flex-direction: column;
        }

        .field-cell {
            border-right: none;
        }

        .recruit-checks {
            flex-direction: column;
        }

        .recruit-option {
            border-right: none;
            border-bottom: 1px solid var(--border);
        }

        .approval-row {
            flex-direction: column;
        }

        .approval-cell {
            border-right: none;
        }
    }
</style>

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
            <strong>Note:</strong> This MR has to be filled for all the positions vacant at any level. The MR must have the approval of the <strong>CEO/DCEO</strong> for the vacant position. Please complete the requisition thoroughly. The filled MR has to be submitted to HR department for further a        <!-- Section: Manpower Required By -->
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
                            <input type="checkbox" name="req_replacement" value="1" />
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

                <div class="field-row">
                    <div class="field-cell" style="flex:1">
                        <label class="field-label">Replacement For</label>
                        <input type="text" name="replacement_for" class="form-control-plain" placeholder="Name of person being replaced" />
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
    </script>
</div>

                    else el.value = '';
                });
            }
        }
    </script>