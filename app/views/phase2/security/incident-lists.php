<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Incidents & Maintenance</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="<?= $base_url ?>/con-security-incidents/create" class="btn btn-danger">
            <i class="bi bi-exclamation-triangle me-1"></i> Report Incident
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
                            <th>Issue Title</th>
                            <th>Equipment</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Reported By</th>
                            <th>Reported At</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($incidents)): ?>
                            <?php foreach ($incidents as $i => $inc): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($inc['project_name']) ?></td>
                                <td>
                                    <div class="fw-medium text-dark" style="max-width: 200px;" title="<?= htmlspecialchars($inc['description'] ?? '') ?>">
                                        <?= htmlspecialchars($inc['issue_title']) ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($inc['equipment_name']): ?>
                                        <span class="badge border text-dark"><?= htmlspecialchars($inc['equipment_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">General System Issue</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                        $pClass = 'bg-info';
                                        if($inc['priority'] == 'High') $pClass = 'bg-warning text-dark';
                                        if($inc['priority'] == 'Critical') $pClass = 'bg-danger';
                                    ?>
                                    <span class="badge <?= $pClass ?>"><?= htmlspecialchars($inc['priority']) ?></span>
                                </td>
                                <td>
                                    <?php 
                                        $sClass = 'bg-secondary';
                                        if($inc['status'] == 'In Progress') $sClass = 'bg-primary';
                                        if($inc['status'] == 'Resolved') $sClass = 'bg-success';
                                    ?>
                                    <span class="badge <?= $sClass ?>"><?= htmlspecialchars($inc['status']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($inc['reported_by_name'] ?? '—') ?></td>
                                <td><div class="small text-muted"><?= date('d M Y, h:i A', strtotime($inc['reported_at'])) ?></div></td>
                                <td class="text-center">
                                    <a href="<?= $base_url ?>/con-security-incidents/view?id=<?= $inc['id'] ?>" class="btn btn-sm btn-outline-info me-1" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 15, 22]) && $inc['status'] != 'Resolved'): ?>
                                        <a href="<?= $base_url ?>/con-security-incidents/approve?id=<?= $inc['id'] ?>" class="btn btn-sm btn-outline-success" title="Mark as Resolved" onclick="return confirm('Are you sure you want to resolve this incident?');">
                                            <i class="bi bi-check2-circle"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="bi bi-shield-check me-2"></i>No incidents reported yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
