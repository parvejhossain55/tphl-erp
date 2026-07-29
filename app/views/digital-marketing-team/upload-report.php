<?php
/**
 * Digital Marketing Lead Upload Report
 * Premium UI Design
 */
?>
<style>
  :root {
    --brand-primary: #0d47a1;
    --brand-dark: #0a2d6e;
    --brand-light: #42a5f5;
    --brand-gradient: linear-gradient(135deg, #0d47a1 0%, #1e88e5 100%);
    --surface-bg: #f8fafc;
    --card-radius: 16px;
    --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    --border-color: #e2e8f0;
  }

  .report-page-wrapper {
    background-color: var(--surface-bg);
    min-height: 100vh;
    padding-bottom: 2rem;
  }

  .report-header-card {
    background: #ffffff;
    border-radius: var(--card-radius);
    border: 1px solid var(--border-color);
    box-shadow: var(--card-shadow);
    padding: 1.5rem 1.75rem;
    margin-bottom: 1.5rem;
  }

  .report-title {
    font-weight: 800;
    color: var(--brand-dark);
    letter-spacing: -0.02em;
    margin: 0;
  }

  .executive-profile-badge {
    background: linear-gradient(135deg, #f0f7ff 0%, #e3f2fd 100%);
    border: 1px solid #bbdefb;
    border-radius: 14px;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
  }

  .exec-avatar {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: var(--brand-gradient);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(13, 71, 161, 0.2);
  }

  .exec-info-item {
    font-size: 0.85rem;
  }
  .exec-info-item span.label {
    color: #64748b;
    font-weight: 600;
    display: block;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }
  .exec-info-item span.val {
    color: var(--brand-dark);
    font-weight: 700;
    font-size: 0.95rem;
  }

  .stat-widget {
    background: #ffffff;
    border-radius: var(--card-radius);
    border: 1px solid var(--border-color);
    box-shadow: var(--card-shadow);
    padding: 1.5rem;
    position: relative;
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .stat-widget:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
  }
  .stat-widget::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: var(--brand-gradient);
  }
  .stat-widget .stat-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    background: #eef2ff;
    color: var(--brand-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
  }
  .stat-widget .stat-value {
    font-size: 2.2rem;
    font-weight: 800;
    color: var(--brand-dark);
    line-height: 1.1;
  }
  .stat-widget .stat-label {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    font-weight: 700;
  }

  .filter-card {
    background: #ffffff;
    border-radius: var(--card-radius);
    border: 1px solid var(--border-color);
    box-shadow: var(--card-shadow);
    padding: 1.25rem 1.5rem;
    margin-bottom: 1.5rem;
  }

  .custom-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    background: #ffffff;
    border-bottom: 1px solid var(--border-color);
    border-top-left-radius: var(--card-radius);
    border-top-right-radius: var(--card-radius);
  }
  .custom-card-header h6 {
    margin: 0;
    font-weight: 700;
    color: var(--brand-dark);
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .data-card {
    background: #ffffff;
    border-radius: var(--card-radius);
    border: 1px solid var(--border-color);
    box-shadow: var(--card-shadow);
    overflow: hidden;
    height: 100%;
  }

  .table-custom {
    margin-bottom: 0;
  }
  .table-custom thead th {
    background: #f8fafc;
    color: #475569;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 0.85rem 1.25rem;
    border-bottom: 1px solid var(--border-color);
  }
  .table-custom tbody td {
    padding: 0.85rem 1.25rem;
    vertical-align: middle;
    color: #334155;
    font-size: 0.875rem;
    border-bottom: 1px solid #f1f5f9;
  }
  .table-custom tbody tr:last-child td {
    border-bottom: none;
  }
  .table-custom tbody tr:hover {
    background-color: #f8fafc;
  }

  .progress-sm {
    height: 6px;
    border-radius: 10px;
    background-color: #e2e8f0;
    overflow: hidden;
  }
  .progress-bar-custom {
    background: var(--brand-gradient);
    border-radius: 10px;
  }

  /* Hide print header on web view */
  .print-header {
    display: none;
  }

  /* Hide theme switchers or extraneous controls */
  .theme-container, .theme-switcher, .layout-setting, .settings-btn, #sidebar-right, .header-element, .theme-layout, .dark-layout, .mode-change, [class*="theme-mode"], [id*="theme-mode"], [class*="dark-mode"], [id*="dark-mode"] {
    display: none !important;
  }

  @media print {
    .sidebar, .navbar, .page-header, .page-header-breadcrumb, .filter-section, .btn, footer, .main-header, .theme-container, .theme-switcher, .layout-setting, .settings-btn, #sidebar-right, .header-element, .theme-layout, .dark-layout, .mode-change, [class*="theme-mode"], [id*="theme-mode"], [class*="dark-mode"], [id*="dark-mode"] {
      display: none !important;
    }
    .report-page-wrapper {
      background: #fff !important;
      padding: 0 !important;
    }
    .print-header {
      display: block !important;
      text-align: center;
      margin-bottom: 2rem;
    }
    .print-header h2 {
      font-size: 1.8rem;
      font-weight: 800;
      color: #000;
      margin-bottom: 0.25rem;
    }
    .print-header p {
      font-size: 0.9rem;
      color: #555;
    }
    .exec-info-print {
      border: 1.5px solid #333;
      border-radius: 8px;
      padding: 12px;
      margin-top: 15px;
      margin-bottom: 15px;
      text-align: left;
    }
    .exec-info-print table {
      width: 100%;
    }
    .exec-info-print td {
      padding: 4px 8px;
      font-size: 0.85rem;
    }
    .stat-widget, .data-card, .filter-card, .report-header-card {
      box-shadow: none !important;
      border: 1px solid #ddd !important;
      margin-bottom: 1.5rem !important;
    }
  }
</style>

<div class="report-page-wrapper">
  <div class="container-fluid">

    <!-- Header & Breadcrumb -->
    <div class="report-header-card d-flex flex-wrap align-items-center justify-content-between gap-3 page-header-breadcrumb mt-3">
      <div>
        <h4 class="report-title">Lead Upload Report</h4>
        <nav aria-label="breadcrumb" class="mt-1">
          <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="#" class="text-decoration-none text-muted">Digital Marketing</a></li>
            <li class="breadcrumb-item active text-primary fw-semibold" aria-current="page">Upload Report</li>
          </ol>
        </nav>
      </div>
      <div>
        <button onclick="window.print()" class="btn btn-primary px-3 shadow-sm rounded-3">
          <i class="bi bi-printer me-2"></i> Print / Export PDF
        </button>
      </div>
    </div>

    <!-- Print Only Header -->
    <div class="print-header">
      <h2>Lead Upload Report</h2>
      <p>Generated Date: <?= date('d M Y, h:i A') ?></p>
      <?php if ($selectedExecutive): ?>
      <div class="exec-info-print">
        <table>
          <tr>
            <td><strong>Executive Name:</strong> <?= htmlspecialchars($selectedExecutive['full_name'] ?? 'N/A') ?></td>
            <td><strong>Employee ID:</strong> <?= htmlspecialchars($selectedExecutive['employee_id'] ?? 'N/A') ?></td>
          </tr>
          <tr>
            <td><strong>Designation:</strong> <?= htmlspecialchars($selectedExecutive['designation'] ?? 'N/A') ?></td>
            <td><strong>Department:</strong> <?= htmlspecialchars($selectedExecutive['department'] ?? 'N/A') ?></td>
          </tr>
        </table>
      </div>
      <?php endif; ?>
      <p>
        <strong>Applied Filter:</strong> 
        <?php 
            $d = $filters['date_filter'];
            if($d == 'today') echo 'Today (' . date('d M Y') . ')';
            elseif($d == 'this_month') echo 'This Month (' . date('M Y') . ')';
            elseif($d == 'this_year') echo 'This Year (' . date('Y') . ')';
            elseif($d == 'date_range') echo 'From: ' . htmlspecialchars($filters['from_date']) . ' To: ' . htmlspecialchars($filters['to_date']);
            elseif($d == 'custom_month') echo 'Month: ' . htmlspecialchars($filters['specific_month']) . '/' . htmlspecialchars($filters['specific_month_year']);
            elseif($d == 'custom_year') echo 'Year: ' . htmlspecialchars($filters['year']);
            else echo 'All Time';
        ?>
      </p>
    </div>

    <!-- Selected Executive Profile Banner (If filtered) -->
    <?php if ($selectedExecutive): ?>
    <div class="executive-profile-badge d-flex align-items-center justify-content-between flex-wrap gap-3 shadow-sm">
      <div class="d-flex align-items-center gap-3">
        <div class="exec-avatar">
          <?= strtoupper(substr($selectedExecutive['full_name'] ?? 'E', 0, 1)) ?>
        </div>
        <div>
          <h5 class="mb-1 text-dark fw-bold ms-2"><?= htmlspecialchars($selectedExecutive['full_name'] ?? '') ?></h5>
          <span class="badge bg-primary bg-opacity-10 text-light ms-2 px-2 py-1 rounded-pill">
            <i class="bi bi-person-badge me-1"></i> ID: <?= htmlspecialchars($selectedExecutive['employee_id'] ?? 'N/A') ?>
          </span>
        </div>
      </div>
      <div class="d-flex align-items-center gap-4 flex-wrap">
        <div class="exec-info-item">
          <span class="label">Designation</span>
          <span class="val"><?= htmlspecialchars($selectedExecutive['designation'] ?? 'N/A') ?></span>
        </div>
        <div class="exec-info-item">
          <span class="label">Department</span>
          <span class="val"><?= htmlspecialchars($selectedExecutive['department'] ?? 'N/A') ?></span>
        </div>
        <div class="exec-info-item">
          <span class="label">Filter Mode</span>
          <span class="val text-capitalize"><?= str_replace('_', ' ', $filters['date_filter']) ?></span>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Filter Form -->
    <div class="filter-card filter-section">
      <form action="" method="GET">
        <div class="row  align-items-end">
          <div class="col-md-3 col-sm-6">
            <label class="form-label small text-secondary fw-semibold">Executive</label>
            <select name="created_by" class="form-select form-select-sm select2">
              <option value="">All Executives</option>
              <?php foreach ($executives as $ex): ?>
                <option value="<?= $ex['id'] ?>" <?= $filters['created_by'] == $ex['id'] ? 'selected' : '' ?> >
                  <?= htmlspecialchars($ex['full_name'] ?? '') ?> (<?= htmlspecialchars($ex['employee_id'] ?? '') ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-2 col-sm-6">
            <label class="form-label small text-secondary fw-semibold">Project Category</label>
            <select name="project_category_id" class="form-select form-select-sm select2">
              <option value="">All Categories</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $filters['project_category_id'] == $cat['id'] ? 'selected' : '' ?> >
                  <?= htmlspecialchars($cat['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-2 col-sm-6">
            <label class="form-label small text-secondary fw-semibold">Project</label>
            <select name="project_id" class="form-select form-select-sm select2">
              <option value="">All Projects</option>
              <?php foreach ($projects as $proj): ?>
                <option value="<?= $proj['id'] ?>" <?= $filters['project_id'] == $proj['id'] ? 'selected' : '' ?> >
                  <?= htmlspecialchars($proj['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-2 col-sm-6">
            <label class="form-label small text-secondary fw-semibold">Platform</label>
            <select name="platform" class="form-select form-select-sm select2">
              <option value="">All Platforms</option>
              <?php foreach ($platforms as $plat): ?>
                <option value="<?= $plat ?>" <?= $filters['platform'] == $plat ? 'selected' : '' ?> >
                  <?= htmlspecialchars($plat) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-3 col-sm-6">
            <label class="form-label small text-secondary fw-semibold">Time Filter</label>
            <select name="date_filter" class="form-select form-select-sm" onchange="toggleDateInputs(this.value)">
              <option value="today" <?= $filters['date_filter'] == 'today' ? 'selected' : '' ?>>Today</option>
              <option value="this_month" <?= $filters['date_filter'] == 'this_month' ? 'selected' : '' ?>>This Month</option>
              <option value="this_year" <?= $filters['date_filter'] == 'this_year' ? 'selected' : '' ?>>This Year</option>
              <option value="date_range" <?= $filters['date_filter'] == 'date_range' ? 'selected' : '' ?>>Date Range (From-To)</option>
              <option value="custom_month" <?= $filters['date_filter'] == 'custom_month' ? 'selected' : '' ?>>Specific Month</option>
              <option value="custom_year" <?= $filters['date_filter'] == 'custom_year' ? 'selected' : '' ?>>Specific Year</option>
              <option value="all" <?= $filters['date_filter'] == 'all' ? 'selected' : '' ?>>All Time</option>
            </select>
          </div>

          <div class="col-md-3 col-sm-6" id="dateRangeWrap" style="display: <?= $filters['date_filter'] == 'date_range' ? 'block' : 'none' ?>;">
            <label class="form-label small text-secondary fw-semibold">From & To Date</label>
            <div class="d-flex gap-2">
                <input type="date" name="from_date" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['from_date']) ?>">
                <input type="date" name="to_date" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['to_date']) ?>">
            </div>
          </div>

          <div class="col-md-3 col-sm-6" id="customMonthWrap" style="display: <?= $filters['date_filter'] == 'custom_month' ? 'block' : 'none' ?>;">
            <label class="form-label small text-secondary fw-semibold">Month & Year</label>
            <div class="d-flex gap-2">
                <select name="specific_month" class="form-select form-select-sm">
                    <?php 
                    $months = ['01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'May','06'=>'Jun','07'=>'Jul','08'=>'Aug','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dec'];
                    foreach($months as $mVal => $mName) {
                        $sel = ($filters['specific_month'] == $mVal) ? 'selected' : '';
                        echo "<option value=\"$mVal\" $sel>$mName</option>";
                    }
                    ?>
                </select>
                <select name="specific_month_year" class="form-select form-select-sm">
                    <?php 
                    for($y=2026; $y<=2030; $y++) {
                        $sel = ($filters['specific_month_year'] == $y) ? 'selected' : '';
                        echo "<option value=\"$y\" $sel>$y</option>";
                    }
                    ?>
                </select>
            </div>
          </div>

          <div class="col-md-2 col-sm-6" id="customYearWrap" style="display: <?= $filters['date_filter'] == 'custom_year' ? 'block' : 'none' ?>;">
            <label class="form-label small text-secondary fw-semibold">Select Year</label>
            <select name="year" class="form-select form-select-sm">
                <?php 
                for($y=2026; $y<=2030; $y++) {
                    $sel = ($filters['year'] == $y) ? 'selected' : '';
                    echo "<option value=\"$y\" $sel>$y</option>";
                }
                ?>
            </select>
          </div>

          <div class="col-md-2 col-sm-6 mt-4">
            <button type="submit" class="btn btn-primary btn-sm w-100 py-2 fw-semibold">
              <i class="bi bi-funnel-fill me-1"></i> Apply Filter
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- Stat Overview Card -->
    <?php $totalLeads = $stats['total'] ?? 0; ?>
    <div class="row mb-4">
      <div class="col-md-4 col-sm-6">
        <div class="stat-widget d-flex align-items-center justify-content-between">
          <div>
            <div class="stat-label">Total Leads Uploaded</div>
            <div class="stat-value mt-1"><?= number_format($totalLeads) ?></div>
          </div>
          <div class="stat-icon">
            <i class="bi bi-cloud-arrow-up-fill"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Breakdowns Grid -->
    <div class="row g-4">
      <!-- Time Breakdown (If present) -->
      <?php if (!empty($stats['time_breakdown'])): ?>
      <div class="col-12">
        <div class="data-card">
          <div class="custom-card-header">
            <h6><i class="bi bi-clock-history text-primary"></i> Daily / Periodical Breakdown</h6>
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1 rounded-pill fw-semibold">
              <?= count($stats['time_breakdown']) ?> Days/Records
            </span>
          </div>
          <div class="table-responsive">
            <table class="table table-custom align-middle">
              <thead>
                <tr>
                  <th>Date / Label</th>
                  <th>Leads Uploaded</th>
                  <th style="width: 40%;">Percentage Contribution</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($stats['time_breakdown'] as $row): 
                  $pct = $totalLeads > 0 ? round(($row['total'] / $totalLeads) * 100, 1) : 0;
                ?>
                <tr>
                  <td class="fw-semibold text-dark"><?= htmlspecialchars($row['date_label']) ?></td>
                  <td class="fw-bold text-primary"><?= number_format($row['total']) ?></td>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div class="progress progress-sm flex-grow-1">
                        <div class="progress-bar progress-bar-custom" role="progressbar" style="width: <?= $pct ?>%"></div>
                      </div>
                      <span class="small fw-semibold text-muted" style="min-width: 45px; text-align: right;"><?= $pct ?>%</span>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Project Category Breakdown -->
      <div class="col-lg-6 col-md-12">
        <div class="data-card">
          <div class="custom-card-header">
            <h6><i class="bi bi-folder-fill text-warning"></i> Project Category Breakdown</h6>
          </div>
          <div class="table-responsive">
            <table class="table table-custom align-middle">
              <thead>
                <tr>
                  <th>Category</th>
                  <th>Leads Uploaded</th>
                  <th style="width: 35%;">Contribution</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($stats['by_category'])): ?>
                  <?php foreach ($stats['by_category'] as $row): 
                    $pct = $totalLeads > 0 ? round(($row['total'] / $totalLeads) * 100, 1) : 0;
                  ?>
                  <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($row['name'] ?? 'Uncategorized') ?></td>
                    <td class="fw-bold text-dark"><?= number_format($row['total']) ?></td>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <div class="progress progress-sm flex-grow-1">
                          <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $pct ?>%"></div>
                        </div>
                        <span class="small text-muted" style="min-width: 45px; text-align: right;"><?= $pct ?>%</span>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr><td colspan="3" class="text-center text-muted py-4">No data available</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Project Breakdown -->
      <div class="col-lg-6 col-md-12">
        <div class="data-card">
          <div class="custom-card-header">
            <h6><i class="bi bi-building text-info"></i> Project Breakdown</h6>
          </div>
          <div class="table-responsive">
            <table class="table table-custom align-middle">
              <thead>
                <tr>
                  <th>Project</th>
                  <th>Leads Uploaded</th>
                  <th style="width: 35%;">Contribution</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($stats['by_project'])): ?>
                  <?php foreach ($stats['by_project'] as $row): 
                    $pct = $totalLeads > 0 ? round(($row['total'] / $totalLeads) * 100, 1) : 0;
                  ?>
                  <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($row['name'] ?? 'Unassigned Project') ?></td>
                    <td class="fw-bold text-dark"><?= number_format($row['total']) ?></td>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <div class="progress progress-sm flex-grow-1">
                          <div class="progress-bar bg-info" role="progressbar" style="width: <?= $pct ?>%"></div>
                        </div>
                        <span class="small text-muted" style="min-width: 45px; text-align: right;"><?= $pct ?>%</span>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr><td colspan="3" class="text-center text-muted py-4">No data available</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Platform Breakdown -->
      <div class="col-lg-6 col-md-12">
        <div class="data-card">
          <div class="custom-card-header">
            <h6><i class="bi bi-globe text-success"></i> Platform Breakdown</h6>
          </div>
          <div class="table-responsive">
            <table class="table table-custom align-middle">
              <thead>
                <tr>
                  <th>Platform</th>
                  <th>Leads Uploaded</th>
                  <th style="width: 35%;">Contribution</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($stats['by_platform'])): ?>
                  <?php foreach ($stats['by_platform'] as $row): 
                    $pct = $totalLeads > 0 ? round(($row['total'] / $totalLeads) * 100, 1) : 0;
                  ?>
                  <tr>
                    <td class="fw-semibold">
                      <?php 
                        $pName = strtolower($row['name'] ?? '');
                        if(strpos($pName, 'fb') !== false || strpos($pName, 'facebook') !== false) echo '<i class="bi bi-facebook text-primary me-2"></i>';
                        elseif(strpos($pName, 'whatsapp') !== false) echo '<i class="bi bi-whatsapp text-success me-2"></i>';
                        elseif(strpos($pName, 'google') !== false) echo '<i class="bi bi-google text-danger me-2"></i>';
                        else echo '<i class="bi bi-share-fill text-secondary me-2"></i>';
                      ?>
                      <?= htmlspecialchars($row['name'] ?? 'Unknown Platform') ?>
                    </td>
                    <td class="fw-bold text-dark"><?= number_format($row['total']) ?></td>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <div class="progress progress-sm flex-grow-1">
                          <div class="progress-bar bg-success" role="progressbar" style="width: <?= $pct ?>%"></div>
                        </div>
                        <span class="small text-muted" style="min-width: 45px; text-align: right;"><?= $pct ?>%</span>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr><td colspan="3" class="text-center text-muted py-4">No data available</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Campaign Breakdown -->
      <div class="col-lg-6 col-md-12">
        <div class="data-card">
          <div class="custom-card-header">
            <h6><i class="bi bi-megaphone-fill text-danger"></i> Campaign Breakdown</h6>
          </div>
          <div class="table-responsive">
            <table class="table table-custom align-middle">
              <thead>
                <tr>
                  <th>Campaign Name</th>
                  <th>Leads Uploaded</th>
                  <th style="width: 35%;">Contribution</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($stats['by_campaign'])): ?>
                  <?php foreach ($stats['by_campaign'] as $row): 
                    $pct = $totalLeads > 0 ? round(($row['total'] / $totalLeads) * 100, 1) : 0;
                  ?>
                  <tr>
                    <td class="fw-semibold text-truncate" style="max-width: 220px;" title="<?= htmlspecialchars($row['name'] ?? '') ?>">
                      <?= htmlspecialchars($row['name'] ?? 'Unknown Campaign') ?>
                    </td>
                    <td class="fw-bold text-dark"><?= number_format($row['total']) ?></td>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <div class="progress progress-sm flex-grow-1">
                          <div class="progress-bar bg-danger" role="progressbar" style="width: <?= $pct ?>%"></div>
                        </div>
                        <span class="small text-muted" style="min-width: 45px; text-align: right;"><?= $pct ?>%</span>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr><td colspan="3" class="text-center text-muted py-4">No data available</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
function toggleDateInputs(val) {
    var dr = document.getElementById('dateRangeWrap');
    var cm = document.getElementById('customMonthWrap');
    var cy = document.getElementById('customYearWrap');
    if (dr) dr.style.display = (val === 'date_range') ? 'block' : 'none';
    if (cm) cm.style.display = (val === 'custom_month') ? 'block' : 'none';
    if (cy) cy.style.display = (val === 'custom_year') ? 'block' : 'none';
}
</script>
