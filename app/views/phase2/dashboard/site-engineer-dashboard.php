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
                                                <span class="fw-semibold"><?= htmlspecialchars($p['name']) ?></span><br>
                                                <span class="badge bg-primary-transparent"><?= ucfirst($p['project_status']) ?></span>
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
                    <a href="<?= $base_url ?>/con-material-receiving" class="btn btn-sm btn-primary btn-wave">View All</a>
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
                                                <span class="fw-semibold"><?= htmlspecialchars($mr['project_name']) ?></span>
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
