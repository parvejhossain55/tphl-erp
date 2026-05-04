<?php
$base_url = $GLOBALS['base_url'];
?>
<div class="mb-4 pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4><?= htmlspecialchars($page_title ?? 'Create QC Inspection') ?></h4>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $base_url ?>/qc-dashboard" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>
</div>

<section class="section">
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    <div class="card__wrapper shadow-sm">
        <div class="card-header bg-white border-bottom pb-3 mb-3">
            <h5 class="card-title m-2">Inspection Details</h5>
        </div>
        <form action="<?= $base_url ?>/qc-inspection-store" method="POST" enctype="multipart/form-data">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Project <span class="text-danger">*</span></label>
                        <select name="project_id" class="form-select" required>
                            <option value="">-- Select Project --</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?= $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Work Type <span class="text-danger">*</span></label>
                        <select name="work_type_id" id="work_type_id" class="form-select" required>
                            <option value="">-- Select Work Type --</option>
                            <?php foreach ($workTypes as $wt): ?>
                                <option value="<?= $wt['id'] ?>"><?= htmlspecialchars($wt['work_type_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Inspection Date <span class="text-danger">*</span></label>
                        <input type="date" name="inspection_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>

                <!-- Dynamic Checklist Items Section -->
                <div class="row mt-4">
                    <div class="col-12">
                        <h5 class="fw-bold mb-3">Checklist Items</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="checklist_table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Checklist Item</th>
                                        <th>Pass/Fail</th>
                                        <th>Observation Note</th>
                                        <th>Attachment (Optional)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Select a Work Type to load checklist items.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Overall Status <span class="text-danger">*</span></label>
                        <select name="overall_status" class="form-select" required>
                            <option value="passed">Passed</option>
                            <option value="failed">Failed</option>
                            <option value="rework_needed">Rework Needed</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Overall Attachment (Optional)</label>
                        <input type="file" name="overall_attachment" class="form-control" accept="image/*,.pdf,.doc,.docx">
                        <small class="text-muted">Master approval copy, site photo, etc.</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Overall Comments</label>
                        <textarea name="comments" class="form-control" rows="2" placeholder="Any overall remarks..."></textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Save Inspection
                </button>
            </div>
        </form>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const workTypeSelect = document.getElementById('work_type_id');
    const checklistTbody = document.querySelector('#checklist_table tbody');

    workTypeSelect.addEventListener('change', function() {
        const workTypeId = this.value;
        if (!workTypeId) {
            checklistTbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Select a Work Type to load checklist items.</td></tr>';
            return;
        }

        checklistTbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Loading...</td></tr>';

        fetch(`<?= $base_url ?>/qc-ajax-checklists?work_type_id=${workTypeId}`)
            .then(response => response.json())
            .then(data => {
                checklistTbody.innerHTML = '';
                if (data.checklists && data.checklists.length > 0) {
                    data.checklists.forEach((item, index) => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>
                                <input type="hidden" name="qc_checklist_id[]" value="${item.id}">
                                ${item.checklist_item_name}
                            </td>
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_passed[${index}]" id="pass_chk_${index}" value="1" checked>
                                    <label class="form-check-label" for="pass_chk_${index}">Passed</label>
                                </div>
                            </td>
                            <td>
                                <input type="text" name="observation_note[]" class="form-control form-control-sm" placeholder="Optional notes...">
                            </td>
                            <td>
                                <input type="file" name="item_attachment[${index}]" class="form-control form-control-sm" accept="image/*,.pdf">
                            </td>
                        `;
                        checklistTbody.appendChild(tr);
                    });
                } else {
                    checklistTbody.innerHTML = '<tr><td colspan="4" class="text-center text-warning">No checklist items found for this work type.</td></tr>';
                }
            })
            .catch(error => {
                console.error('Error fetching checklists:', error);
                checklistTbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Error loading checklist items.</td></tr>';
            });
    });
});
</script>
