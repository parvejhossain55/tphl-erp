<?php
$addPage = true;
$show_breadcrumb = false;
$base = $GLOBALS['base_url'];

$project  = $project  ?? [];
$budget   = $budget   ?? null;
$progress = $progress ?? 0;
$landStudy       = $landStudy       ?? null;
$soilTests       = $soilTests       ?? [];
$drawings        = $drawings        ?? [];
$budgetBreakdown = $budgetBreakdown ?? [];

function pdFmt($n) {
    $n = (float)$n;
    if ($n >= 10000000) return '৳' . number_format($n/10000000, 2) . ' Cr';
    if ($n >= 100000)   return '৳' . number_format($n/100000,   2) . ' L';
    return '৳' . number_format($n, 2);
}

$progColor = $progress >= 75 ? '#10b981' : ($progress >= 40 ? '#f59e0b' : '#ef4444');
$statusMap = [
    'Planning'    => ['bg'=>'rgba(99,102,241,.12)', 'text'=>'#6366f1'],
    'In Progress' => ['bg'=>'rgba(245,158,11,.12)', 'text'=>'#f59e0b'],
    'On Hold'     => ['bg'=>'rgba(239,68,68,.12)',  'text'=>'#ef4444'],
    'Completed'   => ['bg'=>'rgba(16,185,129,.12)', 'text'=>'#10b981'],
];
$sc = $statusMap[$project['project_status'] ?? ''] ?? ['bg'=>'rgba(0,0,0,.07)','text'=>'#6b7280'];
?>
<div class="row"><div class="col-12">
<style>
/* ── Layout ────────────────────────────────────────────────── */
.pd-header {
  border-radius:16px;
  padding:26px 28px;
  border:1px solid var(--border-color, rgba(0,0,0,.08));
  margin-bottom:22px;
  position:relative;
  overflow:hidden;
}
.pd-header::before {
  content:'';
  position:absolute;
  top:0;left:0;right:0;
  height:4px;
  background:linear-gradient(90deg,#3b82f6,#8b5cf6,#10b981);
}
.pd-info-grid {
  display:grid;
  grid-template-columns:repeat(4,1fr);
  gap:16px;
  margin-top:20px;
}
.pd-info-box {
  border-radius:10px;
  padding:14px 16px;
  border:1px solid rgba(0,0,0,.07);
}
.pd-info-label { font-size:10px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--text-muted);margin-bottom:5px; }
.pd-info-value { font-size:17px;font-weight:700;line-height:1.2; }
.pd-info-sub   { font-size:11px;color:var(--text-muted);margin-top:2px; }

.pd-prog-ring {
  width:80px;height:80px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  position:relative;flex-shrink:0;
}
.pd-prog-inner { position:absolute;z-index:2;text-align:center; }
.pd-prog-pct   { font-size:17px;font-weight:800;line-height:1; }
.pd-prog-lbl   { font-size:9px; }

/* ── Tab navigation ────────────────────────────────────────── */
.pd-tabs {
  display:flex;gap:0;border-bottom:2px solid rgba(0,0,0,.08);
  margin-bottom:22px;overflow-x:auto;
}
.pd-tab {
  padding:10px 18px;
  font-size:12.5px;font-weight:600;
  cursor:pointer;border:none;background:none;
  border-bottom:3px solid transparent;margin-bottom:-2px;
  white-space:nowrap;
  color:var(--text-muted);
  transition:color .15s, border-color .15s;
}
.pd-tab.active {
  color:#3b82f6;
  border-bottom-color:#3b82f6;
}
.pd-tab-pane { display:none; }
.pd-tab-pane.active { display:block; }

/* ── Panels ────────────────────────────────────────────────── */
.pd-panel {
  border-radius:12px;padding:20px;
  border:1px solid var(--border-color, rgba(0,0,0,.08));
  margin-bottom:16px;
}
.pd-panel-title {
  font-size:12px;font-weight:700;letter-spacing:.8px;
  text-transform:uppercase;margin-bottom:14px;
  padding-bottom:10px;
  border-bottom:1px solid rgba(0,0,0,.07);
  display:flex;align-items:center;gap:8px;
}

/* ── Document rows ─────────────────────────────────────────── */
.doc-row {
  display:flex;align-items:center;justify-content:space-between;
  padding:10px 14px;border-radius:9px;margin-bottom:8px;
  border:1px solid rgba(0,0,0,.06);
}
.doc-row:last-child { margin-bottom:0; }
.doc-icon { font-size:22px;margin-right:12px; }
.doc-name  { font-size:13px;font-weight:600; }
.doc-sub   { font-size:11px;color:var(--text-muted); }
.doc-status-pill {
  font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;
}

/* ── Design cards ──────────────────────────────────────────── */
.design-card {
  border-radius:10px;padding:14px 16px;
  border:1px solid rgba(0,0,0,.07);margin-bottom:10px;
}
.design-card:last-child { margin-bottom:0; }
.design-type-badge {
  display:inline-block;font-size:10px;font-weight:700;
  padding:3px 9px;border-radius:6px;margin-bottom:8px;
  background:rgba(99,102,241,.1);color:#6366f1;
}
.design-meta { font-size:11.5px;display:flex;gap:20px;flex-wrap:wrap;margin-top:8px; }
.design-meta-item { display:flex;flex-direction:column;gap:1px; }
.design-meta-label { font-size:10px;color:var(--text-muted); }
.design-meta-val   { font-size:12.5px;font-weight:600; }

/* ── Budget table ──────────────────────────────────────────── */
.bud-table { width:100%;border-collapse:collapse; }
.bud-table th {
  font-size:10px;letter-spacing:1px;text-transform:uppercase;font-weight:700;
  padding:8px 12px;border-bottom:2px solid rgba(0,0,0,.08);text-align:left;
}
.bud-table td { padding:10px 12px;border-bottom:1px solid rgba(0,0,0,.05);font-size:12.5px; }
.bud-table tr:last-child td { border-bottom:none; }
.bud-table tfoot td { font-weight:700;font-size:13px;border-top:2px solid rgba(0,0,0,.08); }
.bud-mat-col { color:#f59e0b;font-weight:600; }
.bud-lab-col { color:#8b5cf6;font-weight:600; }
.bud-tot-col { color:#3b82f6;font-weight:700; }

/* ── Work Plan / Targets ───────────────────────────────────── */
.wp-table { width:100%;border-collapse:collapse; }
.wp-table th { font-size:10px;letter-spacing:1px;text-transform:uppercase;font-weight:700;padding:8px 12px;border-bottom:2px solid rgba(0,0,0,.08);text-align:left; }
.wp-table td { padding:10px 12px;border-bottom:1px solid rgba(0,0,0,.05);font-size:12.5px;vertical-align:middle; }
.wp-table tr:last-child td { border-bottom:none; }

/* ── Weekly Report ─────────────────────────────────────────── */
.wr-month-tabs { display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px; }
.wr-month-btn {
  padding:7px 14px;border-radius:8px;font-size:12px;font-weight:600;
  cursor:pointer;border:1px solid rgba(0,0,0,.1);background:none;
  transition:all .15s;color:var(--text-muted);
}
.wr-month-btn.active { background:#3b82f6;color:#fff;border-color:#3b82f6; }
.wr-week-tabs { display:flex;gap:6px;margin-bottom:14px; }
.wr-week-btn {
  padding:5px 13px;border-radius:6px;font-size:11.5px;font-weight:600;
  cursor:pointer;border:1px solid rgba(0,0,0,.1);background:none;
  transition:all .15s;color:var(--text-muted);
}
.wr-week-btn.active { background:#6366f1;color:#fff;border-color:#6366f1; }
.wr-subtabs { display:flex;gap:0;border-bottom:1px solid rgba(0,0,0,.08);margin-bottom:14px; }
.wr-subtab {
  padding:7px 16px;font-size:12px;font-weight:600;
  cursor:pointer;border:none;background:none;
  border-bottom:2px solid transparent;margin-bottom:-1px;
  color:var(--text-muted);transition:all .15s;
}
.wr-subtab.active { color:#10b981;border-bottom-color:#10b981; }
.wr-sub-pane  { display:none; }
.wr-sub-pane.active { display:block; }

/* ── Approval dots ─────────────────────────────────────────── */
.apv-dot {
  display:inline-block;width:20px;height:20px;border-radius:50%;
  font-size:10px;font-weight:700;text-align:center;line-height:20px;
  margin:0 2px;
}
.apv-yes { background:rgba(16,185,129,.15);color:#10b981; }
.apv-no  { background:rgba(0,0,0,.07);color:var(--text-muted); }

/* ── Loading spinner ───────────────────────────────────────── */
.ajax-loader {
  text-align:center;padding:40px;
  color:var(--text-muted);font-size:13px;
}
.spinner {
  display:inline-block;width:24px;height:24px;
  border:3px solid rgba(59,130,246,.2);
  border-top-color:#3b82f6;border-radius:50%;
  animation:spin .7s linear infinite;margin-right:10px;
  vertical-align:middle;
}
@keyframes spin { to { transform:rotate(360deg); } }

/* ── Empty state ───────────────────────────────────────────── */
.empty-pd { text-align:center;padding:32px;color:var(--text-muted);font-size:13px; }
.empty-pd-ico { font-size:36px;opacity:.3;margin-bottom:10px; }
</style>

<!-- ═══════════════════════ BACK + HEADER ════════════════════════════════ -->
<div class="mb-3">
  <a href="<?=$base?>/con-dashboard" style="font-size:12px;color:#3b82f6;text-decoration:none"><i class="fal fa-arrow-left me-1"></i>Back to Dashboard</a>
</div>

<div class="pd-header card__wrapper">
  <div class="d-flex justify-content-between align-items-start">
    <div>
      <div style="font-size:11px;font-weight:600;letter-spacing:1px;text-transform:uppercase;color:var(--text-muted);margin-bottom:6px">Construction Project</div>
      <h4 style="font-weight:800;margin-bottom:8px"><?= htmlspecialchars($project['name'] ?? 'Project') ?></h4>
      <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
        <span class="doc-status-pill" style="background:<?=$sc['bg']?>;color:<?=$sc['text']?>">
          <?= htmlspecialchars($project['project_status'] ?? 'Unknown') ?>
        </span>
        <?php if (($project['workflow_version'] ?? 'legacy') === 'phase_based'): ?>
        <span class="doc-status-pill" style="background:rgba(16,185,129,.12);color:#10b981;font-weight:700">
          <i class="fal fa-sliders-h-square me-1"></i> Phase-based
        </span>
        <?php else: ?>
        <span class="doc-status-pill" style="background:rgba(100,116,139,.12);color:#64748b;font-weight:700">
          <i class="fal fa-history me-1"></i> Legacy
        </span>
        <?php endif; ?>
        <a href="<?= $base ?>/con-project-timeline/<?= $project['id'] ?>" class="btn btn-xs btn-outline-primary py-1 px-2" style="font-size:11px;font-weight:700;border-radius:6px;text-decoration:none">
          <i class="fal fa-calendar-alt me-1"></i> Timeline Setup
        </a>
        <?php if (!empty($project['location_name'])): ?>
        <span style="font-size:12px;color:var(--text-muted)"><i class="fal fa-map-marker-alt me-1"></i><?= htmlspecialchars($project['location_name']) ?></span>
        <?php endif; ?>
        <?php if (!empty($project['building_type'])): ?>
        <span style="font-size:12px;color:var(--text-muted)"><i class="fal fa-city me-1"></i><?= htmlspecialchars($project['building_type']) ?></span>
        <?php endif; ?>
      </div>
    </div>
    <!-- Progress Ring -->
    <div style="display:flex;align-items:center;gap:14px">
      <div class="pd-prog-ring" style="background:conic-gradient(<?=$progColor?> 0% <?=round($progress*3.6)?>deg,rgba(0,0,0,.07) <?=round($progress*3.6)?>deg 360deg)">
        <div style="width:56px;height:56px;border-radius:50%;position:absolute;background:var(--card-bg,#fff)"></div>
        <div class="pd-prog-inner">
          <div class="pd-prog-pct" style="color:<?=$progColor?>"><?=$progress?>%</div>
          <div class="pd-prog-lbl" style="color:var(--text-muted)">Done</div>
        </div>
      </div>
      <div>
        <div style="font-size:11px;font-weight:600;color:var(--text-muted)">Overall Progress</div>
        <div style="font-size:22px;font-weight:800;color:<?=$progColor?>"><?=$progress?>%</div>
      </div>
    </div>
  </div>

  <!-- Info Grid -->
  <div class="pd-info-grid">
    <div class="pd-info-box">
      <div class="pd-info-label"><i class="fal fa-map-marker-alt me-1"></i>Location</div>
      <div class="pd-info-value" style="font-size:14px"><?= htmlspecialchars($project['location_name'] ?? '—') ?></div>
    </div>
    <div class="pd-info-box">
      <div class="pd-info-label"><i class="fal fa-expand-arrows-alt me-1"></i>Land Area</div>
      <div class="pd-info-value"><?= $project['plot_size'] ? number_format($project['plot_size'], 2) . ' Katha' : '—' ?></div>
    </div>
    <div class="pd-info-box">
      <div class="pd-info-label"><i class="fal fa-layer-group me-1"></i>Total Floors</div>
      <div class="pd-info-value"><?= $project['total_share'] ?: '—' ?></div>
    </div>
    <div class="pd-info-box">
      <div class="pd-info-label"><i class="fal fa-calendar-alt me-1"></i>Start Date</div>
      <div class="pd-info-value" style="font-size:14px"><?= $budget && $budget['start_date'] ? date('d/m/Y', strtotime($budget['start_date'])) : '—' ?></div>
    </div>
    <div class="pd-info-box">
      <div class="pd-info-label"><i class="fal fa-flag-checkered me-1"></i>Expected End</div>
      <div class="pd-info-value" style="font-size:14px"><?= $budget && $budget['end_date'] ? date('d/m/Y', strtotime($budget['end_date'])) : '—' ?></div>
    </div>
    <div class="pd-info-box">
      <div class="pd-info-label"><i class="fal fa-building me-1"></i>Building Type</div>
      <div class="pd-info-value" style="font-size:14px"><?= htmlspecialchars($project['building_type'] ?? '—') ?></div>
    </div>
    <div class="pd-info-box">
      <div class="pd-info-label"><i class="fal fa-door-open me-1"></i>Apartments</div>
      <div class="pd-info-value"><?= $project['apartment_count'] ?: '—' ?></div>
    </div>
    <div class="pd-info-box">
      <div class="pd-info-label"><i class="fal fa-money-bill-wave me-1"></i>Total Budget</div>
      <div class="pd-info-value" style="color:#3b82f6"><?= $budget ? pdFmt($budget['total_estimated_cost'] ?? 0) : '—' ?></div>
    </div>
  </div>
</div>

<!-- ═══════════════════════ TAB NAVIGATION ════════════════════════════════ -->
<div class="pd-tabs">
  <button class="pd-tab active" data-tab="overview"><i class="fal fa-th-list me-1"></i>Overview</button>
  <button class="pd-tab" data-tab="documents"><i class="fal fa-folder-open me-1"></i>Documents</button>
  <button class="pd-tab" data-tab="design"><i class="fal fa-drafting-compass me-1"></i>Design</button>
  <button class="pd-tab" data-tab="budget"><i class="fal fa-money-bill-wave me-1"></i>Initial Budget</button>
  <button class="pd-tab" data-tab="workplan"><i class="fal fa-calendar-alt me-1"></i>Work Plan</button>

  <button class="pd-tab" data-tab="weekly"><i class="fal fa-chart-bar me-1"></i>Weekly Report</button>
  <button class="pd-tab" data-tab="workorders"><i class="fal fa-file-signature me-1"></i>Work Orders</button>
</div>

<!-- ═══════════════════════ TAB 1: OVERVIEW ══════════════════════════════ -->
<div class="pd-tab-pane active" id="tab-overview">
  <div class="row ">
    <div class="col-6">
      <div class="pd-panel">
        <div class="pd-panel-title"><i class="fal fa-hard-hat me-2" style="color:#f59e0b"></i>Project Information</div>
        <?php
        $infoRows = [
          ['Plot No',        $project['plot_no'] ?? '—',        false],
          ['Block No',       $project['block_no'] ?? '—',       false],
          ['Unit/Floor',     $project['unit_per_floor'] ? $project['unit_per_floor'] . ' units' : '—', false],
          ['Passenger Lift', $project['passenger_lift'] ? $project['passenger_lift'] . ' lifts' : '—', false],
          ['Cargo Lift',     $project['cargo_lift'] ? $project['cargo_lift'] . ' lifts' : '—',     false],
          ['Car Parking',    $project['car_parking'] ? $project['car_parking'] . ' slots' : '—',   false],
          ['Rooftop Garden', ($project['rooftop_gardening'] ?? 0) ? '<i class="fas fa-check" style="color:#10b981"></i> Yes' : '<i class="fas fa-times" style="color:#ef4444"></i> No', true],
          ['Swimming Pool',  ($project['swimming_pool'] ?? 0)     ? '<i class="fas fa-check" style="color:#10b981"></i> Yes' : '<i class="fas fa-times" style="color:#ef4444"></i> No', true],
          ['Convention Hall',($project['convention'] ?? 0)        ? '<i class="fas fa-check" style="color:#10b981"></i> Yes' : '<i class="fas fa-times" style="color:#ef4444"></i> No', true],
          ['Power Backup',   ($project['electricity_backup'] ?? 0)? '<i class="fas fa-check" style="color:#10b981"></i> Yes' : '<i class="fas fa-times" style="color:#ef4444"></i> No', true],
        ];
        foreach ($infoRows as [$label, $val, $isHtml]):
        ?>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid rgba(0,0,0,.05);font-size:12.5px">
          <span style="color:var(--text-muted)"><?=$label?></span>
          <span style="font-weight:600"><?= $isHtml ? $val : htmlspecialchars((string)$val) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="col-6">
      <div class="pd-panel mb-3">
        <div class="pd-panel-title"><i class="fal fa-calendar-alt me-2" style="color:#3b82f6"></i>Timeline & Budget</div>
        <?php
        $tlRows = [
          ['Budget Status',  $budget ? ucfirst($budget['status']) : '—'],
          ['Start Date',     $budget && $budget['start_date'] ? date('d M Y', strtotime($budget['start_date'])) : '—'],
          ['Expected End',   $budget && $budget['end_date']   ? date('d M Y', strtotime($budget['end_date']))   : '—'],
          ['Total Budget',   $budget ? pdFmt($budget['total_estimated_cost'] ?? 0) : '—'],
          ['Material Cost',  $budget ? pdFmt($budget['material_cost_total'] ?? 0) : '—'],
          ['Labour Cost',    $budget ? pdFmt($budget['labor_cost_total'] ?? 0) : '—'],
          ['Other Costs',    $budget ? pdFmt($budget['other_costs'] ?? 0) : '—'],
          ['Total Area',     $budget && $budget['total_area_sqft'] ? number_format($budget['total_area_sqft']) . ' sqft' : '—'],
        ];
        
        // Calculate project duration
        if ($budget && !empty($budget['start_date']) && !empty($budget['end_date'])) {
            $sDate = new DateTime($budget['start_date']);
            $eDate = new DateTime($budget['end_date']);
            $interval = $sDate->diff($eDate);
            $totalMonths = ($interval->y * 12) + $interval->m;
            if ($totalMonths <= 0) $totalMonths = 1;
            
            $durationText = $interval->y . ' Years ' . $interval->m . ' Months';
            $budgetWeight = (float)($budget['weight_percentage'] ?? 100);
            $perYearWeight = ($interval->y > 0) ? round($budgetWeight / $interval->y, 2) : $budgetWeight;
            
            $tlRows[] = ['Project Duration', $durationText];
            $tlRows[] = ['Est. Target / Year', $perYearWeight . '% per year (Total: ' . $budgetWeight . '%)'];
        }

        foreach ($tlRows as [$label, $val]):
        ?>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid rgba(0,0,0,.05);font-size:12.5px">
          <span style="color:var(--text-muted)"><?=$label?></span>
          <span style="font-weight:600"><?= htmlspecialchars((string)$val) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php if (!empty($project['description'])): ?>
      <div class="pd-panel">
        <div class="pd-panel-title"><i class="fal fa-align-left me-2"></i>Description</div>
        <p style="font-size:13px;margin:0;line-height:1.6"><?= nl2br(htmlspecialchars($project['description'])) ?></p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ═══════════════════════ TAB 2: DOCUMENTS ════════════════════════════ -->
<div class="pd-tab-pane" id="tab-documents">

  <!-- Land Visibility Study -->
  <div class="pd-panel">
    <div class="pd-panel-title"><i class="fal fa-university me-2" style="color:#6366f1"></i>Land Legal Documents</div>
    <?php if ($landStudy): ?>
    <?php
    $docs = [
      ['Land Legal Status',     $landStudy['land_legal_doc_status'] ?? '—',    $landStudy['digital_survey_report_link'] ?? null, '<i class="fal fa-file-alt"></i>'],
      ['Govt. Approval Doc',    'View Document',                                $landStudy['govt_approval_doc_link'] ?? null, '<i class="fal fa-file-check"></i>'],
      ['Digital Survey Report', 'View Report',                                  $landStudy['digital_survey_report_link'] ?? null, '<i class="fal fa-map"></i>'],
      ['Other Documents',       'View Others',                                  $landStudy['others_doc_link'] ?? null, '<i class="fal fa-paperclip"></i>'],
    ];
    foreach ($docs as [$name, $label, $link, $icon]):
    ?>
    <div class="doc-row">
      <div style="display:flex;align-items:center">
        <span class="doc-icon"><?=$icon?></span>
        <div>
          <div class="doc-name"><?=$name?></div>
          <?php if (!$link): ?>
          <div class="doc-sub">No document uploaded</div>
          <?php endif; ?>
        </div>
      </div>
      <div>
        <?php if ($link): ?>
        <a href="<?= htmlspecialchars($link) ?>" target="_blank" class="btn btn-sm btn-outline-primary" style="font-size:11px"><i class="fal fa-external-link me-1"></i><?=$label?></a>
        <?php else: ?>
        <span class="doc-status-pill" style="background:rgba(0,0,0,.06);color:var(--text-muted)">Not Uploaded</span>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
    <?php else: ?>
    <div class="empty-pd"><div class="empty-pd-ico"><i class="fal fa-folder-open"></i></div>No land study documents found</div>
    <?php endif; ?>
  </div>

  <!-- Soil / Lab Test Reports -->
  <div class="pd-panel">
    <div class="pd-panel-title"><i class="fal fa-flask me-2" style="color:#ec4899"></i>Lab Test Reports (Soil Tests)</div>
    <?php if (empty($soilTests)): ?>
    <div class="empty-pd"><div class="empty-pd-ico"><i class="fal fa-flask"></i></div>No soil test records found</div>
    <?php else: ?>
    <?php foreach ($soilTests as $st): ?>
    <div class="doc-row">
      <div style="display:flex;align-items:center">
        <span class="doc-icon"><i class="fal fa-flask"></i></span>
        <div>
          <div class="doc-name">Soil Test — <?= $st['test_date'] ? date('d M Y', strtotime($st['test_date'])) : 'Date N/A' ?></div>
          <div class="doc-sub"><?= $st['engineer_name'] ?? 'Engineer N/A' ?></div>
        </div>
      </div>
      <div class="d-flex gap-2">
        <?php if (!empty($st['lab_test_document_link'])): ?>
        <a href="<?= htmlspecialchars($st['lab_test_document_link']) ?>" target="_blank" class="btn btn-sm btn-outline-primary" style="font-size:11px"><i class="fal fa-external-link me-1"></i>Lab Report</a>
        <?php else: ?>
        <span class="doc-status-pill" style="background:rgba(0,0,0,.06);color:var(--text-muted)">No Lab Doc</span>
        <?php endif; ?>
        <?php if (!empty($st['field_test_doc_link'])): ?>
        <a href="<?= htmlspecialchars($st['field_test_doc_link']) ?>" target="_blank" class="btn btn-sm btn-outline-success" style="font-size:11px"><i class="fal fa-external-link me-1"></i>Field Test Doc</a>
        <?php else: ?>
        <span class="doc-status-pill" style="background:rgba(0,0,0,.06);color:var(--text-muted)">No Field Doc</span>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- ═══════════════════════ TAB 3: DESIGN ═══════════════════════════════ -->
<div class="pd-tab-pane" id="tab-design">
  <?php if (empty($drawings)): ?>
  <div class="empty-pd"><div class="empty-pd-ico"><i class="fal fa-drafting-compass"></i></div>No design drawings found for this project</div>
  <?php else: ?>
  <?php foreach ($drawings as $typeName => $typeDrawings): ?>
  <div class="pd-panel">
    <div class="pd-panel-title"><i class="fal fa-drafting-compass me-2"></i><?= htmlspecialchars($typeName) ?></div>
    <?php foreach ($typeDrawings as $d): ?>
    <div class="design-card">
      <span class="design-type-badge"><?= htmlspecialchars($d['design_type_name'] ?? $typeName) ?></span>
      <div style="font-size:14px;font-weight:700"><?= $d['is_in_house'] ? '<i class="fal fa-home me-1"></i>In-House Design' : '<i class="fal fa-tools me-1"></i>External Design' ?></div>
      <div class="design-meta">
        <div class="design-meta-item">
          <span class="design-meta-label">Designer</span>
          <span class="design-meta-val"><?= htmlspecialchars($d['designer_name'] ?? ($d['internal_designer_name'] ?? '—')) ?></span>
        </div>
        <div class="design-meta-item">
          <span class="design-meta-label">Company</span>
          <span class="design-meta-val"><?= htmlspecialchars($d['designer_company'] ?? '—') ?></span>
        </div>
        <div class="design-meta-item">
          <span class="design-meta-label">Version</span>
          <span class="design-meta-val">v<?= htmlspecialchars($d['version'] ?? '1.0') ?></span>
        </div>
        <div class="design-meta-item">
          <span class="design-meta-label">Status</span>
          <span class="design-meta-val"><?= htmlspecialchars($d['status'] ?? '—') ?></span>
        </div>
        <?php if (!empty($d['design_link'])): ?>
        <div class="design-meta-item">
          <span class="design-meta-label">Drawing</span>
          <a href="<?= htmlspecialchars($d['design_link']) ?>" target="_blank" class="design-meta-val" style="color:#3b82f6"><i class="fal fa-external-link me-1"></i>View File</a>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- ═══════════════════════ TAB 4: INITIAL BUDGET ═══════════════════════ -->
<div class="pd-tab-pane" id="tab-budget">
  <?php if (!$budget): ?>
  <div class="empty-pd"><div class="empty-pd-ico"><i class="fal fa-money-bill-wave"></i></div>No initial budget found for this project</div>
  <?php elseif (empty($budgetBreakdown)): ?>
  <div class="empty-pd"><div class="empty-pd-ico"><i class="fal fa-clipboard-list"></i></div>Budget exists but no line items found</div>
  <?php else:
    $grandMat = 0; $grandLab = 0; $grandTotal = 0;
  ?>
  <?php foreach ($budgetBreakdown as $bd): 
    $grandMat   += (float)$bd['total_material'];
    $grandLab   += (float)$bd['total_labor'];
    $grandTotal += (float)$bd['design_total'];
  ?>
  <div class="pd-panel">
    <div class="pd-panel-title">
      <i class="fal fa-box me-1"></i><?= htmlspecialchars($bd['design_type_name'] ?? 'Unknown Type') ?>
      <span style="margin-left:auto;font-size:13px;font-weight:800;color:#3b82f6"><?= pdFmt($bd['design_total']) ?></span>
    </div>
    <table class="bud-table">
      <thead>
        <tr>
          <th>Category</th>
          <th style="text-align:right">Items/Count</th>
          <th style="text-align:right">Cost</th>
          <th style="text-align:right">% of Category</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><i class="fal fa-bricks me-1"></i>Construction Material</td>
          <td style="text-align:right"><?= $bd['item_count'] ?> items</td>
          <td style="text-align:right" class="bud-mat-col"><?= pdFmt($bd['total_material']) ?></td>
          <td style="text-align:right">
            <?= $bd['design_total'] > 0 ? round($bd['total_material'] / $bd['design_total'] * 100, 1) . '%' : '—' ?>
          </td>
        </tr>
        <tr>
          <td><i class="fal fa-user-hard-hat me-1"></i>Labour / Manpower</td>
          <td style="text-align:right"><?= $bd['labor_count'] ?> entries</td>
          <td style="text-align:right" class="bud-lab-col"><?= pdFmt($bd['total_labor']) ?></td>
          <td style="text-align:right">
            <?= $bd['design_total'] > 0 ? round($bd['total_labor'] / $bd['design_total'] * 100, 1) . '%' : '—' ?>
          </td>
        </tr>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="2">Subtotal</td>
          <td style="text-align:right" class="bud-tot-col"><?= pdFmt($bd['design_total']) ?></td>
          <td style="text-align:right">100%</td>
        </tr>
      </tfoot>
    </table>
  </div>
  <?php endforeach; ?>

  <!-- Grand Total -->
  <div class="pd-panel" style="border:2px solid rgba(59,130,246,.3);background:rgba(59,130,246,.03)">
    <div class="pd-panel-title"><i class="fal fa-money-bill-wave me-2" style="color:#3b82f6"></i>Grand Total Budget Summary</div>
    <table class="bud-table">
      <tbody>
        <tr>
          <td style="width:60%"><i class="fal fa-bricks me-1"></i>Total Material Cost (all categories)</td>
          <td style="text-align:right" class="bud-mat-col" style="font-size:15px"><?= pdFmt($grandMat) ?></td>
        </tr>
        <tr>
          <td><i class="fal fa-user-hard-hat me-1"></i>Total Labour Cost (all categories)</td>
          <td style="text-align:right" class="bud-lab-col" style="font-size:15px"><?= pdFmt($grandLab) ?></td>
        </tr>
        <?php if ($budget && ($budget['other_costs'] ?? 0) > 0): ?>
        <tr>
          <td><i class="fal fa-plus-circle me-1"></i>Other Costs</td>
          <td style="text-align:right;font-weight:600;color:var(--text-muted)"><?= pdFmt($budget['other_costs']) ?></td>
        </tr>
        <?php endif; ?>
      </tbody>
      <tfoot>
        <tr style="background:rgba(59,130,246,.06)">
          <td style="font-size:16px;font-weight:800"><i class="fas fa-star me-1" style="color:#f59e0b"></i>Total Project Budget</td>
          <td style="text-align:right;font-size:20px;font-weight:900;color:#3b82f6"><?= pdFmt($budget['total_estimated_cost'] ?? $grandTotal) ?></td>
        </tr>
      </tfoot>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- ═══════════════════════ TAB 5: WORK PLAN (AJAX) ═════════════════════ -->
<div class="pd-tab-pane" id="tab-workplan">
  <div id="wp-content">
    <div class="ajax-loader"><span class="spinner"></span>Loading work plan...</div>
  </div>
</div>



<!-- ═══════════════════════ TAB 8: WORK ORDERS (AJAX) ════════════════ -->
<div class="pd-tab-pane" id="tab-workorders">
  <div id="wo-content">
    <div class="ajax-loader"><span class="spinner"></span>Loading work orders...</div>
  </div>
</div>

<!-- ═══════════════════════ TAB 7: WEEKLY REPORT (AJAX) ═════════════ -->
<div class="pd-tab-pane" id="tab-weekly">
  <div id="wr-content">
    <div class="ajax-loader"><span class="spinner"></span>Loading weekly report data...</div>
  </div>
</div>


<!-- ═══════════════════════ JS ══════════════════════════════════════════ -->
<script>
(function(){
  const base   = "<?= $base ?>";
  const projId = "<?= (int)($project['id'] ?? 0) ?>";
  let wpLoaded = false, wrLoaded = false, woLoaded = false;

  // ── Tab switching ────────────────────────────────────────────────────
  document.querySelectorAll('.pd-tab').forEach(function(btn){
    btn.addEventListener('click', function(){
      document.querySelectorAll('.pd-tab').forEach(function(b){ b.classList.remove('active'); });
      document.querySelectorAll('.pd-tab-pane').forEach(function(p){ p.classList.remove('active'); });
      btn.classList.add('active');
      const pane = document.getElementById('tab-' + btn.dataset.tab);
      if(pane) pane.classList.add('active');

      // Lazy load AJAX tabs
      if(btn.dataset.tab === 'workplan' && !wpLoaded) { wpLoaded=true; loadWorkPlan(); }
      if(btn.dataset.tab === 'weekly'   && !wrLoaded) { wrLoaded=true; loadWeekly(); }
      if(btn.dataset.tab === 'workorders' && !woLoaded) { woLoaded=true; loadWorkOrders(); }
    });
  });

  // ── Tab 5: Work Plan (Monthly Targets & Items) ───────────────────────
  function loadWorkPlan(){
    fetch(base + '/con-ajax-workplan/' + projId + '?t=' + new Date().getTime())
      .then(function(r){ return r.json(); })
      .then(function(res){
        var el = document.getElementById('wp-content');
        if(!res.success || !res.data.length){
          el.innerHTML = '<div class="empty-pd"><div class="empty-pd-ico"><i class="fal fa-calendar-alt"></i></div>No monthly target work plan entries found</div>';
          return;
        }
        var html = '';
        res.data.forEach(function(group){
          html += '<div class="pd-panel mb-3">';
          html += '<div class="pd-panel-title d-flex justify-content-between align-items-center" style="border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 15px;">';
          html += '<div class="d-flex align-items-center gap-2"><i class="fal fa-calendar-alt" style="color:#2563eb;"></i>';
          html += '<strong>' + group.label + '</strong> – <span style="color:#475569;">' + escHtml(group.phase_name) + '</span></div>';
          html += '<div class="d-flex gap-2">';
          if(group.responsible_team && group.responsible_team !== '—') {
            html += '<span style="font-size:11px;background:#f1f5f9;color:#475569;padding:4px 10px;border-radius:6px;font-weight:600;"><i class="fal fa-users"></i> ' + escHtml(group.responsible_team) + '</span>';
          }
          var statusColor = group.planning_status === 'Approved' ? '#10b981' : '#f59e0b';
          var statusBg = group.planning_status === 'Approved' ? 'rgba(16,185,129,.1)' : 'rgba(245,158,11,.1)';
          html += '<span style="font-size:11px;background:'+statusBg+';color:'+statusColor+';padding:4px 10px;border-radius:6px;font-weight:700;">' + escHtml(group.planning_status) + '</span>';
          html += '</div></div>';
          
          html += '<div class="table-responsive"><table class="wp-table"><thead><tr>';
          html += '<th>Work Item Name</th>';
          html += '<th>Type</th>';
          html += '<th style="text-align:right">Monthly Target</th>';
          html += '<th style="text-align:center">W1 Qty</th>';
          html += '<th style="text-align:center">W2 Qty</th>';
          html += '<th style="text-align:center">W3 Qty</th>';
          html += '<th style="text-align:center">W4 Qty</th>';
          html += '<th style="text-align:right">Actual Qty</th>';
          html += '<th style="text-align:right">Progress</th>';
          html += '</tr></thead><tbody>';
          
          group.items.forEach(function(item){
            var targetQty = parseFloat(item.monthly_target_qty || 0);
            var actualQty = parseFloat(item.actual_qty || 0);
            var actualPct = parseFloat(item.actual_progress_pct || 0).toFixed(1) + '%';
            var plannedPct = parseFloat(item.planned_progress_pct || 0).toFixed(1) + '%';
            
            html += '<tr>';
            html += '<td><strong>' + escHtml(item.work_item_name) + '</strong></td>';
            html += '<td><span style="font-size:10.5px;background:rgba(99,102,241,.1);color:#6366f1;padding:2px 8px;border-radius:5px;font-weight:600">' + escHtml(item.work_type_name || item.design_type_name || '—') + '</span></td>';
            html += '<td style="text-align:right;font-weight:700;">' + targetQty.toFixed(1) + ' ' + escHtml(item.unit) + '</td>';
            html += '<td style="text-align:center;color:#64748b;">' + parseFloat(item.week_1_qty || 0).toFixed(1) + '</td>';
            html += '<td style="text-align:center;color:#64748b;">' + parseFloat(item.week_2_qty || 0).toFixed(1) + '</td>';
            html += '<td style="text-align:center;color:#64748b;">' + parseFloat(item.week_3_qty || 0).toFixed(1) + '</td>';
            html += '<td style="text-align:center;color:#64748b;">' + parseFloat(item.week_4_qty || 0).toFixed(1) + '</td>';
            html += '<td style="text-align:right;font-weight:700;color:#10b981;">' + actualQty.toFixed(1) + ' ' + escHtml(item.unit) + '</td>';
            html += '<td style="text-align:right;"><div style="font-weight:700;color:#2563eb;">' + actualPct + '</div><div style="font-size:10px;color:var(--text-muted);">Planned: ' + plannedPct + '</div></td>';
            html += '</tr>';
          });
          
          html += '</tbody></table></div></div>';
        });
        el.innerHTML = html;
      })
      .catch(function(e){ console.error(e); document.getElementById('wp-content').innerHTML = '<div class="empty-pd">Failed to load monthly targets & work plans</div>'; });
  }

  // ── Tab 7: Weekly Report ──────────────────────────────────────────────
  var wrData = [];
  var curMonth = 0, curWeek = 0;

  function loadWeekly(){
    fetch(base + '/con-ajax-weekly/' + projId + '?t=' + new Date().getTime())
      .then(function(r){ return r.json(); })
      .then(function(res){
        var el = document.getElementById('wr-content');
        if(!res.success || !res.data.length){
          el.innerHTML = '<div class="empty-pd"><div class="empty-pd-ico"><i class="fal fa-chart-bar"></i></div>No weekly report data found. Add targets first.</div>';
          return;
        }
        wrData = res.data;
        renderMonthTabs();
      })
      .catch(function(){ document.getElementById('wr-content').innerHTML = '<div class="empty-pd">Failed to load weekly report</div>'; });
  }

  function renderMonthTabs(){
    var el = document.getElementById('wr-content');
    var html = '<div class="wr-month-tabs" id="month-tabs"></div><div id="month-body"></div>';
    el.innerHTML = html;

    var mtEl = document.getElementById('month-tabs');
    wrData.forEach(function(m, i){
      var btn = document.createElement('button');
      btn.className = 'wr-month-btn' + (i===0 ? ' active' : '');
      btn.textContent = m.month_label;
      btn.onclick = function(){
        document.querySelectorAll('.wr-month-btn').forEach(function(b){ b.classList.remove('active'); });
        btn.classList.add('active');
        curMonth = i; curWeek = 0;
        renderWeekTabs(i);
      };
      mtEl.appendChild(btn);
    });

    curMonth = 0; curWeek = 0;
    renderWeekTabs(0);
  }

  function renderWeekTabs(mi){
    var el = document.getElementById('month-body');
    var month = wrData[mi];
    var html = '<div class="wr-week-tabs" id="week-tabs"></div><div id="week-body"></div>';
    el.innerHTML = html;

    var wtEl = document.getElementById('week-tabs');
    month.weeks.forEach(function(w, i){
      var btn = document.createElement('button');
      btn.className = 'wr-week-btn' + (i===0 ? ' active' : '');
      btn.textContent = w.week_label + ' (' + w.date_from + ' – ' + w.date_to + ')';
      btn.onclick = function(){
        document.querySelectorAll('.wr-week-btn').forEach(function(b){ b.classList.remove('active'); });
        btn.classList.add('active');
        curWeek = i;
        renderWeekContent(mi, i);
      };
      wtEl.appendChild(btn);
    });

    renderWeekContent(mi, 0);
  }

  function renderWeekContent(mi, wi){
    var el = document.getElementById('week-body');
    var week = wrData[mi].weeks[wi];
    if(!week){ el.innerHTML = '<div class="empty-pd">No data</div>'; return; }

    var html = '<div class="wr-subtabs">';
    html += '<button class="wr-subtab active" data-sub="report"><i class="fal fa-clipboard-list me-1"></i>Report</button>';
    html += '<button class="wr-subtab" data-sub="progress"><i class="fal fa-chart-line me-1"></i>Weekly Progress</button>';
    html += '<button class="wr-subtab" data-sub="financial"><i class="fal fa-credit-card me-1"></i>Financial Record</button>';
    html += '</div>';
    html += '<div id="wrs-report" class="wr-sub-pane active">' + renderReport(week) + '</div>';
    html += '<div id="wrs-progress" class="wr-sub-pane">' + renderWeekProgress(week) + '</div>';
    html += '<div id="wrs-financial" class="wr-sub-pane">' + renderFinancial(week) + '</div>';
    el.innerHTML = html;

    // sub-tab switching
    document.querySelectorAll('.wr-subtab').forEach(function(btn){
      btn.addEventListener('click', function(){
        document.querySelectorAll('.wr-subtab').forEach(function(b){ b.classList.remove('active'); });
        document.querySelectorAll('.wr-sub-pane').forEach(function(p){ p.classList.remove('active'); });
        btn.classList.add('active');
        var p = document.getElementById('wrs-' + btn.dataset.sub);
        if(p) p.classList.add('active');
      });
    });
  }

  function renderReport(week){
    if(!week.targets || !week.targets.length)
      return '<div class="empty-pd"><div class="empty-pd-ico"><i class="fal fa-clipboard-list"></i></div>No weekly targets defined for this week</div>';
    
    var html = '<div class="pd-panel">';
    html += '<div style="overflow-x:auto">';
    html += '<table class="wp-table"><thead><tr>';
    html += '<th>Work Item</th>';
    html += '<th>Work Type</th>';
    html += '<th style="text-align:right">Planned</th>';
    html += '<th style="text-align:right" title="Day 1">D1</th>';
    html += '<th style="text-align:right" title="Day 2">D2</th>';
    html += '<th style="text-align:right" title="Day 3">D3</th>';
    html += '<th style="text-align:right" title="Day 4">D4</th>';
    html += '<th style="text-align:right" title="Day 5">D5</th>';
    html += '<th style="text-align:right" title="Day 6">D6</th>';
    html += '<th style="text-align:right" title="Day 7">D7</th>';
    html += '<th style="text-align:right">Achieved</th>';
    html += '<th style="text-align:right">Progress %</th>';
    html += '<th>Status</th>';
    html += '<th>Photo Proof</th>';
    html += '</tr></thead><tbody>';
    
    week.targets.forEach(function(t){
      var plannedQty = parseFloat(t.planned_qty || 0);
      var achievedQty = parseFloat(t.achieved_qty || 0);
      var pct = parseFloat(t.percent_complete || 0);
      var pctClr = pct >= 75 ? '#10b981' : (pct >= 40 ? '#f59e0b' : '#ef4444');
      var statusColor = t.status === 'Completed' ? '#10b981' : (t.status === 'In Progress' ? '#3b82f6' : '#64748b');
      var statusBg = t.status === 'Completed' ? 'rgba(16,185,129,.1)' : (t.status === 'In Progress' ? 'rgba(59,130,246,.1)' : 'rgba(100,116,139,.1)');
      
      var d1 = parseFloat(t['1st_day_qty'] || 0);
      var d2 = parseFloat(t['2nd_day_qty'] || 0);
      var d3 = parseFloat(t['3rd_day_qty'] || 0);
      var d4 = parseFloat(t['4th_day_qty'] || 0);
      var d5 = parseFloat(t['5th_day_qty'] || 0);
      var d6 = parseFloat(t['6_th_day_qty'] || 0);
      var d7 = parseFloat(t['7th_day_qty'] || 0);
      
      // Skip showing in the weekly report if it has no planned qty and no actual achieved progress this week
      if (plannedQty <= 0 && achievedQty <= 0 && d1 <= 0 && d2 <= 0 && d3 <= 0 && d4 <= 0 && d5 <= 0 && d6 <= 0 && d7 <= 0) {
        return;
      }
      
      var photoHtml = '—';
      if(t.photo_path) {
        photoHtml = '<a href="' + base + '/' + t.photo_path + '" target="_blank" style="color:#2563eb; font-weight:600; text-decoration:none;"><i class="fal fa-image me-1"></i>View Photo</a>';
      }

      html += '<tr>';
      html += '<td><strong>' + escHtml(t.work_item_name) + '</strong></td>';
      html += '<td><span style="font-size:10px; background:rgba(99,102,241,.08); color:#6366f1; padding:2px 6px; border-radius:4px; font-weight:600;">' + escHtml(t.work_type_name || '—') + '</span></td>';
      html += '<td style="text-align:right; font-weight:600;">' + plannedQty.toFixed(1) + ' ' + escHtml(t.unit || '') + '</td>';
      
      html += '<td style="text-align:right; color:#64748b;">' + (d1 > 0 ? d1.toFixed(1) : '—') + '</td>';
      html += '<td style="text-align:right; color:#64748b;">' + (d2 > 0 ? d2.toFixed(1) : '—') + '</td>';
      html += '<td style="text-align:right; color:#64748b;">' + (d3 > 0 ? d3.toFixed(1) : '—') + '</td>';
      html += '<td style="text-align:right; color:#64748b;">' + (d4 > 0 ? d4.toFixed(1) : '—') + '</td>';
      html += '<td style="text-align:right; color:#64748b;">' + (d5 > 0 ? d5.toFixed(1) : '—') + '</td>';
      html += '<td style="text-align:right; color:#64748b;">' + (d6 > 0 ? d6.toFixed(1) : '—') + '</td>';
      html += '<td style="text-align:right; color:#64748b;">' + (d7 > 0 ? d7.toFixed(1) : '—') + '</td>';
      
      html += '<td style="text-align:right; font-weight:700; color:#10b981;">' + achievedQty.toFixed(1) + ' ' + escHtml(t.unit || '') + '</td>';
      html += '<td style="text-align:right; font-weight:700; color:' + pctClr + '">' + pct.toFixed(1) + '%</td>';
      html += '<td><span class="doc-status-pill" style="background:'+statusBg+'; color:'+statusColor+'; font-size:10px; font-weight:700;">' + escHtml(t.status) + '</span></td>';
      html += '<td>' + photoHtml + '</td>';
      html += '</tr>';
    });
    
    html += '</tbody></table></div></div>';
    return html;
  }

  function renderWeekProgress(week){
    if(!week.dprs || !week.dprs.length)
      return '<div class="empty-pd"><div class="empty-pd-ico"><i class="fal fa-chart-line"></i></div>No daily progress reports for this week</div>';
    
    var html = '';
    week.dprs.forEach(function(d){
      html += '<div class="pd-panel mb-3" style="border-left: 4px solid #10b981;">';
      
      // Header row
      html += '<div class="d-flex justify-content-between align-items-start mb-2 flex-wrap" style="gap:10px; border-bottom:1px solid rgba(0,0,0,.05); padding-bottom:8px;">';
      html += '<div>';
      html += '<h5 style="margin:0; font-weight:700; color:#1e293b;"><i class="fal fa-calendar-day me-2" style="color:#10b981;"></i>' + formatDate(d.log_date) + '</h5>';
      html += '<span style="font-size:11px; color:var(--text-muted);">Logged by: <strong>' + escHtml(d.creator_name || '—') + '</strong></span>';
      html += '</div>';
      
      // Badges (Safety, QC, Weather)
      html += '<div class="d-flex gap-2 align-items-center">';
      if(d.weather) {
        var tempStr = d.temperature_celsius ? ' (' + d.temperature_celsius + '°C)' : '';
        html += '<span class="doc-status-pill" style="background:rgba(59,130,246,.08); color:#3b82f6; font-size:10px;"><i class="fal fa-cloud-sun me-1"></i>' + escHtml(d.weather) + tempStr + '</span>';
      }
      if(d.safety_ok == 1) {
        html += '<span class="doc-status-pill" style="background:rgba(16,185,129,.1); color:#10b981; font-size:10px;"><i class="fal fa-shield-check me-1"></i>Safety OK</span>';
      } else {
        html += '<span class="doc-status-pill" style="background:rgba(239,68,68,.1); color:#ef4444; font-size:10px;"><i class="fal fa-exclamation-triangle me-1"></i>Safety Issue</span>';
      }
      if(d.qc_checked == 1) {
        html += '<span class="doc-status-pill" style="background:rgba(139,92,246,.1); color:#8b5cf6; font-size:10px;"><i class="fal fa-badge-check me-1"></i>QC Passed</span>';
      }
      html += '</div>';
      html += '</div>'; // end header row

      // Resources info block
      html += '<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap:10px; margin-bottom:12px; background:rgba(0,0,0,.02); padding:10px; border-radius:8px;">';
      html += '<div><span style="font-size:10px; color:var(--text-muted); text-transform:uppercase; font-weight:600;">Labour Count</span><div style="font-size:14px; font-weight:700; color:#475569;">' + (d.labour_count || 0) + '</div></div>';
      html += '<div><span style="font-size:10px; color:var(--text-muted); text-transform:uppercase; font-weight:600;">Cement Used</span><div style="font-size:14px; font-weight:700; color:#b45309;">' + (d.cement_bags || 0) + ' bags</div></div>';
      if(d.concrete_cft > 0) {
        html += '<div><span style="font-size:10px; color:var(--text-muted); text-transform:uppercase; font-weight:600;">Concrete Poured (CFT)</span><div style="font-size:14px; font-weight:700; color:#2563eb;">' + parseFloat(d.concrete_cft).toFixed(1) + ' CFT</div></div>';
      }
      if(d.concrete_mt > 0) {
        html += '<div><span style="font-size:10px; color:var(--text-muted); text-transform:uppercase; font-weight:600;">Concrete Poured (MT)</span><div style="font-size:14px; font-weight:700; color:#4f46e5;">' + parseFloat(d.concrete_mt).toFixed(2) + ' MT</div></div>';
      }
      if(d.equipment_active > 0) {
        html += '<div><span style="font-size:10px; color:var(--text-muted); text-transform:uppercase; font-weight:600;">Equipments</span><div style="font-size:14px; font-weight:700; color:#0d9488;">' + d.equipment_active + ' Active</div></div>';
      }
      html += '</div>';

      // Items table
      if(d.items && d.items.length > 0) {
        html += '<div style="font-weight:700; font-size:11px; text-transform:uppercase; color:#64748b; margin-bottom:6px;"><i class="fal fa-list-check me-1"></i>Work Items Logged:</div>';
        html += '<table class="wp-table"><thead><tr>';
        html += '<th>Work Item</th><th>Type</th><th style="text-align:right">Qty Achieved</th><th>Responsible Team</th><th>Status</th><th>Remarks</th>';
        html += '</tr></thead><tbody>';
        d.items.forEach(function(item){
          var statusColor = item.item_status === 'Completed' ? '#10b981' : (item.item_status === 'In Progress' ? '#3b82f6' : '#64748b');
          var statusBg = item.item_status === 'Completed' ? 'rgba(16,185,129,.1)' : (item.item_status === 'In Progress' ? 'rgba(59,130,246,.1)' : 'rgba(100,116,139,.1)');
          
          html += '<tr>';
          html += '<td><strong>' + escHtml(item.work_item_name) + '</strong></td>';
          html += '<td><span style="font-size:10px; background:rgba(99,102,241,.08); color:#6366f1; padding:2px 6px; border-radius:4px; font-weight:600;">' + escHtml(item.work_type_name || '—') + '</span></td>';
          html += '<td style="text-align:right; font-weight:700;">' + parseFloat(item.qty_done_today).toFixed(1) + ' ' + escHtml(item.unit || '') + '</td>';
          html += '<td>' + escHtml(item.responsible_team || '—') + '</td>';
          html += '<td><span class="doc-status-pill" style="background:'+statusBg+'; color:'+statusColor+'; font-size:10px; font-weight:700;">' + escHtml(item.item_status) + '</span></td>';
          html += '<td style="color:var(--text-muted); font-size:11.5px;">' + escHtml(item.remarks || '—') + '</td>';
          html += '</tr>';
        });
        html += '</tbody></table>';
      } else {
        html += '<div style="font-size:12px; color:var(--text-muted); font-style:italic;"><i class="fal fa-info-circle me-1"></i>No specific work items logged for this day.</div>';
      }

      // General Remarks
      if(d.site_remarks) {
        html += '<div style="margin-top:10px; padding:8px 12px; background:rgba(245,158,11,.04); border-left:3px solid #f59e0b; border-radius:4px; font-size:12px; color:#78350f;">';
        html += '<strong>Remarks:</strong> ' + escHtml(d.site_remarks);
        html += '</div>';
      }

      html += '</div>'; // end panel
    });

    return html;
  }



  function renderFinancial(week){
    if(!week.transactions || !week.transactions.length)
      return '<div class="empty-pd"><div class="empty-pd-ico"><i class="fal fa-credit-card"></i></div>No financial transactions for this week</div>';
    var total = week.transactions.reduce(function(s,t){ return s + parseFloat(t.amount_paid||t.amount||0); }, 0);
    var html = '<div class="pd-panel">';
    html += '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">';
    html += '<span style="font-size:12px;color:var(--text-muted)">'+week.transactions.length+' transaction(s)</span>';
    html += '<span style="font-size:13px">Total Paid: <strong style="color:#3b82f6">'+fmtMoney(total)+'</strong></span>';
    html += '</div>';
    html += '<table class="wp-table"><thead><tr>';
    html += '<th>Transaction Date</th><th>Type</th><th>Payment Type</th><th>Method</th>';
    html += '<th style="text-align:right">Bill Amount</th><th style="text-align:right">Paid Amount</th><th>Bill Status</th><th>Processor</th>';
    html += '</tr></thead><tbody>';
    week.transactions.forEach(function(t){
      // Date
      var txDate = t.payment_date || t.transaction_date_str || '';
      if(!txDate && t.transaction_date) txDate = t.transaction_date.substring(0, 10);
      var txAmt     = parseFloat(t.amount_paid || t.amount || 0);
      var billAmt   = parseFloat(t.cbr_bill_amount || 0);
      var dueAmt    = parseFloat(t.cbr_due_amount || 0);
      var billSt    = t.cbr_status || '';
      var billStClr = billSt === 'fully_paid' ? '#10b981' : (billSt === 'partial' ? '#f59e0b' : '#ef4444');
      var billStLbl = billSt === 'fully_paid' ? '✓ Fully Paid' : (billSt === 'partial_paid' ? '⚡ Partial' : (billSt ? billSt.replace(/_/g,' ').toUpperCase() : '—'));

      html += '<tr>';
      html += '<td><strong>'+(txDate ? formatDate(txDate) : '—')+'</strong></td>';
      html += '<td><span style="font-size:11px;background:rgba(59,130,246,.1);color:#3b82f6;padding:2px 8px;border-radius:5px;font-weight:600">'+escHtml(t.ref_type||'—')+'</span></td>';
      html += '<td><span style="font-size:11px;background:rgba(245,158,11,.1);color:#f59e0b;padding:2px 8px;border-radius:5px;font-weight:600">'+escHtml(t.payment_type||t.cbr_bill_type||'—')+'</span></td>';
      html += '<td>'+escHtml(t.payment_method||'—')+'</td>';
      html += '<td style="text-align:right;color:var(--text-muted)">'+(billAmt>0 ? fmtMoney(billAmt) : '—')+'</td>';
      html += '<td style="text-align:right;font-weight:700;color:#3b82f6">'+fmtMoney(txAmt)+'</td>';
      html += '<td>';
      if(billSt) {
        html += '<span style="font-size:10px;font-weight:700;color:'+billStClr+'">'+billStLbl+'</span>';
        if(dueAmt>0) html += '<br><span style="font-size:10px;color:#ef4444">Due: '+fmtMoney(dueAmt)+'</span>';
      } else html += '—';
      html += '</td>';
      html += '<td>'+escHtml(t.processor_name||'—')+'</td>';
      html += '</tr>';
    });
    html += '</tbody></table></div>';
    return html;
  }


  // ── Tab 8: Work Orders ──────────────────────────────────────────────
  function loadWorkOrders(){
    fetch(base + '/con-ajax-workorders/' + projId + '?t=' + new Date().getTime())
      .then(function(r){ return r.json(); })
      .then(function(res){
        var el = document.getElementById('wo-content');
        if(!res.success || !res.data.length){
          el.innerHTML = '<div class="empty-pd"><div class="empty-pd-ico"><i class="fal fa-file-signature"></i></div>No Work Orders found for this project.</div>';
          return;
        }
        var html = '<div class="pd-panel"><table class="wp-table"><thead><tr>';
        html += '<th>ID & Date</th><th>Work Description</th><th>Contractor Details</th><th>Status</th><th style="text-align:right">Amount</th>';
        html += '</tr></thead><tbody>';
        res.data.forEach(function(w){
          html += '<tr>';
          html += '<td><strong>WO-' + w.id + '</strong><br><span style="font-size:11px;color:var(--text-muted)">' + formatDate(w.created_at) + '</span></td>';
          html += '<td><strong style="color:#1e293b">' + escHtml(w.title||'—') + '</strong><br><span style="font-size:12px;color:#64748b">' + escHtml(w.scope_of_work||'No scope specified') + '</span></td>';
          html += '<td><div style="font-weight:600;color:#3b82f6">' + escHtml(w.contractor_name||'—') + '</div><div style="font-size:11px;color:#64748b"><i class="fal fa-phone-alt me-1"></i>' + escHtml(w.contractor_phone||'N/A') + '</div></td>';
          html += '<td><span class="apv-yes" style="padding:4px 8px;border-radius:10px;font-size:10px;text-transform:uppercase">' + escHtml(w.status||'draft') + '</span></td>';
          html += '<td style="text-align:right;font-weight:700;color:#059669">' + fmtMoney(w.total_amount||0) + '</td>';
          html += '</tr>';
        });
        html += '</tbody></table></div>';
        el.innerHTML = html;
      })
      .catch(function(e){ console.error(e); document.getElementById('wo-content').innerHTML = '<div class="empty-pd">Failed to load work orders</div>'; });
  }

  // ── Utilities ─────────────────────────────────────────────────────────
  function escHtml(str){
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
  function fmtMoney(n){
    n = parseFloat(n)||0;
    if(n>=10000000) return '৳'+(n/10000000).toFixed(2)+' Cr';
    if(n>=100000)   return '৳'+(n/100000).toFixed(2)+' L';
    if(n>=1000)     return '৳'+(n/1000).toFixed(1)+'K';
    return '৳'+n.toFixed(0);
  }
  var months=['','January','February','March','April','May','June','July','August','September','October','November','December'];
  function monthName(m){ return months[parseInt(m)]||'Month'; }
  function formatDate(dStr){
    if(!dStr) return '—';
    var d = new Date(dStr);
    if(isNaN(d)) return dStr;
    return d.toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'numeric'});
  }
})();
</script>

</div></div>
