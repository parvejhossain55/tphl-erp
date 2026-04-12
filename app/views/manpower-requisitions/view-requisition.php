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
           
            <div class="section-head">Manpower Required By</div>
            <div class="form-body">

                <div class="field-row">
                    <div class="field-cell full">
                        <label class="field-label">Name</label>
                        <input type="text" class="form-control-plain" value="<?= esc($requisition['requested_by_name'] ?? 'N/A') ?>" readonly />
                    </div>
                    <div class="field-cell">
                        <label class="field-label">Designation</label>
                        <input type="text" class="form-control-plain" value="<?= ucwords(esc($requisition['designation_name'] ?? 'N/A')) ?>" readonly />
                    </div>
                </div>

                <div class="field-row">
                    <div class="field-cell full">
                        <label class="field-label">Department</label>
                        <input type="text" class="form-control-plain" value="<?= ucwords(esc($requisition['department_name'] ?? 'N/A')) ?>" readonly />
                    </div>
                    <div class="field-cell">
                        <label class="field-label">Submission Date</label>
                        <input type="text" class="form-control-plain" value="<?= formatDate($requisition['created_at']) ?>" readonly />
                    </div>
                </div>

            </div><!-- /form-body -->

            <!-- Section: Manpower Details -->
            <div class="section-head">Manpower Details</div>

            <div class="form-body">

                <div class="field-row">
                    <div class="field-cell full">
                        <label class="field-label">Job Title</label>
                        <input type="text" class="form-control-plain" value="<?= esc($requisition['job_title']) ?>" readonly />
                    </div>
                    <div class="field-cell">
                        <label class="field-label">Job Experience</label>
                        <input type="text" class="form-control-plain" value="<?= esc($requisition['job_experience']) ?>" readonly />
                    </div>
                </div>

                <div class="field-row">
                    <div class="field-cell full">
                        <label class="field-label">No. of Vacancies</label>
                        <input type="number" class="form-control-plain" value="<?= esc($requisition['no_of_vacancies']) ?>" readonly />
                    </div>
                    <div class="field-cell">
                        <label class="field-label">To Be Filled Before Date</label>
                        <input type="text" class="form-control-plain" value="<?= formatDate($requisition['to_be_filled_before']) ?>" readonly />
                    </div>
                </div>

                <div class="field-row">
                    <div class="field-cell full">
                        <label class="field-label">Age Limits</label>
                        <input type="text" class="form-control-plain" value="<?= esc($requisition['age_limit']) ?>" readonly />
                    </div>
                    <div class="field-cell">
                        <label class="field-label">Reports To</label>
                        <input type="text" class="form-control-plain" value="<?= esc($requisition['report_to_name'] ?? 'N/A') ?>" readonly />
                    </div>
                </div>

                <!-- Recruitment Type -->
                <div class="recruit-row">
                    <div class="recruit-label-col">
                        <label class="field-label">Recruitment Type</label>
                    </div>
                    <div class="recruit-checks">
                        <label class="recruit-option">
                            <input type="checkbox" disabled <?= strpos($requisition['requirement_type'], 'Project Based') !== false ? 'checked' : '' ?> />
                            <span>Project Based</span>
                        </label>
                        <label class="recruit-option">
                            <input type="checkbox" disabled <?= strpos($requisition['requirement_type'], 'Replacement') !== false ? 'checked' : '' ?> />
                            <span>Replacement</span>
                        </label>
                        <label class="recruit-option">
                            <input type="checkbox" disabled <?= strpos($requisition['requirement_type'], 'New Hiring') !== false ? 'checked' : '' ?> />
                            <span>New Hiring</span>
                        </label>
                    </div>
                </div>

                <div class="field-row">
                    <div class="field-cell" style="flex:1">
                        <label class="field-label">Project Name</label>
                        <input type="text" class="form-control-plain" value="<?= esc($requisition['project_name'] ?? 'N/A') ?>" readonly />
                    </div>
                </div>

                <?php if (!empty($requisition['replacement_for_name'])): ?>
                <div class="field-row">
                    <div class="field-cell" style="flex:1">
                        <label class="field-label">Replacement For</label>
                        <input type="text" class="form-control-plain" value="<?= esc($requisition['replacement_for_name']) ?>" readonly />
                    </div>
                </div>
                <?php endif; ?>

                <div class="textarea-cell">
                    <label class="field-label">Reason For Requisition</label>
                    <div class="form-control-plain p-2" style="min-height: 60px;"><?= nl2br(esc($requisition['reason'])) ?></div>
                </div>

            </div>

            <!-- Section: Job Description -->
            <div class="section-head">Job Description in Details</div>

            <div class="form-body" style="padding-top:0; padding-bottom:0;">
                <div id="jd-list" class="py-3">
                    <?php 
                    $jdItems = explode("\n", $requisition['job_description']);
                    foreach ($jdItems as $i => $item): 
                        if (empty(trim($item))) continue;
                    ?>
                        <div class="jd-row mb-2">
                            <div class="jd-num"><?= $i + 1 ?></div>
                            <div class="form-control-plain p-2 flex-grow-1 border-bottom"><?= esc($item) ?></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($jdItems) || (count($jdItems) === 1 && empty($jdItems[0]))): ?>
                        <p class="text-muted small">No job description provided.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Section: Qualifications -->
            <div class="section-head">Qualifications & Skills</div>

            <div class="form-body">
                <div class="textarea-cell" style="border-bottom:1px solid var(--border);">
                    <label class="field-label">Educational Qualification</label>
                    <div class="form-control-plain p-2"><?= nl2br(esc($requisition['education_qualification'])) ?></div>
                </div>

                <div class="textarea-cell" style="padding-top:12px;">
                    <label class="field-label">Special Knowledge / Skills</label>
                    <div class="form-control-plain p-2"><?= nl2br(esc($requisition['special_skills'])) ?></div>
                </div>
            </div>

            <!-- Approval Status -->
            <div class="approval-section">
                <div class="section-head" style="border-top:none;">Approval Status</div>
                <div class="form-body" style="padding-top: 15px;">
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge bg-<?= $requisition['status'] === 'approved' ? 'success' : ($requisition['status'] === 'rejected' ? 'danger' : 'warning') ?> p-2 px-3">
                            <?= ucfirst($requisition['status']) ?>
                        </span>
                        <?php if ($requisition['status'] === 'pending'): ?>
                            <p class="mb-0 small text-muted">Awaiting Management review.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="action-bar no-print">
                <a href="<?= $GLOBALS['base_url'] ?>/requisition-lists" class="btn btn-secondary">Back to List</a>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="fa-light fa-print me-1"></i> Print
                </button>
            </div>

        </div><!-- /notice-bar -->

    </div><!-- /card__wrapper -->

</div>