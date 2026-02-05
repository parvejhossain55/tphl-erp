        <?php
        $page_title = "Your Task  ";
        ?>

<!-- Task Notification Section -->
<?php if (!empty($pendingTasks)): ?>
    <div class="tasks-container">
        <div class="row g-4">
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

                <div class="col-lg-6 col-md-6 col-sm-12">
                    <div class="task-card <?= $cardClass ?>">
                        <!-- Task Header -->
                        <div class="task-header">
                            <div class="task-title-section">
                                <div class="task-icon-wrapper">
                                    <i class="bi bi-clipboard-check"></i>
                                </div>
                                <h5 class="task-title"><?= htmlspecialchars($task['title']) ?></h5>
                            </div>
                            <div class="task-actions">
                                <?= $statusBadge ?>
                                <button class="btn-collapse" type="button" data-bs-toggle="collapse"
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
                                    <h6 class="section-title">
                                        <i class="bi bi-file-text me-2"></i>Description
                                    </h6>
                                    <div class="description-content">
                                        <?= $task['description'] ?>
                                    </div>
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
                                method="POST" class="task-action-form">
                                <input type="hidden" name="task_id" value="<?= $task['id'] ?>">

                                <?php if ($task['employee_accept'] == 0): ?>
                                    <!-- Accept Button -->
                                    <button type="submit" name="accept" class="btn-task-action btn-accept">
                                        <i class="bi bi-hand-thumbs-up me-2"></i>
                                        Accept Task
                                    </button>
                                <?php else: ?>
                                    <!-- Update Progress -->
                                    <div class="progress-update-group">
                                        <div class="input-wrapper">
                                            <i class="bi bi-percent input-icon"></i>
                                            <input type="number"
                                                name="progress"
                                                min="<?= $task['progress'] ?>"
                                                max="100"
                                                value="<?= $task['progress'] ?>"
                                                class="form-input"
                                                placeholder="Enter progress">
                                        </div>
                                        <button type="submit" class="btn-task-action btn-update">
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
    <div class="empty-state-container">
        <div class="empty-state-card">
            <div class="empty-icon">
                <i class="bi bi-clipboard-check"></i>
            </div>
            <h4 class="empty-title">No Pending Tasks</h4>
            <p class="empty-description">You're all caught up! No tasks assigned at the moment.</p>
        </div>
    </div>
<?php endif; ?>

<style>
/* Task Container Styles */
.tasks-container {
    padding: 20px 0;
}

/* Task Card Styles */
.task-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 12px rgba(25, 135, 84, 0.08);
    border: 1px solid #e8f5e9;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    height: 100%;
    display: flex;
    flex-direction: column;
}

.task-card:hover {
    box-shadow: 0 8px 24px rgba(25, 135, 84, 0.15);
    transform: translateY(-4px);
    border-color: #198754;
}

/* Card Status Variants */
.card-pending {
    border-left: 4px solid #ffc107;
}

.card-progress {
    border-left: 4px solid #198754;
}

.card-completed {
    border-left: 4px solid #0d6efd;
    background: linear-gradient(135deg, #f8f9fa 0%, #e8f5e9 100%);
}

/* Task Header */
.task-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 2px solid #e8f5e9;
}

.task-title-section {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
}

.task-icon-wrapper {
    width: 44px;
    height: 44px;
    background: linear-gradient(135deg, #198754 0%, #146c43 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
    box-shadow: 0 4px 12px rgba(25, 135, 84, 0.25);
}

.task-title {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: #212529;
    line-height: 1.3;
}

.task-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Status Badges */
.badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.badge-pending {
    background: #fff3cd;
    color: #664d03;
    border: 1px solid #ffc107;
}

.badge-progress {
    background: #d1f2e6;
    color: #0a5730;
    border: 1px solid #198754;
}

.badge-completed {
    background: #cfe2ff;
    color: #084298;
    border: 1px solid #0d6efd;
}

/* Collapse Button */
.btn-collapse {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    background: #f8f9fa;
    color: #198754;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-collapse:hover {
    background: #198754;
    color: white;
}

.btn-collapse i {
    transition: transform 0.3s ease;
}

.btn-collapse[aria-expanded="false"] i {
    transform: rotate(-90deg);
}

/* Assigned Section */
.assigned-section {
    margin-bottom: 20px;
}

.assigned-info-card {
    background: linear-gradient(135deg, #e8f5e9 0%, #f8f9fa 100%);
    border-radius: 12px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 14px;
    border: 1px solid #d1f2e6;
}

.info-icon {
    width: 48px;
    height: 48px;
    background: #198754;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 22px;
    flex-shrink: 0;
}

.info-content {
    flex: 1;
}

.info-label {
    font-size: 12px;
    color: #6c757d;
    margin: 0 0 4px 0;
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.info-value {
    font-size: 16px;
    font-weight: 700;
    color: #212529;
    margin: 0 0 2px 0;
}

.info-designation {
    font-size: 13px;
    color: #198754;
    margin: 0;
    font-weight: 600;
}

/* Description Section */
.task-description-section {
    margin-bottom: 20px;
}

.section-title {
    font-size: 14px;
    font-weight: 700;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
}

.description-content {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 14px;
    font-size: 14px;
    line-height: 1.6;
    color: #495057;
    border-left: 3px solid #198754;
}

/* Date Info Section */
.date-info-section {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}

.date-card {
    background: #ffffff;
    border: 1.5px solid #e8f5e9;
    border-radius: 10px;
    padding: 14px;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: all 0.3s ease;
}

.date-card:hover {
    border-color: #198754;
    box-shadow: 0 4px 8px rgba(25, 135, 84, 0.1);
}

.date-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.start-icon {
    background: #d1f2e6;
    color: #198754;
}

.deadline-icon {
    background: #fff3cd;
    color: #ffc107;
}

.date-details {
    flex: 1;
}

.date-label {
    font-size: 11px;
    color: #6c757d;
    margin: 0 0 4px 0;
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.date-value {
    font-size: 14px;
    font-weight: 700;
    color: #212529;
    margin: 0;
}

/* Progress Section */
.progress-section {
    margin-bottom: 20px;
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.progress-label {
    font-size: 14px;
    font-weight: 700;
    color: #198754;
    display: flex;
    align-items: center;
}

.progress-percentage {
    font-size: 16px;
    font-weight: 700;
    color: #198754;
}

.progress-bar-container {
    height: 12px;
    background: #e8f5e9;
    border-radius: 20px;
    overflow: hidden;
    position: relative;
}

.progress-bar-fill {
    height: 100%;
    border-radius: 20px;
    position: relative;
    transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
}

.progress-pending {
    background: linear-gradient(90deg, #ffc107 0%, #ffca2c 100%);
}

.progress-in-progress {
    background: linear-gradient(90deg, #198754 0%, #20c997 100%);
}

.progress-completed {
    background: linear-gradient(90deg, #0d6efd 0%, #0dcaf0 100%);
}

.progress-shine {
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    animation: shine 2s infinite;
}

@keyframes shine {
    to {
        left: 100%;
    }
}

/* Task Action Form */
.task-action-form {
    margin-top: auto;
}

.btn-task-action {
    width: 100%;
    padding: 14px 24px;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.btn-accept {
    background: linear-gradient(135deg, #198754 0%, #146c43 100%);
    color: white;
}

.btn-accept:hover {
    background: linear-gradient(135deg, #146c43 0%, #0f5132 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(25, 135, 84, 0.3);
}

.progress-update-group {
    display: flex;
    gap: 10px;
}

.input-wrapper {
    position: relative;
    flex: 1;
}

.input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    font-size: 16px;
}

.form-input {
    width: 100%;
    padding: 14px 14px 14px 40px;
    border: 2px solid #e8f5e9;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    color: #212529;
    transition: all 0.3s ease;
}

.form-input:focus {
    outline: none;
    border-color: #198754;
    box-shadow: 0 0 0 4px rgba(25, 135, 84, 0.1);
}

.btn-update {
    background: linear-gradient(135deg, #198754 0%, #146c43 100%);
    color: white;
    padding: 14px 24px;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.btn-update:hover {
    background: linear-gradient(135deg, #146c43 0%, #0f5132 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(25, 135, 84, 0.3);
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
    max-width: 400px;
    background: #ffffff;
    border-radius: 20px;
    padding: 50px 30px;
    box-shadow: 0 8px 24px rgba(25, 135, 84, 0.1);
    border: 2px solid #e8f5e9;
}

.empty-icon {
    width: 100px;
    height: 100px;
    margin: 0 auto 24px;
    background: linear-gradient(135deg, #198754 0%, #146c43 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 48px;
    box-shadow: 0 8px 24px rgba(25, 135, 84, 0.25);
}

.empty-title {
    font-size: 24px;
    font-weight: 700;
    color: #212529;
    margin: 0 0 12px 0;
}

.empty-description {
    font-size: 16px;
    color: #6c757d;
    margin: 0;
    line-height: 1.6;
}

/* Responsive Design */
@media (max-width: 991px) {
    .date-info-section {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 576px) {
    .task-card {
        padding: 18px;
    }

    .task-title {
        font-size: 16px;
    }

    .task-icon-wrapper {
        width: 38px;
        height: 38px;
        font-size: 18px;
    }

    .progress-update-group {
        flex-direction: column;
    }

    .btn-update {
        width: 100%;
    }

    .task-header {
        flex-wrap: wrap;
    }

    .task-actions {
        width: 100%;
        justify-content: space-between;
        margin-top: 12px;
    }
}

/* Animation */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.task-card {
    animation: fadeInUp 0.5s ease-out;
}

.task-card:nth-child(2) {
    animation-delay: 0.1s;
}

.task-card:nth-child(3) {
    animation-delay: 0.2s;
}

.task-card:nth-child(4) {
    animation-delay: 0.3s;
}
</style>