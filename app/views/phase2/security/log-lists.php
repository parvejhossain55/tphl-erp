<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Daily Logs</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="<?= $base_url ?>/con-security-logs/create" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Submit Daily Log
        </a>
    </div>
</div>

<section class="section">
    <div class="card__wrapper shadow-sm">
        <div class="card__body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped custom-table mb-0 multiple-table">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Project</th>
                            <th>Log Date</th>
                            <th>Operator</th>
                            <th>Condition</th>
                            <th>Remarks</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($logs)): ?>
                            <?php foreach ($logs as $i => $log): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($log['project_name']) ?></td>
                                <td>
                                    <span class="badge border text-dark">
                                        <?= date('d M Y', strtotime($log['log_date'])) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($log['operator_name'] ?? '—') ?></td>
                                <td>
                                    <?php if ($log['condition_status'] == 'All Okay'): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> All Okay</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i> Issue Found</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars($log['remarks'] ?? '') ?>">
                                        <?= htmlspecialchars($log['remarks'] ?? '—') ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <a href="<?= $base_url ?>/con-security-logs/view?id=<?= $log['id'] ?>" class="btn btn-sm btn-outline-info me-1" title="View Log Checklist">
                                        <i class="bi bi-card-checklist"></i> View
                                    </a>
                                    <?php if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 15, 22]) && $log['condition_status'] == 'Issue Found' && ($log['status'] ?? 'Pending') != 'Resolved'): ?>
                                        <a href="<?= $base_url ?>/con-security-logs/approve?id=<?= $log['id'] ?>" class="btn btn-sm btn-outline-success" title="Mark as Resolved" onclick="return confirm('Are you sure you want to mark this issue as resolved?');">
                                            <i class="bi bi-check2-circle"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-journal-text me-2"></i>No daily logs have been submitted yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
