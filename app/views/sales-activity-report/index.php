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
        <div class="card custom-card">
            <div class="card-body">
                <ul class="nav nav-tabs tab-style-1 d-sm-flex d-block" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link <?= $activeTab == 'dashboard' ? 'active' : '' ?>" href="?tab=dashboard&year=<?= $year ?>&month=<?= $month ?>&date=<?= $date ?>">Ranking Dashboard</a>
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

                <div class="tab-content mt-3">
                    
                    <!-- Ranking Dashboard Tab -->
                    <div class="tab-pane <?= $activeTab == 'dashboard' ? 'show active' : '' ?>">
                        <div class="row">
                            <div class="col-md-12">
                                <h4 class="mb-4">Top Performers Ranking</h4>
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
                        <div class="alert alert-primary">
                            Showing daily activity report for <strong><?= date('d M, Y', strtotime($date)) ?></strong>
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

                </div>
            </div>
        </div>

    </div>
</div>
<!-- main-content-end -->
