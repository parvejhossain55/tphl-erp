<style>
    .ced-card {
        border-radius: 14px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        border: 1px solid rgba(0,0,0,0.06);
        background: #ffffff;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .ced-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    }
    .summary-metric-card {
        border-radius: 12px;
        padding: 18px 20px;
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        position: relative;
        overflow: hidden;
    }
    .metric-icon-box {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        flex-shrink: 0;
    }
    .agent-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #e2e8f0;
    }
    .agent-avatar-placeholder {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 16px;
        flex-shrink: 0;
    }
    .project-pill {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        margin: 2px 3px 2px 0;
    }
    .project-checkbox-item {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px 14px;
        transition: all 0.2s;
        cursor: pointer;
        background: #f8fafc;
    }
    .project-checkbox-item:hover {
        border-color: #3b82f6;
        background: #f0f7ff;
    }
    .project-checkbox-item.checked {
        border-color: #2563eb;
        background: #eff6ff;
    }
</style>

<div class="container-fluid py-3">
    <!-- PAGE HEADER -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold mb-1" style="color: #0f172a;">
                <i class="fa-solid fa-diagram-project text-primary me-2"></i> Manage Projects for CED
            </h4>
            <p class="text-muted small mb-0">Assign active projects to Client Experience officers for dedicated client management, EMI, and development charge collection.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $base_url ?>/ced-projects-history" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                <i class="fa-solid fa-clock-rotate-left me-1"></i> CED Projects History
            </a>
            <a href="<?= $base_url ?>/ced-dashboard" class="btn btn-secondary btn-sm rounded-pill px-3">
                <i class="fa-solid fa-arrow-left me-1"></i> CED Dashboard
            </a>
        </div>
    </div>

    <!-- METRICS SUMMARY CARDS -->
    <?php
    $totalOfficers = count($summary);
    $totalActiveProjects = count($projects);
    $allAssignedPids = [];
    foreach ($summary as $s) {
        if (!empty($s['assigned_project_ids'])) {
            $pIds = explode(',', $s['assigned_project_ids']);
            foreach ($pIds as $pid) {
                if ($pid > 0) $allAssignedPids[(int)$pid] = true;
            }
        }
    }
    $assignedProjectsCount = count($allAssignedPids);
    $unassignedProjectsCount = max(0, $totalActiveProjects - $assignedProjectsCount);
    ?>

    <div class="row  mb-4">
        <div class="col-6 col-md-3">
            <div class="summary-metric-card bg-white ced-card border-start border-4 border-primary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase" style="font-size:11px;">CED Officers</span>
                        <h3 class="fw-bold mb-0 mt-1" style="color: #1e293b;"><?= number_format($totalOfficers) ?></h3>
                        <small class="text-muted">Active Agents</small>
                    </div>
                    <div class="metric-icon-box bg-primary-subtle text-primary">
                        <i class="fa-solid fa-user-group"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="summary-metric-card bg-white ced-card border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase" style="font-size:11px;">Total Active Projects</span>
                        <h3 class="fw-bold mb-0 mt-1" style="color: #1e293b;"><?= number_format($totalActiveProjects) ?></h3>
                        <small class="text-muted">In Company Portfolio</small>
                    </div>
                    <div class="metric-icon-box bg-success-subtle text-success">
                        <i class="fa-solid fa-building"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="summary-metric-card bg-white ced-card border-start border-4 border-info">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase" style="font-size:11px;">Assigned Projects</span>
                        <h3 class="fw-bold mb-0 mt-1" style="color: #1e293b;"><?= number_format($assignedProjectsCount) ?></h3>
                        <small class="text-muted"><?= round(($assignedProjectsCount / max(1, $totalActiveProjects)) * 100) ?>% Covered</small>
                    </div>
                    <div class="metric-icon-box bg-info-subtle text-info">
                        <i class="fa-solid fa-handshake"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="summary-metric-card bg-white ced-card border-start border-4 border-warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase" style="font-size:11px;">Unassigned Projects</span>
                        <h3 class="fw-bold mb-0 mt-1" style="color: #1e293b;"><?= number_format($unassignedProjectsCount) ?></h3>
                        <small class="text-warning fw-semibold"><?= $unassignedProjectsCount > 0 ? 'Needs Assignment' : 'All Assigned' ?></small>
                    </div>
                    <div class="metric-icon-box bg-warning-subtle text-warning">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN TABLE CARD -->
    <div class="card ced-card border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-bold text-dark fs-6"><i class="fa-solid fa-users-gear text-primary me-1"></i> Officer Project Assignments</span>
                <span class="badge bg-secondary rounded-pill"><?= count($summary) ?> Officers</span>
            </div>
            <!-- Search Filter -->
            <form method="GET" action="<?= $base_url ?>/manage-projects-for-ced" class="d-flex gap-2">
                <div class="input-group input-group-sm" style="max-width: 280px;">
                    <input type="text" name="search" class="form-control" placeholder="Search by officer or project..." value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-search"></i></button>
                    <?php if (!empty($search)): ?>
                        <a href="<?= $base_url ?>/manage-projects-for-ced" class="btn btn-outline-secondary" title="Clear Search"><i class="fa-solid fa-times"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="cedAssignmentTable">
                    <thead class="table-light">
                        <tr class="small text-uppercase text-muted">
                            <th class="ps-3" style="width: 250px;">Officer Name</th>
                            <th style="min-width: 280px;">Assigned Projects</th>
                            <th class="text-center" style="width: 100px;">Clients</th>
                            <th class="text-center" style="width: 90px;">Units</th>
                            <th class="text-end" style="width: 130px;">Total Value</th>
                            <th class="text-end" style="width: 130px;">Total Collected</th>
                            <th class="text-end" style="width: 130px;">Total Due</th>
                            <th class="text-center" style="width: 120px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($summary)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-user-slash fs-2 d-block mb-2 text-secondary"></i>
                                    No Client Experience officers found matching your search.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($summary as $row): ?>
                                <tr id="agent-row-<?= $row['agent_id'] ?>">
                                    <td class="ps-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <?php if (!empty($row['agent_photo']) && file_exists(__DIR__ . '/../../../../public/uploads/' . $row['agent_photo'])): ?>
                                                <img src="<?= $base_url ?>/public/uploads/<?= htmlspecialchars($row['agent_photo']) ?>" class="agent-avatar" alt="Avatar">
                                            <?php else: ?>
                                                <div class="agent-avatar-placeholder">
                                                    <?= strtoupper(substr($row['agent_name'], 0, 1)) ?>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <span class="fw-bold d-block text-dark" style="font-size: 13.5px;"><?= htmlspecialchars($row['agent_name']) ?></span>
                                                <span class="badge bg-light text-secondary border small" style="font-size: 10px;"><?= htmlspecialchars($row['role_name']) ?></span>
                                                <?php if (!empty($row['agent_phone'])): ?>
                                                    <small class="text-muted d-block" style="font-size: 11px;"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($row['agent_phone']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap align-items-center gap-1" id="agent-badges-<?= $row['agent_id'] ?>">
                                            <?php if ($row['projects_count'] === 0): ?>
                                                <span class="text-muted small fst-italic"><i class="fa-regular fa-folder-open me-1"></i>No projects assigned yet</span>
                                            <?php else: ?>
                                                <?php foreach ($row['projects_list'] as $p): ?>
                                                    <span class="project-pill" title="<?= htmlspecialchars($p['name']) ?>">
                                                        <i class="fa-regular fa-building me-1"></i><?= htmlspecialchars($p['name']) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted fw-semibold mt-1 d-block" id="agent-proj-count-<?= $row['agent_id'] ?>">
                                            Total: <?= $row['projects_count'] ?> Project<?= $row['projects_count'] === 1 ? '' : 's' ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-bold text-dark fs-6"><?= number_format($row['total_clients']) ?></span>
                                        <small class="d-block text-muted" style="font-size: 10px;">Clients</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary-subtle text-secondary fw-semibold px-2 py-1 rounded-pill">
                                            <?= number_format($row['total_units']) ?>
                                        </span>
                                        <small class="d-block text-muted" style="font-size: 10px;">Units Booked</small>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-semibold text-dark" style="font-size: 13px;">৳ <?= number_format($row['total_price_value'], 0) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold text-success" style="font-size: 13px;">৳ <?= number_format($row['total_collected'], 0) ?></span>
                                        <?php if ($row['collection_rate'] > 0): ?>
                                            <small class="d-block text-muted" style="font-size: 10px;"><?= $row['collection_rate'] ?>% recovered</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <span class="fw-bold text-danger" style="font-size: 13px;">৳ <?= number_format($row['total_overdue'], 0) ?></span>
                                        <small class="d-block text-muted" style="font-size: 10px;">Overdue EMIs</small>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-primary btn-sm rounded px-2 py-1 shadow-sm" 
                                                onclick="openAssignModal(<?= $row['agent_id'] ?>, '<?= htmlspecialchars(addslashes($row['agent_name'])) ?>', '<?= htmlspecialchars(addslashes($row['role_name'])) ?>')"
                                                title="Assign / Modify Projects">
                                                <i class="fa-solid fa-diagram-project me-1"></i> Assign
                                            </button>
                                            <a href="<?= $base_url ?>/ced-projects-history?agent_id=<?= $row['agent_id'] ?>" 
                                                class="btn btn-outline-secondary btn-sm rounded px-2 py-1 ms-1" 
                                                title="View CED Projects History">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ASSIGNMENT MODAL -->
<div class="modal fade" id="assignProjectsModal" tabindex="-1" aria-labelledby="assignModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header bg-primary text-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 18px;">
                        <i class="fa-solid fa-diagram-project"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="assignModalLabel">Assign Projects to Officer</h5>
                        <small class="text-white-50" id="modalAgentSubtitle">Loading officer info...</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <!-- Search & Bulk Controls inside Modal -->
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                    <div class="input-group input-group-sm" style="max-width: 320px;">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-search text-muted"></i></span>
                        <input type="text" id="projectModalFilter" class="form-control" placeholder="Quick find project..." onkeyup="filterProjectCheckboxes()">
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" onclick="toggleSelectAllProjects(true)">
                            <i class="fa-solid fa-check-double me-1"></i> Select All
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" onclick="toggleSelectAllProjects(false)">
                            <i class="fa-solid fa-xmark me-1"></i> Deselect All
                        </button>
                    </div>
                </div>

                <div class="alert alert-info py-2 px-3 small d-flex align-items-center mb-3">
                    <i class="fa-solid fa-circle-info fs-5 me-2 flex-shrink-0 text-info"></i>
                    <span>Select the projects this officer will manage. Clients, EMIs, development charges, and due tracking for selected projects will automatically scope to this officer.</span>
                </div>

                <!-- Projects Checkbox Grid -->
                <form id="assignProjectsForm">
                    <input type="hidden" name="user_id" id="modalAgentId" value="">
                    
                    <div class="row g-2" id="projectCheckboxesContainer" style="max-height: 420px; overflow-y: auto;">
                        <?php foreach ($projects as $proj): ?>
                            <div class="col-md-6 project-item-col" data-project-name="<?= strtolower(htmlspecialchars($proj['name'])) ?>">
                                <div class="project-checkbox-item" onclick="toggleProjectItem(this, <?= $proj['id'] ?>)">
                                    <div class="form-check d-flex align-items-center justify-content-between mb-0 pointer-events-none">
                                        <div class="d-flex align-items-center gap-2">
                                            <input class="form-check-input project-chk" type="checkbox" name="project_ids[]" value="<?= $proj['id'] ?>" id="chk_proj_<?= $proj['id'] ?>" style="cursor: pointer;">
                                            <label class="form-check-label fw-semibold text-dark mb-0 text-truncate" style="max-width: 250px; font-size: 13px;" for="chk_proj_<?= $proj['id'] ?>">
                                                <?= htmlspecialchars($proj['name']) ?>
                                            </label>
                                        </div>
                                        <?php if (!empty($proj['project_sale_status'])): ?>
                                            <span class="badge bg-light text-secondary border small" style="font-size: 9.5px;"><?= htmlspecialchars($proj['project_sale_status']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </form>
            </div>

            <div class="modal-footer bg-light py-2 px-4 d-flex justify-content-between align-items-center">
                <span class="small text-muted" id="selectedCountBadge">0 Projects Selected</span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm px-4 fw-bold" id="btnSaveAssignments" onclick="submitProjectAssignments()">
                        <i class="fa-solid fa-save me-1"></i> Save Assignments
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let activeAgentId = null;

    function openAssignModal(agentId, agentName, roleName) {
        activeAgentId = agentId;
        document.getElementById('modalAgentId').value = agentId;
        document.getElementById('modalAgentSubtitle').textContent = agentName + ' (' + roleName + ')';
        
        // Reset search filter
        document.getElementById('projectModalFilter').value = '';
        filterProjectCheckboxes();

        // Clear all checkboxes first
        document.querySelectorAll('.project-chk').forEach(chk => {
            chk.checked = false;
            chk.closest('.project-checkbox-item').classList.remove('checked');
        });
        updateSelectedCount();

        // Fetch currently assigned project IDs via AJAX
        fetch('<?= $base_url ?>/ced-project-assignments/agent-projects/' + agentId)
            .then(res => res.json())
            .then(data => {
                if (data.success && Array.isArray(data.assigned_project_ids)) {
                    data.assigned_project_ids.forEach(pId => {
                        const chk = document.getElementById('chk_proj_' + pId);
                        if (chk) {
                            chk.checked = true;
                            chk.closest('.project-checkbox-item').classList.add('checked');
                        }
                    });
                    updateSelectedCount();
                }
                const modal = new bootstrap.Modal(document.getElementById('assignProjectsModal'));
                modal.show();
            })
            .catch(err => {
                console.error(err);
                alert('Failed to load agent project assignments. Please try again.');
            });
    }

    function toggleProjectItem(element, projId) {
        const chk = document.getElementById('chk_proj_' + projId);
        if (chk) {
            chk.checked = !chk.checked;
            if (chk.checked) {
                element.classList.add('checked');
            } else {
                element.classList.remove('checked');
            }
            updateSelectedCount();
        }
    }

    function toggleSelectAllProjects(select) {
        document.querySelectorAll('.project-item-col').forEach(col => {
            if (col.style.display !== 'none') {
                const chk = col.querySelector('.project-chk');
                const box = col.querySelector('.project-checkbox-item');
                chk.checked = select;
                if (select) {
                    box.classList.add('checked');
                } else {
                    box.classList.remove('checked');
                }
            }
        });
        updateSelectedCount();
    }

    function filterProjectCheckboxes() {
        const query = document.getElementById('projectModalFilter').value.toLowerCase().trim();
        document.querySelectorAll('.project-item-col').forEach(col => {
            const name = col.getAttribute('data-project-name') || '';
            if (query === '' || name.includes(query)) {
                col.style.display = '';
            } else {
                col.style.display = 'none';
            }
        });
    }

    function updateSelectedCount() {
        const count = document.querySelectorAll('.project-chk:checked').length;
        document.getElementById('selectedCountBadge').textContent = count + ' Project' + (count === 1 ? '' : 's') + ' Selected';
    }

    function submitProjectAssignments() {
        const form = document.getElementById('assignProjectsForm');
        const btn = document.getElementById('btnSaveAssignments');
        const formData = new FormData(form);

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Saving...';

        fetch('<?= $base_url ?>/ced-project-assignments/save', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-save me-1"></i> Save Assignments';

            if (data.success) {
                // Close modal
                const modalEl = document.getElementById('assignProjectsModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();

                // Show success message and reload page smoothly
                alert(data.message || 'Projects successfully assigned!');
                window.location.reload();
            } else {
                alert(data.message || 'Error occurred while saving assignments.');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-save me-1"></i> Save Assignments';
            console.error(err);
            alert('Failed to save project assignments. Network or server error.');
        });
    }
</script>

