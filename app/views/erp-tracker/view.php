<?php
// Ensure variables exist
$submission = $submission ?? null;
$actions = $actions ?? [];
$department_name = $department_name ?? 'Unknown Department';
$activeSession = $activeSession ?? null;
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 text-dark"><i class="fas fa-eye text-primary me-2"></i><?= htmlspecialchars($department_name) ?> Submission Details</h4>
        <a href="<?= $base_url ?>/erp-tracker" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to Dashboard</a>
    </div>

        <?php if (!$submission && empty($actions)): ?>
            <div class="alert alert-warning">
                This department has not submitted anything yet for the active session.
            </div>
        <?php else: ?>
            
            <!-- Section 2: Action Items -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-4">
                    <h5 class="mb-0 text-primary">Section 2: Action Items</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($actions)): ?>
                        <p class="text-muted">No action items created yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
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
                                            <td><?= htmlspecialchars($act['action_name']) ?></td>
                                            <td>
                                                <?= htmlspecialchars($act['owner_name'] ?? 'Unknown') ?>
                                                <br><small class="text-muted"><?= htmlspecialchars($act['owner_email'] ?? '') ?></small>
                                            </td>
                                            <td>
                                                <?php 
                                                $badgeClass = 'bg-secondary';
                                                if ($act['priority'] == 'High') $badgeClass = 'bg-danger';
                                                elseif ($act['priority'] == 'Medium') $badgeClass = 'bg-warning text-dark';
                                                elseif ($act['priority'] == 'Low') $badgeClass = 'bg-info text-dark';
                                                ?>
                                                <span class="badge <?= $badgeClass ?>"><?= $act['priority'] ?></span>
                                            </td>
                                            <td>
                                                <?= date('d M Y', strtotime($act['deadline'])) ?>
                                                <?php if (strtotime($act['deadline']) < time() && $act['status'] != 'Completed'): ?>
                                                    <span class="text-danger ms-1"><i class="fas fa-exclamation-circle"></i></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $statusClass = 'bg-secondary';
                                                if ($act['status'] == 'Completed') $statusClass = 'bg-success';
                                                elseif ($act['status'] == 'Pending') $statusClass = 'bg-warning text-dark';
                                                elseif ($act['status'] == 'Overdue') $statusClass = 'bg-danger';
                                                ?>
                                                <span class="badge <?= $statusClass ?>"><?= $act['status'] ?></span>
                                            </td>
                                            <td>
                                                <span class="text-muted"><?= htmlspecialchars($act['pending_reason'] ?? '') ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
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
                            <label class="form-label text-muted">Current Paper-based Process</label>
                            <div class="p-3 bg-light rounded" style="min-height: 80px;">
                                <?= nl2br(htmlspecialchars($submission['paperless_current'] ?? 'None')) ?>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted">Target ERP-based Process</label>
                            <div class="p-3 bg-light rounded" style="min-height: 80px;">
                                <?= nl2br(htmlspecialchars($submission['paperless_target'] ?? 'None')) ?>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted">Target Deadline</label>
                            <div class="p-3 bg-light rounded">
                                <?= !empty($submission['paperless_deadline']) ? date('d M Y', strtotime($submission['paperless_deadline'])) : 'N/A' ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 4: Problems & Issues -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-4">
                    <h5 class="mb-0 text-primary">Section 4: Problems & Issues</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label text-muted">Issue Description</label>
                            <div class="p-3 bg-light rounded" style="min-height: 80px;">
                                <?= nl2br(htmlspecialchars($submission['problem_desc'] ?? 'None')) ?>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted">Priority</label>
                            <div class="p-3 bg-light rounded">
                                <?= htmlspecialchars($submission['problem_priority'] ?? 'N/A') ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Section 5: IT Support -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-white border-0 pt-4">
                            <h5 class="mb-0 text-primary">Section 5: IT Support</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($submission['it_support_req'])): ?>
                                <div class="alert alert-danger mb-3">
                                    <i class="fas fa-exclamation-triangle me-2"></i><strong>IT Support Requested</strong>
                                </div>
                                <label class="form-label text-muted">What kind of support is needed?</label>
                                <div class="p-3 bg-light rounded">
                                    <?= nl2br(htmlspecialchars($submission['it_support_desc'] ?? 'None')) ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No IT support requested.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Section 6: New Feature Request -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-white border-0 pt-4">
                            <h5 class="mb-0 text-primary">Section 6: New Feature Request</h5>
                        </div>
                        <div class="card-body">
                            <label class="form-label text-muted">Describe the feature</label>
                            <div class="p-3 bg-light rounded" style="min-height: 80px;">
                                <?= nl2br(htmlspecialchars($submission['feature_req'] ?? 'None')) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>
