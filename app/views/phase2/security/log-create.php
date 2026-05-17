<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-security-logs">Daily Logs</a></li>
                <li class="breadcrumb-item active">Submit Log</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card__wrapper shadow-sm">
                <div class="card__title-wrap mb-4">
                    <h5 class="card__title mb-0">Daily Security & IT Log</h5>
                    <p class="text-muted small mb-0">Complete the checklist to report system conditions for today.</p>
                </div>
                <div class="card__body">
                    <form action="<?= $base_url ?>/con-security-logs/store" method="POST">
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

                            <!-- Log Date -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Log Date <span class="text-danger">*</span></label>
                                <input type="date" name="log_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>

                            <div class="col-12 mt-4">
                                <h6 class="fw-bold mb-3 border-bottom pb-2">System Condition Checklist</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>System Type</th>
                                                <th class="text-center" style="width: 150px;">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><i class="bi bi-camera-video me-2 text-primary"></i> CCTV Surveillance System</td>
                                                <td class="text-center">
                                                    <select name="check_cctv" class="form-select form-select-sm">
                                                        <option value="Okay">All Okay</option>
                                                        <option value="Issue">Issue Found</option>
                                                        <option value="Not Installed">Not Installed</option>
                                                    </select>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><i class="bi bi-person-badge me-2 text-success"></i> Access Control & Biometrics</td>
                                                <td class="text-center">
                                                    <select name="check_access" class="form-select form-select-sm">
                                                        <option value="Okay">All Okay</option>
                                                        <option value="Issue">Issue Found</option>
                                                        <option value="Not Installed">Not Installed</option>
                                                    </select>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><i class="bi bi-telephone me-2 text-info"></i> Intercom & IP Phone</td>
                                                <td class="text-center">
                                                    <select name="check_intercom" class="form-select form-select-sm">
                                                        <option value="Okay">All Okay</option>
                                                        <option value="Issue">Issue Found</option>
                                                        <option value="Not Installed">Not Installed</option>
                                                    </select>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><i class="bi bi-building-gear me-2 text-warning"></i> Building Management System (BMS)</td>
                                                <td class="text-center">
                                                    <select name="check_bms" class="form-select form-select-sm">
                                                        <option value="Okay">All Okay</option>
                                                        <option value="Issue">Issue Found</option>
                                                        <option value="Not Installed">Not Installed</option>
                                                    </select>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><i class="bi bi-fire me-2 text-danger"></i> Fire Alarm & Detection</td>
                                                <td class="text-center">
                                                    <select name="check_fire" class="form-select form-select-sm">
                                                        <option value="Okay">All Okay</option>
                                                        <option value="Issue">Issue Found</option>
                                                        <option value="Not Installed">Not Installed</option>
                                                    </select>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Remarks -->
                            <div class="col-md-12 mb-4">
                                <label class="form-label fw-semibold">Remarks / Notes</label>
                                <textarea name="remarks" class="form-control" rows="3" placeholder="Describe any issues found or maintenance done..."></textarea>
                                <small class="text-muted"><i class="bi bi-info-circle me-1"></i> If you mark 'Issue Found' for any system, an Incident ticket will be automatically generated.</small>
                            </div>

                            <div class="col-12 text-end">
                                <a href="<?= $base_url ?>/con-security-logs" class="btn btn-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-send me-1"></i> Submit Log
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
