<?php
$page_title = 'HR ATS - Recruitment Dashboard';
$baseUrl = $GLOBALS['base_url'];

$showUpRate = $metrics['show_up_rate'] ?? 0;
?>

<div class="container-fluid py-3 px-4">
    <!-- Header & Date Filter -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                <i class="fa-solid fa-chart-line text-primary"></i>
                Recruitment Analytics & Attendance Dashboard
            </h4>
            <p class="text-muted small mb-0">Track shortlisting volume, invitation calls, and past interview show-up rates.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-2 mt-md-0 align-items-center">
            <!-- Quick Preset Ranges -->
            <div class="btn-group btn-group-sm">
                <a href="<?= $baseUrl ?>/ats-dashboard?range=this_week" class="btn <?= ($range === 'this_week') ? 'btn-primary' : 'btn-outline-secondary' ?>">This Week</a>
                <a href="<?= $baseUrl ?>/ats-dashboard?range=this_month" class="btn <?= ($range === 'this_month') ? 'btn-primary' : 'btn-outline-secondary' ?>">This Month</a>
                <a href="<?= $baseUrl ?>/ats-dashboard?range=last_month" class="btn <?= ($range === 'last_month') ? 'btn-primary' : 'btn-outline-secondary' ?>">Last Month</a>
                <a href="<?= $baseUrl ?>/ats-dashboard?range=this_year" class="btn <?= ($range === 'this_year') ? 'btn-primary' : 'btn-outline-secondary' ?>">This Year</a>
                <a href="<?= $baseUrl ?>/ats-dashboard?range=all" class="btn <?= ($range === 'all') ? 'btn-primary' : 'btn-outline-secondary' ?>">All Time</a>
            </div>

            <a href="<?= $baseUrl ?>/ats-tracker" class="btn btn-outline-primary btn-sm px-3">
                <i class="fa-solid fa-table-list me-1"></i> Candidate Tracker
            </a>
        </div>
    </div>

    <!-- Custom Date Range Filter Form -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= $baseUrl ?>/ats-dashboard" class="row g-2 align-items-end">
                <input type="hidden" name="range" value="custom">
                <div class="col-auto">
                    <label class="form-label small fw-semibold text-muted mb-1"><i class="fa-regular fa-calendar me-1"></i>From Date</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="<?= htmlspecialchars($fromDate ?? '') ?>">
                </div>
                <div class="col-auto">
                    <label class="form-label small fw-semibold text-muted mb-1">To Date</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="<?= htmlspecialchars($toDate ?? '') ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm px-3">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Apply Filter
                    </button>
                </div>
                <?php if (!empty($fromDate) || !empty($toDate)): ?>
                    <div class="col-auto">
                        <span class="badge bg-light text-secondary border px-2 py-2 small">
                            Filtering from <strong><?= $fromDate ? date('d M Y', strtotime($fromDate)) : 'Start' ?></strong> to <strong><?= $toDate ? date('d M Y', strtotime($toDate)) : 'Now' ?></strong>
                        </span>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Key Metrics Cards -->
    <div class="row mb-4">
        <!-- 1. Shortlisted Candidates -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 h-100 position-relative overflow-hidden" 
                 style="background: linear-gradient(135deg, #f0f7ff 0%, #ffffff 100%); border-left: 4px solid #2563eb !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-uppercase fw-semibold text-primary" style="font-size: 0.72rem; letter-spacing: 0.05em;">Shortlisted Candidates</span>
                        <h2 class="fw-bold my-1 text-dark"><?= (int)$metrics['total_shortlisted'] ?></h2>
                        <span class="text-muted small" style="font-size: 0.75rem;">Total in selected range</span>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: rgba(37, 99, 235, 0.1); color: #2563eb;">
                        <i class="fa-solid fa-user-plus fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Calls / Invitations Given -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 h-100 position-relative overflow-hidden"
                 style="background: linear-gradient(135deg, #fefce8 0%, #ffffff 100%); border-left: 4px solid #eab308 !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-uppercase fw-semibold text-warning text-darken-2" style="font-size: 0.72rem; letter-spacing: 0.05em;">Invitations / Calls Given</span>
                        <h2 class="fw-bold my-1 text-dark"><?= (int)$metrics['total_invitations'] ?></h2>
                        <span class="text-muted small" style="font-size: 0.75rem;">Called & invited to interview</span>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: rgba(234, 179, 8, 0.12); color: #ca8a04;">
                        <i class="fa-solid fa-phone-volume fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Show-up Rate & Attendance (Crucial Metric from Requirement) -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 h-100 position-relative overflow-hidden"
                 style="background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%); border-left: 4px solid #16a34a !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-uppercase fw-semibold text-success" style="font-size: 0.72rem; letter-spacing: 0.05em;">Interview Show-up Rate</span>
                        <h2 class="fw-bold my-1 text-success"><?= $showUpRate ?>%</h2>
                        <span class="small text-muted" style="font-size: 0.75rem;">
                            <strong><?= $metrics['attended_count'] ?></strong> Present / <strong><?= $metrics['absent_count'] ?></strong> Absent
                        </span>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: rgba(22, 163, 74, 0.12); color: #16a34a;">
                        <i class="fa-solid fa-clipboard-user fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Hired / Onboarded -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 h-100 position-relative overflow-hidden"
                 style="background: linear-gradient(135deg, #faf5ff 0%, #ffffff 100%); border-left: 4px solid #9333ea !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-uppercase fw-semibold" style="color: #9333ea; font-size: 0.72rem; letter-spacing: 0.05em;">Selected / Hired</span>
                        <h2 class="fw-bold my-1 text-dark"><?= (int)$metrics['hired_count'] ?></h2>
                        <span class="text-muted small" style="font-size: 0.75rem;">Successfully converted</span>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: rgba(147, 51, 234, 0.12); color: #9333ea;">
                        <i class="fa-solid fa-handshake fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Progress Bar Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fa-solid fa-chart-simple text-success me-1"></i>
                        Candidate Attendance Performance (Past Interviews)
                    </h6>
                    <small class="text-muted">Calculated only for interviews whose scheduled date has already passed.</small>
                </div>
                <div class="text-end">
                    <span class="fw-bold fs-5 text-dark"><?= $showUpRate ?>%</span>
                    <span class="text-muted small d-block">Show-up Ratio</span>
                </div>
            </div>
            <div class="progress" style="height: 18px; border-radius: 8px;">
                <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" role="progressbar" 
                     style="width: <?= $showUpRate ?>%; font-size: 0.75rem;" 
                     aria-valuenow="<?= $showUpRate ?>" aria-valuemin="0" aria-valuemax="100">
                    <?= $showUpRate ?>% Present
                </div>
                <div class="progress-bar bg-danger bg-opacity-75" role="progressbar" 
                     style="width: <?= (100 - $showUpRate) ?>%; font-size: 0.75rem;" 
                     aria-valuenow="<?= (100 - $showUpRate) ?>" aria-valuemin="0" aria-valuemax="100">
                    <?= (100 - $showUpRate) ?>% Absent
                </div>
            </div>
            <div class="d-flex justify-content-between mt-2 small text-muted">
                <span><i class="fa-solid fa-circle text-success me-1"></i> Attended: <strong><?= (int)$metrics['attended_count'] ?></strong> candidates</span>
                <span><i class="fa-solid fa-circle text-danger me-1"></i> Absent: <strong><?= (int)$metrics['absent_count'] ?></strong> candidates</span>
                <span>Total Scheduled: <strong><?= (int)$metrics['passed_interviews'] ?></strong> past interviews</span>
            </div>
        </div>
    </div>

    <!-- Breakdown Tables Row -->
    <div class="row">
        <!-- Department Breakdown -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-light py-3 border-0">
                    <h6 class="fw-bold my-0 text-dark">
                        <i class="fa-solid fa-building text-primary me-1"></i> Department-wise Candidate Breakdown
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                        <thead class="table-light text-secondary small">
                            <tr>
                                <th>Department</th>
                                <th class="text-center">Candidates</th>
                                <th class="text-center">Attended</th>
                                <th class="text-center">Hired</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($metrics['department_breakdown'])): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">No data available.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($metrics['department_breakdown'] as $dept): ?>
                                    <tr>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($dept['department']) ?></td>
                                        <td class="text-center">
                                            <span class="badge px-2 py-1 rounded-pill" style="background: rgba(37, 99, 235, 0.12); color: #1d4ed8; font-weight: 600;">
                                                <?= (int)$dept['count'] ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge px-2 py-1 rounded-pill" style="background: rgba(16, 185, 129, 0.15); color: #047857; font-weight: 600;">
                                                <?= (int)$dept['present_count'] ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge px-2 py-1 rounded-pill" style="background: rgba(245, 158, 11, 0.15); color: #b45309; font-weight: 600;">
                                                <?= (int)$dept['hired_count'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sourcing Channels Breakdown -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-light py-3 border-0">
                    <h6 class="fw-bold my-0 text-dark">
                        <i class="fa-solid fa-bullhorn text-warning me-1"></i> Sourcing Channels (Where Candidates Come From)
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                        <thead class="table-light text-secondary small">
                            <tr>
                                <th>Source / Channel</th>
                                <th class="text-center">Total Candidates</th>
                                <th class="text-end px-3">Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($metrics['source_breakdown'])): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-3 text-muted">No source data available.</td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $totalSrc = max(1, array_sum(array_column($metrics['source_breakdown'], 'count')));
                                foreach ($metrics['source_breakdown'] as $src): 
                                    $pct = round(($src['count'] / $totalSrc) * 100, 1);
                                ?>
                                    <tr>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($src['source_name']) ?></td>
                                        <td class="text-center">
                                            <span class="badge px-2 py-1 rounded-pill" style="background: #e2e8f0; color: #1e293b; font-weight: 600;">
                                                <?= (int)$src['count'] ?>
                                            </span>
                                        </td>
                                        <td class="text-end px-3">
                                            <span class="small fw-bold text-muted"><?= $pct ?>%</span>
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
