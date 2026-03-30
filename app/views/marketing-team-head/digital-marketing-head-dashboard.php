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
    color: #1a2744;
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
    background: var(--brand);
    border-radius: 14px 14px 0 0 !important;
    padding: 1rem 1.4rem;
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
    background: #fff;
    box-shadow: 0 1px 6px rgba(0, 0, 0, .07);
    overflow: hidden;
  }

  .history-card .card-header {
    background: #fff;
    border-bottom: 1px solid #e9ecef;
    padding: .9rem 1.4rem;
    font-weight: 600;
    font-size: .9rem;
    color: #1a2744;
  }

  .history-card table thead th {
    font-size: .72rem;
    letter-spacing: .07em;
    text-transform: uppercase;
    color: #78909c;
    font-weight: 600;
    border-bottom: 1px solid #e9ecef;
    padding: .65rem 1rem;
    background: #f8fafc;
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


<!-- ════════════════════════════════════
     SIDEBAR
════════════════════════════════════ -->


<!-- ════════════════════════════════════
     MAIN WRAPPER
════════════════════════════════════ -->
<div class="app__slide-wrapper">

  <!-- Stat row -->
  <div class="row mb-4">
    <div class="col-xl-3 col-sm-6">
      <div class="card__wrapper stat-card">
        
        <div>
          <div class="label">Total Leads</div>
          <div class="value"><?= number_format($stats['total_leads'] ?? 0) ?></div>
          <div class="change text-success"><i class="bi bi-arrow-up-short"></i>Data sync ready</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="card__wrapper stat-card">
        
        <div>
          <div class="label">Uploaded This Week</div>
          <div class="value"><?= number_format($stats['leads_this_week'] ?? 0) ?></div>
          <div class="change text-success"><i class="bi bi-arrow-up-short"></i>Last 7 days</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="card__wrapper stat-card">
       
        <div>
          <div class="label">Failed / Errors</div>
          <div class="value"><?= number_format($stats['failed_leads'] ?? 0) ?></div>
          <div class="change text-danger"><i class="bi bi-arrow-down-short"></i>Inactive/Cancelled</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="card__wrapper stat-card">
       
        <div>
          <div class="label">Total Uploads</div>
          <div class="value"><?= number_format($stats['total_uploads'] ?? 0) ?></div>
          <div class="change text-muted">Marketing leads</div>
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
          <h6>Bulk Lead Upload – CSV</h6>
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
            <div class="input-group">
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

  </div><!-- /row -->

</div>

<!-- CSV Import Modal -->
<div class="modal fade" id="importCsvModal" tabindex="-1">
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
  document.addEventListener('DOMContentLoaded', function() {
    // Prevent page reload when Import CSV clicked
    const importBtn = document.getElementById('importCsvBtn');
    importBtn.addEventListener('click', function(e) {
      e.preventDefault(); // ensures no form submit
      // Bootstrap modal opens automatically via data-bs-toggle="modal"
    });
  });
</script>