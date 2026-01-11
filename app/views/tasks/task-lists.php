  <?php
    $page_title = "Task List";
    $page = 'Task Lists';
    $show_breadcrumb = true;
    ?>

<div class="row">
    <h2></h2>
</div>

  <div class="row align-items-stretch">
      <?php foreach ($tasks as $task): ?>

          <?php
            $deadline = !empty($task['deadline']) ? $task['deadline'] : null;
            // print_r($tasks);
            ?>

          <div class="col-xxl-4 d-flex">

              <div class="card__wrapper flex-column w-100 h-100">
                  <div class="task__wrapper flex-grow-1">
                      <div class="task__top-content">
                          <h5 class="mb-8"><a href="task-details.html"><?= $task['title'] ?></a>
                          </h5>
                          <div class="d-flex align-items-center g-5 text-success">
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

                  <div class="task__team mb-20 d-flex justify-content-between">
                      <div class="assign-by">
                          <span class="task__meta-title mb-8">Assigned By:</span>
                          <div class="avatar">
                              <ul>
                                  <li><img class="img-36 border-circle" src="<?= getImage('users', $task['user_photo']) ?>" alt="image"><span><?= $task['assigned_by_name'] ?></span>
                                  </li>

                              </ul>
                          </div>
                      </div>
                      <div class="assign-to">
                          <span class="task__meta-title mb-8">Assigned To:</span>
                          <div class="avatar">
                              <ul>
                                  <li><img class="img-36 border-circle" src="<?= getImage('employees', $task['employee_photo']) ?>" alt="image"><span><?= $task['employee_name'] ?></span>
                                  </li>

                              </ul>
                          </div>
                      </div>


                  </div>
                  <div class="task__progress">
                      <div class="d-flex justify-content-between mb-8">
                          <span class="task__meta-title">Task Progress</span>

                          <span class="task__meta-title">%</span>
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
                  <div class="task__btn mt-auto d-flex justify-content-between">
                      <a class="btn btn-outline-theme-border" href="<?= $base_url ?>/show-task/<?= $task['id'] ?>">View Task</a>
                      <a class="btn btn-outline-theme-border" href="<?= $base_url ?>/edit-task/<?= $task['id'] ?>">Edit Task</a>

                  </div>
              </div>
          </div>
      <?php endforeach ?>
  </div>

