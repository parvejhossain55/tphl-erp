<?php
$base_url = $GLOBALS['base_url'];
$queryString = $_GET;
$queryString['download_pdf'] = 1;
$pdfUrl = $base_url . '/con-land-ledger?' . http_build_query($queryString);
?>
<div class="mb-3 pagetitle d-print-none d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4 class="mb-2"><?= htmlspecialchars($page_title) ?></h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Land Ledger</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $pdfUrl ?>" class="btn btn-outline-danger">
            <i class="fa-light fa-file-pdf me-1"></i> Download PDF
        </a>
        <button onclick="window.print()" class="btn btn-outline-primary">
            <i class="fa-light fa-print me-1"></i> Print Statement
        </button>
    </div>
</div>

<!-- Print Header (Visible only when printing) -->
<div class="d-none d-print-block mb-4 text-center">
    <h2><?= htmlspecialchars($page_title) ?></h2>
    <h5>TPHL Land Department Ledger</h5>
    <div class="text-muted small">Report Generated: <?= date('d M Y, h:i A') ?></div>
    <hr>
</div>

<!-- FILTER CARD (Hidden in Print) -->
<div class="card border-0 shadow-sm mb-4 d-print-none">
  <div class="card-body py-3">
    <form action="" method="GET" class="row align-items-end g-2">
      <div class="col-md-3">
        <label class="form-label small fw-bold text-muted mb-1">Filter by Project</label>
        <select name="project_id" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">-- All Projects --</option>
          <?php foreach ($projects as $p): ?>
            <option value="<?= $p['id'] ?>" <?= $selectedProjectId == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small fw-bold text-muted mb-1">Filter by Land Owner / Portfolio</label>
        <select name="land_acquisition_id" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">-- All Land Portfolios --</option>
          <?php foreach ($acquisitions as $acq): ?>
            <option value="<?= $acq['id'] ?>" <?= $selectedAcqId == $acq['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($acq['land_owner_name']) ?> (<?= htmlspecialchars($acq['project_name']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-bold text-muted mb-1">From Date</label>
        <input type="date" name="from_date" class="form-control form-control-sm" value="<?= htmlspecialchars($from_date ?? '') ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-bold text-muted mb-1">To Date</label>
        <input type="date" name="to_date" class="form-control form-control-sm" value="<?= htmlspecialchars($to_date ?? '') ?>">
      </div>
      <div class="col-md-2 d-flex gap-1">
        <button type="submit" class="btn btn-sm btn-primary w-100">
          <i class="fa-light fa-filter"></i> Filter
        </button>
        <a href="<?= $base_url ?>/con-land-ledger" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
      </div>
    </form>
  </div>
</div>

<!-- SUMMARY CARDS -->
<div class="row mb-4">
  <div class="col-md-3 col-sm-6 mb-3">
    <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #0d6efd !important;">
      <div class="card-body py-3">
        <div class="text-muted small fw-bold text-uppercase mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Total Valuation Budget</div>
        <h4 class="mb-0 fw-black text-primary">৳ <?= number_format($totalValuation, 2) ?></h4>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-sm-6 mb-3">
    <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #198754 !important;">
      <div class="card-body py-3">
        <div class="text-muted small fw-bold text-uppercase mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Fund Received (Paid)</div>
        <h4 class="mb-0 fw-black text-success">৳ <?= number_format($totalReceived, 2) ?></h4>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-sm-6 mb-3">
    <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #dc3545 !important;">
      <div class="card-body py-3">
        <div class="text-muted small fw-bold text-uppercase mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Total Actual Spent</div>
        <h4 class="mb-0 fw-black text-danger">৳ <?= number_format($totalSpent, 2) ?></h4>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-sm-6 mb-3">
    <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #ffc107 !important;">
      <div class="card-body py-3">
        <div class="text-muted small fw-bold text-uppercase mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Cash / Budget Balance</div>
        <h4 class="mb-0 fw-black text-warning">৳ <?= number_format($balanceCash, 2) ?></h4>
      </div>
    </div>
  </div>
</div>

<!-- LEDGER TABLE -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header border-0 py-3 bg-white d-flex justify-content-between align-items-center">
    <h5 class="mb-0 fw-bold"><i class="fal fa-file-invoice-dollar me-2 text-primary"></i>Chronological Account Statement</h5>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
        <thead class="table-light">
          <tr>
            <th style="width: 140px;">Date &amp; Time</th>
            <th>Description &amp; Project Info</th>
            <th style="width: 120px;">Type</th>
            <th class="text-center" style="width: 100px;">Status</th>
            <th class="text-end" style="width: 140px;">Inflow (Received)</th>
            <th class="text-end" style="width: 140px;">Outflow (Spent)</th>
            <th class="text-end" style="width: 150px;">Running Balance</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($ledgerEntries)): ?>
            <?php if ($from_date): ?>
              <tr class="table-light">
                <td>
                  <span class="fw-semibold"><?= date('d M Y', strtotime($from_date)) ?></span>
                </td>
                <td>
                  <div class="fw-bold text-dark">Opening Balance (Before <?= date('d M Y', strtotime($from_date)) ?>)</div>
                  <div class="small text-muted">Brought forward balance</div>
                </td>
                <td><span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Balance B/F</span></td>
                <td class="text-center">—</td>
                <td class="text-end fw-semibold text-muted">—</td>
                <td class="text-end fw-semibold text-muted">—</td>
                <td class="text-end fw-bold text-indigo">
                  ৳ <?= number_format($openingBalance, 2) ?>
                </td>
              </tr>
              <tr>
                <td colspan="7" class="text-center py-4 text-muted">
                  No transaction entries found in this date range.
                </td>
              </tr>
            <?php else: ?>
              <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="fal fa-cabinet-filing me-2 fs-3 d-block mb-3"></i> No ledger entries found for the selected criteria.
                </td>
              </tr>
            <?php endif; ?>
          <?php else: ?>
            <?php if ($from_date): ?>
              <tr class="table-light">
                <td>
                  <span class="fw-semibold"><?= date('d M Y', strtotime($from_date)) ?></span>
                </td>
                <td>
                  <div class="fw-bold text-dark">Opening Balance (Before <?= date('d M Y', strtotime($from_date)) ?>)</div>
                  <div class="small text-muted">Brought forward balance</div>
                </td>
                <td><span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Balance B/F</span></td>
                <td class="text-center">—</td>
                <td class="text-end fw-semibold text-muted">—</td>
                <td class="text-end fw-semibold text-muted">—</td>
                <td class="text-end fw-bold text-indigo">
                  ৳ <?= number_format($openingBalance, 2) ?>
                </td>
              </tr>
            <?php endif; ?>
            <?php foreach ($ledgerEntries as $entry): ?>
              <?php 
                $isRequisition = ($entry['type'] === 'requisition');
                $isPaid = ($entry['status'] === 'Paid');
                
                $rowClass = '';
                if ($isRequisition && !$isPaid) {
                    $rowClass = 'text-muted table-light d-print-none'; // Hide unpaid requisitions in print by default
                }
              ?>
              <tr class="<?= $rowClass ?>">
                <td>
                  <span class="fw-semibold"><?= date('d M Y', strtotime($entry['date'])) ?></span>
                  <br><small class="text-muted"><?= date('h:i A', strtotime($entry['date'])) ?></small>
                </td>
                <td>
                  <div class="fw-bold text-dark"><?= $entry['description'] ?></div>
                  <div class="small text-muted">
                    Landowner: <strong><?= htmlspecialchars($entry['landowner']) ?></strong> 
                    &nbsp;·&nbsp; Project: <strong><?= htmlspecialchars($entry['project_name']) ?></strong>
                  </div>
                  <?php if (!$isRequisition || $isPaid): ?>
                    <div class="small text-muted mt-1">
                      Method: <code><?= htmlspecialchars($entry['details']['pay_method']) ?></code> 
                      &nbsp;·&nbsp; Ref/Cheque/Txn: <code><?= htmlspecialchars($entry['details']['txn_no']) ?></code>
                      &nbsp;·&nbsp; Officer: <strong><?= htmlspecialchars($entry['details']['requester']) ?></strong>
                    </div>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge bg-secondary"><?= htmlspecialchars($entry['requisition_type']) ?></span>
                </td>
                <td class="text-center">
                  <?php if ($isPaid): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2">Paid</span>
                  <?php elseif ($entry['status'] === 'Approved'): ?>
                    <span class="badge bg-info-subtle text-info border border-info-subtle px-2">Approved</span>
                  <?php elseif ($entry['status'] === 'Rejected'): ?>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2">Rejected</span>
                  <?php else: ?>
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2">Pending</span>
                  <?php endif; ?>
                </td>
                <td class="text-end fw-semibold text-success">
                  <?= $entry['inflow'] > 0 ? '৳ ' . number_format($entry['inflow'], 2) : '—' ?>
                  <?php if ($isRequisition && !$isPaid): ?>
                    <br><small class="text-muted" style="font-size: 11px;">(Req: ৳ <?= number_format($entry['req_amount'], 2) ?>)</small>
                  <?php endif; ?>
                </td>
                <td class="text-end fw-semibold text-danger">
                  <?= $entry['outflow'] > 0 ? '৳ ' . number_format($entry['outflow'], 2) : '—' ?>
                </td>
                <td class="text-end fw-bold text-indigo">
                  ৳ <?= number_format($entry['running_balance'], 2) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<style>
  @media print {
    .app-sidebar,
    .sidebar,
    .app__header__area,
    .main-header-container,
    .main-footer,
    .d-print-none {
      display: none !important;
    }
    
    .page__full-wrapper,
    .page__body-wrapper,
    body,
    .card {
      margin: 0 !important;
      padding: 0 !important;
      border: none !important;
      box-shadow: none !important;
      background: white !important;
      color: black !important;
    }
    
    .table {
      width: 100% !important;
      border-collapse: collapse !important;
    }
    
    .table th, .table td {
      border: 1px solid #ddd !important;
      padding: 8px !important;
    }
  }
</style>
