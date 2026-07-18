<?php
$page_title = "ERP Implement Tracker Dashboard";
?>
<div class="container-fluid py-4">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h2 class="mb-0">ERP Implement Tracker</h2>
            <p class="text-muted mb-0">Monitor department adoption and action items</p>
        </div>
        <div class="col-md-6 text-end">
            <div class="d-flex gap-2 justify-content-end align-items-center">
                <?php if ($hasManagementAccess): ?>
                    <a href="<?= $GLOBALS['base_url'] ?>/erp-tracker/champions" class="btn btn-outline-primary">
                        <i class="fas fa-users-cog me-1"></i> Manage Champions
                    </a>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createSessionModal">
                        <i class="fas fa-plus"></i> Create Weekly Session
                    </button>
                <?php endif; ?>
                <a href="<?= $base_url ?>/erp-tracker/form" class="btn btn-outline-secondary">My Department Form</a>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Active Session Overview -->
    <?php if ($activeSession): ?>
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 text-primary">
                        <i class="fas fa-calendar-check me-2"></i><?= isset($_GET['session_id']) ? 'Session #' . $activeSession['id'] : 'Active Session' ?>: <?= htmlspecialchars($activeSession['topic']) ?>
                    </h5>
                    <small class="text-muted">Created Date: <?= date('d M Y', strtotime($activeSession['meeting_date'])) ?></small>
                </div>
                <?php if (isset($_GET['session_id'])): ?>
                    <a href="<?= $base_url ?>/erp-tracker" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to Active Tracker</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-3">
                        <div class="p-3 bg-light rounded text-center">
                            <h3 class="mb-1 text-dark"><?= $stats['departments_submitted'] ?> / <?= $stats['total_departments'] ?></h3>
                            <span class="text-muted small text-uppercase fw-bold">Depts Submitted</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 rounded text-center" style="background-color: rgba(25, 135, 84, 0.1); border: 1px solid rgba(25, 135, 84, 0.2);">
                            <h3 class="mb-1 text-success"><?= $stats['completed_actions'] ?></h3>
                            <span class="text-success small text-uppercase fw-bold">Completed Actions</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 rounded text-center" style="background-color: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.2);">
                            <h3 class="mb-1 text-warning"><?= $stats['pending_actions'] ?></h3>
                            <span class="text-warning small text-uppercase fw-bold">Pending Actions</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 rounded text-center" style="background-color: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.2);">
                            <h3 class="mb-1 text-danger"><?= $stats['overdue_actions'] ?></h3>
                            <span class="text-danger small text-uppercase fw-bold">Overdue Actions</span>
                        </div>
                    </div>
                </div>

                <?php if ($hasManagementAccess): ?>
                    <div class="mt-4 text-end">
                        <a href="<?= $base_url ?>/erp-tracker/session/close/<?= $activeSession['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to manually close this session?');">
                            Close Session Early
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($hasManagementAccess): ?>
            <!-- Department Rankings -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-4">
                    <h5 class="mb-0"><i class="fas fa-chart-line text-primary me-2"></i>Department Rankings</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Rank</th>
                                    <th>Department</th>
                                    <th>Total Actions</th>
                                    <th>Completed</th>
                                    <th>Completion %</th>
                                    <th>Progress Bar</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rankings as $index => $rank): ?>
                                    <tr>
                                        <td><strong>#<?= $index + 1 ?></strong></td>
                                        <td><?= htmlspecialchars($rank['name']) ?></td>
                                        <td><?= $rank['total_actions'] ?></td>
                                        <td><?= $rank['completed_actions'] ?></td>
                                        <td><span class="badge bg-<?= $rank['completion_pct'] >= 80 ? 'success' : ($rank['completion_pct'] >= 50 ? 'warning' : 'danger') ?>"><?= $rank['completion_pct'] ?>%</span></td>
                                        <td style="width: 25%;">
                                            <div class="progress" style="height: 10px;">
                                                <div class="progress-bar bg-<?= $rank['completion_pct'] >= 80 ? 'success' : ($rank['completion_pct'] >= 50 ? 'warning' : 'danger') ?>" role="progressbar" style="width: <?= $rank['completion_pct'] ?>%;"></div>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="<?= $base_url ?>/erp-tracker/view/<?= $rank['id'] ?><?= isset($_GET['session_id']) ? '?session_id=' . $_GET['session_id'] : '' ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye me-1"></i> View Details</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($rankings)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No data available yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i> No active weekly tracking session is open.
            <?php if ($hasManagementAccess): ?>
                Click "Create Weekly Session" to start a new one.
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <!-- All Sessions History -->
    <div class="card mt-4 border-0 shadow-sm">
        <div class="card-header bg-white border-0 pt-4">
            <h5 class="mb-0"><i class="fas fa-history text-secondary me-2"></i>Previous Sessions</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Meeting Date</th>
                            <th>Topic</th>
                            <th>Status</th>
                            <th>Depts Submitted</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allSessions as $sess): ?>
                            <tr>
                                <td>#<?= $sess['id'] ?></td>
                                <td><?= date('d M Y', strtotime($sess['meeting_date'])) ?></td>
                                <td><?= htmlspecialchars($sess['topic']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $sess['status'] === 'Open' ? 'success' : 'secondary' ?>">
                                        <?= $sess['status'] ?>
                                    </span>
                                </td>
                                <td><?= $sess['submitted_departments'] ?></td>
                                <td>
                                    <a href="<?= $base_url ?>/erp-tracker?session_id=<?= $sess['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye me-1"></i> View Dashboard</a>
                                    <?php if ($hasManagementAccess): ?>
                                        <a href="<?= $base_url ?>/erp-tracker/session/delete/<?= $sess['id'] ?>" class="btn btn-sm btn-outline-danger ms-1" onclick="return confirm('Are you sure you want to delete this session entirely? This will delete all tasks and submissions associated with it.');"><i class="fas fa-trash me-1"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Create Session -->
<div class="modal fade" id="createSessionModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= $base_url ?>/erp-tracker/session/create" method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Weekly Session</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Meeting Date</label>
                    <input type="date" name="meeting_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Topic</label>
                    <input type="text" name="topic" class="form-control" placeholder="e.g., Weekly ERP Usage & Targets" required>
                </div>
                <div class="alert alert-warning small">
                    <i class="fas fa-exclamation-triangle"></i> Creating a new session will automatically close any open sessions and carry forward pending tasks.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Session & Notify</button>
            </div>
        </form>
    </div>
</div>
