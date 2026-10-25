<?php
$page_title = 'HR ATS - Candidate Trash';
$baseUrl = $GLOBALS['base_url'];
?>

<div class="container-fluid py-3 px-4">
    <!-- Header Section -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                <i class="fa-solid fa-trash-can text-danger"></i>
                Candidate Trash / Recycle Bin
            </h4>
            <p class="text-muted small mb-0">Restore accidentally deleted candidates or delete them permanently from the system.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 justify-content-end ms-auto">
            <a href="<?= $baseUrl ?>/ats-tracker" class="btn btn-outline-secondary btn-sm px-3 shadow-sm d-inline-flex align-items-center gap-1">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Candidate Tracker
            </a>
            <?php if (!empty($candidates)): ?>
                <button type="button" class="btn btn-danger btn-sm px-3 shadow-sm d-inline-flex align-items-center gap-1" onclick="emptyEntireTrash()">
                    <i class="fa-solid fa-fire me-1"></i> Empty Trash
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="card border-0 shadow-sm rounded-3 mb-3 bg-white" style="border: 1px solid #e2e8f0 !important;">
        <div class="card-body p-3">
            <form method="GET" action="<?= $baseUrl ?>/ats-trash" class="row g-2 align-items-center">
                <div class="col-12 col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0" style="border-color: #cbd5e1;"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" name="q" class="form-control form-control-sm border-start-0" placeholder="Search deleted candidate by name, phone, email..." value="<?= htmlspecialchars($search ?? '') ?>" style="height: 34px; border-color: #cbd5e1;">
                    </div>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm px-3 d-inline-flex align-items-center gap-1" style="height: 34px;">
                        <i class="fa-solid fa-magnifying-glass"></i> Search
                    </button>
                    <?php if (!empty($search)): ?>
                        <a href="<?= $baseUrl ?>/ats-trash" class="btn btn-outline-secondary btn-sm px-2 text-decoration-none" style="height: 34px; line-height: 22px;">Clear</a>
                    <?php endif; ?>
                </div>
                <div class="col text-end">
                    <span class="text-muted small">
                        Showing <strong><?= count($candidates) ?></strong> of <strong><?= $total ?></strong> deleted record(s)
                    </span>
                </div>
            </form>
        </div>
    </div>

    <!-- Batch Actions Bar (Visible when rows selected) -->
    <div id="batchActionBar" class="alert alert-secondary py-2 px-3 mb-2 rounded-3 d-none align-items-center justify-content-between shadow-sm">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-danger rounded-pill px-2" id="selectedCountBadge">0</span>
            <span class="small fw-semibold text-dark">record(s) selected</span>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-success btn-sm py-1 shadow-sm d-inline-flex align-items-center gap-1" onclick="executeBulkRestore()">
                <i class="fa-solid fa-rotate-left"></i> Restore Selected
            </button>
            <button type="button" class="btn btn-danger btn-sm py-1 shadow-sm d-inline-flex align-items-center gap-1" onclick="executeBulkForceDelete()">
                <i class="fa-solid fa-trash-can"></i> Permanently Delete Selected
            </button>
        </div>
    </div>

    <!-- Trash Candidates Table -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden bg-white" style="border: 1px solid #e2e8f0 !important;">
        <?php if (empty($candidates)): ?>
            <div class="text-center py-5">
                <div class="mb-3">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 70px; height: 70px; background: rgba(16, 185, 129, 0.1); color: #10b981;">
                        <i class="fa-solid fa-check fs-2"></i>
                    </div>
                </div>
                <h5 class="fw-bold text-dark">Trash is Empty</h5>
                <p class="text-muted small mb-3">No candidates are currently in the trash.</p>
                <a href="<?= $baseUrl ?>/ats-tracker" class="btn btn-primary btn-sm px-4 shadow-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> Return to Candidate Tracker
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive" style="max-height: 650px;">
                <table class="table table-hover align-middle mb-0" id="trashTable" style="font-size: 0.85rem;">
                    <thead class="table-light sticky-top shadow-sm" style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.03em;">
                        <tr class="text-secondary">
                            <th class="text-center px-2" style="width: 35px;">
                                <input type="checkbox" class="form-check-input" id="selectAllCheckbox" onchange="toggleSelectAll(this)">
                            </th>
                            <th class="px-2" style="width: 40px;">#</th>
                            <th style="min-width: 220px;">Candidate Details</th>
                            <th style="min-width: 170px;">Position & Dept</th>
                            <th style="min-width: 140px;">Original Status</th>
                            <th style="min-width: 150px;">Deleted At</th>
                            <th class="text-end px-3" style="width: 160px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $startIndex = ($currentPage - 1) * $perPage;
                        foreach ($candidates as $idx => $c): 
                            $rowNum = $startIndex + $idx + 1;
                        ?>
                            <tr id="trashRow_<?= $c['id'] ?>">
                                <!-- Checkbox -->
                                <td class="text-center px-2">
                                    <input type="checkbox" class="form-check-input row-select-box" value="<?= $c['id'] ?>" onchange="handleRowSelect()">
                                </td>
                                <!-- Row # -->
                                <td class="text-muted px-2 small"><?= $rowNum ?></td>

                                <!-- Candidate Details -->
                                <td>
                                    <div class="fw-bold text-dark mb-1"><?= htmlspecialchars($c['name']) ?></div>
                                    <div class="d-flex align-items-center gap-2 small text-muted">
                                        <i class="fa-solid fa-phone text-secondary"></i>
                                        <span><?= htmlspecialchars($c['phone']) ?></span>
                                    </div>
                                    <?php if (!empty($c['email'])): ?>
                                        <div class="small text-muted mt-1 text-truncate" style="max-width: 200px;">
                                            <i class="fa-regular fa-envelope me-1"></i><?= htmlspecialchars($c['email']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Position & Dept -->
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1 mb-1 d-inline-block">
                                        <?= htmlspecialchars($c['position']) ?>
                                    </span>
                                    <div class="small text-muted"><?= htmlspecialchars($c['department']) ?></div>
                                </td>

                                <!-- Original Status -->
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                                        <?= htmlspecialchars($c['invitation_status'] ?? 'Pending') ?>
                                    </span>
                                    <?php if (!empty($c['interview_date'])): ?>
                                        <div class="text-muted small mt-1">
                                            <i class="fa-regular fa-calendar me-1"></i><?= date('d M Y', strtotime($c['interview_date'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Deleted At -->
                                <td>
                                    <?php if (!empty($c['deleted_at'])): ?>
                                        <div class="fw-semibold text-danger small">
                                            <i class="fa-regular fa-clock me-1"></i><?= date('d M Y, h:i A', strtotime($c['deleted_at'])) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Actions -->
                                <td class="text-end px-3">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-success btn-sm px-2 d-inline-flex align-items-center gap-1" onclick="restoreCandidate(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['name'])) ?>')" title="Restore Candidate">
                                            <i class="fa-solid fa-rotate-left"></i> Restore
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm px-2 d-inline-flex align-items-center gap-1" onclick="forceDeleteCandidate(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['name'])) ?>')" title="Delete Permanently">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer bg-white border-top py-2 px-3 d-flex flex-wrap justify-content-between align-items-center">
                    <span class="text-muted small">
                        Page <?= $currentPage ?> of <?= $totalPages ?> (Total <?= $total ?> candidates)
                    </span>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <?php if ($currentPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= $baseUrl ?>/ats-trash?page=<?= $currentPage - 1 ?>&q=<?= urlencode($search) ?>">Prev</a>
                                </li>
                            <?php endif; ?>
                            <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                                <li class="page-item <?= ($i === $currentPage) ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= $baseUrl ?>/ats-trash?page=<?= $i ?>&q=<?= urlencode($search) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <?php if ($currentPage < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= $baseUrl ?>/ats-trash?page=<?= $currentPage + 1 ?>&q=<?= urlencode($search) ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Row Selection & Batch Actions
function toggleSelectAll(master) {
    const boxes = document.querySelectorAll('.row-select-box');
    boxes.forEach(b => b.checked = master.checked);
    handleRowSelect();
}

function handleRowSelect() {
    const selected = document.querySelectorAll('.row-select-box:checked');
    const bar = document.getElementById('batchActionBar');
    const badge = document.getElementById('selectedCountBadge');
    
    if (selected.length > 0) {
        bar.classList.remove('d-none');
        bar.classList.add('d-flex');
        badge.textContent = selected.length;
    } else {
        bar.classList.add('d-none');
        bar.classList.remove('d-flex');
    }
}

function getSelectedIds() {
    const selected = document.querySelectorAll('.row-select-box:checked');
    return Array.from(selected).map(cb => cb.value);
}

// Single Candidate Restore
function restoreCandidate(id, name) {
    Swal.fire({
        title: 'Restore Candidate?',
        text: `Do you want to restore candidate "${name}" back to the Candidate Tracker?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fa-solid fa-rotate-left me-1"></i> Yes, Restore',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('id', id);

            fetch('<?= $baseUrl ?>/api/ats/candidate-restore', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Restored!',
                        text: res.message,
                        timer: 1200,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error', res.message || 'Failed to restore candidate', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error', 'Network connection issue', 'error');
            });
        }
    });
}

// Bulk Candidate Restore
function executeBulkRestore() {
    const ids = getSelectedIds();
    if (ids.length === 0) return;

    Swal.fire({
        title: `Restore ${ids.length} Candidate(s)?`,
        text: 'Selected candidates will be moved back to the active Candidate Tracker.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fa-solid fa-rotate-left me-1"></i> Yes, Restore All',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('ids', ids.join(','));

            fetch('<?= $baseUrl ?>/api/ats/bulk-restore', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Restored!',
                        text: res.message,
                        timer: 1200,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error', res.message || 'Failed to restore candidates', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error', 'Network connection issue', 'error');
            });
        }
    });
}

// Single Candidate Force Delete (Hard Delete)
function forceDeleteCandidate(id, name) {
    Swal.fire({
        title: 'Delete Permanently?',
        text: `Candidate "${name}" will be PERMANENTLY deleted from the system along with any attached CV file. This action cannot be reversed!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fa-solid fa-trash-can me-1"></i> Permanently Delete',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('id', id);

            fetch('<?= $baseUrl ?>/api/ats/candidate-force-delete', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Permanently Deleted!',
                        text: res.message,
                        timer: 1200,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error', res.message || 'Failed to permanently delete candidate', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error', 'Network connection issue', 'error');
            });
        }
    });
}

// Bulk Force Delete Candidates (Hard Delete)
function executeBulkForceDelete() {
    const ids = getSelectedIds();
    if (ids.length === 0) return;

    Swal.fire({
        title: `Permanently Delete ${ids.length} Candidate(s)?`,
        text: 'This will PERMANENTLY delete the selected candidate records and their attached CV files from the database. This action CANNOT be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fa-solid fa-trash-can me-1"></i> Yes, Permanently Delete All',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('ids', ids.join(','));

            fetch('<?= $baseUrl ?>/api/ats/bulk-force-delete', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Permanently Deleted!',
                        text: res.message,
                        timer: 1200,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error', res.message || 'Failed to delete candidates', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error', 'Network connection issue', 'error');
            });
        }
    });
}

// Empty Entire Trash
function emptyEntireTrash() {
    Swal.fire({
        title: 'Empty Entire Trash?',
        text: 'Are you sure you want to permanently delete ALL candidates and CV files in the trash? This cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fa-solid fa-fire me-1"></i> Yes, Empty Trash',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('<?= $baseUrl ?>/api/ats/empty-trash', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Trash Emptied!',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error', res.message || 'Failed to empty trash', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error', 'Network connection issue', 'error');
            });
        }
    });
}
</script>
