<?php
$page_title = "Project Cameras";
$page = "Project Cameras";
$show_breadcrumb = true;
$selectedProjectId = $selected_project_id ?? null;
?>

<div class="row gy-3 mb-3">
    <div class="col-12">
        <div class="card__wrapper p-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 rounded bg-primary-subtle text-primary">
                        <i class="fa-solid fa-video fs-4"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 text-dark fw-bold">Project Cameras</h4>
                        <small class="text-muted">Manage live project streaming web links and RTSP feeds</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a class="btn btn-primary" href="<?= $GLOBALS['base_url'] ?>/create-camera<?= $selectedProjectId ? '?project_id=' . $selectedProjectId : '' ?>">
                        <i class="fa-solid fa-plus me-1"></i> Add Camera Link
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
                    <select class="form-select form-select-sm select2" id="projectFilter" data-placeholder="-- Filter by Project --">
                        <option value="">-- All Projects (<?= count($cameras) ?> Cameras) --</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>" <?= ($selectedProjectId == $project['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($project['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Instant Search -->
                <div class="col-md-5 col-12">
                    <label class="form-label small text-muted mb-1 fw-semibold">Search Cameras</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" class="form-control" id="cameraSearchInput" placeholder="Search by project, camera name, link..." onkeyup="filterCameraTable()">
                    </div>
                </div>

                <!-- Reset Button -->
                <div class="col-md-2 col-12 d-flex align-items-end">
                    <?php if ($selectedProjectId): ?>
                        <a href="<?= $GLOBALS['base_url'] ?>/camera-lists" class="btn btn-sm btn-outline-secondary w-100">
                            <i class="fa-solid fa-rotate-left me-1"></i> Clear Filter
                        </a>
                    <?php else: ?>
                        <button type="button" class="btn btn-sm btn-light w-100" onclick="document.getElementById('cameraSearchInput').value=''; filterCameraTable();">
                            Clear Search
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Camera List Table -->
<div class="row">
    <div class="col-12">
        <div class="card__wrapper p-0 overflow-hidden">
            <div class="card__title-wrap p-3 border-bottom d-flex align-items-center justify-content-between">
                <h5 class="card__heading-title mb-0">
                    <i class="fa-solid fa-list-check me-2 text-primary"></i>Camera Links
                    <?php if ($selectedProjectId): ?>
                        <span class="badge bg-primary ms-2">Filtered</span>
                    <?php endif; ?>
                </h5>
                <span class="text-muted small">Total: <strong><?= count($cameras) ?></strong> camera(s)</span>
            </div>

            <div class="card__table table-responsive">
                <table class="table table-hover table-bordered align-middle mb-0" id="camerasTable">
                    <thead class="table-light">
                        <tr class="table__title text-muted small text-uppercase">
                            <th style="width: 50px;" class="text-center">#</th>
                            <th style="min-width: 180px;">Project Name</th>
                            <th style="min-width: 160px;">Camera Name</th>
                            <th style="min-width: 250px;">Web Link (Live Stream)</th>
                            <th style="min-width: 180px;">RTSP URL</th>
                            <th style="width: 100px;" class="text-center">Status</th>
                            <th style="width: 120px;" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php if (empty($cameras)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fa-solid fa-video-slash fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        <h6>No cameras found</h6>
                                        <p class="small mb-3">No camera links have been configured for this selection.</p>
                                        <a href="<?= $GLOBALS['base_url'] ?>/create-camera<?= $selectedProjectId ? '?project_id=' . $selectedProjectId : '' ?>" class="btn btn-sm btn-primary">
                                            <i class="fa-solid fa-plus me-1"></i> Add New Camera Link
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $sl = 1; foreach ($cameras as $camera): ?>
                                <tr>
                                    <td class="text-center text-muted fw-bold"><?= $sl++ ?></td>
                                    
                                    <!-- Project -->
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fa-solid fa-building text-primary"></i>
                                            <div>
                                                <a href="<?= $GLOBALS['base_url'] ?>/camera-lists?project_id=<?= (int)$camera['project_id'] ?>" 
                                                   class="fw-bold text-dark text-decoration-none" title="Filter by this project">
                                                    <?= htmlspecialchars($camera['project_name'] ?: ('Project #' . $camera['project_id'])) ?>
                                                </a>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Camera Name -->
                                    <td>
                                        <span class="fw-semibold text-dark">
                                            <?= htmlspecialchars($camera['camera_name'] ?: 'Camera #' . $camera['id']) ?>
                                        </span>
                                    </td>

                                    <!-- Web Link -->
                                    <td>
                                        <?php if (!empty($camera['web_link'])): ?>
                                            <div class="d-flex align-items-center gap-2">
                                                <a href="<?= htmlspecialchars($camera['web_link']) ?>" target="_blank" 
                                                   class="btn btn-sm btn-outline-primary py-0 px-2 text-nowrap" title="Open live stream in new tab">
                                                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Open
                                                </a>
                                                <span class="text-truncate text-muted small" style="max-width: 250px;" title="<?= htmlspecialchars($camera['web_link']) ?>">
                                                    <?= htmlspecialchars($camera['web_link']) ?>
                                                </span>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small fst-italic">No web link</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- RTSP URL -->
                                    <td>
                                        <?php if (!empty($camera['rtsp_url'])): ?>
                                            <span class="text-truncate d-inline-block small text-muted" style="max-width: 200px;" title="<?= htmlspecialchars($camera['rtsp_url']) ?>">
                                                <?= htmlspecialchars($camera['rtsp_url']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">None</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Status -->
                                    <td class="text-center">
                                        <?php if (($camera['status'] ?? 'active') === 'active'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                <i class="fa-solid fa-circle-dot me-1 small"></i>Active
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                                Inactive
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Actions -->
                                    <td class="text-center">
                                        <div class="d-flex align-items-center justify-content-center gap-2">
                                            <!-- Edit -->
                                            <a href="<?= $GLOBALS['base_url'] ?>/edit-camera/<?= (int)$camera['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary py-1 px-2" title="Edit Camera">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>

                                            <!-- Delete -->
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger py-1 px-2" 
                                                    title="Delete Camera"
                                                    onclick="handleDelete(<?= (int)$camera['id'] ?>, '<?= htmlspecialchars(addslashes($camera['camera_name'] ?: 'Camera #' . $camera['id'])) ?>')">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
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

<!-- Hidden Delete Form -->
<form id="deleteCameraForm" method="post" action="<?= $GLOBALS['base_url'] ?>/delete-camera" style="display:none;">
    <input type="hidden" name="id" id="deleteCameraId">
</form>

<script>
function filterByProject(projectId) {
    const baseUrl = '<?= $GLOBALS['base_url'] ?>/camera-lists';
    if (projectId) {
        window.location.href = baseUrl + '?project_id=' + encodeURIComponent(projectId);
    } else {
        window.location.href = baseUrl;
    }
}

function filterCameraTable() {
    const input = document.getElementById('cameraSearchInput');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('camerasTable');
    const tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) {
        const row = tr[i];
        if (row.cells.length > 1) {
            const rowText = row.innerText.toLowerCase();
            row.style.display = rowText.includes(filter) ? '' : 'none';
        }
    }
}

function handleDelete(id, name) {
    if (confirm('Are you sure you want to delete camera "' + name + '"?')) {
        document.getElementById('deleteCameraId').value = id;
        document.getElementById('deleteCameraForm').submit();
    }
}

$(document).ready(function() {
    if (typeof initSelect2 === 'function') {
        initSelect2();
    } else if ($.fn.select2) {
        $('#projectFilter').select2({ width: '100%' });
    }
    $('#projectFilter').on('change', function() {
        filterByProject($(this).val());
    });
});
</script>