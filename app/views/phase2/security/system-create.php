<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-security-systems">Security Systems</a></li>
                <li class="breadcrumb-item active">Add System</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card__wrapper shadow-sm">
                <div class="card__title-wrap mb-4">
                    <h5 class="card__title mb-0">Security System Details</h5>
                    <p class="text-muted small mb-0">Register a new security and IT infrastructure system to a project.</p>
                </div>
                <div class="card__body">
                    <form action="<?= $base_url ?>/con-security-systems/store" method="POST">
                        <div class="row ">
                            <!-- Project -->
                            <div class="col-md-12 mb-3">
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

                            <!-- System Type -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">System Type <span class="text-danger">*</span></label>
                                <select name="system_type" class="form-select select2" required>
                                    <option value="" selected disabled>Select Type</option>
                                    <option value="CCTV">CCTV Surveillance System</option>
                                    <option value="Access Control">Access Control & Biometrics</option>
                                    <option value="Intercom">Intercom & IP Phone</option>
                                    <option value="BMS">Building Management System (BMS)</option>
                                    <option value="Fire Alarm">Fire Alarm & Detection</option>
                                </select>
                            </div>

                            <!-- Vendor ID (Optional) -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Vendor (Optional)</label>
                                <input type="number" name="vendor_id" class="form-control" placeholder="Vendor ID">
                                <small class="text-muted">Enter vendor ID if applicable</small>
                            </div>

                            <!-- Installation Date -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Installation Date</label>
                                <input type="date" name="installation_date" class="form-control">
                            </div>

                            <!-- Status -->
                            <div class="col-md-6 mb-4">
                                <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    <option value="Active" selected>Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="Under Maintenance">Under Maintenance</option>
                                </select>
                            </div>

                            <div class="col-12 text-end">
                                <a href="<?= $base_url ?>/con-security-systems" class="btn btn-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i> Save System
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
