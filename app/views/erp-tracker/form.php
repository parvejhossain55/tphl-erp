<?php
$page_title = "Department Tracking Form";
?>
<div class="container-fluid py-4">
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h2 class="mb-0">Department Tracking Form</h2>
            <p class="text-muted mb-0">Session: <?= htmlspecialchars($activeSession['topic']) ?> (<?= date('d M Y', strtotime($activeSession['meeting_date'])) ?>)</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?= $base_url ?>/erp-tracker" class="btn btn-outline-secondary">Back to Dashboard</a>
        </div>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form action="<?= $base_url ?>/erp-tracker/form/submit" method="POST" id="trackerForm">
        <input type="hidden" name="session_id" value="<?= $activeSession['id'] ?>">
        <input type="hidden" name="department_id" value="<?= $department_id ?>">

        <!-- Section 1: Basic Info -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-4">
                <h5 class="mb-0 text-primary">Section 1: Department Info</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Department</label>
                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($department_name) ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">ERP Champion (You)</label>
                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($_SESSION['user']['full_name']) ?>" readonly>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Action Items -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-4 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-primary">Section 2: Action Items</h5>
                <button type="button" class="btn btn-sm btn-outline-primary" id="addBtn"><i class="fas fa-plus"></i> Add New Task</button>
            </div>
            <div class="card-body">
                <!-- Carried Forward / Existing Items -->
                <?php if (!empty($actions)): ?>
                    <h6 class="text-muted mb-3">Previous / Current Tasks</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Action Name</th>
                                    <th>Owner</th>
                                    <th>Priority</th>
                                    <th>Deadline</th>
                                    <th>Status</th>
                                    <th>Pending Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($actions as $act): ?>
                                    <tr>
                                        <td>
                                            <input type="hidden" name="old_action_id[]" value="<?= $act['id'] ?>">
                                            <?= htmlspecialchars($act['action_name']) ?>
                                        </td>
                                        <td><?= htmlspecialchars($act['owner_name']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $act['priority'] == 'High' ? 'danger' : ($act['priority'] == 'Medium' ? 'warning' : 'info') ?>">
                                                <?= $act['priority'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="<?= $act['status'] == 'Overdue' ? 'text-danger fw-bold' : '' ?>">
                                                <?= date('d M Y', strtotime($act['deadline'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <select name="old_status[]" class="form-select status-select" required <?= $act['status'] == 'Completed' ? 'disabled' : '' ?>>
                                                <option value="Pending" <?= $act['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="Completed" <?= $act['status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                                <option value="Cancelled" <?= $act['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                <option value="Overdue" <?= $act['status'] == 'Overdue' ? 'selected' : '' ?> disabled>Overdue</option>
                                            </select>
                                            <?php if ($act['status'] == 'Completed'): ?>
                                                <input type="hidden" name="old_status[]" value="Completed">
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <input type="text" name="old_reason[]" class="form-control reason-input" value="<?= htmlspecialchars($act['pending_reason'] ?? '') ?>" <?= $act['status'] == 'Pending' || $act['status'] == 'Overdue' ? 'required' : 'readonly' ?>>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <!-- New Items -->
                <h6 class="text-muted mb-3">Add New Tasks</h6>
                <div class="table-responsive">
                    <table class="table table-bordered" id="newTasksTable">
                        <thead class="table-light">
                            <tr>
                                <th>Action Name</th>
                                <th>Owner</th>
                                <th>Priority</th>
                                <th>Deadline</th>
                                <th>Remove</th>
                            </tr>
                        </thead>
                        <tbody id="newTasksBody">
                            <!-- JS will append rows here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Section 3: Paperless Goal -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-4">
                <h5 class="mb-0 text-primary">Section 3: Paperless Goal</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Current Paper-based Process</label>
                        <textarea name="paperless_current" class="form-control" rows="3"><?= htmlspecialchars($submission['paperless_current'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Target ERP-based Process</label>
                        <textarea name="paperless_target" class="form-control" rows="3"><?= htmlspecialchars($submission['paperless_target'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Target Deadline</label>
                        <input type="date" name="paperless_deadline" class="form-control" value="<?= $submission['paperless_deadline'] ?? '' ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 4: Problems / Issues -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-4">
                <h5 class="mb-0 text-primary">Section 4: Problems & Issues</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Issue Description</label>
                        <textarea name="issues_desc" class="form-control" rows="3"><?= htmlspecialchars($submission['issues_desc'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Priority</label>
                        <select name="issues_priority" class="form-select">
                            <option value="Low" <?= ($submission['issues_priority'] ?? '') == 'Low' ? 'selected' : '' ?>>Low</option>
                            <option value="Medium" <?= ($submission['issues_priority'] ?? '') == 'Medium' ? 'selected' : '' ?>>Medium</option>
                            <option value="High" <?= ($submission['issues_priority'] ?? '') == 'High' ? 'selected' : '' ?>>High</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Section 5: IT Support -->
            <div class="col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 pt-4">
                        <h5 class="mb-0 text-primary">Section 5: IT Support</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="itSupportToggle" name="it_support_req" value="1" <?= !empty($submission['it_support_req']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="itSupportToggle">Need IT Support?</label>
                        </div>
                        <div id="itSupportDescWrapper" class="<?= empty($submission['it_support_req']) ? 'd-none' : '' ?>">
                            <label class="form-label">What kind of support is needed?</label>
                            <textarea name="it_support_desc" class="form-control" rows="3"><?= htmlspecialchars($submission['it_support_desc'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 6: Feature Requests -->
            <div class="col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 pt-4">
                        <h5 class="mb-0 text-primary">Section 6: New Feature Request</h5>
                    </div>
                    <div class="card-body">
                        <label class="form-label">Describe the feature</label>
                        <textarea name="feature_req" class="form-control" rows="4"><?= htmlspecialchars($submission['feature_req'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-end mb-5">
            <button type="submit" class="btn btn-success btn-lg px-5">Submit Department Tracker</button>
        </div>
    </form>
</div>

<script>
function initErpTrackerForm() {
    if (typeof $ === 'undefined') {
        setTimeout(initErpTrackerForm, 50);
        return;
    }

    $(document).ready(function() {
        // Dynamic Task Adding
        let userOptions = "";
        <?php foreach($allUsers as $u): ?>
            <?php $name = addslashes($u["full_name"]); ?>
            userOptions += '<option value="<?= $u['id'] ?>"><?= $name ?></option>';
        <?php endforeach; ?>
        
        $("#addBtn").click(function() {
            let rowHTML = `
                <tr>
                    <td><input type="text" name="new_action_name[]" class="form-control" required></td>
                    <td>
                        <select name="new_owner_id[]" class="form-select select2" required>
                            <option value="">Select Owner</option>
                            ${userOptions}
                        </select>
                    </td>
                    <td>
                        <select name="new_priority[]" class="form-select">
                            <option value="Medium">Medium</option>
                            <option value="High">High</option>
                            <option value="Low">Low</option>
                        </select>
                    </td>
                    <td><input type="date" name="new_deadline[]" class="form-control" required></td>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger removeRowBtn"><i class="fas fa-trash"></i></button></td>
                </tr>
            `;
            let $row = $(rowHTML);
            $row.find(".removeRowBtn").click(function() {
                $(this).closest("tr").remove();
            });
            $("#newTasksBody").append($row);
            
            if($.fn.select2) {
                $row.find(".select2").select2({ theme: "bootstrap-5" });
            }
        });

        // Reason validation logic
        $(".status-select").change(function() {
            let reasonInput = $(this).closest("tr").find(".reason-input");
            if ($(this).val() === "Pending" || $(this).val() === "Overdue") {
                reasonInput.prop("readonly", false);
                reasonInput.prop("required", true);
            } else {
                reasonInput.prop("readonly", true);
                reasonInput.prop("required", false);
                reasonInput.val("");
            }
        });

        // IT Support Toggle
        $("#itSupportToggle").change(function() {
            if ($(this).is(":checked")) {
                $("#itSupportDescWrapper").removeClass("d-none");
                $("textarea[name='it_support_desc']").prop("required", true);
            } else {
                $("#itSupportDescWrapper").addClass("d-none");
                $("textarea[name='it_support_desc']").prop("required", false);
            }
        });
    });
}
initErpTrackerForm();
</script>
