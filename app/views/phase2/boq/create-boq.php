<?php
$base_url         = $GLOBALS['base_url'];
$page_title       = $page_title ?? 'New Bill of Quantities';
$months           = ['','January','February','March','April','May','June',
                    'July','August','September','October','November','December'];
$phaseTarget      = $phaseTarget      ?? null;
$targetPhaseItems = $targetPhaseItems ?? [];
$weeklyProgress   = $weeklyProgress   ?? [];
$existingBoqCount = $existingBoqCount ?? 0;
$target_id        = $target_id        ?? 0;
$weekPlanQty      = [1 => 0.0, 2 => 0.0, 3 => 0.0, 4 => 0.0]; // default — overridden below if phaseTarget exists

// ── Pre-compute KPIs when phaseTarget exists ──────────────────────────────
if ($phaseTarget) {
    $totalItems  = count($targetPhaseItems);
    $avgPlan     = $totalItems > 0
        ? array_sum(array_column($targetPhaseItems, 'planned_progress_pct')) / $totalItems
        : 0;
    $avgActual   = $totalItems > 0
        ? array_sum(array_column($targetPhaseItems, 'actual_progress_pct')) / $totalItems
        : 0;
    $weeksLogged = count(array_filter(
        $weeklyProgress,
        fn($w) => (float)($w['total_achieved_qty'] ?? 0) > 0 || (($w['entry_status'] ?? 'Draft') !== 'Draft')
    ));
    // Pre-calculate planned qty per week from phase_monthly_target_items
    $weekPlanQty = [1 => 0.0, 2 => 0.0, 3 => 0.0, 4 => 0.0];
    foreach ($targetPhaseItems as $_pi) {
        $weekPlanQty[1] += (float)($_pi['week_1_qty'] ?? 0);
        $weekPlanQty[2] += (float)($_pi['week_2_qty'] ?? 0);
        $weekPlanQty[3] += (float)($_pi['week_3_qty'] ?? 0);
        $weekPlanQty[4] += (float)($_pi['week_4_qty'] ?? 0);
    }
    $monthLabel  = date('F Y', strtotime($phaseTarget['target_month']));
    $psColors    = [
        'Draft'     => 'rgba(148,163,184,.18)',
        'In Review' => 'rgba(245,158,11,.22)',
        'Approved'  => 'rgba(16,185,129,.22)',
    ];
    $psBg = $psColors[$phaseTarget['planning_status'] ?? 'Draft'] ?? 'rgba(148,163,184,.18)';
}
?>

<!-- ═══════════════════════════════ PAGE TITLE ═══════════════════════════════ -->
<div class="pagetitle d-flex justify-content-between align-items-center">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-boq">BOQ</a></li>
                <li class="breadcrumb-item active">New BOQ</li>
            </ol>
        </nav>
    </div>
    <a href="<?= $base_url ?>/con-boq" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<section class="section">

<?php if ($phaseTarget): ?>
<!-- ══════════════ PHASE HERO PANEL ══════════════ -->
<div style="background:linear-gradient(135deg,var(--clr-theme-primary) 0%,var(--clr-theme-primary) 55%,#818cf8 100%);
            border-radius:16px;padding:24px 28px;margin-bottom:24px;
            position:relative;overflow:hidden;box-shadow:0 8px 32px rgba(var(--clr-theme-primary-rgb),.28);">
    <!-- Decorative circles -->
    <div style="position:absolute;width:300px;height:300px;border-radius:50%;
                background:rgba(255,255,255,.05);top:-90px;right:-70px;pointer-events:none;"></div>
    <div style="position:absolute;width:180px;height:180px;border-radius:50%;
                background:rgba(255,255,255,.04);bottom:-60px;left:-40px;pointer-events:none;"></div>

    <div style="position:relative;z-index:1;">
        <!-- Top row: title + badges -->
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;margin-bottom:18px;">
            <div>
                <div style="font-size:9.5px;font-weight:700;letter-spacing:2px;text-transform:uppercase;
                            color:rgba(255,255,255,.65);margin-bottom:8px;">
                    <i class="bi bi-bullseye me-1"></i>Phase Monthly Target — BOQ তৈরি করুন
                </div>
                <h5 style="font-size:22px;font-weight:900;color:#fff;margin:0 0 6px;line-height:1.2;">
                    <?= htmlspecialchars($phaseTarget['phase_name']) ?>
                    <span style="font-size:14px;font-weight:600;opacity:.75;margin-left:8px;">— <?= $monthLabel ?></span>
                </h5>
                <div style="font-size:12.5px;color:rgba(255,255,255,.72);display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
                    <span><i class="bi bi-building me-1"></i><?= htmlspecialchars($phaseTarget['project_name']) ?></span>
                    <?php if ($phaseTarget['phase_weight'] ?? 0): ?>
                    <span>· Phase Weight: <strong style="color:#fff;"><?= number_format((float)$phaseTarget['phase_weight'], 0) ?>%</strong></span>
                    <?php endif; ?>
                    <?php if (($phaseTarget['planned_start_date'] ?? '') && ($phaseTarget['planned_end_date'] ?? '')): ?>
                    <span>· <i class="bi bi-calendar-range me-1"></i>
                        <?= date('d M Y', strtotime($phaseTarget['planned_start_date'])) ?>
                        → <?= date('d M Y', strtotime($phaseTarget['planned_end_date'])) ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;">
                <span style="padding:5px 16px;border-radius:20px;font-size:11px;font-weight:700;
                             background:<?= $psBg ?>;color:#fff;border:1px solid rgba(255,255,255,.28);">
                    <?= htmlspecialchars($phaseTarget['planning_status'] ?? 'Draft') ?>
                </span>
                <?php if ($existingBoqCount > 0): ?>
                <span style="padding:4px 12px;border-radius:20px;font-size:10px;font-weight:700;
                             background:rgba(245,158,11,.22);color:#fcd34d;border:1px solid rgba(245,158,11,.3);">
                    <i class="bi bi-exclamation-triangle me-1"></i><?= $existingBoqCount ?>টি BOQ আগে তৈরি — নতুন batch
                </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- KPI mini grid -->
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;">
            <?php
            $kpis = [
                [$totalItems,                          'Work Items',   '#fff'],
                [number_format($avgPlan,   1).'%',     'Avg Planned',  '#fff'],
                [number_format($avgActual, 1).'%',     'Avg Actual',   $avgActual >= $avgPlan ? '#6ee7b7' : '#fcd34d'],
                [$weeksLogged.'/4',                    'Weeks Logged', '#fff'],
            ];
            foreach ($kpis as [$val, $lbl, $col]): ?>
            <div style="background:rgba(255,255,255,.13);border-radius:10px;padding:11px 14px;text-align:center;
                        border:1px solid rgba(255,255,255,.08);">
                <div style="font-size:19px;font-weight:900;color:<?= $col ?>;"><?= $val ?></div>
                <div style="font-size:9.5px;color:rgba(255,255,255,.65);font-weight:600;margin-top:2px;text-transform:uppercase;letter-spacing:.5px;">
                    <?= $lbl ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ════════════════════════ MAIN FORM CARD ════════════════════════ -->
<div class="row justify-content-center">
    <div class="col-lg-12">
        <div class="card__wrapper shadow-sm">
            <div class="card__header" style="background-color:#f8f9fa;">
                <h6 class="card__title p-1 text-muted">
                    <i class="bi bi-clipboard-data me-2 text-primary"></i>Create Bill of Quantities (BOQ)
                </h6>
            </div>
            <div class="card__body p-4 border-top">
                <form action="<?= $base_url ?>/con-boq-store" method="POST" id="boqForm">

                    <!-- Hidden: target_id -->
                    <input type="hidden" name="target_id"  id="target_id_hidden"  value="<?= $target_id ?: '' ?>">
                    <input type="hidden" name="design_type_id" value="">

                    <!-- ── Row 1: Project + Status ── -->
                    <div class="row mb-3">
                        <div class="col-md-5">
                            <label class="form-label fw-bold">Project <span class="text-danger">*</span></label>
                            <?php if ($phaseTarget): ?>
                                <input type="hidden" name="project_id" value="<?= (int)$phaseTarget['project_id'] ?>">
                                <select id="project_id" class="form-select" disabled>
                                    <?php foreach ($projects as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= ($phaseTarget['project_id'] == $p['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <select name="project_id" id="project_id" class="form-select" required>
                                    <option value="">-- Select Project --</option>
                                    <?php foreach ($projects as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3 d-none">
                            <label class="form-label fw-bold">Version</label>
                            <input type="text" name="version" class="form-control" value="1.0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Initial Status</label>
                            <select name="status" class="form-select">
                                <option value="Draft">Draft</option>
                                <option value="Submitted">Submitted</option>
                            </select>
                        </div>
                    </div>

                    <!-- ── Row 2: Target Selection (only shown when NO phaseTarget pre-selected) ── -->
                    <?php if (!$phaseTarget): ?>
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">
                                <i class="bi bi-bullseye me-1 text-primary"></i>
                                Linked Target
                                <span class="text-muted fw-normal" style="font-size:11px">(optional — select project first)</span>
                            </label>
                            <select id="target_select" class="form-select" disabled>
                                <option value="">-- Select Project First --</option>
                            </select>
                            <div id="target_loading" class="text-muted small mt-1" style="display:none">
                                <i class="bi bi-hourglass-split me-1"></i> Loading targets...
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">
                                <i class="bi bi-layers me-1 text-info"></i>Design Type
                            </label>
                            <input type="text" id="design_type_display" class="form-control bg-light" readonly
                                   placeholder="Auto-filled from target">
                        </div>
                    </div>
                    <!-- Target Info Card (dynamic, shown on target select) -->
                    <div id="targetInfoCard" class="alert alert-info d-none mb-3 py-2" style="font-size:12.5px">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>Selected Target:</strong>
                        <span id="targetInfoText">—</span>
                    </div>
                    <?php endif; ?>

<?php if (!empty($targetPhaseItems)): ?>
<!-- ══════════════ PHASE ITEMS PANEL ══════════════ -->
<div class="mb-3" style="border:1.5px solid rgba(var(--clr-theme-primary-rgb),.2);border-radius:10px;overflow:hidden;">
    <div style="background:linear-gradient(90deg,rgba(var(--clr-theme-primary-rgb),.08),transparent);
                padding:10px 16px;border-bottom:1px solid rgba(var(--clr-theme-primary-rgb),.1);
                display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
        <div style="font-size:12.5px;font-weight:700;color:var(--clr-theme-primary);">
            <i class="bi bi-list-check me-2"></i>
            এই Target এর Work Items (<?= count($targetPhaseItems) ?>টি)
            <span style="font-size:11px;font-weight:500;color:var(--text-muted);margin-left:6px;">
                — এই items গুলোর জন্য BOQ তৈরি করা হচ্ছে
            </span>
        </div>
        <?php if ($existingBoqCount > 0): ?>
        <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px;
                     background:rgba(245,158,11,.12);color:#d97706;border:1px solid rgba(245,158,11,.25);">
            <i class="bi bi-exclamation-triangle me-1"></i>
            আগে <?= $existingBoqCount ?>টি BOQ তৈরি হয়েছে — এটা নতুন batch
        </span>
        <?php endif; ?>
    </div>

    <?php
    $th = 'padding:6px 10px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#64748b;border-bottom:2px solid rgba(0,0,0,.07);white-space:nowrap;background:rgba(0,0,0,.02);';
    $td = 'padding:7px 10px;border-bottom:1px solid rgba(0,0,0,.05);font-size:12px;vertical-align:middle;';
    $priColors = ['High'=>'#ef4444','Medium'=>'#f59e0b','Low'=>'#10b981'];
    $stColors  = ['Draft'=>'#94a3b8','Ready'=>'var(--clr-theme-primary)','Approved'=>'#10b981','At Risk'=>'#f59e0b','Critical'=>'#ef4444'];
    ?>
    <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;font-size:12px;">
        <thead>
        <tr>
            <th style="<?= $th ?>">#</th>
            <th style="<?= $th ?>">Work Item</th>
            <th style="<?= $th ?>">Work Type</th>
            <th style="<?= $th ?>">Unit</th>
            <th style="<?= $th ?>;text-align:center">Target Qty</th>
            <th style="<?= $th ?>;text-align:center">Actual Qty</th>
            <th style="<?= $th ?>;text-align:center">Planned %</th>
            <th style="<?= $th ?>;text-align:center">Actual %</th>
            <th style="<?= $th ?>;text-align:center">Wk 1</th>
            <th style="<?= $th ?>;text-align:center">Wk 2</th>
            <th style="<?= $th ?>;text-align:center">Wk 3</th>
            <th style="<?= $th ?>;text-align:center">Wk 4</th>
            <th style="<?= $th ?>">Priority</th>
            <th style="<?= $th ?>">Status</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($targetPhaseItems as $idx => $pi):
            $actPct  = (float)($pi['actual_progress_pct'] ?? 0);
            $planPct = (float)($pi['planned_progress_pct'] ?? 0);
            $priColor = $priColors[$pi['priority'] ?? 'Medium'] ?? '#64748b';
            $stColor  = $stColors[$pi['status'] ?? 'Draft'] ?? '#94a3b8';
            $rowBg = $idx % 2 === 0 ? '' : 'background:rgba(0,0,0,.015)';
        ?>
        <tr style="<?= $rowBg ?>">
            <td style="<?= $td ?>;color:#94a3b8;font-weight:700;"><?= $idx + 1 ?></td>
            <td style="<?= $td ?>;font-weight:700;"><?= htmlspecialchars($pi['work_item_name']) ?></td>
            <td style="<?= $td ?>;color:#64748b;"><?= htmlspecialchars($pi['work_type_name'] ?? '—') ?></td>
            <td style="<?= $td ?>;font-weight:600;color:var(--clr-theme-primary);"><?= htmlspecialchars($pi['unit'] ?? '—') ?></td>
            <td style="<?= $td ?>;text-align:center;font-weight:700;"><?= number_format((float)($pi['monthly_target_qty'] ?? 0), 2) ?></td>
            <td style="<?= $td ?>;text-align:center;font-weight:700;color:#10b981;"><?= number_format((float)($pi['actual_qty'] ?? 0), 2) ?></td>
            <td style="<?= $td ?>;text-align:center;">
                <span style="display:inline-block;font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px;background:rgba(var(--clr-theme-primary-rgb),.1);color:var(--clr-theme-primary);">
                    <?= number_format($planPct, 1) ?>%
                </span>
            </td>
            <td style="<?= $td ?>;text-align:center;">
                <span style="display:inline-block;font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px;
                    background:<?= $actPct >= 100 ? 'rgba(16,185,129,.1)' : ($actPct > 0 ? 'rgba(245,158,11,.1)' : 'rgba(0,0,0,.05)') ?>;
                    color:<?= $actPct >= 100 ? '#10b981' : ($actPct > 0 ? '#f59e0b' : '#94a3b8') ?>;">
                    <?= number_format($actPct, 1) ?>%
                </span>
            </td>
            <td style="<?= $td ?>;text-align:center;"><?= number_format((float)($pi['week_1_qty'] ?? 0), 2) ?></td>
            <td style="<?= $td ?>;text-align:center;"><?= number_format((float)($pi['week_2_qty'] ?? 0), 2) ?></td>
            <td style="<?= $td ?>;text-align:center;"><?= number_format((float)($pi['week_3_qty'] ?? 0), 2) ?></td>
            <td style="<?= $td ?>;text-align:center;"><?= number_format((float)($pi['week_4_qty'] ?? 0), 2) ?></td>
            <td style="<?= $td ?>;text-align:center;">
                <span style="font-size:11px;font-weight:700;color:<?= $priColor ?>;">
                    <?= htmlspecialchars($pi['priority'] ?? '—') ?>
                </span>
            </td>
            <td style="<?= $td ?>;text-align:center;">
                <span style="font-size:11px;font-weight:700;color:<?= $stColor ?>;">
                    <?= htmlspecialchars($pi['status'] ?? '—') ?>
                </span>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<?php
// Show weekly section if: any phase_weekly_progress session exists
// OR if any week has planned qty from phase_monthly_target_items
$anyWeekHasPlan = isset($weekPlanQty) && max(array_values($weekPlanQty + [0])) > 0;
if (!empty($weeklyProgress) || $anyWeekHasPlan):
?>
<!-- ══════════════ WEEKLY PROGRESS SUMMARY ══════════════ -->
<div class="mb-4" style="border:1.5px solid rgba(16,185,129,.2);border-radius:10px;overflow:hidden;">
    <div style="background:linear-gradient(90deg,rgba(16,185,129,.07),transparent);
                padding:10px 16px;border-bottom:1px solid rgba(16,185,129,.1);">
        <div style="font-size:12.5px;font-weight:700;color:#10b981;">
            <i class="bi bi-calendar-week me-2"></i>সাপ্তাহিক Progress Summary
            <span style="font-size:11px;font-weight:500;color:var(--text-muted);margin-left:6px;">
                — phase_weekly_progress থেকে
            </span>
        </div>
    </div>
    <div style="padding:14px 16px;">
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;">
            <?php
            $wkStatusColors = [
                'Draft'      => ['#94a3b8','rgba(148,163,184,.1)'],
                'Submitted'  => ['#f59e0b','rgba(245,158,11,.1)'],
                'Approved'   => ['#10b981','rgba(16,185,129,.1)'],
                'Not Started'=> ['#cbd5e1','rgba(0,0,0,.04)'],
            ];
            for ($w = 1; $w <= 4; $w++):
                $wk         = $weeklyProgress[$w] ?? null;
                $hasSession = $wk !== null;
                // $totPlan logic:
                // 1. If session exists AND total_planned_qty > 0 → use it (daily log was saved)
                // 2. Otherwise → fall back to SUM(week_X_qty) from phase_monthly_target_items
                //    (session may exist via getOrCreate() but no daily log saved → total_planned_qty = 0)
                $sessionPlan = $hasSession ? (float)($wk['total_planned_qty'] ?? 0) : 0.0;
                $totPlan     = ($sessionPlan > 0) ? $sessionPlan : ($weekPlanQty[$w] ?? 0.0);
                $totAch      = $hasSession ? (float)($wk['total_achieved_qty'] ?? 0) : 0.0;
                $hasPlan     = $totPlan > 0;
                $hasData    = $hasSession || $hasPlan;
                $status     = $hasSession ? ($wk['entry_status'] ?? 'Draft') : ($hasPlan ? 'Planned' : 'No Data');
                $wkStart    = $hasSession ? ($wk['week_start_date'] ?? '') : '';
                $wkEnd      = $hasSession ? ($wk['week_end_date']   ?? '') : '';
                // Progress % only when session exists (actual work logged)
                $pct        = ($hasSession && $totPlan > 0)
                    ? min(100.0, round($totAch / $totPlan * 100, 1))
                    : 0.0;
                $wkPlannedStatusColors = [
                    'Draft'      => ['#94a3b8','rgba(148,163,184,.1)'],
                    'Submitted'  => ['#f59e0b','rgba(245,158,11,.1)'],
                    'Approved'   => ['#10b981','rgba(16,185,129,.1)'],
                    'Planned'    => ['var(--clr-theme-primary)','rgba(var(--clr-theme-primary-rgb),.1)'],
                    'Not Started'=> ['#cbd5e1','rgba(0,0,0,.04)'],
                    'No Data'    => ['#cbd5e1','rgba(0,0,0,.04)'],
                ];
                [$sColor, $sBg] = $wkPlannedStatusColors[$status] ?? ['#94a3b8','rgba(0,0,0,.05)'];
                $progColor = $pct >= 100 ? '#10b981' : ($pct > 0 ? 'var(--clr-theme-primary)' : '#94a3b8');
                $borderColor = $hasSession ? 'rgba(var(--clr-theme-primary-rgb),.18)' : ($hasPlan ? 'rgba(var(--clr-theme-primary-rgb),.1)' : 'rgba(0,0,0,.06)');
                $bgColor     = $hasSession ? 'rgba(var(--clr-theme-primary-rgb),.02)' : ($hasPlan ? 'rgba(var(--clr-theme-primary-rgb),.01)' : 'rgba(0,0,0,.01)');
            ?>
            <div style="border:1.5px solid <?= $borderColor ?>;
                        border-radius:10px;padding:12px 14px;
                        background:<?= $bgColor ?>;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <span style="font-size:11px;font-weight:800;color:<?= $hasData ? 'var(--clr-theme-primary)' : '#94a3b8' ?>;">
                        Week <?= $w ?>
                    </span>
                    <span style="font-size:9.5px;font-weight:700;padding:2px 8px;border-radius:20px;
                                 background:<?= $sBg ?>;color:<?= $sColor ?>;">
                        <?= $status ?>
                    </span>
                </div>

                <?php if ($hasSession): ?>
                <!-- Session exists: show actual progress -->
                <div style="font-size:21px;font-weight:900;color:<?= $progColor ?>;margin-bottom:4px;line-height:1;">
                    <?= number_format($pct, 1) ?>%
                </div>
                <div style="height:5px;background:rgba(0,0,0,.07);border-radius:3px;overflow:hidden;margin-bottom:8px;">
                    <div style="width:<?= min($pct,100) ?>%;height:100%;background:<?= $progColor ?>;border-radius:3px;transition:width .5s;"></div>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:10px;color:#94a3b8;margin-bottom:4px;">
                    <span>Plan: <strong style="color:#64748b;"><?= number_format($totPlan, 1) ?></strong></span>
                    <span>Done: <strong style="color:#10b981;"><?= number_format($totAch, 1) ?></strong></span>
                </div>
                <?php if ($wkStart): ?>
                <div style="font-size:10px;color:#94a3b8;">
                    <i class="bi bi-calendar3 me-1"></i>
                    <?= date('d M', strtotime($wkStart)) ?> – <?= date('d M', strtotime($wkEnd)) ?>
                </div>
                <?php endif; ?>

                <?php elseif ($hasPlan): ?>
                <!-- No session yet but has planned qty from target items -->
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                    <i class="bi bi-hourglass-split" style="font-size:14px;color:var(--clr-theme-primary);"></i>
                    <span style="font-size:10px;color:var(--clr-theme-primary);font-weight:600;">কাজ শুরু হয়নি</span>
                </div>
                <div style="height:5px;background:rgba(0,0,0,.06);border-radius:3px;overflow:hidden;margin-bottom:8px;">
                    <div style="width:0%;height:100%;background:var(--clr-theme-primary);border-radius:3px;"></div>
                </div>
                <div style="font-size:10px;color:#94a3b8;margin-bottom:2px;">
                    Plan: <strong style="color:var(--clr-theme-primary);font-size:13px;"><?= number_format($totPlan, 1) ?></strong>
                    <span style="font-size:9px;margin-left:3px;">qty</span>
                </div>
                <div style="font-size:9px;color:#cbd5e1;margin-top:4px;">
                    <i class="bi bi-info-circle me-1"></i>Progress log হয়নি
                </div>

                <?php else: ?>
                <!-- Truly no data -->
                <div style="text-align:center;padding:10px 0;">
                    <i class="bi bi-dash-circle" style="font-size:20px;color:#e2e8f0;display:block;margin-bottom:4px;"></i>
                    <span style="font-size:10px;color:#cbd5e1;">কোনো তথ্য নেই</span>
                </div>
                <?php endif; ?>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</div>
<?php endif; ?>

                    <hr class="my-3">

                    <!-- ── BOQ Items Table ── -->
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle" id="boqItemsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40%;">Material <span class="text-danger">*</span></th>
                                    <th style="width:12%;" class="text-center">Stock</th>
                                    <th style="width:18%;">Quantity <span class="text-danger">*</span></th>
                                    <th style="width:22%;">Required Date <span class="text-danger">*</span></th>
                                    <th style="width:8%;" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="boqItemsBody">
                                <tr>
                                    <td>
                                        <select name="material_id[]" class="form-select boq-material" required>
                                            <option value="">-- Select Material --</option>
                                            <?php foreach ($materials as $m): ?>
                                            <option value="<?= $m['id'] ?>" data-unit="<?= $m['unit'] ?>">
                                                <?= htmlspecialchars($m['name']) ?> [<?= htmlspecialchars($m['category_name'] ?? 'Uncategorized') ?>] (<?= htmlspecialchars($m['unit']) ?>)
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-primary check-stock-btn d-inline-flex align-items-center gap-1"
                                                style="border-radius:6px;font-weight:500;" title="Show Stock">
                                            <i class="bi bi-eye-fill"></i> Show
                                        </button>
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <input type="number" step="0.001" name="quantity[]"
                                                   class="form-control boq-qty" required min="0.001">
                                            <span class="input-group-text boq-unit">-</span>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="date" name="required_date[]"
                                               class="form-control boq-req-date" required>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger boq-remove-row"
                                                style="display:none;" title="Remove">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="boqAddRow">
                            <i class="bi bi-plus-circle me-1"></i> Add Item
                        </button>
                    </div>

                    <div class="mt-5 text-end">
                        <a href="<?= $base_url ?>/con-boq" class="btn btn-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 fw-bold" id="boqSubmitBtn">
                            <i class="bi bi-save me-1"></i> Save BOQ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</section>

<script>
const BASE_URL  = '<?= $base_url ?>';
const MONTHS    = <?= json_encode($months) ?>;

// ── Stored material options HTML (for cloning rows) ──
const materialOptionsHTML = document.querySelector('.boq-material').innerHTML;

// ── Project → Load Targets (only when no phaseTarget pre-selected) ────────
<?php if (!$phaseTarget): ?>
document.getElementById('project_id').addEventListener('change', function () {
    const pid  = this.value;
    const sel  = document.getElementById('target_select');
    const load = document.getElementById('target_loading');

    sel.innerHTML = '<option value="">-- Select Target --</option>';
    sel.disabled  = true;
    document.getElementById('target_id_hidden').value = '';
    document.getElementById('design_type_display').value = '';
    document.getElementById('targetInfoCard').classList.add('d-none');

    if (!pid) {
        sel.innerHTML = '<option value="">-- Select Project First --</option>';
        return;
    }
    load.style.display = 'block';

    fetch(BASE_URL + '/con-ajax-boq-targets?project_id=' + pid)
        .then(r => r.json())
        .then(data => {
            load.style.display = 'none';
            sel.innerHTML = '<option value="">-- No Target (General BOQ) --</option>';
            if (data.targets && data.targets.length > 0) {
                data.targets.forEach(t => {
                    const mon = MONTHS[parseInt(t.month)] || '';
                    let label = t.target_title + ' [' + mon + ' ' + t.year + ']';
                    if (t.boq_id) label += ' (BOQ Already Created)';
                    const opt = document.createElement('option');
                    opt.value = t.id;
                    opt.textContent = label;
                    opt.dataset.targetTitle = t.target_title;
                    opt.dataset.targetPct   = t.target_percentage || '';
                    opt.dataset.year        = t.year;
                    opt.dataset.month       = t.month;
                    opt.dataset.boqId       = t.boq_id || '';
                    sel.appendChild(opt);
                });
            } else {
                sel.innerHTML += '<option disabled>No targets found for this project</option>';
            }
            sel.disabled = false;
        })
        .catch(() => {
            load.style.display = 'none';
            sel.innerHTML = '<option value="">-- Error loading targets --</option>';
            sel.disabled  = false;
        });
});

// ── Target Selection → Auto-fill ─────────────────────────────────────────
document.getElementById('target_select').addEventListener('change', function () {
    const opt     = this.options[this.selectedIndex];
    const saveBtn = document.getElementById('boqSubmitBtn');
    document.getElementById('target_id_hidden').value = this.value || '';

    const card = document.getElementById('targetInfoCard');
    const txt  = document.getElementById('targetInfoText');

    if (this.value) {
        const mon = MONTHS[parseInt(opt.dataset.month)] || '';
        let info  = '<strong>' + (opt.dataset.targetTitle || '—') + '</strong>';
        info     += ' | Period: ' + mon + ' ' + (opt.dataset.year || '');
        if (opt.dataset.targetPct) info += ' | Target: <strong>' + parseFloat(opt.dataset.targetPct).toFixed(1) + '%</strong>';

        if (opt.dataset.boqId) {
            info += '<br><div class="alert alert-danger mt-2 mb-0 py-2 fw-semibold" style="font-size:12.5px;">' +
                    '<i class="bi bi-exclamation-triangle-fill me-1"></i>A BOQ has already been created for this target. ' +
                    '<a href="' + BASE_URL + '/con-boq-show/' + opt.dataset.boqId + '" class="fw-bold text-danger text-decoration-underline" target="_blank">' +
                    'View BOQ #' + opt.dataset.boqId + '</a>. Saving is disabled to prevent duplicates.</div>';
            if (saveBtn) saveBtn.disabled = true;
        } else {
            if (saveBtn) saveBtn.disabled = false;
        }
        txt.innerHTML = info;
        card.classList.remove('d-none');
    } else {
        card.classList.add('d-none');
        if (saveBtn) saveBtn.disabled = false;
    }
});
<?php endif; ?>

// ── BOQ Item Events ───────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    const tbody = document.getElementById('boqItemsBody');

    tbody.addEventListener('change', function (e) {
        if (e.target.classList.contains('boq-material')) {
            const opt  = e.target.options[e.target.selectedIndex];
            const unit = opt.getAttribute('data-unit') || '-';
            e.target.closest('tr').querySelector('.boq-unit').textContent = unit;
        }
    });

    document.getElementById('boqAddRow').addEventListener('click', function () {
        const firstRow = tbody.querySelector('tr');
        const newRow   = firstRow.cloneNode(true);
        const matSel   = newRow.querySelector('.boq-material');
        matSel.innerHTML = materialOptionsHTML;
        matSel.selectedIndex = 0;
        newRow.querySelectorAll('input').forEach(inp => { inp.value = ''; });
        newRow.querySelector('.boq-unit').textContent = '-';
        newRow.querySelector('.boq-remove-row').style.display = '';
        tbody.appendChild(newRow);
    });

    tbody.addEventListener('click', function (e) {
        const removeBtn = e.target.closest('.boq-remove-row');
        if (removeBtn) removeBtn.closest('tr').remove();

        const stockBtn = e.target.closest('.check-stock-btn');
        if (stockBtn) checkStock(stockBtn);
    });
});

// ── Stock Check ───────────────────────────────────────────────────────────
function checkStock(btn) {
    const row        = btn.closest('tr');
    const matSelect  = row.querySelector('.boq-material');
    const materialId = matSelect.value;
    const projectField = document.querySelector('[name="project_id"]');
    const projectId    = projectField ? projectField.value : '';

    if (!projectId) {
        Swal.fire({ icon:'warning', title:'Project Required',
            text:'Please select a Project first.', confirmButtonColor:'var(--clr-theme-primary)' });
        return;
    }
    if (!materialId) {
        Swal.fire({ icon:'warning', title:'Material Required',
            text:'Please select a Material first.', confirmButtonColor:'var(--clr-theme-primary)' });
        return;
    }

    const origHtml = btn.innerHTML;
    btn.disabled   = true;
    btn.innerHTML  = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

    fetch(`${BASE_URL}/con-ajax-material-stock?project_id=${projectId}&material_id=${materialId}`)
        .then(response => response.json())
        .then(data => {
            btn.disabled  = false;
            btn.innerHTML = origHtml;
            if (data.success) {
                let warehouseListHtml = '';
                if (data.warehouses.length > 0) {
                    data.warehouses.forEach(w => {
                        warehouseListHtml += `
                            <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                <div><i class="bi bi-house-door text-muted me-2"></i><span class="fw-semibold">${w.warehouse_name}</span></div>
                                <span class="badge bg-light text-dark border fw-bold">${w.current_quantity.toFixed(3)} ${data.unit}</span>
                            </div>`;
                    });
                } else {
                    warehouseListHtml = `<div class="list-group-item text-center py-3 text-muted bg-light">
                        <i class="bi bi-exclamation-circle me-1 text-warning"></i>No warehouses found for this project.</div>`;
                }
                const badgeClass = data.total_stock > 0 ? 'bg-success' : 'bg-danger';
                Swal.fire({
                    title: '<span style="font-size:18px;font-weight:700;"><i class="bi bi-box-seam text-primary me-2"></i>Project-wise Stock Inquiry</span>',
                    html: `<div class="text-start" style="font-size:14px;font-family:inherit;">
                        <div class="mb-2 pb-2 border-bottom"><strong>Project:</strong> <span class="text-primary fw-semibold">${data.project_name}</span></div>
                        <div class="mb-3"><strong>Material:</strong> <span class="text-dark fw-semibold">${data.material_name}</span></div>
                        <div class="card shadow-sm border-0 mb-3 overflow-hidden" style="border:1px solid #dee2e6 !important;">
                            <div class="card-header bg-light py-2 px-3 border-bottom-0">
                                <h6 class="mb-0 text-secondary fw-bold" style="font-size:12.5px;">
                                    <i class="bi bi-houses me-1 text-info"></i> Warehouse Stock Breakdown
                                </h6>
                            </div>
                            <div class="list-group list-group-flush border-top">${warehouseListHtml}</div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded border" style="border:1px solid #dee2e6 !important;">
                            <span class="fw-bold text-secondary">Total Available Stock:</span>
                            <span class="badge ${badgeClass} px-3 py-2 fs-6 fw-bold" style="letter-spacing:.5px;">
                                ${data.total_stock.toFixed(3)} ${data.unit}
                            </span>
                        </div>
                    </div>`,
                    showCloseButton: true,
                    confirmButtonText: 'Close',
                    confirmButtonColor: 'var(--clr-theme-primary)',
                    customClass: { popup: 'shadow-lg border-0' }
                });
            } else {
                Swal.fire({ icon:'error', title:'Error',
                    text: data.message || 'Failed to fetch stock.', confirmButtonColor:'#ef4444' });
            }
        })
        .catch(() => {
            btn.disabled  = false;
            btn.innerHTML = origHtml;
            Swal.fire({ icon:'error', title:'Network Error',
                text:'Something went wrong. Please try again.', confirmButtonColor:'#ef4444' });
        });
}
</script>
