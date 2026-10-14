<?php
$page_title = "Project Progress";
$page = "Project Progress";
$show_breadcrumb = true;
$selectedProjectId = $selected_project_id ?? null;
?>

<div class="row gy-3 mb-3">
    <div class="col-12">
        <div class="card__wrapper p-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 rounded bg-success-subtle text-success">
                        <i class="fa-solid fa-chart-line fs-4"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 text-dark fw-bold">Project Progress Management</h4>
                        <small class="text-muted">Track work category progress, targets, and weighted overall achievements</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a class="btn btn-primary" href="<?= $GLOBALS['base_url'] ?>/create-project-progress<?= $selectedProjectId ? '?project_id=' . $selectedProjectId : '' ?>">
                        <i class="fa-solid fa-plus me-1"></i> Add / Update Progress
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters & Search -->
<div class="row gy-3 mb-3">
    <div class="col-12">
        <div class="card__wrapper p-3">
            <div class="row g-3 align-items-center">
                <!-- Filter by Project -->
                <div class="col-md-5 col-12">
                    <label class="form-label small text-muted mb-1 fw-semibold">Filter by Project</label>
                    <select class="form-select form-select-sm select2" id="projectFilter" data-placeholder="-- Filter by Project --" onchange="filterByProject(this.value)">
                        <option value="">-- All Projects (<?= count($progress_list) ?> Records) --</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>" <?= ($selectedProjectId == $project['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($project['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Instant Search -->
                <div class="col-md-5 col-12">
                    <label class="form-label small text-muted mb-1 fw-semibold">Search Records</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" class="form-control" id="progressSearchInput" placeholder="Search project name, location..." onkeyup="filterProgressTable()">
                    </div>
                </div>

                <!-- Reset Button -->
                <div class="col-md-2 col-12 d-flex align-items-end">
                    <?php if ($selectedProjectId): ?>
                        <a href="<?= $GLOBALS['base_url'] ?>/project-progress-lists" class="btn btn-sm btn-outline-secondary w-100">
                            <i class="fa-solid fa-rotate-left me-1"></i> Clear Filter
                        </a>
                    <?php else: ?>
                        <button type="button" class="btn btn-sm btn-light w-100" onclick="document.getElementById('progressSearchInput').value=''; filterProgressTable();">
                            Clear Search
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Table Card -->
<div class="row">
    <div class="col-12">
        <div class="card__wrapper p-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="progressTable">
                    <thead class="table-light text-uppercase fs-7">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Project & Location</th>
                            <th>Last Updated</th>
                            <th>Monthly Target / Achieved</th>
                            <th>Work Categories</th>
                            <th style="width: 220px;">Total Progress</th>
                            <th class="text-end" style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($progress_list)): ?>
                            <?php foreach ($progress_list as $index => $row): ?>
                                <?php
                                    $totalPct = (float)($row['total_progress'] ?? 0);
                                    $badgeClass = 'bg-secondary';
                                    $progressBg = 'bg-secondary';
                                    if ($totalPct >= 100) {
                                        $badgeClass = 'bg-success';
                                        $progressBg = 'bg-success';
                                    } elseif ($totalPct >= 60) {
                                        $badgeClass = 'bg-primary';
                                        $progressBg = 'bg-primary';
                                    } elseif ($totalPct >= 30) {
                                        $badgeClass = 'bg-info text-dark';
                                        $progressBg = 'bg-info';
                                    } elseif ($totalPct > 0) {
                                        $badgeClass = 'bg-warning text-dark';
                                        $progressBg = 'bg-warning';
                                    }
                                ?>
                                <tr class="progress-row" data-project="<?= htmlspecialchars(strtolower($row['project_name'] ?? '')) ?>" data-location="<?= htmlspecialchars(strtolower($row['location_name'] ?? '')) ?>">
                                    <td class="text-muted fw-semibold"><?= $index + 1 ?></td>
                                    <td>
                                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($row['project_name'] ?? 'Unnamed Project') ?></div>
                                        <?php if (!empty($row['location_name'])): ?>
                                            <span class="text-muted small"><i class="fa-solid fa-location-dot me-1 text-danger"></i><?= htmlspecialchars($row['location_name']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <i class="fa-regular fa-calendar me-1"></i>
                                            <?= !empty($row['last_updated_date']) ? date('d M, Y', strtotime($row['last_updated_date'])) : 'N/A' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <span class="text-muted">Target:</span> <strong><?= number_format((float)($row['last_month_target'] ?? 0), 1) ?>%</strong>
                                        </div>
                                        <div class="small">
                                            <span class="text-muted">Achieved:</span> <strong class="text-success"><?= number_format((float)($row['last_month_achieved'] ?? 0), 1) ?>%</strong>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small text-muted mb-1">
                                            <span class="badge bg-primary-subtle text-primary">Structural: <?= number_format((float)$row['structural_work'], 1) ?>%</span>
                                        </div>
                                        <div class="d-flex flex-wrap gap-1" style="max-width: 320px;">
                                            <span class="badge bg-light text-muted border" title="Plaster">Plaster: <?= (int)$row['plaster_work'] ?>%</span>
                                            <span class="badge bg-light text-muted border" title="Brick">Brick: <?= (int)$row['brick_work'] ?>%</span>
                                            <span class="badge bg-light text-muted border" title="Electrical">Elec: <?= (int)$row['electrical_work'] ?>%</span>
                                            <span class="badge bg-light text-muted border" title="Plumbing">Plumb: <?= (int)$row['plumbing_work'] ?>%</span>
                                            <span class="badge bg-light text-muted border" title="Tiles">Tiles: <?= (int)$row['tiles_work'] ?>%</span>
                                            <span class="badge bg-light text-muted border" title="Paint">Paint: <?= (int)$row['paint_work'] ?>%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-bold fs-6 text-dark"><?= number_format($totalPct, 1) ?>%</span>
                                            <span class="badge <?= $badgeClass ?> small"><?= $totalPct >= 100 ? 'Completed' : 'In Progress' ?></span>
                                        </div>
                                        <div class="progress" style="height: 10px; border-radius: 6px;">
                                            <div class="progress-bar <?= $progressBg ?> progress-bar-striped progress-bar-animated"
                                                 role="progressbar"
                                                 style="width: <?= min(100, max(0, $totalPct)) ?>%;"
                                                 aria-valuenow="<?= $totalPct ?>"
                                                 aria-valuemin="0"
                                                 aria-valuemax="100"></div>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <a href="<?= $GLOBALS['base_url'] ?>/edit-project-progress/<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit Progress">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                            <form action="<?= $GLOBALS['base_url'] ?>/delete-project-progress/<?= $row['id'] ?>" method="POST" onsubmit="return confirm('Are you sure you want to delete this progress record?');" style="display:inline;">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Progress">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-chart-pie fs-1 text-secondary opacity-50 mb-2 d-block"></i>
                                    <h5>No Project Progress Recorded Yet</h5>
                                    <p class="small text-muted mb-3">Add the first progress update for any active project.</p>
                                    <a class="btn btn-primary btn-sm" href="<?= $GLOBALS['base_url'] ?>/create-project-progress">
                                        <i class="fa-solid fa-plus me-1"></i> Add Progress Now
                                    </a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function filterByProject(projectId) {
    if (projectId) {
        window.location.href = '<?= $GLOBALS['base_url'] ?>/project-progress-lists?project_id=' + projectId;
    } else {
        window.location.href = '<?= $GLOBALS['base_url'] ?>/project-progress-lists';
    }
}

function filterProgressTable() {
    const q = document.getElementById('progressSearchInput').value.toLowerCase().trim();
    const rows = document.querySelectorAll('#progressTable tbody tr.progress-row');
    rows.forEach(r => {
        const project = r.getAttribute('data-project') || '';
        const location = r.getAttribute('data-location') || '';
        if (project.includes(q) || location.includes(q)) {
            r.style.display = '';
        } else {
            r.style.display = 'none';
        }
    });
}
</script>
