<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-security-equipments">Equipments</a></li>
                <li class="breadcrumb-item active">Add Equipment</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card__wrapper shadow-sm">
                <div class="card__title-wrap mb-4">
                    <h5 class="card__title mb-0">Equipment Details</h5>
                    <p class="text-muted small mb-0">Register a new security device to an existing system.</p>
                </div>
                <div class="card__body">
                    <form action="<?= $base_url ?>/con-security-equipments/store" method="POST">
                        <div class="row ">
                            <!-- Project -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Project <span class="text-danger">*</span></label>
                                <select name="project_id" class="form-select select2" required>
                                    <option value="" selected disabled>Select Project</option>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?= $project['id'] ?>">
                                            <?= htmlspecialchars($project['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Parent Security System -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Security System <span class="text-danger">*</span></label>
                                <select name="security_system_id" id="security_system_id" class="form-select select2" required>
                                    <option value="" selected disabled>Select System</option>
                                    <?php foreach ($systems as $sys): ?>
                                        <option value="<?= $sys['id'] ?>" data-type="<?= htmlspecialchars($sys['system_type']) ?>">
                                            System #<?= $sys['id'] ?> - <?= htmlspecialchars($sys['system_type']) ?> (<?= htmlspecialchars($sys['project_name']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="system_type" id="system_type" value="">
                            </div>

                            <!-- Equipment Name -->
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-semibold">Equipment Name / Model <span class="text-danger">*</span></label>
                                <input type="text" name="equipment_name" class="form-control" placeholder="e.g. Hikvision 4MP Dome Camera" required>
                            </div>

                            <!-- Serial & MAC -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Serial Number</label>
                                <input type="text" name="serial_number" class="form-control" placeholder="S/N">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">MAC Address</label>
                                <input type="text" name="mac_address" class="form-control" placeholder="00:1A:2B:3C:4D:5E">
                            </div>

                            <!-- Location Area -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Installation Area</label>
                                <input type="text" name="location_area" class="form-control" placeholder="e.g. Ground Floor Lobby">
                            </div>

                            <!-- Warranty Expiry -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Warranty Expiry</label>
                                <input type="date" name="warranty_expiry" class="form-control">
                            </div>

                            <!-- Status -->
                            <div class="col-md-6 mb-4">
                                <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    <option value="Good" selected>Good</option>
                                    <option value="Damaged">Damaged</option>
                                    <option value="Sent to Repair">Sent to Repair</option>
                                    <option value="Replaced">Replaced</option>
                                </select>
                            </div>

                            <div class="col-12 text-end">
                                <a href="<?= $base_url ?>/con-security-equipments" class="btn btn-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i> Save Equipment
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $('#security_system_id').on('change', function() {
        var selectedType = $(this).find('option:selected').data('type');
        $('#system_type').val(selectedType);
    });
});
</script>
