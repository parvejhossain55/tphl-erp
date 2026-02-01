        <?php
        $page_title = "Employee Dashboard ";
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
                                                        <div class="avatar-circle mt-2">

                                                        </div>
                                                        <strong>Assigned by:</strong>
                                                        <p class="ms-2 fw-semibold"><?= htmlspecialchars($task['assigned_by_name'] ?? 'System') ?></p>

                                                    </div>
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
                <p class="text-muted">You're all caught up! No tasks assigned at the moment.</p>
            </div>
        <?php endif; ?>

        <!-- App side area start -->
        <div class="app__slide-wrapper">
            <div class="row">
                <!-- Notifications -->
                <?php if (!empty($notifications)): ?>
                    <div class="row gy-3 mt-3">
                        <?php foreach ($notifications as $note): ?>
                            <div class="col-md-12">
                                <div class="employee-alert-box">
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <div class="d-flex align-items-start gap-2">
                                            <i class="icon-thumbs-up mt-1"></i>
                                            <div>
                                                <strong class="d-block"><?= htmlspecialchars($note['message'] ?? '') ?></strong>
                                                <strong class="d-block">Assigned by: <?= htmlspecialchars($note['assigned_by_name'] ?? 'System') ?></strong>
                                                <div class="row d-flex justify-content-between">
                                                    <span class="text-dark">Start Task:</span>
                                                    <?= !empty($note['start_date']) ? date('d F, Y', strtotime($note['start_date'])) : 'N/A' ?>

                                                    <span class="text-dark">Deadline:</span>
                                                    <?= !empty($note['deadline']) ? date('d F, Y', strtotime($note['deadline'])) : 'N/A' ?>

                                                </div>
                                            </div>
                                        </div>
                                        <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mt-2">No new notifications</div>
                <?php endif; ?>

                <div class="col-md-12">
                    <div class="employee-alert-box">
                        <div class="col-xl-12">
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="icon-thumbs-down"></i>
                                <p>Your loan request <strong><b>hasn't been approved</b></strong> Please reach out to HR for
                                    further assistance.</p>
                                <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close" title=""></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-20">
                <div class="col-xxl-6 col-xl-6 col-lg-6 col-md-6 col-sm-6">
                    <div class="card__wrapper">
                        <div class="d-flex align-items-center gap-sm">
                            <div class="card__icon">
                                <span><i class="fa-sharp fa-regular fa-gear"></i></span>
                            </div>
                            <div class="card__title-wrap">
                                <h6 class="card__sub-title mb-10">Task Assigned</h6>
                                <div class="d-flex flex-wrap align-items-end gap-10">
                                    <h4 class="card__title">313</h4>
                                    <span class="card__desc style_two"><span class="price-increase"><i
                                                class="fa-light fa-arrow-up"></i> +5.15%</span> Than Last Month</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-6 col-xl-6 col-lg-6 col-md-6 col-sm-6">
                    <div class="card__wrapper">
                        <div class="d-flex align-items-center gap-sm">
                            <div class="card__icon">
                                <span><i class="fa-light fa-badge-check"></i></span>
                            </div>
                            <div class="card__title-wrap">
                                <h6 class="card__sub-title mb-10">Available Leaves</h6>
                                <div class="d-flex flex-wrap align-items-end gap-10">
                                    <h3 class="card__title">150</h3>
                                    <span class="card__desc style_two"><span class="price-decrease"><i
                                                class="fa-light fa-arrow-down"></i> +5.5%</span> Than Last Month</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-4 col-xl-12 col-lg-12">
                    <div class="card__thumb employee-bg d-flex align-items-center mb-20" data-background="assets/images/bg/personal.png">
                        <div class="card__thumb-content">
                            <span class="card__thumb-subtitle">Welcome Back</span>
                            <h1 class="card__thumb-title mb-20">Thomas<span class="text-warning"> Edison</span></h1>
                            <div class="card__thumb-btn">
                                <a class="btn btn-white rounded-pill" href="#">Explore Now</a>
                            </div>
                        </div>
                    </div>
                    <div class="card__wrapper">
                        <div class="card__title-wrap d-flex align-items-center justify-content-between mb-20">
                            <h5 class="card__heading-title">Mark Attendance</h5>
                            <div class="card__dropdown">
                                <div class="dropdown">
                                    <button>
                                        <i class="fa-regular fa-ellipsis-vertical"></i>
                                    </button>
                                    <div class="dropdown-list">
                                        <a class="dropdown__item" href="javascript:void(0)">Action</a>
                                        <a class="dropdown__item" href="javascript:void(0)">More Action</a>
                                        <a class="dropdown__item" href="javascript:void(0)">Another Action</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="text-muted">Office Time: 09:00AM to 06:00PM</p>
                        <div class="d-flex align-items-center gap-15">
                            <div class="">
                                <form action="#">
                                    <button type="submit" value="0" name="in" id="clock_in" class="btn btn-primary">Clock
                                        In</button>
                                </form>
                            </div>
                            <div class="">
                                <button type="submit" id="clock_out" class="btn btn-danger">Clock Out</button>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-sm-6">
                    <div class="card__wrapper mb-20">
                        <div class="card__title-wrap d-flex align-items-center justify-content-between mb-20">
                            <h5 class="card__heading-title">Attendance & Leaves</h5>
                            <div class="card__dropdown">
                                <div class="from__input-box">
                                    <select class="js-example-basic-single">
                                        <option value="opt1">2024</option>
                                        <option value="opt2">2024</option>
                                        <option value="opt3">2022</option>
                                        <option value="opt4">2021</option>
                                        <option value="opt5">2020</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="attendance__list">
                            <div class="row g-20">
                                <div class="col-md-4 col-6">
                                    <div class="attendance__details mb-10">
                                        <h4 class="text-primary">10</h4>
                                        <p>Total Leaves</p>
                                    </div>
                                </div>
                                <div class="col-md-4 col-6">
                                    <div class="attendance__details mb-10">
                                        <h4 class="text-secondary">6.5</h4>
                                        <p>Leaves Taken</p>
                                    </div>
                                </div>
                                <div class="col-md-4 col-6">
                                    <div class="attendance__details mb-10">
                                        <h4 class="text-success">06</h4>
                                        <p>Leaves Absent</p>
                                    </div>
                                </div>
                                <div class="col-md-4 col-6">
                                    <div class="attendance__details mb-10">
                                        <h4 class="text-link">1</h4>
                                        <p>Pending Approval</p>
                                    </div>
                                </div>
                                <div class="col-md-4 col-6">
                                    <div class="attendance__details mb-10">
                                        <h4 class="text-info">315</h4>
                                        <p>Working Days</p>
                                    </div>
                                </div>
                                <div class="col-md-4 col-6">
                                    <div class="attendance__details mb-10">
                                        <h4 class="text-danger">3</h4>
                                        <p>Loss of Pay</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="attendance-btn">
                            <a class="btn btn-primary" href="leaves-employee.html">
                                Apply Leave</a>
                        </div>
                    </div>

                </div>


                <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-12">
                    <div class="card__wrapper">
                        <div class="card__title-wrap d-flex align-items-center justify-content-between mb-20">
                            <h5 class="card__heading-title">Recent Activity</h5>
                            <div class="card__dropdown">
                                <div class="dropdown">
                                    <button>
                                        <i class="fa-regular fa-ellipsis-vertical"></i>
                                    </button>
                                    <div class="dropdown-list">
                                        <a class="dropdown__item" href="javascript:void(0)">Action</a>
                                        <a class="dropdown__item" href="javascript:void(0)">More Action</a>
                                        <a class="dropdown__item" href="javascript:void(0)">Another Action</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="scroll-h-5 card__scroll">
                            <div class="card__inner">
                                <ul class="timeline">
                                    <li class="timeline__item d-flex gap-10">
                                        <div class="timeline__icon"><span><i class="fa-light fa-box"></i></span></div>
                                        <div class="timeline__content w-100">
                                            <div class="d-flex flex-wrap gap-10 align-items-center justify-content-between">
                                                <h5 class="small">Purchased from MediaTek</h5>
                                                <span class="bd-badge bg-success">04 Mins Ago</span>
                                            </div>
                                            <p>Successfully integrated new HRM features into the system</p>
                                            <div class="timeline__thumb">
                                                <img src="assets/images/product/item1.png" alt="image">
                                                <img src="assets/images/product/item2.png" alt="image">
                                                <img src="assets/images/product/item3.png" alt="image">
                                            </div>
                                        </div>
                                    </li>
                                    <li class="timeline__item d-flex gap-10">
                                        <div class="timeline__icon"><span><i class="fa-light fa-box"></i></span></div>
                                        <div class="timeline__content w-100">
                                            <div class="d-flex flex-wrap gap-10 align-items-center justify-content-between">
                                                <h5 class="small">CRM Notification</h5>
                                                <span class="bd-badge bg-success">10 Mins Ago</span>
                                            </div>
                                            <p><span class="text-danger text-decoration-underline">3 days left</span> to update
                                                customer profiles with the new CRM tools</p>
                                        </div>
                                    </li>
                                    <li class="timeline__item d-flex gap-10">
                                        <div class="timeline__icon"><span><i class="fa-light fa-box"></i></span></div>
                                        <div class="timeline__content w-100">
                                            <div class="d-flex flex-wrap gap-10 align-items-center justify-content-between">
                                                <h5 class="small">Purchased from MediaTek</h5>
                                                <span class="bd-badge bg-success">30 Mins Ago</span>
                                            </div>
                                            <p>Team collaboration improved with new HRM tools</p>
                                            <div class="avatar">
                                                <ul>
                                                    <li class="d-inline-block"><img class="img-48 border-circle" src="assets/images/avatar/avatar1.png" alt="image"></li>
                                                    <li class="d-inline-block"><img class="img-48 border-circle" src="assets/images/avatar/avatar2.png" alt="image"></li>
                                                    <li class="d-inline-block"><img class="img-48 border-circle" src="assets/images/avatar/avatar3.png" alt="image"></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="timeline__item d-flex gap-10">
                                        <div class="timeline__icon"><span><i class="fa-light fa-box"></i></span></div>
                                        <div class="timeline__content w-100">
                                            <div class="d-flex flex-wrap gap-10 align-items-center justify-content-between">
                                                <h5 class="small">CRM Update</h5>
                                                <span class="bd-badge bg-success">45 Mins Ago</span>
                                            </div>
                                            <p class="mb-10"><span class="text-danger text-decoration-underline">2 days
                                                    left</span> to complete CRM training sessions</p>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>




            </div>
        </div>
        <!-- App side area end -->