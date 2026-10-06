<?php
$page_title = "Add Project Cameras";
$page = 'Add Cameras';
$show_breadcrumb = true;
$selectedProjectId = $selected_project_id ?? null;
?>

<div class="row">
    <div class="col-12">
        <div class="card__wrapper p-4 w-100">
            <!-- Header -->
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 border-bottom pb-3 gap-2">
                <div>
                    <h4 class="mb-1 text-dark">
                        <i class="fa-solid fa-video text-primary me-2"></i>Add Project Cameras
                    </h4>
                    <p class="text-muted small mb-0">Select a project and add one or multiple live camera streaming links at once.</p>
                </div>
                <a href="<?= $GLOBALS['base_url'] ?>/camera-lists<?= $selectedProjectId ? '?project_id=' . $selectedProjectId : '' ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to Camera List
                </a>
            </div>

            <form action="<?= $GLOBALS['base_url'] ?>/store-camera" method="post" id="bulkCameraForm">
                <!-- Top Section: Select Project Once -->
                <div class="card border bg-light mb-4 p-3 rounded-3">
                    <div class="row align-items-center">
                        <div class="col-lg-7 col-12">
                            <label for="project_id" class="form-label fw-bold text-dark">
                                <i class="fa-solid fa-building text-primary me-1"></i> Select Project <span class="text-danger">*</span>
                            </label>
                            <select class="form-select select2" name="project_id" id="project_id" data-placeholder="-- Type to search & select project --" required>
                                <option value="">-- Choose Project --</option>
                                <?php foreach ($projects as $project): ?>
                                    <option value="<?= $project['id'] ?>" <?= ($selectedProjectId == $project['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($project['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">All camera links added below will belong to this project.</div>
                        </div>
                        <div class="col-lg-5 col-12 text-lg-end mt-3 mt-lg-0">
                            <span class="badge bg-primary-subtle text-primary p-2 fs-7">
                                <i class="fa-solid fa-layer-group me-1"></i> Bulk Add Mode Enabled
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Dynamic Cameras Container -->
                <div class="mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h5 class="fw-bold mb-0 text-dark">
                            <i class="fa-solid fa-list-ol me-2 text-primary"></i>Camera Entries
                        </h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addMoreCameraBtn">
                            <i class="fa-solid fa-plus me-1"></i> Add Another Camera
                        </button>
                    </div>

                    <div id="camerasRepeater">
                        <!-- Camera Row 1 (Default) -->
                        <div class="camera-item-card card mb-3 border border-secondary-subtle shadow-sm rounded-3">
                            <div class="card-header bg-white py-2 px-3 d-flex align-items-center justify-content-between border-bottom">
                                <span class="fw-bold text-primary camera-row-num">
                                    <i class="fa-solid fa-camera me-1"></i> Camera #1
                                </span>
                                <button type="button" class="btn btn-xs btn-outline-danger remove-camera-btn py-0 px-2" style="display:none;" title="Remove this camera">
                                    <i class="fa-solid fa-trash me-1"></i> Remove
                                </button>
                            </div>
                            <div class="card-body p-3">
                                <div class="row">
                                    <!-- Web Link (Required) -->
                                    <div class="col-lg-6 col-12">
                                        <label class="form-label fw-semibold small">
                                            Web Link (Live Stream / Embed) <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" name="cameras[0][web_link]" 
                                               placeholder="https://example.com/live or stream URL" required>
                                        <div class="form-text small">Live stream web link (Required).</div>
                                    </div>

                                    <!-- Camera Name (Optional) -->
                                    <div class="col-lg-6 col-12">
                                        <label class="form-label fw-semibold small">
                                            Camera Title / Name <span class="text-muted fw-normal">(Optional)</span>
                                        </label>
                                        <input type="text" class="form-control" name="cameras[0][camera_name]" 
                                               placeholder="e.g. Main Gate, Tower View (Auto-generated if left empty)">
                                    </div>

                                    <!-- RTSP URL (Optional) -->
                                    <div class="col-lg-6 col-12">
                                        <label class="form-label fw-semibold small">
                                            RTSP URL <span class="text-muted fw-normal">(Optional)</span>
                                        </label>
                                        <input type="text" class="form-control" name="cameras[0][rtsp_url]" 
                                               placeholder="rtsp://admin:password@ip:port/stream">
                                    </div>

                                    <!-- Status -->
                                    <div class="col-lg-6 col-12">
                                        <label class="form-label fw-semibold small">Status</label>
                                        <select class="form-select" name="cameras[0][status]">
                                            <option value="active" selected>Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Add More Button -->
                    <div class="d-flex justify-content-center my-3">
                        <button type="button" class="btn btn-outline-primary px-4 py-2" id="addMoreCameraBtnBottom">
                            <i class="fa-solid fa-plus-circle me-1"></i> Add Another Camera
                        </button>
                    </div>
                </div>

                <!-- Submit / Actions -->
                <div class="pt-3 border-top d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Save All Cameras
                    </button>
                    <a href="<?= $GLOBALS['base_url'] ?>/camera-lists<?= $selectedProjectId ? '?project_id=' . $selectedProjectId : '' ?>" class="btn btn-light px-4">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let cameraRowIndex = 1;

function updateRowNumbers() {
    const cards = document.querySelectorAll('.camera-item-card');
    cards.forEach((card, index) => {
        const numLabel = card.querySelector('.camera-row-num');
        if (numLabel) {
            numLabel.innerHTML = '<i class="fa-solid fa-camera me-1"></i> Camera #' + (index + 1);
        }
        const removeBtn = card.querySelector('.remove-camera-btn');
        if (removeBtn) {
            removeBtn.style.display = (cards.length > 1) ? 'inline-block' : 'none';
        }
    });
}

function addCameraRow() {
    const container = document.getElementById('camerasRepeater');
    const newIndex = cameraRowIndex++;
    
    const card = document.createElement('div');
    card.className = 'camera-item-card card mb-3 border border-secondary-subtle shadow-sm rounded-3';
    card.innerHTML = `
        <div class="card-header bg-white py-2 px-3 d-flex align-items-center justify-content-between border-bottom">
            <span class="fw-bold text-primary camera-row-num">
                <i class="fa-solid fa-camera me-1"></i> Camera #${container.children.length + 1}
            </span>
            <button type="button" class="btn btn-xs btn-outline-danger remove-camera-btn py-0 px-2" title="Remove this camera">
                <i class="fa-solid fa-trash me-1"></i> Remove
            </button>
        </div>
        <div class="card-body p-3">
            <div class="row g-3">
                <div class="col-lg-6 col-12">
                    <label class="form-label fw-semibold small">
                        Web Link (Live Stream / Embed) <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" name="cameras[${newIndex}][web_link]" 
                           placeholder="https://example.com/live or stream URL" required>
                    <div class="form-text small">Live stream web link (Required).</div>
                </div>

                <div class="col-lg-6 col-12">
                    <label class="form-label fw-semibold small">
                        Camera Title / Name <span class="text-muted fw-normal">(Optional)</span>
                    </label>
                    <input type="text" class="form-control" name="cameras[${newIndex}][camera_name]" 
                           placeholder="e.g. Main Gate, Tower View (Auto-generated if left empty)">
                </div>

                <div class="col-lg-6 col-12">
                    <label class="form-label fw-semibold small">
                        RTSP URL <span class="text-muted fw-normal">(Optional)</span>
                    </label>
                    <input type="text" class="form-control" name="cameras[${newIndex}][rtsp_url]" 
                           placeholder="rtsp://admin:password@ip:port/stream">
                </div>

                <div class="col-lg-6 col-12">
                    <label class="form-label fw-semibold small">Status</label>
                    <select class="form-select" name="cameras[${newIndex}][status]">
                        <option value="active" selected>Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
        </div>
    `;

    // Attach remove event
    card.querySelector('.remove-camera-btn').addEventListener('click', function() {
        card.remove();
        updateRowNumbers();
    });

    container.appendChild(card);
    updateRowNumbers();

    // Scroll smoothly to the newly added row
    card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

document.getElementById('addMoreCameraBtn').addEventListener('click', addCameraRow);
document.getElementById('addMoreCameraBtnBottom').addEventListener('click', addCameraRow);

// Attach remove listener to the first item (initially hidden)
document.querySelectorAll('.remove-camera-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        btn.closest('.camera-item-card').remove();
        updateRowNumbers();
    });
});

// Initialize Select2 searchable dropdown
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