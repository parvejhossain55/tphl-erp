<?php
$addPage = true;
$base = $GLOBALS['base_url'];
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

  .wr-form-label {
    font-size: 11.5px;
    font-weight: 700;
    margin-bottom: 5px;
    color: var(--text);
    display: block;
  }

  .wr-form-label .req {
    color: #ef4444;
    margin-left: 2px;
  }

  .wr-form-control {
    width: 100%;
    border-radius: 10px;
    border: 1.5px solid rgba(0, 0, 0, .12);
    padding: 9px 13px;
    font-size: 13px;
    transition: border-color .15s;
    background: var(--input-bg, #fff);
  }

  .wr-form-control:focus {
    outline: none;
    border-color: #198754;
    box-shadow: 0 0 0 3px rgba(25, 135, 84, .12);
  }

  .wr-form-control:disabled {
    background: rgba(0, 0, 0, .04);
    color: var(--text-muted);
  }

  .wr-hint {
    font-size: 10.5px;
    color: var(--text-muted);
    margin-top: 3px;
  }

  .wr-separator {
    border: none;
    border-top: 1px solid rgba(0, 0, 0, .07);
    margin: 18px 0;
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

  .wr-cascade-loader {
    display: none;
    font-size: 11px;
    color: var(--wr-green);
    margin-top: 5px;
    align-items: center;
    gap: 6px;
  }

  .wr-cascade-loader.show {
    display: flex;
  }
</style>

<!-- Hero -->
<div class="wr-hero">
  <div style="position:relative;z-index:2">
    <div class="wr-badge"><i class="fal fa-calendar-week"></i> New Report</div>
    <h4 style="font-size:22px;font-weight:900;margin:0 0 4px">New Weekly Progress Report</h4>
    <p style="font-size:12px;color:rgba(255,255,255,.7);margin:0">
      Select Project → Working Plan → Target (auto-fills Year & Month) → Week No
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

  <!-- ─── Step 1: Project & Plan Cascade ─── -->
  <div class="wr-card card__wrapper">
    <div class="wr-section-title"><i class="fal fa-project-diagram"></i> Step 1 — Select Project & Working Plan</div>

    <div class="row ">
      <!-- Project -->
      <div class="col-md-6">
        <label class="wr-form-label" for="project_id">Project <span class="req">*</span></label>
        <select id="project_id" name="project_id" class="wr-form-control" required>
          <option value="">— Select Project —</option>
          <?php foreach ($projects as $p): ?>
            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="wr-cascade-loader" id="plan-loader"><i class="fa-spin fal fa-spinner"></i> Loading plans...</div>
      </div>

      <!-- Working Plan (loaded via AJAX) -->
      <div class="col-md-6">
        <label class="wr-form-label" for="work_plan_id">Working Plan <span class="req">*</span></label>
        <select id="work_plan_id" name="work_plan_id" class="wr-form-control" required disabled>
          <option value="">— Select project first —</option>
        </select>
        <div class="wr-cascade-loader" id="target-loader"><i class="fa-spin fal fa-spinner"></i> Loading targets...</div>
      </div>

      <!-- Site Engineer (hidden, auto-filled from session) -->
      <input type="hidden" name="site_engineer_id" value="<?= htmlspecialchars($_SESSION['user']['id'] ?? '') ?>">
    </div>
  </div>

  <!-- ─── Step 2: Target & Week ─── -->
  <div class="wr-card card__wrapper">
    <div class="wr-section-title"><i class="fal fa-bullseye"></i> Step 2 — Select Target & Week Number</div>

    <div class="row ">
      <!-- Target (loaded via AJAX after work plan selected) -->
      <div class="col-md-5">
        <label class="wr-form-label" for="target_id">Target (Optional)</label>
        <select id="target_id" name="target_id" class="wr-form-control" disabled>
          <option value="">— Select working plan first —</option>
        </select>
        <div class="wr-hint">Selecting a target auto-fills Year & Month below</div>
      </div>

      <!-- Year (auto-filled, can edit) -->
      <div class="col-md-2">
        <label class="wr-form-label" for="display_year">Year</label>
        <input type="text" id="display_year" class="wr-form-control" placeholder="e.g. 2025" readonly
          style="background:rgba(25,135,84,.04);border-color:rgba(25,135,84,.2)">
      </div>

      <!-- Month (auto-filled) -->
      <div class="col-md-2">
        <label class="wr-form-label" for="display_month">Month</label>
        <input type="text" id="display_month" class="wr-form-control" placeholder="e.g. 6" readonly
          style="background:rgba(25,135,84,.04);border-color:rgba(25,135,84,.2)">
      </div>

      <!-- Week No -->
      <div class="col-md-3">
        <label class="wr-form-label" for="week_no">Week No <span class="req">*</span></label>
        <select id="week_no" name="week_no" class="wr-form-control" required>
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

  <!-- ─── Step 3: Report Details ─── -->
  <div class="wr-card card__wrapper">
    <div class="wr-section-title"><i class="fal fa-clipboard-list"></i> Step 3 — Report Details</div>

    <div class="row ">
      <!-- Report Date -->
      <div class="col-md-4">
        <label class="wr-form-label" for="report_date">Report Date</label>
        <input type="date" id="report_date" name="report_date" class="wr-form-control"
          value="<?= date('Y-m-d') ?>">
      </div>

      <!-- Progress % -->
      <div class="col-md-4">
        <label class="wr-form-label" for="progress_percentage">Progress % <span class="req">*</span></label>
        <input type="number" id="progress_percentage" name="progress_percentage"
          class="wr-form-control" min="0" max="100" step="0.5" placeholder="e.g. 75" required>
      </div>

      <!-- Manpower -->
      <div class="col-md-4">
        <label class="wr-form-label" for="manpower_count">Manpower Count</label>
        <input type="number" id="manpower_count" name="manpower_count"
          class="wr-form-control" min="0" placeholder="e.g. 25">
      </div>

      <!-- Work Done -->
      <div class="col-12">
        <label class="wr-form-label" for="work_done">Work Done (Notes) <span class="req">*</span></label>
        <textarea id="work_done" name="work_done" class="wr-form-control" rows="5"
          placeholder="Describe the work completed this week…" required></textarea>
      </div>

      <!-- Weather Condition -->
      <div class="col-md-6">
        <label class="wr-form-label" for="weather_condition">Weather Condition / Notes</label>
        <input type="text" id="weather_condition" name="weather_condition"
          class="wr-form-control" placeholder="e.g. Sunny, Rain delayed work for 2 hours">
      </div>

      <!-- Progress Image -->
      <div class="col-md-6">
        <label class="wr-form-label" for="progress_image">Progress Photo (optional)</label>
        <input type="file" id="progress_image" name="progress_image"
          class="wr-form-control" accept="image/*">
        <div class="wr-hint">JPG, PNG, WEBP — max 5MB</div>
      </div>
    </div>
  </div>

  <!-- Buttons -->
  <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:30px">
    <button type="submit" class="wr-btn-green" id="submitBtn">
      <i class="fal fa-save"></i> Save Weekly Report
    </button>
    <a href="<?= $base ?>/con-weekly-reports"
      style="border:1.5px solid rgba(0,0,0,.12);border-radius:10px;padding:10px 22px;
        font-size:13px;font-weight:700;color:var(--text-muted);text-decoration:none">
      <i class="fal fa-times me-1"></i> Cancel
    </a>
  </div>
</form>

<script>
  const base = '<?= $base ?>';

  // ── Cascade: Project → Work Plans ──────────────────────────────────────────
  document.getElementById('project_id').addEventListener('change', function() {
    const pid = this.value;
    const planSel = document.getElementById('work_plan_id');
    const targetSel = document.getElementById('target_id');
    const planLoader = document.getElementById('plan-loader');

    planSel.innerHTML = '<option value="">— Loading... —</option>';
    planSel.disabled = true;
    targetSel.innerHTML = '<option value="">— Select working plan first —</option>';
    targetSel.disabled = true;
    clearAutoFill();

    if (!pid) {
      planSel.innerHTML = '<option value="">— Select project first —</option>';
      return;
    }

    planLoader.classList.add('show');
    fetch(`${base}/con-ajax-work-plans?project_id=${pid}`)
      .then(r => r.json())
      .then(data => {
        planLoader.classList.remove('show');
        let html = '<option value="">— Select Working Plan —</option>';
        if (data.length === 0) html = '<option value="">No approved plans found</option>';
        data.forEach(p => {
          html += `<option value="${p.id}">[${p.year}/${String(p.month).padStart(2,'0')}] ${p.work_description}</option>`;
        });
        planSel.innerHTML = html;
        planSel.disabled = false;
      })
      .catch(() => {
        planLoader.classList.remove('show');
        planSel.innerHTML = '<option value="">Error loading plans</option>';
      });
  });

  // ── Cascade: Work Plan → Targets ───────────────────────────────────────────
  document.getElementById('work_plan_id').addEventListener('change', function() {
    const wpid = this.value;
    const targetSel = document.getElementById('target_id');
    const targetLoader = document.getElementById('target-loader');

    targetSel.innerHTML = '<option value="">— Loading... —</option>';
    targetSel.disabled = true;
    clearAutoFill();

    if (!wpid) {
      targetSel.innerHTML = '<option value="">— Select working plan first —</option>';
      return;
    }

    targetLoader.classList.add('show');
    fetch(`${base}/con-ajax-report-targets?work_plan_id=${wpid}`)
      .then(r => r.json())
      .then(data => {
        targetLoader.classList.remove('show');
        let html = '<option value="">— No target (optional) —</option>';
        data.forEach(t => {
          html += `<option value="${t.id}" data-year="${t.year}" data-month="${t.month}" data-week="${t.week_number}">${t.target_title} — Week ${t.week_number} (${t.year}/${String(t.month).padStart(2,'0')})</option>`;
        });
        targetSel.innerHTML = html;
        targetSel.disabled = false;

        // Auto-select if only one target
        if (data.length === 1) targetSel.dispatchEvent(new Event('change'));
      })
      .catch(() => {
        targetLoader.classList.remove('show');
        targetSel.innerHTML = '<option value="">Error loading targets</option>';
      });
  });

  // ── Auto-fill Year/Month/Week when target selected ─────────────────────────
  document.getElementById('target_id').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (opt && opt.dataset.year) {
      document.getElementById('display_year').value = opt.dataset.year;
      document.getElementById('display_month').value = opt.dataset.month;
      const wn = opt.dataset.week;
      if (wn) {
        const wSel = document.getElementById('week_no');
        for (let i = 0; i < wSel.options.length; i++) {
          if (wSel.options[i].value == wn) {
            wSel.selectedIndex = i;
            break;
          }
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