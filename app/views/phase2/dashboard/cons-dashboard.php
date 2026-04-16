<?php
$addPage = true;
$show_breadcrumb = false;
?>
<style>
  /* ── Uses project CSS variables already defined in the theme ── */
  .stat-card {
    border-radius: 12px;
    padding: 18px 20px;
    height: 100%;
    position: relative;
    overflow: hidden;
  }

  .stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 3px;
    height: 100%;
    border-radius: 12px 0 0 12px;
  }

  .stat-label {
    font-size: 10px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    margin-bottom: 6px;
  }

  .stat-value {
    font-weight: 700;
    font-size: 28px;
    line-height: 1;
  }

  .stat-sub {
    font-size: 11px;
    margin-top: 4px;
  }

  .stat-icon {
    position: absolute;
    right: 18px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 28px;
    opacity: .12;
  }

  .card-green {
    --card-accent: #2ecc71;
  }

  .card-amber {
    --card-accent: #f39c12;
  }

  .card-red {
    --card-accent: #e74c3c;
  }

  .card-blue {
    --card-accent: #3498db;
  }

  .section-title {
    font-weight: 700;
    font-size: 14px;
    letter-spacing: .4px;
    text-transform: uppercase;
    margin-bottom: 14px;
  }

  .section-title span {
    font-weight: 400;
    font-size: 11px;
    margin-left: 8px;
    text-transform: none;
    letter-spacing: 0;
  }

  .panel-card {
    border-radius: 12px;
    padding: 20px;
    height: 100%;
  }

  /* Progress */
  .prog-row {
    margin-bottom: 14px;
  }

  .prog-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 6px;
    font-size: 12px;
  }

  .prog-label strong {
    font-weight: 600;
  }

  .prog-track {
    height: 7px;
    border-radius: 6px;
    overflow: hidden;
    background: rgba(0, 0, 0, .08);
  }

  .prog-fill {
    height: 100%;
    border-radius: 6px;
  }

  .fill-green {
    background: linear-gradient(90deg, #27ae60, #2ecc71);
  }

  .fill-amber {
    background: linear-gradient(90deg, #d68910, #f39c12);
  }

  .fill-red {
    background: linear-gradient(90deg, #c0392b, #e74c3c);
  }

  .fill-blue {
    background: linear-gradient(90deg, #2980b9, #3498db);
  }

  /* Donut (CSS only) */
  .donut-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
  }

  .donut {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    background: conic-gradient(#f39c12 0% 70.3%, rgba(0, 0, 0, .1) 70.3% 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
  }

  .donut::after {
    content: '';
    width: 100px;
    height: 100px;
    border-radius: 50%;
    position: absolute;
  }

  .donut-text {
    position: absolute;
    z-index: 2;
    text-align: center;
  }

  .donut-pct {
    font-weight: 800;
    font-size: 26px;
    line-height: 1;
  }

  .donut-sub {
    font-size: 10px;
  }

  .donut-legend {
    display: flex;
    gap: 20px;
    margin-top: 16px;
  }

  .legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
  }

  .legend-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
  }

  .donut-stats {
    display: flex;
    justify-content: space-between;
    width: 100%;
    margin-top: 16px;
    padding-top: 14px;
    border-top: 1px solid rgba(0, 0, 0, .08);
  }

  .donut-stat-val {
    font-size: 18px;
    font-weight: 700;
  }

  .donut-stat-lbl {
    font-size: 10px;
  }

  /* Table */
  .dash-table {
    width: 100%;
  }

  .dash-table th {
    font-size: 10px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    font-weight: 600;
    padding: 0 0 10px;
    border-bottom: 1px solid rgba(0, 0, 0, .08);
  }

  .dash-table td {
    padding: 12px 0;
    border-bottom: 1px solid rgba(0, 0, 0, .06);
    font-size: 12.5px;
    vertical-align: middle;
  }

  .dash-table tr:last-child td {
    border-bottom: none;
  }

  .proj-name {
    font-weight: 600;
    font-size: 13px;
  }

  .proj-loc {
    font-size: 11px;
  }

  .manager-cell {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .manager-av {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 700;
    flex-shrink: 0;
  }

  .status-badge {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .5px;
    padding: 3px 10px;
    border-radius: 20px;
  }

  .status-ongoing {
    background: rgba(52, 152, 219, .15);
    color: #3498db;
  }

  .status-completed {
    background: rgba(46, 204, 113, .15);
    color: #2ecc71;
  }

  .health-score {
    font-weight: 700;
    font-size: 17px;
  }

  .score-low {
    color: #e74c3c;
  }

  .score-mid {
    color: #f39c12;
  }

  .score-high {
    color: #2ecc71;
  }

  /* Health Cards */
  .health-card {
    border-radius: 10px;
    padding: 14px 16px;
    margin-bottom: 10px;
  }

  .health-card:last-child {
    margin-bottom: 0;
  }

  .health-card-title {
    font-weight: 600;
    font-size: 13px;
  }

  .health-card-loc {
    font-size: 11px;
  }

  .health-score-badge {
    font-size: 18px;
    font-weight: 800;
  }

  .at-risk-tag {
    font-size: 9px;
    letter-spacing: 1px;
    text-transform: uppercase;
    border-radius: 4px;
    padding: 2px 7px;
    font-weight: 700;
    background: rgba(231, 76, 60, .12);
    color: #e74c3c;
  }

  .health-meta {
    font-size: 10px;
  }

  /* Alert Cards */
  .alert-card {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    border-radius: 10px;
    padding: 12px 14px;
    margin-bottom: 8px;
  }

  .alert-card:last-child {
    margin-bottom: 0;
  }

  .alert-icon-circle {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
  }

  .alert-icon-amber {
    background: rgba(243, 156, 18, .12);
    color: #f39c12;
  }

  .alert-icon-red {
    background: rgba(231, 76, 60, .12);
    color: #e74c3c;
  }

  .alert-icon-blue {
    background: rgba(52, 152, 219, .12);
    color: #3498db;
  }

  .alert-title {
    font-size: 12.5px;
    font-weight: 600;
  }

  .alert-detail {
    font-size: 11px;
  }

  /* Contractor */
  .contractor-row {
    margin-bottom: 12px;
  }

  .contractor-row:last-child {
    margin-bottom: 0;
  }

  .contractor-name {
    font-size: 12.5px;
    font-weight: 600;
  }

  .contractor-type {
    font-size: 11px;
  }

  /* Stock Alert */
  .stock-alert-card {
    border-radius: 10px;
    padding: 12px 14px;
    margin-bottom: 8px;
  }

  .stock-alert-card:last-child {
    margin-bottom: 0;
  }

  .stock-name {
    font-size: 12.5px;
    font-weight: 600;
  }

  .stock-type {
    font-size: 10px;
  }

  .stock-qty {
    font-size: 16px;
    font-weight: 700;
  }

  .stock-min {
    font-size: 11px;
  }

  /* Labour */
  .labour-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid rgba(0, 0, 0, .06);
  }

  .labour-row:last-child {
    border-bottom: none;
  }

  .labour-label {
    font-size: 12px;
  }

  .labour-val {
    font-size: 17px;
    font-weight: 700;
  }

  .labour-val.neg {
    color: #e74c3c;
  }

  .labour-val.pos {
    color: #2ecc71;
  }

  /* Financial */
  .fin-card {
    border-radius: 10px;
    padding: 14px 16px;
  }

  .fin-label {
    font-size: 10px;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 4px;
  }

  .fin-val {
    font-size: 22px;
    font-weight: 700;
  }

  .fin-tag {
    display: inline-block;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 4px;
    margin-left: 6px;
  }

  .fin-tag-amber {
    background: rgba(243, 156, 18, .12);
    color: #f39c12;
  }

  .fin-tag-red {
    background: rgba(231, 76, 60, .12);
    color: #e74c3c;
  }

  .fin-tag-green {
    background: rgba(46, 204, 113, .12);
    color: #2ecc71;
  }

  /* Wrap panels & stat-cards with card__wrapper look */
  .panel-card,
  .stat-card,
  .health-card,
  .alert-card,
  .stock-alert-card,
  .fin-card {
    border: 1px solid var(--border-color, rgba(0, 0, 0, .08));

  }


</style>








  <!-- ── KPI Row 1 ── -->
  <div class="row  mb-3">
    <div class="col-3">
      <div class="stat-card card__wrapper">
        <div class="stat-label">Total Projects</div>
        <div class="stat-value">4</div>
        <div class="stat-sub">3 ongoing · 1 completed</div>

      </div>
    </div>
    <div class="col-3">
      <div class="stat-card card__wrapper">
        <div class="stat-label">Budget Utilization</div>
        <div class="stat-value">70%</div>
        <div class="stat-sub">৳20.8 Cr of ৳29.5 Cr</div>

      </div>
    </div>
    <div class="col-3">
      <div class="stat-card card__wrapper">
        <div class="stat-label">Total Purchases</div>
        <div class="stat-value">৳20.2 L</div>
        <div class="stat-sub">4 orders pending</div>

      </div>
    </div>
    <div class="col-3">
      <div class="stat-card card__wrapper">
        <div class="stat-label">Stock Alerts</div>
        <div class="stat-value">2</div>
        <div class="stat-sub">Items below minimum level</div>

      </div>
    </div>
  </div>

  <!-- ── KPI Row 2 ── -->
  <div class="row  mb-4">
    <div class="col-3">
      <div class="stat-card card__wrapper">
        <div class="stat-label">Active Workers</div>
        <div class="stat-value">1</div>
        <div class="stat-sub">৳800 unpaid wages</div>

      </div>
    </div>
    <div class="col-3">
      <div class="stat-card card__wrapper">
        <div class="stat-label">Labour Costs</div>
        <div class="stat-value">৳800</div>
        <div class="stat-sub">Total wages to date</div>

      </div>
    </div>
    <div class="col-3">
      <div class="stat-card card__wrapper">
        <div class="stat-label">Pending Approvals</div>
        <div class="stat-value">0</div>
        <div class="stat-sub">All clear</div>

      </div>
    </div>
    <div class="col-3">
      <div class="stat-card card__wrapper">
        <div class="stat-label">Stock Value</div>
        <div class="stat-value">৳58.3 L</div>
        <div class="stat-sub">Total inventory value</div>

      </div>
    </div>
  </div>

  <!-- ── Charts Row ── -->
  <div class="row  mb-4">
    <!-- Project Progress -->
    <div class="col-8">
      <div class="panel-card card__wrapper">
        <div class="section-title">Project Progress Overview</div>
        <div class="prog-row">
          <div class="prog-label"><strong>Green Valley Residency</strong><span>35%</span></div>
          <div class="prog-track">
            <div class="prog-fill fill-amber" style="width:35%"></div>
          </div>
        </div>
        <div class="prog-row">
          <div class="prog-label"><strong>Premium Heights Tower</strong><span>48%</span></div>
          <div class="prog-track">
            <div class="prog-fill fill-amber" style="width:48%"></div>
          </div>
        </div>
        <div class="prog-row">
          <div class="prog-label"><strong>Riverside Apartments</strong><span>100%</span></div>
          <div class="prog-track">
            <div class="prog-fill fill-green" style="width:100%"></div>
          </div>
        </div>
        <div class="prog-row" style="margin-bottom:0">
          <div class="prog-label"><strong>Skyline Plaza</strong><span>88%</span></div>
          <div class="prog-track">
            <div class="prog-fill fill-green" style="width:88%"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Budget Donut -->
    <div class="col-4">
      <div class="panel-card card__wrapper">
        <div class="section-title">Budget Utilization</div>
        <div class="donut-wrap">
          <div style="position:relative; display:flex; align-items:center; justify-content:center;">
            <div class="donut"></div>
            <div class="donut-text">
              <div class="donut-pct">70.3%</div>
              <div class="donut-sub">Used</div>
            </div>
          </div>
          <div class="donut-legend">
            <div class="legend-item">
              <div class="legend-dot" style="background:var(--accent-amber)"></div>Used Budget
            </div>
            <div class="legend-item">
              <div class="legend-dot" style="background:var(--progress-track)"></div>Remaining
            </div>
          </div>
          <div class="donut-stats">
            <div class="text-center">
              <div class="donut-stat-val">৳29.5 Cr</div>
              <div class="donut-stat-lbl">Total Budget</div>
            </div>
            <div class="text-center">
              <div class="donut-stat-val" style="color:var(--accent-amber)">৳8.8 Cr</div>
              <div class="donut-stat-lbl">Remaining</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── Financial Summary + Health Score ── -->
  <div class="row  mb-4">
    <!-- Financial Summary -->
    <div class="col-5">
      <div class="panel-card card__wrapper">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="section-title" style="margin-bottom:0">Financial Summary</div>
          <span class="fin-tag fin-tag-amber">70% Utilized</span>
        </div>
        <div class="row g-2">
          <div class="col-6">
            <div class="fin-card">
              <div class="fin-label">Total Project Budget</div>
              <div class="fin-val">৳29.5 Cr</div>
            </div>
          </div>
          <div class="col-6">
            <div class="fin-card">
              <div class="fin-label">Purchase Costs</div>
              <div class="fin-val">৳20.2 L</div>
            </div>
          </div>
          <div class="col-6">
            <div class="fin-card">
              <div class="fin-label">Labour Costs</div>
              <div class="fin-val" style="color:var(--accent-red)">৳800</div>
            </div>
          </div>
          <div class="col-6">
            <div class="fin-card">
              <div class="fin-label">Remaining Budget</div>
              <div class="fin-val" style="color:var(--accent-amber)">৳8.8 Cr</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Project Health Score -->
    <div class="col-7">
      <div class="panel-card card__wrapper">
        <div class="section-title">Project Health Score</div>

        <div class="health-card">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <div class="health-card-title">Green Valley Residency</div>
              <div class="health-card-loc">📍 Banani, Dhaka</div>
            </div>
            <div class="text-end">
              <div class="health-score-badge score-low">70 / 100</div>
              <div><span class="at-risk-tag">At Risk</span></div>
            </div>
          </div>
          <div class="row g-2">
            <div class="col-4">
              <div class="health-meta">Progress</div>
              <div class="prog-track mt-1">
                <div class="prog-fill fill-amber" style="width:35%"></div>
              </div>
              <div class="health-meta mt-1">35%</div>
            </div>
            <div class="col-4">
              <div class="health-meta">Budget</div>
              <div class="prog-track mt-1">
                <div class="prog-fill fill-amber" style="width:48%"></div>
              </div>
              <div class="health-meta mt-1">48%</div>
            </div>
            <div class="col-4">
              <div class="health-meta">Delays</div>
              <div style="font-family:'Barlow Condensed',sans-serif;font-size:16px;font-weight:700;color:var(--accent-red)">62d</div>
            </div>
          </div>
        </div>

        <div class="health-card">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <div class="health-card-title">Premium Heights Tower</div>
              <div class="health-card-loc">📍 Gulshan-1, Dhaka</div>
            </div>
            <div class="text-end">
              <div class="health-score-badge score-mid">80 / 100</div>
              <div><span class="at-risk-tag">At Risk</span></div>
            </div>
          </div>
          <div class="row g-2">
            <div class="col-4">
              <div class="health-meta">Progress</div>
              <div class="prog-track mt-1">
                <div class="prog-fill fill-amber" style="width:48%"></div>
              </div>
              <div class="health-meta mt-1">48%</div>
            </div>
            <div class="col-4">
              <div class="health-meta">Budget</div>
              <div class="prog-track mt-1">
                <div class="prog-fill fill-amber" style="width:50%"></div>
              </div>
              <div class="health-meta mt-1">50%</div>
            </div>
            <div class="col-4">
              <div class="health-meta">Delays</div>
              <div style="font-family:'Barlow Condensed',sans-serif;font-size:16px;font-weight:700;color:var(--accent-amber)">17d</div>
            </div>
          </div>
        </div>

        <div class="health-card" style="margin-bottom:0">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <div class="health-card-title">Skyline Plaza</div>
              <div class="health-card-loc">📍 Uttara, Dhaka</div>
            </div>
            <div class="text-end">
              <div class="health-score-badge score-mid">75 / 100</div>
              <div><span class="at-risk-tag">At Risk</span></div>
            </div>
          </div>
          <div class="row g-2">
            <div class="col-4">
              <div class="health-meta">Progress</div>
              <div class="prog-track mt-1">
                <div class="prog-fill fill-green" style="width:88%"></div>
              </div>
              <div class="health-meta mt-1">88%</div>
            </div>
            <div class="col-4">
              <div class="health-meta">Budget</div>
              <div class="prog-track mt-1">
                <div class="prog-fill fill-amber" style="width:90%"></div>
              </div>
              <div class="health-meta mt-1">90%</div>
            </div>
            <div class="col-4">
              <div class="health-meta">Delays</div>
              <div style="font-family:'Barlow Condensed',sans-serif;font-size:16px;font-weight:700;color:var(--accent-amber)">6d</div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- ── Project Status Table ── -->
  <div class="panel-card mb-4 card__wrapper">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="section-title" style="margin-bottom:0">Project Status Overview</div>
      <a href="#" style="font-size:11px;color:var(--accent-green);text-decoration:none">View All →</a>
    </div>
    <table class="dash-table">
      <thead>
        <tr>
          <th>Project</th>
          <th>Manager</th>
          <th>Progress</th>
          <th>Budget Used</th>
          <th>Health</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>
            <div class="proj-name">Green Valley Residency</div>
            <div class="proj-loc">📍 Banani, Dhaka · Mar 2025</div>
          </td>
          <td>
            <div class="manager-cell">
              <div class="manager-av">FR</div>
              Fatima Rahman
            </div>
          </td>
          <td>
            <div style="margin-bottom:4px;font-size:12px;font-weight:600;color:var(--accent-amber)">35%</div>
            <div class="prog-track" style="width:120px">
              <div class="prog-fill fill-amber" style="width:35%"></div>
            </div>
          </td>
          <td>
            <div style="color:var(--text-primary);font-weight:600">৳2.2 Cr</div>
            <div style="font-size:11px;color:var(--text-muted)">of ৳14.5 Cr</div>
          </td>
          <td><span class="health-score score-low">70</span>
            <div style="font-size:10px;color:var(--text-muted)">At Risk</div>
          </td>
          <td><span class="status-badge status-ongoing">Ongoing</span></td>
        </tr>
        <tr>
          <td>
            <div class="proj-name">Premium Heights Tower</div>
            <div class="proj-loc">📍 Gulshan-1, Dhaka · Jun 2025</div>
          </td>
          <td>
            <div class="manager-cell">
              <div class="manager-av">KA</div>
              Md. Karim Ahmed
            </div>
          </td>
          <td>
            <div style="margin-bottom:4px;font-size:12px;font-weight:600;color:var(--accent-amber)">48%</div>
            <div class="prog-track" style="width:120px">
              <div class="prog-fill fill-amber" style="width:48%"></div>
            </div>
          </td>
          <td>
            <div style="color:var(--text-primary);font-weight:600">৳4.3 Cr</div>
            <div style="font-size:11px;color:var(--text-muted)">of ৳8.5 Cr</div>
          </td>
          <td><span class="health-score score-mid">80</span>
            <div style="font-size:10px;color:var(--text-muted)">At Risk</div>
          </td>
          <td><span class="status-badge status-ongoing">Ongoing</span></td>
        </tr>
        <tr>
          <td>
            <div class="proj-name">Riverside Apartments</div>
            <div class="proj-loc">📍 Mirpur DOHS, Dhaka · Feb 2024</div>
          </td>
          <td>
            <div class="manager-cell">
              <div class="manager-av">AB</div>
              Aisha Begum
            </div>
          </td>
          <td>
            <div style="margin-bottom:4px;font-size:12px;font-weight:600;color:var(--accent-green)">100%</div>
            <div class="prog-track" style="width:120px">
              <div class="prog-fill fill-green" style="width:100%"></div>
            </div>
          </td>
          <td>
            <div style="color:var(--text-primary);font-weight:600">৳3.5 Cr</div>
            <div style="font-size:11px;color:var(--text-muted)">of ৳3.5 Cr</div>
          </td>
          <td><span class="health-score score-high">86</span>
            <div style="font-size:10px;color:var(--text-muted)">Healthy</div>
          </td>
          <td><span class="status-badge status-completed">Completed</span></td>
        </tr>
        <tr>
          <td>
            <div class="proj-name">Skyline Plaza</div>
            <div class="proj-loc">📍 Uttara, Dhaka · Dec 2024</div>
          </td>
          <td>
            <div class="manager-cell">
              <div class="manager-av">RI</div>
              Md. Rafiq Islam
            </div>
          </td>
          <td>
            <div style="margin-bottom:4px;font-size:12px;font-weight:600;color:var(--accent-green)">88%</div>
            <div class="prog-track" style="width:120px">
              <div class="prog-fill fill-green" style="width:88%"></div>
            </div>
          </td>
          <td>
            <div style="color:var(--text-primary);font-weight:600">৳10.8 Cr</div>
            <div style="font-size:11px;color:var(--text-muted)">of ৳11.9 Cr</div>
          </td>
          <td><span class="health-score score-mid">75</span>
            <div style="font-size:10px;color:var(--text-muted)">At Risk</div>
          </td>
          <td><span class="status-badge status-ongoing">Ongoing</span></td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- ── Bottom Row ── -->
  <div class="row ">
    <!-- Smart Alerts -->
    <div class="col-3">
      <div class="panel-card card__wrapper">
        <div class="section-title">Smart Alerts</div>
        <div class="alert-card">
          <div class="alert-icon-circle alert-icon-amber">⏱</div>
          <div>
            <div class="alert-title">1 Delayed Phase</div>
            <div class="alert-detail">Project phases behind schedule — review timeline</div>
          </div>
        </div>
        <div class="alert-card">
          <div class="alert-icon-circle alert-icon-red">📦</div>
          <div>
            <div class="alert-title">2 Low Stock Items</div>
            <div class="alert-detail">Materials below minimum inventory levels</div>
          </div>
        </div>
        <div class="alert-card">
          <div class="alert-icon-circle alert-icon-blue">💸</div>
          <div>
            <div class="alert-title">Unpaid Labour Wages</div>
            <div class="alert-detail">৳800 in outstanding payments</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Contractor Performance -->
    <div class="col-3">
      <div class="panel-card card__wrapper">
        <div class="section-title">Contractor Performance</div>
        <div class="contractor-row">
          <div class="d-flex justify-content-between mb-1">
            <div>
              <div class="contractor-name">Foundation Experts Ltd</div>
              <div class="contractor-type">Foundation</div>
            </div>
            <div style="font-family:'Barlow Condensed',sans-serif;font-size:16px;font-weight:700;color:var(--accent-green)">100%</div>
          </div>
          <div class="prog-track">
            <div class="prog-fill fill-green" style="width:100%"></div>
          </div>
        </div>
        <div class="contractor-row">
          <div class="d-flex justify-content-between mb-1">
            <div>
              <div class="contractor-name">ABC Developers</div>
              <div class="contractor-type">Land Development</div>
            </div>
            <div style="font-family:'Barlow Condensed',sans-serif;font-size:16px;font-weight:700;color:var(--accent-green)">100%</div>
          </div>
          <div class="prog-track">
            <div class="prog-fill fill-green" style="width:100%"></div>
          </div>
        </div>
        <div class="contractor-row">
          <div class="d-flex justify-content-between mb-1">
            <div>
              <div class="contractor-name">PowerLine Electric</div>
              <div class="contractor-type">Electrical</div>
            </div>
            <div style="font-family:'Barlow Condensed',sans-serif;font-size:16px;font-weight:700;color:var(--accent-amber)">95%</div>
          </div>
          <div class="prog-track">
            <div class="prog-fill fill-amber" style="width:95%"></div>
          </div>
        </div>
        <div class="contractor-row" style="margin-bottom:0">
          <div class="d-flex justify-content-between mb-1">
            <div>
              <div class="contractor-name">AquaFlow Solutions</div>
              <div class="contractor-type">Plumbing</div>
            </div>
            <div style="font-family:'Barlow Condensed',sans-serif;font-size:16px;font-weight:700;color:var(--accent-amber)">90%</div>
          </div>
          <div class="prog-track">
            <div class="prog-fill fill-amber" style="width:90%"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Low Stock Alerts -->
    <div class="col-3">
      <div class="panel-card card__wrapper">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="section-title" style="margin-bottom:0">Low Stock Alerts</div>
          <span class="fin-tag fin-tag-red">2 Items</span>
        </div>
        <div class="stock-alert-card">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <div class="stock-name">Stone Chips</div>
              <div class="stock-type">Aggregate</div>
            </div>
            <span class="fin-tag fin-tag-amber">Low</span>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="stock-qty" style="color:var(--accent-amber)">350 CFT</span>
            <span class="stock-min">Min: 500 CFT</span>
          </div>
          <div class="prog-track">
            <div class="prog-fill fill-amber" style="width:70%"></div>
          </div>
        </div>
        <div class="stock-alert-card">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <div class="stock-name">White Cement</div>
              <div class="stock-type">Cement</div>
            </div>
            <span class="fin-tag fin-tag-red">Critical</span>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="stock-qty" style="color:var(--accent-red)">60 Bag</span>
            <span class="stock-min">Min: 100 Bag</span>
          </div>
          <div class="prog-track">
            <div class="prog-fill fill-red" style="width:60%"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Labour Overview -->
    <div class="col-3">
      <div class="panel-card card__wrapper">
        <div class="section-title">Labour Overview</div>
        <div class="labour-row">
          <span class="labour-label">Total Workers</span>
          <span class="labour-val">1</span>
        </div>
        <div class="labour-row">
          <span class="labour-label">Total Wages</span>
          <span class="labour-val">৳800</span>
        </div>
        <div class="labour-row">
          <span class="labour-label">Paid</span>
          <span class="labour-val pos">৳0</span>
        </div>
        <div class="labour-row">
          <span class="labour-label">Unpaid</span>
          <span class="labour-val neg">৳800</span>
        </div>
        <div class="labour-row">
          <span class="labour-label">Overtime Hours</span>
          <span class="labour-val">0 hrs</span>
        </div>
        <div class="labour-row">
          <span class="labour-label">Total Entries</span>
          <span class="labour-val">—</span>
        </div>
      </div>
    </div>

  </div>
  <!-- end bottom row -->

  <div style="height:32px"></div>
</div><!-- end main-content -->