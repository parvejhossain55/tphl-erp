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
                    <div class="row align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Year</label>
                            <select name="year" class="form-select">
                                <?php for($y = 2024; $y <= date('Y')+1; $y++): ?>
                                    <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Month</label>
                            <select name="month" class="form-select">
                                <?php 
                                $months = ['1'=>'Jan', '2'=>'Feb', '3'=>'Mar', '4'=>'Apr', '5'=>'May', '6'=>'Jun', '7'=>'Jul', '8'=>'Aug', '9'=>'Sep', '10'=>'Oct', '11'=>'Nov', '12'=>'Dec'];
                                foreach($months as $m => $mName): 
                                ?>
                                    <option value="<?= $m ?>" <?= $m == $month ? 'selected' : '' ?>><?= $mName ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Specific Date (For Daily Report)</label>
                            <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date) ?>">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">Filter Data</button>
                        </div>
                    </div>
                </form>
                <?php if(!empty($monthlyData['period'])): ?>
                <div class="mt-3 text-muted">
                    Showing data for period: <strong><?= $monthlyData['period']['start'] ?></strong> to <strong><?= $monthlyData['period']['end'] ?></strong>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <?php
        $excelExportUrl = '?' . http_build_query(array_merge($_GET, ['tab' => $activeTab, 'year' => $year, 'month' => $month, 'date' => $date, 'export' => 'excel']));
        $pdfExportUrl = '?' . http_build_query(array_merge($_GET, ['tab' => $activeTab, 'year' => $year, 'month' => $month, 'date' => $date, 'export' => 'pdf']));
        ?>
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2 border-bottom pb-2">
                    <ul class="nav nav-tabs tab-style-1 d-sm-flex d-block border-bottom-0 mb-0" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link <?= $activeTab == 'dashboard' ? 'active' : '' ?>" href="?tab=dashboard&year=<?= $year ?>&month=<?= $month ?>&date=<?= $date ?>">Ranking Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $activeTab == 'target-vs-achievement' ? 'active' : '' ?>" href="?tab=target-vs-achievement&year=<?= $year ?>&month=<?= $month ?>&date=<?= $date ?>">
                                <i class="fa-solid fa-bullseye me-1 text-success"></i> Target Vs Achievement
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $activeTab == 'monthly' ? 'active' : '' ?>" href="?tab=monthly&year=<?= $year ?>&month=<?= $month ?>&date=<?= $date ?>">Monthly Team Summary</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $activeTab == 'all-team' ? 'active' : '' ?>" href="?tab=all-team&year=<?= $year ?>&month=<?= $month ?>&date=<?= $date ?>">All Team Report</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $activeTab == 'daily' ? 'active' : '' ?>" href="?tab=daily&year=<?= $year ?>&month=<?= $month ?>&date=<?= $date ?>">Daily Report</a>
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
                            $inds = $monthlyData['individuals'] ?? []; 
                            $count = 0;
                            foreach($inds as $emp): 
                                if($count >= 10) break; // Top 10
                                $badgeColor = $count == 0 ? 'bg-warning' : ($count == 1 ? 'bg-secondary' : 'bg-primary');
                            ?>
                            <div class="col-md-4 mb-3">
                                <div class="card border border-primary">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0 fw-bold"><?= htmlspecialchars($emp['member_name']) ?></h5>
                                            <span class="badge <?= $badgeColor ?> fs-5">#<?= $emp['rank'] ?></span>
                                        </div>
                                        <p class="text-muted mb-2"><?= htmlspecialchars($emp['level_designation']) ?> | <?= htmlspecialchars($emp['team_name']) ?></p>
                                        
                                        <div class="mt-3">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Total Score:</span>
                                                <span class="fw-bold"><?= number_format($emp['performance_score'], 2) ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Total Achieved Volume:</span>
                                                <span class="fw-bold"><?= number_format($emp['total_volume_achieved'], 2) ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Follow Up Rate:</span>
                                                <span class="fw-bold"><?= $emp['follow_up_pct'] ?>%</span>
                                            </div>
                                        </div>
                                    </div>
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
                            <table class="table table-bordered table-striped table-hover text-nowrap align-middle">
                                <thead class="table-primary text-center">
                                    <tr>
                                        <th rowspan="2">Rank</th>
                                        <th rowspan="2">Team</th>
                                        <th rowspan="2">Member Name</th>
                                        <th rowspan="2">Designation</th>
                                        <th colspan="3">Targets</th>
                                        <th colspan="4">Leads & Follow Ups</th>
                                        <th colspan="2">Visits</th>
                                        <th colspan="3">Sales / Volume</th>
                                        <th rowspan="2">Perf. Score</th>
                                    </tr>
                                    <tr>
                                        <th>Unit</th>
                                        <th>Volume</th>
                                        <th>Visit</th>
                                        
                                        <th>Assigned</th>
                                        <th>Follow Up (%)</th>
                                        <th>Contacted (%)</th>
                                        <th>Interested</th>
                                        
                                        <th>Achieved</th>
                                        <th>%</th>
                                        
                                        <th>Units Sold</th>
                                        <th>Assigned Vol</th>
                                        <th>Unassigned Vol</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($inds as $emp): ?>
                                    <tr class="text-center">
                                        <td><span class="badge bg-primary">#<?= $emp['rank'] ?></span></td>
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
                                        
                                        <td><?= $emp['units_sold'] ?> (<?= $emp['unit_ach_pct'] ?>%)</td>
                                        <td><?= number_format($emp['volume_assigned'], 2) ?></td>
                                        <td><?= number_format($emp['volume_unassigned'], 2) ?></td>
                                        
                                        <td class="fw-bold"><?= number_format($emp['performance_score'], 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
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
                            <table class="table table-bordered table-striped text-nowrap align-middle">
                                <thead class="table-info text-center">
                                    <tr>
                                        <th>Rank</th>
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
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $teams = $monthlyData['teams'] ?? [];
                                    foreach($teams as $t): 
                                    ?>
                                    <tr class="text-center">
                                        <td><span class="badge bg-info text-dark">#<?= $t['rank'] ?></span></td>
                                        <td class="text-start fw-bold"><?= htmlspecialchars($t['team_name']) ?></td>
                                        <td><?= $t['total_leads'] ?></td>
                                        <td><?= $t['total_follow_up'] ?> (<?= $t['follow_up_pct'] ?>%)</td>
                                        <td><?= $t['total_contacted'] ?> (<?= $t['contacted_pct'] ?>%)</td>
                                        <td><?= $t['total_not_contacted'] ?></td>
                                        <td><?= $t['interested'] ?></td>
                                        <td><?= $t['not_interested'] ?></td>
                                        <td><?= $t['visited'] ?> / <?= $t['visit_target'] ?> (<?= $t['visit_ach_pct'] ?>%)</td>
                                        <td><?= $t['units_sold'] ?></td>
                                        <td><?= number_format($t['volume_target']) ?></td>
                                        <td class="fw-bold text-success"><?= number_format($t['total_volume_achieved'], 2) ?> (<?= $t['volume_ach_pct'] ?>%)</td>
                                        <td class="fw-bold"><?= number_format($t['performance_score'], 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($teams)): ?>
                                        <tr><td colspan="13" class="text-center">No team data found</td></tr>
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
                            <table class="table table-bordered table-striped text-nowrap align-middle">
                                <thead class="table-secondary text-center">
                                    <tr>
                                        <th>Member Name</th>
                                        <th>Designation</th>
                                        <th>Team</th>
                                        <th>Leads Assigned Today</th>
                                        <th>Followed Up Today</th>
                                        <th>Contacted Today</th>
                                        <th>Interested</th>
                                        <th>Not Interested</th>
                                        <th>Will Visit Today</th>
                                        <th>Visited Today</th>
                                        <th>Units Sold Today</th>
                                        <th>Volume Achieved Today</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $daily = $dailyData ?? [];
                                    foreach($daily as $emp): 
                                    ?>
                                    <tr class="text-center">
                                        <td class="text-start fw-bold"><?= htmlspecialchars($emp['member_name']) ?></td>
                                        <td><?= htmlspecialchars($emp['level_designation']) ?></td>
                                        <td><?= htmlspecialchars($emp['team_name']) ?></td>
                                        <td><?= $emp['assigned_leads'] ?></td>
                                        <td><?= $emp['total_follow_up'] ?></td>
                                        <td><?= $emp['total_contacted'] ?></td>
                                        <td><?= $emp['interested'] ?></td>
                                        <td><?= $emp['not_interested'] ?></td>
                                        <td><?= $emp['will_visit'] ?></td>
                                        <td><?= $emp['visited'] ?></td>
                                        <td><?= $emp['units_sold'] ?></td>
                                        <td class="text-success fw-bold"><?= number_format($emp['total_volume_achieved'], 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($daily)): ?>
                                        <tr><td colspan="12" class="text-center">No daily activity data found</td></tr>
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
                        <div class="row g-3 mb-4">
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
                                <div class="card custom-card text-white shadow-sm h-100 mb-0" style="background: linear-gradient(135deg, #166534 0%, #15803d 100%);">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="badge bg-warning text-dark fw-bold"><i class="fa-solid fa-crown me-1"></i> #1 Rank Scorecard</span>
                                            <span class="badge bg-light text-dark"><?= $topP['scorecard']['grade'] ?? 'Platinum' ?></span>
                                        </div>
                                        <h5 class="fw-bold text-white mb-0 mt-2"><?= htmlspecialchars($topP['member_name'] ?? 'N/A') ?></h5>
                                        <div class="text-white-50 fs-12 mb-2"><?= htmlspecialchars($topP['level_designation'] ?? '') ?> | <?= htmlspecialchars($topP['team_name'] ?? '') ?></div>
                                        <div class="d-flex justify-content-between align-items-end mt-2 pt-2 border-top border-white-20">
                                            <div>
                                                <div class="fs-11 text-white-50 text-uppercase">Total Score</div>
                                                <div class="fs-18 fw-bold text-white"><?= number_format($topP['scorecard']['total_score'] ?? 0, 2) ?> / 100</div>
                                            </div>
                                            <div class="text-end">
                                                <div class="fs-11 text-white-50 text-uppercase">Volume Achieved</div>
                                                <div class="fs-13 fw-semibold text-white">Tk <?= number_format($topP['total_volume_achieved'] ?? 0, 0) ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Calculation Explainer Banner (Collapsible) -->
                        <div class="collapse mb-4" id="scoringFormulaGuide">
                            <div class="card border border-success shadow-sm bg-light">
                                <div class="card-header bg-success text-white py-2 d-flex justify-content-between align-items-center">
                                    <span class="fw-bold fs-14"><i class="fa-solid fa-square-root-variable me-2"></i>How Calculation Works (8-Pillar Scoring Weights & Formulae)</span>
                                    <span class="badge bg-white text-success fw-bold">Total: 100% Score</span>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
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
                                        <td><?= $emp['unit_target'] ?> / <strong><?= $emp['units_sold'] ?></strong></td>
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
                                                    title="View Step-by-Step Calculation"
                                                    data-scorecard="<?= htmlspecialchars(json_encode($emp), ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="fa-solid fa-calculator"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($scInds)): ?>
                                        <tr><td colspan="20" class="text-center py-4 text-muted">No sales executives found for this period.</td></tr>
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

<!-- Scorecard Calculation Breakdown Modal -->
<div class="modal fade" id="scorecardBreakdownModal" tabindex="-1" aria-labelledby="scorecardModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-calculator fs-18"></i>
                    <div>
                        <h5 class="modal-title text-white fw-bold mb-0" id="modalMemberName">Member Calculation Breakdown</h5>
                        <small class="text-white-50" id="modalMemberSub">Designation & Team</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
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
            <div class="modal-footer bg-white py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Live Table Search
    const searchInput = document.getElementById('scorecardTableSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#scorecardDataTable tbody tr');
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }

    // 2. Scorecard Calculation Breakdown Modal Populator
    const modalEl = document.getElementById('scorecardBreakdownModal');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        document.querySelectorAll('.view-calc-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                try {
                    const raw = this.getAttribute('data-scorecard');
                    if (!raw) return;
                    const emp = JSON.parse(raw);
                    const sc = emp.scorecard || {};

                    document.getElementById('modalMemberName').textContent = emp.member_name || 'Member Details';
                    document.getElementById('modalMemberSub').textContent = (emp.level_designation || '') + ' | ' + (emp.team_name || '');
                    document.getElementById('modalTotalScore').textContent = (sc.total_score || 0).toFixed(2) + '%';
                    
                    const gradeBadge = document.getElementById('modalGradeBadge');
                    gradeBadge.textContent = sc.grade || 'N/A';
                    gradeBadge.className = 'badge bg-' + (sc.grade_class || 'success');

                    document.getElementById('modalRankText').textContent = 'Scorecard Rank: #' + (sc.rank || '-');

                    const container = document.getElementById('modalPillarsContainer');
                    container.innerHTML = `
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
                    `;

                    modal.show();
                } catch(e) {
                    console.error("Error opening breakdown modal", e);
                }
            });
        });
    }
});
</script>
<!-- main-content-end -->
