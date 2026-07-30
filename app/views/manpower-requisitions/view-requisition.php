<style>
@media print {
    /* Hide non-printable layout elements */
    .app-sidebar,
    .sidebar,
    .navbar,
    .header,
    .app__header__area,
    .app-header,
    .action-bar,
    .no-print {
        display: none !important;
    }

    /* Reset page wrapper margins/padding that leave empty space */
    body, html,
    .page__full-wrapper,
    .page__body-wrapper,
    .main-content,
    .content-wrapper,
    .app-content {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        background-color: #fff !important;
    }
}
</style>
<?php
$roleId   = (int)($_SESSION['user']['role_id'] ?? 0);
$roleName = strtolower($_SESSION['user']['role_name'] ?? '');
$isManagement = in_array($roleId, [1, 3]) || hasPermission("Manpower Requisition");
$isHr = in_array($roleId, [1, 3, 4]) 
        || hasPermission("HRM Management") 
        || hasPermission("Hr Manage")
        || str_contains($roleName, 'hr');

$hrStatus = $requisition['hr_status'] ?? 'pending';
?>

<div class="row">

    <div class="card__wrapper form-wrapper p-0 w-100 shadow-sm border-0 rounded-3">

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
                        <input type="text" class="form-control-plain fw-semibold" value="<?= esc($requisition['requested_by_name'] ?? 'N/A') ?>" readonly />
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
                        <input type="text" class="form-control-plain text-primary fw-bold" value="<?= esc($requisition['job_title']) ?>" readonly />
                    </div>
                    <div class="field-cell">
                        <label class="field-label">Job Experience</label>
                        <input type="text" class="form-control-plain" value="<?= esc($requisition['job_experience']) ?>" readonly />
                    </div>
                </div>

                <div class="field-row">
                    <div class="field-cell full">
                        <label class="field-label">No. of Vacancies</label>
                        <input type="number" class="form-control-plain fw-bold" value="<?= esc($requisition['no_of_vacancies']) ?>" readonly />
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

            <!-- 1. MANAGEMENT APPROVAL STATUS & SWEETALERT ACTIONS -->
            <div class="approval-section my-4 px-3 no-print">
                <div class="section-head border-top-0 rounded-top" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #fbbf24; padding: 12px 18px; font-weight: 700; font-size: 0.85rem; letter-spacing: 0.08em; text-transform: uppercase;">
                    <i class="fa-solid fa-shield-check me-2"></i> 1. Management Approval Status
                </div>
                
                <div class="form-body bg-white border border-top-0 rounded-bottom p-4 shadow-sm">
                    <?php if ($requisition['status'] === 'pending'): ?>
                        <div class="p-3 rounded-3 mb-0 border border-warning" style="background: linear-gradient(135deg, #fffdf5 0%, #fff8e6 100%);">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div>
                                    <span class="badge bg-warning text-dark px-3 py-2 fw-bold rounded-pill shadow-sm" style="font-size: 0.88rem;">
                                        <i class="fa-solid fa-hourglass-half fa-spin-pulse me-2"></i> PENDING MANAGEMENT APPROVAL
                                    </span>
                                    <p class="mb-0 mt-2 text-dark small">
                                        Submitted by <strong><?= esc($requisition['requested_by_name'] ?? 'N/A') ?></strong> on <strong><?= formatDate($requisition['created_at']) ?></strong>. Currently awaiting review and decision by <strong>Management (CEO/DCEO)</strong>.
                                    </p>
                                </div>

                                <?php if ($isManagement): ?>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <!-- SweetAlert Approve Trigger Button -->
                                        <button type="button" class="btn btn-success px-4 py-2 fw-semibold shadow-sm" onclick="triggerApproveSwal()" style="background: linear-gradient(135deg, #10b981, #059669); border:none;">
                                            <i class="fa-solid fa-circle-check me-1"></i> Approve Requisition
                                        </button>
                                        <!-- SweetAlert Reject Trigger Button -->
                                        <button type="button" class="btn btn-outline-danger px-4 py-2 fw-semibold" onclick="triggerRejectSwal()">
                                            <i class="fa-solid fa-circle-xmark me-1"></i> Reject
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php elseif ($requisition['status'] === 'approved'): ?>
                        <div class="p-3 rounded-3 mb-0 border border-success" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div>
                                    <span class="badge bg-success px-3 py-2 fw-bold rounded-pill shadow-sm" style="font-size: 0.88rem;">
                                        <i class="fa-solid fa-circle-check me-2"></i> MANAGEMENT APPROVED
                                    </span>
                                    <p class="mb-0 mt-2 text-dark small">
                                        Approved by <strong><?= esc($requisition['approved_by_name'] ?? 'Management') ?></strong>
                                        <?php if (!empty($requisition['approved_at'])): ?>
                                            on <strong><?= date('d M Y, h:i A', strtotime($requisition['approved_at'])) ?></strong>
                                        <?php endif; ?>
                                    </p>
                                    <?php if (!empty($requisition['approval_note'])): ?>
                                        <small class="text-muted d-block mt-1"><em>Note: <?= esc($requisition['approval_note']) ?></em></small>
                                    <?php endif; ?>
                                </div>
                                <div class="text-success fs-1 opacity-25 me-2">
                                    <i class="fa-solid fa-stamp"></i>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($requisition['status'] === 'rejected'): ?>
                        <div class="p-3 rounded-3 mb-0 border border-danger" style="background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div>
                                    <span class="badge bg-danger px-3 py-2 fw-bold rounded-pill shadow-sm" style="font-size: 0.88rem;">
                                        <i class="fa-solid fa-circle-xmark me-2"></i> REQUISITION REJECTED
                                    </span>
                                    <p class="mb-0 mt-2 text-dark small">
                                        Rejected by <strong><?= esc($requisition['approved_by_name'] ?? 'Management') ?></strong>
                                        <?php if (!empty($requisition['approved_at'])): ?>
                                            on <strong><?= date('d M Y, h:i A', strtotime($requisition['approved_at'])) ?></strong>
                                        <?php endif; ?>
                                    </p>
                                    <?php if (!empty($requisition['approval_note'])): ?>
                                        <div class="mt-2 p-2 bg-white rounded border border-danger-subtle text-danger small">
                                            <strong>Reason for Rejection:</strong> <?= esc($requisition['approval_note']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 2. HR RECRUITMENT PROCESSING STATUS SECTION -->
            <?php if ($requisition['status'] === 'approved'): ?>
            <div class="approval-section my-4 px-3 no-print">
                <div class="section-head border-top-0 rounded-top" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; padding: 12px 18px; font-weight: 700; font-size: 0.85rem; letter-spacing: 0.08em; text-transform: uppercase;">
                    <i class="fa-solid fa-user-gear me-2"></i> 2. HR Recruitment & Hiring Status
                </div>
                
                <div class="form-body bg-white border border-top-0 rounded-bottom p-4 shadow-sm">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <?php if ($hrStatus === 'hiring_completed'): ?>
                                <span class="badge bg-success px-3 py-2 fw-bold rounded-pill shadow-sm" style="font-size: 0.88rem;">
                                    <i class="fa-solid fa-user-check me-2"></i> HIRING COMPLETED
                                </span>
                            <?php elseif ($hrStatus === 'not_needed_now'): ?>
                                <span class="badge bg-secondary px-3 py-2 fw-bold rounded-pill shadow-sm" style="font-size: 0.88rem;">
                                    <i class="fa-solid fa-circle-pause me-2"></i> NOT NEEDED NOW / CANCELLED
                                </span>
                            <?php else: ?>
                                <span class="badge bg-info text-dark px-3 py-2 fw-bold rounded-pill shadow-sm" style="font-size: 0.88rem; background-color: #38bdf8 !important;">
                                    <i class="fa-solid fa-spinner fa-spin me-2"></i> ON PROCESSING / HIRING IN PROGRESS
                                </span>
                            <?php endif; ?>

                            <div class="mt-2 text-dark small">
                                <?php if (!empty($requisition['hr_updated_by_name'])): ?>
                                    <span>Last updated by HR: <strong><?= esc($requisition['hr_updated_by_name']) ?></strong></span>
                                <?php endif; ?>
                                <?php if (!empty($requisition['hr_updated_at'])): ?>
                                    <span class="ms-2">on <strong><?= date('d M Y, h:i A', strtotime($requisition['hr_updated_at'])) ?></strong></span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($requisition['hr_remark'])): ?>
                                <div class="mt-2 p-3 rounded border border-info-subtle bg-light small text-dark">
                                    <strong><i class="fa-solid fa-comment-dots me-1 text-info"></i> HR Remark / Note:</strong>
                                    <div class="mt-1"><?= nl2br(esc($requisition['hr_remark'])) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($isHr): ?>
                            <div>
                                <!-- SweetAlert HR Status Trigger Button -->
                                <button type="button" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm" onclick="triggerHrUpdateSwal()" style="background: linear-gradient(135deg, #0284c7, #0369a1); border:none;">
                                    <i class="fa-solid fa-pen-to-square me-1"></i> Update Recruitment Status
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Action Bar -->
            <div class="action-bar no-print d-flex justify-content-between align-items-center p-3 bg-light border-top">
                <a href="<?= $GLOBALS['base_url'] ?>/requisition-lists" class="btn btn-secondary px-4">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to List
                </a>
                <button type="button" class="btn btn-primary px-4" onclick="window.print()" style="background: var(--gold); border-color: var(--gold);">
                    <i class="fa-light fa-print me-1"></i> Print / Save PDF
                </button>
            </div>

        </div><!-- /notice-bar -->

    </div><!-- /card__wrapper -->

</div>

<!-- Include SweetAlert2 Library -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// SweetAlert2 for Management Approval
function triggerApproveSwal() {
    Swal.fire({
        title: 'Approve Manpower Requisition?',
        text: 'Are you sure you want to approve this manpower requisition for <?= esc($requisition['job_title']) ?>?',
        icon: 'question',
        input: 'textarea',
        inputPlaceholder: 'Enter optional approval note / instructions for HR...',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-check me-1"></i> Yes, Approve!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= $GLOBALS['base_url'] ?>/approve-requisition/<?= $requisition['id'] ?>';
            
            const noteInput = document.createElement('input');
            noteInput.type = 'hidden';
            noteInput.name = 'approval_note';
            noteInput.value = result.value || 'Approved by Management';
            form.appendChild(noteInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// SweetAlert2 for Management Rejection
function triggerRejectSwal() {
    Swal.fire({
        title: 'Reject Manpower Requisition?',
        text: 'Please specify the reason for rejecting this requisition:',
        icon: 'warning',
        input: 'textarea',
        inputPlaceholder: 'Type reason for rejection here...',
        inputValidator: (value) => {
            if (!value || !value.trim()) {
                return 'You must enter a reason for rejection!';
            }
        },
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-ban me-1"></i> Yes, Reject',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= $GLOBALS['base_url'] ?>/reject-requisition/<?= $requisition['id'] ?>';
            
            const reasonInput = document.createElement('input');
            reasonInput.type = 'hidden';
            reasonInput.name = 'reject_reason';
            reasonInput.value = result.value;
            form.appendChild(reasonInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// SweetAlert2 for HR Recruitment Status Update
function triggerHrUpdateSwal() {
    const currentHrStatus = <?= json_encode($hrStatus) ?>;
    const currentHrRemark = <?= json_encode($requisition['hr_remark'] ?? '') ?>;

    Swal.fire({
        title: 'Update HR Recruitment Status',
        html: `
            <div class="text-start mb-3">
                <label class="form-label fw-bold text-muted small mb-1">Recruitment Status <span class="text-danger">*</span></label>
                <select id="swal_hr_status" class="form-select mb-3">
                    <option value="on_processing" ${currentHrStatus === 'on_processing' || currentHrStatus === 'pending' ? 'selected' : ''}>
                        ⏳ On Processing / Hiring In Progress
                    </option>
                    <option value="hiring_completed" ${currentHrStatus === 'hiring_completed' ? 'selected' : ''}>
                        ✅ Hiring Completed (Position Filled)
                    </option>
                    <option value="not_needed_now" ${currentHrStatus === 'not_needed_now' ? 'selected' : ''}>
                        ⏸️ Not Needed Now (Cancelled / Postponed)
                    </option>
                </select>
                <label class="form-label fw-bold text-muted small mb-1">HR Remark / Progress Note</label>
                <textarea id="swal_hr_remark" class="form-control" rows="3" placeholder="Describe current progress, candidate details, or onboarding updates...">${currentHrRemark}</textarea>
            </div>
        `,
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#0284c7',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-floppy-disk me-1"></i> Save Status Update',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const hrStatusVal = document.getElementById('swal_hr_status').value;
            const hrRemarkVal = document.getElementById('swal_hr_remark').value;
            return { hr_status: hrStatusVal, hr_remark: hrRemarkVal };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= $GLOBALS['base_url'] ?>/update-hr-requisition-status/<?= $requisition['id'] ?>';
            
            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'hr_status';
            statusInput.value = result.value.hr_status;
            form.appendChild(statusInput);

            const remarkInput = document.createElement('input');
            remarkInput.type = 'hidden';
            remarkInput.name = 'hr_remark';
            remarkInput.value = result.value.hr_remark;
            form.appendChild(remarkInput);

            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>