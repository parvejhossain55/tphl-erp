<?php
$page_title = "Project Timeline Setup";
$page = 'Timeline Setup';
$show_breadcrumb = false;
$base = $GLOBALS['base_url'];

// Set default values if timeline exists
$timeline_id = $timeline['id'] ?? null;
$project_code = htmlspecialchars($timeline['project_code'] ?? '');
$project_type = htmlspecialchars($timeline['project_type'] ?? '');
$project_category = htmlspecialchars($timeline['project_category'] ?? '');
$handover_months = (int)($timeline['handover_months'] ?? 36);
$construction_months = (int)($timeline['construction_months'] ?? 24);
$post_construction_months = (int)($timeline['post_construction_months'] ?? 6);
$pre_construction_months = $handover_months - $construction_months - $post_construction_months;

$project_start_date = $timeline['project_start_date'] ?? '';
$construction_start_date = $timeline['construction_start_date'] ?? '';
$construction_end_date = $timeline['construction_end_date'] ?? '';
$expected_handover_date = $timeline['expected_handover_date'] ?? '';
$current_status = $timeline['current_status'] ?? 'Planning';
$description = htmlspecialchars($timeline['description'] ?? '');

$form_action = $timeline_id ? "/con-project-timeline-update/" . $timeline_id : "/con-project-timeline-store";
?>
<div class="row">
  <div class="col-12">
    <style>
      @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

      .tl-setup-container {
        font-family: 'Plus Jakarta Sans', sans-serif;
        color: #1e293b;
      }

      /* ── Breadcrumb & Header ─────────────────────────────── */
      .tl-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 12px;
      }

      .tl-breadcrumb {
        font-size: 12px;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 6px;
      }

      .tl-breadcrumb a {
        color: #3b82f6;
        text-decoration: none;
        font-weight: 500;
      }

      .tl-breadcrumb a:hover {
        text-decoration: underline;
      }

      .tl-title {
        font-size: 24px;
        font-weight: 800;
        color: #0f172a;
        margin: 4px 0 0;
        letter-spacing: -0.5px;
      }

      /* ── KPI Metric Cards ────────────────────────────────── */
      .tl-stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
      }

      .tl-stat-card {
        border-radius: 14px;
        padding: 18px 20px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        background: #ffffff;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.02);
        display: flex;
        align-items: center;
        gap: 16px;
        position: relative;
        overflow: hidden;
      }

      .tl-stat-card::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        width: 4px;
      }

      .tl-stat-card-handover::after { background: #3b82f6; }
      .tl-stat-card-construction::after { background: #f59e0b; }
      .tl-stat-card-start::after { background: #10b981; }
      .tl-stat-card-status::after { background: #8b5cf6; }

      .tl-stat-icon {
        width: 46px;
        height: 46px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
      }

      .tl-stat-card-handover .tl-stat-icon { background: rgba(59, 130, 246, 0.08); color: #3b82f6; }
      .tl-stat-card-construction .tl-stat-icon { background: rgba(245, 158, 11, 0.08); color: #f59e0b; }
      .tl-stat-card-start .tl-stat-icon { background: rgba(16, 185, 129, 0.08); color: #10b981; }
      .tl-stat-card-status .tl-stat-icon { background: rgba(139, 92, 246, 0.08); color: #8b5cf6; }

      .tl-stat-info {
        display: flex;
        flex-direction: column;
      }

      .tl-stat-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #64748b;
        margin-bottom: 2px;
      }

      .tl-stat-value {
        font-size: 18px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
      }

      /* ── Form and Visual Columns ─────────────────────────── */
      .tl-grid {
        display: grid;
        grid-template-columns: 5fr 7fr;
        gap: 24px;
        align-items: start;
      }

      .tl-panel {
        border-radius: 16px;
        background: #ffffff;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03);
        padding: 24px;
      }

      .tl-panel-title {
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        gap: 8px;
      }

      /* Form styling */
      .form-label {
        font-size: 12.5px;
        font-weight: 600;
        color: #334155;
        margin-bottom: 6px;
      }

      .form-control, .form-select {
        border-radius: 10px;
        border: 1px solid #cbd5e1;
        padding: 10px 14px;
        font-size: 13.5px;
        color: #1e293b;
        transition: all 0.15s ease;
      }

      .form-control:focus, .form-select:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        outline: none;
      }

      .form-control[readonly] {
        background-color: #f8fafc;
        border-color: #e2e8f0;
        font-weight: 600;
      }

      .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
        margin-bottom: 16px;
      }

      .form-group {
        margin-bottom: 16px;
      }

      .textarea-description {
        height: 80px;
        resize: none;
      }

      .btn-save-timeline {
        border-radius: 10px;
        font-weight: 700;
        font-size: 14px;
        padding: 12px 24px;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border: none;
      }

      /* ── Visual Timeline Bar ──────────────────────────────── */
      .tl-bar-container {
        margin-bottom: 30px;
      }

      .tl-bar-title {
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #64748b;
        margin-bottom: 14px;
      }

      .tl-progress-bar-wrapper {
        height: 38px;
        border-radius: 12px;
        background: #f1f5f9;
        display: flex;
        overflow: hidden;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.02);
        position: relative;
        margin-bottom: 12px;
      }

      .tl-bar-segment {
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-size: 11.5px;
        font-weight: 700;
        transition: width 0.3s ease;
        position: relative;
      }

      .tl-segment-pre {
        background: linear-gradient(90deg, #3b82f6, #60a5fa);
      }

      .tl-segment-construction {
        background: linear-gradient(90deg, #f59e0b, #fbbf24);
      }

      .tl-segment-post {
        background: linear-gradient(90deg, #10b981, #34d399);
      }

      .tl-bar-legend {
        display: flex;
        gap: 20px;
        margin-bottom: 30px;
        justify-content: center;
      }

      .tl-legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        font-weight: 600;
        color: #475569;
      }

      .tl-legend-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
      }

      .tl-legend-pre { background: #3b82f6; }
      .tl-legend-construction { background: #f59e0b; }
      .tl-legend-post { background: #10b981; }

      /* ── Milestones List ─────────────────────────────────── */
      .tl-milestones-wrapper {
        border-left: 2px dashed #cbd5e1;
        padding-left: 24px;
        margin-left: 10px;
      }

      .tl-milestone-item {
        position: relative;
        margin-bottom: 24px;
      }

      .tl-milestone-item:last-child {
        margin-bottom: 0;
      }

      .tl-milestone-dot {
        position: absolute;
        left: -33px;
        top: 2px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        border: 3px solid #ffffff;
        box-shadow: 0 0 0 2px var(--m-color);
        background: var(--m-color);
      }

      .tl-milestone-title {
        font-size: 13.5px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 2px;
      }

      .tl-milestone-date {
        font-size: 12px;
        font-weight: 600;
        color: var(--m-color);
        margin-bottom: 4px;
      }

      .tl-milestone-desc {
        font-size: 12px;
        color: #64748b;
      }

      .badge-workflow-type {
        font-size: 11px;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 20px;
        background: #f1f5f9;
        color: #475569;
      }

      .badge-workflow-active {
        background: rgba(16, 185, 129, 0.12);
        color: #10b981;
      }

      .validation-alert {
        background: rgba(239, 68, 68, 0.08);
        border: 1px solid rgba(239, 68, 68, 0.2);
        border-radius: 10px;
        padding: 12px;
        color: #ef4444;
        font-size: 12.5px;
        font-weight: 600;
        margin-bottom: 16px;
        display: none;
      }
    </style>

    <div class="tl-setup-container">
      
      <!-- ═══════════════════════ HEADER ════════════════════════════ -->
      <div class="tl-header">
        <div>
          <div class="tl-breadcrumb">
            <a href="<?= $base ?>/con-dashboard"><i class="fal fa-tachometer-alt me-1"></i>Dashboard</a>
            <span>›</span>
            <a href="<?= $base ?>/con-projects">Projects</a>
            <span>›</span>
            <a href="<?= $base ?>/con-project/<?= $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></a>
            <span>›</span>
            <span>Timeline Setup</span>
          </div>
          <h4 class="tl-title"><i class="fal fa-calendar-alt me-2" style="color:#3b82f6"></i>Project Timeline Setup</h4>
        </div>
        <div>
          <span class="badge-workflow-type <?= $project['workflow_version'] === 'phase_based' ? 'badge-workflow-active' : '' ?>">
            <i class="fal fa-sliders-h-square me-1"></i>
            Workflow: <?= $project['workflow_version'] === 'phase_based' ? 'Phase-Based Active' : 'Legacy' ?>
          </span>
        </div>
      </div>

      <!-- ═══════════════════════ KPI STAT CARDS ════════════════════════ -->
      <div class="tl-stats-grid">
        <div class="tl-stat-card tl-stat-card-handover">
          <div class="tl-stat-icon"><i class="fal fa-calendar-star"></i></div>
          <div class="tl-stat-info">
            <span class="tl-stat-label">Handover Timeline</span>
            <span class="tl-stat-value" id="kpi-handover"><?= $handover_months ?> Months</span>
          </div>
        </div>
        <div class="tl-stat-card tl-stat-card-construction">
          <div class="tl-stat-icon"><i class="fal fa-hard-hat"></i></div>
          <div class="tl-stat-info">
            <span class="tl-stat-label">Construction Period</span>
            <span class="tl-stat-value" id="kpi-construction"><?= $construction_months ?> Months</span>
          </div>
        </div>
        <div class="tl-stat-card tl-stat-card-start">
          <div class="tl-stat-icon"><i class="fal fa-calendar-alt"></i></div>
          <div class="tl-stat-info">
            <span class="tl-stat-label">Project Start</span>
            <span class="tl-stat-value" id="kpi-start-date">—</span>
          </div>
        </div>
        <div class="tl-stat-card tl-stat-card-status">
          <div class="tl-stat-icon"><i class="fal fa-shield-check"></i></div>
          <div class="tl-stat-info">
            <span class="tl-stat-label">Project Status</span>
            <span class="tl-stat-value" id="kpi-status"><?= $current_status ?></span>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════ MAIN CONTENT GRID ════════════════════ -->
      <div class="tl-grid">
        
        <!-- LEFT PANEL: FORM -->
        <div class="tl-panel">
          <div class="tl-panel-title">
            <i class="fal fa-edit" style="color:#3b82f6"></i>
            Timeline Details
          </div>

          <div class="validation-alert" id="valAlert"></div>

          <form action="<?= $base . $form_action ?>" method="POST" id="timelineForm">
            <input type="hidden" name="project_id" value="<?= $project['id'] ?>">

            <div class="form-group">
              <label class="form-label">Project Name</label>
              <input type="text" class="form-control" value="<?= htmlspecialchars($project['name']) ?>" readonly>
            </div>

            <div class="form-row">
              <div>
                <label class="form-label">Project Code</label>
                <input type="text" class="form-control" name="project_code" value="<?= $project_code ?>" placeholder="PHR-2025-001">
              </div>
              <div>
                <label class="form-label">Project Type</label>
                <select class="form-select" name="project_type">
                  <option value="Residential" <?= $project_type === 'Residential' ? 'selected' : '' ?>>Residential</option>
                  <option value="Commercial" <?= $project_type === 'Commercial' ? 'selected' : '' ?>>Commercial</option>
                  <option value="Mixed-Use" <?= $project_type === 'Mixed-Use' ? 'selected' : '' ?>>Mixed-Use</option>
                  <option value="Industrial" <?= $project_type === 'Industrial' ? 'selected' : '' ?>>Industrial</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Project Category</label>
              <input type="text" class="form-control" name="project_category" value="<?= $project_category ?>" placeholder="e.g. High Rise Building, Industrial Shed">
            </div>

            <div class="form-row">
              <div>
                <label class="form-label">Handover Timeline (Months)</label>
                <input type="number" class="form-control" name="handover_months" id="input_handover" value="<?= $handover_months ?>" min="1" required>
              </div>
              <div>
                <label class="form-label">Construction Period (Months)</label>
                <input type="number" class="form-control" name="construction_months" id="input_construction" value="<?= $construction_months ?>" min="1" required>
              </div>
            </div>

            <div class="form-row">
              <div>
                <label class="form-label">Post Construction (Months)</label>
                <input type="number" class="form-control" name="post_construction_months" id="input_post" value="<?= $post_construction_months ?>" min="0" required>
              </div>
              <div>
                <label class="form-label">Pre Construction (Months)</label>
                <input type="text" class="form-control" id="input_pre" value="<?= $pre_construction_months ?>" readonly>
              </div>
            </div>

            <div class="form-row">
              <div>
                <label class="form-label">Project Start Date</label>
                <input type="date" class="form-control" name="project_start_date" id="input_start_date" value="<?= $project_start_date ?>" required>
              </div>
              <div>
                <label class="form-label">Construction Start Date</label>
                <input type="date" class="form-control" name="construction_start_date" id="input_con_start_date" value="<?= $construction_start_date ?>" required>
              </div>
            </div>

            <div class="form-row">
              <div>
                <label class="form-label">Construction End Date</label>
                <input type="date" class="form-control" name="construction_end_date" id="input_con_end_date" value="<?= $construction_end_date ?>" readonly>
              </div>
              <div>
                <label class="form-label">Expected Handover Date</label>
                <input type="date" class="form-control" name="expected_handover_date" id="input_handover_date" value="<?= $expected_handover_date ?>" readonly>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Current Timeline Status</label>
              <select class="form-select" name="current_status" id="input_status">
                <option value="Planning" <?= $current_status === 'Planning' ? 'selected' : '' ?>>Planning</option>
                <option value="Pre-Construction" <?= $current_status === 'Pre-Construction' ? 'selected' : '' ?>>Pre-Construction</option>
                <option value="Construction" <?= $current_status === 'Construction' ? 'selected' : '' ?>>Construction</option>
                <option value="Post-Construction" <?= $current_status === 'Post-Construction' ? 'selected' : '' ?>>Post-Construction</option>
                <option value="Handover" <?= $current_status === 'Handover' ? 'selected' : '' ?>>Handover</option>
                <option value="Completed" <?= $current_status === 'Completed' ? 'selected' : '' ?>>Completed</option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Description / PM Notes</label>
              <textarea class="form-control textarea-description" name="description" placeholder="Timeline details or assumptions..."><?= $description ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary w-100 btn-save-timeline" id="submitBtn">
              <i class="fal fa-save"></i>
              <?= $timeline_id ? "Update Project Timeline" : "Initialize Project Timeline" ?>
            </button>
          </form>
        </div>

        <!-- RIGHT PANEL: VISUAL PREVIEW & MILESTONES -->
        <div class="tl-panel">
          <div class="tl-panel-title">
            <i class="fal fa-eye" style="color:#10b981"></i>
            Timeline Visualization
          </div>

          <!-- Color Timeline Bar -->
          <div class="tl-bar-container">
            <div class="tl-bar-title">Visual Roadmap Breakdown</div>
            <div class="tl-progress-bar-wrapper">
              <div class="tl-bar-segment tl-segment-pre" id="bar_pre" style="width: 0%;">
                <span id="bar_pre_lbl">Pre-Con</span>
              </div>
              <div class="tl-bar-segment tl-segment-construction" id="bar_con" style="width: 0%;">
                <span id="bar_con_lbl">Construction</span>
              </div>
              <div class="tl-bar-segment tl-segment-post" id="bar_post" style="width: 0%;">
                <span id="bar_post_lbl">Post-Con</span>
              </div>
            </div>
            
            <div class="tl-bar-legend">
              <div class="tl-legend-item">
                <div class="tl-legend-dot tl-legend-pre"></div>
                Pre-Construction
              </div>
              <div class="tl-legend-item">
                <div class="tl-legend-dot tl-legend-construction"></div>
                Construction
              </div>
              <div class="tl-legend-item">
                <div class="tl-legend-dot tl-legend-post"></div>
                Post-Construction
              </div>
            </div>
          </div>

          <!-- Key Milestones -->
          <div>
            <div class="tl-bar-title" style="margin-bottom: 20px;">Key Project Milestones</div>
            <div class="tl-milestones-wrapper">
              
              <!-- Milestone 1 -->
              <div class="tl-milestone-item" style="--m-color: #3b82f6;">
                <div class="tl-milestone-dot"></div>
                <div class="tl-milestone-title">Project Kickoff</div>
                <div class="tl-milestone-date" id="milestone-kickoff-date">—</div>
                <div class="tl-milestone-desc">Start of Pre-construction phase, design finalizing and permissions procurement.</div>
              </div>

              <!-- Milestone 2 -->
              <div class="tl-milestone-item" style="--m-color: #f59e0b;">
                <div class="tl-milestone-dot"></div>
                <div class="tl-milestone-title">On-Site Mobilization & Construction Start</div>
                <div class="tl-milestone-date" id="milestone-con-start-date">—</div>
                <div class="tl-milestone-desc">Contractors mobilized to site. Excavation and piling work begins.</div>
              </div>

              <!-- Milestone 3 -->
              <div class="tl-milestone-item" style="--m-color: #10b981;">
                <div class="tl-milestone-dot"></div>
                <div class="tl-milestone-title">Structural & MEP Works Completion</div>
                <div class="tl-milestone-date" id="milestone-con-end-date">—</div>
                <div class="tl-milestone-desc">All structural columns, slab casting, brickwork, and MEP routing completed.</div>
              </div>

              <!-- Milestone 4 -->
              <div class="tl-milestone-item" style="--m-color: #8b5cf6;">
                <div class="tl-milestone-dot"></div>
                <div class="tl-milestone-title">Post-Construction & Client Handover</div>
                <div class="tl-milestone-date" id="milestone-handover-date">—</div>
                <div class="tl-milestone-desc">Finishing details, testing & commissioning, and handing keys to buyers.</div>
              </div>

            </div>
          </div>

        </div>

      </div>

    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
  const inputHandover = document.getElementById("input_handover");
  const inputConstruction = document.getElementById("input_construction");
  const inputPost = document.getElementById("input_post");
  const inputPre = document.getElementById("input_pre");
  
  const inputStartDate = document.getElementById("input_start_date");
  const inputConStartDate = document.getElementById("input_con_start_date");
  const inputConEndDate = document.getElementById("input_con_end_date");
  const inputHandoverDate = document.getElementById("input_handover_date");

  const valAlert = document.getElementById("valAlert");
  const submitBtn = document.getElementById("submitBtn");

  // KPI elements
  const kpiHandover = document.getElementById("kpi-handover");
  const kpiConstruction = document.getElementById("kpi-construction");
  const kpiStartDate = document.getElementById("kpi-start-date");
  const kpiStatus = document.getElementById("kpi-status");
  const inputStatus = document.getElementById("input_status");

  // Visual bar segments
  const barPre = document.getElementById("bar_pre");
  const barCon = document.getElementById("bar_con");
  const barPost = document.getElementById("bar_post");
  const barPreLbl = document.getElementById("bar_pre_lbl");
  const barConLbl = document.getElementById("bar_con_lbl");
  const barPostLbl = document.getElementById("bar_post_lbl");

  // Milestone Dates
  const milKickoff = document.getElementById("milestone-kickoff-date");
  const milConStart = document.getElementById("milestone-con-start-date");
  const milConEnd = document.getElementById("milestone-con-end-date");
  const milHandover = document.getElementById("milestone-handover-date");

  function addMonths(dateStr, months) {
    if (!dateStr || isNaN(months)) return '';
    let d = new Date(dateStr);
    if (isNaN(d.getTime())) return '';
    d.setMonth(d.getMonth() + parseInt(months));
    // Correct for month end overflow
    let expectedMonth = (new Date(dateStr).getMonth() + parseInt(months)) % 12;
    if (d.getMonth() !== expectedMonth) {
        d.setDate(0);
    }
    let y = d.getFullYear();
    let m = String(d.getMonth() + 1).padStart(2, '0');
    let day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
  }

  function formatDateDisplay(dateStr) {
    if (!dateStr) return '—';
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const parts = dateStr.split('-');
    if (parts.length === 3) {
      const y = parts[0];
      const m = parseInt(parts[1]) - 1;
      const d = parts[2];
      return `${d} ${months[m]} ${y}`;
    }
    return dateStr;
  }

  function calculateTimeline() {
    const handover = parseInt(inputHandover.value) || 0;
    const construction = parseInt(inputConstruction.value) || 0;
    const post = parseInt(inputPost.value) || 0;
    const pre = handover - construction - post;

    inputPre.value = pre;

    // Validation checks
    let hasError = false;
    let errorMsg = "";

    if (handover <= 0 || construction <= 0 || post < 0) {
      hasError = true;
      errorMsg = "Months values must be positive numbers.";
    } else if (pre < 0) {
      hasError = true;
      errorMsg = "Invalid Month Division! Handover Months (" + handover + ") cannot be less than Construction (" + construction + ") + Post Construction (" + post + ") Months.";
    }

    // Dates updates
    const startDateVal = inputStartDate.value;
    const conStartDateVal = inputConStartDate.value;

    if (startDateVal) {
      const handoverDate = addMonths(startDateVal, handover);
      inputHandoverDate.value = handoverDate;
      milKickoff.textContent = formatDateDisplay(startDateVal);
      milHandover.textContent = formatDateDisplay(handoverDate);
      kpiStartDate.textContent = formatDateDisplay(startDateVal);
    }

    if (conStartDateVal) {
      const conEndDate = addMonths(conStartDateVal, construction);
      inputConEndDate.value = conEndDate;
      milConStart.textContent = formatDateDisplay(conStartDateVal);
      milConEnd.textContent = formatDateDisplay(conEndDate);
    }

    if (startDateVal && conStartDateVal && conStartDateVal < startDateVal) {
      hasError = true;
      errorMsg = "Construction Start Date cannot be earlier than Project Kickoff Start Date.";
    }

    if (hasError) {
      valAlert.textContent = errorMsg;
      valAlert.style.display = "block";
      submitBtn.disabled = true;
    } else {
      valAlert.style.display = "none";
      submitBtn.disabled = false;
    }

    // Update KPI metrics
    kpiHandover.textContent = handover + " Months";
    kpiConstruction.textContent = construction + " Months";
    kpiStatus.textContent = inputStatus.value;

    // Update Visual Timeline Bar
    if (handover > 0 && pre >= 0) {
      const prePct = (pre / handover) * 100;
      const conPct = (construction / handover) * 100;
      const postPct = (post / handover) * 100;

      barPre.style.width = prePct + "%";
      barCon.style.width = conPct + "%";
      barPost.style.width = postPct + "%";

      barPreLbl.textContent = pre > 0 ? "Pre-Con (" + pre + "M)" : "";
      barConLbl.textContent = construction > 0 ? "Construction (" + construction + "M)" : "";
      barPostLbl.textContent = post > 0 ? "Post-Con (" + post + "M)" : "";

      barPre.style.display = pre > 0 ? "flex" : "none";
      barCon.style.display = construction > 0 ? "flex" : "none";
      barPost.style.display = post > 0 ? "flex" : "none";
    } else {
      barPre.style.width = "0%";
      barCon.style.width = "0%";
      barPost.style.width = "0%";
    }
  }

  // Event Listeners
  inputHandover.addEventListener("input", calculateTimeline);
  inputConstruction.addEventListener("input", calculateTimeline);
  inputPost.addEventListener("input", calculateTimeline);
  inputStartDate.addEventListener("input", calculateTimeline);
  inputConStartDate.addEventListener("input", calculateTimeline);
  inputStatus.addEventListener("change", function() {
    kpiStatus.textContent = this.value;
  });

  // Initial call to render
  calculateTimeline();
});
</script>
