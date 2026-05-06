<?php
$base_url   = $GLOBALS['base_url'];
$t          = $target ?? [];
$canApprove      = $canApprove      ?? false;
$alreadyApproved = $alreadyApproved ?? false;
$approverRole    = $approverRole    ?? null;  // 'manager' for PM (role 15)
$creatorInfo     = $creatorInfo     ?? [];
$currentRoleId   = $currentRoleId   ?? 0;

$months = [
  '',
  'January',
  'February',
  'March',
  'April',
  'May',
  'June',
  'July',
  'August',
  'September',
  'October',
  'November',
  'December'
];

// Approval state
$pmApproved  = !empty($t['approved_by_manager']);
$fullyApproved = $pmApproved;

$overallStatus = $fullyApproved ? 'Approved' : 'Pending';
$statusMap = [
  'Approved'  => ['bg' => 'rgba(16,185,129,.2)', 'text' => '#059669', 'icon' => 'fa-check-circle'],
  'In Review' => ['bg' => 'rgba(59,130,246,.2)', 'text' => '#2563eb', 'icon' => 'fa-hourglass-half'],
  'Pending'   => ['bg' => 'rgba(245,158,11,.2)', 'text' => '#d97706', 'icon' => 'fa-clock'],
];
$sc = $statusMap[$overallStatus];
?>
<style>
  :root {
    --tgt-primary: #198754;
    --tgt-amber: #f59e0b;
    --tgt-blue: #3b82f6;
    --tgt-green: #10b981;
    --tgt-red: #ef4444;
    --tgt-purple: #8b5cf6;
    --tgt-indigo: #6366f1;
  }

  .tgt-hero {
    background: #059669;
    border-radius: 18px;
    padding: 26px 30px;
    margin-bottom: 22px;
    position: relative;
    overflow: hidden;
  }

  .tgt-hero::before {
    content: '';
    position: absolute;
    width: 260px;
    height: 260px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .04);
    top: -80px;
    right: -60px;
  }

  .tgt-hero::after {
    content: '';
    position: absolute;
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .03);
    bottom: -40px;
    left: -30px;
  }

  .tgt-hero-inner {
    position: relative;
    z-index: 2;
  }

  .tgt-badge-top {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(255, 255, 255, .12);
    border: 1px solid rgba(255, 255, 255, .2);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: 1.4px;
    text-transform: uppercase;
    color: #fff;
    margin-bottom: 12px;
  }

  .tgt-btn-w {
    background: rgba(255, 255, 255, .14);
    border: 1px solid rgba(255, 255, 255, .28);
    color: #fff;
    border-radius: 10px;
    padding: 7px 18px;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: background .18s;
  }

  .tgt-btn-w:hover {
    background: rgba(255, 255, 255, .25);
    color: #fff;
  }

  /* Layout */
  .tgt-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr 1fr;
    gap: 14px;
    margin-bottom: 22px;
  }

  @media(max-width:992px) {
    .tgt-grid {
      grid-template-columns: 1fr 1fr;
    }
  }

  .tgt-kpi {
    border-radius: 14px;
    padding: 16px 18px;
    border: 1px solid rgba(0, 0, 0, .08);
    background: var(--card-bg, #fff);
    position: relative;
    overflow: hidden;
  }

  .tgt-kpi-accent {
    position: absolute;
    left: 0;
    top: 0;
    width: 4px;
    height: 100%;
    border-radius: 14px 0 0 14px;
  }

  .tgt-kpi-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    margin-bottom: 10px;
  }

  .tgt-kpi-val {
    font-size: 22px;
    font-weight: 900;
    line-height: 1;
    margin-bottom: 2px;
  }

  .tgt-kpi-lbl {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: var(--text-muted);
  }

  .tgt-panel  card__wrapper{
    border-radius: 14px;
    padding: 22px;
    border: 1px solid rgba(0, 0, 0, .08);
    /* background: var(--card-bg, #fff); */
    margin-bottom: 18px;
  }

  .tgt-panel-title {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .tgt-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
  }

  /* Approval steps */
  .apv-step {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    border-radius: 12px;
    border: 1px solid rgba(0, 0, 0, .07);
    margin-bottom: 10px;
  }

  .apv-step:last-child {
    margin-bottom: 0;
  }

  .apv-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
  }

  /* Progress bar */
  .tgt-prog-track {
    height: 10px;
    border-radius: 6px;
    background: rgba(0, 0, 0, .07);
    overflow: hidden;
  }

  .tgt-prog-fill {
    height: 100%;
    border-radius: 6px;
    transition: width .5s ease;
  }

  .empty-note {
    text-align: center;
    padding: 20px;
    color: var(--text-muted);
    font-size: 12px;
  }
</style>

<!-- ══════════ HERO ══════════ -->
<div class="tgt-hero">
  <div class="tgt-hero-inner">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:14px">
      <div>
        <div class="tgt-badge-top"><i class="fal fa-bullseye"></i>Site Target</div>
        <h4 style="font-size:22px;font-weight:900;color:#fff;margin:0 0 5px">
          <?= htmlspecialchars($t['target_title'] ?? 'N/A') ?>
        </h4>
        <p style="font-size:12.5px;color:rgba(255,255,255,.65);margin:0">
          <i class="fal fa-building me-1"></i><?= htmlspecialchars($t['project_name'] ?? '—') ?>
          &nbsp;·&nbsp;
          <i class="fal fa-calendar me-1"></i>
          <?= $t['year'] ?? '' ?> / <?= $months[(int)($t['month'] ?? 0)] ?? '' ?>
          <?= $t['week_number'] ? ' — Week ' . $t['week_number'] : '' ?>
        </p>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px">
          <a href="<?= $base_url ?>/con-target-edit/<?= $t['id'] ?>" class="tgt-btn-w">
            <i class="fal fa-pencil"></i>Edit
          </a>
          <a href="<?= $base_url ?>/con-targets" class="tgt-btn-w">
            <i class="fal fa-arrow-left"></i>Back
          </a>
          <a href="<?= $base_url ?>/con-boq-create?target_id=<?= $t['id'] ?>" class="tgt-btn-w" style="border:none">
            <i class="fal fa-plus-circle"></i>Create BOM
          </a>
        </div>
      </div>
      <div style="text-align:right">
        <div style="display:inline-flex;align-items:center;gap:7px;font-size:13px;font-weight:700;
          padding:8px 18px;border-radius:20px;background:<?= $sc['bg'] ?>;color:#fff">
          <i class="fal <?= $sc['icon'] ?>"></i><?= $overallStatus ?>
        </div>
        <div style="font-size:11px;color:rgba(255,255,255,.55);margin-top:8px">
          <?= $fullyApproved ? 'Approved by Project Manager' : 'Awaiting PM Approval' ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ══════════ KPI GRID ══════════ -->
<div class="tgt-grid">
  <div class="tgt-kpi">
    <div class="tgt-kpi-accent" style="background:var(--tgt-blue)"></div>
    <div class="tgt-kpi-icon" style="background:rgba(59,130,246,.12);color:var(--tgt-blue)"><i class="fal fa-percent"></i></div>
    <div class="tgt-kpi-lbl">Target %</div>
    <div class="tgt-kpi-val" style="color:var(--tgt-blue)"><?= number_format($t['target_percentage'] ?? 0, 1) ?>%</div>
  </div>
  <div class="tgt-kpi">
    <div class="tgt-kpi-accent" style="background:var(--tgt-green)"></div>
    <div class="tgt-kpi-icon" style="background:rgba(16,185,129,.12);color:var(--tgt-green)"><i class="fal fa-chart-line"></i></div>
    <div class="tgt-kpi-lbl">Gain %</div>
    <div class="tgt-kpi-val" style="color:var(--tgt-green)"><?= number_format($t['gain_percentage'] ?? 0, 1) ?>%</div>
  </div>
  <div class="tgt-kpi">
    <div class="tgt-kpi-accent" style="background:var(--tgt-purple)"></div>
    <div class="tgt-kpi-icon" style="background:rgba(139,92,246,.12);color:var(--tgt-purple)"><i class="fal fa-shield-check"></i></div>
    <div class="tgt-kpi-lbl">Approvals</div>
    <div class="tgt-kpi-val" style="color:var(--tgt-purple)"><?= $pmApproved ? '1/1' : '0/1' ?></div>
  </div>
  <div class="tgt-kpi">
    <div class="tgt-kpi-accent" style="background:<?= $sc['text'] ?>"></div>
    <div class="tgt-kpi-icon" style="background:<?= $sc['bg'] ?>;color:<?= $sc['text'] ?>"><i class="fal <?= $sc['icon'] ?>"></i></div>
    <div class="tgt-kpi-lbl">Status</div>
    <div class="tgt-kpi-val" style="color:<?= $sc['text'] ?>;font-size:14px"><?= $overallStatus ?></div>
  </div>
</div>

<div class="row">
  <!-- Left: Info + Gain update -->
  <div class="col-xl-7">

    <div class="tgt-panel  card__wrapper">
      <div class="tgt-panel-title">
        <div class="tgt-dot" style="background:var(--tgt-blue)"></div>
        Target Information
      </div>
      <table style="width:100%;border-collapse:collapse">
        <?php
        $isLabTest = !empty($t['is_lab_test']) ? (int)$t['is_lab_test'] : 0;
        $labBadge  = $isLabTest
          ? '<span style="background:rgba(6,182,212,.12);color:#0891b2;padding:2px 9px;border-radius:10px;font-size:10px;font-weight:700"><i class="bi bi-flask me-1"></i>Yes</span>'
          : '<span style="background:rgba(107,114,128,.1);color:#6b7280;padding:2px 9px;border-radius:10px;font-size:10px;font-weight:700">No</span>';
        $labDateFmt = !empty($t['lab_test_date']) ? date('d M Y', strtotime($t['lab_test_date'])) : '—';

        $rows = [
          ['Project',      htmlspecialchars($t['project_name'] ?? '—')],
          ['Working Plan', htmlspecialchars($t['plan_description'] ?? '—')],
          ['Design Type',  '<span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px;background:rgba(59,130,246,.1);color:#2563eb">' . htmlspecialchars($t['design_type_name'] ?? '—') . '</span>'],
          ['Year / Month', ($t['year'] ?? '—') . ' / ' . ($months[(int)($t['month'] ?? 0)] ?? '—')],
          ['Week',         $t['week_number'] ? 'Week ' . $t['week_number'] : 'Monthly Target'],
          ['Lab Test',     $labBadge],
          ['Lab Test Date', $isLabTest ? $labDateFmt : '<span class="text-muted">—</span>'],
          ['Created By',   htmlspecialchars($t['created_by_name'] ?? '—')],
          ['Created',      !empty($t['created_at']) ? date('d M Y', strtotime($t['created_at'])) : '—'],
        ];
        foreach ($rows as $r): ?>
          <tr style="border-bottom:1px solid rgba(0,0,0,.05)">
            <td style="padding:9px 12px;font-size:11px;font-weight:700;color:var(--text-muted);white-space:nowrap;width:35%"><?= $r[0] ?></td>
            <td style="padding:9px 12px;font-size:12.5px"><?= $r[1] ?></td>
          </tr>
        <?php endforeach; ?>
      </table>

      <?php if (!empty($t['target_details'])): ?>
        <div style="margin-top:14px;padding:12px 14px;background:rgba(59,130,246,.04);
                  border:1px solid rgba(59,130,246,.12);border-radius:10px">
          <div style="font-size:10px;font-weight:700;letter-spacing:1px;text-transform:uppercase;
                    color:#2563eb;margin-bottom:6px">
            <i class="bi bi-card-text me-1"></i>Target Details
          </div>
          <div style="font-size:12.5px;line-height:1.7;white-space:pre-line"><?= htmlspecialchars($t['target_details']) ?></div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Progress -->
    <div class="tgt-panel card__wrapper">
      <div class="tgt-panel-title">
        <div class="tgt-dot" style="background:var(--tgt-green)"></div>
        Progress Overview
      </div>
      <?php
      $tPct = min((float)($t['target_percentage'] ?? 0), 100);
      $gPct = min((float)($t['gain_percentage']   ?? 0), 100);
      $gColor = $gPct >= $tPct ? 'linear-gradient(90deg,#059669,#10b981)' : ($gPct > 0 ? 'linear-gradient(90deg,#d97706,#f59e0b)' : 'linear-gradient(90deg,#dc2626,#ef4444)');
      ?>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:16px;text-align:center">
        <div>
          <div style="font-size:36px;font-weight:900;color:var(--tgt-blue)"><?= number_format($tPct, 1) ?>%</div>
          <div style="font-size:11px;color:var(--text-muted)">Target</div>
        </div>
        <div>
          <div style="font-size:36px;font-weight:900;color:<?= $gPct >= $tPct ? 'var(--tgt-green)' : 'var(--tgt-amber)' ?>"><?= number_format($gPct, 1) ?>%</div>
          <div style="font-size:11px;color:var(--text-muted)">Actual Gain</div>
        </div>
      </div>
      <div style="margin-bottom:8px">
        <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:4px">
          <span>Target</span><span><?= number_format($tPct, 1) ?>%</span>
        </div>
        <div class="tgt-prog-track">
          <div class="tgt-prog-fill" style="width:<?= $tPct ?>%;background:linear-gradient(90deg,#1e40af,#3b82f6)"></div>
        </div>
      </div>
      <div>
        <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:4px">
          <span>Gain</span><span><?= number_format($gPct, 1) ?>%</span>
        </div>
        <div class="tgt-prog-track">
          <div class="tgt-prog-fill" style="width:<?= $gPct ?>%;background:<?= $gColor ?>"></div>
        </div>
      </div>

      <?php if (!empty($t['photo_link'])): ?>
        <div style="margin-top:14px">
          <img src="<?= $base_url ?>/<?= htmlspecialchars($t['photo_link']) ?>"
            style="max-width:100%;border-radius:10px;max-height:220px;object-fit:cover" alt="Site Photo">
        </div>
      <?php endif; ?>
      <?php if (!empty($t['field_test_details'])): ?>
        <div style="margin-top:12px;padding:12px;background:rgba(0,0,0,.03);border-radius:10px;font-size:12px">
          <strong>Field Test:</strong><br><?= nl2br(htmlspecialchars($t['field_test_details'])) ?>
        </div>
      <?php endif; ?>
      <?php if (!empty($t['lab_test_result'])): ?>
        <div style="margin-top:8px;padding:12px;background:rgba(0,0,0,.03);border-radius:10px;font-size:12px">
          <strong>Lab Result:</strong><br><?= nl2br(htmlspecialchars($t['lab_test_result'])) ?>
        </div>
      <?php endif; ?>

      <?php if ($currentRoleId == 13 && !$fullyApproved): ?>
      <!-- Update Gain Form -->
      <hr style="border-color:rgba(0,0,0,.07);margin:16px 0">
      <div style="font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;color:var(--text-muted)">
        Update Actual Gain
      </div>
      <form action="<?= $base_url ?>/con-target-gain/<?= $t['id'] ?>" method="POST" enctype="multipart/form-data">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px">
          <div>
            <label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">Gain %</label>
            <input type="number" name="gain_percentage" class="form-control form-control-sm"
              step="0.01" min="0" max="100" value="<?= $t['gain_percentage'] ?? 0 ?>">
          </div>
          <div>
            <label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">Site Photo</label>
            <input type="file" name="photo" class="form-control form-control-sm" accept="image/*">
          </div>
        </div>
        <div style="margin-bottom:10px">
          <label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">Field Test Details</label>
          <textarea name="field_test_details" rows="2" class="form-control form-control-sm"><?= htmlspecialchars($t['field_test_details'] ?? '') ?></textarea>
        </div>
        <div style="margin-bottom:10px">
          <label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">Lab Test Result</label>
          <textarea name="lab_test_result" rows="2" class="form-control form-control-sm"><?= htmlspecialchars($t['lab_test_result'] ?? '') ?></textarea>
        </div>
        <button type="submit" style="background:linear-gradient(135deg,#059669,#10b981);color:#fff;
          border:none;border-radius:10px;padding:8px 20px;font-size:12px;font-weight:700;cursor:pointer">
          <i class="fal fa-upload me-2"></i>Update Gain
        </button>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <!-- Right: Approval Panel & Reports -->
  <div class="col-xl-5">

    <!-- Weekly Reports Section -->
    <?php if (!empty($weeklyReports)): ?>
    <div class="tgt-panel card__wrapper" style="margin-bottom: 20px;">
        <div class="tgt-panel-title">
            <div class="tgt-dot" style="background:var(--tgt-blue)"></div>
            Weekly Progress Reports
        </div>
        <?php foreach ($weeklyReports as $wr): ?>
            <div style="border: 1px solid rgba(0,0,0,.08); padding: 12px; border-radius: 8px; margin-bottom: 10px; background: #fafafa;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                    <div style="font-size:12px; font-weight:700; color:#1e40af;">Week <?= htmlspecialchars($wr['week_no']) ?></div>
                    <div style="font-size:11px; color:var(--text-muted);"><?= date('d M Y', strtotime($wr['report_date'])) ?></div>
                </div>
                <div style="font-size:11px; margin-bottom:4px;">
                    <strong>Work Done:</strong> <?= nl2br(htmlspecialchars($wr['work_done'])) ?>
                </div>
                <div style="font-size:11px; margin-bottom:4px;">
                    <strong>Progress %:</strong> <span style="color:var(--tgt-green); font-weight:700;"><?= number_format($wr['progress_percentage'] ?? 0, 1) ?>%</span>
                </div>
                <?php if (!empty($wr['reported_by_name'])): ?>
                <div style="font-size:10px; color:var(--text-muted); margin-top:6px;">
                    <i class="fal fa-user me-1"></i> Reported by <?= htmlspecialchars($wr['reported_by_name']) ?>
                </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Approval Status (Always visible) -->
    <div class="tgt-panel">
      <div class="tgt-panel-title">
        <div class="tgt-dot" style="background:var(--tgt-purple)"></div>
        Approval Status
        <span style="margin-left:auto;font-size:9px;font-weight:700;padding:2px 8px;border-radius:10px;
          background:<?= $fullyApproved ? 'rgba(16,185,129,.12)' : 'rgba(245,158,11,.12)' ?>;
          color:<?= $fullyApproved ? '#059669' : '#d97706' ?>">
          <?= $pmApproved ? '1/1' : '0/1' ?> Done
        </span>
      </div>

      <!-- Creator Info -->
      <?php if (!empty($creatorInfo) || !empty($t['created_by_name'])): ?>
        <div style="display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:10px;
        background:rgba(99,102,241,.05);border:1px solid rgba(99,102,241,.12);margin-bottom:14px">
          <div style="width:34px;height:34px;border-radius:50%;background:rgba(99,102,241,.12);
          display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="fal fa-user" style="color:#6366f1;font-size:14px"></i>
          </div>
          <div>
            <div style="font-size:12px;font-weight:800;color:#6366f1">
              <?= htmlspecialchars($creatorInfo['full_name'] ?? $t['created_by_name'] ?? '—') ?>
            </div>
            <div style="font-size:11px;color:var(--text-muted)">
              <?= htmlspecialchars($creatorInfo['designation'] ?? 'Site Engineer') ?>
              &nbsp;·&nbsp;<i class="fal fa-calendar me-1"></i>
              <?= !empty($t['created_at']) ? date('d M Y', strtotime($t['created_at'])) : '—' ?>
            </div>
          </div>
        </div>
      <?php endif; ?>



      <!-- PM Step -->
      <div class="apv-step" style="background:<?= $pmApproved ? 'rgba(16,185,129,.05)' : 'rgba(245,158,11,.04)' ?>;
        border-color:<?= $pmApproved ? 'rgba(16,185,129,.2)' : 'rgba(245,158,11,.2)' ?>">
        <div style="display:flex;align-items:center;gap:10px">
          <div class="apv-icon" style="background:<?= $pmApproved ? 'rgba(16,185,129,.15)' : 'rgba(245,158,11,.12)' ?>;
            color:<?= $pmApproved ? '#059669' : '#d97706' ?>">
            <i class="fal <?= $pmApproved ? 'fa-check-circle' : 'fa-user-tie' ?>"></i>
          </div>
          <div>
            <div style="font-size:12.5px;font-weight:700">Project Manager</div>
            <div style="font-size:11px;color:var(--text-muted)"><?= $pmApproved ? 'Approved' : 'Awaiting approval' ?></div>
          </div>
        </div>
        <span style="font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;
          background:<?= $pmApproved ? 'rgba(16,185,129,.15)' : 'rgba(245,158,11,.12)' ?>;
          color:<?= $pmApproved ? '#059669' : '#d97706' ?>">
          <?= $pmApproved ? '✓ Approved' : 'Pending' ?>
        </span>
      </div>

      <!-- Final status banner -->
      <?php if ($fullyApproved): ?>
        <div style="margin-top:14px;padding:14px 16px;border-radius:12px;
        background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);text-align:center">
          <i class="fal fa-check-circle" style="color:#059669;font-size:22px;display:block;margin-bottom:6px"></i>
          <div style="font-size:13px;font-weight:800;color:#059669">Fully Approved</div>
          <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Contractor bill can now be raised</div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Approval Action Card — only for QC/PM, not for SE/creator -->
    <?php if ($canApprove && !$alreadyApproved): ?>
      <div class="tgt-panel" style="border-color:rgba(16,185,129,.3);background:rgba(16,185,129,.04)">
        <div class="tgt-panel-title" style="margin-bottom:14px">
          <div class="tgt-dot" style="background:var(--tgt-green)"></div>
          Your Approval — Action Required
        </div>
        <div style="font-size:12px;color:var(--text-muted);margin-bottom:14px">
          <i class="fal fa-info-circle me-1"></i>
          You are approving as <strong>
            <?php
              $roleLabels = [3 => 'Director', 15 => 'Project Manager', 22 => 'PM Head'];
              echo $roleLabels[$currentRoleId] ?? 'Authorized Approver';
            ?>
          </strong>
        </div>
        <form action="<?= $base_url ?>/con-target-approve/<?= $t['id'] ?>" method="POST">
          <button type="submit"
            style="width:100%;background:linear-gradient(135deg,#059669,#10b981);color:#fff;border:none;
            border-radius:10px;padding:11px;font-size:13px;font-weight:700;cursor:pointer"
            onclick="return confirm('Approve this target? This action cannot be undone.')">
            <i class="fal fa-check-circle me-2"></i>Approve Target
          </button>
        </form>
      </div>
    <?php elseif ($canApprove && $alreadyApproved): ?>
      <div class="tgt-panel" style="border-color:rgba(16,185,129,.3);background:rgba(16,185,129,.04)">
        <div style="text-align:center;padding:10px">
          <i class="fal fa-check-circle" style="color:#059669;font-size:28px;display:block;margin-bottom:8px"></i>
          <div style="font-size:13px;font-weight:700;color:#059669">You have already approved this target</div>
        </div>
      </div>
    <?php endif; ?>

  </div><!-- /col -->
</div><!-- /row -->
<div style="height:30px"></div>