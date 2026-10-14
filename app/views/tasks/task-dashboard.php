<?php
$page_title = "Your Task";

if (!function_exists('renderTaskDescription')) {
    function renderTaskDescription($desc, $wordLimit = 20, $taskId = 0) {
        if (empty($desc)) return '';

        // Strip HTML tags and convert line breaks cleanly so no <p>, </p>, <br> appears as raw text
        $descWithBreaks = preg_replace('/<\/(p|div|li)>/i', "\n", $desc);
        $descWithBreaks = preg_replace('/<br\s*\/?>/i', "\n", $descWithBreaks);
        $cleanDesc = trim(strip_tags(html_entity_decode($descWithBreaks, ENT_QUOTES, 'UTF-8')));
        $cleanDesc = preg_replace("/\n{3,}/", "\n\n", $cleanDesc);

        if (empty($cleanDesc)) return '';

        $words = preg_split('/\s+/u', $cleanDesc, -1, PREG_SPLIT_NO_EMPTY);
        $totalWords = count($words);

        if ($totalWords <= $wordLimit) {
            return '<div class="description-content">' . nl2br(htmlspecialchars($cleanDesc)) . '</div>';
        }

        $shortText = implode(' ', array_slice($words, 0, $wordLimit));
        $uniqueId = 'desc_' . $taskId . '_' . substr(md5($cleanDesc), 0, 6);

        return '
            <div class="description-content task-desc-container" id="container_' . $uniqueId . '">
                <div class="task-desc-short" id="short_' . $uniqueId . '">
                    ' . nl2br(htmlspecialchars($shortText)) . '...
                    <a href="javascript:void(0)" class="text-success fw-bold text-decoration-none ms-1 d-inline-flex align-items-center" onclick="toggleTaskDesc(\'' . $uniqueId . '\', true)">
                        View More <i class="bi bi-chevron-down ms-1" style="font-size: 11px;"></i>
                    </a>
                </div>
                <div class="task-desc-full" id="full_' . $uniqueId . '" style="display: none;">
                    ' . nl2br(htmlspecialchars($cleanDesc)) . '
                    <a href="javascript:void(0)" class="text-secondary fw-bold text-decoration-none ms-1 d-inline-flex align-items-center" onclick="toggleTaskDesc(\'' . $uniqueId . '\', false)">
                        View Less <i class="bi bi-chevron-up ms-1" style="font-size: 11px;"></i>
                    </a>
                </div>
            </div>
        ';
    }
}
?>
        <!-- Task Count Cards -->
        <div class="row mb-4">
            <div class="col-md-4 col-sm-12 mb-3 mb-md-0">
                <div class="card h-100 shadow-sm border-0" style="background: linear-gradient(135deg, #fff3cd 0%, #fff9e6 100%); border-left: 4px solid #ffc107 !important; cursor: default;">
                    <div class="card-body d-flex justify-content-between align-items-center py-2 px-3">
                        <div>
                            <h6 class="mb-1 text-warning-emphasis fw-bold" style="color: #664d03 !important; font-size: 14px;"><i class="bi bi-calendar2-event me-2"></i>Today's Tasks</h6>
                            <span class="text-muted" style="font-size: 12px;">Tasks due today</span>
                        </div>
                        <span class="badge bg-warning text-dark fs-6 rounded-pill px-2 py-1 fw-bold shadow-sm"><?= $todayTaskCount ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-12 mb-3 mb-md-0">
                <div class="card h-100 shadow-sm border-0" onclick="toggleTaskView('pending')" style="background: linear-gradient(135deg, #e8e6ff 0%, #f3f1ff 100%); border-left: 4px solid #5e4cdc !important; cursor: pointer;">
                    <div class="card-body d-flex justify-content-between align-items-center py-2 px-3">
                        <div>
                            <h6 class="mb-1 text-primary fw-bold" style="color: #493aa6 !important; font-size: 14px;"><i class="bi bi-clipboard-data me-2"></i>Total Pending Tasks</h6>
                            <span class="text-muted" style="font-size: 12px;">All incomplete tasks</span>
                        </div>
                        <span class="badge text-white fs-6 rounded-pill px-2 py-1 fw-bold shadow-sm" style="background-color: #5e4cdc !important;"><?= count($pendingTasks) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-12">
                <div class="card h-100 shadow-sm border-0" onclick="toggleTaskView('completed')" style="background: linear-gradient(135deg, #d1e7dd 0%, #e8f5e9 100%); border-left: 4px solid #198754 !important; cursor: pointer;">
                    <div class="card-body d-flex justify-content-between align-items-center py-2 px-3">
                        <div>
                            <h6 class="mb-1  fw-bold" style="color: #0f5132 !important; font-size: 14px;"><i class="bi bi-check-circle-fill me-2"></i>My Completed Tasks</h6>
                            <span class="text-muted" style="font-size: 12px;">Total completed</span>
                        </div>
                        <span class="badge text-white fs-6 rounded-pill px-2 py-1 fw-bold shadow-sm" style="background-color: #198754 !important;"><?= count($completedTasks) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Task Notification Section -->
        <?php if (!empty($pendingTasks)): ?>
        <div class="tasks-container" id="pending-tasks-view">
                <div class="row ">
                    <?php foreach ($pendingTasks as $task): ?>
                        <?php
                        // Determine status and styling
                        $status = 'pending';
                        $statusBadge = '<span class="badge badge-pending"><i class="bi bi-clock-history me-1"></i>Pending</span>';
                        $cardClass = 'card-pending';

                        if ($task['employee_accept'] == 1) {
                            if ($task['progress'] >= 100) {
                                $status = 'completed';
                                $statusBadge = '<span class="badge badge-completed"><i class="bi bi-check-circle-fill me-1"></i>Completed</span>';
                                $cardClass = 'card-completed';
                            } else {
                                $status = 'in-progress';
                                $statusBadge = '<span class="badge badge-progress"><i class="bi bi-arrow-repeat me-1"></i>In Progress</span>';
                                $cardClass = 'card-progress';
                            }
                        }
                        ?>

                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="card__wrapper task-card <?= $cardClass ?>">
                                <!-- Task Header -->
                                <div class="task-header">
                                    <div class="task-title-section flex-grow-1 me-2">
                                        <?php if (!empty($task['project_name'])): ?>
                                            <div class="mb-1">
                                                <?php if (!empty($task['is_team_project'])): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 rounded-pill" style="font-size: 11px; font-weight: 600;">
                                                        <i class="bi bi-people-fill me-1"></i>Team Project: <?= htmlspecialchars($task['project_name']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5 rounded-pill" style="font-size: 11px; font-weight: 600;">
                                                        <i class="bi bi-folder2 me-1"></i>Project: <?= htmlspecialchars($task['project_name']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="task-icon-wrapper flex-shrink-0">
                                                <i class="bi bi-clipboard-check"></i>
                                            </div>
                                            <div>
                                                <span class="text-muted text-uppercase fw-semibold" style="font-size: 9.5px; letter-spacing: 0.5px; display: block; line-height: 1.2;">Task Title</span>
                                                <h5 class="task-title mb-0" style="font-size: 14.5px; font-weight: 700; color: #1e293b;"><?= htmlspecialchars($task['title']) ?></h5>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="task-actions flex-shrink-0">
                                        <?= $statusBadge ?>
                                        <button class="btn-collapse ms-1" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#task-<?= $task['id'] ?>" aria-expanded="true">
                                            <i class="bi bi-chevron-down"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Collapsible Content -->
                                <div class="collapse show" id="task-<?= $task['id'] ?>">
                                    <!-- Assigned By Info -->
                                    <div class="assigned-section">
                                        <div class="assigned-info-card">
                                            <div class="info-icon">
                                                <i class="bi bi-person-badge"></i>
                                            </div>
                                            <div class="info-content">
                                                <p class="info-label">Assigned by</p>
                                                <p class="info-value"><?= htmlspecialchars($task['assigned_by_name'] ?? 'System') ?></p>
                                                <p class="info-designation"><?= htmlspecialchars($task['assigned_by_designation'] ?? '') ?></p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Description -->
                                    <?php if (!empty($task['description'])): ?>
                                        <div class="task-description-section">
                                            <div class="task-desc-title">
                                                <i class="bi bi-file-text me-1 text-success"></i>Description
                                            </div>
                                            <?= renderTaskDescription($task['description'], 20, $task['id']) ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Date Info -->
                                    <div class="date-info-section">
                                        <div class="date-card">
                                            <div class="date-icon start-icon">
                                                <i class="bi bi-calendar-check"></i>
                                            </div>
                                            <div class="date-details">
                                                <p class="date-label">Start Date</p>
                                                <p class="date-value"><?= !empty($task['start_date']) ? date('d M, Y', strtotime($task['start_date'])) : 'N/A' ?></p>
                                            </div>
                                        </div>
                                        <div class="date-card">
                                            <div class="date-icon deadline-icon">
                                                <i class="bi bi-calendar-x"></i>
                                            </div>
                                            <div class="date-details">
                                                <p class="date-label">Deadline</p>
                                                <p class="date-value"><?= !empty($task['deadline']) ? date('d M, Y', strtotime($task['deadline'])) : 'N/A' ?></p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Progress Bar -->
                                    <div class="progress-section">
                                        <div class="progress-header">
                                            <span class="progress-label">
                                                <i class="bi bi-graph-up me-1"></i>Progress
                                            </span>
                                            <span class="progress-percentage"><?= $task['progress'] ?>%</span>
                                        </div>
                                        <div class="progress-bar-container">
                                            <div class="progress-bar-fill progress-<?= $status ?>"
                                                style="width: <?= $task['progress'] ?>%">
                                                <span class="progress-shine"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Action Form -->
                                    <form action="<?= $GLOBALS['base_url'] ?>/<?= $task['employee_accept'] == 0 ? 'update-task-status' : 'update-task-progress' ?>"
                                        method="POST" enctype="multipart/form-data" class="action-form">
                                        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">

                                        <?php if ($task['employee_accept'] == 0): ?>
                                            <!-- Notice & Accept Button -->
                                            <div class="py-1 px-2 mb-2 rounded bg-light text-muted text-center" style="font-size: 11px;">
                                                <i class="bi bi-info-circle me-1 text-primary"></i>Accept this task to start work
                                            </div>
                                            <button type="submit" name="accept" class="btn btn-accept w-100">
                                                <i class="bi bi-check2-circle me-1"></i>
                                                Accept Task
                                            </button>
                                        <?php else: ?>
                                            <!-- Update Progress Details -->
                                            <div class="row g-2 mb-1">
                                                <div class="col-5">
                                                    <label class="form-label text-dark mb-1" style="font-size: 10.5px; font-weight: 600;"><i class="bi bi-percent me-1"></i>Progress (%)</label>
                                                    <input type="number"
                                                        name="progress"
                                                        min="<?= $task['progress'] ?>"
                                                        max="100"
                                                        value="<?= $task['progress'] ?>"
                                                        class="form-control form-control-sm py-1"
                                                        placeholder="%"
                                                        required>
                                                </div>
                                                <div class="col-7">
                                                    <label class="form-label text-dark mb-1" style="font-size: 10.5px; font-weight: 600;"><i class="bi bi-image me-1"></i>Proof Image</label>
                                                    <input type="file" name="attachment_image" class="form-control form-control-sm py-1" accept="image/*">
                                                </div>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label text-dark mb-1" style="font-size: 10.5px; font-weight: 600;"><i class="bi bi-chat-left-text me-1"></i>Remarks</label>
                                                <input type="text" name="remarks" class="form-control form-control-sm py-1" value="<?= htmlspecialchars($task['remarks'] ?? '') ?>" placeholder="Explain what has been completed..." required>
                                                <?php if (!empty($task['attachment_image'])): ?>
                                                    <div class="mt-1 text-start">
                                                        <a href="<?= $GLOBALS['base_url'] ?>/public/uploads/tasks/<?= $task['attachment_image'] ?>" target="_blank" class="text-primary fs-12 text-decoration-none"><i class="bi bi-eye me-1"></i>View Current Image</a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <button type="submit" class="btn btn-update w-100">
                                                <i class="bi bi-arrow-repeat me-1"></i>
                                                Update Progress
                                            </button>
                                        <?php endif; ?>
                                    </form>

                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php else: ?>
            <div class="empty-state-container" id="pending-tasks-view">
                <div class="empty-state-card">
                    <div class="empty-icon">
                        <i class="bi bi-clipboard-check"></i>
                    </div>
                    <h4 class="empty-title">No Pending Tasks</h4>
                    <p class="empty-description">You're all caught up! No tasks assigned at the moment.</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Completed Tasks Section -->
        <div class="tasks-container" id="completed-tasks-view" style="display: none;">
            <?php if (!empty($completedTasks)): ?>
                <div class="row " id="completed-tasks-list">
                    <?php foreach ($completedTasks as $index => $ctask): ?>
                        <div class="col-lg-4 col-md-6 col-sm-12 completed-task-item" data-page="<?= floor($index / 10) + 1 ?>" style="display: none;">
                            <div class="card__wrapper task-card card-completed">
                                <!-- Task Header -->
                                <div class="task-header">
                                    <div class="task-title-section flex-grow-1 me-2">
                                        <?php if (!empty($ctask['project_name'])): ?>
                                            <div class="mb-1">
                                                <?php if (!empty($ctask['is_team_project'])): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 rounded-pill" style="font-size: 11px; font-weight: 600;">
                                                        <i class="bi bi-people-fill me-1"></i>Team Project: <?= htmlspecialchars($ctask['project_name']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5 rounded-pill" style="font-size: 11px; font-weight: 600;">
                                                        <i class="bi bi-folder2 me-1"></i>Project: <?= htmlspecialchars($ctask['project_name']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="task-icon-wrapper flex-shrink-0" style="background: #e8f5e9; color: #198754;">
                                                <i class="bi bi-check2-all"></i>
                                            </div>
                                            <div>
                                                <span class="text-muted text-uppercase fw-semibold" style="font-size: 9.5px; letter-spacing: 0.5px; display: block; line-height: 1.2;">Task Title</span>
                                                <h5 class="task-title mb-0" style="font-size: 14.5px; font-weight: 700; color: #1e293b;"><?= htmlspecialchars($ctask['title']) ?></h5>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="task-actions flex-shrink-0">
                                        <span class="badge badge-completed"><i class="bi bi-check-circle-fill me-1"></i>Completed</span>
                                        <button class="btn-collapse ms-1" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#ctask-<?= $ctask['id'] ?>" aria-expanded="true">
                                            <i class="bi bi-chevron-down"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Collapsible Content -->
                                <div class="collapse show" id="ctask-<?= $ctask['id'] ?>">
                                    <!-- Assigned By Info -->
                                    <div class="assigned-section">
                                        <div class="assigned-info-card">
                                            <div class="info-icon">
                                                <i class="bi bi-person-badge"></i>
                                            </div>
                                            <div class="info-content">
                                                <p class="info-label">Assigned by</p>
                                                <p class="info-value"><?= htmlspecialchars($ctask['assigned_by_name'] ?? 'System') ?></p>
                                                <p class="info-designation"><?= htmlspecialchars($ctask['assigned_by_designation'] ?? '') ?></p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Description -->
                                    <?php if (!empty($ctask['description'])): ?>
                                        <div class="task-description-section">
                                            <div class="task-desc-title">
                                                <i class="bi bi-file-text me-1 text-success"></i>Description
                                            </div>
                                            <?= renderTaskDescription($ctask['description'], 20, $ctask['id']) ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Date Info -->
                                    <div class="date-info-section">
                                        <div class="date-card">
                                            <div class="date-icon start-icon">
                                                <i class="bi bi-calendar-check"></i>
                                            </div>
                                            <div class="date-details">
                                                <p class="date-label">Start Date</p>
                                                <p class="date-value"><?= !empty($ctask['start_date']) ? date('d M, Y', strtotime($ctask['start_date'])) : 'N/A' ?></p>
                                            </div>
                                        </div>
                                        <div class="date-card">
                                            <div class="date-icon deadline-icon" style="background: #e8f5e9; color: #198754;">
                                                <i class="bi bi-check-circle"></i>
                                            </div>
                                            <div class="date-details">
                                                <p class="date-label">Completed On</p>
                                                <p class="date-value"><?= !empty($ctask['updated_at']) ? date('d M, Y', strtotime($ctask['updated_at'])) : 'N/A' ?></p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Progress Bar -->
                                    <div class="progress-section">
                                        <div class="progress-header">
                                            <span class="progress-label">
                                                <i class="bi bi-graph-up me-1"></i>Progress
                                            </span>
                                            <span class="progress-percentage ">100%</span>
                                        </div>
                                        <div class="progress-bar-container">
                                            <div class="progress-bar-fill progress-completed" style="width: 100%">
                                                <span class="progress-shine"></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Rating Section -->
                                    <?php if (!empty($ctask['satisfaction_rating'])): ?>
                                        <div class="mt-2 mb-2 p-2 rounded" style="background: #ffffff; border: 1px solid #e2e8f0;">
                                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                <span class="fw-bold" style="color: #334155; font-size: 12px;">
                                                    <i class="bi bi-award-fill me-1" style="color: #ffc107; font-size: 14px;"></i> Rating
                                                </span>
                                                <div class="d-flex align-items-center gap-1">
                                                    <?php for($i=1; $i<=10; $i++): ?>
                                                        <i class="bi bi-star<?= $i <= $ctask['satisfaction_rating'] ? '-fill' : '' ?>" style="font-size: 13px; color: <?= $i <= $ctask['satisfaction_rating'] ? '#ffc107' : '#e2e8f0' ?>;"></i>
                                                    <?php endfor; ?>
                                                    <span class="badge ms-1 px-2 py-0.5" style="background: #fff8e1; color: #b45309; border: 1px solid #fde68a; font-size: 11px; font-weight: 700;">
                                                        <?= $ctask['satisfaction_rating'] ?> / 10
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Action Info -->
                                    <div class="completed-action-info">
                                        <?php if (!empty($ctask['remarks'])): ?>
                                            <div class="mb-2">
                                                <label class="form-label text-dark mb-1" style="font-size: 11.5px; font-weight: 600;"><i class="bi bi-chat-left-text me-1"></i>Remarks</label>
                                                <div class="p-2 bg-light rounded text-muted" style="font-size: 12px;"><?= htmlspecialchars($ctask['remarks']) ?></div>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($ctask['attachment_image'])): ?>
                                            <div class="mt-1 text-start">
                                                <a href="<?= $GLOBALS['base_url'] ?>/public/uploads/tasks/<?= $ctask['attachment_image'] ?>" target="_blank" class="text-primary fs-12 text-decoration-none"><i class="bi bi-image me-1"></i>View Attachment</a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination Controls -->
                <div class="d-flex justify-content-center mt-4" id="pagination-controls">
                    <!-- JS will inject buttons here -->
                </div>
            <?php else: ?>
                <div class="empty-state-container">
                    <div class="empty-state-card">
                        <div class="empty-icon" style="background: #198754;">
                            <i class="bi bi-check-all"></i>
                        </div>
                        <h4 class="empty-title">No Completed Tasks</h4>
                        <p class="empty-description">You haven't completed any tasks yet.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <script>
            let currentPage = 1;
            const tasksPerPage = 10;
            const totalTasks = <?= count($completedTasks) ?>;
            const totalPages = Math.ceil(totalTasks / tasksPerPage);

            function toggleTaskView(view) {
                if (view === 'pending') {
                    document.getElementById('pending-tasks-view').style.display = 'block';
                    document.getElementById('completed-tasks-view').style.display = 'none';
                } else if (view === 'completed') {
                    document.getElementById('pending-tasks-view').style.display = 'none';
                    document.getElementById('completed-tasks-view').style.display = 'block';
                    showPage(1);
                }
            }

            function showPage(page) {
                if (page < 1 || page > totalPages) return;
                currentPage = page;
                
                // Hide all tasks
                document.querySelectorAll('.completed-task-item').forEach(el => el.style.display = 'none');
                
                // Show tasks for current page
                document.querySelectorAll(`.completed-task-item[data-page="${page}"]`).forEach(el => el.style.display = 'block');
                
                renderPagination();
            }

            function renderPagination() {
                const container = document.getElementById('pagination-controls');
                if (!container || totalPages <= 1) return;
                
                let html = '<ul class="pagination pagination-sm">';
                
                // Prev btn
                html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}"><a class="page-link" href="javascript:void(0)" onclick="showPage(${currentPage - 1})">Previous</a></li>`;
                
                // Page numbers
                for (let i = 1; i <= totalPages; i++) {
                    html += `<li class="page-item ${currentPage === i ? 'active' : ''}"><a class="page-link" href="javascript:void(0)" onclick="showPage(${i})">${i}</a></li>`;
                }
                
                // Next btn
                html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}"><a class="page-link" href="javascript:void(0)" onclick="showPage(${currentPage + 1})">Next</a></li>`;
                
                html += '</ul>';
                container.innerHTML = html;
            }

            // Description View More / View Less Toggle
            function toggleTaskDesc(uniqueId, showFull) {
                const shortEl = document.getElementById('short_' + uniqueId);
                const fullEl = document.getElementById('full_' + uniqueId);
                if (!shortEl || !fullEl) return;
                if (showFull) {
                    shortEl.style.display = 'none';
                    fullEl.style.display = 'block';
                } else {
                    fullEl.style.display = 'none';
                    shortEl.style.display = 'block';
                }
            }
        </script>

        <style>
            /* Task Container Styles */
            .tasks-container {
                padding: 12px 0;
            }

            /* Task Card Styles - Overriding general .card__wrapper to avoid tall middle gaps */
            .card__wrapper.task-card,
            .task-card {
                background: #ffffff;
                border-radius: 12px;
                padding: 12px 14px;
                box-shadow: 0 2px 10px rgba(15, 23, 42, 0.05);
                border: 1px solid #e2e8f0;
                transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
                height: 100%;
                display: flex !important;
                flex-direction: column !important;
                justify-content: flex-start !important;
            }

            .task-card:hover {
                box-shadow: 0 6px 20px rgba(15, 23, 42, 0.08);
                transform: translateY(-2px);
            }

            /* Card Status Left Borders */
            .card-pending {
                border-left: 4px solid #f59e0b !important;
            }

            .card-progress {
                border-left: 4px solid #16a34a !important;
            }

            .card-completed {
                border-left: 4px solid #2563eb !important;
                background: #ffffff;
            }

            /* Collapse Layout */
            .task-card > .collapse.show {
                display: flex !important;
                flex-direction: column;
                flex: 1 1 auto;
            }

            .task-card > .collapse:not(.show) {
                display: none !important;
            }

            /* Task Header */
            .task-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 8px;
                padding-bottom: 8px;
                border-bottom: 1px solid #f1f5f9;
            }

            .task-title-section {
                display: flex;
                flex-direction: column;
                gap: 3px;
                flex: 1;
            }

            .task-icon-wrapper {
                width: 28px;
                height: 28px;
                border-radius: 6px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #f1f5f9;
                color: #475569;
                font-size: 14px;
            }

            .task-title {
                margin: 0;
                font-size: 13.5px;
                font-weight: 700;
                color: #1e293b;
                line-height: 1.3;
            }

            .task-actions {
                display: flex;
                align-items: center;
                gap: 4px;
            }

            /* Status Badges */
            .badge {
                padding: 3px 8px;
                border-radius: 20px;
                font-size: 11px;
                font-weight: 600;
                display: inline-flex;
                align-items: center;
                gap: 4px;
            }

            .badge-pending {
                background: #fef3c7;
                color: #92400e;
                border: 1px solid #fde68a;
            }

            .badge-progress {
                background: #e0e7ff;
                color: #3730a3;
                border: 1px solid #c7d2fe;
            }

            .badge-completed {
                background: #dcfce7;
                color: #166534;
                border: 1px solid #bbf7d0;
            }

            /* Collapse Button */
            .btn-collapse {
                width: 24px;
                height: 24px;
                border-radius: 5px;
                border: 1px solid #e2e8f0;
                background: #f8fafc;
                color: #64748b;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all 0.2s ease;
                font-size: 11px;
            }

            .btn-collapse:hover {
                background: #e2e8f0;
                color: #1e293b;
            }

            .btn-collapse i {
                transition: transform 0.25s ease;
            }

            .btn-collapse[aria-expanded="false"] i {
                transform: rotate(-90deg);
            }

            /* Assigned Section */
            .assigned-section {
                margin-bottom: 8px;
            }

            .assigned-info-card {
                background: #f8fafc;
                border-radius: 6px;
                padding: 5px 8px;
                display: flex;
                align-items: center;
                gap: 8px;
                border: 1px solid #f1f5f9;
            }

            .info-icon {
                width: 26px;
                height: 26px;
                background: #f1f5f9;
                border-radius: 6px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #475569;
                font-size: 13px;
                flex-shrink: 0;
            }

            .info-content {
                flex: 1;
            }

            .info-label {
                font-size: 9px;
                color: #64748b;
                margin: 0;
                text-transform: uppercase;
                font-weight: 700;
                letter-spacing: 0.5px;
            }

            .info-value {
                font-size: 12px;
                font-weight: 700;
                color: #1e293b;
                margin: 1px 0 0 0;
                line-height: 1.2;
            }

            .info-designation {
                font-size: 10.5px;
                color: #64748b;
                margin: 0;
                font-weight: 500;
            }

            /* Description Section */
            .task-description-section {
                margin-bottom: 8px;
            }

            /* Reset any external .section-title styles */
            .task-desc-title,
            .task-card .section-title {
                background: transparent !important;
                color: #334155 !important;
                padding: 0 !important;
                margin: 0 0 4px 0 !important;
                font-size: 11px !important;
                font-weight: 700 !important;
                text-transform: uppercase;
                letter-spacing: 0.4px;
                display: flex !important;
                align-items: center !important;
                gap: 4px !important;
                border-radius: 0 !important;
            }

            .description-content {
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-left: 3px solid #16a34a;
                border-radius: 6px;
                padding: 6px 10px;
                font-size: 12px;
                line-height: 1.45;
                color: #334155;
                max-height: 180px;
                overflow-y: auto;
                word-break: break-word;
            }

            .description-content::-webkit-scrollbar {
                width: 4px;
            }

            .description-content::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 4px;
            }

            /* Date Info Section */
            .date-info-section {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 6px;
                margin-bottom: 8px;
            }

            .date-card {
                background: #f8fafc;
                border: 1px solid #f1f5f9;
                border-radius: 6px;
                padding: 4px 8px;
                display: flex;
                align-items: center;
                gap: 6px;
                transition: all 0.2s ease;
            }

            .date-card:hover {
                border-color: #cbd5e1;
                background: #ffffff;
            }

            .date-icon {
                width: 22px;
                height: 22px;
                border-radius: 4px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 11px;
                flex-shrink: 0;
            }

            .start-icon {
                background: #e0e7ff;
                color: #4338ca;
            }

            .deadline-icon {
                background: #fef3c7;
                color: #b45309;
            }

            .date-details {
                flex: 1;
            }

            .date-label {
                font-size: 9px;
                color: #64748b;
                margin: 0;
                text-transform: uppercase;
                font-weight: 700;
                letter-spacing: 0.4px;
            }

            .date-value {
                font-size: 11.5px;
                font-weight: 700;
                color: #1e293b;
                margin: 0;
            }

            /* Progress Section */
            .progress-section {
                margin-bottom: 8px;
            }

            .progress-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 4px;
            }

            .progress-label {
                font-size: 11px;
                font-weight: 700;
                color: #475569;
                display: flex;
                align-items: center;
            }

            .progress-percentage {
                font-size: 11.5px;
                font-weight: 700;
                color: #1e293b;
            }

            .progress-bar-container {
                height: 6px;
                background: #e2e8f0;
                border-radius: 10px;
                overflow: hidden;
                position: relative;
            }

            .progress-bar-fill {
                height: 100%;
                border-radius: 10px;
                position: relative;
                transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            }

            .progress-pending {
                background: linear-gradient(90deg, #f59e0b 0%, #fbbf24 100%);
            }

            .progress-in-progress {
                background: linear-gradient(90deg, #16a34a 0%, #22c55e 100%);
            }

            .progress-completed {
                background: linear-gradient(90deg, #2563eb 0%, #3b82f6 100%);
            }

            /* Task Action Form & Completed Action Info anchored to bottom */
            .action-form,
            .completed-action-info {
                margin-top: auto !important;
                padding-top: 8px;
                border-top: 1px dashed #e2e8f0;
            }

            .action-form .form-control {
                border-radius: 5px;
                border: 1px solid #cbd5e1;
                font-size: 11px;
                color: #1e293b;
            }

            .action-form .form-control:focus {
                border-color: #16a34a;
                box-shadow: 0 0 0 2px rgba(22, 163, 74, 0.15);
            }

            .btn-accept {
                background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
                color: #ffffff;
                padding: 6px 12px;
                font-size: 12px;
                font-weight: 600;
                border: none;
                border-radius: 6px;
                box-shadow: 0 2px 6px rgba(79, 70, 229, 0.25);
                transition: all 0.2s ease;
            }

            .btn-accept:hover {
                background: linear-gradient(135deg, #4338ca 0%, #3730a3 100%);
                color: #ffffff;
                transform: translateY(-1px);
                box-shadow: 0 3px 10px rgba(79, 70, 229, 0.35);
            }

            .btn-update {
                background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
                color: #ffffff;
                padding: 6px 12px;
                font-size: 12px;
                font-weight: 600;
                border: none;
                border-radius: 6px;
                box-shadow: 0 2px 6px rgba(22, 163, 74, 0.25);
                transition: all 0.2s ease;
                white-space: nowrap;
            }

            .btn-update:hover {
                background: linear-gradient(135deg, #15803d 0%, #166534 100%);
                color: #ffffff;
                transform: translateY(-1px);
                box-shadow: 0 3px 10px rgba(22, 163, 74, 0.35);
            }

            /* Empty State */
            .empty-state-container {
                padding: 60px 20px;
                display: flex;
                justify-content: center;
                align-items: center;
            }

            .empty-state-card {
                text-align: center;
                max-width: 380px;
                background: #ffffff;
                border-radius: 16px;
                padding: 40px 30px;
                box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
                border: 1px solid #e2e8f0;
            }

            .empty-icon {
                width: 80px;
                height: 80px;
                margin: 0 auto 20px;
                background: #16a34a;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 40px;
                box-shadow: 0 6px 18px rgba(22, 163, 74, 0.25);
            }

            .empty-title {
                font-size: 20px;
                font-weight: 700;
                color: #1e293b;
                margin: 0 0 8px 0;
            }

            .empty-description {
                font-size: 14px;
                color: #64748b;
                margin: 0;
                line-height: 1.5;
            }

            /* Responsive Design */
            @media (max-width: 991px) {
                .date-info-section {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 576px) {
                .task-card {
                    padding: 14px;
                }
            }
        </style>