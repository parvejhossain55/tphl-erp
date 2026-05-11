<?php
$base = $GLOBALS['base_url'];
$statusColor = [
  'passed' => ['bg' => 'rgba(16,185,129,.1)', 'color' => '#059669', 'label' => 'Passed', 'icon' => 'fa-check-circle'],
  'failed' => ['bg' => 'rgba(239,68,68,.1)', 'color' => '#dc2626', 'label' => 'Failed', 'icon' => 'fa-times-circle'],
  'rework_needed' => ['bg' => 'rgba(245,158,11,.1)', 'color' => '#d97706', 'label' => 'Rework Needed', 'icon' => 'fa-exclamation-circle'],
];
$st = $statusColor[$ins['overall_status']] ?? $statusColor['passed'];
?>
<style>
  .ins-hero {
    background:#059669;
    border-radius: 16px;
    padding: 24px 28px;
    margin-bottom: 20px;
    color: #fff;
    position: relative;
    overflow: hidden;
  }

  .ins-hero::before {
    content: '';
    position: absolute;
    width: 200px;
    height: 200px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .06);
    top: -70px;
    right: -40px;
  }

  .ins-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(255, 255, 255, .15);
    border: 1px solid rgba(255, 255, 255, .25);
    padding: 3px 12px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.2px;
    text-transform: uppercase;
  }

  .ins-card {
    border-radius: 14px;
    border: 1px solid rgba(0, 0, 0, .07);
    background: var(--clr-bg-card, #fff);
    padding: 20px 22px;
    margin-bottom: 16px;
  }

  .ins-section-title {
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: var(--text-muted);
    border-bottom: 1px solid rgba(0, 0, 0, .06);
    padding-bottom: 10px;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .ins-meta-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 14px;
  }

  .ins-meta-item {
    background: rgba(99, 102, 241, .04);
    border: 1px solid rgba(99, 102, 241, .1);
    border-radius: 10px;
    padding: 12px 14px;
  }

  .ins-meta-label {
    font-size: 10px;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 4px;
  }

  .ins-meta-value {
    font-size: 13px;
    font-weight: 700;
    color: var(--text, #111);
  }

  .checklist-row {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 10px;
    border: 1px solid rgba(0, 0, 0, .06);
    margin-bottom: 8px;
  }

  .checklist-row.passed {
    background: rgba(16, 185, 129, .04);
    border-color: rgba(16, 185, 129, .15);
  }

  .checklist-row.failed {
    background: rgba(239, 68, 68, .04);
    border-color: rgba(239, 68, 68, .15);
  }

  .chk-icon {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 13px;
  }

  .chk-icon.passed {
    background: rgba(16, 185, 129, .15);
    color: #059669;
  }

  .chk-icon.failed {
    background: rgba(239, 68, 68, .12);
    color: #dc2626;
  }
</style>

<!-- Header -->
<div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
  <div>
    <h4 style="font-size:17px;font-weight:800;margin:0">Inspection #<?= $ins['id'] ?></h4>
    <div style="font-size:12px;color:var(--text-muted);margin-top:3px">
      <a href="<?= $base ?>/qc-dashboard" style="color:#6366f1;text-decoration:none"><i
          class="fal fa-arrow-left me-1"></i> Back to QC Dashboard</a>
    </div>
  </div>
  <span
    style="background:<?= $st['bg'] ?>;color:<?= $st['color'] ?>;padding:6px 16px;border-radius:20px;font-size:12px;font-weight:800">
    <i class="fal <?= $st['icon'] ?> me-1"></i><?= $st['label'] ?>
  </span>
</div>

<!-- Hero Card -->
<div class="ins-hero">
  <div style="position:relative;z-index:2">
    <div class="ins-badge"><i class="fal fa-clipboard-check"></i> QC Inspection Report</div>
    <h5 style="font-size:20px;font-weight:900;margin:10px 0 4px"><?= htmlspecialchars($ins['project_name'] ?? 'N/A') ?></h5>
    <div style="font-size:13px;color:rgba(255,255,255,.8);margin-bottom:10px">
      <i class="fal fa-tag me-1"></i><?= htmlspecialchars($ins['work_type_name'] ?? 'N/A') ?>
      &nbsp;·&nbsp;
      <i class="fal fa-calendar me-1"></i><?= date('d M Y', strtotime($ins['inspection_date'])) ?>
      &nbsp;·&nbsp;
      <i class="fal fa-user me-1"></i><?= htmlspecialchars($ins['inspector_name'] ?? 'N/A') ?>
    </div>

    <!-- Inject Drawing Info -->
    <?php if (!empty($drawings)): ?>
      <?php $dr = $drawings[0]; // Display the latest related drawing ?>
      <div style="display:inline-flex;align-items:center;gap:10px;background:rgba(255,255,255,.15);padding:6px 14px;border-radius:10px;border:1px solid rgba(255,255,255,.2)">
        <i class="fal fa-drafting-compass fa-lg text-white"></i>
        <div>
          <div style="font-size:10px;text-transform:uppercase;letter-spacing:1px;font-weight:800;color:rgba(255,255,255,.7)">Related Design</div>
          <div style="font-size:13px;font-weight:700;color:#fff">
            <?= htmlspecialchars($dr['design_type_name']) ?> 
            <?php if ($dr['version']): ?>
              <span style="background:rgba(255,255,255,.2);padding:1px 6px;border-radius:6px;font-size:10px;margin-left:4px">v<?= htmlspecialchars($dr['version']) ?></span>
            <?php endif; ?>
          </div>
        </div>
        <?php if (!empty($dr['design_link'])): ?>
          <a href="<?= htmlspecialchars($dr['design_link']) ?>" target="_blank" style="margin-left:10px;background:#fff;color:#059669;padding:4px 10px;border-radius:6px;font-size:11px;font-weight:800;text-decoration:none">
            <i class="fal fa-eye me-1"></i> View Design
          </a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div style="display:inline-flex;align-items:center;gap:6px;font-size:11px;color:rgba(255,255,255,.6);background:rgba(0,0,0,.1);padding:4px 10px;border-radius:6px">
        <i class="fal fa-exclamation-circle"></i> No design found for this work type
      </div>
    <?php endif; ?>

  </div>
</div>

<div class="row">
  <div class="col-lg-8">
    <!-- Checklist Items -->
    <div class="ins-card">
      <div class="ins-section-title">
        <i class="fal fa-clipboard-list" style="color:#6366f1"></i> Checklist Items
        <span style="margin-left:auto;font-size:11px;font-weight:700;color:#6366f1">
          <?= count(array_filter($items, fn($i) => $i['is_passed'])) ?>/<?= count($items) ?> Passed
        </span>
      </div>

      <?php if (!empty($items)): ?>
        <?php foreach ($items as $item): ?>
          <?php $passed = (bool) $item['is_passed']; ?>
          <div class="checklist-row <?= $passed ? 'passed' : 'failed' ?>">
            <div class="chk-icon <?= $passed ? 'passed' : 'failed' ?>">
              <i class="fal <?= $passed ? 'fa-check' : 'fa-times' ?>"></i>
            </div>
            <div style="flex:1">
              <div style="font-size:13px;font-weight:700"><?= htmlspecialchars($item['checklist_item_name'] ?? 'Item') ?>
              </div>
              <?php if (!empty($item['observation_note'])): ?>
                <div style="font-size:11px;color:var(--text-muted);margin-top:3px">
                  <i class="fal fa-comment-alt me-1"></i><?= htmlspecialchars($item['observation_note']) ?>
                </div>
              <?php endif; ?>
              <?php if (!empty($item['item_attachment'])): ?>
                <div style="margin-top:6px">
                  <a href="<?= $base ?>/<?= htmlspecialchars($item['item_attachment']) ?>" target="_blank"
                    style="font-size:10px;color:#6366f1;font-weight:700">
                    <i class="fal fa-paperclip me-1"></i>View Attachment
                  </a>
                </div>
              <?php endif; ?>
            </div>
            <span style="font-size:10px;font-weight:800;padding:3px 9px;border-radius:10px;
              background:<?= $passed ? 'rgba(16,185,129,.15)' : 'rgba(239,68,68,.12)' ?>;
              color:<?= $passed ? '#059669' : '#dc2626' ?>;white-space:nowrap">
              <?= $passed ? '✓ Pass' : '✗ Fail' ?>
            </span>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div style="text-align:center;padding:30px 0;color:var(--text-muted);font-size:12px">
          <i class="fal fa-clipboard fa-2x" style="opacity:.25;display:block;margin-bottom:8px"></i>
          No checklist items recorded for this inspection.
        </div>
      <?php endif; ?>
    </div>

    <!-- Overall Comments -->
    <?php if (!empty($ins['comments'])): ?>
      <div class="ins-card">
        <div class="ins-section-title"><i class="fal fa-comment-alt" style="color:#6366f1"></i> Overall Comments</div>
        <div style="font-size:13px;line-height:1.7;color:var(--text)"><?= nl2br(htmlspecialchars($ins['comments'])) ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Related Project Drawings -->
    <div class="ins-card">
      <div class="ins-section-title">
        <i class="fal fa-drafting-compass" style="color:#6366f1"></i> Related Project Drawings
        <span style="margin-left:auto;font-size:10px;font-weight:700;color:var(--text-muted)">
          <?= $ins['work_type_name'] ?> → <?= !empty($drawings) ? $drawings[0]['design_type_name'] : 'N/A' ?>
        </span>
      </div>

      <?php if (!empty($drawings)): ?>
        <?php foreach ($drawings as $dr): ?>
        <?php
          $drStatus = $dr['status'];
          $drColor  = match($drStatus) {
            'Approved'  => ['bg'=>'rgba(16,185,129,.1)','c'=>'#059669'],
            'Submitted' => ['bg'=>'rgba(99,102,241,.1)', 'c'=>'#6366f1'],
            'Rejected'  => ['bg'=>'rgba(239,68,68,.1)',  'c'=>'#dc2626'],
            default     => ['bg'=>'rgba(107,114,128,.1)','c'=>'#6b7280'],
          };
        ?>
        <div style="display:flex;align-items:flex-start;gap:12px;padding:12px 14px;border-radius:10px;border:1px solid rgba(0,0,0,.07);margin-bottom:8px;background:rgba(99,102,241,.02)">
          <div style="width:36px;height:36px;border-radius:10px;background:rgba(99,102,241,.1);color:#6366f1;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:16px">
            <i class="fal fa-file-alt"></i>
          </div>
          <div style="flex:1;min-width:0">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
              <span style="font-size:12px;font-weight:800"><?= htmlspecialchars($dr['design_type_name'] ?? 'N/A') ?></span>
              <?php if ($dr['version']): ?>
                <span style="font-size:10px;background:rgba(99,102,241,.1);color:#6366f1;padding:2px 8px;border-radius:10px;font-weight:700">v<?= htmlspecialchars($dr['version']) ?></span>
              <?php endif; ?>
              <span style="font-size:10px;padding:2px 8px;border-radius:10px;font-weight:700;background:<?= $drColor['bg'] ?>;color:<?= $drColor['c'] ?>">
                <?= $drStatus ?>
              </span>
            </div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:4px;display:flex;flex-wrap:wrap;gap:10px">
              <?php if ($dr['is_in_house']): ?>
                <span><i class="fal fa-building me-1"></i>In-house design</span>
                <?php if ($dr['designer_user_name']): ?>
                  <span><i class="fal fa-user me-1"></i><?= htmlspecialchars($dr['designer_user_name']) ?></span>
                <?php endif; ?>
              <?php else: ?>
                <span><i class="fal fa-user-tie me-1"></i><?= htmlspecialchars($dr['designer_name'] ?? '') ?></span>
                <?php if ($dr['designer_company']): ?>
                  <span><i class="fal fa-industry me-1"></i><?= htmlspecialchars($dr['designer_company']) ?></span>
                <?php endif; ?>
              <?php endif; ?>
              <?php if ($dr['approval_date']): ?>
                <span><i class="fal fa-calendar-check me-1"></i>Approved: <?= date('d M Y', strtotime($dr['approval_date'])) ?></span>
              <?php endif; ?>
              <?php if ($dr['approved_by_name']): ?>
                <span><i class="fal fa-user-check me-1"></i>By: <?= htmlspecialchars($dr['approved_by_name']) ?></span>
              <?php endif; ?>
            </div>
            <?php if (!empty($dr['design_link'])): ?>
              <div style="margin-top:6px">
                <a href="<?= htmlspecialchars($dr['design_link']) ?>" target="_blank"
                  style="font-size:11px;color:#6366f1;font-weight:700;text-decoration:none">
                  <i class="fal fa-external-link me-1"></i>View Drawing / Link
                </a>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div style="text-align:center;padding:24px 0;color:var(--text-muted);font-size:12px">
          <i class="fal fa-drafting-compass fa-2x" style="opacity:.2;display:block;margin-bottom:8px"></i>
          No project drawings found for this work type.
        </div>
      <?php endif; ?>
    </div>

  </div>

  <div class="col-lg-4">
    <!-- Meta Info -->
    <div class="ins-card">
      <div class="ins-section-title"><i class="fal fa-info-circle" style="color:#6366f1"></i> Inspection Details</div>
      <div class="ins-meta-grid">
        <div class="ins-meta-item">
          <div class="ins-meta-label"><i class="fal fa-building me-1"></i>Project</div>
          <div class="ins-meta-value"><?= htmlspecialchars($ins['project_name'] ?? 'N/A') ?></div>
        </div>
        <div class="ins-meta-item">
          <div class="ins-meta-label"><i class="fal fa-tag me-1"></i>Work Type</div>
          <div class="ins-meta-value"><?= htmlspecialchars($ins['work_type_name'] ?? 'N/A') ?></div>
        </div>
        <div class="ins-meta-item">
          <div class="ins-meta-label"><i class="fal fa-user me-1"></i>Inspector</div>
          <div class="ins-meta-value"><?= htmlspecialchars($ins['inspector_name'] ?? 'N/A') ?></div>
        </div>
        <div class="ins-meta-item">
          <div class="ins-meta-label"><i class="fal fa-calendar me-1"></i>Date</div>
          <div class="ins-meta-value"><?= date('d M Y', strtotime($ins['inspection_date'])) ?></div>
        </div>
        <div class="ins-meta-item" style="grid-column:1/-1">
          <div class="ins-meta-label"><i class="fal fa-flag me-1"></i>Overall Status</div>
          <div class="ins-meta-value" style="color:<?= $st['color'] ?>">
            <i class="fal <?= $st['icon'] ?> me-1"></i><?= $st['label'] ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Summary -->
    <div class="ins-card">
      <div class="ins-section-title"><i class="fal fa-chart-pie" style="color:#6366f1"></i> Summary</div>
      <?php
      $total = count($items);
      $passed = count(array_filter($items, fn($i) => $i['is_passed']));
      $failed = $total - $passed;
      $pct = $total > 0 ? round(($passed / $total) * 100) : 0;
      ?>
      <div style="margin-bottom:10px">
        <div style="display:flex;justify-content:space-between;font-size:11px;font-weight:700;margin-bottom:5px">
          <span>Pass Rate</span><span style="color:#6366f1"><?= $pct ?>%</span>
        </div>
        <div style="height:8px;background:rgba(0,0,0,.07);border-radius:10px;overflow:hidden">
          <div
            style="height:100%;width:<?= $pct ?>%;background:linear-gradient(90deg,#059669,#10b981);border-radius:10px;transition:width .3s">
          </div>
        </div>
      </div>
      <div style="display:flex;gap:10px">
        <div style="flex:1;text-align:center;background:rgba(16,185,129,.08);border-radius:10px;padding:10px">
          <div style="font-size:22px;font-weight:900;color:#059669"><?= $passed ?></div>
          <div style="font-size:10px;font-weight:700;color:var(--text-muted)">PASSED</div>
        </div>
        <div style="flex:1;text-align:center;background:rgba(239,68,68,.08);border-radius:10px;padding:10px">
          <div style="font-size:22px;font-weight:900;color:#dc2626"><?= $failed ?></div>
          <div style="font-size:10px;font-weight:700;color:var(--text-muted)">FAILED</div>
        </div>
        <div style="flex:1;text-align:center;background:rgba(99,102,241,.08);border-radius:10px;padding:10px">
          <div style="font-size:22px;font-weight:900;color:#6366f1"><?= $total ?></div>
          <div style="font-size:10px;font-weight:700;color:var(--text-muted)">TOTAL</div>
        </div>
      </div>
    </div>

    <!-- Overall Attachment -->
    <?php if (!empty($ins['attachment'])): ?>
      <div class="ins-card">
        <div class="ins-section-title"><i class="fal fa-paperclip" style="color:#6366f1"></i> Overall Attachment</div>
        <?php $ext = strtolower(pathinfo($ins['attachment'], PATHINFO_EXTENSION)); ?>
        <?php if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
          <a href="<?= $base ?>/<?= htmlspecialchars($ins['attachment']) ?>" target="_blank">
            <img src="<?= $base ?>/<?= htmlspecialchars($ins['attachment']) ?>" alt="Attachment"
              style="width:100%;border-radius:10px;border:1px solid rgba(0,0,0,.08)">
          </a>
        <?php else: ?>
          <a href="<?= $base ?>/<?= htmlspecialchars($ins['attachment']) ?>" target="_blank"
            style="display:flex;align-items:center;gap:8px;padding:12px;background:rgba(99,102,241,.06);border-radius:10px;text-decoration:none;color:#6366f1;font-weight:700;font-size:12px">
            <i class="fal fa-file-pdf fa-2x"></i> View Attachment
          </a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>