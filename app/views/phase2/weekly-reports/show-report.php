<?php
$base_url    = $GLOBALS['base_url'];
$project     = $project     ?? [];
$targets     = $targets     ?? [];
$target_id   = $target_id   ?? 0;
$phaseTarget = $phaseTarget ?? null;
$targetItems = $targetItems ?? [];
$weekData    = $weekData    ?? [];
$weekPlanQty = $weekPlanQty ?? [1=>0,2=>0,3=>0,4=>0];
$activeWeek  = $activeWeek  ?? 1;
$roleId      = (int)($_SESSION['user']['role_id'] ?? 0);
$userId      = (int)($_SESSION['user']['id']      ?? 0);

$statusBadge = function(string $s): string {
    return match($s) {
        'Approved'   => '<span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;background:rgba(16,185,129,.12);color:#10b981;border:1px solid rgba(16,185,129,.25);">' . $s . '</span>',
        'Submitted'  => '<span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;background:rgba(245,158,11,.12);color:#f59e0b;border:1px solid rgba(245,158,11,.25);">' . $s . '</span>',
        'Rejected'   => '<span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;background:rgba(239,68,68,.12);color:#ef4444;border:1px solid rgba(239,68,68,.25);">' . $s . '</span>',
        'Draft'      => '<span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;background:rgba(148,163,184,.12);color:#64748b;border:1px solid rgba(148,163,184,.25);">Draft</span>',
        default      => '<span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;background:rgba(148,163,184,.1);color:#94a3b8;">' . htmlspecialchars($s) . '</span>',
    };
};
?>

<!-- ═══ BREADCRUMB ═══ -->
<div class="pagetitle d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 style="font-size:1.1rem;font-weight:800;color:#1e293b;margin:0;">
            <i class="bi bi-calendar-week me-2" style="color:var(--clr-theme-primary);"></i>
            <?= htmlspecialchars($project['name'] ?? '') ?> — Weekly Report
        </h1>
        <nav><ol class="breadcrumb" style="font-size:11px;margin:2px 0 0;">
            <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-weekly-reports">Weekly Reports</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($project['name'] ?? '') ?></li>
        </ol></nav>
    </div>
    <?php if ($phaseTarget): ?>
    <a href="<?= $base_url ?>/con-weekly-progress/<?= $target_id ?>?week=<?= $activeWeek ?>"
       class="btn btn-sm btn-outline-primary" style="font-size:11.5px;border-radius:8px;">
        <i class="bi bi-pencil-square me-1"></i>Entry / Edit
    </a>
    <?php endif; ?>
</div>

<!-- ═══ PROJECT + TARGET SELECTOR ═══ -->
<form method="GET" action="<?= $base_url ?>/con-weekly-report/<?= $project['id'] ?>" id="reportFilterForm">
    <div style="background:#fff;border:1.5px solid rgba(25,135,84,.12);border-radius:12px;
                padding:14px 18px;margin-bottom:18px;display:flex;gap:14px;flex-wrap:wrap;align-items:flex-end;">
        <!-- Target Month Select -->
        <div style="flex:1;min-width:220px;">
            <label style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;
                          display:block;margin-bottom:5px;">
                <i class="bi bi-bullseye me-1 text-primary"></i>Target Month
            </label>
            <select name="target_id" id="targetSelect"
                    style="width:100%;border-radius:8px;font-size:13px;border:1.5px solid #e2e8f0;padding:7px 10px;"
                    onchange="this.form.submit()">
                <option value="">-- Select Target Month --</option>
                <?php foreach ($targets as $t): ?>
                <option value="<?= $t['id'] ?>" <?= $target_id == $t['id'] ? 'selected' : '' ?>>
                     <?= date('F Y', strtotime($t['target_month'])) ?> — <?= htmlspecialchars($t['phase_name']) ?>
                     (<?= htmlspecialchars($t['planning_status'] ?? 'Draft') ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($target_id): ?>
        <!-- Week quick links (submit as GET) -->
        <div style="display:flex;gap:6px;align-items:flex-end;flex-wrap:wrap;">
            <?php for ($w = 1; $w <= 4; $w++):
                $wSession = $weekData[$w]['session'] ?? null;
                $wStatus  = $wSession ? ($wSession['entry_status'] ?? 'Draft') : 'No Data';
                $wColor   = match($wStatus) {
                    'Approved'  => '#10b981',
                    'Submitted' => '#f59e0b',
                    'Draft'     => '#94a3b8',
                    default     => '#cbd5e1',
                };
            ?>
            <a href="?target_id=<?= $target_id ?>&week=<?= $w ?>"
               style="padding:6px 14px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;
                      background:<?= $activeWeek == $w ? 'var(--clr-theme-primary)' : '#f8fafc' ?>;
                      color:<?= $activeWeek == $w ? '#fff' : '#64748b' ?>;
                      border:1.5px solid <?= $activeWeek == $w ? 'var(--clr-theme-primary)' : '#e2e8f0' ?>;">
                Week <?= $w ?>
                <?php if ($wSession): ?>
                <span style="display:inline-block;width:6px;height:6px;border-radius:50%;
                              background:<?= $wColor ?>;margin-left:4px;vertical-align:middle;"></span>
                <?php endif; ?>
            </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</form>

<?php if (!$target_id): ?>
<!-- ── No target selected ── -->
<div style="text-align:center;padding:60px 20px;background:#f8fafc;border-radius:12px;border:1px dashed #cbd5e1;">
    <i class="bi bi-calendar2-check" style="font-size:40px;color:#cbd5e1;display:block;margin-bottom:12px;"></i>
    <div style="font-size:14px;color:#94a3b8;font-weight:600;">Target Month বেছে নিন</div>
    <div style="font-size:12px;color:#cbd5e1;margin-top:4px;">উপরের dropdown থেকে যে মাসের report দেখতে চান সেটি select করুন</div>
</div>

<?php elseif (!$phaseTarget): ?>
<!-- ── Target not found ── -->
<div class="alert alert-warning">Target পাওয়া যায়নি।</div>

<?php else:
    // ══════════════ PHASE TARGET HEADER CARD ══════════════
    $totItems    = count($targetItems);
    $planPct     = $totItems > 0 ? array_sum(array_column($targetItems, 'planned_progress_pct')) / $totItems : 0;
    $actPct      = $totItems > 0 ? array_sum(array_column($targetItems, 'actual_progress_pct'))  / $totItems : 0;
    $sessionsDone = count($weekData);
?>
<!-- Phase Header -->
<div style="background:linear-gradient(135deg,var(--clr-theme-primary) 0%,var(--clr-theme-secondary) 100%);border-radius:14px;
            padding:18px 22px;margin-bottom:18px;color:#fff;">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <div>
            <div style="font-size:11px;opacity:.75;font-weight:600;letter-spacing:.5px;text-transform:uppercase;">
                <?= htmlspecialchars($phaseTarget['phase_name'] ?? '') ?>
            </div>
            <div style="font-size:20px;font-weight:900;margin:3px 0;">
                <?= date('F Y', strtotime($phaseTarget['target_month'])) ?>
            </div>
            <div style="font-size:12px;opacity:.85;">
                <i class="bi bi-building me-1"></i>
                <?= htmlspecialchars($project['name']) ?>
                <?php if (!empty($project['block_no'])): ?>
                &nbsp;·&nbsp; <?= htmlspecialchars($project['block_no']) ?>
                <?php endif; ?>
            </div>
        </div>
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <?php
            $kpis = [
                [$totItems,      'Work Items'],
                [number_format($planPct, 1).'%', 'Planned'],
                [number_format($actPct,  1).'%', 'Actual'],
                [$sessionsDone.'/4', 'Weeks Logged'],
            ];
            foreach ($kpis as [$val, $label]):
            ?>
            <div style="background:rgba(255,255,255,.15);border-radius:10px;padding:10px 16px;text-align:center;min-width:70px;">
                <div style="font-size:20px;font-weight:900;"><?= $val ?></div>
                <div style="font-size:9.5px;opacity:.8;margin-top:1px;"><?= $label ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php
// ══════════════ WEEK CONTENT ══════════════
$wData       = $weekData[$activeWeek] ?? null;
$wSession    = $wData['session']      ?? null;
$wItems      = $wData['items']        ?? [];
$wDailyLogs  = $wData['dailyLogs']   ?? [];
$wDailyItems = $wData['dailyItemsMap'] ?? [];
$wPhotos     = $wData['photos']       ?? [];

$sessionPlan = $wSession ? (float)($wSession['total_planned_qty'] ?? 0) : 0;
$totPlan     = ($sessionPlan > 0) ? $sessionPlan : ($weekPlanQty[$activeWeek] ?? 0);
$totAch      = $wSession ? (float)($wSession['total_achieved_qty'] ?? 0) : 0;
$wPct        = ($totPlan > 0 && $wSession) ? min(100, round($totAch / $totPlan * 100, 1)) : 0;
$wStatus     = $wSession ? ($wSession['entry_status'] ?? 'Draft') : 'Not Started';
?>

<!-- ══ WEEK SESSION HEADER ══ -->
<div style="background:#fff;border:1.5px solid rgba(25,135,84,.15);border-radius:12px;
            padding:14px 18px;margin-bottom:16px;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <div>
            <div style="font-size:13px;font-weight:800;color:#1e293b;">
                Week <?= $activeWeek ?> — Status
            </div>
            <?php if ($wSession && !empty($wSession['week_start_date'])): ?>
            <div style="font-size:11px;color:#94a3b8;margin-top:2px;">
                <i class="bi bi-calendar3 me-1"></i>
                <?= date('d M Y', strtotime($wSession['week_start_date'])) ?> –
                <?= date('d M Y', strtotime($wSession['week_end_date'])) ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($wSession): ?>
        <!-- Approval chain badges -->
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            <!-- Site Engineer (Submitted By) -->
            <div style="background:#f8fafc;border-radius:10px;padding:8px 12px;display:flex;align-items:center;gap:8px;
                        border:1px solid #e2e8f0;">
                <div style="width:30px;height:30px;background:var(--clr-theme-primary);border-radius:50%;display:flex;
                             align-items:center;justify-content:center;color:#fff;font-size:13px;flex-shrink:0;">
                    <i class="bi bi-person-hard-hat"></i>
                </div>
                <div>
                    <div style="font-size:9.5px;color:#94a3b8;font-weight:600;">Site Engineer</div>
                    <div style="font-size:11.5px;font-weight:700;color:#1e293b;">
                        <?= htmlspecialchars($wSession['submitted_by_name'] ?? 'Not submitted') ?>
                    </div>
                </div>
                <?= $statusBadge($wStatus) ?>
            </div>

            <!-- QC Approval -->
            <?php $qcStatus = $wSession['qc_status'] ?? 'Pending'; ?>
            <div style="background:#f8fafc;border-radius:10px;padding:8px 12px;display:flex;align-items:center;gap:8px;
                        border:1px solid <?= $qcStatus === 'Approved' ? 'rgba(16,185,129,.25)' : '#e2e8f0' ?>;">
                <div style="width:30px;height:30px;background:<?= $qcStatus === 'Approved' ? '#10b981' : '#e2e8f0' ?>;
                             border-radius:50%;display:flex;align-items:center;justify-content:center;
                             color:<?= $qcStatus === 'Approved' ? '#fff' : '#94a3b8' ?>;font-size:13px;flex-shrink:0;">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div>
                    <div style="font-size:9.5px;color:#94a3b8;font-weight:600;">QC</div>
                    <div style="font-size:11.5px;font-weight:700;color:#1e293b;">
                        <?= htmlspecialchars($wSession['qc_approver_name'] ?? 'Pending') ?>
                    </div>
                </div>
                <?= $statusBadge($qcStatus) ?>
            </div>

            <!-- PM Approval -->
            <?php $pmStatus = $wSession['pm_status'] ?? 'Pending'; ?>
            <div style="background:#f8fafc;border-radius:10px;padding:8px 12px;display:flex;align-items:center;gap:8px;
                        border:1px solid <?= $pmStatus === 'Approved' ? 'rgba(25,135,84,.25)' : '#e2e8f0' ?>;">
                <div style="width:30px;height:30px;background:<?= $pmStatus === 'Approved' ? 'var(--clr-theme-primary)' : '#e2e8f0' ?>;
                             border-radius:50%;display:flex;align-items:center;justify-content:center;
                             color:<?= $pmStatus === 'Approved' ? '#fff' : '#94a3b8' ?>;font-size:13px;flex-shrink:0;">
                    <i class="bi bi-person-check"></i>
                </div>
                <div>
                    <div style="font-size:9.5px;color:#94a3b8;font-weight:600;">PM</div>
                    <div style="font-size:11.5px;font-weight:700;color:#1e293b;">
                        <?= htmlspecialchars($wSession['pm_approver_name'] ?? 'Pending') ?>
                    </div>
                </div>
                <?= $statusBadge($pmStatus) ?>
            </div>
        </div>
        <?php else: ?>
        <div style="font-size:12px;color:#94a3b8;">
            <i class="bi bi-hourglass me-1"></i>
            <?php if ($totPlan > 0): ?>
                এই সপ্তাহে <?= number_format($totPlan, 1) ?> qty planned — এখনো log entry হয়নি
            <?php else: ?>
                এই সপ্তাহে কোনো data নেই
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($wSession): ?>
    <!-- Progress bar -->
    <div style="margin-top:12px;">
        <div style="display:flex;justify-content:space-between;font-size:11px;color:#94a3b8;margin-bottom:4px;">
            <span>সাপ্তাহিক অগ্রগতি</span>
            <span style="font-weight:700;color:<?= $wPct >= 100 ? '#10b981' : 'var(--clr-theme-primary)' ?>;"><?= $wPct ?>%</span>
        </div>
        <div style="height:8px;background:#f1f5f9;border-radius:4px;overflow:hidden;">
            <div style="width:<?= $wPct ?>%;height:100%;background:<?= $wPct >= 100 ? '#10b981' : 'linear-gradient(90deg,var(--clr-theme-primary),var(--clr-theme-secondary))' ?>;
                         border-radius:4px;transition:width .5s;"></div>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:10.5px;color:#94a3b8;margin-top:4px;">
            <span>Plan: <strong style="color:#64748b;"><?= number_format($totPlan, 1) ?></strong></span>
            <span>Done: <strong style="color:#10b981;"><?= number_format($totAch, 1) ?></strong></span>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ══ WORK ITEMS TABLE ══ -->
<?php if (!empty($wItems)): ?>
<div style="background:#fff;border:1.5px solid rgba(25,135,84,.12);border-radius:12px;
            margin-bottom:16px;overflow:hidden;">
    <div style="background:linear-gradient(90deg,rgba(25,135,84,.07),transparent);
                padding:10px 16px;border-bottom:1px solid rgba(25,135,84,.1);
                display:flex;align-items:center;justify-content:space-between;">
        <div style="font-size:12.5px;font-weight:700;color:var(--clr-theme-primary);">
            <i class="bi bi-list-check me-2"></i>Work Items — Week <?= $activeWeek ?>
        </div>
    </div>
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr>
                    <?php
                    $th = 'padding:7px 10px;font-size:10px;font-weight:700;text-transform:uppercase;
                           letter-spacing:.4px;color:#64748b;border-bottom:2px solid rgba(0,0,0,.07);
                           background:rgba(0,0,0,.02);white-space:nowrap;';
                    ?>
                    <th style="<?= $th ?>">#</th>
                    <th style="<?= $th ?>">Work Item</th>
                    <th style="<?= $th ?>">Type</th>
                    <th style="<?= $th ?>;text-align:center;">Unit</th>
                    <th style="<?= $th ?>;text-align:center;">Planned Qty</th>
                    <th style="<?= $th ?>;text-align:center;">Achieved Qty</th>
                    <th style="<?= $th ?>;text-align:center;">Progress %</th>
                    <th style="<?= $th ?>;text-align:center;">Status</th>
                    <?php if (!empty($wPhotos)): ?>
                    <th style="<?= $th ?>;text-align:center;">Photo</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $td = 'padding:8px 10px;border-bottom:1px solid rgba(0,0,0,.05);font-size:12.5px;vertical-align:middle;';
                foreach ($wItems as $n => $item):
                    $planQty = (float)($item['planned_qty'] ?? $item['planned_qty_from_target'] ?? 0);
                    $achQty  = (float)($item['achieved_qty'] ?? 0);
                    $pct     = ($planQty > 0) ? min(100, round($achQty / $planQty * 100, 1)) : 0;
                    $pctColor = $pct >= 100 ? '#10b981' : ($pct > 0 ? 'var(--clr-theme-primary)' : '#94a3b8');
                    $iStatus  = $item['item_status'] ?? '—';
                    $stColor  = match($iStatus) {
                        'Completed' => '#10b981', 'In Progress' => 'var(--clr-theme-primary)',
                        'Delayed'   => '#ef4444', default => '#94a3b8',
                    };
                ?>
                <tr>
                    <td style="<?= $td ?>;color:#94a3b8;font-size:11px;"><?= $n + 1 ?></td>
                    <td style="<?= $td ?>;font-weight:600;color:#1e293b;">
                        <?= htmlspecialchars($item['work_item_name'] ?? '—') ?>
                    </td>
                    <td style="<?= $td ?>;color:#64748b;font-size:11.5px;">
                        <?= htmlspecialchars($item['work_type_name'] ?? '—') ?>
                    </td>
                    <td style="<?= $td ?>;text-align:center;color:#64748b;">
                        <?= htmlspecialchars($item['unit'] ?? '—') ?>
                    </td>
                    <td style="<?= $td ?>;text-align:center;font-weight:600;">
                        <?= number_format($planQty, 2) ?>
                    </td>
                    <td style="<?= $td ?>;text-align:center;font-weight:700;color:#10b981;">
                        <?= number_format($achQty, 2) ?>
                    </td>
                    <td style="<?= $td ?>;text-align:center;">
                        <div style="display:flex;align-items:center;gap:6px;justify-content:center;">
                            <div style="width:50px;height:5px;background:rgba(0,0,0,.07);border-radius:3px;overflow:hidden;">
                                <div style="width:<?= $pct ?>%;height:100%;background:<?= $pctColor ?>;border-radius:3px;"></div>
                            </div>
                            <span style="font-size:11px;font-weight:700;color:<?= $pctColor ?>;"><?= $pct ?>%</span>
                        </div>
                    </td>
                    <td style="<?= $td ?>;text-align:center;">
                        <span style="font-size:10.5px;font-weight:700;color:<?= $stColor ?>;">
                            <?= htmlspecialchars($iStatus) ?>
                        </span>
                    </td>
                    <?php if (!empty($wPhotos)): ?>
                    <td style="<?= $td ?>;text-align:center;">
                        <?php if (!empty($item['photo_path'])): ?>
                        <a href="<?= $base_url ?>/<?= htmlspecialchars($item['photo_path']) ?>"
                           target="_blank" data-lightbox="week-photos">
                            <img src="<?= $base_url ?>/<?= htmlspecialchars($item['photo_path']) ?>"
                                 style="width:36px;height:36px;object-fit:cover;border-radius:6px;
                                        border:2px solid rgba(25,135,84,.2);">
                        </a>
                        <?php else: ?>
                        <span style="font-size:10px;color:#cbd5e1;">—</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php elseif ($totPlan > 0): ?>
<div style="background:#fff;border:1.5px dashed rgba(25,135,84,.2);border-radius:12px;
            padding:20px;text-align:center;margin-bottom:16px;color:#94a3b8;font-size:12.5px;">
    <i class="bi bi-hourglass-split me-2" style="color:var(--clr-theme-primary);"></i>
    এই week এ planned qty: <strong style="color:var(--clr-theme-primary);"><?= number_format($totPlan, 1) ?></strong>
    — এখনো daily log entry হয়নি
</div>
<?php endif; ?>

<!-- ══ DAILY LOGS ACCORDION ══ -->
<?php if (!empty($wDailyLogs)): ?>
<div style="background:#fff;border:1.5px solid rgba(16,185,129,.15);border-radius:12px;
            margin-bottom:16px;overflow:hidden;">
    <div style="background:linear-gradient(90deg,rgba(16,185,129,.07),transparent);
                padding:10px 16px;border-bottom:1px solid rgba(16,185,129,.1);">
        <div style="font-size:12.5px;font-weight:700;color:#10b981;">
            <i class="bi bi-journal-text me-2"></i>Daily Logs — Week <?= $activeWeek ?>
            <span style="font-size:11px;font-weight:500;color:#94a3b8;margin-left:6px;">
                (<?= count($wDailyLogs) ?> দিনের রেকর্ড)
            </span>
        </div>
    </div>
    <div class="accordion" id="dailyLogsAccordion" style="border:none;">
        <?php foreach ($wDailyLogs as $li => $log):
            $logDate    = $log['log_date'] ?? '';
            $accId      = 'log_' . $li;
            $isOpen     = $li === count($wDailyLogs) - 1; // last day open by default
            $logItems   = $wDailyItems[$logDate] ?? [];
            $qcSt       = $log['qc_status']    ?? 'Pending';
            $pmSt       = $log['pm_status']     ?? 'Pending';
            $qcColor    = $qcSt === 'Approved' ? '#10b981' : ($qcSt === 'Rejected' ? '#ef4444' : '#f59e0b');
            $pmColor    = $pmSt === 'Approved' ? 'var(--clr-theme-primary)' : ($pmSt === 'Rejected' ? '#ef4444' : '#f59e0b');
        ?>
        <div style="border-bottom:1px solid rgba(0,0,0,.05);">
            <!-- Accordion Header -->
            <button onclick="toggleLog('<?= $accId ?>')"
                    id="btn_<?= $accId ?>"
                    style="width:100%;background:none;border:none;padding:12px 16px;text-align:left;
                           cursor:pointer;display:flex;align-items:center;gap:10px;transition:background .15s;"
                    onmouseenter="this.style.background='rgba(0,0,0,.02)'"
                    onmouseleave="this.style.background='none'">
                <!-- Date -->
                <div style="font-size:12.5px;font-weight:700;color:#1e293b;min-width:120px;">
                    <i class="bi bi-calendar-day me-1 text-primary"></i>
                    <?= date('d M Y (D)', strtotime($logDate)) ?>
                </div>
                <!-- Resource Summary -->
                <div style="display:flex;gap:10px;flex:1;flex-wrap:wrap;">
                    <?php if ((int)($log['labour_count'] ?? 0) > 0): ?>
                    <span style="font-size:10.5px;background:rgba(25,135,84,.08);color:var(--clr-theme-primary);
                                 padding:2px 8px;border-radius:999px;font-weight:600;">
                        <i class="bi bi-people me-1"></i><?= $log['labour_count'] ?> Labour
                    </span>
                    <?php endif; ?>
                    <?php if ((int)($log['cement_bags'] ?? 0) > 0): ?>
                    <span style="font-size:10.5px;background:rgba(245,158,11,.08);color:#f59e0b;
                                 padding:2px 8px;border-radius:999px;font-weight:600;">
                        <?= $log['cement_bags'] ?> Bags
                    </span>
                    <?php endif; ?>
                    <?php if (!empty($log['weather'])): ?>
                    <span style="font-size:10.5px;color:#94a3b8;">
                        <i class="bi bi-cloud me-1"></i><?= htmlspecialchars($log['weather']) ?>
                    </span>
                    <?php endif; ?>
                </div>
                <!-- Approval pills -->
                <div style="display:flex;gap:6px;align-items:center;flex-shrink:0;">
                    <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;
                                 background:<?= $qcColor ?>15;color:<?= $qcColor ?>;border:1px solid <?= $qcColor ?>30;">
                        QC: <?= $qcSt ?>
                    </span>
                    <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;
                                 background:<?= $pmColor ?>15;color:<?= $pmColor ?>;border:1px solid <?= $pmColor ?>30;">
                        PM: <?= $pmSt ?>
                    </span>
                    <i class="bi bi-chevron-down" id="icon_<?= $accId ?>"
                       style="color:#94a3b8;font-size:12px;transition:transform .2s;
                              <?= $isOpen ? 'transform:rotate(180deg)' : '' ?>"></i>
                </div>
            </button>

            <!-- Accordion Body -->
            <div id="<?= $accId ?>" style="<?= $isOpen ? '' : 'display:none;' ?>padding:0 16px 14px;">
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px;margin-bottom:12px;">

                    <!-- Daily Work Items -->
                    <?php if (!empty($logItems)): ?>
                    <div style="grid-column:1/-1;background:#f8fafc;border-radius:10px;padding:12px;
                                border:1px solid rgba(25,135,84,.1);">
                        <div style="font-size:11px;font-weight:700;color:var(--clr-theme-primary);margin-bottom:8px;">
                            <i class="bi bi-check2-square me-1"></i>আজকের কাজ
                        </div>
                        <table style="width:100%;border-collapse:collapse;">
                            <?php foreach ($logItems as $di):
                                $dAch = (float)($di['qty_done_today'] ?? 0);
                            ?>
                            <tr style="border-bottom:1px solid rgba(0,0,0,.04);">
                                <td style="padding:5px 8px;font-size:12px;color:#1e293b;font-weight:600;">
                                    <?= htmlspecialchars($di['work_item_name'] ?? '—') ?>
                                </td>
                                <td style="padding:5px 8px;font-size:11px;color:#64748b;text-align:center;">
                                    <?= htmlspecialchars($di['work_type_name'] ?? '—') ?>
                                </td>
                                <td style="padding:5px 8px;font-size:12.5px;font-weight:800;color:#10b981;text-align:right;">
                                    +<?= number_format($dAch, 2) ?> <?= htmlspecialchars($di['unit'] ?? '') ?>
                                </td>
                                <td style="padding:5px 8px;font-size:10.5px;color:#94a3b8;text-align:right;">
                                    <?= htmlspecialchars($di['item_status'] ?? '') ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                    <?php endif; ?>

                    <!-- Site Engineer Remarks -->
                    <?php if (!empty($log['site_remarks'])): ?>
                    <div style="background:#f8fafc;border-radius:10px;padding:12px;border:1px solid #e2e8f0;">
                        <div style="font-size:10px;font-weight:700;color:#94a3b8;margin-bottom:4px;text-transform:uppercase;">
                            Site Remarks
                        </div>
                        <div style="font-size:12px;color:#1e293b;"><?= nl2br(htmlspecialchars($log['site_remarks'])) ?></div>
                    </div>
                    <?php endif; ?>

                    <!-- QC Approval Details -->
                    <div style="background:<?= $qcSt === 'Approved' ? 'rgba(16,185,129,.04)' : '#f8fafc' ?>;
                                border-radius:10px;padding:12px;
                                border:1px solid <?= $qcSt === 'Approved' ? 'rgba(16,185,129,.2)' : '#e2e8f0' ?>;">
                        <div style="font-size:10px;font-weight:700;color:#94a3b8;margin-bottom:8px;
                                    text-transform:uppercase;letter-spacing:.5px;">
                            <i class="bi bi-shield-check me-1" style="color:<?= $qcColor ?>;"></i>QC Approval
                        </div>
                        <?php if (!empty($log['qc_approved_id'])): ?>
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                            <div style="width:32px;height:32px;background:<?= $qcColor ?>20;border-radius:50%;
                                        display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="bi bi-person" style="color:<?= $qcColor ?>;"></i>
                            </div>
                            <div>
                                <div style="font-size:12px;font-weight:700;color:#1e293b;">
                                    <?= htmlspecialchars($log['qc_name'] ?? 'QC Officer') ?>
                                </div>
                                <div style="font-size:10px;color:#94a3b8;">
                                    <?= htmlspecialchars($log['qc_designation'] ?? '') ?>
                                </div>
                            </div>
                            <?= $statusBadge($qcSt) ?>
                        </div>
                        <?php if (!empty($log['qc_comment'])): ?>
                        <div style="font-size:11.5px;color:#64748b;background:rgba(0,0,0,.03);
                                    border-radius:6px;padding:6px 8px;">
                            <?= nl2br(htmlspecialchars($log['qc_comment'])) ?>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($log['qc_image'])): ?>
                        <div style="margin-top:8px;">
                            <a href="<?= $base_url ?>/<?= htmlspecialchars($log['qc_image']) ?>" target="_blank">
                                <img src="<?= $base_url ?>/<?= htmlspecialchars($log['qc_image']) ?>"
                                     style="width:100%;max-width:160px;border-radius:8px;border:2px solid rgba(16,185,129,.2);
                                            object-fit:cover;">
                            </a>
                            <div style="font-size:10px;color:#94a3b8;margin-top:3px;">QC Site Photo</div>
                        </div>
                        <?php endif; ?>
                        <?php else: ?>
                        <div style="font-size:11px;color:#94a3b8;font-style:italic;">QC review pending</div>
                        <?php endif; ?>
                    </div>

                    <!-- PM Approval Details -->
                    <div style="background:<?= $pmSt === 'Approved' ? 'rgba(25,135,84,.04)' : '#f8fafc' ?>;
                                border-radius:10px;padding:12px;
                                border:1px solid <?= $pmSt === 'Approved' ? 'rgba(25,135,84,.2)' : '#e2e8f0' ?>;">
                        <div style="font-size:10px;font-weight:700;color:#94a3b8;margin-bottom:8px;
                                    text-transform:uppercase;letter-spacing:.5px;">
                            <i class="bi bi-person-check me-1" style="color:<?= $pmColor ?>;"></i>PM Approval
                        </div>
                        <?php if (!empty($log['pm_approved_id'])): ?>
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                            <div style="width:32px;height:32px;background:<?= $pmColor ?>20;border-radius:50%;
                                        display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="bi bi-person" style="color:<?= $pmColor ?>;"></i>
                            </div>
                            <div>
                                <div style="font-size:12px;font-weight:700;color:#1e293b;">
                                    <?= htmlspecialchars($log['pm_name'] ?? 'PM') ?>
                                </div>
                                <div style="font-size:10px;color:#94a3b8;">
                                    <?= htmlspecialchars($log['pm_designation'] ?? '') ?>
                                </div>
                            </div>
                            <?= $statusBadge($pmSt) ?>
                        </div>
                        <?php if (!empty($log['pm_comment'])): ?>
                        <div style="font-size:11.5px;color:#64748b;background:rgba(0,0,0,.03);
                                    border-radius:6px;padding:6px 8px;">
                            <?= nl2br(htmlspecialchars($log['pm_comment'])) ?>
                        </div>
                        <?php endif; ?>
                        <?php else: ?>
                        <div style="font-size:11px;color:#94a3b8;font-style:italic;">PM review pending</div>
                        <?php endif; ?>
                    </div>

                </div><!-- /grid -->
            </div><!-- /accordion body -->
        </div><!-- /log item -->
        <?php endforeach; ?>
    </div>
</div>
<?php endif; // daily logs ?>

<!-- ══ SITE PHOTOS GALLERY ══ -->
<?php if (!empty($wPhotos)): ?>
<div style="background:#fff;border:1.5px solid rgba(245,158,11,.2);border-radius:12px;
            margin-bottom:16px;overflow:hidden;">
    <div style="background:linear-gradient(90deg,rgba(245,158,11,.07),transparent);
                padding:10px 16px;border-bottom:1px solid rgba(245,158,11,.1);">
        <div style="font-size:12.5px;font-weight:700;color:#f59e0b;">
            <i class="bi bi-images me-2"></i>Site Photos — Week <?= $activeWeek ?>
            <span style="font-size:11px;font-weight:500;color:#94a3b8;margin-left:6px;">(<?= count($wPhotos) ?> টি)</span>
        </div>
    </div>
    <div style="padding:14px 16px;display:flex;flex-wrap:wrap;gap:10px;">
        <?php foreach ($wPhotos as $photo): ?>
        <a href="<?= $base_url ?>/<?= htmlspecialchars($photo) ?>" target="_blank">
            <img src="<?= $base_url ?>/<?= htmlspecialchars($photo) ?>"
                 style="width:100px;height:80px;object-fit:cover;border-radius:8px;
                        border:2px solid rgba(245,158,11,.2);transition:transform .2s;"
                 onmouseenter="this.style.transform='scale(1.05)'"
                 onmouseleave="this.style.transform=''">
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php endif; // phaseTarget ?>

<!-- ══ ACCORDION JS ══ -->
<script>
function toggleLog(id) {
    const body = document.getElementById(id);
    const icon = document.getElementById('icon_' + id);
    if (!body) return;
    const isOpen = body.style.display !== 'none';
    body.style.display = isOpen ? 'none' : '';
    if (icon) icon.style.transform = isOpen ? '' : 'rotate(180deg)';
}
</script>