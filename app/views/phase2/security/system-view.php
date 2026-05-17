<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-security-systems">Security Systems</a></li>
                <li class="breadcrumb-item active">View Details</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="<?= $base_url ?>/con-security-systems" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>
</div>

<section class="section">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card__wrapper shadow-sm">
                <div class="card__body">
                    <?php if ($system): ?>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="mb-0 text-primary"><i class="bi bi-shield-check me-2"></i>System Information</h5>
                            <span class="badge <?= $system['status'] == 'Active' ? 'bg-success' : 'bg-warning text-dark' ?> fs-6 px-3 py-2">
                                <?= htmlspecialchars($system['status']) ?>
                            </span>
                        </div>
                        
                        <table class="table table-bordered mb-0">
                            <tbody>
                                <tr>
                                    <th style="width: 30%;" class="bg-light">Project Name</th>
                                    <td class="fw-bold"><?= htmlspecialchars($system['project_name']) ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">System Type</th>
                                    <td><?= htmlspecialchars($system['system_type']) ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Vendor / Installer</th>
                                    <td><?= htmlspecialchars($system['vendor_id'] ?? 'N/A') ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Installation Date</th>
                                    <td><?= !empty($system['installation_date']) ? date('d M Y', strtotime($system['installation_date'])) : 'Not Set' ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Registered By</th>
                                    <td><?= htmlspecialchars($system['creator_name'] ?? 'System') ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Registration Date</th>
                                    <td><?= date('d M Y, h:i A', strtotime($system['created_at'])) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-danger mb-0">System not found or you don't have permission to view it.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
