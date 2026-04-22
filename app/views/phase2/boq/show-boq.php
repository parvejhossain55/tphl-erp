<?php
$base        = $base_url;
$boq         = $boq         ?? [];
$items       = $items       ?? [];
$canApprove  = $canApprove  ?? false;
$creatorInfo = $creatorInfo ?? [];

function boqFmt($n) {
    $n = (float)$n;
    if ($n >= 10000000) return '৳' . number_format($n/10000000, 2) . ' Cr';
    if ($n >= 100000)   return '৳' . number_format($n/100000,   2) . ' L';
    if ($n >= 1000)     return '৳' . number_format($n/1000,     1) . 'K';
    return '৳' . number_format($n, 2);
}

$grandTotal = array_sum(array_column($items, 'total_cost'));
$status     = $boq['status'] ?? 'Draft';
$statusMap  = [
    'Approved'  => ['bg' => 'rgba(16,185,129,.2)',  'text' => '#059669', 'icon' => 'fa-check-circle'],
    'Rejected'  => ['bg' => 'rgba(239,68,68,.2)',   'text' => '#dc2626', 'icon' => 'fa-times-circle'],
    'Submitted' => ['bg' => 'rgba(59,130,246,.2)',  'text' => '#2563eb', 'icon' => 'fa-paper-plane'],
    'Draft'     => ['bg' => 'rgba(245,158,11,.2)',  'text' => '#d97706', 'icon' => 'fa-pencil-alt'],
];
$sc = $statusMap[$status] ?? $statusMap['Draft'];
?>
<style>
:root {
  --boq-primary:#198754; --boq-dark:#0f5132; --boq-blue:#3b82f6;
  --boq-green:#10b981;   --boq-amber:#f59e0b; --boq-red:#ef4444;
  --boq-purple:#8b5cf6;  --boq-indigo:#6366f1;
}

/* Hero */
.boq-hero {
  background:linear-gradient(135deg,#0f5132 0%,#198754 55%,#20a86a 100%);
  border-radius:18px; padding:26px 30px; margin-bottom:22px;
  position:relative; overflow:hidden;
}
.boq-hero::before{content:'';position:absolute;width:280px;height:280px;
  border-radius:50%;background:rgba(255,255,255,.04);top:-90px;right:-70px;}
.boq-hero::after{content:'';position:absolute;width:160px;height:160px;
  border-radius:50%;background:rgba(255,255,255,.03);bottom:-50px;left:-30px;}
.boq-hero-inner{position:relative;z-index:2;}
.boq-badge{display:inline-flex;align-items:center;gap:6px;
  background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);
  padding:4px 12px;border-radius:20px;font-size:10.5px;font-weight:700;
  letter-spacing:1.4px;text-transform:uppercase;color:#fff;margin-bottom:12px;}
.boq-hero-title{font-size:24px;font-weight:900;color:#fff;margin:0 0 5px;}
.boq-hero-sub{font-size:12.5px;color:rgba(255,255,255,.65);margin:0;}
.boq-btn-w{background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.28);
  color:#fff;border-radius:10px;padding:7px 18px;font-size:12px;font-weight:700;
  text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:background .18s;}
.boq-btn-w:hover{background:rgba(255,255,255,.25);color:#fff;text-decoration:none;}

/* KPI */
.boq-kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px;}
@media(max-width:992px){.boq-kpi-grid{grid-template-columns:repeat(2,1fr);}}
.boq-kpi{border-radius:14px;padding:18px 20px;border:1px solid rgba(0,0,0,.08);
  background:var(--card-bg,#fff);position:relative;overflow:hidden;}
.boq-kpi-accent{position:absolute;left:0;top:0;width:4px;height:100%;border-radius:14px 0 0 14px;}
.boq-kpi-icon{width:40px;height:40px;border-radius:11px;display:flex;align-items:center;
  justify-content:center;font-size:17px;margin-bottom:11px;}
.boq-kpi-val{font-size:20px;font-weight:900;line-height:1;margin-bottom:3px;}
.boq-kpi-lbl{font-size:10px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--text-muted);}

/* Panel */
.boq-panel{border-radius:14px;padding:22px;border:1px solid rgba(0,0,0,.08);
  background:var(--card-bg,#fff);margin-bottom:20px;}
.boq-panel-title{font-size:11px;font-weight:700;letter-spacing:1.5px;
  text-transform:uppercase;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.boq-dot{width:8px;height:8px;border-radius:50%;}

/* Table */
.boq-table{width:100%;border-collapse:collapse;}
.boq-table th{font-size:10px;letter-spacing:1.3px;text-transform:uppercase;
  font-weight:600;padding:0 12px 10px;border-bottom:2px solid rgba(0,0,0,.07);text-align:left;}
.boq-table td{padding:12px;border-bottom:1px solid rgba(0,0,0,.05);font-size:12.5px;vertical-align:middle;}
.boq-table tr:last-child td{border-bottom:none;}
.boq-table .tr{text-align:right;}

/* Approval card */
.boq-action-card{border-radius:14px;padding:22px;
  border:2px solid rgba(245,158,11,.3);background:rgba(245,158,11,.04);margin-bottom:20px;}

.empty-state{text-align:center;padding:32px 0;color:var(--text-muted);}
.empty-state i{font-size:32px;opacity:.25;display:block;margin-bottom:8px;}

@media print {
  .boq-hero .boq-btn-w, .boq-action-card { display:none!important; }
}
</style>

<!-- ══════════ HERO ══════════ -->
<div class="boq-hero">
  <div class="boq-hero-inner">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:14px">
      <div>
        <div class="boq-badge"><i class="fal fa-clipboard-list"></i>Bill of Quantities (BOQ)</div>
        <h4 class="boq-hero-title"><?= htmlspecialchars($boq['project_name'] ?? 'N/A') ?></h4>
        <p class="boq-hero-sub">
          <i class="fal fa-code-branch me-1"></i>Version <?= htmlspecialchars($boq['version'] ?? '1.0') ?>
          &nbsp;·&nbsp;
          <i class="fal fa-calendar me-1"></i><?= !empty($boq['created_at']) ? date('d M Y', strtotime($boq['created_at'])) : '—' ?>
        </p>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px">
          <?php if ($status !== 'Approved'): ?>
          <a href="<?= $base ?>/con-boq-edit/<?= $boq['id'] ?>" class="boq-btn-w">
            <i class="fal fa-pencil"></i>Edit BOQ
          </a>
          <?php endif; ?>
          <a href="<?= $base ?>/con-boq" class="boq-btn-w">
            <i class="fal fa-arrow-left"></i>Back to List
          </a>
          <button onclick="window.print()" class="boq-btn-w" style="border:none;cursor:pointer">
            <i class="fal fa-print"></i>Print
          </button>
        </div>
      </div>
      <!-- Status pill -->
      <div style="text-align:right">
        <div style="display:inline-flex;align-items:center;gap:7px;font-size:13px;font-weight:700;
          padding:8px 18px;border-radius:20px;background:<?= $sc['bg'] ?>;color:<?= $sc['text'] ?>">
          <i class="fal <?= $sc['icon'] ?>"></i><?= $status ?>
        </div>
        <?php if ($status === 'Approved' && !empty($boq['approved_by_name'])): ?>
        <div style="font-size:11px;color:rgba(255,255,255,.65);margin-top:8px">
          <i class="fal fa-user-check me-1"></i>
          Approved by <strong style="color:#fff"><?= htmlspecialchars($boq['approved_by_name']) ?></strong><br>
          <i class="fal fa-calendar me-1"></i><?= !empty($boq['approved_at']) ? date('d M Y', strtotime($boq['approved_at'])) : '' ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ══════════ KPI GRID ══════════ -->
<div class="boq-kpi-grid">
  <div class="boq-kpi">
    <div class="boq-kpi-accent" style="background:var(--boq-green)"></div>
    <div class="boq-kpi-icon" style="background:rgba(16,185,129,.12);color:var(--boq-green)">
      <i class="fal fa-coins"></i>
    </div>
    <div class="boq-kpi-lbl">Grand Total</div>
    <div class="boq-kpi-val" style="color:var(--boq-green)"><?= boqFmt($grandTotal) ?></div>
  </div>
  <div class="boq-kpi">
    <div class="boq-kpi-accent" style="background:var(--boq-blue)"></div>
    <div class="boq-kpi-icon" style="background:rgba(59,130,246,.12);color:var(--boq-blue)">
      <i class="fal fa-boxes-stacked"></i>
    </div>
    <div class="boq-kpi-lbl">Total Items</div>
    <div class="boq-kpi-val" style="color:var(--boq-blue)"><?= count($items) ?></div>
  </div>
  <div class="boq-kpi">
    <div class="boq-kpi-accent" style="background:var(--boq-purple)"></div>
    <div class="boq-kpi-icon" style="background:rgba(139,92,246,.12);color:var(--boq-purple)">
      <i class="fal fa-code-branch"></i>
    </div>
    <div class="boq-kpi-lbl">Version</div>
    <div class="boq-kpi-val" style="color:var(--boq-purple)">v<?= htmlspecialchars($boq['version'] ?? '1.0') ?></div>
  </div>
  <div class="boq-kpi">
    <div class="boq-kpi-accent" style="background:<?= $sc['text'] ?>"></div>
    <div class="boq-kpi-icon" style="background:<?= $sc['bg'] ?>;color:<?= $sc['text'] ?>">
      <i class="fal <?= $sc['icon'] ?>"></i>
    </div>
    <div class="boq-kpi-lbl">Status</div>
    <div class="boq-kpi-val" style="color:<?= $sc['text'] ?>"><?= $status ?></div>
  </div>
</div>

<!-- ══════════ APPROVAL ACTION (only PM, not creator) ══════════ -->
<?php if ($canApprove && in_array($status, ['Draft','Submitted'])): ?>
<div class="boq-action-card">

  <!-- Creator Info Banner -->
  <?php if (!empty($creatorInfo)): ?>
  <div style="display:flex;align-items:center;gap:14px;padding:12px 16px;border-radius:10px;
    background:rgba(99,102,241,.06);border:1px solid rgba(99,102,241,.15);margin-bottom:16px">
    <div style="width:38px;height:38px;border-radius:50%;background:rgba(99,102,241,.12);
      display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <i class="fal fa-user" style="color:#6366f1;font-size:16px"></i>
    </div>
    <div>
      <div style="font-size:13px;font-weight:800;color:#6366f1">
        <?= htmlspecialchars($creatorInfo['full_name'] ?? 'Unknown') ?>
      </div>
      <div style="font-size:11px;color:var(--text-muted)">
        <?= htmlspecialchars($creatorInfo['designation'] ?: 'Site Engineer') ?>
        &nbsp;·&nbsp;
        <i class="fal fa-calendar me-1"></i>
        Submitted <?= !empty($boq['created_at']) ? date('d M Y, h:i A', strtotime($boq['created_at'])) : '—' ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="boq-panel-title" style="margin-bottom:14px">
    <div class="boq-dot" style="background:var(--boq-amber)"></div>
    BOQ Approval — Your Action Required
  </div>
  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
    <form action="<?= $base ?>/con-boq-approve/<?= $boq['id'] ?>" method="POST">
      <input type="hidden" name="status" value="Approved">
      <button type="submit"
        style="background:linear-gradient(135deg,#059669,#10b981);color:#fff;border:none;
          border-radius:10px;padding:8px 20px;font-size:12px;font-weight:700;cursor:pointer"
        onclick="return confirm('Approve this BOQ? This cannot be undone.')">
        <i class="fal fa-check-circle me-2"></i>Approve BOQ
      </button>
    </form>
    <button type="button"
      style="background:rgba(239,68,68,.1);color:#dc2626;border:1px solid rgba(239,68,68,.3);
        border-radius:10px;padding:8px 20px;font-size:12px;font-weight:700;cursor:pointer"
      data-bs-toggle="modal" data-bs-target="#rejectBoqModal">
      <i class="fal fa-times-circle me-2"></i>Reject BOQ
    </button>
    <span style="font-size:11px;color:var(--text-muted)">
      <i class="fal fa-info-circle me-1"></i>Once approved, items cannot be edited.
    </span>
  </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectBoqModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="border-radius:16px;border:none">
      <form action="<?= $base ?>/con-boq-approve/<?= $boq['id'] ?>" method="POST">
        <input type="hidden" name="status" value="Rejected">
        <div class="modal-header" style="border-bottom:1px solid rgba(0,0,0,.07)">
          <h5 class="modal-title" style="font-size:14px;font-weight:800">
            <i class="fal fa-exclamation-triangle text-danger me-2"></i>Reject BOQ
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p style="font-size:12.5px;color:var(--text-muted)">
            Are you sure you want to reject the BOQ for
            <strong><?= htmlspecialchars($boq['project_name'] ?? '') ?></strong>?
          </p>
        </div>
        <div class="modal-footer" style="border-top:1px solid rgba(0,0,0,.07)">
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-sm btn-danger px-4" style="border-radius:10px">Confirm Reject</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ══════════ SE: Submit for Review (only for SE on Draft) ══════════ -->
<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$viewerRole = (int)($_SESSION['user']['role_id'] ?? 0);
if ($viewerRole === 12 && $status === 'Draft'):
?>
<div class="boq-panel" style="border-color:rgba(59,130,246,.3);background:rgba(59,130,246,.03);margin-bottom:20px">
  <div class="boq-panel-title" style="margin-bottom:14px">
    <div class="boq-dot" style="background:var(--boq-blue)"></div>
    Submit for PM Approval
  </div>
  <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
    <form action="<?= $base ?>/con-boq-approve/<?= $boq['id'] ?>" method="POST">
      <input type="hidden" name="status" value="Submitted">
      <button type="submit"
        style="background:linear-gradient(135deg,#1e40af,#3b82f6);color:#fff;border:none;
          border-radius:10px;padding:8px 20px;font-size:12px;font-weight:700;cursor:pointer"
        onclick="return confirm('Submit this BOQ for PM approval?')">
        <i class="fal fa-paper-plane me-2"></i>Submit for Approval
      </button>
    </form>
    <span style="font-size:11px;color:var(--text-muted)">
      <i class="fal fa-info-circle me-1"></i>The Project Manager will review and approve this BOQ.
    </span>
  </div>
</div>
<?php endif; ?>

<!-- ══════════ ITEMS TABLE ══════════ -->
<div class="boq-panel">
  <div class="boq-panel-title">
    <div class="boq-dot" style="background:var(--boq-green)"></div>
    Bill of Quantities — Material Items
    <span style="margin-left:auto;font-size:9px;font-weight:700;padding:2px 8px;border-radius:10px;
      background:rgba(16,185,129,.12);color:var(--boq-green)"><?= count($items) ?> Items</span>
  </div>

  <?php if (empty($items)): ?>
  <div class="empty-state"><i class="fal fa-boxes-stacked"></i>No items added to this BOQ.</div>
  <?php else: ?>
  <div style="overflow-x:auto">
    <table class="boq-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Material</th>
          <th>Unit</th>
          <th style="text-align:right">Quantity</th>
          <th style="text-align:right">Unit Cost</th>
          <th style="text-align:right">Total Cost</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $i => $item): ?>
        <tr>
          <td style="font-size:11px;color:var(--text-muted)"><?= $i+1 ?></td>
          <td style="font-weight:700"><?= htmlspecialchars($item['material_name']) ?></td>
          <td style="font-size:11.5px;color:var(--text-muted)"><?= htmlspecialchars($item['unit']) ?></td>
          <td style="text-align:right"><?= number_format($item['quantity'], 3) ?></td>
          <td style="text-align:right"><?= boqFmt($item['unit_cost']) ?></td>
          <td style="text-align:right;font-weight:800;color:var(--boq-green)"><?= boqFmt($item['total_cost']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr style="background:rgba(0,0,0,.03)">
          <td colspan="5" style="text-align:right;font-weight:800;padding:14px 12px;font-size:12px">
            GRAND TOTAL ESTIMATED COST:
          </td>
          <td style="text-align:right;font-weight:900;font-size:18px;color:var(--boq-primary,#198754);padding:14px 12px">
            <?= boqFmt($grandTotal) ?>
          </td>
        </tr>
      </tfoot>
    </table>
  </div>
  <?php endif; ?>
</div>

<div style="height:30px"></div>
