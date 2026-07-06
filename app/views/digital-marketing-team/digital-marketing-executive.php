<style>
  :root {
    --brand: #0d47a1;
    --brand-light: #1565c0;
    --accent: #00bcd4;
    --sidebar-bg: #0a2d6e;
    --sidebar-w: 240px;
  }

  body {
    font-family: 'Segoe UI', sans-serif;
    background: #f0f4f8;
    min-height: 100vh;
  }

  /* ── Sidebar ── */
  .sidebar {
    width: var(--sidebar-w);
    min-height: 100vh;
    background: var(--sidebar-bg);
    position: fixed;
    top: 0;
    left: 0;
    display: flex;
    flex-direction: column;
  }

  .sidebar-brand {
    padding: 1.4rem 1.2rem;
    border-bottom: 1px solid rgba(255, 255, 255, .1);
  }

  .sidebar-brand span {
    font-size: .75rem;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: var(--accent);
    font-weight: 600;
  }

  .sidebar-brand h5 {
    color: #fff;
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
  }

  .sidebar-nav {
    padding: 1rem 0;
    flex: 1;
  }

  .sidebar-nav .nav-item {
    margin: 2px 0;
  }

  .sidebar-nav .nav-link {
    color: rgba(255, 255, 255, .65);
    padding: .6rem 1.4rem;
    border-radius: 0;
    font-size: .875rem;
    display: flex;
    align-items: center;
    gap: .65rem;
    transition: all .2s;
  }

  .sidebar-nav .nav-link:hover,
  .sidebar-nav .nav-link.active {
    color: #fff;
    background: rgba(255, 255, 255, .1);
    border-left: 3px solid var(--accent);
  }

  .sidebar-nav .nav-link i {
    font-size: 1rem;
  }

  .sidebar-footer {
    padding: 1rem 1.2rem;
    border-top: 1px solid rgba(255, 255, 255, .1);
  }

  .sidebar-footer .user-thumb {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--accent);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: .85rem;
    flex-shrink: 0;
  }

  .sidebar-footer .user-info small {
    font-size: .7rem;
    color: rgba(255, 255, 255, .5);
  }

  .sidebar-footer .user-info strong {
    font-size: .8rem;
    color: #fff;
    display: block;
    line-height: 1.2;
  }

  /* ── Main ── */
  .main-wrap {
    margin-left: var(--sidebar-w);
    display: flex;
    flex-direction: column;
    min-height: 100vh;
  }

  /* ── Topbar ── */
  .topbar {
    background: #fff;
    border-bottom: 1px solid #dde3ea;
    padding: .75rem 1.75rem;
    position: sticky;
    top: 0;
    z-index: 100;
  }

  .topbar .page-title {
    font-size: 1rem;
    font-weight: 700;
    color: #1a2744;
    margin: 0;
  }

  .topbar .breadcrumb {
    margin: 0;
    font-size: .75rem;
  }

  .badge-role {
    background: #e3f2fd;
    color: var(--brand);
    font-size: .7rem;
    font-weight: 600;
    padding: .25rem .65rem;
    border-radius: 20px;
    letter-spacing: .05em;
  }

  .bell-btn {
    position: relative;
    background: #f0f4f8;
    border: none;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #555;
    font-size: 1rem;
  }

  .bell-btn .dot {
    position: absolute;
    top: 6px;
    right: 7px;
    width: 8px;
    height: 8px;
    background: #f44336;
    border-radius: 50%;
    border: 1.5px solid #fff;
  }

  /* ── Stat Cards ── */
  .stat-card {
    border: none;
    border-radius: 12px;
    margin-bottom: 10px;
    padding: 1.25rem 1.4rem;
    box-shadow: 0 1px 6px rgba(0, 0, 0, .06);
    display: flex;
    align-items: center;
    gap: 1rem;
  }


  .stat-card .label {
    font-size: .75rem;
    color: #6c757d;
  }

  .stat-card .value {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1.1;
  }

  .stat-card .change {
    font-size: .72rem;
  }

  /* ── Upload Card ── */
  .upload-card {
    border: none;
    border-radius: 14px;
    background: #fff;
    box-shadow: 0 1px 6px rgba(0, 0, 0, .07);
  }

  .upload-card .card-header {
    background: #198754;
    border-radius: 14px 14px 0 0 !important;
    padding: 1rem 1.4rem;
    color: #fff;
    display: flex;
    align-items: center;
    gap: .6rem;
  }

  .upload-card .card-header h6 {
    margin: 0;
    font-weight: 600;
    font-size: .95rem;
  }

  .drop-zone {
    border: 2px dashed #b0bec5;
    border-radius: 12px;
    background: #f8fafc;
    text-align: center;
    padding: 2.8rem 1rem;
    transition: border-color .2s, background .2s;
    cursor: pointer;
  }

  .drop-zone:hover {
    border-color: var(--accent);
    background: #e0f7fa;
  }

  .drop-zone .drop-icon {
    font-size: 2.8rem;
    display: block;
    margin-bottom: .6rem;
  }

  .drop-zone p {
    margin: 0;
    font-size: .875rem;
    color: #546e7a;
  }

  .drop-zone small {
    color: #90a4ae;
    font-size: .75rem;
  }

  .file-preview {
    border: 1px solid #e0e7ef;
    border-radius: 10px;
    padding: .75rem 1rem;
    background: #f8fafc;
    display: flex;
    align-items: center;
    gap: .75rem;
  }

  .file-preview .file-icon {
    width: 40px;
    height: 40px;
    background: #e8f5e9;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #2e7d32;
    font-size: 1.2rem;
    flex-shrink: 0;
  }

  .file-preview .file-name {
    font-size: .85rem;
    font-weight: 600;
    color: #1a2744;
  }

  .file-preview .file-meta {
    font-size: .72rem;
    color: #78909c;
  }

  .upload-progress .progress {
    height: 8px;
    border-radius: 4px;
  }

  .upload-progress .progress-bar {
    background: linear-gradient(90deg, var(--brand), var(--accent));
    border-radius: 4px;
  }

  /* ── Template download ── */
  .template-strip {
    background: #e8f5e9;
    border: 1px solid #c8e6c9;
    border-radius: 10px;
    padding: .75rem 1rem;
    display: flex;
    align-items: center;
    gap: .75rem;
  }

  .template-strip i {
    color: #2e7d32;
    font-size: 1.1rem;
  }

  .template-strip p {
    margin: 0;
    font-size: .8rem;
    color: #1b5e20;
  }

  /* ── History Table ── */
  .history-card {
    border: none;
    border-radius: 14px;
    /* background: #fff; */
    box-shadow: 0 1px 6px rgba(0, 0, 0, .07);
    overflow: hidden;
  }

  .history-card .card-header {
    /* background: #fff; */
    border-bottom: 1px solid #e9ecef;
    padding: .9rem 1.4rem;
    font-weight: 600;
    font-size: .9rem;
    /* color: #1a2744; */
  }

  .history-card table thead th {
    font-size: .72rem;
    letter-spacing: .07em;
    text-transform: uppercase;
    /* color: #78909c; */
    font-weight: 600;
    border-bottom: 1px solid #e9ecef;
    padding: .65rem 1rem;
    /* background: #f8fafc; */
  }

  .history-card table tbody td {
    font-size: .82rem;
    color: #3d4f6e;
    padding: .7rem 1rem;
    vertical-align: middle;
    border-bottom: 1px solid #f0f4f8;
  }

  .status-badge {
    font-size: .68rem;
    font-weight: 600;
    padding: .25rem .7rem;
    border-radius: 20px;
  }

  .status-success {
    background: #e8f5e9;
    color: #2e7d32;
  }

  .status-processing {
    background: #fff8e1;
    color: #f57f17;
  }

  .status-failed {
    background: #ffebee;
    color: #c62828;
  }

  /* ── Contact Status Badges ── */
  .badge-contacted {
    background: linear-gradient(135deg, #d4edda, #b8dfc5);
    color: #155724;
    font-size: .68rem;
    font-weight: 700;
    padding: .28rem .75rem;
    border-radius: 20px;
    border: 1px solid #c3e6cb;
    display: inline-flex;
    align-items: center;
    gap: .3rem;
  }

  .badge-not-contacted {
    background: linear-gradient(135deg, #fdecea, #fbc7c0);
    color: #721c24;
    font-size: .68rem;
    font-weight: 700;
    padding: .28rem .75rem;
    border-radius: 20px;
    border: 1px solid #f5c6cb;
    display: inline-flex;
    align-items: center;
    gap: .3rem;
  }

  /* ── Lead Type Badges ── */
  .lead-type-badge {
    font-size: .65rem;
    font-weight: 700;
    padding: .22rem .65rem;
    border-radius: 20px;
    display: inline-block;
    letter-spacing: .02em;
  }
  .lt-hot { background:#fff3cd; color:#856404; border:1px solid #ffc107; }
  .lt-warm { background:#fff8e1; color:#f57f17; border:1px solid #ffca28; }
  .lt-cold { background:#e3f2fd; color:#0d47a1; border:1px solid #90caf9; }
  .lt-prospect { background:#e8f5e9; color:#1b5e20; border:1px solid #a5d6a7; }
  .lt-visited { background:#ede7f6; color:#4527a0; border:1px solid #ce93d8; }
  .lt-sold { background:#e0f2f1; color:#004d40; border:1px solid #80cbc4; }
  .lt-not-interested { background:#ffebee; color:#b71c1c; border:1px solid #ef9a9a; }
  .lt-pending { background:#e8eaf6; color:#283593; border:1px solid #9fa8da; }
  .lt-future { background:#f3e5f5; color:#6a1b9a; border:1px solid #ce93d8; }
  .lt-no-response { background:#f5f5f5; color:#424242; border:1px solid #bdbdbd; }
  .lt-default { background:#f0f0f0; color:#555; border:1px solid #ccc; }

  /* ── Buttons ── */
  .btn-brand {
    background: var(--brand);
    color: #fff;
    border: none;
    font-size: .85rem;
    font-weight: 600;
    border-radius: 8px;
    padding: .55rem 1.3rem;
    display: inline-flex;
    align-items: center;
    gap: .4rem;
  }

  .btn-brand:hover {
    background: var(--brand-light);
    color: #fff;
  }

  .btn-outline-brand {
    border: 1.5px solid var(--brand);
    color: var(--brand);
    background: transparent;
    font-size: .8rem;
    font-weight: 600;
    border-radius: 8px;
    padding: .45rem 1.1rem;
  }

  .btn-outline-brand:hover {
    background: var(--brand);
    color: #fff;
  }

  /* ── Field requirements ── */
  .req-list li {
    font-size: .78rem;
    color: #546e7a;
    padding: .15rem 0;
  }

  .req-list li i {
    color: var(--brand);
    font-size: .75rem;
    margin-right: .35rem;
  }
</style>
<?php  ?>
<div class="app__slide-wrapper">

  <!-- Stat row -->
  <div class="row mb-4">
    <div class="col-xl-3 col-sm-6 col-6 mb-2">
      <div class="card__wrapper stat-card">

        <div>
          <div class="label">Total Leads</div>
          <div class="value"><?= number_format($stats['total_leads'] ?? 0) ?></div>
          <div class="change text-success"><i class="bi bi-arrow-up-short"></i>Data sync ready</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6 col-6 mb-2">
      <div class="card__wrapper stat-card">

        <div>
          <div class="label">Uploaded This Week</div>
          <div class="value"><?= number_format($stats['leads_this_week'] ?? 0) ?></div>
          <div class="change text-success"><i class="bi bi-arrow-up-short"></i>Last 7 days</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6 col-6 mb-2">
      <div class="card__wrapper stat-card">

        <div>
          <div class="label">Today Upload Leads</div>
          <div class="value"><?= number_format($stats['today_uploads'] ?? 0) ?></div>
          <div class="change text-danger"><i class="bi bi-arrow-down-short"></i>Active</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6 col-6 mb-2">
      <div class="card__wrapper stat-card">

        <div>
          <div class="label">Total Uploads</div>
          <div class="value"><?= number_format($stats['total_uploads'] ?? 0) ?></div>
          <div class="change text-muted">Marketing leads</div>
        </div>
      </div>
    </div>

  </div>

  <!-- Contact Status Summary -->
  <div class="row mb-4">
    <div class="col-xl-3 col-sm-6 col-6 mb-2">
      <a href="<?= $GLOBALS['base_url'] ?>/digital-marketing-executive-dashboard?contact_status=contacted&date_age=<?= $date_age ?? 'all' ?>" class="text-decoration-none">
        <div class="card border-0 shadow-sm" style="border-radius:12px; border-left:4px solid #28a745 !important; border-left-width:4px;">
          <div class="card-body py-3 px-4 d-flex align-items-center gap-3">
            <div style="width:44px;height:44px;background:linear-gradient(135deg,#d4edda,#b8dfc5);border-radius:50%;display:flex;align-items:center;justify-content:center;">
              <i class="bi bi-telephone-check-fill text-success fs-5"></i>
            </div>
            <div>
              <div class="text-muted" style="font-size:.72rem;font-weight:600;letter-spacing:.05em;">CONTACTED</div>
              <div style="font-size:1.6rem;font-weight:800;color:#155724;line-height:1.1;"><?= number_format($totalContacted ?? 0) ?></div>
              <div style="font-size:.68rem;color:#28a745;">Touch leads</div>
            </div>
          </div>
        </div>
      </a>
    </div>
    <div class="col-xl-3 col-sm-6 col-6 mb-2">
      <a href="<?= $GLOBALS['base_url'] ?>/digital-marketing-executive-dashboard?contact_status=not_contacted&date_age=<?= $date_age ?? 'all' ?>" class="text-decoration-none">
        <div class="card border-0 shadow-sm" style="border-radius:12px; border-left:4px solid #dc3545 !important; border-left-width:4px;">
          <div class="card-body py-3 px-4 d-flex align-items-center gap-3">
            <div style="width:44px;height:44px;background:linear-gradient(135deg,#fdecea,#fbc7c0);border-radius:50%;display:flex;align-items:center;justify-content:center;">
              <i class="bi bi-telephone-x-fill text-danger fs-5"></i>
            </div>
            <div>
              <div class="text-muted" style="font-size:.72rem;font-weight:600;letter-spacing:.05em;">NOT CONTACTED</div>
              <div style="font-size:1.6rem;font-weight:800;color:#721c24;line-height:1.1;"><?= number_format($totalNotContacted ?? 0) ?></div>
              <div style="font-size:.68rem;color:#dc3545;">Touch hoini yet</div>
            </div>
          </div>
        </div>
      </a>
    </div>
    <?php
      $totalShown = ($totalContacted ?? 0) + ($totalNotContacted ?? 0);
      $contactedPct = $totalShown > 0 ? round(($totalContacted / $totalShown) * 100) : 0;
    ?>
    <div class="col-xl-6 col-sm-12 mb-2">
      <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
        <div class="card-body py-3 px-4">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span style="font-size:.78rem;font-weight:700;color:#1a2744;">Contact Progress</span>
            <span style="font-size:.78rem;color:#6c757d;"><?= $contactedPct ?>% Contacted</span>
          </div>
          <div class="progress" style="height:12px;border-radius:8px;background:#f0f4f8;">
            <div class="progress-bar bg-success" role="progressbar" style="width:<?= $contactedPct ?>%; border-radius:8px;" aria-valuenow="<?= $contactedPct ?>" aria-valuemin="0" aria-valuemax="100"></div>
          </div>
          <div class="d-flex justify-content-between mt-2">
            <small class="text-success fw-semibold"><i class="bi bi-circle-fill me-1" style="font-size:.5rem;"></i><?= number_format($totalContacted ?? 0) ?> Contacted</small>
            <small class="text-danger fw-semibold"><i class="bi bi-circle-fill me-1" style="font-size:.5rem;"></i><?= number_format($totalNotContacted ?? 0) ?> Pending</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">

    <!-- LEFT: Upload Section -->
    <div class="col-xl-7">
      <!-- Template Strip -->
      <div class="template-strip mb-3">
        <i class="bi bi-file-earmark-arrow-down-fill"></i>
        <p>Download the official CSV template before uploading to ensure correct column mapping.</p>
        <a href="#" class="btn btn-sm btn-outline-success ms-auto" style="white-space:nowrap;font-size:.75rem;font-weight:600;">
          <i class="bi bi-download me-1"></i> CSV Template
        </a>
      </div>

      <!-- Upload Card -->
      <div class="upload-card card">
        <div class="card-header">
          <i class="bi bi-cloud-upload-fill fs-5"></i>
          <h6 class="text-light">Bulk Lead Upload – CSV</h6>
        </div>
        <div class="card-body p-4">

          <!-- Drop Zone -->
          <div class="drop-zone mb-3">
            <i class="bi bi-file-earmark-spreadsheet drop-icon"></i>
            <p class="fw-semibold mb-1">Drag & drop your CSV file here</p>
            <p>or <span style="color:var(--brand);font-weight:600;cursor:pointer;">browse to upload</span></p>
            <small>Accepted format: <strong>.csv</strong> only &nbsp;|&nbsp; Max file size: <strong>10 MB</strong></small>
          </div>

          <!-- File input -->
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.82rem;">Select CSV File</label>
            <div class="input-group d-none">
              <input type="file" class="form-control" accept=".csv" style="font-size:.85rem;">
              <span class="input-group-text bg-white" style="font-size:.8rem; color:#78909c;">
                <i class="bi bi-filetype-csv me-1"></i>.csv only
              </span>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-brand flex-grow-1" data-bs-toggle="modal" data-bs-target="#importCsvModal">
              <i class="bi bi-cloud-upload-fill"></i> Upload Leads
            </button>
            <button class="btn btn-outline-secondary" style="font-size:.85rem; border-radius:8px;">
              <i class="bi bi-x-circle"></i> Clear
            </button>
          </div>

        </div>
      </div>
    </div>

    <!-- RIGHT: Required Columns -->
    <div class="col-xl-5">
      <div class="card border-0 shadow-sm mb-3" style="border-radius:14px; overflow:hidden;">
        <div class="card-header bg-white border-bottom fw-semibold" style="font-size:.88rem; color:#1a2744;">
          <i class="bi bi-list-check text-primary me-2"></i>Required CSV Columns
        </div>
        <div class="card-body p-3">
          <ul class="req-list list-unstyled mb-0">
            <li><i class="bi bi-dot"></i><strong>campaign_name</strong></li>
            <li><i class="bi bi-dot"></i><strong>platform</strong></li>
            <li><i class="bi bi-dot"></i><strong>which_apartment_size_are_you_interested_in</strong></li>
            <li><i class="bi bi-dot"></i><strong>which_floor_are_you_for_shop</strong></li>
            <li><i class="bi bi-dot"></i><strong>when_are_you_planning_to_buy</strong></li>
            <li><i class="bi bi-dot"></i><strong>would_you_like_to_schedule_a_site_visit</strong></li>
            <li><i class="bi bi-dot"></i><strong>when_is_the_best_time_to_contact_you</strong></li>
            <li><i class="bi bi-dot"></i><strong>full_name</strong></li>
            <li><i class="bi bi-dot"></i><strong>job_title</strong></li>
            <li><i class="bi bi-dot"></i><strong>company_name</strong></li>
            <li><i class="bi bi-dot"></i><strong>phone_number</strong></li>
            <li><i class="bi bi-dot"></i><strong>city</strong></li>
          </ul>
        </div>
      </div>
    </div>

  </div>
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

          <!-- From Date -->
          <div class="col-md-2 col-6">
            <label class="form-label small text-muted fw-semibold">From Date</label>
            <input type="date" name="from_date" class="form-control form-control-sm" value="<?= htmlspecialchars($from_date ?? '') ?>">
          </div>

          <!-- To Date -->
          <div class="col-md-2 col-6">
            <label class="form-label small text-muted fw-semibold">To Date</label>
            <input type="date" name="to_date" class="form-control form-control-sm" value="<?= htmlspecialchars($to_date ?? '') ?>">
          </div>

          <!-- Sort -->
          <div class="col-md-2 col-6">
            <label class="form-label small text-muted fw-semibold">Sort</label>
            <select name="sort" class="form-select form-select-sm">
              <option value="newest" <?= ($sort_order ?? '') == 'newest' ? 'selected' : '' ?>>Newest</option>
              <option value="oldest" <?= ($sort_order ?? '') == 'oldest' ? 'selected' : '' ?>>Oldest</option>
            </select>
          </div>

          <!-- Contact Status Filter -->
          <div class="col-md-2 col-6">
            <label class="form-label small text-muted fw-semibold">Contact Status</label>
            <select name="contact_status" class="form-select form-select-sm">
              <option value="" <?= empty($_GET['contact_status']) ? 'selected' : '' ?>>All</option>
              <option value="contacted" <?= ($_GET['contact_status'] ?? '') == 'contacted' ? 'selected' : '' ?>>✅ Contacted</option>
              <option value="not_contacted" <?= ($_GET['contact_status'] ?? '') == 'not_contacted' ? 'selected' : '' ?>>❌ Not Contacted</option>
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

          <!--  Small Duplicate Count UI -->
          <div class="col-md-2 col-6 d-flex align-items-end">
            <a href="<?= $GLOBALS['base_url'] ?>/digital-marketing-executive-dashboard?duplicates_only=1&date_age=all"
              class="text-decoration-none w-100">

              <div class="d-flex align-items-center justify-content-between px-2 py-2 rounded"
                style="background:#fff5f5; border:1px solid #f5c2c7;">

                <div>
                  <div class="small text-danger fw-semibold">
                    <i class="bi bi-exclamation-circle me-1"></i>Total Duplicates Lead
                  </div>
                  <div style="font-size:.65rem; color:#999; margin-top:1px;">
                    by Phone &amp; Email
                  </div>
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

            <a href="<?= $GLOBALS['base_url'] ?>/digital-marketing-executive-dashboard"
              class="btn btn-outline-secondary btn-sm">
              <i class="bi bi-arrow-clockwise"></i>
            </a>

          </div>

        </div>

      </form>
 
    </div>
  </div>

  <!-- Recent Leads Table -->
  <?php if (isset($_SESSION['user']['role_id']) && $_SESSION['user']['role_id'] == 11): ?>
    <div class="row mt-4">
      <div class="col-12">
        <div class="history-card shadow-sm border-0">
          <div class="card-header d-flex justify-content-between align-items-center bg-white py-3 flex-wrap gap-2">
            <h6 class="mb-0 fw-bold text-dark">
              <i class="bi bi-layers me-2 text-primary"></i>
              <span id="tableHeaderText">Uploaded Leads</span>
            </h6>
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <!-- Search -->
              <div class="input-group input-group-sm" style="width:220px;">
                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="dmLeadSearch" class="form-control" placeholder="Search name, phone, campaign...">
              </div>
              <!-- Per page -->
              <select id="dmPerPage" class="form-select form-select-sm" style="width:80px;">
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
              </select>
              <span class="badge bg-primary-soft text-primary rounded-pill" id="dmLeadCount">— Leads</span>
            </div>
          </div>

          <div class="table__wrapper table-responsive my-3">
            <!-- Bulk Delete -->
            <div class="mb-2">
              <button id="bulkDeleteBtn" class="btn btn-danger btn-sm">
                <i class="bi bi-trash"></i> Delete Selected
              </button>
            </div>

            <table class="table no-border-table mt-20" id="dmLeadTable">
              <thead class="py-3">
                <tr>
                  <th class="text-center"><input type="checkbox" id="selectAll"></th>
                  <th class="text-center" style="width:42px">#</th>
                  <th class="ps-4">Lead Name</th>
                  <th>Contact Info</th>
                  <th class="text-center">Follow Up</th>
                  <th>Contact Status</th>
                  <th>Lead Type</th>
                  <th>Platform &amp; Campaign</th>
                  <th>Team Name</th>
                  <th>Sales Team Lead</th>
                  <th>Assigned To</th>
                  <th class="pe-4 text-end">Upload Date</th>
                  <th class="pe-4 text-center">Action</th>
                </tr>
              </thead>
              <tbody id="dmLeadTbody">
                <tr><td colspan="13" class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>
              </tbody>
            </table>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center px-3 pb-3 flex-wrap gap-2" id="dmPaginationWrap">
              <div class="text-muted" style="font-size:.8rem;" id="dmPaginationInfo"></div>
              <nav><ul class="pagination pagination-sm mb-0" id="dmPagination"></ul></nav>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

</div>

<!-- CSV Import Modal -->
<div class="modal fade" id="importCsvModal">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Import Leads from CSV</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form action="<?= $GLOBALS['base_url'] ?>/leads-import-csv" method="post" enctype="multipart/form-data">
          <div class="mb-3">
            <label class="form-label">Upload CSV File <span class="text-danger">*</span></label>
            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Select Team <span class="text-danger">*</span></label>
            <select name="team_id" class="form-select" required>
              <option value="">-- Select Team --</option>
              <?php foreach ($teams as $team): ?>
                <option value="<?= $team['id'] ?>">
                  <?= htmlspecialchars($team['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Select Project</label>
            <select name="project_id" class="form-select select2-no-scroll" data-placeholder="-- Select Project (Optional) --">
              <option value="">-- Select Project (Optional) --</option>
              <?php foreach ($projects as $project): ?>
                <option value="<?= $project['id'] ?>">
                  <?= htmlspecialchars($project['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="text-center">
            <button type="submit" class="btn btn-primary">Import</button>
          </div>
        </form>
        <p class="mt-3 text-muted small">CSV header must match the columns listed on the right.</p>
      </div>


    </div>
  </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
  const base_url   = "<?= $GLOBALS['base_url'] ?>";
  const dmFilters  = {
    team_id        : "<?= htmlspecialchars($_GET['team_id']        ?? '') ?>",
    date_age       : "<?= htmlspecialchars($_GET['date_age']       ?? 'all') ?>",
    sort           : "<?= htmlspecialchars($_GET['sort']           ?? 'newest') ?>",
    contact_status : "<?= htmlspecialchars($_GET['contact_status'] ?? '') ?>",
    duplicates_only: "<?= isset($_GET['duplicates_only']) && $_GET['duplicates_only'] == '1' ? '1' : '' ?>",
    from_date      : "<?= htmlspecialchars($_GET['from_date']      ?? '') ?>",
    to_date        : "<?= htmlspecialchars($_GET['to_date']        ?? '') ?>",
  };

  let dmCurrentPage = 1;
  let dmPerPage     = 30;
  let dmSearchTimer = null;
  let dmSearchVal   = '';

  function dmLoadLeads(page = 1) {
    dmCurrentPage = page;
    const tbody   = document.getElementById('dmLeadTbody');
    tbody.innerHTML = '<tr><td colspan="13" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>';

    const params = new URLSearchParams({
      ...dmFilters,
      page    : page,
      per_page: dmPerPage,
      search  : dmSearchVal,
    });

    fetch(`${base_url}/dm-exec-leads-ajax?${params.toString()}`)
      .then(r => r.json())
      .then(data => {
        if (data.error) {
          tbody.innerHTML = `<tr><td colspan="13" class="text-center py-4 text-danger">${data.error}</td></tr>`;
          return;
        }

        // Header text
        const headerLabels = { 'today':'Today\'s Uploaded Leads', 'old':'Old Uploaded Leads', '7days':'Recent Leads (Last 7 Days)', '15days':'Recent Leads (Last 15 Days)', 'all':'All Uploaded Leads' };
        let headerText = headerLabels[dmFilters.date_age] || 'Uploaded Leads';
        if (dmFilters.duplicates_only === '1') headerText += ' (Duplicates Only)';
        document.getElementById('tableHeaderText').textContent = headerText;
        document.getElementById('dmLeadCount').textContent = `${data.total} Leads`;

        // Render rows
        if (!data.rows || data.rows.length === 0) {
          tbody.innerHTML = '<tr><td colspan="13" class="text-center py-5"><i class="bi bi-inbox fs-1 d-block mb-2 text-muted"></i><div class="text-muted">No leads found.</div></td></tr>';
          document.getElementById('dmPaginationInfo').textContent = '';
          document.getElementById('dmPagination').innerHTML = '';
          return;
        }

        const startIndex = (page - 1) * dmPerPage;
        tbody.innerHTML = data.rows.map((row, i) => `
          <tr>
            <td class="text-center">${row.checkbox}</td>
            <td class="text-center text-muted" style="font-size:.8rem;font-weight:600">${startIndex + i + 1}</td>
            <td class="ps-4">${row.name}</td>
            <td>${row.contact}</td>
            <td class="text-center">${row.follow_up}</td>
            <td>${row.contact_status}</td>
            <td>${row.lead_type}</td>
            <td>${row.platform}</td>
            <td>${row.team}</td>
            <td>${row.sales_lead}</td>
            <td>${row.assigned_to}</td>
            <td class="pe-4 text-end">${row.upload_date}</td>
            <td class="pe-4 text-center">${row.action}</td>
          </tr>
        `).join('');

        // Pagination info
        const from = ((page - 1) * dmPerPage) + 1;
        const to   = Math.min(page * dmPerPage, data.total);
        document.getElementById('dmPaginationInfo').textContent = `Showing ${from}–${to} of ${data.total} leads`;

        // Pagination buttons
        const pag = document.getElementById('dmPagination');
        pag.innerHTML = '';
        const totalPages = data.total_pages;
        if (totalPages <= 1) return;

        const addBtn = (label, p, disabled = false, active = false) => {
          const li = document.createElement('li');
          li.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
          li.innerHTML = `<a class="page-link" href="#">${label}</a>`;
          if (!disabled && !active) li.querySelector('a').addEventListener('click', e => { e.preventDefault(); dmLoadLeads(p); });
          pag.appendChild(li);
        };

        addBtn('«', 1, page === 1);
        addBtn('‹', page - 1, page === 1);

        let start = Math.max(1, page - 2);
        let end   = Math.min(totalPages, page + 2);
        if (start > 1) { addBtn('1', 1); if (start > 2) addBtn('...', null, true); }
        for (let i = start; i <= end; i++) addBtn(i, i, false, i === page);
        if (end < totalPages) { if (end < totalPages - 1) addBtn('...', null, true); addBtn(totalPages, totalPages); }

        addBtn('›', page + 1, page === totalPages);
        addBtn('»', totalPages, page === totalPages);
      })
      .catch(() => {
        tbody.innerHTML = '<tr><td colspan="13" class="text-center py-4 text-danger">Failed to load leads. Please refresh.</td></tr>';
      });
  }

  document.addEventListener('DOMContentLoaded', function () {
    // Start from URL ?page= if present (e.g., after redirect)
    const _urlP = new URLSearchParams(window.location.search);
    const _startPage = parseInt(_urlP.get('page') || '1', 10);
    dmLoadLeads(_startPage > 1 ? _startPage : 1);

    // Per page change
    document.getElementById('dmPerPage').addEventListener('change', function () {
      dmPerPage = parseInt(this.value);
      dmLoadLeads(1);
    });

    // Search with debounce
    document.getElementById('dmLeadSearch').addEventListener('input', function () {
      clearTimeout(dmSearchTimer);
      dmSearchTimer = setTimeout(() => {
        dmSearchVal = this.value.trim();
        dmLoadLeads(1);
      }, 400);
    });

    // Select all
    document.getElementById('selectAll').addEventListener('click', function () {
      document.querySelectorAll('#dmLeadTbody .rowCheckbox').forEach(cb => cb.checked = this.checked);
    });

    // Individual delete (AJAX — stays on current page)
    document.addEventListener('click', function (e) {
      const btn = e.target.closest('.removeBtnCust');
      if (!btn) return;
      e.preventDefault();
      const leadId   = btn.getAttribute('data-id');
      const leadName = btn.getAttribute('data-name');
      if (!confirm(`Are you sure you want to permanently delete "${leadName}"?`)) return;

      const fd = new FormData();
      fd.append('id',            leadId);
      fd.append('redirect_page', dmCurrentPage);
      fd.append('ajax',          '1');

      fetch(`${base_url}/delete-lead-permanent`, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
          if (data.ok) {
            dmLoadLeads(dmCurrentPage); // reload same page
          } else {
            alert(data.message || 'Delete failed.');
          }
        })
        .catch(() => alert('Delete failed. Please try again.'));
    });

    // Bulk delete (AJAX — stays on current page)
    document.getElementById('bulkDeleteBtn').addEventListener('click', function () {
      const selected = [...document.querySelectorAll('#dmLeadTbody .rowCheckbox:checked')].map(cb => cb.value);
      if (!selected.length) { alert('Please select at least one lead'); return; }
      if (!confirm('Are you sure you want to delete selected leads?')) return;

      const fd = new FormData();
      selected.forEach(id => fd.append('ids[]', id));
      fd.append('redirect_page', dmCurrentPage);
      fd.append('ajax',          '1');

      fetch(`${base_url}/delete-leads-permanent-multiple`, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
          if (data.ok) {
            dmLoadLeads(dmCurrentPage); // reload same page
          } else {
            alert(data.message || 'Delete failed.');
          }
        })
        .catch(() => alert('Delete failed. Please try again.'));
    });

    // Prevent page reload when Import CSV clicked
    const importBtn = document.getElementById('importCsvBtn');
    if (importBtn) {
      importBtn.addEventListener('click', function (e) { e.preventDefault(); });
    }
  });
</script>