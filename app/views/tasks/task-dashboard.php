        <?php
        $page_title = "Your Task  ";
        ?>
        <!-- Task Count Cards -->
        <div class="row mb-4">
            <div class="col-md-6 col-sm-12">
                <div class="card h-100 shadow-sm border-0" style="background: linear-gradient(135deg, #fff3cd 0%, #fff9e6 100%); border-left: 4px solid #ffc107 !important;">
                    <div class="card-body d-flex justify-content-between align-items-center py-3">
                        <div>
                            <h6 class="mb-1 text-warning-emphasis fw-bold" style="color: #664d03 !important;"><i class="bi bi-calendar2-event me-2"></i>Today's Tasks</h6>
                            <span class="text-muted small">Tasks due today</span>
                        </div>
                        <span class="badge bg-warning text-dark fs-5 rounded-pill px-3 py-2 fw-bold shadow-sm"><?= $todayTaskCount ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-sm-12">
                <div class="card h-100 shadow-sm border-0" style="background: linear-gradient(135deg, #e8e6ff 0%, #f3f1ff 100%); border-left: 4px solid #5e4cdc !important;">
                    <div class="card-body d-flex justify-content-between align-items-center py-3">
                        <div>
                            <h6 class="mb-1 text-primary fw-bold" style="color: #493aa6 !important;"><i class="bi bi-clipboard-data me-2"></i>Total Pending Tasks</h6>
                            <span class="text-muted small">All incomplete tasks</span>
                        </div>
                        <span class="badge text-white fs-5 rounded-pill px-3 py-2 fw-bold shadow-sm" style="background-color: #5e4cdc !important;"><?= count($pendingTasks) ?></span>
                    </div>
                </div>
            </div>
        </div>


        <!-- Task Notification Section -->
        <?php if (!empty($pendingTasks)): ?>
            <div class="tasks-container">
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
                                        method="POST" enctype="multipart/form-data" class="action-form">
                                        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">

                                        <?php if ($task['employee_accept'] == 0): ?>
                                            <!-- Accept Button -->
                                            <button type="submit" name="accept" class="btn-task-action btn-accept">
                                                <i class="bi bi-hand-thumbs-up me-2"></i>
                                                Accept Task
                                            </button>
                                        <?php else: ?>
                                            <!-- Update Progress Details -->
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold text-dark"><i class="bi bi-percent me-1"></i>Progress (%)</label>
                                                <input type="number"
                                                    name="progress"
                                                    min="<?= $task['progress'] ?>"
                                                    max="100"
                                                    value="<?= $task['progress'] ?>"
                                                    class="form-control"
                                                    placeholder="Progress percentage"
                                                    required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold text-dark"><i class="bi bi-chat-left-text me-1"></i>Remarks</label>
                                                <textarea name="remarks" class="form-control" rows="2" placeholder="Explain what has been completed..." required><?= htmlspecialchars($task['remarks'] ?? '') ?></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold text-dark"><i class="bi bi-image me-1"></i>Proof Image (Optional)</label>
                                                <input type="file" name="attachment_image" class="form-control" accept="image/*">
                                                <?php if (!empty($task['attachment_image'])): ?>
                                                    <div class="mt-2 text-start">
                                                        <a href="<?= $GLOBALS['base_url'] ?>/public/uploads/tasks/<?= $task['attachment_image'] ?>" target="_blank" class="text-primary fs-13"><i class="bi bi-eye me-1"></i>View Current Image</a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <button type="submit" class="btn btn-update w-100">
                                                <i class="bi bi-arrow-clockwise me-2"></i>
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
                border-radius: 12px;
                padding: 16px;
                box-shadow: 0 4px 12px rgba(94, 76, 220, 0.08);
                border: 1px solid #e8e6ff;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                height: 100%;
                display: flex;
                flex-direction: column;
            }

            .task-card:hover {
                box-shadow: 0 8px 24px rgba(94, 76, 220, 0.15);
                transform: translateY(-4px);
                border-color: #5e4cdc;
            }

            /* Card Status Variants */
            .card-pending {
                border-left: 4px solid #ffc107;
            }

            .card-progress {
                border-left: 4px solid #5e4cdc;
            }

            .card-completed {
                border-left: 4px solid #0d6efd;
                background: linear-gradient(135deg, #f8f9fa 0%, #e8e6ff 100%);
            }

            /* Task Header */
            .task-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 14px;
                padding-bottom: 10px;
                border-bottom: 2px solid #e8e6ff;
            }

            .task-title-section {
                display: flex;
                align-items: center;
                gap: 12px;
                flex: 1;
            }

            .task-icon-wrapper {
                width: 36px;
                height: 36px;
                /* background: linear-gradient(135deg, #5e4cdc 0%, #4b3db1 100%); */
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                /* color: white; */
                font-size: 16px;
                box-shadow: 0 4px 12px rgba(94, 76, 220, 0.25);
            }

            .task-title {
                margin: 0;
                font-size: 15px;
                font-weight: 700;
                /* color: #212529; */
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
                background: #e8e6ff;
                color: #493aa6;
                border: 1px solid #5e4cdc;
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
                color: #5e4cdc;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all 0.3s ease;
            }

            .btn-collapse:hover {
                background: #5e4cdc;
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
                margin-bottom: 14px;
            }

            .assigned-info-card {
                /* background: linear-gradient(135deg, #e8e6ff 0%, #f8f9fa 100%); */
                border-radius: 10px;
                padding: 10px 12px;
                display: flex;
                align-items: center;
                gap: 10px;
                border: 1px solid #e8e6ff;
            }

            .info-icon {
                width: 36px;
                height: 36px;
                /* background: #5e4cdc; */
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #5e4cdc;
                font-size: 18px;
                flex-shrink: 0;
            }

            .info-content {
                flex: 1;
            }

            .info-label {
                font-size: 11px;
                color: #6c757d;
                margin: 0 0 4px 0;
                text-transform: uppercase;
                font-weight: 600;
                letter-spacing: 0.5px;
            }

            .info-value {
                font-size: 14px;
                font-weight: 700;
                /* color: #212529; */
                margin: 0 0 2px 0;
            }

            .info-designation {
                font-size: 12px;
                color: #5e4cdc;
                margin: 0;
                font-weight: 600;
            }

            /* Description Section */
            .task-description-section {
                margin-bottom: 14px;
            }

            .section-title {
                font-size: 13px;
                font-weight: 700;
                margin-bottom: 8px;
                display: flex;
                align-items: center;
            }

            .description-content {
                /* background: #f8f9fa; */
                border-radius: 8px;
                padding: 10px 12px;
                font-size: 13px;
                line-height: 1.5;
                /* color: #495057; */
                border-left: 3px solid #5e4cdc;
            }

            /* Date Info Section */
            .date-info-section {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 8px;
                margin-bottom: 14px;
            }

            .date-card {
                /* background: #ffffff; */
                border: 1.5px solid #e8e6ff;
                border-radius: 8px;
                padding: 8px 10px;
                display: flex;
                align-items: center;
                gap: 8px;
                transition: all 0.3s ease;
            }

            .date-card:hover {
                border-color: #5e4cdc;
                box-shadow: 0 4px 8px rgba(94, 76, 220, 0.1);
            }

            .date-icon {
                width: 30px;
                height: 30px;
                border-radius: 6px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 14px;
                flex-shrink: 0;
            }

            .start-icon {
                background: #e8e6ff;
                color: #5e4cdc;
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
                /* color: #6c757d; */
                margin: 0 0 4px 0;
                text-transform: uppercase;
                font-weight: 600;
                letter-spacing: 0.5px;
            }

            .date-value {
                font-size: 14px;
                font-weight: 700;
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
                color: #5e4cdc;
                display: flex;
                align-items: center;
            }

            .progress-percentage {
                font-size: 16px;
                font-weight: 700;
                color: #5e4cdc;
            }

            .progress-bar-container {
                height: 12px;
                background: #e8e6ff;
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
                background: linear-gradient(90deg, #5e4cdc 0%, #8e7eff 100%);
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
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
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
                padding: 10px 16px;
                border: none;
                border-radius: 8px;
                font-size: 14px;
                font-weight: 700;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }

            .btn-accept {
                background: #5e4cdc;
                color: white;
            }

            .btn-accept:hover {
                background: orange;
                transform: translateY(-2px);
                box-shadow: 0 6px 16px rgba(94, 76, 220, 0.3);
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
                padding: 10px 10px 10px 32px;
                border: 2px solid #e8e6ff;
                border-radius: 8px;
                font-size: 14px;
                font-weight: 600;
                color: #212529;
                transition: all 0.3s ease;
            }

            .form-input:focus {
                outline: none;
                border-color: #5e4cdc;
                box-shadow: 0 0 0 4px rgba(94, 76, 220, 0.1);
            }

            .btn-update {
                background: #19915a;
                color: white;
                padding: 10px 16px;
                border: none;
                border-radius: 8px;
                font-size: 14px;
                font-weight: 700;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all 0.3s ease;
                white-space: nowrap;
            }

            .btn-update:hover {
                background: linear-gradient(135deg, #4b3db1 0%, #352880 100%);
                transform: translateY(-2px);
                box-shadow: 0 6px 16px rgba(94, 76, 220, 0.3);
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
                box-shadow: 0 8px 24px rgba(94, 76, 220, 0.1);
                border: 2px solid #e8e6ff;
            }

            .empty-icon {
                width: 100px;
                height: 100px;
                margin: 0 auto 24px;
                background: #19915a;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 48px;
                box-shadow: 0 8px 24px rgba(94, 76, 220, 0.25);
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