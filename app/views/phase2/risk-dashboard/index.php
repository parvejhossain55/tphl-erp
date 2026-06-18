<?php
$base_url     = $GLOBALS['base_url'];
$projectId    = $project['id'];
?>

<style>
/* ── Risk Dashboard Styles ───────────────────────────── */
.risk-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #1a0a2e 100%);
    border-radius: 16px;
    padding: 28px 32px;
    color: #fff;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}
.risk-hero::before {
    content: '';
    position: absolute; top: -40px; right: -40px;
    width: 200px; height: 200px;
    background: radial-gradient(circle, rgba(239,68,68,0.25) 0%, transparent 70%);
    border-radius: 50%;
}
.risk-hero h1 { font-size: 1.6rem; font-weight: 700; margin: 0 0 4px; }
.risk-hero .subtitle { color: rgba(255,255,255,0.65); font-size: 0.9rem; }

/* KPI Cards */
.kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); gap: 16px; margin-bottom: 24px; }
.kpi-card {
    background: var(--clr-bg-card, #fff);
    border-radius: 14px;
    padding: 20px 22px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    border: 1px solid var(--clr-border-1, #e2e8f0);
    border-left: 4px solid var(--clr-border-1, #e2e8f0);
    transition: transform 0.18s, box-shadow 0.18s;
}
.kpi-card:hover { transform: translateY(-2px); box-shadow: 0 6px 24px rgba(0,0,0,0.1); }
.kpi-card.red   { border-left-color: #ef4444; }
.kpi-card.amber { border-left-color: #f59e0b; }
.kpi-card.green { border-left-color: #10b981; }
.kpi-card.blue  { border-left-color: #3b82f6; }
.kpi-card.purple{ border-left-color: var(--clr-theme-secondary); }
.kpi-number { font-size: 2rem; font-weight: 800; line-height: 1; }
.kpi-label  { font-size: 0.78rem; color: var(--clr-text-muted, #64748b); margin-top: 4px; text-transform: uppercase; letter-spacing: .04em; }

/* Severity badges */
.badge-severity {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.73rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
}
.sev-low      { background: rgba(16, 185, 129, 0.15); color: #10b981; }
.sev-medium   { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
.sev-high     { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
.sev-critical { background: rgba(239, 68, 68, 0.25); color: #ef4444; font-weight: 800; }

/* Status badges */
.badge-status {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.73rem;
    font-weight: 600;
}
.st-open        { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
.st-in-progress { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
.st-resolved    { background: rgba(16, 185, 129, 0.15); color: #10b981; }
.st-closed      { background: rgba(59, 130, 246, 0.15); color: var(--clr-theme-primary, #3b82f6); }

/* Table */
.risk-table-wrap { background: var(--clr-bg-card, #fff); border-radius: 14px; padding: 0; box-shadow: 0 2px 12px rgba(0,0,0,0.06); border: 1px solid var(--clr-border-1, #e2e8f0); overflow: hidden; margin-bottom: 24px; }
.risk-table-head { display: flex; justify-content: space-between; align-items: center; padding: 18px 22px; border-bottom: 1px solid var(--clr-border-1, #f1f5f9); }
.risk-table-head h5 { margin: 0; font-weight: 700; font-size: 1rem; color: var(--clr-text-body, inherit); }
.risk-tbl { width: 100%; border-collapse: collapse; font-size: 0.84rem; }
.risk-tbl th { background: var(--clr-bg-body, #f8fafc); color: var(--clr-text-muted, #475569); font-weight: 600; padding: 10px 14px; text-align: left; border-bottom: 1px solid var(--clr-border-1, #e2e8f0); }
.risk-tbl td { padding: 11px 14px; border-bottom: 1px solid var(--clr-border-1, #f1f5f9); vertical-align: middle; color: var(--clr-text-body, inherit); }
.risk-tbl tr:last-child td { border-bottom: none; }
.risk-tbl tr:hover td { background: var(--clr-bg-card-hover, rgba(0, 0, 0, 0.02)); }

/* Description Truncation */
.risk-desc-col { max-width: 260px; white-space: normal; }
.risk-desc-text { cursor: pointer; transition: color 0.15s; }
.risk-desc-text:hover { color: var(--clr-theme-primary, #3b82f6); }

/* Auto badge */
.badge-auto { background: rgba(var(--clr-theme-primary-rgb),.08); color: #6d28d9; font-size: 0.68rem; padding: 2px 7px; border-radius: 10px; font-weight: 600; }

/* Missed target rows */
.missed-row td { font-size: 0.83rem; }
.qty-miss { color: #ef4444; font-weight: 700; }
.qty-plan { color: var(--clr-theme-primary, #3b82f6); }
.qty-ach  { color: #10b981; }

/* Modal */
.modal-content { border-radius: 16px; border: none; background: var(--clr-bg-card, #fff); color: var(--clr-text-body, inherit); }
.modal-header  { background: linear-gradient(135deg, #1e293b, #0f172a); color: #fff; border-radius: 16px 16px 0 0; }
.modal-header .btn-close { filter: invert(1); }
.modal-footer  { border-top: 1px solid var(--clr-border-1, #f1f5f9); }
.form-label    { font-weight: 600; font-size: 0.84rem; color: var(--clr-text-body, #374151); margin-bottom: 4px; }
.form-control, .form-select { border-radius: 8px; border: 1.5px solid var(--clr-border-1, #e2e8f0); font-size: 0.88rem; background: var(--clr-bg-card, #fff); color: var(--clr-text-body, inherit); }
.form-control:focus, .form-select:focus { border-color: var(--clr-theme-primary); box-shadow: 0 0 0 3px rgba(var(--clr-theme-primary-rgb),0.1); background: var(--clr-bg-card, #fff); color: var(--clr-text-body, inherit); }

/* Tabs */
.risk-tabs .nav-link { color: var(--clr-text-muted, #64748b); font-weight: 600; font-size: 0.85rem; border: none; padding: 10px 20px; border-radius: 8px; transition: all 0.2s; }
.risk-tabs .nav-link.active { background: var(--clr-theme-primary, #1e293b); color: #fff; }
.risk-tabs { gap: 6px; }

/* Section title */
.section-hdr { font-weight: 700; font-size: 1rem; color: var(--clr-text-body, #1e293b); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.section-hdr .dot { width: 8px; height: 8px; border-radius: 50%; background: #ef4444; }

/* Recovery link */
.btn-recovery { background: linear-gradient(135deg, var(--clr-theme-secondary), var(--clr-theme-primary)); color: #fff; border: none; border-radius: 10px; padding: 8px 18px; font-weight: 600; font-size: 0.85rem; transition: opacity 0.2s; }
.btn-recovery:hover { opacity: 0.88; color: #fff; }

/* Mobile Responsiveness Styles */
@media (max-width: 767.98px) {
    .kpi-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 10px !important;
    }
    .kpi-card {
        padding: 12px 14px !important;
    }
    .kpi-number {
        font-size: 1.5rem !important;
    }
    .kpi-label {
        font-size: 0.7rem !important;
    }
    .risk-tbl th, .risk-tbl td {
        padding: 8px 10px !important;
        font-size: 0.78rem !important;
    }
    .risk-desc-col {
        max-width: 150px !important;
    }
    .risk-desc-text.truncated {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
    }
}
</style>

<div class="container-fluid py-4">

    <!-- Hero Banner -->
    <div class="risk-hero mb-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="subtitle mb-1">
                    <a href="<?= $base_url ?>/con-monthly-review/<?= $projectId ?>" class="text-white-50 text-decoration-none">
                        ← Monthly Review
                    </a>
                </div>
                <h1>⚠️ Risk Dashboard</h1>
                <div class="subtitle"><?= htmlspecialchars($project['name']) ?> — Project Risk Register & Analysis</div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-danger fw-bold" data-bs-toggle="modal" data-bs-target="#addRiskModal">
                    <i class="bi bi-plus-circle me-1"></i> Log Risk
                </button>
            </div>
        </div>
    </div>

    <!-- KPI Metrics -->
    <div class="kpi-grid">
        <div class="kpi-card red">
            <div class="kpi-number text-danger"><?= $totalRisks ?></div>
            <div class="kpi-label">Total Risks</div>
        </div>
        <div class="kpi-card amber">
            <div class="kpi-number text-warning"><?= $openRisks ?></div>
            <div class="kpi-label">Open Risks</div>
        </div>
        <div class="kpi-card red">
            <div class="kpi-number text-danger"><?= $criticalRisks ?></div>
            <div class="kpi-label">High / Critical</div>
        </div>
        <div class="kpi-card blue">
            <div class="kpi-number text-primary"><?= $missedWeekCount ?></div>
            <div class="kpi-label">Missed Weekly Targets</div>
        </div>
        <div class="kpi-card green">
            <div class="kpi-number text-success"><?= $resolvedRisks ?></div>
            <div class="kpi-label">Resolved / Closed</div>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Tabs -->
    <ul class="nav risk-tabs mb-4" id="riskTabs">
        <li class="nav-item">
            <a class="nav-link active" href="#" onclick="showTab('tab-register', this)">
                <i class="bi bi-shield-exclamation me-1"></i> Risk Register
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#" onclick="showTab('tab-missed', this)">
                <i class="bi bi-exclamation-triangle me-1"></i> Missed Targets
                <?php if ($missedWeekCount > 0): ?>
                    <span class="badge bg-danger ms-1"><?= $missedWeekCount ?></span>
                <?php endif; ?>
            </a>
        </li>
    </ul>

    <!-- TAB: Risk Register -->
    <div id="tab-register">
        <div class="risk-table-wrap">
            <div class="risk-table-head">
                <h5><span class="dot" style="width:8px;height:8px;border-radius:50%;background:#ef4444;display:inline-block;margin-right:8px;"></span>Identified Risk Register</h5>
                <span class="text-muted" style="font-size:0.82rem;"><?= count($risks) ?> risks logged</span>
            </div>
            <?php if (empty($risks)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-shield-check" style="font-size:2.5rem; color:#10b981;"></i>
                    <p class="mt-3 mb-1 fw-bold">No risks identified</p>
                    <p class="text-muted small">Risks will auto-appear when weekly targets are missed, or you can log risks manually.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="risk-tbl">
                        <thead>
                            <tr>
                                <th>Risk Code</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Phase</th>
                                <th>Impact</th>
                                <th>Severity</th>
                                <th>Owner</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($risks as $r): ?>
                            <tr>
                                <td>
                                    <code style="font-size:0.75rem;"><?= htmlspecialchars($r['risk_code'] ?? '—') ?></code>
                                    <?php if ($r['is_auto']): ?>
                                        <span class="badge-auto ms-1">AUTO</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($r['category']) ?></td>
                                <td class="risk-desc-col">
                                    <div class="risk-desc-text truncated" onclick="toggleRiskDesc(this)" title="Click to expand/collapse">
                                        <?= htmlspecialchars($r['description']) ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($r['phase_name'] ?? '—') ?></td>
                                <td>
                                    <?php
                                    $imp = $r['impact'];
                                    $cls = $imp === 'Critical' ? 'sev-critical' : ($imp === 'High' ? 'sev-high' : ($imp === 'Medium' ? 'sev-medium' : 'sev-low'));
                                    ?>
                                    <span class="badge-severity <?= $cls ?>"><?= $imp ?></span>
                                </td>
                                <td>
                                    <?php
                                    $sev = $r['severity'];
                                    $scls = $sev === 'Critical' ? 'sev-critical' : ($sev === 'High' ? 'sev-high' : ($sev === 'Medium' ? 'sev-medium' : 'sev-low'));
                                    ?>
                                    <span class="badge-severity <?= $scls ?>"><?= $sev ?></span>
                                </td>
                                <td><?= htmlspecialchars($r['owner'] ?? '—') ?></td>
                                <td><?= $r['due_date'] ? date('d M Y', strtotime($r['due_date'])) : '—' ?></td>
                                <td>
                                    <?php
                                    $st = $r['status'];
                                    $stcls = $st === 'Open' ? 'st-open' : ($st === 'In Progress' ? 'st-in-progress' : ($st === 'Resolved' ? 'st-resolved' : 'st-closed'));
                                    ?>
                                    <span class="badge-status <?= $stcls ?>"><?= $st ?></span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <!-- Update Status -->
                                        <form method="POST" action="<?= $base_url ?>/con-risk-dashboard-update-status" class="d-flex gap-1 align-items-center">
                                            <input type="hidden" name="risk_id" value="<?= $r['id'] ?>">
                                            <input type="hidden" name="project_id" value="<?= $projectId ?>">
                                            <select name="status" class="form-select form-select-sm" style="min-width:110px; font-size:0.78rem;">
                                                <?php foreach (['Open','In Progress','Resolved','Closed'] as $opt): ?>
                                                    <option value="<?= $opt ?>" <?= $r['status'] === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-outline-primary" style="font-size:0.75rem; padding:3px 8px;">Save</button>
                                        </form>
                                        <!-- Delete -->
                                        <a href="<?= $base_url ?>/con-risk-dashboard-delete/<?= $r['id'] ?>?project_id=<?= $projectId ?>"
                                           class="btn btn-sm btn-outline-danger"
                                           style="font-size:0.75rem; padding:3px 8px;"
                                           onclick="return confirm('Delete this risk?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
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

    <!-- TAB: Missed Targets -->
    <div id="tab-missed" style="display:none;">
        <div class="risk-table-wrap">
            <div class="risk-table-head">
                <h5><i class="bi bi-exclamation-triangle text-warning me-2"></i>Missed Weekly Targets — Auto-Detected</h5>
                <span class="text-muted" style="font-size:0.82rem;"><?= $missedWeekCount ?> missed target(s) across all weeks</span>
            </div>
            <?php if (empty($missedTargets)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-check-circle-fill text-success" style="font-size:2.5rem;"></i>
                    <p class="mt-3 mb-1 fw-bold">All weekly targets are on track!</p>
                    <p class="text-muted small">No missed targets detected. Keep it up!</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="risk-tbl">
                        <thead>
                            <tr>
                                <th>Phase</th>
                                <th>Month</th>
                                <th>Week</th>
                                <th>Work Item</th>
                                <th>Work Type</th>
                                <th>Unit</th>
                                <th class="text-primary">Target</th>
                                <th class="text-success">Achieved</th>
                                <th class="text-danger">Shortfall</th>
                                <th>Miss %</th>
                                <th>Nav</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($missedTargets as $m): ?>
                            <?php
                            $missRatio = $m['planned_qty'] > 0 ? ($m['missed_qty'] / $m['planned_qty']) : 0;
                            $missPct   = round($missRatio * 100, 1);
                            $rowBg     = $missPct >= 50 ? 'rgba(239, 68, 68, 0.15)' : ($missPct >= 25 ? 'rgba(245, 158, 11, 0.15)' : '');
                            ?>
                            <tr style="background:<?= $rowBg ?>;">
                                <td><?= htmlspecialchars($m['phase_name']) ?></td>
                                <td><?= date('M Y', strtotime($m['target_month'])) ?></td>
                                <td>
                                    <span class="badge bg-dark text-white" style="font-size:0.75rem;">Wk <?= $m['week_number'] ?></span>
                                    <div class="text-muted" style="font-size:0.7rem;">
                                        <?= date('d M', strtotime($m['week_start'])) ?> –
                                        <?= date('d M', strtotime($m['week_end'])) ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($m['item_name']) ?></td>
                                <td><?= htmlspecialchars($m['work_type_name'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($m['unit']) ?></td>
                                <td class="qty-plan"><?= number_format($m['planned_qty'], 1) ?></td>
                                <td class="qty-ach"><?= number_format($m['achieved_qty'], 1) ?></td>
                                <td class="qty-miss">–<?= number_format($m['missed_qty'], 1) ?></td>
                                <td>
                                    <span class="badge-severity <?= $missPct >= 50 ? 'sev-high' : ($missPct >= 25 ? 'sev-medium' : 'sev-low') ?>">
                                        <?= $missPct ?>%
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= $base_url ?>/con-monthly-review/<?= $projectId ?>/phase/<?= $m['phase_id'] ?>/month/<?= date('Y-m', strtotime($m['target_month'])) ?>"
                                       class="btn btn-sm btn-outline-primary" style="font-size:0.75rem; padding:3px 8px;">
                                        View Review
                                    </a>
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

<!-- ── Add Risk Modal ─────────────────────────────────────────────── -->
<div class="modal fade" id="addRiskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-shield-plus me-2"></i>Log New Risk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= $base_url ?>/con-risk-dashboard-store">
                <div class="modal-body p-4">
                    <input type="hidden" name="project_id" value="<?= $projectId ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Phase (optional)</label>
                            <select name="phase_id" class="form-select">
                                <option value="">— All Phases —</option>
                                <?php foreach ($phases as $ph): ?>
                                    <option value="<?= $ph['id'] ?>"><?= htmlspecialchars($ph['phase_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" required>
                                <option>Progress</option>
                                <option>Labour</option>
                                <option>Material</option>
                                <option>Design</option>
                                <option>Finance</option>
                                <option>Weather</option>
                                <option>Contractor</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Risk Description <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control" rows="3" required placeholder="Describe the risk in detail…"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Impact</label>
                            <select name="impact" class="form-select">
                                <option>Low</option>
                                <option selected>Medium</option>
                                <option>High</option>
                                <option>Critical</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Likelihood</label>
                            <select name="likelihood" class="form-select">
                                <option>Low</option>
                                <option selected>Medium</option>
                                <option>High</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Severity</label>
                            <select name="severity" class="form-select">
                                <option>Low</option>
                                <option selected>Medium</option>
                                <option>High</option>
                                <option>Critical</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Owner / Responsible</label>
                            <input type="text" name="owner" class="form-control" placeholder="e.g. Site Engineer, PM">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-bold">
                        <i class="bi bi-shield-exclamation me-1"></i> Log Risk
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showTab(id, el) {
    document.querySelectorAll('#tab-register, #tab-missed').forEach(t => t.style.display = 'none');
    document.querySelectorAll('#riskTabs .nav-link').forEach(l => l.classList.remove('active'));
    document.getElementById(id).style.display = 'block';
    el.classList.add('active');
    return false;
}
function toggleRiskDesc(el) {
    el.classList.toggle('truncated');
}
</script>
