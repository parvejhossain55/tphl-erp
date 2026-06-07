<?php
$base_url     = $GLOBALS['base_url'];
$page_title   = $page_title ?? 'Weekly Progress Reports';
$projects     = $projects   ?? [];
$userId       = (int)($_SESSION['user']['id']      ?? 0);
$roleId       = (int)($_SESSION['user']['role_id'] ?? 0);
?>

<!-- ══ PAGE TITLE ══ -->
<div class="pagetitle d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 style="font-size:1.25rem;font-weight:800;color:#1e293b;margin:0;">
            <i class="bi bi-calendar-week me-2" style="color:var(--clr-theme-primary);"></i>
            সাপ্তাহিক Progress Report
        </h1>
        <nav><ol class="breadcrumb" style="font-size:11.5px;margin:2px 0 0;">
            <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
            <li class="breadcrumb-item active">Weekly Reports</li>
        </ol></nav>
    </div>
</div>

<!-- ══ HERO BAND ══ -->
<div style="background:linear-gradient(135deg,var(--clr-theme-primary),var(--clr-theme-secondary));border-radius:14px;padding:20px 24px;
            margin-bottom:24px;color:#fff;display:flex;align-items:center;gap:16px;">
    <div style="width:52px;height:52px;background:rgba(255,255,255,.15);border-radius:12px;
                display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <i class="bi bi-bar-chart-steps" style="font-size:26px;"></i>
    </div>
    <div>
        <div style="font-size:18px;font-weight:800;letter-spacing:-.3px;">Phase Weekly Progress Reports</div>
        <div style="font-size:12.5px;opacity:.85;margin-top:3px;">
            Project বেছে নিন — মাস ও week অনুযায়ী daily log, QC ও PM approval সহ সম্পূর্ণ report দেখুন
        </div>
    </div>
    <div style="margin-left:auto;font-size:12px;opacity:.75;">
        <?= count($projects) ?> টি project
    </div>
</div>

<?php if (empty($projects)): ?>
<div style="text-align:center;padding:60px 20px;background:#f8fafc;border-radius:12px;border:1px dashed #cbd5e1;">
    <i class="bi bi-folder2-open" style="font-size:40px;color:#cbd5e1;display:block;margin-bottom:12px;"></i>
    <div style="font-size:14px;color:#94a3b8;">কোনো project পাওয়া যায়নি</div>
</div>
<?php else: ?>
<!-- ══ SEARCH BAR ══ -->
<div class="mb-3">
    <input type="text" id="projectSearch" class="form-control"
           placeholder="🔍 Project নামে খুঁজুন..."
           style="border-radius:10px;font-size:13px;border:1.5px solid #e2e8f0;padding:10px 14px;">
</div>

<!-- ══ PROJECT GRID ══ -->
<div id="projectGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px;">
    <?php foreach ($projects as $proj):
        $hasTargets = (int)($proj['total_targets'] ?? 0) > 0;
        $hasWeekly  = (int)($proj['total_weekly_sessions'] ?? 0) > 0;
        $lastAct    = !empty($proj['last_activity'])
            ? date('d M Y', strtotime($proj['last_activity']))
            : null;
        $statusColor = match($proj['project_status'] ?? '') {
            'Active'     => '#10b981',
            'On Hold'    => '#f59e0b',
            'Completed'  => 'var(--clr-theme-primary)',
            default      => '#94a3b8',
        };
    ?>
    <a href="<?= $base_url ?>/con-weekly-report/<?= $proj['id'] ?>"
       class="project-card"
       data-name="<?= strtolower(htmlspecialchars($proj['name'])) ?>"
       style="display:block;text-decoration:none;background:#fff;border-radius:12px;
              border:1.5px solid rgba(25,135,84,.12);padding:16px 18px;
              transition:all .2s;box-shadow:0 1px 4px rgba(0,0,0,.04);">
        <!-- Header row -->
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:10px;">
            <div style="font-size:14px;font-weight:700;color:#1e293b;line-height:1.3;flex:1;margin-right:8px;">
                <?= htmlspecialchars($proj['name']) ?>
            </div>
            <span style="font-size:9.5px;font-weight:700;padding:2px 8px;border-radius:999px;
                         background:<?= strpos($statusColor, 'var') !== false ? 'rgba(25,135,84,.08)' : $statusColor . '18' ?>;
                         color:<?= $statusColor ?>;
                         border:1px solid <?= strpos($statusColor, 'var') !== false ? 'rgba(25,135,84,.3)' : $statusColor . '30' ?>;white-space:nowrap;flex-shrink:0;">
                <?= htmlspecialchars($proj['project_status'] ?? 'Active') ?>
            </span>
        </div>

        <!-- Block/Plot -->
        <?php if (!empty($proj['block_no']) || !empty($proj['plot_no'])): ?>
        <div style="font-size:11px;color:#94a3b8;margin-bottom:10px;">
            <i class="bi bi-geo-alt me-1"></i>
            <?= htmlspecialchars(($proj['block_no'] ?? '') . ' / ' . ($proj['plot_no'] ?? '')) ?>
        </div>
        <?php endif; ?>

        <!-- Stats row -->
        <div style="display:flex;gap:10px;">
            <div style="flex:1;background:rgba(25,135,84,.05);border-radius:8px;padding:8px 10px;text-align:center;">
                <div style="font-size:18px;font-weight:900;color:var(--clr-theme-primary);">
                    <?= $proj['total_targets'] ?? 0 ?>
                </div>
                <div style="font-size:9.5px;color:#94a3b8;margin-top:1px;">Targets</div>
            </div>
            <div style="flex:1;background:rgba(16,185,129,.05);border-radius:8px;padding:8px 10px;text-align:center;">
                <div style="font-size:18px;font-weight:900;color:#10b981;">
                    <?= $proj['total_weekly_sessions'] ?? 0 ?>
                </div>
                <div style="font-size:9.5px;color:#94a3b8;margin-top:1px;">Week Sessions</div>
            </div>
        </div>

        <!-- Last activity -->
        <?php if ($lastAct): ?>
        <div style="font-size:10.5px;color:#94a3b8;margin-top:10px;border-top:1px solid #f1f5f9;padding-top:8px;">
            <i class="bi bi-clock me-1"></i>Last activity: <?= $lastAct ?>
        </div>
        <?php endif; ?>

        <!-- Arrow -->
        <div style="text-align:right;margin-top:6px;">
            <span style="font-size:11px;color:var(--clr-theme-primary);font-weight:600;">
                Report দেখুন <i class="bi bi-arrow-right-short"></i>
            </span>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ══ SEARCH JS ══ -->
<script>
document.getElementById('projectSearch')?.addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('.project-card').forEach(card => {
        const name = card.dataset.name || '';
        card.closest('a').style.display = name.includes(q) ? '' : 'none';
    });
});
// Hover effect
document.querySelectorAll('.project-card').forEach(el => {
    el.addEventListener('mouseenter', () => {
        el.style.transform = 'translateY(-2px)';
        el.style.boxShadow = '0 6px 20px rgba(25,135,84,.15)';
        el.style.borderColor = 'rgba(25,135,84,.3)';
    });
    el.addEventListener('mouseleave', () => {
        el.style.transform = '';
        el.style.boxShadow = '0 1px 4px rgba(0,0,0,.04)';
        el.style.borderColor = 'rgba(25,135,84,.12)';
    });
});
</script>