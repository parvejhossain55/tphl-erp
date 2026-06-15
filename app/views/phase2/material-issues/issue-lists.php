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
    <div class="d-flex gap-2 mb-2">
        <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#csvImportModal">
            <i class="bi bi-upload me-1"></i> Import CSV
        </button>
        <a href="<?= $base_url ?>/con-material-issue-create" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i> New Material Issue
        </a>
    </div>
</div>

<!-- Import CSV Modal: Import Material Issues -->
<div class="modal fade" id="csvImportModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-file-earmark-arrow-up me-2 text-success"></i>Import Material Issues
                </h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= $base_url ?>/con-material-issue-csv-import" method="POST" enctype="multipart/form-data">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Upload CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    </div>

                    <div class="border rounded p-2 mb-3" style="font-size:12px;">
                        <div class="fw-semibold mb-2">
                            <i class="bi bi-info-circle text-primary me-1"></i>CSV Format — Required Columns (11):
                        </div>
                        <code>project_name, warehouse_name, material_name, quantity, contractor_name, purpose, target_title, year, month, week_number, issue_date</code>

                        <div class="mt-2 text-muted" style="font-size:11px; border-top:1px solid #dee2e6; padding-top:6px;">
                            <div class="row g-1">
                                <div class="col-6">⚠️ <strong>project_name</strong> — projects table এর নামের সাথে হুবহু মিলতে হবে</div>
                                <div class="col-6">⚠️ <strong>material_name</strong> — con_materials এর নাম</div>
                                <div class="col-6">⚠️ <strong>contractor_name</strong> — contractors table এর নাম</div>
                                <div class="col-6">⚠️ <strong>target_title</strong> — না থাকলে নতুন target তৈরি হবে</div>
                                <div class="col-6">⚠️ <strong>year / month / week_number</strong> — নতুন target এ ব্যবহার হবে</div>
                                <div class="col-6">⚠️ <strong>issue_date</strong> — format: YYYY-MM-DD</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="data:text/csv;charset=utf-8,project_name%2Cwarehouse_name%2Cmaterial_name%2Cquantity%2Ccontractor_name%2Cpurpose%2Ctarget_title%2Cyear%2Cmonth%2Cweek_number%2Cissue_date%0AThe%20Prime%20Heights%2CAshulia%20Warehouse%2CAggregate%2FStone%2C150.000%2CMd.%20Rahim%2CFloor%203%20slab%2CTarget%20Week%201%2C2026%2C5%2C1%2C2026-05-07"
                           download="material_issue_template.csv"
                           class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-download me-1"></i> Download Sample CSV
                        </a>
                        <div>
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success btn-sm ms-2">
                                <i class="bi bi-upload me-1"></i> Import
                            </button>
                        </div>
                    </div>

                </form>
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
