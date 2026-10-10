<style>
/* Modern Ultra-Clean Todo Module CSS */
.todo-container {
    background-color: #ffffff;
    min-height: calc(100vh - 120px);
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    margin-bottom: 30px;
}
.todo-wrapper {
    display: flex;
    height: 100%;
}
.todo-sidebar {
    width: 270px;
    background-color: #f8fafc;
    padding: 30px 18px;
    border-right: 1px solid #edf2f7;
    flex-shrink: 0;
}
.todo-main {
    flex: 1;
    padding: 32px 48px;
    width: 100%;
    background-color: #ffffff;
    overflow-y: auto;
}
.nav-item-custom {
    padding: 10px 16px;
    border-radius: 10px;
    color: #475569;
    text-decoration: none;
    display: flex;
    align-items: center;
    font-size: 14.5px;
    font-weight: 500;
    margin-bottom: 6px;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}
.nav-item-custom:hover {
    background-color: #f1f5f9;
    color: #0f172a;
}
.nav-item-custom i {
    width: 28px;
    font-size: 17px;
}
.nav-item-custom.active {
    background-color: #fef2f2;
    color: #dc2626;
    font-weight: 600;
}
.nav-item-custom.active i {
    color: #dc2626 !important;
}
.project-header {
    font-size: 13px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-top: 32px;
    margin-bottom: 12px;
    padding-left: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.project-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 12px;
}
.task-header {
    font-size: 26px;
    font-weight: 800;
    margin-bottom: 24px;
    color: #0f172a;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.task-item {
    display: flex;
    padding: 16px 20px;
    border: 1px solid #f1f5f9;
    border-radius: 12px;
    margin-bottom: 10px;
    align-items: center;
    background-color: #ffffff;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}
.task-item:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 12px rgba(0,0,0,0.04);
    background-color: #fafafa;
}
.task-checkbox {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: 2px solid #cbd5e1;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    margin-right: 18px;
    flex-shrink: 0;
    transition: all 0.2s;
}
.task-checkbox:hover {
    background-color: #f1f5f9;
    border-color: #94a3b8;
}
.task-checkbox i {
    font-size: 11px;
    color: white;
    display: none;
}
.task-checkbox.completed { background-color: #64748b; border-color: #64748b; }
.task-checkbox.completed i { display: block; }
.task-checkbox.p1 { border-color: #ef4444; background-color: #fef2f2; }
.task-checkbox.p2 { border-color: #f97316; background-color: #fff7ed; }
.task-checkbox.p3 { border-color: #3b82f6; background-color: #eff6ff; }
.task-checkbox.p4 { border-color: #cbd5e1; }

.task-content { flex: 1; min-width: 0; }
.task-title { font-size: 15.5px; color: #1e293b; font-weight: 600; margin-bottom: 4px; word-break: break-word; }
.task-desc { font-size: 13.5px; color: #64748b; margin-bottom: 8px; }
.task-meta { font-size: 12.5px; color: #64748b; display: flex; flex-wrap: wrap; gap: 10px 18px; align-items: center; }

/* Enhanced Add Task Button */
.btn-add-task {
    color: #16a34a;
    background-color: #f0fdf4;
    border: 1px dashed #bbf7d0;
    text-decoration: none;
    font-size: 15px;
    display: inline-flex;
    align-items: center;
    padding: 12px 24px;
    margin-top: 18px;
    cursor: pointer;
    border-radius: 12px;
    transition: all 0.2s;
    font-weight: 600;
    width: 100%;
    justify-content: center;
}
.btn-add-task:hover {
    background-color: #dcfce7;
    border-color: #86efac;
    color: #15803d;
}
.btn-add-task i {
    margin-right: 10px;
    font-size: 18px;
}

/* Enhanced Add Task Form */
.add-task-form {
    border: 1px solid #cbd5e1;
    border-radius: 16px;
    padding: 26px;
    margin-top: 20px;
    display: none;
    box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.08);
    background-color: #ffffff;
    width: 100%;
}
.add-task-form input.task-title-input {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px 16px;
    font-weight: 600;
    font-size: 16.5px;
    width: 100%;
    margin-bottom: 12px;
    background-color: #f8fafc;
    transition: all 0.2s;
}
.add-task-form input.task-title-input:focus {
    background-color: #ffffff;
    border-color: #16a34a;
    box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.12);
    outline: none;
}
.add-task-form input.task-title-input::placeholder { color: #94a3b8; font-weight: 500; }

.add-task-form input.task-desc-input {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 16px;
    font-size: 14px;
    width: 100%;
    color: #334155;
    margin-bottom: 20px;
    background-color: #f8fafc;
    transition: all 0.2s;
}
.add-task-form input.task-desc-input:focus {
    background-color: #ffffff;
    border-color: #16a34a;
    box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.12);
    outline: none;
}

/* Settings Card within Forms */
.advanced-settings-panel {
    background: linear-gradient(145deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 18px 22px;
    margin-top: 18px;
    margin-bottom: 20px;
}
.form-field-label {
    font-size: 12.5px;
    font-weight: 600;
    color: #475569;
    margin-bottom: 6px;
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* 4-column single row layout */
.todo-row-4-col {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    width: 100%;
}
@media (max-width: 991px) {
    .todo-row-4-col {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (max-width: 576px) {
    .todo-row-4-col {
        grid-template-columns: 1fr;
    }
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    border-top: 1px solid #f1f5f9;
    padding-top: 18px;
    gap: 12px;
}
.btn-todo-submit {
    background-color: #16a34a;
    color: white;
    border: none;
    padding: 10px 24px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14.5px;
    transition: all 0.2s;
    box-shadow: 0 2px 6px rgba(22, 163, 74, 0.25);
}
.btn-todo-submit:hover {
    background-color: #15803d;
    color: white;
    box-shadow: 0 4px 12px rgba(22, 163, 74, 0.35);
}
.btn-todo-cancel {
    background-color: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 500;
    font-size: 14.5px;
    transition: all 0.2s;
}
.btn-todo-cancel:hover { background-color: #e2e8f0; color: #1e293b; }
</style>

<div class="container-fluid py-4 px-md-4">
    <div class="row mb-3">
        <div class="col-12">
            <h4 class="mb-1 text-dark fw-bold">Task Management</h4>
            <p class="text-muted small">Manage your personal tasks, recurring schedules, and email reminders.</p>
        </div>
    </div>

    <div class="todo-container">
        <div class="todo-wrapper">
            <!-- Sidebar -->
            <div class="todo-sidebar">
                <a href="<?php echo $GLOBALS['base_url']; ?>/todo?view=inbox" class="nav-item-custom <?php echo $viewType == 'inbox' ? 'active' : ''; ?>">
                    <i class="fas fa-inbox text-primary"></i> Inbox
                    <span class="ms-auto text-muted small badge bg-light text-dark rounded-pill"><?php echo $counts['inbox_count'] > 0 ? $counts['inbox_count'] : ''; ?></span>
                </a>
                <a href="<?php echo $GLOBALS['base_url']; ?>/todo?view=today" class="nav-item-custom <?php echo $viewType == 'today' ? 'active' : ''; ?>">
                    <i class="far fa-calendar-alt text-success"></i> Today
                    <span class="ms-auto text-muted small badge bg-success-subtle text-success rounded-pill"><?php echo $counts['today_count'] > 0 ? $counts['today_count'] : ''; ?></span>
                </a>
                <a href="<?php echo $GLOBALS['base_url']; ?>/todo?view=upcoming" class="nav-item-custom <?php echo $viewType == 'upcoming' ? 'active' : ''; ?>">
                    <i class="far fa-calendar-check text-warning"></i> Upcoming
                    <span class="ms-auto text-muted small badge bg-warning-subtle text-warning-emphasis rounded-pill"><?php echo $counts['upcoming_count'] > 0 ? $counts['upcoming_count'] : ''; ?></span>
                </a>
                <a href="<?php echo $GLOBALS['base_url']; ?>/todo?view=completed" class="nav-item-custom <?php echo $viewType == 'completed' ? 'active' : ''; ?>">
                    <i class="fas fa-check-double text-secondary"></i> Completed
                </a>

                <div class="project-header">
                    <span>My Projects</span>
                </div>
                
                <?php foreach($projects as $p): if($p['is_inbox']) continue; ?>
                <a href="<?php echo $GLOBALS['base_url']; ?>/todo?view=project&project_id=<?php echo $p['id']; ?>" class="nav-item-custom <?php echo ($viewType == 'project' && $projectId == $p['id']) ? 'active' : ''; ?>">
                    <span class="project-dot" style="background-color: <?php echo $p['color_code']; ?>"></span>
                    <?php echo htmlspecialchars($p['name']); ?>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Main Content Area -->
            <div class="todo-main">
                <div class="task-header">
                    <span><?php echo htmlspecialchars($pageTitle ?? 'Inbox'); ?></span>
                    <span class="badge bg-light text-secondary border fw-normal fs-13 px-3 py-2 rounded-pill">
                        Total <?php echo count($tasks); ?> Tasks
                    </span>
                </div>

                <div class="task-list">
                    <?php if(empty($tasks)): ?>
                        <div class="text-center py-5 text-muted bg-light rounded-4 my-3">
                            <div class="mb-3 text-success" style="font-size: 54px; opacity: 0.6;">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <h5 class="fw-bold text-dark mb-1">All clear & up to date</h5>
                            <p class="text-muted mb-0">No tasks pending in this view. Click "Add task" below to schedule one.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($tasks as $t): ?>
                        <div class="task-item">
                            <?php if ($t['status'] === 'completed'): ?>
                                <div class="task-checkbox completed">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="task-content" style="opacity: 0.6;">
                                    <div class="task-title text-decoration-line-through"><?php echo htmlspecialchars($t['title']); ?></div>
                                    <?php if($t['description']): ?>
                                    <div class="task-desc text-decoration-line-through"><?php echo htmlspecialchars($t['description']); ?></div>
                                    <?php endif; ?>
                            <?php else: ?>
                                <div class="task-checkbox p<?php echo $t['priority']; ?>" onclick="completeTask(<?php echo $t['id']; ?>)">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="task-content">
                                    <div class="task-title"><?php echo htmlspecialchars($t['title']); ?></div>
                                    <?php if($t['description']): ?>
                                    <div class="task-desc"><?php echo htmlspecialchars($t['description']); ?></div>
                                    <?php endif; ?>
                            <?php endif; ?>
                                
                                <div class="task-meta">
                                    <?php if($t['due_date']): ?>
                                        <?php if(!empty($t['repeat_until'])): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" title="From <?= date('d M Y', strtotime($t['due_date'])) ?> to <?= date('d M Y', strtotime($t['repeat_until'])) ?>">
                                            <i class="far fa-calendar-alt me-1"></i> <?php echo date('d M Y', strtotime($t['due_date'])); ?> &rarr; <?php echo date('d M Y', strtotime($t['repeat_until'])); ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                            <i class="far fa-calendar me-1"></i> <?php echo date('d M Y', strtotime($t['due_date'])); ?>
                                        </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php if($t['project_name']): ?>
                                    <span class="badge bg-light text-dark border px-2 py-1">
                                        <i class="fas fa-circle me-1" style="color: <?php echo $t['project_color']; ?>; font-size: 8px;"></i> <?php echo htmlspecialchars($t['project_name']); ?>
                                    </span>
                                    <?php endif; ?>
                                    
                                    <span class="badge px-2 py-1" style="background-color: <?php echo $t['priority'] == 1 ? '#fee2e2' : ($t['priority'] == 2 ? '#ffedd5' : ($t['priority'] == 3 ? '#dbeafe' : '#f1f5f9')); ?>; color: <?php echo $t['priority'] == 1 ? '#dc2626' : ($t['priority'] == 2 ? '#ea580c' : ($t['priority'] == 3 ? '#2563eb' : '#64748b')); ?>; border: 1px solid currentColor;">
                                        <i class="fas fa-flag me-1"></i> Priority <?php echo $t['priority']; ?>
                                    </span>
                                    
                                    <?php if(!empty($t['location'])): ?>
                                    <span class="text-muted"><i class="fas fa-map-marker-alt text-primary me-1"></i> <?php echo htmlspecialchars($t['location']); ?></span>
                                    <?php endif; ?>
                                    
                                    <?php if(!empty($t['attachment'])): ?>
                                    <a href="<?php echo $GLOBALS['base_url']; ?>/<?php echo htmlspecialchars($t['attachment']); ?>" target="_blank" class="badge bg-light text-primary border px-2 py-1 text-decoration-none" title="View Attachment">
                                        <i class="fas fa-paperclip me-1"></i> File
                                    </a>
                                    <?php endif; ?>

                                    <?php if(!empty($t['repeat_type'])): ?>
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1">
                                        <i class="fas fa-redo me-1"></i> <?php echo ucfirst($t['repeat_type']); ?><?php echo !empty($t['repeat_until']) ? ' (Until ' . date('d M', strtotime($t['repeat_until'])) . ')' : ''; ?>
                                    </span>
                                    <?php endif; ?>

                                    <?php if(!empty($t['max_reminders']) && $t['max_reminders'] > 0): ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1" title="Reminder: <?= !empty($t['reminder_trigger_time']) ? date('d M Y, h:i A', strtotime($t['reminder_trigger_time'])) : '' ?> (Max <?= $t['max_reminders'] ?> times)">
                                        <i class="fas fa-bell me-1"></i> <?= !empty($t['reminder_trigger_time']) ? date('d M, h:i A', strtotime($t['reminder_trigger_time'])) : $t['max_reminders'].'x Mail' ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="ms-auto ps-3 align-self-center d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-light text-secondary border rounded-pill px-2 py-1" onclick='editTask(<?php echo json_encode([
                                    "id" => $t["id"],
                                    "title" => htmlspecialchars($t["title"]),
                                    "description" => htmlspecialchars($t["description"] ?? ""),
                                    "due_date" => $t["due_date"] ? date("Y-m-d", strtotime($t["due_date"])) : "",
                                    "priority" => $t["priority"],
                                    "location" => htmlspecialchars($t["location"] ?? ""),
                                    "repeat_type" => $t["repeat_type"] ?? "",
                                    "repeat_until" => $t["repeat_until"] ? date("Y-m-d", strtotime($t["repeat_until"])) : "",
                                    "reminder_trigger_time" => $t["reminder_trigger_time"] ?? "",
                                    "max_reminders" => $t["max_reminders"] ?? 0
                                ], JSON_HEX_APOS); ?>)' title="Edit Task">
                                    <i class="fas fa-pen"></i> Edit
                                </button>
                                <button type="button" class="btn btn-sm btn-light text-danger border rounded-pill px-2 py-1" onclick="deleteTask(<?php echo $t['id']; ?>, '<?php echo $t['repeat_type'] ?? ''; ?>')" title="Delete Task">
                                    <i class="far fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if(isset($totalPages) && $totalPages > 1): ?>
                <nav aria-label="Task pagination" class="mt-4">
                    <ul class="pagination pagination-sm justify-content-center">
                        <?php for($i=1; $i<=$totalPages; $i++): ?>
                        <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                            <a class="page-link text-success" href="?view=<?php echo urlencode($viewType); ?><?php echo $projectId ? '&project_id='.$projectId : ''; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>

                <!-- Add Task Trigger Button -->
                <a class="btn-add-task" onclick="showAddTaskForm()">
                    <i class="fas fa-plus-circle"></i> Add new task
                </a>

                <!-- Add Task Form -->
                <div class="add-task-form" id="addTaskForm">
                    <form action="<?php echo $GLOBALS['base_url']; ?>/todo/store" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="todo_project_id" value="<?php echo $inboxId; ?>">
                        
                        <div class="mb-3">
                            <input type="text" name="title" class="task-title-input" placeholder="Task name..." required autocomplete="off">
                            <input type="text" name="description" class="task-desc-input" placeholder="Add description (optional)..." autocomplete="off">
                        </div>
                        
                        <!-- Core Controls Row: Strictly 1 Single Row (4 Columns) -->
                        <div class="todo-row-4-col mb-3">
                            <div>
                                <label class="form-field-label"><i class="far fa-calendar text-danger me-1"></i> Due / Start Date</label>
                                <input type="date" name="due_date" class="form-control form-control-sm w-100" title="Due / Start Date">
                            </div>
                            
                            <div>
                                <label class="form-field-label"><i class="fas fa-flag text-warning me-1"></i> Priority</label>
                                <select name="priority" class="form-select form-select-sm w-100">
                                    <option value="4">Priority 4 (Normal)</option>
                                    <option value="3">Priority 3 (Medium)</option>
                                    <option value="2">Priority 2 (High)</option>
                                    <option value="1">Priority 1 (Urgent / Highest)</option>
                                </select>
                            </div>

                            <div>
                                <label class="form-field-label"><i class="fas fa-map-marker-alt text-primary me-1"></i> Location / Tag</label>
                                <input type="text" name="location" class="form-control form-control-sm w-100" placeholder="e.g. Office, Site, Online">
                            </div>
                            
                            <div>
                                <label class="form-field-label"><i class="fas fa-paperclip text-info me-1"></i> Attachment File</label>
                                <input type="file" name="attachment" class="form-control form-control-sm w-100" style="font-size: 12px;">
                            </div>
                        </div>

                        <!-- Advanced Reminders & Date Range Repeat Panel: Strictly 1 Single Row (4 Columns) -->
                        <div class="advanced-settings-panel">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h6 class="mb-0 text-dark fw-bold" style="font-size: 13.5px;">
                                    <i class="fas fa-bell text-warning me-2"></i> Advanced Reminders & Date Range Repeat
                                </h6>
                                <span class="badge bg-white text-secondary border px-2 py-1 fs-11">Custom Schedule & Email Trigger</span>
                            </div>
                            
                            <div class="todo-row-4-col">
                                <div>
                                    <label class="form-field-label">Repeat Mode</label>
                                    <select name="repeat_type" id="add_repeat_type" class="form-select form-select-sm w-100" onchange="toggleRepeatUntil(this.value, 'add_repeat_until_input')">
                                        <option value="">Do not repeat</option>
                                        <option value="daily">Daily</option>
                                        <option value="weekly">Weekly</option>
                                        <option value="monthly">Monthly</option>
                                        <option value="custom">Custom Date Range</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-field-label text-primary">Repeat Until (To Date)</label>
                                    <input type="date" name="repeat_until" id="add_repeat_until_input" class="form-control form-control-sm bg-white border-primary-subtle w-100" placeholder="To Date" disabled>
                                </div>
                                <div>
                                    <label class="form-field-label">Custom Reminder Date & Time</label>
                                    <input type="datetime-local" name="reminder_date_time" class="form-control form-control-sm bg-white w-100" title="Select specific date and time for reminder">
                                </div>
                                <div>
                                    <label class="form-field-label">Email Reminder Count</label>
                                    <div class="input-group input-group-sm w-100">
                                        <span class="input-group-text bg-white">Send Max</span>
                                        <input type="number" name="max_reminders" class="form-control bg-white text-center" value="1" min="0" placeholder="e.g. 2">
                                        <span class="input-group-text bg-white">Times</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn btn-todo-cancel" onclick="hideAddTaskForm()">Cancel</button>
                            <button type="submit" class="btn btn-todo-submit"><i class="fas fa-check me-1"></i> Add Task</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Complete Task Modal -->
<div class="modal fade" id="completeTaskModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
      <div class="modal-body text-center p-4">
        <div class="mb-3 text-success" style="font-size: 44px;">
            <i class="fas fa-check-circle"></i>
        </div>
        <h5 class="fw-bold mb-2">Complete Task?</h5>
        <p class="text-muted small mb-4">Are you sure you want to mark this task as completed?</p>
        <form action="<?php echo $GLOBALS['base_url']; ?>/todo/complete" method="POST" id="completeTaskForm">
            <input type="hidden" name="task_id" id="modalTaskId">
            <input type="hidden" name="redirect_view" value="<?php echo htmlspecialchars($viewType); ?>">
            <div class="d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal" style="font-weight: 500;">Cancel</button>
                <button type="submit" class="btn btn-success rounded-pill px-4" style="font-weight: 600; background-color: #16a34a; border: none;">Yes, Complete</button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Task Delete Modal -->
<div class="modal fade" id="deleteTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-body text-center p-4">
                <div class="mb-3 text-danger" style="font-size: 44px;">
                    <i class="fas fa-trash-alt"></i>
                </div>
                <h5 class="fw-bold mb-2">Delete Task?</h5>
                <p class="text-muted small mb-3">Are you sure you want to delete this task?</p>
                <form action="<?php echo $GLOBALS['base_url']; ?>/todo/delete" method="POST">
                    <input type="hidden" name="task_id" id="delete_task_id" value="">
                    <input type="hidden" name="redirect_view" value="<?php echo htmlspecialchars($viewType); ?>">
                    
                    <div class="form-check text-start mb-4 bg-light p-2 rounded" id="deleteFutureWrap" style="display: none; font-size: 13px;">
                        <input class="form-check-input ms-1" type="checkbox" name="delete_future" value="1" id="deleteFutureCheck" checked>
                        <label class="form-check-label text-danger fw-bold ms-1" for="deleteFutureCheck">
                            Turn off future repetition
                        </label>
                    </div>

                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal" style="font-weight: 500;">Cancel</button>
                        <button type="submit" class="btn btn-danger rounded-pill px-4" style="font-weight: 600; border: none;">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Task Edit Modal -->
<div class="modal fade" id="editTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold text-dark"><i class="fas fa-pen-to-square text-primary me-2"></i> Edit Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <form action="<?php echo $GLOBALS['base_url']; ?>/todo/update" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="task_id" id="edit_task_id" value="">
                    <input type="hidden" name="redirect_view" value="<?php echo htmlspecialchars($viewType); ?>">
                    
                    <div class="mb-3">
                        <label class="form-field-label">Task Title</label>
                        <input type="text" name="title" id="edit_title" class="form-control fw-bold" style="font-size: 16px; padding: 10px 14px;" placeholder="Task name" required autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label class="form-field-label">Task Description (Optional)</label>
                        <input type="text" name="description" id="edit_description" class="form-control" style="font-size: 14px; padding: 9px 14px;" placeholder="Description (optional)" autocomplete="off">
                    </div>
                    
                    <!-- Edit Core Controls: 1 Single Row (4 Columns) -->
                    <div class="todo-row-4-col mb-3">
                        <div>
                            <label class="form-field-label"><i class="far fa-calendar text-danger me-1"></i> Due / Start Date</label>
                            <input type="date" name="due_date" id="edit_due_date" class="form-control form-control-sm w-100">
                        </div>
                        
                        <div>
                            <label class="form-field-label"><i class="fas fa-flag text-warning me-1"></i> Priority</label>
                            <select name="priority" id="edit_priority" class="form-select form-select-sm w-100">
                                <option value="4">Priority 4 (Normal)</option>
                                <option value="3">Priority 3 (Medium)</option>
                                <option value="2">Priority 2 (High)</option>
                                <option value="1">Priority 1 (Urgent / Red)</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="form-field-label"><i class="fas fa-map-marker-alt text-primary me-1"></i> Location / Tag</label>
                            <input type="text" name="location" id="edit_location" class="form-control form-control-sm w-100" placeholder="Location/Deadline">
                        </div>
                        
                        <div>
                            <label class="form-field-label"><i class="fas fa-paperclip text-info me-1"></i> Attachment</label>
                            <input type="file" name="attachment" class="form-control form-control-sm w-100" style="font-size: 12px;">
                        </div>
                    </div>

                    <!-- Edit Advanced Settings: 1 Single Row (4 Columns) -->
                    <div class="advanced-settings-panel">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="mb-0 text-dark fw-bold" style="font-size: 13.5px;">
                                <i class="fas fa-bell text-warning me-2"></i> Advanced Reminders & Date Range Repeat
                            </h6>
                            <span class="badge bg-white text-secondary border px-2 py-1 fs-11">Custom Schedule</span>
                        </div>
                        <div class="todo-row-4-col">
                            <div>
                                <label class="form-field-label">Repeat Task Mode</label>
                                <select name="repeat_type" id="edit_repeat_type" class="form-select form-select-sm bg-white w-100" onchange="toggleRepeatUntil(this.value, 'edit_repeat_until_input')">
                                    <option value="">Do not repeat</option>
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="custom">Custom Date Range</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-field-label text-primary">Repeat Until (To Date)</label>
                                <input type="date" name="repeat_until" id="edit_repeat_until_input" class="form-control form-control-sm bg-white border-primary-subtle w-100" disabled>
                            </div>
                            <div>
                                <label class="form-field-label">Custom Reminder Date & Time</label>
                                <input type="datetime-local" name="reminder_date_time" id="edit_reminder_date_time" class="form-control form-control-sm bg-white w-100">
                            </div>
                            <div>
                                <label class="form-field-label">Email Reminder Count</label>
                                <div class="input-group input-group-sm w-100">
                                    <span class="input-group-text bg-white border-end-0">Max</span>
                                    <input type="number" name="max_reminders" id="edit_max_reminders" class="form-control bg-white text-center" value="0" min="0">
                                    <span class="input-group-text bg-white border-start-0">Times</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-2">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success rounded-pill px-4" style="background-color: #16a34a; border: none; font-weight: 600;"><i class="fas fa-save me-1"></i> Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function showAddTaskForm() {
    document.querySelector('.btn-add-task').style.display = 'none';
    document.getElementById('addTaskForm').style.display = 'block';
    document.querySelector('.task-title-input').focus();
}

function hideAddTaskForm() {
    document.querySelector('.btn-add-task').style.display = 'inline-flex';
    document.getElementById('addTaskForm').style.display = 'none';
}

function toggleRepeatUntil(val, inputId) {
    var el = document.getElementById(inputId);
    if (el) {
        if (val && val.trim() !== '') {
            el.disabled = false;
            el.style.backgroundColor = '#ffffff';
            el.focus();
        } else {
            el.disabled = true;
            el.value = '';
            el.style.backgroundColor = '#f1f5f9';
        }
    }
}

function completeTask(taskId) {
    document.getElementById('modalTaskId').value = taskId;
    var completeModal = new bootstrap.Modal(document.getElementById('completeTaskModal'));
    completeModal.show();
}

function deleteTask(id, repeatType) {
    document.getElementById('delete_task_id').value = id;
    const futureWrap = document.getElementById('deleteFutureWrap');
    if (repeatType && repeatType.trim() !== '') {
        futureWrap.style.display = 'block';
    } else {
        futureWrap.style.display = 'none';
    }
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteTaskModal'));
    deleteModal.show();
}

function editTask(task) {
    document.getElementById('edit_task_id').value = task.id;
    document.getElementById('edit_title').value = task.title;
    document.getElementById('edit_description').value = task.description;
    document.getElementById('edit_due_date').value = task.due_date;
    document.getElementById('edit_priority').value = task.priority;
    document.getElementById('edit_location').value = task.location;
    document.getElementById('edit_repeat_type').value = task.repeat_type;
    document.getElementById('edit_repeat_until_input').value = task.repeat_until || '';
    
    // Toggle repeat until state
    toggleRepeatUntil(task.repeat_type, 'edit_repeat_until_input');

    if (task.reminder_trigger_time) {
        var dt = task.reminder_trigger_time.replace(' ', 'T').substring(0, 16);
        document.getElementById('edit_reminder_date_time').value = dt;
    } else {
        document.getElementById('edit_reminder_date_time').value = '';
    }

    document.getElementById('edit_max_reminders').value = task.max_reminders;
    
    var editModal = new bootstrap.Modal(document.getElementById('editTaskModal'));
    editModal.show();
}
</script>
