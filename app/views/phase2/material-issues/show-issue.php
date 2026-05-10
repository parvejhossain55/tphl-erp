<div class="pagetitle d-flex justify-content-between align-items-center">
    <div>
        <h1><?= htmlspecialchars($page_title) ?></h1>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-material-issues">Material Issues</a></li>
                <li class="breadcrumb-item active">Detail</li>
            </ol>
        </nav>
    </div>
    <a href="<?= $base_url ?>/con-material-issues" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<section class="section">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card__wrapper shadow-sm">
                <div class="card__header d-flex justify-content-between align-items-center">
                    <h6 class="card__title mb-0">
                        <i class="bi bi-box-arrow-right me-2"></i>Issue Details (ISS-<?= $issue['id'] ?>)
                    </h6>
                    <span class="badge bg-danger"><i class="bi bi-dash-circle me-1"></i> Stock OUT</span>
                </div>
                <div class="card__body p-4">

                    <table class="table table-bordered mb-0">
                        <tbody>
                            <tr>
                                <th style="width:30%" >Project</th>
                                <td><?= htmlspecialchars($issue['project_name']) ?></td>
                            </tr>
                            <tr>
                                <th >Warehouse</th>
                                <td><?= htmlspecialchars($issue['warehouse_name']) ?></td>
                            </tr>
                            <tr>
                                <th >Target</th>
                                <td>
                                    <?php if (!empty($issue['target_name'])): ?>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($issue['target_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Not specified</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th >Working Plan</th>
                                <td>
                                    <?php if (!empty($issue['work_plan_name'])): ?>
                                        <?= htmlspecialchars($issue['work_plan_name']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not specified</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th >Issue Date</th>
                                <td><?= date('d M Y', strtotime($issue['issue_date'])) ?></td>
                            </tr>
                            <tr>
                                <th >Issued By</th>
                                <td><?= htmlspecialchars($issue['issued_by_name']) ?></td>
                            </tr>
                            <tr>
                                <th >Issued To</th>
                                <td><?= htmlspecialchars($issue['issued_to'] ?: '—') ?></td>
                            </tr>
                            <tr>
                                <th >Purpose</th>
                                <td><?= htmlspecialchars($issue['purpose'] ?: '—') ?></td>
                            </tr>
                            <tr>
                                <td colspan="2" class="p-0 border-0"></td>
                            </tr>
                            <tr class="table-light">
                                <th class="fs-5 text-primary">Material Issued</th>
                                <td class="fs-5 fw-bold text-success">
                                    <i class="bi bi-box me-1 text-muted"></i>
                                    <?= htmlspecialchars($issue['material_name']) ?>
                                    <span class="ms-3 ps-3 border-start text-danger">
                                        <?= (float)$issue['quantity'] ?> <?= $issue['unit'] ?>
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="alert alert-success mt-4 mb-0">

                        Stock deducted from <strong><?= htmlspecialchars($issue['warehouse_name']) ?></strong>.
                        Movement logged in <span class="ms-1"><code> con_stock_movements</code>.</span>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>
