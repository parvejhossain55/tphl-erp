<!-- main-content-start -->
<div class="main-content app-content">
    <div class="container-fluid">
        
        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0">Sales Activity Report</h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Sales</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Activity Report</li>
                    </ol>
                </nav>
            </div>
        </div>
        <!-- End Page Header -->

        <!-- Filters -->
        <div class="card custom-card">
            <div class="card-body">
                <form method="GET" action="<?= $base_url ?>/sales-activity-report">
                    <input type="hidden" name="tab" value="<?= htmlspecialchars($activeTab) ?>">
                    <div class="row g-2 align-items-end">
                        <?php if ($activeTab === 'daily'): ?>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Specific Date (Daily)</label>
                                <input type="date" name="date" class="form-control form-control-sm" value="<?= htmlspecialchars($date) ?>" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">
                                    <i class="fa-solid fa-users text-primary me-1"></i>Team
                                </label>
                                <select name="team_id" class="form-select form-select-sm">
                                    <option value="">All Teams (Company-Wide)</option>
                                    <?php if(!empty($teams)): foreach($teams as $t): ?>
                                        <option value="<?= $t['id'] ?>" <?= (!empty($selectedTeamId) && (int)$selectedTeamId === (int)$t['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($t['name']) ?>
                                        </option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">
                                    <i class="fa-solid fa-filter me-1"></i> Filter Daily
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Year</label>
                                <select name="year" class="form-select form-select-sm">
                                    <?php for($y = 2024; $y <= date('Y')+1; $y++): ?>
                                        <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Month</label>
                                <select name="month" class="form-select form-select-sm">
                                    <?php 
                                    $months = ['1'=>'Jan', '2'=>'Feb', '3'=>'Mar', '4'=>'Apr', '5'=>'May', '6'=>'Jun', '7'=>'Jul', '8'=>'Aug', '9'=>'Sep', '10'=>'Oct', '11'=>'Nov', '12'=>'Dec'];
                                    foreach($months as $m => $mName): 
                                    ?>
                                        <option value="<?= $m ?>" <?= (int)$m === (int)$month ? 'selected' : '' ?>><?= $mName ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">
                                    <i class="fa-solid fa-users text-primary me-1"></i>Team
                                </label>
                                <select name="team_id" class="form-select form-select-sm">
                                    <option value="">All Teams (Company-Wide)</option>
                                    <?php if(!empty($teams)): foreach($teams as $t): ?>
                                        <option value="<?= $t['id'] ?>" <?= (!empty($selectedTeamId) && (int)$selectedTeamId === (int)$t['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($t['name']) ?>
                                        </option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">
                                    <i class="fa-solid fa-filter me-1"></i> Filter Data
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </form>
                <div class="mt-3 text-muted d-flex align-items-center justify-content-between flex-wrap gap-2 fs-12">
                    <?php if($activeTab === 'daily'): ?>
                    <div>
                        <i class="fa-regular fa-calendar me-1 text-primary"></i>
                        Showing Daily Report for: <strong><?= date('d M, Y', strtotime($date)) ?></strong>
                    </div>
                    <?php elseif(!empty($monthlyData['period'])): ?>
                    <div>
                        <i class="fa-regular fa-calendar me-1 text-primary"></i>
                        Showing data for period: <strong><?= $monthlyData['period']['start'] ?></strong> to <strong><?= $monthlyData['period']['end'] ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php if(!empty($selectedTeamId)): ?>
                        <?php 
                            $selTeamName = '';
                            if(!empty($teams)) {
                                foreach ($teams as $t) {
                                    if ((int)$t['id'] === (int)$selectedTeamId) {
                                        $selTeamName = $t['name'];
                                        break;
                                    }
                                }
                            }
                            $clearTeamQuery = ['tab' => $activeTab];
                            if ($activeTab === 'daily') {
                                $clearTeamQuery['date'] = $date;
                            } else {
                                $clearTeamQuery['year'] = $year;
                                $clearTeamQuery['month'] = $month;
                            }
                            $clearTeamUrl = '?' . http_build_query($clearTeamQuery);
                        ?>
                        <div class="badge bg-primary-transparent text-primary fs-12 px-3 py-2 border border-primary-subtle">
                            <i class="fa-solid fa-filter me-1"></i> Filtered Team: <strong><?= htmlspecialchars($selTeamName ?: 'Team #' . $selectedTeamId) ?></strong>
                            <a href="<?= $clearTeamUrl ?>" class="text-danger ms-2" title="Clear Team Filter">
                                <i class="fa-solid fa-circle-xmark"></i>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="badge bg-success-transparent text-success fs-12 px-3 py-2 border border-success-subtle">
                            <i class="fa-solid fa-layer-group me-1"></i> Scope: <strong>All Teams (Company-Wide)</strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <?php
        $teamParam = !empty($selectedTeamId) ? '&team_id=' . urlencode($selectedTeamId) : '';
        $monthYearParam = "&year={$year}&month={$month}";
        $dailyParam = !empty($date) ? "&date={$date}" : '';

        // Clean export parameters based on active tab
        $exportParams = ['tab' => $activeTab];
        if ($activeTab === 'daily') {
            $exportParams['date'] = $date;
        } else {
            $exportParams['year'] = $year;
            $exportParams['month'] = $month;
        }
        if (!empty($selectedTeamId)) {
            $exportParams['team_id'] = $selectedTeamId;
        }
        $excelExportUrl = '?' . http_build_query(array_merge($exportParams, ['export' => 'excel']));
        $pdfExportUrl = '?' . http_build_query(array_merge($exportParams, ['export' => 'pdf']));
        ?>
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2 border-bottom pb-2">
                    <ul class="nav nav-tabs tab-style-1 d-sm-flex d-block border-bottom-0 mb-0" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link <?= $activeTab == 'dashboard' ? 'active' : '' ?>" href="?tab=dashboard<?= $monthYearParam ?><?= $teamParam ?>">Ranking Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $activeTab == 'target-vs-achievement' ? 'active' : '' ?>" href="?tab=target-vs-achievement<?= $monthYearParam ?><?= $teamParam ?>">
                                <i class="fa-solid fa-bullseye me-1 text-success"></i> Target Vs Achievement
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $activeTab == 'monthly' ? 'active' : '' ?>" href="?tab=monthly<?= $monthYearParam ?><?= $teamParam ?>">Monthly Team Summary</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $activeTab == 'all-team' ? 'active' : '' ?>" href="?tab=all-team<?= $monthYearParam ?><?= $teamParam ?>">All Team Report</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $activeTab == 'daily' ? 'active' : '' ?>" href="?tab=daily<?= $dailyParam ?><?= $teamParam ?>">Daily Report</a>
                        </li>
                    </ul>
                    <div class="d-flex gap-2">
                        <a href="<?= $excelExportUrl ?>" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1 shadow-sm" title="Export current tab data to Excel">
                            <i class="fa-solid fa-file-excel me-1"></i> Export Excel
                        </a>
                        <a href="<?= $pdfExportUrl ?>" target="_blank" class="btn btn-sm btn-danger d-inline-flex align-items-center gap-1 shadow-sm" title="Export current tab data to PDF">
                            <i class="fa-solid fa-file-pdf me-1"></i> Export PDF
                        </a>
                    </div>
                </div>

                <div class="tab-content mt-3">
                    
                    <!-- Ranking Dashboard Tab -->
                    <div class="tab-pane <?= $activeTab == 'dashboard' ? 'show active' : '' ?>">
                        <div class="row">
                            <div class="col-md-12 d-flex justify-content-between align-items-center mb-4">
                                <h4 class="mb-0">Top Performers Ranking</h4>
                                <div class="d-flex gap-2">
                                    <a href="<?= $excelExportUrl ?>" class="btn btn-sm btn-outline-success shadow-sm" title="Download Excel">
                                        <i class="fa-solid fa-file-excel me-1"></i> Excel
                                    </a>
                                    <a href="<?= $pdfExportUrl ?>" target="_blank" class="btn btn-sm btn-outline-danger shadow-sm" title="Download PDF">
                                        <i class="fa-solid fa-file-pdf me-1"></i> PDF
                                    </a>
                                </div>
                            </div>
                            <?php
                            if (!function_exists('formatBDMoneyUI')) {
                                function formatBDMoneyUI($amount) {
                                    if ($amount >= 10000000) {
                                        return '৳<span class="fs-4 fw-bold">' . number_format($amount / 10000000, 2) . '</span> <span class="fs-6">Cr</span>';
                                    } elseif ($amount >= 100000) {
                                        return '৳<span class="fs-4 fw-bold">' . number_format($amount / 100000, 2) . '</span> <span class="fs-6">Lac</span>';
                                    } elseif ($amount >= 1000) {
                                        return '৳<span class="fs-4 fw-bold">' . number_format($amount / 1000, 2) . '</span> <span class="fs-6">K</span>';
                                    }
                                    return '৳<span class="fs-4 fw-bold">' . number_format($amount, 2) . '</span>';
                                }
                            }
                            if (!function_exists('getInitialsUI')) {
                                function getInitialsUI($name) {
                                    $words = explode(' ', str_replace(['.', ',', 'MD', 'Mr'], '', $name));
                                    $initials = '';
                                    foreach ($words as $w) {
                                        if (trim($w) != '') {
                                            $initials .= strtoupper(substr(trim($w), 0, 1));
                                        }
                                    }
                                    return substr($initials, 0, 2);
                                }
                            }
                            ?>
                            <style>
                            :root {
                                --rank-bg-color: #ffffff;
                                --rank-text-color: #333333;
                                --rank-border-color: #e2e8f0;
                                --rank-muted-color: #64748b;
                                --rank-card-bg: #f8fafc;
                                --rank-progress-bg: #e2e8f0;
                                
                                --gold: #f59e0b;
                                --gold-light: #fef3c7;
                                --silver: #94a3b8;
                                --silver-light: #f1f5f9;
                                --bronze: #d97706;
                                --bronze-light: #fef3c7;
                                --default-rank: #334155;
                                --default-rank-light: #f1f5f9;
                            }

                            [bd-theme="bd-theme-dark"] .modern-rank-card, [bd-theme="bd-theme-dark"] body, [data-bs-theme="dark"] body, body.dark-mode, body.dark-theme, body[class*="dark"], html[data-theme="dark"] body, html.dark body, body[data-theme="dark"] {
                                --rank-bg-color: #1f242b;
                                --rank-text-color: #f8fafc;
                                --rank-border-color: #334155;
                                --rank-muted-color: #94a3b8;
                                --rank-card-bg: #21262d !important;
                                --rank-card-border: #334155 !important;
                                --rank-progress-bg: #334155;
                            }

                            .modern-rank-card {
                                background: var(--rank-card-bg);
                                border: 1px solid var(--rank-card-border, var(--rank-border-color));
                                border-radius: 12px;
                                padding: 1.25rem;
                                color: var(--rank-text-color);
                                position: relative;
                                overflow: hidden;
                                transition: transform 0.2s, box-shadow 0.2s;
                            }
                            
                            /* Light Mode Shading for Top 3 */
                            .m-rank-1 { --rank-card-bg: linear-gradient(145deg, #fffbf0 0%, #ffffff 100%); --rank-card-border: #fde68a; }
                            .m-rank-2 { --rank-card-bg: linear-gradient(145deg, #f8f9fa 0%, #ffffff 100%); --rank-card-border: #e2e8f0; }
                            .m-rank-3 { --rank-card-bg: linear-gradient(145deg, #fffaf5 0%, #ffffff 100%); --rank-card-border: #fed7aa; }

                            /* Team Grouping Colors (Light) */
                            :root {
                                --team-bg-0: #f8fafc;
                                --team-bg-1: #f0fdf4;
                                --team-bg-2: #eff6ff;
                                --team-bg-3: #fef2f2;
                                --team-bg-4: #fdf4ff;
                                --team-bg-5: #fffbeb;
                            }
                            /* Team Grouping Colors (Dark) */
                            [bd-theme="bd-theme-dark"] body, [data-bs-theme="dark"] body, body.dark-mode {
                                --team-bg-0: rgba(255, 255, 255, 0.02);
                                --team-bg-1: rgba(16, 185, 129, 0.05);
                                --team-bg-2: rgba(59, 130, 246, 0.05);
                                --team-bg-3: rgba(239, 68, 68, 0.05);
                                --team-bg-4: rgba(168, 85, 247, 0.05);
                                --team-bg-5: rgba(245, 158, 11, 0.05);
                            }

                            .modern-rank-card:hover {
                                transform: translateY(-4px);
                                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
                            }

                            .modern-rank-card::before {
                                content: '';
                                position: absolute;
                                top: 0;
                                left: 0;
                                right: 0;
                                height: 4px;
                            }
                            .m-rank-1::before { background-color: var(--gold); }
                            .m-rank-2::before { background-color: var(--silver); }
                            .m-rank-3::before { background-color: var(--bronze); }

                            .avatar-circle {
                                width: 48px;
                                height: 48px;
                                border-radius: 50%;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                font-weight: bold;
                                font-size: 1.1rem;
                                flex-shrink: 0;
                            }
                            .m-rank-1 .avatar-circle { background-color: var(--gold); color: #fff; }
                            .m-rank-2 .avatar-circle { background-color: var(--silver-light); color: var(--silver); }
                            .m-rank-3 .avatar-circle { background-color: var(--bronze-light); color: var(--bronze); }
                            .m-rank-other .avatar-circle { background-color: var(--default-rank-light); color: var(--default-rank); }

                            .rank-badge {
                                position: absolute;
                                top: 1.25rem;
                                right: 1.25rem;
                                width: 32px;
                                height: 32px;
                                border-radius: 50%;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                font-weight: 700;
                                font-size: 0.9rem;
                            }
                            .m-rank-1 .rank-badge { background-color: var(--gold); color: #fff; }
                            .m-rank-2 .rank-badge { background-color: var(--silver-light); color: var(--silver); }
                            .m-rank-3 .rank-badge { background-color: var(--bronze-light); color: var(--bronze); }
                            .m-rank-other .rank-badge { background-color: var(--default-rank-light); color: var(--default-rank); }

                            .circular-chart {
                                display: block;
                                margin: 0 auto;
                                max-width: 90px;
                                max-height: 90px;
                            }
                            .circle-bg {
                                fill: none;
                                stroke: var(--rank-progress-bg);
                                stroke-width: 3.8;
                            }
                            .circle {
                                fill: none;
                                stroke-width: 3.8;
                                stroke-linecap: round;
                                animation: progress 1s ease-out forwards;
                            }
                            @keyframes progress {
                                0% { stroke-dasharray: 0 100; }
                            }
                            .percentage {
                                fill: var(--rank-text-color);
                                font-family: sans-serif;
                                font-size: 0.45em;
                                text-anchor: middle;
                                font-weight: bold;
                            }
                            .score-label {
                                fill: var(--rank-muted-color);
                                font-family: sans-serif;
                                font-size: 0.25em;
                                text-anchor: middle;
                            }
                            .m-rank-1 .circle { stroke: #10b981; } 
                            .m-rank-2 .circle { stroke: #f59e0b; } 
                            .m-rank-3 .circle { stroke: #ef4444; } 
                            .m-rank-other .circle { stroke: #ef4444; }

                            .linear-progress-container {
                                background-color: var(--rank-progress-bg);
                                height: 4px;
                                border-radius: 2px;
                                width: 100%;
                                margin-top: 4px;
                                overflow: hidden;
                            }
                            .linear-progress-bar {
                                height: 100%;
                                border-radius: 2px;
                            }
                            .progress-green { background-color: #10b981; }
                            .progress-orange { background-color: #f59e0b; }
                            .progress-red { background-color: #ef4444; }
                            
                            @media print {
                                .modern-rank-card {
                                    -webkit-print-color-adjust: exact !important;
                                    print-color-adjust: exact !important;
                                    box-shadow: none !important;
                                    break-inside: avoid;
                                }
                            }
                            </style>
                            <?php 
                            $inds = $monthlyData['individuals'] ?? []; 
                            $count = 0;
                            // Find max units for progress bars
                            $maxUnitsTeam = 1;
                            foreach($inds as $e) { if($e['units_sold'] > $maxUnitsTeam) $maxUnitsTeam = $e['units_sold']; }

                            foreach($inds as $emp): 
                                if($count >= 10) break; // Top 10
                                $rank = $count + 1;
                                
                                $rankClass = $rank <= 3 ? "m-rank-$rank" : "m-rank-other";
                                $iconHtml = '';
                                if ($rank == 1) $iconHtml = '<i class="fa-solid fa-crown text-warning me-1"></i>';
                                elseif ($rank == 2) $iconHtml = '<i class="fa-solid fa-medal text-secondary me-1"></i>';
                                elseif ($rank == 3) $iconHtml = '<i class="fa-solid fa-medal" style="color:#d97706;"></i>';

                                $initials = getInitialsUI($emp['member_name']);
                                $achievedVolText = formatBDMoneyUI($emp['total_volume_achieved']);
                                $targetVolText = $emp['volume_target'] > 0 ? 'Tk ' . number_format($emp['volume_target']) : 'not set';
                                $scorePct = number_format($emp['performance_score'], 1);
                                
                                // Progress calculations
                                $unitPct = ($emp['units_sold'] / $maxUnitsTeam) * 100;
                                $unitColor = $unitPct >= 80 ? 'progress-green' : ($unitPct >= 50 ? 'progress-orange' : 'progress-red');
                                
                                $followUpPct = $emp['follow_up_pct'];
                                $followUpColor = $followUpPct >= 80 ? 'progress-green' : ($followUpPct >= 50 ? 'progress-orange' : 'progress-red');
                                $followUpText = $followUpPct >= 80 ? 'high' : ($followUpPct >= 50 ? 'fair' : 'low');
                            ?>
                            <div class="col-md-4 mb-3">
                                <div class="modern-rank-card <?= $rankClass ?> h-100" 
                                     data-bs-toggle="modal"
                                     data-bs-target="#rankingBreakdownModal"
                                     data-emp='<?= htmlspecialchars(json_encode($emp), ENT_QUOTES, 'UTF-8') ?>'>
                                    
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="avatar-circle me-3"><?= $initials ?></div>
                                        <div>
                                            <h6 class="mb-0 fw-bold d-flex align-items-center" style="color: var(--rank-text-color);">
                                                <?= $iconHtml ?> <?= htmlspecialchars($emp['member_name']) ?>
                                            </h6>
                                            <small style="color: var(--rank-muted-color); font-size: 11px;">
                                                <?= htmlspecialchars($emp['level_designation']) ?> &bull; <?= htmlspecialchars($emp['team_name']) ?>
                                            </small>
                                        </div>
                                        <div class="rank-badge">#<?= $rank ?></div>
                                    </div>
                                    
                                    <div class="d-flex mb-3 align-items-center">
                                        <div class="me-3" style="width: 80px;">
                                            <svg viewBox="0 0 36 36" class="circular-chart">
                                                <path class="circle-bg"
                                                    d="M18 2.0845
                                                    a 15.9155 15.9155 0 0 1 0 31.831
                                                    a 15.9155 15.9155 0 0 1 0 -31.831"
                                                />
                                                <path class="circle"
                                                    stroke-dasharray="<?= $scorePct ?>, 100"
                                                    d="M18 2.0845
                                                    a 15.9155 15.9155 0 0 1 0 31.831
                                                    a 15.9155 15.9155 0 0 1 0 -31.831"
                                                />
                                                <text x="18" y="17" class="percentage"><?= $scorePct ?>%</text>
                                                <text x="18" y="24" class="score-label">SCORE</text>
                                            </svg>
                                        </div>
                                        <div>
                                            <div style="font-size: 10px; color: var(--rank-muted-color); font-weight: bold; letter-spacing: 0.5px;">ACHIEVED VOLUME</div>
                                            <div class="mb-1" style="color: var(--rank-text-color);"><?= $achievedVolText ?></div>
                                            <div style="font-size: 11px; color: var(--rank-muted-color); margin-bottom: 5px;">Tk <?= number_format($emp['total_volume_achieved'], 2) ?></div>
                                            <span class="badge" style="background-color: var(--rank-progress-bg); color: var(--rank-text-color); font-size: 10px; font-weight: normal;"><i class="fa-solid fa-circle text-muted" style="font-size: 6px; vertical-align: middle;"></i> Target: <?= $targetVolText ?></span>
                                        </div>
                                    </div>

                                    <hr style="border-color: var(--rank-border-color); opacity: 0.5;">
                                    
                                    <div class="row mb-3">
                                        <div class="col-6 pe-2">
                                            <div class="d-flex justify-content-between align-items-end mb-1">
                                                <span style="font-size: 10px; color: var(--rank-muted-color); font-weight: bold;">UNITS SOLD</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-end">
                                                <span class="fw-bold fs-5" style="color: var(--rank-text-color);"><?= $emp['units_sold'] ?></span>
                                                <span style="font-size: 10px; color: var(--rank-muted-color);">of <?= $maxUnitsTeam ?> top</span>
                                            </div>
                                            <div class="linear-progress-container">
                                                <div class="linear-progress-bar <?= $unitColor ?>" style="width: <?= $unitPct ?>%"></div>
                                            </div>
                                        </div>
                                        <div class="col-6 ps-2">
                                            <div class="d-flex justify-content-between align-items-end mb-1">
                                                <span style="font-size: 10px; color: var(--rank-muted-color); font-weight: bold;">FOLLOW UP RATE</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-end">
                                                <span class="fw-bold fs-5" style="color: var(--rank-text-color);"><?= $followUpPct ?>%</span>
                                                <span style="font-size: 10px; color: var(--rank-muted-color);"><?= $followUpText ?></span>
                                            </div>
                                            <div class="linear-progress-container">
                                                <div class="linear-progress-bar <?= $followUpColor ?>" style="width: <?= $followUpPct ?>%"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="button" class="btn w-100 fw-semibold" 
                                            style="background-color: var(--rank-progress-bg); color: var(--rank-text-color); font-size: 13px; border: 1px solid var(--rank-border-color);"
                                            data-bs-toggle="modal"
                                            data-bs-target="#rankingBreakdownModal"
                                            data-emp="<?= htmlspecialchars(json_encode($emp), ENT_QUOTES, 'UTF-8') ?>">
                                        View Ranking Breakdown
                                    </button>
                                </div>
                            </div>
                            <?php $count++; endforeach; ?>
                            
                            <?php if(empty($inds)): ?>
                                <div class="col-12"><div class="alert alert-info">No data available for this period.</div></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Monthly Team Summary Tab -->
                    <div class="tab-pane <?= $activeTab == 'monthly' ? 'show active' : '' ?>">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0 fw-semibold text-primary"><i class="fa-solid fa-table me-2"></i>Monthly Team & Member Summary</h5>
                            <div class="d-flex gap-2">
                                <a href="<?= $excelExportUrl ?>" class="btn btn-sm btn-outline-success shadow-sm" title="Download Excel">
                                    <i class="fa-solid fa-file-excel me-1"></i> Excel
                                </a>
                                <a href="<?= $pdfExportUrl ?>" target="_blank" class="btn btn-sm btn-outline-danger shadow-sm" title="Download PDF">
                                    <i class="fa-solid fa-file-pdf me-1"></i> PDF
                                </a>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover text-nowrap align-middle">
                                <thead class="table-info text-center align-middle">
                                    <tr>
                                        <th rowspan="2">Rank</th>
                                        <th rowspan="2">Branch</th>
                                        <th rowspan="2">Team Name</th>
                                        <th rowspan="2">Member Name</th>
                                        <th rowspan="2">Designation</th>
                                        
                                        <th colspan="3">Target</th>
                                        
                                        <th colspan="4">Activity (Visits & Follow-up)</th>
                                        
                                        <th colspan="2">Site Visit Ach.</th>
                                        <th colspan="2">Unit Ach.</th>
                                        <th colspan="3">Volume Achieved (Tk)</th>
                                        
                                        <th rowspan="2">Score</th>
                                        <th rowspan="2">Action</th>
                                    </tr>
                                    <tr>
                                        <th>Unit</th>
                                        <th>Vol</th>
                                        <th>Visit</th>
                                        
                                        <th>Assigned</th>
                                        <th>Follow Up</th>
                                        <th>Contacted</th>
                                        <th>Interested</th>
                                        
                                        <th>Achieved</th>
                                        <th>%</th>
                                        
                                        <th>Units Sold</th>
                                        <th>%</th>
                                        <th>Assigned Vol</th>
                                        <th>Unassigned Vol</th>
                                        <th>Total Achieved</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $tableInds = $monthlyData['table_individuals'] ?? ($monthlyData['individuals'] ?? []);
                                    $currentTeam = null;
                                    $colorIndex = 0;
                                    foreach($tableInds as $emp): 
                                        if ($currentTeam !== $emp['team_name']) {
                                            $currentTeam = $emp['team_name'];
                                            $colorIndex = ($colorIndex + 1) % 6;
                                        }
                                    ?>
                                    <tr class="text-center" style="background-color: var(--team-bg-<?= $colorIndex ?>) !important;">
                                        <td><span class="badge bg-primary">#<?= $emp['rank'] ?></span></td>
                                        <td><?= htmlspecialchars($emp['branch_name'] ?? 'Other') ?></td>
                                        <td><?= htmlspecialchars($emp['team_name']) ?></td>
                                        <td class="text-start fw-bold"><?= htmlspecialchars($emp['member_name']) ?></td>
                                        <td><?= htmlspecialchars($emp['level_designation']) ?></td>
                                        
                                        <td><?= $emp['unit_target'] ?></td>
                                        <td><?= number_format($emp['volume_target']) ?></td>
                                        <td><?= $emp['visit_target'] ?></td>
                                        
                                        <td><?= $emp['assigned_leads'] ?></td>
                                        <td><?= $emp['total_follow_up'] ?> (<?= $emp['follow_up_pct'] ?>%)</td>
                                        <td><?= $emp['total_contacted'] ?> (<?= $emp['contacted_pct'] ?>%)</td>
                                        <td><?= $emp['interested'] ?></td>
                                        
                                        <td><?= $emp['visited'] ?></td>
                                        <td><?= $emp['visit_ach_pct'] ?>%</td>
                                        
                                        <td>
                                            <?php if(!empty($emp['units_sold']) && $emp['units_sold'] > 0): ?>
                                                <button type="button" class="btn btn-sm btn-outline-success fw-bold px-2 py-0 view-sold-units-btn" 
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#soldUnitsModal"
                                                        data-title="Sold Units Breakdown - <?= htmlspecialchars($emp['member_name'], ENT_QUOTES, 'UTF-8') ?>"
                                                        data-name="<?= htmlspecialchars($emp['member_name'], ENT_QUOTES, 'UTF-8') ?>"
                                                        data-role="<?= htmlspecialchars($emp['level_designation'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($emp['team_name'], ENT_QUOTES, 'UTF-8') ?>)"
                                                        data-units="<?= htmlspecialchars(json_encode($emp['sold_units_details'] ?? []), ENT_QUOTES, 'UTF-8') ?>">
                                                    <i class="fa-solid fa-house-chimney me-1"></i><?= $emp['units_sold'] ?>
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $emp['unit_ach_pct'] ?>%</td>
                                        <td><?= number_format($emp['volume_assigned'], 2) ?></td>
                                        <td><?= number_format($emp['volume_unassigned'], 2) ?></td>
                                        <td class="fw-bold text-success">Tk <?= number_format($emp['total_volume_achieved'], 2) ?></td>
                                        
                                        <td class="fw-bold text-primary"><?= number_format($emp['performance_score'], 2) ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary px-2 py-0 view-ranking-breakdown-btn" 
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#rankingBreakdownModal"
                                                    data-emp="<?= htmlspecialchars(json_encode($emp), ENT_QUOTES, 'UTF-8') ?>" 
                                                    title="View Score & Activity Breakdown">
                                                <i class="fa-solid fa-circle-info me-1"></i> Breakdown
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($tableInds)): ?>
                                        <tr><td colspan="21" class="text-center py-4 text-muted">No member records found for the selected period.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- All Team Report Tab -->
                    <div class="tab-pane <?= $activeTab == 'all-team' ? 'show active' : '' ?>">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0 fw-semibold text-info"><i class="fa-solid fa-users me-2"></i>All Team Performance Report</h5>
                            <div class="d-flex gap-2">
                                <a href="<?= $excelExportUrl ?>" class="btn btn-sm btn-outline-success shadow-sm" title="Download Excel">
                                    <i class="fa-solid fa-file-excel me-1"></i> Excel
                                </a>
                                <a href="<?= $pdfExportUrl ?>" target="_blank" class="btn btn-sm btn-outline-danger shadow-sm" title="Download PDF">
                                    <i class="fa-solid fa-file-pdf me-1"></i> PDF
                                </a>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover text-nowrap align-middle">
                                <thead class="table-info text-center">
                                    <tr>
                                        <th>Rank</th>
                                        <th>Branch</th>
                                        <th>Team Name</th>
                                        <th>Total Leads</th>
                                        <th>Follow Up (%)</th>
                                        <th>Contacted (%)</th>
                                        <th>Not Contacted</th>
                                        <th>Interested</th>
                                        <th>Not Interested</th>
                                        <th>Visits (Ach. / Target)</th>
                                        <th>Units Sold</th>
                                        <th>Volume Target</th>
                                        <th>Volume Achieved</th>
                                        <th>Score</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $allTeamsData = $monthlyData['teams'] ?? [];
                                    $currentTeamName = null;
                                    $colorIndex = 0;
                                    foreach($allTeamsData as $t): 
                                        if ($currentTeamName !== ($t['team_name'] ?? '')) {
                                            $currentTeamName = ($t['team_name'] ?? '');
                                            $colorIndex = ($colorIndex + 1) % 6;
                                        }
                                        $teamMembersPayload = [];
                                        if (!empty($t['members'])) {
                                            foreach ($t['members'] as $tm) {
                                                $teamMembersPayload[] = [
                                                    'rank' => $tm['rank'] ?? '-',
                                                    'member_name' => $tm['member_name'] ?? '',
                                                    'level_designation' => $tm['level_designation'] ?? '',
                                                    'assigned_leads' => $tm['assigned_leads'] ?? 0,
                                                    'follow_up_pct' => $tm['follow_up_pct'] ?? 0,
                                                    'contacted_pct' => $tm['contacted_pct'] ?? 0,
                                                    'visited' => $tm['visited'] ?? 0,
                                                    'visit_target' => $tm['visit_target'] ?? 0,
                                                    'units_sold' => $tm['units_sold'] ?? 0,
                                                    'total_volume_achieved' => $tm['total_volume_achieved'] ?? 0,
                                                    'performance_score' => $tm['performance_score'] ?? 0
                                                ];
                                            }
                                        }
                                    ?>
                                    <tr class="text-center align-middle" style="background-color: var(--team-bg-<?= $colorIndex ?>) !important;">
                                        <td><span class="badge bg-info text-dark">#<?= $t['rank'] ?></span></td>
                                        <td><?= htmlspecialchars($t['branch_name'] ?? 'Other') ?></td>
                                        <td class="text-start fw-bold"><?= htmlspecialchars($t['team_name']) ?></td>
                                        <td><?= $t['total_leads'] ?></td>
                                        <td><?= $t['total_follow_up'] ?> (<?= $t['follow_up_pct'] ?>%)</td>
                                        <td><?= $t['total_contacted'] ?> (<?= $t['contacted_pct'] ?>%)</td>
                                        <td><?= $t['total_not_contacted'] ?></td>
                                        <td><?= $t['interested'] ?></td>
                                        <td><?= $t['not_interested'] ?></td>
                                        <td><?= $t['visited'] ?> / <?= $t['visit_target'] ?> (<?= $t['visit_ach_pct'] ?>%)</td>
                                        <td>
                                            <?php if (!empty($t['units_sold']) && $t['units_sold'] > 0): ?>
                                                <button type="button" class="btn btn-sm btn-outline-success fw-bold px-2 py-0 view-sold-units-btn" 
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#soldUnitsModal"
                                                        data-title="Sold Units Breakdown - <?= htmlspecialchars($t['team_name'], ENT_QUOTES, 'UTF-8') ?>"
                                                        data-name="<?= htmlspecialchars($t['team_name'], ENT_QUOTES, 'UTF-8') ?>"
                                                        data-role="Company Team"
                                                        data-units="<?= htmlspecialchars(json_encode($t['sold_units_details'] ?? []), ENT_QUOTES, 'UTF-8') ?>">
                                                    <i class="fa-solid fa-building me-1"></i><?= $t['units_sold'] ?> Units
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= number_format($t['volume_target']) ?></td>
                                        <td class="fw-bold text-success"><?= number_format($t['total_volume_achieved'], 2) ?> (<?= $t['volume_ach_pct'] ?>%)</td>
                                        <td class="fw-bold"><?= number_format($t['performance_score'], 2) ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info px-2 py-0 view-team-members-btn" 
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#teamMembersBreakdownModal"
                                                    data-team="<?= htmlspecialchars(json_encode([
                                                        'team_name' => $t['team_name'],
                                                        'rank' => $t['rank'],
                                                        'performance_score' => $t['performance_score'],
                                                        'total_leads' => $t['total_leads'],
                                                        'total_volume_achieved' => $t['total_volume_achieved'],
                                                        'units_sold' => $t['units_sold'],
                                                        'members' => $teamMembersPayload
                                                    ]), ENT_QUOTES, 'UTF-8') ?>" 
                                                    title="View Members Contribution Breakdown">
                                                <i class="fa-solid fa-eye me-1"></i> View Breakdown
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($allTeamsData)): ?>
                                        <tr><td colspan="15" class="text-center py-4 text-muted">No team data found</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Daily Report Tab -->
                    <div class="tab-pane <?= $activeTab == 'daily' ? 'show active' : '' ?>">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                            <div class="alert alert-primary mb-0 py-2 px-3">
                                Showing daily activity report for <strong><?= date('d M, Y', strtotime($date)) ?></strong>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="<?= $excelExportUrl ?>" class="btn btn-sm btn-outline-success shadow-sm" title="Download Excel">
                                    <i class="fa-solid fa-file-excel me-1"></i> Excel
                                </a>
                                <a href="<?= $pdfExportUrl ?>" target="_blank" class="btn btn-sm btn-outline-danger shadow-sm" title="Download PDF">
                                    <i class="fa-solid fa-file-pdf me-1"></i> PDF
                                </a>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover text-nowrap align-middle">
                                <thead class="table-success text-center align-middle">
                                    <tr>
                                        <th>Member Name</th>
                                        <th>Branch</th>
                                        <th>Designation</th>
                                        <th>Team Name</th>
                                        <th>Assigned Leads</th>
                                        <th>Total Follow-up</th>
                                        <th>Contacted</th>
                                        <th>Interested</th>
                                        <th>Not Interested</th>
                                        <th>Will Visit Today</th>
                                        <th>Visited Today</th>
                                        <th>Units Sold Today</th>
                                        <th>Volume Achieved Today</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $daily = $dailyData ?? [];
                                    $currentTeam = null;
                                    $colorIndex = 0;
                                    foreach($daily as $emp): 
                                        if ($currentTeam !== $emp['team_name']) {
                                            $currentTeam = $emp['team_name'];
                                            $colorIndex = ($colorIndex + 1) % 6;
                                        }
                                    ?>
                                    <tr class="text-center align-middle" style="background-color: var(--team-bg-<?= $colorIndex ?>) !important;">
                                        <td class="text-start fw-bold"><?= htmlspecialchars($emp['member_name']) ?></td>
                                        <td><?= htmlspecialchars($emp['branch_name'] ?? 'Other') ?></td>
                                        <td><?= htmlspecialchars($emp['level_designation']) ?></td>
                                        <td><?= htmlspecialchars($emp['team_name']) ?></td>
                                        <td><?= $emp['assigned_leads'] ?></td>
                                        <td><?= $emp['total_follow_up'] ?></td>
                                        <td><?= $emp['total_contacted'] ?></td>
                                        <td><?= $emp['interested'] ?></td>
                                        <td><?= $emp['not_interested'] ?></td>
                                        <td><?= $emp['will_visit'] ?></td>
                                        <td><?= $emp['visited'] ?></td>
                                        <td>
                                            <?php if (!empty($emp['units_sold']) && $emp['units_sold'] > 0): ?>
                                                <button type="button" class="btn btn-sm btn-outline-success fw-bold px-2 py-0 view-sold-units-btn" 
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#soldUnitsModal"
                                                        data-title="Units Sold Today - <?= htmlspecialchars($emp['member_name'], ENT_QUOTES, 'UTF-8') ?>"
                                                        data-name="<?= htmlspecialchars($emp['member_name'], ENT_QUOTES, 'UTF-8') ?>"
                                                        data-role="<?= htmlspecialchars($emp['level_designation'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($emp['team_name'], ENT_QUOTES, 'UTF-8') ?>)"
                                                        data-units="<?= htmlspecialchars(json_encode($emp['sold_units_details'] ?? []), ENT_QUOTES, 'UTF-8') ?>">
                                                    <i class="fa-solid fa-house-chimney me-1"></i><?= $emp['units_sold'] ?>
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-success fw-bold"><?= number_format($emp['total_volume_achieved'], 2) ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary px-2 py-0 view-daily-details-btn" 
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#dailyActivityModal"
                                                    data-daily="<?= htmlspecialchars(json_encode([
                                                        'member_name' => $emp['member_name'],
                                                        'level_designation' => $emp['level_designation'],
                                                        'team_name' => $emp['team_name'],
                                                        'assigned_leads' => $emp['assigned_leads'],
                                                        'total_follow_up' => $emp['total_follow_up'],
                                                        'total_contacted' => $emp['total_contacted'],
                                                        'total_not_contacted' => $emp['total_not_contacted'],
                                                        'interested' => $emp['interested'],
                                                        'not_interested' => $emp['not_interested'],
                                                        'will_visit' => $emp['will_visit'],
                                                        'visited' => $emp['visited'],
                                                        'units_sold' => $emp['units_sold'],
                                                        'total_volume_achieved' => $emp['total_volume_achieved'],
                                                        'daily_follow_ups' => $emp['daily_follow_ups'] ?? [],
                                                        'daily_visits' => $emp['daily_visits'] ?? [],
                                                        'sold_units_details' => $emp['sold_units_details'] ?? []
                                                    ]), ENT_QUOTES, 'UTF-8') ?>" 
                                                    title="View Today's Follow-ups, Contacts, Visits & Sold Units">
                                                <i class="fa-solid fa-eye me-1"></i> View Activity Details
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($daily)): ?>
                                        <tr><td colspan="14" class="text-center py-4 text-muted">No daily activity data found</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Target Vs Achievement Tab -->
                    <div class="tab-pane <?= $activeTab == 'target-vs-achievement' ? 'show active' : '' ?>">
                        <?php
                        $scKpis = $monthlyData['scorecard_kpis'] ?? [];
                        $scInds = $monthlyData['scorecard_individuals'] ?? [];
                        ?>

                        <!-- Header & Exports -->
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
                            <div>
                                <h4 class="mb-1 fw-bold text-dark">
                                    <i class="fa-solid fa-bullseye text-success me-2"></i>Target Vs Achievement (8-Pillar Scorecard)
                                </h4>
                                <p class="text-muted fs-12 mb-0">
                                    Comprehensive 100% Weighted Evaluation Model & Target Performance Breakdown
                                </p>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 shadow-sm" data-bs-toggle="collapse" data-bs-target="#scoringFormulaGuide">
                                    <i class="fa-solid fa-calculator me-1"></i> Calculation Guide
                                </button>
                                <a href="<?= $excelExportUrl ?>" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1 shadow-sm" title="Download Excel">
                                    <i class="fa-solid fa-file-excel me-1"></i> Excel
                                </a>
                                <a href="<?= $pdfExportUrl ?>" target="_blank" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1 shadow-sm" title="Download PDF">
                                    <i class="fa-solid fa-file-pdf me-1"></i> PDF
                                </a>
                            </div>
                        </div>

                        <!-- Top KPI Cards -->
                        <div class="row  mb-4">
                            <!-- Card 1: Volume -->
                            <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
                                <div class="card custom-card border-start border-4 border-success shadow-sm h-100 mb-0">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted fs-12 fw-semibold text-uppercase">Sales Volume Achieved</span>
                                            <span class="badge bg-success-transparent fs-11 fw-bold"><?= $scKpis['volume_ach_pct'] ?? 0 ?>% Achieved</span>
                                        </div>
                                        <h4 class="fw-bold mb-1 text-success">Tk <?= number_format($scKpis['total_volume_achieved'] ?? 0, 2) ?></h4>
                                        <div class="text-muted fs-12 mb-2">Target: Tk <?= number_format($scKpis['total_volume_target'] ?? 0, 2) ?></div>
                                        <div class="progress progress-sm">
                                            <div class="progress-bar bg-success" style="width: <?= min(100, $scKpis['volume_ach_pct'] ?? 0) ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Card 2: Units -->
                            <div class="col-xxl-2 col-xl-6 col-lg-6 col-md-6">
                                <div class="card custom-card border-start border-4 border-primary shadow-sm h-100 mb-0">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted fs-12 fw-semibold text-uppercase">Units Booked</span>
                                            <span class="badge bg-primary-transparent fs-11 fw-bold"><?= $scKpis['unit_ach_pct'] ?? 0 ?>%</span>
                                        </div>
                                        <h4 class="fw-bold mb-1 text-primary"><?= number_format($scKpis['total_units_sold'] ?? 0) ?> <span class="fs-13 text-muted">Flats</span></h4>
                                        <div class="text-muted fs-12 mb-2">Target: <?= number_format($scKpis['total_unit_target'] ?? 0) ?> Flats</div>
                                        <div class="progress progress-sm">
                                            <div class="progress-bar bg-primary" style="width: <?= min(100, $scKpis['unit_ach_pct'] ?? 0) ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Card 3: Visits -->
                            <div class="col-xxl-2 col-xl-6 col-lg-6 col-md-6">
                                <div class="card custom-card border-start border-4 border-info shadow-sm h-100 mb-0">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted fs-12 fw-semibold text-uppercase">Site Visits</span>
                                            <span class="badge bg-info-transparent fs-11 fw-bold"><?= $scKpis['visit_ach_pct'] ?? 0 ?>%</span>
                                        </div>
                                        <h4 class="fw-bold mb-1 text-info"><?= number_format($scKpis['total_visited'] ?? 0) ?> <span class="fs-13 text-muted">Visits</span></h4>
                                        <div class="text-muted fs-12 mb-2">Target: <?= number_format($scKpis['total_visit_target'] ?? 0) ?> Visits</div>
                                        <div class="progress progress-sm">
                                            <div class="progress-bar bg-info" style="width: <?= min(100, $scKpis['visit_ach_pct'] ?? 0) ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Card 4: CVR & Quality -->
                            <div class="col-xxl-2 col-xl-6 col-lg-6 col-md-6">
                                <div class="card custom-card border-start border-4 border-warning shadow-sm h-100 mb-0">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted fs-12 fw-semibold text-uppercase">Sales CVR & Quality</span>
                                            <span class="badge bg-warning-transparent fs-11 fw-bold">Benchmark: 1%</span>
                                        </div>
                                        <h4 class="fw-bold mb-1 text-warning"><?= $scKpis['avg_cvr_pct'] ?? 0 ?>% <span class="fs-12 text-muted">Avg CVR</span></h4>
                                        <div class="text-muted fs-12 mb-2">Hot/High Prospect: <?= number_format($scKpis['total_quality_leads'] ?? 0) ?> (<?= $scKpis['avg_quality_pct'] ?? 0 ?>%)</div>
                                        <div class="progress progress-sm">
                                            <div class="progress-bar bg-warning" style="width: <?= min(100, ($scKpis['avg_cvr_pct'] ?? 0) * 50) ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Card 5: Top #1 Scorecard Performer -->
                            <div class="col-xxl-3 col-xl-12 col-lg-12 col-md-12">
                                <?php $topP = $scKpis['top_scorer'] ?? null; ?>
                                <div class="card custom-card  shadow-sm h-100 mb-0" style="background: linear-gradient(135deg, #166534 0%, #15803d 100%);">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="badge bg-warning text-dark fw-bold"><i class="fa-solid fa-crown me-1"></i> #1 Rank Scorecard</span>
                                            <span class="badge bg-light text-dark"><?= $topP['scorecard']['grade'] ?? 'Platinum' ?></span>
                                        </div>
                                        <h5 class="fw-bold  mb-0 mt-2"><?= htmlspecialchars($topP['member_name'] ?? 'N/A') ?></h5>
                                        <div class="-50 fs-12 mb-2"><?= htmlspecialchars($topP['level_designation'] ?? '') ?> | <?= htmlspecialchars($topP['team_name'] ?? '') ?></div>
                                        <div class="d-flex justify-content-between align-items-end mt-2 pt-2 border-top border-white-20">
                                            <div>
                                                <div class="fs-11 -50 text-uppercase">Total Score</div>
                                                <div class="fs-18 fw-bold "><?= number_format($topP['scorecard']['total_score'] ?? 0, 2) ?> / 100</div>
                                            </div>
                                            <div class="text-end">
                                                <div class="fs-11 -50 text-uppercase">Volume Achieved</div>
                                                <div class="fs-13 fw-semibold ">Tk <?= number_format($topP['total_volume_achieved'] ?? 0, 0) ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Calculation Explainer Banner (Collapsible) -->
                        <div class="collapse mb-4" id="scoringFormulaGuide">
                            <div class="card border border-success shadow-sm bg-light">
                                <div class="card-header bg-success  py-2 d-flex justify-content-between align-items-center">
                                    <span class="fw-bold fs-14"><i class="fa-solid fa-square-root-variable me-2"></i>How Calculation Works (8-Pillar Scoring Weights & Formulae)</span>
                                    <span class="badge bg-white text-success fw-bold">Total: 100% Score</span>
                                </div>
                                <div class="card-body">
                                    <div class="row ">
                                        <!-- 1 -->
                                        <div class="col-md-3">
                                            <div class="p-3 bg-white border rounded h-100">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="fw-bold text-success fs-13">1. Target Vol (Land Share)</span>
                                                    <span class="badge bg-success">30%</span>
                                                </div>
                                                <p class="fs-11 text-muted mb-1">Formula: <code>(Achieved / Target) × 30%</code></p>
                                                <small class="text-secondary d-block fs-10">Example: 100K Target, 70K Achieved = 70% × 30% = <strong>21.00%</strong> revenue points.</small>
                                            </div>
                                        </div>
                                        <!-- 2 -->
                                        <div class="col-md-3">
                                            <div class="p-3 bg-white border rounded h-100">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="fw-bold text-primary fs-13">2. Value Realization</span>
                                                    <span class="badge bg-primary">20%</span>
                                                </div>
                                                <p class="fs-11 text-muted mb-1">Formula: <code>(Achieved / Target) × 20%</code></p>
                                                <small class="text-secondary d-block fs-10">Benchmark: At least 20% target achievement required to meet threshold.</small>
                                            </div>
                                        </div>
                                        <!-- 3 -->
                                        <div class="col-md-3">
                                            <div class="p-3 bg-white border rounded h-100">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="fw-bold text-info fs-13">3. No. of Units Sold</span>
                                                    <span class="badge bg-info">10%</span>
                                                </div>
                                                <p class="fs-11 text-muted mb-1">Formula: <code>(Units Sold / Target) × 10%</code></p>
                                                <small class="text-secondary d-block fs-10">Evaluates actual booking counts against monthly flat/unit quota.</small>
                                            </div>
                                        </div>
                                        <!-- 4 -->
                                        <div class="col-md-3">
                                            <div class="p-3 bg-white border rounded h-100">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="fw-bold text-dark fs-13">4. Highest / Site Visits</span>
                                                    <span class="badge bg-dark">10%</span>
                                                </div>
                                                <p class="fs-11 text-muted mb-1">Formula: <code>(Visits / Target) × 10%</code></p>
                                                <small class="text-secondary d-block fs-10">Site visits completed vs quota and relative team standing.</small>
                                            </div>
                                        </div>
                                        <!-- 5 -->
                                        <div class="col-md-3">
                                            <div class="p-3 bg-white border rounded h-100">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="fw-bold text-warning fs-13">5. Sales CVR %</span>
                                                    <span class="badge bg-warning text-dark">10%</span>
                                                </div>
                                                <p class="fs-11 text-muted mb-1">Formula: <code>(Sold / Assigned Leads) × 100%</code></p>
                                                <small class="text-secondary d-block fs-10">Min 1.0% benchmark. ≥1% qualifies for 5.0+ points scaled to 10% for 2% CVR.</small>
                                            </div>
                                        </div>
                                        <!-- 6 -->
                                        <div class="col-md-3">
                                            <div class="p-3 bg-white border rounded h-100">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="fw-bold text-secondary fs-13">6. Connect & Follow-up</span>
                                                    <span class="badge bg-secondary">10%</span>
                                                </div>
                                                <p class="fs-11 text-muted mb-1">Formula: <code>(Contacted% × 5%) + (Follow% × 5%)</code></p>
                                                <small class="text-secondary d-block fs-10">Ensures assigned leads are actively called and touched.</small>
                                            </div>
                                        </div>
                                        <!-- 7 -->
                                        <div class="col-md-3">
                                            <div class="p-3 bg-white border rounded h-100">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="fw-bold text-danger fs-13">7. Quality Leads</span>
                                                    <span class="badge bg-danger">5%</span>
                                                </div>
                                                <p class="fs-11 text-muted mb-1">Formula: <code>(Hot + High Prospect) / Leads</code></p>
                                                <small class="text-secondary d-block fs-10">Proportion of Hot and High Prospect CRM leads maintained.</small>
                                            </div>
                                        </div>
                                        <!-- 8 -->
                                        <div class="col-md-3">
                                            <div class="p-3 bg-white border rounded h-100">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="fw-bold text-secondary fs-13">8. HR & Compliance</span>
                                                    <span class="badge bg-secondary">5%</span>
                                                </div>
                                                <p class="fs-11 text-muted mb-1">Rating: <code>Standard 5.00 / 5.00</code></p>
                                                <small class="text-secondary d-block fs-10">Attendance, discipline, and CRM reporting compliance.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Table Filter / Search -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="text-muted fs-13">
                                Showing <strong><?= count($scInds) ?></strong> active sales executives evaluated:
                            </div>
                            <div class="w-25">
                                <input type="text" id="scorecardTableSearch" class="form-control form-control-sm" placeholder="Search by name, team, designation...">
                            </div>
                        </div>

                        <!-- Scorecard Table -->
                        <div class="table-responsive border rounded shadow-sm">
                            <table class="table table-hover table-bordered text-nowrap align-middle mb-0" id="scorecardDataTable">
                                <thead class="table-light text-center">
                                    <tr class="align-middle fs-12">
                                        <th rowspan="2" style="width: 50px;">Rank</th>
                                        <th rowspan="2" class="text-start">Sales Member</th>
                                        <th colspan="3" class="bg-success-transparent text-success fw-bold">1. Volume Target (30%)</th>
                                        <th colspan="2" class="bg-primary-transparent text-primary fw-bold">2. Value Realization (20%)</th>
                                        <th colspan="2" class="bg-info-transparent text-info fw-bold">3. Units (10%)</th>
                                        <th colspan="2" class="bg-dark-transparent text-dark fw-bold">4. Visits (10%)</th>
                                        <th colspan="3" class="bg-warning-transparent text-warning fw-bold">5. Sales CVR (10%)</th>
                                        <th colspan="2" class="bg-secondary-transparent text-secondary fw-bold">6. Connected (10%)</th>
                                        <th colspan="2" class="bg-danger-transparent text-danger fw-bold">7. Quality (5%)</th>
                                        <th rowspan="2" class="bg-secondary-transparent text-secondary">8. HR (5%)</th>
                                        <th rowspan="2" style="min-width: 140px;" class="bg-success text-light fw-bold">Total Score</th>
                                        <th rowspan="2" style="width: 60px;">Action</th>
                                    </tr>
                                    <tr class="fs-11">
                                        <!-- 1 -->
                                        <th class="bg-success-transparent">Target (Tk)</th>
                                        <th class="bg-success-transparent">Achieved (Tk)</th>
                                        <th class="bg-success-transparent text-success fw-bold">Score (/30)</th>
                                        <!-- 2 -->
                                        <th class="bg-primary-transparent">Ach %</th>
                                        <th class="bg-primary-transparent text-primary fw-bold">Score (/20)</th>
                                        <!-- 3 -->
                                        <th class="bg-info-transparent">Tgt / Sold</th>
                                        <th class="bg-info-transparent text-info fw-bold">Score (/10)</th>
                                        <!-- 4 -->
                                        <th class="bg-dark-transparent">Tgt / Done</th>
                                        <th class="bg-dark-transparent text-dark fw-bold">Score (/10)</th>
                                        <!-- 5 -->
                                        <th class="bg-warning-transparent">Leads</th>
                                        <th class="bg-warning-transparent">CVR %</th>
                                        <th class="bg-warning-transparent text-warning fw-bold">Score (/10)</th>
                                        <!-- 6 -->
                                        <th class="bg-secondary-transparent">Cont. %</th>
                                        <th class="bg-secondary-transparent text-secondary fw-bold">Score (/10)</th>
                                        <!-- 7 -->
                                        <th class="bg-danger-transparent">Hot / High</th>
                                        <th class="bg-danger-transparent text-danger fw-bold">Score (/5)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($scInds as $emp): ?>
                                    <?php 
                                        $sc = $emp['scorecard'] ?? [];
                                        $sRank = $sc['rank'] ?? 0;
                                        $rankBadge = $sRank == 1 ? 'bg-warning text-dark' : ($sRank == 2 ? 'bg-secondary ' : ($sRank == 3 ? 'bg-info ' : 'bg-light text-dark border'));
                                        $gradeClass = $sc['grade_class'] ?? 'success';
                                    ?>
                                    <tr class="text-center fs-12">
                                        <td><span class="badge <?= $rankBadge ?> fs-12 fw-bold">#<?= $sRank ?></span></td>
                                        <td class="text-start">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($emp['member_name']) ?></div>
                                            <div class="text-muted fs-11"><?= htmlspecialchars($emp['level_designation']) ?> &bull; <span class="badge bg-light text-secondary"><?= htmlspecialchars($emp['team_name']) ?></span></div>
                                        </td>
                                        <!-- 1. Volume 30% -->
                                        <td><?= number_format($emp['volume_target'], 0) ?></td>
                                        <td class="fw-semibold <?= $emp['total_volume_achieved'] > 0 ? 'text-success' : 'text-muted' ?>"><?= number_format($emp['total_volume_achieved'], 0) ?></td>
                                        <td class="fw-bold text-success bg-success-transparent"><?= number_format($sc['pillar1_score'] ?? 0, 2) ?></td>
                                        <!-- 2. Value 20% -->
                                        <td>
                                            <span class="badge <?= !empty($sc['min_20_met']) ? 'bg-success-transparent text-success' : 'bg-danger-transparent text-danger' ?>">
                                                <?= $emp['volume_ach_pct'] ?>%
                                            </span>
                                        </td>
                                        <td class="fw-bold text-primary bg-primary-transparent"><?= number_format($sc['pillar2_score'] ?? 0, 2) ?></td>
                                        <!-- 3. Units 10% -->
                                        <td>
                                            <?= $emp['unit_target'] ?> / 
                                            <?php if(!empty($emp['units_sold']) && $emp['units_sold'] > 0): ?>
                                                <button type="button" class="btn btn-sm btn-outline-success fw-bold px-1 py-0 view-sold-units-btn" 
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#soldUnitsModal"
                                                        data-title="Sold Units Breakdown - <?= htmlspecialchars($emp['member_name'], ENT_QUOTES, 'UTF-8') ?>"
                                                        data-name="<?= htmlspecialchars($emp['member_name'], ENT_QUOTES, 'UTF-8') ?>"
                                                        data-role="<?= htmlspecialchars($emp['level_designation'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($emp['team_name'], ENT_QUOTES, 'UTF-8') ?>)"
                                                        data-units="<?= htmlspecialchars(json_encode($emp['sold_units_details'] ?? []), ENT_QUOTES, 'UTF-8') ?>"
                                                        title="View Sold Units Breakdown">
                                                    <?= $emp['units_sold'] ?>
                                                </button>
                                            <?php else: ?>
                                                <strong>0</strong>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-bold text-info bg-info-transparent"><?= number_format($sc['pillar3_score'] ?? 0, 2) ?></td>
                                        <!-- 4. Visits 10% -->
                                        <td><?= $emp['visit_target'] ?> / <strong><?= $emp['visited'] ?></strong></td>
                                        <td class="fw-bold text-dark bg-dark-transparent"><?= number_format($sc['pillar4_score'] ?? 0, 2) ?></td>
                                        <!-- 5. Sales CVR 10% -->
                                        <td><?= $emp['assigned_leads'] ?></td>
                                        <td>
                                            <span class="badge <?= !empty($sc['cvr_min_1_met']) ? 'bg-success-transparent text-success' : 'bg-warning-transparent text-warning' ?>">
                                                <?= $sc['cvr_pct'] ?>%
                                            </span>
                                        </td>
                                        <td class="fw-bold text-warning bg-warning-transparent"><?= number_format($sc['pillar5_score'] ?? 0, 2) ?></td>
                                        <!-- 6. Connected 10% -->
                                        <td><?= $sc['contacted_ratio_pct'] ?>%</td>
                                        <td class="fw-bold text-secondary bg-secondary-transparent"><?= number_format($sc['pillar6_score'] ?? 0, 2) ?></td>
                                        <!-- 7. Quality 5% -->
                                        <td><?= $sc['quality_leads'] ?> <small class="text-muted">(<?= $sc['quality_pct'] ?>%)</small></td>
                                        <td class="fw-bold text-danger bg-danger-transparent"><?= number_format($sc['pillar7_score'] ?? 0, 2) ?></td>
                                        <!-- 8. HR 5% -->
                                        <td class="fw-semibold text-secondary"><?= number_format($sc['pillar8_score'] ?? 0, 2) ?></td>
                                        <!-- Total Score -->
                                        <td class="text-start bg-light">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="fw-bold fs-13 "><?= number_format($sc['total_score'] ?? 0, 2) ?>%</span>
                                                <span class="badge bg-<?= $gradeClass ?> fs-10"><?= $sc['grade'] ?? 'Good' ?></span>
                                            </div>
                                            <div class="progress progress-xs">
                                                <div class="progress-bar bg-<?= $gradeClass ?>" style="width: <?= min(100, $sc['total_score'] ?? 0) ?>%"></div>
                                            </div>
                                        </td>
                                        <!-- Action -->
                                        <td>
                                            <button type="button" class="btn btn-sm btn-icon btn-outline-success view-calc-btn" 
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#scorecardBreakdownModal"
                                                    title="View Step-by-Step Calculation"
                                                    data-scorecard="<?= htmlspecialchars(json_encode($emp), ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="fa-solid fa-calculator"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($scInds)): ?>
                                        <tr><td colspan="21" class="text-center py-4 text-muted">No sales executives found for this period.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

<style>
.modal.custom-modal .modal-body {
    max-height: calc(85vh - 120px);
    overflow-y: auto;
}
</style>

<!-- Scorecard Calculation Breakdown Modal -->
<div class="modal custom-modal fade" id="scorecardBreakdownModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-success"><i class="fa-solid fa-calculator me-2"></i> <span id="modalMemberName">Member Calculation Breakdown</span></h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <span class="text-muted fs-13" id="modalMemberSub">Designation & Team</span>
                </div>
                <!-- Summary Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-3">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="text-muted fs-11 text-uppercase">Overall Performance Score</div>
                                <div class="d-flex align-items-baseline gap-2">
                                    <h2 class="fw-bold text-success mb-0" id="modalTotalScore">0.00%</h2>
                                    <span class="badge bg-success" id="modalGradeBadge">Platinum</span>
                                </div>
                            </div>
                            <div class="col-md-6 text-md-end mt-2 mt-md-0">
                                <div class="text-muted fs-11 text-uppercase">Scorecard Standing</div>
                                <div class="fs-16 fw-bold text-dark" id="modalRankText">Rank #1</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 8 Pillars Breakdown Cards -->
                <div class="row g-3" id="modalPillarsContainer">
                    <!-- Dynamic cards injected via JS -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Ranking Breakdown Modal -->
<div class="modal custom-modal fade" id="rankingBreakdownModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-primary"><i class="fa-solid fa-trophy me-2 text-warning"></i> <span id="rbMemberName">Member Ranking Breakdown</span></h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <span class="text-muted fs-13" id="rbMemberSub">Role & Team</span>
                </div>
                <!-- Summary Card -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-3">
                        <div class="row align-items-center text-center">
                            <div class="col-4 border-end">
                                <div class="text-muted fs-11 text-uppercase fw-semibold">Current Ranking</div>
                                <div class="fs-22 fw-bold text-primary" id="rbRankBadge">#1</div>
                            </div>
                            <div class="col-4 border-end">
                                <div class="text-muted fs-11 text-uppercase fw-semibold">Overall Performance Score</div>
                                <div class="fs-22 fw-bold text-success" id="rbTotalScore">0.00%</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted fs-11 text-uppercase fw-semibold">Achieved Volume</div>
                                <div class="fs-16 fw-bold text-dark" id="rbTotalVolume">Tk 0.00</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5 Weighted Scoring Formula Cards -->
                <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-calculator text-primary me-1"></i> How This Rank Was Achieved (5 Weighted Performance Pillars):</h6>
                <div class="row g-2 mb-3" id="rbPillarsContainer">
                    <!-- Dynamic -->
                </div>

                <!-- Sold Units in Period Section -->
                <div class="card border shadow-sm mt-3" id="rbSoldUnitsCard">
                    <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-success"><i class="fa-solid fa-house-chimney me-1"></i> Sold Units in this Cycle (<span id="rbSoldUnitsCount">0</span>)</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-striped mb-0 fs-12 align-middle">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th>Date</th>
                                        <th>Project</th>
                                        <th>Unit / Flat</th>
                                        <th>Client</th>
                                        <th>Agreement Val</th>
                                        <th>Land Share Val</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="rbSoldUnitsTbody">
                                    <!-- Dynamic -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Sold Units Breakdown Modal -->
<div class="modal custom-modal fade" id="soldUnitsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-success"><i class="fa-solid fa-house-chimney me-2"></i> <span id="suModalTitle">Sold Units Breakdown</span></h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <span class="text-muted fs-13" id="suModalSub">Details</span>
                </div>
                <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded border shadow-sm mb-3">
                    <div>
                        <span class="text-muted fs-12 fw-semibold text-uppercase">Total Units Sold:</span>
                        <h4 class="mb-0 fw-bold text-primary" id="suTotalUnits">0</h4>
                    </div>
                    <div class="text-end">
                        <span class="text-muted fs-12 fw-semibold text-uppercase">Total Volume Realized:</span>
                        <h4 class="mb-0 fw-bold text-success" id="suTotalVolume">Tk 0.00</h4>
                    </div>
                </div>

                <div class="table-responsive bg-white rounded border shadow-sm">
                    <table class="table table-hover table-striped align-middle mb-0 text-nowrap">
                        <thead class="table-light text-center">
                            <tr>
                                <th>#</th>
                                <th>Booking Date</th>
                                <th>Project Name</th>
                                <th>Unit / Flat No</th>
                                <th>Size (Sqft)</th>
                                <th>Client / Lead Name</th>
                                <th>Client Phone</th>
                                <th>Agreement Value (Tk)</th>
                                <th>Land Share Value (Tk)</th>
                                <th>Effective Volume (Tk)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="suTableBody">
                            <!-- Dynamic -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Team Members Breakdown Modal -->
<div class="modal custom-modal fade" id="teamMembersBreakdownModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-info"><i class="fa-solid fa-users me-2"></i> <span id="tbModalTitle">Team Performance Breakdown</span></h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <span class="text-muted fs-13" id="tbModalSub">All Member Contributions</span>
                </div>
                <div class="table-responsive bg-white rounded border shadow-sm">
                    <table class="table table-hover table-striped align-middle mb-0 text-nowrap">
                        <thead class="table-light text-center">
                            <tr>
                                <th>Rank</th>
                                <th>Member Name</th>
                                <th>Designation</th>
                                <th>Assigned Leads</th>
                                <th>Follow-up (%)</th>
                                <th>Contacted (%)</th>
                                <th>Visits Done</th>
                                <th>Units Sold</th>
                                <th>Volume Achieved (Tk)</th>
                                <th>Perf Score</th>
                            </tr>
                        </thead>
                        <tbody id="tbTableBody">
                            <!-- Dynamic -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Daily Activity Drilldown Modal -->
<div class="modal custom-modal fade" id="dailyActivityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-primary"><i class="fa-solid fa-calendar-day me-2"></i> <span id="daModalTitle">Daily Activity Details</span></h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <span class="text-muted fs-13" id="daModalSub">Member Activity</span>
                </div>
                <!-- Daily Quick KPI cards -->
                <div class="row g-2 mb-3 text-center">
                    <div class="col-md-2 col-4">
                        <div class="bg-light p-2 rounded border shadow-sm">
                            <span class="text-muted fs-11 text-uppercase fw-semibold">Assigned Today</span>
                            <h5 class="fw-bold mb-0 text-primary" id="daKpiAssigned">0</h5>
                        </div>
                    </div>
                    <div class="col-md-2 col-4">
                        <div class="bg-light p-2 rounded border shadow-sm">
                            <span class="text-muted fs-11 text-uppercase fw-semibold">Follow-ups Today</span>
                            <h5 class="fw-bold mb-0 text-dark" id="daKpiFollowUps">0</h5>
                        </div>
                    </div>
                    <div class="col-md-2 col-4">
                        <div class="bg-light p-2 rounded border shadow-sm">
                            <span class="text-muted fs-11 text-uppercase fw-semibold">Contacted Today</span>
                            <h5 class="fw-bold mb-0 text-success" id="daKpiContacted">0</h5>
                        </div>
                    </div>
                    <div class="col-md-2 col-4">
                        <div class="bg-light p-2 rounded border shadow-sm">
                            <span class="text-muted fs-11 text-uppercase fw-semibold">Not Contacted</span>
                            <h5 class="fw-bold mb-0 text-danger" id="daKpiNotContacted">0</h5>
                        </div>
                    </div>
                    <div class="col-md-2 col-4">
                        <div class="bg-light p-2 rounded border shadow-sm">
                            <span class="text-muted fs-11 text-uppercase fw-semibold">Visits Today</span>
                            <h5 class="fw-bold mb-0 text-info" id="daKpiVisits">0</h5>
                        </div>
                    </div>
                    <div class="col-md-2 col-4">
                        <div class="bg-light p-2 rounded border shadow-sm">
                            <span class="text-muted fs-11 text-uppercase fw-semibold">Units Sold Today</span>
                            <h5 class="fw-bold mb-0 text-warning" id="daKpiUnits">0</h5>
                        </div>
                    </div>
                </div>

                <!-- Activity Tabs -->
                <ul class="nav nav-pills mb-3 gap-2" id="dailyPillsTab" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active btn-sm" id="pills-fu-tab" data-bs-toggle="pill" data-bs-target="#pills-fu" type="button" role="tab">
                            <i class="fa-solid fa-phone me-1"></i> Follow-ups & Contact History (<span id="daFuTabCount">0</span>)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link btn-sm" id="pills-visits-tab" data-bs-toggle="pill" data-bs-target="#pills-visits" type="button" role="tab">
                            <i class="fa-solid fa-location-dot me-1"></i> Site Visits Today (<span id="daVisitTabCount">0</span>)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link btn-sm" id="pills-sold-tab" data-bs-toggle="pill" data-bs-target="#pills-sold" type="button" role="tab">
                            <i class="fa-solid fa-house-chimney me-1"></i> Units Sold Today (<span id="daSoldTabCount">0</span>)
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Tab 1: Follow-ups -->
                    <div class="tab-pane fade show active" id="pills-fu" role="tabpanel">
                        <div class="table-responsive bg-white rounded border shadow-sm">
                            <table class="table table-hover table-striped align-middle mb-0 fs-12">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th>Time</th>
                                        <th>Lead Name</th>
                                        <th>Phone</th>
                                        <th>Project</th>
                                        <th>Type</th>
                                        <th>Contacted?</th>
                                        <th>Status</th>
                                        <th>Discussion / Notes</th>
                                        <th>Next Action & Date</th>
                                    </tr>
                                </thead>
                                <tbody id="daFuTableBody">
                                    <!-- Dynamic -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 2: Visits -->
                    <div class="tab-pane fade" id="pills-visits" role="tabpanel">
                        <div class="table-responsive bg-white rounded border shadow-sm">
                            <table class="table table-hover table-striped align-middle mb-0 fs-12">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th>Lead / Client Name</th>
                                        <th>Phone Number</th>
                                        <th>Project</th>
                                        <th>Visit Status</th>
                                        <th>Follow-up Date</th>
                                        <th>Discussion / Next Plan</th>
                                    </tr>
                                </thead>
                                <tbody id="daVisitTableBody">
                                    <!-- Dynamic -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 3: Units Sold -->
                    <div class="tab-pane fade" id="pills-sold" role="tabpanel">
                        <div class="table-responsive bg-white rounded border shadow-sm">
                            <table class="table table-hover table-striped align-middle mb-0 fs-12">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th>Booking Date</th>
                                        <th>Project Name</th>
                                        <th>Unit / Flat</th>
                                        <th>Size (Sqft)</th>
                                        <th>Client Name</th>
                                        <th>Phone</th>
                                        <th>Agreement Val</th>
                                        <th>Land Share Val</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="daSoldTableBody">
                                    <!-- Dynamic -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    function initSalesActivityScripts() {
        if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
            setTimeout(initSalesActivityScripts, 40);
            return;
        }

        $(function() {
            // Helper to get parsed data safely from element or closest container
            function getElementData(btn, attr) {
                if (!btn || !btn.length) return null;
                var raw = btn.attr(attr) || btn.closest('[' + attr + ']').attr(attr);
                if (!raw) return null;
                try {
                    return typeof raw === 'object' ? raw : JSON.parse(raw);
                } catch(e) {
                    console.error('Error parsing ' + attr, e, raw);
                    return null;
                }
            }

            // 1. Live Table Search (Target Vs Achievement Tab)
            $('#scorecardTableSearch').on('keyup', function() {
                var filter = $(this).val().toLowerCase();
                $('#scorecardDataTable tbody tr').each(function() {
                    var text = $(this).text().toLowerCase();
                    $(this).toggle(text.indexOf(filter) > -1);
                });
            });

            // 2. Scorecard Modal Populator
            function populateScorecardModal(btn) {
                var emp = getElementData(btn, 'data-scorecard');
                if (!emp) return;
                var sc = emp.scorecard || {};

                $('#modalMemberName').text(emp.member_name || 'Member Details');
                $('#modalMemberSub').text((emp.level_designation || '') + ' | ' + (emp.team_name || ''));
                $('#modalTotalScore').text((sc.total_score || 0).toFixed(2) + '%');
                
                var gradeBadge = $('#modalGradeBadge');
                gradeBadge.text(sc.grade || 'N/A');
                gradeBadge.attr('class', 'badge bg-' + (sc.grade_class || 'success'));

                $('#modalRankText').text('Scorecard Rank: #' + (sc.rank || '-'));

                $('#modalPillarsContainer').html(`
                    <!-- 1. Volume Target -->
                    <div class="col-md-6">
                        <div class="card border rounded p-3 h-100 bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-success fs-13">1. Land Share Revenue (30%)</span>
                                <span class="badge bg-success-transparent text-success fw-bold">${(sc.pillar1_score || 0).toFixed(2)} / 30.00</span>
                            </div>
                            <div class="fs-12 text-muted mb-2">Target: Tk ${Number(emp.volume_target || 0).toLocaleString()} &bull; Achieved: Tk ${Number(emp.total_volume_achieved || 0).toLocaleString()}</div>
                            <div class="fs-11 bg-light p-2 rounded border font-monospace">
                                (${Number(emp.total_volume_achieved || 0).toLocaleString()} / ${Number(emp.volume_target || 0).toLocaleString()}) × 30% = ${(sc.pillar1_score || 0).toFixed(2)}%
                            </div>
                        </div>
                    </div>

                    <!-- 2. Value Realization -->
                    <div class="col-md-6">
                        <div class="card border rounded p-3 h-100 bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-primary fs-13">2. Target vs Achieved Value (20%)</span>
                                <span class="badge bg-primary-transparent text-primary fw-bold">${(sc.pillar2_score || 0).toFixed(2)} / 20.00</span>
                            </div>
                            <div class="fs-12 text-muted mb-2">Achievement: ${emp.volume_ach_pct || 0}% &bull; Benchmark (Min 20%): <span class="badge ${sc.min_20_met ? 'bg-success' : 'bg-danger'}">${sc.min_20_met ? 'Met' : 'Not Met'}</span></div>
                            <div class="fs-11 bg-light p-2 rounded border font-monospace">
                                (${Number(emp.total_volume_achieved || 0).toLocaleString()} / ${Number(emp.volume_target || 0).toLocaleString()}) × 20% = ${(sc.pillar2_score || 0).toFixed(2)}%
                            </div>
                        </div>
                    </div>

                    <!-- 3. Units Sold -->
                    <div class="col-md-6">
                        <div class="card border rounded p-3 h-100 bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-info fs-13">3. No. of Units Sold (10%)</span>
                                <span class="badge bg-info-transparent text-info fw-bold">${(sc.pillar3_score || 0).toFixed(2)} / 10.00</span>
                            </div>
                            <div class="fs-12 text-muted mb-2">Target Units: ${emp.unit_target || 0} &bull; Units Sold: ${emp.units_sold || 0}</div>
                            <div class="fs-11 bg-light p-2 rounded border font-monospace">
                                (${emp.units_sold || 0} / ${emp.unit_target || 0}) × 10% = ${(sc.pillar3_score || 0).toFixed(2)}%
                            </div>
                        </div>
                    </div>

                    <!-- 4. Site Visits -->
                    <div class="col-md-6">
                        <div class="card border rounded p-3 h-100 bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-dark fs-13">4. Site Visits Completed (10%)</span>
                                <span class="badge bg-dark-transparent text-dark fw-bold">${(sc.pillar4_score || 0).toFixed(2)} / 10.00</span>
                            </div>
                            <div class="fs-12 text-muted mb-2">Target Visits: ${emp.visit_target || 0} &bull; Visits Done: ${emp.visited || 0}</div>
                            <div class="fs-11 bg-light p-2 rounded border font-monospace">
                                (${emp.visited || 0} / ${emp.visit_target || 0}) × 10% = ${(sc.pillar4_score || 0).toFixed(2)}%
                            </div>
                        </div>
                    </div>

                    <!-- 5. Sales CVR -->
                    <div class="col-md-6">
                        <div class="card border rounded p-3 h-100 bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-warning fs-13">5. Sales CVR Conversion Rate (10%)</span>
                                <span class="badge bg-warning-transparent text-warning fw-bold">${(sc.pillar5_score || 0).toFixed(2)} / 10.00</span>
                            </div>
                            <div class="fs-12 text-muted mb-2">Assigned Leads: ${emp.assigned_leads || 0} &bull; Deals Closed: ${emp.units_sold || 0} &bull; CVR: ${sc.cvr_pct || 0}%</div>
                            <div class="fs-11 bg-light p-2 rounded border font-monospace">
                                CVR ${sc.cvr_pct || 0}% (Min 1.0% Benchmark: ${sc.cvr_min_1_met ? 'Met' : 'Not Met'}) = ${(sc.pillar5_score || 0).toFixed(2)}%
                            </div>
                        </div>
                    </div>

                    <!-- 6. Connected & Follow-up -->
                    <div class="col-md-6">
                        <div class="card border rounded p-3 h-100 bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-secondary fs-13">6. Leads Connected & Follow-up (10%)</span>
                                <span class="badge bg-secondary-transparent text-secondary fw-bold">${(sc.pillar6_score || 0).toFixed(2)} / 10.00</span>
                            </div>
                            <div class="fs-12 text-muted mb-2">Contacted: ${sc.contacted_ratio_pct || 0}% &bull; Followed: ${sc.followed_ratio_pct || 0}%</div>
                            <div class="fs-11 bg-light p-2 rounded border font-monospace">
                                (${sc.contacted_ratio_pct || 0}% × 5%) + (${sc.followed_ratio_pct || 0}% × 5%) = ${(sc.pillar6_score || 0).toFixed(2)}%
                            </div>
                        </div>
                    </div>

                    <!-- 7. Data Quality -->
                    <div class="col-md-6">
                        <div class="card border rounded p-3 h-100 bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-danger fs-13">7. Data Quality & Timeliness (5%)</span>
                                <span class="badge bg-danger-transparent text-danger fw-bold">${(sc.pillar7_score || 0).toFixed(2)} / 5.00</span>
                            </div>
                            <div class="fs-12 text-muted mb-2">Hot / High Prospect: ${sc.quality_leads || 0} (${sc.quality_pct || 0}%)</div>
                            <div class="fs-11 bg-light p-2 rounded border font-monospace">
                                (${sc.quality_leads || 0} / (10% of ${emp.assigned_leads || 0})) × 5% = ${(sc.pillar7_score || 0).toFixed(2)}%
                            </div>
                        </div>
                    </div>

                    <!-- 8. HR -->
                    <div class="col-md-6">
                        <div class="card border rounded p-3 h-100 bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-secondary fs-13">8. HR Compliance & Discipline (5%)</span>
                                <span class="badge bg-secondary-transparent text-secondary fw-bold">${(sc.pillar8_score || 0).toFixed(2)} / 5.00</span>
                            </div>
                            <div class="fs-12 text-muted mb-2">Attendance, CRM updates & Employment Status</div>
                            <div class="fs-11 bg-light p-2 rounded border font-monospace">
                                Standard Active Compliance Score: ${(sc.pillar8_score || 0).toFixed(2)} / 5.00
                            </div>
                        </div>
                    </div>
                `);
            }

            // 3. Ranking Breakdown Modal Populator
            function populateRankingBreakdownModal(btn) {
                var emp = getElementData(btn, 'data-emp');
                if (!emp) return;

                $('#rbMemberName').text(emp.member_name || 'Member Details');
                $('#rbMemberSub').text((emp.level_designation || '') + ' • ' + (emp.team_name || ''));
                $('#rbRankBadge').text('#' + (emp.rank || '-'));
                $('#rbTotalScore').text(Number(emp.performance_score || 0).toFixed(2) + '%');
                $('#rbTotalVolume').text('Tk ' + Number(emp.total_volume_achieved || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));

                var hasTarget = (Number(emp.volume_target || 0) > 0 || Number(emp.unit_target || 0) > 0);
                var wVol = 0.45;
                var wUnit = hasTarget ? 0.20 : 0.35;
                var wVisit = hasTarget ? 0.15 : 0.10;
                var wCont = hasTarget ? 0.10 : 0.05;
                var wFoll = hasTarget ? 0.10 : 0.05;

                var volAchPct = Number(emp.volume_ach_pct || 0);
                var volScore = Number((volAchPct * wVol).toFixed(2));
                
                var unitAchPct = Number(emp.unit_ach_pct || 0);
                var unitScore = Number((unitAchPct * wUnit).toFixed(2));
                
                var visitAchPct = Number(emp.visit_ach_pct || 0);
                var visitScore = Number((visitAchPct * wVisit).toFixed(2));

                var contactedPct = Number(emp.contacted_pct || 0);
                var contactedScore = Number((contactedPct * wCont).toFixed(2));

                var followUpPct = Number(emp.follow_up_pct || 0);
                var followUpScore = Number((followUpPct * wFoll).toFixed(2));

                $('#rbPillarsContainer').html(`
                    <!-- Pillar 1: Volume -->
                    <div class="col-md-6">
                        <div class="card border rounded p-3 h-100 bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-success fs-13">1. Sales Volume Achieved (${Math.round(wVol * 100)}% Weight)</span>
                                <span class="badge bg-success-transparent text-success fw-bold">${volScore.toFixed(2)} pts</span>
                            </div>
                            <div class="fs-12 text-muted mb-2">Target: Tk ${Number(emp.volume_target || 0).toLocaleString()} &bull; Achieved: Tk ${Number(emp.total_volume_achieved || 0).toLocaleString()} (${volAchPct}%)</div>
                            <div class="progress progress-xs mb-2">
                                <div class="progress-bar bg-success" style="width: ${Math.min(100, volAchPct)}%"></div>
                            </div>
                            <div class="fs-11 bg-light p-2 rounded border font-monospace">
                                ${volAchPct}% × ${Math.round(wVol * 100)}% = ${volScore.toFixed(2)} points
                            </div>
                        </div>
                    </div>

                    <!-- Pillar 2: Unit -->
                    <div class="col-md-6">
                        <div class="card border rounded p-3 h-100 bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-primary fs-13">2. Unit Sold Achievement (${Math.round(wUnit * 100)}% Weight)</span>
                                <span class="badge bg-primary-transparent text-primary fw-bold">${unitScore.toFixed(2)} pts</span>
                            </div>
                            <div class="fs-12 text-muted mb-2">Target: ${emp.unit_target || 0} &bull; Sold: ${emp.units_sold || 0} (${unitAchPct}%)</div>
                            <div class="progress progress-xs mb-2">
                                <div class="progress-bar bg-primary" style="width: ${Math.min(100, unitAchPct)}%"></div>
                            </div>
                            <div class="fs-11 bg-light p-2 rounded border font-monospace">
                                ${unitAchPct}% × ${Math.round(wUnit * 100)}% = ${unitScore.toFixed(2)} points
                            </div>
                        </div>
                    </div>

                    <!-- Pillar 3: Visits -->
                    <div class="col-md-4">
                        <div class="card border rounded p-3 h-100 bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-info fs-13">3. Site Visits (${Math.round(wVisit * 100)}%)</span>
                                <span class="badge bg-info-transparent text-info fw-bold">${visitScore.toFixed(2)} pts</span>
                            </div>
                            <div class="fs-12 text-muted mb-2">Visits: ${emp.visited || 0} / Target: ${emp.visit_target || 0} (${visitAchPct}%)</div>
                            <div class="fs-11 bg-light p-2 rounded border font-monospace">
                                ${visitAchPct}% × ${Math.round(wVisit * 100)}% = ${visitScore.toFixed(2)} pts
                            </div>
                        </div>
                    </div>

                    <!-- Pillar 4: Contacted -->
                    <div class="col-md-4">
                        <div class="card border rounded p-3 h-100 bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-warning fs-13">4. Contacted Rate (${Math.round(wCont * 100)}%)</span>
                                <span class="badge bg-warning-transparent text-warning fw-bold">${contactedScore.toFixed(2)} pts</span>
                            </div>
                            <div class="fs-12 text-muted mb-2">Contacted: ${emp.total_contacted || 0} / Followed: ${emp.unique_leads_followed || 0} (${contactedPct}%)</div>
                            <div class="fs-11 bg-light p-2 rounded border font-monospace">
                                ${contactedPct}% × ${Math.round(wCont * 100)}% = ${contactedScore.toFixed(2)} pts
                            </div>
                        </div>
                    </div>

                    <!-- Pillar 5: Follow Up -->
                    <div class="col-md-4">
                        <div class="card border rounded p-3 h-100 bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-secondary fs-13">5. Lead Coverage (${Math.round(wFoll * 100)}%)</span>
                                <span class="badge bg-secondary-transparent text-secondary fw-bold">${followUpScore.toFixed(2)} pts</span>
                            </div>
                            <div class="fs-12 text-muted mb-2">Followed: ${emp.unique_leads_followed || 0} / Assigned: ${emp.assigned_leads || 0} (${followUpPct}%)</div>
                            <div class="fs-11 bg-light p-2 rounded border font-monospace">
                                ${followUpPct}% × ${Math.round(wFoll * 100)}% = ${followUpScore.toFixed(2)} pts
                            </div>
                        </div>
                    </div>
                `);

                // Sold Units Table
                var soldUnits = emp.sold_units_details || [];
                $('#rbSoldUnitsCount').text(soldUnits.length);
                var tbody = $('#rbSoldUnitsTbody');
                if (soldUnits.length > 0) {
                    tbody.html(soldUnits.map(function(u) {
                        return `
                            <tr>
                                <td class="text-center">${u.booking_date || '-'}</td>
                                <td class="fw-semibold text-primary">${u.project_name || '-'}</td>
                                <td>${u.unit_label || '-'}</td>
                                <td>${u.client_name || '-'}<br><small class="text-muted">${u.client_phone || ''}</small></td>
                                <td class="text-end">Tk ${Number(u.agreement_value || 0).toLocaleString()}</td>
                                <td class="text-end fw-bold text-success">Tk ${Number(u.land_share_value || 0).toLocaleString()}</td>
                                <td class="text-center"><span class="badge bg-success-transparent text-success">${u.booking_status || 'Booked'}</span></td>
                            </tr>
                        `;
                    }).join(''));
                    $('#rbSoldUnitsCard').show();
                } else {
                    tbody.html(`<tr><td colspan="7" class="text-center text-muted py-3">No units sold in this period.</td></tr>`);
                }
            }

            // 4. Sold Units Modal Populator
            function populateSoldUnitsModal(btn) {
                var units = getElementData(btn, 'data-units') || [];
                var title = btn.attr('data-title') || btn.closest('[data-title]').attr('data-title') || 'Sold Units Breakdown';
                var name = btn.attr('data-name') || btn.closest('[data-name]').attr('data-name') || '';
                var role = btn.attr('data-role') || btn.closest('[data-role]').attr('data-role') || '';

                $('#suModalTitle').text(title);
                $('#suModalSub').text(name + (role ? ' • ' + role : ''));
                $('#suTotalUnits').text(units.length);

                var totalVol = 0;
                var tbody = $('#suTableBody');
                if (units.length > 0) {
                    tbody.html(units.map(function(u, i) {
                        var effVal = Number(u.effective_volume || u.land_share_value || u.agreement_value || 0);
                        totalVol += effVal;
                        return `
                            <tr>
                                <td class="text-center fw-bold">${i + 1}</td>
                                <td class="text-center">${u.booking_date || '-'}</td>
                                <td class="fw-bold text-primary">${u.project_name || '-'}</td>
                                <td class="fw-semibold">${u.unit_label || '-'}</td>
                                <td class="text-center">${u.sqft || '-'}</td>
                                <td>${u.client_name || '-'}</td>
                                <td>${u.client_phone || '-'}</td>
                                <td class="text-end">Tk ${Number(u.agreement_value || 0).toLocaleString()}</td>
                                <td class="text-end">Tk ${Number(u.land_share_value || 0).toLocaleString()}</td>
                                <td class="text-end fw-bold text-success">Tk ${effVal.toLocaleString()}</td>
                                <td class="text-center"><span class="badge bg-success-transparent text-success">${u.booking_status || 'Booked'}</span></td>
                            </tr>
                        `;
                    }).join(''));
                } else {
                    tbody.html(`<tr><td colspan="11" class="text-center text-muted py-4">No units sold records found.</td></tr>`);
                }

                $('#suTotalVolume').text('Tk ' + totalVol.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            }

            // 5. Team Members Breakdown Modal Populator
            function populateTeamMembersModal(btn) {
                var team = getElementData(btn, 'data-team');
                if (!team) return;

                $('#tbModalTitle').text((team.team_name || '') + ' - Member Contributions');
                $('#tbModalSub').text('Rank #' + (team.rank || '-') + ' • Score: ' + Number(team.performance_score || 0).toFixed(2) + '% • Volume: Tk ' + Number(team.total_volume_achieved || 0).toLocaleString());

                var members = team.members || [];
                var tbody = $('#tbTableBody');
                if (members.length > 0) {
                    tbody.html(members.map(function(m) {
                        return `
                            <tr class="text-center">
                                <td><span class="badge bg-primary">#${m.rank || '-'}</span></td>
                                <td class="text-start fw-bold">${m.member_name || '-'}</td>
                                <td>${m.level_designation || '-'}</td>
                                <td>${m.assigned_leads || 0}</td>
                                <td>${m.follow_up_pct || 0}%</td>
                                <td>${m.contacted_pct || 0}%</td>
                                <td>${m.visited || 0} / ${m.visit_target || 0}</td>
                                <td class="fw-bold ${m.units_sold > 0 ? 'text-success' : 'text-muted'}">${m.units_sold || 0} Units</td>
                                <td class="text-end fw-bold text-dark">Tk ${Number(m.total_volume_achieved || 0).toLocaleString()}</td>
                                <td class="fw-bold text-success">${Number(m.performance_score || 0).toFixed(2)}%</td>
                            </tr>
                        `;
                    }).join(''));
                } else {
                    tbody.html(`<tr><td colspan="10" class="text-center text-muted py-4">No team member records found.</td></tr>`);
                }
            }

            // 6. Daily Activity Drilldown Modal Populator
            function populateDailyActivityModal(btn) {
                var daily = getElementData(btn, 'data-daily');
                if (!daily) return;

                $('#daModalTitle').text((daily.member_name || '') + " - Today's Activity Drilldown");
                $('#daModalSub').text((daily.level_designation || '') + ' • ' + (daily.team_name || ''));

                // KPIs
                $('#daKpiAssigned').text(daily.assigned_leads || 0);
                $('#daKpiFollowUps').text(daily.total_follow_up || 0);
                $('#daKpiContacted').text(daily.total_contacted || 0);
                $('#daKpiNotContacted').text(daily.total_not_contacted || 0);
                $('#daKpiVisits').text(daily.visited || 0);
                $('#daKpiUnits').text(daily.units_sold || 0);

                // Follow-ups Tab
                var fuList = daily.daily_follow_ups || [];
                $('#daFuTabCount').text(fuList.length);
                var fuTbody = $('#daFuTableBody');
                if (fuList.length > 0) {
                    fuTbody.html(fuList.map(function(f) {
                        return `
                            <tr>
                                <td class="text-center fw-semibold text-muted">${f.created_time || '-'}</td>
                                <td class="fw-bold text-dark">${f.lead_name || '-'}</td>
                                <td>${f.lead_phone || '-'}</td>
                                <td><span class="badge bg-primary-transparent text-primary">${f.project_name || '-'}</span></td>
                                <td>${f.follow_up_type || '-'}</td>
                                <td class="text-center">
                                    <span class="badge ${f.is_contacted ? 'bg-success' : 'bg-danger'}">
                                        ${f.is_contacted ? 'Contacted' : 'Not Contacted'}
                                    </span>
                                </td>
                                <td><span class="badge bg-light text-dark border">${f.lead_status || 'Pending'}</span></td>
                                <td class="text-wrap" style="max-width: 250px;">${f.discussion_note || '-'}</td>
                                <td>${f.next_action || '-'}<br><small class="text-muted">${f.next_follow_up_date || ''}</small></td>
                            </tr>
                        `;
                    }).join(''));
                } else {
                    fuTbody.html(`<tr><td colspan="9" class="text-center text-muted py-4">No follow-ups recorded for today.</td></tr>`);
                }

                // Visits Tab
                var visits = daily.daily_visits || [];
                $('#daVisitTabCount').text(visits.length);
                var visitTbody = $('#daVisitTableBody');
                if (visits.length > 0) {
                    visitTbody.html(visits.map(function(v) {
                        return `
                            <tr>
                                <td class="fw-bold text-dark">${v.lead_name || '-'}</td>
                                <td>${v.lead_phone || '-'}</td>
                                <td class="fw-semibold text-primary">${v.project_name || '-'}</td>
                                <td class="text-center"><span class="badge ${v.visit_badge || 'bg-info'}">${v.visit_status || 'Visited'}</span></td>
                                <td class="text-center">${v.next_follow_up_date || '-'}</td>
                                <td class="text-wrap">${v.notes || '-'}</td>
                            </tr>
                        `;
                    }).join(''));
                } else {
                    visitTbody.html(`<tr><td colspan="6" class="text-center text-muted py-4">No site visits recorded for today.</td></tr>`);
                }

                // Sold Units Tab
                var soldUnits = daily.sold_units_details || [];
                $('#daSoldTabCount').text(soldUnits.length);
                var soldTbody = $('#daSoldTableBody');
                if (soldUnits.length > 0) {
                    soldTbody.html(soldUnits.map(function(s) {
                        return `
                            <tr>
                                <td class="text-center">${s.booking_date || '-'}</td>
                                <td class="fw-bold text-primary">${s.project_name || '-'}</td>
                                <td>${s.unit_label || '-'}</td>
                                <td class="text-center">${s.sqft || '-'}</td>
                                <td class="fw-semibold">${s.client_name || '-'}</td>
                                <td>${s.client_phone || '-'}</td>
                                <td class="text-end">Tk ${Number(s.agreement_value || 0).toLocaleString()}</td>
                                <td class="text-end fw-bold text-success">Tk ${Number(s.land_share_value || 0).toLocaleString()}</td>
                                <td class="text-center"><span class="badge bg-success-transparent text-success">${s.booking_status || 'Booked'}</span></td>
                            </tr>
                        `;
                    }).join(''));
                } else {
                    soldTbody.html(`<tr><td colspan="9" class="text-center text-muted py-4">No units sold recorded for today.</td></tr>`);
                }

                // Smart Tab Selection: if no followups but has units sold, auto show Units Sold tab!
                var targetTabId = 'pills-fu-tab';
                if (fuList.length === 0 && soldUnits.length > 0) {
                    targetTabId = 'pills-sold-tab';
                } else if (fuList.length === 0 && visits.length > 0) {
                    targetTabId = 'pills-visits-tab';
                }
                var targetTabBtn = document.getElementById(targetTabId);
                if (targetTabBtn && typeof bootstrap !== 'undefined' && bootstrap.Tab) {
                    bootstrap.Tab.getOrCreateInstance(targetTabBtn).show();
                }
            }

            // Dual Binding: Bootstrap modal events AND Direct Delegated clicks
            $('#scorecardBreakdownModal').on('show.bs.modal', function(e) {
                if (e.relatedTarget) populateScorecardModal($(e.relatedTarget));
            });
            $(document).on('click', '.view-calc-btn', function() {
                populateScorecardModal($(this));
            });

            $('#rankingBreakdownModal').on('show.bs.modal', function(e) {
                if (e.relatedTarget) populateRankingBreakdownModal($(e.relatedTarget));
            });
            $(document).on('click', '.view-ranking-breakdown-btn', function() {
                populateRankingBreakdownModal($(this));
            });

            $('#soldUnitsModal').on('show.bs.modal', function(e) {
                if (e.relatedTarget) populateSoldUnitsModal($(e.relatedTarget));
            });
            $(document).on('click', '.view-sold-units-btn', function() {
                populateSoldUnitsModal($(this));
            });

            $('#teamMembersBreakdownModal').on('show.bs.modal', function(e) {
                if (e.relatedTarget) populateTeamMembersModal($(e.relatedTarget));
            });
            $(document).on('click', '.view-team-members-btn', function() {
                populateTeamMembersModal($(this));
            });

            $('#dailyActivityModal').on('show.bs.modal', function(e) {
                if (e.relatedTarget) populateDailyActivityModal($(e.relatedTarget));
            });
            $(document).on('click', '.view-daily-details-btn', function() {
                populateDailyActivityModal($(this));
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSalesActivityScripts);
    } else {
        initSalesActivityScripts();
    }
})();
</script>
<!-- main-content-end -->
