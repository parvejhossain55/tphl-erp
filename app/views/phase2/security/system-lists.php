<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Security Systems</li>
            </ol>
        </nav>
    </div>
    <div class="row justify-content-end mb-3 mt-3">
        <div class="col-12">
            <div class="card__wrapper border-0 p-0 shadow-none bg-transparent">
                <div class="d-flex flex-column flex-sm-row gap-2 justify-content-end">
                    <!-- Hide create button for roles that are approvers (15, 22, 3), but let 20 and 1 see it -->
                    <?php if (!in_array($_SESSION['user']['role_id'] ?? 0, [3, 15, 22])): ?>
                    <a href="<?= $base_url ?>/con-security-systems/create" class="btn btn-primary text-nowrap">
                        <i class="bi bi-plus-circle me-1"></i> Add Security System
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
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
                            <th>System Type</th>
                            <th>Installation Date</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($systems)): ?>
                            <?php foreach ($systems as $i => $sys): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($sys['project_name']) ?></td>
                                <td>
                                    <span class="badge bg-info text-dark">
                                        <i class="bi bi-shield-lock me-1"></i> <?= htmlspecialchars($sys['system_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?= !empty($sys['installation_date']) ? date('d M Y', strtotime($sys['installation_date'])) : '<span class="text-muted">Not Set</span>' ?>
                                </td>
                                <td>
                                    <?php 
                                        $badgeClass = 'bg-secondary';
                                        if($sys['status'] == 'Active') $badgeClass = 'bg-success';
                                        if($sys['status'] == 'Under Maintenance') $badgeClass = 'bg-warning text-dark';
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($sys['status']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($sys['creator_name'] ?? '—') ?></td>
                                <td class="text-center">
                                    <a href="<?= $base_url ?>/con-security-systems/view?id=<?= $sys['id'] ?>" class="btn btn-sm btn-outline-info me-1" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if (!in_array($_SESSION['user']['role_id'] ?? 0, [3, 15, 22])): ?>
                                    <a href="<?= $base_url ?>/con-security-systems/edit?id=<?= $sys['id'] ?>"
                                       class="btn btn-sm btn-outline-warning" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-diagram-3 me-2"></i>No security systems have been set up yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
