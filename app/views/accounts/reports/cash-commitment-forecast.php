<?php 
// Layout handled by Controller via master.php
$totalCommitment = $report['total_commitment'] ?? 0;
$commitments = $report['commitments'] ?? [];
?>

<div class="container-fluid mt-4">
    <!-- Header & Action Bar -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-7">
            <h2 class="mb-1 text-dark fw-bold">
                <i class="fa-light fa-chart-line-up text-success me-2"></i> Cash Flow Commitment Forecast
            </h2>
            <p class="text-muted mb-0 fs-13">
                ক্যাশ ফ্লো পূর্বাভাস — Upcoming Projected Cash Outflows for Management Approved Requisitions
            </p>
        </div>
        <div class="col-md-5 text-end">
            <button type="button" onclick="window.print()" class="btn btn-outline-secondary me-2">
                <i class="fa-light fa-print me-1"></i> Print Forecast
            </button>
            <a href="<?= $base_url ?>/financial-dashboard" class="btn btn-outline-primary">
                <i class="fa-light fa-chart-line me-1"></i> Financial Dashboard
            </a>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-4 rounded-3 text-white" style="background-color: #198754 !important;">
                <span class="fs-12 text-white-50 text-uppercase fw-bold">Projected 30-Day Cash Requirement</span>
                <h2 class="fw-bold mb-0 text-white mt-1">৳ <?= number_format($totalCommitment, 2) ?></h2>
                <small class="text-white-50 fs-11">Total required cash outlay for Management Approved requisitions</small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-4 rounded-3 bg-white border-start border-4" style="border-color: #198754 !important;">
                <span class="fs-12 text-muted text-uppercase fw-bold">Total Approved Commitments</span>
                <h2 class="fw-bold mb-0 text-dark mt-1"><?= count($commitments) ?> Requests</h2>
                <small class="text-muted fs-11">Across Fund Requisitions, Procurement, Vendor & Contractor Bills</small>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body py-3">
            <form method="GET" action="<?= $base_url ?>/reports/cash-commitment-forecast" class="row align-items-end">
                <div class="col-md-8">
                    <label class="form-label fs-11 fw-semibold mb-1">Filter by Project</label>
                    <select name="project_id" class="form-select form-select-sm">
                        <option value="">All Projects / Head Office</option>
                        <?php foreach ($projects as $proj): ?>
                            <option value="<?= $proj['id'] ?>" <?= ($filters['project_id'] == $proj['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($proj['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 text-end">
                    <button type="submit" class="btn btn-primary btn-sm px-4 me-1">
                        <i class="fa-light fa-filter me-1"></i> Apply Filter
                    </button>
                    <a href="<?= $base_url ?>/reports/cash-commitment-forecast" class="btn btn-light btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Forecast Table -->
    <div class="card shadow-sm border-0 mb-5">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="fa-light fa-list-check text-success me-2"></i> Management Approved Cash Commitments
            </h5>
            <span class="badge bg-light text-dark border">Real-Time Forecast</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark fs-12">
                        <tr>
                            <th>#</th>
                            <th>Module Category</th>
                            <th>Ref / Voucher No</th>
                            <th>Description / Purpose</th>
                            <th>Project / Location</th>
                            <th>Requested By</th>
                            <th>Req Date</th>
                            <th>Status</th>
                            <th class="text-end">Required Cash (BDT)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($commitments)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="fa-light fa-circle-check text-success me-1 fs-16"></i> No pending management approved commitments found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $sl = 1; 
                            foreach ($commitments as $c): 
                            ?>
                                <tr>
                                    <td><?= $sl++ ?></td>
                                    <td>
                                        <span class="badge bg-success-subtle text-success border px-2 py-1">
                                            <?= htmlspecialchars($c['module']) ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($c['ref_no']) ?></td>
                                    <td><?= htmlspecialchars($c['description']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($c['project_name']) ?></span></td>
                                    <td><?= htmlspecialchars($c['requested_by']) ?></td>
                                    <td class="fs-12 text-muted"><?= htmlspecialchars($c['req_date']) ?></td>
                                    <td><span class="badge bg-success text-white"><?= htmlspecialchars($c['status']) ?></span></td>
                                    <td class="text-end fw-bold text-dark font-monospace fs-14">৳ <?= number_format($c['amount'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="8" class="text-end">TOTAL PROJECTED CASH OUTFLOW REQUIRED:</td>
                            <td class="text-end text-success font-monospace fs-16">৳ <?= number_format($totalCommitment, 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
