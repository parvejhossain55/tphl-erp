<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Security Equipments</li>
            </ol>
        </nav>
    </div>
    
</div>
<div>
        <a href="<?= $base_url ?>/con-security-equipments/create" class="btn btn-success" style="display: inline-block !important; visibility: visible !important; opacity: 1 !important;">
            <i class="bi bi-plus-circle me-1"></i> Add New Equipment
        </a>
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
                            <th>Equipment Name</th>
                            <th>S/N or MAC</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($equipments)): ?>
                            <?php foreach ($equipments as $i => $eq): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($eq['project_name']) ?></td>
                                <td>
                                    <span class="badge border text-dark">
                                        <?= htmlspecialchars($eq['system_type']) ?>
                                    </span>
                                </td>
                                <td class="fw-medium text-primary"><?= htmlspecialchars($eq['equipment_name']) ?></td>
                                <td>
                                    <div class="small">
                                        SN: <?= htmlspecialchars($eq['serial_number'] ?? 'N/A') ?><br>
                                        MAC: <?= htmlspecialchars($eq['mac_address'] ?? 'N/A') ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($eq['location_area'] ?? '—') ?></td>
                                <td>
                                    <?php 
                                        $badgeClass = 'bg-success';
                                        if($eq['status'] == 'Damaged') $badgeClass = 'bg-danger';
                                        if($eq['status'] == 'Sent to Repair') $badgeClass = 'bg-warning text-dark';
                                        if($eq['status'] == 'Replaced') $badgeClass = 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($eq['status']) ?></span>
                                </td>
                                <td class="text-center">
                                    <a href="<?= $base_url ?>/con-security-equipments/view?id=<?= $eq['id'] ?>" class="btn btn-sm btn-outline-info me-1" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if (!in_array($_SESSION['user']['role_id'] ?? 0, [3, 15, 22])): ?>
                                    <a href="<?= $base_url ?>/con-security-equipments/edit?id=<?= $eq['id'] ?>"
                                       class="btn btn-sm btn-outline-warning" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="bi bi-webcam me-2"></i>No equipments have been added yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
