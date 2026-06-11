<style>
    /* ── Page Header ── */
    .page-header {
        background: #fff;
        border-bottom: 1px solid #e4e7ec;
        padding: 18px 28px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }

    .page-header h1 {
        font-size: 18px;
        font-weight: 600;
        color: #111827;
        letter-spacing: -0.3px;
    }

    .page-header p {
        font-size: 13px;
        color: #6b7280;
        margin-top: 2px;
    }

    .period-badge {
        /* background: #EFF6FF; */
        /* color: #1D4ED8; */
        font-size: 12px;
        font-weight: 500;
        padding: 5px 14px;
        border-radius: 99px;
        border: 1px solid #BFDBFE;
        font-family: 'DM Mono', monospace;
    }

    /* ── Filter Bar ── */
    .filter-bar {
        /* background: #fff; */
        /* border-bottom: 1px solid #e4e7ec; */
        padding: 14px 28px;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }

    .filter-bar select,
    .filter-bar input {
        height: 36px;
        font-size: 13px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        padding: 0 12px;
        /* background: #fff; */
        /* color: #374151; */
        outline: none;
        font-family: 'DM Sans', sans-serif;
    }

    .filter-bar select:focus,
    .filter-bar input:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    .btn-filter {
        height: 36px;
        padding: 0 18px;
        font-size: 13px;
        font-weight: 500;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        font-family: 'DM Sans', sans-serif;
    }

    .btn-filter.primary {
        background: #4F46E5;
        color: #fff;
    }

    .btn-filter.primary:hover {
        background: #4338CA;
    }

    .btn-filter.secondary {
        background: #F3F4F6;
        color: #374151;
        border: 1px solid #d1d5db;
    }

    .btn-filter.secondary:hover {
        background: #E5E7EB;
    }

    /* ── Main Content ── */
    .main-content {
        padding: 24px 28px;
    }

    /* ── Summary Strip ── */
    .summary-strip {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 12px;
        margin-bottom: 28px;
    }

    .summary-card {
        /* background: #fff; */
        border: 1px solid #e4e7ec;
        border-radius: 12px;
        padding: 16px 18px;
        border-top: 3px solid transparent;
    }

    .summary-card.blue {
        border-top-color: #6366F1;
    }

    .summary-card.green {
        border-top-color: #10B981;
    }

    .summary-card.amber {
        border-top-color: #F59E0B;
    }

    .summary-card.coral {
        border-top-color: #EF4444;
    }

    .summary-card.purple {
        border-top-color: #8B5CF6;
    }

    .summary-card .s-label {
        font-size: 12px;
        /* color: #6b7280; */
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 6px;
    }

    .summary-card .s-val {
        font-size: 24px;
        font-weight: 600;
        /* color: #111827; */
        line-height: 1;
    }

    .summary-card .s-sub {
        font-size: 12px;
        /* color: #9CA3AF; */
        margin-top: 4px;
    }

    /* ── Section title ── */
    .section-label {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        /* color: #9CA3AF; */
        margin-bottom: 14px;
    }

    /* ── Person KPI Card ── */
    .person-block {
        /* background: #fff; */
        border: 1px solid #e4e7ec;
        border-radius: 14px;
        margin-bottom: 20px;
        overflow: hidden;
    }

    .person-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        border-bottom: 1px solid #f1f3f5;
        flex-wrap: wrap;
        gap: 10px;
    }

    .person-identity {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 600;
        flex-shrink: 0;
    }

    .av-blue {
        background: #EEF2FF;
        color: #4F46E5;
    }

    .av-green {
        background: #ECFDF5;
        color: #059669;
    }

    .av-amber {
        background: #FFFBEB;
        color: #D97706;
    }

    .av-coral {
        background: #FEF2F2;
        color: #DC2626;
    }

    .av-purple {
        background: #F5F3FF;
        color: #7C3AED;
    }

    .person-name {
        font-size: 15px;
        font-weight: 600;
        /* color: #111827; */
    }

    .person-role {
        font-size: 12px;
        /* color: #6B7280; */
    }

    .person-badges {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
    }

    .rank-badge {
        font-size: 11px;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 99px;
        font-family: 'DM Mono', monospace;
    }

    .rank-1 {
        background: #FEF9C3;
        /* color: #854D0E; */
        border: 1px solid #FDE047;
    }

    .rank-2 {
        background: #F1F5F9;
        color: #475569;
        border: 1px solid #CBD5E1;
    }

    .rank-3 {
        background: #FFF7ED;
        /* color: #9A3412; */
        border: 1px solid #FED7AA;
    }

    .rank-n {
        /* background: #F3F4F6; */
        /* color: #6B7280; */
        border: 1px solid #E5E7EB;
    }

    .perf-badge {
        font-size: 11px;
        font-weight: 500;
        padding: 3px 10px;
        border-radius: 99px;
    }

    .perf-top {
        background: #ECFDF5;
        color: #065F46;
        border: 1px solid #A7F3D0;
    }

    .perf-good {
        /* background: #EFF6FF; */
        /* color: #1E40AF; */
        border: 1px solid #BFDBFE;
    }

    .perf-avg {
        background: #FFFBEB;
        color: #92400E;
        border: 1px solid #FDE68A;
    }

    .perf-low {
        background: #FEF2F2;
        color: #991B1B;
        border: 1px solid #FECACA;
    }

    /* ── KPI Grid inside card ── */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: 0;
        border-bottom: 1px solid #f1f3f5;
    }

    .kpi-cell {
        padding: 14px 18px;
        border-right: 1px solid #f1f3f5;
    }

    .kpi-cell:last-child {
        border-right: none;
    }

    .kpi-cell .kc-label {
        font-size: 11px;
        /* color: #9CA3AF; */
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 5px;
    }

    .kpi-cell .kc-val {
        font-size: 20px;
        font-weight: 600;
        /* color: #111827; */
        line-height: 1;
    }

    .kpi-cell .kc-sub {
        font-size: 11px;
        /* color: #6B7280; */
        margin-top: 3px;
    }

    .kpi-cell .kc-bar {
        height: 4px;
        /* background: #F3F4F6; */
        border-radius: 99px;
        margin-top: 8px;
        overflow: hidden;
    }

    .kpi-cell .kc-bar-fill {
        height: 100%;
        border-radius: 99px;
    }

    /* ── Project table inside card ── */
    .project-section {
        padding: 14px 18px;
    }

    .project-section .ps-title {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        /* color: #9CA3AF; */
        margin-bottom: 10px;
    }

    .proj-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    .proj-table th {
        text-align: left;
        padding: 6px 10px;
        color: #6B7280;
        font-weight: 500;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        border-bottom: 1px solid #F3F4F6;
    }

    .proj-table td {
        padding: 8px 10px;
        /* color: #374151; */
        border-bottom: 1px solid #F9FAFB;
        vertical-align: middle;
    }

    .proj-table tr:last-child td {
        border-bottom: none;
    }

    .proj-table .td-num {
        font-weight: 600;
        /* color: #111827; */
        font-family: 'DM Mono', monospace;
    }

    .proj-table .td-money {
        font-weight: 600;
        color: #059669;
        font-family: 'DM Mono', monospace;
        font-size: 12px;
    }

    .status-dot {
        display: inline-block;
        width: 7px;
        height: 7px;
        border-radius: 50%;
        margin-right: 5px;
    }

    .dot-green {
        background: #10B981;
    }

    .dot-amber {
        background: #F59E0B;
    }

    .dot-red {
        background: #EF4444;
    }

    .dot-gray {
        background: #D1D5DB;
    }

    /* ── Responsive ── */
    @media (max-width: 640px) {
        .main-content {
            padding: 16px 14px;
        }

        .page-header,
        .filter-bar {
            padding: 14px 14px;
        }

        .kpi-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .kpi-cell {
            border-right: 1px solid #f1f3f5;
        }

        .kpi-cell:nth-child(even) {
            border-right: none;
        }
    }

    @media print {
        body {
            background: #fff;
        }

        .filter-bar,
        .btn-filter {
            display: none;
        }
    }
</style>


<!-- ── Page Header ── -->

<?php
$periodMonth = $selectedMonth ?? null;
$periodYear = $selectedYear ?? null;
$months = [
    1 => 'January',
    2 => 'February',
    3 => 'March',
    4 => 'April',
    5 => 'May',
    6 => 'June',
    7 => 'July',
    8 => 'August',
    9 => 'September',
    10 => 'October',
    11 => 'November',
    12 => 'December'
];

$periodText = '';
if (!empty($periodMonth)) {
    $periodText .= $months[$periodMonth] . ' ';
} else {
    $periodText .= 'All Months ';
}
if (!empty($periodYear)) {
    $periodText .= $periodYear;
} else {
    $periodText .= 'All Years';
}
?>
<div class="app__slide-wrapper">

    <div class="mb-2">
        <h3>Sales Team KPI Performance</h3>
    </div>
    
</div>

<!-- ── Filter Bar ── -->
<div class="filter-bar">
    <span class="period-badge"><?= htmlspecialchars($periodText) ?></span>
    <form method="GET" action="" class="d-flex flex-wrap gap-10 align-items-center w-100">
        <input type="text" name="q" class="form-control" placeholder="Search executive name, email or phone"
            value="<?= htmlspecialchars($searchTerm ?? '') ?>">

        <select name="executive">
            <option value="">All Executives</option>
            <?php foreach ($executives as $executive): ?>
                <option value="<?= $executive['id'] ?>" <?= isset($selectedExecutive) && $selectedExecutive == $executive['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($executive['full_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="month">
            <option value="">All Months</option>
            <?php foreach ($months as $num => $label): ?>
                <option value="<?= $num ?>" <?= ($selectedMonth !== null && $selectedMonth !== '' && $selectedMonth == $num) ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>

        <select name="year">
            <option value="">All Years</option>
            <?php
            $yearOptions = [2026, 2025, 2024];
            ?>
            <?php foreach ($yearOptions as $y): ?>
                <option value="<?= $y ?>" <?= ($selectedYear !== null && $selectedYear !== '' && $selectedYear == $y) ? 'selected' : '' ?>><?= $y ?></option>
            <?php endforeach; ?>
        </select>

        <label style="display:flex;align-items:center;gap:6px;">
            <input type="checkbox" name="has_visits" value="1" <?= !empty($hasVisits) ? 'checked' : '' ?>>
            Visits only
        </label>
        <label style="display:flex;align-items:center;gap:6px;">
            <input type="checkbox" name="has_bookings" value="1" <?= !empty($hasBookings) ? 'checked' : '' ?>>
            Bookings only
        </label>

        <div class="d-flex gap-10">
            <button type="submit" class="btn-filter primary">Filter</button>
            <a href="<?= $GLOBALS['base_url'] ?>/sales-team-performance" class="btn-filter secondary">Reset</a>
        </div>
    </form>
</div>

<!-- ── Main Content ── -->
<div class="card__wrapper ">

    <!-- Summary Strip -->
    <div class="summary-strip">
        <div class="summary-card blue">
            <div class="s-label">Total leads assigned</div>
            <div class="s-val"><?= $summary['total_leads'] ?? 0 ?></div>
            <div class="s-sub">Across <?= $summary['total_users'] ?? 0 ?> executives</div>
        </div>
        <div class="summary-card green">
            <div class="s-label">Total site visits</div>
            <div class="s-val"><?= $summary['total_visits'] ?? 0 ?></div>
            <div class="s-sub">Completed visits</div>
        </div>
        <div class="summary-card amber">
            <div class="s-label">Total bookings</div>
            <div class="s-val"><?= $summary['total_bookings'] ?? 0 ?></div>
            <div class="s-sub">Confirmed bookings</div>
        </div>
        <div class="summary-card coral">
            <div class="s-label">Money collected</div>
            <div class="s-val">৳<?= number_format($summary['total_money'] ?? 0, 0) ?></div>
            <div class="s-sub">Total collected</div>
        </div>
        <div class="summary-card purple">
            <div class="s-label">Avg conversion rate</div>
            <div class="s-val"><?= $summary['avg_conversion'] ?? 0 ?>%</div>
            <div class="s-sub">Leads → bookings</div>
        </div>
    </div>

    <div class="section-label">Individual KPI Cards</div>

    <?php if (!empty($kpiData)): ?>
        <?php foreach ($kpiData as $index => $data): ?>
            <?php
            $user = $data['user'];
            $initials = strtoupper(substr($user['full_name'], 0, 2));
            $avatarClass = ['av-blue', 'av-green', 'av-amber', 'av-coral', 'av-purple'][$index % 5];
            ?>
            <div class="person-block">
                <div class="person-header">
                    <div class="person-identity">
                        <div class="avatar <?= $avatarClass ?>"><?= $initials ?></div>
                        <div>
                            <div class="person-name"><?= htmlspecialchars($user['full_name']) ?></div>
                            <div class="person-role"><?= htmlspecialchars($user['role_name'] ?? 'Sales Executive') ?></div>
                        </div>
                    </div>
                    <div class="person-badges">
                        <span class="rank-badge rank-n">#<?= $index + 1 ?> Rank</span>
                        <span class="perf-badge perf-good">Active</span>
                    </div>
                </div>

                <div class="kpi-grid">
                    <div class="kpi-cell">
                        <div class="kc-label">Leads assigned</div>
                        <div class="kc-val"><?= $data['total_leads'] ?></div>
                        <div class="kc-sub">Total assigned</div>
                    </div>
                    <div class="kpi-cell">
                        <div class="kc-label">Site visits</div>
                        <div class="kc-val"><?= $data['total_visits'] ?></div>
                        <div class="kc-sub">Completed visits</div>
                    </div>
                    <div class="kpi-cell">
                        <div class="kc-label">Bookings</div>
                        <div class="kc-val"><?= $data['total_bookings'] ?></div>
                        <div class="kc-sub">Confirmed bookings</div>
                    </div>
                    <div class="kpi-cell">
                        <div class="kc-label">Money collected</div>
                        <div class="kc-val" style="font-size:16px;">৳<?= number_format($data['total_money'], 0) ?></div>
                        <div class="kc-sub">Total collected</div>
                    </div>
                    <div class="kpi-cell">
                        <div class="kc-label">Conversion rate</div>
                        <div class="kc-val"><?= $data['conversion_rate'] ?>%</div>
                        <div class="kc-sub">Leads → bookings</div>
                        <div class="kc-bar">
                            <div class="kc-bar-fill" style="width:<?= min($data['conversion_rate'], 100) ?>%;background:#F59E0B;"></div>
                        </div>
                    </div>
                </div>
                <?php if (!empty($data['project_bookings']) && $data['total_bookings'] > 0): ?>
                    <?php foreach ($data['project_bookings'] as $project): ?>
                        <?php
                        // Check if this project has any paid flats
                        $userId = $data['user']['id'];
                        $projectId = $project['project_id'];
                        $flatsSql = "
            SELECT f.flat_name, lb.booking_date, lp.amount
            FROM lead_bookings lb
            JOIN flats f ON lb.flat_id = f.id
            JOIN lead_payments lp ON lp.booking_id = lb.id AND lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified'
            WHERE lb.project_id = ? AND lb.deleted_at IS NULL AND lb.booking_status IN ('completed', 'booked') AND lb.sales_by_id = ?
        ";
                        $stmtFlats = $this->db->prepare($flatsSql);
                        $stmtFlats->bind_param("ii", $projectId, $userId);
                        $stmtFlats->execute();
                        $flatsResult = $stmtFlats->get_result();

                        if ($flatsResult->num_rows === 0) {
                            continue; // Skip this project if no paid flats
                        }
                        ?>
                        <div class="project-section border-bottom">
                            <div class="ps-title text-primary"><i class="fa-solid fa-building me-1"></i> Project: <?= htmlspecialchars($project['project_name'] ?? '') ?></div>
                            <div class="d-flex flex-wrap gap-2 my-2">
                                <?php while ($flat = $flatsResult->fetch_assoc()): ?>
                                    <div class="p-2 border rounded d-flex align-items-center gap-2  shadow-sm" style="min-width: 235px; border-radius: 8px;">
                                        <div class="avatar d-flex align-items-center justify-content-center" style="width: 34px; height: 34px; border-radius: 50%; font-size: 13px; background: #EEF2FF; color: #4F46E5; flex-shrink: 0;">
                                            <i class="fa-solid fa-house-chimney"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size: 13px;"><?= htmlspecialchars($flat['flat_name'] ?? '') ?></div>
                                            <div class="text-muted" style="font-size: 10px;">Booked: <?= date('d M, Y', strtotime($flat['booking_date'])) ?></div>
                                            <div class="text-success fw-bold" style="font-size: 11px;">Paid: ৳<?= number_format($flat['amount'], 0) ?></div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div class="project-section">
                    <div class="ps-title">Performance Summary</div>
                    <table class="proj-table">
                        <thead>
                            <tr>
                                <th>Metric</th>
                                <th>Value</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Leads Assigned</td>
                                <td class="td-num"><?= $data['total_leads'] ?></td>
                                <td><span class="status-dot dot-green"></span>Active</td>
                            </tr>
                            <tr>
                                <td>Site Visits</td>
                                <td class="td-num"><?= $data['total_visits'] ?></td>
                                <td><span class="status-dot dot-green"></span>Completed</td>
                            </tr>
                            <tr>
                                <td>Bookings</td>
                                <td class="td-num"><?= $data['total_bookings'] ?></td>
                                <td><span class="status-dot dot-amber"></span>In Progress</td>
                            </tr>
                            <tr>
                                <td>Money Collected</td>
                                <td class="td-money">৳<?= number_format($data['total_money'], 0) ?></td>
                                <td><span class="status-dot dot-green"></span>Collected</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="person-block">
            <div class="person-header">
                <div class="person-identity">
                    <div class="avatar av-gray">NA</div>
                    <div>
                        <div class="person-name">No Data Available</div>
                        <div class="person-role">No sales executives with assigned leads found</div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>



    <div class="row my-4">
        <div class="col-12 d-flex justify-content-center">
            <?= paginateLinksSecondary($paginationRoute, $currentPage, $totalPages); ?>
        </div>
    </div>