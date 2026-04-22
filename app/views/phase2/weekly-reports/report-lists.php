<?php
$addPage = true;
$base = $GLOBALS['base_url'];
$reports = $reports ?? [];
?>
<style>
:root { --wr-green:#198754; --wr-dark:#0f5132; --wr-light:#20a86a; }
.wr-hero { background:linear-gradient(135deg,#0f5132 0%,#198754 55%,#20a86a 100%);
  border-radius:18px;padding:24px 30px;margin-bottom:20px;color:#fff;position:relative;overflow:hidden; }
.wr-hero::before { content:'';position:absolute;width:220px;height:220px;border-radius:50%;
  background:rgba(255,255,255,.05);top:-70px;right:-50px; }
.wr-badge { display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.13);
  border:1px solid rgba(255,255,255,.22);padding:3px 14px;border-radius:20px;
  font-size:10.5px;font-weight:700;letter-spacing:1.3px;text-transform:uppercase;margin-bottom:10px; }
.wr-panel { border-radius:14px;border:1px solid rgba(0,0,0,.08);background:var(--card-bg,#fff);overflow:hidden; }
.wr-panel-head { padding:16px 20px;border-bottom:1px solid rgba(0,0,0,.07);display:flex;align-items:center;justify-content:space-between; }
.wr-panel-title { font-size:11px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase; }
.wr-table { width:100%;border-collapse:collapse; }
.wr-table th { font-size:10px;letter-spacing:1.2px;text-transform:uppercase;font-weight:600;
  padding:10px 14px;border-bottom:2px solid rgba(0,0,0,.07);text-align:left;background:rgba(0,0,0,.02); }
.wr-table td { padding:12px 14px;border-bottom:1px solid rgba(0,0,0,.05);font-size:12.5px;vertical-align:middle; }
.wr-table tr:last-child td { border-bottom:none; }
.wr-pill { font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;display:inline-flex;align-items:center;gap:4px; }
.wr-btn-green { background:linear-gradient(135deg,#0f5132,#198754);color:#fff;border:none;
  border-radius:9px;padding:8px 18px;font-size:12px;font-weight:700;text-decoration:none;
  display:inline-flex;align-items:center;gap:7px;transition:opacity .15s; }
.wr-btn-green:hover { opacity:.88;color:#fff;text-decoration:none; }
.empty-state { text-align:center;padding:40px;color:var(--text-muted); }
.empty-state i { font-size:40px;opacity:.2;display:block;margin-bottom:10px; }
</style>

<!-- Hero -->
<div class="wr-hero">
  <div style="position:relative;z-index:2">
    <div class="wr-badge"><i class="fal fa-calendar-week"></i> Weekly Reports</div>
    <h4 style="font-size:22px;font-weight:900;margin:0 0 4px">Weekly Progress Reports</h4>
    <p style="font-size:12px;color:rgba(255,255,255,.7);margin:0">Project → Working Plan → Target → Week No</p>
    <div style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap">
      <a href="<?= $base ?>/con-weekly-report-create" class="wr-btn-green">
        <i class="fal fa-plus"></i> New Weekly Report
      </a>
    </div>
  </div>
</div>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" style="margin-bottom:16px">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $base ?>/con-dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Weekly Reports</li>
  </ol>
</nav>

<!-- Table Panel -->
<div class="wr-panel">
  <div class="wr-panel-head">
    <span class="wr-panel-title" style="color:var(--wr-green)">
      <i class="fal fa-list me-2"></i>All Weekly Reports
    </span>
    <span style="font-size:11px;color:var(--text-muted)"><?= count($reports) ?> records</span>
  </div>

  <?php if (empty($reports)): ?>
    <div class="empty-state">
      <i class="fal fa-calendar-week"></i>
      No weekly reports found.<br>
      <a href="<?= $base ?>/con-weekly-report-create" class="wr-btn-green mt-3 d-inline-flex">
        <i class="fal fa-plus"></i> Create First Report
      </a>
    </div>
  <?php else: ?>
  <div style="overflow-x:auto">
    <table class="wr-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Project</th>
          <th>Working Plan</th>
          <th>Target</th>
          <th>Week No</th>
          <th>Year / Month</th>
          <th>Progress %</th>
          <th>Engineer</th>
          <th>Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($reports as $i => $r):
          $pct = (float)($r['progress_percentage'] ?? 0);
          $pctColor = $pct >= 80 ? '#198754' : ($pct >= 50 ? '#f59e0b' : '#ef4444');
        ?>
        <tr>
          <td style="font-size:11px;color:var(--text-muted)"><?= $i + 1 ?></td>
          <td style="font-weight:700"><?= htmlspecialchars($r['project_name'] ?? '—') ?></td>
          <td style="font-size:11.5px"><?= htmlspecialchars(mb_strimwidth($r['work_plan_title'] ?? '—', 0, 35, '…')) ?></td>
          <td style="font-size:11.5px"><?= htmlspecialchars($r['target_title'] ?? '—') ?></td>
          <td><span class="wr-pill" style="background:rgba(25,135,84,.1);color:#198754">
            Week <?= (int)($r['week_no'] ?? 0) ?>
          </span></td>
          <td style="font-size:11.5px"><?= $r['target_year'] ?? '—' ?> / <?= $r['target_month'] ?? '—' ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:8px">
              <div style="flex:1;height:7px;border-radius:4px;background:rgba(0,0,0,.07);overflow:hidden;min-width:60px">
                <div style="width:<?= $pct ?>%;height:100%;border-radius:4px;background:<?= $pctColor ?>"></div>
              </div>
              <span style="font-size:11.5px;font-weight:700;color:<?= $pctColor ?>"><?= number_format($pct, 1) ?>%</span>
            </div>
          </td>
          <td style="font-size:11.5px"><?= htmlspecialchars($r['engineer_name'] ?? '—') ?></td>
          <td style="font-size:11px;color:var(--text-muted)"><?= $r['report_date'] ?? $r['created_at'] ?? '—' ?></td>
          <td>
            <a href="<?= $base ?>/con-weekly-report/<?= $r['id'] ?>"
              style="color:#198754;font-size:11px;font-weight:700;text-decoration:none">
              View <i class="fal fa-arrow-right ms-1"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<div style="height:30px"></div>
