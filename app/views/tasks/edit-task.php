  <?php
    $page_title = "Edit Task";
    $page = 'Task Edit';
    $show_breadcrumb = true;
    ?>


  <div class="row">
      <div class="card__wrapper">
          <form action="<?= $GLOBALS['base_url'] ?>/update-task/<?= $task['id'] ?>" method="POST">
              <div class="row">
                  <div class="col-xxl-12">
                      <div class="row gy-10">
                          <div class="col-xl-12">
                              <div class="card__wrapper">
                                  <div class="row gy-20">

                                      <!-- Task Title -->
                                      <div class="col-lg-6">
                                          <div class="from__input-box">
                                              <div class="form__input-title">
                                                  <label for="title">Task Title<span>*</span></label>
                                              </div>
                                              <div class="form__input">
                                                  <input class="form-control" name="title" id="title" type="text"
                                                      value="<?= htmlspecialchars($task['title']) ?>">
                                              </div>
                                          </div>
                                      </div>

                                      <!-- Assigned Employee -->
                                      <div class="col-lg-6">
                                          <div class="from__input-box">
                                              <div class="form__input-title">
                                                  <label for="user_id">Assigned Employee <span>*</span></label>
                                              </div>
                                              <div class="form__input">
                                                  <select id="user_id" name="user_id" class="form-select select2">
                                                      <option value="">Select Employee</option>
                                                      <?php foreach ($users as $employee): ?>
                                                          <option value="<?= $employee['id'] ?>"
                                                              <?= $employee['id'] == $task['user_id'] ? 'selected' : '' ?>>
                                                              <?= htmlspecialchars($employee['full_name']) ?><?php if (!empty($employee['employee_code'])): ?> (<?= htmlspecialchars($employee['employee_code']) ?>)<?php endif; ?>
                                                          </option>
                                                      <?php endforeach; ?>
                                                  </select>
                                              </div>
                                          </div>
                                      </div>

                                      <?php
                                      $selectedMentions = [];
                                      if (!empty($task['mentioned_user_ids'])) {
                                          $decoded = json_decode($task['mentioned_user_ids'], true);
                                          $selectedMentions = is_array($decoded) ? $decoded : explode(',', $task['mentioned_user_ids']);
                                      }
                                      $selectedCcs = [];
                                      if (!empty($task['cc_user_ids'])) {
                                          $decoded = json_decode($task['cc_user_ids'], true);
                                          $selectedCcs = is_array($decoded) ? $decoded : explode(',', $task['cc_user_ids']);
                                      }
                                      ?>

                                      <!-- Mention Team Mate -->
                                      <div class="col-lg-6">
                                          <div class="from__input-box">
                                              <div class="form__input-title">
                                                  <label for="mention_user_ids"><i class="fa-solid fa-at text-primary me-1"></i> Mention Team Mate</label>
                                              </div>
                                              <div class="form__input">
                                                  <select id="mention_user_ids" name="mention_user_ids[]" class="form-select select2" multiple data-placeholder="Select team mate to mention...">
                                                      <?php foreach ($users as $employee): ?>
                                                          <option value="<?= $employee['id'] ?>" <?= in_array($employee['id'], $selectedMentions) ? 'selected' : '' ?>>
                                                              <?= htmlspecialchars($employee['full_name']) ?><?php if (!empty($employee['employee_code'])): ?> (<?= htmlspecialchars($employee['employee_code']) ?>)<?php endif; ?>
                                                          </option>
                                                      <?php endforeach; ?>
                                                  </select>
                                              </div>
                                          </div>
                                      </div>

                                      <!-- CC User -->
                                      <div class="col-lg-6">
                                          <div class="from__input-box">
                                              <div class="form__input-title">
                                                  <label for="cc_user_ids"><i class="fa-solid fa-envelope-open-text text-info me-1"></i> CC User</label>
                                              </div>
                                              <div class="form__input">
                                                  <select id="cc_user_ids" name="cc_user_ids[]" class="form-select select2" multiple data-placeholder="Select users for CC...">
                                                      <?php foreach ($users as $employee): ?>
                                                          <option value="<?= $employee['id'] ?>" <?= in_array($employee['id'], $selectedCcs) ? 'selected' : '' ?>>
                                                              <?= htmlspecialchars($employee['full_name']) ?><?php if (!empty($employee['employee_code'])): ?> (<?= htmlspecialchars($employee['employee_code']) ?>)<?php endif; ?>
                                                          </option>
                                                      <?php endforeach; ?>
                                                  </select>
                                              </div>
                                          </div>
                                      </div>

                                      <!-- Start Date -->
                                      <div class="col-md-6">
                                          <div class="from__input-box">
                                              <div class="form__input-title">
                                                  <label>Start Date <span>*</span></label>
                                              </div>
                                              <div class="form__input">
                                                  <input class="form-control" id="startingDate" type="text" readonly
                                                      name="start_date" value="<?= $task['start_date'] ?>">
                                              </div>
                                          </div>
                                      </div>

                                      <!-- Deadline -->
                                      <div class="col-md-6">
                                          <div class="from__input-box">
                                              <div class="form__input-title">
                                                  <label>Deadline <span>*</span></label>
                                              </div>
                                              <div class="form__input">
                                                  <input class="form-control" id="humanFriendlyDates" type="text" readonly
                                                      name="deadline" value="<?= $task['deadline'] ?>">
                                              </div>
                                          </div>
                                      </div>

                                      <!-- Priority -->
                                      <div class="col-lg-6">
                                          <div class="from__input-box">
                                              <div class="form__input-title">
                                                  <label for="projectPriority">Priority <span>*</span></label>
                                              </div>
                                              <div class="form__input">
                                                  <select id="projectPriority" name="priority" class="form-select">
                                                      <?php
                                                        $priorities = ['low', 'medium', 'high', 'urgent'];
                                                        foreach ($priorities as $p): ?>
                                                          <option value="<?= $p ?>" <?= $task['priority'] == $p ? 'selected' : '' ?>>
                                                              <?= ucfirst($p) ?>
                                                          </option>
                                                      <?php endforeach; ?>
                                                  </select>
                                              </div>
                                          </div>
                                      </div>

                                      <!-- Status -->
                                      <div class="col-lg-6">
                                          <div class="from__input-box">
                                              <div class="form__input-title">
                                                  <label for="projectStatus">Status <span>*</span></label>
                                              </div>
                                              <div class="form__input">
                                                  <select id="projectStatus" name="status" class="form-select">
                                                      <?php
                                                        $statuses = ['pending', 'in_progress', 'complete', 'expired'];
                                                        foreach ($statuses as $s): ?>
                                                          <option value="<?= $s ?>" <?= $task['status'] == $s ? 'selected' : '' ?>>
                                                              <?= ucwords(str_replace('_', ' ', $s)) ?>
                                                          </option>
                                                      <?php endforeach; ?>
                                                  </select>
                                              </div>
                                          </div>
                                      </div>

                                      <!-- Description -->
                                      <!-- Amenities -->
                                      <div class="col-12">
                                          <label>Task Description</label>
                                            <textarea name="description" id="tinymce_simple_textarea">
                                                <?= htmlspecialchars($task['description'] ?? '') ?>
                                            </textarea>
                                      </div>


                                  </div>
                              </div>
                          </div>
                      </div>
                  </div>

                  <!-- Submit Button -->
                  <div class="submit__btn text-center mb-20">
                      <button class="btn btn-primary">Update Task</button>
                  </div>
              </div>
          </form>
      </div>
  </div>