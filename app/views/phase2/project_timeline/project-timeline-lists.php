<?php
$page_title = "Project Timeline";
$page = 'Timeline Setup';
$show_breadcrumb = false;
$base = $GLOBALS['base_url'];

$timeline_id = $timeline['id'];
$project_code = htmlspecialchars($timeline['project_code'] ?? 'N/A');
$project_type = htmlspecialchars($timeline['project_type'] ?? 'N/A');
$project_category = htmlspecialchars($timeline['project_category'] ?? 'N/A');
$handover_months = (int)($timeline['handover_months'] ?? 0);
$construction_months = (int)($timeline['construction_months'] ?? 0);
$post_construction_months = (int)($timeline['post_construction_months'] ?? 0);
$pre_construction_months = $handover_months - $construction_months - $post_construction_months;

$project_start_date = $timeline['project_start_date'] ?? '';
$construction_start_date = $timeline['construction_start_date'] ?? '';
$construction_end_date = $timeline['construction_end_date'] ?? '';
$expected_handover_date = $timeline['expected_handover_date'] ?? '';
$current_status = $timeline['current_status'] ?? 'Planning';
$description = htmlspecialchars($timeline['description'] ?? 'No notes provided.');

// Calculate percentage widths for timeline visualization
$pre_pct = $handover_months > 0 ? ($pre_construction_months / $handover_months) * 100 : 0;
$con_pct = $handover_months > 0 ? ($construction_months / $handover_months) * 100 : 0;
$post_pct = $handover_months > 0 ? ($post_construction_months / $handover_months) * 100 : 0;

function formatTimelineDate($dateStr) {
    if (empty($dateStr)) return '—';
    return date('d M Y', strtotime($dateStr));
}
?>
<div class="row">
  <div class="col-12">
    <style>
      @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

      .tl-setup-container {
        font-family: 'Plus Jakarta Sans', sans-serif;
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
        color: var(--clr-text-muted, #64748b);
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
        border: 1px solid var(--clr-border-1, rgba(226, 232, 240, 0.8));
        background: var(--clr-bg-card, #ffffff);
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
      .tl-stat-card-status::after { background: var(--clr-theme-secondary); }

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
      .tl-stat-card-status .tl-stat-icon { background: rgba(var(--clr-theme-secondary-rgb), 0.08); color: var(--clr-theme-secondary); }

      .tl-stat-info {
        display: flex;
        flex-direction: column;
      }

      .tl-stat-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: var(--clr-text-muted, #64748b);
        margin-bottom: 2px;
      }

      .tl-stat-value {
        font-size: 18px;
        font-weight: 800;
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
        background: var(--clr-bg-card, #ffffff);
        border: 1px solid var(--clr-border-1, rgba(226, 232, 240, 0.8));
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03);
        padding: 24px;
      }

      .tl-panel-title {
        font-size: 15px;
        font-weight: 700;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--clr-border-1, #f1f5f9);
        display: flex;
        align-items: center;
        gap: 8px;
      }

      /* Readonly info rows */
      .info-row {
        display: flex;
        justify-content: space-between;
        padding: 11px 0;
        border-bottom: 1px solid var(--clr-border-1, #f1f5f9);
        font-size: 13.5px;
      }
      .info-row:last-child {
        border-bottom: none;
      }
      .info-label {
        color: var(--clr-text-muted, #64748b);
        font-weight: 500;
      }
      .info-value {
        font-weight: 600;
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
        color: var(--clr-text-muted, #64748b);
        margin-bottom: 14px;
      }

      .tl-progress-bar-wrapper {
        height: 38px;
        border-radius: 12px;
        background: var(--clr-border-1, #f1f5f9);
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
        min-width: 0;
      }

      .tl-bar-segment span {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 100%;
        padding: 0 4px;
        text-align: center;
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
        border-left: 2px dashed var(--clr-border-1, #cbd5e1);
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
        border: 3px solid var(--clr-bg-card, #ffffff);
        box-shadow: 0 0 0 2px var(--m-color);
        background: var(--m-color);
      }

      .tl-milestone-title {
        font-size: 13.5px;
        font-weight: 700;
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
        color: var(--clr-text-muted, #64748b);
      }

      .badge-workflow-type {
        font-size: 11px;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 20px;
        background: rgba(16, 185, 129, 0.12);
        color: #10b981;
      }

      .btn-edit-timeline {
        border-radius: 10px;
        font-weight: 700;
        font-size: 14px;
        padding: 12px 24px;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        text-decoration: none !important;
      }

      /* ── Responsive Styling (Mobile & Tablet) ────────────── */
      @media (max-width: 991.98px) {
        .tl-grid {
          grid-template-columns: 1fr;
          gap: 20px;
        }

        .tl-stats-grid {
          grid-template-columns: repeat(2, 1fr);
          gap: 14px;
        }

        .tl-title {
          font-size: 20px;
        }
      }

      @media (max-width: 575.98px) {
        .tl-stats-grid {
          grid-template-columns: 1fr;
          gap: 10px;
        }

        .tl-header {
          flex-direction: column;
          align-items: flex-start;
          gap: 8px;
        }

        .badge-workflow-type {
          align-self: flex-start;
        }

        .tl-panel {
          padding: 16px;
        }

        .tl-stat-card {
          padding: 14px 16px;
        }
        
        .tl-bar-legend {
          flex-direction: column;
          align-items: flex-start;
          gap: 8px;
          margin-bottom: 20px;
        }
        
        .tl-milestones-wrapper {
          padding-left: 18px;
          margin-left: 6px;
        }
        
        .tl-milestone-dot {
          left: -27px;
        }
      }
    </style>

    <div class="tl-setup-container">
      
      <!-- ═══════════════════════ HEADER ════════════════════════════ -->
      <div class="tl-header">
        <div>
          <div class="tl-breadcrumb">
           
            <a href="<?= $base ?>/con-project/<?= $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></a>
            <span>›</span>
            <span>Timeline</span>
          </div>
          <h4 class="tl-title"><i class="fal fa-calendar-alt me-2" style="color:#3b82f6"></i>Project Timeline</h4>
        </div>
        <div>
          <span class="badge-workflow-type">
            <i class="fal fa-sliders-h-square me-1"></i>
            Workflow: Phase-Based Active
          </span>
        </div>
      </div>

      <!-- ═══════════════════════ KPI STAT CARDS ════════════════════════ -->
      <div class="tl-stats-grid">
        <div class="tl-stat-card tl-stat-card-handover">
          <div class="tl-stat-icon"><i class="fal fa-calendar-star"></i></div>
          <div class="tl-stat-info">
            <span class="tl-stat-label">Handover Timeline</span>
            <span class="tl-stat-value"><?= $handover_months ?> Months</span>
          </div>
        </div>
        <div class="tl-stat-card tl-stat-card-construction">
          <div class="tl-stat-icon"><i class="fal fa-hard-hat"></i></div>
          <div class="tl-stat-info">
            <span class="tl-stat-label">Construction Period</span>
            <span class="tl-stat-value"><?= $construction_months ?> Months</span>
          </div>
        </div>
        <div class="tl-stat-card tl-stat-card-start">
          <div class="tl-stat-icon"><i class="fal fa-calendar-alt"></i></div>
          <div class="tl-stat-info">
            <span class="tl-stat-label">Project Start</span>
            <span class="tl-stat-value"><?= formatTimelineDate($project_start_date) ?></span>
          </div>
        </div>
        <div class="tl-stat-card tl-stat-card-status">
          <div class="tl-stat-icon"><i class="fal fa-shield-check"></i></div>
          <div class="tl-stat-info">
            <span class="tl-stat-label">Project Status</span>
            <span class="tl-stat-value"><?= $current_status ?></span>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════ MAIN CONTENT GRID ════════════════════ -->
      <div class="tl-grid">
        
        <!-- LEFT PANEL: DETAILS -->
        <div class="tl-panel">
          <div class="tl-panel-title">
            <i class="fal fa-info-circle" style="color:#3b82f6"></i>
            Timeline Specifications
          </div>

          <div class="info-row">
            <span class="info-label">Project Name</span>
            <span class="info-value"><?= htmlspecialchars($project['name']) ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Project Code</span>
            <span class="info-value"><?= $project_code ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Project Type</span>
            <span class="info-value"><?= $project_type ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Project Category</span>
            <span class="info-value"><?= $project_category ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Pre Construction Period</span>
            <span class="info-value"><?= $pre_construction_months ?> Months</span>
          </div>
          <div class="info-row">
            <span class="info-label">Construction Period</span>
            <span class="info-value"><?= $construction_months ?> Months</span>
          </div>
          <div class="info-row">
            <span class="info-label">Grace Period</span>
            <span class="info-value"><?= $post_construction_months ?> Months</span>
          </div>
          <div class="info-row">
            <span class="info-label">Timeline Status</span>
            <span class="info-value text-primary"><?= $current_status ?></span>
          </div>
          
          <div class="form-group mt-3">
            <label class="form-label" style="font-weight: 700;">PM Timeline Notes</label>
            <div style="background: var(--clr-bg-body, #f8fafc); border: 1px solid var(--clr-border-1, #e2e8f0); padding: 12px 14px; border-radius: 10px; font-size: 13px; line-height: 1.5; color: inherit; max-height: 100px; overflow-y: auto;">
              <?= nl2br($description) ?>
            </div>
          </div>

          <!-- Edit Action Button -->
          <div class="mt-4 d-flex flex-column gap-2">
            <a href="<?= $base ?>/con-project-timeline-edit/<?= $timeline_id ?>" class="btn btn-primary w-100 btn-edit-timeline">
              <i class="fal fa-edit"></i>
              Edit Timeline Configuration
            </a>
            <a href="<?= $base ?>/con-phase-plan/<?= $project['id'] ?>" class="btn w-100 btn-edit-timeline"
               style="background:rgba(var(--clr-theme-primary-rgb),.1);color:var(--clr-theme-primary);border:1px solid rgba(var(--clr-theme-primary-rgb),.25);">
              <i class="fal fa-sitemap"></i>
              Go to Phase Plan
            </a>
          </div>
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
              <?php if ($pre_construction_months > 0): ?>
              <div class="tl-bar-segment tl-segment-pre" style="width: <?= $pre_pct ?>%;" title="Pre-Con (<?= $pre_construction_months ?>M)">
                <span>Pre-Con (<?= $pre_construction_months ?>M)</span>
              </div>
              <?php endif; ?>
              
              <?php if ($construction_months > 0): ?>
              <div class="tl-bar-segment tl-segment-construction" style="width: <?= $con_pct ?>%;" title="Construction (<?= $construction_months ?>M)">
                <span>Construction (<?= $construction_months ?>M)</span>
              </div>
              <?php endif; ?>
              
              <?php if ($post_construction_months > 0): ?>
              <div class="tl-bar-segment tl-segment-post" style="width: <?= $post_pct ?>%;" title="Grace Period (<?= $post_construction_months ?>M)">
                <span>Grace Period (<?= $post_construction_months ?>M)</span>
              </div>
              <?php endif; ?>
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
                Grace Period
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
                <div class="tl-milestone-date"><?= formatTimelineDate($project_start_date) ?></div>
                <div class="tl-milestone-desc">Start of Pre-construction phase, design finalizing and permissions procurement.</div>
              </div>

              <!-- Milestone 2 -->
              <div class="tl-milestone-item" style="--m-color: #f59e0b;">
                <div class="tl-milestone-dot"></div>
                <div class="tl-milestone-title">On-Site Mobilization & Construction Start</div>
                <div class="tl-milestone-date"><?= formatTimelineDate($construction_start_date) ?></div>
                <div class="tl-milestone-desc">Contractors mobilized to site. Excavation and piling work begins.</div>
              </div>

              <!-- Milestone 3 -->
              <div class="tl-milestone-item" style="--m-color: #10b981;">
                <div class="tl-milestone-dot"></div>
                <div class="tl-milestone-title">Structural & MEP Works Completion</div>
                <div class="tl-milestone-date"><?= formatTimelineDate($construction_end_date) ?></div>
                <div class="tl-milestone-desc">All structural columns, slab casting, brickwork, and MEP routing completed.</div>
              </div>

              <!-- Milestone 4 -->
              <div class="tl-milestone-item" style="--m-color: var(--clr-theme-secondary);">
                <div class="tl-milestone-dot"></div>
                <div class="tl-milestone-title">Grace Period & Client Handover</div>
                <div class="tl-milestone-date"><?= formatTimelineDate($expected_handover_date) ?></div>
                <div class="tl-milestone-desc">Finishing details, testing & commissioning, and handing keys to buyers.</div>
              </div>

            </div>
          </div>

        </div>

      </div>

    </div>
  </div>
</div>
