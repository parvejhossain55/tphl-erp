<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-security-incidents">Incidents</a></li>
                <li class="breadcrumb-item active">Report Incident</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card__wrapper shadow-sm border-top border-danger border-3">
                <div class="card__title-wrap mb-4">
                    <h5 class="card__title text-danger mb-0"><i class="bi bi-exclamation-octagon me-2"></i> Report System Incident</h5>
                    <p class="text-muted small mb-0">Log a new maintenance request or report a security equipment failure.</p>
                </div>
                <div class="card__body">
                    <form action="<?= $base_url ?>/con-security-incidents/store" method="POST">
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

                            <!-- Priority -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Issue Priority <span class="text-danger">*</span></label>
                                <select name="priority" class="form-select" required>
                                    <option value="Low">Low</option>
                                    <option value="Medium" selected>Medium</option>
                                    <option value="High">High</option>
                                    <option value="Critical">Critical (Immediate Action Required)</option>
                                </select>
                            </div>

                            <!-- Security System -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Security System</label>
                                <select name="security_system_id" class="form-select select2">
                                    <option value="" selected>General Issue / Unknown</option>
                                    <?php foreach ($systems as $sys): ?>
                                        <option value="<?= $sys['id'] ?>">
                                            <?= htmlspecialchars($sys['system_type']) ?> (<?= htmlspecialchars($sys['project_name']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Equipment -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Specific Equipment</label>
                                <select name="equipment_id" class="form-select select2">
                                    <option value="" selected>General / Not Applicable</option>
                                    <?php foreach ($equipments as $eq): ?>
                                        <option value="<?= $eq['id'] ?>">
                                            <?= htmlspecialchars($eq['equipment_name']) ?> (<?= htmlspecialchars($eq['system_type']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Select specific equipment if the issue is isolated.</small>
                            </div>

                            <!-- Issue Title -->
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-semibold">Issue Title <span class="text-danger">*</span></label>
                                <input type="text" name="issue_title" class="form-control" placeholder="e.g. CCTV Camera #3 Offline" required>
                            </div>

                            <!-- Description -->
                            <div class="col-md-12 mb-4">
                                <label class="form-label fw-semibold">Detailed Description <span class="text-danger">*</span></label>
                                <textarea name="description" class="form-control" rows="4" placeholder="Describe the problem, when it was noticed, and any troubleshooting steps taken..." required></textarea>
                            </div>

                            <div class="col-12 text-end">
                                <a href="<?= $base_url ?>/con-security-incidents" class="btn btn-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-danger">
                                    <i class="bi bi-send me-1"></i> Report Incident
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
