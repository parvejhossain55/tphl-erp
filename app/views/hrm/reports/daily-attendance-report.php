<style>
    :root {
        --bg: #0d1117;
        --surface: #161b22;
        --border: #21262d;
        --accent: #58a6ff;
        --green: #3fb950;
        --yellow: #d29922;
        --red: #f85149;
        --purple: #bc8cff;
        --orange: #ffa657;
        --text: #e6edf3;
        --muted: #8b949e;
    }





    /* ── Header ── */
    .report-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 2.5rem;
    }

    .report-header .brand {
        display: flex;
        align-items: center;
        gap: .75rem;
    }

    .brand-icon {
        width: 44px;
        height: 44px;
        background: linear-gradient(135deg, #58a6ff, #bc8cff);
        border-radius: 10px;
        display: grid;
        place-items: center;
        font-size: 1.3rem;
    }

    .brand-text h1 {
        font-size: 1.25rem;
        font-weight: 800;
        letter-spacing: -.02em;
    }

    .brand-text p {
        font-size: .75rem;
        /* color: var(--muted); */
        margin-top: 1px;
    }

    .report-meta {
        text-align: right;
    }

    .report-meta .date-badge {
        display: inline-block;
        /* background: #161b22; */
        border: 2px solid #36b914;
        border-radius: 8px;
        padding: .35rem .85rem;
        font-family: 'DM Mono', monospace;
        font-size: .8rem;
        color: #127616;
        letter-spacing: .04em;
    }

    .report-meta .shift-info {
        font-size: .72rem;
        /* color: var(--muted); */
        margin-top: 4px;
    }

    /* ── Stat Cards ── */
    .stat-card {
        border-radius: 14px;
        padding: 1.25rem 1.4rem;
        position: relative;
        overflow: hidden;
        transition: transform .2s, box-shadow .2s;
    }

    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, .4);
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        border-radius: 14px 14px 0 0;
    }



    .stat-label {
        font-size: .7rem;
        text-transform: uppercase;
        letter-spacing: .1em;
        color: var(--muted);
        margin-bottom: .4rem;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 800;
        line-height: 1;
    }

    .stat-card.total .stat-value {
        color: #58a6ff;
    }

    .stat-card.present .stat-value {
        color: var(--green);
    }

    .stat-card.late .stat-value {
        color: var(--yellow);
    }

    .stat-card.absent .stat-value {
        color: var(--red);
    }

    .stat-card.leave .stat-value {
        color: var(--purple);
    }

    .stat-card.dayoff .stat-value {
        color: var(--orange);
    }

    .stat-sub {
        font-size: .72rem;
        color: var(--muted);
        margin-top: .35rem;
    }

    .stat-icon {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        font-size: 2.2rem;
        opacity: .07;
    }

    /* ── Attendance % Ring ── */
    .attendance-ring-wrap {
        background: #161b22;
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1.5rem;
        flex-wrap: wrap;
    }

    .ring-svg {
        flex-shrink: 0;
    }

    .ring-track {
        fill: none;
        stroke: var(--border);
        stroke-width: 10;
    }

    .ring-fill {
        fill: none;
        stroke: url(#ringGrad);
        stroke-width: 10;
        stroke-linecap: round;
        stroke-dasharray: 283;
        stroke-dashoffset: calc(283 - (283 * 87.5 / 100));
        transform: rotate(-90deg);
        transform-origin: 50% 50%;
        transition: stroke-dashoffset 1s ease;
    }

    .ring-label {
        text-anchor: middle;
        dominant-baseline: middle;
    }

    .ring-info h2 {
        font-size: 2.4rem;
        font-weight: 800;
        color: var(--green);
        line-height: 1;
    }

    .ring-info p {
        font-size: .8rem;
        color: var(--muted);
        margin-top: .3rem;
    }

    .ring-info .ring-sub {
        display: flex;
        gap: 1rem;
        margin-top: .75rem;
        flex-wrap: wrap;
    }

    .ring-chip {
        display: flex;
        align-items: center;
        gap: .4rem;
        font-size: .72rem;
        color: var(--muted);
    }

    .ring-chip span {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
    }




    .filter-tabs {
        display: flex;
        gap: .4rem;
    }

    .filter-tab {
        padding: .25rem .7rem;
        border-radius: 20px;
        font-size: .7rem;
        font-weight: 600;
        cursor: pointer;
        border: 1px solid var(--border);
        background: transparent;
        color: var(--muted);
        transition: all .15s;
    }

    .filter-tab.active {
        background: #58a6ff;
        border-color: #58a6ff;
        color: #fff;
    }




    .emp-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: inline-grid;
        place-items: center;
        font-size: .7rem;
        font-weight: 700;
        flex-shrink: 0;
        color: #fff;
    }

    .emp-name-wrap {
        display: flex;
        align-items: center;
        gap: .6rem;
    }

    .emp-name {
        font-weight: 600;
        font-size: .84rem;
    }

    .emp-dept {
        font-size: .68rem;
        color: var(--muted);
    }

    .badge-status {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .2rem .65rem;
        border-radius: 20px;
        font-size: .7rem;
        font-weight: 600;
    }



    .bs-present {
        background: rgba(63, 185, 80, .15);
        color: var(--green);
    }

    .bs-late {
        background: rgba(210, 153, 34, .15);
        color: var(--yellow);
    }

    .bs-absent {
        background: rgba(248, 81, 73, .15);
        color: var(--red);
    }

    .bs-leave {
        background: rgba(188, 140, 255, .15);
        color: var(--purple);
    }

    .bs-dayoff {
        background: rgba(255, 166, 87, .15);
        color: var(--orange);
    }

    .time-mono {
        font-family: 'DM Mono', monospace;
        font-size: .78rem;
        color: var(--muted);
    }

    .time-mono.late {
        color: var(--yellow);
    }

    /* Progress bar */
    .mini-bar {
        width: 80px;
        height: 6px;
        background: var(--border);
        border-radius: 10px;
        overflow: hidden;
        display: inline-block;
    }

    .mini-bar-fill {
        height: 100%;
        border-radius: 10px;
        background: var(--green);
    }

    /* ── Footer ── */
    .report-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 2rem;
        flex-wrap: wrap;
        gap: .5rem;
    }

    .report-footer p {
        font-size: .72rem;
        color: var(--muted);
    }

    .print-btn {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .45rem 1.1rem;
        background: #161b22;
        border: 1px solid var(--border);
        border-radius: 8px;
        font-size: .78rem;
        font-weight: 600;
        color: var(--text);
        cursor: pointer;
        transition: border-color .15s;
    }

    .print-btn:hover {
        border-color: var(--accent);
        color: var(--accent);
    }

    @media (max-width: 576px) {
        .report-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .report-meta {
            text-align: left;
        }

        .ring-info h2 {
            font-size: 1.8rem;
        }
    }
</style>


<div class="row">

    <!-- ── Header ── -->
    <div class="report-header">
        <div class="brand">
            <div class="brand-text">
                <h1>Attendance Report</h1>
                <p>Human Resource Management System</p>
            </div>
        </div>

        <div class="report-meta d-flex gap-2">
            <div class="date-badge mb-2">
                &nbsp;<?= date('l, d F Y') ?>
            </div>
            <div><a href="<?= $GLOBALS['base_url'] ?>/export-daily-report?date=<?= htmlspecialchars($reportDate) ?>" class="btn btn-success">
                    Export CSV
                </a></div>
            <div><a href="<?= $GLOBALS['base_url'] ?>/export-daily-report-pdf?date=<?= htmlspecialchars($reportDate ?? date('Y-m-d')) ?>" class="btn btn-danger">
                    Export PDF
                </a></div>
        </div>
    </div>

    <!-- ── Stat Cards ── -->
    <div class="row mb-4">
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card__wrapper stat-card total">
                <div class="stat-label">Total Employee</div>
                <div class="stat-value"><?= $employeeCount ?? 0 ?></div>
                <div class="stat-sub">Active headcount</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card__wrapper stat-card present">
                <div class="stat-label">Present</div>
                <div class="stat-value"><?= $todaySummary['Present'] ?></div>
                <div class="stat-sub">On time today</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card__wrapper stat-card late">
                <div class="stat-label">Late</div>
                <div class="stat-value"><?= $todaySummary['Late'] ?></div>
                <div class="stat-sub">After 9:10 AM</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card__wrapper stat-card absent">
                <div class="stat-label">Absent</div>
                <div class="stat-value"><?= $todaySummary['Absent'] ?></div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card__wrapper stat-card leave">
                <div class="stat-label">On Leave</div>
                <div class="stat-value"><?= $todaySummary['On Leave'] ?></div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card__wrapper stat-card leave">
                <div class="stat-label">Day Off</div>
                <div class="stat-value"><?= $todaySummary['Day Off'] ?></div>
            </div>
        </div>
    </div>

    <!--  Employee Table ── -->
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table no-border-table mb-20 multiple_tables">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Department</th>
                            <th>Total Employee</th>
                            <th>Present</th>
                            <th>Late</th>
                            <th>Absent</th>
                            <th>Leave</th>
                            <th>Day Off</th>
                            <th>Attendance %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportData as $i => $row):
                            // Department-wise attendance % = (present / total) * 100
                            $attendancePercent = ($row['present_count'] / max($row['total_employee'], 1)) * 100;
                        ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($row['department_name']) ?></td>
                                <td><span class="bd-badge bg-primary"><?= $row['total_employee'] ?></span></td>
                                <td><span class="badge-status bs-present"><?= $row['present_count'] ?></span></td>
                                <td><span class="badge-status bs-late"><?= $row['late_count'] ?></span></td>
                                <td><span class="badge-status bs-absent"><?= $row['absent_count'] ?></span></td>
                                <td><span class="badge-status bs-leave"><?= $row['leave_count'] ?></td>
                                <td><span class="badge-status bs-dayoff"><?= $row['day_off_count'] ?></td>
                                <td><?= number_format($attendancePercent, 2) ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>
        </div>
    </div>