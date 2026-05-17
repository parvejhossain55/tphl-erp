<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-security-equipments">Security Equipments</a></li>
                <li class="breadcrumb-item active">View Details</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="<?= $base_url ?>/con-security-equipments" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>
</div>

<section class="section">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card__wrapper shadow-sm">
                <div class="card__body">
                    <?php if ($equipment): ?>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="mb-0 text-primary"><i class="bi bi-webcam me-2"></i>Equipment Details</h5>
                            <span class="badge <?= $equipment['status'] == 'Good' ? 'bg-success' : 'bg-warning text-dark' ?> fs-6 px-3 py-2">
                                <?= htmlspecialchars($equipment['status']) ?>
                            </span>
                        </div>
                        
                        <table class="table table-bordered mb-0">
                            <tbody>
                                <tr>
                                    <th style="width: 30%;" class="bg-light">Project Name</th>
                                    <td class="fw-bold"><?= htmlspecialchars($equipment['project_name']) ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Associated System</th>
                                    <td><span class="badge border text-dark"><?= htmlspecialchars($equipment['system_type']) ?></span></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Equipment Name</th>
                                    <td class="text-primary fw-medium"><?= htmlspecialchars($equipment['equipment_name']) ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Serial Number (S/N)</th>
                                    <td><?= htmlspecialchars($equipment['serial_number'] ?? 'N/A') ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">MAC Address</th>
                                    <td><?= htmlspecialchars($equipment['mac_address'] ?? 'N/A') ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Location / Area</th>
                                    <td><?= htmlspecialchars($equipment['location_area'] ?? 'Not specified') ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Warranty Expiry</th>
                                    <td><?= !empty($equipment['warranty_expiry']) ? date('d M Y', strtotime($equipment['warranty_expiry'])) : 'No warranty recorded' ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Registered By</th>
                                    <td><?= htmlspecialchars($equipment['creator_name'] ?? 'System') ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Registration Date</th>
                                    <td><?= date('d M Y, h:i A', strtotime($equipment['created_at'])) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-danger mb-0">Equipment not found or you don't have permission to view it.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
