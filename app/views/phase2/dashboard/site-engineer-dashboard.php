<?php
$base_url = $GLOBALS['base_url'];
?>

<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h4 class="mb-0">Site Engineer Dashboard</h4>
            <p class="mb-0 text-muted">Welcome to your workspace. Manage your projects, materials, and reports.</p>
        </div>
        <div class="main-dashboard-header-right">
            <div>
                <label class="tx-13">Today's Date</label>
                <div class="main-star">
                    <i class="bi bi-calendar fw-semibold"></i> <?= date('d M Y') ?>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row">
        <!-- Assigned Projects -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
            <div class="card custom-card crypto-card overflow-hidden">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted fs-13 mb-1">Assigned Projects</p>
                            <h4 class="mb-0 fw-semibold"><?= $myProjectsCount ?></h4>
                        </div>
                        <div class="avatar avatar-lg bg-primary-transparent rounded-circle">
                            <i class="bi bi-buildings fs-20"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Material Received -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
            <div class="card custom-card crypto-card overflow-hidden">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted fs-13 mb-1">Material Received (GRN)</p>
                            <h4 class="mb-0 fw-semibold"><?= $totalReceived ?></h4>
                        </div>
                        <div class="avatar avatar-lg bg-success-transparent rounded-circle">
                            <i class="bi bi-box-seam fs-20"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Material Issued -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
            <div class="card custom-card crypto-card overflow-hidden">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted fs-13 mb-1">Material Issued</p>
                            <h4 class="mb-0 fw-semibold"><?= $totalIssued ?></h4>
                        </div>
                        <div class="avatar avatar-lg bg-warning-transparent rounded-circle">
                            <i class="bi bi-arrow-up-right-square fs-20"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reports Submitted -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
            <div class="card custom-card crypto-card overflow-hidden">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted fs-13 mb-1">Reports Submitted</p>
                            <h4 class="mb-0 fw-semibold"><?= $totalReports ?></h4>
                        </div>
                        <div class="avatar avatar-lg bg-info-transparent rounded-circle">
                            <i class="bi bi-clipboard-data fs-20"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 1: My Projects & Recent Material Receiving -->
    <div class="row">
        <!-- My Projects -->
        <div class="col-xl-6">
            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">My Assigned Projects</div>
                    <a href="<?= $base_url ?>/con-projects" class="btn btn-sm btn-primary btn-wave">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table text-nowrap table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Project Name</th>
                                    <th>Location</th>
                                    <th>Progress</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($myProjects)): ?>
                                    <tr><td colspan="4" class="text-center text-muted">No projects assigned to you.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($myProjects as $p): ?>
                                        <tr>
                                            <td>
                                                <span class="fw-semibold"><?= htmlspecialchars($p['name'] ?? 'N/A') ?></span><br>
                                                <span class="badge bg-primary-transparent"><?= $p['project_status'] ?? 'N/A' ?></span>
                                            </td>
                                            <td><?= htmlspecialchars($p['location_name']) ?></td>
                                            <td>
                                                <div class="progress progress-sm">
                                                    <div class="progress-bar bg-success" style="width: <?= min((float)$p['progress_pct'], 100) ?>%"></div>
                                                </div>
                                                <small class="text-muted"><?= number_format((float)$p['progress_pct'], 1) ?>%</small>
                                            </td>
                                            <td>
                                                <a href="<?= $base_url ?>/con-project/<?= $p['id'] ?>" class="btn btn-sm btn-info-light">
                                                    <i class="bi bi-eye"></i> Details
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Material Received -->
        <div class="col-xl-6">
            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">Recent Material Received (GRN)</div>
                    <a href="<?= $base_url ?>/con-material-receivings" class="btn btn-sm btn-primary btn-wave">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table text-nowrap table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentReceives)): ?>
                                    <tr><td colspan="3" class="text-center text-muted">No materials received recently.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recentReceives as $mr): ?>
                                        <tr>
                                            <td>
                                                <span class="fw-semibold"><?= htmlspecialchars($mr['
                                                
                                                project_name']) ?></span>
                                            </td>
                                            <td><?= date('d M Y', strtotime($mr['receiving_date'])) ?></td>
                                            <td>
                                                <?php 
                                                $badge = 'bg-warning-transparent';
                                                if ($mr['overall_status'] === 'Stock In') $badge = 'bg-success-transparent';
                                                if ($mr['overall_status'] === 'Rejected') $badge = 'bg-danger-transparent';
                                                ?>
                                                <span class="badge <?= $badge ?>"><?= htmlspecialchars($mr['overall_status']) ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: Recent Material Issued & Weekly Reports -->
    <div class="row">
        <!-- Recent Material Issued -->
        <div class="col-xl-6">
            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">Recent Material Issues</div>
                    <a href="<?= $base_url ?>/con-material-issues" class="btn btn-sm btn-primary btn-wave">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table text-nowrap table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Material</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentIssues)): ?>
                                    <tr><td colspan="3" class="text-center text-muted">No materials issued recently.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recentIssues as $mi): ?>
                                        <tr>
                                            <td><span class="fw-semibold"><?= htmlspecialchars($mi['project_name']) ?></span></td>
                                            <td><?= htmlspecialchars($mi['material_name']) ?></td>
                                            <td><?= date('d M Y', strtotime($mi['issue_date'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Weekly Reports -->
        <div class="col-xl-6">
            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">Recent Weekly Reports</div>
                    <a href="<?= $base_url ?>/con-weekly-reports" class="btn btn-sm btn-primary btn-wave">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table text-nowrap table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Week No.</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentReports)): ?>
                                    <tr><td colspan="3" class="text-center text-muted">No reports submitted recently.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recentReports as $rep): ?>
                                        <tr>
                                            <td><span class="fw-semibold"><?= htmlspecialchars($rep['project_name']) ?></span></td>
                                            <td>Week <?= $rep['week_no'] ?></td>
                                            <td>
                                                <?php 
                                                $status = 'Pending';
                                                $badge = 'bg-warning-transparent';
                                                if ($rep['approved_by_pm']) { $status = 'Approved'; $badge = 'bg-success-transparent'; }
                                                ?>
                                                <span class="badge <?= $badge ?>"><?= $status ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// ── Approved Purchase Orders Notification ─────────────────────────────────
$approvedPOs = $approvedPOs ?? [];
if (!empty($approvedPOs)):
?>
<div class="container-fluid mt-2 mb-4">
    <div class="card custom-card" style="border:2px solid rgba(16,185,129,.35);background:rgba(16,185,129,.02)">
        <div class="card-header justify-content-between" style="border-bottom:1px solid rgba(16,185,129,.2)">
            <div class="card-title d-flex align-items-center gap-2" style="color:#059669">
                <span style="width:10px;height:10px;border-radius:50%;background:#10b981;display:inline-block;animation:pulse 1.5s infinite"></span>
                <i class="bi bi-bag-check-fill text-success"></i>
                Purchase Orders Approved — Action Required
                <span class="badge bg-success ms-2" style="font-size:10px"><?= count($approvedPOs) ?> PO<?= count($approvedPOs) > 1 ? 's' : '' ?></span>
            </div>
            <a href="<?= $base_url ?>/con-purchase-orders" class="btn btn-sm btn-success-light">
                View All POs →
            </a>
        </div>
        <div class="card-body p-0">
            <div class="alert alert-success border-0 rounded-0 mb-0 py-2 px-4" style="font-size:12px">
                <i class="bi bi-info-circle me-1"></i>
                নিচের Purchase Order গুলো Project Manager কর্তৃক Approved হয়েছে। এগুলোর Material Receiving (GRN) করুন।
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:12.5px">
                    <thead class="table-success bg-opacity-25">
                        <tr>
                            <th>#PO</th>
                            <th>Project</th>
                            <th>PR Ref</th>
                            <th>Items</th>
                            <th>Total Amount</th>
                            <th>PO Status</th>
                            <th>Approved By (PM)</th>
                            <th>Approved On</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($approvedPOs as $po):
                            $poStatus = $po['status'] ?? 'Issued';
                            $stBadge = match($poStatus) {
                                'Issued'            => 'bg-primary-transparent',
                                'Partially Received' => 'bg-warning-transparent',
                                'Fully Received'    => 'bg-success-transparent',
                                'Cancelled'         => 'bg-danger-transparent',
                                default             => 'bg-secondary-transparent',
                            };
                        ?>
                        <tr>
                            <td class="fw-bold" style="color:#059669">PO-<?= $po['id'] ?></td>
                            <td><strong><?= htmlspecialchars($po['project_name'] ?? 'N/A') ?></strong></td>
                            <td>
                                <a href="<?= $base_url ?>/con-purchase-request/<?= $po['pr_id'] ?>"
                                   class="text-primary" style="font-size:11px">PR-<?= $po['pr_id'] ?></a>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border" style="font-size:10px">
                                    <?= (int)$po['item_count'] ?> items
                                </span>
                            </td>
                            <td class="fw-semibold">
                                ৳<?= number_format((float)$po['total_amount'], 2) ?>
                            </td>
                            <td>
                                <span class="badge <?= $stBadge ?>" style="font-size:10px"><?= $poStatus ?></span>
                            </td>
                            <td style="font-size:11px;color:#6b7280">
                                <?= htmlspecialchars($po['approved_by_name'] ?? '—') ?>
                            </td>
                            <td style="font-size:11px;color:#6b7280">
                                <?= !empty($po['pm_approved_at']) ? date('d M Y, h:i A', strtotime($po['pm_approved_at'])) : '—' ?>
                            </td>
                            <td class="text-end" style="white-space:nowrap">
                                <a href="<?= $base_url ?>/con-purchase-order/<?= $po['id'] ?>"
                                   class="btn btn-sm btn-outline-success" style="font-size:11px">
                                    <i class="bi bi-eye"></i> View PO
                                </a>
                                <?php if (in_array($poStatus, ['Issued', 'Partially Received'])): ?>
                                <a href="<?= $base_url ?>/con-material-receiving-create?po_id=<?= $po['id'] ?>"
                                   class="btn btn-sm btn-success" style="font-size:11px">
                                    <i class="bi bi-inbox-fill"></i> Receive
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
