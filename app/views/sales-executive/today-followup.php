<!-- Todays Follow up -->
 <div class="col-12 mt-3">
     <div class="card__wrapper">
          <div class="card__header d-flex justify-content-between align-items-center mb-3">
              <h5 class="card__title mb-0" id="transactionsTitle">
                  Today's Follow-ups
              </h5>
          </div>

          <!-- Filters -->
          <div class="filters mb-3">
              <form action="" method="get">
                  <div class="row g-2 align-items-center flex-wrap">
                      <!-- Project Filter -->
                      <?php if (!empty($projects)): ?>
                      <div class="col-auto col-sm-6 col-md-3">
                          <?php $selectedProject = $_GET['project_id'] ?? ''; ?>
                          <select class="form-select form-select-sm filter-select select2" name="project_id" data-placeholder="Project: All">
                              <option value=""></option>
                              <?php foreach ($projects as $p): ?>
                                  <option value="<?= $p['id'] ?>" <?= $selectedProject == $p['id'] ? 'selected' : '' ?>>
                                      <?= htmlspecialchars($p['name']) ?>
                                  </option>
                              <?php endforeach; ?>
                          </select>
                      </div>
                      <?php endif; ?>

                      <!-- Category Filter -->
                      <?php if (!empty($projectCategories)): ?>
                      <div class="col-auto col-sm-6 col-md-3">
                          <?php $selectedCategory = $_GET['project_category_id'] ?? ''; ?>
                          <select class="form-select form-select-sm filter-select select2" name="project_category_id" data-placeholder="Category: All">
                              <option value=""></option>
                              <?php foreach ($projectCategories as $cat): ?>
                                  <option value="<?= $cat['id'] ?>" <?= $selectedCategory == $cat['id'] ? 'selected' : '' ?>>
                                      <?= htmlspecialchars($cat['name']) ?>
                                  </option>
                              <?php endforeach; ?>
                          </select>
                      </div>
                      <?php endif; ?>

                      <!-- Country Filter (Multi-Select) -->
                      <?php if (!empty($countries)): ?>
                      <div class="col-auto col-sm-6 col-md-3">
                          <?php 
                          $selectedCountry = $_GET['filter_country'] ?? [];
                          $selectedCountriesArr = is_array($selectedCountry) ? $selectedCountry : (array)$selectedCountry;
                          ?>
                          <select class="form-select form-select-sm filter-select select2" name="filter_country[]" multiple data-placeholder="Country: All">
                              <?php foreach ($countries as $c): ?>
                                  <option value="<?= htmlspecialchars($c) ?>" <?= in_array($c, $selectedCountriesArr) ? 'selected' : '' ?>>
                                      <?= htmlspecialchars($c) ?>
                                  </option>
                              <?php endforeach; ?>
                          </select>
                      </div>
                      <?php endif; ?>

                      <!-- Submit & Clear buttons -->
                      <div class="col-auto">
                          <div class="d-flex gap-2">
                              <button type="submit" class="btn btn-primary btn-sm px-3 shadow-sm d-flex align-items-center gap-1" style="height: 31px;">
                                  <i class="bi bi-filter" style="font-size: 14px;"></i> Filter
                              </button>
                              <?php if (!empty($_GET['project_id']) || !empty($_GET['project_category_id']) || !empty($_GET['filter_country'])): ?>
                              <a href="<?= $GLOBALS['base_url'] ?>/today-follow-up" class="btn btn-outline-danger btn-sm px-2 shadow-sm d-flex align-items-center gap-1" style="height: 31px;" title="Clear Filters">
                                  <i class="bi bi-x-circle" style="font-size: 14px;"></i> Clear
                              </a>
                              <?php endif; ?>
                          </div>
                      </div>
                  </div>
              </form>
          </div>

         <div class="table__wrapper table-responsive">
             <table class="table mb-20">
                 <thead>
                     <tr class="table__title">
                         <th>S/L</th>
                         <th>Last Contact Date</th>
                         <th>Lead Name</th>
                         <th>phone</th>
                         <th>Project</th>
                         <th>Flat</th>
                         <th>Last Decussion Note</th>

                         <th>Today Follow Up Action</th>
                         <th class="text-center">Note</th>
                         <th class="text-center">Status</th>
                         <th>Next Follow Up</th>
                     </tr>
                 </thead>
                 <tbody id="">
                     <?php if (!empty($todayFollowUpLeads)): ?>
                         <?php foreach ($todayFollowUpLeads as $key => $followUp): ?>
                             <tr>
                                 <td><?= $key + 1 ?></td>
                                 <td><?= date('d M, Y', strtotime($followUp['follow_up_date'])) ?></td>
                                 <td><?= htmlspecialchars($followUp['full_name'] ?? 'N/A') ?></td>
                                 <?php
                                    $phone = $followUp['phone_number'] ?? $followUp['phone'] ?? '';
                                    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

                                    // convert 01 → 8801
                                    if (substr($cleanPhone, 0, 2) === '01') {
                                        $cleanPhone = '880' . substr($cleanPhone, 1);
                                    }
                                    ?>

                                 <td>
                                     <?php if (!empty($cleanPhone)): ?>
                                         <a href="https://wa.me/<?= $cleanPhone ?>" target="_blank">
                                             <?= htmlspecialchars($phone) ?>
                                         </a>
                                     <?php else: ?>
                                         N/A
                                     <?php endif; ?>
                                 </td>
                                 <td><?= htmlspecialchars($followUp['project_name'] ?? 'N/A') ?></td>
                                 <td><?= htmlspecialchars($followUp['flat_name'] ?? 'N/A') ?></td>
                                 <td><?= htmlspecialchars($followUp['discussion_note'] ?? 'N/A') ?></td>
                                 <td class="call-status-cell">
                                     <select class="next-action" data-id="<?= $followUp['id'] ?>">
                                         <option value="">Select Client Action</option>
                                         <option value="site visit" <?= $followUp['next_action'] == 'site visit' ? 'selected' : '' ?>>Site visit</option>
                                         <option value="booking" <?= $followUp['next_action'] == 'booking' ? 'selected' : '' ?>>Booking</option>
                                         <option value="call_back" <?= $followUp['next_action'] == 'call_back' ? 'selected' : '' ?>>Call back</option>
                                         <option value="take_time" <?= $followUp['next_action'] == 'take_time' ? 'selected' : '' ?>>Take Time</option>
                                     </select>
                                 </td>



                                 <td class="note-cell">
                                     <div class="note-wrapper">
                                         <textarea class="call-note" data-id="<?= $followUp['id'] ?>" placeholder="Click to add note..."><?= htmlspecialchars($followUp['discussion_note'] ?? '') ?></textarea>
                                         <button type="button" class="btn btn-sm btn-success note-save-btn" data-id="<?= $followUp['id'] ?>">Save</button>

                                     </div>
                                 </td>
                                 <td class="call-status-cell">
                                     <select class="call-status" data-id="<?= $followUp['id'] ?>">
                                         <option value="completed" <?= $followUp['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                                         <option value="pending" <?= $followUp['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                         <option value="missed" <?= $followUp['status'] == 'missed' ? 'selected' : '' ?>>Missed</option>
                                         <option value="canceld" <?= $followUp['status'] == 'canceld' ? 'selected' : '' ?>>Canceld</option>
                                     </select>


                                 </td>
                                 <td>
                                     <a href="<?= $GLOBALS['base_url'] ?>/follow-up-leads/<?= $followUp['lead_id'] ?>"
                                         class="btn btn-primary d-flex align-items-center gap-2 justify-content-between">

                                         <span>Follow Up Action</span>
                                     </a>
                                 </td>
                             </tr>
                         <?php endforeach; ?>
                     <?php else: ?>
                         <tr>
                             <td colspan="10" class="text-center py-4">
                                 <div class="text-muted">
                                     No follow-ups found
                                 </div>
                             </td>
                         </tr>
                     <?php endif; ?>
                 </tbody>
             </table>
         </div>
     </div>
 </div>

 <script>
     // Status change event
     document.querySelectorAll('.call-status').forEach(select => {
         select.addEventListener('change', function() {
             const followUpId = this.dataset.id;
             const note = document.querySelector(`.call-note[data-id='${followUpId}']`).value;

             fetch('<?= $GLOBALS['base_url'] ?>/follow-up/update-status-note', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/x-www-form-urlencoded'
                     },
                     body: `id=${followUpId}&status=${this.value}&discussion_note=${encodeURIComponent(note)}`
                 })
                 .then(res => res.json())
                 .then(data => {
                     if (data.success) {
                         showMessage(data.message, 'success');
                     } else if (data.error) {
                         showMessage(data.error, 'error');
                     }
                 });
         });
     });
     // Status Next  event
     document.querySelectorAll('.next-action').forEach(select => {
         select.addEventListener('change', function() {
             const followUpId = this.dataset.id;
             const note = document.querySelector(`.call-note[data-id='${followUpId}']`).value;
             const nextAction = this.value;
             const status = document.querySelector(`.call-status[data-id='${followUpId}']`).value;

             fetch('<?= $GLOBALS['base_url'] ?>/follow-up/update-status-note', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/x-www-form-urlencoded'
                     },
                     body: `id=${followUpId}&status=${status}&next_action=${nextAction}&discussion_note=${encodeURIComponent(note)}`
                 })
                 .then(res => res.json())
                 .then(data => {
                     if (data.success) {
                         showMessage(data.message, 'success');
                     } else if (data.error) {
                         showMessage(data.error, 'error');
                     }
                 });
         });
     });


     // Note save button event
     document.querySelectorAll('.note-save-btn').forEach(btn => {
         btn.addEventListener('click', function() {
             const followUpId = this.dataset.id;
             const note = document.querySelector(`.call-note[data-id='${followUpId}']`).value;
             const status = document.querySelector(`.call-status[data-id='${followUpId}']`).value;

             fetch('<?= $GLOBALS['base_url'] ?>/follow-up/update-status-note', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/x-www-form-urlencoded'
                     },
                     body: `id=${followUpId}&status=${status}&discussion_note=${encodeURIComponent(note)}`
                 })
                 .then(res => res.json())
                 .then(data => {
                     if (data.success) {
                         showMessage(data.message, 'success');
                     } else if (data.error) {
                         showMessage(data.error, 'error');
                     }
                 });
         });
     });

     // Simple showMessage function
     function showMessage(message, type = 'success') {
         let msgBox = document.querySelector('#followUpMessage');
         if (!msgBox) {
             msgBox = document.createElement('div');
             msgBox.id = 'followUpMessage';
             msgBox.style.position = 'fixed';
             msgBox.style.top = '20px';
             msgBox.style.right = '20px';
             msgBox.style.padding = '10px 20px';
             msgBox.style.borderRadius = '5px';
             msgBox.style.zIndex = 9999;
             document.body.appendChild(msgBox);
         }

         msgBox.innerText = message;
         msgBox.style.backgroundColor = type === 'success' ? '#4CAF50' : '#F44336';
         msgBox.style.color = '#fff';
         msgBox.style.display = 'block';

         setTimeout(() => {
             msgBox.style.display = 'none';
         }, 3000);
     }
 </script>