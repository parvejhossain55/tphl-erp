  <?php
    $page_title = "Create Project";
    $page = 'Task Create';
    $show_breadcrumb = true;
    ?>

  <div class="row">
      <div class="card__wrapper">

          <form action="<?= $GLOBALS['base_url'] ?>/store-task" method="POST">
              <div class="row">
                  <div class="col-xxl-12">


                      <div class="row gy-10">
                          <div class="col-xl-12">
                              <div class="card__wrapper">
                                  <div class="row gy-20">
                                      <div class="col-lg-6">
                                          <div class="from__input-box">
                                              <div class="form__input-title">
                                                  <label for="title">Task Title<span>*</span></label>
                                              </div>
                                              <div class="form__input">
                                                  <input class="form-control" name="title" id="title" type="text" required>
                                              </div>
                                          </div>
                                      </div>

                                      <div class="col-lg-6">
                                          <div class="from__input-box">
                                              <div class="form__input-title">
                                                  <label for="project_id" class="form-label"><i class="fa-solid fa-folder-tree text-success me-1"></i> Project / Team Project</label>
                                              </div>
                                              <div class="form__input">
                                                  <select id="project_id" name="project_id" class="form-select select2" onchange="handleProjectChange(this.value)">
                                                      <option value="">No Project (General Task)</option>
                                                      <?php if (!empty($projects)): ?>
                                                          <optgroup label="Shared Team Projects">
                                                              <?php foreach ($projects as $pr): ?>
                                                                  <?php if (!empty($pr['is_team_project'])): ?>
                                                                      <option value="<?= $pr['id'] ?>">👥 [Team] <?= htmlspecialchars($pr['name']) ?></option>
                                                                  <?php endif; ?>
                                                              <?php endforeach; ?>
                                                          </optgroup>
                                                          <optgroup label="Personal / Other Projects">
                                                              <?php foreach ($projects as $pr): ?>
                                                                  <?php if (empty($pr['is_team_project'])): ?>
                                                                      <option value="<?= $pr['id'] ?>">📁 <?= htmlspecialchars($pr['name']) ?></option>
                                                                  <?php endif; ?>
                                                              <?php endforeach; ?>
                                                          </optgroup>
                                                      <?php endif; ?>
                                                      <option value="new">+ Create New Project / Team Project...</option>
                                                  </select>
                                              </div>
                                              <div id="newProjectWrap" style="display: none;" class="mt-2 p-3 bg-light rounded-3 border">
                                                  <label class="form-label small fw-bold text-dark mb-1">New Project Name</label>
                                                  <input type="text" name="new_project_name" id="new_project_name" class="form-control mb-2" placeholder="e.g. Sales Campaign Q4, ERP Deployment">
                                                  <div class="form-check">
                                                      <input class="form-check-input" type="checkbox" name="is_team_project" id="is_team_project" value="1" checked>
                                                      <label class="form-check-label small fw-semibold text-secondary" for="is_team_project">
                                                          <i class="fa-solid fa-users text-primary me-1"></i> Share as Team Project (Visible to all members in department/team)
                                                      </label>
                                                  </div>
                                              </div>
                                          </div>
                                      </div>


                                       <div class="col-lg-6">
                                           <div class="from__input-box">
                                               <div class="form__input-title">
                                                   <label for="user_id" class="form-label">Assigned Employee <span>*</span></label>
                                               </div>
                                               <div class="form__input">
                                                   <select id="user_id" name="user_id" class="form-select select2" required>
                                                       <option value="">Select Employee</option>
                                                       <?php foreach ($users as $user): ?>
                                                           <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['full_name']) ?><?php if (!empty($user['employee_code'])): ?> (<?= htmlspecialchars($user['employee_code']) ?>)<?php endif; ?></option>
                                                       <?php endforeach; ?>
                                                   </select>
                                               </div>
                                           </div>
                                       </div>

                                       <div class="col-lg-6">
                                           <div class="from__input-box">
                                               <div class="form__input-title">
                                                   <label for="mention_user_ids" class="form-label"><i class="fa-solid fa-at text-primary me-1"></i> Mention Team Mate</label>
                                               </div>
                                               <div class="form__input">
                                                   <select id="mention_user_ids" name="mention_user_ids[]" class="form-select select2" multiple data-placeholder="Select team mate to mention...">
                                                       <?php foreach ($users as $user): ?>
                                                           <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['full_name']) ?><?php if (!empty($user['employee_code'])): ?> (<?= htmlspecialchars($user['employee_code']) ?>)<?php endif; ?></option>
                                                       <?php endforeach; ?>
                                                   </select>
                                               </div>
                                           </div>
                                       </div>

                                       <div class="col-lg-6">
                                           <div class="from__input-box">
                                               <div class="form__input-title">
                                                   <label for="cc_user_ids" class="form-label"><i class="fa-solid fa-envelope-open-text text-info me-1"></i> CC User (Optional)</label>
                                               </div>
                                               <div class="form__input">
                                                   <select id="cc_user_ids" name="cc_user_ids[]" class="form-select select2" multiple data-placeholder="Select users for CC...">
                                                       <?php foreach ($users as $user): ?>
                                                           <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['full_name']) ?><?php if (!empty($user['employee_code'])): ?> (<?= htmlspecialchars($user['employee_code']) ?>)<?php endif; ?></option>
                                                       <?php endforeach; ?>
                                                   </select>
                                               </div>
                                           </div>
                                       </div>

                                      <div class="col-md-6">
                                          <div class="from__input-box">
                                              <div class="form__input-title">
                                                  <label>Start Date <span>*</span></label>
                                              </div>
                                              <div class="form__input">
                                                  <input class="form-control" id="startingDate" type="text" placeholder="" readonly="readonly" name="start_date">
                                              </div>
                                          </div>
                                      </div>
                                      <div class="col-md-6">
                                          <div class="from__input-box">
                                              <div class="form__input-title">
                                                  <label>Deadline<span>*</span></label>
                                              </div>
                                              <div class="form__input">
                                                  <input class="form-control" id="humanFriendlyDates" type="text" placeholder="" readonly="readonly" name="deadline">
                                              </div>
                                          </div>
                                      </div>
                                      <div class="col-lg-6">
                                          <div class="from__input-box">
                                              <div class="form__input-title">
                                                  <label for="projectPriority" class="form-label">Priority<span>*</span></label>
                                              </div>
                                              <div class="form__input">
                                                  <select id="projectPriority" name="priority" class="form-select">
                                                      <option value="low">Low</option>
                                                      <option value="medium">Medium</option>
                                                      <option value="high" selected>High</option>
                                                      <option value="urgent">Urgent</option>
                                                  </select>
                                              </div>
                                          </div>
                                      </div>
                                      <div class="col-lg-6">
                                          <div class="from__input-box">
                                              <div class="form__input-title">
                                                  <label for="projectStatus" class="form-label">Status
                                                      <span>*</span></label>
                                              </div>
                                              <div class="form__input">
                                                  <select id="projectStatus" class="form-select" name="status">
                                                      <option value="pending">Pending</option>
                                                      <option value="in_progress">In Progress</option>
                                                      <option value="complete">Complete</option>
                                                      <option value="expired">Expired</option>
                                                  </select>
                                              </div>
                                          </div>
                                      </div>

                                      <div class="col-md-12">
                                          <div class="from__input-box">
                                              <div class="form__input-title">
                                                  <label>task Description <span>*</span></label>
                                              </div>
                                              <div class="from__input-box">
                                                  <textarea id="tinymce_simple_textarea" name="description"></textarea>
                                              </div>
                                          </div>
                                      </div>
                                  </div>
                              </div>
                          </div>
                      </div>
                  </div>
                  <div class="submit__btn text-center mb-20">
                      <button class="btn btn-primary">Submit</button>
                  </div>
              </div>
          </form>
      </div>
  </div>
<script>
function handleProjectChange(val) {
    const wrap = document.getElementById('newProjectWrap');
    if (wrap) {
        wrap.style.display = (val === 'new') ? 'block' : 'none';
        if (val === 'new') {
            const input = document.getElementById('new_project_name');
            if (input) input.focus();
        }
    }
}
document.addEventListener("DOMContentLoaded", function() {
    if (typeof jQuery !== 'undefined') {
        jQuery('#project_id').on('change', function() {
            handleProjectChange(jQuery(this).val());
        });
    }
});
</script>