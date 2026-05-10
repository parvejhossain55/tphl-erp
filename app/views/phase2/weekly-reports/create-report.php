<?php
$addPage = true;
$base    = $GLOBALS['base_url'];
$projects = $projects ?? [];
?>
<style>
  :root {
    --wr-green: #198754;
    --wr-dark: #0f5132;
  }

  .wr-hero {
    background: linear-gradient(135deg, #0f5132 0%, #198754 55%, #20a86a 100%);
    border-radius: 18px;
    padding: 22px 28px;
    margin-bottom: 20px;
    color: #fff;
    position: relative;
    overflow: hidden;
  }

  .wr-hero::before {
    content: '';
    position: absolute;
    width: 200px;
    height: 200px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .05);
    top: -60px;
    right: -40px;
  }

  .wr-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(255, 255, 255, .13);
    border: 1px solid rgba(255, 255, 255, .22);
    padding: 3px 14px;
    border-radius: 20px;
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: 1.3px;
    text-transform: uppercase;
    margin-bottom: 10px;
  }

  .wr-card {
    border-radius: 16px;
    border: 1px solid rgba(0, 0, 0, .08);
    /* background: var(--card-bg, #fff); */
    padding: 28px;
    margin-bottom: 20px;
  }

  .wr-section-title {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1.4px;
    text-transform: uppercase;
    color: var(--wr-green);
    margin-bottom: 16px;
    padding-bottom: 10px;
    border-bottom: 2px solid rgba(25, 135, 84, .12);
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .wr-btn-green {
    background: linear-gradient(135deg, #0f5132, #198754);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 10px 26px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: opacity .15s;
  }

  .wr-btn-green:hover {
    opacity: .88;
  }
</style>

<!-- Hero -->
<div class="wr-hero">
  <div style="position:relative;z-index:2">
    <div class="wr-badge"><i class="fal fa-calendar-week"></i> New Report</div>
    <h4 style="font-size:22px;font-weight:900;margin:0 0 4px;color:#fff">New Weekly Progress Report</h4>
    <p style="font-size:12px;color:rgba(255,255,255,.7);margin:0">
      Project → Working Plan → Target → Week No
    </p>
  </div>
</div>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" style="margin-bottom:16px">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $base ?>/con-dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="<?= $base ?>/con-weekly-reports">Weekly Reports</a></li>
    <li class="breadcrumb-item active">New Report</li>
  </ol>
</nav>

<form action="<?= $base ?>/con-weekly-report-store" method="POST" enctype="multipart/form-data">

  <!-- Step 1: Project & Plan -->
  <div class="wr-card card__wrapper">
    <div class="wr-section-title"><i class="fal fa-project-diagram"></i> Step 1 — Project & Working Plan</div>
    <div class="row ">

      <div class="col-md-6">
        <label class="form-label fw-semibold">Project <span class="text-danger">*</span></label>
        <select id="project_id" name="project_id" class="form-select" required>
          <option value="">— Select Project —</option>
          <?php foreach ($projects as $p): ?>
            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <div id="plan-loader" class="mt-1 text-muted small" style="display:none">
          <i class="fa-spin fal fa-spinner me-1"></i> Loading plans...
        </div>
      </div>

      <div class="col-md-6">
        <label class="form-label fw-semibold">Working Plan <span class="text-danger">*</span></label>
        <select id="work_plan_id" name="work_plan_id" class="form-select" required disabled>
          <option value="">— Select project first —</option>
        </select>
        <div id="plan-info" class="mt-1 text-muted small"></div>
      </div>

      <input type="hidden" name="site_engineer_id" value="<?= htmlspecialchars($_SESSION['user']['id'] ?? '') ?>">
    </div>
  </div>

  <!-- Step 2: Target & Week -->
  <div class="wr-card card__wrapper">
    <div class="wr-section-title"><i class="fal fa-bullseye"></i> Step 2 — Target & Week Number</div>
    <div class="row ">

      <div class="col-md-5">
        <label class="form-label fw-semibold">Target (Optional)</label>
        <select id="target_id" name="target_id" class="form-select" disabled>
          <option value="">— Select working plan first —</option>
        </select>
        <div id="target-loader" class="mt-1 text-muted small" style="display:none">
          <i class="fa-spin fal fa-spinner me-1"></i> Loading targets...
        </div>
        <small class="text-muted">Selects auto-fill year & month below</small>
      </div>

      <div class="col-md-2">
        <label class="form-label fw-semibold">Year</label>
        <input type="text" id="display_year" class="form-control bg-light" placeholder="Auto" readonly>
      </div>

      <div class="col-md-2">
        <label class="form-label fw-semibold">Month</label>
        <input type="text" id="display_month" class="form-control bg-light" placeholder="Auto" readonly>
      </div>

      <div class="col-md-3">
        <label class="form-label fw-semibold">Week No <span class="text-danger">*</span></label>
        <select id="week_no" name="week_no" class="form-select" required>
          <option value="">— Select Week —</option>
          <option value="1">Week 1</option>
          <option value="2">Week 2</option>
          <option value="3">Week 3</option>
          <option value="4">Week 4</option>
          <option value="5">Week 5</option>
        </select>
      </div>
    </div>
  </div>

  <!-- Step 3: Report Details -->
  <div class="wr-card card__wrapper">
    <div class="wr-section-title"><i class="fal fa-clipboard-list"></i> Step 3 — Report Details</div>
    <div class="row ">

      <div class="col-md-4">
        <label class="form-label fw-semibold">Report Date</label>
        <input type="date" name="report_date" class="form-control" value="<?= date('Y-m-d') ?>">
      </div>

      <div class="col-md-4">
        <label class="form-label fw-semibold">Progress % <span class="text-danger">*</span></label>
        <input type="number" name="progress_percentage" class="form-control"
          min="0" max="100" step="0.5" placeholder="e.g. 75" required>
      </div>

      <div class="col-md-4">
        <label class="form-label fw-semibold">Manpower Count</label>
        <input type="number" name="manpower_count" class="form-control" min="0" placeholder="e.g. 25">
      </div>

      <div class="col-12">
        <label class="form-label fw-semibold">Work Done / Notes <span class="text-danger">*</span></label>
        <textarea name="work_done" class="form-control" rows="5"
          placeholder="Describe the work completed this week…" required></textarea>
      </div>

      <div class="col-md-6">
        <label class="form-label fw-semibold">Weather / Additional Notes</label>
        <input type="text" name="weather_condition" class="form-control"
          placeholder="e.g. Sunny, Rain halted work for 2 hrs">
      </div>

      <div class="col-md-6">
        <label class="form-label fw-semibold">Progress Photo (optional)</label>
        <input type="file" name="progress_image" class="form-control" accept="image/*">
      </div>
    </div>
  </div>

  <div class="d-flex gap-2 mb-4">
    <button type="submit" class="wr-btn-green"><i class="fal fa-save"></i> Save Weekly Report</button>
    <a href="<?= $base ?>/con-weekly-reports"
      class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>

<script>
  const BASE_URL = '<?= $base ?>';

  // ── Cascade 1: Project → Work Plans (via existing con-ajax-project-data) ────
  document.getElementById('project_id').addEventListener('change', function() {
    const pid = this.value;
    const planSel = document.getElementById('work_plan_id');
    const planInfo = document.getElementById('plan-info');
    const planLoader = document.getElementById('plan-loader');
    const targetSel = document.getElementById('target_id');

    planSel.innerHTML = '<option value="">— Loading... —</option>';
    planSel.disabled = true;
    targetSel.innerHTML = '<option value="">— Select working plan first —</option>';
    targetSel.disabled = true;
    clearAutoFill();

    if (!pid) {
      planSel.innerHTML = '<option value="">— Select project first —</option>';
      return;
    }

    planLoader.style.display = 'block';

    fetch(BASE_URL + '/con-ajax-project-data?project_id=' + pid)
      .then(r => r.json())
      .then(data => {
        planLoader.style.display = 'none';
        planSel.innerHTML = '<option value="">— Select Working Plan —</option>';
        const plans = data.work_plans || [];
        if (plans.length === 0) {
          planSel.innerHTML += '<option disabled>No plans found for this project</option>';
        } else {
          plans.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.id;
            opt.textContent = p.label;
            opt.dataset.desc = p.work_description || '';
            planSel.appendChild(opt);
          });
        }
        planSel.disabled = false;
      })
      .catch(err => {
        planLoader.style.display = 'none';
        planSel.innerHTML = '<option value="">Error: Could not load plans</option>';
        planSel.disabled = false;
        console.error('Work plan AJAX error:', err);
      });
  });

  // ── Cascade 2: Work Plan → Targets ──────────────────────────────────────────
  document.getElementById('work_plan_id').addEventListener('change', function() {
    const wpid = this.value;
    const targetSel = document.getElementById('target_id');
    const targetLoader = document.getElementById('target-loader');
    const planInfo = document.getElementById('plan-info');

    // Show plan description
    const opt = this.options[this.selectedIndex];
    planInfo.textContent = opt && opt.dataset.desc ? '📋 ' + opt.dataset.desc : '';

    targetSel.innerHTML = '<option value="">— Loading targets... —</option>';
    targetSel.disabled = true;
    clearAutoFill();

    if (!wpid) {
      targetSel.innerHTML = '<option value="">— Select working plan first —</option>';
      return;
    }

    targetLoader.style.display = 'block';

    fetch(BASE_URL + '/con-ajax-report-targets?work_plan_id=' + wpid)
      .then(r => r.json())
      .then(data => {
        targetLoader.style.display = 'none';
        let html = '<option value="">— No specific target (optional) —</option>';
        if (Array.isArray(data) && data.length > 0) {
          data.forEach(t => {
            html += `<option value="${t.id}"
                        data-year="${t.year}"
                        data-month="${t.month}"
                        data-week="${t.week_number}">
                        ${t.target_title} — Week ${t.week_number || '?'} (${t.year}/${String(t.month).padStart(2,'0')})
                    </option>`;
          });
        }
        targetSel.innerHTML = html;
        targetSel.disabled = false;

        // Auto-select if only one
        if (data.length === 1) targetSel.selectedIndex = 1, targetSel.dispatchEvent(new Event('change'));
      })
      .catch(err => {
        targetLoader.style.display = 'none';
        targetSel.innerHTML = '<option value="">Error loading targets</option>';
        targetSel.disabled = false;
        console.error('Target AJAX error:', err);
      });
  });

  // ── Target → Auto-fill Year / Month / Week ──────────────────────────────────
  document.getElementById('target_id').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (opt && opt.dataset.year) {
      document.getElementById('display_year').value = opt.dataset.year;
      document.getElementById('display_month').value = opt.dataset.month;
      const wSel = document.getElementById('week_no');
      for (let i = 0; i < wSel.options.length; i++) {
        if (wSel.options[i].value == opt.dataset.week) {
          wSel.selectedIndex = i;
          break;
        }
      }
    } else {
      clearAutoFill();
    }
  });

  function clearAutoFill() {
    document.getElementById('display_year').value = '';
    document.getElementById('display_month').value = '';
  }
</script>