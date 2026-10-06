<?php
$page_title = "Edit Project Camera";
$page = 'Edit Camera';
$show_breadcrumb = true;
?>

<div class="row">
    <div class="col-12">
        <div class="card__wrapper p-4 w-100">
            <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
                <div>
                    <h4 class="mb-1 text-dark"><i class="fa-solid fa-pen-to-square text-primary me-2"></i>Edit Project Camera Link</h4>
                    <p class="text-muted small mb-0">Update live streaming web link or details for <strong><?= htmlspecialchars($camera['camera_name'] ?: 'Camera #' . $camera['id']) ?></strong>.</p>
                </div>
                <a href="<?= $GLOBALS['base_url'] ?>/camera-lists<?= !empty($camera['project_id']) ? '?project_id=' . $camera['project_id'] : '' ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to Camera List
                </a>
            </div>

            <form action="<?= $GLOBALS['base_url'] ?>/update-camera/<?= (int)$camera['id'] ?>" method="post">
                <div class="row gy-3">
                    <!-- Project (Required) -->
                    <div class="col-lg-6 col-12">
                        <label for="project_id" class="form-label fw-semibold">
                            Project <span class="text-danger">*</span>
                        </label>
                        <select class="form-select select2" name="project_id" id="project_id" data-placeholder="-- Type to search & select project --" required>
                            <option value="">-- Select Project --</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?= $project['id'] ?>" <?= ((int)$camera['project_id'] === (int)$project['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($project['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Assigned project for this camera link.</div>
                    </div>

                    <!-- Web Link (Required) -->
                    <div class="col-lg-6 col-12">
                        <label for="web_link" class="form-label fw-semibold">
                            Web Link <span class="text-danger">*</span>
                        </label>
                        <input class="form-control" name="web_link" id="web_link" type="text" 
                               value="<?= htmlspecialchars($camera['web_link'] ?? '') ?>"
                               placeholder="https://example.com/live or stream URL" required>
                        <div class="form-text">Live web viewer link / embed stream URL (Required).</div>
                    </div>

                    <!-- Camera Title / Name (Optional) -->
                    <div class="col-lg-6 col-12">
                        <label for="camera_name" class="form-label fw-semibold">
                            Camera Name / Title <span class="text-muted fw-normal">(Optional)</span>
                        </label>
                        <input class="form-control" name="camera_name" id="camera_name" type="text" 
                               value="<?= htmlspecialchars($camera['camera_name'] ?? '') ?>"
                               placeholder="e.g. Main Gate Camera, Tower View">
                        <div class="form-text">Leave blank to auto-generate from project name.</div>
                    </div>

                    <!-- RTSP URL (Optional) -->
                    <div class="col-lg-6 col-12">
                        <label for="rtsp_url" class="form-label fw-semibold">
                            RTSP URL <span class="text-muted fw-normal">(Optional)</span>
                        </label>
                        <input class="form-control" name="rtsp_url" id="rtsp_url" type="text" 
                               value="<?= htmlspecialchars($camera['rtsp_url'] ?? '') ?>"
                               placeholder="rtsp://admin:password@ip:port/stream">
                        <div class="form-text">Direct RTSP feed link if available.</div>
                    </div>

                    <!-- Status -->
                    <div class="col-lg-6 col-12">
                        <label for="status" class="form-label fw-semibold">Status</label>
                        <select id="status" class="form-select" name="status">
                            <option value="active" <?= (($camera['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= (($camera['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="col-12 mt-4 pt-3 border-top d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fa-solid fa-check me-1"></i> Update Camera Link
                        </button>
                        <a href="<?= $GLOBALS['base_url'] ?>/camera-lists<?= !empty($camera['project_id']) ? '?project_id=' . $camera['project_id'] : '' ?>" class="btn btn-light px-4">
                            Cancel
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    if (typeof initSelect2 === 'function') {
        initSelect2();
    } else if ($.fn.select2) {
        $('#project_id').select2({
            width: '100%',
            placeholder: '-- Type to search & select project --',
            allowClear: true
        });
    }
});
</script>
