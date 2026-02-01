        <?php
        $page_title = "Your Task  ";
        ?>

        <!-- Task Notification Section -->
        <?php if (!empty($pendingTasks)): ?>
            <div class="tasks-container">
                <div class="row g-3">
                    <?php foreach ($pendingTasks as $task): ?>
                        <?php
                        // Determine status and styling
                        $status = 'pending';
                        $statusBadge = '<span class="badge badge-pending"><i class="bi bi-clock-history me-1"></i>Pending</span>';
                        $cardGradient = 'card-pending';

                        if ($task['employee_accept'] == 1) {
                            if ($task['progress'] >= 100) {
                                $status = 'completed';
                                $statusBadge = '<span class="badge badge-completed"><i class="bi bi-check-circle-fill me-1"></i>Completed</span>';
                                $cardGradient = 'card-completed';
                            } else {
                                $status = 'in-progress';
                                $statusBadge = '<span class="badge badge-progress"><i class="bi bi-arrow-repeat me-1"></i>In Progress</span>';
                                $cardGradient = 'card-progress';
                            }
                        }
                        ?>

                        <div class="col-12">
                            <div class="task-card <?= $cardGradient ?> animate-slide-in">
                                <!-- Task Header: Title and Status in Same Row -->
                                <div class="task-header">
                                    <div class="task-title-wrapper">
                                        <i class="bi bi-clipboard-check task-icon"></i>
                                        <h5 class="task-title"><?= htmlspecialchars($task['title']) ?></h5>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <?= $statusBadge ?>
                                        <button class="collapse-toggle" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#task-<?= $task['id'] ?>" aria-expanded="true">
                                            <i class="bi bi-chevron-down fs-5"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Collapsible Content -->
                                <div class="collapse show" id="task-<?= $task['id'] ?>">
                                    <!-- Assigned By -->
                                    <div class="col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12">
                                        <div class="card__wrapper">
                                            <div class="d-flex align-items-center gap-sm">
                                                <div class="card__icon">
                                                    <span><i class="fa-sharp fa-regular fa-gear"></i></span>
                                                </div>
                                                <div class="card__title-wrap">
                                                    <h6 class="card__sub-title mb-10">Task Assigned</h6>
                                                    <div class="d-flex flex-wrap align-items-end gap-10 mt-3">
                                                        <div class="d-none avatar-circle mt-2">
                                                        </div>
                                                        <strong>Assigned by:</strong>
                                                        <p class="ms-2 fw-semibold"><?= htmlspecialchars($task['assigned_by_name'] ?? 'System') ?></p>
                                                    </div>
                                                    <strong class="pe-1">Designation:</strong>
                                                    <p class="mt-2 ms-2"><strong><?= htmlspecialchars($task['assigned_by_designation']); ?></strong></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="task-title mb-2">Task Description</h5>
                                    <!-- Description -->
                                    <?php if (!empty($task['description'])): ?>
                                        <div class="task-description fw-semibold">
                                            <?= $task['description'] ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Date Info -->
                                    <div class="date-section">
                                        <div class="date-box date-start">
                                            <i class="bi bi-calendar-check"></i>
                                            <div class="date-content">
                                                <small class="fw-bold fs-5">Start Date</small>
                                                <strong><?= !empty($task['start_date']) ? date('d M, Y', strtotime($task['start_date'])) : 'N/A' ?></strong>
                                            </div>
                                        </div>
                                        <div class="date-box date-deadline">
                                            <i class="bi bi-calendar-x"></i>
                                            <div class="date-content">
                                                <small class="fw-bold fs-5">Deadline</small>
                                                <strong><?= !empty($task['deadline']) ? date('d M, Y', strtotime($task['deadline'])) : 'N/A' ?></strong>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Progress Bar -->
                                    <div class="progress-section">
                                        <div class="progress-header">
                                            <span class="progress-label">Progress</span>
                                            <span class="progress-percentage"><?= $task['progress'] ?>%</span>
                                        </div>
                                        <div class="custom-progress">
                                            <div class="custom-progress-bar progress-bar-<?= $status ?>"
                                                style="width: <?= $task['progress'] ?>%;"
                                                data-progress="<?= $task['progress'] ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Action Form -->
                                    <form action="<?= $GLOBALS['base_url'] ?>/<?= $task['employee_accept'] == 0 ? 'update-task-status' : 'update-task-progress' ?>"
                                        method="POST" class="action-form">
                                        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">

                                        <?php if ($task['employee_accept'] == 0): ?>
                                            <!-- Accept Button -->
                                            <button type="submit" name="accept" class="btn btn-accept w-100">
                                                <i class="bi bi-hand-thumbs-up me-2"></i>
                                                Accept Task
                                            </button>
                                        <?php else: ?>
                                            <!-- Update Progress -->
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="bi bi-percent"></i>
                                                </span>
                                                <input type="number"
                                                    name="progress"
                                                    min="<?= $task['progress'] ?>"
                                                    max="100"
                                                    value="<?= $task['progress'] ?>"
                                                    class="form-control"
                                                    placeholder="Enter progress percentage">
                                                <button type="submit" class="btn btn-update">
                                                    <i class="bi bi-arrow-clockwise me-2"></i>
                                                    Update
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="bi bi-clipboard-check"></i>
                </div>
                <h4>No Pending Tasks</h4>
                <p class="text-light">You're all caught up! No tasks assigned at the moment.</p>
            </div>
        <?php endif; ?>