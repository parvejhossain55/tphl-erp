<style>
/* Enhanced Todoist Clone Custom CSS */
.todo-container {
    background-color: #fafafa;
    min-height: calc(100vh - 100px);
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.03);
    overflow: hidden;
    margin-bottom: 30px;
}
.todo-wrapper {
    display: flex;
    height: 100%;
}
.todo-sidebar {
    width: 260px;
    background-color: #fcfcfc;
    padding: 30px 15px;
    border-right: 1px solid #f0f0f0;
}
.todo-main {
    flex: 1;
    padding: 40px 60px;
    max-width: 900px;
    background-color: #fff;
    overflow-y: auto;
}
.nav-item-custom {
    padding: 10px 15px;
    border-radius: 8px;
    color: #444;
    text-decoration: none;
    display: flex;
    align-items: center;
    font-size: 15px;
    margin-bottom: 5px;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}
.nav-item-custom:hover {
    background-color: #f1f1f1;
    color: #222;
}
.nav-item-custom i {
    width: 28px;
    font-size: 18px;
}
.nav-item-custom.active {
    background-color: #ffeef0;
    color: #d1453b;
    font-weight: 600;
}
.nav-item-custom.active i {
    color: #d1453b !important;
}
.project-header {
    font-size: 14px;
    font-weight: 700;
    color: #666;
    margin-top: 30px;
    margin-bottom: 12px;
    padding-left: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.project-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 12px;
}
.task-header {
    font-size: 28px;
    font-weight: 800;
    margin-bottom: 30px;
    color: #202020;
    display: flex;
    align-items: center;
}
.task-item {
    display: flex;
    padding: 15px 0;
    border-bottom: 1px solid #f0f0f0;
    align-items: flex-start;
    transition: background-color 0.2s;
}
.task-item:last-child {
    border-bottom: none;
}
.task-item:hover {
    background-color: #fafafa;
    border-radius: 8px;
    padding-left: 10px;
    padding-right: 10px;
    margin-left: -10px;
    margin-right: -10px;
}
.task-checkbox {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: 2px solid #ccc;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    margin-right: 15px;
    margin-top: 2px;
    transition: all 0.2s;
}
.task-checkbox:hover {
    background-color: #f5f5f5;
}
.task-checkbox i {
    font-size: 12px;
    color: white;
    display: none;
}
.task-checkbox.completed { background-color: #808080; border-color: #808080; }
.task-checkbox.completed i { display: block; }
.task-checkbox.p1 { border-color: #d1453b; background-color: rgba(209,69,59,0.05); }
.task-checkbox.p2 { border-color: #eb8909; background-color: rgba(235,137,9,0.05); }
.task-checkbox.p3 { border-color: #246fe0; background-color: rgba(36,111,224,0.05); }
.task-checkbox.p4 { border-color: #ccc; }

.task-content { flex: 1; }
.task-title { font-size: 15px; color: #202020; font-weight: 500; margin-bottom: 4px; }
.task-desc { font-size: 13px; color: #666; margin-bottom: 6px; }
.task-meta { font-size: 12px; color: #888; display: flex; gap: 15px; }

/* Enhanced Add Task Button */
.btn-add-task {
    color: #2ea043;
    background-color: #eaf5eb;
    text-decoration: none;
    font-size: 15px;
    display: inline-flex;
    align-items: center;
    padding: 10px 15px;
    margin-top: 15px;
    cursor: pointer;
    border-radius: 8px;
    transition: all 0.2s;
    font-weight: 600;
}
.btn-add-task:hover {
    background-color: #dcefdc;
    color: #238636;
}
.btn-add-task i {
    margin-right: 10px;
    color: #2ea043;
    font-size: 18px;
}

/* Enhanced Add Task Form */
.add-task-form {
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 20px;
    margin-top: 15px;
    display: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    background-color: #fff;
}
.add-task-form input.task-title-input {
    border: none;
    outline: none;
    font-weight: 600;
    font-size: 16px;
    width: 100%;
    margin-bottom: 10px;
}
.add-task-form input.task-title-input::placeholder { color: #aaa; font-weight: 500; }
.add-task-form input.task-desc-input {
    border: none;
    outline: none;
    font-size: 14px;
    width: 100%;
    color: #555;
    margin-bottom: 20px;
}
.form-actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    border-top: 1px solid #f0f0f0;
    padding-top: 15px;
    gap: 10px;
}
.btn-todo-submit {
    background-color: #2ea043;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 5px;
    font-weight: 600;
    transition: background-color 0.2s;
}
.btn-todo-submit:hover {
    background-color: #238636;
    color: white;
}
.btn-todo-cancel {
    background-color: #f5f5f5;
    color: #444;
    border: 1px solid #ddd;
    padding: 8px 16px;
    border-radius: 5px;
    font-weight: 500;
}
.btn-todo-cancel:hover { background-color: #e5e5e5; }
</style>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12">
            <h4 class="mb-0 text-dark fw-bold">Task Management</h4>
            <p class="text-muted small">Manage your daily tasks and reminders efficiently.</p>
        </div>
    </div>

    <div class="todo-container">
        <div class="todo-wrapper">
            <!-- Sidebar -->
            <div class="todo-sidebar">
                <a href="<?php echo $GLOBALS['base_url']; ?>/todo?view=inbox" class="nav-item-custom <?php echo $viewType == 'inbox' ? 'active' : ''; ?>">
                    <i class="fas fa-inbox text-primary"></i> Inbox
                    <span class="ms-auto text-muted small"><?php echo $counts['inbox_count'] > 0 ? $counts['inbox_count'] : ''; ?></span>
                </a>
                <a href="<?php echo $GLOBALS['base_url']; ?>/todo?view=today" class="nav-item-custom <?php echo $viewType == 'today' ? 'active' : ''; ?>">
                    <i class="far fa-calendar-alt text-success"></i> Today
                    <span class="ms-auto text-muted small"><?php echo $counts['today_count'] > 0 ? $counts['today_count'] : ''; ?></span>
                </a>
                <a href="<?php echo $GLOBALS['base_url']; ?>/todo?view=upcoming" class="nav-item-custom <?php echo $viewType == 'upcoming' ? 'active' : ''; ?>">
                    <i class="far fa-calendar-check text-warning"></i> Upcoming
                    <span class="ms-auto text-muted small"><?php echo $counts['upcoming_count'] > 0 ? $counts['upcoming_count'] : ''; ?></span>
                </a>
                <a href="<?php echo $GLOBALS['base_url']; ?>/todo?view=completed" class="nav-item-custom <?php echo $viewType == 'completed' ? 'active' : ''; ?>">
                    <i class="fas fa-check-double text-secondary"></i> Completed
                </a>

                <div class="project-header">
                    <span>My Projects</span>
                    <i class="fas fa-plus text-muted" style="cursor:pointer;" title="Add Project"></i>
                </div>
                
                <?php foreach($projects as $p): if($p['is_inbox']) continue; ?>
                <a href="<?php echo $GLOBALS['base_url']; ?>/todo?view=project&project_id=<?php echo $p['id']; ?>" class="nav-item-custom <?php echo ($viewType == 'project' && $projectId == $p['id']) ? 'active' : ''; ?>">
                    <span class="project-dot" style="background-color: <?php echo $p['color_code']; ?>"></span>
                    <?php echo htmlspecialchars($p['name']); ?>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Main Content -->
            <div class="todo-main">
                <div class="task-header">
                    <?php echo htmlspecialchars($pageTitle ?? 'Inbox'); ?>
                </div>

                <div class="task-list">
                    <?php if(empty($tasks)): ?>
                        <div class="text-center py-5 text-muted">
                            <div class="mb-4 text-success" style="font-size: 60px; opacity: 0.5;">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <h5 class="fw-bold text-dark">All clear</h5>
                            <p>Looks like everything's organized in the right place.</p>
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
                                    <span class="text-danger fw-bold"><i class="far fa-calendar"></i> <?php echo date('d M', strtotime($t['due_date'])); ?></span>
                                    <?php endif; ?>
                                    
                                    <?php if($t['project_name']): ?>
                                    <span><i class="fas fa-circle" style="color: <?php echo $t['project_color']; ?>; font-size: 8px;"></i> <?php echo htmlspecialchars($t['project_name']); ?></span>
                                    <?php endif; ?>
                                    
                                    <span style="color: <?php echo $t['priority'] == 1 ? '#d1453b' : ($t['priority'] == 2 ? '#eb8909' : ($t['priority'] == 3 ? '#246fe0' : '#808080')); ?>; font-size: 11px; font-weight: bold;">
                                        <i class="fas fa-flag"></i> P<?php echo $t['priority']; ?>
                                    </span>
                                    
                                    <?php if(!empty($t['location'])): ?>
                                    <span class="text-muted"><i class="fas fa-map-marker-alt text-primary"></i> <?php echo htmlspecialchars($t['location']); ?></span>
                                    <?php endif; ?>
                                    
                                    <?php if(!empty($t['attachment'])): ?>
                                    <a href="<?php echo $GLOBALS['base_url']; ?>/<?php echo htmlspecialchars($t['attachment']); ?>" target="_blank" class="text-decoration-none text-muted" title="View Attachment">
                                        <i class="fas fa-paperclip text-info"></i> Attachment
                                    </a>
                                    <?php endif; ?>

                                    <?php if(!empty($t['repeat_type'])): ?>
                                    <span class="text-info fw-bold" style="font-size: 11px;"><i class="fas fa-redo"></i> <?php echo ucfirst($t['repeat_type']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="ms-auto ps-3 align-self-center d-flex align-items-center" style="opacity: 0.6;">
                                <button type="button" class="btn btn-sm text-secondary border-0 p-0 me-3" onclick='editTask(<?php echo json_encode([
                                    "id" => $t["id"],
                                    "title" => htmlspecialchars($t["title"]),
                                    "description" => htmlspecialchars($t["description"] ?? ""),
                                    "due_date" => $t["due_date"] ? date("Y-m-d", strtotime($t["due_date"])) : "",
                                    "priority" => $t["priority"],
                                    "location" => htmlspecialchars($t["location"] ?? ""),
                                    "repeat_type" => $t["repeat_type"] ?? "",
                                    "max_reminders" => $t["max_reminders"] ?? 0
                                ], JSON_HEX_APOS); ?>)' title="Edit Task">
                                    <i class="fas fa-pen" style="font-size: 15px;"></i>
                                </button>
                                <button type="button" class="btn btn-sm text-danger border-0 p-0" onclick="deleteTask(<?php echo $t['id']; ?>, '<?php echo $t['repeat_type'] ?? ''; ?>')" title="Delete Task">
                                    <i class="far fa-trash-alt" style="font-size: 16px;"></i>
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
                    <i class="fas fa-plus-circle"></i> Add task
                </a>

                <!-- Add Task Form -->
                <div class="add-task-form" id="addTaskForm">
                    <form action="<?php echo $GLOBALS['base_url']; ?>/todo/store" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="todo_project_id" value="<?php echo $inboxId; ?>">
                        
                        <input type="text" name="title" class="task-title-input" placeholder="Task name" required autocomplete="off">
                        <input type="text" name="description" class="task-desc-input" placeholder="Description (optional)" autocomplete="off">
                        
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <div class="input-group input-group-sm" style="width: auto;">
                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="far fa-calendar"></i></span>
                                <input type="date" name="due_date" class="form-control border-start-0" title="Due Date">
                            </div>
                            
                            <select name="priority" class="form-select form-select-sm" style="width: auto;">
                                <option value="4">Priority 4</option>
                                <option value="3">Priority 3</option>
                                <option value="2">Priority 2</option>
                                <option value="1">Priority 1 (Highest)</option>
                            </select>
                        </div>
                        
                        <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
                            <div class="input-group input-group-sm" style="width: 250px;">
                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-map-marker-alt"></i></span>
                                <input type="text" name="location" class="form-control border-start-0" placeholder="Location/Deadline (optional)">
                            </div>
                            
                            <div class="input-group input-group-sm" style="width: auto;">
                                <span class="input-group-text bg-white text-muted"><i class="fas fa-paperclip"></i> File</span>
                                <input type="file" name="attachment" class="form-control" style="font-size: 12px;">
                            </div>
                        </div>

                        <!-- Repeating Logic & Reminder Limit UI -->
                        <div class="card border-0 bg-light mt-3 mb-2">
                            <div class="card-body p-3">
                                <h6 class="card-title text-muted mb-3" style="font-size:13px; font-weight:bold;">
                                    <i class="fas fa-bell text-warning"></i> Advanced Reminders & Repeat
                                </h6>
                                <div class="row ">
                                    <div class="col-md-6">
                                        <label class="form-label" style="font-size:12px;">Repeat Task</label>
                                        <select name="repeat_type" class="form-select form-select-sm">
                                            <option value="">Do not repeat</option>
                                            <option value="daily">Daily</option>
                                            <option value="weekly">Weekly</option>
                                            <option value="monthly">Monthly</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" style="font-size:12px;">Email Reminder Count</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white">Send Max</span>
                                            <input type="number" name="max_reminders" class="form-control" value="0" min="0" placeholder="e.g. 3">
                                            <span class="input-group-text bg-white">Times</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn btn-todo-cancel" onclick="hideAddTaskForm()">Cancel</button>
                            <button type="submit" class="btn btn-todo-submit">Add task</button>
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
    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
      <div class="modal-body text-center p-4">
        <div class="mb-3 text-success" style="font-size: 40px;">
            <i class="fas fa-check-circle"></i>
        </div>
        <h5 class="fw-bold mb-2">Complete Task?</h5>
        <p class="text-muted small mb-4">Are you sure you want to mark this task as completed?</p>
        <form action="<?php echo $GLOBALS['base_url']; ?>/todo/complete" method="POST" id="completeTaskForm">
            <input type="hidden" name="task_id" id="modalTaskId">
            <input type="hidden" name="redirect_view" value="<?php echo htmlspecialchars($viewType); ?>">
            <div class="d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="font-weight: 500;">Cancel</button>
                <button type="submit" class="btn btn-success px-4" style="font-weight: 600; background-color: #2ea043; border: none;">Yes, Complete</button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Task Delete Modal -->
<div class="modal fade" id="deleteTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-body text-center p-4">
                <div class="mb-3 text-danger" style="font-size: 40px;">
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
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="font-weight: 500;">Cancel</button>
                        <button type="submit" class="btn btn-danger px-4" style="font-weight: 600; border: none;">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Task Edit Modal -->
<div class="modal fade" id="editTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-body p-4">
                <h5 class="fw-bold mb-3"><i class="fas fa-pen text-primary"></i> Edit Task</h5>
                <form action="<?php echo $GLOBALS['base_url']; ?>/todo/update" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="task_id" id="edit_task_id" value="">
                    <input type="hidden" name="redirect_view" value="<?php echo htmlspecialchars($viewType); ?>">
                    
                    <div class="mb-3">
                        <input type="text" name="title" id="edit_title" class="form-control fw-bold border-0 bg-light" style="font-size: 16px;" placeholder="Task name" required autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <input type="text" name="description" id="edit_description" class="form-control border-0 bg-light" style="font-size: 14px;" placeholder="Description (optional)" autocomplete="off">
                    </div>
                    
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <div class="input-group input-group-sm" style="width: auto;">
                            <span class="input-group-text bg-light border-0 text-muted"><i class="far fa-calendar"></i></span>
                            <input type="date" name="due_date" id="edit_due_date" class="form-control border-0 bg-light">
                        </div>
                        
                        <select name="priority" id="edit_priority" class="form-select form-select-sm border-0 bg-light" style="width: auto;">
                            <option value="4">Priority 4 (Gray)</option>
                            <option value="3">Priority 3 (Blue)</option>
                            <option value="2">Priority 2 (Orange)</option>
                            <option value="1">Priority 1 (Red)</option>
                        </select>
                    </div>
                    
                    <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
                        <div class="input-group input-group-sm" style="width: 200px;">
                            <span class="input-group-text bg-light border-0 text-muted"><i class="fas fa-map-marker-alt"></i></span>
                            <input type="text" name="location" id="edit_location" class="form-control border-0 bg-light" placeholder="Location/Deadline">
                        </div>
                        
                        <div class="input-group input-group-sm" style="width: auto;">
                            <span class="input-group-text bg-light text-muted border-0"><i class="fas fa-paperclip"></i> File</span>
                            <input type="file" name="attachment" class="form-control border-0 bg-light" style="font-size: 12px;">
                        </div>
                    </div>

                    <div class="card border-0 bg-light mt-3 mb-4 rounded-3">
                        <div class="card-body p-3">
                            <h6 class="card-title text-muted mb-3" style="font-size:13px; font-weight:bold;">
                                <i class="fas fa-bell text-warning"></i> Advanced Reminders & Repeat
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label text-muted" style="font-size:12px;">Repeat Task</label>
                                    <select name="repeat_type" id="edit_repeat_type" class="form-select form-select-sm border-0">
                                        <option value="">Do not repeat</option>
                                        <option value="daily">Daily</option>
                                        <option value="weekly">Weekly</option>
                                        <option value="monthly">Monthly</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted" style="font-size:12px;">Email Reminder Count</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white border-0">Max</span>
                                        <input type="number" name="max_reminders" id="edit_max_reminders" class="form-control border-0" value="0" min="0">
                                        <span class="input-group-text bg-white border-0">Times</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4" style="background-color: #2ea043; border: none;">Save Changes</button>
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
    document.getElementById('edit_max_reminders').value = task.max_reminders;
    
    var editModal = new bootstrap.Modal(document.getElementById('editTaskModal'));
    editModal.show();
}
</script>
