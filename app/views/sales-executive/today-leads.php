   <?php
    $page_title = "User Dashboard ";
    ?>

   <div class="row">
       <div class="card__wrapper">

           <div class="col-12">
               <h4 class="mb-0">Today’s Leads</h4>
               <small class="text-muted">All leads created today</small>
           </div>
       </div>

       <div class="row">
           <div class="col-xxl-12">
               <div class="card__wrapper">
                   <div class="table__wrapper table-responsive">
                       <table class="table mb-20 multiple_tables">
                           <thead>
                               <tr class="table__title table__sort">


                                   <th style="min-width:60px;">S/L</th> <!-- non-sticky first column -->
                                   <th class="th_1">Client/Lead Name</th>

                                   <!-- <th>Source Type</th> -->

                                   <th>Company name</th>
                                   <th>phone_number</th>
                                   <th>Follow Up</th>
                                   <th class="d-none">Booking</th>
                                   <th class="d-none">job title</th>
                                   <th class="d-none">Interested Apartment Size</th>
                                   <th class="d-none">Interset Floor for Shop</th>
                                   <th class="d-none">Planning To Buy Time</th>
                                   <th>Best Time to Contact</th>
                                   <th>city</th>
                                   <th class="d-none">Interest On</th>
                                   <th class="text-center">Status</th>
                                   <th class="text-center">Action</th>
                                   <th class="text-center">Note</th>
                               </tr>
                           </thead>
                           <tbody class="table__body">
                               <?php foreach ($todayLeads as $key => $lead): ?>
                                   <tr>
                                       <!-- S/L -->
                                       <td><?= $key + 1 ?></td>

                                       <!-- Client / Lead Name -->
                                       <td><?= htmlspecialchars($lead['full_name'] ?? 'N/A') ?></td>

                                       <!-- Company / Project -->
                                       <td><?= htmlspecialchars(!empty($lead['company_name']) ? $lead['company_name'] : 'N/A') ?></td>

                                       <!-- Phone -->
                                       <td><?= htmlspecialchars($lead['phone_number'] ?? 'N/A') ?></td>

                                       <!-- Job Title -->
                                       <td class="d-none"><?= htmlspecialchars($lead['job_title'] ?? 'N/A') ?></td>

                                       <!-- Apartment Size -->
                                       <td class="d-none">
                                           <?= htmlspecialchars($lead['which_apartment_size_are_you_interested_in'] ?? $lead['pref_flat_size'] ?? 'N/A') ?>
                                       </td>

                                       <!-- Floor -->
                                       <td class="d-none">
                                           <?= htmlspecialchars($lead['which_floor_are_you_for_shop'] ?? 'N/A') ?>
                                       </td>

                                       <!-- Planning to Buy -->
                                       <td class="d-none">
                                           <?= htmlspecialchars($lead['when_are_you_planning_to_buy'] ?? 'N/A') ?>
                                       </td>

                                       <td>
                                           <a href="<?= $GLOBALS['base_url'] ?>/follow-up-leads/<?= $lead['id'] ?>"
                                               class="btn btn-primary d-flex align-items-center gap-2 justify-content-between">

                                               <span>Follow Up</span>

                                               <?php if (!empty($followUpCounts[$lead['id']])): ?>
                                                   <span class="followup-badge">
                                                       <?= $followUpCounts[$lead['id']] ?>
                                                   </span>
                                               <?php endif; ?>
                                           </a>
                                       </td>
                                       <td class="d-none">
                                           <a href="<?= $GLOBALS['base_url'] ?>/booking-leads/<?= $lead['id'] ?>"
                                               class="btn btn-secondary d-flex align-items-center gap-2 justify-content-between">
                                               <?php if (!empty($bookingCount[$lead['id']])): ?>
                                                   <span>Booked </span>
                                                   <span class="followup-badge">
                                                       <?= $bookingCount[$lead['id']] ?>
                                                   </span>
                                               <?php else: ?>
                                                   <span>Booking </span>
                                               <?php endif ?>
                                           </a>
                                       </td>


                                       <!-- Best Time to Contact -->
                                       <td>
                                           <?= htmlspecialchars($lead['when_is_the_best_time_to_contact_you'] ?? 'N/A') ?>
                                       </td>

                                       <!-- City -->
                                       <td><?= htmlspecialchars($lead['city'] ?? 'N/A') ?></td>

                                       <!-- Hidden Interest On -->
                                       <td class="d-none"><?= htmlspecialchars($lead['interest_on'] ?? '') ?></td>

                                       <!-- Action -->
                                       <td>
                                           <button
                                               class="btn btn-sm btn-success call-btn"
                                               data-id="<?= $lead['id'] ?>"
                                               data-phone="<?= $lead['phone_number'] ?>">
                                               <?= $lead['call_status'] ?>
                                           </button>
                                       </td>
                                       <td class="call-status-cell">
                                           <select
                                               class="call-status"
                                               data-id="<?= $lead['id'] ?>">
                                               <option value="not_called" <?= $lead['call_status'] == 'not_called' ? 'selected' : '' ?>>Not Called</option>
                                               <option value="called" <?= $lead['call_status'] == 'called' ? 'selected' : '' ?>>Called</option>
                                               <option value="received" <?= $lead['call_status'] == 'received' ? 'selected' : '' ?>>Received</option>
                                               <option value="not_received" <?= $lead['call_status'] == 'not_received' ? 'selected' : '' ?>>Not Received</option>
                                           </select>
                                       </td>


                                       <td class="note-cell">
                                           <div class="note-wrapper">
                                               <textarea
                                                   class="call-note"
                                                   data-id="<?= $lead['id'] ?>"
                                                   placeholder="Click to add note..."><?= htmlspecialchars($lead['call_note'] ?? '') ?></textarea>
                                               <button type="button" class="btn btn-sm btn-success note-save-btn" data-id="<?= $lead['id'] ?>">Save</button>
                                           </div>
                                       </td>



                                   </tr>
                               <?php endforeach ?>
                           </tbody>

                       </table>
                   </div>
               </div>
           </div>
       </div>
   </div>
   <script>
       document.querySelectorAll('.call-btn').forEach(btn => {
           btn.addEventListener('click', function() {
               let leadId = this.dataset.id;
               let phone = this.dataset.phone;

               // Mobile call
               window.location.href = 'tel:' + phone;

               // Update call status = called
               fetch('<?= $GLOBALS['base_url'] ?>/update-call-status', {
                   method: 'POST',
                   headers: {
                       'Content-Type': 'application/x-www-form-urlencoded'
                   },
                   body: 'lead_id=' + leadId + '&call_status=called'
               });
           });
       });

       // received or not received 
       document.querySelectorAll('.call-status').forEach(select => {
           select.addEventListener('change', function() {
               fetch('<?= $base_url ?>/update-call-status', {
                   method: 'POST',
                   headers: {
                       'Content-Type': 'application/x-www-form-urlencoded'
                   },
                   body: 'lead_id=' + this.dataset.id + '&call_status=' + this.value
               });
           });
       });


       document.querySelectorAll('.note-save-btn').forEach(btn => {
           btn.addEventListener('click', function() {
               const leadId = this.dataset.id;
               const textarea = document.querySelector('.call-note[data-id="' + leadId + '"]');
               saveNote(leadId, textarea.value, textarea);
           });
       });

       document.querySelectorAll('.call-note').forEach(textarea => {
           textarea.addEventListener('keydown', function(e) {
               if (e.key === 'Enter' && !e.shiftKey) { // shift+enter for newline
                   e.preventDefault(); // avoid new line
                   saveNote(this.dataset.id, this.value, this);
               }
           });
       });

       function saveNote(leadId, note, el) {
           fetch('<?= $base_url ?>/update-call-notes', {
                   method: 'POST',
                   headers: {
                       'Content-Type': 'application/x-www-form-urlencoded'
                   },
                   body: 'lead_id=' + encodeURIComponent(leadId) + '&call_note=' + encodeURIComponent(note)
               })
               .then(res => res.text())
               .then(() => {
                   el.style.borderColor = '#198754'; // success hint
                   setTimeout(() => el.style.borderColor = '#ddd', 800);
               });
       }
   </script>