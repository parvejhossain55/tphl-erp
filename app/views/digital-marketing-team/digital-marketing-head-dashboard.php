<style>
  :root {
    --brand: #1a2744;
    --brand-light: #2563eb;
    --surface: #f4f6fb;
    --card-radius: 14px;
  }

  /* ── reuse your existing wrappers ── */
  .card__wrapper {
    background: #fff;
    border-radius: var(--card-radius);
    border: 1px solid #e8ecf3;
    padding: 18px 20px;
    height: 100%;
  }

  .table__wrapper {
    border-radius: 0 0 var(--card-radius) var(--card-radius);
    overflow: hidden;
  }

  /* ── stat cards ── */
  .stat-card .label {
    font-size: .74rem;
    color: #78909c;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: 4px;
  }

  .stat-card .value {
    font-size: 1.7rem;
    font-weight: 700;
    color: #1a2744;
    line-height: 1.1;
  }

  .stat-card .change {
    font-size: .76rem;
    margin-top: 4px;
  }

  /* ── section title ── */
  .section-title {
    font-size: .82rem;
    font-weight: 700;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-bottom: 14px;
  }

  /* ── team cards ── */
  .team-card {
    background: #fff;
    border: 1.5px solid #e8ecf3;
    border-radius: var(--card-radius);
    padding: 16px;
    cursor: pointer;
    transition: border-color .18s, box-shadow .18s;
  }

  .team-card:hover {
    border-color: #2563eb;
    box-shadow: 0 2px 12px rgba(37, 99, 235, .08);
  }

  .team-card.active {
    border-color: #2563eb;
    background: #f0f5ff;
  }

  .team-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .8rem;
    font-weight: 700;
    margin-bottom: 10px;
  }

  .team-name {
    font-size: .85rem;
    font-weight: 700;
    color: #1a2744;
  }

  .team-lead {
    font-size: .74rem;
    color: #78909c;
    margin-bottom: 8px;
  }

  .team-spend {
    font-size: .76rem;
    color: #546e7a;
    margin-top: 5px;
  }

  /* ── progress bar ── */
  .prog-bg {
    height: 5px;
    background: #edf0f6;
    border-radius: 4px;
    overflow: hidden;
    margin-top: 4px;
  }

  .prog-fill {
    height: 100%;
    border-radius: 4px;
    transition: width .4s ease;
  }

  /* ── platform cards ── */
  .plat-card {
    background: #fff;
    border: 1px solid #e8ecf3;
    border-radius: var(--card-radius);
    padding: 16px;
  }

  .plat-icon {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .75rem;
    font-weight: 700;
  }

  .plat-budget {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1a2744;
    margin: 8px 0 2px;
  }

  .plat-meta {
    font-size: .74rem;
    color: #78909c;
  }

  /* ── summary strip ── */
  .sum-strip {
    background: #fff;
    border: 1px solid #e8ecf3;
    border-radius: var(--card-radius);
    padding: 14px 20px;
    display: flex;
    gap: 32px;
    flex-wrap: wrap;
    margin-bottom: 20px;
  }

  .sum-item .s-label {
    font-size: .72rem;
    color: #78909c;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
  }

  .sum-item .s-val {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1a2744;
  }

  /* ── tabs ── */
  .nav-pills .nav-link {
    font-size: .8rem;
    border-radius: 8px;
    padding: 6px 16px;
  }

  .nav-pills .nav-link.active {
    background: #1a2744;
    color: #fff;
  }

  /* ── add-row ── */
  .add-row select,
  .add-row input {
    font-size: .82rem;
    border-radius: 8px;
    border: 1px solid #dde3ef;
    padding: 7px 10px;
    color: #1a2744;
  }

  .add-row .btn-brand {
    background: #1a2744;
    color: #fff;
    border-radius: 8px;
    font-size: .82rem;
    font-weight: 600;
    padding: 7px 18px;
    border: none;
  }

  .add-row .btn-brand:hover {
    background: #2563eb;
  }

  /* ── table ── */
  .table thead th {
    background: #f7f9fc;
    font-size: .76rem;
    font-weight: 700;
    color: #78909c;
    text-transform: uppercase;
    letter-spacing: .04em;
    border-bottom: 1px solid #e8ecf3;
    padding: 10px 14px;
  }

  .table tbody td {
    font-size: .83rem;
    color: #1a2744;
    padding: 10px 14px;
    vertical-align: middle;
    border-bottom: 1px solid #f0f3f8;
  }

  .table tbody tr:hover td {
    background: #f7f9fc;
  }

  /* ── badges ── */
  .bg-info-soft {
    background: #e3f2fd;
  }

  .text-info-dark {
    color: #0d47a1;
  }

  .bg-warn-soft {
    background: #fff8e1;
  }

  .text-warn-dark {
    color: #f57f17;
  }

  .bg-success-soft {
    background: #e8f5e9;
  }

  .text-success-dark {
    color: #1b5e20;
  }

  .bg-danger-soft {
    background: #fce4ec;
  }

  .text-danger-dark {
    color: #880e4f;
  }

  .bg-purple-soft {
    background: #ede7f6;
  }

  .text-purple-dark {
    color: #4527a0;
  }

  /* ── chart legend ── */
  .chart-legend span {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: .76rem;
    color: #546e7a;
  }

  .leg-box {
    width: 10px;
    height: 10px;
    border-radius: 2px;
    display: inline-block;
  }
</style>


<div class="app__slide-wrapper">
  <div class="row mb-4">
    <?php
    $tb = $budgetSummary['total_budget'] ?? 0;
    $ts = $budgetSummary['total_spent'] ?? 0;
    $trem = $tb - $ts;
    $ap = $budgetSummary['active_platforms'] ?? 0;
    $spentPercentage = $tb > 0 ? round(($ts / $tb) * 100, 1) : 0;
    ?>
    <div class="col-xl-4 col-sm-6 col-6 mb-2">
      <div class="card__wrapper stat-card">
        <div class="label">Total Monthly Budget</div>
        <div class="value">৳<?= number_format($tb) ?></div>
        <div class="change text-success"><i class="bi bi-arrow-up-short"></i><?= $ap ?> Active Platforms</div>
      </div>
    </div>
    <div class="col-xl-4 col-sm-6 col-6 mb-2">
      <div class="card__wrapper stat-card">
        <div class="label">Total Spent</div>
        <div class="value">৳<?= number_format($ts) ?></div>
        <div class="change text-danger"><i class="bi bi-arrow-up-short"></i><?= $spentPercentage ?>% of budget</div>
      </div>
    </div>
    <div class="col-xl-4 col-sm-6 col-6 mb-2">
      <div class="card__wrapper stat-card">
        <div class="label">Remaining</div>
        <div class="value">৳<?= number_format($trem) ?></div>
        <div class="change text-muted"><i class="bi bi-clock"></i> </div>
      </div>
    </div>
  </div>



  <!-- ════ TABS ════ -->
  <ul class="nav nav-pills mb-3" id="budgetTabs">
    <li class="nav-item"><a class="nav-link active" href="#" onclick="showTab('platform',this);return false;">
        <i class="bi bi-pie-chart-fill me-1"></i>Platform Budget</a></li>
    <li class="nav-item ms-2"><a class="nav-link" href="#" onclick="showTab('add',this);return false;">
        <i class="bi bi-plus-circle-fill me-1"></i>Add Budget</a></li>
    <li class="nav-item ms-2"><a class="nav-link" href="#" onclick="showTab('log',this);return false;">
        <i class="bi bi-clock-history me-1"></i>Budget Log</a></li>

  </ul>

  <!-- ── TAB: Platform Budget ── -->
  <div id="tab-platform">
    <!-- summary strip -->
    <div class="sum-strip mb-4">
      <div class="sum-item">
        <div class="s-label">Platform Budget</div>
        <div class="s-val">৳<?= number_format($tb) ?></div>
      </div>
      <div class="sum-item">
        <div class="s-label">Spent</div>
        <div class="s-val" style="color:#e53e3e;">৳<?= number_format($ts) ?></div>
      </div>
      <div class="sum-item">
        <div class="s-label">Remaining</div>
        <div class="s-val" style="color:#16a34a;">৳<?= number_format($trem) ?></div>
      </div>
      <div class="sum-item">
        <div class="s-label">Active Platforms</div>
        <div class="s-val"><?= $ap ?></div>
      </div>
    </div>

    <!-- platform cards -->
    <div class="row g-3 mb-4">
      <?php foreach ($platformBudgets as $pb): ?>
        <?php
        $rem = $pb['budget'] - $pb['spent'];
        $percent = $pb['budget'] > 0 ? round(($pb['spent'] / $pb['budget']) * 100) : 0;
        $color = '#3b82f6';
        if ($percent > 85) $color = '#dc2626';
        elseif ($percent > 60) $color = '#f59e0b';
        ?>
        <div class="col-xxl-2 col-md-3 col-lg-3 col-sm-4 col-6">
          <div class="plat-card">
            <div class="d-flex align-items-center gap-2 mb-2">
              <span class="fw-semibold" style="font-size:.85rem;"><?= htmlspecialchars($pb['source_name'] ?? 'Unknown Platform') ?></span>
            </div>
            <div class="plat-budget">৳<?= number_format($pb['budget']) ?></div>
            <div class="plat-meta">Spent: ৳<?= number_format($pb['spent']) ?> (<?= $percent ?>%)</div>
            <div class="prog-bg mt-2">
              <div class="prog-fill" style="width:<?= min(100, $percent) ?>%;background:<?= $color ?>;"></div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div><!-- /tab-platform -->

  <!-- ── TAB: Add Budget ── -->
  <div id="tab-add" style="display:none;">
    <div class="card__wrapper mb-4">
      <div class="section-title mb-3"><i class="bi bi-plus-circle-fill me-2 text-primary"></i>Add / Update Platform Budget</div>
      <form action="<?= $GLOBALS['base_url'] ?>/store-platform-budget" method="POST">
        <div class="row g-2 add-row align-items-end">
          <div class="col-xl-3 col-md-3 col-6">
            <label class="form-label fw-semibold" style="font-size:.78rem;">Platform Source</label>
            <select class="form-select w-100" name="source_id" required>
              <option value="">Select Platform</option>
              <?php foreach ($sources as $source): ?>
                <option value="<?= $source['id'] ?>"><?= htmlspecialchars($source['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-xl-3 col-md-3 col-6">
            <label class="form-label fw-semibold" style="font-size:.78rem;">Select Month</label>
            <input type="month" name="month" class="form-control" value="<?= htmlspecialchars($budget_month) ?>" required />
          </div>
          <div class="col-xl-2 col-md-2 col-6">
            <label class="form-label fw-semibold" style="font-size:.78rem;">Budget Limit (৳)</label>
            <input type="number" name="budget" step="0.01" class="form-control" placeholder="e.g. 150000" required />
          </div>
          <div class="col-xl-2 col-md-2 col-6">
            <label class="form-label fw-semibold" style="font-size:.78rem;">Spent Amount (৳)</label>
            <input type="number" name="spent" step="0.01" class="form-control" placeholder="e.g. 80000" required />
          </div>
          <div class="col-xl-2 col-md-2 col-12">
            <button type="submit" class="btn-brand w-100" style="padding:8px 0;border-radius:8px;font-size:.82rem;font-weight:600;background:#1a2744;color:#fff;border:none;cursor:pointer;">
              <i class="bi bi-plus-lg me-1"></i>Add Budget
            </button>
          </div>
        </div>
      </form>
    </div>
  </div><!-- /tab-add -->

  <!-- ── TAB: Budget Log ── -->
  <div id="tab-log" style="display:none;">
    <div class="card border-0 shadow-sm mb-4" style="border-radius:var(--card-radius);">
      <div class="card-header bg-white border-bottom fw-bold d-flex justify-content-between align-items-center py-3">
        <span style="font-size:.88rem;color:#1a2744;"><i class="bi bi-clock-history me-2 text-primary"></i>Monthly Budget Log</span>
        <span class="badge bg-primary-soft text-primary rounded-pill">8 Entries</span>
      </div>
      <div class="table__wrapper table-responsive">
        <table class="table mb-0">
          <thead>
            <tr>
              <th>Platform</th>
              <th>Month</th>
              <th class="text-end">Budget (৳)</th>
              <th class="text-end">Spent (৳)</th>
              <th class="text-end">Remaining</th>
              <th class="text-end pe-4">Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($platformBudgets)): ?>
              <tr>
                <td colspan="6" class="text-center py-4">No budget records found for this month.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($platformBudgets as $pb): ?>
                <?php
                $rem = $pb['budget'] - $pb['spent'];
                $percent = $pb['budget'] > 0 ? round(($pb['spent'] / $pb['budget']) * 100) : 0;
                $badgeClass = 'bg-success-soft text-success-dark';
                if ($percent > 85) $badgeClass = 'bg-danger-soft text-danger-dark';
                elseif ($percent > 60) $badgeClass = 'bg-warn-soft text-warn-dark';
                ?>
                <tr>
                  <td><?= htmlspecialchars($pb['source_name'] ?? 'Unknown Platform') ?></td>
                  <td><?= date('F Y', strtotime($pb['month'])) ?></td>
                  <td class="text-end fw-semibold"><?= number_format($pb['budget']) ?></td>
                  <td class="text-end"><?= number_format($pb['spent']) ?></td>
                  <td class="text-end text-success fw-semibold"><?= number_format($rem) ?></td>
                  <td class="text-end pe-4"><span class="badge <?= $badgeClass ?> px-2 py-1 rounded-pill" style="font-size:.72rem;"><?= $percent ?>% used</span></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div><!-- /tab-log -->


  <div class="row mt-4">
    <div class="col-12 mb-3">
      <form action="" method="GET" class="card__wrapper p-3 shadow-sm" style="border-radius:12px;">

        <div class="row align-items-end g-2">

          <!-- Team Filter -->
          <div class="col-md-3 col-6">
            <label class="form-label small text-muted fw-semibold">Team</label>
            <select name="team_id" class="form-select form-select-sm select2">
              <option value="">All Teams</option>
              <?php foreach ($teams as $team): ?>
                <option value="<?= $team['id'] ?>" <?= ($selected_team_id ?? '') == $team['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($team['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Lead Age -->
          <div class="col-md-2 col-6">
            <label class="form-label small text-muted fw-semibold">Lead Age</label>
            <select name="date_age" class="form-select form-select-sm">
              <option value="all" <?= ($date_age ?? '') == 'all' ? 'selected' : '' ?>>All</option>
              <option value="today" <?= ($date_age ?? '') == 'today' ? 'selected' : '' ?>>Today</option>
              <option value="7days" <?= ($date_age ?? '') == '7days' ? 'selected' : '' ?>>7 Days</option>
              <option value="old" <?= ($date_age ?? '') == 'old' ? 'selected' : '' ?>>Old</option>
            </select>
          </div>

          <!-- Sort -->
          <div class="col-md-2 col-6">
            <label class="form-label small text-muted fw-semibold">Sort</label>
            <select name="sort" class="form-select form-select-sm">
              <option value="newest" <?= ($sort_order ?? '') == 'newest' ? 'selected' : '' ?>>Newest</option>
              <option value="oldest" <?= ($sort_order ?? '') == 'oldest' ? 'selected' : '' ?>>Oldest</option>
            </select>
          </div>

          <!-- Duplicate Toggle -->
          <div class="col-md-2 col-6">
            <label class="form-label small text-muted fw-semibold d-block">Duplicates</label>
            <div class="form-check form-switch">
               <input class="form-check-input" type="checkbox" name="duplicates_only" id="duplicates_only" value="1" <?= ($duplicates_only ?? false) ? 'checked' : '' ?>>
              <label class="form-check-label fw-bold small text-muted" for="duplicates_only">Show Duplicates Only</label>
            </div>
          </div>

          <!-- 🔥 Small Duplicate Count UI -->
          <div class="col-md-2 col-6 d-flex align-items-end">
            <a href="<?= $GLOBALS['base_url'] ?>/digital-marketing-dashboard?duplicates_only=1&date_age=all"
              class="text-decoration-none w-100">

              <div class="d-flex align-items-center justify-content-between px-2 py-2 rounded"
                style="background:#fff5f5; border:1px solid #f5c2c7;">

                <div class="small text-danger fw-semibold">
                  <i class="bi bi-exclamation-circle me-1"></i>Total Duplicates Lead
                </div>

                <span class="badge bg-danger">
                  <?= number_format($duplicateLeadsCount ?? 0) ?>
                </span>
              </div>

            </a>
          </div>

          <!-- Buttons -->
          <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-sm w-100" style="background:#1a2744; color:#fff;">
              <i class="bi bi-filter me-1"></i> Apply
            </button>

            <a href="<?= $GLOBALS['base_url'] ?>/digital-marketing-dashboard"
              class="btn btn-outline-secondary btn-sm">
              <i class="bi bi-arrow-clockwise"></i>
            </a>
          </div>

        </div>

      </form>
    </div>
  </div>

  <!-- Recent Leads Table -->
  <?php if (isset($_SESSION['user']['role_id']) && $_SESSION['user']['role_id'] == 10): ?>
    <div class="row mt-4">
      <div class="col-12">
        <div class="history-card shadow-sm border-0">
          <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <h6 class="mb-0 fw-bold text-dark">
              <i class="bi bi-clock-history me-2 text-primary"></i>
              <?php
              $headerText = "Uploaded Leads";
              if ($date_age == 'today') $headerText = "Today's Uploaded Leads";
              elseif ($date_age == 'old') $headerText = "Old Uploaded Leads";
              elseif ($date_age == '7days') $headerText = "Recent Uploaded Leads (Last 7 Days)";

              if ($duplicates_only) $headerText .= " (Duplicates Only)";
              echo $headerText;
              ?>
            </h6>
            <span class="badge bg-primary-soft text-primary rounded-pill"><?= count($leads) ?> Leads Shown</span>
          </div>
          <div class="table__wrapper table-responsive my-3">
            <table class="table no-border-table mt-20 multiple_tables">
              <thead class="">
                <tr>
                  <th class="ps-4">Lead Name</th>
                  <th>Contact Info</th>
                  <th>Platform & Campaign</th>
                  <th>Team Name</th>
                  <th>Sales Team Lead</th>
                  <th class="pe-4 text-end">Upload Date</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($leads)): ?>
                  <tr>
                    <td colspan="5" class="text-center py-5">
                      <div class="text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        No leads uploaded in the last 7 days.
                      </div>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($leads as $lead): ?>
                    <tr>
                      <td class="ps-4">
                        <div class="fw-bold text-dark"><?= htmlspecialchars($lead['full_name'] ?? 'N/A') ?></div>
                        <small class="text-muted"><?= htmlspecialchars($lead['job_title'] ?? 'N/A') ?></small>
                      </td>
                      <td>
                        <div class="d-flex flex-column">
                          <?php
                          $p = $lead['phone_number'] ?? '';
                          $isDuplicate = !empty($p) && isset($phoneFreq[$p]) && $phoneFreq[$p] > 1;
                          $textClass = $isDuplicate ? 'text-danger fw-bold' : '';
                          ?>
                          <span class="<?= $textClass ?>"><i class="bi bi-telephone text-muted me-2"></i><?= htmlspecialchars($p ?: 'N/A') ?></span>
                          <small class="text-muted"><i class="bi bi-envelope me-2"></i><?= htmlspecialchars($lead['email'] ?? 'N/A') ?></small>
                        </div>
                      </td>
                      <td>
                        <span class="badge bg-info-soft text-info mb-1 d-inline-block"><?= htmlspecialchars($lead['platform'] ?? 'N/A') ?></span>
                        <div class="small text-muted"><?= htmlspecialchars($lead['campaign_name'] ?? 'N/A') ?></div>
                      </td>
                      <td>
                        <span class="text-dark"><?= htmlspecialchars($lead['team_name'] ?? 'N/A') ?></span>
                      </td>
                      <td>
                        <div class="fw-bold text-primary">
                          <?= htmlspecialchars($lead['sales_team_lead_name'] ?? 'Not Assigned') ?>
                        </div>
                      </td>
                      <td class="pe-4 text-end">
                        <div class="fw-semibold"><?= date('M d, Y', strtotime($lead['created_at'])) ?></div>
                        <small class="text-muted"><?= date('h:i A', strtotime($lead['created_at'])) ?></small>
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
  <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
  /* ── tab switching ── */
  function showTab(name, el) {
    ['platform', 'add', 'log', 'chart'].forEach(t => {
      document.getElementById('tab-' + t).style.display = t === name ? '' : 'none';
    });
    document.querySelectorAll('#budgetTabs .nav-link').forEach(a => a.classList.remove('active'));
    el.classList.add('active');
    if (name === 'chart') buildCharts();
  }

  /* ── team card select ── */
  function selectTeam(el) {
    document.querySelectorAll('.team-card').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
  }

  /* ── charts ── */
  let chartsBuilt = false;

  function buildCharts() {
    if (chartsBuilt) return;
    chartsBuilt = true;

    /* bar — budget vs spent per platform */
    new Chart(document.getElementById('mainChart'), {
      type: 'bar',
      data: {
        labels: ['Facebook', 'YouTube', 'Instagram', 'Google Ads', 'TikTok', 'LinkedIn'],
        datasets: [{
            label: 'Budget',
            data: [450000, 280000, 350000, 200000, 110000, 80000],
            backgroundColor: '#3b82f6',
            borderRadius: 5
          },
          {
            label: 'Spent',
            data: [345000, 200000, 290500, 140000, 62500, 40000],
            backgroundColor: '#f472b6',
            borderRadius: 5
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            ticks: {
              callback: v => '৳' + (v / 1000) + 'k'
            },
            grid: {
              color: '#f0f3f8'
            }
          },
          x: {
            grid: {
              display: false
            }
          }
        }
      }
    });

    /* pie — spend share */
    new Chart(document.getElementById('pieChart'), {
      type: 'doughnut',
      data: {
        labels: ['Facebook', 'YouTube', 'Instagram', 'Google Ads', 'TikTok', 'LinkedIn'],
        datasets: [{
          data: [345000, 200000, 290500, 140000, 62500, 40000],
          backgroundColor: ['#3b82f6', '#dc2626', '#be185d', '#16a34a', '#374151', '#0d47a1'],
          borderWidth: 2,
          borderColor: '#fff'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'right',
            labels: {
              font: {
                size: 11
              },
              boxWidth: 10
            }
          }
        }
      }
    });

    /* horizontal bar — team budget */
    new Chart(document.getElementById('teamChart'), {
      type: 'bar',
      data: {
        labels: ['Team Gulshan', 'Team Gulshan - 1', 'Team Gulsahn-2', 'Team Delta', 'Team Omega'],
        datasets: [{
            label: 'Budget',
            data: [400000, 350000, 300000, 400000, 200000],
            backgroundColor: '#c7d2fe',
            borderRadius: 4
          },
          {
            label: 'Spent',
            data: [230000, 280000, 195000, 200000, 132500],
            backgroundColor: '#6366f1',
            borderRadius: 4
          }
        ]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          x: {
            ticks: {
              callback: v => '৳' + (v / 1000) + 'k'
            },
            grid: {
              color: '#f0f3f8'
            }
          },
          y: {
            grid: {
              display: false
            }
          }
        }
      }
    });
  }
</script>
</body>

</html>