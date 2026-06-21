<?php
$base_url = $GLOBALS['base_url'];
$page_title = 'Site Progress Report';
$projects = $projects ?? [];
$siteEngineers = $siteEngineers ?? [];
$reportData = $reportData ?? [];
$filters = $filters ?? [];

$selProjectId = (int)($filters['project_id'] ?? 0);
$selSiteEngineerId = (int)($filters['site_engineer_id'] ?? 0);
$selInterval = $filters['interval'] ?? 'monthly';
$selYear = (int)($filters['year'] ?? date('Y'));
$selMonth = (int)($filters['month'] ?? date('n'));
$roleId = (int)($_SESSION['user']['role_id'] ?? 0);

// Stats calculation
$totalTarget = 0.0;
$totalAchieved = 0.0;
$totalGap = 0.0;
$totalRecovered = 0.0;
$starCount = 0;

foreach ($reportData as $row) {
    if ($row['target_mode'] === 'pct') {
        $totalTarget += (float)$row['planned_pct'];
        $totalAchieved += (float)$row['achieved_pct'];
        $totalGap += (float)$row['gap_pct'];
        $totalRecovered += (float)$row['recovered_pct'];
    } else {
        $totalTarget += (float)$row['planned_qty'];
        $totalAchieved += (float)$row['achieved_qty'];
        $totalGap += (float)$row['gap_qty'];
        $totalRecovered += (float)$row['recovered_qty'];
    }
    if ((int)$row['earned_star'] === 1) {
        $starCount++;
    }
}

$achievementRate = 0.0;
if ($totalTarget > 0) {
    $achievementRate = ($totalAchieved / $totalTarget) * 100;
}
?>

<!-- Print Styling -->
<style>
@media print {
    #sidebar, .app-header, .main-sidebar-header, .sidebar-left, .breadcrumb-area, .filter-card, .btn-print, footer, .main-footer {
        display: none !important;
    }
    .main-content {
        margin: 0 !important;
        padding: 0 !important;
    }
    .container-fluid {
        padding: 0 !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .table {
        width: 100% !important;
        border-collapse: collapse !important;
    }
    .table th, .table td {
        border: 1px solid #ddd !important;
        padding: 8px !important;
        font-size: 11px !important;
    }
    .print-only-header {
        display: block !important;
    }
}
.print-only-header {
    display: none;
}
.report-badge {
    font-size: 11px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
    white-space: nowrap;
}
.badge-completed { background-color: rgba(25, 135, 84, 0.1); color: #198754; border: 1px solid rgba(25, 135, 84, 0.2); }
.badge-progress { background-color: rgba(13, 202, 240, 0.1); color: #0dcaf0; border: 1px solid rgba(13, 202, 240, 0.2); }
.badge-delayed { background-color: rgba(220, 53, 69, 0.1); color: #dc3545; border: 1px solid rgba(220, 53, 69, 0.2); }
.badge-notstarted { background-color: rgba(108, 117, 125, 0.1); color: #6c757d; border: 1px solid rgba(108, 117, 125, 0.2); }
</style>

<!-- ══ PRINT METADATA HEADER ══ -->
<div class="print-only-header mb-4" style="font-family: 'Inter', sans-serif; font-size:12px; border-bottom:2.5px solid #1e293b; padding-bottom:12px; margin-bottom:20px;">
    <div style="display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <h2 style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0;">Site Progress Report</h2>
            <div style="font-size: 12px; color: #475569; margin-top: 4px;">The Premium Homes Ltd.</div>
        </div>
        <div style="text-align: right; font-size: 11px; color: #475569;">
            <strong>Print Date:</strong> <?= date('d M Y, h:i A') ?>
        </div>
    </div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; padding-top: 10px; border-top: 1px dashed #cbd5e1;">
        <div>
            <strong>Site Engineer:</strong> 
            <?php 
                if ($selSiteEngineerId > 0) {
                    $engName = "Unknown";
                    foreach ($siteEngineers as $eng) {
                        if ((int)$eng['id'] === $selSiteEngineerId) {
                            $engName = $eng['full_name'];
                            break;
                        }
                    }
                    echo htmlspecialchars($engName);
                } else {
                    if ($roleId === 12) {
                        echo htmlspecialchars($_SESSION['user']['full_name'] ?? '');
                    } else {
                        echo "All Engineers (সকল ইঞ্জিনিয়ার)";
                    }
                }
            ?>
        </div>
        <div>
            <strong>Period:</strong> 
            <?php 
                $dispMonth = ($selMonth > 0) ? date('F', mktime(0, 0, 0, $selMonth, 10)) : 'All Months';
                echo ucfirst($selInterval) . " ($dispMonth $selYear)";
            ?>
        </div>
    </div>
</div>

<!-- ══ PAGE TITLE ══ -->
<div class="pagetitle d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 style="font-size:1.25rem;font-weight:800;color:#1e293b;margin:0;">
            <i class="bi bi-file-earmark-bar-graph me-2" style="color:var(--clr-theme-primary);"></i>
            Site Progress Report
        </h1>
    </div>
    <div>
        <button onclick="window.print()" class="btn btn-primary btn-print" style="border-radius:8px;font-size:13px;font-weight:600;">
            <i class="bi bi-printer me-1"></i> Print Report
        </button>
    </div>
</div>

<!-- ══ STATS OVERVIEW CARDS ══ -->
<div class="row  mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius:12px; background:#fff; padding:16px 20px;">
            <div style="font-size:12px; color:#64748b; font-weight:600;">Total Planned / Target</div>
            <div style="font-size:20px; font-weight:800; color:#1e293b; margin-top:6px;">
                <?= number_format($totalTarget, 2) ?>
            </div>
            <div style="font-size:11px; color:#94a3b8; margin-top:2px;">Cumulative Target Qty / Pct</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius:12px; background:#fff; padding:16px 20px;">
            <div style="font-size:12px; color:#64748b; font-weight:600;">Total Achieved</div>
            <div style="font-size:20px; font-weight:800; color:#198754; margin-top:6px;">
                <?= number_format($totalAchieved, 2) ?>
            </div>
            <div style="font-size:11px; color:#198754; font-weight:600; margin-top:2px;">
                Rate: <?= number_format($achievementRate, 1) ?>%
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius:12px; background:#fff; padding:16px 20px;">
            <div style="font-size:12px; color:#64748b; font-weight:600;">Total Delay / Shortfall</div>
            <div style="font-size:20px; font-weight:800; color:#dc3545; margin-top:6px;">
                <?= number_format($totalGap, 2) ?>
            </div>
            <div style="font-size:11px; color:#94a3b8; margin-top:2px;">Unfinished Backlog</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius:12px; background:#fff; padding:16px 20px;">
            <div style="font-size:12px; color:#64748b; font-weight:600;">Recovered Backlog & Star</div>
            <div style="font-size:20px; font-weight:800; color:#0dcaf0; margin-top:6px; display:flex; align-items:center; gap:8px;">
                <?= number_format($totalRecovered, 2) ?>
                <?php if ($starCount > 0): ?>
                    <span style="font-size:16px; color:#f59e0b;"><i class="bi bi-star-fill"></i> (<?= $starCount ?>)</span>
                <?php endif; ?>
            </div>
            <div style="font-size:11px; color:#94a3b8; margin-top:2px;">Recovered Qty / Pct</div>
        </div>
    </div>
</div>

<!-- ══ FILTER CARD ══ -->
<div class="card border-0 shadow-sm filter-card mb-4" style="border-radius:12px; background:#fff;">
    <div class="card-body p-3">
        <form method="GET" action="<?= $base_url ?>/con-progress-report">
            <div class="row g-2 align-items-end">
                <!-- Project Selector -->
                <div class="<?= !empty($siteEngineers) ? 'col-md-2' : 'col-md-3' ?>">
                    <label style="font-size:12px; font-weight:700; color:#475569; margin-bottom:5px; display:block;">Project</label>
                    <select name="project_id" class="form-select" style="border-radius:8px; font-size:13px; border:1.5px solid #cbd5e1; height:38px;">
                        <option value="0">All Projects (সকল প্রজেক্ট)</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $selProjectId === (int)$p['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Site Engineer Selector (Shown only for Admin, PM, PM Head, Super Admin) -->
                <?php if (!empty($siteEngineers)): ?>
                    <div class="col-md-3">
                        <label style="font-size:12px; font-weight:700; color:#475569; margin-bottom:5px; display:block;">Site Engineer</label>
                        <select name="site_engineer_id" class="form-select" style="border-radius:8px; font-size:13px; border:1.5px solid #cbd5e1; height:38px;">
                            <option value="0">All Engineers (সকল ইঞ্জিনিয়ার)</option>
                            <?php foreach ($siteEngineers as $eng): ?>
                                <option value="<?= $eng['id'] ?>" <?= $selSiteEngineerId === (int)$eng['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($eng['full_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <!-- Interval Selector -->
                <div class="col-md-2">
                    <label style="font-size:12px; font-weight:700; color:#475569; margin-bottom:5px; display:block;">Report Interval</label>
                    <select name="interval" id="intervalSelector" class="form-select" style="border-radius:8px; font-size:13px; border:1.5px solid #cbd5e1; height:38px;">
                        <option value="weekly" <?= $selInterval === 'weekly' ? 'selected' : '' ?>>Weekly (সাপ্তাহিক)</option>
                        <option value="15-day" <?= $selInterval === '15-day' ? 'selected' : '' ?>>15-Day (১৫-দিনের)</option>
                        <option value="monthly" <?= $selInterval === 'monthly' ? 'selected' : '' ?>>Monthly (মাসিক)</option>
                        <option value="quarterly" <?= $selInterval === 'quarterly' ? 'selected' : '' ?>>Quarterly (ত্রৈমাসিক)</option>
                        <option value="yearly" <?= $selInterval === 'yearly' ? 'selected' : '' ?>>Yearly (বার্ষিক)</option>
                    </select>
                </div>

                <!-- Year Selector -->
                <div class="<?= !empty($siteEngineers) ? 'col-md-1' : 'col-md-2' ?>">
                    <label style="font-size:12px; font-weight:700; color:#475569; margin-bottom:5px; display:block;">Year</label>
                    <select name="year" class="form-select" style="border-radius:8px; font-size:13px; border:1.5px solid #cbd5e1; height:38px;">
                        <?php
                        $startYear = (int)date('Y') - 3;
                        $endYear = (int)date('Y') + 2;
                        for ($y = $startYear; $y <= $endYear; $y++):
                        ?>
                            <option value="<?= $y ?>" <?= $selYear === $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <!-- Month Selector -->
                <div class="col-md-2" id="monthContainer">
                    <label style="font-size:12px; font-weight:700; color:#475569; margin-bottom:5px; display:block;">Month</label>
                    <select name="month" class="form-select" style="border-radius:8px; font-size:13px; border:1.5px solid #cbd5e1; height:38px;">
                        <option value="0">All Months (সকল মাস)</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $selMonth === $m ? 'selected' : '' ?>>
                                <?= date('F', mktime(0, 0, 0, $m, 10)) ?> (<?= $m ?>)
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <!-- Buttons -->
                <div class="<?= !empty($siteEngineers) ? 'col-md-2' : 'col-md-3' ?> d-flex gap-2">
                    <button type="submit" class="btn btn-success flex-grow-1" style="border-radius:8px; font-size:13px; font-weight:600; height:38px; background:#198754; border-color:#198754;">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <a href="<?= $base_url ?>/con-progress-report" class="btn btn-light" style="border-radius:8px; font-size:13px; font-weight:600; height:38px; border:1.5px solid #cbd5e1;">
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ══ REPORT DATA CARD ══ -->
<div class="card border-0 shadow-sm" style="border-radius:12px; ">
    <div class="card-body p-0">
        <?php if (empty($reportData)): ?>
            <div style="text-align:center; padding:60px 20px; ">
                <i class="bi bi-folder2-open" style="font-size:40px;  display:block; margin-bottom:12px;"></i>
                <div style="font-size:14px; font-weight:600;">কোনো প্রগ্রেস ডাটা পাওয়া যায়নি</div>
                <div style="font-size:12px; opacity:0.8; margin-top:4px;">দয়া করে প্রজেক্ট, পিরিয়ড বা ফিল্টার পরিবর্তন করে চেষ্টা করুন।</div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:13px;">
                    <thead class="table-light" style="font-weight:700; border-bottom:1.5px solid #e2e8f0;">
                        <tr>
                            <th style="padding:14px 16px;">Project & Phase Item Details</th>
                            <th style="padding:14px 10px; text-align:center;">Period</th>
                            <th style="padding:14px 10px; text-align:right;">Target</th>
                            <th style="padding:14px 10px; text-align:right;">Achieved</th>
                            <th style="padding:14px 10px; text-align:right;">Backlog (Carryover)</th>
                            <th style="padding:14px 10px; text-align:right;">Gap (Delay)</th>
                            <th style="padding:14px 10px; text-align:right;">Recovered</th>
                            <th style="padding:14px 16px;">Backlog Recovery Details (উদ্ধার বিবরণী)</th>
                            <th style="padding:14px 10px; text-align:center;">Status</th>
                        </tr>
                    </thead>
                    <tbody style="border-top:0;">
                        <?php foreach ($reportData as $row):
                            $targetMode = $row['target_mode'] ?? 'qty';
                            $unit = $row['unit'] ?? '';

                            // Calculate Target and Achieved display values
                            if ($targetMode === 'pct') {
                                $dispTarget = number_format($row['planned_pct'], 2) . '%';
                                $dispAchieved = number_format($row['achieved_pct'], 2) . '%';
                                $dispCarryover = number_format($row['carryover_pct'], 2) . '%';
                                $dispGap = number_format($row['gap_pct'], 2) . '%';
                                $dispRecovered = number_format($row['recovered_pct'], 2) . '%';
                            } else {
                                $dispTarget = number_format($row['planned_qty'], 2) . ' ' . $unit;
                                $dispAchieved = number_format($row['achieved_qty'], 2) . ' ' . $unit;
                                $dispCarryover = number_format($row['carryover_qty'], 2) . ' ' . $unit;
                                $dispGap = number_format($row['gap_qty'], 2) . ' ' . $unit;
                                $dispRecovered = number_format($row['recovered_qty'], 2) . ' ' . $unit;
                            }

                            // Period Column formatting
                            $dispPeriod = "";
                            if ($selInterval === 'weekly') {
                                $dispPeriod = "Week " . $row['week_number'];
                            } elseif ($selInterval === '15-day') {
                                $dispPeriod = ((int)$row['period_number'] === 1) ? "1st Fortnight" : "2nd Fortnight";
                            } elseif ($selInterval === 'monthly') {
                                $dispPeriod = date('F', mktime(0, 0, 0, (int)$row['period_number'], 10));
                            } elseif ($selInterval === 'quarterly') {
                                $dispPeriod = "Quarter " . $row['period_number'];
                            } else { // yearly
                                $dispPeriod = $row['period_number'];
                            }

                            // Status badge class
                            $targetVal = ($targetMode === 'pct') ? (float)$row['planned_pct'] : (float)$row['planned_qty'];
                            $achievedVal = ($targetMode === 'pct') ? (float)$row['achieved_pct'] : (float)$row['achieved_qty'];
                            
                            if ($targetVal > 0 && $achievedVal >= $targetVal) {
                                $statusVal = 'Completed';
                            } elseif ($achievedVal > 0) {
                                $statusVal = 'In Progress';
                            } else {
                                $statusVal = $row['status'] ?? 'Not Started';
                            }

                            $badgeClass = match($statusVal) {
                                'Completed' => 'badge-completed',
                                'In Progress' => 'badge-progress',
                                'Delayed' => 'badge-delayed',
                                default => 'badge-notstarted'
                            };
                        ?>
                            <tr style="border-bottom:1px solid #f1f5f9;">
                                <td style="padding:14px 16px;">
                                    <div style="font-weight:700;">
                                        <?= htmlspecialchars($row['work_item_name']) ?>
                                        <?php if ((int)$row['earned_star'] === 1): ?>
                                            <span style="color:#f59e0b; margin-left:4px;" title="Earned Star"><i class="bi bi-star-fill"></i></span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size:11px;  margin-top:2px;">
                                        <i class="bi bi-building me-1"></i> <?= htmlspecialchars($row['project_name']) ?> 
                                        &bull; Month: <?= date('M Y', strtotime($row['target_month'])) ?>
                                        <?php if (!empty($row['site_engineer_name'])): ?>
                                            &bull; Engineer: <?= htmlspecialchars($row['site_engineer_name']) ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="padding:14px 10px; text-align:center; font-weight:600; color:#475569;">
                                    <?= $dispPeriod ?>
                                </td>
                                <td style="padding:14px 10px; text-align:right; font-weight:600; color:#0284c7;">
                                    <?= $dispTarget ?>
                                </td>
                                <td style="padding:14px 10px; text-align:right; font-weight:600; color:#16a34a;">
                                    <?= $dispAchieved ?>
                                </td>
                                <td style="padding:14px 10px; text-align:right; color:#64748b;">
                                    <?= $dispCarryover ?>
                                </td>
                                <td style="padding:14px 10px; text-align:right; font-weight:600; color:#dc3545;">
                                    <?= $dispGap ?>
                                </td>
                                <td style="padding:14px 10px; text-align:right; font-weight:600; color:#0891b2;">
                                    <?= $dispRecovered ?>
                                </td>
                                <td style="padding:14px 16px; font-size:12px; color:#475569; max-width:300px; line-height:1.4;">
                                    <?php if (!empty($row['recovery_details'])): ?>
                                        <span class="text-success"><i class="bi bi-info-circle me-1"></i> <?= htmlspecialchars($row['recovery_details']) ?></span>
                                    <?php else: ?>
                                        <span style="color:#94a3b8; font-style:italic;">No Backlog Recovery</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:14px 10px; text-align:center;">
                                    <span class="report-badge <?= $badgeClass ?>"><?= htmlspecialchars($statusVal) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ══ SCRIPT TO ADJUST FILTERS DYNAMICALLY ══ -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const selector = document.getElementById("intervalSelector");
    const monthContainer = document.getElementById("monthContainer");

    function adjustMonthVisibility() {
        const val = selector.value;
        if (val === 'quarterly' || val === 'yearly') {
            monthContainer.style.display = 'none';
        } else {
            monthContainer.style.display = 'block';
        }
    }

    selector.addEventListener("change", adjustMonthVisibility);
    adjustMonthVisibility(); // init on load
});
</script>
