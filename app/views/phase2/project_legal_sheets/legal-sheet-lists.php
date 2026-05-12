

<div class="content-wrapper">
    <div class="content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0 text-dark font-weight-bold">
                    <i class="fal fa-file-contract me-2" style="color:var(--tphl-blue)"></i>
                    Project Legal Sheets
                </h4>
                <a href="<?= $GLOBALS['base_url'] ?>/create-legal-sheet" class="btn btn-primary" style="border-radius:8px">
                    <i class="fal fa-plus-circle me-1"></i> Add Legal Sheet
                </a>
            </div>

            <!-- Table Card -->
            <div class="card shadow-sm border-0" style="border-radius:12px; overflow:hidden">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 custom-table multiple-table">
                            <thead class="bg-light">
                                <tr>
                                    <th class="text-secondary text-uppercase font-weight-bold" style="font-size:12px; padding:15px">ID</th>
                                    <th class="text-secondary text-uppercase font-weight-bold" style="font-size:12px; padding:15px">Project</th>
                                    <th class="text-secondary text-uppercase font-weight-bold" style="font-size:12px; padding:15px">Sheet Type</th>
                                    <th class="text-secondary text-uppercase font-weight-bold" style="font-size:12px; padding:15px">Title & Version</th>
                                    <th class="text-secondary text-uppercase font-weight-bold" style="font-size:12px; padding:15px">Submitted By</th>
                                    <th class="text-secondary text-uppercase font-weight-bold text-center" style="font-size:12px; padding:15px">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($sheets)): ?>
                                    <?php foreach ($sheets as $s): ?>
                                        <tr>
                                            <td class="text-muted" style="padding:15px">#<?= $s['id'] ?></td>
                                            <td class="font-weight-bold text-dark" style="padding:15px">
                                                <i class="fal fa-building text-secondary me-1"></i> <?= htmlspecialchars($s['project_name']) ?>
                                            </td>
                                            <td style="padding:15px">
                                                <span class="badge" style="background:rgba(99,102,241,0.1); color:#6366f1; padding:5px 10px; border-radius:6px; font-weight:600">
                                                    <?= htmlspecialchars($s['sheet_type']) ?>
                                                </span>
                                            </td>
                                            <td style="padding:15px">
                                                <div class="font-weight-bold text-dark"><?= htmlspecialchars($s['title']) ?></div>
                                                <div style="font-size:12px; color:#6c757d">
                                                    Version: <span class="badge badge-primary border text-muted"><?= htmlspecialchars($s['version']) ?></span>
                                                </div>
                                            </td>
                                            <td style="padding:15px">
                                                <i class="fal fa-user-circle text-muted me-1"></i> <?= htmlspecialchars($s['submitted_by_name']) ?>
                                                <br><small class="text-muted"><?= date('d M Y', strtotime($s['created_at'])) ?></small>
                                            </td>
                                            <td class="text-center" style="padding:15px">
                                                <div class="d-flex justify-content-center gap-2">
                                                    <a href="<?= htmlspecialchars($s['link']) ?>" target="_blank" 
                                                       class="btn btn-sm btn-info text-white" title="View Link" style="border-radius:6px; padding:4px 10px">
                                                        <i class="fal fa-external-link"></i>
                                                    </a>
                                                    <a href="<?= $GLOBALS['base_url'] ?>/edit-legal-sheet/<?= $s['id'] ?>" 
                                                       class="btn btn-sm btn-light border" title="Edit" style="border-radius:6px; padding:4px 10px">
                                                        <i class="fal fa-edit text-primary"></i>
                                                    </a>
                                                    <a href="<?= $GLOBALS['base_url'] ?>/delete-legal-sheet/<?= $s['id'] ?>" 
                                                       class="btn btn-sm btn-light border" title="Delete" 
                                                       onclick="return confirm('Are you sure you want to delete this sheet?')"
                                                       style="border-radius:6px; padding:4px 10px">
                                                        <i class="fal fa-trash-alt text-danger"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <div style="font-size:40px; margin-bottom:10px; opacity:0.3"><i class="fal fa-folder-open"></i></div>
                                            No legal sheets found.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>


