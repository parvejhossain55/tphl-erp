 <!-- Todays Follow up -->
 <div class="col-12 mt-3">
     <div class="card__wrapper">
         <div class="card__header d-flex justify-content-between align-items-center mb-3">
             <h5 class="card__title mb-0" id="transactionsTitle">
                 Today's Follow-ups
             </h5>

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
                                 <td><?= htmlspecialchars($followUp['phone_number'] ?? $followUp['phone'] ?? 'N/A') ?></td>
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