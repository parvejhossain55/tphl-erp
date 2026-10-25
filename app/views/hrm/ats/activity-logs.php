<?php
$page_title = 'HR ATS - Activity Logs & Audit Trail';
$baseUrl = $GLOBALS['base_url'];

// Relative time helper
function atsTimeAgo($datetime) {
    if (empty($datetime)) return 'N/A';
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('d M Y', $time);
}

// Action badge helper
function atsActionBadge($actionType) {
    $map = [
        'candidate_create' => ['bg' => 'bg-success', 'icon' => 'fa-user-plus', 'label' => 'Candidate Added'],
        'candidate_edit'   => ['bg' => 'bg-primary', 'icon' => 'fa-user-pen', 'label' => 'Candidate Edited'],
        'inline_cell_edit' => ['bg' => 'bg-info text-dark', 'icon' => 'fa-pen-to-square', 'label' => 'Inline Edit'],
        'soft_delete'      => ['bg' => 'bg-warning text-dark', 'icon' => 'fa-trash-can', 'label' => 'Moved to Trash'],
        'restore'          => ['bg' => 'bg-success', 'icon' => 'fa-rotate-left', 'label' => 'Restored'],
        'force_delete'     => ['bg' => 'bg-danger', 'icon' => 'fa-trash-xmark', 'label' => 'Permanent Delete'],
        'bulk_trash'       => ['bg' => 'bg-warning text-dark', 'icon' => 'fa-trash-arrow-up', 'label' => 'Bulk Trash'],
        'bulk_edit'        => ['bg' => 'bg-info text-dark', 'icon' => 'fa-layer-group', 'label' => 'Bulk Update'],
        'bulk_restore'     => ['bg' => 'bg-success', 'icon' => 'fa-rotate-left', 'label' => 'Bulk Restore'],
        'bulk_force_delete'=> ['bg' => 'bg-danger', 'icon' => 'fa-fire', 'label' => 'Bulk Force Delete'],
        'empty_trash'      => ['bg' => 'bg-danger', 'icon' => 'fa-dumpster-fire', 'label' => 'Emptied Trash'],
        'cv_upload'        => ['bg' => 'bg-purple text-white', 'icon' => 'fa-file-arrow-up', 'label' => 'CV Upload'],
        'batch_csv_import' => ['bg' => 'bg-teal text-white', 'icon' => 'fa-file-csv', 'label' => 'CSV Import'],
        'settings_change'  => ['bg' => 'bg-secondary', 'icon' => 'fa-sliders', 'label' => 'Settings Edit'],
        'page_view'        => ['bg' => 'bg-light text-muted border', 'icon' => 'fa-eye', 'label' => 'Page View'],
    ];

    $cfg = $map[$actionType] ?? ['bg' => 'bg-secondary', 'icon' => 'fa-bolt', 'label' => ucfirst(str_replace('_', ' ', $actionType))];
    return '<span class="badge ' . $cfg['bg'] . ' px-2 py-1 shadow-none fw-medium d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;">'
        . '<i class="fa-solid ' . $cfg['icon'] . '"></i> ' . htmlspecialchars($cfg['label'])
        . '</span>';
}
?>

<div class="container-fluid py-3 px-4">
    <!-- Header Section -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-primary"></i>
                ATS Activity Log & Audit Trail
            </h4>
            <p class="text-muted small mb-0">Track all system activities, candidate lifecycle updates, and user modifications in real time.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 justify-content-end ms-auto">
            <a href="<?= $baseUrl ?>/ats-tracker" class="btn btn-outline-secondary btn-sm px-3 shadow-sm d-inline-flex align-items-center gap-1">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Tracker
            </a>
            <a href="<?= $baseUrl ?>/ats-trash" class="btn btn-outline-danger btn-sm px-3 shadow-sm d-inline-flex align-items-center gap-1">
                <i class="fa-solid fa-trash-can me-1"></i> View Trash
            </a>
        </div>
    </div>

    <!-- Summary Metrics Cards -->
    <div class="row g-3 mb-3">
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white" style="border-left: 4px solid #3b82f6 !important;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Today's Actions</div>
                        <h3 class="fw-bold mb-0 text-primary mt-1"><?= number_format($stats['total_today'] ?? 0) ?></h3>
                    </div>
                    <div class="bg-primary-subtle text-primary p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 46px; height: 46px;">
                        <i class="fa-solid fa-bolt fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white" style="border-left: 4px solid #10b981 !important;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Total Logs Recorded</div>
                        <h3 class="fw-bold mb-0 text-success mt-1"><?= number_format($stats['total_all'] ?? 0) ?></h3>
                    </div>
                    <div class="bg-success-subtle text-success p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 46px; height: 46px;">
                        <i class="fa-solid fa-database fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white" style="border-left: 4px solid #8b5cf6 !important;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Top Active User (Today)</div>
                        <div class="fw-bold text-dark mt-1 text-truncate" style="max-width: 170px;" title="<?= htmlspecialchars($stats['top_user'] ?? 'None') ?>">
                            <?= htmlspecialchars($stats['top_user'] ?? 'None') ?>
                        </div>
                    </div>
                    <div class="bg-purple-subtle text-purple p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 46px; height: 46px; background-color: #ede9fe; color: #8b5cf6;">
                        <i class="fa-solid fa-user-check fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white" style="border-left: 4px solid #f59e0b !important;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Audit Status</div>
                        <div class="fw-bold text-success mt-1 d-flex align-items-center gap-1">
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Active & Logging</span>
                        </div>
                    </div>
                    <div class="bg-warning-subtle text-warning p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 46px; height: 46px;">
                        <i class="fa-solid fa-shield-halved fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-3 bg-white" style="border: 1px solid #e2e8f0 !important;">
        <div class="card-body p-3">
            <form method="GET" action="<?= $baseUrl ?>/ats-activity-logs" class="row g-2 align-items-center">
                <!-- Keyword Search -->
                <div class="col-12 col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0" style="border-color: #cbd5e1;"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" name="q" class="form-control form-control-sm border-start-0" placeholder="Search candidate, action, description..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>" style="height: 34px; border-color: #cbd5e1;">
                    </div>
                </div>

                <!-- User Filter -->
                <div class="col-12 col-sm-6 col-md-2">
                    <select name="user_id" class="form-select form-select-sm" style="height: 34px; border-color: #cbd5e1;">
                        <option value="">-- All Users --</option>
                        <?php foreach ($usersList as $u): ?>
                            <option value="<?= $u['user_id'] ?>" <?= (!empty($filters['user_id']) && (int)$filters['user_id'] === (int)$u['user_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['user_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Action Type Filter -->
                <div class="col-12 col-sm-6 col-md-2">
                    <select name="action_type" class="form-select form-select-sm" style="height: 34px; border-color: #cbd5e1;">
                        <option value="">-- All Actions --</option>
                        <?php foreach ($actionTypes as $at): ?>
                            <option value="<?= $at ?>" <?= (!empty($filters['action_type']) && $filters['action_type'] === $at) ? 'selected' : '' ?>>
                                <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $at))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Date From -->
                <div class="col-6 col-md-2">
                    <input type="date" name="date_from" class="form-control form-control-sm" title="From Date" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>" style="height: 34px; border-color: #cbd5e1;">
                </div>

                <!-- Date To -->
                <div class="col-6 col-md-2">
                    <input type="date" name="date_to" class="form-control form-control-sm" title="To Date" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>" style="height: 34px; border-color: #cbd5e1;">
                </div>

                <!-- Submit & Reset Buttons -->
                <div class="col-12 col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill d-flex align-items-center justify-content-center shadow-none" style="height: 34px;" title="Apply Filters">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                    <a href="<?= $baseUrl ?>/ats-activity-logs" class="btn btn-outline-danger btn-sm flex-fill d-flex align-items-center justify-content-center shadow-none" style="height: 34px;" title="Reset Filters">
                        <i class="fa-solid fa-rotate-right"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Activity Logs Table Card -->
    <div class="card border-0 shadow-sm rounded-3 bg-white" style="border: 1px solid #e2e8f0 !important;">
        <div class="card-header bg-white py-3 px-3 border-bottom d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary rounded-pill px-2"><?= number_format($total) ?></span>
                <span class="fw-semibold text-dark small">Matching Activities Found</span>
            </div>
            <div class="text-muted small">
                Showing page <strong><?= $currentPage ?></strong> of <strong><?= $totalPages ?></strong>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                    <thead class="table-light text-muted small text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                        <tr>
                            <th class="ps-3" style="width: 50px;">#</th>
                            <th style="width: 170px;">Actor / User</th>
                            <th style="width: 140px;">Action</th>
                            <th style="width: 180px;">Candidate</th>
                            <th>Description & Details</th>
                            <th style="width: 110px;">IP Address</th>
                            <th class="text-end pe-3" style="width: 160px;">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-clock-rotate-left fa-3x mb-3 text-secondary opacity-50 d-block"></i>
                                    <h6 class="fw-semibold text-dark">No Activity Logs Found</h6>
                                    <p class="small text-muted mb-0">System events and user actions will be listed here automatically as they occur.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $startSl = ($currentPage - 1) * $perPage;
                            foreach ($logs as $i => $log): 
                                $sl = $startSl + $i + 1;
                                $detailsObj = null;
                                if (!empty($log['details'])) {
                                    $detailsObj = json_decode($log['details'], true);
                                }
                            ?>
                                <tr>
                                    <!-- SL -->
                                    <td class="ps-3 text-muted small"><?= $sl ?></td>

                                    <!-- Actor / User -->
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 30px; height: 30px; font-size: 0.75rem; flex-shrink: 0;">
                                                <?= strtoupper(substr($log['user_name'] ?? 'U', 0, 1)) ?>
                                            </div>
                                            <div class="lh-sm text-truncate" style="max-width: 130px;">
                                                <div class="fw-semibold text-dark text-truncate" title="<?= htmlspecialchars($log['user_name'] ?? 'System') ?>">
                                                    <?= htmlspecialchars($log['user_name'] ?? 'System') ?>
                                                </div>
                                                <?php if (!empty($log['user_email'])): ?>
                                                    <div class="text-muted text-truncate" style="font-size: 0.7rem;" title="<?= htmlspecialchars($log['user_email']) ?>">
                                                        <?= htmlspecialchars($log['user_email']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Action Badge -->
                                    <td>
                                        <?= atsActionBadge($log['action_type']) ?>
                                    </td>

                                    <!-- Candidate -->
                                    <td>
                                        <?php if (!empty($log['candidate_name'])): ?>
                                            <div class="fw-medium text-dark text-truncate" style="max-width: 170px;" title="<?= htmlspecialchars($log['candidate_name']) ?>">
                                                <a href="<?= $baseUrl ?>/ats-tracker?search=<?= urlencode($log['candidate_name']) ?>" class="text-decoration-none text-primary" target="_blank">
                                                    <i class="fa-solid fa-user me-1 text-muted" style="font-size: 0.75rem;"></i><?= htmlspecialchars($log['candidate_name']) ?>
                                                </a>
                                            </div>
                                            <?php if (!empty($log['candidate_id'])): ?>
                                                <span class="text-muted" style="font-size: 0.7rem;">ID #<?= $log['candidate_id'] ?></span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Description & Details -->
                                    <td>
                                        <div class="text-dark lh-sm">
                                            <?= htmlspecialchars($log['description']) ?>
                                        </div>
                                        <?php if (!empty($detailsObj)): ?>
                                            <div class="mt-1">
                                                <button type="button" class="btn btn-outline-secondary btn-xs py-0 px-1 border-0 text-muted" style="font-size: 0.7rem;" data-bs-toggle="collapse" data-bs-target="#detailsCollapse<?= $log['id'] ?>">
                                                    <i class="fa-solid fa-code me-1"></i> View Snapshot <i class="fa-solid fa-chevron-down ms-1" style="font-size: 0.6rem;"></i>
                                                </button>
                                                <div class="collapse mt-1" id="detailsCollapse<?= $log['id'] ?>">
                                                    <pre class="bg-light p-2 rounded small mb-0 text-muted border" style="font-size: 0.7rem; max-height: 120px; overflow-y: auto;"><?= htmlspecialchars(json_encode($detailsObj, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- IP Address -->
                                    <td>
                                        <span class="text-muted font-monospace" style="font-size: 0.75rem;" title="<?= htmlspecialchars($log['user_agent'] ?? '') ?>">
                                            <i class="fa-solid fa-network-wired me-1 opacity-50" style="font-size: 0.65rem;"></i><?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1') ?>
                                        </span>
                                    </td>

                                    <!-- Timestamp -->
                                    <td class="text-end pe-3">
                                        <div class="fw-medium text-dark" style="font-size: 0.78rem;">
                                            <?= date('d M Y, h:i A', strtotime($log['created_at'])) ?>
                                        </div>
                                        <div class="text-muted" style="font-size: 0.7rem;">
                                            <i class="fa-regular fa-clock me-1"></i><?= atsTimeAgo($log['created_at']) ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination Footer -->
        <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-white py-2 px-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="text-muted small">
                    Page <?= $currentPage ?> of <?= $totalPages ?> (Total <?= number_format($total) ?> records)
                </div>
                <nav aria-label="Logs pagination">
                    <ul class="pagination pagination-sm mb-0">
                        <?php
                        $queryParams = $_GET;
                        // Previous Page
                        $prevPage = max(1, $currentPage - 1);
                        $queryParams['page'] = $prevPage;
                        $prevUrl = $baseUrl . '/ats-activity-logs?' . http_build_query($queryParams);
                        ?>
                        <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= $prevUrl ?>"><i class="fa-solid fa-chevron-left"></i></a>
                        </li>

                        <?php
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($totalPages, $currentPage + 2);

                        if ($startPage > 1):
                            $queryParams['page'] = 1;
                        ?>
                            <li class="page-item"><a class="page-link" href="<?= $baseUrl ?>/ats-activity-logs?<?= http_build_query($queryParams) ?>">1</a></li>
                            <?php if ($startPage > 2): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($p = $startPage; $p <= $endPage; $p++): 
                            $queryParams['page'] = $p;
                        ?>
                            <li class="page-item <?= ($p === $currentPage) ? 'active' : '' ?>">
                                <a class="page-link" href="<?= $baseUrl ?>/ats-activity-logs?<?= http_build_query($queryParams) ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($endPage < $totalPages): 
                            $queryParams['page'] = $totalPages;
                        ?>
                            <?php if ($endPage < $totalPages - 1): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                            <li class="page-item"><a class="page-link" href="<?= $baseUrl ?>/ats-activity-logs?<?= http_build_query($queryParams) ?>"><?= $totalPages ?></a></li>
                        <?php endif; ?>

                        <?php
                        // Next Page
                        $nextPage = min($totalPages, $currentPage + 1);
                        $queryParams['page'] = $nextPage;
                        $nextUrl = $baseUrl . '/ats-activity-logs?' . http_build_query($queryParams);
                        ?>
                        <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= $nextUrl ?>"><i class="fa-solid fa-chevron-right"></i></a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>
