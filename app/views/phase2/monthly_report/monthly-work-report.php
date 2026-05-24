<?php
$show_breadcrumb = false;
$userFullName = $_SESSION['user']['full_name'] ?? 'N/A';
$roleNameText = htmlspecialchars($roleName);
$selectedMonthName = date('F Y', strtotime($selectedMonth . '-01'));
?>

<!-- Premium styling for the screen and print layout -->
<style>
    .report-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
        background: #fff;
        overflow: hidden;
    }
    .report-card-header {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        color: #fff;
        padding: 24px 30px;
        border-bottom: none;
    }
    .report-card-header h5 {
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0;
        letter-spacing: 0.5px;
    }
    .report-card-body {
        padding: 30px;
    }
    .summary-item {
        background: #f8fafc;
        border-radius: 8px;
        padding: 16px 20px;
        border: 1px solid #e2e8f0;
    }
    .summary-item .label {
        font-size: 11px;
        text-transform: uppercase;
        color: #64748b;
        font-weight: 600;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }
    .summary-item .value {
        font-size: 18px;
        color: #0f172a;
        font-weight: 700;
    }
    .table-theme {
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
    }
    .table-theme th {
        background-color: #f1f5f9;
        color: #475569;
        font-weight: 600;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 14px 16px;
        border-bottom: 2px solid #e2e8f0;
    }
    .table-theme td {
        padding: 14px 16px;
        font-size: 14px;
        color: #334155;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
    }
    .table-theme tbody tr:hover {
        background-color: #f8fafc;
    }
    
    /* PRINT STYLES */
    @media print {
        body {
            background: #fff !important;
            color: #000 !important;
        }
        body * {
            visibility: hidden;
        }
        .print-area, .print-area * {
            visibility: visible;
        }
        .print-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            margin: 0;
            padding: 20px;
        }
        .noprint, .btn, .filter-section, .pagetitle {
            display: none !important;
        }
        .report-card {
            box-shadow: none !important;
            border: none !important;
            border-radius: 0 !important;
        }
        .report-card-header {
            background: none !important;
            color: #000 !important;
            padding: 0 !important;
            margin-bottom: 20px;
        }
        .report-card-body {
            padding: 0 !important;
        }
        .summary-row {
            margin-bottom: 20px !important;
        }
        .summary-item {
            border: 1px solid #000 !important;
            background: none !important;
            padding: 10px !important;
        }
        .table-theme th {
            background-color: #e2e8f0 !important;
            color: #000 !important;
            border-bottom: 2px solid #000 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .table-theme td, .table-theme th {
            border: 1px solid #000 !important;
            padding: 8px !important;
        }
    }
    .print-only-header {
        display: none;
    }
    @media print {
        .print-only-header {
            display: block !important;
            margin-bottom: 30px;
        }
    }
</style>

<div class="mb-4 pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2 noprint">
    <div>
        <h4><?= htmlspecialchars($page_title) ?></h4>
    </div>
</div>

<!-- Filter Section -->
<div class="card__wrapper mb-4 noprint">
    <form method="GET" action="" class="row  align-items-center">
        <div class="col-auto">
            <label for="month" class="col-form-label fw-semibold">Select Month:</label>
        </div>
        <div class="col-auto">
            <input type="month" name="month" id="month" class="form-control" value="<?= htmlspecialchars($selectedMonth) ?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter me-1"></i>Filter</button>
        </div>
        <div class="col-auto ms-auto">
            <button type="button" class="btn btn-success" onclick="window.print()"><i class="fa-solid fa-print me-1"></i>Print Report</button>
        </div>
    </form>
</div>

<!-- Print & View Area -->
<div class="print-area">
    
    <!-- Print Only Letterhead -->
    <div class="print-only-header text-center">
        <h2 style="font-weight: 700; color: #1e3c72; margin-bottom: 5px;">TPHL GROUP</h2>
        <p class="text-uppercase mb-2" style="font-size: 12px; letter-spacing: 1px; color: #475569;">Monthly Work Report</p>
        <div style="border-top: 2px solid #1e3c72; margin: 10px auto; width: 100%;"></div>
    </div>

    <div class="card report-card">
        <div class="report-card-header noprint">
            <h5><i class="fa-solid fa-file-invoice me-2"></i>Work Performance Summary</h5>
        </div>
        <div class="report-card-body">
            
            <!-- Summary Information Cards -->
            <div class="row  mb-4 summary-row">
                <div class="col-md-4">
                    <div class="summary-item">
                        <div class="label">Employee Name</div>
                        <div class="value"><?= htmlspecialchars($userFullName) ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="summary-item">
                        <div class="label">Department / Designation</div>
                        <div class="value"><?= $roleNameText ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="summary-item">
                        <div class="label">Reporting Period</div>
                        <div class="value"><?= $selectedMonthName ?></div>
                    </div>
                </div>
            </div>

            <!-- Detailed Performance List -->
            <h6 class="fw-bold mb-3" style="color: #1e3c72;"><i class="fa-solid fa-list-check me-2"></i>Logged Activities (<?= count($records) ?>)</h6>
            
            <div class="table-responsive">
                <table class="table table-theme">
                    <thead>
                        <?php if ($roleId === 18): ?>
                            <tr>
                                <th style="width: 60px;">#</th>
                                <th>Project Name</th>
                                <th>Design Link</th>
                                <th>Status</th>
                                <th>Notes</th>
                                <th style="width: 140px;">Date Created</th>
                            </tr>
                        <?php elseif ($roleId === 16): ?>
                            <tr>
                                <th style="width: 60px;">#</th>
                                <th>Project Name</th>
                                <th>Design Type</th>
                                <th>Design Link</th>
                                <th>Version</th>
                                <th>Status</th>
                                <th style="width: 140px;">Date Created</th>
                            </tr>
                        <?php elseif ($roleId === 21): ?>
                            <tr>
                                <th style="width: 60px;">#</th>
                                <th>Project Name</th>
                                <th>Designer Name</th>
                                <th>Company</th>
                                <th>Design Link</th>
                                <th style="width: 140px;">Date Created</th>
                            </tr>
                        <?php endif; ?>
                    </thead>
                    <tbody>
                        <?php if (!empty($records)): ?>
                            <?php foreach ($records as $index => $row): ?>
                                <?php if ($roleId === 18): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td class="fw-semibold text-primary"><?= htmlspecialchars($row['project_name'] ?? 'N/A') ?></td>
                                        <td>
                                            <?php if (!empty($row['full_architecture_design_link'])): ?>
                                                <a href="<?= htmlspecialchars($row['full_architecture_design_link']) ?>" target="_blank" class="text-decoration-none">
                                                    <i class="fa-solid fa-link me-1"></i>View Link
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">No Link</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                                $status = $row['status'] ?? 'draft';
                                                $badgeColor = 'bg-secondary';
                                                if ($status == 'partially') $badgeColor = 'bg-warning text-dark';
                                                elseif ($status == 'fully') $badgeColor = 'bg-success';
                                            ?>
                                            <span class="badge <?= $badgeColor ?>"><?= ucfirst(htmlspecialchars($status)) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($row['notes'] ?? '—') ?></td>
                                        <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                                    </tr>
                                <?php elseif ($roleId === 16): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td class="fw-semibold text-primary"><?= htmlspecialchars($row['project_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($row['design_type_name'] ?? 'N/A') ?></td>
                                        <td>
                                            <?php if (!empty($row['design_link'])): ?>
                                                <a href="<?= htmlspecialchars($row['design_link']) ?>" target="_blank" class="text-decoration-none">
                                                    <i class="fa-solid fa-link me-1"></i>View Link
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">No Link</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-info text-dark">v<?= htmlspecialchars($row['version'] ?? '1.0') ?></span></td>
                                        <td>
                                            <?php 
                                                $status = strtolower($row['status'] ?? 'draft');
                                                $badgeColor = 'bg-secondary';
                                                if ($status == 'approved' || $status == 'completed' || $status == 'final') $badgeColor = 'bg-success';
                                            ?>
                                            <span class="badge <?= $badgeColor ?>"><?= ucfirst(htmlspecialchars($status)) ?></span>
                                        </td>
                                        <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                                    </tr>
                                <?php elseif ($roleId === 21): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td class="fw-semibold text-primary"><?= htmlspecialchars($row['project_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($row['designer_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($row['company_name'] ?? 'N/A') ?></td>
                                        <td>
                                            <?php if (!empty($row['design_link'])): ?>
                                                <a href="<?= htmlspecialchars($row['design_link']) ?>" target="_blank" class="text-decoration-none">
                                                    <i class="fa-solid fa-link me-1"></i>View Link
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">No Link</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-inbox d-block mb-2 fs-3"></i>
                                    No records found for <?= $selectedMonthName ?>.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Print Signatures Section -->
            <div class="row mt-5 pt-4 print-only-header">
                <div class="col-6 text-center">
                    <div style="border-top: 1px solid #000; width: 180px; margin: 0 auto 5px auto;"></div>
                    <p style="font-size: 12px; font-weight: 600;">Employee Signature</p>
                </div>
                <div class="col-6 text-center">
                    <div style="border-top: 1px solid #000; width: 180px; margin: 0 auto 5px auto;"></div>
                    <p style="font-size: 12px; font-weight: 600;">Authorized Approval</p>
                </div>
            </div>

        </div>
    </div>
</div>
