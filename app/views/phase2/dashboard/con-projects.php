<?php
$addPage = true;
$show_breadcrumb = false;
$base = $GLOBALS['base_url'];

$projects = $projects ?? [];
$statusCounts = $statusCounts ?? [];

// Check if current user can access phase-based workflow
$userRoleId = $_SESSION['user']['role_id'] ?? 0;
$canPhaseWork = in_array($userRoleId, [1, 3, 15, 22]);

function cpFmt($n)
{
  $n = (float) $n;
  if ($n >= 10000000)
    return '৳' . number_format($n / 10000000, 2) . ' Cr';
  if ($n >= 100000)
    return '৳' . number_format($n / 100000, 2) . ' L';
  if ($n >= 1000)
    return '৳' . number_format($n / 1000, 1) . 'K';
  return '৳' . number_format($n, 0);
}

$statusMeta = [
  'Planning' => ['color' => '#6366f1', 'bg' => 'rgba(99,102,241,.12)', 'icon' => '<i class="fal fa-clipboard-list"></i>', 'dot' => '#6366f1'],
  'In Progress' => ['color' => '#f59e0b', 'bg' => 'rgba(245,158,11,.12)', 'icon' => '<i class="fal fa-cog"></i>', 'dot' => '#f59e0b'],
  'On Hold' => ['color' => '#ef4444', 'bg' => 'rgba(239,68,68,.12)', 'icon' => '<i class="fal fa-pause-circle"></i>', 'dot' => '#ef4444'],
  'Completed' => ['color' => '#10b981', 'bg' => 'rgba(16,185,129,.12)', 'icon' => '<i class="fal fa-check-circle"></i>', 'dot' => '#10b981'],
];
?>
<div class="row">
  <div class="col-12">
    <style>
      /* ── Google Font ─────────────────────────────────────── */
      @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

      /* ── Variables ───────────────────────────────────────── */
      :root {
        --cp-blue: #3b82f6;
        --cp-green: #10b981;
        --cp-amber: #f59e0b;
        --cp-red: #ef4444;
        --cp-indigo: #6366f1;
        --cp-violet: #8b5cf6;
        --cp-teal: #14b8a6;
      }

      /* ── Page Header ─────────────────────────────────────── */
      .cp-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 28px;
        flex-wrap: wrap;
        gap: 14px;
      }

      .cp-header-left h4 {
        font-size: 22px;
        font-weight: 800;
        margin: 0 0 4px;
        letter-spacing: -.3px;
      }

      .cp-breadcrumb {
        font-size: 12px;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 6px;
      }

      .cp-breadcrumb a {
        color: var(--cp-blue);
        text-decoration: none;
      }

      .cp-breadcrumb a:hover {
        text-decoration: underline;
      }

      /* ── Summary Strip ───────────────────────────────────── */
      .cp-summary-strip {
        display: flex;
        gap: 12px;
        margin-bottom: 24px;
        flex-wrap: wrap;
      }

      .cp-stat-pill {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        border: 1px solid rgba(0, 0, 0, .08);
        cursor: pointer;
        transition: transform .15s, box-shadow .15s;
        /* background: var(--clr-bg-card, #fff); */
      }

      .cp-stat-pill:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(0, 0, 0, .09);
      }

      .cp-stat-pill-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
      }

      .cp-stat-pill span.count {
        font-size: 15px;
        font-weight: 900;
      }

      /* ── Search + Filter Bar ─────────────────────────────── */
      .cp-filter-bar {
        display: flex;
        gap: 12px;
        margin-bottom: 22px;
        flex-wrap: wrap;
        align-items: center;
      }

      .cp-search-wrap {
        position: relative;
        flex: 1;
        min-width: 200px;
      }

      .cp-search-wrap input {
        width: 100%;
        padding: 9px 14px 9px 38px;
        border-radius: 10px;
        border: 1px solid rgba(0, 0, 0, .12);
        font-size: 13px;
        /* background: var(--clr-bg-card, #fff); */
        color: inherit;
        outline: none;
        transition: border-color .2s, box-shadow .2s;
      }

      .cp-search-wrap input:focus {
        border-color: var(--cp-green);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, .12);
      }

      .cp-search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 14px;
        opacity: .45;
      }

      .cp-filter-select {
        padding: 9px 14px;
        border-radius: 10px;
        border: 1px solid rgba(0, 0, 0, .12);
        font-size: 12.5px;
        font-weight: 600;
        /* background: var(--clr-bg-card, #fff); */
        color: inherit;
        cursor: pointer;
        outline: none;
      }

      .cp-filter-select:focus {
        border-color: var(--cp-green);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, .12);
      }

      /* ── Grid ────────────────────────────────────────────── */
      .cp-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
      }

      /* ── Project Card ────────────────────────────────────── */
      .cp-card {
        border-radius: 16px;
        border: 1px solid rgba(0, 0, 0, .08);
        overflow: hidden;
        /* background: var(--clr-bg-card, #fff); */
        cursor: pointer;
        transition: transform .2s ease, box-shadow .2s ease;
        display: flex;
        flex-direction: column;
        text-decoration: none !important;
        color: inherit !important;
      }

      .cp-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 36px rgba(0, 0, 0, .12);
      }

      /* Card top banner */
      .cp-card-banner {
        height: 7px;
      }

      /* Card body */
      .cp-card-body {
        padding: 20px 20px 16px;
        flex: 1;
        display: flex;
        flex-direction: column;
      }

      /* Top row */
      .cp-card-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 6px;
      }

      .cp-card-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
      }

      .cp-status-badge {
        font-size: 10px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 999px;
        letter-spacing: .4px;
        white-space: nowrap;
      }

      .cp-card-title {
        font-size: 15.5px;
        font-weight: 800;
        margin-bottom: 4px;
        line-height: 1.3;
      }

      .cp-card-loc {
        font-size: 11.5px;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 4px;
        margin-bottom: 14px;
      }

      /* Meta row */
      .cp-meta-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 16px;
      }

      .cp-meta-item {}

      .cp-meta-label {
        font-size: 10px;
        letter-spacing: .8px;
        text-transform: uppercase;
        color: var(--text-muted);
        margin-bottom: 2px;
        font-weight: 600;
      }

      .cp-meta-value {
        font-size: 13px;
        font-weight: 700;
      }

      /* Progress */
      .cp-progress-wrap {
        margin-top: auto;
      }

      .cp-prog-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 6px;
        font-size: 11px;
      }

      .cp-prog-label {
        color: var(--text-muted);
        font-weight: 600;
      }

      .cp-prog-pct {
        font-weight: 800;
        font-size: 13px;
      }

      .cp-prog-track {
        height: 7px;
        border-radius: 6px;
        background: rgba(0, 0, 0, .07);
        overflow: hidden;
      }

      .cp-prog-fill {
        height: 100%;
        border-radius: 6px;
        transition: width .6s cubic-bezier(.4, 0, .2, 1);
      }

      /* Divider */
      .cp-card-divider {
        height: 1px;
        background: rgba(0, 0, 0, .06);
        margin: 0 20px;
      }

      /* Card footer */
      .cp-card-footer {
        padding: 12px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
      }

      .cp-footer-stat {
        font-size: 11.5px;
        color: var(--text-muted);
      }

      .cp-footer-stat strong {
        color: var(--heading-color, #111);
        font-weight: 700;
      }

      .cp-view-btn {
        font-size: 11px;
        font-weight: 700;
        padding: 5px 14px;
        border-radius: 8px;
        color: var(--cp-green);
        background: rgba(59, 130, 246, .1);
        transition: background .15s;
        letter-spacing: .2px;
      }

      .cp-view-btn:hover {
        background: rgba(59, 130, 246, .18);
      }

      /* ── Empty State ─────────────────────────────────────── */
      .cp-empty {
        text-align: center;
        padding: 60px 20px;
        grid-column: 1/-1;
      }

      .cp-empty-icon {
        font-size: 52px;
        margin-bottom: 14px;
        opacity: .3;
      }

      .cp-empty-text {
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 6px;
      }

      .cp-empty-sub {
        font-size: 13px;
        color: var(--text-muted);
      }

      /* ── Responsive ──────────────────────────────────────── */
      @media (max-width: 1100px) {
        .cp-grid {
          grid-template-columns: repeat(2, 1fr);
        }
      }

      @media (max-width: 700px) {
        .cp-grid {
          grid-template-columns: 1fr;
        }
      }
    </style>

    <!-- ════════════════════════════ PAGE HEADER ════════════════════════════ -->
    <div class="cp-header">
      <div class="cp-header-left">
        <h4><i class="fal fa-building me-2" style="color:var(--cp-blue)"></i>Construction Projects</h4>
        <div class="cp-breadcrumb">
          <a href="<?= $base ?>/con-dashboard"><i class="fal fa-tachometer-alt me-1"></i>Dashboard</a>
          <span>›</span>
          <span>All Projects</span>
        </div>
      </div>
      <div class="d-flex gap-2 d-none">
        <a href="<?= $base ?>/create-project" class="btn btn-sm btn-primary" style="font-size:12px"><i
            class="fal fa-plus me-1"></i>New Project</a>
        <a href="<?= $base ?>/con-dashboard" class="btn btn-sm btn-outline-secondary" style="font-size:12px"><i
            class="fal fa-arrow-left me-1"></i>Dashboard</a>
      </div>
    </div>

    <!-- ════════════════════════ STATUS SUMMARY PILLS ═══════════════════════ -->
    <div class="cp-summary-strip">
      <!-- All -->
      <div class="cp-stat-pill" onclick="filterCards('')" style="border-color:var(--cp-blue)">
        <div class="cp-stat-pill-dot" style="background:var(--cp-blue)"></div>
        <span class="count" style="color:var(--cp-blue)"><i
            class="fal fa-th-large me-1"></i><?= count($projects) ?></span>
        <span>All Projects</span>
      </div>
      <?php foreach ($statusMeta as $status => $meta): ?>
        <?php if (!empty($statusCounts[$status])): ?>
          <div class="cp-stat-pill" onclick="filterCards('<?= $status ?>')" style="border-color:<?= $meta['dot'] ?>">
            <div class="cp-stat-pill-dot" style="background:<?= $meta['dot'] ?>"></div>
            <span class="count" style="color:<?= $meta['color'] ?>"><?= $statusCounts[$status] ?></span>
            <span><?= $status ?></span>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>

    <!-- ════════════════════════ SEARCH + FILTER BAR ════════════════════════ -->
    <div class="cp-filter-bar">
      <div class="cp-search-wrap">
        <span class="cp-search-icon"><i class="fal fa-search"></i></span>
        <input type="text" id="cpSearch" placeholder="Search projects by name or location…" oninput="filterCards()">
      </div>
      <select class="cp-filter-select" id="cpStatusFilter" onchange="filterCards()">
        <option value="">All Statuses</option>
        <option value="Planning">Planning</option>
        <option value="In Progress">In Progress</option>
        <option value="On Hold">On Hold</option>
        <option value="Completed">Completed</option>
      </select>
      <select class="cp-filter-select" id="cpSortFilter" onchange="filterCards()">
        <option value="newest">Newest First</option>
        <option value="progress_hi">Progress ↑</option>
        <option value="progress_lo">Progress ↓</option>
        <option value="budget_hi">Budget ↑</option>
      </select>
    </div>

    <!-- ══════════════════════════ PROJECT CARD GRID ════════════════════════ -->
    <div class="cp-grid" id="cpGrid">
      <?php if (empty($projects)): ?>
        <div class="cp-empty">
          <div class="cp-empty-icon"><i class="fal fa-building"></i></div>
          <div class="cp-empty-text">No Projects Found</div>
          <div class="cp-empty-sub">Start by creating your first construction project.</div>
        </div>
      <?php else: ?>
        <?php foreach ($projects as $pr):
          $pct = round((float) ($pr['progress_pct'] ?? 0), 1);
          $status = $pr['project_status'] ?? 'Planning';
          $meta = $statusMeta[$status] ?? ['color' => '#888', 'bg' => 'rgba(0,0,0,.07)', 'icon' => '🏗️', 'dot' => '#888'];
          $grad = $pct >= 75 ? 'linear-gradient(90deg,#059669,#10b981)'
            : ($pct >= 40 ? 'linear-gradient(90deg,#d97706,#f59e0b)'
              : 'linear-gradient(90deg,#dc2626,#ef4444)');
          $pctColor = $pct >= 75 ? '#10b981' : ($pct >= 40 ? '#f59e0b' : '#ef4444');
          $budgetUsedPct = ($pr['total_budget'] > 0) ? round($pr['total_spent'] / $pr['total_budget'] * 100, 1) : 0;
          $startDate = $pr['start_date'] ? date('M Y', strtotime($pr['start_date'])) : '—';
          $endDate = $pr['end_date'] ? date('M Y', strtotime($pr['end_date'])) : '—';
          $isPhaseBased = ($pr['workflow_version'] ?? '') === 'phase_based';
          // Card href: phase-based যাবে timeline এ, regular যাবে project detail এ
          $cardHref = ($canPhaseWork && $isPhaseBased)
              ? $base . '/con-project-timeline/' . $pr['id']
              : $base . '/con-project/' . $pr['id'];
          ?>
          <a href="<?= $cardHref ?>" class="cp-card" data-status="<?= htmlspecialchars($status) ?>"
            data-name="<?= htmlspecialchars(strtolower($pr['name'])) ?>"
            data-loc="<?= htmlspecialchars(strtolower($pr['location_name'])) ?>" data-progress="<?= $pct ?>"
            data-budget="<?= $pr['total_budget'] ?>" data-id="<?= $pr['id'] ?>">

            <!-- Coloured top banner -->
            <div class="cp-card-banner" style="background:<?= $meta['dot'] ?>"></div>

            <!-- Body -->
            <div class="cp-card-body card__wrapper">

              <!-- Top row: icon + status badge -->
              <div class="cp-card-top">
                <div class="cp-card-icon" style="background:<?= $meta['bg'] ?>">
                  <?= $meta['icon'] ?>
                </div>
                <span class="cp-status-badge" style="background:<?= $meta['bg'] ?>;color:<?= $meta['color'] ?>">
                  <?= htmlspecialchars($status) ?>
                </span>
              </div>

              <!-- Title + location -->
              <div class="cp-card-title"><?= htmlspecialchars($pr['name']) ?></div>
              <div class="cp-card-loc">
                <i class="fal fa-map-marker-alt me-1"></i><?= htmlspecialchars($pr['location_name'] ?: 'Location N/A') ?>
                <?php if ($pr['building_type']): ?>
                  &nbsp;·&nbsp; <i class="fal fa-city me-1"></i><?= htmlspecialchars($pr['building_type']) ?>
                <?php endif; ?>
              </div>

              <!-- Meta info -->
              <div class="cp-meta-row">
                <div class="cp-meta-item">
                  <div class="cp-meta-label">Total Budget</div>
                  <div class="cp-meta-value" style="color:var(--cp-green)"><?= cpFmt($pr['total_budget']) ?></div>
                </div>
                <div class="cp-meta-item">
                  <div class="cp-meta-label">Amount Spent</div>
                  <div class="cp-meta-value" style="color:<?= $budgetUsedPct > 80 ? '#ef4444' : 'var(--cp-green)' ?>">
                    <?= cpFmt($pr['total_spent']) ?></div>
                </div>
                <div class="cp-meta-item">
                  <div class="cp-meta-label">Start Date</div>
                  <div class="cp-meta-value" style="font-size:12px"><?= $startDate ?></div>
                </div>
                <div class="cp-meta-item">
                  <div class="cp-meta-label">End Date</div>
                  <div class="cp-meta-value" style="font-size:12px"><?= $endDate ?></div>
                </div>
              </div>

              <!-- Progress -->
              <div class="cp-progress-wrap">
                <div class="cp-prog-header">
                  <span class="cp-prog-label">Construction Progress</span>
                  <span class="cp-prog-pct" style="color:<?= $pctColor ?>"><?= $pct ?>%</span>
                </div>
                <div class="cp-prog-track">
                  <div class="cp-prog-fill" style="width:<?= $pct ?>%;background:<?= $grad ?>"></div>
                </div>
              </div>

            </div>

            <div class="cp-card-divider"></div>

            <!-- Footer -->
            <div class="cp-card-footer">
              <div class="cp-footer-stat">
                <?php if ($isPhaseBased): ?>
                  <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;background:rgba(99,102,241,.12);color:#6366f1;letter-spacing:.3px;">⚡ Phase-Based</span>
                <?php elseif ($pr['total_floors']): ?>
                  <i class="fal fa-layer-group me-1"></i><strong><?= $pr['total_floors'] ?></strong> Floors
                <?php else: ?>
                  <i class="fal fa-ruler-combined me-1"></i>Plot:
                  <strong><?= $pr['plot_size'] ? $pr['plot_size'] . ' sqft' : '—' ?></strong>
                <?php endif; ?>
              </div>
              <?php if ($canPhaseWork && $isPhaseBased): ?>
                <div class="cp-view-btn" style="background:rgba(99,102,241,.12);color:#6366f1">Open Phase Work <i class="fal fa-arrow-right ms-1"></i></div>
              <?php else: ?>
                <div class="cp-view-btn">View Details <i class="fal fa-arrow-right ms-1"></i></div>
              <?php endif; ?>
            </div>

          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Empty search state -->
    <div id="cpNoResults" style="display:none;text-align:center;padding:50px;grid-column:1/-1">
      <div style="font-size:40px;margin-bottom:12px;opacity:.3"><i class="fal fa-search"></i></div>
      <div style="font-size:15px;font-weight:700;margin-bottom:6px">No Projects Match</div>
      <div style="font-size:13px;color:var(--text-muted)">Try adjusting your search or filter.</div>
    </div>

    <div style="height:40px"></div>

    <script>
      // ----- Filter / Search / Sort logic -----
      const allCards = () => document.querySelectorAll('#cpGrid .cp-card');

      function filterCards(statusOverride) {
        if (statusOverride !== undefined) {
          document.getElementById('cpStatusFilter').value = statusOverride;
        }
        const q = document.getElementById('cpSearch').value.toLowerCase().trim();
        const status = document.getElementById('cpStatusFilter').value;
        const sort = document.getElementById('cpSortFilter').value;

        let cards = Array.from(allCards());

        // Filter
        cards.forEach(c => {
          const matchQ = !q || c.dataset.name.includes(q) || c.dataset.loc.includes(q);
          const matchStatus = !status || c.dataset.status === status;
          c.style.display = (matchQ && matchStatus) ? '' : 'none';
        });

        // Sort visible cards
        const grid = document.getElementById('cpGrid');
        const visible = cards.filter(c => c.style.display !== 'none');
        visible.sort((a, b) => {
          if (sort === 'progress_hi') return parseFloat(b.dataset.progress) - parseFloat(a.dataset.progress);
          if (sort === 'progress_lo') return parseFloat(a.dataset.progress) - parseFloat(b.dataset.progress);
          if (sort === 'budget_hi') return parseFloat(b.dataset.budget) - parseFloat(a.dataset.budget);
          return parseInt(b.dataset.id) - parseInt(a.dataset.id); // newest
        });
        visible.forEach(c => grid.appendChild(c));

        // No-results state
        document.getElementById('cpNoResults').style.display = visible.length === 0 ? 'block' : 'none';
      }

      // Animate progress bars on load
      window.addEventListener('load', () => {
        document.querySelectorAll('.cp-prog-fill').forEach(bar => {
          const w = bar.style.width;
          bar.style.width = '0';
          setTimeout(() => { bar.style.width = w; }, 200);
        });
      });
    </script>

  </div>
</div>