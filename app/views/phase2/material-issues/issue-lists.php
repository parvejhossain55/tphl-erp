<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Material Issues</li>
            </ol>
        </nav>
    </div>
    <a href="<?= $base_url ?>/con-material-issue-create" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i> New Material Issue
    </a>
</div>

<section class="section">
    <div class="card__wrapper shadow-sm">
        <div class="card__body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped custom-table mb-0 multiple-table">
                    <thead class="table-light">
                        <tr>
                            <th>ISS #</th>
                            <th>Project</th>
                            <th>Warehouse</th>
                            <th>Material</th>
                            <th class="text-end">Qty</th>
                            <th>Issued To</th>
                            <th>Purpose</th>
                            <th>Issued By</th>
                            <th>Date</th>
                            <th class="text-center">View</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($issues)): ?>
                            <?php foreach ($issues as $iss): ?>
                            <tr>
                                <td><span class="fw-bold text-primary">ISS-<?= $iss['id'] ?></span></td>
                                <td class="fw-bold"><?= htmlspecialchars($iss['project_name'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($iss['warehouse_name'] ?? '—') ?></td>
                                <td class="fw-bold">
                                    <?= htmlspecialchars($iss['material_name'] ?? '—') ?>
                                    <span class="text-muted small">(<?= $iss['unit'] ?? '' ?>)</span>
                                </td>
                                <td class="text-end fw-bold text-danger"><?= number_format($iss['quantity'], 3) ?></td>
                                <td class="small"><?= htmlspecialchars($iss['issued_to'] ?? '—') ?></td>
                                <td class="small"><?= htmlspecialchars(mb_strimwidth($iss['purpose'] ?? '', 0, 30, '...')) ?></td>
                                <td><?= htmlspecialchars($iss['issued_by_name'] ?? '—') ?></td>
                                <td class="text-muted small"><?= date('d M Y', strtotime($iss['issue_date'])) ?></td>
                                <td class="text-center">
                                    <a href="<?= $base_url ?>/con-material-issue/<?= $iss['id'] ?>"
                                       class="btn btn-sm btn-outline-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="bi bi-box-arrow-right me-2"></i>No material issues yet.
                                    <a href="<?= $base_url ?>/con-material-issue-create" class="ms-2">Create one →</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
