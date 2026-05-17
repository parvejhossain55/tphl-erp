<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-security-incidents">Incidents</a></li>
                <li class="breadcrumb-item active">View Details</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="<?= $base_url ?>/con-security-incidents" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>
</div>

<section class="section">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card__wrapper shadow-sm border-top border-3 <?= $incident['status'] == 'Resolved' ? 'border-success' : 'border-danger' ?>">
                <div class="card__body">
                    <?php if ($incident): ?>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="mb-0 text-dark"><i class="bi bi-exclamation-octagon text-danger me-2"></i>Incident Details</h5>
                            <div>
                                <?php 
                                    $pClass = 'bg-info';
                                    if($incident['priority'] == 'High') $pClass = 'bg-warning text-dark';
                                    if($incident['priority'] == 'Critical') $pClass = 'bg-danger';
                                    
                                    $sClass = 'bg-secondary';
                                    if($incident['status'] == 'In Progress') $sClass = 'bg-primary';
                                    if($incident['status'] == 'Resolved') $sClass = 'bg-success';
                                ?>
                                <span class="badge <?= $pClass ?> fs-6 px-3 py-2 me-2">Priority: <?= htmlspecialchars($incident['priority']) ?></span>
                                <span class="badge <?= $sClass ?> fs-6 px-3 py-2">Status: <?= htmlspecialchars($incident['status']) ?></span>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="text-muted small">Project Name</div>
                                <div class="fw-bold fs-5 text-primary"><?= htmlspecialchars($incident['project_name']) ?></div>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <div class="text-muted small">Reported Date</div>
                                <div class="fw-bold fs-5"><?= date('d M Y, h:i A', strtotime($incident['reported_at'])) ?></div>
                            </div>
                        </div>

                        <table class="table table-bordered mb-4">
                            <tbody>
                                <tr>
                                    <th style="width: 30%;" class="bg-light">Issue Title</th>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($incident['issue_title']) ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Related System</th>
                                    <td><?= htmlspecialchars($incident['system_type'] ?? 'General System') ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Faulty Equipment</th>
                                    <td>
                                        <?php if ($incident['equipment_name']): ?>
                                            <span class="text-danger fw-medium"><?= htmlspecialchars($incident['equipment_name']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Not specified / General</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Reported By</th>
                                    <td>
                                        <div><?= htmlspecialchars($incident['reported_by_name'] ?? 'System') ?></div>
                                        <div class="small text-muted"><?= date('d M Y, h:i A', strtotime($incident['reported_at'])) ?></div>
                                    </td>
                                </tr>
                                <?php if ($incident['status'] == 'Resolved'): ?>
                                <tr>
                                    <th class="bg-light text-success">Resolved By</th>
                                    <td>
                                        <div class="text-success fw-bold"><?= htmlspecialchars($incident['resolved_by_name'] ?? 'System') ?></div>
                                        <div class="small text-muted"><?= date('d M Y, h:i A', strtotime($incident['resolved_at'])) ?></div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <h6 class="border-bottom pb-2 mb-3">Detailed Description</h6>
                        <div class="bg-light p-3 rounded text-dark" style="min-height: 100px;">
                            <?= nl2br(htmlspecialchars($incident['description'])) ?>
                        </div>

                        <?php if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 15, 22]) && $incident['status'] != 'Resolved'): ?>
                            <div class="mt-4 text-end border-top pt-3">
                                <a href="<?= $base_url ?>/con-security-incidents/approve?id=<?= $incident['id'] ?>" class="btn btn-success" onclick="return confirm('Are you sure you want to mark this incident as resolved?');">
                                    <i class="bi bi-check2-circle me-1"></i> Mark as Resolved
                                </a>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="alert alert-danger mb-0">Incident record not found or you don't have permission to view it.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
