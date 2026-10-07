  <?php
    $page_title = "Task List";
    $page = 'Task Lists';
    $show_breadcrumb = true;
    ?>
<style>
@media (max-width: 991.98px) {
    /* Make task action buttons equal width */
    .task__btn {
        gap: 10px !important;
    }
    .task__btn a {
        flex: 1 !important;
        text-align: center !important;
    }
}
.rating-stars {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 2px;
}
.rating-stars input {
    display: none;
}
.rating-stars label {
    cursor: pointer;
    font-size: 20px;
    color: #e4e5e9;
    transition: all 0.2s ease-in-out;
    margin: 0;
    line-height: 1;
}
.rating-stars input:checked ~ label,
.rating-stars label:hover,
.rating-stars label:hover ~ label {
    color: #ffc107;
    transform: scale(1.15);
}
</style>

  <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

      <h4 class="mb-2 mb-md-0">
          Task Management
      </h4>

      <div class="d-flex gap-2">
          <a href="<?= $GLOBALS['base_url'] ?>/create-task" class="btn btn-primary"> <i class="fa-light fa-plus me-1"></i> Add Task</a>
      </div>

  </div>

  <?php if (isset($_SESSION['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="bi bi-check-circle me-1"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
  <?php endif; ?>
  <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="bi bi-exclamation-triangle me-1"></i> <?= $_SESSION['error']; unset($_SESSION['error']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
  <?php endif; ?>

  <div class="row align-items-stretch">
      <?php foreach ($tasks as $task): ?>

          <?php
            $deadline = !empty($task['deadline']) ? $task['deadline'] : null;
            // print_r($tasks);
            ?>

          <div class="col-xxl-4 col-md-6 col-12 d-flex">

              <div class="card__wrapper flex-column w-100 h-100">
                  <div class="task__wrapper flex-grow-1">
                      <div class="task__top-content">
                          <h5 class="mb-8"><a href="task-details.html"><?= $task['title'] ?></a>
                          </h5>
                          <div class="d-flex align-items-center g-5 ">
                              <i class="fa-solid fa-circle blink"></i><span class="d-block"><?= $task['status'] ?></span>
                          </div>
                          <div class="fs-14 d-none mb-15">
                              <span>5</span> <span class="text-muted">open tasks,</span>
                              <span>10</span> <span class="text-muted">tasks have been completed</span>
                          </div>
                      </div>
                      <p class="task__description mb-15"><?= $task['description'] ?></p>
                      <div class="d-flex justify-content-between">
                          <div class="task__deadline mb-20">
                              <span class="task__meta-title">Start:</span>
                              <div class="text-muted"><?= $task['start_date'] ? date('d F, Y', strtotime($task['start_date'])) : '' ?>
                              </div>
                          </div>
                          <div class="task__deadline mb-20">
                              <span class="task__meta-title">Deadline:</span>
                              <div class="text-muted"><?= $task['deadline'] ? date('d F, Y', strtotime($task['deadline'])) : '' ?>
                              </div>
                          </div>
                      </div>
                  </div>

                  <div class="task__team mb-20 d-flex flex-column gap-3">
                      <div class="assign-by">
                          <span class="task__meta-title d-block mb-1">Assigned By:</span>
                          <div class="d-flex align-items-center gap-2">
                              <img class="img-36 border-circle" src="<?= getImage('users', $task['user_photo']) ?>" alt="image" style="width: 36px; height: 36px; object-fit: cover; object-position: top; border-radius: 50%;">
                              <div class="d-flex flex-column">
                                  <span class="fw-semibold">
                                      <?= $task['assigned_by_name'] ?>
                                  </span>
                                  <small class="text-muted">
                                      <?= $task['assigned_designation'] ?>
                                  </small>
                              </div>

                          </div>
                      </div>
                      <div class="assign-to">
                          <span class="task__meta-title d-block mb-1">Assigned To:</span>

                          <div class="d-flex align-items-center gap-2">
                              <img
                                  class="img-36 border-circle"
                                  src="<?= getImage('users', $task['employee_photo']) ?>"
                                  alt="image"
                                  style="width: 36px; height: 36px; object-fit: cover; object-position: top; border-radius: 50%;">

                              <div class="d-flex flex-column">
                                  <span class="fw-semibold">
                                      <?= $task['employee_name'] ?>
                                  </span>
                                  <small class="text-muted">
                                      <?= $task['user_designation'] ?>
                                  </small>
                              </div>
                          </div>
                      </div>
                  </div>
                  <div class="task__progress">
                      <div class="d-flex justify-content-between mb-8">
                          <span class="task__meta-title">Task Progress</span>

                          <span class="task__meta-title"><?= $task['progress'] ?? 0 ?>%</span>
                      </div>
                      <div class="progress">
                          <div class="progress-bar" role="progressbar" style="width: <?= $task['progress'] ?>%" aria-valuenow=" <?= $task['progress'] ?>" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip"
                              title="<?= $task['status'] ?>">

                          </div>
                      </div>
                      <div class="reamaining-time my-3 d-flex justify-content-between gap-2">
                          <div class="time">
                              <label>Task Remaining Time</label>
                              <span class="count-down bd-badge bg-danger"
                                  id="timer-<?= $task['id']; ?>"
                                  data-deadline="<?= $deadline ?>">
                                  Loading...
                              </span>
                          </div>

                          <div class="">
                              <label>Project Status</label>
                              <span class="count-down bd-badge bg-primary ">On Going</span>
                          </div>
                      </div>

                  </div>
                  <!-- Rating Section -->
                  <?php if (strtolower($task['status']) === 'completed' || $task['progress'] >= 100): ?>
                      <div class="mt-2 mb-3 p-2 bg-light rounded border border-light-subtle">
                          <div class="d-flex justify-content-between align-items-center">
                              <span class="fw-semibold text-dark fs-13"><i class="bi bi-star-fill text-warning me-1"></i> Rating</span>
                              <?php if (!empty($task['satisfaction_rating'])): ?>
                                  <div class="mt-2 w-100 p-2 rounded" style="background: #ffffff; border: 1px solid #e8e6ff; box-shadow: 0 2px 8px rgba(94, 76, 220, 0.05);">
                                      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                          <div class="d-flex align-items-center gap-1">
                                              <?php for($i=1; $i<=10; $i++): ?>
                                                  <i class="bi bi-star<?= $i <= $task['satisfaction_rating'] ? '-fill' : '' ?>" style="font-size: 14px; color: <?= $i <= $task['satisfaction_rating'] ? '#ffc107' : '#e4e5e9' ?>;"></i>
                                              <?php endfor; ?>
                                          </div>
                                          <span class="badge px-2 py-1" style="background: #fff8e1; color: #f57f17; border: 1px solid #ffe082; font-size: 11px; font-weight: bold;">
                                              <?= $task['satisfaction_rating'] ?> / 10
                                          </span>
                                      </div>
                                  </div>
                              <?php else: ?>
                                  <span class="badge bg-secondary bg-opacity-10 px-2 py-1 fs-12">Unrated</span>
                              <?php endif; ?>
                          </div>
                          
                          <?php if (empty($task['satisfaction_rating']) && (($_SESSION['user']['id'] ?? 0) == $task['assigned_by'] || in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 4]))): ?>
                              <form action="<?= $GLOBALS['base_url'] ?>/rate-task" method="POST" class="mt-3 w-100">
                                  <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                  <div class="d-flex align-items-center justify-content-between bg-white px-3 py-2 rounded shadow-sm border">
                                      <span class="text-muted fs-12 fw-medium me-2">Rate:</span>
                                      <div class="rating-stars">
                                          <?php for($i=10; $i>=1; $i--): ?>
                                              <input type="radio" id="star<?= $i ?>-<?= $task['id'] ?>" name="satisfaction_rating" value="<?= $i ?>" onchange="this.form.submit()">
                                              <label for="star<?= $i ?>-<?= $task['id'] ?>" title="<?= $i ?>/10"><i class="bi bi-star-fill"></i></label>
                                          <?php endfor; ?>
                                      </div>
                                  </div>
                              </form>
                          <?php endif; ?>
                      </div>
                  <?php endif; ?>
                  <div class="task__btn mt-auto d-flex justify-content-between align-items-center gap-2">
                      <a class="btn btn-sm btn-outline-theme-border flex-grow-1 text-center" href="<?= $base_url ?>/show-task/<?= $task['id'] ?>">View</a>
                      <a class="btn btn-sm btn-outline-theme-border flex-grow-1 text-center" href="<?= $base_url ?>/edit-task/<?= $task['id'] ?>">Edit</a>
                      <a class="btn btn-sm btn-outline-danger px-3 text-center" href="<?= $base_url ?>/delete-task/<?= $task['id'] ?>" onclick="return confirm('Are you sure you want to permanently delete this task from everywhere?');" title="Delete Task">
                          <i class="fa-solid fa-trash-can me-1"></i> Delete
                      </a>
                  </div>
              </div>
          </div>
      <?php endforeach ?>
  </div>
  <!-- Pagination -->
<div class="row my-4">
    <div class="col-12 d-flex justify-content-center">
        <?= paginateLinksSecondary($base_url . "/task-lists", $currentPage, $totalPages); ?>


    </div>
</div>