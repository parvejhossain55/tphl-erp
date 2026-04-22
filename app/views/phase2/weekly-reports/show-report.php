<?php
$addPage = true;
$base = $GLOBALS['base_url'];
$r = $report ?? [];
$pct = (float)($r['progress_percentage'] ?? 0);
$pctColor = $pct >= 80 ? '#198754' : ($pct >= 50 ? '#f59e0b' : '#ef4444');
?>
<style>
:root { --wr-green:#198754; --wr-dark:#0f5132; }
.wr-hero { background:linear-gradient(135deg,#0f5132 0%,#198754 55%,#20a86a 100%);
  border-radius:18px;padding:24px 30px;margin-bottom:20px;color:#fff;position:relative;overflow:hidden; }
.wr-hero::before { content:'';position:absolute;width:200px;height:200px;border-radius:50%;
  background:rgba(255,255,255,.05);top:-60px;right:-40px; }
.wr-badge { display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.13);
  border:1px solid rgba(255,255,255,.22);padding:3px 14px;border-radius:20px;
  font-size:10.5px;font-weight:700;letter-spacing:1.3px;text-transform:uppercase;margin-bottom:10px; }
.wr-card { border-radius:16px;border:1px solid rgba(0,0,0,.08);background:var(--card-bg,#fff);padding:24px;margin-bottom:20px; }
.wr-sec { font-size:10.5px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;
  color:var(--wr-green);margin-bottom:14px;padding-bottom:8px;border-bottom:2px solid rgba(25,135,84,.12);
  display:flex;align-items:center;gap:7px; }
.wr-info-row { display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:0; }
.wr-info-item { background:rgba(25,135,84,.04);border:1px solid rgba(25,135,84,.1);
  border-radius:12px;padding:14px 16px; }
.wr-info-lbl { font-size:10px;font-weight:700;letter-spacing:1.1px;text-transform:uppercase;
  color:var(--wr-green);margin-bottom:4px; }
.wr-info-val { font-size:14px;font-weight:800;color:var(--text, #222); }
</style>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" style="margin-bottom:14px">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $base ?>/con-dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="<?= $base ?>/con-weekly-reports">Weekly Reports</a></li>
    <li class="breadcrumb-item active">Report #<?= $r['id'] ?? '—' ?></li>
  </ol>
</nav>

<!-- Hero -->
<div class="wr-hero">
  <div style="position:relative;z-index:2;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
    <div>
      <div class="wr-badge"><i class="fal fa-calendar-week"></i> Weekly Report #<?= $r['id'] ?? '—' ?></div>
      <h4 style="font-size:22px;font-weight:900;margin:0 0 4px"><?= htmlspecialchars($r['project_name'] ?? 'Unknown Project') ?></h4>
      <p style="font-size:12px;color:rgba(255,255,255,.7);margin:0">
        <?= htmlspecialchars($r['work_plan_title'] ?? '—') ?>
        &nbsp;·&nbsp; <?= $r['target_year'] ?? '—' ?>/<?= str_pad($r['target_month'] ?? '', 2, '0', STR_PAD_LEFT) ?>
        &nbsp;·&nbsp; Week <?= $r['week_no'] ?? '—' ?>
      </p>
    </div>
    <!-- Progress Ring -->
    <div style="background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);
      border-radius:14px;padding:16px 22px;text-align:center;min-width:130px">
      <div style="font-size:10px;font-weight:700;letter-spacing:1.1px;text-transform:uppercase;
        color:rgba(255,255,255,.7);margin-bottom:4px">Progress</div>
      <div style="font-size:30px;font-weight:900"><?= number_format($pct, 1) ?>%</div>
      <div style="height:6px;border-radius:4px;background:rgba(255,255,255,.2);margin-top:8px;overflow:hidden">
        <div style="width:<?= min($pct,100) ?>%;height:100%;border-radius:4px;background:#fff"></div>
      </div>
    </div>
  </div>
</div>

<!-- KPI Row -->
<div class="wr-info-row mb-4">
  <div class="wr-info-item">
    <div class="wr-info-lbl">Target</div>
    <div class="wr-info-val"><?= htmlspecialchars($r['target_title'] ?? '—') ?></div>
  </div>
  <div class="wr-info-item">
    <div class="wr-info-lbl">Week No</div>
    <div class="wr-info-val">Week <?= (int)($r['week_no'] ?? 0) ?></div>
  </div>
  <div class="wr-info-item">
    <div class="wr-info-lbl">Year / Month</div>
    <div class="wr-info-val"><?= $r['target_year'] ?? '—' ?> / <?= $r['target_month'] ?? '—' ?></div>
  </div>
  <div class="wr-info-item">
    <div class="wr-info-lbl">Manpower</div>
    <div class="wr-info-val"><?= !empty($r['manpower_count']) ? number_format($r['manpower_count']) . ' persons' : '—' ?></div>
  </div>
  <div class="wr-info-item">
    <div class="wr-info-lbl">Report Date</div>
    <div class="wr-info-val"><?= $r['report_date'] ?? '—' ?></div>
  </div>
  <div class="wr-info-item">
    <div class="wr-info-lbl">Site Engineer</div>
    <div class="wr-info-val"><?= htmlspecialchars($r['engineer_name'] ?? '—') ?></div>
  </div>
</div>

<div class="row">
  <!-- Work Done Notes -->
  <div class="col-lg-<?= !empty($r['progress_image']) ? '8' : '12' ?>">
    <div class="wr-card">
      <div class="wr-sec"><i class="fal fa-clipboard-list"></i> Work Done This Week</div>
      <div style="white-space:pre-line;font-size:13.5px;line-height:1.7;color:var(--text,#222)">
        <?= nl2br(htmlspecialchars($r['work_done'] ?? 'No details recorded.')) ?>
      </div>

      <?php if (!empty($r['weather_condition'])): ?>
      <hr style="border-color:rgba(0,0,0,.07);margin:16px 0">
      <div class="wr-sec"><i class="fal fa-cloud-sun"></i> Weather / Additional Notes</div>
      <p style="font-size:13px"><?= htmlspecialchars($r['weather_condition']) ?></p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Progress Photo -->
  <?php if (!empty($r['progress_image'])): ?>
  <div class="col-lg-4">
    <div class="wr-card">
      <div class="wr-sec"><i class="fal fa-image"></i> Progress Photo</div>
      <img src="<?= $GLOBALS['base_url'] ?>/<?= htmlspecialchars($r['progress_image']) ?>"
        alt="Progress Photo" style="width:100%;border-radius:12px;border:1px solid rgba(0,0,0,.08)">
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Actions -->
<div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:30px">
  <a href="<?= $base ?>/con-weekly-reports"
    style="border:1.5px solid rgba(0,0,0,.12);border-radius:10px;padding:9px 22px;
      font-size:12px;font-weight:700;color:var(--text-muted);text-decoration:none">
    <i class="fal fa-arrow-left me-1"></i> Back to List
  </a>
  <a href="<?= $base ?>/con-weekly-report-create"
    style="background:linear-gradient(135deg,#0f5132,#198754);color:#fff;border:none;
      border-radius:10px;padding:9px 22px;font-size:12px;font-weight:700;text-decoration:none">
    <i class="fal fa-plus me-1"></i> New Report
  </a>
</div>
