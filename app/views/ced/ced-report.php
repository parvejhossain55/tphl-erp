<?php
    $ps = $portfolioStats ?? [];
    $assignedProjectsCount = (int)($ps['assigned_projects_count'] ?? 0);
    $assignedProjects      = $ps['assigned_projects'] ?? [];
    $portfolioClients      = (int)($ps['portfolio_clients'] ?? 0);
    $portfolioUnits        = (int)($ps['portfolio_units'] ?? 0);

    $totalClientsFollowed = (int)($summary['total_clients'] ?? 0);
    $totalFollowupNotes   = (int)($summary['total_notes'] ?? 0);
    $coveragePct          = ($portfolioClients > 0)
        ? min(100, round(($totalClientsFollowed / $portfolioClients) * 100, 1))
        : null;
    $avgNotesPerClient    = ($totalClientsFollowed > 0)
        ? round($totalFollowupNotes / $totalClientsFollowed, 1)
        : 0;

    $pgPage       = (int)($pagination['page'] ?? 1);
    $pgPerPage    = (int)($pagination['per_page'] ?? 50);
    $pgTotal      = (int)($pagination['total_records'] ?? count($details));
    $pgTotalPages = (int)($pagination['total_pages'] ?? 1);
    $pgStart      = $pgTotal > 0 ? (($pgPage - 1) * $pgPerPage) + 1 : 0;
    $pgEnd        = min($pgTotal, $pgPage * $pgPerPage);
    $baseQueryParams = array_filter([
        'agent_id'     => $agentId ?: null,
        'report_mode'  => $reportMode ?? null,
        'report_month' => $reportMonth ?? null,
        'report_year'  => $reportYear ?? null,
        'start_date'   => $startDate ?? null,
        'end_date'     => $endDate ?? null,
        'per_page'     => $pgPerPage,
    ], static fn($v) => $v !== null && $v !== '');
?>

<style>
    /* Scoped fixes so main.css (.card-body shadow/margin & .g-3 gap) don't break this report */
    .ced-report-wrap .report-card {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e9eef5;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
        margin-bottom: 18px;
        overflow: hidden;
    }
    .ced-report-wrap .report-card > .card-body {
        margin-bottom: 0 !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        background: transparent !important;
    }
    .ced-report-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin: 12px 0 18px;
        padding-right: 48px; /* prevents overlap with floating theme toggle */
    }
    .ced-report-header h1 {
        font-size: 1.35rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }

    /* Filter bar */
    .ced-filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(175px, 1fr));
        gap: 14px;
        align-items: end;
    }
    .ced-filter-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }
    .ced-filter-grid .form-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: #64748b;
        font-weight: 700;
        margin-bottom: 5px;
    }
    .ced-filter-grid .form-select {
        padding-right: 2.25rem;
        text-overflow: ellipsis;
    }

    /* 3-Column KPI Grid (immune to main.css .g-3 flex gap bug) */
    .ced-kpi-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 18px;
    }
    @media (max-width: 991px) {
        .ced-kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 575px) {
        .ced-kpi-grid {
            grid-template-columns: 1fr;
        }
    }
    .ced-kpi-card {
        background: #ffffff;
        border: 1px solid #e9eef5;
        border-left-width: 4px;
        border-radius: 12px;
        padding: 18px 20px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .ced-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.07);
    }
    .ced-kpi-meta {
        min-width: 0;
        flex: 1;
    }
    .ced-kpi-label {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: #64748b;
        margin-bottom: 4px;
    }
    .ced-kpi-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.15;
        margin-bottom: 5px;
        font-variant-numeric: tabular-nums;
    }
    .ced-kpi-sub {
        font-size: 0.76rem;
        color: #64748b;
        line-height: 1.35;
    }
    .ced-kpi-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        flex-shrink: 0;
    }
    .ced-kpi-projects  { border-left-color: #0d9488; }
    .ced-kpi-projects .ced-kpi-icon  { background: rgba(13, 148, 136, 0.12); color: #0d9488; }
    .ced-kpi-clients   { border-left-color: #15803d; }
    .ced-kpi-clients .ced-kpi-icon   { background: rgba(21, 128, 61, 0.12); color: #15803d; }
    .ced-kpi-notes     { border-left-color: #1d4ed8; }
    .ced-kpi-notes .ced-kpi-icon     { background: rgba(29, 78, 216, 0.12); color: #1d4ed8; }

    /* Assigned projects pill strip */
    .ced-projects-strip {
        background: #ffffff;
        border: 1px solid #e9eef5;
        border-radius: 12px;
        padding: 12px 18px;
        margin-bottom: 18px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
    }
    .ced-project-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 600;
        background: #f0fdfa;
        color: #0f766e;
        border: 1px solid #ccfbf1;
    }

    /* Original table-modern styling */
    .table-modern {
        border-collapse: separate;
        border-spacing: 0 10px;
        border: none;
    }
    .table-modern thead th {
        border: none;
        background: #f8f9fa;
        color: #5a5c69;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        padding: 15px;
    }
    .table-modern tbody tr {
        box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        background: white;
        border-radius: 8px;
    }
    .table-modern tbody td {
        border-top: 1px solid #f2f2f2;
        border-bottom: 1px solid #f2f2f2;
        padding: 15px;
        vertical-align: middle;
    }
    .table-modern tbody td:first-child {
        border-left: 1px solid #f2f2f2;
        border-top-left-radius: 8px;
        border-bottom-left-radius: 8px;
    }
    .table-modern tbody td:last-child {
        border-right: 1px solid #f2f2f2;
        border-top-right-radius: 8px;
        border-bottom-right-radius: 8px;
    }
    .note-text {
        font-size: 0.9rem;
        color: #444;
        line-height: 1.5;
        background: #f8f9fc;
        padding: 10px;
        border-radius: 8px;
        border-left: 3px solid #4e73df;
    }

    /* Dark theme compatibility */
    [bd-theme="bd-theme-dark"] .ced-report-wrap .report-card,
    [bd-theme="bd-theme-dark"] .ced-kpi-card,
    [bd-theme="bd-theme-dark"] .ced-projects-strip {
        background: var(--clr-bg-card, #1e293b);
        border-color: rgba(255, 255, 255, 0.08);
    }
    [bd-theme="bd-theme-dark"] .ced-report-header h1,
    [bd-theme="bd-theme-dark"] .ced-kpi-value {
        color: #f1f5f9;
    }
    [bd-theme="bd-theme-dark"] .note-text {
        background: rgba(255, 255, 255, 0.05);
        color: #e2e8f0;
    }

    @media print {
        .app-sidebar, .app-header, .page-header, .no-print, .main-footer {
            display: none !important;
        }
        .ced-report-wrap .report-card,
        .ced-kpi-card,
        .ced-projects-strip,
        .table-modern tbody tr {
            box-shadow: none !important;
            border: 1px solid #cbd5e1 !important;
        }
        .ced-kpi-grid {
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 10px !important;
        }
        .ced-kpi-card {
            padding: 10px 12px !important;
        }
        .ced-kpi-value {
            font-size: 1.25rem !important;
            color: #000 !important;
        }
        .print-only-header {
            display: block !important;
        }
    }
</style>

<div class="ced-report-wrap">
    <div class="container-fluid px-0">
        <!-- Page Header -->
        <div class="ced-report-header no-print">
            <div>
                <h1>CED Activity Report</h1>
                <small class="text-muted">
                    Follow-up activities &amp; assigned portfolio summary
                    (<?= date('d M Y', strtotime($startDate)) ?> – <?= date('d M Y', strtotime($endDate)) ?>)
                </small>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="javascript:void(0);">CED</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Activity Report</li>
                </ol>
            </nav>
        </div>

        <!-- Print Only Header -->
        <div class="d-none print-only-header mb-4 border-bottom pb-3">
            <div class="text-center mb-3">
                <p class="text-muted fs-14 mb-0 fw-bold">CED Activity Report</p>
            </div>
            <div class="row text-dark">
                <div class="col-6">
                    <p class="mb-1"><strong>Name:</strong> <?= isset($agentInfo['full_name']) ? htmlspecialchars($agentInfo['full_name']) : 'All Agents' ?></p>
                    <p class="mb-1"><strong>Employee ID:</strong> <?= isset($agentInfo['employee_id']) ? htmlspecialchars($agentInfo['employee_id']) : 'N/A' ?></p>
                    <p class="mb-1"><strong>Department:</strong> <?= isset($agentInfo['department_name']) ? htmlspecialchars($agentInfo['department_name']) : 'N/A' ?></p>
                </div>
                <div class="col-6 text-end">
                    <p class="mb-1"><strong>Designation:</strong> <?= isset($agentInfo['designation_name']) ? htmlspecialchars($agentInfo['designation_name']) : 'N/A' ?></p>
                    <p class="mb-1"><strong>Office Branch:</strong> <?= isset($agentInfo['office_branch']) ? htmlspecialchars($agentInfo['office_branch']) : 'N/A' ?></p>
                    <p class="mb-1"><strong>Date Range:</strong> <?= date('d M Y', strtotime($startDate)) ?> to <?= date('d M Y', strtotime($endDate)) ?></p>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="card report-card no-print">
            <div class="card-body p-3 p-md-4">
                <form method="GET" action="" id="filterForm" class="ced-filter-grid">
                    <input type="hidden" name="per_page" value="<?= $pgPerPage ?>">

                    <div>
                        <label class="form-label">Select CED Agent</label>
                        <select name="agent_id" class="form-select">
                            <option value="">-- All Agents --</option>
                            <?php foreach ($cedEmployees as $emp): ?>
                                <option value="<?= $emp['id'] ?>" <?= ($agentId == $emp['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($emp['full_name'] ?? '') ?> (<?= htmlspecialchars($emp['role_name'] ?? '') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Report Mode</label>
                        <select name="report_mode" id="reportMode" class="form-select" onchange="toggleInputs()">
                            <option value="daily" <?= ($reportMode == 'daily') ? 'selected' : '' ?>>Daily (Today)</option>
                            <option value="monthly" <?= ($reportMode == 'monthly') ? 'selected' : '' ?>>Monthly Breakdown</option>
                            <option value="yearly" <?= ($reportMode == 'yearly') ? 'selected' : '' ?>>Yearly Breakdown</option>
                            <option value="custom" <?= ($reportMode == 'custom') ? 'selected' : '' ?>>Custom Range</option>
                        </select>
                    </div>

                    <!-- Monthly / Yearly inputs -->
                    <div class="mode-input mode-monthly d-none">
                        <label class="form-label">Month</label>
                        <select name="report_month" class="form-select">
                            <?php for ($i = 1; $i <= 12; $i++): $mStr = sprintf("%02d", $i); ?>
                                <option value="<?= $mStr ?>" <?= ($reportMonth == $mStr) ? 'selected' : '' ?>>
                                    <?= date("F", mktime(0, 0, 0, $i, 10)) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="mode-input mode-monthly mode-yearly d-none">
                        <label class="form-label">Year</label>
                        <select name="report_year" class="form-select">
                            <?php for ($y = 2025; $y <= 2035; $y++): ?>
                                <option value="<?= $y ?>" <?= ($reportYear == $y) ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <!-- Custom inputs -->
                    <div class="mode-input mode-custom d-none">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="<?= htmlspecialchars($startDate ?? '') ?>">
                    </div>
                    <div class="mode-input mode-custom d-none">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="<?= htmlspecialchars($endDate ?? '') ?>">
                    </div>

                    <div class="ced-filter-actions">
                        <button type="submit" class="btn btn-primary px-3"><i class="bi bi-funnel me-1"></i> Filter</button>
                        <a href="<?= htmlspecialchars(strtok($_SERVER['REQUEST_URI'], '?')) ?>" class="btn btn-outline-secondary px-3"><i class="bi bi-arrow-counterclockwise me-1"></i> Reset</a>
                        <button type="button" class="btn btn-outline-success px-3" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function toggleInputs() {
                const mode = document.getElementById('reportMode').value;
                document.querySelectorAll('.mode-input').forEach(el => el.classList.add('d-none'));
                document.querySelectorAll('.mode-' + mode).forEach(el => el.classList.remove('d-none'));

                const startEl = document.getElementById('start_date');
                const endEl = document.getElementById('end_date');
                if (mode === 'custom') {
                    startEl.setAttribute('required', 'required');
                    endEl.setAttribute('required', 'required');
                } else {
                    startEl.removeAttribute('required');
                    endEl.removeAttribute('required');
                }
            }
            document.addEventListener("DOMContentLoaded", toggleInputs);
        </script>

        <!-- Summary KPI Grid (4 Equal Columns) -->
        <div class="ced-kpi-grid">
            <!-- 1. Assigned Projects & Portfolio -->
            <div class="ced-kpi-card ced-kpi-projects">
                <div class="ced-kpi-meta">
                    <div class="ced-kpi-label">Assigned Projects</div>
                    <div class="ced-kpi-value"><?= number_format($assignedProjectsCount) ?></div>
                    <div class="ced-kpi-sub">
                        <strong><?= number_format($portfolioClients) ?></strong> active clients &middot;
                        <strong><?= number_format($portfolioUnits) ?></strong> units
                    </div>
                </div>
                <div class="ced-kpi-icon">
                    <i class="bi bi-building"></i>
                </div>
            </div>

            <!-- 2. Clients Followed Up & Coverage -->
            <div class="ced-kpi-card ced-kpi-clients">
                <div class="ced-kpi-meta">
                    <div class="ced-kpi-label">Clients Followed Up</div>
                    <div class="ced-kpi-value"><?= number_format($totalClientsFollowed) ?></div>
                    <div class="ced-kpi-sub">
                        <?php if ($coveragePct !== null): ?>
                            Coverage: <strong class="text-success"><?= $coveragePct ?>%</strong> of assigned portfolio
                        <?php else: ?>
                            Unique clients contacted in range
                        <?php endif; ?>
                    </div>
                </div>
                <div class="ced-kpi-icon">
                    <i class="bi bi-people"></i>
                </div>
            </div>

            <!-- 3. Total Follow-up Notes -->
            <div class="ced-kpi-card ced-kpi-notes">
                <div class="ced-kpi-meta">
                    <div class="ced-kpi-label">Total Follow-up Notes</div>
                    <div class="ced-kpi-value"><?= number_format($totalFollowupNotes) ?></div>
                    <div class="ced-kpi-sub">
                        Lead + EMI &middot; Avg <strong><?= $avgNotesPerClient ?></strong> / client
                    </div>
                </div>
                <div class="ced-kpi-icon">
                    <i class="bi bi-journal-check"></i>
                </div>
            </div>
        </div>

        <!-- Assigned Projects List Strip (shown when a specific agent is selected) -->
        <?php if (!empty($agentId)): ?>
            <div class="ced-projects-strip">
                <span class="fw-bold text-muted" style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.4px;">
                    <i class="bi bi-buildings me-1 text-success"></i>Assigned Projects:
                </span>
                <?php if (!empty($assignedProjects)): ?>
                    <?php foreach ($assignedProjects as $proj): ?>
                        <span class="ced-project-pill">
                            <i class="bi bi-check-circle-fill" style="font-size: 0.7rem;"></i>
                            <?= htmlspecialchars($proj['name'] ?? '') ?>
                        </span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span class="text-muted" style="font-size: 0.82rem;">No active projects currently assigned to this agent.</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Breakdown Section -->
        <?php if ($reportMode != 'custom' && $reportMode != 'daily' && !empty($breakdownData)): ?>
        <div class="card report-card mb-4 no-print">
            <div class="card-header bg-transparent border-bottom-0 pt-4 pb-0">
                <div class="card-title fw-bold text-dark fs-16">
                    <i class="bi bi-bar-chart-fill text-success me-2"></i>
                    <?= ucfirst($reportMode) ?> Breakdown
                </div>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-modern w-100 text-center">
                        <thead>
                            <tr>
                                <th><?= $reportMode == 'monthly' ? 'Date' : 'Month' ?></th>
                                <th>Follow-ups Done</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $totalBreakdown = 0;
                            foreach ($breakdownData as $bRow):
                                $totalBreakdown += $bRow['count'];
                            ?>
                            <tr>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($bRow['label'] ?? '') ?></td>
                                <td>
                                    <span class="badge bg-success-transparent text-success px-3 py-2 rounded-pill fs-12">
                                        <?= $bRow['count'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="bg-light">
                                <td class="fw-bold text-dark text-end pe-4">Total</td>
                                <td class="fw-bold text-success fs-15"><?= $totalBreakdown ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Details Table -->
        <div class="card report-card">
            <div class="card-header bg-transparent border-bottom-0 pt-4 pb-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="card-title fw-bold text-dark fs-16">
                    <i class="bi bi-list-columns-reverse text-primary me-2"></i>
                    Activity Footprints (<?= htmlspecialchars($startDate ?? '') ?> to <?= htmlspecialchars($endDate ?? '') ?>)
                </div>
                <div class="text-muted fs-13">
                    Showing <strong><?= number_format($pgStart) ?>–<?= number_format($pgEnd) ?></strong> of <strong><?= number_format($pgTotal) ?></strong> records
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-modern w-100">
                        <thead>
                            <tr>
                                <th style="width: 5%">#</th>
                                <th style="width: 15%">Added Date &amp; Time</th>
                                <th style="width: 15%">Agent</th>
                                <th style="width: 20%">Client Details</th>
                                <th style="width: 20%">Project / Flat</th>
                                <th style="width: 25%">Discussion Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($details)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="bi bi-inbox fs-1" style="opacity: 0.3"></i>
                                            <p class="mt-2">No activities found for the selected period.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $i = $pgStart; foreach ($details as $row): ?>
                                    <tr>
                                        <td class="fw-bold text-muted align-middle"><?= $i++ ?></td>
                                        <td class="align-middle">
                                            <div class="fw-bold text-dark"><?= date('d M Y', strtotime($row['follow_up_date'])) ?></div>
                                            <?php if (!empty($row['follow_up_time'])): ?>
                                                <div class="text-muted fs-12"><i class="bi bi-clock me-1"></i><?= date('h:i A', strtotime($row['follow_up_time'])) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="align-middle">
                                            <span class="badge bg-light text-primary border border-primary-subtle p-2">
                                                <i class="bi bi-person-badge me-1"></i><?= htmlspecialchars($row['created_by_name'] ?? 'Unknown') ?>
                                            </span>
                                        </td>
                                        <td class="align-middle">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($row['client_name'] ?? 'Unnamed Client') ?></div>
                                            <?php if (!empty($row['client_phone'])): ?>
                                                <div class="text-muted fs-12"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($row['client_phone']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="align-middle">
                                            <?php if (!empty($row['project_name']) || !empty($row['flat_name'])): ?>
                                                <div class="fw-semibold text-dark"><?= htmlspecialchars($row['project_name'] ?? '') ?></div>
                                                <?php if (!empty($row['flat_name'])): ?>
                                                    <div class="text-muted fs-12"><?= htmlspecialchars($row['flat_name']) ?></div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="align-middle">
                                            <?php if (!empty($row['follow_up_type'])): ?>
                                                <div class="mb-2">
                                                    <span class="badge bg-success"><i class="bi bi-tag me-1"></i>Follow Up for: <?= htmlspecialchars($row['follow_up_type'] ?? '') ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="note-text p-2 bg-light rounded text-dark fs-13">
                                                <?= nl2br(htmlspecialchars($row['discussion_note'] ?? 'No note provided')) ?>
                                            </div>
                                            <?php if (!empty($row['activity_date']) || !empty($row['activity_mode']) || !empty($row['activity_reason'])): ?>
                                                <div class="mt-2 p-2 bg-white border border-secondary-subtle rounded fs-12">
                                                    <?php if (!empty($row['activity_date'])): ?>
                                                        <div class="mb-1"><i class="bi bi-calendar-event me-1 text-muted"></i><span class="fw-semibold text-muted">Activity Date:</span> <?= date('d M Y', strtotime($row['activity_date'])) ?></div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($row['activity_mode'])): ?>
                                                        <div class="mb-1"><i class="bi bi-info-circle me-1 text-muted"></i><span class="fw-semibold text-muted">Mode:</span> <?= htmlspecialchars($row['activity_mode'] ?? '') ?></div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($row['activity_reason'])): ?>
                                                        <div><i class="bi bi-question-circle me-1 text-muted"></i><span class="fw-semibold text-muted">Reason:</span> <?= htmlspecialchars($row['activity_reason'] ?? '') ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($pgTotalPages > 1): ?>
                    <nav class="d-flex flex-wrap justify-content-between align-items-center mt-3 pt-3 border-top no-print gap-2" aria-label="CED report pagination">
                        <div class="text-muted fs-13">
                            Page <strong><?= $pgPage ?></strong> of <strong><?= $pgTotalPages ?></strong>
                        </div>
                        <ul class="pagination pagination-sm mb-0">
                            <?php
                                $buildPageUrl = static function ($targetPage) use ($baseQueryParams) {
                                    return '?' . http_build_query(array_merge($baseQueryParams, ['page' => $targetPage]));
                                };
                                $winStart = max(1, $pgPage - 2);
                                $winEnd   = min($pgTotalPages, $pgPage + 2);
                            ?>
                            <li class="page-item <?= ($pgPage <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= ($pgPage > 1) ? htmlspecialchars($buildPageUrl($pgPage - 1)) : '#' ?>">Prev</a>
                            </li>
                            <?php if ($winStart > 1): ?>
                                <li class="page-item"><a class="page-link" href="<?= htmlspecialchars($buildPageUrl(1)) ?>">1</a></li>
                                <?php if ($winStart > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                            <?php endif; ?>
                            <?php for ($p = $winStart; $p <= $winEnd; $p++): ?>
                                <li class="page-item <?= ($p === $pgPage) ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= htmlspecialchars($buildPageUrl($p)) ?>"><?= $p ?></a>
                                </li>
                            <?php endfor; ?>
                            <?php if ($winEnd < $pgTotalPages): ?>
                                <?php if ($winEnd < $pgTotalPages - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                                <li class="page-item"><a class="page-link" href="<?= htmlspecialchars($buildPageUrl($pgTotalPages)) ?>"><?= $pgTotalPages ?></a></li>
                            <?php endif; ?>
                            <li class="page-item <?= ($pgPage >= $pgTotalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= ($pgPage < $pgTotalPages) ? htmlspecialchars($buildPageUrl($pgPage + 1)) : '#' ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
