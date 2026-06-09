<?php
$base_url = $GLOBALS['base_url'];

if (!function_exists('toBengaliNum')) {
    function toBengaliNum($number) {
        return (string)$number;
    }
}

if (!function_exists('cpFmtPct')) {
    function cpFmtPct($n) {
        $n = (float)$n;
        if ($n <= 0) return '0.0%';
        if ($n < 0.1) return number_format($n, 3) . '%';
        return number_format($n, 1) . '%';
    }
}

if (!function_exists('getBengaliMonthName')) {
    function getBengaliMonthName($dateStr) {
        if (empty($dateStr)) return '';
        $bengaliMonths = [
            'January' => 'জানুয়ারি', 'February' => 'ফেব্রুয়ারি', 'March' => 'মার্চ',
            'April' => 'এপ্রিল', 'May' => 'মে', 'June' => 'জুন',
            'July' => 'জুলাই', 'August' => 'আগস্ট', 'September' => 'সেপ্টেম্বর',
            'October' => 'অক্টোবর', 'November' => 'নভেম্বর', 'December' => 'ডিসেম্বর'
        ];
        $engMonth = date('F', strtotime($dateStr));
        return $bengaliMonths[$engMonth] ?? '';
    }
}
?>

<style>
    /* Styling for progress bars and status pills in Assigned Projects table */
    .prog-compare-wrap {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .prog-bar-container {
        width: 100%;
        border-radius: 99px;
        overflow: hidden;
        background: #f1f5f9;
    }
    .prog-bar-fill-planned {
        border-radius: 99px;
        transition: width 0.3s ease;
    }
    .prog-bar-fill-actual {
        border-radius: 99px;
        transition: width 0.3s ease;
    }

    /* Status Pills */
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 10px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 99px;
        letter-spacing: 0.2px;
        white-space: nowrap;
    }
    .status-pill-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        display: inline-block;
    }
    .status-ontrack { background: rgba(25, 135, 84, 0.08); color: #198754; }
    .status-ontrack .status-pill-dot { background: #198754; }
    .status-atrisk { background: rgba(245, 158, 11, 0.08); color: #d97706; }
    .status-atrisk .status-pill-dot { background: #f59e0b; }
    .status-delayed { background: rgba(239, 68, 68, 0.08); color: #dc2626; }
    .status-delayed .status-pill-dot { background: #ef4444; }
    .status-planning { background: rgba(59, 130, 246, 0.08); color: #2563eb; }
    .status-planning .status-pill-dot { background: #3b82f6; }
    .status-completed { background: rgba(16, 185, 129, 0.08); color: #059669; }
    .status-completed .status-pill-dot { background: #10b981; }

    /* Phase items container styling */
    .phase-items-container {
        animation: slideDown 0.2s ease-out;
    }
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-5px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

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

    <!-- Row 1: My Projects (Full Width) -->
    <div class="row">
        <!-- My Projects -->
        <div class="col-xl-12 col-12">
            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">My Assigned Projects</div>
                    <a href="<?= $base_url ?>/con-projects" class="btn btn-sm btn-primary btn-wave">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table text-nowrap table-hover mb-0" id="projectOverviewTable">
                            <thead>
                                <tr>
                                    <th style="width: 25%;">Project & Location</th>
                                    <th style="width: 15%;">Current Phase</th>
                                    <th style="width: 30%;">Planned vs Actual Progress</th>
                                    <th style="width: 10%;">Status</th>
                                    <th style="width: 10%;">Handover Target</th>
                                    <th style="width: 8%;">Budget Usage</th>
                                    <th style="width: 2%; text-align: center;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($myProjects)): ?>
                                    <tr><td colspan="7" class="text-center text-muted">No projects assigned to you.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($myProjects as $pr):
                                        $pct = round((float) ($pr['progress_pct'] ?? 0));
                                        $plPct = round((float) ($pr['planned_progress_pct'] ?? 0));
                                        $status = $pr['calculated_status'] ?? $pr['project_status'] ?? 'Planning';
                                        $isPhaseBased = ($pr['workflow_version'] ?? '') === 'phase_based';
                                        
                                        $statusClass = 'status-planning';
                                        if ($status === 'On Track') $statusClass = 'status-ontrack';
                                        elseif ($status === 'At Risk') $statusClass = 'status-atrisk';
                                        elseif ($status === 'Delayed') $statusClass = 'status-delayed';
                                        elseif ($status === 'Completed') $statusClass = 'status-completed';

                                        $detailUrl = ($isPhaseBased) 
                                            ? $base_url . '/con-project-timeline/' . $pr['id'] 
                                            : $base_url . '/con-project/' . $pr['id'];

                                        $budgetDetailUrl = !empty($pr['budget_id'])
                                            ? $base_url . '/con-estimate-show/' . $pr['budget_id']
                                            : $detailUrl;

                                        $budgetPct = $pr['total_budget'] > 0 ? round(($pr['total_spent'] / $pr['total_budget']) * 100) : 0;

                                        // Construct Overall Progress Popover Content
                                        $plaLines = [];
                                        $actLines = [];
                                        if (!empty($pr['phases'])) {
                                            foreach ($pr['phases'] as $phase) {
                                                if ((float)$phase['total_boq'] > 0) {
                                                    $phName = $phase['phase_name'];
                                                    $phWeight = (float)$phase['phase_weight'];
                                                    
                                                    // Month name
                                                    $targetMonthBn = 'জুনের'; // default
                                                    if (!empty($phase['target_month'])) {
                                                        $bnMonth = getBengaliMonthName($phase['target_month']);
                                                        $targetMonthBn = ($bnMonth === 'জুন') ? 'জুনের' : $bnMonth . ' মাসের';
                                                    }
                                                    
                                                    // Planned
                                                    $phPlannedVal = (float)$phase['phase_planned_weighted_pct'];
                                                    $phPlannedContrib = $phPlannedVal * ($phWeight / 100);
                                                    if ($phPlannedVal > 0) {
                                                        $bnWeight = toBengaliNum(round($phWeight, 1));
                                                        $bnPlanned = toBengaliNum(round($phPlannedVal, 1));
                                                        $bnPlannedContrib = toBengaliNum(round($phPlannedContrib, 1));
                                                        $plaLines[] = "{$phName} ফেজের {$targetMonthBn} নিজস্ব প্ল্যান ({$bnPlanned}%) × প্রজেক্টে এই ফেজের গুরুত্ব ({$bnWeight}%) = {$bnPlannedContrib}%";
                                                    }
                                                    
                                                    // Actual
                                                    $phActualVal = (float)$phase['phase_actual_weighted_pct'];
                                                    $phActualContrib = $phActualVal * ($phWeight / 100);
                                                    if ($phActualVal > 0) {
                                                        $bnWeight = toBengaliNum(round($phWeight, 1));
                                                        $bnActual = toBengaliNum(round($phActualVal, 2));
                                                        $bnActualContrib = toBengaliNum(round($phActualContrib, 2));
                                                        $actLines[] = "{$phName} ফেজের {$targetMonthBn} নিজস্ব প্রকৃত অগ্রগতি ({$bnActual}%) × প্রজেক্টে এই ফেজের গুরুত্ব ({$bnWeight}%) = {$bnActualContrib}%";
                                                    }
                                                }
                                            }
                                        }
                                        
                                        $formattedPla = ($pr['planned_progress_pct'] > 0 && $pr['planned_progress_pct'] < 0.1) ? number_format($pr['planned_progress_pct'], 3) : number_format($pr['planned_progress_pct'], 1);
                                        $formattedAct = ($pr['progress_pct'] > 0 && $pr['progress_pct'] < 0.1) ? number_format($pr['progress_pct'], 3) : number_format($pr['progress_pct'], 1);

                                        $plaPopoverContent = "<strong>ℹ️ সামগ্রিক পরিকল্পিত অগ্রগতি (Overall Planned):</strong><br/>"
                                                           . (empty($plaLines) ? "কোনো পরিকল্পিত অগ্রগতি নেই।" : "হিসাব: " . implode(" + ", $plaLines) . " = " . toBengaliNum($formattedPla) . "% (পুরো প্রজেক্টের সাপেক্ষে)");
                                                           
                                        $actPopoverContent = "<strong>ℹ️ সামগ্রিক প্রকৃত অগ্রগতি (Overall Actual):</strong><br/>"
                                                           . (empty($actLines) ? "কোনো প্রকৃত অগ্রগতি নেই।" : "হিসাব: " . implode(" + ", $actLines) . " = " . toBengaliNum($formattedAct) . "% (পুরো প্রজেক্টের সাপেক্ষে)");
                                        
                                        // Find current active phase
                                        $currentPhaseName = 'N/A';
                                        $currentPhaseSub = 'No active phases';
                                        if (!empty($pr['phases'])) {
                                            $totalPhases = count($pr['phases']);
                                            $currentPhaseIdx = 1;
                                            foreach ($pr['phases'] as $idx => $phase) {
                                                if ((float)$phase['phase_actual_pct'] < 100) {
                                                    $currentPhaseName = $phase['phase_name'];
                                                    $currentPhaseIdx = $idx + 1;
                                                    break;
                                                }
                                            }
                                            // If all phases are completed, default to the last phase
                                            if ($currentPhaseName === 'N/A') {
                                                $lastPhase = end($pr['phases']);
                                                $currentPhaseName = $lastPhase['phase_name'];
                                                $currentPhaseIdx = $totalPhases;
                                            }
                                            $currentPhaseSub = "Phase " . $currentPhaseIdx . " of " . $totalPhases;
                                        }
                                        
                                        // Dynamic building color gradient for thumbnail
                                        $colors = ['#4f46e5', '#0891b2', '#0d9488', '#ea580c', '#e11d48', '#2563eb'];
                                        $gradColor = $colors[$pr['id'] % count($colors)];
                                        ?>
                                        <tr class="project-row">
                                            <!-- Column 1: Project & Location with Thumbnail & Phase Toggle -->
                                            <td style="padding: 16px; vertical-align: middle;">
                                                <div style="display: flex; align-items: center; gap: 12px;">
                                                    <!-- Phase Toggle Button -->
                                                    <?php if (!empty($pr['phases'])): ?>
                                                        <button onclick="togglePhaseItems('phases-breakdown-<?= $pr['id'] ?>', this.querySelector('i'))" 
                                                                style="border: none; background: transparent; padding: 4px; color: #94a3b8; cursor: pointer; transition: color 0.15s; outline: none;">
                                                            <i class="fa-solid fa-eye" style="font-size: 13px;"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <span style="width: 19px; display: inline-block;"></span>
                                                    <?php endif; ?>

                                                    <!-- Thumbnail/Image placeholder -->
                                                    <?php if (!empty($pr['image'])): ?>
                                                        <div style="width: 42px; height: 42px; border-radius: 8px; overflow: hidden; flex-shrink: 0; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                                                            <img src="<?= getImage('projects', $pr['image']) ?>" alt="<?= htmlspecialchars($pr['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                                        </div>
                                                    <?php else: ?>
                                                        <div style="width: 42px; height: 42px; border-radius: 8px; background: linear-gradient(135deg, <?= $gradColor ?>, <?= $gradColor ?>dd); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 700; flex-shrink: 0; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                                                            <i class="fa-solid fa-building"></i>
                                                        </div>
                                                    <?php endif; ?>

                                                    <div>
                                                        <a href="<?= $detailUrl ?>" class="proj-title" style="font-size: 13.5px; font-weight: 700; color: #0f172a; text-decoration: none; display: block;"><?= htmlspecialchars($pr['name']) ?></a>
                                                        <div class="proj-loc" style="font-size: 11px; color: #64748b; margin-top: 2px; display: flex; align-items: center; gap: 4px;">
                                                            <i class="fa-solid fa-location-dot" style="font-size: 10px;"></i>
                                                            <?= htmlspecialchars($pr['location_name'] ?: 'N/A') ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            
                                            <!-- Column 2: Current Phase -->
                                            <td style="padding: 16px; vertical-align: middle;">
                                                <div style="font-weight: 700; color: #1e293b; font-size: 13px;"><?= htmlspecialchars($currentPhaseName) ?></div>
                                                <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;"><?= $currentPhaseSub ?></div>
                                            </td>
                                            
                                            <!-- Column 3: Planned vs Actual Progress (Dual stacked horizontal bars) -->
                                            <td style="padding: 16px; vertical-align: middle; min-width: 200px;">
                                                <div class="prog-compare-wrap">
                                                    <!-- Planned Bar -->
                                                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                                                        <span style="font-size: 10px; font-weight: 700; color: #94a3b8; min-width: 24px;">Pla</span>
                                                        <div class="prog-bar-container" style="height: 6px; flex: 1; background: #f1f5f9;">
                                                            <div class="prog-bar-fill-planned" style="width: <?= min(100, $pr['planned_progress_pct']) ?>%; height: 100%; background: #6366f1; opacity: 0.85;"></div>
                                                        </div>
                                                        <span style="font-size: 11px; font-weight: 800; color: #6366f1; min-width: 32px; text-align: right; display: flex; align-items: center; gap: 4px;">
                                                            <?= cpFmtPct($pr['planned_progress_pct']) ?>
                                                            <span style="font-size: 10px; cursor: pointer; color: #94a3b8;" data-bs-toggle="popover" data-bs-content="<?= htmlspecialchars($plaPopoverContent) ?>">ℹ️</span>
                                                        </span>
                                                    </div>
                                                    <!-- Actual Bar -->
                                                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                                                        <span style="font-size: 10px; font-weight: 700; color: #94a3b8; min-width: 24px;">Act</span>
                                                        <div class="prog-bar-container" style="height: 6px; flex: 1; background: #f1f5f9;">
                                                            <div class="prog-bar-fill-actual" style="width: <?= min(100, $pr['progress_pct']) ?>%; height: 100%; background: #198754;"></div>
                                                        </div>
                                                        <span style="font-size: 11px; font-weight: 800; color: #198754; min-width: 32px; text-align: right; display: flex; align-items: center; gap: 4px;">
                                                            <?= cpFmtPct($pr['progress_pct']) ?>
                                                            <span style="font-size: 10px; cursor: pointer; color: #94a3b8;" data-bs-toggle="popover" data-bs-content="<?= htmlspecialchars($actPopoverContent) ?>">ℹ️</span>
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                            
                                            <!-- Column 4: Status Badge -->
                                            <td style="padding: 16px; vertical-align: middle;">
                                                <span class="status-pill <?= $statusClass ?>">
                                                    <span class="status-pill-dot"></span>
                                                    <?= $status ?>
                                                </span>
                                            </td>
                                            
                                            <!-- Column 5: Handover Target -->
                                            <td style="padding: 16px; vertical-align: middle;">
                                                <div style="font-weight: 700; color: #1e293b; font-size: 13px;">
                                                    <?= $pr['expected_handover_date'] ? date('M Y', strtotime($pr['expected_handover_date'])) : 'N/A' ?>
                                                </div>
                                                <div style="font-size:11px; color: #94a3b8; margin-top:2px">
                                                    <?php if ($pr['expected_handover_date']):
                                                        $diff = time() - strtotime($pr['expected_handover_date']);
                                                        $months = abs(round($diff / (60 * 60 * 24 * 30)));
                                                        if ($diff < 0) {
                                                            echo "In $months months";
                                                        } else {
                                                            echo "$months months ago";
                                                        }
                                                    else:
                                                        echo 'Timeline unset';
                                                    endif; ?>
                                                </div>
                                            </td>
                                            
                                            <!-- Column 6: Budget Usage -->
                                            <td style="padding: 16px; vertical-align: middle;">
                                                <div style="font-weight: 700; color: #1e293b; font-size: 13px;"><?= cpFmt($pr['total_spent']) ?></div>
                                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: 4px;">
                                                    <div class="prog-bar-container" style="height: 4px; flex: 1; background: #f1f5f9;">
                                                        <div class="prog-bar-fill-actual" style="width: <?= min(100, $budgetPct) ?>%; height: 100%; background: <?= $budgetPct > 90 ? '#ef4444' : '#198754' ?>;"></div>
                                                    </div>
                                                    <span style="font-size: 10px; font-weight: 700; color: #64748b; min-width: 26px; text-align: right;"><?= $budgetPct ?>%</span>
                                                </div>
                                            </td>
                                            
                                            <!-- Column 7: Actions Menu -->
                                            <td style="padding: 16px; vertical-align: middle; text-align: center;">
                                                <button style="border: none; background: transparent; color: #94a3b8; cursor: pointer; padding: 4px; font-size: 14px; outline: none;" 
                                                        onclick="window.location.href='<?= $budgetDetailUrl ?>'">
                                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Nested Timeline Phases Detail Block (Collapsible) -->
                                        <?php if (!empty($pr['phases'])): ?>
                                            <tr id="phases-breakdown-<?= $pr['id'] ?>" class="phase-items-container" style="display: none; background: #fafbfc;">
                                                <td colspan="7" style="padding: 18px 24px; border-bottom: 1px solid rgba(0,0,0,0.05);">
                                                    <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                                                        <i class="fa-solid fa-folder-open text-primary"></i> Phase Progress Breakdown for <?= htmlspecialchars($pr['name']) ?>
                                                    </div>
                                                    
                                                    <div style="display: flex; flex-direction: column; gap: 12px;">
                                                        <?php foreach ($pr['phases'] as $phase): 
                                                            $phWeight = (float)$phase['phase_weight'];
                                                            $phaseActualProgress = (float)$phase['phase_actual_pct'];
                                                            $phaseContribution = (float)$phase['phase_contribution'];

                                                            // Compute dynamic tooltips for this phase
                                                            $totalApprovedVal = (float)$phase['total_approved_qty'];
                                                            $totalBOQVal = (float)$phase['total_boq'];
                                                            
                                                            $phaseProgressTooltip = "<strong>ℹ️ ফেজের নিজস্ব অগ্রগতি (Phase Internal Progress):</strong><br/>"
                                                                                  . "সূত্র: (অনুমোদিত কাজের ভলিউম ÷ মোট ফেজ BOQ) × ১০০<br/>"
                                                                                  . "হিসাব: (" . toBengaliNum(number_format($totalApprovedVal)) . " CFT ÷ " . toBengaliNum(number_format($totalBOQVal)) . " CFT) × ১০০ = " . toBengaliNum(number_format(round($phaseActualProgress, 1), 1)) . "%";

                                                            $phaseProgressVal2 = number_format($phaseActualProgress, 2);
                                                            $phaseWeightVal = number_format($phWeight, 1);
                                                            $phaseContributionVal = number_format($phaseContribution, 2);
                                                            
                                                            $phaseContributionTooltip = "<strong>ℹ️ প্রজেক্টে এই ফেজের অবদান (Project Contribution):</strong><br/>"
                                                                                      . "সূত্র: ফেজের বর্তমান প্রগ্রেস (" . toBengaliNum($phaseProgressVal2) . "%) × (ফেজ ওয়েট " . toBengaliNum($phaseWeightVal) . " ÷ ১০০) = " . toBengaliNum($phaseContributionVal) . "%";
                                                            ?>
                                                            <div style="display: grid; grid-template-columns: 200px 1fr; align-items: center; gap: 16px;">
                                                                <div style="font-size: 12px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                                                                     <?php if (!empty($phase['items'])): ?>
                                                                        <i class="fa-solid fa-chevron-right phase-toggle-icon" 
                                                                           onclick="togglePhaseItems('subitems-<?= $pr['id'] ?>-<?= $phase['phase_id'] ?>', this)" 
                                                                           style="cursor: pointer; font-size: 9px; width: 12px; color: #198754; transition: transform 0.15s; display: inline-block;"></i>
                                                                     <?php else: ?>
                                                                        <span style="width: 12px; display: inline-block;"></span>
                                                                     <?php endif; ?>
                                                                     <span style="<?php if (!empty($phase['items'])): ?>cursor: pointer;<?php endif; ?>" 
                                                                           onclick="<?php if (!empty($phase['items'])): ?>togglePhaseItems('subitems-<?= $pr['id'] ?>-<?= $phase['phase_id'] ?>', this.previousElementSibling)<?php endif; ?>">
                                                                         <?= htmlspecialchars($phase['phase_name']) ?>
                                                                     </span>
                                                                </div>
                                                                <div style="width: 100%;">
                                                                    <div style="font-size: 9px; font-weight: 800; display: flex; justify-content: space-between; margin-bottom: 2px;">
                                                                        <span style="color:#6366f1">Phase Weight: <?= number_format($phWeight, 1) ?>%</span>
                                                                        <span style="color:#198754; display: flex; align-items: center; gap: 4px;">
                                                                             Contribution: <?= number_format($phaseContribution, 2) ?>% 
                                                                             <span style="font-size: 9px; cursor: pointer; color: #94a3b8;" data-bs-toggle="popover" data-bs-content="<?= htmlspecialchars($phaseContributionTooltip) ?>">ℹ️</span>
                                                                             (<?= number_format($phaseActualProgress, 1) ?>% complete)
                                                                             <span style="font-size: 9px; cursor: pointer; color: #94a3b8;" data-bs-toggle="popover" data-bs-content="<?= htmlspecialchars($phaseProgressTooltip) ?>">ℹ️</span>
                                                                        </span>
                                                                    </div>
                                                                    <div class="prog-bar-container" style="height: 6px; background: #e2e8f0; position: relative;">
                                                                        <!-- Planned phase width (representing total phase weight) -->
                                                                        <div class="prog-bar-fill-planned" style="width: <?= $phWeight ?>%; height: 100%; background: #6366f1; opacity: 0.15; position: absolute; left: 0;"></div>
                                                                        <!-- Actual phase contribution fill -->
                                                                        <div class="prog-bar-fill-actual" style="width: <?= $phaseContribution ?>%; height: 100%; background: <?= $phaseActualProgress >= 100 ? '#198754' : '#f59e0b' ?>; position: absolute; left: 0;"></div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Nested Sub-items Loop -->
                                                            <?php if (!empty($phase['items'])): ?>
                                                                <div id="subitems-<?= $pr['id'] ?>-<?= $phase['phase_id'] ?>" class="phase-items-container" style="display: none; flex-direction: column; gap: 8px; margin-left: 24px; border-left: 2px solid #e2e8f0; padding-left: 14px; margin-top: 4px; margin-bottom: 4px;">
                                                                    <?php foreach ($phase['items'] as $item): 
                                                                        $itemWeight = (float)$item['item_weight'];
                                                                        $itemActualPct = (float)$item['item_actual_pct'];
                                                                        $itemProjWeight = ($itemWeight / 100) * $phWeight;
                                                                        $itemActWeightVal = ($itemActualPct / 100) * $itemProjWeight;
                                                                        ?>
                                                                        <div style="display: grid; grid-template-columns: 180px 1fr; align-items: center; gap: 16px;">
                                                                            <div style="font-size: 11.5px; font-weight: 600; color: #64748b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($item['item_name']) ?>">
                                                                                ↳ <?= htmlspecialchars($item['item_name']) ?>
                                                                            </div>
                                                                            <div style="width: 100%;">
                                                                                <div style="font-size: 8px; font-weight: 700; display: flex; justify-content: space-between; margin-bottom: 1px;">
                                                                                    <span style="color:#6366f1">Item Weight: <?= number_format($itemProjWeight, 2) ?>%</span>
                                                                                    <span style="color:#198754">
                                                                                        Contribution: <?= number_format($itemActWeightVal, 2) ?>% (<?= number_format($itemActualPct, 1) ?>% complete)
                                                                                        <?php if (!empty($item['qty_usage_text'])): ?>
                                                                                            &middot; <span style="color: #64748b; font-weight: 500;"><?= htmlspecialchars($item['qty_usage_text']) ?></span>
                                                                                        <?php endif; ?>
                                                                                    </span>
                                                                                </div>
                                                                                <div class="prog-bar-container" style="height: 4px; background: #f1f5f9; position: relative;">
                                                                                    <div class="prog-bar-fill-planned" style="width: <?= $itemProjWeight ?>%; height: 100%; background: #6366f1; opacity: 0.15; position: absolute; left: 0;"></div>
                                                                                    <div class="prog-bar-fill-actual" style="width: <?= $itemActWeightVal ?>%; height: 100%; background: <?= $itemActualPct >= 100 ? '#198754' : '#f59e0b' ?>; position: absolute; left: 0;"></div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: Recent Material Received, Recent Material Issued & Recent Weekly Reports (3-column layout) -->
    <div class="row">
        <!-- Recent Material Received -->
        <div class="col-xl-4 col-lg-4 col-md-12">
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

        <!-- Recent Material Issued -->
        <div class="col-xl-4 col-lg-4 col-md-12">
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
        <div class="col-xl-4 col-lg-4 col-md-12">
            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">Recent Weekly Reports</div>
                    <a href="<?= $base_url ?>/con-weekly-reports" class="btn btn-sm btn-primary btn-wave">View All</a>
                </div>
                <div class="card-body p-4 text-center">
                    <div class="mb-3">
                        <span class="avatar avatar-md bg-primary-transparent rounded-circle">
                            <i class="bi bi-bell-fill fs-18 text-primary"></i>
                        </span>
                    </div>
                    <h6 class="fw-semibold mb-1 fs-13">Weekly Report Updates</h6>
                    <p class="text-muted mb-3 fs-11">নিচের বাটনে ক্লিক করে প্রজেক্টের সাপ্তাহিক অগ্রগতি রিপোর্ট দেখুন এবং নতুন রিপোর্ট জমা দিন।</p>
                    <a href="<?= $base_url ?>/con-weekly-reports" class="btn btn-sm btn-primary-light w-100">
                        View &amp; Submit Reports →
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
(function() {
    // initialize Bootstrap 5 popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl, {
            html: true,
            trigger: 'hover'
        });
    });
})();

function togglePhaseItems(id, iconEl) {
    var el = document.getElementById(id);
    if (!el) return;
    if (el.style.display === 'none' || el.style.display === '') {
        el.style.display = (el.tagName === 'TR') ? 'table-row' : 'flex';
        if (iconEl) {
            if (iconEl.classList.contains('fa-eye')) {
                iconEl.classList.remove('fa-eye');
                iconEl.classList.add('fa-eye-slash');
            } else if (iconEl.classList.contains('fa-chevron-right')) {
                iconEl.classList.remove('fa-chevron-right');
                iconEl.classList.add('fa-chevron-down');
            }
        }
    } else {
        el.style.display = 'none';
        if (iconEl) {
            if (iconEl.classList.contains('fa-eye-slash')) {
                iconEl.classList.remove('fa-eye-slash');
                iconEl.classList.add('fa-eye');
            } else if (iconEl.classList.contains('fa-chevron-down')) {
                iconEl.classList.remove('fa-chevron-down');
                iconEl.classList.add('fa-chevron-right');
            }
        }
    }
}
<?php $page_script = ob_get_clean(); ?>

<?php
$pendingWorkOrders = $pendingWorkOrders ?? [];
if (!empty($pendingWorkOrders)):
?>
<div class="container-fluid mt-2 mb-4">
    <div class="card custom-card" style="border:2px solid rgba(14,165,233,.35);background:rgba(14,165,233,.02)">
        <div class="card-header justify-content-between" style="border-bottom:1px solid rgba(14,165,233,.2)">
            <div class="card-title d-flex align-items-center gap-2" style="color:#0284c7">
                <span style="width:10px;height:10px;border-radius:50%;background:#0ea5e9;display:inline-block;animation:pulse 1.5s infinite"></span>
                <i class="bi bi-file-earmark-text-fill text-info"></i>
                Work Orders Awaiting Your Approval
                <span class="badge bg-info ms-2" style="font-size:10px"><?= count($pendingWorkOrders) ?> Pending</span>
            </div>
            <a href="<?= $base_url ?>/con-work-orders" class="btn btn-sm btn-info-light">
                View All Work Orders →
            </a>
        </div>
        <div class="card-body p-0">
            <div class="alert alert-info border-0 rounded-0 mb-0 py-2 px-4" style="font-size:12px">
                <i class="bi bi-info-circle me-1"></i>
                নিচের Work Order গুলো আপনার এপ্রুভাল এর জন্য পেন্ডিং রয়েছে। রিভিউ করে এপ্রুভ করুন।
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:12.5px">
                    <thead class="table-info bg-opacity-25">
                        <tr>
                            <th>#</th>
                            <th>Project</th>
                            <th>Work Order Title</th>
                            <th>Contractor</th>
                            <th>Total Amount</th>
                            <th>Created By</th>
                            <th>Submitted Date</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingWorkOrders as $woIndex => $wo): ?>
                        <tr>
                            <td><?= $woIndex + 1 ?></td>
                            <td><strong><?= htmlspecialchars($wo['project_name'] ?? '—') ?></strong></td>
                            <td class="fw-semibold text-info"><?= htmlspecialchars($wo['title'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($wo['contractor_name'] ?? '—') ?></td>
                            <td class="fw-bold text-success">
                                ৳<?= number_format((float)$wo['total_amount'], 2) ?>
                            </td>
                            <td style="font-size:11px;color:#6b7280">
                                <?= htmlspecialchars($wo['creator_name'] ?? '—') ?>
                            </td>
                            <td style="font-size:11px;color:#6b7280">
                                <?= !empty($wo['created_at']) ? date('d M Y, h:i A', strtotime($wo['created_at'])) : '—' ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= $base_url ?>/con-work-order/<?= $wo['id'] ?>"
                                   class="btn btn-sm btn-info" style="font-size:11px;color:#fff">
                                    <i class="bi bi-check-circle"></i> Review &amp; Approve
                                </a>
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
