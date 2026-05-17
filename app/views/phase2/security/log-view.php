<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-security-logs">Daily Logs</a></li>
                <li class="breadcrumb-item active">View Details</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="<?= $base_url ?>/con-security-logs" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>
</div>

<section class="section">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card__wrapper shadow-sm">
                <div class="card__body">
                    <?php if ($log): 
                        $checklist = json_decode($log['checklist_data'], true) ?: [];
                    ?>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="mb-0 text-primary"><i class="bi bi-card-checklist me-2"></i>Daily Log Checklist</h5>
                            <div>
                                <span class="badge <?= $log['condition_status'] == 'All Okay' ? 'bg-success' : 'bg-danger' ?> fs-6 px-3 py-2 me-2">
                                    Condition: <?= htmlspecialchars($log['condition_status']) ?>
                                </span>
                                <?php if ($log['condition_status'] == 'Issue Found'): ?>
                                    <span class="badge <?= ($log['status'] ?? 'Pending') == 'Resolved' ? 'bg-success' : 'bg-warning text-dark' ?> fs-6 px-3 py-2">
                                        Status: <?= htmlspecialchars($log['status'] ?? 'Pending') ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="text-muted small">Project Name</div>
                                <div class="fw-bold fs-5"><?= htmlspecialchars($log['project_name']) ?></div>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <div class="text-muted small">Log Date</div>
                                <div class="fw-bold fs-5"><?= date('d M Y', strtotime($log['log_date'])) ?></div>
                            </div>
                        </div>

                        <h6 class="border-bottom pb-2 mb-3">System Checks</h6>
                        <ul class="list-group mb-4">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                CCTV System
                                <?php if (($checklist['cctv'] ?? '') == 'Okay'): ?>
                                    <span class="badge bg-success rounded-pill px-3"><i class="bi bi-check-circle me-1"></i> Okay</span>
                                <?php else: ?>
                                    <span class="badge bg-danger rounded-pill px-3"><i class="bi bi-exclamation-triangle me-1"></i> Issue</span>
                                <?php endif; ?>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Access Control
                                <?php if (($checklist['access_control'] ?? '') == 'Okay'): ?>
                                    <span class="badge bg-success rounded-pill px-3"><i class="bi bi-check-circle me-1"></i> Okay</span>
                                <?php else: ?>
                                    <span class="badge bg-danger rounded-pill px-3"><i class="bi bi-exclamation-triangle me-1"></i> Issue</span>
                                <?php endif; ?>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Intercom System
                                <?php if (($checklist['intercom'] ?? '') == 'Okay'): ?>
                                    <span class="badge bg-success rounded-pill px-3"><i class="bi bi-check-circle me-1"></i> Okay</span>
                                <?php else: ?>
                                    <span class="badge bg-danger rounded-pill px-3"><i class="bi bi-exclamation-triangle me-1"></i> Issue</span>
                                <?php endif; ?>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Building Management System (BMS)
                                <?php if (($checklist['bms'] ?? '') == 'Okay'): ?>
                                    <span class="badge bg-success rounded-pill px-3"><i class="bi bi-check-circle me-1"></i> Okay</span>
                                <?php else: ?>
                                    <span class="badge bg-danger rounded-pill px-3"><i class="bi bi-exclamation-triangle me-1"></i> Issue</span>
                                <?php endif; ?>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Fire Alarm System
                                <?php if (($checklist['fire_alarm'] ?? '') == 'Okay'): ?>
                                    <span class="badge bg-success rounded-pill px-3"><i class="bi bi-check-circle me-1"></i> Okay</span>
                                <?php else: ?>
                                    <span class="badge bg-danger rounded-pill px-3"><i class="bi bi-exclamation-triangle me-1"></i> Issue</span>
                                <?php endif; ?>
                            </li>
                        </ul>

                        <h6 class="border-bottom pb-2 mb-3">Remarks / Notes</h6>
                        <div class="bg-light p-3 rounded text-dark">
                            <?= nl2br(htmlspecialchars($log['remarks'] ?? 'No remarks provided.')) ?>
                        </div>

                        <div class="mt-4 pt-3 border-top text-muted small d-flex flex-column gap-2">
                            <div class="d-flex justify-content-between">
                                <span>Reported by: <strong><?= htmlspecialchars($log['operator_name'] ?? 'System') ?></strong></span>
                                <span>Reported at: <?= date('d M Y, h:i A', strtotime($log['created_at'])) ?></span>
                            </div>
                            <?php if (($log['status'] ?? '') == 'Resolved'): ?>
                            <div class="d-flex justify-content-between text-success">
                                <span>Resolved by: <strong><?= htmlspecialchars($log['resolved_by_name'] ?? 'System') ?></strong></span>
                                <span>Resolved at: <?= date('d M Y, h:i A', strtotime($log['resolved_at'])) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 15, 22]) && $log['condition_status'] == 'Issue Found' && ($log['status'] ?? 'Pending') != 'Resolved'): ?>
                            <div class="mt-4 text-end border-top pt-3">
                                <a href="<?= $base_url ?>/con-security-logs/approve?id=<?= $log['id'] ?>" class="btn btn-success" onclick="return confirm('Are you sure you want to mark this issue as resolved?');">
                                    <i class="bi bi-check2-circle me-1"></i> Mark as Resolved
                                </a>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="alert alert-danger mb-0">Log record not found or you don't have permission to view it.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
