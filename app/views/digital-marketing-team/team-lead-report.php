<?php
/**
 * Lead Upload Reporting Dashboard (Team Lead Report)
 * ERP Project Standard Layout & Aesthetics
 */
?>
<style>
  :root {
    --brand-primary: #1e3a8a;
    --brand-dark: #0f172a;
    --brand-accent: #3b82f6;
    --surface-bg: #f8fafc;
    --card-radius: 12px;
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
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    padding: 1.25rem 1.5rem;
    margin-bottom: 1.25rem;
  }

  .kpi-stat-card {
    background: #ffffff;
    border-radius: var(--card-radius);
    border: 1px solid var(--border-color);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);
    padding: 1.25rem 1.4rem;
    position: relative;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    height: 100%;
  }
  .kpi-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
  }
  .kpi-label {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    font-weight: 700;
    margin-bottom: 0.4rem;
  }
  .kpi-value {
    font-size: 2.1rem;
    font-weight: 800;
    color: #1e293b;
    line-height: 1.1;
  }
  .kpi-icon-wrap {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
  }

  .filter-panel {
    background: #ffffff;
    border-radius: var(--card-radius);
    border: 1px solid var(--border-color);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    padding: 1.25rem 1.5rem;
    margin-bottom: 1.5rem;
  }

  .data-table-card {
    background: #ffffff;
    border-radius: var(--card-radius);
    border: 1px solid var(--border-color);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    overflow: hidden;
  }

  .data-table-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.1rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
    background: #ffffff;
  }
  .data-table-header h5 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
    color: #1e293b;
  }

  .table-custom thead th {
    background: #f8fafc;
    color: #475569;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 0.85rem 1.1rem;
    border-bottom: 1px solid var(--border-color);
    white-space: nowrap;
  }
  .table-custom tbody td {
    padding: 0.85rem 1.1rem;
    vertical-align: middle;
    color: #334155;
    font-size: 0.875rem;
    border-bottom: 1px solid #f1f5f9;
  }
  .table-custom tbody tr:hover {
    background-color: #f8fafc;
  }

  .member-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
    color: #fff;
    font-weight: 700;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  /* Print specific styles */
  .print-only-header {
    display: none;
  }

  @media print {
    body {
      background: #fff !important;
      font-size: 12px !important;
    }
    .sidebar, .navbar, .header, .main-header, .filter-panel, .btn, .breadcrumb, footer, .modal, .no-print {
      display: none !important;
    }
    .report-page-wrapper {
      padding: 0 !important;
      background: #fff !important;
    }
    .report-header-card {
      display: none !important;
    }
    .print-only-header {
      display: block !important;
      margin-bottom: 20px;
      text-align: center;
    }
    .data-table-card, .kpi-stat-card {
      box-shadow: none !important;
      border: 1px solid #ccc !important;
    }
    .table-custom tbody td, .table-custom thead th {
      padding: 6px 8px !important;
    }
  }
</style>

<div class="report-page-wrapper">
  <div class="container-fluid">

    <!-- Top Action Bar & Breadcrumbs -->
    <div class="report-header-card d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3">
      <div>
        <h4 class="mb-1 fw-bold text-dark">Lead Upload Reporting Dashboard</h4>
        <p class="text-muted mb-0 fs-13">Monitor lead uploads by team, member, date and upload session.</p>
        <nav aria-label="breadcrumb" class="mt-2">
          <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/digital-marketing-dashboard" class="text-decoration-none text-muted">Digital Marketing</a></li>
            <li class="breadcrumb-item active text-primary fw-semibold" aria-current="page">Team Lead Report</li>
          </ol>
        </nav>
      </div>
      <div class="d-flex align-items-center gap-2 flex-wrap no-print">
        <?php
          $exportQuery = http_build_query($filters);
        ?>
        <a href="<?= $GLOBALS['base_url'] ?>/team-lead-report/export-excel?<?= $exportQuery ?>" class="btn btn-outline-success px-3 shadow-sm rounded-3">
          <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
        </a>
        <button type="button" onclick="window.print()" class="btn btn-outline-primary px-3 shadow-sm rounded-3">
          <i class="bi bi-printer me-1"></i> Print / PDF
        </button>
      </div>
    </div>

    <!-- Print Only Header -->
    <div class="print-only-header">
      <h3 style="font-weight: 800; margin-bottom: 2px;">TPHL - Lead Upload Reporting Dashboard</h3>
      <p style="color: #666; margin-bottom: 10px; font-size: 13px;">Generated on: <?= date('d M Y, h:i A') ?></p>
      <p style="font-size: 12px; margin-bottom: 5px;">
        <strong>Filter Applied:</strong> <?= htmlspecialchars(ucwords(str_replace('_', ' ', $filters['date_preset']))) ?>
        <?php if (!empty($filters['from_date']) && !empty($filters['to_date'])): ?>
          (<?= htmlspecialchars($filters['from_date']) ?> to <?= htmlspecialchars($filters['to_date']) ?>)
        <?php endif; ?>
      </p>
    </div>

    <!-- Filter Panel -->
    <div class="filter-panel no-print">
      <form action="<?= $GLOBALS['base_url'] ?>/team-lead-report" method="GET" id="reportFilterForm">
        <div class="row  align-items-end">
          
          <!-- Date Filter Preset -->
          <div class="col-l col-md-6">
            <label class="form-label small fw-semibold text-secondary mb-1">Date Preset</label>
            <select name="date_preset" id="datePresetSelect" class="form-select form-select-sm" onchange="handlePresetChange(this.value)">
              <option value="today" <?= $filters['date_preset'] === 'today' ? 'selected' : '' ?>>Today (<?= date('d M') ?>)</option>
              <option value="yesterday" <?= $filters['date_preset'] === 'yesterday' ? 'selected' : '' ?>>Yesterday</option>
              <option value="last_7_days" <?= $filters['date_preset'] === 'last_7_days' ? 'selected' : '' ?>>Last 7 Days</option>
              <option value="this_month" <?= $filters['date_preset'] === 'this_month' ? 'selected' : '' ?>>This Month (<?= date('M Y') ?>)</option>
              <option value="last_month" <?= $filters['date_preset'] === 'last_month' ? 'selected' : '' ?>>Last Month</option>
              <option value="custom" <?= in_array($filters['date_preset'], ['custom', 'date_range']) ? 'selected' : '' ?>>Custom Date Range</option>
              <option value="all" <?= $filters['date_preset'] === 'all' ? 'selected' : '' ?>>All Time</option>
            </select>
          </div>

          <!-- Custom From-To Dates -->
          <div class="col-l col-md-6" id="customDateRangeInputs" style="display: <?= in_array($filters['date_preset'], ['custom', 'date_range']) ? 'block' : 'none' ?>;">
            <label class="form-label small fw-semibold text-secondary mb-1">From & To Date</label>
            <div class="d-flex gap-2">
              <input type="date" name="from_date" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['from_date']) ?>">
              <input type="date" name="to_date" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['to_date']) ?>">
            </div>
          </div>

          <!-- Team Dropdown -->
          <div class="col-lg-2 col-md-4">
            <label class="form-label small fw-semibold text-secondary mb-1">Team</label>
            <select name="team_id" class="form-select form-select-sm">
              <option value="">All Teams</option>
              <?php foreach ($teams as $t): ?>
                <option value="<?= $t['id'] ?>" <?= ($filters['team_id'] == $t['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($t['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Executive Member Dropdown -->
          <div class="col-lg-2 col-md-4">
            <label class="form-label small fw-semibold text-secondary mb-1">Executive (Member)</label>
            <select name="member_id" class="form-select form-select-sm">
              <option value="">All Executives (Role 11)</option>
              <?php foreach ($executives as $ex): ?>
                <option value="<?= $ex['id'] ?>" <?= ($filters['member_id'] == $ex['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($ex['full_name']) ?> <?= $ex['employee_id'] ? '(' . htmlspecialchars($ex['employee_id']) . ')' : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Search Keyword -->
          <div class="col-lg-2 col-md-4">
            <label class="form-label small fw-semibold text-secondary mb-1">Search</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Name or ID..." value="<?= htmlspecialchars($filters['search']) ?>">
          </div>

          <!-- Action Buttons -->
          <div class="col-lg-2 col-md-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
              <i class="bi bi-funnel-fill me-1"></i> Filter
            </button>
            <a href="<?= $GLOBALS['base_url'] ?>/team-lead-report" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
              <i class="bi bi-arrow-counterclockwise"></i>
            </a>
          </div>

        </div>
      </form>
    </div>

    <!-- 4 KPI Cards (Matching Image) -->
    <div class="row  mb-4">
      
      <!-- 1. Total Leads -->
      <div class="col-xl-3 col-sm-6">
        <div class="kpi-stat-card d-flex align-items-center justify-content-between">
          <div>
            <div class="kpi-label">
              Total Leads <?= ($filters['date_preset'] === 'today') ? 'Today' : 'in Period' ?>
            </div>
            <div class="kpi-value text-primary"><?= number_format($kpi['total_leads']) ?></div>
            <span class="badge bg-primary-subtle text-primary mt-1 fs-11">
              <i class="bi bi-cloud-arrow-up me-1"></i>CSV & Campaign
            </span>
          </div>
          <div class="kpi-icon-wrap bg-primary bg-opacity-10 text-primary">
            <i class="bi bi-cloud-check-fill"></i>
          </div>
        </div>
      </div>

      <!-- 2. Upload Sessions -->
      <div class="col-xl-3 col-sm-6">
        <div class="kpi-stat-card d-flex align-items-center justify-content-between">
          <div>
            <div class="kpi-label">Upload Sessions</div>
            <div class="kpi-value text-info"><?= number_format($kpi['total_sessions']) ?></div>
            <span class="badge bg-info-subtle text-info mt-1 fs-11">
              <i class="bi bi-layers me-1"></i>Batch Uploads
            </span>
          </div>
          <div class="kpi-icon-wrap bg-info bg-opacity-10 text-info">
            <i class="bi bi-stack"></i>
          </div>
        </div>
      </div>

      <!-- 3. Active Uploaders -->
      <div class="col-xl-3 col-sm-6">
        <div class="kpi-stat-card d-flex align-items-center justify-content-between">
          <div>
            <div class="kpi-label">Active Uploaders</div>
            <div class="kpi-value text-success"><?= number_format($kpi['active_uploaders']) ?></div>
            <span class="badge bg-success-subtle text-success mt-1 fs-11">
              <i class="bi bi-people me-1"></i>Role 11 Executives
            </span>
          </div>
          <div class="kpi-icon-wrap bg-success bg-opacity-10 text-success">
            <i class="bi bi-person-check-fill"></i>
          </div>
        </div>
      </div>

      <!-- 4. Last Upload -->
      <div class="col-xl-3 col-sm-6">
        <div class="kpi-stat-card d-flex align-items-center justify-content-between">
          <div>
            <div class="kpi-label">Last Upload</div>
            <div class="kpi-value text-dark" style="font-size: 1.8rem;"><?= htmlspecialchars($kpi['last_upload']) ?></div>
            <span class="badge bg-secondary-subtle text-secondary mt-1 fs-11">
              <i class="bi bi-clock-history me-1"></i>Most Recent
            </span>
          </div>
          <div class="kpi-icon-wrap bg-secondary bg-opacity-10 text-secondary">
            <i class="bi bi-stopwatch-fill"></i>
          </div>
        </div>
      </div>

    </div>

    <!-- Main Table: Team Member Summary -->
    <div class="data-table-card">
      <div class="data-table-header">
        <div>
          <h5><i class="bi bi-table me-2 text-primary"></i> Team Member Summary</h5>
          <span class="text-muted fs-12">Breakdown of leads uploaded by each digital marketing executive</span>
        </div>
        <span class="badge bg-primary px-3 py-2 rounded-pill fw-semibold fs-12">
          <?= count($rows) ?> Record<?= count($rows) !== 1 ? 's' : '' ?> Found
        </span>
      </div>

      <div class="table-responsive">
        <table class="table table-custom mb-0">
          <thead>
            <tr>
              <th style="width: 50px;">S/L</th>
              <th>Team</th>
              <th>Member</th>
              <th>Date</th>
              <th class="text-center">Total Leads</th>
              <th class="text-center">Sessions</th>
              <th>First Upload</th>
              <th>Last Upload</th>
              <th class="text-end no-print" style="width: 100px;">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($rows)): ?>
              <tr>
                <td colspan="9" class="text-center py-5 text-muted">
                  <i class="bi bi-inbox fs-1 d-block mb-2 text-muted"></i>
                  <span>No lead uploads found for the selected filter criteria.</span>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($rows as $index => $row): ?>
                <tr>
                  <!-- S/L -->
                  <td class="text-muted fw-semibold"><?= $index + 1 ?></td>

                  <!-- Team -->
                  <td>
                    <span class="badge bg-light text-dark border">
                      <i class="bi bi-people-fill me-1 text-primary"></i><?= htmlspecialchars($row['team_name'] ?: 'Lead Management') ?>
                    </span>
                  </td>

                  <!-- Member -->
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div class="member-avatar">
                        <?= strtoupper(substr($row['member_name'], 0, 1)) ?>
                      </div>
                      <div>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($row['member_name']) ?></div>
                        <div class="fs-11 text-muted">
                          <span class="badge bg-light text-secondary border px-1">ID: <?= htmlspecialchars($row['employee_id']) ?></span>
                          <?php if (!empty($row['designation'])): ?>
                            <span class="ms-1"><?= htmlspecialchars($row['designation']) ?></span>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  </td>

                  <!-- Date -->
                  <td class="fw-medium text-dark">
                    <?= date('d M, Y', strtotime($row['upload_date'])) ?>
                  </td>

                  <!-- Total Leads -->
                  <td class="text-center">
                    <span class="badge bg-primary-subtle text-primary border border-primary px-3 py-1 fs-12 fw-bold">
                      <?= number_format($row['total_leads']) ?>
                    </span>
                  </td>

                  <!-- Sessions -->
                  <td class="text-center">
                    <span class="badge bg-info-subtle text-info border border-info px-2 py-1 fs-12">
                      <i class="bi bi-stack me-1"></i><?= $row['sessions'] ?>
                    </span>
                  </td>

                  <!-- First Upload -->
                  <td>
                    <div class="text-muted fs-12">
                      <i class="bi bi-clock me-1 text-secondary"></i><?= htmlspecialchars($row['first_upload']) ?>
                    </div>
                  </td>

                  <!-- Last Upload -->
                  <td>
                    <div class="text-dark fw-medium fs-12">
                      <i class="bi bi-clock-history me-1 text-primary"></i><?= htmlspecialchars($row['last_upload']) ?>
                    </div>
                  </td>

                  <!-- Action -->
                  <td class="text-end no-print">
                    <button type="button" 
                            class="btn btn-sm btn-outline-primary px-2 py-1 view-lead-details-btn"
                            data-member-id="<?= $row['created_by'] ?>"
                            data-member-name="<?= htmlspecialchars($row['member_name'], ENT_QUOTES, 'UTF-8') ?>"
                            data-date="<?= $row['upload_date'] ?>"
                            data-total="<?= $row['total_leads'] ?>"
                            title="View Leads">
                      <i class="bi bi-eye me-1"></i> View
                    </button>
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

<!-- Modal: View Leads for Member on Date -->
<div class="modal fade" id="leadDetailsModal" tabindex="-1" aria-labelledby="leadDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light border-bottom">
        <div>
          <h5 class="modal-title fw-bold" id="leadDetailsModalLabel">
            <i class="bi bi-list-check me-2 text-primary"></i> Uploaded Leads
          </h5>
          <div class="text-muted fs-12 mt-1" id="modalSubTitle">Loading...</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <div id="modalLoadingSpinner" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="text-muted mt-2 fs-13">Fetching leads...</p>
        </div>
        <div class="table-responsive" id="modalTableWrap" style="display: none;">
          <table class="table table-hover align-middle mb-0" id="modalLeadsTable">
            <thead class="table-light">
              <tr>
                <th style="width: 40px;">#</th>
                <th>Lead Name</th>
                <th>Phone Number</th>
                <th>Campaign</th>
                <th>Platform</th>
                <th>Source</th>
                <th>Time</th>
              </tr>
            </thead>
            <tbody id="modalLeadsBody">
              <!-- Dynamically populated via AJAX -->
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer bg-light border-top">
        <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
function handlePresetChange(val) {
  var rangeDiv = document.getElementById('customDateRangeInputs');
  if (rangeDiv) {
    rangeDiv.style.display = (val === 'custom' || val === 'date_range') ? 'block' : 'none';
  }
}

document.addEventListener('DOMContentLoaded', function () {
  var modalEl = document.getElementById('leadDetailsModal');
  var modalInstance = new bootstrap.Modal(modalEl);
  var modalSubTitle = document.getElementById('modalSubTitle');
  var modalSpinner = document.getElementById('modalLoadingSpinner');
  var modalTableWrap = document.getElementById('modalTableWrap');
  var modalBody = document.getElementById('modalLeadsBody');

  document.querySelectorAll('.view-lead-details-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var memberId = this.getAttribute('data-member-id');
      var memberName = this.getAttribute('data-member-name');
      var date = this.getAttribute('data-date');
      var total = this.getAttribute('data-total');

      modalSubTitle.textContent = 'Member: ' + memberName + ' | Date: ' + date + ' (' + total + ' leads)';
      modalSpinner.style.display = 'block';
      modalTableWrap.style.display = 'none';
      modalBody.innerHTML = '';
      modalInstance.show();

      fetch('<?= $GLOBALS['base_url'] ?>/team-lead-report/lead-details-ajax?member_id=' + memberId + '&date=' + date)
        .then(function (response) { return response.json(); })
        .then(function (data) {
          modalSpinner.style.display = 'none';
          modalTableWrap.style.display = 'block';

          if (data.status === 'success' && data.leads && data.leads.length > 0) {
            var html = '';
            data.leads.forEach(function (lead, idx) {
              var timeStr = lead.created_at ? lead.created_at.substring(11, 19) : '—';
              html += '<tr>' +
                '<td class="text-muted">' + (idx + 1) + '</td>' +
                '<td class="fw-semibold">' + (lead.full_name || '—') + '</td>' +
                '<td>' + (lead.phone_number || '—') + '</td>' +
                '<td><span class="badge bg-light text-dark border">' + (lead.campaign_name || '—') + '</span></td>' +
                '<td><span class="badge bg-secondary-subtle text-secondary">' + (lead.platform || '—') + '</span></td>' +
                '<td><span class="badge bg-success-subtle text-success">' + (lead.uploaded_from || '—') + '</span></td>' +
                '<td class="text-muted small">' + timeStr + '</td>' +
              '</tr>';
            });
            modalBody.innerHTML = html;
          } else {
            modalBody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No individual leads found.</td></tr>';
          }
        })
        .catch(function (err) {
          modalSpinner.style.display = 'none';
          modalTableWrap.style.display = 'block';
          modalBody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-danger">Error loading leads.</td></tr>';
        });
    });
  });
});
</script>
