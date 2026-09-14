<?php
/**
 * All Platform Leads - Date-wise Summary Report View
 * Role 10 (Digital Marketing Head) & Role 11 (Digital Marketing Executive)
 */

$fromDate = $filters['from_date'] ?? date('Y-m-d');
$toDate   = $filters['to_date'] ?? date('Y-m-d');
$todayDate = date('Y-m-d');
$yesterdayDate = date('Y-m-d', strtotime('-1 day'));
$last7DaysDate = date('Y-m-d', strtotime('-6 days'));
$monthStartDate = date('Y-m-01');

// Determine active preset
$activePreset = '';
if ($fromDate === $todayDate && $toDate === $todayDate) {
    $activePreset = 'today';
} elseif ($fromDate === $yesterdayDate && $toDate === $yesterdayDate) {
    $activePreset = 'yesterday';
} elseif ($fromDate === $last7DaysDate && $toDate === $todayDate) {
    $activePreset = 'last_7_days';
} elseif ($fromDate === $monthStartDate && $toDate === $todayDate) {
    $activePreset = 'this_month';
} elseif ($fromDate === '2020-01-01') {
    $activePreset = 'all_time';
    
}

$exportQuery = http_build_query([
    'from_date'           => $fromDate,
    'to_date'             => $toDate,
    'project_id'          => $filters['project_id'] ?? '',
    'project_category_id' => $filters['project_category_id'] ?? '',
    'team_id'             => $filters['team_id'] ?? '',
]);
?>

<style>
  :root {
    --brand-primary: #0d47a1;
    --brand-dark: #0a2d6e;
    --brand-gradient: linear-gradient(135deg, #0d47a1 0%, #1e88e5 100%);
    --surface-bg: #f8fafc;
    --card-radius: 14px;
    --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    --border-color: #e2e8f0;
  }

  .report-page-wrapper {
    background-color: var(--surface-bg);
    min-height: 100vh;
    padding-bottom: 2.5rem;
  }

  .report-header-card {
    background: #ffffff;
    border-radius: var(--card-radius);
    border: 1px solid var(--border-color);
    box-shadow: var(--card-shadow);
    padding: 1.25rem 1.75rem;
    margin-bottom: 1.5rem;
  }

  .report-title {
    font-weight: 800;
    color: var(--brand-dark);
    letter-spacing: -0.02em;
    margin: 0;
  }

  /* Metric Card Styles */
  .platform-card {
    background: #ffffff;
    border-radius: var(--card-radius);
    border: 1px solid var(--border-color);
    box-shadow: var(--card-shadow);
    padding: 1.25rem;
    position: relative;
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    height: 100%;
  }

  .platform-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
  }

  .platform-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
  }

  .platform-card.card-total::before { background: #0d47a1; }
  .platform-card.card-fb::before { background: #1877F2; }
  .platform-card.card-wa::before { background: #25D366; }
  .platform-card.card-mi::before { background: #8B5CF6; }
  .platform-card.card-gf::before { background: #EA4335; }
  .platform-card.card-bl::before { background: #0D9488; }
  .platform-card.card-others::before { background: #64748B; }

  .platform-icon-wrap {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
  }

  .icon-total { background: #e3f2fd; color: #0d47a1; }
  .icon-fb { background: #e8f0fe; color: #1877F2; }
  .icon-wa { background: #e8f8f0; color: #25D366; }
  .icon-mi { background: #f3e8ff; color: #8B5CF6; }
  .icon-gf { background: #fee2e2; color: #EA4335; }
  .icon-bl { background: #ccfbf1; color: #0D9488; }
  .icon-others { background: #f1f5f9; color: #475569; }

  .metric-value {
    font-size: 1.65rem;
    font-weight: 800;
    line-height: 1.1;
    color: #1e293b;
  }

  .metric-label {
    font-size: 0.78rem;
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 0.04em;
    color: #64748b;
  }

  /* Filter Card */
  .filter-card {
    background: #ffffff;
    border-radius: var(--card-radius);
    border: 1px solid var(--border-color);
    box-shadow: var(--card-shadow);
    padding: 1.25rem 1.5rem;
    margin-bottom: 1.5rem;
  }

  .preset-btn {
    font-size: 0.8rem;
    font-weight: 600;
    padding: 0.35rem 0.85rem;
    border-radius: 20px;
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #475569;
    text-decoration: none;
    transition: all 0.15s ease;
    display: inline-block;
  }

  .preset-btn:hover {
    background: #f1f5f9;
    color: #0f172a;
  }

  .preset-btn.active {
    background: #0d47a1;
    border-color: #0d47a1;
    color: #ffffff !important;
    box-shadow: 0 2px 8px rgba(13, 71, 161, 0.25);
  }

  /* Data Table Card */
  .data-card {
    background: #ffffff;
    border-radius: var(--card-radius);
    border: 1px solid var(--border-color);
    box-shadow: var(--card-shadow);
    overflow: hidden;
  }

  .data-card-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #ffffff;
  }

  .table-report {
    margin-bottom: 0;
  }

  .table-report thead th {
    background: #f8fafc;
    color: #334155;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 0.9rem 1rem;
    border-bottom: 2px solid #e2e8f0;
    vertical-align: middle;
  }

  .table-report tbody td {
    padding: 0.85rem 1rem;
    vertical-align: middle;
    color: #1e293b;
    font-size: 0.875rem;
    border-bottom: 1px solid #f1f5f9;
  }

  .table-report tbody tr:hover {
    background-color: #f8fafc;
  }

  .table-report tfoot th {
    background: #f0fdf4;
    color: #166534;
    font-size: 0.875rem;
    font-weight: 800;
    padding: 1rem;
    border-top: 2px solid #86efac;
    vertical-align: middle;
  }

  .count-cell {
    font-weight: 600;
    font-variant-numeric: tabular-nums;
  }

  .count-zero {
    color: #94a3b8;
    font-weight: 400;
  }

  .badge-total-pill {
    background: #e0f2fe;
    color: #0369a1;
    font-weight: 700;
    padding: 0.35rem 0.65rem;
    border-radius: 6px;
    font-size: 0.85rem;
  }

  @media print {
    .sidebar, .navbar, .page-header, .page-header-breadcrumb, .filter-card, .btn, footer, .main-header {
      display: none !important;
    }
    .report-page-wrapper {
      background: #fff !important;
      padding: 0 !important;
    }
    .data-card, .platform-card {
      box-shadow: none !important;
      border: 1px solid #ddd !important;
    }
  }
</style>

<div class="report-page-wrapper">
  <div class="container-fluid">

    <!-- Header & Breadcrumb -->
    <div class="report-header-card d-flex flex-wrap align-items-center justify-content-between gap-3 page-header-breadcrumb mt-3">
      <div>
        <h4 class="report-title">
          <i class="fa-solid fa-layer-group text-primary me-2"></i> All Platform Leads Report
        </h4>
        <nav aria-label="breadcrumb" class="mt-1">
          <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/digital-marketing-dashboard" class="text-decoration-none text-muted">Digital Marketing</a></li>
            <li class="breadcrumb-item active text-primary fw-semibold" aria-current="page">All Platform Leads</li>
          </ol>
        </nav>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= $GLOBALS['base_url'] ?>/all-platform-leads-export?<?= $exportQuery ?>" class="btn btn-success px-3 shadow-sm rounded-3 fw-semibold">
          <i class="fa-solid fa-file-excel me-1"></i> Export Excel
        </a>
        <button onclick="window.print()" class="btn btn-outline-primary px-3 shadow-sm rounded-3">
          <i class="fa-solid fa-print me-1"></i> Print
        </button>
      </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row  mb-4">
      <!-- Total Leads -->
      <div class="col-6 col-md-3 col-xl">
        <div class="platform-card card-total">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="metric-label mb-1">Total Leads</div>
              <div class="metric-value"><?= number_format($totals['total_count']) ?></div>
            </div>
            <div class="platform-icon-wrap icon-total">
              <i class="fa-solid fa-users"></i>
            </div>
          </div>
          <div class="small text-muted mt-2">All platforms aggregated</div>
        </div>
      </div>

      <!-- Facebook Lead Form -->
      <div class="col-6 col-md-3 col-xl">
        <div class="platform-card card-fb">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="metric-label mb-1">Facebook Lead Form</div>
              <div class="metric-value"><?= number_format($totals['fb_count']) ?></div>
            </div>
            <div class="platform-icon-wrap icon-fb">
              <i class="fa-brands fa-facebook-f"></i>
            </div>
          </div>
          <div class="small text-muted mt-2">Forms & Lead Ads</div>
        </div>
      </div>

      <!-- Whatsapp -->
      <div class="col-6 col-md-3 col-xl">
        <div class="platform-card card-wa">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="metric-label mb-1">WhatsApp</div>
              <div class="metric-value"><?= number_format($totals['wa_count']) ?></div>
            </div>
            <div class="platform-icon-wrap icon-wa">
              <i class="fa-brands fa-whatsapp"></i>
            </div>
          </div>
          <div class="small text-muted mt-2">WA Chats & Leads</div>
        </div>
      </div>

      <!-- Meta Inbox -->
      <div class="col-6 col-md-3 col-xl">
        <div class="platform-card card-mi">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="metric-label mb-1">Meta Inbox</div>
              <div class="metric-value"><?= number_format($totals['mi_count']) ?></div>
            </div>
            <div class="platform-icon-wrap icon-mi">
              <i class="fa-solid fa-comments"></i>
            </div>
          </div>
          <div class="small text-muted mt-2">Messenger & Direct</div>
        </div>
      </div>

      <!-- Google Form -->
      <div class="col-6 col-md-3 col-xl">
        <div class="platform-card card-gf">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="metric-label mb-1">Google Form</div>
              <div class="metric-value"><?= number_format($totals['gf_count']) ?></div>
            </div>
            <div class="platform-icon-wrap icon-gf">
              <i class="fa-brands fa-google"></i>
            </div>
          </div>
          <div class="small text-muted mt-2">Search & Google Forms</div>
        </div>
      </div>

      <!-- Brochure Leads -->
      <div class="col-6 col-md-3 col-xl">
        <div class="platform-card card-bl">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="metric-label mb-1">Brochure Leads</div>
              <div class="metric-value"><?= number_format($totals['bl_count']) ?></div>
            </div>
            <div class="platform-icon-wrap icon-bl">
              <i class="fa-solid fa-book-open"></i>
            </div>
          </div>
          <div class="small text-muted mt-2">Brochure downloads</div>
        </div>
      </div>

      <!-- Others (Includes Instagram) -->
      <div class="col-6 col-md-3 col-xl">
        <div class="platform-card card-others">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="metric-label mb-1">Others</div>
              <div class="metric-value"><?= number_format($totals['others_count']) ?></div>
            </div>
            <div class="platform-icon-wrap icon-others">
              <i class="fa-brands fa-instagram"></i>
            </div>
          </div>
          <div class="small text-muted mt-2">Instagram & Misc</div>
        </div>
      </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="filter-card">
      <!-- Quick Date Presets -->
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-3 border-bottom">
        <div class="d-flex flex-wrap align-items-center gap-2">
          <span class="small text-muted fw-bold text-uppercase me-1"><i class="fa-regular fa-clock me-1"></i> Quick Presets:</span>
          <a href="<?= $GLOBALS['base_url'] ?>/all-platform-leads?from_date=<?= $todayDate ?>&to_date=<?= $todayDate ?>" 
             class="preset-btn <?= $activePreset === 'today' ? 'active' : '' ?>">Today</a>
          <a href="<?= $GLOBALS['base_url'] ?>/all-platform-leads?from_date=<?= $yesterdayDate ?>&to_date=<?= $yesterdayDate ?>" 
             class="preset-btn <?= $activePreset === 'yesterday' ? 'active' : '' ?>">Yesterday</a>
          <a href="<?= $GLOBALS['base_url'] ?>/all-platform-leads?from_date=<?= $last7DaysDate ?>&to_date=<?= $todayDate ?>" 
             class="preset-btn <?= $activePreset === 'last_7_days' ? 'active' : '' ?>">Last 7 Days</a>
          <a href="<?= $GLOBALS['base_url'] ?>/all-platform-leads?from_date=<?= $monthStartDate ?>&to_date=<?= $todayDate ?>" 
             class="preset-btn <?= $activePreset === 'this_month' ? 'active' : '' ?>">This Month</a>
          <a href="<?= $GLOBALS['base_url'] ?>/all-platform-leads?from_date=2020-01-01&to_date=<?= $todayDate ?>" 
             class="preset-btn <?= $activePreset === 'all_time' ? 'active' : '' ?>">All Time</a>
        </div>
        <div class="small text-muted">
          Showing: <span class="fw-bold text-dark"><?= date('d M Y', strtotime($fromDate)) ?></span> to <span class="fw-bold text-dark"><?= date('d M Y', strtotime($toDate)) ?></span>
        </div>
      </div>

      <!-- Custom Date & Filter Form -->
      <form method="GET" action="<?= $GLOBALS['base_url'] ?>/all-platform-leads" class="row  align-items-end">
        <div class="col-12 col-sm-6 col-md-3">
          <label class="form-label small fw-semibold text-muted mb-1">From Date</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light"><i class="fa-regular fa-calendar"></i></span>
            <input type="date" name="from_date" id="filter_from_date" class="form-control" value="<?= htmlspecialchars($fromDate) ?>" required>
          </div>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <label class="form-label small fw-semibold text-muted mb-1">To Date</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light"><i class="fa-regular fa-calendar"></i></span>
            <input type="date" name="to_date" id="filter_to_date" class="form-control" value="<?= htmlspecialchars($toDate) ?>" required>
          </div>
        </div>

        <?php if (!empty($projects)): ?>
        <div class="col-12 col-sm-6 col-md-3">
          <label class="form-label small fw-semibold text-muted mb-1">Project (Optional)</label>
          <select name="project_id" class="form-select form-select-sm">
            <option value="">-- All Projects --</option>
            <?php foreach ($projects as $proj): ?>
              <option value="<?= $proj['id'] ?>" <?= (!empty($filters['project_id']) && $filters['project_id'] == $proj['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($proj['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>

        <div class="col-12 col-sm-6 col-md-3 d-flex gap-2">
          <button type="submit" class="btn btn-primary btn-sm px-3 flex-fill fw-semibold">
            <i class="fa-solid fa-filter me-1"></i> Filter
          </button>
          <a href="<?= $GLOBALS['base_url'] ?>/all-platform-leads" class="btn btn-outline-secondary btn-sm px-3" title="Reset to Today">
            <i class="fa-solid fa-rotate-left"></i> Reset
          </a>
          <a href="<?= $GLOBALS['base_url'] ?>/all-platform-leads-export?<?= $exportQuery ?>" class="btn btn-outline-success btn-sm px-3" title="Export Excel">
            <i class="fa-solid fa-file-excel"></i>
          </a>
        </div>
      </form>
    </div>

    <!-- Date-Wise Breakdown Table Card -->
    <div class="data-card">
      <div class="data-card-header">
        <div>
          <h5 class="mb-0 fw-bold text-dark">
            <i class="fa-solid fa-table-list text-primary me-2"></i> Date-Wise Platform Breakdown
          </h5>
          <small class="text-muted">Aggregated counts for each platform per day</small>
        </div>
        <div>
          <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill fw-bold">
            <?= count($rows) ?> Day<?= count($rows) !== 1 ? 's' : '' ?> Found
          </span>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-report table-hover align-middle">
          <thead>
            <tr>
              <th style="min-width: 150px;"><i class="fa-regular fa-calendar-days me-1 text-primary"></i> Date</th>
              <th class="text-end" style="min-width: 170px;"><i class="fa-brands fa-facebook-f me-1 text-primary"></i> Facebook Lead Form</th>
              <th class="text-end" style="min-width: 130px;"><i class="fa-brands fa-whatsapp me-1 text-success"></i> Whatsapp</th>
              <th class="text-end" style="min-width: 130px;"><i class="fa-solid fa-comments me-1 text-info"></i> Meta Inbox</th>
              <th class="text-end" style="min-width: 130px;"><i class="fa-brands fa-google me-1 text-danger"></i> Google Form</th>
              <th class="text-end" style="min-width: 150px;"><i class="fa-solid fa-book-open me-1" style="color: #0D9488;"></i> Brochure Leads</th>
              <th class="text-end" style="min-width: 160px;"><i class="fa-brands fa-instagram me-1 text-secondary"></i> Others (IG & Misc)</th>
              <th class="text-end bg-light" style="min-width: 120px;"><i class="fa-solid fa-calculator me-1 text-dark"></i> Total</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($rows)): ?>
              <tr>
                <td colspan="8" class="text-center py-5">
                  <div class="py-4">
                    <div class="mb-3 text-muted" style="font-size: 3rem;">
                      <i class="fa-solid fa-folder-open"></i>
                    </div>
                    <h5 class="fw-bold text-dark">No Leads Found For This Range</h5>
                    <p class="text-muted small mb-3">There are no leads uploaded from campaigns within the selected dates.</p>
                    <div class="d-flex justify-content-center gap-2">
                      <a href="<?= $GLOBALS['base_url'] ?>/all-platform-leads?from_date=<?= $todayDate ?>&to_date=<?= $todayDate ?>" class="btn btn-sm btn-primary">
                        <i class="fa-solid fa-calendar-day me-1"></i> View Today
                      </a>
                      <a href="<?= $GLOBALS['base_url'] ?>/all-platform-leads?from_date=2020-01-01&to_date=<?= $todayDate ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-clock-rotate-left me-1"></i> View All Time
                      </a>
                    </div>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($rows as $r): ?>
                <?php
                  $timestamp = !empty($r['report_date']) ? strtotime($r['report_date']) : null;
                  $formattedDate = $timestamp ? date('d M Y', $timestamp) : '-';
                  $dayName       = $timestamp ? date('l', $timestamp) : '';
                  $isToday       = $r['report_date'] === $todayDate;
                ?>
                <tr>
                  <td>
                    <div class="d-flex align-items-center">
                      <?php if ($isToday): ?>
                        <span class="badge bg-success me-2" style="font-size: 0.65rem;">TODAY</span>
                      <?php endif; ?>
                      <div>
                        <span class="fw-bold text-dark"><?= $formattedDate ?></span>
                        <span class="text-muted d-block small" style="font-size: 0.75rem;"><?= $dayName ?></span>
                      </div>
                    </div>
                  </td>
                  <td class="text-end count-cell <?= (int)$r['fb_count'] === 0 ? 'count-zero' : 'text-primary' ?>">
                    <?= (int)$r['fb_count'] > 0 ? number_format($r['fb_count']) : '-' ?>
                  </td>
                  <td class="text-end count-cell <?= (int)$r['wa_count'] === 0 ? 'count-zero' : 'text-success' ?>">
                    <?= (int)$r['wa_count'] > 0 ? number_format($r['wa_count']) : '-' ?>
                  </td>
                  <td class="text-end count-cell <?= (int)$r['mi_count'] === 0 ? 'count-zero' : '' ?>" style="<?= (int)$r['mi_count'] > 0 ? 'color: #8B5CF6;' : '' ?>">
                    <?= (int)$r['mi_count'] > 0 ? number_format($r['mi_count']) : '-' ?>
                  </td>
                  <td class="text-end count-cell <?= (int)$r['gf_count'] === 0 ? 'count-zero' : 'text-danger' ?>">
                    <?= (int)$r['gf_count'] > 0 ? number_format($r['gf_count']) : '-' ?>
                  </td>
                  <td class="text-end count-cell <?= (int)$r['bl_count'] === 0 ? 'count-zero' : '' ?>" style="<?= (int)$r['bl_count'] > 0 ? 'color: #0D9488;' : '' ?>">
                    <?= (int)$r['bl_count'] > 0 ? number_format($r['bl_count']) : '-' ?>
                  </td>
                  <td class="text-end count-cell <?= (int)$r['others_count'] === 0 ? 'count-zero' : 'text-secondary' ?>">
                    <?= (int)$r['others_count'] > 0 ? number_format($r['others_count']) : '-' ?>
                  </td>
                  <td class="text-end count-cell bg-light">
                    <span class="badge-total-pill">
                      <?= number_format($r['total_count']) ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
          <?php if (!empty($rows)): ?>
            <tfoot>
              <tr>
                <th><i class="fa-solid fa-sigma me-1"></i> Total</th>
                <th class="text-end text-primary"><?= number_format($totals['fb_count']) ?></th>
                <th class="text-end text-success"><?= number_format($totals['wa_count']) ?></th>
                <th class="text-end" style="color: #8B5CF6;"><?= number_format($totals['mi_count']) ?></th>
                <th class="text-end text-danger"><?= number_format($totals['gf_count']) ?></th>
                <th class="text-end" style="color: #0D9488;"><?= number_format($totals['bl_count']) ?></th>
                <th class="text-end text-secondary"><?= number_format($totals['others_count']) ?></th>
                <th class="text-end">
                  <span class="badge bg-success text-white px-3 py-2 rounded-pill fs-6 shadow-sm">
                    <?= number_format($totals['total_count']) ?>
                  </span>
                </th>
              </tr>
            </tfoot>
          <?php endif; ?>
        </table>
      </div>
    </div>

  </div>
</div>
