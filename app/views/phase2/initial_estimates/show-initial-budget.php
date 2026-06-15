<?php
$base         = $base_url;
$budget       = $budget            ?? [];
$items        = $items             ?? [];
$labors       = $labors            ?? [];
$breakdown    = $breakdown         ?? [];
$workingPlans = $workingPlans      ?? [];
$monthlyPlanSummary = $monthlyPlanSummary ?? [];
$locked       = $locked            ?? false;
$currentUserId= $currentUserId     ?? 0;
$canApprove   = $canApprove        ?? false;
$creatorInfo  = $creatorInfo       ?? [];
$spentTotal   = $spentTotal        ?? 0.0;
$spentDetails = $spentDetails      ?? ['materials' => [], 'contractors' => [], 'materials_total' => 0.0, 'contractors_total' => 0.0];

$monthNames = ['','January','February','March','April','May','June',
               'July','August','September','October','November','December'];

function sib_fmt($n) {
    $n = (float)$n;
    if ($n >= 10000000) return '৳' . number_format($n/10000000,2).' Cr';
    if ($n >= 100000)   return '৳' . number_format($n/100000,2).' L';
    return '৳' . number_format($n, 2);
}

function sib_format_floor($floorType, $floorNo) {
    if ($floorNo === null || $floorNo === '') {
        return 'General/Unassigned';
    }
    if ($floorType === 'Roof') {
        return 'Roof (Lvl ' . $floorNo . ')';
    }
    if ($floorType === 'Ground_Floor') {
        return 'Ground Floor';
    }
    if ($floorType === 'Basement') {
        return 'Basement ' . abs($floorNo);
    }
    
    $n = (int)$floorNo;
    $ends = array('th','st','nd','rd','th','th','th','th','th','th');
    if ((($n % 100) >= 11) && (($n % 100) <= 13)) {
        $ordinal = $n . 'th';
    } else {
        $ordinal = $n . $ends[$n % 10];
    }
    return $ordinal . ' Floor';
}
?>
<style>
/* ═══════════════════════ DESIGN TOKENS ═══════════════════════ */
:root{
  --sib-primary:#198754; --sib-dark:#0f5132; --sib-light:#20a86a;
  --sib-blue:#3b82f6; --sib-green:#10b981; --sib-amber:#f59e0b;
  --sib-red:#ef4444;  --sib-purple:var(--clr-theme-secondary);--sib-teal:#14b8a6;
}

/* ── Hero ── */
.sib-hero{
  background:linear-gradient(135deg, #0f5132 0%, #198754 55%, #20a86a 100%);
  border-radius:18px; padding:26px 30px; margin-bottom:22px;
  position:relative; overflow:hidden;
}
.sib-hero::before{content:'';position:absolute;width:280px;height:280px;
  border-radius:50%;background:rgba(255,255,255,.04);top:-90px;right:-70px;}
.sib-hero::after{content:'';position:absolute;width:160px;height:160px;
  border-radius:50%;background:rgba(255,255,255,.03);bottom:-50px;left:-30px;}
.sib-hero-inner{position:relative;z-index:2;}
.sib-badge{display:inline-flex;align-items:center;gap:6px;
  background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);
  padding:4px 12px;border-radius:20px;font-size:10.5px;font-weight:700;
  letter-spacing:1.4px;text-transform:uppercase;color:#fff;margin-bottom:12px;}
.sib-hero-title{font-size:24px;font-weight:900;color:#fff;margin:0 0 5px;}
.sib-hero-sub{font-size:12.5px;color:rgba(255,255,255,.65);margin:0;}
.sib-status-pill{display:inline-flex;align-items:center;gap:7px;
  font-size:12px;font-weight:700;padding:6px 16px;border-radius:20px;
  letter-spacing:.5px;}
.sib-btn-w{background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.28);
  color:#fff;border-radius:10px;padding:7px 18px;font-size:12px;font-weight:700;
  text-decoration:none;display:inline-flex;align-items:center;gap:6px;
  transition:background .18s;}
.sib-btn-w:hover{background:rgba(255,255,255,.25);color:#fff;text-decoration:none;}

/* ── KPI Grid ── */
.sib-kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;}
@media(max-width:992px){.sib-kpi-grid{grid-template-columns:repeat(2,1fr);}}
.sib-kpi{border-radius:14px;padding:18px 20px;border:1px solid rgba(0,0,0,.08);
  background:var(--card-bg,#fff);position:relative;overflow:hidden;}
.sib-kpi-accent{position:absolute;left:0;top:0;width:4px;height:100%;border-radius:14px 0 0 14px;}
.sib-kpi-icon{width:40px;height:40px;border-radius:11px;display:flex;align-items:center;
  justify-content:center;font-size:17px;margin-bottom:11px;}
.sib-kpi-val{font-size:20px;font-weight:900;line-height:1;margin-bottom:3px;}
.sib-kpi-lbl{font-size:10px;font-weight:700;letter-spacing:1px;text-transform:uppercase;
  color:var(--text-muted);}

/* ── Panel ── */
.sib-panel{border-radius:14px;padding:22px;border:1px solid rgba(0,0,0,.08);
  background:var(--card-bg,#fff);margin-bottom:20px;}
.sib-panel-title{font-size:11px;font-weight:700;letter-spacing:1.5px;
  text-transform:uppercase;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.sib-dot{width:8px;height:8px;border-radius:50%;}

/* ── Table ── */
.sib-table{width:100%;border-collapse:collapse;}
.sib-table th{font-size:10px;letter-spacing:1.3px;text-transform:uppercase;
  font-weight:600;padding:0 10px 10px;border-bottom:2px solid rgba(0,0,0,.07);text-align:left;}
.sib-table td{padding:11px 10px;border-bottom:1px solid rgba(0,0,0,.05);
  font-size:12.5px;vertical-align:middle;}
.sib-table tr:last-child td{border-bottom:none;}
.sib-table .text-right{text-align:right;}

/* ── Design Type Breakdown ── */
.bkd-card{border:1px solid rgba(0,0,0,.08);border-radius:12px;
  padding:16px;margin-bottom:12px;}
.bkd-card:last-child{margin-bottom:0;}
.bkd-name{font-size:13px;font-weight:800;margin-bottom:10px;}
.bkd-row{display:flex;justify-content:space-between;font-size:12px;
  border-bottom:1px dashed rgba(0,0,0,.07);padding:5px 0;}
.bkd-row:last-child{border-bottom:none;font-weight:800;font-size:13px;}

/* ── Working Plan Status Pills ── */
.wp-status{font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;
  letter-spacing:.5px;display:inline-flex;align-items:center;gap:4px;}
.wp-approved{background:rgba(16,185,129,.12);color:#059669;}
.wp-pending {background:rgba(245,158,11,.12);color:#d97706;}
.wp-rejected{background:rgba(239,68,68,.12);color:#dc2626;}

/* ── Approve / Cancel Action card ── */
.sib-action-card{border-radius:14px;padding:22px;
  border:2px solid rgba(245,158,11,.3);background:rgba(245,158,11,.04);
  margin-bottom:20px;}

/* ── Tab Nav ── */
.sib-tabs{display:flex;gap:4px;margin-bottom:20px;border-bottom:2px solid rgba(0,0,0,.07);
  padding-bottom:-2px;}
.sib-tab-btn{background:none;border:none;padding:10px 18px;font-size:12px;
  font-weight:700;color:var(--text-muted);cursor:pointer;border-radius:8px 8px 0 0;
  transition:all .18s;letter-spacing:.5px;}
.sib-tab-btn.active,.sib-tab-btn:hover{color:var(--sib-blue);}
.sib-tab-btn.active{border-bottom:3px solid var(--sib-blue);margin-bottom:-2px;}
.sib-tab-pane{display:none;}
.sib-tab-pane.active{display:block;}

.empty-state{text-align:center;padding:32px 0;color:var(--text-muted);}
.empty-state i{font-size:32px;opacity:.25;display:block;margin-bottom:8px;}

/* Spent Modal Tabs custom styling */
.spent-nav-tabs .nav-link {
  color: var(--text-muted) !important;
  border: none !important;
  border-bottom: 3px solid transparent !important;
  background: none !important;
  transition: all 0.2s;
}
.spent-nav-tabs .nav-link:hover {
  border-bottom-color: rgba(0,0,0,0.1) !important;
}
.spent-nav-tabs .nav-link.active {
  color: currentColor !important;
  border-bottom: 3px solid currentColor !important;
}
</style>

<!-- ═══════════════ HERO ═══════════════ -->
<div class="sib-hero">
  <div class="sib-hero-inner">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:14px">
      <div>
        <div class="sib-badge"><i class="fal fa-file-invoice-dollar"></i>Initial Estimate Budget</div>
        <h4 class="sib-hero-title"><?= htmlspecialchars($budget['project_name'] ?? 'N/A') ?></h4>
        <p class="sib-hero-sub">
          <i class="fal fa-calendar me-1"></i>
          <?= (!empty($budget['start_date']) && $budget['start_date'] !== '0000-00-00') ? date('d M Y', strtotime($budget['start_date'])) : '—' ?>
          &nbsp;→&nbsp;
          <?= (!empty($budget['end_date']) && $budget['end_date'] !== '0000-00-00') ? date('d M Y', strtotime($budget['end_date'])) : '—' ?>
          &nbsp;&nbsp;·&nbsp;&nbsp;
          <?= number_format($budget['total_area_sqft'] ?? 0, 2) ?> sqft
        </p>
        <?php if (!empty($budget['budget_link'])): ?>
        <p class="sib-hero-sub mt-2">
          <i class="fal fa-link me-1"></i><a href="<?= htmlspecialchars($budget['budget_link']) ?>" target="_blank" style="color:rgba(255,255,255,.8);text-decoration:underline;">View Budget Link</a>
        </p>
        <?php endif; ?>
        <?php if (!empty($budget['work_details'])): ?>
        <p class="sib-hero-sub mt-2" style="max-width:500px;line-height:1.4;">
          <i class="fal fa-info-circle me-1"></i><?= htmlspecialchars($budget['work_details']) ?>
        </p>
        <?php endif; ?>
      </div>
      <div style="text-align:right">
        <?php
        $st   = $budget['status'] ?? 'draft';
        $sMap = [
          'approved'    => ['bg'=>'#fff',                    'text'=>'#059669','icon'=>'fa-check-circle'],
          'cancelled'   => ['bg'=>'rgba(239,68,68,.2)',       'text'=>'#fff',   'icon'=>'fa-times-circle'],
          'pm_approved' => ['bg'=>'rgba(16,185,129,.2)',      'text'=>'#fff',   'icon'=>'fa-user-check'],
          'pending'     => ['bg'=>'rgba(245,158,11,.2)',      'text'=>'#fff',   'icon'=>'fa-clock'],
          'draft'       => ['bg'=>'rgba(148,163,184,.25)',    'text'=>'#fff',   'icon'=>'fa-file-alt'],
        ];
        $sc = $sMap[$st] ?? $sMap['draft'];
        $stLabel = match($st) {
            'pm_approved' => 'PM Approved',
            'pending'     => 'Pending PM Approval',
            default       => ucfirst($st),
        };
        ?>
        <div class="sib-status-pill mb-2" style="background:<?= $sc['bg'] ?>;color:<?= $sc['text'] ?>">
          <i class="fal <?= $sc['icon'] ?>"></i>
          <?= $stLabel ?>
        </div>
        <?php if ($st === 'approved' && !empty($budget['approved_by_name'])): ?>
        <div style="font-size:11px;color:rgba(255,255,255,.65)">
          <i class="fal fa-user-check me-1"></i>
          Approved by <strong style="color:#fff"><?= htmlspecialchars($budget['approved_by_name']) ?></strong><br>
          <?php if (!empty($budget['approved_by_role'])): ?>
          <span style="font-size:10px;font-style:italic;color:rgba(255,255,255,.5)"><?= htmlspecialchars($budget['approved_by_role']) ?></span><br>
          <?php endif; ?>
          <i class="fal fa-calendar me-1"></i><?= !empty($budget['approval_date']) ? date('d M Y', strtotime($budget['approval_date'])) : '' ?>
        </div>
        <?php endif; ?>
        <?php if ($st === 'cancelled' && !empty($budget['rejection_note'])): ?>
        <div style="font-size:11px;color:rgba(255,255,255,.65);margin-top:4px">
          <i class="fal fa-comment-alt me-1"></i><?= htmlspecialchars(substr($budget['rejection_note'],0,50)) ?>…
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Action Buttons -->
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:18px">
      <?php if (!$locked && in_array($st, ['draft', 'pending'])): ?>
        <a href="<?= $base ?>/con-estimate-edit/<?= $budget['id'] ?>" class="sib-btn-w">
          <i class="fal fa-pencil"></i>Edit Budget
        </a>
      <?php endif; ?>
      <?php if ($st === 'draft'): ?>
        <span class="sib-btn-w" style="background:rgba(245,158,11,.2);border-color:rgba(245,158,11,.5);cursor:default">
          <i class="fal fa-file-alt"></i>Draft — Submit to finalize
        </span>
      <?php endif; ?>
      <a href="<?= $base ?>/con-estimate-print/<?= $budget['id'] ?>" target="_blank" class="sib-btn-w"
         style="background:rgba(74,222,128,.15);border-color:rgba(74,222,128,.4);">
        <i class="fal fa-print"></i>Print / PDF
      </a>
      <a href="<?= $base ?>/con-estimates" class="sib-btn-w"><i class="fal fa-arrow-left"></i>Back to List</a>
    </div>
  </div>
</div>

<!-- ═══════════════ APPROVAL TRACKING TIMELINE ═══════════════ -->
<div class="sib-panel" style="border-left: 4px solid var(--clr-theme-primary, #16a34a); background: rgba(22, 163, 74, 0.02); margin-bottom: 22px;">
  <div class="sib-panel-title" style="margin-bottom: 18px; color: var(--clr-theme-primary, #16a34a); font-weight: 800;">
    <div class="sib-dot" style="background: var(--clr-theme-primary, #16a34a)"></div>
    <i class="fal fa-tasks me-1"></i> Budget Approval Status Tracker
  </div>
  <div class="row align-items-center justify-content-between text-center text-md-start">
    <!-- Step 1: Creation -->
    <div class="col-md-4 mb-3 mb-md-0">
      <div class="d-flex align-items-center gap-3 justify-content-center justify-content-md-start">
        <div class="step-icon" style="width:36px; height:36px; border-radius:50%; background:rgba(22,163,74,0.12); color:#16a34a; display:flex; align-items:center; justify-content:center; font-size:16px;">
          <i class="fal fa-check-circle"></i>
        </div>
        <div>
          <div style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">Step 1: Submission</div>
          <div style="font-size:13px; font-weight:700; color:#2d3748;">Created & Submitted</div>
          <div style="font-size:11px; color:var(--text-muted);">
            By <?= htmlspecialchars($budget['creator_name'] ?? 'Estimator') ?><br>
            <span style="font-size:10px; opacity:0.8;"><?= !empty($budget['created_at']) ? date('d M Y, h:i A', strtotime($budget['created_at'])) : '' ?></span>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Step 2: PM Approval -->
    <div class="col-md-4 mb-3 mb-md-0">
      <div class="d-flex align-items-center gap-3 justify-content-center justify-content-md-start">
        <?php
        $pmApproved = in_array($st, ['pm_approved', 'approved']);
        $pmPending = ($st === 'pending');
        $pmRejected = ($st === 'cancelled' && empty($budget['pm_approved_by_name']));
        
        if ($pmApproved):
        ?>
          <div class="step-icon" style="width:36px; height:36px; border-radius:50%; background:rgba(22,163,74,0.12); color:#16a34a; display:flex; align-items:center; justify-content:center; font-size:16px;">
            <i class="fal fa-check-circle"></i>
          </div>
          <div>
            <div style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">Step 2: PM Approval</div>
            <div style="font-size:13px; font-weight:700; color:#16a34a;">Approved</div>
            <div style="font-size:11px; color:var(--text-muted);">
              By <?= htmlspecialchars($budget['pm_approved_by_name'] ?? $budget['approved_by_name'] ?? 'Project Manager') ?><br>
              <span style="font-size:10px; opacity:0.8;"><?= !empty($budget['pm_approval_date']) ? date('d M Y, h:i A', strtotime($budget['pm_approval_date'])) : '' ?></span>
            </div>
          </div>
        <?php elseif ($pmRejected): ?>
          <div class="step-icon" style="width:36px; height:36px; border-radius:50%; background:rgba(239,68,68,0.12); color:#ef4444; display:flex; align-items:center; justify-content:center; font-size:16px;">
            <i class="fal fa-times-circle"></i>
          </div>
          <div>
            <div style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">Step 2: PM Approval</div>
            <div style="font-size:13px; font-weight:700; color:#ef4444;">Rejected</div>
            <div style="font-size:11px; color:var(--text-muted);">PM Review Rejected</div>
          </div>
        <?php else: ?>
          <div class="step-icon" style="width:36px; height:36px; border-radius:50%; background:rgba(245,158,11,0.12); color:#f59e0b; display:flex; align-items:center; justify-content:center; font-size:16px;">
            <i class="fal fa-clock"></i>
          </div>
          <div>
            <div style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">Step 2: PM Approval</div>
            <div style="font-size:13px; font-weight:700; color:#f59e0b;">Pending Review</div>
            <div style="font-size:11px; color:var(--text-muted);">Awaiting PM Approval</div>
          </div>
        <?php endif; ?>
      </div>
    </div>
    
    <!-- Step 3: Management Approval -->
    <div class="col-md-4">
      <div class="d-flex align-items-center gap-3 justify-content-center justify-content-md-start">
        <?php
        $mgmtApproved = ($st === 'approved');
        $mgmtPending = ($st === 'pm_approved');
        $mgmtRejected = ($st === 'cancelled' && !empty($budget['pm_approved_by_name']));
        $mgmtLocked = ($st === 'pending');
        
        if ($mgmtApproved):
        ?>
          <div class="step-icon" style="width:36px; height:36px; border-radius:50%; background:rgba(22,163,74,0.12); color:#16a34a; display:flex; align-items:center; justify-content:center; font-size:16px;">
            <i class="fal fa-check-circle"></i>
          </div>
          <div>
            <div style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">Step 3: Management Approval</div>
            <div style="font-size:13px; font-weight:700; color:#16a34a;">Approved (Final)</div>
            <div style="font-size:11px; color:var(--text-muted);">
              By <?= htmlspecialchars($budget['mgmt_approved_by_name'] ?? 'Management') ?><br>
              <span style="font-size:10px; opacity:0.8;"><?= !empty($budget['mgmt_approval_date']) ? date('d M Y, h:i A', strtotime($budget['mgmt_approval_date'])) : '' ?></span>
            </div>
          </div>
        <?php elseif ($mgmtRejected): ?>
          <div class="step-icon" style="width:36px; height:36px; border-radius:50%; background:rgba(239,68,68,0.12); color:#ef4444; display:flex; align-items:center; justify-content:center; font-size:16px;">
            <i class="fal fa-times-circle"></i>
          </div>
          <div>
            <div style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">Step 3: Management Approval</div>
            <div style="font-size:13px; font-weight:700; color:#ef4444;">Rejected</div>
            <div style="font-size:11px; color:var(--text-muted);">Management Rejected</div>
          </div>
        <?php elseif ($mgmtPending): ?>
          <div class="step-icon" style="width:36px; height:36px; border-radius:50%; background:rgba(245,158,11,0.12); color:#f59e0b; display:flex; align-items:center; justify-content:center; font-size:16px;">
            <i class="fal fa-clock"></i>
          </div>
          <div>
            <div style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">Step 3: Management Approval</div>
            <div style="font-size:13px; font-weight:700; color:#f59e0b;">Pending Final Approval</div>
            <div style="font-size:11px; color:var(--text-muted);">Awaiting Management Approval</div>
          </div>
        <?php else: ?>
          <div class="step-icon" style="width:36px; height:36px; border-radius:50%; background:rgba(148,163,184,0.1); color:#94a3b8; display:flex; align-items:center; justify-content:center; font-size:16px;">
            <i class="fal fa-lock"></i>
          </div>
          <div>
            <div style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">Step 3: Management Approval</div>
            <div style="font-size:13px; font-weight:700; color:#94a3b8;">Locked</div>
            <div style="font-size:11px; color:var(--text-muted);">Awaiting Step 2 completion</div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════ KPI GRID ═══════════════ -->
<div class="sib-kpi-grid">
  <div class="sib-kpi">
    <div class="sib-kpi-accent" style="background:var(--sib-green)"></div>
    <div class="sib-kpi-icon" style="background:rgba(16,185,129,.12);color:var(--sib-green)"><i class="fal fa-boxes-stacked"></i></div>
    <div class="sib-kpi-lbl">Material Cost</div>
    <div class="sib-kpi-val" style="color:var(--sib-green)"><?= sib_fmt($budget['material_cost_total'] ?? 0) ?></div>
  </div>
  <div class="sib-kpi">
    <div class="sib-kpi-accent" style="background:var(--sib-blue)"></div>
    <div class="sib-kpi-icon" style="background:rgba(59,130,246,.12);color:var(--sib-blue)"><i class="fal fa-hard-hat"></i></div>
    <div class="sib-kpi-lbl">Labor Cost</div>
    <div class="sib-kpi-val" style="color:var(--sib-blue)"><?= sib_fmt($budget['labor_cost_total'] ?? 0) ?></div>
  </div>
  <div class="sib-kpi">
    <div class="sib-kpi-accent" style="background:var(--sib-amber)"></div>
    <div class="sib-kpi-icon" style="background:rgba(245,158,11,.12);color:var(--sib-amber)"><i class="fal fa-receipt"></i></div>
    <div class="sib-kpi-lbl">Other Costs</div>
    <div class="sib-kpi-val" style="color:var(--sib-amber)"><?= sib_fmt($budget['other_costs'] ?? 0) ?></div>
    <?php if (!empty($budget['other_cost_detail'])): ?>
    <div style="font-size:10.5px;color:var(--text-muted);margin-top:6px;line-height:1.3;max-height:28px;overflow:hidden" title="<?= htmlspecialchars($budget['other_cost_detail']) ?>">
        <?= htmlspecialchars($budget['other_cost_detail']) ?>
    </div>
    <?php endif; ?>
  </div>
  <div class="sib-kpi">
    <div class="sib-kpi-accent" style="background:var(--sib-purple)"></div>
    <div class="sib-kpi-icon" style="background:rgba(var(--clr-theme-secondary-rgb),.12);color:var(--sib-purple)"><i class="fal fa-coins"></i></div>
    <div class="sib-kpi-lbl">Grand Total</div>
    <div class="sib-kpi-val" style="color:var(--sib-purple)"><?= sib_fmt($budget['total_estimated_cost'] ?? 0) ?></div>
  </div>
</div>

<!-- Spent & Remaining cost tracking -->
<div class="row mb-4">
  <div class="col-md-6 mb-3 mb-md-0">
    <div class="sib-kpi" style="border-left: 5px solid var(--sib-red); height: 100%;">
      <div class="sib-kpi-icon" style="background:rgba(239,68,68,.12);color:var(--sib-red)"><i class="fal fa-file-invoice-dollar"></i></div>
      <div class="sib-kpi-lbl">Total Spent / Cost Usage</div>
      <div class="sib-kpi-val" style="color:var(--sib-red)"><?= sib_fmt($spentTotal) ?></div>
      <button type="button" class="btn btn-sm btn-outline-danger mt-2" data-bs-toggle="modal" data-bs-target="#spentDetailsModal" style="font-size:11px; padding:3px 10px; border-radius:8px;">
        <i class="fal fa-list me-1"></i> View Details / Purpose
      </button>
    </div>
  </div>
  <div class="col-md-6">
    <?php 
    $remaining = ($budget['total_estimated_cost'] ?? 0) - $spentTotal;
    $remColor = $remaining >= 0 ? 'var(--sib-green)' : 'var(--sib-red)';
    $remBg = $remaining >= 0 ? 'rgba(16,185,129,.12)' : 'rgba(239,68,68,.12)';
    $pct = ($budget['total_estimated_cost'] ?? 0) > 0 ? min(100, round(($spentTotal / $budget['total_estimated_cost']) * 100, 1)) : 0;
    ?>
    <div class="sib-kpi" style="border-left: 5px solid <?= $remColor ?>; height: 100%;">
      <div class="sib-kpi-icon" style="background:<?= $remBg ?>;color:<?= $remColor ?>"><i class="fal fa-wallet"></i></div>
      <div class="sib-kpi-lbl">Remaining Budget</div>
      <div class="sib-kpi-val" style="color:<?= $remColor ?>"><?= sib_fmt($remaining) ?></div>
      <div class="text-muted mt-2" style="font-size:11.5px; padding-top:2px;">
        Usage Ratio: <span class="fw-bold text-dark"><?= $pct ?>%</span>
      </div>
    </div>
  </div>
</div>

<!-- Project Floor Setup Details Display -->
<div class="row mb-4">
  <div class="col-12">
    <?php if (!empty($projectFloors)): ?>
      <?php 
      $totalSlabArea = 0; 
      foreach ($projectFloors as $f) {
          $totalSlabArea += (float)($f['slab_area_sqft'] ?? 0);
      }
      ?>
      <div class="p-3 rounded border shadow-sm" style="background:#f0fdf4; border-color:#bbf7d0;">
          <div class="d-flex align-items-center justify-content-between mb-2 text-success fw-bold" style="font-size:0.9rem">
              <span><i class="fal fa-layer-group"></i> Project Floor Setup Details</span>
              <span class="badge bg-success" style="font-size:0.85rem; padding: 5px 10px;">Total: <?= number_format($totalSlabArea, 2) ?> sqft</span>
          </div>
          <div class="d-flex flex-wrap">
              <?php foreach ($projectFloors as $f): ?>
                  <span class="badge bg-light text-dark border p-2 me-2 mb-2" style="font-size:0.85rem; font-weight:normal">
                      <i class="fal fa-building text-success"></i> <strong><?= htmlspecialchars(sib_format_floor($f['floor_type'], $f['floor_no'])) ?></strong>: <?= number_format($f['slab_area_sqft'], 2) ?> sqft
                  </span>
              <?php endforeach; ?>
          </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- ═══════════════ APPROVAL ACTION ═══════════════ -->
<?php
$roleId = (int)($_SESSION['user']['role_id'] ?? 0);
?>

<?php if (!$locked && $st === 'pending' && in_array($roleId, [15, 22])): ?>
<!-- ═══ PM APPROVAL CARD ═══════════════════════════════════════════ -->
<div class="sib-action-card" style="border-left:4px solid #d97706">
  <div class="sib-panel-title" style="margin-bottom:14px">
    <div class="sib-dot" style="background:var(--sib-amber)"></div>
    Step 1: PM Approval Required
    <span style="font-size:11px;font-weight:400;color:rgba(255,255,255,.6);margin-left:8px">
      (আপনার role: PM / Project Manager)
    </span>
  </div>
  <?php if (!empty($creatorInfo)): ?>
  <div style="font-size:12px;color:rgba(255,255,255,.65);margin-bottom:12px">
    <i class="fal fa-user me-1"></i>
    Submitted by <strong><?= htmlspecialchars($creatorInfo['full_name'] ?? '—') ?></strong>
    &nbsp;·&nbsp;
    <?= !empty($budget['created_at']) ? date('d M Y, h:i A', strtotime($budget['created_at'])) : '—' ?>
  </div>
  <?php endif; ?>
  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
    <form action="<?= $base ?>/con-estimate-approve/<?= $budget['id'] ?>" method="POST">
      <button type="submit" class="btn btn-sm px-4"
        style="background:linear-gradient(135deg,#d97706,#f59e0b);color:#fff;border-radius:10px;font-size:12px;font-weight:700;border:none"
        onclick="return confirm('PM Approval করবেন? এরপর Management Final Approve করবে।')">
        <i class="fal fa-user-check me-2"></i>PM Approve করুন
      </button>
    </form>
    <button type="button" class="btn btn-sm px-4"
      style="background:rgba(239,68,68,.1);color:#dc2626;border:1px solid rgba(239,68,68,.3);border-radius:10px;font-size:12px;font-weight:700"
      data-bs-toggle="modal" data-bs-target="#cancelModal">
      <i class="fal fa-times-circle me-2"></i>Reject করুন
    </button>
  </div>
</div>
<?php endif; ?>

<?php if (!$locked && $st === 'pm_approved' && in_array($roleId, [1, 3])): ?>
<!-- ═══ MANAGEMENT FINAL APPROVAL CARD ══════════════════════════════ -->
<div class="sib-action-card" style="border-left:4px solid #059669">
  <div class="sib-panel-title" style="margin-bottom:14px">
    <div class="sib-dot" style="background:var(--sib-green)"></div>
    Step 2: Management Final Approval
    <span style="font-size:11px;font-weight:400;color:rgba(255,255,255,.6);margin-left:8px">
      (PM Approved — আপনার final approval বাকি)
    </span>
  </div>
  <?php if (!empty($budget['approved_by_name'])): ?>
  <div style="font-size:12px;color:rgba(255,255,255,.65);margin-bottom:12px">
    <i class="fal fa-user-check me-1"></i>
    PM Approved by <strong><?= htmlspecialchars($budget['approved_by_name']) ?></strong>
    &nbsp;·&nbsp;
    <?= !empty($budget['approval_date']) ? date('d M Y', strtotime($budget['approval_date'])) : '—' ?>
  </div>
  <?php endif; ?>
  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
    <form action="<?= $base ?>/con-estimate-approve/<?= $budget['id'] ?>" method="POST">
      <button type="submit" class="btn btn-sm px-4"
        style="background:linear-gradient(135deg,#059669,#10b981);color:#fff;border-radius:10px;font-size:12px;font-weight:700;border:none"
        onclick="return confirm('Final Approve করবেন? এরপর আর edit করা যাবে না।')">
        <i class="fal fa-check-circle me-2"></i>Final Approve করুন
      </button>
    </form>
    <button type="button" class="btn btn-sm px-4"
      style="background:rgba(239,68,68,.1);color:#dc2626;border:1px solid rgba(239,68,68,.3);border-radius:10px;font-size:12px;font-weight:700"
      data-bs-toggle="modal" data-bs-target="#cancelModal">
      <i class="fal fa-times-circle me-2"></i>Reject করুন
    </button>
    <span style="font-size:11px;color:var(--text-muted)">
    </span>
  </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="border-radius:16px;border:none">
      <form action="<?= $base ?>/con-estimate-cancel/<?= $budget['id'] ?>" method="POST">
        <div class="modal-header" style="border-bottom:1px solid rgba(0,0,0,.07)">
          <h5 class="modal-title" style="font-size:14px;font-weight:800">
            <i class="fal fa-exclamation-triangle text-danger me-2"></i>Reject Budget
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <label class="form-label fw-bold" style="font-size:12px">
            Rejection Note <span class="text-danger">*</span>
          </label>
          <textarea name="rejection_note" class="form-control" rows="4" required
            style="border-radius:10px;font-size:13px"
            placeholder="Explain why this budget is being rejected…"></textarea>
        </div>
        <div class="modal-footer" style="border-top:1px solid rgba(0,0,0,.07)">
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-sm btn-danger px-4" style="border-radius:10px">Confirm Reject</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>


<!-- ═══════════════ TABBED CONTENT ═══════════════ -->
<div class="sib-tabs">
  <button class="sib-tab-btn active" onclick="sibTab(this,'tab-breakdown')">
    <i class="fal fa-layer-group me-1"></i>Design Breakdown
    <span class="badge ms-1" style="background:rgba(var(--clr-theme-primary-rgb),.12);color:var(--clr-theme-primary);font-size:9px"><?= count($breakdown) ?></span>
  </button>
  <button class="sib-tab-btn" onclick="sibTab(this,'tab-materials')">
    <i class="fal fa-boxes-stacked me-1"></i>Materials
    <span class="badge ms-1" style="background:rgba(16,185,129,.12);color:#10b981;font-size:9px"><?= count($items) ?></span>
  </button>
  <button class="sib-tab-btn" onclick="sibTab(this,'tab-labor')">
    <i class="fal fa-hard-hat me-1"></i>Labor
    <span class="badge ms-1" style="background:rgba(59,130,246,.12);color:#3b82f6;font-size:9px"><?= count($labors) ?></span>
  </button>
  <button class="sib-tab-btn" onclick="sibTab(this,'tab-plans')">
    <i class="fal fa-calendar-alt me-1"></i>Working Plans
    <span class="badge ms-1" style="background:rgba(245,158,11,.12);color:#d97706;font-size:9px"><?= count($workingPlans) ?></span>
  </button>
</div>

<!-- TAB 1: Design Breakdown -->
<div class="sib-tab-pane active" id="tab-breakdown">
  <?php if (empty($breakdown)): ?>
    <div class="empty-state"><i class="fal fa-layer-group"></i>No breakdown data available</div>
  <?php else: ?>
  <div class="row">
    <?php foreach ($breakdown as $bk): ?>
    <div class="col-lg-6">
      <div class="bkd-card">
        <div class="bkd-name">
          <i class="fal fa-layer-group me-2" style="color:var(--sib-purple)"></i>
          <?= htmlspecialchars($bk['design_type_name'] ?? 'N/A') ?>
        </div>
        <div class="bkd-row">
          <span style="color:var(--text-muted)"><i class="fal fa-boxes-stacked me-1"></i>Material (<?= $bk['item_count'] ?> items)</span>
          <span style="color:var(--sib-green);font-weight:700"><?= sib_fmt($bk['total_material']) ?></span>
        </div>
        <div class="bkd-row">
          <span style="color:var(--text-muted)"><i class="fal fa-hard-hat me-1"></i>Labor (<?= $bk['labor_count'] ?> rows)</span>
          <span style="color:var(--sib-blue);font-weight:700"><?= sib_fmt($bk['total_labor']) ?></span>
        </div>
        <div class="bkd-row" style="border-top:1px solid rgba(0,0,0,.08);margin-top:4px;padding-top:8px">
          <span>Design Type Total</span>
          <span style="color:var(--sib-purple)"><?= sib_fmt($bk['design_total']) ?></span>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- TAB 2: Material Items -->
<div class="sib-tab-pane" id="tab-materials">
  <?php if (empty($items)): ?>
    <div class="empty-state"><i class="fal fa-boxes-stacked"></i>No material items added</div>
  <?php else: ?>
  <div style="overflow-x:auto">
    <table class="sib-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Work Type</th>
          <th>Material</th>
          <th>Description</th>
          <th class="text-right">Unit</th>
          <th class="text-right">Est. Qty</th>
          <th class="text-right">Unit Cost</th>
          <th class="text-right">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        <?php $matGrand = 0; foreach ($items as $si => $item):
          $sub = $item['estimated_qty'] * $item['unit_cost_approx'];
          $matGrand += $sub;
        ?>
        <tr>
          <td style="color:var(--text-muted);font-size:11px"><?= $si+1 ?></td>
          <td>
            <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px;
              background:rgba(var(--clr-theme-secondary-rgb),.1);color:var(--clr-theme-secondary)">
              <?= htmlspecialchars($item['work_type_label'] ?? $item['design_type_name'] ?? '—') ?>
            </span>
            <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px;
              background:rgba(0,0,0,.06);color:#333;margin-left:4px;" title="Floor">
              <i class="fal fa-building me-1"></i><?= htmlspecialchars(sib_format_floor($item['floor_type'] ?? 'Typical_Floor', $item['floor_no'] ?? null)) ?>
            </span>
            <div style="font-size:10px;color:var(--text-muted);margin-top:4px;">
              <strong>Work Type ID:</strong> <?= (!empty($item['work_type_id']) && $item['work_type_id'] != 0) ? htmlspecialchars($item['work_type_id']) : '—' ?>
              <br>
              <strong>Work Type Name:</strong> <?= !empty($item['work_type_name']) ? htmlspecialchars($item['work_type_name']) : '—' ?>
            </div>
            <?php if (!empty($item['phase_name']) || !empty($item['phase_item_name'])): ?>
            <div style="font-size:10.5px;color:var(--text-muted);margin-top:4px;border-top:1px dashed rgba(0,0,0,0.05);padding-top:4px;">
              <span title="Phase" class="me-2"><i class="fal fa-sitemap me-1"></i><?= htmlspecialchars($item['phase_name'] ?? '—') ?></span>
              <br>
              <span title="Phase Item"><i class="fal fa-list-ol me-1"></i><?= htmlspecialchars($item['phase_item_name'] ?? '—') ?></span>
            </div>
            <?php endif; ?>
          </td>
          <td>
            <span style="font-weight:700"><?= htmlspecialchars($item['material_name'] ?? '—') ?></span>
            <br><small class="text-muted">[Category: <?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?>]</small>
          </td>
          <td style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($item['remarks'] ?? '—') ?></td>
          <td class="text-right" style="color:var(--text-muted);font-size:11px"><?= htmlspecialchars($item['material_unit'] ?? '') ?></td>
          <td class="text-right"><?= number_format($item['estimated_qty'], 3) ?></td>
          <td class="text-right"><?= sib_fmt($item['unit_cost_approx']) ?></td>
          <td class="text-right" style="font-weight:800;color:var(--sib-green)"><?= sib_fmt($sub) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr style="background:rgba(0,0,0,.03)">
          <td colspan="7" style="text-align:right;font-weight:800;padding:12px 10px">Material Total:</td>
          <td class="text-right" style="font-weight:900;color:var(--sib-green);font-size:14px"><?= sib_fmt($matGrand) ?></td>
        </tr>
      </tfoot>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- TAB 3: Labor -->
<div class="sib-tab-pane" id="tab-labor">
  <?php if (empty($labors)): ?>
    <div class="empty-state"><i class="fal fa-hard-hat"></i>No labor records added</div>
  <?php else: ?>
  <div style="overflow-x:auto">
    <table class="sib-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Work Type</th>
          <th>Labor Type</th>
          <th>Description</th>
          <th class="text-right">Rate / sqft</th>
          <th class="text-right">Area (sqft)</th>
          <th class="text-right">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        <?php $labGrand = 0; foreach ($labors as $li => $lb):
          $sub = $lb['rate_per_sqft'] * $lb['area_sqft'];
          $labGrand += $sub;
        ?>
        <tr>
          <td style="color:var(--text-muted);font-size:11px"><?= $li+1 ?></td>
          <td>
            <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px;
              background:rgba(59,130,246,.1);color:#3b82f6">
              <?= htmlspecialchars($lb['work_type_label'] ?? $lb['design_type_name'] ?? '—') ?>
            </span>
            <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px;
              background:rgba(0,0,0,.06);color:#333;margin-left:4px;" title="Floor">
              <i class="fal fa-building me-1"></i><?= htmlspecialchars(sib_format_floor($lb['floor_type'] ?? 'Typical_Floor', $lb['floor_no'] ?? null)) ?>
            </span>
            <div style="font-size:10px;color:var(--text-muted);margin-top:4px;">
              <strong>Work Type ID:</strong> <?= (!empty($lb['work_type_id']) && $lb['work_type_id'] != 0) ? htmlspecialchars($lb['work_type_id']) : '—' ?>
              <br>
              <strong>Work Type Name:</strong> <?= !empty($lb['work_type_name']) ? htmlspecialchars($lb['work_type_name']) : '—' ?>
            </div>
            <?php if (!empty($lb['phase_name']) || !empty($lb['phase_item_name'])): ?>
            <div style="font-size:10.5px;color:var(--text-muted);margin-top:4px;border-top:1px dashed rgba(0,0,0,0.05);padding-top:4px;">
              <span title="Phase" class="me-2"><i class="fal fa-sitemap me-1"></i><?= htmlspecialchars($lb['phase_name'] ?? '—') ?></span>
              <br>
              <span title="Phase Item"><i class="fal fa-list-ol me-1"></i><?= htmlspecialchars($lb['phase_item_name'] ?? '—') ?></span>
            </div>
            <?php endif; ?>
          </td>
          <td style="font-weight:600"><?= htmlspecialchars($lb['labor_type_name']) ?></td>
          <td style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($lb['description'] ?? '—') ?></td>
          <td class="text-right"><?= sib_fmt($lb['rate_per_sqft']) ?></td>
          <td class="text-right"><?= number_format($lb['area_sqft'], 2) ?></td>
          <td class="text-right" style="font-weight:800;color:var(--sib-blue)"><?= sib_fmt($sub) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr style="background:rgba(0,0,0,.03)">
          <td colspan="6" style="text-align:right;font-weight:800;padding:12px 10px">Labor Total:</td>
          <td class="text-right" style="font-weight:900;color:var(--sib-blue);font-size:14px"><?= sib_fmt($labGrand) ?></td>
        </tr>
      </tfoot>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- TAB 4: Working Plans -->
<div class="sib-tab-pane" id="tab-plans">
  <?php if (empty($workingPlans)): ?>
    <div class="empty-state">
      <i class="fal fa-calendar-alt"></i>
      No working plans linked to this budget yet.
      <div style="margin-top:12px">
        <a href="<?= $base ?>/con-working-plan-create?budget=<?= $budget['id'] ?>" class="btn btn-sm"
          style="background:linear-gradient(135deg,#1e40af,#3b82f6);color:#fff;border-radius:10px;font-size:12px;font-weight:700">
          <i class="fal fa-plus me-1"></i>Create Working Plan
        </a>
      </div>
    </div>
  <?php else: ?>

  <!-- ═══ Year+Month wise Cost Summary ═══ -->
  <?php if (!empty($monthlyPlanSummary)): ?>
  <?php
    // Group by year
    $byYear = [];
    foreach ($monthlyPlanSummary as $row) {
        $byYear[$row['year']][] = $row;
    }
    $grandTotal = array_sum(array_column($monthlyPlanSummary, 'total_cost'));
    $budgetTotal = (float)($budget['total_estimated_cost'] ?? 0);
    $progressPct = $budgetTotal > 0 ? min(100, round($grandTotal / $budgetTotal * 100, 1)) : 0;
  ?>
  <div class="sib-panel" style="margin-bottom:18px;">
    <div class="sib-panel-title">
      <div class="sib-dot" style="background:var(--sib-teal)"></div>
      Working Plan Cost Summary — Year &amp; Month Wise
      <span style="margin-left:auto;font-size:11px;font-weight:500;color:var(--text-muted);text-transform:none;letter-spacing:0">
        Grand Total: <strong style="color:var(--sib-teal)">৳<?= number_format($grandTotal, 2) ?></strong>
        &nbsp;/&nbsp; Budget: <strong>৳<?= number_format($budgetTotal, 2) ?></strong>
      </span>
    </div>

    <!-- Progress bar -->
    <div style="margin-bottom:14px;">
      <div style="display:flex;justify-content:space-between;font-size:11px;font-weight:700;margin-bottom:4px">
        <span style="color:var(--sib-teal)">Planned: <?= $progressPct ?>%</span>
        <span style="color:var(--text-muted)">৳<?= number_format($budgetTotal - $grandTotal, 2) ?> remaining</span>
      </div>
      <div style="height:8px;background:rgba(0,0,0,.07);border-radius:10px;overflow:hidden">
        <div style="height:100%;width:<?= $progressPct ?>%;background:linear-gradient(90deg,#14b8a6,#06b6d4);border-radius:10px;transition:width .4s"></div>
      </div>
    </div>

    <!-- Year-wise breakdown -->
    <?php foreach ($byYear as $year => $months): ?>
    <div style="margin-bottom:14px;">
      <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:1px;color:var(--sib-blue);padding:6px 10px;background:rgba(59,130,246,.06);border-radius:8px;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center">
        <span><i class="fal fa-calendar me-1"></i><?= $year ?></span>
        <span style="color:var(--text-muted)">Total: ৳<?= number_format(array_sum(array_column($months, 'total_cost')), 2) ?></span>
      </div>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:8px;padding:0 4px">
        <?php foreach ($months as $m): ?>
        <div style="background:var(--card-bg,#fff);border:1px solid rgba(0,0,0,.07);border-radius:10px;padding:10px 14px;position:relative;overflow:hidden">
          <div style="position:absolute;left:0;top:0;width:3px;height:100%;background:var(--sib-teal);border-radius:10px 0 0 10px"></div>
          <div style="font-size:11px;font-weight:700;color:var(--text-muted);margin-bottom:3px"><?= $monthNames[(int)$m['month']] ?></div>
          <div style="font-size:14px;font-weight:900;color:var(--sib-teal)">৳<?= number_format($m['total_cost'], 2) ?></div>
          <div style="font-size:10px;color:var(--text-muted);margin-top:2px"><?= $m['plan_count'] ?> plan<?= $m['plan_count'] > 1 ? 's' : '' ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div style="overflow-x:auto">
    <table class="sib-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Period</th>
          <th>Design Type</th>
          <th>Description</th>
          <th class="text-right">Planned Qty</th>
          <th class="text-right">Est. Cost</th>
          <th>Approval Status</th>
          <th>Approved By</th>
          <th>Created By</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($workingPlans as $wi => $wp):
          $wpStatus = $wp['approved_status'] ?? 'Pending';
          $wpClass  = match($wpStatus) {
              'Approved' => 'wp-approved',
              'Rejected' => 'wp-rejected',
              default    => 'wp-pending',
          };
          $wpIcon   = match($wpStatus) {
              'Approved' => 'fa-check-circle',
              'Rejected' => 'fa-times-circle',
              default    => 'fa-clock',
          };
        ?>
        <tr>
          <td style="color:var(--text-muted);font-size:11px"><?= $wi+1 ?></td>
          <td style="font-weight:700;font-size:12px;white-space:nowrap">
            <?= $monthNames[(int)($wp['month'] ?? 0)] ?? '?' ?>
            <span style="color:var(--text-muted)"><?= $wp['year'] ?? '' ?></span>
          </td>
          <td>
            <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px;
              background:rgba(var(--clr-theme-secondary-rgb),.1);color:var(--clr-theme-secondary)">
              <?= htmlspecialchars($wp['design_type_name'] ?? '—') ?>
            </span>
          </td>
          <td style="font-size:12px;max-width:200px">
            <?= htmlspecialchars(mb_strimwidth($wp['work_description'] ?? '', 0, 60, '…')) ?>
          </td>
          <td class="text-right"><?= number_format($wp['planned_quantity'] ?? 0, 2) ?></td>
          <td class="text-right" style="font-weight:700;color:var(--sib-teal)"><?= sib_fmt($wp['estimated_cost'] ?? 0) ?></td>
          <td>
            <span class="wp-status <?= $wpClass ?>">
              <i class="fal <?= $wpIcon ?>"></i><?= $wpStatus ?>
            </span>
          </td>
          <td style="font-size:11.5px;color:var(--text-muted)">
            <?php if (!empty($wp['approved_by_name'])): ?>
              <span style="color:#059669;font-weight:700"><?= htmlspecialchars($wp['approved_by_name']) ?></span>
              <?php if (!empty($wp['approved_by_role'])): ?>
                <br><span style="font-size:10px;font-style:italic;color:var(--text-muted)"><?= htmlspecialchars($wp['approved_by_role']) ?></span>
              <?php endif; ?>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
          <td style="font-size:11.5px;color:var(--text-muted)">
            <?= htmlspecialchars($wp['created_by_name'] ?? '—') ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr style="background:rgba(0,0,0,.03)">
          <td colspan="5" style="text-align:right;font-weight:800;padding:12px 10px">Total Planned Cost:</td>
          <td class="text-right" style="font-weight:900;color:var(--sib-teal);font-size:14px">
            <?= sib_fmt(array_sum(array_column($workingPlans, 'estimated_cost'))) ?>
          </td>
          <td colspan="3">
            <?php
            $apv = count(array_filter($workingPlans, fn($p)=>($p['approved_status']??'')=='Approved'));
            $pen = count(array_filter($workingPlans, fn($p)=>($p['approved_status']??'Pending')!='Approved'&&($p['approved_status']??'')!='Rejected'));
            ?>
            <span class="wp-status wp-approved me-1"><i class="fal fa-check-circle"></i><?= $apv ?> Approved</span>
            <span class="wp-status wp-pending"><i class="fal fa-clock"></i><?= $pen ?> Pending</span>
          </td>
        </tr>
      </tfoot>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Spent Details Modal -->
<div class="modal fade" id="spentDetailsModal" tabindex="-1" aria-labelledby="spentDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
      <div class="modal-header" style="background: linear-gradient(135deg, #0f5132 0%, #198754 100%); color: #fff; border-top-left-radius: 16px; border-top-right-radius: 16px; border-bottom: none; padding: 18px 24px;">
        <h5 class="modal-title id-title" id="spentDetailsModalLabel" style="font-weight: 800; font-size: 16px; display: flex; align-items: center; gap: 8px;">
          <i class="fal fa-file-invoice-dollar"></i> Cost Usage &amp; Spent Details
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="opacity: 0.8;"></button>
      </div>
      <div class="modal-body" style="padding: 24px;">
        
        <!-- Modal Tabs -->
        <ul class="nav nav-tabs spent-nav-tabs mb-4" id="spentModalTabs" role="tablist" style="border-bottom: 2px solid rgba(0,0,0,0.07);">
          <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold text-success" id="mat-tab" data-bs-toggle="tab" data-bs-target="#mat-pane" type="button" role="tab" aria-controls="mat-pane" aria-selected="true" style="border: none; border-bottom: 3px solid transparent; padding: 10px 20px; font-size: 13px;">
              <i class="fal fa-boxes-stacked me-1"></i> Received Materials (৳ <?= number_format($spentDetails['materials_total'] ?? 0, 2) ?>)
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold text-primary" id="con-tab" data-bs-toggle="tab" data-bs-target="#con-pane" type="button" role="tab" aria-controls="con-pane" aria-selected="false" style="border: none; border-bottom: 3px solid transparent; padding: 10px 20px; font-size: 13px;">
              <i class="fal fa-hard-hat me-1"></i> Contractor Payments (৳ <?= number_format($spentDetails['contractors_total'] ?? 0, 2) ?>)
            </button>
          </li>
        </ul>

        <div class="tab-content" id="spentModalTabsContent">
          <!-- Received Materials Tab Pane -->
          <div class="tab-pane fade show active" id="mat-pane" role="tabpanel" aria-labelledby="mat-tab">
            <?php if (empty($spentDetails['materials'])): ?>
              <div class="empty-state py-5 text-center text-muted">
                <i class="fal fa-boxes-stacked" style="font-size: 48px; opacity: 0.3; display: block; margin-bottom: 12px;"></i>
                No materials received for this project yet.
              </div>
            <?php else: ?>
              <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-hover align-middle custom-table">
                  <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                    <tr>
                      <th style="font-size: 11px; font-weight: 700; text-transform: uppercase;">GRN ID</th>
                      <th style="font-size: 11px; font-weight: 700; text-transform: uppercase;">Material Name</th>
                      <th style="font-size: 11px; font-weight: 700; text-transform: uppercase;" class="text-end">Qty Received</th>
                      <th style="font-size: 11px; font-weight: 700; text-transform: uppercase;" class="text-end">Unit Price</th>
                      <th style="font-size: 11px; font-weight: 700; text-transform: uppercase;" class="text-end">Total Value</th>
                      <th style="font-size: 11px; font-weight: 700; text-transform: uppercase;">Receiving Date</th>
                      <th style="font-size: 11px; font-weight: 700; text-transform: uppercase;">Received By</th>
                      <th style="font-size: 11px; font-weight: 700; text-transform: uppercase;">Notes</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($spentDetails['materials'] as $m): ?>
                      <tr>
                        <td class="fw-bold text-muted" style="font-size: 12px;">GRN-<?= (int)$m['grn_id'] ?></td>
                        <td class="fw-bold" style="font-size: 13px;"><?= htmlspecialchars($m['material_name']) ?></td>
                        <td class="text-end" style="font-size: 13px;"><?= number_format($m['received_quantity'], 3) ?> <small class="text-muted"><?= htmlspecialchars($m['material_unit']) ?></small></td>
                        <td class="text-end" style="font-size: 13px;">৳<?= number_format($m['unit_price'], 2) ?></td>
                        <td class="text-end fw-bold text-success" style="font-size: 13px;">৳<?= number_format($m['total_cost'], 2) ?></td>
                        <td style="font-size: 12px;"><?= date('d M Y', strtotime($m['receiving_date'])) ?></td>
                        <td style="font-size: 12px;"><?= htmlspecialchars($m['receiver_name'] ?? '—') ?></td>
                        <td class="text-muted" style="font-size: 12px; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($m['receiving_notes'] ?? '') ?>">
                          <?= htmlspecialchars($m['receiving_notes'] ?? '—') ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>

          <!-- Contractor Payments Tab Pane -->
          <div class="tab-pane fade" id="con-pane" role="tabpanel" aria-labelledby="con-tab">
            <?php if (empty($spentDetails['contractors'])): ?>
              <div class="empty-state py-5 text-center text-muted">
                <i class="fal fa-hard-hat" style="font-size: 48px; opacity: 0.3; display: block; margin-bottom: 12px;"></i>
                No contractor payments found for this project yet.
              </div>
            <?php else: ?>
              <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-hover align-middle custom-table">
                  <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                    <tr>
                      <th style="font-size: 11px; font-weight: 700; text-transform: uppercase;">Contractor Name</th>
                      <th style="font-size: 11px; font-weight: 700; text-transform: uppercase;">Payment Purpose (Type)</th>
                      <th style="font-size: 11px; font-weight: 700; text-transform: uppercase;">Stage</th>
                      <th style="font-size: 11px; font-weight: 700; text-transform: uppercase;">Payment Method</th>
                      <th style="font-size: 11px; font-weight: 700; text-transform: uppercase;" class="text-end">Amount Paid</th>
                      <th style="font-size: 11px; font-weight: 700; text-transform: uppercase;">Transaction Date</th>
                      <th style="font-size: 11px; font-weight: 700; text-transform: uppercase;">Processed By</th>
                      <th style="font-size: 11px; font-weight: 700; text-transform: uppercase;">Reference / Notes</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($spentDetails['contractors'] as $c): ?>
                      <?php 
                      // Map bill_type enum: Advance, Progress (After Work), Retention (Remite)
                      $purpose = match(trim($c['bill_type'])) {
                          'Advance' => '<span class="badge bg-warning text-dark"><i class="fal fa-forward me-1"></i>Advance</span>',
                          'Progress' => '<span class="badge bg-primary"><i class="fal fa-tasks me-1"></i>After Work</span>',
                          'Retention' => '<span class="badge bg-info text-dark"><i class="fal fa-shield-check me-1"></i>Retention / Remite</span>',
                          default => '<span class="badge bg-secondary">' . htmlspecialchars($c['bill_type']) . '</span>'
                      };
                      ?>
                      <tr>
                        <td class="fw-bold" style="font-size: 13px;"><?= htmlspecialchars($c['contractor_name']) ?></td>
                        <td><?= $purpose ?></td>
                        <td style="font-size: 12px;"><?= htmlspecialchars($c['payment_type'] ?? '—') ?></td>
                        <td style="font-size: 12px;"><i class="fal fa-credit-card me-1"></i><?= htmlspecialchars($c['payment_method']) ?></td>
                        <td class="text-end fw-bold text-primary" style="font-size: 13px;">৳<?= number_format($c['amount_paid'], 2) ?></td>
                        <td style="font-size: 12px;"><?= date('d M Y', strtotime($c['transaction_date'])) ?></td>
                        <td style="font-size: 12px;"><?= htmlspecialchars($c['processed_by_name'] ?? '—') ?></td>
                        <td style="font-size: 12px;">
                          <?php if (!empty($c['reference_number'])): ?>
                            <span class="badge bg-light text-dark border">Ref: <?= htmlspecialchars($c['reference_number']) ?></span>
                          <?php endif; ?>
                          <div class="text-muted small text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($c['txn_notes'] ?? '') ?>">
                            <?= htmlspecialchars($c['txn_notes'] ?? '—') ?>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>

      </div>
      <div class="modal-footer" style="border-top: 1px solid rgba(0,0,0,0.07); padding: 16px 24px;">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" style="border-radius: 8px;">Close</button>
      </div>
    </div>
  </div>
</div>

<div style="height:30px"></div>

<script>
function sibTab(btn, tabId) {
    document.querySelectorAll('.sib-tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.sib-tab-pane').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById(tabId).classList.add('active');
}
</script>